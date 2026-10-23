import { describe, expect, it } from 'vitest';
import { SELF, env } from 'cloudflare:test';

const BASE = 'https://gas-station.test';

function encode(data: Record<string, string>): string {
  return new URLSearchParams(data).toString();
}

async function post(
  path: string,
  data: Record<string, string>,
  cookie?: string,
) {
  return SELF.fetch(BASE + path, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      ...(cookie ? { cookie } : {}),
    },
    body: encode(data),
    redirect: 'manual',
  });
}

async function get(path: string, cookie?: string) {
  return SELF.fetch(BASE + path, {
    headers: cookie ? { cookie } : {},
    redirect: 'manual',
  });
}

function sessionCookie(res: Response): string {
  const cookies = res.headers.getSetCookie();
  const found = cookies
    .map((c) => c.split(';')[0] ?? '')
    .find((c) => c.startsWith('gs_session='));
  if (!found) throw new Error('no se recibió cookie de sesión');
  return found;
}

async function register(nombre: string, email: string) {
  return post('/registro', {
    nombre,
    email,
    password: 'supersecret1',
    password2: 'supersecret1',
  });
}

async function login(email: string) {
  return post('/login', { email, password: 'supersecret1' });
}

async function setPoints(email: string, points: number): Promise<void> {
  await env.DB.prepare('update users set points = ? where email = ?')
    .bind(points, email)
    .run();
}

async function setRole(email: string, role: string): Promise<void> {
  await env.DB.prepare('update users set role = ? where email = ?')
    .bind(role, email)
    .run();
}

describe('ranking', () => {
  it('/ranking sin sesión redirige a /login', async () => {
    const res = await get('/ranking');
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/login');
  });

  it('filtro de gestores sin usuarios muestra estado vacío', async () => {
    const reg = await register('Ana Top', 'ana.ranking@test.com');
    const cookie = sessionCookie(reg);
    const res = await get('/ranking?rol=gestores', cookie);
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('Todavía no hay usuarios en el ranking');
    expect(html).toContain('<span class="pill">Gestores</span>');
    expect(html).toContain('href="/ranking?rol=clientes"');
    expect(html).toContain('href="/ranking?rol=todos"');
  });

  it('ordena por puntos, muestra posiciones y resalta al usuario', async () => {
    const cookieAna = sessionCookie(await login('ana.ranking@test.com'));
    await setPoints('ana.ranking@test.com', 100);
    await register('Bruno Top', 'bruno.ranking@test.com');
    await setPoints('bruno.ranking@test.com', 300);
    await register('Carla Top', 'carla.ranking@test.com');
    await setPoints('carla.ranking@test.com', 200);

    const res = await get('/ranking', cookieAna);
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('<strong>1</strong> Bruno Top');
    expect(html).toContain('<strong>2</strong> Carla Top');
    expect(html).toContain('<strong>3</strong> Ana Top');
    expect(html).toContain('300 pts');
    expect(html).toContain('200 pts');
    expect(html).toContain('100 pts');
    expect(html.indexOf('data-pos="1"')).toBeLessThan(
      html.indexOf('data-pos="2"'),
    );
    expect(html.indexOf('data-pos="2"')).toBeLessThan(
      html.indexOf('data-pos="3"'),
    );
    expect(html).toContain('class="rank-row me"');
    expect(html).toContain('data-pos="3"');
  });

  it('filtra por rol clientes y gestores', async () => {
    await setRole('bruno.ranking@test.com', 'store_manager');
    await register('Goyo Gestor', 'goyo.ranking@test.com');
    await setRole('goyo.ranking@test.com', 'store_manager');
    await setPoints('goyo.ranking@test.com', 500);

    const cookieAna = sessionCookie(await login('ana.ranking@test.com'));

    const gestores = await get('/ranking?rol=gestores', cookieAna);
    expect(gestores.status).toBe(200);
    const gestoresHtml = await gestores.text();
    expect(gestoresHtml).toContain('<strong>1</strong> Goyo Gestor');
    expect(gestoresHtml).toContain('<strong>2</strong> Bruno Top');
    expect(gestoresHtml).toContain('500 pts');
    expect(gestoresHtml).toContain('<span class="pill">Gestores</span>');
    expect(gestoresHtml).not.toContain('rank-row me');
    expect(gestoresHtml).not.toContain('<strong>3</strong> Ana Top');

    const clientes = await get('/ranking?rol=clientes', cookieAna);
    expect(clientes.status).toBe(200);
    const clientesHtml = await clientes.text();
    expect(clientesHtml).toContain('<strong>1</strong> Carla Top');
    expect(clientesHtml).toContain('<strong>2</strong> Ana Top');
    expect(clientesHtml).toContain('class="rank-row me"');
    expect(clientesHtml).toContain('<span class="pill">Clientes</span>');
    expect(clientesHtml).not.toContain('Goyo Gestor');

    const todos = await get('/ranking', cookieAna);
    const todosHtml = await todos.text();
    expect(todosHtml).toContain('<strong>1</strong> Goyo Gestor');
    expect(todosHtml).toContain('<strong>2</strong> Bruno Top');
    expect(todosHtml).toContain('<strong>3</strong> Carla Top');
    expect(todosHtml).toContain('<strong>4</strong> Ana Top');
  });

  it('muestra tu posición cuando no entrás en el top 50', async () => {
    for (let i = 0; i < 52; i++) {
      await env.DB.prepare(
        'insert into users (name, email, password_hash, points) values (?, ?, ?, ?)',
      )
        .bind(`Filler ${i}`, `filler.ranking${i}@test.com`, 'x', i + 50)
        .run();
    }
    const reg = await register('Zane Zulu', 'zane.ranking@test.com');
    const cookie = sessionCookie(reg);
    const res = await get('/ranking', cookie);
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('Tu posición');
    expect(html).toMatch(/class="rank-row me" data-pos="\d+"/);
    expect(html).toMatch(/<strong>\d+<\/strong> Zane Zulu/);
    const topRows = (html.match(/class="rank-row"/g) ?? []).length;
    expect(topRows).toBe(50);
  });
});
