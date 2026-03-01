<?php
/**
 * Dashboard Tabs para el módulo de Colectivos y Asociaciones
 *
 * Proporciona tabs en el dashboard del cliente para gestionar colectivos,
 * proyectos y asambleas del usuario.
 *
 * @package FlavorChatIA
 * @subpackage Modules\Colectivos
 * @since 4.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de colectivos
 */
class Flavor_Colectivos_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Colectivos_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Nombre de las tablas
     */
    private $tabla_colectivos;
    private $tabla_miembros;
    private $tabla_proyectos;
    private $tabla_asambleas;

    /**
     * Etiquetas de roles
     */
    private $etiquetas_roles = [];

    /**
     * Etiquetas de tipos
     */
    private $etiquetas_tipos = [];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
        $this->tabla_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';
        $this->tabla_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';
        $this->tabla_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

        $this->etiquetas_roles = [
            'presidente' => __('Presidente/a', 'flavor-chat-ia'),
            'secretario' => __('Secretario/a', 'flavor-chat-ia'),
            'tesorero'   => __('Tesorero/a', 'flavor-chat-ia'),
            'vocal'      => __('Vocal', 'flavor-chat-ia'),
            'miembro'    => __('Miembro', 'flavor-chat-ia'),
            'admin'      => __('Administrador', 'flavor-chat-ia'),
        ];

        $this->etiquetas_tipos = [
            'asociacion'  => __('Asociación', 'flavor-chat-ia'),
            'cooperativa' => __('Cooperativa', 'flavor-chat-ia'),
            'ong'         => __('ONG', 'flavor-chat-ia'),
            'colectivo'   => __('Colectivo', 'flavor-chat-ia'),
            'plataforma'  => __('Plataforma', 'flavor-chat-ia'),
        ];

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Colectivos_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Registra los tabs del módulo en el dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        // Tab principal: Mis Colectivos
        $tabs['colectivos-mis-colectivos'] = [
            'label'    => __('Mis Colectivos', 'flavor-chat-ia'),
            'icon'     => 'groups',
            'callback' => [$this, 'render_tab_mis_colectivos'],
            'orden'    => 50,
        ];

        // Tab: Mis Proyectos
        $tabs['colectivos-mis-proyectos'] = [
            'label'    => __('Mis Proyectos', 'flavor-chat-ia'),
            'icon'     => 'portfolio',
            'callback' => [$this, 'render_tab_mis_proyectos'],
            'orden'    => 51,
        ];

        // Tab: Asambleas
        $tabs['colectivos-asambleas'] = [
            'label'    => __('Asambleas', 'flavor-chat-ia'),
            'icon'     => 'calendar-alt',
            'callback' => [$this, 'render_tab_asambleas'],
            'orden'    => 52,
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de Mis Colectivos
     */
    public function render_tab_mis_colectivos() {
        $identificador_usuario = get_current_user_id();
        if (!$identificador_usuario) {
            echo '<p class="flavor-alert flavor-alert-warning">' .
                 esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;

        // Verificar que las tablas existen
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_colectivos)) {
            echo '<div class="flavor-alert flavor-alert-info">' .
                 esc_html__('El módulo de colectivos no está configurado.', 'flavor-chat-ia') . '</div>';
            return;
        }

        // KPIs
        $total_mis_colectivos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE usuario_id = %d AND estado = 'activo'",
            $identificador_usuario
        ));

        $colectivos_administrados = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_miembros}
             WHERE usuario_id = %d AND estado = 'activo' AND rol IN ('presidente', 'secretario', 'admin')",
            $identificador_usuario
        ));

        $proyectos_activos = $this->contar_proyectos_usuario($identificador_usuario);
        $proximas_asambleas = $this->contar_asambleas_proximas($identificador_usuario);

        // Obtener mis colectivos con información ampliada
        $mis_colectivos = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, m.rol, m.fecha_union,
                    (SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE colectivo_id = c.id AND estado = 'activo') as total_miembros,
                    (SELECT COUNT(*) FROM {$this->tabla_proyectos} WHERE colectivo_id = c.id AND estado IN ('en_curso', 'activo')) as proyectos_activos
             FROM {$this->tabla_colectivos} c
             INNER JOIN {$this->tabla_miembros} m ON c.id = m.colectivo_id
             WHERE m.usuario_id = %d AND m.estado = 'activo' AND c.estado = 'activo'
             ORDER BY FIELD(m.rol, 'presidente', 'secretario', 'tesorero', 'vocal', 'miembro'), m.fecha_union DESC",
            $identificador_usuario
        ));

        ?>
        <div class="flavor-panel flavor-colectivos-dashboard-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-groups"></span>
                    <?php esc_html_e('Mis Colectivos', 'flavor-chat-ia'); ?>
                </h2>
                <p class="flavor-panel-subtitle">
                    <?php esc_html_e('Gestiona tu participación en colectivos, asociaciones y organizaciones.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <!-- KPIs -->
            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-groups"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_mis_colectivos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Colectivos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-shield-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($colectivos_administrados); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Administrados', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-portfolio"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($proyectos_activos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Proyectos Activos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-highlight">
                    <span class="flavor-kpi-icon dashicons dashicons-calendar-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($proximas_asambleas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Próximas Asambleas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/colectivos/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Explorar Colectivos', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/colectivos/crear/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Crear Colectivo', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- Listado de mis colectivos -->
            <?php if (empty($mis_colectivos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-groups"></span>
                    <h3><?php esc_html_e('No perteneces a ningún colectivo', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Únete a un colectivo existente o crea uno nuevo para empezar a participar.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/colectivos/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar Colectivos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid flavor-grid-2">
                    <?php foreach ($mis_colectivos as $colectivo): ?>
                        <?php $this->render_card_colectivo($colectivo); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Mis Proyectos
     */
    public function render_tab_mis_proyectos() {
        $identificador_usuario = get_current_user_id();
        if (!$identificador_usuario) {
            echo '<p class="flavor-alert flavor-alert-warning">' .
                 esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_proyectos)) {
            echo '<div class="flavor-alert flavor-alert-info">' .
                 esc_html__('El sistema de proyectos no está configurado.', 'flavor-chat-ia') . '</div>';
            return;
        }

        // Obtener IDs de colectivos del usuario
        $colectivo_ids = $this->obtener_colectivos_usuario($identificador_usuario);

        // Contadores por estado
        $contador_planificados = 0;
        $contador_en_curso = 0;
        $contador_completados = 0;

        $proyectos = [];

        if (!empty($colectivo_ids)) {
            $placeholders = implode(',', array_fill(0, count($colectivo_ids), '%d'));

            // Contar por estado
            $estadisticas_proyectos = $wpdb->get_results($wpdb->prepare(
                "SELECT estado, COUNT(*) as total
                 FROM {$this->tabla_proyectos}
                 WHERE colectivo_id IN ($placeholders)
                 GROUP BY estado",
                ...$colectivo_ids
            ));

            foreach ($estadisticas_proyectos as $stat) {
                switch ($stat->estado) {
                    case 'planificado':
                        $contador_planificados = (int) $stat->total;
                        break;
                    case 'en_curso':
                    case 'activo':
                        $contador_en_curso += (int) $stat->total;
                        break;
                    case 'completado':
                        $contador_completados = (int) $stat->total;
                        break;
                }
            }

            // Obtener proyectos con información del colectivo
            $proyectos = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, c.nombre as colectivo_nombre, c.tipo as colectivo_tipo
                 FROM {$this->tabla_proyectos} p
                 INNER JOIN {$this->tabla_colectivos} c ON p.colectivo_id = c.id
                 WHERE p.colectivo_id IN ($placeholders)
                 ORDER BY FIELD(p.estado, 'en_curso', 'activo', 'planificado', 'completado', 'cancelado'),
                          p.fecha_actualizacion DESC, p.fecha_creacion DESC
                 LIMIT 20",
                ...$colectivo_ids
            ));
        }

        ?>
        <div class="flavor-panel flavor-proyectos-dashboard-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-portfolio"></span>
                    <?php esc_html_e('Mis Proyectos', 'flavor-chat-ia'); ?>
                </h2>
                <p class="flavor-panel-subtitle">
                    <?php esc_html_e('Proyectos de los colectivos en los que participas.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <!-- KPIs de proyectos -->
            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-clipboard"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($contador_planificados); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Planificados', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-warning">
                    <span class="flavor-kpi-icon dashicons dashicons-controls-play"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($contador_en_curso); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('En Curso', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($contador_completados); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Completados', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Lista de proyectos -->
            <?php if (empty($proyectos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-portfolio"></span>
                    <h3><?php esc_html_e('Sin proyectos activos', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Los proyectos de tus colectivos aparecerán aquí.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-proyectos-lista">
                    <?php foreach ($proyectos as $proyecto): ?>
                        <?php $this->render_card_proyecto($proyecto); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Asambleas
     */
    public function render_tab_asambleas() {
        $identificador_usuario = get_current_user_id();
        if (!$identificador_usuario) {
            echo '<p class="flavor-alert flavor-alert-warning">' .
                 esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_asambleas)) {
            echo '<div class="flavor-alert flavor-alert-info">' .
                 esc_html__('El sistema de asambleas no está configurado.', 'flavor-chat-ia') . '</div>';
            return;
        }

        // Obtener IDs de colectivos del usuario
        $colectivo_ids = $this->obtener_colectivos_usuario($identificador_usuario);

        $asambleas_proximas = [];
        $asambleas_pasadas = [];

        if (!empty($colectivo_ids)) {
            $placeholders = implode(',', array_fill(0, count($colectivo_ids), '%d'));

            // Próximas asambleas (incluyendo hoy)
            $asambleas_proximas = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, c.nombre as colectivo_nombre, c.tipo as colectivo_tipo,
                        (SELECT COUNT(*) FROM {$this->tabla_miembros} WHERE colectivo_id = a.colectivo_id AND estado = 'activo') as total_miembros
                 FROM {$this->tabla_asambleas} a
                 INNER JOIN {$this->tabla_colectivos} c ON a.colectivo_id = c.id
                 WHERE a.colectivo_id IN ($placeholders) AND a.fecha >= CURDATE()
                 ORDER BY a.fecha ASC, a.hora ASC
                 LIMIT 10",
                ...$colectivo_ids
            ));

            // Asambleas pasadas recientes
            $asambleas_pasadas = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, c.nombre as colectivo_nombre, c.tipo as colectivo_tipo
                 FROM {$this->tabla_asambleas} a
                 INNER JOIN {$this->tabla_colectivos} c ON a.colectivo_id = c.id
                 WHERE a.colectivo_id IN ($placeholders) AND a.fecha < CURDATE()
                 ORDER BY a.fecha DESC
                 LIMIT 5",
                ...$colectivo_ids
            ));
        }

        $total_proximas = count($asambleas_proximas);
        $total_pasadas = count($asambleas_pasadas);

        // Encontrar la próxima asamblea
        $proxima_asamblea = !empty($asambleas_proximas) ? $asambleas_proximas[0] : null;
        $dias_para_proxima = null;
        if ($proxima_asamblea) {
            $fecha_asamblea = new DateTime($proxima_asamblea->fecha);
            $hoy = new DateTime();
            $dias_para_proxima = $hoy->diff($fecha_asamblea)->days;
            if ($fecha_asamblea < $hoy) {
                $dias_para_proxima = 0;
            }
        }

        ?>
        <div class="flavor-panel flavor-asambleas-dashboard-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Asambleas', 'flavor-chat-ia'); ?>
                </h2>
                <p class="flavor-panel-subtitle">
                    <?php esc_html_e('Próximas asambleas de tus colectivos.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <!-- KPIs de asambleas -->
            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card flavor-kpi-highlight">
                    <span class="flavor-kpi-icon dashicons dashicons-calendar"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_proximas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Próximas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <?php if ($proxima_asamblea): ?>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-clock"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value">
                            <?php
                            if ($dias_para_proxima === 0) {
                                esc_html_e('Hoy', 'flavor-chat-ia');
                            } elseif ($dias_para_proxima === 1) {
                                esc_html_e('Mañana', 'flavor-chat-ia');
                            } else {
                                printf(esc_html__('%d días', 'flavor-chat-ia'), $dias_para_proxima);
                            }
                            ?>
                        </span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Próxima Asamblea', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-backup"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_pasadas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Pasadas (recientes)', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Próximas asambleas -->
            <div class="flavor-section">
                <h3 class="flavor-section-title">
                    <span class="dashicons dashicons-calendar"></span>
                    <?php esc_html_e('Próximas Asambleas', 'flavor-chat-ia'); ?>
                </h3>

                <?php if (empty($asambleas_proximas)): ?>
                    <div class="flavor-empty-state flavor-empty-state-sm">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <p><?php esc_html_e('No hay asambleas programadas próximamente.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="flavor-asambleas-timeline">
                        <?php foreach ($asambleas_proximas as $asamblea): ?>
                            <?php $this->render_card_asamblea($asamblea, true); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($asambleas_pasadas)): ?>
            <div class="flavor-section">
                <h3 class="flavor-section-title">
                    <span class="dashicons dashicons-backup"></span>
                    <?php esc_html_e('Asambleas Recientes', 'flavor-chat-ia'); ?>
                </h3>
                <div class="flavor-asambleas-pasadas">
                    <?php foreach ($asambleas_pasadas as $asamblea): ?>
                        <?php $this->render_card_asamblea($asamblea, false); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza una tarjeta de colectivo con actividad reciente
     *
     * @param object $colectivo Datos del colectivo
     */
    private function render_card_colectivo($colectivo) {
        global $wpdb;

        $url_detalle = home_url('/colectivos/detalle/?colectivo=' . $colectivo->id);
        $etiqueta_tipo = $this->etiquetas_tipos[$colectivo->tipo] ?? $colectivo->tipo;
        $etiqueta_rol = $this->etiquetas_roles[$colectivo->rol] ?? ucfirst($colectivo->rol);

        // Obtener actividad reciente del colectivo
        $actividad_reciente = $this->obtener_actividad_colectivo($colectivo->id);

        // Próxima asamblea
        $proxima_asamblea = $wpdb->get_row($wpdb->prepare(
            "SELECT titulo, fecha, hora FROM {$this->tabla_asambleas}
             WHERE colectivo_id = %d AND fecha >= CURDATE() AND estado IN ('convocada', 'programada')
             ORDER BY fecha ASC LIMIT 1",
            $colectivo->id
        ));

        ?>
        <div class="flavor-card flavor-colectivo-card-dashboard">
            <div class="flavor-card-header">
                <div class="flavor-card-icon">
                    <?php if (!empty($colectivo->imagen)): ?>
                        <img src="<?php echo esc_url($colectivo->imagen); ?>" alt="<?php echo esc_attr($colectivo->nombre); ?>">
                    <?php else: ?>
                        <span class="dashicons dashicons-groups"></span>
                    <?php endif; ?>
                </div>
                <div class="flavor-card-title-wrap">
                    <h4 class="flavor-card-title">
                        <a href="<?php echo esc_url($url_detalle); ?>">
                            <?php echo esc_html($colectivo->nombre); ?>
                        </a>
                    </h4>
                    <div class="flavor-card-badges">
                        <span class="flavor-badge flavor-badge-tipo"><?php echo esc_html($etiqueta_tipo); ?></span>
                        <span class="flavor-badge flavor-badge-rol flavor-badge-<?php echo esc_attr($colectivo->rol); ?>">
                            <?php echo esc_html($etiqueta_rol); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="flavor-card-body">
                <div class="flavor-card-stats">
                    <span class="flavor-stat" title="<?php esc_attr_e('Miembros activos', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-groups"></span>
                        <?php echo number_format_i18n($colectivo->total_miembros); ?>
                    </span>
                    <span class="flavor-stat" title="<?php esc_attr_e('Proyectos activos', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php echo number_format_i18n($colectivo->proyectos_activos); ?>
                    </span>
                    <span class="flavor-stat flavor-stat-muted" title="<?php esc_attr_e('Miembro desde', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-calendar"></span>
                        <?php echo esc_html(date_i18n('M Y', strtotime($colectivo->fecha_union))); ?>
                    </span>
                </div>

                <?php if ($proxima_asamblea): ?>
                <div class="flavor-card-proxima-asamblea">
                    <span class="dashicons dashicons-megaphone"></span>
                    <div class="flavor-proxima-asamblea-info">
                        <strong><?php echo esc_html($proxima_asamblea->titulo); ?></strong>
                        <span class="flavor-fecha">
                            <?php echo esc_html(date_i18n('j M', strtotime($proxima_asamblea->fecha))); ?>
                            <?php if (!empty($proxima_asamblea->hora)): ?>
                                - <?php echo esc_html($proxima_asamblea->hora); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($actividad_reciente)): ?>
                <div class="flavor-card-actividad">
                    <h5><?php esc_html_e('Actividad Reciente', 'flavor-chat-ia'); ?></h5>
                    <ul class="flavor-actividad-lista">
                        <?php foreach ($actividad_reciente as $actividad): ?>
                            <li class="flavor-actividad-item flavor-actividad-<?php echo esc_attr($actividad['tipo']); ?>">
                                <span class="flavor-actividad-icon dashicons dashicons-<?php echo esc_attr($actividad['icono']); ?>"></span>
                                <span class="flavor-actividad-texto"><?php echo esc_html($actividad['texto']); ?></span>
                                <span class="flavor-actividad-fecha"><?php echo esc_html($actividad['fecha']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>

            <div class="flavor-card-footer">
                <a href="<?php echo esc_url($url_detalle); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                    <?php esc_html_e('Ver Colectivo', 'flavor-chat-ia'); ?>
                </a>
                <?php if (in_array($colectivo->rol, ['presidente', 'secretario', 'admin'])): ?>
                    <a href="<?php echo esc_url(add_query_arg(['colectivo' => $colectivo->id, 'accion' => 'gestionar'], home_url('/colectivos/'))); ?>"
                       class="flavor-btn flavor-btn-sm flavor-btn-primary">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e('Gestionar', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza una tarjeta de proyecto
     *
     * @param object $proyecto Datos del proyecto
     */
    private function render_card_proyecto($proyecto) {
        $clase_estado = $this->obtener_clase_estado_proyecto($proyecto->estado);
        $etiqueta_estado = $this->obtener_etiqueta_estado_proyecto($proyecto->estado);
        $progreso = isset($proyecto->progreso) ? (int) $proyecto->progreso : 0;

        ?>
        <div class="flavor-proyecto-item">
            <div class="flavor-proyecto-header">
                <div class="flavor-proyecto-info">
                    <h4 class="flavor-proyecto-titulo"><?php echo esc_html($proyecto->nombre); ?></h4>
                    <span class="flavor-proyecto-colectivo">
                        <span class="dashicons dashicons-groups"></span>
                        <?php echo esc_html($proyecto->colectivo_nombre); ?>
                    </span>
                </div>
                <span class="flavor-badge flavor-badge-<?php echo esc_attr($clase_estado); ?>">
                    <?php echo esc_html($etiqueta_estado); ?>
                </span>
            </div>

            <?php if (!empty($proyecto->descripcion)): ?>
                <p class="flavor-proyecto-descripcion">
                    <?php echo esc_html(wp_trim_words($proyecto->descripcion, 25)); ?>
                </p>
            <?php endif; ?>

            <?php if ($proyecto->estado === 'en_curso' || $proyecto->estado === 'activo'): ?>
                <div class="flavor-proyecto-progreso">
                    <div class="flavor-progreso-bar">
                        <div class="flavor-progreso-fill" style="width: <?php echo esc_attr($progreso); ?>%;"></div>
                    </div>
                    <span class="flavor-progreso-texto"><?php echo esc_html($progreso); ?>%</span>
                </div>
            <?php endif; ?>

            <div class="flavor-proyecto-meta">
                <?php if (!empty($proyecto->fecha_inicio)): ?>
                    <span class="flavor-meta-item">
                        <span class="dashicons dashicons-calendar"></span>
                        <?php echo esc_html(date_i18n('j M Y', strtotime($proyecto->fecha_inicio))); ?>
                        <?php if (!empty($proyecto->fecha_fin)): ?>
                            - <?php echo esc_html(date_i18n('j M Y', strtotime($proyecto->fecha_fin))); ?>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($proyecto->presupuesto) && $proyecto->presupuesto > 0): ?>
                    <span class="flavor-meta-item">
                        <span class="dashicons dashicons-money-alt"></span>
                        <?php echo esc_html(number_format($proyecto->presupuesto, 0, ',', '.')); ?> &euro;
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza una tarjeta de asamblea
     *
     * @param object $asamblea Datos de la asamblea
     * @param bool $es_futura Si es una asamblea futura
     */
    private function render_card_asamblea($asamblea, $es_futura = true) {
        $fecha_formateada = date_i18n('j', strtotime($asamblea->fecha));
        $mes_formateado = date_i18n('M', strtotime($asamblea->fecha));
        $anio_formateado = date_i18n('Y', strtotime($asamblea->fecha));

        $es_hoy = (date('Y-m-d') === $asamblea->fecha);
        $es_manana = (date('Y-m-d', strtotime('+1 day')) === $asamblea->fecha);

        $clase_fecha = $es_hoy ? 'flavor-fecha-hoy' : ($es_manana ? 'flavor-fecha-manana' : '');

        ?>
        <div class="flavor-asamblea-card <?php echo $es_futura ? 'flavor-asamblea-futura' : 'flavor-asamblea-pasada'; ?>">
            <div class="flavor-asamblea-fecha <?php echo esc_attr($clase_fecha); ?>">
                <span class="flavor-dia"><?php echo esc_html($fecha_formateada); ?></span>
                <span class="flavor-mes"><?php echo esc_html($mes_formateado); ?></span>
                <?php if ($es_hoy): ?>
                    <span class="flavor-etiqueta-hoy"><?php esc_html_e('Hoy', 'flavor-chat-ia'); ?></span>
                <?php elseif ($es_manana): ?>
                    <span class="flavor-etiqueta-manana"><?php esc_html_e('Mañana', 'flavor-chat-ia'); ?></span>
                <?php endif; ?>
            </div>

            <div class="flavor-asamblea-contenido">
                <h4 class="flavor-asamblea-titulo"><?php echo esc_html($asamblea->titulo); ?></h4>
                <span class="flavor-asamblea-colectivo">
                    <span class="dashicons dashicons-groups"></span>
                    <?php echo esc_html($asamblea->colectivo_nombre); ?>
                </span>

                <div class="flavor-asamblea-detalles">
                    <?php if (!empty($asamblea->hora)): ?>
                        <span class="flavor-detalle">
                            <span class="dashicons dashicons-clock"></span>
                            <?php echo esc_html($asamblea->hora); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($asamblea->ubicacion)): ?>
                        <span class="flavor-detalle">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($asamblea->ubicacion); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (isset($asamblea->total_miembros) && $asamblea->total_miembros > 0): ?>
                        <span class="flavor-detalle flavor-detalle-miembros">
                            <span class="dashicons dashicons-groups"></span>
                            <?php printf(esc_html__('%d miembros', 'flavor-chat-ia'), $asamblea->total_miembros); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($asamblea->orden_del_dia) && $es_futura): ?>
                    <details class="flavor-orden-del-dia">
                        <summary><?php esc_html_e('Ver orden del día', 'flavor-chat-ia'); ?></summary>
                        <div class="flavor-orden-contenido">
                            <?php echo wp_kses_post(wpautop($asamblea->orden_del_dia)); ?>
                        </div>
                    </details>
                <?php endif; ?>
            </div>

            <?php if ($es_futura): ?>
                <div class="flavor-asamblea-acciones">
                    <button class="flavor-btn flavor-btn-sm flavor-btn-primary flavor-confirmar-asistencia-btn"
                            data-asamblea-id="<?php echo esc_attr($asamblea->id); ?>">
                        <span class="dashicons dashicons-yes"></span>
                        <?php esc_html_e('Confirmar', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Obtiene la actividad reciente de un colectivo
     *
     * @param int $colectivo_id ID del colectivo
     * @return array Lista de actividades
     */
    private function obtener_actividad_colectivo($colectivo_id) {
        global $wpdb;
        $actividades = [];

        // Nuevos miembros (últimos 7 días)
        $nuevos_miembros = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_miembros}
             WHERE colectivo_id = %d AND estado = 'activo'
             AND fecha_union >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $colectivo_id
        ));

        if ($nuevos_miembros > 0) {
            $actividades[] = [
                'tipo'  => 'miembro',
                'icono' => 'admin-users',
                'texto' => sprintf(
                    _n('%d nuevo miembro', '%d nuevos miembros', $nuevos_miembros, 'flavor-chat-ia'),
                    $nuevos_miembros
                ),
                'fecha' => __('Esta semana', 'flavor-chat-ia'),
            ];
        }

        // Proyectos actualizados recientemente
        $proyecto_reciente = $wpdb->get_row($wpdb->prepare(
            "SELECT nombre, estado, fecha_actualizacion FROM {$this->tabla_proyectos}
             WHERE colectivo_id = %d
             ORDER BY fecha_actualizacion DESC LIMIT 1",
            $colectivo_id
        ));

        if ($proyecto_reciente && !empty($proyecto_reciente->fecha_actualizacion)) {
            $dias_desde_actualizacion = (int) ((time() - strtotime($proyecto_reciente->fecha_actualizacion)) / DAY_IN_SECONDS);
            if ($dias_desde_actualizacion <= 14) {
                $actividades[] = [
                    'tipo'  => 'proyecto',
                    'icono' => 'portfolio',
                    'texto' => sprintf(
                        __('Proyecto "%s" actualizado', 'flavor-chat-ia'),
                        wp_trim_words($proyecto_reciente->nombre, 4)
                    ),
                    'fecha' => $dias_desde_actualizacion === 0
                        ? __('Hoy', 'flavor-chat-ia')
                        : sprintf(__('Hace %d días', 'flavor-chat-ia'), $dias_desde_actualizacion),
                ];
            }
        }

        // Última asamblea celebrada
        $ultima_asamblea = $wpdb->get_row($wpdb->prepare(
            "SELECT titulo, fecha FROM {$this->tabla_asambleas}
             WHERE colectivo_id = %d AND fecha < CURDATE() AND estado = 'celebrada'
             ORDER BY fecha DESC LIMIT 1",
            $colectivo_id
        ));

        if ($ultima_asamblea) {
            $actividades[] = [
                'tipo'  => 'asamblea',
                'icono' => 'calendar-alt',
                'texto' => sprintf(__('Asamblea "%s"', 'flavor-chat-ia'), wp_trim_words($ultima_asamblea->titulo, 4)),
                'fecha' => date_i18n('j M', strtotime($ultima_asamblea->fecha)),
            ];
        }

        return array_slice($actividades, 0, 3);
    }

    /**
     * Obtiene los IDs de colectivos del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array IDs de colectivos
     */
    private function obtener_colectivos_usuario($usuario_id) {
        global $wpdb;

        $colectivos = $wpdb->get_col($wpdb->prepare(
            "SELECT colectivo_id FROM {$this->tabla_miembros}
             WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        return array_map('absint', $colectivos);
    }

    /**
     * Cuenta los proyectos activos del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return int
     */
    private function contar_proyectos_usuario($usuario_id) {
        global $wpdb;

        $colectivo_ids = $this->obtener_colectivos_usuario($usuario_id);
        if (empty($colectivo_ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($colectivo_ids), '%d'));

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_proyectos}
             WHERE colectivo_id IN ($placeholders) AND estado IN ('en_curso', 'activo')",
            ...$colectivo_ids
        ));
    }

    /**
     * Cuenta las próximas asambleas del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return int
     */
    private function contar_asambleas_proximas($usuario_id) {
        global $wpdb;

        $colectivo_ids = $this->obtener_colectivos_usuario($usuario_id);
        if (empty($colectivo_ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($colectivo_ids), '%d'));

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_asambleas}
             WHERE colectivo_id IN ($placeholders) AND fecha >= CURDATE()",
            ...$colectivo_ids
        ));
    }

    /**
     * Obtiene la clase CSS para el estado del proyecto
     *
     * @param string $estado Estado del proyecto
     * @return string
     */
    private function obtener_clase_estado_proyecto($estado) {
        $clases = [
            'planificado' => 'info',
            'en_curso'    => 'warning',
            'activo'      => 'warning',
            'completado'  => 'success',
            'cancelado'   => 'danger',
            'pausado'     => 'muted',
        ];

        return $clases[$estado] ?? 'default';
    }

    /**
     * Obtiene la etiqueta legible del estado del proyecto
     *
     * @param string $estado Estado del proyecto
     * @return string
     */
    private function obtener_etiqueta_estado_proyecto($estado) {
        $etiquetas = [
            'planificado' => __('Planificado', 'flavor-chat-ia'),
            'en_curso'    => __('En Curso', 'flavor-chat-ia'),
            'activo'      => __('Activo', 'flavor-chat-ia'),
            'completado'  => __('Completado', 'flavor-chat-ia'),
            'cancelado'   => __('Cancelado', 'flavor-chat-ia'),
            'pausado'     => __('Pausado', 'flavor-chat-ia'),
        ];

        return $etiquetas[$estado] ?? ucfirst($estado);
    }

    /**
     * Enqueue de assets
     */
    public function enqueue_assets() {
        if (!is_page() || !is_user_logged_in()) {
            return;
        }

        // Los estilos base del dashboard ya incluyen los necesarios
        // Solo añadimos estilos específicos si es necesario
    }
}

// Inicializar la instancia
Flavor_Colectivos_Dashboard_Tab::get_instance();
