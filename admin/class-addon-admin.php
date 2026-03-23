<?php
/**
 * Panel de Administración de Addons
 *
 * Interfaz para gestionar addons instalados y disponibles
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para el panel de administración de addons
 *
 * @since 3.0.0
 */
class Flavor_Addon_Admin {

    /**
     * Instancia singleton
     *
     * @var Flavor_Addon_Admin
     */
    private static $instancia = null;

    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Addon_Admin
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Menú registrado centralmente por Flavor_Admin_Menu_Manager

        // Procesar acciones
        add_action('admin_init', [$this, 'process_actions']);

        // Registrar scripts y estilos
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Agrega la página de menú
     *
     * @return void
     */
    public function add_menu_page() {
        add_submenu_page(
            'flavor-chat-ia',  // Parent slug
            __('Addons', 'flavor-chat-ia'),
            __('Addons', 'flavor-chat-ia'),
            'manage_options',
            'flavor-addons',
            [$this, 'render_addons_page']
        );
    }

    /**
     * Procesa acciones de activación/desactivación
     *
     * @return void
     */
    public function process_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Verificar nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'flavor_addon_action')) {
            return;
        }

        $accion = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $slug_addon = isset($_GET['addon']) ? sanitize_text_field($_GET['addon']) : '';

        if (empty($accion) || empty($slug_addon)) {
            return;
        }

        $gestor_addons = Flavor_Addon_Manager::get_instance();

        switch ($accion) {
            case 'activate':
                $resultado = $gestor_addons->activate_addon($slug_addon);

                if (is_wp_error($resultado)) {
                    add_settings_error(
                        'flavor_addons',
                        'addon_activation_error',
                        $resultado->get_error_message(),
                        'error'
                    );
                } else {
                    add_settings_error(
                        'flavor_addons',
                        'addon_activation_success',
                        __('Addon activado correctamente.', 'flavor-chat-ia'),
                        'success'
                    );
                }
                break;

            case 'deactivate':
                $gestor_addons->deactivate_addon($slug_addon);
                add_settings_error(
                    'flavor_addons',
                    'addon_deactivation_success',
                    __('Addon desactivado correctamente.', 'flavor-chat-ia'),
                    'success'
                );
                break;
        }

        // Redirigir para evitar reenvío de formulario
        Flavor_Chat_Helpers::safe_redirect(admin_url('admin.php?page=flavor-addons'));
        exit;
    }

    /**
     * Registra assets del admin
     *
     * @param string $hook_suffix Sufijo del hook
     * @return void
     */
    public function enqueue_admin_assets($hook_suffix) {
        // El hook puede variar según el parent del menú
        if (strpos($hook_suffix, 'flavor-addons') === false) {
            return;
        }

        // Estilos inline para la página de addons
        $css_personalizado = "
            .flavor-addons-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            .flavor-addon-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                position: relative;
            }
            .flavor-addon-card.active {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .flavor-addon-header {
                display: flex;
                align-items: center;
                margin-bottom: 15px;
            }
            .flavor-addon-icon {
                font-size: 32px;
                margin-right: 15px;
                color: #2271b1;
            }
            .flavor-addon-title {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
            }
            .flavor-addon-version {
                color: #646970;
                font-size: 12px;
            }
            .flavor-addon-description {
                color: #50575e;
                font-size: 13px;
                margin-bottom: 15px;
            }
            .flavor-addon-meta {
                display: flex;
                gap: 10px;
                margin-bottom: 15px;
                font-size: 12px;
                color: #646970;
            }
            .flavor-addon-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .flavor-addon-badge.active {
                background: #00a32a;
                color: #fff;
            }
            .flavor-addon-badge.inactive {
                background: #dcdcde;
                color: #50575e;
            }
            .flavor-addon-badge.premium {
                background: #f0c33c;
                color: #000;
            }
            .flavor-addon-actions {
                display: flex;
                gap: 10px;
            }
            .flavor-addon-empty {
                text-align: center;
                padding: 60px 20px;
                color: #646970;
            }
            .flavor-addon-empty .dashicons {
                font-size: 80px;
                width: 80px;
                height: 80px;
                color: #c3c4c7;
                margin-bottom: 20px;
            }
        ";

        wp_add_inline_style('wp-admin', $css_personalizado);
    }

    /**
     * Renderiza la página de addons
     *
     * @return void
     */
    public function render_addons_page() {
        $addons_registrados = Flavor_Addon_Manager::get_registered_addons();
        $addons_activos = Flavor_Addon_Manager::get_active_addons();
        $estadisticas = Flavor_Addon_Manager::get_stats();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Addons de Flavor Platform', 'flavor-chat-ia'); ?></h1>

            <?php settings_errors('flavor_addons'); ?>

            <p class="description">
                <?php echo esc_html__('Los addons extienden la funcionalidad de Flavor Platform con características adicionales.', 'flavor-chat-ia'); ?>
            </p>

            <!-- Estadísticas -->
            <div style="display: flex; gap: 15px; margin: 20px 0;">
                <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 15px; flex: 1;">
                    <div style="font-size: 24px; font-weight: 600; color: #2271b1;">
                        <?php echo esc_html($estadisticas['total_registrados']); ?>
                    </div>
                    <div style="color: #646970; font-size: 13px;">
                        <?php echo esc_html__('Addons Disponibles', 'flavor-chat-ia'); ?>
                    </div>
                </div>
                <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 15px; flex: 1;">
                    <div style="font-size: 24px; font-weight: 600; color: #00a32a;">
                        <?php echo esc_html($estadisticas['total_activos']); ?>
                    </div>
                    <div style="color: #646970; font-size: 13px;">
                        <?php echo esc_html__('Addons Activos', 'flavor-chat-ia'); ?>
                    </div>
                </div>
                <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 15px; flex: 1;">
                    <div style="font-size: 24px; font-weight: 600; color: #f0c33c;">
                        <?php echo esc_html($estadisticas['premium_count']); ?>
                    </div>
                    <div style="color: #646970; font-size: 13px;">
                        <?php echo esc_html__('Addons Premium', 'flavor-chat-ia'); ?>
                    </div>
                </div>
            </div>

            <?php if (empty($addons_registrados)): ?>
                <!-- Sin addons -->
                <div class="flavor-addon-empty">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <h2><?php echo esc_html__('No se detectaron addons', 'flavor-chat-ia'); ?></h2>
                    <p>
                        <?php echo esc_html__('Los addons son extensiones integradas que amplían las funcionalidades de Flavor Platform. Coloca los addons en la carpeta addons/ del plugin.', 'flavor-chat-ia'); ?>
                    </p>
                    <p>
                        <code><?php echo esc_html(FLAVOR_CHAT_IA_PATH . 'addons/'); ?></code>
                    </p>
                </div>

            <?php else: ?>
                <!-- Grid de addons -->
                <div class="flavor-addons-grid">
                    <?php foreach ($addons_registrados as $slug_addon => $configuracion): ?>
                        <?php
                        $es_activo = in_array($slug_addon, $addons_activos);
                        $clase_card = $es_activo ? 'flavor-addon-card active' : 'flavor-addon-card';
                        $url_accion = wp_nonce_url(
                            admin_url('admin.php?page=flavor-addons&action=' . ($es_activo ? 'deactivate' : 'activate') . '&addon=' . $slug_addon),
                            'flavor_addon_action'
                        );
                        ?>
                        <div class="<?php echo esc_attr($clase_card); ?>">
                            <!-- Header -->
                            <div class="flavor-addon-header">
                                <span class="dashicons <?php echo esc_attr($configuracion['icon']); ?> flavor-addon-icon"></span>
                                <div>
                                    <h3 class="flavor-addon-title">
                                        <?php echo esc_html($configuracion['name']); ?>
                                    </h3>
                                    <div class="flavor-addon-version">
                                        v<?php echo esc_html($configuracion['version']); ?>
                                        <?php if (!empty($configuracion['author'])): ?>
                                            | <?php echo esc_html($configuracion['author']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Badges -->
                            <div style="margin-bottom: 15px;">
                                <span class="flavor-addon-badge <?php echo $es_activo ? 'active' : 'inactive'; ?>">
                                    <?php echo $es_activo ? esc_html__('Activo', 'flavor-chat-ia') : esc_html__('Inactivo', 'flavor-chat-ia'); ?>
                                </span>
                                <?php if (!empty($configuracion['is_premium'])): ?>
                                    <span class="flavor-addon-badge premium">
                                        <?php echo esc_html__('Premium', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Descripción -->
                            <?php if (!empty($configuracion['description'])): ?>
                                <p class="flavor-addon-description">
                                    <?php echo esc_html($configuracion['description']); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Meta -->
                            <div class="flavor-addon-meta">
                                <?php if (!empty($configuracion['requires_core'])): ?>
                                    <span>
                                        <?php echo esc_html__('Requiere Core:', 'flavor-chat-ia'); ?>
                                        <?php echo esc_html($configuracion['requires_core']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Acciones -->
                            <div class="flavor-addon-actions">
                                <?php if ($es_activo): ?>
                                    <a href="<?php echo esc_url($url_accion); ?>" class="button">
                                        <?php echo esc_html__('Desactivar', 'flavor-chat-ia'); ?>
                                    </a>
                                    <?php if (!empty($configuracion['settings_page'])): ?>
                                        <a href="<?php echo esc_url(admin_url($configuracion['settings_page'])); ?>" class="button button-primary">
                                            <?php echo esc_html__('Configurar', 'flavor-chat-ia'); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($url_accion); ?>" class="button button-primary">
                                        <?php echo esc_html__('Activar', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($configuracion['documentation_url'])): ?>
                                    <a href="<?php echo esc_url($configuracion['documentation_url']); ?>" class="button" target="_blank">
                                        <?php echo esc_html__('Documentación', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Información adicional -->
            <div style="margin-top: 30px; padding: 20px; background: #f0f6fc; border-left: 4px solid #2271b1;">
                <h3><?php echo esc_html__('¿Qué son los Addons?', 'flavor-chat-ia'); ?></h3>
                <p>
                    <?php echo esc_html__('Los addons son extensiones integradas que añaden funcionalidades específicas a Flavor Platform. Se ubican en la carpeta addons/ del plugin y se activan individualmente desde esta página, permitiendo personalizar tu instalación con solo las características que necesitas.', 'flavor-chat-ia'); ?>
                </p>
                <p>
                    <strong><?php echo esc_html__('Ventajas:', 'flavor-chat-ia'); ?></strong>
                </p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php echo esc_html__('Instala solo lo que necesitas', 'flavor-chat-ia'); ?></li>
                    <li><?php echo esc_html__('Mejor rendimiento (menos código cargado)', 'flavor-chat-ia'); ?></li>
                    <li><?php echo esc_html__('Actualizaciones independientes', 'flavor-chat-ia'); ?></li>
                    <li><?php echo esc_html__('Fácil mantenimiento y debugging', 'flavor-chat-ia'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
