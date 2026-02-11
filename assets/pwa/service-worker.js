/**
 * Flavor Platform - Service Worker
 * PWA con estrategias de cache usando Workbox
 *
 * @package FlavorPlatform
 * @since 3.1.0
 */

// Importar Workbox desde CDN
importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-sw.js');

// Configurar Workbox
workbox.setConfig({
    debug: false
});

// Usar nombres descriptivos para las constantes
const CACHE_VERSION = 'v1.0.0';
const CACHE_STATIC_NAME = `flavor-static-${CACHE_VERSION}`;
const CACHE_DYNAMIC_NAME = `flavor-dynamic-${CACHE_VERSION}`;
const CACHE_IMAGES_NAME = `flavor-images-${CACHE_VERSION}`;
const CACHE_API_NAME = `flavor-api-${CACHE_VERSION}`;

// URLs para precache (se inyectarán dinámicamente desde PHP)
const PRECACHE_URLS = self.__FLAVOR_PRECACHE_URLS || [];

// URL de la página offline
const OFFLINE_PAGE_URL = self.__FLAVOR_OFFLINE_URL || '/flavor-offline/';

// API base path
const FLAVOR_API_PATH = '/wp-json/flavor/';

// Estrategias de Workbox
const { registerRoute, NavigationRoute, setCatchHandler } = workbox.routing;
const {
    CacheFirst,
    NetworkFirst,
    StaleWhileRevalidate,
    NetworkOnly
} = workbox.strategies;
const { ExpirationPlugin } = workbox.expiration;
const { CacheableResponsePlugin } = workbox.cacheableResponse;
const { BackgroundSyncPlugin } = workbox.backgroundSync;
const { precacheAndRoute, cleanupOutdatedCaches } = workbox.precaching;

// Limpiar caches antiguas
cleanupOutdatedCaches();

// Precache de assets estáticos (inyectados desde PHP)
if (PRECACHE_URLS.length > 0) {
    precacheAndRoute(PRECACHE_URLS);
}

/**
 * Estrategia Network-First para API de Flavor
 * Intenta red primero, fallback a cache
 */
registerRoute(
    ({ url }) => url.pathname.startsWith(FLAVOR_API_PATH),
    new NetworkFirst({
        cacheName: CACHE_API_NAME,
        networkTimeoutSeconds: 10,
        plugins: [
            new CacheableResponsePlugin({
                statuses: [0, 200]
            }),
            new ExpirationPlugin({
                maxEntries: 100,
                maxAgeSeconds: 60 * 60 * 24, // 24 horas
                purgeOnQuotaError: true
            })
        ]
    })
);

/**
 * Estrategia Cache-First para imágenes
 * Sirve desde cache, actualiza en background
 */
registerRoute(
    ({ request }) => request.destination === 'image',
    new CacheFirst({
        cacheName: CACHE_IMAGES_NAME,
        plugins: [
            new CacheableResponsePlugin({
                statuses: [0, 200]
            }),
            new ExpirationPlugin({
                maxEntries: 200,
                maxAgeSeconds: 60 * 60 * 24 * 30, // 30 dias
                purgeOnQuotaError: true
            })
        ]
    })
);

/**
 * Estrategia StaleWhileRevalidate para CSS y JS
 * Sirve cache inmediatamente, actualiza en background
 */
registerRoute(
    ({ request }) =>
        request.destination === 'style' ||
        request.destination === 'script',
    new StaleWhileRevalidate({
        cacheName: CACHE_STATIC_NAME,
        plugins: [
            new CacheableResponsePlugin({
                statuses: [0, 200]
            }),
            new ExpirationPlugin({
                maxEntries: 100,
                maxAgeSeconds: 60 * 60 * 24 * 7, // 7 dias
                purgeOnQuotaError: true
            })
        ]
    })
);

/**
 * Estrategia StaleWhileRevalidate para fuentes
 */
registerRoute(
    ({ request }) => request.destination === 'font',
    new CacheFirst({
        cacheName: 'flavor-fonts',
        plugins: [
            new CacheableResponsePlugin({
                statuses: [0, 200]
            }),
            new ExpirationPlugin({
                maxEntries: 30,
                maxAgeSeconds: 60 * 60 * 24 * 365, // 1 anio
                purgeOnQuotaError: true
            })
        ]
    })
);

/**
 * Estrategia Network-First para paginas HTML
 * Con fallback a pagina offline
 */
registerRoute(
    ({ request }) => request.mode === 'navigate',
    new NetworkFirst({
        cacheName: CACHE_DYNAMIC_NAME,
        networkTimeoutSeconds: 5,
        plugins: [
            new CacheableResponsePlugin({
                statuses: [0, 200]
            }),
            new ExpirationPlugin({
                maxEntries: 50,
                maxAgeSeconds: 60 * 60 * 24, // 24 horas
                purgeOnQuotaError: true
            })
        ]
    })
);

/**
 * Background Sync para formularios
 * Cola las peticiones fallidas para reintentar cuando haya conexion
 */
const backgroundSyncPlugin = new BackgroundSyncPlugin('flavor-form-queue', {
    maxRetentionTime: 60 * 24 * 7, // 7 dias en minutos
    onSync: async ({ queue }) => {
        let entry;
        while ((entry = await queue.shiftRequest())) {
            try {
                await fetch(entry.request.clone());
                console.log('[Flavor SW] Request synced:', entry.request.url);

                // Notificar al cliente sobre el sync exitoso
                self.clients.matchAll().then(clients => {
                    clients.forEach(client => {
                        client.postMessage({
                            type: 'BACKGROUND_SYNC_SUCCESS',
                            url: entry.request.url
                        });
                    });
                });
            } catch (error) {
                console.error('[Flavor SW] Sync failed, re-queuing:', error);
                await queue.unshiftRequest(entry);
                throw error;
            }
        }
    }
});

/**
 * Ruta para formularios con Background Sync
 * POST requests a la API de Flavor
 */
registerRoute(
    ({ url, request }) =>
        url.pathname.startsWith(FLAVOR_API_PATH) &&
        request.method === 'POST',
    new NetworkOnly({
        plugins: [backgroundSyncPlugin]
    }),
    'POST'
);

/**
 * Catch handler para requests fallidos
 * Muestra pagina offline para navegacion
 */
setCatchHandler(async ({ event }) => {
    if (event.request.mode === 'navigate') {
        // Intentar devolver la pagina offline cacheada
        const cachedOfflinePage = await caches.match(OFFLINE_PAGE_URL);
        if (cachedOfflinePage) {
            return cachedOfflinePage;
        }

        // Generar respuesta offline basica si no hay cache
        return new Response(
            generateOfflineHTML(),
            {
                headers: { 'Content-Type': 'text/html; charset=utf-8' }
            }
        );
    }

    // Para imagenes, devolver placeholder
    if (event.request.destination === 'image') {
        return new Response(
            generateOfflineSVG(),
            {
                headers: { 'Content-Type': 'image/svg+xml' }
            }
        );
    }

    return Response.error();
});

/**
 * Genera HTML basico para modo offline
 */
function generateOfflineHTML() {
    return `
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin conexion - Flavor Platform</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .offline-container {
            text-align: center;
            max-width: 400px;
        }
        .offline-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 24px;
        }
        h1 { font-size: 24px; margin-bottom: 16px; }
        p { opacity: 0.9; line-height: 1.6; margin-bottom: 24px; }
        button {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 32px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="offline-container">
        <svg class="offline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0119 12.55M5 12.55a10.94 10.94 0 015.17-2.39M10.71 5.05A16 16 0 0122.58 9M1.42 9a15.91 15.91 0 014.7-2.88M8.53 16.11a6 6 0 016.95 0M12 20h.01"/>
        </svg>
        <h1>Sin conexion a Internet</h1>
        <p>Parece que has perdido la conexion. Verifica tu conexion a Internet e intenta nuevamente.</p>
        <button onclick="window.location.reload()">Reintentar</button>
    </div>
</body>
</html>
    `;
}

/**
 * Genera SVG placeholder para imagenes offline
 */
function generateOfflineSVG() {
    return `
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 150" width="200" height="150">
    <rect fill="#f0f0f0" width="200" height="150"/>
    <text fill="#999" font-family="sans-serif" font-size="12" x="50%" y="50%" text-anchor="middle" dy=".3em">
        Imagen no disponible
    </text>
</svg>
    `;
}

/**
 * Evento install - Cachear recursos esenciales
 */
self.addEventListener('install', (event) => {
    console.log('[Flavor SW] Installing...');

    event.waitUntil(
        caches.open(CACHE_STATIC_NAME).then(async (cache) => {
            console.log('[Flavor SW] Caching static assets');

            // Cachear pagina offline
            try {
                await cache.add(OFFLINE_PAGE_URL);
            } catch (error) {
                console.warn('[Flavor SW] Could not cache offline page:', error);
            }

            return cache;
        })
    );

    // Activar inmediatamente
    self.skipWaiting();
});

/**
 * Evento activate - Limpiar caches antiguas
 */
self.addEventListener('activate', (event) => {
    console.log('[Flavor SW] Activating...');

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((cacheName) => {
                        return cacheName.startsWith('flavor-') &&
                               !cacheName.includes(CACHE_VERSION);
                    })
                    .map((cacheName) => {
                        console.log('[Flavor SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    })
            );
        })
    );

    // Tomar control inmediatamente
    self.clients.claim();
});

/**
 * Mensajes desde el cliente
 */
self.addEventListener('message', (event) => {
    if (!event.data) return;

    switch (event.data.type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;

        case 'CACHE_URLS':
            // Cachear URLs especificas
            if (event.data.urls && Array.isArray(event.data.urls)) {
                caches.open(CACHE_DYNAMIC_NAME).then((cache) => {
                    cache.addAll(event.data.urls);
                });
            }
            break;

        case 'CLEAR_CACHE':
            // Limpiar cache especifica o todas
            if (event.data.cacheName) {
                caches.delete(event.data.cacheName);
            } else {
                caches.keys().then((names) => {
                    names.forEach((name) => {
                        if (name.startsWith('flavor-')) {
                            caches.delete(name);
                        }
                    });
                });
            }
            break;

        case 'GET_CACHE_SIZE':
            // Obtener tamanio del cache
            getCacheSize().then((size) => {
                event.ports[0].postMessage({ size });
            });
            break;
    }
});

/**
 * Calcula el tamanio total del cache
 */
async function getCacheSize() {
    const cacheNames = await caches.keys();
    let totalSize = 0;

    for (const cacheName of cacheNames) {
        if (!cacheName.startsWith('flavor-')) continue;

        const cache = await caches.open(cacheName);
        const requests = await cache.keys();

        for (const request of requests) {
            const response = await cache.match(request);
            if (response) {
                const blob = await response.clone().blob();
                totalSize += blob.size;
            }
        }
    }

    return totalSize;
}

/**
 * Push notifications
 */
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let notificationData;

    try {
        notificationData = event.data.json();
    } catch (error) {
        notificationData = {
            title: 'Flavor Platform',
            body: event.data.text(),
            icon: '/wp-content/plugins/flavor-chat-ia/assets/pwa/icons/icon-192.png'
        };
    }

    const options = {
        body: notificationData.body || '',
        icon: notificationData.icon || '/wp-content/plugins/flavor-chat-ia/assets/pwa/icons/icon-192.png',
        badge: notificationData.badge || '/wp-content/plugins/flavor-chat-ia/assets/pwa/icons/badge-72.png',
        vibrate: notificationData.vibrate || [100, 50, 100],
        data: notificationData.data || {},
        actions: notificationData.actions || [],
        tag: notificationData.tag || 'flavor-notification',
        renotify: notificationData.renotify || false,
        requireInteraction: notificationData.requireInteraction || false
    };

    event.waitUntil(
        self.registration.showNotification(
            notificationData.title || 'Flavor Platform',
            options
        )
    );
});

/**
 * Click en notificacion
 */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const notificationData = event.notification.data;
    let targetUrl = '/';

    // Determinar URL segun accion
    if (event.action && notificationData.actions) {
        const action = notificationData.actions.find(a => a.action === event.action);
        if (action && action.url) {
            targetUrl = action.url;
        }
    } else if (notificationData.url) {
        targetUrl = notificationData.url;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Buscar ventana existente
                for (const client of clientList) {
                    if (client.url === targetUrl && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Abrir nueva ventana
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
    );
});

/**
 * Sincronizacion periodica (si esta disponible)
 */
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'flavor-content-sync') {
        event.waitUntil(syncContent());
    }
});

/**
 * Sincroniza contenido en background
 */
async function syncContent() {
    try {
        // Actualizar cache de API
        const response = await fetch('/wp-json/flavor/v1/pwa/sync');
        const data = await response.json();

        if (data.urls) {
            const cache = await caches.open(CACHE_API_NAME);
            await cache.addAll(data.urls);
        }

        console.log('[Flavor SW] Content synced successfully');
    } catch (error) {
        console.error('[Flavor SW] Content sync failed:', error);
    }
}

console.log('[Flavor SW] Service Worker loaded - Flavor Platform PWA');
