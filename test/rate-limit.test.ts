import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { Hono } from 'hono';
import { rateLimit, ipRateLimit, userRateLimit, loginRateLimit, registerRateLimit, adminRateLimit, apiRateLimit } from '../src/middleware/rate-limit';
import type { AppEnv, Bindings } from '../src/env';

// Mock KV namespace
function createMockKV() {
  const store = new Map<string, { value: unknown; expiresAt: number }>();

  return {
    store,
    get: vi.fn(async (key: string, type?: 'json') => {
      const entry = store.get(key);
      if (!entry) return null;
      if (Date.now() > entry.expiresAt) {
        store.delete(key);
        return null;
      }
      return type === 'json' ? entry.value : JSON.stringify(entry.value);
    }),
    put: vi.fn(async (key: string, value: string, options?: { expirationTtl?: number }) => {
      const ttl = options?.expirationTtl ?? 3600;
      const expiresAt = Date.now() + ttl * 1000;
      try {
        const parsed = JSON.parse(value);
        store.set(key, { value: parsed, expiresAt });
      } catch {
        store.set(key, { value, expiresAt });
      }
    }),
    delete: vi.fn(async (key: string) => {
      store.delete(key);
    }),
    list: vi.fn(async () => ({ keys: [] })),
  } as unknown as KVNamespace;
}

// Mock D1 database
function createMockDB(): D1Database {
  return {
    prepare: vi.fn(() => ({
      bind: vi.fn(() => ({
        first: vi.fn(),
        run: vi.fn(),
        all: vi.fn(),
      })),
      run: vi.fn(),
      first: vi.fn(),
      all: vi.fn(),
    })),
    batch: vi.fn(),
    exec: vi.fn(),
    withSession: vi.fn(),
    dump: vi.fn(),
  } as any;
}

// Mock R2 bucket
function createMockR2() {
  return {
    put: vi.fn(),
    get: vi.fn(),
    head: vi.fn(),
    delete: vi.fn(),
    list: vi.fn(),
  } as any;
}

// Create test bindings
function createTestBindings(kv: any) {
  return {
    SESSIONS: kv,
    DB: createMockDB(),
    R2: createMockR2(),
  };
}

// Create a test app with middleware
function createTestApp(middleware: ReturnType<typeof rateLimit>) {
  const app = new Hono<AppEnv>();
  app.use('*', middleware);
  app.get('/test', (c) => c.json({ ok: true }));
  app.post('/test', (c) => c.json({ ok: true }));
  return app;
}

describe('rate-limit middleware', () => {
  let kv: any;
  let bindings: any;

  beforeEach(() => {
    kv = createMockKV();
    bindings = createTestBindings(kv);
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-01-15T12:00:00Z'));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('rateLimit factory', () => {
    it('allows requests under the limit', async () => {
      const middleware = rateLimit({
        keyGenerator: () => 'test-key',
        limit: 5,
        windowMs: 60_000,
      });
      const app = createTestApp(middleware);

      const res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.status).toBe(200);
      expect(await res.json()).toEqual({ ok: true });
    });

    it('blocks requests over the limit', async () => {
      const middleware = rateLimit({
        keyGenerator: () => 'test-key',
        limit: 2,
        windowMs: 60_000,
      });
      const app = createTestApp(middleware);

      // First request
      let res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.status).toBe(200);

      // Second request
      res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.status).toBe(200);

      // Third request - should be rate limited
      res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.status).toBe(429);
      expect(await res.json()).toEqual({ error: 'Demasiadas solicitudes. Por favor, intente de nuevo más tarde.' });
    });

    it('includes rate limit headers', async () => {
      const middleware = rateLimit({
        keyGenerator: () => 'test-key',
        limit: 3,
        windowMs: 60_000,
      });
      const app = createTestApp(middleware);

      const res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.headers.get('X-RateLimit-Limit')).toBe('3');
      expect(res.headers.get('X-RateLimit-Remaining')).toBe('2');
      expect(res.headers.get('X-RateLimit-Reset')).toBeTruthy();
    });

    it('includes Retry-After header when rate limited', async () => {
      const middleware = rateLimit({
        keyGenerator: () => 'test-key',
        limit: 1,
        windowMs: 60_000,
      });
      const app = createTestApp(middleware);

      // First request
      await app.fetch(new Request('https://example.com/test'), bindings);

      // Second request - rate limited
      const res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.status).toBe(429);
      expect(res.headers.get('Retry-After')).toBeTruthy();
      const retryAfter = parseInt(res.headers.get('Retry-After')!, 10);
      expect(retryAfter).toBeGreaterThan(0);
      expect(retryAfter).toBeLessThanOrEqual(60);
    });

    it('uses custom key generator', async () => {
      const middleware = rateLimit({
        keyGenerator: (c) => `custom:${c.req.header('x-custom-id') ?? 'anon'}`,
        limit: 1,
        windowMs: 60_000,
      });
      const app = createTestApp(middleware);

      // Request with custom ID
      let req = new Request('https://example.com/test', { headers: { 'x-custom-id': 'user1' } });
      let res = await app.fetch(req, bindings);
      expect(res.status).toBe(200);

      // Same custom ID - should be rate limited
      req = new Request('https://example.com/test', { headers: { 'x-custom-id': 'user1' } });
      res = await app.fetch(req, bindings);
      expect(res.status).toBe(429);

      // Different custom ID - should work
      req = new Request('https://example.com/test', { headers: { 'x-custom-id': 'user2' } });
      res = await app.fetch(req, bindings);
      expect(res.status).toBe(200);
    });

    it('uses custom message and status code', async () => {
      const middleware = rateLimit({
        keyGenerator: () => 'test-key',
        limit: 1,
        windowMs: 60_000,
        message: 'Custom rate limit message',
        statusCode: 420,
      });
      const app = createTestApp(middleware);

      await app.fetch(new Request('https://example.com/test'), bindings);
      const res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.status).toBe(420);
      expect(await res.json()).toEqual({ error: 'Custom rate limit message' });
    });

    it('disables standard headers when configured', async () => {
      const middleware = rateLimit({
        keyGenerator: () => 'test-key',
        limit: 1,
        windowMs: 60_000,
        standardHeaders: false,
      });
      const app = createTestApp(middleware);

      await app.fetch(new Request('https://example.com/test'), bindings);
      const res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.headers.get('X-RateLimit-Limit')).toBeNull();
      expect(res.headers.get('X-RateLimit-Remaining')).toBeNull();
      expect(res.headers.get('X-RateLimit-Reset')).toBeNull();
    });

    it('resets counter after window expires', async () => {
      const middleware = rateLimit({
        keyGenerator: () => 'test-key',
        limit: 1,
        windowMs: 60_000,
      });
      const app = createTestApp(middleware);

      // First request
      let res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.status).toBe(200);

      // Second request - rate limited
      res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.status).toBe(429);

      // Advance time past the window
      vi.advanceTimersByTime(61_000);

      // Should work again
      res = await app.fetch(new Request('https://example.com/test'), bindings);
      expect(res.status).toBe(200);
    });
  });

  describe('ipRateLimit', () => {
    it('limits by IP address from cf-connecting-ip header', async () => {
      const middleware = ipRateLimit(2, 60_000);
      const app = createTestApp(middleware);

      // Request from IP 1
      let req = new Request('https://example.com/test', { headers: { 'cf-connecting-ip': '192.168.1.1' } });
      let res = await app.fetch(req, bindings);
      expect(res.status).toBe(200);

      // Second request from same IP
      req = new Request('https://example.com/test', { headers: { 'cf-connecting-ip': '192.168.1.1' } });
      res = await app.fetch(req, bindings);
      expect(res.status).toBe(200);

      // Third request from same IP - rate limited
      req = new Request('https://example.com/test', { headers: { 'cf-connecting-ip': '192.168.1.1' } });
      res = await app.fetch(req, bindings);
      expect(res.status).toBe(429);

      // Request from different IP - should work
      req = new Request('https://example.com/test', { headers: { 'cf-connecting-ip': '192.168.1.2' } });
      res = await app.fetch(req, bindings);
      expect(res.status).toBe(200);
    });

    it('falls back to x-forwarded-for when cf-connecting-ip not present', async () => {
      const middleware = ipRateLimit(1, 60_000);
      const app = createTestApp(middleware);

      const req = new Request('https://example.com/test', {
        headers: { 'x-forwarded-for': '10.0.0.1, 10.0.0.2' },
      });
      const res = await app.fetch(req, bindings);
      expect(res.status).toBe(200);

      // Same forwarded IP - rate limited
      const req2 = new Request('https://example.com/test', {
        headers: { 'x-forwarded-for': '10.0.0.1, 10.0.0.2' },
      });
      const res2 = await app.fetch(req2, bindings);
      expect(res2.status).toBe(429);
    });

    it('uses "unknown" when no IP headers present', async () => {
      const middleware = ipRateLimit(1, 60_000);
      const app = createTestApp(middleware);

      const req = new Request('https://example.com/test');
      const res = await app.fetch(req, bindings);
      expect(res.status).toBe(200);

      const req2 = new Request('https://example.com/test');
      const res2 = await app.fetch(req2, bindings);
      expect(res2.status).toBe(429);
    });
  });

  describe('userRateLimit', () => {
    it('limits by authenticated user ID', async () => {
      const middleware = userRateLimit(2, 60_000);
      const app = createTestApp(middleware);

      // We need to create an app that also uses attachUser middleware
      const testApp = new Hono<AppEnv>();
      testApp.use('*', async (c, next) => {
        // Mock attachUser behavior
        const userHeader = c.req.header('x-test-user') ?? '';
        if (userHeader) {
          const [id, role] = userHeader.split(':') as [string, string];
          c.set('user', { id: parseInt(id, 10), name: 'Test', email: 'test@test.com', role: role as any, points: 0 });
        } else {
          c.set('user', null);
        }
        await next();
      });
      testApp.use('*', middleware);
      testApp.get('/test', (c) => c.json({ ok: true }));

      // Request as user 1
      let req = new Request('https://example.com/test', { headers: { 'x-test-user': '1:customer' } });
      let res = await testApp.fetch(req, bindings);
      expect(res.status).toBe(200);

      // Second request as user 1
      req = new Request('https://example.com/test', { headers: { 'x-test-user': '1:customer' } });
      res = await testApp.fetch(req, bindings);
      expect(res.status).toBe(200);

      // Third request as user 1 - rate limited
      req = new Request('https://example.com/test', { headers: { 'x-test-user': '1:customer' } });
      res = await testApp.fetch(req, bindings);
      expect(res.status).toBe(429);

      // Request as user 2 - should work
      req = new Request('https://example.com/test', { headers: { 'x-test-user': '2:customer' } });
      res = await testApp.fetch(req, bindings);
      expect(res.status).toBe(200);
    });

    it('falls back to IP when user not authenticated', async () => {
      const middleware = userRateLimit(1, 60_000);
      const app = createTestApp(middleware);

      // Request without user (no x-test-user header)
      let req = new Request('https://example.com/test', { headers: { 'cf-connecting-ip': '192.168.1.100' } });
      let res = await app.fetch(req, bindings);
      expect(res.status).toBe(200);

      // Second request from same IP - rate limited
      req = new Request('https://example.com/test', { headers: { 'cf-connecting-ip': '192.168.1.100' } });
      res = await app.fetch(req, bindings);
      expect(res.status).toBe(429);
    });
  });

  describe('pre-configured limiters', () => {
    it('loginRateLimit allows 5 requests per minute per IP', async () => {
      const app = createTestApp(loginRateLimit);

      for (let i = 0; i < 5; i++) {
        const req = new Request('https://example.com/test', { headers: { 'cf-connecting-ip': '1.2.3.4' } });
        const res = await app.fetch(req, bindings);
        expect(res.status).toBe(200);
      }

      // 6th request should be rate limited
      const req = new Request('https://example.com/test', { headers: { 'cf-connecting-ip': '1.2.3.4' } });
      const res = await app.fetch(req, bindings);
      expect(res.status).toBe(429);
    });

    it('registerRateLimit allows 3 requests per minute per IP', async () => {
      const app = createTestApp(registerRateLimit);

      for (let i = 0; i < 3; i++) {
        const req = new Request('https://example.com/test', { headers: { 'cf-connecting-ip': '1.2.3.5' } });
        const res = await app.fetch(req, bindings);
        expect(res.status).toBe(200);
      }

      // 4th request should be rate limited
      const req = new Request('https://example.com/test', { headers: { 'cf-connecting-ip': '1.2.3.5' } });
      const res = await app.fetch(req, bindings);
      expect(res.status).toBe(429);
    });

    it('adminRateLimit allows 100 requests per minute per user', async () => {
      const testApp = new Hono<AppEnv>();
      testApp.use('*', async (c, next) => {
        const userHeader = c.req.header('x-test-user') ?? '';
        if (userHeader) {
          const [id, role] = userHeader.split(':') as [string, string];
          c.set('user', { id: parseInt(id, 10), name: 'Test', email: 'test@test.com', role: role as any, points: 0 });
        } else {
          c.set('user', null);
        }
        await next();
      });
      testApp.use('*', adminRateLimit);
      testApp.get('/test', (c) => c.json({ ok: true }));

      for (let i = 0; i < 100; i++) {
        const req = new Request('https://example.com/test', { headers: { 'x-test-user': '42:admin' } });
        const res = await testApp.fetch(req, bindings);
        expect(res.status).toBe(200);
      }

      // 101st request should be rate limited
      const req = new Request('https://example.com/test', { headers: { 'x-test-user': '42:admin' } });
      const res = await testApp.fetch(req, bindings);
      expect(res.status).toBe(429);
    });

    it('apiRateLimit allows 60 requests per minute per user', async () => {
      const testApp = new Hono<AppEnv>();
      testApp.use('*', async (c, next) => {
        const userHeader = c.req.header('x-test-user') ?? '';
        if (userHeader) {
          const [id, role] = userHeader.split(':') as [string, string];
          c.set('user', { id: parseInt(id, 10), name: 'Test', email: 'test@test.com', role: role as any, points: 0 });
        } else {
          c.set('user', null);
        }
        await next();
      });
      testApp.use('*', apiRateLimit);
      testApp.get('/test', (c) => c.json({ ok: true }));

      for (let i = 0; i < 60; i++) {
        const req = new Request('https://example.com/test', { headers: { 'x-test-user': '99:customer' } });
        const res = await testApp.fetch(req, bindings);
        expect(res.status).toBe(200);
      }

      // 61st request should be rate limited
      const req = new Request('https://example.com/test', { headers: { 'x-test-user': '99:customer' } });
      const res = await testApp.fetch(req, bindings);
      expect(res.status).toBe(429);
    });
  });
});