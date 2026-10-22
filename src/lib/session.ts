import { getCookie, setCookie, deleteCookie } from 'hono/cookie';
import type { Context } from 'hono';
import { eq, lt } from 'drizzle-orm';
import type { Db } from '../db';
import { sessions, users, ROLE_VALUES } from '../db/schema';

export const SESSION_COOKIE = 'gs_session';
const SESSION_TTL_MS = 1000 * 60 * 60 * 24 * 30;
const TOUCH_INTERVAL_MS = 1000 * 60 * 5;

export type SessionUser = {
  id: number;
  name: string;
  email: string;
  role: (typeof ROLE_VALUES)[number];
  points: number;
};

export async function createSession(
  db: Db,
  userId: number,
  meta: { ip?: string | null; userAgent?: string | null } = {},
): Promise<string> {
  const token = randomToken();
  const now = new Date();
  await db.insert(sessions).values({
    id: token,
    userId,
    ip: meta.ip ?? null,
    userAgent: meta.userAgent ?? null,
    lastSeenAt: now,
    expiresAt: new Date(now.getTime() + SESSION_TTL_MS),
  });
  return token;
}

export function setSessionCookie(c: Context, token: string): void {
  setCookie(c, SESSION_COOKIE, token, {
    httpOnly: true,
    secure: true,
    sameSite: 'Lax',
    path: '/',
    maxAge: SESSION_TTL_MS / 1000,
  });
}

export function clearSessionCookie(c: Context): void {
  deleteCookie(c, SESSION_COOKIE, { path: '/' });
}

export async function resolveSession(
  db: Db,
  c: Context,
): Promise<SessionUser | null> {
  const token = getCookie(c, SESSION_COOKIE);
  if (!token) return null;
  const rows = await db
    .select({
      sessionId: sessions.id,
      expiresAt: sessions.expiresAt,
      lastSeenAt: sessions.lastSeenAt,
      userId: users.id,
      name: users.name,
      email: users.email,
      role: users.role,
      points: users.points,
      active: users.active,
    })
    .from(sessions)
    .innerJoin(users, eq(sessions.userId, users.id))
    .where(eq(sessions.id, token))
    .limit(1);
  const row = rows[0];
  if (!row) return null;
  if (row.expiresAt.getTime() < Date.now()) {
    await db.delete(sessions).where(eq(sessions.id, token));
    return null;
  }
  if (!row.active) return null;
  if (
    !row.lastSeenAt ||
    Date.now() - row.lastSeenAt.getTime() > TOUCH_INTERVAL_MS
  ) {
    await db
      .update(sessions)
      .set({ lastSeenAt: new Date() })
      .where(eq(sessions.id, token));
  }
  return {
    id: row.userId,
    name: row.name,
    email: row.email,
    role: row.role,
    points: row.points,
  };
}

export async function destroySession(db: Db, c: Context): Promise<void> {
  const token = getCookie(c, SESSION_COOKIE);
  if (token) {
    await db.delete(sessions).where(eq(sessions.id, token));
  }
  clearSessionCookie(c);
}

export async function destroyAllUserSessions(
  db: Db,
  userId: number,
): Promise<void> {
  await db.delete(sessions).where(eq(sessions.userId, userId));
}

export async function deleteExpiredSessions(db: Db): Promise<void> {
  await db.delete(sessions).where(lt(sessions.expiresAt, new Date()));
}

function randomToken(): string {
  const bytes = crypto.getRandomValues(new Uint8Array(32));
  let binary = '';
  for (const b of bytes) binary += String.fromCharCode(b);
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}
