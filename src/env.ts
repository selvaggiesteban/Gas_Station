import type { SessionUser } from './lib/session';

export type Bindings = {
  DB: D1Database;
  R2: R2Bucket;
  BRAND_NAME?: string;
  PBKDF2_ITERATIONS?: string;
};

export type AppEnv = {
  Bindings: Bindings;
  Variables: {
    user: SessionUser | null;
  };
};
