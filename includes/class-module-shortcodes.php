<?php
/**
 * Shortcodes Automáticos de Módulos
 *
 * Registra shortcodes automáticamente para cada módulo activo
 *
 * @package FlavorChatIA
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para shortcodes automáticos de módulos
 */
class Flavor_Module_Shortcodes {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la URL actual para redirects de login en shortcodes dinámicos.
     */
    private function get_current_request_url(): string {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return home_url($request_uri);
    }

    /**
     * Obtiene la instancia singleton
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
        // SHORTCODE UNIVERSAL - Usa este para todo
        add_shortcode('flavor', [$this, 'render_unified']);
        add_shortcode('flavor_view', [$this, 'render_unified']); // Alias

        // Legacy: mantener compatibilidad con shortcodes antiguos
        add_action('init', [$this, 'register_module_shortcodes'], 20);
        add_action('init', [$this, 'register_specific_shortcodes'], 21);

        // Shortcodes adicionales
        add_shortcode('flavor_module_form', [$this, 'render_module_form']);
        add_shortcode('flavor_module_listing', [$this, 'render_module_listing']);

        // === WIDGETS GENÉRICOS ===
        add_shortcode('flavor_ultimos', [$this, 'render_widget_ultimos']);
        add_shortcode('flavor_destacados', [$this, 'render_widget_destacados']);
        add_shortcode('flavor_proximo', [$this, 'render_widget_proximo']);
        add_shortcode('flavor_mi_resumen', [$this, 'render_widget_mi_resumen']);

        // Handler AJAX para formularios
        add_action('wp_ajax_flavor_module_action', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_flavor_module_action', [$this, 'handle_form_submission']);
    }

    /**
     * SHORTCODE UNIVERSAL
     *
     * Uso: [flavor module="incidencias" view="listado"]
     *      [flavor module="eventos" view="calendario"]
     *      [flavor module="marketplace" view="mis"]
     *      [flavor module="banco-tiempo" view="single" id="123"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_unified($atts) {
        $atts = shortcode_atts([
            'module'   => '',
            'view'     => 'listado',  // listado, single, mis, calendario, mapa, form, stats
            'id'       => '',
            'limit'    => 12,
            'columns'  => 3,
            'color'    => '',         // Color del tema o hex
            'title'    => '',         // Título personalizado
            'filters'  => 'yes',      // Mostrar filtros
            'header'   => 'yes',      // Mostrar header
            'user'     => '',         // ID de usuario (para filtrar)
        ], $atts);

        $module_slug = sanitize_title($atts['module']);
        $view = sanitize_title($atts['view']);

        if (empty($module_slug)) {
            return '<div class="flavor-error">' . __('Error: Especifica un módulo [flavor module="X"]', 'flavor-chat-ia') . '</div>';
        }

        // Verificar login para vistas personales
        if (in_array($view, ['mis', 'mis-items', 'personal', 'user']) && !is_user_logged_in()) {
            return $this->render_login_required_message();
        }

        // Cargar Archive Renderer
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }
        $renderer = new Flavor_Archive_Renderer();

        // Configuración base
        $config = [
            'per_page'     => intval($atts['limit']),
            'columns'      => intval($atts['columns']),
            'show_header'  => ($atts['header'] === 'yes'),
            'show_filters' => ($atts['filters'] === 'yes'),
        ];

        if (!empty($atts['color'])) {
            $config['color'] = $atts['color'];
        }
        if (!empty($atts['title'])) {
            $config['title'] = $atts['title'];
        }

        // Renderizar según el tipo de vista
        switch ($view) {
            // === LISTADOS ===
            case 'listado':
            case 'archive':
            case 'catalogo':
            case 'lista':
            case 'grid':
            case 'explorar':
                return $renderer->render_auto($module_slug, $config);

            // === SINGLE/DETALLE ===
            case 'single':
            case 'detalle':
            case 'ver':
            case 'ficha':
                $item_id = !empty($atts['id']) ? intval($atts['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
                return $renderer->render_single_auto($module_slug, $item_id);

            // === VISTAS PERSONALES ===
            case 'mis':
            case 'mis-items':
            case 'personal':
            case 'user':
                $config['user_id'] = !empty($atts['user']) ? intval($atts['user']) : get_current_user_id();
                $config['show_header'] = false;
                $config['empty_title'] = __('No tienes registros aún', 'flavor-chat-ia');
                return $renderer->render_auto($module_slug, $config);

            // === CALENDARIO ===
            case 'calendario':
            case 'calendar':
                return $this->render_universal_calendar($module_slug, $atts);

            // === MAPA ===
            case 'mapa':
            case 'map':
                return $this->render_universal_map($module_slug, $atts);

            // === FORMULARIO ===
            case 'form':
            case 'formulario':
            case 'crear':
            case 'nuevo':
                return $this->render_module_form_auto($module_slug, $view, $atts);

            // === ESTADÍSTICAS ===
            case 'stats':
            case 'estadisticas':
            case 'resumen':
                return $this->render_universal_stats($module_slug, $atts);

            // === WIDGETS ===
            case 'ultimos':
            case 'recientes':
            case 'novedades':
                return $this->render_widget_ultimos(['module' => $module_slug, 'limit' => $atts['limit'], 'color' => $atts['color'], 'title' => $atts['title']]);

            case 'destacados':
            case 'featured':
            case 'populares':
                return $this->render_widget_destacados(['module' => $module_slug, 'limit' => $atts['limit'], 'columns' => $atts['columns'], 'color' => $atts['color'], 'title' => $atts['title']]);

            case 'proximo':
            case 'next':
            case 'siguiente':
                return $this->render_widget_proximo(['module' => $module_slug, 'color' => $atts['color'], 'title' => $atts['title']]);

            case 'mi-resumen':
            case 'user-stats':
            case 'mi-saldo':
                return $this->render_widget_mi_resumen(['module' => $module_slug, 'color' => $atts['color'], 'title' => $atts['title']]);

            // === DEFAULT: intentar Archive Renderer ===
            default:
                return $renderer->render_auto($module_slug, $config);
        }
    }

    /**
     * Calendario universal para cualquier módulo
     */
    private function render_universal_calendar($module_slug, $atts) {
        // Obtener configuración del módulo
        $config = $this->get_module_renderer_config($module_slug);

        $color = $atts['color'] ?: ($config['color'] ?? 'primary');
        $gradient = function_exists('flavor_get_gradient_classes')
            ? flavor_get_gradient_classes($color)
            : ['from' => 'from-blue-500', 'to' => 'to-blue-600'];
        $gradient_classes = "bg-gradient-to-r {$gradient['from']} {$gradient['to']}";

        $title = $atts['title'] ?: ($config['title'] ?? ucfirst(str_replace('-', ' ', $module_slug)));
        $icon = $config['icon'] ?? '📅';

        ob_start();
        ?>
        <div class="flavor-calendar-wrapper" data-module="<?php echo esc_attr($module_slug); ?>">
            <div class="<?php echo esc_attr($gradient_classes); ?> rounded-t-xl p-4 text-white">
                <div class="flex items-center gap-3">
                    <span class="text-2xl"><?php echo esc_html($icon); ?></span>
                    <h3 class="text-lg font-bold"><?php echo esc_html($title); ?> - <?php esc_html_e('Calendario', 'flavor-chat-ia'); ?></h3>
                </div>
            </div>
            <div class="bg-white rounded-b-xl shadow-lg p-4">
                <div id="flavor-calendar-<?php echo esc_attr($module_slug); ?>" class="flavor-fullcalendar"></div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof FullCalendar !== 'undefined') {
                var calendarEl = document.getElementById('flavor-calendar-<?php echo esc_js($module_slug); ?>');
                if (calendarEl) {
                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        locale: 'es',
                        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
                        events: flavorAjax.url + '?action=flavor_get_calendar_events&module=<?php echo esc_js($module_slug); ?>&_wpnonce=' + flavorAjax.nonce
                    });
                    calendar.render();
                }
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Mapa universal para cualquier módulo
     */
    private function render_universal_map($module_slug, $atts) {
        $config = $this->get_module_renderer_config($module_slug);

        $color = $atts['color'] ?: ($config['color'] ?? 'primary');
        $gradient = function_exists('flavor_get_gradient_classes')
            ? flavor_get_gradient_classes($color)
            : ['from' => 'from-blue-500', 'to' => 'to-blue-600'];
        $gradient_classes = "bg-gradient-to-r {$gradient['from']} {$gradient['to']}";

        $title = $atts['title'] ?: ($config['title'] ?? ucfirst(str_replace('-', ' ', $module_slug)));
        $icon = $config['icon'] ?? '🗺️';

        ob_start();
        ?>
        <div class="flavor-map-wrapper" data-module="<?php echo esc_attr($module_slug); ?>">
            <div class="<?php echo esc_attr($gradient_classes); ?> rounded-t-xl p-4 text-white">
                <div class="flex items-center gap-3">
                    <span class="text-2xl"><?php echo esc_html($icon); ?></span>
                    <h3 class="text-lg font-bold"><?php echo esc_html($title); ?> - <?php esc_html_e('Mapa', 'flavor-chat-ia'); ?></h3>
                </div>
            </div>
            <div class="bg-white rounded-b-xl shadow-lg">
                <div id="flavor-map-<?php echo esc_attr($module_slug); ?>" class="flavor-leaflet-map" style="height: 400px;"></div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L !== 'undefined') {
                var mapEl = document.getElementById('flavor-map-<?php echo esc_js($module_slug); ?>');
                if (mapEl && !mapEl._leaflet_id) {
                    var map = L.map(mapEl).setView([40.4168, -3.7038], 12);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);

                    fetch(flavorAjax.url + '?action=flavor_get_map_markers&module=<?php echo esc_js($module_slug); ?>&_wpnonce=' + flavorAjax.nonce)
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && data.data.markers) {
                                data.data.markers.forEach(m => {
                                    L.marker([m.lat, m.lng]).addTo(map).bindPopup(m.popup || m.title);
                                });
                            }
                        });
                }
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Estadísticas universales para cualquier módulo
     */
    private function render_universal_stats($module_slug, $atts) {
        $module_class = $this->get_module_class($module_slug);
        $config = $this->get_module_renderer_config($module_slug);
        $stats = [];
        if ($module_class && method_exists($module_class, 'get_stats')) {
            $stats = $module_class::get_stats();
        }

        $color = $atts['color'] ?: ($config['color'] ?? 'primary');
        $gradient = function_exists('flavor_get_gradient_classes')
            ? flavor_get_gradient_classes($color)
            : ['from' => 'from-blue-500', 'to' => 'to-blue-600'];
        $gradient_classes = "bg-gradient-to-r {$gradient['from']} {$gradient['to']}";

        $title = $atts['title'] ?: ($config['title'] ?? ucfirst(str_replace('-', ' ', $module_slug)));
        $icon = $config['icon'] ?? '📊';

        ob_start();
        ?>
        <div class="flavor-stats-wrapper">
            <div class="<?php echo esc_attr($gradient_classes); ?> rounded-t-xl p-4 text-white">
                <div class="flex items-center gap-3">
                    <span class="text-2xl"><?php echo esc_html($icon); ?></span>
                    <h3 class="text-lg font-bold"><?php echo esc_html($title); ?> - <?php esc_html_e('Estadísticas', 'flavor-chat-ia'); ?></h3>
                </div>
            </div>
            <div class="bg-white rounded-b-xl shadow-lg p-6">
                <?php if (!empty($stats)): ?>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($stats as $stat): ?>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-3xl font-bold text-gray-800"><?php echo esc_html($stat['value'] ?? 0); ?></div>
                        <div class="text-sm text-gray-500"><?php echo esc_html($stat['label'] ?? ''); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-center text-gray-500"><?php esc_html_e('No hay estadísticas disponibles', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene la clase del módulo
     */
    private function get_module_class($module_slug) {
        $class_name = 'Flavor_' . str_replace('-', '_', ucwords($module_slug, '-')) . '_Module';
        if (class_exists($class_name)) {
            return $class_name;
        }
        return null;
    }

    /**
     * Obtiene la configuración renderer de un módulo de forma segura.
     *
     * Soporta implementaciones estáticas y no estáticas de get_renderer_config().
     */
    private function get_module_renderer_config($module_slug) {
        $module_class = $this->get_module_class($module_slug);
        if (!$module_class || !method_exists($module_class, 'get_renderer_config')) {
            return [];
        }

        try {
            // Intentar primero por instancia (más seguro para implementaciones mixtas).
            if (class_exists('Flavor_Chat_Module_Loader')) {
                $loader = Flavor_Chat_Module_Loader::get_instance();
                $instance = $loader->get_module(str_replace('-', '_', $module_slug));
                if (is_object($instance) && method_exists($instance, 'get_renderer_config')) {
                    try {
                        $config = $instance->get_renderer_config();
                        if (is_array($config)) {
                            return $config;
                        }
                    } catch (Throwable $e) {
                        // Continuar con fallback estático.
                    }
                }
            }

            $method = new ReflectionMethod($module_class, 'get_renderer_config');
            if ($method->isStatic()) {
                $config = $module_class::get_renderer_config();
                return is_array($config) ? $config : [];
            }
        } catch (Throwable $e) {
            return [];
        }

        return [];
    }

    /**
     * Registra un shortcode solo si no existe ya
     * Esto permite que los módulos registren sus propios shortcodes primero
     *
     * @param string $tag Nombre del shortcode
     * @param string $module Módulo al que pertenece
     * @param string $template Template a renderizar
     */
    private function register_fallback_shortcode($tag, $module, $template) {
        if (!shortcode_exists($tag)) {
            add_shortcode($tag, function($atts) use ($module, $template) {
                return $this->render_template_shortcode($module, $template, $atts);
            });
        }
    }

    /**
     * Registra shortcodes específicos para módulos con templates propios
     * Solo se registran como fallback si el módulo no ha registrado sus propios shortcodes
     */
    public function register_specific_shortcodes() {
        // === ESPACIOS COMUNES ===
        $this->register_fallback_shortcode('espacios_listado', 'espacios-comunes', 'listado');
        $this->register_fallback_shortcode('espacios_mis_reservas', 'espacios-comunes', 'mis-reservas');
        $this->register_fallback_shortcode('espacios_calendario', 'espacios-comunes', 'calendario');
        $this->register_fallback_shortcode('espacios_detalle', 'espacios-comunes', 'detalle');

        // === EVENTOS ===
        $this->register_fallback_shortcode('eventos_listado', 'eventos', 'listado');
        $this->register_fallback_shortcode('eventos_mis_inscripciones', 'eventos', 'mis-inscripciones');
        $this->register_fallback_shortcode('eventos_calendario', 'eventos', 'calendario');

        // === BIBLIOTECA ===
        $this->register_fallback_shortcode('biblioteca_catalogo', 'biblioteca', 'catalogo');
        $this->register_fallback_shortcode('biblioteca_mis_prestamos', 'biblioteca', 'mis-prestamos');
        $this->register_fallback_shortcode('biblioteca_mis_libros', 'biblioteca', 'mis-libros');

        // === MARKETPLACE ===
        $this->register_fallback_shortcode('marketplace_listado', 'marketplace', 'listado');
        $this->register_fallback_shortcode('marketplace_formulario', 'marketplace', 'formulario');

        // === INCIDENCIAS ===
        $this->register_fallback_shortcode('incidencias_listado', 'incidencias', 'listado');
        $this->register_fallback_shortcode('incidencias_mis_incidencias', 'incidencias', 'mis-incidencias');
        $this->register_fallback_shortcode('incidencias_mapa', 'incidencias', 'mapa');
        $this->register_fallback_shortcode('incidencias_reportar', 'incidencias', 'reportar');

        // === CURSOS ===
        $this->register_fallback_shortcode('cursos_catalogo', 'cursos', 'catalogo');
        $this->register_fallback_shortcode('cursos_mis_cursos', 'cursos', 'mis-cursos');
        $this->register_fallback_shortcode('cursos_aula', 'cursos', 'aula');

        // === TALLERES ===
        $this->register_fallback_shortcode('proximos_talleres', 'talleres', 'listado');
        $this->register_fallback_shortcode('mis_inscripciones_talleres', 'talleres', 'mis-inscripciones');
        $this->register_fallback_shortcode('calendario_talleres', 'talleres', 'calendario');
        $this->register_fallback_shortcode('proponer_taller', 'talleres', 'proponer');

        // === HUERTOS URBANOS ===
        $this->register_fallback_shortcode('mapa_huertos', 'huertos-urbanos', 'mapa');
        $this->register_fallback_shortcode('mi_parcela', 'huertos-urbanos', 'mi-parcela');
        $this->register_fallback_shortcode('lista_huertos', 'huertos-urbanos', 'listado');
        $this->register_fallback_shortcode('calendario_cultivos', 'huertos-urbanos', 'calendario');
        $this->register_fallback_shortcode('intercambios_huertos', 'huertos-urbanos', 'intercambios');

        // === PARTICIPACIÓN ===
        $this->register_fallback_shortcode('propuestas_activas', 'participacion', 'propuestas');
        $this->register_fallback_shortcode('votacion_activa', 'participacion', 'votacion');
        $this->register_fallback_shortcode('crear_propuesta', 'participacion', 'crear');
        $this->register_fallback_shortcode('resultados_participacion', 'participacion', 'resultados');

        // === PRESUPUESTOS PARTICIPATIVOS ===
        $this->register_fallback_shortcode('presupuesto_participativo', 'presupuestos-participativos', 'dashboard');
        $this->register_fallback_shortcode('fases_participacion', 'presupuestos-participativos', 'fases');
        $this->register_fallback_shortcode('presupuesto_estado_actual', 'presupuestos-participativos', 'fases');
        $this->register_fallback_shortcode('presupuestos_listado', 'presupuestos-participativos', 'listado');
        $this->register_fallback_shortcode('presupuestos_votar', 'presupuestos-participativos', 'votar');
        $this->register_fallback_shortcode('presupuestos_mi_proyecto', 'presupuestos-participativos', 'mis-propuestas');
        $this->register_fallback_shortcode('presupuestos_resultados', 'presupuestos-participativos', 'resultados');
        $this->register_fallback_shortcode('presupuestos_seguimiento', 'presupuestos-participativos', 'seguimiento');
        $this->register_fallback_shortcode('presupuestos_proponer', 'presupuestos-participativos', 'proponer');

        // === RECICLAJE ===
        $this->register_fallback_shortcode('reciclaje_mis_puntos', 'reciclaje', 'mis-puntos');
        $this->register_fallback_shortcode('reciclaje_puntos_cercanos', 'reciclaje', 'puntos-cercanos');
        $this->register_fallback_shortcode('reciclaje_ranking', 'reciclaje', 'ranking');
        $this->register_fallback_shortcode('reciclaje_guia', 'reciclaje', 'guia');
        $this->register_fallback_shortcode('reciclaje_recompensas', 'reciclaje', 'recompensas');

        // === COMPOSTAJE ===
        $this->register_fallback_shortcode('mis_aportaciones', 'compostaje', 'mis-aportaciones');
        $this->register_fallback_shortcode('estadisticas_compostaje', 'compostaje', 'estadisticas');
        $this->register_fallback_shortcode('mapa_composteras', 'compostaje', 'mapa');
        $this->register_fallback_shortcode('registrar_aportacion', 'compostaje', 'registrar');
        $this->register_fallback_shortcode('ranking_compostaje', 'compostaje', 'ranking');

        // === RED SOCIAL ===
        $this->register_fallback_shortcode('rs_perfil', 'red-social', 'perfil');
        $this->register_fallback_shortcode('rs_feed', 'red-social', 'feed');
        $this->register_fallback_shortcode('rs_explorar', 'red-social', 'explorar');
        $this->register_fallback_shortcode('rs_historias', 'red-social', 'historias');

        // === GRUPOS DE CONSUMO ===
        $this->register_fallback_shortcode('gc_ciclo_actual', 'grupos-consumo', 'ciclo-actual');
        $this->register_fallback_shortcode('gc_mi_pedido', 'grupos-consumo', 'mi-pedido');
        $this->register_fallback_shortcode('gc_productos', 'grupos-consumo', 'productos');
        $this->register_fallback_shortcode('gc_productores_cercanos', 'grupos-consumo', 'productores');
        $this->register_fallback_shortcode('gc_mi_cesta', 'grupos-consumo', 'mi-cesta');

        // === PARKINGS ===
        $this->register_fallback_shortcode('flavor_disponibilidad_parking', 'parkings', 'disponibilidad');
        $this->register_fallback_shortcode('flavor_mis_reservas_parking', 'parkings', 'mis-reservas');
        $this->register_fallback_shortcode('flavor_mapa_parkings', 'parkings', 'mapa');
        $this->register_fallback_shortcode('flavor_ocupacion_tiempo_real', 'parkings', 'ocupacion');
        $this->register_fallback_shortcode('flavor_solicitar_plaza', 'parkings', 'solicitar');

        // === CHAT ===
        $this->register_fallback_shortcode('flavor_chat_inbox', 'chat-interno', 'inbox');
        $this->register_fallback_shortcode('flavor_iniciar_chat', 'chat-interno', 'iniciar');
        $this->register_fallback_shortcode('flavor_chat_grupos', 'chat-interno', 'grupos');

        // === BICICLETAS COMPARTIDAS ===
        $this->register_fallback_shortcode('flavor_bicicletas_mapa', 'bicicletas-compartidas', 'mapa');
        $this->register_fallback_shortcode('flavor_bicicletas_mis_prestamos', 'bicicletas-compartidas', 'mis-prestamos');
        $this->register_fallback_shortcode('flavor_bicicletas_estaciones', 'bicicletas-compartidas', 'estaciones');
        $this->register_fallback_shortcode('flavor_bicicletas_alquilar', 'bicicletas-compartidas', 'alquilar');

        // === CARPOOLING ===
        $this->register_fallback_shortcode('flavor_carpooling_viajes', 'carpooling', 'viajes');
        $this->register_fallback_shortcode('flavor_carpooling_mis_viajes', 'carpooling', 'mis-viajes');
        $this->register_fallback_shortcode('flavor_carpooling_publicar', 'carpooling', 'publicar');
        $this->register_fallback_shortcode('flavor_carpooling_buscar', 'carpooling', 'buscar');

        // === BANCO TIEMPO ===
        $this->register_fallback_shortcode('flavor_banco_tiempo_servicios', 'banco-tiempo', 'servicios');
        $this->register_fallback_shortcode('flavor_banco_tiempo_mis_intercambios', 'banco-tiempo', 'intercambios');
        $this->register_fallback_shortcode('flavor_banco_tiempo_ofrecer', 'banco-tiempo', 'ofrecer');
        $this->register_fallback_shortcode('flavor_banco_tiempo_mi_saldo', 'banco-tiempo', 'mi-saldo');
        $this->register_fallback_shortcode('flavor_banco_tiempo_ranking', 'banco-tiempo', 'ranking-comunidad');
        $this->register_fallback_shortcode('flavor_banco_tiempo_donar', 'banco-tiempo', 'donar-horas');
        $this->register_fallback_shortcode('flavor_banco_tiempo_fondo', 'banco-tiempo', 'fondo-solidario');
        $this->register_fallback_shortcode('flavor_banco_tiempo_sostenibilidad', 'banco-tiempo', 'dashboard-sostenibilidad');
        $this->register_fallback_shortcode('flavor_banco_tiempo_reputacion', 'banco-tiempo', 'mi-reputacion');

        // === COMUNIDADES ===
        $this->register_fallback_shortcode('flavor_comunidades_listado', 'comunidades', 'listado');
        $this->register_fallback_shortcode('flavor_comunidades_mis_comunidades', 'comunidades', 'mis-comunidades');
        $this->register_fallback_shortcode('flavor_comunidades_crear', 'comunidades', 'crear');
        $this->register_fallback_shortcode('flavor_comunidades_detalle', 'comunidades', 'detalle');

        // === PODCAST ===
        $this->register_fallback_shortcode('flavor_podcast_listado', 'podcast', 'listado');
        $this->register_fallback_shortcode('flavor_podcast_reproductor', 'podcast', 'reproductor');
        $this->register_fallback_shortcode('flavor_podcast_episodio', 'podcast', 'episodio');

        // === RADIO ===
        $this->register_fallback_shortcode('flavor_radio_player', 'radio', 'player');
        $this->register_fallback_shortcode('flavor_radio_programacion', 'radio', 'programacion');

        // === RED SOCIAL ===
        $this->register_fallback_shortcode('flavor_red_social_perfil', 'red-social', 'perfil');
        $this->register_fallback_shortcode('flavor_red_social_feed', 'red-social', 'feed');
        $this->register_fallback_shortcode('flavor_red_social_amigos', 'red-social', 'amigos');

        // === TRAMITES ===
        $this->register_fallback_shortcode('flavor_tramites_listado', 'tramites', 'listado');
        $this->register_fallback_shortcode('flavor_tramites_mis_tramites', 'tramites', 'mis-tramites');
        $this->register_fallback_shortcode('flavor_tramites_nuevo', 'tramites', 'nuevo');

        // === TRANSPARENCIA ===
        $this->register_fallback_shortcode('flavor_transparencia_portal', 'transparencia', 'portal');
        $this->register_fallback_shortcode('flavor_transparencia_contratos', 'transparencia', 'contratos');
        $this->register_fallback_shortcode('flavor_transparencia_presupuestos', 'transparencia', 'presupuestos');

        // === AVISOS MUNICIPALES ===
        $this->register_fallback_shortcode('flavor_avisos_listado', 'avisos-municipales', 'listado');
        $this->register_fallback_shortcode('flavor_avisos_suscripcion', 'avisos-municipales', 'suscripcion');

        // === DIRECTORIO ===
        $this->register_fallback_shortcode('flavor_directorio_buscar', 'directorio', 'buscar');
        $this->register_fallback_shortcode('flavor_directorio_categorias', 'directorio', 'categorias');

        // === VOLUNTARIADO ===
        $this->register_fallback_shortcode('flavor_voluntariado_oportunidades', 'voluntariado', 'oportunidades');
        $this->register_fallback_shortcode('flavor_voluntariado_mis_actividades', 'voluntariado', 'mis-actividades');
        $this->register_fallback_shortcode('flavor_voluntariado_inscribirse', 'voluntariado', 'inscribirse');

        // === ENCUESTAS ===
        $this->register_fallback_shortcode('flavor_encuestas_activas', 'encuestas', 'activas');
        $this->register_fallback_shortcode('flavor_encuestas_mis_respuestas', 'encuestas', 'mis-respuestas');
        $this->register_fallback_shortcode('flavor_encuestas_crear', 'encuestas', 'crear');

        // === FOROS ===
        $this->register_fallback_shortcode('flavor_foros_listado', 'foros', 'listado');
        $this->register_fallback_shortcode('flavor_foros_tema', 'foros', 'tema');
        $this->register_fallback_shortcode('flavor_foros_crear_tema', 'foros', 'crear-tema');

        // === EMPLEO ===
        $this->register_fallback_shortcode('flavor_empleo_ofertas', 'empleo', 'ofertas');
        $this->register_fallback_shortcode('flavor_empleo_mis_candidaturas', 'empleo', 'mis-candidaturas');
        $this->register_fallback_shortcode('flavor_empleo_publicar', 'empleo', 'publicar');

        // === SERVICIOS SOCIALES ===
        $this->register_fallback_shortcode('flavor_servicios_sociales_recursos', 'servicios-sociales', 'recursos');
        $this->register_fallback_shortcode('flavor_servicios_sociales_solicitar', 'servicios-sociales', 'solicitar');

        // === MONEDA LOCAL ===
        $this->register_fallback_shortcode('flavor_moneda_local_saldo', 'moneda-local', 'saldo');
        $this->register_fallback_shortcode('flavor_moneda_local_transferir', 'moneda-local', 'transferir');
        $this->register_fallback_shortcode('flavor_moneda_local_comercios', 'moneda-local', 'comercios');

        // === FICHAJE ===
        $this->register_fallback_shortcode('flavor_fichaje_fichar', 'fichaje', 'fichar');
        $this->register_fallback_shortcode('flavor_fichaje_historial', 'fichaje', 'historial');
        $this->register_fallback_shortcode('flavor_fichaje_resumen', 'fichaje', 'resumen');

        // === ADVERTISING ===
        $this->register_fallback_shortcode('flavor_advertising_banner', 'advertising', 'banner');
        $this->register_fallback_shortcode('flavor_advertising_mis_campanas', 'advertising', 'mis-campanas');

        // === HUELLA ECOLÓGICA ===
        $this->register_fallback_shortcode('flavor_huella_calculadora', 'huella-ecologica', 'calculadora');
        $this->register_fallback_shortcode('flavor_huella_comunidad', 'huella-ecologica', 'comunidad');
        $this->register_fallback_shortcode('flavor_huella_logros', 'huella-ecologica', 'logros');
        $this->register_fallback_shortcode('flavor_huella_mis_registros', 'huella-ecologica', 'mis-registros');
        $this->register_fallback_shortcode('flavor_huella_proyectos', 'huella-ecologica', 'proyectos');

        // === SABERES ANCESTRALES ===
        $this->register_fallback_shortcode('flavor_saberes_catalogo', 'saberes-ancestrales', 'catalogo');
        $this->register_fallback_shortcode('flavor_saberes_compartir', 'saberes-ancestrales', 'compartir');
        $this->register_fallback_shortcode('flavor_saberes_talleres', 'saberes-ancestrales', 'talleres');

        // === ECONOMÍA DEL DON ===
        $this->register_fallback_shortcode('flavor_don_listado', 'economia-don', 'listado-dones');
        $this->register_fallback_shortcode('flavor_don_mis_dones', 'economia-don', 'mis-dones');
        $this->register_fallback_shortcode('flavor_don_muro_gratitud', 'economia-don', 'muro-gratitud');
        $this->register_fallback_shortcode('flavor_don_ofrecer', 'economia-don', 'ofrecer-don');

        // === ECONOMÍA DE SUFICIENCIA ===
        $this->register_fallback_shortcode('flavor_suficiencia_biblioteca', 'economia-suficiencia', 'biblioteca');
        $this->register_fallback_shortcode('flavor_suficiencia_compromisos', 'economia-suficiencia', 'compromisos');
        $this->register_fallback_shortcode('flavor_suficiencia_evaluacion', 'economia-suficiencia', 'evaluacion');
        $this->register_fallback_shortcode('flavor_suficiencia_intro', 'economia-suficiencia', 'intro');
        $this->register_fallback_shortcode('flavor_suficiencia_mi_camino', 'economia-suficiencia', 'mi-camino');

        // === TRABAJO DIGNO ===
        $this->register_fallback_shortcode('flavor_trabajo_emprendimientos', 'trabajo-digno', 'emprendimientos');
        $this->register_fallback_shortcode('flavor_trabajo_formacion', 'trabajo-digno', 'formacion');
        $this->register_fallback_shortcode('flavor_trabajo_mi_perfil', 'trabajo-digno', 'mi-perfil');
        $this->register_fallback_shortcode('flavor_trabajo_ofertas', 'trabajo-digno', 'ofertas');
        $this->register_fallback_shortcode('flavor_trabajo_publicar', 'trabajo-digno', 'publicar');

        // === SELLO CONCIENCIA ===
        $this->register_fallback_shortcode('flavor_sello_badge', 'sello-conciencia', 'badge');
        $this->register_fallback_shortcode('flavor_sello_premisas', 'sello-conciencia', 'premisas');

        // === CÍRCULOS DE CUIDADOS ===
        $this->register_fallback_shortcode('flavor_circulos_listado', 'circulos-cuidados', 'listado-circulos');
        $this->register_fallback_shortcode('flavor_circulos_mis_cuidados', 'circulos-cuidados', 'mis-cuidados');
        $this->register_fallback_shortcode('flavor_circulos_necesidades', 'circulos-cuidados', 'necesidades');

        // === JUSTICIA RESTAURATIVA ===
        $this->register_fallback_shortcode('flavor_justicia_info', 'justicia-restaurativa', 'info');
        $this->register_fallback_shortcode('flavor_justicia_mediadores', 'justicia-restaurativa', 'mediadores');
        $this->register_fallback_shortcode('flavor_justicia_mis_procesos', 'justicia-restaurativa', 'mis-procesos');
        $this->register_fallback_shortcode('flavor_justicia_solicitar', 'justicia-restaurativa', 'solicitar');

        // === EMAIL MARKETING ===
        $this->register_fallback_shortcode('flavor_newsletter_darse_baja', 'email-marketing', 'darse-baja');
        $this->register_fallback_shortcode('flavor_newsletter_formulario', 'email-marketing', 'formulario-suscripcion');
        $this->register_fallback_shortcode('flavor_newsletter_preferencias', 'email-marketing', 'preferencias');

        // === ALIASES SIN PREFIJO (usado en dynamic-pages) ===
        // Banco Tiempo
        $this->register_fallback_shortcode('banco_tiempo_mi_saldo', 'banco-tiempo', 'mi-saldo');
        $this->register_fallback_shortcode('banco_tiempo_mis_intercambios', 'banco-tiempo', 'intercambios');
        $this->register_fallback_shortcode('banco_tiempo_ofrecer', 'banco-tiempo', 'ofrecer');
        $this->register_fallback_shortcode('banco_tiempo_ranking', 'banco-tiempo', 'ranking-comunidad');
        $this->register_fallback_shortcode('banco_tiempo_servicios', 'banco-tiempo', 'servicios');

        // Carpooling
        $this->register_fallback_shortcode('carpooling_buscar', 'carpooling', 'buscar');
        $this->register_fallback_shortcode('carpooling_busqueda_rapida', 'carpooling', 'busqueda-rapida');
        $this->register_fallback_shortcode('carpooling_mis_reservas', 'carpooling', 'mis-reservas');
        $this->register_fallback_shortcode('carpooling_mis_viajes', 'carpooling', 'mis-viajes');
        $this->register_fallback_shortcode('carpooling_proximo_viaje', 'carpooling', 'proximo-viaje');
        $this->register_fallback_shortcode('carpooling_publicar', 'carpooling', 'publicar');

        // Avisos
        $this->register_fallback_shortcode('avisos_urgentes', 'avisos-municipales', 'urgentes');
        $this->register_fallback_shortcode('avisos_activos', 'avisos-municipales', 'activos');
        $this->register_fallback_shortcode('historial_avisos', 'avisos-municipales', 'historial');
        $this->register_fallback_shortcode('suscribirse_avisos', 'avisos-municipales', 'suscribirse');

        // Ayuda Vecinal
        $this->register_fallback_shortcode('ayuda_vecinal_cercana', 'ayuda-vecinal', 'cercana');

        // Bicicletas
        $this->register_fallback_shortcode('bicicletas_estaciones_cercanas', 'bicicletas-compartidas', 'estaciones');
        $this->register_fallback_shortcode('bicicletas_prestamo_actual', 'bicicletas-compartidas', 'prestamo-actual');

        // Chat
        $this->register_fallback_shortcode('chat_grupos_sin_leer', 'chat-grupos', 'sin-leer');
        $this->register_fallback_shortcode('chat_mensajes_sin_leer', 'chat-interno', 'sin-leer');

        // Colectivos
        $this->register_fallback_shortcode('colectivos_mi_actividad', 'colectivos', 'mi-actividad');

        // Compostaje
        $this->register_fallback_shortcode('compostaje_cercana', 'compostaje', 'cercana');
        $this->register_fallback_shortcode('compostaje_mi_balance', 'compostaje', 'mi-balance');

        // Cursos
        $this->register_fallback_shortcode('cursos_mi_progreso', 'cursos', 'mi-progreso');
        $this->register_fallback_shortcode('cursos_mis_inscripciones', 'cursos', 'mis-inscripciones');

        // Eventos
        $this->register_fallback_shortcode('eventos_proximo', 'eventos', 'proximo');

        // Fichaje Empleados
        $this->register_fallback_shortcode('fichaje_boton', 'fichaje-empleados', 'boton');
        $this->register_fallback_shortcode('fichaje_fichar', 'fichaje-empleados', 'fichar');
        $this->register_fallback_shortcode('fichaje_calendario', 'fichaje-empleados', 'calendario');
        $this->register_fallback_shortcode('fichaje_informe', 'fichaje-empleados', 'informe');
        $this->register_fallback_shortcode('fichaje_mis_fichajes', 'fichaje-empleados', 'mis-fichajes');
        $this->register_fallback_shortcode('fichaje_resumen', 'fichaje-empleados', 'resumen');

        // Grupos Consumo
        $this->register_fallback_shortcode('gc_calendario', 'grupos-consumo', 'calendario');
        $this->register_fallback_shortcode('gc_grupos_lista', 'grupos-consumo', 'grupos-lista');
        $this->register_fallback_shortcode('gc_historial', 'grupos-consumo', 'historial');
        $this->register_fallback_shortcode('gc_panel', 'grupos-consumo', 'panel');
        $this->register_fallback_shortcode('gc_suscripciones', 'grupos-consumo', 'suscripciones');

        // Huertos
        $this->register_fallback_shortcode('huertos_calendario', 'huertos-urbanos', 'calendario');

        // Incidencias
        $this->register_fallback_shortcode('incidencias_mis_reportes', 'incidencias', 'mis-reportes');
        $this->register_fallback_shortcode('incidencias_resumen_estado', 'incidencias', 'resumen-estado');

        // Marketplace
        $this->register_fallback_shortcode('marketplace_mis_anuncios', 'marketplace', 'mis-anuncios');
        $this->register_fallback_shortcode('marketplace_mis_stats', 'marketplace', 'mis-stats');

        // Parking
        $this->register_fallback_shortcode('parking_ocupacion_actual', 'parkings', 'ocupacion-actual');
        $this->register_fallback_shortcode('parking_reserva_activa', 'parkings', 'reserva-activa');

        // Podcast
        $this->register_fallback_shortcode('podcast_lista_episodios', 'podcast', 'lista-episodios');
        $this->register_fallback_shortcode('podcast_player', 'podcast', 'player');
        $this->register_fallback_shortcode('podcast_series', 'podcast', 'series');
        $this->register_fallback_shortcode('podcast_suscribirse', 'podcast', 'suscribirse');
        $this->register_fallback_shortcode('podcast_ultimo_episodio', 'podcast', 'ultimo-episodio');

        // Presupuestos participativos
        $this->register_fallback_shortcode('presupuesto_estado_actual', 'presupuestos-participativos', 'estado-actual');

        // Radio
        $this->register_fallback_shortcode('radio_en_directo', 'radio', 'en-directo');

        // Reciclaje
        $this->register_fallback_shortcode('reciclaje_calendario', 'reciclaje', 'calendario');
        $this->register_fallback_shortcode('reciclaje_mi_impacto', 'reciclaje', 'mi-impacto');
        $this->register_fallback_shortcode('reciclaje_punto_cercano', 'reciclaje', 'punto-cercano');

        // Red Social
        $this->register_fallback_shortcode('rs_mi_actividad', 'red-social', 'mi-actividad');
        $this->register_fallback_shortcode('rs_notificaciones', 'red-social', 'notificaciones');

        // Reservas
        $this->register_fallback_shortcode('reservas_calendario_mini', 'reservas', 'calendario-mini');
        $this->register_fallback_shortcode('reservas_proxima', 'reservas', 'proxima');

        // Socios
        $this->register_fallback_shortcode('socios_mi_carnet', 'socios', 'mi-carnet');

        // Talleres
        $this->register_fallback_shortcode('talleres_proximo', 'talleres', 'proximo');

        // Trading
        $this->register_fallback_shortcode('trading_balance', 'trading-ia', 'balance');

        // Tramites
        $this->register_fallback_shortcode('catalogo_tramites', 'tramites', 'catalogo');
        $this->register_fallback_shortcode('iniciar_tramite', 'tramites', 'iniciar');
        $this->register_fallback_shortcode('mis_expedientes', 'tramites', 'mis-expedientes');
        $this->register_fallback_shortcode('tramites_pendientes', 'tramites', 'pendientes');

        // Transparencia
        $this->register_fallback_shortcode('transparencia_actas', 'transparencia', 'actas');
        $this->register_fallback_shortcode('transparencia_indicadores', 'transparencia', 'indicadores');
        $this->register_fallback_shortcode('transparencia_portal', 'transparencia', 'portal');
        $this->register_fallback_shortcode('transparencia_presupuesto_actual', 'transparencia', 'presupuesto-actual');
        $this->register_fallback_shortcode('transparencia_presupuesto_resumen', 'transparencia', 'presupuesto-resumen');
        $this->register_fallback_shortcode('transparencia_ultimos_gastos', 'transparencia', 'ultimos-gastos');

        // Espacios
        $this->register_fallback_shortcode('espacios_calendario_mini', 'espacios-comunes', 'calendario-mini');
        $this->register_fallback_shortcode('espacios_proxima_reserva', 'espacios-comunes', 'proxima-reserva');
        $this->register_fallback_shortcode('espacios_reservar', 'espacios-comunes', 'reservar');

        // Otros
        $this->register_fallback_shortcode('votaciones_activas', 'participacion', 'votaciones-activas');
        $this->register_fallback_shortcode('mis_propuestas_resumen', 'participacion', 'mis-propuestas-resumen');

        // === SHORTCODES ADICIONALES (Fase 5 - Plan Refactorización) ===

        // Advertising
        $this->register_fallback_shortcode('ads_rendimiento', 'advertising', 'rendimiento');
        $this->register_fallback_shortcode('ads_mis_campanas', 'advertising', 'mis-campanas');

        // DEX Solana
        $this->register_fallback_shortcode('dex_balance', 'dex-solana', 'balance');
        $this->register_fallback_shortcode('dex_operaciones', 'dex-solana', 'operaciones');
        $this->register_fallback_shortcode('dex_trading', 'dex-solana', 'trading');

        // Empresarial
        $this->register_fallback_shortcode('empresa_mi_ficha', 'empresarial', 'mi-ficha');
        $this->register_fallback_shortcode('empresa_dashboard', 'empresarial', 'dashboard');
        $this->register_fallback_shortcode('empresa_servicios', 'empresarial', 'servicios');

        // Comunidades
        $this->register_fallback_shortcode('mi_comunidad_resumen', 'comunidades', 'mi-comunidad-resumen');
        $this->register_fallback_shortcode('comunidades_mi_actividad', 'comunidades', 'mi-actividad');

        // Huertos Urbanos
        $this->register_fallback_shortcode('mi_parcela_resumen', 'huertos-urbanos', 'mi-parcela-resumen');
        $this->register_fallback_shortcode('huertos_mi_cultivo', 'huertos-urbanos', 'mi-cultivo');

        // Email Marketing / Newsletter
        $this->register_fallback_shortcode('newsletter_mi_estado', 'email-marketing', 'mi-estado');
        $this->register_fallback_shortcode('newsletter_historial', 'email-marketing', 'historial');

        // Socios
        $this->register_fallback_shortcode('ultimo_pago', 'socios', 'ultimo-pago');
        $this->register_fallback_shortcode('socios_estado_cuota', 'socios', 'estado-cuota');
        $this->register_fallback_shortcode('socios_historial_pagos', 'socios', 'historial-pagos');
        $this->register_fallback_shortcode('socios_beneficios', 'socios', 'beneficios');
        $this->register_fallback_shortcode('socios_historial', 'socios', 'historial');
        $this->register_fallback_shortcode('socios_carnet', 'socios', 'carnet');

        // WooCommerce
        $this->register_fallback_shortcode('woo_ultimo_pedido', 'woocommerce', 'ultimo-pedido');
        $this->register_fallback_shortcode('woo_resumen_compras', 'woocommerce', 'resumen-compras');

        // Campañas
        $this->register_fallback_shortcode('campanias_activas', 'campanias', 'activas');
        $this->register_fallback_shortcode('campanias_firmar', 'campanias', 'firmar');
        $this->register_fallback_shortcode('mis_campanias', 'campanias', 'mis-campanias');

        // Chat Estados
        $this->register_fallback_shortcode('chat_estados_ver', 'chat-estados', 'ver-estados');
        $this->register_fallback_shortcode('chat_estados_crear', 'chat-estados', 'crear');
        $this->register_fallback_shortcode('chat_estados_mis_estados', 'chat-estados', 'mis-estados');

        // Chat Interno
        $this->register_fallback_shortcode('chat_interno_conversaciones', 'chat-interno', 'conversaciones');
        $this->register_fallback_shortcode('chat_interno_nuevo', 'chat-interno', 'nuevo');
        $this->register_fallback_shortcode('chat_interno_archivados', 'chat-interno', 'archivados');

        // Documentación Legal
        $this->register_fallback_shortcode('documentacion_legal_buscar', 'documentacion-legal', 'buscar');
        $this->register_fallback_shortcode('documentacion_legal_leyes', 'documentacion-legal', 'leyes');
        $this->register_fallback_shortcode('documentacion_legal_modelos', 'documentacion-legal', 'modelos');
        $this->register_fallback_shortcode('documentacion_legal_sentencias', 'documentacion-legal', 'sentencias');
        $this->register_fallback_shortcode('documentacion_legal_favoritos', 'documentacion-legal', 'favoritos');

        // Mapa de Actores
        $this->register_fallback_shortcode('mapa_actores_listado', 'mapa-actores', 'listado');
        $this->register_fallback_shortcode('mapa_actores_grafo', 'mapa-actores', 'grafo');
        $this->register_fallback_shortcode('mapa_actores_tipos', 'mapa-actores', 'tipos');
        $this->register_fallback_shortcode('mapa_actores_relaciones', 'mapa-actores', 'relaciones');

        // Recetas
        $this->register_fallback_shortcode('recetas_listado', 'recetas', 'listado');
        $this->register_fallback_shortcode('recetas_mis_recetas', 'recetas', 'mis-recetas');
        $this->register_fallback_shortcode('recetas_favoritas', 'recetas', 'favoritas');
        $this->register_fallback_shortcode('recetas_formulario', 'recetas', 'formulario');

        // Seguimiento de Denuncias
        $this->register_fallback_shortcode('denuncias_listado', 'seguimiento-denuncias', 'listado');
        $this->register_fallback_shortcode('denuncias_formulario', 'seguimiento-denuncias', 'formulario');
        $this->register_fallback_shortcode('denuncias_alertas', 'seguimiento-denuncias', 'alertas');
        $this->register_fallback_shortcode('denuncias_archivadas', 'seguimiento-denuncias', 'archivadas');

        // Themacle
        $this->register_fallback_shortcode('themacle_listado', 'themacle', 'listado');
        $this->register_fallback_shortcode('themacle_mis_temas', 'themacle', 'mis-temas');
        $this->register_fallback_shortcode('themacle_formulario', 'themacle', 'formulario');

        // Biodiversidad
        $this->register_fallback_shortcode('biodiversidad_mapa', 'biodiversidad-local', 'mapa');
        $this->register_fallback_shortcode('biodiversidad_avistamientos', 'biodiversidad-local', 'avistamientos');
        $this->register_fallback_shortcode('biodiversidad_mis_registros', 'biodiversidad-local', 'mis-registros');

        // Clientes
        $this->register_fallback_shortcode('clientes_listado', 'clientes', 'listado');
        $this->register_fallback_shortcode('clientes_mis_leads', 'clientes', 'mis-leads');
        $this->register_fallback_shortcode('clientes_pipeline', 'clientes', 'pipeline');

        // Facturas
        $this->register_fallback_shortcode('facturas_listado', 'facturas', 'listado');
        $this->register_fallback_shortcode('facturas_mis_facturas', 'facturas', 'mis-facturas');
        $this->register_fallback_shortcode('facturas_crear', 'facturas', 'crear');

        // Bares
        $this->register_fallback_shortcode('bares_listado', 'bares', 'listado');
        $this->register_fallback_shortcode('bares_mapa', 'bares', 'mapa');
        $this->register_fallback_shortcode('bares_mi_bar', 'bares', 'mi-bar');
        $this->register_fallback_shortcode('bares_opiniones', 'bares', 'opiniones');
        $this->register_fallback_shortcode('bares_promociones', 'bares', 'promociones');
        $this->register_fallback_shortcode('bares_reservar', 'bares', 'reservar');

        // === SHORTCODES ADICIONALES (Fase 6 - Completar cobertura) ===

        // Avisos - adicionales
        $this->register_fallback_shortcode('avisos_categorias', 'avisos-municipales', 'categorias');
        $this->register_fallback_shortcode('avisos_suscripciones', 'avisos-municipales', 'suscripciones');

        // Banco Tiempo - adicionales
        $this->register_fallback_shortcode('banco_tiempo_mi_balance', 'banco-tiempo', 'mi-saldo');

        // Biblioteca - adicionales
        $this->register_fallback_shortcode('biblioteca_clubes', 'biblioteca', 'clubes-lectura');
        $this->register_fallback_shortcode('biblioteca_prestamos_activos', 'biblioteca', 'prestamos-activos');

        // Carpooling - adicionales
        $this->register_fallback_shortcode('carpooling_rutas', 'carpooling', 'rutas');
        $this->register_fallback_shortcode('carpooling_valoraciones', 'carpooling', 'valoraciones');

        // Círculos de Cuidados - adicionales
        $this->register_fallback_shortcode('circulos_cuidados_calendario', 'circulos-cuidados', 'calendario');
        $this->register_fallback_shortcode('circulos_cuidados_recursos', 'circulos-cuidados', 'recursos');

        // Compostaje - adicionales
        $this->register_fallback_shortcode('compostaje_comunidad', 'compostaje', 'comunidad');
        $this->register_fallback_shortcode('compostaje_guias', 'compostaje', 'guias');
        $this->register_fallback_shortcode('compostaje_ranking', 'compostaje', 'ranking');

        // Comunidades - adicionales
        $this->register_fallback_shortcode('comunidades_recursos', 'comunidades', 'recursos');

        // CRM / Clientes
        $this->register_fallback_shortcode('crm_resumen', 'clientes', 'resumen');

        // Cursos - adicionales
        $this->register_fallback_shortcode('cursos_materiales', 'cursos', 'materiales');

        // Espacios Comunes - adicionales
        $this->register_fallback_shortcode('ec_normas_uso', 'espacios-comunes', 'normas-uso');

        // Economía del Don - adicionales
        $this->register_fallback_shortcode('economia_don_mapa', 'economia-don', 'mapa');

        // Economía de Suficiencia - adicionales
        $this->register_fallback_shortcode('economia_suficiencia_comunidad', 'economia-suficiencia', 'comunidad');
        $this->register_fallback_shortcode('economia_suficiencia_retos', 'economia-suficiencia', 'retos');

        // Facturas - adicionales
        $this->register_fallback_shortcode('facturas_pendientes', 'facturas', 'pendientes');

        // Saberes - aliases
        $this->register_fallback_shortcode('flavor_mis_saberes', 'saberes-ancestrales', 'mis-aprendizajes');
        $this->register_fallback_shortcode('saberes_ancestrales_maestros', 'saberes-ancestrales', 'portadores');

        // Grupos de Consumo - adicionales
        $this->register_fallback_shortcode('gc_trueques', 'grupos-consumo', 'trueques');

        // Huella Ecológica - adicionales
        $this->register_fallback_shortcode('huella_ecologica_retos', 'huella-ecologica', 'retos');

        // Huertos - adicionales
        $this->register_fallback_shortcode('huertos_banco_semillas', 'huertos-urbanos', 'banco-semillas');

        // Incidencias - adicionales
        $this->register_fallback_shortcode('incidencias_categorias', 'incidencias', 'categorias');
        $this->register_fallback_shortcode('incidencias_estadisticas', 'incidencias', 'estadisticas');

        // Justicia Restaurativa - adicionales
        $this->register_fallback_shortcode('justicia_restaurativa_recursos', 'justicia-restaurativa', 'recursos');

        // Parkings - adicionales
        $this->register_fallback_shortcode('parkings_ocupacion', 'parkings', 'ocupacion');
        $this->register_fallback_shortcode('parkings_tarifas', 'parkings', 'tarifas');

        // Podcast - adicionales
        $this->register_fallback_shortcode('podcast_suscripciones', 'podcast', 'suscripciones');
        $this->register_fallback_shortcode('podcast_estadisticas', 'podcast', 'estadisticas');

        // Radio - adicionales
        $this->register_fallback_shortcode('radio_archivo', 'radio', 'archivo');
        $this->register_fallback_shortcode('radio_colaboradores', 'radio', 'colaboradores');

        // Recetas - adicionales
        $this->register_fallback_shortcode('recetas_ingredientes', 'recetas', 'ingredientes');
        $this->register_fallback_shortcode('recetas_temporada', 'recetas', 'temporada');

        // Red Social - adicionales
        $this->register_fallback_shortcode('red_social_amigos', 'red-social', 'amigos');
        $this->register_fallback_shortcode('red_social_historias', 'red-social', 'historias');

        // Socios - adicionales
        $this->register_fallback_shortcode('socios_mi_membresia', 'socios', 'mi-membresia');
        $this->register_fallback_shortcode('socios_directorio', 'socios', 'directorio');

        // Talleres - adicionales
        $this->register_fallback_shortcode('talleres_materiales', 'talleres', 'materiales');

        // Trabajo Digno - adicionales
        $this->register_fallback_shortcode('trabajo_digno_alertas', 'trabajo-digno', 'alertas');

        // Trámites - adicionales
        $this->register_fallback_shortcode('tramites_citas', 'tramites', 'citas');
        $this->register_fallback_shortcode('tramites_documentos', 'tramites', 'documentos');

        // Transparencia - adicionales
        $this->register_fallback_shortcode('transparencia_contratos', 'transparencia', 'contratos');

        // === SHORTCODES ADICIONALES (Fase 7 - Cobertura completa) ===

        // Ayuda Vecinal - faltantes
        $this->register_fallback_shortcode('ayuda_vecinal_estadisticas', 'ayuda-vecinal', 'estadisticas');
        $this->register_fallback_shortcode('ayuda_vecinal_mapa', 'ayuda-vecinal', 'mapa');
        $this->register_fallback_shortcode('ayuda_vecinal_mis_ayudas', 'ayuda-vecinal', 'mis-ayudas');
        $this->register_fallback_shortcode('ayuda_vecinal_ofrecer', 'ayuda-vecinal', 'ofrecer');
        $this->register_fallback_shortcode('ayuda_vecinal_solicitar', 'ayuda-vecinal', 'solicitar');
        $this->register_fallback_shortcode('ayuda_vecinal_solicitudes', 'ayuda-vecinal', 'solicitudes');

        // Bicicletas - faltantes
        $this->register_fallback_shortcode('bicicletas_estaciones', 'bicicletas-compartidas', 'estaciones');
        $this->register_fallback_shortcode('bicicletas_estadisticas', 'bicicletas-compartidas', 'estadisticas');
        $this->register_fallback_shortcode('bicicletas_mis_viajes', 'bicicletas-compartidas', 'mis-viajes');

        // Biodiversidad - faltantes
        $this->register_fallback_shortcode('biodiversidad_proyectos', 'biodiversidad-local', 'proyectos');

        // Colectivos - faltantes
        $this->register_fallback_shortcode('colectivos_asambleas', 'colectivos', 'asambleas');
        $this->register_fallback_shortcode('colectivos_proyectos', 'colectivos', 'proyectos');

        // Grupos - faltantes
        $this->register_fallback_shortcode('flavor_grupos_explorar', 'chat-grupos', 'explorar');

        // Huella Ecológica - faltantes
        $this->register_fallback_shortcode('huella_ecologica_comunidad', 'huella-ecologica', 'comunidad');
        $this->register_fallback_shortcode('huella_ecologica_proyectos', 'huella-ecologica', 'proyectos');
        $this->register_fallback_shortcode('huella_ecologica_calculadora', 'huella-ecologica', 'calculadora');
        $this->register_fallback_shortcode('huella_ecologica_mis_registros', 'huella-ecologica', 'mis-registros');
        $this->register_fallback_shortcode('huella_ecologica_logros', 'huella-ecologica', 'logros');

        // Marketplace - faltantes
        $this->register_fallback_shortcode('marketplace_favoritos', 'marketplace', 'favoritos');

        // Trabajo Digno - faltantes
        $this->register_fallback_shortcode('trabajo_digno_emprendimientos', 'trabajo-digno', 'emprendimientos');
    }

    /**
     * Renderiza un template de shortcode de módulo
     * PRIORIDAD: 1. Template específico del módulo, 2. Sistema unificado
     */
    private function render_template_shortcode($module_slug, $template_name, $atts) {
        // PRIORIDAD 1: Intentar cargar template específico del módulo
        $template_result = $this->try_render_module_template($module_slug, $template_name, $atts);
        if ($template_result !== null) {
            return $template_result;
        }

        // PRIORIDAD 2: Mapear nombre de template a view del sistema unificado
        $view_map = [
            // Listados
            'listado' => 'listado', 'archive' => 'listado', 'catalogo' => 'listado',
            'lista' => 'listado', 'grid' => 'listado', 'explorar' => 'listado',
            // Singles
            'detalle' => 'single', 'single' => 'single', 'ver' => 'single', 'ficha' => 'single',
            // Calendario
            'calendario' => 'calendario', 'calendario-mini' => 'calendario', 'calendar' => 'calendario',
            // Mapa
            'mapa' => 'mapa', 'map' => 'mapa', 'puntos-cercanos' => 'mapa',
            // Formularios
            'formulario' => 'form', 'crear' => 'form', 'registrar' => 'form',
            'proponer' => 'form', 'reportar' => 'form', 'fichar' => 'form',
            // Stats
            'informe' => 'stats', 'resumen' => 'stats', 'estadisticas' => 'stats',
            'ranking' => 'stats', 'dashboard' => 'stats',
        ];

        // Determinar la vista
        $view = 'listado'; // Default
        if (strpos($template_name, 'mis-') === 0 || strpos($template_name, 'mis_') === 0) {
            $view = 'mis';
        } elseif (isset($view_map[$template_name])) {
            $view = $view_map[$template_name];
        }

        // Construir atributos para render_unified
        $unified_atts = [
            'module'  => $module_slug,
            'view'    => $view,
            'limit'   => $atts['limit'] ?? 12,
            'columns' => $atts['columnas'] ?? $atts['columns'] ?? 3,
            'id'      => $atts['id'] ?? '',
        ];

        return $this->render_unified($unified_atts);
    }

    /**
     * Intenta renderizar un template específico del módulo
     *
     * @param string $module_slug Slug del módulo
     * @param string $template_name Nombre del template
     * @param array $atts Atributos del shortcode
     * @return string|null HTML renderizado o null si no existe
     */
    private function try_render_module_template($module_slug, $template_name, $atts) {
        // Mapeo de nombres de template a archivos específicos
        $template_files_map = [
            'presupuestos-participativos' => [
                'votar' => 'interfaz-votacion.php',
                'votaciones' => 'interfaz-votacion.php',
                'resultados' => 'resultados.php',
                'mis-propuestas' => 'mis-propuestas.php',
                'listado' => 'listado-proyectos.php',
                'proyectos' => 'listado-proyectos.php',
                'proponer' => 'formulario-propuesta.php',
                'fases' => 'dashboard.php',
                'seguimiento' => 'dashboard.php',
            ],
        ];

        // Verificar si hay mapeo para este módulo y template
        if (!isset($template_files_map[$module_slug][$template_name])) {
            return null;
        }

        $template_file = $template_files_map[$module_slug][$template_name];
        $template_path = FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/views/{$template_file}";

        if (!file_exists($template_path)) {
            return null;
        }

        // Obtener datos necesarios para el template
        $data = $this->get_template_data($module_slug, $template_name, $atts);

        // Extraer variables para el template
        extract($data);
        $atributos = $atts;

        ob_start();
        include $template_path;
        $content = ob_get_clean();

        return '<div class="flavor-shortcode-wrapper flavor-' . esc_attr($module_slug) . '-' . esc_attr($template_name) . '">' . $content . '</div>';
    }

    /**
     * Obtiene los datos necesarios para un template específico
     */
    private function get_template_data($module_slug, $template_name, $atts) {
        global $wpdb;
        $data = [];

        switch ($module_slug) {
            case 'presupuestos-participativos':
                $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
                $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

                // Obtener edición activa
                $edicion = $wpdb->get_row(
                    "SELECT * FROM {$tabla_ediciones} WHERE estado = 'activa' ORDER BY anio DESC LIMIT 1",
                    ARRAY_A
                );

                if (!$edicion) {
                    $edicion = [
                        'id' => 0,
                        'anio' => date('Y'),
                        'presupuesto_total' => 100000,
                        'fase_actual' => 'cerrada',
                    ];
                }

                $data['edicion'] = $edicion;
                $edicion_id = $edicion['id'] ?? 0;

                switch ($template_name) {
                    case 'votar':
                    case 'votaciones':
                        // Proyectos en fase de votación
                        $data['proyectos'] = $wpdb->get_results(
                            "SELECT * FROM {$tabla_proyectos}
                             WHERE estado IN ('validado', 'en_votacion')
                             ORDER BY votos_recibidos DESC, fecha_creacion DESC",
                            ARRAY_A
                        ) ?: [];
                        $data['fase_actual'] = $edicion['fase_actual'] ?? 'cerrada';

                        // Datos de votación del usuario
                        $user_id = get_current_user_id();
                        $data['votos_maximos'] = 3; // Por defecto
                        $data['votos_usuario'] = [];
                        $data['votos_restantes'] = $data['votos_maximos'];

                        if ($user_id) {
                            $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
                            $votos = $wpdb->get_col($wpdb->prepare(
                                "SELECT proyecto_id FROM {$tabla_votos} WHERE user_id = %d",
                                $user_id
                            ));
                            $data['votos_usuario'] = $votos ?: [];
                            $data['votos_restantes'] = max(0, $data['votos_maximos'] - count($data['votos_usuario']));
                        }

                        // Categorías
                        $data['categorias'] = [
                            'infraestructura' => __('Infraestructura', 'flavor-chat-ia'),
                            'medio_ambiente' => __('Medio Ambiente', 'flavor-chat-ia'),
                            'cultura' => __('Cultura y Ocio', 'flavor-chat-ia'),
                            'deporte' => __('Deporte', 'flavor-chat-ia'),
                            'social' => __('Social', 'flavor-chat-ia'),
                            'educacion' => __('Educación', 'flavor-chat-ia'),
                            'accesibilidad' => __('Accesibilidad', 'flavor-chat-ia'),
                        ];
                        break;

                    case 'resultados':
                        // Ranking de proyectos
                        $data['proyectos_ranking'] = $wpdb->get_results(
                            "SELECT * FROM {$tabla_proyectos}
                             WHERE estado NOT IN ('borrador', 'rechazado')
                             ORDER BY votos_recibidos DESC, fecha_creacion ASC",
                            ARRAY_A
                        ) ?: [];
                        $data['total_votantes'] = $wpdb->get_var(
                            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}flavor_pp_votos"
                        ) ?: 0;
                        $data['total_proyectos'] = count($data['proyectos_ranking']);
                        break;

                    case 'mis-propuestas':
                        $user_id = get_current_user_id();
                        $data['propuestas'] = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT * FROM {$tabla_proyectos}
                                 WHERE proponente_id = %d
                                 ORDER BY fecha_creacion DESC",
                                $user_id
                            ),
                            ARRAY_A
                        ) ?: [];
                        break;

                    case 'listado':
                    case 'proyectos':
                        $data['proyectos'] = $wpdb->get_results(
                            "SELECT * FROM {$tabla_proyectos}
                             WHERE estado NOT IN ('borrador', 'rechazado')
                             ORDER BY votos_recibidos DESC, fecha_creacion DESC",
                            ARRAY_A
                        ) ?: [];
                        break;

                    case 'fases':
                    case 'seguimiento':
                        // Para dashboard/fases, pasar datos generales
                        $data['proyectos'] = $wpdb->get_results(
                            "SELECT * FROM {$tabla_proyectos} ORDER BY votos_recibidos DESC",
                            ARRAY_A
                        ) ?: [];
                        $data['stats'] = [
                            'total' => count($data['proyectos']),
                            'validados' => 0,
                            'en_votacion' => 0,
                            'seleccionados' => 0,
                        ];
                        foreach ($data['proyectos'] as $proyecto) {
                            $estado = $proyecto['estado'] ?? '';
                            if ($estado === 'validado') $data['stats']['validados']++;
                            if ($estado === 'en_votacion') $data['stats']['en_votacion']++;
                            if (in_array($estado, ['seleccionado', 'en_ejecucion', 'ejecutado'])) $data['stats']['seleccionados']++;
                        }
                        break;
                }
                break;
        }

        return $data;
    }

    /**
     * Renderiza usando Archive Renderer
     */
    private function render_with_archive_renderer($module_slug, $type, $atts) {
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }

        $renderer = new Flavor_Archive_Renderer();
        $config = [
            'per_page' => intval($atts['limit'] ?? 12),
            'columns' => intval($atts['columnas'] ?? 3),
        ];

        // Añadir filtro de usuario para vistas personales
        if ($type === 'user_items' && !empty($atts['user_filter'])) {
            $config['user_id'] = get_current_user_id();
            $config['show_header'] = false; // Sin header grande para vistas personales
            $config['empty_title'] = __('No tienes registros aún', 'flavor-chat-ia');
            $config['empty_text'] = __('Cuando tengas actividad, aparecerá aquí.', 'flavor-chat-ia');
        }

        if ($type === 'single' && !empty($atts['id'])) {
            return '<div class="flavor-shortcode-wrapper flavor-' . esc_attr($module_slug) . '-single">'
                . $renderer->render_single_auto($module_slug, intval($atts['id']), $config)
                . '</div>';
        }

        return '<div class="flavor-shortcode-wrapper flavor-' . esc_attr($module_slug) . '-archive">'
            . $renderer->render_auto($module_slug, $config)
            . '</div>';
    }

    /**
     * Renderiza un formulario de módulo automáticamente
     */
    private function render_module_form_auto($module_slug, $template_name, $atts) {
        // Verificar login
        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }

        // Obtener configuración de formulario del módulo
        $form_config = $this->get_module_form_config($module_slug, $template_name);

        if (empty($form_config) || empty($form_config['fields'])) {
            // Si no hay configuración, intentar generar una básica
            $form_config = $this->generate_basic_form_config($module_slug, $template_name);
        }

        ob_start();
        $args = $form_config;
        $args['module'] = $module_slug;
        $args['action'] = $template_name;
        include FLAVOR_CHAT_IA_PATH . 'templates/components/shared/form-builder.php';
        return '<div class="flavor-shortcode-wrapper flavor-' . esc_attr($module_slug) . '-form">' . ob_get_clean() . '</div>';
    }

    /**
     * Obtiene la configuración de formulario de un módulo
     */
    private function get_module_form_config($module_slug, $action) {
        $configs = $this->get_all_form_configs();
        return $configs[$module_slug][$action] ?? [];
    }

    /**
     * Genera configuración básica de formulario
     */
    private function generate_basic_form_config($module_slug, $action) {
        $module_name = ucwords(str_replace('-', ' ', $module_slug));
        $action_labels = [
            'crear' => __('Crear nuevo', 'flavor-chat-ia'),
            'formulario' => __('Nuevo registro', 'flavor-chat-ia'),
            'registrar' => __('Registrar', 'flavor-chat-ia'),
            'proponer' => __('Nueva propuesta', 'flavor-chat-ia'),
            'reportar' => __('Reportar', 'flavor-chat-ia'),
            'crear-tema' => __('Nuevo tema', 'flavor-chat-ia'),
            'formulario-suscripcion' => __('Suscribirse', 'flavor-chat-ia'),
        ];

        $icons = [
            'incidencias' => '⚠️',
            'marketplace' => '🛒',
            'eventos' => '📅',
            'participacion' => '🗳️',
            'talleres' => '🎓',
            'compostaje' => '🌱',
            'foros' => '💬',
            'encuestas' => '📊',
            'comunidades' => '👥',
            'recetas' => '🍳',
            'facturas' => '📄',
        ];

        return [
            'title' => $action_labels[$action] ?? __('Nuevo registro', 'flavor-chat-ia'),
            'subtitle' => $module_name,
            'icon' => $icons[$module_slug] ?? '📝',
            'color' => 'primary',
            'fields' => [
                [
                    'name' => 'titulo',
                    'type' => 'text',
                    'label' => __('Título', 'flavor-chat-ia'),
                    'placeholder' => __('Escribe un título descriptivo', 'flavor-chat-ia'),
                    'required' => true,
                ],
                [
                    'name' => 'descripcion',
                    'type' => 'textarea',
                    'label' => __('Descripción', 'flavor-chat-ia'),
                    'placeholder' => __('Describe con detalle...', 'flavor-chat-ia'),
                    'required' => true,
                    'rows' => 5,
                ],
            ],
            'submit_text' => __('Enviar', 'flavor-chat-ia'),
            'cancel_url' => home_url('/' . $module_slug . '/'),
        ];
    }

    /**
     * Configuraciones de formularios por módulo
     */
    private function get_all_form_configs() {
        return [
            'incidencias' => [
                'reportar' => [
                    'title' => __('Reportar Incidencia', 'flavor-chat-ia'),
                    'subtitle' => __('Informa de un problema en tu barrio', 'flavor-chat-ia'),
                    'icon' => '⚠️',
                    'color' => 'error',
                    'fields' => [
                        ['name' => 'titulo', 'type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'required' => true, 'placeholder' => __('Ej: Farola rota en Calle Mayor', 'flavor-chat-ia')],
                        ['name' => 'categoria', 'type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'required' => true, 'options' => [
                            'alumbrado' => __('Alumbrado', 'flavor-chat-ia'),
                            'limpieza' => __('Limpieza', 'flavor-chat-ia'),
                            'vias' => __('Vías y aceras', 'flavor-chat-ia'),
                            'mobiliario' => __('Mobiliario urbano', 'flavor-chat-ia'),
                            'zonas_verdes' => __('Zonas verdes', 'flavor-chat-ia'),
                            'otro' => __('Otro', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'descripcion', 'type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true, 'rows' => 4],
                        ['name' => 'ubicacion', 'type' => 'text', 'label' => __('Ubicación', 'flavor-chat-ia'), 'required' => true, 'placeholder' => __('Calle, número, referencias...', 'flavor-chat-ia')],
                        ['name' => 'imagen', 'type' => 'file', 'label' => __('Foto (opcional)', 'flavor-chat-ia'), 'accept' => 'image/*'],
                        ['name' => 'urgencia', 'type' => 'select', 'label' => __('Urgencia', 'flavor-chat-ia'), 'options' => [
                            'baja' => __('Baja', 'flavor-chat-ia'),
                            'media' => __('Media', 'flavor-chat-ia'),
                            'alta' => __('Alta', 'flavor-chat-ia'),
                        ]],
                    ],
                    'submit_text' => __('Enviar reporte', 'flavor-chat-ia'),
                ],
            ],
            'marketplace' => [
                'formulario' => [
                    'title' => __('Publicar Anuncio', 'flavor-chat-ia'),
                    'subtitle' => __('Vende o intercambia productos localmente', 'flavor-chat-ia'),
                    'icon' => '🛒',
                    'color' => 'success',
                    'fields' => [
                        ['name' => 'titulo', 'type' => 'text', 'label' => __('Título del anuncio', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'categoria', 'type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'required' => true, 'options' => [
                            'electronica' => __('Electrónica', 'flavor-chat-ia'),
                            'hogar' => __('Hogar y jardín', 'flavor-chat-ia'),
                            'moda' => __('Moda y accesorios', 'flavor-chat-ia'),
                            'deportes' => __('Deportes', 'flavor-chat-ia'),
                            'libros' => __('Libros y cultura', 'flavor-chat-ia'),
                            'infantil' => __('Infantil', 'flavor-chat-ia'),
                            'servicios' => __('Servicios', 'flavor-chat-ia'),
                            'otro' => __('Otros', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'descripcion', 'type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true, 'rows' => 4],
                        ['name' => 'precio', 'type' => 'number', 'label' => __('Precio (€)', 'flavor-chat-ia'), 'min' => '0', 'step' => '0.01', 'placeholder' => '0 = Gratis'],
                        ['name' => 'condicion', 'type' => 'select', 'label' => __('Estado', 'flavor-chat-ia'), 'options' => [
                            'nuevo' => __('Nuevo', 'flavor-chat-ia'),
                            'como_nuevo' => __('Como nuevo', 'flavor-chat-ia'),
                            'buen_estado' => __('Buen estado', 'flavor-chat-ia'),
                            'usado' => __('Usado', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'imagenes', 'type' => 'file', 'label' => __('Fotos', 'flavor-chat-ia'), 'accept' => 'image/*'],
                        ['name' => 'contacto', 'type' => 'text', 'label' => __('Forma de contacto', 'flavor-chat-ia'), 'placeholder' => __('Teléfono, email, etc.', 'flavor-chat-ia')],
                    ],
                    'submit_text' => __('Publicar anuncio', 'flavor-chat-ia'),
                ],
            ],
            'participacion' => [
                'crear' => [
                    'title' => __('Nueva Propuesta', 'flavor-chat-ia'),
                    'subtitle' => __('Propón mejoras para tu comunidad', 'flavor-chat-ia'),
                    'icon' => '🗳️',
                    'color' => 'primary',
                    'fields' => [
                        ['name' => 'titulo', 'type' => 'text', 'label' => __('Título de la propuesta', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'categoria', 'type' => 'select', 'label' => __('Área', 'flavor-chat-ia'), 'required' => true, 'options' => [
                            'urbanismo' => __('Urbanismo', 'flavor-chat-ia'),
                            'medio_ambiente' => __('Medio ambiente', 'flavor-chat-ia'),
                            'cultura' => __('Cultura y ocio', 'flavor-chat-ia'),
                            'servicios' => __('Servicios públicos', 'flavor-chat-ia'),
                            'movilidad' => __('Movilidad', 'flavor-chat-ia'),
                            'social' => __('Bienestar social', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'descripcion', 'type' => 'textarea', 'label' => __('Descripción detallada', 'flavor-chat-ia'), 'required' => true, 'rows' => 6],
                        ['name' => 'beneficios', 'type' => 'textarea', 'label' => __('Beneficios esperados', 'flavor-chat-ia'), 'rows' => 3],
                        ['name' => 'presupuesto_estimado', 'type' => 'number', 'label' => __('Presupuesto estimado (€)', 'flavor-chat-ia'), 'min' => '0'],
                        ['name' => 'ubicacion', 'type' => 'text', 'label' => __('Ubicación (si aplica)', 'flavor-chat-ia')],
                    ],
                    'submit_text' => __('Enviar propuesta', 'flavor-chat-ia'),
                ],
            ],
            'talleres' => [
                'proponer' => [
                    'title' => __('Proponer Taller', 'flavor-chat-ia'),
                    'subtitle' => __('Comparte tus conocimientos', 'flavor-chat-ia'),
                    'icon' => '🎓',
                    'color' => 'primary',
                    'fields' => [
                        ['name' => 'titulo', 'type' => 'text', 'label' => __('Nombre del taller', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'categoria', 'type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'required' => true, 'options' => [
                            'manualidades' => __('Manualidades', 'flavor-chat-ia'),
                            'cocina' => __('Cocina', 'flavor-chat-ia'),
                            'tecnologia' => __('Tecnología', 'flavor-chat-ia'),
                            'idiomas' => __('Idiomas', 'flavor-chat-ia'),
                            'arte' => __('Arte', 'flavor-chat-ia'),
                            'bienestar' => __('Bienestar', 'flavor-chat-ia'),
                            'otro' => __('Otro', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'descripcion', 'type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true, 'rows' => 4],
                        ['name' => 'duracion', 'type' => 'text', 'label' => __('Duración estimada', 'flavor-chat-ia'), 'placeholder' => __('Ej: 2 horas', 'flavor-chat-ia')],
                        ['name' => 'materiales', 'type' => 'textarea', 'label' => __('Materiales necesarios', 'flavor-chat-ia'), 'rows' => 2],
                        ['name' => 'nivel', 'type' => 'select', 'label' => __('Nivel', 'flavor-chat-ia'), 'options' => [
                            'principiante' => __('Principiante', 'flavor-chat-ia'),
                            'intermedio' => __('Intermedio', 'flavor-chat-ia'),
                            'avanzado' => __('Avanzado', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'plazas', 'type' => 'number', 'label' => __('Plazas máximas', 'flavor-chat-ia'), 'min' => '1', 'max' => '50'],
                    ],
                    'submit_text' => __('Proponer taller', 'flavor-chat-ia'),
                ],
            ],
            'compostaje' => [
                'registrar' => [
                    'title' => __('Registrar Aportación', 'flavor-chat-ia'),
                    'subtitle' => __('Suma tu contribución al compostaje comunitario', 'flavor-chat-ia'),
                    'icon' => '🌱',
                    'color' => 'success',
                    'fields' => [
                        ['name' => 'compostera_id', 'type' => 'select', 'label' => __('Compostera', 'flavor-chat-ia'), 'required' => true, 'options' => []],
                        ['name' => 'tipo_residuo', 'type' => 'select', 'label' => __('Tipo de residuo', 'flavor-chat-ia'), 'required' => true, 'options' => [
                            'verde' => __('Verde (restos vegetales frescos)', 'flavor-chat-ia'),
                            'marron' => __('Marrón (hojas secas, cartón)', 'flavor-chat-ia'),
                            'mixto' => __('Mixto', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'cantidad', 'type' => 'number', 'label' => __('Cantidad (kg aprox)', 'flavor-chat-ia'), 'required' => true, 'min' => '0.1', 'step' => '0.1'],
                        ['name' => 'notas', 'type' => 'textarea', 'label' => __('Notas (opcional)', 'flavor-chat-ia'), 'rows' => 2],
                    ],
                    'submit_text' => __('Registrar', 'flavor-chat-ia'),
                ],
            ],
            'foros' => [
                'crear-tema' => [
                    'title' => __('Nuevo Tema', 'flavor-chat-ia'),
                    'subtitle' => __('Inicia una conversación', 'flavor-chat-ia'),
                    'icon' => '💬',
                    'color' => 'primary',
                    'fields' => [
                        ['name' => 'titulo', 'type' => 'text', 'label' => __('Título del tema', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'categoria', 'type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'required' => true, 'options' => [
                            'general' => __('General', 'flavor-chat-ia'),
                            'anuncios' => __('Anuncios', 'flavor-chat-ia'),
                            'ayuda' => __('Ayuda', 'flavor-chat-ia'),
                            'propuestas' => __('Propuestas', 'flavor-chat-ia'),
                            'off_topic' => __('Off-topic', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'contenido', 'type' => 'textarea', 'label' => __('Mensaje', 'flavor-chat-ia'), 'required' => true, 'rows' => 8],
                        ['name' => 'etiquetas', 'type' => 'text', 'label' => __('Etiquetas', 'flavor-chat-ia'), 'placeholder' => __('Separadas por comas', 'flavor-chat-ia')],
                    ],
                    'submit_text' => __('Publicar tema', 'flavor-chat-ia'),
                ],
            ],
            'encuestas' => [
                'crear' => [
                    'title' => __('Crear Encuesta', 'flavor-chat-ia'),
                    'subtitle' => __('Pregunta a tu comunidad', 'flavor-chat-ia'),
                    'icon' => '📊',
                    'color' => 'accent',
                    'fields' => [
                        ['name' => 'titulo', 'type' => 'text', 'label' => __('Pregunta principal', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'descripcion', 'type' => 'textarea', 'label' => __('Descripción (opcional)', 'flavor-chat-ia'), 'rows' => 2],
                        ['name' => 'tipo', 'type' => 'select', 'label' => __('Tipo de respuesta', 'flavor-chat-ia'), 'options' => [
                            'simple' => __('Opción única', 'flavor-chat-ia'),
                            'multiple' => __('Opción múltiple', 'flavor-chat-ia'),
                            'escala' => __('Escala 1-5', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'opciones', 'type' => 'textarea', 'label' => __('Opciones (una por línea)', 'flavor-chat-ia'), 'required' => true, 'rows' => 4, 'placeholder' => __("Opción 1\nOpción 2\nOpción 3", 'flavor-chat-ia')],
                        ['name' => 'fecha_fin', 'type' => 'date', 'label' => __('Fecha límite', 'flavor-chat-ia'), 'min' => date('Y-m-d')],
                        ['name' => 'anonima', 'type' => 'checkbox', 'label' => '', 'placeholder' => __('Respuestas anónimas', 'flavor-chat-ia')],
                    ],
                    'submit_text' => __('Crear encuesta', 'flavor-chat-ia'),
                ],
            ],
            'email-marketing' => [
                'formulario-suscripcion' => [
                    'title' => __('Suscríbete', 'flavor-chat-ia'),
                    'subtitle' => __('Recibe las últimas novedades', 'flavor-chat-ia'),
                    'icon' => '📧',
                    'color' => 'primary',
                    'require_login' => false,
                    'fields' => [
                        ['name' => 'email', 'type' => 'email', 'label' => __('Email', 'flavor-chat-ia'), 'required' => true, 'placeholder' => __('tu@email.com', 'flavor-chat-ia')],
                        ['name' => 'nombre', 'type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia'), 'placeholder' => __('Tu nombre', 'flavor-chat-ia')],
                        ['name' => 'acepto', 'type' => 'checkbox', 'label' => '', 'placeholder' => __('Acepto recibir comunicaciones', 'flavor-chat-ia'), 'required' => true],
                    ],
                    'submit_text' => __('Suscribirme', 'flavor-chat-ia'),
                ],
            ],
            'recetas' => [
                'formulario' => [
                    'title' => __('Compartir Receta', 'flavor-chat-ia'),
                    'subtitle' => __('Comparte tus recetas favoritas', 'flavor-chat-ia'),
                    'icon' => '🍳',
                    'color' => 'warning',
                    'fields' => [
                        ['name' => 'titulo', 'type' => 'text', 'label' => __('Nombre de la receta', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'categoria', 'type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'options' => [
                            'entrantes' => __('Entrantes', 'flavor-chat-ia'),
                            'principales' => __('Platos principales', 'flavor-chat-ia'),
                            'postres' => __('Postres', 'flavor-chat-ia'),
                            'bebidas' => __('Bebidas', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'ingredientes', 'type' => 'textarea', 'label' => __('Ingredientes', 'flavor-chat-ia'), 'required' => true, 'rows' => 4, 'placeholder' => __('Un ingrediente por línea', 'flavor-chat-ia')],
                        ['name' => 'preparacion', 'type' => 'textarea', 'label' => __('Preparación', 'flavor-chat-ia'), 'required' => true, 'rows' => 6],
                        ['name' => 'tiempo', 'type' => 'text', 'label' => __('Tiempo de preparación', 'flavor-chat-ia'), 'placeholder' => __('Ej: 30 minutos', 'flavor-chat-ia')],
                        ['name' => 'porciones', 'type' => 'number', 'label' => __('Porciones', 'flavor-chat-ia'), 'min' => '1'],
                        ['name' => 'imagen', 'type' => 'file', 'label' => __('Foto', 'flavor-chat-ia'), 'accept' => 'image/*'],
                    ],
                    'submit_text' => __('Publicar receta', 'flavor-chat-ia'),
                ],
            ],
            'comunidades' => [
                'crear' => [
                    'title' => __('Crear Comunidad', 'flavor-chat-ia'),
                    'subtitle' => __('Crea un espacio para tu grupo', 'flavor-chat-ia'),
                    'icon' => '👥',
                    'color' => 'primary',
                    'fields' => [
                        ['name' => 'nombre', 'type' => 'text', 'label' => __('Nombre de la comunidad', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'descripcion', 'type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true, 'rows' => 4],
                        ['name' => 'tipo', 'type' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'options' => [
                            'publica' => __('Pública', 'flavor-chat-ia'),
                            'privada' => __('Privada', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'imagen', 'type' => 'file', 'label' => __('Imagen de portada', 'flavor-chat-ia'), 'accept' => 'image/*'],
                    ],
                    'submit_text' => __('Crear comunidad', 'flavor-chat-ia'),
                ],
            ],
            'facturas' => [
                'crear' => [
                    'title' => __('Nueva Factura', 'flavor-chat-ia'),
                    'subtitle' => __('Genera una factura', 'flavor-chat-ia'),
                    'icon' => '📄',
                    'color' => 'secondary',
                    'fields' => [
                        ['name' => 'cliente', 'type' => 'text', 'label' => __('Cliente', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'concepto', 'type' => 'text', 'label' => __('Concepto', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'cantidad', 'type' => 'number', 'label' => __('Importe (€)', 'flavor-chat-ia'), 'required' => true, 'min' => '0', 'step' => '0.01'],
                        ['name' => 'fecha', 'type' => 'date', 'label' => __('Fecha', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'notas', 'type' => 'textarea', 'label' => __('Notas', 'flavor-chat-ia'), 'rows' => 2],
                    ],
                    'submit_text' => __('Generar factura', 'flavor-chat-ia'),
                ],
            ],
            'seguimiento-denuncias' => [
                'formulario' => [
                    'title' => __('Nueva Denuncia', 'flavor-chat-ia'),
                    'subtitle' => __('Reporta una situación', 'flavor-chat-ia'),
                    'icon' => '🚨',
                    'color' => 'error',
                    'fields' => [
                        ['name' => 'tipo', 'type' => 'select', 'label' => __('Tipo de denuncia', 'flavor-chat-ia'), 'required' => true, 'options' => [
                            'ruido' => __('Ruido', 'flavor-chat-ia'),
                            'suciedad' => __('Suciedad', 'flavor-chat-ia'),
                            'vandalismo' => __('Vandalismo', 'flavor-chat-ia'),
                            'otro' => __('Otro', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'descripcion', 'type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true, 'rows' => 4],
                        ['name' => 'ubicacion', 'type' => 'text', 'label' => __('Ubicación', 'flavor-chat-ia'), 'required' => true],
                        ['name' => 'fecha_incidente', 'type' => 'datetime', 'label' => __('Fecha y hora del incidente', 'flavor-chat-ia')],
                        ['name' => 'evidencia', 'type' => 'file', 'label' => __('Evidencia (foto/video)', 'flavor-chat-ia'), 'accept' => 'image/*,video/*'],
                        ['name' => 'anonimo', 'type' => 'checkbox', 'label' => '', 'placeholder' => __('Denuncia anónima', 'flavor-chat-ia')],
                    ],
                    'submit_text' => __('Enviar denuncia', 'flavor-chat-ia'),
                ],
            ],
            'chat-estados' => [
                'crear' => [
                    'title' => __('Crear Estado', 'flavor-chat-ia'),
                    'subtitle' => __('Comparte un momento', 'flavor-chat-ia'),
                    'icon' => '📷',
                    'color' => 'accent',
                    'fields' => [
                        ['name' => 'media', 'type' => 'file', 'label' => __('Foto o video', 'flavor-chat-ia'), 'required' => true, 'accept' => 'image/*,video/*'],
                        ['name' => 'texto', 'type' => 'textarea', 'label' => __('Texto (opcional)', 'flavor-chat-ia'), 'rows' => 2, 'placeholder' => __('Añade un mensaje...', 'flavor-chat-ia')],
                        ['name' => 'privacidad', 'type' => 'select', 'label' => __('Quién puede ver', 'flavor-chat-ia'), 'options' => [
                            'todos' => __('Todos', 'flavor-chat-ia'),
                            'contactos' => __('Solo contactos', 'flavor-chat-ia'),
                        ]],
                    ],
                    'submit_text' => __('Publicar estado', 'flavor-chat-ia'),
                ],
            ],
            'fichaje-empleados' => [
                'fichar' => [
                    'title' => __('Fichar', 'flavor-chat-ia'),
                    'subtitle' => __('Registra tu entrada o salida', 'flavor-chat-ia'),
                    'icon' => '⏱️',
                    'color' => 'primary',
                    'fields' => [
                        ['name' => 'tipo', 'type' => 'select', 'label' => __('Tipo de fichaje', 'flavor-chat-ia'), 'required' => true, 'options' => [
                            'entrada' => __('Entrada', 'flavor-chat-ia'),
                            'salida' => __('Salida', 'flavor-chat-ia'),
                            'pausa_inicio' => __('Inicio de pausa', 'flavor-chat-ia'),
                            'pausa_fin' => __('Fin de pausa', 'flavor-chat-ia'),
                        ]],
                        ['name' => 'notas', 'type' => 'textarea', 'label' => __('Notas (opcional)', 'flavor-chat-ia'), 'rows' => 2, 'placeholder' => __('Comentarios adicionales...', 'flavor-chat-ia')],
                    ],
                    'submit_text' => __('Registrar fichaje', 'flavor-chat-ia'),
                ],
                'boton' => [
                    'title' => __('Fichar ahora', 'flavor-chat-ia'),
                    'subtitle' => '',
                    'icon' => '⏱️',
                    'color' => 'primary',
                    'fields' => [],
                    'submit_text' => __('Fichar', 'flavor-chat-ia'),
                ],
            ],
            'themacle' => [
                'formulario' => [
                    'title' => __('Configurar Tema', 'flavor-chat-ia'),
                    'subtitle' => __('Personaliza la apariencia', 'flavor-chat-ia'),
                    'icon' => '🎨',
                    'color' => 'primary',
                    'fields' => [
                        ['name' => 'primary_color', 'type' => 'text', 'label' => __('Color primario', 'flavor-chat-ia'), 'placeholder' => '#3B82F6'],
                        ['name' => 'secondary_color', 'type' => 'text', 'label' => __('Color secundario', 'flavor-chat-ia'), 'placeholder' => '#6366F1'],
                        ['name' => 'font_family', 'type' => 'select', 'label' => __('Tipografía', 'flavor-chat-ia'), 'options' => [
                            'inter' => 'Inter',
                            'roboto' => 'Roboto',
                            'poppins' => 'Poppins',
                            'montserrat' => 'Montserrat',
                        ]],
                    ],
                    'submit_text' => __('Guardar tema', 'flavor-chat-ia'),
                ],
            ],
        ];
    }

    /**
     * Renderiza un calendario de módulo
     */
    private function render_module_calendar($module_slug, $template_name, $atts) {
        $is_mini = strpos($template_name, 'mini') !== false;

        // Obtener eventos/items del módulo
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }
        $renderer = new Flavor_Archive_Renderer();
        $data = $renderer->get_module_data($module_slug, ['per_page' => 100]);

        // Configuración del módulo
        $module_config = $renderer->get_renderer_config_for_module($module_slug);

        $title = $module_config['title'] ?? ucwords(str_replace('-', ' ', $module_slug));
        $icon = $module_config['icon'] ?? '📅';
        $color = $module_config['color'] ?? 'primary';

        // Obtener clases de gradiente
        $gradient = function_exists('flavor_get_gradient_classes') ? flavor_get_gradient_classes($color) : ['from' => 'from-blue-500', 'to' => 'to-blue-600'];
        $gradient_classes = "bg-gradient-to-r {$gradient['from']} {$gradient['to']}";

        ob_start();
        ?>
        <div class="flavor-calendar-wrapper flavor-<?php echo esc_attr($module_slug); ?>-calendario <?php echo $is_mini ? 'flavor-calendar-mini' : ''; ?>">
            <?php if (!$is_mini): ?>
            <div class="<?php echo esc_attr($gradient_classes); ?> rounded-t-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl"><?php echo esc_html($icon); ?></span>
                        <h2 class="text-2xl font-bold"><?php echo esc_html($title); ?> - <?php esc_html_e('Calendario', 'flavor-chat-ia'); ?></h2>
                    </div>
                    <div class="flex gap-2">
                        <button class="flavor-cal-prev px-3 py-1 bg-white/20 rounded-lg hover:bg-white/30 transition-colors">&larr;</button>
                        <button class="flavor-cal-today px-3 py-1 bg-white/20 rounded-lg hover:bg-white/30 transition-colors"><?php esc_html_e('Hoy', 'flavor-chat-ia'); ?></button>
                        <button class="flavor-cal-next px-3 py-1 bg-white/20 rounded-lg hover:bg-white/30 transition-colors">&rarr;</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white <?php echo $is_mini ? 'rounded-xl' : 'rounded-b-2xl'; ?> shadow-lg p-4">
                <div class="flavor-calendar-header flex items-center justify-between mb-4">
                    <h3 class="flavor-cal-month text-xl font-semibold"><?php echo esc_html(date_i18n('F Y')); ?></h3>
                    <?php if ($is_mini): ?>
                    <div class="flex gap-1">
                        <button class="flavor-cal-prev p-1 hover:bg-gray-100 rounded">&larr;</button>
                        <button class="flavor-cal-next p-1 hover:bg-gray-100 rounded">&rarr;</button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flavor-calendar-grid">
                    <!-- Días de la semana -->
                    <div class="grid grid-cols-7 gap-1 mb-2 text-center text-sm text-gray-500 font-medium">
                        <span><?php esc_html_e('Lun', 'flavor-chat-ia'); ?></span>
                        <span><?php esc_html_e('Mar', 'flavor-chat-ia'); ?></span>
                        <span><?php esc_html_e('Mié', 'flavor-chat-ia'); ?></span>
                        <span><?php esc_html_e('Jue', 'flavor-chat-ia'); ?></span>
                        <span><?php esc_html_e('Vie', 'flavor-chat-ia'); ?></span>
                        <span><?php esc_html_e('Sáb', 'flavor-chat-ia'); ?></span>
                        <span><?php esc_html_e('Dom', 'flavor-chat-ia'); ?></span>
                    </div>

                    <!-- Grid de días -->
                    <div class="flavor-cal-days grid grid-cols-7 gap-1">
                        <?php
                        $today = new DateTime();
                        $first_day = new DateTime('first day of this month');
                        $last_day = new DateTime('last day of this month');

                        // Días vacíos al inicio
                        $start_weekday = (int)$first_day->format('N') - 1;
                        for ($i = 0; $i < $start_weekday; $i++):
                        ?>
                            <div class="flavor-cal-day flavor-cal-empty p-2"></div>
                        <?php endfor;

                        // Días del mes
                        $current = clone $first_day;
                        while ($current <= $last_day):
                            $is_today = $current->format('Y-m-d') === $today->format('Y-m-d');
                            $day_class = $is_today ? 'bg-blue-600 text-white' : 'hover:bg-gray-100';

                            // Buscar eventos para este día
                            $day_events = [];
                            foreach ($data['items'] as $item) {
                                $item_date = $item['fecha'] ?? $item['date'] ?? $item['created_at'] ?? '';
                                if ($item_date && strpos($item_date, $current->format('Y-m-d')) === 0) {
                                    $day_events[] = $item;
                                }
                            }
                        ?>
                            <div class="flavor-cal-day <?php echo esc_attr($day_class); ?> p-2 text-center rounded-lg cursor-pointer relative transition-colors"
                                 data-date="<?php echo esc_attr($current->format('Y-m-d')); ?>">
                                <span class="<?php echo $is_mini ? 'text-sm' : ''; ?>"><?php echo $current->format('j'); ?></span>
                                <?php if (!empty($day_events)): ?>
                                    <span class="absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                <?php endif; ?>
                            </div>
                        <?php
                            $current->modify('+1 day');
                        endwhile;
                        ?>
                    </div>
                </div>

                <?php if (!$is_mini && !empty($data['items'])): ?>
                <!-- Lista de próximos eventos -->
                <div class="mt-6 border-t pt-4">
                    <h4 class="font-semibold mb-3"><?php esc_html_e('Próximos eventos', 'flavor-chat-ia'); ?></h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php
                        $upcoming = array_slice($data['items'], 0, 5);
                        foreach ($upcoming as $event):
                            $event_date = $event['fecha'] ?? $event['date'] ?? '';
                            $event_title = $event['titulo'] ?? $event['title'] ?? $event['nombre'] ?? '';
                        ?>
                        <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg">
                            <div class="w-12 text-center">
                                <div class="text-xs text-gray-500"><?php echo $event_date ? date_i18n('M', strtotime($event_date)) : ''; ?></div>
                                <div class="text-lg font-bold"><?php echo $event_date ? date('d', strtotime($event_date)) : ''; ?></div>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium"><?php echo esc_html($event_title); ?></div>
                                <?php if (!empty($event['hora'])): ?>
                                <div class="text-sm text-gray-500"><?php echo esc_html($event['hora']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .flavor-calendar-mini { max-width: 280px; }
        .flavor-calendar-mini .flavor-cal-day { padding: 0.25rem; font-size: 0.75rem; }
        </style>
        <?php
        return '<div class="flavor-shortcode-wrapper">' . ob_get_clean() . '</div>';
    }

    /**
     * Renderiza un mapa de módulo
     */
    private function render_module_map($module_slug, $template_name, $atts) {
        // Obtener items con ubicación
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }
        $renderer = new Flavor_Archive_Renderer();
        $data = $renderer->get_module_data($module_slug, ['per_page' => 100]);

        // Configuración del módulo
        $module_config = $renderer->get_renderer_config_for_module($module_slug);

        $title = $module_config['title'] ?? ucwords(str_replace('-', ' ', $module_slug));
        $icon = $module_config['icon'] ?? '📍';
        $color = $module_config['color'] ?? 'primary';

        // Filtrar items con coordenadas
        $markers = [];
        foreach ($data['items'] as $item) {
            $lat = $item['lat'] ?? $item['latitud'] ?? $item['latitude'] ?? null;
            $lng = $item['lng'] ?? $item['longitud'] ?? $item['longitude'] ?? null;

            if ($lat && $lng) {
                $markers[] = [
                    'id' => $item['id'] ?? 0,
                    'lat' => floatval($lat),
                    'lng' => floatval($lng),
                    'title' => $item['titulo'] ?? $item['title'] ?? $item['nombre'] ?? '',
                    'description' => $item['descripcion'] ?? $item['description'] ?? '',
                    'status' => $item['estado'] ?? $item['status'] ?? '',
                    'url' => $item['url'] ?? '',
                ];
            }
        }

        // Coordenadas por defecto (España)
        $default_lat = 40.4168;
        $default_lng = -3.7038;
        $default_zoom = 6;

        if (!empty($markers)) {
            // Centrar en el primer marcador
            $default_lat = $markers[0]['lat'];
            $default_lng = $markers[0]['lng'];
            $default_zoom = 13;
        }

        $map_id = 'flavor-map-' . $module_slug . '-' . wp_rand(1000, 9999);

        // Obtener clases de gradiente
        $gradient = function_exists('flavor_get_gradient_classes') ? flavor_get_gradient_classes($color) : ['from' => 'from-blue-500', 'to' => 'to-blue-600'];
        $gradient_classes = "bg-gradient-to-r {$gradient['from']} {$gradient['to']}";

        ob_start();
        ?>
        <div class="flavor-map-wrapper flavor-<?php echo esc_attr($module_slug); ?>-mapa">
            <!-- Header -->
            <div class="<?php echo esc_attr($gradient_classes); ?> rounded-t-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl"><?php echo esc_html($icon); ?></span>
                        <div>
                            <h2 class="text-2xl font-bold"><?php echo esc_html($title); ?></h2>
                            <p class="text-white/80"><?php echo sprintf(__('%d ubicaciones', 'flavor-chat-ia'), count($markers)); ?></p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="flavorMapLocateMe('<?php echo esc_js($map_id); ?>')"
                                class="px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition-colors flex items-center gap-2">
                            <span>📍</span>
                            <span><?php esc_html_e('Mi ubicación', 'flavor-chat-ia'); ?></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mapa -->
            <div class="bg-white rounded-b-2xl shadow-lg overflow-hidden">
                <div id="<?php echo esc_attr($map_id); ?>" class="h-96 w-full"></div>

                <?php if (!empty($markers)): ?>
                <!-- Lista de ubicaciones -->
                <div class="p-4 border-t max-h-64 overflow-y-auto">
                    <h4 class="font-semibold mb-3"><?php esc_html_e('Ubicaciones', 'flavor-chat-ia'); ?></h4>
                    <div class="space-y-2">
                        <?php foreach (array_slice($markers, 0, 10) as $marker): ?>
                        <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer"
                             onclick="flavorMapGoTo('<?php echo esc_js($map_id); ?>', <?php echo esc_attr($marker['lat']); ?>, <?php echo esc_attr($marker['lng']); ?>)">
                            <span class="text-2xl">📍</span>
                            <div class="flex-1">
                                <div class="font-medium"><?php echo esc_html($marker['title']); ?></div>
                                <?php if ($marker['status']): ?>
                                <span class="text-xs px-2 py-0.5 bg-gray-100 rounded-full"><?php echo esc_html($marker['status']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($marker['url']): ?>
                            <a href="<?php echo esc_url($marker['url']); ?>" class="text-blue-600 hover:underline text-sm">
                                <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
        (function() {
            const mapId = '<?php echo esc_js($map_id); ?>';
            const markers = <?php echo json_encode($markers); ?>;
            const defaultLat = <?php echo esc_js($default_lat); ?>;
            const defaultLng = <?php echo esc_js($default_lng); ?>;
            const defaultZoom = <?php echo esc_js($default_zoom); ?>;

            // Inicializar mapa
            const map = L.map(mapId).setView([defaultLat, defaultLng], defaultZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Añadir marcadores
            const markerLayer = L.layerGroup().addTo(map);
            markers.forEach(m => {
                const marker = L.marker([m.lat, m.lng]).addTo(markerLayer);
                let popupContent = '<strong>' + m.title + '</strong>';
                if (m.description) popupContent += '<br>' + m.description;
                if (m.url) popupContent += '<br><a href="' + m.url + '">Ver detalles</a>';
                marker.bindPopup(popupContent);
            });

            // Ajustar vista a todos los marcadores
            if (markers.length > 1) {
                const bounds = L.latLngBounds(markers.map(m => [m.lat, m.lng]));
                map.fitBounds(bounds, { padding: [20, 20] });
            }

            // Funciones globales
            window.flavorMapLocateMe = function(id) {
                if (id !== mapId) return;
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(pos => {
                        map.setView([pos.coords.latitude, pos.coords.longitude], 15);
                        L.marker([pos.coords.latitude, pos.coords.longitude])
                            .addTo(map)
                            .bindPopup('<?php echo esc_js(__('Tu ubicación', 'flavor-chat-ia')); ?>')
                            .openPopup();
                    });
                }
            };

            window.flavorMapGoTo = function(id, lat, lng) {
                if (id !== mapId) return;
                map.setView([lat, lng], 16);
            };
        })();
        </script>
        <?php
        return '<div class="flavor-shortcode-wrapper">' . ob_get_clean() . '</div>';
    }

    /**
     * Renderiza un informe/resumen de módulo
     */
    private function render_module_report($module_slug, $template_name, $atts) {
        // Verificar login
        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }

        // Obtener datos del módulo
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }
        $renderer = new Flavor_Archive_Renderer();
        $user_id = get_current_user_id();
        $data = $renderer->get_module_data($module_slug, ['user_id' => $user_id, 'per_page' => 50]);

        // Configuración del módulo
        $module_config = $renderer->get_renderer_config_for_module($module_slug);

        $title = $module_config['title'] ?? ucwords(str_replace('-', ' ', $module_slug));
        $icon = $module_config['icon'] ?? '📊';
        $color = $module_config['color'] ?? 'primary';

        // Configuraciones específicas por tipo de reporte
        $report_titles = [
            'informe' => __('Informe', 'flavor-chat-ia'),
            'resumen' => __('Resumen', 'flavor-chat-ia'),
            'estadisticas' => __('Estadísticas', 'flavor-chat-ia'),
            'ranking' => __('Ranking', 'flavor-chat-ia'),
            'dashboard' => __('Panel', 'flavor-chat-ia'),
        ];

        $report_title = ($report_titles[$template_name] ?? __('Informe', 'flavor-chat-ia')) . ' - ' . $title;

        // Obtener clases de gradiente
        $gradient = function_exists('flavor_get_gradient_classes') ? flavor_get_gradient_classes($color) : ['from' => 'from-blue-500', 'to' => 'to-blue-600'];
        $gradient_classes = "bg-gradient-to-r {$gradient['from']} {$gradient['to']}";

        ob_start();
        ?>
        <div class="flavor-report-wrapper flavor-<?php echo esc_attr($module_slug); ?>-<?php echo esc_attr($template_name); ?>">
            <!-- Header -->
            <div class="<?php echo esc_attr($gradient_classes); ?> rounded-t-2xl p-6 text-white">
                <div class="flex items-center gap-3">
                    <span class="text-3xl"><?php echo esc_html($icon); ?></span>
                    <div>
                        <h2 class="text-2xl font-bold"><?php echo esc_html($report_title); ?></h2>
                        <p class="text-white/80"><?php echo sprintf(__('Datos actualizados al %s', 'flavor-chat-ia'), date_i18n(get_option('date_format'))); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-b-2xl shadow-lg p-6">
                <!-- Stats Grid -->
                <?php if (!empty($data['stats'])): ?>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <?php foreach ($data['stats'] as $stat): ?>
                    <div class="bg-gray-50 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-gray-800"><?php echo esc_html($stat['value'] ?? 0); ?></div>
                        <div class="text-sm text-gray-500 mt-1"><?php echo esc_html($stat['label'] ?? ''); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($template_name === 'ranking' && !empty($data['items'])): ?>
                <!-- Ranking List -->
                <div class="space-y-2">
                    <?php
                    $position = 1;
                    foreach (array_slice($data['items'], 0, 10) as $item):
                        $medals = ['🥇', '🥈', '🥉'];
                        $medal = $position <= 3 ? $medals[$position - 1] : $position . 'º';
                    ?>
                    <div class="flex items-center gap-4 p-3 <?php echo $position <= 3 ? 'bg-yellow-50' : 'bg-gray-50'; ?> rounded-lg">
                        <span class="text-2xl w-10 text-center"><?php echo $medal; ?></span>
                        <div class="flex-1">
                            <div class="font-medium"><?php echo esc_html($item['nombre'] ?? $item['titulo'] ?? $item['title'] ?? ''); ?></div>
                        </div>
                        <div class="text-xl font-bold text-gray-800"><?php echo esc_html($item['puntos'] ?? $item['total'] ?? $item['value'] ?? 0); ?></div>
                    </div>
                    <?php
                        $position++;
                    endforeach;
                    ?>
                </div>

                <?php elseif (!empty($data['items'])): ?>
                <!-- Items Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach (array_slice($data['items'], 0, 20) as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <?php
                                    $date = $item['fecha'] ?? $item['date'] ?? $item['created_at'] ?? '';
                                    echo $date ? date_i18n(get_option('date_format') . ' H:i', strtotime($date)) : '-';
                                    ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium"><?php echo esc_html($item['titulo'] ?? $item['title'] ?? $item['descripcion'] ?? ''); ?></div>
                                    <?php if (!empty($item['tipo'])): ?>
                                    <span class="text-xs text-gray-500"><?php echo esc_html($item['tipo']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                    $status = $item['estado'] ?? $item['status'] ?? '';
                                    $status_colors = [
                                        'entrada' => 'bg-green-100 text-green-700',
                                        'salida' => 'bg-red-100 text-red-700',
                                        'pendiente' => 'bg-yellow-100 text-yellow-700',
                                        'completado' => 'bg-green-100 text-green-700',
                                        'activo' => 'bg-blue-100 text-blue-700',
                                    ];
                                    $color_class = $status_colors[$status] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-xs <?php echo esc_attr($color_class); ?>">
                                        <?php echo esc_html(ucfirst($status)); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-12">
                    <span class="text-6xl block mb-4">📭</span>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php esc_html_e('Sin datos', 'flavor-chat-ia'); ?></h3>
                    <p class="text-gray-500"><?php esc_html_e('No hay registros para mostrar en este período.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php endif; ?>

                <!-- Export buttons -->
                <div class="mt-6 pt-4 border-t flex justify-end gap-2">
                    <button onclick="window.print()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors flex items-center gap-2">
                        <span>🖨️</span>
                        <?php esc_html_e('Imprimir', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return '<div class="flavor-shortcode-wrapper">' . ob_get_clean() . '</div>';
    }

    /**
     * Renderiza contenido dinámico usando Archive Renderer
     * Antes mostraba "próximamente", ahora usa el sistema dinámico
     */
    private function render_coming_soon_message($module_slug, $template_name) {
        // Usar Archive Renderer dinámicamente en lugar de mostrar "próximamente"
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }

        $renderer = new Flavor_Archive_Renderer();

        // Determinar tipo de vista según el nombre del template
        $template_lower = strtolower($template_name);

        // Si es un single/detalle
        if (strpos($template_lower, 'single') !== false ||
            strpos($template_lower, 'detalle') !== false ||
            strpos($template_lower, 'ver') !== false) {
            $item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            return $renderer->render_single_auto($module_slug, $item_id);
        }

        // Si es mis-* (filtrado por usuario)
        if (strpos($template_lower, 'mis-') === 0 || strpos($template_lower, 'mis_') === 0) {
            return $renderer->render_auto($module_slug, ['user_id' => get_current_user_id()]);
        }

        // Por defecto: listado/archive
        return $renderer->render_auto($module_slug);
    }

    /**
     * Renderiza un listado de módulo
     * UNIFICADO: Delega al sistema unificado [flavor]
     * Uso: [flavor_module_listing module="participacion" columnas="2" limite="12"]
     */
    public function render_module_listing($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'action' => 'listar',
            'vista' => 'grid',
            'limite' => '12',
            'columnas' => '3',
            'mostrar_filtros' => 'no',
            'color' => '',
        ], $atts);

        if (empty($atts['module'])) {
            return '<div class="flavor-error">' . __('Error: Especifica el módulo', 'flavor-chat-ia') . '</div>';
        }

        // Delegar al sistema unificado
        return $this->render_unified([
            'module'  => $atts['module'],
            'view'    => 'listado',
            'limit'   => $atts['limite'],
            'columns' => $atts['columnas'],
            'filters' => $atts['mostrar_filtros'],
            'color'   => $atts['color'],
        ]);
    }

    /**
     * Registra shortcodes para todos los módulos activos
     */
    public function register_module_shortcodes() {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos = $loader->get_loaded_modules();

        foreach ($modulos as $id => $instance) {
            $id_normalizado = str_replace('_', '-', $id);

            // Registrar shortcode: [flavor_{modulo_id}]
            $shortcode_name = 'flavor_' . $id;
            add_shortcode($shortcode_name, function($atts) use ($id, $instance) {
                return $this->render_module_shortcode($id, $instance, $atts);
            });

            // Registrar shortcode de acciones: [flavor_{modulo}_acciones]
            add_shortcode('flavor_' . $id . '_acciones', function($atts) use ($id, $instance) {
                return $this->render_module_acciones($id, $instance, $atts);
            });
            add_shortcode('flavor_' . $id_normalizado . '_acciones', function($atts) use ($id, $instance) {
                return $this->render_module_acciones($id, $instance, $atts);
            });

            // Registrar shortcodes de vistas comunes
            $this->register_common_view_shortcodes($id, $id_normalizado, $instance);

            // Registrar aliases declarados en renderer_config()['tabs'].
            $this->register_renderer_tab_shortcodes($id, $id_normalizado, $instance);
        }
    }

    /**
     * Registra shortcodes declarados en tabs del renderer de cada modulo.
     *
     * Permite resolver configuraciones tipo "content => shortcode:xxx" aunque
     * el tag "xxx" no exista como shortcode explicito en el modulo.
     */
    private function register_renderer_tab_shortcodes($id, $id_normalizado, $instance) {
        if (!is_object($instance)) {
            return;
        }

        $config = [];
        if (method_exists($instance, 'get_renderer_config')) {
            try {
                $config = $instance->get_renderer_config();
            } catch (Throwable $e) {
                $config = [];
            }
        }

        if (empty($config) || !is_array($config)) {
            $config = $this->get_module_renderer_config($id_normalizado);
        }

        if (empty($config['tabs']) || !is_array($config['tabs'])) {
            return;
        }

        foreach ($config['tabs'] as $tab_id => $tab_config) {
            $content = $tab_config['content'] ?? '';
            if (!is_string($content) || strpos($content, 'shortcode:') !== 0) {
                continue;
            }

            $declared_shortcode = trim(substr($content, strlen('shortcode:')));
            if ($declared_shortcode === '') {
                continue;
            }

            $base_candidates = [
                $declared_shortcode,
                str_replace('-', '_', $declared_shortcode),
                str_replace('_', '-', $declared_shortcode),
            ];

            $shortcode_candidates = [];
            foreach ($base_candidates as $candidate) {
                if (!is_string($candidate) || $candidate === '') {
                    continue;
                }
                $shortcode_candidates[] = $candidate;
                if (strpos($candidate, 'flavor_') !== 0) {
                    $shortcode_candidates[] = 'flavor_' . $candidate;
                }
            }

            foreach (array_unique($shortcode_candidates) as $shortcode_tag) {
                if (shortcode_exists($shortcode_tag)) {
                    continue;
                }

                add_shortcode($shortcode_tag, function($atts) use ($id, $instance, $tab_id) {
                    $atts = shortcode_atts([
                        'limit' => '12',
                        'columnas' => '3',
                    ], $atts);

                    $vista = str_replace('-', '_', (string) $tab_id);
                    return $this->render_module_view($id, $instance, $vista, $atts);
                });
            }
        }
    }

    /**
     * Registra shortcodes de vistas comunes para un módulo
     */
    private function register_common_view_shortcodes($id, $id_normalizado, $instance) {
        // Vistas comunes que deberían tener shortcodes
        $vistas_comunes = [
            'listado', 'calendario', 'mapa', 'catalogo',
            'mis_inscripciones', 'mis_reservas', 'mis_prestamos',
            'mis_anuncios', 'mis_cursos', 'mis_viajes', 'mis_incidencias',
            'buscar', 'formulario', 'crear', 'reportar'
        ];

        foreach ($vistas_comunes as $vista) {
            $vista_normalizada = str_replace('_', '-', $vista);

            // [eventos_listado] -> [flavor_module_listing module="eventos" vista="listado"]
            add_shortcode($id_normalizado . '_' . $vista_normalizada, function($atts) use ($id, $instance, $vista) {
                $atts = shortcode_atts(['limit' => '12', 'columnas' => '3'], $atts);
                return $this->render_module_view($id, $instance, $vista, $atts);
            });

            // También con guiones bajos
            add_shortcode($id . '_' . $vista, function($atts) use ($id, $instance, $vista) {
                $atts = shortcode_atts(['limit' => '12', 'columnas' => '3'], $atts);
                return $this->render_module_view($id, $instance, $vista, $atts);
            });
        }
    }

    /**
     * Renderiza una vista específica de un módulo
     */
    private function render_module_view($module_id, $instance, $vista, $atts) {
        // Verificar si el usuario está logueado para vistas personales
        if (strpos($vista, 'mis_') === 0 && !is_user_logged_in()) {
            return $this->render_login_required_message();
        }

        // Intentar cargar template específico del módulo
        $module_slug = str_replace('_', '-', $module_id);
        $vista_slug = str_replace('_', '-', $vista);

        $template_paths = [
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/templates/{$vista_slug}.php",
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/frontend/{$vista_slug}.php",
            FLAVOR_CHAT_IA_PATH . "templates/frontend/{$module_slug}/{$vista_slug}.php",
        ];

        foreach ($template_paths as $path) {
            if (file_exists($path)) {
                ob_start();
                $module = $instance;
                $usuario_id = get_current_user_id();
                include $path;
                return ob_get_clean();
            }
        }

        // Si no hay template, usar render genérico
        return $this->render_module_shortcode($module_id, $instance, array_merge($atts, ['vista' => $vista]));
    }

    /**
     * Renderiza las acciones de un módulo
     */
    private function render_module_acciones($module_id, $instance, $atts) {
        $atts = shortcode_atts([
            'layout' => 'buttons',
            'mostrar' => '', // Filtrar acciones específicas
        ], $atts);

        // Si el módulo tiene el trait de acciones, usarlo
        if (method_exists($instance, 'get_frontend_actions')) {
            $acciones = $instance->get_frontend_actions();
        } else {
            // Usar acciones por defecto según el módulo
            $acciones = $this->get_default_module_actions($module_id);
        }

        if (empty($acciones)) {
            return '';
        }

        // Filtrar acciones si se especifica
        if (!empty($atts['mostrar'])) {
            $filtrar = array_map('trim', explode(',', $atts['mostrar']));
            $acciones = array_intersect_key($acciones, array_flip($filtrar));
        }

        ob_start();
        $this->render_acciones_html($module_id, $acciones, $atts['layout']);
        return ob_get_clean();
    }

    /**
     * Obtiene acciones por defecto para un módulo
     */
    private function get_default_module_actions($module_id) {
        $module_slug = str_replace('_', '-', $module_id);

        $acciones_por_modulo = [
            'eventos' => [
                'inscribirse' => ['label' => __('Inscribirse', 'flavor-chat-ia'), 'icon' => 'dashicons-yes-alt', 'url' => '#inscribirse'],
                'ver_calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'url' => '/mi-portal/eventos/calendario'],
                'mis_inscripciones' => ['label' => __('Mis Inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt', 'url' => '/mi-portal/eventos/mis-inscripciones'],
            ],
            'espacios-comunes' => [
                'reservar' => ['label' => __('Reservar', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'url' => '#reservar'],
                'mis_reservas' => ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'url' => '/mi-portal/espacios-comunes/mis-reservas'],
                'ver_disponibilidad' => ['label' => __('Disponibilidad', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility', 'url' => '/mi-portal/espacios-comunes/calendario'],
            ],
            'biblioteca' => [
                'buscar' => ['label' => __('Buscar Libro', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'url' => '/mi-portal/biblioteca'],
                'mis_prestamos' => ['label' => __('Mis Préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'url' => '/mi-portal/biblioteca/mis-prestamos'],
            ],
            'bicicletas-compartidas' => [
                'alquilar' => ['label' => __('Alquilar Bici', 'flavor-chat-ia'), 'icon' => 'dashicons-unlock', 'url' => '/mi-portal/bicicletas-compartidas'],
                'devolver' => ['label' => __('Devolver', 'flavor-chat-ia'), 'icon' => 'dashicons-lock', 'url' => '#devolver'],
                'mis_prestamos' => ['label' => __('Mis Préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'url' => '/mi-portal/bicicletas-compartidas/mis-prestamos'],
            ],
            'incidencias' => [
                'reportar' => ['label' => __('Reportar Incidencia', 'flavor-chat-ia'), 'icon' => 'dashicons-flag', 'url' => '/mi-portal/incidencias/reportar'],
                'mis_reportes' => ['label' => __('Mis Reportes', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'url' => '/mi-portal/incidencias/mis-incidencias'],
            ],
            'marketplace' => [
                'publicar' => ['label' => __('Publicar Anuncio', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'url' => '/mi-portal/marketplace/publicar'],
                'mis_anuncios' => ['label' => __('Mis Anuncios', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone', 'url' => '/mi-portal/marketplace/mis-anuncios'],
            ],
            'banco-tiempo' => [
                'ofrecer' => ['label' => __('Ofrecer Servicio', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'url' => '/mi-portal/banco-tiempo/ofrecer'],
                'buscar' => ['label' => __('Buscar Servicio', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'url' => '/mi-portal/banco-tiempo'],
                'mis_intercambios' => ['label' => __('Mis Intercambios', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize', 'url' => '/mi-portal/banco-tiempo/mis-intercambios'],
            ],
        ];

        return $acciones_por_modulo[$module_slug] ?? [];
    }

    /**
     * Renderiza HTML de acciones
     */
    private function render_acciones_html($module_id, $acciones, $layout) {
        $module_slug = str_replace('_', '-', $module_id);
        ?>
        <div class="flavor-module-actions flavor-module-actions--<?php echo esc_attr($layout); ?>" data-module="<?php echo esc_attr($module_id); ?>">
            <?php foreach ($acciones as $action_id => $action) : ?>
                <a href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                   class="flavor-action-btn"
                   data-action="<?php echo esc_attr($action_id); ?>">
                    <span class="dashicons <?php echo esc_attr($action['icon'] ?? 'dashicons-yes'); ?>"></span>
                    <span class="flavor-action-btn__label"><?php echo esc_html($action['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <style>
        .flavor-module-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; margin: 1rem 0; }
        .flavor-action-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: #f3f4f6; border-radius: 8px; text-decoration: none; color: #374151; font-weight: 500; transition: all 0.2s; }
        .flavor-action-btn:hover { background: #4f46e5; color: white; }
        .flavor-action-btn .dashicons { font-size: 18px; width: 18px; height: 18px; }
        </style>
        <?php
    }

    /**
     * Renderiza mensaje de login requerido
     */
    private function render_login_required_message() {
        $login_url = wp_login_url($this->get_current_request_url());
        return sprintf(
            '<div class="flavor-login-required">
                <span class="dashicons dashicons-lock"></span>
                <p>%s</p>
                <a href="%s" class="flavor-button">%s</a>
            </div>
            <style>
            .flavor-login-required { text-align: center; padding: 2rem; background: #f9fafb; border-radius: 12px; }
            .flavor-login-required .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }
            .flavor-login-required p { color: #6b7280; margin-bottom: 1rem; }
            .flavor-login-required .flavor-button { display: inline-block; padding: 0.75rem 1.5rem; background: #4f46e5; color: white; border-radius: 8px; text-decoration: none; }
            </style>',
            __('Inicia sesión para ver tu contenido personal', 'flavor-chat-ia'),
            esc_url($login_url),
            __('Iniciar sesión', 'flavor-chat-ia')
        );
    }

    /**
     * Maneja el envío de formularios vía AJAX
     */
    public function handle_form_submission() {
        // Verificar nonce
        $module_id = sanitize_text_field($_POST['flavor_module'] ?? '');

        if (!check_ajax_referer('flavor_module_action_' . $module_id, 'flavor_nonce', false)) {
            wp_send_json_error(__('Token de seguridad inválido', 'flavor-chat-ia'));
            return;
        }

        $action = sanitize_text_field($_POST['flavor_action'] ?? '');

        if (empty($module_id) || empty($action)) {
            wp_send_json_error(__('Datos incompletos', 'flavor-chat-ia'));
            return;
        }

        // Obtener módulo
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            wp_send_json_error(__('Sistema no disponible', 'flavor-chat-ia'));
            return;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $instance = $loader->get_module($module_id);

        if (!$instance) {
            wp_send_json_error(__('Módulo no encontrado', 'flavor-chat-ia'));
            return;
        }

        // Verificar que el usuario tenga permisos
        if (class_exists('Flavor_Module_Access_Control')) {
            $control = Flavor_Module_Access_Control::get_instance();
            if (!$control->user_can_access($module_id)) {
                wp_send_json_error(__('No tienes permisos para esta acción', 'flavor-chat-ia'));
                return;
            }
        }

        // Preparar parámetros
        $params = [];
        foreach ($_POST as $key => $value) {
            if (!in_array($key, ['action', 'flavor_module', 'flavor_action', 'flavor_nonce'])) {
                $params[$key] = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
            }
        }

        // Ejecutar acción del módulo
        try {
            if (method_exists($instance, 'execute_action')) {
                $result = $instance->execute_action($action, $params);
            } else {
                $result = ['success' => false, 'message' => __('Módulo no soporta acciones', 'flavor-chat-ia')];
            }

            if (is_array($result)) {
                if ($result['success'] ?? false) {
                    wp_send_json_success([
                        'message' => $result['message'] ?? __('Operación completada', 'flavor-chat-ia'),
                        'data' => $result['data'] ?? [],
                        'redirect' => $result['redirect'] ?? '',
                    ]);
                } else {
                    wp_send_json_error($result['message'] ?? __('Error al procesar', 'flavor-chat-ia'));
                }
            } else {
                wp_send_json_error(__('Respuesta inválida del módulo', 'flavor-chat-ia'));
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Renderiza un formulario de módulo
     * UNIFICADO: Delega al sistema unificado [flavor]
     * Uso: [flavor_module_form module="eventos" action="inscribirse"]
     */
    public function render_module_form($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'action' => 'crear',
            'titulo' => '',
            'descripcion' => '',
            'mostrar_titulo' => 'yes',
        ], $atts);

        if (empty($atts['module'])) {
            return '<div class="flavor-error">' . __('Error: Especifica el módulo', 'flavor-chat-ia') . '</div>';
        }

        // Delegar al sistema unificado
        return $this->render_unified([
            'module' => $atts['module'],
            'view'   => 'form',
            'title'  => $atts['titulo'],
        ]);
    }

    /**
     * Renderiza el HTML del formulario
     */
    private function render_form_html($form_data, $atts) {
        ?>
        <div class="flavor-module-form-wrapper">
            <?php if ($atts['mostrar_titulo'] === 'yes' && !empty($form_data['title'])) : ?>
                <div class="flavor-form-header">
                    <h3 class="flavor-form-title"><?php echo esc_html($form_data['title']); ?></h3>
                    <?php if (!empty($form_data['description'])) : ?>
                        <p class="flavor-form-description"><?php echo esc_html($form_data['description']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form class="flavor-module-form"
                  data-module="<?php echo esc_attr($form_data['module_id']); ?>"
                  data-action="<?php echo esc_attr($form_data['action']); ?>"
                  data-ajax="<?php echo $form_data['ajax'] ? '1' : '0'; ?>"
                  method="post">

                <?php wp_nonce_field('flavor_module_action_' . $form_data['module_id'], 'flavor_nonce'); ?>
                <input type="hidden" name="flavor_module" value="<?php echo esc_attr($form_data['module_id']); ?>">
                <input type="hidden" name="flavor_action" value="<?php echo esc_attr($form_data['action']); ?>">

                <div class="flavor-form-fields">
                    <?php foreach ($form_data['fields'] as $field_name => $field_config) : ?>
                        <?php $this->render_form_field($field_name, $field_config); ?>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-form-messages"></div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-button flavor-button--primary">
                        <?php echo esc_html($form_data['submit_text']); ?>
                    </button>
                </div>
            </form>
        </div>

        <style>
        .flavor-module-form-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .flavor-form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .flavor-form-title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 12px;
        }
        .flavor-form-description {
            font-size: 16px;
            color: #6b7280;
            margin: 0;
        }
        .flavor-form-fields {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .flavor-form-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .flavor-form-label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        .flavor-form-label--required::after {
            content: " *";
            color: #ef4444;
        }
        .flavor-form-input,
        .flavor-form-textarea,
        .flavor-form-select {
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s;
        }
        .flavor-form-input:focus,
        .flavor-form-textarea:focus,
        .flavor-form-select:focus {
            outline: none;
            border-color: var(--flavor-primary, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .flavor-form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .flavor-form-help {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
        .flavor-form-messages {
            margin: 20px 0;
        }
        .flavor-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .flavor-message--success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .flavor-message--error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .flavor-form-actions {
            margin-top: 24px;
        }
        .flavor-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }
        .flavor-button--primary {
            background: var(--flavor-primary, #3b82f6);
            color: white;
        }
        .flavor-button--primary:hover {
            background: var(--flavor-primary-dark, #2563eb);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .flavor-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        @media (max-width: 640px) {
            .flavor-module-form-wrapper {
                padding: 20px;
            }
            .flavor-form-title {
                font-size: 24px;
            }
        }
        </style>

        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                const forms = document.querySelectorAll('.flavor-module-form');

                forms.forEach(function(form) {
                    if (form.dataset.ajax !== '1') return;

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        const button = form.querySelector('button[type="submit"]');
                        const messages = form.querySelector('.flavor-form-messages');
                        const formData = new FormData(form);

                        formData.append('action', 'flavor_module_action');

                        button.disabled = true;
                        button.textContent = '<?php _e('Enviando...', 'flavor-chat-ia'); ?>';
                        messages.innerHTML = '';

                        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                messages.innerHTML = '<div class="flavor-message flavor-message--success">' +
                                    (data.data.message || '<?php _e('Operación completada con éxito', 'flavor-chat-ia'); ?>') +
                                    '</div>';
                                form.reset();

                                // Redirigir si se especifica
                                if (data.data.redirect) {
                                    setTimeout(function() {
                                        window.location.href = data.data.redirect;
                                    }, 1500);
                                }
                            } else {
                                messages.innerHTML = '<div class="flavor-message flavor-message--error">' +
                                    (data.data || '<?php _e('Error al procesar el formulario', 'flavor-chat-ia'); ?>') +
                                    '</div>';
                            }
                        })
                        .catch(error => {
                            messages.innerHTML = '<div class="flavor-message flavor-message--error">' +
                                '<?php _e('Error de conexión', 'flavor-chat-ia'); ?>' +
                                '</div>';
                        })
                        .finally(function() {
                            button.disabled = false;
                            button.textContent = '<?php echo esc_js($form_data['submit_text'] ?? __('Enviar', 'flavor-chat-ia')); ?>';
                        });
                    });
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Renderiza un campo del formulario
     */
    private function render_form_field($field_name, $field_config) {
        $type = $field_config['type'] ?? 'text';
        $label = $field_config['label'] ?? ucfirst(str_replace('_', ' ', $field_name));
        $required = $field_config['required'] ?? false;
        $placeholder = $field_config['placeholder'] ?? '';
        $help = $field_config['help'] ?? '';
        $options = $field_config['options'] ?? [];
        $value = $field_config['value'] ?? '';

        ?>
        <div class="flavor-form-field">
            <label for="flavor_field_<?php echo esc_attr($field_name); ?>"
                   class="flavor-form-label <?php echo $required ? 'flavor-form-label--required' : ''; ?>">
                <?php echo esc_html($label); ?>
            </label>

            <?php if ($type === 'textarea') : ?>
                <textarea
                    id="flavor_field_<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="flavor-form-textarea"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    <?php echo $required ? 'required' : ''; ?>
                    <?php if (isset($field_config['rows'])) : ?>rows="<?php echo intval($field_config['rows']); ?>"<?php endif; ?>
                ><?php echo esc_textarea($value); ?></textarea>

            <?php elseif ($type === 'select') : ?>
                <select
                    id="flavor_field_<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="flavor-form-select"
                    <?php echo $required ? 'required' : ''; ?>>
                    <?php if (!$required || $placeholder) : ?>
                        <option value=""><?php echo esc_html($placeholder ?: __('Selecciona una opción', 'flavor-chat-ia')); ?></option>
                    <?php endif; ?>
                    <?php foreach ($options as $opt_value => $opt_label) : ?>
                        <option value="<?php echo esc_attr($opt_value); ?>" <?php selected($value, $opt_value); ?>>
                            <?php echo esc_html($opt_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php else : ?>
                <input
                    type="<?php echo esc_attr($type); ?>"
                    id="flavor_field_<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="flavor-form-input"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    <?php echo $required ? 'required' : ''; ?>
                    <?php if (isset($field_config['min'])) : ?>min="<?php echo esc_attr($field_config['min']); ?>"<?php endif; ?>
                    <?php if (isset($field_config['max'])) : ?>max="<?php echo esc_attr($field_config['max']); ?>"<?php endif; ?>
                    <?php if (isset($field_config['step'])) : ?>step="<?php echo esc_attr($field_config['step']); ?>"<?php endif; ?>
                    <?php if (isset($field_config['pattern'])) : ?>pattern="<?php echo esc_attr($field_config['pattern']); ?>"<?php endif; ?>
                >
            <?php endif; ?>

            <?php if ($help) : ?>
                <span class="flavor-form-help"><?php echo esc_html($help); ?></span>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el shortcode de un módulo
     */
    private function render_module_shortcode($modulo_id, $instance, $atts) {
        $atts = shortcode_atts([
            'vista' => 'grid', // grid, lista, calendario
            'limite' => '12',
            'columnas' => '3',
            'mostrar_filtros' => 'yes',
            'mostrar_fecha' => 'yes',
            'mostrar_precio' => 'no',
            'tipo' => '', // Para filtrar por tipo específico
            'color' => '#4f46e5',
        ], $atts);

        // Preparar datos para el template
        $data = $this->prepare_module_data($modulo_id, $instance, $atts);

        // Buscar template del módulo en varias ubicaciones
        $template_paths = $this->get_template_paths($modulo_id, $atts['vista']);

        $template = null;
        foreach ($template_paths as $path) {
            if (file_exists($path)) {
                $template = $path;
                break;
            }
        }

        // Renderizar template
        ob_start();

        // Hacer disponibles las variables en el scope del template
        extract($data);

        // Variables adicionales para templates genéricos
        $titulo = $data['modulo_nombre'];
        $items = $data['items'];
        $columnas = intval($atts['columnas']);
        $color_primario = $atts['color'];
        $mostrar_fecha = $atts['mostrar_fecha'] === 'yes';
        $mostrar_precio = $atts['mostrar_precio'] === 'yes';
        $mostrar_imagen = true;
        $mostrar_descripcion = true;
        $estilo = 'cards';

        // Wrapper para estilos consistentes
        echo '<div class="flavor-module-shortcode flavor-module-' . esc_attr($modulo_id) . '">';

        if ($template) {
            include $template;
        } else {
            // Usar el Component Renderer del Web Builder Pro si está disponible
            echo $this->render_with_component_renderer($modulo_id, $data, $atts);
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Obtiene las rutas de template posibles para un módulo
     *
     * @param string $modulo_id ID del módulo
     * @param string $vista Tipo de vista (grid, lista, calendario)
     * @return array Lista de rutas de template
     */
    private function get_template_paths($modulo_id, $vista) {
        $paths = [];

        // Normalizar IDs para soportar guiones y guiones bajos
        $modulo_guiones = str_replace('_', '-', $modulo_id);
        $modulo_guiones_bajos = str_replace('-', '_', $modulo_id);

        // 1. Templates en el directorio views del módulo (includes/modules/{modulo}/views/)
        $paths[] = FLAVOR_CHAT_IA_PATH . "includes/modules/{$modulo_guiones}/views/{$vista}.php";
        $paths[] = FLAVOR_CHAT_IA_PATH . "includes/modules/{$modulo_guiones_bajos}/views/{$vista}.php";

        // 2. Template específico del módulo en el plugin principal
        $paths[] = FLAVOR_CHAT_IA_PATH . "templates/components/{$modulo_id}/{$modulo_id}-{$vista}.php";
        $paths[] = FLAVOR_CHAT_IA_PATH . "templates/components/{$modulo_guiones}/{$vista}.php";
        $paths[] = FLAVOR_CHAT_IA_PATH . "templates/components/{$modulo_id}/grid.php";
        $paths[] = FLAVOR_CHAT_IA_PATH . "templates/frontend/{$modulo_id}/archive.php";

        // 3. Template genérico en el plugin principal
        $paths[] = FLAVOR_CHAT_IA_PATH . "templates/components/unified/_generic-grid.php";

        // 4. Templates del Web Builder Pro addon
        if (defined('FLAVOR_WEB_BUILDER_PATH')) {
            $paths[] = FLAVOR_WEB_BUILDER_PATH . "templates/components/{$modulo_id}/{$vista}.php";
            $paths[] = FLAVOR_WEB_BUILDER_PATH . "templates/components/landings/_generic-grid.php";
        }

        return $paths;
    }

    /**
     * Renderiza usando el Component Renderer del Web Builder Pro
     *
     * @param string $modulo_id ID del módulo
     * @param array $data Datos del módulo
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    private function render_with_component_renderer($modulo_id, $data, $atts) {
        // Verificar si Web Builder Pro está disponible
        if (!class_exists('Flavor_Component_Renderer')) {
            return $this->render_fallback($modulo_id, null, $atts);
        }

        $renderer = Flavor_Component_Renderer::get_instance();

        // Preparar datos para el componente unificado
        $component_data = [
            'titulo' => $data['modulo_nombre'],
            'items' => $data['items'],
            'columnas' => intval($atts['columnas']),
            'color_primario' => $atts['color'],
            'mostrar_fecha' => $atts['mostrar_fecha'] === 'yes',
            'mostrar_precio' => $atts['mostrar_precio'] === 'yes',
            'mostrar_imagen' => true,
            'mostrar_descripcion' => true,
            'estilo' => 'cards',
            'btn_texto' => '',
            'btn_url' => '',
        ];

        // Intentar renderizar el componente unified_grid
        ob_start();
        $renderer->render_component('unified_grid', $component_data, [
            'custom_class' => 'flavor-module-' . sanitize_html_class($modulo_id),
        ]);
        $output = ob_get_clean();

        // Si el componente no existe, usar template genérico directo
        if (empty(trim($output)) || strpos($output, 'error') !== false) {
            return $this->render_generic_grid($data, $atts);
        }

        return $output;
    }

    /**
     * Renderiza usando el template genérico de grid directamente
     *
     * @param array $data Datos del módulo
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    private function render_generic_grid($data, $atts) {
        // Variables para el template
        $titulo = $data['modulo_nombre'];
        $subtitulo = '';
        $items = $data['items'];
        $columnas = intval($atts['columnas']);
        $mostrar_imagen = true;
        $mostrar_descripcion = true;
        $mostrar_fecha = $atts['mostrar_fecha'] === 'yes';
        $mostrar_precio = $atts['mostrar_precio'] === 'yes';
        $color_primario = $atts['color'];
        $btn_texto = '';
        $btn_url = '';
        $estilo = 'cards';

        // Buscar template genérico
        $template_path = null;
        if (defined('FLAVOR_WEB_BUILDER_PATH')) {
            $template_path = FLAVOR_WEB_BUILDER_PATH . 'templates/components/landings/_generic-grid.php';
        }

        if ($template_path && file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Fallback último recurso
        return $this->render_fallback($data['modulo_id'], null, $atts);
    }

    /**
     * Prepara los datos del módulo para el template
     */
    private function prepare_module_data($modulo_id, $instance, $atts) {
        global $wpdb;

        $data = [
            'modulo_id' => $modulo_id,
            'modulo_nombre' => $instance->name ?? ucfirst(str_replace('_', ' ', $modulo_id)),
            'atts' => $atts,
            'items' => [],
        ];

        // Intentar obtener datos del módulo
        $tabla = $wpdb->prefix . 'flavor_' . $modulo_id;

        if ($this->tabla_existe($tabla)) {
            $limite = intval($atts['limite']);
            $tipo = sanitize_text_field($atts['tipo']);

            $where = "WHERE 1=1";
            $params = [];

            // Filtro por estado publicado si existe la columna
            $columns = $wpdb->get_col("DESCRIBE {$tabla}");
            if (in_array('estado', $columns)) {
                $where .= " AND estado = %s";
                $params[] = 'publicado';
            }

            // Filtro por tipo si se especifica
            if (!empty($tipo) && in_array('tipo', $columns)) {
                $where .= " AND tipo = %s";
                $params[] = $tipo;
            }

            // Filtro por fecha futura para eventos
            if ($modulo_id === 'eventos' && in_array('fecha_inicio', $columns)) {
                $where .= " AND fecha_inicio >= %s";
                $params[] = current_time('mysql');
            }

            // Orden
            $order = "ORDER BY id DESC";
            if (in_array('fecha_inicio', $columns)) {
                $order = "ORDER BY fecha_inicio ASC";
            } elseif (in_array('fecha_creacion', $columns)) {
                $order = "ORDER BY fecha_creacion DESC";
            } elseif (in_array('created_at', $columns)) {
                $order = "ORDER BY created_at DESC";
            }

            $query = "SELECT * FROM {$tabla} {$where} {$order} LIMIT {$limite}";

            if (!empty($params)) {
                $query = $wpdb->prepare($query, ...$params);
            }

            $data['items'] = $wpdb->get_results($query, ARRAY_A);
        }

        // Si no hay datos reales, proporcionar datos de ejemplo según el módulo
        if (empty($data['items'])) {
            $data['items'] = $this->get_sample_data($modulo_id);
        }

        return $data;
    }

    /**
     * Verifica si una tabla existe
     */
    private function tabla_existe($tabla) {
        global $wpdb;
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $tabla);
        return $wpdb->get_var($query) === $tabla;
    }

    /**
     * Obtiene datos de ejemplo para un módulo
     *
     * NOTA: Datos de demostración eliminados (2026-02-23)
     * Los shortcodes ahora muestran "Sin contenido" en lugar de datos falsos.
     * Para habilitar modo demo, activar la opción 'flavor_demo_mode'.
     *
     * @see docs/DATOS-DEMO-HARDCODEADOS.md
     */
    private function get_sample_data($modulo_id) {
        // Modo demo desactivado por defecto - devolver array vacío
        if (!get_option('flavor_demo_mode', false)) {
            return [];
        }

        // Normalizar ID de módulo
        $modulo_normalizado = str_replace('-', '_', $modulo_id);

        switch ($modulo_normalizado) {
            case 'eventos':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Conferencia de Tecnología', 'tipo'=>'conferencia', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+3 days')), 'ubicacion'=>'Centro de Convenciones', 'precio'=>15.00, 'aforo_maximo'=>100, 'inscritos_count'=>45, 'estado'=>'publicado', 'descripcion'=>'Últimas tendencias en tecnología', 'icon'=>'dashicons-calendar-alt'],
                    ['id'=>2, 'titulo'=>'[DEMO] Taller de Cerámica', 'tipo'=>'taller', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+5 days')), 'ubicacion'=>'Sala de Artes', 'precio'=>25.00, 'aforo_maximo'=>20, 'inscritos_count'=>18, 'estado'=>'publicado', 'descripcion'=>'Técnicas de cerámica artesanal', 'icon'=>'dashicons-art'],
                    ['id'=>3, 'titulo'=>'[DEMO] Charla: Alimentación Saludable', 'tipo'=>'charla', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+7 days')), 'ubicacion'=>'Biblioteca', 'precio'=>0, 'aforo_maximo'=>50, 'inscritos_count'=>12, 'estado'=>'publicado', 'descripcion'=>'Consejos para una dieta equilibrada', 'icon'=>'dashicons-heart'],
                ];

            case 'talleres':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Taller de Fotografía', 'tipo'=>'Arte', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+5 days')), 'plazas_disponibles'=>15, 'precio'=>30.00, 'descripcion'=>'Aprende técnicas de fotografía digital', 'icon'=>'dashicons-camera'],
                    ['id'=>2, 'titulo'=>'[DEMO] Cocina Mediterránea', 'tipo'=>'Gastronomía', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+10 days')), 'plazas_disponibles'=>12, 'precio'=>40.00, 'descripcion'=>'Recetas tradicionales del Mediterráneo', 'icon'=>'dashicons-carrot'],
                    ['id'=>3, 'titulo'=>'[DEMO] Yoga para Principiantes', 'tipo'=>'Bienestar', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+7 days')), 'plazas_disponibles'=>20, 'precio'=>15.00, 'descripcion'=>'Inicia tu práctica de yoga', 'icon'=>'dashicons-universal-access'],
                ];

            case 'grupos_consumo':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Grupo Ecológico Norte', 'descripcion'=>'Productos ecológicos y de proximidad de agricultores locales', 'miembros'=>25, 'icon'=>'dashicons-carrot'],
                    ['id'=>2, 'titulo'=>'[DEMO] Cooperativa Sur', 'descripcion'=>'Compra conjunta de productos frescos y de temporada', 'miembros'=>40, 'icon'=>'dashicons-store'],
                    ['id'=>3, 'titulo'=>'[DEMO] Huerta Compartida', 'descripcion'=>'Verduras de nuestro propio huerto comunitario', 'miembros'=>18, 'icon'=>'dashicons-palmtree'],
                ];

            case 'bicicletas_compartidas':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Estación Centro', 'descripcion'=>'Plaza Mayor, 12 bicicletas disponibles', 'disponibles'=>12, 'tipo'=>'estacion', 'icon'=>'dashicons-location'],
                    ['id'=>2, 'titulo'=>'[DEMO] Estación Parque', 'descripcion'=>'Entrada principal del parque, 8 bicicletas', 'disponibles'=>8, 'tipo'=>'estacion', 'icon'=>'dashicons-location'],
                    ['id'=>3, 'titulo'=>'[DEMO] Estación Universidad', 'descripcion'=>'Campus principal, 15 bicicletas', 'disponibles'=>15, 'tipo'=>'estacion', 'icon'=>'dashicons-location'],
                ];

            case 'carpooling':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Madrid → Barcelona', 'descripcion'=>'Salida a las 8:00, 3 plazas libres', 'fecha_inicio'=>date('Y-m-d', strtotime('+2 days')), 'precio'=>25.00, 'plazas'=>3, 'icon'=>'dashicons-car'],
                    ['id'=>2, 'titulo'=>'[DEMO] Valencia → Alicante', 'descripcion'=>'Salida a las 10:00, 2 plazas libres', 'fecha_inicio'=>date('Y-m-d', strtotime('+1 day')), 'precio'=>12.00, 'plazas'=>2, 'icon'=>'dashicons-car'],
                    ['id'=>3, 'titulo'=>'[DEMO] Sevilla → Córdoba', 'descripcion'=>'Salida a las 9:30, 4 plazas libres', 'fecha_inicio'=>date('Y-m-d', strtotime('+3 days')), 'precio'=>15.00, 'plazas'=>4, 'icon'=>'dashicons-car'],
                ];

            case 'biblioteca':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] El Quijote', 'descripcion'=>'Miguel de Cervantes - Clásico de la literatura española', 'tipo'=>'Novela', 'disponible'=>true, 'icon'=>'dashicons-book'],
                    ['id'=>2, 'titulo'=>'[DEMO] 1984', 'descripcion'=>'George Orwell - Distopía y control social', 'tipo'=>'Novela', 'disponible'=>true, 'icon'=>'dashicons-book'],
                    ['id'=>3, 'titulo'=>'[DEMO] Cien años de soledad', 'descripcion'=>'Gabriel García Márquez - Realismo mágico', 'tipo'=>'Novela', 'disponible'=>false, 'icon'=>'dashicons-book'],
                ];

            case 'marketplace':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Bicicleta de montaña', 'descripcion'=>'Bicicleta en buen estado, poco uso', 'precio'=>150.00, 'tipo'=>'venta', 'icon'=>'dashicons-products'],
                    ['id'=>2, 'titulo'=>'[DEMO] Colección de libros', 'descripcion'=>'50 libros variados de segunda mano', 'precio'=>45.00, 'tipo'=>'venta', 'icon'=>'dashicons-book-alt'],
                    ['id'=>3, 'titulo'=>'[DEMO] Mueble de jardín', 'descripcion'=>'Mesa con 4 sillas de exterior', 'precio'=>80.00, 'tipo'=>'venta', 'icon'=>'dashicons-admin-home'],
                ];

            case 'incidencias':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Farola sin funcionar', 'descripcion'=>'Calle Mayor esquina Plaza', 'tipo'=>'alumbrado', 'estado'=>'pendiente', 'icon'=>'dashicons-warning'],
                    ['id'=>2, 'titulo'=>'[DEMO] Bache en calzada', 'descripcion'=>'Avenida Principal km 2', 'tipo'=>'via_publica', 'estado'=>'en_proceso', 'icon'=>'dashicons-warning'],
                    ['id'=>3, 'titulo'=>'[DEMO] Contenedor roto', 'descripcion'=>'Junto al parque infantil', 'tipo'=>'limpieza', 'estado'=>'resuelto', 'icon'=>'dashicons-yes-alt'],
                ];

            case 'comunidades':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Comunidad Vecinal Centro', 'descripcion'=>'Vecinos del barrio centro unidos por un mejor barrio', 'miembros'=>156, 'tipo'=>'vecinal', 'icon'=>'dashicons-groups'],
                    ['id'=>2, 'titulo'=>'[DEMO] Club de Lectura', 'descripcion'=>'Amantes de la lectura compartiendo recomendaciones', 'miembros'=>45, 'tipo'=>'cultural', 'icon'=>'dashicons-book'],
                    ['id'=>3, 'titulo'=>'[DEMO] Runners del Parque', 'descripcion'=>'Grupo de running para todos los niveles', 'miembros'=>89, 'tipo'=>'deportivo', 'icon'=>'dashicons-heart'],
                ];

            case 'espacios_comunes':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Sala de Reuniones A', 'descripcion'=>'Capacidad 20 personas, proyector incluido', 'tipo'=>'sala', 'disponible'=>true, 'icon'=>'dashicons-building'],
                    ['id'=>2, 'titulo'=>'[DEMO] Salón de Actos', 'descripcion'=>'Capacidad 100 personas, escenario y sonido', 'tipo'=>'auditorio', 'disponible'=>true, 'icon'=>'dashicons-megaphone'],
                    ['id'=>3, 'titulo'=>'[DEMO] Terraza Comunitaria', 'descripcion'=>'Espacio exterior para eventos', 'tipo'=>'exterior', 'disponible'=>false, 'icon'=>'dashicons-palmtree'],
                ];

            case 'huertos_urbanos':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Parcela A-12', 'descripcion'=>'25m² disponible para cultivo', 'tipo'=>'parcela', 'disponible'=>true, 'precio'=>15.00, 'icon'=>'dashicons-carrot'],
                    ['id'=>2, 'titulo'=>'[DEMO] Parcela B-05', 'descripcion'=>'30m² con sistema de riego', 'tipo'=>'parcela', 'disponible'=>false, 'precio'=>20.00, 'icon'=>'dashicons-carrot'],
                    ['id'=>3, 'titulo'=>'[DEMO] Zona Comunitaria', 'descripcion'=>'Espacio compartido para principiantes', 'tipo'=>'comunitaria', 'disponible'=>true, 'precio'=>5.00, 'icon'=>'dashicons-groups'],
                ];

            case 'podcast':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Episodio 45: Sostenibilidad urbana', 'descripcion'=>'Hablamos sobre cómo hacer ciudades más verdes', 'fecha'=>date('Y-m-d', strtotime('-3 days')), 'duracion'=>'45:00', 'icon'=>'dashicons-microphone'],
                    ['id'=>2, 'titulo'=>'[DEMO] Episodio 44: Economía circular', 'descripcion'=>'Cómo reducir, reutilizar y reciclar', 'fecha'=>date('Y-m-d', strtotime('-10 days')), 'duracion'=>'38:00', 'icon'=>'dashicons-microphone'],
                    ['id'=>3, 'titulo'=>'[DEMO] Episodio 43: Movilidad compartida', 'descripcion'=>'El futuro del transporte en las ciudades', 'fecha'=>date('Y-m-d', strtotime('-17 days')), 'duracion'=>'52:00', 'icon'=>'dashicons-microphone'],
                ];

            case 'banco_tiempo':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Clases de guitarra', 'descripcion'=>'Ofrezco clases de guitarra para principiantes', 'tipo'=>'oferta', 'tiempo'=>'2h', 'icon'=>'dashicons-format-audio'],
                    ['id'=>2, 'titulo'=>'[DEMO] Ayuda con mudanza', 'descripcion'=>'Necesito ayuda para mover muebles', 'tipo'=>'demanda', 'tiempo'=>'3h', 'icon'=>'dashicons-hammer'],
                    ['id'=>3, 'titulo'=>'[DEMO] Reparación de ordenadores', 'descripcion'=>'Ofrezco mantenimiento básico de PCs', 'tipo'=>'oferta', 'tiempo'=>'1h', 'icon'=>'dashicons-laptop'],
                ];

            case 'cursos':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Introducción a Python', 'descripcion'=>'Aprende programación desde cero', 'tipo'=>'online', 'fecha_inicio'=>date('Y-m-d', strtotime('+7 days')), 'precio'=>50.00, 'icon'=>'dashicons-laptop'],
                    ['id'=>2, 'titulo'=>'[DEMO] Marketing Digital', 'descripcion'=>'Estrategias de marketing en redes sociales', 'tipo'=>'presencial', 'fecha_inicio'=>date('Y-m-d', strtotime('+14 days')), 'precio'=>75.00, 'icon'=>'dashicons-share'],
                    ['id'=>3, 'titulo'=>'[DEMO] Inglés Conversacional', 'descripcion'=>'Mejora tu fluidez hablando', 'tipo'=>'hibrido', 'fecha_inicio'=>date('Y-m-d', strtotime('+5 days')), 'precio'=>40.00, 'icon'=>'dashicons-translation'],
                ];

            case 'parkings':
                return [
                    ['id'=>1, 'titulo'=>'[DEMO] Parking Centro', 'descripcion'=>'Plaza Mayor - 50 plazas disponibles', 'plazas_libres'=>50, 'precio'=>2.50, 'icon'=>'dashicons-location-alt'],
                    ['id'=>2, 'titulo'=>'[DEMO] Parking Estación', 'descripcion'=>'Junto a la estación de tren', 'plazas_libres'=>25, 'precio'=>1.80, 'icon'=>'dashicons-location-alt'],
                    ['id'=>3, 'titulo'=>'[DEMO] Parking Hospital', 'descripcion'=>'Acceso 24 horas', 'plazas_libres'=>120, 'precio'=>2.00, 'icon'=>'dashicons-location-alt'],
                ];

            default:
                // Datos genéricos para módulos sin datos específicos
                return [
                    ['id'=>1, 'titulo'=>__('Elemento de ejemplo 1', 'flavor-chat-ia'), 'descripcion'=>__('Descripción del primer elemento de ejemplo', 'flavor-chat-ia'), 'icon'=>'dashicons-admin-generic'],
                    ['id'=>2, 'titulo'=>__('Elemento de ejemplo 2', 'flavor-chat-ia'), 'descripcion'=>__('Descripción del segundo elemento de ejemplo', 'flavor-chat-ia'), 'icon'=>'dashicons-admin-generic'],
                    ['id'=>3, 'titulo'=>__('Elemento de ejemplo 3', 'flavor-chat-ia'), 'descripcion'=>__('Descripción del tercer elemento de ejemplo', 'flavor-chat-ia'), 'icon'=>'dashicons-admin-generic'],
                ];
        }
    }

    /**
     * Renderiza fallback cuando no hay template
     * UNIFICADO: Delega al sistema render_unified
     */
    private function render_fallback($modulo_id, $instance, $atts) {
        // Normalizar el ID del módulo
        $module_slug = str_replace('_', '-', $modulo_id);

        // Usar el sistema unificado
        return $this->render_unified([
            'module'  => $module_slug,
            'view'    => 'listado',
            'limit'   => $atts['limite'] ?? $atts['limit'] ?? 12,
            'columns' => $atts['columnas'] ?? $atts['columns'] ?? 3,
            'color'   => $atts['color'] ?? '',
        ]);
    }

    /**
     * Obtiene el icono dashicons para un módulo
     *
     * @param string $modulo_id ID del módulo
     * @return string Clase de icono dashicons
     */
    private function get_module_icon($modulo_id) {
        $iconos = [
            'eventos' => 'dashicons-calendar-alt',
            'talleres' => 'dashicons-welcome-learn-more',
            'cursos' => 'dashicons-book-alt',
            'reservas' => 'dashicons-calendar',
            'incidencias' => 'dashicons-warning',
            'marketplace' => 'dashicons-cart',
            'biblioteca' => 'dashicons-book',
            'podcast' => 'dashicons-microphone',
            'radio' => 'dashicons-format-audio',
            'comunidades' => 'dashicons-groups',
            'huertos-urbanos' => 'dashicons-carrot',
            'bicicletas-compartidas' => 'dashicons-bike',
            'carpooling' => 'dashicons-car',
            'parkings' => 'dashicons-location-alt',
            'banco-tiempo' => 'dashicons-clock',
            'grupos-consumo' => 'dashicons-store',
            'espacios-comunes' => 'dashicons-building',
            'participacion' => 'dashicons-megaphone',
            'presupuestos' => 'dashicons-chart-pie',
            'tramites' => 'dashicons-clipboard',
            'avisos-municipales' => 'dashicons-bell',
            'transparencia' => 'dashicons-visibility',
            'reciclaje' => 'dashicons-update',
            'compostaje' => 'dashicons-carrot',
        ];

        $modulo_id_normalizado = str_replace('_', '-', $modulo_id);
        return $iconos[$modulo_id_normalizado] ?? 'dashicons-admin-generic';
    }

    // =========================================================================
    // WIDGETS GENÉRICOS
    // =========================================================================

    /**
     * Widget: Últimos items de un módulo
     *
     * Uso: [flavor_ultimos module="eventos" limit="5" title="Últimos eventos"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_widget_ultimos($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'limit'  => 5,
            'title'  => '',
            'color'  => '',
            'show_date' => 'yes',
            'link'   => '',  // URL para "Ver todos"
        ], $atts);

        $module_slug = sanitize_title($atts['module']);
        if (empty($module_slug)) {
            return '';
        }

        // Obtener configuración del módulo
        $config = $this->get_module_renderer_config($module_slug);

        $color = $atts['color'] ?: ($config['color'] ?? 'primary');
        $title = $atts['title'] ?: sprintf(__('Últimos %s', 'flavor-chat-ia'), $config['title'] ?? ucfirst($module_slug));
        $icon = $config['icon'] ?? '📋';

        // Obtener datos
        $items = $this->get_module_recent_items($module_slug, intval($atts['limit']));

        // Formatear items para quick-list
        $list_items = [];
        foreach ($items as $item) {
            $list_items[] = [
                'title'    => $item['title'] ?? $item['titulo'] ?? '',
                'subtitle' => $atts['show_date'] === 'yes' && !empty($item['date'])
                    ? date_i18n('j M Y', strtotime($item['date']))
                    : ($item['subtitle'] ?? ''),
                'icon'     => $item['icon'] ?? $icon,
                'url'      => $item['url'] ?? '',
                'status'   => $item['status'] ?? '',
            ];
        }

        // Acciones
        $actions = [];
        if (!empty($atts['link'])) {
            $actions[] = ['label' => __('Ver todos', 'flavor-chat-ia'), 'url' => $atts['link']];
        }

        ob_start();
        $items = $list_items;
        $empty_text = __('No hay registros recientes', 'flavor-chat-ia');
        include FLAVOR_CHAT_IA_PATH . 'templates/components/shared/quick-list.php';
        return ob_get_clean();
    }

    /**
     * Widget: Items destacados de un módulo
     *
     * Uso: [flavor_destacados module="cursos" limit="3" title="Cursos destacados"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_widget_destacados($atts) {
        $atts = shortcode_atts([
            'module'  => '',
            'limit'   => 3,
            'title'   => '',
            'color'   => '',
            'columns' => 3,
        ], $atts);

        $module_slug = sanitize_title($atts['module']);
        if (empty($module_slug)) {
            return '';
        }

        // Obtener configuración del módulo
        $config = $this->get_module_renderer_config($module_slug);

        $color = $atts['color'] ?: ($config['color'] ?? 'primary');
        $title = $atts['title'] ?: sprintf(__('%s destacados', 'flavor-chat-ia'), $config['title'] ?? ucfirst($module_slug));

        // Obtener datos destacados
        $items = $this->get_module_featured_items($module_slug, intval($atts['limit']));

        if (empty($items)) {
            return '';
        }

        // Usar Archive Renderer con configuración mínima
        if (!class_exists('Flavor_Archive_Renderer')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-archive-renderer.php';
        }
        $renderer = new Flavor_Archive_Renderer();

        return '<div class="flavor-widget-destacados">' .
            $renderer->render_auto($module_slug, [
                'items'       => $items,
                'per_page'    => intval($atts['limit']),
                'columns'     => intval($atts['columns']),
                'show_header' => false,
                'show_filters'=> false,
                'color'       => $color,
                'title'       => $title,
            ]) .
            '</div>';
    }

    /**
     * Widget: Próximo item de un módulo
     *
     * Uso: [flavor_proximo module="eventos" title="Próximo evento"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_widget_proximo($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'title'  => '',
            'color'  => '',
            'show_countdown' => 'yes',
        ], $atts);

        $module_slug = sanitize_title($atts['module']);
        if (empty($module_slug)) {
            return '';
        }

        // Obtener configuración del módulo
        $config = $this->get_module_renderer_config($module_slug);

        $color = $atts['color'] ?: ($config['color'] ?? 'primary');
        $title = $atts['title'] ?: sprintf(__('Próximo %s', 'flavor-chat-ia'), $config['title_singular'] ?? $config['title'] ?? ucfirst($module_slug));
        $icon = $config['icon'] ?? '📅';

        // Obtener próximo item
        $item = $this->get_module_next_item($module_slug);

        if (empty($item)) {
            // Mostrar widget CTA para crear
            $cta = [
                'icon' => $icon,
                'title' => $title,
                'description' => __('No hay próximos eventos programados', 'flavor-chat-ia'),
                'label' => __('Ver calendario', 'flavor-chat-ia'),
                'url' => home_url('/' . $module_slug . '/'),
            ];

            ob_start();
            $type = 'cta';
            include FLAVOR_CHAT_IA_PATH . 'templates/components/shared/sidebar-widget.php';
            return ob_get_clean();
        }

        // Preparar datos del item
        $items = [
            ['icon' => '📅', 'label' => __('Fecha', 'flavor-chat-ia'), 'value' => date_i18n('j M Y, H:i', strtotime($item['date'] ?? ''))],
            ['icon' => '📍', 'label' => __('Lugar', 'flavor-chat-ia'), 'value' => $item['location'] ?? $item['ubicacion'] ?? '-'],
        ];

        $cta = [
            'label' => __('Ver detalles', 'flavor-chat-ia'),
            'url' => $item['url'] ?? '#',
        ];

        ob_start();
        $type = 'info';
        include FLAVOR_CHAT_IA_PATH . 'templates/components/shared/sidebar-widget.php';
        return ob_get_clean();
    }

    /**
     * Widget: Mi resumen personal de un módulo
     *
     * Uso: [flavor_mi_resumen module="banco-tiempo" title="Mi saldo"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_widget_mi_resumen($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'title'  => '',
            'color'  => '',
        ], $atts);

        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }

        $module_slug = sanitize_title($atts['module']);
        if (empty($module_slug)) {
            return '';
        }

        // Obtener configuración del módulo
        $config = $this->get_module_renderer_config($module_slug);

        $color = $atts['color'] ?: ($config['color'] ?? 'primary');
        $title = $atts['title'] ?: sprintf(__('Mi %s', 'flavor-chat-ia'), $config['title'] ?? ucfirst($module_slug));
        $icon = $config['icon'] ?? '👤';

        // Obtener stats del usuario para este módulo
        $user_stats = $this->get_user_module_stats($module_slug, get_current_user_id());

        if (empty($user_stats)) {
            // Stats genéricos
            $user_stats = [
                ['icon' => '📊', 'label' => __('Total', 'flavor-chat-ia'), 'value' => 0],
                ['icon' => '⏳', 'label' => __('Pendientes', 'flavor-chat-ia'), 'value' => 0],
            ];
        }

        $cta = [
            'label' => __('Ver todo', 'flavor-chat-ia'),
            'url' => home_url('/mi-portal/' . $module_slug . '/'),
        ];

        ob_start();
        $items = $user_stats;
        $type = 'stats';
        include FLAVOR_CHAT_IA_PATH . 'templates/components/shared/sidebar-widget.php';
        return ob_get_clean();
    }

    /**
     * Obtiene los items recientes de un módulo
     */
    private function get_module_recent_items($module_slug, $limit = 5) {
        $module_class = $this->get_module_class($module_slug);

        // Si el módulo tiene método específico, usarlo
        if ($module_class && method_exists($module_class, 'get_recent_items')) {
            return $module_class::get_recent_items($limit);
        }

        // Fallback: buscar post type
        $post_types = [
            'eventos' => 'flavor_evento',
            'cursos' => 'flavor_curso',
            'talleres' => 'flavor_taller',
            'marketplace' => 'flavor_anuncio',
            'incidencias' => 'flavor_incidencia',
            'biblioteca' => 'flavor_libro',
            'podcast' => 'flavor_podcast',
        ];

        $post_type = $post_types[$module_slug] ?? '';
        if (empty($post_type) || !post_type_exists($post_type)) {
            return [];
        }

        $posts = get_posts([
            'post_type'      => $post_type,
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        ]);

        $items = [];
        foreach ($posts as $post) {
            $items[] = [
                'title' => $post->post_title,
                'date'  => $post->post_date,
                'url'   => get_permalink($post->ID),
            ];
        }

        return $items;
    }

    /**
     * Obtiene los items destacados de un módulo
     */
    private function get_module_featured_items($module_slug, $limit = 3) {
        $module_class = $this->get_module_class($module_slug);

        // Si el módulo tiene método específico, usarlo
        if ($module_class && method_exists($module_class, 'get_featured_items')) {
            return $module_class::get_featured_items($limit);
        }

        // Fallback: buscar posts con meta _featured o sticky
        $post_types = [
            'eventos' => 'flavor_evento',
            'cursos' => 'flavor_curso',
            'talleres' => 'flavor_taller',
        ];

        $post_type = $post_types[$module_slug] ?? '';
        if (empty($post_type) || !post_type_exists($post_type)) {
            return [];
        }

        $posts = get_posts([
            'post_type'      => $post_type,
            'posts_per_page' => $limit,
            'meta_key'       => '_featured',
            'meta_value'     => '1',
            'post_status'    => 'publish',
        ]);

        // Si no hay destacados, devolver los más recientes
        if (empty($posts)) {
            return $this->get_module_recent_items($module_slug, $limit);
        }

        return $posts;
    }

    /**
     * Obtiene el próximo item de un módulo (basado en fecha)
     */
    private function get_module_next_item($module_slug) {
        $module_class = $this->get_module_class($module_slug);

        // Si el módulo tiene método específico, usarlo
        if ($module_class && method_exists($module_class, 'get_next_item')) {
            return $module_class::get_next_item();
        }

        // Módulos con fechas
        $date_fields = [
            'eventos'   => 'fecha_inicio',
            'talleres'  => 'fecha_inicio',
            'cursos'    => 'fecha_inicio',
            'reservas'  => 'fecha',
        ];

        $post_types = [
            'eventos'  => 'flavor_evento',
            'talleres' => 'flavor_taller',
            'cursos'   => 'flavor_curso',
        ];

        $post_type = $post_types[$module_slug] ?? '';
        $date_field = $date_fields[$module_slug] ?? '';

        if (empty($post_type) || empty($date_field) || !post_type_exists($post_type)) {
            return null;
        }

        $posts = get_posts([
            'post_type'      => $post_type,
            'posts_per_page' => 1,
            'meta_key'       => $date_field,
            'meta_value'     => date('Y-m-d'),
            'meta_compare'   => '>=',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ]);

        if (empty($posts)) {
            return null;
        }

        $post = $posts[0];
        return [
            'title'    => $post->post_title,
            'date'     => get_post_meta($post->ID, $date_field, true),
            'location' => get_post_meta($post->ID, 'ubicacion', true),
            'url'      => get_permalink($post->ID),
        ];
    }

    /**
     * Obtiene estadísticas del usuario para un módulo
     */
    private function get_user_module_stats($module_slug, $user_id) {
        $module_class = $this->get_module_class($module_slug);

        // Si el módulo tiene método específico, usarlo
        if ($module_class && method_exists($module_class, 'get_user_stats')) {
            return $module_class::get_user_stats($user_id);
        }

        // Stats genéricos basados en tipo de módulo
        $stats_config = [
            'banco-tiempo' => [
                ['icon' => '⏰', 'label' => __('Horas ofrecidas', 'flavor-chat-ia'), 'value' => 0],
                ['icon' => '🤝', 'label' => __('Intercambios', 'flavor-chat-ia'), 'value' => 0],
                ['icon' => '💰', 'label' => __('Saldo', 'flavor-chat-ia'), 'value' => '0h'],
            ],
            'marketplace' => [
                ['icon' => '📦', 'label' => __('Anuncios activos', 'flavor-chat-ia'), 'value' => 0],
                ['icon' => '💬', 'label' => __('Mensajes', 'flavor-chat-ia'), 'value' => 0],
            ],
            'eventos' => [
                ['icon' => '🎫', 'label' => __('Inscritos', 'flavor-chat-ia'), 'value' => 0],
                ['icon' => '📅', 'label' => __('Próximos', 'flavor-chat-ia'), 'value' => 0],
            ],
            'incidencias' => [
                ['icon' => '📝', 'label' => __('Reportadas', 'flavor-chat-ia'), 'value' => 0],
                ['icon' => '✅', 'label' => __('Resueltas', 'flavor-chat-ia'), 'value' => 0],
            ],
        ];

        return $stats_config[$module_slug] ?? [];
    }
}
