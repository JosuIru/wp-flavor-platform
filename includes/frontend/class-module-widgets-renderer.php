<?php
/**
 * Renderizador de widgets de módulos para el portal
 *
 * Centraliza la configuración y renderizado de widgets que se muestran
 * en los dashboards de módulos del portal de usuario.
 *
 * @package FlavorPlatform
 * @subpackage Frontend
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para renderizar widgets de módulos
 */
class Flavor_Module_Widgets_Renderer {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return self
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Nada por ahora
    }

    /**
     * Obtiene la configuración de widgets para un módulo
     *
     * @param string $module_id   ID del módulo (ej: 'grupos-consumo').
     * @param object $renderer    Objeto que tiene los métodos de renderizado.
     * @return array Configuración de widgets.
     */
    public function get_widgets_config($module_id, $renderer = null) {
        $config = $this->get_all_widgets_config($renderer);
        return $config[$module_id] ?? [];
    }

    /**
     * Obtiene toda la configuración de widgets
     *
     * @param object $renderer Objeto con métodos de renderizado (para callbacks).
     * @return array Configuración completa de widgets por módulo.
     */
    public function get_all_widgets_config($renderer = null) {
        // Si no se pasa renderer, usar $this para widgets que usen métodos internos
        $r = $renderer ?: $this;

        return [
            // === GRUPOS DE CONSUMO ===
            'grupos-consumo' => [
                ['title' => __('Ciclo Actual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-update', 'size' => 'medium', 'callback' => [$r, 'render_gc_ciclo_widget'], 'action' => 'ciclos'],
                ['title' => __('Mi Pedido', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-cart', 'size' => 'medium', 'callback' => [$r, 'render_gc_pedido_widget'], 'action' => 'mi-pedido'],
            ],

            // === EVENTOS ===
            'eventos' => [
                ['title' => __('Próximo Evento', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar', 'size' => 'medium', 'callback' => [$r, 'render_eventos_proximo_widget'], 'action' => 'proximos'],
                ['title' => __('Mis Inscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-tickets-alt', 'size' => 'medium', 'callback' => [$r, 'render_eventos_inscripciones_widget'], 'action' => 'inscripciones'],
            ],

            // === RESERVAS ===
            'reservas' => [
                ['title' => __('Próxima Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar-alt', 'size' => 'medium', 'callback' => [$r, 'render_reservas_proxima_widget'], 'action' => 'mis-reservas'],
                ['title' => __('Mis Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-list-view', 'size' => 'medium', 'callback' => [$r, 'render_reservas_stats_widget'], 'action' => 'listado'],
            ],

            // === BIBLIOTECA ===
            'biblioteca' => [
                ['title' => __('Préstamos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-book', 'size' => 'medium', 'callback' => [$r, 'render_biblioteca_prestamos_widget'], 'action' => 'mis-prestamos'],
                ['title' => __('Mi Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'callback' => [$r, 'render_biblioteca_stats_widget'], 'action' => 'catalogo'],
            ],

            // === MARKETPLACE ===
            'marketplace' => [
                ['title' => __('Mis Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-megaphone', 'size' => 'medium', 'callback' => [$r, 'render_marketplace_anuncios_widget'], 'action' => 'mis-anuncios'],
                ['title' => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'callback' => [$r, 'render_marketplace_stats_widget'], 'action' => 'listado'],
            ],

            // === INCIDENCIAS ===
            'incidencias' => [
                ['title' => __('Mis Reportes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-flag', 'size' => 'medium', 'callback' => [$r, 'render_incidencias_mis_reportes_widget'], 'action' => 'mis-reportes'],
                ['title' => __('Estado General', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-pie', 'size' => 'medium', 'callback' => [$r, 'render_incidencias_stats_widget'], 'action' => 'listado'],
            ],

            // === BANCO DE TIEMPO ===
            'banco-tiempo' => [
                ['title' => __('Mi Saldo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock', 'size' => 'medium', 'callback' => [$r, 'render_banco_tiempo_saldo_widget'], 'action' => 'mi-saldo'],
                ['title' => __('Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-randomize', 'size' => 'medium', 'callback' => [$r, 'render_banco_tiempo_stats_widget'], 'action' => 'intercambios'],
            ],

            // === COLECTIVOS ===
            'colectivos' => [
                ['title' => __('Mis Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-groups', 'size' => 'medium', 'callback' => [$r, 'render_colectivos_mis_widget'], 'action' => 'mis-colectivos'],
                ['title' => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'callback' => [$r, 'render_colectivos_stats_widget'], 'action' => 'listado'],
            ],

            // === COMUNIDADES ===
            'comunidades' => [
                ['title' => __('Mis Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-multisite', 'size' => 'medium', 'callback' => [$r, 'render_comunidades_mis_widget'], 'action' => 'mis-comunidades'],
                ['title' => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'callback' => [$r, 'render_comunidades_stats_widget'], 'action' => 'actividad'],
            ],

            // === SOCIOS ===
            'socios' => [
                ['title' => __('Mi Membresía', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-id', 'size' => 'medium', 'callback' => [$r, 'render_socios_membresia_widget'], 'action' => 'mi-membresia'],
                ['title' => __('Beneficios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-awards', 'size' => 'medium', 'callback' => [$r, 'render_socios_beneficios_widget'], 'action' => 'beneficios'],
            ],

            // === FOROS ===
            'foros' => [
                ['title' => __('Mi Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-format-chat', 'size' => 'medium', 'callback' => [$r, 'render_foros_actividad_widget'], 'action' => 'mis-hilos'],
                ['title' => __('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'callback' => [$r, 'render_foros_stats_widget'], 'action' => 'hilos'],
            ],

            // === CHAT GRUPOS ===
            'chat-grupos' => [
                ['title' => __('Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-email-alt', 'size' => 'medium', 'callback' => [$r, 'render_chat_grupos_mensajes_widget'], 'action' => 'mensajes'],
                ['title' => __('Mis Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-groups', 'size' => 'medium', 'callback' => [$r, 'render_chat_grupos_stats_widget'], 'action' => 'mis-grupos'],
            ],

            // === CHAT INTERNO ===
            'chat-interno' => [
                ['title' => __('Bandeja', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-email', 'size' => 'medium', 'callback' => [$r, 'render_chat_interno_widget'], 'action' => 'mensajes'],
            ],

            // === MÓDULOS CON SHORTCODES (sin callbacks de render) ===
            'espacios-comunes' => [
                ['title' => __('Próxima Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar-alt', 'size' => 'medium', 'shortcode' => '[espacios_proxima_reserva]', 'action' => 'mis-reservas'],
                ['title' => __('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar', 'size' => 'large', 'shortcode' => '[espacios_calendario_mini]', 'action' => 'calendario'],
            ],

            'huertos-urbanos' => [
                ['title' => __('Mi Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-site-alt3', 'size' => 'large', 'shortcode' => '[mi_parcela_resumen]', 'action' => 'mi-parcela'],
                ['title' => __('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar', 'size' => 'medium', 'shortcode' => '[huertos_calendario]', 'action' => 'calendario'],
            ],

            'bicicletas-compartidas' => [
                ['title' => __('Mi Préstamo Actual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-dashboard', 'size' => 'medium', 'shortcode' => '[bicicletas_prestamo_actual]', 'action' => 'mis-prestamos'],
                ['title' => __('Estaciones Cercanas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-location', 'size' => 'large', 'shortcode' => '[bicicletas_estaciones_cercanas]', 'action' => 'mapa'],
            ],

            'parkings' => [
                ['title' => __('Ocupación Actual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-bar', 'size' => 'medium', 'shortcode' => '[parking_ocupacion_actual]', 'action' => 'disponibilidad'],
                ['title' => __('Mi Reserva Activa', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar-alt', 'size' => 'medium', 'shortcode' => '[parking_reserva_activa]', 'action' => 'mis-reservas'],
            ],

            'carpooling' => [
                ['title' => __('Próximo Viaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-car', 'size' => 'large', 'shortcode' => '[carpooling_proximo_viaje]', 'action' => 'mis-viajes'],
                ['title' => __('Búsqueda Rápida', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-search', 'size' => 'medium', 'shortcode' => '[carpooling_busqueda_rapida]', 'action' => 'buscar'],
            ],

            'reciclaje' => [
                ['title' => __('Mi Impacto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-bar', 'size' => 'large', 'shortcode' => '[reciclaje_mi_impacto]', 'action' => 'mis-puntos'],
                ['title' => __('Punto Más Cercano', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-location', 'size' => 'medium', 'shortcode' => '[reciclaje_punto_cercano]', 'action' => 'puntos-cercanos'],
            ],

            'compostaje' => [
                ['title' => __('Mi Balance', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-area', 'size' => 'medium', 'shortcode' => '[compostaje_mi_balance]', 'action' => 'mis-aportaciones'],
                ['title' => __('Compostera Cercana', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-location-alt', 'size' => 'medium', 'shortcode' => '[compostaje_cercana]', 'action' => 'mapa'],
            ],

            'bares' => [
                ['title' => __('Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-star-filled', 'size' => 'large', 'shortcode' => '[bares_destacados limit="4"]', 'action' => 'listado'],
            ],

            'cursos' => [
                ['title' => __('Mi Progreso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-awards', 'size' => 'medium', 'shortcode' => '[cursos_mi_progreso]', 'action' => 'mis-cursos'],
                ['title' => __('Próximos a Comenzar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar', 'size' => 'large', 'shortcode' => '[cursos_proximos limit="3"]', 'action' => 'catalogo'],
            ],

            'talleres' => [
                ['title' => __('Próximo Taller', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar', 'size' => 'medium', 'shortcode' => '[talleres_proximo]', 'action' => 'proximos'],
                ['title' => __('Mis Inscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-tickets-alt', 'size' => 'medium', 'shortcode' => '[talleres_mis_inscripciones limite="3"]', 'action' => 'inscripciones'],
            ],

            'red-social' => [
                ['title' => __('Mi Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-users', 'size' => 'medium', 'shortcode' => '[rs_perfil]', 'action' => 'mi-perfil'],
                ['title' => __('Mi Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-rss', 'size' => 'medium', 'shortcode' => '[rs_mi_actividad]', 'action' => 'mi-actividad'],
            ],

            'participacion' => [
                ['title' => __('Decisiones Activas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-thumbs-up', 'size' => 'medium', 'shortcode' => '[votaciones_activas]', 'action' => 'votaciones'],
                ['title' => __('Iniciativas en Marcha', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clipboard', 'size' => 'medium', 'shortcode' => '[mis_propuestas_resumen]', 'action' => 'propuestas'],
            ],

            'presupuestos-participativos' => [
                ['title' => __('Estado Actual', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-pie', 'size' => 'large', 'shortcode' => '[presupuesto_estado_actual]', 'action' => 'presupuesto'],
            ],
        ];
    }

    /**
     * Renderiza mensaje de widget vacío estándar
     *
     * @param string $message Mensaje a mostrar.
     * @return void
     */
    public function render_empty($message) {
        echo '<p class="fmd-widget-empty">' . esc_html($message) . '</p>';
    }

    /**
     * Renderiza mensaje de login requerido
     *
     * @param string $context Contexto para el mensaje (ej: 'tus eventos').
     * @return void
     */
    public function render_login_required($context) {
        printf(
            '<p class="fmd-widget-empty">%s</p>',
            esc_html(sprintf(__('Inicia sesión para ver %s.', FLAVOR_PLATFORM_TEXT_DOMAIN), $context))
        );
    }

    /**
     * Verifica si una tabla existe (helper para widgets)
     *
     * @param string $tabla Nombre de la tabla.
     * @return bool
     */
    protected function table_exists($tabla) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla)) === $tabla;
    }
}
