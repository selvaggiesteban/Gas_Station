import { Hono } from 'hono';
import type { AppEnv } from '../env';
import { brandOf, requireAuth, requireRole } from '../middleware/auth';
import { page, escapeHtml } from '../lib/html';

export const appRoutes = new Hono<AppEnv>();

appRoutes.get('/app', requireAuth(), (c) => {
  const user = c.get('user')!;
  return c.html(
    page(
      { title: 'Inicio', brand: brandOf(c), user },
      `<div class="card">
        <h1>Hola, ${escapeHtml(user.name)}</h1>
        <p>Tenés <strong>${user.points} puntos</strong>. Sumá puntos iniciando sesión todos los días, completando desafíos semanales y ganando batallas.</p>
        <p class="muted">Rol: ${escapeHtml(user.role)}</p>
      </div>
      <div class="card">
        <h2>Secciones</h2>
        <p class="muted">Tienda, batallas y ranking se habilitan en las próximas entregas.</p>
      </div>`,
    ),
  );
});

appRoutes.get('/tienda', requireAuth(), (c) => {
  const user = c.get('user')!;
  return c.html(
    page(
      { title: 'Tienda', brand: brandOf(c), user },
      `<div class="card">
        <h1>Tienda</h1>
        <p class="muted">El catálogo con precios en pesos, el carrito y el pago por transferencia se habilitan en la fase F2.</p>
        <a class="btn secondary" href="/app">Volver al inicio</a>
      </div>`,
    ),
  );
});
