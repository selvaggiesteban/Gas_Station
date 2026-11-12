/**
 * Service Worker for offline-first tienda (catalog + cart)
 * Version: injected at build time via __APP_VERSION__ or read from registration URL
 * Cache name: lesco-v{version}
 */

/// <reference lib="webworker" />

/**
 * Get version from multiple sources:
 * 1. Build-time injected global __APP_VERSION__
 * 2. Registration URL query parameter ?v=
 * 3. Default 'dev'
 */
function getVersion() {
  // Build-time injected version (via build script or wrangler define)
  if (typeof self.__APP_VERSION__ === 'string' && self.__APP_VERSION__) {
    return self.__APP_VERSION__;
  }
  // Try to get from registration URL
  try {
    const url = new URL(self.registration.scope);
    const v = url.searchParams.get('v');
    if (v) return v;
  } catch {}
  // Try to get from current SW script URL
  try {
    const scripts = self.document?.currentScript;
    if (scripts) {
      const scriptUrl = new URL(scripts.src);
      const v = scriptUrl.searchParams.get('v');
      if (v) return v;
    }
  } catch {}
  // Fallback: check if there's a version in the cache name already
  return 'dev';
}

const CACHE_VERSION = getVersion();
const CACHE_NAME = `lesco-v${CACHE_VERSION}`;
const OFFLINE_CACHE_NAME = `lesco-offline-v${CACHE_VERSION}`;
const INDEXEDB_NAME = 'lesco-cart';
const INDEXEDB_VERSION = 1;
const INDEXEDB_STORE = 'pending-cart-ops';

/**
 * @typedef {Object} CartOperation
 * @property {string} id - Unique operation ID
 * @property {string} method - HTTP method
 * @property {string} url - Request URL
 * @property {Object} headers - Request headers
 * @property {string} body - Request body
 * @property {number} timestamp - Operation timestamp
 */

// ============================
// Route patterns and strategies
// ============================

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

/**
 * Check if URL matches any pattern
 * @param {string} url - URL to check
 * @param {RegExp[]} patterns - Array of regex patterns
 * @returns {boolean}
 */
function matchesPatterns(url, patterns) {
  return patterns.some((pattern) => pattern.test(url));
}

/**
 * Check if request is for a static asset
 * @param {Request} request
 * @returns {boolean}
 */
function isStaticAsset(request) {
  const url = new URL(request.url);
  return matchesPatterns(url.pathname, STATIC_ASSET_PATTERNS);
}

/**
 * Check if request is for an HTML page (tienda/carrito/checkout)
 * @param {Request} request
 * @returns {boolean}
 */
function isHtmlPage(request) {
  const url = new URL(request.url);
  return matchesPatterns(url.pathname, HTML_PAGE_PATTERNS);
}

/**
 * Check if request is a cart mutation (POST to cart endpoints)
 * @param {Request} request
 * @returns {boolean}
 */
function isCartMutation(request) {
  if (request.method !== 'POST') return false;
  const url = new URL(request.url);
  return matchesPatterns(url.pathname, CART_MUTATION_PATTERNS);
}

/**
 * Check if request requires authentication
 * @param {Request} request
 * @returns {boolean}
 */
function isAuthRequired(request) {
  const url = new URL(request.url);
  return matchesPatterns(url.pathname, AUTH_REQUIRED_PATTERNS);
}

/**
 * Check if request should never be cached
 * @param {Request} request
 * @returns {boolean}
 */
function isNeverCache(request) {
  const url = new URL(request.url);
  return matchesPatterns(url.pathname, NEVER_CACHE_PATTERNS);
}

/**
 * Check if request is a navigation (HTML page load)
 * @param {Request} request
 * @returns {boolean}
 */
function isNavigation(request) {
  return request.mode === 'navigate' || 
    (request.headers.get('accept') ?? '').includes('text/html');
}

// ============================
// IndexedDB for offline cart operations
// ============================

/**
 * Open IndexedDB connection
 * @returns {Promise<IDBDatabase>}
 */
function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(INDEXEDB_NAME, INDEXEDB_VERSION);
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains(INDEXEDB_STORE)) {
        db.createObjectStore(INDEXEDB_STORE, { keyPath: 'id' });
      }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

/**
 * Save a pending cart operation to IndexedDB
 * @param {CartOperation} op
 * @returns {Promise<void>}
 */
async function savePendingOperation(op) {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(INDEXEDB_STORE, 'readwrite');
    tx.objectStore(INDEXEDB_STORE).put(op);
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
  });
}

/**
 * Get all pending cart operations
 * @returns {Promise<CartOperation[]>}
 */
async function getPendingOperations() {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(INDEXEDB_STORE, 'readonly');
    const request = tx.objectStore(INDEXEDB_STORE).getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

/**
 * Remove a pending cart operation
 * @param {string} id
 * @returns {Promise<void>}
 */
async function removePendingOperation(id) {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(INDEXEDB_STORE, 'readwrite');
    tx.objectStore(INDEXEDB_STORE).delete(id);
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
  });
}

/**
 * Clear all pending operations
 * @returns {Promise<void>}
 */
async function clearPendingOperations() {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(INDEXEDB_STORE, 'readwrite');
    tx.objectStore(INDEXEDB_STORE).clear();
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
  });
}

// ============================
// Cache strategies
// ============================

/**
 * Cache First strategy - for static assets
 * @param {Request} request
 * @returns {Promise<Response>}
 */
async function cacheFirst(request) {
  const cache = await caches.open(CACHE_NAME);
  const cached = await cache.match(request);
  if (cached) {
    return cached;
  }
  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    return new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
  }
}

/**
 * Network First strategy - for HTML pages
 * @param {Request} request
 * @returns {Promise<Response>}
 */
async function networkFirst(request) {
  const cache = await caches.open(CACHE_NAME);
  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    const cached = await cache.match(request);
    if (cached) {
      return cached;
    }
    // Return offline fallback
    return caches.match('/tienda/offline', { cacheName: OFFLINE_CACHE_NAME }) ||
      new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
  }
}

/**
 * Network Only strategy - for API/cart mutations
 * @param {Request} request
 * @returns {Promise<Response>}
 */
async function networkOnly(request) {
  try {
    const response = await fetch(request);
    // If offline, queue the mutation for later replay
    if (!response.ok && response.status === 0) {
      throw new Error('Network error');
    }
    return response;
  } catch (error) {
    // Queue for background sync when online
    if (isCartMutation(request)) {
      await queueCartMutation(request);
      return new Response(
        JSON.stringify({ 
          queued: true, 
          message: 'Operación guardada para sincronizar cuando haya conexión' 
        }),
        { 
          status: 202, 
          headers: { 'Content-Type': 'application/json' } 
        }
      );
    }
    throw error;
  }
}

/**
 * Queue a cart mutation for background sync
 * @param {Request} request
 * @returns {Promise<void>}
 */
async function queueCartMutation(request) {
  const clonedRequest = request.clone();
  const body = await clonedRequest.text();
  const headers = {};
  clonedRequest.headers.forEach((value, key) => {
    headers[key] = value;
  });
  
  const op = {
    id: crypto.randomUUID(),
    method: clonedRequest.method,
    url: clonedRequest.url,
    headers,
    body,
    timestamp: Date.now(),
  };
  
  await savePendingOperation(op);
  
  // Register for background sync if available
  if ('serviceWorker' in navigator && 'sync' in self.registration) {
    try {
      await self.registration.sync.register('cart-sync');
    } catch {
      // Sync registration failed, will retry on next online event
    }
  }
}

/**
 * Replay queued cart mutations
 * @returns {Promise<void>}
 */
async function replayPendingOperations() {
  const operations = await getPendingOperations();
  for (const op of operations) {
    try {
      const response = await fetch(op.url, {
        method: op.method,
        headers: op.headers,
        body: op.body,
        credentials: 'include',
      });
      
      if (response.ok || response.status === 422) {
        // Success or validation error (not a network error) - remove from queue
        await removePendingOperation(op.id);
      } else if (response.status >= 500) {
        // Server error - keep in queue for retry
        console.warn('Server error replaying cart op:', op.id, response.status);
      }
    } catch (error) {
      // Network error - keep in queue
      console.warn('Network error replaying cart op:', op.id);
      break; // Stop on first network error, will retry later
    }
  }
  
  // Notify clients that sync completed
  const clients = await self.clients.matchAll();
  clients.forEach((client) => {
    client.postMessage({ type: 'cart-sync-complete' });
  });
}

// ============================
// Offline fallback page
// ============================

/**
 * Precache the offline fallback page
 * @returns {Promise<void>}
 */
async function precacheOfflinePage() {
  const cache = await caches.open(OFFLINE_CACHE_NAME);
  try {
    const response = await fetch('/tienda/offline');
    if (response.ok) {
      await cache.put('/tienda/offline', response);
    }
  } catch {
    // If /tienda/offline route doesn't exist yet, create a minimal fallback
    const fallbackHtml = `<!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sin conexión · LESCO GNC</title>
<style>
body{font-family:system-ui;padding:2rem;text-align:center;max-width:40rem;margin:0 auto}
.card{background:#fff;border:1px solid #e4e4e7;border-radius:.75rem;padding:2rem;margin:1rem auto;max-width:32rem}
h1{margin:0 0 1rem}.muted{color:#71717a}.btn{display:inline-block;margin-top:1rem;padding:.6rem 1.1rem;background:#0a0a0a;color:#fff;border-radius:.5rem;text-decoration:none;font-weight:600}
</style>
</head>
<body>
<div class="card">
<h1>Estás sin conexión</h1>
<p class="muted">No hay internet disponible. Los productos cacheados se muestran a continuación.</p>
<div id="cached-products" class="muted">Cargando productos guardados…</div>
<a class="btn" href="/tienda" onclick="return navigator.onLine">Reintentar</a>
</div>
<script>
(async()=>{
try{
const cache=await caches.open('lesco-v${CACHE_VERSION}');
const res=await cache.match('/tienda');
if(res){
const html=await res.text();
const parser=new DOMParser();
const doc=parser.parseFromString(html,'text/html');
const cards=doc.querySelectorAll('.card');
if(cards.length>1){
document.getElementById('cached-products').innerHTML=Array.from(cards).slice(1).map(c=>c.outerHTML).join('');
}else{
document.getElementById('cached-products').textContent='No hay productos cacheados.';
}
}else{
document.getElementById('cached-products').textContent='No hay productos cacheados.';
}
}catch(e){
document.getElementById('cached-products').textContent='Error cargando productos cacheados.';
}
})();
</script>
</body></html>`;
    await cache.put('/tienda/offline', new Response(fallbackHtml, {
      headers: { 'Content-Type': 'text/html; charset=utf-8' }
    }));
  }
}

// ============================
// Service Worker lifecycle
// ============================

self.addEventListener('install', (event) => {
  event.waitUntil(
    (async () => {
      await precacheOfflinePage();
      // Skip waiting to activate immediately
      await self.skipWaiting();
    })()
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    (async () => {
      // Clean up old caches
      const cacheNames = await caches.keys();
      await Promise.all(
        cacheNames
          .filter((name) => name.startsWith('lesco-') && name !== CACHE_NAME && name !== OFFLINE_CACHE_NAME)
          .map((name) => caches.delete(name))
      );
      // Claim all clients immediately
      await self.clients.claim();
    })()
  );
});

// ============================
// Fetch handler
// ============================

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Only handle same-origin requests
  if (url.origin !== self.location.origin) {
    return;
  }
  
  // Never cache auth pages, login, logout, registro
  if (isNeverCache(request) || isAuthRequired(request)) {
    return;
  }
  
  // Cart mutations: Network Only, queue when offline (check BEFORE HTML pages)
  if (isCartMutation(request)) {
    event.respondWith(networkOnly(request));
    return;
  }
  
  // Skip non-GET for static assets and HTML pages (let browser handle natively)
  if (request.method !== 'GET') {
    return;
  }
  
  // Static assets: Cache First
  if (isStaticAsset(request)) {
    event.respondWith(cacheFirst(request));
    return;
  }
  
  // HTML pages (tienda, carrito, checkout): Network First with offline fallback
  if (isHtmlPage(request) || (isNavigation(request) && matchesPatterns(url.pathname, HTML_PAGE_PATTERNS))) {
    event.respondWith(networkFirst(request));
    return;
  }
  
  // Default: Network First for other GET requests
  event.respondWith(networkFirst(request));
});

// ============================
// Background Sync
// ============================

self.addEventListener('sync', (event) => {
  if (event.tag === 'cart-sync') {
    event.waitUntil(replayPendingOperations());
  }
});

// ============================
// Online/Offline detection
// ============================

self.addEventListener('online', () => {
  // Trigger background sync when coming online
  replayPendingOperations();
});

// ============================
// Message handling
// ============================

self.addEventListener('message', (event) => {
  if (!event.data) return;
  
  switch (event.data.type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
    case 'GET_VERSION':
      event.ports[0]?.postMessage({ version: CACHE_VERSION });
      break;
    case 'CLEAR_CART_QUEUE':
      clearPendingOperations();
      break;
    case 'GET_QUEUE_LENGTH':
      getPendingOperations().then((ops) => {
        event.ports[0]?.postMessage({ length: ops.length });
      });
      break;
  }
});

console.log(`[SW] LESCO Service Worker v${CACHE_VERSION} loaded`);