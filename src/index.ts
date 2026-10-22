import { Hono } from 'hono';
import { createDb } from './db';
import { jobs } from './db/schema';
import { sql } from 'drizzle-orm';

type Bindings = {
  DB: D1Database;
  R2: R2Bucket;
};

const app = new Hono<{ Bindings: Bindings }>();

app.get('/', (c) =>
  c.json({
    app: 'Gas Station',
    version: '0.1.0',
    status: 'ok',
  }),
);

app.get('/health', async (c) => {
  const db = createDb(c.env.DB);
  const row = await db
    .select({ one: sql<number>`1` })
    .from(jobs)
    .limit(1)
    .then((r) => r.length >= 0);
  return c.json({ ok: row, db: 'up', time: new Date().toISOString() });
});

app.notFound((c) => c.json({ error: 'no encontrado' }, 404));

app.onError((err, c) => {
  console.error('unhandled', err);
  return c.json({ error: 'error interno' }, 500);
});

async function scheduledHandler(_controller: ScheduledController, env: Bindings) {
  const db = createDb(env.DB);
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
