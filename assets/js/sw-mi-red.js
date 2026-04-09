/**
 * Service Worker para Mi Red Social
 *
 * Funcionalidades:
 * - Caché de assets estáticos
 * - Notificaciones push
 * - Offline fallback
 *
 * @package FlavorChatIA
 */

const CACHE_VERSION = 'mi-red-v1';
const STATIC_CACHE = CACHE_VERSION + '-static';
const DYNAMIC_CACHE = CACHE_VERSION + '-dynamic';

// Assets a cachear inmediatamente
const STATIC_ASSETS = [
	'/wp-content/plugins/flavor-platform/assets/css/mi-red-social.css',
	'/wp-content/plugins/flavor-platform/assets/js/mi-red-social.js',
];

// Instalar Service Worker
self.addEventListener('install', (event) => {
	event.waitUntil(
		caches.open(STATIC_CACHE)
			.then(cache => cache.addAll(STATIC_ASSETS))
			.then(() => self.skipWaiting())
	);
});

// Activar y limpiar cachés antiguas
self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches.keys()
			.then(keys => {
				return Promise.all(
					keys
						.filter(key => key.startsWith('mi-red-') && key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
						.map(key => caches.delete(key))
				);
			})
			.then(() => self.clients.claim())
	);
});

// Estrategia de caché: Network First para API, Cache First para assets
self.addEventListener('fetch', (event) => {
	const url = new URL(event.request.url);

	// No cachear peticiones POST o admin-ajax
	if (event.request.method !== 'GET' || url.pathname.includes('admin-ajax')) {
		return;
	}

	// Assets estáticos: Cache First
	if (url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff2?)$/)) {
		event.respondWith(
			caches.match(event.request)
				.then(cached => cached || fetch(event.request)
					.then(response => {
						const clone = response.clone();
						caches.open(DYNAMIC_CACHE)
							.then(cache => cache.put(event.request, clone));
						return response;
					})
				)
		);
		return;
	}
});

// Manejar notificaciones push
self.addEventListener('push', (event) => {
	if (!event.data) {return;}

	const data = event.data.json();
	const options = {
		body: data.body || '',
		icon: data.icon || '/wp-content/plugins/flavor-platform/assets/img/notification-icon.png',
		badge: '/wp-content/plugins/flavor-platform/assets/img/badge-icon.png',
		vibrate: [100, 50, 100],
		data: {
			url: data.url || '/mi-portal/mi-red/',
			dateOfArrival: Date.now(),
		},
		actions: data.actions || [
			{ action: 'open', title: 'Abrir' },
			{ action: 'dismiss', title: 'Descartar' }
		],
		tag: data.tag || 'mi-red-notification',
		renotify: true,
	};

	event.waitUntil(
		self.registration.showNotification(data.title || 'Mi Red', options)
	);
});

// Manejar click en notificación
self.addEventListener('notificationclick', (event) => {
	event.notification.close();

	if (event.action === 'dismiss') {return;}

	const url = event.notification.data?.url || '/mi-portal/mi-red/';

	event.waitUntil(
		clients.matchAll({ type: 'window', includeUncontrolled: true })
			.then(windowClients => {
				// Si ya hay una ventana abierta, enfocarla
				for (const client of windowClients) {
					if (client.url.includes('/mi-portal/mi-red/') && 'focus' in client) {
						client.navigate(url);
						return client.focus();
					}
				}
				// Si no, abrir una nueva
				return clients.openWindow(url);
			})
	);
});

// Sincronización en background (para enviar datos offline)
self.addEventListener('sync', (event) => {
	if (event.tag === 'sync-publicaciones') {
		event.waitUntil(syncPublicaciones());
	}
});

async function syncPublicaciones() {
	// Recuperar publicaciones pendientes del IndexedDB
	// y enviarlas al servidor
	console.log('[SW] Sincronizando publicaciones pendientes...');
}
