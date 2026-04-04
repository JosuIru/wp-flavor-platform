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
        // Tour: Primeros Pasos (para usuarios nuevos)
        $this->tours['tour_primeros_pasos'] = [
            'id' => 'tour_primeros_pasos',
            'titulo' => __('Primeros Pasos', 'flavor-chat-ia'),
            'descripcion' => __('Introducción rápida para empezar a usar Flavor Platform', 'flavor-chat-ia'),
            'icono' => 'dashicons-welcome-learn-more',
            'duracion' => '3 min',
            'paginas' => ['toplevel_page_flavor-dashboard', 'flavor-platform_page_flavor-dashboard', 'toplevel_page_flavor-chat-ia'],
            'video_url' => '',
            'destacado' => true, // Marcar como tour prioritario para nuevos usuarios
            'pasos' => [
                [
                    'elemento' => '.flavor-dashboard-wrapper, .wrap',
                    'titulo' => __('¡Bienvenido a Flavor Platform!', 'flavor-chat-ia'),
                    'contenido' => __('Flavor es una plataforma completa para gestionar comunidades, asociaciones, ayuntamientos y organizaciones. En 3 minutos aprenderás lo básico para empezar.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '#adminmenu .toplevel_page_flavor-dashboard, #adminmenu [href*="flavor"]',
                    'titulo' => __('Tu Menú de Control', 'flavor-chat-ia'),
                    'contenido' => __('Todo lo que necesitas está en este menú lateral. Dashboard para ver el estado general, Módulos para activar funciones, y Configuración para personalizar.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '.flavor-dashboard-hero, .app-profile-card, .flavor-active-app, .dashboard-hero',
                    'titulo' => __('Tu Tipo de Organización', 'flavor-chat-ia'),
                    'contenido' => __('Flavor se adapta a ti. Ya seas una asociación vecinal, un ayuntamiento, una cooperativa o una empresa, hay un perfil con las herramientas que necesitas.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '#adminmenu [href*="module"], .modules-link, #adminmenu [href*="modulos"]',
                    'titulo' => __('Activa los Módulos que Necesites', 'flavor-chat-ia'),
                    'contenido' => __('Los módulos son las funcionalidades: Eventos, Cursos, Reservas, Miembros, Chat IA... Activa solo los que uses. Siempre puedes añadir más después.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '#adminmenu [href*="design"], #adminmenu [href*="diseno"]',
                    'titulo' => __('Personaliza los Colores', 'flavor-chat-ia'),
                    'contenido' => __('Adapta la apariencia a tu marca: colores, logo, tipografías. Tu plataforma, tu estilo.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '#flavor-help-launcher, .flavor-help-btn',
                    'titulo' => __('Ayuda Siempre Disponible', 'flavor-chat-ia'),
                    'contenido' => __('¿Tienes dudas? Este botón flotante te da acceso a más tours guiados y documentación. Estamos aquí para ayudarte.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-dashboard-wrapper, .wrap',
                    'titulo' => __('¡Listo para Empezar!', 'flavor-chat-ia'),
                    'contenido' => __('Ya conoces lo básico. Te recomendamos hacer el "Tour del Dashboard" para profundizar, o simplemente explora y descubre. ¡Bienvenido a la comunidad Flavor!', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
            ],
        ];

        // Tour del Dashboard
        $this->tours['tour_dashboard'] = [
            'id' => 'tour_dashboard',
            'titulo' => __('Tour del Dashboard', 'flavor-chat-ia'),
            'descripcion' => __('Conoce el panel principal y todas sus funcionalidades', 'flavor-chat-ia'),
            'icono' => 'dashicons-dashboard',
            'duracion' => '4 min',
            'paginas' => ['toplevel_page_flavor-dashboard', 'flavor-platform_page_flavor-dashboard'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.flavor-dashboard-header, .flavor-dashboard-wrapper > h1',
                    'titulo' => __('Bienvenido a Flavor Platform', 'flavor-chat-ia'),
                    'contenido' => __('Este es tu centro de control principal. Aquí tienes una visión completa del estado de tu plataforma, métricas clave y accesos rápidos a las funciones más importantes.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.flavor-dashboard-hero, .dashboard-hero, .app-profile-card, .flavor-active-app',
                    'titulo' => __('Perfil de Aplicación Activo', 'flavor-chat-ia'),
                    'contenido' => __('Muestra qué tipo de aplicación tienes configurada (Asociación, Ayuntamiento, Cooperativa...). Cada perfil activa módulos y funciones específicas para tu caso de uso.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-metrics-grid, .flavor-widget-metrics',
                    'titulo' => __('Panel de Métricas', 'flavor-chat-ia'),
                    'contenido' => __('Estadísticas en tiempo real: usuarios registrados, módulos activos, conversaciones del chat IA, eventos próximos y actividad de la comunidad.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-widget-system, .flavor-health-semaphore, .system-health',
                    'titulo' => __('Semáforo de Salud del Sistema', 'flavor-chat-ia'),
                    'contenido' => __('Indicador visual del estado técnico: verde = todo funciona correctamente, amarillo = hay aspectos que revisar, rojo = problemas que necesitan atención inmediata.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-widget-alerts, .flavor-alerts-list, .dashboard-alerts',
                    'titulo' => __('Centro de Alertas', 'flavor-chat-ia'),
                    'contenido' => __('Notificaciones importantes que requieren tu atención: nuevos usuarios pendientes de aprobar, módulos sin configurar, actualizaciones disponibles...', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-widget-activity, .activity-feed, .recent-activity',
                    'titulo' => __('Actividad Reciente', 'flavor-chat-ia'),
                    'contenido' => __('Registro de las últimas acciones en la plataforma: inscripciones, publicaciones, reservas, comentarios... Ideal para saber qué está pasando.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-quick-actions, .dashboard-shortcuts, .quick-links',
                    'titulo' => __('Acciones Rápidas', 'flavor-chat-ia'),
                    'contenido' => __('Atajos directos a las tareas más frecuentes: crear evento, añadir curso, publicar noticia, gestionar miembros... Un clic y estás ahí.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.flavor-widget-modules, .modules-overview, .active-modules',
                    'titulo' => __('Resumen de Módulos', 'flavor-chat-ia'),
                    'contenido' => __('Vista rápida de los módulos activos y su estado. Desde aquí puedes acceder directamente al dashboard de cada módulo.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-widget-calendar, .upcoming-events, .calendar-widget',
                    'titulo' => __('Próximos Eventos', 'flavor-chat-ia'),
                    'contenido' => __('Calendario con los eventos, talleres y actividades programadas. Te ayuda a planificar y no perderte nada importante.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '#adminmenu .toplevel_page_flavor-dashboard, #adminmenu [href*="flavor-dashboard"]',
                    'titulo' => __('Navegación Principal', 'flavor-chat-ia'),
                    'contenido' => __('El menú lateral te da acceso a todas las secciones: Dashboard, Módulos, Configuración, Diseño, Usuarios y más. Los submenús se expanden al pasar el ratón.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '#flavor-help-launcher, .flavor-help-btn',
                    'titulo' => __('Ayuda Siempre Disponible', 'flavor-chat-ia'),
                    'contenido' => __('El botón de ayuda flotante te ofrece tours guiados, documentación y recursos en cualquier momento. ¡No dudes en usarlo cuando tengas dudas!', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
            ],
        ];

        // Tour de Módulos
        $this->tours['tour_modulos'] = [
            'id' => 'tour_modulos',
            'titulo' => __('Tour de Módulos', 'flavor-chat-ia'),
            'descripcion' => __('Aprende a activar y configurar módulos especializados', 'flavor-chat-ia'),
            'icono' => 'dashicons-admin-plugins',
            'duracion' => '5 min',
            'paginas' => ['flavor-platform_page_flavor-module-dashboards', 'toplevel_page_flavor-module-dashboards', 'flavor-platform_page_flavor-modules'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.flavor-modules-header, .wrap > h1',
                    'titulo' => __('Gestión de Módulos', 'flavor-chat-ia'),
                    'contenido' => __('Los módulos extienden las capacidades de tu plataforma. Cada módulo añade funcionalidades específicas: eventos, cursos, reservas, miembros y mucho más.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.flavor-module-card:first-child, .flavor-addon-card:first-child, .module-card',
                    'titulo' => __('Tarjetas de Módulo', 'flavor-chat-ia'),
                    'contenido' => __('Cada módulo tiene su propia tarjeta con información sobre su estado, descripción y opciones de configuración.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-module-toggle, .flavor-addon-toggle, .module-status-toggle',
                    'titulo' => __('Activar/Desactivar', 'flavor-chat-ia'),
                    'contenido' => __('Usa este interruptor para activar o desactivar módulos. Los requisitos se verifican automáticamente antes de activar.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-module-config, .flavor-addon-settings, .module-settings-btn',
                    'titulo' => __('Configuración del Módulo', 'flavor-chat-ia'),
                    'contenido' => __('Accede a la configuración específica de cada módulo para personalizarlo según las necesidades de tu organización.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.flavor-module-dependencies, .module-requirements',
                    'titulo' => __('Dependencias', 'flavor-chat-ia'),
                    'contenido' => __('Algunos módulos requieren otros módulos o plugins para funcionar. El sistema te avisa de los requisitos antes de activar.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.module-category-filter, .flavor-modules-filter, .tablenav',
                    'titulo' => __('Filtrar por Categoría', 'flavor-chat-ia'),
                    'contenido' => __('Filtra módulos por categoría: comunicación, gestión, economía, comunidad... Encuentra rápidamente lo que necesitas.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.module-search, .search-box, .wrap > h1',
                    'titulo' => __('Buscar Módulos', 'flavor-chat-ia'),
                    'contenido' => __('Usa el buscador para encontrar módulos por nombre o funcionalidad. Ideal cuando sabes lo que necesitas.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.module-status-active, .active-modules-section, .wrap > h1',
                    'titulo' => __('Módulos Activos', 'flavor-chat-ia'),
                    'contenido' => __('Vista rápida de los módulos que tienes activados. Desde aquí puedes acceder a su dashboard o configuración.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.module-dashboard-link, .ver-dashboard, .wrap > h1',
                    'titulo' => __('Dashboard del Módulo', 'flavor-chat-ia'),
                    'contenido' => __('Cada módulo activo tiene su propio dashboard con estadísticas, listados y acciones específicas. Un clic y estás ahí.', 'flavor-chat-ia'),
                    'posicion' => 'left',
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
            'paginas' => ['flavor-platform_page_vbp-editor', 'toplevel_page_vbp-editor'],
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
            'titulo' => __('Red de Nodos Federada', 'flavor-chat-ia'),
            'descripcion' => __('Conecta tu instalación a la red distribuida de Flavor y comparte recursos con otras organizaciones', 'flavor-chat-ia'),
            'icono' => 'dashicons-networking',
            'duracion' => '5 min',
            'paginas' => ['flavor-platform_page_flavor-network', 'toplevel_page_flavor-network', 'admin_page_flavor-network'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.flavor-network-header, .wrap > h1, .network-dashboard-header',
                    'titulo' => __('Bienvenido a la Red Federada', 'flavor-chat-ia'),
                    'contenido' => __('La Red de Nodos es una infraestructura distribuida que conecta instalaciones de Flavor Platform en todo el mundo. Permite compartir recursos, sincronizar datos y colaborar entre organizaciones manteniendo la soberanía de cada nodo.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.flavor-node-status, .network-status-indicator, .node-connection-status',
                    'titulo' => __('Estado de tu Nodo', 'flavor-chat-ia'),
                    'contenido' => __('Indicador en tiempo real del estado de conexión. Verde = conectado y sincronizado, Amarillo = sincronizando, Rojo = desconectado. El sistema intenta reconectar automáticamente.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '.flavor-network-map, .nodes-map, .network-visualization',
                    'titulo' => __('Mapa de la Red', 'flavor-chat-ia'),
                    'contenido' => __('Visualización geográfica de todos los nodos conectados. Puedes ver organizaciones cercanas, explorar sus recursos públicos y solicitar colaboraciones.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-join-network, .network-join-btn, .connect-to-network',
                    'titulo' => __('Conectar a la Red', 'flavor-chat-ia'),
                    'contenido' => __('Si aún no estás conectado, este botón inicia el proceso. Se genera una clave única para tu nodo y se establece conexión con el servidor central de coordinación.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-node-key, .api-key-section, .node-credentials',
                    'titulo' => __('Credenciales del Nodo', 'flavor-chat-ia'),
                    'contenido' => __('Tu clave única de nodo (Node Key) identifica tu instalación en la red. Guárdala de forma segura. Si la pierdes, puedes regenerarla pero perderás las conexiones establecidas.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-node-info, .node-details, .my-node-card',
                    'titulo' => __('Perfil de tu Nodo', 'flavor-chat-ia'),
                    'contenido' => __('Información pública de tu instalación: nombre de la organización, ubicación, tipo (asociación, ayuntamiento, cooperativa...) y descripción. Otros nodos verán esto al buscarte.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-sync-settings, .sync-options, .synchronization-config',
                    'titulo' => __('Configuración de Sincronización', 'flavor-chat-ia'),
                    'contenido' => __('Decide qué compartir: plantillas de eventos, prompts de IA, catálogos de cursos, recursos de biblioteca... Tú controlas qué datos salen de tu nodo.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.flavor-shared-resources, .resources-pool, .shared-content',
                    'titulo' => __('Recursos Compartidos', 'flavor-chat-ia'),
                    'contenido' => __('Biblioteca de recursos que otros nodos han compartido: plantillas, prompts IA, configuraciones probadas, traducciones... Puedes importarlos con un clic.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-federation-requests, .connection-requests, .pending-connections',
                    'titulo' => __('Solicitudes de Federación', 'flavor-chat-ia'),
                    'contenido' => __('Cuando otro nodo quiere conectarse contigo para compartir recursos específicos, aparece aquí. Puedes aprobar, rechazar o configurar permisos granulares.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-connected-nodes, .partner-nodes, .federation-partners',
                    'titulo' => __('Nodos Asociados', 'flavor-chat-ia'),
                    'contenido' => __('Lista de organizaciones con las que tienes federación activa. Puedes ver su estado, recursos compartidos y gestionar la relación desde aquí.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-network-stats, .federation-metrics, .network-analytics',
                    'titulo' => __('Estadísticas de Red', 'flavor-chat-ia'),
                    'contenido' => __('Métricas de tu participación: recursos compartidos, descargas de tus plantillas, nodos que te siguen, colaboraciones activas... Mide tu impacto en la comunidad.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-network-settings, .advanced-network-config',
                    'titulo' => __('Configuración Avanzada', 'flavor-chat-ia'),
                    'contenido' => __('Opciones técnicas: intervalos de sincronización, límites de ancho de banda, caché de recursos remotos, webhooks para integraciones y modo offline.', 'flavor-chat-ia'),
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

        // Tour: Configuración Esencial del Sistema
        $this->tours['tour_configuracion'] = [
            'id' => 'tour_configuracion',
            'titulo' => __('Configuración Esencial', 'flavor-chat-ia'),
            'descripcion' => __('Ajustes importantes para configurar correctamente tu plataforma', 'flavor-chat-ia'),
            'icono' => 'dashicons-admin-generic',
            'duracion' => '5 min',
            'paginas' => ['flavor-platform_page_flavor-settings', 'toplevel_page_flavor-settings', 'flavor-platform_page_flavor-config'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap > h1, .settings-header',
                    'titulo' => __('Centro de Configuración', 'flavor-chat-ia'),
                    'contenido' => __('Aquí se configuran todos los aspectos técnicos y funcionales de Flavor Platform. Tómate unos minutos para revisar las opciones principales.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.nav-tab-wrapper, .flavor-settings-tabs',
                    'titulo' => __('Pestañas de Configuración', 'flavor-chat-ia'),
                    'contenido' => __('La configuración está organizada en pestañas: General, Módulos, Permisos, Notificaciones, Integraciones... Navega entre ellas para encontrar lo que buscas.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '[name*="site_name"], .site-identity, #blogname',
                    'titulo' => __('Identidad del Sitio', 'flavor-chat-ia'),
                    'contenido' => __('Nombre de tu organización, descripción y datos de contacto. Esta información aparece en emails, notificaciones y el pie de página.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '[name*="email"], .email-settings, .notification-email',
                    'titulo' => __('Configuración de Email', 'flavor-chat-ia'),
                    'contenido' => __('Email del administrador y configuración de notificaciones. Asegúrate de que el email sea correcto para recibir alertas importantes.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '[name*="timezone"], .timezone-select, select[name*="time"]',
                    'titulo' => __('Zona Horaria', 'flavor-chat-ia'),
                    'contenido' => __('Fundamental para que eventos, reservas y fechas funcionen correctamente. Selecciona la zona horaria de tu localidad.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.modules-section, [name*="active_modules"], .modules-config',
                    'titulo' => __('Módulos Activos', 'flavor-chat-ia'),
                    'contenido' => __('Activa solo los módulos que vayas a usar. Menos módulos = mejor rendimiento. Siempre puedes activar más cuando los necesites.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.permissions-section, [name*="permissions"], .roles-config',
                    'titulo' => __('Permisos y Roles', 'flavor-chat-ia'),
                    'contenido' => __('Controla quién puede hacer qué: administradores, moderadores, miembros... Cada rol tiene permisos específicos que puedes personalizar.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.api-section, [name*="api_key"], .integrations-config',
                    'titulo' => __('Claves API e Integraciones', 'flavor-chat-ia'),
                    'contenido' => __('Conecta servicios externos: proveedores de IA (Claude, OpenAI), pasarelas de pago, servicios de email marketing, mapas y más.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.privacy-section, [name*="privacy"], .legal-config',
                    'titulo' => __('Privacidad y Legal', 'flavor-chat-ia'),
                    'contenido' => __('Configura políticas de privacidad, términos de uso y cumplimiento RGPD. Importante para organizaciones que gestionan datos personales.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.submit, #submit, .button-primary',
                    'titulo' => __('Guardar Cambios', 'flavor-chat-ia'),
                    'contenido' => __('Recuerda guardar siempre tus cambios. El sistema valida automáticamente que todo esté correcto antes de guardar.', 'flavor-chat-ia'),
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

        // Tour: Panel de Administración Completo
        $this->tours['demo_admin'] = [
            'id' => 'demo_admin',
            'titulo' => __('Panel de Administración', 'flavor-chat-ia'),
            'descripcion' => __('Recorrido completo por el panel de administración de Flavor Platform', 'flavor-chat-ia'),
            'icono' => 'dashicons-admin-settings',
            'duracion' => '6 min',
            'paginas' => ['toplevel_page_flavor-chat-ia', 'flavor-platform_page_flavor-dashboard', 'toplevel_page_flavor-dashboard', 'flavor-platform_page_flavor-settings'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '#adminmenu .toplevel_page_flavor-chat-ia, #adminmenu [href*="flavor-dashboard"]',
                    'titulo' => __('Menú Principal de Flavor', 'flavor-chat-ia'),
                    'contenido' => __('Este es tu centro de control. Desde aquí accedes a todas las secciones: Dashboard, Módulos, Configuración, Diseño, Tours de ayuda y más.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.flavor-dashboard-header, .wrap > h1:first-child',
                    'titulo' => __('Dashboard Principal', 'flavor-chat-ia'),
                    'contenido' => __('Vista general de tu plataforma con métricas en tiempo real, estado del sistema y accesos rápidos a las funciones más usadas.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-widget-metrics, .flavor-metrics-grid, .flavor-dashboard-stats',
                    'titulo' => __('Métricas y Estadísticas', 'flavor-chat-ia'),
                    'contenido' => __('Monitorea usuarios activos, módulos habilitados, conversaciones del chat IA y actividad general de la comunidad.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.flavor-widget-system, .flavor-health-semaphore, .system-status',
                    'titulo' => __('Estado del Sistema', 'flavor-chat-ia'),
                    'contenido' => __('Semáforo de salud: verde significa todo OK, amarillo requiere atención, rojo indica problemas. Incluye versiones de PHP, WordPress y estado de APIs.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-widget-alerts, .flavor-alerts-list, .pending-alerts',
                    'titulo' => __('Alertas Pendientes', 'flavor-chat-ia'),
                    'contenido' => __('Notificaciones importantes: actualizaciones disponibles, módulos que requieren configuración, o acciones pendientes de usuarios.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.flavor-quick-actions, .dashboard-quick-links, .flavor-shortcuts',
                    'titulo' => __('Acciones Rápidas', 'flavor-chat-ia'),
                    'contenido' => __('Atajos a las tareas más comunes: crear evento, añadir usuario, configurar chat IA, ver estadísticas detalladas...', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '#adminmenu [href*="flavor-module"], #adminmenu [href*="modulos"]',
                    'titulo' => __('Gestión de Módulos', 'flavor-chat-ia'),
                    'contenido' => __('Activa y configura más de 40 módulos: Eventos, Cursos, Marketplace, Miembros, Reservas, Biblioteca, Incidencias, Chat IA y muchos más.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '#adminmenu [href*="flavor-design"], #adminmenu [href*="diseno"]',
                    'titulo' => __('Personalización de Diseño', 'flavor-chat-ia'),
                    'contenido' => __('Colores, tipografías, logotipos y estilos. Adapta la apariencia de toda la plataforma a la identidad visual de tu organización.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '#adminmenu [href*="flavor-settings"], #adminmenu [href*="configuracion"]',
                    'titulo' => __('Configuración General', 'flavor-chat-ia'),
                    'contenido' => __('Ajustes globales: idioma, zona horaria, permisos de usuarios, integraciones con terceros, claves API y opciones avanzadas.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '#adminmenu [href*="flavor-chat-config"], .ia-config-link',
                    'titulo' => __('Configuración del Chat IA', 'flavor-chat-ia'),
                    'contenido' => __('Elige el motor de IA (Claude, OpenAI, DeepSeek...), configura el comportamiento del asistente, personalidad y límites de uso.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '#adminmenu [href*="flavor-tours"], .tours-link',
                    'titulo' => __('Tours de Ayuda', 'flavor-chat-ia'),
                    'contenido' => __('Accede a todos los tours guiados disponibles. Perfectos para aprender funcionalidades específicas o formar a nuevos administradores.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '#wp-admin-bar-root-default, #wpadminbar',
                    'titulo' => __('Barra de Administración', 'flavor-chat-ia'),
                    'contenido' => __('Acceso rápido a tu perfil, notificaciones del sistema y enlace para ver el sitio público. Siempre visible mientras administras.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '#flavor-help-launcher, .flavor-help-btn',
                    'titulo' => __('Botón de Ayuda', 'flavor-chat-ia'),
                    'contenido' => __('¿Necesitas ayuda? Este botón flotante te da acceso rápido a tours, documentación y recursos de soporte desde cualquier página.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
            ],
        ];

        // Tour: Marketplace para Emprendedores
        $this->tours['demo_marketplace'] = [
            'id' => 'demo_marketplace',
            'titulo' => __('Marketplace Local', 'flavor-chat-ia'),
            'descripcion' => __('Plataforma de compra-venta entre emprendedores y ciudadanos de la comunidad', 'flavor-chat-ia'),
            'icono' => 'dashicons-store',
            'duracion' => '5 min',
            'paginas' => ['flavor-platform_page_flavor-marketplace', 'toplevel_page_marketplace', 'admin_page_marketplace-dashboard'],
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
                    'titulo' => __('Búsqueda y Filtros', 'flavor-chat-ia'),
                    'contenido' => __('Los usuarios pueden buscar por categoría, precio, ubicación y tipo (venta, intercambio, regalo). Perfecto para encontrar proveedores locales.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.wp-list-table, .marketplace-items',
                    'titulo' => __('Listado de Anuncios', 'flavor-chat-ia'),
                    'contenido' => __('Cada anuncio muestra foto, precio, vendedor y estado. El admin puede moderar contenido inapropiado y destacar productos locales.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.page-title-action, .add-new',
                    'titulo' => __('Publicar Anuncio', 'flavor-chat-ia'),
                    'contenido' => __('Los emprendedores pueden publicar fácilmente: foto, descripción, precio y categoría. Sin comisiones, contacto directo entre vecinos.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.tablenav .actions, .bulk-actions, .marketplace-categories',
                    'titulo' => __('Categorías del Marketplace', 'flavor-chat-ia'),
                    'contenido' => __('Organiza los productos en categorías: alimentación local, artesanía, servicios profesionales, segunda mano, huertos... Facilita la navegación.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-price, .marketplace-stats, .wrap h1',
                    'titulo' => __('Estadísticas de Ventas', 'flavor-chat-ia'),
                    'contenido' => __('Métricas del marketplace: productos activos, transacciones completadas, vendedores destacados. Mide el impacto económico en el territorio.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-author, .vendor-column, .wrap h1',
                    'titulo' => __('Vendedores Verificados', 'flavor-chat-ia'),
                    'contenido' => __('Los negocios locales pueden verificar su perfil para generar más confianza. Muestra valoraciones y opiniones de otros compradores.', 'flavor-chat-ia'),
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
            'duracion' => '5 min',
            'paginas' => ['flavor-platform_page_flavor-banco-tiempo', 'toplevel_page_banco-tiempo', 'admin_page_banco-tiempo-dashboard'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap h1, .banco-tiempo-header',
                    'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'),
                    'contenido' => __('Sistema de intercambio de servicios sin dinero. Ideal para emprendedores que empiezan con poco capital. Un diseñador hace un logo a cambio de asesoría fiscal.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.tablenav, .banco-tiempo-stats, .stats-cards',
                    'titulo' => __('Estadísticas del Banco', 'flavor-chat-ia'),
                    'contenido' => __('Horas totales intercambiadas, usuarios activos, servicios más demandados... Métricas útiles para reportar el impacto social del programa.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.wp-list-table, .servicios-lista',
                    'titulo' => __('Servicios Disponibles', 'flavor-chat-ia'),
                    'contenido' => __('Cada usuario ofrece sus habilidades: diseño, reparaciones, formación, cuidados, transporte... La comunidad decide el valor en horas.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.page-title-action, .add-new, .nuevo-servicio',
                    'titulo' => __('Ofrecer un Servicio', 'flavor-chat-ia'),
                    'contenido' => __('Los miembros publican lo que saben hacer: clases de idiomas, cuidado de niños, fontanería, diseño web... Toda habilidad tiene valor.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.column-balance, .saldo-horas, .wrap h1',
                    'titulo' => __('Saldo de Horas', 'flavor-chat-ia'),
                    'contenido' => __('Cada usuario tiene un saldo: cuando da una hora gana +1, cuando recibe -1. El sistema garantiza el equilibrio y la reciprocidad.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-category, .categorias-servicios, .wrap h1',
                    'titulo' => __('Categorías de Servicios', 'flavor-chat-ia'),
                    'contenido' => __('Organiza los servicios: domésticos, profesionales, educativos, cuidados, transporte, manualidades... Facilita encontrar lo que necesitas.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-date, .historial, .wrap h1',
                    'titulo' => __('Historial de Intercambios', 'flavor-chat-ia'),
                    'contenido' => __('Registro completo de todos los intercambios: quién, qué, cuándo, valoraciones. Transparencia total para generar confianza comunitaria.', 'flavor-chat-ia'),
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
            'duracion' => '5 min',
            'paginas' => ['flavor-platform_page_flavor-grupos-consumo', 'toplevel_page_grupos-consumo', 'admin_page_grupos-consumo-dashboard'],
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
                    'contenido' => __('Cada grupo tiene su ciclo (semanal, quincenal), punto de recogida y productores asociados. Los vecinos se organizan por barrios.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.page-title-action, .add-new, .nuevo-grupo',
                    'titulo' => __('Crear Nuevo Grupo', 'flavor-chat-ia'),
                    'contenido' => __('Crea grupos por zona geográfica, tipo de producto o interés: verduras ecológicas, carnes locales, lácteos de la comarca...', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.tablenav, .productores-section, .wrap h1',
                    'titulo' => __('Productores Locales', 'flavor-chat-ia'),
                    'contenido' => __('Directorio de agricultores, ganaderos y artesanos alimentarios de la zona. Cada uno con su ficha: productos, certificaciones, zona de reparto.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-members, .miembros-grupo, .wrap h1',
                    'titulo' => __('Miembros del Grupo', 'flavor-chat-ia'),
                    'contenido' => __('Lista de consumidores inscritos en cada grupo. Gestiona altas, bajas y rotación de responsables de recogida.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-date, .pedidos-section, .wrap h1',
                    'titulo' => __('Ciclo de Pedidos', 'flavor-chat-ia'),
                    'contenido' => __('Calendario de pedidos: apertura, cierre, preparación y recogida. Notificaciones automáticas a productores y consumidores.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.column-status, .estado-pedido, .wrap h1',
                    'titulo' => __('Estado de los Pedidos', 'flavor-chat-ia'),
                    'contenido' => __('Seguimiento en tiempo real: abierto, cerrado, en preparación, listo para recoger. Los consumidores saben siempre el estado de su cesta.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
            ],
        ];

        // Tour: Talleres y Formación
        $this->tours['demo_talleres'] = [
            'id' => 'demo_talleres',
            'titulo' => __('Formación y Talleres', 'flavor-chat-ia'),
            'descripcion' => __('Capacitación entre emprendedores de la comarca', 'flavor-chat-ia'),
            'icono' => 'dashicons-welcome-learn-more',
            'duracion' => '5 min',
            'paginas' => ['flavor-platform_page_flavor-talleres', 'flavor-platform_page_flavor-cursos', 'toplevel_page_talleres', 'admin_page_cursos-dashboard'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap h1, .talleres-header',
                    'titulo' => __('Talleres y Formación', 'flavor-chat-ia'),
                    'contenido' => __('Los propios emprendedores comparten conocimiento: marketing digital, contabilidad, oficios... Pueden cobrar en euros o en horas de banco de tiempo.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.wp-list-table, .talleres-lista',
                    'titulo' => __('Catálogo de Formación', 'flavor-chat-ia'),
                    'contenido' => __('Fecha, lugar, formador, plazas disponibles. El sistema gestiona inscripciones, lista de espera y certificados digitales.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.page-title-action, .add-new, .nuevo-taller',
                    'titulo' => __('Crear Nuevo Taller', 'flavor-chat-ia'),
                    'contenido' => __('Cualquier miembro puede proponer formación. Define tema, fecha, lugar, precio y número de plazas. El sistema gestiona todo automáticamente.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.column-instructor, .formador-info, .wrap h1',
                    'titulo' => __('Perfil del Formador', 'flavor-chat-ia'),
                    'contenido' => __('Cada formador tiene su perfil con experiencia, valoraciones y talleres impartidos. Genera confianza y reconocimiento en la comunidad.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-attendees, .inscritos-section, .wrap h1',
                    'titulo' => __('Gestión de Inscripciones', 'flavor-chat-ia'),
                    'contenido' => __('Control de asistentes: confirmados, lista de espera, cancelaciones. Notificaciones automáticas cuando se liberan plazas.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-price, .precio-taller, .wrap h1',
                    'titulo' => __('Precios y Pagos', 'flavor-chat-ia'),
                    'contenido' => __('Flexibilidad total: gratis, precio en euros, pago en horas de banco de tiempo, o combinaciones. Cada formador decide.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.column-category, .categorias-formacion, .wrap h1',
                    'titulo' => __('Categorías de Formación', 'flavor-chat-ia'),
                    'contenido' => __('Organiza por temáticas: digitalización, sostenibilidad, oficios tradicionales, idiomas, gestión empresarial, habilidades sociales...', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.column-date, .certificados-section, .wrap h1',
                    'titulo' => __('Certificados Digitales', 'flavor-chat-ia'),
                    'contenido' => __('Los asistentes reciben certificado digital automático. Útil para currículum y demostrar competencias adquiridas.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
            ],
        ];

        // Tour: Tejido Empresarial Local (Socios/Directorio)
        $this->tours['demo_directorio'] = [
            'id' => 'demo_directorio',
            'titulo' => __('Directorio Empresarial', 'flavor-chat-ia'),
            'descripcion' => __('Directorio de emprendedores y negocios de la comunidad', 'flavor-chat-ia'),
            'icono' => 'dashicons-groups',
            'duracion' => '5 min',
            'paginas' => ['admin_page_socios-dashboard', 'admin_page_socios', 'admin_page_socios-listado', 'admin_page_bares-dashboard'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap h1, .socios-header',
                    'titulo' => __('Directorio de Emprendedores', 'flavor-chat-ia'),
                    'contenido' => __('Cada emprendedor tiene su perfil completo: negocio, servicios, ubicación, contacto. El ciudadano puede buscar "carpintero en Estella" y encontrarlo al instante.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '.wp-list-table, .socios-lista',
                    'titulo' => __('Listado de Emprendedores', 'flavor-chat-ia'),
                    'contenido' => __('Filtrable por categoría, ubicación, servicios. Perfecto para fomentar colaboraciones y que los negocios locales sean visibles online.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.page-title-action, .add-new, .nuevo-socio',
                    'titulo' => __('Añadir Nuevo Negocio', 'flavor-chat-ia'),
                    'contenido' => __('Registra emprendedores: datos de contacto, descripción del negocio, horarios, redes sociales, fotos del local o productos.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.tablenav, .categorias-section, .wrap h1',
                    'titulo' => __('Categorías de Negocios', 'flavor-chat-ia'),
                    'contenido' => __('Organiza por sectores: hostelería, comercio, servicios profesionales, artesanía, agricultura... Facilita encontrar lo que se busca.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-location, .ubicacion-mapa, .wrap h1',
                    'titulo' => __('Mapa Interactivo', 'flavor-chat-ia'),
                    'contenido' => __('Visualiza todos los negocios en un mapa. Los ciudadanos descubren comercios cercanos, rutas de compra local, zonas comerciales.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-status, .verificacion-negocio, .wrap h1',
                    'titulo' => __('Verificación de Negocios', 'flavor-chat-ia'),
                    'contenido' => __('Los negocios pueden verificar su perfil para generar confianza: licencias, certificaciones, sellos de calidad local.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.column-date, .estadisticas-directorio, .wrap h1',
                    'titulo' => __('Estadísticas de Visibilidad', 'flavor-chat-ia'),
                    'contenido' => __('Cada negocio ve cuántas visitas recibe su perfil, clics en contacto, valoraciones. Mide el impacto de estar en el directorio.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
            ],
        ];

        // Tour: Red Social / Networking
        $this->tours['demo_networking'] = [
            'id' => 'demo_networking',
            'titulo' => __('Red Social de Emprendedores', 'flavor-chat-ia'),
            'descripcion' => __('LinkedIn local para la comunidad emprendedora', 'flavor-chat-ia'),
            'icono' => 'dashicons-networking',
            'duracion' => '5 min',
            'paginas' => ['admin_page_red-social-dashboard', 'admin_page_red-social', 'admin_page_comunidades-dashboard', 'admin_page_comunidades'],
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
                    'titulo' => __('Grupos Temáticos', 'flavor-chat-ia'),
                    'contenido' => __('Grupos por sector (hostelería, agricultura, servicios...) o por interés (sostenibilidad, digitalización...). Facilita el networking natural.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.page-title-action, .add-new, .nuevo-grupo',
                    'titulo' => __('Crear Comunidad', 'flavor-chat-ia'),
                    'contenido' => __('Crea grupos de interés: "Hosteleros de la zona", "Emprendedoras rurales", "Digitalización de comercios"... Espacios de colaboración sectorial.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.tablenav, .muro-publicaciones, .wrap h1',
                    'titulo' => __('Muro de Publicaciones', 'flavor-chat-ia'),
                    'contenido' => __('Los miembros comparten: ofertas de colaboración, búsqueda de proveedores, éxitos conseguidos, dudas profesionales. Interacción constante.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-members, .miembros-comunidad, .wrap h1',
                    'titulo' => __('Perfiles Profesionales', 'flavor-chat-ia'),
                    'contenido' => __('Cada emprendedor tiene perfil con habilidades, experiencia y servicios. Facilita encontrar el colaborador perfecto para cada proyecto.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '.column-activity, .actividad-grupo, .wrap h1',
                    'titulo' => __('Eventos y Quedadas', 'flavor-chat-ia'),
                    'contenido' => __('Organiza networking presencial: desayunos de trabajo, afterworks, jornadas sectoriales. El online facilita lo presencial.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '.column-date, .mensajeria-privada, .wrap h1',
                    'titulo' => __('Mensajería Privada', 'flavor-chat-ia'),
                    'contenido' => __('Chat directo entre miembros para cerrar colaboraciones, resolver dudas o proponer proyectos conjuntos. Comunicación ágil.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '.column-status, .oportunidades-section, .wrap h1',
                    'titulo' => __('Tablón de Oportunidades', 'flavor-chat-ia'),
                    'contenido' => __('Ofertas de trabajo, búsqueda de socios, traspasos de negocio, alquiler de locales... El mercado laboral y empresarial local en un vistazo.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
            ],
        ];

        // Tour: Tejido Empresarial Local
        // Este tour es conceptual y presenta las herramientas disponibles para emprendedores
        $this->tours['tour_tejido_empresarial'] = [
            'id' => 'tour_tejido_empresarial',
            'titulo' => __('Tejido Empresarial Local', 'flavor-chat-ia'),
            'descripcion' => __('Herramientas para fortalecer la economía local y conectar negocios del territorio', 'flavor-chat-ia'),
            'icono' => 'dashicons-building',
            'duracion' => '5 min',
            'paginas' => ['toplevel_page_flavor-dashboard', 'flavor-platform_page_flavor-dashboard', 'toplevel_page_flavor-chat-ia', 'flavor-platform_page_flavor-modules'],
            'video_url' => '',
            'pasos' => [
                [
                    'elemento' => '.wrap',
                    'titulo' => __('Ecosistema Empresarial Local', 'flavor-chat-ia'),
                    'contenido' => __('Flavor Platform ofrece un conjunto completo de herramientas para fortalecer el tejido empresarial del territorio: CRM, facturación, marketplace, directorio de comercios y mucho más.', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                    'destacar' => true,
                ],
                [
                    'elemento' => '#adminmenu',
                    'titulo' => __('CRM de Clientes', 'flavor-chat-ia'),
                    'contenido' => __('El módulo CRM permite registrar clientes, historial de compras y preferencias. Ideal para comercios de proximidad que quieren fidelizar a sus vecinos.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '#adminmenu',
                    'titulo' => __('Facturación Simplificada', 'flavor-chat-ia'),
                    'contenido' => __('Genera facturas, presupuestos y albaranes de forma sencilla. Sistema pensado para autónomos y pequeños comercios que necesitan cumplir con sus obligaciones fiscales.', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '#wpbody-content',
                    'titulo' => __('Marketplace Local', 'flavor-chat-ia'),
                    'contenido' => __('Un escaparate digital para productos y servicios del territorio. Los negocios locales pueden vender online sin depender de grandes plataformas, manteniendo el dinero en la comunidad.', 'flavor-chat-ia'),
                    'posicion' => 'left',
                ],
                [
                    'elemento' => '#wpbody-content',
                    'titulo' => __('Directorio de Comercios', 'flavor-chat-ia'),
                    'contenido' => __('Mapa interactivo con bares, restaurantes, tiendas y servicios de la zona. Los vecinos descubren negocios cercanos y los comerciantes ganan visibilidad local.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                ],
                [
                    'elemento' => '#wpadminbar',
                    'titulo' => __('Crowdfunding Comunitario', 'flavor-chat-ia'),
                    'contenido' => __('Financiación colectiva para proyectos locales. Los vecinos pueden apoyar iniciativas empresariales del barrio: apertura de tiendas, renovaciones, nuevos productos...', 'flavor-chat-ia'),
                    'posicion' => 'bottom',
                ],
                [
                    'elemento' => '#adminmenu',
                    'titulo' => __('Banco de Tiempo Empresarial', 'flavor-chat-ia'),
                    'contenido' => __('Intercambia servicios entre negocios sin dinero: un diseñador hace el logo, un contable lleva las cuentas, un fotógrafo hace las fotos del menú... ¡Colaboración local!', 'flavor-chat-ia'),
                    'posicion' => 'right',
                ],
                [
                    'elemento' => '.wrap',
                    'titulo' => __('Economía Circular', 'flavor-chat-ia'),
                    'contenido' => __('Todas estas herramientas trabajan juntas para que el dinero circule dentro del territorio, fortaleciendo el comercio de proximidad y creando empleo local sostenible.', 'flavor-chat-ia'),
                    'posicion' => 'top',
                    'destacar' => true,
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
            'edit_posts',
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
            'adminUrl' => admin_url('admin.php?'),
            'nonce' => wp_create_nonce('flavor_tour_nonce'),
            'chatNonce' => wp_create_nonce('chat_ia_nonce'),
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
                'chatIA' => __('Asistente IA', 'flavor-chat-ia'),
                'askAnything' => __('Escribe tu pregunta...', 'flavor-chat-ia'),
                'chatError' => __('Error al procesar el mensaje', 'flavor-chat-ia'),
                'connectionError' => __('Error de conexión', 'flavor-chat-ia'),
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

                <?php
                // Verificar si el Chat IA está disponible y configurado
                $chat_ia_disponible = false;
                $chat_settings = get_option('flavor_chat_ia_settings', []);

                if (!empty($chat_settings['enabled']) && class_exists('Flavor_Engine_Manager')) {
                    $engine_manager = Flavor_Engine_Manager::get_instance();
                    $active_engine = $engine_manager->get_active_engine();
                    $chat_ia_disponible = $active_engine && $active_engine->is_configured();
                }
                ?>

                <?php if ($chat_ia_disponible): ?>
                <div class="flavor-help-section">
                    <h4><?php esc_html_e('Asistente IA', 'flavor-chat-ia'); ?></h4>
                    <div class="flavor-help-item flavor-help-chat-ia" id="flavor-help-chat-trigger">
                        <span class="dashicons dashicons-format-chat"></span>
                        <div class="flavor-help-item-content">
                            <span class="flavor-help-item-title"><?php esc_html_e('Abrir Chat IA', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-help-item-desc"><?php esc_html_e('Pregunta lo que necesites', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>
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
