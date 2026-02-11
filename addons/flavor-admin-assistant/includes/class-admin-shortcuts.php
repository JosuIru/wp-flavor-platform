<?php
/**
 * Sistema de Atajos para Admin Assistant
 *
 * Permite ejecutar herramientas directamente SIN consumir tokens de IA
 * Optimizado para acciones frecuentes con respuestas inmediatas
 *
 * @package ChatIAAddon
 * @since 1.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chat_IA_Admin_Shortcuts {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Referencia a las herramientas del asistente
     */
    private $tools = null;

    /**
     * Cache de analytics
     */
    private $analytics_cache = null;

    /**
     * Control de acceso por roles
     */
    private $role_access = null;

    /**
     * Definicion de atajos disponibles
     */
    private $shortcuts = [];

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
        $this->define_shortcuts();
        $this->init_hooks();
    }

    /**
     * Establece la referencia a las herramientas
     */
    public function set_tools($tools) {
        $this->tools = $tools;
    }

    /**
     * Establece la referencia al cache de analytics
     */
    public function set_analytics_cache($cache) {
        $this->analytics_cache = $cache;
    }

    /**
     * Establece la referencia al control de acceso por roles
     */
    public function set_role_access($role_access) {
        $this->role_access = $role_access;
    }

    /**
     * Define los atajos disponibles
     */
    private function define_shortcuts() {
        $this->shortcuts = [
            // ==========================================
            // CALENDARIO - Estados
            // ==========================================
            'set_day_open' => [
                'label' => __('Abrir dia', 'chat-ia-addon'),
                'icon' => 'unlock',
                'group' => 'calendario',
                'tool' => 'asignar_estado_calendario',
                'defaults' => ['estado' => 'abierto'],
                'requires' => ['fecha'],
                'description' => __('Establece un dia como abierto', 'chat-ia-addon'),
            ],
            'set_day_closed' => [
                'label' => __('Cerrar dia', 'chat-ia-addon'),
                'icon' => 'lock',
                'group' => 'calendario',
                'tool' => 'asignar_estado_calendario',
                'defaults' => ['estado' => 'cerrado'],
                'requires' => ['fecha'],
                'description' => __('Establece un dia como cerrado', 'chat-ia-addon'),
            ],
            'set_range_state' => [
                'label' => __('Estado a rango', 'chat-ia-addon'),
                'icon' => 'calendar-alt',
                'group' => 'calendario',
                'tool' => 'asignar_estado_calendario',
                'requires' => ['fecha_inicio', 'fecha_fin', 'estado'],
                'description' => __('Asigna un estado a un rango de fechas', 'chat-ia-addon'),
            ],
            'import_states' => [
                'label' => __('Importar estados', 'chat-ia-addon'),
                'icon' => 'upload',
                'group' => 'calendario',
                'tool' => 'importar_estados_texto',
                'requires' => ['texto'],
                'description' => __('Importa estados desde texto (formato: fecha:estado)', 'chat-ia-addon'),
            ],

            // ==========================================
            // TICKETS - Gestion rapida
            // ==========================================
            'quick_ticket' => [
                'label' => __('Crear ticket', 'chat-ia-addon'),
                'icon' => 'plus-alt',
                'group' => 'tickets',
                'tool' => 'crear_ticket_rapido',
                'requires' => ['nombre', 'precio'],
                'defaults' => ['plazas' => 50, 'iva' => 21],
                'description' => __('Crea un tipo de ticket rapidamente', 'chat-ia-addon'),
            ],
            'update_price' => [
                'label' => __('Cambiar precio', 'chat-ia-addon'),
                'icon' => 'money-alt',
                'group' => 'tickets',
                'tool' => 'actualizar_precio_ticket',
                'requires' => ['ticket_slug', 'precio'],
                'description' => __('Actualiza el precio de un ticket', 'chat-ia-addon'),
            ],
            'update_seats' => [
                'label' => __('Cambiar plazas', 'chat-ia-addon'),
                'icon' => 'groups',
                'group' => 'tickets',
                'tool' => 'editar_tipo_ticket',
                'requires' => ['slug', 'plazas'],
                'description' => __('Actualiza las plazas de un ticket', 'chat-ia-addon'),
            ],

            // ==========================================
            // LIMITES - Plazas por fecha
            // ==========================================
            'set_limit' => [
                'label' => __('Limite de plazas', 'chat-ia-addon'),
                'icon' => 'performance',
                'group' => 'limites',
                'tool' => 'modificar_limite_plazas',
                'requires' => ['fecha', 'tipo_ticket', 'nuevo_limite'],
                'description' => __('Modifica el limite de plazas para una fecha', 'chat-ia-addon'),
            ],

            // ==========================================
            // SHORTCODES - Generacion
            // ==========================================
            'gen_shortcode_calendar' => [
                'label' => __('Shortcode calendario', 'chat-ia-addon'),
                'icon' => 'shortcode',
                'group' => 'shortcodes',
                'tool' => 'generar_shortcode',
                'defaults' => ['tipo' => 'calendario'],
                'description' => __('Genera shortcode de calendario', 'chat-ia-addon'),
            ],
            'gen_shortcode_tickets' => [
                'label' => __('Shortcode tickets', 'chat-ia-addon'),
                'icon' => 'shortcode',
                'group' => 'shortcodes',
                'tool' => 'generar_shortcode',
                'defaults' => ['tipo' => 'reserva_form'],
                'description' => __('Genera shortcode selector de tickets', 'chat-ia-addon'),
            ],
            'gen_shortcode_cart' => [
                'label' => __('Shortcode carrito', 'chat-ia-addon'),
                'icon' => 'shortcode',
                'group' => 'shortcodes',
                'tool' => 'generar_shortcode',
                'defaults' => ['tipo' => 'reservas_carrito'],
                'description' => __('Genera shortcode de carrito', 'chat-ia-addon'),
            ],

            // ==========================================
            // ANALYTICS - Resumenes cacheados
            // ==========================================
            'summary_today' => [
                'label' => __('Resumen hoy', 'chat-ia-addon'),
                'icon' => 'chart-bar',
                'group' => 'analytics',
                'tool' => 'obtener_resumen_hoy',
                'cached' => true,
                'cache_key' => 'summary_today',
                'cache_ttl' => 300, // 5 minutos
                'description' => __('Resumen de reservas de hoy', 'chat-ia-addon'),
            ],
            'summary_week' => [
                'label' => __('Resumen semana', 'chat-ia-addon'),
                'icon' => 'chart-line',
                'group' => 'analytics',
                'tool' => 'obtener_resumen_periodo',
                'defaults' => [
                    'fecha_inicio' => '', // Se calcula dinamicamente
                    'fecha_fin' => '',
                ],
                'cached' => true,
                'cache_key' => 'summary_week',
                'cache_ttl' => 900, // 15 minutos
                'description' => __('Resumen de la semana actual', 'chat-ia-addon'),
            ],
            'summary_month' => [
                'label' => __('Resumen mes', 'chat-ia-addon'),
                'icon' => 'chart-area',
                'group' => 'analytics',
                'tool' => 'obtener_resumen_periodo',
                'defaults' => [],
                'cached' => true,
                'cache_key' => 'summary_month',
                'cache_ttl' => 1800, // 30 minutos
                'description' => __('Resumen del mes actual', 'chat-ia-addon'),
            ],
            'comparison_yesterday' => [
                'label' => __('Vs. ayer', 'chat-ia-addon'),
                'icon' => 'chart-pie',
                'group' => 'analytics',
                'tool' => 'obtener_comparativa_rapida',
                'defaults' => ['tipo' => 'hoy_vs_ayer'],
                'cached' => true,
                'cache_key' => 'comp_yesterday',
                'cache_ttl' => 600, // 10 minutos
                'description' => __('Comparativa hoy vs ayer', 'chat-ia-addon'),
            ],
            'comparison_week' => [
                'label' => __('Vs. semana anterior', 'chat-ia-addon'),
                'icon' => 'chart-pie',
                'group' => 'analytics',
                'tool' => 'obtener_comparativa_rapida',
                'defaults' => ['tipo' => 'semana_vs_anterior'],
                'cached' => true,
                'cache_key' => 'comp_week',
                'cache_ttl' => 1800, // 30 minutos
                'description' => __('Comparativa semanal', 'chat-ia-addon'),
            ],

            // ==========================================
            // BACKUPS - Acciones rapidas
            // ==========================================
            'create_backup' => [
                'label' => __('Crear backup', 'chat-ia-addon'),
                'icon' => 'backup',
                'group' => 'backups',
                'tool' => 'crear_backup',
                'description' => __('Crea un backup manual', 'chat-ia-addon'),
            ],
            'list_backups' => [
                'label' => __('Ver backups', 'chat-ia-addon'),
                'icon' => 'archive',
                'group' => 'backups',
                'tool' => 'listar_backups',
                'defaults' => ['limite' => 10],
                'description' => __('Lista backups disponibles', 'chat-ia-addon'),
            ],

            // ==========================================
            // CONSULTAS RAPIDAS
            // ==========================================
            'available_today' => [
                'label' => __('Plazas hoy', 'chat-ia-addon'),
                'icon' => 'groups',
                'group' => 'consultas',
                'tool' => 'obtener_plazas_disponibles',
                'defaults' => ['fecha' => ''], // Hoy
                'cached' => true,
                'cache_key' => 'plazas_hoy',
                'cache_ttl' => 60, // 1 minuto
                'description' => __('Plazas disponibles hoy', 'chat-ia-addon'),
            ],
            'available_tomorrow' => [
                'label' => __('Plazas manana', 'chat-ia-addon'),
                'icon' => 'groups',
                'group' => 'consultas',
                'tool' => 'obtener_plazas_disponibles',
                'defaults' => ['fecha' => ''], // Manana
                'cached' => true,
                'cache_key' => 'plazas_manana',
                'cache_ttl' => 300, // 5 minutos
                'description' => __('Plazas disponibles manana', 'chat-ia-addon'),
            ],
            'next_reservations' => [
                'label' => __('Proximas reservas', 'chat-ia-addon'),
                'icon' => 'list-view',
                'group' => 'consultas',
                'tool' => 'obtener_proximas_reservas',
                'defaults' => ['dias' => 7, 'limite' => 15],
                'cached' => true,
                'cache_key' => 'proximas_reservas',
                'cache_ttl' => 120, // 2 minutos
                'description' => __('Proximas 15 reservas', 'chat-ia-addon'),
            ],
            'alerts' => [
                'label' => __('Ver alertas', 'chat-ia-addon'),
                'icon' => 'warning',
                'group' => 'consultas',
                'tool' => 'obtener_alertas_sistema',
                'cached' => true,
                'cache_key' => 'alertas_sistema',
                'cache_ttl' => 300, // 5 minutos
                'description' => __('Alertas del sistema', 'chat-ia-addon'),
            ],

            // ==========================================
            // DIAGNÓSTICO
            // ==========================================
            'test_ping' => [
                'label' => __('Test', 'chat-ia-addon'),
                'icon' => 'yes',
                'group' => 'diagnostico',
                'description' => __('Test de conexión', 'chat-ia-addon'),
                'direct_action' => true, // No usa tool, acción directa
            ],
        ];
    }

    /**
     * Inicializa hooks de WordPress
     */
    private function init_hooks() {
        add_action('wp_ajax_chat_ia_execute_shortcut', [$this, 'ajax_execute_shortcut']);
        add_action('wp_ajax_chat_ia_get_shortcuts', [$this, 'ajax_get_shortcuts']);
        add_action('wp_ajax_chat_ia_get_shortcut_form', [$this, 'ajax_get_shortcut_form']);
    }

    /**
     * Obtiene la lista de atajos agrupados
     *
     * @param bool $filter_by_role Si debe filtrar según permisos del usuario
     * @return array
     */
    public function get_shortcuts_grouped($filter_by_role = true) {
        $grouped = [];

        foreach ($this->shortcuts as $shortcut_id => $shortcut) {
            // Filtrar por permisos de rol si está activo
            if ($filter_by_role && $this->role_access && !$this->role_access->can_use_shortcut($shortcut_id)) {
                continue;
            }

            $group = $shortcut['group'] ?? 'otros';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [
                    'label' => $this->get_group_label($group),
                    'icon' => $this->get_group_icon($group),
                    'shortcuts' => [],
                ];
            }
            $grouped[$group]['shortcuts'][$shortcut_id] = [
                'id' => $shortcut_id,
                'label' => $shortcut['label'],
                'icon' => $shortcut['icon'],
                'description' => $shortcut['description'] ?? '',
                'requires' => $shortcut['requires'] ?? [],
                'has_defaults' => !empty($shortcut['defaults']),
            ];
        }

        // Eliminar grupos vacíos
        foreach ($grouped as $group_id => $group) {
            if (empty($group['shortcuts'])) {
                unset($grouped[$group_id]);
            }
        }

        return $grouped;
    }

    /**
     * Obtiene etiqueta del grupo
     */
    private function get_group_label($group) {
        $labels = [
            'calendario' => __('Calendario', 'chat-ia-addon'),
            'tickets' => __('Tickets', 'chat-ia-addon'),
            'limites' => __('Limites', 'chat-ia-addon'),
            'shortcodes' => __('Shortcodes', 'chat-ia-addon'),
            'analytics' => __('Analytics', 'chat-ia-addon'),
            'backups' => __('Backups', 'chat-ia-addon'),
            'consultas' => __('Consultas', 'chat-ia-addon'),
            'otros' => __('Otros', 'chat-ia-addon'),
        ];
        return $labels[$group] ?? ucfirst($group);
    }

    /**
     * Obtiene icono del grupo
     */
    private function get_group_icon($group) {
        $icons = [
            'calendario' => 'calendar-alt',
            'tickets' => 'tickets-alt',
            'limites' => 'performance',
            'shortcodes' => 'shortcode',
            'analytics' => 'chart-bar',
            'backups' => 'backup',
            'consultas' => 'search',
            'otros' => 'admin-generic',
        ];
        return $icons[$group] ?? 'admin-generic';
    }

    /**
     * Ejecuta un atajo
     *
     * @param string $shortcut_id ID del atajo
     * @param array $params Parametros adicionales
     * @return array Resultado de la ejecucion
     */
    public function execute_shortcut($shortcut_id, $params = []) {
        if (!isset($this->shortcuts[$shortcut_id])) {
            return [
                'success' => false,
                'error' => sprintf(__('Atajo no encontrado: %s', 'chat-ia-addon'), $shortcut_id),
            ];
        }

        // Verificar permisos de rol
        if ($this->role_access && !$this->role_access->can_use_shortcut($shortcut_id)) {
            error_log('[AdminShortcuts] Access denied for shortcut: ' . $shortcut_id);
            return [
                'success' => false,
                'error' => $this->role_access->get_restriction_message('shortcut'),
                'access_denied' => true,
            ];
        }

        $shortcut = $this->shortcuts[$shortcut_id];

        // Verificar parametros requeridos
        $requires = $shortcut['requires'] ?? [];
        $missing = [];
        foreach ($requires as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            return [
                'success' => false,
                'error' => sprintf(
                    __('Parametros requeridos: %s', 'chat-ia-addon'),
                    implode(', ', $missing)
                ),
                'missing_fields' => $missing,
                'shortcut' => $shortcut,
            ];
        }

        // Combinar defaults con params
        $defaults = $shortcut['defaults'] ?? [];
        $args = array_merge($this->process_dynamic_defaults($defaults, $shortcut_id), $params);

        // Manejar acciones directas (no requieren tools)
        if (!empty($shortcut['direct_action'])) {
            return $this->execute_direct_action($shortcut_id, $args);
        }

        // Verificar cache si aplica
        if (!empty($shortcut['cached'])) {
            $cached_result = $this->get_cached_result($shortcut, $args);
            // get_transient() devuelve false si no existe, verificamos que sea un array válido
            if ($cached_result !== false && is_array($cached_result)) {
                // Registrar cache hit en monitor de tokens
                if (class_exists('Chat_IA_Token_Monitor')) {
                    Chat_IA_Token_Monitor::get_instance()->log_cache_hit(
                        $shortcut['cache_key'] ?? $shortcut_id,
                        $shortcut['estimated_tokens'] ?? 300
                    );
                }
                return [
                    'success' => true,
                    'data' => $cached_result,
                    'cached' => true,
                    'message' => $this->format_result_message($shortcut, $cached_result),
                ];
            }
        }

        // Ejecutar herramienta
        if (!$this->tools) {
            return [
                'success' => false,
                'error' => __('Sistema de herramientas no disponible', 'chat-ia-addon'),
            ];
        }

        $tool_name = $shortcut['tool'];
        $result = $this->tools->execute_tool($tool_name, $args);

        // Guardar en cache si aplica
        if (!empty($shortcut['cached']) && ($result['success'] ?? false)) {
            $this->cache_result($shortcut, $args, $result);
        }

        // Registrar uso de shortcut en monitor de tokens
        if (class_exists('Chat_IA_Token_Monitor')) {
            Chat_IA_Token_Monitor::get_instance()->log_shortcut_usage(
                $shortcut_id,
                $shortcut['estimated_tokens'] ?? 400
            );
        }

        // Filtrar resultado según permisos de rol
        if ($this->role_access) {
            $result = $this->role_access->filter_tool_result($result, $tool_name);
        }

        // Formatear respuesta
        return [
            'success' => $result['success'] ?? false,
            'data' => $result,
            'cached' => false,
            'message' => $this->format_result_message($shortcut, $result),
            'actions' => $this->get_followup_actions($shortcut_id, $result),
        ];
    }

    /**
     * Ejecuta acciones directas que no requieren tools
     */
    private function execute_direct_action($shortcut_id, $args) {
        switch ($shortcut_id) {
            case 'test_ping':
                $tools_status = $this->tools ? __('Conectadas', 'chat-ia-addon') : __('NO conectadas', 'chat-ia-addon');
                return [
                    'success' => true,
                    'message' => sprintf(
                        __('✅ **Test exitoso!**\n\nConexión AJAX funcionando correctamente.\n\n- Fecha: %s\n- Usuario: %s\n- Tools: %s', 'chat-ia-addon'),
                        date('Y-m-d H:i:s'),
                        wp_get_current_user()->display_name,
                        $tools_status
                    ),
                    'data' => [
                        'timestamp' => time(),
                        'tools_connected' => $this->tools !== null,
                    ],
                ];

            default:
                return [
                    'success' => false,
                    'error' => sprintf(__('Acción directa no reconocida: %s', 'chat-ia-addon'), $shortcut_id),
                ];
        }
    }

    /**
     * Procesa defaults dinamicos (fechas, etc)
     */
    private function process_dynamic_defaults($defaults, $shortcut_id) {
        // Fechas dinamicas
        if ($shortcut_id === 'available_today' || $shortcut_id === 'summary_today') {
            $defaults['fecha'] = date('Y-m-d');
        } elseif ($shortcut_id === 'available_tomorrow') {
            $defaults['fecha'] = date('Y-m-d', strtotime('+1 day'));
        } elseif ($shortcut_id === 'summary_week') {
            $defaults['fecha_inicio'] = date('Y-m-d', strtotime('monday this week'));
            $defaults['fecha_fin'] = date('Y-m-d', strtotime('sunday this week'));
        } elseif ($shortcut_id === 'summary_month') {
            $defaults['fecha_inicio'] = date('Y-m-01');
            $defaults['fecha_fin'] = date('Y-m-t');
        }

        return $defaults;
    }

    /**
     * Obtiene resultado cacheado
     */
    private function get_cached_result($shortcut, $args) {
        $cache_key = 'chat_ia_shortcut_' . ($shortcut['cache_key'] ?? md5(serialize($args)));
        return get_transient($cache_key);
    }

    /**
     * Guarda resultado en cache
     */
    private function cache_result($shortcut, $args, $result) {
        $cache_key = 'chat_ia_shortcut_' . ($shortcut['cache_key'] ?? md5(serialize($args)));
        $ttl = $shortcut['cache_ttl'] ?? 300;
        set_transient($cache_key, $result, $ttl);
    }

    /**
     * Formatea el mensaje de resultado
     */
    private function format_result_message($shortcut, $result) {
        if (!($result['success'] ?? false)) {
            return '**Error:** ' . ($result['error'] ?? __('Error desconocido', 'chat-ia-addon'));
        }

        $tool = $shortcut['tool'];

        // Formateo especifico por herramienta
        switch ($tool) {
            case 'obtener_resumen_hoy':
            case 'obtener_resumen_periodo':
                return $this->format_resumen_message($result);

            case 'obtener_plazas_disponibles':
                return $this->format_plazas_message($result);

            case 'asignar_estado_calendario':
                return $this->format_calendario_message($result);

            case 'obtener_alertas_sistema':
                return $this->format_alertas_message($result);

            case 'generar_shortcode':
                return $this->format_shortcode_message($result);

            case 'listar_backups':
                return $this->format_backups_message($result);

            case 'crear_backup':
                return $this->format_backup_creado_message($result);

            case 'obtener_comparativa_rapida':
                return $this->format_comparativa_message($result);

            default:
                return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Formatea mensaje de resumen
     */
    private function format_resumen_message($result) {
        $msg = "📊 **Resumen**\n\n";

        // Mostrar periodo (si es un solo día, mostrar solo fecha)
        if (isset($result['periodo'])) {
            if ($result['periodo']['inicio'] === $result['periodo']['fin']) {
                $msg .= "**Fecha:** {$result['periodo']['inicio']}\n\n";
            } else {
                $msg .= "**Periodo:** {$result['periodo']['inicio']} a {$result['periodo']['fin']}\n\n";
            }
        }

        $msg .= "| Metrica | Valor |\n";
        $msg .= "|---------|-------|\n";

        if (isset($result['total_reservas'])) {
            $msg .= "| Reservas | {$result['total_reservas']} |\n";
        }
        if (isset($result['ingresos_estimados'])) {
            $ingresos = number_format($result['ingresos_estimados'], 2, ',', '.');
            $msg .= "| Ingresos | {$ingresos}€ |\n";
        }
        // Campos adicionales para resumen de hoy
        if (isset($result['check_ins_realizados'])) {
            $msg .= "| Check-ins | {$result['check_ins_realizados']} |\n";
        }
        if (isset($result['pendientes_checkin']) && $result['pendientes_checkin'] > 0) {
            $msg .= "| Pendientes | {$result['pendientes_checkin']} |\n";
        }

        if (!empty($result['por_tipo_ticket'])) {
            $msg .= "\n**Por tipo de ticket:**\n";
            foreach ($result['por_tipo_ticket'] as $tipo) {
                $nombre = $tipo['nombre'] ?? $tipo['ticket_slug'];
                $cantidad = $tipo['cantidad'];
                $msg .= "- {$nombre}: {$cantidad} reservas\n";
            }
        }

        return $msg;
    }

    /**
     * Formatea mensaje de plazas
     */
    private function format_plazas_message($result) {
        $fecha = $result['fecha'] ?? date('Y-m-d');
        $msg = "🎟️ **Disponibilidad para {$fecha}**\n\n";

        if (empty($result['disponibilidad'])) {
            return $msg . "No hay tipos de ticket configurados.";
        }

        $msg .= "| Ticket | Libres | Ocupacion |\n";
        $msg .= "|--------|--------|-----------|\n";

        foreach ($result['disponibilidad'] as $disp) {
            $nombre = $disp['nombre'] ?? $disp['tipo_ticket'];
            $libres = $disp['libres'];
            $totales = $disp['plazas_totales'];
            $pct = $disp['porcentaje_ocupacion'];
            $msg .= "| {$nombre} | {$libres}/{$totales} | {$pct}% |\n";
        }

        return $msg;
    }

    /**
     * Formatea mensaje de calendario
     */
    private function format_calendario_message($result) {
        if (!($result['success'] ?? false)) {
            return "❌ " . ($result['error'] ?? 'Error al actualizar calendario');
        }

        $msg = "✅ **Calendario actualizado**\n\n";

        if (isset($result['dias_actualizados'])) {
            $msg .= "- Dias modificados: {$result['dias_actualizados']}\n";
        }
        if (isset($result['backup_id'])) {
            $msg .= "- Backup creado: `{$result['backup_id']}`\n";
        }

        $msg .= "\n🔄 Refresca la pagina del calendario para ver los cambios.";

        return $msg;
    }

    /**
     * Formatea mensaje de alertas
     */
    private function format_alertas_message($result) {
        $msg = "⚠️ **Alertas del Sistema**\n\n";

        $alertas = $result['alertas'] ?? [];

        if (empty($alertas)) {
            return $msg . "✅ No hay alertas activas. Todo funcionando correctamente.";
        }

        foreach ($alertas as $alerta) {
            $tipo = $alerta['tipo'] ?? 'info';
            $emoji = $tipo === 'error' ? '🔴' : ($tipo === 'warning' ? '🟡' : '🔵');
            $msg .= "{$emoji} {$alerta['mensaje']}\n";
        }

        return $msg;
    }

    /**
     * Formatea mensaje de shortcode
     */
    private function format_shortcode_message($result) {
        $msg = "📝 **Shortcode generado**\n\n";

        if (isset($result['shortcode'])) {
            $msg .= "```\n{$result['shortcode']}\n```\n\n";
            $msg .= "Copia este codigo y pegalo en cualquier pagina o entrada.";
        }

        return $msg;
    }

    /**
     * Formatea mensaje de lista de backups
     */
    private function format_backups_message($result) {
        $msg = "💾 **Backups disponibles**\n\n";

        $backups = $result['backups'] ?? [];

        if (empty($backups)) {
            return $msg . "No hay backups disponibles.";
        }

        $msg .= "| ID | Fecha | Tipo |\n";
        $msg .= "|----|-------|------|\n";

        foreach (array_slice($backups, 0, 10) as $backup) {
            $id = substr($backup['id'], 0, 20);
            $fecha = $backup['fecha'] ?? '-';
            $tipo = $backup['automatico'] ? 'Auto' : 'Manual';
            $msg .= "| {$id} | {$fecha} | {$tipo} |\n";
        }

        if (count($backups) > 10) {
            $msg .= "\n*...y " . (count($backups) - 10) . " mas*";
        }

        return $msg;
    }

    /**
     * Formatea mensaje de backup creado
     */
    private function format_backup_creado_message($result) {
        if (!($result['success'] ?? false)) {
            return "❌ Error al crear backup: " . ($result['error'] ?? 'desconocido');
        }

        $msg = "✅ **Backup creado correctamente**\n\n";
        $msg .= "- ID: `{$result['backup_id']}`\n";
        $msg .= "- Fecha: {$result['fecha']}\n\n";
        $msg .= "Para restaurar: *\"restaura el backup {$result['backup_id']}\"*";

        return $msg;
    }

    /**
     * Formatea mensaje de comparativa
     */
    private function format_comparativa_message($result) {
        $msg = "📈 **Comparativa**\n\n";

        if (isset($result['diferencias'])) {
            $diff_reservas = $result['diferencias']['reservas'] ?? 0;
            $diff_pct = $result['diferencias']['reservas_porcentaje'] ?? 0;

            $emoji = $diff_reservas >= 0 ? '📈' : '📉';
            $signo = $diff_reservas >= 0 ? '+' : '';

            $msg .= "{$emoji} Reservas: {$signo}{$diff_reservas} ({$signo}{$diff_pct}%)\n";

            if (isset($result['diferencias']['ingresos'])) {
                $diff_ingresos = $result['diferencias']['ingresos'];
                $diff_ing_pct = $result['diferencias']['ingresos_porcentaje'] ?? 0;
                $signo_ing = $diff_ingresos >= 0 ? '+' : '';
                $msg .= "💰 Ingresos: {$signo_ing}" . number_format($diff_ingresos, 2) . "€ ({$signo_ing}{$diff_ing_pct}%)\n";
            }
        }

        return $msg;
    }

    /**
     * Obtiene acciones de seguimiento
     */
    private function get_followup_actions($shortcut_id, $result) {
        $actions = [];

        // Acciones basadas en el tipo de shortcut
        switch ($shortcut_id) {
            case 'set_day_open':
            case 'set_day_closed':
            case 'set_range_state':
                $actions[] = [
                    'label' => __('Ver calendario', 'chat-ia-addon'),
                    'url' => admin_url('admin.php?page=calendario_experiencias'),
                ];
                if (isset($result['backup_id'])) {
                    $actions[] = [
                        'label' => __('Deshacer', 'chat-ia-addon'),
                        'shortcut' => 'restore_backup',
                        'params' => ['backup_id' => $result['backup_id']],
                    ];
                }
                break;

            case 'quick_ticket':
            case 'update_price':
            case 'update_seats':
                $actions[] = [
                    'label' => __('Ver tickets', 'chat-ia-addon'),
                    'url' => admin_url('admin.php?page=calendario-gestion-tickets&tab=tipos'),
                ];
                break;

            case 'summary_today':
            case 'summary_week':
            case 'summary_month':
                $actions[] = [
                    'label' => __('Ver dashboard', 'chat-ia-addon'),
                    'url' => admin_url('admin.php?page=calendario-gestion-tickets'),
                ];
                $actions[] = [
                    'label' => __('Exportar CSV', 'chat-ia-addon'),
                    'shortcut' => 'export_csv',
                ];
                break;

            case 'alerts':
                if (!empty($result['alertas'])) {
                    $actions[] = [
                        'label' => __('Ver problemas', 'chat-ia-addon'),
                        'url' => admin_url('admin.php?page=calendario-gestion-tickets&tab=bloqueos'),
                    ];
                }
                break;
        }

        return $actions;
    }

    /**
     * Handler AJAX para ejecutar atajos
     */
    public function ajax_execute_shortcut() {
        check_ajax_referer('chat_ia_admin_assistant_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'chat-ia-addon')]);
        }

        $shortcut_id = sanitize_text_field($_POST['shortcut'] ?? '');
        $params = [];

        // Recoger parametros
        if (isset($_POST['params'])) {
            if (is_string($_POST['params'])) {
                $params = json_decode(stripslashes($_POST['params']), true) ?: [];
            } else {
                $params = array_map('sanitize_text_field', $_POST['params']);
            }
        }

        $result = $this->execute_shortcut($shortcut_id, $params);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Handler AJAX para obtener lista de atajos
     */
    public function ajax_get_shortcuts() {
        check_ajax_referer('chat_ia_admin_assistant_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'chat-ia-addon')]);
        }

        wp_send_json_success([
            'shortcuts' => $this->get_shortcuts_grouped(),
        ]);
    }

    /**
     * Handler AJAX para obtener formulario de atajo
     */
    public function ajax_get_shortcut_form() {
        check_ajax_referer('chat_ia_admin_assistant_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'chat-ia-addon')]);
        }

        $shortcut_id = sanitize_text_field($_POST['shortcut'] ?? '');

        if (!isset($this->shortcuts[$shortcut_id])) {
            wp_send_json_error(['error' => __('Atajo no encontrado', 'chat-ia-addon')]);
        }

        $shortcut = $this->shortcuts[$shortcut_id];
        $form_fields = $this->generate_form_fields($shortcut);

        wp_send_json_success([
            'shortcut' => $shortcut,
            'fields' => $form_fields,
        ]);
    }

    /**
     * Genera campos de formulario para un atajo
     */
    private function generate_form_fields($shortcut) {
        $fields = [];
        $requires = $shortcut['requires'] ?? [];

        foreach ($requires as $field_name) {
            $field = $this->get_field_config($field_name);
            $fields[$field_name] = $field;
        }

        return $fields;
    }

    /**
     * Obtiene configuracion de un campo
     */
    private function get_field_config($field_name) {
        $configs = [
            'fecha' => [
                'type' => 'date',
                'label' => __('Fecha', 'chat-ia-addon'),
                'default' => date('Y-m-d'),
            ],
            'fecha_inicio' => [
                'type' => 'date',
                'label' => __('Fecha inicio', 'chat-ia-addon'),
                'default' => date('Y-m-d'),
            ],
            'fecha_fin' => [
                'type' => 'date',
                'label' => __('Fecha fin', 'chat-ia-addon'),
                'default' => date('Y-m-d', strtotime('+7 days')),
            ],
            'estado' => [
                'type' => 'select',
                'label' => __('Estado', 'chat-ia-addon'),
                'options' => $this->get_estados_options(),
            ],
            'nombre' => [
                'type' => 'text',
                'label' => __('Nombre', 'chat-ia-addon'),
                'placeholder' => __('Nombre del ticket', 'chat-ia-addon'),
            ],
            'precio' => [
                'type' => 'number',
                'label' => __('Precio (EUR)', 'chat-ia-addon'),
                'step' => '0.01',
                'min' => 0,
            ],
            'plazas' => [
                'type' => 'number',
                'label' => __('Plazas', 'chat-ia-addon'),
                'min' => 1,
                'default' => 50,
            ],
            'ticket_slug' => [
                'type' => 'select',
                'label' => __('Tipo de ticket', 'chat-ia-addon'),
                'options' => $this->get_tickets_options(),
            ],
            'slug' => [
                'type' => 'select',
                'label' => __('Tipo de ticket', 'chat-ia-addon'),
                'options' => $this->get_tickets_options(),
            ],
            'tipo_ticket' => [
                'type' => 'select',
                'label' => __('Tipo de ticket', 'chat-ia-addon'),
                'options' => $this->get_tickets_options(),
            ],
            'nuevo_limite' => [
                'type' => 'number',
                'label' => __('Nuevo limite', 'chat-ia-addon'),
                'min' => 0,
            ],
            'texto' => [
                'type' => 'textarea',
                'label' => __('Texto', 'chat-ia-addon'),
                'placeholder' => __('2024-01-15:abierto\n2024-01-16:cerrado', 'chat-ia-addon'),
                'rows' => 5,
            ],
        ];

        return $configs[$field_name] ?? [
            'type' => 'text',
            'label' => ucfirst(str_replace('_', ' ', $field_name)),
        ];
    }

    /**
     * Obtiene opciones de estados
     */
    private function get_estados_options() {
        $estados = get_option('calendario_experiencias_estados', []);
        $options = [];

        foreach ($estados as $slug => $estado) {
            $nombre = $estado['nombre'] ?? $estado['title'] ?? $slug;
            $options[$slug] = $nombre;
        }

        return $options;
    }

    /**
     * Obtiene opciones de tickets
     */
    private function get_tickets_options() {
        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $options = [];

        foreach ($tipos as $slug => $tipo) {
            $nombre = $tipo['name'] ?? $slug;
            $precio = $tipo['precio'] ?? 0;
            $options[$slug] = "{$nombre} ({$precio}€)";
        }

        return $options;
    }

    /**
     * Obtiene la definicion de un atajo especifico
     */
    public function get_shortcut($shortcut_id) {
        return $this->shortcuts[$shortcut_id] ?? null;
    }

    /**
     * Verifica si un atajo existe
     */
    public function shortcut_exists($shortcut_id) {
        return isset($this->shortcuts[$shortcut_id]);
    }

    /**
     * Invalida cache de un shortcut especifico
     */
    public function invalidate_cache($shortcut_id = null) {
        if ($shortcut_id && isset($this->shortcuts[$shortcut_id])) {
            $shortcut = $this->shortcuts[$shortcut_id];
            if (!empty($shortcut['cache_key'])) {
                delete_transient('chat_ia_shortcut_' . $shortcut['cache_key']);
            }
        } else {
            // Invalidar todo
            foreach ($this->shortcuts as $id => $shortcut) {
                if (!empty($shortcut['cache_key'])) {
                    delete_transient('chat_ia_shortcut_' . $shortcut['cache_key']);
                }
            }
        }
    }
}
