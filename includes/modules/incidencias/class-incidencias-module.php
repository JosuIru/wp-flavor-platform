<?php
/**
 * Módulo de Incidencias Urbanas para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Incidencias Urbanas - Reportar problemas del barrio/ciudad
 */
class Flavor_Chat_Incidencias_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;

    /**
     * Versión de las tablas
     */
    const DB_VERSION = '1.0.0';

    /**
     * Constructor
     */
    public function __construct() {
        // Auto-registered AJAX handlers
        add_action('wp_ajax_incidencias_reportar_incidencia', [$this, 'ajax_reportar_incidencia']);
        add_action('wp_ajax_nopriv_incidencias_reportar_incidencia', [$this, 'ajax_reportar_incidencia']);
        add_action('wp_ajax_incidencias_listar_incidencias', [$this, 'ajax_listar_incidencias']);
        add_action('wp_ajax_nopriv_incidencias_listar_incidencias', [$this, 'ajax_listar_incidencias']);
        add_action('wp_ajax_incidencias_detalle_incidencia', [$this, 'ajax_detalle_incidencia']);
        add_action('wp_ajax_nopriv_incidencias_detalle_incidencia', [$this, 'ajax_detalle_incidencia']);
        add_action('wp_ajax_incidencias_votar_incidencia', [$this, 'ajax_votar_incidencia']);
        add_action('wp_ajax_nopriv_incidencias_votar_incidencia', [$this, 'ajax_votar_incidencia']);
        add_action('wp_ajax_incidencias_comentar_incidencia', [$this, 'ajax_comentar_incidencia']);
        add_action('wp_ajax_nopriv_incidencias_comentar_incidencia', [$this, 'ajax_comentar_incidencia']);
        add_action('wp_ajax_incidencias_asignar_responsable', [$this, 'ajax_asignar_responsable']);
        add_action('wp_ajax_nopriv_incidencias_asignar_responsable', [$this, 'ajax_asignar_responsable']);
        add_action('wp_ajax_incidencias_eliminar_incidencia', [$this, 'ajax_eliminar_incidencia']);
        add_action('wp_ajax_nopriv_incidencias_eliminar_incidencia', [$this, 'ajax_eliminar_incidencia']);

        $this->id = 'incidencias';
        $this->name = 'Incidencias Urbanas'; // Translation loaded on init
        $this->description = 'Reportar y gestionar incidencias del barrio: baches, alumbrado, limpieza, etc.'; // Translation loaded on init

        parent::__construct();

        // Integrar sistema de notificaciones
        $this->init_notifications();
    }

    /**
     * Inicializar sistema de notificaciones
     */
    private function init_notifications() {
        add_action('incidencia_created', [$this, 'notify_ticket_created'], 10, 2);
        add_action('incidencia_status_changed', [$this, 'notify_status_changed'], 10, 3);
        add_action('incidencia_comment_added', [$this, 'notify_new_comment'], 10, 3);
        add_action('incidencia_resolved', [$this, 'notify_ticket_resolved'], 10, 2);

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
    }

    /**
     * Notificación: Ticket creado
     */
    public function notify_ticket_created($ticket_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Incidencia reportada', 'flavor-chat-ia'),
            sprintf(__('Tu incidencia #%d ha sido registrada. Te mantendremos informado del progreso.', 'flavor-chat-ia'), $ticket_id),
            [
                'module_id' => $this->id,
                'type' => 'success',
                'link' => home_url("/incidencias/mis-incidencias?ticket={$ticket_id}"),
                'metadata' => [
                    'ticket_id' => $ticket_id,
                    'action' => 'ticket_created'
                ]
            ]
        );
    }

    /**
     * Notificación: Cambio de estado
     */
    public function notify_status_changed($ticket_id, $user_id, $new_status) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $status_labels = [
            'pending' => __('Pendiente', 'flavor-chat-ia'),
            'in_progress' => __('En progreso', 'flavor-chat-ia'),
            'resolved' => __('Resuelta', 'flavor-chat-ia'),
            'closed' => __('Cerrada', 'flavor-chat-ia'),
        ];

        $status_label = $status_labels[$new_status] ?? $new_status;

        $nc->send(
            $user_id,
            __('Estado de incidencia actualizado', 'flavor-chat-ia'),
            sprintf(__('El estado de tu incidencia #%d cambió a: %s', 'flavor-chat-ia'), $ticket_id, $status_label),
            [
                'module_id' => $this->id,
                'type' => 'info',
                'link' => home_url("/incidencias/mis-incidencias?ticket={$ticket_id}"),
                'metadata' => [
                    'ticket_id' => $ticket_id,
                    'action' => 'status_changed',
                    'new_status' => $new_status
                ]
            ]
        );
    }

    /**
     * Notificación: Nuevo comentario
     */
    public function notify_new_comment($ticket_id, $user_id, $comment_author) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Nuevo comentario en tu incidencia', 'flavor-chat-ia'),
            sprintf(__('%s ha comentado en tu incidencia #%d', 'flavor-chat-ia'), $comment_author, $ticket_id),
            [
                'module_id' => $this->id,
                'type' => 'info',
                'link' => home_url("/incidencias/mis-incidencias?ticket={$ticket_id}#comments"),
                'metadata' => [
                    'ticket_id' => $ticket_id,
                    'action' => 'new_comment',
                    'comment_author' => $comment_author
                ]
            ]
        );
    }

    /**
     * Notificación: Incidencia resuelta
     */
    public function notify_ticket_resolved($ticket_id, $user_id) {
        if (!class_exists('Flavor_Notification_Center')) {
            return;
        }

        $nc = Flavor_Notification_Center::get_instance();

        $nc->send(
            $user_id,
            __('Incidencia resuelta', 'flavor-chat-ia'),
            sprintf(__('¡Tu incidencia #%d ha sido resuelta! Gracias por reportarla.', 'flavor-chat-ia'), $ticket_id),
            [
                'module_id' => $this->id,
                'type' => 'success',
                'link' => home_url("/incidencias/mis-incidencias?ticket={$ticket_id}"),
                'metadata' => [
                    'ticket_id' => $ticket_id,
                    'action' => 'ticket_resolved'
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        return Flavor_Chat_Helpers::tabla_existe($tabla_incidencias);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Incidencias no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
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
            'requiere_validacion' => false,
            'permite_anonimo' => true,
            'tiempo_respuesta_objetivo' => 48,
            'notificar_actualizaciones' => true,
            'notificar_dias_antes' => 2,
            'max_fotos_por_incidencia' => 5,
            'tamano_max_foto' => 5,
            'habilitar_votos' => true,
            'habilitar_comentarios' => true,
            'moderacion_comentarios' => false,
            'mapbox_token' => '',
            'centro_mapa_lat' => 40.4168,
            'centro_mapa_lng' => -3.7038,
            'zoom_mapa' => 14,
            'categorias' => [
                'alumbrado' => __('Alumbrado público', 'flavor-chat-ia'),
                'limpieza' => __('Limpieza y residuos', 'flavor-chat-ia'),
                'via_publica' => __('Vía pública (baches, aceras)', 'flavor-chat-ia'),
                'mobiliario' => __('Mobiliario urbano', 'flavor-chat-ia'),
                'parques' => __('Parques y jardines', 'flavor-chat-ia'),
                'ruido' => __('Ruidos y molestias', 'flavor-chat-ia'),
                'agua' => __('Agua y alcantarillado', 'flavor-chat-ia'),
                'senalizacion' => __('Señalización', 'flavor-chat-ia'),
                'accesibilidad' => __('Accesibilidad', 'flavor-chat-ia'),
                'otros' => __('Otros', 'flavor-chat-ia'),
            ],
            'prioridades' => [
                'baja' => __('Baja', 'flavor-chat-ia'),
                'media' => __('Media', 'flavor-chat-ia'),
                'alta' => __('Alta', 'flavor-chat-ia'),
                'urgente' => __('Urgente', 'flavor-chat-ia'),
            ],
            'estados' => [
                'pendiente' => __('Pendiente', 'flavor-chat-ia'),
                'validada' => __('Validada', 'flavor-chat-ia'),
                'en_proceso' => __('En proceso', 'flavor-chat-ia'),
                'resuelta' => __('Resuelta', 'flavor-chat-ia'),
                'cerrada' => __('Cerrada', 'flavor-chat-ia'),
                'rechazada' => __('Rechazada', 'flavor-chat-ia'),
            ],
            'departamentos' => [
                'obras' => __('Obras y Servicios', 'flavor-chat-ia'),
                'medio_ambiente' => __('Medio Ambiente', 'flavor-chat-ia'),
                'seguridad' => __('Seguridad Ciudadana', 'flavor-chat-ia'),
                'urbanismo' => __('Urbanismo', 'flavor-chat-ia'),
                'trafico' => __('Tráfico', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia']; // Solo fotos/videos de la incidencia
    }

    /**
     * Define donde se muestran los metaboxes de integracion
     *
     * @return array Configuracion de targets
     */
    protected function get_integration_targets() {
        return [
            [
                'type'      => 'table',
                'table'     => 'flavor_incidencias',
                'context'   => 'side',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->register_as_integration_consumer();

        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Registrar en Panel Unificado de Gestión
        
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
        $this->registrar_en_panel_unificado();

        // Cargar funcionalidades del Sello de Conciencia (+13 pts)
        $this->cargar_funcionalidades_conciencia();

        // Cargar Frontend Controller
        $this->cargar_frontend_controller();

        // AJAX handlers públicos
        add_action('wp_ajax_incidencias_reportar', [$this, 'ajax_reportar_incidencia']);
        add_action('wp_ajax_nopriv_incidencias_reportar', [$this, 'ajax_reportar_incidencia']);
        add_action('wp_ajax_incidencias_listar', [$this, 'ajax_listar_incidencias']);
        add_action('wp_ajax_nopriv_incidencias_listar', [$this, 'ajax_listar_incidencias']);
        add_action('wp_ajax_incidencias_obtener_mapa', [$this, 'ajax_obtener_mapa']);
        add_action('wp_ajax_nopriv_incidencias_obtener_mapa', [$this, 'ajax_obtener_mapa']);
        add_action('wp_ajax_incidencias_detalle', [$this, 'ajax_detalle_incidencia']);
        add_action('wp_ajax_nopriv_incidencias_detalle', [$this, 'ajax_detalle_incidencia']);

        // AJAX handlers autenticados
        add_action('wp_ajax_incidencias_votar', [$this, 'ajax_votar_incidencia']);
        add_action('wp_ajax_incidencias_quitar_voto', [$this, 'ajax_quitar_voto']);
        add_action('wp_ajax_incidencias_comentar', [$this, 'ajax_comentar_incidencia']);
        add_action('wp_ajax_incidencias_subir_foto', [$this, 'ajax_subir_foto']);

        // AJAX handlers admin
        add_action('wp_ajax_incidencias_cambiar_estado', [$this, 'ajax_cambiar_estado']);
        add_action('wp_ajax_incidencias_cambiar_prioridad', [$this, 'ajax_cambiar_prioridad']);
        add_action('wp_ajax_incidencias_asignar', [$this, 'ajax_asignar_responsable']);
        add_action('wp_ajax_incidencias_eliminar', [$this, 'ajax_eliminar_incidencia']);

        // Cron para notificaciones
        add_action('flavor_incidencias_notificaciones', [$this, 'procesar_notificaciones']);
        if (!wp_next_scheduled('flavor_incidencias_notificaciones')) {
            wp_schedule_event(time(), 'daily', 'flavor_incidencias_notificaciones');
        }

        // Cron para estadísticas
        add_action('flavor_incidencias_estadisticas_diarias', [$this, 'calcular_estadisticas_diarias']);
        if (!wp_next_scheduled('flavor_incidencias_estadisticas_diarias')) {
            wp_schedule_event(time(), 'daily', 'flavor_incidencias_estadisticas_diarias');
        }
    }

    /**
     * Cargar funcionalidades de Sello de Conciencia para Incidencias
     * +13 puntos: Monitoreo ambiental, alertas comunitarias, voluntariado, gamificación
     */
    private function cargar_funcionalidades_conciencia() {
        $archivo_conciencia = dirname(__FILE__) . '/class-incidencias-conciencia-features.php';
        if (file_exists($archivo_conciencia)) {
            require_once $archivo_conciencia;
            if (class_exists('Flavor_Incidencias_Conciencia_Features')) {
                Flavor_Incidencias_Conciencia_Features::get_instance();
            }
        }
    }

    /**
     * Carga el controlador frontend
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-incidencias-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Incidencias_Frontend_Controller::get_instance();
        }
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'incidencias',
            'label' => __('Incidencias', 'flavor-chat-ia'),
            'icon' => 'dashicons-warning',
            'capability' => 'manage_options',
            'categoria' => 'servicios',
            'paginas' => [
                [
                    'slug' => 'incidencias-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'incidencias-abiertas',
                    'titulo' => __('Abiertas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_abiertas'],
                    'badge' => [$this, 'contar_incidencias_abiertas'],
                ],
                [
                    'slug' => 'incidencias-todas',
                    'titulo' => __('Todas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_todas'],
                ],
                [
                    'slug' => 'incidencias-mapa',
                    'titulo' => __('Mapa', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_mapa'],
                ],
                [
                    'slug' => 'incidencias-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta incidencias abiertas (pendientes, validadas, en proceso)
     *
     * @return int
     */
    public function contar_incidencias_abiertas() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_incidencias}
             WHERE estado IN ('pendiente', 'validada', 'en_proceso')"
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
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
            return $estadisticas;
        }

        // Incidencias abiertas
        $incidencias_abiertas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_incidencias}
             WHERE estado IN ('pendiente', 'validada', 'en_proceso')"
        );
        $estadisticas[] = [
            'icon' => 'dashicons-warning',
            'valor' => $incidencias_abiertas,
            'label' => __('Incidencias abiertas', 'flavor-chat-ia'),
            'color' => $incidencias_abiertas > 0 ? 'orange' : 'green',
            'enlace' => admin_url('admin.php?page=incidencias-abiertas'),
        ];

        // Resueltas este mes
        $mes_actual = date('Y-m');
        $resueltas_mes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_incidencias}
             WHERE estado = 'resuelta'
             AND DATE_FORMAT(fecha_resolucion, '%%Y-%%m') = %s",
            $mes_actual
        ));
        $estadisticas[] = [
            'icon' => 'dashicons-yes-alt',
            'valor' => $resueltas_mes,
            'label' => __('Resueltas este mes', 'flavor-chat-ia'),
            'color' => 'green',
            'enlace' => admin_url('admin.php?page=incidencias-todas&estado=resuelta'),
        ];

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de admin de incidencias
     */
    public function render_admin_dashboard() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Incidencias', 'flavor-chat-ia'), [
            ['label' => __('Ver Abiertas', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=incidencias-abiertas'), 'class' => 'button-primary'],
            ['label' => __('Ver Mapa', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=incidencias-mapa'), 'class' => ''],
        ]);
        $this->handle_admin_actions();

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', 'flavor-chat-ia') . '</p></div>';
            echo '</div>';
            return;
        }

        echo '<p>' . __('Panel de control del módulo de incidencias urbanas.', 'flavor-chat-ia') . '</p>';

        $estadisticas = $this->get_estadisticas_dashboard();
        if (!empty($estadisticas)) {
            echo '<div class="flavor-stats-grid">';
            foreach ($estadisticas as $estadistica) {
                $color_class = !empty($estadistica['color']) ? 'flavor-stat-' . $estadistica['color'] : '';
                $enlace = !empty($estadistica['enlace']) ? $estadistica['enlace'] : '';
                $card_open = $enlace ? '<a class="flavor-stat-card ' . esc_attr($color_class) . '" href="' . esc_url($enlace) . '">' : '<div class="flavor-stat-card ' . esc_attr($color_class) . '">';
                $card_close = $enlace ? '</a>' : '</div>';

                echo $card_open;
                echo '<div class="flavor-stat-icon"><span class="dashicons ' . esc_attr($estadistica['icon']) . '"></span></div>';
                echo '<div class="flavor-stat-content">';
                echo '<div class="flavor-stat-value">' . esc_html($estadistica['valor']) . '</div>';
                echo '<div class="flavor-stat-label">' . esc_html($estadistica['label']) . '</div>';
                echo '</div>';
                echo $card_close;
            }
            echo '</div>';
        }

        $this->render_incidencias_resumen();
        echo '</div>';
    }

    /**
     * Renderiza el listado de incidencias abiertas
     */
    public function render_admin_abiertas() {
        $this->render_admin_listado_incidencias(true);
    }

    /**
     * Renderiza el listado de todas las incidencias
     */
    public function render_admin_todas() {
        $this->render_admin_listado_incidencias(false);
    }

    /**
     * Renderiza el mapa de incidencias
     */
    public function render_admin_mapa() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Mapa de Incidencias', 'flavor-chat-ia'));
        $this->handle_admin_actions();

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', 'flavor-chat-ia') . '</p></div>';
            echo '</div>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';
        $incidencias = $wpdb->get_results(
            "SELECT id, numero_incidencia, titulo, categoria, estado, latitud, longitud, direccion, fecha_reporte
             FROM $tabla
             WHERE latitud IS NOT NULL AND longitud IS NOT NULL
             ORDER BY fecha_reporte DESC
             LIMIT 500"
        );

        $map_data = array_map(function($i) {
            return [
                'id' => (int) $i->id,
                'numero' => $i->numero_incidencia,
                'titulo' => $i->titulo,
                'categoria' => $i->categoria,
                'estado' => $i->estado,
                'lat' => (float) $i->latitud,
                'lng' => (float) $i->longitud,
                'direccion' => $i->direccion,
                'fecha' => $i->fecha_reporte,
            ];
        }, $incidencias);

        $centro_lat = (float) $this->get_setting('centro_mapa_lat');
        $centro_lng = (float) $this->get_setting('centro_mapa_lng');
        $zoom = (int) $this->get_setting('zoom_mapa');

        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        echo '<p>' . __('Mapa interactivo de incidencias reportadas con coordenadas.', 'flavor-chat-ia') . '</p>';
        echo '<div id="incidencias-admin-mapa" style="height: 520px; background: #f5f5f5; border: 1px solid #e5e5e5;"></div>';

        echo '<div style="margin-top: 20px;">';
        echo '<h3>' . esc_html__('Últimas incidencias geolocalizadas', 'flavor-chat-ia') . '</h3>';
        if (empty($incidencias)) {
            echo '<p>' . esc_html__('No hay incidencias con coordenadas todavía.', 'flavor-chat-ia') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>ID</th><th>' . esc_html__('Número', 'flavor-chat-ia') . '</th><th>' . esc_html__('Título', 'flavor-chat-ia') . '</th><th>' . esc_html__('Estado', 'flavor-chat-ia') . '</th><th>' . esc_html__('Fecha', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead><tbody>';
            foreach (array_slice($incidencias, 0, 20) as $incidencia) {
                echo '<tr>';
                echo '<td>' . esc_html($incidencia->id) . '</td>';
                echo '<td>' . esc_html($incidencia->numero_incidencia) . '</td>';
                echo '<td>' . esc_html($incidencia->titulo) . '</td>';
                echo '<td>' . esc_html($incidencia->estado) . '</td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($incidencia->fecha_reporte))) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';

        $map_json = wp_json_encode($map_data);
        $map_js = "
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof L === 'undefined') {
                    return;
                }
                var mapEl = document.getElementById('incidencias-admin-mapa');
                if (!mapEl) {
                    return;
                }
                var map = L.map(mapEl).setView([$centro_lat, $centro_lng], $zoom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                var data = $map_json || [];
                data.forEach(function(item) {
                    if (!item.lat || !item.lng) return;
                    var marker = L.marker([item.lat, item.lng]).addTo(map);
                    var popup = '<strong>' + (item.numero || '') + '</strong><br>' +
                        (item.titulo || '') + '<br>' +
                        (item.estado || '') + (item.direccion ? '<br>' + item.direccion : '');
                    marker.bindPopup(popup);
                });
            });
        ";
        wp_add_inline_script('leaflet', $map_js);
        echo '</div>';
    }

    /**
     * Renderiza configuración del módulo
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Incidencias', 'flavor-chat-ia'));
        $this->handle_admin_save_config();
        echo '<p>' . __('Configuración del sistema de incidencias urbanas.', 'flavor-chat-ia') . '</p>';

        $categorias = $this->get_setting('categorias', []);
        $prioridades = $this->get_setting('prioridades', []);
        $estados = $this->get_setting('estados', []);
        $departamentos = $this->get_setting('departamentos', []);

        $format_lines = function($items) {
            $lines = [];
            foreach ($items as $key => $label) {
                $lines[] = $key . '|' . $label;
            }
            return implode("\n", $lines);
        };

        echo '<form method="post">';
        wp_nonce_field('incidencias_config', 'incidencias_config_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . esc_html__('Requiere validación', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="requiere_validacion" value="1" ' . checked($this->get_setting('requiere_validacion'), true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Permite reporte anónimo', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="permite_anonimo" value="1" ' . checked($this->get_setting('permite_anonimo'), true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Tiempo objetivo (h)', 'flavor-chat-ia') . '</th><td><input type="number" name="tiempo_respuesta_objetivo" min="1" value="' . esc_attr($this->get_setting('tiempo_respuesta_objetivo')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Notificar actualizaciones', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="notificar_actualizaciones" value="1" ' . checked($this->get_setting('notificar_actualizaciones'), true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Notificar días antes', 'flavor-chat-ia') . '</th><td><input type="number" name="notificar_dias_antes" min="0" value="' . esc_attr($this->get_setting('notificar_dias_antes')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Máx. fotos por incidencia', 'flavor-chat-ia') . '</th><td><input type="number" name="max_fotos_por_incidencia" min="0" value="' . esc_attr($this->get_setting('max_fotos_por_incidencia')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Tamaño máximo foto (MB)', 'flavor-chat-ia') . '</th><td><input type="number" name="tamano_max_foto" min="1" value="' . esc_attr($this->get_setting('tamano_max_foto')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Habilitar votos', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="habilitar_votos" value="1" ' . checked($this->get_setting('habilitar_votos'), true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Habilitar comentarios', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="habilitar_comentarios" value="1" ' . checked($this->get_setting('habilitar_comentarios'), true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Moderación comentarios', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="moderacion_comentarios" value="1" ' . checked($this->get_setting('moderacion_comentarios'), true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Token Mapbox', 'flavor-chat-ia') . '</th><td><input type="text" name="mapbox_token" class="regular-text" value="' . esc_attr($this->get_setting('mapbox_token')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Centro mapa (lat, lng)', 'flavor-chat-ia') . '</th><td>';
        echo '<input type="number" step="0.000001" name="centro_mapa_lat" value="' . esc_attr($this->get_setting('centro_mapa_lat')) . '" style="width:140px;"> ';
        echo '<input type="number" step="0.000001" name="centro_mapa_lng" value="' . esc_attr($this->get_setting('centro_mapa_lng')) . '" style="width:140px;">';
        echo '</td></tr>';
        echo '<tr><th>' . esc_html__('Zoom mapa', 'flavor-chat-ia') . '</th><td><input type="number" name="zoom_mapa" min="1" max="20" value="' . esc_attr($this->get_setting('zoom_mapa')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Categorías', 'flavor-chat-ia') . '</th><td>';
        echo '<textarea name="categorias" rows="6" class="large-text" placeholder="alumbrado|Alumbrado público">' . esc_textarea($format_lines($categorias)) . '</textarea>';
        echo '<p class="description">' . esc_html__('Una por línea en formato clave|Etiqueta.', 'flavor-chat-ia') . '</p></td></tr>';
        echo '<tr><th>' . esc_html__('Prioridades', 'flavor-chat-ia') . '</th><td>';
        echo '<textarea name="prioridades" rows="4" class="large-text" placeholder="baja|Baja">' . esc_textarea($format_lines($prioridades)) . '</textarea>';
        echo '</td></tr>';
        echo '<tr><th>' . esc_html__('Estados', 'flavor-chat-ia') . '</th><td>';
        echo '<textarea name="estados" rows="6" class="large-text" placeholder="pendiente|Pendiente">' . esc_textarea($format_lines($estados)) . '</textarea>';
        echo '</td></tr>';
        echo '<tr><th>' . esc_html__('Departamentos', 'flavor-chat-ia') . '</th><td>';
        echo '<textarea name="departamentos" rows="4" class="large-text" placeholder="obras|Obras y Servicios">' . esc_textarea($format_lines($departamentos)) . '</textarea>';
        echo '</td></tr>';
        echo '</tbody></table>';
        submit_button(__('Guardar configuración', 'flavor-chat-ia'));
        echo '</form>';
        echo '</div>';
    }

    private function render_incidencias_resumen() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';
        $incidencias = $wpdb->get_results(
            "SELECT id, numero_incidencia, titulo, categoria, estado, prioridad, fecha_reporte
             FROM $tabla
             ORDER BY fecha_reporte DESC
             LIMIT 10"
        );

        echo '<h3>' . esc_html__('Incidencias recientes', 'flavor-chat-ia') . '</h3>';
        if (empty($incidencias)) {
            echo '<p>' . esc_html__('No hay incidencias registradas aún.', 'flavor-chat-ia') . '</p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>ID</th><th>' . esc_html__('Número', 'flavor-chat-ia') . '</th><th>' . esc_html__('Título', 'flavor-chat-ia') . '</th><th>' . esc_html__('Estado', 'flavor-chat-ia') . '</th><th>' . esc_html__('Prioridad', 'flavor-chat-ia') . '</th><th>' . esc_html__('Fecha', 'flavor-chat-ia') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($incidencias as $incidencia) {
            echo '<tr>';
            echo '<td>' . esc_html($incidencia->id) . '</td>';
            echo '<td>' . esc_html($incidencia->numero_incidencia) . '</td>';
            echo '<td>' . esc_html($incidencia->titulo) . '</td>';
            echo '<td>' . esc_html($incidencia->estado) . '</td>';
            echo '<td>' . esc_html($incidencia->prioridad) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($incidencia->fecha_reporte))) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    private function render_admin_listado_incidencias($solo_abiertas) {
        echo '<div class="wrap flavor-modulo-page">';
        $titulo = $solo_abiertas ? __('Incidencias Abiertas', 'flavor-chat-ia') : __('Todas las Incidencias', 'flavor-chat-ia');
        $this->render_page_header($titulo, [
            ['label' => __('Ver Mapa', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=incidencias-mapa'), 'class' => ''],
        ]);
        $this->handle_admin_actions();

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', 'flavor-chat-ia') . '</p></div>';
            echo '</div>';
            return;
        }

        $config = $this->get_settings();
        $estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $categoria = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
        $prioridad = isset($_GET['prioridad']) ? sanitize_text_field($_GET['prioridad']) : '';
        $busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $estados_disponibles = array_keys($config['estados'] ?? []);
        $categorias_disponibles = array_keys($config['categorias'] ?? []);
        $prioridades_disponibles = array_keys($config['prioridades'] ?? []);

        if ($solo_abiertas && !$estado) {
            $estado = 'abiertas';
        }

        echo '<form method="get" style="margin: 12px 0;">';
        echo '<input type="hidden" name="page" value="' . esc_attr($solo_abiertas ? 'incidencias-abiertas' : 'incidencias-todas') . '">';
        echo '<select name="estado">';
        echo '<option value="">' . esc_html__('Todos los estados', 'flavor-chat-ia') . '</option>';
        if ($solo_abiertas) {
            echo '<option value="abiertas" ' . selected($estado, 'abiertas', false) . '>' . esc_html__('Solo abiertas', 'flavor-chat-ia') . '</option>';
        }
        foreach ($estados_disponibles as $key) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($estado, $key, false) . '>' . esc_html($config['estados'][$key] ?? $key) . '</option>';
        }
        echo '</select> ';
        echo '<select name="categoria">';
        echo '<option value="">' . esc_html__('Todas las categorías', 'flavor-chat-ia') . '</option>';
        foreach ($categorias_disponibles as $key) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($categoria, $key, false) . '>' . esc_html($config['categorias'][$key] ?? $key) . '</option>';
        }
        echo '</select> ';
        echo '<select name="prioridad">';
        echo '<option value="">' . esc_html__('Todas las prioridades', 'flavor-chat-ia') . '</option>';
        foreach ($prioridades_disponibles as $key) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($prioridad, $key, false) . '>' . esc_html($config['prioridades'][$key] ?? $key) . '</option>';
        }
        echo '</select> ';
        echo '<input type="search" name="s" placeholder="' . esc_attr__('Buscar por título o número', 'flavor-chat-ia') . '" value="' . esc_attr($busqueda) . '"> ';
        echo '<button class="button">' . esc_html__('Filtrar', 'flavor-chat-ia') . '</button>';
        echo '</form>';

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';
        $where = [];
        $params = [];

        if ($estado === 'abiertas') {
            $where[] = "estado IN ('pendiente','validada','en_proceso')";
        } elseif ($estado && in_array($estado, $estados_disponibles, true)) {
            $where[] = 'estado = %s';
            $params[] = $estado;
        } elseif ($solo_abiertas) {
            $where[] = "estado IN ('pendiente','validada','en_proceso')";
        }

        if ($categoria && in_array($categoria, $categorias_disponibles, true)) {
            $where[] = 'categoria = %s';
            $params[] = $categoria;
        }
        if ($prioridad && in_array($prioridad, $prioridades_disponibles, true)) {
            $where[] = 'prioridad = %s';
            $params[] = $prioridad;
        }
        if ($busqueda) {
            $where[] = '(titulo LIKE %s OR numero_incidencia LIKE %s)';
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT id, numero_incidencia, titulo, categoria, prioridad, estado, votos_ciudadanos, fecha_reporte FROM $tabla";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY fecha_reporte DESC LIMIT 200';

        $incidencias = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);

        if (empty($incidencias)) {
            echo '<p>' . esc_html__('No hay incidencias con esos filtros.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>ID</th>';
        echo '<th>' . esc_html__('Número', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Título', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Categoría', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Prioridad', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Estado', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Votos', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Fecha', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Acciones', 'flavor-chat-ia') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($incidencias as $incidencia) {
            echo '<tr>';
            echo '<td>' . esc_html($incidencia->id) . '</td>';
            echo '<td>' . esc_html($incidencia->numero_incidencia) . '</td>';
            echo '<td>' . esc_html($incidencia->titulo) . '</td>';
            echo '<td>' . esc_html($config['categorias'][$incidencia->categoria] ?? $incidencia->categoria) . '</td>';
            echo '<td>' . esc_html($config['prioridades'][$incidencia->prioridad] ?? $incidencia->prioridad) . '</td>';
            echo '<td>' . esc_html($config['estados'][$incidencia->estado] ?? $incidencia->estado) . '</td>';
            echo '<td>' . esc_html($incidencia->votos_ciudadanos) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($incidencia->fecha_reporte))) . '</td>';
            echo '<td>' . $this->render_incidencia_actions($incidencia->id, $incidencia->estado, $incidencia->prioridad, $solo_abiertas ? 'incidencias-abiertas' : 'incidencias-todas') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    private function render_incidencia_actions($incidencia_id, $estado_actual, $prioridad_actual, $page_slug) {
        $config = $this->get_settings();
        $estados = array_keys($config['estados'] ?? []);
        $prioridades = array_keys($config['prioridades'] ?? []);

        $links = [];
        foreach ($estados as $estado) {
            if ($estado === $estado_actual) {
                continue;
            }
            $url = add_query_arg([
                'page' => $page_slug,
                'incidencia_action' => 'estado',
                'incidencia_id' => $incidencia_id,
                'estado' => $estado,
            ], admin_url('admin.php'));
            $url = wp_nonce_url($url, 'incidencias_admin_' . $incidencia_id);
            $links[] = '<a href="' . esc_url($url) . '">' . esc_html($config['estados'][$estado] ?? $estado) . '</a>';
            if (count($links) >= 3) {
                break;
            }
        }

        $prio_links = [];
        foreach ($prioridades as $prioridad) {
            if ($prioridad === $prioridad_actual) {
                continue;
            }
            $url = add_query_arg([
                'page' => $page_slug,
                'incidencia_action' => 'prioridad',
                'incidencia_id' => $incidencia_id,
                'prioridad' => $prioridad,
            ], admin_url('admin.php'));
            $url = wp_nonce_url($url, 'incidencias_admin_' . $incidencia_id);
            $prio_links[] = '<a href="' . esc_url($url) . '">' . esc_html($config['prioridades'][$prioridad] ?? $prioridad) . '</a>';
            if (count($prio_links) >= 2) {
                break;
            }
        }

        $output = '';
        if (!empty($links)) {
            $output .= '<div><strong>' . esc_html__('Estado:', 'flavor-chat-ia') . '</strong> ' . implode(' | ', $links) . '</div>';
        }
        if (!empty($prio_links)) {
            $output .= '<div><strong>' . esc_html__('Prioridad:', 'flavor-chat-ia') . '</strong> ' . implode(' | ', $prio_links) . '</div>';
        }
        return $output;
    }

    private function handle_admin_actions() {
        if (empty($_GET['incidencia_action']) || empty($_GET['incidencia_id'])) {
            return;
        }

        $accion = sanitize_text_field($_GET['incidencia_action']);
        $incidencia_id = absint($_GET['incidencia_id']);
        $nonce = $_GET['_wpnonce'] ?? '';

        if (!$incidencia_id) {
            return;
        }
        if (!wp_verify_nonce($nonce, 'incidencias_admin_' . $incidencia_id)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';
        $config = $this->get_settings();

        if ($accion === 'estado') {
            $nuevo_estado = sanitize_text_field($_GET['estado'] ?? '');
            if (empty($nuevo_estado) || empty($config['estados'][$nuevo_estado])) {
                return;
            }
            $data = ['estado' => $nuevo_estado];
            $format = ['%s'];
            if ($nuevo_estado === 'resuelta') {
                $data['fecha_resolucion'] = current_time('mysql');
                $format[] = '%s';
            } elseif (in_array($nuevo_estado, ['cerrada', 'rechazada'], true)) {
                $data['fecha_cierre'] = current_time('mysql');
                $format[] = '%s';
            }
            $wpdb->update($tabla, $data, ['id' => $incidencia_id], $format, ['%d']);
            echo '<div class="notice notice-success"><p>' . esc_html__('Estado actualizado.', 'flavor-chat-ia') . '</p></div>';
        }

        if ($accion === 'prioridad') {
            $nueva_prioridad = sanitize_text_field($_GET['prioridad'] ?? '');
            if (empty($nueva_prioridad) || empty($config['prioridades'][$nueva_prioridad])) {
                return;
            }
            $wpdb->update($tabla, ['prioridad' => $nueva_prioridad], ['id' => $incidencia_id], ['%s'], ['%d']);
            echo '<div class="notice notice-success"><p>' . esc_html__('Prioridad actualizada.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    private function handle_admin_save_config() {
        if (empty($_POST['incidencias_config_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['incidencias_config_nonce'], 'incidencias_config')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $this->update_setting('requiere_validacion', !empty($_POST['requiere_validacion']));
        $this->update_setting('permite_anonimo', !empty($_POST['permite_anonimo']));
        $this->update_setting('tiempo_respuesta_objetivo', absint($_POST['tiempo_respuesta_objetivo'] ?? 48));
        $this->update_setting('notificar_actualizaciones', !empty($_POST['notificar_actualizaciones']));
        $this->update_setting('notificar_dias_antes', absint($_POST['notificar_dias_antes'] ?? 2));
        $this->update_setting('max_fotos_por_incidencia', absint($_POST['max_fotos_por_incidencia'] ?? 5));
        $this->update_setting('tamano_max_foto', absint($_POST['tamano_max_foto'] ?? 5));
        $this->update_setting('habilitar_votos', !empty($_POST['habilitar_votos']));
        $this->update_setting('habilitar_comentarios', !empty($_POST['habilitar_comentarios']));
        $this->update_setting('moderacion_comentarios', !empty($_POST['moderacion_comentarios']));
        $this->update_setting('mapbox_token', sanitize_text_field($_POST['mapbox_token'] ?? ''));
        $this->update_setting('centro_mapa_lat', floatval($_POST['centro_mapa_lat'] ?? 0));
        $this->update_setting('centro_mapa_lng', floatval($_POST['centro_mapa_lng'] ?? 0));
        $this->update_setting('zoom_mapa', absint($_POST['zoom_mapa'] ?? 14));

        $this->update_setting('categorias', $this->parse_config_lines($_POST['categorias'] ?? ''));
        $this->update_setting('prioridades', $this->parse_config_lines($_POST['prioridades'] ?? ''));
        $this->update_setting('estados', $this->parse_config_lines($_POST['estados'] ?? ''));
        $this->update_setting('departamentos', $this->parse_config_lines($_POST['departamentos'] ?? ''));

        echo '<div class="notice notice-success"><p>' . esc_html__('Configuración guardada.', 'flavor-chat-ia') . '</p></div>';
    }

    private function parse_config_lines($raw) {
        $raw = is_string($raw) ? $raw : '';
        $items = [];
        foreach (array_filter(array_map('trim', explode("\n", $raw))) as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            if (!empty($parts[0])) {
                $items[$parts[0]] = $parts[1] ?? $parts[0];
            }
        }
        return $items;
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('incidencias_reportar', [$this, 'shortcode_reportar']);
        add_shortcode('incidencias_mapa', [$this, 'shortcode_mapa']);
        add_shortcode('incidencias_listado', [$this, 'shortcode_listado']);
        add_shortcode('incidencias_mis_incidencias', [$this, 'shortcode_mis_incidencias']);
        add_shortcode('incidencias_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('incidencias_estadisticas', [$this, 'shortcode_estadisticas']);
    }

    /**
     * Encolar assets del frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->can_activate()) {
            return;
        }

        if (!$this->should_load_assets()) {
            return;
        }

        $ruta_modulo = plugin_dir_url(__FILE__);
        $version_modulo = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.0.0';

        // CSS
        wp_enqueue_style(
            'flavor-incidencias-frontend',
            $ruta_modulo . 'assets/css/incidencias-frontend.css',
            [],
            $version_modulo
        );

        // Leaflet CSS
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        // Leaflet JS
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        // JS del módulo
        wp_enqueue_script(
            'flavor-incidencias-frontend',
            $ruta_modulo . 'assets/js/incidencias-frontend.js',
            ['jquery', 'leaflet'],
            $version_modulo,
            true
        );

        // Configuración JS
        $configuracion_js = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('incidencias_nonce'),
            'mapboxToken' => $this->get_setting('mapbox_token'),
            'defaultLat' => floatval($this->get_setting('centro_mapa_lat')),
            'defaultLng' => floatval($this->get_setting('centro_mapa_lng')),
            'defaultZoom' => intval($this->get_setting('zoom_mapa')),
            'maxFotos' => intval($this->get_setting('max_fotos_por_incidencia')),
            'maxFileSize' => intval($this->get_setting('tamano_max_foto')) * 1024 * 1024,
            'i18n' => [
                'errorGenerico' => __('Ha ocurrido un error. Por favor, intenta de nuevo.', 'flavor-chat-ia'),
                'confirmDelete' => __('Esta acción no se puede deshacer.', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'enviando' => __('Enviando...', 'flavor-chat-ia'),
                'ubicacionObtenida' => __('Ubicación obtenida correctamente', 'flavor-chat-ia'),
                'errorUbicacion' => __('No se pudo obtener la ubicación', 'flavor-chat-ia'),
                'archivoGrande' => __('El archivo es demasiado grande', 'flavor-chat-ia'),
                'tipoNoPermitido' => __('Tipo de archivo no permitido', 'flavor-chat-ia'),
                'maxFotosAlcanzado' => __('Número máximo de fotos alcanzado', 'flavor-chat-ia'),
            ],
        ];

        wp_localize_script('flavor-incidencias-frontend', 'flavorIncidenciasConfig', $configuracion_js);
    }

    /**
     * Verificar si cargar assets
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'incidencias_reportar',
            'incidencias_mapa',
            'incidencias_listado',
            'incidencias_mis_incidencias',
            'incidencias_detalle',
            'incidencias_estadisticas'
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_seguimiento = $wpdb->prefix . 'flavor_incidencias_seguimiento';
        $tabla_categorias = $wpdb->prefix . 'flavor_incidencias_categorias';
        $tabla_asignaciones = $wpdb->prefix . 'flavor_incidencias_asignaciones';
        $tabla_fotos = $wpdb->prefix . 'flavor_incidencias_fotos';
        $tabla_votos = $wpdb->prefix . 'flavor_incidencias_votos';
        $tabla_estadisticas = $wpdb->prefix . 'flavor_incidencias_estadisticas';

        // Tabla principal de incidencias
        $sql_incidencias = "CREATE TABLE IF NOT EXISTS $tabla_incidencias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            numero_incidencia varchar(50) NOT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            email_reportante varchar(255) DEFAULT NULL,
            nombre_reportante varchar(255) DEFAULT NULL,
            telefono_reportante varchar(50) DEFAULT NULL,
            categoria varchar(50) NOT NULL,
            subcategoria varchar(50) DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            direccion varchar(500) DEFAULT NULL,
            codigo_postal varchar(10) DEFAULT NULL,
            barrio varchar(100) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            prioridad enum('baja','media','alta','urgente') DEFAULT 'media',
            estado enum('pendiente','validada','en_proceso','resuelta','cerrada','rechazada') DEFAULT 'pendiente',
            departamento varchar(100) DEFAULT NULL,
            asignado_a bigint(20) unsigned DEFAULT NULL,
            votos_ciudadanos int(11) DEFAULT 0,
            visibilidad enum('publica','privada') DEFAULT 'publica',
            notas_internas text DEFAULT NULL,
            motivo_rechazo text DEFAULT NULL,
            resolucion text DEFAULT NULL,
            tiempo_estimado_resolucion int(11) DEFAULT NULL,
            fecha_reporte datetime NOT NULL,
            fecha_validacion datetime DEFAULT NULL,
            fecha_asignacion datetime DEFAULT NULL,
            fecha_inicio_trabajo datetime DEFAULT NULL,
            fecha_resolucion datetime DEFAULT NULL,
            fecha_cierre datetime DEFAULT NULL,
            ip_reportante varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY numero_incidencia (numero_incidencia),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY prioridad (prioridad),
            KEY departamento (departamento),
            KEY asignado_a (asignado_a),
            KEY fecha_reporte (fecha_reporte),
            KEY coordenadas (latitud, longitud),
            FULLTEXT KEY busqueda (titulo, descripcion, direccion)
        ) $charset_collate;";

        // Tabla de seguimiento/historial
        $sql_seguimiento = "CREATE TABLE IF NOT EXISTS $tabla_seguimiento (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            incidencia_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            tipo enum('comentario','cambio_estado','asignacion','nota_interna','resolucion','reapertura') NOT NULL,
            contenido text NOT NULL,
            estado_anterior varchar(50) DEFAULT NULL,
            estado_nuevo varchar(50) DEFAULT NULL,
            es_publico tinyint(1) DEFAULT 1,
            adjuntos text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY incidencia_id (incidencia_id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo),
            KEY es_publico (es_publico),
            KEY fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        // Tabla de categorías personalizadas
        $sql_categorias = "CREATE TABLE IF NOT EXISTS $tabla_categorias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(50) NOT NULL,
            nombre varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            icono varchar(50) DEFAULT NULL,
            color varchar(7) DEFAULT NULL,
            departamento_defecto varchar(100) DEFAULT NULL,
            prioridad_defecto enum('baja','media','alta','urgente') DEFAULT 'media',
            tiempo_resolucion_objetivo int(11) DEFAULT 48,
            activa tinyint(1) DEFAULT 1,
            orden int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY activa (activa),
            KEY orden (orden)
        ) $charset_collate;";

        // Tabla de asignaciones/responsables
        $sql_asignaciones = "CREATE TABLE IF NOT EXISTS $tabla_asignaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            incidencia_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            asignado_por bigint(20) unsigned DEFAULT NULL,
            rol enum('responsable','colaborador','supervisor') DEFAULT 'responsable',
            departamento varchar(100) DEFAULT NULL,
            notas text DEFAULT NULL,
            estado enum('activa','completada','reasignada') DEFAULT 'activa',
            fecha_asignacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY incidencia_id (incidencia_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Tabla de fotos
        $sql_fotos = "CREATE TABLE IF NOT EXISTS $tabla_fotos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            incidencia_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            attachment_id bigint(20) unsigned DEFAULT NULL,
            url varchar(500) NOT NULL,
            url_thumbnail varchar(500) DEFAULT NULL,
            nombre_archivo varchar(255) DEFAULT NULL,
            tipo_mime varchar(100) DEFAULT NULL,
            tamano int(11) DEFAULT NULL,
            es_principal tinyint(1) DEFAULT 0,
            descripcion text DEFAULT NULL,
            metadatos text DEFAULT NULL,
            fecha_subida datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY incidencia_id (incidencia_id),
            KEY usuario_id (usuario_id),
            KEY es_principal (es_principal)
        ) $charset_collate;";

        // Tabla de votos
        $sql_votos = "CREATE TABLE IF NOT EXISTS $tabla_votos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            incidencia_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            ip_votante varchar(45) DEFAULT NULL,
            fecha_voto datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY voto_unico (incidencia_id, usuario_id),
            KEY incidencia_id (incidencia_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Tabla de estadísticas diarias
        $sql_estadisticas = "CREATE TABLE IF NOT EXISTS $tabla_estadisticas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            fecha date NOT NULL,
            categoria varchar(50) DEFAULT NULL,
            departamento varchar(100) DEFAULT NULL,
            total_reportadas int(11) DEFAULT 0,
            total_resueltas int(11) DEFAULT 0,
            total_pendientes int(11) DEFAULT 0,
            total_en_proceso int(11) DEFAULT 0,
            tiempo_medio_resolucion decimal(10,2) DEFAULT NULL,
            satisfaccion_media decimal(3,2) DEFAULT NULL,
            datos_extra text DEFAULT NULL,
            fecha_calculo datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY fecha_cat_dept (fecha, categoria, departamento),
            KEY fecha (fecha),
            KEY categoria (categoria),
            KEY departamento (departamento)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_incidencias);
        dbDelta($sql_seguimiento);
        dbDelta($sql_categorias);
        dbDelta($sql_asignaciones);
        dbDelta($sql_fotos);
        dbDelta($sql_votos);
        dbDelta($sql_estadisticas);

        // Insertar categorías por defecto
        $this->insertar_categorias_defecto();

        update_option('flavor_incidencias_db_version', self::DB_VERSION);
    }

    /**
     * Insertar categorías por defecto
     */
    private function insertar_categorias_defecto() {
        global $wpdb;
        $tabla_categorias = $wpdb->prefix . 'flavor_incidencias_categorias';

        $categorias_defecto = [
            ['slug' => 'alumbrado', 'nombre' => 'Alumbrado público', 'icono' => 'lightbulb', 'color' => '#fbbf24', 'departamento_defecto' => 'obras', 'orden' => 1],
            ['slug' => 'limpieza', 'nombre' => 'Limpieza y residuos', 'icono' => 'trash', 'color' => '#34d399', 'departamento_defecto' => 'medio_ambiente', 'orden' => 2],
            ['slug' => 'via_publica', 'nombre' => 'Vía pública', 'icono' => 'road', 'color' => '#f87171', 'departamento_defecto' => 'obras', 'orden' => 3],
            ['slug' => 'mobiliario', 'nombre' => 'Mobiliario urbano', 'icono' => 'bench', 'color' => '#60a5fa', 'departamento_defecto' => 'obras', 'orden' => 4],
            ['slug' => 'parques', 'nombre' => 'Parques y jardines', 'icono' => 'tree', 'color' => '#a3e635', 'departamento_defecto' => 'medio_ambiente', 'orden' => 5],
            ['slug' => 'ruido', 'nombre' => 'Ruidos y molestias', 'icono' => 'volume', 'color' => '#c084fc', 'departamento_defecto' => 'seguridad', 'orden' => 6],
            ['slug' => 'agua', 'nombre' => 'Agua y alcantarillado', 'icono' => 'droplet', 'color' => '#38bdf8', 'departamento_defecto' => 'obras', 'orden' => 7],
            ['slug' => 'senalizacion', 'nombre' => 'Señalización', 'icono' => 'sign', 'color' => '#fb923c', 'departamento_defecto' => 'trafico', 'orden' => 8],
            ['slug' => 'accesibilidad', 'nombre' => 'Accesibilidad', 'icono' => 'wheelchair', 'color' => '#818cf8', 'departamento_defecto' => 'urbanismo', 'orden' => 9],
            ['slug' => 'otros', 'nombre' => 'Otros', 'icono' => 'more', 'color' => '#94a3b8', 'departamento_defecto' => 'obras', 'orden' => 99],
        ];

        foreach ($categorias_defecto as $categoria) {
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_categorias WHERE slug = %s",
                $categoria['slug']
            ));

            if (!$existe) {
                $wpdb->insert($tabla_categorias, $categoria);
            }
        }
    }

    /**
     * Genera número de incidencia único
     */
    private function generar_numero_incidencia() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $anio = date('Y');
        $mes = date('m');
        $prefijo = 'INC-' . $anio . $mes . '-';

        $ultimo = $wpdb->get_var($wpdb->prepare(
            "SELECT numero_incidencia FROM $tabla_incidencias WHERE numero_incidencia LIKE %s ORDER BY id DESC LIMIT 1",
            $prefijo . '%'
        ));

        if ($ultimo) {
            preg_match('/INC-\d+-(\d+)/', $ultimo, $matches);
            $numero = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $numero = 1;
        }

        return sprintf('%s%05d', $prefijo, $numero);
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Reportar incidencia
     */
    public function ajax_reportar_incidencia() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        $configuracion = $this->get_settings();

        // Validar datos obligatorios
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        if (empty($categoria) || empty($titulo) || empty($descripcion)) {
            wp_send_json_error(['error' => __('Categoría, título y descripción son obligatorios.', 'flavor-chat-ia')]);
        }

        // Datos opcionales
        $direccion = sanitize_text_field($_POST['direccion'] ?? '');
        $latitud = !empty($_POST['latitud']) ? floatval($_POST['latitud']) : null;
        $longitud = !empty($_POST['longitud']) ? floatval($_POST['longitud']) : null;
        $email_reportante = sanitize_email($_POST['email'] ?? '');
        $nombre_reportante = sanitize_text_field($_POST['nombre'] ?? '');
        $telefono_reportante = sanitize_text_field($_POST['telefono'] ?? '');

        // Si no está logueado, verificar datos de contacto
        if (!$usuario_id && !$configuracion['permite_anonimo']) {
            if (empty($email_reportante)) {
                wp_send_json_error(['error' => __('Se requiere un email de contacto.', 'flavor-chat-ia')]);
            }
        }

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $numero_incidencia = $this->generar_numero_incidencia();
        $estado_inicial = $configuracion['requiere_validacion'] ? 'pendiente' : 'validada';

        $datos_insertar = [
            'numero_incidencia' => $numero_incidencia,
            'usuario_id' => $usuario_id ?: null,
            'email_reportante' => $email_reportante ?: null,
            'nombre_reportante' => $nombre_reportante ?: null,
            'telefono_reportante' => $telefono_reportante ?: null,
            'categoria' => $categoria,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'direccion' => $direccion,
            'latitud' => $latitud,
            'longitud' => $longitud,
            'prioridad' => 'media',
            'estado' => $estado_inicial,
            'fecha_reporte' => current_time('mysql'),
            'visibilidad' => 'publica',
            'ip_reportante' => $this->obtener_ip_cliente(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ];

        $resultado = $wpdb->insert($tabla_incidencias, $datos_insertar);

        if ($resultado === false) {
            wp_send_json_error(['error' => __('Error al registrar la incidencia.', 'flavor-chat-ia')]);
        }

        $incidencia_id = $wpdb->insert_id;

        // Procesar fotos si las hay
        if (!empty($_FILES['fotos'])) {
            $this->procesar_fotos_subidas($incidencia_id, $_FILES['fotos']);
        }

        // Registrar en seguimiento
        $this->agregar_seguimiento($incidencia_id, $usuario_id, 'comentario', __('Incidencia reportada', 'flavor-chat-ia'), true);

        // Enviar notificación al admin
        $this->notificar_nueva_incidencia($incidencia_id);

        // Hook para sistema de reputación
        do_action('flavor_incidencia_reportada', $usuario_id, $incidencia_id);

        wp_send_json_success([
            'incidencia_id' => $incidencia_id,
            'numero_incidencia' => $numero_incidencia,
            'mensaje' => sprintf(
                __('Incidencia reportada con éxito. Número de seguimiento: %s', 'flavor-chat-ia'),
                $numero_incidencia
            ),
            'redirect_url' => add_query_arg('incidencia', $numero_incidencia, get_permalink()),
        ]);
    }

    /**
     * AJAX: Listar incidencias
     */
    public function ajax_listar_incidencias() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_fotos = $wpdb->prefix . 'flavor_incidencias_fotos';

        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $estado = sanitize_text_field($_POST['estado'] ?? '');
        $prioridad = sanitize_text_field($_POST['prioridad'] ?? '');
        $busqueda = sanitize_text_field($_POST['busqueda'] ?? '');
        $pagina = absint($_POST['pagina'] ?? 1);
        $por_pagina = 12;
        $offset = ($pagina - 1) * $por_pagina;

        $where = ["visibilidad = 'publica'"];
        $valores_preparar = [];

        if (!empty($categoria)) {
            $where[] = 'categoria = %s';
            $valores_preparar[] = $categoria;
        }

        if (!empty($estado)) {
            $where[] = 'estado = %s';
            $valores_preparar[] = $estado;
        }

        if (!empty($prioridad)) {
            $where[] = 'prioridad = %s';
            $valores_preparar[] = $prioridad;
        }

        if (!empty($busqueda)) {
            $where[] = "(titulo LIKE %s OR descripcion LIKE %s OR direccion LIKE %s)";
            $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
            $valores_preparar[] = $busqueda_like;
            $valores_preparar[] = $busqueda_like;
            $valores_preparar[] = $busqueda_like;
        }

        $sql_where = implode(' AND ', $where);

        // Contar total
        $sql_count = "SELECT COUNT(*) FROM $tabla_incidencias WHERE $sql_where";
        if (!empty($valores_preparar)) {
            $total = $wpdb->get_var($wpdb->prepare($sql_count, ...$valores_preparar));
        } else {
            $total = $wpdb->get_var($sql_count);
        }

        // Obtener incidencias
        $sql = "SELECT i.*,
                (SELECT url_thumbnail FROM $tabla_fotos WHERE incidencia_id = i.id AND es_principal = 1 LIMIT 1) as imagen_principal
                FROM $tabla_incidencias i
                WHERE $sql_where
                ORDER BY fecha_reporte DESC
                LIMIT %d OFFSET %d";

        $valores_preparar[] = $por_pagina;
        $valores_preparar[] = $offset;

        $incidencias = $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparar));

        $configuracion = $this->get_settings();
        $datos_incidencias = [];

        foreach ($incidencias as $incidencia) {
            $datos_incidencias[] = [
                'id' => $incidencia->id,
                'numero' => $incidencia->numero_incidencia,
                'titulo' => $incidencia->titulo,
                'descripcion_corta' => wp_trim_words($incidencia->descripcion, 20),
                'categoria' => $incidencia->categoria,
                'categoria_texto' => $configuracion['categorias'][$incidencia->categoria] ?? $incidencia->categoria,
                'estado' => $incidencia->estado,
                'estado_texto' => $configuracion['estados'][$incidencia->estado] ?? $incidencia->estado,
                'prioridad' => $incidencia->prioridad,
                'direccion' => $incidencia->direccion,
                'fecha' => date_i18n('d/m/Y', strtotime($incidencia->fecha_reporte)),
                'votos' => intval($incidencia->votos_ciudadanos),
                'imagen_principal' => $incidencia->imagen_principal ?: '',
                'url_detalle' => add_query_arg('incidencia', $incidencia->numero_incidencia, get_permalink()),
            ];
        }

        wp_send_json_success([
            'incidencias' => $datos_incidencias,
            'total' => intval($total),
            'pagina_actual' => $pagina,
            'total_paginas' => ceil($total / $por_pagina),
        ]);
    }

    /**
     * AJAX: Obtener incidencias para el mapa
     */
    public function ajax_obtener_mapa() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $estado = sanitize_text_field($_POST['estado'] ?? '');

        $where = ["visibilidad = 'publica'", "latitud IS NOT NULL", "longitud IS NOT NULL"];
        $valores_preparar = [];

        if (!empty($categoria)) {
            $where[] = 'categoria = %s';
            $valores_preparar[] = $categoria;
        }

        if (!empty($estado)) {
            $where[] = 'estado = %s';
            $valores_preparar[] = $estado;
        }

        $sql_where = implode(' AND ', $where);
        $sql = "SELECT id, numero_incidencia, titulo, categoria, estado, prioridad, direccion, latitud, longitud, fecha_reporte
                FROM $tabla_incidencias
                WHERE $sql_where
                ORDER BY fecha_reporte DESC
                LIMIT 500";

        if (!empty($valores_preparar)) {
            $incidencias = $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparar));
        } else {
            $incidencias = $wpdb->get_results($sql);
        }

        $configuracion = $this->get_settings();
        $datos_mapa = [];

        foreach ($incidencias as $incidencia) {
            $datos_mapa[] = [
                'id' => $incidencia->id,
                'titulo' => $incidencia->titulo,
                'categoria' => $incidencia->categoria,
                'categoria_texto' => $configuracion['categorias'][$incidencia->categoria] ?? $incidencia->categoria,
                'estado' => $incidencia->estado,
                'estado_texto' => $configuracion['estados'][$incidencia->estado] ?? $incidencia->estado,
                'prioridad' => $incidencia->prioridad,
                'direccion' => $incidencia->direccion,
                'latitud' => floatval($incidencia->latitud),
                'longitud' => floatval($incidencia->longitud),
                'fecha' => date_i18n('d/m/Y', strtotime($incidencia->fecha_reporte)),
                'url_detalle' => add_query_arg('incidencia', $incidencia->numero_incidencia, get_permalink()),
            ];
        }

        wp_send_json_success(['incidencias' => $datos_mapa]);
    }

    /**
     * AJAX: Detalle de incidencia
     */
    public function ajax_detalle_incidencia() {
        $incidencia_id = absint($_POST['incidencia_id'] ?? 0);
        $numero_incidencia = sanitize_text_field($_POST['numero'] ?? '');

        if (!$incidencia_id && empty($numero_incidencia)) {
            wp_send_json_error(['error' => __('ID de incidencia inválido.', 'flavor-chat-ia')]);
        }

        $incidencia = $this->obtener_incidencia($incidencia_id, $numero_incidencia);

        if (!$incidencia) {
            wp_send_json_error(['error' => __('Incidencia no encontrada.', 'flavor-chat-ia')]);
        }

        // Obtener fotos
        $fotos = $this->obtener_fotos_incidencia($incidencia->id);

        // Obtener seguimiento público
        $seguimiento = $this->obtener_seguimiento_incidencia($incidencia->id, true);

        $configuracion = $this->get_settings();

        wp_send_json_success([
            'incidencia' => [
                'id' => $incidencia->id,
                'numero' => $incidencia->numero_incidencia,
                'titulo' => $incidencia->titulo,
                'descripcion' => $incidencia->descripcion,
                'categoria' => $incidencia->categoria,
                'categoria_texto' => $configuracion['categorias'][$incidencia->categoria] ?? $incidencia->categoria,
                'estado' => $incidencia->estado,
                'estado_texto' => $configuracion['estados'][$incidencia->estado] ?? $incidencia->estado,
                'prioridad' => $incidencia->prioridad,
                'prioridad_texto' => $configuracion['prioridades'][$incidencia->prioridad] ?? $incidencia->prioridad,
                'direccion' => $incidencia->direccion,
                'latitud' => floatval($incidencia->latitud),
                'longitud' => floatval($incidencia->longitud),
                'votos' => intval($incidencia->votos_ciudadanos),
                'fecha_reporte' => date_i18n('d/m/Y H:i', strtotime($incidencia->fecha_reporte)),
                'fecha_resolucion' => $incidencia->fecha_resolucion ? date_i18n('d/m/Y H:i', strtotime($incidencia->fecha_resolucion)) : null,
                'resolucion' => $incidencia->resolucion,
            ],
            'fotos' => $fotos,
            'seguimiento' => $seguimiento,
        ]);
    }

    /**
     * AJAX: Votar incidencia
     */
    public function ajax_votar_incidencia() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => __('Debes iniciar sesión para votar.', 'flavor-chat-ia')]);
        }

        $incidencia_id = absint($_POST['incidencia_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$incidencia_id) {
            wp_send_json_error(['error' => __('ID de incidencia inválido.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_incidencias_votos';
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        // Verificar si ya votó
        $voto_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_votos WHERE incidencia_id = %d AND usuario_id = %d",
            $incidencia_id,
            $usuario_id
        ));

        if ($voto_existente) {
            wp_send_json_error(['error' => __('Ya has votado esta incidencia.', 'flavor-chat-ia')]);
        }

        // Registrar voto
        $wpdb->insert($tabla_votos, [
            'incidencia_id' => $incidencia_id,
            'usuario_id' => $usuario_id,
            'ip_votante' => $this->obtener_ip_cliente(),
        ]);

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_incidencias SET votos_ciudadanos = votos_ciudadanos + 1 WHERE id = %d",
            $incidencia_id
        ));

        wp_send_json_success(['mensaje' => __('Voto registrado correctamente.', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Quitar voto
     */
    public function ajax_quitar_voto() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $incidencia_id = absint($_POST['incidencia_id'] ?? 0);
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_incidencias_votos';
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $eliminado = $wpdb->delete($tabla_votos, [
            'incidencia_id' => $incidencia_id,
            'usuario_id' => $usuario_id,
        ]);

        if ($eliminado) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_incidencias SET votos_ciudadanos = GREATEST(0, votos_ciudadanos - 1) WHERE id = %d",
                $incidencia_id
            ));
        }

        wp_send_json_success(['mensaje' => __('Voto eliminado.', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Comentar incidencia
     */
    public function ajax_comentar_incidencia() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => __('Debes iniciar sesión para comentar.', 'flavor-chat-ia')]);
        }

        $incidencia_id = absint($_POST['incidencia_id'] ?? 0);
        $contenido = sanitize_textarea_field($_POST['contenido'] ?? '');

        if (!$incidencia_id || empty($contenido)) {
            wp_send_json_error(['error' => __('Datos incompletos.', 'flavor-chat-ia')]);
        }

        $usuario_id = get_current_user_id();
        $usuario = get_userdata($usuario_id);

        $id_comentario = $this->agregar_seguimiento($incidencia_id, $usuario_id, 'comentario', $contenido, true);

        if (!$id_comentario) {
            wp_send_json_error(['error' => __('Error al guardar el comentario.', 'flavor-chat-ia')]);
        }

        wp_send_json_success([
            'comentario' => [
                'id' => $id_comentario,
                'autor' => $usuario->display_name,
                'contenido' => $contenido,
                'fecha' => date_i18n('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * AJAX: Cambiar estado (admin)
     */
    public function ajax_cambiar_estado() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos.', 'flavor-chat-ia')]);
        }

        $incidencia_id = absint($_POST['incidencia_id'] ?? 0);
        $nuevo_estado = sanitize_text_field($_POST['estado'] ?? '');
        $comentario = sanitize_textarea_field($_POST['comentario'] ?? '');

        $estados_validos = ['pendiente', 'validada', 'en_proceso', 'resuelta', 'cerrada', 'rechazada'];
        if (!in_array($nuevo_estado, $estados_validos)) {
            wp_send_json_error(['error' => __('Estado no válido.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $incidencia = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_incidencias WHERE id = %d",
            $incidencia_id
        ));

        if (!$incidencia) {
            wp_send_json_error(['error' => __('Incidencia no encontrada.', 'flavor-chat-ia')]);
        }

        $estado_anterior = $incidencia->estado;

        // Actualizar estado
        $datos_actualizar = ['estado' => $nuevo_estado];

        if ($nuevo_estado === 'validada' && !$incidencia->fecha_validacion) {
            $datos_actualizar['fecha_validacion'] = current_time('mysql');
        }
        if ($nuevo_estado === 'en_proceso' && !$incidencia->fecha_inicio_trabajo) {
            $datos_actualizar['fecha_inicio_trabajo'] = current_time('mysql');
        }
        if ($nuevo_estado === 'resuelta') {
            $datos_actualizar['fecha_resolucion'] = current_time('mysql');
            if (!empty($comentario)) {
                $datos_actualizar['resolucion'] = $comentario;
            }
        }
        if ($nuevo_estado === 'cerrada') {
            $datos_actualizar['fecha_cierre'] = current_time('mysql');
        }

        $wpdb->update($tabla_incidencias, $datos_actualizar, ['id' => $incidencia_id]);

        // Registrar cambio
        $mensaje_cambio = sprintf(
            __('Estado cambiado de "%s" a "%s"', 'flavor-chat-ia'),
            $estado_anterior,
            $nuevo_estado
        );
        if (!empty($comentario)) {
            $mensaje_cambio .= ': ' . $comentario;
        }

        $this->agregar_seguimiento(
            $incidencia_id,
            get_current_user_id(),
            'cambio_estado',
            $mensaje_cambio,
            true,
            $estado_anterior,
            $nuevo_estado
        );

        // Notificar al reportante
        $this->notificar_cambio_estado($incidencia_id, $estado_anterior, $nuevo_estado);

        $configuracion = $this->get_settings();

        wp_send_json_success([
            'mensaje' => __('Estado actualizado correctamente.', 'flavor-chat-ia'),
            'estado_texto' => $configuracion['estados'][$nuevo_estado] ?? $nuevo_estado,
        ]);
    }

    /**
     * AJAX: Cambiar prioridad (admin)
     */
    public function ajax_cambiar_prioridad() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos.', 'flavor-chat-ia')]);
        }

        $incidencia_id = absint($_POST['incidencia_id'] ?? 0);
        $nueva_prioridad = sanitize_text_field($_POST['prioridad'] ?? '');

        $prioridades_validas = ['baja', 'media', 'alta', 'urgente'];
        if (!in_array($nueva_prioridad, $prioridades_validas)) {
            wp_send_json_error(['error' => __('Prioridad no válida.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $wpdb->update($tabla_incidencias, ['prioridad' => $nueva_prioridad], ['id' => $incidencia_id]);

        wp_send_json_success(['mensaje' => __('Prioridad actualizada.', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Asignar responsable (admin)
     */
    public function ajax_asignar_responsable() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos.', 'flavor-chat-ia')]);
        }

        $incidencia_id = absint($_POST['incidencia_id'] ?? 0);
        $usuario_id = absint($_POST['usuario_id'] ?? 0);
        $departamento = sanitize_text_field($_POST['departamento'] ?? '');

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_asignaciones = $wpdb->prefix . 'flavor_incidencias_asignaciones';

        // Actualizar incidencia principal
        $datos_actualizar = [
            'asignado_a' => $usuario_id ?: null,
            'fecha_asignacion' => current_time('mysql'),
        ];
        if (!empty($departamento)) {
            $datos_actualizar['departamento'] = $departamento;
        }

        $wpdb->update($tabla_incidencias, $datos_actualizar, ['id' => $incidencia_id]);

        // Registrar asignación
        if ($usuario_id) {
            $wpdb->insert($tabla_asignaciones, [
                'incidencia_id' => $incidencia_id,
                'usuario_id' => $usuario_id,
                'asignado_por' => get_current_user_id(),
                'departamento' => $departamento,
            ]);

            $usuario_asignado = get_userdata($usuario_id);
            $this->agregar_seguimiento(
                $incidencia_id,
                get_current_user_id(),
                'asignacion',
                sprintf(__('Asignada a %s', 'flavor-chat-ia'), $usuario_asignado->display_name),
                true
            );
        }

        wp_send_json_success(['mensaje' => __('Responsable asignado correctamente.', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Eliminar incidencia (admin)
     */
    public function ajax_eliminar_incidencia() {
        check_ajax_referer('incidencias_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos.', 'flavor-chat-ia')]);
        }

        $incidencia_id = absint($_POST['incidencia_id'] ?? 0);

        if (!$incidencia_id) {
            wp_send_json_error(['error' => __('ID inválido.', 'flavor-chat-ia')]);
        }

        global $wpdb;

        // Eliminar registros relacionados
        $wpdb->delete($wpdb->prefix . 'flavor_incidencias_seguimiento', ['incidencia_id' => $incidencia_id]);
        $wpdb->delete($wpdb->prefix . 'flavor_incidencias_asignaciones', ['incidencia_id' => $incidencia_id]);
        $wpdb->delete($wpdb->prefix . 'flavor_incidencias_votos', ['incidencia_id' => $incidencia_id]);

        // Eliminar fotos
        $fotos = $wpdb->get_results($wpdb->prepare(
            "SELECT attachment_id FROM {$wpdb->prefix}flavor_incidencias_fotos WHERE incidencia_id = %d",
            $incidencia_id
        ));
        foreach ($fotos as $foto) {
            if ($foto->attachment_id) {
                wp_delete_attachment($foto->attachment_id, true);
            }
        }
        $wpdb->delete($wpdb->prefix . 'flavor_incidencias_fotos', ['incidencia_id' => $incidencia_id]);

        // Eliminar incidencia
        $wpdb->delete($wpdb->prefix . 'flavor_incidencias', ['id' => $incidencia_id]);

        wp_send_json_success(['mensaje' => __('Incidencia eliminada.', 'flavor-chat-ia')]);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Obtener incidencia por ID o número
     */
    private function obtener_incidencia($incidencia_id = 0, $numero_incidencia = '') {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        if ($incidencia_id) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_incidencias WHERE id = %d",
                $incidencia_id
            ));
        }

        if (!empty($numero_incidencia)) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_incidencias WHERE numero_incidencia = %s",
                $numero_incidencia
            ));
        }

        return null;
    }

    /**
     * Obtener fotos de incidencia
     */
    private function obtener_fotos_incidencia($incidencia_id) {
        global $wpdb;
        $tabla_fotos = $wpdb->prefix . 'flavor_incidencias_fotos';

        $fotos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_fotos WHERE incidencia_id = %d ORDER BY es_principal DESC, fecha_subida ASC",
            $incidencia_id
        ));

        return array_map(function($foto) {
            return [
                'id' => $foto->id,
                'url' => $foto->url,
                'url_thumbnail' => $foto->url_thumbnail ?: $foto->url,
                'es_principal' => (bool) $foto->es_principal,
                'descripcion' => $foto->descripcion,
            ];
        }, $fotos);
    }

    /**
     * Obtener seguimiento de incidencia
     */
    private function obtener_seguimiento_incidencia($incidencia_id, $solo_publico = true) {
        global $wpdb;
        $tabla_seguimiento = $wpdb->prefix . 'flavor_incidencias_seguimiento';

        $where = "incidencia_id = %d";
        if ($solo_publico) {
            $where .= " AND es_publico = 1";
        }

        $seguimientos = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name as autor_nombre
             FROM $tabla_seguimiento s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             WHERE $where
             ORDER BY fecha_creacion DESC",
            $incidencia_id
        ));

        return array_map(function($seguimiento) {
            return [
                'id' => $seguimiento->id,
                'tipo' => $seguimiento->tipo,
                'contenido' => $seguimiento->contenido,
                'autor' => $seguimiento->autor_nombre ?: __('Sistema', 'flavor-chat-ia'),
                'fecha' => date_i18n('d/m/Y H:i', strtotime($seguimiento->fecha_creacion)),
                'estado_anterior' => $seguimiento->estado_anterior,
                'estado_nuevo' => $seguimiento->estado_nuevo,
            ];
        }, $seguimientos);
    }

    /**
     * Agregar entrada de seguimiento
     */
    private function agregar_seguimiento($incidencia_id, $usuario_id, $tipo, $contenido, $es_publico = true, $estado_anterior = null, $estado_nuevo = null) {
        global $wpdb;
        $tabla_seguimiento = $wpdb->prefix . 'flavor_incidencias_seguimiento';

        $resultado = $wpdb->insert($tabla_seguimiento, [
            'incidencia_id' => $incidencia_id,
            'usuario_id' => $usuario_id ?: null,
            'tipo' => $tipo,
            'contenido' => $contenido,
            'estado_anterior' => $estado_anterior,
            'estado_nuevo' => $estado_nuevo,
            'es_publico' => $es_publico ? 1 : 0,
        ]);

        return $resultado ? $wpdb->insert_id : false;
    }

    /**
     * Procesar fotos subidas
     */
    private function procesar_fotos_subidas($incidencia_id, $archivos) {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        global $wpdb;
        $tabla_fotos = $wpdb->prefix . 'flavor_incidencias_fotos';
        $es_primera = true;

        $nombres_archivos = $archivos['name'];
        $archivos_tmp = $archivos['tmp_name'];
        $tipos_archivos = $archivos['type'];
        $tamanos_archivos = $archivos['size'];

        for ($i = 0; $i < count($nombres_archivos); $i++) {
            if (empty($archivos_tmp[$i])) continue;

            $archivo = [
                'name' => $nombres_archivos[$i],
                'type' => $tipos_archivos[$i],
                'tmp_name' => $archivos_tmp[$i],
                'size' => $tamanos_archivos[$i],
            ];

            $resultado_upload = wp_handle_upload($archivo, ['test_form' => false]);

            if (isset($resultado_upload['error'])) {
                continue;
            }

            $attachment = [
                'guid' => $resultado_upload['url'],
                'post_mime_type' => $resultado_upload['type'],
                'post_title' => sanitize_file_name($archivo['name']),
                'post_content' => '',
                'post_status' => 'inherit',
            ];

            $attachment_id = wp_insert_attachment($attachment, $resultado_upload['file']);

            if ($attachment_id) {
                $metadata = wp_generate_attachment_metadata($attachment_id, $resultado_upload['file']);
                wp_update_attachment_metadata($attachment_id, $metadata);

                $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'medium');

                $wpdb->insert($tabla_fotos, [
                    'incidencia_id' => $incidencia_id,
                    'usuario_id' => get_current_user_id() ?: null,
                    'attachment_id' => $attachment_id,
                    'url' => $resultado_upload['url'],
                    'url_thumbnail' => $thumbnail_url ?: $resultado_upload['url'],
                    'nombre_archivo' => $archivo['name'],
                    'tipo_mime' => $resultado_upload['type'],
                    'tamano' => $archivo['size'],
                    'es_principal' => $es_primera ? 1 : 0,
                ]);

                $es_primera = false;
            }
        }
    }

    /**
     * Obtener IP del cliente
     */
    private function obtener_ip_cliente() {
        $claves_ip = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($claves_ip as $clave) {
            if (!empty($_SERVER[$clave])) {
                $ip = $_SERVER[$clave];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    // =========================================================================
    // NOTIFICACIONES
    // =========================================================================

    /**
     * Notificar nueva incidencia
     */
    private function notificar_nueva_incidencia($incidencia_id) {
        $incidencia = $this->obtener_incidencia($incidencia_id);
        if (!$incidencia) return;

        $email_admin = get_option('admin_email');
        $asunto = sprintf(__('[Nueva Incidencia] %s - %s', 'flavor-chat-ia'), $incidencia->numero_incidencia, $incidencia->titulo);

        $mensaje = sprintf(
            __("Se ha reportado una nueva incidencia:\n\nNúmero: %s\nTítulo: %s\nCategoría: %s\nDirección: %s\n\nDescripción:\n%s", 'flavor-chat-ia'),
            $incidencia->numero_incidencia,
            $incidencia->titulo,
            $incidencia->categoria,
            $incidencia->direccion,
            $incidencia->descripcion
        );

        wp_mail($email_admin, $asunto, $mensaje);
    }

    /**
     * Notificar cambio de estado
     */
    private function notificar_cambio_estado($incidencia_id, $estado_anterior, $estado_nuevo) {
        $configuracion = $this->get_settings();
        if (!$configuracion['notificar_actualizaciones']) return;

        $incidencia = $this->obtener_incidencia($incidencia_id);
        if (!$incidencia) return;

        $email_destinatario = null;
        if ($incidencia->usuario_id) {
            $usuario = get_userdata($incidencia->usuario_id);
            $email_destinatario = $usuario->user_email;
        } elseif ($incidencia->email_reportante) {
            $email_destinatario = $incidencia->email_reportante;
        }

        if (!$email_destinatario) return;

        $asunto = sprintf(__('[Actualización] Tu incidencia %s ha cambiado de estado', 'flavor-chat-ia'), $incidencia->numero_incidencia);

        $mensaje = sprintf(
            __("Hola,\n\nTu incidencia \"%s\" (Nº %s) ha cambiado de estado.\n\nEstado anterior: %s\nNuevo estado: %s\n\nPuedes seguir el progreso de tu incidencia en nuestra plataforma.\n\nGracias por tu colaboración.", 'flavor-chat-ia'),
            $incidencia->titulo,
            $incidencia->numero_incidencia,
            $configuracion['estados'][$estado_anterior] ?? $estado_anterior,
            $configuracion['estados'][$estado_nuevo] ?? $estado_nuevo
        );

        wp_mail($email_destinatario, $asunto, $mensaje);
    }

    /**
     * Procesar notificaciones pendientes (cron)
     */
    public function procesar_notificaciones() {
        // Implementar lógica de recordatorios
    }

    /**
     * Calcular estadísticas diarias (cron)
     */
    public function calcular_estadisticas_diarias() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_estadisticas = $wpdb->prefix . 'flavor_incidencias_estadisticas';

        $fecha_hoy = date('Y-m-d');

        // Estadísticas generales
        $estadisticas = $wpdb->get_row("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas,
                AVG(CASE WHEN fecha_resolucion IS NOT NULL THEN TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion) END) as tiempo_medio
            FROM $tabla_incidencias
        ");

        $wpdb->replace($tabla_estadisticas, [
            'fecha' => $fecha_hoy,
            'categoria' => null,
            'departamento' => null,
            'total_reportadas' => $estadisticas->total,
            'total_resueltas' => $estadisticas->resueltas,
            'total_pendientes' => $estadisticas->pendientes,
            'total_en_proceso' => $estadisticas->en_proceso,
            'tiempo_medio_resolucion' => $estadisticas->tiempo_medio,
        ]);
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Formulario de reporte
     */
    public function shortcode_reportar($atributos) {
        $atributos = shortcode_atts([
            'titulo' => __('Reportar Incidencia', 'flavor-chat-ia'),
            'mostrar_mapa' => 'true',
        ], $atributos);

        $configuracion = $this->get_settings();
        $categorias = $this->obtener_categorias_activas();

        ob_start();
        include dirname(__FILE__) . '/templates/formulario-reportar.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mapa de incidencias
     */
    public function shortcode_mapa($atributos) {
        $atributos = shortcode_atts([
            'titulo' => __('Mapa de Incidencias', 'flavor-chat-ia'),
            'altura' => '500',
            'categoria' => '',
            'estado' => '',
        ], $atributos);

        ob_start();
        include dirname(__FILE__) . '/templates/mapa-incidencias.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Listado de incidencias
     */
    public function shortcode_listado($atributos) {
        $atributos = shortcode_atts([
            'titulo' => __('Incidencias', 'flavor-chat-ia'),
            'limite' => 20,
            'categoria' => '',
            'estado' => '',
            'mostrar_filtros' => 'true',
            'paginacion' => 'true',
        ], $atributos);

        // Extraer variables para el template
        $limit = intval($atributos['limite']);
        $mostrar_filtros = $atributos['mostrar_filtros'] === 'true' || $atributos['mostrar_filtros'] === true;

        ob_start();
        include dirname(__FILE__) . '/templates/listado.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis incidencias
     */
    public function shortcode_mis_incidencias($atributos) {
        if (!is_user_logged_in()) {
            return '<p class="incidencias-login-required">' . __('Debes iniciar sesión para ver tus incidencias.', 'flavor-chat-ia') . '</p>';
        }

        $atributos = shortcode_atts([
            'titulo' => __('Mis Incidencias', 'flavor-chat-ia'),
        ], $atributos);

        $usuario_id = get_current_user_id();
        $incidencias = $this->obtener_incidencias_usuario($usuario_id);

        ob_start();
        include dirname(__FILE__) . '/templates/mis-incidencias.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de incidencia
     */
    public function shortcode_detalle($atributos) {
        $numero_incidencia = sanitize_text_field($_GET['incidencia'] ?? '');

        if (empty($numero_incidencia)) {
            return '<p class="incidencias-error">' . __('No se especificó ninguna incidencia.', 'flavor-chat-ia') . '</p>';
        }

        $incidencia = $this->obtener_incidencia(0, $numero_incidencia);

        if (!$incidencia) {
            return '<p class="incidencias-error">' . __('Incidencia no encontrada.', 'flavor-chat-ia') . '</p>';
        }

        $fotos = $this->obtener_fotos_incidencia($incidencia->id);
        $seguimiento = $this->obtener_seguimiento_incidencia($incidencia->id, true);
        $configuracion = $this->get_settings();

        ob_start();
        include dirname(__FILE__) . '/templates/detalle-incidencia.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas
     */
    public function shortcode_estadisticas($atributos) {
        $atributos = shortcode_atts([
            'titulo' => __('Estadísticas de Incidencias', 'flavor-chat-ia'),
        ], $atributos);

        $estadisticas = $this->obtener_estadisticas_generales();
        $estadisticas_por_categoria = $this->obtener_estadisticas_por_categoria();

        ob_start();
        include dirname(__FILE__) . '/templates/estadisticas.php';
        return ob_get_clean();
    }

    /**
     * Obtener categorías activas
     */
    private function obtener_categorias_activas() {
        global $wpdb;
        $tabla_categorias = $wpdb->prefix . 'flavor_incidencias_categorias';

        return $wpdb->get_results("SELECT * FROM $tabla_categorias WHERE activa = 1 ORDER BY orden ASC");
    }

    /**
     * Obtener incidencias de un usuario
     */
    private function obtener_incidencias_usuario($usuario_id) {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_incidencias WHERE usuario_id = %d ORDER BY fecha_reporte DESC",
            $usuario_id
        ));
    }

    /**
     * Obtener estadísticas generales
     */
    private function obtener_estadisticas_generales() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        return $wpdb->get_row("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' OR estado = 'validada' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'resuelta' OR estado = 'cerrada' THEN 1 ELSE 0 END) as resueltas,
                AVG(CASE WHEN fecha_resolucion IS NOT NULL THEN TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion) END) as tiempo_medio_horas
            FROM $tabla_incidencias
        ");
    }

    /**
     * Obtener estadísticas por categoría
     */
    private function obtener_estadisticas_por_categoria() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        return $wpdb->get_results("
            SELECT
                categoria,
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'resuelta' OR estado = 'cerrada' THEN 1 ELSE 0 END) as resueltas
            FROM $tabla_incidencias
            GROUP BY categoria
            ORDER BY total DESC
        ");
    }

    // =========================================================================
    // REST API
    // =========================================================================

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/incidencias', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_incidencias'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/incidencias/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_detalle_incidencia'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/incidencias', [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_incidencia'],
            'permission_callback' => [$this, 'check_api_permissions'],
        ]);

        register_rest_route('flavor/v1', '/incidencias/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'api_actualizar_incidencia'],
            'permission_callback' => [$this, 'check_api_permissions'],
        ]);

        register_rest_route('flavor/v1', '/incidencias/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_estadisticas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/incidencias/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'api_categorias'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/incidencias/mapa', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mapa'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Cargar también la clase API móvil (namespace flavor-chat-ia/v1)
        $api_file = dirname(__FILE__) . '/class-incidencias-api.php';
        if (file_exists($api_file)) {
            require_once $api_file;
            if (class_exists('Flavor_Incidencias_API')) {
                Flavor_Incidencias_API::get_instance();
            }
        }
    }

    /**
     * Verificar permisos API
     */
    public function check_api_permissions() {
        return is_user_logged_in();
    }

    /**
     * API: Listar incidencias
     */
    public function api_listar_incidencias($request) {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $parametros = $request->get_params();
        $pagina = absint($parametros['page'] ?? 1);
        $por_pagina = min(absint($parametros['per_page'] ?? 20), 100);
        $offset = ($pagina - 1) * $por_pagina;

        $where = ["visibilidad = 'publica'"];
        $valores = [];

        if (!empty($parametros['categoria'])) {
            $where[] = 'categoria = %s';
            $valores[] = sanitize_text_field($parametros['categoria']);
        }

        if (!empty($parametros['estado'])) {
            $where[] = 'estado = %s';
            $valores[] = sanitize_text_field($parametros['estado']);
        }

        $sql_where = implode(' AND ', $where);
        $valores[] = $por_pagina;
        $valores[] = $offset;

        $incidencias = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_incidencias WHERE $sql_where ORDER BY fecha_reporte DESC LIMIT %d OFFSET %d",
            ...$valores
        ));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_incidencias WHERE " . implode(' AND ', array_slice($where, 0, -2) ?: ['1=1']),
            ...array_slice($valores, 0, -2)
        ));

        $respuesta = [
            'data' => $incidencias,
            'meta' => [
                'total' => intval($total),
                'page' => $pagina,
                'per_page' => $por_pagina,
                'total_pages' => ceil($total / $por_pagina),
            ],
        ];

        return new WP_REST_Response($this->sanitize_public_incidencias_response($respuesta), 200);
    }

    /**
     * API: Detalle incidencia
     */
    public function api_detalle_incidencia($request) {
        $id = absint($request['id']);
        $incidencia = $this->obtener_incidencia($id);

        if (!$incidencia) {
            return new WP_Error('not_found', __('Incidencia no encontrada.', 'flavor-chat-ia'), ['status' => 404]);
        }

        $fotos = $this->obtener_fotos_incidencia($id);
        $seguimiento = $this->obtener_seguimiento_incidencia($id, true);

        $respuesta = [
            'data' => $incidencia,
            'fotos' => $fotos,
            'seguimiento' => $seguimiento,
        ];

        return new WP_REST_Response($this->sanitize_public_incidencias_response($respuesta), 200);
    }

    /**
     * API: Crear incidencia
     */
    public function api_crear_incidencia($request) {
        $parametros = $request->get_json_params();

        $categoria = sanitize_text_field($parametros['categoria'] ?? '');
        $titulo = sanitize_text_field($parametros['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($parametros['descripcion'] ?? '');

        if (empty($categoria) || empty($titulo) || empty($descripcion)) {
            return new WP_Error('invalid_data', __('Datos incompletos.', 'flavor-chat-ia'), ['status' => 400]);
        }

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $numero_incidencia = $this->generar_numero_incidencia();

        $resultado = $wpdb->insert($tabla_incidencias, [
            'numero_incidencia' => $numero_incidencia,
            'usuario_id' => get_current_user_id(),
            'categoria' => $categoria,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'direccion' => sanitize_text_field($parametros['direccion'] ?? ''),
            'latitud' => isset($parametros['latitud']) ? floatval($parametros['latitud']) : null,
            'longitud' => isset($parametros['longitud']) ? floatval($parametros['longitud']) : null,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_reporte' => current_time('mysql'),
            'visibilidad' => 'publica',
        ]);

        if (!$resultado) {
            return new WP_Error('db_error', __('Error al crear incidencia.', 'flavor-chat-ia'), ['status' => 500]);
        }

        return new WP_REST_Response([
            'id' => $wpdb->insert_id,
            'numero_incidencia' => $numero_incidencia,
            'mensaje' => __('Incidencia creada correctamente.', 'flavor-chat-ia'),
        ], 201);
    }

    /**
     * API: Estadísticas
     */
    public function api_estadisticas($request) {
        return new WP_REST_Response([
            'general' => $this->obtener_estadisticas_generales(),
            'por_categoria' => $this->obtener_estadisticas_por_categoria(),
        ], 200);
    }

    /**
     * API: Categorías
     */
    public function api_categorias($request) {
        return new WP_REST_Response([
            'data' => $this->obtener_categorias_activas(),
        ], 200);
    }

    /**
     * API: Mapa
     */
    public function api_mapa($request) {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $incidencias = $wpdb->get_results("
            SELECT id, numero_incidencia, titulo, categoria, estado, prioridad, direccion, latitud, longitud, fecha_reporte
            FROM $tabla_incidencias
            WHERE visibilidad = 'publica' AND latitud IS NOT NULL AND longitud IS NOT NULL
            ORDER BY fecha_reporte DESC
            LIMIT 500
        ");

        $respuesta = ['data' => $incidencias];

        return new WP_REST_Response($this->sanitize_public_incidencias_response($respuesta), 200);
    }

    private function sanitize_public_incidencias_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta)) {
            return $respuesta;
        }

        if (!empty($respuesta['data']) && is_array($respuesta['data'])) {
            $respuesta['data'] = array_map([$this, 'sanitize_public_incidencia'], $respuesta['data']);
        }

        if (!empty($respuesta['data']) && is_object($respuesta['data'])) {
            $respuesta['data'] = $this->sanitize_public_incidencia($respuesta['data']);
        }

        return $respuesta;
    }

    private function sanitize_public_incidencia($incidencia) {
        if (is_object($incidencia)) {
            unset($incidencia->usuario_id, $incidencia->direccion);
            return $incidencia;
        }

        if (!is_array($incidencia)) {
            return $incidencia;
        }

        unset($incidencia['usuario_id'], $incidencia['direccion']);

        return $incidencia;
    }

    // =========================================================================
    // CHAT IA INTEGRATION
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'reportar_incidencia' => [
                'description' => 'Reportar una nueva incidencia urbana',
                'params' => ['categoria', 'titulo', 'descripcion', 'direccion', 'latitud', 'longitud'],
            ],
            'listar_incidencias' => [
                'description' => 'Listar incidencias por estado o categoría',
                'params' => ['estado', 'categoria', 'limite'],
            ],
            'ver_incidencia' => [
                'description' => 'Ver detalles de una incidencia',
                'params' => ['incidencia_id', 'numero_incidencia'],
            ],
            'mis_incidencias' => [
                'description' => 'Ver incidencias que he reportado',
                'params' => ['estado'],
            ],
            'votar_incidencia' => [
                'description' => 'Votar una incidencia como importante',
                'params' => ['incidencia_id'],
            ],
            'estadisticas_incidencias' => [
                'description' => 'Ver estadísticas de incidencias',
                'params' => [],
            ],
            'mapa_incidencias' => [
                'description' => 'Ver incidencias en el mapa',
                'params' => ['categoria', 'estado'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $aliases = [
            'listar' => 'listar_incidencias',
            'listado' => 'listar_incidencias',
            'buscar' => 'listar_incidencias',
            'crear' => 'reportar_incidencia',
            'nuevo' => 'reportar_incidencia',
            'detalle' => 'ver_incidencia',
            'ver' => 'ver_incidencia',
            'mis_items' => 'listar_incidencias',
            'stats' => 'estadisticas_incidencias',
        ];

        $nombre_accion = $aliases[$nombre_accion] ?? $nombre_accion;
        $metodo = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo)) {
            return $this->$metodo($parametros);
        }

        return [
            'success' => false,
            'error' => sprintf(__('Acción no implementada: %s', 'flavor-chat-ia'), $nombre_accion),
        ];
    }

    /**
     * Acción: Reportar incidencia
     */
    private function action_reportar_incidencia($parametros) {
        $categoria = sanitize_text_field($parametros['categoria'] ?? '');
        $titulo = sanitize_text_field($parametros['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($parametros['descripcion'] ?? '');

        if (empty($categoria) || empty($titulo) || empty($descripcion)) {
            return [
                'success' => false,
                'error' => __('Categoría, título y descripción son obligatorios.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $numero_incidencia = $this->generar_numero_incidencia();

        $resultado = $wpdb->insert($tabla_incidencias, [
            'numero_incidencia' => $numero_incidencia,
            'usuario_id' => get_current_user_id() ?: null,
            'categoria' => $categoria,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'direccion' => sanitize_text_field($parametros['direccion'] ?? ''),
            'latitud' => isset($parametros['latitud']) ? floatval($parametros['latitud']) : null,
            'longitud' => isset($parametros['longitud']) ? floatval($parametros['longitud']) : null,
            'prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_reporte' => current_time('mysql'),
            'visibilidad' => 'publica',
        ]);

        if (!$resultado) {
            return [
                'success' => false,
                'error' => __('Error al registrar la incidencia.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'incidencia_id' => $wpdb->insert_id,
            'numero_incidencia' => $numero_incidencia,
            'mensaje' => sprintf(
                __('Incidencia reportada con éxito. Número de seguimiento: %s', 'flavor-chat-ia'),
                $numero_incidencia
            ),
        ];
    }

    /**
     * Acción: Listar incidencias
     */
    private function action_listar_incidencias($parametros) {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $where = ["visibilidad = 'publica'"];
        $valores = [];

        if (!empty($parametros['estado'])) {
            $where[] = 'estado = %s';
            $valores[] = $parametros['estado'];
        }

        if (!empty($parametros['categoria'])) {
            $where[] = 'categoria = %s';
            $valores[] = $parametros['categoria'];
        }

        $limite = absint($parametros['limite'] ?? 10);
        $valores[] = $limite;

        $sql_where = implode(' AND ', $where);

        $incidencias = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_incidencias WHERE $sql_where ORDER BY fecha_reporte DESC LIMIT %d",
            ...$valores
        ));

        $configuracion = $this->get_settings();

        return [
            'success' => true,
            'total' => count($incidencias),
            'incidencias' => array_map(function($i) use ($configuracion) {
                return [
                    'id' => $i->id,
                    'numero' => $i->numero_incidencia,
                    'titulo' => $i->titulo,
                    'categoria' => $configuracion['categorias'][$i->categoria] ?? $i->categoria,
                    'estado' => $configuracion['estados'][$i->estado] ?? $i->estado,
                    'prioridad' => $i->prioridad,
                    'fecha' => date_i18n('d/m/Y H:i', strtotime($i->fecha_reporte)),
                    'votos' => $i->votos_ciudadanos,
                    'direccion' => $i->direccion,
                ];
            }, $incidencias),
        ];
    }

    /**
     * Acción: Ver incidencia
     */
    private function action_ver_incidencia($parametros) {
        $incidencia_id = absint($parametros['incidencia_id'] ?? 0);
        $numero_incidencia = sanitize_text_field($parametros['numero_incidencia'] ?? '');

        $incidencia = $this->obtener_incidencia($incidencia_id, $numero_incidencia);

        if (!$incidencia) {
            return [
                'success' => false,
                'error' => __('Incidencia no encontrada.', 'flavor-chat-ia'),
            ];
        }

        $seguimiento = $this->obtener_seguimiento_incidencia($incidencia->id, true);
        $configuracion = $this->get_settings();

        return [
            'success' => true,
            'incidencia' => [
                'id' => $incidencia->id,
                'numero' => $incidencia->numero_incidencia,
                'titulo' => $incidencia->titulo,
                'descripcion' => $incidencia->descripcion,
                'categoria' => $configuracion['categorias'][$incidencia->categoria] ?? $incidencia->categoria,
                'estado' => $configuracion['estados'][$incidencia->estado] ?? $incidencia->estado,
                'prioridad' => $incidencia->prioridad,
                'direccion' => $incidencia->direccion,
                'fecha_reporte' => date_i18n('d/m/Y H:i', strtotime($incidencia->fecha_reporte)),
                'fecha_resolucion' => $incidencia->fecha_resolucion ? date_i18n('d/m/Y H:i', strtotime($incidencia->fecha_resolucion)) : null,
                'votos' => $incidencia->votos_ciudadanos,
                'seguimiento' => $seguimiento,
            ],
        ];
    }

    /**
     * Acción: Estadísticas
     */
    private function action_estadisticas_incidencias($parametros) {
        $estadisticas = $this->obtener_estadisticas_generales();
        $por_categoria = $this->obtener_estadisticas_por_categoria();

        return [
            'success' => true,
            'estadisticas' => [
                'total' => intval($estadisticas->total),
                'pendientes' => intval($estadisticas->pendientes),
                'en_proceso' => intval($estadisticas->en_proceso),
                'resueltas' => intval($estadisticas->resueltas),
                'tiempo_medio_horas' => round($estadisticas->tiempo_medio_horas, 1),
            ],
            'por_categoria' => $por_categoria,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'incidencias_reportar',
                'description' => 'Reportar una incidencia urbana (bache, farola, basura, etc.)',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Categoría de la incidencia',
                            'enum' => ['alumbrado', 'limpieza', 'via_publica', 'mobiliario', 'parques', 'ruido', 'agua', 'senalizacion', 'accesibilidad', 'otros'],
                        ],
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Título breve de la incidencia',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción detallada del problema',
                        ],
                        'direccion' => [
                            'type' => 'string',
                            'description' => 'Dirección o ubicación del problema',
                        ],
                    ],
                    'required' => ['categoria', 'titulo', 'descripcion'],
                ],
            ],
            [
                'name' => 'incidencias_listar',
                'description' => 'Ver listado de incidencias reportadas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Filtrar por estado',
                            'enum' => ['pendiente', 'validada', 'en_proceso', 'resuelta', 'cerrada'],
                        ],
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Filtrar por categoría',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados',
                            'default' => 10,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'incidencias_ver',
                'description' => 'Ver detalles de una incidencia específica',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'numero_incidencia' => [
                            'type' => 'string',
                            'description' => 'Número de la incidencia (ej: INC-202602-00001)',
                        ],
                    ],
                    'required' => ['numero_incidencia'],
                ],
            ],
            [
                'name' => 'incidencias_estadisticas',
                'description' => 'Ver estadísticas generales de incidencias',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Incidencias Urbanas**

Permite a los vecinos reportar problemas del barrio y hacer seguimiento hasta su resolución.

**Categorías de incidencias:**
- Alumbrado público: farolas rotas, cables sueltos, zonas oscuras
- Limpieza y residuos: basura, contenedores llenos, vertidos
- Vía pública: baches, aceras rotas, socavones
- Mobiliario urbano: bancos, papeleras, fuentes rotas
- Parques y jardines: árboles caídos, césped, juegos infantiles
- Ruidos y molestias: obras, locales, vecinos
- Agua y alcantarillado: fugas, atascos, inundaciones
- Señalización: señales dañadas, pasos de cebra borrados
- Accesibilidad: rampas, obstáculos
- Otros: cualquier otro problema

**Estados de una incidencia:**
1. Pendiente: Recién reportada, esperando validación
2. Validada: Verificada y aceptada
3. En proceso: Asignada y en gestión
4. Resuelta: Problema solucionado
5. Cerrada: Verificada y cerrada definitivamente
6. Rechazada: No procede o duplicada

**Proceso:**
1. El vecino reporta con descripción y ubicación
2. Se genera número de seguimiento automático
3. El ayuntamiento valida y asigna al departamento correspondiente
4. Se actualiza el estado hasta la resolución
5. El vecino recibe notificaciones de cambios

**Prioridades:**
- Baja: No urgente, puede esperar
- Media: Normal
- Alta: Requiere atención pronta
- Urgente: Peligro o afecta muchas personas
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo reporto un problema del barrio?',
                'respuesta' => 'Puedes usar el formulario de incidencias o decirme qué problema has detectado. Necesito saber la categoría (alumbrado, limpieza, baches...), una descripción del problema y la ubicación.',
            ],
            [
                'pregunta' => '¿Cuánto tardan en resolver una incidencia?',
                'respuesta' => 'Depende de la prioridad y el tipo de problema. Las urgentes se atienden en 24-48h. Puedes seguir el estado con tu número de incidencia.',
            ],
            [
                'pregunta' => '¿Puedo ver el estado de mi incidencia?',
                'respuesta' => 'Sí, dime el número de incidencia (formato INC-YYYYMM-XXXXX) y te muestro todos los detalles y su estado actual.',
            ],
            [
                'pregunta' => '¿Qué pasa si mi incidencia es rechazada?',
                'respuesta' => 'Se te notificará con el motivo del rechazo. Puede ser porque ya existe una similar, no es competencia municipal o no procede.',
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Incidencias', 'flavor-chat-ia'),
                'description' => __('Sección hero con formulario rápido de reporte', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-warning',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Reportar Incidencias', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Ayúdanos a mejorar tu barrio', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'incidencias/hero',
            ],
            'mapa_incidencias' => [
                'label' => __('Mapa de Incidencias', 'flavor-chat-ia'),
                'description' => __('Mapa interactivo con ubicación de reportes', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Mapa de Incidencias', 'flavor-chat-ia'),
                    ],
                    'altura_mapa' => [
                        'type' => 'number',
                        'label' => __('Altura del mapa (px)', 'flavor-chat-ia'),
                        'default' => 500,
                    ],
                ],
                'template' => 'incidencias/mapa',
            ],
            'incidencias_grid' => [
                'label' => __('Grid de Incidencias', 'flavor-chat-ia'),
                'description' => __('Listado de incidencias recientes', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Incidencias Recientes', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 6,
                    ],
                ],
                'template' => 'incidencias/grid',
            ],
            'estadisticas' => [
                'label' => __('Estadísticas Incidencias', 'flavor-chat-ia'),
                'description' => __('Métricas de resolución y tiempos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Estadísticas', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'incidencias/estadisticas',
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
            Flavor_Page_Creator::refresh_module_pages('incidencias');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('incidencias');
        if (!$pagina && !get_option('flavor_incidencias_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['incidencias']);
            update_option('flavor_incidencias_pages_created', 1, false);
        }
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
                'title' => __('Incidencias', 'flavor-chat-ia'),
                'slug' => 'incidencias',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Gestión de Incidencias', 'flavor-chat-ia'),
                    'subtitle' => __('Reporta problemas y consulta el estado de tus tickets', 'flavor-chat-ia'),
                    'background' => 'gradient',
                    'module' => 'incidencias',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="incidencias" action="tickets_publicos" columnas="2"]',
                ]),
                'parent' => 0,
            ],

            // Crear incidencia
            [
                'title' => __('Crear Incidencia', 'flavor-chat-ia'),
                'slug' => 'nueva',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Reportar Incidencia', 'flavor-chat-ia'),
                    'subtitle' => __('Describe el problema que quieres reportar', 'flavor-chat-ia'),
                    'module' => 'incidencias',
                    'current' => 'crear',
                    'content_after' => '[flavor_module_form module="incidencias" action="crear_ticket"]',
                ]),
                'parent' => 'incidencias',
            ],

            // Mis incidencias
            [
                'title' => __('Mis Incidencias', 'flavor-chat-ia'),
                'slug' => 'mis-tickets',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Mis Incidencias', 'flavor-chat-ia'),
                    'subtitle' => __('Seguimiento de tus tickets', 'flavor-chat-ia'),
                    'module' => 'incidencias',
                    'current' => 'mis_tickets',
                    'content_after' => '[flavor_module_listing module="incidencias" action="mis_tickets" user_specific="yes"]',
                ]),
                'parent' => 'incidencias',
            ],

            // Mapa de incidencias
            [
                'title' => __('Mapa', 'flavor-chat-ia'),
                'slug' => 'mapa',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Mapa de Incidencias', 'flavor-chat-ia'),
                    'subtitle' => __('Visualiza las incidencias en el mapa', 'flavor-chat-ia'),
                    'module' => 'incidencias',
                    'current' => 'mapa',
                    'content_after' => '[flavor_module_map module="incidencias" action="mapa_incidencias"]',
                ]),
                'parent' => 'incidencias',
            ],

            // Estadísticas
            [
                'title' => __('Estadísticas', 'flavor-chat-ia'),
                'slug' => 'estadisticas',
                'content' => Flavor_Page_Creator_V3::page_content([
                    'title' => __('Estadísticas de Incidencias', 'flavor-chat-ia'),
                    'subtitle' => __('Datos sobre las incidencias reportadas y resueltas', 'flavor-chat-ia'),
                    'module' => 'incidencias',
                    'current' => 'estadisticas',
                    'content_after' => '[flavor_module_stats module="incidencias"]',
                ]),
                'parent' => 'incidencias',
            ],
        ];
    }

    /**
     * Registrar páginas de administración
     */
    public function registrar_paginas_admin() {
        $capability = 'manage_options';

        // Páginas ocultas (sin menú visible en el sidebar)
        add_submenu_page(
            null,
            __('Incidencias - Dashboard', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            $capability,
            'incidencias',
            [$this, 'render_pagina_dashboard']
        );

        add_submenu_page(
            null,
            __('Incidencias - Tickets', 'flavor-chat-ia'),
            __('Tickets', 'flavor-chat-ia'),
            $capability,
            'incidencias-tickets',
            [$this, 'render_pagina_tickets']
        );

        add_submenu_page(
            null,
            __('Incidencias - Categorías', 'flavor-chat-ia'),
            __('Categorías', 'flavor-chat-ia'),
            $capability,
            'incidencias-categorias',
            [$this, 'render_pagina_categorias']
        );

        add_submenu_page(
            null,
            __('Incidencias - Estadísticas', 'flavor-chat-ia'),
            __('Estadísticas', 'flavor-chat-ia'),
            $capability,
            'incidencias-estadisticas',
            [$this, 'render_pagina_estadisticas']
        );
    }

    /**
     * Renderizar página dashboard
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Dashboard Incidencias', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de tickets
     */
    public function render_pagina_tickets() {
        $views_path = dirname(__FILE__) . '/views/tickets.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Tickets', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de categorías
     */
    public function render_pagina_categorias() {
        $views_path = dirname(__FILE__) . '/views/categorias.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Categorías', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de estadísticas
     */
    public function render_pagina_estadisticas() {
        $views_path = dirname(__FILE__) . '/views/estadisticas.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Estadísticas de Incidencias', 'flavor-chat-ia') . '</h1></div>';
        }
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo_tab = dirname(__FILE__) . '/class-incidencias-dashboard-tab.php';
        if (file_exists($archivo_tab)) {
            require_once $archivo_tab;
            Flavor_Incidencias_Dashboard_Tab::get_instance();
        }
    }
}
