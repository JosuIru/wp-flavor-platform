<?php
/**
 * Dashboard Tab para Cursos
 *
 * Compatible con el sistema de tabs de dashboard de cliente
 *
 * @package FlavorChatIA
 * @subpackage Modules\Cursos
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario para Cursos
 */
class Flavor_Cursos_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Cursos_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Tablas de base de datos
     * @var string
     */
    private $tabla_cursos;
    private $tabla_matriculas;
    private $tabla_lecciones;
    private $tabla_progreso;
    private $tabla_valoraciones;

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $this->tabla_matriculas = $wpdb->prefix . 'flavor_cursos_matriculas';
        $this->tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $this->tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';
        $this->tabla_valoraciones = $wpdb->prefix . 'flavor_cursos_valoraciones';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Cursos_Dashboard_Tab
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
        add_action('wp_enqueue_scripts', [$this, 'encolar_assets']);
    }

    /**
     * Encola CSS/JS si estamos en el dashboard
     */
    public function encolar_assets() {
        if (!is_page() || !is_user_logged_in()) {
            return;
        }

        $pagina_actual = get_queried_object();
        if (!$pagina_actual || !isset($pagina_actual->post_name)) {
            return;
        }

        $paginas_dashboard = ['mi-portal', 'dashboard', 'mi-cuenta'];
        if (!in_array($pagina_actual->post_name, $paginas_dashboard, true)) {
            return;
        }

        wp_add_inline_style('flavor-frontend', $this->get_inline_styles());
    }

    /**
     * Estilos inline para el dashboard tab
     *
     * @return string
     */
    private function get_inline_styles() {
        return '
        .cursos-progress-bar {
            width: 100%;
            height: 8px;
            background: var(--flavor-bg-secondary, #e5e7eb);
            border-radius: 4px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        .cursos-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        .cursos-progress-fill.completado {
            background: linear-gradient(90deg, #10b981, #059669);
        }
        .cursos-curso-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: var(--flavor-bg-secondary, #f8f9fa);
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }
        .cursos-curso-card:last-child {
            margin-bottom: 0;
        }
        .cursos-curso-imagen {
            width: 100px;
            height: 70px;
            border-radius: 6px;
            object-fit: cover;
            background: #e5e7eb;
            flex-shrink: 0;
        }
        .cursos-curso-info {
            flex: 1;
            min-width: 0;
        }
        .cursos-curso-titulo {
            font-weight: 600;
            color: var(--flavor-text-primary, #111827);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cursos-curso-meta {
            font-size: 0.875rem;
            color: var(--flavor-text-secondary, #6b7280);
        }
        .cursos-curso-acciones {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .cursos-inscripcion-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--flavor-border, #e5e7eb);
        }
        .cursos-inscripcion-card:last-child {
            border-bottom: none;
        }
        .cursos-inscripcion-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .cursos-inscripcion-icon.activa {
            background: #dbeafe;
        }
        .cursos-inscripcion-icon.completada {
            background: #dcfce7;
        }
        .cursos-inscripcion-icon.pausada {
            background: #fef3c7;
        }
        .cursos-inscripcion-info {
            flex: 1;
        }
        .cursos-certificado-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #fcd34d;
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }
        .cursos-certificado-card:last-child {
            margin-bottom: 0;
        }
        .cursos-certificado-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .cursos-certificado-info {
            flex: 1;
        }
        .cursos-certificado-titulo {
            font-weight: 600;
            color: #92400e;
        }
        .cursos-certificado-fecha {
            font-size: 0.875rem;
            color: #b45309;
        }
        .cursos-nivel-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .cursos-nivel-badge.basico {
            background: #dcfce7;
            color: #166534;
        }
        .cursos-nivel-badge.intermedio {
            background: #dbeafe;
            color: #1e40af;
        }
        .cursos-nivel-badge.avanzado {
            background: #fae8ff;
            color: #86198f;
        }
        .cursos-gratuito-badge {
            background: #dcfce7;
            color: #166534;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .cursos-precio {
            font-weight: 700;
            color: var(--flavor-primary, #2563eb);
        }
        ';
    }

    /**
     * Registra los tabs del modulo en el dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        $tabs['cursos-catalogo'] = [
            'label' => __('Cursos', 'flavor-chat-ia'),
            'icon' => 'welcome-learn-more',
            'callback' => [$this, 'render_tab_catalogo'],
            'orden' => 33,
        ];

        $tabs['cursos-mis-cursos'] = [
            'label' => __('Mi Aprendizaje', 'flavor-chat-ia'),
            'icon' => 'book-alt',
            'callback' => [$this, 'render_tab_mis_cursos'],
            'orden' => 34,
        ];

        $tabs['cursos-inscripciones'] = [
            'label' => __('Inscripciones', 'flavor-chat-ia'),
            'icon' => 'clipboard',
            'callback' => [$this, 'render_tab_inscripciones'],
            'orden' => 35,
        ];

        $tabs['cursos-certificados'] = [
            'label' => __('Certificados', 'flavor-chat-ia'),
            'icon' => 'awards',
            'callback' => [$this, 'render_tab_certificados'],
            'orden' => 36,
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de Catalogo de cursos
     */
    public function render_tab_catalogo() {
        global $wpdb;

        $total_cursos = 0;
        $cursos_activos = 0;
        $cursos_destacados = [];
        $categorias = [];

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_cursos)) {
            $total_cursos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->tabla_cursos} WHERE estado = 'publicado'"
            );

            $cursos_activos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_cursos}
                 WHERE estado = 'publicado'
                 AND (fecha_inicio IS NULL OR fecha_inicio <= %s)",
                current_time('mysql')
            ));

            $cursos_destacados = $wpdb->get_results(
                "SELECT c.*,
                        (SELECT COUNT(*) FROM {$this->tabla_matriculas} m WHERE m.curso_id = c.id) as total_inscritos
                 FROM {$this->tabla_cursos} c
                 WHERE c.estado = 'publicado'
                 ORDER BY c.inscritos_count DESC, c.created_at DESC
                 LIMIT 6"
            );

            // Obtener categorias unicas
            $categorias = $wpdb->get_col(
                "SELECT DISTINCT nivel FROM {$this->tabla_cursos} WHERE estado = 'publicado' AND nivel IS NOT NULL"
            );
        }

        ?>
        <div class="flavor-panel flavor-cursos-catalogo-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php esc_html_e('Formacion y Cursos', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Aprende nuevas habilidades con nuestra comunidad', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-book"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_cursos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Cursos Disponibles', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($cursos_activos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('En Marcha', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($cursos_destacados)): ?>
                <div class="flavor-cards-grid flavor-cards-grid-3">
                    <?php foreach ($cursos_destacados as $curso): ?>
                        <div class="flavor-card flavor-curso-card-vertical">
                            <?php if (!empty($curso->imagen_destacada)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($curso->imagen_destacada); ?>" alt="<?php echo esc_attr($curso->titulo); ?>">
                                    <?php if ($curso->es_gratuito): ?>
                                        <span class="cursos-gratuito-badge"><?php esc_html_e('Gratis', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <?php if (!empty($curso->nivel)): ?>
                                    <span class="cursos-nivel-badge <?php echo esc_attr($curso->nivel); ?>">
                                        <?php echo esc_html(ucfirst($curso->nivel)); ?>
                                    </span>
                                <?php endif; ?>
                                <h4><?php echo esc_html($curso->titulo); ?></h4>
                                <?php if (!empty($curso->descripcion_corta)): ?>
                                    <p class="flavor-text-muted flavor-text-truncate-2"><?php echo esc_html($curso->descripcion_corta); ?></p>
                                <?php endif; ?>
                                <p class="flavor-text-muted">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php echo number_format_i18n($curso->total_inscritos ?? $curso->inscritos_count ?? 0); ?> <?php esc_html_e('inscritos', 'flavor-chat-ia'); ?>
                                    <?php if (!empty($curso->duracion_horas)): ?>
                                        <span class="dashicons dashicons-clock" style="margin-left: 0.5rem;"></span>
                                        <?php echo number_format_i18n($curso->duracion_horas); ?>h
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="flavor-card-footer">
                                <?php if (!$curso->es_gratuito && $curso->precio > 0): ?>
                                    <span class="cursos-precio"><?php echo number_format_i18n($curso->precio, 2); ?> &euro;</span>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(home_url('/cursos/' . $curso->slug)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php esc_html_e('Ver curso', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <p><?php esc_html_e('No hay cursos disponibles en este momento.', 'flavor-chat-ia'); ?></p>
                    <p><?php esc_html_e('Vuelve pronto para ver las nuevas formaciones.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/cursos/')); ?>" class="flavor-btn flavor-btn-primary">
                    <?php esc_html_e('Ver todos los cursos', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Mis Cursos (aprendizaje en curso)
     */
    public function render_tab_mis_cursos() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesion para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $mis_cursos = [];
        $estadisticas = [
            'en_curso' => 0,
            'completados' => 0,
            'horas_totales' => 0,
        ];

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_matriculas) && Flavor_Chat_Helpers::tabla_existe($this->tabla_cursos)) {
            $mis_cursos = $wpdb->get_results($wpdb->prepare(
                "SELECT m.*, c.titulo, c.slug, c.imagen_destacada, c.duracion_horas, c.nivel, c.total_lecciones
                 FROM {$this->tabla_matriculas} m
                 JOIN {$this->tabla_cursos} c ON m.curso_id = c.id
                 WHERE m.usuario_id = %d AND m.estado IN ('activa', 'pausada')
                 ORDER BY m.updated_at DESC
                 LIMIT 10",
                $user_id
            ));

            // Estadisticas
            $estadisticas['en_curso'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_matriculas}
                 WHERE usuario_id = %d AND estado = 'activa'",
                $user_id
            ));

            $estadisticas['completados'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_matriculas}
                 WHERE usuario_id = %d AND estado = 'completada'",
                $user_id
            ));

            $estadisticas['horas_totales'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(m.tiempo_total_minutos), 0) / 60
                 FROM {$this->tabla_matriculas} m
                 WHERE m.usuario_id = %d",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-cursos-mis-cursos-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-book-alt"></span> <?php esc_html_e('Mi Aprendizaje', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Continua donde lo dejaste', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card flavor-kpi-primary">
                    <span class="flavor-kpi-icon dashicons dashicons-book"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['en_curso']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('En curso', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['completados']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Completados', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-clock"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['horas_totales']); ?>h</span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Tiempo total', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($mis_cursos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <p><?php esc_html_e('No estas matriculado en ningun curso.', 'flavor-chat-ia'); ?></p>
                    <p><?php esc_html_e('Explora nuestro catalogo y empieza a aprender.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/cursos/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar cursos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="cursos-lista">
                    <?php foreach ($mis_cursos as $curso):
                        $progreso = (float) ($curso->progreso ?? 0);
                        $clase_progreso = $progreso >= 100 ? 'completado' : '';
                    ?>
                        <div class="cursos-curso-card">
                            <?php if (!empty($curso->imagen_destacada)): ?>
                                <img src="<?php echo esc_url($curso->imagen_destacada); ?>" alt="" class="cursos-curso-imagen">
                            <?php else: ?>
                                <div class="cursos-curso-imagen" style="display: flex; align-items: center; justify-content: center;">
                                    <span class="dashicons dashicons-welcome-learn-more" style="font-size: 2rem; color: #9ca3af;"></span>
                                </div>
                            <?php endif; ?>
                            <div class="cursos-curso-info">
                                <div class="cursos-curso-titulo"><?php echo esc_html($curso->titulo); ?></div>
                                <div class="cursos-progress-bar">
                                    <div class="cursos-progress-fill <?php echo esc_attr($clase_progreso); ?>" style="width: <?php echo esc_attr($progreso); ?>%;"></div>
                                </div>
                                <div class="cursos-curso-meta">
                                    <?php echo number_format_i18n($progreso, 0); ?>% <?php esc_html_e('completado', 'flavor-chat-ia'); ?>
                                    <?php if (!empty($curso->duracion_horas)): ?>
                                        &bull; <?php echo number_format_i18n($curso->duracion_horas); ?>h
                                    <?php endif; ?>
                                    <?php if (!empty($curso->nivel)): ?>
                                        &bull;
                                        <span class="cursos-nivel-badge <?php echo esc_attr($curso->nivel); ?>"><?php echo esc_html(ucfirst($curso->nivel)); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="cursos-curso-acciones">
                                <a href="<?php echo esc_url(home_url('/cursos/' . $curso->slug . '/aula')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php esc_html_e('Continuar', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-panel-footer">
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('cursos', '')); ?>" class="flavor-btn flavor-btn-outline">
                        <?php esc_html_e('Ver todos mis cursos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Inscripciones
     */
    public function render_tab_inscripciones() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesion para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $inscripciones = [];
        $estadisticas = [
            'activas' => 0,
            'completadas' => 0,
            'canceladas' => 0,
        ];

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_matriculas) && Flavor_Chat_Helpers::tabla_existe($this->tabla_cursos)) {
            $inscripciones = $wpdb->get_results($wpdb->prepare(
                "SELECT m.*, c.titulo, c.slug, c.imagen_destacada, c.precio, c.es_gratuito
                 FROM {$this->tabla_matriculas} m
                 JOIN {$this->tabla_cursos} c ON m.curso_id = c.id
                 WHERE m.usuario_id = %d
                 ORDER BY m.fecha_matricula DESC
                 LIMIT 20",
                $user_id
            ));

            $estadisticas['activas'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_matriculas}
                 WHERE usuario_id = %d AND estado = 'activa'",
                $user_id
            ));

            $estadisticas['completadas'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_matriculas}
                 WHERE usuario_id = %d AND estado = 'completada'",
                $user_id
            ));

            $estadisticas['canceladas'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_matriculas}
                 WHERE usuario_id = %d AND estado = 'cancelada'",
                $user_id
            ));
        }

        $estados_config = [
            'activa' => ['color' => 'success', 'icono' => 'book', 'label' => __('Activa', 'flavor-chat-ia')],
            'pausada' => ['color' => 'warning', 'icono' => 'clock', 'label' => __('Pausada', 'flavor-chat-ia')],
            'completada' => ['color' => 'primary', 'icono' => 'yes-alt', 'label' => __('Completada', 'flavor-chat-ia')],
            'cancelada' => ['color' => 'secondary', 'icono' => 'no-alt', 'label' => __('Cancelada', 'flavor-chat-ia')],
        ];

        ?>
        <div class="flavor-panel flavor-cursos-inscripciones-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Mis Inscripciones', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Historial de matriculas en cursos', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-book"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['activas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Activas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-primary">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['completadas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Completadas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-no-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['canceladas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Canceladas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($inscripciones)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p><?php esc_html_e('No tienes inscripciones registradas.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/cursos/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar cursos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="cursos-inscripciones-lista">
                    <?php foreach ($inscripciones as $inscripcion):
                        $estado_config = $estados_config[$inscripcion->estado] ?? $estados_config['activa'];
                    ?>
                        <div class="cursos-inscripcion-card">
                            <div class="cursos-inscripcion-icon <?php echo esc_attr($inscripcion->estado); ?>">
                                <span class="dashicons dashicons-<?php echo esc_attr($estado_config['icono']); ?>"></span>
                            </div>
                            <div class="cursos-inscripcion-info">
                                <div class="cursos-curso-titulo"><?php echo esc_html($inscripcion->titulo); ?></div>
                                <div class="cursos-curso-meta">
                                    <?php echo esc_html(date_i18n('d M Y', strtotime($inscripcion->fecha_matricula))); ?>
                                    &bull;
                                    <span class="flavor-badge flavor-badge-<?php echo esc_attr($estado_config['color']); ?>">
                                        <?php echo esc_html($estado_config['label']); ?>
                                    </span>
                                    <?php if ($inscripcion->monto_pagado > 0): ?>
                                        &bull; <?php echo number_format_i18n($inscripcion->monto_pagado, 2); ?> &euro;
                                    <?php elseif ($inscripcion->es_gratuito): ?>
                                        &bull; <?php esc_html_e('Gratuito', 'flavor-chat-ia'); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($inscripcion->estado === 'activa'): ?>
                                <a href="<?php echo esc_url(home_url('/cursos/' . $inscripcion->slug . '/aula')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php esc_html_e('Acceder', 'flavor-chat-ia'); ?>
                                </a>
                            <?php elseif ($inscripcion->estado === 'completada' && $inscripcion->certificado_emitido): ?>
                                <a href="<?php echo esc_url($inscripcion->certificado_url ?? '#'); ?>" class="flavor-btn flavor-btn-sm flavor-btn-success" target="_blank">
                                    <span class="dashicons dashicons-awards"></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Certificados
     */
    public function render_tab_certificados() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesion para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $certificados = [];
        $total_certificados = 0;

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_matriculas) && Flavor_Chat_Helpers::tabla_existe($this->tabla_cursos)) {
            $certificados = $wpdb->get_results($wpdb->prepare(
                "SELECT m.*, c.titulo, c.slug, c.imagen_destacada, c.duracion_horas, c.nivel
                 FROM {$this->tabla_matriculas} m
                 JOIN {$this->tabla_cursos} c ON m.curso_id = c.id
                 WHERE m.usuario_id = %d
                 AND m.certificado_emitido = 1
                 ORDER BY m.certificado_fecha DESC",
                $user_id
            ));

            $total_certificados = count($certificados);
        }

        ?>
        <div class="flavor-panel flavor-cursos-certificados-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-awards"></span> <?php esc_html_e('Mis Certificados', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Tus logros y acreditaciones obtenidas', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card flavor-kpi-warning">
                    <span class="flavor-kpi-icon dashicons dashicons-awards"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_certificados); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Certificados Obtenidos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($certificados)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-awards"></span>
                    <p><?php esc_html_e('Aun no has obtenido certificados.', 'flavor-chat-ia'); ?></p>
                    <p><?php esc_html_e('Completa cursos para obtener tus acreditaciones.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/cursos/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar cursos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="cursos-certificados-lista">
                    <?php foreach ($certificados as $certificado): ?>
                        <div class="cursos-certificado-card">
                            <div class="cursos-certificado-icon">
                                <span class="dashicons dashicons-awards"></span>
                            </div>
                            <div class="cursos-certificado-info">
                                <div class="cursos-certificado-titulo"><?php echo esc_html($certificado->titulo); ?></div>
                                <div class="cursos-certificado-fecha">
                                    <?php esc_html_e('Emitido el', 'flavor-chat-ia'); ?>
                                    <?php echo esc_html(date_i18n('d M Y', strtotime($certificado->certificado_fecha))); ?>
                                    <?php if (!empty($certificado->duracion_horas)): ?>
                                        &bull; <?php echo number_format_i18n($certificado->duracion_horas); ?>h
                                    <?php endif; ?>
                                    <?php if (!empty($certificado->nivel)): ?>
                                        &bull;
                                        <span class="cursos-nivel-badge <?php echo esc_attr($certificado->nivel); ?>"><?php echo esc_html(ucfirst($certificado->nivel)); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="cursos-certificado-acciones">
                                <?php if (!empty($certificado->certificado_url)): ?>
                                    <a href="<?php echo esc_url($certificado->certificado_url); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary" target="_blank">
                                        <span class="dashicons dashicons-download"></span>
                                        <?php esc_html_e('Descargar', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url(home_url('/cursos/' . $certificado->slug . '/certificado')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                        <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-panel-footer">
                    <p class="flavor-text-muted flavor-text-center">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e('Los certificados son verificables digitalmente y pueden compartirse en redes profesionales.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
