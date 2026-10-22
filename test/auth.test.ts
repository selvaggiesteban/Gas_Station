import { describe, expect, it } from 'vitest';
import { SELF, env } from 'cloudflare:test';

const BASE = 'https://gas-station.test';

function encode(data: Record<string, string>): string {
  return new URLSearchParams(data).toString();
}

async function post(path: string, data: Record<string, string>, cookie?: string) {
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

async function register(
  nombre: string,
  email: string,
  password = 'supersecret1',
) {
  const res = await post('/registro', {
    nombre,
    email,
    password,
    password2: password,
  });
  return res;
}

describe('auth', () => {
  it('registro crea sesión y da acceso a /app', async () => {
    const res = await register('Ana Prueba', 'ana@test.com');
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/app');
    const cookie = sessionCookie(res);
    const app = await get('/app', cookie);
    expect(app.status).toBe(200);
    expect(await app.text()).toContain('Ana Prueba');
  });

  it('/app sin sesión redirige a /login', async () => {
    const res = await get('/app');
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/login');
  });

  it('login con contraseña incorrecta responde error', async () => {
    await register('Luis Prueba', 'luis@test.com');
    const res = await post('/login', {
      email: 'luis@test.com',
      password: 'otracontrasena',
    });
    expect(res.status).toBe(422);
  });

  it('login correcto inicia sesión', async () => {
    await register('Carla Prueba', 'carla@test.com');
    const res = await post('/login', {
      email: 'carla@test.com',
      password: 'supersecret1',
    });
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/app');
    sessionCookie(res);
  });

  it('registro duplicado responde error', async () => {
    await register('Pedro Duplicado', 'pedro@test.com');
    const res = await register('Pedro Duplicado', 'pedro@test.com');
    expect(res.status).toBe(422);
    expect(await res.text()).toContain('Ya existe una cuenta');
  });

  it('flujo completo de recuperación de contraseña', async () => {
    await register('Marta Recupera', 'marta@test.com');
    const forgot = await post('/olvide-contrasena', {
      email: 'marta@test.com',
    });
    expect(forgot.status).toBe(200);
    const row = await env.DB.prepare(
      'select token from password_reset_tokens where email = ? order by id desc limit 1',
    )
      .bind('marta@test.com')
      .first<{ token: string }>();
    expect(row?.token).toBeTruthy();
    const reset = await post('/restablecer', {
      token: row!.token,
      password: 'nuevapass1234',
      password2: 'nuevapass1234',
    });
    expect(reset.status).toBe(302);
    expect(reset.headers.get('location')).toBe('/login?aviso=ok');
    const login = await post('/login', {
      email: 'marta@test.com',
      password: 'nuevapass1234',
    });
    expect(login.status).toBe(302);
    sessionCookie(login);
  });

  it('/admin exige rol admin', async () => {
    const reg = await register('Solo Cliente', 'cliente@test.com');
    const cookie = sessionCookie(reg);
    const denied = await get('/admin', cookie);
    expect(denied.status).toBe(403);
    await env.DB.prepare(
      "update users set role = 'admin' where email = 'cliente@test.com'",
    ).run();
    const allowed = await get('/admin', cookie);
    expect(allowed.status).toBe(200);
  });
});
