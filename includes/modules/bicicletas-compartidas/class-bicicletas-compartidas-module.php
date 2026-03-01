<?php
/**
 * Módulo de Bicicletas Compartidas para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Bicicletas Compartidas - Sistema de bici-sharing comunitario
 */
class Flavor_Chat_Bicicletas_Compartidas_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'bicicletas_compartidas';
        $this->name = 'Bicicletas Compartidas'; // Translation loaded on init
        $this->description = 'Sistema de bicicletas compartidas gestionado por la comunidad.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        return Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Bicicletas Compartidas no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'requiere_fianza' => true,
            'importe_fianza' => 50,
            'precio_hora' => 0,
            'precio_dia' => 0,
            'precio_mes' => 10,
            'duracion_maxima_prestamo_dias' => 7,
            'permite_reservas' => true,
            'horas_anticipacion_reserva' => 2,
            'requiere_verificacion_usuario' => true,
            'notificar_mantenimiento' => true,
            'permite_reportar_problemas' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en panel unificado de gestión
        $this->registrar_en_panel_unificado();

        // Inicializar Dashboard Tab para el área de usuario
        $this->inicializar_dashboard_tab();

        // AJAX handlers para shortcodes
        add_action('wp_ajax_bicicletas_reservar', [$this, 'ajax_reservar_bicicleta']);
        add_action('wp_ajax_nopriv_bicicletas_reservar', [$this, 'ajax_login_required']);

        // Enqueue assets frontend
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_frontend_assets']);
    }

    /**
     * Registra los shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('bicicletas_mapa', [$this, 'shortcode_mapa']);
        add_shortcode('bicicletas_estaciones', [$this, 'shortcode_estaciones']);
        add_shortcode('bicicletas_reservar', [$this, 'shortcode_reservar']);
        add_shortcode('bicicletas_mis_viajes', [$this, 'shortcode_mis_viajes']);
        add_shortcode('bicicletas_estadisticas', [$this, 'shortcode_estadisticas']);
        add_shortcode('bicicletas_tarifas', [$this, 'shortcode_tarifas']);
    }

    /**
     * Verifica si se deben cargar los assets del módulo
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'bicicletas_mapa',
            'bicicletas_estaciones',
            'bicicletas_reservar',
            'bicicletas_mis_viajes',
            'bicicletas_estadisticas',
            'bicicletas_tarifas',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encola assets frontend solo cuando se usan shortcodes
     */
    public function maybe_enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $assets_url = plugin_dir_url(__FILE__) . 'assets/';
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style(
            'bicicletas-frontend',
            $assets_url . 'css/bicicletas-frontend.css',
            [],
            $version
        );

        wp_enqueue_script(
            'bicicletas-frontend',
            $assets_url . 'js/bicicletas-frontend.js',
            ['jquery'],
            $version,
            true
        );

        // Leaflet para mapas
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

        wp_localize_script('bicicletas-frontend', 'bicicletasData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bicicletas_frontend'),
            'user_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'strings' => [
                'error_general' => __('Ha ocurrido un error. Inténtalo de nuevo.', 'flavor-chat-ia'),
                'confirmar_reserva' => __('¿Deseas reservar esta bicicleta?', 'flavor-chat-ia'),
                'reserva_confirmada' => __('Tu reserva ha sido confirmada.', 'flavor-chat-ia'),
                'viaje_iniciado' => __('Tu viaje ha comenzado.', 'flavor-chat-ia'),
                'viaje_finalizado' => __('Tu viaje ha finalizado correctamente.', 'flavor-chat-ia'),
                'login_requerido' => __('Debes iniciar sesión para realizar esta acción.', 'flavor-chat-ia'),
            ]
        ]);
    }

    /**
     * Inicializa el Dashboard Tab para el área de usuario frontend
     *
     * Registra tabs en "Mi Cuenta" para mostrar:
     * - Historial de viajes del usuario
     * - Información de cuenta (saldo, plan activo)
     * - Estadísticas personales (km, CO2 ahorrado)
     */
    private function inicializar_dashboard_tab() {
        $ruta_archivo_dashboard_tab = __DIR__ . '/class-bicicletas-dashboard-tab.php';

        if (file_exists($ruta_archivo_dashboard_tab)) {
            require_once $ruta_archivo_dashboard_tab;

            if (class_exists('Flavor_Bicicletas_Dashboard_Tab')) {
                Flavor_Bicicletas_Dashboard_Tab::get_instance();
            }
        }
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Mapa con estaciones y disponibilidad
     * Uso: [bicicletas_mapa altura="500" zoom="14" lat="" lng=""]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del mapa
     */
    public function shortcode_mapa($atts) {
        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $atributos = shortcode_atts([
            'altura' => 500,
            'zoom' => 14,
            'lat' => '',
            'lng' => '',
            'mostrar_leyenda' => 'yes',
        ], $atts);

        // Obtener estaciones activas
        $estaciones = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_estaciones)) {
            $estaciones = $wpdb->get_results(
                "SELECT id, nombre, direccion, latitud, longitud, capacidad_total, bicicletas_disponibles, estado, tipo
                 FROM $tabla_estaciones
                 WHERE estado = 'activa'
                 ORDER BY nombre ASC"
            );
        }

        // Calcular centro del mapa
        $centro_lat = !empty($atributos['lat']) ? floatval($atributos['lat']) : 40.4168;
        $centro_lng = !empty($atributos['lng']) ? floatval($atributos['lng']) : -3.7038;

        if (empty($atributos['lat']) && !empty($estaciones)) {
            $suma_lat = 0;
            $suma_lng = 0;
            foreach ($estaciones as $estacion) {
                $suma_lat += floatval($estacion->latitud);
                $suma_lng += floatval($estacion->longitud);
            }
            $centro_lat = $suma_lat / count($estaciones);
            $centro_lng = $suma_lng / count($estaciones);
        }

        $mapa_id = 'bicicletas-mapa-' . wp_rand(1000, 9999);

        ob_start();
        ?>
        <div class="flavor-bicicletas-mapa-wrapper">
            <div class="bg-gradient-to-r from-green-500 to-teal-500 rounded-t-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🚲</span>
                        <div>
                            <h3 class="text-lg font-bold"><?php esc_html_e('Estaciones de Bicicletas', 'flavor-chat-ia'); ?></h3>
                            <p class="text-sm opacity-90"><?php echo esc_html(count($estaciones)); ?> <?php esc_html_e('estaciones activas', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>
                    <button type="button" class="bg-white/20 hover:bg-white/30 px-3 py-1 rounded-lg text-sm" onclick="flavorBicicletasGeolocate('<?php echo esc_js($mapa_id); ?>')">
                        📍 <?php esc_html_e('Mi ubicación', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>

            <div id="<?php echo esc_attr($mapa_id); ?>" class="flavor-leaflet-map rounded-b-xl shadow-lg" style="height: <?php echo esc_attr(intval($atributos['altura'])); ?>px;"></div>

            <?php if ($atributos['mostrar_leyenda'] === 'yes'): ?>
            <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-600">
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-500 rounded-full"></span> <?php esc_html_e('Alta disponibilidad', 'flavor-chat-ia'); ?></span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-yellow-500 rounded-full"></span> <?php esc_html_e('Pocas bicis', 'flavor-chat-ia'); ?></span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-red-500 rounded-full"></span> <?php esc_html_e('Sin bicis', 'flavor-chat-ia'); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L === 'undefined') {
                console.warn('Leaflet no está cargado');
                return;
            }

            var mapEl = document.getElementById('<?php echo esc_js($mapa_id); ?>');
            if (!mapEl || mapEl._leaflet_id) return;

            var map = L.map('<?php echo esc_js($mapa_id); ?>').setView([<?php echo esc_js($centro_lat); ?>, <?php echo esc_js($centro_lng); ?>], <?php echo esc_js(intval($atributos['zoom'])); ?>);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>'
            }).addTo(map);

            // Función para color según disponibilidad
            function getMarkerColor(disponibles, capacidad) {
                var porcentaje = (disponibles / capacidad) * 100;
                if (porcentaje >= 50) return '#22c55e'; // verde
                if (porcentaje >= 20) return '#eab308'; // amarillo
                return '#ef4444'; // rojo
            }

            // Añadir marcadores de estaciones
            var estaciones = <?php echo wp_json_encode(array_map(function($estacion) {
                return [
                    'id' => intval($estacion->id),
                    'nombre' => $estacion->nombre,
                    'direccion' => $estacion->direccion,
                    'lat' => floatval($estacion->latitud),
                    'lng' => floatval($estacion->longitud),
                    'capacidad' => intval($estacion->capacidad_total),
                    'disponibles' => intval($estacion->bicicletas_disponibles),
                    'tipo' => $estacion->tipo,
                ];
            }, $estaciones)); ?>;

            estaciones.forEach(function(est) {
                var color = getMarkerColor(est.disponibles, est.capacidad);
                var icono = L.divIcon({
                    html: '<div style="background:' + color + '; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);">🚲</div>',
                    className: 'flavor-bici-marker',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                var marcador = L.marker([est.lat, est.lng], { icon: icono }).addTo(map);

                var popupContent = '<div class="flavor-bici-popup">' +
                    '<strong>' + est.nombre + '</strong><br>' +
                    '<span class="text-gray-600">' + est.direccion + '</span><br>' +
                    '<div style="margin-top:8px; padding:8px; background:#f3f4f6; border-radius:8px;">' +
                    '<span style="font-size:1.5em; font-weight:bold; color:' + color + ';">' + est.disponibles + '</span> / ' + est.capacidad + ' bicis<br>' +
                    '<small>' + (est.capacidad - est.disponibles) + ' huecos libres</small>' +
                    '</div>' +
                    '<a href="?estacion_id=' + est.id + '" class="inline-block mt-2 px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600">Ver bicicletas</a>' +
                    '</div>';

                marcador.bindPopup(popupContent);
            });

            // Guardar referencia al mapa
            window['flavorBicicletasMap_<?php echo esc_js($mapa_id); ?>'] = map;
        });

        // Función para geolocalización
        function flavorBicicletasGeolocate(mapId) {
            var map = window['flavorBicicletasMap_' + mapId];
            if (!map) return;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    map.setView([lat, lng], 15);

                    L.marker([lat, lng], {
                        icon: L.divIcon({
                            html: '<div style="background:#3b82f6; width:20px; height:20px; border-radius:50%; border:3px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>',
                            className: 'flavor-user-marker',
                            iconSize: [20, 20],
                            iconAnchor: [10, 10]
                        })
                    }).addTo(map).bindPopup('<?php echo esc_js(__('Tu ubicación', 'flavor-chat-ia')); ?>');
                }, function(err) {
                    alert('<?php echo esc_js(__('No se pudo obtener tu ubicación', 'flavor-chat-ia')); ?>');
                });
            }
        }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Lista de estaciones con bicis disponibles
     * Uso: [bicicletas_estaciones limite="12" columnas="3" ordenar="nombre"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML de las estaciones
     */
    public function shortcode_estaciones($atts) {
        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        $atributos = shortcode_atts([
            'limite' => 12,
            'columnas' => 3,
            'ordenar' => 'nombre', // nombre, disponibles, distancia
            'mostrar_vacia' => 'no',
        ], $atts);

        $estaciones = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_estaciones)) {
            $orden_sql = 'nombre ASC';
            if ($atributos['ordenar'] === 'disponibles') {
                $orden_sql = 'bicicletas_disponibles DESC';
            }

            $condicion_disponibilidad = '';
            if ($atributos['mostrar_vacia'] === 'no') {
                $condicion_disponibilidad = 'AND bicicletas_disponibles > 0';
            }

            $estaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_estaciones
                 WHERE estado = 'activa' $condicion_disponibilidad
                 ORDER BY $orden_sql
                 LIMIT %d",
                intval($atributos['limite'])
            ));
        }

        $columnas_clase = $this->get_grid_columns_class(intval($atributos['columnas']));

        ob_start();
        ?>
        <div class="flavor-bicicletas-estaciones">
            <div class="bg-gradient-to-r from-green-500 to-teal-500 rounded-t-xl p-4 text-white mb-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📍</span>
                    <div>
                        <h3 class="text-lg font-bold"><?php esc_html_e('Estaciones de Bicicletas', 'flavor-chat-ia'); ?></h3>
                        <p class="text-sm opacity-90"><?php echo esc_html(count($estaciones)); ?> <?php esc_html_e('con bicicletas disponibles', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>

            <?php if (empty($estaciones)): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
                    <span class="text-4xl mb-2 block">🚲</span>
                    <p class="text-yellow-700"><?php esc_html_e('No hay estaciones con bicicletas disponibles en este momento.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="grid <?php echo esc_attr($columnas_clase); ?> gap-4">
                    <?php foreach ($estaciones as $estacion):
                        $porcentaje_ocupacion = ($estacion->bicicletas_disponibles / max(1, $estacion->capacidad_total)) * 100;
                        $color_barra = $porcentaje_ocupacion >= 50 ? 'bg-green-500' : ($porcentaje_ocupacion >= 20 ? 'bg-yellow-500' : 'bg-red-500');
                        $estado_texto = $porcentaje_ocupacion >= 50 ? __('Alta disponibilidad', 'flavor-chat-ia') : ($porcentaje_ocupacion >= 20 ? __('Pocas bicis', 'flavor-chat-ia') : __('Casi vacía', 'flavor-chat-ia'));
                    ?>
                        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow border border-gray-100 overflow-hidden">
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-800"><?php echo esc_html($estacion->nombre); ?></h4>
                                        <p class="text-sm text-gray-500"><?php echo esc_html($estacion->direccion); ?></p>
                                    </div>
                                    <span class="text-2xl">🚲</span>
                                </div>

                                <div class="flex items-center gap-4 mb-3">
                                    <div class="text-center">
                                        <span class="text-3xl font-bold text-gray-800"><?php echo esc_html($estacion->bicicletas_disponibles); ?></span>
                                        <p class="text-xs text-gray-500"><?php esc_html_e('disponibles', 'flavor-chat-ia'); ?></p>
                                    </div>
                                    <div class="flex-1">
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full <?php echo esc_attr($color_barra); ?> rounded-full transition-all" style="width: <?php echo esc_attr($porcentaje_ocupacion); ?>%;"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($estado_texto); ?></p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">
                                        <?php echo esc_html($estacion->capacidad_total - $estacion->bicicletas_disponibles); ?> <?php esc_html_e('huecos libres', 'flavor-chat-ia'); ?>
                                    </span>
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">
                                        <?php echo esc_html(ucfirst($estacion->tipo)); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 p-3 bg-gray-50">
                                <a href="?estacion_id=<?php echo esc_attr($estacion->id); ?>" class="block w-full text-center py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition-colors">
                                    <?php esc_html_e('Ver bicicletas', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario para reservar bicicleta
     * Uso: [bicicletas_reservar estacion_id=""]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del formulario
     */
    public function shortcode_reservar($atts) {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        // Verificar login
        if (!is_user_logged_in()) {
            return $this->render_login_required('reservar una bicicleta');
        }

        $atributos = shortcode_atts([
            'estacion_id' => isset($_GET['estacion_id']) ? intval($_GET['estacion_id']) : 0,
        ], $atts);

        $estacion_seleccionada_id = intval($atributos['estacion_id']);

        // Obtener estaciones
        $estaciones = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_estaciones)) {
            $estaciones = $wpdb->get_results(
                "SELECT id, nombre, direccion, bicicletas_disponibles FROM $tabla_estaciones WHERE estado = 'activa' AND bicicletas_disponibles > 0 ORDER BY nombre"
            );
        }

        // Obtener bicicletas de la estación seleccionada
        $bicicletas_disponibles = [];
        if ($estacion_seleccionada_id > 0 && Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
            $bicicletas_disponibles = $wpdb->get_results($wpdb->prepare(
                "SELECT id, codigo, tipo, marca, modelo, talla, color FROM $tabla_bicicletas WHERE estacion_actual_id = %d AND estado = 'disponible' ORDER BY tipo, codigo",
                $estacion_seleccionada_id
            ));
        }

        // Verificar si el usuario ya tiene un préstamo activo
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $usuario_id = get_current_user_id();
        $prestamo_activo = null;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            $prestamo_activo = $wpdb->get_row($wpdb->prepare(
                "SELECT p.*, b.codigo as bicicleta_codigo FROM $tabla_prestamos p
                 LEFT JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
                 WHERE p.usuario_id = %d AND p.estado = 'activo'",
                $usuario_id
            ));
        }

        // Configuración de fianza
        $requiere_fianza = $this->get_setting('requiere_fianza', true);
        $importe_fianza = $this->get_setting('importe_fianza', 50);

        ob_start();
        ?>
        <div class="flavor-bicicletas-reservar">
            <div class="bg-gradient-to-r from-green-500 to-teal-500 rounded-t-xl p-4 text-white">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🚲</span>
                    <div>
                        <h3 class="text-lg font-bold"><?php esc_html_e('Reservar Bicicleta', 'flavor-chat-ia'); ?></h3>
                        <p class="text-sm opacity-90"><?php esc_html_e('Selecciona una bicicleta disponible', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-b-xl shadow-lg p-6">
                <?php if ($prestamo_activo): ?>
                    <!-- Usuario ya tiene un préstamo activo -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
                        <span class="text-4xl mb-3 block">🚴</span>
                        <h4 class="font-bold text-blue-800 mb-2"><?php esc_html_e('Ya tienes una bicicleta en uso', 'flavor-chat-ia'); ?></h4>
                        <p class="text-blue-600 mb-4">
                            <?php printf(
                                esc_html__('Bicicleta %s desde %s', 'flavor-chat-ia'),
                                '<strong>' . esc_html($prestamo_activo->bicicleta_codigo) . '</strong>',
                                esc_html(date_i18n('d/m/Y H:i', strtotime($prestamo_activo->fecha_inicio)))
                            ); ?>
                        </p>
                        <a href="?accion=devolver" class="inline-block px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium">
                            <?php esc_html_e('Devolver bicicleta', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <form id="form-reservar-bicicleta" class="space-y-6">
                        <?php wp_nonce_field('bicicletas_reservar', 'bicicletas_nonce'); ?>

                        <!-- Paso 1: Seleccionar estación -->
                        <div class="form-group">
                            <label for="estacion_id" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php esc_html_e('1. Selecciona una estación', 'flavor-chat-ia'); ?>
                            </label>
                            <select name="estacion_id" id="estacion_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                <option value=""><?php esc_html_e('-- Elige una estación --', 'flavor-chat-ia'); ?></option>
                                <?php foreach ($estaciones as $estacion): ?>
                                    <option value="<?php echo esc_attr($estacion->id); ?>" <?php selected($estacion_seleccionada_id, $estacion->id); ?>>
                                        <?php echo esc_html($estacion->nombre); ?> (<?php echo esc_html($estacion->bicicletas_disponibles); ?> <?php esc_html_e('bicis', 'flavor-chat-ia'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Paso 2: Seleccionar bicicleta -->
                        <div class="form-group" id="bicicletas-container" style="<?php echo empty($bicicletas_disponibles) ? 'display:none;' : ''; ?>">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php esc_html_e('2. Selecciona una bicicleta', 'flavor-chat-ia'); ?>
                            </label>
                            <div id="bicicletas-lista" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php foreach ($bicicletas_disponibles as $bicicleta): ?>
                                    <label class="bicicleta-opcion flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-400 transition-colors">
                                        <input type="radio" name="bicicleta_id" value="<?php echo esc_attr($bicicleta->id); ?>" class="sr-only" required>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-800"><?php echo esc_html($bicicleta->codigo); ?></span>
                                                <span class="px-2 py-0.5 bg-gray-100 rounded text-xs"><?php echo esc_html(ucfirst($bicicleta->tipo)); ?></span>
                                            </div>
                                            <p class="text-sm text-gray-500"><?php echo esc_html($bicicleta->marca . ' ' . $bicicleta->modelo); ?></p>
                                            <p class="text-xs text-gray-400"><?php esc_html_e('Talla:', 'flavor-chat-ia'); ?> <?php echo esc_html($bicicleta->talla); ?> | <?php esc_html_e('Color:', 'flavor-chat-ia'); ?> <?php echo esc_html($bicicleta->color); ?></p>
                                        </div>
                                        <span class="text-2xl">🚲</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Información de fianza -->
                        <?php if ($requiere_fianza): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <span class="text-xl">💰</span>
                                <div>
                                    <h5 class="font-medium text-yellow-800"><?php esc_html_e('Fianza requerida', 'flavor-chat-ia'); ?></h5>
                                    <p class="text-sm text-yellow-700">
                                        <?php printf(
                                            esc_html__('Se requiere una fianza de %s€ que será devuelta al entregar la bicicleta en buen estado.', 'flavor-chat-ia'),
                                            esc_html(number_format($importe_fianza, 2, ',', '.'))
                                        ); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Términos y condiciones -->
                        <div class="form-group">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="acepto_terminos" id="acepto_terminos" required class="mt-1 w-4 h-4 text-green-500 border-gray-300 rounded focus:ring-green-500">
                                <span class="text-sm text-gray-600">
                                    <?php esc_html_e('Acepto las condiciones de uso del servicio de bicicletas compartidas y me comprometo a devolver la bicicleta en buen estado.', 'flavor-chat-ia'); ?>
                                </span>
                            </label>
                        </div>

                        <!-- Botón de reserva -->
                        <div class="pt-4">
                            <button type="submit" id="btn-reservar" class="w-full py-3 px-6 bg-green-500 hover:bg-green-600 text-white rounded-xl font-bold text-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="normal-state"><?php esc_html_e('Reservar Bicicleta', 'flavor-chat-ia'); ?> 🚲</span>
                                <span class="loading-state hidden"><?php esc_html_e('Procesando...', 'flavor-chat-ia'); ?></span>
                            </button>
                        </div>

                        <div id="reserva-mensaje" class="hidden"></div>
                    </form>

                    <script>
                    (function() {
                        var form = document.getElementById('form-reservar-bicicleta');
                        var estacionSelect = document.getElementById('estacion_id');
                        var bicicletasContainer = document.getElementById('bicicletas-container');
                        var bicicletasLista = document.getElementById('bicicletas-lista');
                        var btnReservar = document.getElementById('btn-reservar');
                        var mensajeDiv = document.getElementById('reserva-mensaje');

                        // Cambio de estación: cargar bicicletas
                        estacionSelect.addEventListener('change', function() {
                            var estacionId = this.value;
                            if (!estacionId) {
                                bicicletasContainer.style.display = 'none';
                                return;
                            }

                            bicicletasLista.innerHTML = '<div class="col-span-2 text-center py-4"><span class="animate-spin inline-block">⏳</span> <?php echo esc_js(__('Cargando bicicletas...', 'flavor-chat-ia')); ?></div>';
                            bicicletasContainer.style.display = 'block';

                            fetch('<?php echo esc_url(rest_url('flavor/v1/bicicletas')); ?>?estacion_id=' + estacionId + '&estado=disponible')
                                .then(function(r) { return r.json(); })
                                .then(function(data) {
                                    if (data.success && data.bicicletas.length > 0) {
                                        var html = '';
                                        data.bicicletas.forEach(function(bici) {
                                            html += '<label class="bicicleta-opcion flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-400 transition-colors">' +
                                                '<input type="radio" name="bicicleta_id" value="' + bici.id + '" class="sr-only" required>' +
                                                '<div class="flex-1">' +
                                                '<div class="flex items-center gap-2">' +
                                                '<span class="font-bold text-gray-800">' + bici.codigo + '</span>' +
                                                '<span class="px-2 py-0.5 bg-gray-100 rounded text-xs">' + bici.tipo + '</span>' +
                                                '</div>' +
                                                '<p class="text-sm text-gray-500">' + (bici.marca || '') + ' ' + (bici.modelo || '') + '</p>' +
                                                '<p class="text-xs text-gray-400">Talla: ' + (bici.talla || '-') + ' | Color: ' + (bici.color || '-') + '</p>' +
                                                '</div>' +
                                                '<span class="text-2xl">🚲</span>' +
                                                '</label>';
                                        });
                                        bicicletasLista.innerHTML = html;
                                        initBicicletaOpciones();
                                    } else {
                                        bicicletasLista.innerHTML = '<div class="col-span-2 text-center py-4 text-gray-500"><?php echo esc_js(__('No hay bicicletas disponibles en esta estación', 'flavor-chat-ia')); ?></div>';
                                    }
                                })
                                .catch(function(err) {
                                    bicicletasLista.innerHTML = '<div class="col-span-2 text-center py-4 text-red-500"><?php echo esc_js(__('Error al cargar bicicletas', 'flavor-chat-ia')); ?></div>';
                                });
                        });

                        // Selección visual de bicicleta
                        function initBicicletaOpciones() {
                            document.querySelectorAll('.bicicleta-opcion').forEach(function(label) {
                                label.addEventListener('click', function() {
                                    document.querySelectorAll('.bicicleta-opcion').forEach(function(l) {
                                        l.classList.remove('border-green-500', 'bg-green-50');
                                    });
                                    this.classList.add('border-green-500', 'bg-green-50');
                                });
                            });
                        }
                        initBicicletaOpciones();

                        // Envío del formulario
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();

                            var bicicletaId = form.querySelector('input[name="bicicleta_id"]:checked');
                            if (!bicicletaId) {
                                alert('<?php echo esc_js(__('Selecciona una bicicleta', 'flavor-chat-ia')); ?>');
                                return;
                            }

                            btnReservar.disabled = true;
                            btnReservar.querySelector('.normal-state').classList.add('hidden');
                            btnReservar.querySelector('.loading-state').classList.remove('hidden');

                            var formData = new FormData();
                            formData.append('action', 'bicicletas_reservar');
                            formData.append('bicicleta_id', bicicletaId.value);
                            formData.append('bicicletas_nonce', form.querySelector('[name="bicicletas_nonce"]').value);

                            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                                method: 'POST',
                                body: formData
                            })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (data.success) {
                                    mensajeDiv.innerHTML = '<div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700"><strong>✓</strong> ' + data.data.mensaje + '</div>';
                                    mensajeDiv.classList.remove('hidden');
                                    form.reset();
                                    bicicletasContainer.style.display = 'none';
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 2000);
                                } else {
                                    mensajeDiv.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700"><strong>✗</strong> ' + (data.data || '<?php echo esc_js(__('Error al reservar', 'flavor-chat-ia')); ?>') + '</div>';
                                    mensajeDiv.classList.remove('hidden');
                                }
                            })
                            .catch(function(err) {
                                mensajeDiv.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700"><?php echo esc_js(__('Error de conexión', 'flavor-chat-ia')); ?></div>';
                                mensajeDiv.classList.remove('hidden');
                            })
                            .finally(function() {
                                btnReservar.disabled = false;
                                btnReservar.querySelector('.normal-state').classList.remove('hidden');
                                btnReservar.querySelector('.loading-state').classList.add('hidden');
                            });
                        });
                    })();
                    </script>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Historial de viajes del usuario
     * Uso: [bicicletas_mis_viajes limite="20" mostrar_activo="yes"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del historial
     */
    public function shortcode_mis_viajes($atts) {
        global $wpdb;

        // Verificar login
        if (!is_user_logged_in()) {
            return $this->render_login_required('ver tu historial de viajes');
        }

        $atributos = shortcode_atts([
            'limite' => 20,
            'mostrar_activo' => 'yes',
        ], $atts);

        $usuario_id = get_current_user_id();
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $prestamos = [];
        $prestamo_activo = null;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            // Obtener préstamo activo
            if ($atributos['mostrar_activo'] === 'yes') {
                $prestamo_activo = $wpdb->get_row($wpdb->prepare(
                    "SELECT p.*, b.codigo as bicicleta_codigo, b.tipo as bicicleta_tipo, b.marca, b.modelo,
                            es.nombre as estacion_salida_nombre
                     FROM $tabla_prestamos p
                     LEFT JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
                     LEFT JOIN $tabla_estaciones es ON p.estacion_salida_id = es.id
                     WHERE p.usuario_id = %d AND p.estado = 'activo'",
                    $usuario_id
                ));
            }

            // Obtener historial
            $prestamos = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, b.codigo as bicicleta_codigo, b.tipo as bicicleta_tipo,
                        es.nombre as estacion_salida_nombre, el.nombre as estacion_llegada_nombre
                 FROM $tabla_prestamos p
                 LEFT JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
                 LEFT JOIN $tabla_estaciones es ON p.estacion_salida_id = es.id
                 LEFT JOIN $tabla_estaciones el ON p.estacion_llegada_id = el.id
                 WHERE p.usuario_id = %d AND p.estado = 'finalizado'
                 ORDER BY p.fecha_fin DESC
                 LIMIT %d",
                $usuario_id,
                intval($atributos['limite'])
            ));
        }

        // Calcular totales
        $total_viajes = count($prestamos);
        $total_km = 0;
        $total_minutos = 0;
        foreach ($prestamos as $prestamo) {
            $total_km += floatval($prestamo->kilometros_recorridos);
            $total_minutos += intval($prestamo->duracion_minutos);
        }

        ob_start();
        ?>
        <div class="flavor-bicicletas-mis-viajes">
            <div class="bg-gradient-to-r from-green-500 to-teal-500 rounded-t-xl p-4 text-white">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📋</span>
                    <div>
                        <h3 class="text-lg font-bold"><?php esc_html_e('Mis Viajes en Bicicleta', 'flavor-chat-ia'); ?></h3>
                        <p class="text-sm opacity-90"><?php echo esc_html($total_viajes); ?> <?php esc_html_e('viajes realizados', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-b-xl shadow-lg">
                <?php if ($prestamo_activo): ?>
                    <!-- Viaje activo -->
                    <div class="p-4 bg-blue-50 border-b border-blue-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl animate-bounce">🚴</span>
                                <div>
                                    <span class="font-bold text-blue-800"><?php esc_html_e('Viaje en curso', 'flavor-chat-ia'); ?></span>
                                    <p class="text-sm text-blue-600">
                                        <?php echo esc_html($prestamo_activo->bicicleta_codigo); ?> -
                                        <?php printf(esc_html__('Desde %s', 'flavor-chat-ia'), esc_html($prestamo_activo->estacion_salida_nombre)); ?>
                                    </p>
                                    <p class="text-xs text-blue-500">
                                        <?php
                                        $inicio = strtotime($prestamo_activo->fecha_inicio);
                                        $duracion_actual = round((time() - $inicio) / 60);
                                        printf(
                                            esc_html__('Iniciado hace %s minutos', 'flavor-chat-ia'),
                                            esc_html($duracion_actual)
                                        );
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <a href="?accion=devolver" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium">
                                <?php esc_html_e('Devolver', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($prestamos) && !$prestamo_activo): ?>
                    <div class="p-8 text-center">
                        <span class="text-4xl mb-3 block">🚲</span>
                        <p class="text-gray-500"><?php esc_html_e('Aún no has realizado ningún viaje.', 'flavor-chat-ia'); ?></p>
                        <a href="?pagina=reservar" class="inline-block mt-4 px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg">
                            <?php esc_html_e('Reservar mi primera bicicleta', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Resumen -->
                    <div class="grid grid-cols-3 gap-4 p-4 border-b border-gray-100">
                        <div class="text-center">
                            <span class="text-2xl font-bold text-gray-800"><?php echo esc_html($total_viajes); ?></span>
                            <p class="text-xs text-gray-500"><?php esc_html_e('Viajes', 'flavor-chat-ia'); ?></p>
                        </div>
                        <div class="text-center">
                            <span class="text-2xl font-bold text-gray-800"><?php echo esc_html(number_format($total_km, 1)); ?></span>
                            <p class="text-xs text-gray-500"><?php esc_html_e('Km totales', 'flavor-chat-ia'); ?></p>
                        </div>
                        <div class="text-center">
                            <span class="text-2xl font-bold text-gray-800"><?php echo esc_html(round($total_minutos / 60, 1)); ?></span>
                            <p class="text-xs text-gray-500"><?php esc_html_e('Horas', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>

                    <!-- Lista de viajes -->
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($prestamos as $prestamo):
                            $duracion_texto = $prestamo->duracion_minutos < 60
                                ? $prestamo->duracion_minutos . ' min'
                                : round($prestamo->duracion_minutos / 60, 1) . ' h';
                        ?>
                            <div class="p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="text-xl">🚲</span>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-800"><?php echo esc_html($prestamo->bicicleta_codigo); ?></span>
                                                <span class="px-2 py-0.5 bg-gray-100 rounded text-xs"><?php echo esc_html(ucfirst($prestamo->bicicleta_tipo)); ?></span>
                                            </div>
                                            <p class="text-sm text-gray-500">
                                                <?php echo esc_html($prestamo->estacion_salida_nombre); ?>
                                                <?php if ($prestamo->estacion_llegada_nombre): ?>
                                                    → <?php echo esc_html($prestamo->estacion_llegada_nombre); ?>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($prestamo->fecha_inicio))); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-medium text-gray-700"><?php echo esc_html($duracion_texto); ?></span>
                                        <?php if ($prestamo->kilometros_recorridos > 0): ?>
                                            <p class="text-xs text-gray-500"><?php echo esc_html(number_format($prestamo->kilometros_recorridos, 1)); ?> km</p>
                                        <?php endif; ?>
                                        <?php if ($prestamo->valoracion): ?>
                                            <p class="text-xs text-yellow-500">
                                                <?php echo str_repeat('⭐', intval($prestamo->valoracion)); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas personales (km, CO2 ahorrado)
     * Uso: [bicicletas_estadisticas]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML de estadísticas
     */
    public function shortcode_estadisticas($atts) {
        global $wpdb;

        // Verificar login
        if (!is_user_logged_in()) {
            return $this->render_login_required('ver tus estadísticas');
        }

        $usuario_id = get_current_user_id();
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

        // Obtener estadísticas
        $estadisticas = [
            'total_viajes' => 0,
            'total_km' => 0,
            'total_minutos' => 0,
            'co2_ahorrado' => 0,
            'calorias_quemadas' => 0,
            'viajes_mes_actual' => 0,
            'km_mes_actual' => 0,
        ];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            // Totales históricos
            $totales = $wpdb->get_row($wpdb->prepare(
                "SELECT COUNT(*) as viajes, COALESCE(SUM(kilometros_recorridos), 0) as km, COALESCE(SUM(duracion_minutos), 0) as minutos
                 FROM $tabla_prestamos
                 WHERE usuario_id = %d AND estado = 'finalizado'",
                $usuario_id
            ));

            if ($totales) {
                $estadisticas['total_viajes'] = intval($totales->viajes);
                $estadisticas['total_km'] = floatval($totales->km);
                $estadisticas['total_minutos'] = intval($totales->minutos);
            }

            // Mes actual
            $mes_actual = $wpdb->get_row($wpdb->prepare(
                "SELECT COUNT(*) as viajes, COALESCE(SUM(kilometros_recorridos), 0) as km
                 FROM $tabla_prestamos
                 WHERE usuario_id = %d AND estado = 'finalizado' AND MONTH(fecha_fin) = MONTH(CURRENT_DATE()) AND YEAR(fecha_fin) = YEAR(CURRENT_DATE())",
                $usuario_id
            ));

            if ($mes_actual) {
                $estadisticas['viajes_mes_actual'] = intval($mes_actual->viajes);
                $estadisticas['km_mes_actual'] = floatval($mes_actual->km);
            }
        }

        // Calcular CO2 ahorrado (aprox. 120g CO2/km en coche)
        $estadisticas['co2_ahorrado'] = $estadisticas['total_km'] * 0.12;

        // Calcular calorías quemadas (aprox. 25 calorías/km en bicicleta)
        $estadisticas['calorias_quemadas'] = $estadisticas['total_km'] * 25;

        // Horas totales
        $horas_totales = round($estadisticas['total_minutos'] / 60, 1);

        // Árboles equivalentes (1 árbol absorbe ~22kg CO2/año)
        $arboles_equivalentes = round($estadisticas['co2_ahorrado'] / 22, 1);

        ob_start();
        ?>
        <div class="flavor-bicicletas-estadisticas">
            <div class="bg-gradient-to-r from-green-500 to-teal-500 rounded-t-xl p-6 text-white">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">📊</span>
                    <div>
                        <h3 class="text-xl font-bold"><?php esc_html_e('Tus Estadísticas de Ciclismo', 'flavor-chat-ia'); ?></h3>
                        <p class="text-sm opacity-90"><?php esc_html_e('Impacto positivo en el medio ambiente', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>

                <!-- KPIs principales -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur-sm">
                        <span class="text-3xl font-bold"><?php echo esc_html($estadisticas['total_viajes']); ?></span>
                        <p class="text-sm opacity-90"><?php esc_html_e('Viajes', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur-sm">
                        <span class="text-3xl font-bold"><?php echo esc_html(number_format($estadisticas['total_km'], 1)); ?></span>
                        <p class="text-sm opacity-90"><?php esc_html_e('Kilómetros', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur-sm">
                        <span class="text-3xl font-bold"><?php echo esc_html($horas_totales); ?></span>
                        <p class="text-sm opacity-90"><?php esc_html_e('Horas', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="bg-white/20 rounded-xl p-4 text-center backdrop-blur-sm">
                        <span class="text-3xl font-bold"><?php echo esc_html(number_format($estadisticas['co2_ahorrado'], 1)); ?></span>
                        <p class="text-sm opacity-90"><?php esc_html_e('kg CO₂ ahorrado', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-b-xl shadow-lg p-6">
                <!-- Impacto ambiental -->
                <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span>🌍</span> <?php esc_html_e('Tu Impacto Ambiental', 'flavor-chat-ia'); ?>
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-green-50 rounded-xl p-4 text-center">
                        <span class="text-4xl mb-2 block">🌳</span>
                        <span class="text-2xl font-bold text-green-700"><?php echo esc_html($arboles_equivalentes); ?></span>
                        <p class="text-sm text-green-600"><?php esc_html_e('Árboles equivalentes', 'flavor-chat-ia'); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php esc_html_e('en absorción de CO₂', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="bg-orange-50 rounded-xl p-4 text-center">
                        <span class="text-4xl mb-2 block">🔥</span>
                        <span class="text-2xl font-bold text-orange-700"><?php echo esc_html(number_format($estadisticas['calorias_quemadas'])); ?></span>
                        <p class="text-sm text-orange-600"><?php esc_html_e('Calorías quemadas', 'flavor-chat-ia'); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php esc_html_e('equivale a', 'flavor-chat-ia'); ?> <?php echo esc_html(round($estadisticas['calorias_quemadas'] / 280)); ?> 🍔</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-4 text-center">
                        <span class="text-4xl mb-2 block">💰</span>
                        <span class="text-2xl font-bold text-blue-700"><?php echo esc_html(number_format($estadisticas['total_km'] * 0.15, 2)); ?>€</span>
                        <p class="text-sm text-blue-600"><?php esc_html_e('Dinero ahorrado', 'flavor-chat-ia'); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php esc_html_e('vs transporte público', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>

                <!-- Estadísticas del mes -->
                <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span>📅</span> <?php esc_html_e('Este Mes', 'flavor-chat-ia'); ?>
                </h4>

                <div class="flex items-center gap-6">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600"><?php esc_html_e('Viajes realizados', 'flavor-chat-ia'); ?></span>
                            <span class="font-bold"><?php echo esc_html($estadisticas['viajes_mes_actual']); ?></span>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full" style="width: <?php echo min(100, $estadisticas['viajes_mes_actual'] * 10); ?>%;"></div>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600"><?php esc_html_e('Kilómetros', 'flavor-chat-ia'); ?></span>
                            <span class="font-bold"><?php echo esc_html(number_format($estadisticas['km_mes_actual'], 1)); ?></span>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-teal-500 rounded-full" style="width: <?php echo min(100, $estadisticas['km_mes_actual'] * 2); ?>%;"></div>
                        </div>
                    </div>
                </div>

                <?php if ($estadisticas['total_viajes'] === 0): ?>
                    <div class="mt-6 text-center bg-gray-50 rounded-xl p-6">
                        <span class="text-4xl mb-2 block">🚲</span>
                        <p class="text-gray-600"><?php esc_html_e('¡Aún no has realizado ningún viaje!', 'flavor-chat-ia'); ?></p>
                        <a href="?pagina=reservar" class="inline-block mt-3 px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg">
                            <?php esc_html_e('Hacer mi primer viaje', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Tarifas y planes disponibles
     * Uso: [bicicletas_tarifas]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML de tarifas
     */
    public function shortcode_tarifas($atts) {
        // Obtener configuración de precios
        $precio_hora = $this->get_setting('precio_hora', 0);
        $precio_dia = $this->get_setting('precio_dia', 0);
        $precio_mes = $this->get_setting('precio_mes', 10);
        $requiere_fianza = $this->get_setting('requiere_fianza', true);
        $importe_fianza = $this->get_setting('importe_fianza', 50);
        $duracion_maxima_dias = $this->get_setting('duracion_maxima_prestamo_dias', 7);

        ob_start();
        ?>
        <div class="flavor-bicicletas-tarifas">
            <div class="bg-gradient-to-r from-green-500 to-teal-500 rounded-t-xl p-6 text-white text-center">
                <span class="text-4xl mb-2 block">💳</span>
                <h3 class="text-2xl font-bold"><?php esc_html_e('Tarifas y Planes', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm opacity-90"><?php esc_html_e('Elige el plan que mejor se adapte a ti', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="bg-white rounded-b-xl shadow-lg p-6">
                <!-- Planes -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Plan Por Uso -->
                    <div class="border-2 border-gray-200 rounded-xl overflow-hidden hover:border-green-300 transition-colors">
                        <div class="bg-gray-50 p-4 text-center">
                            <h4 class="font-bold text-gray-800"><?php esc_html_e('Por Uso', 'flavor-chat-ia'); ?></h4>
                            <p class="text-sm text-gray-500"><?php esc_html_e('Pago por hora', 'flavor-chat-ia'); ?></p>
                        </div>
                        <div class="p-6 text-center">
                            <div class="text-4xl font-bold text-gray-800 mb-1">
                                <?php if ($precio_hora > 0): ?>
                                    <?php echo esc_html(number_format($precio_hora, 2, ',', '.')); ?>€
                                <?php else: ?>
                                    <?php esc_html_e('Gratis', 'flavor-chat-ia'); ?>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-500"><?php esc_html_e('por hora', 'flavor-chat-ia'); ?></p>

                            <ul class="text-left text-sm text-gray-600 mt-4 space-y-2">
                                <li class="flex items-center gap-2">
                                    <span class="text-green-500">✓</span>
                                    <?php esc_html_e('Sin compromiso', 'flavor-chat-ia'); ?>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-green-500">✓</span>
                                    <?php esc_html_e('Todas las estaciones', 'flavor-chat-ia'); ?>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-green-500">✓</span>
                                    <?php printf(esc_html__('Máx. %d días', 'flavor-chat-ia'), $duracion_maxima_dias); ?>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Plan Diario -->
                    <div class="border-2 border-gray-200 rounded-xl overflow-hidden hover:border-green-300 transition-colors">
                        <div class="bg-gray-50 p-4 text-center">
                            <h4 class="font-bold text-gray-800"><?php esc_html_e('Pase Diario', 'flavor-chat-ia'); ?></h4>
                            <p class="text-sm text-gray-500"><?php esc_html_e('Todo el día', 'flavor-chat-ia'); ?></p>
                        </div>
                        <div class="p-6 text-center">
                            <div class="text-4xl font-bold text-gray-800 mb-1">
                                <?php if ($precio_dia > 0): ?>
                                    <?php echo esc_html(number_format($precio_dia, 2, ',', '.')); ?>€
                                <?php else: ?>
                                    <?php esc_html_e('Gratis', 'flavor-chat-ia'); ?>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-500"><?php esc_html_e('24 horas', 'flavor-chat-ia'); ?></p>

                            <ul class="text-left text-sm text-gray-600 mt-4 space-y-2">
                                <li class="flex items-center gap-2">
                                    <span class="text-green-500">✓</span>
                                    <?php esc_html_e('Uso ilimitado 24h', 'flavor-chat-ia'); ?>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-green-500">✓</span>
                                    <?php esc_html_e('Ideal para turistas', 'flavor-chat-ia'); ?>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-green-500">✓</span>
                                    <?php esc_html_e('Sin límite de viajes', 'flavor-chat-ia'); ?>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Plan Mensual (destacado) -->
                    <div class="border-2 border-green-500 rounded-xl overflow-hidden relative">
                        <div class="absolute top-0 right-0 bg-green-500 text-white text-xs px-3 py-1 rounded-bl-lg font-medium">
                            <?php esc_html_e('RECOMENDADO', 'flavor-chat-ia'); ?>
                        </div>
                        <div class="bg-green-500 text-white p-4 text-center">
                            <h4 class="font-bold"><?php esc_html_e('Abono Mensual', 'flavor-chat-ia'); ?></h4>
                            <p class="text-sm opacity-90"><?php esc_html_e('La mejor opción', 'flavor-chat-ia'); ?></p>
                        </div>
                        <div class="p-6 text-center">
                            <div class="text-4xl font-bold text-green-600 mb-1">
                                <?php echo esc_html(number_format($precio_mes, 2, ',', '.')); ?>€
                            </div>
                            <p class="text-sm text-gray-500"><?php esc_html_e('al mes', 'flavor-chat-ia'); ?></p>

                            <ul class="text-left text-sm text-gray-600 mt-4 space-y-2">
                                <li class="flex items-center gap-2">
                                    <span class="text-green-500">✓</span>
                                    <?php esc_html_e('Viajes ilimitados', 'flavor-chat-ia'); ?>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-green-500">✓</span>
                                    <?php esc_html_e('Reserva prioritaria', 'flavor-chat-ia'); ?>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-green-500">✓</span>
                                    <?php esc_html_e('Bicis eléctricas', 'flavor-chat-ia'); ?>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-green-500">✓</span>
                                    <?php esc_html_e('Estadísticas detalladas', 'flavor-chat-ia'); ?>
                                </li>
                            </ul>

                            <a href="?plan=mensual" class="mt-4 block w-full py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors">
                                <?php esc_html_e('Suscribirme', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Información de fianza -->
                <?php if ($requiere_fianza): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">💰</span>
                        <div>
                            <h5 class="font-bold text-yellow-800"><?php esc_html_e('Fianza', 'flavor-chat-ia'); ?></h5>
                            <p class="text-sm text-yellow-700">
                                <?php printf(
                                    esc_html__('Se requiere una fianza de %s€ que será devuelta íntegramente al finalizar tu suscripción o al devolver la bicicleta en buen estado.', 'flavor-chat-ia'),
                                    esc_html(number_format($importe_fianza, 2, ',', '.'))
                                ); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Condiciones -->
                <div class="bg-gray-50 rounded-xl p-4">
                    <h5 class="font-bold text-gray-800 mb-3"><?php esc_html_e('Condiciones del servicio', 'flavor-chat-ia'); ?></h5>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400">•</span>
                            <?php printf(esc_html__('Duración máxima por préstamo: %d días', 'flavor-chat-ia'), $duracion_maxima_dias); ?>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400">•</span>
                            <?php esc_html_e('Devolución en cualquier estación con espacio disponible', 'flavor-chat-ia'); ?>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400">•</span>
                            <?php esc_html_e('Uso del casco obligatorio', 'flavor-chat-ia'); ?>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400">•</span>
                            <?php esc_html_e('El usuario es responsable de la bicicleta durante el préstamo', 'flavor-chat-ia'); ?>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-gray-400">•</span>
                            <?php esc_html_e('Reportar cualquier incidencia inmediatamente', 'flavor-chat-ia'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // HELPERS PARA SHORTCODES
    // =========================================================================

    /**
     * Renderiza mensaje de login requerido
     *
     * @param string $accion_descripcion Descripción de la acción
     * @return string HTML
     */
    private function render_login_required($accion_descripcion = '') {
        ob_start();
        ?>
        <div class="flavor-login-required bg-gray-50 border border-gray-200 rounded-xl p-8 text-center">
            <span class="text-4xl mb-3 block">🔒</span>
            <h4 class="font-bold text-gray-800 mb-2"><?php esc_html_e('Acceso restringido', 'flavor-chat-ia'); ?></h4>
            <p class="text-gray-600 mb-4">
                <?php
                if ($accion_descripcion) {
                    printf(esc_html__('Debes iniciar sesión para %s.', 'flavor-chat-ia'), esc_html($accion_descripcion));
                } else {
                    esc_html_e('Debes iniciar sesión para acceder a esta funcionalidad.', 'flavor-chat-ia');
                }
                ?>
            </p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="inline-block px-6 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium">
                <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene clase CSS para grid de columnas
     *
     * @param int $columnas Número de columnas
     * @return string Clase CSS
     */
    private function get_grid_columns_class($columnas) {
        $clases_columnas = [
            1 => 'grid-cols-1',
            2 => 'grid-cols-1 md:grid-cols-2',
            3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
            4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
        ];
        return $clases_columnas[$columnas] ?? 'grid-cols-1 md:grid-cols-3';
    }

    /**
     * AJAX: Reservar bicicleta
     */
    public function ajax_reservar_bicicleta() {
        // Verificar nonce
        if (!check_ajax_referer('bicicletas_reservar', 'bicicletas_nonce', false)) {
            wp_send_json_error(__('Sesión expirada. Recarga la página.', 'flavor-chat-ia'));
        }

        // Verificar login
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $bicicleta_id = isset($_POST['bicicleta_id']) ? absint($_POST['bicicleta_id']) : 0;

        if (!$bicicleta_id) {
            wp_send_json_error(__('Selecciona una bicicleta', 'flavor-chat-ia'));
        }

        // Crear un WP_REST_Request para reusar la lógica existente
        $request = new WP_REST_Request('POST');
        $request->set_param('id', $bicicleta_id);

        $resultado = $this->api_reservar_bicicleta($request);

        if ($resultado->get_status() === 201) {
            $datos = $resultado->get_data();
            wp_send_json_success([
                'mensaje' => $datos['mensaje'],
                'prestamo' => $datos['prestamo'],
            ]);
        } else {
            $datos = $resultado->get_data();
            wp_send_json_error($datos['error'] ?? __('Error al reservar', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Login requerido
     */
    public function ajax_login_required() {
        wp_send_json_error(__('Debes iniciar sesión para realizar esta acción.', 'flavor-chat-ia'));
    }

    /**
     * Registrar rutas REST API para APKs
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Listar bicicletas disponibles
        register_rest_route($namespace, '/bicicletas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_bicicletas'],
            'permission_callback' => '__return_true',
            'args' => [
                'estacion_id' => [
                    'type' => 'integer',
                    'description' => 'ID de la estación para filtrar bicicletas',
                ],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['urbana', 'montana', 'electrica', 'infantil', 'carga'],
                    'description' => 'Tipo de bicicleta',
                ],
                'estado' => [
                    'type' => 'string',
                    'enum' => ['disponible', 'en_uso', 'mantenimiento', 'reservada'],
                    'default' => 'disponible',
                ],
            ],
        ]);

        // Listar estaciones
        register_rest_route($namespace, '/bicicletas/estaciones', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_estaciones'],
            'permission_callback' => '__return_true',
            'args' => [
                'lat' => [
                    'type' => 'number',
                    'description' => 'Latitud para buscar estaciones cercanas',
                ],
                'lng' => [
                    'type' => 'number',
                    'description' => 'Longitud para buscar estaciones cercanas',
                ],
                'radio_km' => [
                    'type' => 'integer',
                    'default' => 5,
                    'description' => 'Radio en kilómetros para la búsqueda',
                ],
            ],
        ]);

        // Obtener una bicicleta específica
        register_rest_route($namespace, '/bicicletas/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_bicicleta'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'ID de la bicicleta',
                ],
            ],
        ]);

        // Reservar bicicleta (iniciar préstamo)
        register_rest_route($namespace, '/bicicletas/(?P<id>\d+)/reservar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_reservar_bicicleta'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'ID de la bicicleta a reservar',
                ],
            ],
        ]);

        // Devolver bicicleta (finalizar préstamo)
        register_rest_route($namespace, '/bicicletas/(?P<id>\d+)/devolver', [
            'methods' => 'POST',
            'callback' => [$this, 'api_devolver_bicicleta'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'ID de la bicicleta a devolver',
                ],
                'estacion_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'ID de la estación donde se devuelve',
                ],
                'kilometros' => [
                    'type' => 'number',
                    'description' => 'Kilómetros recorridos',
                ],
                'incidencias' => [
                    'type' => 'string',
                    'description' => 'Incidencias o problemas detectados',
                ],
                'valoracion' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 5,
                    'description' => 'Valoración del servicio (1-5)',
                ],
            ],
        ]);

        // Mis reservas/préstamos
        register_rest_route($namespace, '/bicicletas/mis-reservas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_reservas'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'estado' => [
                    'type' => 'string',
                    'enum' => ['activo', 'finalizado', 'todos'],
                    'default' => 'todos',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);
    }

    /**
     * Verifica que el usuario esté autenticado
     */
    public function verificar_usuario_autenticado() {
        return is_user_logged_in();
    }

    // =========================================================================
    // Métodos API REST
    // =========================================================================

    /**
     * API: Listar bicicletas disponibles
     */
    public function api_listar_bicicletas($request) {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $condiciones_where = [];
        $valores_preparados = [];

        // Filtro por estado
        $estado = $request->get_param('estado') ?: 'disponible';
        $condiciones_where[] = 'b.estado = %s';
        $valores_preparados[] = $estado;

        // Filtro por estación
        $estacion_id = $request->get_param('estacion_id');
        if ($estacion_id) {
            $condiciones_where[] = 'b.estacion_actual_id = %d';
            $valores_preparados[] = absint($estacion_id);
        }

        // Filtro por tipo
        $tipo = $request->get_param('tipo');
        if ($tipo) {
            $condiciones_where[] = 'b.tipo = %s';
            $valores_preparados[] = sanitize_text_field($tipo);
        }

        $clausula_where = implode(' AND ', $condiciones_where);

        $consulta_sql = "SELECT b.*, e.nombre as estacion_nombre, e.direccion as estacion_direccion
            FROM $tabla_bicicletas b
            LEFT JOIN $tabla_estaciones e ON b.estacion_actual_id = e.id
            WHERE $clausula_where
            ORDER BY b.codigo ASC
            LIMIT 100";

        $bicicletas = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$valores_preparados));

        $bicicletas_formateadas = array_map(function($bicicleta) {
            return [
                'id' => (int) $bicicleta->id,
                'codigo' => $bicicleta->codigo,
                'tipo' => $bicicleta->tipo,
                'marca' => $bicicleta->marca,
                'modelo' => $bicicleta->modelo,
                'color' => $bicicleta->color,
                'talla' => $bicicleta->talla,
                'estado' => $bicicleta->estado,
                'kilometros_acumulados' => (int) $bicicleta->kilometros_acumulados,
                'foto_url' => $bicicleta->foto_url,
                'equipamiento' => $bicicleta->equipamiento ? json_decode($bicicleta->equipamiento, true) : null,
                'estacion' => $bicicleta->estacion_actual_id ? [
                    'id' => (int) $bicicleta->estacion_actual_id,
                    'nombre' => $bicicleta->estacion_nombre,
                    'direccion' => $bicicleta->estacion_direccion,
                ] : null,
            ];
        }, $bicicletas);

        return new WP_REST_Response([
            'success' => true,
            'total' => count($bicicletas_formateadas),
            'bicicletas' => $bicicletas_formateadas,
        ], 200);
    }

    /**
     * API: Listar estaciones
     */
    public function api_listar_estaciones($request) {
        $resultado = $this->action_estaciones([
            'lat' => $request->get_param('lat'),
            'lng' => $request->get_param('lng'),
            'radio_km' => $request->get_param('radio_km') ?: 5,
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error'] ?? 'Error al obtener estaciones'], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Obtener una bicicleta específica
     */
    public function api_obtener_bicicleta($request) {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $bicicleta_id = absint($request->get_param('id'));

        $bicicleta = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, e.nombre as estacion_nombre, e.direccion as estacion_direccion, e.latitud, e.longitud
            FROM $tabla_bicicletas b
            LEFT JOIN $tabla_estaciones e ON b.estacion_actual_id = e.id
            WHERE b.id = %d",
            $bicicleta_id
        ));

        if (!$bicicleta) {
            return new WP_REST_Response(['success' => false, 'error' => 'Bicicleta no encontrada'], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'bicicleta' => [
                'id' => (int) $bicicleta->id,
                'codigo' => $bicicleta->codigo,
                'tipo' => $bicicleta->tipo,
                'marca' => $bicicleta->marca,
                'modelo' => $bicicleta->modelo,
                'color' => $bicicleta->color,
                'talla' => $bicicleta->talla,
                'estado' => $bicicleta->estado,
                'kilometros_acumulados' => (int) $bicicleta->kilometros_acumulados,
                'ultima_revision' => $bicicleta->ultima_revision,
                'foto_url' => $bicicleta->foto_url,
                'equipamiento' => $bicicleta->equipamiento ? json_decode($bicicleta->equipamiento, true) : null,
                'fecha_alta' => $bicicleta->fecha_alta,
                'estacion' => $bicicleta->estacion_actual_id ? [
                    'id' => (int) $bicicleta->estacion_actual_id,
                    'nombre' => $bicicleta->estacion_nombre,
                    'direccion' => $bicicleta->estacion_direccion,
                    'lat' => (float) $bicicleta->latitud,
                    'lng' => (float) $bicicleta->longitud,
                ] : null,
            ],
        ], 200);
    }

    /**
     * API: Reservar bicicleta (iniciar préstamo)
     */
    public function api_reservar_bicicleta($request) {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

        $bicicleta_id = absint($request->get_param('id'));
        $usuario_id = get_current_user_id();

        // Verificar que la bicicleta existe y está disponible
        $bicicleta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_bicicletas WHERE id = %d",
            $bicicleta_id
        ));

        if (!$bicicleta) {
            return new WP_REST_Response(['success' => false, 'error' => 'Bicicleta no encontrada'], 404);
        }

        if ($bicicleta->estado !== 'disponible') {
            return new WP_REST_Response([
                'success' => false,
                'error' => sprintf('La bicicleta no está disponible. Estado actual: %s', $bicicleta->estado)
            ], 400);
        }

        // Verificar que el usuario no tenga ya un préstamo activo
        $prestamo_activo = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_prestamos WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        if ($prestamo_activo > 0) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Ya tienes un préstamo activo. Devuelve la bicicleta actual antes de reservar otra.'
            ], 400);
        }

        // Obtener configuración de fianza
        $requiere_fianza = $this->get_setting('requiere_fianza', true);
        $importe_fianza = $requiere_fianza ? $this->get_setting('importe_fianza', 50) : 0;

        // Crear el préstamo
        $resultado_insercion = $wpdb->insert($tabla_prestamos, [
            'bicicleta_id' => $bicicleta_id,
            'usuario_id' => $usuario_id,
            'estacion_salida_id' => $bicicleta->estacion_actual_id,
            'fecha_inicio' => current_time('mysql'),
            'fianza' => $importe_fianza,
            'estado' => 'activo',
            'fecha_creacion' => current_time('mysql'),
        ], ['%d', '%d', '%d', '%s', '%f', '%s', '%s']);

        if ($resultado_insercion === false) {
            return new WP_REST_Response(['success' => false, 'error' => 'Error al crear el préstamo'], 500);
        }

        $prestamo_id = $wpdb->insert_id;

        // Actualizar estado de la bicicleta
        $wpdb->update(
            $tabla_bicicletas,
            ['estado' => 'en_uso', 'estacion_actual_id' => null],
            ['id' => $bicicleta_id],
            ['%s', '%d'],
            ['%d']
        );

        // Actualizar contador de bicicletas en la estación
        if ($bicicleta->estacion_actual_id) {
            $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_estaciones SET bicicletas_disponibles = GREATEST(0, bicicletas_disponibles - 1) WHERE id = %d",
                $bicicleta->estacion_actual_id
            ));
        }

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => sprintf('Bicicleta %s reservada correctamente', $bicicleta->codigo),
            'prestamo' => [
                'id' => $prestamo_id,
                'bicicleta_id' => $bicicleta_id,
                'bicicleta_codigo' => $bicicleta->codigo,
                'fecha_inicio' => current_time('mysql'),
                'fianza' => $importe_fianza,
                'estado' => 'activo',
            ],
        ], 201);
    }

    /**
     * API: Devolver bicicleta (finalizar préstamo)
     */
    public function api_devolver_bicicleta($request) {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $bicicleta_id = absint($request->get_param('id'));
        $estacion_id = absint($request->get_param('estacion_id'));
        $kilometros = floatval($request->get_param('kilometros') ?: 0);
        $incidencias = sanitize_textarea_field($request->get_param('incidencias') ?: '');
        $valoracion = absint($request->get_param('valoracion') ?: 0);
        $usuario_id = get_current_user_id();

        // Verificar que existe un préstamo activo para esta bicicleta y usuario
        $prestamo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_prestamos WHERE bicicleta_id = %d AND usuario_id = %d AND estado = 'activo'",
            $bicicleta_id,
            $usuario_id
        ));

        if (!$prestamo) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'No tienes un préstamo activo para esta bicicleta'
            ], 400);
        }

        // Verificar que la estación existe
        $estacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_estaciones WHERE id = %d AND estado = 'activa'",
            $estacion_id
        ));

        if (!$estacion) {
            return new WP_REST_Response(['success' => false, 'error' => 'Estación no encontrada o no disponible'], 404);
        }

        // Verificar capacidad de la estación
        if ($estacion->bicicletas_disponibles >= $estacion->capacidad_total) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'La estación está llena. Por favor, elige otra estación.'
            ], 400);
        }

        // Calcular duración del préstamo
        $fecha_inicio = strtotime($prestamo->fecha_inicio);
        $fecha_fin = current_time('timestamp');
        $duracion_minutos = round(($fecha_fin - $fecha_inicio) / 60);

        // Calcular coste (si aplica)
        $precio_hora = $this->get_setting('precio_hora', 0);
        $coste_total = $precio_hora > 0 ? ($duracion_minutos / 60) * $precio_hora : 0;

        // Actualizar el préstamo
        $wpdb->update(
            $tabla_prestamos,
            [
                'estacion_llegada_id' => $estacion_id,
                'fecha_fin' => current_time('mysql'),
                'duracion_minutos' => $duracion_minutos,
                'kilometros_recorridos' => $kilometros,
                'coste_total' => $coste_total,
                'incidencias' => $incidencias,
                'valoracion' => $valoracion > 0 && $valoracion <= 5 ? $valoracion : null,
                'fianza_devuelta' => 1,
                'estado' => 'finalizado',
            ],
            ['id' => $prestamo->id],
            ['%d', '%s', '%d', '%f', '%f', '%s', '%d', '%d', '%s'],
            ['%d']
        );

        // Determinar estado de la bicicleta
        $estado_bicicleta = !empty($incidencias) ? 'mantenimiento' : 'disponible';

        // Actualizar bicicleta
        $wpdb->update(
            $tabla_bicicletas,
            [
                'estado' => $estado_bicicleta,
                'estacion_actual_id' => $estacion_id,
                'kilometros_acumulados' => $wpdb->get_var($wpdb->prepare(
                    "SELECT kilometros_acumulados FROM $tabla_bicicletas WHERE id = %d",
                    $bicicleta_id
                )) + $kilometros,
            ],
            ['id' => $bicicleta_id],
            ['%s', '%d', '%d'],
            ['%d']
        );

        // Actualizar contador de bicicletas en la estación (solo si está disponible)
        if ($estado_bicicleta === 'disponible') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_estaciones SET bicicletas_disponibles = bicicletas_disponibles + 1 WHERE id = %d",
                $estacion_id
            ));
        }

        // Formatear duración para mostrar
        $duracion_texto = $duracion_minutos < 60
            ? $duracion_minutos . ' minutos'
            : round($duracion_minutos / 60, 1) . ' horas';

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => sprintf('Bicicleta devuelta correctamente en %s', $estacion->nombre),
            'resumen' => [
                'prestamo_id' => $prestamo->id,
                'duracion' => $duracion_texto,
                'duracion_minutos' => $duracion_minutos,
                'kilometros' => $kilometros,
                'coste' => $coste_total,
                'fianza_devuelta' => $prestamo->fianza,
                'estacion_devolucion' => $estacion->nombre,
            ],
        ], 200);
    }

    /**
     * API: Obtener préstamos/reservas del usuario
     */
    public function api_mis_reservas($request) {
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $usuario_id = get_current_user_id();
        $estado = $request->get_param('estado') ?: 'todos';
        $limite = absint($request->get_param('limite') ?: 20);

        $condiciones_where = ['p.usuario_id = %d'];
        $valores_preparados = [$usuario_id];

        if ($estado === 'activo') {
            $condiciones_where[] = "p.estado = 'activo'";
        } elseif ($estado === 'finalizado') {
            $condiciones_where[] = "p.estado = 'finalizado'";
        }

        $clausula_where = implode(' AND ', $condiciones_where);
        $valores_preparados[] = $limite;

        $consulta_sql = "SELECT p.*,
            b.codigo as bicicleta_codigo, b.tipo as bicicleta_tipo, b.marca, b.modelo,
            es.nombre as estacion_salida_nombre,
            el.nombre as estacion_llegada_nombre
            FROM $tabla_prestamos p
            LEFT JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
            LEFT JOIN $tabla_estaciones es ON p.estacion_salida_id = es.id
            LEFT JOIN $tabla_estaciones el ON p.estacion_llegada_id = el.id
            WHERE $clausula_where
            ORDER BY p.fecha_inicio DESC
            LIMIT %d";

        $prestamos = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$valores_preparados));

        $prestamos_formateados = array_map(function($prestamo) {
            $duracion_texto = null;
            if ($prestamo->duracion_minutos) {
                $duracion_texto = $prestamo->duracion_minutos < 60
                    ? $prestamo->duracion_minutos . ' min'
                    : round($prestamo->duracion_minutos / 60, 1) . ' h';
            }

            return [
                'id' => (int) $prestamo->id,
                'bicicleta' => [
                    'id' => (int) $prestamo->bicicleta_id,
                    'codigo' => $prestamo->bicicleta_codigo,
                    'tipo' => $prestamo->bicicleta_tipo,
                    'marca_modelo' => trim($prestamo->marca . ' ' . $prestamo->modelo),
                ],
                'estacion_salida' => [
                    'id' => (int) $prestamo->estacion_salida_id,
                    'nombre' => $prestamo->estacion_salida_nombre,
                ],
                'estacion_llegada' => $prestamo->estacion_llegada_id ? [
                    'id' => (int) $prestamo->estacion_llegada_id,
                    'nombre' => $prestamo->estacion_llegada_nombre,
                ] : null,
                'fecha_inicio' => $prestamo->fecha_inicio,
                'fecha_fin' => $prestamo->fecha_fin,
                'duracion' => $duracion_texto,
                'duracion_minutos' => $prestamo->duracion_minutos ? (int) $prestamo->duracion_minutos : null,
                'kilometros' => $prestamo->kilometros_recorridos ? (float) $prestamo->kilometros_recorridos : null,
                'coste' => (float) $prestamo->coste_total,
                'fianza' => (float) $prestamo->fianza,
                'fianza_devuelta' => (bool) $prestamo->fianza_devuelta,
                'valoracion' => $prestamo->valoracion ? (int) $prestamo->valoracion : null,
                'estado' => $prestamo->estado,
                'incidencias' => $prestamo->incidencias,
            ];
        }, $prestamos);

        return new WP_REST_Response([
            'success' => true,
            'total' => count($prestamos_formateados),
            'prestamos' => $prestamos_formateados,
        ], 200);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
        $tabla_mantenimiento = $wpdb->prefix . 'flavor_bicicletas_mantenimiento';

        $sql_bicicletas = "CREATE TABLE $tabla_bicicletas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            codigo varchar(50) NOT NULL,
            tipo varchar(20) DEFAULT 'urbana',
            marca varchar(100) DEFAULT NULL,
            modelo varchar(100) DEFAULT NULL,
            color varchar(50) DEFAULT NULL,
            talla varchar(5) DEFAULT 'M',
            estacion_actual_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'disponible',
            kilometros_acumulados int(11) DEFAULT 0,
            ultima_revision datetime DEFAULT NULL,
            proximo_mantenimiento_km int(11) DEFAULT 500,
            foto_url varchar(500) DEFAULT NULL,
            equipamiento text DEFAULT NULL,
            fecha_alta datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY codigo (codigo),
            KEY estacion_actual_id (estacion_actual_id),
            KEY estado (estado),
            KEY tipo (tipo)
        ) $charset_collate;";

        $sql_prestamos = "CREATE TABLE $tabla_prestamos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            bicicleta_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            estacion_salida_id bigint(20) unsigned NOT NULL,
            estacion_llegada_id bigint(20) unsigned DEFAULT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime DEFAULT NULL,
            duracion_minutos int(11) DEFAULT NULL,
            kilometros_recorridos decimal(10,2) DEFAULT NULL,
            coste_total decimal(10,2) DEFAULT 0,
            fianza decimal(10,2) DEFAULT NULL,
            fianza_devuelta tinyint(1) DEFAULT 0,
            incidencias text DEFAULT NULL,
            valoracion int(11) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY bicicleta_id (bicicleta_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        $sql_estaciones = "CREATE TABLE $tabla_estaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            direccion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            capacidad_total int(11) NOT NULL,
            bicicletas_disponibles int(11) DEFAULT 0,
            tipo varchar(20) DEFAULT 'publica',
            horario_apertura time DEFAULT NULL,
            horario_cierre time DEFAULT NULL,
            servicios text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activa',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY latitud (latitud),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_mantenimiento = "CREATE TABLE $tabla_mantenimiento (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            bicicleta_id bigint(20) unsigned NOT NULL,
            tipo varchar(20) DEFAULT 'revision',
            descripcion text NOT NULL,
            reportado_por bigint(20) unsigned DEFAULT NULL,
            tecnico_asignado bigint(20) unsigned DEFAULT NULL,
            fecha_reporte datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_inicio datetime DEFAULT NULL,
            fecha_fin datetime DEFAULT NULL,
            coste decimal(10,2) DEFAULT NULL,
            piezas_cambiadas text DEFAULT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            PRIMARY KEY  (id),
            KEY bicicleta_id (bicicleta_id),
            KEY estado (estado),
            KEY fecha_reporte (fecha_reporte)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_bicicletas);
        dbDelta($sql_prestamos);
        dbDelta($sql_estaciones);
        dbDelta($sql_mantenimiento);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'estaciones' => [
                'description' => 'Listar estaciones cercanas',
                'params' => ['lat', 'lng', 'radio_km'],
            ],
            'bicicletas_disponibles' => [
                'description' => 'Ver bicicletas disponibles en estación',
                'params' => ['estacion_id'],
            ],
            'iniciar_prestamo' => [
                'description' => 'Retirar bicicleta',
                'params' => ['bicicleta_id'],
            ],
            'finalizar_prestamo' => [
                'description' => 'Devolver bicicleta',
                'params' => ['prestamo_id', 'estacion_id', 'kilometros'],
            ],
            'mis_prestamos' => [
                'description' => 'Historial de préstamos',
                'params' => [],
            ],
            'reportar_problema' => [
                'description' => 'Reportar problema con bicicleta',
                'params' => ['bicicleta_id', 'descripcion'],
            ],
            'reservar_bicicleta' => [
                'description' => 'Reservar bicicleta',
                'params' => ['bicicleta_id', 'estacion_id', 'fecha_hora'],
            ],
            // Admin actions
            'estadisticas_uso' => [
                'description' => 'Estadísticas de uso (admin)',
                'params' => ['periodo'],
            ],
            'gestion_mantenimiento' => [
                'description' => 'Gestión de mantenimiento (admin)',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Listar estaciones
     */
    private function action_estaciones($params) {
        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);
        $radio_km = absint($params['radio_km'] ?? 5);

        if ($lat == 0 || $lng == 0) {
            // Sin ubicación, devolver todas las estaciones activas
            $estaciones = $wpdb->get_results("SELECT * FROM $tabla_estaciones WHERE estado = 'activa' ORDER BY nombre");
        } else {
            // Con ubicación, calcular distancia
            $sql = "SELECT *,
                    (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                    FROM $tabla_estaciones
                    WHERE estado = 'activa'
                    HAVING distancia <= %d
                    ORDER BY distancia ASC";

            $estaciones = $wpdb->get_results($wpdb->prepare($sql, $lat, $lng, $lat, $radio_km));
        }

        return [
            'success' => true,
            'estaciones' => array_map(function($e) {
                return [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'direccion' => $e->direccion,
                    'lat' => floatval($e->latitud),
                    'lng' => floatval($e->longitud),
                    'bicicletas_disponibles' => $e->bicicletas_disponibles,
                    'capacidad_total' => $e->capacidad_total,
                    'distancia_km' => isset($e->distancia) ? round($e->distancia, 2) : null,
                ];
            }, $estaciones),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Predicción de disponibilidad en tiempo real
     * - Sugerencia de rutas optimizadas
     * - Recomendación de tipo de bici según destino
     */
    public function get_web_components() {
        return [
            'hero_bicis' => [
                'label' => __('Hero Bicicletas', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-admin-site',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Bicicletas Compartidas', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Movilidad sostenible y saludable', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_mapa_estaciones' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'bicicletas/hero',
            ],
            'mapa_estaciones' => [
                'label' => __('Mapa de Estaciones', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'zoom_inicial' => ['type' => 'number', 'default' => 13],
                    'mostrar_disponibilidad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'bicicletas/mapa',
            ],
            'tipos_bicicletas' => [
                'label' => __('Tipos de Bicicletas', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-admin-site',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Elige tu Bicicleta', 'flavor-chat-ia')],
                    'mostrar_precios' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'bicicletas/tipos',
            ],
            'como_usar' => [
                'label' => __('Cómo Usar', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-info',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('¿Cómo funciona?', 'flavor-chat-ia')],
                    'paso1' => ['type' => 'text', 'default' => __('Encuentra estación cercana', 'flavor-chat-ia')],
                    'paso2' => ['type' => 'text', 'default' => __('Escanea código QR', 'flavor-chat-ia')],
                    'paso3' => ['type' => 'text', 'default' => __('¡Pedalea!', 'flavor-chat-ia')],
                ],
                'template' => 'bicicletas/como-usar',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'bicicletas_estaciones',
                'description' => 'Ver estaciones de bicicletas cercanas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number', 'description' => 'Latitud'],
                        'lng' => ['type' => 'number', 'description' => 'Longitud'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Bicicletas Compartidas**

Sistema de préstamo de bicicletas gestionado por la comunidad.

**Tipos de bicicletas:**
- Urbanas
- De montaña
- Eléctricas
- Infantiles
- De carga

**Cómo funciona:**
1. Encuentra estación cercana
2. Elige bicicleta disponible
3. Escanea código QR o introduce número
4. Disfruta tu viaje
5. Devuelve en cualquier estación

**Tarifas:**
- Gratis las primeras 2 horas
- Tarifa por hora después
- Abonos mensuales disponibles
- Fianza reembolsable

**Equipamiento incluido:**
- Casco obligatorio
- Candado de seguridad
- Luces delanteras y traseras
- Kit de herramientas básico (estaciones)
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Qué pasa si la bicicleta se avería?',
                'respuesta' => 'Reporta el problema desde la app inmediatamente. No pagarás por el tiempo de avería.',
            ],
            [
                'pregunta' => '¿Puedo reservar una bicicleta?',
                'respuesta' => 'Sí, puedes reservar con hasta 2 horas de antelación.',
            ],
            [
                'pregunta' => '¿Dónde puedo devolverla?',
                'respuesta' => 'En cualquier estación con espacio disponible, no tiene que ser la misma.',
            ],
        ];
    }

    // =========================================================================
    // PANEL UNIFICADO DE ADMINISTRACIÓN
    // =========================================================================

    /**
     * Configuración para el panel unificado de gestión
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'bicicletas_compartidas',
            'label' => __('Bicicletas', 'flavor-chat-ia'),
            'icon' => 'dashicons-car',
            'capability' => 'manage_options',
            'categoria' => 'sostenibilidad',
            'paginas' => [
                [
                    'slug' => 'flavor-bicicletas-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'flavor-bicicletas-flota',
                    'titulo' => __('Flota', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_flota'],
                ],
                [
                    'slug' => 'flavor-bicicletas-estaciones',
                    'titulo' => __('Estaciones', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_estaciones'],
                ],
                [
                    'slug' => 'flavor-bicicletas-prestamos',
                    'titulo' => __('Préstamos Activos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_prestamos'],
                    'badge' => [$this, 'contar_prestamos_activos'],
                ],
                [
                    'slug' => 'flavor-bicicletas-configuracion',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_configuracion'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Obtiene estadísticas para el dashboard del panel unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

        $bicicletas_disponibles = 0;
        $prestamos_activos = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
            $bicicletas_disponibles = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_bicicletas WHERE estado = 'disponible'"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            $prestamos_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'activo'"
            );
        }

        return [
            [
                'icon' => 'dashicons-car',
                'valor' => $bicicletas_disponibles,
                'label' => __('Bicis disponibles', 'flavor-chat-ia'),
                'color' => 'green',
                'enlace' => admin_url('admin.php?page=flavor-bicicletas-flota'),
            ],
            [
                'icon' => 'dashicons-migrate',
                'valor' => $prestamos_activos,
                'label' => __('Préstamos activos', 'flavor-chat-ia'),
                'color' => 'blue',
                'enlace' => admin_url('admin.php?page=flavor-bicicletas-prestamos'),
            ],
        ];
    }

    /**
     * Cuenta préstamos activos para el badge
     *
     * @return int
     */
    public function contar_prestamos_activos() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'activo'"
        );
    }

    /**
     * Renderiza el dashboard de administración
     */
    public function render_admin_dashboard() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        // Estadísticas
        $total_bicicletas = 0;
        $bicicletas_disponibles = 0;
        $bicicletas_en_uso = 0;
        $bicicletas_mantenimiento = 0;
        $total_estaciones = 0;
        $prestamos_hoy = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
            $total_bicicletas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bicicletas");
            $bicicletas_disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bicicletas WHERE estado = 'disponible'");
            $bicicletas_en_uso = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bicicletas WHERE estado = 'en_uso'");
            $bicicletas_mantenimiento = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bicicletas WHERE estado = 'mantenimiento'");
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_estaciones)) {
            $total_estaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_estaciones WHERE estado = 'activa'");
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            $prestamos_hoy = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_prestamos WHERE DATE(fecha_inicio) = %s",
                current_time('Y-m-d')
            ));
        }

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Bicicletas Compartidas - Dashboard', 'flavor-chat-ia')); ?>

            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-car"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($total_bicicletas); ?></span>
                        <span class="stat-label"><?php _e('Total Bicicletas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card green">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($bicicletas_disponibles); ?></span>
                        <span class="stat-label"><?php _e('Disponibles', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card blue">
                    <span class="dashicons dashicons-migrate"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($bicicletas_en_uso); ?></span>
                        <span class="stat-label"><?php _e('En Uso', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card orange">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($bicicletas_mantenimiento); ?></span>
                        <span class="stat-label"><?php _e('En Mantenimiento', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-location"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($total_estaciones); ?></span>
                        <span class="stat-label"><?php _e('Estaciones Activas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card purple">
                    <span class="dashicons dashicons-clock"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo esc_html($prestamos_hoy); ?></span>
                        <span class="stat-label"><?php _e('Préstamos Hoy', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-admin-section">
                <h2><?php _e('Accesos Rápidos', 'flavor-chat-ia'); ?></h2>
                <div class="flavor-quick-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-flota')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-car"></span>
                        <?php _e('Gestionar Flota', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones')); ?>" class="button">
                        <span class="dashicons dashicons-location"></span>
                        <?php _e('Ver Estaciones', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-prestamos')); ?>" class="button">
                        <span class="dashicons dashicons-migrate"></span>
                        <?php _e('Préstamos Activos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de gestión de flota
     */
    public function render_admin_flota() {
        global $wpdb;
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';

        $bicicletas = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_bicicletas)) {
            $bicicletas = $wpdb->get_results("SELECT * FROM $tabla_bicicletas ORDER BY codigo ASC");
        }

        ?>
        <div class="wrap flavor-admin-page">
            <?php
            $this->render_page_header(
                __('Gestión de Flota', 'flavor-chat-ia'),
                [
                    [
                        'label' => __('Añadir Bicicleta', 'flavor-chat-ia'),
                        'url' => '#',
                        'class' => 'button-primary',
                    ],
                ]
            );
            ?>

            <div class="flavor-admin-section">
                <?php if (empty($bicicletas)): ?>
                    <div class="notice notice-info">
                        <p><?php _e('No hay bicicletas registradas. Añade la primera bicicleta a la flota.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Código', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Marca/Modelo', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Talla', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Km Acumulados', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bicicletas as $bicicleta): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($bicicleta->codigo); ?></strong></td>
                                    <td><?php echo esc_html(ucfirst($bicicleta->tipo)); ?></td>
                                    <td><?php echo esc_html($bicicleta->marca . ' ' . $bicicleta->modelo); ?></td>
                                    <td><?php echo esc_html($bicicleta->talla); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($bicicleta->estado); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $bicicleta->estado))); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(number_format($bicicleta->kilometros_acumulados)); ?> km</td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=bicicletas-flota&action=editar&id=' . $bicicleta->id)); ?>" class="button button-small"><?php _e('Editar', 'flavor-chat-ia'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de estaciones
     */
    public function render_admin_estaciones() {
        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $estaciones = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_estaciones)) {
            $estaciones = $wpdb->get_results("SELECT * FROM $tabla_estaciones ORDER BY nombre ASC");
        }

        ?>
        <div class="wrap flavor-admin-page">
            <?php
            $this->render_page_header(
                __('Estaciones de Bicicletas', 'flavor-chat-ia'),
                [
                    [
                        'label' => __('Añadir Estación', 'flavor-chat-ia'),
                        'url' => '#',
                        'class' => 'button-primary',
                    ],
                ]
            );
            ?>

            <div class="flavor-admin-section">
                <?php if (empty($estaciones)): ?>
                    <div class="notice notice-info">
                        <p><?php _e('No hay estaciones registradas. Configura la primera estación de bicicletas.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Nombre', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Dirección', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Capacidad', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Disponibles', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estaciones as $estacion): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($estacion->nombre); ?></strong></td>
                                    <td><?php echo esc_html($estacion->direccion); ?></td>
                                    <td><?php echo esc_html(ucfirst($estacion->tipo)); ?></td>
                                    <td><?php echo esc_html($estacion->capacidad_total); ?></td>
                                    <td><?php echo esc_html($estacion->bicicletas_disponibles); ?></td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estacion->estado); ?>">
                                            <?php echo esc_html(ucfirst($estacion->estado)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=bicicletas-estaciones&action=editar&id=' . $estacion->id)); ?>" class="button button-small"><?php _e('Editar', 'flavor-chat-ia'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de préstamos activos
     */
    public function render_admin_prestamos() {
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        $prestamos = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            $prestamos = $wpdb->get_results("
                SELECT p.*, b.codigo as bicicleta_codigo, e.nombre as estacion_nombre, u.display_name as usuario_nombre
                FROM $tabla_prestamos p
                LEFT JOIN $tabla_bicicletas b ON p.bicicleta_id = b.id
                LEFT JOIN $tabla_estaciones e ON p.estacion_salida_id = e.id
                LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
                WHERE p.estado = 'activo'
                ORDER BY p.fecha_inicio DESC
            ");
        }

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Préstamos Activos', 'flavor-chat-ia')); ?>

            <div class="flavor-admin-section">
                <?php if (empty($prestamos)): ?>
                    <div class="notice notice-info">
                        <p><?php _e('No hay préstamos activos en este momento.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Bicicleta', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Usuario', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Estación Salida', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Inicio', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Duración', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestamos as $prestamo):
                                $inicio = strtotime($prestamo->fecha_inicio);
                                $duracion_minutos = round((time() - $inicio) / 60);
                                $duracion_texto = $duracion_minutos < 60
                                    ? $duracion_minutos . ' min'
                                    : round($duracion_minutos / 60, 1) . ' h';
                            ?>
                                <tr>
                                    <td>#<?php echo esc_html($prestamo->id); ?></td>
                                    <td><strong><?php echo esc_html($prestamo->bicicleta_codigo); ?></strong></td>
                                    <td><?php echo esc_html($prestamo->usuario_nombre); ?></td>
                                    <td><?php echo esc_html($prestamo->estacion_nombre); ?></td>
                                    <td><?php echo esc_html(date_i18n('d/m/Y H:i', $inicio)); ?></td>
                                    <td><?php echo esc_html($duracion_texto); ?></td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('finalizar_prestamo_bici', '_wpnonce'); ?>
                                            <input type="hidden" name="accion" value="finalizar_prestamo">
                                            <input type="hidden" name="prestamo_id" value="<?php echo esc_attr($prestamo->id); ?>">
                                            <button type="submit" class="button button-small" onclick="return confirm('<?php echo esc_js(__('¿Finalizar este préstamo?', 'flavor-chat-ia')); ?>');"><?php _e('Finalizar', 'flavor-chat-ia'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_admin_configuracion() {
        $configuracion = $this->get_settings();

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Configuración de Bicicletas', 'flavor-chat-ia')); ?>

            <div class="flavor-admin-section">
                <form method="post" action="">
                    <?php wp_nonce_field('flavor_bicicletas_config', 'flavor_bicicletas_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="requiere_fianza"><?php _e('Requiere Fianza', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="requiere_fianza" name="requiere_fianza" value="1"
                                    <?php checked($configuracion['requiere_fianza'] ?? true); ?>>
                                <p class="description"><?php _e('Solicitar fianza para préstamos de bicicletas.', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="importe_fianza"><?php _e('Importe Fianza', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="importe_fianza" name="importe_fianza"
                                    value="<?php echo esc_attr($configuracion['importe_fianza'] ?? 50); ?>"
                                    min="0" step="0.01" class="small-text"> &euro;
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="duracion_maxima_prestamo_dias"><?php _e('Duración Máxima Préstamo', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="duracion_maxima_prestamo_dias" name="duracion_maxima_prestamo_dias"
                                    value="<?php echo esc_attr($configuracion['duracion_maxima_prestamo_dias'] ?? 7); ?>"
                                    min="1" class="small-text"> <?php _e('días', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="permite_reservas"><?php _e('Permitir Reservas', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="permite_reservas" name="permite_reservas" value="1"
                                    <?php checked($configuracion['permite_reservas'] ?? true); ?>>
                                <p class="description"><?php _e('Permitir a los usuarios reservar bicicletas con antelación.', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="precio_mes"><?php _e('Precio Mensual', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="precio_mes" name="precio_mes"
                                    value="<?php echo esc_attr($configuracion['precio_mes'] ?? 10); ?>"
                                    min="0" step="0.01" class="small-text"> &euro;
                                <p class="description"><?php _e('Tarifa de abono mensual (0 = gratuito).', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" name="guardar_config" class="button button-primary">
                            <?php _e('Guardar Configuración', 'flavor-chat-ia'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    /**
     * Crea/actualiza páginas del módulo si es necesario
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('bicicletas_compartidas');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('bicicletas-compartidas');
        if (!$pagina && !get_option('flavor_bicicletas_compartidas_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['bicicletas_compartidas']);
            update_option('flavor_bicicletas_compartidas_pages_created', 1, false);
        }
    }

    /**
     * Configuración de formularios del módulo
     *
     * @param string $action_name Nombre de la acción
     * @return array Configuración del formulario
     */
    public function get_form_config($action_name) {
        $configs = [
            'reservar_bicicleta' => [
                'title' => __('Reservar Bicicleta', 'flavor-chat-ia'),
                'description' => __('Selecciona una bicicleta para reservar', 'flavor-chat-ia'),
                'fields' => [
                    'bicicleta_id' => [
                        'type' => 'select',
                        'label' => __('Bicicleta', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => $this->get_bicicletas_disponibles_options(),
                    ],
                ],
                'submit_text' => __('Reservar', 'flavor-chat-ia'),
                'success_message' => __('Bicicleta reservada correctamente', 'flavor-chat-ia'),
            ],
            'reportar_problema' => [
                'title' => __('Reportar Problema', 'flavor-chat-ia'),
                'description' => __('Reporta un problema con una bicicleta', 'flavor-chat-ia'),
                'fields' => [
                    'bicicleta_id' => [
                        'type' => 'number',
                        'label' => __('ID de Bicicleta', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción del problema', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 4,
                    ],
                ],
                'submit_text' => __('Enviar Reporte', 'flavor-chat-ia'),
                'success_message' => __('Reporte enviado correctamente', 'flavor-chat-ia'),
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    /**
     * Obtiene opciones de bicicletas disponibles para select
     *
     * @return array
     */
    private function get_bicicletas_disponibles_options() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_bicicletas';
        $options = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $bicicletas = $wpdb->get_results(
                "SELECT id, codigo, tipo, marca FROM $tabla WHERE estado = 'disponible' ORDER BY codigo ASC LIMIT 50"
            );
            foreach ($bicicletas as $bici) {
                $options[$bici->id] = sprintf('%s - %s %s', $bici->codigo, ucfirst($bici->tipo), $bici->marca);
            }
        }

        if (empty($options)) {
            $options[''] = __('No hay bicicletas disponibles', 'flavor-chat-ia');
        }

        return $options;
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Bicicletas Compartidas', 'flavor-chat-ia'),
                'slug' => 'bicicletas-compartidas',
                'content' => '<h1>' . __('Bicicletas Compartidas', 'flavor-chat-ia') . '</h1>
<p>' . __('Sistema de préstamo de bicicletas comunitario. Encuentra estaciones cercanas, reserva tu bici y disfruta de movilidad sostenible.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="bicicletas_compartidas" action="estaciones" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Alquilar Bicicleta', 'flavor-chat-ia'),
                'slug' => 'alquilar',
                'content' => '<h1>' . __('Alquilar Bicicleta', 'flavor-chat-ia') . '</h1>
<p>' . __('Selecciona una bicicleta disponible y comienza tu viaje.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="bicicletas_compartidas" action="bicicletas_disponibles" columnas="3" limite="12"]

[flavor_module_form module="bicicletas_compartidas" action="reservar_bicicleta"]',
                'parent' => 'bicicletas-compartidas',
            ],
            [
                'title' => __('Mis Alquileres', 'flavor-chat-ia'),
                'slug' => 'mis-alquileres',
                'content' => '<h1>' . __('Mis Alquileres', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta tu historial de préstamos y alquileres de bicicletas.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="bicicletas_compartidas" action="mis_prestamos" columnas="2" limite="20"]',
                'parent' => 'bicicletas-compartidas',
            ],
        ];
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'bicicletas-compartidas',
            'title'    => __('Bicicletas Compartidas', 'flavor-chat-ia'),
            'subtitle' => __('Sistema de préstamo de bicicletas comunitario', 'flavor-chat-ia'),
            'icon'     => '🚲',
            'color'    => 'success', // Usa variable CSS --flavor-success del tema

            'database' => [
                'table'       => 'flavor_bicicletas',
                'primary_key' => 'id',
            ],

            'fields' => [
                'codigo'       => ['type' => 'text', 'label' => __('Código bicicleta', 'flavor-chat-ia'), 'required' => true],
                'tipo'         => ['type' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'options' => ['urbana', 'montaña', 'electrica', 'plegable', 'cargo']],
                'estacion_id'  => ['type' => 'select', 'label' => __('Estación', 'flavor-chat-ia')],
                'estado_fisico' => ['type' => 'select', 'label' => __('Estado físico', 'flavor-chat-ia'), 'options' => ['perfecto', 'bueno', 'reparacion']],
                'bateria'      => ['type' => 'number', 'label' => __('Batería %', 'flavor-chat-ia'), 'min' => 0, 'max' => 100],
            ],

            'estados' => [
                'disponible' => ['label' => __('Disponible', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🟢'],
                'en_uso'     => ['label' => __('En uso', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '🚴'],
                'reservada'  => ['label' => __('Reservada', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '🟡'],
                'reparacion' => ['label' => __('En reparación', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '🔧'],
            ],

            'stats' => [
                'bicis_disponibles' => ['label' => __('Disponibles', 'flavor-chat-ia'), 'icon' => '🚲', 'color' => 'lime'],
                'en_uso'            => ['label' => __('En uso', 'flavor-chat-ia'), 'icon' => '🚴', 'color' => 'blue'],
                'estaciones'        => ['label' => __('Estaciones', 'flavor-chat-ia'), 'icon' => '📍', 'color' => 'purple'],
                'km_recorridos'     => ['label' => __('km recorridos', 'flavor-chat-ia'), 'icon' => '🛤️', 'color' => 'green'],
            ],

            'card' => [
                'template'     => 'bicicleta-card',
                'title_field'  => 'codigo',
                'subtitle_field' => 'tipo',
                'meta_fields'  => ['estacion', 'bateria'],
                'show_estado'  => true,
            ],

            'tabs' => [
                'mapa' => [
                    'label'   => __('Mapa', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-location',
                    'content' => 'shortcode:bicicletas_mapa',
                    'public'  => true,
                ],
                'estaciones' => [
                    'label'   => __('Estaciones', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-marker',
                    'content' => 'template:_archive.php',
                    'public'  => true,
                ],
                'alquilar' => [
                    'label'      => __('Alquilar', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-unlock',
                    'content'    => 'shortcode:bicicletas_alquilar',
                    'requires_login' => true,
                ],
                'mis-alquileres' => [
                    'label'      => __('Mis alquileres', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => 'shortcode:bicicletas_mis_alquileres',
                    'requires_login' => true,
                ],
            ],

            'archive' => [
                'columns'    => 3,
                'per_page'   => 12,
                'order_by'   => 'codigo',
                'order'      => 'ASC',
                'filterable' => ['tipo', 'estado', 'estacion'],
                'show_mapa'  => true,
            ],

            'dashboard' => [
                'widgets' => ['mapa_tiempo_real', 'stats', 'mis_alquileres', 'estaciones_cercanas'],
                'actions' => [
                    'alquilar'  => ['label' => __('Alquilar bici', 'flavor-chat-ia'), 'icon' => '🚲', 'color' => 'lime'],
                    'devolver'  => ['label' => __('Devolver bici', 'flavor-chat-ia'), 'icon' => '🔒', 'color' => 'blue'],
                ],
            ],

            'features' => [
                'geolocalizacion' => true,
                'tiempo_real'     => true,
                'reservas'        => true,
                'qr_desbloqueo'   => true,
                'estadisticas'    => true,
            ],
        ];
    }
}
