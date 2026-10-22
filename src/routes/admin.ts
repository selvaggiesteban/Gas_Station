import { Hono } from 'hono';
import type { AppEnv } from '../env';
import { brandOf, requireRole } from '../middleware/auth';
import { page } from '../lib/html';

export const adminRoutes = new Hono<AppEnv>();

adminRoutes.get('/admin', requireRole('admin'), (c) => {
  const user = c.get('user')!;
  return c.html(
    page(
      { title: 'Administración', brand: brandOf(c), user },
      `<div class="card">
        <h1>Panel de administración</h1>
        <ul>
          <li>Métricas de ventas y usuarios</li>
          <li>Usuarios y roles</li>
          <li>Tiendas y gestores</li>
          <li>Productos, cupones y órdenes</li>
          <li>Anuncios y desafíos</li>
        </ul>
        <p class="muted">Se construye en la fase F5.</p>
      </div>`,
    ),
  );
});
