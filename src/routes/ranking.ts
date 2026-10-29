import { Hono } from 'hono';
import { and, asc, count, desc, eq, inArray, sql } from 'drizzle-orm';
import type { AppEnv } from '../env';
import { dbOf, brandOf, requireAuth } from '../middleware/auth';
import { page, escapeHtml } from '../lib/html';
import { users } from '../db/schema';
import { refreshUserChallenges } from '../services/challenges';

export const rankingRoutes = new Hono<AppEnv>();

const TOP_LIMIT = 50;

type RolFilter = 'todos' | 'clientes' | 'gestores';

const ROL_LABELS: Record<RolFilter, string> = {
  todos: 'Todos',
  clientes: 'Clientes',
  gestores: 'Gestores',
};

function parseRol(v: string | undefined): RolFilter {
  return v === 'clientes' || v === 'gestores' ? v : 'todos';
}

function rolCondition(rol: RolFilter) {
  if (rol === 'clientes') return eq(users.role, 'customer');
  if (rol === 'gestores') return eq(users.role, 'store_manager');
  return inArray(users.role, ['customer', 'store_manager']);
}

function inSegment(rol: RolFilter, role: string): boolean {
  if (rol === 'todos') return role !== 'admin';
  if (rol === 'clientes') return role === 'customer';
  return role === 'store_manager';
}

function roleLabel(role: string): string {
  switch (role) {
    case 'admin':
      return 'Admin';
    case 'store_manager':
      return 'Gestor';
    case 'customer':
      return 'Cliente';
    default:
      return role;
  }
}

function filtersHtml(rol: RolFilter): string {
  const pills = (Object.keys(ROL_LABELS) as RolFilter[])
    .map((r) =>
      r === rol
        ? `<span class="pill">${ROL_LABELS[r]}</span>`
        : `<a href="/ranking?rol=${r}" style="color:#44403c">${ROL_LABELS[r]}</a>`,
    )
    .join(' ');
  return `<p class="row" style="margin:0 0 0.75rem">${pills}</p>`;
}

const PANEL_NAV = `<nav class="bottom" style="margin-bottom:1rem">
  <a href="/app">Inicio</a>
  <a href="/ranking">Ranking</a>
  <a href="/tienda">Tienda</a>
  <a href="/perfil">Tu perfil</a>
</nav>`;

function rankRow(opts: {
  position: number;
  name: string;
  role: string;
  points: number;
  isMe: boolean;
}): string {
  const highlight = opts.isMe ? ';background:#fff7ed' : '';
  const me = opts.isMe ? ' me' : '';
  return `<div class="rank-row${me}" data-pos="${opts.position}" style="display:flex;justify-content:space-between;gap:0.75rem;align-items:center;padding:0.5rem 0.6rem;border-top:1px solid #e7e5e4${highlight}">
    <span><strong>${opts.position}</strong> ${escapeHtml(opts.name)}${opts.isMe ? ' <span class="pill">Vos</span>' : ''} <span class="muted">${roleLabel(opts.role)}</span></span>
    <span><strong>${opts.points} pts</strong></span>
  </div>`;
}

rankingRoutes.get('/ranking', requireAuth(), async (c) => {
  const sessionUser = c.get('user')!;
  const db = dbOf(c);
  const rol = parseRol(c.req.query('rol'));
  const condition = rolCondition(rol);

  if (sessionUser.role !== 'admin') {
    await refreshUserChallenges(db, {
      id: sessionUser.id,
      role: sessionUser.role,
    });
  }

  const meRows = await db
    .select({
      id: users.id,
      name: users.name,
      points: users.points,
      role: users.role,
      createdAt: users.createdAt,
    })
    .from(users)
    .where(eq(users.id, sessionUser.id))
    .limit(1);
  const me = meRows[0] ?? {
    id: sessionUser.id,
    name: sessionUser.name,
    points: sessionUser.points,
    role: sessionUser.role,
    createdAt: new Date(0),
  };

  const top = await db
    .select({
      id: users.id,
      name: users.name,
      points: users.points,
      role: users.role,
    })
    .from(users)
    .where(and(eq(users.active, true), ...(condition ? [condition] : [])))
    .orderBy(desc(users.points), asc(users.createdAt), asc(users.id))
    .limit(TOP_LIMIT);

  const inTop = top.some((r) => r.id === me.id);
  let myRow = '';
  if (!inTop && inSegment(rol, me.role)) {
    const meMs = me.createdAt.getTime();
    const ahead = await db
      .select({ n: count() })
      .from(users)
      .where(
        and(
          eq(users.active, true),
          ...(condition ? [condition] : []),
          sql`(${users.points} > ${me.points}) or (${users.points} = ${me.points} and (${users.createdAt} < ${meMs} or (${users.createdAt} = ${meMs} and ${users.id} < ${me.id})))`,
        ),
      );
    const position = (ahead[0]?.n ?? 0) + 1;
    myRow = `<p class="muted" style="margin:0.75rem 0 0">Tu posición</p>
      ${rankRow({ position, name: me.name, role: me.role, points: me.points, isMe: true })}`;
  }

  const list =
    top.length === 0
      ? `<p class="muted">Todavía no hay usuarios en el ranking. ¡Sé el primero en sumar puntos!</p>`
      : top
          .map((r, i) =>
            rankRow({
              position: i + 1,
              name: r.name,
              role: r.role,
              points: r.points,
              isMe: r.id === me.id,
            }),
          )
          .join('');

  return c.html(
    page(
      { title: 'Ranking', brand: brandOf(c), user: me },
      `${PANEL_NAV}
      <div class="card">
        <h1>Ranking</h1>
        <p class="muted">El ranking se ordena por puntos acumulados. Sumá puntos con desafíos, sesiones y compras.</p>
        ${filtersHtml(rol)}
        ${list}
        ${myRow}
        <p style="margin-top:1rem"><a class="btn secondary" href="/app">Volver al inicio</a></p>
      </div>`,
    ),
  );
});
