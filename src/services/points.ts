import { eq, sql } from 'drizzle-orm';
import type { Db } from '../db';
import { pointLogs, users } from '../db/schema';

export const LOGIN_POINTS = 10;

function fmtDate(date: Date): string {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: 'America/Argentina/Buenos_Aires',
  }).format(date);
}

export function todayInBuenosAires(): string {
  return fmtDate(new Date());
}

export function yesterdayInBuenosAires(): string {
  return fmtDate(new Date(Date.now() - 86_400_000));
}

export async function awardLoginPoints(
  db: Db,
  user: {
    id: number;
    lastLoginDay: string | null;
    loginStreak: number;
    role: string;
  },
): Promise<number> {
  if (user.role === 'admin') return 0;
  const today = todayInBuenosAires();
  if (user.lastLoginDay === today) return 0;
  const streak =
    user.lastLoginDay === yesterdayInBuenosAires()
      ? user.loginStreak + 1
      : 1;
  await db
    .update(users)
    .set({
      lastLoginDay: today,
      loginStreak: streak,
      points: sql`${users.points} + ${LOGIN_POINTS}`,
    })
    .where(eq(users.id, user.id));
  await db.insert(pointLogs).values({
    userId: user.id,
    points: LOGIN_POINTS,
    reason: 'login',
  });
  return LOGIN_POINTS;
}
