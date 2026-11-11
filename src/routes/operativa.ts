import { Hono } from 'hono';
import type { Context, Next } from 'hono';
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
  cashLines,
  dailyActivities,
  dailyActivitySummary,
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
  adicional: 'Adicional',
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

// Prefijos de columnas por turno en daily_activity_summary
const MANUAL_PREFIX: Record<Shift, 'manana' | 'tarde' | 'noche' | 'adicional'> = {
  morning: 'manana',
  evening: 'tarde',
  night: 'noche',
  adicional: 'adicional',
};

// Facturación de servicios/minimercado: carga manual del admin en el resumen diario
function manualFacturacion(
  summary: typeof dailyActivitySummary.$inferSelect | undefined,
  shift: Shift,
  tipo: 'Servicios' | 'MiniMercado',
): number {
  if (!summary) return 0;
  const p = MANUAL_PREFIX[shift];
  if (tipo === 'Servicios') {
    if (p === 'manana') return summary.mananaFacturacionServicios;
    if (p === 'tarde') return summary.tardeFacturacionServicios;
    if (p === 'noche') return summary.nocheFacturacionServicios;
    return summary.adicionalFacturacionServicios;
  }
  if (p === 'manana') return summary.mananaFacturacionMiniMercado;
  if (p === 'tarde') return summary.tardeFacturacionMiniMercado;
  if (p === 'noche') return summary.nocheFacturacionMiniMercado;
  return summary.adicionalFacturacionMiniMercado;
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

// El playero solo entra a /operativa y a la rendición del turno.
// Caja, ventas, actividades, sorteo y resumen diario son del admin.
async function playeroSoloRendicion(
  c: Context<AppEnv>,
  next: Next,
): Promise<Response | void> {
  const user = c.get('user');
  if (user?.role === 'store_manager') {
    const path = c.req.path;
    if (path !== '/operativa' && !path.startsWith('/operativa/rendicion')) {
      return render(
        c,
        'Sin permiso',
        `<div class="card">
          <h1>No tenés permiso para ver esta sección</h1>
          <p class="muted">Tu rol actual: Playero. Esta sección es solo del administrador.</p>
          <a class="btn" href="/app">Volver al inicio</a>
        </div>`,
        { status: 403 },
      );
    }
  }
  await next();
}

operativaRoutes.use(
  '/operativa',
  requireAuth(),
  requireRole('admin', 'store_manager'),
  playeroSoloRendicion,
);
operativaRoutes.use(
  '/operativa/*',
  requireAuth(),
  requireRole('admin', 'store_manager'),
  playeroSoloRendicion,
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

  const [cash, sales, daily, shift, raf] = await Promise.all([
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

  const isAdmin = c.get('user')?.role === 'admin';

  const rendicionCard = indexCard(
    'Rendición de turno',
    'Lecturas de surtidores y puente de medición.',
    '/operativa/rendicion',
    shift[0]
      ? `${fmtDay(shift[0].reportDate)} · Turno ${SHIFT_LABELS[shift[0].shift]} · Ventas ${formatArs(shift[0].totalSalesCents)}`
      : NO_DATA,
  );

  // El playero solo carga rendiciones; el admin ve todos los módulos.
  const cards = (
    isAdmin
      ? [
          indexCard(
            'Caja diaria',
            'Apertura y cierre de caja por día y tienda.',
            '/operativa/caja',
            cash[0]
              ? `${fmtDay(cash[0].reportDate)} · ${cash[0].status === 'open' ? 'Abierta' : 'Cerrada'} · Apertura ${formatArs(cash[0].openingCents)}${cash[0].differenceCents !== null ? ` · Diferencia ${formatArs(cash[0].differenceCents)}` : ''}`
              : NO_DATA,
          ),
          indexCard(
            'Resumen de ventas',
            'Totales por medio de pago, por día y turno.',
            '/operativa/ventas',
            sales[0]
              ? `${fmtDay(sales[0].reportDate)} · Turno ${SHIFT_LABELS[sales[0].shift]} · Total ${formatArs(sales[0].totalCents)}`
              : NO_DATA,
          ),
          indexCard(
            'Resumen diario de actividades',
            'Matriz de GNC y revisión de surtidores por turno.',
            '/operativa/actividades',
            daily[0]
              ? `${fmtDay(daily[0].reportDate)} · Turno ${SHIFT_LABELS[daily[0].shift]} · GNC ${daily[0].gncVolume} m³`
              : NO_DATA,
          ),
          rendicionCard,
          indexCard(
            'Sorteo',
            'Inscriptos automáticos al registrarse; marcá al ganador.',
            '/operativa/sorteo',
            raf[0]
              ? `Último: ${escapeHtml(raf[0].code)} · ${raf[0].winning ? 'Ganador' : 'Emitido'} · ${fmtMoment(raf[0].issuedAt)}`
              : NO_DATA,
          ),
        ]
      : [rendicionCard]
  ).join('');

  return render(
    c,
    'Operativa',
    `<div class="card">
      <h1>Operativa diaria</h1>
      <p class="muted">${isAdmin ? 'Seis formularios de la estación, ahora digitales.' : 'Cargá la rendición de tu turno.'} Tienda activa: <strong>${escapeHtml(storeName)}</strong>.</p>
      <a class="btn secondary" href="/app">Volver al inicio</a>
    </div>
    ${cards}`,
  );
});

const CAJA_MAX_FILAS = 60;

type CajaSide = 'entrada' | 'salida';
type CajaKind = 'movimiento' | 'cuenta' | 'referencia';
type CashRow = typeof cashDays.$inferSelect;
type LineRow = typeof cashLines.$inferSelect;
type SalesRow = typeof salesSummaries.$inferSelect;

function dateInput(d: Date): string {
  return `${d.getUTCFullYear()}-${pad2(d.getUTCMonth() + 1)}-${pad2(d.getUTCDate())}`;
}

function utcToday(): Date {
  const n = new Date();
  return new Date(Date.UTC(n.getUTCFullYear(), n.getUTCMonth(), n.getUTCDate()));
}

function asList(v: unknown): string[] {
  if (Array.isArray(v)) {
    return v.filter((x): x is string => typeof x === 'string');
  }
  return typeof v === 'string' ? [v] : [];
}

function movimientoRow(
  side: CajaSide,
  kind: CajaKind,
  detail: string,
  amount: string,
  sort: number,
): string {
  const kindLabel = kind === 'cuenta' ? ' (cuenta)' : kind === 'referencia' ? ' (ref.)' : '';
  const kindHidden = `<input type="hidden" name="${side}_k" value="${kind}">`;
  const sortHidden = `<input type="hidden" name="${side}_s" value="${sort}">`;
  return `<tr>
      <td>${kindHidden}${sortHidden}<input class="campo-subrayado" type="text" name="${side}_d" value="${escapeHtml(detail)}" placeholder="Detalle" maxlength="80" aria-label="Detalle"></td>
      <td class="num" style="white-space:nowrap">
        <select name="${side}_t" class="campo-subrayado" style="width:auto;min-width:8rem;margin-right:0.4rem;font-size:0.8rem">
          <option value="movimiento"${kind === 'movimiento' ? ' selected' : ''}>Movimiento</option>
          <option value="cuenta"${kind === 'cuenta' ? ' selected' : ''}>Cuenta</option>
          <option value="referencia"${kind === 'referencia' ? ' selected' : ''}>Referencia</option>
        </select>
        <input class="campo-subrayado" type="number" name="${side}_i" value="${amount}" min="0" step="0.01" inputmode="decimal" placeholder="0" style="text-align:right;max-width:7.5rem" aria-label="Importe">
        <button type="button" class="quitar" title="Quitar fila" aria-label="Quitar fila" style="margin-top:0;padding:0.05rem 0.5rem;font-size:0.8rem;line-height:1.5;background:transparent;color:#52525b;border:1px solid #d4d4d4;border-radius:999px">×</button>
      </td>
    </tr>`;
}

function movimientoColumn(
  side: CajaSide,
  title: string,
  lines: Array<{ detail: string; amountCents: number; kind: CajaKind }>,
  totalCents: number,
): string {
  const cells = lines.map((l, idx) =>
    movimientoRow(side, l.kind, l.detail, (l.amountCents / 100).toFixed(2), idx),
  );
  for (let i = lines.length; i < 6; i += 1) {
    cells.push(movimientoRow(side, 'movimiento', '', '', i));
  }
  return `<section>
      <table class="papel" data-side="${side}">
        <thead>
          <tr><th class="seccion" colspan="2" style="text-align:center">${title}</th></tr>
          <tr><th>Detalle</th><th style="text-align:right">Importe · Tipo</th></tr>
        </thead>
        <tbody>${cells.join('')}</tbody>
        <tfoot><tr>
          <td class="seccion">Total ${title}</td>
          <td class="num"><strong data-total="${side}">${formatArs(totalCents)}</strong></td>
        </tr></tfoot>
      </table>
      <button type="button" class="secondary" data-add="${side}" style="${SMALL_BTN}">+ Agregar fila</button>
    </section>`;
}

function controlCajaHtml(cash: CashRow | undefined, fechaInput: string): string {
  if (!cash) {
    return `<div class="seccion-papel">
      <h2>Control de caja</h2>
      <p class="muted">Todavía no hay caja para este día. Cargá la apertura para abrirla.</p>
      <form method="post" action="/operativa/caja">
        <input type="hidden" name="fecha" value="${fechaInput}">
        <label for="caja-apertura">Efectivo de apertura (pesos)</label>
        <input id="caja-apertura" name="apertura" type="number" step="0.01" min="0" required>
        <label for="caja-notas">Notas (opcional)</label>
        <input id="caja-notas" name="notas" type="text">
        <button type="submit">Abrí la caja</button>
      </form>
    </div>`;
  }
  const resumen = `<p class="muted" style="margin:0 0 0.5rem">Estado: <span class="pill"${cash.status === 'closed' ? ' style="background:#52525b"' : ''}>${cash.status === 'open' ? 'Abierta' : 'Cerrada'}</span>
      · Apertura: <strong>${formatArs(cash.openingCents)}</strong>
      ${cash.closingDeclaredCents !== null ? ` · Declarado: <strong>${formatArs(cash.closingDeclaredCents)}</strong>` : ''}
      ${cash.closingCountedCents !== null ? ` · Contado: <strong>${formatArs(cash.closingCountedCents)}</strong>` : ''}
      ${cash.differenceCents !== null ? ` · Diferencia: <strong>${formatArs(cash.differenceCents)}</strong>` : ''}
    </p>`;
  if (cash.status === 'open') {
    return `<div class="seccion-papel">
      <h2>Control de caja</h2>
      ${resumen}
      <form method="post" action="/operativa/caja/${cash.id}/cerrar">
        <label for="caja-declarado">Efectivo declarado (pesos)</label>
        <input id="caja-declarado" name="declarado" type="number" step="0.01" min="0" required>
        <label for="caja-contado">Efectivo contado (pesos)</label>
        <input id="caja-contado" name="contado" type="number" step="0.01" min="0" required>
        <label for="caja-cnotas">Notas de cierre</label>
        <input id="caja-cnotas" name="notas" type="text">
        <button type="submit">Cerrá la caja</button>
      </form>
    </div>`;
  }
  return `<div class="seccion-papel">
    <h2>Control de caja</h2>
    ${resumen}
  </div>`;
}

function historialHtml(rows: CashRow[]): string {
  if (rows.length === 0) return '';
  const bodyRows = rows
    .map(
      (r) => `<tr>
        <td style="${TD}">${fmtDay(r.reportDate)}</td>
        <td style="${TD}">${r.status === 'open' ? 'Abierta' : 'Cerrada'}</td>
        <td style="${TD_NUM}">${formatArs(r.openingCents)}</td>
        <td style="${TD_NUM}">${r.differenceCents !== null ? formatArs(r.differenceCents) : '—'}</td>
      </tr>`,
    )
    .join('');
  return `<details style="margin-top:1rem">
      <summary style="cursor:pointer;font-weight:600">Histórico de cajas</summary>
      <table class="papel" style="margin-top:0.5rem">
        <thead><tr>
          <th style="${TH}">Fecha</th>
          <th style="${TH}">Estado</th>
          <th style="${TH}">Apertura</th>
          <th style="${TH}">Diferencia</th>
        </tr></thead>
        <tbody>${bodyRows}</tbody>
      </table>
    </details>`;
}

// ---------- RESUMEN DE CAJA (solo lectura) ----------
operativaRoutes.get('/operativa/caja', async (c) => {
  const db = dbOf(c);
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const fecha = str(c.req.query('fecha'));
  const day = fecha ? dayStart(fecha) : null;
  if (fecha && !day) {
    return fail(c, 'Caja diaria', 'Ingresá una fecha válida.', '/operativa/caja');
  }
  const selected = day ?? utcToday();
  const fechaInput = dateInput(selected);

  const [cashRows, lineRows, turnos, historial] = await Promise.all([
    db
      .select()
      .from(cashDays)
      .where(
        and(
          eq(cashDays.storeId, access.storeId),
          gte(cashDays.reportDate, selected),
          lt(cashDays.reportDate, nextDay(selected)),
        ),
      )
      .limit(1),
    db
      .select()
      .from(cashLines)
      .where(
        and(
          eq(cashLines.storeId, access.storeId),
          eq(cashLines.reportDate, selected),
        ),
      )
      .orderBy(asc(cashLines.sort), asc(cashLines.id)),
    db
      .select()
      .from(salesSummaries)
      .where(
        and(
          eq(salesSummaries.storeId, access.storeId),
          gte(salesSummaries.reportDate, selected),
          lt(salesSummaries.reportDate, nextDay(selected)),
        ),
      )
      .orderBy(asc(salesSummaries.shift)),
    db
      .select()
      .from(cashDays)
      .where(eq(cashDays.storeId, access.storeId))
      .orderBy(desc(cashDays.reportDate), desc(cashDays.id))
      .limit(20),
  ]);

  const cash = cashRows[0];
  const prevOpening = cash ? cash.openingCents : 0;

  // Turnos: morning, evening, night, adicional → total_cents
  const turnoManana = turnos.find(t => t.shift === 'morning');
  const turnoTarde = turnos.find(t => t.shift === 'evening');
  const turnoNoche = turnos.find(t => t.shift === 'night');
  const turnoAdicional = turnos.find(t => t.shift === 'adicional');
  const totalTurnos =
    (turnoManana?.totalCents ?? 0) +
    (turnoTarde?.totalCents ?? 0) +
    (turnoNoche?.totalCents ?? 0) +
    (turnoAdicional?.totalCents ?? 0);

  // Líneas manuales
  const entradasManuales = lineRows.filter(l => l.side === 'entrada' && l.kind === 'movimiento');
  const salidasManuales = lineRows.filter(l => l.side === 'salida' && l.kind === 'movimiento');
  const cuentas = lineRows.filter(l => l.side === 'entrada' && l.kind === 'cuenta');
  const referencias = lineRows.filter(l => l.side === 'salida' && l.kind === 'referencia');

  const totalEntradasManuales = entradasManuales.reduce((a, l) => a + l.amountCents, 0);
  const totalSalidasManuales = salidasManuales.reduce((a, l) => a + l.amountCents, 0);
  const totalCuentas = cuentas.reduce((a, l) => a + l.amountCents, 0);
  // totalReferencias = referencias.reduce((a, l) => a + l.amountCents, 0); // no se usa en totales

  // Fórmulas del printeo legacy
  const totalEntradas = prevOpening + totalTurnos + totalEntradasManuales; // BANCO (cuentas) NO suma
  const totalSalidas = totalSalidasManuales; // referencias NO suman
  const totalDia = totalEntradas - totalSalidas;
  const totalNeto = totalDia - totalCuentas; // restar BANCO/cuentas

  const cabecera = `<div class="cabecera-papel" style="display:flex;justify-content:space-between;align-items:center;gap:0.75rem;flex-wrap:wrap">
      <h1>Caja</h1>
      <form method="get" action="/operativa/caja" class="row" style="gap:0.5rem;align-items:center">
        <input type="date" name="fecha" value="${fechaInput}" style="width:auto;margin-top:0" aria-label="Fecha">
        <button type="submit" style="${SMALL_BTN}">Mostrá</button>
      </form>
      <p class="sub" style="margin:0">Fecha: <strong>${fmtDay(selected)}</strong>${cash ? ` · <span class="pill"${cash.status === 'closed' ? ' style="background:#52525b"' : ''}>${cash.status === 'open' ? 'Abierta' : 'Cerrada'}</span>` : ''}</p>
    </div>`;

  // Filas de entrada para la tabla de solo lectura
  const entradasRows = [
    { label: 'SALDO DÍA ANTERIOR', amount: prevOpening, isSubtotal: false },
    { label: 'TURNO MAÑANA', amount: turnoManana?.totalCents ?? 0, isSubtotal: false },
    { label: 'TURNO TARDE', amount: turnoTarde?.totalCents ?? 0, isSubtotal: false },
    { label: 'TURNO NOCHE', amount: turnoNoche?.totalCents ?? 0, isSubtotal: false },
    { label: 'TURNO ADICIONAL', amount: turnoAdicional?.totalCents ?? 0, isSubtotal: false },
    { label: 'TOTAL TURNOS', amount: totalTurnos, isSubtotal: true },
  ].concat(
    entradasManuales.map(l => ({ label: l.detail.toUpperCase(), amount: l.amountCents, isSubtotal: false })),
    cuentas.map(l => ({ label: l.detail.toUpperCase() + ' (cuenta)', amount: l.amountCents, isSubtotal: false })),
  );

  const salidasRows = [
    ...salidasManuales.map(l => ({ label: l.detail.toUpperCase(), amount: l.amountCents, isSubtotal: false })),
    ...referencias.map(l => ({ label: l.detail.toUpperCase() + ' (ref.)', amount: l.amountCents, isSubtotal: false })),
  ];

  function renderColumn(title: string, rows: Array<{ label: string; amount: number; isSubtotal?: boolean }>, totalCents: number): string {
    const cells = rows.map(r => `
      <tr${r.isSubtotal ? ' style="background:var(--paper-head)"' : ''}>
        <td${r.isSubtotal ? ' style="font-weight:700;text-transform:uppercase;font-size:0.8rem;letter-spacing:0.05em"' : ''}>${escapeHtml(r.label)}</td>
        <td class="num"${r.isSubtotal ? ' style="font-weight:700"' : ''}>${formatArs(r.amount)}</td>
      </tr>`).join('');
    return `<section>
      <table class="papel" data-side="${title.toLowerCase()}">
        <thead>
          <tr><th class="seccion" colspan="2" style="text-align:center">${title}</th></tr>
          <tr><th>Detalle</th><th style="text-align:right">Total</th></tr>
        </thead>
        <tbody>${cells}</tbody>
        <tfoot><tr>
          <td class="seccion">Total ${title}</td>
          <td class="num"><strong>${formatArs(totalCents)}</strong></td>
        </tr></tfoot>
      </table>
    </section>`;
  }

  const resumen = `<div class="grid">
      ${renderColumn('Entradas', entradasRows, totalEntradas)}
      ${renderColumn('Salidas', salidasRows, totalSalidas)}
    </div>
    <div style="display:flex;justify-content:flex-end;gap:1.5rem;flex-wrap:wrap;margin-top:0.85rem;border-top:2px solid var(--paper-border);padding-top:0.75rem;font-size:1.05rem">
      <p style="margin:0">Total del día: <strong>${formatArs(totalDia)}</strong></p>
      <p style="margin:0">Total Neto: <strong>${formatArs(totalNeto)}</strong></p>
    </div>
    <p class="muted" style="margin-top:0.5rem;font-size:0.85rem">Las filas se editan en <a href="/operativa/caja/movimientos?fecha=${fechaInput}">Movimientos de caja</a>.</p>`;

  return render(
    c,
    'Caja diaria',
    `<div class="card">
      ${cabecera}
      ${resumen}
    </div>
    ${controlCajaHtml(cash, fechaInput)}
    ${historialHtml(historial)}`,
    { notice: aviso(c, 'Resumen de caja guardado.') },
  );
});

// ---------- MOVIMIENTOS DE CAJA (carga/edición) ----------
operativaRoutes.get('/operativa/caja/movimientos', async (c) => {
  const db = dbOf(c);
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const fecha = str(c.req.query('fecha'));
  const day = fecha ? dayStart(fecha) : null;
  if (fecha && !day) {
    return fail(c, 'Movimientos de caja', 'Ingresá una fecha válida.', '/operativa/caja/movimientos');
  }
  const selected = day ?? utcToday();
  const fechaInput = dateInput(selected);

  const [cashRows, lineRows] = await Promise.all([
    db
      .select()
      .from(cashDays)
      .where(
        and(
          eq(cashDays.storeId, access.storeId),
          gte(cashDays.reportDate, selected),
          lt(cashDays.reportDate, nextDay(selected)),
        ),
      )
      .limit(1),
    db
      .select()
      .from(cashLines)
      .where(
        and(
          eq(cashLines.storeId, access.storeId),
          eq(cashLines.reportDate, selected),
        ),
      )
      .orderBy(asc(cashLines.sort), asc(cashLines.id)),
  ]);

  const cash = cashRows[0];
  const entradas = lineRows.filter(l => l.side === 'entrada').map(l => ({
    detail: l.detail,
    amountCents: l.amountCents,
    kind: l.kind as CajaKind,
  }));
  const salidas = lineRows.filter(l => l.side === 'salida').map(l => ({
    detail: l.detail,
    amountCents: l.amountCents,
    kind: l.kind as CajaKind,
  }));

  const totalEntradas = entradas.reduce((a, l) => a + l.amountCents, 0);
  const totalSalidas = salidas.reduce((a, l) => a + l.amountCents, 0);

  const cabecera = `<div class="cabecera-papel" style="display:flex;justify-content:space-between;align-items:center;gap:0.75rem;flex-wrap:wrap">
      <h1>Movimientos de caja</h1>
      <form method="get" action="/operativa/caja/movimientos" class="row" style="gap:0.5rem;align-items:center">
        <input type="date" name="fecha" value="${fechaInput}" style="width:auto;margin-top:0" aria-label="Fecha">
        <button type="submit" style="${SMALL_BTN}">Mostrá</button>
      </form>
      <p class="sub" style="margin:0">Fecha: <strong>${fmtDay(selected)}</strong></p>
    </div>`;

  const formHtml = `<form method="post" action="/operativa/caja/movimientos" id="form-movimientos">
      <input type="hidden" name="fecha" value="${fechaInput}">
      <div class="grid">
        ${movimientoColumn('entrada', 'Entradas', entradas, totalEntradas)}
        ${movimientoColumn('salida', 'Salidas', salidas, totalSalidas)}
      </div>
      <p class="muted" style="font-size:0.85rem;margin-top:0.5rem">
        <strong>Movimiento</strong> = suma en su columna |
        <strong>Cuenta</strong> (BANCO/monedas/ahorro) = no suma en Entradas, resta en Total Neto |
        <strong>Referencia</strong> (Venta M Pago) = se muestra pero no suma en Salidas
      </p>
      <button type="submit">Guardá los movimientos</button>
    </form>`;

  const movScript = `<script>
(function () {
  var form = document.getElementById('form-movimientos');
  if (!form) return;
  var fmt = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });
  function cents(input) {
    var v = parseFloat(String(input.value).replace(',', '.'));
    return isFinite(v) && v > 0 ? Math.round(v * 100) : 0;
  }
  function recalc() {
    var e = 0, s = 0;
    Array.prototype.forEach.call(form.querySelectorAll('table[data-side]'), function (t) {
      Array.prototype.forEach.call(t.querySelectorAll('tbody tr'), function (tr) {
        var input = tr.querySelector('input[type="number"]');
        var c = input ? cents(input) : 0;
        if (t.getAttribute('data-side') === 'entrada') e += c; else s += c;
      });
    });
    form.querySelector('[data-total="entrada"]').textContent = fmt.format(e / 100);
    form.querySelector('[data-total="salida"]').textContent = fmt.format(s / 100);
  }
  function bindRow(tr) {
    tr.addEventListener('input', recalc);
    var btn = tr.querySelector('.quitar');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var tbody = tr.parentNode;
      if (tbody.querySelectorAll('tr').length <= 1) {
        Array.prototype.forEach.call(tr.querySelectorAll('input'), function (i) { i.value = ''; });
        tr.querySelector('select').value = 'movimiento';
      } else {
        tbody.removeChild(tr);
      }
      recalc();
    });
  }
  Array.prototype.forEach.call(form.querySelectorAll('tbody tr'), bindRow);
  Array.prototype.forEach.call(form.querySelectorAll('[data-add]'), function (btn) {
    btn.addEventListener('click', function () {
      var tbody = form.querySelector('table[data-side="' + btn.getAttribute('data-add') + '"] tbody');
      var template = tbody.querySelector('tr').cloneNode(true);
      Array.prototype.forEach.call(template.querySelectorAll('input'), function (i) { i.value = ''; });
      template.querySelector('select').value = 'movimiento';
      tbody.appendChild(template);
      bindRow(template);
      var first = template.querySelector('input[type="text"]');
      if (first) first.focus();
      recalc();
    });
  });
  recalc();
})();
</script>`;

  return render(
    c,
    'Movimientos de caja',
    `<div class="card">
      ${cabecera}
      ${formHtml}
    </div>
    <p class="muted" style="margin-top:1rem"><a href="/operativa/caja?fecha=${fechaInput}">← Volver al resumen</a></p>
    ${movScript}`,
    { notice: aviso(c, 'Movimientos guardados.') },
  );
});

operativaRoutes.post('/operativa/caja/movimientos', async (c) => {
  const db = dbOf(c);
  const body = await c.req.parseBody({ all: true });
  const access = await resolveStore(c, db, body.tienda);
  if ('error' in access) return denied(c, access.error);
  const fecha = dayStart(str(body.fecha));
  if (!fecha) {
    return fail(
      c,
      'Movimientos de caja',
      'Ingresá una fecha válida (AAAA-MM-DD).',
      '/operativa/caja/movimientos',
    );
  }
  const back = `/operativa/caja/movimientos?fecha=${dateInput(fecha)}`;

  const lados: Array<{ side: CajaSide; detalles: string[]; importes: string[]; tipos: string[]; sorts: string[] }> = [
    { side: 'entrada', detalles: asList(body.entrada_d), importes: asList(body.entrada_i), tipos: asList(body.entrada_t), sorts: asList(body.entrada_s) },
    { side: 'salida', detalles: asList(body.salida_d), importes: asList(body.salida_i), tipos: asList(body.salida_t), sorts: asList(body.salida_s) },
  ];

  const filas: Array<{ side: CajaSide; detail: string; amountCents: number; kind: CajaKind; sort: number }> = [];
  for (const lado of lados) {
    const n = Math.max(lado.detalles.length, lado.importes.length, lado.tipos.length, lado.sorts.length);
    if (n > CAJA_MAX_FILAS) {
      return fail(
        c,
        'Movimientos de caja',
        `Cada columna admite hasta ${CAJA_MAX_FILAS} filas.`,
        back,
      );
    }
    for (let i = 0; i < n; i += 1) {
      const detalle = (lado.detalles[i] ?? '').trim();
      const importe = (lado.importes[i] ?? '').trim();
      const tipo = (lado.tipos[i] ?? 'movimiento') as CajaKind;
      const sort = parseInt(lado.sorts[i] ?? String(i), 10);
      if (!detalle && !importe) continue;
      if (!detalle) {
        return fail(
          c,
          'Movimientos de caja',
          'Ingresá el detalle de cada fila que tenga importe.',
          back,
        );
      }
      if (detalle.length > 80) {
        return fail(
          c,
          'Movimientos de caja',
          'El detalle de cada fila tiene hasta 80 caracteres.',
          back,
        );
      }
      if (!importe) {
        return fail(
          c,
          'Movimientos de caja',
          `Ingresá el importe de "${detalle}".`,
          back,
        );
      }
      const cents = centsFrom(importe);
      if (cents === null) {
        return fail(
          c,
          'Movimientos de caja',
          `El importe de "${detalle}" no es un monto válido en pesos.`,
          back,
        );
      }
      if (!['movimiento', 'cuenta', 'referencia'].includes(tipo)) {
        return fail(
          c,
          'Movimientos de caja',
          `Tipo "${tipo}" inválido.`,
          back,
        );
      }
      filas.push({ side: lado.side, detail: detalle, amountCents: cents, kind: tipo, sort });
    }
  }

  await db
    .delete(cashLines)
    .where(
      and(
        eq(cashLines.storeId, access.storeId),
        eq(cashLines.reportDate, fecha),
      ),
    );
  if (filas.length > 0) {
    await db.insert(cashLines).values(
      filas.map(f => ({
        storeId: access.storeId,
        reportDate: fecha,
        side: f.side,
        kind: f.kind,
        sort: f.sort,
        detail: f.detail,
        amountCents: f.amountCents,
      })),
    );
  }
  return c.redirect(`${back}&aviso=1`);
});

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
        <td style="${TD}">${escapeHtml(r.holderDni ?? '—')}</td>
        <td style="${TD}">${escapeHtml(r.holderAddress ?? '—')}</td>
        <td style="${TD}">${escapeHtml(r.holderPhone ?? '—')}</td>
        <td style="${TD}">${fmtMoment(r.issuedAt)}</td>
        <td style="${TD}">${r.winning ? `<span class="pill">Ganador</span>${r.drawnAt ? ` <span class="muted">${fmtMoment(r.drawnAt)}</span>` : ''}` : '<span class="muted">Inscripto</span>'}</td>
        <td style="${TD}">
          <details style="display:inline">
            <summary style="cursor:pointer;font-size:0.85rem">Editá</summary>
            <form method="post" action="/operativa/sorteo/${r.id}/editar" style="min-width:16rem">
              <label for="sn-${r.id}">Nombre del consumidor</label>
              <input id="sn-${r.id}" name="nombre" type="text" required value="${escapeHtml(r.holderName)}">
              <label for="sdi-${r.id}">DNI</label>
              <input id="sdi-${r.id}" name="dni" type="text" value="${escapeHtml(r.holderDni ?? '')}">
              <label for="sdir-${r.id}">Dirección</label>
              <input id="sdir-${r.id}" name="direccion" type="text" value="${escapeHtml(r.holderAddress ?? '')}">
              <label for="sp-${r.id}">Teléfono</label>
              <input id="sp-${r.id}" name="telefono" type="text" value="${escapeHtml(r.holderPhone ?? '')}">
              <label for="se-${r.id}">Email</label>
              <input id="se-${r.id}" name="email" type="text" value="${escapeHtml(r.holderEmail ?? '')}">
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
    'Sorteo',
    `<div class="card">
      <h1>Sorteo</h1>
      <p class="muted">Cada persona que se registra en la tienda queda inscripta automáticamente con un código único. Inscriptos: ${rows.length} · Ganadores: ${ganadores}.</p>
      ${filterBar('/operativa/sorteo', '', storesHtml)}
      <table class="papel" style="margin-top:0.75rem">
        <thead><tr>
          <th style="${TH}">Código</th>
          <th style="${TH}">Titular</th>
          <th style="${TH}">DNI</th>
          <th style="${TH}">Dirección</th>
          <th style="${TH}">Teléfono</th>
          <th style="${TH}">Inscripto</th>
          <th style="${TH}">Estado</th>
          <th style="${TH}">Acciones</th>
        </tr></thead>
        <tbody>${bodyRows || `<tr><td colspan="8" style="${TD}" class="muted">${NO_DATA}</td></tr>`}</tbody>
      </table>
    </div>`,
    { notice: aviso(c, 'Sorteo actualizado.') },
  );
});

operativaRoutes.post('/operativa/sorteo/:id/ganador', async (c) => {
  const db = dbOf(c);
  const user = c.get('user')!;
  const id = idFrom(c.req.param('id'));
  if (!id) return notFound(c, 'Sorteo', '/operativa/sorteo');
  const rows = await db
    .select()
    .from(raffleCoupons)
    .where(eq(raffleCoupons.id, id))
    .limit(1);
  const row = rows[0];
  if (!row) return notFound(c, 'Sorteo', '/operativa/sorteo');
  if (!(await canManageStore(db, user, row.storeId))) {
    return denied(c, 'No tenés permiso sobre la tienda de ese cupón.');
  }
  if (row.winning) {
    return fail(
      c,
      'Sorteo',
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
  if (!id) return notFound(c, 'Sorteo', '/operativa/sorteo');
  const rows = await db
    .select()
    .from(raffleCoupons)
    .where(eq(raffleCoupons.id, id))
    .limit(1);
  const row = rows[0];
  if (!row) return notFound(c, 'Sorteo', '/operativa/sorteo');
  if (!(await canManageStore(db, user, row.storeId))) {
    return denied(c, 'No tenés permiso sobre la tienda de ese cupón.');
  }
  const body = await c.req.parseBody();
  const nombre = str(body.nombre);
  if (nombre.length < 2) {
    return fail(
      c,
      'Sorteo',
      'Ingresá el nombre de quien recibe el cupón.',
      '/operativa/sorteo',
    );
  }
  const dni = str(body.dni);
  if (dni.length < 7) {
    return fail(
      c,
      'Sorteo',
      'Ingresá un DNI válido (mínimo 7 dígitos).',
      '/operativa/sorteo',
    );
  }
  const direccion = str(body.direccion);
  if (direccion.length < 5) {
    return fail(
      c,
      'Sorteo',
      'Ingresá la dirección del consumidor.',
      '/operativa/sorteo',
    );
  }
  const email = str(body.email);
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return fail(
      c,
      'Sorteo',
      'Ingresá un email válido o dejalo vacío.',
      '/operativa/sorteo',
    );
  }
  await db
    .update(raffleCoupons)
    .set({
      holderName: nombre,
      holderDni: dni,
      holderAddress: direccion,
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
  return c.redirect(
    `/operativa/caja?fecha=${dateInput(row.reportDate)}&aviso=1`,
  );
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
      <h1>Resumen de ventas</h1>
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
          <th style="${TH}">Revisión surtidor</th>
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
          <input type="checkbox" name="revision" value="on" style="width:auto"> Revisión de surtidores hecha
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

operativaRoutes.get('/operativa/resumen-diario', requireRole('admin'), async (c) => {
  const db = dbOf(c);
  const access = await resolveStore(c, db, c.req.query('tienda'));
  if ('error' in access) return denied(c, access.error);
  const fecha = str(c.req.query('fecha'));
  const day = fecha ? dayStart(fecha) : null;
  if (fecha && !day) {
    return fail(
      c,
      'Resumen diario de actividades',
      'Ingresá una fecha válida.',
      '/operativa/resumen-diario',
    );
  }
  const selected = day ?? utcToday();
  const fechaInput = dateInput(selected);

  // Get all data for the day
  const [activities, shiftReportsData, cash] = await Promise.all([
    db
      .select()
      .from(dailyActivities)
      .where(
        and(
          eq(dailyActivities.storeId, access.storeId),
          gte(dailyActivities.reportDate, selected),
          lt(dailyActivities.reportDate, nextDay(selected)),
        ),
      )
      .orderBy(asc(dailyActivities.shift)),
    db
      .select()
      .from(shiftReports)
      .where(
        and(
          eq(shiftReports.storeId, access.storeId),
          gte(shiftReports.reportDate, selected),
          lt(shiftReports.reportDate, nextDay(selected)),
        ),
      )
      .orderBy(asc(shiftReports.shift)),
    db
      .select()
      .from(cashDays)
      .where(
        and(
          eq(cashDays.storeId, access.storeId),
          gte(cashDays.reportDate, selected),
          lt(cashDays.reportDate, nextDay(selected)),
        ),
      )
      .limit(1),
  ]);

  // Organize by shift
  const shifts = ['morning', 'evening', 'night', 'adicional'] as const;
  const shiftLabels: Record<string, string> = {
    morning: 'Mañana',
    evening: 'Tarde',
    night: 'Noche',
    adicional: 'Adicional',
  };

  // Resumen diario guardado: observaciones + facturación manual del admin
  const existingSummary = await db
    .select()
    .from(dailyActivitySummary)
    .where(
      and(
        eq(dailyActivitySummary.storeId, access.storeId),
        eq(dailyActivitySummary.reportDate, selected),
      ),
    )
    .limit(1);
  const summary = existingSummary[0];
  const observaciones = summary?.observaciones ?? '';

  // Build summary rows for each shift
  const summaryRows = shifts.map(shift => {
    const act = activities.find(a => a.shift === shift);
    const sr = shiftReportsData.find(s => s.shift === shift);

    const gncIngreso = act?.gncOpening ?? 0;
    const gncVentasM3 = act?.gncVolume ?? 0;
    // Merma = diferencia del puente de medición (entrante − saliente) de la rendición
    const gncMermasExcedencias = sr ? sr.puenteEntrante - sr.puenteSaliente : 0;
    const gncPorcentaje = gncIngreso > 0 ? ((gncMermasExcedencias / gncIngreso) * 100) : 0;
    const gncPrecioUnitario = act?.gncSalesCents && gncVentasM3 > 0 
      ? Math.round(act.gncSalesCents / gncVentasM3) 
      : 0;
    const gncImporteTotal = act?.gncSalesCents ?? 0;
    // Planilla: Imp. ventas lubricantes y varios = rubro de la rendición del turno
    const lubricantesVariosImporte =
      (sr?.lubricantesSalesCents ?? 0) + (sr?.variosSalesCents ?? 0);
    // Planilla: Facturado total playeros = GNC + lubricantes y varios
    const facturadoPlayeros = gncImporteTotal + lubricantesVariosImporte;
    // Carga manual del admin (columnas por turno de daily_activity_summary)
    const facturacionServicios = manualFacturacion(summary, shift, 'Servicios');
    const facturacionMiniMercado = manualFacturacion(summary, shift, 'MiniMercado');
    // Planilla: Total recaudación = facturado playeros + servicios + mini mercado
    const totalRecaudacion =
      facturadoPlayeros + facturacionServicios + facturacionMiniMercado;

    return {
      shift,
      label: shiftLabels[shift],
      gncIngreso,
      gncMermasExcedencias,
      gncPorcentaje,
      gncVentasM3,
      gncPrecioUnitario,
      gncImporteTotal,
      lubricantesVariosImporte,
      facturadoPlayeros,
      facturacionServicios,
      facturacionMiniMercado,
      totalRecaudacion,
    };
  });

  // Columna TOTALES: suma de los cuatro turnos del día
  const totals = summaryRows.reduce(
    (acc, r) => ({
      gncIngreso: acc.gncIngreso + r.gncIngreso,
      gncMermasExcedencias: acc.gncMermasExcedencias + r.gncMermasExcedencias,
      gncVentasM3: acc.gncVentasM3 + r.gncVentasM3,
      gncImporteTotal: acc.gncImporteTotal + r.gncImporteTotal,
      lubricantesVariosImporte: acc.lubricantesVariosImporte + r.lubricantesVariosImporte,
      facturadoPlayeros: acc.facturadoPlayeros + r.facturadoPlayeros,
      facturacionServicios: acc.facturacionServicios + r.facturacionServicios,
      facturacionMiniMercado: acc.facturacionMiniMercado + r.facturacionMiniMercado,
      totalRecaudacion: acc.totalRecaudacion + r.totalRecaudacion,
    }),
    {
      gncIngreso: 0,
      gncMermasExcedencias: 0,
      gncVentasM3: 0,
      gncImporteTotal: 0,
      lubricantesVariosImporte: 0,
      facturadoPlayeros: 0,
      facturacionServicios: 0,
      facturacionMiniMercado: 0,
      totalRecaudacion: 0,
    },
  );
  const totalsPorcentaje =
    totals.gncIngreso > 0 ? (totals.gncMermasExcedencias / totals.gncIngreso) * 100 : 0;
  const totalsPrecioUnitario =
    totals.gncImporteTotal > 0 && totals.gncVentasM3 > 0
      ? Math.round(totals.gncImporteTotal / totals.gncVentasM3)
      : 0;

  const storesHtml = await storeOptions(db, access.storeId);

  return render(
    c,
    'Resumen diario de actividades',
    `<div class="card">
      <h1>Resumen diario de actividades</h1>
      <form method="get" action="/operativa/resumen-diario" class="row" style="gap:0.5rem;align-items:center">
        <input type="date" name="fecha" value="${fechaInput}" style="width:auto;margin-top:0" aria-label="Fecha">
        <select name="tienda" style="width:auto;margin-top:0" aria-label="Tienda">${storesHtml}</select>
        <button type="submit" style="${SMALL_BTN}">Mostrá</button>
      </form>
      <p class="muted" style="margin-top:0.5rem">Fecha: <strong>${fmtDay(selected)}</strong></p>
      
      <form method="post" action="/operativa/resumen-diario/facturacion">
        <input type="hidden" name="fecha" value="${fechaInput}">
        <div style="overflow-x:auto">
        <table class="papel" style="margin-top:0.75rem;font-size:0.8rem">
          <thead>
            <tr>
              <th style="${TH}" rowspan="2">Detalle</th>
              ${summaryRows.map(r => `<th style="${TH}" colspan="2">${r.label}</th>`).join('')}
              <th style="${TH}" colspan="2">TOTALES</th>
            </tr>
            <tr>
              ${summaryRows.map(() => `<th style="${TH}">m³</th><th style="${TH}">$</th>`).join('')}
              <th style="${TH}">m³</th><th style="${TH}">$</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="${TD}"><strong>Ingreso GNC (m³)</strong></td>
              ${summaryRows.map(r => `<td style="${TD_NUM}">${r.gncIngreso}</td><td></td>`).join('')}
              <td style="${TD_NUM}">${totals.gncIngreso}</td><td></td>
            </tr>
            <tr>
              <td style="${TD}"><strong>Mermas/Excedencias (m³)</strong></td>
              ${summaryRows.map(r => `<td style="${TD_NUM}">${r.gncMermasExcedencias >= 0 ? '+' : ''}${r.gncMermasExcedencias}</td><td></td>`).join('')}
              <td style="${TD_NUM}">${totals.gncMermasExcedencias >= 0 ? '+' : ''}${totals.gncMermasExcedencias}</td><td></td>
            </tr>
            <tr>
              <td style="${TD}"><strong>% Mermas/Excedencias</strong></td>
              ${summaryRows.map(r => `<td style="${TD_NUM}">${r.gncPorcentaje.toFixed(2)}%</td><td></td>`).join('')}
              <td style="${TD_NUM}">${totalsPorcentaje.toFixed(2)}%</td><td></td>
            </tr>
            <tr>
              <td style="${TD}"><strong>Ventas GNC (m³)</strong></td>
              ${summaryRows.map(r => `<td style="${TD_NUM}">${r.gncVentasM3}</td><td></td>`).join('')}
              <td style="${TD_NUM}">${totals.gncVentasM3}</td><td></td>
            </tr>
            <tr>
              <td style="${TD}"><strong>Precio unitario GNC ($/m³)</strong></td>
              ${summaryRows.map(r => `<td></td><td style="${TD_NUM}">${formatArs(r.gncPrecioUnitario)}</td>`).join('')}
              <td></td><td style="${TD_NUM}">${formatArs(totalsPrecioUnitario)}</td>
            </tr>
            <tr>
              <td style="${TD}"><strong>Importe total GNC</strong></td>
              ${summaryRows.map(r => `<td></td><td style="${TD_NUM}"><strong>${formatArs(r.gncImporteTotal)}</strong></td>`).join('')}
              <td></td><td style="${TD_NUM}"><strong>${formatArs(totals.gncImporteTotal)}</strong></td>
            </tr>
            <tr>
              <td style="${TD}"><strong>Importe lubricantes y varios</strong></td>
              ${summaryRows.map(r => `<td></td><td style="${TD_NUM}"><strong>${formatArs(r.lubricantesVariosImporte)}</strong></td>`).join('')}
              <td></td><td style="${TD_NUM}"><strong>${formatArs(totals.lubricantesVariosImporte)}</strong></td>
            </tr>
            <tr>
              <td style="${TD}"><strong>Facturado playeros</strong></td>
              ${summaryRows.map(r => `<td></td><td style="${TD_NUM}">${formatArs(r.facturadoPlayeros)}</td>`).join('')}
              <td></td><td style="${TD_NUM}">${formatArs(totals.facturadoPlayeros)}</td>
            </tr>
            <tr>
              <td style="${TD}"><strong>Facturación servicios/materiales</strong></td>
              ${summaryRows.map(r => `<td></td><td style="${TD_NUM}"><input type="number" name="servicios_${r.shift}" step="0.01" min="0" value="${(r.facturacionServicios / 100).toFixed(2)}" style="width:7rem;margin:0;text-align:right" aria-label="Servicios ${r.label}"></td>`).join('')}
              <td></td><td style="${TD_NUM}">${formatArs(totals.facturacionServicios)}</td>
            </tr>
            <tr>
              <td style="${TD}"><strong>Facturación mini mercado</strong></td>
              ${summaryRows.map(r => `<td></td><td style="${TD_NUM}"><input type="number" name="mini_${r.shift}" step="0.01" min="0" value="${(r.facturacionMiniMercado / 100).toFixed(2)}" style="width:7rem;margin:0;text-align:right" aria-label="Mini mercado ${r.label}"></td>`).join('')}
              <td></td><td style="${TD_NUM}">${formatArs(totals.facturacionMiniMercado)}</td>
            </tr>
            <tr style="background:var(--paper-head)">
              <td style="${TD}"><strong>TOTAL RECAUDACIÓN</strong></td>
              ${summaryRows.map(r => `<td></td><td style="${TD_NUM}"><strong>${formatArs(r.totalRecaudacion)}</strong></td>`).join('')}
              <td></td><td style="${TD_NUM}"><strong>${formatArs(totals.totalRecaudacion)}</strong></td>
            </tr>
          </tbody>
        </table>
        </div>
        <p class="muted" style="margin:0.5rem 0 0;font-size:0.8rem">Completá los importes de facturación de servicios y mini mercado (carga manual) y guardá.</p>
        <button type="submit" style="margin-top:0.5rem">Guardar facturación</button>
      </form>
      
      <div class="seccion-papel" style="margin-top:1rem">
        <h2>Observaciones</h2>
        <form method="post" action="/operativa/resumen-diario/observaciones">
          <input type="hidden" name="fecha" value="${fechaInput}">
          <textarea name="observaciones" rows="4" style="width:100%;font-family:inherit;padding:0.5rem">${escapeHtml(observaciones)}</textarea>
          <button type="submit" style="margin-top:0.5rem">Guardar observaciones</button>
        </form>
      </div>
      
      ${cash[0] ? `
      <div class="seccion-papel" style="margin-top:1rem">
        <h2>Depósito bancario</h2>
        <p class="muted">Importe total depositado: <strong>${formatArs(cash[0].closingCountedCents ?? 0)}</strong></p>
        <p class="muted">Recibo: ${cash[0].notes ?? '—'}</p>
        <p class="muted">Fecha depósito: ${cash[0].closedAt ? fmtDay(new Date(cash[0].closedAt)) : '—'}</p>
        <p class="muted">Responsable: ${cash[0].closedBy ? 'ID: ' + cash[0].closedBy : '—'}</p>
      </div>
      ` : ''}
    </div>`,
    { notice: aviso(c, 'Resumen diario guardado.') },
  );
});

operativaRoutes.post('/operativa/resumen-diario/observaciones', requireRole('admin'), async (c) => {
  const db = dbOf(c);
  const body = await c.req.parseBody();
  const access = await resolveStore(c, db, body.tienda);
  if ('error' in access) return denied(c, access.error);
  const fecha = dayStart(str(body.fecha));
  if (!fecha) {
    return fail(c, 'Resumen diario', 'Fecha inválida', '/operativa/resumen-diario');
  }
  const existing = await db
    .select()
    .from(dailyActivitySummary)
    .where(
      and(
        eq(dailyActivitySummary.storeId, access.storeId),
        eq(dailyActivitySummary.reportDate, fecha),
      ),
    )
    .limit(1);
  const observaciones = str(body.observaciones);
  if (existing[0]) {
    await db
      .update(dailyActivitySummary)
      .set({ observaciones, updatedAt: new Date() })
      .where(eq(dailyActivitySummary.id, existing[0].id));
  } else {
    await db.insert(dailyActivitySummary).values({
      storeId: access.storeId,
      reportDate: fecha,
      observaciones,
    });
  }
  return c.redirect(`/operativa/resumen-diario?fecha=${dateInput(fecha)}&aviso=1`);
});

operativaRoutes.post('/operativa/resumen-diario/facturacion', requireRole('admin'), async (c) => {
  const db = dbOf(c);
  const body = await c.req.parseBody();
  const access = await resolveStore(c, db, body.tienda);
  if ('error' in access) return denied(c, access.error);
  const fecha = dayStart(str(body.fecha));
  if (!fecha) {
    return fail(c, 'Resumen diario', 'Fecha inválida', '/operativa/resumen-diario');
  }
  const campos = [
    'servicios_morning',
    'mini_morning',
    'servicios_evening',
    'mini_evening',
    'servicios_night',
    'mini_night',
    'servicios_adicional',
    'mini_adicional',
  ];
  const importes: Record<string, number> = {};
  for (const campo of campos) {
    const valor = centsOr0(body[campo]);
    if (valor === null) {
      return fail(
        c,
        'Resumen diario',
        'Los importes de facturación deben ser números válidos en pesos.',
        '/operativa/resumen-diario',
      );
    }
    importes[campo] = valor;
  }
  const updates = {
    mananaFacturacionServicios: importes.servicios_morning,
    mananaFacturacionMiniMercado: importes.mini_morning,
    tardeFacturacionServicios: importes.servicios_evening,
    tardeFacturacionMiniMercado: importes.mini_evening,
    nocheFacturacionServicios: importes.servicios_night,
    nocheFacturacionMiniMercado: importes.mini_night,
    adicionalFacturacionServicios: importes.servicios_adicional,
    adicionalFacturacionMiniMercado: importes.mini_adicional,
    updatedAt: new Date(),
  };
  const existing = await db
    .select({ id: dailyActivitySummary.id })
    .from(dailyActivitySummary)
    .where(
      and(
        eq(dailyActivitySummary.storeId, access.storeId),
        eq(dailyActivitySummary.reportDate, fecha),
      ),
    )
    .limit(1);
  if (existing[0]) {
    await db
      .update(dailyActivitySummary)
      .set(updates)
      .where(eq(dailyActivitySummary.id, existing[0].id));
  } else {
    await db.insert(dailyActivitySummary).values({
      storeId: access.storeId,
      reportDate: fecha,
      ...updates,
    });
  }
  return c.redirect(`/operativa/resumen-diario?fecha=${dateInput(fecha)}&aviso=1`);
});

type ReadingInput = {
  surtidor: number;
  product: Fuel;
  opening: number;
  closing: number;
  priceCents: number;
};

const READING_ROWS = 3;

function parseReadings(
  body: Record<string, unknown>,
): ReadingInput[] | string {
  const out: ReadingInput[] = [];
  const seen = new Set<number>();
  let any = false;
  for (let i = 0; i < READING_ROWS; i++) {
    const sur = str(body[`r${i}_surtidor`]);
    const prod = str(body[`r${i}_producto`]);
    const ini = str(body[`r${i}_apertura`]);
    const fin = str(body[`r${i}_cierre`]);
    const precio = str(body[`r${i}_precio`]);
    if (!sur && !prod && !ini && !fin && !precio) continue;
    any = true;
    const label = `En la fila ${i + 1}`;
    const s = Number(sur);
    if (!Number.isInteger(s) || s < 1 || s > 3) {
      return `${label}: el surtidor debe ser 1, 2 o 3.`;
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
    if (seen.has(s)) {
      return `${label}: repetís el surtidor ${s}.`;
    }
    seen.add(s);
    out.push({
      surtidor: s,
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
    [1, 2, 3].map((n) => `<option value="${n}">${n}</option>`).join('');
  const fuelOpts = `<option value="">—</option>` + fuelOptions();
  let out = '';
  for (let i = 0; i < READING_ROWS; i++) {
    out += `<div class="row" style="border-top:1px solid #e4e4e7;padding-top:0.5rem;margin-top:0.5rem">
      <div style="flex:1;min-width:6rem">
        <label for="r${i}_surtidor">Surtidor</label>
        <select id="r${i}_surtidor" name="r${i}_surtidor">${surtidorOpts}</select>
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
        <td style="${TD}">${r.puenteEntrante}</td>
        <td style="${TD}">${r.puenteSaliente}</td>
        <td style="${TD}">${formatArs(r.totalSalesCents)}</td>
        <td style="${TD}">${formatArs(r.totalEnsobradoCents)}</td>
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
      <p class="muted">Encabezado del turno, puente de medición y lecturas de cada surtidor.</p>
      ${filterBar('/operativa/rendicion', fecha, storesHtml)}
      <a class="btn" href="/operativa/rendicion/nuevo">Cargá una rendición</a>
      <table class="papel" style="margin-top:0.75rem">
        <thead><tr>
          <th style="${TH}">Fecha</th>
          <th style="${TH}">Turno</th>
          <th style="${TH}">Inicio</th>
          <th style="${TH}">Fin</th>
          <th style="${TH}">Puente entr.</th>
          <th style="${TH}">Puente sal.</th>
          <th style="${TH}">Ventas</th>
          <th style="${TH}">Ensobrado</th>
          <th style="${TH}">Detalle</th>
        </tr></thead>
        <tbody>${bodyRows || `<tr><td colspan="9" style="${TD}" class="muted">${NO_DATA}</td></tr>`}</tbody>
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
  return render(
    c,
    'Nueva rendición de turno',
    `<div class="card">
      <h1>Rendición de turno</h1>
      <form method="post" action="/operativa/rendicion">
        <h2>Datos del turno</h2>
        <div class="grid">
          <div>
            <label for="fecha">Fecha</label>
            <input id="fecha" name="fecha" type="date" required value="${todayInput()}">
          </div>
          <div>
            <label for="turno">Turno</label>
            <select id="turno" name="turno">${shiftOptions()}</select>
          </div>
          <div>
            <label for="operador">Operador</label>
            <input id="operador" name="operador" type="text" required value="${escapeHtml(user.name)}" readonly>
          </div>
        </div>
        <div class="grid">
          <div>
            <label for="inicio">Inicio del turno</label>
            <input id="inicio" name="inicio" type="datetime-local" required>
          </div>
          <div>
            <label for="fin">Fin del turno</label>
            <input id="fin" name="fin" type="datetime-local" required>
          </div>
        </div>
        
        <h2 style="margin-top:1.25rem">Lecturas de surtidores (GNC)</h2>
        <p class="muted">Completá una fila por surtidor usado en el turno.</p>
        ${readingsFormHtml()}
        
        <h2 style="margin-top:1.25rem">Ventas del turno</h2>
        <div class="grid">
          <div>
            <label for="gnc_qty">GNC - Cantidad (m³)</label>
            <input id="gnc_qty" name="gnc_qty" type="number" step="1" min="0" value="0">
          </div>
          <div>
            <label for="gnc_price">GNC - Precio unitario (pesos/m³)</label>
            <input id="gnc_price" name="gnc_price" type="number" step="0.01" min="0" value="0">
          </div>
          <div>
            <label for="gnc_sales">GNC - Importe (pesos)</label>
            <input id="gnc_sales" name="gnc_sales" type="number" step="0.01" min="0" value="0" readonly style="background:#f4f4f5">
          </div>
          <div>
            <label for="lubricantes_sales">Lubricantes - Importe total (pesos)</label>
            <input id="lubricantes_sales" name="lubricantes_sales" type="number" step="0.01" min="0" value="0">
          </div>
          <div>
            <label for="varios_qty">Varios - Cantidad</label>
            <input id="varios_qty" name="varios_qty" type="number" step="1" min="0" value="0">
          </div>
          <div>
            <label for="varios_price">Varios - Precio unitario (pesos)</label>
            <input id="varios_price" name="varios_price" type="number" step="0.01" min="0" value="0">
          </div>
          <div>
            <label for="varios_sales">Varios - Importe (pesos)</label>
            <input id="varios_sales" name="varios_sales" type="number" step="0.01" min="0" value="0" readonly style="background:#f4f4f5">
          </div>
          <div style="grid-column:1/-1">
            <label for="total_sales">Total ventas del turno (pesos)</label>
            <input id="total_sales" name="total_sales" type="number" step="0.01" min="0" value="0" readonly style="background:#f4f4f5;font-weight:700;font-size:1.1rem">
          </div>
        </div>
        
        <h2 style="margin-top:1.25rem">Recaudación ensobrada</h2>
        <p class="muted">Registrá cada sobre con su hora e importe.</p>
        <div class="grid">
          <div>
            <label for="sobre1_hora">Sobre 1 - Hora</label>
            <input id="sobre1_hora" name="sobre1_hora" type="time">
          </div>
          <div>
            <label for="sobre1_importe">Sobre 1 - Importe (pesos)</label>
            <input id="sobre1_importe" name="sobre1_importe" type="number" step="0.01" min="0" value="0">
          </div>
          <div>
            <label for="sobre2_hora">Sobre 2 - Hora</label>
            <input id="sobre2_hora" name="sobre2_hora" type="time">
          </div>
          <div>
            <label for="sobre2_importe">Sobre 2 - Importe (pesos)</label>
            <input id="sobre2_importe" name="sobre2_importe" type="number" step="0.01" min="0" value="0">
          </div>
          <div>
            <label for="sobre3_hora">Sobre 3 - Hora</label>
            <input id="sobre3_hora" name="sobre3_hora" type="time">
          </div>
          <div>
            <label for="sobre3_importe">Sobre 3 - Importe (pesos)</label>
            <input id="sobre3_importe" name="sobre3_importe" type="number" step="0.01" min="0" value="0">
          </div>
          <div>
            <label for="sobre4_hora">Sobre 4 - Hora</label>
            <input id="sobre4_hora" name="sobre4_hora" type="time">
          </div>
          <div>
            <label for="sobre4_importe">Sobre 4 - Importe (pesos)</label>
            <input id="sobre4_importe" name="sobre4_importe" type="number" step="0.01" min="0" value="0">
          </div>
          <div>
            <label for="sobre5_hora">Sobre 5 - Hora</label>
            <input id="sobre5_hora" name="sobre5_hora" type="time">
          </div>
          <div>
            <label for="sobre5_importe">Sobre 5 - Importe (pesos)</label>
            <input id="sobre5_importe" name="sobre5_importe" type="number" step="0.01" min="0" value="0">
          </div>
          <div>
            <label for="sobre6_hora">Sobre 6 - Hora</label>
            <input id="sobre6_hora" name="sobre6_hora" type="time">
          </div>
          <div>
            <label for="sobre6_importe">Sobre 6 - Importe (pesos)</label>
            <input id="sobre6_importe" name="sobre6_importe" type="number" step="0.01" min="0" value="0">
          </div>
          <div style="grid-column:1/-1">
            <label for="total_ensobrado">Total ensobrado (pesos)</label>
            <input id="total_ensobrado" name="total_ensobrado" type="number" step="0.01" min="0" value="0" readonly style="background:#f4f4f5;font-weight:700">
          </div>
        </div>
        
        <h2 style="margin-top:1.25rem">Puente de medición</h2>
        <p class="muted">Ingresá las lecturas del puente al inicio y al fin del turno (m³).</p>
        <div class="grid">
          <div>
            <label for="puente_entrante">Puente entrante (m³)</label>
            <input id="puente_entrante" name="puente_entrante" type="number" step="0.001" min="0" value="0" required>
          </div>
          <div>
            <label for="puente_saliente">Puente saliente (m³)</label>
            <input id="puente_saliente" name="puente_saliente" type="number" step="0.001" min="0" value="0" required>
          </div>
        </div>
        
        <p class="muted" style="margin-top:0.75rem">Nota: No podrán tener más de $20.000 por billetera en caso de robo. La diferencia mayor será abonada por ustedes.</p>
        
        <label for="notas">Observaciones (opcional)</label>
        <textarea id="notas" name="notas" rows="3" placeholder="Observaciones del turno..."></textarea>
        
        <button type="submit">Guardá la rendición</button>
      </form>
      <p><a href="/operativa/rendicion">Volver al listado</a></p>
    </div>
    <script>
      (function() {
        function cents(v) { return Math.round(parseFloat(v || 0) * 100); }
        function fmt(v) { return (v/100).toFixed(2); }
        function calc() {
          // Ventas por rubro: el importe se calcula en el servidor con la misma regla
          function importe(qid, pid) {
            const q = Math.round(parseFloat(document.getElementById(qid).value || 0));
            const p = cents(document.getElementById(pid).value);
            return Math.round(q * p);
          }
          const gnc = importe('gnc_qty', 'gnc_price');
          const lub = cents(document.getElementById('lubricantes_sales').value);
          const var_ = importe('varios_qty', 'varios_price');
          document.getElementById('gnc_sales').value = fmt(gnc);
          document.getElementById('varios_sales').value = fmt(var_);
          document.getElementById('total_sales').value = fmt(gnc + lub + var_);
          // Ensobrado
          let totalEns = 0;
          for (let i=1; i<=6; i++) {
            totalEns += cents(document.getElementById('sobre'+i+'_importe').value);
          }
          document.getElementById('total_ensobrado').value = fmt(totalEns);
        }
        document.querySelectorAll('input[type="number"]').forEach(function(el) {
          el.addEventListener('input', calc);
        });
        // Initial calc
        calc();
      })();
    </script>`,
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
  const puenteEntrante = floatFrom(body.puente_entrante);
  if (puenteEntrante === null) {
    return fail(
      c,
      'Rendición de turno',
      'El puente entrante no es un valor válido.',
      '/operativa/rendicion/nuevo',
    );
  }
  const puenteSaliente = floatFrom(body.puente_saliente);
  if (puenteSaliente === null) {
    return fail(
      c,
      'Rendición de turno',
      'El puente saliente no es un valor válido.',
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
  
  // Ventas por rubro: el importe se calcula SIEMPRE en el servidor
  const gncQty = qtyFrom(body.gnc_qty);
  const gncPriceCents = centsOr0(body.gnc_price);
  const lubricantesSalesCents = centsOr0(body.lubricantes_sales);
  const variosQty = qtyFrom(body.varios_qty);
  const variosPriceCents = centsOr0(body.varios_price);
  if (
    gncQty === null ||
    gncPriceCents === null ||
    lubricantesSalesCents === null ||
    variosQty === null ||
    variosPriceCents === null
  ) {
    return fail(
      c,
      'Rendición de turno',
      'Las cantidades o precios de los rubros no son válidos.',
      '/operativa/rendicion/nuevo',
    );
  }
  const gncSalesCents = Math.round(gncQty * gncPriceCents);
  const variosSalesCents = Math.round(variosQty * variosPriceCents);
  const totalSalesCents = gncSalesCents + lubricantesSalesCents + variosSalesCents;
  
  const sobre1Hora = str(body.sobre1_hora) || null;
  const sobre1ImporteCents = centsFrom(body.sobre1_importe) ?? 0;
  const sobre2Hora = str(body.sobre2_hora) || null;
  const sobre2ImporteCents = centsFrom(body.sobre2_importe) ?? 0;
  const sobre3Hora = str(body.sobre3_hora) || null;
  const sobre3ImporteCents = centsFrom(body.sobre3_importe) ?? 0;
  const sobre4Hora = str(body.sobre4_hora) || null;
  const sobre4ImporteCents = centsFrom(body.sobre4_importe) ?? 0;
  const sobre5Hora = str(body.sobre5_hora) || null;
  const sobre5ImporteCents = centsFrom(body.sobre5_importe) ?? 0;
  const sobre6Hora = str(body.sobre6_hora) || null;
  const sobre6ImporteCents = centsFrom(body.sobre6_importe) ?? 0;
  const totalEnsobradoCents = sobre1ImporteCents + sobre2ImporteCents + sobre3ImporteCents + sobre4ImporteCents + sobre5ImporteCents + sobre6ImporteCents;
  
  const observaciones = str(body.notas) || null;
  
  const inserted = await db
    .insert(shiftReports)
    .values({
      storeId: access.storeId,
      reportDate: fecha,
      shift: turno,
      shiftStart: inicio,
      shiftEnd: fin,
      cashierId: user.id,
      gncQty,
      gncPriceCents,
      gncSalesCents,
      lubricantesSalesCents,
      variosQty,
      variosPriceCents,
      variosSalesCents,
      totalSalesCents,
      sobre1Hora,
      sobre1ImporteCents,
      sobre2Hora,
      sobre2ImporteCents,
      sobre3Hora,
      sobre3ImporteCents,
      sobre4Hora,
      sobre4ImporteCents,
      sobre5Hora,
      sobre5ImporteCents,
      sobre6Hora,
      sobre6ImporteCents,
      totalEnsobradoCents,
      puenteEntrante,
      puenteSaliente,
      observaciones,
      notes: null,
    })
    .returning({ id: shiftReports.id });
  const reportId = inserted[0]!.id;
  await db.insert(shiftReportReadings).values(
    readings.map((r) => ({
      reportId,
      surtidor: r.surtidor,
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

  const sobres = [
    { n: 1, hora: report.sobre1Hora, cents: report.sobre1ImporteCents },
    { n: 2, hora: report.sobre2Hora, cents: report.sobre2ImporteCents },
    { n: 3, hora: report.sobre3Hora, cents: report.sobre3ImporteCents },
    { n: 4, hora: report.sobre4Hora, cents: report.sobre4ImporteCents },
    { n: 5, hora: report.sobre5Hora, cents: report.sobre5ImporteCents },
    { n: 6, hora: report.sobre6Hora, cents: report.sobre6ImporteCents },
  ];
  const sobreRows = sobres
    .map(
      (s) => `<tr>
        <td style="${TD}">Sobre ${s.n}</td>
        <td style="${TD}">${s.hora ? escapeHtml(s.hora) : '—'}</td>
        <td style="${TD_NUM}">${formatArs(s.cents)}</td>
      </tr>`,
    )
    .join('');

  const rubroRow = (label: string, qty: number, price: number, sales: number, unit: string) =>
    `<tr>
      <td style="${TD}">${label}</td>
      <td style="${TD_NUM}">${qty} ${unit}</td>
      <td style="${TD_NUM}">${formatArs(price)}</td>
      <td style="${TD_NUM}">${formatArs(sales)}</td>
    </tr>`;

  return render(
    c,
    'Detalle de rendición',
    `<div class="card">
      <h1>Rendición · ${fmtDay(report.reportDate)} · ${SHIFT_LABELS[report.shift]}</h1>
      <div class="row">
        <span class="pill">Inicio: ${MOMENT_FMT.format(report.shiftStart)}</span>
        <span class="pill">Fin: ${MOMENT_FMT.format(report.shiftEnd)}</span>
      </div>
      <table class="papel" style="margin-top:0.75rem">
        <thead><tr>
          <th style="${TH}">Surtidor</th>
          <th style="${TH}">Producto</th>
          <th style="${TH}">Lect. inicial</th>
          <th style="${TH}">Lect. final</th>
          <th style="${TH}">Volumen (L)</th>
          <th style="${TH}">Precio/L</th>
          <th style="${TH}">Total</th>
        </tr></thead>
        <tbody>${bodyRows || `<tr><td colspan="7" style="${TD}" class="muted">${NO_DATA}</td></tr>`}</tbody>
        <tfoot><tr>
          <td colspan="4" style="${TD}"><strong>Suma del detalle</strong></td>
          <td style="${TD}"><strong>${totalVolume.toFixed(2)}</strong></td>
          <td style="${TD}"></td>
          <td style="${TD}"><strong>${formatArs(totalCents)}</strong></td>
        </tr></tfoot>
      </table>

      <h2 style="margin-top:1.25rem">Ventas del turno</h2>
      <table class="papel">
        <thead><tr>
          <th style="${TH}">Rubro</th>
          <th style="${TH}">Cantidad</th>
          <th style="${TH}">Precio unitario</th>
          <th style="${TH}">Importe</th>
        </tr></thead>
        <tbody>
          ${rubroRow('GNC', report.gncQty, report.gncPriceCents, report.gncSalesCents, 'm³')}
          <tr>
            <td style="${TD}">Lubricantes</td>
            <td style="${TD_NUM}">—</td>
            <td style="${TD_NUM}">—</td>
            <td style="${TD_NUM}">${formatArs(report.lubricantesSalesCents)}</td>
          </tr>
          ${rubroRow('Varios', report.variosQty, report.variosPriceCents, report.variosSalesCents, 'un.')}
          <tr>
            <td style="${TD}"><strong>Total ventas</strong></td>
            <td style="${TD}"></td>
            <td style="${TD}"></td>
            <td style="${TD_NUM}"><strong>${formatArs(report.totalSalesCents)}</strong></td>
          </tr>
        </tbody>
      </table>

      <h2 style="margin-top:1.25rem">Recaudación ensobrada</h2>
      <table class="papel">
        <thead><tr>
          <th style="${TH}">Sobre</th>
          <th style="${TH}">Hora</th>
          <th style="${TH}">Importe</th>
        </tr></thead>
        <tbody>
          ${sobreRows}
          <tr>
            <td style="${TD}"><strong>Total ensobrado</strong></td>
            <td style="${TD}"></td>
            <td style="${TD_NUM}"><strong>${formatArs(report.totalEnsobradoCents)}</strong></td>
          </tr>
        </tbody>
      </table>

      <h2 style="margin-top:1.25rem">Puente de medición</h2>
      <div class="row">
        <span>Entrante: <strong>${report.puenteEntrante.toFixed(3)} m³</strong></span>
        <span>Saliente: <strong>${report.puenteSaliente.toFixed(3)} m³</strong></span>
      </div>
      <p class="muted" style="margin-top:0.35rem">No podrán tener más de $20.000 por billetera en caso de robo. La diferencia mayor será abonada por ustedes.</p>

      <h2 style="margin-top:1.25rem">Observaciones</h2>
      <p>${report.observaciones ? escapeHtml(report.observaciones) : report.notes ? escapeHtml(report.notes) : '<span class="muted">Sin observaciones.</span>'}</p>

      <p style="margin-top:1rem"><a href="/operativa/rendicion">Volver al listado</a></p>
    </div>`,
    { notice: aviso(c, 'Rendición guardada.') },
  );
});
