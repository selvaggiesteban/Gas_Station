import { describe, expect, it } from 'vitest';
import { app } from '../src/index';

describe('app', () => {
  it('GET / responde con estado ok', async () => {
    const res = await app.request('/');
    expect(res.status).toBe(200);
    const body = await res.json();
    expect(body).toMatchObject({ app: 'Gas Station', status: 'ok' });
  });

  it('GET desconocido responde 404', async () => {
    const res = await app.request('/no-existe');
    expect(res.status).toBe(404);
  });
});
