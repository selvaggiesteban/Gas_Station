import { describe, expect, it } from 'vitest';
import { SELF, env } from 'cloudflare:test';
import { createDb } from '../src/db';
import {
  awardLoginPoints,
  isLateLogin,
  minutesOfDayBuenosAires,
} from '../src/services/points';

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

  it('/registro es público y monocromo', async () => {
    const res = await get('/registro');
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('Creá tu cuenta');
    expect(html).toContain('class="public"');
    expect(html).not.toContain('#eb7716');
  });

  it('las páginas autenticadas son monocromas (sin naranja de marca)', async () => {
    const reg = await register('Naranja Prueba', 'naranja@test.com');
    const cookie = sessionCookie(reg);
    const app = await get('/app', cookie);
    expect(app.status).toBe(200);
    const html = await app.text();
    expect(html).not.toContain('class="public"');
    expect(html).not.toContain('#eb7716');
    expect(html).not.toContain('transition:');
    expect(html).not.toContain('scroll-behavior');
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

  it('el login otorga 10 puntos, registra el movimiento con hora y no suma de nuevo el mismo día', async () => {
    await register('Sofia Puntual', 'sofia.login@test.com');
    const res = await post('/login', {
      email: 'sofia.login@test.com',
      password: 'supersecret1',
    });
    expect(res.status).toBe(302);
    const user = await env.DB.prepare(
      'select id, points from users where email = ?',
    )
      .bind('sofia.login@test.com')
      .first<{ id: number; points: number }>();
    // El login otorga +10 puntos.
    expect(user?.points).toBe(10);
    const log = await env.DB.prepare(
      'select points, reason from point_logs where user_id = ? order by id desc limit 1',
    )
      .bind(user!.id)
      .first<{ points: number; reason: string }>();
    expect(log?.reason).toBe('login');
    expect(log?.points).toBe(10);
    // En "Últimos movimientos" se ven la fecha y hora del inicio de sesión.
    const app = await get('/app', sessionCookie(res));
    expect(app.status).toBe(200);
    const html = await app.text();
    expect(html).toMatch(
      /\d{2}\/\d{2}\/\d{4} \d{2}:\d{2} — Inicio de sesión/,
    );
    expect(html).toContain('+10 pts');
    expect(html).not.toContain('+0 pts');
    // Segundo login el mismo día: no vuelve a sumar ni crea otra fila
    // (gate de 1 log por día, así login_days no se infla).
    const second = await post('/login', {
      email: 'sofia.login@test.com',
      password: 'supersecret1',
    });
    expect(second.status).toBe(302);
    const rows = await env.DB.prepare(
      `select count(*) as n from point_logs pl
       join users u on u.id = pl.user_id
       where u.email = 'sofia.login@test.com' and pl.reason = 'login'`,
    ).first<{ n: number }>();
    expect(rows?.n).toBe(1);
    const again = await env.DB.prepare(
      "select points from users where email = 'sofia.login@test.com'",
    ).first<{ points: number }>();
    expect(again?.points).toBe(10);
  });

  it('el login del admin queda registrado en el log (0 puntos) y no suma puntos', async () => {
    await register('Rita Admin', 'rita.adminlogin@test.com');
    await env.DB.prepare(
      "update users set role = 'admin' where email = 'rita.adminlogin@test.com'",
    ).run();
    const res = await post('/login', {
      email: 'rita.adminlogin@test.com',
      password: 'supersecret1',
    });
    expect(res.status).toBe(302);
    const log = await env.DB.prepare(
      `select count(*) as n from point_logs pl
       join users u on u.id = pl.user_id
       where u.email = 'rita.adminlogin@test.com'
         and pl.reason = 'login' and pl.points = 0`,
    ).first<{ n: number }>();
    expect(log?.n).toBe(1);
    const user = await env.DB.prepare(
      "select points from users where email = 'rita.adminlogin@test.com'",
    ).first<{ points: number }>();
    expect(user?.points).toBe(0);
  });
});

type UsuarioLogin = {
  id: number;
  points: number;
  lastLoginDay: string | null;
  loginStreak: number;
  role: string;
};

async function leerUsuario(email: string): Promise<UsuarioLogin> {
  const row = await env.DB.prepare(
    `select id, points, last_login_day as lastLoginDay,
            login_streak as loginStreak, role
     from users where email = ?`,
  )
    .bind(email)
    .first<UsuarioLogin>();
  if (!row) throw new Error(`no se encontró el usuario ${email}`);
  return row;
}

async function crearUsuario(
  email: string,
  role: string,
  points: number,
): Promise<UsuarioLogin> {
  await env.DB.prepare(
    'insert into users (name, email, password_hash, role, points) values (?, ?, ?, ?, ?)',
  )
    .bind('Usuario Puntualidad', email, 'x', role, points)
    .run();
  return leerUsuario(email);
}

async function puntosDe(id: number): Promise<number> {
  const row = await env.DB.prepare('select points from users where id = ?')
    .bind(id)
    .first<{ points: number }>();
  return row!.points;
}

async function ultimoLoginLog(
  userId: number,
): Promise<{ points: number; reason: string }> {
  const row = await env.DB.prepare(
    'select points, reason from point_logs where user_id = ? order by id desc limit 1',
  )
    .bind(userId)
    .first<{ points: number; reason: string }>();
  if (!row) throw new Error('no hay fila de log de login');
  return row;
}

describe('puntualidad de turnos (store_manager)', () => {
  it('playero en hora (06:05) recibe +10', async () => {
    const email = 'playero.enhora@test.com';
    const playero = await crearUsuario(email, 'store_manager', 100);
    const db = createDb(env.DB);
    // 09:05Z = 06:05 en Argentina → dentro de la ventana 05:45-06:15.
    const logPoints = await awardLoginPoints(
      db,
      playero,
      new Date('2026-11-18T09:05:00Z'),
    );
    expect(logPoints).toBe(10);
    expect(await puntosDe(playero.id)).toBe(110);
    const log = await ultimoLoginLog(playero.id);
    expect(log.reason).toBe('login');
    expect(log.points).toBe(10);
    const row = await env.DB.prepare(
      'select last_login_day from users where id = ?',
    )
      .bind(playero.id)
      .first<{ last_login_day: string }>();
    expect(row?.last_login_day).toBe('2026-11-18');
  });

  it('playero tarde (06:30) no suma el +10 y resta 15', async () => {
    const email = 'playero.tarde@test.com';
    const playero = await crearUsuario(email, 'store_manager', 100);
    const db = createDb(env.DB);
    // 09:30Z = 06:30 en Argentina → fuera de toda ventana de turno.
    const logPoints = await awardLoginPoints(
      db,
      playero,
      new Date('2026-11-18T09:30:00Z'),
    );
    expect(logPoints).toBe(-15);
    expect(await puntosDe(playero.id)).toBe(85);
    const log = await ultimoLoginLog(playero.id);
    expect(log.reason).toBe('login');
    expect(log.points).toBe(-15);
    // Solo aplica al primer login del día: repetir no vuelve a restar.
    const fresco = await leerUsuario(email);
    const otraVez = await awardLoginPoints(
      db,
      fresco,
      new Date('2026-11-18T09:30:00Z'),
    );
    expect(otraVez).toBe(0);
    expect(await puntosDe(playero.id)).toBe(85);
    const filas = await env.DB.prepare(
      "select count(*) as n from point_logs where user_id = ? and reason = 'login'",
    )
      .bind(playero.id)
      .first<{ n: number }>();
    expect(filas?.n).toBe(1);
  });

  it('playero tarde con 5 puntos queda en 0 (log -5)', async () => {
    const playero = await crearUsuario(
      'playero.pocos@test.com',
      'store_manager',
      5,
    );
    const db = createDb(env.DB);
    const logPoints = await awardLoginPoints(
      db,
      playero,
      new Date('2026-11-18T09:30:00Z'),
    );
    expect(logPoints).toBe(-5);
    expect(await puntosDe(playero.id)).toBe(0);
    const log = await ultimoLoginLog(playero.id);
    expect(log.points).toBe(-5);
  });

  it('customer a cualquier hora (03:00) recibe +10 sin resta', async () => {
    const customer = await crearUsuario('customer.0300@test.com', 'customer', 0);
    const db = createDb(env.DB);
    // 06:00Z = 03:00 en Argentina → la puntualidad no aplica a customers.
    const logPoints = await awardLoginPoints(
      db,
      customer,
      new Date('2026-11-18T06:00:00Z'),
    );
    expect(logPoints).toBe(10);
    expect(await puntosDe(customer.id)).toBe(10);
    const log = await ultimoLoginLog(customer.id);
    expect(log.points).toBe(10);
  });
});

describe('isLateLogin (función pura)', () => {
  it('bordes de las ventanas de turno = en hora', () => {
    expect(isLateLogin('store_manager', 5 * 60 + 45).late).toBe(false); // 05:45
    expect(isLateLogin('store_manager', 6 * 60 + 15).late).toBe(false); // 06:15
    expect(isLateLogin('store_manager', 14 * 60 + 15).late).toBe(false); // 14:15
    expect(isLateLogin('store_manager', 22 * 60 + 15).late).toBe(false); // 22:15
  });

  it('fuera de la ventana = tarde', () => {
    expect(isLateLogin('store_manager', 6 * 60 + 16).late).toBe(true); // 06:16
    expect(isLateLogin('store_manager', 3 * 60).late).toBe(true); // 03:00
    expect(isLateLogin('store_manager', 5 * 60 + 44).late).toBe(true); // 05:44
    expect(isLateLogin('store_manager', 6 * 60 + 30).late).toBe(true); // 06:30
  });

  it('solo aplica a store_manager', () => {
    expect(isLateLogin('customer', 3 * 60).late).toBe(false);
    expect(isLateLogin('admin', 6 * 60 + 30).late).toBe(false);
    expect(isLateLogin('customer', 3 * 60).expected).toBe(
      'sin control de puntualidad',
    );
  });

  it('expected describe el turno o las ventanas esperadas', () => {
    expect(isLateLogin('store_manager', 6 * 60 + 5).expected).toBe(
      'turno mañana (05:45-06:15)',
    );
    expect(isLateLogin('store_manager', 3 * 60).expected).toBe(
      '05:45-06:15, 13:45-14:15, 21:45-22:15',
    );
  });
});

describe('minutesOfDayBuenosAires (UTC-3 fijo)', () => {
  it('convierte instantes UTC a minutos del día en Argentina', () => {
    expect(minutesOfDayBuenosAires(new Date('2026-11-18T09:05:00Z'))).toBe(
      6 * 60 + 5,
    ); // 06:05
    expect(minutesOfDayBuenosAires(new Date('2026-11-18T03:00:00Z'))).toBe(0); // 00:00
    expect(minutesOfDayBuenosAires(new Date('2026-11-18T01:59:00Z'))).toBe(
      22 * 60 + 59,
    ); // 22:59 del día anterior
  });
});
