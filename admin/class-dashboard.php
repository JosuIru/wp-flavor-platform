<?php
/**
 * Dashboard Principal de Flavor Platform
 *
 * Vista general con widgets, estadísticas y acciones rápidas
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para el dashboard principal
 *
 * @since 3.0.0
 */
class Flavor_Dashboard {

    /**
     * Instancia singleton
     *
     * @var Flavor_Dashboard
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Dashboard
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Menú registrado centralmente por Flavor_Admin_Menu_Manager
        // Registrar assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
    }

    /**
     * Registra assets del dashboard
     *
     * @param string $sufijo_hook Sufijo del hook
     * @return void
     */
    public function enqueue_dashboard_assets($sufijo_hook) {
        $sufijo_hook = (string) $sufijo_hook;
        if (strpos($sufijo_hook, 'flavor-dashboard') === false) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // Design tokens (variables CSS base)
        wp_enqueue_style(
            'flavor-design-tokens',
            FLAVOR_CHAT_IA_URL . "admin/css/design-tokens{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // CSS del dashboard
        wp_enqueue_style(
            'flavor-dashboard',
            FLAVOR_CHAT_IA_URL . "admin/css/dashboard{$sufijo_asset}.css",
            ['flavor-design-tokens'],
            FLAVOR_CHAT_IA_VERSION
        );

        // Chart.js para gráficos
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );
    }

    /**
     * Renderiza la página de dashboard
     *
     * @return void
     */
    public function render_dashboard_page() {
        $estadisticas = $this->get_dashboard_stats();
        $addons_activos = Flavor_Addon_Manager::get_active_addons();
        $addons_registrados = Flavor_Addon_Manager::get_registered_addons();

        // Datos del perfil activo
        $datos_perfil_activo = $this->obtener_datos_perfil_activo();
        $checks_onboarding = $this->obtener_checks_onboarding();
        $progreso_onboarding = $this->calcular_progreso_onboarding($checks_onboarding);
        $acciones_rapidas_contextuales = $this->obtener_acciones_rapidas($datos_perfil_activo['id']);
        $nivel_salud = $this->obtener_semaforo_salud();
        $actividad_reciente = $this->obtener_actividad_reciente();

        ?>
        <div class="wrap flavor-dashboard-wrapper">
            <h1><?php echo esc_html__('Dashboard de Flavor Platform', 'flavor-chat-ia'); ?></h1>

            <!-- Hero del perfil activo -->
            <div class="flavor-dashboard-hero" style="background: linear-gradient(135deg, <?php echo esc_attr($datos_perfil_activo['color']); ?> 0%, <?php echo esc_attr($datos_perfil_activo['color']); ?>cc 100%);">
                <div class="flavor-dashboard-hero-icon">
                    <span class="dashicons <?php echo esc_attr($datos_perfil_activo['icono']); ?>"></span>
                </div>
                <div class="flavor-dashboard-hero-info">
                    <h2><?php echo esc_html($datos_perfil_activo['nombre']); ?></h2>
                    <p><?php echo esc_html($datos_perfil_activo['descripcion']); ?></p>
                    <div class="flavor-dashboard-hero-meta">
                        <span><span class="dashicons dashicons-admin-plugins"></span> <?php printf(esc_html__('%d módulos activos', 'flavor-chat-ia'), $estadisticas['modulos_activos']); ?></span>
                        <span><span class="dashicons dashicons-admin-plugins"></span> <?php printf(esc_html__('%d addons', 'flavor-chat-ia'), $estadisticas['addons_activos']); ?></span>
                    </div>
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-app-composer')); ?>" class="button">
                    <?php echo esc_html__('Modificar mi app', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- Barra de progreso onboarding -->
            <div class="flavor-onboarding-bar">
                <h3><?php printf(esc_html__('Configuración: %d%%', 'flavor-chat-ia'), $progreso_onboarding); ?></h3>
                <div class="flavor-onboarding-steps">
                    <?php foreach ($checks_onboarding as $check): ?>
                        <span class="flavor-onboarding-step <?php echo $check['completado'] ? 'completado' : 'pendiente'; ?>">
                            <span class="dashicons <?php echo $check['completado'] ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>"></span>
                            <?php echo esc_html($check['etiqueta']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div class="flavor-onboarding-progress">
                    <div class="flavor-onboarding-progress-fill" style="width: <?php echo intval($progreso_onboarding); ?>%;"></div>
                </div>
            </div>

            <!-- Semáforo de salud + Acciones rápidas -->
            <div class="flavor-dashboard-grid">
                <!-- Semáforo de salud -->
                <div class="flavor-dashboard-card">
                    <h3><?php echo esc_html__('Salud del Sistema', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-health-semaphore <?php echo esc_attr($nivel_salud['nivel']); ?>">
                        <span class="dashicons <?php echo esc_attr($nivel_salud['icono']); ?>"></span>
                        <strong><?php echo esc_html($nivel_salud['mensaje']); ?></strong>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong><?php echo esc_html__('Versión:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html(FLAVOR_CHAT_IA_VERSION); ?>
                        &nbsp;|&nbsp;
                        <strong>PHP:</strong> <?php echo esc_html(PHP_VERSION); ?>
                        &nbsp;|&nbsp;
                        <strong>WP:</strong> <?php echo esc_html(get_bloginfo('version')); ?>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-health-check')); ?>" class="button" style="width: 100%;">
                        <?php echo esc_html__('Health Check Completo', 'flavor-chat-ia'); ?>
                    </a>
                </div>

                <!-- Estadísticas generales -->
                <div class="flavor-dashboard-card">
                    <h3><?php echo esc_html__('Estadísticas', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-stat-grid">
                        <div class="flavor-stat-item">
                            <div class="flavor-stat-value"><?php echo esc_html($estadisticas['addons_activos']); ?></div>
                            <div class="flavor-stat-label"><?php echo esc_html__('Addons', 'flavor-chat-ia'); ?></div>
                        </div>
                        <div class="flavor-stat-item">
                            <div class="flavor-stat-value"><?php echo esc_html($estadisticas['modulos_activos']); ?></div>
                            <div class="flavor-stat-label"><?php echo esc_html__('Módulos', 'flavor-chat-ia'); ?></div>
                        </div>
                        <div class="flavor-stat-item">
                            <div class="flavor-stat-value"><?php echo esc_html($estadisticas['conversaciones']); ?></div>
                            <div class="flavor-stat-label"><?php echo esc_html__('Conversaciones', 'flavor-chat-ia'); ?></div>
                        </div>
                        <div class="flavor-stat-item">
                            <div class="flavor-stat-value"><?php echo esc_html($estadisticas['mensajes']); ?></div>
                            <div class="flavor-stat-label"><?php echo esc_html__('Mensajes', 'flavor-chat-ia'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Addons -->
                <div class="flavor-dashboard-card">
                    <h3><?php echo esc_html__('Addons', 'flavor-chat-ia'); ?></h3>
                    <?php if (empty($addons_registrados)): ?>
                        <p style="color: var(--flavor-text-secondary); text-align: center; padding: 20px 0;">
                            <?php echo esc_html__('No hay addons instalados', 'flavor-chat-ia'); ?>
                        </p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-addons')); ?>" class="button button-primary" style="width: 100%;">
                            <?php echo esc_html__('Explorar Addons', 'flavor-chat-ia'); ?>
                        </a>
                    <?php else: ?>
                        <?php foreach (array_slice($addons_registrados, 0, 4) as $slug_addon => $datos_addon): ?>
                            <div class="flavor-addon-status <?php echo in_array($slug_addon, $addons_activos) ? 'active' : ''; ?>">
                                <span><?php echo esc_html($datos_addon['name']); ?></span>
                                <span class="flavor-addon-badge <?php echo in_array($slug_addon, $addons_activos) ? 'active' : 'inactive'; ?>">
                                    <?php echo in_array($slug_addon, $addons_activos) ? esc_html__('Activo', 'flavor-chat-ia') : esc_html__('Inactivo', 'flavor-chat-ia'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-addons')); ?>" class="button" style="width: 100%; margin-top: 10px;">
                            <?php echo esc_html__('Ver todos', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actividad reciente + Quick Actions -->
            <div class="flavor-dashboard-grid">
                <!-- Actividad reciente -->
                <div class="flavor-dashboard-card">
                    <h3><?php echo esc_html__('Actividad Reciente', 'flavor-chat-ia'); ?></h3>
                    <?php if (!empty($actividad_reciente)): ?>
                        <ul class="flavor-recent-activity">
                            <?php foreach ($actividad_reciente as $actividad_item): ?>
                                <li>
                                    <span class="dashicons dashicons-format-chat"></span>
                                    <?php echo esc_html($actividad_item['resumen']); ?>
                                    <span class="flavor-activity-time"><?php echo esc_html($actividad_item['tiempo']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color: var(--flavor-text-secondary); text-align: center; padding: 20px 0;">
                            <?php echo esc_html__('Sin actividad reciente', 'flavor-chat-ia'); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Acciones rápidas contextuales -->
                <div class="flavor-dashboard-card">
                    <h3><?php echo esc_html__('Acciones Rápidas', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-quick-actions">
                        <?php foreach ($acciones_rapidas_contextuales as $accion_rapida): ?>
                            <a href="<?php echo esc_url($accion_rapida['url']); ?>" class="flavor-quick-action">
                                <span class="dashicons <?php echo esc_attr($accion_rapida['icono']); ?>"></span><br>
                                <?php echo esc_html($accion_rapida['etiqueta']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene datos del perfil activo para el hero
     *
     * @return array
     */
    private function obtener_datos_perfil_activo() {
        $datos_perfil = [
            'id'          => 'personalizado',
            'nombre'      => __('Personalizado', 'flavor-chat-ia'),
            'descripcion' => __('Selecciona manualmente los módulos que necesitas.', 'flavor-chat-ia'),
            'icono'       => 'dashicons-admin-generic',
            'color'       => '#2271b1',
        ];

        if (class_exists('Flavor_App_Profiles')) {
            $gestor_perfiles = Flavor_App_Profiles::get_instance();
            $id_perfil_activo = $gestor_perfiles->obtener_perfil_activo();
            $perfil = $gestor_perfiles->obtener_perfil($id_perfil_activo);
            if ($perfil) {
                $datos_perfil = [
                    'id'          => $id_perfil_activo,
                    'nombre'      => $perfil['nombre'],
                    'descripcion' => $perfil['descripcion'],
                    'icono'       => $perfil['icono'],
                    'color'       => $perfil['color'],
                ];
            }
        }

        return $datos_perfil;
    }

    /**
     * Obtiene checks del onboarding
     *
     * @return array
     */
    private function obtener_checks_onboarding() {
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $id_perfil_activo = $configuracion['app_profile'] ?? 'personalizado';
        $modulos_activos = $configuracion['active_modules'] ?? [];
        $tiene_api_key = !empty($configuracion['claude_api_key']) || !empty($configuracion['openai_api_key']) || !empty($configuracion['deepseek_api_key']) || !empty($configuracion['mistral_api_key']);

        $conteo_paginas_creadas = 0;
        if (class_exists('Flavor_Page_Creator')) {
            $estado_paginas = Flavor_Page_Creator::get_pages_status();
            $conteo_paginas_creadas = count($estado_paginas['exists'] ?? []);
        }

        $conteo_addons = count(Flavor_Addon_Manager::get_active_addons());

        return [
            ['etiqueta' => __('Perfil seleccionado', 'flavor-chat-ia'), 'completado' => $id_perfil_activo !== 'personalizado'],
            ['etiqueta' => __('Módulos activos', 'flavor-chat-ia'),     'completado' => count($modulos_activos) > 0],
            ['etiqueta' => __('IA configurada', 'flavor-chat-ia'),      'completado' => $tiene_api_key],
            ['etiqueta' => __('Páginas creadas', 'flavor-chat-ia'),     'completado' => $conteo_paginas_creadas > 0],
            ['etiqueta' => __('Addons instalados', 'flavor-chat-ia'),   'completado' => $conteo_addons > 0],
        ];
    }

    /**
     * Calcula el progreso del onboarding
     *
     * @param array $checks_onboarding
     * @return int Porcentaje 0-100
     */
    private function calcular_progreso_onboarding($checks_onboarding) {
        $completados = count(array_filter($checks_onboarding, function ($check) {
            return $check['completado'];
        }));
        return (int) round(($completados / count($checks_onboarding)) * 100);
    }

    /**
     * Obtiene acciones rápidas contextuales según perfil
     *
     * @param string $id_perfil
     * @return array
     */
    private function obtener_acciones_rapidas($id_perfil) {
        $acciones_base = [
            ['etiqueta' => __('Configuración', 'flavor-chat-ia'), 'icono' => 'dashicons-admin-settings', 'url' => admin_url('admin.php?page=flavor-chat-config')],
            ['etiqueta' => __('Compositor', 'flavor-chat-ia'),    'icono' => 'dashicons-smartphone',     'url' => admin_url('admin.php?page=flavor-app-composer')],
            ['etiqueta' => __('Addons', 'flavor-chat-ia'),        'icono' => 'dashicons-admin-plugins',  'url' => admin_url('admin.php?page=flavor-addons')],
        ];

        $acciones_contextuales = [
            'tienda'        => [['etiqueta' => __('Ver pedidos', 'flavor-chat-ia'),         'icono' => 'dashicons-cart',     'url' => admin_url('edit.php?post_type=shop_order')]],
            'comunidad'     => [['etiqueta' => __('Gestionar socios', 'flavor-chat-ia'),    'icono' => 'dashicons-groups',   'url' => admin_url('admin.php?page=flavor-chat-config')]],
            'ayuntamiento'  => [['etiqueta' => __('Incidencias', 'flavor-chat-ia'),         'icono' => 'dashicons-warning',  'url' => admin_url('admin.php?page=flavor-chat-config')]],
            'barrio'        => [['etiqueta' => __('Ayuda vecinal', 'flavor-chat-ia'),       'icono' => 'dashicons-heart',    'url' => admin_url('admin.php?page=flavor-chat-config')]],
            'restaurante'   => [['etiqueta' => __('Reservas', 'flavor-chat-ia'),            'icono' => 'dashicons-calendar', 'url' => admin_url('admin.php?page=flavor-chat-config')]],
        ];

        if (isset($acciones_contextuales[$id_perfil])) {
            $acciones_base = array_merge($acciones_base, $acciones_contextuales[$id_perfil]);
        }

        return $acciones_base;
    }

    /**
     * Obtiene el nivel del semáforo de salud
     *
     * @return array
     */
    private function obtener_semaforo_salud() {
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $tiene_api_key = !empty($configuracion['claude_api_key']) || !empty($configuracion['openai_api_key']) || !empty($configuracion['deepseek_api_key']) || !empty($configuracion['mistral_api_key']);
        $chat_habilitado = !empty($configuracion['enabled']);

        if ($tiene_api_key && $chat_habilitado) {
            return ['nivel' => 'verde', 'icono' => 'dashicons-yes-alt', 'mensaje' => __('Todo funcionando correctamente', 'flavor-chat-ia')];
        } elseif ($tiene_api_key) {
            return ['nivel' => 'amarillo', 'icono' => 'dashicons-warning', 'mensaje' => __('Chat IA deshabilitado', 'flavor-chat-ia')];
        }
        return ['nivel' => 'rojo', 'icono' => 'dashicons-dismiss', 'mensaje' => __('API key no configurada', 'flavor-chat-ia')];
    }

    /**
     * Obtiene las últimas conversaciones del chat IA
     *
     * @return array
     */
    private function obtener_actividad_reciente() {
        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversations';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_conversaciones)) {
            return [];
        }

        $conversaciones_recientes = $wpdb->get_results(
            "SELECT id, session_id, message_count, started_at FROM $tabla_conversaciones ORDER BY started_at DESC LIMIT 5",
            ARRAY_A
        );

        $actividad_formateada = [];
        foreach ($conversaciones_recientes as $conversacion) {
            $actividad_formateada[] = [
                'resumen' => sprintf(
                    __('Conversación #%s (%d mensajes)', 'flavor-chat-ia'),
                    substr($conversacion['session_id'], 0, 8),
                    $conversacion['message_count']
                ),
                'tiempo' => human_time_diff(strtotime($conversacion['started_at']), current_time('timestamp')),
            ];
        }

        return $actividad_formateada;
    }

    /**
     * Obtiene estadísticas para el dashboard
     *
     * @return array
     */
    private function get_dashboard_stats() {
        global $wpdb;

        // Addons activos
        $addons_activos = count(Flavor_Addon_Manager::get_active_addons());

        // Módulos activos
        $modulos_activos = 0;
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $modulos_activos = count(Flavor_Chat_Module_Loader::get_instance()->get_loaded_modules());
        }

        // Conversaciones
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversations';
        $conversaciones = 0;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_conversaciones)) {
            $conversaciones = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_conversaciones");
        }

        // Mensajes
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_messages';
        $mensajes = 0;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_mensajes)) {
            $mensajes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_mensajes");
        }

        return [
            'addons_activos' => $addons_activos,
            'modulos_activos' => $modulos_activos,
            'conversaciones' => number_format_i18n($conversaciones),
            'mensajes' => number_format_i18n($mensajes),
        ];
    }
}
