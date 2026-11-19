import { Hono } from 'hono';
import type { Context } from 'hono';
import type { AppEnv } from '../env';
import { dbOf, brandOf, requireRole } from '../middleware/auth';
import { users } from '../db/schema';
import { hashPassword, iterationsFromEnv } from '../lib/crypto';
import { page, escapeHtml } from '../lib/html';
import { sendEmail } from '../lib/email';
import { enrollInRaffle } from '../services/raffle';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export const clientesRoutes = new Hono<AppEnv>();

function str(v: unknown): string {
  return typeof v === 'string' ? v.trim() : '';
}

function lowerEmail(v: unknown): string {
  return str(v).toLowerCase();
}

/**
 * Contraseña de 12 caracteres generada en el sistema, sin caracteres
 * ambiguos (sin I, l, O, 0, etc.) para que el playero pueda leerla
 * en voz alta al cliente sin equivocarse.
 */
function generatePassword(): string {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
  const bytes = crypto.getRandomValues(new Uint8Array(12));
  return Array.from(bytes, (b) => chars[b % chars.length]).join('');
}

function newClientForm(
  c: Context<AppEnv>,
  opts: { error?: string } = {},
): Response {
  return c.html(
    page(
      {
        title: 'Nuevo cliente',
        brand: brandOf(c),
        user: c.get('user'),
        error: opts.error,
      },
      `<div class="card">
        <h1>Nuevo cliente</h1>
        <p class="muted">Dalo de alta desde playa. La contraseña la generamos nosotros y te la mostramos una sola vez.</p>
        <form method="post" action="/admin/clientes/nuevo">
          <label for="nombre">Nombre</label>
          <input id="nombre" name="nombre" type="text" required minlength="2" autocomplete="name">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email">
          <button type="submit">Crear cliente</button>
        </form>
        <p class="muted"><a href="/admin/ordenes">Volver a órdenes</a></p>
      </div>`,
    ),
    opts.error ? 422 : 200,
  );
}

function successPage(
  c: Context<AppEnv>,
  opts: { name: string; email: string; password: string },
): Response {
  return c.html(
    page(
      {
        title: 'Cliente creado',
        brand: brandOf(c),
        user: c.get('user'),
        notice: `Se creó la cuenta de ${opts.name} (${opts.email}).`,
      },
      `<div class="card">
        <h1>Cliente creado</h1>
        <p>Contraseña: <code>${escapeHtml(opts.password)}</code></p>
        <p class="muted">Pasásela al cliente. Esta contraseña no se vuelve a mostrar.</p>
        <div class="row">
          <a class="btn" href="/admin/clientes/nuevo">Crear otro cliente</a>
          <a class="btn secondary" href="/admin/ordenes">Volver</a>
        </div>
      </div>`,
    ),
  );
}

// Defensa en profundidad: el gate global de /admin ya deja pasar a admin y
// store_manager en esta ruta, pero el rol queda explícito acá también.
clientesRoutes.use('/admin/clientes/nuevo', requireRole('admin', 'store_manager'));

clientesRoutes.get('/admin/clientes/nuevo', (c) => {
  return newClientForm(c);
});

clientesRoutes.post('/admin/clientes/nuevo', async (c) => {
  const body = await c.req.parseBody();
  const nombre = str(body.nombre);
  const email = lowerEmail(body.email);

  if (nombre.length < 2) {
    return newClientForm(c, { error: 'Ingresá el nombre del cliente (mínimo 2 letras).' });
  }
  if (!EMAIL_RE.test(email)) {
    return newClientForm(c, { error: 'Ingresá un email válido.' });
  }

  const password = generatePassword();
  const db = dbOf(c);
  const passwordHash = await hashPassword(password, iterationsFromEnv(c.env));
  try {
    await db.insert(users).values({
      name: nombre,
      email,
      passwordHash,
      role: 'customer',
      emailVerifiedAt: new Date(),
    });
  } catch {
    return newClientForm(c, { error: 'Ya existe una cuenta con ese email.' });
  }

  // Registro = participación automática en el sorteo.
  const raffleCode = await enrollInRaffle(db, { name: nombre, email });
  await sendEmail({
    to: email,
    subject: 'Bienvenido a Gas Station',
    text: `Hola ${nombre}, tu cuenta fue creada correctamente.${
      raffleCode ? ` Tu cupón del sorteo es ${raffleCode}.` : ''
    }`,
  });

  // Ojo: acá NO creamos sesión ni seteamos cookie. El playero sigue
  // logueado con su propia sesión y en su lugar vemos una página de éxito.
  return successPage(c, { name: nombre, email, password });
});
