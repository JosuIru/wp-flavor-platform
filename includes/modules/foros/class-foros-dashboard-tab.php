<?php
/**
 * Dashboard Tab para Foros de Discusion
 *
 * Proporciona tabs del dashboard del usuario con:
 * - Mis Temas: Temas creados por el usuario
 * - Mis Respuestas: Respuestas del usuario
 * - Siguiendo: Temas que sigue el usuario
 * - Menciones: Menciones al usuario en respuestas
 *
 * @package FlavorPlatform
 * @subpackage Modules\Foros
 * @since 4.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario para Foros
 */
class Flavor_Foros_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Foros_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Nombres de las tablas de la base de datos
     */
    private $tabla_foros;
    private $tabla_hilos;
    private $tabla_respuestas;
    private $tabla_seguidos;
    private $tabla_menciones;
    private $tabla_notificaciones;

    /**
     * Constructor privado para singleton
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_foros = $wpdb->prefix . 'flavor_foros';
        $this->tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $this->tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';
        $this->tabla_seguidos = $wpdb->prefix . 'flavor_foros_seguidos';
        $this->tabla_menciones = $wpdb->prefix . 'flavor_foros_menciones';
        $this->tabla_notificaciones = $wpdb->prefix . 'flavor_foros_notificaciones';

        $this->init();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Foros_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa el controlador
     */
    public function init() {
        // Crear tablas adicionales si no existen
        add_action('init', [$this, 'crear_tablas_adicionales']);

        // Registrar dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tabs'], 15);

        // AJAX handlers
        add_action('wp_ajax_flavor_foros_seguir_tema', [$this, 'ajax_seguir_tema']);
        add_action('wp_ajax_flavor_foros_dejar_seguir', [$this, 'ajax_dejar_seguir']);
        add_action('wp_ajax_flavor_foros_marcar_notificacion_leida', [$this, 'ajax_marcar_notificacion_leida']);
        add_action('wp_ajax_flavor_foros_marcar_todas_leidas', [$this, 'ajax_marcar_todas_leidas']);

        // Hook para detectar menciones al crear respuesta
        add_action('flavor_foros_respuesta_creada', [$this, 'procesar_menciones'], 10, 2);

        // Hook para notificar nuevas respuestas a seguidores
        add_action('flavor_foros_respuesta_creada', [$this, 'notificar_seguidores'], 10, 2);
    }

    /**
     * Crea las tablas adicionales necesarias
     */
    public function crear_tablas_adicionales() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Verificar si ya existen
        if (Flavor_Platform_Helpers::tabla_existe($this->tabla_seguidos)) {
            return;
        }

        // Tabla de temas seguidos
        $sql_seguidos = "CREATE TABLE IF NOT EXISTS {$this->tabla_seguidos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            hilo_id bigint(20) unsigned NOT NULL,
            fecha_seguimiento datetime DEFAULT CURRENT_TIMESTAMP,
            notificar_respuestas tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_hilo (usuario_id, hilo_id),
            KEY usuario_id (usuario_id),
            KEY hilo_id (hilo_id)
        ) $charset_collate;";

        // Tabla de menciones
        $sql_menciones = "CREATE TABLE IF NOT EXISTS {$this->tabla_menciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_mencionado_id bigint(20) unsigned NOT NULL,
            usuario_autor_id bigint(20) unsigned NOT NULL,
            hilo_id bigint(20) unsigned NOT NULL,
            respuesta_id bigint(20) unsigned NOT NULL,
            leida tinyint(1) DEFAULT 0,
            fecha_mencion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_mencionado_id (usuario_mencionado_id),
            KEY hilo_id (hilo_id),
            KEY leida (leida),
            KEY fecha_mencion (fecha_mencion)
        ) $charset_collate;";

        // Tabla de notificaciones de foros
        $sql_notificaciones = "CREATE TABLE IF NOT EXISTS {$this->tabla_notificaciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo enum('nueva_respuesta','mencion','solucion','voto') NOT NULL,
            hilo_id bigint(20) unsigned NOT NULL,
            respuesta_id bigint(20) unsigned DEFAULT NULL,
            usuario_origen_id bigint(20) unsigned DEFAULT NULL,
            mensaje text DEFAULT NULL,
            leida tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY leida (leida),
            KEY fecha (fecha),
            KEY tipo (tipo)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_seguidos);
        dbDelta($sql_menciones);
        dbDelta($sql_notificaciones);
    }

    /**
     * Registra los tabs del dashboard de usuario
     *
     * @param array $tabs Tabs existentes
     * @return array Tabs modificados
     */
    public function registrar_dashboard_tabs($tabs) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return $tabs;
        }

        // Tab principal de Foros (si no existe ya)
        if (!isset($tabs['foros'])) {
            $tabs['foros'] = [
                'id'       => 'foros',
                'label'    => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'     => 'dashicons-format-chat',
                'orden'    => 35,
                'callback' => [$this, 'render_tab_resumen'],
                'badge'    => $this->contar_notificaciones_no_leidas($usuario_id),
            ];
        }

        // Sub-tab: Mis Temas
        $tabs['foros-mis-temas'] = [
            'id'       => 'foros-mis-temas',
            'label'    => __('Mis Temas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => 'dashicons-admin-comments',
            'orden'    => 36,
            'parent'   => 'foros',
            'callback' => [$this, 'render_tab_mis_temas'],
            'badge'    => $this->contar_mis_temas($usuario_id),
        ];

        // Sub-tab: Mis Respuestas
        $tabs['foros-mis-respuestas'] = [
            'id'       => 'foros-mis-respuestas',
            'label'    => __('Mis Respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => 'dashicons-format-status',
            'orden'    => 37,
            'parent'   => 'foros',
            'callback' => [$this, 'render_tab_mis_respuestas'],
            'badge'    => $this->contar_mis_respuestas($usuario_id),
        ];

        // Sub-tab: Temas Siguiendo
        $tabs['foros-siguiendo'] = [
            'id'       => 'foros-siguiendo',
            'label'    => __('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => 'dashicons-star-filled',
            'orden'    => 38,
            'parent'   => 'foros',
            'callback' => [$this, 'render_tab_siguiendo'],
            'badge'    => $this->contar_temas_seguidos($usuario_id),
        ];

        // Sub-tab: Menciones
        $total_menciones_no_leidas = $this->contar_menciones_no_leidas($usuario_id);
        $tabs['foros-menciones'] = [
            'id'       => 'foros-menciones',
            'label'    => __('Menciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => 'dashicons-megaphone',
            'orden'    => 39,
            'parent'   => 'foros',
            'callback' => [$this, 'render_tab_menciones'],
            'badge'    => $total_menciones_no_leidas > 0 ? $total_menciones_no_leidas : null,
            'badge_type' => $total_menciones_no_leidas > 0 ? 'warning' : 'default',
        ];

        return $tabs;
    }

    // =========================================================
    // RENDERS DE TABS
    // =========================================================

    /**
     * Render del tab resumen de foros
     */
    public function render_tab_resumen() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return;
        }

        global $wpdb;

        // Estadisticas del usuario
        $estadisticas_usuario = [
            'mis_temas' => $this->contar_mis_temas($usuario_id),
            'mis_respuestas' => $this->contar_mis_respuestas($usuario_id),
            'temas_seguidos' => $this->contar_temas_seguidos($usuario_id),
            'menciones_pendientes' => $this->contar_menciones_no_leidas($usuario_id),
            'votos_recibidos' => $this->contar_votos_recibidos($usuario_id),
            'soluciones_aceptadas' => $this->contar_soluciones_aceptadas($usuario_id),
        ];

        // Notificaciones recientes
        $notificaciones_recientes = $this->obtener_notificaciones_recientes($usuario_id, 5);

        // Actividad reciente en temas seguidos
        $actividad_reciente = $this->obtener_actividad_temas_seguidos($usuario_id, 5);

        ?>
        <div class="flavor-dashboard-foros-tab">
            <!-- KPIs -->
            <div class="flavor-kpi-grid flavor-grid-3">
                <div class="flavor-kpi-card flavor-kpi-blue">
                    <div class="flavor-kpi-icono">
                        <span class="dashicons dashicons-admin-comments"></span>
                    </div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($estadisticas_usuario['mis_temas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Temas creados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card flavor-kpi-green">
                    <div class="flavor-kpi-icono">
                        <span class="dashicons dashicons-format-status"></span>
                    </div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($estadisticas_usuario['mis_respuestas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card flavor-kpi-purple">
                    <div class="flavor-kpi-icono">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($estadisticas_usuario['temas_seguidos']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <!-- Segunda fila de KPIs -->
            <div class="flavor-kpi-grid flavor-grid-3" style="margin-top: 16px;">
                <div class="flavor-kpi-card flavor-kpi-orange">
                    <div class="flavor-kpi-icono">
                        <span class="dashicons dashicons-megaphone"></span>
                    </div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($estadisticas_usuario['menciones_pendientes']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Menciones sin leer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card flavor-kpi-teal">
                    <div class="flavor-kpi-icono">
                        <span class="dashicons dashicons-thumbs-up"></span>
                    </div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($estadisticas_usuario['votos_recibidos']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Votos recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-kpi-card flavor-kpi-success">
                    <div class="flavor-kpi-icono">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($estadisticas_usuario['soluciones_aceptadas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Soluciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-dashboard-grid" style="margin-top: 24px;">
                <!-- Notificaciones -->
                <div class="flavor-panel">
                    <div class="flavor-panel-header">
                        <h3>
                            <span class="dashicons dashicons-bell"></span>
                            <?php esc_html_e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <?php if (!empty($notificaciones_recientes)): ?>
                            <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-text"
                                    onclick="flavorForosMarcarTodasLeidas()">
                                <?php esc_html_e('Marcar todas leidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-panel-body">
                        <?php if (empty($notificaciones_recientes)): ?>
                            <div class="flavor-empty-state">
                                <span class="dashicons dashicons-smiley"></span>
                                <p><?php esc_html_e('No tienes notificaciones pendientes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        <?php else: ?>
                            <ul class="flavor-notificaciones-lista">
                                <?php foreach ($notificaciones_recientes as $notificacion): ?>
                                    <?php $this->render_notificacion_item($notificacion); ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actividad en temas seguidos -->
                <div class="flavor-panel">
                    <div class="flavor-panel-header">
                        <h3>
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php esc_html_e('Actividad en temas que sigues', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                    </div>
                    <div class="flavor-panel-body">
                        <?php if (empty($actividad_reciente)): ?>
                            <div class="flavor-empty-state">
                                <span class="dashicons dashicons-star-empty"></span>
                                <p><?php esc_html_e('No hay actividad reciente en los temas que sigues.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_module_url('foros')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <?php esc_html_e('Explorar foros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <ul class="flavor-actividad-lista">
                                <?php foreach ($actividad_reciente as $actividad): ?>
                                    <li class="flavor-actividad-item <?php echo !$actividad->leida ? 'no-leida' : ''; ?>">
                                        <div class="flavor-actividad-avatar">
                                            <?php echo get_avatar($actividad->autor_id, 32); ?>
                                        </div>
                                        <div class="flavor-actividad-contenido">
                                            <span class="flavor-actividad-texto">
                                                <strong><?php echo esc_html($actividad->autor_nombre); ?></strong>
                                                <?php esc_html_e('respondio a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                                <a href="<?php echo esc_url($this->get_url_tema($actividad->hilo_id)); ?>">
                                                    <?php echo esc_html(wp_trim_words($actividad->titulo, 6)); ?>
                                                </a>
                                            </span>
                                            <span class="flavor-actividad-tiempo">
                                                <?php echo esc_html(human_time_diff(strtotime($actividad->fecha))); ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php $this->render_scripts_estilos(); ?>
        <?php
    }

    /**
     * Render del tab Mis Temas
     */
    public function render_tab_mis_temas() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return;
        }

        global $wpdb;

        $pagina_actual = max(1, absint($_GET['pag'] ?? 1));
        $temas_por_pagina = 10;
        $desplazamiento = ($pagina_actual - 1) * $temas_por_pagina;

        // Obtener temas del usuario
        $temas_usuario = $wpdb->get_results($wpdb->prepare("
            SELECT h.*,
                   f.nombre as foro_nombre,
                   (SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE hilo_id = h.id AND estado = 'visible') as total_respuestas,
                   (SELECT MAX(created_at) FROM {$this->tabla_respuestas} WHERE hilo_id = h.id AND estado = 'visible') as ultima_respuesta
            FROM {$this->tabla_hilos} h
            LEFT JOIN {$this->tabla_foros} f ON h.foro_id = f.id
            WHERE h.autor_id = %d AND h.estado != 'eliminado'
            ORDER BY h.created_at DESC
            LIMIT %d OFFSET %d
        ", $usuario_id, $temas_por_pagina, $desplazamiento));

        $total_temas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_hilos} WHERE autor_id = %d AND estado != 'eliminado'",
            $usuario_id
        ));

        ?>
        <div class="flavor-dashboard-mis-temas">
            <div class="flavor-panel-header-actions">
                <h3><?php esc_html_e('Mis Temas de Discusion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('foros', 'nuevo-tema')); ?>"
                   class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e('Crear tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($temas_usuario)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-admin-comments"></span>
                    <h4><?php esc_html_e('No has creado ningun tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php esc_html_e('Crea tu primer tema de discusion para iniciar conversaciones con la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('foros', 'nuevo-tema')); ?>"
                       class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Crear mi primer tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-tabla-responsive">
                    <table class="flavor-table flavor-table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th class="text-center"><?php esc_html_e('Respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th class="text-center"><?php esc_html_e('Vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Ultima actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($temas_usuario as $tema): ?>
                                <tr>
                                    <td>
                                        <div class="flavor-tema-info">
                                            <?php if ($tema->es_fijado): ?>
                                                <span class="flavor-badge flavor-badge-sm flavor-badge-info" title="<?php esc_attr_e('Fijado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                                    <span class="dashicons dashicons-admin-post"></span>
                                                </span>
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url($this->get_url_tema($tema->id)); ?>" class="flavor-tema-titulo">
                                                <?php echo esc_html($tema->titulo); ?>
                                            </a>
                                            <span class="flavor-tema-foro"><?php echo esc_html($tema->foro_nombre); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="flavor-stat-numero <?php echo $tema->total_respuestas > 0 ? 'flavor-has-activity' : ''; ?>">
                                            <?php echo absint($tema->total_respuestas); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php echo absint($tema->vistas); ?>
                                    </td>
                                    <td>
                                        <?php $this->render_badge_estado($tema); ?>
                                    </td>
                                    <td>
                                        <?php if ($tema->ultima_respuesta): ?>
                                            <span class="flavor-tiempo-relativo">
                                                <?php echo esc_html(human_time_diff(strtotime($tema->ultima_respuesta))); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="flavor-tiempo-relativo flavor-sin-actividad">
                                                <?php esc_html_e('Sin respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php $this->render_paginacion($total_temas, $temas_por_pagina, $pagina_actual); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render del tab Mis Respuestas
     */
    public function render_tab_mis_respuestas() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return;
        }

        global $wpdb;

        $pagina_actual = max(1, absint($_GET['pag'] ?? 1));
        $respuestas_por_pagina = 15;
        $desplazamiento = ($pagina_actual - 1) * $respuestas_por_pagina;

        // Obtener respuestas del usuario
        $respuestas_usuario = $wpdb->get_results($wpdb->prepare("
            SELECT r.*,
                   h.titulo as hilo_titulo,
                   h.id as hilo_id,
                   f.nombre as foro_nombre
            FROM {$this->tabla_respuestas} r
            LEFT JOIN {$this->tabla_hilos} h ON r.hilo_id = h.id
            LEFT JOIN {$this->tabla_foros} f ON h.foro_id = f.id
            WHERE r.autor_id = %d AND r.estado = 'visible'
            ORDER BY r.created_at DESC
            LIMIT %d OFFSET %d
        ", $usuario_id, $respuestas_por_pagina, $desplazamiento));

        $total_respuestas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE autor_id = %d AND estado = 'visible'",
            $usuario_id
        ));

        ?>
        <div class="flavor-dashboard-mis-respuestas">
            <div class="flavor-panel-header-actions">
                <h3><?php esc_html_e('Mis Respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <span class="flavor-badge flavor-badge-default">
                    <?php printf(
                        esc_html(_n('%d respuesta', '%d respuestas', $total_respuestas, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                        $total_respuestas
                    ); ?>
                </span>
            </div>

            <?php if (empty($respuestas_usuario)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-format-status"></span>
                    <h4><?php esc_html_e('No has respondido a ningun tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php esc_html_e('Participa en las discusiones respondiendo a temas que te interesen.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_module_url('foros')); ?>"
                       class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar foros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-respuestas-lista">
                    <?php foreach ($respuestas_usuario as $respuesta): ?>
                        <div class="flavor-respuesta-card <?php echo $respuesta->es_solucion ? 'es-solucion' : ''; ?>">
                            <div class="flavor-respuesta-header">
                                <div class="flavor-respuesta-tema">
                                    <a href="<?php echo esc_url($this->get_url_tema($respuesta->hilo_id)); ?>">
                                        <?php echo esc_html($respuesta->hilo_titulo); ?>
                                    </a>
                                    <span class="flavor-respuesta-foro"><?php echo esc_html($respuesta->foro_nombre); ?></span>
                                </div>
                                <div class="flavor-respuesta-badges">
                                    <?php if ($respuesta->es_solucion): ?>
                                        <span class="flavor-badge flavor-badge-success">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <?php esc_html_e('Solucion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="flavor-votos-badge <?php echo $respuesta->votos > 0 ? 'positivo' : ($respuesta->votos < 0 ? 'negativo' : ''); ?>">
                                        <span class="dashicons dashicons-thumbs-up"></span>
                                        <?php echo intval($respuesta->votos); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flavor-respuesta-contenido">
                                <p><?php echo esc_html(wp_trim_words(strip_tags($respuesta->contenido), 30)); ?></p>
                            </div>
                            <div class="flavor-respuesta-footer">
                                <span class="flavor-tiempo-relativo">
                                    <?php echo esc_html(human_time_diff(strtotime($respuesta->created_at))); ?>
                                </span>
                                <a href="<?php echo esc_url($this->get_url_tema($respuesta->hilo_id) . '#respuesta-' . $respuesta->id); ?>"
                                   class="flavor-btn flavor-btn-sm flavor-btn-text">
                                    <?php esc_html_e('Ver en contexto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php $this->render_paginacion($total_respuestas, $respuestas_por_pagina, $pagina_actual); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render del tab Temas Siguiendo
     */
    public function render_tab_siguiendo() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return;
        }

        global $wpdb;

        $pagina_actual = max(1, absint($_GET['pag'] ?? 1));
        $temas_por_pagina = 10;
        $desplazamiento = ($pagina_actual - 1) * $temas_por_pagina;

        // Obtener temas seguidos
        $temas_seguidos = $wpdb->get_results($wpdb->prepare("
            SELECT h.*,
                   f.nombre as foro_nombre,
                   u.display_name as autor_nombre,
                   s.fecha_seguimiento,
                   s.notificar_respuestas,
                   (SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE hilo_id = h.id AND estado = 'visible') as total_respuestas,
                   (SELECT MAX(created_at) FROM {$this->tabla_respuestas} WHERE hilo_id = h.id AND estado = 'visible') as ultima_respuesta,
                   (SELECT COUNT(*) FROM {$this->tabla_respuestas}
                    WHERE hilo_id = h.id AND estado = 'visible' AND created_at > s.fecha_seguimiento) as respuestas_nuevas
            FROM {$this->tabla_seguidos} s
            INNER JOIN {$this->tabla_hilos} h ON s.hilo_id = h.id
            LEFT JOIN {$this->tabla_foros} f ON h.foro_id = f.id
            LEFT JOIN {$wpdb->users} u ON h.autor_id = u.ID
            WHERE s.usuario_id = %d AND h.estado != 'eliminado'
            ORDER BY COALESCE(h.ultima_actividad, h.created_at) DESC
            LIMIT %d OFFSET %d
        ", $usuario_id, $temas_por_pagina, $desplazamiento));

        $total_seguidos = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$this->tabla_seguidos} s
            INNER JOIN {$this->tabla_hilos} h ON s.hilo_id = h.id
            WHERE s.usuario_id = %d AND h.estado != 'eliminado'
        ", $usuario_id));

        ?>
        <div class="flavor-dashboard-siguiendo">
            <div class="flavor-panel-header-actions">
                <h3><?php esc_html_e('Temas que sigues', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <span class="flavor-badge flavor-badge-default">
                    <?php printf(
                        esc_html(_n('%d tema', '%d temas', $total_seguidos, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                        $total_seguidos
                    ); ?>
                </span>
            </div>

            <?php if (empty($temas_seguidos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-star-empty"></span>
                    <h4><?php esc_html_e('No sigues ningun tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php esc_html_e('Sigue temas para recibir notificaciones cuando haya nuevas respuestas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_module_url('foros')); ?>"
                       class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar foros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-temas-seguidos-lista">
                    <?php foreach ($temas_seguidos as $tema): ?>
                        <div class="flavor-tema-seguido-card <?php echo $tema->respuestas_nuevas > 0 ? 'tiene-actividad' : ''; ?>">
                            <div class="flavor-tema-seguido-header">
                                <div class="flavor-tema-seguido-info">
                                    <h4>
                                        <?php if ($tema->respuestas_nuevas > 0): ?>
                                            <span class="flavor-badge flavor-badge-sm flavor-badge-warning">
                                                <?php echo absint($tema->respuestas_nuevas); ?>
                                                <?php esc_html_e('nuevas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </span>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url($this->get_url_tema($tema->id)); ?>">
                                            <?php echo esc_html($tema->titulo); ?>
                                        </a>
                                    </h4>
                                    <div class="flavor-tema-seguido-meta">
                                        <span class="flavor-foro"><?php echo esc_html($tema->foro_nombre); ?></span>
                                        <span class="flavor-autor">
                                            <?php printf(esc_html__('por %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($tema->autor_nombre)); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flavor-tema-seguido-stats">
                                    <span class="flavor-stat">
                                        <span class="dashicons dashicons-admin-comments"></span>
                                        <?php echo absint($tema->total_respuestas); ?>
                                    </span>
                                    <span class="flavor-stat">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php echo absint($tema->vistas); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flavor-tema-seguido-footer">
                                <span class="flavor-tiempo">
                                    <?php if ($tema->ultima_respuesta): ?>
                                        <?php printf(
                                            esc_html__('Ultima actividad: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                            human_time_diff(strtotime($tema->ultima_respuesta))
                                        ); ?>
                                    <?php else: ?>
                                        <?php esc_html_e('Sin respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <?php endif; ?>
                                </span>
                                <div class="flavor-acciones">
                                    <button type="button"
                                            class="flavor-btn flavor-btn-sm flavor-btn-danger flavor-btn-text"
                                            onclick="flavorForosDejarSeguir(<?php echo absint($tema->id); ?>)"
                                            title="<?php esc_attr_e('Dejar de seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                        <?php esc_html_e('Dejar de seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php $this->render_paginacion($total_seguidos, $temas_por_pagina, $pagina_actual); ?>
            <?php endif; ?>
        </div>

        <?php $this->render_scripts_estilos(); ?>
        <?php
    }

    /**
     * Render del tab Menciones
     */
    public function render_tab_menciones() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return;
        }

        global $wpdb;

        $pagina_actual = max(1, absint($_GET['pag'] ?? 1));
        $menciones_por_pagina = 15;
        $desplazamiento = ($pagina_actual - 1) * $menciones_por_pagina;

        // Obtener menciones
        $menciones = $wpdb->get_results($wpdb->prepare("
            SELECT m.*,
                   h.titulo as hilo_titulo,
                   r.contenido as respuesta_contenido,
                   u.display_name as autor_nombre
            FROM {$this->tabla_menciones} m
            INNER JOIN {$this->tabla_hilos} h ON m.hilo_id = h.id
            INNER JOIN {$this->tabla_respuestas} r ON m.respuesta_id = r.id
            LEFT JOIN {$wpdb->users} u ON m.usuario_autor_id = u.ID
            WHERE m.usuario_mencionado_id = %d
            ORDER BY m.fecha_mencion DESC
            LIMIT %d OFFSET %d
        ", $usuario_id, $menciones_por_pagina, $desplazamiento));

        $total_menciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_menciones} WHERE usuario_mencionado_id = %d",
            $usuario_id
        ));

        // Marcar menciones visibles como leidas
        if (!empty($menciones)) {
            $ids_menciones = wp_list_pluck($menciones, 'id');
            $placeholders = implode(',', array_fill(0, count($ids_menciones), '%d'));
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_menciones} SET leida = 1 WHERE id IN ($placeholders)",
                ...$ids_menciones
            ));
        }

        ?>
        <div class="flavor-dashboard-menciones">
            <div class="flavor-panel-header-actions">
                <h3><?php esc_html_e('Menciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <span class="flavor-badge flavor-badge-default">
                    <?php printf(
                        esc_html(_n('%d mencion', '%d menciones', $total_menciones, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                        $total_menciones
                    ); ?>
                </span>
            </div>

            <div class="flavor-info-box">
                <span class="dashicons dashicons-info"></span>
                <p><?php esc_html_e('Las menciones se crean cuando alguien usa @tu_usuario en una respuesta.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <?php if (empty($menciones)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-megaphone"></span>
                    <h4><?php esc_html_e('No tienes menciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><?php esc_html_e('Cuando alguien te mencione en una respuesta usando @tu_usuario, aparecera aqui.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-menciones-lista">
                    <?php foreach ($menciones as $mencion): ?>
                        <div class="flavor-mencion-card <?php echo !$mencion->leida ? 'no-leida' : ''; ?>">
                            <div class="flavor-mencion-avatar">
                                <?php echo get_avatar($mencion->usuario_autor_id, 48); ?>
                            </div>
                            <div class="flavor-mencion-contenido">
                                <div class="flavor-mencion-header">
                                    <strong><?php echo esc_html($mencion->autor_nombre); ?></strong>
                                    <?php esc_html_e('te menciono en', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <a href="<?php echo esc_url($this->get_url_tema($mencion->hilo_id) . '#respuesta-' . $mencion->respuesta_id); ?>">
                                        <?php echo esc_html(wp_trim_words($mencion->hilo_titulo, 8)); ?>
                                    </a>
                                </div>
                                <div class="flavor-mencion-extracto">
                                    <blockquote>
                                        <?php echo esc_html(wp_trim_words(strip_tags($mencion->respuesta_contenido), 25)); ?>
                                    </blockquote>
                                </div>
                                <div class="flavor-mencion-footer">
                                    <span class="flavor-tiempo">
                                        <?php echo esc_html(human_time_diff(strtotime($mencion->fecha_mencion))); ?>
                                    </span>
                                    <a href="<?php echo esc_url($this->get_url_tema($mencion->hilo_id) . '#respuesta-' . $mencion->respuesta_id); ?>"
                                       class="flavor-btn flavor-btn-sm flavor-btn-text">
                                        <?php esc_html_e('Ver respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php $this->render_paginacion($total_menciones, $menciones_por_pagina, $pagina_actual); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    // =========================================================
    // AJAX HANDLERS
    // =========================================================

    /**
     * AJAX: Seguir un tema
     */
    public function ajax_seguir_tema() {
        check_ajax_referer('flavor_foros_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesion.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $hilo_id = absint($_POST['hilo_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$hilo_id) {
            wp_send_json_error(['message' => __('Tema no valido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        // Verificar si ya sigue
        $ya_sigue = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_seguidos} WHERE usuario_id = %d AND hilo_id = %d",
            $usuario_id, $hilo_id
        ));

        if ($ya_sigue) {
            wp_send_json_error(['message' => __('Ya sigues este tema.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Insertar seguimiento
        $resultado = $wpdb->insert($this->tabla_seguidos, [
            'usuario_id' => $usuario_id,
            'hilo_id' => $hilo_id,
            'fecha_seguimiento' => current_time('mysql'),
            'notificar_respuestas' => 1,
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Ahora sigues este tema.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al seguir el tema.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Dejar de seguir un tema
     */
    public function ajax_dejar_seguir() {
        check_ajax_referer('flavor_foros_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesion.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $hilo_id = absint($_POST['hilo_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$hilo_id) {
            wp_send_json_error(['message' => __('Tema no valido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        $resultado = $wpdb->delete($this->tabla_seguidos, [
            'usuario_id' => $usuario_id,
            'hilo_id' => $hilo_id,
        ]);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Has dejado de seguir este tema.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al dejar de seguir.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Marcar notificacion como leida
     */
    public function ajax_marcar_notificacion_leida() {
        check_ajax_referer('flavor_foros_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesion.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $notificacion_id = absint($_POST['notificacion_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$notificacion_id) {
            wp_send_json_error(['message' => __('Notificacion no valida.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        $resultado = $wpdb->update(
            $this->tabla_notificaciones,
            ['leida' => 1],
            ['id' => $notificacion_id, 'usuario_id' => $usuario_id]
        );

        wp_send_json_success(['message' => __('Notificacion marcada como leida.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Marcar todas las notificaciones como leidas
     */
    public function ajax_marcar_todas_leidas() {
        check_ajax_referer('flavor_foros_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesion.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $usuario_id = get_current_user_id();

        global $wpdb;

        $wpdb->update(
            $this->tabla_notificaciones,
            ['leida' => 1],
            ['usuario_id' => $usuario_id, 'leida' => 0]
        );

        $wpdb->update(
            $this->tabla_menciones,
            ['leida' => 1],
            ['usuario_mencionado_id' => $usuario_id, 'leida' => 0]
        );

        wp_send_json_success(['message' => __('Todas las notificaciones marcadas como leidas.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    // =========================================================
    // PROCESAMIENTO DE MENCIONES Y NOTIFICACIONES
    // =========================================================

    /**
     * Procesa menciones en una respuesta
     *
     * @param int $respuesta_id ID de la respuesta
     * @param array $datos_respuesta Datos de la respuesta
     */
    public function procesar_menciones($respuesta_id, $datos_respuesta) {
        $contenido = $datos_respuesta['contenido'] ?? '';
        $hilo_id = $datos_respuesta['hilo_id'] ?? 0;
        $autor_id = $datos_respuesta['autor_id'] ?? 0;

        if (empty($contenido) || !$hilo_id || !$autor_id) {
            return;
        }

        // Buscar menciones @usuario
        preg_match_all('/@([a-zA-Z0-9_]+)/', $contenido, $coincidencias);

        if (empty($coincidencias[1])) {
            return;
        }

        global $wpdb;

        foreach (array_unique($coincidencias[1]) as $nombre_usuario) {
            $usuario_mencionado = get_user_by('login', $nombre_usuario);

            if (!$usuario_mencionado || $usuario_mencionado->ID == $autor_id) {
                continue;
            }

            // Insertar mencion
            $wpdb->insert($this->tabla_menciones, [
                'usuario_mencionado_id' => $usuario_mencionado->ID,
                'usuario_autor_id' => $autor_id,
                'hilo_id' => $hilo_id,
                'respuesta_id' => $respuesta_id,
                'leida' => 0,
                'fecha_mencion' => current_time('mysql'),
            ]);

            // Crear notificacion
            $autor = get_user_by('ID', $autor_id);
            $this->crear_notificacion(
                $usuario_mencionado->ID,
                'mencion',
                $hilo_id,
                $respuesta_id,
                $autor_id,
                sprintf(
                    __('%s te ha mencionado en una respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $autor ? $autor->display_name : __('Alguien', FLAVOR_PLATFORM_TEXT_DOMAIN)
                )
            );
        }
    }

    /**
     * Notifica a los seguidores de un tema sobre nuevas respuestas
     *
     * @param int $respuesta_id ID de la respuesta
     * @param array $datos_respuesta Datos de la respuesta
     */
    public function notificar_seguidores($respuesta_id, $datos_respuesta) {
        $hilo_id = $datos_respuesta['hilo_id'] ?? 0;
        $autor_id = $datos_respuesta['autor_id'] ?? 0;

        if (!$hilo_id || !$autor_id) {
            return;
        }

        global $wpdb;

        // Obtener seguidores del tema (excepto el autor de la respuesta)
        $seguidores = $wpdb->get_results($wpdb->prepare("
            SELECT usuario_id FROM {$this->tabla_seguidos}
            WHERE hilo_id = %d AND usuario_id != %d AND notificar_respuestas = 1
        ", $hilo_id, $autor_id));

        if (empty($seguidores)) {
            return;
        }

        $autor = get_user_by('ID', $autor_id);
        $hilo = $wpdb->get_row($wpdb->prepare(
            "SELECT titulo FROM {$this->tabla_hilos} WHERE id = %d",
            $hilo_id
        ));

        foreach ($seguidores as $seguidor) {
            $this->crear_notificacion(
                $seguidor->usuario_id,
                'nueva_respuesta',
                $hilo_id,
                $respuesta_id,
                $autor_id,
                sprintf(
                    __('%s ha respondido a "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $autor ? $autor->display_name : __('Alguien', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $hilo ? wp_trim_words($hilo->titulo, 5) : __('un tema', FLAVOR_PLATFORM_TEXT_DOMAIN)
                )
            );
        }
    }

    /**
     * Crea una notificacion
     */
    private function crear_notificacion($usuario_id, $tipo, $hilo_id, $respuesta_id, $usuario_origen_id, $mensaje) {
        global $wpdb;

        $wpdb->insert($this->tabla_notificaciones, [
            'usuario_id' => $usuario_id,
            'tipo' => $tipo,
            'hilo_id' => $hilo_id,
            'respuesta_id' => $respuesta_id,
            'usuario_origen_id' => $usuario_origen_id,
            'mensaje' => $mensaje,
            'leida' => 0,
            'fecha' => current_time('mysql'),
        ]);
    }

    // =========================================================
    // HELPERS DE CONTEO
    // =========================================================

    /**
     * Cuenta los temas del usuario
     */
    private function contar_mis_temas($usuario_id) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tabla_hilos)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_hilos} WHERE autor_id = %d AND estado != 'eliminado'",
            $usuario_id
        ));
    }

    /**
     * Cuenta las respuestas del usuario
     */
    private function contar_mis_respuestas($usuario_id) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tabla_respuestas)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE autor_id = %d AND estado = 'visible'",
            $usuario_id
        ));
    }

    /**
     * Cuenta los temas seguidos por el usuario
     */
    private function contar_temas_seguidos($usuario_id) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tabla_seguidos)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_seguidos} WHERE usuario_id = %d",
            $usuario_id
        ));
    }

    /**
     * Cuenta las menciones no leidas del usuario
     */
    private function contar_menciones_no_leidas($usuario_id) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tabla_menciones)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_menciones} WHERE usuario_mencionado_id = %d AND leida = 0",
            $usuario_id
        ));
    }

    /**
     * Cuenta las notificaciones no leidas del usuario
     */
    private function contar_notificaciones_no_leidas($usuario_id) {
        global $wpdb;

        $total_notificaciones = 0;

        if (Flavor_Platform_Helpers::tabla_existe($this->tabla_notificaciones)) {
            $total_notificaciones += (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_notificaciones} WHERE usuario_id = %d AND leida = 0",
                $usuario_id
            ));
        }

        $total_notificaciones += $this->contar_menciones_no_leidas($usuario_id);

        return $total_notificaciones;
    }

    /**
     * Cuenta los votos recibidos por el usuario
     */
    private function contar_votos_recibidos($usuario_id) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tabla_respuestas)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(votos), 0) FROM {$this->tabla_respuestas} WHERE autor_id = %d AND estado = 'visible'",
            $usuario_id
        ));
    }

    /**
     * Cuenta las soluciones aceptadas del usuario
     */
    private function contar_soluciones_aceptadas($usuario_id) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tabla_respuestas)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE autor_id = %d AND es_solucion = 1 AND estado = 'visible'",
            $usuario_id
        ));
    }

    // =========================================================
    // HELPERS DE DATOS
    // =========================================================

    /**
     * Obtiene notificaciones recientes
     */
    private function obtener_notificaciones_recientes($usuario_id, $limite = 5) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tabla_notificaciones)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare("
            SELECT n.*, h.titulo as hilo_titulo, u.display_name as autor_nombre
            FROM {$this->tabla_notificaciones} n
            LEFT JOIN {$this->tabla_hilos} h ON n.hilo_id = h.id
            LEFT JOIN {$wpdb->users} u ON n.usuario_origen_id = u.ID
            WHERE n.usuario_id = %d
            ORDER BY n.fecha DESC
            LIMIT %d
        ", $usuario_id, $limite));
    }

    /**
     * Obtiene actividad reciente en temas seguidos
     */
    private function obtener_actividad_temas_seguidos($usuario_id, $limite = 5) {
        global $wpdb;

        if (!Flavor_Platform_Helpers::tabla_existe($this->tabla_seguidos)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare("
            SELECT r.*, h.titulo, h.id as hilo_id, u.display_name as autor_nombre,
                   CASE WHEN r.created_at > s.fecha_seguimiento THEN 0 ELSE 1 END as leida
            FROM {$this->tabla_respuestas} r
            INNER JOIN {$this->tabla_seguidos} s ON r.hilo_id = s.hilo_id
            INNER JOIN {$this->tabla_hilos} h ON r.hilo_id = h.id
            LEFT JOIN {$wpdb->users} u ON r.autor_id = u.ID
            WHERE s.usuario_id = %d AND r.autor_id != %d AND r.estado = 'visible'
            ORDER BY r.created_at DESC
            LIMIT %d
        ", $usuario_id, $usuario_id, $limite));
    }

    /**
     * Obtiene la URL de un tema
     */
    private function get_url_tema($hilo_id) {
        return add_query_arg('tema_id', absint($hilo_id), Flavor_Platform_Helpers::get_action_url('foros', ''));
    }

    // =========================================================
    // HELPERS DE RENDERIZADO
    // =========================================================

    /**
     * Renderiza un item de notificacion
     */
    private function render_notificacion_item($notificacion) {
        $icono = 'dashicons-bell';
        $clase_tipo = '';

        switch ($notificacion->tipo) {
            case 'nueva_respuesta':
                $icono = 'dashicons-admin-comments';
                $clase_tipo = 'tipo-respuesta';
                break;
            case 'mencion':
                $icono = 'dashicons-megaphone';
                $clase_tipo = 'tipo-mencion';
                break;
            case 'solucion':
                $icono = 'dashicons-yes-alt';
                $clase_tipo = 'tipo-solucion';
                break;
            case 'voto':
                $icono = 'dashicons-thumbs-up';
                $clase_tipo = 'tipo-voto';
                break;
        }
        ?>
        <li class="flavor-notificacion-item <?php echo esc_attr($clase_tipo); ?> <?php echo !$notificacion->leida ? 'no-leida' : ''; ?>"
            data-id="<?php echo esc_attr($notificacion->id); ?>">
            <div class="flavor-notificacion-icono">
                <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
            </div>
            <div class="flavor-notificacion-contenido">
                <p class="flavor-notificacion-mensaje">
                    <?php echo esc_html($notificacion->mensaje); ?>
                </p>
                <span class="flavor-notificacion-tiempo">
                    <?php echo esc_html(human_time_diff(strtotime($notificacion->fecha))); ?>
                </span>
            </div>
            <a href="<?php echo esc_url($this->get_url_tema($notificacion->hilo_id) . ($notificacion->respuesta_id ? '#respuesta-' . $notificacion->respuesta_id : '')); ?>"
               class="flavor-notificacion-link"
               title="<?php esc_attr_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </li>
        <?php
    }

    /**
     * Renderiza el badge de estado de un tema
     */
    private function render_badge_estado($tema) {
        if ($tema->estado === 'cerrado') {
            echo '<span class="flavor-badge flavor-badge-secondary">';
            echo '<span class="dashicons dashicons-lock"></span> ';
            esc_html_e('Cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN);
            echo '</span>';
        } elseif (isset($tema->tiene_solucion) && $tema->tiene_solucion) {
            echo '<span class="flavor-badge flavor-badge-success">';
            echo '<span class="dashicons dashicons-yes-alt"></span> ';
            esc_html_e('Resuelto', FLAVOR_PLATFORM_TEXT_DOMAIN);
            echo '</span>';
        } else {
            echo '<span class="flavor-badge flavor-badge-info">';
            esc_html_e('Abierto', FLAVOR_PLATFORM_TEXT_DOMAIN);
            echo '</span>';
        }
    }

    /**
     * Renderiza la paginacion
     */
    private function render_paginacion($total, $por_pagina, $pagina_actual) {
        $total_paginas = ceil($total / $por_pagina);

        if ($total_paginas <= 1) {
            return;
        }

        $url_base = remove_query_arg('pag');
        ?>
        <nav class="flavor-paginacion" aria-label="<?php esc_attr_e('Paginacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <?php if ($pagina_actual > 1): ?>
                <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual - 1, $url_base)); ?>"
                   class="flavor-pag-link flavor-pag-prev">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <span class="sr-only"><?php esc_html_e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
            <?php endif; ?>

            <span class="flavor-pag-info">
                <?php printf(
                    esc_html__('Pagina %1$d de %2$d', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $pagina_actual,
                    $total_paginas
                ); ?>
            </span>

            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual + 1, $url_base)); ?>"
                   class="flavor-pag-link flavor-pag-next">
                    <span class="sr-only"><?php esc_html_e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            <?php endif; ?>
        </nav>
        <?php
    }

    /**
     * Renderiza scripts y estilos adicionales
     */
    private function render_scripts_estilos() {
        ?>
        <style>
        .flavor-dashboard-foros-tab { }

        .flavor-kpi-grid { display: grid; gap: 16px; }
        .flavor-grid-3 { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .flavor-grid-4 { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }

        .flavor-kpi-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid var(--kpi-color, #3b82f6);
        }
        .flavor-kpi-blue { --kpi-color: #3b82f6; }
        .flavor-kpi-green { --kpi-color: #10b981; }
        .flavor-kpi-purple { --kpi-color: #8b5cf6; }
        .flavor-kpi-orange { --kpi-color: #f59e0b; }
        .flavor-kpi-teal { --kpi-color: #14b8a6; }
        .flavor-kpi-success { --kpi-color: #22c55e; }

        .flavor-kpi-icono {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--kpi-color) 15%, transparent);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .flavor-kpi-icono .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            color: var(--kpi-color);
        }
        .flavor-kpi-contenido { display: flex; flex-direction: column; }
        .flavor-kpi-valor { font-size: 1.75rem; font-weight: 700; line-height: 1.2; color: #1f2937; }
        .flavor-kpi-label { font-size: 0.875rem; color: #6b7280; }

        .flavor-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .flavor-panel {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .flavor-panel-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .flavor-panel-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .flavor-panel-header h3 .dashicons { color: #6b7280; }
        .flavor-panel-body { padding: 16px 20px; }

        .flavor-empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        .flavor-empty-state .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            opacity: 0.5;
            margin-bottom: 16px;
        }
        .flavor-empty-state h4 { margin: 0 0 8px; color: #374151; }
        .flavor-empty-state p { margin: 0 0 16px; }

        .flavor-notificaciones-lista,
        .flavor-actividad-lista {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .flavor-notificacion-item,
        .flavor-actividad-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .flavor-notificacion-item:last-child,
        .flavor-actividad-item:last-child { border-bottom: none; }

        .flavor-notificacion-item.no-leida,
        .flavor-actividad-item.no-leida {
            background: #fef3c7;
            margin: -12px -20px;
            padding: 12px 20px;
        }

        .flavor-notificacion-icono {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .flavor-notificacion-contenido { flex: 1; min-width: 0; }
        .flavor-notificacion-mensaje { margin: 0 0 4px; font-size: 0.875rem; }
        .flavor-notificacion-tiempo { font-size: 0.75rem; color: #9ca3af; }
        .flavor-notificacion-link {
            color: #6b7280;
            padding: 8px;
        }
        .flavor-notificacion-link:hover { color: #3b82f6; }

        .flavor-actividad-avatar img { border-radius: 50%; }
        .flavor-actividad-contenido { flex: 1; min-width: 0; }
        .flavor-actividad-texto { font-size: 0.875rem; }
        .flavor-actividad-texto a { color: #3b82f6; text-decoration: none; }
        .flavor-actividad-tiempo { font-size: 0.75rem; color: #9ca3af; display: block; margin-top: 4px; }

        /* Tabla de temas */
        .flavor-panel-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .flavor-panel-header-actions h3 { margin: 0; }

        .flavor-tabla-responsive { overflow-x: auto; }
        .flavor-table {
            width: 100%;
            border-collapse: collapse;
        }
        .flavor-table th,
        .flavor-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .flavor-table th {
            background: #f9fafb;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
        }
        .flavor-table-hover tbody tr:hover { background: #f9fafb; }

        .flavor-tema-info { display: flex; flex-direction: column; gap: 4px; }
        .flavor-tema-titulo { color: #1f2937; text-decoration: none; font-weight: 500; }
        .flavor-tema-titulo:hover { color: #3b82f6; }
        .flavor-tema-foro { font-size: 0.75rem; color: #9ca3af; }

        .flavor-stat-numero { font-weight: 600; }
        .flavor-stat-numero.flavor-has-activity { color: #3b82f6; }

        .flavor-tiempo-relativo { font-size: 0.875rem; color: #6b7280; }
        .flavor-tiempo-relativo.flavor-sin-actividad { font-style: italic; }

        .text-center { text-align: center; }

        /* Badges */
        .flavor-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .flavor-badge .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .flavor-badge-sm { padding: 2px 8px; font-size: 0.7rem; }
        .flavor-badge-default { background: #f3f4f6; color: #6b7280; }
        .flavor-badge-info { background: #dbeafe; color: #1d4ed8; }
        .flavor-badge-success { background: #d1fae5; color: #059669; }
        .flavor-badge-warning { background: #fef3c7; color: #d97706; }
        .flavor-badge-danger { background: #fee2e2; color: #dc2626; }
        .flavor-badge-secondary { background: #e5e7eb; color: #4b5563; }

        /* Respuestas lista */
        .flavor-respuestas-lista { display: flex; flex-direction: column; gap: 16px; }
        .flavor-respuesta-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
        }
        .flavor-respuesta-card.es-solucion {
            border-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #fff 100%);
        }
        .flavor-respuesta-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .flavor-respuesta-tema a {
            font-weight: 500;
            color: #1f2937;
            text-decoration: none;
        }
        .flavor-respuesta-tema a:hover { color: #3b82f6; }
        .flavor-respuesta-foro { display: block; font-size: 0.75rem; color: #9ca3af; margin-top: 4px; }
        .flavor-respuesta-badges { display: flex; gap: 8px; }
        .flavor-votos-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            background: #f3f4f6;
            color: #6b7280;
        }
        .flavor-votos-badge.positivo { background: #d1fae5; color: #059669; }
        .flavor-votos-badge.negativo { background: #fee2e2; color: #dc2626; }
        .flavor-respuesta-contenido p { margin: 0; color: #4b5563; font-size: 0.875rem; }
        .flavor-respuesta-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f3f4f6;
        }

        /* Temas seguidos */
        .flavor-temas-seguidos-lista { display: flex; flex-direction: column; gap: 12px; }
        .flavor-tema-seguido-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
        }
        .flavor-tema-seguido-card.tiene-actividad {
            border-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fff 100%);
        }
        .flavor-tema-seguido-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }
        .flavor-tema-seguido-info h4 { margin: 0 0 8px; font-size: 1rem; }
        .flavor-tema-seguido-info h4 a { color: #1f2937; text-decoration: none; }
        .flavor-tema-seguido-info h4 a:hover { color: #3b82f6; }
        .flavor-tema-seguido-meta { display: flex; gap: 12px; font-size: 0.75rem; color: #9ca3af; }
        .flavor-tema-seguido-stats {
            display: flex;
            gap: 16px;
            flex-shrink: 0;
        }
        .flavor-tema-seguido-stats .flavor-stat {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .flavor-tema-seguido-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f3f4f6;
        }
        .flavor-tema-seguido-footer .flavor-tiempo { font-size: 0.75rem; color: #9ca3af; }

        /* Menciones */
        .flavor-info-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #eff6ff;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .flavor-info-box .dashicons { color: #3b82f6; }
        .flavor-info-box p { margin: 0; font-size: 0.875rem; color: #1e40af; }

        .flavor-menciones-lista { display: flex; flex-direction: column; gap: 12px; }
        .flavor-mencion-card {
            display: flex;
            gap: 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
        }
        .flavor-mencion-card.no-leida {
            background: #fef3c7;
            border-color: #fcd34d;
        }
        .flavor-mencion-avatar img { border-radius: 50%; }
        .flavor-mencion-contenido { flex: 1; min-width: 0; }
        .flavor-mencion-header { font-size: 0.875rem; margin-bottom: 8px; }
        .flavor-mencion-header a { color: #3b82f6; text-decoration: none; }
        .flavor-mencion-extracto blockquote {
            margin: 0;
            padding: 8px 12px;
            background: #f9fafb;
            border-left: 3px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.875rem;
            color: #4b5563;
            font-style: italic;
        }
        .flavor-mencion-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
        }
        .flavor-mencion-footer .flavor-tiempo { font-size: 0.75rem; color: #9ca3af; }

        /* Paginacion */
        .flavor-paginacion {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }
        .flavor-pag-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #f3f4f6;
            color: #6b7280;
            text-decoration: none;
        }
        .flavor-pag-link:hover { background: #e5e7eb; color: #1f2937; }
        .flavor-pag-info { font-size: 0.875rem; color: #6b7280; }

        /* Botones */
        .flavor-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .flavor-btn-sm { padding: 6px 12px; font-size: 0.8125rem; }
        .flavor-btn-primary { background: #3b82f6; color: #fff; }
        .flavor-btn-primary:hover { background: #2563eb; }
        .flavor-btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
        .flavor-btn-outline:hover { background: #f3f4f6; }
        .flavor-btn-text { background: transparent; color: #6b7280; padding: 6px 8px; }
        .flavor-btn-text:hover { color: #1f2937; background: #f3f4f6; }
        .flavor-btn-danger { color: #dc2626; }
        .flavor-btn-danger:hover { background: #fee2e2; }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        </style>

        <script>
        function flavorForosDejarSeguir(hiloId) {
            if (!confirm('<?php echo esc_js(__('¿Dejar de seguir este tema?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
                return;
            }

            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'flavor_foros_dejar_seguir',
                    nonce: '<?php echo wp_create_nonce('flavor_foros_nonce'); ?>',
                    hilo_id: hiloId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data.message || '<?php echo esc_js(__('Error al procesar la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?php echo esc_js(__('Error de conexion', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
            });
        }

        function flavorForosMarcarTodasLeidas() {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'flavor_foros_marcar_todas_leidas',
                    nonce: '<?php echo wp_create_nonce('flavor_foros_nonce'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.no-leida').forEach(el => {
                        el.classList.remove('no-leida');
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        </script>
        <?php
    }
}

// Inicializar singleton
Flavor_Foros_Dashboard_Tab::get_instance();
