<?php
/**
 * Dashboard Tab para Bug Tracker
 *
 * @package Flavor_Chat_IA
 * @subpackage Bug_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona el tab de Bug Tracker en el dashboard
 */
class Flavor_Bug_Tracker_Dashboard_Tab {

    /**
     * Instancia del módulo principal
     *
     * @var Flavor_Bug_Tracker_Module
     */
    private $modulo;

    /**
     * Constructor
     *
     * @param Flavor_Bug_Tracker_Module $modulo Instancia del módulo
     */
    public function __construct(Flavor_Bug_Tracker_Module $modulo) {
        $this->modulo = $modulo;

        // Registrar el widget de dashboard
        add_action('flavor_register_dashboard_widgets', [$this, 'registrar_widget']);

        // Añadir contador al menú admin
        add_filter('flavor_admin_menu_badges', [$this, 'agregar_badge_menu']);
    }

    /**
     * Registra el widget de dashboard
     *
     * @return void
     */
    public function registrar_widget() {
        if (!function_exists('flavor_register_dashboard_widget')) {
            return;
        }

        flavor_register_dashboard_widget([
            'id' => 'bug-tracker-summary',
            'title' => __('Bugs Recientes', 'flavor-chat-ia'),
            'callback' => [$this, 'render_widget'],
            'context' => 'side',
            'priority' => 'high',
            'capability' => 'manage_options',
        ]);
    }

    /**
     * Renderiza el widget del dashboard
     *
     * @return void
     */
    public function render_widget() {
        $estadisticas = $this->modulo->obtener_estadisticas();
        $bugs_recientes = $this->modulo->listar_bugs([
            'estado' => 'nuevo',
            'limit' => 5,
        ]);

        $colores_severidad = [
            'critical' => '#dc2626',
            'high' => '#ea580c',
            'medium' => '#ca8a04',
            'low' => '#2563eb',
            'info' => '#6b7280',
        ];

        ?>
        <div class="flavor-bug-tracker-widget">
            <div class="bug-stats-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                <div class="stat-card" style="background: #fef2f2; padding: 10px; border-radius: 6px; text-align: center;">
                    <span style="font-size: 24px; font-weight: bold; color: #dc2626;">
                        <?php echo esc_html($estadisticas['por_estado']['nuevo'] ?? 0); ?>
                    </span>
                    <br><small><?php esc_html_e('Nuevos', 'flavor-chat-ia'); ?></small>
                </div>
                <div class="stat-card" style="background: #fef3c7; padding: 10px; border-radius: 6px; text-align: center;">
                    <span style="font-size: 24px; font-weight: bold; color: #d97706;">
                        <?php echo esc_html($estadisticas['por_estado']['abierto'] ?? 0); ?>
                    </span>
                    <br><small><?php esc_html_e('Abiertos', 'flavor-chat-ia'); ?></small>
                </div>
                <div class="stat-card" style="background: #f0fdf4; padding: 10px; border-radius: 6px; text-align: center;">
                    <span style="font-size: 24px; font-weight: bold; color: #16a34a;">
                        <?php echo esc_html($estadisticas['por_estado']['resuelto'] ?? 0); ?>
                    </span>
                    <br><small><?php esc_html_e('Resueltos', 'flavor-chat-ia'); ?></small>
                </div>
                <div class="stat-card" style="background: #f3f4f6; padding: 10px; border-radius: 6px; text-align: center;">
                    <span style="font-size: 24px; font-weight: bold; color: #6b7280;">
                        <?php echo esc_html($estadisticas['ultimas_24h']); ?>
                    </span>
                    <br><small><?php esc_html_e('Últimas 24h', 'flavor-chat-ia'); ?></small>
                </div>
            </div>

            <?php if (!empty($bugs_recientes['bugs'])) : ?>
                <h4 style="margin: 15px 0 10px; font-size: 13px; color: #666;">
                    <?php esc_html_e('Bugs más recientes', 'flavor-chat-ia'); ?>
                </h4>
                <ul style="margin: 0; padding: 0; list-style: none;">
                    <?php foreach ($bugs_recientes['bugs'] as $bug) : ?>
                        <li style="padding: 8px 0; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 8px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo esc_attr($colores_severidad[$bug->severidad] ?? '#6b7280'); ?>;"></span>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bug-tracker&bug_id=' . $bug->id)); ?>" style="flex: 1; text-decoration: none; color: #333; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo esc_html($bug->codigo); ?>: <?php echo esc_html(mb_substr($bug->titulo, 0, 40)); ?>
                            </a>
                            <?php if ($bug->ocurrencias > 1) : ?>
                                <span style="background: #e5e7eb; color: #374151; padding: 2px 6px; border-radius: 10px; font-size: 10px;">
                                    x<?php echo esc_html($bug->ocurrencias); ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p style="color: #16a34a; text-align: center; padding: 20px 0;">
                    ✓ <?php esc_html_e('No hay bugs nuevos', 'flavor-chat-ia'); ?>
                </p>
            <?php endif; ?>

            <p style="margin-top: 15px; text-align: center;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bug-tracker')); ?>" class="button button-secondary">
                    <?php esc_html_e('Ver todos los bugs', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Agrega badge al menú de administración
     *
     * @param array $badges Badges existentes
     * @return array
     */
    public function agregar_badge_menu($badges) {
        $bugs_nuevos = $this->modulo->listar_bugs([
            'estado' => 'nuevo',
            'severidad' => 'critical',
            'limit' => 1,
        ]);

        if ($bugs_nuevos['total'] > 0) {
            $badges['flavor-bug-tracker'] = [
                'count' => $bugs_nuevos['total'],
                'class' => 'update-plugins count-' . $bugs_nuevos['total'],
            ];
        }

        return $badges;
    }

    /**
     * Renderiza el resumen rápido para notificaciones
     *
     * @return string
     */
    public function render_resumen_rapido() {
        $estadisticas = $this->modulo->obtener_estadisticas();

        $criticos = $estadisticas['por_severidad']['critical'] ?? 0;
        $altos = $estadisticas['por_severidad']['high'] ?? 0;
        $nuevos = $estadisticas['por_estado']['nuevo'] ?? 0;

        $items = [];

        if ($criticos > 0) {
            $items[] = sprintf('%d críticos', $criticos);
        }
        if ($altos > 0) {
            $items[] = sprintf('%d altos', $altos);
        }
        if ($nuevos > 0) {
            $items[] = sprintf('%d nuevos', $nuevos);
        }

        if (empty($items)) {
            return __('Sin bugs pendientes', 'flavor-chat-ia');
        }

        return implode(', ', $items);
    }
}
