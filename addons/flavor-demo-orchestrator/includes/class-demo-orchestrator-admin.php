<?php

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Demo_Orchestrator_Admin {

    /**
     * @var Flavor_Demo_Orchestrator_Admin|null
     */
    private static $instance = null;

    /**
     * @return Flavor_Demo_Orchestrator_Admin
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        add_filter('flavor_demo_tools_rendered_by_addon', [$this, 'force_addon_rendering'], 10, 2);
        add_action('flavor_app_config_tools_cards', [$this, 'render_tools_card'], 10, 1);
    }

    /**
     * Fuerza que el bloque de datos demo lo pinte el addon.
     *
     * @param bool $current
     * @param array $modulos_activos
     * @return bool
     */
    public function force_addon_rendering($current, $modulos_activos) {
        return true;
    }

    /**
     * Renderiza tarjeta de acciones de datos demo.
     *
     * @param array $modulos_activos
     */
    public function render_tools_card($modulos_activos) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $modulos_activos = is_array($modulos_activos)
            ? array_values(array_filter(array_map('sanitize_key', $modulos_activos)))
            : [];

        $historial = get_option('flavor_demo_data_last_runs', []);
        $historial = is_array($historial) ? array_slice($historial, 0, 10) : [];
        ?>
        <div class="tool-card">
            <h3><span class="dashicons dashicons-database-import"></span> <?php _e('Datos Demo', 'flavor-demo-orchestrator'); ?></h3>
            <p><?php _e('Carga o elimina datos de demostración para los módulos del plugin.', 'flavor-demo-orchestrator'); ?></p>
            <p style="margin-top: -8px; color:#646970;">
                <?php echo esc_html(sprintf(__('Módulos activos detectados: %d', 'flavor-demo-orchestrator'), count($modulos_activos))); ?>
            </p>

            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:8px;">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('flavor_demo_data_action'); ?>
                    <input type="hidden" name="action" value="flavor_populate_demo_data">
                    <input type="hidden" name="modulo_id" value="all">
                    <input type="hidden" name="redirect_page" value="flavor-apps-config">
                    <input type="hidden" name="redirect_tab" value="tools">
                    <?php foreach ($modulos_activos as $modulo_activo): ?>
                        <input type="hidden" name="modulos_activos[]" value="<?php echo esc_attr($modulo_activo); ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="button button-primary">
                        <?php _e('Poblar activos', 'flavor-demo-orchestrator'); ?>
                    </button>
                </form>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('flavor_demo_data_action'); ?>
                    <input type="hidden" name="action" value="flavor_populate_demo_data">
                    <input type="hidden" name="modulo_id" value="all">
                    <input type="hidden" name="redirect_page" value="flavor-apps-config">
                    <input type="hidden" name="redirect_tab" value="tools">
                    <button type="submit" class="button">
                        <?php _e('Poblar todos', 'flavor-demo-orchestrator'); ?>
                    </button>
                </form>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('¿Seguro que quieres eliminar los datos demo de módulos activos?', 'flavor-demo-orchestrator')); ?>');">
                    <?php wp_nonce_field('flavor_demo_data_action'); ?>
                    <input type="hidden" name="action" value="flavor_clear_demo_data">
                    <input type="hidden" name="modulo_id" value="all">
                    <input type="hidden" name="redirect_page" value="flavor-apps-config">
                    <input type="hidden" name="redirect_tab" value="tools">
                    <?php foreach ($modulos_activos as $modulo_activo): ?>
                        <input type="hidden" name="modulos_activos[]" value="<?php echo esc_attr($modulo_activo); ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="button button-secondary">
                        <?php _e('Limpiar activos', 'flavor-demo-orchestrator'); ?>
                    </button>
                </form>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('¿Seguro que quieres eliminar todos los datos demo?', 'flavor-demo-orchestrator')); ?>');">
                    <?php wp_nonce_field('flavor_demo_data_action'); ?>
                    <input type="hidden" name="action" value="flavor_clear_demo_data">
                    <input type="hidden" name="modulo_id" value="all">
                    <input type="hidden" name="redirect_page" value="flavor-apps-config">
                    <input type="hidden" name="redirect_tab" value="tools">
                    <button type="submit" class="button">
                        <?php _e('Limpiar todos', 'flavor-demo-orchestrator'); ?>
                    </button>
                </form>
            </div>

            <?php $this->render_history_table($historial); ?>
        </div>
        <?php
    }

    /**
     * Renderiza tabla de historial.
     *
     * @param array $historial
     */
    private function render_history_table(array $historial) {
        ?>
        <div style="margin-top:16px;">
            <h4 style="margin:0 0 8px;"><?php _e('Últimas ejecuciones', 'flavor-demo-orchestrator'); ?></h4>
            <?php if (empty($historial)) : ?>
                <p style="margin:0; color:#646970;"><?php _e('Todavía no hay ejecuciones registradas.', 'flavor-demo-orchestrator'); ?></p>
            <?php else : ?>
                <table class="widefat striped" style="margin:0;">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha', 'flavor-demo-orchestrator'); ?></th>
                            <th><?php _e('Acción', 'flavor-demo-orchestrator'); ?></th>
                            <th><?php _e('Alcance', 'flavor-demo-orchestrator'); ?></th>
                            <th><?php _e('Módulos', 'flavor-demo-orchestrator'); ?></th>
                            <th><?php _e('Resultado', 'flavor-demo-orchestrator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $run) : ?>
                            <tr>
                                <td><?php echo esc_html($this->format_timestamp($run['timestamp'] ?? '')); ?></td>
                                <td><?php echo esc_html($this->format_action($run['action'] ?? '')); ?></td>
                                <td><?php echo esc_html($this->format_scope($run['scope'] ?? '')); ?></td>
                                <td><?php echo esc_html($this->format_modules($run['target_modules'] ?? [])); ?></td>
                                <td><?php echo esc_html(sprintf('%d/%d', (int) ($run['success'] ?? 0), (int) ($run['attempted'] ?? 0))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * @param string $timestamp
     * @return string
     */
    private function format_timestamp($timestamp) {
        if (empty($timestamp)) {
            return '-';
        }

        $unix = strtotime($timestamp);
        if ($unix === false) {
            return (string) $timestamp;
        }

        return wp_date('Y-m-d H:i', $unix);
    }

    /**
     * @param string $action
     * @return string
     */
    private function format_action($action) {
        return $action === 'clear'
            ? __('Limpiar', 'flavor-demo-orchestrator')
            : __('Poblar', 'flavor-demo-orchestrator');
    }

    /**
     * @param string $scope
     * @return string
     */
    private function format_scope($scope) {
        if ($scope === 'single') {
            return __('Módulo único', 'flavor-demo-orchestrator');
        }
        if ($scope === 'active') {
            return __('Solo activos', 'flavor-demo-orchestrator');
        }

        return __('Todos', 'flavor-demo-orchestrator');
    }

    /**
     * @param array $modules
     * @return string
     */
    private function format_modules($modules) {
        if (!is_array($modules) || empty($modules)) {
            return __('(todos)', 'flavor-demo-orchestrator');
        }

        $modules = array_map('sanitize_key', $modules);
        return implode(', ', $modules);
    }
}
