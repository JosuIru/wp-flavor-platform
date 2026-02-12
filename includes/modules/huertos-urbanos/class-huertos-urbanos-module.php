<?php
/**
 * Módulo de Huertos Urbanos para Chat IA
 * Sistema completo de huertos comunitarios
 *
 * @package FlavorChatIA
 * @subpackage HuertosUrbanos
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Huertos Urbanos - Gestión completa de huertos comunitarios
 */
class Flavor_Chat_Huertos_Urbanos_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Versión del módulo
     */
    const VERSION = '2.0.0';

    /**
     * Prefijo de tablas
     */
    private $tabla_huertos;
    private $tabla_parcelas;
    private $tabla_asignaciones;
    private $tabla_cultivos;
    private $tabla_tareas;
    private $tabla_participantes_tareas;
    private $tabla_intercambios;
    private $tabla_turnos_riego;
    private $tabla_actividades;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;

        $this->id = 'huertos_urbanos';
        $this->name = 'Huertos Urbanos'; // Translation loaded on init
        $this->description = 'Gestión completa de huertos urbanos comunitarios - parcelas, cultivos, tareas, intercambios y más.'; // Translation loaded on init

        // Definir nombres de tablas
        $this->tabla_huertos = $wpdb->prefix . 'flavor_huertos';
        $this->tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
        $this->tabla_asignaciones = $wpdb->prefix . 'flavor_huertos_asignaciones';
        $this->tabla_cultivos = $wpdb->prefix . 'flavor_huertos_cultivos';
        $this->tabla_tareas = $wpdb->prefix . 'flavor_huertos_tareas';
        $this->tabla_participantes_tareas = $wpdb->prefix . 'flavor_huertos_participantes_tareas';
        $this->tabla_intercambios = $wpdb->prefix . 'flavor_huertos_intercambios';
        $this->tabla_turnos_riego = $wpdb->prefix . 'flavor_huertos_turnos_riego';
        $this->tabla_actividades = $wpdb->prefix . 'flavor_huertos_actividades';

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe($this->tabla_huertos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Huertos Urbanos no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
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
            'permite_solicitar_parcela' => true,
            'precio_parcela_anual' => 0,
            'requiere_compromiso_asistencia' => true,
            'horas_minimas_mes' => 4,
            'permite_intercambio_cosechas' => true,
            'sistema_turnos_riego' => true,
            'max_parcelas_por_usuario' => 1,
            'dias_espera_lista' => 30,
            'notificaciones_email' => true,
            'mostrar_mapa_publico' => true,
            'coordenadas_centro_lat' => 40.4168,
            'coordenadas_centro_lng' => -3.7038,
            'zoom_mapa_default' => 12,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();

        // Registrar AJAX handlers
        $this->registrar_ajax_handlers();

        // Registrar shortcodes
        $this->registrar_shortcodes();

        // Registrar REST API
        add_action('rest_api_init', [$this, 'registrar_rest_routes']);

        // Cron para notificaciones
        add_action('flavor_huertos_cron_notificaciones', [$this, 'enviar_notificaciones_programadas']);

        if (!wp_next_scheduled('flavor_huertos_cron_notificaciones')) {
            wp_schedule_event(time(), 'daily', 'flavor_huertos_cron_notificaciones');
        }
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'huertos_urbanos',
            'label' => __('Huertos', 'flavor-chat-ia'),
            'icon' => 'dashicons-carrot',
            'capability' => 'manage_options',
            'categoria' => 'sostenibilidad',
            'paginas' => [
                [
                    'slug' => 'huertos-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'huertos-parcelas',
                    'titulo' => __('Parcelas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_parcelas'],
                    'badge' => [$this, 'contar_parcelas_ocupadas'],
                ],
                [
                    'slug' => 'huertos-hortelanos',
                    'titulo' => __('Hortelanos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_hortelanos'],
                ],
                [
                    'slug' => 'huertos-lista-espera',
                    'titulo' => __('Lista de Espera', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_lista_espera'],
                    'badge' => [$this, 'contar_lista_espera'],
                ],
                [
                    'slug' => 'huertos-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta parcelas ocupadas
     *
     * @return int
     */
    public function contar_parcelas_ocupadas() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_parcelas)) {
            return 0;
        }
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_parcelas} WHERE estado = 'ocupada'"
        );
    }

    /**
     * Cuenta solicitudes en lista de espera
     *
     * @return int
     */
    public function contar_lista_espera() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_asignaciones)) {
            return 0;
        }
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_asignaciones} WHERE estado = 'lista_espera'"
        );
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        // Parcelas ocupadas
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_parcelas)) {
            $parcelas_ocupadas = $this->contar_parcelas_ocupadas();
            $total_parcelas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_parcelas}");
            $estadisticas[] = [
                'icon' => 'dashicons-carrot',
                'valor' => $parcelas_ocupadas . '/' . $total_parcelas,
                'label' => __('Parcelas ocupadas', 'flavor-chat-ia'),
                'color' => $parcelas_ocupadas > 0 ? 'green' : 'gray',
                'enlace' => admin_url('admin.php?page=huertos-parcelas'),
            ];
        }

        // Lista de espera
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_asignaciones)) {
            $en_lista_espera = $this->contar_lista_espera();
            $estadisticas[] = [
                'icon' => 'dashicons-clock',
                'valor' => $en_lista_espera,
                'label' => __('Lista de espera', 'flavor-chat-ia'),
                'color' => $en_lista_espera > 0 ? 'orange' : 'gray',
                'enlace' => admin_url('admin.php?page=huertos-lista-espera'),
            ];
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de huertos urbanos
     */
    public function render_admin_dashboard() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Huertos Urbanos', 'flavor-chat-ia'), [
            ['label' => __('Nueva Parcela', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=huertos-parcelas&action=nueva'), 'class' => 'button-primary'],
        ]);

        // Resumen de estadísticas
        global $wpdb;
        $total_parcelas = 0;
        $parcelas_ocupadas = 0;
        $parcelas_disponibles = 0;
        $total_hortelanos = 0;
        $en_lista_espera = 0;

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_parcelas)) {
            $total_parcelas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_parcelas}");
            $parcelas_ocupadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_parcelas} WHERE estado = 'ocupada'");
            $parcelas_disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_parcelas} WHERE estado = 'disponible'");
        }

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_asignaciones)) {
            $total_hortelanos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$this->tabla_asignaciones} WHERE estado = 'activa'");
            $en_lista_espera = $this->contar_lista_espera();
        }

        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_parcelas) . '</span><span class="stat-label">' . __('Total Parcelas', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($parcelas_ocupadas) . '</span><span class="stat-label">' . __('Ocupadas', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($parcelas_disponibles) . '</span><span class="stat-label">' . __('Disponibles', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_hortelanos) . '</span><span class="stat-label">' . __('Hortelanos', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($en_lista_espera) . '</span><span class="stat-label">' . __('En espera', 'flavor-chat-ia') . '</span></div>';
        echo '</div>';

        echo '<p>' . __('Panel de control del módulo de huertos urbanos con métricas y accesos rápidos.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }

    /**
     * Renderiza la página de gestión de parcelas
     */
    public function render_admin_parcelas() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestión de Parcelas', 'flavor-chat-ia'), [
            ['label' => __('Nueva Parcela', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=huertos-parcelas&action=nueva'), 'class' => 'button-primary'],
        ]);

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_parcelas)) {
            echo '<p>' . __('Las tablas del módulo no están creadas.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $parcelas = $wpdb->get_results("SELECT * FROM {$this->tabla_parcelas} ORDER BY codigo ASC", ARRAY_A);

        if (!empty($parcelas)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Código', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Huerto', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Tamaño (m²)', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Estado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Hortelano', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            foreach ($parcelas as $parcela) {
                $clase_estado = $parcela['estado'] === 'ocupada' ? 'status-confirmed' : ($parcela['estado'] === 'disponible' ? 'status-available' : 'status-pending');
                echo '<tr>';
                echo '<td><strong>' . esc_html($parcela['codigo']) . '</strong></td>';
                echo '<td>' . esc_html($parcela['huerto_id']) . '</td>';
                echo '<td>' . esc_html($parcela['tamano_m2'] ?? '-') . '</td>';
                echo '<td><span class="' . esc_attr($clase_estado) . '">' . esc_html(ucfirst($parcela['estado'])) . '</span></td>';
                echo '<td>' . esc_html($parcela['user_id'] ? get_userdata($parcela['user_id'])->display_name ?? '-' : '-') . '</td>';
                echo '<td><a href="#" class="button button-small">' . __('Editar', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay parcelas registradas.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de gestión de hortelanos
     */
    public function render_admin_hortelanos() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestión de Hortelanos', 'flavor-chat-ia'));

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_asignaciones)) {
            echo '<p>' . __('Las tablas del módulo no están creadas.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $hortelanos = $wpdb->get_results(
            "SELECT a.*, p.codigo as parcela_codigo
             FROM {$this->tabla_asignaciones} a
             LEFT JOIN {$this->tabla_parcelas} p ON a.parcela_id = p.id
             WHERE a.estado = 'activa'
             ORDER BY a.created_at DESC",
            ARRAY_A
        );

        if (!empty($hortelanos)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Hortelano', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Email', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Parcela', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Desde', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            foreach ($hortelanos as $hortelano) {
                $usuario = get_userdata($hortelano['user_id']);
                echo '<tr>';
                echo '<td><strong>' . esc_html($usuario ? $usuario->display_name : __('Usuario eliminado', 'flavor-chat-ia')) . '</strong></td>';
                echo '<td>' . esc_html($usuario ? $usuario->user_email : '-') . '</td>';
                echo '<td>' . esc_html($hortelano['parcela_codigo'] ?? '-') . '</td>';
                echo '<td>' . esc_html(date_i18n('d/m/Y', strtotime($hortelano['created_at']))) . '</td>';
                echo '<td><a href="#" class="button button-small">' . __('Ver', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay hortelanos activos.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de lista de espera
     */
    public function render_admin_lista_espera() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Lista de Espera', 'flavor-chat-ia'));

        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_asignaciones)) {
            echo '<p>' . __('Las tablas del módulo no están creadas.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $solicitudes = $wpdb->get_results(
            "SELECT * FROM {$this->tabla_asignaciones}
             WHERE estado = 'lista_espera'
             ORDER BY created_at ASC",
            ARRAY_A
        );

        if (!empty($solicitudes)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Posición', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Solicitante', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Email', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Fecha solicitud', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            $posicion = 1;
            foreach ($solicitudes as $solicitud) {
                $usuario = get_userdata($solicitud['user_id']);
                echo '<tr>';
                echo '<td><strong>#' . esc_html($posicion) . '</strong></td>';
                echo '<td>' . esc_html($usuario ? $usuario->display_name : __('Usuario eliminado', 'flavor-chat-ia')) . '</td>';
                echo '<td>' . esc_html($usuario ? $usuario->user_email : '-') . '</td>';
                echo '<td>' . esc_html(date_i18n('d/m/Y H:i', strtotime($solicitud['created_at']))) . '</td>';
                echo '<td>';
                echo '<a href="#" class="button button-small button-primary">' . __('Asignar', 'flavor-chat-ia') . '</a> ';
                echo '<a href="#" class="button button-small">' . __('Rechazar', 'flavor-chat-ia') . '</a>';
                echo '</td>';
                echo '</tr>';
                $posicion++;
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay solicitudes en lista de espera.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la configuración del módulo de huertos urbanos
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Huertos Urbanos', 'flavor-chat-ia'));

        $configuracion_actual = $this->get_default_settings();
        echo '<form method="post" action="">';
        wp_nonce_field('guardar_config_huertos', 'huertos_config_nonce');
        echo '<table class="form-table">';

        echo '<tr><th scope="row"><label for="max_parcelas_por_usuario">' . __('Máximo parcelas por usuario', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="max_parcelas_por_usuario" id="max_parcelas_por_usuario" value="' . esc_attr($configuracion_actual['max_parcelas_por_usuario']) . '" min="1" max="10" class="small-text" />';
        echo '<p class="description">' . __('Número máximo de parcelas que puede tener un usuario.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="precio_parcela_anual">' . __('Precio anual parcela', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="precio_parcela_anual" id="precio_parcela_anual" value="' . esc_attr($configuracion_actual['precio_parcela_anual']) . '" min="0" step="0.01" class="small-text" /> EUR';
        echo '<p class="description">' . __('0 = gratuito.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="horas_minimas_mes">' . __('Horas mínimas mensuales', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="horas_minimas_mes" id="horas_minimas_mes" value="' . esc_attr($configuracion_actual['horas_minimas_mes']) . '" min="0" class="small-text" />';
        echo '<p class="description">' . __('Horas mínimas de trabajo comunitario al mes.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="sistema_turnos_riego">' . __('Sistema de turnos de riego', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="sistema_turnos_riego" id="sistema_turnos_riego" ' . checked($configuracion_actual['sistema_turnos_riego'], true, false) . ' />';
        echo '<p class="description">' . __('Habilitar gestión de turnos de riego.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="permite_intercambio_cosechas">' . __('Intercambio de cosechas', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="permite_intercambio_cosechas" id="permite_intercambio_cosechas" ' . checked($configuracion_actual['permite_intercambio_cosechas'], true, false) . ' />';
        echo '<p class="description">' . __('Permitir intercambio de cosechas entre hortelanos.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="notificaciones_email">' . __('Notificaciones por email', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="notificaciones_email" id="notificaciones_email" ' . checked($configuracion_actual['notificaciones_email'], true, false) . ' /></td></tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="guardar_config" class="button-primary" value="' . __('Guardar Configuración', 'flavor-chat-ia') . '" /></p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Registra los handlers AJAX
     */
    private function registrar_ajax_handlers() {
        $acciones_ajax = [
            'flavor_huertos_listar',
            'flavor_huertos_detalle',
            'flavor_huertos_mi_parcela',
            'flavor_huertos_cultivos_parcela',
            'flavor_huertos_tareas',
            'flavor_huertos_intercambios',
            'flavor_huertos_solicitar_parcela',
            'flavor_huertos_registrar_cultivo',
            'flavor_huertos_apuntarse_tarea',
            'flavor_huertos_publicar_intercambio',
            'flavor_huertos_completar_tarea',
            'flavor_huertos_registrar_actividad',
            'flavor_huertos_calendario_riego',
            'flavor_huertos_marcar_riego',
            'flavor_huertos_estadisticas',
        ];

        foreach ($acciones_ajax as $accion) {
            add_action('wp_ajax_' . $accion, [$this, 'handle_ajax_' . str_replace('flavor_huertos_', '', $accion)]);
            add_action('wp_ajax_nopriv_' . $accion, [$this, 'handle_ajax_' . str_replace('flavor_huertos_', '', $accion)]);
        }
    }

    /**
     * Registra los shortcodes
     */
    private function registrar_shortcodes() {
        add_shortcode('mapa_huertos', [$this, 'shortcode_mapa_huertos']);
        add_shortcode('mi_parcela', [$this, 'shortcode_mi_parcela']);
        add_shortcode('calendario_cultivos', [$this, 'shortcode_calendario_cultivos']);
        add_shortcode('intercambios_huertos', [$this, 'shortcode_intercambios']);
        add_shortcode('tareas_huerto', [$this, 'shortcode_tareas_huerto']);
        add_shortcode('lista_huertos', [$this, 'shortcode_lista_huertos']);
    }

    /**
     * Encola assets del frontend
     */
    public function enqueue_assets() {
        if (!$this->debe_cargar_assets()) {
            return;
        }

        $modulo_url = plugin_dir_url(__FILE__);

        // CSS
        wp_enqueue_style(
            'flavor-huertos-css',
            $modulo_url . 'assets/css/huertos.css',
            [],
            self::VERSION
        );

        // Leaflet para mapas
        wp_enqueue_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        wp_enqueue_script(
            'leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        // JS del módulo
        wp_enqueue_script(
            'flavor-huertos-js',
            $modulo_url . 'assets/js/huertos.js',
            ['jquery', 'leaflet-js'],
            self::VERSION,
            true
        );

        // Localizar script
        wp_localize_script('flavor-huertos-js', 'flavorHuertosConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_huertos_nonce'),
            'restUrl' => rest_url('flavor-huertos/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
            'settings' => $this->get_settings(),
            'i18n' => [
                'error_conexion' => __('Error de conexión', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'sin_resultados' => __('No se encontraron resultados', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Determina si debe cargar assets
     */
    private function debe_cargar_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        $shortcodes = ['mapa_huertos', 'mi_parcela', 'calendario_cultivos', 'intercambios_huertos', 'tareas_huerto', 'lista_huertos'];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encola assets del admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'flavor-chat') === false) {
            return;
        }

        $modulo_url = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'flavor-huertos-admin-css',
            $modulo_url . 'assets/css/huertos.css',
            [],
            self::VERSION
        );
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_huertos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea todas las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de huertos
        $sql_huertos = "CREATE TABLE IF NOT EXISTS {$this->tabla_huertos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            direccion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            superficie_m2 int(11) NOT NULL,
            num_parcelas int(11) NOT NULL,
            parcelas_disponibles int(11) DEFAULT 0,
            coordinador_id bigint(20) unsigned DEFAULT NULL,
            equipamiento text DEFAULT NULL COMMENT 'JSON: herramientas, riego, compostadora',
            normas text DEFAULT NULL,
            horario_acceso varchar(255) DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            contacto_email varchar(255) DEFAULT NULL,
            contacto_telefono varchar(50) DEFAULT NULL,
            precio_anual decimal(10,2) DEFAULT 0,
            estado enum('activo','mantenimiento','inactivo') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY coordinador_id (coordinador_id),
            KEY ubicacion (latitud, longitud),
            KEY estado (estado)
        ) $charset_collate;";

        // Tabla de parcelas
        $sql_parcelas = "CREATE TABLE IF NOT EXISTS {$this->tabla_parcelas} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) unsigned NOT NULL,
            numero_parcela varchar(20) NOT NULL,
            superficie_m2 decimal(10,2) NOT NULL,
            tipo_suelo varchar(100) DEFAULT NULL,
            orientacion enum('norte','sur','este','oeste','noreste','noroeste','sureste','suroeste') DEFAULT NULL,
            tiene_sombra tinyint(1) DEFAULT 0,
            tiene_riego tinyint(1) DEFAULT 1,
            coordenadas_poligono text DEFAULT NULL COMMENT 'JSON con coordenadas del polígono',
            estado enum('disponible','ocupada','reservada','mantenimiento') DEFAULT 'disponible',
            notas text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY huerto_numero (huerto_id, numero_parcela),
            KEY estado (estado),
            FOREIGN KEY (huerto_id) REFERENCES {$this->tabla_huertos}(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Tabla de asignaciones de parcelas
        $sql_asignaciones = "CREATE TABLE IF NOT EXISTS {$this->tabla_asignaciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parcela_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha_solicitud datetime NOT NULL,
            fecha_asignacion datetime DEFAULT NULL,
            fecha_fin datetime DEFAULT NULL,
            motivacion text DEFAULT NULL,
            experiencia_previa varchar(50) DEFAULT NULL,
            estado enum('pendiente','aprobada','rechazada','activa','finalizada','cancelada') DEFAULT 'pendiente',
            motivo_rechazo text DEFAULT NULL,
            notas_admin text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY parcela_id (parcela_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_solicitud (fecha_solicitud),
            FOREIGN KEY (parcela_id) REFERENCES {$this->tabla_parcelas}(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Tabla de cultivos
        $sql_cultivos = "CREATE TABLE IF NOT EXISTS {$this->tabla_cultivos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parcela_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            nombre_cultivo varchar(255) NOT NULL,
            variedad varchar(255) DEFAULT NULL,
            cantidad_semillas int(11) DEFAULT NULL,
            fecha_siembra date NOT NULL,
            fecha_germinacion date DEFAULT NULL,
            fecha_transplante date DEFAULT NULL,
            fecha_floracion date DEFAULT NULL,
            fecha_cosecha_estimada date DEFAULT NULL,
            fecha_cosecha_real date DEFAULT NULL,
            cantidad_cosechada_kg decimal(10,2) DEFAULT NULL,
            calidad_cosecha enum('excelente','buena','regular','mala') DEFAULT NULL,
            metodo_siembra enum('directa','semillero','transplante','esqueje') DEFAULT 'directa',
            notas text DEFAULT NULL,
            fotos text DEFAULT NULL COMMENT 'JSON array de URLs',
            estado enum('planificado','sembrado','germinando','crecimiento','floracion','maduracion','cosecha','finalizado','fallido') DEFAULT 'planificado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parcela_id (parcela_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_siembra (fecha_siembra),
            KEY nombre_cultivo (nombre_cultivo),
            FOREIGN KEY (parcela_id) REFERENCES {$this->tabla_parcelas}(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Tabla de tareas del huerto
        $sql_tareas = "CREATE TABLE IF NOT EXISTS {$this->tabla_tareas} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) unsigned NOT NULL,
            creador_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('riego','limpieza','mantenimiento','taller','reunion','siembra_comunitaria','cosecha_comunitaria','compostaje','otro') NOT NULL,
            fecha date NOT NULL,
            hora_inicio time DEFAULT NULL,
            hora_fin time DEFAULT NULL,
            max_participantes int(11) DEFAULT NULL,
            ubicacion_especifica varchar(255) DEFAULT NULL,
            materiales_necesarios text DEFAULT NULL,
            es_obligatoria tinyint(1) DEFAULT 0,
            puntos_participacion int(11) DEFAULT 1,
            estado enum('programada','en_curso','completada','cancelada') DEFAULT 'programada',
            notas_completado text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY huerto_id (huerto_id),
            KEY fecha (fecha),
            KEY tipo (tipo),
            KEY estado (estado),
            FOREIGN KEY (huerto_id) REFERENCES {$this->tabla_huertos}(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Tabla de participantes en tareas
        $sql_participantes = "CREATE TABLE IF NOT EXISTS {$this->tabla_participantes_tareas} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tarea_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            asistio tinyint(1) DEFAULT NULL,
            fecha_confirmacion_asistencia datetime DEFAULT NULL,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tarea_usuario (tarea_id, usuario_id),
            KEY usuario_id (usuario_id),
            FOREIGN KEY (tarea_id) REFERENCES {$this->tabla_tareas}(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Tabla de intercambios
        $sql_intercambios = "CREATE TABLE IF NOT EXISTS {$this->tabla_intercambios} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            huerto_id bigint(20) unsigned DEFAULT NULL,
            tipo enum('semillas','cosecha','plantulas','herramientas','conocimiento') NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            cantidad varchar(100) DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            busca_a_cambio text DEFAULT NULL,
            disponible_hasta date DEFAULT NULL,
            contacto_preferido enum('mensaje','email','telefono','presencial') DEFAULT 'mensaje',
            estado enum('disponible','reservado','intercambiado','cancelado','expirado') DEFAULT 'disponible',
            veces_visto int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY huerto_id (huerto_id),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        // Tabla de turnos de riego
        $sql_turnos = "CREATE TABLE IF NOT EXISTS {$this->tabla_turnos_riego} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha_turno date NOT NULL,
            hora_inicio time DEFAULT '08:00:00',
            hora_fin time DEFAULT '10:00:00',
            zona_riego varchar(100) DEFAULT NULL,
            completado tinyint(1) DEFAULT 0,
            fecha_completado datetime DEFAULT NULL,
            sustituido_por bigint(20) unsigned DEFAULT NULL,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY huerto_id (huerto_id),
            KEY usuario_id (usuario_id),
            KEY fecha_turno (fecha_turno),
            KEY completado (completado),
            FOREIGN KEY (huerto_id) REFERENCES {$this->tabla_huertos}(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Tabla de actividades/registro
        $sql_actividades = "CREATE TABLE IF NOT EXISTS {$this->tabla_actividades} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) unsigned DEFAULT NULL,
            parcela_id bigint(20) unsigned DEFAULT NULL,
            cultivo_id bigint(20) unsigned DEFAULT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo enum('riego','abonado','poda','cosecha','tratamiento','limpieza','siembra','transplante','observacion','otro') NOT NULL,
            descripcion text NOT NULL,
            duracion_minutos int(11) DEFAULT NULL,
            productos_usados text DEFAULT NULL COMMENT 'JSON: productos fitosanitarios, abonos',
            condiciones_clima varchar(255) DEFAULT NULL,
            fotos text DEFAULT NULL COMMENT 'JSON array de URLs',
            fecha_actividad datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY huerto_id (huerto_id),
            KEY parcela_id (parcela_id),
            KEY cultivo_id (cultivo_id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo),
            KEY fecha_actividad (fecha_actividad)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_huertos);
        dbDelta($sql_parcelas);
        dbDelta($sql_asignaciones);
        dbDelta($sql_cultivos);
        dbDelta($sql_tareas);
        dbDelta($sql_participantes);
        dbDelta($sql_intercambios);
        dbDelta($sql_turnos);
        dbDelta($sql_actividades);

        // Insertar datos de ejemplo si las tablas están vacías
        $this->insertar_datos_ejemplo();
    }

    /**
     * Inserta datos de ejemplo
     */
    private function insertar_datos_ejemplo() {
        global $wpdb;

        $existe_huerto = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_huertos}");

        if ($existe_huerto > 0) {
            return;
        }

        // Insertar huerto de ejemplo
        $wpdb->insert($this->tabla_huertos, [
            'nombre' => 'Huerto Comunitario Central',
            'descripcion' => 'Huerto urbano en el centro de la ciudad, ideal para principiantes y familias.',
            'direccion' => 'Calle del Huerto, 15',
            'latitud' => 40.4168,
            'longitud' => -3.7038,
            'superficie_m2' => 2000,
            'num_parcelas' => 40,
            'parcelas_disponibles' => 12,
            'horario_acceso' => 'Lunes a Domingo: 8:00 - 21:00',
            'equipamiento' => json_encode(['herramientas_basicas', 'punto_agua', 'compostadora', 'caseta']),
            'normas' => 'Uso exclusivo de métodos ecológicos. Respetar turnos de riego. Mantener limpia tu parcela.',
            'estado' => 'activo',
        ]);

        $huerto_id = $wpdb->insert_id;

        // Insertar parcelas de ejemplo
        for ($i = 1; $i <= 40; $i++) {
            $estado = $i <= 28 ? 'ocupada' : 'disponible';
            $orientaciones = ['norte', 'sur', 'este', 'oeste'];

            $wpdb->insert($this->tabla_parcelas, [
                'huerto_id' => $huerto_id,
                'numero_parcela' => 'P' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'superficie_m2' => rand(20, 35),
                'orientacion' => $orientaciones[array_rand($orientaciones)],
                'tiene_sombra' => rand(0, 1),
                'tiene_riego' => 1,
                'estado' => $estado,
            ]);
        }
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * Handler AJAX: Listar huertos
     */
    public function handle_ajax_listar() {
        $this->verificar_nonce();

        $latitud = isset($_POST['lat']) ? floatval($_POST['lat']) : 0;
        $longitud = isset($_POST['lng']) ? floatval($_POST['lng']) : 0;

        $resultado = $this->obtener_huertos($latitud, $longitud);

        wp_send_json_success(['huertos' => $resultado]);
    }

    /**
     * Handler AJAX: Detalle de huerto
     */
    public function handle_ajax_detalle() {
        $this->verificar_nonce();

        $huerto_id = intval($_POST['huerto_id'] ?? 0);

        if (!$huerto_id) {
            wp_send_json_error(['message' => __('ID de huerto no válido', 'flavor-chat-ia')]);
        }

        $huerto = $this->obtener_huerto_detalle($huerto_id);

        if (!$huerto) {
            wp_send_json_error(['message' => __('Huerto no encontrado', 'flavor-chat-ia')]);
        }

        wp_send_json_success(['huerto' => $huerto]);
    }

    /**
     * Handler AJAX: Mi parcela
     */
    public function handle_ajax_mi_parcela() {
        $this->verificar_nonce();
        $this->verificar_usuario_logueado();

        $usuario_id = get_current_user_id();
        $parcela = $this->obtener_parcela_usuario($usuario_id);

        if ($parcela) {
            wp_send_json_success(['parcela' => $parcela]);
        } else {
            wp_send_json_success(['parcela' => null, 'message' => __('No tienes parcela asignada', 'flavor-chat-ia')]);
        }
    }

    /**
     * Handler AJAX: Cultivos de parcela
     */
    public function handle_ajax_cultivos_parcela() {
        $this->verificar_nonce();

        $parcela_id = intval($_POST['parcela_id'] ?? 0);

        if (!$parcela_id) {
            wp_send_json_error(['message' => __('ID de parcela no válido', 'flavor-chat-ia')]);
        }

        $cultivos = $this->obtener_cultivos_parcela($parcela_id);

        wp_send_json_success(['cultivos' => $cultivos]);
    }

    /**
     * Handler AJAX: Tareas
     */
    public function handle_ajax_tareas() {
        $this->verificar_nonce();

        $huerto_id = intval($_POST['huerto_id'] ?? 0);
        $tareas = $this->obtener_tareas_proximas($huerto_id);

        wp_send_json_success(['tareas' => $tareas]);
    }

    /**
     * Handler AJAX: Intercambios
     */
    public function handle_ajax_intercambios() {
        $this->verificar_nonce();

        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $intercambios = $this->obtener_intercambios($tipo);

        wp_send_json_success(['intercambios' => $intercambios]);
    }

    /**
     * Handler AJAX: Solicitar parcela
     */
    public function handle_ajax_solicitar_parcela() {
        $this->verificar_nonce();
        $this->verificar_usuario_logueado();

        $huerto_id = intval($_POST['huerto_id'] ?? 0);
        $motivacion = sanitize_textarea_field($_POST['motivacion'] ?? '');
        $experiencia = sanitize_text_field($_POST['experiencia'] ?? '');

        if (!$huerto_id || !$motivacion) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        $resultado = $this->procesar_solicitud_parcela($huerto_id, $motivacion, $experiencia);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * Handler AJAX: Registrar cultivo
     */
    public function handle_ajax_registrar_cultivo() {
        $this->verificar_nonce();
        $this->verificar_usuario_logueado();

        $datos_cultivo = [
            'parcela_id' => intval($_POST['parcela_id'] ?? 0),
            'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
            'variedad' => sanitize_text_field($_POST['variedad'] ?? ''),
            'fecha_siembra' => sanitize_text_field($_POST['fecha_siembra'] ?? ''),
            'fecha_cosecha_estimada' => sanitize_text_field($_POST['fecha_cosecha_estimada'] ?? ''),
            'notas' => sanitize_textarea_field($_POST['notas'] ?? ''),
        ];

        if (!$datos_cultivo['parcela_id'] || !$datos_cultivo['nombre'] || !$datos_cultivo['fecha_siembra']) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        $resultado = $this->crear_cultivo($datos_cultivo);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * Handler AJAX: Apuntarse a tarea
     */
    public function handle_ajax_apuntarse_tarea() {
        $this->verificar_nonce();
        $this->verificar_usuario_logueado();

        $tarea_id = intval($_POST['tarea_id'] ?? 0);

        if (!$tarea_id) {
            wp_send_json_error(['message' => __('ID de tarea no válido', 'flavor-chat-ia')]);
        }

        $resultado = $this->apuntar_usuario_tarea($tarea_id);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * Handler AJAX: Publicar intercambio
     */
    public function handle_ajax_publicar_intercambio() {
        $this->verificar_nonce();
        $this->verificar_usuario_logueado();

        $datos_intercambio = [
            'tipo' => sanitize_text_field($_POST['tipo'] ?? ''),
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'cantidad' => sanitize_text_field($_POST['cantidad'] ?? ''),
            'busca_a_cambio' => sanitize_textarea_field($_POST['busca_a_cambio'] ?? ''),
        ];

        if (!$datos_intercambio['tipo'] || !$datos_intercambio['titulo'] || !$datos_intercambio['descripcion']) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        $resultado = $this->crear_intercambio($datos_intercambio);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * Handler AJAX: Completar tarea
     */
    public function handle_ajax_completar_tarea() {
        $this->verificar_nonce();
        $this->verificar_usuario_logueado();

        $tarea_id = intval($_POST['tarea_id'] ?? 0);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        $resultado = $this->marcar_tarea_completada($tarea_id, $notas);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * Handler AJAX: Registrar actividad
     */
    public function handle_ajax_registrar_actividad() {
        $this->verificar_nonce();
        $this->verificar_usuario_logueado();

        $datos_actividad = [
            'parcela_id' => intval($_POST['parcela_id'] ?? 0),
            'cultivo_id' => intval($_POST['cultivo_id'] ?? 0),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'duracion_minutos' => intval($_POST['duracion_minutos'] ?? 0),
        ];

        $resultado = $this->crear_actividad($datos_actividad);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * Handler AJAX: Calendario de riego
     */
    public function handle_ajax_calendario_riego() {
        $this->verificar_nonce();

        $huerto_id = intval($_POST['huerto_id'] ?? 0);
        $mes = intval($_POST['mes'] ?? date('n'));
        $anio = intval($_POST['anio'] ?? date('Y'));

        $turnos = $this->obtener_turnos_riego($huerto_id, $mes, $anio);

        wp_send_json_success(['turnos' => $turnos]);
    }

    /**
     * Handler AJAX: Marcar riego completado
     */
    public function handle_ajax_marcar_riego() {
        $this->verificar_nonce();
        $this->verificar_usuario_logueado();

        $turno_id = intval($_POST['turno_id'] ?? 0);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        $resultado = $this->marcar_turno_riego_completado($turno_id, $notas);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * Handler AJAX: Estadísticas
     */
    public function handle_ajax_estadisticas() {
        $this->verificar_nonce();

        $huerto_id = intval($_POST['huerto_id'] ?? 0);
        $estadisticas = $this->obtener_estadisticas($huerto_id);

        wp_send_json_success(['estadisticas' => $estadisticas]);
    }

    // =========================================================================
    // MÉTODOS DE DATOS
    // =========================================================================

    /**
     * Obtiene lista de huertos
     */
    private function obtener_huertos($latitud = 0, $longitud = 0) {
        global $wpdb;

        if ($latitud != 0 && $longitud != 0) {
            $sql = $wpdb->prepare(
                "SELECT h.*,
                    (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                FROM {$this->tabla_huertos} h
                WHERE h.estado = 'activo'
                ORDER BY distancia ASC",
                $latitud, $longitud, $latitud
            );
        } else {
            $sql = "SELECT * FROM {$this->tabla_huertos} WHERE estado = 'activo' ORDER BY nombre";
        }

        $huertos = $wpdb->get_results($sql);

        return array_map(function($huerto) {
            return [
                'id' => (int) $huerto->id,
                'nombre' => $huerto->nombre,
                'descripcion' => $huerto->descripcion,
                'direccion' => $huerto->direccion,
                'latitud' => (float) $huerto->latitud,
                'longitud' => (float) $huerto->longitud,
                'superficie_m2' => (int) $huerto->superficie_m2,
                'parcelas_totales' => (int) $huerto->num_parcelas,
                'parcelas_disponibles' => (int) $huerto->parcelas_disponibles,
                'foto' => $huerto->foto_url,
                'horario_acceso' => $huerto->horario_acceso,
                'distancia_km' => isset($huerto->distancia) ? round($huerto->distancia, 2) : null,
            ];
        }, $huertos);
    }

    /**
     * Obtiene detalle de un huerto
     */
    private function obtener_huerto_detalle($huerto_id) {
        global $wpdb;

        $huerto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_huertos} WHERE id = %d",
            $huerto_id
        ));

        if (!$huerto) {
            return null;
        }

        // Obtener parcelas
        $parcelas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_parcelas} WHERE huerto_id = %d ORDER BY numero_parcela",
            $huerto_id
        ));

        // Obtener coordinador
        $coordinador = null;
        if ($huerto->coordinador_id) {
            $usuario_coordinador = get_userdata($huerto->coordinador_id);
            if ($usuario_coordinador) {
                $coordinador = [
                    'id' => $usuario_coordinador->ID,
                    'nombre' => $usuario_coordinador->display_name,
                ];
            }
        }

        return [
            'id' => (int) $huerto->id,
            'nombre' => $huerto->nombre,
            'descripcion' => $huerto->descripcion,
            'direccion' => $huerto->direccion,
            'latitud' => (float) $huerto->latitud,
            'longitud' => (float) $huerto->longitud,
            'superficie_m2' => (int) $huerto->superficie_m2,
            'num_parcelas' => (int) $huerto->num_parcelas,
            'parcelas_disponibles' => (int) $huerto->parcelas_disponibles,
            'foto' => $huerto->foto_url,
            'horario_acceso' => $huerto->horario_acceso,
            'normas' => $huerto->normas,
            'equipamiento' => json_decode($huerto->equipamiento, true),
            'precio_anual' => (float) $huerto->precio_anual,
            'coordinador' => $coordinador,
            'parcelas' => array_map(function($parcela) {
                return [
                    'id' => (int) $parcela->id,
                    'numero' => $parcela->numero_parcela,
                    'superficie_m2' => (float) $parcela->superficie_m2,
                    'orientacion' => $parcela->orientacion,
                    'tiene_sombra' => (bool) $parcela->tiene_sombra,
                    'estado' => $parcela->estado,
                ];
            }, $parcelas),
        ];
    }

    /**
     * Obtiene la parcela de un usuario
     */
    private function obtener_parcela_usuario($usuario_id) {
        global $wpdb;

        $asignacion = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, p.*, h.nombre as huerto_nombre, h.direccion as huerto_direccion
            FROM {$this->tabla_asignaciones} a
            JOIN {$this->tabla_parcelas} p ON a.parcela_id = p.id
            JOIN {$this->tabla_huertos} h ON p.huerto_id = h.id
            WHERE a.usuario_id = %d AND a.estado = 'activa'
            LIMIT 1",
            $usuario_id
        ));

        if (!$asignacion) {
            return null;
        }

        // Contar cultivos activos
        $cultivos_activos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_cultivos}
            WHERE parcela_id = %d AND estado NOT IN ('finalizado', 'fallido')",
            $asignacion->parcela_id
        ));

        return [
            'id' => (int) $asignacion->parcela_id,
            'numero' => $asignacion->numero_parcela,
            'huerto_id' => (int) $asignacion->huerto_id,
            'huerto_nombre' => $asignacion->huerto_nombre,
            'huerto_direccion' => $asignacion->huerto_direccion,
            'superficie_m2' => (float) $asignacion->superficie_m2,
            'orientacion' => $asignacion->orientacion,
            'tiene_sombra' => (bool) $asignacion->tiene_sombra,
            'fecha_asignacion' => $asignacion->fecha_asignacion,
            'cultivos_activos' => (int) $cultivos_activos,
        ];
    }

    /**
     * Obtiene cultivos de una parcela
     */
    private function obtener_cultivos_parcela($parcela_id) {
        global $wpdb;

        $cultivos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_cultivos}
            WHERE parcela_id = %d
            ORDER BY fecha_siembra DESC",
            $parcela_id
        ));

        return array_map(function($cultivo) {
            return [
                'id' => (int) $cultivo->id,
                'nombre' => $cultivo->nombre_cultivo,
                'variedad' => $cultivo->variedad,
                'fecha_siembra' => $cultivo->fecha_siembra,
                'fecha_cosecha_estimada' => $cultivo->fecha_cosecha_estimada,
                'estado' => $cultivo->estado,
                'notas' => $cultivo->notas,
            ];
        }, $cultivos);
    }

    /**
     * Obtiene tareas próximas
     */
    private function obtener_tareas_proximas($huerto_id = 0, $limite = 10) {
        global $wpdb;

        $where_huerto = $huerto_id ? $wpdb->prepare(" AND t.huerto_id = %d", $huerto_id) : "";

        $tareas = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, h.nombre as huerto_nombre,
                (SELECT COUNT(*) FROM {$this->tabla_participantes_tareas} WHERE tarea_id = t.id) as participantes
            FROM {$this->tabla_tareas} t
            JOIN {$this->tabla_huertos} h ON t.huerto_id = h.id
            WHERE t.fecha >= CURDATE() AND t.estado = 'programada' {$where_huerto}
            ORDER BY t.fecha ASC, t.hora_inicio ASC
            LIMIT %d",
            $limite
        ));

        $usuario_id = get_current_user_id();

        return array_map(function($tarea) use ($wpdb, $usuario_id) {
            $esta_apuntado = false;
            if ($usuario_id) {
                $esta_apuntado = (bool) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->tabla_participantes_tareas}
                    WHERE tarea_id = %d AND usuario_id = %d",
                    $tarea->id, $usuario_id
                ));
            }

            $puede_apuntarse = !$esta_apuntado &&
                (!$tarea->max_participantes || $tarea->participantes < $tarea->max_participantes);

            return [
                'id' => (int) $tarea->id,
                'titulo' => $tarea->titulo,
                'descripcion' => $tarea->descripcion,
                'tipo' => $tarea->tipo,
                'fecha' => $tarea->fecha,
                'hora' => $tarea->hora_inicio ? substr($tarea->hora_inicio, 0, 5) : null,
                'huerto_nombre' => $tarea->huerto_nombre,
                'participantes' => (int) $tarea->participantes,
                'max_participantes' => $tarea->max_participantes ? (int) $tarea->max_participantes : null,
                'esta_apuntado' => $esta_apuntado,
                'puede_apuntarse' => $puede_apuntarse,
            ];
        }, $tareas);
    }

    /**
     * Obtiene intercambios disponibles
     */
    private function obtener_intercambios($tipo = '') {
        global $wpdb;

        $where_tipo = $tipo ? $wpdb->prepare(" AND i.tipo = %s", $tipo) : "";

        $intercambios = $wpdb->get_results(
            "SELECT i.*, u.display_name as usuario_nombre, h.nombre as huerto_nombre
            FROM {$this->tabla_intercambios} i
            LEFT JOIN {$wpdb->users} u ON i.usuario_id = u.ID
            LEFT JOIN {$this->tabla_huertos} h ON i.huerto_id = h.id
            WHERE i.estado = 'disponible' {$where_tipo}
            ORDER BY i.fecha_creacion DESC
            LIMIT 50"
        );

        return array_map(function($intercambio) {
            return [
                'id' => (int) $intercambio->id,
                'tipo' => $intercambio->tipo,
                'titulo' => $intercambio->titulo,
                'descripcion' => $intercambio->descripcion,
                'cantidad' => $intercambio->cantidad,
                'foto' => $intercambio->foto_url,
                'usuario_id' => (int) $intercambio->usuario_id,
                'usuario_nombre' => $intercambio->usuario_nombre,
                'huerto_nombre' => $intercambio->huerto_nombre,
                'fecha' => $intercambio->fecha_creacion,
            ];
        }, $intercambios);
    }

    /**
     * Procesa solicitud de parcela
     */
    private function procesar_solicitud_parcela($huerto_id, $motivacion, $experiencia) {
        global $wpdb;

        $usuario_id = get_current_user_id();

        // Verificar si ya tiene parcela activa
        $tiene_parcela = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_asignaciones}
            WHERE usuario_id = %d AND estado IN ('activa', 'pendiente', 'aprobada')",
            $usuario_id
        ));

        if ($tiene_parcela) {
            return ['success' => false, 'message' => __('Ya tienes una solicitud o parcela activa', 'flavor-chat-ia')];
        }

        // Obtener parcela disponible
        $parcela_disponible = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->tabla_parcelas}
            WHERE huerto_id = %d AND estado = 'disponible'
            ORDER BY RAND() LIMIT 1",
            $huerto_id
        ));

        if (!$parcela_disponible) {
            return ['success' => false, 'message' => __('No hay parcelas disponibles en este huerto', 'flavor-chat-ia')];
        }

        // Crear solicitud
        $insertado = $wpdb->insert($this->tabla_asignaciones, [
            'parcela_id' => $parcela_disponible->id,
            'usuario_id' => $usuario_id,
            'fecha_solicitud' => current_time('mysql'),
            'motivacion' => $motivacion,
            'experiencia_previa' => $experiencia,
            'estado' => 'pendiente',
        ]);

        if ($insertado) {
            // Marcar parcela como reservada
            $wpdb->update(
                $this->tabla_parcelas,
                ['estado' => 'reservada'],
                ['id' => $parcela_disponible->id]
            );

            // Actualizar contador de disponibles
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_huertos} SET parcelas_disponibles = parcelas_disponibles - 1 WHERE id = %d",
                $huerto_id
            ));

            return [
                'success' => true,
                'message' => __('Solicitud enviada correctamente. Te notificaremos cuando sea revisada.', 'flavor-chat-ia'),
            ];
        }

        return ['success' => false, 'message' => __('Error al procesar la solicitud', 'flavor-chat-ia')];
    }

    /**
     * Crea un nuevo cultivo
     */
    private function crear_cultivo($datos) {
        global $wpdb;

        $usuario_id = get_current_user_id();

        // Verificar que el usuario tiene acceso a la parcela
        $tiene_acceso = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_asignaciones}
            WHERE parcela_id = %d AND usuario_id = %d AND estado = 'activa'",
            $datos['parcela_id'], $usuario_id
        ));

        if (!$tiene_acceso) {
            return ['success' => false, 'message' => __('No tienes acceso a esta parcela', 'flavor-chat-ia')];
        }

        $insertado = $wpdb->insert($this->tabla_cultivos, [
            'parcela_id' => $datos['parcela_id'],
            'usuario_id' => $usuario_id,
            'nombre_cultivo' => $datos['nombre'],
            'variedad' => $datos['variedad'] ?: null,
            'fecha_siembra' => $datos['fecha_siembra'],
            'fecha_cosecha_estimada' => $datos['fecha_cosecha_estimada'] ?: null,
            'notas' => $datos['notas'] ?: null,
            'estado' => 'sembrado',
        ]);

        if ($insertado) {
            return [
                'success' => true,
                'message' => __('Cultivo registrado correctamente', 'flavor-chat-ia'),
                'cultivo_id' => $wpdb->insert_id,
            ];
        }

        return ['success' => false, 'message' => __('Error al registrar el cultivo', 'flavor-chat-ia')];
    }

    /**
     * Apunta usuario a una tarea
     */
    private function apuntar_usuario_tarea($tarea_id) {
        global $wpdb;

        $usuario_id = get_current_user_id();

        // Verificar que la tarea existe y tiene plazas
        $tarea = $wpdb->get_row($wpdb->prepare(
            "SELECT t.*,
                (SELECT COUNT(*) FROM {$this->tabla_participantes_tareas} WHERE tarea_id = t.id) as participantes
            FROM {$this->tabla_tareas} t
            WHERE t.id = %d AND t.estado = 'programada'",
            $tarea_id
        ));

        if (!$tarea) {
            return ['success' => false, 'message' => __('Tarea no encontrada', 'flavor-chat-ia')];
        }

        if ($tarea->max_participantes && $tarea->participantes >= $tarea->max_participantes) {
            return ['success' => false, 'message' => __('No hay plazas disponibles', 'flavor-chat-ia')];
        }

        // Verificar si ya está apuntado
        $ya_apuntado = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_participantes_tareas}
            WHERE tarea_id = %d AND usuario_id = %d",
            $tarea_id, $usuario_id
        ));

        if ($ya_apuntado) {
            return ['success' => false, 'message' => __('Ya estás apuntado a esta tarea', 'flavor-chat-ia')];
        }

        $insertado = $wpdb->insert($this->tabla_participantes_tareas, [
            'tarea_id' => $tarea_id,
            'usuario_id' => $usuario_id,
        ]);

        if ($insertado) {
            return ['success' => true, 'message' => __('Te has apuntado correctamente', 'flavor-chat-ia')];
        }

        return ['success' => false, 'message' => __('Error al apuntarse', 'flavor-chat-ia')];
    }

    /**
     * Crea un nuevo intercambio
     */
    private function crear_intercambio($datos) {
        global $wpdb;

        $usuario_id = get_current_user_id();

        // Obtener huerto del usuario si tiene parcela
        $huerto_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.huerto_id FROM {$this->tabla_asignaciones} a
            JOIN {$this->tabla_parcelas} p ON a.parcela_id = p.id
            WHERE a.usuario_id = %d AND a.estado = 'activa'
            LIMIT 1",
            $usuario_id
        ));

        $insertado = $wpdb->insert($this->tabla_intercambios, [
            'usuario_id' => $usuario_id,
            'huerto_id' => $huerto_id ?: null,
            'tipo' => $datos['tipo'],
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'cantidad' => $datos['cantidad'] ?: null,
            'busca_a_cambio' => $datos['busca_a_cambio'] ?: null,
            'estado' => 'disponible',
        ]);

        if ($insertado) {
            return [
                'success' => true,
                'message' => __('Intercambio publicado correctamente', 'flavor-chat-ia'),
                'intercambio_id' => $wpdb->insert_id,
            ];
        }

        return ['success' => false, 'message' => __('Error al publicar el intercambio', 'flavor-chat-ia')];
    }

    /**
     * Marca una tarea como completada
     */
    private function marcar_tarea_completada($tarea_id, $notas) {
        global $wpdb;

        $actualizado = $wpdb->update(
            $this->tabla_tareas,
            [
                'estado' => 'completada',
                'notas_completado' => $notas,
            ],
            ['id' => $tarea_id]
        );

        if ($actualizado !== false) {
            return ['success' => true, 'message' => __('Tarea marcada como completada', 'flavor-chat-ia')];
        }

        return ['success' => false, 'message' => __('Error al actualizar la tarea', 'flavor-chat-ia')];
    }

    /**
     * Crea una actividad
     */
    private function crear_actividad($datos) {
        global $wpdb;

        $usuario_id = get_current_user_id();

        // Obtener huerto_id desde parcela si existe
        $huerto_id = null;
        if ($datos['parcela_id']) {
            $huerto_id = $wpdb->get_var($wpdb->prepare(
                "SELECT huerto_id FROM {$this->tabla_parcelas} WHERE id = %d",
                $datos['parcela_id']
            ));
        }

        $insertado = $wpdb->insert($this->tabla_actividades, [
            'huerto_id' => $huerto_id,
            'parcela_id' => $datos['parcela_id'] ?: null,
            'cultivo_id' => $datos['cultivo_id'] ?: null,
            'usuario_id' => $usuario_id,
            'tipo' => $datos['tipo'],
            'descripcion' => $datos['descripcion'],
            'duracion_minutos' => $datos['duracion_minutos'] ?: null,
        ]);

        if ($insertado) {
            return ['success' => true, 'message' => __('Actividad registrada', 'flavor-chat-ia')];
        }

        return ['success' => false, 'message' => __('Error al registrar la actividad', 'flavor-chat-ia')];
    }

    /**
     * Obtiene turnos de riego
     */
    private function obtener_turnos_riego($huerto_id, $mes, $anio) {
        global $wpdb;

        $fecha_inicio = sprintf('%04d-%02d-01', $anio, $mes);
        $fecha_fin = date('Y-m-t', strtotime($fecha_inicio));

        $where_huerto = $huerto_id ? $wpdb->prepare(" AND t.huerto_id = %d", $huerto_id) : "";

        $turnos = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, u.display_name as usuario_nombre, h.nombre as huerto_nombre
            FROM {$this->tabla_turnos_riego} t
            JOIN {$wpdb->users} u ON t.usuario_id = u.ID
            JOIN {$this->tabla_huertos} h ON t.huerto_id = h.id
            WHERE t.fecha_turno BETWEEN %s AND %s {$where_huerto}
            ORDER BY t.fecha_turno, t.hora_inicio",
            $fecha_inicio, $fecha_fin
        ));

        return array_map(function($turno) {
            return [
                'id' => (int) $turno->id,
                'fecha' => $turno->fecha_turno,
                'hora_inicio' => substr($turno->hora_inicio, 0, 5),
                'hora_fin' => substr($turno->hora_fin, 0, 5),
                'usuario_nombre' => $turno->usuario_nombre,
                'huerto_nombre' => $turno->huerto_nombre,
                'completado' => (bool) $turno->completado,
            ];
        }, $turnos);
    }

    /**
     * Marca turno de riego como completado
     */
    private function marcar_turno_riego_completado($turno_id, $notas) {
        global $wpdb;

        $usuario_id = get_current_user_id();

        // Verificar que el turno pertenece al usuario
        $turno = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_turnos_riego} WHERE id = %d AND usuario_id = %d",
            $turno_id, $usuario_id
        ));

        if (!$turno) {
            return ['success' => false, 'message' => __('Turno no encontrado o no te pertenece', 'flavor-chat-ia')];
        }

        $actualizado = $wpdb->update(
            $this->tabla_turnos_riego,
            [
                'completado' => 1,
                'fecha_completado' => current_time('mysql'),
                'notas' => $notas,
            ],
            ['id' => $turno_id]
        );

        if ($actualizado !== false) {
            return ['success' => true, 'message' => __('Turno de riego marcado como completado', 'flavor-chat-ia')];
        }

        return ['success' => false, 'message' => __('Error al actualizar el turno', 'flavor-chat-ia')];
    }

    /**
     * Obtiene estadísticas
     */
    private function obtener_estadisticas($huerto_id = 0) {
        global $wpdb;

        $where_huerto = $huerto_id ? $wpdb->prepare(" WHERE huerto_id = %d", $huerto_id) : "";
        $where_huerto_and = $huerto_id ? $wpdb->prepare(" AND p.huerto_id = %d", $huerto_id) : "";

        $estadisticas = [
            'total_huertos' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_huertos} WHERE estado = 'activo'"),
            'total_parcelas' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_parcelas}" . $where_huerto),
            'parcelas_ocupadas' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_parcelas} WHERE estado = 'ocupada'" . ($huerto_id ? " AND huerto_id = $huerto_id" : "")),
            'total_hortelanos' => (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$this->tabla_asignaciones} WHERE estado = 'activa'"),
            'cultivos_activos' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_cultivos} c JOIN {$this->tabla_parcelas} p ON c.parcela_id = p.id WHERE c.estado NOT IN ('finalizado', 'fallido')" . $where_huerto_and),
            'intercambios_activos' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_intercambios} WHERE estado = 'disponible'"),
            'tareas_mes' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_tareas} WHERE MONTH(fecha) = %d AND YEAR(fecha) = %d AND estado IN ('programada', 'completada')",
                date('n'), date('Y')
            )),
        ];

        // Cultivos más populares
        $cultivos_populares = $wpdb->get_results(
            "SELECT nombre_cultivo, COUNT(*) as cantidad
            FROM {$this->tabla_cultivos}
            GROUP BY nombre_cultivo
            ORDER BY cantidad DESC
            LIMIT 5"
        );

        $estadisticas['cultivos_populares'] = array_map(function($cultivo) {
            return [
                'nombre' => $cultivo->nombre_cultivo,
                'cantidad' => (int) $cultivo->cantidad,
            ];
        }, $cultivos_populares);

        return $estadisticas;
    }

    // =========================================================================
    // REST API
    // =========================================================================

    /**
     * Registra las rutas REST
     */
    public function registrar_rest_routes() {
        $namespace = 'flavor-huertos/v1';

        register_rest_route($namespace, '/huertos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_huertos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/huertos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_huerto'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/parcelas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_parcelas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/mi-parcela', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_mi_parcela'],
            'permission_callback' => [$this, 'rest_check_logged_in'],
        ]);

        register_rest_route($namespace, '/cultivos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_cultivos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/cultivos', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_create_cultivo'],
            'permission_callback' => [$this, 'rest_check_logged_in'],
        ]);

        register_rest_route($namespace, '/tareas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_tareas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/intercambios', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_intercambios'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/intercambios', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_create_intercambio'],
            'permission_callback' => [$this, 'rest_check_logged_in'],
        ]);

        register_rest_route($namespace, '/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_estadisticas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/calendario-cultivos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_calendario_cultivos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Verifica si el usuario está logueado para REST
     */
    public function rest_check_logged_in() {
        return is_user_logged_in();
    }

    /**
     * REST: Obtener huertos
     */
    public function rest_get_huertos($request) {
        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');

        $huertos = $this->obtener_huertos($lat, $lng);

        return rest_ensure_response($this->sanitize_public_huertos_response($huertos));
    }

    /**
     * REST: Obtener huerto específico
     */
    public function rest_get_huerto($request) {
        $huerto_id = $request->get_param('id');
        $huerto = $this->obtener_huerto_detalle($huerto_id);

        if (!$huerto) {
            return new WP_Error('not_found', __('Huerto no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        return rest_ensure_response($this->sanitize_public_huertos_response($huerto));
    }

    /**
     * REST: Obtener parcelas
     */
    public function rest_get_parcelas($request) {
        global $wpdb;

        $huerto_id = $request->get_param('huerto_id');
        $estado = $request->get_param('estado');

        $where = "1=1";
        if ($huerto_id) {
            $where .= $wpdb->prepare(" AND huerto_id = %d", $huerto_id);
        }
        if ($estado) {
            $where .= $wpdb->prepare(" AND estado = %s", $estado);
        }

        $parcelas = $wpdb->get_results("SELECT * FROM {$this->tabla_parcelas} WHERE {$where} ORDER BY numero_parcela");

        return rest_ensure_response($parcelas);
    }

    /**
     * REST: Obtener mi parcela
     */
    public function rest_get_mi_parcela($request) {
        $parcela = $this->obtener_parcela_usuario(get_current_user_id());

        return rest_ensure_response($parcela ?: ['message' => __('No tienes parcela asignada', 'flavor-chat-ia')]);
    }

    /**
     * REST: Obtener cultivos
     */
    public function rest_get_cultivos($request) {
        $parcela_id = $request->get_param('parcela_id');

        if (!$parcela_id) {
            return new WP_Error('missing_param', __('Se requiere parcela_id', 'flavor-chat-ia'), ['status' => 400]);
        }

        $cultivos = $this->obtener_cultivos_parcela($parcela_id);

        return rest_ensure_response($cultivos);
    }

    /**
     * REST: Crear cultivo
     */
    public function rest_create_cultivo($request) {
        $datos = [
            'parcela_id' => $request->get_param('parcela_id'),
            'nombre' => $request->get_param('nombre'),
            'variedad' => $request->get_param('variedad'),
            'fecha_siembra' => $request->get_param('fecha_siembra'),
            'fecha_cosecha_estimada' => $request->get_param('fecha_cosecha_estimada'),
            'notas' => $request->get_param('notas'),
        ];

        $resultado = $this->crear_cultivo($datos);

        if ($resultado['success']) {
            return rest_ensure_response($resultado);
        }

        return new WP_Error('create_failed', $resultado['message'], ['status' => 400]);
    }

    /**
     * REST: Obtener tareas
     */
    public function rest_get_tareas($request) {
        $huerto_id = $request->get_param('huerto_id');
        $limite = $request->get_param('limite') ?: 10;

        $tareas = $this->obtener_tareas_proximas($huerto_id, $limite);

        return rest_ensure_response($tareas);
    }

    /**
     * REST: Obtener intercambios
     */
    public function rest_get_intercambios($request) {
        $tipo = $request->get_param('tipo');

        $intercambios = $this->obtener_intercambios($tipo);

        return rest_ensure_response($this->sanitize_public_huertos_response($intercambios));
    }

    private function sanitize_public_huertos_response($data) {
        if (is_user_logged_in()) {
            return $data;
        }

        if (is_array($data)) {
            return array_map([$this, 'sanitize_public_huerto_item'], $data);
        }

        return $this->sanitize_public_huerto_item($data);
    }

    private function sanitize_public_huerto_item($item) {
        if (!is_array($item)) {
            return $item;
        }

        if (!empty($item['coordinador']) && is_array($item['coordinador'])) {
            unset($item['coordinador']['id']);
        }

        if (array_key_exists('usuario_id', $item)) {
            unset($item['usuario_id']);
        }

        return $item;
    }

    /**
     * REST: Crear intercambio
     */
    public function rest_create_intercambio($request) {
        $datos = [
            'tipo' => $request->get_param('tipo'),
            'titulo' => $request->get_param('titulo'),
            'descripcion' => $request->get_param('descripcion'),
            'cantidad' => $request->get_param('cantidad'),
            'busca_a_cambio' => $request->get_param('busca_a_cambio'),
        ];

        $resultado = $this->crear_intercambio($datos);

        if ($resultado['success']) {
            return rest_ensure_response($resultado);
        }

        return new WP_Error('create_failed', $resultado['message'], ['status' => 400]);
    }

    /**
     * REST: Obtener estadísticas
     */
    public function rest_get_estadisticas($request) {
        $huerto_id = $request->get_param('huerto_id');

        $estadisticas = $this->obtener_estadisticas($huerto_id);

        return rest_ensure_response($estadisticas);
    }

    /**
     * REST: Obtener calendario de cultivos
     */
    public function rest_get_calendario_cultivos($request) {
        return rest_ensure_response($this->obtener_calendario_cultivos_data());
    }

    /**
     * Datos del calendario de cultivos
     */
    private function obtener_calendario_cultivos_data() {
        return [
            ['nombre' => 'Tomate', 'icono' => 'tomato', 'siembra' => [2, 3, 4], 'cosecha' => [6, 7, 8, 9]],
            ['nombre' => 'Lechuga', 'icono' => 'lettuce', 'siembra' => [1, 2, 3, 8, 9], 'cosecha' => [3, 4, 5, 10, 11]],
            ['nombre' => 'Zanahoria', 'icono' => 'carrot', 'siembra' => [2, 3, 4, 7, 8], 'cosecha' => [5, 6, 7, 10, 11]],
            ['nombre' => 'Pimiento', 'icono' => 'pepper', 'siembra' => [2, 3], 'cosecha' => [7, 8, 9]],
            ['nombre' => 'Calabacín', 'icono' => 'zucchini', 'siembra' => [3, 4, 5], 'cosecha' => [6, 7, 8, 9]],
            ['nombre' => 'Berenjena', 'icono' => 'eggplant', 'siembra' => [2, 3], 'cosecha' => [7, 8, 9]],
            ['nombre' => 'Cebolla', 'icono' => 'onion', 'siembra' => [0, 1, 8, 9], 'cosecha' => [5, 6, 7]],
            ['nombre' => 'Ajo', 'icono' => 'garlic', 'siembra' => [9, 10, 11], 'cosecha' => [5, 6]],
            ['nombre' => 'Fresa', 'icono' => 'strawberry', 'siembra' => [8, 9], 'cosecha' => [4, 5, 6]],
            ['nombre' => 'Espinaca', 'icono' => 'spinach', 'siembra' => [1, 2, 8, 9, 10], 'cosecha' => [3, 4, 5, 10, 11]],
            ['nombre' => 'Judía verde', 'icono' => 'bean', 'siembra' => [3, 4, 5], 'cosecha' => [6, 7, 8]],
            ['nombre' => 'Pepino', 'icono' => 'cucumber', 'siembra' => [3, 4, 5], 'cosecha' => [6, 7, 8, 9]],
        ];
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Mapa de huertos
     */
    public function shortcode_mapa_huertos($atts) {
        $atts = shortcode_atts([
            'altura' => 500,
            'zoom' => 12,
            'mostrar_filtros' => 'true',
        ], $atts);

        ob_start();
        ?>
        <div class="huertos-contenedor">
            <div class="huertos-seccion">
                <h2 class="huertos-titulo-seccion">
                    <span class="icono">🗺️</span>
                    <?php _e('Mapa de Huertos Urbanos', 'flavor-chat-ia'); ?>
                </h2>

                <div class="huertos-mapa-contenedor">
                    <?php if ($atts['mostrar_filtros'] === 'true'): ?>
                    <div class="huertos-mapa-controles">
                        <div class="huertos-mapa-filtro">
                            <label><?php _e('Filtrar:', 'flavor-chat-ia'); ?></label>
                            <select>
                                <option value="<?php echo esc_attr__('todos', 'flavor-chat-ia'); ?>"><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('disponibles', 'flavor-chat-ia'); ?>"><?php _e('Con parcelas disponibles', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('completos', 'flavor-chat-ia'); ?>"><?php _e('Completos', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="huertos-mapa" style="height: <?php echo intval($atts['altura']); ?>px;"></div>
                </div>
            </div>

            <div class="huertos-seccion">
                <h3 class="huertos-titulo-seccion">
                    <span class="icono">🌱</span>
                    <?php _e('Huertos Disponibles', 'flavor-chat-ia'); ?>
                </h3>
                <div class="huertos-grid"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi parcela
     */
    public function shortcode_mi_parcela($atts) {
        if (!is_user_logged_in()) {
            return '<div class="huertos-alerta huertos-alerta-advertencia">' .
                   __('Debes iniciar sesión para ver tu parcela.', 'flavor-chat-ia') .
                   '</div>';
        }

        ob_start();
        ?>
        <div class="huertos-contenedor">
            <div class="huertos-seccion">
                <h2 class="huertos-titulo-seccion">
                    <span class="icono">🏡</span>
                    <?php _e('Mi Parcela', 'flavor-chat-ia'); ?>
                </h2>
                <div class="mi-parcela-contenedor">
                    <div class="huertos-cargando">
                        <div class="huertos-cargando-spinner"></div>
                        <?php _e('Cargando información de tu parcela...', 'flavor-chat-ia'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario de cultivos
     */
    public function shortcode_calendario_cultivos($atts) {
        $atts = shortcode_atts([
            'vista' => 'anual',
        ], $atts);

        ob_start();
        ?>
        <div class="huertos-contenedor">
            <div class="huertos-seccion">
                <div class="calendario-cultivos-contenedor">
                    <div class="huertos-cargando">
                        <div class="huertos-cargando-spinner"></div>
                        <?php _e('Cargando calendario...', 'flavor-chat-ia'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Intercambios
     */
    public function shortcode_intercambios($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
            'limite' => 12,
        ], $atts);

        ob_start();
        ?>
        <div class="huertos-contenedor">
            <div class="huertos-seccion">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="huertos-titulo-seccion" style="margin-bottom: 0; border-bottom: none; padding-bottom: 0;">
                        <span class="icono">🔄</span>
                        <?php _e('Intercambios de la Comunidad', 'flavor-chat-ia'); ?>
                    </h2>
                    <?php if (is_user_logged_in()): ?>
                    <button class="huertos-boton huertos-boton-primario"
                            data-huertos-accion="publicar-intercambio">
                        <?php _e('+ Publicar intercambio', 'flavor-chat-ia'); ?>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="intercambios-contenedor">
                    <div class="huertos-cargando" style="grid-column: 1 / -1;">
                        <div class="huertos-cargando-spinner"></div>
                        <?php _e('Cargando intercambios...', 'flavor-chat-ia'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Tareas del huerto
     */
    public function shortcode_tareas_huerto($atts) {
        $atts = shortcode_atts([
            'huerto_id' => 0,
            'limite' => 10,
        ], $atts);

        ob_start();
        ?>
        <div class="huertos-contenedor">
            <div class="huertos-seccion">
                <h2 class="huertos-titulo-seccion">
                    <span class="icono">📋</span>
                    <?php _e('Próximas Tareas Comunitarias', 'flavor-chat-ia'); ?>
                </h2>

                <div class="tareas-huerto-contenedor">
                    <div class="tareas-lista">
                        <div class="huertos-cargando">
                            <div class="huertos-cargando-spinner"></div>
                            <?php _e('Cargando tareas...', 'flavor-chat-ia'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Lista de huertos
     */
    public function shortcode_lista_huertos($atts) {
        $atts = shortcode_atts([
            'columnas' => 3,
            'mostrar_estadisticas' => 'true',
        ], $atts);

        $huertos = $this->obtener_huertos();

        ob_start();
        ?>
        <div class="huertos-contenedor">
            <?php if ($atts['mostrar_estadisticas'] === 'true'): ?>
            <div class="huertos-estadisticas-grid">
                <?php
                $estadisticas = $this->obtener_estadisticas();
                $items_estadisticas = [
                    ['icono' => '🌿', 'valor' => $estadisticas['total_huertos'], 'etiqueta' => __('Huertos Activos', 'flavor-chat-ia')],
                    ['icono' => '🏡', 'valor' => $estadisticas['total_parcelas'], 'etiqueta' => __('Parcelas Totales', 'flavor-chat-ia')],
                    ['icono' => '👩‍🌾', 'valor' => $estadisticas['total_hortelanos'], 'etiqueta' => __('Hortelanos', 'flavor-chat-ia')],
                    ['icono' => '🌱', 'valor' => $estadisticas['cultivos_activos'], 'etiqueta' => __('Cultivos Activos', 'flavor-chat-ia')],
                ];
                foreach ($items_estadisticas as $item):
                ?>
                <div class="huertos-estadistica-tarjeta">
                    <div class="huertos-estadistica-icono"><?php echo $item['icono']; ?></div>
                    <div class="huertos-estadistica-valor"><?php echo $item['valor']; ?></div>
                    <div class="huertos-estadistica-etiqueta"><?php echo $item['etiqueta']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="huertos-seccion">
                <h2 class="huertos-titulo-seccion">
                    <span class="icono">🌻</span>
                    <?php _e('Nuestros Huertos', 'flavor-chat-ia'); ?>
                </h2>

                <div class="huertos-grid" style="grid-template-columns: repeat(<?php echo intval($atts['columnas']); ?>, 1fr);">
                    <?php foreach ($huertos as $huerto): ?>
                    <div class="huertos-tarjeta">
                        <?php if ($huerto['foto']): ?>
                        <img src="<?php echo esc_url($huerto['foto']); ?>" alt="<?php echo esc_attr($huerto['nombre']); ?>" class="huertos-tarjeta-imagen">
                        <?php else: ?>
                        <div class="huertos-tarjeta-imagen"></div>
                        <?php endif; ?>

                        <div class="huertos-tarjeta-contenido">
                            <h3 class="huertos-tarjeta-nombre"><?php echo esc_html($huerto['nombre']); ?></h3>
                            <p class="huertos-tarjeta-direccion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($huerto['direccion']); ?>
                            </p>

                            <div class="huertos-tarjeta-estadisticas">
                                <div class="huertos-tarjeta-estadistica">
                                    <span class="huertos-tarjeta-estadistica-valor"><?php echo $huerto['superficie_m2']; ?></span>
                                    <span class="huertos-tarjeta-estadistica-etiqueta"><?php echo esc_html__('m2', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div class="huertos-tarjeta-estadistica">
                                    <span class="huertos-tarjeta-estadistica-valor"><?php echo $huerto['parcelas_totales']; ?></span>
                                    <span class="huertos-tarjeta-estadistica-etiqueta"><?php _e('parcelas', 'flavor-chat-ia'); ?></span>
                                </div>
                            </div>

                            <span class="huertos-badge huertos-badge-<?php echo $huerto['parcelas_disponibles'] > 0 ? 'disponible' : 'ocupada'; ?>">
                                <?php echo $huerto['parcelas_disponibles'] > 0
                                    ? sprintf(__('%d disponibles', 'flavor-chat-ia'), $huerto['parcelas_disponibles'])
                                    : __('Completo', 'flavor-chat-ia'); ?>
                            </span>

                            <div class="huertos-tarjeta-acciones">
                                <button class="huertos-boton huertos-boton-primario huertos-boton-pequeno"
                                        data-huertos-accion="ver-detalle-huerto"
                                        data-huertos-params='{"id": <?php echo $huerto['id']; ?>}'>
                                    <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
                                </button>
                                <?php if ($huerto['parcelas_disponibles'] > 0): ?>
                                <button class="huertos-boton huertos-boton-secundario huertos-boton-pequeno"
                                        data-huertos-accion="solicitar-parcela"
                                        data-huertos-params='{"huerto_id": <?php echo $huerto['id']; ?>}'>
                                    <?php _e('Solicitar', 'flavor-chat-ia'); ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Verifica el nonce AJAX
     */
    private function verificar_nonce() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flavor_huertos_nonce')) {
            wp_send_json_error(['message' => __('Token de seguridad inválido', 'flavor-chat-ia')]);
        }
    }

    /**
     * Verifica que el usuario esté logueado
     */
    private function verificar_usuario_logueado() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }
    }

    /**
     * Envía notificaciones programadas
     */
    public function enviar_notificaciones_programadas() {
        // Notificar turnos de riego del día siguiente
        $this->notificar_turnos_riego_proximos();

        // Notificar tareas próximas
        $this->notificar_tareas_proximas();
    }

    /**
     * Notifica turnos de riego próximos
     */
    private function notificar_turnos_riego_proximos() {
        global $wpdb;

        $manana = date('Y-m-d', strtotime('+1 day'));

        $turnos = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, u.user_email, u.display_name, h.nombre as huerto_nombre
            FROM {$this->tabla_turnos_riego} t
            JOIN {$wpdb->users} u ON t.usuario_id = u.ID
            JOIN {$this->tabla_huertos} h ON t.huerto_id = h.id
            WHERE t.fecha_turno = %s AND t.completado = 0",
            $manana
        ));

        foreach ($turnos as $turno) {
            $asunto = sprintf(__('Recordatorio: Turno de riego mañana en %s', 'flavor-chat-ia'), $turno->huerto_nombre);
            $mensaje = sprintf(
                __("Hola %s,\n\nTe recordamos que mañana tienes turno de riego en el huerto %s.\n\nFecha: %s\nHora: %s - %s\n\n¡Gracias por tu colaboración!", 'flavor-chat-ia'),
                $turno->display_name,
                $turno->huerto_nombre,
                date_i18n('l j F', strtotime($turno->fecha_turno)),
                substr($turno->hora_inicio, 0, 5),
                substr($turno->hora_fin, 0, 5)
            );

            wp_mail($turno->user_email, $asunto, $mensaje);
        }
    }

    /**
     * Notifica tareas próximas
     */
    private function notificar_tareas_proximas() {
        global $wpdb;

        $manana = date('Y-m-d', strtotime('+1 day'));

        $participantes = $wpdb->get_results($wpdb->prepare(
            "SELECT pt.*, t.titulo, t.fecha, t.hora_inicio, h.nombre as huerto_nombre,
                    u.user_email, u.display_name
            FROM {$this->tabla_participantes_tareas} pt
            JOIN {$this->tabla_tareas} t ON pt.tarea_id = t.id
            JOIN {$this->tabla_huertos} h ON t.huerto_id = h.id
            JOIN {$wpdb->users} u ON pt.usuario_id = u.ID
            WHERE t.fecha = %s AND t.estado = 'programada'",
            $manana
        ));

        foreach ($participantes as $participante) {
            $asunto = sprintf(__('Recordatorio: Tarea mañana - %s', 'flavor-chat-ia'), $participante->titulo);
            $mensaje = sprintf(
                __("Hola %s,\n\nTe recordamos que mañana participas en la tarea:\n\n%s\nHuerto: %s\nFecha: %s\nHora: %s\n\n¡Te esperamos!", 'flavor-chat-ia'),
                $participante->display_name,
                $participante->titulo,
                $participante->huerto_nombre,
                date_i18n('l j F', strtotime($participante->fecha)),
                substr($participante->hora_inicio, 0, 5)
            );

            wp_mail($participante->user_email, $asunto, $mensaje);
        }
    }

    // =========================================================================
    // MÉTODOS HEREDADOS DEL MÓDULO BASE
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_huertos' => [
                'description' => 'Listar huertos comunitarios disponibles',
                'params' => ['lat', 'lng'],
            ],
            'detalle_huerto' => [
                'description' => 'Ver detalles completos de un huerto',
                'params' => ['huerto_id'],
            ],
            'solicitar_parcela' => [
                'description' => 'Solicitar una parcela en un huerto',
                'params' => ['huerto_id', 'motivacion', 'experiencia'],
            ],
            'mi_parcela' => [
                'description' => 'Ver información de mi parcela asignada',
                'params' => [],
            ],
            'registrar_cultivo' => [
                'description' => 'Registrar un nuevo cultivo en mi parcela',
                'params' => ['parcela_id', 'nombre', 'variedad', 'fecha_siembra'],
            ],
            'registrar_actividad' => [
                'description' => 'Registrar una actividad realizada',
                'params' => ['parcela_id', 'tipo', 'descripcion', 'duracion_minutos'],
            ],
            'calendario_riego' => [
                'description' => 'Ver calendario de turnos de riego',
                'params' => ['huerto_id', 'mes', 'anio'],
            ],
            'marcar_riego_completado' => [
                'description' => 'Marcar turno de riego como completado',
                'params' => ['turno_id'],
            ],
            'listar_intercambios' => [
                'description' => 'Ver intercambios disponibles',
                'params' => ['tipo'],
            ],
            'publicar_intercambio' => [
                'description' => 'Publicar un intercambio de semillas/cosecha',
                'params' => ['tipo', 'titulo', 'descripcion', 'cantidad'],
            ],
            'tareas_proximas' => [
                'description' => 'Ver tareas comunitarias próximas',
                'params' => ['huerto_id'],
            ],
            'apuntarse_tarea' => [
                'description' => 'Apuntarse a una tarea comunitaria',
                'params' => ['tarea_id'],
            ],
            'guia_cultivos' => [
                'description' => 'Guía de cultivos por temporada',
                'params' => ['mes'],
            ],
            'estadisticas_huerto' => [
                'description' => 'Ver estadísticas del huerto',
                'params' => ['huerto_id'],
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
            'error' => sprintf(__('Acción no implementada: %s', 'flavor-chat-ia'), $action_name),
        ];
    }

    /**
     * Acción: Listar huertos
     */
    private function action_listar_huertos($params) {
        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);

        return [
            'success' => true,
            'huertos' => $this->obtener_huertos($lat, $lng),
        ];
    }

    /**
     * Acción: Detalle huerto
     */
    private function action_detalle_huerto($params) {
        $huerto_id = intval($params['huerto_id'] ?? 0);
        $huerto = $this->obtener_huerto_detalle($huerto_id);

        if ($huerto) {
            return ['success' => true, 'huerto' => $huerto];
        }

        return ['success' => false, 'error' => __('Huerto no encontrado', 'flavor-chat-ia')];
    }

    /**
     * Acción: Mi parcela
     */
    private function action_mi_parcela($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Usuario no autenticado', 'flavor-chat-ia')];
        }

        $parcela = $this->obtener_parcela_usuario($usuario_id);

        return [
            'success' => true,
            'parcela' => $parcela,
            'tiene_parcela' => !is_null($parcela),
        ];
    }

    /**
     * Acción: Guía de cultivos
     */
    private function action_guia_cultivos($params) {
        $mes = intval($params['mes'] ?? date('n'));

        $calendario = $this->obtener_calendario_cultivos_data();

        // Filtrar cultivos recomendados para el mes
        $recomendados_siembra = [];
        $recomendados_cosecha = [];

        foreach ($calendario as $cultivo) {
            if (in_array($mes - 1, $cultivo['siembra'])) {
                $recomendados_siembra[] = $cultivo['nombre'];
            }
            if (in_array($mes - 1, $cultivo['cosecha'])) {
                $recomendados_cosecha[] = $cultivo['nombre'];
            }
        }

        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        return [
            'success' => true,
            'mes' => $meses[$mes - 1],
            'siembra' => $recomendados_siembra,
            'cosecha' => $recomendados_cosecha,
            'calendario_completo' => $calendario,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_web_components() {
        return [
            'hero_huertos' => [
                'label' => __('Hero Huertos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-carrot',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Huertos Urbanos', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Cultiva tus propios alimentos en comunidad', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_estadisticas' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'huertos-urbanos/hero',
            ],
            'mapa_huertos' => [
                'label' => __('Mapa de Huertos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Encuentra tu Huerto', 'flavor-chat-ia')],
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'zoom_inicial' => ['type' => 'number', 'default' => 12],
                    'mostrar_disponibilidad' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'huertos-urbanos/mapa',
            ],
            'parcelas_disponibles' => [
                'label' => __('Parcelas Disponibles', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Parcelas Disponibles', 'flavor-chat-ia')],
                    'huerto_id' => ['type' => 'number', 'default' => 0],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                ],
                'template' => 'huertos-urbanos/parcelas',
            ],
            'calendario_cultivos' => [
                'label' => __('Calendario de Cultivos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Qué Plantar Este Mes', 'flavor-chat-ia')],
                    'vista_tipo' => ['type' => 'select', 'options' => ['mensual', 'anual'], 'default' => 'anual'],
                ],
                'template' => 'huertos-urbanos/calendario',
            ],
            'intercambios_widget' => [
                'label' => __('Intercambios', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-randomize',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Intercambios Recientes', 'flavor-chat-ia')],
                    'tipo' => ['type' => 'select', 'options' => ['', 'semillas', 'cosecha', 'plantulas'], 'default' => ''],
                    'limite' => ['type' => 'number', 'default' => 6],
                ],
                'template' => 'huertos-urbanos/intercambios',
            ],
            'tareas_comunidad' => [
                'label' => __('Tareas Comunidad', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-clipboard',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Próximas Tareas', 'flavor-chat-ia')],
                    'limite' => ['type' => 'number', 'default' => 5],
                ],
                'template' => 'huertos-urbanos/tareas',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'huertos_listar',
                'description' => 'Ver huertos urbanos comunitarios disponibles cerca de una ubicación',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number', 'description' => 'Latitud del usuario'],
                        'lng' => ['type' => 'number', 'description' => 'Longitud del usuario'],
                    ],
                ],
            ],
            [
                'name' => 'huertos_detalle',
                'description' => 'Ver información detallada de un huerto específico',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'huerto_id' => ['type' => 'integer', 'description' => 'ID del huerto'],
                    ],
                    'required' => ['huerto_id'],
                ],
            ],
            [
                'name' => 'huertos_mi_parcela',
                'description' => 'Ver información de la parcela asignada al usuario',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'huertos_guia_cultivos',
                'description' => 'Obtener recomendaciones de cultivos para un mes específico',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'mes' => ['type' => 'integer', 'description' => 'Número del mes (1-12)'],
                    ],
                ],
            ],
            [
                'name' => 'huertos_intercambios',
                'description' => 'Ver intercambios de semillas y cosechas disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => ['type' => 'string', 'enum' => ['semillas', 'cosecha', 'plantulas']],
                    ],
                ],
            ],
            [
                'name' => 'huertos_tareas',
                'description' => 'Ver tareas comunitarias próximas en los huertos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'huerto_id' => ['type' => 'integer', 'description' => 'ID del huerto (opcional)'],
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
**Huertos Urbanos Comunitarios**

Sistema completo de gestión de huertos urbanos para cultivar en comunidad.

**Qué ofrecemos:**
- Parcelas individuales en huertos comunitarios
- Calendario de cultivos por temporada
- Sistema de turnos de riego compartido
- Intercambio de semillas y cosechas
- Tareas comunitarias y talleres
- Seguimiento de cultivos

**Cómo participar:**
1. Explora los huertos disponibles en el mapa
2. Solicita una parcela con tu motivación
3. Espera la aprobación del coordinador
4. Recibe tu parcela y empieza a cultivar
5. Participa en turnos de riego y tareas

**Compromisos del hortelano:**
- Cuidar tu parcela regularmente
- Cumplir turnos de riego asignados
- Asistir a jornadas comunitarias (mínimo 4h/mes)
- Usar métodos de cultivo ecológicos
- Respetar las normas del huerto

**Calendario de cultivos:**
- Primavera (Mar-May): Tomate, pimiento, calabacín
- Verano (Jun-Ago): Cosecha de solanáceas, siembra otoño
- Otoño (Sep-Nov): Lechuga, espinaca, cebolla
- Invierno (Dic-Feb): Ajo, habas, preparación suelo

**Intercambios:**
- Semillas: Comparte variedades locales
- Cosecha: Intercambia excedentes
- Plántulas: Ayuda a otros a empezar
- Conocimiento: Comparte tu experiencia

**Tareas comunitarias:**
- Mantenimiento de zonas comunes
- Compostaje colectivo
- Talleres de formación
- Jornadas de limpieza
- Fiestas de la cosecha

**Beneficios:**
- Alimentos frescos y ecológicos
- Conexión con la naturaleza
- Comunidad y aprendizaje
- Ejercicio al aire libre
- Contribución medioambiental
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Necesito experiencia previa para tener una parcela?',
                'respuesta' => 'No es necesario. Ofrecemos talleres para principiantes y los hortelanos experimentados te ayudarán a empezar.',
            ],
            [
                'pregunta' => '¿Cuánto tiempo tengo que dedicar?',
                'respuesta' => 'El compromiso mínimo es de 4 horas mensuales de trabajo comunitario, más el cuidado de tu parcela y tus turnos de riego.',
            ],
            [
                'pregunta' => '¿Qué puedo cultivar?',
                'respuesta' => 'Puedes cultivar hortalizas, aromáticas y pequeños frutales. Te recomendamos seguir el calendario de temporada.',
            ],
            [
                'pregunta' => '¿Qué pasa con mi cosecha?',
                'respuesta' => 'La cosecha es tuya. También puedes intercambiarla con otros hortelanos o donar excedentes.',
            ],
            [
                'pregunta' => '¿Cómo funcionan los turnos de riego?',
                'respuesta' => 'Cada hortelano tiene turnos asignados semanalmente. Si no puedes cumplirlo, busca un sustituto.',
            ],
            [
                'pregunta' => '¿Hay lista de espera?',
                'respuesta' => 'Si no hay parcelas disponibles, entrarás en lista de espera. Te notificaremos cuando haya vacantes.',
            ],
            [
                'pregunta' => '¿Puedo llevar a mi familia?',
                'respuesta' => 'Sí, los menores son bienvenidos bajo supervisión adulta. Es una gran actividad familiar.',
            ],
            [
                'pregunta' => '¿Qué herramientas necesito?',
                'respuesta' => 'El huerto dispone de herramientas básicas compartidas. Puedes traer las tuyas si lo prefieres.',
            ],
        ];
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
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
            Flavor_Page_Creator::refresh_module_pages('huertos_urbanos');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('huertos-urbanos');
        if (!$pagina && !get_option('flavor_huertos_urbanos_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['huertos_urbanos']);
            update_option('flavor_huertos_urbanos_pages_created', 1, false);
        }
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Huertos Urbanos', 'flavor-chat-ia'),
                'slug' => 'huertos-urbanos',
                'content' => '<h1>' . __('Huertos Urbanos Comunitarios', 'flavor-chat-ia') . '</h1>
<p>' . __('Cultiva tus propios alimentos en la ciudad', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="huertos_urbanos" action="listar_huertos" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Solicitar Parcela', 'flavor-chat-ia'),
                'slug' => 'solicitar',
                'content' => '<h1>' . __('Solicitar Parcela', 'flavor-chat-ia') . '</h1>
<p>' . __('Solicita tu parcela en el huerto comunitario', 'flavor-chat-ia') . '</p>

[flavor_module_form module="huertos_urbanos" action="solicitar_parcela"]',
                'parent' => 'huertos-urbanos',
            ],
            [
                'title' => __('Mi Parcela', 'flavor-chat-ia'),
                'slug' => 'mi-parcela',
                'content' => '<h1>' . __('Mi Parcela', 'flavor-chat-ia') . '</h1>

[flavor_module_dashboard module="huertos_urbanos"]',
                'parent' => 'huertos-urbanos',
            ],
            [
                'title' => __('Intercambios', 'flavor-chat-ia'),
                'slug' => 'intercambios',
                'content' => '<h1>' . __('Intercambios de Productos', 'flavor-chat-ia') . '</h1>
<p>' . __('Intercambia semillas, plantones y cosecha con otros hortelanos', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="huertos_urbanos" action="listar_intercambios" columnas="3"]',
                'parent' => 'huertos-urbanos',
            ],
        ];
    }
}
