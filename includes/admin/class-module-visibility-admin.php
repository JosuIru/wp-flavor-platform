<?php
/**
 * Administración de Visibilidad de Módulos
 *
 * Permite configurar la visibilidad y permisos de acceso de cada módulo
 * desde el panel de administración de WordPress.
 *
 * @package FlavorChatIA
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para administrar la visibilidad de módulos
 */
class Flavor_Module_Visibility_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Module_Visibility_Admin
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
        add_action('admin_init', [$this, 'registrar_settings']);
        add_action('wp_ajax_flavor_save_module_visibility', [$this, 'ajax_guardar_visibilidad']);
        add_action('wp_ajax_flavor_reset_module_visibility', [$this, 'ajax_resetear_visibilidad']);
    }

    /**
     * Registra los settings
     */
    public function registrar_settings() {
        register_setting('flavor_modules_visibility', 'flavor_modules_visibility', [
            'sanitize_callback' => [$this, 'sanitizar_visibilidades'],
            'default' => [],
        ]);

        register_setting('flavor_modules_capabilities', 'flavor_modules_capabilities', [
            'sanitize_callback' => [$this, 'sanitizar_capacidades'],
            'default' => [],
        ]);
    }

    /**
     * Sanitiza las visibilidades
     *
     * @param array $input Datos de entrada
     * @return array Datos sanitizados
     */
    public function sanitizar_visibilidades($input) {
        $visibilidades_validas = ['public', 'private', 'members_only'];
        $resultado = [];

        if (is_array($input)) {
            foreach ($input as $module_id => $visibilidad) {
                $module_id_sanitizado = sanitize_key($module_id);
                if (in_array($visibilidad, $visibilidades_validas, true)) {
                    $resultado[$module_id_sanitizado] = $visibilidad;
                }
            }
        }

        return $resultado;
    }

    /**
     * Sanitiza las capacidades
     *
     * @param array $input Datos de entrada
     * @return array Datos sanitizados
     */
    public function sanitizar_capacidades($input) {
        $resultado = [];

        if (is_array($input)) {
            foreach ($input as $module_id => $capacidad) {
                $module_id_sanitizado = sanitize_key($module_id);
                $capacidad_sanitizada = sanitize_key($capacidad);
                $resultado[$module_id_sanitizado] = $capacidad_sanitizada;
            }
        }

        return $resultado;
    }

    /**
     * AJAX: Guarda la visibilidad de un módulo
     */
    public function ajax_guardar_visibilidad() {
        check_ajax_referer('flavor_module_visibility_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos suficientes', 'flavor-chat-ia')]);
        }

        $module_id = sanitize_key($_POST['module_id'] ?? '');
        $visibilidad = sanitize_key($_POST['visibility'] ?? 'public');
        $capacidad = sanitize_key($_POST['capability'] ?? 'read');

        if (empty($module_id)) {
            wp_send_json_error(['message' => __('ID de modulo invalido', 'flavor-chat-ia')]);
        }

        // Validar visibilidad
        $visibilidades_validas = ['public', 'private', 'members_only'];
        if (!in_array($visibilidad, $visibilidades_validas, true)) {
            $visibilidad = 'public';
        }

        // Guardar visibilidad
        $visibilidades_actuales = get_option('flavor_modules_visibility', []);
        $visibilidades_actuales[$module_id] = $visibilidad;
        update_option('flavor_modules_visibility', $visibilidades_actuales);

        // Guardar capacidad
        $capacidades_actuales = get_option('flavor_modules_capabilities', []);
        $capacidades_actuales[$module_id] = $capacidad;
        update_option('flavor_modules_capabilities', $capacidades_actuales);

        // Limpiar cache de acceso si existe
        if (class_exists('Flavor_Module_Access_Control')) {
            Flavor_Module_Access_Control::get_instance()->limpiar_cache();
        }

        wp_send_json_success([
            'message' => __('Visibilidad actualizada correctamente', 'flavor-chat-ia'),
            'module_id' => $module_id,
            'visibility' => $visibilidad,
            'capability' => $capacidad,
        ]);
    }

    /**
     * AJAX: Resetea la visibilidad de todos los módulos a valores por defecto
     */
    public function ajax_resetear_visibilidad() {
        check_ajax_referer('flavor_module_visibility_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos suficientes', 'flavor-chat-ia')]);
        }

        // Eliminar opciones personalizadas
        delete_option('flavor_modules_visibility');
        delete_option('flavor_modules_capabilities');

        // Limpiar cache de acceso si existe
        if (class_exists('Flavor_Module_Access_Control')) {
            Flavor_Module_Access_Control::get_instance()->limpiar_cache();
        }

        wp_send_json_success([
            'message' => __('Se han restaurado los valores por defecto', 'flavor-chat-ia'),
        ]);
    }

    /**
     * Renderiza la sección de visibilidad en la página de módulos
     *
     * @return string HTML de la sección
     */
    public function renderizar_seccion_visibilidad() {
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $informacion_modulos = $loader->get_all_modules_visibility_info();

        // Agrupar módulos por visibilidad actual
        $modulos_por_visibilidad = [
            'public' => [],
            'members_only' => [],
            'private' => [],
        ];

        foreach ($informacion_modulos as $module_id => $info) {
            $visibilidad = $info['current_visibility'];
            $modulos_por_visibilidad[$visibilidad][$module_id] = $info;
        }

        ob_start();
        ?>
        <div class="flavor-visibility-section">
            <div class="flavor-visibility-header">
                <h2><?php esc_html_e('Control de Acceso a Modulos', 'flavor-chat-ia'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Configura quien puede acceder a cada modulo. Los cambios se aplican inmediatamente.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <div class="flavor-visibility-actions">
                <button type="button" id="flavor-reset-visibility" class="button button-secondary">
                    <span class="dashicons dashicons-image-rotate"></span>
                    <?php esc_html_e('Restaurar valores por defecto', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <div class="flavor-visibility-grid">
                <?php foreach ($informacion_modulos as $module_id => $info) : ?>
                    <?php $this->renderizar_tarjeta_modulo($module_id, $info); ?>
                <?php endforeach; ?>
            </div>

            <?php wp_nonce_field('flavor_module_visibility_nonce', 'flavor_visibility_nonce'); ?>
        </div>

        <style>
            .flavor-visibility-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 20px;
                margin-top: 20px;
            }

            .flavor-visibility-header {
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }

            .flavor-visibility-header h2 {
                margin: 0 0 8px;
                font-size: 1.3em;
            }

            .flavor-visibility-actions {
                margin-bottom: 20px;
            }

            .flavor-visibility-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 16px;
            }

            .flavor-module-card {
                background: #f9f9f9;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 16px;
                transition: all 0.2s;
            }

            .flavor-module-card:hover {
                border-color: #2271b1;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            }

            .flavor-module-card.visibility-public {
                border-left: 4px solid #00a32a;
            }

            .flavor-module-card.visibility-members_only {
                border-left: 4px solid #dba617;
            }

            .flavor-module-card.visibility-private {
                border-left: 4px solid #d63638;
            }

            .flavor-module-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 12px;
            }

            .flavor-module-name {
                font-weight: 600;
                font-size: 14px;
                color: #1d2327;
            }

            .flavor-visibility-badge {
                font-size: 11px;
                padding: 3px 8px;
                border-radius: 4px;
                font-weight: 500;
                text-transform: uppercase;
            }

            .flavor-visibility-badge.public {
                background: #d4edda;
                color: #155724;
            }

            .flavor-visibility-badge.members_only {
                background: #fff3cd;
                color: #856404;
            }

            .flavor-visibility-badge.private {
                background: #f8d7da;
                color: #721c24;
            }

            .flavor-module-controls {
                display: flex;
                gap: 10px;
            }

            .flavor-module-controls select {
                flex: 1;
                min-width: 0;
            }

            .flavor-control-group {
                flex: 1;
            }

            .flavor-control-group label {
                display: block;
                font-size: 11px;
                color: #646970;
                margin-bottom: 4px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .flavor-control-group select {
                width: 100%;
            }

            .flavor-module-footer {
                margin-top: 12px;
                padding-top: 10px;
                border-top: 1px solid #e0e0e0;
                font-size: 12px;
                color: #646970;
            }

            .flavor-default-badge {
                display: inline-block;
                font-size: 10px;
                padding: 2px 6px;
                background: #f0f0f1;
                border-radius: 3px;
                color: #50575e;
            }

            .flavor-saving-indicator {
                display: none;
                color: #2271b1;
                font-size: 12px;
            }

            .flavor-saving-indicator.active {
                display: inline-block;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var nonce = $('#flavor_visibility_nonce').val();

            // Cambio de visibilidad
            $('.flavor-visibility-select').on('change', function() {
                var $card = $(this).closest('.flavor-module-card');
                var moduleId = $card.data('module-id');
                var visibility = $(this).val();
                var capability = $card.find('.flavor-capability-select').val();

                guardarVisibilidad(moduleId, visibility, capability, $card);
            });

            // Cambio de capacidad
            $('.flavor-capability-select').on('change', function() {
                var $card = $(this).closest('.flavor-module-card');
                var moduleId = $card.data('module-id');
                var visibility = $card.find('.flavor-visibility-select').val();
                var capability = $(this).val();

                guardarVisibilidad(moduleId, visibility, capability, $card);
            });

            function guardarVisibilidad(moduleId, visibility, capability, $card) {
                var $indicator = $card.find('.flavor-saving-indicator');
                $indicator.addClass('active').text('<?php esc_html_e('Guardando...', 'flavor-chat-ia'); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'flavor_save_module_visibility',
                        nonce: nonce,
                        module_id: moduleId,
                        visibility: visibility,
                        capability: capability
                    },
                    success: function(response) {
                        if (response.success) {
                            $indicator.text('<?php esc_html_e('Guardado', 'flavor-chat-ia'); ?>');
                            // Actualizar badge
                            var $badge = $card.find('.flavor-visibility-badge');
                            $badge.removeClass('public members_only private').addClass(visibility);
                            $badge.text(getVisibilityLabel(visibility));
                            // Actualizar borde
                            $card.removeClass('visibility-public visibility-members_only visibility-private')
                                 .addClass('visibility-' + visibility);
                        } else {
                            $indicator.text('<?php esc_html_e('Error', 'flavor-chat-ia'); ?>');
                        }
                        setTimeout(function() {
                            $indicator.removeClass('active');
                        }, 2000);
                    },
                    error: function() {
                        $indicator.text('<?php esc_html_e('Error de conexion', 'flavor-chat-ia'); ?>');
                        setTimeout(function() {
                            $indicator.removeClass('active');
                        }, 2000);
                    }
                });
            }

            function getVisibilityLabel(visibility) {
                var labels = {
                    'public': '<?php esc_html_e('Publico', 'flavor-chat-ia'); ?>',
                    'members_only': '<?php esc_html_e('Miembros', 'flavor-chat-ia'); ?>',
                    'private': '<?php esc_html_e('Privado', 'flavor-chat-ia'); ?>'
                };
                return labels[visibility] || visibility;
            }

            // Resetear visibilidades
            $('#flavor-reset-visibility').on('click', function() {
                if (!confirm('<?php esc_html_e('Esto restaurara todos los modulos a su visibilidad por defecto. Continuar?', 'flavor-chat-ia'); ?>')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php esc_html_e('Restaurando...', 'flavor-chat-ia'); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'flavor_reset_module_visibility',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message);
                            $btn.prop('disabled', false).html('<span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e('Restaurar valores por defecto', 'flavor-chat-ia'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e('Error de conexion', 'flavor-chat-ia'); ?>');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e('Restaurar valores por defecto', 'flavor-chat-ia'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza la tarjeta de un módulo
     *
     * @param string $module_id ID del módulo
     * @param array $info Información del módulo
     */
    private function renderizar_tarjeta_modulo($module_id, $info) {
        $tipos_visibilidad = Flavor_Module_Access_Control::get_visibility_types();
        $capacidades_disponibles = Flavor_Module_Access_Control::get_available_capabilities();

        $visibilidad_actual = $info['current_visibility'];
        $capacidad_actual = $info['current_capability'];
        $visibilidad_por_defecto = $info['default_visibility'];
        $capacidad_por_defecto = $info['default_capability'];

        $es_personalizado = ($visibilidad_actual !== $visibilidad_por_defecto) || ($capacidad_actual !== $capacidad_por_defecto);
        ?>
        <div class="flavor-module-card visibility-<?php echo esc_attr($visibilidad_actual); ?>"
             x-show="landingCoincideFiltro('<?php echo esc_js($info['name']); ?>', '', moduleCategoryMap['<?php echo esc_js($module_id); ?>'] || [], (landingTags['<?php echo esc_js($module_id); ?>']?.tipos || []), (landingTags['<?php echo esc_js($module_id); ?>']?.impactos || []))"
             data-module-id="<?php echo esc_attr($module_id); ?>">

            <div class="flavor-module-header">
                <span class="flavor-module-name"><?php echo esc_html($info['name']); ?></span>
                <span class="flavor-visibility-badge <?php echo esc_attr($visibilidad_actual); ?>">
                    <?php echo esc_html($this->obtener_etiqueta_visibilidad($visibilidad_actual)); ?>
                </span>
            </div>

            <div class="flavor-module-controls">
                <div class="flavor-control-group">
                    <label for="visibility-<?php echo esc_attr($module_id); ?>">
                        <?php esc_html_e('Visibilidad', 'flavor-chat-ia'); ?>
                    </label>
                    <select id="visibility-<?php echo esc_attr($module_id); ?>"
                            class="flavor-visibility-select">
                        <?php foreach ($tipos_visibilidad as $valor => $etiqueta) : ?>
                            <option value="<?php echo esc_attr($valor); ?>"
                                    <?php selected($visibilidad_actual, $valor); ?>>
                                <?php echo esc_html($etiqueta); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-control-group">
                    <label for="capability-<?php echo esc_attr($module_id); ?>">
                        <?php esc_html_e('Permiso requerido', 'flavor-chat-ia'); ?>
                    </label>
                    <select id="capability-<?php echo esc_attr($module_id); ?>"
                            class="flavor-capability-select">
                        <?php foreach ($capacidades_disponibles as $valor => $etiqueta) : ?>
                            <option value="<?php echo esc_attr($valor); ?>"
                                    <?php selected($capacidad_actual, $valor); ?>>
                                <?php echo esc_html($etiqueta); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flavor-module-footer">
                <span class="flavor-saving-indicator"></span>
                <?php if ($es_personalizado) : ?>
                    <span class="flavor-default-badge">
                        <?php
                        printf(
                            /* translators: %s: visibilidad por defecto */
                            esc_html__('Por defecto: %s', 'flavor-chat-ia'),
                            esc_html($this->obtener_etiqueta_visibilidad($visibilidad_por_defecto))
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene la etiqueta legible para una visibilidad
     *
     * @param string $visibilidad Tipo de visibilidad
     * @return string Etiqueta legible
     */
    private function obtener_etiqueta_visibilidad($visibilidad) {
        $etiquetas = [
            'public' => __('Publico', 'flavor-chat-ia'),
            'private' => __('Privado', 'flavor-chat-ia'),
            'members_only' => __('Miembros', 'flavor-chat-ia'),
        ];

        return $etiquetas[$visibilidad] ?? $visibilidad;
    }
}
