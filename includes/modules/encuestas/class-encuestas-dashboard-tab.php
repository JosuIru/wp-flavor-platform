<?php
/**
 * Dashboard Tab para Encuestas
 *
 * Compatible con el sistema de tabs de dashboard de cliente
 *
 * @package FlavorPlatform
 * @subpackage Modules\Encuestas
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario para encuestas
 */
class Flavor_Encuestas_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Encuestas_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Nombre de la tabla de encuestas
     * @var string
     */
    private $tabla_encuestas;

    /**
     * Nombre de la tabla de participantes
     * @var string
     */
    private $tabla_participantes;

    /**
     * Nombre de la tabla de respuestas
     * @var string
     */
    private $tabla_respuestas;

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_encuestas = $wpdb->prefix . 'flavor_encuestas';
        $this->tabla_participantes = $wpdb->prefix . 'flavor_encuestas_participantes';
        $this->tabla_respuestas = $wpdb->prefix . 'flavor_encuestas_respuestas';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Encuestas_Dashboard_Tab
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
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    /**
     * Registra los tabs del modulo en el dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        $tabs['encuestas-pendientes'] = [
            'label'    => __('Encuestas', 'flavor-platform'),
            'icon'     => 'forms',
            'callback' => [$this, 'render_tab_pendientes'],
            'orden'    => 50,
        ];

        $tabs['encuestas-mis-respuestas'] = [
            'label'    => __('Mis Respuestas', 'flavor-platform'),
            'icon'     => 'chart-bar',
            'callback' => [$this, 'render_tab_mis_respuestas'],
            'orden'    => 51,
        ];

        $tabs['encuestas-resultados'] = [
            'label'    => __('Resultados', 'flavor-platform'),
            'icon'     => 'chart-pie',
            'callback' => [$this, 'render_tab_resultados'],
            'orden'    => 52,
        ];

        return $tabs;
    }

    /**
     * Verifica si las tablas existen
     *
     * @return bool
     */
    private function tablas_existen() {
        return Flavor_Platform_Helpers::tabla_existe($this->tabla_encuestas);
    }

    /**
     * Renderiza el tab de encuestas pendientes
     */
    public function render_tab_pendientes() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            echo '<p>' . esc_html__('Debes iniciar sesion para ver este contenido.', 'flavor-platform') . '</p>';
            return;
        }

        global $wpdb;

        // KPIs
        $total_activas = 0;
        $pendientes_responder = 0;
        $respondidas_total = 0;
        $mis_encuestas_creadas = 0;

        $encuestas_pendientes = [];

        if ($this->tablas_existen()) {
            // Total de encuestas activas
            $total_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->tabla_encuestas} WHERE estado = 'activa'"
            );

            // Encuestas que el usuario ha respondido
            $respondidas_total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT encuesta_id) FROM {$this->tabla_participantes}
                 WHERE usuario_id = %d AND completada = 1",
                $usuario_id
            ));

            // Encuestas creadas por el usuario
            $mis_encuestas_creadas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_encuestas} WHERE autor_id = %d",
                $usuario_id
            ));

            // Encuestas activas que el usuario NO ha respondido
            $encuestas_pendientes = $wpdb->get_results($wpdb->prepare(
                "SELECT e.* FROM {$this->tabla_encuestas} e
                 WHERE e.estado = 'activa'
                 AND e.id NOT IN (
                     SELECT p.encuesta_id FROM {$this->tabla_participantes} p
                     WHERE p.usuario_id = %d AND p.completada = 1
                 )
                 AND (e.fecha_cierre IS NULL OR e.fecha_cierre > NOW())
                 ORDER BY e.fecha_creacion DESC
                 LIMIT 10",
                $usuario_id
            ));

            $pendientes_responder = count($encuestas_pendientes);
        }

        ?>
        <div class="flavor-panel flavor-encuestas-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-forms"></span> <?php esc_html_e('Encuestas Activas', 'flavor-platform'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Participa en las encuestas de la comunidad', 'flavor-platform'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-forms"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_activas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Encuestas Activas', 'flavor-platform'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-warning">
                    <span class="flavor-kpi-icon dashicons dashicons-clock"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($pendientes_responder); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Pendientes', 'flavor-platform'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($respondidas_total); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Respondidas', 'flavor-platform'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-edit"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_encuestas_creadas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Encuestas', 'flavor-platform'); ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($encuestas_pendientes)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p><?php esc_html_e('Has respondido todas las encuestas disponibles. ¡Excelente participacion!', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-encuestas-lista">
                    <h3><?php esc_html_e('Encuestas pendientes de responder', 'flavor-platform'); ?></h3>
                    <div class="flavor-cards-grid">
                        <?php foreach ($encuestas_pendientes as $encuesta): ?>
                            <div class="flavor-card flavor-encuesta-card">
                                <div class="flavor-card-header">
                                    <span class="flavor-badge flavor-badge-<?php echo esc_attr($this->obtener_tipo_badge($encuesta->tipo)); ?>">
                                        <?php echo esc_html(ucfirst($encuesta->tipo)); ?>
                                    </span>
                                    <?php if ($encuesta->fecha_cierre): ?>
                                        <span class="flavor-card-meta">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                            <?php echo esc_html(sprintf(
                                                __('Cierra: %s', 'flavor-platform'),
                                                date_i18n('d/m/Y', strtotime($encuesta->fecha_cierre))
                                            )); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="flavor-card-title"><?php echo esc_html($encuesta->titulo); ?></h4>
                                <?php if (!empty($encuesta->descripcion)): ?>
                                    <p class="flavor-card-description">
                                        <?php echo esc_html(wp_trim_words($encuesta->descripcion, 20)); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flavor-card-footer">
                                    <span class="flavor-card-stats">
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php echo esc_html(sprintf(
                                            __('%d participantes', 'flavor-platform'),
                                            $encuesta->total_participantes ?? 0
                                        )); ?>
                                    </span>
                                    <a href="<?php echo esc_url(home_url('/encuestas/' . $encuesta->id . '/')); ?>"
                                       class="flavor-btn flavor-btn-primary flavor-btn-sm">
                                        <?php esc_html_e('Responder', 'flavor-platform'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/encuestas/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Ver Todas', 'flavor-platform'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/encuestas/crear/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Crear Encuesta', 'flavor-platform'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de mis respuestas
     */
    public function render_tab_mis_respuestas() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            echo '<p>' . esc_html__('Debes iniciar sesion para ver este contenido.', 'flavor-platform') . '</p>';
            return;
        }

        global $wpdb;

        $encuestas_respondidas = [];

        if ($this->tablas_existen()) {
            $encuestas_respondidas = $wpdb->get_results($wpdb->prepare(
                "SELECT e.*, p.fecha_completada
                 FROM {$this->tabla_encuestas} e
                 INNER JOIN {$this->tabla_participantes} p ON e.id = p.encuesta_id
                 WHERE p.usuario_id = %d AND p.completada = 1
                 ORDER BY p.fecha_completada DESC
                 LIMIT 20",
                $usuario_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-mis-respuestas-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e('Mis Respuestas', 'flavor-platform'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Historial de encuestas en las que has participado', 'flavor-platform'); ?></p>
            </div>

            <?php if (empty($encuestas_respondidas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p><?php esc_html_e('Aun no has respondido ninguna encuesta.', 'flavor-platform'); ?></p>
                    <a href="<?php echo esc_url(home_url('/encuestas/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Ver encuestas disponibles', 'flavor-platform'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Encuesta', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Tipo', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Fecha Respuesta', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Participantes', 'flavor-platform'); ?></th>
                                <th><?php esc_html_e('Acciones', 'flavor-platform'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($encuestas_respondidas as $encuesta): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html(wp_trim_words($encuesta->titulo, 8)); ?></strong>
                                    </td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($this->obtener_tipo_badge($encuesta->tipo)); ?>">
                                            <?php echo esc_html(ucfirst($encuesta->tipo)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($this->obtener_estado_badge($encuesta->estado)); ?>">
                                            <?php echo esc_html(ucfirst($encuesta->estado)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($encuesta->fecha_completada))); ?>
                                    </td>
                                    <td>
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php echo esc_html($encuesta->total_participantes ?? 0); ?>
                                    </td>
                                    <td>
                                        <?php if ($this->puede_ver_resultados($encuesta)): ?>
                                            <a href="<?php echo esc_url(home_url('/encuestas/' . $encuesta->id . '/resultados/')); ?>"
                                               class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                                <span class="dashicons dashicons-chart-pie"></span>
                                                <?php esc_html_e('Resultados', 'flavor-platform'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="flavor-text-muted">
                                                <?php esc_html_e('Resultados no disponibles', 'flavor-platform'); ?>
                                            </span>
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

    /**
     * Renderiza el tab de resultados publicos
     */
    public function render_tab_resultados() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            echo '<p>' . esc_html__('Debes iniciar sesion para ver este contenido.', 'flavor-platform') . '</p>';
            return;
        }

        global $wpdb;

        $encuestas_con_resultados = [];

        if ($this->tablas_existen()) {
            // Encuestas con resultados visibles:
            // - mostrar_resultados = 'siempre'
            // - mostrar_resultados = 'al_cerrar' AND estado = 'cerrada'
            // - Usuario es autor
            $encuestas_con_resultados = $wpdb->get_results($wpdb->prepare(
                "SELECT e.* FROM {$this->tabla_encuestas} e
                 WHERE (
                     e.mostrar_resultados = 'siempre'
                     OR (e.mostrar_resultados = 'al_cerrar' AND e.estado = 'cerrada')
                     OR e.autor_id = %d
                 )
                 AND e.total_participantes > 0
                 ORDER BY e.total_participantes DESC, e.fecha_creacion DESC
                 LIMIT 20",
                $usuario_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-resultados-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e('Resultados de Encuestas', 'flavor-platform'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Consulta los resultados de las encuestas publicas', 'flavor-platform'); ?></p>
            </div>

            <?php if (empty($encuestas_con_resultados)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <p><?php esc_html_e('No hay resultados de encuestas disponibles todavia.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid flavor-cards-grid-2">
                    <?php foreach ($encuestas_con_resultados as $encuesta): ?>
                        <div class="flavor-card flavor-resultado-card">
                            <div class="flavor-card-header">
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($this->obtener_estado_badge($encuesta->estado)); ?>">
                                    <?php echo esc_html(ucfirst($encuesta->estado)); ?>
                                </span>
                                <?php if ($encuesta->autor_id == $usuario_id): ?>
                                    <span class="flavor-badge flavor-badge-info">
                                        <?php esc_html_e('Tu encuesta', 'flavor-platform'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h4 class="flavor-card-title"><?php echo esc_html($encuesta->titulo); ?></h4>

                            <div class="flavor-resultado-stats">
                                <div class="flavor-stat">
                                    <span class="flavor-stat-value"><?php echo number_format_i18n($encuesta->total_participantes ?? 0); ?></span>
                                    <span class="flavor-stat-label"><?php esc_html_e('Participantes', 'flavor-platform'); ?></span>
                                </div>
                                <div class="flavor-stat">
                                    <span class="flavor-stat-value"><?php echo number_format_i18n($encuesta->total_respuestas ?? 0); ?></span>
                                    <span class="flavor-stat-label"><?php esc_html_e('Respuestas', 'flavor-platform'); ?></span>
                                </div>
                            </div>

                            <div class="flavor-card-footer">
                                <span class="flavor-card-meta">
                                    <span class="dashicons dashicons-calendar"></span>
                                    <?php echo esc_html(date_i18n('d/m/Y', strtotime($encuesta->fecha_creacion))); ?>
                                </span>
                                <a href="<?php echo esc_url(home_url('/encuestas/' . $encuesta->id . '/resultados/')); ?>"
                                   class="flavor-btn flavor-btn-primary flavor-btn-sm">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php esc_html_e('Ver Resultados', 'flavor-platform'); ?>
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
     * Obtiene la clase badge segun el tipo de encuesta
     *
     * @param string $tipo Tipo de encuesta
     * @return string
     */
    private function obtener_tipo_badge($tipo) {
        $tipos_badge = [
            'encuesta'   => 'primary',
            'formulario' => 'info',
            'quiz'       => 'warning',
        ];
        return $tipos_badge[$tipo] ?? 'secondary';
    }

    /**
     * Obtiene la clase badge segun el estado
     *
     * @param string $estado Estado de la encuesta
     * @return string
     */
    private function obtener_estado_badge($estado) {
        $estados_badge = [
            'borrador'  => 'secondary',
            'activa'    => 'success',
            'cerrada'   => 'warning',
            'archivada' => 'dark',
        ];
        return $estados_badge[$estado] ?? 'secondary';
    }

    /**
     * Verifica si el usuario puede ver los resultados de una encuesta
     *
     * @param object $encuesta Objeto encuesta
     * @return bool
     */
    private function puede_ver_resultados($encuesta) {
        $usuario_id = get_current_user_id();

        // Autor siempre puede ver
        if ($usuario_id && $encuesta->autor_id == $usuario_id) {
            return true;
        }

        // Admin siempre puede ver
        if (current_user_can('manage_options')) {
            return true;
        }

        // Segun configuracion
        switch ($encuesta->mostrar_resultados) {
            case 'siempre':
                return true;
            case 'al_votar':
                return true; // Ya participo porque esta en la lista de respondidas
            case 'al_cerrar':
                return $encuesta->estado === 'cerrada';
            case 'nunca':
                return false;
        }

        return false;
    }
}
