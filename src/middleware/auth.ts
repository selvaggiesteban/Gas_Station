import type { Bindings, AppEnv } from '../env';
import type { Context, MiddlewareHandler, Next } from 'hono';
import { createDb, type Db } from '../db';
import { resolveSession, type SessionUser } from '../lib/session';
import { page } from '../lib/html';

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

export type { AppEnv, Bindings, Next, SessionUser };
