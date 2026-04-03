<?php
/**
 * Clase de Configuración de Visual Builder Pro
 *
 * Gestiona las opciones de configuración de VBP, incluyendo:
 * - API key para acceso de Claude/herramientas externas
 * - Opción de reemplazar Gutenberg
 * - Otras configuraciones del editor
 *
 * @package FlavorPlatform
 * @subpackage VisualBuilderPro
 * @since 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VBP_Settings {

    /**
     * Instancia singleton
     *
     * @var VBP_Settings|null
     */
    private static $instancia = null;

    /**
     * Slug del menú
     */
    const MENU_SLUG = 'vbp-settings';

    /**
     * Nombre de la opción en la base de datos
     */
    const OPTION_NAME = 'flavor_vbp_settings';

    /**
     * Obtener instancia singleton
     *
     * @return VBP_Settings
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'agregar_pagina_settings' ), 20 );
        add_action( 'admin_init', array( $this, 'registrar_settings' ) );
        add_action( 'wp_ajax_vbp_regenerar_api_key', array( $this, 'ajax_regenerar_api_key' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'encolar_assets' ) );
    }

    /**
     * Agregar página de settings al menú
     */
    public function agregar_pagina_settings() {
        add_submenu_page(
            'flavor-chat-ia',
            __( 'Configuración VBP', 'flavor-chat-ia' ),
            __( 'Config. VBP', 'flavor-chat-ia' ),
            'manage_options',
            self::MENU_SLUG,
            array( $this, 'renderizar_pagina' )
        );
    }

    /**
     * Registrar settings con la API de WordPress
     */
    public function registrar_settings() {
        register_setting(
            'vbp_settings_group',
            self::OPTION_NAME,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitizar_settings' ),
                'default'           => $this->get_defaults(),
            )
        );

        // Sección: API Key
        add_settings_section(
            'vbp_api_section',
            __( 'Configuración de API', 'flavor-chat-ia' ),
            array( $this, 'render_seccion_api' ),
            self::MENU_SLUG
        );

        add_settings_field(
            'api_key_display',
            __( 'API Key', 'flavor-chat-ia' ),
            array( $this, 'render_campo_api_key' ),
            self::MENU_SLUG,
            'vbp_api_section'
        );

        // Sección: Editor
        add_settings_section(
            'vbp_editor_section',
            __( 'Configuración del Editor', 'flavor-chat-ia' ),
            array( $this, 'render_seccion_editor' ),
            self::MENU_SLUG
        );

        add_settings_field(
            'replace_gutenberg',
            __( 'Reemplazar Gutenberg', 'flavor-chat-ia' ),
            array( $this, 'render_campo_replace_gutenberg' ),
            self::MENU_SLUG,
            'vbp_editor_section'
        );

        add_settings_field(
            'enable_versioning',
            __( 'Historial de versiones', 'flavor-chat-ia' ),
            array( $this, 'render_campo_versioning' ),
            self::MENU_SLUG,
            'vbp_editor_section'
        );
    }

    /**
     * Obtener valores por defecto
     *
     * @return array
     */
    public function get_defaults() {
        return array(
            'api_key'           => '',
            'replace_gutenberg' => false,
            'enable_versioning' => true,
            'max_versions'      => 10,
        );
    }

    /**
     * Sanitizar settings antes de guardar
     *
     * @param array $input Datos de entrada.
     * @return array Datos sanitizados.
     */
    public function sanitizar_settings( $input ) {
        $sanitizado = array();
        $defaults   = $this->get_defaults();

        // API Key: no se modifica desde el formulario (solo via AJAX regenerar)
        $settings_actuales          = get_option( self::OPTION_NAME, array() );
        $sanitizado['api_key']      = isset( $settings_actuales['api_key'] ) ? $settings_actuales['api_key'] : '';

        // Replace Gutenberg
        $sanitizado['replace_gutenberg'] = ! empty( $input['replace_gutenberg'] );

        // Versioning
        $sanitizado['enable_versioning'] = ! empty( $input['enable_versioning'] );
        $sanitizado['max_versions']      = isset( $input['max_versions'] )
            ? absint( $input['max_versions'] )
            : $defaults['max_versions'];

        return $sanitizado;
    }

    /**
     * Renderizar descripción de sección API
     */
    public function render_seccion_api() {
        echo '<p>' . esc_html__(
            'La API Key permite que herramientas externas (como Claude Code) accedan a VBP de forma segura.',
            'flavor-chat-ia'
        ) . '</p>';
    }

    /**
     * Renderizar descripción de sección Editor
     */
    public function render_seccion_editor() {
        echo '<p>' . esc_html__(
            'Configura el comportamiento del editor Visual Builder Pro.',
            'flavor-chat-ia'
        ) . '</p>';
    }

    /**
     * Renderizar campo API Key
     */
    public function render_campo_api_key() {
        $api_key = flavor_get_vbp_api_key();
        $masked  = substr( $api_key, 0, 8 ) . str_repeat( '*', 16 ) . substr( $api_key, -4 );
        ?>
        <div class="vbp-api-key-container">
            <code id="vbp-api-key-display" class="vbp-api-key-masked"><?php echo esc_html( $masked ); ?></code>
            <code id="vbp-api-key-full" class="vbp-api-key-full" style="display:none;"><?php echo esc_html( $api_key ); ?></code>

            <div class="vbp-api-key-actions">
                <button type="button" id="vbp-toggle-key" class="button button-secondary">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e( 'Mostrar', 'flavor-chat-ia' ); ?>
                </button>

                <button type="button" id="vbp-copy-key" class="button button-secondary">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e( 'Copiar', 'flavor-chat-ia' ); ?>
                </button>

                <button type="button" id="vbp-regenerate-key" class="button button-secondary vbp-btn-danger">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e( 'Regenerar', 'flavor-chat-ia' ); ?>
                </button>
            </div>

            <p class="description">
                <?php esc_html_e( 'Usa esta key en el header X-VBP-Key para autenticar peticiones a la API.', 'flavor-chat-ia' ); ?>
            </p>

            <div class="vbp-api-usage-example">
                <strong><?php esc_html_e( 'Ejemplo de uso:', 'flavor-chat-ia' ); ?></strong>
                <pre>curl -H "X-VBP-Key: <?php echo esc_html( $masked ); ?>" \
     <?php echo esc_url( rest_url( 'flavor-vbp/v1/claude/status' ) ); ?></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar campo Replace Gutenberg
     */
    public function render_campo_replace_gutenberg() {
        $settings = get_option( self::OPTION_NAME, $this->get_defaults() );
        $checked  = ! empty( $settings['replace_gutenberg'] );
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[replace_gutenberg]"
                   value="1"
                   <?php checked( $checked ); ?>>
            <?php esc_html_e( 'Usar VBP en lugar de Gutenberg para páginas y posts', 'flavor-chat-ia' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e(
                'Si está desactivado, VBP solo se usará para el tipo de post "flavor_landing". ' .
                'Gutenberg seguirá disponible para páginas y posts normales.',
                'flavor-chat-ia'
            ); ?>
        </p>
        <?php
    }

    /**
     * Renderizar campo Versioning
     */
    public function render_campo_versioning() {
        $settings = get_option( self::OPTION_NAME, $this->get_defaults() );
        $checked  = isset( $settings['enable_versioning'] ) ? $settings['enable_versioning'] : true;
        $max      = isset( $settings['max_versions'] ) ? $settings['max_versions'] : 10;
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enable_versioning]"
                   value="1"
                   <?php checked( $checked ); ?>>
            <?php esc_html_e( 'Guardar historial de versiones', 'flavor-chat-ia' ); ?>
        </label>
        <br><br>
        <label>
            <?php esc_html_e( 'Máximo de versiones por página:', 'flavor-chat-ia' ); ?>
            <input type="number"
                   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[max_versions]"
                   value="<?php echo esc_attr( $max ); ?>"
                   min="1"
                   max="50"
                   style="width: 60px;">
        </label>
        <p class="description">
            <?php esc_html_e( 'Permite restaurar versiones anteriores de las páginas editadas con VBP.', 'flavor-chat-ia' ); ?>
        </p>
        <?php
    }

    /**
     * Renderizar página completa de settings
     */
    public function renderizar_pagina() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'flavor-chat-ia' ) );
        }
        ?>
        <div class="wrap vbp-settings-wrap">
            <h1>
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e( 'Configuración de Visual Builder Pro', 'flavor-chat-ia' ); ?>
            </h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'vbp_settings_group' );
                do_settings_sections( self::MENU_SLUG );
                submit_button();
                ?>
            </form>

            <hr>

            <h2><?php esc_html_e( 'Información del Sistema', 'flavor-chat-ia' ); ?></h2>
            <table class="widefat fixed" style="max-width: 600px;">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e( 'Versión VBP', 'flavor-chat-ia' ); ?></strong></td>
                        <td><?php echo esc_html( defined( 'FLAVOR_VBP_VERSION' ) ? FLAVOR_VBP_VERSION : '3.5.0' ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Páginas con VBP', 'flavor-chat-ia' ); ?></strong></td>
                        <td><?php echo esc_html( $this->contar_paginas_vbp() ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'API Endpoint', 'flavor-chat-ia' ); ?></strong></td>
                        <td><code><?php echo esc_url( rest_url( 'flavor-vbp/v1/' ) ); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Documentación', 'flavor-chat-ia' ); ?></strong></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flavor-documentation' ) ); ?>">
                                <?php esc_html_e( 'Ver guía de uso', 'flavor-chat-ia' ); ?>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Contar páginas que usan VBP
     *
     * @return int
     */
    private function contar_paginas_vbp() {
        global $wpdb;
        $count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
             WHERE meta_key IN ('_flavor_vbp_data', '_vbp_content')"
        );
        return absint( $count );
    }

    /**
     * AJAX: Regenerar API Key
     */
    public function ajax_regenerar_api_key() {
        check_ajax_referer( 'vbp_settings_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permisos insuficientes.', 'flavor-chat-ia' ) ) );
        }

        $nueva_key = flavor_regenerate_vbp_api_key();
        $masked    = substr( $nueva_key, 0, 8 ) . str_repeat( '*', 16 ) . substr( $nueva_key, -4 );

        wp_send_json_success( array(
            'key'    => $nueva_key,
            'masked' => $masked,
            'message' => __( 'API Key regenerada correctamente.', 'flavor-chat-ia' ),
        ) );
    }

    /**
     * Encolar assets para la página de settings
     *
     * @param string $hook Hook actual.
     */
    public function encolar_assets( $hook ) {
        if ( 'flavor-chat-ia_page_' . self::MENU_SLUG !== $hook ) {
            return;
        }

        wp_enqueue_style( 'dashicons' );

        // CSS inline
        wp_add_inline_style( 'dashicons', $this->get_inline_css() );

        // JS inline
        wp_add_inline_script( 'jquery', $this->get_inline_js() );
    }

    /**
     * Obtener CSS inline para la página
     *
     * @return string
     */
    private function get_inline_css() {
        return '
            .vbp-settings-wrap h1 .dashicons {
                font-size: 28px;
                width: 28px;
                height: 28px;
                margin-right: 8px;
                vertical-align: middle;
            }
            .vbp-api-key-container {
                background: #f9f9f9;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                max-width: 600px;
            }
            .vbp-api-key-container code {
                display: block;
                padding: 10px;
                background: #fff;
                border: 1px solid #ccc;
                font-size: 14px;
                margin-bottom: 10px;
                word-break: break-all;
            }
            .vbp-api-key-actions {
                display: flex;
                gap: 8px;
                margin-bottom: 10px;
            }
            .vbp-api-key-actions .button .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                vertical-align: middle;
                margin-right: 4px;
            }
            .vbp-btn-danger {
                color: #a00 !important;
                border-color: #a00 !important;
            }
            .vbp-btn-danger:hover {
                background: #fee !important;
            }
            .vbp-api-usage-example {
                margin-top: 15px;
                padding: 10px;
                background: #fff;
                border-left: 3px solid #0073aa;
            }
            .vbp-api-usage-example pre {
                margin: 5px 0 0;
                padding: 8px;
                background: #f5f5f5;
                overflow-x: auto;
                font-size: 12px;
            }
            .vbp-settings-wrap .widefat td {
                padding: 10px 15px;
            }
        ';
    }

    /**
     * Obtener JS inline para la página
     *
     * @return string
     */
    private function get_inline_js() {
        $nonce = wp_create_nonce( 'vbp_settings_nonce' );
        return "
            jQuery(document).ready(function($) {
                var keyVisible = false;

                // Toggle visibility
                $('#vbp-toggle-key').on('click', function() {
                    keyVisible = !keyVisible;
                    if (keyVisible) {
                        $('#vbp-api-key-display').hide();
                        $('#vbp-api-key-full').show();
                        $(this).find('span').removeClass('dashicons-visibility').addClass('dashicons-hidden');
                        $(this).contents().last().replaceWith(' " . esc_js( __( 'Ocultar', 'flavor-chat-ia' ) ) . "');
                    } else {
                        $('#vbp-api-key-display').show();
                        $('#vbp-api-key-full').hide();
                        $(this).find('span').removeClass('dashicons-hidden').addClass('dashicons-visibility');
                        $(this).contents().last().replaceWith(' " . esc_js( __( 'Mostrar', 'flavor-chat-ia' ) ) . "');
                    }
                });

                // Copy to clipboard
                $('#vbp-copy-key').on('click', function() {
                    var key = $('#vbp-api-key-full').text();
                    navigator.clipboard.writeText(key).then(function() {
                        alert('" . esc_js( __( 'API Key copiada al portapapeles', 'flavor-chat-ia' ) ) . "');
                    });
                });

                // Regenerate key
                $('#vbp-regenerate-key').on('click', function() {
                    if (!confirm('" . esc_js( __( '¿Seguro que quieres regenerar la API Key? Las herramientas que usen la key actual dejarán de funcionar.', 'flavor-chat-ia' ) ) . "')) {
                        return;
                    }

                    var btn = $(this);
                    btn.prop('disabled', true);

                    $.post(ajaxurl, {
                        action: 'vbp_regenerar_api_key',
                        nonce: '" . esc_js( $nonce ) . "'
                    }, function(response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            $('#vbp-api-key-display').text(response.data.masked);
                            $('#vbp-api-key-full').text(response.data.key);
                            alert(response.data.message);
                        } else {
                            alert(response.data.message || 'Error al regenerar la key');
                        }
                    });
                });
            });
        ";
    }
}

// Inicializar
VBP_Settings::get_instance();
