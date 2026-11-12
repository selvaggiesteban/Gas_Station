import type { SessionUser } from './lib/session';

export type Bindings = {
  DB: D1Database;
  R2: R2Bucket;
  SESSIONS: KVNamespace;
  LOGS_WORKERS: R2Bucket;
  DB_BACKUPS: R2Bucket;
  BRAND_NAME?: string;
  PBKDF2_ITERATIONS?: string;
  GMAIL_USER?: string;
  GMAIL_APP_PASSWORD?: string;
  GMAIL_SMTP_HOST?: string;
  GMAIL_SMTP_PORT?: string;
  EMAIL_FROM?: string;
  JWT_SECRET: string;
  MFA_SECRET?: string;
  TURNSTILE_SITE_KEY?: string;
  TURNSTILE_SECRET_KEY?: string;
};

export type AppEnv = {
  Bindings: Bindings;
  Variables: {
    user: SessionUser | null;
    mfaVerified: boolean;
  };
};
