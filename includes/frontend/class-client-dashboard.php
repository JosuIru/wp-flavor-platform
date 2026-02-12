<?php
/**
 * Dashboard de Cliente Frontend
 *
 * Renderiza un dashboard completo para usuarios con estadisticas,
 * widgets modulares, actividad reciente y accesos rapidos.
 * Los modulos pueden registrar sus propios widgets.
 *
 * @package FlavorChatIA
 * @subpackage Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del dashboard de cliente
 */
class Flavor_Client_Dashboard {

    /**
     * Instancia singleton
     *
     * @var Flavor_Client_Dashboard|null
     */
    private static $instancia = null;

    /**
     * Indica si el shortcode esta presente en la pagina actual
     *
     * @var bool
     */
    private $shortcode_presente_en_pagina = false;

    /**
     * Widgets registrados por modulos
     *
     * @var array
     */
    private $widgets_registrados = [];

    /**
     * Estadisticas registradas
     *
     * @var array
     */
    private $estadisticas_registradas = [];

    /**
     * Atajos rapidos registrados
     *
     * @var array
     */
    private $atajos_registrados = [];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Client_Dashboard
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado: registra shortcode, hooks y assets
     */
    private function __construct() {
        add_shortcode('flavor_client_dashboard', [$this, 'render_dashboard']);
        add_action('wp', [$this, 'detectar_shortcode_en_pagina']);
        add_action('wp_enqueue_scripts', [$this, 'encolar_assets_condicional']);

        // Endpoints AJAX
        add_action('wp_ajax_flavor_client_dashboard_stats', [$this, 'ajax_obtener_estadisticas']);
        add_action('wp_ajax_flavor_client_dashboard_widgets', [$this, 'ajax_obtener_widgets']);
        add_action('wp_ajax_flavor_client_dashboard_activity', [$this, 'ajax_obtener_actividad']);
        add_action('wp_ajax_flavor_client_dashboard_notifications', [$this, 'ajax_obtener_notificaciones']);
        add_action('wp_ajax_flavor_client_dashboard_dismiss_notification', [$this, 'ajax_descartar_notificacion']);
        add_action('wp_ajax_flavor_client_dashboard_save_preferences', [$this, 'ajax_guardar_preferencias']);

        // Registrar estadisticas y widgets por defecto
        add_action('init', [$this, 'registrar_elementos_por_defecto'], 20);

        // Hook para que modulos registren sus widgets
        add_action('flavor_client_dashboard_init', [$this, 'inicializar_widgets_modulos']);
    }

    /**
     * Detecta si el shortcode esta presente en la pagina actual
     */
    public function detectar_shortcode_en_pagina() {
        global $post;

        if (!$post || !is_singular()) {
            return;
        }

        if (has_shortcode($post->post_content, 'flavor_client_dashboard')) {
            $this->shortcode_presente_en_pagina = true;
        }
    }

    /**
     * Encola CSS y JS solo cuando el shortcode esta presente
     */
    public function encolar_assets_condicional() {
        if (!$this->shortcode_presente_en_pagina) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // Estilos
        wp_enqueue_style(
            'flavor-client-dashboard',
            FLAVOR_CHAT_IA_URL . "assets/css/client-dashboard{$sufijo_asset}.css",
            ['flavor-base'],
            FLAVOR_CHAT_IA_VERSION
        );

        // Scripts
        wp_enqueue_script(
            'flavor-client-dashboard',
            FLAVOR_CHAT_IA_URL . "assets/js/client-dashboard{$sufijo_asset}.js",
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        $usuario_actual = wp_get_current_user();
        $preferencias_usuario = $this->obtener_preferencias_usuario($usuario_actual->ID);

        $datos_localizados = [
            'ajaxUrl'           => admin_url('admin-ajax.php'),
            'nonce'             => wp_create_nonce('flavor_client_dashboard'),
            'userId'            => get_current_user_id(),
            'userName'          => $usuario_actual->exists() ? $usuario_actual->display_name : '',
            'refreshInterval'   => 120000,
            'preferences'       => $preferencias_usuario,
            'i18n'              => [
                'cargando'                => __('Cargando...', 'flavor-chat-ia'),
                'error_conexion'          => __('Error de conexion. Intentalo de nuevo.', 'flavor-chat-ia'),
                'actualizado'             => __('Datos actualizados', 'flavor-chat-ia'),
                'sin_actividad'           => __('No hay actividad reciente', 'flavor-chat-ia'),
                'sin_notificaciones'      => __('No tienes notificaciones pendientes', 'flavor-chat-ia'),
                'notificacion_descartada' => __('Notificacion descartada', 'flavor-chat-ia'),
                'preferencias_guardadas'  => __('Preferencias guardadas', 'flavor-chat-ia'),
                'ver_todo'                => __('Ver todo', 'flavor-chat-ia'),
                'hace_momentos'           => __('Hace unos momentos', 'flavor-chat-ia'),
                'hace_minutos'            => __('Hace %d minutos', 'flavor-chat-ia'),
                'hace_horas'              => __('Hace %d horas', 'flavor-chat-ia'),
                'hace_dias'               => __('Hace %d dias', 'flavor-chat-ia'),
                'atajo_actualizar'        => __('Ctrl+R para actualizar', 'flavor-chat-ia'),
                'atajo_buscar'            => __('Ctrl+K para buscar', 'flavor-chat-ia'),
            ],
        ];

        wp_localize_script('flavor-client-dashboard', 'flavorClientDashboard', $datos_localizados);
    }

    /**
     * Registra elementos por defecto del dashboard
     */
    public function registrar_elementos_por_defecto() {
        // Estadistica: Reservas del usuario
        $this->registrar_estadistica('reservas', [
            'label'    => __('Mis Reservas', 'flavor-chat-ia'),
            'icon'     => 'calendar',
            'color'    => 'primary',
            'callback' => [$this, 'obtener_estadistica_reservas'],
            'url'      => home_url('/mi-cuenta/?tab=reservas'),
            'orden'    => 10,
        ]);

        // Estadistica: Participaciones
        $this->registrar_estadistica('participaciones', [
            'label'    => __('Participaciones', 'flavor-chat-ia'),
            'icon'     => 'users',
            'color'    => 'success',
            'callback' => [$this, 'obtener_estadistica_participaciones'],
            'url'      => home_url('/mi-cuenta/?tab=participaciones'),
            'orden'    => 20,
        ]);

        // Estadistica: Puntos
        $this->registrar_estadistica('puntos', [
            'label'    => __('Mis Puntos', 'flavor-chat-ia'),
            'icon'     => 'star',
            'color'    => 'warning',
            'callback' => [$this, 'obtener_estadistica_puntos'],
            'url'      => home_url('/mi-cuenta/?tab=puntos'),
            'orden'    => 30,
        ]);

        // Estadistica: Mensajes sin leer
        $this->registrar_estadistica('mensajes', [
            'label'    => __('Mensajes', 'flavor-chat-ia'),
            'icon'     => 'message',
            'color'    => 'info',
            'callback' => [$this, 'obtener_estadistica_mensajes'],
            'url'      => home_url('/mi-cuenta/?tab=mensajes'),
            'orden'    => 40,
        ]);

        // Atajos rapidos por defecto
        $this->registrar_atajo('nueva-reserva', [
            'label'  => __('Nueva Reserva', 'flavor-chat-ia'),
            'icon'   => 'plus-circle',
            'url'    => home_url('/reservas/nueva/'),
            'color'  => 'primary',
            'orden'  => 10,
        ]);

        $this->registrar_atajo('mi-perfil', [
            'label'  => __('Mi Perfil', 'flavor-chat-ia'),
            'icon'   => 'user',
            'url'    => home_url('/mi-cuenta/?tab=perfil'),
            'color'  => 'secondary',
            'orden'  => 20,
        ]);

        $this->registrar_atajo('soporte', [
            'label'  => __('Soporte', 'flavor-chat-ia'),
            'icon'   => 'help-circle',
            'url'    => home_url('/soporte/'),
            'color'  => 'info',
            'orden'  => 30,
        ]);

        // Widget: Proximas reservas
        $this->registrar_widget('proximas-reservas', [
            'title'    => __('Proximas Reservas', 'flavor-chat-ia'),
            'icon'     => 'calendar',
            'callback' => [$this, 'render_widget_proximas_reservas'],
            'size'     => 'medium',
            'orden'    => 10,
        ]);

        // Widget: Mensajes recientes
        $this->registrar_widget('mensajes-recientes', [
            'title'    => __('Mensajes Recientes', 'flavor-chat-ia'),
            'icon'     => 'message',
            'callback' => [$this, 'render_widget_mensajes_recientes'],
            'size'     => 'medium',
            'orden'    => 20,
        ]);

        /**
         * Hook para que modulos registren sus propios widgets
         *
         * Ejemplo de uso:
         *   add_action('flavor_client_dashboard_init', function($dashboard) {
         *       $dashboard->registrar_widget('mi-widget', [
         *           'title'    => 'Mi Widget',
         *           'icon'     => 'star',
         *           'callback' => [$this, 'render_mi_widget'],
         *           'size'     => 'small',
         *           'orden'    => 50,
         *       ]);
         *   });
         */
        do_action('flavor_client_dashboard_init', $this);
    }

    /**
     * Inicializa widgets de modulos activos
     *
     * @param Flavor_Client_Dashboard $dashboard Instancia del dashboard
     */
    public function inicializar_widgets_modulos($dashboard) {
        // Los modulos usan el hook flavor_client_dashboard_init
        // para registrar sus widgets
    }

    /**
     * Registra una estadistica
     *
     * @param string $identificador Identificador unico
     * @param array  $configuracion Configuracion de la estadistica
     */
    public function registrar_estadistica($identificador, $configuracion) {
        $this->estadisticas_registradas[$identificador] = wp_parse_args($configuracion, [
            'label'    => '',
            'icon'     => 'chart',
            'color'    => 'primary',
            'callback' => null,
            'url'      => '',
            'orden'    => 50,
        ]);
    }

    /**
     * Registra un widget
     *
     * @param string $identificador Identificador unico
     * @param array  $configuracion Configuracion del widget
     */
    public function registrar_widget($identificador, $configuracion) {
        $this->widgets_registrados[$identificador] = wp_parse_args($configuracion, [
            'title'    => '',
            'icon'     => 'box',
            'callback' => null,
            'size'     => 'medium',
            'orden'    => 50,
            'modulo'   => '',
        ]);
    }

    /**
     * Registra un atajo rapido
     *
     * @param string $identificador Identificador unico
     * @param array  $configuracion Configuracion del atajo
     */
    public function registrar_atajo($identificador, $configuracion) {
        $this->atajos_registrados[$identificador] = wp_parse_args($configuracion, [
            'label'  => '',
            'icon'   => 'link',
            'url'    => '',
            'color'  => 'secondary',
            'orden'  => 50,
            'target' => '_self',
        ]);
    }

    /**
     * Obtiene todas las estadisticas ordenadas
     *
     * @return array
     */
    public function obtener_estadisticas() {
        $estadisticas = apply_filters('flavor_client_dashboard_estadisticas', $this->estadisticas_registradas);

        uasort($estadisticas, function ($estadistica_a, $estadistica_b) {
            return ($estadistica_a['orden'] ?? 50) - ($estadistica_b['orden'] ?? 50);
        });

        return $estadisticas;
    }

    /**
     * Obtiene todos los widgets ordenados
     *
     * @return array
     */
    public function obtener_widgets() {
        $widgets = apply_filters('flavor_client_dashboard_widgets', $this->widgets_registrados);

        uasort($widgets, function ($widget_a, $widget_b) {
            return ($widget_a['orden'] ?? 50) - ($widget_b['orden'] ?? 50);
        });

        return $widgets;
    }

    /**
     * Obtiene todos los atajos ordenados
     *
     * @return array
     */
    public function obtener_atajos() {
        $atajos = apply_filters('flavor_client_dashboard_atajos', $this->atajos_registrados);

        uasort($atajos, function ($atajo_a, $atajo_b) {
            return ($atajo_a['orden'] ?? 50) - ($atajo_b['orden'] ?? 50);
        });

        return $atajos;
    }

    /**
     * Renderiza el dashboard completo
     *
     * @param array $atributos_shortcode Atributos del shortcode
     * @return string HTML del dashboard
     */
    public function render_dashboard($atributos_shortcode = []) {
        $this->shortcode_presente_en_pagina = true;

        if (!is_user_logged_in()) {
            return $this->render_acceso_requerido();
        }

        $atributos = shortcode_atts([
            'mostrar_estadisticas' => 'true',
            'mostrar_atajos'       => 'true',
            'mostrar_actividad'    => 'true',
            'mostrar_widgets'      => 'true',
            'mostrar_notificaciones' => 'true',
            'columnas_widgets'     => 2,
        ], $atributos_shortcode);

        $usuario_actual = wp_get_current_user();
        $avatar_url     = get_avatar_url($usuario_actual->ID, ['size' => 128]);
        $nombre_usuario = $this->obtener_nombre_usuario($usuario_actual);
        $saludo         = $this->obtener_saludo_personalizado();

        // Preparar datos
        $estadisticas        = $this->obtener_estadisticas_con_valores($usuario_actual->ID);
        $atajos              = $this->obtener_atajos();
        $widgets             = $this->obtener_widgets();
        $actividad_reciente  = $this->obtener_actividad_reciente($usuario_actual->ID, 10);
        $notificaciones      = $this->obtener_notificaciones_usuario($usuario_actual->ID, 5);
        $preferencias        = $this->obtener_preferencias_usuario($usuario_actual->ID);

        ob_start();

        $datos_template = [
            'usuario'            => $usuario_actual,
            'avatar_url'         => $avatar_url,
            'nombre_usuario'     => $nombre_usuario,
            'saludo'             => $saludo,
            'estadisticas'       => $estadisticas,
            'atajos'             => $atajos,
            'widgets'            => $widgets,
            'actividad_reciente' => $actividad_reciente,
            'notificaciones'     => $notificaciones,
            'preferencias'       => $preferencias,
            'atributos'          => $atributos,
            'dashboard_instance' => $this,
        ];

        $ruta_template = FLAVOR_CHAT_IA_PATH . 'templates/frontend/dashboard/client-dashboard.php';
        if (file_exists($ruta_template)) {
            extract($datos_template);
            include $ruta_template;
        }

        return ob_get_clean();
    }

    /**
     * Renderiza mensaje de acceso requerido
     *
     * @return string HTML
     */
    private function render_acceso_requerido() {
        ob_start();
        ?>
        <div class="flavor-client-dashboard flavor-client-dashboard--login-required">
            <div class="flavor-client-dashboard__login-box">
                <div class="flavor-client-dashboard__login-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                </div>
                <h2><?php esc_html_e('Acceso Requerido', 'flavor-chat-ia'); ?></h2>
                <p><?php esc_html_e('Necesitas iniciar sesion para acceder a tu panel personal.', 'flavor-chat-ia'); ?></p>
                <div class="flavor-client-dashboard__login-actions">
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-btn flavor-btn--primary">
                        <?php esc_html_e('Iniciar Sesion', 'flavor-chat-ia'); ?>
                    </a>
                    <?php if (get_option('users_can_register')) : ?>
                        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="flavor-btn flavor-btn--outline">
                            <?php esc_html_e('Crear Cuenta', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene el nombre del usuario para mostrar
     *
     * @param WP_User $usuario Usuario
     * @return string
     */
    private function obtener_nombre_usuario($usuario) {
        $nombre_completo = trim($usuario->first_name . ' ' . $usuario->last_name);

        if (!empty($nombre_completo)) {
            return $nombre_completo;
        }

        return $usuario->display_name;
    }

    /**
     * Genera un saludo personalizado segun la hora
     *
     * @return string
     */
    private function obtener_saludo_personalizado() {
        $hora_actual = (int) current_time('G');

        if ($hora_actual >= 5 && $hora_actual < 12) {
            return __('Buenos dias', 'flavor-chat-ia');
        } elseif ($hora_actual >= 12 && $hora_actual < 20) {
            return __('Buenas tardes', 'flavor-chat-ia');
        } else {
            return __('Buenas noches', 'flavor-chat-ia');
        }
    }

    /**
     * Obtiene las estadisticas con sus valores calculados
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_estadisticas_con_valores($id_usuario) {
        $estadisticas = $this->obtener_estadisticas();
        $resultados = [];

        foreach ($estadisticas as $identificador => $configuracion) {
            $valor = 0;
            $texto_secundario = '';

            if (is_callable($configuracion['callback'])) {
                $resultado = call_user_func($configuracion['callback'], $id_usuario);

                if (is_array($resultado)) {
                    $valor = $resultado['valor'] ?? 0;
                    $texto_secundario = $resultado['texto'] ?? '';
                } else {
                    $valor = $resultado;
                }
            }

            $resultados[$identificador] = [
                'label'     => $configuracion['label'],
                'valor'     => $valor,
                'texto'     => $texto_secundario,
                'icon'      => $configuracion['icon'],
                'color'     => $configuracion['color'],
                'url'       => $configuracion['url'],
            ];
        }

        return $resultados;
    }

    /**
     * Obtiene la actividad reciente del usuario
     *
     * @param int $id_usuario ID del usuario
     * @param int $limite     Cantidad maxima de items
     * @return array
     */
    public function obtener_actividad_reciente($id_usuario, $limite = 10) {
        $actividad = [];

        /**
         * Filtro para que modulos agreguen actividad
         *
         * @param array $actividad Actividad actual
         * @param int   $id_usuario ID del usuario
         * @param int   $limite Limite de items
         */
        $actividad = apply_filters('flavor_client_dashboard_actividad', $actividad, $id_usuario, $limite);

        // Ordenar por fecha descendente
        usort($actividad, function ($actividad_a, $actividad_b) {
            $fecha_a = strtotime($actividad_a['fecha'] ?? '');
            $fecha_b = strtotime($actividad_b['fecha'] ?? '');
            return $fecha_b - $fecha_a;
        });

        return array_slice($actividad, 0, $limite);
    }

    /**
     * Obtiene las notificaciones del usuario
     *
     * @param int $id_usuario ID del usuario
     * @param int $limite     Cantidad maxima
     * @return array
     */
    public function obtener_notificaciones_usuario($id_usuario, $limite = 5) {
        $notificaciones = [];

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $notificaciones = $gestor_notificaciones->get_user_notifications($id_usuario, [
                'limit'  => $limite,
                'unread' => true,
            ]);
        }

        return apply_filters('flavor_client_dashboard_notificaciones', $notificaciones, $id_usuario);
    }

    /**
     * Obtiene preferencias del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_preferencias_usuario($id_usuario) {
        $preferencias_por_defecto = [
            'tema'                  => 'auto',
            'widgets_colapsados'    => [],
            'orden_widgets'         => [],
            'mostrar_actividad'     => true,
            'mostrar_notificaciones' => true,
        ];

        $preferencias_guardadas = get_user_meta($id_usuario, 'flavor_client_dashboard_preferences', true);

        if (!is_array($preferencias_guardadas)) {
            $preferencias_guardadas = [];
        }

        return wp_parse_args($preferencias_guardadas, $preferencias_por_defecto);
    }

    /**
     * Guarda preferencias del usuario
     *
     * @param int   $id_usuario   ID del usuario
     * @param array $preferencias Preferencias a guardar
     * @return bool
     */
    public function guardar_preferencias_usuario($id_usuario, $preferencias) {
        $preferencias_actuales = $this->obtener_preferencias_usuario($id_usuario);
        $preferencias_nuevas = wp_parse_args($preferencias, $preferencias_actuales);

        return update_user_meta($id_usuario, 'flavor_client_dashboard_preferences', $preferencias_nuevas);
    }

    // =========================================================================
    // Callbacks de estadisticas por defecto
    // =========================================================================

    /**
     * Obtiene estadistica de reservas del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_estadistica_reservas($id_usuario) {
        global $wpdb;

        $total_reservas = 0;
        $proximas_reservas = 0;

        // Intentar obtener de diferentes fuentes
        $tabla_reservas = $wpdb->prefix . 'flavor_reservations';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'") === $tabla_reservas) {
            $total_reservas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_reservas WHERE user_id = %d",
                $id_usuario
            ));

            $proximas_reservas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_reservas WHERE user_id = %d AND fecha >= CURDATE() AND status IN ('confirmed', 'pending')",
                $id_usuario
            ));
        }

        return [
            'valor' => $total_reservas,
            'texto' => sprintf(__('%d proximas', 'flavor-chat-ia'), $proximas_reservas),
        ];
    }

    /**
     * Obtiene estadistica de participaciones del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_estadistica_participaciones($id_usuario) {
        $participaciones = 0;

        // Contar inscripciones en eventos, cursos, talleres, etc.
        $participaciones = apply_filters('flavor_client_dashboard_participaciones_count', $participaciones, $id_usuario);

        return [
            'valor' => $participaciones,
            'texto' => __('Este mes', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene estadistica de puntos del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_estadistica_puntos($id_usuario) {
        $puntos = (int) get_user_meta($id_usuario, 'flavor_user_points', true);
        $nivel = $this->calcular_nivel_usuario($puntos);

        return [
            'valor' => $puntos,
            'texto' => sprintf(__('Nivel %s', 'flavor-chat-ia'), $nivel),
        ];
    }

    /**
     * Obtiene estadistica de mensajes del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_estadistica_mensajes($id_usuario) {
        $mensajes_sin_leer = 0;

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $mensajes_sin_leer = $gestor_notificaciones->get_unread_count($id_usuario);
        }

        return [
            'valor' => $mensajes_sin_leer,
            'texto' => __('Sin leer', 'flavor-chat-ia'),
        ];
    }

    /**
     * Calcula el nivel del usuario segun sus puntos
     *
     * @param int $puntos Puntos del usuario
     * @return string
     */
    private function calcular_nivel_usuario($puntos) {
        if ($puntos >= 10000) {
            return __('Experto', 'flavor-chat-ia');
        } elseif ($puntos >= 5000) {
            return __('Avanzado', 'flavor-chat-ia');
        } elseif ($puntos >= 1000) {
            return __('Intermedio', 'flavor-chat-ia');
        } elseif ($puntos >= 100) {
            return __('Basico', 'flavor-chat-ia');
        }

        return __('Nuevo', 'flavor-chat-ia');
    }

    // =========================================================================
    // Widgets por defecto
    // =========================================================================

    /**
     * Renderiza widget de proximas reservas
     *
     * @param int $id_usuario ID del usuario
     */
    public function render_widget_proximas_reservas($id_usuario) {
        global $wpdb;

        $reservas = [];
        $tabla_reservas = $wpdb->prefix . 'flavor_reservations';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'") === $tabla_reservas) {
            $reservas = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_reservas
                WHERE user_id = %d AND fecha >= CURDATE() AND status IN ('confirmed', 'pending')
                ORDER BY fecha ASC, hora ASC
                LIMIT 5",
                $id_usuario
            ), ARRAY_A);
        }

        if (empty($reservas)) {
            echo '<div class="flavor-widget-empty">';
            echo '<p>' . esc_html__('No tienes reservas proximas', 'flavor-chat-ia') . '</p>';
            echo '<a href="' . esc_url(home_url('/reservas/')) . '" class="flavor-btn flavor-btn--sm flavor-btn--outline">' . esc_html__('Hacer una reserva', 'flavor-chat-ia') . '</a>';
            echo '</div>';
            return;
        }

        echo '<ul class="flavor-widget-list">';
        foreach ($reservas as $reserva) {
            $fecha_formateada = date_i18n(get_option('date_format'), strtotime($reserva['fecha']));
            $hora_formateada = isset($reserva['hora']) ? date_i18n(get_option('time_format'), strtotime($reserva['hora'])) : '';
            $estado_clase = $reserva['status'] === 'confirmed' ? 'success' : 'warning';

            echo '<li class="flavor-widget-list__item">';
            echo '<div class="flavor-widget-list__content">';
            echo '<span class="flavor-widget-list__title">' . esc_html($reserva['servicio'] ?? __('Reserva', 'flavor-chat-ia')) . '</span>';
            echo '<span class="flavor-widget-list__meta">' . esc_html($fecha_formateada);
            if ($hora_formateada) {
                echo ' - ' . esc_html($hora_formateada);
            }
            echo '</span>';
            echo '</div>';
            echo '<span class="flavor-badge flavor-badge--' . esc_attr($estado_clase) . '">' . esc_html(ucfirst($reserva['status'])) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * Renderiza widget de mensajes recientes
     *
     * @param int $id_usuario ID del usuario
     */
    public function render_widget_mensajes_recientes($id_usuario) {
        $notificaciones = $this->obtener_notificaciones_usuario($id_usuario, 5);

        if (empty($notificaciones)) {
            echo '<div class="flavor-widget-empty">';
            echo '<p>' . esc_html__('No tienes mensajes nuevos', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        echo '<ul class="flavor-widget-list">';
        foreach ($notificaciones as $notificacion) {
            $titulo = $notificacion['title'] ?? __('Notificacion', 'flavor-chat-ia');
            $fecha = isset($notificacion['created_at']) ? human_time_diff(strtotime($notificacion['created_at'])) : '';

            echo '<li class="flavor-widget-list__item">';
            echo '<div class="flavor-widget-list__content">';
            echo '<span class="flavor-widget-list__title">' . esc_html($titulo) . '</span>';
            if ($fecha) {
                echo '<span class="flavor-widget-list__meta">' . sprintf(esc_html__('Hace %s', 'flavor-chat-ia'), esc_html($fecha)) . '</span>';
            }
            echo '</div>';
            if (!empty($notificacion['is_read']) && !$notificacion['is_read']) {
                echo '<span class="flavor-indicator flavor-indicator--unread"></span>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    // =========================================================================
    // Helpers para templates
    // =========================================================================

    /**
     * Genera el icono SVG
     *
     * @param string $nombre_icono Nombre del icono
     * @param int    $tamano       Tamano en pixels
     * @return string
     */
    public function obtener_icono_svg($nombre_icono, $tamano = 24) {
        $iconos_disponibles = [
            'calendar'     => '<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
            'users'        => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/>',
            'star'         => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            'message'      => '<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
            'user'         => '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'bell'         => '<path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>',
            'plus-circle'  => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
            'help-circle'  => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'settings'     => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>',
            'activity'     => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
            'clock'        => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'check-circle' => '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'alert-circle' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
            'x'            => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
            'refresh'      => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>',
            'sun'          => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
            'moon'         => '<path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>',
            'box'          => '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
            'link'         => '<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>',
            'chart'        => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'home'         => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
            'shopping-bag' => '<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>',
        ];

        $path_icono = $iconos_disponibles[$nombre_icono] ?? $iconos_disponibles['box'];

        return sprintf(
            '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%2$s</svg>',
            (int) $tamano,
            $path_icono
        );
    }

    /**
     * Formatea una fecha relativa
     *
     * @param string $fecha Fecha en formato timestamp o string
     * @return string
     */
    public function formatear_fecha_relativa($fecha) {
        $timestamp = is_numeric($fecha) ? $fecha : strtotime($fecha);
        $diferencia = time() - $timestamp;

        if ($diferencia < 60) {
            return __('Hace unos momentos', 'flavor-chat-ia');
        } elseif ($diferencia < 3600) {
            $minutos = floor($diferencia / 60);
            return sprintf(__('Hace %d minutos', 'flavor-chat-ia'), $minutos);
        } elseif ($diferencia < 86400) {
            $horas = floor($diferencia / 3600);
            return sprintf(__('Hace %d horas', 'flavor-chat-ia'), $horas);
        } elseif ($diferencia < 604800) {
            $dias = floor($diferencia / 86400);
            return sprintf(__('Hace %d dias', 'flavor-chat-ia'), $dias);
        }

        return date_i18n(get_option('date_format'), $timestamp);
    }

    // =========================================================================
    // Endpoints AJAX
    // =========================================================================

    /**
     * AJAX: Obtener estadisticas actualizadas
     */
    public function ajax_obtener_estadisticas() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $estadisticas = $this->obtener_estadisticas_con_valores($id_usuario);

        wp_send_json_success([
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * AJAX: Obtener contenido de widgets
     */
    public function ajax_obtener_widgets() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $id_widget = isset($_POST['widget_id']) ? sanitize_key($_POST['widget_id']) : '';
        $widgets = $this->obtener_widgets();

        if (!empty($id_widget) && isset($widgets[$id_widget])) {
            ob_start();
            if (is_callable($widgets[$id_widget]['callback'])) {
                call_user_func($widgets[$id_widget]['callback'], $id_usuario);
            }
            $contenido_html = ob_get_clean();

            wp_send_json_success([
                'widget_id' => $id_widget,
                'html'      => $contenido_html,
            ]);
        }

        wp_send_json_error(['message' => __('Widget no encontrado', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Obtener actividad reciente
     */
    public function ajax_obtener_actividad() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $limite = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
        $actividad = $this->obtener_actividad_reciente($id_usuario, $limite);

        wp_send_json_success([
            'actividad' => $actividad,
        ]);
    }

    /**
     * AJAX: Obtener notificaciones
     */
    public function ajax_obtener_notificaciones() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $limite = isset($_POST['limit']) ? absint($_POST['limit']) : 5;
        $notificaciones = $this->obtener_notificaciones_usuario($id_usuario, $limite);

        wp_send_json_success([
            'notificaciones' => $notificaciones,
            'total'          => count($notificaciones),
        ]);
    }

    /**
     * AJAX: Descartar notificacion
     */
    public function ajax_descartar_notificacion() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_notificacion = isset($_POST['notification_id']) ? absint($_POST['notification_id']) : 0;

        if (!$id_notificacion) {
            wp_send_json_error(['message' => __('ID de notificacion no valido', 'flavor-chat-ia')]);
        }

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $gestor_notificaciones->mark_as_read($id_notificacion);
        }

        wp_send_json_success([
            'message' => __('Notificacion descartada', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Guardar preferencias del usuario
     */
    public function ajax_guardar_preferencias() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $preferencias = [];

        if (isset($_POST['tema'])) {
            $preferencias['tema'] = sanitize_key($_POST['tema']);
        }

        if (isset($_POST['widgets_colapsados'])) {
            $preferencias['widgets_colapsados'] = array_map('sanitize_key', (array) $_POST['widgets_colapsados']);
        }

        if (isset($_POST['orden_widgets'])) {
            $preferencias['orden_widgets'] = array_map('sanitize_key', (array) $_POST['orden_widgets']);
        }

        $guardado_exitoso = $this->guardar_preferencias_usuario($id_usuario, $preferencias);

        if ($guardado_exitoso) {
            wp_send_json_success([
                'message'      => __('Preferencias guardadas', 'flavor-chat-ia'),
                'preferencias' => $this->obtener_preferencias_usuario($id_usuario),
            ]);
        }

        wp_send_json_error(['message' => __('Error al guardar preferencias', 'flavor-chat-ia')]);
    }
}

// Inicializar
Flavor_Client_Dashboard::get_instance();
