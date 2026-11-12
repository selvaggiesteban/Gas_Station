import { Hono } from 'hono';
import type { Context } from 'hono';
import { and, asc, count, desc, eq, like, ne, or, sql } from 'drizzle-orm';
import type { SQL } from 'drizzle-orm';
import { alias } from 'drizzle-orm/sqlite-core';
import type { AppEnv } from '../env';
import type { Db } from '../db';
import { dbOf, brandOf, requireRole, requireMFA, auditLog } from '../middleware/auth';
import { adminRateLimit } from '../middleware/rate-limit';
import { page, escapeHtml } from '../lib/html';
import { encryptSecret, decryptSecret } from '../lib/crypto';
import {
  GOAL_TYPE_VALUES,
  ORDER_STATUS_VALUES,
  ROLE_VALUES,
  announcements,
  auditLogs,
  cashDays,
  mfaSecrets,
  notifications,
  orderItems,
  orders,
  passwordResetTokens,
  pointLogs,
  products,
  storeUser,
  stores,
  users,
  weeklyChallenges,
} from '../db/schema';
import {
  approveOrder,
  decrementStockGuard,
  findAutoGestor,
  insertOrderItems,
  insertOrderWithRetry,
  mimeFromKey,
  rejectOrder,
  ensureDefaultStore,
  restoreStock,
  sanitizeFilename,
} from '../services/orders';
import { generateSecret, verify, generateURI } from 'otplib';

type Role = (typeof ROLE_VALUES)[number];
type GoalType = (typeof GOAL_TYPE_VALUES)[number];
type OrderStatus = (typeof ORDER_STATUS_VALUES)[number];

const ROLE_LABELS: Record<Role, string> = {
  admin: 'Admin',
  store_manager: 'Playero',
  customer: 'Cliente',
};

function roleOptions(selected: Role): string {
  return ROLE_VALUES.map(
    (r) =>
      `<option value="${r}"${r === selected ? ' selected' : ''}>${ROLE_LABELS[r]}</option>`,
  ).join('');
}

const GOAL_LABELS: Record<GoalType, string> = {
  points: 'Puntos',
  login_days: 'Días de login',
  sales_amount: 'Monto de ventas',
};

const ORDER_STATUS_LABELS: Record<OrderStatus, string> = {
  pending: 'Pendiente',
  paid: 'Pagada',
  cancelled: 'Rechazada',
  refunded: 'Reembolsada',
};

const TH =
  'text-align:left;padding:0.5rem 0.75rem;border:1px solid var(--paper-border);font-weight:700;text-align:left;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.05em;background:var(--paper-head)';
const TD =
  'padding:0.5rem 0.75rem;border:1px solid var(--paper-border);font-size:0.9rem;vertical-align:top';
const TD_NUM =
  'padding:0.5rem 0.75rem;border:1px solid var(--paper-border);font-size:0.9rem;vertical-align:top;text-align:right;font-variant-numeric:tabular-nums';
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

const MAX_PRODUCT_IMAGE_BYTES = 5 * 1024 * 1024;

function slugify(name: string): string {
  return name
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 60);
}

async function uniqueSlug(db: Db, base: string, excludeId?: number): Promise<string> {
  const root = slugify(base) || 'producto';
  let candidate = root;
  for (let n = 2; n < 200; n += 1) {
    const rows = await db
      .select({ id: products.id })
      .from(products)
      .where(eq(products.slug, candidate))
      .limit(1);
    if (!rows[0] || rows[0].id === excludeId) return candidate;
    candidate = `${root}-${n}`;
  }
  return `${root}-${Date.now()}`;
}

function parsePriceCents(v: unknown): number | null {
  const s = str(v).replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
  if (!/^\d+(\.\d{1,2})?$/.test(s)) return null;
  const cents = Math.round(Number(s) * 100);
  return Number.isFinite(cents) && cents >= 0 ? cents : null;
}

function parseStock(v: unknown): number | null {
  const s = str(v);
  if (s === '') return null;
  const n = Number(s);
  return Number.isInteger(n) && n >= 0 ? n : null;
}

function toLocalInput(d: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function fmtFecha(d: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function formatArs(cents: number): string {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  }).format(cents / 100);
}

function isOrderStatus(v: string): v is OrderStatus {
  return (ORDER_STATUS_VALUES as readonly string[]).includes(v);
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
  const aviso = c.req.query('aviso');
  if (!aviso) return undefined;
  return aviso === 'eliminado' ? 'Usuario eliminado.' : 'Cambios guardados.';
}

function notFoundBody(volver: string): string {
  return `<div class="card"><h1>No encontrado</h1>
    <p class="muted">El recurso pedido no existe.</p>
    <a class="btn secondary" href="${volver}">Volver</a></div>`;
}

export const adminRoutes = new Hono<AppEnv>();

// Apply audit logging to all admin routes (after auth middleware)
adminRoutes.use('/admin/*', auditLog());

// El playero (store_manager) solo entra a las rutas de órdenes:
// listado, detalle, comprobante y nueva orden. Aprobar/rechazar es solo admin.
const PLAYERO_ADMIN_PATHS = [
  /^\/admin\/ordenes$/,
  /^\/admin\/ordenes\/nueva$/,
  /^\/admin\/ordenes\/\d+$/,
  /^\/admin\/comprobantes\/\d+$/,
];

adminRoutes.use('/admin', requireRole('admin'));
adminRoutes.use('/admin/*', async (c, next) => {
  const user = c.get('user');
  if (user?.role === 'store_manager') {
    const path = c.req.path;
    if (PLAYERO_ADMIN_PATHS.some((re) => re.test(path))) return next();
  }
  return requireRole('admin')(c, next);
});

// Rate limiting for admin routes: 100 requests/minute per authenticated user
adminRoutes.use('/admin*', adminRateLimit);

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
          <li><a href="/admin/tienda">Tienda y playeros</a></li>
          <li><a href="/admin/desafios">Desafíos semanales</a></li>
          <li><a href="/admin/ordenes">Órdenes y comprobantes</a></li>
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

  const bodyRows = rows
    .map(
      (u) => `<tr>
      <td style="${TD}">${escapeHtml(u.name)}</td>
      <td style="${TD}">${escapeHtml(u.email)}</td>
      <td style="${TD}">${ROLE_LABELS[u.role]}</td>
      <td style="${TD}">${u.active ? 'Sí' : 'No'}</td>
      <td style="${TD}">${u.points}</td>
      <td style="${TD}">
        <a class="btn secondary" href="/admin/usuarios/${u.id}/editar" style="${SMALL_BTN}">Editar</a>
        <form method="post" action="/admin/usuarios/${u.id}/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar este usuario? No se puede deshacer.')">
          <button type="submit" class="secondary" style="${SMALL_BTN}">Eliminar</button>
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
        <form method="get" action="/admin/usuarios" class="row">
          <input type="search" name="q" value="${escapeHtml(q)}" placeholder="Buscá por nombre o email" style="flex:1;min-width:12rem;margin-top:0">
          <button type="submit" style="${SMALL_BTN}">Buscá</button>
        </form>
        <p class="muted">${rows.length} resultado${rows.length === 1 ? '' : 's'}${rows.length === 50 ? ' (máx. 50)' : ''}</p>
        <table class="papel";margin-top:0.5rem">
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

adminRoutes.get('/admin/usuarios/:id/editar', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      status: 404,
    });
  }
  const db = dbOf(c);
  const rows = await db
    .select()
    .from(users)
    .where(eq(users.id, id))
    .limit(1);
  const u = rows[0];
  if (!u) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      status: 404,
    });
  }
  return c.html(
    page(
      { title: 'Editar usuario', brand: brandOf(c), user: c.get('user') },
      `<div class="card">
        <h1>Editar usuario</h1>
        <form method="post" action="/admin/usuarios/${u.id}/editar">
          <label for="user-name">Nombre</label>
          <input id="user-name" name="name" type="text" required minlength="2" value="${escapeHtml(u.name)}">
          <label for="user-email">Email</label>
          <input id="user-email" name="email" type="email" required value="${escapeHtml(u.email)}">
          <label for="user-role">Rol</label>
          <select id="user-role" name="role">${roleOptions(u.role)}</select>
          <button type="submit">Guardá</button>
          <a class="btn secondary" href="/admin/usuarios">Volver a usuarios</a>
        </form>
      </div>`,
    ),
  );
});

adminRoutes.post('/admin/usuarios/:id/editar', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      status: 404,
    });
  }
  const volver = `<a class="btn secondary" href="/admin/usuarios">Volver a usuarios</a>`;
  const body = await c.req.parseBody();
  const name = str(body.name);
  const email = str(body.email);
  const role = str(body.role);
  if (name.length < 2) {
    return render(
      c,
      'Usuarios',
      `<div class="card"><h1>No se guardó</h1>
       <p class="muted">Ingresá un nombre de al menos 2 caracteres.</p>${volver}</div>`,
      { error: 'Ingresá un nombre de al menos 2 caracteres.' },
    );
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return render(
      c,
      'Usuarios',
      `<div class="card"><h1>No se guardó</h1>
       <p class="muted">Ingresá un email válido.</p>${volver}</div>`,
      { error: 'Ingresá un email válido.' },
    );
  }
  if (!isRole(role)) {
    return render(
      c,
      'Usuarios',
      `<div class="card"><h1>No se guardó</h1>
       <p class="muted">Elegí un rol válido.</p>${volver}</div>`,
      { error: 'Elegí un rol válido.' },
    );
  }
  const db = dbOf(c);
  const rows = await db
    .select({ id: users.id, role: users.role, email: users.email })
    .from(users)
    .where(eq(users.id, id))
    .limit(1);
  if (!rows[0]) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      status: 404,
    });
  }
  const duplicado = await db
    .select({ id: users.id })
    .from(users)
    .where(eq(users.email, email))
    .limit(1);
  if (duplicado[0] && duplicado[0].id !== id) {
    return render(
      c,
      'Usuarios',
      `<div class="card"><h1>No se guardó</h1>
       <p class="muted">Ese email ya está en uso por otro usuario.</p>${volver}</div>`,
      { error: 'Ese email ya está en uso por otro usuario.' },
    );
  }
  const me = c.get('user')!;
  if (me.id === id && role !== rows[0].role) {
    return render(
      c,
      'Usuarios',
      `<div class="card"><h1>No se guardó</h1>
       <p class="muted">No podés cambiarte el rol a vos mismo.</p>${volver}</div>`,
      { error: 'No podés cambiarte el rol a vos mismo.' },
    );
  }
  await db
    .update(users)
    .set({ name, email, role, updatedAt: new Date() })
    .where(eq(users.id, id));
  if (email !== rows[0].email) {
    await db
      .delete(passwordResetTokens)
      .where(eq(passwordResetTokens.email, rows[0].email));
  }
  return c.redirect(avisado('/admin/usuarios'));
});

// Require MFA for deleting users
adminRoutes.post('/admin/usuarios/:id/eliminar', requireMFA(), async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      status: 404,
    });
  }
  const volver = `<a class="btn secondary" href="/admin/usuarios">Volver a usuarios</a>`;
  const me = c.get('user')!;
  if (me.id === id) {
    return render(
      c,
      'Usuarios',
      `<div class="card"><h1>No se pudo eliminar</h1>
       <p class="muted">No podés eliminarte a vos mismo.</p>${volver}</div>`,
      { error: 'No podés eliminarte a vos mismo.' },
    );
  }
  const db = dbOf(c);
  const rows = await db
    .select({ id: users.id, name: users.name, email: users.email, role: users.role })
    .from(users)
    .where(eq(users.id, id))
    .limit(1);
  const target = rows[0];
  if (!target) {
    return render(c, 'Usuarios', notFoundBody('/admin/usuarios'), {
      status: 404,
    });
  }
  if (target.role === 'admin') {
    const otros = await db
      .select({ n: count() })
      .from(users)
      .where(
        and(
          eq(users.role, 'admin'),
          eq(users.active, true),
          ne(users.id, id),
        ),
      )
      .limit(1);
    if ((otros[0]?.n ?? 0) === 0) {
      return render(
        c,
        'Usuarios',
        `<div class="card"><h1>No se pudo eliminar</h1>
         <p class="muted">No se puede eliminar al único administrador activo.</p>${volver}</div>`,
        { error: 'No se puede eliminar al único administrador activo.' },
      );
    }
  }
  // Historial de negocio: si existe, no se borra para no perder datos.
  const compras = await db
    .select({ n: count() })
    .from(orders)
    .where(or(eq(orders.userId, id), eq(orders.gestorId, id)))
    .limit(1);
  const puntos = await db
    .select({ n: count() })
    .from(pointLogs)
    .where(and(eq(pointLogs.userId, id), ne(pointLogs.reason, 'login')))
    .limit(1);
  const caja = await db
    .select({ n: count() })
    .from(cashDays)
    .where(or(eq(cashDays.openedBy, id), eq(cashDays.closedBy, id)))
    .limit(1);
  const novedades = await db
    .select({ n: count() })
    .from(announcements)
    .where(eq(announcements.authorId, id))
    .limit(1);
  const motivos: string[] = [];
  if ((compras[0]?.n ?? 0) > 0) motivos.push('tiene órdenes a su nombre');
  if ((puntos[0]?.n ?? 0) > 0) motivos.push('sumó puntos por compras o desafíos');
  if ((caja[0]?.n ?? 0) > 0) motivos.push('movió caja');
  if ((novedades[0]?.n ?? 0) > 0) motivos.push('publicó novedades');
  if (motivos.length > 0) {
    return render(
      c,
      'Usuarios',
      `<div class="card"><h1>No se pudo eliminar</h1>
       <p class="muted">No se puede eliminar a ${escapeHtml(target.name)} porque ${motivos.join(', ')}. Los usuarios con historial se conservan para no perder datos de la operación.</p>${volver}</div>`,
      { error: 'No se puede eliminar un usuario con historial.' },
    );
  }
  try {
    await db
      .delete(passwordResetTokens)
      .where(eq(passwordResetTokens.email, target.email));
    await db.delete(users).where(eq(users.id, id));
  } catch (err) {
    console.error('no se pudo eliminar el usuario', err);
    return render(
      c,
      'Usuarios',
      `<div class="card"><h1>No se pudo eliminar</h1>
       <p class="muted">Ocurrió un error inesperado. Probá de nuevo.</p>${volver}</div>`,
      { error: 'No se pudo eliminar el usuario.' },
    );
  }
  return c.redirect('/admin/usuarios?aviso=eliminado');
});

// --------------------------------------------------------------- tienda única

async function singleStore(db: Db) {
  const id = await ensureDefaultStore(db);
  const rows = await db
    .select()
    .from(stores)
    .where(eq(stores.id, id))
    .limit(1);
  return rows[0]!;
}

adminRoutes.get('/admin/tienda', async (c) => {
  const db = dbOf(c);
  const store = await singleStore(db);

  const gestores = await db
    .select({
      userId: users.id,
      name: users.name,
      email: users.email,
    })
    .from(storeUser)
    .innerJoin(users, eq(storeUser.userId, users.id))
    .where(eq(storeUser.storeId, store.id))
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
        <form method="post" action="/admin/tienda/gestores/quitar" style="display:inline">
          <input type="hidden" name="userId" value="${g.userId}">
          <button type="submit" class="secondary" style="${SMALL_BTN}">Quitá playero</button>
        </form>
      </td>
    </tr>`,
    )
    .join('');

  const productos = await db
    .select()
    .from(products)
    .orderBy(asc(products.sort), asc(products.name))
    .limit(200);

  const filasProductos = productos
    .map(
      (p) => `<tr>
      <td style="${TD}">${escapeHtml(p.name)}</td>
      <td style="${TD}">${escapeHtml(p.category)}</td>
      <td style="${TD}">${formatArs(p.priceCents)}</td>
      <td style="${TD}">${p.stock === null ? '—' : p.stock}</td>
      <td style="${TD}">${p.active ? 'Sí' : 'No'}</td>
      <td style="${TD}">
        <a class="btn secondary" href="/admin/tienda/productos/${p.id}/editar" style="${SMALL_BTN}">Editar</a>
        <form method="post" action="/admin/tienda/productos/${p.id}/activo" style="display:inline">
          <input type="hidden" name="active" value="${p.active ? '0' : '1'}">
          <button type="submit" class="secondary" style="${SMALL_BTN}">${p.active ? 'Desactivá' : 'Activá'}</button>
        </form>
      </td>
    </tr>`,
    )
    .join('');

  return c.html(
    page(
      {
        title: 'Tienda',
        brand: brandOf(c),
        user: c.get('user'),
        notice: avisoDe(c),
      },
      `<div class="card">
        <h1>Tienda</h1>
        <p class="muted">La estación opera con una única tienda. Editá su nombre acá abajo.</p>
        <form method="post" action="/admin/tienda/editar">
          <label for="store-name">Nombre</label>
          <input id="store-name" name="name" type="text" required value="${escapeHtml(store.name)}">
          <button type="submit">Guardá</button>
        </form>
      </div>
      <div class="card">
        <h2>Playeros asignados</h2>
        <table class="papel">
          <thead><tr>
            <th style="${TH}">Nombre</th>
            <th style="${TH}">Email</th>
            <th style="${TH}">Acciones</th>
          </tr></thead>
          <tbody>${filasGestores || `<tr><td colspan="3" style="${TD}" class="muted">Todavía no hay playeros asignados.</td></tr>`}</tbody>
        </table>
        <h2 style="margin-top:1rem">Asigná playero</h2>
        ${
          disponibles.length
            ? `<form method="post" action="/admin/tienda/gestores">
          <label for="userId">Usuario con rol playero</label>
          <select id="userId" name="userId" required>${opcionesGestor}</select>
          <button type="submit">Asigná playero</button>
        </form>`
            : `<p class="muted">No hay usuarios con rol playero disponibles. Creá o reasigná usuarios desde <a href="/admin/usuarios">Usuarios</a>.</p>`
        }
      </div>
      <div class="card">
        <h2>Productos</h2>
        <p class="muted">Cargá los productos que se venden en la tienda online.</p>
        <p><a class="btn" href="/admin/tienda/productos/nuevo">Nuevo producto</a></p>
        <table class="papel">
          <thead><tr>
            <th style="${TH}">Nombre</th>
            <th style="${TH}">Categoría</th>
            <th style="${TH}">Precio</th>
            <th style="${TH}">Stock</th>
            <th style="${TH}">Activo</th>
            <th style="${TH}">Acciones</th>
          </tr></thead>
          <tbody>${filasProductos || `<tr><td colspan="6" style="${TD}" class="muted">Todavía no hay productos. Creá el primero con «Nuevo producto».</td></tr>`}</tbody>
        </table>
      </div>`,
    ),
  );
});

adminRoutes.post('/admin/tienda/editar', async (c) => {
  const body = await c.req.parseBody();
  const name = str(body.name);
  if (!name) {
    return render(c, 'Tienda', notFoundBody('/admin/tienda'), {
      error: 'Ingresá el nombre de la tienda.',
    });
  }
  const db = dbOf(c);
  const store = await singleStore(db);
  await db
    .update(stores)
    .set({ name, updatedAt: new Date() })
    .where(eq(stores.id, store.id));
  return c.redirect(avisado('/admin/tienda'));
});

adminRoutes.post('/admin/tienda/gestores', async (c) => {
  const volver = '/admin/tienda';
  const body = await c.req.parseBody();
  const userId = Number(str(body.userId));
  if (!Number.isInteger(userId) || userId <= 0) {
    return render(c, 'Tienda', notFoundBody(volver), {
      error: 'Seleccioná un playero válido.',
    });
  }
  const db = dbOf(c);
  const store = await singleStore(db);
  const userRows = await db
    .select({ id: users.id, role: users.role })
    .from(users)
    .where(eq(users.id, userId))
    .limit(1);
  if (!userRows[0]) {
    return render(c, 'Tienda', notFoundBody(volver), {
      error: 'No existe ese usuario.',
    });
  }
  if (userRows[0].role !== 'store_manager') {
    return render(c, 'Tienda', notFoundBody(volver), {
      error: 'Ese usuario no tiene el rol de playero.',
    });
  }
  const yaAsignado = await db
    .select({ userId: storeUser.userId })
    .from(storeUser)
    .where(and(eq(storeUser.storeId, store.id), eq(storeUser.userId, userId)))
    .limit(1);
  if (yaAsignado[0]) {
    return render(c, 'Tienda', notFoundBody(volver), {
      error: 'Ese playero ya está asignado a la tienda.',
    });
  }
  await db.insert(storeUser).values({ storeId: store.id, userId });
  return c.redirect(avisado(volver));
});

adminRoutes.post('/admin/tienda/gestores/quitar', async (c) => {
  const body = await c.req.parseBody();
  const userId = Number(str(body.userId));
  if (!Number.isInteger(userId) || userId <= 0) {
    return render(c, 'Tienda', notFoundBody('/admin/tienda'), {
      error: 'Seleccioná un playero válido.',
    });
  }
  const db = dbOf(c);
  const store = await singleStore(db);
  await db
    .delete(storeUser)
    .where(and(eq(storeUser.storeId, store.id), eq(storeUser.userId, userId)));
  return c.redirect(avisado('/admin/tienda'));
});

// ---------------------------------------------------------------- productos

function productoFormFields(p?: {
  name: string;
  category: string;
  priceCents: number;
  stock: number | null;
  description: string;
}): string {
  const precio =
    p === undefined ? '' : (p.priceCents / 100).toFixed(2).replace('.', ',');
  return `
    <label for="p-name">Nombre</label>
    <input id="p-name" name="name" type="text" required minlength="2" value="${escapeHtml(p?.name ?? '')}">
    <label for="p-category">Categoría</label>
    <input id="p-category" name="category" type="text" value="${escapeHtml(p?.category ?? '')}" placeholder="Ej: lubricantes, bebidas, general">
    <label for="p-price">Precio (ARS)</label>
    <input id="p-price" name="price" type="text" required inputmode="decimal" value="${precio}" placeholder="Ej: 1500,50">
    <label for="p-stock">Stock (vacío = sin control de stock)</label>
    <input id="p-stock" name="stock" type="number" min="0" step="1" value="${p?.stock ?? ''}">
    <label for="p-description">Descripción</label>
    <textarea id="p-description" name="description" rows="3" placeholder="Detalle breve del producto">${escapeHtml(p?.description ?? '')}</textarea>`;
}

adminRoutes.get('/admin/tienda/productos/nuevo', (c) => {
  return c.html(
    page(
      { title: 'Nuevo producto', brand: brandOf(c), user: c.get('user') },
      `<div class="card">
        <h1>Nuevo producto</h1>
        <form method="post" action="/admin/tienda/productos/nuevo" enctype="multipart/form-data">
          ${productoFormFields()}
          <label for="p-image">Imagen (opcional, JPG/PNG/WEBP hasta 5 MB)</label>
          <input id="p-image" name="image" type="file" accept="image/*">
          <button type="submit">Creá producto</button>
          <a class="btn secondary" href="/admin/tienda">Volver a tienda</a>
        </form>
      </div>`,
    ),
  );
});

adminRoutes.post('/admin/tienda/productos/nuevo', async (c) => {
  const volver = `<a class="btn secondary" href="/admin/tienda">Volver a tienda</a>`;
  const body = await c.req.parseBody();
  const name = str(body.name);
  const category = str(body.category) || 'general';
  const priceCents = parsePriceCents(body.price);
  const stock = parseStock(body.stock);
  const description = str(body.description);
  if (name.length < 2) {
    return render(
      c,
      'Nuevo producto',
      `<div class="card"><h1>No se creó</h1>
       <p class="muted">Ingresá un nombre de al menos 2 caracteres.</p>${volver}</div>`,
      { error: 'Ingresá un nombre de al menos 2 caracteres.' },
    );
  }
  if (priceCents === null) {
    return render(
      c,
      'Nuevo producto',
      `<div class="card"><h1>No se creó</h1>
       <p class="muted">Ingresá un precio válido (ej: 1500 o 1500,50).</p>${volver}</div>`,
      { error: 'Ingresá un precio válido.' },
    );
  }
  const db = dbOf(c);

  const fileRaw = body.image;
  let imageFile: File | null = null;
  if (fileRaw instanceof File && fileRaw.size > 0) {
    if (!fileRaw.type.startsWith('image/')) {
      return render(
        c,
        'Nuevo producto',
        `<div class="card"><h1>No se creó</h1>
         <p class="muted">La imagen tiene que ser un archivo de imagen (JPG, PNG o WEBP).</p>${volver}</div>`,
        { error: 'La imagen tiene que ser un archivo de imagen.' },
      );
    }
    if (fileRaw.size > MAX_PRODUCT_IMAGE_BYTES) {
      return render(
        c,
        'Nuevo producto',
        `<div class="card"><h1>No se creó</h1>
         <p class="muted">La imagen supera los 5 MB. Elegí una más liviana.</p>${volver}</div>`,
        { error: 'La imagen supera los 5 MB.' },
      );
    }
    imageFile = fileRaw;
  }

  const slug = await uniqueSlug(db, name);
  const [inserted] = await db
    .insert(products)
    .values({
      name,
      slug,
      category,
      priceCents,
      stock,
      description,
      sort: 0,
    })
    .returning({ id: products.id });

  if (imageFile && inserted) {
    const ext = (imageFile.name.split('.').pop() ?? '').toLowerCase();
    const key = `productos/${inserted.id}.${ext && ext.length <= 5 ? ext : 'img'}`;
    await c.env.R2.put(key, imageFile, {
      httpMetadata: { contentType: imageFile.type },
    });
    await db
      .update(products)
      .set({ imageKey: key, updatedAt: new Date() })
      .where(eq(products.id, inserted.id));
  }
  return c.redirect(avisado('/admin/tienda'));
});

adminRoutes.get('/admin/tienda/productos/:id/editar', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Tienda', notFoundBody('/admin/tienda'), { status: 404 });
  }
  const db = dbOf(c);
  const rows = await db
    .select()
    .from(products)
    .where(eq(products.id, id))
    .limit(1);
  const p = rows[0];
  if (!p) {
    return render(c, 'Tienda', notFoundBody('/admin/tienda'), { status: 404 });
  }
  const preview = p.imageKey
    ? `<p class="muted">Imagen actual:</p>
       <img src="/producto/${p.id}/imagen" alt="${escapeHtml(p.name)}" style="max-width:8rem;max-height:8rem;border:1px solid var(--paper-border);border-radius:4px;display:block;margin-bottom:0.75rem">
       <p class="muted">Dejá el campo vacío para mantener la imagen actual.</p>`
    : `<p class="muted">Todavía no tiene imagen.</p>`;
  return c.html(
    page(
      { title: 'Editar producto', brand: brandOf(c), user: c.get('user') },
      `<div class="card">
        <h1>Editar producto</h1>
        <form method="post" action="/admin/tienda/productos/${p.id}/editar" enctype="multipart/form-data">
          ${productoFormFields(p)}
          ${preview}
          <label for="p-image">Nueva imagen (opcional, JPG/PNG/WEBP hasta 5 MB)</label>
          <input id="p-image" name="image" type="file" accept="image/*">
          <button type="submit">Guardá</button>
          <a class="btn secondary" href="/admin/tienda">Volver a tienda</a>
        </form>
      </div>`,
    ),
  );
});

// Require MFA for editing products (price changes)
adminRoutes.post('/admin/tienda/productos/:id/editar', requireMFA(), async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Tienda', notFoundBody('/admin/tienda'), { status: 404 });
  }
  const volver = `<a class="btn secondary" href="/admin/tienda">Volver a tienda</a>`;
  const db = dbOf(c);
  const rows = await db
    .select()
    .from(products)
    .where(eq(products.id, id))
    .limit(1);
  if (!rows[0]) {
    return render(c, 'Tienda', notFoundBody('/admin/tienda'), { status: 404 });
  }
  const oldPriceCents = rows[0].priceCents;
  const oldName = rows[0].name;
  const body = await c.req.parseBody();
  const name = str(body.name);
  const category = str(body.category) || 'general';
  const priceCents = parsePriceCents(body.price);
  const stock = parseStock(body.stock);
  const description = str(body.description);
  if (name.length < 2) {
    return render(
      c,
      'Editar producto',
      `<div class="card"><h1>No se guardó</h1>
       <p class="muted">Ingresá un nombre de al menos 2 caracteres.</p>${volver}</div>`,
      { error: 'Ingresá un nombre de al menos 2 caracteres.' },
    );
  }
  if (priceCents === null) {
    return render(
      c,
      'Editar producto',
      `<div class="card"><h1>No se guardó</h1>
       <p class="muted">Ingresá un precio válido (ej: 1500 o 1500,50).</p>${volver}</div>`,
      { error: 'Ingresá un precio válido.' },
    );
  }

  const fileRaw = body.image;
  let imageFile: File | null = null;
  if (fileRaw instanceof File && fileRaw.size > 0) {
    if (!fileRaw.type.startsWith('image/')) {
      return render(
        c,
        'Editar producto',
        `<div class="card"><h1>No se guardó</h1>
         <p class="muted">La imagen tiene que ser un archivo de imagen (JPG, PNG o WEBP).</p>${volver}</div>`,
        { error: 'La imagen tiene que ser un archivo de imagen.' },
      );
    }
    if (fileRaw.size > MAX_PRODUCT_IMAGE_BYTES) {
      return render(
        c,
        'Editar producto',
        `<div class="card"><h1>No se guardó</h1>
         <p class="muted">La imagen supera los 5 MB. Elegí una más liviana.</p>${volver}</div>`,
        { error: 'La imagen supera los 5 MB.' },
      );
    }
    imageFile = fileRaw;
  }

  const slug = await uniqueSlug(db, name, id);
  await db
    .update(products)
    .set({ name, slug, category, priceCents, stock, description, updatedAt: new Date() })
    .where(eq(products.id, id));

  // Notificar a playeros si cambió el precio
  if (priceCents !== oldPriceCents) {
    const playeros = await db
      .select({ userId: users.id })
      .from(users)
      .where(eq(users.role, 'store_manager'));
    if (playeros.length > 0) {
      const notifValues = playeros.map((p) => ({
        userId: p.userId,
        type: 'price_change',
        title: 'Cambio de precio',
        body: 'El producto "' + escapeHtml(name) + '" cambió de ' + formatArs(oldPriceCents) + ' a ' + formatArs(priceCents) + '.',
        data: JSON.stringify({ productId: id, oldPriceCents, newPriceCents: priceCents }),
      }));
      await db.insert(notifications).values(notifValues);
    }
  }

  if (imageFile) {
    const ext = (imageFile.name.split('.').pop() ?? '').toLowerCase();
    const key = `productos/${id}.${ext && ext.length <= 5 ? ext : 'img'}`;
    await c.env.R2.put(key, imageFile, {
      httpMetadata: { contentType: imageFile.type },
    });
    const vieja = rows[0].imageKey;
    await db
      .update(products)
      .set({ imageKey: key, updatedAt: new Date() })
      .where(eq(products.id, id));
    if (vieja && vieja !== key) {
      await c.env.R2.delete(vieja);
    }
  }
  return c.redirect(avisado('/admin/tienda'));
});

// Require MFA for toggling product active status
adminRoutes.post('/admin/tienda/productos/:id/activo', requireMFA(), async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Tienda', notFoundBody('/admin/tienda'), { status: 404 });
  }
  const body = await c.req.parseBody();
  const active = parseActivo(body.active);
  if (active === null) {
    return render(c, 'Tienda', notFoundBody('/admin/tienda'), {
      error: "Enviá active = '1' para activar o '0' para desactivar.",
    });
  }
  const db = dbOf(c);
  const updated = await db
    .update(products)
    .set({ active, updatedAt: new Date() })
    .where(eq(products.id, id))
    .returning({ id: products.id });
  if (!updated[0]) {
    return render(c, 'Tienda', notFoundBody('/admin/tienda'), { status: 404 });
  }
  return c.redirect(avisado('/admin/tienda'));
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
      <h2>${escapeHtml(ch.title)} ${ch.active ? '<span class="pill">Activo</span>' : '<span class="pill" style="background:#52525b">Inactivo</span>'}</h2>
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

// ----------------------------------------------------------------- órdenes

const ORDENES_PAGE_SIZE = 20;

function ordenAvisoDe(c: Context<AppEnv>): string | undefined {
  const v = c.req.query('aviso');
  if (v === 'aprobada') {
    return 'Orden aprobada. Si correspondía, se acreditaron los puntos.';
  }
  if (v === 'rechazada') return 'Orden rechazada y stock devuelto.';
  if (v === 'nueva') return 'Orden creada como pendiente. El admin la aprueba.';
  if (v === 'ok') return 'Cambios guardados.';
  return undefined;
}

function ordenesHref(filtros: Record<string, string>, page: number): string {
  const params = new URLSearchParams(filtros);
  if (page > 1) params.set('page', String(page));
  const qs = params.toString();
  return `/admin/ordenes${qs ? `?${qs}` : ''}`;
}

function ordenErrorBody(volver: string): string {
  return `<div class="card"><h1>No se pudo completar</h1>
    <p class="muted">Volvé al detalle de la orden para revisar su estado.</p>
    <a class="btn secondary" href="${volver}">Volver a la orden</a></div>`;
}

adminRoutes.get('/admin/ordenes', async (c) => {
  const db = dbOf(c);
  const estadoRaw = str(c.req.query('estado'));
  const estado = isOrderStatus(estadoRaw) ? estadoRaw : null;
  const tiendaRaw = Number(str(c.req.query('tienda')));
  const tiendaId =
    Number.isInteger(tiendaRaw) && tiendaRaw > 0 ? tiendaRaw : null;
  const q = str(c.req.query('q'));
  const pageRaw = Number(str(c.req.query('page')));
  const pageNum = Number.isInteger(pageRaw) && pageRaw > 0 ? pageRaw : 1;

  const conds: SQL[] = [];
  if (estado) conds.push(eq(orders.status, estado));
  if (tiendaId) conds.push(eq(orders.storeId, tiendaId));
  if (q) {
    conds.push(or(like(orders.number, `%${q}%`), like(users.email, `%${q}%`))!);
  }
  const where = conds.length > 0 ? and(...conds) : undefined;

  const totalRows = await db
    .select({ n: count() })
    .from(orders)
    .innerJoin(users, eq(orders.userId, users.id))
    .where(where);
  const total = totalRows[0]?.n ?? 0;
  const totalPages = Math.max(1, Math.ceil(total / ORDENES_PAGE_SIZE));

  const gestores = alias(users, 'gestor_alias');
  const rows = await db
    .select({
      id: orders.id,
      number: orders.number,
      createdAt: orders.createdAt,
      totalCents: orders.totalCents,
      status: orders.status,
      proofKey: orders.proofKey,
      cliente: users.name,
      email: users.email,
      tienda: stores.name,
      gestor: gestores.name,
    })
    .from(orders)
    .innerJoin(users, eq(orders.userId, users.id))
    .innerJoin(stores, eq(orders.storeId, stores.id))
    .leftJoin(gestores, eq(orders.gestorId, gestores.id))
    .where(where)
    .orderBy(desc(orders.createdAt), desc(orders.id))
    .limit(ORDENES_PAGE_SIZE)
    .offset((pageNum - 1) * ORDENES_PAGE_SIZE);

  const tiendasRows = await db
    .select({ id: stores.id, name: stores.name })
    .from(stores)
    .orderBy(stores.name);

  const opcionesEstado = ORDER_STATUS_VALUES.map(
    (s) =>
      `<option value="${s}"${s === estado ? ' selected' : ''}>${ORDER_STATUS_LABELS[s]}</option>`,
  ).join('');
  const opcionesTienda = tiendasRows
    .map(
      (s) =>
        `<option value="${s.id}"${s.id === tiendaId ? ' selected' : ''}>${escapeHtml(s.name)}</option>`,
    )
    .join('');

  const bodyRows = rows
    .map(
      (o) => `<tr>
      <td style="${TD}">${escapeHtml(o.number)}</td>
      <td style="${TD}">${fmtFecha(o.createdAt)}</td>
      <td style="${TD}">${escapeHtml(o.cliente)}<br><span class="muted">${escapeHtml(o.email)}</span></td>
      <td style="${TD}">${escapeHtml(o.tienda)}</td>
      <td style="${TD}">${o.gestor ? escapeHtml(o.gestor) : '<span class="muted">—</span>'}</td>
      <td style="${TD}">${formatArs(o.totalCents)}</td>
      <td style="${TD}"><span class="pill">${ORDER_STATUS_LABELS[o.status] ?? o.status}</span></td>
      <td style="${TD}">${
        o.proofKey
          ? `<a href="/admin/comprobantes/${o.id}"><img src="/admin/comprobantes/${o.id}" alt="Comprobante" style="height:2.5rem;border-radius:0.3rem;border:1px solid #e4e4e7;object-fit:cover"></a>`
          : '<span class="muted">No</span>'
      }</td>
      <td style="${TD}"><a class="btn secondary" href="/admin/ordenes/${o.id}" style="${SMALL_BTN}">Detalle</a></td>
    </tr>`,
    )
    .join('');

  const filtros: Record<string, string> = {};
  if (estado) filtros.estado = estado;
  if (tiendaId) filtros.tienda = String(tiendaId);
  if (q) filtros.q = q;

  const paginacion =
    totalPages > 1
      ? `<div class="row" style="margin-top:0.75rem">
          ${pageNum > 1 ? `<a class="btn secondary" href="${ordenesHref(filtros, pageNum - 1)}" style="${SMALL_BTN}">Anterior</a>` : ''}
          <span class="muted">Página ${pageNum} de ${totalPages}</span>
          ${pageNum < totalPages ? `<a class="btn secondary" href="${ordenesHref(filtros, pageNum + 1)}" style="${SMALL_BTN}">Siguiente</a>` : ''}
        </div>`
      : '';

  return c.html(
    page(
      {
        title: 'Órdenes',
        brand: brandOf(c),
        user: c.get('user'),
        notice: ordenAvisoDe(c),
      },
      `<div class="card">
        <h1>Órdenes</h1>
        <div class="row" style="margin-bottom:0.75rem">
          <a class="btn" href="/admin/ordenes/nueva">Nueva orden</a>
        </div>
        <form method="get" action="/admin/ordenes" class="row">
          <select name="estado" style="width:auto;margin-top:0">
            <option value="">Todos los estados</option>
            ${opcionesEstado}
          </select>
          <select name="tienda" style="width:auto;margin-top:0">
            <option value="">Todas las tiendas</option>
            ${opcionesTienda}
          </select>
          <input type="search" name="q" value="${escapeHtml(q)}" placeholder="Número o email" style="flex:1;min-width:12rem;margin-top:0">
          <button type="submit" style="${SMALL_BTN}">Buscá</button>
        </form>
        <p class="muted">${total} orden${total === 1 ? '' : 'es'}</p>
        <table class="papel";margin-top:0.5rem">
          <thead><tr>
            <th style="${TH}">Número</th>
            <th style="${TH}">Fecha</th>
            <th style="${TH}">Cliente</th>
            <th style="${TH}">Tienda</th>
            <th style="${TH}">Playero</th>
            <th style="${TH}">Total</th>
            <th style="${TH}">Estado</th>
            <th style="${TH}">Comprobante</th>
            <th style="${TH}">Acciones</th>
          </tr></thead>
          <tbody>${bodyRows || `<tr><td colspan="9" style="${TD}" class="muted">No hay órdenes para mostrar.</td></tr>`}</tbody>
        </table>
        ${paginacion}
      </div>`,
    ),
  );
});

adminRoutes.get('/admin/ordenes/nueva', async (c) => {
  const db = dbOf(c);
  const [clientesRows, productosRows] = await Promise.all([
    db
      .select({ id: users.id, name: users.name, email: users.email })
      .from(users)
      .where(eq(users.active, true))
      .orderBy(asc(users.name), asc(users.id)),
    db
      .select({
        id: products.id,
        name: products.name,
        priceCents: products.priceCents,
        stock: products.stock,
      })
      .from(products)
      .where(eq(products.active, true))
      .orderBy(asc(products.name), asc(products.id)),
  ]);

  const opcionesClientes = clientesRows
    .map(
      (u) =>
        `<option value="${u.id}">${escapeHtml(u.name)} · ${escapeHtml(u.email)}</option>`,
    )
    .join('');

  const filasProductos = productosRows
    .map(
      (p) => `<tr>
      <td style="${TD}">${escapeHtml(p.name)}</td>
      <td style="${TD}">${formatArs(p.priceCents)}</td>
      <td style="${TD}">${p.stock === null ? 'Sin límite' : p.stock}</td>
      <td style="${TD_NUM}"><input type="number" name="qty_${p.id}" value="0" min="0" step="1" inputmode="numeric" style="max-width:6rem;text-align:right" aria-label="Cantidad de ${escapeHtml(p.name)}"></td>
    </tr>`,
    )
    .join('');

  return c.html(
    page(
      { title: 'Nueva orden', brand: brandOf(c), user: c.get('user') },
      `<div class="card">
        <h1>Nueva orden</h1>
        <p class="muted">Cargá una orden a mano. Queda <strong>pendiente</strong> y el admin la aprueba.</p>
        <form method="post" action="/admin/ordenes/nueva">
          <label for="userId">Cliente</label>
          <select id="userId" name="userId" required>
            <option value="">Elegí un cliente…</option>
            ${opcionesClientes}
          </select>
          <label for="pago">Método de pago</label>
          <select id="pago" name="pago" required>
            <option value="transferencia">Transferencia bancaria</option>
            <option value="mercadopago">Mercado Pago</option>
          </select>
          <label for="notas">Notas (opcional)</label>
          <textarea id="notas" name="notas" rows="2" maxlength="500"></textarea>
          <button type="submit">Creá la orden</button>
          <a class="btn secondary" href="/admin/ordenes">Volver a órdenes</a>
        </form>
      </div>
      <div class="card">
        <h2>Productos</h2>
        <p class="muted">Poné la cantidad de cada producto. Los que quedan en 0 no se agregan.</p>
        <table class="papel">
          <thead><tr>
            <th style="${TH}">Producto</th>
            <th style="${TH}">Precio</th>
            <th style="${TH}">Stock</th>
            <th style="${TH}">Cantidad</th>
          </tr></thead>
          <tbody>${filasProductos || `<tr><td colspan="4" style="${TD}" class="muted">No hay productos activos.</td></tr>`}</tbody>
        </table>
      </div>`,
    ),
  );
});

adminRoutes.post('/admin/ordenes/nueva', async (c) => {
  const volver = '<a class="btn secondary" href="/admin/ordenes/nueva">Volver</a>';
  const body = await c.req.parseBody();
  const userId = Number(str(body.userId));
  const pago = str(body.pago);
  const notas = str(body.notas);
  if (!Number.isInteger(userId) || userId <= 0) {
    return render(
      c,
      'Nueva orden',
      `<div class="card"><h1>No se creó</h1><p class="muted">Elegí un cliente.</p>${volver}</div>`,
      { error: 'Elegí un cliente.' },
    );
  }
  if (pago !== 'transferencia' && pago !== 'mercadopago') {
    return render(
      c,
      'Nueva orden',
      `<div class="card"><h1>No se creó</h1><p class="muted">Elegí un método de pago válido.</p>${volver}</div>`,
      { error: 'Elegí un método de pago válido.' },
    );
  }

  const db = dbOf(c);
  const clienteRows = await db
    .select({ id: users.id, active: users.active })
    .from(users)
    .where(eq(users.id, userId))
    .limit(1);
  if (!clienteRows[0] || !clienteRows[0].active) {
    return render(
      c,
      'Nueva orden',
      `<div class="card"><h1>No se creó</h1><p class="muted">El cliente elegido no existe o está inactivo.</p>${volver}</div>`,
      { error: 'El cliente elegido no existe o está inactivo.' },
    );
  }

  const productoRows = await db
    .select()
    .from(products)
    .where(eq(products.active, true));
  const lines = productoRows
    .map((p) => ({ product: p, qty: Number(str(body[`qty_${p.id}`])) || 0 }))
    .filter((l) => l.qty > 0);
  if (lines.length === 0) {
    return render(
      c,
      'Nueva orden',
      `<div class="card"><h1>No se creó</h1><p class="muted">Poné la cantidad de al menos un producto.</p>${volver}</div>`,
      { error: 'Poné la cantidad de al menos un producto.' },
    );
  }
  for (const line of lines) {
    if (!Number.isInteger(line.qty) || line.qty < 0) {
      return render(
        c,
        'Nueva orden',
        `<div class="card"><h1>No se creó</h1><p class="muted">Las cantidades tienen que ser números enteros.</p>${volver}</div>`,
        { error: 'Las cantidades tienen que ser números enteros.' },
      );
    }
    if (line.product.stock !== null && line.product.stock < line.qty) {
      return render(
        c,
        'Nueva orden',
        `<div class="card"><h1>No se creó</h1><p class="muted">No hay stock suficiente de "${escapeHtml(line.product.name)}" (disponible: ${line.product.stock}).</p>${volver}</div>`,
        { error: `No hay stock suficiente de "${line.product.name}".` },
      );
    }
  }

  const subtotalCents = lines.reduce(
    (acc, l) => acc + l.product.priceCents * l.qty,
    0,
  );

  const stock = await decrementStockGuard(db, lines);
  if (!stock.ok) {
    return render(
      c,
      'Nueva orden',
      `<div class="card"><h1>No se creó</h1><p class="muted">${escapeHtml(stock.error)}</p>${volver}</div>`,
      { error: stock.error },
    );
  }

  let order;
  try {
    const storeId = await ensureDefaultStore(db);
    const me = c.get('user')!;
    const gestorId =
      me.role === 'store_manager' ? me.id : await findAutoGestor(db, storeId);
    order = await insertOrderWithRetry(db, {
      userId,
      storeId,
      gestorId,
      subtotalCents,
      discountCents: 0,
      totalCents: subtotalCents,
      couponId: null,
      paymentMethod: pago,
      notes: notas || null,
    });
    await insertOrderItems(db, order.id, lines);
  } catch (err) {
    await restoreStock(db, stock.decremented);
    throw err;
  }

  return c.redirect(`/admin/ordenes/${order.id}?aviso=nueva`);
});

adminRoutes.get('/admin/ordenes/:id', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  const volver = '/admin/ordenes';
  if (!id) {
    return render(c, 'Ordenes', notFoundBody(volver), { status: 404 });
  }
  const db = dbOf(c);
  const orderRows = await db
    .select()
    .from(orders)
    .where(eq(orders.id, id))
    .limit(1);
  const order = orderRows[0];
  if (!order) {
    return render(c, 'Ordenes', notFoundBody(volver), { status: 404 });
  }

  const [clienteRows, gestorRows, storeRows, items, aprobadorRows] =
    await Promise.all([
      db
        .select({ name: users.name, email: users.email })
        .from(users)
        .where(eq(users.id, order.userId))
        .limit(1),
      order.gestorId === null
        ? Promise.resolve([])
        : db
            .select({ name: users.name })
            .from(users)
            .where(eq(users.id, order.gestorId))
            .limit(1),
      db
        .select({ name: stores.name })
        .from(stores)
        .where(eq(stores.id, order.storeId))
        .limit(1),
      db.select().from(orderItems).where(eq(orderItems.orderId, order.id)),
      order.approvedBy === null
        ? Promise.resolve([])
        : db
            .select({ name: users.name })
            .from(users)
            .where(eq(users.id, order.approvedBy))
            .limit(1),
    ]);
  const cliente = clienteRows[0];
  const gestor = gestorRows[0];
  const store = storeRows[0];
  const aprobador = aprobadorRows[0];

  const itemsHtml = items
    .map(
      (it) => `<tr>
      <td style="${TD}">${escapeHtml(it.nameSnapshot)}</td>
      <td style="${TD}">${it.qty}</td>
      <td style="${TD}">${formatArs(it.unitPriceCents)}</td>
      <td style="${TD}">${formatArs(it.unitPriceCents * it.qty)}</td>
    </tr>`,
    )
    .join('');

  const proofHtml = order.proofKey
    ? `<p><a href="/admin/comprobantes/${order.id}">Ver comprobante</a></p>
       <img src="/admin/comprobantes/${order.id}" alt="Comprobante" style="max-width:100%;border-radius:0.5rem;border:1px solid #e4e4e7;margin-top:0.5rem">`
    : '<p class="muted">Sin comprobante adjunto.</p>';

  const isAdmin = c.get('user')?.role === 'admin';
  const acciones =
    isAdmin && order.status === 'pending'
      ? `<div class="row" style="margin-top:1rem">
          <form method="post" action="/admin/ordenes/${order.id}/aprobar" style="display:inline;margin-top:0">
            <button type="submit" style="${SMALL_BTN}">Aprobá el pago</button>
          </form>
          <form method="post" action="/admin/ordenes/${order.id}/rechazar" style="display:inline;margin-top:0;min-width:14rem">
            <label for="motivo-${order.id}" style="margin-top:0">Motivo del rechazo</label>
            <textarea id="motivo-${order.id}" name="motivo" rows="2" required></textarea>
            <button type="submit" class="secondary" style="${SMALL_BTN}">Rechazá</button>
          </form>
        </div>`
      : order.status === 'pending'
        ? '<p class="muted" style="margin-top:1rem">Pendiente de aprobación por el admin.</p>'
        : '';

  const historial = [
    order.paidAt ? `<div class="row"><span>Pago registrado</span><span>${fmtFecha(order.paidAt)}</span></div>` : '',
    order.approvedAt
      ? `<div class="row"><span>Aprobada</span><span>${fmtFecha(order.approvedAt)}${aprobador ? ` · por ${escapeHtml(aprobador.name)}` : ''}</span></div>`
      : '',
    order.notes
      ? `<div class="row"><span>Motivo de rechazo / nota</span><span>${escapeHtml(order.notes)}</span></div>`
      : '',
  ]
    .filter(Boolean)
    .join('');

  return c.html(
    page(
      {
        title: `Orden ${order.number}`,
        brand: brandOf(c),
        user: c.get('user'),
        notice: ordenAvisoDe(c),
      },
      `<div class="card">
        <h1>Orden ${escapeHtml(order.number)}</h1>
        <div class="row">
          <span class="pill">${ORDER_STATUS_LABELS[order.status] ?? order.status}</span>
          <span class="muted">Creada: ${fmtFecha(order.createdAt)}</span>
          <span class="muted">Pago: ${order.paymentMethod === 'transferencia' ? 'Transferencia bancaria' : 'Mercado Pago'}</span>
        </div>
        <p class="muted">Cliente: ${cliente ? `${escapeHtml(cliente.name)} · ${escapeHtml(cliente.email)}` : `#${order.userId}`} · Tienda: ${store ? escapeHtml(store.name) : `#${order.storeId}`}${gestor ? ` · Playero: ${escapeHtml(gestor.name)}` : ''}</p>
        ${acciones}
      </div>
      <div class="card">
        <h2>Productos</h2>
        <table class="papel"">
          <thead><tr>
            <th style="${TH}">Producto</th>
            <th style="${TH}">Cantidad</th>
            <th style="${TH}">Precio unitario</th>
            <th style="${TH}">Subtotal</th>
          </tr></thead>
          <tbody>${itemsHtml || `<tr><td colspan="4" style="${TD}" class="muted">Sin ítems.</td></tr>`}</tbody>
        </table>
        <div class="row" style="margin-top:0.75rem"><span>Subtotal</span><span>${formatArs(order.subtotalCents)}</span></div>
        <div class="row"><span>Descuento</span><span>-${formatArs(order.discountCents)}</span></div>
        <div class="row"><strong>Total</strong><strong>${formatArs(order.totalCents)}</strong></div>
      </div>
      <div class="card">
        <h2>Comprobante</h2>
        ${proofHtml}
      </div>
      ${
        historial
          ? `<div class="card"><h2>Historial</h2>${historial}</div>`
          : ''
      }
      <div class="card">
        <a class="btn secondary" href="/admin/ordenes">Volver a órdenes</a>
      </div>`,
    ),
  );
});

adminRoutes.get('/admin/comprobantes/:id', async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) return c.notFound();
  const db = dbOf(c);
  const rows = await db
    .select({ proofKey: orders.proofKey })
    .from(orders)
    .where(eq(orders.id, id))
    .limit(1);
  const proofKey = rows[0]?.proofKey;
  if (!proofKey) return c.notFound();
  const obj = await c.env.R2.get(proofKey);
  if (!obj) return c.notFound();
  const contentType = obj.httpMetadata?.contentType ?? mimeFromKey(proofKey);
  return new Response(obj.body, {
    headers: {
      'Content-Type': contentType,
      'Cache-Control': 'private, max-age=300',
    },
  });
});

// Require MFA for approving orders
adminRoutes.post('/admin/ordenes/:id/aprobar', requireMFA(), async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Ordenes', notFoundBody('/admin/ordenes'), {
      status: 404,
    });
  }
  const db = dbOf(c);
  const orderRows = await db
    .select()
    .from(orders)
    .where(eq(orders.id, id))
    .limit(1);
  const order = orderRows[0];
  if (!order) {
    return render(c, 'Ordenes', notFoundBody('/admin/ordenes'), {
      status: 404,
    });
  }
  const me = c.get('user')!;
  const result = await approveOrder(db, order, me.id);
  if (!result.ok) {
    return render(c, 'Ordenes', ordenErrorBody(`/admin/ordenes/${id}`), {
      error: result.error,
    });
  }
  return c.redirect(`/admin/ordenes/${id}?aviso=aprobada`);
});

// Require MFA for rejecting orders
adminRoutes.post('/admin/ordenes/:id/rechazar', requireMFA(), async (c) => {
  const id = parseIdParam(c.req.param('id'));
  if (!id) {
    return render(c, 'Ordenes', notFoundBody('/admin/ordenes'), {
      status: 404,
    });
  }
  const db = dbOf(c);
  const orderRows = await db
    .select()
    .from(orders)
    .where(eq(orders.id, id))
    .limit(1);
  const order = orderRows[0];
  if (!order) {
    return render(c, 'Ordenes', notFoundBody('/admin/ordenes'), {
      status: 404,
    });
  }
  const body = await c.req.parseBody();
  const motivo = str(body.motivo);
  if (!motivo) {
    return render(c, 'Ordenes', ordenErrorBody(`/admin/ordenes/${id}`), {
      error: 'Ingresá el motivo del rechazo.',
    });
  }
  const result = await rejectOrder(db, order, motivo);
  if (!result.ok) {
    return render(c, 'Ordenes', ordenErrorBody(`/admin/ordenes/${id}`), {
      error: result.error,
    });
  }
  return c.redirect(`/admin/ordenes/${id}?aviso=rechazada`);
});

// ========================================================================
// MFA Admin Routes
// ========================================================================

/**
 * GET /admin/mfa/setup - Show MFA setup page with QR code
 */
adminRoutes.get('/admin/mfa/setup', requireRole('admin'), async (c) => {
  const user = c.get('user')!;
  const db = dbOf(c);

  const existing = await db
    .select()
    .from(mfaSecrets)
    .where(eq(mfaSecrets.userId, user.id))
    .limit(1);

  if (existing[0]?.enabled) {
    return c.redirect('/admin/mfa/setup?aviso=already_enabled');
  }

  // Generate a new secret if none exists
  let secret: string;
  if (existing[0]?.secret) {
    secret = await decryptSecret(existing[0].secret, c.env.JWT_SECRET || c.env.MFA_SECRET || 'default-secret-change-me');
  } else {
    secret = generateSecret();
  }

  // Generate QR code URI
  const serviceName = c.env.BRAND_NAME || 'LESCO GNC';
  const otpauth = generateURI({ label: user.email, issuer: serviceName, secret, algorithm: 'sha1', digits: 6, period: 30 });

  // Generate backup codes
  const backupCodes = Array.from({ length: 8 }, () =>
    Math.random().toString(36).substring(2, 10).toUpperCase()
  );

  // Store secret (encrypted) and backup codes (hashed) temporarily in session or return to user
  // For setup, we show the QR code and backup codes, user verifies with a code to enable
  const encryptedSecret = await encryptSecret(secret, c.env.JWT_SECRET || c.env.MFA_SECRET || 'default-secret-change-me');

  // Upsert mfa_secrets with the new secret (not enabled yet)
  await db
    .insert(mfaSecrets)
    .values({
      userId: user.id,
      secret: encryptedSecret,
      enabled: false,
      backupCodes: JSON.stringify(backupCodes),
    })
    .onConflictDoUpdate({
      target: mfaSecrets.userId,
      set: {
        secret: encryptedSecret,
        backupCodes: JSON.stringify(backupCodes),
        updatedAt: new Date(),
      },
    });

  const aviso = c.req.query('aviso');
  const notice = aviso === 'already_enabled' ? 'MFA ya está habilitado.' : undefined;
  const error = aviso === 'invalid_code' ? 'Código inválido. Intenta de nuevo.' : undefined;

  return c.html(
    page(
      {
        title: 'Configurar MFA (TOTP)',
        brand: brandOf(c),
        user,
        notice,
        error,
      },
      `<div class="card">
        <h1>Configurar Autenticación de Dos Factores (TOTP)</h1>
        <p class="muted">Escanea este código QR con tu app de autenticación (Google Authenticator, Authy, 1Password, etc.)</p>
        <div style="text-align:center;margin:1.5rem 0">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(otpauth)}" alt="QR Code para MFA" style="border:1px solid var(--paper-border);border-radius:8px;background:white;padding:1rem">
        </div>
        <p class="muted"><strong>Secreto manual:</strong> <code style="font-size:0.9rem;background:var(--paper-head);padding:0.25rem 0.5rem;border-radius:4px">${escapeHtml(secret)}</code></p>

        <div class="card" style="margin-top:1.5rem">
          <h2>Códigos de respaldo</h2>
          <p class="muted">Guarda estos códigos en un lugar seguro. Cada uno se puede usar una sola vez si pierdes acceso a tu app de autenticación.</p>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:0.5rem;margin-top:1rem">
            ${backupCodes.map((code) => `<code style="background:var(--paper-head);padding:0.5rem;border-radius:4px;display:block;text-align:center;font-size:1rem;letter-spacing:0.1em">${escapeHtml(code)}</code>`).join('')}
          </div>
        </div>

        <form method="post" action="/admin/mfa/setup" style="margin-top:1.5rem">
          <label for="totp-code">Código de 6 dígitos de tu app</label>
          <input id="totp-code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" placeholder="123456" style="text-align:center;font-size:1.5rem;letter-spacing:0.5em">
          <button type="submit" style="margin-top:1rem">Activar MFA</button>
          <a class="btn secondary" href="/admin" style="margin-top:1rem;display:inline-block">Cancelar</a>
        </form>
      </div>`,
    ),
  );
});

/**
 * POST /admin/mfa/setup - Verify TOTP code and enable MFA
 */
adminRoutes.post('/admin/mfa/setup', requireRole('admin'), async (c) => {
  const user = c.get('user')!;
  const db = dbOf(c);
  const body = await c.req.parseBody();
  const code = str(body.code);

  if (!/^\d{6}$/.test(code)) {
    return c.redirect('/admin/mfa/setup?aviso=invalid_code');
  }

  const mfa = await db
    .select()
    .from(mfaSecrets)
    .where(eq(mfaSecrets.userId, user.id))
    .limit(1);

  if (!mfa[0] || !mfa[0].secret) {
    return c.redirect('/admin/mfa/setup?aviso=invalid_code');
  }

  const secret = await decryptSecret(mfa[0].secret, c.env.JWT_SECRET || c.env.MFA_SECRET || 'default-secret-change-me');
  const verified = verify({ token: code, secret });

  if (!verified) {
    return c.redirect('/admin/mfa/setup?aviso=invalid_code');
  }

  // Enable MFA
  await db
    .update(mfaSecrets)
    .set({ enabled: true, updatedAt: new Date() })
    .where(eq(mfaSecrets.userId, user.id));

  return c.redirect('/admin/mfa/setup?aviso=enabled');
});

/**
 * GET /admin/mfa/verify - MFA verification page for sensitive actions
 */
adminRoutes.get('/admin/mfa/verify', requireRole('admin'), async (c) => {
  const user = c.get('user')!;
  const redirect = c.req.query('redirect') || '/admin';

  const db = dbOf(c);
  const mfa = await db
    .select()
    .from(mfaSecrets)
    .where(eq(mfaSecrets.userId, user.id))
    .limit(1);

  if (!mfa[0] || !mfa[0].enabled) {
    // MFA not enabled, allow through
    return c.redirect(redirect);
  }

  const error = c.req.query('error');

  return c.html(
    page(
      {
        title: 'Verificación MFA',
        brand: brandOf(c),
        user,
        error: error === 'invalid' ? 'Código inválido o expirado.' : undefined,
      },
      `<div class="card" style="max-width:24rem;margin:0 auto">
        <h1>Verificación de Dos Factores</h1>
        <p class="muted">Ingresa el código de 6 dígitos de tu app de autenticación para continuar.</p>
        <form method="post" action="/admin/mfa/verify">
          <input type="hidden" name="redirect" value="${escapeHtml(redirect)}">
          <label for="totp-code">Código</label>
          <input id="totp-code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" placeholder="123456" style="text-align:center;font-size:1.5rem;letter-spacing:0.5em">
          <button type="submit" style="margin-top:1rem;width:100%">Verificar</button>
        </form>
        <p class="muted" style="margin-top:1rem"><a href="/admin/mfa/verify/backup?redirect=${encodeURIComponent(redirect)}">Usar código de respaldo</a></p>
      </div>`,
    ),
  );
});

/**
 * POST /admin/mfa/verify - Verify TOTP code for sensitive action
 */
adminRoutes.post('/admin/mfa/verify', requireRole('admin'), async (c) => {
  const user = c.get('user')!;
  const body = await c.req.parseBody();
  const code = str(body.code);
  const redirect = str(body.redirect) || '/admin';

  if (!/^\d{6}$/.test(code)) {
    return c.redirect(`/admin/mfa/verify?redirect=${encodeURIComponent(redirect)}&error=invalid`);
  }

  const db = dbOf(c);
  const mfa = await db
    .select()
    .from(mfaSecrets)
    .where(eq(mfaSecrets.userId, user.id))
    .limit(1);

  if (!mfa[0] || !mfa[0].enabled || !mfa[0].secret) {
    return c.redirect(`/admin/mfa/verify?redirect=${encodeURIComponent(redirect)}&error=invalid`);
  }

  const secret = await decryptSecret(mfa[0].secret, c.env.JWT_SECRET || c.env.MFA_SECRET || 'default-secret-change-me');
  const verified = verify({ token: code, secret });

  if (!verified) {
    // Check backup codes
    const backupCodes = JSON.parse(mfa[0].backupCodes || '[]') as string[];
    const codeUpper = code.toUpperCase();
    const idx = backupCodes.indexOf(codeUpper);
    if (idx !== -1) {
      // Valid backup code - remove it and allow
      backupCodes.splice(idx, 1);
      await db
        .update(mfaSecrets)
        .set({ backupCodes: JSON.stringify(backupCodes), updatedAt: new Date() })
        .where(eq(mfaSecrets.userId, user.id));
      c.set('mfaVerified', true);
      return c.redirect(redirect);
    }
    return c.redirect(`/admin/mfa/verify?redirect=${encodeURIComponent(redirect)}&error=invalid`);
  }

  // Set MFA verified flag in context for requireMFA middleware
  c.set('mfaVerified', true);
  return c.redirect(redirect);
});

/**
 * GET /admin/mfa/verify/backup - Backup code verification page
 */
adminRoutes.get('/admin/mfa/verify/backup', requireRole('admin'), async (c) => {
  const redirect = c.req.query('redirect') || '/admin';
  const error = c.req.query('error');

  return c.html(
    page(
      {
        title: 'Código de Respaldo MFA',
        brand: brandOf(c),
        user: c.get('user'),
        error: error === 'invalid' ? 'Código de respaldo inválido o ya usado.' : undefined,
      },
      `<div class="card" style="max-width:24rem;margin:0 auto">
        <h1>Código de Respaldo</h1>
        <p class="muted">Ingresa uno de tus códigos de respaldo para continuar.</p>
        <form method="post" action="/admin/mfa/verify/backup">
          <input type="hidden" name="redirect" value="${escapeHtml(redirect)}">
          <label for="backup-code">Código de respaldo</label>
          <input id="backup-code" name="code" type="text" required autocomplete="one-time-code" placeholder="ABC123XY" style="text-align:center;font-size:1.2rem;letter-spacing:0.2em;text-transform:uppercase">
          <button type="submit" style="margin-top:1rem;width:100%">Verificar</button>
        </form>
        <p class="muted" style="margin-top:1rem"><a href="/admin/mfa/verify?redirect=${encodeURIComponent(redirect)}">Volver a app de autenticación</a></p>
      </div>`,
    ),
  );
});

/**
 * POST /admin/mfa/verify/backup - Verify backup code
 */
adminRoutes.post('/admin/mfa/verify/backup', requireRole('admin'), async (c) => {
  const user = c.get('user')!;
  const body = await c.req.parseBody();
  const code = str(body.code).toUpperCase();
  const redirect = str(body.redirect) || '/admin';

  const db = dbOf(c);
  const mfa = await db
    .select()
    .from(mfaSecrets)
    .where(eq(mfaSecrets.userId, user.id))
    .limit(1);

  if (!mfa[0] || !mfa[0].enabled) {
    return c.redirect(`/admin/mfa/verify/backup?redirect=${encodeURIComponent(redirect)}&error=invalid`);
  }

  const backupCodes = JSON.parse(mfa[0].backupCodes || '[]') as string[];
  const idx = backupCodes.indexOf(code);

  if (idx === -1) {
    return c.redirect(`/admin/mfa/verify/backup?redirect=${encodeURIComponent(redirect)}&error=invalid`);
  }

  // Remove used backup code
  backupCodes.splice(idx, 1);
  await db
    .update(mfaSecrets)
    .set({ backupCodes: JSON.stringify(backupCodes), updatedAt: new Date() })
    .where(eq(mfaSecrets.userId, user.id));

  c.set('mfaVerified', true);
  return c.redirect(redirect);
});

/**
 * POST /admin/mfa/disable - Disable MFA (requires current code)
 */
adminRoutes.post('/admin/mfa/disable', requireRole('admin'), async (c) => {
  const user = c.get('user')!;
  const body = await c.req.parseBody();
  const code = str(body.code);

  if (!/^\d{6}$/.test(code)) {
    return c.redirect('/admin/mfa/setup?aviso=invalid_disable_code');
  }

  const db = dbOf(c);
  const mfa = await db
    .select()
    .from(mfaSecrets)
    .where(eq(mfaSecrets.userId, user.id))
    .limit(1);

  if (!mfa[0] || !mfa[0].enabled || !mfa[0].secret) {
    return c.redirect('/admin/mfa/setup?aviso=invalid_disable_code');
  }

  const secret = await decryptSecret(mfa[0].secret, c.env.JWT_SECRET || c.env.MFA_SECRET || 'default-secret-change-me');
  const verified = verify({ token: code, secret });

  if (!verified) {
    // Check backup codes
    const backupCodes = JSON.parse(mfa[0].backupCodes || '[]') as string[];
    const codeUpper = code.toUpperCase();
    const idx = backupCodes.indexOf(codeUpper);
    if (idx === -1) {
      return c.redirect('/admin/mfa/setup?aviso=invalid_disable_code');
    }
    backupCodes.splice(idx, 1);
    await db
      .update(mfaSecrets)
      .set({ backupCodes: JSON.stringify(backupCodes), updatedAt: new Date() })
      .where(eq(mfaSecrets.userId, user.id));
  }

  // Disable MFA
  await db
    .update(mfaSecrets)
    .set({ enabled: false, updatedAt: new Date() })
    .where(eq(mfaSecrets.userId, user.id));

  return c.redirect('/admin/mfa/setup?aviso=disabled');
});

// ========================================================================
// Audit Log Routes
// ========================================================================

/**
 * GET /admin/audit-logs - List audit logs with filters
 */
adminRoutes.get('/admin/audit-logs', requireRole('admin'), async (c) => {
  const db = dbOf(c);

  const userIdRaw = Number(str(c.req.query('userId')));
  const userId = Number.isInteger(userIdRaw) && userIdRaw > 0 ? userIdRaw : null;

  const action = str(c.req.query('action'));
  const resource = str(c.req.query('resource'));
  const dateFromRaw = str(c.req.query('dateFrom'));
  const dateToRaw = str(c.req.query('dateTo'));
  const pageRaw = Number(str(c.req.query('page')));
  const pageNum = Number.isInteger(pageRaw) && pageRaw > 0 ? pageRaw : 1;
  const PAGE_SIZE = 50;

  const conds: SQL[] = [];
  if (userId) conds.push(eq(auditLogs.userId, userId));
  if (action) conds.push(like(auditLogs.action, `%${action}%`));
  if (resource) conds.push(eq(auditLogs.resource, resource));
  if (dateFromRaw) {
    const dateFrom = new Date(dateFromRaw);
    if (!Number.isNaN(dateFrom.getTime())) {
      conds.push(sql`${auditLogs.createdAt} >= ${dateFrom.getTime()}`);
    }
  }
  if (dateToRaw) {
    const dateTo = new Date(dateToRaw);
    dateTo.setHours(23, 59, 59, 999);
    if (!Number.isNaN(dateTo.getTime())) {
      conds.push(sql`${auditLogs.createdAt} <= ${dateTo.getTime()}`);
    }
  }

  const where = conds.length > 0 ? and(...conds) : undefined;

  const totalRows = await db
    .select({ n: count() })
    .from(auditLogs)
    .where(where);
  const total = totalRows[0]?.n ?? 0;
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

  const rows = await db
    .select({
      id: auditLogs.id,
      userId: auditLogs.userId,
      action: auditLogs.action,
      resource: auditLogs.resource,
      resourceId: auditLogs.resourceId,
      metadata: auditLogs.metadata,
      ip: auditLogs.ip,
      userAgent: auditLogs.userAgent,
      createdAt: auditLogs.createdAt,
      userName: users.name,
      userEmail: users.email,
    })
    .from(auditLogs)
    .innerJoin(users, eq(auditLogs.userId, users.id))
    .where(where)
    .orderBy(desc(auditLogs.createdAt), desc(auditLogs.id))
    .limit(PAGE_SIZE)
    .offset((pageNum - 1) * PAGE_SIZE);

  // Get list of users for filter dropdown
  const userRows = await db
    .select({ id: users.id, name: users.name, email: users.email })
    .from(users)
    .orderBy(users.name);

  const bodyRows = rows
    .map(
      (log) => `<tr>
        <td style="${TD}">${fmtFecha(log.createdAt)}</td>
        <td style="${TD}">${escapeHtml(log.userName)}<br><span class="muted">${escapeHtml(log.userEmail)}</span></td>
        <td style="${TD}"><code style="font-size:0.8rem">${escapeHtml(log.action)}</code></td>
        <td style="${TD}">${escapeHtml(log.resource)}${log.resourceId ? ` #${log.resourceId}` : ''}</td>
        <td style="${TD}"><pre style="margin:0;font-size:0.7rem;max-height:8rem;overflow:auto">${escapeHtml(log.metadata ?? '{}')}</pre></td>
        <td style="${TD}">${escapeHtml(log.ip ?? 'unknown')}</td>
      </tr>`,
    )
    .join('');

  const userOptions = userRows
    .map(
      (u) => `<option value="${u.id}"${u.id === userId ? ' selected' : ''}>${escapeHtml(u.name)} (${escapeHtml(u.email)})</option>`,
    )
    .join('');

  const resourceOptions = ['usuarios', 'tienda', 'desafios', 'ordenes', 'productos', 'mfa', 'audit-logs']
    .map((r) => `<option value="${r}"${r === resource ? ' selected' : ''}>${r}</option>`)
    .join('');

  const filtros: Record<string, string> = {};
  if (userId) filtros.userId = String(userId);
  if (action) filtros.action = action;
  if (resource) filtros.resource = resource;
  if (dateFromRaw) filtros.dateFrom = dateFromRaw;
  if (dateToRaw) filtros.dateTo = dateToRaw;

  const qs = new URLSearchParams(filtros).toString();
  const basePath = `/admin/audit-logs${qs ? `?${qs}` : ''}`;

  const paginacion =
    totalPages > 1
      ? `<div class="row" style="margin-top:0.75rem">
          ${pageNum > 1 ? `<a class="btn secondary" href="${basePath}&page=${pageNum - 1}" style="${SMALL_BTN}">Anterior</a>` : ''}
          <span class="muted">Página ${pageNum} de ${totalPages}</span>
          ${pageNum < totalPages ? `<a class="btn secondary" href="${basePath}&page=${pageNum + 1}" style="${SMALL_BTN}">Siguiente</a>` : ''}
        </div>`
      : '';

  return c.html(
    page(
      {
        title: 'Log de Auditoría',
        brand: brandOf(c),
        user: c.get('user'),
      },
      `<div class="card">
        <h1>Log de Auditoría</h1>
        <p class="muted">Registro de acciones administrativas (solo POST, PUT, DELETE, PATCH en /admin/*)</p>
        <form method="get" action="/admin/audit-logs" class="row" style="margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem">
          <select name="userId" style="width:auto;min-width:12rem">
            <option value="">Todos los usuarios</option>
            ${userOptions}
          </select>
          <select name="resource" style="width:auto;min-width:10rem">
            <option value="">Todos los recursos</option>
            ${resourceOptions}
          </select>
          <input type="search" name="action" value="${escapeHtml(action)}" placeholder="Acción (método + ruta)" style="flex:1;min-width:12rem">
          <input type="date" name="dateFrom" value="${escapeHtml(dateFromRaw)}" style="width:auto">
          <input type="date" name="dateTo" value="${escapeHtml(dateToRaw)}" style="width:auto">
          <button type="submit" style="${SMALL_BTN}">Filtrar</button>
          <a class="btn secondary" href="/admin/audit-logs" style="${SMALL_BTN}">Limpiar</a>
        </form>
        <p class="muted">${total} registro${total === 1 ? '' : 's'}</p>
        <div style="overflow-x:auto">
          <table class="papel" style="margin-top:0.5rem">
            <thead><tr>
              <th style="${TH}">Fecha</th>
              <th style="${TH}">Usuario</th>
              <th style="${TH}">Acción</th>
              <th style="${TH}">Recurso</th>
              <th style="${TH}">Metadata</th>
              <th style="${TH}">IP</th>
            </tr></thead>
            <tbody>${bodyRows || `<tr><td colspan="6" style="${TD}" class="muted">No hay registros para mostrar.</td></tr>`}</tbody>
          </table>
        </div>
        ${paginacion}
      </div>`,
    ),
  );
});
