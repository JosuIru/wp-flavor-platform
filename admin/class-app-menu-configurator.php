<?php
/**
 * Configurador de Menús de la App
 *
 * Interfaz drag-drop para configurar la navegación
 * de la aplicación móvil (tabs, drawer, etc.)
 *
 * @package Flavor_Chat_IA
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_App_Menu_Configurator {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Opción de configuración
     */
    const OPTION_KEY = 'flavor_app_navigation';

    /**
     * Items disponibles para navegación
     */
    private $available_items = array();

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 26 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_flavor_save_app_navigation', array( $this, 'ajax_save_navigation' ) );
        add_action( 'wp_ajax_flavor_get_available_items', array( $this, 'ajax_get_available_items' ) );
        add_action( 'wp_ajax_flavor_reset_app_navigation', array( $this, 'ajax_reset_navigation' ) );

        // REST API
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Initialize available items
        $this->init_available_items();
    }

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializar items disponibles
     */
    private function init_available_items() {
        $this->available_items = array(
            // Core
            'home' => array(
                'id'       => 'home',
                'label'    => __( 'Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'home',
                'route'    => '/',
                'category' => 'core',
            ),
            'profile' => array(
                'id'       => 'profile',
                'label'    => __( 'Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'person',
                'route'    => '/profile',
                'category' => 'core',
            ),
            'settings' => array(
                'id'       => 'settings',
                'label'    => __( 'Ajustes', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'settings',
                'route'    => '/settings',
                'category' => 'core',
            ),
            'notifications' => array(
                'id'       => 'notifications',
                'label'    => __( 'Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'notifications',
                'route'    => '/notifications',
                'category' => 'core',
                'badge'    => true,
            ),
            'search' => array(
                'id'       => 'search',
                'label'    => __( 'Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'search',
                'route'    => '/search',
                'category' => 'core',
            ),
            'info' => array(
                'id'       => 'info',
                'label'    => __( 'Información', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'info',
                'route'    => '/info',
                'category' => 'core',
            ),

            // Comunidad
            'eventos' => array(
                'id'       => 'eventos',
                'label'    => __( 'Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'event',
                'route'    => '/eventos',
                'category' => 'comunidad',
                'module'   => 'eventos',
            ),
            'foros' => array(
                'id'       => 'foros',
                'label'    => __( 'Foros', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'forum',
                'route'    => '/foros',
                'category' => 'comunidad',
                'module'   => 'foros',
            ),
            'socios' => array(
                'id'       => 'socios',
                'label'    => __( 'Socios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'group',
                'route'    => '/socios',
                'category' => 'comunidad',
                'module'   => 'socios',
            ),
            'comunidades' => array(
                'id'       => 'comunidades',
                'label'    => __( 'Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'people',
                'route'    => '/comunidades',
                'category' => 'comunidad',
                'module'   => 'comunidades',
            ),

            // Economía
            'marketplace' => array(
                'id'       => 'marketplace',
                'label'    => __( 'Tienda', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'shopping_bag',
                'route'    => '/marketplace',
                'category' => 'economia',
                'module'   => 'marketplace',
            ),
            'grupos_consumo' => array(
                'id'       => 'grupos_consumo',
                'label'    => __( 'Grupos Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'storefront',
                'route'    => '/grupos-consumo',
                'category' => 'economia',
                'module'   => 'grupos-consumo',
            ),
            'banco_tiempo' => array(
                'id'       => 'banco_tiempo',
                'label'    => __( 'Banco Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'schedule',
                'route'    => '/banco-tiempo',
                'category' => 'economia',
                'module'   => 'banco-tiempo',
            ),

            // Reservas
            'reservas' => array(
                'id'       => 'reservas',
                'label'    => __( 'Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'calendar_today',
                'route'    => '/reservas',
                'category' => 'reservas',
                'module'   => 'reservas',
            ),
            'espacios' => array(
                'id'       => 'espacios',
                'label'    => __( 'Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'meeting_room',
                'route'    => '/espacios',
                'category' => 'reservas',
                'module'   => 'espacios-comunes',
            ),
            'bicicletas' => array(
                'id'       => 'bicicletas',
                'label'    => __( 'Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'directions_bike',
                'route'    => '/bicicletas',
                'category' => 'reservas',
                'module'   => 'bicicletas-compartidas',
            ),
            'parkings' => array(
                'id'       => 'parkings',
                'label'    => __( 'Parkings', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'local_parking',
                'route'    => '/parkings',
                'category' => 'reservas',
                'module'   => 'parkings',
            ),

            // Formación
            'cursos' => array(
                'id'       => 'cursos',
                'label'    => __( 'Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'school',
                'route'    => '/cursos',
                'category' => 'formacion',
                'module'   => 'cursos',
            ),
            'talleres' => array(
                'id'       => 'talleres',
                'label'    => __( 'Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'construction',
                'route'    => '/talleres',
                'category' => 'formacion',
                'module'   => 'talleres',
            ),
            'biblioteca' => array(
                'id'       => 'biblioteca',
                'label'    => __( 'Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'local_library',
                'route'    => '/biblioteca',
                'category' => 'formacion',
                'module'   => 'biblioteca',
            ),

            // Participación
            'encuestas' => array(
                'id'       => 'encuestas',
                'label'    => __( 'Encuestas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'poll',
                'route'    => '/encuestas',
                'category' => 'participacion',
                'module'   => 'encuestas',
            ),
            'presupuestos' => array(
                'id'       => 'presupuestos',
                'label'    => __( 'Presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'account_balance',
                'route'    => '/presupuestos-participativos',
                'category' => 'participacion',
                'module'   => 'presupuestos-participativos',
            ),
            'campanias' => array(
                'id'       => 'campanias',
                'label'    => __( 'Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'campaign',
                'route'    => '/campanias',
                'category' => 'participacion',
                'module'   => 'campanias',
            ),

            // Social
            'red_social' => array(
                'id'       => 'red_social',
                'label'    => __( 'Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'public',
                'route'    => '/red-social',
                'category' => 'social',
                'module'   => 'red-social',
            ),
            'chat' => array(
                'id'       => 'chat',
                'label'    => __( 'Chat', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'chat',
                'route'    => '/chat',
                'category' => 'social',
                'module'   => 'chat-interno',
                'badge'    => true,
            ),

            // Movilidad
            'carpooling' => array(
                'id'       => 'carpooling',
                'label'    => __( 'Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'directions_car',
                'route'    => '/carpooling',
                'category' => 'movilidad',
                'module'   => 'carpooling',
            ),

            // Gestión
            'incidencias' => array(
                'id'       => 'incidencias',
                'label'    => __( 'Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'report_problem',
                'route'    => '/incidencias',
                'category' => 'gestion',
                'module'   => 'incidencias',
            ),
            'tramites' => array(
                'id'       => 'tramites',
                'label'    => __( 'Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'description',
                'route'    => '/tramites',
                'category' => 'gestion',
                'module'   => 'tramites',
            ),
            'transparencia' => array(
                'id'       => 'transparencia',
                'label'    => __( 'Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'visibility',
                'route'    => '/transparencia',
                'category' => 'gestion',
                'module'   => 'transparencia',
            ),

            // Cultura
            'multimedia' => array(
                'id'       => 'multimedia',
                'label'    => __( 'Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'perm_media',
                'route'    => '/multimedia',
                'category' => 'cultura',
                'module'   => 'multimedia',
            ),
            'radio' => array(
                'id'       => 'radio',
                'label'    => __( 'Radio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'radio',
                'route'    => '/radio',
                'category' => 'cultura',
                'module'   => 'radio',
            ),
            'podcast' => array(
                'id'       => 'podcast',
                'label'    => __( 'Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'icon'     => 'podcasts',
                'route'    => '/podcast',
                'category' => 'cultura',
                'module'   => 'podcast',
            ),
        );

        // Allow filtering
        $this->available_items = apply_filters( 'flavor_app_navigation_items', $this->available_items );
    }

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-dashboard',
            __( 'Menú App', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            __( 'Menú App', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'manage_options',
            'flavor-platform-app-menu',
            array( $this, 'render_page' )
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets( $hook ) {
        if ( 'flavor_page_flavor-platform-app-menu' !== $hook && 'flavor_page_flavor-app-menu' !== $hook ) {
            return;
        }

        // Sortable
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'jquery-ui-droppable' );

        wp_enqueue_style(
            'flavor-app-menu',
            FLAVOR_CHAT_IA_URL . 'admin/css/app-menu-configurator.css',
            array(),
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-app-menu',
            FLAVOR_CHAT_IA_URL . 'admin/js/app-menu-configurator.js',
            array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-droppable', 'wp-util' ),
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script( 'flavor-app-menu', 'flavorAppMenu', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'flavor_app_menu' ),
            'currentConfig'  => $this->get_navigation_config(),
            'availableItems' => $this->get_filtered_available_items(),
            'categories'     => $this->get_categories(),
            'i18n'           => array(
                'confirm_reset'  => __( '¿Restablecer la navegación a los valores por defecto?', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'saved'          => __( 'Configuración guardada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'error'          => __( 'Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'max_tabs'       => __( 'Máximo 5 elementos en la barra inferior', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'min_tabs'       => __( 'La barra inferior necesita al menos 2 elementos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
        ) );
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route( 'flavor-app/v2', '/navigation', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_navigation' ),
            'permission_callback' => array( $this, 'check_app_read_access' ),
        ) );
    }

    /**
     * Verificar acceso de lectura para apps y admins.
     *
     * Acepta sesión WordPress, token Bearer móvil válido,
     * token de app registrado o secreto admin del sitio.
     *
     * @param WP_REST_Request $request Request actual.
     * @return bool|WP_Error
     */
    public function check_app_read_access( $request ) {
        if ( is_user_logged_in() ) {
            return true;
        }

        if ( class_exists( 'Chat_IA_Mobile_API' ) ) {
            $mobile_api = Chat_IA_Mobile_API::get_instance();
            if ( $mobile_api && $mobile_api->check_auth_token( $request ) ) {
                return true;
            }
        }

        $app_token = $request->get_header( 'X-Flavor-Token' );
        if ( is_string( $app_token ) && '' !== $app_token ) {
            $valid_tokens = get_option( 'flavor_apps_tokens', array() );
            foreach ( $valid_tokens as $token_data ) {
                if ( isset( $token_data['token'] ) && hash_equals( (string) $token_data['token'], $app_token ) ) {
                    return true;
                }
            }

            if ( class_exists( 'Flavor_App_Config_Admin' ) ) {
                $admin_secret = Flavor_App_Config_Admin::get_admin_site_secret();
                if ( is_string( $admin_secret ) && '' !== $admin_secret && hash_equals( $admin_secret, $app_token ) ) {
                    return true;
                }
            }
        }

        return new WP_Error(
            'rest_forbidden',
            __( 'Autenticación de app requerida.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            array( 'status' => 401 )
        );
    }

    /**
     * REST: Obtener configuración de navegación
     */
    public function rest_get_navigation( $request ) {
        $config = $this->get_navigation_config();

        // Filter items based on active modules
        $active_modules = get_option( 'flavor_active_modules', array() );

        $filter_items = function( $items ) use ( $active_modules ) {
            return array_values( array_filter( $items, function( $item ) use ( $active_modules ) {
                if ( empty( $item['module'] ) ) {
                    return true; // Core items always shown
                }
                return in_array( $item['module'], $active_modules, true );
            } ) );
        };

        $config['bottom_tabs'] = $filter_items( $config['bottom_tabs'] );
        $config['drawer_items'] = $filter_items( $config['drawer_items'] );

        return rest_ensure_response( $config );
    }

    /**
     * Obtener categorías
     */
    private function get_categories() {
        return array(
            'core'         => __( 'Principal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'comunidad'    => __( 'Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'economia'     => __( 'Economía', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'reservas'     => __( 'Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'formacion'    => __( 'Formación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'participacion' => __( 'Participación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'social'       => __( 'Social', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'movilidad'    => __( 'Movilidad', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'gestion'      => __( 'Gestión', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'cultura'      => __( 'Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN ),
        );
    }

    /**
     * Obtener items filtrados por módulos activos
     */
    private function get_filtered_available_items() {
        $active_modules = get_option( 'flavor_active_modules', array() );
        $items = array();

        foreach ( $this->available_items as $id => $item ) {
            // Include core items and items with active modules
            if ( empty( $item['module'] ) || in_array( $item['module'], $active_modules, true ) ) {
                $items[ $id ] = $item;
            }
        }

        return $items;
    }

    /**
     * Obtener configuración de navegación
     */
    public function get_navigation_config() {
        $default = array(
            'style'        => 'bottom_tabs', // bottom_tabs, drawer, both
            'bottom_tabs'  => array(
                array( 'id' => 'home', 'label' => 'Inicio', 'icon' => 'home', 'route' => '/' ),
                array( 'id' => 'eventos', 'label' => 'Eventos', 'icon' => 'event', 'route' => '/eventos' ),
                array( 'id' => 'marketplace', 'label' => 'Tienda', 'icon' => 'shopping_bag', 'route' => '/marketplace' ),
                array( 'id' => 'comunidades', 'label' => 'Comunidad', 'icon' => 'people', 'route' => '/comunidades' ),
                array( 'id' => 'profile', 'label' => 'Perfil', 'icon' => 'person', 'route' => '/profile' ),
            ),
            'drawer_items' => array(
                array( 'id' => 'home', 'label' => 'Inicio', 'icon' => 'home', 'route' => '/' ),
                array( 'id' => 'notifications', 'label' => 'Notificaciones', 'icon' => 'notifications', 'route' => '/notifications' ),
                array( 'id' => 'settings', 'label' => 'Ajustes', 'icon' => 'settings', 'route' => '/settings' ),
                array( 'id' => 'info', 'label' => 'Información', 'icon' => 'info', 'route' => '/info' ),
            ),
            'fab_action'   => null, // Floating action button
            'show_labels'  => true,
        );

        $saved = get_option( self::OPTION_KEY, array() );

        return wp_parse_args( $saved, $default );
    }

    /**
     * Renderizar página
     */
    public function render_page() {
        $config = $this->get_navigation_config();
        $categories = $this->get_categories();
        ?>
        <div class="wrap flavor-app-menu-configurator">
            <div class="page-header">
                <h1>
                    <span class="dashicons dashicons-menu"></span>
                    <?php esc_html_e( 'Configurador de Menú de la App', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                </h1>
                <div class="header-actions">
                    <button type="button" class="button" id="reset-navigation">
                        <span class="dashicons dashicons-image-rotate"></span>
                        <?php esc_html_e( 'Restablecer', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                    <button type="button" class="button button-primary" id="save-navigation">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e( 'Guardar Cambios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>

            <div class="configurator-layout">
                <!-- Phone Preview -->
                <div class="phone-preview-container">
                    <div class="phone-frame">
                        <div class="phone-screen">
                            <div class="phone-status-bar">
                                <span class="time">9:41</span>
                                <span class="icons">
                                    <span class="signal"></span>
                                    <span class="wifi"></span>
                                    <span class="battery"></span>
                                </span>
                            </div>

                            <div class="phone-content">
                                <div class="preview-placeholder">
                                    <span class="dashicons dashicons-smartphone"></span>
                                    <p><?php esc_html_e( 'Vista previa de la app', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                                </div>
                            </div>

                            <div class="phone-bottom-tabs" id="preview-tabs">
                                <!-- Tabs rendered by JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration Panel -->
                <div class="config-panel">
                    <!-- Navigation Style -->
                    <div class="config-section">
                        <h3><?php esc_html_e( 'Estilo de Navegación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
                        <div class="style-options">
                            <label class="style-option <?php echo $config['style'] === 'bottom_tabs' ? 'selected' : ''; ?>">
                                <input type="radio" name="nav_style" value="bottom_tabs" <?php checked( $config['style'], 'bottom_tabs' ); ?>>
                                <span class="style-icon">
                                    <span class="dashicons dashicons-menu-alt3"></span>
                                </span>
                                <span class="style-label"><?php esc_html_e( 'Barra Inferior', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </label>
                            <label class="style-option <?php echo $config['style'] === 'drawer' ? 'selected' : ''; ?>">
                                <input type="radio" name="nav_style" value="drawer" <?php checked( $config['style'], 'drawer' ); ?>>
                                <span class="style-icon">
                                    <span class="dashicons dashicons-menu"></span>
                                </span>
                                <span class="style-label"><?php esc_html_e( 'Menú Lateral', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </label>
                            <label class="style-option <?php echo $config['style'] === 'both' ? 'selected' : ''; ?>">
                                <input type="radio" name="nav_style" value="both" <?php checked( $config['style'], 'both' ); ?>>
                                <span class="style-icon">
                                    <span class="dashicons dashicons-layout"></span>
                                </span>
                                <span class="style-label"><?php esc_html_e( 'Ambos', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Bottom Tabs -->
                    <div class="config-section" id="bottom-tabs-section">
                        <h3>
                            <?php esc_html_e( 'Barra Inferior', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            <span class="tab-count">(<span id="tab-count">0</span>/5)</span>
                        </h3>
                        <p class="section-desc"><?php esc_html_e( 'Arrastra elementos aquí (máximo 5)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                        <div class="sortable-zone" id="bottom-tabs-zone" data-max="5">
                            <!-- Items rendered by JS -->
                        </div>
                    </div>

                    <!-- Drawer Items -->
                    <div class="config-section" id="drawer-section">
                        <h3><?php esc_html_e( 'Menú Lateral', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
                        <p class="section-desc"><?php esc_html_e( 'Arrastra elementos aquí', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
                        <div class="sortable-zone" id="drawer-zone">
                            <!-- Items rendered by JS -->
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="config-section">
                        <h3><?php esc_html_e( 'Opciones', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
                        <label class="option-toggle">
                            <input type="checkbox" id="show-labels" <?php checked( $config['show_labels'] ); ?>>
                            <span><?php esc_html_e( 'Mostrar etiquetas en barra inferior', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Available Items -->
                <div class="available-items-panel">
                    <h3><?php esc_html_e( 'Elementos Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
                    <div class="items-search">
                        <input type="search" id="search-items" placeholder="<?php esc_attr_e( 'Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                    </div>

                    <div class="items-categories">
                        <?php foreach ( $categories as $cat_id => $cat_label ) : ?>
                        <div class="category-group" data-category="<?php echo esc_attr( $cat_id ); ?>">
                            <h4 class="category-header">
                                <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
                                <?php echo esc_html( $cat_label ); ?>
                            </h4>
                            <div class="category-items" id="category-<?php echo esc_attr( $cat_id ); ?>">
                                <!-- Items rendered by JS -->
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Item Editor Modal -->
            <div class="item-modal-overlay" id="item-modal">
                <div class="item-modal">
                    <div class="item-modal-header">
                        <h3><?php esc_html_e( 'Editar Elemento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h3>
                        <button type="button" class="item-modal-close">&times;</button>
                    </div>
                    <div class="item-modal-body">
                        <input type="hidden" id="edit-item-id">
                        <div class="form-group">
                            <label for="edit-label"><?php esc_html_e( 'Etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" id="edit-label" maxlength="12">
                        </div>
                        <div class="form-group">
                            <label for="edit-icon"><?php esc_html_e( 'Icono', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <div class="icon-selector" id="icon-selector">
                                <!-- Icons rendered by JS -->
                            </div>
                            <input type="hidden" id="edit-icon">
                        </div>
                        <div class="form-group">
                            <label for="edit-route"><?php esc_html_e( 'Ruta', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
                            <input type="text" id="edit-route" placeholder="/ruta">
                        </div>
                    </div>
                    <div class="item-modal-footer">
                        <button type="button" class="button btn-cancel"><?php esc_html_e( 'Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
                        <button type="button" class="button button-primary" id="save-item-btn">
                            <?php esc_html_e( 'Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Guardar navegación
     */
    public function ajax_save_navigation() {
        check_ajax_referer( 'flavor_app_menu', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Sin permisos' ) );
        }

        $config = array(
            'style'        => sanitize_text_field( $_POST['style'] ?? 'bottom_tabs' ),
            'bottom_tabs'  => $this->sanitize_items( $_POST['bottom_tabs'] ?? array() ),
            'drawer_items' => $this->sanitize_items( $_POST['drawer_items'] ?? array() ),
            'fab_action'   => sanitize_text_field( $_POST['fab_action'] ?? '' ),
            'show_labels'  => ! empty( $_POST['show_labels'] ),
        );

        // Validate
        if ( count( $config['bottom_tabs'] ) > 5 ) {
            wp_send_json_error( array( 'message' => 'Máximo 5 elementos en barra inferior' ) );
        }

        if ( count( $config['bottom_tabs'] ) < 2 && $config['style'] !== 'drawer' ) {
            wp_send_json_error( array( 'message' => 'Mínimo 2 elementos en barra inferior' ) );
        }

        update_option( self::OPTION_KEY, $config );

        wp_send_json_success( array(
            'message' => 'Configuración guardada',
            'config'  => $config,
        ) );
    }

    /**
     * Sanitizar items de navegación
     */
    private function sanitize_items( $items ) {
        if ( ! is_array( $items ) ) {
            $items = json_decode( stripslashes( $items ), true );
        }

        if ( ! is_array( $items ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $items as $item ) {
            if ( ! isset( $item['id'] ) ) {
                continue;
            }

            $sanitized[] = array(
                'id'     => sanitize_key( $item['id'] ),
                'label'  => sanitize_text_field( $item['label'] ?? '' ),
                'icon'   => sanitize_text_field( $item['icon'] ?? 'home' ),
                'route'  => sanitize_text_field( $item['route'] ?? '/' ),
                'badge'  => ! empty( $item['badge'] ),
                'module' => sanitize_text_field( $item['module'] ?? '' ),
            );
        }

        return $sanitized;
    }

    /**
     * AJAX: Obtener items disponibles
     */
    public function ajax_get_available_items() {
        check_ajax_referer( 'flavor_app_menu', 'nonce' );

        wp_send_json_success( array(
            'items'      => $this->get_filtered_available_items(),
            'categories' => $this->get_categories(),
        ) );
    }

    /**
     * AJAX: Restablecer navegación
     */
    public function ajax_reset_navigation() {
        check_ajax_referer( 'flavor_app_menu', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Sin permisos' ) );
        }

        delete_option( self::OPTION_KEY );

        wp_send_json_success( array(
            'message' => 'Navegación restablecida',
            'config'  => $this->get_navigation_config(),
        ) );
    }
}

// Initialize
Flavor_App_Menu_Configurator::get_instance();
