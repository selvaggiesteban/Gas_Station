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

let seq = 0;
function uniqueEmail(prefix: string): string {
  seq += 1;
  return `${prefix}-${Date.now()}-${seq}@test.com`;
}

async function register(nombre: string, email: string): Promise<Response> {
  return post('/registro', {
    nombre,
    email,
    password: 'supersecret1',
    password2: 'supersecret1',
  });
}

async function registerAs(
  nombre: string,
  email: string,
  role: string,
): Promise<{ cookie: string; id: number }> {
  const res = await register(nombre, email);
  expect(res.status).toBe(302);
  const cookie = sessionCookie(res);
  const row = await env.DB.prepare('select id from users where email = ?')
    .bind(email)
    .first<{ id: number }>();
  await env.DB.prepare('update users set role = ? where email = ?')
    .bind(role, email)
    .run();
  return { cookie, id: row!.id };
}

async function countUsersByEmail(email: string): Promise<number> {
  const row = await env.DB.prepare(
    'select count(*) as n from users where email = ?',
  )
    .bind(email)
    .first<{ n: number }>();
  return row!.n;
}

async function roleByEmail(email: string): Promise<string | null> {
  const row = await env.DB.prepare('select role from users where email = ?')
    .bind(email)
    .first<{ role: string }>();
  return row?.role ?? null;
}

function passwordDe(html: string): string {
  const m = html.match(/<code>([^<]+)<\/code>/);
  if (!m) throw new Error('no se mostró la contraseña en <code>');
  return m[1]!;
}

describe('alta de clientes desde playa', () => {
  it('el sidebar del playero tiene Nuevo cliente y el del admin no', async () => {
    const playero = await registerAs(
      'Playero Sidebar',
      uniqueEmail('playero-sidebar'),
      'store_manager',
    );
    const admin = await registerAs(
      'Admin Sidebar',
      uniqueEmail('admin-sidebar'),
      'admin',
    );

    const comoPlayero = await get('/admin/ordenes', playero.cookie);
    expect(comoPlayero.status).toBe(200);
    const htmlPlayero = await comoPlayero.text();
    expect(htmlPlayero).toContain('Nuevo cliente');
    expect(htmlPlayero).toContain('/admin/clientes/nuevo');

    const comoAdmin = await get('/admin', admin.cookie);
    expect(comoAdmin.status).toBe(200);
    const htmlAdmin = await comoAdmin.text();
    expect(htmlAdmin).toContain('/admin/ordenes');
    expect(htmlAdmin).not.toContain('/admin/clientes/nuevo');
  });

  it('GET /admin/clientes/nuevo muestra el formulario al playero y pide login sin sesión', async () => {
    const sinSesion = await get('/admin/clientes/nuevo');
    expect(sinSesion.status).toBe(302);
    expect(sinSesion.headers.get('location')).toBe('/login');

    const playero = await registerAs(
      'Playero Form',
      uniqueEmail('playero-form'),
      'store_manager',
    );
    const res = await get('/admin/clientes/nuevo', playero.cookie);
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('Nuevo cliente');
    expect(html).toContain('name="nombre"');
    expect(html).toContain('minlength="2"');
    expect(html).toContain('name="email"');
    expect(html).toContain('type="email"');
    // Sin campo de contraseña: la genera el sistema.
    expect(html).not.toContain('name="password"');
  });

  it('el POST crea el customer, muestra la contraseña una vez y no toca la sesión del playero', async () => {
    const playero = await registerAs(
      'Playero Alta',
      uniqueEmail('playero-alta'),
      'store_manager',
    );
    const email = uniqueEmail('cliente-alta');

    const res = await post(
      '/admin/clientes/nuevo',
      { nombre: 'Cliente Playa', email },
      playero.cookie,
    );
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('Cliente creado');
    expect(html).toContain('<code>');
    expect(html).toContain('no se vuelve a mostrar');

    const password = passwordDe(html);
    expect(password).toMatch(/^[A-Za-z0-9]{12}$/);

    expect(await countUsersByEmail(email)).toBe(1);
    expect(await roleByEmail(email)).toBe('customer');

    // La sesión del playero sigue intacta: puede seguir operando.
    const ordenes = await get('/admin/ordenes', playero.cookie);
    expect(ordenes.status).toBe(200);

    // Y el cliente recién creado puede entrar con la contraseña generada.
    const login = await post('/login', { email, password });
    expect(login.status).toBe(302);
    expect(login.headers.get('location')).toBe('/app');
  });

  it('un email duplicado no crea un segundo usuario', async () => {
    const playero = await registerAs(
      'Playero Duplicado',
      uniqueEmail('playero-dup'),
      'store_manager',
    );
    const email = uniqueEmail('cliente-dup');

    const primera = await post(
      '/admin/clientes/nuevo',
      { nombre: 'Cliente Duplicado', email },
      playero.cookie,
    );
    expect(primera.status).toBe(200);

    const segunda = await post(
      '/admin/clientes/nuevo',
      { nombre: 'Cliente Duplicado Otra Vez', email },
      playero.cookie,
    );
    expect(segunda.status).toBe(422);
    expect(await segunda.text()).toContain('Ya existe una cuenta con ese email.');
    expect(await countUsersByEmail(email)).toBe(1);
  });

  it('un customer recibe 403 en GET /admin/clientes/nuevo', async () => {
    const customer = await registerAs(
      'Cliente Común',
      uniqueEmail('customer-alta'),
      'customer',
    );
    const res = await get('/admin/clientes/nuevo', customer.cookie);
    expect(res.status).toBe(403);
    expect(await res.text()).toContain('No tienes permiso');
  });
});
