import type { Context, MiddlewareHandler, Next } from 'hono';
import type { AppEnv } from '../env';

export interface RateLimitOptions {
  /** Function to generate a unique key for the rate limit bucket */
  keyGenerator: (c: Context<AppEnv>) => string;
  /** Maximum number of requests allowed in the window */
  limit: number;
  /** Time window in milliseconds */
  windowMs: number;
  /** Custom message when rate limited */
  message?: string;
  /** Custom status code (default: 429) */
  statusCode?: number;
  /** Whether to include standard rate limit headers (default: true) */
  standardHeaders?: boolean;
}

/**
 * Rate limit entry stored in KV
 */
interface RateLimitEntry {
  count: number;
  resetTime: number;
}

/**
 * Creates a rate limiting middleware using KV for distributed counting.
 * Uses a fixed window algorithm with sliding window fallback for smoother limiting.
 */
export function rateLimit(options: RateLimitOptions): MiddlewareHandler<AppEnv> {
  const {
    keyGenerator,
    limit,
    windowMs,
    message = 'Demasiadas solicitudes. Por favor, intente de nuevo más tarde.',
    statusCode = 429,
    standardHeaders = true,
  } = options;

  const windowSec = Math.ceil(windowMs / 1000);

  return async (c: Context<AppEnv>, next: Next) => {
    const kv = c.env.SESSIONS;
    const key = `ratelimit:${keyGenerator(c)}:${Math.floor(Date.now() / windowMs)}`;
    const now = Date.now();
    const resetTime = Math.ceil((Math.floor(now / windowMs) + 1) * windowMs / 1000);

    // Try to get current count
    const entry = await kv.get<RateLimitEntry>(key, 'json');
    const currentCount = entry?.count ?? 0;

    if (currentCount >= limit) {
      // Rate limited
      const retryAfter = Math.ceil((resetTime * 1000 - now) / 1000);

      if (standardHeaders) {
        c.header('X-RateLimit-Limit', String(limit));
        c.header('X-RateLimit-Remaining', '0');
        c.header('X-RateLimit-Reset', String(resetTime));
        c.header('Retry-After', String(retryAfter));
      }

      return c.json({ error: message }, statusCode as 429, {
        'Retry-After': String(retryAfter),
      });
    }

    // Increment counter
    const newCount = currentCount + 1;
    const ttl = windowSec + 1; // Extra second for safety

    await kv.put(
      key,
      JSON.stringify({ count: newCount, resetTime }),
      { expirationTtl: ttl }
    );

    if (standardHeaders) {
      c.header('X-RateLimit-Limit', String(limit));
      c.header('X-RateLimit-Remaining', String(limit - newCount));
      c.header('X-RateLimit-Reset', String(resetTime));
    }

    await next();
  };
}

/**
 * Creates a rate limiter keyed by client IP address.
 * Uses the `cf-connecting-ip` header (Cloudflare) with fallback to `x-forwarded-for`.
 */
export function ipRateLimit(
  limit: number,
  windowMs: number,
  message?: string
): MiddlewareHandler<AppEnv> {
  return rateLimit({
    keyGenerator: (c) => {
      const ip =
        c.req.header('cf-connecting-ip') ??
        c.req.header('x-forwarded-for')?.split(',')[0]?.trim() ??
        'unknown';
      return `ip:${ip}`;
    },
    limit,
    windowMs,
    message,
  });
}

/**
 * Creates a rate limiter keyed by authenticated user ID.
 * Falls back to IP if user is not authenticated.
 */
export function userRateLimit(
  limit: number,
  windowMs: number,
  message?: string
): MiddlewareHandler<AppEnv> {
  return rateLimit({
    keyGenerator: (c) => {
      const user = c.get('user');
      if (user) {
        return `user:${user.id}`;
      }
      const ip =
        c.req.header('cf-connecting-ip') ??
        c.req.header('x-forwarded-for')?.split(',')[0]?.trim() ??
        'unknown';
      return `ip:${ip}`;
    },
    limit,
    windowMs,
    message,
  });
}

/**
 * Pre-configured rate limiters for common use cases.
 */

// 5 requests per minute per IP for login attempts
export const loginRateLimit = ipRateLimit(5, 60_000, 'Demasiados intentos de acceso. Espere un minuto antes de volver a intentarlo.');

// 3 requests per minute per IP for registration
export const registerRateLimit = ipRateLimit(3, 60_000, 'Demasiados registros. Espere un minuto antes de volver a intentarlo.');

// 100 requests per minute per authenticated user for admin routes
export const adminRateLimit = userRateLimit(100, 60_000, 'Demasiadas solicitudes al panel de administración.');

// 60 requests per minute per authenticated user for API routes
export const apiRateLimit = userRateLimit(60, 60_000, 'Límite de API excedido. Reduzca la frecuencia de sus solicitudes.');