const PBKDF2_ALGORITHM = 'PBKDF2';
const HASH_NAME = 'SHA-256';
const SALT_BYTES = 16;
const KEY_BITS = 32;

export const DEFAULT_PBKDF2_ITERATIONS = 30_000;
export const MAX_PBKDF2_ITERATIONS = 100_000;

export function iterationsFromEnv(env: { PBKDF2_ITERATIONS?: string }): number {
  const n = Number(env.PBKDF2_ITERATIONS);
  if (Number.isInteger(n) && n >= 1_000 && n <= MAX_PBKDF2_ITERATIONS) {
    return n;
  }
  return DEFAULT_PBKDF2_ITERATIONS;
}

function b64encode(buf: ArrayBuffer): string {
  const bytes = new Uint8Array(buf);
  let binary = '';
  for (const b of bytes) binary += String.fromCharCode(b);
  return btoa(binary);
}

function b64decode(s: string): Uint8Array {
  const binary = atob(s);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
  return bytes;
}

async function deriveBits(
  password: string,
  salt: Uint8Array,
  iterations: number,
): Promise<ArrayBuffer> {
  const enc = new TextEncoder();
  const keyMaterial = await crypto.subtle.importKey(
    'raw',
    enc.encode(password),
    PBKDF2_ALGORITHM,
    false,
    ['deriveBits'],
  );
  return crypto.subtle.deriveBits(
    {
      name: PBKDF2_ALGORITHM,
      hash: HASH_NAME,
      salt: salt as BufferSource,
      iterations,
    },
    keyMaterial,
    KEY_BITS,
  );
}

export async function hashPassword(
  password: string,
  iterations: number = DEFAULT_PBKDF2_ITERATIONS,
): Promise<string> {
  const salt = crypto.getRandomValues(new Uint8Array(SALT_BYTES));
  const bits = await deriveBits(password, salt, iterations);
  return `pbkdf2$${iterations}$${b64encode(salt.buffer)}$${b64encode(bits)}`;
}

export async function verifyPassword(
  password: string,
  stored: string,
): Promise<boolean> {
  const parts = stored.split('$');
  const [algo, iterStr, saltStr, expected] = parts;
  if (parts.length !== 4 || algo !== 'pbkdf2') return false;
  if (!iterStr || !saltStr || !expected) return false;
  const iterations = Number(iterStr);
  if (!Number.isInteger(iterations) || iterations < 1) return false;
  const salt = b64decode(saltStr);
  const bits = await deriveBits(password, salt, iterations);
  const actual = b64encode(bits);
  if (actual.length !== expected.length) return false;
  let diff = 0;
  for (let i = 0; i < actual.length; i++) {
    diff |= actual.charCodeAt(i) ^ expected.charCodeAt(i);
  }
  return diff === 0;
}

const tick = () => new Promise((r) => setTimeout(r, 0));

export async function benchPbkdf2(
  iterations: number,
  rounds = 5,
): Promise<{ totalMs: number; perRoundMs: number }> {
  const salt = crypto.getRandomValues(new Uint8Array(SALT_BYTES));
  await tick();
  const start = Date.now();
  for (let i = 0; i < rounds; i++) {
    await deriveBits('bench-password-12345', salt, iterations);
  }
  await tick();
  const totalMs = Date.now() - start;
  return {
    totalMs,
    perRoundMs: Math.round((totalMs / rounds) * 100) / 100,
  };
}
