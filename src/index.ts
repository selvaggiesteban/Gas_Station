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
import { page, escapeHtml } from './lib/html';
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
      `<section class="hero">
        <h1>La gestión de tu estación de servicio, en un solo lugar</h1>
        <p class="lead">${escapeHtml(brand)} es la plataforma white-label para tu estación: catálogo propio, marca propia, órdenes con transferencia, puntos y desafíos, panel de operativa y administración.</p>
        <div class="cta-row">
          <a class="btn lg" href="/registro">Creá tu cuenta</a>
          <a class="btn secondary lg" href="/login">Accedé</a>
        </div>
      </section>

      <section id="incluye" class="section">
        <h2>Qué incluye</h2>
        <p class="lead">Todo lo que necesitás para administrar tu estación sin planillas ni sistemas sueltos.</p>
        <div class="grid">
      ${incluyeHtml}
        </div>
      </section>

      <section id="precios" class="section">
        <h2>Precios</h2>
        <p class="lead">Planes mensuales en pesos, sin permanencia mínima. Cancelá cuando quieras.</p>
        <div class="grid plans">
      ${planesHtml}
        </div>
      </section>

      <section id="contacto" class="section">
        <h2>Contacto</h2>
        <p class="lead">Dejanos tu consulta y te respondemos a la brevedad.</p>
        <form class="contact" action="mailto:contacto@gasstation.app" method="post" enctype="text/plain">
          <label for="nombre">Nombre</label>
          <input id="nombre" name="nombre" type="text" required autocomplete="name">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email">
          <label for="mensaje">Mensaje</label>
          <textarea id="mensaje" name="mensaje" rows="6" required></textarea>
          <button type="submit">Enviar mensaje</button>
        </form>
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
