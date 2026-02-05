<?php
/**
 * PWA Support para Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para soporte PWA (Progressive Web App)
 */
class Flavor_GC_PWA {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicialización
     */
    private function init() {
        // Agregar manifest link
        add_action('wp_head', [$this, 'agregar_manifest_link']);

        // Agregar meta tags PWA
        add_action('wp_head', [$this, 'agregar_meta_tags']);

        // Registrar service worker
        add_action('wp_footer', [$this, 'registrar_service_worker']);

        // Endpoint para manifest dinámico
        add_action('rest_api_init', [$this, 'registrar_endpoints']);

        // Generar archivos estáticos si no existen
        add_action('init', [$this, 'generar_archivos_estaticos']);
    }

    /**
     * Agregar link al manifest
     */
    public function agregar_manifest_link() {
        if (!$this->esta_habilitado()) {
            return;
        }

        $manifest_url = $this->obtener_manifest_url();
        echo '<link rel="manifest" href="' . esc_url($manifest_url) . '">' . "\n";
    }

    /**
     * Agregar meta tags para PWA
     */
    public function agregar_meta_tags() {
        if (!$this->esta_habilitado()) {
            return;
        }

        $config = $this->obtener_config();
        ?>
        <meta name="theme-color" content="<?php echo esc_attr($config['theme_color']); ?>">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="<?php echo esc_attr($config['short_name']); ?>">
        <link rel="apple-touch-icon" href="<?php echo esc_url($config['icon_192']); ?>">
        <meta name="mobile-web-app-capable" content="yes">
        <?php
    }

    /**
     * Registrar service worker
     */
    public function registrar_service_worker() {
        if (!$this->esta_habilitado()) {
            return;
        }

        $sw_url = $this->obtener_service_worker_url();
        ?>
        <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo esc_js($sw_url); ?>')
                    .then(function(registration) {
                        console.log('GC ServiceWorker registrado:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('GC ServiceWorker error:', error);
                    });
            });
        }
        </script>
        <?php
    }

    /**
     * Registrar endpoints REST
     */
    public function registrar_endpoints() {
        register_rest_route('flavor-chat-ia/v1', '/gc/manifest.json', [
            'methods' => 'GET',
            'callback' => [$this, 'generar_manifest'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Generar manifest dinámico
     */
    public function generar_manifest($request) {
        $config = $this->obtener_config();

        $manifest = [
            'name' => $config['name'],
            'short_name' => $config['short_name'],
            'description' => $config['description'],
            'start_url' => $config['start_url'],
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => $config['background_color'],
            'theme_color' => $config['theme_color'],
            'icons' => [
                [
                    'src' => $config['icon_192'],
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $config['icon_512'],
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
            'categories' => ['food', 'shopping'],
            'lang' => get_locale(),
            'dir' => is_rtl() ? 'rtl' : 'ltr',
            'shortcuts' => [
                [
                    'name' => __('Ver Ciclo Actual', 'flavor-chat-ia'),
                    'short_name' => __('Ciclo', 'flavor-chat-ia'),
                    'description' => __('Ver el ciclo de pedidos actual', 'flavor-chat-ia'),
                    'url' => home_url('/grupos-consumo/'),
                    'icons' => [['src' => $config['icon_192'], 'sizes' => '192x192']],
                ],
                [
                    'name' => __('Mi Lista de Compra', 'flavor-chat-ia'),
                    'short_name' => __('Lista', 'flavor-chat-ia'),
                    'description' => __('Ver mi lista de compra', 'flavor-chat-ia'),
                    'url' => home_url('/mi-cuenta/?tab=gc-lista-compra'),
                    'icons' => [['src' => $config['icon_192'], 'sizes' => '192x192']],
                ],
                [
                    'name' => __('Mis Pedidos', 'flavor-chat-ia'),
                    'short_name' => __('Pedidos', 'flavor-chat-ia'),
                    'description' => __('Ver historial de pedidos', 'flavor-chat-ia'),
                    'url' => home_url('/mi-cuenta/?tab=gc-mis-pedidos'),
                    'icons' => [['src' => $config['icon_192'], 'sizes' => '192x192']],
                ],
            ],
        ];

        return new WP_REST_Response($manifest, 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }

    /**
     * Generar archivos estáticos (manifest.json y sw.js)
     */
    public function generar_archivos_estaticos() {
        $upload_dir = wp_upload_dir();
        $gc_dir = $upload_dir['basedir'] . '/gc-pwa/';

        if (!file_exists($gc_dir)) {
            wp_mkdir_p($gc_dir);
        }

        // Generar service worker
        $sw_path = $gc_dir . 'gc-sw.js';
        if (!file_exists($sw_path) || $this->necesita_actualizar($sw_path)) {
            $this->escribir_service_worker($sw_path);
        }
    }

    /**
     * Escribir archivo de service worker
     */
    private function escribir_service_worker($path) {
        $config = $this->obtener_config();
        $version = $config['cache_version'];

        $sw_content = <<<JS
// Service Worker para Grupos de Consumo
const CACHE_NAME = 'gc-cache-v{$version}';
const OFFLINE_URL = '/offline/';

const URLS_TO_CACHE = [
    '/',
    '/grupos-consumo/',
    '/mi-cuenta/',
    '/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/gc-frontend.css',
    '/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/gc-frontend.js'
];

// Instalación
self.addEventListener('install', event => {
    console.log('[GC SW] Install');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[GC SW] Caching app shell');
                return cache.addAll(URLS_TO_CACHE);
            })
            .then(() => self.skipWaiting())
    );
});

// Activación
self.addEventListener('activate', event => {
    console.log('[GC SW] Activate');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => cacheName.startsWith('gc-cache-') && cacheName !== CACHE_NAME)
                    .map(cacheName => {
                        console.log('[GC SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch
self.addEventListener('fetch', event => {
    // Solo manejar GET requests
    if (event.request.method !== 'GET') return;

    // Ignorar requests a la API REST
    if (event.request.url.includes('/wp-json/')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    // Devolver desde cache pero actualizar en background
                    event.waitUntil(
                        fetch(event.request)
                            .then(networkResponse => {
                                if (networkResponse && networkResponse.status === 200) {
                                    caches.open(CACHE_NAME)
                                        .then(cache => cache.put(event.request, networkResponse));
                                }
                            })
                            .catch(() => {})
                    );
                    return response;
                }

                return fetch(event.request)
                    .then(networkResponse => {
                        // Cachear recursos estáticos
                        if (networkResponse && networkResponse.status === 200) {
                            const responseToCache = networkResponse.clone();
                            const url = event.request.url;

                            if (url.endsWith('.css') || url.endsWith('.js') || url.endsWith('.png') || url.endsWith('.jpg')) {
                                caches.open(CACHE_NAME)
                                    .then(cache => cache.put(event.request, responseToCache));
                            }
                        }
                        return networkResponse;
                    })
                    .catch(() => {
                        // Offline fallback para navegación
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                    });
            })
    );
});

// Push notifications
self.addEventListener('push', event => {
    console.log('[GC SW] Push received');

    let data = { title: 'Grupos de Consumo', body: 'Nueva notificación' };

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: '/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/icon-192.png',
        badge: '/wp-content/plugins/flavor-chat-ia/includes/modules/grupos-consumo/assets/badge-72.png',
        vibrate: [100, 50, 100],
        data: data.data || {},
        actions: data.actions || []
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Click en notificación
self.addEventListener('notificationclick', event => {
    console.log('[GC SW] Notification click');
    event.notification.close();

    const data = event.notification.data;
    let url = '/grupos-consumo/';

    if (data.tipo === 'ciclo' && data.ciclo_id) {
        url = '/grupos-consumo/?ciclo=' + data.ciclo_id;
    } else if (data.tipo === 'pedido' && data.pedido_id) {
        url = '/mi-cuenta/?tab=gc-mis-pedidos';
    } else if (data.tipo === 'entrega') {
        url = '/mi-cuenta/?tab=gc-mis-pedidos';
    }

    event.waitUntil(
        clients.matchAll({ type: 'window' })
            .then(clientList => {
                for (const client of clientList) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// Background sync
self.addEventListener('sync', event => {
    console.log('[GC SW] Sync:', event.tag);

    if (event.tag === 'gc-sync-lista') {
        event.waitUntil(sincronizarListaCompra());
    }
});

async function sincronizarListaCompra() {
    try {
        const db = await openDB();
        const items = await db.getAll('lista-pendiente');

        for (const item of items) {
            await fetch('/wp-json/flavor-chat-ia/v1/gc/lista-compra/agregar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(item)
            });
            await db.delete('lista-pendiente', item.id);
        }
    } catch (error) {
        console.error('[GC SW] Sync error:', error);
    }
}

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('gc-offline', 1);
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        request.onupgradeneeded = event => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('lista-pendiente')) {
                db.createObjectStore('lista-pendiente', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}
JS;

        file_put_contents($path, $sw_content);
    }

    /**
     * Verificar si necesita actualizar
     */
    private function necesita_actualizar($path) {
        $mod_time = filemtime($path);
        return (time() - $mod_time) > DAY_IN_SECONDS;
    }

    /**
     * Obtener configuración PWA
     */
    private function obtener_config() {
        $sitio_nombre = get_bloginfo('name');
        $plugin_url = plugins_url('assets/', dirname(__FILE__) . '/');

        return apply_filters('gc_pwa_config', [
            'name' => $sitio_nombre . ' - Grupos de Consumo',
            'short_name' => 'GC ' . substr($sitio_nombre, 0, 8),
            'description' => __('App de grupos de consumo para pedidos colaborativos', 'flavor-chat-ia'),
            'start_url' => home_url('/grupos-consumo/'),
            'background_color' => '#ffffff',
            'theme_color' => '#2c5530',
            'icon_192' => $plugin_url . 'icon-192.png',
            'icon_512' => $plugin_url . 'icon-512.png',
            'cache_version' => '1',
        ]);
    }

    /**
     * Verificar si PWA está habilitado
     */
    private function esta_habilitado() {
        $config = get_option('flavor_gc_settings', []);
        return !empty($config['pwa_enabled']) || true; // Habilitado por defecto
    }

    /**
     * Obtener URL del manifest
     */
    private function obtener_manifest_url() {
        return rest_url('flavor-chat-ia/v1/gc/manifest.json');
    }

    /**
     * Obtener URL del service worker
     */
    private function obtener_service_worker_url() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/gc-pwa/gc-sw.js';
    }

    /**
     * Solicitar permiso para push
     */
    public function solicitar_permiso_push() {
        ?>
        <script>
        function gcSolicitarPermisoPush() {
            if (!('Notification' in window)) {
                console.log('Este navegador no soporta notificaciones');
                return;
            }

            if (Notification.permission === 'granted') {
                gcSuscribirPush();
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(function(permission) {
                    if (permission === 'granted') {
                        gcSuscribirPush();
                    }
                });
            }
        }

        function gcSuscribirPush() {
            navigator.serviceWorker.ready.then(function(registration) {
                const vapidPublicKey = '<?php echo esc_js($this->obtener_vapid_key()); ?>';

                if (!vapidPublicKey) {
                    console.log('VAPID key no configurada');
                    return;
                }

                return registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
                });
            }).then(function(subscription) {
                // Enviar suscripción al servidor
                fetch('/wp-json/flavor-chat-ia/v1/gc/push/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(subscription)
                });
            });
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
        </script>
        <?php
    }

    /**
     * Obtener VAPID public key
     */
    private function obtener_vapid_key() {
        $config = get_option('flavor_gc_settings', []);
        return $config['vapid_public_key'] ?? '';
    }
}
