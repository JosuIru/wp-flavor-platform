<?php
/**
 * Modal de confirmación para desactivación del plugin
 *
 * Muestra un diálogo preguntando al usuario si desea conservar
 * o eliminar los datos al desinstalar el plugin.
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar el modal de desactivación
 */
class Flavor_Deactivation_Modal {

    /**
     * Instancia singleton
     *
     * @var Flavor_Deactivation_Modal|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Deactivation_Modal
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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_footer', [$this, 'render_modal']);
        add_action('wp_ajax_flavor_save_uninstall_preference', [$this, 'save_uninstall_preference']);
    }

    /**
     * Encola scripts solo en la página de plugins
     *
     * @param string $hook Hook de la página actual
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'plugins.php') {
            return;
        }

        wp_enqueue_script(
            'flavor-deactivation-modal',
            FLAVOR_CHAT_IA_URL . 'assets/js/deactivation-modal.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-deactivation-modal', 'flavorDeactivation', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_deactivation_nonce'),
            'pluginSlug' => 'flavor-chat-ia/flavor-chat-ia.php',
            'i18n' => [
                'title' => __('Desactivar Flavor Platform', 'flavor-chat-ia'),
                'subtitle' => __('¿Qué deseas hacer con los datos del plugin?', 'flavor-chat-ia'),
                'keepData' => __('Conservar datos', 'flavor-chat-ia'),
                'keepDataDesc' => __('Los datos se mantendrán para cuando vuelvas a activar el plugin.', 'flavor-chat-ia'),
                'deleteData' => __('Eliminar datos', 'flavor-chat-ia'),
                'deleteDataDesc' => __('Todos los datos serán eliminados permanentemente al desinstalar.', 'flavor-chat-ia'),
                'cancel' => __('Cancelar', 'flavor-chat-ia'),
                'deactivate' => __('Desactivar', 'flavor-chat-ia'),
                'warning' => __('Esta acción no se puede deshacer si eliges eliminar los datos.', 'flavor-chat-ia'),
            ],
        ]);

        // Estilos inline para el modal
        wp_add_inline_style('wp-admin', $this->get_modal_styles());
    }

    /**
     * Obtiene los estilos CSS del modal
     *
     * @return string
     */
    private function get_modal_styles() {
        return '
        .flavor-deactivation-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 100100;
            justify-content: center;
            align-items: center;
        }
        .flavor-deactivation-overlay.active {
            display: flex;
        }
        .flavor-deactivation-modal {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            padding: 0;
            animation: flavorModalSlide 0.3s ease-out;
        }
        @keyframes flavorModalSlide {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .flavor-deactivation-modal__header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        .flavor-deactivation-modal__header h2 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        .flavor-deactivation-modal__header p {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
        }
        .flavor-deactivation-modal__body {
            padding: 20px 24px;
        }
        .flavor-deactivation-option {
            display: flex;
            align-items: flex-start;
            padding: 16px;
            margin-bottom: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .flavor-deactivation-option:last-child {
            margin-bottom: 0;
        }
        .flavor-deactivation-option:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }
        .flavor-deactivation-option.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .flavor-deactivation-option.delete-option.selected {
            border-color: #dc2626;
            background: #fef2f2;
        }
        .flavor-deactivation-option input[type="radio"] {
            margin-top: 2px;
            margin-right: 12px;
        }
        .flavor-deactivation-option__content h4 {
            margin: 0 0 4px;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }
        .flavor-deactivation-option__content p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }
        .flavor-deactivation-option.delete-option h4 {
            color: #dc2626;
        }
        .flavor-deactivation-warning {
            display: none;
            padding: 12px 16px;
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            margin-top: 16px;
            font-size: 13px;
            color: #92400e;
        }
        .flavor-deactivation-warning.visible {
            display: block;
        }
        .flavor-deactivation-warning::before {
            content: "⚠️ ";
        }
        .flavor-deactivation-modal__footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 0 0 12px 12px;
        }
        .flavor-deactivation-modal__footer .button {
            padding: 8px 20px;
            font-size: 14px;
            border-radius: 6px;
        }
        .flavor-deactivation-modal__footer .button-primary {
            background: #3b82f6;
            border-color: #3b82f6;
        }
        .flavor-deactivation-modal__footer .button-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }
        .flavor-deactivation-modal__footer .button-delete {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
        }
        .flavor-deactivation-modal__footer .button-delete:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }
        ';
    }

    /**
     * Renderiza el HTML del modal en el footer
     */
    public function render_modal() {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'plugins') {
            return;
        }
        ?>
        <div id="flavor-deactivation-overlay" class="flavor-deactivation-overlay">
            <div class="flavor-deactivation-modal" role="dialog" aria-modal="true" aria-labelledby="flavor-deactivation-title">
                <div class="flavor-deactivation-modal__header">
                    <h2 id="flavor-deactivation-title"><?php esc_html_e('Desactivar Flavor Platform', 'flavor-chat-ia'); ?></h2>
                    <p><?php esc_html_e('¿Qué deseas hacer con los datos del plugin?', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-deactivation-modal__body">
                    <label class="flavor-deactivation-option selected" data-value="keep">
                        <input type="radio" name="flavor_uninstall_data" value="keep" checked>
                        <div class="flavor-deactivation-option__content">
                            <h4><?php esc_html_e('Conservar datos', 'flavor-chat-ia'); ?></h4>
                            <p><?php esc_html_e('Los datos se mantendrán para cuando vuelvas a activar el plugin.', 'flavor-chat-ia'); ?></p>
                        </div>
                    </label>
                    <label class="flavor-deactivation-option delete-option" data-value="delete">
                        <input type="radio" name="flavor_uninstall_data" value="delete">
                        <div class="flavor-deactivation-option__content">
                            <h4><?php esc_html_e('Eliminar datos al desinstalar', 'flavor-chat-ia'); ?></h4>
                            <p><?php esc_html_e('Todos los datos serán eliminados permanentemente al desinstalar el plugin.', 'flavor-chat-ia'); ?></p>
                        </div>
                    </label>
                    <div id="flavor-deactivation-warning" class="flavor-deactivation-warning">
                        <?php esc_html_e('Esta acción no se puede deshacer. Todas las tablas, opciones y datos personalizados serán eliminados permanentemente.', 'flavor-chat-ia'); ?>
                    </div>
                </div>
                <div class="flavor-deactivation-modal__footer">
                    <button type="button" class="button" id="flavor-deactivation-cancel">
                        <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="flavor-deactivation-confirm">
                        <?php esc_html_e('Desactivar', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Guarda la preferencia de desinstalación via AJAX
     */
    public function save_uninstall_preference() {
        check_ajax_referer('flavor_deactivation_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'flavor-chat-ia')]);
        }

        $delete_data = isset($_POST['delete_data']) && $_POST['delete_data'] === 'true';

        $settings = get_option('flavor_chat_ia_settings', []);
        $settings['limpiar_al_desinstalar'] = $delete_data;
        update_option('flavor_chat_ia_settings', $settings);

        wp_send_json_success([
            'message' => $delete_data
                ? __('Los datos serán eliminados al desinstalar el plugin.', 'flavor-chat-ia')
                : __('Los datos serán conservados.', 'flavor-chat-ia'),
            'delete_data' => $delete_data,
        ]);
    }
}

// Inicializar en admin
if (is_admin()) {
    Flavor_Deactivation_Modal::get_instance();
}
