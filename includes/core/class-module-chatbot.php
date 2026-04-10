<?php
/**
 * Chatbot Especializado por Módulo
 *
 * Proporciona un chatbot contextualizado para cada módulo:
 * - Conocimiento específico del módulo
 * - Acciones rápidas
 * - Ayuda contextual
 * - Tutoriales interactivos
 *
 * @package FlavorPlatform
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Chatbot {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Motor de IA activo
     */
    private $engine = null;

    /**
     * Módulo actual
     */
    private $current_module = '';

    /**
     * Contexto del módulo
     */
    private $module_contexts = [];

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
        $this->init_module_contexts();
        add_action('wp_ajax_flavor_module_chat', [$this, 'ajax_chat']);
        add_action('wp_ajax_flavor_module_quick_action', [$this, 'ajax_quick_action']);
        add_action('wp_ajax_flavor_get_module_help', [$this, 'ajax_get_help']);
    }

    /**
     * Inicializa contextos de módulos
     */
    private function init_module_contexts() {
        $this->module_contexts = [
            // Contexto GENERAL para Flavor Platform (cuando no hay módulo específico)
            'general' => [
                'nombre' => __('Flavor Platform', 'flavor-platform'),
                'descripcion' => __('Plataforma integral para comunidades y organizaciones', 'flavor-platform'),
                'system_prompt' => "Eres el asistente de Flavor Platform, una plataforma modular para comunidades y organizaciones. Puedes ayudar con:
- Gestión de socios y miembros
- Eventos y actividades
- Reservas de espacios
- Grupos de consumo
- Incidencias y soporte
- Foros y comunicación
- Participación ciudadana
- Y más de 40 módulos disponibles

Responde de forma amable y práctica. Si el usuario pregunta por un módulo específico, guíale hacia él. Si necesitas más información, pregunta.",
                'quick_actions' => [
                    'ver_modulos' => ['label' => __('Ver módulos', 'flavor-platform'), 'icon' => 'grid'],
                    'configuracion' => ['label' => __('Configuración', 'flavor-platform'), 'icon' => 'settings'],
                    'ayuda' => ['label' => __('Ayuda', 'flavor-platform'), 'icon' => 'help'],
                    'documentacion' => ['label' => __('Documentación', 'flavor-platform'), 'icon' => 'book'],
                ],
                'faqs' => [
                    __('¿Qué módulos tiene Flavor Platform?', 'flavor-platform'),
                    __('¿Cómo activo un nuevo módulo?', 'flavor-platform'),
                    __('¿Cómo configuro mi organización?', 'flavor-platform'),
                    __('¿Cómo personalizo el diseño?', 'flavor-platform'),
                ],
            ],

            // Contexto LANDING - Experto en Flavor Platform para clientes potenciales
            'landing' => [
                'nombre' => __('Experto Flavor Platform', 'flavor-platform'),
                'descripcion' => __('Asistente especializado en resolver dudas sobre la plataforma, módulos y funcionalidades', 'flavor-platform'),
                'system_prompt' => "Eres un experto en Flavor Platform, una plataforma modular open source para comunidades, cooperativas, ayuntamientos y organizaciones basada en WordPress.

## TU ROL
Eres el asistente comercial y técnico de Flavor Platform. Respondes dudas de clientes potenciales sobre las funcionalidades, módulos, precios y posibilidades de la plataforma. Eres amable, profesional y conoces en profundidad cada módulo.

## SOBRE FLAVOR PLATFORM
- Plataforma 100% Open Source (licencia GPL v3)
- Más de 70 módulos disponibles organizados en 11 categorías
- Sistema de red federada donde cada comunidad es un 'nodo' autónomo
- Los módulos se comunican entre sí automáticamente
- Compatible con cualquier tema WordPress
- API REST completa con autenticación JWT para apps móviles

## CATEGORÍAS DE MÓDULOS
1. **Participación Ciudadana**: Presupuestos Participativos, Encuestas y Votaciones, Transparencia, Consultas Populares
2. **Economía Social**: Grupos de Consumo, Banco de Tiempo, Marketplace Local, Monedas Locales, Crowdfunding
3. **Sostenibilidad**: Huertos Urbanos, Huella de Carbono, Economía Circular, Movilidad Sostenible
4. **Red Social**: Red Social Federada, Foros y Chat, Comunidades Multinivel, Mensajería
5. **Gestión Comunitaria**: Gestión de Socios, Incidencias, Voluntariado, Turnos de Trabajo
6. **Espacios**: Reserva de Espacios, Espacios Comunes, Gestión de Equipamiento
7. **Cuidados**: Ayuda Vecinal, Círculos de Cuidados, Acompañamiento
8. **Formación**: Cursos y Talleres, Biblioteca Digital, Mentoring
9. **Comunicación**: Campañas, Newsletter, Podcast y Radio, Tablón de Anuncios
10. **Empresarial**: Gestión Empresarial, Facturación, CRM, Fichaje RRHH, TPV
11. **Impacto Social**: Sello Conciencia, Memoria ESG, Balance Bien Común, ODS Agenda 2030

## RED FEDERADA
- Cada instalación es un 'nodo' autónomo
- Los nodos pueden conectarse entre sí para compartir: banco de tiempo, eventos, marketplace
- Cada comunidad mantiene soberanía total sobre sus datos
- Red social multinivel: publica a nivel barrio, ciudad o toda la red
- Conexión peer-to-peer, sin servidores centrales

## PRECIOS
- **Comunidad (Gratis)**: Código completo, actualizaciones desde GitHub, soporte por foro
- **Pro (30-50€/mes)**: Soporte prioritario, instalación asistida, actualizaciones automáticas, 1h consultoría mensual
- **Enterprise (personalizado)**: SLA garantizado, desarrollos a medida, formación, soporte 24/7

## FAQ IMPORTANTES
- Los módulos son independientes: activa solo los que necesites
- Los datos se almacenan en tu propio servidor WordPress
- Compatible con WooCommerce para pagos
- Documentación completa para crear módulos personalizados
- App Flutter de referencia disponible para crear apps móviles

## INFORMACIÓN DE CONTACTO
Cuando tengas dudas, no puedas responder con certeza, o el usuario necesite atención personalizada:
- **Email**: info@gailu.net
- **Formulario de contacto**: Sección 'Contacto' al final de la landing
- **Demo personalizada**: Pueden solicitar una demostración gratuita

## CÓMO RESPONDER
1. Sé conciso pero completo
2. Si preguntan por un módulo específico, explica sus funcionalidades principales
3. Si preguntan por precios, explica el modelo de pricing justo basado en capacidad
4. Si no conoces algo específico o tienes dudas, deriva al contacto: 'Para resolver esta consulta específica, te recomiendo contactar directamente en info@gailu.net o usar el formulario de contacto de la landing'
5. Siempre invita a explorar la landing o solicitar una demo
6. Si preguntan por funcionalidades muy técnicas o integraciones personalizadas, deriva al equipo técnico

Responde en el mismo idioma que use el usuario.",
                'quick_actions' => [
                    'ver_modulos' => ['label' => __('Ver todos los módulos', 'flavor-platform'), 'icon' => 'grid'],
                    'ver_precios' => ['label' => __('Ver precios', 'flavor-platform'), 'icon' => 'money'],
                    'solicitar_demo' => ['label' => __('Solicitar demo', 'flavor-platform'), 'icon' => 'play'],
                    'contactar' => ['label' => __('Contactar', 'flavor-platform'), 'icon' => 'email'],
                ],
                'faqs' => [
                    __('¿Qué es la red federada de nodos?', 'flavor-platform'),
                    __('¿Tengo que usar todos los módulos?', 'flavor-platform'),
                    __('¿Cómo funciona el módulo de Grupos de Consumo?', 'flavor-platform'),
                    __('¿El Banco de Tiempo usa dinero real?', 'flavor-platform'),
                    __('¿Los datos de mi comunidad están seguros?', 'flavor-platform'),
                    __('¿Qué diferencia hay entre la versión gratuita y Pro?', 'flavor-platform'),
                    __('¿Puedo crear una app móvil?', 'flavor-platform'),
                    __('¿Funciona con WooCommerce?', 'flavor-platform'),
                ],
            ],

            'socios' => [
                'nombre' => __('Gestión de Miembros', 'flavor-platform'),
                'descripcion' => __('Gestión de socios, membresías, cuotas y comunicaciones', 'flavor-platform'),
                'system_prompt' => "Eres un asistente experto en gestión de membresías y socios para organizaciones comunitarias. Ayudas con: altas y bajas de socios, gestión de cuotas, tipos de membresía, comunicaciones a socios, carnets digitales y beneficios. Responde de forma clara y práctica.",
                'quick_actions' => [
                    'nuevo_socio' => ['label' => __('Alta de socio', 'flavor-platform'), 'icon' => 'plus'],
                    'buscar_socio' => ['label' => __('Buscar socio', 'flavor-platform'), 'icon' => 'search'],
                    'cuotas_pendientes' => ['label' => __('Ver cuotas pendientes', 'flavor-platform'), 'icon' => 'money'],
                    'enviar_comunicacion' => ['label' => __('Enviar comunicación', 'flavor-platform'), 'icon' => 'email'],
                ],
                'faqs' => [
                    __('¿Cómo doy de alta un nuevo socio?', 'flavor-platform'),
                    __('¿Cómo configuro los tipos de cuota?', 'flavor-platform'),
                    __('¿Cómo envío emails a todos los socios?', 'flavor-platform'),
                    __('¿Cómo genero carnets digitales?', 'flavor-platform'),
                ],
            ],
            'eventos' => [
                'nombre' => __('Eventos', 'flavor-platform'),
                'descripcion' => __('Organización de eventos, inscripciones y calendarios', 'flavor-platform'),
                'system_prompt' => "Eres un asistente experto en gestión de eventos comunitarios. Ayudas con: creación de eventos, gestión de inscripciones, listas de espera, recordatorios, QR de acceso y estadísticas de asistencia. Responde de forma práctica.",
                'quick_actions' => [
                    'crear_evento' => ['label' => __('Crear evento', 'flavor-platform'), 'icon' => 'calendar'],
                    'ver_inscripciones' => ['label' => __('Ver inscripciones', 'flavor-platform'), 'icon' => 'list'],
                    'enviar_recordatorio' => ['label' => __('Enviar recordatorio', 'flavor-platform'), 'icon' => 'bell'],
                    'exportar_asistentes' => ['label' => __('Exportar asistentes', 'flavor-platform'), 'icon' => 'download'],
                ],
                'faqs' => [
                    __('¿Cómo creo un evento recurrente?', 'flavor-platform'),
                    __('¿Cómo gestiono la lista de espera?', 'flavor-platform'),
                    __('¿Cómo envío recordatorios automáticos?', 'flavor-platform'),
                    __('¿Cómo exporto la lista de asistentes?', 'flavor-platform'),
                ],
            ],
            'reservas' => [
                'nombre' => __('Reservas', 'flavor-platform'),
                'descripcion' => __('Sistema de reserva de espacios y recursos', 'flavor-platform'),
                'system_prompt' => "Eres un asistente experto en gestión de reservas de espacios y recursos comunitarios. Ayudas con: configuración de recursos, horarios, precios, políticas de cancelación y confirmaciones. Responde de forma práctica.",
                'quick_actions' => [
                    'nueva_reserva' => ['label' => __('Nueva reserva', 'flavor-platform'), 'icon' => 'calendar-check'],
                    'ver_disponibilidad' => ['label' => __('Ver disponibilidad', 'flavor-platform'), 'icon' => 'clock'],
                    'confirmar_reservas' => ['label' => __('Confirmar pendientes', 'flavor-platform'), 'icon' => 'check'],
                    'configurar_recurso' => ['label' => __('Configurar recurso', 'flavor-platform'), 'icon' => 'settings'],
                ],
                'faqs' => [
                    __('¿Cómo configuro un nuevo espacio reservable?', 'flavor-platform'),
                    __('¿Cómo establezco horarios y precios?', 'flavor-platform'),
                    __('¿Cómo gestiono las cancelaciones?', 'flavor-platform'),
                    __('¿Cómo veo el calendario de ocupación?', 'flavor-platform'),
                ],
            ],
            'grupos_consumo' => [
                'nombre' => __('Grupos de Consumo', 'flavor-platform'),
                'descripcion' => __('Gestión de grupos de consumo, productores y pedidos', 'flavor-platform'),
                'system_prompt' => "Eres un asistente experto en gestión de grupos de consumo. Ayudas con: ciclos de pedido, catálogo de productos, productores, reparto y contabilidad. Responde de forma práctica.",
                'quick_actions' => [
                    'nuevo_ciclo' => ['label' => __('Abrir ciclo', 'flavor-platform'), 'icon' => 'refresh'],
                    'ver_pedidos' => ['label' => __('Ver pedidos', 'flavor-platform'), 'icon' => 'list'],
                    'gestionar_productos' => ['label' => __('Productos', 'flavor-platform'), 'icon' => 'box'],
                    'calcular_reparto' => ['label' => __('Calcular reparto', 'flavor-platform'), 'icon' => 'calculator'],
                ],
                'faqs' => [
                    __('¿Cómo abro un nuevo ciclo de pedidos?', 'flavor-platform'),
                    __('¿Cómo añado un nuevo productor?', 'flavor-platform'),
                    __('¿Cómo calculo el reparto de pedidos?', 'flavor-platform'),
                    __('¿Cómo gestiono los pagos?', 'flavor-platform'),
                ],
            ],
            'incidencias' => [
                'nombre' => __('Incidencias', 'flavor-platform'),
                'descripcion' => __('Sistema de tickets y gestión de incidencias', 'flavor-platform'),
                'system_prompt' => "Eres un asistente experto en gestión de incidencias y tickets de soporte. Ayudas con: categorización, asignación, prioridades, SLAs y resolución. Responde de forma práctica.",
                'quick_actions' => [
                    'nueva_incidencia' => ['label' => __('Nueva incidencia', 'flavor-platform'), 'icon' => 'plus'],
                    'pendientes' => ['label' => __('Ver pendientes', 'flavor-platform'), 'icon' => 'clock'],
                    'urgentes' => ['label' => __('Ver urgentes', 'flavor-platform'), 'icon' => 'alert'],
                    'estadisticas' => ['label' => __('Estadísticas', 'flavor-platform'), 'icon' => 'chart'],
                ],
                'faqs' => [
                    __('¿Cómo priorizo las incidencias?', 'flavor-platform'),
                    __('¿Cómo asigno incidencias a gestores?', 'flavor-platform'),
                    __('¿Cómo configuro notificaciones automáticas?', 'flavor-platform'),
                    __('¿Cómo genero reportes de incidencias?', 'flavor-platform'),
                ],
            ],
            'biblioteca' => [
                'nombre' => __('Biblioteca', 'flavor-platform'),
                'descripcion' => __('Gestión de biblioteca, préstamos y catálogo', 'flavor-platform'),
                'system_prompt' => "Eres un asistente experto en gestión bibliotecaria comunitaria. Ayudas con: catálogo, préstamos, reservas, morosos y adquisiciones. Responde de forma práctica.",
                'quick_actions' => [
                    'nuevo_prestamo' => ['label' => __('Nuevo préstamo', 'flavor-platform'), 'icon' => 'book'],
                    'devoluciones' => ['label' => __('Devoluciones', 'flavor-platform'), 'icon' => 'return'],
                    'buscar_libro' => ['label' => __('Buscar libro', 'flavor-platform'), 'icon' => 'search'],
                    'morosos' => ['label' => __('Ver morosos', 'flavor-platform'), 'icon' => 'alert'],
                ],
                'faqs' => [
                    __('¿Cómo registro un nuevo libro?', 'flavor-platform'),
                    __('¿Cómo gestiono los préstamos vencidos?', 'flavor-platform'),
                    __('¿Cómo configuro los plazos de préstamo?', 'flavor-platform'),
                    __('¿Cómo importo el catálogo?', 'flavor-platform'),
                ],
            ],
            'cursos' => [
                'nombre' => __('Cursos y Formación', 'flavor-platform'),
                'descripcion' => __('Plataforma de cursos online y presenciales', 'flavor-platform'),
                'system_prompt' => "Eres un asistente experto en gestión de formación y cursos. Ayudas con: creación de cursos, matrículas, contenidos, evaluaciones y certificados. Responde de forma práctica.",
                'quick_actions' => [
                    'crear_curso' => ['label' => __('Crear curso', 'flavor-platform'), 'icon' => 'plus'],
                    'matriculas' => ['label' => __('Matrículas', 'flavor-platform'), 'icon' => 'users'],
                    'contenidos' => ['label' => __('Contenidos', 'flavor-platform'), 'icon' => 'file'],
                    'certificados' => ['label' => __('Certificados', 'flavor-platform'), 'icon' => 'award'],
                ],
                'faqs' => [
                    __('¿Cómo creo un nuevo curso?', 'flavor-platform'),
                    __('¿Cómo añado lecciones y contenidos?', 'flavor-platform'),
                    __('¿Cómo configuro evaluaciones?', 'flavor-platform'),
                    __('¿Cómo genero certificados?', 'flavor-platform'),
                ],
            ],
            'transparencia' => [
                'nombre' => __('Transparencia', 'flavor-platform'),
                'descripcion' => __('Portal de transparencia y rendición de cuentas', 'flavor-platform'),
                'system_prompt' => "Eres un asistente experto en transparencia organizacional. Ayudas con: publicación de presupuestos, actas, contratos y documentación pública. Responde de forma práctica.",
                'quick_actions' => [
                    'publicar_acta' => ['label' => __('Publicar acta', 'flavor-platform'), 'icon' => 'file'],
                    'actualizar_presupuesto' => ['label' => __('Presupuesto', 'flavor-platform'), 'icon' => 'money'],
                    'subir_contrato' => ['label' => __('Subir contrato', 'flavor-platform'), 'icon' => 'upload'],
                    'estadisticas' => ['label' => __('Estadísticas', 'flavor-platform'), 'icon' => 'chart'],
                ],
                'faqs' => [
                    __('¿Cómo publico un acta de reunión?', 'flavor-platform'),
                    __('¿Cómo actualizo el presupuesto?', 'flavor-platform'),
                    __('¿Qué documentos debo publicar?', 'flavor-platform'),
                    __('¿Cómo configuro la visibilidad?', 'flavor-platform'),
                ],
            ],
            'banco_tiempo' => [
                'nombre' => __('Banco de Tiempo', 'flavor-platform'),
                'descripcion' => __('Intercambio de servicios entre miembros de la comunidad', 'flavor-platform'),
                'system_prompt' => "Eres un asistente experto en Bancos de Tiempo comunitarios. Ayudas con: publicar servicios que ofreces, solicitar servicios que necesitas, gestionar intercambios, ver tu saldo de horas, historial de intercambios y comunicación entre miembros. Responde con los datos reales cuando estén disponibles.",
                'quick_actions' => [
                    'ofrecer_servicio' => ['label' => __('Ofrecer servicio', 'flavor-platform'), 'icon' => 'plus'],
                    'buscar_servicio' => ['label' => __('Buscar servicio', 'flavor-platform'), 'icon' => 'search'],
                    'mis_intercambios' => ['label' => __('Mis intercambios', 'flavor-platform'), 'icon' => 'update'],
                    'mi_saldo' => ['label' => __('Mi saldo', 'flavor-platform'), 'icon' => 'clock'],
                ],
                'faqs' => [
                    __('¿Cómo ofrezco un servicio?', 'flavor-platform'),
                    __('¿Cómo solicito un servicio?', 'flavor-platform'),
                    __('¿Cómo funciona el sistema de horas?', 'flavor-platform'),
                    __('¿Cómo contacto con otro miembro?', 'flavor-platform'),
                ],
            ],
        ];
    }

    /**
     * Obtiene el motor de IA
     */
    private function get_engine() {
        if ($this->engine === null && class_exists('Flavor_Engine_Manager')) {
            $manager = Flavor_Engine_Manager::get_instance();
            $this->engine = $manager->get_active_engine();
        }
        return $this->engine;
    }

    /**
     * Handler AJAX para chat
     */
    public function ajax_chat() {
        check_ajax_referer('flavor_module_chat', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-platform')]);
        }

        $module = sanitize_text_field($_POST['module'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $history = isset($_POST['history']) ? $this->sanitize_history((array) $_POST['history']) : [];

        if (empty($module) || empty($message)) {
            wp_send_json_error(['error' => __('Parámetros inválidos', 'flavor-platform')]);
        }

        $response = $this->chat($module, $message, $history);
        wp_send_json_success($response);
    }

    /**
     * Handler AJAX para acciones rápidas
     */
    public function ajax_quick_action() {
        check_ajax_referer('flavor_module_chat', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-platform')]);
        }

        $module = sanitize_text_field($_POST['module'] ?? '');
        $action = sanitize_text_field($_POST['action'] ?? '');

        $result = $this->execute_quick_action($module, $action);
        wp_send_json_success($result);
    }

    /**
     * Handler AJAX para obtener ayuda del módulo
     */
    public function ajax_get_help() {
        check_ajax_referer('flavor_module_chat', 'nonce');

        $module = sanitize_text_field($_POST['module'] ?? '');

        if (!isset($this->module_contexts[$module])) {
            wp_send_json_error(['error' => __('Módulo no encontrado', 'flavor-platform')]);
        }

        $context = $this->module_contexts[$module];

        wp_send_json_success([
            'nombre' => $context['nombre'],
            'descripcion' => $context['descripcion'],
            'quick_actions' => $context['quick_actions'],
            'faqs' => $context['faqs'],
        ]);
    }

    /**
     * Procesa mensaje de chat
     */
    public function chat($module, $message, $history = []) {
        $this->current_module = $module;

        // Verificar si es una pregunta de FAQ
        $faq_response = $this->check_faq_match($module, $message);
        if ($faq_response) {
            return [
                'response' => $faq_response,
                'type' => 'faq',
            ];
        }

        // Obtener contexto del módulo (usar 'general' como fallback)
        $module_context = $this->module_contexts[$module] ?? $this->module_contexts['general'] ?? null;
        if (!$module_context) {
            return [
                'response' => __('No tengo información específica sobre este módulo.', 'flavor-platform'),
                'type' => 'error',
            ];
        }

        // Obtener datos en vivo del módulo
        $live_data = $this->get_module_live_data($module);

        // Construir contexto completo
        $system_prompt = $module_context['system_prompt'];
        if (!empty($live_data)) {
            $system_prompt .= "\n\nDatos actuales del módulo:\n" . $live_data;
        }

        // Usar motor IA
        $engine = $this->get_engine();
        if (!$engine) {
            error_log('Flavor Module Chatbot: No hay motor de IA disponible');
            return $this->get_fallback_response($module, $message);
        }
        if (!$engine->is_configured()) {
            error_log('Flavor Module Chatbot: El motor ' . $engine->get_id() . ' no está configurado (sin API key)');
            return $this->get_fallback_response($module, $message);
        }

        // Construir historial de mensajes
        $messages = [];
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $response = $engine->send_message($messages, $system_prompt, []);

            if ($response['success']) {
                return [
                    'response' => $response['response'],
                    'type' => 'ai',
                    'suggestions' => $this->get_follow_up_suggestions($module, $message),
                ];
            }
        } catch (Exception $e) {
            error_log('Flavor Module Chatbot Error: ' . $e->getMessage());
        }

        return $this->get_fallback_response($module, $message);
    }

    /**
     * Verifica si el mensaje coincide con una FAQ
     */
    private function check_faq_match($module, $message) {
        // Por ahora retorna null, se puede implementar matching de FAQs
        return null;
    }

    /**
     * Obtiene datos en vivo del módulo
     */
    private function get_module_live_data($module) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';
        $data = [];

        switch ($module) {
            case 'socios':
                $tabla = $prefix . 'socios';
                if ($this->table_exists($tabla)) {
                    $data[] = sprintf('Total socios activos: %d',
                        $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE estado = 'activo'")
                    );
                    $data[] = sprintf('Nuevos este mes: %d',
                        $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$tabla} WHERE fecha_alta >= %s",
                            date('Y-m-01')
                        ))
                    );
                }
                break;

            case 'eventos':
                $tabla = $prefix . 'eventos';
                if ($this->table_exists($tabla)) {
                    $data[] = sprintf('Próximos eventos: %d',
                        $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$tabla} WHERE fecha_evento >= %s",
                            date('Y-m-d')
                        ))
                    );
                }
                break;

            case 'incidencias':
                $tabla = $prefix . 'incidencias';
                if ($this->table_exists($tabla)) {
                    $data[] = sprintf('Incidencias pendientes: %d',
                        $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE estado NOT IN ('cerrada', 'resuelta')")
                    );
                    $data[] = sprintf('Urgentes: %d',
                        $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE prioridad = 'alta' AND estado NOT IN ('cerrada', 'resuelta')")
                    );
                }
                break;

            case 'reservas':
                $tabla = $prefix . 'reservas';
                if ($this->table_exists($tabla)) {
                    $data[] = sprintf('Reservas para hoy: %d',
                        $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$tabla} WHERE DATE(fecha_reserva) = %s",
                            date('Y-m-d')
                        ))
                    );
                }
                break;

            case 'grupos_consumo':
                // Información del ciclo activo
                $tabla_ciclos = $prefix . 'gc_ciclos';
                $tabla_pedidos = $prefix . 'gc_pedidos';
                $tabla_lineas = $prefix . 'gc_pedidos_lineas';
                $tabla_productos = $prefix . 'gc_productos';

                if ($this->table_exists($tabla_ciclos)) {
                    $ciclo_activo = $wpdb->get_row(
                        "SELECT * FROM {$tabla_ciclos} WHERE estado = 'abierto' ORDER BY id DESC LIMIT 1"
                    );
                    if ($ciclo_activo) {
                        $data[] = sprintf('Ciclo activo: %s (cierra %s)',
                            $ciclo_activo->nombre ?? 'Sin nombre',
                            $ciclo_activo->fecha_cierre ?? 'N/A'
                        );

                        // Pedidos del ciclo actual
                        if ($this->table_exists($tabla_pedidos)) {
                            $total_pedidos = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE ciclo_id = %d",
                                $ciclo_activo->id
                            ));
                            $data[] = sprintf('Pedidos en este ciclo: %d', $total_pedidos ?: 0);
                        }
                    } else {
                        $data[] = 'No hay ciclo de pedidos activo';
                    }
                }

                // Productos más pedidos (histórico)
                if ($this->table_exists($tabla_lineas) && $this->table_exists($tabla_productos)) {
                    $top_productos = $wpdb->get_results("
                        SELECT
                            p.nombre as producto_nombre,
                            SUM(l.cantidad) as total_cantidad,
                            COUNT(DISTINCT l.pedido_id) as num_pedidos
                        FROM {$tabla_lineas} l
                        INNER JOIN {$tabla_productos} p ON l.producto_id = p.id
                        GROUP BY l.producto_id
                        ORDER BY total_cantidad DESC
                        LIMIT 5
                    ");

                    if ($top_productos && count($top_productos) > 0) {
                        $data[] = "\nProductos más consumidos (histórico):";
                        foreach ($top_productos as $i => $producto) {
                            $data[] = sprintf('%d. %s - %s unidades (%d pedidos)',
                                $i + 1,
                                $producto->producto_nombre,
                                number_format($producto->total_cantidad, 0, ',', '.'),
                                $producto->num_pedidos
                            );
                        }
                    }
                }

                // Si no hay datos de líneas, intentar con productos del catálogo
                if (empty($top_productos) && $this->table_exists($tabla_productos)) {
                    $total_productos = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_productos} WHERE estado = 'activo'");
                    $data[] = sprintf('Productos en catálogo: %d', $total_productos ?: 0);

                    // Categorías más populares
                    $categorias = $wpdb->get_results("
                        SELECT categoria, COUNT(*) as num_productos
                        FROM {$tabla_productos}
                        WHERE estado = 'activo' AND categoria IS NOT NULL AND categoria != ''
                        GROUP BY categoria
                        ORDER BY num_productos DESC
                        LIMIT 5
                    ");
                    if ($categorias && count($categorias) > 0) {
                        $data[] = "\nCategorías con más productos:";
                        foreach ($categorias as $cat) {
                            $data[] = sprintf('- %s: %d productos', $cat->categoria, $cat->num_productos);
                        }
                    }

                    // Productos ecológicos
                    $ecologicos = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_productos} WHERE estado = 'activo' AND es_ecologico = 1");
                    if ($ecologicos > 0) {
                        $data[] = sprintf('Productos ecológicos: %d', $ecologicos);
                    }
                }
                break;

            case 'banco_tiempo':
                $tabla_servicios = $prefix . 'banco_tiempo_servicios';
                $tabla_intercambios = $prefix . 'banco_tiempo_intercambios';
                $tabla_saldos = $prefix . 'banco_tiempo_saldos';

                // Servicios disponibles
                if ($this->table_exists($tabla_servicios)) {
                    $servicios_activos = $wpdb->get_var(
                        "SELECT COUNT(*) FROM {$tabla_servicios} WHERE estado = 'activo'"
                    );
                    $data[] = sprintf('Servicios disponibles: %d', $servicios_activos ?: 0);

                    // Categorías más populares
                    $categorias = $wpdb->get_results("
                        SELECT categoria, COUNT(*) as total
                        FROM {$tabla_servicios}
                        WHERE estado = 'activo' AND categoria IS NOT NULL
                        GROUP BY categoria
                        ORDER BY total DESC
                        LIMIT 5
                    ");
                    if ($categorias && count($categorias) > 0) {
                        $data[] = "\nCategorías más populares:";
                        foreach ($categorias as $cat) {
                            $data[] = sprintf('- %s: %d servicios', $cat->categoria, $cat->total);
                        }
                    }

                    // Servicios más solicitados
                    $top_servicios = $wpdb->get_results("
                        SELECT s.titulo, COUNT(i.id) as veces_solicitado
                        FROM {$tabla_servicios} s
                        LEFT JOIN {$tabla_intercambios} i ON s.id = i.servicio_id
                        WHERE s.estado = 'activo'
                        GROUP BY s.id
                        ORDER BY veces_solicitado DESC
                        LIMIT 5
                    ");
                    if ($top_servicios && count($top_servicios) > 0) {
                        $data[] = "\nServicios más solicitados:";
                        foreach ($top_servicios as $i => $srv) {
                            if ($srv->veces_solicitado > 0) {
                                $data[] = sprintf('%d. %s (%d intercambios)',
                                    $i + 1, $srv->titulo, $srv->veces_solicitado);
                            }
                        }
                    }
                }

                // Intercambios recientes
                if ($this->table_exists($tabla_intercambios)) {
                    $intercambios_mes = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_intercambios} WHERE fecha_creacion >= %s",
                        date('Y-m-01')
                    ));
                    $data[] = sprintf('Intercambios este mes: %d', $intercambios_mes ?: 0);

                    $total_horas = $wpdb->get_var(
                        "SELECT SUM(horas) FROM {$tabla_intercambios} WHERE estado = 'completado'"
                    );
                    if ($total_horas > 0) {
                        $data[] = sprintf('Total horas intercambiadas: %s', number_format($total_horas, 1, ',', '.'));
                    }
                }

                // Miembros activos
                if ($this->table_exists($tabla_saldos)) {
                    $miembros_activos = $wpdb->get_var(
                        "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_saldos}"
                    );
                    $data[] = sprintf('Miembros participando: %d', $miembros_activos ?: 0);
                }
                break;
        }

        return implode("\n", $data);
    }

    /**
     * Obtiene respuesta de fallback sin IA
     */
    private function get_fallback_response($module, $message) {
        $context = $this->module_contexts[$module] ?? null;

        $response = sprintf(
            __('Para ayuda con %s, estas son las acciones más comunes:', 'flavor-platform'),
            $context['nombre'] ?? $module
        );

        if (!empty($context['quick_actions'])) {
            $actions = array_keys($context['quick_actions']);
            $response .= "\n\n" . implode("\n", array_map(function($action) use ($context) {
                return "• " . $context['quick_actions'][$action]['label'];
            }, array_slice($actions, 0, 4)));
        }

        $response .= "\n\n" . __('Para asistencia más detallada, contacta con el administrador.', 'flavor-platform');

        return [
            'response' => $response,
            'type' => 'fallback',
            'suggestions' => $context['faqs'] ?? [],
        ];
    }

    /**
     * Obtiene sugerencias de seguimiento
     */
    private function get_follow_up_suggestions($module, $message) {
        $context = $this->module_contexts[$module] ?? null;
        if (!$context || empty($context['faqs'])) {
            return [];
        }

        // Retornar 2 FAQs aleatorias como sugerencias
        $faqs = $context['faqs'];
        shuffle($faqs);
        return array_slice($faqs, 0, 2);
    }

    /**
     * Ejecuta acción rápida
     */
    public function execute_quick_action($module, $action) {
        $context = $this->module_contexts[$module] ?? null;

        if (!$context || !isset($context['quick_actions'][$action])) {
            return [
                'success' => false,
                'message' => __('Acción no encontrada', 'flavor-platform'),
            ];
        }

        // Generar URL o instrucciones según la acción
        $action_urls = $this->get_action_urls($module);

        if (isset($action_urls[$action])) {
            return [
                'success' => true,
                'type' => 'redirect',
                'url' => $action_urls[$action],
                'label' => $context['quick_actions'][$action]['label'],
            ];
        }

        // Si no hay URL, dar instrucciones
        return [
            'success' => true,
            'type' => 'instruction',
            'message' => sprintf(
                __('Para "%s", ve al menú de %s en el panel de administración.', 'flavor-platform'),
                $context['quick_actions'][$action]['label'],
                $context['nombre']
            ),
        ];
    }

    /**
     * Obtiene URLs de acciones
     */
    private function get_action_urls($module) {
        $base = admin_url('admin.php?page=flavor-');

        $urls = [
            'socios' => [
                'nuevo_socio' => $base . 'socios&action=nuevo',
                'buscar_socio' => $base . 'socios',
                'cuotas_pendientes' => $base . 'socios-cuotas&filter=pendientes',
            ],
            'eventos' => [
                'crear_evento' => $base . 'eventos&action=nuevo',
                'ver_inscripciones' => $base . 'eventos-inscripciones',
            ],
            'reservas' => [
                'nueva_reserva' => $base . 'reservas&action=nueva',
                'ver_disponibilidad' => $base . 'reservas-calendario',
            ],
            'incidencias' => [
                'nueva_incidencia' => $base . 'incidencias&action=nueva',
                'pendientes' => $base . 'incidencias&filter=pendientes',
                'urgentes' => $base . 'incidencias&filter=urgentes',
            ],
        ];

        return $urls[$module] ?? [];
    }

    /**
     * Sanitiza historial de chat
     */
    private function sanitize_history($history) {
        $sanitized = [];
        foreach ($history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $sanitized[] = [
                    'role' => in_array($msg['role'], ['user', 'assistant']) ? $msg['role'] : 'user',
                    'content' => sanitize_textarea_field($msg['content']),
                ];
            }
        }
        return $sanitized;
    }

    /**
     * Obtiene contexto de un módulo
     */
    public function get_module_context($module) {
        return $this->module_contexts[$module] ?? null;
    }

    /**
     * Obtiene todos los módulos disponibles
     */
    public function get_available_modules() {
        $modules = [];
        foreach ($this->module_contexts as $id => $context) {
            $modules[$id] = [
                'id' => $id,
                'nombre' => $context['nombre'],
                'descripcion' => $context['descripcion'],
            ];
        }
        return $modules;
    }

    /**
     * Verifica si tabla existe
     */
    private function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }
}

// Inicializar
add_action('init', function() {
    Flavor_Module_Chatbot::get_instance();
});
