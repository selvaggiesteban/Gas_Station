import { Hono } from 'hono';
import type { Context } from 'hono';
import { and, count, desc, eq, like, or, sql } from 'drizzle-orm';
import type { AppEnv } from '../env';
import { dbOf, brandOf, requireRole } from '../middleware/auth';
import { page, escapeHtml } from '../lib/html';
import {
  GOAL_TYPE_VALUES,
  ROLE_VALUES,
  orders,
  sessions,
  storeUser,
  stores,
  users,
  weeklyChallenges,
} from '../db/schema';

type Role = (typeof ROLE_VALUES)[number];
type GoalType = (typeof GOAL_TYPE_VALUES)[number];

const ROLE_LABELS: Record<Role, string> = {
  admin: 'Admin',
  content_manager: 'Contenido',
  store_manager: 'Gestor de tienda',
  customer: 'Cliente',
};

const GOAL_LABELS: Record<GoalType, string> = {
  points: 'Puntos',
  battles_won: 'Batallas ganadas',
  login_days: 'Días de login',
  sales_amount: 'Monto de ventas',
};

const TH =
  'text-align:left;padding:0.45rem 0.6rem;border-bottom:1px solid #e7e5e4;font-size:0.85rem';
const TD =
  'padding:0.45rem 0.6rem;border-bottom:1px solid #f5f5f4;font-size:0.9rem;vertical-align:top';
const SMALL_BTN = 'margin-top:0;padding:0.35rem 0.7rem';

function str(v: unknown): string {
  return typeof v === 'string' ? v.trim() : '';
}

function isRole(v: string): v is Role {
  return (ROLE_VALUES as readonly string[]).includes(v);
}

function isGoalType(v: string): v is GoalType {
  return (GOAL_TYPE_VALUES as readonly string[]).includes(v);
}

function parseIdParam(raw: string): number | null {
  const id = Number(raw);
  return Number.isInteger(id) && id > 0 ? id : null;
}

function parseActivo(v: unknown): boolean | null {
  const s = str(v);
  if (s === '1') return true;
  if (s === '0') return false;
  return null;
}

function toLocalInput(d: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function fmtFecha(d: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function render(
  c: Context<AppEnv>,
  title: string,
  body: string,
  opts: { error?: string; status?: 200 | 404 | 422 } = {},
): Response {
  return c.html(
    page(
      {
        title,
        brand: brandOf(c),
        user: c.get('user'),
        error: opts.error,
      },
      body,
    ),
    opts.status ?? (opts.error ? 422 : 200),
  );
}

function avisado(path: string): string {
  return path + (path.includes('?') ? '&' : '?') + 'aviso=ok';
}

function avisoDe(c: Context<AppEnv>): string | undefined {
  return c.req.query('aviso') ? 'Cambios guardados.' : undefined;
}

function notFoundBody(volver: string): string {
  return `<div class="card"><h1>No encontrado</h1>
    <p class="muted">El recurso pedido no existe.</p>
    <a class="btn secondary" href="${volver}">Volver</a></div>`;
}

function adminNav(
  section: 'panel' | 'usuarios' | 'tiendas' | 'desafios',
): string {
  const items: Array<{ key: typeof section; label: string; href: string }> = [
    { key: 'panel', label: 'Panel', href: '/admin' },
    { key: 'usuarios', label: 'Usuarios', href: '/admin/usuarios' },
    { key: 'tiendas', label: 'Tiendas', href: '/admin/tiendas' },
    { key: 'desafios', label: 'Desafíos', href: '/admin/desafios' },
  ];
  return `<p class="row" style="margin-top:0">${items
    .map(
      (it) =>
        `<a class="btn${it.key === section ? '' : ' secondary'}" href="${it.href}" style="${SMALL_BTN}">${it.label}</a>`,
    )
    .join('')}</p>`;
}

export const adminRoutes = new Hono<AppEnv>();

adminRoutes.use('/admin', requireRole('admin'));
adminRoutes.use('/admin/*', requireRole('admin'));

// ---------------------------------------------------------------- dashboard

adminRoutes.get('/admin', async (c) => {
  const db = dbOf(c);
  const [usersTotal, usersActive, storesActive, ordersPending, challengesActive] =
    await Promise.all([
      db.select({ n: count() }).from(users),
      db.select({ n: count() }).from(users).where(eq(users.active, true)),
      db.select({ n: count() }).from(stores).where(eq(stores.active, true)),
      db
        .select({ n: count() })
        .from(orders)
        .where(eq(orders.status, 'pending')),
      db
        .select({ n: count() })
        .from(weeklyChallenges)
        .where(eq(weeklyChallenges.active, true)),
    ]);
  return c.html(
    page(
      {
        title: 'Administración',
        brand: brandOf(c),
        user: c.get('user'),
        notice: avisoDe(c),
      },
      `<div class="card">
        <h1>Panel de administración</h1>
        ${adminNav('panel')}
        <div class="row">
          <span class="pill">${usersTotal[0]?.n ?? 0} usuarios (${usersActive[0]?.n ?? 0} activos)</span>
          <span class="pill">${storesActive[0]?.n ?? 0} tiendas activas</span>
          <span class="pill">${ordersPending[0]?.n ?? 0} órdenes pendientes</span>
          <span class="pill">${challengesActive[0]?.n ?? 0} desafíos activos</span>
        </div>
      </div>
      <div class="card">
        <h2>Secciones</h2>
        <ul>
          <li><a href="/admin/usuarios">Usuarios y roles</a></li>
          <li><a href="/admin/tiendas">Tiendas y gestores</a></li>
          <li><a href="/admin/desafios">Desafíos semanales</a></li>
        </ul>
      </div>`,
    ),
  );
});

// ---------------------------------------------------------------- usuarios

adminRoutes.get('/admin/usuarios', async (c) => {
  const db = dbOf(c);
  const q = str(c.req.query('q'));
  const cond = q
    ? or(like(users.name, `%${q}%`), like(users.email, `%${q}%`))
    : undefined;
  const rows = await db
    .select({
      id: users.id,
      name: users.name,
      email: users.email,
      role: users.role,
      active: users.active,
      points: users.points,
    })
    .from(users)
    .where(cond)
    .orderBy(users.name)
    .limit(50);

  const roleOptions = (selected: Role): string =>
    ROLE_VALUES.map(
      (r) =>
        `<option value="${r}"${r === selected ? ' selected' : ''}>${ROLE_LABELS[r]}</option>`,
    ).join('');

  const bodyRows = rows
    .map(
      (u) => `<tr>
      <td style="${TD}">${escapeHtml(u.name)}</td>
      <td style="${TD}">${escapeHtml(u.email)}</td>
      <td style="${TD}">
        <form method="post" action="/admin/usuarios/${u.id}/rol" style="display:flex;gap:0.4rem;align-items:center">
          <select name="role" style="width:auto">${roleOptions(u.role)}</select>
          <button type="submit" style="${SMALL_BTN}">Guardá</button>
        </form>
      </td>
      <td style="${TD}">${u.active ? 'Sí' : 'No'}</td>
      <td style="${TD}">${u.points}</td>
      <td style="${TD}">
        <form method="post" action="/admin/usuarios/${u.id}/activo" style="display:inline">
          <input type="hidden" name="active" value="${u.active ? '0' : '1'}">
          <button type="submit" class="secondary" style="${SMALL_BTN}">${u.active ? 'Desactivá' : 'Activá'}</button>
        </form>
      </td>
    </tr>`,
    )
    .join('');

  return c.html(
    page(
      {
        title: 'Usuarios',
        brand: brandOf(c),
        user: c.get('user'),
        notice: avisoDe(c),
      },
      `<div class="card">
        <h1>Usuarios</h1>
        ${adminNav('usuarios')}
        <form method="get" action="/admin/usuarios" class="row">
          <input type="search" name="q" value="${escapeHtml(q)}" placeholder="Buscá por nombre o email" style="flex:1;min-width:12rem;margin-top:0">
          <button type="submit" style="${SMALL_BTN}">Buscá</button>
        </form>
        <p class="muted">${rows.length} resultado${rows.length === 1 ? '' : 's'}${rows.length === 50 ? ' (máx. 50)' : ''}</p>
        <table style="width:100%;border-collapse:collapse;margin-top:0.5rem">
          <thead><tr>
            <th style="${TH}">Nombre</th>
            <th style="${TH}">Email</th>
            <th style="${TH}">Rol</th>
            <th style="${TH}">Activo</th>
            <th style="${TH}">Puntos</th>
            <th style="${TH}">Acciones</th>
          </tr></thead>
          <tbody>${bodyRows || `<tr><td colspan="6" style="${TD}" class="muted">No hay usuarios para mostrar.</td></tr>`}</tbody>
        </table>
      </div>`,
    ),
  );
});

adminRoutes.post('/admin/usuarios/:id/rol', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      status: 404,
    });
  }
  const body = await c.req.parseBody();
  const role = str(body.role);
  if (!isRole(role)) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      error: 'Elegí un rol válido.',
    });
  }
  const me = c.get('user')!;
  if (me.id === id) {
    return render(
      c,
      'Usuarios',
      `<div class="card"><h1>No cambiaste el rol</h1>
       <p class="muted">No podés cambiarte el rol a vos mismo.</p>
       <a class="btn secondary" href="/admin/usuarios">Volver a usuarios</a></div>`,
      { error: 'No podés cambiarte el rol a vos mismo.' },
    );
  }
  const db = dbOf(c);
  const updated = await db
    .update(users)
    .set({ role, updatedAt: new Date() })
    .where(eq(users.id, id))
    .returning({ id: users.id });
  if (!updated[0]) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      status: 404,
    });
  }
  return c.redirect(avisado('/admin/usuarios'));
});

adminRoutes.post('/admin/usuarios/:id/activo', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      status: 404,
    });
  }
  const body = await c.req.parseBody();
  const active = parseActivo(body.active);
  if (active === null) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      error: "Enviá active = '1' para activar o '0' para desactivar.",
    });
  }
  const me = c.get('user')!;
  if (me.id === id && !active) {
    return render(
      c,
      'Usuarios',
      `<div class="card"><h1>No se desactivó la cuenta</h1>
       <p class="muted">No podés desactivarte a vos mismo.</p>
       <a class="btn secondary" href="/admin/usuarios">Volver a usuarios</a></div>`,
      { error: 'No podés desactivarte a vos mismo.' },
    );
  }
  const db = dbOf(c);
  const updated = await db
    .update(users)
    .set({ active, updatedAt: new Date() })
    .where(eq(users.id, id))
    .returning({ id: users.id });
  if (!updated[0]) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      status: 404,
    });
  }
  if (!active) {
    await db.delete(sessions).where(eq(sessions.userId, id));
  }
  return c.redirect(avisado('/admin/usuarios'));
});

// ----------------------------------------------------------------- tiendas

adminRoutes.get('/admin/tiendas', async (c) => {
  const db = dbOf(c);
  const rows = await db
    .select({
      id: stores.id,
      name: stores.name,
      zone: stores.zone,
      active: stores.active,
      managers: sql<number>`count(${storeUser.userId})`,
    })
    .from(stores)
    .leftJoin(storeUser, eq(storeUser.storeId, stores.id))
    .groupBy(stores.id)
    .orderBy(stores.name);

  const bodyRows = rows
    .map(
      (s) => `<tr>
      <td style="${TD}">${escapeHtml(s.name)}</td>
      <td style="${TD}">${s.zone ? escapeHtml(s.zone) : '<span class="muted">—</span>'}</td>
      <td style="${TD}">${s.active ? 'Sí' : 'No'}</td>
      <td style="${TD}">${s.managers}</td>
      <td style="${TD}">
        <a class="btn secondary" href="/admin/tiendas/${s.id}" style="${SMALL_BTN}">Detalle</a>
        <form method="post" action="/admin/tiendas/${s.id}/activo" style="display:inline">
          <input type="hidden" name="active" value="${s.active ? '0' : '1'}">
          <button type="submit" class="secondary" style="${SMALL_BTN}">${s.active ? 'Desactivá' : 'Activá'}</button>
        </form>
        <details style="display:inline;margin-left:0.5rem">
          <summary style="display:inline;cursor:pointer;font-size:0.85rem">Editá</summary>
          <form method="post" action="/admin/tiendas/${s.id}/editar" style="margin-top:0.5rem;min-width:14rem">
            <label for="name-${s.id}">Nombre</label>
            <input id="name-${s.id}" name="name" type="text" required value="${escapeHtml(s.name)}">
            <label for="zone-${s.id}">Zona (opcional)</label>
            <input id="zone-${s.id}" name="zone" type="text" value="${escapeHtml(s.zone ?? '')}">
            <button type="submit" style="${SMALL_BTN}">Guardá</button>
          </form>
        </details>
      </td>
    </tr>`,
    )
    .join('');

  return c.html(
    page(
      {
        title: 'Tiendas',
        brand: brandOf(c),
        user: c.get('user'),
        notice: avisoDe(c),
      },
      `<div class="card">
        <h1>Tiendas</h1>
        ${adminNav('tiendas')}
        <table style="width:100%;border-collapse:collapse">
          <thead><tr>
            <th style="${TH}">Nombre</th>
            <th style="${TH}">Zona</th>
            <th style="${TH}">Activa</th>
            <th style="${TH}">Gestores</th>
            <th style="${TH}">Acciones</th>
          </tr></thead>
          <tbody>${bodyRows || `<tr><td colspan="5" style="${TD}" class="muted">Todavía no hay tiendas. Creá la primera acá abajo.</td></tr>`}</tbody>
        </table>
      </div>
      <div class="card">
        <h2>Creá una tienda</h2>
        <form method="post" action="/admin/tiendas">
          <label for="new-name">Nombre (único)</label>
          <input id="new-name" name="name" type="text" required>
          <label for="new-zone">Zona (opcional)</label>
          <input id="new-zone" name="zone" type="text">
          <button type="submit">Creá tienda</button>
        </form>
      </div>`,
    ),
  );
});

adminRoutes.post('/admin/tiendas', async (c) => {
  const body = await c.req.parseBody();
  const name = str(body.name);
  const zone = str(body.zone) || null;
  if (!name) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      error: 'Ingresá el nombre de la tienda.',
    });
  }
  const db = dbOf(c);
  const existing = await db
    .select({ id: stores.id })
    .from(stores)
    .where(eq(stores.name, name))
    .limit(1);
  if (existing[0]) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      error: 'Ya existe una tienda con ese nombre.',
    });
  }
  await db.insert(stores).values({ name, zone, active: true });
  return c.redirect(avisado('/admin/tiendas'));
});

adminRoutes.post('/admin/tiendas/:id/editar', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      status: 404,
    });
  }
  const body = await c.req.parseBody();
  const name = str(body.name);
  const zone = str(body.zone) || null;
  if (!name) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      error: 'Ingresá el nombre de la tienda.',
    });
  }
  const db = dbOf(c);
  const existing = await db
    .select({ id: stores.id })
    .from(stores)
    .where(eq(stores.name, name))
    .limit(1);
  if (existing[0] && existing[0].id !== id) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      error: 'Ya existe una tienda con ese nombre.',
    });
  }
  const updated = await db
    .update(stores)
    .set({ name, zone, updatedAt: new Date() })
    .where(eq(stores.id, id))
    .returning({ id: stores.id });
  if (!updated[0]) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      status: 404,
    });
  }
  return c.redirect(avisado('/admin/tiendas'));
});

adminRoutes.post('/admin/tiendas/:id/activo', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      status: 404,
    });
  }
  const body = await c.req.parseBody();
  const active = parseActivo(body.active);
  if (active === null) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      error: "Enviá active = '1' para activar o '0' para desactivar.",
    });
  }
  const db = dbOf(c);
  const updated = await db
    .update(stores)
    .set({ active, updatedAt: new Date() })
    .where(eq(stores.id, id))
    .returning({ id: stores.id });
  if (!updated[0]) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      status: 404,
    });
  }
  return c.redirect(avisado('/admin/tiendas'));
});

adminRoutes.get('/admin/tiendas/:id', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      status: 404,
    });
  }
  const db = dbOf(c);
  const storeRows = await db
    .select()
    .from(stores)
    .where(eq(stores.id, id))
    .limit(1);
  const store = storeRows[0];
  if (!store) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      status: 404,
    });
  }

  const gestores = await db
    .select({
      userId: users.id,
      name: users.name,
      email: users.email,
    })
    .from(storeUser)
    .innerJoin(users, eq(storeUser.userId, users.id))
    .where(eq(storeUser.storeId, id))
    .orderBy(users.name);

  const asignados = gestores.map((g) => g.userId);
  const candidatos = await db
    .select({ id: users.id, name: users.name, email: users.email })
    .from(users)
    .where(and(eq(users.role, 'store_manager'), eq(users.active, true)))
    .orderBy(users.name);
  const disponibles = candidatos.filter((u) => !asignados.includes(u.id));

  const opcionesGestor = disponibles
    .map(
      (u) =>
        `<option value="${u.id}">${escapeHtml(u.name)} · ${escapeHtml(u.email)}</option>`,
    )
    .join('');

  const filasGestores = gestores
    .map(
      (g) => `<tr>
      <td style="${TD}">${escapeHtml(g.name)}</td>
      <td style="${TD}">${escapeHtml(g.email)}</td>
      <td style="${TD}">
        <form method="post" action="/admin/tiendas/${id}/gestores/quitar" style="display:inline">
          <input type="hidden" name="userId" value="${g.userId}">
          <button type="submit" class="secondary" style="${SMALL_BTN}">Quitá gestor</button>
        </form>
      </td>
    </tr>`,
    )
    .join('');

  return c.html(
    page(
      {
        title: `Tienda: ${store.name}`,
        brand: brandOf(c),
        user: c.get('user'),
        notice: avisoDe(c),
      },
      `<div class="card">
        <h1>${escapeHtml(store.name)}</h1>
        ${adminNav('tiendas')}
        <p class="muted">Zona: ${store.zone ? escapeHtml(store.zone) : 'sin zona'} · Activa: ${store.active ? 'sí' : 'no'}</p>
        <a class="btn secondary" href="/admin/tiendas">Volver a tiendas</a>
      </div>
      <div class="card">
        <h2>Gestores asignados</h2>
        <table style="width:100%;border-collapse:collapse">
          <thead><tr>
            <th style="${TH}">Nombre</th>
            <th style="${TH}">Email</th>
            <th style="${TH}">Acciones</th>
          </tr></thead>
          <tbody>${filasGestores || `<tr><td colspan="3" style="${TD}" class="muted">Esta tienda todavía no tiene gestores.</td></tr>`}</tbody>
        </table>
        <h2 style="margin-top:1rem">Asigná gestor</h2>
        ${
          disponibles.length
            ? `<form method="post" action="/admin/tiendas/${id}/gestores">
          <label for="userId">Usuario con rol gestor de tienda</label>
          <select id="userId" name="userId" required>${opcionesGestor}</select>
          <button type="submit">Asigná gestor</button>
        </form>`
            : `<p class="muted">No hay usuarios con rol gestor de tienda disponibles. Creá o reasigná usuarios desde <a href="/admin/usuarios">Usuarios</a>.</p>`
        }
      </div>`,
    ),
  );
});

adminRoutes.post('/admin/tiendas/:id/gestores', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      status: 404,
    });
  }
  const volver = `/admin/tiendas/${id}`;
  const body = await c.req.parseBody();
  const userId = Number(str(body.userId));
  if (!Number.isInteger(userId) || userId <= 0) {
    return render(c, 'Tiendas', notFoundBody(volver), {
      error: 'Seleccioná un gestor válido.',
    });
  }
  const db = dbOf(c);
  const storeRows = await db
    .select({ id: stores.id })
    .from(stores)
    .where(eq(stores.id, id))
    .limit(1);
  if (!storeRows[0]) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      status: 404,
    });
  }
  const userRows = await db
    .select({ id: users.id, role: users.role })
    .from(users)
    .where(eq(users.id, userId))
    .limit(1);
  if (!userRows[0]) {
    return render(c, 'Tiendas', notFoundBody(volver), {
      error: 'No existe ese usuario.',
    });
  }
  if (userRows[0].role !== 'store_manager') {
    return render(c, 'Tiendas', notFoundBody(volver), {
      error: 'Ese usuario no tiene el rol de gestor de tienda.',
    });
  }
  const yaAsignado = await db
    .select({ userId: storeUser.userId })
    .from(storeUser)
    .where(and(eq(storeUser.storeId, id), eq(storeUser.userId, userId)))
    .limit(1);
  if (yaAsignado[0]) {
    return render(c, 'Tiendas', notFoundBody(volver), {
      error: 'Ese gestor ya está asignado a esta tienda.',
    });
  }
  await db.insert(storeUser).values({ storeId: id, userId });
  return c.redirect(avisado(volver));
});

adminRoutes.post('/admin/tiendas/:id/gestores/quitar', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Tiendas', notFoundBody('/admin/tiendas'), {
      status: 404,
    });
  }
  const body = await c.req.parseBody();
  const userId = Number(str(body.userId));
  if (!Number.isInteger(userId) || userId <= 0) {
    return render(c, 'Tiendas', notFoundBody(`/admin/tiendas/${id}`), {
      error: 'Seleccioná un gestor válido.',
    });
  }
  const db = dbOf(c);
  await db
    .delete(storeUser)
    .where(and(eq(storeUser.storeId, id), eq(storeUser.userId, userId)));
  return c.redirect(avisado(`/admin/tiendas/${id}`));
});

// ---------------------------------------------------------------- desafíos

function desafioFormFields(
  prefix: string,
  ch?: {
    title: string;
    description: string;
    goalType: GoalType;
    goalValue: number;
    startsAt: Date;
    endsAt: Date;
    rewardPoints: number;
  },
): string {
  const now = new Date();
  const inWeek = new Date(now.getTime() + 7 * 24 * 3600 * 1000);
  const goalTypeOptions = GOAL_TYPE_VALUES.map(
    (g) =>
      `<option value="${g}"${ch && ch.goalType === g ? ' selected' : ''}>${GOAL_LABELS[g]}</option>`,
  ).join('');
  return `
    <label for="${prefix}-title">Título</label>
    <input id="${prefix}-title" name="title" type="text" required value="${escapeHtml(ch?.title ?? '')}">
    <label for="${prefix}-desc">Descripción</label>
    <textarea id="${prefix}-desc" name="description" rows="2">${escapeHtml(ch?.description ?? '')}</textarea>
    <label for="${prefix}-goal">Tipo de meta</label>
    <select id="${prefix}-goal" name="goalType">${goalTypeOptions}</select>
    <label for="${prefix}-goalValue">Valor de la meta</label>
    <input id="${prefix}-goalValue" name="goalValue" type="number" min="1" step="1" required value="${ch?.goalValue ?? ''}">
    <label for="${prefix}-starts">Inicio</label>
    <input id="${prefix}-starts" name="startsAt" type="datetime-local" required value="${toLocalInput(ch?.startsAt ?? now)}">
    <label for="${prefix}-ends">Fin</label>
    <input id="${prefix}-ends" name="endsAt" type="datetime-local" required value="${toLocalInput(ch?.endsAt ?? inWeek)}">
    <label for="${prefix}-reward">Puntos de recompensa</label>
    <input id="${prefix}-reward" name="rewardPoints" type="number" min="0" step="1" value="${ch?.rewardPoints ?? 0}">
  `;
}

adminRoutes.get('/admin/desafios', async (c) => {
  const db = dbOf(c);
  const rows = await db
    .select()
    .from(weeklyChallenges)
    .orderBy(desc(weeklyChallenges.startsAt));

  const tarjetas = rows
    .map(
      (ch) => `<div class="card">
      <h2>${escapeHtml(ch.title)} ${ch.active ? '<span class="pill">Activo</span>' : '<span class="pill" style="background:#57534e">Inactivo</span>'}</h2>
      <p class="muted">${escapeHtml(ch.description || 'sin descripción')}</p>
      <p class="muted">Meta: ${GOAL_LABELS[ch.goalType]} ≥ ${ch.goalValue} · Ventana: ${fmtFecha(ch.startsAt)} → ${fmtFecha(ch.endsAt)} · Recompensa: ${ch.rewardPoints} pts</p>
      <form method="post" action="/admin/desafios/${ch.id}/activo" style="display:inline">
        <input type="hidden" name="active" value="${ch.active ? '0' : '1'}">
        <button type="submit" class="secondary" style="${SMALL_BTN}">${ch.active ? 'Desactivá' : 'Activá'}</button>
      </form>
      <details style="display:inline;margin-left:0.5rem">
        <summary style="cursor:pointer;font-size:0.85rem">Editá</summary>
        <form method="post" action="/admin/desafios/${ch.id}/editar">
          ${desafioFormFields(`ch-${ch.id}`, ch)}
          <button type="submit">Guardá cambios</button>
        </form>
      </details>
    </div>`,
    )
    .join('');

  return c.html(
    page(
      {
        title: 'Desafíos',
        brand: brandOf(c),
        user: c.get('user'),
        notice: avisoDe(c),
      },
      `<div class="card">
        <h1>Desafíos semanales</h1>
        ${adminNav('desafios')}
        ${tarjetas || '<p class="muted">Todavía no hay desafíos. Creá el primero acá abajo.</p>'}
      </div>
      <div class="card">
        <h2>Creá desafío</h2>
        <form method="post" action="/admin/desafios">
          ${desafioFormFields('nuevo')}
          <label style="display:flex;gap:0.5rem;align-items:center;font-weight:400">
            <input type="checkbox" name="active" value="on" checked style="width:auto"> Activo
          </label>
          <button type="submit">Creá desafío</button>
        </form>
      </div>`,
    ),
  );
});

type DesafioInput = {
  title: string;
  description: string;
  goalType: GoalType;
  goalValue: number;
  startsAt: Date;
  endsAt: Date;
  rewardPoints: number;
  active: boolean;
};

function parseDesafioBody(body: Record<string, unknown>): DesafioInput | string {
  const title = str(body.title);
  if (!title) return 'Ingresá un título para el desafío.';
  const description = str(body.description);
  const goalType = str(body.goalType);
  if (!isGoalType(goalType)) return 'Elegí un tipo de meta válido.';
  const goalValue = Number(str(body.goalValue));
  if (!Number.isFinite(goalValue) || goalValue <= 0) {
    return 'El valor de la meta debe ser mayor a 0.';
  }
  const startsAt = new Date(str(body.startsAt));
  const endsAt = new Date(str(body.endsAt));
  if (Number.isNaN(startsAt.getTime()) || Number.isNaN(endsAt.getTime())) {
    return 'Ingresá fechas de inicio y fin válidas.';
  }
  if (endsAt.getTime() <= startsAt.getTime()) {
    return 'La fecha de fin debe ser posterior a la de inicio.';
  }
  const rewardRaw = str(body.rewardPoints);
  const rewardPoints = rewardRaw === '' ? 0 : Number(rewardRaw);
  if (!Number.isFinite(rewardPoints) || rewardPoints < 0) {
    return 'Los puntos de recompensa no pueden ser negativos.';
  }
  const activeRaw = body.active;
  const active =
    activeRaw !== undefined &&
    activeRaw !== null &&
    (typeof activeRaw !== 'string' || activeRaw !== '');
  return {
    title,
    description,
    goalType,
    goalValue: Math.round(goalValue),
    startsAt,
    endsAt,
    rewardPoints: Math.round(rewardPoints),
    active,
  };
}

adminRoutes.post('/admin/desafios', async (c) => {
  const body = await c.req.parseBody();
  const parsed = parseDesafioBody(body);
  if (typeof parsed === 'string') {
    return render(c, 'Desafíos', notFoundBody('/admin/desafios'), {
      error: parsed,
    });
  }
  const db = dbOf(c);
  await db.insert(weeklyChallenges).values(parsed);
  return c.redirect(avisado('/admin/desafios'));
});

adminRoutes.post('/admin/desafios/:id/editar', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Desafíos', notFoundBody('/admin/desafios'), {
      status: 404,
    });
  }
  const body = await c.req.parseBody();
  const parsed = parseDesafioBody(body);
  if (typeof parsed === 'string') {
    return render(c, 'Desafíos', notFoundBody('/admin/desafios'), {
      error: parsed,
    });
  }
  const db = dbOf(c);
  const { active: _active, ...campos } = parsed;
  const updated = await db
    .update(weeklyChallenges)
    .set({ ...campos, updatedAt: new Date() })
    .where(eq(weeklyChallenges.id, id))
    .returning({ id: weeklyChallenges.id });
  if (!updated[0]) {
    return render(c, 'Desafíos', notFoundBody('/admin/desafios'), {
      status: 404,
    });
  }
  return c.redirect(avisado('/admin/desafios'));
});

adminRoutes.post('/admin/desafios/:id/activo', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Desafíos', notFoundBody('/admin/desafios'), {
      status: 404,
    });
  }
  const body = await c.req.parseBody();
  const active = parseActivo(body.active);
  if (active === null) {
    return render(c, 'Desafíos', notFoundBody('/admin/desafios'), {
      error: "Enviá active = '1' para activar o '0' para desactivar.",
    });
  }
  const db = dbOf(c);
  const updated = await db
    .update(weeklyChallenges)
    .set({ active, updatedAt: new Date() })
    .where(eq(weeklyChallenges.id, id))
    .returning({ id: weeklyChallenges.id });
  if (!updated[0]) {
    return render(c, 'Desafíos', notFoundBody('/admin/desafios'), {
      status: 404,
    });
  }
  return c.redirect(avisado('/admin/desafios'));
});
