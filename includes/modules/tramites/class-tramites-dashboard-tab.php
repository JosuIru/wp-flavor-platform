<?php
/**
 * Dashboard Tab para Tramites - Panel de Usuario
 *
 * Gestiona los tabs del dashboard de usuario para el modulo de tramites,
 * mostrando expedientes, tramites pendientes e historial.
 *
 * @package FlavorChatIA
 * @subpackage Modules\Tramites
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona los tabs de usuario para tramites
 */
class Flavor_Tramites_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Tramites_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Tablas de la base de datos
     */
    private $tabla_expedientes;
    private $tabla_documentos;
    private $tabla_tipos_tramite;
    private $tabla_estados;
    private $tabla_historial_estados;

    /**
     * Estados de expedientes agrupados
     */
    private $estados_pendientes = ['pendiente', 'en_proceso', 'requiere_documentacion', 'en_revision'];
    private $estados_completados = ['resuelto', 'aprobado', 'rechazado', 'cancelado', 'archivado'];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;

        $this->tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
        $this->tabla_documentos = $wpdb->prefix . 'flavor_documentos_expediente';
        $this->tabla_tipos_tramite = $wpdb->prefix . 'flavor_tipos_tramite';
        $this->tabla_estados = $wpdb->prefix . 'flavor_estados_tramite';
        $this->tabla_historial_estados = $wpdb->prefix . 'flavor_historial_estados_expediente';

        $this->init();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Tramites_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa el componente
     */
    private function init() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 25);
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
    }

    /**
     * Registra los tabs en el dashboard de usuario
     *
     * @param array $tabs Tabs existentes
     * @return array Tabs modificados
     */
    public function registrar_tabs($tabs) {
        // Tab principal: Mis Expedientes
        $tabs['tramites-mis-expedientes'] = [
            'id' => 'tramites-mis-expedientes',
            'label' => __('Mis Expedientes', 'flavor-chat-ia'),
            'icon' => 'dashicons-clipboard',
            'orden' => 30,
            'callback' => [$this, 'render_tab_mis_expedientes'],
            'badge' => $this->contar_expedientes_activos(),
        ];

        // Sub-tab: Pendientes de accion
        $tabs['tramites-pendientes'] = [
            'id' => 'tramites-pendientes',
            'label' => __('Pendientes', 'flavor-chat-ia'),
            'icon' => 'dashicons-warning',
            'orden' => 31,
            'parent' => 'tramites-mis-expedientes',
            'callback' => [$this, 'render_tab_pendientes'],
            'badge' => $this->contar_expedientes_pendientes_accion(),
        ];

        // Sub-tab: Historial
        $tabs['tramites-historial'] = [
            'id' => 'tramites-historial',
            'label' => __('Historial', 'flavor-chat-ia'),
            'icon' => 'dashicons-backup',
            'orden' => 32,
            'parent' => 'tramites-mis-expedientes',
            'callback' => [$this, 'render_tab_historial'],
        ];

        return $tabs;
    }

    /**
     * Registra assets necesarios
     */
    public function registrar_assets() {
        $base_url = FLAVOR_CHAT_IA_URL . 'includes/modules/tramites/assets/';
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.0.0';

        wp_register_style(
            'flavor-tramites-dashboard',
            $base_url . 'css/tramites-dashboard.css',
            ['flavor-dashboard-styles'],
            $version
        );

        wp_register_script(
            'flavor-tramites-dashboard',
            $base_url . 'js/tramites-dashboard.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-tramites-dashboard', 'flavorTramitesDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_tramites_dashboard_nonce'),
            'strings' => [
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error al cargar los datos', 'flavor-chat-ia'),
                'sinExpedientes' => __('No tienes expedientes registrados', 'flavor-chat-ia'),
                'verDetalles' => __('Ver detalles', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encola los assets
     */
    private function enqueue_assets() {
        wp_enqueue_style('flavor-tramites-dashboard');
        wp_enqueue_script('flavor-tramites-dashboard');
    }

    // =========================================================================
    // CONTADORES
    // =========================================================================

    /**
     * Cuenta expedientes activos del usuario
     *
     * @return int
     */
    private function contar_expedientes_activos() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return 0;
        }

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_expedientes)) {
            return 0;
        }

        $estados_activos = implode("','", array_map('esc_sql', $this->estados_pendientes));

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_expedientes}
             WHERE (user_id = %d OR solicitante_id = %d)
             AND estado_actual IN ('{$estados_activos}')",
            $usuario_id,
            $usuario_id
        ));
    }

    /**
     * Cuenta expedientes que requieren accion del usuario
     *
     * @return int
     */
    private function contar_expedientes_pendientes_accion() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return 0;
        }

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_expedientes)) {
            return 0;
        }

        // Expedientes que requieren documentacion o alguna accion del solicitante
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_expedientes}
             WHERE (user_id = %d OR solicitante_id = %d)
             AND estado_actual IN ('requiere_documentacion', 'pendiente')",
            $usuario_id,
            $usuario_id
        ));
    }

    // =========================================================================
    // TABS RENDERS
    // =========================================================================

    /**
     * Renderiza el tab principal de Mis Expedientes
     */
    public function render_tab_mis_expedientes() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            $this->render_mensaje_login();
            return;
        }

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_expedientes)) {
            $this->render_mensaje_no_disponible();
            return;
        }

        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);
        $expedientes_activos = $this->obtener_expedientes_usuario($usuario_id, 'activos', 5);
        ?>
        <div class="flavor-dashboard-tramites">
            <!-- KPIs -->
            <div class="flavor-kpi-grid flavor-grid-4">
                <div class="flavor-kpi-card flavor-kpi-primary">
                    <div class="flavor-kpi-icono">
                        <span class="dashicons dashicons-clipboard"></span>
                    </div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($estadisticas['total']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Total expedientes', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card flavor-kpi-warning">
                    <div class="flavor-kpi-icono">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($estadisticas['en_proceso']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('En proceso', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card flavor-kpi-danger">
                    <div class="flavor-kpi-icono">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($estadisticas['requiere_accion']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Requieren accion', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card flavor-kpi-success">
                    <div class="flavor-kpi-icono">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($estadisticas['completados']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Completados', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Expedientes activos -->
            <div class="flavor-panel flavor-panel-expedientes">
                <div class="flavor-panel-header">
                    <h3>
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php _e('Expedientes en curso', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (!empty($expedientes_activos)) : ?>
                        <a href="<?php echo esc_url(home_url('/mi-portal/?tab=tramites-pendientes')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                            <?php _e('Ver todos', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="flavor-panel-body">
                    <?php if (empty($expedientes_activos)) : ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-portfolio"></span>
                            <p><?php _e('No tienes expedientes activos en este momento.', 'flavor-chat-ia'); ?></p>
                            <a href="<?php echo esc_url(home_url('/tramites/')); ?>" class="flavor-btn flavor-btn-primary">
                                <?php _e('Iniciar un tramite', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="flavor-expedientes-lista">
                            <?php foreach ($expedientes_activos as $expediente) : ?>
                                <?php $this->render_expediente_card($expediente); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acciones rapidas -->
            <div class="flavor-panel flavor-panel-acciones">
                <div class="flavor-panel-header">
                    <h3>
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Acciones rapidas', 'flavor-chat-ia'); ?>
                    </h3>
                </div>
                <div class="flavor-panel-body">
                    <div class="flavor-acciones-grid">
                        <a href="<?php echo esc_url(home_url('/tramites/')); ?>" class="flavor-accion-card">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <span><?php _e('Nuevo tramite', 'flavor-chat-ia'); ?></span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/mi-portal/?tab=tramites-pendientes')); ?>" class="flavor-accion-card">
                            <span class="dashicons dashicons-warning"></span>
                            <span><?php _e('Pendientes', 'flavor-chat-ia'); ?></span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/mi-portal/?tab=tramites-historial')); ?>" class="flavor-accion-card">
                            <span class="dashicons dashicons-backup"></span>
                            <span><?php _e('Historial', 'flavor-chat-ia'); ?></span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/tramites/citas/')); ?>" class="flavor-accion-card">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <span><?php _e('Mis citas', 'flavor-chat-ia'); ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Pendientes
     */
    public function render_tab_pendientes() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            $this->render_mensaje_login();
            return;
        }

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_expedientes)) {
            $this->render_mensaje_no_disponible();
            return;
        }

        $expedientes_pendientes = $this->obtener_expedientes_pendientes_accion($usuario_id);
        ?>
        <div class="flavor-dashboard-tramites-pendientes">
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3>
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Tramites que requieren tu atencion', 'flavor-chat-ia'); ?>
                    </h3>
                </div>

                <div class="flavor-panel-body">
                    <?php if (empty($expedientes_pendientes)) : ?>
                        <div class="flavor-empty-state flavor-empty-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><?php _e('No tienes tramites pendientes de accion. ¡Todo al dia!', 'flavor-chat-ia'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="flavor-alert flavor-alert-warning">
                            <span class="dashicons dashicons-info"></span>
                            <?php printf(
                                _n(
                                    'Tienes %d tramite que requiere tu atencion.',
                                    'Tienes %d tramites que requieren tu atencion.',
                                    count($expedientes_pendientes),
                                    'flavor-chat-ia'
                                ),
                                count($expedientes_pendientes)
                            ); ?>
                        </div>

                        <div class="flavor-expedientes-lista flavor-expedientes-pendientes">
                            <?php foreach ($expedientes_pendientes as $expediente) : ?>
                                <?php $this->render_expediente_card($expediente, true); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Historial
     */
    public function render_tab_historial() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            $this->render_mensaje_login();
            return;
        }

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_expedientes)) {
            $this->render_mensaje_no_disponible();
            return;
        }

        $pagina_actual = max(1, absint($_GET['pag'] ?? 1));
        $por_pagina = 10;
        $expedientes_historial = $this->obtener_expedientes_historial($usuario_id, $pagina_actual, $por_pagina);
        $total_historial = $this->contar_expedientes_historial($usuario_id);
        $total_paginas = ceil($total_historial / $por_pagina);
        ?>
        <div class="flavor-dashboard-tramites-historial">
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3>
                        <span class="dashicons dashicons-backup"></span>
                        <?php _e('Historial de tramites', 'flavor-chat-ia'); ?>
                    </h3>
                    <span class="flavor-badge flavor-badge-muted">
                        <?php printf(__('%d tramites', 'flavor-chat-ia'), $total_historial); ?>
                    </span>
                </div>

                <div class="flavor-panel-body">
                    <?php if (empty($expedientes_historial)) : ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-backup"></span>
                            <p><?php _e('Aun no tienes tramites completados en tu historial.', 'flavor-chat-ia'); ?></p>
                        </div>
                    <?php else : ?>
                        <div class="flavor-expedientes-lista flavor-expedientes-historial">
                            <?php foreach ($expedientes_historial as $expediente) : ?>
                                <?php $this->render_expediente_card_historial($expediente); ?>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($total_paginas > 1) : ?>
                            <div class="flavor-paginacion">
                                <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>
                                    <a href="<?php echo esc_url(add_query_arg('pag', $i)); ?>"
                                       class="flavor-btn flavor-btn-sm <?php echo $i === $pagina_actual ? 'flavor-btn-primary' : 'flavor-btn-outline'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // RENDERS DE EXPEDIENTES
    // =========================================================================

    /**
     * Renderiza una tarjeta de expediente
     *
     * @param object $expediente Datos del expediente
     * @param bool $destacar_accion Si se debe destacar la accion requerida
     */
    private function render_expediente_card($expediente, $destacar_accion = false) {
        $estado_info = $this->get_estado_info($expediente->estado_actual);
        $documentos = $this->contar_documentos_expediente($expediente->id);
        $progreso = $this->calcular_progreso_expediente($expediente);
        $timeline = $this->obtener_timeline_expediente($expediente->id, 3);
        ?>
        <div class="flavor-expediente-card <?php echo $destacar_accion ? 'flavor-expediente-urgente' : ''; ?>">
            <div class="flavor-expediente-header">
                <div class="flavor-expediente-info">
                    <span class="flavor-expediente-numero">
                        <?php echo esc_html($expediente->numero_expediente); ?>
                    </span>
                    <h4 class="flavor-expediente-titulo">
                        <?php echo esc_html($expediente->tipo_nombre ?: __('Tramite', 'flavor-chat-ia')); ?>
                    </h4>
                </div>
                <span class="flavor-badge flavor-badge-<?php echo esc_attr($estado_info['clase']); ?>">
                    <span class="dashicons <?php echo esc_attr($estado_info['icono']); ?>"></span>
                    <?php echo esc_html($estado_info['texto']); ?>
                </span>
            </div>

            <div class="flavor-expediente-meta">
                <span class="flavor-expediente-meta-item">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($expediente->fecha_solicitud))); ?>
                </span>
                <span class="flavor-expediente-meta-item">
                    <span class="dashicons dashicons-media-document"></span>
                    <?php printf(__('%d docs', 'flavor-chat-ia'), $documentos); ?>
                </span>
                <?php if ($expediente->prioridad && $expediente->prioridad !== 'media') : ?>
                    <span class="flavor-expediente-meta-item flavor-prioridad-<?php echo esc_attr($expediente->prioridad); ?>">
                        <span class="dashicons dashicons-flag"></span>
                        <?php echo esc_html(ucfirst($expediente->prioridad)); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Barra de progreso -->
            <div class="flavor-expediente-progreso">
                <div class="flavor-progreso-barra">
                    <div class="flavor-progreso-fill" style="width: <?php echo esc_attr($progreso); ?>%"></div>
                </div>
                <span class="flavor-progreso-texto"><?php echo esc_html($progreso); ?>%</span>
            </div>

            <!-- Timeline mini -->
            <?php if (!empty($timeline)) : ?>
                <div class="flavor-expediente-timeline-mini">
                    <div class="flavor-timeline-mini">
                        <?php foreach ($timeline as $evento) : ?>
                            <div class="flavor-timeline-mini-item">
                                <span class="flavor-timeline-mini-dot <?php echo $evento->estado_nuevo === $expediente->estado_actual ? 'activo' : ''; ?>"></span>
                                <div class="flavor-timeline-mini-contenido">
                                    <span class="flavor-timeline-mini-estado">
                                        <?php echo esc_html($this->get_estado_texto($evento->estado_nuevo)); ?>
                                    </span>
                                    <span class="flavor-timeline-mini-fecha">
                                        <?php echo esc_html(human_time_diff(strtotime($evento->fecha_cambio), current_time('timestamp'))); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($destacar_accion && $expediente->estado_actual === 'requiere_documentacion') : ?>
                <div class="flavor-expediente-alerta">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('Se requiere documentacion adicional', 'flavor-chat-ia'); ?>
                </div>
            <?php endif; ?>

            <div class="flavor-expediente-acciones">
                <a href="<?php echo esc_url($this->get_expediente_url($expediente->id)); ?>"
                   class="flavor-btn flavor-btn-sm flavor-btn-primary">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
                </a>
                <?php if ($expediente->estado_actual === 'requiere_documentacion') : ?>
                    <a href="<?php echo esc_url($this->get_subir_documentos_url($expediente->id)); ?>"
                       class="flavor-btn flavor-btn-sm flavor-btn-warning">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Subir documentos', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza una tarjeta de expediente para el historial
     *
     * @param object $expediente Datos del expediente
     */
    private function render_expediente_card_historial($expediente) {
        $estado_info = $this->get_estado_info($expediente->estado_actual);
        ?>
        <div class="flavor-expediente-card flavor-expediente-historial">
            <div class="flavor-expediente-header">
                <div class="flavor-expediente-info">
                    <span class="flavor-expediente-numero">
                        <?php echo esc_html($expediente->numero_expediente); ?>
                    </span>
                    <h4 class="flavor-expediente-titulo">
                        <?php echo esc_html($expediente->tipo_nombre ?: __('Tramite', 'flavor-chat-ia')); ?>
                    </h4>
                </div>
                <span class="flavor-badge flavor-badge-<?php echo esc_attr($estado_info['clase']); ?>">
                    <?php echo esc_html($estado_info['texto']); ?>
                </span>
            </div>

            <div class="flavor-expediente-fechas">
                <div class="flavor-fecha-item">
                    <span class="flavor-fecha-label"><?php _e('Iniciado:', 'flavor-chat-ia'); ?></span>
                    <span class="flavor-fecha-valor">
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($expediente->fecha_solicitud))); ?>
                    </span>
                </div>
                <?php if ($expediente->fecha_resolucion) : ?>
                    <div class="flavor-fecha-item">
                        <span class="flavor-fecha-label"><?php _e('Resuelto:', 'flavor-chat-ia'); ?></span>
                        <span class="flavor-fecha-valor">
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($expediente->fecha_resolucion))); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flavor-expediente-acciones">
                <a href="<?php echo esc_url($this->get_expediente_url($expediente->id)); ?>"
                   class="flavor-btn flavor-btn-sm flavor-btn-outline">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // METODOS DE DATOS
    // =========================================================================

    /**
     * Obtiene estadisticas del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array Estadisticas
     */
    private function obtener_estadisticas_usuario($usuario_id) {
        global $wpdb;

        $estados_proceso = implode("','", array_map('esc_sql', ['en_proceso', 'en_revision']));
        $estados_accion = implode("','", array_map('esc_sql', ['requiere_documentacion', 'pendiente']));
        $estados_completados = implode("','", array_map('esc_sql', $this->estados_completados));

        $estadisticas = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado_actual IN ('{$estados_proceso}') THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado_actual IN ('{$estados_accion}') THEN 1 ELSE 0 END) as requiere_accion,
                SUM(CASE WHEN estado_actual IN ('{$estados_completados}') THEN 1 ELSE 0 END) as completados
             FROM {$this->tabla_expedientes}
             WHERE user_id = %d OR solicitante_id = %d",
            $usuario_id,
            $usuario_id
        ), ARRAY_A);

        return $estadisticas ?: [
            'total' => 0,
            'en_proceso' => 0,
            'requiere_accion' => 0,
            'completados' => 0,
        ];
    }

    /**
     * Obtiene expedientes del usuario
     *
     * @param int $usuario_id ID del usuario
     * @param string $tipo Tipo: 'activos', 'todos'
     * @param int $limite Limite de resultados
     * @return array Expedientes
     */
    private function obtener_expedientes_usuario($usuario_id, $tipo = 'activos', $limite = 10) {
        global $wpdb;

        $where_estado = '';
        if ($tipo === 'activos') {
            $estados = implode("','", array_map('esc_sql', $this->estados_pendientes));
            $where_estado = "AND e.estado_actual IN ('{$estados}')";
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono
             FROM {$this->tabla_expedientes} e
             LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
             WHERE (e.user_id = %d OR e.solicitante_id = %d) {$where_estado}
             ORDER BY e.fecha_solicitud DESC
             LIMIT %d",
            $usuario_id,
            $usuario_id,
            $limite
        ));
    }

    /**
     * Obtiene expedientes pendientes de accion
     *
     * @param int $usuario_id ID del usuario
     * @return array Expedientes
     */
    private function obtener_expedientes_pendientes_accion($usuario_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, t.nombre as tipo_nombre, t.icono as tipo_icono
             FROM {$this->tabla_expedientes} e
             LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
             WHERE (e.user_id = %d OR e.solicitante_id = %d)
             AND e.estado_actual IN ('requiere_documentacion', 'pendiente')
             ORDER BY
                CASE e.prioridad
                    WHEN 'urgente' THEN 1
                    WHEN 'alta' THEN 2
                    WHEN 'media' THEN 3
                    ELSE 4
                END,
                e.fecha_solicitud ASC",
            $usuario_id,
            $usuario_id
        ));
    }

    /**
     * Obtiene expedientes del historial
     *
     * @param int $usuario_id ID del usuario
     * @param int $pagina Pagina actual
     * @param int $por_pagina Items por pagina
     * @return array Expedientes
     */
    private function obtener_expedientes_historial($usuario_id, $pagina = 1, $por_pagina = 10) {
        global $wpdb;

        $offset = ($pagina - 1) * $por_pagina;
        $estados = implode("','", array_map('esc_sql', $this->estados_completados));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, t.nombre as tipo_nombre
             FROM {$this->tabla_expedientes} e
             LEFT JOIN {$this->tabla_tipos_tramite} t ON e.tipo_tramite_id = t.id
             WHERE (e.user_id = %d OR e.solicitante_id = %d)
             AND e.estado_actual IN ('{$estados}')
             ORDER BY COALESCE(e.fecha_resolucion, e.updated_at) DESC
             LIMIT %d OFFSET %d",
            $usuario_id,
            $usuario_id,
            $por_pagina,
            $offset
        ));
    }

    /**
     * Cuenta expedientes del historial
     *
     * @param int $usuario_id ID del usuario
     * @return int Total
     */
    private function contar_expedientes_historial($usuario_id) {
        global $wpdb;

        $estados = implode("','", array_map('esc_sql', $this->estados_completados));

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_expedientes}
             WHERE (user_id = %d OR solicitante_id = %d)
             AND estado_actual IN ('{$estados}')",
            $usuario_id,
            $usuario_id
        ));
    }

    /**
     * Cuenta documentos de un expediente
     *
     * @param int $expediente_id ID del expediente
     * @return int Total de documentos
     */
    private function contar_documentos_expediente($expediente_id) {
        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_documentos)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_documentos} WHERE expediente_id = %d",
            $expediente_id
        ));
    }

    /**
     * Obtiene el timeline de estados de un expediente
     *
     * @param int $expediente_id ID del expediente
     * @param int $limite Limite de eventos
     * @return array Timeline
     */
    private function obtener_timeline_expediente($expediente_id, $limite = 5) {
        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_historial_estados)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, u.display_name as usuario_nombre
             FROM {$this->tabla_historial_estados} h
             LEFT JOIN {$wpdb->users} u ON h.usuario_id = u.ID
             WHERE h.expediente_id = %d
             ORDER BY h.fecha_cambio DESC
             LIMIT %d",
            $expediente_id,
            $limite
        ));
    }

    /**
     * Calcula el progreso de un expediente
     *
     * @param object $expediente Datos del expediente
     * @return int Porcentaje de progreso
     */
    private function calcular_progreso_expediente($expediente) {
        $progresos = [
            'pendiente' => 10,
            'en_revision' => 30,
            'requiere_documentacion' => 40,
            'en_proceso' => 60,
            'aprobado' => 90,
            'resuelto' => 100,
            'rechazado' => 100,
            'cancelado' => 100,
            'archivado' => 100,
        ];

        return $progresos[$expediente->estado_actual] ?? 50;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Obtiene informacion de un estado
     *
     * @param string $estado Codigo del estado
     * @return array Info del estado
     */
    private function get_estado_info($estado) {
        $estados = [
            'pendiente' => [
                'texto' => __('Pendiente', 'flavor-chat-ia'),
                'clase' => 'warning',
                'icono' => 'dashicons-clock',
            ],
            'en_revision' => [
                'texto' => __('En revision', 'flavor-chat-ia'),
                'clase' => 'info',
                'icono' => 'dashicons-visibility',
            ],
            'requiere_documentacion' => [
                'texto' => __('Requiere documentacion', 'flavor-chat-ia'),
                'clase' => 'danger',
                'icono' => 'dashicons-warning',
            ],
            'en_proceso' => [
                'texto' => __('En proceso', 'flavor-chat-ia'),
                'clase' => 'primary',
                'icono' => 'dashicons-admin-tools',
            ],
            'aprobado' => [
                'texto' => __('Aprobado', 'flavor-chat-ia'),
                'clase' => 'success',
                'icono' => 'dashicons-yes-alt',
            ],
            'resuelto' => [
                'texto' => __('Resuelto', 'flavor-chat-ia'),
                'clase' => 'success',
                'icono' => 'dashicons-yes-alt',
            ],
            'rechazado' => [
                'texto' => __('Rechazado', 'flavor-chat-ia'),
                'clase' => 'danger',
                'icono' => 'dashicons-dismiss',
            ],
            'cancelado' => [
                'texto' => __('Cancelado', 'flavor-chat-ia'),
                'clase' => 'muted',
                'icono' => 'dashicons-no',
            ],
            'archivado' => [
                'texto' => __('Archivado', 'flavor-chat-ia'),
                'clase' => 'muted',
                'icono' => 'dashicons-archive',
            ],
        ];

        return $estados[$estado] ?? [
            'texto' => ucfirst($estado),
            'clase' => 'muted',
            'icono' => 'dashicons-marker',
        ];
    }

    /**
     * Obtiene texto de un estado
     *
     * @param string $estado Codigo del estado
     * @return string Texto del estado
     */
    private function get_estado_texto($estado) {
        $info = $this->get_estado_info($estado);
        return $info['texto'];
    }

    /**
     * Obtiene URL de un expediente
     *
     * @param int $expediente_id ID del expediente
     * @return string URL
     */
    private function get_expediente_url($expediente_id) {
        return add_query_arg([
            'expediente_id' => $expediente_id,
        ], home_url('/tramites/seguimiento/'));
    }

    /**
     * Obtiene URL para subir documentos
     *
     * @param int $expediente_id ID del expediente
     * @return string URL
     */
    private function get_subir_documentos_url($expediente_id) {
        return add_query_arg([
            'expediente_id' => $expediente_id,
            'accion' => 'documentos',
        ], home_url('/tramites/seguimiento/'));
    }

    /**
     * Renderiza mensaje de login requerido
     */
    private function render_mensaje_login() {
        ?>
        <div class="flavor-alert flavor-alert-warning">
            <span class="dashicons dashicons-lock"></span>
            <?php _e('Debes iniciar sesion para ver tus tramites.', 'flavor-chat-ia'); ?>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-btn flavor-btn-sm">
                <?php _e('Iniciar sesion', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Renderiza mensaje de modulo no disponible
     */
    private function render_mensaje_no_disponible() {
        ?>
        <div class="flavor-alert flavor-alert-info">
            <span class="dashicons dashicons-info"></span>
            <?php _e('El sistema de tramites no esta disponible en este momento.', 'flavor-chat-ia'); ?>
        </div>
        <?php
    }
}

// Inicializar la clase
Flavor_Tramites_Dashboard_Tab::get_instance();
