const PBKDF2_ALGORITHM = 'PBKDF2';
const HASH_NAME = 'SHA-256';
const SALT_BYTES = 16;
const KEY_BITS = 32;

export const DEFAULT_PBKDF2_ITERATIONS = 30_000;
export const MAX_PBKDF2_ITERATIONS = 100_000;

// AES-GCM constants for MFA secret encryption
const AES_ALGORITHM = 'AES-GCM';
const AES_KEY_BITS = 256;
const AES_IV_BYTES = 12;
const AES_TAG_BITS = 128;

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

/**
 * Derives an AES-GCM key from a secret (e.g., JWT_SECRET or MFA_SECRET).
 * Uses PBKDF2 with a fixed salt for deterministic key derivation.
 */
async function deriveAesKey(secret: string): Promise<CryptoKey> {
  // Use a fixed salt for deterministic key derivation from the secret
  const salt = new TextEncoder().encode('gas-station-mfa-key-v1');
  const enc = new TextEncoder();
  const keyMaterial = await crypto.subtle.importKey(
    'raw',
    enc.encode(secret),
    PBKDF2_ALGORITHM,
    false,
    ['deriveBits'],
  );
  const bits = await crypto.subtle.deriveBits(
    {
      name: PBKDF2_ALGORITHM,
      hash: HASH_NAME,
      salt,
      iterations: 100_000,
    },
    keyMaterial,
    AES_KEY_BITS,
  );
  return crypto.subtle.importKey(
    'raw',
    bits,
    { name: AES_ALGORITHM },
    false,
    ['encrypt', 'decrypt'],
  );
}

/**
 * Encrypts a plaintext string using AES-GCM.
 * Returns a base64-encoded string containing: IV + ciphertext + authTag
 * Format: base64(iv || ciphertext || tag)
 */
export async function encryptSecret(
  plaintext: string,
  masterSecret: string,
): Promise<string> {
  const key = await deriveAesKey(masterSecret);
  const iv = crypto.getRandomValues(new Uint8Array(AES_IV_BYTES));
  const enc = new TextEncoder();
  const ciphertext = await crypto.subtle.encrypt(
    {
      name: AES_ALGORITHM,
      iv,
      tagLength: AES_TAG_BITS,
    },
    key,
    enc.encode(plaintext),
  );
  // Combine IV + ciphertext (which includes auth tag at the end for AES-GCM)
  const combined = new Uint8Array(iv.length + ciphertext.byteLength);
  combined.set(iv, 0);
  combined.set(new Uint8Array(ciphertext), iv.length);
  return b64encode(combined.buffer);
}

/**
 * Decrypts a secret encrypted with encryptSecret.
 * Returns the plaintext string.
 */
export async function decryptSecret(
  encrypted: string,
  masterSecret: string,
): Promise<string> {
  const key = await deriveAesKey(masterSecret);
  const combined = b64decode(encrypted);
  if (combined.length < AES_IV_BYTES + 1) {
    throw new Error('Invalid encrypted data');
  }
  const iv = combined.slice(0, AES_IV_BYTES);
  const ciphertext = combined.slice(AES_IV_BYTES);
  const decrypted = await crypto.subtle.decrypt(
    {
      name: AES_ALGORITHM,
      iv,
      tagLength: AES_TAG_BITS,
    },
    key,
    ciphertext,
  );
  return new TextDecoder().decode(decrypted);
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
