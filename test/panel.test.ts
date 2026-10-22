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

async function register(
  nombre: string,
  email: string,
  password = 'supersecret1',
) {
  return post('/registro', {
    nombre,
    email,
    password,
    password2: password,
  });
}

async function insertChallenge(opts: {
  title: string;
  goalValue: number;
  rewardPoints: number;
  windowMs?: number;
}): Promise<number> {
  const now = Date.now();
  const windowMs = opts.windowMs ?? 86_400_000;
  await env.DB.prepare(
    `insert into weekly_challenges
       (title, description, goal_type, goal_value, starts_at, ends_at, reward_points, active)
     values (?, ?, 'points', ?, ?, ?, ?, 1)`,
  )
    .bind(
      opts.title,
      'Desafío semanal de prueba.',
      opts.goalValue,
      now - windowMs,
      now + windowMs,
      opts.rewardPoints,
    )
    .run();
  const row = await env.DB.prepare(
    'select id from weekly_challenges order by id desc limit 1',
  ).first<{ id: number }>();
  if (!row) throw new Error('no se creó el desafío');
  return row.id;
}

describe('panel de usuario', () => {
  it('/app sin sesión redirige a /login', async () => {
    const res = await get('/app');
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/login');
  });

  it('/app muestra el nombre y los puntos tras registrarse', async () => {
    const reg = await register('Ana Panel', 'ana.panel@test.com');
    expect(reg.status).toBe(302);
    const cookie = sessionCookie(reg);
    const app = await get('/app', cookie);
    expect(app.status).toBe(200);
    const html = await app.text();
    expect(html).toContain('Ana Panel');
    expect(html).toContain('puntos');
  });

  it('unirse a un desafío de puntos muestra progreso en /app', async () => {
    const reg = await register('Diego Desafio', 'diego.panel@test.com');
    const login = await post('/login', {
      email: 'diego.panel@test.com',
      password: 'supersecret1',
    });
    const cookie = sessionCookie(login);
    const challengeId = await insertChallenge({
      title: 'Sumá 50 puntos',
      goalValue: 50,
      rewardPoints: 100,
    });
    const join = await post(`/desafios/${challengeId}/unirse`, {}, cookie);
    expect(join.status).toBe(302);
    expect(join.headers.get('location')).toMatch(/^\/app/);
    const app = await get('/app', cookie);
    expect(app.status).toBe(200);
    const html = await app.text();
    expect(html).toContain('Sumá 50 puntos');
    expect(html).toContain('10 / 50');
    const part = await env.DB.prepare(
      `select p.progress from challenge_participations p
       join users u on u.id = p.user_id
       where p.challenge_id = ? and u.email = ?`,
    )
      .bind(challengeId, 'diego.panel@test.com')
      .first<{ progress: number }>();
    expect(part?.progress).toBe(10);
    const again = await post(`/desafios/${challengeId}/unirse`, {}, cookie);
    expect(again.status).toBe(302);
    expect(again.headers.get('location')).toContain('error=');
  });

  it('completa un desafío, otorga la recompensa y es idempotente', async () => {
    const reg = await register('Eva Completa', 'eva.panel@test.com');
    const cookie = sessionCookie(reg);
    await env.DB.prepare(
      `insert into point_logs (user_id, points, reason)
       select id, 25, 'login' from users where email = 'eva.panel@test.com'`,
    ).run();
    const challengeId = await insertChallenge({
      title: 'Desafío mínimo',
      goalValue: 1,
      rewardPoints: 100,
      windowMs: 3_600_000,
    });
    const join = await post(`/desafios/${challengeId}/unirse`, {}, cookie);
    expect(join.status).toBe(302);
    const app = await get('/app', cookie);
    expect(app.status).toBe(200);
    expect(await app.text()).toContain('Completado');
    const part = await env.DB.prepare(
      `select p.progress, p.completed_at, p.rewarded_at
       from challenge_participations p
       join users u on u.id = p.user_id
       where p.challenge_id = ? and u.email = ?`,
    )
      .bind(challengeId, 'eva.panel@test.com')
      .first<{ progress: number; completed_at: number; rewarded_at: number }>();
    expect(part?.progress).toBe(25);
    expect(part?.completed_at).toBeTruthy();
    expect(part?.rewarded_at).toBeTruthy();
    const user = await env.DB.prepare(
      "select points from users where email = 'eva.panel@test.com'",
    ).first<{ points: number }>();
    expect(user?.points).toBe(100);
    const again = await get('/app', cookie);
    expect(again.status).toBe(200);
    const after = await env.DB.prepare(
      "select points from users where email = 'eva.panel@test.com'",
    ).first<{ points: number }>();
    expect(after?.points).toBe(100);
  });

  it('/perfil actualiza el nombre con la contraseña actual', async () => {
    const reg = await register('Paula Perfil', 'paula.panel@test.com');
    const cookie = sessionCookie(reg);
    const res = await post(
      '/perfil',
      {
        nombre: 'Paula Nueva',
        password_actual: 'supersecret1',
        password_nueva: '',
        password_nueva2: '',
      },
      cookie,
    );
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/perfil?aviso=ok');
    const perfil = await get('/perfil', cookie);
    expect(perfil.status).toBe(200);
    expect(await perfil.text()).toContain('Paula Nueva');
  });

  it('/perfil rechaza la contraseña actual incorrecta', async () => {
    const reg = await register('Pedro Perfil', 'pedro.panel@test.com');
    const cookie = sessionCookie(reg);
    const res = await post(
      '/perfil',
      {
        nombre: 'Pedro Nuevo',
        password_actual: 'contraseña-mala',
        password_nueva: 'nuevapass1234',
        password_nueva2: 'nuevapass1234',
      },
      cookie,
    );
    expect(res.status).toBe(422);
    expect(await res.text()).toContain('contraseña actual');
    const check = await env.DB.prepare(
      "select name from users where email = 'pedro.panel@test.com'",
    ).first<{ name: string }>();
    expect(check?.name).toBe('Pedro Perfil');
  });
});
