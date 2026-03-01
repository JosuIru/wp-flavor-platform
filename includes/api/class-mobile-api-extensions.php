<?php
/**
 * Extensiones de API REST para Apps Móviles
 *
 * Endpoints adicionales para notificaciones y actividad reciente
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Mobile_API_Extensions {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'chat-ia-mobile/v1';

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
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registra las rutas adicionales
     */
    public function register_routes() {
        // ==========================================
        // NOTIFICACIONES
        // ==========================================

        // Listar notificaciones del usuario
        register_rest_route(self::API_NAMESPACE, '/notifications', [
            'methods' => 'GET',
            'callback' => [$this, 'get_notifications'],
            'permission_callback' => [$this, 'check_user_permission'],
            'args' => [
                'limit' => [
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
                'unread_only' => [
                    'default' => false,
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ],
            ],
        ]);

        // Marcar notificación como leída
        register_rest_route(self::API_NAMESPACE, '/notifications/(?P<id>\d+)/read', [
            'methods' => 'POST',
            'callback' => [$this, 'mark_notification_read'],
            'permission_callback' => [$this, 'check_user_permission'],
        ]);

        // Marcar todas como leídas
        register_rest_route(self::API_NAMESPACE, '/notifications/read-all', [
            'methods' => 'POST',
            'callback' => [$this, 'mark_all_read'],
            'permission_callback' => [$this, 'check_user_permission'],
        ]);

        // Eliminar notificación
        register_rest_route(self::API_NAMESPACE, '/notifications/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_notification'],
            'permission_callback' => [$this, 'check_user_permission'],
        ]);

        // Contador de no leídas
        register_rest_route(self::API_NAMESPACE, '/notifications/unread-count', [
            'methods' => 'GET',
            'callback' => [$this, 'get_unread_count'],
            'permission_callback' => [$this, 'check_user_permission'],
        ]);

        // ==========================================
        // ACTIVIDAD RECIENTE
        // ==========================================

        // Feed de actividad del usuario
        register_rest_route(self::API_NAMESPACE, '/activity', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user_activity'],
            'permission_callback' => [$this, 'check_user_permission'],
            'args' => [
                'limit' => [
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
                'offset' => [
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // ==========================================
        // MÉTRICAS DASHBOARD
        // ==========================================

        // Métricas del usuario para dashboard
        register_rest_route(self::API_NAMESPACE, '/dashboard/user-metrics', [
            'methods' => 'GET',
            'callback' => [$this, 'get_user_metrics'],
            'permission_callback' => [$this, 'check_user_permission'],
        ]);

        // Datos para gráficos
        register_rest_route(self::API_NAMESPACE, '/dashboard/charts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_chart_data'],
            'permission_callback' => [$this, 'check_user_permission'],
            'args' => [
                'type' => [
                    'default' => 'activity',
                    'enum' => ['activity', 'trends', 'distribution'],
                ],
                'days' => [
                    'default' => 7,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
    }

    /**
     * Verifica que el usuario esté autenticado
     */
    public function check_user_permission($request) {
        return is_user_logged_in();
    }

    // ==========================================
    // ENDPOINTS DE NOTIFICACIONES
    // ==========================================

    /**
     * Obtiene las notificaciones del usuario
     */
    public function get_notifications($request) {
        $user_id = get_current_user_id();
        $limit = $request->get_param('limit');
        $unread_only = $request->get_param('unread_only');

        // Verificar que el sistema de notificaciones esté disponible
        if (!class_exists('Flavor_Notifications_System')) {
            return new WP_Error('notifications_unavailable', 'Sistema de notificaciones no disponible', ['status' => 503]);
        }

        $system = Flavor_Notifications_System::get_instance();
        $args = [
            'limit' => $limit,
            'unread_only' => $unread_only,
        ];

        $notifications = $system->get_user_notifications($user_id, $args);

        // Formatear para móvil
        $formatted = array_map(function($notif) {
            return [
                'id' => (int) $notif->id,
                'type' => $notif->type,
                'title' => $notif->title,
                'message' => $notif->message,
                'icon' => $notif->icon ?? $this->get_icon_for_type($notif->type),
                'color' => $this->get_color_for_type($notif->type),
                'link' => $notif->link,
                'is_read' => (bool) $notif->is_read,
                'created_at' => $notif->created_at,
                'time_ago' => $this->time_ago($notif->created_at),
            ];
        }, $notifications);

        return rest_ensure_response([
            'success' => true,
            'data' => $formatted,
            'total' => count($formatted),
        ]);
    }

    /**
     * Marca una notificación como leída
     */
    public function mark_notification_read($request) {
        $user_id = get_current_user_id();
        $notification_id = $request->get_param('id');

        if (!class_exists('Flavor_Notifications_System')) {
            return new WP_Error('notifications_unavailable', 'Sistema de notificaciones no disponible', ['status' => 503]);
        }

        $system = Flavor_Notifications_System::get_instance();

        // Verificar que la notificación pertenece al usuario
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_notifications';
        $notif = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND user_id = %d",
            $notification_id,
            $user_id
        ));

        if (!$notif) {
            return new WP_Error('not_found', 'Notificación no encontrada', ['status' => 404]);
        }

        $result = $system->mark_as_read($notification_id);

        return rest_ensure_response([
            'success' => $result,
            'message' => $result ? 'Notificación marcada como leída' : 'Error al marcar notificación',
        ]);
    }

    /**
     * Marca todas las notificaciones como leídas
     */
    public function mark_all_read($request) {
        $user_id = get_current_user_id();

        if (!class_exists('Flavor_Notifications_System')) {
            return new WP_Error('notifications_unavailable', 'Sistema de notificaciones no disponible', ['status' => 503]);
        }

        $system = Flavor_Notifications_System::get_instance();
        $result = $system->mark_all_as_read($user_id);

        return rest_ensure_response([
            'success' => $result,
            'message' => $result ? 'Todas las notificaciones marcadas como leídas' : 'Error al marcar notificaciones',
        ]);
    }

    /**
     * Elimina una notificación
     */
    public function delete_notification($request) {
        $user_id = get_current_user_id();
        $notification_id = $request->get_param('id');

        if (!class_exists('Flavor_Notifications_System')) {
            return new WP_Error('notifications_unavailable', 'Sistema de notificaciones no disponible', ['status' => 503]);
        }

        // Verificar que la notificación pertenece al usuario
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_notifications';
        $notif = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND user_id = %d",
            $notification_id,
            $user_id
        ));

        if (!$notif) {
            return new WP_Error('not_found', 'Notificación no encontrada', ['status' => 404]);
        }

        $system = Flavor_Notifications_System::get_instance();
        $result = $system->delete($notification_id);

        return rest_ensure_response([
            'success' => $result,
            'message' => $result ? 'Notificación eliminada' : 'Error al eliminar notificación',
        ]);
    }

    /**
     * Obtiene el contador de notificaciones no leídas
     */
    public function get_unread_count($request) {
        $user_id = get_current_user_id();

        if (!class_exists('Flavor_Notifications_System')) {
            return rest_ensure_response([
                'success' => true,
                'count' => 0,
            ]);
        }

        $system = Flavor_Notifications_System::get_instance();
        $count = $system->get_unread_count($user_id);

        return rest_ensure_response([
            'success' => true,
            'count' => $count,
        ]);
    }

    // ==========================================
    // ENDPOINT DE ACTIVIDAD
    // ==========================================

    /**
     * Obtiene el feed de actividad del usuario
     */
    public function get_user_activity($request) {
        $user_id = get_current_user_id();
        $limit = $request->get_param('limit');
        $offset = $request->get_param('offset');

        $activities = [];

        // Recopilar actividades de diferentes fuentes

        // 1. Reservas recientes
        $activities = array_merge($activities, $this->get_reservation_activities($user_id, $limit));

        // 2. Pedidos de Grupos de Consumo
        if (class_exists('Flavor_Grupos_Consumo_API')) {
            $activities = array_merge($activities, $this->get_grupos_consumo_activities($user_id, $limit));
        }

        // 3. Intercambios de Banco de Tiempo
        if (class_exists('Flavor_Banco_Tiempo_API')) {
            $activities = array_merge($activities, $this->get_banco_tiempo_activities($user_id, $limit));
        }

        // 4. Anuncios de Marketplace
        if (class_exists('Flavor_Marketplace_API')) {
            $activities = array_merge($activities, $this->get_marketplace_activities($user_id, $limit));
        }

        // Ordenar por fecha descendente
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        // Aplicar offset y limit
        $activities = array_slice($activities, $offset, $limit);

        return rest_ensure_response([
            'success' => true,
            'data' => $activities,
            'total' => count($activities),
        ]);
    }

    /**
     * Obtiene actividades de reservas
     */
    private function get_reservation_activities($user_id, $limit) {
        $activities = [];

        // Buscar pedidos de WooCommerce del usuario
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        foreach ($orders as $order) {
            $activities[] = [
                'id' => 'order_' . $order->get_id(),
                'type' => 'reservation',
                'title' => 'Reserva confirmada',
                'message' => sprintf('Pedido #%s - %s', $order->get_order_number(), $order->get_status()),
                'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'time_ago' => $this->time_ago($order->get_date_created()->format('Y-m-d H:i:s')),
                'icon' => 'calendar_today',
                'color' => 'blue',
                'link' => '/mis-reservas/' . $order->get_id(),
            ];
        }

        return $activities;
    }

    /**
     * Obtiene actividades de Grupos de Consumo
     */
    private function get_grupos_consumo_activities($user_id, $limit) {
        $activities = [];

        global $wpdb;
        $table = $wpdb->prefix . 'gc_pedidos';

        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return $activities;
        }

        $pedidos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
            WHERE usuario_id = %d
            ORDER BY fecha_creacion DESC
            LIMIT %d",
            $user_id,
            $limit
        ));

        foreach ($pedidos as $pedido) {
            $activities[] = [
                'id' => 'gc_' . $pedido->id,
                'type' => 'grupos_consumo',
                'title' => 'Pedido realizado',
                'message' => 'Grupo de Consumo',
                'date' => $pedido->fecha_creacion,
                'time_ago' => $this->time_ago($pedido->fecha_creacion),
                'icon' => 'shopping_basket',
                'color' => 'green',
                'link' => '/grupos-consumo/mis-pedidos/' . $pedido->id,
            ];
        }

        return $activities;
    }

    /**
     * Obtiene actividades de Banco de Tiempo
     */
    private function get_banco_tiempo_activities($user_id, $limit) {
        $activities = [];

        global $wpdb;
        $table = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return $activities;
        }

        $intercambios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
            WHERE (usuario_receptor_id = %d OR usuario_solicitante_id = %d)
            AND estado = 'completado'
            ORDER BY fecha_creacion DESC
            LIMIT %d",
            $user_id,
            $user_id,
            $limit
        ));

        foreach ($intercambios as $intercambio) {
            $horas = $intercambio->horas ?? 0;
            $activities[] = [
                'id' => 'bt_' . $intercambio->id,
                'type' => 'banco_tiempo',
                'title' => 'Servicio intercambiado',
                'message' => sprintf('Has %s %d horas',
                    $intercambio->usuario_receptor_id == $user_id ? 'ganado' : 'gastado',
                    $horas
                ),
                'date' => $intercambio->fecha_creacion,
                'time_ago' => $this->time_ago($intercambio->fecha_creacion),
                'icon' => 'volunteer_activism',
                'color' => 'teal',
                'link' => '/banco-tiempo/mis-intercambios/' . $intercambio->id,
            ];
        }

        return $activities;
    }

    /**
     * Obtiene actividades de Marketplace
     */
    private function get_marketplace_activities($user_id, $limit) {
        $activities = [];

        global $wpdb;
        $table = $wpdb->prefix . 'marketplace_anuncios';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return $activities;
        }

        $anuncios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
            WHERE usuario_id = %d
            ORDER BY fecha_creacion DESC
            LIMIT %d",
            $user_id,
            $limit
        ));

        foreach ($anuncios as $anuncio) {
            $activities[] = [
                'id' => 'marketplace_' . $anuncio->id,
                'type' => 'marketplace',
                'title' => 'Anuncio publicado',
                'message' => $anuncio->titulo ?? 'Anuncio en Marketplace',
                'date' => $anuncio->fecha_creacion,
                'time_ago' => $this->time_ago($anuncio->fecha_creacion),
                'icon' => 'storefront',
                'color' => 'orange',
                'link' => '/marketplace/anuncio/' . $anuncio->id,
            ];
        }

        return $activities;
    }

    // ==========================================
    // ENDPOINT DE MÉTRICAS
    // ==========================================

    /**
     * Obtiene métricas del usuario para dashboard
     */
    public function get_user_metrics($request) {
        $user_id = get_current_user_id();
        $metrics = [];

        // Reservas
        $orders_count = wc_get_orders([
            'customer_id' => $user_id,
            'return' => 'ids',
        ]);

        $metrics[] = [
            'id' => 'reservations',
            'title' => 'Mis Reservas',
            'value' => count($orders_count),
            'icon' => 'calendar_today',
            'color' => 'blue',
        ];

        // Grupos de Consumo
        if (class_exists('Flavor_Grupos_Consumo_API')) {
            global $wpdb;
            $table = $wpdb->prefix . 'gc_pedidos';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $pedidos_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE usuario_id = %d AND estado = 'abierto'",
                    $user_id
                ));

                $metrics[] = [
                    'id' => 'gc_pedidos',
                    'title' => 'Pedidos Activos',
                    'value' => (int) $pedidos_count,
                    'icon' => 'shopping_basket',
                    'color' => 'green',
                ];
            }
        }

        // Banco de Tiempo - Saldo de horas
        if (class_exists('Flavor_Banco_Tiempo_API')) {
            global $wpdb;
            $table = $wpdb->prefix . 'bt_usuarios';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $saldo = $wpdb->get_var($wpdb->prepare(
                    "SELECT saldo_horas FROM $table WHERE usuario_id = %d",
                    $user_id
                ));

                $metrics[] = [
                    'id' => 'bt_saldo',
                    'title' => 'Horas Disponibles',
                    'value' => $saldo ?? 0,
                    'icon' => 'volunteer_activism',
                    'color' => 'teal',
                ];
            }
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Obtiene datos para gráficos
     */
    public function get_chart_data($request) {
        $user_id = get_current_user_id();
        $type = $request->get_param('type');
        $days = $request->get_param('days');

        switch ($type) {
            case 'activity':
                return $this->get_activity_chart_data($user_id, $days);
            case 'trends':
                return $this->get_trends_chart_data($user_id, $days);
            case 'distribution':
                return $this->get_distribution_chart_data($user_id);
            default:
                return new WP_Error('invalid_type', 'Tipo de gráfico inválido', ['status' => 400]);
        }
    }

    /**
     * Datos para gráfico de actividad por día
     */
    private function get_activity_chart_data($user_id, $days) {
        $data = [];
        $current_date = new DateTime();

        // Generar últimos N días
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = clone $current_date;
            $date->modify("-$i days");
            $date_str = $date->format('Y-m-d');

            // Contar actividades de ese día
            $count = $this->count_activities_for_date($user_id, $date_str);

            // Formato adaptado para fl_chart
            $data[] = [
                'label' => $this->get_day_short_name($date),
                'value' => (float) $count,
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'type' => 'bar',
            'data' => $data,
        ]);
    }

    /**
     * Cuenta actividades para una fecha específica
     */
    private function count_activities_for_date($user_id, $date) {
        $count = 0;

        // Contar pedidos de WooCommerce
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'date_created' => $date,
            'return' => 'ids',
        ]);
        $count += count($orders);

        // TODO: Añadir conteo de otros módulos si están activos

        return $count;
    }

    /**
     * Datos para gráfico de tendencias (ingresos o reservas)
     */
    private function get_trends_chart_data($user_id, $days) {
        global $wpdb;
        $data = [];
        $current_date = new DateTime();

        // Generar últimos N días
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = clone $current_date;
            $date->modify("-$i days");
            $date_str = $date->format('Y-m-d');

            // Calcular ingresos del día (solo para admin)
            $revenue = 0;
            if (current_user_can('manage_options')) {
                $orders = wc_get_orders([
                    'date_created' => $date_str,
                    'status' => ['completed', 'processing'],
                    'return' => 'ids',
                ]);

                foreach ($orders as $order_id) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        $revenue += (float) $order->get_total();
                    }
                }
            }

            // Formato para fl_chart
            $data[] = [
                'label' => $this->get_day_short_name($date),
                'value' => (float) $revenue,
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'type' => 'line',
            'data' => $data,
        ]);
    }

    /**
     * Datos para gráfico de distribución por módulo
     */
    private function get_distribution_chart_data($user_id) {
        global $wpdb;
        $data = [];

        // Contar uso por módulo
        $modules_count = [];

        // Reservas (WooCommerce)
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit' => -1,
            'return' => 'ids',
        ]);
        if (count($orders) > 0) {
            $modules_count['Reservas'] = count($orders);
        }

        // Grupos de Consumo
        $gc_table = $wpdb->prefix . 'gc_pedidos';
        if ($this->table_exists($gc_table)) {
            $gc_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$gc_table} WHERE usuario_id = %d",
                $user_id
            ));
            if ($gc_count > 0) {
                $modules_count['Grupos Consumo'] = (int) $gc_count;
            }
        }

        // Banco de Tiempo
        $bt_table = $wpdb->prefix . 'bt_intercambios';
        if ($this->table_exists($bt_table)) {
            $bt_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$bt_table} WHERE (usuario_oferta_id = %d OR usuario_solicitud_id = %d)",
                $user_id,
                $user_id
            ));
            if ($bt_count > 0) {
                $modules_count['Banco Tiempo'] = (int) $bt_count;
            }
        }

        // Marketplace
        $mp_table = $wpdb->prefix . 'mp_anuncios';
        if ($this->table_exists($mp_table)) {
            $mp_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$mp_table} WHERE usuario_id = %d",
                $user_id
            ));
            if ($mp_count > 0) {
                $modules_count['Marketplace'] = (int) $mp_count;
            }
        }

        // Convertir a formato para fl_chart
        foreach ($modules_count as $module => $count) {
            $data[] = [
                'label' => $module,
                'value' => (float) $count,
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'type' => 'pie',
            'data' => $data,
        ]);
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Convierte timestamp a tiempo relativo
     */
    private function time_ago($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;

        if ($diff < 60) {
            return 'Justo ahora';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return sprintf('Hace %d %s', $mins, $mins == 1 ? 'minuto' : 'minutos');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return sprintf('Hace %d %s', $hours, $hours == 1 ? 'hora' : 'horas');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return sprintf('Hace %d %s', $days, $days == 1 ? 'día' : 'días');
        } else {
            return date('d/m/Y', $time);
        }
    }

    /**
     * Obtiene icono según tipo de notificación
     */
    private function get_icon_for_type($type) {
        $icons = [
            'info' => 'info',
            'success' => 'check_circle',
            'warning' => 'warning',
            'error' => 'error',
            'message' => 'mail',
            'event' => 'calendar_today',
            'taller' => 'school',
            'incidencia' => 'report_problem',
            'reserva' => 'event',
            'pedido' => 'shopping_basket',
        ];

        return $icons[$type] ?? 'notifications';
    }

    /**
     * Obtiene color según tipo de notificación
     */
    private function get_color_for_type($type) {
        $colors = [
            'info' => 'blue',
            'success' => 'green',
            'warning' => 'orange',
            'error' => 'red',
            'message' => 'purple',
            'event' => 'pink',
            'taller' => 'indigo',
            'incidencia' => 'red',
            'reserva' => 'blue',
            'pedido' => 'green',
        ];

        return $colors[$type] ?? 'gray';
    }

    /**
     * Obtiene nombre corto del día en español
     */
    private function get_day_short_name($date) {
        $day_names = [
            'Mon' => 'Lun',
            'Tue' => 'Mar',
            'Wed' => 'Mié',
            'Thu' => 'Jue',
            'Fri' => 'Vie',
            'Sat' => 'Sáb',
            'Sun' => 'Dom',
        ];

        $day_en = $date->format('D');
        return $day_names[$day_en] ?? $day_en;
    }

    /**
     * Verifica si una tabla existe en la base de datos
     */
    private function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
    }
}

// Inicializar
Flavor_Mobile_API_Extensions::get_instance();
