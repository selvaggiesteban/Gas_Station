import { describe, expect, it } from 'vitest';
import { SELF } from 'cloudflare:test';

const BASE = 'https://gas-station.test';

const CALIDO = [
  '#eb7716',
  '#f5f4f2',
  '#1c1917',
  '#e7e5e4',
  '#d6d3d1',
  '#57534e',
  '#78716c',
  '#44403c',
  '#fef2f2',
  '#fecaca',
  '#b91c1c',
  '#f0fdf4',
  '#bbf7d0',
  '#15803d',
];

async function get(path: string): Promise<string> {
  const res = await SELF.fetch(BASE + path, { redirect: 'manual' });
  expect(res.status).toBe(200);
  return res.text();
}

describe('app', () => {
  it('GET / muestra el landing público con la marca', async () => {
    const res = await SELF.fetch(BASE + '/', { redirect: 'manual' });
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('LESCO GNC');
    expect(html).toContain('Creá tu cuenta');
    expect(html).toContain('lang="es-AR"');
    expect(html).toContain('class="public"');
  });

  it('home muestra logotipo centrado y botones Acceder / Crear cuenta', async () => {
    const html = await get('/');
    expect(html).toContain('<section class="home">');
    expect(html).toContain('data:image/png;base64');
    expect(html).toContain('<a class="btn lg" href="/registro">Creá tu cuenta</a>');
    expect(html).toContain('<a class="btn secondary lg" href="/login">Accedé</a>');
  });

  it('landing público es estrictamente monocromo', async () => {
    const html = await get('/');
    expect(html).toContain('class="public"');
    for (const color of CALIDO) {
      expect(html).not.toContain(color);
    }
    expect(html).not.toContain('--brand: #eb7716');
    expect(html).toContain('body.public { --brand: #0a0a0a');
  });

  it('/login es público y monocromo', async () => {
    const html = await get('/login');
    expect(html).toContain('class="public"');
    expect(html).not.toContain('#eb7716');
  });

  it('ruta desconocida responde 404', async () => {
    const res = await SELF.fetch(BASE + '/no-existe');
    expect(res.status).toBe(404);
  });
});
