import { and, count, eq, gte, lte, sql, sum } from 'drizzle-orm';
import type { Db } from '../db';
import {
  challengeParticipations,
  orders,
  pointLogs,
  users,
  weeklyChallenges,
  type GOAL_TYPE_VALUES,
} from '../db/schema';
import type { SessionUser } from '../lib/session';

export type GoalType = (typeof GOAL_TYPE_VALUES)[number];

export type Challenge = typeof weeklyChallenges.$inferSelect;

export type ProgressUser = {
  id: number;
  role: SessionUser['role'];
};

export type JoinResult = 'joined' | 'already' | 'inactive' | 'not_found';

export function goalTypeLabel(goalType: GoalType): string {
  switch (goalType) {
    case 'points':
      return 'puntos';
    case 'login_days':
      return 'días de sesión';
    case 'sales_amount':
      return 'en ventas';
  }
}

export function formatGoalValue(goalType: GoalType, goalValue: number): string {
  if (goalType === 'sales_amount') {
    return `${new Intl.NumberFormat('es-AR', {
      style: 'currency',
      currency: 'ARS',
    }).format(goalValue / 100)} en ventas`;
  }
  return `${goalValue} ${goalTypeLabel(goalType)}`;
}

export async function computeProgress(
  db: Db,
  user: ProgressUser,
  challenge: Challenge,
): Promise<number> {
  if (user.role === 'admin') return 0;
  const startsAt = challenge.startsAt;
  const endsAt = challenge.endsAt;

  switch (challenge.goalType) {
    case 'points': {
      const rows = await db
        .select({ total: sum(pointLogs.points) })
        .from(pointLogs)
        .where(
          and(
            eq(pointLogs.userId, user.id),
            gte(pointLogs.createdAt, startsAt),
            lte(pointLogs.createdAt, endsAt),
          ),
        );
      return Number(rows[0]?.total ?? 0) || 0;
    }
    case 'login_days': {
      const rows = await db
        .select({ total: count() })
        .from(pointLogs)
        .where(
          and(
            eq(pointLogs.userId, user.id),
            eq(pointLogs.reason, 'login'),
            gte(pointLogs.createdAt, startsAt),
            lte(pointLogs.createdAt, endsAt),
          ),
        );
      return Number(rows[0]?.total ?? 0) || 0;
    }
    case 'sales_amount': {
      const ownerFilter =
        user.role === 'customer'
          ? eq(orders.userId, user.id)
          : eq(orders.gestorId, user.id);
      const rows = await db
        .select({ total: sum(orders.totalCents) })
        .from(orders)
        .where(
          and(
            ownerFilter,
            eq(orders.status, 'paid'),
            gte(orders.createdAt, startsAt),
            lte(orders.createdAt, endsAt),
          ),
        );
      return Number(rows[0]?.total ?? 0) || 0;
    }
  }
}

export async function joinChallenge(
  db: Db,
  userId: number,
  challengeId: number,
  userRole: string,
): Promise<JoinResult> {
  if (userRole === 'admin') return 'inactive';
  const rows = await db
    .select()
    .from(weeklyChallenges)
    .where(eq(weeklyChallenges.id, challengeId))
    .limit(1);
  const challenge = rows[0];
  if (!challenge) return 'not_found';
  const now = new Date();
  if (
    !challenge.active ||
    challenge.startsAt.getTime() > now.getTime() ||
    challenge.endsAt.getTime() < now.getTime()
  ) {
    return 'inactive';
  }
  const existing = await db
    .select({ id: challengeParticipations.id })
    .from(challengeParticipations)
    .where(
      and(
        eq(challengeParticipations.challengeId, challengeId),
        eq(challengeParticipations.userId, userId),
      ),
    )
    .limit(1);
  if (existing[0]) return 'already';
  try {
    await db
      .insert(challengeParticipations)
      .values({ challengeId, userId, progress: 0 });
  } catch {
    return 'already';
  }
  return 'joined';
}

export async function refreshUserChallenges(
  db: Db,
  user: ProgressUser,
): Promise<void> {
  if (user.role === 'admin') return;
  const now = new Date();
  const active = await db
    .select()
    .from(weeklyChallenges)
    .where(
      and(
        eq(weeklyChallenges.active, true),
        lte(weeklyChallenges.startsAt, now),
        gte(weeklyChallenges.endsAt, now),
      ),
    );
  if (active.length === 0) return;
  const participations = await db
    .select()
    .from(challengeParticipations)
    .where(eq(challengeParticipations.userId, user.id));
  const byChallenge = new Map(
    participations.map((p) => [p.challengeId, p]),
  );

  for (const challenge of active) {
    const participation = byChallenge.get(challenge.id);
    if (!participation) continue;
    const progress = await computeProgress(db, user, challenge);
    const completed = progress >= challenge.goalValue;
    const now2 = new Date();
    await db
      .update(challengeParticipations)
      .set({
        progress,
        ...(completed && !participation.completedAt
          ? { completedAt: now2 }
          : {}),
      })
      .where(eq(challengeParticipations.id, participation.id));
    if (completed && !participation.rewardedAt) {
      await db
        .update(challengeParticipations)
        .set({ rewardedAt: now2 })
        .where(eq(challengeParticipations.id, participation.id));
      await db
        .update(users)
        .set({ points: sql`${users.points} + ${challenge.rewardPoints}` })
        .where(eq(users.id, user.id));
      await db.insert(pointLogs).values({
        userId: user.id,
        points: challenge.rewardPoints,
        reason: 'challenge',
        referenceType: 'weekly_challenge',
        referenceId: challenge.id,
      });
    }
  }
}
