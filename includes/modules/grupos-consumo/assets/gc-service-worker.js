/**
 * Service Worker para Grupos de Consumo PWA
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 */

const CACHE_NAME = 'gc-cache-v1';
const OFFLINE_URL = '/offline.html';

// Recursos a cachear en la instalación
const PRECACHE_URLS = [
    '/',
    '/offline.html',
    '/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/gc-frontend.css',
    '/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/gc-frontend.js',
];

// URLs de API que se deben cachear
const API_CACHE_URLS = [
    '/wp-json/flavor/v1/gc/productos',
    '/wp-json/flavor/v1/gc/ciclos/calendario',
    '/wp-json/flavor/v1/gc/cestas-tipo',
];

/**
 * Evento de instalación
 */
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[GC-SW] Precacheando recursos');
                return cache.addAll(PRECACHE_URLS);
            })
            .then(() => {
                console.log('[GC-SW] Instalación completada');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[GC-SW] Error en instalación:', error);
            })
    );
});

/**
 * Evento de activación
 */
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME)
                        .map((name) => {
                            console.log('[GC-SW] Eliminando caché antigua:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                console.log('[GC-SW] Activación completada');
                return self.clients.claim();
            })
    );
});

/**
 * Evento de fetch - Estrategia Network First con fallback a cache
 */
self.addEventListener('fetch', (event) => {
    const requestUrl = new URL(event.request.url);

    // Solo manejar requests GET
    if (event.request.method !== 'GET') {
        return;
    }

    // Ignorar requests de extensiones de Chrome y otros
    if (!requestUrl.protocol.startsWith('http')) {
        return;
    }

    // Estrategia para API
    if (requestUrl.pathname.startsWith('/wp-json/flavor/v1/gc/')) {
        event.respondWith(networkFirstStrategy(event.request));
        return;
    }

    // Estrategia para assets estáticos
    if (isStaticAsset(requestUrl.pathname)) {
        event.respondWith(cacheFirstStrategy(event.request));
        return;
    }

    // Estrategia para páginas HTML
    if (event.request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(networkFirstWithOffline(event.request));
        return;
    }

    // Default: Network first
    event.respondWith(networkFirstStrategy(event.request));
});

/**
 * Estrategia Cache First (para assets estáticos)
 */
async function cacheFirstStrategy(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        // Actualizar cache en background
        fetchAndCache(request);
        return cachedResponse;
    }
    return fetchAndCache(request);
}

/**
 * Estrategia Network First (para API y contenido dinámico)
 */
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

/**
 * Network First con fallback a página offline
 */
async function networkFirstWithOffline(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        // Mostrar página offline
        const offlinePage = await caches.match(OFFLINE_URL);
        if (offlinePage) {
            return offlinePage;
        }
        // Respuesta básica offline
        return new Response(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Sin conexión</title></head>' +
            '<body style="font-family:sans-serif;text-align:center;padding:50px;">' +
            '<h1>🥕 Sin conexión</h1>' +
            '<p>No hay conexión a internet. Por favor, inténtalo más tarde.</p></body></html>',
            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }
}

/**
 * Fetch y cachear
 */
async function fetchAndCache(request) {
    const response = await fetch(request);
    if (response.ok) {
        const cache = await caches.open(CACHE_NAME);
        cache.put(request, response.clone());
    }
    return response;
}

/**
 * Verificar si es un asset estático
 */
function isStaticAsset(pathname) {
    const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2'];
    return staticExtensions.some(ext => pathname.endsWith(ext));
}

/**
 * Evento de push notification
 */
self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let data;
    try {
        data = event.data.json();
    } catch (e) {
        data = {
            title: 'Grupos de Consumo',
            body: event.data.text(),
            icon: '/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/icon-192x192.png',
        };
    }

    const options = {
        body: data.body || data.message,
        icon: data.icon || '/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/icon-192x192.png',
        badge: '/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/',
            dateOfArrival: Date.now(),
        },
        actions: data.actions || [
            { action: 'view', title: 'Ver' },
            { action: 'close', title: 'Cerrar' },
        ],
        tag: data.tag || 'gc-notification',
        renotify: true,
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Grupos de Consumo', options)
    );
});

/**
 * Evento de click en notificación
 */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Si ya hay una ventana abierta, enfocarla
                for (const client of clientList) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Si no, abrir una nueva
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

/**
 * Evento de sincronización en background
 */
self.addEventListener('sync', (event) => {
    if (event.tag === 'gc-sync-pedidos') {
        event.waitUntil(syncPedidos());
    }
    if (event.tag === 'gc-sync-lista-compra') {
        event.waitUntil(syncListaCompra());
    }
});

/**
 * Sincronizar pedidos pendientes
 */
async function syncPedidos() {
    try {
        const db = await openDB();
        const pedidosPendientes = await getPendingData(db, 'pedidos');

        for (const pedido of pedidosPendientes) {
            try {
                const response = await fetch('/wp-json/flavor/v1/gc/pedidos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(pedido.data),
                });

                if (response.ok) {
                    await removePendingData(db, 'pedidos', pedido.id);
                }
            } catch (e) {
                console.error('[GC-SW] Error sincronizando pedido:', e);
            }
        }
    } catch (error) {
        console.error('[GC-SW] Error en syncPedidos:', error);
    }
}

/**
 * Sincronizar lista de compra
 */
async function syncListaCompra() {
    try {
        const db = await openDB();
        const itemsPendientes = await getPendingData(db, 'lista_compra');

        for (const item of itemsPendientes) {
            try {
                const response = await fetch('/wp-json/flavor/v1/gc/lista-compra/agregar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(item.data),
                });

                if (response.ok) {
                    await removePendingData(db, 'lista_compra', item.id);
                }
            } catch (e) {
                console.error('[GC-SW] Error sincronizando item:', e);
            }
        }
    } catch (error) {
        console.error('[GC-SW] Error en syncListaCompra:', error);
    }
}

/**
 * Abrir IndexedDB
 */
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('gc-offline-db', 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('pending_sync')) {
                db.createObjectStore('pending_sync', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

/**
 * Obtener datos pendientes de sincronización
 */
function getPendingData(db, tipo) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['pending_sync'], 'readonly');
        const store = transaction.objectStore('pending_sync');
        const request = store.getAll();

        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const items = request.result.filter(item => item.tipo === tipo);
            resolve(items);
        };
    });
}

/**
 * Eliminar dato sincronizado
 */
function removePendingData(db, tipo, id) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['pending_sync'], 'readwrite');
        const store = transaction.objectStore('pending_sync');
        const request = store.delete(id);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve();
    });
}

console.log('[GC-SW] Service Worker cargado');
