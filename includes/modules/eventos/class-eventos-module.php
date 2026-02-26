<?php
/**
 * Modulo de Eventos para Chat IA
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) { exit; }

// Incluir API REST
require_once __DIR__ . '/class-eventos-api.php';

class Flavor_Chat_Eventos_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;
    use Flavor_Module_Dashboard_Tabs_Trait;
    use Flavor_Encuestas_Features;

    protected $id = 'eventos';
    protected $name = 'Eventos y Calendario';
    protected $description = 'Gestion de eventos, calendario e inscripciones';

    // Visibilidad por defecto: publico (cualquiera puede ver eventos)
    protected $default_visibility = 'public';
    protected $required_capability = 'read';

    public function __construct() { parent::__construct(); }

    public function can_activate() {
        global $wpdb;
        $nombre_tabla = $wpdb->prefix . 'flavor_eventos';
        return Flavor_Chat_Helpers::tabla_existe($nombre_tabla);
    }

    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Eventos no estan creadas. Activa el modulo.', 'flavor-chat-ia');
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    protected function get_default_settings() {
        return [
            'requiere_aprobacion' => false,
            'aforo_maximo_defecto' => 0,
            'permite_invitados' => true,
            'enviar_recordatorios' => true,
            'dias_recordatorio' => 1,
            'precio_defecto' => 0,
            'precio_socios_defecto' => 0,
            'permitir_lista_espera' => true,
            'tipos_evento' => ['conferencia','taller','charla','festival','deportivo','cultural','social','networking'],
        ];
    }

    /**
     * Tipos de contenido que este módulo acepta como integraciones
     *
     * @return array Lista de IDs de tipos de contenido aceptados
     */
    protected function get_accepted_integrations() {
        return ['recetas', 'multimedia', 'podcast'];
    }

    /**
     * Targets donde se pueden vincular integraciones
     *
     * @return array Configuración de targets
     */
    protected function get_integration_targets() {
        return [
            [
                'type' => 'custom_table',
                'table' => 'flavor_eventos',
                'module_id' => $this->id,
            ],
        ];
    }

    // =========================================================================
    // TABS DEL DASHBOARD (SISTEMA FLEXIBLE)
    // =========================================================================

    /**
     * Define los tabs del dashboard para Mi Portal
     *
     * @return array Configuración de tabs
     */
    protected function define_dashboard_tabs() {
        return [
            'proximos' => [
                'label'    => __('Próximos', 'flavor-chat-ia'),
                'icon'     => 'dashicons-calendar',
                'content'  => '[eventos_proximos limit="12"]',
                'priority' => 10,
            ],
            'inscripciones' => [
                'label'    => __('Mis Inscripciones', 'flavor-chat-ia'),
                'icon'     => 'dashicons-tickets-alt',
                'content'  => '[eventos_mis_inscripciones]',
                'priority' => 20,
                'cap'      => 'read',
            ],
            'calendario' => [
                'label'    => __('Calendario', 'flavor-chat-ia'),
                'icon'     => 'dashicons-calendar-alt',
                'content'  => '[eventos_calendario]',
                'priority' => 30,
            ],
            'mapa' => [
                'label'    => __('Mapa', 'flavor-chat-ia'),
                'icon'     => 'dashicons-location',
                'content'  => '[eventos_mapa]',
                'priority' => 40,
            ],
            'valoraciones' => [
                'label'    => __('Valoraciones', 'flavor-chat-ia'),
                'icon'     => 'dashicons-star-filled',
                'content'  => '[flavor_encuestas_contexto tipo="evento" estado="activa" limit="10"]',
                'priority' => 50,
                'cap'      => 'read',
            ],
        ];
    }

    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX para dashboard de admin
        add_action('wp_ajax_eventos_get_dashboard_data', [$this, 'ajax_get_dashboard_data']);

        // Registrar páginas de administración
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();

        // Registrar como consumidor de integraciones
        $this->register_as_integration_consumer();

        // Inicializar funcionalidades de encuestas
        $this->init_encuestas_features('evento');

        // Cargar funcionalidades del Sello de Conciencia (+12 pts)
        $this->cargar_funcionalidades_conciencia();

        // Cargar Frontend Controller
        $this->cargar_frontend_controller();
    }

    /**
     * Carga el controlador frontend
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-eventos-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Eventos_Frontend_Controller::get_instance();
        }
    }

    /**
     * Cargar funcionalidades de Sello de Conciencia para Eventos
     * +12 puntos: Eventos inclusivos, huella de carbono, voluntariado, colaboraciones
     */
    private function cargar_funcionalidades_conciencia() {
        $archivo_conciencia = dirname(__FILE__) . '/class-eventos-conciencia-features.php';
        if (file_exists($archivo_conciencia)) {
            require_once $archivo_conciencia;
            if (class_exists('Flavor_Eventos_Conciencia_Features')) {
                Flavor_Eventos_Conciencia_Features::get_instance();
            }
        }
    }

    /**
     * Registrar rutas REST API (para apps)
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/eventos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_listar_eventos'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/eventos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_ver_evento'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/eventos/(?P<id>\d+)/inscribirse', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_inscribirse_evento'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/eventos/mis', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_eventos'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/eventos/mis-inscripciones', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_inscripciones'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/eventos/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_estadisticas'],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }

    public function rest_listar_eventos($request) {
        $respuesta = $this->action_listar_eventos([
            'tipo' => $request->get_param('tipo'),
            'categoria' => $request->get_param('categoria'),
            'desde' => $request->get_param('desde'),
            'hasta' => $request->get_param('hasta'),
            'limite' => $request->get_param('limite'),
            'estado' => $request->get_param('estado'),
            'solo_gratuitos' => $request->get_param('solo_gratuitos'),
        ]);

        return rest_ensure_response($respuesta);
    }

    public function rest_ver_evento($request) {
        return rest_ensure_response($this->action_ver_evento([
            'evento_id' => $request->get_param('id'),
        ]));
    }

    public function rest_inscribirse_evento($request) {
        return rest_ensure_response($this->action_inscribirse_evento([
            'evento_id' => $request->get_param('id'),
            'num_plazas' => $request->get_param('num_plazas'),
            'nombre' => $request->get_param('nombre'),
            'email' => $request->get_param('email'),
            'telefono' => $request->get_param('telefono'),
            'notas' => $request->get_param('notas'),
        ]));
    }

    public function rest_mis_eventos($request) {
        return rest_ensure_response($this->action_mis_eventos([
            'estado' => $request->get_param('estado'),
            'limite' => $request->get_param('limite'),
        ]));
    }

    public function rest_mis_inscripciones($request) {
        return rest_ensure_response($this->action_mis_inscripciones([
            'estado' => $request->get_param('estado'),
            'limite' => $request->get_param('limite'),
        ]));
    }

    public function rest_estadisticas($request) {
        return rest_ensure_response($this->action_estadisticas([]));
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'eventos',
            'label' => __('Eventos', 'flavor-chat-ia'),
            'icon' => 'dashicons-calendar',
            'capability' => 'edit_posts', // Permite a editores y administradores gestionar eventos
            'categoria' => 'actividades',
            'paginas' => [
                [
                    'slug' => 'eventos-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'eventos-proximos',
                    'titulo' => __('Próximos Eventos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_proximos'],
                    'badge' => [$this, 'contar_eventos_proximos'],
                ],
                [
                    'slug' => 'eventos-calendario',
                    'titulo' => __('Calendario', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_calendario'],
                ],
                [
                    'slug' => 'eventos-asistentes',
                    'titulo' => __('Asistentes', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_asistentes'],
                ],
                [
                    'slug' => 'eventos-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
                [
                    'slug' => 'eventos-nuevo',
                    'titulo' => __('Nuevo Evento', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_nuevo'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta eventos próximos (publicados y futuros)
     *
     * @return int
     */
    public function contar_eventos_proximos() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
            return 0;
        }
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_eventos WHERE estado = 'publicado' AND fecha_inicio > %s",
            current_time('mysql')
        ));
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $estadisticas = [];

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
            return $estadisticas;
        }

        // Eventos próximos
        $eventos_proximos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_eventos WHERE estado = 'publicado' AND fecha_inicio > %s",
            current_time('mysql')
        ));
        $estadisticas[] = [
            'icon' => 'dashicons-calendar',
            'valor' => $eventos_proximos,
            'label' => __('Eventos próximos', 'flavor-chat-ia'),
            'color' => $eventos_proximos > 0 ? 'blue' : 'gray',
            'enlace' => admin_url('admin.php?page=eventos-proximos'),
        ];

        // Asistentes registrados (inscripciones confirmadas o pendientes)
        if (Flavor_Chat_Helpers::tabla_existe($tabla_inscripciones)) {
            $total_asistentes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_inscripciones WHERE estado IN ('confirmada', 'pendiente')"
            );
            $estadisticas[] = [
                'icon' => 'dashicons-groups',
                'valor' => $total_asistentes,
                'label' => __('Asistentes registrados', 'flavor-chat-ia'),
                'color' => $total_asistentes > 0 ? 'green' : 'gray',
                'enlace' => admin_url('admin.php?page=eventos-asistentes'),
            ];
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de eventos
     */
    public function render_admin_dashboard() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Eventos', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Evento', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=eventos-nuevo'), 'class' => 'button-primary'],
        ]);

        // Resumen de estadísticas
        $estadisticas = $this->action_estadisticas([]);
        if ($estadisticas['success'] && !empty($estadisticas['data'])) {
            $datos = $estadisticas['data'];
            echo '<div class="flavor-stats-grid">';
            echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($datos['total_eventos']) . '</span><span class="stat-label">' . __('Total Eventos', 'flavor-chat-ia') . '</span></div>';
            echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($datos['eventos_proximos']) . '</span><span class="stat-label">' . __('Próximos', 'flavor-chat-ia') . '</span></div>';
            echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($datos['total_inscripciones']) . '</span><span class="stat-label">' . __('Inscripciones', 'flavor-chat-ia') . '</span></div>';
            echo '</div>';
        }

        echo '<p>' . __('Panel de control del módulo de eventos con métricas y accesos rápidos.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }

    /**
     * Renderiza la página de próximos eventos
     */
    public function render_admin_proximos() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Próximos Eventos', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Evento', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=eventos-nuevo'), 'class' => 'button-primary'],
        ]);

        // Listado de próximos eventos
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_eventos WHERE estado = 'publicado' AND fecha_inicio > %s ORDER BY fecha_inicio ASC LIMIT 20",
            current_time('mysql')
        ), ARRAY_A);

        if (!empty($eventos)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Título', 'flavor-chat-ia') . '</th><th>' . __('Fecha', 'flavor-chat-ia') . '</th><th>' . __('Tipo', 'flavor-chat-ia') . '</th><th>' . __('Inscritos', 'flavor-chat-ia') . '</th><th>' . __('Acciones', 'flavor-chat-ia') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($eventos as $evento) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($evento['titulo']) . '</strong></td>';
                echo '<td>' . esc_html(date_i18n('d/m/Y H:i', strtotime($evento['fecha_inicio']))) . '</td>';
                echo '<td>' . esc_html(ucfirst($evento['tipo'])) . '</td>';
                echo '<td>' . esc_html($evento['inscritos_count']) . ($evento['aforo_maximo'] > 0 ? '/' . $evento['aforo_maximo'] : '') . '</td>';
                echo '<td><a href="' . esc_url(admin_url('admin.php?page=eventos-editar&id=' . $evento['id'])) . '" class="button button-small">' . __('Ver', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay eventos próximos programados.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza el calendario de eventos
     */
    public function render_admin_calendario() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Calendario de Eventos', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Evento', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=eventos-nuevo'), 'class' => 'button-primary'],
        ]);
        echo '<p>' . __('Vista de calendario mensual con todos los eventos programados.', 'flavor-chat-ia') . '</p>';
        echo '<div id="flavor-eventos-calendario">';

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $mes = isset($_GET['mes']) ? sanitize_text_field($_GET['mes']) : date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = date('Y-m');
        }
        $inicio_mes = $mes . '-01';
        $fin_mes = date('Y-m-t', strtotime($inicio_mes));

        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, fecha_inicio, fecha_fin, tipo, estado
             FROM $tabla_eventos
             WHERE DATE(fecha_inicio) BETWEEN %s AND %s
             ORDER BY fecha_inicio ASC",
            $inicio_mes,
            $fin_mes
        ), ARRAY_A);

        $por_dia = [];
        foreach ($eventos as $evento) {
            $dia = date('Y-m-d', strtotime($evento['fecha_inicio']));
            if (!isset($por_dia[$dia])) {
                $por_dia[$dia] = [];
            }
            $por_dia[$dia][] = $evento;
        }

        echo '<form method="get" style="margin: 12px 0;">';
        echo '<input type="hidden" name="page" value="eventos-calendario">';
        echo '<input type="month" name="mes" value="' . esc_attr($mes) . '"> ';
        echo '<button class="button">' . esc_html__('Ver', 'flavor-chat-ia') . '</button>';
        echo '</form>';

        if (empty($eventos)) {
            echo '<p>' . esc_html__('No hay eventos programados en este mes.', 'flavor-chat-ia') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>' . esc_html__('Fecha', 'flavor-chat-ia') . '</th>';
            echo '<th>' . esc_html__('Eventos', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($por_dia as $fecha => $items) {
                echo '<tr>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($fecha))) . '</td>';
                echo '<td>';
                foreach ($items as $item) {
                    echo '<div style="margin-bottom:6px;">';
                    echo '<strong>' . esc_html($item['titulo']) . '</strong> ';
                    echo '<span style="color:#666;">(' . esc_html($item['tipo']) . ')</span> ';
                    echo '<span style="color:#999;">' . esc_html(date_i18n('H:i', strtotime($item['fecha_inicio']))) . '</span>';
                    echo '</div>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Renderiza la página de gestión de asistentes
     */
    public function render_admin_asistentes() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestión de Asistentes', 'flavor-chat-ia'));

        // Listado de inscripciones
        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        $inscripciones = $wpdb->get_results(
            "SELECT i.*, e.titulo as evento_titulo, e.fecha_inicio
             FROM $tabla_inscripciones i
             LEFT JOIN $tabla_eventos e ON i.evento_id = e.id
             WHERE i.estado IN ('confirmada', 'pendiente')
             ORDER BY i.created_at DESC
             LIMIT 50",
            ARRAY_A
        );

        if (!empty($inscripciones)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Nombre', 'flavor-chat-ia') . '</th><th>' . __('Email', 'flavor-chat-ia') . '</th><th>' . __('Evento', 'flavor-chat-ia') . '</th><th>' . __('Plazas', 'flavor-chat-ia') . '</th><th>' . __('Estado', 'flavor-chat-ia') . '</th><th>' . __('Acciones', 'flavor-chat-ia') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($inscripciones as $inscripcion) {
                $clase_estado = $inscripcion['estado'] === 'confirmada' ? 'status-confirmed' : 'status-pending';
                echo '<tr>';
                echo '<td><strong>' . esc_html($inscripcion['nombre']) . '</strong></td>';
                echo '<td>' . esc_html($inscripcion['email']) . '</td>';
                echo '<td>' . esc_html($inscripcion['evento_titulo'] ?? __('Evento eliminado', 'flavor-chat-ia')) . '</td>';
                echo '<td>' . esc_html($inscripcion['num_plazas']) . '</td>';
                echo '<td><span class="' . esc_attr($clase_estado) . '">' . esc_html(ucfirst($inscripcion['estado'])) . '</span></td>';
                echo '<td><a href="#" class="button button-small ev-gestionar-inscripcion" data-id="' . esc_attr($inscripcion['id']) . '" data-estado="' . esc_attr($inscripcion['estado']) . '">' . __('Gestionar', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay asistentes registrados.', 'flavor-chat-ia') . '</p>';
        }

        // Modal para gestionar inscripción
        ?>
        <div id="modal-gestionar-inscripcion" style="display:none;">
            <div class="modal-overlay" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:100000;">
                <div class="modal-content" style="position:relative;max-width:400px;margin:50px auto;background:#fff;padding:20px;border-radius:4px;">
                    <h2><?php _e('Gestionar Inscripción', 'flavor-chat-ia'); ?></h2>
                    <form id="form-gestionar-inscripcion" method="post">
                        <?php wp_nonce_field('ev_gestionar_inscripcion', 'ev_nonce'); ?>
                        <input type="hidden" name="inscripcion_id" id="gestionar-inscripcion-id" />
                        <p>
                            <label for="gestionar-estado"><?php _e('Cambiar estado:', 'flavor-chat-ia'); ?></label>
                            <select id="gestionar-estado" name="estado" style="width:100%;">
                                <option value="pendiente"><?php _e('Pendiente', 'flavor-chat-ia'); ?></option>
                                <option value="confirmada"><?php _e('Confirmada', 'flavor-chat-ia'); ?></option>
                                <option value="cancelada"><?php _e('Cancelada', 'flavor-chat-ia'); ?></option>
                            </select>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary"><?php _e('Guardar', 'flavor-chat-ia'); ?></button>
                            <button type="button" class="button" id="cerrar-modal-inscripcion"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.ev-gestionar-inscripcion').on('click', function(e) {
                e.preventDefault();
                $('#gestionar-inscripcion-id').val($(this).data('id'));
                $('#gestionar-estado').val($(this).data('estado'));
                $('#modal-gestionar-inscripcion').fadeIn();
            });

            $('#cerrar-modal-inscripcion, .modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $('#modal-gestionar-inscripcion').fadeOut();
                }
            });
        });
        </script>
        <?php
        echo '</div>';
    }

    /**
     * Renderiza la configuración del módulo de eventos
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Eventos', 'flavor-chat-ia'));

        $configuracion_actual = $this->get_default_settings();
        echo '<form method="post" action="">';
        echo '<table class="form-table">';

        echo '<tr><th scope="row"><label for="requiere_aprobacion">' . __('Requiere aprobación', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="requiere_aprobacion" id="requiere_aprobacion" ' . checked($configuracion_actual['requiere_aprobacion'], true, false) . ' />';
        echo '<p class="description">' . __('Las inscripciones requieren aprobación manual.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="aforo_maximo_defecto">' . __('Aforo máximo por defecto', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="aforo_maximo_defecto" id="aforo_maximo_defecto" value="' . esc_attr($configuracion_actual['aforo_maximo_defecto']) . '" min="0" class="small-text" />';
        echo '<p class="description">' . __('0 = sin límite.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="dias_recordatorio">' . __('Días para recordatorio', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="dias_recordatorio" id="dias_recordatorio" value="' . esc_attr($configuracion_actual['dias_recordatorio']) . '" min="0" max="30" class="small-text" />';
        echo '<p class="description">' . __('Días antes del evento para enviar recordatorio.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="permitir_lista_espera">' . __('Permitir lista de espera', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="permitir_lista_espera" id="permitir_lista_espera" ' . checked($configuracion_actual['permitir_lista_espera'], true, false) . ' /></td></tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="guardar_config" class="button-primary" value="' . __('Guardar Configuración', 'flavor-chat-ia') . '" /></p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Renderiza la página para crear/editar eventos
     */
    public function render_admin_nuevo() {
        // Incluir la vista con el formulario completo
        include __DIR__ . '/views/eventos.php';
    }

    public function activate() {
        $this->create_tables();

        // Crear páginas frontend automáticamente
        if (class_exists('Flavor_Page_Creator')) {
            Flavor_Page_Creator::create_pages_for_modules(['eventos']);
        }
    }

    public function deactivate() { }

    /**
     * Crea/actualiza páginas de eventos si es necesario.
     * Se ejecuta en hook init para que $wp_rewrite esté disponible.
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('eventos');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina_eventos = get_page_by_path('eventos');
        if (!$pagina_eventos && !get_option('flavor_eventos_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['eventos']);
            update_option('flavor_eventos_pages_created', 1, false);
        }
    }

    public function maybe_create_tables() {
        global $wpdb;
        $t = $wpdb->prefix . 'flavor_eventos';
        if (!Flavor_Chat_Helpers::tabla_existe($t)) { $this->create_tables(); }
    }

    private function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $te = $wpdb->prefix . 'flavor_eventos';
        $ti = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tc = $wpdb->prefix . 'flavor_eventos_categorias';
        $tr = $wpdb->prefix . 'flavor_eventos_recordatorios';

        $sql_eventos = "CREATE TABLE IF NOT EXISTS $te (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL, descripcion text DEFAULT NULL, contenido longtext DEFAULT NULL,
            imagen varchar(500) DEFAULT NULL,
            tipo enum('conferencia','taller','charla','festival','deportivo','cultural','social','networking') DEFAULT 'conferencia',
            fecha_inicio datetime NOT NULL, fecha_fin datetime DEFAULT NULL,
            ubicacion varchar(255) DEFAULT NULL, direccion varchar(500) DEFAULT NULL,
            coordenadas_lat decimal(10,8) DEFAULT NULL, coordenadas_lng decimal(11,8) DEFAULT NULL,
            organizador_id bigint(20) unsigned DEFAULT NULL,
            precio decimal(10,2) DEFAULT 0.00, precio_socios decimal(10,2) DEFAULT 0.00,
            aforo_maximo int(11) DEFAULT 0, inscritos_count int(11) DEFAULT 0,
            es_online tinyint(1) DEFAULT 0, url_online varchar(500) DEFAULT NULL,
            categorias JSON DEFAULT NULL, etiquetas JSON DEFAULT NULL,
            estado enum('borrador','publicado','cancelado','finalizado') DEFAULT 'borrador',
            es_destacado tinyint(1) DEFAULT 0,
            recurrente enum('diario','semanal','mensual','anual') DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_organizador (organizador_id), KEY idx_fecha (fecha_inicio),
            KEY idx_tipo (tipo), KEY idx_estado (estado), KEY idx_destacado (es_destacado)
        ) $charset;";

        $sql_inscripciones = "CREATE TABLE IF NOT EXISTS $ti (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL, user_id bigint(20) unsigned DEFAULT NULL,
            nombre varchar(255) NOT NULL, email varchar(255) NOT NULL, telefono varchar(50) DEFAULT NULL,
            num_plazas int(11) DEFAULT 1,
            estado enum('confirmada','pendiente','cancelada','lista_espera') DEFAULT 'pendiente',
            notas text DEFAULT NULL, metodo_pago varchar(100) DEFAULT NULL, referencia_pago varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_evento (evento_id), KEY idx_user (user_id),
            KEY idx_estado (estado), KEY idx_email (email)
        ) $charset;";

        $sql_categorias = "CREATE TABLE IF NOT EXISTS $tc (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL, slug varchar(255) NOT NULL, descripcion text DEFAULT NULL,
            icono varchar(100) DEFAULT NULL, color varchar(20) DEFAULT NULL, orden int(11) DEFAULT 0,
            parent_id bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id), UNIQUE KEY idx_slug (slug), KEY idx_parent (parent_id), KEY idx_orden (orden)
        ) $charset;";

        $sql_recordatorios = "CREATE TABLE IF NOT EXISTS $tr (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL, user_id bigint(20) unsigned NOT NULL,
            tipo enum('email','push') DEFAULT 'email', dias_antes int(11) DEFAULT 1,
            enviado tinyint(1) DEFAULT 0, fecha_envio datetime DEFAULT NULL,
            PRIMARY KEY (id), KEY idx_evento_rec (evento_id), KEY idx_user_rec (user_id), KEY idx_enviado (enviado)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_eventos); dbDelta($sql_inscripciones); dbDelta($sql_categorias); dbDelta($sql_recordatorios);
    }

    // ─── Actions ─────────────────────────────────────────────

    public function get_actions() {
        return [
            'listar_eventos'     => ['description' => 'Listar proximos eventos con filtros', 'params' => ['tipo','categoria','desde','hasta','limite','estado','solo_gratuitos']],
            'ver_evento'         => ['description' => 'Ver detalles de un evento', 'params' => ['evento_id']],
            'crear_evento'       => ['description' => 'Crear un nuevo evento', 'params' => ['titulo','descripcion','contenido','tipo','fecha_inicio','fecha_fin','ubicacion','direccion','precio','precio_socios','aforo_maximo','es_online','url_online']],
            'actualizar_evento'  => ['description' => 'Actualizar un evento existente', 'params' => ['evento_id','titulo','descripcion','tipo','fecha_inicio','fecha_fin','ubicacion','precio','estado']],
            'inscribirse'        => ['description' => 'Inscribirse a un evento', 'params' => ['evento_id','nombre','email','telefono','num_plazas','notas']],
            'cancelar_inscripcion' => ['description' => 'Cancelar inscripcion', 'params' => ['evento_id','inscripcion_id']],
            'mis_eventos'        => ['description' => 'Ver eventos del usuario', 'params' => ['estado','limite']],
            'mis_inscripciones'  => ['description' => 'Ver inscripciones del usuario', 'params' => ['estado','limite']],
            'eventos_hoy'        => ['description' => 'Ver eventos de hoy', 'params' => ['tipo']],
            'estadisticas'       => ['description' => 'Obtener estadisticas de eventos', 'params' => []],
            'eventos_proximos'   => ['description' => 'Listar eventos proximos (alias de listar_eventos)', 'params' => ['tipo', 'categoria', 'limite']],
            'inscribirse_evento' => ['description' => 'Inscribirse a un evento (alias de inscribirse)', 'params' => ['evento_id', 'nombre', 'email', 'telefono', 'num_plazas', 'notas']],
        ];
    }

    public function execute_action($action_name, $params) {
        $nombre_metodo = 'action_' . $action_name;
        if (method_exists($this, $nombre_metodo)) {
            return $this->$nombre_metodo($params);
        }
        return ['success' => false, 'message' => __('Accion no encontrada', 'flavor-chat-ia')];
    }

    // ─── Action: listar_eventos ──────────────────────────────

    private function action_listar_eventos($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $where = ['1=1'];
        $values = [];
        if (!empty($params['tipo'])) {
            $where[] = 'tipo = %s';
            $values[] = sanitize_text_field($params['tipo']);
        }
        if (!empty($params['estado'])) {
            $where[] = 'estado = %s';
            $values[] = sanitize_text_field($params['estado']);
        } else {
            $where[] = "estado = 'publicado'";
        }
        if (!empty($params['desde'])) {
            $where[] = 'fecha_inicio >= %s';
            $values[] = sanitize_text_field($params['desde']);
        }
        if (!empty($params['hasta'])) {
            $where[] = 'fecha_inicio <= %s';
            $values[] = sanitize_text_field($params['hasta']);
        }
        if (!empty($params['solo_gratuitos'])) {
            $where[] = 'precio = 0';
        }
        $limite = isset($params['limite']) ? absint($params['limite']) : 20;
        $where_sql = implode(' AND ', $where);
        $sql = "SELECT * FROM $tabla WHERE $where_sql ORDER BY fecha_inicio ASC LIMIT %d";
        $values[] = $limite;
        $eventos = $wpdb->get_results($wpdb->prepare($sql, ...$values), ARRAY_A);
        if (!$eventos) {
            return ['success' => true, 'data' => [], 'message' => __('eventos_buscar', 'flavor-chat-ia')];
        }
        $resultado = [];
        foreach ($eventos as $evento) {
            $resultado[] = [
                'id'                 => (int) $evento['id'],
                'titulo'             => $evento['titulo'],
                'tipo'               => $evento['tipo'],
                'fecha_inicio'       => $evento['fecha_inicio'],
                'fecha_fin'          => $evento['fecha_fin'],
                'ubicacion'          => $evento['ubicacion'],
                'precio'             => (float) $evento['precio'],
                'aforo_maximo'       => (int) $evento['aforo_maximo'],
                'inscritos_count'    => (int) $evento['inscritos_count'],
                'plazas_disponibles' => $this->calcular_plazas_disponibles($evento),
                'estado'             => $evento['estado'],
            ];
        }
        return ['success' => true, 'data' => $resultado, 'total' => count($resultado)];
    }

    // ─── Action: ver_evento ──────────────────────────────────

    private function action_ver_evento($params) {
        if (empty($params['evento_id'])) {
            return ['success' => false, 'message' => __('Accion no encontrada', 'flavor-chat-ia')];
        }
        global $wpdb;
        $tabla_ev = $wpdb->prefix . 'flavor_eventos';
        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $evento_id = absint($params['evento_id']);
        $evento = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $tabla_ev WHERE id = %d", $evento_id),
            ARRAY_A
        );
        if (!$evento) {
            return ['success' => false, 'message' => __('Accion no encontrada', 'flavor-chat-ia')];
        }
        $total_inscritos = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(num_plazas), 0) FROM $tabla_ins WHERE evento_id = %d AND estado IN ('confirmada','pendiente')",
                $evento_id
            )
        );
        $evento['inscritos_real'] = $total_inscritos;
        $evento['plazas_disponibles'] = $this->calcular_plazas_disponibles($evento);
        $evento['precio_formateado'] = $this->format_price((float) $evento['precio']);
        if ((float) $evento['precio_socios'] > 0) {
            $evento['precio_socios_formateado'] = $this->format_price((float) $evento['precio_socios']);
        }
        return ['success' => true, 'data' => $evento];
    }

    // ─── Action: crear_evento ────────────────────────────────

    private function action_crear_evento($params) {
        if (empty($params['titulo']) || empty($params['fecha_inicio'])) {
            return ['success' => false, 'message' => __('Accion no encontrada', 'flavor-chat-ia')];
        }
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $datos = [
            'titulo'        => sanitize_text_field($params['titulo']),
            'descripcion'   => isset($params['descripcion']) ? sanitize_textarea_field($params['descripcion']) : '',
            'contenido'     => isset($params['contenido']) ? wp_kses_post($params['contenido']) : '',
            'tipo'          => isset($params['tipo']) ? sanitize_text_field($params['tipo']) : 'conferencia',
            'fecha_inicio'  => sanitize_text_field($params['fecha_inicio']),
            'fecha_fin'     => isset($params['fecha_fin']) ? sanitize_text_field($params['fecha_fin']) : null,
            'ubicacion'     => isset($params['ubicacion']) ? sanitize_text_field($params['ubicacion']) : null,
            'direccion'     => isset($params['direccion']) ? sanitize_text_field($params['direccion']) : null,
            'precio'        => isset($params['precio']) ? floatval($params['precio']) : 0.00,
            'precio_socios' => isset($params['precio_socios']) ? floatval($params['precio_socios']) : 0.00,
            'aforo_maximo'  => isset($params['aforo_maximo']) ? absint($params['aforo_maximo']) : 0,
            'es_online'     => !empty($params['es_online']) ? 1 : 0,
            'url_online'    => isset($params['url_online']) ? esc_url_raw($params['url_online']) : null,
            'organizador_id' => get_current_user_id(),
            'estado'        => 'borrador',
        ];
        $resultado = $wpdb->insert($tabla, $datos);
        if ($resultado === false) {
            return ['success' => false, 'message' => __('Organiza un evento para la comunidad', 'flavor-chat-ia')];
        }
        return ['success' => true, 'data' => ['id' => $wpdb->insert_id], 'message' => __('titulo', 'flavor-chat-ia')];
    }

    // ─── Action: actualizar_evento ───────────────────────────

    private function action_actualizar_evento($params) {
        if (empty($params['evento_id'])) {
            return ['success' => false, 'message' => __('textarea', 'flavor-chat-ia')];
        }
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $evento_id = absint($params['evento_id']);
        $campos_permitidos = ['titulo','descripcion','tipo','fecha_inicio','fecha_fin','ubicacion','precio','estado'];
        $datos_actualizar = [];
        foreach ($campos_permitidos as $campo) {
            if (isset($params[$campo])) {
                $datos_actualizar[$campo] = sanitize_text_field($params[$campo]);
            }
        }
        if (empty($datos_actualizar)) {
            return ['success' => false, 'message' => __('Reunión', 'flavor-chat-ia')];
        }
        $resultado = $wpdb->update($tabla, $datos_actualizar, ['id' => $evento_id]);
        if ($resultado === false) {
            return ['success' => false, 'message' => __('Networking', 'flavor-chat-ia')];
        }
        return ['success' => true, 'message' => __('Evento actualizado correctamente', 'flavor-chat-ia')];
    }

    // ─── Action: inscribirse ─────────────────────────────────

    private function action_inscribirse($params) {
        if (empty($params['evento_id'])) {
            return ['success' => false, 'message' => __('datetime-local', 'flavor-chat-ia')];
        }
        global $wpdb;
        $tabla_ev = $wpdb->prefix . 'flavor_eventos';
        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $evento_id = absint($params['evento_id']);
        $evento = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $tabla_ev WHERE id = %d AND estado = 'publicado'", $evento_id),
            ARRAY_A
        );
        if (!$evento) {
            return ['success' => false, 'message' => __('Aforo máximo', 'flavor-chat-ia')];
        }
        $num_plazas = isset($params['num_plazas']) ? absint($params['num_plazas']) : 1;
        $plazas_disponibles = $this->calcular_plazas_disponibles($evento);
        $estado_inscripcion = 'confirmada';
        if ((int) $evento['aforo_maximo'] > 0 && $plazas_disponibles >= 0 && $plazas_disponibles < $num_plazas) {
            if ($this->get_setting('permitir_lista_espera')) {
                $estado_inscripcion = 'lista_espera';
            } else {
                return ['success' => false, 'message' => __('min', 'flavor-chat-ia')];
            }
        }
        if ($this->get_setting('requiere_aprobacion')) {
            $estado_inscripcion = 'pendiente';
        }
        $nombre_inscrito = !empty($params['nombre']) ? sanitize_text_field($params['nombre']) : '';
        $email_inscrito = !empty($params['email']) ? sanitize_email($params['email']) : '';
        if (empty($nombre_inscrito) || empty($email_inscrito)) {
            $usuario_actual = wp_get_current_user();
            if ($usuario_actual->ID) {
                if (empty($nombre_inscrito)) { $nombre_inscrito = $usuario_actual->display_name; }
                if (empty($email_inscrito))  { $email_inscrito = $usuario_actual->user_email; }
            } else {
                return ['success' => false, 'message' => __('inscribirse_evento', 'flavor-chat-ia')];
            }
        }
        $datos_inscripcion = [
            'evento_id'  => $evento_id,
            'user_id'    => get_current_user_id() ?: null,
            'nombre'     => $nombre_inscrito,
            'email'      => $email_inscrito,
            'telefono'   => isset($params['telefono']) ? sanitize_text_field($params['telefono']) : null,
            'num_plazas' => $num_plazas,
            'estado'     => $estado_inscripcion,
            'notas'      => isset($params['notas']) ? sanitize_textarea_field($params['notas']) : null,
        ];
        $resultado = $wpdb->insert($tabla_ins, $datos_inscripcion);
        if ($resultado === false) {
            return ['success' => false, 'message' => __('required', 'flavor-chat-ia')];
        }
        $wpdb->query(
            $wpdb->prepare("UPDATE $tabla_ev SET inscritos_count = inscritos_count + %d WHERE id = %d", $num_plazas, $evento_id)
        );

        // Hook para sistema de reputación
        $inscripcion_id = $wpdb->insert_id;
        do_action('flavor_evento_inscripcion', $usuario_id, $evento_id);

        return [
            'success' => true,
            'data'    => ['inscripcion_id' => $inscripcion_id, 'estado' => $estado_inscripcion],
            'message' => __('number', 'flavor-chat-ia'),
        ];
    }

    // ─── Action: cancelar_inscripcion ────────────────────────

    private function action_cancelar_inscripcion($params) {
        if (empty($params['evento_id'])) {
            return ['success' => false, 'message' => __('Comentarios', 'flavor-chat-ia')];
        }
        global $wpdb;
        $tabla_ev = $wpdb->prefix . 'flavor_eventos';
        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $evento_id = absint($params['evento_id']);
        if (!empty($params['inscripcion_id'])) {
            $inscripcion_id = absint($params['inscripcion_id']);
            $inscripcion = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $tabla_ins WHERE id = %d AND evento_id = %d AND estado != 'cancelada'",
                    $inscripcion_id, $evento_id
                ), ARRAY_A
            );
        } else {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return ['success' => false, 'message' => __('textarea', 'flavor-chat-ia')];
            }
            $inscripcion = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $tabla_ins WHERE user_id = %d AND evento_id = %d AND estado != 'cancelada'",
                    $user_id, $evento_id
                ), ARRAY_A
            );
        }
        if (!$inscripcion) {
            return ['success' => false, 'message' => __('Accion no encontrada', 'flavor-chat-ia')];
        }
        $wpdb->update($tabla_ins, ['estado' => 'cancelada'], ['id' => $inscripcion['id']]);
        $plazas_canceladas = (int) $inscripcion['num_plazas'];
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $tabla_ev SET inscritos_count = GREATEST(0, inscritos_count - %d) WHERE id = %d",
                $plazas_canceladas, $evento_id
            )
        );
        return ['success' => true, 'message' => __('eventos/hero', 'flavor-chat-ia')];
    }

    // ─── Action: mis_eventos ─────────────────────────────────

    private function action_mis_eventos($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => __('Calendario de Eventos', 'flavor-chat-ia')];
        }
        $limite = isset($params['limite']) ? absint($params['limite']) : 20;
        $estado_filtro = '';
        $args = [$user_id];
        if (!empty($params['estado'])) {
            $estado_filtro = ' AND estado = %s';
            $args[] = sanitize_text_field($params['estado']);
        }
        $args[] = $limite;
        $eventos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tabla WHERE organizador_id = %d$estado_filtro ORDER BY fecha_inicio DESC LIMIT %d",
                ...$args
            ), ARRAY_A
        );
        return ['success' => true, 'data' => $eventos ?: [], 'total' => count($eventos ?: [])];
    }

    // ─── Action: mis_inscripciones ───────────────────────────

    private function action_mis_inscripciones($params) {
        global $wpdb;
        $tabla_ev = $wpdb->prefix . 'flavor_eventos';
        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => __('Accion no encontrada', 'flavor-chat-ia')];
        }
        $limite = isset($params['limite']) ? absint($params['limite']) : 20;
        $estado_filtro = '';
        $args = [$user_id];
        if (!empty($params['estado'])) {
            $estado_filtro = ' AND i.estado = %s';
            $args[] = sanitize_text_field($params['estado']);
        }
        $args[] = $limite;
        $inscripciones = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.*, e.titulo, e.fecha_inicio, e.ubicacion, e.tipo FROM $tabla_ins i LEFT JOIN $tabla_ev e ON i.evento_id = e.id WHERE i.user_id = %d$estado_filtro ORDER BY i.created_at DESC LIMIT %d",
                ...$args
            ), ARRAY_A
        );
        return ['success' => true, 'data' => $inscripciones ?: [], 'total' => count($inscripciones ?: [])];
    }

    // ─── Action: eventos_hoy ─────────────────────────────────

    private function action_eventos_hoy($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $hoy_inicio = current_time('Y-m-d') . ' 00:00:00';
        $hoy_fin    = current_time('Y-m-d') . ' 23:59:59';
        $tipo_filtro = '';
        $args = [$hoy_inicio, $hoy_fin];
        if (!empty($params['tipo'])) {
            $tipo_filtro = ' AND tipo = %s';
            $args[] = sanitize_text_field($params['tipo']);
        }
        $eventos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tabla WHERE estado = 'publicado' AND fecha_inicio BETWEEN %s AND %s$tipo_filtro ORDER BY fecha_inicio ASC",
                ...$args
            ), ARRAY_A
        );
        return ['success' => true, 'data' => $eventos ?: [], 'total' => count($eventos ?: []), 'fecha' => current_time('Y-m-d')];
    }

    // ─── Action: estadisticas ────────────────────────────────

    private function action_estadisticas($params) {
        global $wpdb;
        $tabla_ev = $wpdb->prefix . 'flavor_eventos';
        $tabla_ins = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $total_eventos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_ev");
        $eventos_publicados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_ev WHERE estado = 'publicado'");
        $eventos_proximos = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $tabla_ev WHERE estado = 'publicado' AND fecha_inicio > %s", current_time('mysql'))
        );
        $total_inscripciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_ins WHERE estado IN ('confirmada','pendiente')");
        $por_tipo = $wpdb->get_results("SELECT tipo, COUNT(*) as total FROM $tabla_ev GROUP BY tipo", ARRAY_A);
        $por_estado = $wpdb->get_results("SELECT estado, COUNT(*) as total FROM $tabla_ev GROUP BY estado", ARRAY_A);
        return [
            'success' => true,
            'data'    => [
                'total_eventos'        => $total_eventos,
                'eventos_publicados'   => $eventos_publicados,
                'eventos_proximos'     => $eventos_proximos,
                'total_inscripciones'  => $total_inscripciones,
                'por_tipo'             => $por_tipo ?: [],
                'por_estado'           => $por_estado ?: [],
            ],
        ];
    }

    // ─── AI Tool Definitions ─────────────────────────────────

    public function get_tool_definitions() {
        return [
            [
                'name'         => 'eventos_listar',
                'description'  => 'Listar eventos disponibles con filtros opcionales de tipo, fecha y precio',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'tipo'           => ['type' => 'string', 'description' => 'Tipo: conferencia, taller, charla, festival, deportivo, cultural, social, networking'],
                        'desde'          => ['type' => 'string', 'description' => 'Fecha inicio filtro (YYYY-MM-DD)'],
                        'hasta'          => ['type' => 'string', 'description' => 'Fecha fin filtro (YYYY-MM-DD)'],
                        'limite'         => ['type' => 'integer', 'description' => 'Numero maximo de resultados'],
                        'solo_gratuitos' => ['type' => 'boolean', 'description' => 'Solo mostrar eventos gratuitos'],
                    ],
                ],
            ],
            [
                'name'         => 'eventos_buscar',
                'description'  => 'Buscar eventos por texto en titulo, descripcion o ubicacion',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'busqueda' => ['type' => 'string', 'description' => 'Texto a buscar'],
                        'tipo'     => ['type' => 'string', 'description' => 'Filtrar por tipo de evento'],
                        'limite'   => ['type' => 'integer', 'description' => 'Numero maximo de resultados'],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name'         => 'eventos_inscribirse',
                'description'  => 'Inscribir al usuario en un evento',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'evento_id'  => ['type' => 'integer', 'description' => 'ID del evento'],
                        'nombre'     => ['type' => 'string', 'description' => 'Nombre del asistente'],
                        'email'      => ['type' => 'string', 'description' => 'Email del asistente'],
                        'num_plazas' => ['type' => 'integer', 'description' => 'Numero de plazas a reservar'],
                    ],
                    'required' => ['evento_id'],
                ],
            ],
            [
                'name'         => 'eventos_proximos',
                'description'  => 'Obtener los proximos eventos programados',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'dias'   => ['type' => 'integer', 'description' => 'Numero de dias a futuro (default 30)'],
                        'tipo'   => ['type' => 'string', 'description' => 'Filtrar por tipo'],
                        'limite' => ['type' => 'integer', 'description' => 'Maximo resultados'],
                    ],
                ],
            ],
        ];
    }

    // ─── Knowledge Base & FAQs ───────────────────────────────

    public function get_knowledge_base() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'publicado'");
        $proximos = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $tabla WHERE estado = 'publicado' AND fecha_inicio > %s", current_time('mysql'))
        );
        $tipos = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla WHERE estado = 'publicado'");
        return [
            'total_eventos_publicados' => $total,
            'eventos_proximos'         => $proximos,
            'tipos_disponibles'        => $tipos ?: [],
            'descripcion'              => 'Modulo de gestion de eventos con inscripciones, calendario y estadisticas. Soporta eventos presenciales y online.',
        ];
    }

    public function get_faqs() {
        return [
            ['pregunta' => 'Como puedo ver los proximos eventos?', 'respuesta' => 'Puedo mostrarte los proximos eventos. Dime si quieres filtrar por tipo, fecha o precio.'],
            ['pregunta' => 'Como me inscribo a un evento?', 'respuesta' => 'Dime el nombre o ID del evento y te ayudo con la inscripcion. Necesitare tu nombre y email.'],
            ['pregunta' => 'Puedo cancelar mi inscripcion?', 'respuesta' => 'Si, puedo cancelar tu inscripcion. Dime a que evento quieres cancelar.'],
            ['pregunta' => 'Hay eventos gratuitos?', 'respuesta' => 'Puedo filtrarte solo los eventos gratuitos. Quieres que te los muestre?'],
            ['pregunta' => 'Que tipos de eventos hay?', 'respuesta' => 'Tenemos conferencias, talleres, charlas, festivales, eventos deportivos, culturales, sociales y de networking.'],
            ['pregunta' => 'Como puedo crear un evento?', 'respuesta' => 'Puedo ayudarte a crear un evento. Necesitare titulo, descripcion, tipo, fecha y ubicacion como minimo.'],
        ];
    }

    /**
     * Configuración de formularios del módulo
     *
     * @param string $action_name Nombre de la acción
     * @return array Configuración del formulario
     */
    public function get_form_config($action_name) {
        $configs = [
            'crear_evento' => [
                'title' => __('Crear Nuevo Evento', 'flavor-chat-ia'),
                'description' => __('Organiza un evento para la comunidad', 'flavor-chat-ia'),
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título del evento', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Ej: Encuentro vecinal de primavera', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 5,
                        'placeholder' => __('Describe el evento, qué se hará, qué incluye...', 'flavor-chat-ia'),
                    ],
                    'tipo_evento' => [
                        'type' => 'select',
                        'label' => __('Tipo de evento', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'charla' => __('Charla/Conferencia', 'flavor-chat-ia'),
                            'taller' => __('Taller práctico', 'flavor-chat-ia'),
                            'reunion' => __('Reunión', 'flavor-chat-ia'),
                            'festival' => __('Festival/Fiesta', 'flavor-chat-ia'),
                            'deportivo' => __('Actividad deportiva', 'flavor-chat-ia'),
                            'cultural' => __('Evento cultural', 'flavor-chat-ia'),
                            'networking' => __('Networking', 'flavor-chat-ia'),
                        ],
                    ],
                    'fecha_inicio' => [
                        'type' => 'datetime-local',
                        'label' => __('Fecha y hora de inicio', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'fecha_fin' => [
                        'type' => 'datetime-local',
                        'label' => __('Fecha y hora de fin', 'flavor-chat-ia'),
                    ],
                    'ubicacion' => [
                        'type' => 'text',
                        'label' => __('Ubicación', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Centro comunitario, Calle...', 'flavor-chat-ia'),
                    ],
                    'aforo_maximo' => [
                        'type' => 'number',
                        'label' => __('Aforo máximo', 'flavor-chat-ia'),
                        'min' => 1,
                        'placeholder' => __('Número de asistentes', 'flavor-chat-ia'),
                        'description' => __('Deja vacío si es ilimitado', 'flavor-chat-ia'),
                    ],
                    'precio' => [
                        'type' => 'number',
                        'label' => __('Precio entrada (€)', 'flavor-chat-ia'),
                        'step' => '0.01',
                        'min' => 0,
                        'default' => 0,
                        'description' => __('Deja en 0 si es gratuito', 'flavor-chat-ia'),
                    ],
                    'requiere_inscripcion' => [
                        'type' => 'checkbox',
                        'label' => __('Inscripción previa', 'flavor-chat-ia'),
                        'checkbox_label' => __('Requiere inscripción previa', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Crear Evento', 'flavor-chat-ia'),
                'success_message' => __('Evento creado correctamente', 'flavor-chat-ia'),
                'redirect_url' => '/eventos/mis-eventos/',
            ],
            'inscribirse_evento' => [
                'title' => __('Inscribirse en Evento', 'flavor-chat-ia'),
                'fields' => [
                    'evento_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'nombre_completo' => [
                        'type' => 'text',
                        'label' => __('Nombre completo', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'telefono' => [
                        'type' => 'tel',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                        'placeholder' => __('600123456', 'flavor-chat-ia'),
                    ],
                    'num_acompanantes' => [
                        'type' => 'number',
                        'label' => __('Número de acompañantes', 'flavor-chat-ia'),
                        'min' => 0,
                        'max' => 5,
                        'default' => 0,
                    ],
                    'comentarios' => [
                        'type' => 'textarea',
                        'label' => __('Comentarios', 'flavor-chat-ia'),
                        'rows' => 2,
                        'placeholder' => __('Alergias, necesidades especiales...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Confirmar Inscripción', 'flavor-chat-ia'),
                'success_message' => __('¡Inscripción confirmada! Te esperamos.', 'flavor-chat-ia'),
            ],
            'cancelar_inscripcion' => [
                'title' => __('Cancelar Inscripción', 'flavor-chat-ia'),
                'description' => __('Lamentamos que no puedas asistir', 'flavor-chat-ia'),
                'fields' => [
                    'inscripcion_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'motivo' => [
                        'type' => 'textarea',
                        'label' => __('Motivo de cancelación (opcional)', 'flavor-chat-ia'),
                        'rows' => 2,
                    ],
                ],
                'submit_text' => __('Cancelar Inscripción', 'flavor-chat-ia'),
                'success_message' => __('Inscripción cancelada', 'flavor-chat-ia'),
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    // ─── Web Components ──────────────────────────────────────

    public function get_web_components() {
        return [
            'eventos_hero' => [
                'label'       => 'Hero de Eventos',
                'description' => 'Seccion hero con busqueda y filtros de eventos',
                'template'    => 'eventos/hero',
                'category'    => 'eventos',
            ],
            'eventos_grid' => [
                'label'       => 'Grid de Eventos',
                'description' => 'Cuadricula de tarjetas de eventos con filtros',
                'template'    => 'eventos/eventos-grid',
                'category'    => 'eventos',
            ],
            'eventos_calendario' => [
                'label'       => 'Calendario de Eventos',
                'description' => 'Vista de calendario mensual con eventos',
                'template'    => 'eventos/eventos-calendario',
                'category'    => 'eventos',
            ],
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function calcular_plazas_disponibles($evento) {
        $aforo = (int) ($evento['aforo_maximo'] ?? 0);
        if ($aforo <= 0) { return -1; }
        $inscritos = (int) ($evento['inscritos_count'] ?? 0);
        return max(0, $aforo - $inscritos);
    }

    public function obtener_color_tipo_evento($tipo) {
        $colores = [
            'conferencia' => '#3B82F6',
            'taller'      => '#8B5CF6',
            'charla'      => '#06B6D4',
            'festival'    => '#F59E0B',
            'deportivo'   => '#10B981',
            'cultural'    => '#EC4899',
            'social'      => '#F97316',
            'networking'  => '#6366F1',
        ];
        return $colores[$tipo] ?? '#6B7280';
    }

    public function obtener_icono_tipo_evento($tipo) {
        $iconos = [
            'conferencia' => 'microphone',
            'taller'      => 'wrench',
            'charla'      => 'chat-bubble-left-right',
            'festival'    => 'musical-note',
            'deportivo'   => 'trophy',
            'cultural'    => 'paint-brush',
            'social'      => 'users',
            'networking'  => 'link',
        ];
        return $iconos[$tipo] ?? 'calendar';
    }

    // ─── Acciones delegadas para formularios frontend ─────────

    private function action_eventos_proximos($params) {
        return $this->action_listar_eventos($params);
    }

    private function action_inscribirse_evento($params) {
        return $this->action_inscribirse($params);
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        if (!class_exists('Flavor_Page_Creator_V3')) {
            return [];
        }

        return [
            // Página principal
            [
                'title' => __('Eventos', 'flavor-chat-ia'),
                'slug' => 'eventos',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Calendario de Eventos', 'flavor-chat-ia'),
                    'subtitle' => __('Descubre actividades y eventos de la comunidad', 'flavor-chat-ia'),
                    'background' => 'gradient',
                    'module' => 'eventos',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="eventos" action="eventos_proximos" columnas="3" limite="12"]',
                ]),
                'parent' => 0,
            ],

            // Calendario
            [
                'title' => __('Calendario', 'flavor-chat-ia'),
                'slug' => 'calendario',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Calendario de Eventos', 'flavor-chat-ia'),
                    'subtitle' => __('Vista mensual de todos los eventos', 'flavor-chat-ia'),
                    'module' => 'eventos',
                    'current' => 'calendario',
                    'content_after' => '[flavor_module_calendar module="eventos"]',
                ]),
                'parent' => 'eventos',
            ],

            // Crear evento
            [
                'title' => __('Crear Evento', 'flavor-chat-ia'),
                'slug' => 'crear',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Crear Evento', 'flavor-chat-ia'),
                    'subtitle' => __('Organiza un evento para la comunidad', 'flavor-chat-ia'),
                    'module' => 'eventos',
                    'current' => 'crear',
                    'content_after' => '[flavor_module_form module="eventos" action="crear_evento"]',
                ]),
                'parent' => 'eventos',
            ],

            // Mis eventos
            [
                'title' => __('Mis Eventos', 'flavor-chat-ia'),
                'slug' => 'mis-eventos',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Mis Eventos', 'flavor-chat-ia'),
                    'subtitle' => __('Eventos en los que participo u organizo', 'flavor-chat-ia'),
                    'module' => 'eventos',
                    'current' => 'mis_eventos',
                    'content_after' => '[flavor_module_listing module="eventos" action="mis_eventos" user_specific="yes"]',
                ]),
                'parent' => 'eventos',
            ],

            // Asistentes
            [
                'title' => __('Asistentes', 'flavor-chat-ia'),
                'slug' => 'asistentes',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Gestión de Asistentes', 'flavor-chat-ia'),
                    'subtitle' => __('Controla las inscripciones a tus eventos', 'flavor-chat-ia'),
                    'module' => 'eventos',
                    'current' => 'asistentes',
                    'content_after' => '[flavor_module_listing module="eventos" action="gestionar_asistentes"]',
                ]),
                'parent' => 'eventos',
            ],
        ];
    }

    // =========================================================================
    // =========================================================================
    // AJAX DASHBOARD
    // =========================================================================

    /**
     * AJAX: Obtener datos del dashboard de eventos
     */
    public function ajax_get_dashboard_data() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        // KPIs
        $eventos_activos = 0;
        $entradas_vendidas = 0;
        $asistentes_totales = 0;
        $ingresos_totales = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
            $eventos_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_eventos} WHERE estado = 'publicado' AND fecha_inicio >= NOW()"
            );
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_inscripciones)) {
            $entradas_vendidas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE estado = 'confirmada'"
            );

            $asistentes_totales = (int) $wpdb->get_var(
                "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_inscripciones} WHERE estado = 'confirmada'"
            );

            $ingresos_totales = (float) $wpdb->get_var(
                "SELECT COALESCE(SUM(precio_pagado), 0) FROM {$tabla_inscripciones} WHERE estado = 'confirmada'"
            );
        }

        // Datos para gráfico de categorías
        $categorias_data = ['labels' => [], 'values' => []];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
            $categorias = $wpdb->get_results(
                "SELECT tipo, COUNT(*) as total FROM {$tabla_eventos}
                 WHERE estado = 'publicado' GROUP BY tipo ORDER BY total DESC LIMIT 5"
            );
            foreach ($categorias as $cat) {
                $categorias_data['labels'][] = ucfirst($cat->tipo);
                $categorias_data['values'][] = (int) $cat->total;
            }
        }

        // Datos para gráfico de asistencia mensual
        $asistencia_data = ['labels' => [], 'values' => []];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_inscripciones)) {
            for ($i = 5; $i >= 0; $i--) {
                $mes = date('Y-m', strtotime("-{$i} months"));
                $asistencia_data['labels'][] = date_i18n('M', strtotime($mes . '-01'));
                $asistencia_data['values'][] = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_inscripciones}
                     WHERE estado = 'confirmada' AND DATE_FORMAT(fecha_inscripcion, '%%Y-%%m') = %s",
                    $mes
                ));
            }
        }

        wp_send_json_success([
            'kpis' => [
                'eventos_activos'    => $eventos_activos,
                'entradas_vendidas'  => $entradas_vendidas,
                'asistentes_totales' => $asistentes_totales,
                'ingresos_totales'   => number_format($ingresos_totales, 2),
            ],
            'categorias' => $categorias_data,
            'asistencia' => $asistencia_data,
        ]);
    }

    // =========================================================================
    // PÁGINAS DE ADMINISTRACIÓN
    // =========================================================================

    /**
     * Registra las páginas de administración del módulo (ocultas del sidebar)
     */
    public function registrar_paginas_admin() {
        $capability = 'manage_options';

        // Páginas ocultas del sidebar (primer parámetro null)
        add_submenu_page(null, __('Eventos - Dashboard', 'flavor-chat-ia'), __('Dashboard', 'flavor-chat-ia'), $capability, 'eventos', [$this, 'render_pagina_dashboard']);
        add_submenu_page(null, __('Todos los Eventos', 'flavor-chat-ia'), __('Eventos', 'flavor-chat-ia'), $capability, 'eventos-listado', [$this, 'render_pagina_eventos']);
        add_submenu_page(null, __('Calendario', 'flavor-chat-ia'), __('Calendario', 'flavor-chat-ia'), $capability, 'eventos-calendario', [$this, 'render_pagina_calendario']);
        add_submenu_page(null, __('Asistentes', 'flavor-chat-ia'), __('Asistentes', 'flavor-chat-ia'), $capability, 'eventos-asistentes', [$this, 'render_pagina_asistentes']);
        add_submenu_page(null, __('Entradas', 'flavor-chat-ia'), __('Entradas', 'flavor-chat-ia'), $capability, 'eventos-entradas', [$this, 'render_pagina_entradas']);
    }

    public function render_pagina_dashboard() {
        echo '<div class="wrap"><h1>' . esc_html__('Dashboard Eventos', 'flavor-chat-ia') . '</h1>';
        $path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($path)) include $path;
        echo '</div>';
    }

    public function render_pagina_eventos() {
        echo '<div class="wrap"><h1>' . esc_html__('Todos los Eventos', 'flavor-chat-ia') . '</h1>';
        $path = dirname(__FILE__) . '/views/eventos.php';
        if (file_exists($path)) include $path;
        echo '</div>';
    }

    public function render_pagina_calendario() {
        echo '<div class="wrap"><h1>' . esc_html__('Calendario', 'flavor-chat-ia') . '</h1>';
        $path = dirname(__FILE__) . '/views/calendario.php';
        if (file_exists($path)) include $path;
        echo '</div>';
    }

    public function render_pagina_asistentes() {
        echo '<div class="wrap"><h1>' . esc_html__('Asistentes', 'flavor-chat-ia') . '</h1>';
        $path = dirname(__FILE__) . '/views/asistentes.php';
        if (file_exists($path)) include $path;
        echo '</div>';
    }

    public function render_pagina_entradas() {
        echo '<div class="wrap"><h1>' . esc_html__('Entradas', 'flavor-chat-ia') . '</h1>';
        $path = dirname(__FILE__) . '/views/entradas.php';
        if (file_exists($path)) include $path;
        echo '</div>';
    }
}
