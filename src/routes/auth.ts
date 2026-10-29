import { Hono } from 'hono';
import type { Context } from 'hono';
import { eq } from 'drizzle-orm';
import type { AppEnv } from '../env';
import { dbOf, brandOf } from '../middleware/auth';
import { users, passwordResetTokens } from '../db/schema';
import {
  hashPassword,
  verifyPassword,
  iterationsFromEnv,
} from '../lib/crypto';
import {
  createSession,
  setSessionCookie,
  destroySession,
  destroyAllUserSessions,
} from '../lib/session';
import { page, escapeHtml } from '../lib/html';
import { sendEmail } from '../lib/email';
import { awardLoginPoints } from '../services/points';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export const authRoutes = new Hono<AppEnv>();

function str(v: unknown): string {
  return typeof v === 'string' ? v.trim() : '';
}

function lowerEmail(v: unknown): string {
  return str(v).toLowerCase();
}

function randomHex(bytes = 32): string {
  const a = crypto.getRandomValues(new Uint8Array(bytes));
  return Array.from(a, (b) => b.toString(16).padStart(2, '0')).join('');
}

function loginForm(
  c: Context<AppEnv>,
  opts: { error?: string; notice?: string } = {},
): Response {
  return c.html(
    page(
      {
        title: 'Accedé',
        brand: brandOf(c),
        error: opts.error,
        notice: opts.notice,
      },
      `<div class="card">
        <h1>Accedé a tu cuenta</h1>
        <form method="post" action="/login">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email">
          <label for="password">Contraseña</label>
          <input id="password" name="password" type="password" required autocomplete="current-password">
          <button type="submit">Accedé</button>
        </form>
        <p class="muted"><a href="/olvide-contrasena">¿Olvidaste tu contraseña?</a> ·
        <a href="/registro">¿No tenés cuenta? Creá una</a></p>
      </div>`,
    ),
    opts.error ? 422 : 200,
  );
}

function registerForm(
  c: Context<AppEnv>,
  opts: { error?: string } = {},
): Response {
  return c.html(
    page(
      {
        title: 'Creá tu cuenta',
        brand: brandOf(c),
        error: opts.error,
      },
      `<div class="card">
        <h1>Creá tu cuenta</h1>
        <form method="post" action="/registro">
          <label for="nombre">Nombre</label>
          <input id="nombre" name="nombre" type="text" required minlength="2" autocomplete="name">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email">
          <label for="password">Contraseña (mínimo 8 caracteres)</label>
          <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
          <label for="password2">Repetí la contraseña</label>
          <input id="password2" name="password2" type="password" required minlength="8" autocomplete="new-password">
          <button type="submit">Creá tu cuenta</button>
        </form>
        <p class="muted"><a href="/login">¿Ya tenés cuenta? Accedé</a></p>
      </div>`,
    ),
    opts.error ? 422 : 200,
  );
}

authRoutes.get('/login', (c) => {
  if (c.get('user')) return c.redirect('/app');
  const notice = c.req.query('aviso');
  return loginForm(c, { notice: notice ? 'Contraseña actualizada. Accedé de nuevo.' : undefined });
});

authRoutes.post('/login', async (c) => {
  const body = await c.req.parseBody();
  const email = lowerEmail(body.email);
  const password = str(body.password);
  if (!email || !password) {
    return loginForm(c, { error: 'Completá el email y la contraseña.' });
  }
  const db = dbOf(c);
  const rows = await db
    .select()
    .from(users)
    .where(eq(users.email, email))
    .limit(1);
  const user = rows[0];
  const valid =
    !!user &&
    user.active &&
    (await verifyPassword(password, user.passwordHash));
  if (!valid) {
    return loginForm(c, { error: 'Email o contraseña incorrectos.' });
  }
  const token = await createSession(db, user.id, {
    ip: c.req.header('cf-connecting-ip') ?? null,
    userAgent: c.req.header('user-agent') ?? null,
  });
  setSessionCookie(c, token);
  if (user.role !== 'admin') {
    await awardLoginPoints(db, user);
  }
  return c.redirect('/app');
});

authRoutes.get('/registro', (c) => {
  if (c.get('user')) return c.redirect('/app');
  return registerForm(c);
});

authRoutes.post('/registro', async (c) => {
  const body = await c.req.parseBody();
  const nombre = str(body.nombre);
  const email = lowerEmail(body.email);
  const password = str(body.password);
  const password2 = str(body.password2);

  if (nombre.length < 2) {
    return registerForm(c, { error: 'Ingresá tu nombre (mínimo 2 letras).' });
  }
  if (!EMAIL_RE.test(email)) {
    return registerForm(c, { error: 'Ingresá un email válido.' });
  }
  if (password.length < 8) {
    return registerForm(c, {
      error: 'La contraseña debe tener al menos 8 caracteres.',
    });
  }
  if (password !== password2) {
    return registerForm(c, { error: 'Las contraseñas no coinciden.' });
  }

  const db = dbOf(c);
  const iters = iterationsFromEnv(c.env);
  const passwordHash = await hashPassword(password, iters);
  let userId: number;
  try {
    const inserted = await db
      .insert(users)
      .values({
        name: nombre,
        email,
        passwordHash,
        role: 'customer',
        emailVerifiedAt: new Date(),
      })
      .returning({ id: users.id });
    userId = inserted[0]!.id;
  } catch {
    return registerForm(c, {
      error: 'Ya existe una cuenta con ese email.',
    });
  }

  const token = await createSession(db, userId, {
    ip: c.req.header('cf-connecting-ip') ?? null,
    userAgent: c.req.header('user-agent') ?? null,
  });
  setSessionCookie(c, token);
  await sendEmail({
    to: email,
    subject: 'Bienvenido a Gas Station',
    text: `Hola ${nombre}, tu cuenta fue creada correctamente.`,
  });
  return c.redirect('/app');
});

authRoutes.post('/logout', async (c) => {
  await destroySession(dbOf(c), c);
  return c.redirect('/');
});

authRoutes.get('/olvide-contrasena', (c) => {
  return c.html(
    page(
      { title: 'Recuperar contraseña', brand: brandOf(c) },
      `<div class="card">
        <h1>Recuperar contraseña</h1>
        <p class="muted">Ingresá tu email y te enviamos las instrucciones para crear una nueva contraseña.</p>
        <form method="post" action="/olvide-contrasena">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email">
          <button type="submit">Enviar instrucciones</button>
        </form>
      </div>`,
    ),
  );
});

authRoutes.post('/olvide-contrasena', async (c) => {
  const body = await c.req.parseBody();
  const email = lowerEmail(body.email);
  const db = dbOf(c);
  if (EMAIL_RE.test(email)) {
    const rows = await db
      .select({ id: users.id, name: users.name })
      .from(users)
      .where(eq(users.email, email))
      .limit(1);
    const user = rows[0];
    if (user) {
      const token = randomHex();
      await db.insert(passwordResetTokens).values({
        email,
        token,
        expiresAt: new Date(Date.now() + 3_600_000),
      });
      const origin = new URL(c.req.url).origin;
      await sendEmail({
        to: email,
        subject: 'Restablecé tu contraseña',
        text: `Hola ${user.name}, creá una nueva contraseña en: ${origin}/restablecer?token=${token}`,
      });
    }
  }
  return c.html(
    page(
      {
        title: 'Recuperar contraseña',
        brand: brandOf(c),
        notice:
          'Si existe una cuenta con ese email, te enviamos las instrucciones para recuperarla.',
      },
      `<div class="card">
        <h1>Revisá tu email</h1>
        <p class="muted">Si el email corresponde a una cuenta, vas a recibir un enlace para restablecer tu contraseña.</p>
        <a class="btn secondary" href="/login">Volver a acceder</a>
      </div>`,
    ),
  );
});

authRoutes.get('/restablecer', async (c) => {
  const token = str(c.req.query('token'));
  const db = dbOf(c);
  const rows = token
    ? await db
        .select()
        .from(passwordResetTokens)
        .where(eq(passwordResetTokens.token, token))
        .limit(1)
    : [];
  const row = rows[0];
  const valid =
    !!row && !row.usedAt && row.expiresAt.getTime() > Date.now();
  if (!valid) {
    return c.html(
      page(
        {
          title: 'Enlace inválido',
          brand: brandOf(c),
          error: 'El enlace para restablecer la contraseña no es válido o expiró.',
        },
        `<div class="card"><h1>Enlace inválido</h1>
         <p class="muted">Pedí uno nuevo desde <a href="/olvide-contrasena">acá</a>.</p></div>`,
      ),
      400,
    );
  }
  return c.html(
    page(
      { title: 'Nueva contraseña', brand: brandOf(c) },
      `<div class="card">
        <h1>Creá una contraseña nueva</h1>
        <form method="post" action="/restablecer">
          <input type="hidden" name="token" value="${escapeHtml(token)}">
          <label for="password">Contraseña (mínimo 8 caracteres)</label>
          <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
          <label for="password2">Repetí la contraseña</label>
          <input id="password2" name="password2" type="password" required minlength="8" autocomplete="new-password">
          <button type="submit">Guardar contraseña</button>
        </form>
      </div>`,
    ),
  );
});

authRoutes.post('/restablecer', async (c) => {
  const body = await c.req.parseBody();
  const token = str(body.token);
  const password = str(body.password);
  const password2 = str(body.password2);

  const fail = (error: string) =>
    c.html(
      page(
        { title: 'Nueva contraseña', brand: brandOf(c), error },
        `<div class="card"><h1>No se pudo actualizar</h1>
         <p class="muted">${escapeHtml(error)}</p>
         <a class="btn secondary" href="/olvide-contrasena">Pedir un enlace nuevo</a></div>`,
      ),
      422,
    );

  if (password.length < 8) {
    return fail('La contraseña debe tener al menos 8 caracteres.');
  }
  if (password !== password2) {
    return fail('Las contraseñas no coinciden.');
  }

  const db = dbOf(c);
  const rows = token
    ? await db
        .select()
        .from(passwordResetTokens)
        .where(eq(passwordResetTokens.token, token))
        .limit(1)
    : [];
  const row = rows[0];
  if (!row || row.usedAt || row.expiresAt.getTime() <= Date.now()) {
    return fail('El enlace no es válido o expiró.');
  }

  const userRows = await db
    .select()
    .from(users)
    .where(eq(users.email, row.email))
    .limit(1);
  const user = userRows[0];
  if (!user) {
    return fail('No se encontró la cuenta asociada al enlace.');
  }

  const passwordHash = await hashPassword(
    password,
    iterationsFromEnv(c.env),
  );
  await db
    .update(users)
    .set({ passwordHash, updatedAt: new Date() })
    .where(eq(users.id, user.id));
  await db
    .update(passwordResetTokens)
    .set({ usedAt: new Date() })
    .where(eq(passwordResetTokens.id, row.id));
  await destroyAllUserSessions(db, user.id);
  return c.redirect('/login?aviso=ok');
});
