import { Hono } from 'hono';
import { createDb } from './db';
import { jobs } from './db/schema';
import { sql } from 'drizzle-orm';
import type { AppEnv } from './env';
import { attachUser, brandOf } from './middleware/auth';
import { authRoutes } from './routes/auth';
import { appRoutes } from './routes/app';
import { shopRoutes } from './routes/shop';
import { adminRoutes } from './routes/admin';
import { rankingRoutes } from './routes/ranking';
import { operativaRoutes } from './routes/operativa';
import { page, escapeHtml, LOGO_IMG } from './lib/html';
import { configureEmail } from './lib/email';
import { deleteExpiredSessions } from './lib/session';

const app = new Hono<AppEnv>();

app.use('*', async (c, next) => {
  configureEmail(c.env);
  await next();
});

app.use('*', attachUser);

app.get('/', (c) => {
  const user = c.get('user');
  if (user) return c.redirect('/app');
  const brand = brandOf(c);
  const LOGO_IMG_TAG = `<img src="${LOGO_IMG}" alt="${escapeHtml(brand)}" style="width:120px;height:120px;object-fit:contain;display:block;margin:0 auto">`;
  const ars = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
  });
  const incluye = [
    {
      titulo: 'Catálogo propio',
      texto: 'Cargá productos, precios y stock con una estructura clara para vender en el mostrador.',
    },
    {
      titulo: 'Marca propia',
      texto: 'Etiquetá tu marca en tickets, comprobantes y toda la comunicación con tus clientes.',
    },
    {
      titulo: 'Órdenes con transferencia',
      texto: 'Generá órdenes de compra, registrá el pago por transferencia y marcá el envío.',
    },
    {
      titulo: 'Puntos y desafíos',
      texto: 'Premiá la fidelidad con puntos por compra y desafíos activos para tus clientes.',
    },
    {
      titulo: 'Panel de operativa',
      texto: 'Caja, rendición de turno, stock y ensobrado en un solo lugar, todos los días.',
    },
    {
      titulo: 'Administración',
      texto: 'Gestioná usuarios, roles, tiendas y permisos desde un panel simple y ordenado.',
    },
  ];
  const planes = [
    {
      nombre: 'Básico',
      precio: 999900,
      destacado: false,
      features: [
        'Hasta 3 usuarios',
        'Catálogo propio de productos',
        'Puntos para tus clientes',
        'Soporte por email',
      ],
    },
    {
      nombre: 'Profesional',
      precio: 1999900,
      destacado: true,
      features: [
        'Usuarios ilimitados',
        'Marca propia y etiquetas',
        'Órdenes con transferencia',
        'Desafíos y ranking',
        'Soporte prioritario',
      ],
    },
    {
      nombre: 'Todo incluido',
      precio: 3499900,
      destacado: false,
      features: [
        'Todo lo del plan Profesional',
        'Panel de operativa en vivo',
        'Administración multi-sucursal',
        'Reportes y exportación',
        'Acompañamiento mensual',
      ],
    },
  ];
  const incluyeHtml = incluye
    .map(
      (item) => `<div class="card feature">
        <h3>${escapeHtml(item.titulo)}</h3>
        <p>${escapeHtml(item.texto)}</p>
      </div>`,
    )
    .join('\n      ');
  const planesHtml = planes
    .map(
      (plan) => `<div class="card plan${plan.destacado ? ' featured' : ''}">
        ${plan.destacado ? '<span class="pill">Más elegido</span>' : ''}
        <h3>${escapeHtml(plan.nombre)}</h3>
        <p class="price">${ars.format(plan.precio / 100)}<span> / mes</span></p>
        <ul class="features">${plan.features
          .map((f) => `<li>${escapeHtml(f)}</li>`)
          .join('')}</ul>
        <a class="btn" href="/registro">Creá tu cuenta</a>
      </div>`,
    )
    .join('\n      ');
  return c.html(
    page(
      { title: 'Inicio', brand, landing: true },
      `<section class="home">
        <div class="home-brand">
          ${LOGO_IMG_TAG}
        </div>
        <p class="home-lead">La gestión de tu estación de servicio, en un solo lugar.</p>
        <div class="home-cta">
          <a class="btn lg" href="/registro">Creá tu cuenta</a>
          <a class="btn secondary lg" href="/login">Accedé</a>
        </div>
      </section>`,
    ),
  );
});

app.route('/', authRoutes);
app.route('/', appRoutes);
app.route('/', shopRoutes);
app.route('/', adminRoutes);
app.route('/', rankingRoutes);
app.route('/', operativaRoutes);

app.notFound((c) => c.json({ error: 'no encontrado' }, 404));

app.onError((err, c) => {
  console.error('unhandled', err);
  return c.json({ error: 'error interno' }, 500);
});

async function scheduledHandler(
  _controller: ScheduledController,
  env: AppEnv['Bindings'],
) {
  const db = createDb(env.DB);
  await deleteExpiredSessions(db);
  const now = new Date();
  const ready = await db
    .select({ id: jobs.id, type: jobs.type, attempts: jobs.attempts })
    .from(jobs)
    .where(sql`${jobs.status} = 'pending' and ${jobs.runAt} <= ${now}`)
    .limit(20);
  console.log(`cron: ${ready.length} jobs ready`);
}

export { app };

export default {
  fetch: app.fetch,
  scheduled: scheduledHandler,
};
