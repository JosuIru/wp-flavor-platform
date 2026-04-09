<?php
/**
 * Gestor principal de la Red de Comunidades
 *
 * Singleton que orquesta todo el sistema de red:
 * nodos, conexiones, contenido compartido, colaboraciones,
 * mensajería, mapa, directorio y sellos de calidad.
 *
 * @package FlavorChatIA\Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Versión del sistema de red
     */
    const VERSION = '1.0.0';

    /**
     * Módulos registrados
     */
    private $modulos_registrados = [];

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
     * Constructor privado
     */
    private function __construct() {
        $this->registrar_modulos();
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Verificar/actualizar BD al cargar
        add_action('admin_init', [$this, 'check_db_version']);

        // Shortcodes públicos
        if (!shortcode_exists('flavor_network_directory')) {
            add_shortcode('flavor_network_directory', [$this, 'shortcode_directory']);
        }
        if (!shortcode_exists('flavor_network_map')) {
            add_shortcode('flavor_network_map', [$this, 'shortcode_map']);
        }
        if (!shortcode_exists('flavor_network_board')) {
            add_shortcode('flavor_network_board', [$this, 'shortcode_board']);
        }
        if (!shortcode_exists('flavor_network_events')) {
            add_shortcode('flavor_network_events', [$this, 'shortcode_events']);
        }
        if (!shortcode_exists('flavor_network_alerts')) {
            add_shortcode('flavor_network_alerts', [$this, 'shortcode_alerts']);
        }
        if (!shortcode_exists('flavor_network_catalog')) {
            add_shortcode('flavor_network_catalog', [$this, 'shortcode_catalog']);
        }
        if (!shortcode_exists('flavor_network_collaborations')) {
            add_shortcode('flavor_network_collaborations', [$this, 'shortcode_collaborations']);
        }
        if (!shortcode_exists('flavor_network_time_offers')) {
            add_shortcode('flavor_network_time_offers', [$this, 'shortcode_time_offers']);
        }
        if (!shortcode_exists('flavor_network_node_profile')) {
            add_shortcode('flavor_network_node_profile', [$this, 'shortcode_node_profile']);
        }
        if (!shortcode_exists('flavor_network_questions')) {
            add_shortcode('flavor_network_questions', [$this, 'shortcode_network_questions']);
        }

        // Assets frontend
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_frontend_assets']);
    }

    /**
     * Registra los módulos disponibles de la red
     */
    private function registrar_modulos() {
        $this->modulos_registrados = [
            // Conexión Básica
            'perfil_publico'     => [
                'nombre'      => __('Perfil público en red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Tu ficha visible para otras entidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'conexion',
                'icono'       => 'dashicons-id-alt',
            ],
            'qr_entidad'         => [
                'nombre'      => __('QR de entidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Código para escanear y ver perfil', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'conexion',
                'icono'       => 'dashicons-smartphone',
            ],
            'geolocalizacion'    => [
                'nombre'      => __('Geolocalización', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Aparecer en mapa de la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'conexion',
                'icono'       => 'dashicons-location-alt',
            ],
            'categorizacion'     => [
                'nombre'      => __('Categorización', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Tipo de entidad, sector, tags', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'conexion',
                'icono'       => 'dashicons-tag',
            ],
            'nivel_consciencia'  => [
                'nombre'      => __('Nivel de consciencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Básico / Transición / Consciente / Referente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'conexion',
                'icono'       => 'dashicons-star-filled',
            ],

            // Interconexión
            'conexiones'         => [
                'nombre'      => __('Conexiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Solicitar, gestionar y nivelar conexiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'interconexion',
                'icono'       => 'dashicons-networking',
            ],
            'favoritos'          => [
                'nombre'      => __('Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Marcar entidades de interés', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'interconexion',
                'icono'       => 'dashicons-heart',
            ],
            'recomendaciones'    => [
                'nombre'      => __('Recomendaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Sugerir entidades a otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'interconexion',
                'icono'       => 'dashicons-megaphone',
            ],

            // Compartir Contenido
            'catalogo_publico'   => [
                'nombre'      => __('Catálogo público', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Productos visibles a la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-cart',
            ],
            'servicios_publicos' => [
                'nombre'      => __('Servicios públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Directorio de profesionales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-businessman',
            ],
            'espacios'           => [
                'nombre'      => __('Espacios compartibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Banco de espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-building',
            ],
            'recursos'           => [
                'nombre'      => __('Recursos compartibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Herramientas, vehículos...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-hammer',
            ],
            'eventos'            => [
                'nombre'      => __('Eventos públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Agenda de la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-calendar-alt',
            ],
            'banco_tiempo'       => [
                'nombre'      => __('Ofertas de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Banco de tiempo inter-nodos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-clock',
            ],
            'saberes'            => [
                'nombre'      => __('Saberes públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Formaciones disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-book',
            ],
            'excedentes'         => [
                'nombre'      => __('Excedentes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Economía circular', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-update',
            ],
            'necesidades'        => [
                'nombre'      => __('Necesidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Pedir ayuda a la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'contenido',
                'icono'       => 'dashicons-sos',
            ],

            // Colaboración
            'compras_colectivas' => [
                'nombre'      => __('Compras colectivas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Unir pedidos para mejor precio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-groups',
            ],
            'logistica'          => [
                'nombre'      => __('Logística compartida', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Coordinar transportes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-car',
            ],
            'proyectos'          => [
                'nombre'      => __('Proyectos conjuntos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Colaborar en iniciativas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-lightbulb',
            ],
            'alianzas'           => [
                'nombre'      => __('Alianzas temáticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Grupos por afinidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-admin-links',
            ],
            'hermanamientos'     => [
                'nombre'      => __('Hermanamientos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Vínculo estable con otra entidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-admin-users',
            ],
            'mentoria'           => [
                'nombre'      => __('Mentoría cruzada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Acompañamiento mutuo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'colaboracion',
                'icono'       => 'dashicons-welcome-learn-more',
            ],

            // Comunicación
            'tablon_red'         => [
                'nombre'      => __('Tablón de la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Ver/publicar anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'comunicacion',
                'icono'       => 'dashicons-clipboard',
            ],
            'preguntas_red'      => [
                'nombre'      => __('Preguntas a la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Inteligencia colectiva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'comunicacion',
                'icono'       => 'dashicons-editor-help',
            ],
            'alertas_solidarias' => [
                'nombre'      => __('Alertas solidarias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Necesidades urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'comunicacion',
                'icono'       => 'dashicons-warning',
            ],
            'newsletter_red'     => [
                'nombre'      => __('Newsletter de red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Resumen periódico', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'comunicacion',
                'icono'       => 'dashicons-email-alt',
            ],
            'mensajeria'         => [
                'nombre'      => __('Mensajería inter-nodos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Chat entre entidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'comunicacion',
                'icono'       => 'dashicons-format-chat',
            ],

            // Calidad y Mapa
            'sello_calidad'      => [
                'nombre'      => __('Sello App Consciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Certificación y niveles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'calidad',
                'icono'       => 'dashicons-awards',
            ],
            'mapa_apps'          => [
                'nombre'      => __('Mapa de Apps', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Mapa público con filtros y buscador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'categoria'   => 'calidad',
                'icono'       => 'dashicons-admin-site-alt3',
            ],
        ];
    }

    /**
     * Comprueba y actualiza la versión de BD si es necesario
     */
    public function check_db_version() {
        Flavor_Network_Installer::maybe_upgrade();
    }

    /**
     * Obtiene los módulos registrados
     */
    public function get_modulos() {
        return $this->modulos_registrados;
    }

    /**
     * Obtiene módulos por categoría
     */
    public function get_modulos_por_categoria($categoria) {
        return array_filter($this->modulos_registrados, function($modulo) use ($categoria) {
            return $modulo['categoria'] === $categoria;
        });
    }

    /**
     * Obtiene las categorías de módulos
     */
    public function get_categorias() {
        return [
            'conexion'       => __('Conexión Básica', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'interconexion'  => __('Interconexión', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'contenido'      => __('Compartir Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colaboracion'   => __('Colaboración', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comunicacion'   => __('Comunicación de Red', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'calidad'        => __('Calidad y Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Obtiene los módulos activos del nodo local
     */
    public function get_modulos_activos() {
        $nodo_local = Flavor_Network_Node::get_local_node();
        if (!$nodo_local) {
            return [];
        }
        return $nodo_local->get_modulos_activos();
    }

    /**
     * Comprueba si un módulo está activo
     */
    public function is_modulo_activo($modulo_id) {
        $activos = $this->get_modulos_activos();
        if (empty($activos)) {
            return true;
        }
        return in_array($modulo_id, $activos, true);
    }

    /**
     * Renderiza un aviso cuando el módulo no está activo en la red.
     */
    private function render_modulo_inactivo($modulo_id) {
        $modulo = $this->modulos_registrados[$modulo_id] ?? null;
        $nombre = $modulo['nombre'] ?? $modulo_id;
        return '<div class="notice notice-warning" style="padding:12px;margin:15px 0;">' .
            esc_html(sprintf(__('El módulo "%s" no está activo en tu nodo de red.', FLAVOR_PLATFORM_TEXT_DOMAIN), $nombre)) .
            '</div>';
    }

    // ─── Shortcodes ───

    public function shortcode_directory($atts) {
        if (!$this->is_modulo_activo('perfil_publico')) {
            return $this->render_modulo_inactivo('perfil_publico');
        }
        $atts = shortcode_atts([
            'tipo'   => '',
            'pais'   => '',
            'limite' => 20,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_NETWORK_PATH . 'includes/templates/network-directory.php';
        return ob_get_clean();
    }

    public function shortcode_map($atts) {
        if (!$this->is_modulo_activo('geolocalizacion')) {
            return $this->render_modulo_inactivo('geolocalizacion');
        }
        $atts = shortcode_atts([
            'altura' => '500px',
            'tipo'   => '',
            'zoom'   => 6,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        $this->enqueue_map_assets();
        include FLAVOR_NETWORK_PATH . 'includes/templates/network-map.php';
        return ob_get_clean();
    }

    public function shortcode_board($atts) {
        if (!$this->is_modulo_activo('tablon_red')) {
            return $this->render_modulo_inactivo('tablon_red');
        }
        $atts = shortcode_atts([
            'tipo'   => '',
            'limite' => 15,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_NETWORK_PATH . 'includes/templates/network-board.php';
        return ob_get_clean();
    }

    public function shortcode_events($atts) {
        if (!$this->is_modulo_activo('eventos')) {
            return $this->render_modulo_inactivo('eventos');
        }
        $atts = shortcode_atts([
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_NETWORK_PATH . 'includes/templates/network-events.php';
        return ob_get_clean();
    }

    public function shortcode_alerts($atts) {
        if (!$this->is_modulo_activo('alertas_solidarias')) {
            return $this->render_modulo_inactivo('alertas_solidarias');
        }
        $atts = shortcode_atts([
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_NETWORK_PATH . 'includes/templates/network-alerts.php';
        return ob_get_clean();
    }

    public function shortcode_catalog($atts) {
        if (!$this->is_modulo_activo('catalogo_publico')) {
            return $this->render_modulo_inactivo('catalogo_publico');
        }
        $atts = shortcode_atts([
            'nodo' => '',
            'tipo' => '',
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_NETWORK_PATH . 'includes/templates/network-catalog.php';
        return ob_get_clean();
    }

    public function shortcode_collaborations($atts) {
        if (!$this->is_modulo_activo('proyectos')) {
            return $this->render_modulo_inactivo('proyectos');
        }
        $atts = shortcode_atts([
            'tipo'   => '',
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_NETWORK_PATH . 'includes/templates/network-collaborations.php';
        return ob_get_clean();
    }

    public function shortcode_time_offers($atts) {
        if (!$this->is_modulo_activo('banco_tiempo')) {
            return $this->render_modulo_inactivo('banco_tiempo');
        }
        $atts = shortcode_atts([
            'tipo'   => '',
            'limite' => 10,
        ], $atts);

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_NETWORK_PATH . 'includes/templates/network-time-offers.php';
        return ob_get_clean();
    }

    public function shortcode_node_profile($atts) {
        if (!$this->is_modulo_activo('perfil_publico')) {
            return $this->render_modulo_inactivo('perfil_publico');
        }
        $atts = shortcode_atts([
            'slug' => '',
        ], $atts);

        if (empty($atts['slug']) && isset($_GET['nodo'])) {
            $atts['slug'] = sanitize_text_field($_GET['nodo']);
        }

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_NETWORK_PATH . 'includes/templates/network-node-profile.php';
        return ob_get_clean();
    }

    public function shortcode_network_questions($atts) {
        if (!$this->is_modulo_activo('preguntas_red')) {
            return $this->render_modulo_inactivo('preguntas_red');
        }
        $atts = shortcode_atts([
            'categoria' => '',
            'limite'    => 10,
        ], $atts, 'flavor_network_questions');

        ob_start();
        $this->enqueue_frontend_assets();
        include FLAVOR_NETWORK_PATH . 'includes/templates/network-questions.php';
        return ob_get_clean();
    }

    // ─── Assets ───

    public function maybe_enqueue_frontend_assets() {
        // Solo cargar si hay shortcodes en el contenido
        global $post;
        if (!$post) {
            return;
        }

        $shortcodes_red = ['flavor_network_directory', 'flavor_network_map', 'flavor_network_board',
                           'flavor_network_events', 'flavor_network_alerts', 'flavor_network_catalog',
                           'flavor_network_collaborations', 'flavor_network_time_offers', 'flavor_network_node_profile',
                           'flavor_network_questions'];

        foreach ($shortcodes_red as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                $this->enqueue_frontend_assets();
                if ($shortcode === 'flavor_network_map') {
                    $this->enqueue_map_assets();
                }
                break;
            }
        }
    }

    private function enqueue_frontend_assets() {
        if (wp_style_is('flavor-network-frontend', 'enqueued')) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-network-frontend',
            FLAVOR_NETWORK_URL . "assets/css/network-frontend{$sufijo_asset}.css",
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'flavor-network-frontend',
            FLAVOR_NETWORK_URL . "assets/js/network-frontend{$sufijo_asset}.js",
            ['jquery'],
            self::VERSION,
            true
        );

        wp_localize_script('flavor-network-frontend', 'flavorNetwork', [
            'apiUrl' => rest_url(Flavor_Network_API::API_NAMESPACE),
            'nonce'  => wp_create_nonce('wp_rest'),
            'i18n'   => [
                'cargando'       => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_resultados' => __('No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error'          => __('Error al cargar datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ver_mas'        => __('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'buscar'         => __('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    private function enqueue_map_assets() {
        // Leaflet CSS y JS desde CDN
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_script(
            'flavor-network-map',
            FLAVOR_NETWORK_URL . "assets/js/network-map{$sufijo_asset}.js",
            ['jquery', 'leaflet', 'flavor-network-frontend'],
            self::VERSION,
            true
        );
    }
}
