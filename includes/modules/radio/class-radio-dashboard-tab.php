<?php
/**
 * Dashboard Tab para Radio Comunitaria
 *
 * Proporciona tabs en el dashboard del usuario para:
 * - Programas favoritos
 * - Dedicatorias enviadas
 * - Propuestas de contenido
 *
 * @package FlavorPlatform
 * @subpackage Radio
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario del módulo Radio
 */
class Flavor_Radio_Dashboard_Tab {

    /**
     * Instancia singleton
     *
     * @var Flavor_Radio_Dashboard_Tab|null
     */
    private static $instance = null;

    /**
     * Prefijo para metadatos de usuario
     *
     * @var string
     */
    private $user_meta_prefix = 'flavor_radio_';

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Radio_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Registrar tabs en el dashboard del usuario
        add_filter('flavor_user_dashboard_tabs', [$this, 'register_dashboard_tabs'], 20);

        // AJAX handlers para favoritos
        add_action('wp_ajax_flavor_radio_toggle_favorito', [$this, 'ajax_toggle_favorito']);
        add_action('wp_ajax_flavor_radio_get_mis_favoritos', [$this, 'ajax_get_mis_favoritos']);

        // Enqueue assets específicos
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
    }

    /**
     * Registrar tabs del dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array Tabs modificados
     */
    public function register_dashboard_tabs($tabs) {
        // Tab: Mis Programas Favoritos
        $tabs['radio-mis-programas'] = [
            'label'    => __('Mis Programas', 'flavor-platform'),
            'icon'     => 'heart',
            'callback' => [$this, 'render_tab_mis_programas'],
            'orden'    => 41,
            'parent'   => 'radio',
            'badge'    => $this->contar_programas_favoritos(),
        ];

        // Tab: Mis Dedicatorias
        $tabs['radio-mis-dedicatorias'] = [
            'label'    => __('Mis Dedicatorias', 'flavor-platform'),
            'icon'     => 'music',
            'callback' => [$this, 'render_tab_mis_dedicatorias'],
            'orden'    => 42,
            'parent'   => 'radio',
            'badge'    => $this->contar_dedicatorias_usuario(),
        ];

        // Tab: Mis Propuestas
        $tabs['radio-mis-propuestas'] = [
            'label'    => __('Mis Propuestas', 'flavor-platform'),
            'icon'     => 'send',
            'callback' => [$this, 'render_tab_mis_propuestas'],
            'orden'    => 43,
            'parent'   => 'radio',
            'badge'    => $this->contar_propuestas_pendientes(),
        ];

        return $tabs;
    }

    /**
     * Renderizar tab de programas favoritos
     */
    public function render_tab_mis_programas() {
        if (!is_user_logged_in()) {
            $this->render_login_required();
            return;
        }

        $usuario_id = get_current_user_id();
        $programas_favoritos = $this->get_programas_favoritos($usuario_id);
        $settings = $this->get_radio_settings();

        ?>
        <div class="flavor-dashboard-tab radio-mis-programas">
            <!-- Mini Player integrado -->
            <?php $this->render_mini_player(); ?>

            <div class="tab-header">
                <h3 class="tab-title">
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Mis Programas Favoritos', 'flavor-platform'); ?>
                </h3>
                <p class="tab-description">
                    <?php esc_html_e('Programas que sigues para recibir notificaciones cuando estén en vivo.', 'flavor-platform'); ?>
                </p>
            </div>

            <?php if (empty($programas_favoritos)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-format-audio"></span>
                    <h4><?php esc_html_e('No tienes programas favoritos', 'flavor-platform'); ?></h4>
                    <p><?php esc_html_e('Explora nuestra programación y marca tus programas favoritos para seguirlos.', 'flavor-platform'); ?></p>
                    <a href="<?php echo esc_url(home_url('/radio/')); ?>" class="btn btn-primary">
                        <span class="dashicons dashicons-microphone"></span>
                        <?php esc_html_e('Explorar Programas', 'flavor-platform'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="programas-favoritos-grid">
                    <?php foreach ($programas_favoritos as $programa): ?>
                        <div class="programa-card" data-programa-id="<?php echo esc_attr($programa->id); ?>">
                            <div class="programa-imagen">
                                <?php if (!empty($programa->imagen_url)): ?>
                                    <img src="<?php echo esc_url($programa->imagen_url); ?>" alt="<?php echo esc_attr($programa->nombre); ?>">
                                <?php else: ?>
                                    <div class="programa-imagen-placeholder">
                                        <span class="dashicons dashicons-microphone"></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($this->is_programa_en_vivo($programa->id)): ?>
                                    <span class="en-vivo-badge"><?php esc_html_e('EN VIVO', 'flavor-platform'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="programa-info">
                                <h4 class="programa-nombre"><?php echo esc_html($programa->nombre); ?></h4>
                                <p class="programa-locutor">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php echo esc_html($programa->locutor_nombre); ?>
                                </p>
                                <p class="programa-horario">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($this->format_horario($programa)); ?>
                                </p>
                                <?php if (!empty($programa->categoria)): ?>
                                    <span class="programa-categoria"><?php echo esc_html($programa->categoria); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="programa-acciones">
                                <button type="button" class="btn-favorito active" data-programa-id="<?php echo esc_attr($programa->id); ?>" title="<?php esc_attr_e('Quitar de favoritos', 'flavor-platform'); ?>">
                                    <span class="dashicons dashicons-heart"></span>
                                </button>
                                <?php if ($this->is_programa_en_vivo($programa->id)): ?>
                                    <button type="button" class="btn-escuchar" data-programa-id="<?php echo esc_attr($programa->id); ?>">
                                        <span class="dashicons dashicons-controls-play"></span>
                                        <?php esc_html_e('Escuchar', 'flavor-platform'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="programas-stats">
                    <div class="stat-item">
                        <span class="stat-numero"><?php echo count($programas_favoritos); ?></span>
                        <span class="stat-label"><?php esc_html_e('Programas siguiendo', 'flavor-platform'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar tab de dedicatorias del usuario
     */
    public function render_tab_mis_dedicatorias() {
        if (!is_user_logged_in()) {
            $this->render_login_required();
            return;
        }

        $usuario_id = get_current_user_id();
        $dedicatorias = $this->get_dedicatorias_usuario($usuario_id);
        $settings = $this->get_radio_settings();
        $dedicatorias_hoy = $this->contar_dedicatorias_hoy($usuario_id);
        $limite_diario = isset($settings['max_dedicatorias_dia']) ? intval($settings['max_dedicatorias_dia']) : 3;

        ?>
        <div class="flavor-dashboard-tab radio-mis-dedicatorias">
            <!-- Mini Player integrado -->
            <?php $this->render_mini_player(); ?>

            <div class="tab-header">
                <h3 class="tab-title">
                    <span class="dashicons dashicons-format-audio"></span>
                    <?php esc_html_e('Mis Dedicatorias', 'flavor-platform'); ?>
                </h3>
                <p class="tab-description">
                    <?php esc_html_e('Historial de dedicatorias musicales que has enviado a la radio.', 'flavor-platform'); ?>
                </p>
            </div>

            <!-- Resumen de dedicatorias -->
            <div class="dedicatorias-resumen">
                <div class="resumen-item">
                    <span class="resumen-numero"><?php echo esc_html($dedicatorias_hoy); ?></span>
                    <span class="resumen-label"><?php esc_html_e('Enviadas hoy', 'flavor-platform'); ?></span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-numero"><?php echo esc_html($limite_diario - $dedicatorias_hoy); ?></span>
                    <span class="resumen-label"><?php esc_html_e('Disponibles hoy', 'flavor-platform'); ?></span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-numero"><?php echo count($dedicatorias); ?></span>
                    <span class="resumen-label"><?php esc_html_e('Total enviadas', 'flavor-platform'); ?></span>
                </div>
            </div>

            <?php if ($dedicatorias_hoy < $limite_diario): ?>
                <div class="nueva-dedicatoria-cta">
                    <a href="<?php echo esc_url(home_url('/radio/dedicatorias/')); ?>" class="btn btn-primary">
                        <span class="dashicons dashicons-heart"></span>
                        <?php esc_html_e('Enviar Nueva Dedicatoria', 'flavor-platform'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (empty($dedicatorias)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-format-audio"></span>
                    <h4><?php esc_html_e('No has enviado dedicatorias', 'flavor-platform'); ?></h4>
                    <p><?php esc_html_e('Envía una dedicatoria musical a alguien especial y la emitiremos en la radio.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="dedicatorias-lista">
                    <?php foreach ($dedicatorias as $dedicatoria): ?>
                        <div class="dedicatoria-item estado-<?php echo esc_attr($dedicatoria->estado); ?>">
                            <div class="dedicatoria-header">
                                <div class="dedicatoria-destinatarios">
                                    <span class="de"><?php esc_html_e('De:', 'flavor-platform'); ?> <strong><?php echo esc_html($dedicatoria->de_nombre); ?></strong></span>
                                    <span class="para"><?php esc_html_e('Para:', 'flavor-platform'); ?> <strong><?php echo esc_html($dedicatoria->para_nombre); ?></strong></span>
                                </div>
                                <span class="dedicatoria-estado estado-<?php echo esc_attr($dedicatoria->estado); ?>">
                                    <?php echo esc_html($this->get_estado_label($dedicatoria->estado)); ?>
                                </span>
                            </div>
                            <div class="dedicatoria-mensaje">
                                <p><?php echo esc_html($dedicatoria->mensaje); ?></p>
                            </div>
                            <?php if (!empty($dedicatoria->cancion_titulo)): ?>
                                <div class="dedicatoria-cancion">
                                    <span class="dashicons dashicons-format-audio"></span>
                                    <span class="cancion-info">
                                        <?php echo esc_html($dedicatoria->cancion_titulo); ?>
                                        <?php if (!empty($dedicatoria->cancion_artista)): ?>
                                            - <?php echo esc_html($dedicatoria->cancion_artista); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="dedicatoria-footer">
                                <span class="dedicatoria-fecha">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo esc_html(human_time_diff(strtotime($dedicatoria->fecha_solicitud), current_time('timestamp'))); ?>
                                    <?php esc_html_e('atrás', 'flavor-platform'); ?>
                                </span>
                                <?php if ($dedicatoria->estado === 'emitida' && !empty($dedicatoria->fecha_emision)): ?>
                                    <span class="dedicatoria-emision">
                                        <span class="dashicons dashicons-controls-play"></span>
                                        <?php
                                        printf(
                                            esc_html__('Emitida el %s', 'flavor-platform'),
                                            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($dedicatoria->fecha_emision))
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($dedicatoria->estado === 'rechazada' && !empty($dedicatoria->motivo_rechazo)): ?>
                                    <span class="dedicatoria-rechazo">
                                        <span class="dashicons dashicons-info"></span>
                                        <?php echo esc_html($dedicatoria->motivo_rechazo); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar tab de propuestas de programas
     */
    public function render_tab_mis_propuestas() {
        if (!is_user_logged_in()) {
            $this->render_login_required();
            return;
        }

        $usuario_id = get_current_user_id();
        $propuestas = $this->get_propuestas_usuario($usuario_id);
        $settings = $this->get_radio_settings();
        $permite_propuestas = isset($settings['permite_locutores_comunidad']) ? $settings['permite_locutores_comunidad'] : true;
        $tiene_pendiente = $this->tiene_propuesta_pendiente($usuario_id);

        ?>
        <div class="flavor-dashboard-tab radio-mis-propuestas">
            <!-- Mini Player integrado -->
            <?php $this->render_mini_player(); ?>

            <div class="tab-header">
                <h3 class="tab-title">
                    <span class="dashicons dashicons-megaphone"></span>
                    <?php esc_html_e('Mis Propuestas de Programa', 'flavor-platform'); ?>
                </h3>
                <p class="tab-description">
                    <?php esc_html_e('Propuestas de programas que has enviado para ser locutor/a en la radio.', 'flavor-platform'); ?>
                </p>
            </div>

            <?php if ($permite_propuestas && !$tiene_pendiente): ?>
                <div class="nueva-propuesta-cta">
                    <a href="<?php echo esc_url(home_url('/radio/proponer-programa/')); ?>" class="btn btn-primary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php esc_html_e('Enviar Nueva Propuesta', 'flavor-platform'); ?>
                    </a>
                </div>
            <?php elseif ($tiene_pendiente): ?>
                <div class="aviso-pendiente">
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e('Ya tienes una propuesta pendiente de revisión. Espera la respuesta antes de enviar otra.', 'flavor-platform'); ?>
                </div>
            <?php elseif (!$permite_propuestas): ?>
                <div class="aviso-cerrado">
                    <span class="dashicons dashicons-lock"></span>
                    <?php esc_html_e('Las propuestas de nuevos programas están temporalmente cerradas.', 'flavor-platform'); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($propuestas)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-microphone"></span>
                    <h4><?php esc_html_e('No has enviado propuestas', 'flavor-platform'); ?></h4>
                    <p><?php esc_html_e('¿Tienes una idea para un programa de radio? Cuéntanos y podrías tener tu propio espacio en nuestra emisora.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="propuestas-lista">
                    <?php foreach ($propuestas as $propuesta): ?>
                        <div class="propuesta-item estado-<?php echo esc_attr($propuesta->estado); ?>">
                            <div class="propuesta-header">
                                <h4 class="propuesta-nombre"><?php echo esc_html($propuesta->nombre_programa); ?></h4>
                                <span class="propuesta-estado estado-<?php echo esc_attr($propuesta->estado); ?>">
                                    <?php echo esc_html($this->get_estado_propuesta_label($propuesta->estado)); ?>
                                </span>
                            </div>
                            <div class="propuesta-descripcion">
                                <p><?php echo esc_html(wp_trim_words($propuesta->descripcion, 30)); ?></p>
                            </div>
                            <div class="propuesta-detalles">
                                <?php if (!empty($propuesta->categoria)): ?>
                                    <span class="detalle-item">
                                        <span class="dashicons dashicons-category"></span>
                                        <?php echo esc_html($propuesta->categoria); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($propuesta->frecuencia_deseada)): ?>
                                    <span class="detalle-item">
                                        <span class="dashicons dashicons-calendar"></span>
                                        <?php echo esc_html($propuesta->frecuencia_deseada); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($propuesta->horario_preferido)): ?>
                                    <span class="detalle-item">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo esc_html($propuesta->horario_preferido); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="propuesta-footer">
                                <span class="propuesta-fecha">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php
                                    printf(
                                        esc_html__('Enviada %s', 'flavor-platform'),
                                        human_time_diff(strtotime($propuesta->fecha_solicitud), current_time('timestamp')) . ' ' . __('atrás', 'flavor-platform')
                                    );
                                    ?>
                                </span>
                                <?php if ($propuesta->estado === 'aprobada' && !empty($propuesta->programa_id)): ?>
                                    <a href="<?php echo esc_url(home_url('/radio/programa/' . $propuesta->programa_id . '/')); ?>" class="btn btn-small btn-success">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php esc_html_e('Ver Mi Programa', 'flavor-platform'); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ($propuesta->estado === 'rechazada' && !empty($propuesta->notas_admin)): ?>
                                    <div class="propuesta-notas-admin">
                                        <span class="dashicons dashicons-info"></span>
                                        <span><?php echo esc_html($propuesta->notas_admin); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Estadísticas de propuestas -->
                <div class="propuestas-stats">
                    <?php
                    $conteos = $this->get_conteo_propuestas_por_estado($usuario_id);
                    ?>
                    <div class="stat-item">
                        <span class="stat-numero"><?php echo esc_html($conteos['pendiente'] ?? 0); ?></span>
                        <span class="stat-label"><?php esc_html_e('Pendientes', 'flavor-platform'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-numero"><?php echo esc_html($conteos['aprobada'] ?? 0); ?></span>
                        <span class="stat-label"><?php esc_html_e('Aprobadas', 'flavor-platform'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-numero"><?php echo esc_html($conteos['rechazada'] ?? 0); ?></span>
                        <span class="stat-label"><?php esc_html_e('Rechazadas', 'flavor-platform'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar mini player integrado
     */
    private function render_mini_player() {
        $settings = $this->get_radio_settings();
        $programa_actual = $this->get_programa_actual();
        ?>
        <div class="radio-mini-player" id="radio-mini-player">
            <div class="mini-player-info">
                <div class="mini-player-logo">
                    <?php if (!empty($settings['logo_url'])): ?>
                        <img src="<?php echo esc_url($settings['logo_url']); ?>" alt="<?php echo esc_attr($settings['nombre_radio']); ?>">
                    <?php else: ?>
                        <span class="dashicons dashicons-microphone"></span>
                    <?php endif; ?>
                </div>
                <div class="mini-player-texto">
                    <span class="mini-player-nombre"><?php echo esc_html($settings['nombre_radio'] ?? __('Radio Comunitaria', 'flavor-platform')); ?></span>
                    <span class="mini-player-programa">
                        <?php if ($programa_actual): ?>
                            <span class="en-vivo-dot"></span>
                            <?php echo esc_html($programa_actual->titulo ?? $programa_actual->programa_nombre ?? __('En vivo', 'flavor-platform')); ?>
                        <?php else: ?>
                            <?php esc_html_e('Sin emisión actual', 'flavor-platform'); ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="mini-player-controles">
                <?php if (!empty($settings['url_stream'])): ?>
                    <button type="button" class="mini-player-btn play-btn" id="mini-player-toggle" data-stream="<?php echo esc_url($settings['url_stream']); ?>">
                        <span class="dashicons dashicons-controls-play"></span>
                    </button>
                    <input type="range" class="mini-player-volume" id="mini-player-volume" min="0" max="100" value="80">
                <?php else: ?>
                    <span class="mini-player-offline"><?php esc_html_e('Stream no disponible', 'flavor-platform'); ?></span>
                <?php endif; ?>
            </div>
            <div class="mini-player-oyentes" id="mini-player-oyentes">
                <span class="dashicons dashicons-groups"></span>
                <span class="oyentes-count">--</span>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar mensaje de login requerido
     */
    private function render_login_required() {
        ?>
        <div class="login-required">
            <span class="dashicons dashicons-lock"></span>
            <h4><?php esc_html_e('Inicia sesión para continuar', 'flavor-platform'); ?></h4>
            <p><?php esc_html_e('Necesitas iniciar sesión para acceder a esta sección.', 'flavor-platform'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="btn btn-primary">
                <?php esc_html_e('Iniciar Sesión', 'flavor-platform'); ?>
            </a>
        </div>
        <?php
    }

    // =========================================================================
    // Métodos de datos
    // =========================================================================

    /**
     * Obtener programas favoritos del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function get_programas_favoritos($usuario_id) {
        global $wpdb;

        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
        $favoritos_ids = get_user_meta($usuario_id, $this->user_meta_prefix . 'favoritos', true);

        if (empty($favoritos_ids) || !is_array($favoritos_ids)) {
            return [];
        }

        $ids_placeholders = implode(',', array_fill(0, count($favoritos_ids), '%d'));

        $programas = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name as locutor_nombre
             FROM $tabla_programas p
             LEFT JOIN {$wpdb->users} u ON p.locutor_id = u.ID
             WHERE p.id IN ($ids_placeholders) AND p.estado = 'activo'
             ORDER BY p.nombre ASC",
            ...$favoritos_ids
        ));

        return $programas ?: [];
    }

    /**
     * Obtener dedicatorias del usuario
     *
     * @param int $usuario_id ID del usuario
     * @param int $limite Límite de resultados
     * @return array
     */
    private function get_dedicatorias_usuario($usuario_id, $limite = 20) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_radio_dedicatorias';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return [];
        }

        $dedicatorias = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla
             WHERE usuario_id = %d
             ORDER BY fecha_solicitud DESC
             LIMIT %d",
            $usuario_id,
            $limite
        ));

        return $dedicatorias ?: [];
    }

    /**
     * Obtener propuestas del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function get_propuestas_usuario($usuario_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_radio_propuestas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return [];
        }

        $propuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla
             WHERE usuario_id = %d
             ORDER BY fecha_solicitud DESC",
            $usuario_id
        ));

        return $propuestas ?: [];
    }

    /**
     * Obtener programa actual en emisión
     *
     * @return object|null
     */
    private function get_programa_actual() {
        global $wpdb;

        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_emision)) {
            return null;
        }

        $ahora = current_time('mysql');

        $emision = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, p.nombre as programa_nombre, p.imagen_url as programa_imagen
             FROM $tabla_emision e
             LEFT JOIN $tabla_programas p ON e.programa_id = p.id
             WHERE e.estado = 'en_emision'
             OR (e.estado = 'programado' AND e.fecha_hora_inicio <= %s AND e.fecha_hora_fin >= %s)
             ORDER BY e.fecha_hora_inicio DESC
             LIMIT 1",
            $ahora,
            $ahora
        ));

        return $emision;
    }

    /**
     * Verificar si un programa está en vivo
     *
     * @param int $programa_id ID del programa
     * @return bool
     */
    private function is_programa_en_vivo($programa_id) {
        global $wpdb;

        $tabla_emision = $wpdb->prefix . 'flavor_radio_programacion';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_emision)) {
            return false;
        }

        $ahora = current_time('mysql');

        $en_vivo = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_emision
             WHERE programa_id = %d
             AND (estado = 'en_emision' OR (estado = 'programado' AND fecha_hora_inicio <= %s AND fecha_hora_fin >= %s))",
            $programa_id,
            $ahora,
            $ahora
        ));

        return $en_vivo > 0;
    }

    // =========================================================================
    // Contadores
    // =========================================================================

    /**
     * Contar programas favoritos del usuario actual
     *
     * @return int
     */
    private function contar_programas_favoritos() {
        if (!is_user_logged_in()) {
            return 0;
        }

        $favoritos = get_user_meta(get_current_user_id(), $this->user_meta_prefix . 'favoritos', true);
        return is_array($favoritos) ? count($favoritos) : 0;
    }

    /**
     * Contar dedicatorias del usuario actual
     *
     * @return int
     */
    private function contar_dedicatorias_usuario() {
        if (!is_user_logged_in()) {
            return 0;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_dedicatorias';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d",
            get_current_user_id()
        ));
    }

    /**
     * Contar dedicatorias enviadas hoy por el usuario
     *
     * @param int $usuario_id ID del usuario
     * @return int
     */
    private function contar_dedicatorias_hoy($usuario_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_radio_dedicatorias';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return 0;
        }

        $hoy = date('Y-m-d');

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND DATE(fecha_solicitud) = %s",
            $usuario_id,
            $hoy
        ));
    }

    /**
     * Contar propuestas pendientes del usuario actual
     *
     * @return int
     */
    private function contar_propuestas_pendientes() {
        if (!is_user_logged_in()) {
            return 0;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_propuestas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'pendiente'",
            get_current_user_id()
        ));
    }

    /**
     * Verificar si el usuario tiene una propuesta pendiente
     *
     * @param int $usuario_id ID del usuario
     * @return bool
     */
    private function tiene_propuesta_pendiente($usuario_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_radio_propuestas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return false;
        }

        $pendiente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE usuario_id = %d AND estado = 'pendiente' LIMIT 1",
            $usuario_id
        ));

        return !empty($pendiente);
    }

    /**
     * Obtener conteo de propuestas por estado
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function get_conteo_propuestas_por_estado($usuario_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_radio_propuestas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return [];
        }

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT estado, COUNT(*) as cantidad FROM $tabla WHERE usuario_id = %d GROUP BY estado",
            $usuario_id
        ), ARRAY_A);

        $conteos = [];
        foreach ($resultados as $fila) {
            $conteos[$fila['estado']] = (int) $fila['cantidad'];
        }

        return $conteos;
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    /**
     * AJAX: Toggle favorito de programa
     */
    public function ajax_toggle_favorito() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $programa_id = absint($_POST['programa_id'] ?? 0);

        if (!$programa_id) {
            wp_send_json_error(['mensaje' => __('Programa no válido', 'flavor-platform')]);
        }

        $usuario_id = get_current_user_id();
        $favoritos = get_user_meta($usuario_id, $this->user_meta_prefix . 'favoritos', true);

        if (!is_array($favoritos)) {
            $favoritos = [];
        }

        $es_favorito = false;
        $indice = array_search($programa_id, $favoritos);

        if ($indice !== false) {
            // Quitar de favoritos
            unset($favoritos[$indice]);
            $favoritos = array_values($favoritos);
            $mensaje = __('Programa eliminado de favoritos', 'flavor-platform');
        } else {
            // Agregar a favoritos
            $favoritos[] = $programa_id;
            $es_favorito = true;
            $mensaje = __('Programa agregado a favoritos', 'flavor-platform');
        }

        update_user_meta($usuario_id, $this->user_meta_prefix . 'favoritos', $favoritos);

        wp_send_json_success([
            'mensaje' => $mensaje,
            'es_favorito' => $es_favorito,
            'total_favoritos' => count($favoritos),
        ]);
    }

    /**
     * AJAX: Obtener mis favoritos
     */
    public function ajax_get_mis_favoritos() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $programas = $this->get_programas_favoritos(get_current_user_id());

        $data = [];
        foreach ($programas as $programa) {
            $data[] = [
                'id' => $programa->id,
                'nombre' => $programa->nombre,
                'imagen' => $programa->imagen_url,
                'locutor' => $programa->locutor_nombre,
                'categoria' => $programa->categoria,
                'en_vivo' => $this->is_programa_en_vivo($programa->id),
            ];
        }

        wp_send_json_success(['programas' => $data]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Obtener configuración del módulo Radio
     *
     * @return array
     */
    private function get_radio_settings() {
        $modulo = Flavor_Platform_Module_Loader::get_instance()->get_module('radio');

        if ($modulo && method_exists($modulo, 'get_settings')) {
            return $modulo->get_settings();
        }

        // Valores por defecto
        return [
            'nombre_radio' => __('Radio Comunitaria', 'flavor-platform'),
            'slogan' => __('La voz de tu barrio', 'flavor-platform'),
            'url_stream' => '',
            'logo_url' => '',
            'max_dedicatorias_dia' => 3,
            'permite_locutores_comunidad' => true,
        ];
    }

    /**
     * Formatear horario del programa
     *
     * @param object $programa Datos del programa
     * @return string
     */
    private function format_horario($programa) {
        $dias_semana = ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $dias = json_decode($programa->dias_semana, true) ?: [];

        $dias_texto = array_map(function($dia_numero) use ($dias_semana) {
            return $dias_semana[$dia_numero] ?? '';
        }, $dias);

        $hora = !empty($programa->hora_inicio) ? date('H:i', strtotime($programa->hora_inicio)) : '';

        $resultado = implode(', ', array_filter($dias_texto));
        if ($hora) {
            $resultado .= ' - ' . $hora;
        }

        return $resultado ?: __('Horario por definir', 'flavor-platform');
    }

    /**
     * Obtener etiqueta del estado de dedicatoria
     *
     * @param string $estado Estado de la dedicatoria
     * @return string
     */
    private function get_estado_label($estado) {
        $estados = [
            'pendiente' => __('Pendiente', 'flavor-platform'),
            'aprobada'  => __('Aprobada', 'flavor-platform'),
            'rechazada' => __('Rechazada', 'flavor-platform'),
            'emitida'   => __('Emitida', 'flavor-platform'),
        ];

        return $estados[$estado] ?? $estado;
    }

    /**
     * Obtener etiqueta del estado de propuesta
     *
     * @param string $estado Estado de la propuesta
     * @return string
     */
    private function get_estado_propuesta_label($estado) {
        $estados = [
            'pendiente' => __('En revisión', 'flavor-platform'),
            'aprobada'  => __('Aprobada', 'flavor-platform'),
            'rechazada' => __('No aprobada', 'flavor-platform'),
        ];

        return $estados[$estado] ?? $estado;
    }

    /**
     * Enqueue assets del dashboard
     */
    public function enqueue_dashboard_assets() {
        // Solo cargar en páginas del dashboard
        if (!is_page() && !is_singular()) {
            return;
        }

        // Verificar si estamos en una página de dashboard del usuario
        $pagina_actual = get_queried_object();
        if (!$pagina_actual || !isset($pagina_actual->post_name)) {
            return;
        }

        // Cargar solo si hay tabs de radio activos
        if (strpos($pagina_actual->post_name, 'dashboard') === false &&
            strpos($pagina_actual->post_name, 'mi-cuenta') === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-radio-dashboard-tab',
            plugins_url('assets/css/radio-dashboard-tab.css', __FILE__),
            [],
            FLAVOR_PLATFORM_VERSION ?? '1.0.0'
        );

        wp_enqueue_script(
            'flavor-radio-dashboard-tab',
            plugins_url('assets/js/radio-dashboard-tab.js', __FILE__),
            ['jquery'],
            FLAVOR_PLATFORM_VERSION ?? '1.0.0',
            true
        );

        wp_localize_script('flavor-radio-dashboard-tab', 'flavorRadioDashboard', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_radio_nonce'),
            'strings' => [
                'play' => __('Reproducir', 'flavor-platform'),
                'pause' => __('Pausar', 'flavor-platform'),
                'loading' => __('Cargando...', 'flavor-platform'),
                'error' => __('Error de conexión', 'flavor-platform'),
                'added_favorite' => __('Agregado a favoritos', 'flavor-platform'),
                'removed_favorite' => __('Eliminado de favoritos', 'flavor-platform'),
            ],
        ]);
    }
}
