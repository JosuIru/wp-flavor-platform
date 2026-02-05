<?php
/**
 * Module Config - Configuración Granular por Módulo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Config {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Configuraciones por defecto de módulos
     */
    private $module_defaults = [];

    /**
     * Schema de configuración
     */
    private $config_schema = [];

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
        $this->init_schemas();
        $this->init_hooks();
    }

    /**
     * Inicializar schemas de configuración
     */
    private function init_schemas() {
        $this->config_schema = [
            'woocommerce' => [
                'title' => 'WooCommerce',
                'sections' => [
                    'general' => [
                        'title' => 'General',
                        'fields' => [
                            'enable_product_search' => [
                                'type' => 'toggle',
                                'label' => 'Búsqueda de productos',
                                'description' => 'Permitir buscar productos desde el chat',
                                'default' => true,
                            ],
                            'enable_order_tracking' => [
                                'type' => 'toggle',
                                'label' => 'Seguimiento de pedidos',
                                'description' => 'Permitir consultar estado de pedidos',
                                'default' => true,
                            ],
                            'enable_cart_management' => [
                                'type' => 'toggle',
                                'label' => 'Gestión del carrito',
                                'description' => 'Permitir añadir/quitar productos del carrito',
                                'default' => true,
                            ],
                        ],
                    ],
                    'display' => [
                        'title' => 'Visualización',
                        'fields' => [
                            'products_per_page' => [
                                'type' => 'number',
                                'label' => 'Productos por página',
                                'default' => 6,
                                'min' => 1,
                                'max' => 20,
                            ],
                            'show_prices' => [
                                'type' => 'toggle',
                                'label' => 'Mostrar precios',
                                'default' => true,
                            ],
                            'show_stock' => [
                                'type' => 'toggle',
                                'label' => 'Mostrar stock',
                                'default' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'carpooling' => [
                'title' => 'Carpooling',
                'sections' => [
                    'general' => [
                        'title' => 'General',
                        'fields' => [
                            'max_passengers' => [
                                'type' => 'number',
                                'label' => 'Máximo de pasajeros por viaje',
                                'default' => 4,
                                'min' => 1,
                                'max' => 8,
                            ],
                            'advance_booking_days' => [
                                'type' => 'number',
                                'label' => 'Días de antelación máxima',
                                'default' => 30,
                                'min' => 1,
                                'max' => 90,
                            ],
                            'cancellation_hours' => [
                                'type' => 'number',
                                'label' => 'Horas mínimas para cancelar',
                                'default' => 24,
                                'min' => 1,
                                'max' => 72,
                            ],
                        ],
                    ],
                    'pricing' => [
                        'title' => 'Precios',
                        'fields' => [
                            'enable_pricing' => [
                                'type' => 'toggle',
                                'label' => 'Habilitar sistema de precios',
                                'default' => true,
                            ],
                            'price_per_km' => [
                                'type' => 'number',
                                'label' => 'Precio por km (€)',
                                'default' => 0.10,
                                'step' => 0.01,
                                'min' => 0,
                            ],
                            'minimum_price' => [
                                'type' => 'number',
                                'label' => 'Precio mínimo (€)',
                                'default' => 2.00,
                                'step' => 0.50,
                                'min' => 0,
                            ],
                        ],
                    ],
                    'notifications' => [
                        'title' => 'Notificaciones',
                        'fields' => [
                            'notify_new_request' => [
                                'type' => 'toggle',
                                'label' => 'Notificar nuevas solicitudes',
                                'default' => true,
                            ],
                            'notify_booking_confirmed' => [
                                'type' => 'toggle',
                                'label' => 'Notificar reservas confirmadas',
                                'default' => true,
                            ],
                            'notify_trip_reminder' => [
                                'type' => 'toggle',
                                'label' => 'Recordatorio de viaje',
                                'default' => true,
                            ],
                            'reminder_hours_before' => [
                                'type' => 'number',
                                'label' => 'Horas antes del recordatorio',
                                'default' => 24,
                                'min' => 1,
                                'max' => 72,
                            ],
                        ],
                    ],
                ],
            ],
            'banco-tiempo' => [
                'title' => 'Banco de Tiempo',
                'sections' => [
                    'general' => [
                        'title' => 'General',
                        'fields' => [
                            'time_unit' => [
                                'type' => 'select',
                                'label' => 'Unidad de tiempo',
                                'options' => [
                                    'minutes' => 'Minutos',
                                    'hours' => 'Horas',
                                ],
                                'default' => 'hours',
                            ],
                            'min_exchange' => [
                                'type' => 'number',
                                'label' => 'Intercambio mínimo (unidades)',
                                'default' => 1,
                                'min' => 1,
                            ],
                            'max_exchange' => [
                                'type' => 'number',
                                'label' => 'Intercambio máximo (unidades)',
                                'default' => 8,
                                'min' => 1,
                            ],
                        ],
                    ],
                    'categories' => [
                        'title' => 'Categorías',
                        'fields' => [
                            'enable_categories' => [
                                'type' => 'toggle',
                                'label' => 'Habilitar categorías de servicios',
                                'default' => true,
                            ],
                            'default_categories' => [
                                'type' => 'textarea',
                                'label' => 'Categorías predefinidas (una por línea)',
                                'default' => "Cuidados\nEnseñanza\nReparaciones\nJardinería\nTecnología\nCompañía\nTransporte\nOtros",
                            ],
                        ],
                    ],
                ],
            ],
            'grupos-consumo' => [
                'title' => 'Grupos de Consumo',
                'sections' => [
                    'general' => [
                        'title' => 'General',
                        'fields' => [
                            'min_order_amount' => [
                                'type' => 'number',
                                'label' => 'Pedido mínimo (€)',
                                'default' => 20,
                                'min' => 0,
                            ],
                            'delivery_day' => [
                                'type' => 'select',
                                'label' => 'Día de entrega',
                                'options' => [
                                    '1' => 'Lunes',
                                    '2' => 'Martes',
                                    '3' => 'Miércoles',
                                    '4' => 'Jueves',
                                    '5' => 'Viernes',
                                    '6' => 'Sábado',
                                    '0' => 'Domingo',
                                ],
                                'default' => '5',
                            ],
                            'order_deadline_days' => [
                                'type' => 'number',
                                'label' => 'Días antes del cierre de pedidos',
                                'default' => 2,
                                'min' => 1,
                                'max' => 7,
                            ],
                        ],
                    ],
                    'products' => [
                        'title' => 'Productos',
                        'fields' => [
                            'show_producer' => [
                                'type' => 'toggle',
                                'label' => 'Mostrar información del productor',
                                'default' => true,
                            ],
                            'show_origin' => [
                                'type' => 'toggle',
                                'label' => 'Mostrar origen del producto',
                                'default' => true,
                            ],
                            'enable_seasonal_products' => [
                                'type' => 'toggle',
                                'label' => 'Destacar productos de temporada',
                                'default' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'eventos' => [
                'title' => 'Eventos',
                'sections' => [
                    'general' => [
                        'title' => 'General',
                        'fields' => [
                            'enable_registration' => [
                                'type' => 'toggle',
                                'label' => 'Permitir inscripciones',
                                'default' => true,
                            ],
                            'max_capacity' => [
                                'type' => 'number',
                                'label' => 'Capacidad máxima por defecto',
                                'default' => 50,
                                'min' => 1,
                            ],
                            'registration_deadline_hours' => [
                                'type' => 'number',
                                'label' => 'Cierre de inscripciones (horas antes)',
                                'default' => 24,
                                'min' => 0,
                            ],
                        ],
                    ],
                    'display' => [
                        'title' => 'Visualización',
                        'fields' => [
                            'calendar_view' => [
                                'type' => 'select',
                                'label' => 'Vista de calendario por defecto',
                                'options' => [
                                    'month' => 'Mes',
                                    'week' => 'Semana',
                                    'list' => 'Lista',
                                ],
                                'default' => 'month',
                            ],
                            'show_map' => [
                                'type' => 'toggle',
                                'label' => 'Mostrar mapa de ubicación',
                                'default' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'directorio' => [
                'title' => 'Directorio',
                'sections' => [
                    'general' => [
                        'title' => 'General',
                        'fields' => [
                            'entries_per_page' => [
                                'type' => 'number',
                                'label' => 'Entradas por página',
                                'default' => 12,
                                'min' => 6,
                                'max' => 48,
                            ],
                            'enable_search' => [
                                'type' => 'toggle',
                                'label' => 'Habilitar búsqueda',
                                'default' => true,
                            ],
                            'enable_filters' => [
                                'type' => 'toggle',
                                'label' => 'Habilitar filtros',
                                'default' => true,
                            ],
                        ],
                    ],
                    'display' => [
                        'title' => 'Visualización',
                        'fields' => [
                            'default_view' => [
                                'type' => 'select',
                                'label' => 'Vista por defecto',
                                'options' => [
                                    'grid' => 'Cuadrícula',
                                    'list' => 'Lista',
                                    'map' => 'Mapa',
                                ],
                                'default' => 'grid',
                            ],
                            'show_ratings' => [
                                'type' => 'toggle',
                                'label' => 'Mostrar valoraciones',
                                'default' => true,
                            ],
                            'show_contact_info' => [
                                'type' => 'toggle',
                                'label' => 'Mostrar información de contacto',
                                'default' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->config_schema = apply_filters('flavor_module_config_schema', $this->config_schema);

        // Extraer defaults
        foreach ($this->config_schema as $module_id => $module_config) {
            $this->module_defaults[$module_id] = [];
            foreach ($module_config['sections'] as $section_id => $section) {
                foreach ($section['fields'] as $field_id => $field) {
                    $this->module_defaults[$module_id][$field_id] = $field['default'] ?? null;
                }
            }
        }
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_flavor_get_module_config', [$this, 'ajax_get_config']);
        add_action('wp_ajax_flavor_save_module_config', [$this, 'ajax_save_config']);
        add_action('wp_ajax_flavor_reset_module_config', [$this, 'ajax_reset_config']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Obtener configuración de un módulo
     *
     * @param string $module_id
     * @param string|null $key
     * @return mixed
     */
    public function get($module_id, $key = null) {
        $saved_config = get_option("flavor_module_config_{$module_id}", []);
        $defaults = $this->module_defaults[$module_id] ?? [];
        $config = wp_parse_args($saved_config, $defaults);

        if ($key !== null) {
            return $config[$key] ?? null;
        }

        return $config;
    }

    /**
     * Establecer configuración de un módulo
     *
     * @param string $module_id
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set($module_id, $key, $value) {
        $config = get_option("flavor_module_config_{$module_id}", []);
        $config[$key] = $value;
        return update_option("flavor_module_config_{$module_id}", $config);
    }

    /**
     * Guardar configuración completa de un módulo
     *
     * @param string $module_id
     * @param array $config
     * @return bool
     */
    public function save($module_id, $config) {
        // Validar y sanitizar
        $sanitized = $this->sanitize_config($module_id, $config);
        return update_option("flavor_module_config_{$module_id}", $sanitized);
    }

    /**
     * Resetear configuración de un módulo
     *
     * @param string $module_id
     * @return bool
     */
    public function reset($module_id) {
        return delete_option("flavor_module_config_{$module_id}");
    }

    /**
     * Sanitizar configuración
     *
     * @param string $module_id
     * @param array $config
     * @return array
     */
    private function sanitize_config($module_id, $config) {
        $schema = $this->config_schema[$module_id] ?? null;

        if (!$schema) {
            return [];
        }

        $sanitized = [];

        foreach ($schema['sections'] as $section_id => $section) {
            foreach ($section['fields'] as $field_id => $field) {
                if (!isset($config[$field_id])) {
                    continue;
                }

                $value = $config[$field_id];

                switch ($field['type']) {
                    case 'toggle':
                        $sanitized[$field_id] = (bool) $value;
                        break;

                    case 'number':
                        $value = floatval($value);
                        if (isset($field['min'])) {
                            $value = max($field['min'], $value);
                        }
                        if (isset($field['max'])) {
                            $value = min($field['max'], $value);
                        }
                        $sanitized[$field_id] = $value;
                        break;

                    case 'select':
                        if (isset($field['options'][$value])) {
                            $sanitized[$field_id] = $value;
                        }
                        break;

                    case 'textarea':
                        $sanitized[$field_id] = sanitize_textarea_field($value);
                        break;

                    case 'text':
                    default:
                        $sanitized[$field_id] = sanitize_text_field($value);
                        break;
                }
            }
        }

        return $sanitized;
    }

    /**
     * AJAX: Obtener configuración
     */
    public function ajax_get_config() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $module_id = sanitize_text_field($_POST['module_id'] ?? '');

        if (!isset($this->config_schema[$module_id])) {
            wp_send_json_error(['message' => 'Módulo no encontrado']);
        }

        wp_send_json_success([
            'schema' => $this->config_schema[$module_id],
            'config' => $this->get($module_id),
            'defaults' => $this->module_defaults[$module_id],
        ]);
    }

    /**
     * AJAX: Guardar configuración
     */
    public function ajax_save_config() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $module_id = sanitize_text_field($_POST['module_id'] ?? '');
        $config = $_POST['config'] ?? [];

        if (!isset($this->config_schema[$module_id])) {
            wp_send_json_error(['message' => 'Módulo no encontrado']);
        }

        $result = $this->save($module_id, $config);

        if ($result) {
            do_action('flavor_module_config_saved', $module_id, $config);
            wp_send_json_success(['message' => 'Configuración guardada']);
        } else {
            wp_send_json_error(['message' => 'Error al guardar']);
        }
    }

    /**
     * AJAX: Resetear configuración
     */
    public function ajax_reset_config() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $module_id = sanitize_text_field($_POST['module_id'] ?? '');

        if (!isset($this->config_schema[$module_id])) {
            wp_send_json_error(['message' => 'Módulo no encontrado']);
        }

        $this->reset($module_id);

        wp_send_json_success([
            'message' => 'Configuración restablecida',
            'config' => $this->module_defaults[$module_id],
        ]);
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/modules/(?P<module_id>[a-z0-9-]+)/config', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'rest_get_config'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'rest_save_config'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
        ]);

        register_rest_route('flavor/v1', '/modules/config-schema', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_schema'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * REST: Obtener configuración
     */
    public function rest_get_config($request) {
        $module_id = $request['module_id'];

        if (!isset($this->config_schema[$module_id])) {
            return new WP_Error('not_found', 'Módulo no encontrado', ['status' => 404]);
        }

        return rest_ensure_response([
            'module_id' => $module_id,
            'config' => $this->get($module_id),
        ]);
    }

    /**
     * REST: Guardar configuración
     */
    public function rest_save_config($request) {
        $module_id = $request['module_id'];
        $config = $request->get_json_params();

        if (!isset($this->config_schema[$module_id])) {
            return new WP_Error('not_found', 'Módulo no encontrado', ['status' => 404]);
        }

        $this->save($module_id, $config);

        return rest_ensure_response([
            'module_id' => $module_id,
            'config' => $this->get($module_id),
            'message' => 'Configuración guardada',
        ]);
    }

    /**
     * REST: Obtener schema completo
     */
    public function rest_get_schema($request) {
        return rest_ensure_response($this->config_schema);
    }

    /**
     * Obtener schema
     */
    public function get_schema() {
        return $this->config_schema;
    }
}

/**
 * Helper global
 */
function flavor_module_config($module_id, $key = null) {
    return Flavor_Module_Config::get_instance()->get($module_id, $key);
}
