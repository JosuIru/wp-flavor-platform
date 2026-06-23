<?php
/**
 * Flavor Platform Shortcodes
 *
 * Registra shortcodes para renderizar módulos en el frontend.
 *
 * @package Flavor_Platform
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para manejar shortcodes de Flavor Platform.
 */
class Flavor_Shortcodes {

    /**
     * Instancia singleton.
     *
     * @var Flavor_Shortcodes
     */
    private static $instance = null;

    /**
     * Módulos disponibles con sus vistas.
     *
     * @var array
     */
    private $modules_config = array();

    /**
     * Obtener instancia singleton.
     *
     * @return Flavor_Shortcodes
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->define_modules_config();
        $this->register_shortcodes();
        $this->fix_large_shortcode_regex();
    }

    /**
     * Fix para el error "regular expression is too large" en WordPress.
     *
     * Cuando hay muchos shortcodes registrados (>500), las funciones de WordPress
     * que usan regex con shortcodes (shortcode_unautop, wptexturize, etc.) fallan.
     * Este fix desactiva esos filtros problemáticos.
     */
    private function fix_large_shortcode_regex() {
        // Ejecutar en múltiples hooks para asegurar que se aplique
        add_action( 'init', array( $this, 'maybe_disable_shortcode_regex_filters' ), 999 );
        add_action( 'wp', array( $this, 'maybe_disable_shortcode_regex_filters' ), 1 );
        add_action( 'template_redirect', array( $this, 'maybe_disable_shortcode_regex_filters' ), 1 );
    }

    /**
     * Desactiva filtros que usan regex de shortcodes si hay demasiados registrados.
     */
    public function maybe_disable_shortcode_regex_filters() {
        global $shortcode_tags;

        // El límite de 500 es conservador; el problema aparece ~1000+
        if ( ! is_array( $shortcode_tags ) || count( $shortcode_tags ) <= 500 ) {
            return;
        }

        // Filtros que usan get_shortcode_regex() y causan el error
        $filters_to_remove = array(
            'shortcode_unautop',  // Principal culpable
            'wptexturize',        // También usa regex de shortcodes
        );

        $content_filters = array( 'the_content', 'the_excerpt', 'widget_text_content' );

        foreach ( $filters_to_remove as $filter_func ) {
            foreach ( $content_filters as $content_filter ) {
                remove_filter( $content_filter, $filter_func );
            }
        }

        // Log throttled: el static se reinicia en cada request, así que aquí
        // usamos un transient (1 hora) para no saturar el log en producción.
        // Solo se registra cuando cambia el número de shortcodes.
        if ( function_exists( 'flavor_platform_log' ) ) {
            $cantidad_shortcodes = count( $shortcode_tags );
            $clave_throttle = 'flavor_shortcodes_filtros_desactivados';
            $cantidad_cacheada = get_transient( $clave_throttle );

            if ( $cantidad_cacheada !== (string) $cantidad_shortcodes ) {
                flavor_platform_log(
                    sprintf( 'Desactivados filtros regex: %d shortcodes registrados (límite: 500)', $cantidad_shortcodes ),
                    'debug'
                );
                set_transient( $clave_throttle, (string) $cantidad_shortcodes, HOUR_IN_SECONDS );
            }
        }
    }

    /**
     * Definir configuración de módulos y sus vistas.
     */
    private function define_modules_config() {
        $this->modules_config = array(
            'eventos' => array(
                'title'       => __( 'Eventos', 'flavor-platform' ),
                'icon'        => 'calendar',
                'views'       => array( 'grid', 'list', 'calendar', 'featured' ),
                'default'     => 'grid',
                'post_type'   => 'flavor_evento',
                'description' => __( 'Calendario de eventos de la comunidad', 'flavor-platform' ),
            ),
            'socios' => array(
                'title'       => __( 'Socios', 'flavor-platform' ),
                'icon'        => 'users',
                'views'       => array( 'directory', 'grid', 'list' ),
                'default'     => 'directory',
                'post_type'   => null,
                'description' => __( 'Directorio de miembros de la comunidad', 'flavor-platform' ),
            ),
            'cursos' => array(
                'title'       => __( 'Cursos', 'flavor-platform' ),
                'icon'        => 'graduation-cap',
                'views'       => array( 'catalog', 'grid', 'list' ),
                'default'     => 'catalog',
                'post_type'   => 'flavor_curso',
                'description' => __( 'Catálogo de cursos y formación', 'flavor-platform' ),
            ),
            'foros' => array(
                'title'       => __( 'Foros', 'flavor-platform' ),
                'icon'        => 'comments',
                'views'       => array( 'topics', 'recent', 'popular' ),
                'default'     => 'topics',
                'post_type'   => 'flavor_foro',
                'description' => __( 'Foros de discusión de la comunidad', 'flavor-platform' ),
            ),
            'marketplace' => array(
                'title'       => __( 'Marketplace', 'flavor-platform' ),
                'icon'        => 'shopping-bag',
                'views'       => array( 'grid', 'list', 'featured', 'categories' ),
                'default'     => 'grid',
                'post_type'   => 'flavor_producto',
                'description' => __( 'Tienda de productos locales', 'flavor-platform' ),
            ),
            'grupos-consumo' => array(
                'title'       => __( 'Grupos de Consumo', 'flavor-platform' ),
                'icon'        => 'leaf',
                'views'       => array( 'list', 'grid', 'pedidos' ),
                'default'     => 'list',
                'post_type'   => 'flavor_grupo_consumo',
                'description' => __( 'Grupos de consumo responsable', 'flavor-platform' ),
            ),
            'biblioteca' => array(
                'title'       => __( 'Biblioteca', 'flavor-platform' ),
                'icon'        => 'book',
                'views'       => array( 'catalog', 'grid', 'search' ),
                'default'     => 'catalog',
                'post_type'   => 'flavor_libro',
                'description' => __( 'Biblioteca compartida', 'flavor-platform' ),
            ),
            'reservas' => array(
                'title'       => __( 'Reservas', 'flavor-platform' ),
                'icon'        => 'calendar-check',
                'views'       => array( 'calendar', 'list', 'spaces' ),
                'default'     => 'calendar',
                'post_type'   => 'flavor_reserva',
                'description' => __( 'Sistema de reservas de espacios', 'flavor-platform' ),
            ),
            'banco-tiempo' => array(
                'title'       => __( 'Banco de Tiempo', 'flavor-platform' ),
                'icon'        => 'clock',
                'views'       => array( 'services', 'requests', 'balance' ),
                'default'     => 'services',
                'post_type'   => 'flavor_servicio_bt',
                'description' => __( 'Intercambio de servicios por tiempo', 'flavor-platform' ),
            ),
            'crowdfunding' => array(
                'title'       => __( 'Crowdfunding', 'flavor-platform' ),
                'icon'        => 'hand-holding-heart',
                'views'       => array( 'projects', 'grid', 'featured' ),
                'default'     => 'projects',
                'post_type'   => 'flavor_proyecto_cf',
                'description' => __( 'Proyectos de financiación colectiva', 'flavor-platform' ),
            ),
            'transparencia' => array(
                'title'       => __( 'Transparencia', 'flavor-platform' ),
                'icon'        => 'file-alt',
                'views'       => array( 'documents', 'budgets', 'reports' ),
                'default'     => 'documents',
                'post_type'   => 'flavor_documento',
                'description' => __( 'Portal de transparencia', 'flavor-platform' ),
            ),
            'red-social' => array(
                'title'       => __( 'Red Social', 'flavor-platform' ),
                'icon'        => 'share-alt',
                'views'       => array( 'feed', 'wall', 'activity' ),
                'default'     => 'feed',
                'post_type'   => null,
                'description' => __( 'Red social de la comunidad', 'flavor-platform' ),
            ),
            'encuestas' => array(
                'title'       => __( 'Encuestas', 'flavor-platform' ),
                'icon'        => 'poll',
                'views'       => array( 'active', 'results', 'all' ),
                'default'     => 'active',
                'post_type'   => 'flavor_encuesta',
                'description' => __( 'Encuestas y votaciones', 'flavor-platform' ),
            ),
        );
    }

    /**
     * Registrar todos los shortcodes.
     */
    private function register_shortcodes() {
        // Shortcode principal: [flavor_module]
        add_shortcode( 'flavor_module', array( $this, 'render_module_shortcode' ) );

        // Shortcodes específicos por módulo: [flavor_eventos], [flavor_socios], etc.
        foreach ( array_keys( $this->modules_config ) as $module_id ) {
            $shortcode_name = 'flavor_' . str_replace( '-', '_', $module_id );
            add_shortcode( $shortcode_name, array( $this, 'render_specific_module_shortcode' ) );
        }

        // Shortcode del dashboard unificado
        add_shortcode( 'flavor_unified_dashboard', array( $this, 'render_dashboard_shortcode' ) );

        // Shortcode de login/registro
        add_shortcode( 'flavor_auth', array( $this, 'render_auth_shortcode' ) );
    }

    /**
     * Renderizar shortcode [flavor_module].
     *
     * @param array  $atts    Atributos del shortcode.
     * @param string $content Contenido entre tags (no usado).
     * @return string HTML renderizado.
     */
    public function render_module_shortcode( $atts, $content = null ) {
        $atts = shortcode_atts(
            array(
                'module'  => '',
                'view'    => '',
                'limit'   => 6,
                'columns' => 3,
                'style'   => 'default',
                'class'   => '',
            ),
            $atts,
            'flavor_module'
        );

        $module_id = sanitize_key( $atts['module'] );

        if ( empty( $module_id ) || ! isset( $this->modules_config[ $module_id ] ) ) {
            if ( current_user_can( 'manage_options' ) ) {
                return $this->render_admin_notice(
                    sprintf(
                        /* translators: %s: module ID */
                        __( 'Módulo "%s" no encontrado. Módulos disponibles: %s', 'flavor-platform' ),
                        esc_html( $module_id ),
                        implode( ', ', array_keys( $this->modules_config ) )
                    )
                );
            }
            return '';
        }

        return $this->render_module( $module_id, $atts );
    }

    /**
     * Renderizar shortcode específico de módulo.
     *
     * @param array  $atts    Atributos del shortcode.
     * @param string $content Contenido.
     * @param string $tag     Nombre del shortcode.
     * @return string HTML renderizado.
     */
    public function render_specific_module_shortcode( $atts, $content, $tag ) {
        // Extraer ID del módulo del nombre del shortcode
        $module_id = str_replace( array( 'flavor_', '_' ), array( '', '-' ), $tag );

        $atts = shortcode_atts(
            array(
                'view'    => '',
                'limit'   => 6,
                'columns' => 3,
                'style'   => 'default',
                'class'   => '',
            ),
            $atts,
            $tag
        );

        $atts['module'] = $module_id;

        return $this->render_module( $module_id, $atts );
    }

    /**
     * Renderizar un módulo.
     *
     * @param string $module_id ID del módulo.
     * @param array  $atts      Atributos.
     * @return string HTML.
     */
    private function render_module( $module_id, $atts ) {
        $config = $this->modules_config[ $module_id ];
        $view   = ! empty( $atts['view'] ) ? sanitize_key( $atts['view'] ) : $config['default'];
        $limit  = absint( $atts['limit'] );
        $class  = sanitize_html_class( $atts['class'] );

        // Verificar si el módulo está activo
        $active_modules = get_option( 'flavor_active_modules', array() );
        $is_active      = in_array( $module_id, $active_modules, true ) ||
                          in_array( str_replace( '-', '_', $module_id ), $active_modules, true );

        // Contenedor principal
        $wrapper_class = sprintf(
            'flavor-module flavor-module--%s flavor-module--view-%s %s',
            esc_attr( $module_id ),
            esc_attr( $view ),
            esc_attr( $class )
        );

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $wrapper_class ); ?>" data-module="<?php echo esc_attr( $module_id ); ?>" data-view="<?php echo esc_attr( $view ); ?>">
            <?php
            if ( ! $is_active ) {
                echo $this->render_inactive_module( $module_id, $config );
            } else {
                echo $this->render_module_content( $module_id, $config, $view, $limit, $atts );
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar contenido del módulo.
     *
     * @param string $module_id ID del módulo.
     * @param array  $config    Configuración del módulo.
     * @param string $view      Vista a renderizar.
     * @param int    $limit     Límite de items.
     * @param array  $atts      Atributos adicionales.
     * @return string HTML.
     */
    private function render_module_content( $module_id, $config, $view, $limit, $atts ) {
        // Buscar template específico del módulo
        $template_locations = array(
            get_stylesheet_directory() . "/flavor-platform/modules/{$module_id}/{$view}.php",
            get_template_directory() . "/flavor-platform/modules/{$module_id}/{$view}.php",
            FLAVOR_PLATFORM_PATH . "templates/modules/{$module_id}/{$view}.php",
            FLAVOR_PLATFORM_PATH . "templates/modules/{$module_id}/default.php",
        );

        foreach ( $template_locations as $template_file ) {
            if ( file_exists( $template_file ) ) {
                ob_start();
                include $template_file;
                return ob_get_clean();
            }
        }

        // Si no hay template, renderizar contenido genérico
        return $this->render_generic_module_content( $module_id, $config, $view, $limit );
    }

    /**
     * Renderizar contenido genérico si no hay template específico.
     *
     * @param string $module_id ID del módulo.
     * @param array  $config    Configuración.
     * @param string $view      Vista.
     * @param int    $limit     Límite.
     * @return string HTML.
     */
    private function render_generic_module_content( $module_id, $config, $view, $limit ) {
        $items = array();

        // Si tiene post_type, obtener posts
        if ( ! empty( $config['post_type'] ) && post_type_exists( $config['post_type'] ) ) {
            $query_args = array(
                'post_type'      => $config['post_type'],
                'posts_per_page' => $limit,
                'post_status'    => 'publish',
            );

            $query = new WP_Query( $query_args );
            $items = $query->posts;
        }

        ob_start();
        ?>
        <div class="flavor-module__header">
            <h3 class="flavor-module__title">
                <span class="flavor-module__icon dashicons dashicons-<?php echo esc_attr( $this->get_dashicon( $config['icon'] ) ); ?>"></span>
                <?php echo esc_html( $config['title'] ); ?>
            </h3>
            <p class="flavor-module__description"><?php echo esc_html( $config['description'] ); ?></p>
        </div>

        <div class="flavor-module__content flavor-module__content--<?php echo esc_attr( $view ); ?>">
            <?php if ( empty( $items ) ) : ?>
                <div class="flavor-module__empty">
                    <p><?php esc_html_e( 'No hay contenido disponible en este momento.', 'flavor-platform' ); ?></p>
                    <?php if ( current_user_can( 'edit_posts' ) ) : ?>
                        <p class="flavor-module__admin-hint">
                            <?php
                            printf(
                                /* translators: %s: post type name */
                                esc_html__( 'Administrador: Crea contenido de tipo "%s" para que aparezca aquí.', 'flavor-platform' ),
                                esc_html( $config['post_type'] ?? $module_id )
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="flavor-module__grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                    <?php foreach ( $items as $item ) : ?>
                        <article class="flavor-module__item">
                            <?php if ( has_post_thumbnail( $item ) ) : ?>
                                <div class="flavor-module__thumbnail">
                                    <?php echo get_the_post_thumbnail( $item, 'medium' ); ?>
                                </div>
                            <?php endif; ?>
                            <div class="flavor-module__item-content">
                                <h4 class="flavor-module__item-title">
                                    <a href="<?php echo esc_url( get_permalink( $item ) ); ?>">
                                        <?php echo esc_html( get_the_title( $item ) ); ?>
                                    </a>
                                </h4>
                                <div class="flavor-module__item-excerpt">
                                    <?php echo wp_trim_words( get_the_excerpt( $item ), 20 ); ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .flavor-module {
                margin: 20px 0;
            }
            .flavor-module__header {
                margin-bottom: 20px;
            }
            .flavor-module__title {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 1.5rem;
                margin: 0 0 10px;
            }
            .flavor-module__description {
                color: #666;
                margin: 0;
            }
            .flavor-module__empty {
                text-align: center;
                padding: 40px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .flavor-module__admin-hint {
                font-size: 0.875rem;
                color: #999;
                font-style: italic;
            }
            .flavor-module__item {
                background: #fff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .flavor-module__item:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .flavor-module__thumbnail img {
                width: 100%;
                height: 180px;
                object-fit: cover;
            }
            .flavor-module__item-content {
                padding: 15px;
            }
            .flavor-module__item-title {
                margin: 0 0 10px;
                font-size: 1.1rem;
            }
            .flavor-module__item-title a {
                color: inherit;
                text-decoration: none;
            }
            .flavor-module__item-title a:hover {
                color: #007bff;
            }
            .flavor-module__item-excerpt {
                color: #666;
                font-size: 0.9rem;
                line-height: 1.5;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar módulo inactivo.
     *
     * @param string $module_id ID del módulo.
     * @param array  $config    Configuración.
     * @return string HTML.
     */
    private function render_inactive_module( $module_id, $config ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="flavor-module__inactive" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 20px; text-align: center;">
            <p style="margin: 0 0 10px; font-weight: bold; color: #856404;">
                <?php
                printf(
                    /* translators: %s: module name */
                    esc_html__( 'El módulo "%s" no está activo', 'flavor-platform' ),
                    esc_html( $config['title'] )
                );
                ?>
            </p>
            <p style="margin: 0; color: #856404; font-size: 0.9rem;">
                <?php esc_html_e( 'Activa este módulo desde Flavor Platform → Módulos para mostrar su contenido.', 'flavor-platform' ); ?>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar dashboard unificado.
     *
     * @param array $atts Atributos.
     * @return string HTML.
     */
    public function render_dashboard_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'modules' => '',
                'columns' => 2,
            ),
            $atts,
            'flavor_unified_dashboard'
        );

        if ( ! is_user_logged_in() ) {
            return $this->render_login_required();
        }

        $active_modules = get_option( 'flavor_active_modules', array() );
        $modules_to_show = ! empty( $atts['modules'] )
            ? array_map( 'trim', explode( ',', $atts['modules'] ) )
            : $active_modules;

        ob_start();
        ?>
        <div class="flavor-dashboard" style="display: grid; grid-template-columns: repeat(<?php echo absint( $atts['columns'] ); ?>, 1fr); gap: 20px;">
            <?php
            foreach ( $modules_to_show as $module_id ) {
                $module_id = str_replace( '_', '-', $module_id );
                if ( isset( $this->modules_config[ $module_id ] ) ) {
                    echo $this->render_dashboard_widget( $module_id, $this->modules_config[ $module_id ] );
                }
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar widget de dashboard.
     *
     * @param string $module_id ID del módulo.
     * @param array  $config    Configuración.
     * @return string HTML.
     */
    private function render_dashboard_widget( $module_id, $config ) {
        ob_start();
        ?>
        <div class="flavor-dashboard__widget" style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h3 style="display: flex; align-items: center; gap: 10px; margin: 0 0 15px;">
                <span class="dashicons dashicons-<?php echo esc_attr( $this->get_dashicon( $config['icon'] ) ); ?>"></span>
                <?php echo esc_html( $config['title'] ); ?>
            </h3>
            <p style="color: #666; margin: 0 0 15px;"><?php echo esc_html( $config['description'] ); ?></p>
            <a href="<?php echo esc_url( home_url( '/' . $module_id . '/' ) ); ?>" class="button" style="display: inline-block; padding: 8px 16px; background: #007bff; color: #fff; text-decoration: none; border-radius: 6px;">
                <?php esc_html_e( 'Ver más', 'flavor-platform' ); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar mensaje de login requerido.
     *
     * @return string HTML.
     */
    private function render_login_required() {
        ob_start();
        ?>
        <div class="flavor-auth-required" style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 12px;">
            <span class="dashicons dashicons-lock" style="font-size: 48px; color: #ccc;"></span>
            <h3 style="margin: 20px 0 10px;"><?php esc_html_e( 'Acceso restringido', 'flavor-platform' ); ?></h3>
            <p style="color: #666; margin-bottom: 20px;"><?php esc_html_e( 'Debes iniciar sesión para acceder a esta sección.', 'flavor-platform' ); ?></p>
            <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="button" style="display: inline-block; padding: 12px 24px; background: #007bff; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;">
                <?php esc_html_e( 'Iniciar sesión', 'flavor-platform' ); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar shortcode de autenticación.
     *
     * @param array $atts Atributos.
     * @return string HTML.
     */
    public function render_auth_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'type'     => 'login', // login, register, both
                'redirect' => '',
            ),
            $atts,
            'flavor_auth'
        );

        if ( is_user_logged_in() ) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                esc_html__( 'Ya has iniciado sesión.', 'flavor-platform' ),
                esc_url( wp_logout_url( get_permalink() ) ),
                esc_html__( 'Cerrar sesión', 'flavor-platform' )
            );
        }

        $redirect_to = ! empty( $atts['redirect'] ) ? $atts['redirect'] : get_permalink();

        ob_start();
        ?>
        <div class="flavor-auth-form" style="max-width: 400px; margin: 0 auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.1);">
            <?php
            if ( 'login' === $atts['type'] || 'both' === $atts['type'] ) {
                wp_login_form(
                    array(
                        'redirect' => $redirect_to,
                    )
                );
            }

            if ( 'register' === $atts['type'] || 'both' === $atts['type'] ) {
                if ( get_option( 'users_can_register' ) ) {
                    ?>
                    <p style="margin-top: 20px; text-align: center;">
                        <?php esc_html_e( '¿No tienes cuenta?', 'flavor-platform' ); ?>
                        <a href="<?php echo esc_url( wp_registration_url() ); ?>">
                            <?php esc_html_e( 'Regístrate', 'flavor-platform' ); ?>
                        </a>
                    </p>
                    <?php
                }
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar aviso para administradores.
     *
     * @param string $message Mensaje.
     * @return string HTML.
     */
    private function render_admin_notice( $message ) {
        return sprintf(
            '<div class="flavor-admin-notice" style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; color: #0c5460;"><strong>%s:</strong> %s</div>',
            esc_html__( 'Aviso para administrador', 'flavor-platform' ),
            esc_html( $message )
        );
    }

    /**
     * Convertir nombre de icono a dashicon.
     *
     * @param string $icon Nombre del icono.
     * @return string Clase dashicon.
     */
    private function get_dashicon( $icon ) {
        $icon_map = array(
            'calendar'           => 'calendar-alt',
            'users'              => 'groups',
            'graduation-cap'     => 'welcome-learn-more',
            'comments'           => 'format-chat',
            'shopping-bag'       => 'cart',
            'leaf'               => 'carrot',
            'book'               => 'book',
            'calendar-check'     => 'calendar',
            'clock'              => 'clock',
            'hand-holding-heart' => 'heart',
            'file-alt'           => 'media-document',
            'share-alt'          => 'share',
            'poll'               => 'chart-bar',
        );

        return isset( $icon_map[ $icon ] ) ? $icon_map[ $icon ] : 'admin-generic';
    }
}

// Inicializar shortcodes
add_action( 'init', array( 'Flavor_Shortcodes', 'get_instance' ) );
