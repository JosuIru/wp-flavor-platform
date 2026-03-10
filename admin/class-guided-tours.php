<?php
/**
 * Sistema de Tours Guiados Interactivos Mejorado
 *
 * Tours paso a paso para ayudar a usuarios nuevos a familiarizarse
 * con las funcionalidades de Flavor Platform
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para tours guiados
 *
 * @since 3.0.0
 */
class Flavor_Guided_Tours {

    /**
     * Instancia singleton
     *
     * @var Flavor_Guided_Tours
     */
    private static $instancia = null;

    /**
     * Tours disponibles
     *
     * @var array
     */
    private $tours = [];

    /**
     * Meta key para guardar tours completados
     *
     * @var string
     */
    const META_KEY_COMPLETED = 'flavor_completed_tours';

    /**
     * Meta key para guardar progreso de tours
     *
     * @var string
     */
    const META_KEY_PROGRESS = 'flavor_tour_progress';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Guided_Tours
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->registrar_tours();
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Cargar assets solo en admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_tour_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_complete_tour', [$this, 'ajax_complete_tour']);
        add_action('wp_ajax_flavor_reset_tour', [$this, 'ajax_reset_tour']);
        add_action('wp_ajax_flavor_reset_all_tours', [$this, 'ajax_reset_all_tours']);
        add_action('wp_ajax_flavor_save_tour_progress', [$this, 'ajax_save_tour_progress']);
        add_action('wp_ajax_flavor_get_tour_data', [$this, 'ajax_get_tour_data']);
        add_action('wp_ajax_flavor_dismiss_tour', [$this, 'ajax_dismiss_tour']);

        // Mostrar tours disponibles en cada página
        add_action('admin_footer', [$this, 'render_tour_launcher']);

        // NOTA: El menú se registra centralizadamente en class-admin-menu-manager.php
        // add_action('admin_menu', [$this, 'add_tours_submenu'], 99);
    }

    /**
     * Registra todos los tours disponibles
     *
     * @return void
     */
    private function registrar_tours() {
        // Tour del Dashboard
        $this->tours['tour_dashboard'] = [
            'id' => 'tour_dashboard',
            'titulo' => __('Tour del Dashboard', 'flavor-chat-ia'),
            'descripcion' => __('Conoce el panel principal y sus funcionalidades', 'flavor-chat-ia'),
            'icono' => 'dashicons-dashboard',
            'duracion' => '2 min',
            'paginas' => ['toplevel_page_flavor-dashboard', 'flavor-platform_page_flavor-dashboard'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.flavor-dashboard-header, .flavor-dashboard-wrapper > h1',
                    'titulo' => __('Bienvenido al Dashboard', 'flavor-chat-ia'),
                    'contenido' => __('Este es tu centro de control. Desde aquí puedes ver estadísticas, acceder a configuraciones y gestionar toda la plataforma.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.flavor-dashboard-hero, .dashboard-hero, .app-profile-card, .flavor-active-app',
                    'titulo' => __('Tu App Activa', 'flavor-chat-ia'),
                    'contenido' => __('Aquí ves el perfil de app activo actualmente, con información sobre módulos activos, addons y usuarios.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-metrics-grid, .flavor-widget-metrics',
                    'titulo' => __('Métricas en Tiempo Real', 'flavor-chat-ia'),
                    'contenido' => __('Métricas importantes de tu plataforma: usuarios activos, módulos, conversaciones IA y más.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-widget-system, .flavor-health-semaphore',
                    'titulo' => __('Estado del Sistema', 'flavor-chat-ia'),
                    'contenido' => __('Monitorea la salud de tu sistema, versiones y estado de la API.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-widget-alerts, .flavor-alerts-list',
                    'titulo' => __('Alertas y Notificaciones', 'flavor-chat-ia'),
                    'contenido' => __('Aquí aparecen las alertas pendientes que requieren tu atención.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '#adminmenu .toplevel_page_flavor-dashboard, #adminmenu [href*="flavor-dashboard"]',
                    'titulo' => __('Menú de Navegación', 'flavor-chat-ia'),
                    'contenido' => __('Desde el menú lateral puedes acceder a todas las secciones de Flavor Platform.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
            ],
        ];

        // Tour de Módulos
        $this->tours['tour_modulos'] = [
            'id' => 'tour_modulos',
            'titulo' => __('Tour de Módulos', 'flavor-chat-ia'),
            'descripcion' => __('Aprende a activar y configurar módulos especializados', 'flavor-chat-ia'),
            'icono' => 'dashicons-admin-plugins',
            'duracion' => '3 min',
            'paginas' => ['flavor-platform_page_flavor-module-dashboards', 'toplevel_page_flavor-module-dashboards'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.flavor-modules-header, .wrap > h1',
                    'titulo' => __('Gestión de Módulos', 'flavor-chat-ia'),
                    'contenido' => __('Los módulos extienden las capacidades de tu chat IA. Cada módulo añade funcionalidades específicas como reservas, productos, citas, etc.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.flavor-module-card:first-child, .flavor-addon-card:first-child',
                    'titulo' => __('Tarjetas de Módulo', 'flavor-chat-ia'),
                    'contenido' => __('Cada módulo tiene su propia tarjeta con información sobre su estado, descripción y opciones de configuración.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-module-toggle, .flavor-addon-toggle, .module-status-toggle',
                    'titulo' => __('Activar/Desactivar', 'flavor-chat-ia'),
                    'contenido' => __('Usa este interruptor para activar o desactivar módulos. Los requisitos se verifican automáticamente.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-module-config, .flavor-addon-settings, .module-settings-btn',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'contenido' => __('Accede a la configuración específica de cada módulo para personalizarlo según tus necesidades.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.flavor-module-dependencies, .module-requirements',
                    'titulo' => __('Dependencias', 'flavor-chat-ia'),
                    'contenido' => __('Algunos módulos requieren otros módulos o plugins para funcionar. Aquí se muestran los requisitos.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
            ],
        ];

        // Tour de Diseño
        $this->tours['tour_diseno'] = [
            'id' => 'tour_diseno',
            'titulo' => __('Tour de Diseño', 'flavor-chat-ia'),
            'descripcion' => __('Personaliza colores, tipografías y apariencia del chat', 'flavor-chat-ia'),
            'icono' => 'dashicons-art',
            'duracion' => '4 min',
            'paginas' => ['flavor-platform_page_flavor-design-settings', 'toplevel_page_flavor-design-settings'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.flavor-design-header, .wrap > h1',
                    'titulo' => __('Personalización de Diseño', 'flavor-chat-ia'),
                    'contenido' => __('Aquí puedes personalizar completamente la apariencia de tu chat y landing pages para que coincidan con tu marca.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.flavor-color-picker, .color-palette-section, input[type="color"]',
                    'titulo' => __('Paleta de Colores', 'flavor-chat-ia'),
                    'contenido' => __('Define los colores principales, secundarios y de acento. Puedes usar colores predefinidos o crear tu propia paleta.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '.flavor-typography-section, .typography-settings, select[name*="font"]',
                    'titulo' => __('Tipografía', 'flavor-chat-ia'),
                    'contenido' => __('Selecciona las fuentes para títulos y texto. Disponemos de Google Fonts y fuentes del sistema.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-layout-options, .layout-settings, .chat-position-selector',
                    'titulo' => __('Posición y Layout', 'flavor-chat-ia'),
                    'contenido' => __('Configura dónde aparecerá el chat, su tamaño y comportamiento en diferentes dispositivos.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-preview-section, .design-preview, #preview-container',
                    'titulo' => __('Vista Previa en Vivo', 'flavor-chat-ia'),
                    'contenido' => __('Ve los cambios en tiempo real antes de guardarlos. La vista previa muestra cómo se verá el chat en tu sitio.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-theme-presets, .preset-themes',
                    'titulo' => __('Temas Predefinidos', 'flavor-chat-ia'),
                    'contenido' => __('Usa uno de nuestros temas predefinidos como punto de partida y personalízalo a tu gusto.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour de Landing Pages
        $this->tours['tour_landing'] = [
            'id' => 'tour_landing',
            'titulo' => __('Tour de Landing Pages', 'flavor-chat-ia'),
            'descripcion' => __('Crea páginas de aterrizaje para tus aplicaciones', 'flavor-chat-ia'),
            'icono' => 'dashicons-welcome-widgets-menus',
            'duracion' => '5 min',
            'paginas' => ['flavor-platform_page_flavor-landing-editor', 'toplevel_page_flavor-landing-editor'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap > h1, .page-title-action',
                    'titulo' => __('Gestión de Landing Pages', 'flavor-chat-ia'),
                    'contenido' => __('Crea landing pages optimizadas para promocionar tus aplicaciones móviles con descargas directas.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.page-title-action, a[href*="post-new.php"]',
                    'titulo' => __('Crear Nueva Landing', 'flavor-chat-ia'),
                    'contenido' => __('Haz clic aquí para crear una nueva landing page. Podrás elegir entre varias plantillas profesionales.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-template-selector, #template-chooser',
                    'titulo' => __('Selector de Plantillas', 'flavor-chat-ia'),
                    'contenido' => __('Elige entre plantillas modernas optimizadas para conversión. Cada una está diseñada para diferentes tipos de apps.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '.flavor-landing-meta, #flavor_landing_metabox',
                    'titulo' => __('Configuración de la Landing', 'flavor-chat-ia'),
                    'contenido' => __('Configura los enlaces de descarga, capturas de pantalla, características y toda la información de tu app.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-app-links, .download-buttons-section',
                    'titulo' => __('Enlaces de Descarga', 'flavor-chat-ia'),
                    'contenido' => __('Añade los enlaces a App Store, Google Play o descargas directas de APK.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.flavor-screenshots, .gallery-section',
                    'titulo' => __('Galería de Capturas', 'flavor-chat-ia'),
                    'contenido' => __('Sube capturas de pantalla de tu app. Se mostrarán en un carrusel atractivo.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
            ],
        ];

        // Tour de Red de Nodos
        $this->tours['tour_red_nodos'] = [
            'id' => 'tour_red_nodos',
            'titulo' => __('Tour de la Red de Nodos', 'flavor-chat-ia'),
            'descripcion' => __('Conecta tu sitio a la red distribuida de Flavor', 'flavor-chat-ia'),
            'icono' => 'dashicons-networking',
            'duracion' => '3 min',
            'paginas' => ['flavor-platform_page_flavor-network', 'toplevel_page_flavor-network'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.flavor-network-header, .wrap > h1',
                    'titulo' => __('Red de Nodos', 'flavor-chat-ia'),
                    'contenido' => __('La red de nodos te permite conectar múltiples instalaciones de Flavor Platform, compartir recursos y sincronizar datos.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.flavor-node-status, .network-status-indicator',
                    'titulo' => __('Estado de Conexión', 'flavor-chat-ia'),
                    'contenido' => __('Aquí puedes ver si tu nodo está conectado a la red y el estado de la sincronización.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '.flavor-join-network, .network-join-btn',
                    'titulo' => __('Unirse a la Red', 'flavor-chat-ia'),
                    'contenido' => __('Conecta tu instalación a la red principal para beneficiarte de recursos compartidos y actualizaciones.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-node-key, .api-key-section',
                    'titulo' => __('Clave de Nodo', 'flavor-chat-ia'),
                    'contenido' => __('Tu clave única de nodo. Mantéela segura ya que identifica tu instalación en la red.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-sync-settings, .sync-options',
                    'titulo' => __('Opciones de Sincronización', 'flavor-chat-ia'),
                    'contenido' => __('Configura qué datos sincronizar: prompts, plantillas, configuraciones y más.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour de Configuración de Chat IA
        $this->tours['tour_chat_ia'] = [
            'id' => 'tour_chat_ia',
            'titulo' => __('Tour de Configuración IA', 'flavor-chat-ia'),
            'descripcion' => __('Configura el motor de IA y personaliza las respuestas', 'flavor-chat-ia'),
            'icono' => 'dashicons-format-chat',
            'duracion' => '4 min',
            'paginas' => ['flavor-platform_page_flavor-chat-config', 'toplevel_page_flavor-chat-config'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.flavor-chat-header, .wrap > h1',
                    'titulo' => __('Configuración del Chat IA', 'flavor-chat-ia'),
                    'contenido' => __('Aquí configuras el cerebro de tu chat: el motor de IA, modelo, personalidad y comportamiento.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => 'select[name*="engine"], .engine-selector, #ai-engine-select',
                    'titulo' => __('Motor de IA', 'flavor-chat-ia'),
                    'contenido' => __('Elige entre diferentes proveedores: Claude (Anthropic), OpenAI, DeepSeek, Mistral u Ollama local.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => 'input[name*="api_key"], .api-key-field, #api-key-input',
                    'titulo' => __('API Key', 'flavor-chat-ia'),
                    'contenido' => __('Ingresa tu API key del proveedor. Se almacena de forma segura y encriptada.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => 'select[name*="model"], .model-selector, #model-select',
                    'titulo' => __('Modelo de IA', 'flavor-chat-ia'),
                    'contenido' => __('Selecciona qué versión del modelo usar. Los modelos más recientes ofrecen mejores respuestas.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => 'textarea[name*="system_prompt"], .system-prompt-field, #system-prompt',
                    'titulo' => __('Prompt del Sistema', 'flavor-chat-ia'),
                    'contenido' => __('Define la personalidad y comportamiento del chat. Este texto guía todas las respuestas.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-token-settings, .token-limits, #token-config',
                    'titulo' => __('Límites de Tokens', 'flavor-chat-ia'),
                    'contenido' => __('Configura la longitud máxima de respuestas y el contexto de conversación.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour de Perfiles de Apps
        $this->tours['tour_app_profiles'] = [
            'id' => 'tour_app_profiles',
            'titulo' => __('Tour de Perfiles de Apps', 'flavor-chat-ia'),
            'descripcion' => __('Gestiona perfiles para tus aplicaciones móviles', 'flavor-chat-ia'),
            'icono' => 'dashicons-smartphone',
            'duracion' => '3 min',
            'paginas' => ['flavor-platform_page_flavor-apps-config', 'toplevel_page_flavor-apps-config'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.flavor-apps-header, .wrap > h1',
                    'titulo' => __('Perfiles de Aplicaciones', 'flavor-chat-ia'),
                    'contenido' => __('Crea perfiles que definen cómo se comporta Flavor en diferentes apps o contextos.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.flavor-app-card:first-child, .app-profile-item:first-child',
                    'titulo' => __('Perfil de App', 'flavor-chat-ia'),
                    'contenido' => __('Cada perfil tiene su propia configuración de chat, diseño y módulos activos.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '.flavor-app-pairing, .pairing-section',
                    'titulo' => __('Vincular con App', 'flavor-chat-ia'),
                    'contenido' => __('Conecta tu app móvil escaneando un código QR o ingresando el código de vinculación.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
            ],
        ];

        // =====================================================
        // TOURS DEMO - Tejido Empresarial y Emprendimiento
        // =====================================================

        // Tour: Demo Completo (Admin)
        $this->tours['demo_admin'] = [
            'id' => 'demo_admin',
            'titulo' => __('Demo - Panel Admin', 'flavor-chat-ia'),
            'descripcion' => __('Recorrido completo por las funcionalidades de administracion del ecosistema', 'flavor-chat-ia'),
            'icono' => 'dashicons-building',
            'duracion' => '5 min',
            'paginas' => ['toplevel_page_flavor-chat-ia', 'flavor-platform_page_flavor-dashboard', 'toplevel_page_flavor-dashboard'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '#adminmenu .toplevel_page_flavor-chat-ia, #adminmenu [href*="flavor"]',
                    'titulo' => __('Menu Flavor Platform', 'flavor-chat-ia'),
                    'contenido' => __('Todo el ecosistema se gestiona desde este menu. Configuracion, modulos, usuarios, contenido... cada seccion tiene su area dedicada.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.wrap h1, .flavor-dashboard-header',
                    'titulo' => __('Panel de Control', 'flavor-chat-ia'),
                    'contenido' => __('Vision general del ecosistema: usuarios activos, modulos habilitados, actividad reciente. Ideal para monitorear el uso de la plataforma.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.nav-tab-wrapper, .flavor-tabs',
                    'titulo' => __('Configuracion por Pestanas', 'flavor-chat-ia'),
                    'contenido' => __('Cada aspecto de la plataforma se configura en su propia pestana: modulos activos, diseno, permisos, notificaciones...', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.submit, #submit, .button-primary',
                    'titulo' => __('Guardar Cambios', 'flavor-chat-ia'),
                    'contenido' => __('Todos los cambios se guardan de forma segura. El sistema valida automaticamente que no haya errores.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour: Marketplace para Emprendedores
        $this->tours['demo_marketplace'] = [
            'id' => 'demo_marketplace',
            'titulo' => __('Marketplace Local', 'flavor-chat-ia'),
            'descripcion' => __('Plataforma de compra-venta entre emprendedores y ciudadanos de la comunidad', 'flavor-chat-ia'),
            'icono' => 'dashicons-store',
            'duracion' => '4 min',
            'paginas' => ['flavor-platform_page_flavor-marketplace', 'toplevel_page_marketplace'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap h1, .marketplace-header',
                    'titulo' => __('Marketplace Local', 'flavor-chat-ia'),
                    'contenido' => __('Alternativa local a Wallapop/Amazon. Los emprendedores publican productos y servicios, los ciudadanos compran. El dinero se queda en el territorio.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.tablenav, .search-box, .marketplace-filters',
                    'titulo' => __('Busqueda y Filtros', 'flavor-chat-ia'),
                    'contenido' => __('Los usuarios pueden buscar por categoria, precio, ubicacion y tipo (venta, intercambio, regalo). Perfecto para encontrar proveedores locales.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.wp-list-table, .marketplace-items',
                    'titulo' => __('Listado de Anuncios', 'flavor-chat-ia'),
                    'contenido' => __('Cada anuncio muestra foto, precio, vendedor y estado. El admin puede moderar contenido inapropiado.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.page-title-action, .add-new',
                    'titulo' => __('Publicar Anuncio', 'flavor-chat-ia'),
                    'contenido' => __('Los emprendedores pueden publicar facilmente: foto, descripcion, precio y categoria. Sin comisiones, contacto directo.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
            ],
        ];

        // Tour: Banco de Tiempo
        $this->tours['demo_banco_tiempo'] = [
            'id' => 'demo_banco_tiempo',
            'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'),
            'descripcion' => __('Intercambio de servicios sin dinero: 1 hora dada = 1 hora recibida', 'flavor-chat-ia'),
            'icono' => 'dashicons-clock',
            'duracion' => '4 min',
            'paginas' => ['flavor-platform_page_flavor-banco-tiempo', 'toplevel_page_banco-tiempo'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap h1, .banco-tiempo-header',
                    'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'),
                    'contenido' => __('Sistema de intercambio de servicios sin dinero. Ideal para emprendedores que empiezan con poco capital. Un disenador hace un logo a cambio de asesoria fiscal.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.tablenav, .banco-tiempo-stats',
                    'titulo' => __('Estadisticas del Banco', 'flavor-chat-ia'),
                    'contenido' => __('Horas totales intercambiadas, usuarios activos, servicios mas demandados... Metricas utiles para reportar el impacto del programa.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.wp-list-table, .servicios-lista',
                    'titulo' => __('Servicios Disponibles', 'flavor-chat-ia'),
                    'contenido' => __('Cada usuario ofrece sus habilidades: diseno, reparaciones, formacion, cuidados, transporte... La comunidad decide el valor en horas.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour: Grupos de Consumo
        $this->tours['demo_grupos_consumo'] = [
            'id' => 'demo_grupos_consumo',
            'titulo' => __('Grupos de Consumo', 'flavor-chat-ia'),
            'descripcion' => __('Pedidos colectivos a productores locales de la zona', 'flavor-chat-ia'),
            'icono' => 'dashicons-carrot',
            'duracion' => '3 min',
            'paginas' => ['flavor-platform_page_flavor-grupos-consumo', 'toplevel_page_grupos-consumo'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap h1, .grupos-consumo-header',
                    'titulo' => __('Grupos de Consumo', 'flavor-chat-ia'),
                    'contenido' => __('Conecta productores agroalimentarios de la zona con consumidores. Pedidos colectivos semanales que reducen intermediarios y aseguran demanda al productor.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.wp-list-table, .grupos-lista',
                    'titulo' => __('Grupos Activos', 'flavor-chat-ia'),
                    'contenido' => __('Cada grupo tiene su ciclo (semanal, quincenal), punto de recogida (Plaza de los Fueros, por ejemplo) y productores asociados.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour: Talleres y Formacion
        $this->tours['demo_talleres'] = [
            'id' => 'demo_talleres',
            'titulo' => __('Formacion y Talleres', 'flavor-chat-ia'),
            'descripcion' => __('Capacitacion entre emprendedores de la comarca', 'flavor-chat-ia'),
            'icono' => 'dashicons-welcome-learn-more',
            'duracion' => '3 min',
            'paginas' => ['flavor-platform_page_flavor-talleres', 'flavor-platform_page_flavor-cursos', 'toplevel_page_talleres'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap h1, .talleres-header',
                    'titulo' => __('Talleres y Formacion', 'flavor-chat-ia'),
                    'contenido' => __('Los propios emprendedores comparten conocimiento: marketing digital, contabilidad, oficios... Pueden cobrar en euros o en horas de banco de tiempo.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.wp-list-table, .talleres-lista',
                    'titulo' => __('Catalogo de Formacion', 'flavor-chat-ia'),
                    'contenido' => __('Fecha, lugar, formador, plazas disponibles. El sistema gestiona inscripciones, lista de espera y certificados digitales.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour: Directorio de Emprendedores (Socios)
        $this->tours['demo_directorio'] = [
            'id' => 'demo_directorio',
            'titulo' => __('Directorio de Emprendedores', 'flavor-chat-ia'),
            'descripcion' => __('Mapa del tejido empresarial de la comunidad', 'flavor-chat-ia'),
            'icono' => 'dashicons-groups',
            'duracion' => '2 min',
            'paginas' => ['flavor-platform_page_flavor-socios', 'toplevel_page_socios'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap h1, .socios-header',
                    'titulo' => __('Directorio de Emprendedores', 'flavor-chat-ia'),
                    'contenido' => __('Cada emprendedor tiene su perfil completo: negocio, servicios, ubicacion, contacto. El ciudadano puede buscar "carpintero en Estella" y encontrarlo al instante.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.wp-list-table, .socios-lista',
                    'titulo' => __('Listado de Emprendedores', 'flavor-chat-ia'),
                    'contenido' => __('Filtrable por categoria, ubicacion, servicios. Perfecto para fomentar colaboraciones y que los negocios locales sean visibles online.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour: Red Social / Networking
        $this->tours['demo_networking'] = [
            'id' => 'demo_networking',
            'titulo' => __('Red Social de Emprendedores', 'flavor-chat-ia'),
            'descripcion' => __('LinkedIn local para la comunidad emprendedora', 'flavor-chat-ia'),
            'icono' => 'dashicons-networking',
            'duracion' => '2 min',
            'paginas' => ['flavor-platform_page_flavor-red-social', 'flavor-platform_page_flavor-comunidades', 'toplevel_page_red-social'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap h1, .red-social-header',
                    'titulo' => __('Red Social Interna', 'flavor-chat-ia'),
                    'contenido' => __('Espacio para que los emprendedores conecten, compartan oportunidades y colaboren. Como un LinkedIn pero local y cercano.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.wp-list-table, .comunidades-lista, .grupos-lista',
                    'titulo' => __('Grupos Tematicos', 'flavor-chat-ia'),
                    'contenido' => __('Grupos por sector (hosteleria, agricultura, servicios...) o por interes (sostenibilidad, digitalizacion...). Facilita el networking natural.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Permitir que addons/plugins registren sus propios tours
        $this->tours = apply_filters('flavor_guided_tours', $this->tours);
    }

    /**
     * Añade submenú para panel de tours
     *
     * @return void
     */
    public function add_tours_submenu() {
        add_submenu_page(
            'flavor-dashboard',
            __('Tours de Ayuda', 'flavor-chat-ia'),
            __('Tours', 'flavor-chat-ia'),
            'manage_options',
            'flavor-tours',
            [$this, 'render_tours_panel']
        );
    }

    /**
     * Renderiza el panel de tours
     *
     * @return void
     */
    public function render_tours_panel() {
        include FLAVOR_CHAT_IA_PATH . 'admin/views/tours-panel.php';
    }

    /**
     * Obtiene todos los tours registrados
     *
     * @return array
     */
    public function get_all_tours() {
        return $this->tours;
    }

    /**
     * Obtiene un tour específico
     *
     * @param string $tour_id ID del tour
     * @return array|null
     */
    public function get_tour($tour_id) {
        return isset($this->tours[$tour_id]) ? $this->tours[$tour_id] : null;
    }

    /**
     * Carga assets del sistema de tours
     *
     * @param string $hook_suffix Sufijo del hook
     * @return void
     */
    public function enqueue_tour_assets($hook_suffix) {
        // CSS del sistema de onboarding
        wp_enqueue_style(
            'flavor-onboarding',
            FLAVOR_CHAT_IA_URL . 'admin/css/onboarding.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // JavaScript del sistema de onboarding
        wp_enqueue_script(
            'flavor-onboarding',
            FLAVOR_CHAT_IA_URL . 'admin/js/onboarding.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // Datos para JavaScript
        $tours_for_js = [];
        foreach ($this->tours as $tour_id => $tour) {
            if (in_array($hook_suffix, $tour['paginas'])) {
                $tours_for_js[$tour_id] = $tour;
            }
        }

        wp_localize_script('flavor-onboarding', 'FlavorOnboardingData', [
            'tours' => $tours_for_js,
            'allTours' => $this->tours,
            'completedTours' => $this->get_completed_tours(),
            'tourProgress' => $this->get_tour_progress(),
            'dismissedTours' => $this->get_dismissed_tours(),
            'currentPage' => $hook_suffix,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_tour_nonce'),
            'strings' => [
                'next' => __('Siguiente', 'flavor-chat-ia'),
                'prev' => __('Anterior', 'flavor-chat-ia'),
                'finish' => __('Finalizar', 'flavor-chat-ia'),
                'skip' => __('Saltar tour', 'flavor-chat-ia'),
                'close' => __('Cerrar', 'flavor-chat-ia'),
                'stepOf' => __('Paso %1$d de %2$d', 'flavor-chat-ia'),
                'startTour' => __('Iniciar Tour', 'flavor-chat-ia'),
                'tourCompleted' => __('Tour Completado', 'flavor-chat-ia'),
                'dontShowAgain' => __('No mostrar de nuevo', 'flavor-chat-ia'),
                'restartTour' => __('Reiniciar Tour', 'flavor-chat-ia'),
                'helpButton' => __('Ayuda', 'flavor-chat-ia'),
                'watchVideo' => __('Ver Video', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Renderiza el lanzador de tours
     *
     * @return void
     */
    public function render_tour_launcher() {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        $tours_disponibles = [];
        foreach ($this->tours as $tour_id => $tour) {
            if (!empty($tour['paginas']) && in_array($screen->id, $tour['paginas'])) {
                $tours_disponibles[$tour_id] = $tour;
            }
        }

        $completados = $this->get_completed_tours();
        $dismissed = $this->get_dismissed_tours();

        // Verificar si hay tours sin completar y no descartados para mostrar promo
        $tours_pendientes = [];
        foreach ($tours_disponibles as $tour_id => $tour) {
            if (!in_array($tour_id, $completados) && !in_array($tour_id, $dismissed)) {
                $tours_pendientes[$tour_id] = $tour;
            }
        }

        ?>
        <!-- Botón flotante de ayuda -->
        <div id="flavor-help-launcher" class="flavor-help-launcher">
            <button class="flavor-help-btn" title="<?php esc_attr_e('Ayuda y Tours', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-editor-help"></span>
            </button>
            <div class="flavor-help-menu">
                <div class="flavor-help-menu-header">
                    <h3><?php esc_html_e('Centro de Ayuda', 'flavor-chat-ia'); ?></h3>
                </div>
                <?php if (!empty($tours_disponibles)): ?>
                    <div class="flavor-help-section">
                        <h4><?php esc_html_e('Tours Disponibles', 'flavor-chat-ia'); ?></h4>
                        <?php foreach ($tours_disponibles as $tour_id => $tour):
                            $is_completed = in_array($tour_id, $completados);
                        ?>
                            <div class="flavor-help-item <?php echo $is_completed ? 'completed' : ''; ?>"
                                 data-tour-id="<?php echo esc_attr($tour_id); ?>">
                                <span class="dashicons <?php echo esc_attr($tour['icono'] ?? 'dashicons-info'); ?>"></span>
                                <div class="flavor-help-item-content">
                                    <span class="flavor-help-item-title"><?php echo esc_html($tour['titulo']); ?></span>
                                    <span class="flavor-help-item-duration"><?php echo esc_html($tour['duracion'] ?? ''); ?></span>
                                </div>
                                <?php if ($is_completed): ?>
                                    <span class="dashicons dashicons-yes-alt flavor-check"></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="flavor-help-section">
                    <h4><?php esc_html_e('Recursos', 'flavor-chat-ia'); ?></h4>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-tours')); ?>" class="flavor-help-item">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <span class="flavor-help-item-title"><?php esc_html_e('Ver Todos los Tours', 'flavor-chat-ia'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-docs')); ?>" class="flavor-help-item">
                        <span class="dashicons dashicons-book"></span>
                        <span class="flavor-help-item-title"><?php esc_html_e('Documentación', 'flavor-chat-ia'); ?></span>
                    </a>
                </div>
            </div>
        </div>

        <?php
        // Mostrar notificación de tour pendiente si es primera visita
        if (!empty($tours_pendientes)):
            $first_tour = reset($tours_pendientes);
            $first_tour_id = key($tours_pendientes);
        ?>
        <div id="flavor-tour-notification" class="flavor-tour-notification" data-tour-id="<?php echo esc_attr($first_tour_id); ?>">
            <div class="flavor-tour-notification-content">
                <span class="dashicons <?php echo esc_attr($first_tour['icono'] ?? 'dashicons-info'); ?>"></span>
                <div class="flavor-tour-notification-text">
                    <strong><?php echo esc_html($first_tour['titulo']); ?></strong>
                    <p><?php echo esc_html($first_tour['descripcion']); ?></p>
                </div>
                <div class="flavor-tour-notification-actions">
                    <button class="button button-primary flavor-start-tour-btn">
                        <?php esc_html_e('Iniciar Tour', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="button flavor-dismiss-tour-btn">
                        <?php esc_html_e('Ahora no', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <button class="flavor-tour-notification-close" title="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Modal para videos tutoriales -->
        <div id="flavor-video-modal" class="flavor-video-modal">
            <div class="flavor-video-modal-content">
                <button class="flavor-video-modal-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
                <div class="flavor-video-modal-body">
                    <iframe id="flavor-video-iframe" src="" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Marca un tour como completado
     *
     * @return void
     */
    public function ajax_complete_tour() {
        check_ajax_referer('flavor_tour_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos', 'flavor-chat-ia')]);
        }

        $tour_id = sanitize_text_field($_POST['tour_id'] ?? '');
        if (empty($tour_id)) {
            wp_send_json_error(['message' => __('ID de tour no válido', 'flavor-chat-ia')]);
        }

        $completados = $this->get_completed_tours();

        if (!in_array($tour_id, $completados)) {
            $completados[] = $tour_id;
            update_user_meta(get_current_user_id(), self::META_KEY_COMPLETED, $completados);

            // Limpiar progreso del tour completado
            $progress = $this->get_tour_progress();
            unset($progress[$tour_id]);
            update_user_meta(get_current_user_id(), self::META_KEY_PROGRESS, $progress);
        }

        wp_send_json_success(['completed' => $completados]);
    }

    /**
     * AJAX: Reinicia un tour específico
     *
     * @return void
     */
    public function ajax_reset_tour() {
        check_ajax_referer('flavor_tour_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos', 'flavor-chat-ia')]);
        }

        $tour_id = sanitize_text_field($_POST['tour_id'] ?? '');
        if (empty($tour_id)) {
            wp_send_json_error(['message' => __('ID de tour no válido', 'flavor-chat-ia')]);
        }

        // Quitar de completados
        $completados = $this->get_completed_tours();
        $key = array_search($tour_id, $completados);
        if ($key !== false) {
            unset($completados[$key]);
            update_user_meta(get_current_user_id(), self::META_KEY_COMPLETED, array_values($completados));
        }

        // Quitar de descartados
        $dismissed = $this->get_dismissed_tours();
        $key = array_search($tour_id, $dismissed);
        if ($key !== false) {
            unset($dismissed[$key]);
            update_user_meta(get_current_user_id(), 'flavor_dismissed_tours', array_values($dismissed));
        }

        // Limpiar progreso
        $progress = $this->get_tour_progress();
        unset($progress[$tour_id]);
        update_user_meta(get_current_user_id(), self::META_KEY_PROGRESS, $progress);

        wp_send_json_success(['message' => __('Tour reiniciado', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Reinicia todos los tours
     *
     * @return void
     */
    public function ajax_reset_all_tours() {
        check_ajax_referer('flavor_tour_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos', 'flavor-chat-ia')]);
        }

        delete_user_meta(get_current_user_id(), self::META_KEY_COMPLETED);
        delete_user_meta(get_current_user_id(), self::META_KEY_PROGRESS);
        delete_user_meta(get_current_user_id(), 'flavor_dismissed_tours');

        wp_send_json_success(['message' => __('Todos los tours han sido reiniciados', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Guarda el progreso de un tour
     *
     * @return void
     */
    public function ajax_save_tour_progress() {
        check_ajax_referer('flavor_tour_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos', 'flavor-chat-ia')]);
        }

        $tour_id = sanitize_text_field($_POST['tour_id'] ?? '');
        $step = absint($_POST['step'] ?? 0);

        if (empty($tour_id)) {
            wp_send_json_error(['message' => __('ID de tour no válido', 'flavor-chat-ia')]);
        }

        $progress = $this->get_tour_progress();
        $progress[$tour_id] = [
            'step' => $step,
            'timestamp' => time(),
        ];
        update_user_meta(get_current_user_id(), self::META_KEY_PROGRESS, $progress);

        wp_send_json_success(['progress' => $progress]);
    }

    /**
     * AJAX: Obtiene datos de un tour
     *
     * @return void
     */
    public function ajax_get_tour_data() {
        check_ajax_referer('flavor_tour_nonce', 'nonce');

        $tour_id = sanitize_text_field($_POST['tour_id'] ?? '');
        if (empty($tour_id) || !isset($this->tours[$tour_id])) {
            wp_send_json_error(['message' => __('Tour no encontrado', 'flavor-chat-ia')]);
        }

        $tour = $this->tours[$tour_id];
        $progress = $this->get_tour_progress();

        wp_send_json_success([
            'tour' => $tour,
            'progress' => $progress[$tour_id] ?? null,
            'isCompleted' => in_array($tour_id, $this->get_completed_tours()),
        ]);
    }

    /**
     * AJAX: Descarta un tour (no mostrar de nuevo)
     *
     * @return void
     */
    public function ajax_dismiss_tour() {
        check_ajax_referer('flavor_tour_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos', 'flavor-chat-ia')]);
        }

        $tour_id = sanitize_text_field($_POST['tour_id'] ?? '');
        if (empty($tour_id)) {
            wp_send_json_error(['message' => __('ID de tour no válido', 'flavor-chat-ia')]);
        }

        $dismissed = $this->get_dismissed_tours();
        if (!in_array($tour_id, $dismissed)) {
            $dismissed[] = $tour_id;
            update_user_meta(get_current_user_id(), 'flavor_dismissed_tours', $dismissed);
        }

        wp_send_json_success(['dismissed' => $dismissed]);
    }

    /**
     * Obtiene tours completados del usuario actual
     *
     * @return array
     */
    public function get_completed_tours() {
        $completados = get_user_meta(get_current_user_id(), self::META_KEY_COMPLETED, true);
        return is_array($completados) ? $completados : [];
    }

    /**
     * Obtiene el progreso de tours del usuario actual
     *
     * @return array
     */
    public function get_tour_progress() {
        $progress = get_user_meta(get_current_user_id(), self::META_KEY_PROGRESS, true);
        return is_array($progress) ? $progress : [];
    }

    /**
     * Obtiene tours descartados del usuario actual
     *
     * @return array
     */
    public function get_dismissed_tours() {
        $dismissed = get_user_meta(get_current_user_id(), 'flavor_dismissed_tours', true);
        return is_array($dismissed) ? $dismissed : [];
    }

    /**
     * Verifica si un tour está completado
     *
     * @param string $tour_id ID del tour
     * @return bool
     */
    public function is_tour_completed($tour_id) {
        return in_array($tour_id, $this->get_completed_tours());
    }

    /**
     * Obtiene estadísticas de tours
     *
     * @return array
     */
    public function get_tour_stats() {
        $total = count($this->tours);
        $completed = count($this->get_completed_tours());
        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'remaining' => $total - $completed,
            'percentage' => $percentage,
        ];
    }
}
