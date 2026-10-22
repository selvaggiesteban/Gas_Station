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

let seq = 0;
function uniqueEmail(prefix: string): string {
  seq += 1;
  return `${prefix}-${Date.now()}-${seq}@test.com`;
}

async function registerAs(
  nombre: string,
  email: string,
  role: string,
): Promise<{ cookie: string; id: number }> {
  const res = await register(nombre, email);
  const cookie = sessionCookie(res);
  const row = await env.DB.prepare('select id from users where email = ?')
    .bind(email)
    .first<{ id: number }>();
  await env.DB.prepare('update users set role = ? where email = ?')
    .bind(role, email)
    .run();
  return { cookie, id: row!.id };
}

describe('admin panel', () => {
  it('/admin/usuarios controla acceso por sesión y rol', async () => {
    const sinSesion = await get('/admin/usuarios');
    expect(sinSesion.status).toBe(302);
    expect(sinSesion.headers.get('location')).toBe('/login');

    const email = uniqueEmail('customer');
    const reg = await register('Cliente Prueba', email);
    const cookie = sessionCookie(reg);
    const comoCustomer = await get('/admin/usuarios', cookie);
    expect(comoCustomer.status).toBe(403);

    await env.DB.prepare("update users set role = 'admin' where email = ?")
      .bind(email)
      .run();
    const comoAdmin = await get('/admin/usuarios', cookie);
    expect(comoAdmin.status).toBe(200);
    expect(await comoAdmin.text()).toContain('Buscá');
  });

  it('permite cambiar el rol de un usuario a store_manager', async () => {
    const { cookie } = await registerAs('Admin Roles', uniqueEmail('admin-rol'), 'admin');
    const targetEmail = uniqueEmail('target-rol');
    await register('User Target', targetEmail);
    const target = await env.DB.prepare('select id from users where email = ?')
      .bind(targetEmail)
      .first<{ id: number }>();

    const res = await post(
      `/admin/usuarios/${target!.id}/rol`,
      { role: 'store_manager' },
      cookie,
    );
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/admin/usuarios?aviso=ok');

    const after = await env.DB.prepare('select role from users where id = ?')
      .bind(target!.id)
      .first<{ role: string }>();
    expect(after?.role).toBe('store_manager');
  });

  it('crea tienda y asigna un gestor', async () => {
    const { cookie } = await registerAs('Admin Tiendas', uniqueEmail('admin-tiendas'), 'admin');
    const tienda = `YPF Centro ${Date.now()}-${seq++}`;
    const creada = await post(
      '/admin/tiendas',
      { name: tienda, zone: 'Norte' },
      cookie,
    );
    expect(creada.status).toBe(302);
    expect(creada.headers.get('location')).toBe('/admin/tiendas?aviso=ok');

    const store = await env.DB.prepare(
      'select id, zone, active from stores where name = ?',
    )
      .bind(tienda)
      .first<{ id: number; zone: string | null; active: number }>();
    expect(store).toBeTruthy();
    expect(store!.zone).toBe('Norte');
    expect(store!.active).toBe(1);

    const gestorEmail = uniqueEmail('gestor');
    await register('Gestor Uno', gestorEmail);
    await env.DB.prepare("update users set role = 'store_manager' where email = ?")
      .bind(gestorEmail)
      .run();
    const gestor = await env.DB.prepare('select id from users where email = ?')
      .bind(gestorEmail)
      .first<{ id: number }>();

    const asignado = await post(
      `/admin/tiendas/${store!.id}/gestores`,
      { userId: String(gestor!.id) },
      cookie,
    );
    expect(asignado.status).toBe(302);
    expect(asignado.headers.get('location')).toBe(
      `/admin/tiendas/${store!.id}?aviso=ok`,
    );

    const link = await env.DB.prepare(
      'select user_id from store_user where store_id = ? and user_id = ?',
    )
      .bind(store!.id, gestor!.id)
      .first<{ user_id: number }>();
    expect(link?.user_id).toBe(gestor!.id);
  });

  it('crea desafío con meta, ventana y recompensa', async () => {
    const { cookie } = await registerAs('Admin Desafios', uniqueEmail('admin-desafios'), 'admin');
    const res = await post(
      '/admin/desafios',
      {
        title: 'Semana gasolera',
        description: 'Sumá puntos',
        goalType: 'points',
        goalValue: '100',
        startsAt: '2026-01-05T10:00',
        endsAt: '2026-01-12T10:00',
        rewardPoints: '50',
        active: 'on',
      },
      cookie,
    );
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('/admin/desafios?aviso=ok');

    const row = await env.DB.prepare(
      `select goal_type, goal_value, starts_at, ends_at, reward_points, active
       from weekly_challenges where title = ?`,
    )
      .bind('Semana gasolera')
      .first<{
        goal_type: string;
        goal_value: number;
        starts_at: number;
        ends_at: number;
        reward_points: number;
        active: number;
      }>();
    expect(row).toBeTruthy();
    expect(row!.goal_type).toBe('points');
    expect(row!.goal_value).toBe(100);
    expect(row!.starts_at).toBe(Date.parse('2026-01-05T10:00'));
    expect(row!.ends_at).toBe(Date.parse('2026-01-12T10:00'));
    expect(row!.reward_points).toBe(50);
    expect(row!.active).toBe(1);
  });

  it('impide que un admin se desactive a sí mismo', async () => {
    const { cookie, id } = await registerAs('Admin Auto', uniqueEmail('admin-auto'), 'admin');
    const res = await post(
      `/admin/usuarios/${id}/activo`,
      { active: '0' },
      cookie,
    );
    expect(res.status).toBe(422);
    expect(await res.text()).toContain('No podés desactivarte');

    const after = await env.DB.prepare('select active from users where id = ?')
      .bind(id)
      .first<{ active: number }>();
    expect(after?.active).toBe(1);
  });
});
