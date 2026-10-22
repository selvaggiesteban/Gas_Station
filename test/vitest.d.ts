export {};

declare module 'vitest' {
  interface ProvidedContext {
    d1Migrations: import('@cloudflare/vitest-plugin').D1Migration[];
  }
}
