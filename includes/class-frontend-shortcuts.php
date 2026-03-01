<?php
/**
 * Sistema de Atajos Frontend (Privacy-Aware)
 *
 * Proporciona atajos limitados y seguros para clientes
 * Protege datos sensibles y limita la informacion expuesta
 *
 * @package ChatIAAddon
 * @since 1.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Evitar redeclaración si ya existe (ej: desde chat-ia-addon)
if (class_exists('Chat_IA_Frontend_Shortcuts')) {
    return;
}

class Chat_IA_Frontend_Shortcuts {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Atajos publicos disponibles
     */
    private $public_shortcuts = [];

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
        $this->define_public_shortcuts();
        $this->init_hooks();
    }

    /**
     * Define atajos publicos (privacy-aware)
     */
    private function define_public_shortcuts() {
        $this->public_shortcuts = [
            // ==========================================
            // DISPONIBILIDAD - Sin datos sensibles
            // ==========================================
            'check_availability' => [
                'label' => __('Ver disponibilidad', 'chat-ia-addon'),
                'icon' => 'calendar',
                'prompt' => __('Que fechas hay disponibles para visitar', 'chat-ia-addon'),
                'action' => 'prompt',
                'description' => __('Consulta fechas disponibles', 'chat-ia-addon'),
            ],
            'check_date' => [
                'label' => __('Consultar fecha', 'chat-ia-addon'),
                'icon' => 'calendar-alt',
                'prompt' => __('Hay disponibilidad para el dia', 'chat-ia-addon'),
                'action' => 'prompt_with_date',
                'requires_datepicker' => true,
                'description' => __('Verifica disponibilidad de una fecha', 'chat-ia-addon'),
            ],

            // ==========================================
            // PRECIOS - Informacion publica
            // ==========================================
            'view_prices' => [
                'label' => __('Ver precios', 'chat-ia-addon'),
                'icon' => 'tag',
                'prompt' => __('Cuales son los precios de las entradas', 'chat-ia-addon'),
                'action' => 'prompt',
                'description' => __('Muestra precios de tickets', 'chat-ia-addon'),
            ],
            'view_tickets' => [
                'label' => __('Tipos de entrada', 'chat-ia-addon'),
                'icon' => 'ticket',
                'prompt' => __('Que tipos de entrada hay disponibles', 'chat-ia-addon'),
                'action' => 'prompt',
                'description' => __('Lista los tipos de entrada', 'chat-ia-addon'),
            ],

            // ==========================================
            // AYUDA - Guias de uso
            // ==========================================
            'how_to_book' => [
                'label' => __('Como reservar', 'chat-ia-addon'),
                'icon' => 'help',
                'prompt' => __('Como puedo hacer una reserva', 'chat-ia-addon'),
                'action' => 'prompt',
                'description' => __('Guia de reserva paso a paso', 'chat-ia-addon'),
            ],
            'payment_methods' => [
                'label' => __('Formas de pago', 'chat-ia-addon'),
                'icon' => 'credit-card',
                'prompt' => __('Cuales son las formas de pago disponibles', 'chat-ia-addon'),
                'action' => 'prompt',
                'description' => __('Metodos de pago aceptados', 'chat-ia-addon'),
            ],
            'cancellation_policy' => [
                'label' => __('Cancelaciones', 'chat-ia-addon'),
                'icon' => 'dismiss',
                'prompt' => __('Cual es la politica de cancelacion', 'chat-ia-addon'),
                'action' => 'prompt',
                'description' => __('Informacion sobre cancelaciones', 'chat-ia-addon'),
            ],

            // ==========================================
            // CONTACTO - Escalamiento
            // ==========================================
            'contact' => [
                'label' => __('Contactar', 'chat-ia-addon'),
                'icon' => 'email',
                'action' => 'escalate',
                'description' => __('Hablar con una persona', 'chat-ia-addon'),
            ],

            // ==========================================
            // HORARIOS - Informacion publica
            // ==========================================
            'opening_hours' => [
                'label' => __('Horarios', 'chat-ia-addon'),
                'icon' => 'clock',
                'prompt' => __('Cuales son los horarios de apertura', 'chat-ia-addon'),
                'action' => 'prompt',
                'description' => __('Horarios de apertura', 'chat-ia-addon'),
            ],
            'location' => [
                'label' => __('Como llegar', 'chat-ia-addon'),
                'icon' => 'location',
                'prompt' => __('Donde estan ubicados y como puedo llegar', 'chat-ia-addon'),
                'action' => 'prompt',
                'description' => __('Direccion e indicaciones', 'chat-ia-addon'),
            ],
        ];
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // AJAX para ejecutar shortcuts publicos
        add_action('wp_ajax_chat_ia_frontend_shortcut', [$this, 'ajax_execute_shortcut']);
        add_action('wp_ajax_nopriv_chat_ia_frontend_shortcut', [$this, 'ajax_execute_shortcut']);

        // AJAX para obtener disponibilidad (privacy-aware)
        add_action('wp_ajax_chat_ia_check_availability', [$this, 'ajax_check_availability']);
        add_action('wp_ajax_nopriv_chat_ia_check_availability', [$this, 'ajax_check_availability']);

        // AJAX para obtener precios (publico)
        add_action('wp_ajax_chat_ia_get_prices', [$this, 'ajax_get_prices']);
        add_action('wp_ajax_nopriv_chat_ia_get_prices', [$this, 'ajax_get_prices']);

        // Agregar datos al frontend
        add_action('wp_enqueue_scripts', [$this, 'localize_frontend_data']);
    }

    /**
     * Obtiene atajos publicos
     */
    public function get_public_shortcuts() {
        return $this->public_shortcuts;
    }

    /**
     * Ejecuta un shortcut publico
     */
    public function ajax_execute_shortcut() {
        check_ajax_referer('chat_ia_public_nonce', 'nonce');

        $shortcut_id = sanitize_text_field($_POST['shortcut'] ?? '');

        if (!isset($this->public_shortcuts[$shortcut_id])) {
            wp_send_json_error(['error' => __('Accion no disponible', 'chat-ia-addon')]);
        }

        $shortcut = $this->public_shortcuts[$shortcut_id];
        $action = $shortcut['action'] ?? 'prompt';

        switch ($action) {
            case 'prompt':
                wp_send_json_success([
                    'type' => 'prompt',
                    'prompt' => $shortcut['prompt'],
                ]);
                break;

            case 'prompt_with_date':
                $fecha = sanitize_text_field($_POST['fecha'] ?? '');
                $prompt = $shortcut['prompt'] . ($fecha ? " {$fecha}" : '');
                wp_send_json_success([
                    'type' => 'prompt',
                    'prompt' => $prompt,
                ]);
                break;

            case 'escalate':
                wp_send_json_success([
                    'type' => 'escalate',
                    'message' => $this->get_contact_message(),
                ]);
                break;

            default:
                wp_send_json_error(['error' => __('Tipo de accion no soportado', 'chat-ia-addon')]);
        }
    }

    /**
     * Verifica disponibilidad (privacy-aware)
     * NO expone numeros exactos de reservas
     */
    public function ajax_check_availability() {
        check_ajax_referer('chat_ia_public_nonce', 'nonce');

        $fecha = sanitize_text_field($_POST['fecha'] ?? '');

        if (empty($fecha)) {
            // Si no hay fecha, mostrar proximos 14 dias
            $disponibilidad = $this->get_availability_summary(14);
        } else {
            // Disponibilidad para fecha especifica
            $disponibilidad = $this->get_date_availability($fecha);
        }

        wp_send_json_success($disponibilidad);
    }

    /**
     * Obtiene resumen de disponibilidad (privacy-aware)
     * Usa lenguaje vago: "buena disponibilidad", "casi completo"
     */
    private function get_availability_summary($dias = 14) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'reservas_tickets';

        $hoy = date('Y-m-d');
        $fin = date('Y-m-d', strtotime("+{$dias} days"));

        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $dias_config = get_option('calendario_experiencias_dias', []);
        $estados = get_option('calendario_experiencias_estados', []);

        $resumen = [];
        $fecha_actual = new DateTime($hoy);
        $fecha_final = new DateTime($fin);

        while ($fecha_actual <= $fecha_final) {
            $fecha_str = $fecha_actual->format('Y-m-d');
            $dia_semana = $fecha_actual->format('l');

            $estado_slug = $dias_config[$fecha_str] ?? null;
            $estado_info = $estado_slug ? ($estados[$estado_slug] ?? null) : null;
            $es_reservable = $estado_info ? !empty($estado_info['reservable']) : false;

            // Si el dia es reservable, calcular disponibilidad vaga
            if ($es_reservable) {
                $disponibilidad_nivel = $this->calcular_nivel_disponibilidad($fecha_str, $tipos);

                $resumen[] = [
                    'fecha' => $fecha_str,
                    'dia_semana' => $this->traducir_dia($dia_semana),
                    'estado' => $estado_info['nombre'] ?? $estado_slug ?? __('Disponible', 'chat-ia-addon'),
                    'disponibilidad' => $disponibilidad_nivel,
                    'reservable' => true,
                ];
            } else {
                $resumen[] = [
                    'fecha' => $fecha_str,
                    'dia_semana' => $this->traducir_dia($dia_semana),
                    'estado' => $estado_info['nombre'] ?? __('No disponible', 'chat-ia-addon'),
                    'disponibilidad' => 'no_disponible',
                    'reservable' => false,
                ];
            }

            $fecha_actual->modify('+1 day');
        }

        return [
            'periodo' => ['desde' => $hoy, 'hasta' => $fin],
            'dias' => $resumen,
            'mensaje' => $this->generar_mensaje_disponibilidad($resumen),
        ];
    }

    /**
     * Calcula nivel de disponibilidad de forma vaga (privacy-aware)
     * Retorna: 'alta', 'media', 'baja', 'agotado'
     */
    private function calcular_nivel_disponibilidad($fecha, $tipos) {
        global $wpdb;
        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';
        $tabla_limites = $wpdb->prefix . 'reservas_limites';

        $plazas_totales = 0;
        $vendidas_totales = 0;

        foreach ($tipos as $slug => $tipo) {
            $plazas_base = intval($tipo['plazas'] ?? 0);

            // Verificar limite especial
            $limite_especial = $wpdb->get_var($wpdb->prepare(
                "SELECT plazas FROM {$tabla_limites} WHERE ticket_slug = %s AND fecha = %s",
                $slug, $fecha
            ));

            $plazas = $limite_especial !== null ? intval($limite_especial) : $plazas_base;
            $plazas_totales += $plazas;

            // Contar vendidas
            $vendidas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_tickets}
                 WHERE ticket_slug = %s AND fecha = %s AND estado != 'cancelado'",
                $slug, $fecha
            ));

            $vendidas_totales += intval($vendidas);
        }

        if ($plazas_totales === 0) {
            return 'no_disponible';
        }

        $ocupacion = ($vendidas_totales / $plazas_totales) * 100;

        if ($ocupacion >= 100) {
            return 'agotado';
        } elseif ($ocupacion >= 80) {
            return 'baja';
        } elseif ($ocupacion >= 50) {
            return 'media';
        } else {
            return 'alta';
        }
    }

    /**
     * Obtiene disponibilidad para una fecha especifica (privacy-aware)
     */
    private function get_date_availability($fecha) {
        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $dias_config = get_option('calendario_experiencias_dias', []);
        $estados = get_option('calendario_experiencias_estados', []);

        $estado_slug = $dias_config[$fecha] ?? null;
        $estado_info = $estado_slug ? ($estados[$estado_slug] ?? null) : null;
        $es_reservable = $estado_info ? !empty($estado_info['reservable']) : false;

        if (!$es_reservable) {
            return [
                'fecha' => $fecha,
                'disponible' => false,
                'mensaje' => __('Esta fecha no esta disponible para reservas.', 'chat-ia-addon'),
            ];
        }

        $nivel = $this->calcular_nivel_disponibilidad($fecha, $tipos);
        $mensaje = $this->get_mensaje_nivel($nivel);

        // Listar tipos de ticket disponibles (sin numeros exactos)
        $tickets_disponibles = [];
        foreach ($tipos as $slug => $tipo) {
            $nivel_ticket = $this->calcular_nivel_disponibilidad_ticket($fecha, $slug, $tipo);
            if ($nivel_ticket !== 'agotado') {
                $tickets_disponibles[] = [
                    'nombre' => $tipo['name'] ?? $slug,
                    'precio' => floatval($tipo['precio'] ?? 0),
                    'disponibilidad' => $nivel_ticket,
                ];
            }
        }

        return [
            'fecha' => $fecha,
            'disponible' => true,
            'nivel_general' => $nivel,
            'mensaje' => $mensaje,
            'tickets' => $tickets_disponibles,
        ];
    }

    /**
     * Calcula nivel de disponibilidad para un tipo de ticket
     */
    private function calcular_nivel_disponibilidad_ticket($fecha, $slug, $tipo) {
        global $wpdb;
        $tabla_tickets = $wpdb->prefix . 'reservas_tickets';
        $tabla_limites = $wpdb->prefix . 'reservas_limites';

        $plazas_base = intval($tipo['plazas'] ?? 0);

        $limite_especial = $wpdb->get_var($wpdb->prepare(
            "SELECT plazas FROM {$tabla_limites} WHERE ticket_slug = %s AND fecha = %s",
            $slug, $fecha
        ));

        $plazas = $limite_especial !== null ? intval($limite_especial) : $plazas_base;

        if ($plazas === 0) {
            return 'no_disponible';
        }

        $vendidas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_tickets}
             WHERE ticket_slug = %s AND fecha = %s AND estado != 'cancelado'",
            $slug, $fecha
        ));

        $ocupacion = (intval($vendidas) / $plazas) * 100;

        if ($ocupacion >= 100) {
            return 'agotado';
        } elseif ($ocupacion >= 80) {
            return 'ultimas_plazas';
        } elseif ($ocupacion >= 50) {
            return 'disponible';
        } else {
            return 'buena_disponibilidad';
        }
    }

    /**
     * Obtiene mensaje segun nivel de disponibilidad
     */
    private function get_mensaje_nivel($nivel) {
        $mensajes = [
            'alta' => __('Hay buena disponibilidad para esta fecha.', 'chat-ia-addon'),
            'media' => __('Hay disponibilidad para esta fecha.', 'chat-ia-addon'),
            'baja' => __('Quedan pocas plazas para esta fecha. Te recomendamos reservar pronto.', 'chat-ia-addon'),
            'agotado' => __('Lo sentimos, esta fecha esta completa.', 'chat-ia-addon'),
            'no_disponible' => __('Esta fecha no esta disponible.', 'chat-ia-addon'),
        ];

        return $mensajes[$nivel] ?? $mensajes['no_disponible'];
    }

    /**
     * Genera mensaje resumido de disponibilidad
     */
    private function generar_mensaje_disponibilidad($resumen) {
        $disponibles = array_filter($resumen, fn($d) => $d['reservable']);
        $total = count($disponibles);

        if ($total === 0) {
            return __('No hay fechas disponibles en el periodo consultado.', 'chat-ia-addon');
        }

        $alta = count(array_filter($disponibles, fn($d) => $d['disponibilidad'] === 'alta'));
        $baja = count(array_filter($disponibles, fn($d) => $d['disponibilidad'] === 'baja'));

        if ($alta > $total * 0.7) {
            return __('Hay buena disponibilidad en las proximas semanas.', 'chat-ia-addon');
        } elseif ($baja > $total * 0.5) {
            return __('Varias fechas tienen poca disponibilidad. Te recomendamos reservar pronto.', 'chat-ia-addon');
        }

        return sprintf(
            __('Hay %d fechas disponibles en las proximas semanas.', 'chat-ia-addon'),
            $total
        );
    }

    /**
     * Traduce dia de la semana
     */
    private function traducir_dia($dia_ingles) {
        $traducciones = [
            'Monday' => __('Lunes', 'chat-ia-addon'),
            'Tuesday' => __('Martes', 'chat-ia-addon'),
            'Wednesday' => __('Miercoles', 'chat-ia-addon'),
            'Thursday' => __('Jueves', 'chat-ia-addon'),
            'Friday' => __('Viernes', 'chat-ia-addon'),
            'Saturday' => __('Sabado', 'chat-ia-addon'),
            'Sunday' => __('Domingo', 'chat-ia-addon'),
        ];

        return $traducciones[$dia_ingles] ?? $dia_ingles;
    }

    /**
     * Obtiene precios (informacion publica)
     */
    public function ajax_get_prices() {
        check_ajax_referer('chat_ia_public_nonce', 'nonce');

        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $precios = [];

        foreach ($tipos as $slug => $tipo) {
            // Solo mostrar tickets activos y publicos
            if (!empty($tipo['oculto']) || !empty($tipo['solo_admin'])) {
                continue;
            }

            $precios[] = [
                'nombre' => $tipo['name'] ?? $slug,
                'descripcion' => $tipo['descripcion'] ?? '',
                'precio' => floatval($tipo['precio'] ?? 0),
                'iva_incluido' => true,
            ];
        }

        wp_send_json_success([
            'precios' => $precios,
            'mensaje' => sprintf(
                __('Tenemos %d tipos de entrada disponibles.', 'chat-ia-addon'),
                count($precios)
            ),
        ]);
    }

    /**
     * Obtiene mensaje de contacto
     */
    private function get_contact_message() {
        $settings = get_option('chat_ia_settings', []);
        $email = $settings['contact_email'] ?? get_option('admin_email');
        $phone = $settings['contact_phone'] ?? '';

        $mensaje = __('Para hablar con una persona:', 'chat-ia-addon') . "\n\n";

        if ($email) {
            $mensaje .= "📧 Email: {$email}\n";
        }
        if ($phone) {
            $mensaje .= "📞 Telefono: {$phone}\n";
        }

        $mensaje .= "\n" . __('Estaremos encantados de ayudarte.', 'chat-ia-addon');

        return $mensaje;
    }

    /**
     * Localiza datos para frontend
     */
    public function localize_frontend_data() {
        if (!is_admin()) {
            // Preparar shortcuts para JS
            $shortcuts_js = [];
            foreach ($this->public_shortcuts as $id => $shortcut) {
                $shortcuts_js[] = [
                    'id' => $id,
                    'label' => $shortcut['label'],
                    'icon' => $shortcut['icon'] ?? '',
                    'description' => $shortcut['description'] ?? '',
                    'requires_datepicker' => $shortcut['requires_datepicker'] ?? false,
                ];
            }

            wp_localize_script('chat-ia-widget', 'chatIAFrontendShortcuts', $shortcuts_js);
        }
    }

    /**
     * Genera sugerencias contextuales basadas en la respuesta
     *
     * @param string $response La respuesta del asistente
     * @return array Sugerencias para mostrar
     */
    public function generate_smart_suggestions($response) {
        $suggestions = [];
        $response_lower = strtolower($response);

        // Si menciona disponibilidad
        if (strpos($response_lower, 'disponib') !== false ||
            strpos($response_lower, 'plazas') !== false) {
            $suggestions[] = [
                'text' => __('Reservar ahora', 'chat-ia-addon'),
                'action' => 'prompt',
                'prompt' => __('Quiero hacer una reserva', 'chat-ia-addon'),
            ];
        }

        // Si menciona precios
        if (strpos($response_lower, 'precio') !== false ||
            strpos($response_lower, 'euros') !== false ||
            strpos($response_lower, '€') !== false) {
            $suggestions[] = [
                'text' => __('Ver fechas', 'chat-ia-addon'),
                'action' => 'prompt',
                'prompt' => __('Que fechas hay disponibles', 'chat-ia-addon'),
            ];
        }

        // Si menciona reserva o confirmacion
        if (strpos($response_lower, 'reserv') !== false) {
            $suggestions[] = [
                'text' => __('Como pago', 'chat-ia-addon'),
                'action' => 'prompt',
                'prompt' => __('Cuales son las formas de pago', 'chat-ia-addon'),
            ];
        }

        // Si es respuesta larga o compleja
        if (strlen($response) > 500) {
            $suggestions[] = [
                'text' => __('Hablar con persona', 'chat-ia-addon'),
                'action' => 'escalate',
            ];
        }

        // Limitar a 3 sugerencias
        return array_slice($suggestions, 0, 3);
    }

    /**
     * Filtros de privacidad para respuestas del asistente
     * Asegura que no se exponen datos sensibles en el frontend
     *
     * @param string $response Respuesta original
     * @return string Respuesta filtrada
     */
    public function filter_response_for_privacy($response) {
        // Patrones a filtrar
        $patterns = [
            // Numeros exactos de reservas
            '/\d+ reservas? (hoy|este|esta|del)/i' => __('varias reservas', 'chat-ia-addon'),

            // URLs de admin
            '/\/wp-admin\/[^\s]+/i' => '',

            // IDs de backup
            '/backup_id[:\s]+[a-z0-9_-]+/i' => '',

            // Datos de otros clientes (emails, nombres)
            '/cliente[:\s]+[^\n]+@[^\n]+/i' => '',

            // Ingresos exactos
            '/\d+[.,]?\d*\s*€?\s*(de\s+)?ingresos?/i' => 'buenos ingresos',

            // Porcentajes de ocupacion exactos
            '/(\d{2,3})[,.]?\d*%\s*(de\s+)?ocupacion/i' => function($matches) {
                $pct = intval($matches[1]);
                if ($pct >= 80) return 'alta ocupacion';
                if ($pct >= 50) return 'ocupacion moderada';
                return 'buena disponibilidad';
            },
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $response = preg_replace_callback($pattern, $replacement, $response);
            } else {
                $response = preg_replace($pattern, $replacement, $response);
            }
        }

        return $response;
    }
}
