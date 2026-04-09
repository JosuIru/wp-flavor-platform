<?php
/**
 * Dashboard Tab para Huertos Urbanos
 *
 * Registra tabs en el dashboard de usuario "Mi Cuenta":
 * - Huertos: listado de huertos disponibles
 * - Mi Parcela: parcela asignada al usuario
 * - Calendario: calendario de cultivos y tareas
 * - Mapa: mapa interactivo de huertos
 *
 * @package FlavorChatIA
 * @subpackage Modules\HuertosUrbanos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Huertos_Urbanos_Dashboard_Tab {

    /**
     * Instancia singleton
     *
     * @var Flavor_Huertos_Urbanos_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Prefijo de tablas
     *
     * @var string
     */
    private $prefijo_tablas;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->prefijo_tablas = $wpdb->prefix . 'flavor_huertos';

        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Huertos_Urbanos_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Registra los tabs en el dashboard de usuario
     *
     * @param array $tabs Tabs existentes
     * @return array Tabs modificados
     */
    public function registrar_tabs($tabs) {
        $tabs['huertos'] = [
            'label' => __('Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'carrot',
            'callback' => [$this, 'render_tab_huertos'],
            'orden' => 55,
        ];

        $tabs['mi-parcela'] = [
            'label' => __('Mi Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'admin-site-alt3',
            'callback' => [$this, 'render_tab_mi_parcela'],
            'orden' => 56,
        ];

        $tabs['calendario-huertos'] = [
            'label' => __('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'calendar',
            'callback' => [$this, 'render_tab_calendario'],
            'orden' => 57,
        ];

        $tabs['mapa-huertos'] = [
            'label' => __('Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'map',
            'callback' => [$this, 'render_tab_mapa'],
            'orden' => 58,
        ];

        return $tabs;
    }

    // =========================================================================
    // TAB: HUERTOS
    // =========================================================================

    /**
     * Renderiza el tab de listado de huertos
     */
    public function render_tab_huertos() {
        global $wpdb;
        $tabla_huertos = $this->prefijo_tablas;
        $tabla_parcelas = $this->prefijo_tablas . '_parcelas';

        $total_huertos = 0;
        $parcelas_disponibles = 0;
        $huertos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_huertos)) {
            $total_huertos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_huertos} WHERE estado = 'activo'"
            );

            $huertos = $wpdb->get_results(
                "SELECT h.*,
                        (SELECT COUNT(*) FROM {$tabla_parcelas} p WHERE p.huerto_id = h.id) as total_parcelas,
                        (SELECT COUNT(*) FROM {$tabla_parcelas} p WHERE p.huerto_id = h.id AND p.estado = 'disponible') as parcelas_libres
                 FROM {$tabla_huertos} h
                 WHERE h.estado = 'activo'
                 ORDER BY h.nombre ASC
                 LIMIT 12"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_parcelas)) {
            $parcelas_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_parcelas} WHERE estado = 'disponible'"
            );
        }
        ?>
        <div class="flavor-panel flavor-huertos-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-carrot"></span> <?php esc_html_e('Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Cultiva tu propia comida en espacios comunitarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-admin-site-alt3"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_huertos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Huertos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($parcelas_disponibles); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Parcelas Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($huertos)): ?>
                <div class="flavor-cards-grid flavor-cards-grid-3">
                    <?php foreach ($huertos as $huerto): ?>
                        <div class="flavor-card flavor-huerto-card">
                            <?php if (!empty($huerto->foto_url)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($huerto->foto_url); ?>" alt="<?php echo esc_attr($huerto->nombre); ?>">
                                </div>
                            <?php else: ?>
                                <div class="flavor-card-image flavor-card-image-placeholder">
                                    <span class="dashicons dashicons-carrot"></span>
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <h4><?php echo esc_html($huerto->nombre); ?></h4>
                                <?php if (!empty($huerto->direccion)): ?>
                                    <p class="flavor-text-muted">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html(wp_trim_words($huerto->direccion, 8)); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flavor-card-meta">
                                    <span class="flavor-badge <?php echo ($huerto->parcelas_libres > 0) ? 'flavor-badge-success' : 'flavor-badge-warning'; ?>">
                                        <?php printf(
                                            esc_html__('%d/%d parcelas libres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                            $huerto->parcelas_libres,
                                            $huerto->total_parcelas
                                        ); ?>
                                    </span>
                                </div>
                                <?php if (!empty($huerto->superficie_m2)): ?>
                                    <p class="flavor-text-muted">
                                        <span class="dashicons dashicons-editor-expand"></span>
                                        <?php echo number_format_i18n($huerto->superficie_m2); ?> m2
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(add_query_arg('huerto_id', $huerto->id, home_url('/huertos/'))); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <?php esc_html_e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                                <?php if ($huerto->parcelas_libres > 0): ?>
                                    <a href="<?php echo esc_url(add_query_arg('huerto_id', $huerto->id, home_url('/huertos/solicitar/'))); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                        <?php esc_html_e('Solicitar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-carrot"></span>
                    <h3><?php esc_html_e('No hay huertos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Pronto tendras huertos urbanos cerca de ti.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/huertos/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <?php esc_html_e('Ver todos los huertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // TAB: MI PARCELA
    // =========================================================================

    /**
     * Renderiza el tab de Mi Parcela
     */
    public function render_tab_mi_parcela() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            $this->render_login_requerido();
            return;
        }

        global $wpdb;
        $tabla_parcelas = $this->prefijo_tablas . '_parcelas';
        $tabla_asignaciones = $this->prefijo_tablas . '_asignaciones';
        $tabla_huertos = $this->prefijo_tablas;
        $tabla_cultivos = $this->prefijo_tablas . '_cultivos';
        $tabla_actividades = $this->prefijo_tablas . '_actividades';

        $mis_parcelas = [];
        $cultivos_activos = [];
        $actividades_recientes = [];

        // Obtener parcelas asignadas al usuario
        if (Flavor_Chat_Helpers::tabla_existe($tabla_asignaciones) &&
            Flavor_Chat_Helpers::tabla_existe($tabla_parcelas) &&
            Flavor_Chat_Helpers::tabla_existe($tabla_huertos)) {

            $mis_parcelas = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*,
                        p.numero_parcela, p.superficie_m2, p.orientacion, p.tiene_riego, p.tiene_sombra,
                        h.nombre as huerto_nombre, h.direccion as huerto_direccion, h.horario_acceso
                 FROM {$tabla_asignaciones} a
                 JOIN {$tabla_parcelas} p ON a.parcela_id = p.id
                 JOIN {$tabla_huertos} h ON p.huerto_id = h.id
                 WHERE a.usuario_id = %d AND a.estado = 'activa'
                 ORDER BY a.fecha_asignacion DESC",
                $usuario_id
            ));

            // Obtener cultivos activos
            if (!empty($mis_parcelas) && Flavor_Chat_Helpers::tabla_existe($tabla_cultivos)) {
                $parcela_ids = wp_list_pluck($mis_parcelas, 'parcela_id');
                $placeholders = implode(',', array_fill(0, count($parcela_ids), '%d'));

                $cultivos_activos = $wpdb->get_results($wpdb->prepare(
                    "SELECT c.*, p.numero_parcela
                     FROM {$tabla_cultivos} c
                     JOIN {$tabla_parcelas} p ON c.parcela_id = p.id
                     WHERE c.parcela_id IN ($placeholders)
                     AND c.estado NOT IN ('finalizado', 'fallido')
                     ORDER BY c.fecha_siembra DESC
                     LIMIT 6",
                    ...$parcela_ids
                ));
            }

            // Obtener actividades recientes
            if (!empty($mis_parcelas) && Flavor_Chat_Helpers::tabla_existe($tabla_actividades)) {
                $actividades_recientes = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$tabla_actividades}
                     WHERE usuario_id = %d
                     ORDER BY fecha_actividad DESC
                     LIMIT 5",
                    $usuario_id
                ));
            }
        }
        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e('Mi Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>

            <?php if (empty($mis_parcelas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-carrot"></span>
                    <h3><?php esc_html_e('No tienes parcela asignada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Solicita una parcela en uno de nuestros huertos urbanos y comienza a cultivar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/huertos/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Ver huertos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($mis_parcelas as $parcela): ?>
                    <div class="flavor-card flavor-parcela-card flavor-card-horizontal">
                        <div class="flavor-card-body">
                            <div class="parcela-header">
                                <h3>
                                    <?php printf(esc_html__('Parcela %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($parcela->numero_parcela)); ?>
                                </h3>
                                <span class="flavor-badge flavor-badge-success">
                                    <?php esc_html_e('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                            </div>

                            <div class="flavor-info-grid">
                                <div class="flavor-info-item">
                                    <span class="dashicons dashicons-admin-site-alt3"></span>
                                    <span><?php echo esc_html($parcela->huerto_nombre); ?></span>
                                </div>
                                <div class="flavor-info-item">
                                    <span class="dashicons dashicons-location"></span>
                                    <span><?php echo esc_html($parcela->huerto_direccion); ?></span>
                                </div>
                                <?php if ($parcela->superficie_m2): ?>
                                    <div class="flavor-info-item">
                                        <span class="dashicons dashicons-editor-expand"></span>
                                        <span><?php echo number_format_i18n($parcela->superficie_m2, 1); ?> m2</span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($parcela->orientacion): ?>
                                    <div class="flavor-info-item">
                                        <span class="dashicons dashicons-admin-site"></span>
                                        <span><?php echo esc_html(ucfirst($parcela->orientacion)); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="flavor-info-item">
                                    <span class="dashicons dashicons-calendar"></span>
                                    <span>
                                        <?php printf(
                                            esc_html__('Desde %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                            date_i18n('d/m/Y', strtotime($parcela->fecha_asignacion))
                                        ); ?>
                                    </span>
                                </div>
                                <?php if ($parcela->horario_acceso): ?>
                                    <div class="flavor-info-item">
                                        <span class="dashicons dashicons-clock"></span>
                                        <span><?php echo esc_html($parcela->horario_acceso); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-parcela-features">
                                <?php if ($parcela->tiene_riego): ?>
                                    <span class="flavor-feature-badge">
                                        <span class="dashicons dashicons-water"></span>
                                        <?php esc_html_e('Riego', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($parcela->tiene_sombra): ?>
                                    <span class="flavor-feature-badge">
                                        <span class="dashicons dashicons-palmtree"></span>
                                        <?php esc_html_e('Sombra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($cultivos_activos)): ?>
                    <div class="flavor-section">
                        <h3><?php esc_html_e('Mis Cultivos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-cards-grid flavor-cards-grid-3">
                            <?php foreach ($cultivos_activos as $cultivo): ?>
                                <div class="flavor-card flavor-cultivo-card">
                                    <div class="flavor-card-body">
                                        <h4><?php echo esc_html($cultivo->nombre_cultivo); ?></h4>
                                        <?php if ($cultivo->variedad): ?>
                                            <p class="flavor-text-muted"><?php echo esc_html($cultivo->variedad); ?></p>
                                        <?php endif; ?>
                                        <div class="flavor-cultivo-meta">
                                            <span class="flavor-badge flavor-badge-<?php echo esc_attr($this->obtener_color_estado_cultivo($cultivo->estado)); ?>">
                                                <?php echo esc_html($this->obtener_texto_estado_cultivo($cultivo->estado)); ?>
                                            </span>
                                        </div>
                                        <p class="flavor-text-muted">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                            <?php printf(
                                                esc_html__('Sembrado: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                                date_i18n('d/m/Y', strtotime($cultivo->fecha_siembra))
                                            ); ?>
                                        </p>
                                        <?php if ($cultivo->fecha_cosecha_estimada): ?>
                                            <p class="flavor-text-muted">
                                                <span class="dashicons dashicons-carrot"></span>
                                                <?php printf(
                                                    esc_html__('Cosecha: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                                    date_i18n('d/m/Y', strtotime($cultivo->fecha_cosecha_estimada))
                                                ); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($actividades_recientes)): ?>
                    <div class="flavor-section">
                        <h3><?php esc_html_e('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-activity-list">
                            <?php foreach ($actividades_recientes as $actividad): ?>
                                <div class="flavor-activity-item">
                                    <span class="flavor-activity-icon dashicons dashicons-<?php echo esc_attr($this->obtener_icono_actividad($actividad->tipo)); ?>"></span>
                                    <div class="flavor-activity-content">
                                        <strong><?php echo esc_html($this->obtener_texto_tipo_actividad($actividad->tipo)); ?></strong>
                                        <p><?php echo esc_html(wp_trim_words($actividad->descripcion, 15)); ?></p>
                                        <span class="flavor-activity-date">
                                            <?php echo esc_html(human_time_diff(strtotime($actividad->fecha_actividad), current_time('timestamp'))); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flavor-panel-actions">
                    <a href="<?php echo esc_url(home_url('/huertos/registrar-cultivo/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Registrar cultivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/huertos/registrar-actividad/')); ?>" class="flavor-btn flavor-btn-secondary">
                        <?php esc_html_e('Registrar actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // =========================================================================
    // TAB: CALENDARIO
    // =========================================================================

    /**
     * Renderiza el tab de Calendario
     */
    public function render_tab_calendario() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            $this->render_login_requerido();
            return;
        }

        global $wpdb;
        $tabla_tareas = $this->prefijo_tablas . '_tareas';
        $tabla_participantes = $this->prefijo_tablas . '_participantes_tareas';
        $tabla_turnos = $this->prefijo_tablas . '_turnos_riego';
        $tabla_cultivos = $this->prefijo_tablas . '_cultivos';
        $tabla_huertos = $this->prefijo_tablas;

        $mes_actual = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('n'));
        $anio_actual = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));

        $fecha_inicio = sprintf('%04d-%02d-01', $anio_actual, $mes_actual);
        $fecha_fin = date('Y-m-t', strtotime($fecha_inicio));

        $tareas_proximas = [];
        $mis_turnos_riego = [];
        $cosechas_proximas = [];

        // Obtener tareas proximas
        if (Flavor_Chat_Helpers::tabla_existe($tabla_tareas)) {
            $tareas_proximas = $wpdb->get_results($wpdb->prepare(
                "SELECT t.*, h.nombre as huerto_nombre,
                        (SELECT COUNT(*) FROM {$tabla_participantes} pt WHERE pt.tarea_id = t.id) as inscritos,
                        (SELECT COUNT(*) FROM {$tabla_participantes} pt WHERE pt.tarea_id = t.id AND pt.usuario_id = %d) as estoy_inscrito
                 FROM {$tabla_tareas} t
                 LEFT JOIN {$tabla_huertos} h ON t.huerto_id = h.id
                 WHERE t.fecha BETWEEN %s AND %s
                 AND t.estado IN ('programada', 'en_curso')
                 ORDER BY t.fecha ASC, t.hora_inicio ASC
                 LIMIT 20",
                $usuario_id,
                $fecha_inicio,
                $fecha_fin
            ));
        }

        // Obtener turnos de riego del usuario
        if (Flavor_Chat_Helpers::tabla_existe($tabla_turnos)) {
            $mis_turnos_riego = $wpdb->get_results($wpdb->prepare(
                "SELECT tr.*, h.nombre as huerto_nombre
                 FROM {$tabla_turnos} tr
                 LEFT JOIN {$tabla_huertos} h ON tr.huerto_id = h.id
                 WHERE tr.usuario_id = %d
                 AND tr.fecha_turno BETWEEN %s AND %s
                 ORDER BY tr.fecha_turno ASC",
                $usuario_id,
                $fecha_inicio,
                $fecha_fin
            ));
        }

        // Obtener cosechas proximas
        if (Flavor_Chat_Helpers::tabla_existe($tabla_cultivos)) {
            $cosechas_proximas = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_cultivos}
                 WHERE usuario_id = %d
                 AND fecha_cosecha_estimada BETWEEN %s AND %s
                 AND estado NOT IN ('finalizado', 'fallido')
                 ORDER BY fecha_cosecha_estimada ASC
                 LIMIT 10",
                $usuario_id,
                $fecha_inicio,
                $fecha_fin
            ));
        }

        $nombre_mes = date_i18n('F Y', strtotime($fecha_inicio));
        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-calendar"></span> <?php esc_html_e('Calendario del Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>

            <div class="flavor-calendar-nav">
                <?php
                $mes_anterior = $mes_actual - 1;
                $anio_anterior = $anio_actual;
                if ($mes_anterior < 1) {
                    $mes_anterior = 12;
                    $anio_anterior--;
                }

                $mes_siguiente = $mes_actual + 1;
                $anio_siguiente = $anio_actual;
                if ($mes_siguiente > 12) {
                    $mes_siguiente = 1;
                    $anio_siguiente++;
                }
                ?>
                <a href="<?php echo esc_url(add_query_arg(['tab' => 'calendario-huertos', 'mes' => $mes_anterior, 'anio' => $anio_anterior])); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </a>
                <span class="flavor-calendar-title"><?php echo esc_html(ucfirst($nombre_mes)); ?></span>
                <a href="<?php echo esc_url(add_query_arg(['tab' => 'calendario-huertos', 'mes' => $mes_siguiente, 'anio' => $anio_siguiente])); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>

            <div class="flavor-calendar-sections">
                <?php if (!empty($tareas_proximas)): ?>
                    <div class="flavor-section">
                        <h3><span class="dashicons dashicons-hammer"></span> <?php esc_html_e('Tareas y Actividades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-event-list">
                            <?php foreach ($tareas_proximas as $tarea): ?>
                                <div class="flavor-event-item <?php echo $tarea->estoy_inscrito ? 'flavor-event-inscrito' : ''; ?>">
                                    <div class="flavor-event-date">
                                        <span class="flavor-event-day"><?php echo date('d', strtotime($tarea->fecha)); ?></span>
                                        <span class="flavor-event-month"><?php echo date_i18n('M', strtotime($tarea->fecha)); ?></span>
                                    </div>
                                    <div class="flavor-event-content">
                                        <h4><?php echo esc_html($tarea->titulo); ?></h4>
                                        <p class="flavor-text-muted">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html($tarea->huerto_nombre); ?>
                                            <?php if ($tarea->hora_inicio): ?>
                                                | <span class="dashicons dashicons-clock"></span>
                                                <?php echo esc_html(date('H:i', strtotime($tarea->hora_inicio))); ?>
                                            <?php endif; ?>
                                        </p>
                                        <div class="flavor-event-meta">
                                            <span class="flavor-badge flavor-badge-<?php echo esc_attr($this->obtener_color_tipo_tarea($tarea->tipo)); ?>">
                                                <?php echo esc_html($this->obtener_texto_tipo_tarea($tarea->tipo)); ?>
                                            </span>
                                            <?php if ($tarea->max_participantes): ?>
                                                <span class="flavor-event-capacity">
                                                    <?php printf(
                                                        esc_html__('%d/%d inscritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                                        $tarea->inscritos,
                                                        $tarea->max_participantes
                                                    ); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flavor-event-actions">
                                        <?php if ($tarea->estoy_inscrito): ?>
                                            <span class="flavor-badge flavor-badge-success"><?php esc_html_e('Inscrito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                        <?php else: ?>
                                            <button class="flavor-btn flavor-btn-sm flavor-btn-primary flavor-inscribirse-tarea"
                                                    data-tarea-id="<?php echo esc_attr($tarea->id); ?>">
                                                <?php esc_html_e('Inscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($mis_turnos_riego)): ?>
                    <div class="flavor-section">
                        <h3><span class="dashicons dashicons-water"></span> <?php esc_html_e('Mis Turnos de Riego', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-event-list">
                            <?php foreach ($mis_turnos_riego as $turno): ?>
                                <div class="flavor-event-item <?php echo $turno->completado ? 'flavor-event-completado' : ''; ?>">
                                    <div class="flavor-event-date">
                                        <span class="flavor-event-day"><?php echo date('d', strtotime($turno->fecha_turno)); ?></span>
                                        <span class="flavor-event-month"><?php echo date_i18n('M', strtotime($turno->fecha_turno)); ?></span>
                                    </div>
                                    <div class="flavor-event-content">
                                        <h4><?php esc_html_e('Turno de Riego', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                                        <p class="flavor-text-muted">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html($turno->huerto_nombre); ?>
                                            <?php if ($turno->zona_riego): ?>
                                                - <?php echo esc_html($turno->zona_riego); ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="flavor-text-muted">
                                            <span class="dashicons dashicons-clock"></span>
                                            <?php echo esc_html(date('H:i', strtotime($turno->hora_inicio))); ?> -
                                            <?php echo esc_html(date('H:i', strtotime($turno->hora_fin))); ?>
                                        </p>
                                    </div>
                                    <div class="flavor-event-actions">
                                        <?php if ($turno->completado): ?>
                                            <span class="flavor-badge flavor-badge-success">
                                                <span class="dashicons dashicons-yes"></span>
                                                <?php esc_html_e('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </span>
                                        <?php else: ?>
                                            <button class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-marcar-riego"
                                                    data-turno-id="<?php echo esc_attr($turno->id); ?>">
                                                <?php esc_html_e('Marcar completado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($cosechas_proximas)): ?>
                    <div class="flavor-section">
                        <h3><span class="dashicons dashicons-carrot"></span> <?php esc_html_e('Cosechas Proximas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-event-list">
                            <?php foreach ($cosechas_proximas as $cultivo): ?>
                                <div class="flavor-event-item">
                                    <div class="flavor-event-date">
                                        <span class="flavor-event-day"><?php echo date('d', strtotime($cultivo->fecha_cosecha_estimada)); ?></span>
                                        <span class="flavor-event-month"><?php echo date_i18n('M', strtotime($cultivo->fecha_cosecha_estimada)); ?></span>
                                    </div>
                                    <div class="flavor-event-content">
                                        <h4><?php echo esc_html($cultivo->nombre_cultivo); ?></h4>
                                        <?php if ($cultivo->variedad): ?>
                                            <p class="flavor-text-muted"><?php echo esc_html($cultivo->variedad); ?></p>
                                        <?php endif; ?>
                                        <span class="flavor-badge flavor-badge-warning">
                                            <?php esc_html_e('Listo para cosechar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($tareas_proximas) && empty($mis_turnos_riego) && empty($cosechas_proximas)): ?>
                    <div class="flavor-empty-state">
                        <span class="dashicons dashicons-calendar"></span>
                        <h3><?php esc_html_e('No hay eventos este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <p><?php esc_html_e('Tu calendario esta libre. Explora las tareas disponibles o registra nuevos cultivos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // TAB: MAPA
    // =========================================================================

    /**
     * Renderiza el tab de Mapa
     */
    public function render_tab_mapa() {
        global $wpdb;
        $tabla_huertos = $this->prefijo_tablas;

        $huertos = [];
        $centro_lat = 40.4168;
        $centro_lng = -3.7038;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_huertos)) {
            $huertos = $wpdb->get_results(
                "SELECT id, nombre, direccion, latitud, longitud, superficie_m2, estado,
                        (SELECT COUNT(*) FROM {$this->prefijo_tablas}_parcelas p WHERE p.huerto_id = {$tabla_huertos}.id AND p.estado = 'disponible') as parcelas_disponibles,
                        (SELECT COUNT(*) FROM {$this->prefijo_tablas}_parcelas p WHERE p.huerto_id = {$tabla_huertos}.id) as total_parcelas
                 FROM {$tabla_huertos}
                 WHERE estado = 'activo'
                 AND latitud IS NOT NULL AND longitud IS NOT NULL
                 ORDER BY nombre ASC"
            );

            // Calcular centro del mapa
            if (!empty($huertos)) {
                $sum_lat = 0;
                $sum_lng = 0;
                $count = 0;
                foreach ($huertos as $huerto) {
                    if ($huerto->latitud && $huerto->longitud) {
                        $sum_lat += $huerto->latitud;
                        $sum_lng += $huerto->longitud;
                        $count++;
                    }
                }
                if ($count > 0) {
                    $centro_lat = $sum_lat / $count;
                    $centro_lng = $sum_lng / $count;
                }
            }
        }

        $mapa_id = 'mapa-huertos-' . wp_unique_id();
        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-location"></span> <?php esc_html_e('Mapa de Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Encuentra huertos urbanos cerca de ti', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-mapa-container">
                <div id="<?php echo esc_attr($mapa_id); ?>" class="flavor-mapa-huertos" style="height: 450px; border-radius: 8px;"></div>
            </div>

            <div class="flavor-mapa-leyenda">
                <div class="flavor-leyenda-item">
                    <span class="flavor-leyenda-marker flavor-leyenda-disponible"></span>
                    <span><?php esc_html_e('Parcelas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flavor-leyenda-item">
                    <span class="flavor-leyenda-marker flavor-leyenda-completo"></span>
                    <span><?php esc_html_e('Sin disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <?php if (!empty($huertos)): ?>
                <div class="flavor-section">
                    <h3><?php esc_html_e('Listado de Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="flavor-huertos-lista">
                        <?php foreach ($huertos as $huerto): ?>
                            <div class="flavor-huerto-item" data-lat="<?php echo esc_attr($huerto->latitud); ?>" data-lng="<?php echo esc_attr($huerto->longitud); ?>">
                                <div class="flavor-huerto-info">
                                    <h4><?php echo esc_html($huerto->nombre); ?></h4>
                                    <p class="flavor-text-muted"><?php echo esc_html($huerto->direccion); ?></p>
                                </div>
                                <div class="flavor-huerto-stats">
                                    <span class="flavor-badge <?php echo $huerto->parcelas_disponibles > 0 ? 'flavor-badge-success' : 'flavor-badge-warning'; ?>">
                                        <?php printf(
                                            esc_html__('%d/%d libres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                            $huerto->parcelas_disponibles,
                                            $huerto->total_parcelas
                                        ); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .flavor-mapa-container { margin: 20px 0; }
        .flavor-mapa-leyenda { display: flex; gap: 20px; justify-content: center; margin: 15px 0; }
        .flavor-leyenda-item { display: flex; align-items: center; gap: 8px; }
        .flavor-leyenda-marker { width: 16px; height: 16px; border-radius: 50%; }
        .flavor-leyenda-disponible { background: #28a745; }
        .flavor-leyenda-completo { background: #dc3545; }
        .flavor-huertos-lista { display: flex; flex-direction: column; gap: 10px; }
        .flavor-huerto-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; cursor: pointer; transition: background 0.2s; }
        .flavor-huerto-item:hover { background: #e9ecef; }
        .flavor-huerto-info h4 { margin: 0 0 5px; }
        .flavor-huerto-info p { margin: 0; }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L === 'undefined') {
                console.error('Leaflet no esta cargado');
                return;
            }

            var mapa = L.map('<?php echo esc_js($mapa_id); ?>').setView([<?php echo esc_js($centro_lat); ?>, <?php echo esc_js($centro_lng); ?>], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(mapa);

            var huertos = <?php echo wp_json_encode(array_map(function($huerto) {
                return [
                    'id' => $huerto->id,
                    'nombre' => $huerto->nombre,
                    'direccion' => $huerto->direccion,
                    'lat' => floatval($huerto->latitud),
                    'lng' => floatval($huerto->longitud),
                    'disponibles' => intval($huerto->parcelas_disponibles),
                    'total' => intval($huerto->total_parcelas)
                ];
            }, $huertos)); ?>;

            huertos.forEach(function(huerto) {
                if (huerto.lat && huerto.lng) {
                    var color = huerto.disponibles > 0 ? '#28a745' : '#dc3545';
                    var icono = L.divIcon({
                        className: 'flavor-marker-huerto',
                        html: '<div style="background:' + color + '; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold; border:3px solid #fff; box-shadow:0 2px 5px rgba(0,0,0,0.3);">' + huerto.disponibles + '</div>',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    });

                    var marker = L.marker([huerto.lat, huerto.lng], {icon: icono}).addTo(mapa);
                    marker.bindPopup(
                        '<strong>' + huerto.nombre + '</strong><br>' +
                        '<small>' + huerto.direccion + '</small><br>' +
                        '<span style="color:' + color + '">' + huerto.disponibles + '/' + huerto.total + ' parcelas disponibles</span><br>' +
                        '<a href="<?php echo esc_url(home_url('/huertos/')); ?>?huerto_id=' + huerto.id + '" class="flavor-btn flavor-btn-sm">Ver huerto</a>'
                    );
                }
            });

            // Click en listado para centrar mapa
            document.querySelectorAll('.flavor-huerto-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    var lat = parseFloat(this.dataset.lat);
                    var lng = parseFloat(this.dataset.lng);
                    if (lat && lng) {
                        mapa.setView([lat, lng], 15);
                    }
                });
            });
        });
        </script>
        <?php
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Renderiza mensaje de login requerido
     */
    private function render_login_requerido() {
        ?>
        <div class="flavor-empty-state">
            <span class="dashicons dashicons-lock"></span>
            <h3><?php esc_html_e('Acceso restringido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Debes iniciar sesion para acceder a esta seccion.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="flavor-btn flavor-btn-primary">
                <?php esc_html_e('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Obtiene el color del estado del cultivo
     *
     * @param string $estado Estado del cultivo
     * @return string Clase de color
     */
    private function obtener_color_estado_cultivo($estado) {
        $colores = [
            'planificado' => 'secondary',
            'sembrado' => 'info',
            'germinando' => 'info',
            'crecimiento' => 'primary',
            'floracion' => 'warning',
            'maduracion' => 'warning',
            'cosecha' => 'success',
            'finalizado' => 'success',
            'fallido' => 'danger',
        ];
        return $colores[$estado] ?? 'secondary';
    }

    /**
     * Obtiene el texto del estado del cultivo
     *
     * @param string $estado Estado del cultivo
     * @return string Texto del estado
     */
    private function obtener_texto_estado_cultivo($estado) {
        $textos = [
            'planificado' => __('Planificado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sembrado' => __('Sembrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'germinando' => __('Germinando', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'crecimiento' => __('Crecimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'floracion' => __('Floracion', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'maduracion' => __('Maduracion', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cosecha' => __('Cosecha', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'finalizado' => __('Finalizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'fallido' => __('Fallido', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        return $textos[$estado] ?? ucfirst($estado);
    }

    /**
     * Obtiene el icono de la actividad
     *
     * @param string $tipo Tipo de actividad
     * @return string Nombre del icono dashicons
     */
    private function obtener_icono_actividad($tipo) {
        $iconos = [
            'riego' => 'water',
            'abonado' => 'carrot',
            'poda' => 'scissors',
            'cosecha' => 'carrot',
            'tratamiento' => 'shield',
            'limpieza' => 'trash',
            'siembra' => 'visibility',
            'transplante' => 'update',
            'observacion' => 'visibility',
            'otro' => 'edit',
        ];
        return $iconos[$tipo] ?? 'edit';
    }

    /**
     * Obtiene el texto del tipo de actividad
     *
     * @param string $tipo Tipo de actividad
     * @return string Texto del tipo
     */
    private function obtener_texto_tipo_actividad($tipo) {
        $textos = [
            'riego' => __('Riego', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'abonado' => __('Abonado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'poda' => __('Poda', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cosecha' => __('Cosecha', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tratamiento' => __('Tratamiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'limpieza' => __('Limpieza', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'siembra' => __('Siembra', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transplante' => __('Transplante', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'observacion' => __('Observacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'otro' => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        return $textos[$tipo] ?? ucfirst($tipo);
    }

    /**
     * Obtiene el color del tipo de tarea
     *
     * @param string $tipo Tipo de tarea
     * @return string Clase de color
     */
    private function obtener_color_tipo_tarea($tipo) {
        $colores = [
            'riego' => 'info',
            'limpieza' => 'secondary',
            'mantenimiento' => 'warning',
            'taller' => 'primary',
            'reunion' => 'purple',
            'siembra_comunitaria' => 'success',
            'cosecha_comunitaria' => 'success',
            'compostaje' => 'earth',
            'otro' => 'secondary',
        ];
        return $colores[$tipo] ?? 'secondary';
    }

    /**
     * Obtiene el texto del tipo de tarea
     *
     * @param string $tipo Tipo de tarea
     * @return string Texto del tipo
     */
    private function obtener_texto_tipo_tarea($tipo) {
        $textos = [
            'riego' => __('Riego', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'limpieza' => __('Limpieza', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'mantenimiento' => __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'taller' => __('Taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'reunion' => __('Reunion', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'siembra_comunitaria' => __('Siembra comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cosecha_comunitaria' => __('Cosecha comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'compostaje' => __('Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'otro' => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        return $textos[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
    }
}
