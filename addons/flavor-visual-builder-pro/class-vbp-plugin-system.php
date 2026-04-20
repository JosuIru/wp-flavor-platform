<?php
/**
 * Visual Builder Pro - Plugin System
 *
 * Sistema de extensiones y plugins para VBP que permite a terceros
 * añadir bloques, paneles, atajos de teclado y funcionalidades personalizadas.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase principal del sistema de plugins de VBP
 *
 * @since 2.3.0
 */
class Flavor_VBP_Plugin_System {

    /**
     * Versión del sistema de plugins
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Plugin_System|null
     */
    private static $instance = null;

    /**
     * Plugins registrados
     *
     * @var array
     */
    private $plugins = array();

    /**
     * Plugins activos
     *
     * @var array
     */
    private $active_plugins = array();

    /**
     * Hooks disponibles
     *
     * @var array
     */
    private $available_hooks = array(
        'vbp_before_render',
        'vbp_after_render',
        'vbp_before_save',
        'vbp_after_save',
        'vbp_block_registered',
        'vbp_inspector_panel',
        'vbp_toolbar_buttons',
        'vbp_context_menu',
        'vbp_keyboard_shortcut',
        'vbp_canvas_init',
        'vbp_element_selected',
        'vbp_element_deselected',
        'vbp_document_loaded',
        'vbp_document_saved',
        'vbp_plugin_activated',
        'vbp_plugin_deactivated',
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Plugin_System
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->load_active_plugins();
        $this->init_hooks();
    }

    /**
     * Inicializa los hooks de WordPress
     */
    private function init_hooks() {
        // Registrar endpoint REST para plugins
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Hook para cargar plugins en el editor
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_plugin_assets' ), 20 );

        // AJAX handlers para gestión de plugins
        add_action( 'wp_ajax_vbp_activate_plugin', array( $this, 'ajax_activate_plugin' ) );
        add_action( 'wp_ajax_vbp_deactivate_plugin', array( $this, 'ajax_deactivate_plugin' ) );
        add_action( 'wp_ajax_vbp_get_plugins', array( $this, 'ajax_get_plugins' ) );
        add_action( 'wp_ajax_vbp_save_editor_theme', array( $this, 'ajax_save_editor_theme' ) );

        // Permitir que plugins se registren
        do_action( 'vbp_plugin_system_init', $this );
    }

    /**
     * Carga los plugins activos desde la base de datos
     */
    private function load_active_plugins() {
        $this->active_plugins = get_option( 'vbp_active_plugins', array() );
    }

    /**
     * Registra un plugin en el sistema
     *
     * @param string $plugin_id ID único del plugin.
     * @param array  $config    Configuración del plugin.
     * @return bool
     */
    public function register_plugin( $plugin_id, $config ) {
        // Validar ID
        if ( empty( $plugin_id ) || ! is_string( $plugin_id ) ) {
            return false;
        }

        // Sanitizar ID
        $plugin_id = sanitize_key( $plugin_id );

        // Validar configuración mínima
        if ( ! is_array( $config ) || empty( $config['name'] ) ) {
            return false;
        }

        // Estructura por defecto del plugin
        $default_config = array(
            'id'           => $plugin_id,
            'name'         => '',
            'description'  => '',
            'version'      => '1.0.0',
            'author'       => '',
            'author_uri'   => '',
            'icon'         => 'extension',
            'category'     => 'general',
            'blocks'       => array(),
            'panels'       => array(),
            'shortcuts'    => array(),
            'toolbar'      => array(),
            'context_menu' => array(),
            'hooks'        => array(),
            'styles'       => array(),
            'scripts'      => array(),
            'settings'     => array(),
            'dependencies' => array(),
            'init'         => null,
            'activate'     => null,
            'deactivate'   => null,
        );

        // Merge con configuración proporcionada
        $plugin_config = wp_parse_args( $config, $default_config );
        $plugin_config['id'] = $plugin_id;

        // Verificar dependencias
        if ( ! empty( $plugin_config['dependencies'] ) ) {
            foreach ( $plugin_config['dependencies'] as $dependency ) {
                if ( ! isset( $this->plugins[ $dependency ] ) ) {
                    $plugin_config['missing_dependencies'][] = $dependency;
                }
            }
        }

        // Registrar plugin
        $this->plugins[ $plugin_id ] = $plugin_config;

        // Hook para cuando un plugin se registra
        do_action( 'vbp_block_registered', $plugin_id, $plugin_config );

        return true;
    }

    /**
     * Activa un plugin
     *
     * @param string $plugin_id ID del plugin.
     * @return bool|WP_Error
     */
    public function activate_plugin( $plugin_id ) {
        // Verificar que el plugin existe
        if ( ! isset( $this->plugins[ $plugin_id ] ) ) {
            return new WP_Error( 'plugin_not_found', __( 'Plugin no encontrado', 'flavor-platform' ) );
        }

        $plugin = $this->plugins[ $plugin_id ];

        // Verificar dependencias
        if ( ! empty( $plugin['missing_dependencies'] ) ) {
            return new WP_Error(
                'missing_dependencies',
                sprintf(
                    /* translators: %s: list of missing dependencies */
                    __( 'Dependencias no encontradas: %s', 'flavor-platform' ),
                    implode( ', ', $plugin['missing_dependencies'] )
                )
            );
        }

        // Verificar si ya está activo
        if ( in_array( $plugin_id, $this->active_plugins, true ) ) {
            return true;
        }

        // Ejecutar callback de activación si existe
        if ( is_callable( $plugin['activate'] ) ) {
            $activation_result = call_user_func( $plugin['activate'] );
            if ( is_wp_error( $activation_result ) ) {
                return $activation_result;
            }
        }

        // Añadir a plugins activos
        $this->active_plugins[] = $plugin_id;
        update_option( 'vbp_active_plugins', $this->active_plugins );

        // Hook de activación
        do_action( 'vbp_plugin_activated', $plugin_id, $plugin );

        return true;
    }

    /**
     * Desactiva un plugin
     *
     * @param string $plugin_id ID del plugin.
     * @return bool|WP_Error
     */
    public function deactivate_plugin( $plugin_id ) {
        // Verificar que el plugin existe
        if ( ! isset( $this->plugins[ $plugin_id ] ) ) {
            return new WP_Error( 'plugin_not_found', __( 'Plugin no encontrado', 'flavor-platform' ) );
        }

        $plugin = $this->plugins[ $plugin_id ];

        // Verificar si está activo
        $index = array_search( $plugin_id, $this->active_plugins, true );
        if ( false === $index ) {
            return true;
        }

        // Verificar si otros plugins dependen de este
        $dependents = $this->get_dependent_plugins( $plugin_id );
        if ( ! empty( $dependents ) ) {
            return new WP_Error(
                'has_dependents',
                sprintf(
                    /* translators: %s: list of dependent plugins */
                    __( 'Otros plugins dependen de este: %s', 'flavor-platform' ),
                    implode( ', ', $dependents )
                )
            );
        }

        // Ejecutar callback de desactivación si existe
        if ( is_callable( $plugin['deactivate'] ) ) {
            call_user_func( $plugin['deactivate'] );
        }

        // Quitar de plugins activos
        array_splice( $this->active_plugins, $index, 1 );
        update_option( 'vbp_active_plugins', $this->active_plugins );

        // Hook de desactivación
        do_action( 'vbp_plugin_deactivated', $plugin_id, $plugin );

        return true;
    }

    /**
     * Obtiene plugins que dependen de un plugin dado
     *
     * @param string $plugin_id ID del plugin.
     * @return array
     */
    private function get_dependent_plugins( $plugin_id ) {
        $dependents = array();

        foreach ( $this->active_plugins as $active_plugin_id ) {
            if ( isset( $this->plugins[ $active_plugin_id ] ) ) {
                $plugin = $this->plugins[ $active_plugin_id ];
                if ( ! empty( $plugin['dependencies'] ) && in_array( $plugin_id, $plugin['dependencies'], true ) ) {
                    $dependents[] = $plugin['name'];
                }
            }
        }

        return $dependents;
    }

    /**
     * Verifica si un plugin está activo
     *
     * @param string $plugin_id ID del plugin.
     * @return bool
     */
    public function is_plugin_active( $plugin_id ) {
        return in_array( $plugin_id, $this->active_plugins, true );
    }

    /**
     * Obtiene todos los plugins registrados
     *
     * @return array
     */
    public function get_all_plugins() {
        $plugins_with_status = array();

        foreach ( $this->plugins as $plugin_id => $plugin ) {
            $plugin['is_active'] = $this->is_plugin_active( $plugin_id );
            $plugins_with_status[ $plugin_id ] = $plugin;
        }

        return $plugins_with_status;
    }

    /**
     * Obtiene solo los plugins activos
     *
     * @return array
     */
    public function get_active_plugins() {
        $active = array();

        foreach ( $this->active_plugins as $plugin_id ) {
            if ( isset( $this->plugins[ $plugin_id ] ) ) {
                $active[ $plugin_id ] = $this->plugins[ $plugin_id ];
            }
        }

        return $active;
    }

    /**
     * Obtiene un plugin específico
     *
     * @param string $plugin_id ID del plugin.
     * @return array|null
     */
    public function get_plugin( $plugin_id ) {
        if ( isset( $this->plugins[ $plugin_id ] ) ) {
            $plugin = $this->plugins[ $plugin_id ];
            $plugin['is_active'] = $this->is_plugin_active( $plugin_id );
            return $plugin;
        }
        return null;
    }

    /**
     * Obtiene bloques de todos los plugins activos
     *
     * @return array
     */
    public function get_plugin_blocks() {
        $blocks = array();

        foreach ( $this->get_active_plugins() as $plugin_id => $plugin ) {
            if ( ! empty( $plugin['blocks'] ) ) {
                foreach ( $plugin['blocks'] as $block ) {
                    $block['plugin_id'] = $plugin_id;
                    $blocks[] = $block;
                }
            }
        }

        return $blocks;
    }

    /**
     * Obtiene paneles de todos los plugins activos
     *
     * @return array
     */
    public function get_plugin_panels() {
        $panels = array();

        foreach ( $this->get_active_plugins() as $plugin_id => $plugin ) {
            if ( ! empty( $plugin['panels'] ) ) {
                foreach ( $plugin['panels'] as $panel ) {
                    $panel['plugin_id'] = $plugin_id;
                    $panels[] = $panel;
                }
            }
        }

        return $panels;
    }

    /**
     * Obtiene atajos de teclado de todos los plugins activos
     *
     * @return array
     */
    public function get_plugin_shortcuts() {
        $shortcuts = array();

        foreach ( $this->get_active_plugins() as $plugin_id => $plugin ) {
            if ( ! empty( $plugin['shortcuts'] ) ) {
                foreach ( $plugin['shortcuts'] as $shortcut ) {
                    $shortcut['plugin_id'] = $plugin_id;
                    $shortcuts[] = $shortcut;
                }
            }
        }

        return $shortcuts;
    }

    /**
     * Obtiene items de toolbar de todos los plugins activos
     *
     * @return array
     */
    public function get_plugin_toolbar_items() {
        $toolbar_items = array();

        foreach ( $this->get_active_plugins() as $plugin_id => $plugin ) {
            if ( ! empty( $plugin['toolbar'] ) ) {
                foreach ( $plugin['toolbar'] as $item ) {
                    $item['plugin_id'] = $plugin_id;
                    $toolbar_items[] = $item;
                }
            }
        }

        return $toolbar_items;
    }

    /**
     * Obtiene items de menú contextual de todos los plugins activos
     *
     * @return array
     */
    public function get_plugin_context_menu_items() {
        $context_menu_items = array();

        foreach ( $this->get_active_plugins() as $plugin_id => $plugin ) {
            if ( ! empty( $plugin['context_menu'] ) ) {
                foreach ( $plugin['context_menu'] as $item ) {
                    $item['plugin_id'] = $plugin_id;
                    $context_menu_items[] = $item;
                }
            }
        }

        return $context_menu_items;
    }

    /**
     * Encola assets de plugins activos
     *
     * @param string $hook_suffix Hook actual de admin.
     */
    public function enqueue_plugin_assets( $hook_suffix ) {
        // Solo en el editor VBP
        if ( 'admin_page_vbp-editor' !== $hook_suffix ) {
            return;
        }

        foreach ( $this->get_active_plugins() as $plugin_id => $plugin ) {
            // Estilos
            if ( ! empty( $plugin['styles'] ) ) {
                foreach ( $plugin['styles'] as $style_index => $style ) {
                    $style_handle = 'vbp-plugin-' . $plugin_id . '-style-' . $style_index;

                    if ( is_array( $style ) ) {
                        wp_enqueue_style(
                            $style_handle,
                            $style['src'],
                            isset( $style['deps'] ) ? $style['deps'] : array(),
                            isset( $style['version'] ) ? $style['version'] : $plugin['version']
                        );
                    } else {
                        wp_enqueue_style(
                            $style_handle,
                            $style,
                            array(),
                            $plugin['version']
                        );
                    }
                }
            }

            // Scripts
            if ( ! empty( $plugin['scripts'] ) ) {
                foreach ( $plugin['scripts'] as $script_index => $script ) {
                    $script_handle = 'vbp-plugin-' . $plugin_id . '-script-' . $script_index;

                    if ( is_array( $script ) ) {
                        wp_enqueue_script(
                            $script_handle,
                            $script['src'],
                            isset( $script['deps'] ) ? $script['deps'] : array(),
                            isset( $script['version'] ) ? $script['version'] : $plugin['version'],
                            true
                        );

                        // Localizar datos si existen
                        if ( isset( $script['localize'] ) ) {
                            wp_localize_script(
                                $script_handle,
                                $script['localize']['name'],
                                $script['localize']['data']
                            );
                        }
                    } else {
                        wp_enqueue_script(
                            $script_handle,
                            $script,
                            array(),
                            $plugin['version'],
                            true
                        );
                    }
                }
            }
        }
    }

    /**
     * Registra rutas REST
     */
    public function register_rest_routes() {
        register_rest_route(
            'flavor-vbp/v1',
            '/plugins',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'rest_get_plugins' ),
                    'permission_callback' => array( $this, 'rest_permission_check' ),
                ),
            )
        );

        register_rest_route(
            'flavor-vbp/v1',
            '/plugins/(?P<id>[a-z0-9-_]+)/activate',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'rest_activate_plugin' ),
                    'permission_callback' => array( $this, 'rest_permission_check' ),
                ),
            )
        );

        register_rest_route(
            'flavor-vbp/v1',
            '/plugins/(?P<id>[a-z0-9-_]+)/deactivate',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'rest_deactivate_plugin' ),
                    'permission_callback' => array( $this, 'rest_permission_check' ),
                ),
            )
        );

        register_rest_route(
            'flavor-vbp/v1',
            '/plugins/extensions',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'rest_get_extensions' ),
                    'permission_callback' => array( $this, 'rest_permission_check' ),
                ),
            )
        );
    }

    /**
     * Verificación de permisos REST
     *
     * @return bool
     */
    public function rest_permission_check() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * REST: Obtiene todos los plugins
     *
     * @return WP_REST_Response
     */
    public function rest_get_plugins() {
        return rest_ensure_response(
            array(
                'plugins'   => $this->get_all_plugins(),
                'active'    => $this->active_plugins,
                'blocks'    => $this->get_plugin_blocks(),
                'panels'    => $this->get_plugin_panels(),
                'shortcuts' => $this->get_plugin_shortcuts(),
                'toolbar'   => $this->get_plugin_toolbar_items(),
            )
        );
    }

    /**
     * REST: Activa un plugin
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public function rest_activate_plugin( $request ) {
        $plugin_id = $request->get_param( 'id' );
        $result = $this->activate_plugin( $plugin_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'plugin'  => $this->get_plugin( $plugin_id ),
            )
        );
    }

    /**
     * REST: Desactiva un plugin
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public function rest_deactivate_plugin( $request ) {
        $plugin_id = $request->get_param( 'id' );
        $result = $this->deactivate_plugin( $plugin_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'plugin'  => $this->get_plugin( $plugin_id ),
            )
        );
    }

    /**
     * REST: Obtiene datos de extensiones para el frontend
     *
     * @return WP_REST_Response
     */
    public function rest_get_extensions() {
        return rest_ensure_response(
            array(
                'blocks'       => $this->get_plugin_blocks(),
                'panels'       => $this->get_plugin_panels(),
                'shortcuts'    => $this->get_plugin_shortcuts(),
                'toolbar'      => $this->get_plugin_toolbar_items(),
                'context_menu' => $this->get_plugin_context_menu_items(),
            )
        );
    }

    /**
     * AJAX: Activa un plugin
     */
    public function ajax_activate_plugin() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permisos', 'flavor-platform' ) ) );
        }

        $plugin_id = isset( $_POST['plugin_id'] ) ? sanitize_key( $_POST['plugin_id'] ) : '';
        $result = $this->activate_plugin( $plugin_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Plugin activado', 'flavor-platform' ),
                'plugin'  => $this->get_plugin( $plugin_id ),
            )
        );
    }

    /**
     * AJAX: Desactiva un plugin
     */
    public function ajax_deactivate_plugin() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permisos', 'flavor-platform' ) ) );
        }

        $plugin_id = isset( $_POST['plugin_id'] ) ? sanitize_key( $_POST['plugin_id'] ) : '';
        $result = $this->deactivate_plugin( $plugin_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Plugin desactivado', 'flavor-platform' ),
                'plugin'  => $this->get_plugin( $plugin_id ),
            )
        );
    }

    /**
     * AJAX: Obtiene todos los plugins
     */
    public function ajax_get_plugins() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permisos', 'flavor-platform' ) ) );
        }

        wp_send_json_success(
            array(
                'plugins' => $this->get_all_plugins(),
                'active'  => $this->active_plugins,
            )
        );
    }

    /**
     * AJAX: Guarda el tema del editor en user meta
     */
    public function ajax_save_editor_theme() {
        check_ajax_referer( 'vbp_editor_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sin permisos', 'flavor-platform' ) ) );
        }

        $theme_id = isset( $_POST['theme_id'] ) ? sanitize_key( $_POST['theme_id'] ) : 'system';
        $user_id  = get_current_user_id();

        // Validar que es un tema válido
        $temas_validos = array( 'light', 'dark', 'midnight', 'forest', 'high-contrast', 'system' );

        // También permitir temas personalizados (empiezan con 'custom_')
        if ( ! in_array( $theme_id, $temas_validos, true ) && strpos( $theme_id, 'custom_' ) !== 0 ) {
            wp_send_json_error( array( 'message' => __( 'Tema no válido', 'flavor-platform' ) ) );
        }

        // Guardar en user meta
        update_user_meta( $user_id, 'vbp_editor_theme', $theme_id );

        wp_send_json_success(
            array(
                'message'  => __( 'Tema guardado', 'flavor-platform' ),
                'theme_id' => $theme_id,
            )
        );
    }

    /**
     * Obtiene el tema del editor para el usuario actual
     *
     * @return string
     */
    public static function get_user_editor_theme() {
        $user_id  = get_current_user_id();
        $theme_id = get_user_meta( $user_id, 'vbp_editor_theme', true );

        if ( empty( $theme_id ) ) {
            return 'system';
        }

        return $theme_id;
    }

    /**
     * Obtiene los hooks disponibles
     *
     * @return array
     */
    public function get_available_hooks() {
        return $this->available_hooks;
    }

    /**
     * Registra un hook personalizado
     *
     * @param string $hook_name Nombre del hook.
     * @return bool
     */
    public function register_hook( $hook_name ) {
        if ( ! in_array( $hook_name, $this->available_hooks, true ) ) {
            $this->available_hooks[] = $hook_name;
            return true;
        }
        return false;
    }
}

/**
 * Función helper para obtener la instancia del sistema de plugins
 *
 * @return Flavor_VBP_Plugin_System
 */
function flavor_vbp_plugins() {
    return Flavor_VBP_Plugin_System::get_instance();
}

/**
 * Función helper para registrar un plugin VBP
 *
 * @param string $plugin_id ID único del plugin.
 * @param array  $config    Configuración del plugin.
 * @return bool
 */
function flavor_vbp_register_plugin( $plugin_id, $config ) {
    return Flavor_VBP_Plugin_System::get_instance()->register_plugin( $plugin_id, $config );
}
