<?php
/**
 * Registro Central de Integraciones entre Modulos
 *
 * Gestiona las relaciones dinamicas entre modulos polivalentes
 * y modulos base del sistema.
 *
 * CONCEPTOS CLAVE:
 * - Provider: Modulo que OFRECE contenido (Recetas, Videos, Podcast, etc.)
 * - Consumer: Modulo que ACEPTA contenido de otros (Productos, Productores, Eventos, etc.)
 * - Integration: Relacion activa entre un provider y un consumer
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase del Registro de Integraciones
 */
class Flavor_Integration_Registry {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Providers registrados
     */
    private $providers = [];

    /**
     * Consumers registrados
     */
    private $consumers = [];

    /**
     * Mapa de integraciones activas
     */
    private $active_integrations = [];

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Cargar trait de integraciones
        $trait_file = dirname(__FILE__) . '/trait-module-integrations.php';
        if (file_exists($trait_file)) {
            require_once $trait_file;
        }

        // Inicializar despues de que todos los modulos esten cargados
        add_action('init', [$this, 'collect_registrations'], 99);
        add_action('init', [$this, 'setup_integrations'], 100);

        // API REST para integraciones
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Dashboard widget con resumen de integraciones
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);
    }

    /**
     * Recoger registros de providers y consumers
     */
    public function collect_registrations() {
        $this->providers = apply_filters('flavor_integration_providers', []);
        $this->consumers = apply_filters('flavor_integration_consumers', []);

        // Calcular integraciones activas
        foreach ($this->consumers as $consumer_id => $consumer) {
            foreach ($consumer['accepted'] as $provider_id) {
                if (isset($this->providers[$provider_id])) {
                    $this->active_integrations[] = [
                        'provider' => $provider_id,
                        'consumer' => $consumer_id,
                        'targets' => $consumer['targets'],
                    ];
                }
            }
        }
    }

    /**
     * Configurar integraciones activas
     */
    public function setup_integrations() {
        // Las integraciones ya se configuran via los traits
        // Este hook permite extensiones adicionales
        do_action('flavor_integrations_ready', $this->active_integrations);
    }

    /**
     * Obtener providers registrados
     */
    public function get_providers() {
        return $this->providers;
    }

    /**
     * Obtener consumers registrados
     */
    public function get_consumers() {
        return $this->consumers;
    }

    /**
     * Obtener integraciones activas
     */
    public function get_active_integrations() {
        return $this->active_integrations;
    }

    /**
     * Verificar si una integracion esta activa
     */
    public function is_integration_active($provider_id, $consumer_id = null) {
        if ($consumer_id === null) {
            return isset($this->providers[$provider_id]);
        }

        foreach ($this->active_integrations as $integration) {
            if ($integration['provider'] === $provider_id && $integration['consumer'] === $consumer_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener contenido relacionado de un objeto
     *
     * @param int    $object_id   ID del objeto
     * @param string $object_type 'post' o 'user'
     * @param string $provider_id ID del provider (opcional, null = todos)
     * @return array
     */
    public function get_related_content($object_id, $object_type = 'post', $provider_id = null) {
        $related = [];

        $providers_to_check = $provider_id ? [$provider_id] : array_keys($this->providers);

        foreach ($providers_to_check as $pid) {
            if (!isset($this->providers[$pid])) {
                continue;
            }

            $meta_key = '_flavor_rel_' . $pid;
            $meta_type = $object_type === 'user' ? 'user' : 'post';
            $ids = get_metadata($meta_type, $object_id, $meta_key, true);

            if (!empty($ids) && is_array($ids)) {
                $provider = $this->providers[$pid];
                $items = get_posts([
                    'post__in' => $ids,
                    'post_type' => $provider['post_type'] ?? 'any',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                ]);

                $related[$pid] = [
                    'provider' => $provider,
                    'items' => $items,
                ];
            }
        }

        return $related;
    }

    /**
     * Obtener objetos que referencian un contenido
     *
     * @param int    $content_id  ID del contenido (ej: una receta)
     * @param string $provider_id ID del provider
     * @return array
     */
    public function get_reverse_relations($content_id, $provider_id) {
        $meta_key = '_flavor_rel_' . $provider_id . '_reverse';
        $ids = get_post_meta($content_id, $meta_key, true);

        if (empty($ids) || !is_array($ids)) {
            return [];
        }

        return get_posts([
            'post__in' => $ids,
            'post_type' => 'any',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/integrations', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_integrations'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ]);

        register_rest_route('flavor/v1', '/integrations/(?P<object_type>post|user)/(?P<object_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_object_relations'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ]);

        register_rest_route('flavor/v1', '/integrations/(?P<object_type>post|user)/(?P<object_id>\d+)/(?P<provider_id>[a-z_]+)', [
            'methods' => ['POST', 'DELETE'],
            'callback' => [$this, 'api_manage_relation'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ]);
    }

    /**
     * API: Obtener todas las integraciones
     */
    public function api_get_integrations($request) {
        return rest_ensure_response([
            'providers' => array_map(function($p) {
                unset($p['module_instance']);
                return $p;
            }, $this->providers),
            'consumers' => $this->consumers,
            'active_integrations' => $this->active_integrations,
        ]);
    }

    /**
     * API: Obtener relaciones de un objeto
     */
    public function api_get_object_relations($request) {
        $object_id = absint($request['object_id']);
        $object_type = $request['object_type'];

        return rest_ensure_response([
            'object_id' => $object_id,
            'object_type' => $object_type,
            'relations' => $this->get_related_content($object_id, $object_type),
        ]);
    }

    /**
     * API: Gestionar relacion
     */
    public function api_manage_relation($request) {
        $object_id = absint($request['object_id']);
        $object_type = $request['object_type'];
        $provider_id = sanitize_key($request['provider_id']);

        $meta_key = '_flavor_rel_' . $provider_id;
        $meta_type = $object_type === 'user' ? 'user' : 'post';

        $current = get_metadata($meta_type, $object_id, $meta_key, true);
        if (!is_array($current)) {
            $current = [];
        }

        if ($request->get_method() === 'POST') {
            // Agregar relacion
            $content_id = absint($request->get_param('content_id'));
            if ($content_id && !in_array($content_id, $current)) {
                $current[] = $content_id;
            }
        } else {
            // Eliminar relacion
            $content_id = absint($request->get_param('content_id'));
            $current = array_diff($current, [$content_id]);
        }

        if ($object_type === 'user') {
            update_user_meta($object_id, $meta_key, array_values($current));
        } else {
            update_post_meta($object_id, $meta_key, array_values($current));
        }

        return rest_ensure_response([
            'success' => true,
            'relations' => $current,
        ]);
    }

    /**
     * Widget de dashboard con resumen
     */
    public function register_dashboard_widget($registry) {
        if (empty($this->active_integrations) || !$registry instanceof Flavor_Widget_Registry) {
            return;
        }

        if (!class_exists('Flavor_Module_Widget')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
        }

        $registry->register(new Flavor_Module_Widget([
            'id' => 'integrations-summary',
            'title' => __('Integraciones Activas', 'flavor-chat-ia'),
            'icon' => 'dashicons-networking',
            'size' => 'medium',
            'category' => 'sistema',
            'priority' => 90,
            'refreshable' => true,
            'cache_time' => 300,
            'data_callback' => [$this, 'get_dashboard_widget_data'],
            'render_callback' => function() {
                $this->render_dashboard_widget();
            },
        ]));
    }

    /**
     * Datos del widget de integraciones para el dashboard unificado.
     */
    public function get_dashboard_widget_data(): array {
        $items = [];

        foreach (array_slice($this->active_integrations, 0, 5) as $integration) {
            $provider = $this->providers[$integration['provider']] ?? null;
            $consumer = $this->consumers[$integration['consumer']] ?? null;

            $items[] = [
                'icon' => 'dashicons-randomize',
                'title' => ($provider['name'] ?? $integration['provider']) . ' -> ' . ($consumer['name'] ?? $integration['consumer']),
                'meta' => !empty($integration['targets']) ? implode(', ', (array) $integration['targets']) : __('Sin targets', 'flavor-chat-ia'),
                'badge' => __('Activa', 'flavor-chat-ia'),
                'badge_color' => 'success',
            ];
        }

        return [
            'stats' => [
                [
                    'icon' => 'dashicons-admin-plugins',
                    'valor' => count($this->providers),
                    'label' => __('Providers', 'flavor-chat-ia'),
                    'color' => 'info',
                ],
                [
                    'icon' => 'dashicons-screenoptions',
                    'valor' => count($this->consumers),
                    'label' => __('Consumers', 'flavor-chat-ia'),
                    'color' => 'primary',
                ],
                [
                    'icon' => 'dashicons-networking',
                    'valor' => count($this->active_integrations),
                    'label' => __('Integraciones', 'flavor-chat-ia'),
                    'color' => 'success',
                ],
            ],
            'items' => $items,
            'empty_state' => __('No hay integraciones activas.', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver integraciones', 'flavor-chat-ia'),
                    'url' => admin_url('admin.php?page=flavor-chat-ia'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * Renderizar widget de dashboard
     */
    public function render_dashboard_widget() {
        ?>
        <div class="flavor-integrations-widget">
            <p><?php printf(
                __('%d modulos de contenido disponibles para vincular', 'flavor-chat-ia'),
                count($this->providers)
            ); ?></p>

            <ul style="margin: 10px 0;">
                <?php foreach ($this->providers as $provider): ?>
                <li>
                    <span class="dashicons <?php echo esc_attr($provider['icon']); ?>" style="color: #0073aa;"></span>
                    <?php echo esc_html($provider['label']); ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Helper: Renderizar seccion de contenido relacionado en frontend
     *
     * @param int    $object_id   ID del objeto
     * @param string $object_type Tipo de objeto
     * @param array  $options     Opciones de renderizado
     */
    public function render_related_content($object_id, $object_type = 'post', $options = []) {
        $options = wp_parse_args($options, [
            'title' => __('Contenido Relacionado', 'flavor-chat-ia'),
            'providers' => null, // null = todos
            'layout' => 'grid', // grid, list, tabs
            'columns' => 3,
        ]);

        $related = $this->get_related_content($object_id, $object_type, $options['providers']);

        if (empty($related)) {
            return;
        }

        ?>
        <div class="flavor-related-content flavor-layout-<?php echo esc_attr($options['layout']); ?>">
            <?php if ($options['title']): ?>
            <h3><?php echo esc_html($options['title']); ?></h3>
            <?php endif; ?>

            <?php foreach ($related as $provider_id => $data):
                if (empty($data['items'])) continue;
                $provider = $data['provider'];
            ?>
            <div class="flavor-related-section" data-provider="<?php echo esc_attr($provider_id); ?>">
                <h4>
                    <span class="dashicons <?php echo esc_attr($provider['icon']); ?>"></span>
                    <?php echo esc_html($provider['label']); ?>
                </h4>

                <div class="flavor-related-items" style="display: grid; grid-template-columns: repeat(<?php echo intval($options['columns']); ?>, 1fr); gap: 15px;">
                    <?php foreach ($data['items'] as $item):
                        $thumbnail = get_the_post_thumbnail_url($item->ID, 'thumbnail');
                    ?>
                    <a href="<?php echo get_permalink($item->ID); ?>" class="flavor-related-item" style="display: block; text-decoration: none; color: inherit; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                        <?php if ($thumbnail): ?>
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="" style="width: 100%; height: 100px; object-fit: cover;" />
                        <?php endif; ?>
                        <div style="padding: 10px;">
                            <strong style="display: block; font-size: 14px;"><?php echo esc_html($item->post_title); ?></strong>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Integration_Registry::get_instance();
}, 5);

/**
 * Funcion helper global para obtener el registro
 */
function flavor_integrations() {
    return Flavor_Integration_Registry::get_instance();
}
