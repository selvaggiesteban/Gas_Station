import { describe, expect, it } from 'vitest';
import { SELF } from 'cloudflare:test';

const BASE = 'https://gas-station.test';

describe('app', () => {
  it('GET / muestra el landing público con la marca', async () => {
    const res = await SELF.fetch(BASE + '/', { redirect: 'manual' });
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('Gas Station');
    expect(html).toContain('Creá tu cuenta');
    expect(html).toContain('lang="es-AR"');
  });

  it('ruta desconocida responde 404', async () => {
    const res = await SELF.fetch(BASE + '/no-existe');
    expect(res.status).toBe(404);
  });
});
