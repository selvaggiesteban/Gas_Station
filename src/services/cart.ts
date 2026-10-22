import { deleteCookie, getCookie, setCookie } from 'hono/cookie';
import type { Context } from 'hono';

export const CART_COOKIE = 'gs_cart';
export const MAX_CART_ITEMS = 20;
export const MAX_QTY = 99;
const CART_TTL_MS = 1000 * 60 * 60 * 24 * 30;

export type CartMap = Record<string, number>;

export function getCart(c: Context): CartMap {
  const raw = getCookie(c, CART_COOKIE);
  if (!raw) return {};
  let parsed: unknown;
  try {
    parsed = JSON.parse(raw);
  } catch {
    return {};
  }
  if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
    return {};
  }
  const cart: CartMap = {};
  for (const [key, value] of Object.entries(
    parsed as Record<string, unknown>,
  )) {
    if (Object.keys(cart).length >= MAX_CART_ITEMS) break;
    if (!/^\d+$/.test(key)) continue;
    const qty = Math.floor(Number(value));
    if (!Number.isFinite(qty) || qty < 1) continue;
    cart[key] = Math.min(qty, MAX_QTY);
  }
  return cart;
}

export function setCart(c: Context, cart: CartMap): void {
  const clean: CartMap = {};
  for (const [key, qty] of Object.entries(cart)) {
    if (Object.keys(clean).length >= MAX_CART_ITEMS) break;
    if (!/^\d+$/.test(key)) continue;
    const parsed = Math.floor(Number(qty));
    if (!Number.isFinite(parsed) || parsed < 1) continue;
    clean[key] = Math.min(parsed, MAX_QTY);
  }
  if (Object.keys(clean).length === 0) {
    deleteCookie(c, CART_COOKIE, { path: '/' });
    return;
  }
  setCookie(c, CART_COOKIE, JSON.stringify(clean), {
    httpOnly: true,
    secure: true,
    sameSite: 'Lax',
    path: '/',
    maxAge: CART_TTL_MS / 1000,
  });
}

export function cartUnits(cart: CartMap): number {
  let total = 0;
  for (const qty of Object.values(cart)) total += qty;
  return total;
}
