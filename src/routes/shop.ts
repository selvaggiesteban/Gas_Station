import { Hono } from 'hono';
import type { Context } from 'hono';
import { asc, desc, eq } from 'drizzle-orm';
import type { AppEnv } from '../env';
import { dbOf, brandOf, requireAuth } from '../middleware/auth';
import { page, escapeHtml } from '../lib/html';
import {
  getCart,
  setCart,
  MAX_CART_ITEMS,
  MAX_QTY,
  type CartMap,
} from '../services/cart';
import {
  buildLines,
  decrementStockGuard,
  ensureDefaultStore,
  findAutoGestor,
  incrementCouponUses,
  insertOrderItems,
  insertOrderWithRetry,
  loadCartProducts,
  restoreStock,
  sanitizeFilename,
  stockError,
  subtotalOf,
  validateCoupon,
  type CheckoutLine,
} from '../services/orders';
import { orderItems, orders, products } from '../db/schema';

export const shopRoutes = new Hono<AppEnv>();

const MAX_PROOF_BYTES = 5 * 1024 * 1024;

const STATUS_LABELS: Record<string, string> = {
  pending: 'Pendiente',
  paid: 'Pagado',
  cancelled: 'Cancelado',
  refunded: 'Reembolsado',
};

function str(v: unknown): string {
  return typeof v === 'string' ? v.trim() : '';
}

function productIdFrom(v: unknown): number | null {
  const n = Number(str(v));
  return Number.isInteger(n) && n > 0 ? n : null;
}

function qtyFrom(v: unknown): number | null {
  const n = Math.floor(Number(str(v)));
  if (!Number.isFinite(n) || n < 1) return null;
  return Math.min(n, MAX_QTY);
}

function formatArs(cents: number): string {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  }).format(cents / 100);
}

function cartCount(cart: CartMap): number {
  let total = 0;
  for (const qty of Object.values(cart)) total += qty;
  return total;
}

function cartLink(cart: CartMap): string {
  const n = cartCount(cart);
  return n > 0 ? ` /carrito (${n})` : '';
}

shopRoutes.get('/tienda', requireAuth(), async (c) => {
  const user = c.get('user')!;
  const db = dbOf(c);
  const rows = await db
    .select()
    .from(products)
    .where(eq(products.active, true))
    .orderBy(asc(products.sort), asc(products.name));
  const cart = getCart(c);

  const cards = rows
    .map((p) => {
      const sinStock = p.stock !== null && p.stock <= 0;
      const stockHtml = sinStock
        ? `<span class="pill" style="background:#b91c1c">Sin stock</span>`
        : p.stock === null
          ? `<span class="pill">Disponible</span>`
          : `<span class="pill">Disponible</span> <span class="muted">${p.stock} en stock</span>`;
      const form = sinStock
        ? ''
        : `<form method="post" action="/carrito/agregar" class="row">
            <input type="hidden" name="productId" value="${p.id}">
            <input type="number" name="qty" value="1" min="1" max="99" style="width:6rem" aria-label="Cantidad">
            <button type="submit" style="margin-top:0">Agregá al carrito</button>
          </form>`;
      return `<div class="card">
        <h2>${escapeHtml(p.name)}</h2>
        <p class="muted">${escapeHtml(p.description)}</p>
        <div class="row"><strong>${formatArs(p.priceCents)}</strong>${stockHtml}</div>
        ${form}
      </div>`;
    })
    .join('');

  return c.html(
    page(
      { title: 'Tienda', brand: brandOf(c), user },
      `<div class="card">
        <h1>Tienda</h1>
        <p class="muted">Precios en pesos. Agregá productos y finalizá la compra con transferencia.</p>
        <p><a href="/carrito">Ver carrito${escapeHtml(cartLink(cart))}</a></p>
      </div>
      ${cards || '<div class="card"><p class="muted">No hay productos disponibles por el momento.</p></div>'}`,
    ),
  );
});

shopRoutes.get('/carrito', requireAuth(), async (c) => {
  const user = c.get('user')!;
  const db = dbOf(c);
  const cart = getCart(c);
  const rows = await loadCartProducts(db, cart);
  const lines = buildLines(cart, rows);

  if (lines.length === 0) {
    return c.html(
      page(
        { title: 'Carrito', brand: brandOf(c), user },
        `<div class="card">
          <h1>Tu carrito está vacío</h1>
          <p class="muted">Agregá productos desde la tienda para comenzar tu compra.</p>
          <a class="btn" href="/tienda">Ir a la tienda</a>
        </div>`,
      ),
    );
  }

  const items = lines
    .map(
      (line) => `<div class="card">
        <h2>${escapeHtml(line.product.name)}</h2>
        <p class="muted">${formatArs(line.product.priceCents)} c/u · ${formatArs(line.product.priceCents * line.qty)} total</p>
        <form method="post" action="/carrito" class="row">
          <input type="hidden" name="productId" value="${line.product.id}">
          <input type="number" name="qty" value="${line.qty}" min="0" max="99" style="width:6rem" aria-label="Cantidad">
          <button type="submit" style="margin-top:0">Modificá cantidad</button>
        </form>
        <form method="post" action="/carrito/quitar" style="margin:0">
          <input type="hidden" name="productId" value="${line.product.id}">
          <button type="submit" class="secondary" style="margin-top:0.5rem">Quitá del carrito</button>
        </form>
      </div>`,
    )
    .join('');

  const subtotal = subtotalOf(lines);

  return c.html(
    page(
      { title: 'Carrito', brand: brandOf(c), user },
      `<div class="card">
        <h1>Tu carrito</h1>
        ${items}
        <div class="row"><strong>Subtotal: ${formatArs(subtotal)}</strong></div>
        <a class="btn" href="/checkout">Finalizá la compra</a>
        <form method="post" action="/carrito/vaciar" style="display:inline">
          <button type="submit" class="secondary">Vaciar carrito</button>
        </form>
      </div>`,
    ),
  );
});

shopRoutes.post('/carrito/agregar', requireAuth(), async (c) => {
  const body = await c.req.parseBody();
  const id = productIdFrom(body.productId);
  const qty = qtyFrom(body.qty) ?? 1;
  if (!id) {
    return c.html(
      page(
        { title: 'Carrito', brand: brandOf(c), user: c.get('user'), error: 'Producto inválido.' },
        `<div class="card"><h1>No se pudo agregar</h1>
         <p class="muted">Elegí un producto válido desde la tienda.</p>
         <a class="btn secondary" href="/tienda">Volver a la tienda</a></div>`,
      ),
      422,
    );
  }
  const db = dbOf(c);
  const rows = await db
    .select()
    .from(products)
    .where(eq(products.id, id))
    .limit(1);
  const product = rows[0];
  if (!product || !product.active) {
    return c.html(
      page(
        { title: 'Carrito', brand: brandOf(c), user: c.get('user'), error: 'El producto no está disponible.' },
        `<div class="card"><h1>No se pudo agregar</h1>
         <p class="muted">El producto seleccionado ya no está disponible.</p>
         <a class="btn secondary" href="/tienda">Volver a la tienda</a></div>`,
      ),
      422,
    );
  }
  const cart = getCart(c);
  const key = String(id);
  if (!(key in cart) && Object.keys(cart).length >= MAX_CART_ITEMS) {
    return c.html(
      page(
        {
          title: 'Carrito',
          brand: brandOf(c),
          user: c.get('user'),
          error: `El carrito admite hasta ${MAX_CART_ITEMS} productos distintos.`,
        },
        `<div class="card"><h1>Carrito lleno</h1>
         <a class="btn secondary" href="/carrito">Ver carrito</a></div>`,
      ),
      422,
    );
  }
  cart[key] = Math.min((cart[key] ?? 0) + qty, MAX_QTY);
  setCart(c, cart);
  return c.redirect('/carrito');
});

shopRoutes.post('/carrito', requireAuth(), async (c) => {
  const body = await c.req.parseBody();
  const id = productIdFrom(body.productId);
  const cart = getCart(c);
  if (id) {
    const key = String(id);
    if (key in cart) {
      const qty = qtyFrom(body.qty);
      if (!qty) {
        delete cart[key];
      } else {
        cart[key] = qty;
      }
      setCart(c, cart);
    }
  }
  return c.redirect('/carrito');
});

shopRoutes.post('/carrito/quitar', requireAuth(), async (c) => {
  const body = await c.req.parseBody();
  const id = productIdFrom(body.productId);
  const cart = getCart(c);
  if (id) {
    delete cart[String(id)];
    setCart(c, cart);
  }
  return c.redirect('/carrito');
});

shopRoutes.post('/carrito/vaciar', requireAuth(), async (c) => {
  setCart(c, {});
  return c.redirect('/carrito');
});

function checkoutPage(
  c: Context<AppEnv>,
  opts: {
    lines: CheckoutLine[];
    subtotal: number;
    error?: string | null;
  },
): Response {
  const user = c.get('user')!;
  const summary = opts.lines
    .map(
      (line) =>
        `<div class="row"><span>${escapeHtml(line.product.name)} × ${line.qty}</span>
         <span>${formatArs(line.product.priceCents * line.qty)}</span></div>`,
    )
    .join('');
  return c.html(
    page(
      {
        title: 'Finalizá la compra',
        brand: brandOf(c),
        user,
        error: opts.error,
      },
      `<div class="card">
        <h1>Finalizá la compra</h1>
        ${summary}
        <div class="row"><strong>Subtotal: ${formatArs(opts.subtotal)}</strong></div>
        <form method="post" action="/checkout" enctype="multipart/form-data">
          <label for="cupon">Cupón (opcional)</label>
          <input id="cupon" name="cupon" type="text" placeholder="CÓDIGO">
          <label for="comprobante">Comprobante de transferencia (imagen o PDF, máx. 5&nbsp;MB — opcional)</label>
          <input id="comprobante" name="comprobante" type="file" accept="image/*,application/pdf">
          <button type="submit" name="metodo" value="transferencia">Confirmá por transferencia</button>
          <button type="button" class="secondary" disabled style="opacity:0.6;cursor:not-allowed">Pagar con Mercado Pago (próximamente)</button>
        </form>
        <p class="muted">Transferís el total a la cuenta indicada y adjuntás el comprobante. Vamos a revisar el pago y a confirmar tu pedido.</p>
      </div>`,
    ),
    opts.error ? 422 : 200,
  );
}

shopRoutes.get('/checkout', requireAuth(), async (c) => {
  const db = dbOf(c);
  const cart = getCart(c);
  const rows = await loadCartProducts(db, cart);
  const lines = buildLines(cart, rows);
  if (lines.length === 0) return c.redirect('/tienda');
  return checkoutPage(c, { lines, subtotal: subtotalOf(lines) });
});

shopRoutes.post('/checkout', requireAuth(), async (c) => {
  const user = c.get('user')!;
  const db = dbOf(c);
  const cart = getCart(c);
  const rows = await loadCartProducts(db, cart);
  const lines = buildLines(cart, rows);
  if (lines.length === 0) return c.redirect('/tienda');
  const subtotal = subtotalOf(lines);

  const fail = (error: string) =>
    checkoutPage(c, { lines, subtotal, error });

  const sErr = stockError(lines);
  if (sErr) return fail(sErr);

  const body = await c.req.parseBody();
  const couponCode = str(body.cupon);
  let discountCents = 0;
  let couponId: number | null = null;
  if (couponCode) {
    const result = await validateCoupon(db, couponCode, subtotal);
    if (!result.ok) return fail(result.error);
    discountCents = result.discountCents;
    couponId = result.couponId;
  }
  const totalCents = subtotal - discountCents;

  const fileRaw = body.comprobante;
  let proofFile: File | null = null;
  if (fileRaw instanceof File && fileRaw.size > 0) {
    if (
      !(fileRaw.type.startsWith('image/') || fileRaw.type === 'application/pdf')
    ) {
      return fail('El comprobante debe ser una imagen o un PDF.');
    }
    if (fileRaw.size > MAX_PROOF_BYTES) {
      return fail('El comprobante no puede superar los 5 MB.');
    }
    proofFile = fileRaw;
  }

  const stock = await decrementStockGuard(db, lines);
  if (!stock.ok) return fail(stock.error);

  let order;
  try {
    const storeId = await ensureDefaultStore(db);
    const gestorId = await findAutoGestor(db, storeId);
    order = await insertOrderWithRetry(db, {
      userId: user.id,
      storeId,
      gestorId,
      subtotalCents: subtotal,
      discountCents,
      totalCents,
      couponId,
    });
    await insertOrderItems(db, order.id, lines);
  } catch (err) {
    await restoreStock(db, stock.decremented);
    throw err;
  }

  if (proofFile) {
    const safe = sanitizeFilename(proofFile.name || 'comprobante.pdf');
    const key = `comprobantes/${order.number}/${safe}`;
    await c.env.R2.put(key, proofFile, {
      httpMetadata: { contentType: proofFile.type || 'application/octet-stream' },
    });
    await db
      .update(orders)
      .set({ proofKey: key })
      .where(eq(orders.id, order.id));
  }

  if (couponId !== null) {
    await incrementCouponUses(db, couponId);
  }

  setCart(c, {});
  return c.redirect('/ordenes?aviso=comprobante');
});

shopRoutes.get('/ordenes', requireAuth(), async (c) => {
  const user = c.get('user')!;
  const db = dbOf(c);
  const rows = await db
    .select()
    .from(orders)
    .where(eq(orders.userId, user.id))
    .orderBy(desc(orders.createdAt));
  const aviso = c.req.query('aviso');
  const notice =
    aviso === 'comprobante'
      ? 'Recibimos tu pedido y el comprobante. Vamos a revisar el pago y te confirmamos la compra.'
      : null;

  const list = rows
    .map(
      (o) => `<div class="card">
        <div class="row">
          <strong>${escapeHtml(o.number)}</strong>
          <span class="pill">${escapeHtml(STATUS_LABELS[o.status] ?? o.status)}</span>
          <span>${formatArs(o.totalCents)}</span>
        </div>
        <p class="muted">Pedido del ${o.createdAt.toLocaleDateString('es-AR')}</p>
        <a class="btn secondary" href="/ordenes/${o.id}">Ver detalle</a>
      </div>`,
    )
    .join('');

  return c.html(
    page(
      { title: 'Mis órdenes', brand: brandOf(c), user, notice },
      `<div class="card">
        <h1>Mis órdenes</h1>
        <p class="muted">Historial de tus compras con estado y total.</p>
      </div>
      ${list || '<div class="card"><p class="muted">Todavía no hiciste ninguna compra.</p></div>'}`,
    ),
  );
});

async function findOrderForUser(
  c: Context<AppEnv>,
  id: number,
): Promise<(typeof orders.$inferSelect) | null> {
  if (!Number.isInteger(id) || id <= 0) return null;
  const db = dbOf(c);
  const user = c.get('user')!;
  const rows = await db
    .select()
    .from(orders)
    .where(eq(orders.id, id))
    .limit(1);
  const order = rows[0];
  if (!order) return null;
  if (order.userId !== user.id && user.role !== 'admin') return null;
  return order;
}

shopRoutes.get('/ordenes/:id', requireAuth(), async (c) => {
  const user = c.get('user')!;
  const order = await findOrderForUser(c, Number(c.req.param('id')));
  if (!order) return c.notFound();
  const db = dbOf(c);
  const items = await db
    .select()
    .from(orderItems)
    .where(eq(orderItems.orderId, order.id));

  const itemsHtml = items
    .map(
      (it) =>
        `<div class="row"><span>${escapeHtml(it.nameSnapshot)} × ${it.qty}</span>
         <span>${formatArs(it.unitPriceCents * it.qty)}</span></div>`,
    )
    .join('');

  const proofHtml = order.proofKey
    ? `<p><a href="/ordenes/${order.id}/comprobante">Ver comprobante</a></p>`
    : `<p class="muted">Sin comprobante adjunto.</p>`;

  return c.html(
    page(
      { title: `Orden ${order.number}`, brand: brandOf(c), user },
      `<div class="card">
        <h1>Orden ${escapeHtml(order.number)}</h1>
        <div class="row">
          <span class="pill">${escapeHtml(STATUS_LABELS[order.status] ?? order.status)}</span>
          <span class="muted">Pago: ${order.paymentMethod === 'transferencia' ? 'Transferencia bancaria' : 'Mercado Pago'}</span>
        </div>
        <h2>Productos</h2>
        ${itemsHtml}
        <div class="row"><span>Subtotal</span><span>${formatArs(order.subtotalCents)}</span></div>
        <div class="row"><span>Descuento</span><span>-${formatArs(order.discountCents)}</span></div>
        <div class="row"><strong>Total</strong><strong>${formatArs(order.totalCents)}</strong></div>
        ${proofHtml}
        <a class="btn secondary" href="/ordenes">Volver a mis órdenes</a>
      </div>`,
    ),
  );
});

shopRoutes.get('/ordenes/:id/comprobante', requireAuth(), async (c) => {
  const order = await findOrderForUser(c, Number(c.req.param('id')));
  if (!order || !order.proofKey) return c.notFound();
  const obj = await c.env.R2.get(order.proofKey);
  if (!obj) return c.notFound();
  return new Response(obj.body, {
    headers: {
      'Content-Type': obj.httpMetadata?.contentType ?? 'application/octet-stream',
    },
  });
});
