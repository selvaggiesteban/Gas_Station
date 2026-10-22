import { and, asc, eq, inArray, sql } from 'drizzle-orm';
import type { Db } from '../db';
import {
  coupons,
  orderItems,
  orders,
  products,
  storeUser,
  stores,
  users,
} from '../db/schema';
import type { CartMap } from './cart';

export type CartProduct = typeof products.$inferSelect;
export type OrderRow = typeof orders.$inferSelect;

export type CheckoutLine = { product: CartProduct; qty: number };

export async function loadCartProducts(
  db: Db,
  cart: CartMap,
): Promise<CartProduct[]> {
  const ids = Object.keys(cart)
    .map(Number)
    .filter((n) => Number.isInteger(n) && n > 0);
  if (ids.length === 0) return [];
  return db.select().from(products).where(inArray(products.id, ids));
}

export function buildLines(
  cart: CartMap,
  rows: CartProduct[],
): CheckoutLine[] {
  return rows.map((product) => ({
    product,
    qty: cart[String(product.id)] ?? 1,
  }));
}

export function stockError(lines: CheckoutLine[]): string | null {
  for (const { product, qty } of lines) {
    if (!product.active) {
      return `El producto "${product.name}" ya no está disponible.`;
    }
    if (product.stock !== null && product.stock < qty) {
      return `No hay stock suficiente de "${product.name}" (disponible: ${product.stock}).`;
    }
  }
  return null;
}

export function subtotalOf(lines: CheckoutLine[]): number {
  let subtotal = 0;
  for (const { product, qty } of lines) {
    subtotal += product.priceCents * qty;
  }
  return subtotal;
}

export type CouponResult =
  | { ok: true; couponId: number; discountCents: number }
  | { ok: false; error: string };

export async function validateCoupon(
  db: Db,
  code: string,
  subtotalCents: number,
): Promise<CouponResult> {
  const trimmed = code.trim();
  if (!trimmed) return { ok: false, error: 'Ingresá un código de cupón válido.' };
  const rows = await db
    .select()
    .from(coupons)
    .where(eq(coupons.code, trimmed))
    .limit(1);
  const coupon = rows[0];
  if (!coupon || !coupon.active) {
    return { ok: false, error: 'El cupón no es válido.' };
  }
  if (coupon.expiresAt && coupon.expiresAt.getTime() <= Date.now()) {
    return { ok: false, error: 'El cupón expiró.' };
  }
  if (subtotalCents < coupon.minOrderCents) {
    return {
      ok: false,
      error: 'El cupón requiere un pedido mínimo más alto.',
    };
  }
  if (coupon.maxUses !== null && coupon.usedCount >= coupon.maxUses) {
    return { ok: false, error: 'El cupón ya se agotó.' };
  }
  const raw =
    coupon.type === 'percent'
      ? Math.round((subtotalCents * coupon.value) / 100)
      : coupon.value;
  const discountCents = Math.max(0, Math.min(raw, subtotalCents));
  return { ok: true, couponId: coupon.id, discountCents };
}

export async function ensureDefaultStore(db: Db): Promise<number> {
  const rows = await db
    .select({ id: stores.id })
    .from(stores)
    .orderBy(asc(stores.id))
    .limit(1);
  if (rows[0]) return rows[0].id;
  const inserted = await db
    .insert(stores)
    .values({ name: 'Central' })
    .returning({ id: stores.id });
  return inserted[0]!.id;
}

export async function findAutoGestor(
  db: Db,
  storeId: number,
): Promise<number | null> {
  const rows = await db
    .select({ userId: users.id })
    .from(storeUser)
    .innerJoin(users, eq(storeUser.userId, users.id))
    .where(
      and(eq(storeUser.storeId, storeId), eq(users.role, 'store_manager')),
    );
  return rows.length === 1 ? rows[0]!.userId : null;
}

export type StockGuardResult =
  | { ok: true; decremented: CheckoutLine[] }
  | { ok: false; error: string };

export async function decrementStockGuard(
  db: Db,
  lines: CheckoutLine[],
): Promise<StockGuardResult> {
  const decremented: CheckoutLine[] = [];
  for (const line of lines) {
    if (line.product.stock === null) continue;
    const result = await db.run(
      sql`update products set stock = stock - ${line.qty} where id = ${line.product.id} and stock is not null and stock >= ${line.qty}`,
    );
    if (result.meta.changes === 0) {
      await restoreStock(db, decremented);
      return {
        ok: false,
        error: `No hay stock suficiente de "${line.product.name}".`,
      };
    }
    decremented.push(line);
  }
  return { ok: true, decremented };
}

export async function restoreStock(
  db: Db,
  lines: CheckoutLine[],
): Promise<void> {
  for (const line of lines) {
    await db.run(
      sql`update products set stock = stock + ${line.qty} where id = ${line.product.id}`,
    );
  }
}

export function randomOrderNumber(): string {
  const bytes = crypto.getRandomValues(new Uint8Array(7));
  let out = '';
  for (const b of bytes) out += String(b % 10);
  return `GS-${out}`;
}

export type NewOrderValues = {
  userId: number;
  storeId: number;
  gestorId: number | null;
  subtotalCents: number;
  discountCents: number;
  totalCents: number;
  couponId: number | null;
};

export async function insertOrderWithRetry(
  db: Db,
  values: NewOrderValues,
): Promise<OrderRow> {
  for (let attempt = 0; attempt < 10; attempt++) {
    try {
      const rows = await db
        .insert(orders)
        .values({
          ...values,
          number: randomOrderNumber(),
          status: 'pending',
          paymentMethod: 'transferencia',
        })
        .returning();
      return rows[0]!;
    } catch {
      // número duplicado: reintentar con otro
    }
  }
  throw new Error('No se pudo generar un número de orden único.');
}

export async function insertOrderItems(
  db: Db,
  orderId: number,
  lines: CheckoutLine[],
): Promise<void> {
  await db.insert(orderItems).values(
    lines.map((line) => ({
      orderId,
      productId: line.product.id,
      qty: line.qty,
      unitPriceCents: line.product.priceCents,
      nameSnapshot: line.product.name,
    })),
  );
}

export async function incrementCouponUses(
  db: Db,
  couponId: number,
): Promise<void> {
  await db
    .update(coupons)
    .set({ usedCount: sql`${coupons.usedCount} + 1` })
    .where(eq(coupons.id, couponId));
}

export function sanitizeFilename(name: string): string {
  const cleaned = name.replace(/[^A-Za-z0-9._-]/g, '_').replace(/^\.+/, '');
  return cleaned || 'comprobante';
}
