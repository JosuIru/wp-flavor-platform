<?php
/**
 * Dashboard Tab para Talleres
 *
 * @package FlavorPlatform
 * @subpackage Modules\Talleres
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Talleres_Dashboard_Tab {

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
        $tabs['talleres-proximos'] = [
            'label' => __('Talleres', 'flavor-platform'),
            'icon' => 'hammer',
            'callback' => [$this, 'render_tab_proximos'],
            'orden' => 35,
        ];

        $tabs['talleres-mis-cursos'] = [
            'label' => __('Mis Cursos', 'flavor-platform'),
            'icon' => 'welcome-learn-more',
            'callback' => [$this, 'render_tab_mis_cursos'],
            'orden' => 36,
        ];

        return $tabs;
    }

    public function render_tab_proximos() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_talleres';

        $talleres = [];
        $total_proximos = 0;

        if (Flavor_Platform_Helpers::tabla_existe($tabla)) {
            $total_proximos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'publicado' AND fecha_inicio >= %s",
                date('Y-m-d')
            ));

            $talleres = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla}
                 WHERE estado = 'publicado' AND fecha_inicio >= %s
                 ORDER BY fecha_inicio ASC LIMIT 6",
                date('Y-m-d')
            ));
        }

        ?>
        <div class="flavor-panel flavor-talleres-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-hammer"></span> <?php esc_html_e('Próximos Talleres', 'flavor-platform'); ?></h2>
                <span class="flavor-badge"><?php echo number_format_i18n($total_proximos); ?> <?php esc_html_e('disponibles', 'flavor-platform'); ?></span>
            </div>

            <?php if (empty($talleres)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-hammer"></span>
                    <p><?php esc_html_e('No hay talleres próximos programados.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid flavor-cards-grid-3">
                    <?php foreach ($talleres as $taller): ?>
                        <div class="flavor-card flavor-taller-card">
                            <?php if (!empty($taller->imagen_destacada)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($taller->imagen_destacada); ?>" alt="">
                                    <?php if ($taller->es_gratuito): ?>
                                        <span class="flavor-badge flavor-badge-success"><?php esc_html_e('Gratis', 'flavor-platform'); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($taller->nivel); ?>">
                                    <?php echo esc_html(ucfirst($taller->nivel)); ?>
                                </span>
                                <h4><?php echo esc_html($taller->titulo); ?></h4>
                                <p class="flavor-taller-fecha">
                                    <span class="dashicons dashicons-calendar"></span>
                                    <?php echo esc_html(date_i18n('d M Y', strtotime($taller->fecha_inicio))); ?>
                                </p>
                                <?php if (!empty($taller->instructor_nombre)): ?>
                                    <p class="flavor-taller-instructor">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <?php echo esc_html($taller->instructor_nombre); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($taller->plazas_maximas): ?>
                                    <p class="flavor-taller-plazas">
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php printf(
                                            esc_html__('%d/%d plazas', 'flavor-platform'),
                                            $taller->inscritos_count,
                                            $taller->plazas_maximas
                                        ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-footer">
                                <?php if (!$taller->es_gratuito && $taller->precio > 0): ?>
                                    <span class="flavor-precio"><?php echo number_format_i18n($taller->precio, 2); ?> €</span>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(home_url('/talleres/' . $taller->slug)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php esc_html_e('Ver más', 'flavor-platform'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/talleres/')); ?>" class="flavor-btn flavor-btn-outline">
                    <?php esc_html_e('Ver todos los talleres', 'flavor-platform'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    public function render_tab_mis_cursos() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión.', 'flavor-platform') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_talleres';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

        $inscripciones = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_inscripciones) && Flavor_Platform_Helpers::tabla_existe($tabla)) {
            $inscripciones = $wpdb->get_results($wpdb->prepare(
                "SELECT i.*, t.titulo, t.fecha_inicio, t.fecha_fin, t.slug, t.instructor_nombre, t.certificado
                 FROM {$tabla_inscripciones} i
                 JOIN {$tabla} t ON i.taller_id = t.id
                 WHERE i.usuario_id = %d
                 ORDER BY t.fecha_inicio DESC LIMIT 10",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php esc_html_e('Mis Cursos y Talleres', 'flavor-platform'); ?></h2>
            </div>

            <?php if (empty($inscripciones)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <p><?php esc_html_e('No estás inscrito en ningún taller.', 'flavor-platform'); ?></p>
                    <a href="<?php echo esc_url(home_url('/talleres/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar talleres', 'flavor-platform'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Taller', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Instructor', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Fechas', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Asistencia', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Acciones', 'flavor-platform'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscripciones as $insc): ?>
                                <tr>
                                    <td><?php echo esc_html($insc->titulo); ?></td>
                                    <td><?php echo esc_html($insc->instructor_nombre ?: '-'); ?></td>
                                    <td>
                                        <?php echo esc_html(date_i18n('d/m/Y', strtotime($insc->fecha_inicio))); ?>
                                        <?php if ($insc->fecha_fin && $insc->fecha_fin !== $insc->fecha_inicio): ?>
                                            - <?php echo esc_html(date_i18n('d/m/Y', strtotime($insc->fecha_fin))); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo $insc->estado === 'completada' ? 'success' : ($insc->estado === 'confirmada' ? 'primary' : 'warning'); ?>">
                                            <?php echo esc_html(ucfirst($insc->estado)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($insc->porcentaje_asistencia !== null): ?>
                                            <div class="flavor-progress-mini">
                                                <div class="progress-bar" style="width: <?php echo esc_attr($insc->porcentaje_asistencia); ?>%"></div>
                                            </div>
                                            <span><?php echo number_format_i18n($insc->porcentaje_asistencia, 0); ?>%</span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($insc->certificado_emitido && $insc->certificado_url): ?>
                                            <a href="<?php echo esc_url($insc->certificado_url); ?>" class="flavor-btn flavor-btn-sm flavor-btn-success" target="_blank">
                                                <span class="dashicons dashicons-awards"></span>
                                                <?php esc_html_e('Certificado', 'flavor-platform'); ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url(home_url('/talleres/' . $insc->slug)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                                <?php esc_html_e('Ver', 'flavor-platform'); ?>
                                            </a>
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
