<?php
/**
 * Dashboard Tab para Eventos
 *
 * @package FlavorPlatform
 * @subpackage Modules\Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Eventos_Dashboard_Tab {

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
        $tabs['eventos-proximos'] = [
            'label' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'calendar-alt',
            'callback' => [$this, 'render_tab_proximos'],
            'orden' => 30,
        ];

        $tabs['eventos-mis-inscripciones'] = [
            'label' => __('Mis Inscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'tickets-alt',
            'callback' => [$this, 'render_tab_inscripciones'],
            'orden' => 31,
        ];

        return $tabs;
    }

    public function render_tab_proximos() {
        $user_id = get_current_user_id();
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        $total_eventos = 0;
        $proximos = 0;
        $mis_inscripciones = 0;
        $eventos_proximos = [];

        if (Flavor_Platform_Helpers::tabla_existe($tabla_eventos)) {
            $total_eventos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_eventos} WHERE estado = 'publicado'"
            );

            $proximos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_eventos} WHERE estado = 'publicado' AND fecha_inicio >= %s",
                current_time('mysql')
            ));

            $eventos_proximos = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_eventos}
                 WHERE estado = 'publicado' AND fecha_inicio >= %s
                 ORDER BY fecha_inicio ASC LIMIT 6",
                current_time('mysql')
            ));
        }

        if ($user_id && Flavor_Platform_Helpers::tabla_existe($tabla_inscripciones)) {
            $mis_inscripciones = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE usuario_id = %d AND estado = 'confirmada'",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-eventos-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Próximos Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-calendar"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($proximos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Próximos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-tickets-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_inscripciones); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Inscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-admin-site"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_eventos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Total Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($eventos_proximos)): ?>
                <div class="flavor-cards-grid flavor-cards-grid-3">
                    <?php foreach ($eventos_proximos as $evento): ?>
                        <div class="flavor-card flavor-evento-card">
                            <?php if (!empty($evento->imagen_destacada)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($evento->imagen_destacada); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <span class="flavor-badge"><?php echo esc_html($evento->tipo); ?></span>
                                <h4><?php echo esc_html($evento->titulo); ?></h4>
                                <p class="flavor-evento-fecha">
                                    <span class="dashicons dashicons-calendar"></span>
                                    <?php echo esc_html(date_i18n('d M Y, H:i', strtotime($evento->fecha_inicio))); ?>
                                </p>
                                <?php if (!empty($evento->ubicacion_nombre)): ?>
                                    <p class="flavor-evento-ubicacion">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($evento->ubicacion_nombre); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(add_query_arg('evento_id', $evento->id, Flavor_Platform_Helpers::get_action_url('eventos', 'detalle'))); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <?php esc_html_e('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No hay eventos próximos programados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('eventos', '')); ?>" class="flavor-btn flavor-btn-primary">
                    <?php esc_html_e('Ver todos los eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }

    public function render_tab_inscripciones() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        $inscripciones = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_inscripciones) && Flavor_Platform_Helpers::tabla_existe($tabla_eventos)) {
            $inscripciones = $wpdb->get_results($wpdb->prepare(
                "SELECT i.*, e.titulo, e.fecha_inicio, e.ubicacion_nombre, e.slug
                 FROM {$tabla_inscripciones} i
                 JOIN {$tabla_eventos} e ON i.evento_id = e.id
                 WHERE i.usuario_id = %d
                 ORDER BY e.fecha_inicio DESC LIMIT 10",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-tickets-alt"></span> <?php esc_html_e('Mis Inscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>

            <?php if (empty($inscripciones)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-tickets-alt"></span>
                    <p><?php esc_html_e('No estás inscrito en ningún evento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('eventos', '')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscripciones as $insc): ?>
                                <tr>
                                    <td><?php echo esc_html($insc->titulo); ?></td>
                                    <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($insc->fecha_inicio))); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo $insc->estado === 'confirmada' ? 'success' : 'warning'; ?>">
                                            <?php echo esc_html(ucfirst($insc->estado)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg('evento_id', $insc->evento_id, Flavor_Platform_Helpers::get_action_url('eventos', 'detalle'))); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                            <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a>
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
