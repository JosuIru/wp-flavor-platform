<?php
/**
 * Manejadores AJAX para el chat
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Incluir clase de sesión
require_once __DIR__ . '/class-chat-session.php';

class Flavor_Chat_Ajax {

    /**
     * Flag para evitar doble registro
     */
    private static $hooks_registered = false;

    /**
     * Registra los hooks AJAX
     */
    public static function register_hooks() {
        // Evitar doble registro
        if (self::$hooks_registered) {
            return;
        }
        self::$hooks_registered = true;

        // Para usuarios logueados y no logueados
        add_action('wp_ajax_flavor_chat_send', [__CLASS__, 'handle_send_message'], 1);
        add_action('wp_ajax_nopriv_flavor_chat_send', [__CLASS__, 'handle_send_message'], 1);

        // Alias para compatibilidad con widget frontend
        // Prioridad 1 para ejecutarse ANTES que otros plugins (como wp-calendario-experiencias)
        add_action('wp_ajax_chat_ia_send_message', [__CLASS__, 'handle_send_message'], 1);
        add_action('wp_ajax_nopriv_chat_ia_send_message', [__CLASS__, 'handle_send_message'], 1);

        // Handler específico para modal de ayuda admin (usa nonce diferente)
        add_action('wp_ajax_flavor_admin_chat_send', [__CLASS__, 'handle_send_message_admin'], 1);

        add_action('wp_ajax_flavor_chat_start', [__CLASS__, 'handle_start_session'], 1);
        add_action('wp_ajax_nopriv_flavor_chat_start', [__CLASS__, 'handle_start_session'], 1);

        // Alias para compatibilidad con el widget (chat_ia_init_session)
        add_action('wp_ajax_chat_ia_init_session', [__CLASS__, 'handle_init_session'], 1);
        add_action('wp_ajax_nopriv_chat_ia_init_session', [__CLASS__, 'handle_init_session'], 1);

        // Remover handlers conflictivos de otros plugins si existen
        add_action('init', [__CLASS__, 'remove_conflicting_handlers'], 999);
    }

    /**
     * Remueve handlers conflictivos de otros plugins
     */
    public static function remove_conflicting_handlers() {
        // Si Chat_IA_Ajax existe (de wp-calendario-experiencias), remover su handler
        if (class_exists('Chat_IA_Ajax') && !is_a('Chat_IA_Ajax', 'Flavor_Chat_Ajax')) {
            remove_action('wp_ajax_chat_ia_send_message', ['Chat_IA_Ajax', 'handle_send_message']);
            remove_action('wp_ajax_nopriv_chat_ia_send_message', ['Chat_IA_Ajax', 'handle_send_message']);
        }
    }

    /**
     * Maneja el envío de mensajes
     */
    public static function handle_send_message() {
        // Verificar nonce - acepta ambos tipos para compatibilidad
        $nonce_valid = check_ajax_referer('flavor_chat_nonce', 'nonce', false) ||
                       check_ajax_referer('chat_ia_nonce', 'nonce', false);
        if (!$nonce_valid) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-platform')], 403);
        }

        // Rate limiting básico
        if (!self::check_rate_limit()) {
            wp_send_json_error(['error' => __('Demasiadas solicitudes. Espera un momento.', 'flavor-platform')], 429);
        }

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $language = sanitize_text_field($_POST['language'] ?? 'es');
        $context = sanitize_text_field($_POST['context'] ?? ''); // Contexto: landing, general, etc.
        $honeypot = sanitize_text_field($_POST['website_url'] ?? ''); // Campo honeypot
        $ip = self::get_client_ip();

        // Validar idioma
        $supported_languages = ['es', 'eu', 'en', 'fr', 'ca'];
        if (!in_array($language, $supported_languages)) {
            $language = 'es';
        }

        if (empty($message)) {
            wp_send_json_error(['error' => __('Mensaje vacío', 'flavor-platform')]);
        }

        if (strlen($message) > 2000) {
            wp_send_json_error(['error' => __('Mensaje demasiado largo', 'flavor-platform')]);
        }

        // Sistema Antispam
        if (class_exists('Flavor_Chat_Antispam')) {
            $antispam = Flavor_Chat_Antispam::get_instance();
            $validation = $antispam->validate_message($message, $session_id, $ip, [
                'honeypot' => $honeypot,
            ]);

            if (!$validation['valid']) {
                wp_send_json_error([
                    'error' => $validation['error'],
                    'error_code' => $validation['error_code'],
                ]);
            }
        }

        // Obtener o crear sesión
        $session = new Flavor_Chat_Session($session_id);

        if (!$session->get_conversation_id()) {
            $session->start_conversation($language);
        }

        // Guardar mensaje del usuario
        $session->add_message('user', $message);

        // Obtener motor de IA activo
        if (!class_exists('Flavor_Engine_Manager')) {
            wp_send_json_error([
                'error' => __('El chat no está disponible.', 'flavor-platform'),
                'fallback' => self::get_fallback_response($language),
            ]);
        }

        $engine_manager = Flavor_Engine_Manager::get_instance();
        $engine = $engine_manager->get_active_engine();

        if (!$engine || !$engine->is_configured()) {
            wp_send_json_error([
                'error' => __('El chat no está configurado.', 'flavor-platform'),
                'fallback' => self::get_fallback_response($language),
            ]);
        }

        // Obtener configuración
        $settings = flavor_get_main_settings();
        $assistant_name = $settings['assistant_name'] ?? 'Asistente Virtual';
        $assistant_role = $settings['assistant_role'] ?? '';

        // Construir system prompt según contexto
        $system_prompt = self::get_context_system_prompt($context, $assistant_name, $assistant_role);

        // Obtener historial de la conversación
        $history = $session->get_messages();
        $messages = [];
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        // Enviar al motor de IA
        $response = $engine->send_message($messages, $system_prompt, []);

        if (!$response['success']) {
            $fallback = self::get_fallback_response($language);
            wp_send_json_error([
                'error' => $response['error'] ?? __('Error al procesar mensaje', 'flavor-platform'),
                'fallback' => $fallback,
            ]);
        }

        // Guardar respuesta del asistente
        $session->add_message('assistant', $response['response']);

        wp_send_json_success([
            'response' => $response['response'],
            'session_id' => $session->get_session_id(),
            'cart_updated' => false,
        ]);
    }

    /**
     * Maneja el inicio de sesión
     */
    public static function handle_start_session() {
        // Verificar nonce
        if (!check_ajax_referer('flavor_chat_nonce', 'nonce', false)) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-platform')], 403);
        }

        $language = sanitize_text_field($_POST['language'] ?? 'es');

        $session = new Flavor_Chat_Session();
        $session->start_conversation($language);

        wp_send_json_success([
            'session_id' => $session->get_session_id(),
            'conversation_id' => $session->get_conversation_id(),
        ]);
    }

    /**
     * Maneja la inicialización de sesión (alias para el widget)
     * Usado por chat-widget.js con action: chat_ia_init_session
     */
    public static function handle_init_session() {
        // Verificar nonce - acepta ambos nombres
        $nonce_valid = check_ajax_referer('flavor_chat_nonce', 'nonce', false);

        if (!$nonce_valid) {
            wp_send_json_error(['error' => __('Nonce inválido', 'flavor-platform')], 403);
        }

        $language = sanitize_text_field($_POST['language'] ?? 'es');

        // Validar idioma
        $supported_languages = ['es', 'eu', 'en', 'fr', 'ca'];
        if (!in_array($language, $supported_languages)) {
            $language = 'es';
        }

        $session = new Flavor_Chat_Session();
        $session->start_conversation($language);

        // Obtener mensaje de bienvenida
        $settings = flavor_get_main_settings();
        $appearance = $settings['appearance'] ?? [];
        $welcome_message = $appearance['welcome_message'] ?? __('¡Hola! ¿En qué puedo ayudarte?', 'flavor-platform');

        wp_send_json_success([
            'session_id' => $session->get_session_id(),
            'conversation_id' => $session->get_conversation_id(),
            'welcome_message' => $welcome_message,
            'language' => $language,
        ]);
    }

    /**
     * Verifica rate limiting
     *
     * @return bool
     */
    private static function check_rate_limit() {
        $ip = self::get_client_ip();
        $key = 'flavor_chat_rate_' . md5($ip);

        $count = get_transient($key);

        if ($count === false) {
            set_transient($key, 1, 60); // 1 minuto
            return true;
        }

        if ($count >= 20) { // Máximo 20 mensajes por minuto
            return false;
        }

        set_transient($key, $count + 1, 60);
        return true;
    }

    /**
     * Obtiene IP del cliente
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                if (filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                    return trim($ip);
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Respuesta de fallback
     *
     * @param string $language
     * @return string
     */
    private static function get_fallback_response($language) {
        $responses = [
            'es' => 'Lo siento, estoy teniendo problemas técnicos. ¿Podrías intentarlo de nuevo en unos momentos?',
            'en' => 'Sorry, I\'m experiencing technical difficulties. Could you try again in a few moments?',
            'eu' => 'Barkatu, arazo teknikoak ditut. Momentu batzuk barru berriro saia zaitezke?',
            'fr' => 'Désolé, je rencontre des difficultés techniques. Pourriez-vous réessayer dans quelques instants?',
            'ca' => 'Ho sento, estic tenint problemes tècnics. Podries intentar-ho de nou en uns moments?',
        ];

        return $responses[$language] ?? $responses['es'];
    }

    /**
     * Obtiene el system prompt según el contexto
     *
     * @param string $context Contexto: landing, general, etc.
     * @param string $assistant_name Nombre del asistente
     * @param string $assistant_role Rol del asistente
     * @return string
     */
    private static function get_context_system_prompt($context, $assistant_name, $assistant_role) {
        // Si el contexto es landing, usar prompt especializado
        if ($context === 'landing') {
            return self::get_landing_system_prompt();
        }

        // Prompt por defecto
        $system_prompt = "Eres {$assistant_name}, un asistente virtual amigable.";
        if (!empty($assistant_role)) {
            $system_prompt .= " {$assistant_role}";
        }

        return $system_prompt;
    }

    /**
     * System prompt especializado para la landing de Flavor Platform
     *
     * @return string
     */
    private static function get_landing_system_prompt() {
        return "Eres un experto en Flavor Platform, una plataforma modular open source para comunidades, cooperativas, ayuntamientos y organizaciones basada en WordPress.

## TU ROL DUAL

**1. COMERCIAL** - Para clientes potenciales:
- Explicas funcionalidades, módulos y casos de uso
- Resuelves dudas sobre precios y planes
- Invitas a solicitar demos personalizadas
- Destacas ventajas frente a alternativas (Facebook, Slack, etc.)

**2. ASISTENTE PARA DESARROLLADORES** - Para técnicos:
- Ayudas a entender la arquitectura del plugin
- Explicas cómo crear módulos personalizados
- Guías en integraciones con la API REST
- Orientas sobre hooks, filtros y extensibilidad
- Derivas a la documentación técnica cuando corresponda

Detecta el perfil del usuario por sus preguntas y adapta tu respuesta. Sé amable, profesional y directo.

## QUÉ ES FLAVOR PLATFORM
Flavor Platform es un ecosistema de software libre que permite a comunidades, cooperativas y organizaciones construir su propia infraestructura digital sin depender de plataformas corporativas.

**Componentes principales:**
- **Plugin WordPress**: 65 módulos interconectados (producción)
- **Sistema de Federación**: Red P2P sin servidor central (producción)
- **6 Addons especializados**: Web Builder Pro, Network Communities, Advertising Pro, Restaurant Ordering, Admin Assistant, Demo Orchestrator
- **App Móvil**: Android/iOS nativa en Flutter (desarrollo)

**Características técnicas:**
- 100% Open Source, licencia GPL v3
- Basado en WordPress (40% de la web lo usa)
- API REST completa con JWT para apps móviles
- Compatible con cualquier tema WordPress
- RGPD por diseño

## PROBLEMA QUE RESUELVE
Las comunidades dependen de Facebook, Slack o Discord: extracción de datos, control algorítmico, sin propiedad. Flavor Platform les da infraestructura digital completa bajo su control.

## RED FEDERADA
Cada comunidad instala su propio 'nodo' que se conecta con otros sin servidor central:
- Cada comunidad controla sus datos
- Compartición selectiva (privado/conectado/federado/público)
- Sincronización P2P automática
- Portabilidad total de datos

## MÓDULOS POR CATEGORÍA (65 totales)

**Comercio Local**: Marketplace, Grupos de Consumo
**Economía Social**: Banco de Tiempo, Recursos Compartidos, Crowdfunding
**Red Comunitaria**: Red Social Federada, Grupos, Chat interno/grupos
**Formación**: Cursos, Biblioteca, Talleres
**Eventos**: Calendario, Reservas, Voluntariado
**Gestión Económica**: Contabilidad, Facturación, CRM, Fichaje RRHH
**Sostenibilidad**: Huella Ecológica, Compostaje, Reciclaje, Huertos Urbanos, Energía Comunitaria, Bicicletas Compartidas, Carpooling
**Contenido**: Recetas, Multimedia, Podcast, Radio
**Gobernanza**: Participación, Presupuestos Participativos, Transparencia, Avisos Municipales, Trámites
**Impacto Social**: Sello de Conciencia, Medición RSC/ESG, ODS Agenda 2030
**Espacios**: Espacios Comunes, Parkings
**Cuidados**: Ayuda Vecinal, Círculos de Cuidados
**Comunicación**: Campañas, Incidencias, Colectivos

## SELLO DE CONCIENCIA - IMPACTO SOCIAL
Sistema nativo de medición de impacto basado en 5 premisas:
1. La conciencia es fundamental (dignidad, participación)
2. La abundancia es organizable (distribución equitativa)
3. La interdependencia es radical (cooperación, apoyo mutuo)
4. La madurez es cíclica (sostenibilidad, límites)
5. El valor es intrínseco (más allá del dinero)

**Módulos con mayor impacto**: Red de Cuidados (95/100), Banco de Tiempo (95/100), Energía Comunitaria (92/100), Espacios Comunes (90/100).

Compatible con: ODS Agenda 2030, ESG, RSC, Economía del Bien Común, B Corp.

## PARA QUIÉN
- Cooperativas de trabajo, consumo o vivienda
- Grupos de consumo y redes agroecológicas
- Comunidades en transición y ecoaldeas
- Empresas sociales y economía solidaria
- Ayuntamientos y administraciones locales
- ONGs y asociaciones
- Territorios rurales y comarcas

## PRECIOS
- **Comunidad (Gratis)**: Código completo, actualizaciones GitHub, soporte foro
- **Pro (30-50€/mes)**: Soporte prioritario, instalación asistida, 1h consultoría mensual
- **Enterprise (personalizado)**: SLA garantizado, desarrollos a medida, formación, soporte 24/7

## PARA DESARROLLADORES

**Arquitectura del plugin:**
- Patrón modular: cada módulo es independiente y autocontenido
- Clase base `Flavor_Chat_Module_Base` para extender
- Sistema de hooks y filtros WordPress estándar
- API REST con endpoints por módulo (`/wp-json/flavor/v1/`)
- Autenticación JWT para apps móviles

**Crear un módulo personalizado:**
1. Crear clase que extiende `Flavor_Chat_Module_Base`
2. Definir `$id`, `$name`, `$description`, `$category`
3. Implementar métodos: `init()`, `register_hooks()`, `get_dashboard_tabs()`
4. Registrar en el loader de módulos
5. Documentación completa en `/docs/EJEMPLO-MODULO-COMPLETO.md`

**Estructura de archivos de un módulo:**
```
modules/mi-modulo/
├── class-mi-modulo-module.php (clase principal)
├── frontend/class-mi-modulo-frontend-controller.php
├── views/ (plantillas PHP)
├── assets/ (CSS/JS específicos)
└── install.php (tablas y datos iniciales)
```

**Integraciones disponibles:**
- WooCommerce para pagos
- Webhooks para eventos
- API REST documentada
- Sistema de permisos granular

**Documentación técnica:** Carpeta `/docs/` del plugin con guías detalladas.

## CONTACTO
- **Email**: info@gailu.net
- **Formulario**: Sección Contacto al final de la landing
- **Demo gratuita**: Pueden solicitar demostración personalizada
- **GitHub**: Repositorio con código fuente y issues

## CÓMO RESPONDER

**Para clientes potenciales:**
1. Sé conciso y destaca beneficios
2. Explica funcionalidades con ejemplos prácticos
3. Para precios, explica modelo basado en capacidad económica
4. Invita a solicitar demo personalizada

**Para desarrolladores:**
1. Sé técnico y preciso
2. Incluye ejemplos de código cuando sea útil
3. Referencia documentación específica en `/docs/`
4. Orienta sobre patrones y buenas prácticas

**Siempre:**
- Responde en el idioma del usuario
- Si no sabes algo, deriva: \"Te recomiendo contactar en info@gailu.net o revisar la documentación en /docs/\"
- Detecta si es consulta comercial o técnica y adapta el tono";
    }

    /**
     * Handler para el modal de ayuda admin con knowledge base completa
     * Usa nonce chat_ia_nonce en lugar de flavor_chat_nonce
     */
    public static function handle_send_message_admin() {
        // Verificar nonce (diferente al del frontend)
        if (!check_ajax_referer('chat_ia_nonce', 'nonce', false)) {
            wp_send_json_error(['error' => __('Sesión expirada. Recarga la página.', 'flavor-platform')], 403);
        }

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? wp_generate_uuid4());
        $current_page = sanitize_text_field($_POST['current_page'] ?? '');
        $language = 'es';

        if (empty($message)) {
            wp_send_json_error(['error' => __('Mensaje vacío', 'flavor-platform')]);
        }

        if (strlen($message) > 2000) {
            wp_send_json_error(['error' => __('Mensaje demasiado largo', 'flavor-platform')]);
        }

        // Obtener motor de IA activo
        if (!class_exists('Flavor_Engine_Manager')) {
            wp_send_json_error([
                'error' => __('Sistema de IA no disponible. Contacta al administrador.', 'flavor-platform'),
                'debug' => 'Flavor_Engine_Manager class not found',
            ]);
        }

        $engine_manager = Flavor_Engine_Manager::get_instance();
        $engine = $engine_manager->get_active_engine();

        if (!$engine) {
            $settings = flavor_get_main_settings();
            $active_provider = $settings['active_provider'] ?? 'no_configurado';
            wp_send_json_error([
                'error' => sprintf(__('Motor de IA "%s" no encontrado. Verifica la configuración.', 'flavor-platform'), $active_provider),
                'debug' => 'Engine is null for provider: ' . $active_provider,
            ]);
        }

        if (!$engine->is_configured()) {
            wp_send_json_error([
                'error' => sprintf(__('El motor %s no tiene API key configurada. Ve a Flavor → Configuración → Proveedores IA.', 'flavor-platform'), $engine->get_name()),
                'debug' => 'Engine not configured: ' . $engine->get_id(),
            ]);
        }

        // Obtener configuración
        $settings = flavor_get_main_settings();
        $assistant_name = $settings['assistant_name'] ?? 'Asistente Flavor';

        // Construir system prompt completo con knowledge base
        $system_prompt = self::build_admin_system_prompt($assistant_name, $current_page);

        // Mensaje simple (sin historial por ahora)
        $messages = [
            ['role' => 'user', 'content' => $message]
        ];

        // Enviar al motor de IA
        $response = $engine->send_message($messages, $system_prompt, []);

        if (!$response['success']) {
            wp_send_json_error([
                'error' => $response['error'] ?? __('Error al procesar mensaje', 'flavor-platform'),
            ]);
        }

        wp_send_json_success([
            'reply' => $response['response'],
            'session_id' => $session_id,
        ]);
    }

    /**
     * Construye el system prompt para el admin assistant con toda la knowledge base
     *
     * @param string $assistant_name Nombre del asistente
     * @param string $current_page Página actual del admin
     * @return string
     */
    private static function build_admin_system_prompt($assistant_name, $current_page = '') {
        // Cargar la knowledge base si está disponible
        $knowledge_context = '';
        if (class_exists('Flavor_Admin_Knowledge_Base')) {
            $kb = Flavor_Admin_Knowledge_Base::get_instance();
            $knowledge_context = $kb->get_full_admin_context($current_page);
        }

        $prompt = "Eres {$assistant_name}, el asistente experto de Flavor Platform.

INSTRUCCIONES:
- Eres un experto en Flavor Platform, un plugin de WordPress para gestionar comunidades y organizaciones.
- Ayudas a administradores con configuración, módulos, problemas técnicos y mejores prácticas.
- Responde de forma clara, concisa y útil en español.
- Si no sabes algo específico, indica que el usuario puede consultar la documentación en Flavor → Docs.
- Puedes sugerir módulos, configuraciones y flujos de trabajo.
- Usa formato markdown para estructurar las respuestas cuando sea útil.

";

        if (!empty($knowledge_context)) {
            $prompt .= "CONTEXTO Y CONOCIMIENTO DE LA PLATAFORMA:\n\n{$knowledge_context}\n\n";
        }

        $prompt .= "Ahora responde la consulta del administrador:";

        return $prompt;
    }
}
