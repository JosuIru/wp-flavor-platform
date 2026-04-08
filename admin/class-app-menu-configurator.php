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
                'label'    => __( 'Inicio', 'flavor-chat-ia' ),
                'icon'     => 'home',
                'route'    => '/',
                'category' => 'core',
            ),
            'profile' => array(
                'id'       => 'profile',
                'label'    => __( 'Perfil', 'flavor-chat-ia' ),
                'icon'     => 'person',
                'route'    => '/profile',
                'category' => 'core',
            ),
            'settings' => array(
                'id'       => 'settings',
                'label'    => __( 'Ajustes', 'flavor-chat-ia' ),
                'icon'     => 'settings',
                'route'    => '/settings',
                'category' => 'core',
            ),
            'notifications' => array(
                'id'       => 'notifications',
                'label'    => __( 'Notificaciones', 'flavor-chat-ia' ),
                'icon'     => 'notifications',
                'route'    => '/notifications',
                'category' => 'core',
                'badge'    => true,
            ),
            'search' => array(
                'id'       => 'search',
                'label'    => __( 'Buscar', 'flavor-chat-ia' ),
                'icon'     => 'search',
                'route'    => '/search',
                'category' => 'core',
            ),
            'info' => array(
                'id'       => 'info',
                'label'    => __( 'Información', 'flavor-chat-ia' ),
                'icon'     => 'info',
                'route'    => '/info',
                'category' => 'core',
            ),

            // Comunidad
            'eventos' => array(
                'id'       => 'eventos',
                'label'    => __( 'Eventos', 'flavor-chat-ia' ),
                'icon'     => 'event',
                'route'    => '/eventos',
                'category' => 'comunidad',
                'module'   => 'eventos',
            ),
            'foros' => array(
                'id'       => 'foros',
                'label'    => __( 'Foros', 'flavor-chat-ia' ),
                'icon'     => 'forum',
                'route'    => '/foros',
                'category' => 'comunidad',
                'module'   => 'foros',
            ),
            'socios' => array(
                'id'       => 'socios',
                'label'    => __( 'Socios', 'flavor-chat-ia' ),
                'icon'     => 'group',
                'route'    => '/socios',
                'category' => 'comunidad',
                'module'   => 'socios',
            ),
            'comunidades' => array(
                'id'       => 'comunidades',
                'label'    => __( 'Comunidades', 'flavor-chat-ia' ),
                'icon'     => 'people',
                'route'    => '/comunidades',
                'category' => 'comunidad',
                'module'   => 'comunidades',
            ),

            // Economía
            'marketplace' => array(
                'id'       => 'marketplace',
                'label'    => __( 'Tienda', 'flavor-chat-ia' ),
                'icon'     => 'shopping_bag',
                'route'    => '/marketplace',
                'category' => 'economia',
                'module'   => 'marketplace',
            ),
            'grupos_consumo' => array(
                'id'       => 'grupos_consumo',
                'label'    => __( 'Grupos Consumo', 'flavor-chat-ia' ),
                'icon'     => 'storefront',
                'route'    => '/grupos-consumo',
                'category' => 'economia',
                'module'   => 'grupos-consumo',
            ),
            'banco_tiempo' => array(
                'id'       => 'banco_tiempo',
                'label'    => __( 'Banco Tiempo', 'flavor-chat-ia' ),
                'icon'     => 'schedule',
                'route'    => '/banco-tiempo',
                'category' => 'economia',
                'module'   => 'banco-tiempo',
            ),

            // Reservas
            'reservas' => array(
                'id'       => 'reservas',
                'label'    => __( 'Reservas', 'flavor-chat-ia' ),
                'icon'     => 'calendar_today',
                'route'    => '/reservas',
                'category' => 'reservas',
                'module'   => 'reservas',
            ),
            'espacios' => array(
                'id'       => 'espacios',
                'label'    => __( 'Espacios', 'flavor-chat-ia' ),
                'icon'     => 'meeting_room',
                'route'    => '/espacios',
                'category' => 'reservas',
                'module'   => 'espacios-comunes',
            ),
            'bicicletas' => array(
                'id'       => 'bicicletas',
                'label'    => __( 'Bicicletas', 'flavor-chat-ia' ),
                'icon'     => 'directions_bike',
                'route'    => '/bicicletas',
                'category' => 'reservas',
                'module'   => 'bicicletas-compartidas',
            ),
            'parkings' => array(
                'id'       => 'parkings',
                'label'    => __( 'Parkings', 'flavor-chat-ia' ),
                'icon'     => 'local_parking',
                'route'    => '/parkings',
                'category' => 'reservas',
                'module'   => 'parkings',
            ),

            // Formación
            'cursos' => array(
                'id'       => 'cursos',
                'label'    => __( 'Cursos', 'flavor-chat-ia' ),
                'icon'     => 'school',
                'route'    => '/cursos',
                'category' => 'formacion',
                'module'   => 'cursos',
            ),
            'talleres' => array(
                'id'       => 'talleres',
                'label'    => __( 'Talleres', 'flavor-chat-ia' ),
                'icon'     => 'construction',
                'route'    => '/talleres',
                'category' => 'formacion',
                'module'   => 'talleres',
            ),
            'biblioteca' => array(
                'id'       => 'biblioteca',
                'label'    => __( 'Biblioteca', 'flavor-chat-ia' ),
                'icon'     => 'local_library',
                'route'    => '/biblioteca',
                'category' => 'formacion',
                'module'   => 'biblioteca',
            ),

            // Participación
            'encuestas' => array(
                'id'       => 'encuestas',
                'label'    => __( 'Encuestas', 'flavor-chat-ia' ),
                'icon'     => 'poll',
                'route'    => '/encuestas',
                'category' => 'participacion',
                'module'   => 'encuestas',
            ),
            'presupuestos' => array(
                'id'       => 'presupuestos',
                'label'    => __( 'Presupuestos', 'flavor-chat-ia' ),
                'icon'     => 'account_balance',
                'route'    => '/presupuestos-participativos',
                'category' => 'participacion',
                'module'   => 'presupuestos-participativos',
            ),
            'campanias' => array(
                'id'       => 'campanias',
                'label'    => __( 'Campañas', 'flavor-chat-ia' ),
                'icon'     => 'campaign',
                'route'    => '/campanias',
                'category' => 'participacion',
                'module'   => 'campanias',
            ),

            // Social
            'red_social' => array(
                'id'       => 'red_social',
                'label'    => __( 'Red Social', 'flavor-chat-ia' ),
                'icon'     => 'public',
                'route'    => '/red-social',
                'category' => 'social',
                'module'   => 'red-social',
            ),
            'chat' => array(
                'id'       => 'chat',
                'label'    => __( 'Chat', 'flavor-chat-ia' ),
                'icon'     => 'chat',
                'route'    => '/chat',
                'category' => 'social',
                'module'   => 'chat-interno',
                'badge'    => true,
            ),

            // Movilidad
            'carpooling' => array(
                'id'       => 'carpooling',
                'label'    => __( 'Carpooling', 'flavor-chat-ia' ),
                'icon'     => 'directions_car',
                'route'    => '/carpooling',
                'category' => 'movilidad',
                'module'   => 'carpooling',
            ),

            // Gestión
            'incidencias' => array(
                'id'       => 'incidencias',
                'label'    => __( 'Incidencias', 'flavor-chat-ia' ),
                'icon'     => 'report_problem',
                'route'    => '/incidencias',
                'category' => 'gestion',
                'module'   => 'incidencias',
            ),
            'tramites' => array(
                'id'       => 'tramites',
                'label'    => __( 'Trámites', 'flavor-chat-ia' ),
                'icon'     => 'description',
                'route'    => '/tramites',
                'category' => 'gestion',
                'module'   => 'tramites',
            ),
            'transparencia' => array(
                'id'       => 'transparencia',
                'label'    => __( 'Transparencia', 'flavor-chat-ia' ),
                'icon'     => 'visibility',
                'route'    => '/transparencia',
                'category' => 'gestion',
                'module'   => 'transparencia',
            ),

            // Cultura
            'multimedia' => array(
                'id'       => 'multimedia',
                'label'    => __( 'Multimedia', 'flavor-chat-ia' ),
                'icon'     => 'perm_media',
                'route'    => '/multimedia',
                'category' => 'cultura',
                'module'   => 'multimedia',
            ),
            'radio' => array(
                'id'       => 'radio',
                'label'    => __( 'Radio', 'flavor-chat-ia' ),
                'icon'     => 'radio',
                'route'    => '/radio',
                'category' => 'cultura',
                'module'   => 'radio',
            ),
            'podcast' => array(
                'id'       => 'podcast',
                'label'    => __( 'Podcast', 'flavor-chat-ia' ),
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
            __( 'Menú App', 'flavor-chat-ia' ),
            __( 'Menú App', 'flavor-chat-ia' ),
            'manage_options',
            'flavor-app-menu',
            array( $this, 'render_page' )
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets( $hook ) {
        if ( 'flavor_page_flavor-app-menu' !== $hook ) {
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
                'confirm_reset'  => __( '¿Restablecer la navegación a los valores por defecto?', 'flavor-chat-ia' ),
                'saved'          => __( 'Configuración guardada', 'flavor-chat-ia' ),
                'error'          => __( 'Error al guardar', 'flavor-chat-ia' ),
                'max_tabs'       => __( 'Máximo 5 elementos en la barra inferior', 'flavor-chat-ia' ),
                'min_tabs'       => __( 'La barra inferior necesita al menos 2 elementos', 'flavor-chat-ia' ),
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
            __( 'Autenticación de app requerida.', 'flavor-chat-ia' ),
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
            'core'         => __( 'Principal', 'flavor-chat-ia' ),
            'comunidad'    => __( 'Comunidad', 'flavor-chat-ia' ),
            'economia'     => __( 'Economía', 'flavor-chat-ia' ),
            'reservas'     => __( 'Reservas', 'flavor-chat-ia' ),
            'formacion'    => __( 'Formación', 'flavor-chat-ia' ),
            'participacion' => __( 'Participación', 'flavor-chat-ia' ),
            'social'       => __( 'Social', 'flavor-chat-ia' ),
            'movilidad'    => __( 'Movilidad', 'flavor-chat-ia' ),
            'gestion'      => __( 'Gestión', 'flavor-chat-ia' ),
            'cultura'      => __( 'Cultura', 'flavor-chat-ia' ),
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
                    <?php esc_html_e( 'Configurador de Menú de la App', 'flavor-chat-ia' ); ?>
                </h1>
                <div class="header-actions">
                    <button type="button" class="button" id="reset-navigation">
                        <span class="dashicons dashicons-image-rotate"></span>
                        <?php esc_html_e( 'Restablecer', 'flavor-chat-ia' ); ?>
                    </button>
                    <button type="button" class="button button-primary" id="save-navigation">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e( 'Guardar Cambios', 'flavor-chat-ia' ); ?>
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
                                    <p><?php esc_html_e( 'Vista previa de la app', 'flavor-chat-ia' ); ?></p>
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
                        <h3><?php esc_html_e( 'Estilo de Navegación', 'flavor-chat-ia' ); ?></h3>
                        <div class="style-options">
                            <label class="style-option <?php echo $config['style'] === 'bottom_tabs' ? 'selected' : ''; ?>">
                                <input type="radio" name="nav_style" value="bottom_tabs" <?php checked( $config['style'], 'bottom_tabs' ); ?>>
                                <span class="style-icon">
                                    <span class="dashicons dashicons-menu-alt3"></span>
                                </span>
                                <span class="style-label"><?php esc_html_e( 'Barra Inferior', 'flavor-chat-ia' ); ?></span>
                            </label>
                            <label class="style-option <?php echo $config['style'] === 'drawer' ? 'selected' : ''; ?>">
                                <input type="radio" name="nav_style" value="drawer" <?php checked( $config['style'], 'drawer' ); ?>>
                                <span class="style-icon">
                                    <span class="dashicons dashicons-menu"></span>
                                </span>
                                <span class="style-label"><?php esc_html_e( 'Menú Lateral', 'flavor-chat-ia' ); ?></span>
                            </label>
                            <label class="style-option <?php echo $config['style'] === 'both' ? 'selected' : ''; ?>">
                                <input type="radio" name="nav_style" value="both" <?php checked( $config['style'], 'both' ); ?>>
                                <span class="style-icon">
                                    <span class="dashicons dashicons-layout"></span>
                                </span>
                                <span class="style-label"><?php esc_html_e( 'Ambos', 'flavor-chat-ia' ); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Bottom Tabs -->
                    <div class="config-section" id="bottom-tabs-section">
                        <h3>
                            <?php esc_html_e( 'Barra Inferior', 'flavor-chat-ia' ); ?>
                            <span class="tab-count">(<span id="tab-count">0</span>/5)</span>
                        </h3>
                        <p class="section-desc"><?php esc_html_e( 'Arrastra elementos aquí (máximo 5)', 'flavor-chat-ia' ); ?></p>
                        <div class="sortable-zone" id="bottom-tabs-zone" data-max="5">
                            <!-- Items rendered by JS -->
                        </div>
                    </div>

                    <!-- Drawer Items -->
                    <div class="config-section" id="drawer-section">
                        <h3><?php esc_html_e( 'Menú Lateral', 'flavor-chat-ia' ); ?></h3>
                        <p class="section-desc"><?php esc_html_e( 'Arrastra elementos aquí', 'flavor-chat-ia' ); ?></p>
                        <div class="sortable-zone" id="drawer-zone">
                            <!-- Items rendered by JS -->
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="config-section">
                        <h3><?php esc_html_e( 'Opciones', 'flavor-chat-ia' ); ?></h3>
                        <label class="option-toggle">
                            <input type="checkbox" id="show-labels" <?php checked( $config['show_labels'] ); ?>>
                            <span><?php esc_html_e( 'Mostrar etiquetas en barra inferior', 'flavor-chat-ia' ); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Available Items -->
                <div class="available-items-panel">
                    <h3><?php esc_html_e( 'Elementos Disponibles', 'flavor-chat-ia' ); ?></h3>
                    <div class="items-search">
                        <input type="search" id="search-items" placeholder="<?php esc_attr_e( 'Buscar...', 'flavor-chat-ia' ); ?>">
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
                        <h3><?php esc_html_e( 'Editar Elemento', 'flavor-chat-ia' ); ?></h3>
                        <button type="button" class="item-modal-close">&times;</button>
                    </div>
                    <div class="item-modal-body">
                        <input type="hidden" id="edit-item-id">
                        <div class="form-group">
                            <label for="edit-label"><?php esc_html_e( 'Etiqueta', 'flavor-chat-ia' ); ?></label>
                            <input type="text" id="edit-label" maxlength="12">
                        </div>
                        <div class="form-group">
                            <label for="edit-icon"><?php esc_html_e( 'Icono', 'flavor-chat-ia' ); ?></label>
                            <div class="icon-selector" id="icon-selector">
                                <!-- Icons rendered by JS -->
                            </div>
                            <input type="hidden" id="edit-icon">
                        </div>
                        <div class="form-group">
                            <label for="edit-route"><?php esc_html_e( 'Ruta', 'flavor-chat-ia' ); ?></label>
                            <input type="text" id="edit-route" placeholder="/ruta">
                        </div>
                    </div>
                    <div class="item-modal-footer">
                        <button type="button" class="button btn-cancel"><?php esc_html_e( 'Cancelar', 'flavor-chat-ia' ); ?></button>
                        <button type="button" class="button button-primary" id="save-item-btn">
                            <?php esc_html_e( 'Guardar', 'flavor-chat-ia' ); ?>
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
