import { describe, expect, it } from 'vitest';
import { SELF, env } from 'cloudflare:test';

const BASE = 'https://gas-station.test';

function encode(data: Record<string, string>): string {
  return new URLSearchParams(data).toString();
}

async function post(
  path: string,
  data: Record<string, string>,
  cookie?: string,
) {
  return SELF.fetch(BASE + path, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      ...(cookie ? { cookie } : {}),
    },
    body: encode(data),
    redirect: 'manual',
  });
}

async function get(path: string, cookie?: string) {
  return SELF.fetch(BASE + path, {
    headers: cookie ? { cookie } : {},
    redirect: 'manual',
  });
}

function sessionCookie(res: Response): string {
  const cookies = res.headers.getSetCookie();
  const found = cookies
    .map((c) => c.split(';')[0] ?? '')
    .find((c) => c.startsWith('gs_session='));
  if (!found) throw new Error('no se recibió cookie de sesión');
  return found;
}

async function register(nombre: string, email: string): Promise<string> {
  const res = await post('/registro', {
    nombre,
    email,
    password: 'supersecret1',
    password2: 'supersecret1',
  });
  expect(res.status).toBe(302);
  return sessionCookie(res);
}

function formatArs(cents: number): string {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  }).format(cents / 100);
}

let seq = 0;
function uniq(suffix: string): string {
  seq += 1;
  return `${suffix}-${Date.now()}-${seq}`;
}

async function userIdByEmail(email: string): Promise<number> {
  const row = await env.DB.prepare('select id from users where email = ?')
    .bind(email)
    .first<{ id: number }>();
  if (!row) throw new Error('no existe el usuario');
  return row.id;
}

async function makeStore(name: string): Promise<number> {
  const res = await env.DB.prepare('insert into stores (name) values (?)')
    .bind(name)
    .run();
  return Number(res.meta.last_row_id);
}

async function makeProduct(opts: {
  name: string;
  slug: string;
  priceCents: number;
  stock: number | null;
}): Promise<number> {
  const res = await env.DB.prepare(
    'insert into products (name, slug, description, price_cents, stock) values (?, ?, ?, ?, ?)',
  )
    .bind(opts.name, opts.slug, '', opts.priceCents, opts.stock)
    .run();
  return Number(res.meta.last_row_id);
}

async function makeOrder(opts: {
  number: string;
  userId: number;
  storeId: number;
  gestorId: number | null;
  totalCents: number;
  proofKey: string | null;
}): Promise<number> {
  const res = await env.DB.prepare(
    `insert into orders (number, user_id, store_id, gestor_id, status, payment_method, subtotal_cents, discount_cents, total_cents, proof_key)
     values (?, ?, ?, ?, 'pending', 'transferencia', ?, 0, ?, ?)`,
  )
    .bind(
      opts.number,
      opts.userId,
      opts.storeId,
      opts.gestorId,
      opts.totalCents,
      opts.totalCents,
      opts.proofKey,
    )
    .run();
  return Number(res.meta.last_row_id);
}

async function addItem(
  orderId: number,
  productId: number,
  qty: number,
  unitPriceCents: number,
  nameSnapshot: string,
): Promise<void> {
  await env.DB.prepare(
    'insert into order_items (order_id, product_id, qty, unit_price_cents, name_snapshot) values (?, ?, ?, ?, ?)',
  )
    .bind(orderId, productId, qty, unitPriceCents, nameSnapshot)
    .run();
}

async function makeAdmin(): Promise<{ cookie: string; id: number }> {
  const email = `${uniq('admin-ordenes')}@test.com`;
  const cookie = await register('Admin Órdenes', email);
  const id = await userIdByEmail(email);
  await env.DB.prepare("update users set role = 'admin' where email = ?")
    .bind(email)
    .run();
  return { cookie, id };
}

type Escenario = {
  adminCookie: string;
  adminId: number;
  clienteId: number;
  clienteEmail: string;
  clienteCookie: string;
  gestorId: number;
  storeId: number;
  storeName: string;
  productId: number;
  orderId: number;
  number: string;
  proofKey: string;
};

async function crearEscenario(
  opts: { totalCents?: number; stock?: number; qty?: number } = {},
): Promise<Escenario> {
  const { cookie: adminCookie, id: adminId } = await makeAdmin();

  const clienteEmail = `${uniq('cliente-ordenes')}@test.com`;
  const clienteCookie = await register('Cliente Órdenes', clienteEmail);
  const clienteId = await userIdByEmail(clienteEmail);

  const gestorEmail = `${uniq('gestor-ordenes')}@test.com`;
  await register('Gestor Órdenes', gestorEmail);
  await env.DB.prepare("update users set role = 'store_manager' where email = ?")
    .bind(gestorEmail)
    .run();
  const gestorId = await userIdByEmail(gestorEmail);

  const storeName = `Estación ${uniq('centro')}`;
  const storeId = await makeStore(storeName);

  const qty = opts.qty ?? 2;
  const unitCents = 400000;
  const productId = await makeProduct({
    name: 'Aceite 20W50',
    slug: uniq('aceite-20w50'),
    priceCents: unitCents,
    stock: opts.stock ?? 5,
  });

  const totalCents = opts.totalCents ?? unitCents * qty;
  const number = `GS-TEST-${uniq('n')}`;
  const proofKey = `comprobantes/${number}/transferencia.pdf`;
  await env.R2.put(proofKey, new Uint8Array([1, 2, 3, 4]), {
    httpMetadata: { contentType: 'application/pdf' },
  });

  const orderId = await makeOrder({
    number,
    userId: clienteId,
    storeId,
    gestorId,
    totalCents,
    proofKey,
  });
  await addItem(orderId, productId, qty, unitCents, 'Aceite 20W50');

  return {
    adminCookie,
    adminId,
    clienteId,
    clienteEmail,
    clienteCookie,
    gestorId,
    storeId,
    storeName,
    productId,
    orderId,
    number,
    proofKey,
  };
}

async function orderRow(id: number): Promise<{
  status: string;
  notes: string | null;
  paid_at: number | null;
  approved_by: number | null;
}> {
  const row = await env.DB.prepare(
    'select status, notes, paid_at, approved_by from orders where id = ?',
  )
    .bind(id)
    .first();
  if (!row) throw new Error('no existe la orden');
  return row as {
    status: string;
    notes: string | null;
    paid_at: number | null;
    approved_by: number | null;
  };
}

async function userPoints(id: number): Promise<number> {
  const row = await env.DB.prepare('select points from users where id = ?')
    .bind(id)
    .first<{ points: number }>();
  if (!row) throw new Error('no existe el usuario');
  return row.points;
}

async function orderPointLogs(orderId: number): Promise<number> {
  const row = await env.DB.prepare(
    "select count(*) as n from point_logs where reason = 'order' and reference_type = 'order' and reference_id = ?",
  )
    .bind(orderId)
    .first<{ n: number }>();
  return row!.n;
}

async function productStock(id: number): Promise<number | null> {
  const row = await env.DB.prepare('select stock from products where id = ?')
    .bind(id)
    .first<{ stock: number | null }>();
  if (!row) throw new Error('no existe el producto');
  return row.stock;
}

describe('admin órdenes', () => {
  it('/admin/ordenes exige admin, lista la orden y filtra', async () => {
    const esc = await crearEscenario();

    const sinSesion = await get('/admin/ordenes');
    expect(sinSesion.status).toBe(302);
    expect(sinSesion.headers.get('location')).toBe('/login');

    const comoCliente = await get('/admin/ordenes', esc.clienteCookie);
    expect(comoCliente.status).toBe(403);

    const res = await get('/admin/ordenes', esc.adminCookie);
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain(esc.number);
    expect(html).toContain(esc.clienteEmail);
    expect(html).toContain(esc.storeName);
    expect(html).toContain('Pendiente');
    expect(html).toContain(formatArs(800000));
    expect(html).toContain(`href="/admin/comprobantes/${esc.orderId}"`);

    const pendientes = await get('/admin/ordenes?estado=pending', esc.adminCookie);
    expect(await pendientes.text()).toContain(esc.number);

    const pagadas = await get('/admin/ordenes?estado=paid', esc.adminCookie);
    expect(await pagadas.text()).not.toContain(esc.number);

    const porTienda = await get(
      `/admin/ordenes?tienda=${esc.storeId}`,
      esc.adminCookie,
    );
    expect(await porTienda.text()).toContain(esc.number);

    const porNumero = await get(
      `/admin/ordenes?q=${esc.number}`,
      esc.adminCookie,
    );
    expect(await porNumero.text()).toContain(esc.number);

    const porEmail = await get(
      `/admin/ordenes?q=${encodeURIComponent(esc.clienteEmail)}`,
      esc.adminCookie,
    );
    expect(await porEmail.text()).toContain(esc.number);

    const estadoInvalido = await get(
      '/admin/ordenes?estado=otracosa',
      esc.adminCookie,
    );
    expect(estadoInvalido.status).toBe(200);
    expect(await estadoInvalido.text()).toContain(esc.number);
  });

  it('el detalle muestra items, totales, comprobante y acciones', async () => {
    const esc = await crearEscenario();
    const res = await get(`/admin/ordenes/${esc.orderId}`, esc.adminCookie);
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain(esc.number);
    expect(html).toContain('Aceite 20W50');
    expect(html).toContain(formatArs(800000));
    expect(html).toContain(`src="/admin/comprobantes/${esc.orderId}"`);
    expect(html).toContain('Aprobá el pago');
    expect(html).toContain('Motivo del rechazo');

    const inexistente = await get('/admin/ordenes/999999', esc.adminCookie);
    expect(inexistente.status).toBe(404);
  });

  it('/admin/comprobantes/:id sirve el archivo solo a admins', async () => {
    const esc = await crearEscenario();

    const admin = await get(
      `/admin/comprobantes/${esc.orderId}`,
      esc.adminCookie,
    );
    expect(admin.status).toBe(200);
    expect(admin.headers.get('content-type')).toBe('application/pdf');
    const bytes = new Uint8Array(await admin.arrayBuffer());
    expect(Array.from(bytes)).toEqual([1, 2, 3, 4]);

    const comoCliente = await get(
      `/admin/comprobantes/${esc.orderId}`,
      esc.clienteCookie,
    );
    expect(comoCliente.status).toBe(403);

    const sinSesion = await get(`/admin/comprobantes/${esc.orderId}`);
    expect(sinSesion.status).toBe(302);
    expect(sinSesion.headers.get('location')).toBe('/login');

    const inexistente = await get(
      '/admin/comprobantes/999999',
      esc.adminCookie,
    );
    expect(inexistente.status).toBe(404);
  });

  it('aprobar marca paid y acredita puntos una sola vez', async () => {
    const esc = await crearEscenario();
    const puntosAntes = await userPoints(esc.clienteId);

    const res = await post(
      `/admin/ordenes/${esc.orderId}/aprobar`,
      {},
      esc.adminCookie,
    );
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe(
      `/admin/ordenes/${esc.orderId}?aviso=aprobada`,
    );

    const row = await orderRow(esc.orderId);
    expect(row.status).toBe('paid');
    expect(row.paid_at).toBeTruthy();
    expect(row.approved_by).toBe(esc.adminId);

    expect((await userPoints(esc.clienteId)) - puntosAntes).toBe(800);
    expect(await orderPointLogs(esc.orderId)).toBe(1);

    const segunda = await post(
      `/admin/ordenes/${esc.orderId}/aprobar`,
      {},
      esc.adminCookie,
    );
    expect(segunda.status).toBe(302);
    expect(await userPoints(esc.clienteId)).toBe(puntosAntes + 800);
    expect(await orderPointLogs(esc.orderId)).toBe(1);

    const detalle = await get(`/admin/ordenes/${esc.orderId}`, esc.adminCookie);
    const html = await detalle.text();
    expect(html).toContain('Pagada');
    expect(html).not.toContain('Aprobá el pago');
  });

  it('rechazar guarda el motivo, marca cancelled y devuelve stock', async () => {
    const esc = await crearEscenario({ stock: 3, qty: 2 });

    const res = await post(
      `/admin/ordenes/${esc.orderId}/rechazar`,
      { motivo: 'Comprobante ilegible' },
      esc.adminCookie,
    );
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe(
      `/admin/ordenes/${esc.orderId}?aviso=rechazada`,
    );

    const row = await orderRow(esc.orderId);
    expect(row.status).toBe('cancelled');
    expect(row.notes).toBe('Comprobante ilegible');
    expect(await productStock(esc.productId)).toBe(5);
    expect(await orderPointLogs(esc.orderId)).toBe(0);

    const aprobarTrasRechazo = await post(
      `/admin/ordenes/${esc.orderId}/aprobar`,
      {},
      esc.adminCookie,
    );
    expect(aprobarTrasRechazo.status).toBe(422);
    expect((await orderRow(esc.orderId)).status).toBe('cancelled');
  });

  it('rechazar sin motivo devuelve 422 y no toca la orden', async () => {
    const esc = await crearEscenario({ stock: 3, qty: 2 });

    const res = await post(
      `/admin/ordenes/${esc.orderId}/rechazar`,
      {},
      esc.adminCookie,
    );
    expect(res.status).toBe(422);
    expect(await res.text()).toContain('Ingresá el motivo del rechazo');

    const row = await orderRow(esc.orderId);
    expect(row.status).toBe('pending');
    expect(row.notes).toBeNull();
    expect(await productStock(esc.productId)).toBe(3);
  });
});
