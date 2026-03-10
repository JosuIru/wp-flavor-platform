<?php
/**
 * Dashboard Tab para Transparencia
 *
 * Gestiona los tabs del dashboard de usuario para el portal de transparencia:
 * - Mis Solicitudes: Solicitudes de informacion del usuario
 * - Seguimiento: Estado de solicitudes pendientes con timeline
 * - Documentos Guardados: Documentos que el usuario ha guardado
 *
 * @package FlavorChatIA
 * @subpackage Modules\Transparencia
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario del modulo Transparencia
 */
class Flavor_Transparencia_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Transparencia_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Prefijo de tablas
     * @var string
     */
    private $prefijo_tabla;

    /**
     * Estados de solicitudes con configuracion visual
     * @var array
     */
    private $estados_solicitud;

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->prefijo_tabla = $wpdb->prefix . 'flavor_transparencia_';
        $this->inicializar_estados();
        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Transparencia_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa la configuracion de estados
     */
    private function inicializar_estados() {
        $this->estados_solicitud = [
            'recibida' => [
                'label' => __('Recibida', 'flavor-chat-ia'),
                'color' => 'blue',
                'icon' => 'dashicons-email-alt',
                'descripcion' => __('Solicitud registrada en el sistema', 'flavor-chat-ia'),
            ],
            'admitida' => [
                'label' => __('Admitida', 'flavor-chat-ia'),
                'color' => 'cyan',
                'icon' => 'dashicons-yes-alt',
                'descripcion' => __('Solicitud admitida a tramite', 'flavor-chat-ia'),
            ],
            'en_tramite' => [
                'label' => __('En Tramite', 'flavor-chat-ia'),
                'color' => 'yellow',
                'icon' => 'dashicons-admin-generic',
                'descripcion' => __('Solicitud en proceso de gestion', 'flavor-chat-ia'),
            ],
            'ampliacion' => [
                'label' => __('Ampliacion', 'flavor-chat-ia'),
                'color' => 'orange',
                'icon' => 'dashicons-edit',
                'descripcion' => __('Se requiere informacion adicional', 'flavor-chat-ia'),
            ],
            'resuelta' => [
                'label' => __('Resuelta', 'flavor-chat-ia'),
                'color' => 'green',
                'icon' => 'dashicons-yes',
                'descripcion' => __('Solicitud resuelta favorablemente', 'flavor-chat-ia'),
            ],
            'denegada' => [
                'label' => __('Denegada', 'flavor-chat-ia'),
                'color' => 'red',
                'icon' => 'dashicons-dismiss',
                'descripcion' => __('Solicitud denegada', 'flavor-chat-ia'),
            ],
            'desistida' => [
                'label' => __('Desistida', 'flavor-chat-ia'),
                'color' => 'gray',
                'icon' => 'dashicons-minus',
                'descripcion' => __('Solicitante desistio de la solicitud', 'flavor-chat-ia'),
            ],
            'archivada' => [
                'label' => __('Archivada', 'flavor-chat-ia'),
                'color' => 'gray',
                'icon' => 'dashicons-archive',
                'descripcion' => __('Solicitud archivada', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers para documentos guardados
        add_action('wp_ajax_transparencia_guardar_documento', [$this, 'ajax_guardar_documento']);
        add_action('wp_ajax_transparencia_quitar_documento', [$this, 'ajax_quitar_documento']);
    }

    /**
     * Registra los tabs del modulo en el dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        $tabs['transparencia-mis-solicitudes'] = [
            'label' => __('Mis Solicitudes', 'flavor-chat-ia'),
            'icon' => 'clipboard-list',
            'callback' => [$this, 'render_tab_mis_solicitudes'],
            'orden' => 80,
            'grupo' => 'transparencia',
        ];

        $tabs['transparencia-seguimiento'] = [
            'label' => __('Seguimiento', 'flavor-chat-ia'),
            'icon' => 'search',
            'callback' => [$this, 'render_tab_seguimiento'],
            'orden' => 81,
            'grupo' => 'transparencia',
        ];

        $tabs['transparencia-documentos-guardados'] = [
            'label' => __('Docs. Guardados', 'flavor-chat-ia'),
            'icon' => 'bookmark',
            'callback' => [$this, 'render_tab_documentos_guardados'],
            'orden' => 82,
            'grupo' => 'transparencia',
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de Mis Solicitudes
     */
    public function render_tab_mis_solicitudes() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            $this->render_login_requerido();
            return;
        }

        global $wpdb;
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';

        // Verificar que existe la tabla
        if (!$this->tabla_existe($tabla_solicitudes)) {
            $this->render_modulo_no_configurado();
            return;
        }

        // Obtener solicitudes del usuario
        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT id, numero_expediente, titulo, categoria, estado, prioridad,
                    fecha_solicitud, fecha_limite, fecha_resolucion, dias_tramitacion
             FROM {$tabla_solicitudes}
             WHERE user_id = %d
             ORDER BY fecha_solicitud DESC
             LIMIT 50",
            $usuario_id
        ));

        // Calcular estadisticas
        $estadisticas = $this->calcular_estadisticas_usuario($usuario_id);

        ?>
        <div class="flavor-panel flavor-transparencia-panel">
            <div class="flavor-panel-header">
                <div class="flavor-panel-header-content">
                    <h2>
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Mis Solicitudes de Informacion', 'flavor-chat-ia'); ?>
                    </h2>
                    <p class="flavor-panel-subtitle">
                        <?php esc_html_e('Historial de solicitudes de acceso a informacion publica', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/nueva-solicitud/')); ?>"
                   class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nueva Solicitud', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- KPIs -->
            <div class="flavor-panel-kpis flavor-kpis-4">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-clipboard"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['total']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Total Solicitudes', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-warning">
                    <span class="flavor-kpi-icon dashicons dashicons-clock"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['pendientes']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('En Tramite', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['resueltas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Resueltas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-calendar-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['promedio_dias']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Dias Promedio', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Listado de solicitudes -->
            <div class="flavor-panel-section">
                <?php if (empty($solicitudes)): ?>
                    <div class="flavor-empty-state">
                        <span class="dashicons dashicons-format-aside"></span>
                        <h3><?php esc_html_e('No tienes solicitudes', 'flavor-chat-ia'); ?></h3>
                        <p class="flavor-text-muted">
                            <?php esc_html_e('Puedes solicitar acceso a informacion publica como presupuestos, contratos, actas y mas.', 'flavor-chat-ia'); ?>
                        </p>
                        <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/nueva-solicitud/')); ?>"
                           class="flavor-btn flavor-btn-primary">
                            <?php esc_html_e('Crear mi primera solicitud', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="flavor-table-responsive">
                        <table class="flavor-table flavor-table-hover">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Expediente', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Titulo', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Categoria', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitudes as $solicitud):
                                    $estado_config = $this->estados_solicitud[$solicitud->estado] ?? $this->estados_solicitud['recibida'];
                                ?>
                                    <tr>
                                        <td>
                                            <span class="flavor-expediente-numero">
                                                <?php echo esc_html($solicitud->numero_expediente ?: '#' . $solicitud->id); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/solicitud/' . $solicitud->id . '/')); ?>"
                                               class="flavor-link-primary">
                                                <?php echo esc_html(wp_trim_words($solicitud->titulo, 8)); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="flavor-categoria-badge">
                                                <?php echo esc_html(ucfirst($solicitud->categoria ?: __('General', 'flavor-chat-ia'))); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="flavor-badge flavor-badge-<?php echo esc_attr($estado_config['color']); ?>">
                                                <span class="dashicons <?php echo esc_attr($estado_config['icon']); ?>"></span>
                                                <?php echo esc_html($estado_config['label']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="flavor-fecha">
                                                <?php echo esc_html(date_i18n('d/m/Y', strtotime($solicitud->fecha_solicitud))); ?>
                                            </span>
                                            <?php if ($solicitud->fecha_limite && in_array($solicitud->estado, ['recibida', 'admitida', 'en_tramite'])): ?>
                                                <?php
                                                $dias_restantes = (strtotime($solicitud->fecha_limite) - time()) / DAY_IN_SECONDS;
                                                $clase_urgencia = $dias_restantes <= 5 ? 'flavor-text-danger' : ($dias_restantes <= 10 ? 'flavor-text-warning' : '');
                                                ?>
                                                <br>
                                                <small class="<?php echo esc_attr($clase_urgencia); ?>">
                                                    <?php printf(
                                                        esc_html__('Limite: %s', 'flavor-chat-ia'),
                                                        date_i18n('d/m/Y', strtotime($solicitud->fecha_limite))
                                                    ); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/solicitud/' . $solicitud->id . '/')); ?>"
                                               class="flavor-btn flavor-btn-sm flavor-btn-outline"
                                               title="<?php esc_attr_e('Ver detalle', 'flavor-chat-ia'); ?>">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Seguimiento con timeline
     */
    public function render_tab_seguimiento() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            $this->render_login_requerido();
            return;
        }

        global $wpdb;
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';

        // Verificar que existe la tabla
        if (!$this->tabla_existe($tabla_solicitudes)) {
            $this->render_modulo_no_configurado();
            return;
        }

        // Obtener solicitudes pendientes (no finalizadas)
        $estados_pendientes = ['recibida', 'admitida', 'en_tramite', 'ampliacion'];
        $placeholders = implode(',', array_fill(0, count($estados_pendientes), '%s'));

        $parametros = array_merge([$usuario_id], $estados_pendientes);

        $solicitudes_pendientes = $wpdb->get_results($wpdb->prepare(
            "SELECT id, numero_expediente, titulo, categoria, estado, prioridad,
                    fecha_solicitud, fecha_admision, fecha_limite, historial_estados
             FROM {$tabla_solicitudes}
             WHERE user_id = %d AND estado IN ($placeholders)
             ORDER BY
                CASE prioridad
                    WHEN 'urgente' THEN 1
                    WHEN 'alta' THEN 2
                    WHEN 'normal' THEN 3
                    ELSE 4
                END,
                fecha_solicitud DESC
             LIMIT 20",
            ...$parametros
        ));

        ?>
        <div class="flavor-panel flavor-transparencia-seguimiento">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Seguimiento de Solicitudes', 'flavor-chat-ia'); ?>
                </h2>
                <p class="flavor-panel-subtitle">
                    <?php esc_html_e('Estado actualizado de tus solicitudes en tramite', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <?php if (empty($solicitudes_pendientes)): ?>
                <div class="flavor-empty-state flavor-empty-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <h3><?php esc_html_e('Sin solicitudes pendientes', 'flavor-chat-ia'); ?></h3>
                    <p class="flavor-text-muted">
                        <?php esc_html_e('No tienes solicitudes en tramite actualmente.', 'flavor-chat-ia'); ?>
                    </p>
                    <div class="flavor-empty-actions">
                        <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/nueva-solicitud/')); ?>"
                           class="flavor-btn flavor-btn-primary">
                            <?php esc_html_e('Nueva solicitud', 'flavor-chat-ia'); ?>
                        </a>
                        <a href="#" onclick="document.querySelector('[data-tab=transparencia-mis-solicitudes]').click(); return false;"
                           class="flavor-btn flavor-btn-outline">
                            <?php esc_html_e('Ver historial', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="flavor-seguimiento-grid">
                    <?php foreach ($solicitudes_pendientes as $solicitud):
                        $estado_config = $this->estados_solicitud[$solicitud->estado] ?? $this->estados_solicitud['recibida'];
                        $historial = json_decode($solicitud->historial_estados ?: '[]', true);
                        $dias_transcurridos = floor((time() - strtotime($solicitud->fecha_solicitud)) / DAY_IN_SECONDS);
                        $dias_limite = $solicitud->fecha_limite ? floor((strtotime($solicitud->fecha_limite) - time()) / DAY_IN_SECONDS) : null;
                    ?>
                        <div class="flavor-seguimiento-card flavor-card-<?php echo esc_attr($estado_config['color']); ?>">
                            <div class="flavor-seguimiento-header">
                                <div class="flavor-seguimiento-info">
                                    <span class="flavor-expediente">
                                        <?php echo esc_html($solicitud->numero_expediente ?: '#' . $solicitud->id); ?>
                                    </span>
                                    <?php if ($solicitud->prioridad !== 'normal'): ?>
                                        <span class="flavor-prioridad flavor-prioridad-<?php echo esc_attr($solicitud->prioridad); ?>">
                                            <?php echo esc_html(ucfirst($solicitud->prioridad)); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($estado_config['color']); ?>">
                                    <span class="dashicons <?php echo esc_attr($estado_config['icon']); ?>"></span>
                                    <?php echo esc_html($estado_config['label']); ?>
                                </span>
                            </div>

                            <h4 class="flavor-seguimiento-titulo">
                                <?php echo esc_html($solicitud->titulo); ?>
                            </h4>

                            <?php if ($solicitud->categoria): ?>
                                <p class="flavor-seguimiento-categoria">
                                    <span class="dashicons dashicons-category"></span>
                                    <?php echo esc_html(ucfirst($solicitud->categoria)); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Timeline de estados -->
                            <div class="flavor-timeline-container">
                                <h5 class="flavor-timeline-titulo">
                                    <span class="dashicons dashicons-backup"></span>
                                    <?php esc_html_e('Historial', 'flavor-chat-ia'); ?>
                                </h5>
                                <?php $this->render_timeline_solicitud($solicitud, $historial); ?>
                            </div>

                            <!-- Info de tiempos -->
                            <div class="flavor-seguimiento-tiempos">
                                <div class="flavor-tiempo-item">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <span>
                                        <?php printf(
                                            esc_html__('Iniciada hace %d dias', 'flavor-chat-ia'),
                                            $dias_transcurridos
                                        ); ?>
                                    </span>
                                </div>
                                <?php if ($dias_limite !== null): ?>
                                    <div class="flavor-tiempo-item <?php echo $dias_limite <= 5 ? 'flavor-urgente' : ($dias_limite <= 10 ? 'flavor-aviso' : ''); ?>">
                                        <span class="dashicons dashicons-clock"></span>
                                        <span>
                                            <?php if ($dias_limite > 0): ?>
                                                <?php printf(
                                                    esc_html__('%d dias para respuesta', 'flavor-chat-ia'),
                                                    $dias_limite
                                                ); ?>
                                            <?php elseif ($dias_limite == 0): ?>
                                                <?php esc_html_e('Vence hoy', 'flavor-chat-ia'); ?>
                                            <?php else: ?>
                                                <?php printf(
                                                    esc_html__('Vencida hace %d dias', 'flavor-chat-ia'),
                                                    abs($dias_limite)
                                                ); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-seguimiento-actions">
                                <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/solicitud/' . $solicitud->id . '/')); ?>"
                                   class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php esc_html_e('Ver detalle', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el timeline de una solicitud
     *
     * @param object $solicitud Datos de la solicitud
     * @param array $historial Historial de estados
     */
    private function render_timeline_solicitud($solicitud, $historial) {
        // Si no hay historial guardado, crear uno basico
        if (empty($historial)) {
            $historial = [
                [
                    'estado' => 'recibida',
                    'fecha' => $solicitud->fecha_solicitud,
                    'comentario' => __('Solicitud registrada', 'flavor-chat-ia'),
                ],
            ];

            if ($solicitud->fecha_admision) {
                $historial[] = [
                    'estado' => 'admitida',
                    'fecha' => $solicitud->fecha_admision,
                    'comentario' => __('Solicitud admitida a tramite', 'flavor-chat-ia'),
                ];
            }

            if ($solicitud->estado !== 'recibida' && $solicitud->estado !== 'admitida') {
                $historial[] = [
                    'estado' => $solicitud->estado,
                    'fecha' => date('Y-m-d H:i:s'),
                    'comentario' => $this->estados_solicitud[$solicitud->estado]['descripcion'] ?? '',
                ];
            }
        }

        ?>
        <div class="flavor-timeline">
            <?php foreach ($historial as $indice => $evento):
                $estado_config = $this->estados_solicitud[$evento['estado']] ?? $this->estados_solicitud['recibida'];
                $es_ultimo = ($indice === count($historial) - 1);
            ?>
                <div class="flavor-timeline-item <?php echo $es_ultimo ? 'flavor-timeline-current' : ''; ?>">
                    <div class="flavor-timeline-marker flavor-marker-<?php echo esc_attr($estado_config['color']); ?>">
                        <span class="dashicons <?php echo esc_attr($estado_config['icon']); ?>"></span>
                    </div>
                    <div class="flavor-timeline-content">
                        <span class="flavor-timeline-estado">
                            <?php echo esc_html($estado_config['label']); ?>
                        </span>
                        <span class="flavor-timeline-fecha">
                            <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($evento['fecha']))); ?>
                        </span>
                        <?php if (!empty($evento['comentario'])): ?>
                            <p class="flavor-timeline-comentario">
                                <?php echo esc_html($evento['comentario']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Documentos Guardados
     */
    public function render_tab_documentos_guardados() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            $this->render_login_requerido();
            return;
        }

        global $wpdb;
        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';

        // Verificar que existe la tabla
        if (!$this->tabla_existe($tabla_documentos)) {
            $this->render_modulo_no_configurado();
            return;
        }

        // Obtener IDs de documentos guardados del user_meta
        $documentos_guardados_ids = get_user_meta($usuario_id, 'transparencia_documentos_guardados', true);
        $documentos_guardados_ids = is_array($documentos_guardados_ids) ? $documentos_guardados_ids : [];

        $documentos = [];
        if (!empty($documentos_guardados_ids)) {
            $ids_sanitizados = array_map('absint', $documentos_guardados_ids);
            $placeholders = implode(',', array_fill(0, count($ids_sanitizados), '%d'));

            $documentos = $wpdb->get_results($wpdb->prepare(
                "SELECT id, categoria, subcategoria, titulo, descripcion, importe, periodo,
                        fecha_documento, entidad, archivo_url, archivo_nombre, fecha_publicacion
                 FROM {$tabla_documentos}
                 WHERE id IN ($placeholders) AND estado = 'publicado'
                 ORDER BY fecha_publicacion DESC",
                ...$ids_sanitizados
            ));
        }

        // Categorias de documentos para filtros visuales
        $categorias_iconos = [
            'presupuestos' => ['icon' => 'dashicons-chart-bar', 'color' => 'blue'],
            'contratos' => ['icon' => 'dashicons-clipboard', 'color' => 'purple'],
            'subvenciones' => ['icon' => 'dashicons-money-alt', 'color' => 'green'],
            'normativa' => ['icon' => 'dashicons-book', 'color' => 'red'],
            'actas' => ['icon' => 'dashicons-media-document', 'color' => 'orange'],
            'personal' => ['icon' => 'dashicons-groups', 'color' => 'cyan'],
            'indicadores' => ['icon' => 'dashicons-chart-line', 'color' => 'teal'],
            'patrimonio' => ['icon' => 'dashicons-building', 'color' => 'brown'],
        ];

        ?>
        <div class="flavor-panel flavor-transparencia-documentos">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Documentos Guardados', 'flavor-chat-ia'); ?>
                </h2>
                <p class="flavor-panel-subtitle">
                    <?php esc_html_e('Documentos publicos que has marcado como favoritos', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <?php if (empty($documentos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-star-empty"></span>
                    <h3><?php esc_html_e('No tienes documentos guardados', 'flavor-chat-ia'); ?></h3>
                    <p class="flavor-text-muted">
                        <?php esc_html_e('Explora el portal de transparencia y guarda los documentos que te interesen para acceder a ellos rapidamente.', 'flavor-chat-ia'); ?>
                    </p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/')); ?>"
                       class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Explorar portal', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <!-- Estadisticas rapidas -->
                <div class="flavor-docs-stats">
                    <span class="flavor-stat">
                        <strong><?php echo count($documentos); ?></strong>
                        <?php esc_html_e('documentos guardados', 'flavor-chat-ia'); ?>
                    </span>
                </div>

                <!-- Grid de documentos -->
                <div class="flavor-cards-grid flavor-docs-grid">
                    <?php foreach ($documentos as $documento):
                        $categoria_config = $categorias_iconos[$documento->categoria] ?? ['icon' => 'dashicons-media-default', 'color' => 'gray'];
                    ?>
                        <div class="flavor-card flavor-doc-card" data-documento-id="<?php echo esc_attr($documento->id); ?>">
                            <div class="flavor-card-header flavor-card-header-<?php echo esc_attr($categoria_config['color']); ?>">
                                <span class="dashicons <?php echo esc_attr($categoria_config['icon']); ?>"></span>
                                <span class="flavor-categoria">
                                    <?php echo esc_html(ucfirst($documento->categoria)); ?>
                                </span>
                                <button type="button"
                                        class="flavor-btn-icon flavor-btn-quitar-doc"
                                        data-documento-id="<?php echo esc_attr($documento->id); ?>"
                                        title="<?php esc_attr_e('Quitar de guardados', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-star-filled"></span>
                                </button>
                            </div>
                            <div class="flavor-card-body">
                                <h4 class="flavor-doc-titulo">
                                    <?php echo esc_html($documento->titulo); ?>
                                </h4>
                                <?php if (!empty($documento->descripcion)): ?>
                                    <p class="flavor-doc-descripcion">
                                        <?php echo esc_html(wp_trim_words($documento->descripcion, 20)); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="flavor-doc-meta">
                                    <?php if ($documento->periodo): ?>
                                        <span class="flavor-meta-item">
                                            <span class="dashicons dashicons-calendar"></span>
                                            <?php echo esc_html($documento->periodo); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($documento->importe): ?>
                                        <span class="flavor-meta-item">
                                            <span class="dashicons dashicons-money-alt"></span>
                                            <?php echo esc_html(number_format_i18n($documento->importe, 2)); ?> &euro;
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($documento->entidad): ?>
                                        <span class="flavor-meta-item">
                                            <span class="dashicons dashicons-building"></span>
                                            <?php echo esc_html($documento->entidad); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flavor-card-footer">
                                <span class="flavor-fecha-publicacion">
                                    <?php printf(
                                        esc_html__('Publicado: %s', 'flavor-chat-ia'),
                                        date_i18n('d/m/Y', strtotime($documento->fecha_publicacion))
                                    ); ?>
                                </span>
                                <div class="flavor-card-actions">
                                    <a href="<?php echo esc_url(home_url('/mi-portal/transparencia/documento/' . $documento->id . '/')); ?>"
                                       class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                    </a>
                                    <?php if (!empty($documento->archivo_url)): ?>
                                        <a href="<?php echo esc_url($documento->archivo_url); ?>"
                                           class="flavor-btn flavor-btn-sm flavor-btn-primary"
                                           target="_blank"
                                           download>
                                            <span class="dashicons dashicons-download"></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        (function($) {
            // Quitar documento de guardados
            $('.flavor-btn-quitar-doc').on('click', function(e) {
                e.preventDefault();
                var $boton = $(this);
                var documentoId = $boton.data('documento-id');
                var $tarjeta = $boton.closest('.flavor-doc-card');

                if (!confirm('<?php echo esc_js(__('Quitar este documento de guardados?', 'flavor-chat-ia')); ?>')) {
                    return;
                }

                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'transparencia_quitar_documento',
                        documento_id: documentoId,
                        nonce: '<?php echo wp_create_nonce('transparencia_docs_nonce'); ?>'
                    },
                    success: function(respuesta) {
                        if (respuesta.success) {
                            $tarjeta.fadeOut(300, function() {
                                $(this).remove();
                                // Si no quedan documentos, recargar para mostrar estado vacio
                                if ($('.flavor-doc-card').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            alert(respuesta.data.message || '<?php echo esc_js(__('Error al quitar documento', 'flavor-chat-ia')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Error de conexion', 'flavor-chat-ia')); ?>');
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Calcula estadisticas de solicitudes del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function calcular_estadisticas_usuario($usuario_id) {
        global $wpdb;
        $tabla_solicitudes = $this->prefijo_tabla . 'solicitudes_info';

        $estadisticas = [
            'total' => 0,
            'pendientes' => 0,
            'resueltas' => 0,
            'promedio_dias' => 0,
        ];

        if (!$this->tabla_existe($tabla_solicitudes)) {
            return $estadisticas;
        }

        // Total de solicitudes
        $estadisticas['total'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE user_id = %d",
            $usuario_id
        ));

        // Solicitudes pendientes (en tramite)
        $estadisticas['pendientes'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_solicitudes}
             WHERE user_id = %d AND estado IN ('recibida', 'admitida', 'en_tramite', 'ampliacion')",
            $usuario_id
        ));

        // Solicitudes resueltas favorablemente
        $estadisticas['resueltas'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE user_id = %d AND estado = 'resuelta'",
            $usuario_id
        ));

        // Promedio de dias de tramitacion (solo resueltas)
        $promedio = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(dias_tramitacion) FROM {$tabla_solicitudes}
             WHERE user_id = %d AND dias_tramitacion IS NOT NULL AND dias_tramitacion > 0",
            $usuario_id
        ));
        $estadisticas['promedio_dias'] = $promedio ? round($promedio) : 0;

        return $estadisticas;
    }

    /**
     * Handler AJAX para guardar documento
     */
    public function ajax_guardar_documento() {
        check_ajax_referer('transparencia_docs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $documento_id = absint($_POST['documento_id'] ?? 0);
        if (!$documento_id) {
            wp_send_json_error(['message' => __('ID de documento invalido', 'flavor-chat-ia')]);
        }

        // Verificar que el documento existe
        global $wpdb;
        $tabla_documentos = $this->prefijo_tabla . 'documentos_publicos';
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_documentos} WHERE id = %d AND estado = 'publicado'",
            $documento_id
        ));

        if (!$existe) {
            wp_send_json_error(['message' => __('Documento no encontrado', 'flavor-chat-ia')]);
        }

        // Obtener documentos guardados actuales
        $documentos_guardados = get_user_meta($usuario_id, 'transparencia_documentos_guardados', true);
        $documentos_guardados = is_array($documentos_guardados) ? $documentos_guardados : [];

        // Agregar si no existe
        if (!in_array($documento_id, $documentos_guardados)) {
            $documentos_guardados[] = $documento_id;
            update_user_meta($usuario_id, 'transparencia_documentos_guardados', $documentos_guardados);
        }

        wp_send_json_success(['message' => __('Documento guardado', 'flavor-chat-ia')]);
    }

    /**
     * Handler AJAX para quitar documento de guardados
     */
    public function ajax_quitar_documento() {
        check_ajax_referer('transparencia_docs_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesion', 'flavor-chat-ia')]);
        }

        $documento_id = absint($_POST['documento_id'] ?? 0);
        if (!$documento_id) {
            wp_send_json_error(['message' => __('ID de documento invalido', 'flavor-chat-ia')]);
        }

        // Obtener documentos guardados actuales
        $documentos_guardados = get_user_meta($usuario_id, 'transparencia_documentos_guardados', true);
        $documentos_guardados = is_array($documentos_guardados) ? $documentos_guardados : [];

        // Quitar el documento
        $documentos_guardados = array_diff($documentos_guardados, [$documento_id]);
        $documentos_guardados = array_values($documentos_guardados); // Reindexar

        update_user_meta($usuario_id, 'transparencia_documentos_guardados', $documentos_guardados);

        wp_send_json_success(['message' => __('Documento quitado de guardados', 'flavor-chat-ia')]);
    }

    /**
     * Enqueue de assets
     */
    public function enqueue_assets() {
        if (!is_page() || !is_user_logged_in()) {
            return;
        }

        // CSS inline para estilos especificos del dashboard de transparencia
        $estilos_css = '
            .flavor-transparencia-panel .flavor-kpis-4 {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
            @media (max-width: 768px) {
                .flavor-transparencia-panel .flavor-kpis-4 {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            .flavor-timeline {
                position: relative;
                padding-left: 30px;
            }
            .flavor-timeline::before {
                content: "";
                position: absolute;
                left: 10px;
                top: 0;
                bottom: 0;
                width: 2px;
                background: #e5e7eb;
            }
            .flavor-timeline-item {
                position: relative;
                padding-bottom: 1rem;
                margin-bottom: 0.5rem;
            }
            .flavor-timeline-item:last-child {
                padding-bottom: 0;
                margin-bottom: 0;
            }
            .flavor-timeline-marker {
                position: absolute;
                left: -25px;
                width: 22px;
                height: 22px;
                border-radius: 50%;
                background: #fff;
                border: 2px solid #e5e7eb;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .flavor-timeline-marker .dashicons {
                font-size: 12px;
                width: 12px;
                height: 12px;
            }
            .flavor-timeline-current .flavor-timeline-marker {
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            }
            .flavor-marker-green { border-color: #10b981; }
            .flavor-marker-green .dashicons { color: #10b981; }
            .flavor-marker-blue { border-color: #3b82f6; }
            .flavor-marker-blue .dashicons { color: #3b82f6; }
            .flavor-marker-yellow { border-color: #f59e0b; }
            .flavor-marker-yellow .dashicons { color: #f59e0b; }
            .flavor-marker-red { border-color: #ef4444; }
            .flavor-marker-red .dashicons { color: #ef4444; }
            .flavor-marker-orange { border-color: #f97316; }
            .flavor-marker-orange .dashicons { color: #f97316; }
            .flavor-timeline-content {
                padding-left: 0.5rem;
            }
            .flavor-timeline-estado {
                font-weight: 600;
                font-size: 0.875rem;
            }
            .flavor-timeline-fecha {
                font-size: 0.75rem;
                color: #6b7280;
                margin-left: 0.5rem;
            }
            .flavor-timeline-comentario {
                font-size: 0.8rem;
                color: #6b7280;
                margin: 0.25rem 0 0 0;
            }
            .flavor-seguimiento-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 1.5rem;
            }
            .flavor-seguimiento-card {
                background: #fff;
                border-radius: 0.75rem;
                border: 1px solid #e5e7eb;
                padding: 1.25rem;
                transition: box-shadow 0.2s;
            }
            .flavor-seguimiento-card:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            }
            .flavor-seguimiento-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.75rem;
            }
            .flavor-expediente {
                font-family: monospace;
                font-size: 0.8rem;
                color: #6b7280;
            }
            .flavor-prioridad {
                font-size: 0.7rem;
                padding: 0.15rem 0.5rem;
                border-radius: 9999px;
                margin-left: 0.5rem;
            }
            .flavor-prioridad-urgente { background: #fef2f2; color: #dc2626; }
            .flavor-prioridad-alta { background: #fff7ed; color: #ea580c; }
            .flavor-seguimiento-titulo {
                font-size: 1rem;
                font-weight: 600;
                margin: 0 0 0.5rem 0;
                color: #111827;
            }
            .flavor-seguimiento-categoria {
                font-size: 0.85rem;
                color: #6b7280;
                margin: 0 0 1rem 0;
            }
            .flavor-seguimiento-categoria .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
                margin-right: 0.25rem;
            }
            .flavor-timeline-container {
                background: #f9fafb;
                border-radius: 0.5rem;
                padding: 1rem;
                margin-bottom: 1rem;
            }
            .flavor-timeline-titulo {
                font-size: 0.8rem;
                font-weight: 600;
                color: #374151;
                margin: 0 0 0.75rem 0;
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }
            .flavor-timeline-titulo .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
            .flavor-seguimiento-tiempos {
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
                margin-bottom: 1rem;
            }
            .flavor-tiempo-item {
                font-size: 0.8rem;
                color: #6b7280;
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }
            .flavor-tiempo-item .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
            .flavor-tiempo-item.flavor-urgente {
                color: #dc2626;
                font-weight: 600;
            }
            .flavor-tiempo-item.flavor-aviso {
                color: #ea580c;
            }
            .flavor-seguimiento-actions {
                display: flex;
                gap: 0.5rem;
            }
            .flavor-docs-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 1.25rem;
            }
            .flavor-doc-card .flavor-card-header {
                display: flex;
                align-items: center;
                padding: 0.75rem 1rem;
                border-radius: 0.5rem 0.5rem 0 0;
            }
            .flavor-card-header-blue { background: #eff6ff; }
            .flavor-card-header-purple { background: #f5f3ff; }
            .flavor-card-header-green { background: #f0fdf4; }
            .flavor-card-header-red { background: #fef2f2; }
            .flavor-card-header-orange { background: #fff7ed; }
            .flavor-card-header-cyan { background: #ecfeff; }
            .flavor-card-header-teal { background: #f0fdfa; }
            .flavor-card-header-brown { background: #fef3c7; }
            .flavor-card-header-gray { background: #f3f4f6; }
            .flavor-doc-card .flavor-card-header .dashicons {
                font-size: 20px;
                width: 20px;
                height: 20px;
                margin-right: 0.5rem;
            }
            .flavor-doc-card .flavor-categoria {
                flex: 1;
                font-size: 0.8rem;
                font-weight: 600;
            }
            .flavor-btn-icon {
                background: none;
                border: none;
                cursor: pointer;
                padding: 0.25rem;
                border-radius: 0.25rem;
            }
            .flavor-btn-icon:hover {
                background: rgba(0,0,0,0.1);
            }
            .flavor-btn-quitar-doc .dashicons {
                color: #f59e0b;
            }
            .flavor-doc-titulo {
                font-size: 0.95rem;
                font-weight: 600;
                margin: 0 0 0.5rem 0;
                line-height: 1.4;
            }
            .flavor-doc-descripcion {
                font-size: 0.85rem;
                color: #6b7280;
                margin: 0 0 0.75rem 0;
                line-height: 1.5;
            }
            .flavor-doc-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
            }
            .flavor-meta-item {
                font-size: 0.8rem;
                color: #6b7280;
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }
            .flavor-meta-item .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
            .flavor-docs-stats {
                margin-bottom: 1rem;
                padding: 0.75rem 1rem;
                background: #f3f4f6;
                border-radius: 0.5rem;
            }
            .flavor-stat {
                font-size: 0.9rem;
                color: #374151;
            }
            .flavor-empty-success .dashicons {
                color: #10b981;
            }
        ';

        wp_add_inline_style('flavor-frontend', $estilos_css);
    }

    /**
     * Renderiza mensaje de login requerido
     */
    private function render_login_requerido() {
        ?>
        <div class="flavor-panel flavor-panel-login">
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-lock"></span>
                <h3><?php esc_html_e('Acceso restringido', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Debes iniciar sesion para ver este contenido.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="flavor-btn flavor-btn-primary">
                    <?php esc_html_e('Iniciar sesion', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza mensaje de modulo no configurado
     */
    private function render_modulo_no_configurado() {
        ?>
        <div class="flavor-panel flavor-panel-error">
            <div class="flavor-empty-state">
                <span class="dashicons dashicons-warning"></span>
                <h3><?php esc_html_e('Modulo no disponible', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('El modulo de transparencia no esta configurado correctamente.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Verifica si una tabla existe en la base de datos
     *
     * @param string $nombre_tabla Nombre completo de la tabla
     * @return bool
     */
    private function tabla_existe($nombre_tabla) {
        global $wpdb;
        $resultado = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $nombre_tabla
        ));
        return $resultado === $nombre_tabla;
    }
}
