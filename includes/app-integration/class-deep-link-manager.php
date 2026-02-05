<?php
/**
 * Gestor de Deep Links para Apps Móviles
 *
 * Sistema similar a Firebase Dynamic Links para configurar apps
 * con diferentes empresas/organizaciones usando enlaces.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Deep_Link_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Nombre de la tabla de configuraciones de empresas
     */
    const TABLE_NAME = 'flavor_company_configs';

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'flavor-app/v1';

    /**
     * Obtiene la instancia singleton
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
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('admin_menu', [$this, 'add_admin_menu'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Crear tabla de configuraciones al activar el plugin
     */
    public static function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            nombre varchar(255) NOT NULL,
            descripcion text,
            logo_url varchar(500),
            api_base varchar(500) NOT NULL,
            configuracion longtext,
            activo tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY activo (activo)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Registrar rutas de API REST
     */
    public function register_routes() {
        // GET /flavor-app/v1/config/{slug}
        register_rest_route(self::API_NAMESPACE, '/config/(?P<slug>[a-z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_company_config'],
            'permission_callback' => '__return_true', // Público
            'args' => [
                'slug' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return preg_match('/^[a-z0-9-]+$/', $param);
                    }
                ],
            ],
        ]);

        // GET /flavor-app/v1/companies (lista pública de empresas activas)
        register_rest_route(self::API_NAMESPACE, '/companies', [
            'methods' => 'GET',
            'callback' => [$this, 'get_active_companies'],
            'permission_callback' => '__return_true',
        ]);

        // POST /flavor-app/v1/config (crear/actualizar - solo admin)
        register_rest_route(self::API_NAMESPACE, '/config', [
            'methods' => 'POST',
            'callback' => [$this, 'save_company_config'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        // DELETE /flavor-app/v1/config/{slug} (eliminar - solo admin)
        register_rest_route(self::API_NAMESPACE, '/config/(?P<slug>[a-z0-9-]+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_company_config'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        // GET /flavor-app/v1/generate-link/{slug} (generar enlace - solo admin)
        register_rest_route(self::API_NAMESPACE, '/generate-link/(?P<slug>[a-z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'generate_dynamic_link'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * GET /flavor-app/v1/config/{slug}
     * Obtiene la configuración de una empresa por su slug
     */
    public function get_company_config($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $slug = $request['slug'];

        $config = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE slug = %s AND activo = 1",
            $slug
        ));

        if (!$config) {
            return new WP_Error(
                'company_not_found',
                __('Empresa no encontrada', 'flavor-chat-ia'),
                ['status' => 404]
            );
        }

        // Decodificar configuración JSON
        $configuracion_data = json_decode($config->configuracion, true);

        // Construir respuesta en el formato esperado por la app
        $response = [
            'slug' => $config->slug,
            'nombre' => $config->nombre,
            'descripcion' => $config->descripcion,
            'logo' => $config->logo_url,
            'api_base' => $config->api_base,
            'colores' => $configuracion_data['colores'] ?? $this->get_default_colors(),
            'tema' => $configuracion_data['tema'] ?? 'light',
            'idioma' => $configuracion_data['idioma'] ?? 'es',
            'modulos_activos' => $configuracion_data['modulos_activos'] ?? [],
            'config_adicional' => $configuracion_data['adicional'] ?? [],
        ];

        return new WP_REST_Response($response, 200);
    }

    /**
     * GET /flavor-app/v1/companies
     * Lista de empresas activas (para selección en app)
     */
    public function get_active_companies($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $companies = $wpdb->get_results(
            "SELECT slug, nombre, descripcion, logo_url
             FROM {$table_name}
             WHERE activo = 1
             ORDER BY nombre ASC"
        );

        $response = array_map(function($company) {
            return [
                'slug' => $company->slug,
                'nombre' => $company->nombre,
                'descripcion' => $company->descripcion,
                'logo' => $company->logo_url,
            ];
        }, $companies);

        return new WP_REST_Response($response, 200);
    }

    /**
     * POST /flavor-app/v1/config
     * Guarda o actualiza configuración de empresa
     */
    public function save_company_config($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $params = $request->get_json_params();

        // Validaciones
        if (empty($params['slug']) || empty($params['nombre']) || empty($params['api_base'])) {
            return new WP_Error(
                'missing_required_fields',
                __('Faltan campos requeridos: slug, nombre, api_base', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        // Validar formato del slug
        if (!preg_match('/^[a-z0-9-]+$/', $params['slug'])) {
            return new WP_Error(
                'invalid_slug',
                __('El slug solo puede contener letras minúsculas, números y guiones', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        // Preparar datos de configuración
        $configuracion = [
            'colores' => $params['colores'] ?? $this->get_default_colors(),
            'tema' => $params['tema'] ?? 'light',
            'idioma' => $params['idioma'] ?? 'es',
            'modulos_activos' => $params['modulos_activos'] ?? [],
            'adicional' => $params['config_adicional'] ?? [],
        ];

        $data = [
            'slug' => sanitize_title($params['slug']),
            'nombre' => sanitize_text_field($params['nombre']),
            'descripcion' => sanitize_textarea_field($params['descripcion'] ?? ''),
            'logo_url' => esc_url_raw($params['logo_url'] ?? ''),
            'api_base' => esc_url_raw($params['api_base']),
            'configuracion' => wp_json_encode($configuracion),
            'activo' => !empty($params['activo']) ? 1 : 0,
        ];

        // Verificar si existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE slug = %s",
            $data['slug']
        ));

        if ($exists) {
            // Actualizar
            $result = $wpdb->update(
                $table_name,
                $data,
                ['slug' => $data['slug']],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%d'],
                ['%s']
            );

            if ($result === false) {
                return new WP_Error('db_error', __('Error al actualizar configuración', 'flavor-chat-ia'));
            }

            return new WP_REST_Response([
                'success' => true,
                'message' => __('Configuración actualizada correctamente', 'flavor-chat-ia'),
                'slug' => $data['slug'],
            ], 200);
        } else {
            // Insertar
            $result = $wpdb->insert($table_name, $data, ['%s', '%s', '%s', '%s', '%s', '%s', '%d']);

            if ($result === false) {
                return new WP_Error('db_error', __('Error al crear configuración', 'flavor-chat-ia'));
            }

            return new WP_REST_Response([
                'success' => true,
                'message' => __('Configuración creada correctamente', 'flavor-chat-ia'),
                'slug' => $data['slug'],
            ], 201);
        }
    }

    /**
     * DELETE /flavor-app/v1/config/{slug}
     * Elimina una configuración de empresa
     */
    public function delete_company_config($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $slug = $request['slug'];

        $result = $wpdb->delete($table_name, ['slug' => $slug], ['%s']);

        if ($result === false) {
            return new WP_Error('db_error', __('Error al eliminar configuración', 'flavor-chat-ia'));
        }

        if ($result === 0) {
            return new WP_Error('not_found', __('Configuración no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Configuración eliminada correctamente', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * GET /flavor-app/v1/generate-link/{slug}
     * Genera un enlace dinámico para una empresa
     */
    public function generate_dynamic_link($request) {
        $slug = $request['slug'];

        // Verificar que la empresa existe
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE slug = %s AND activo = 1",
            $slug
        ));

        if (!$exists) {
            return new WP_Error('company_not_found', __('Empresa no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        $site_url = get_site_url();
        $base_domain = parse_url($site_url, PHP_URL_HOST);

        // Generar diferentes tipos de enlaces
        $links = [
            // Enlace directo a la API (para configuración inicial)
            'api_config_url' => rest_url(self::API_NAMESPACE . '/config/' . $slug),

            // Enlace corto tipo Firebase Dynamic Links
            'short_link' => $site_url . '/app/' . $slug,

            // Deep link para Android
            'android_deep_link' => 'flavorapp://company/' . $slug,

            // Universal link para iOS
            'ios_universal_link' => 'https://' . $base_domain . '/app/' . $slug,

            // Enlace para Google Play con parámetros
            'google_play_link' => 'https://play.google.com/store/apps/details?id=com.flavor.community&referrer=' . urlencode('company=' . $slug),

            // Enlace para App Store con parámetros
            'app_store_link' => 'https://apps.apple.com/app/id123456789?pt=company&ct=' . $slug,

            // QR code data (URL to config API)
            'qr_data' => rest_url(self::API_NAMESPACE . '/config/' . $slug),
        ];

        return new WP_REST_Response([
            'success' => true,
            'slug' => $slug,
            'links' => $links,
            'instructions' => [
                'android' => 'Usa android_deep_link o google_play_link',
                'ios' => 'Usa ios_universal_link o app_store_link',
                'qr' => 'Genera un código QR con qr_data',
                'direct' => 'Usa api_config_url para configuración directa',
            ],
        ], 200);
    }

    /**
     * Obtiene colores por defecto
     */
    private function get_default_colors() {
        return [
            'primario' => '#3B82F6',
            'secundario' => '#8B5CF6',
            'acento' => '#10B981',
            'fondo' => '#FFFFFF',
            'texto' => '#1F2937',
            'error' => '#EF4444',
            'exito' => '#10B981',
            'advertencia' => '#F59E0B',
        ];
    }

    /**
     * Añade menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-chat-ia',
            __('Deep Links App', 'flavor-chat-ia'),
            __('Deep Links App', 'flavor-chat-ia'),
            'manage_options',
            'flavor-deep-links',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renderiza página de administración
     */
    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Obtener todas las configuraciones
        $companies = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY nombre ASC");

        include dirname(__FILE__) . '/views/deep-links-admin.php';
    }

    /**
     * Encola assets del admin
     */
    public function enqueue_admin_assets($hook) {
        if ('flavor-chat-ia_page_flavor-deep-links' !== $hook) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-deep-links-admin',
            plugins_url("assets/css/deep-links-admin{$sufijo_asset}.css", dirname(dirname(__FILE__))),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'flavor-deep-links-admin',
            plugins_url("assets/js/deep-links-admin{$sufijo_asset}.js", dirname(dirname(__FILE__))),
            ['jquery', 'wp-color-picker'],
            '1.0.0',
            true
        );

        wp_localize_script('flavor-deep-links-admin', 'flavorDeepLinks', [
            'apiUrl' => rest_url(self::API_NAMESPACE),
            'nonce' => wp_create_nonce('wp_rest'),
            'i18n' => [
                'confirmDelete' => __('¿Estás seguro de eliminar esta configuración?', 'flavor-chat-ia'),
                'copied' => __('¡Copiado!', 'flavor-chat-ia'),
                'error' => __('Error', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Obtiene una configuración por slug (uso interno)
     */
    public function get_config_by_slug($slug) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE slug = %s AND activo = 1",
            $slug
        ));
    }
}

// Inicializar
Flavor_Deep_Link_Manager::get_instance();
