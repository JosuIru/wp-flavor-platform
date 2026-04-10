<?php
/**
 * Dashboard Tab para Chat Estados (Stories)
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_Estados_Dashboard_Tab {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public function registrar_tabs($tabs) {
        $tabs['estados'] = [
            'label' => __('Estados', 'flavor-platform'),
            'icon' => 'dashicons-format-status',
            'callback' => [$this, 'render_tab'],
            'priority' => 19,
        ];
        return $tabs;
    }

    public function render_tab() {
        ?>
        <div class="flavor-estados-dashboard">
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=estados&subtab=estados" class="subtab <?php echo (!isset($_GET['subtab']) || $_GET['subtab'] === 'estados') ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-format-status"></span> <?php esc_html_e('Estados', 'flavor-platform'); ?>
                </a>
                <a href="?tab=estados&subtab=crear" class="subtab <?php echo (isset($_GET['subtab']) && $_GET['subtab'] === 'crear') ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Crear', 'flavor-platform'); ?>
                </a>
                <a href="?tab=estados&subtab=mis-estados" class="subtab <?php echo (isset($_GET['subtab']) && $_GET['subtab'] === 'mis-estados') ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Mis estados', 'flavor-platform'); ?>
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'estados';
                switch ($subtab) {
                    case 'crear':
                        echo do_shortcode('[flavor_estados_crear]');
                        break;
                    case 'mis-estados':
                        echo do_shortcode('[flavor_estados_mis_estados]');
                        break;
                    default:
                        echo do_shortcode('[flavor_estados]');
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_estados = $wpdb->prefix . 'flavor_chat_estados';
        $tabla_vistas = $wpdb->prefix . 'flavor_chat_estados_vistas';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        // Mis estados activos (no expirados)
        $mis_estados = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, (SELECT COUNT(*) FROM $tabla_vistas WHERE estado_id = e.id) as vistas
             FROM $tabla_estados e
             WHERE e.usuario_id = %d AND e.activo = 1 AND e.fecha_expiracion > NOW()
             ORDER BY e.fecha_creacion DESC",
            $user_id
        ));

        // Estados de contactos (personas que sigo)
        $estados_contactos = $wpdb->get_results($wpdb->prepare(
            "SELECT e.usuario_id, u.display_name as nombre,
                    MAX(e.fecha_creacion) as ultimo_estado,
                    EXISTS(SELECT 1 FROM $tabla_vistas WHERE estado_id = e.id AND usuario_id = %d) as visto
             FROM $tabla_estados e
             JOIN {$wpdb->users} u ON e.usuario_id = u.ID
             JOIN $tabla_seguimientos s ON e.usuario_id = s.seguido_id AND s.seguidor_id = %d
             WHERE e.activo = 1 AND e.fecha_expiracion > NOW()
             GROUP BY e.usuario_id
             ORDER BY ultimo_estado DESC",
            $user_id, $user_id
        ));

        return [
            'mis_estados' => $mis_estados ?: [],
            'estados_contactos' => $estados_contactos ?: [],
        ];
    }
}

$dashboard_tab_class = 'Flavor_Platform_Estados_Dashboard_Tab';
if (!class_exists('Flavor_Chat_Estados_Dashboard_Tab', false)) {
    class_alias('Flavor_Platform_Estados_Dashboard_Tab', 'Flavor_Chat_Estados_Dashboard_Tab');
}
$dashboard_tab_class::get_instance();
