import { eq, sql, type SQL } from 'drizzle-orm';
import type { Db } from '../db';
import { pointLogs, users } from '../db/schema';

/**
 * El login otorga 10 puntos, una vez por día.
 * La puntualidad NO cambia este valor para clientes: iniciar turno tarde no
 * afecta a `customer`. Para `store_manager` (playeros) ver `isLateLogin`:
 * en hora = +10; tarde = no suma el +10 y resta 15 (piso 0). Admin = 0.
 * El log con fecha y hora sirve para control del turno
 * (06-14 / 14-22 / 22-06).
 */
export const LOGIN_POINTS = 10;

/**
 * Puntos que se restan al playero que inicia sesión tarde (fuera de la
 * ventana de ±15 min de un turno). No suma el +10 y se restan hasta 15
 * puntos, con piso en 0 (nunca negativo).
 */
export const LATE_LOGIN_PENALTY = 15;

function fmtDate(date: Date): string {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: 'America/Argentina/Buenos_Aires',
  }).format(date);
}

export function todayInBuenosAires(at: Date = new Date()): string {
  return fmtDate(at);
}

export function yesterdayInBuenosAires(at: Date = new Date()): string {
  return fmtDate(new Date(at.getTime() - 86_400_000));
}

/** Inicios de turno en minutos desde medianoche (hora Argentina, UTC-3). */
const TURN_STARTS = [
  { name: 'mañana', minutes: 6 * 60 },
  { name: 'tarde', minutes: 14 * 60 },
  { name: 'noche', minutes: 22 * 60 },
] as const;

/** Ventana "en hora": ±15 minutos desde el inicio de cada turno. */
const TURN_WINDOW_MIN = 15;

function hhmm(totalMinutes: number): string {
  const h = Math.floor(totalMinutes / 60);
  const m = totalMinutes % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

/**
 * Minutos del día (0–1439) en Argentina para un instante dado.
 * UTC-3 fijo (Argentina no tiene DST).
 */
export function minutesOfDayBuenosAires(date: Date): number {
  const ART_OFFSET_MS = -3 * 60 * 60 * 1000;
  const local = new Date(date.getTime() + ART_OFFSET_MS);
  return local.getUTCHours() * 60 + local.getUTCMinutes();
}

export type PunctualityResult = {
  /** true si el login queda fuera de la ventana de todo turno. */
  late: boolean;
  /** Turno alcanzado o ventanas esperadas, para diagnóstico/tests. */
  expected: string;
};

/**
 * Control de puntualidad (función pura y testeable).
 *
 * Aplica SOLO al rol `store_manager` (playeros): cualquier otro rol nunca
 * se considera tarde (customers siempre +10; admin no suma puntos).
 *
 * "En hora" = dentro de ±15 minutos del inicio de un turno
 * (05:45–06:15, 13:45–14:15, 21:45–22:15). Cualquier otro momento es tarde.
 *
 * @param role rol del usuario.
 * @param localMinutes minutos del día en hora Argentina (0–1439), p. ej.
 * vía `minutesOfDayBuenosAires(date)` (inyección de tiempo).
 */
export function isLateLogin(
  role: string,
  localMinutes: number,
): PunctualityResult {
  if (role !== 'store_manager') {
    return { late: false, expected: 'sin control de puntualidad' };
  }
  const window = (start: number) =>
    `${hhmm(start - TURN_WINDOW_MIN)}-${hhmm(start + TURN_WINDOW_MIN)}`;
  const turno = TURN_STARTS.find(
    (t) => Math.abs(localMinutes - t.minutes) <= TURN_WINDOW_MIN,
  );
  if (turno) {
    return {
      late: false,
      expected: `turno ${turno.name} (${window(turno.minutes)})`,
    };
  }
  return {
    late: true,
    expected: TURN_STARTS.map((t) => window(t.minutes)).join(', '),
  };
}

/**
 * Registra el primer inicio de sesión del día (gate: una vez por día por
 * usuario, según `lastLoginDay`).
 *
 * Reglas de puntualidad — turnos 06:00 / 14:00 / 22:00 (hora Argentina),
 * ventana ±15 min:
 * - `store_manager` (playeros) EN HORA: +10 puntos, fila de log con
 *   `points: 10`.
 * - `store_manager` TARDE: NO se otorga el +10 y se restan hasta 15 puntos
 *   (piso en 0, nunca negativo); el log queda con `points: -deducción`
 *   (ej. -15, o -5 si tenía 5). Así "Últimos movimientos" muestra la resta
 *   junto a la hora exacta del login.
 * - `customer`: siempre +10, sin importar el horario.
 * - `admin`: sin puntos, fila de log con `points: 0` para que su llegada
 *   sea visible en la vista de admin.
 *
 * El cálculo de "tarde" vive en la función pura `isLateLogin`.
 *
 * @param now hora a inyectar (para tests); por defecto, la actual.
 * @returns los puntos registrados en el log (10, -deducción o 0), o 0 si
 * ya había iniciado sesión hoy.
 */
export async function awardLoginPoints(
  db: Db,
  user: {
    id: number;
    points: number;
    lastLoginDay: string | null;
    loginStreak: number;
    role: string;
  },
  now: Date = new Date(),
): Promise<number> {
  const today = todayInBuenosAires(now);
  if (user.lastLoginDay === today) return 0;
  const isAdmin = user.role === 'admin';
  const streak =
    user.lastLoginDay === yesterdayInBuenosAires(now)
      ? user.loginStreak + 1
      : 1;

  const { late } = isLateLogin(user.role, minutesOfDayBuenosAires(now));
  // Playero tarde: no suma el +10 y resta LATE_LOGIN_PENALTY con piso en 0.
  const deduction = !isAdmin && late
    ? Math.min(LATE_LOGIN_PENALTY, Math.max(0, user.points))
    : 0;
  const logPoints = isAdmin
    ? 0
    : late
      ? deduction > 0
        ? -deduction
        : 0 // playero tarde sin puntos: nada que restar (evita -0)
      : LOGIN_POINTS;

  const pointsSet: { points?: SQL } = isAdmin
    ? {}
    : late
      ? deduction > 0
        ? { points: sql`max(0, ${users.points} - ${LATE_LOGIN_PENALTY})` }
        : {}
      : { points: sql`${users.points} + ${LOGIN_POINTS}` };

  await db
    .update(users)
    .set({
      lastLoginDay: today,
      loginStreak: streak,
      ...pointsSet,
    })
    .where(eq(users.id, user.id));
  await db.insert(pointLogs).values({
    userId: user.id,
    points: logPoints,
    reason: 'login',
  });
  return logPoints;
}
