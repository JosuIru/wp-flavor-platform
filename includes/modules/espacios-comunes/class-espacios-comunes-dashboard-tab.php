<?php
/**
 * Dashboard Tab para Espacios Comunes
 *
 * @package FlavorChatIA
 * @subpackage Modules\EspaciosComunes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Espacios_Comunes_Dashboard_Tab {

    private static $instancia = null;

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function registrar_tabs($tabs) {
        $tabs['espacios-disponibles'] = [
            'label' => __('Espacios', 'flavor-chat-ia'),
            'icon' => 'building',
            'callback' => [$this, 'render_tab_espacios'],
            'orden' => 45,
        ];

        $tabs['espacios-mis-reservas'] = [
            'label' => __('Mis Reservas', 'flavor-chat-ia'),
            'icon' => 'calendar-alt',
            'callback' => [$this, 'render_tab_mis_reservas'],
            'orden' => 46,
        ];

        return $tabs;
    }

    public function render_tab_espacios() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_espacios_comunes';

        $total_espacios = 0;
        $espacios = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $total_espacios = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'activo'"
            );

            $espacios = $wpdb->get_results(
                "SELECT * FROM {$tabla}
                 WHERE estado = 'activo'
                 ORDER BY nombre ASC LIMIT 8"
            );
        }

        ?>
        <div class="flavor-panel flavor-espacios-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-building"></span> <?php esc_html_e('Espacios Comunes', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Reserva salas, locales y espacios para tus actividades', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-building"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_espacios); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Espacios Disponibles', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($espacios)): ?>
                <div class="flavor-cards-grid flavor-cards-grid-4">
                    <?php foreach ($espacios as $espacio): ?>
                        <div class="flavor-card flavor-espacio-card">
                            <?php if (!empty($espacio->imagen_destacada)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($espacio->imagen_destacada); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <h4><?php echo esc_html($espacio->nombre); ?></h4>
                                <?php if (!empty($espacio->capacidad)): ?>
                                    <p class="flavor-text-muted">
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php printf(esc_html__('Hasta %d personas', 'flavor-chat-ia'), $espacio->capacidad); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($espacio->equipamiento)): ?>
                                    <p class="flavor-text-muted flavor-text-truncate">
                                        <?php echo esc_html(wp_trim_words($espacio->equipamiento, 8)); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-footer">
                                <?php if ($espacio->precio_hora > 0): ?>
                                    <span class="flavor-precio"><?php echo number_format_i18n($espacio->precio_hora, 2); ?> €/h</span>
                                <?php else: ?>
                                    <span class="flavor-badge flavor-badge-success"><?php esc_html_e('Gratis', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(home_url('/espacios/' . $espacio->slug)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php esc_html_e('Reservar', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-building"></span>
                    <p><?php esc_html_e('No hay espacios disponibles en este momento.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/espacios-comunes/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <?php esc_html_e('Ver todos los espacios', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    public function render_tab_mis_reservas() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $reservas = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_reservas) && Flavor_Chat_Helpers::tabla_existe($tabla_espacios)) {
            $reservas = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, e.nombre as espacio_nombre, e.slug as espacio_slug
                 FROM {$tabla_reservas} r
                 JOIN {$tabla_espacios} e ON r.espacio_id = e.id
                 WHERE r.usuario_id = %d
                 ORDER BY r.fecha_inicio DESC LIMIT 10",
                $user_id
            ));
        }

        $estados_colores = [
            'pendiente' => 'warning',
            'confirmada' => 'success',
            'cancelada' => 'danger',
            'completada' => 'secondary',
        ];

        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Mis Reservas', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url(home_url('/espacios-comunes/')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nueva Reserva', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($reservas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No tienes reservas de espacios.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/espacios-comunes/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Reservar un espacio', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Espacio', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Horario', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $reserva): ?>
                                <tr>
                                    <td><?php echo esc_html($reserva->espacio_nombre); ?></td>
                                    <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($reserva->fecha_inicio))); ?></td>
                                    <td>
                                        <?php echo esc_html(date_i18n('H:i', strtotime($reserva->fecha_inicio))); ?>
                                        -
                                        <?php echo esc_html(date_i18n('H:i', strtotime($reserva->fecha_fin))); ?>
                                    </td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_colores[$reserva->estado] ?? 'secondary'); ?>">
                                            <?php echo esc_html(ucfirst($reserva->estado)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(home_url('/espacios/' . $reserva->espacio_slug)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                        </a>
                                        <?php if ($reserva->estado === 'pendiente' || $reserva->estado === 'confirmada'): ?>
                                            <?php if (strtotime($reserva->fecha_inicio) > current_time('timestamp')): ?>
                                                <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-danger" data-cancelar-reserva="<?php echo esc_attr($reserva->id); ?>">
                                                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
