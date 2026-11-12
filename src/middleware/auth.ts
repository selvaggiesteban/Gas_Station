import type { Bindings, AppEnv } from '../env';
import type { Context, MiddlewareHandler, Next } from 'hono';
import { createDb, type Db } from '../db';
import { resolveSession, type SessionUser } from '../lib/session';
import { page } from '../lib/html';
import { mfaSecrets } from '../db/schema';
import { eq } from 'drizzle-orm';
import { decryptSecret } from '../lib/crypto';

export function dbOf(c: Context<AppEnv>): Db {
  return createDb(c.env.DB);
}

export function brandOf(c: Context<AppEnv>): string {
  return c.env.BRAND_NAME || 'LESCO GNC';
}

export const attachUser: MiddlewareHandler<AppEnv> = async (c, next) => {
  const db = dbOf(c);
  c.set('user', await resolveSession(db, c));
  await next();
};

export function requireAuth(): MiddlewareHandler<AppEnv> {
  return async (c, next) => {
    if (!c.get('user')) {
      return c.redirect('/login');
    }
    await next();
  };
}

export function requireRole(
  ...roles: SessionUser['role'][]
): MiddlewareHandler<AppEnv> {
  return async (c, next) => {
    const user = c.get('user');
    if (!user) return c.redirect('/login');
    if (!roles.includes(user.role)) {
      return c.html(
        page(
          { title: 'Sin permiso', brand: brandOf(c), user },
          `<div class="card"><h1>No tienes permiso para ver esta sección</h1>
           <p class="muted">Tu rol actual: ${user.role}. Si creés que es un error, contactate con el administrador.</p>
           <a class="btn" href="/app">Volver al inicio</a></div>`,
        ),
        403,
      );
    }
    await next();
  };
}

/**
 * Middleware to require MFA verification for sensitive admin actions.
 * Checks if the user has MFA enabled and if they've verified it in the current session.
 * If not, redirects to the MFA verification page.
 */
export function requireMFA(): MiddlewareHandler<AppEnv> {
  return async (c, next) => {
    const user = c.get('user');
    if (!user) return c.redirect('/login');

    const db = dbOf(c);
    const mfa = await db
      .select()
      .from(mfaSecrets)
      .where(eq(mfaSecrets.userId, user.id))
      .limit(1);

    // If MFA is not enabled for this user, allow access
    if (!mfa[0] || !mfa[0].enabled) {
      return next();
    }

    // Check if user has verified MFA in this session
    const mfaVerified = c.get('mfaVerified');
    if (mfaVerified === true) {
      return next();
    }

    // Store the intended destination and redirect to MFA verification
    const intendedPath = c.req.path + (c.req.query() ? `?${c.req.query()}` : '');
    return c.redirect(`/admin/mfa/verify?redirect=${encodeURIComponent(intendedPath)}`);
  };
}

/**
 * Middleware to audit log all admin requests (POST, PUT, DELETE).
 * Logs: user_id, action (method+path), resource, resource_id, metadata (sanitized body), ip, user_agent
 */
export function auditLog(): MiddlewareHandler<AppEnv> {
  return async (c, next) => {
    const method = c.req.method;
    const path = c.req.path;

    // Only audit mutating requests on /admin/*
    if (!path.startsWith('/admin/') || !['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
      return next();
    }

    const user = c.get('user');
    if (!user) return next();

    // Read and sanitize request body for metadata
    let metadata: Record<string, unknown> = {};
    try {
      const clonedReq = c.req.raw.clone();
      const contentType = clonedReq.headers.get('content-type') || '';
      if (contentType.includes('application/json')) {
        const body = (await clonedReq.json()) as Record<string, unknown>;
        metadata = sanitizeMetadata(body);
      } else if (contentType.includes('application/x-www-form-urlencoded') || contentType.includes('multipart/form-data')) {
        const formData = await clonedReq.formData();
        const obj: Record<string, unknown> = {};
        for (const [key, value] of formData.entries()) {
          obj[key] = value;
        }
        metadata = sanitizeMetadata(obj);
      }
    } catch {
      // Ignore body parsing errors
    }

    // Extract resource and resource_id from path
    const { resource, resourceId } = parseResourceFromPath(path);

    // Get client IP and user agent
    const ip = c.req.header('cf-connecting-ip') || c.req.header('x-forwarded-for') || 'unknown';
    const userAgent = c.req.header('user-agent') || 'unknown';

    // Log after the request completes
    const startTime = Date.now();
    await next();

    // Don't log if the response is a redirect to login (auth failure)
    if (c.res.status === 302 && c.res.headers.get('location')?.includes('/login')) {
      return;
    }

    try {
      const db = dbOf(c);
      await db.insert(auditLogs).values({
        userId: user.id,
        action: `${method} ${path}`,
        resource,
        resourceId,
        metadata: JSON.stringify(metadata),
        ip,
        userAgent,
      });
    } catch (err) {
      console.error('Audit log failed:', err);
    }
  };
}

// Import auditLogs table for the middleware
import { auditLogs } from '../db/schema';

/**
 * Sanitizes metadata by removing sensitive fields.
 */
function sanitizeMetadata(obj: Record<string, unknown>): Record<string, unknown> {
  const sensitiveKeys = ['password', 'password2', 'token', 'secret', 'code', 'backupCode', 'authorization', 'cookie', 'csrf'];
  const sanitized: Record<string, unknown> = {};
  for (const [key, value] of Object.entries(obj)) {
    const lowerKey = key.toLowerCase();
    if (sensitiveKeys.some((sk) => lowerKey.includes(sk))) {
      sanitized[key] = '[REDACTED]';
    } else if (typeof value === 'object' && value !== null) {
      sanitized[key] = sanitizeMetadata(value as Record<string, unknown>);
    } else {
      sanitized[key] = value;
    }
  }
  return sanitized;
}

/**
 * Parses resource type and ID from admin path.
 * e.g., /admin/usuarios/123/editar -> { resource: 'usuarios', resourceId: 123 }
 */
function parseResourceFromPath(path: string): { resource: string; resourceId: number | null } {
  // Remove /admin prefix
  const adminPath = path.replace(/^\/admin\//, '');
  const parts = adminPath.split('/').filter(Boolean);

  if (parts.length === 0) return { resource: 'admin', resourceId: null };

  const resource = parts[0];
  let resourceId: number | null = null;

  // Look for numeric ID in path parts
  for (const part of parts) {
    const num = Number(part);
    if (Number.isInteger(num) && num > 0) {
      resourceId = num;
      break;
    }
  }

  return { resource: resource ?? 'admin', resourceId };
}

export type { AppEnv, Bindings, Next, SessionUser };
