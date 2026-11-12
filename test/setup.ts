import { applyD1Migrations, env } from 'cloudflare:test';
import { inject, beforeEach } from 'vitest';

const migrations = inject('d1Migrations');
await applyD1Migrations(env.DB, migrations);

// Clear KV store before each test to avoid rate limit interference
beforeEach(async () => {
  const kv = (env as any).SESSIONS;
  if (kv && typeof kv.list === 'function') {
    const { keys } = await kv.list();
    await Promise.all(keys.map((k: { name: string }) => kv.delete(k.name)));
  }
});
