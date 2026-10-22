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

function cookieNamed(res: Response, name: string): string | null {
  const found = res.headers
    .getSetCookie()
    .map((c) => c.split(';')[0] ?? '')
    .find((c) => c.startsWith(`${name}=`));
  return found ?? null;
}

function sessionCookie(res: Response): string {
  const found = cookieNamed(res, 'gs_session');
  if (!found) throw new Error('no se recibió cookie de sesión');
  return found;
}

async function register(nombre: string, email: string): Promise<string> {
  const password = 'supersecret1';
  const res = await post('/registro', {
    nombre,
    email,
    password,
    password2: password,
  });
  expect(res.status).toBe(302);
  return sessionCookie(res);
}

async function insertProduct(p: {
  name: string;
  slug: string;
  priceCents: number;
  stock: number | null;
  description?: string;
}): Promise<number> {
  const res = await env.DB.prepare(
    'insert into products (name, slug, description, price_cents, stock) values (?, ?, ?, ?, ?)',
  )
    .bind(p.name, p.slug, p.description ?? '', p.priceCents, p.stock)
    .run();
  return Number(res.meta.last_row_id);
}

async function addToCart(
  cookie: string,
  productId: number,
  qty: number,
): Promise<string> {
  const res = await post(
    '/carrito/agregar',
    { productId: String(productId), qty: String(qty) },
    cookie,
  );
  expect(res.status).toBe(302);
  const cart = cookieNamed(res, 'gs_cart');
  if (!cart) throw new Error('no se recibió cookie de carrito');
  return `${cookie}; ${cart}`;
}

function checkoutForm(
  opts: { cupon?: string; comprobante?: boolean } = {},
): FormData {
  const fd = new FormData();
  fd.append('cupon', opts.cupon ?? '');
  if (opts.comprobante) {
    const blob = new Blob([new Uint8Array([1, 2, 3, 4])], {
      type: 'application/pdf',
    });
    fd.append('comprobante', blob, 'transferencia.pdf');
  }
  return fd;
}

async function checkout(cookie: string, fd: FormData): Promise<Response> {
  return SELF.fetch(BASE + '/checkout', {
    method: 'POST',
    headers: { cookie },
    body: fd,
    redirect: 'manual',
  });
}

describe('shop', () => {
  it('/tienda sin sesión redirige a /login', async () => {
    const res = await get('/tienda');
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/login');
  });

  it('/tienda muestra productos y el carrito refleja el alta', async () => {
    const cookie = await register('Ana Tienda', 'ana.tienda@test.com');
    await insertProduct({
      name: 'Aceite Full Synthetic',
      slug: 'aceite-full-1',
      priceCents: 1250000,
      stock: 10,
    });
    const shop = await get('/tienda', cookie);
    expect(shop.status).toBe(200);
    const html = await shop.text();
    expect(html).toContain('Aceite Full Synthetic');
    expect(html).toContain(
      new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
      }).format(12500),
    );

    const withCart = await addToCart(cookie, 1, 2);
    const cart = await get('/carrito', withCart);
    expect(cart.status).toBe(200);
    const cartHtml = await cart.text();
    expect(cartHtml).toContain('Aceite Full Synthetic');
    expect(cartHtml).toContain('Subtotal');
  });

  it('checkout con comprobante crea orden pendiente, sube archivo a R2 y baja stock', async () => {
    const cookie = await register('Bruno Checkout', 'bruno.checkout@test.com');
    const productId = await insertProduct({
      name: 'Filtro de Aceite',
      slug: 'filtro-aceite-1',
      priceCents: 800000,
      stock: 5,
    });
    const withCart = await addToCart(cookie, productId, 2);

    const res = await checkout(withCart, checkoutForm({ comprobante: true }));
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/ordenes?aviso=comprobante');

    const order = await env.DB.prepare(
      'select * from orders order by id desc limit 1',
    ).first<{
      id: number;
      status: string;
      proof_key: string | null;
      subtotal_cents: number;
      total_cents: number;
      payment_method: string;
      store_id: number;
    }>();
    expect(order).toBeTruthy();
    expect(order!.status).toBe('pending');
    expect(order!.payment_method).toBe('transferencia');
    expect(order!.subtotal_cents).toBe(1600000);
    expect(order!.total_cents).toBe(1600000);
    expect(order!.proof_key).toBeTruthy();
    expect(order!.proof_key).toContain('comprobantes/');

    const proof = await env.R2.get(order!.proof_key!);
    expect(proof).toBeTruthy();

    const stock = await env.DB.prepare('select stock from products where id = ?')
      .bind(productId)
      .first<{ stock: number }>();
    expect(stock!.stock).toBe(3);
  });

  it('cupón percent 10% descuenta el total', async () => {
    const cookie = await register('Carla Cupon', 'carla.cupon@test.com');
    const productId = await insertProduct({
      name: 'Nafta Premium',
      slug: 'nafta-premium-1',
      priceCents: 100000,
      stock: 10,
    });
    await env.DB.prepare(
      "insert into coupons (code, type, value) values ('PORTE10', 'percent', 10)",
    ).run();
    const withCart = await addToCart(cookie, productId, 1);

    const res = await checkout(
      withCart,
      checkoutForm({ cupon: 'PORTE10', comprobante: false }),
    );
    expect(res.status).toBe(302);

    const order = await env.DB.prepare(
      'select subtotal_cents, discount_cents, total_cents, coupon_id from orders order by id desc limit 1',
    ).first<{
      subtotal_cents: number;
      discount_cents: number;
      total_cents: number;
      coupon_id: number | null;
    }>();
    expect(order!.subtotal_cents).toBe(100000);
    expect(order!.discount_cents).toBe(10000);
    expect(order!.total_cents).toBe(90000);
    expect(order!.coupon_id).toBeTruthy();
  });

  it('checkout sin comprobante también crea la orden con proofKey null', async () => {
    const cookie = await register('Diego Sin Boleto', 'diego.sin@test.com');
    const productId = await insertProduct({
      name: 'Agua Mineral',
      slug: 'agua-mineral-1',
      priceCents: 25000,
      stock: 3,
    });
    const withCart = await addToCart(cookie, productId, 1);

    const res = await checkout(withCart, checkoutForm({ comprobante: false }));
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/ordenes?aviso=comprobante');

    const order = await env.DB.prepare(
      'select status, proof_key from orders order by id desc limit 1',
    ).first<{ status: string; proof_key: string | null }>();
    expect(order!.status).toBe('pending');
    expect(order!.proof_key).toBeNull();
  });

  it('stock insuficiente responde 422 y no crea la orden', async () => {
    const cookie = await register('Eva Stock', 'eva.stock@test.com');
    const productId = await insertProduct({
      name: 'Limpia Parabrisas',
      slug: 'limpia-parabrisas-1',
      priceCents: 15000,
      stock: 1,
    });
    const withCart = await addToCart(cookie, productId, 3);

    const before = await env.DB.prepare(
      'select count(*) as n from orders',
    ).first<{ n: number }>();

    const res = await checkout(withCart, checkoutForm({ comprobante: false }));
    expect(res.status).toBe(422);

    const after = await env.DB.prepare(
      'select count(*) as n from orders',
    ).first<{ n: number }>();
    expect(after!.n).toBe(before!.n);

    const stock = await env.DB.prepare('select stock from products where id = ?')
      .bind(productId)
      .first<{ stock: number }>();
    expect(stock!.stock).toBe(1);
  });
});
