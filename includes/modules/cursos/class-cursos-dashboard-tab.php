<?php
/**
 * Dashboard Tab para Cursos
 *
 * @package FlavorChatIA
 * @subpackage Modules\Cursos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Cursos_Dashboard_Tab {

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
        $tabs['cursos-catalogo'] = [
            'label' => __('Cursos', 'flavor-chat-ia'),
            'icon' => 'welcome-learn-more',
            'callback' => [$this, 'render_tab_catalogo'],
            'orden' => 33,
        ];

        $tabs['cursos-mis-cursos'] = [
            'label' => __('Mi Aprendizaje', 'flavor-chat-ia'),
            'icon' => 'awards',
            'callback' => [$this, 'render_tab_mis_cursos'],
            'orden' => 34,
        ];

        return $tabs;
    }

    public function render_tab_catalogo() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos';
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';

        $total_cursos = 0;
        $cursos_activos = 0;
        $cursos_destacados = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $total_cursos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'publicado'"
            );

            $cursos_activos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'publicado' AND fecha_inicio <= %s",
                current_time('mysql')
            ));

            $cursos_destacados = $wpdb->get_results(
                "SELECT * FROM {$tabla}
                 WHERE estado = 'publicado'
                 ORDER BY inscritos_count DESC LIMIT 6"
            );
        }

        ?>
        <div class="flavor-panel flavor-cursos-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php esc_html_e('Formación y Cursos', 'flavor-chat-ia'); ?></h2>
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
                        <div class="flavor-card flavor-curso-card">
                            <?php if (!empty($curso->imagen_destacada)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($curso->imagen_destacada); ?>" alt="">
                                    <?php if ($curso->es_gratuito): ?>
                                        <span class="flavor-badge flavor-badge-success"><?php esc_html_e('Gratis', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <span class="flavor-badge flavor-badge-<?php echo esc_attr($curso->nivel ?? 'basico'); ?>">
                                    <?php echo esc_html(ucfirst($curso->nivel ?? 'básico')); ?>
                                </span>
                                <h4><?php echo esc_html($curso->titulo); ?></h4>
                                <p class="flavor-text-muted">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php echo number_format_i18n($curso->inscritos_count ?? 0); ?> <?php esc_html_e('inscritos', 'flavor-chat-ia'); ?>
                                </p>
                                <?php if (!empty($curso->duracion_horas)): ?>
                                    <p class="flavor-text-muted">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo number_format_i18n($curso->duracion_horas); ?>h
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-footer">
                                <?php if (!$curso->es_gratuito && $curso->precio > 0): ?>
                                    <span class="flavor-precio"><?php echo number_format_i18n($curso->precio, 2); ?> €</span>
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

    public function render_tab_mis_cursos() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos';
        $tabla_matriculas = $wpdb->prefix . 'flavor_cursos_matriculas';

        $matriculas = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_matriculas) && Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $matriculas = $wpdb->get_results($wpdb->prepare(
                "SELECT m.*, c.titulo, c.slug, c.imagen_destacada, c.duracion_horas
                 FROM {$tabla_matriculas} m
                 JOIN {$tabla} c ON m.curso_id = c.id
                 WHERE m.usuario_id = %d
                 ORDER BY m.fecha_matricula DESC LIMIT 10",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-awards"></span> <?php esc_html_e('Mi Aprendizaje', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (empty($matriculas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <p><?php esc_html_e('No estás matriculado en ningún curso.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/cursos/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar cursos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid">
                    <?php foreach ($matriculas as $matricula): ?>
                        <div class="flavor-card">
                            <?php if (!empty($matricula->imagen_destacada)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($matricula->imagen_destacada); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <h4><?php echo esc_html($matricula->titulo); ?></h4>
                                <div class="flavor-progress-bar">
                                    <div class="progress-fill" style="width: <?php echo esc_attr($matricula->progreso ?? 0); ?>%"></div>
                                </div>
                                <p class="flavor-text-muted">
                                    <?php echo number_format_i18n($matricula->progreso ?? 0); ?>% <?php esc_html_e('completado', 'flavor-chat-ia'); ?>
                                </p>
                            </div>
                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(home_url('/cursos/' . $matricula->slug . '/aula')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <?php esc_html_e('Continuar', 'flavor-chat-ia'); ?>
                                </a>
                                <?php if ($matricula->certificado_emitido && !empty($matricula->certificado_url)): ?>
                                    <a href="<?php echo esc_url($matricula->certificado_url); ?>" class="flavor-btn flavor-btn-sm flavor-btn-success" target="_blank">
                                        <span class="dashicons dashicons-awards"></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
