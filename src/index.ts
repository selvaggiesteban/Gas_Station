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
import { page, escapeHtml } from './lib/html';
import {
  benchPbkdf2,
  hashPassword,
  DEFAULT_PBKDF2_ITERATIONS,
  MAX_PBKDF2_ITERATIONS,
} from './lib/crypto';
import { deleteExpiredSessions } from './lib/session';

const app = new Hono<AppEnv>();

app.use('*', attachUser);

app.get('/', (c) => {
  const user = c.get('user');
  if (user) return c.redirect('/app');
  const brand = brandOf(c);
  return c.html(
    page(
      { title: 'Inicio', brand },
      `<div class="card">
        <h1>${escapeHtml(brand)}</h1>
        <p>Sumá puntos, participá de desafíos semanales, competí en batallas y comprá desde la tienda.</p>
        <a class="btn" href="/registro">Creá tu cuenta</a>
        <a class="btn secondary" href="/login" style="margin-left:0.5rem">Accedé</a>
      </div>`,
    ),
  );
});

app.get('/__bench', async (c) => {
  const raw = Number(c.req.query('iters') ?? DEFAULT_PBKDF2_ITERATIONS);
  const iters = Math.min(
    MAX_PBKDF2_ITERATIONS,
    Math.max(1_000, Number.isFinite(raw) ? raw : DEFAULT_PBKDF2_ITERATIONS),
  );
  const ms = await benchPbkdf2(iters);
  return c.json({ iters, ...ms });
});

app.get('/__time', async (c) => {
  const tick = () => new Promise((r) => setTimeout(r, 0));
  await tick();
  const t0 = Date.now();
  let acc = 0;
  for (let i = 0; i < 5_000_000; i++) acc = (acc + i) % 7;
  await tick();
  const busyMs = Date.now() - t0;

  const s0 = Date.now();
  await new Promise((r) => setTimeout(r, 300));
  const sleepMs = Date.now() - s0;

  const bench = await benchPbkdf2(30_000, 5);

  return c.json({ busyMs, sleepMs, bench, acc, now: Date.now() });
});

app.get('/__cpu', async (c) => {
  const tick = () => new Promise((r) => setTimeout(r, 0));
  const n = Math.min(50, Math.max(1, Number(c.req.query('n') ?? 3) || 3));
  await tick();
  const start = Date.now();
  for (let i = 0; i < n; i++) {
    await hashPassword(`bench-pass-${i}-abcdefghij`);
  }
  await tick();
  return c.json({ n, totalMs: Date.now() - start });
});

app.route('/', authRoutes);
app.route('/', appRoutes);
app.route('/', shopRoutes);
app.route('/', adminRoutes);

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
