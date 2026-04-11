/**
 * Visual Builder Pro - Service Worker
 *
 * Proporciona capacidades offline para el editor VBP mediante:
 * - Cache de assets estaticos (CSS, JS, fonts, iconos)
 * - Network-first para API calls con fallback a cache
 * - Background sync para cambios pendientes
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

const CACHE_VERSION = 'vbp-v1';
const CACHE_STATIC = `${CACHE_VERSION}-static`;
const CACHE_DYNAMIC = `${CACHE_VERSION}-dynamic`;
const CACHE_API = `${CACHE_VERSION}-api`;

/**
 * Assets estaticos a pre-cachear durante instalacion
 * Paths relativos a la raiz del plugin
 */
const STATIC_ASSETS = [
    // CSS Core del Editor
    'assets/vbp/css/editor-core.css',
    'assets/vbp/css/editor-canvas.css',
    'assets/vbp/css/editor-panels.css',
    'assets/vbp/css/editor-toolbar.css',
    'assets/vbp/css/editor-responsive.css',
    'assets/vbp/css/editor-selectors.css',
    'assets/vbp/css/editor-richtext.css',
    'assets/vbp/css/editor-command-palette.css',
    'assets/vbp/css/editor-statusbar.css',
    'assets/vbp/css/editor-toast.css',
    'assets/vbp/css/offline.css',

    // CSS Componentes
    'assets/vbp/css/vbp-design-tokens.css',
    'assets/vbp/css/vbp-blocks-enhanced.css',
    'assets/vbp/css/frontend-components.css',

    // JS Core
    'assets/vbp/js/vbp-store.js',
    'assets/vbp/js/vbp-app.js',
    'assets/vbp/js/vbp-canvas.js',
    'assets/vbp/js/vbp-inspector.js',
    'assets/vbp/js/vbp-layers.js',
    'assets/vbp/js/vbp-api.js',
    'assets/vbp/js/vbp-keyboard-modular.js',
    'assets/vbp/js/vbp-toast.js',
    'assets/vbp/js/vbp-indexed-db.js',
    'assets/vbp/js/vbp-offline-sync.js',

    // Vendor
    'assets/vbp/vendor/alpine.min.js',
    'assets/vbp/vendor/alpine-collapse.min.js',
    'assets/vbp/vendor/sortable.min.js',
    'assets/vbp/vendor/material-icons.css',
    'assets/vbp/vendor/fontawesome.min.css',

    // Fonts (Material Icons)
    'assets/vbp/vendor/fonts/material-icons.woff2',

    // Fonts (FontAwesome)
    'assets/vbp/vendor/webfonts/fa-solid-900.woff2',
    'assets/vbp/vendor/webfonts/fa-regular-400.woff2',
    'assets/vbp/vendor/webfonts/fa-brands-400.woff2',
];

/**
 * Patrones de URL para diferentes estrategias de cache
 */
const URL_PATTERNS = {
    // Assets estaticos - Cache First
    static: [
        /\.css(\?.*)?$/,
        /\.js(\?.*)?$/,
        /\.woff2?(\?.*)?$/,
        /\.ttf(\?.*)?$/,
        /\.svg(\?.*)?$/,
        /\.png(\?.*)?$/,
        /\.jpg(\?.*)?$/,
        /\.jpeg(\?.*)?$/,
        /\.gif(\?.*)?$/,
        /\.ico(\?.*)?$/,
    ],

    // API REST de WordPress - Network First
    api: [
        /\/wp-json\/flavor-vbp\//,
        /\/wp-json\/flavor-site-builder\//,
        /\/wp-admin\/admin-ajax\.php/,
    ],

    // Imagenes de usuario (uploads) - Cache con expiracion
    uploads: [
        /\/wp-content\/uploads\//,
    ],

    // URLs que nunca se cachean
    noCache: [
        /\/wp-admin\/(?!admin-ajax)/,
        /\/wp-login\.php/,
        /chrome-extension:/,
        /livereload/,
        /browser-sync/,
    ],
};

/**
 * Duracion de cache para diferentes tipos de recursos (en ms)
 */
const CACHE_DURATIONS = {
    static: 7 * 24 * 60 * 60 * 1000,  // 7 dias
    api: 5 * 60 * 1000,                // 5 minutos
    uploads: 24 * 60 * 60 * 1000,      // 1 dia
};

/**
 * Evento Install: Pre-cachear assets estaticos
 */
self.addEventListener('install', (event) => {
    console.log('[VBP SW] Installing service worker...');

    event.waitUntil(
        caches.open(CACHE_STATIC)
            .then((cache) => {
                console.log('[VBP SW] Pre-caching static assets');

                // Intentar cachear cada asset individualmente para manejar errores
                const cachePromises = STATIC_ASSETS.map((assetPath) => {
                    const fullUrl = self.registration.scope + assetPath;

                    return fetch(fullUrl, { mode: 'no-cors' })
                        .then((response) => {
                            if (response.ok || response.type === 'opaque') {
                                return cache.put(fullUrl, response);
                            }
                            console.warn(`[VBP SW] Failed to cache: ${assetPath}`);
                            return Promise.resolve();
                        })
                        .catch((error) => {
                            console.warn(`[VBP SW] Error caching ${assetPath}:`, error.message);
                            return Promise.resolve();
                        });
                });

                return Promise.all(cachePromises);
            })
            .then(() => {
                console.log('[VBP SW] Static assets cached');
                // Activar inmediatamente sin esperar a que se cierren otras pestanas
                return self.skipWaiting();
            })
    );
});

/**
 * Evento Activate: Limpiar caches antiguos
 */
self.addEventListener('activate', (event) => {
    console.log('[VBP SW] Activating service worker...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => {
                            // Eliminar caches de versiones anteriores
                            return cacheName.startsWith('vbp-') &&
                                   !cacheName.startsWith(CACHE_VERSION);
                        })
                        .map((cacheName) => {
                            console.log(`[VBP SW] Deleting old cache: ${cacheName}`);
                            return caches.delete(cacheName);
                        })
                );
            })
            .then(() => {
                console.log('[VBP SW] Old caches cleared');
                // Tomar control de todas las pestanas inmediatamente
                return self.clients.claim();
            })
    );
});

/**
 * Evento Fetch: Interceptar requests y aplicar estrategias de cache
 */
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Solo manejar requests GET
    if (request.method !== 'GET') {
        return;
    }

    // Ignorar URLs que no deben cachearse
    if (shouldNotCache(url.href)) {
        return;
    }

    // Determinar estrategia segun tipo de recurso
    if (isApiRequest(url.href)) {
        event.respondWith(networkFirstStrategy(request, CACHE_API));
    } else if (isStaticAsset(url.href)) {
        event.respondWith(staleWhileRevalidate(request, CACHE_STATIC));
    } else if (isUploadedImage(url.href)) {
        event.respondWith(cacheFirstWithExpiration(request, CACHE_DYNAMIC, CACHE_DURATIONS.uploads));
    }
});

/**
 * Evento Message: Comunicacion con el cliente
 */
self.addEventListener('message', (event) => {
    const { type, payload } = event.data || {};

    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;

        case 'CLEAR_CACHE':
            clearAllCaches().then(() => {
                event.ports[0]?.postMessage({ success: true });
            });
            break;

        case 'GET_CACHE_STATUS':
            getCacheStatus().then((status) => {
                event.ports[0]?.postMessage(status);
            });
            break;

        case 'CACHE_ASSETS':
            cacheAdditionalAssets(payload.assets).then(() => {
                event.ports[0]?.postMessage({ success: true });
            });
            break;
    }
});

/**
 * Evento Sync: Background sync cuando vuelve la conexion
 */
self.addEventListener('sync', (event) => {
    console.log('[VBP SW] Background sync triggered:', event.tag);

    if (event.tag === 'vbp-sync-pending') {
        event.waitUntil(syncPendingChanges());
    }
});

// =============================================================================
// ESTRATEGIAS DE CACHE
// =============================================================================

/**
 * Network First: Intentar red primero, fallback a cache
 * Ideal para API calls donde queremos datos frescos
 */
async function networkFirstStrategy(request, cacheName) {
    try {
        const networkResponse = await fetch(request);

        // Cachear respuesta exitosa
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.log('[VBP SW] Network failed, falling back to cache:', request.url);

        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Si no hay cache, devolver respuesta offline
        return createOfflineResponse(request);
    }
}

/**
 * Stale While Revalidate: Devolver cache inmediatamente, actualizar en background
 * Ideal para assets estaticos que cambian poco
 */
async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    // Fetch en background para actualizar cache
    const fetchPromise = fetch(request)
        .then((networkResponse) => {
            if (networkResponse.ok) {
                cache.put(request, networkResponse.clone());
            }
            return networkResponse;
        })
        .catch((error) => {
            console.log('[VBP SW] Background fetch failed:', request.url);
            return null;
        });

    // Devolver cache inmediatamente si existe, sino esperar fetch
    return cachedResponse || fetchPromise || createOfflineResponse(request);
}

/**
 * Cache First con Expiracion: Usar cache si no ha expirado
 * Ideal para imagenes de usuario
 */
async function cacheFirstWithExpiration(request, cacheName, maxAge) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) {
        const cachedDate = cachedResponse.headers.get('sw-cached-date');
        const isExpired = cachedDate && (Date.now() - parseInt(cachedDate, 10)) > maxAge;

        if (!isExpired) {
            return cachedResponse;
        }
    }

    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            // Clonar y agregar timestamp
            const responseToCache = networkResponse.clone();
            const headers = new Headers(responseToCache.headers);
            headers.set('sw-cached-date', Date.now().toString());

            const modifiedResponse = new Response(await responseToCache.blob(), {
                status: responseToCache.status,
                statusText: responseToCache.statusText,
                headers: headers,
            });

            cache.put(request, modifiedResponse);
        }

        return networkResponse;
    } catch (error) {
        return cachedResponse || createOfflineResponse(request);
    }
}

// =============================================================================
// UTILIDADES
// =============================================================================

/**
 * Verifica si una URL corresponde a una peticion API
 */
function isApiRequest(url) {
    return URL_PATTERNS.api.some((pattern) => pattern.test(url));
}

/**
 * Verifica si una URL corresponde a un asset estatico
 */
function isStaticAsset(url) {
    return URL_PATTERNS.static.some((pattern) => pattern.test(url));
}

/**
 * Verifica si una URL corresponde a una imagen subida
 */
function isUploadedImage(url) {
    return URL_PATTERNS.uploads.some((pattern) => pattern.test(url));
}

/**
 * Verifica si una URL no debe cachearse
 */
function shouldNotCache(url) {
    return URL_PATTERNS.noCache.some((pattern) => pattern.test(url));
}

/**
 * Crea una respuesta offline generica
 */
function createOfflineResponse(request) {
    const url = new URL(request.url);

    // Para API calls, devolver JSON de error
    if (isApiRequest(url.href)) {
        return new Response(
            JSON.stringify({
                success: false,
                offline: true,
                message: 'You are offline. Changes will be synced when connection is restored.',
            }),
            {
                status: 503,
                statusText: 'Service Unavailable',
                headers: {
                    'Content-Type': 'application/json',
                    'X-VBP-Offline': 'true',
                },
            }
        );
    }

    // Para otros recursos, devolver placeholder
    return new Response('Offline', {
        status: 503,
        statusText: 'Service Unavailable',
    });
}

/**
 * Limpia todos los caches de VBP
 */
async function clearAllCaches() {
    const cacheNames = await caches.keys();
    await Promise.all(
        cacheNames
            .filter((name) => name.startsWith('vbp-'))
            .map((name) => caches.delete(name))
    );
    console.log('[VBP SW] All caches cleared');
}

/**
 * Obtiene el estado actual de los caches
 */
async function getCacheStatus() {
    const cacheNames = await caches.keys();
    const vbpCaches = cacheNames.filter((name) => name.startsWith('vbp-'));

    const cacheDetails = await Promise.all(
        vbpCaches.map(async (cacheName) => {
            const cache = await caches.open(cacheName);
            const keys = await cache.keys();
            return {
                name: cacheName,
                count: keys.length,
            };
        })
    );

    return {
        version: CACHE_VERSION,
        caches: cacheDetails,
        totalItems: cacheDetails.reduce((sum, cache) => sum + cache.count, 0),
    };
}

/**
 * Cachea assets adicionales bajo demanda
 */
async function cacheAdditionalAssets(assets) {
    if (!assets || !assets.length) return;

    const cache = await caches.open(CACHE_DYNAMIC);

    const cachePromises = assets.map(async (assetUrl) => {
        try {
            const response = await fetch(assetUrl);
            if (response.ok) {
                await cache.put(assetUrl, response);
            }
        } catch (error) {
            console.warn('[VBP SW] Failed to cache additional asset:', assetUrl);
        }
    });

    await Promise.all(cachePromises);
}

/**
 * Sincroniza cambios pendientes con el servidor
 * Esta funcion se comunica con IndexedDB via postMessage
 */
async function syncPendingChanges() {
    console.log('[VBP SW] Syncing pending changes...');

    // Notificar a todos los clientes que inicien sync
    const clients = await self.clients.matchAll({ type: 'window' });

    for (const client of clients) {
        client.postMessage({
            type: 'SYNC_REQUIRED',
            timestamp: Date.now(),
        });
    }
}

console.log('[VBP SW] Service Worker loaded');
