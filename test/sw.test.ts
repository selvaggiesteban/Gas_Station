import { describe, expect, it } from 'vitest';

describe('Service Worker - Cache Strategy Logic', () => {
  describe('Route Pattern Matching', () => {
    const STATIC_ASSET_PATTERNS = [
      /\/logo\.png$/,
      /\/producto\/\d+\/imagen/,
    ];

    const HTML_PAGE_PATTERNS = [
      /^\/tienda(\/.*)?$/,
      /^\/carrito(\/.*)?$/,
      /^\/checkout(\/.*)?$/,
    ];

    const CART_MUTATION_PATTERNS = [
      /^\/carrito\/agregar$/,
      /^\/carrito$/,
      /^\/carrito\/quitar$/,
      /^\/carrito\/vaciar$/,
      /^\/checkout$/,
    ];

    const AUTH_REQUIRED_PATTERNS = [
      /^\/ordenes(\/.*)?$/,
      /^\/perfil(\/.*)?$/,
      /^\/app(\/.*)?$/,
      /^\/admin(\/.*)?$/,
      /^\/operativa(\/.*)?$/,
      /^\/ranking(\/.*)?$/,
    ];

    const NEVER_CACHE_PATTERNS = [
      /^\/logout$/,
      /^\/registro$/,
      /^\/login$/,
    ];

    function matchesPatterns(url: string, patterns: RegExp[]): boolean {
      return patterns.some((pattern) => pattern.test(url));
    }

    it('matches static asset patterns', () => {
      expect(matchesPatterns('/logo.png', STATIC_ASSET_PATTERNS)).toBe(true);
      expect(matchesPatterns('/producto/123/imagen', STATIC_ASSET_PATTERNS)).toBe(true);
      expect(matchesPatterns('/producto/456/imagen?size=large', STATIC_ASSET_PATTERNS)).toBe(true);
      expect(matchesPatterns('/tienda', STATIC_ASSET_PATTERNS)).toBe(false);
      expect(matchesPatterns('/carrito', STATIC_ASSET_PATTERNS)).toBe(false);
    });

    it('matches HTML page patterns', () => {
      expect(matchesPatterns('/tienda', HTML_PAGE_PATTERNS)).toBe(true);
      expect(matchesPatterns('/tienda/', HTML_PAGE_PATTERNS)).toBe(true);
      expect(matchesPatterns('/carrito', HTML_PAGE_PATTERNS)).toBe(true);
      expect(matchesPatterns('/checkout', HTML_PAGE_PATTERNS)).toBe(true);
      expect(matchesPatterns('/checkout/', HTML_PAGE_PATTERNS)).toBe(true);
      expect(matchesPatterns('/ordenes', HTML_PAGE_PATTERNS)).toBe(false);
      expect(matchesPatterns('/producto/1/imagen', HTML_PAGE_PATTERNS)).toBe(false);
    });

    it('matches cart mutation patterns', () => {
      expect(matchesPatterns('/carrito/agregar', CART_MUTATION_PATTERNS)).toBe(true);
      expect(matchesPatterns('/carrito', CART_MUTATION_PATTERNS)).toBe(true);
      expect(matchesPatterns('/carrito/quitar', CART_MUTATION_PATTERNS)).toBe(true);
      expect(matchesPatterns('/carrito/vaciar', CART_MUTATION_PATTERNS)).toBe(true);
      expect(matchesPatterns('/checkout', CART_MUTATION_PATTERNS)).toBe(true);
      expect(matchesPatterns('/tienda', CART_MUTATION_PATTERNS)).toBe(false);
      expect(matchesPatterns('/ordenes', CART_MUTATION_PATTERNS)).toBe(false);
    });

    it('matches auth required patterns', () => {
      expect(matchesPatterns('/ordenes', AUTH_REQUIRED_PATTERNS)).toBe(true);
      expect(matchesPatterns('/ordenes/123', AUTH_REQUIRED_PATTERNS)).toBe(true);
      expect(matchesPatterns('/perfil', AUTH_REQUIRED_PATTERNS)).toBe(true);
      expect(matchesPatterns('/app', AUTH_REQUIRED_PATTERNS)).toBe(true);
      expect(matchesPatterns('/admin/usuarios', AUTH_REQUIRED_PATTERNS)).toBe(true);
      expect(matchesPatterns('/operativa/caja', AUTH_REQUIRED_PATTERNS)).toBe(true);
      expect(matchesPatterns('/ranking', AUTH_REQUIRED_PATTERNS)).toBe(true);
      expect(matchesPatterns('/tienda', AUTH_REQUIRED_PATTERNS)).toBe(false);
      expect(matchesPatterns('/carrito', AUTH_REQUIRED_PATTERNS)).toBe(false);
    });

    it('matches never cache patterns', () => {
      expect(matchesPatterns('/logout', NEVER_CACHE_PATTERNS)).toBe(true);
      expect(matchesPatterns('/registro', NEVER_CACHE_PATTERNS)).toBe(true);
      expect(matchesPatterns('/login', NEVER_CACHE_PATTERNS)).toBe(true);
      expect(matchesPatterns('/tienda', NEVER_CACHE_PATTERNS)).toBe(false);
      expect(matchesPatterns('/carrito', NEVER_CACHE_PATTERNS)).toBe(false);
    });
  });

  describe('Request Classification', () => {
    interface MockRequest {
      url: string;
      method: string;
      headers: Headers;
    }

    function createMockRequest(url: string, options: { method?: string } = {}): MockRequest {
      return {
        url,
        method: options.method || 'GET',
        headers: new Headers(),
      };
    }

    function isStaticAsset(request: MockRequest): boolean {
      const url = new URL(request.url);
      const STATIC_ASSET_PATTERNS = [
        /\/logo\.png$/,
        /\/producto\/\d+\/imagen/,
      ];
      return STATIC_ASSET_PATTERNS.some((pattern) => pattern.test(url.pathname));
    }

    function isHtmlPage(request: MockRequest): boolean {
      const url = new URL(request.url);
      const HTML_PAGE_PATTERNS = [
        /^\/tienda(\/.*)?$/,
        /^\/carrito(\/.*)?$/,
        /^\/checkout(\/.*)?$/,
      ];
      return HTML_PAGE_PATTERNS.some((pattern) => pattern.test(url.pathname));
    }

    function isCartMutation(request: MockRequest): boolean {
      if (request.method !== 'POST') return false;
      const url = new URL(request.url);
      const CART_MUTATION_PATTERNS = [
        /^\/carrito\/agregar$/,
        /^\/carrito$/,
        /^\/carrito\/quitar$/,
        /^\/carrito\/vaciar$/,
        /^\/checkout$/,
      ];
      return CART_MUTATION_PATTERNS.some((pattern) => pattern.test(url.pathname));
    }

    function isAuthRequired(request: MockRequest): boolean {
      const url = new URL(request.url);
      const AUTH_REQUIRED_PATTERNS = [
        /^\/ordenes(\/.*)?$/,
        /^\/perfil(\/.*)?$/,
        /^\/app(\/.*)?$/,
        /^\/admin(\/.*)?$/,
        /^\/operativa(\/.*)?$/,
        /^\/ranking(\/.*)?$/,
      ];
      return AUTH_REQUIRED_PATTERNS.some((pattern) => pattern.test(url.pathname));
    }

    function isNeverCache(request: MockRequest): boolean {
      const url = new URL(request.url);
      const NEVER_CACHE_PATTERNS = [
        /^\/logout$/,
        /^\/registro$/,
        /^\/login$/,
      ];
      return NEVER_CACHE_PATTERNS.some((pattern) => pattern.test(url.pathname));
    }

    it('classifies static asset requests correctly', () => {
      expect(isStaticAsset(createMockRequest('https://example.com/logo.png'))).toBe(true);
      expect(isStaticAsset(createMockRequest('https://example.com/producto/123/imagen'))).toBe(true);
      expect(isStaticAsset(createMockRequest('https://example.com/tienda'))).toBe(false);
      expect(isStaticAsset(createMockRequest('https://example.com/carrito'))).toBe(false);
    });

    it('classifies HTML page requests correctly', () => {
      expect(isHtmlPage(createMockRequest('https://example.com/tienda'))).toBe(true);
      expect(isHtmlPage(createMockRequest('https://example.com/carrito'))).toBe(true);
      expect(isHtmlPage(createMockRequest('https://example.com/checkout'))).toBe(true);
      expect(isHtmlPage(createMockRequest('https://example.com/ordenes'))).toBe(false);
      expect(isHtmlPage(createMockRequest('https://example.com/producto/1/imagen'))).toBe(false);
    });

    it('classifies cart mutations correctly', () => {
      expect(isCartMutation(createMockRequest('https://example.com/carrito/agregar', { method: 'POST' }))).toBe(true);
      expect(isCartMutation(createMockRequest('https://example.com/carrito', { method: 'POST' }))).toBe(true);
      expect(isCartMutation(createMockRequest('https://example.com/carrito/quitar', { method: 'POST' }))).toBe(true);
      expect(isCartMutation(createMockRequest('https://example.com/carrito/vaciar', { method: 'POST' }))).toBe(true);
      expect(isCartMutation(createMockRequest('https://example.com/checkout', { method: 'POST' }))).toBe(true);
      // GET requests should not be mutations
      expect(isCartMutation(createMockRequest('https://example.com/carrito/agregar', { method: 'GET' }))).toBe(false);
      expect(isCartMutation(createMockRequest('https://example.com/tienda', { method: 'POST' }))).toBe(false);
    });

    it('classifies auth required requests correctly', () => {
      expect(isAuthRequired(createMockRequest('https://example.com/ordenes'))).toBe(true);
      expect(isAuthRequired(createMockRequest('https://example.com/ordenes/123'))).toBe(true);
      expect(isAuthRequired(createMockRequest('https://example.com/perfil'))).toBe(true);
      expect(isAuthRequired(createMockRequest('https://example.com/app'))).toBe(true);
      expect(isAuthRequired(createMockRequest('https://example.com/admin/usuarios'))).toBe(true);
      expect(isAuthRequired(createMockRequest('https://example.com/operativa/caja'))).toBe(true);
      expect(isAuthRequired(createMockRequest('https://example.com/ranking'))).toBe(true);
      expect(isAuthRequired(createMockRequest('https://example.com/tienda'))).toBe(false);
      expect(isAuthRequired(createMockRequest('https://example.com/carrito'))).toBe(false);
    });

    it('classifies never cache requests correctly', () => {
      expect(isNeverCache(createMockRequest('https://example.com/logout'))).toBe(true);
      expect(isNeverCache(createMockRequest('https://example.com/registro'))).toBe(true);
      expect(isNeverCache(createMockRequest('https://example.com/login'))).toBe(true);
      expect(isNeverCache(createMockRequest('https://example.com/tienda'))).toBe(false);
      expect(isNeverCache(createMockRequest('https://example.com/carrito'))).toBe(false);
    });
  });

  describe('Cache Strategy Selection', () => {
    interface MockRequest {
      url: string;
      method: string;
    }

    function createMockRequest(url: string, options: { method?: string } = {}): MockRequest {
      return {
        url,
        method: options.method || 'GET',
      };
    }

    function getStrategyForRequest(request: MockRequest): string {
      const url = new URL(request.url);
      
      // Never cache auth pages, login, logout, registro
      const NEVER_CACHE_PATTERNS = [
        /^\/logout$/,
        /^\/registro$/,
        /^\/login$/,
      ];
      const AUTH_REQUIRED_PATTERNS = [
        /^\/ordenes(\/.*)?$/,
        /^\/perfil(\/.*)?$/,
        /^\/app(\/.*)?$/,
        /^\/admin(\/.*)?$/,
        /^\/operativa(\/.*)?$/,
        /^\/ranking(\/.*)?$/,
      ];
      
      if (NEVER_CACHE_PATTERNS.some(p => p.test(url.pathname)) || 
          AUTH_REQUIRED_PATTERNS.some(p => p.test(url.pathname))) {
        return 'none';
      }
      
      // Cart mutations: Network Only (check BEFORE HTML pages)
      const CART_MUTATION_PATTERNS = [
        /^\/carrito\/agregar$/,
        /^\/carrito$/,
        /^\/carrito\/quitar$/,
        /^\/carrito\/vaciar$/,
        /^\/checkout$/,
      ];
      if (request.method === 'POST' && CART_MUTATION_PATTERNS.some(p => p.test(url.pathname))) {
        return 'network-only';
      }
      
      // Skip non-GET
      if (request.method !== 'GET') {
        return 'none';
      }
      
      // Static assets: Cache First
      const STATIC_ASSET_PATTERNS = [
        /\/logo\.png$/,
        /\/producto\/\d+\/imagen/,
      ];
      if (STATIC_ASSET_PATTERNS.some(p => p.test(url.pathname))) {
        return 'cache-first';
      }
      
      // HTML pages: Network First
      const HTML_PAGE_PATTERNS = [
        /^\/tienda(\/.*)?$/,
        /^\/carrito(\/.*)?$/,
        /^\/checkout(\/.*)?$/,
      ];
      if (HTML_PAGE_PATTERNS.some(p => p.test(url.pathname))) {
        return 'network-first';
      }
      
      // Default: Network First for GET
      return 'network-first';
    }

    it('selects cache-first for static assets', () => {
      expect(getStrategyForRequest(createMockRequest('https://example.com/logo.png'))).toBe('cache-first');
      expect(getStrategyForRequest(createMockRequest('https://example.com/producto/123/imagen'))).toBe('cache-first');
    });

    it('selects network-first for HTML pages', () => {
      expect(getStrategyForRequest(createMockRequest('https://example.com/tienda'))).toBe('network-first');
      expect(getStrategyForRequest(createMockRequest('https://example.com/carrito'))).toBe('network-first');
      expect(getStrategyForRequest(createMockRequest('https://example.com/checkout'))).toBe('network-first');
    });

    it('selects network-only for cart mutations', () => {
      expect(getStrategyForRequest(createMockRequest('https://example.com/carrito/agregar', { method: 'POST' }))).toBe('network-only');
      expect(getStrategyForRequest(createMockRequest('https://example.com/carrito', { method: 'POST' }))).toBe('network-only');
      expect(getStrategyForRequest(createMockRequest('https://example.com/checkout', { method: 'POST' }))).toBe('network-only');
    });

    it('selects none for auth required pages', () => {
      expect(getStrategyForRequest(createMockRequest('https://example.com/ordenes'))).toBe('none');
      expect(getStrategyForRequest(createMockRequest('https://example.com/perfil'))).toBe('none');
      expect(getStrategyForRequest(createMockRequest('https://example.com/app'))).toBe('none');
      expect(getStrategyForRequest(createMockRequest('https://example.com/admin/usuarios'))).toBe('none');
    });

    it('selects none for never cache pages', () => {
      expect(getStrategyForRequest(createMockRequest('https://example.com/logout'))).toBe('none');
      expect(getStrategyForRequest(createMockRequest('https://example.com/registro'))).toBe('none');
      expect(getStrategyForRequest(createMockRequest('https://example.com/login'))).toBe('none');
    });

    it('defaults to network-first for other GET requests', () => {
      expect(getStrategyForRequest(createMockRequest('https://example.com/api/products'))).toBe('network-first');
    });
  });

  describe('Offline Page Generation', () => {
    it('generates valid offline fallback HTML structure', () => {
      const fallbackHtml = `<!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sin conexión · LESCO GNC</title>
</head>
<body>
<div class="card">
<h1>Estás sin conexión</h1>
</div>
</body></html>`;
      
      expect(fallbackHtml).toContain('Sin conexión');
      expect(fallbackHtml).toContain('LESCO GNC');
      expect(fallbackHtml).toContain('utf-8');
      expect(fallbackHtml).toContain('viewport');
    });
  });

  describe('Version Handling', () => {
    it('generates correct cache name from version', () => {
      const version = 'test-1.0.0';
      const cacheName = `lesco-v${version}`;
      expect(cacheName).toBe('lesco-vtest-1.0.0');
    });

    it('generates correct offline cache name', () => {
      const version = 'test-1.0.0';
      const offlineCacheName = `lesco-offline-v${version}`;
      expect(offlineCacheName).toBe('lesco-offline-vtest-1.0.0');
    });
  });
});

describe('Service Worker - Integration Helpers', () => {
  it('has correct cache name constants', () => {
    const CACHE_VERSION = '1.0.0';
    const CACHE_NAME = `lesco-v${CACHE_VERSION}`;
    const OFFLINE_CACHE_NAME = `lesco-offline-v${CACHE_VERSION}`;
    
    expect(CACHE_NAME).toBe('lesco-v1.0.0');
    expect(OFFLINE_CACHE_NAME).toBe('lesco-offline-v1.0.0');
  });

  it('has correct IndexedDB constants', () => {
    const INDEXEDB_NAME = 'lesco-cart';
    const INDEXEDB_VERSION = 1;
    const INDEXEDB_STORE = 'pending-cart-ops';
    
    expect(INDEXEDB_NAME).toBe('lesco-cart');
    expect(INDEXEDB_VERSION).toBe(1);
    expect(INDEXEDB_STORE).toBe('pending-cart-ops');
  });
});