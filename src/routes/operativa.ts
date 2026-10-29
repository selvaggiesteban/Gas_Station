import { Hono } from 'hono';
import type { Context } from 'hono';
import { and, asc, desc, eq, gte, lt, sql } from 'drizzle-orm';
import type { AppEnv } from '../env';
import type { SessionUser } from '../lib/session';
import { dbOf, brandOf, requireAuth, requireRole } from '../middleware/auth';
import { page, escapeHtml } from '../lib/html';
import type { Db } from '../db';
import {
  FUEL_PRODUCT_VALUES,
  SHIFT_VALUES,
  cashDays,
  dailyActivities,
  lubricantStocks,
  products,
  raffleCoupons,
  salesSummaries,
  shiftReportReadings,
  shiftReports,
  stores,
} from '../db/schema';
import { ensureDefaultStore } from '../services/orders';

export const operativaRoutes = new Hono<AppEnv>();

type Shift = (typeof SHIFT_VALUES)[number];
type Fuel = (typeof FUEL_PRODUCT_VALUES)[number];

const TH =
  'text-align:left;padding:0.5rem 0.75rem;border:1px solid var(--paper-border);font-weight:700;text-align:left;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.05em;background:var(--paper-head)';
const TD =
  'padding:0.5rem 0.75rem;border:1px solid var(--paper-border);font-size:0.9rem;vertical-align:top';
const TD_NUM =
  'padding:0.5rem 0.75rem;border:1px solid var(--paper-border);font-size:0.9rem;vertical-align:top;text-align:right;font-variant-numeric:tabular-nums';
const SMALL_BTN = 'margin-top:0;padding:0.35rem 0.7rem';

const NO_DATA = 'Sin registros todavía.';

const SHIFT_LABELS: Record<Shift, string> = {
  morning: 'Mañana',
  evening: 'Tarde',
  night: 'Noche',
};

const FUEL_LABELS: Record<Fuel, string> = {
  nafta: 'Nafta',
  gasoil: 'Gasoil',
  diesel: 'Diesel',
  gnc: 'GNC',
};

const DAY_FMT = new Intl.DateTimeFormat('es-AR', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  timeZone: 'UTC',
});

const MOMENT_FMT = new Intl.DateTimeFormat('es-AR', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
  timeZone: 'America/Argentina/Buenos_Aires',
});

function str(v: unknown): string {
  return typeof v === 'string' ? v.trim() : '';
}

function idFrom(v: unknown): number | null {
  const n = Number(str(v));
  return Number.isInteger(n) && n > 0 ? n : null;
}

function isShift(v: string): v is Shift {
  return (SHIFT_VALUES as readonly string[]).includes(v);
}

function isFuel(v: string): v is Fuel {
  return (FUEL_PRODUCT_VALUES as readonly string[]).includes(v);
}

function centsFrom(v: unknown): number | null {
  const s = str(v);
  if (s === '') return null;
  const n = Number(s);
  if (!Number.isFinite(n) || n < 0) return null;
  return Math.round(n * 100);
}

function centsOr0(v: unknown): number | null {
  const s = str(v);
  if (s === '') return 0;
  return centsFrom(s);
}

function qtyFrom(v: unknown): number | null {
  const s = str(v);
  if (s === '') return 0;
  const n = Number(s);
  if (!Number.isFinite(n) || n < 0) return null;
  return Math.round(n);
}

function floatFrom(v: unknown): number | null {
  const s = str(v);
  if (s === '') return 0;
  const n = Number(s);
  if (!Number.isFinite(n) || n < 0) return null;
  return n;
}

function dayStart(raw: string): Date | null {
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(raw);
  if (!m) return null;
  const y = Number(m[1]);
  const mo = Number(m[2]);
  const da = Number(m[3]);
  if (mo < 1 || mo > 12 || da < 1 || da > 31) return null;
  const d = new Date(Date.UTC(y, mo - 1, da));
  return Number.isNaN(d.getTime()) ? null : d;
}

function nextDay(d: Date): Date {
  return new Date(d.getTime() + 24 * 3600 * 1000);
}

function dtFrom(v: unknown): Date | null {
  const s = str(v);
  if (!s) return null;
  const d = new Date(s);
  return Number.isNaN(d.getTime()) ? null : d;
}

function pad2(n: number): string {
  return String(n).padStart(2, '0');
}

function todayInput(): string {
  const d = new Date();
  return `${d.getUTCFullYear()}-${pad2(d.getUTCMonth() + 1)}-${pad2(d.getUTCDate())}`;
}

function fmtDay(d: Date): string {
  return DAY_FMT.format(d);
}

function fmtMoment(d: Date | null): string {
  return d ? MOMENT_FMT.format(d) : '—';
}

function formatArs(cents: number): string {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  }).format(cents / 100);
}

function render(
  c: Context<AppEnv>,
  title: string,
  body: string,
  opts: {
    error?: string;
    notice?: string;
    status?: 200 | 403 | 404 | 422;
  } = {},
): Response {
  return c.html(
    page(
      {
        title,
        brand: brandOf(c),
        user: c.get('user'),
        error: opts.error,
        notice: opts.notice,
      },
      body,
    ),
    opts.status ?? (opts.error ? 422 : 200),
  );
}

function aviso(c: Context<AppEnv>, message: string): string | undefined {
  return c.req.query('aviso') ? message : undefined;
}

function fail(
  c: Context<AppEnv>,
  title: string,
  message: string,
  back: string,
): Response {
  return render(
    c,
    title,
    `<div class="card">
      <h1>No se pudo guardar</h1>
      <p class="muted">Revisá los datos que enviaste e intentá de nuevo.</p>
      <a class="btn secondary" href="${back}">Volver</a>
    </div>`,
    { error: message, status: 422 },
  );
}

function denied(c: Context<AppEnv>, message: string): Response {
  return render(
    c,
    'Sin permiso',
    `<div class="card">
      <h1>No podés operar esa tienda</h1>
      <p class="muted">Si creés que es un error, contactate con el administrador.</p>
      <a class="btn" href="/operativa">Volver a operativa</a>
    </div>`,
    { error: message, status: 403 },
  );
}

function notFound(c: Context<AppEnv>, title: string, back: string): Response {
  return render(
    c,
    title,
    `<div class="card">
      <h1>No encontrado</h1>
      <p class="muted">El registro pedido no existe.</p>
      <a class="btn secondary" href="${back}">Volver</a>
    </div>`,
    { error: 'No encontramos ese registro.', status: 404 },
  );
}

type StoreResult = { storeId: number } | { error: string };

async function resolveStore(
  _c: Context<AppEnv>,
  db: Db,
  _raw: unknown,
): Promise<StoreResult> {
  return { storeId: await ensureDefaultStore(db) };
}

async function canManageStore(
  _db: Db,
  user: SessionUser,
  _storeId: number,
): Promise<boolean> {
  return user.role === 'admin' || user.role === 'store_manager';
}

async function storeOptions(db: Db, selected: number): Promise<string> {
  const rows = await db
    .select({ id: stores.id, name: stores.name })
    .from(stores)
    .orderBy(asc(stores.name));
  return rows
    .map(
      (s) =>
        `<option value="${s.id}"${s.id === selected ? ' selected' : ''}>${escapeHtml(s.name)}</option>`,
    )
    .join('');
}

function shiftOptions(selected?: string): string {
  return SHIFT_VALUES.map(
    (s) =>
      `<option value="${s}"${s === selected ? ' selected' : ''}>${SHIFT_LABELS[s]}</option>`,
  ).join('');
}

function fuelOptions(selected?: string): string {
  return FUEL_PRODUCT_VALUES.map(
    (f) =>
      `<option value="${f}"${f === selected ? ' selected' : ''}>${FUEL_LABELS[f]}</option>`,
  ).join('');
}

function filterBar(path: string, fecha: string, storesHtml: string): string {
  return `<form method="get" action="${path}" class="row">
    <input type="date" name="fecha" value="${escapeHtml(fecha)}" style="width:auto;margin-top:0" aria-label="Fecha">
    <select name="tienda" style="width:auto;margin-top:0" aria-label="Tienda">${storesHtml}</select>
    <button type="submit" style="${SMALL_BTN}">Filtrá</button>
  </form>`;
}

operativaRoutes.use(
  '/operativa',
  requireAuth(),
  requireRole('admin', 'store_manager'),
);
operativaRoutes.use(
  '/operativa/*',
  requireAuth(),
  requireRole('admin', 'store_manager'),
);

operativaRoutes.get('/operativa', async (c) => {
  const db = dbOf(c);
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const storeId = access.storeId;
  const storeRows = await db
    .select({ name: stores.name })
    .from(stores)
    .where(eq(stores.id, storeId))
    .limit(1);
  const storeName = storeRows[0]?.name ?? '—';

  const [cash, sales, daily, lube, shift, raf] = await Promise.all([
    db
      .select()
      .from(cashDays)
      .where(eq(cashDays.storeId, storeId))
      .orderBy(desc(cashDays.reportDate), desc(cashDays.id))
      .limit(1),
    db
      .select()
      .from(salesSummaries)
      .where(eq(salesSummaries.storeId, storeId))
      .orderBy(desc(salesSummaries.reportDate), desc(salesSummaries.id))
      .limit(1),
    db
      .select()
      .from(dailyActivities)
      .where(eq(dailyActivities.storeId, storeId))
      .orderBy(desc(dailyActivities.reportDate), desc(dailyActivities.id))
      .limit(1),
    db
      .select()
      .from(lubricantStocks)
      .where(eq(lubricantStocks.storeId, storeId))
      .orderBy(desc(lubricantStocks.reportDate), desc(lubricantStocks.id))
      .limit(1),
    db
      .select()
      .from(shiftReports)
      .where(eq(shiftReports.storeId, storeId))
      .orderBy(desc(shiftReports.reportDate), desc(shiftReports.id))
      .limit(1),
    db
      .select()
      .from(raffleCoupons)
      .where(eq(raffleCoupons.storeId, storeId))
      .orderBy(desc(raffleCoupons.issuedAt), desc(raffleCoupons.id))
      .limit(1),
  ]);

  const indexCard = (
    title: string,
    desc: string,
    href: string,
    summary: string,
  ): string => `<div class="card">
    <h2>${title}</h2>
    <p class="muted">${desc}</p>
    <p>${summary}</p>
    <a class="btn secondary" href="${href}" style="margin-top:0.25rem">Abrir módulo</a>
  </div>`;

  const cards = [
    indexCard(
      'Caja diaria',
      'Apertura y cierre de caja por día y tienda.',
      '/operativa/caja',
      cash[0]
        ? `${fmtDay(cash[0].reportDate)} · ${cash[0].status === 'open' ? 'Abierta' : 'Cerrada'} · Apertura ${formatArs(cash[0].openingCents)}${cash[0].differenceCents !== null ? ` · Diferencia ${formatArs(cash[0].differenceCents)}` : ''}`
        : NO_DATA,
    ),
    indexCard(
      'Resumen de ventas (ensobrado)',
      'Totales por medio de pago, por día y turno.',
      '/operativa/ventas',
      sales[0]
        ? `${fmtDay(sales[0].reportDate)} · Turno ${SHIFT_LABELS[sales[0].shift]} · Total ${formatArs(sales[0].totalCents)}`
        : NO_DATA,
    ),
    indexCard(
      'Resumen diario de actividades',
      'Matriz de GNC y revisión de bombas por turno.',
      '/operativa/actividades',
      daily[0]
        ? `${fmtDay(daily[0].reportDate)} · Turno ${SHIFT_LABELS[daily[0].shift]} · GNC ${daily[0].gncVolume} m³`
        : NO_DATA,
    ),
    indexCard(
      'Stock de lubricantes',
      'Reconteo de envases de aceite por tienda.',
      '/operativa/lubricantes',
      lube[0]
        ? `${fmtDay(lube[0].reportDate)} · ${escapeHtml(lube[0].brand)} ${escapeHtml(lube[0].grade)} · Cierre ${lube[0].closingQty}`
        : NO_DATA,
    ),
    indexCard(
      'Rendición de turno',
      'Lecturas de surtidores y cierre de turno.',
      '/operativa/rendicion',
      shift[0]
        ? `${fmtDay(shift[0].reportDate)} · Turno ${SHIFT_LABELS[shift[0].shift]} · Cierre ${formatArs(shift[0].closingCents)}`
        : NO_DATA,
    ),
    indexCard(
      'Cupón de sorteo',
      'Emisión de códigos y marcado de ganadores.',
      '/operativa/sorteo',
      raf[0]
        ? `Último: ${escapeHtml(raf[0].code)} · ${raf[0].winning ? 'Ganador' : 'Emitido'} · ${fmtMoment(raf[0].issuedAt)}`
        : NO_DATA,
    ),
  ].join('');

  return render(
    c,
    'Operativa',
    `<div class="card">
      <h1>Operativa diaria</h1>
      <p class="muted">Seis formularios de la estación, ahora digitales. Tienda activa: <strong>${escapeHtml(storeName)}</strong>.</p>
      <a class="btn secondary" href="/app">Volver al inicio</a>
    </div>
    ${cards}`,
  );
});

operativaRoutes.get('/operativa/caja', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const fecha = str(c.req.query('fecha'));
  const day = fecha ? dayStart(fecha) : null;
  if (fecha && !day) {
    return fail(
      c,
      'Caja diaria',
      'Ingresá una fecha válida.',
      '/operativa/caja',
    );
  }
  const rows = await db
    .select()
    .from(cashDays)
    .where(
      and(
        eq(cashDays.storeId, access.storeId),
        ...(day
          ? [
              gte(cashDays.reportDate, day),
              lt(cashDays.reportDate, nextDay(day)),
            ]
          : []),
      ),
    )
    .orderBy(desc(cashDays.reportDate), desc(cashDays.id))
    .limit(50);

  const bodyRows = rows
    .map((r) => {
      const cerrar =
        r.status === 'open'
          ? `<details style="display:inline">
              <summary style="cursor:pointer;font-size:0.85rem">Cerrá</summary>
              <form method="post" action="/operativa/caja/${r.id}/cerrar" style="min-width:14rem">
                <label for="decl-${r.id}">Efectivo declarado (pesos)</label>
                <input id="decl-${r.id}" name="declarado" type="number" step="0.01" min="0" required>
                <label for="cnt-${r.id}">Efectivo contado (pesos)</label>
                <input id="cnt-${r.id}" name="contado" type="number" step="0.01" min="0" required>
                <label for="cnotas-${r.id}">Notas de cierre</label>
                <input id="cnotas-${r.id}" name="notas" type="text">
                <button type="submit" style="${SMALL_BTN}">Cerrá la caja</button>
              </form>
            </details>`
          : '';
      return `<tr>
        <td style="${TD}">${fmtDay(r.reportDate)}</td>
        <td style="${TD}"><span class="pill"${r.status === 'closed' ? ' style="background:#57534e"' : ''}>${r.status === 'open' ? 'Abierta' : 'Cerrada'}</span></td>
        <td style="${TD}">${formatArs(r.openingCents)}</td>
        <td style="${TD}">${r.closingDeclaredCents !== null ? formatArs(r.closingDeclaredCents) : '—'}</td>
        <td style="${TD}">${r.closingCountedCents !== null ? formatArs(r.closingCountedCents) : '—'}</td>
        <td style="${TD}">${r.differenceCents !== null ? formatArs(r.differenceCents) : '—'}</td>
        <td style="${TD}">${cerrar}</td>
      </tr>`;
    })
    .join('');

  const storesHtml = await storeOptions(db, access.storeId);

  return render(
    c,
    'Caja diaria',
    `<div class="card">
      <h1>Caja diaria</h1>
      <p class="muted">Una caja por tienda y día. Al cerrar, la diferencia se calcula como contado menos declarado.</p>
      ${filterBar('/operativa/caja', fecha, storesHtml)}
      <a class="btn" href="/operativa/caja/nuevo">Cargá la apertura</a>
      <table class="papel" style="margin-top:0.75rem">
        <thead><tr>
          <th style="${TH}">Fecha</th>
          <th style="${TH}">Estado</th>
          <th style="${TH}">Apertura</th>
          <th style="${TH}">Declarado</th>
          <th style="${TH}">Contado</th>
          <th style="${TH}">Diferencia</th>
          <th style="${TH}">Acciones</th>
        </tr></thead>
        <tbody>${bodyRows || `<tr><td colspan="7" style="${TD}" class="muted">${NO_DATA}</td></tr>`}</tbody>
      </table>
    </div>`,
    { notice: aviso(c, 'Rendición guardada.') },
  );
});

function randomRaffleCode(): string {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  const bytes = crypto.getRandomValues(new Uint8Array(8));
  let out = '';
  for (const b of bytes) out += chars[b % chars.length];
  return out;
}

operativaRoutes.get('/operativa/sorteo', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const rows = await db
    .select()
    .from(raffleCoupons)
    .where(eq(raffleCoupons.storeId, access.storeId))
    .orderBy(desc(raffleCoupons.issuedAt), desc(raffleCoupons.id))
    .limit(100);

  const bodyRows = rows
    .map(
      (r) => `<tr>
        <td style="${TD}"><strong>${escapeHtml(r.code)}</strong></td>
        <td style="${TD}">${escapeHtml(r.holderName)}</td>
        <td style="${TD}">${escapeHtml(r.holderEmail ?? '—')}</td>
        <td style="${TD}">${fmtMoment(r.issuedAt)}</td>
        <td style="${TD}">${r.winning ? `<span class="pill">Ganador</span>${r.drawnAt ? ` <span class="muted">${fmtMoment(r.drawnAt)}</span>` : ''}` : '<span class="muted">Emitido</span>'}</td>
        <td style="${TD}">
          <details style="display:inline">
            <summary style="cursor:pointer;font-size:0.85rem">Editá</summary>
            <form method="post" action="/operativa/sorteo/${r.id}/editar" style="min-width:14rem">
              <label for="sn-${r.id}">Nombre de quien recibe</label>
              <input id="sn-${r.id}" name="nombre" type="text" required value="${escapeHtml(r.holderName)}">
              <label for="se-${r.id}">Email</label>
              <input id="se-${r.id}" name="email" type="text" value="${escapeHtml(r.holderEmail ?? '')}">
              <label for="sp-${r.id}">Teléfono</label>
              <input id="sp-${r.id}" name="telefono" type="text" value="${escapeHtml(r.holderPhone ?? '')}">
              <label for="snotas-${r.id}">Notas</label>
              <input id="snotas-${r.id}" name="notas" type="text" value="${escapeHtml(r.notes ?? '')}">
              <button type="submit" style="${SMALL_BTN}">Guardá</button>
            </form>
          </details>
          ${r.winning ? '' : `<form method="post" action="/operativa/sorteo/${r.id}/ganador" style="display:inline">
            <button type="submit" class="secondary" style="${SMALL_BTN}">Marcar ganador</button>
          </form>`}
        </td>
      </tr>`,
    )
    .join('');

  const storesHtml = await storeOptions(db, access.storeId);
  const ganadores = rows.filter((r) => r.winning).length;

  return render(
    c,
    'Cupón de sorteo',
    `<div class="card">
      <h1>Cupón de sorteo</h1>
      <p class="muted">Los códigos se generan en el servidor y son únicos. Emitidos: ${rows.length} · Ganadores: ${ganadores}.</p>
      ${filterBar('/operativa/sorteo', '', storesHtml)}
      <a class="btn" href="/operativa/sorteo/nuevo">Emití cupones</a>
      <table class="papel" style="margin-top:0.75rem">
        <thead><tr>
          <th style="${TH}">Código</th>
          <th style="${TH}">Titular</th>
          <th style="${TH}">Email</th>
          <th style="${TH}">Emitido</th>
          <th style="${TH}">Estado</th>
          <th style="${TH}">Acciones</th>
        </tr></thead>
        <tbody>${bodyRows || `<tr><td colspan="6" style="${TD}" class="muted">${NO_DATA}</td></tr>`}</tbody>
      </table>
    </div>`,
    { notice: aviso(c, 'Operación sobre cupones realizada.') },
  );
});

operativaRoutes.get('/operativa/sorteo/nuevo', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const storesHtml = await storeOptions(db, access.storeId);
  return render(
    c,
    'Emitir cupones',
    `<div class="card">
      <h1>Emitir cupones de sorteo</h1>
      <form method="post" action="/operativa/sorteo">
        <label for="tienda">Tienda</label>
        <select id="tienda" name="tienda">${storesHtml}</select>
        <label for="cantidad">Cantidad de cupones (1 a 50)</label>
        <input id="cantidad" name="cantidad" type="number" step="1" min="1" max="50" value="1" required>
        <label for="nombre">Nombre de quien recibe</label>
        <input id="nombre" name="nombre" type="text" required minlength="2">
        <label for="email">Email (opcional)</label>
        <input id="email" name="email" type="text">
        <label for="telefono">Teléfono (opcional)</label>
        <input id="telefono" name="telefono" type="text">
        <label for="notas">Notas (opcional)</label>
        <input id="notas" name="notas" type="text">
        <button type="submit">Generá los códigos</button>
      </form>
      <p><a href="/operativa/sorteo">Volver al listado</a></p>
    </div>`,
  );
});

operativaRoutes.post('/operativa/sorteo', async (c) => {
  const db = dbOf(c);
  const body = await c.req.parseBody();
  const access = await resolveStore(c, db, body.tienda);
  if ('error' in access) return denied(c, access.error);
  const cantidad = qtyFrom(body.cantidad);
  if (cantidad === null || cantidad < 1 || cantidad > 50) {
    return fail(
      c,
      'Cupón de sorteo',
      'La cantidad de cupones debe estar entre 1 y 50.',
      '/operativa/sorteo/nuevo',
    );
  }
  const nombre = str(body.nombre);
  if (nombre.length < 2) {
    return fail(
      c,
      'Cupón de sorteo',
      'Ingresá el nombre de quien recibe el cupón.',
      '/operativa/sorteo/nuevo',
    );
  }
  const email = str(body.email);
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return fail(
      c,
      'Cupón de sorteo',
      'Ingresá un email válido o dejalo vacío.',
      '/operativa/sorteo/nuevo',
    );
  }
  const telefono = str(body.telefono);
  const notas = str(body.notas);
  const codes = new Set<string>();
  while (codes.size < cantidad) {
    const code = randomRaffleCode();
    const exists = await db
      .select({ code: raffleCoupons.code })
      .from(raffleCoupons)
      .where(eq(raffleCoupons.code, code))
      .limit(1);
    if (!exists[0]) codes.add(code);
  }
  await db.insert(raffleCoupons).values(
    [...codes].map((code) => ({
      code,
      storeId: access.storeId,
      holderName: nombre,
      holderEmail: email || null,
      holderPhone: telefono || null,
      notes: notas || null,
    })),
  );
  return c.redirect('/operativa/sorteo?aviso=1');
});

operativaRoutes.post('/operativa/sorteo/:id/ganador', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const id = idFrom(c.req.param('id'));
  if (!id) return notFound(c, 'Cupón de sorteo', '/operativa/sorteo');
  const rows = await db
    .select()
    .from(raffleCoupons)
    .where(eq(raffleCoupons.id, id))
    .limit(1);
  const row = rows[0];
  if (!row) return notFound(c, 'Cupón de sorteo', '/operativa/sorteo');
  if (!(await canManageStore(db, user, row.storeId))) {
    return denied(c, 'No tenés permiso sobre la tienda de ese cupón.');
  }
  if (row.winning) {
    return fail(
      c,
      'Cupón de sorteo',
      'Este cupón ya estaba marcado como ganador.',
      '/operativa/sorteo',
    );
  }
  await db
    .update(raffleCoupons)
    .set({ winning: true, drawnAt: new Date(), updatedAt: new Date() })
    .where(eq(raffleCoupons.id, id));
  return c.redirect('/operativa/sorteo?aviso=1');
});

operativaRoutes.post('/operativa/sorteo/:id/editar', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const id = idFrom(c.req.param('id'));
  if (!id) return notFound(c, 'Cupón de sorteo', '/operativa/sorteo');
  const rows = await db
    .select()
    .from(raffleCoupons)
    .where(eq(raffleCoupons.id, id))
    .limit(1);
  const row = rows[0];
  if (!row) return notFound(c, 'Cupón de sorteo', '/operativa/sorteo');
  if (!(await canManageStore(db, user, row.storeId))) {
    return denied(c, 'No tenés permiso sobre la tienda de ese cupón.');
  }
  const body = await c.req.parseBody();
  const nombre = str(body.nombre);
  if (nombre.length < 2) {
    return fail(
      c,
      'Cupón de sorteo',
      'Ingresá el nombre de quien recibe el cupón.',
      '/operativa/sorteo',
    );
  }
  const email = str(body.email);
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return fail(
      c,
      'Cupón de sorteo',
      'Ingresá un email válido o dejalo vacío.',
      '/operativa/sorteo',
    );
  }
  await db
    .update(raffleCoupons)
    .set({
      holderName: nombre,
      holderEmail: email || null,
      holderPhone: str(body.telefono) || null,
      notes: str(body.notas) || null,
      updatedAt: new Date(),
    })
    .where(eq(raffleCoupons.id, id));
  return c.redirect('/operativa/sorteo?aviso=1');
});

operativaRoutes.get('/operativa/caja/nuevo', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const storesHtml = await storeOptions(db, access.storeId);
  return render(
    c,
    'Nueva caja',
    `<div class="card">
      <h1>Apertura de caja</h1>
      <form method="post" action="/operativa/caja">
        <label for="tienda">Tienda</label>
        <select id="tienda" name="tienda">${storesHtml}</select>
        <label for="fecha">Fecha del día</label>
        <input id="fecha" name="fecha" type="date" required value="${todayInput()}">
        <label for="apertura">Efectivo de apertura (pesos)</label>
        <input id="apertura" name="apertura" type="number" step="0.01" min="0" required>
        <label for="notas">Notas (opcional)</label>
        <input id="notas" name="notas" type="text">
        <button type="submit">Abrí la caja</button>
      </form>
      <p><a href="/operativa/caja">Volver al listado</a></p>
    </div>`,
  );
});

operativaRoutes.post('/operativa/caja', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const body = await c.req.parseBody();
  const access = await resolveStore(c, db, body.tienda);
  if ('error' in access) return denied(c, access.error);
  const fecha = dayStart(str(body.fecha));
  if (!fecha) {
    return fail(
      c,
      'Caja diaria',
      'Ingresá una fecha válida (AAAA-MM-DD).',
      '/operativa/caja/nuevo',
    );
  }
  const apertura = centsFrom(body.apertura);
  if (apertura === null) {
    return fail(
      c,
      'Caja diaria',
      'Ingresá el efectivo de apertura (monto válido en pesos).',
      '/operativa/caja/nuevo',
    );
  }
  const dup = await db
    .select({ id: cashDays.id })
    .from(cashDays)
    .where(
      and(eq(cashDays.storeId, access.storeId), eq(cashDays.reportDate, fecha)),
    )
    .limit(1);
  if (dup[0]) {
    return fail(
      c,
      'Caja diaria',
      'Ya hay una caja cargada para esa tienda y fecha.',
      '/operativa/caja/nuevo',
    );
  }
  const notas = str(body.notas);
  await db.insert(cashDays).values({
    storeId: access.storeId,
    reportDate: fecha,
    openingCents: apertura,
    status: 'open',
    openedBy: user.id,
    notes: notas || null,
  });
  return c.redirect('/operativa/caja?aviso=1');
});

operativaRoutes.post('/operativa/caja/:id/cerrar', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const id = idFrom(c.req.param('id'));
  if (!id) return notFound(c, 'Caja diaria', '/operativa/caja');
  const rows = await db
    .select()
    .from(cashDays)
    .where(eq(cashDays.id, id))
    .limit(1);
  const row = rows[0];
  if (!row) return notFound(c, 'Caja diaria', '/operativa/caja');
  if (!(await canManageStore(db, user, row.storeId))) {
    return denied(c, 'No tenés permiso sobre la tienda de esa caja.');
  }
  if (row.status === 'closed') {
    return fail(
      c,
      'Caja diaria',
      'Esta caja ya estaba cerrada.',
      '/operativa/caja',
    );
  }
  const body = await c.req.parseBody();
  const declarado = centsFrom(body.declarado);
  if (declarado === null) {
    return fail(
      c,
      'Caja diaria',
      'Ingresá el efectivo declarado (monto válido en pesos).',
      '/operativa/caja',
    );
  }
  const contado = centsFrom(body.contado);
  if (contado === null) {
    return fail(
      c,
      'Caja diaria',
      'Ingresá el efectivo contado (monto válido en pesos).',
      '/operativa/caja',
    );
  }
  const notas = str(body.notas);
  await db
    .update(cashDays)
    .set({
      status: 'closed',
      closingDeclaredCents: declarado,
      closingCountedCents: contado,
      differenceCents: contado - declarado,
      closedBy: user.id,
      closedAt: new Date(),
      ...(notas ? { notes: notas } : {}),
    })
    .where(eq(cashDays.id, id));
  return c.redirect('/operativa/caja?aviso=1');
});

operativaRoutes.get('/operativa/ventas', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const fecha = str(c.req.query('fecha'));
  const day = fecha ? dayStart(fecha) : null;
  if (fecha && !day) {
    return fail(
      c,
      'Resumen de ventas',
      'Ingresá una fecha válida.',
      '/operativa/ventas',
    );
  }
  const rows = await db
    .select()
    .from(salesSummaries)
    .where(
      and(
        eq(salesSummaries.storeId, access.storeId),
        ...(day
          ? [
              gte(salesSummaries.reportDate, day),
              lt(salesSummaries.reportDate, nextDay(day)),
            ]
          : []),
      ),
    )
    .orderBy(desc(salesSummaries.reportDate), desc(salesSummaries.id))
    .limit(50);

  const bodyRows = rows
    .map(
      (r) => `<tr>
        <td style="${TD}">${fmtDay(r.reportDate)}</td>
        <td style="${TD}">${SHIFT_LABELS[r.shift]}</td>
        <td style="${TD}">${formatArs(r.cashCents)}</td>
        <td style="${TD}">${formatArs(r.cardCents)}</td>
        <td style="${TD}">${formatArs(r.transferCents)}</td>
        <td style="${TD}">${formatArs(r.creditCents)}</td>
        <td style="${TD}"><strong>${formatArs(r.totalCents)}</strong></td>
        <td style="${TD}">
          <details style="display:inline">
            <summary style="cursor:pointer;font-size:0.85rem">Editá</summary>
            <form method="post" action="/operativa/ventas/${r.id}/editar" style="min-width:14rem">
              <label for="ef-${r.id}">Efectivo (pesos)</label>
              <input id="ef-${r.id}" name="efectivo" type="number" step="0.01" min="0" value="${(r.cashCents / 100).toFixed(2)}" required>
              <label for="ta-${r.id}">Tarjeta (pesos)</label>
              <input id="ta-${r.id}" name="tarjeta" type="number" step="0.01" min="0" value="${(r.cardCents / 100).toFixed(2)}" required>
              <label for="tr-${r.id}">Transferencia (pesos)</label>
              <input id="tr-${r.id}" name="transferencia" type="number" step="0.01" min="0" value="${(r.transferCents / 100).toFixed(2)}" required>
              <label for="fi-${r.id}">Fiado (pesos)</label>
              <input id="fi-${r.id}" name="fiado" type="number" step="0.01" min="0" value="${(r.creditCents / 100).toFixed(2)}" required>
              <label for="vnotas-${r.id}">Notas</label>
              <input id="vnotas-${r.id}" name="notas" type="text" value="${escapeHtml(r.notes ?? '')}">
              <button type="submit" style="${SMALL_BTN}">Guardá</button>
            </form>
          </details>
        </td>
      </tr>`,
    )
    .join('');

  const storesHtml = await storeOptions(db, access.storeId);

  return render(
    c,
    'Resumen de ventas',
    `<div class="card">
      <h1>Resumen de ventas (ensobrado)</h1>
      <p class="muted">Un registro por tienda, día y turno. El total se suma en el servidor.</p>
      ${filterBar('/operativa/ventas', fecha, storesHtml)}
      <a class="btn" href="/operativa/ventas/nuevo">Cargá un turno</a>
      <table class="papel" style="margin-top:0.75rem">
        <thead><tr>
          <th style="${TH}">Fecha</th>
          <th style="${TH}">Turno</th>
          <th style="${TH}">Efectivo</th>
          <th style="${TH}">Tarjeta</th>
          <th style="${TH}">Transferencia</th>
          <th style="${TH}">Fiado</th>
          <th style="${TH}">Total</th>
          <th style="${TH}">Acciones</th>
        </tr></thead>
        <tbody>${bodyRows || `<tr><td colspan="8" style="${TD}" class="muted">${NO_DATA}</td></tr>`}</tbody>
      </table>
    </div>`,
    { notice: aviso(c, 'Resumen guardado.') },
  );
});

operativaRoutes.get('/operativa/ventas/nuevo', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const storesHtml = await storeOptions(db, access.storeId);
  return render(
    c,
    'Nuevo resumen de ventas',
    `<div class="card">
      <h1>Resumen de ventas</h1>
      <form method="post" action="/operativa/ventas">
        <label for="tienda">Tienda</label>
        <select id="tienda" name="tienda">${storesHtml}</select>
        <label for="fecha">Fecha del día</label>
        <input id="fecha" name="fecha" type="date" required value="${todayInput()}">
        <label for="turno">Turno</label>
        <select id="turno" name="turno">${shiftOptions()}</select>
        <label for="efectivo">Efectivo (pesos)</label>
        <input id="efectivo" name="efectivo" type="number" step="0.01" min="0" value="0" required>
        <label for="tarjeta">Tarjeta (pesos)</label>
        <input id="tarjeta" name="tarjeta" type="number" step="0.01" min="0" value="0" required>
        <label for="transferencia">Transferencia (pesos)</label>
        <input id="transferencia" name="transferencia" type="number" step="0.01" min="0" value="0" required>
        <label for="fiado">Fiado (pesos)</label>
        <input id="fiado" name="fiado" type="number" step="0.01" min="0" value="0" required>
        <label for="notas">Notas (opcional)</label>
        <input id="notas" name="notas" type="text">
        <button type="submit">Guardá el resumen</button>
      </form>
      <p><a href="/operativa/ventas">Volver al listado</a></p>
    </div>`,
  );
});

operativaRoutes.post('/operativa/ventas', async (c) => {
  const db = dbOf(c);
  const body = await c.req.parseBody();
  const access = await resolveStore(c, db, body.tienda);
  if ('error' in access) return denied(c, access.error);
  const fecha = dayStart(str(body.fecha));
  if (!fecha) {
    return fail(
      c,
      'Resumen de ventas',
      'Ingresá una fecha válida (AAAA-MM-DD).',
      '/operativa/ventas/nuevo',
    );
  }
  const turno = str(body.turno);
  if (!isShift(turno)) {
    return fail(
      c,
      'Resumen de ventas',
      'Elegí un turno válido.',
      '/operativa/ventas/nuevo',
    );
  }
  const efectivo = centsOr0(body.efectivo);
  const tarjeta = centsOr0(body.tarjeta);
  const transferencia = centsOr0(body.transferencia);
  const fiado = centsOr0(body.fiado);
  if (efectivo === null) {
    return fail(
      c,
      'Resumen de ventas',
      'El efectivo no es un monto válido en pesos.',
      '/operativa/ventas/nuevo',
    );
  }
  if (tarjeta === null) {
    return fail(
      c,
      'Resumen de ventas',
      'La tarjeta no es un monto válido en pesos.',
      '/operativa/ventas/nuevo',
    );
  }
  if (transferencia === null) {
    return fail(
      c,
      'Resumen de ventas',
      'La transferencia no es un monto válido en pesos.',
      '/operativa/ventas/nuevo',
    );
  }
  if (fiado === null) {
    return fail(
      c,
      'Resumen de ventas',
      'El fiado no es un monto válido en pesos.',
      '/operativa/ventas/nuevo',
    );
  }
  const dup = await db
    .select({ id: salesSummaries.id })
    .from(salesSummaries)
    .where(
      and(
        eq(salesSummaries.storeId, access.storeId),
        eq(salesSummaries.reportDate, fecha),
        eq(salesSummaries.shift, turno),
      ),
    )
    .limit(1);
  if (dup[0]) {
    return fail(
      c,
      'Resumen de ventas',
      'Ya hay un resumen para esa fecha y turno en esta tienda.',
      '/operativa/ventas/nuevo',
    );
  }
  await db.insert(salesSummaries).values({
    storeId: access.storeId,
    reportDate: fecha,
    shift: turno,
    cashCents: efectivo,
    cardCents: tarjeta,
    transferCents: transferencia,
    creditCents: fiado,
    totalCents: efectivo + tarjeta + transferencia + fiado,
    notes: str(body.notas) || null,
  });
  return c.redirect('/operativa/ventas?aviso=1');
});

operativaRoutes.post('/operativa/ventas/:id/editar', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const id = idFrom(c.req.param('id'));
  if (!id) return notFound(c, 'Resumen de ventas', '/operativa/ventas');
  const rows = await db
    .select()
    .from(salesSummaries)
    .where(eq(salesSummaries.id, id))
    .limit(1);
  const row = rows[0];
  if (!row) return notFound(c, 'Resumen de ventas', '/operativa/ventas');
  if (!(await canManageStore(db, user, row.storeId))) {
    return denied(c, 'No tenés permiso sobre la tienda de ese resumen.');
  }
  const body = await c.req.parseBody();
  const efectivo = centsOr0(body.efectivo);
  const tarjeta = centsOr0(body.tarjeta);
  const transferencia = centsOr0(body.transferencia);
  const fiado = centsOr0(body.fiado);
  if (efectivo === null) {
    return fail(
      c,
      'Resumen de ventas',
      'El efectivo no es un monto válido en pesos.',
      '/operativa/ventas',
    );
  }
  if (tarjeta === null) {
    return fail(
      c,
      'Resumen de ventas',
      'La tarjeta no es un monto válido en pesos.',
      '/operativa/ventas',
    );
  }
  if (transferencia === null) {
    return fail(
      c,
      'Resumen de ventas',
      'La transferencia no es un monto válido en pesos.',
      '/operativa/ventas',
    );
  }
  if (fiado === null) {
    return fail(
      c,
      'Resumen de ventas',
      'El fiado no es un monto válido en pesos.',
      '/operativa/ventas',
    );
  }
  await db
    .update(salesSummaries)
    .set({
      cashCents: efectivo,
      cardCents: tarjeta,
      transferCents: transferencia,
      creditCents: fiado,
      totalCents: efectivo + tarjeta + transferencia + fiado,
      notes: str(body.notas) || null,
      updatedAt: new Date(),
    })
    .where(eq(salesSummaries.id, id));
  return c.redirect('/operativa/ventas?aviso=1');
});

operativaRoutes.get('/operativa/actividades', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const fecha = str(c.req.query('fecha'));
  const day = fecha ? dayStart(fecha) : null;
  if (fecha && !day) {
    return fail(
      c,
      'Actividades diarias',
      'Ingresá una fecha válida.',
      '/operativa/actividades',
    );
  }
  const rows = await db
    .select()
    .from(dailyActivities)
    .where(
      and(
        eq(dailyActivities.storeId, access.storeId),
        ...(day
          ? [
              gte(dailyActivities.reportDate, day),
              lt(dailyActivities.reportDate, nextDay(day)),
            ]
          : []),
      ),
    )
    .orderBy(desc(dailyActivities.reportDate), desc(dailyActivities.id))
    .limit(50);

  const bodyRows = rows
    .map(
      (r) => `<tr>
        <td style="${TD}">${fmtDay(r.reportDate)}</td>
        <td style="${TD}">${SHIFT_LABELS[r.shift]}</td>
        <td style="${TD}">${r.gncOpening} m³</td>
        <td style="${TD}">${r.gncClosing} m³</td>
        <td style="${TD}"><strong>${r.gncVolume} m³</strong></td>
        <td style="${TD}">${formatArs(r.gncSalesCents)}</td>
        <td style="${TD}">${r.pumpCheck ? 'Revisada' : 'Pendiente'}</td>
        <td style="${TD}">${r.notes ? escapeHtml(r.notes) : '—'}</td>
      </tr>`,
    )
    .join('');

  const storesHtml = await storeOptions(db, access.storeId);

  return render(
    c,
    'Actividades diarias',
    `<div class="card">
      <h1>Resumen diario de actividades</h1>
      <p class="muted">Una fila por turno con la matriz de GNC. El volumen se calcula como cierre menos apertura.</p>
      ${filterBar('/operativa/actividades', fecha, storesHtml)}
      <a class="btn" href="/operativa/actividades/nuevo">Cargá un turno</a>
      <table class="papel" style="margin-top:0.75rem">
        <thead><tr>
          <th style="${TH}">Fecha</th>
          <th style="${TH}">Turno</th>
          <th style="${TH}">Apertura GNC</th>
          <th style="${TH}">Cierre GNC</th>
          <th style="${TH}">Volumen</th>
          <th style="${TH}">Ventas GNC</th>
          <th style="${TH}">Revisión bomba</th>
          <th style="${TH}">Notas</th>
        </tr></thead>
        <tbody>${bodyRows || `<tr><td colspan="8" style="${TD}" class="muted">${NO_DATA}</td></tr>`}</tbody>
      </table>
    </div>`,
    { notice: aviso(c, 'Actividad guardada.') },
  );
});

operativaRoutes.get('/operativa/actividades/nuevo', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const storesHtml = await storeOptions(db, access.storeId);
  return render(
    c,
    'Nueva actividad',
    `<div class="card">
      <h1>Resumen diario de actividades</h1>
      <form method="post" action="/operativa/actividades">
        <label for="tienda">Tienda</label>
        <select id="tienda" name="tienda">${storesHtml}</select>
        <label for="fecha">Fecha del día</label>
        <input id="fecha" name="fecha" type="date" required value="${todayInput()}">
        <label for="turno">Turno</label>
        <select id="turno" name="turno">${shiftOptions()}</select>
        <label for="gnc_apertura">GNC apertura (m³)</label>
        <input id="gnc_apertura" name="gnc_apertura" type="number" step="1" min="0" value="0" required>
        <label for="gnc_cierre">GNC cierre (m³)</label>
        <input id="gnc_cierre" name="gnc_cierre" type="number" step="1" min="0" value="0" required>
        <label for="gnc_ventas">Ventas de GNC (pesos)</label>
        <input id="gnc_ventas" name="gnc_ventas" type="number" step="0.01" min="0" value="0" required>
        <label style="display:flex;gap:0.5rem;align-items:center;font-weight:400">
          <input type="checkbox" name="revision" value="on" style="width:auto"> Revisión de bombas hecha
        </label>
        <label for="notas">Notas (opcional)</label>
        <input id="notas" name="notas" type="text">
        <button type="submit">Guardá la actividad</button>
      </form>
      <p><a href="/operativa/actividades">Volver al listado</a></p>
    </div>`,
  );
});

operativaRoutes.post('/operativa/actividades', async (c) => {
  const db = dbOf(c);
  const body = await c.req.parseBody();
  const access = await resolveStore(c, db, body.tienda);
  if ('error' in access) return denied(c, access.error);
  const fecha = dayStart(str(body.fecha));
  if (!fecha) {
    return fail(
      c,
      'Actividades diarias',
      'Ingresá una fecha válida (AAAA-MM-DD).',
      '/operativa/actividades/nuevo',
    );
  }
  const turno = str(body.turno);
  if (!isShift(turno)) {
    return fail(
      c,
      'Actividades diarias',
      'Elegí un turno válido.',
      '/operativa/actividades/nuevo',
    );
  }
  const apertura = qtyFrom(body.gnc_apertura);
  if (apertura === null) {
    return fail(
      c,
      'Actividades diarias',
      'La apertura de GNC no es un número válido en m³.',
      '/operativa/actividades/nuevo',
    );
  }
  const cierre = qtyFrom(body.gnc_cierre);
  if (cierre === null) {
    return fail(
      c,
      'Actividades diarias',
      'El cierre de GNC no es un número válido en m³.',
      '/operativa/actividades/nuevo',
    );
  }
  if (cierre < apertura) {
    return fail(
      c,
      'Actividades diarias',
      'El cierre de GNC no puede ser menor que la apertura.',
      '/operativa/actividades/nuevo',
    );
  }
  const ventas = centsOr0(body.gnc_ventas);
  if (ventas === null) {
    return fail(
      c,
      'Actividades diarias',
      'Las ventas de GNC no son un monto válido en pesos.',
      '/operativa/actividades/nuevo',
    );
  }
  const dup = await db
    .select({ id: dailyActivities.id })
    .from(dailyActivities)
    .where(
      and(
        eq(dailyActivities.storeId, access.storeId),
        eq(dailyActivities.reportDate, fecha),
        eq(dailyActivities.shift, turno),
      ),
    )
    .limit(1);
  if (dup[0]) {
    return fail(
      c,
      'Actividades diarias',
      'Ya hay una actividad cargada para esa fecha y turno en esta tienda.',
      '/operativa/actividades/nuevo',
    );
  }
  const revision = str(body.revision);
  await db.insert(dailyActivities).values({
    storeId: access.storeId,
    reportDate: fecha,
    shift: turno,
    gncOpening: apertura,
    gncClosing: cierre,
    gncVolume: cierre - apertura,
    gncSalesCents: ventas,
    pumpCheck: revision === 'on' || revision === '1',
    notes: str(body.notas) || null,
  });
  return c.redirect('/operativa/actividades?aviso=1');
});

operativaRoutes.get('/operativa/lubricantes', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const fecha = str(c.req.query('fecha'));
  const day = fecha ? dayStart(fecha) : null;
  if (fecha && !day) {
    return fail(
      c,
      'Stock de lubricantes',
      'Ingresá una fecha válida.',
      '/operativa/lubricantes',
    );
  }
  const rows = await db
    .select()
    .from(lubricantStocks)
    .where(
      and(
        eq(lubricantStocks.storeId, access.storeId),
        ...(day
          ? [
              gte(lubricantStocks.reportDate, day),
              lt(lubricantStocks.reportDate, nextDay(day)),
            ]
          : []),
      ),
    )
    .orderBy(desc(lubricantStocks.reportDate), desc(lubricantStocks.id))
    .limit(50);

  const bodyRows = rows
    .map(
      (r) => `<tr>
        <td style="${TD}">${fmtDay(r.reportDate)}</td>
        <td style="${TD}">${escapeHtml(r.brand)}</td>
        <td style="${TD}">${escapeHtml(r.grade)}</td>
        <td style="${TD}">${r.containerL} L</td>
        <td style="${TD}">${r.openingQty}</td>
        <td style="${TD}">${r.entriesQty}</td>
        <td style="${TD}">${r.exitsQty}</td>
        <td style="${TD}"><strong>${r.closingQty}</strong></td>
        <td style="${TD}">${formatArs(r.unitPriceCents)}</td>
        <td style="${TD}">
          <details style="display:inline">
            <summary style="cursor:pointer;font-size:0.85rem">Editá</summary>
            <form method="post" action="/operativa/lubricantes/${r.id}/editar" style="min-width:14rem">
              <label for="lmar-${r.id}">Marca</label>
              <input id="lmar-${r.id}" name="marca" type="text" required value="${escapeHtml(r.brand)}">
              <label for="lgra-${r.id}">Viscosidad</label>
              <input id="lgra-${r.id}" name="grado" type="text" required value="${escapeHtml(r.grade)}">
              <label for="lenv-${r.id}">Envase (litros)</label>
              <input id="lenv-${r.id}" name="envase_l" type="number" step="0.001" min="0" required value="${r.containerL}">
              <label for="lap-${r.id}">Apertura</label>
              <input id="lap-${r.id}" name="apertura" type="number" step="1" min="0" required value="${r.openingQty}">
              <label for="len-${r.id}">Entradas</label>
              <input id="len-${r.id}" name="entradas" type="number" step="1" min="0" required value="${r.entriesQty}">
              <label for="lsa-${r.id}">Salidas</label>
              <input id="lsa-${r.id}" name="salidas" type="number" step="1" min="0" required value="${r.exitsQty}">
              <label for="lpre-${r.id}">Precio unitario (pesos)</label>
              <input id="lpre-${r.id}" name="precio" type="number" step="0.01" min="0" required value="${(r.unitPriceCents / 100).toFixed(2)}">
              <button type="submit" style="${SMALL_BTN}">Guardá</button>
            </form>
          </details>
        </td>
      </tr>`,
    )
    .join('');

  const storesHtml = await storeOptions(db, access.storeId);

  return render(
    c,
    'Stock de lubricantes',
    `<div class="card">
      <h1>Stock de lubricantes</h1>
      <p class="muted">El cierre se calcula en el servidor como apertura más entradas menos salidas.</p>
      ${filterBar('/operativa/lubricantes', fecha, storesHtml)}
      <a class="btn" href="/operativa/lubricantes/nuevo">Cargá un recuento</a>
      <table class="papel" style="margin-top:0.75rem">
        <thead><tr>
          <th style="${TH}">Fecha</th>
          <th style="${TH}">Marca</th>
          <th style="${TH}">Grado</th>
          <th style="${TH}">Envase</th>
          <th style="${TH}">Apertura</th>
          <th style="${TH}">Entradas</th>
          <th style="${TH}">Salidas</th>
          <th style="${TH}">Cierre</th>
          <th style="${TH}">Precio unit.</th>
          <th style="${TH}">Acciones</th>
        </tr></thead>
        <tbody>${bodyRows || `<tr><td colspan="10" style="${TD}" class="muted">${NO_DATA}</td></tr>`}</tbody>
      </table>
    </div>`,
    { notice: aviso(c, 'Stock guardado.') },
  );
});

operativaRoutes.get('/operativa/lubricantes/nuevo', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const storesHtml = await storeOptions(db, access.storeId);
  const catalog = await db
    .select({ id: products.id, name: products.name })
    .from(products)
    .where(eq(products.active, true))
    .orderBy(asc(products.name))
    .limit(100);
  const productOptions =
    `<option value="">Sin vincular a un producto</option>` +
    catalog
      .map(
        (p) =>
          `<option value="${p.id}">${escapeHtml(p.name)}</option>`,
      )
      .join('');
  return render(
    c,
    'Nuevo recuento de lubricantes',
    `<div class="card">
      <h1>Stock de lubricantes</h1>
      <form method="post" action="/operativa/lubricantes">
        <label for="tienda">Tienda</label>
        <select id="tienda" name="tienda">${storesHtml}</select>
        <label for="fecha">Fecha del conteo</label>
        <input id="fecha" name="fecha" type="date" required value="${todayInput()}">
        <label for="producto">Vincular a un producto de la tienda (opcional)</label>
        <select id="producto" name="producto">${productOptions}</select>
        <label for="marca">Marca</label>
        <input id="marca" name="marca" type="text" required placeholder="Ej.: YPF">
        <label for="grado">Viscosidad</label>
        <input id="grado" name="grado" type="text" required placeholder="Ej.: 5W30">
        <label for="envase_l">Envase (litros)</label>
        <input id="envase_l" name="envase_l" type="number" step="0.001" min="0" required placeholder="Ej.: 1">
        <label for="apertura">Stock de apertura (unidades)</label>
        <input id="apertura" name="apertura" type="number" step="1" min="0" value="0" required>
        <label for="entradas">Entradas (unidades)</label>
        <input id="entradas" name="entradas" type="number" step="1" min="0" value="0" required>
        <label for="salidas">Salidas (unidades)</label>
        <input id="salidas" name="salidas" type="number" step="1" min="0" value="0" required>
        <label for="precio">Precio unitario (pesos)</label>
        <input id="precio" name="precio" type="number" step="0.01" min="0" value="0" required>
        <p class="muted">El stock de cierre se calcula solo: apertura + entradas − salidas.</p>
        <button type="submit">Guardá el recuento</button>
      </form>
      <p><a href="/operativa/lubricantes">Volver al listado</a></p>
    </div>`,
  );
});

operativaRoutes.post('/operativa/lubricantes', async (c) => {
  const db = dbOf(c);
  const body = await c.req.parseBody();
  const access = await resolveStore(c, db, body.tienda);
  if ('error' in access) return denied(c, access.error);
  const fecha = dayStart(str(body.fecha));
  if (!fecha) {
    return fail(
      c,
      'Stock de lubricantes',
      'Ingresá una fecha válida (AAAA-MM-DD).',
      '/operativa/lubricantes/nuevo',
    );
  }
  const marca = str(body.marca);
  if (!marca) {
    return fail(
      c,
      'Stock de lubricantes',
      'Ingresá la marca del lubricante.',
      '/operativa/lubricantes/nuevo',
    );
  }
  const grado = str(body.grado);
  if (!grado) {
    return fail(
      c,
      'Stock de lubricantes',
      'Ingresá la viscosidad del lubricante.',
      '/operativa/lubricantes/nuevo',
    );
  }
  const envase = floatFrom(body.envase_l);
  if (envase === null || envase <= 0) {
    return fail(
      c,
      'Stock de lubricantes',
      'Ingresá el tamaño del envase en litros (mayor a 0).',
      '/operativa/lubricantes/nuevo',
    );
  }
  const apertura = qtyFrom(body.apertura);
  const entradas = qtyFrom(body.entradas);
  const salidas = qtyFrom(body.salidas);
  if (apertura === null) {
    return fail(
      c,
      'Stock de lubricantes',
      'La apertura de stock no es un número válido.',
      '/operativa/lubricantes/nuevo',
    );
  }
  if (entradas === null) {
    return fail(
      c,
      'Stock de lubricantes',
      'Las entradas de stock no son un número válido.',
      '/operativa/lubricantes/nuevo',
    );
  }
  if (salidas === null) {
    return fail(
      c,
      'Stock de lubricantes',
      'Las salidas de stock no son un número válido.',
      '/operativa/lubricantes/nuevo',
    );
  }
  const cierre = apertura + entradas - salidas;
  if (cierre < 0) {
    return fail(
      c,
      'Stock de lubricantes',
      `El cierre quedaría en ${cierre}: las salidas superan la apertura más las entradas.`,
      '/operativa/lubricantes/nuevo',
    );
  }
  const precio = centsOr0(body.precio);
  if (precio === null) {
    return fail(
      c,
      'Stock de lubricantes',
      'El precio unitario no es un monto válido en pesos.',
      '/operativa/lubricantes/nuevo',
    );
  }
  const productoId = idFrom(body.producto);
  if (body.producto !== undefined && str(body.producto) !== '' && !productoId) {
    return fail(
      c,
      'Stock de lubricantes',
      'Seleccioná un producto válido.',
      '/operativa/lubricantes/nuevo',
    );
  }
  if (productoId) {
    const prows = await db
      .select({ id: products.id })
      .from(products)
      .where(eq(products.id, productoId))
      .limit(1);
    if (!prows[0]) {
      return fail(
        c,
        'Stock de lubricantes',
        'Ese producto no existe.',
        '/operativa/lubricantes/nuevo',
      );
    }
  }
  await db.insert(lubricantStocks).values({
    storeId: access.storeId,
    reportDate: fecha,
    productId: productoId,
    brand: marca,
    grade: grado,
    containerL: envase,
    openingQty: apertura,
    entriesQty: entradas,
    exitsQty: salidas,
    closingQty: cierre,
    unitPriceCents: precio,
  });
  return c.redirect('/operativa/lubricantes?aviso=1');
});

operativaRoutes.post('/operativa/lubricantes/:id/editar', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const id = idFrom(c.req.param('id'));
  if (!id) return notFound(c, 'Stock de lubricantes', '/operativa/lubricantes');
  const rows = await db
    .select()
    .from(lubricantStocks)
    .where(eq(lubricantStocks.id, id))
    .limit(1);
  const row = rows[0];
  if (!row) {
    return notFound(c, 'Stock de lubricantes', '/operativa/lubricantes');
  }
  if (!(await canManageStore(db, user, row.storeId))) {
    return denied(c, 'No tenés permiso sobre la tienda de ese recuento.');
  }
  const body = await c.req.parseBody();
  const marca = str(body.marca);
  if (!marca) {
    return fail(
      c,
      'Stock de lubricantes',
      'Ingresá la marca del lubricante.',
      '/operativa/lubricantes',
    );
  }
  const grado = str(body.grado);
  if (!grado) {
    return fail(
      c,
      'Stock de lubricantes',
      'Ingresá la viscosidad del lubricante.',
      '/operativa/lubricantes',
    );
  }
  const envase = floatFrom(body.envase_l);
  if (envase === null || envase <= 0) {
    return fail(
      c,
      'Stock de lubricantes',
      'Ingresá el tamaño del envase en litros (mayor a 0).',
      '/operativa/lubricantes',
    );
  }
  const apertura = qtyFrom(body.apertura);
  const entradas = qtyFrom(body.entradas);
  const salidas = qtyFrom(body.salidas);
  if (apertura === null) {
    return fail(
      c,
      'Stock de lubricantes',
      'La apertura de stock no es un número válido.',
      '/operativa/lubricantes',
    );
  }
  if (entradas === null) {
    return fail(
      c,
      'Stock de lubricantes',
      'Las entradas de stock no son un número válido.',
      '/operativa/lubricantes',
    );
  }
  if (salidas === null) {
    return fail(
      c,
      'Stock de lubricantes',
      'Las salidas de stock no son un número válido.',
      '/operativa/lubricantes',
    );
  }
  const cierre = apertura + entradas - salidas;
  if (cierre < 0) {
    return fail(
      c,
      'Stock de lubricantes',
      `El cierre quedaría en ${cierre}: las salidas superan la apertura más las entradas.`,
      '/operativa/lubricantes',
    );
  }
  const precio = centsOr0(body.precio);
  if (precio === null) {
    return fail(
      c,
      'Stock de lubricantes',
      'El precio unitario no es un monto válido en pesos.',
      '/operativa/lubricantes',
    );
  }
  await db
    .update(lubricantStocks)
    .set({
      brand: marca,
      grade: grado,
      containerL: envase,
      openingQty: apertura,
      entriesQty: entradas,
      exitsQty: salidas,
      closingQty: cierre,
      unitPriceCents: precio,
      updatedAt: new Date(),
    })
    .where(eq(lubricantStocks.id, id));
  return c.redirect('/operativa/lubricantes?aviso=1');
});

type ReadingInput = {
  surtidor: number;
  nozzle: number;
  product: Fuel;
  opening: number;
  closing: number;
  priceCents: number;
};

const READING_ROWS = 8;

function parseReadings(
  body: Record<string, unknown>,
): ReadingInput[] | string {
  const out: ReadingInput[] = [];
  const seen = new Set<string>();
  let any = false;
  for (let i = 0; i < READING_ROWS; i++) {
    const sur = str(body[`r${i}_surtidor`]);
    const noz = str(body[`r${i}_nozzle`]);
    const prod = str(body[`r${i}_producto`]);
    const ini = str(body[`r${i}_apertura`]);
    const fin = str(body[`r${i}_cierre`]);
    const precio = str(body[`r${i}_precio`]);
    if (!sur && !noz && !prod && !ini && !fin && !precio) continue;
    any = true;
    const label = `En la fila ${i + 1}`;
    const s = Number(sur);
    if (!Number.isInteger(s) || s < 1 || s > 6) {
      return `${label}: el surtidor debe ser un número de 1 a 6.`;
    }
    const n = Number(noz);
    if (!Number.isInteger(n) || n < 1 || n > 6) {
      return `${label}: la boquilla debe ser un número de 1 a 6.`;
    }
    if (!isFuel(prod)) {
      return `${label}: elegí un producto válido.`;
    }
    const opening = Number(ini);
    if (ini === '' || !Number.isFinite(opening) || opening < 0) {
      return `${label}: la lectura inicial no es válida.`;
    }
    const closing = Number(fin);
    if (fin === '' || !Number.isFinite(closing) || closing < 0) {
      return `${label}: la lectura final no es válida.`;
    }
    if (closing < opening) {
      return `${label}: la lectura final no puede ser menor que la inicial.`;
    }
    const price = centsFrom(precio);
    if (price === null) {
      return `${label}: ingresá el precio por litro (monto válido en pesos).`;
    }
    const key = `${s}-${n}`;
    if (seen.has(key)) {
      return `${label}: repetís el surtidor ${s} con la boquilla ${n}.`;
    }
    seen.add(key);
    out.push({
      surtidor: s,
      nozzle: n,
      product: prod,
      opening,
      closing,
      priceCents: price,
    });
  }
  if (!any) return 'Cargá al menos una lectura de surtidor.';
  return out;
}

function readingsFormHtml(): string {
  const surtidorOpts =
    `<option value="">—</option>` +
    [1, 2, 3, 4, 5, 6].map((n) => `<option value="${n}">${n}</option>`).join('');
  const fuelOpts = `<option value="">—</option>` + fuelOptions();
  let out = '';
  for (let i = 0; i < READING_ROWS; i++) {
    out += `<div class="row" style="border-top:1px solid #e7e5e4;padding-top:0.5rem;margin-top:0.5rem">
      <div style="flex:1;min-width:6rem">
        <label for="r${i}_surtidor">Surtidor</label>
        <select id="r${i}_surtidor" name="r${i}_surtidor">${surtidorOpts}</select>
      </div>
      <div style="flex:1;min-width:6rem">
        <label for="r${i}_nozzle">Boquilla</label>
        <select id="r${i}_nozzle" name="r${i}_nozzle">${surtidorOpts}</select>
      </div>
      <div style="flex:1;min-width:7rem">
        <label for="r${i}_producto">Producto</label>
        <select id="r${i}_producto" name="r${i}_producto">${fuelOpts}</select>
      </div>
      <div style="flex:1;min-width:7rem">
        <label for="r${i}_apertura">Lectura inicial</label>
        <input id="r${i}_apertura" name="r${i}_apertura" type="number" step="0.001" min="0">
      </div>
      <div style="flex:1;min-width:7rem">
        <label for="r${i}_cierre">Lectura final</label>
        <input id="r${i}_cierre" name="r${i}_cierre" type="number" step="0.001" min="0">
      </div>
      <div style="flex:1;min-width:7rem">
        <label for="r${i}_precio">Precio por litro (pesos)</label>
        <input id="r${i}_precio" name="r${i}_precio" type="number" step="0.01" min="0">
      </div>
    </div>`;
  }
  return out;
}

operativaRoutes.get('/operativa/rendicion', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const fecha = str(c.req.query('fecha'));
  const day = fecha ? dayStart(fecha) : null;
  if (fecha && !day) {
    return fail(
      c,
      'Rendición de turno',
      'Ingresá una fecha válida.',
      '/operativa/rendicion',
    );
  }
  const rows = await db
    .select()
    .from(shiftReports)
    .where(
      and(
        eq(shiftReports.storeId, access.storeId),
        ...(day
          ? [
              gte(shiftReports.reportDate, day),
              lt(shiftReports.reportDate, nextDay(day)),
            ]
          : []),
      ),
    )
    .orderBy(desc(shiftReports.reportDate), desc(shiftReports.id))
    .limit(50);

  const bodyRows = rows
    .map(
      (r) => `<tr>
        <td style="${TD}">${fmtDay(r.reportDate)}</td>
        <td style="${TD}">${SHIFT_LABELS[r.shift]}</td>
        <td style="${TD}">${MOMENT_FMT.format(r.shiftStart)}</td>
        <td style="${TD}">${MOMENT_FMT.format(r.shiftEnd)}</td>
        <td style="${TD}">${formatArs(r.openingCents)}</td>
        <td style="${TD}">${formatArs(r.closingCents)}</td>
        <td style="${TD}"><a href="/operativa/rendicion/${r.id}">Ver detalle</a></td>
      </tr>`,
    )
    .join('');

  const storesHtml = await storeOptions(db, access.storeId);

  return render(
    c,
    'Rendición de turno',
    `<div class="card">
      <h1>Rendición de turno</h1>
      <p class="muted">Encabezado del turno más las lecturas de cada surtidor y boquilla.</p>
      ${filterBar('/operativa/rendicion', fecha, storesHtml)}
      <a class="btn" href="/operativa/rendicion/nuevo">Cargá una rendición</a>
      <table class="papel" style="margin-top:0.75rem">
        <thead><tr>
          <th style="${TH}">Fecha</th>
          <th style="${TH}">Turno</th>
          <th style="${TH}">Inicio</th>
          <th style="${TH}">Fin</th>
          <th style="${TH}">Apertura</th>
          <th style="${TH}">Cierre</th>
          <th style="${TH}">Detalle</th>
        </tr></thead>
        <tbody>${bodyRows || `<tr><td colspan="7" style="${TD}" class="muted">${NO_DATA}</td></tr>`}</tbody>
      </table>
    </div>`,
    { notice: aviso(c, 'Rendición guardada.') },
  );
});

operativaRoutes.get('/operativa/rendicion/nuevo', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const storesHtml = await storeOptions(db, access.storeId);
  return render(
    c,
    'Nueva rendición de turno',
    `<div class="card">
      <h1>Rendición de turno</h1>
      <form method="post" action="/operativa/rendicion">
        <label for="tienda">Tienda</label>
        <select id="tienda" name="tienda">${storesHtml}</select>
        <label for="fecha">Fecha del día</label>
        <input id="fecha" name="fecha" type="date" required value="${todayInput()}">
        <label for="turno">Turno</label>
        <select id="turno" name="turno">${shiftOptions()}</select>
        <label for="inicio">Inicio del turno</label>
        <input id="inicio" name="inicio" type="datetime-local" required>
        <label for="fin">Fin del turno</label>
        <input id="fin" name="fin" type="datetime-local" required>
        <label for="apertura">Efectivo de apertura (pesos)</label>
        <input id="apertura" name="apertura" type="number" step="0.01" min="0" value="0" required>
        <label for="cierre">Efectivo de cierre (pesos)</label>
        <input id="cierre" name="cierre" type="number" step="0.01" min="0" value="0" required>
        <label for="notas">Notas (opcional)</label>
        <input id="notas" name="notas" type="text">
        <h2 style="margin-top:1.25rem">Lecturas de surtidores</h2>
        <p class="muted">Completá una fila por surtidor y boquilla usados en el turno.</p>
        ${readingsFormHtml()}
        <button type="submit">Guardá la rendición</button>
      </form>
      <p><a href="/operativa/rendicion">Volver al listado</a></p>
    </div>`,
  );
});

operativaRoutes.post('/operativa/rendicion', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const body = await c.req.parseBody();
  const access = await resolveStore(c, db, body.tienda);
  if ('error' in access) return denied(c, access.error);
  const fecha = dayStart(str(body.fecha));
  if (!fecha) {
    return fail(
      c,
      'Rendición de turno',
      'Ingresá una fecha válida (AAAA-MM-DD).',
      '/operativa/rendicion/nuevo',
    );
  }
  const turno = str(body.turno);
  if (!isShift(turno)) {
    return fail(
      c,
      'Rendición de turno',
      'Elegí un turno válido.',
      '/operativa/rendicion/nuevo',
    );
  }
  const inicio = dtFrom(body.inicio);
  if (!inicio) {
    return fail(
      c,
      'Rendición de turno',
      'Ingresá la hora de inicio del turno.',
      '/operativa/rendicion/nuevo',
    );
  }
  const fin = dtFrom(body.fin);
  if (!fin) {
    return fail(
      c,
      'Rendición de turno',
      'Ingresá la hora de fin del turno.',
      '/operativa/rendicion/nuevo',
    );
  }
  if (fin.getTime() <= inicio.getTime()) {
    return fail(
      c,
      'Rendición de turno',
      'El fin del turno debe ser posterior al inicio.',
      '/operativa/rendicion/nuevo',
    );
  }
  const apertura = centsOr0(body.apertura);
  if (apertura === null) {
    return fail(
      c,
      'Rendición de turno',
      'La apertura de caja no es un monto válido en pesos.',
      '/operativa/rendicion/nuevo',
    );
  }
  const cierre = centsOr0(body.cierre);
  if (cierre === null) {
    return fail(
      c,
      'Rendición de turno',
      'El cierre de caja no es un monto válido en pesos.',
      '/operativa/rendicion/nuevo',
    );
  }
  const dup = await db
    .select({ id: shiftReports.id })
    .from(shiftReports)
    .where(
      and(
        eq(shiftReports.storeId, access.storeId),
        eq(shiftReports.reportDate, fecha),
        eq(shiftReports.shift, turno),
      ),
    )
    .limit(1);
  if (dup[0]) {
    return fail(
      c,
      'Rendición de turno',
      'Ya hay una rendición para esa fecha y turno en esta tienda.',
      '/operativa/rendicion/nuevo',
    );
  }
  const readings = parseReadings(body);
  if (typeof readings === 'string') {
    return fail(c, 'Rendición de turno', readings, '/operativa/rendicion/nuevo');
  }
  const inserted = await db
    .insert(shiftReports)
    .values({
      storeId: access.storeId,
      reportDate: fecha,
      shift: turno,
      shiftStart: inicio,
      shiftEnd: fin,
      cashierId: user.id,
      openingCents: apertura,
      closingCents: cierre,
      notes: str(body.notas) || null,
    })
    .returning({ id: shiftReports.id });
  const reportId = inserted[0]!.id;
  await db.insert(shiftReportReadings).values(
    readings.map((r) => ({
      reportId,
      surtidor: r.surtidor,
      nozzle: r.nozzle,
      product: r.product,
      openingReading: r.opening,
      closingReading: r.closing,
      volume: r.closing - r.opening,
      priceCents: r.priceCents,
      totalCents: Math.round((r.closing - r.opening) * r.priceCents),
    })),
  );
  return c.redirect(`/operativa/rendicion/${reportId}?aviso=1`);
});

operativaRoutes.get('/operativa/rendicion/:id', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const id = idFrom(c.req.param('id'));
  if (!id) return notFound(c, 'Rendición de turno', '/operativa/rendicion');
  const rows = await db
    .select()
    .from(shiftReports)
    .where(eq(shiftReports.id, id))
    .limit(1);
  const report = rows[0];
  if (!report) {
    return notFound(c, 'Rendición de turno', '/operativa/rendicion');
  }
  if (!(await canManageStore(db, user, report.storeId))) {
    return denied(c, 'No tenés permiso sobre la tienda de esa rendición.');
  }
  const readings = await db
    .select()
    .from(shiftReportReadings)
    .where(eq(shiftReportReadings.reportId, report.id))
    .orderBy(
      asc(shiftReportReadings.surtidor),
      asc(shiftReportReadings.nozzle),
    );
  const totals = await db
    .select({
      total: sql<number>`coalesce(sum(${shiftReportReadings.totalCents}), 0)`,
      volume: sql<number>`coalesce(sum(${shiftReportReadings.volume}), 0)`,
    })
    .from(shiftReportReadings)
    .where(eq(shiftReportReadings.reportId, report.id));

  const bodyRows = readings
    .map(
      (r) => `<tr>
        <td style="${TD}">${r.surtidor}</td>
        <td style="${TD}">${r.nozzle}</td>
        <td style="${TD}">${FUEL_LABELS[r.product]}</td>
        <td style="${TD}">${r.openingReading}</td>
        <td style="${TD}">${r.closingReading}</td>
        <td style="${TD}"><strong>${r.volume}</strong></td>
        <td style="${TD}">${formatArs(r.priceCents)}</td>
        <td style="${TD}">${formatArs(r.totalCents)}</td>
      </tr>`,
    )
    .join('');

  const totalCents = Number(totals[0]?.total ?? 0);
  const totalVolume = Number(totals[0]?.volume ?? 0);

  return render(
    c,
    'Detalle de rendición',
    `<div class="card">
      <h1>Rendición · ${fmtDay(report.reportDate)} · ${SHIFT_LABELS[report.shift]}</h1>
      <div class="row">
        <span class="pill">Inicio: ${MOMENT_FMT.format(report.shiftStart)}</span>
        <span class="pill">Fin: ${MOMENT_FMT.format(report.shiftEnd)}</span>
      </div>
      <div class="row" style="margin-top:0.5rem">
        <span>Apertura de caja: <strong>${formatArs(report.openingCents)}</strong></span>
        <span>Cierre de caja: <strong>${formatArs(report.closingCents)}</strong></span>
      </div>
      ${report.notes ? `<p class="muted">Notas: ${escapeHtml(report.notes)}</p>` : ''}
      <table class="papel" style="margin-top:0.75rem">
        <thead><tr>
          <th style="${TH}">Surtidor</th>
          <th style="${TH}">Boquilla</th>
          <th style="${TH}">Producto</th>
          <th style="${TH}">Lect. inicial</th>
          <th style="${TH}">Lect. final</th>
          <th style="${TH}">Volumen (L)</th>
          <th style="${TH}">Precio/L</th>
          <th style="${TH}">Total</th>
        </tr></thead>
        <tbody>${bodyRows || `<tr><td colspan="8" style="${TD}" class="muted">${NO_DATA}</td></tr>`}</tbody>
        <tfoot><tr>
          <td colspan="5" style="${TD}"><strong>Suma del detalle</strong></td>
          <td style="${TD}"><strong>${totalVolume.toFixed(2)}</strong></td>
          <td style="${TD}"></td>
          <td style="${TD}"><strong>${formatArs(totalCents)}</strong></td>
        </tr></tfoot>
      </table>
      <p><a href="/operativa/rendicion">Volver al listado</a></p>
    </div>`,
    { notice: aviso(c, 'Rendición guardada.') },
  );
});
