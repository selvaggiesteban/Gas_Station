import { Hono } from 'hono';
import { and, desc, eq, gte, inArray, isNotNull, lte, or } from 'drizzle-orm';
import type { AppEnv } from '../env';
import { dbOf, brandOf, requireAuth } from '../middleware/auth';
import { page, escapeHtml } from '../lib/html';
import { hashPassword, verifyPassword, iterationsFromEnv } from '../lib/crypto';
import {
  announcements,
  challengeParticipations,
  pointLogs,
  users,
  weeklyChallenges,
  type ANNOUNCEMENT_AUDIENCE_VALUES,
} from '../db/schema';
import type { Db } from '../db';
import type { SessionUser } from '../lib/session';
import {
  computeProgress,
  formatGoalValue,
  joinChallenge,
  refreshUserChallenges,
  type Challenge,
} from '../services/challenges';

export const appRoutes = new Hono<AppEnv>();

function str(v: unknown): string {
  return typeof v === 'string' ? v.trim() : '';
}

const DATE_FMT = new Intl.DateTimeFormat('es-AR', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  timeZone: 'America/Argentina/Buenos_Aires',
});

const POINT_REASON_LABELS: Record<string, string> = {
  challenge: 'Desafío completado',
  login: 'Inicio de sesión',
  order: 'Compra',
  admin_adjust: 'Ajuste administrativo',
};

function audienceCondition(role: SessionUser['role']) {
  if (role === 'admin') return undefined;
  const audience: (typeof ANNOUNCEMENT_AUDIENCE_VALUES)[number] | null =
    role === 'customer'
      ? 'customers'
      : role === 'store_manager'
        ? 'store_managers'
        : null;
  if (!audience) return eq(announcements.audience, 'all');
  return or(
    eq(announcements.audience, 'all'),
    eq(announcements.audience, audience),
  );
}

const PANEL_NAV = `<nav class="bottom" style="margin-bottom:1rem">
  <a href="/app">Inicio</a>
  <a href="/ranking">Ranking</a>
  <a href="/tienda">Tienda</a>
  <a href="/perfil">Tu perfil</a>
</nav>`;

function progressBar(progress: number, goalValue: number): string {
  const pct =
    goalValue > 0
      ? Math.min(100, Math.round((progress / goalValue) * 100))
      : progress > 0
        ? 100
        : 0;
  return `<div style="background:#e7e5e4;border-radius:999px;height:0.5rem;max-width:20rem">
    <div style="background:#eb7716;height:0.5rem;border-radius:999px;width:${pct}%"></div>
  </div>`;
}

function challengeCard(
  challenge: Challenge,
  progress: number,
  participation: { completedAt: Date | null } | undefined,
): string {
  const state = participation
    ? participation.completedAt
      ? `<span class="pill">Completado</span>
         <p class="muted">¡Listo! Completaste el desafío y ganaste ${challenge.rewardPoints} puntos.</p>`
      : `<span class="muted">Ya estás participando. ¡Sumá para completarlo!</span>`
    : `<form method="post" action="/desafios/${challenge.id}/unirse" style="display:inline">
         <button type="submit">Unirme</button>
       </form>`;
  return `<div style="border-top:1px solid #e7e5e4;padding:0.85rem 0">
    <h3 style="margin:0 0 0.25rem;font-size:1rem">${escapeHtml(challenge.title)}</h3>
    ${challenge.description ? `<p class="muted" style="margin:0 0 0.5rem">${escapeHtml(challenge.description)}</p>` : ''}
    <p style="margin:0 0 0.5rem">Meta: <strong>${escapeHtml(formatGoalValue(challenge.goalType, challenge.goalValue))}</strong>
      · Recompensa: <span class="pill">+${challenge.rewardPoints} pts</span></p>
    <p style="margin:0 0 0.35rem">Progreso: <strong>${progress} / ${challenge.goalValue}</strong></p>
    ${progressBar(progress, challenge.goalValue)}
    <p style="margin:0.5rem 0 0">${state}</p>
  </div>`;
}

async function loadActiveChallenges(
  db: Db,
  user: SessionUser,
  now: Date,
): Promise<string> {
  const active = await db
    .select()
    .from(weeklyChallenges)
    .where(
      and(
        eq(weeklyChallenges.active, true),
        lte(weeklyChallenges.startsAt, now),
        gte(weeklyChallenges.endsAt, now),
      ),
    )
    .orderBy(weeklyChallenges.endsAt);
  let body: string;
  if (active.length === 0) {
    body = `<p class="muted">No hay desafíos activos ahora. Volvé pronto para sumar puntos.</p>`;
  } else {
    const ids = active.map((ch) => ch.id);
    const parts = await db
      .select()
      .from(challengeParticipations)
      .where(
        and(
          eq(challengeParticipations.userId, user.id),
          inArray(challengeParticipations.challengeId, ids),
        ),
      );
    const byChallenge = new Map(parts.map((p) => [p.challengeId, p]));
    const cards: string[] = [];
    for (const challenge of active) {
      const participation = byChallenge.get(challenge.id);
      const progress = participation
        ? participation.progress
        : await computeProgress(db, user, challenge);
      cards.push(challengeCard(challenge, progress, participation));
    }
    body = cards.join('');
  }
  return `<div class="card">
    <h2>Desafíos activos</h2>
    <p class="muted">Sumá puntos completando los desafíos de la semana.</p>
    ${body}
  </div>`;
}

async function loadAnnouncementsHtml(
  db: Db,
  user: SessionUser,
  now: Date,
): Promise<string> {
  const audience = audienceCondition(user.role);
  const rows = await db
    .select()
    .from(announcements)
    .where(
      and(
        isNotNull(announcements.publishedAt),
        lte(announcements.publishedAt, now),
        ...(audience ? [audience] : []),
      ),
    )
    .orderBy(desc(announcements.publishedAt), desc(announcements.id))
    .limit(5);
  if (rows.length === 0) return '';
  const items = rows
    .map(
      (a) => `<div style="border-top:1px solid #e7e5e4;padding:0.6rem 0">
        <strong>${escapeHtml(a.title)}</strong>
        ${a.body ? `<p class="muted" style="margin:0.25rem 0 0">${escapeHtml(a.body)}</p>` : ''}
        <p class="muted" style="margin:0.25rem 0 0">${a.publishedAt ? DATE_FMT.format(a.publishedAt) : ''} · ${escapeHtml(a.audience === 'all' ? 'Todos' : a.audience === 'customers' ? 'Clientes' : 'Gestores')}</p>
      </div>`,
    )
    .join('');
  return `<div class="card">
    <h2>Novedades</h2>
    ${items}
  </div>`;
}

async function loadPointLogsHtml(db: Db, userId: number): Promise<string> {
  const logs = await db
    .select()
    .from(pointLogs)
    .where(eq(pointLogs.userId, userId))
    .orderBy(desc(pointLogs.createdAt), desc(pointLogs.id))
    .limit(10);
  if (logs.length === 0) {
    return `<div class="card">
      <h2>Últimos movimientos</h2>
      <p class="muted">Todavía no hay movimientos de puntos.</p>
    </div>`;
  }
  const items = logs
    .map((log) => {
      const label = POINT_REASON_LABELS[log.reason] ?? log.reason;
      const sign = log.points > 0 ? '+' : '';
      return `<li style="margin-bottom:0.35rem">${DATE_FMT.format(log.createdAt)} — ${escapeHtml(label)} — <strong>${sign}${log.points} pts</strong></li>`;
    })
    .join('');
  return `<div class="card">
    <h2>Últimos movimientos</h2>
    <ul style="margin:0;padding-left:1.2rem">${items}</ul>
  </div>`;
}

appRoutes.get('/app', requireAuth(), async (c) => {
  const sessionUser = c.get('user')!;
  const db = dbOf(c);
  const now = new Date();

  if (sessionUser.role !== 'admin') {
    await refreshUserChallenges(db, {
      id: sessionUser.id,
      role: sessionUser.role,
    });
  }

  const rows = await db
    .select()
    .from(users)
    .where(eq(users.id, sessionUser.id))
    .limit(1);
  const profile = rows[0];
  const user: SessionUser = {
    ...sessionUser,
    name: profile?.name ?? sessionUser.name,
    points: profile?.points ?? sessionUser.points,
  };
  c.set('user', user);

  const error = c.req.query('error');
  const notice = c.req.query('aviso');

  const greeting = `<div class="card">
    <h1>Hola, ${escapeHtml(user.name)}</h1>
    ${user.role !== 'admin' ? '<p>Sumá puntos iniciando sesión todos los días y completando desafíos semanales.</p>' : ''}
    <p class="muted">
      Tenés <strong>${user.points} puntos</strong>
      · Racha actual: <strong>${profile?.loginStreak ?? 0} día(s)</strong>
      · Último inicio de sesión: <strong>${profile?.lastLoginDay ?? '—'}</strong>
    </p>
    <a class="btn secondary" href="/perfil">Tu perfil</a>
  </div>`;

  return c.html(
    page(
      { title: 'Inicio', brand: brandOf(c), user, error, notice },
      `${user.role === 'customer' ? PANEL_NAV : ''}${greeting}
      ${await loadAnnouncementsHtml(db, user, now)}
      ${user.role !== 'admin' ? await loadActiveChallenges(db, user, now) : ''}
      ${await loadPointLogsHtml(db, user.id)}`,
    ),
  );
});

appRoutes.post('/desafios/:id/unirse', requireAuth(), async (c) => {
  const user = c.get('user')!;
  if (user.role === 'admin') {
    return c.redirect(`/app?error=${encodeURIComponent('Los administradores no participan en desafíos.')}`);
  }
  const id = Number(c.req.param('id'));
  const db = dbOf(c);
  const redirectWithError = (message: string) =>
    c.redirect(`/app?error=${encodeURIComponent(message)}`);
  if (!Number.isInteger(id) || id <= 0) {
    return redirectWithError('No encontramos ese desafío.');
  }
  const result = await joinChallenge(db, user.id, id, user.role);
  switch (result) {
    case 'joined':
      return c.redirect(
        `/app?aviso=${encodeURIComponent('¡Listo! Ya estás participando del desafío. ¡Sumá puntos!')}`,
      );
    case 'already':
      return redirectWithError('Ya estás participando de este desafío.');
    case 'inactive':
      return redirectWithError('Este desafío no está activo.');
    default:
      return redirectWithError('No encontramos ese desafío.');
  }
});

function perfilForm(
  profile: { name: string; email: string },
): string {
  return `<div class="card">
    <h1>Tu perfil</h1>
    <form method="post" action="/perfil">
      <label for="nombre">Nombre</label>
      <input id="nombre" name="nombre" type="text" required minlength="2" value="${escapeHtml(profile.name)}" autocomplete="name">
      <label for="email">Email</label>
      <input id="email" type="email" value="${escapeHtml(profile.email)}" disabled>
      <h2 style="margin-top:1.25rem">Cambiar contraseña</h2>
      <label for="password_actual">Contraseña actual</label>
      <input id="password_actual" name="password_actual" type="password" autocomplete="current-password">
      <label for="password_nueva">Contraseña nueva (mínimo 8 caracteres)</label>
      <input id="password_nueva" name="password_nueva" type="password" minlength="8" autocomplete="new-password">
      <label for="password_nueva2">Repetí la contraseña nueva</label>
      <input id="password_nueva2" name="password_nueva2" type="password" minlength="8" autocomplete="new-password">
      <button type="submit">Guardar cambios</button>
    </form>
    <p class="muted">Dejá los campos de contraseña vacíos si solo querés cambiar el nombre.</p>
  </div>`;
}

appRoutes.get('/perfil', requireAuth(), async (c) => {
  const user = c.get('user')!;
  const db = dbOf(c);
  const rows = await db
    .select({ name: users.name, email: users.email })
    .from(users)
    .where(eq(users.id, user.id))
    .limit(1);
  const profile = rows[0] ?? { name: user.name, email: user.email };
  const notice =
    c.req.query('aviso') === 'ok' ? 'Actualizamos tu perfil.' : undefined;
  return c.html(
    page(
      { title: 'Tu perfil', brand: brandOf(c), user, notice },
      perfilForm(profile),
    ),
  );
});

appRoutes.post('/perfil', requireAuth(), async (c) => {
  const sessionUser = c.get('user')!;
  const db = dbOf(c);
  const body = await c.req.parseBody();
  const nombre = str(body.nombre);
  const passwordActual = str(body.password_actual);
  const passwordNueva = str(body.password_nueva);
  const passwordNueva2 = str(body.password_nueva2);

  const rows = await db
    .select()
    .from(users)
    .where(eq(users.id, sessionUser.id))
    .limit(1);
  const profile = rows[0]!;

  const fail = (error: string) =>
    c.html(
      page(
        {
          title: 'Tu perfil',
          brand: brandOf(c),
          user: sessionUser,
          error,
        },
        perfilForm(profile),
      ),
      422,
    );

  if (nombre.length < 2) {
    return fail('Ingresá tu nombre (mínimo 2 letras).');
  }

  const wantsPassword = passwordNueva !== '' || passwordNueva2 !== '';
  let passwordHash: string | null = null;
  if (passwordActual !== '' || wantsPassword) {
    const valid = await verifyPassword(passwordActual, profile.passwordHash);
    if (!valid) return fail('La contraseña actual no es correcta.');
    if (wantsPassword) {
      if (passwordNueva.length < 8) {
        return fail('La contraseña nueva debe tener al menos 8 caracteres.');
      }
      if (passwordNueva !== passwordNueva2) {
        return fail('Las contraseñas nuevas no coinciden.');
      }
      passwordHash = await hashPassword(
        passwordNueva,
        iterationsFromEnv(c.env),
      );
    }
  }

  await db
    .update(users)
    .set({
      name: nombre,
      ...(passwordHash ? { passwordHash } : {}),
      updatedAt: new Date(),
    })
    .where(eq(users.id, sessionUser.id));
  return c.redirect('/perfil?aviso=ok');
});
