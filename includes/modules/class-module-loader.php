<?php
/**
 * Cargador de módulos para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar trait para páginas de admin de módulos
$trait_file = FLAVOR_CHAT_IA_PATH . 'includes/admin/trait-module-admin-pages.php';
if (file_exists($trait_file) && !trait_exists('Flavor_Module_Admin_Pages_Trait')) {
    require_once $trait_file;
}

// Cargar trait para tabs de dashboard (sistema flexible)
$trait_dashboard_tabs = FLAVOR_CHAT_IA_PATH . 'includes/traits/trait-module-dashboard-tabs.php';
if (file_exists($trait_dashboard_tabs) && !trait_exists('Flavor_Module_Dashboard_Tabs_Trait')) {
    require_once $trait_dashboard_tabs;
}

// Cargar trait para integraciones de tabs de módulos de red
$trait_tab_integrations = FLAVOR_CHAT_IA_PATH . 'includes/traits/trait-module-tab-integrations.php';
if (file_exists($trait_tab_integrations) && !trait_exists('Flavor_Module_Tab_Integrations_Trait')) {
    require_once $trait_tab_integrations;
}

// Cargar traits para integraciones dinámicas entre módulos (Provider/Consumer)
$trait_integrations = FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-module-integrations.php';
if (file_exists($trait_integrations) && !trait_exists('Flavor_Module_Integration_Consumer')) {
    require_once $trait_integrations;
}

// Cargar trait de notificaciones de módulos
$trait_notifications = FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-module-notifications.php';
if (file_exists($trait_notifications) && !trait_exists('Flavor_Module_Notifications_Trait')) {
    require_once $trait_notifications;
}

// Cargar trait de funciones WhatsApp
$trait_whatsapp = FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-whatsapp-features.php';
if (file_exists($trait_whatsapp) && !trait_exists('Flavor_WhatsApp_Features')) {
    require_once $trait_whatsapp;
}

// Cargar trait de Admin UI
$trait_admin_ui = FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-module-admin-ui.php';
if (file_exists($trait_admin_ui) && !trait_exists('Flavor_Module_Admin_UI_Trait')) {
    require_once $trait_admin_ui;
}

// Cargar trait de Dashboard Widget
$trait_dashboard_widget = FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-dashboard-widget.php';
if (file_exists($trait_dashboard_widget) && !trait_exists('Flavor_Dashboard_Widget_Trait')) {
    require_once $trait_dashboard_widget;
}

// Cargar trait de acciones frontend
$trait_frontend_actions = FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-module-frontend-actions.php';
if (file_exists($trait_frontend_actions) && !trait_exists('Flavor_Module_Frontend_Actions')) {
    require_once $trait_frontend_actions;
}

// Cargar gestor de contenido de entidades
$entity_content_manager = FLAVOR_CHAT_IA_PATH . 'includes/class-entity-content-manager.php';
if (file_exists($entity_content_manager) && !class_exists('Flavor_Entity_Content_Manager')) {
    require_once $entity_content_manager;
}

// Cargar trait de funcionalidades de encuestas
$trait_encuestas_features = FLAVOR_CHAT_IA_PATH . 'includes/modules/trait-encuestas-features.php';
if (file_exists($trait_encuestas_features) && !trait_exists('Flavor_Encuestas_Features')) {
    require_once $trait_encuestas_features;
}

/**
 * Clase que gestiona la carga de módulos
 */
class Flavor_Chat_Module_Loader {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Módulos registrados
     */
    private $registered_modules = [];

    /**
     * Módulos cargados
     */
    private $loaded_modules = [];

    /**
     * Caché de metadatos de módulos (para evitar cargarlos)
     */
    private $modules_metadata_cache = null;

    /**
     * Clave de caché para metadatos
     */
    private const METADATA_CACHE_KEY = 'flavor_modules_metadata_cache';

    /**
     * Versión del caché (incrementar al cambiar estructura)
     */
    private const METADATA_CACHE_VERSION = 3;

    /**
     * TTL del caché de metadatos en segundos
     * Producción: 7 días (metadatos rara vez cambian)
     * Desarrollo (WP_DEBUG): 1 hora
     */
    private static function get_metadata_cache_ttl(): int {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return HOUR_IN_SECONDS;
        }
        return 7 * DAY_IN_SECONDS;
    }

    /**
     * Registro de fallback de principios Gailu por módulo.
     * Se usa solo cuando la caché de metadatos no contiene los principios
     * (por ejemplo, antes de que se regenere la caché tras actualización).
     *
     * Los principios reales se leen dinámicamente desde cada módulo y se
     * almacenan en la caché de metadatos (ver rebuild_metadata_cache).
     *
     * Principios: economia_local, cuidados, gobernanza, regeneracion, aprendizaje
     * Contribuciones: autonomia, resiliencia, cohesion, impacto
     *
     * @since 3.1.0
     * @deprecated Se eliminará cuando todos los módulos tengan gailu_principios definidos
     */
    private const GAILU_MODULOS_REGISTRO = [
        // Economía y recursos
        'grupos_consumo'        => ['principios' => ['economia_local', 'regeneracion'], 'contribuye_a' => ['autonomia', 'resiliencia']],
        'banco_tiempo'          => ['principios' => ['economia_local', 'cuidados'], 'contribuye_a' => ['cohesion', 'resiliencia']],
        'marketplace'           => ['principios' => ['economia_local'], 'contribuye_a' => ['autonomia']],
        'economia_don'          => ['principios' => ['economia_local', 'cuidados'], 'contribuye_a' => ['cohesion', 'resiliencia']],
        'economia_suficiencia'  => ['principios' => ['economia_local', 'regeneracion'], 'contribuye_a' => ['resiliencia', 'impacto']],

        // Energía y medio ambiente
        'energia_comunitaria'   => ['principios' => ['economia_local', 'regeneracion'], 'contribuye_a' => ['autonomia', 'impacto']],
        'huertos_urbanos'       => ['principios' => ['economia_local', 'regeneracion'], 'contribuye_a' => ['autonomia', 'resiliencia']],
        'compostaje'            => ['principios' => ['regeneracion'], 'contribuye_a' => ['impacto', 'cohesion']],
        'reciclaje'             => ['principios' => ['regeneracion'], 'contribuye_a' => ['impacto']],
        'huella_ecologica'      => ['principios' => ['regeneracion', 'aprendizaje'], 'contribuye_a' => ['impacto', 'autonomia']],
        'biodiversidad_local'   => ['principios' => ['regeneracion'], 'contribuye_a' => ['impacto', 'resiliencia']],

        // Cuidados y bienestar
        'ayuda_vecinal'         => ['principios' => ['cuidados'], 'contribuye_a' => ['cohesion', 'resiliencia']],
        'circulos_cuidados'     => ['principios' => ['cuidados', 'gobernanza'], 'contribuye_a' => ['cohesion', 'resiliencia']],

        // Gobernanza y participación
        'participacion'             => ['principios' => ['gobernanza'], 'contribuye_a' => ['autonomia', 'cohesion']],
        'presupuestos_participativos' => ['principios' => ['gobernanza', 'economia_local'], 'contribuye_a' => ['autonomia', 'impacto']],
        'transparencia'             => ['principios' => ['gobernanza'], 'contribuye_a' => ['autonomia']],
        'colectivos'                => ['principios' => ['gobernanza', 'cuidados'], 'contribuye_a' => ['cohesion', 'autonomia']],
        'comunidades'               => ['principios' => ['gobernanza', 'cuidados'], 'contribuye_a' => ['cohesion']],

        // Aprendizaje y cultura
        'cursos'            => ['principios' => ['aprendizaje'], 'contribuye_a' => ['autonomia', 'impacto']],
        'talleres'          => ['principios' => ['aprendizaje', 'economia_local'], 'contribuye_a' => ['autonomia', 'cohesion']],
        'biblioteca'        => ['principios' => ['aprendizaje'], 'contribuye_a' => ['autonomia']],
        'saberes_ancestrales' => ['principios' => ['aprendizaje', 'cuidados'], 'contribuye_a' => ['resiliencia', 'cohesion']],
        'foros'             => ['principios' => ['aprendizaje', 'gobernanza'], 'contribuye_a' => ['cohesion']],

        // Eventos y comunicación
        'eventos'           => ['principios' => ['cuidados'], 'contribuye_a' => ['cohesion']],
        'radio'             => ['principios' => ['aprendizaje', 'gobernanza'], 'contribuye_a' => ['cohesion', 'autonomia']],
        'podcast'           => ['principios' => ['aprendizaje'], 'contribuye_a' => ['autonomia']],

        // Movilidad
        'carpooling'            => ['principios' => ['economia_local', 'regeneracion'], 'contribuye_a' => ['resiliencia', 'cohesion']],
        'bicicletas_compartidas' => ['principios' => ['regeneracion', 'economia_local'], 'contribuye_a' => ['impacto', 'cohesion']],
        'parkings'              => ['principios' => ['economia_local'], 'contribuye_a' => ['resiliencia']],

        // Espacios y recursos compartidos
        'espacios_comunes'  => ['principios' => ['economia_local', 'cuidados'], 'contribuye_a' => ['cohesion', 'autonomia']],
        'reservas'          => ['principios' => ['economia_local'], 'contribuye_a' => ['autonomia']],

        // Gestión de miembros
        'socios'            => ['principios' => ['gobernanza'], 'contribuye_a' => ['cohesion', 'autonomia']],
        'tramites'          => ['principios' => ['gobernanza'], 'contribuye_a' => ['autonomia']],

        // Incidencias y conflictos
        'incidencias'           => ['principios' => ['gobernanza', 'cuidados'], 'contribuye_a' => ['resiliencia']],
        'justicia_restaurativa' => ['principios' => ['cuidados', 'gobernanza'], 'contribuye_a' => ['cohesion', 'resiliencia']],

        // Red social y comunicación
        'red_social'        => ['principios' => ['cuidados'], 'contribuye_a' => ['cohesion']],
        'campanias'         => ['principios' => ['gobernanza', 'cuidados'], 'contribuye_a' => ['impacto', 'cohesion']],
        'email_marketing'   => ['principios' => ['gobernanza'], 'contribuye_a' => ['impacto']],

        // Trabajo y empleo
        'trabajo_digno'     => ['principios' => ['economia_local', 'cuidados'], 'contribuye_a' => ['autonomia', 'resiliencia']],

        // Mapeo y documentación
        'mapa_actores'          => ['principios' => ['gobernanza'], 'contribuye_a' => ['cohesion', 'impacto']],
        'documentacion_legal'   => ['principios' => ['gobernanza'], 'contribuye_a' => ['autonomia']],
        'seguimiento_denuncias' => ['principios' => ['gobernanza', 'cuidados'], 'contribuye_a' => ['resiliencia']],
    ];

    /**
     * Obtiene los principios Gailu para un conjunto de módulos activos.
     * Lee los principios desde la caché de metadatos o directamente de los módulos.
     *
     * @param array $modulos_activos Array de IDs de módulos activos
     * @return array [
     *   'principios' => ['economia_local' => ['modulo1', 'modulo2'], ...],
     *   'contribuciones' => ['autonomia' => ['modulo1', ...], ...],
     *   'totales' => ['principios' => 5, 'contribuciones' => 4],
     *   'cubiertos' => ['principios' => 3, 'contribuciones' => 2]
     * ]
     * @since 3.1.0
     */
    public static function get_gailu_metricas($modulos_activos) {
        $principios = [
            'economia_local' => [],
            'cuidados' => [],
            'gobernanza' => [],
            'regeneracion' => [],
            'aprendizaje' => [],
        ];

        $contribuciones = [
            'autonomia' => [],
            'resiliencia' => [],
            'cohesion' => [],
            'impacto' => [],
        ];

        // Obtener instancia para acceder a la caché
        $loader = self::get_instance();
        $cache = $loader->modules_metadata_cache;

        // Si no hay caché, intentar reconstruirla
        if (empty($cache)) {
            $cache = $loader->rebuild_metadata_cache();
        }

        foreach ($modulos_activos as $modulo_id) {
            $gailu_principios = [];
            $gailu_contribuye = [];

            // Primero intentar desde caché de metadatos
            if (isset($cache[$modulo_id])) {
                $gailu_principios = $cache[$modulo_id]['gailu_principios'] ?? [];
                $gailu_contribuye = $cache[$modulo_id]['gailu_contribuye_a'] ?? [];
            }

            // Si no hay en caché, usar registro estático como fallback
            if (empty($gailu_principios) && isset(self::GAILU_MODULOS_REGISTRO[$modulo_id])) {
                $registro = self::GAILU_MODULOS_REGISTRO[$modulo_id];
                $gailu_principios = $registro['principios'] ?? [];
                $gailu_contribuye = $registro['contribuye_a'] ?? [];
            }

            // Registrar principios
            foreach ($gailu_principios as $principio) {
                if (isset($principios[$principio])) {
                    $principios[$principio][] = $modulo_id;
                }
            }

            // Registrar contribuciones
            foreach ($gailu_contribuye as $contribucion) {
                if (isset($contribuciones[$contribucion])) {
                    $contribuciones[$contribucion][] = $modulo_id;
                }
            }
        }

        // Calcular cubiertos (los que tienen al menos un módulo)
        $principios_cubiertos = count(array_filter($principios, function ($modulos) {
            return !empty($modulos);
        }));

        $contribuciones_cubiertas = count(array_filter($contribuciones, function ($modulos) {
            return !empty($modulos);
        }));

        return [
            'principios' => $principios,
            'contribuciones' => $contribuciones,
            'totales' => [
                'principios' => 5,
                'contribuciones' => 4,
            ],
            'cubiertos' => [
                'principios' => $principios_cubiertos,
                'contribuciones' => $contribuciones_cubiertas,
            ],
        ];
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Chat_Module_Loader
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
        $this->discover_modules();
        $this->load_metadata_cache();
    }

    /**
     * Carga la caché de metadatos de módulos
     */
    private function load_metadata_cache() {
        $cached = get_transient(self::METADATA_CACHE_KEY);

        if ($cached && isset($cached['version']) && $cached['version'] === self::METADATA_CACHE_VERSION) {
            $this->modules_metadata_cache = $cached['data'];
        }
    }

    /**
     * Guarda la caché de metadatos
     *
     * @param array $metadata
     */
    private function save_metadata_cache($metadata) {
        $this->modules_metadata_cache = $metadata;

        set_transient(self::METADATA_CACHE_KEY, [
            'version' => self::METADATA_CACHE_VERSION,
            'data' => $metadata,
            'created_at' => time(),
        ], self::get_metadata_cache_ttl());
    }

    /**
     * Invalida la caché de metadatos
     */
    public function invalidate_metadata_cache() {
        delete_transient(self::METADATA_CACHE_KEY);
        $this->modules_metadata_cache = null;
    }

    /**
     * Obtiene metadatos de un módulo sin cargarlo completamente
     *
     * @param string $module_id
     * @return array|null
     */
    public function get_module_metadata($module_id) {
        // Primero verificar caché
        if ($this->modules_metadata_cache && isset($this->modules_metadata_cache[$module_id])) {
            return $this->modules_metadata_cache[$module_id];
        }

        // Si el módulo está cargado, obtener de la instancia
        if (isset($this->loaded_modules[$module_id])) {
            $module = $this->loaded_modules[$module_id];
            return [
                'id' => $module->get_id(),
                'name' => $module->get_name(),
                'description' => $module->get_description(),
                'visibility' => method_exists($module, 'get_default_visibility')
                    ? $module->get_default_visibility()
                    : 'public',
                'capability' => method_exists($module, 'get_default_capability')
                    ? $module->get_default_capability()
                    : 'read',
            ];
        }

        return null;
    }

    /**
     * Regenera la caché de metadatos de todos los módulos
     * Solo llamar cuando sea necesario (instalación, actualización)
     *
     * @return array
     */
    public function rebuild_metadata_cache() {
        $metadata = [];

        foreach ($this->registered_modules as $module_id => $module_data) {
            // Validar que los datos del módulo son válidos
            if (empty($module_data['file']) || !is_string($module_data['file'])) {
                continue;
            }

            if (!file_exists($module_data['file'])) {
                continue;
            }

            // Solo cargar si no está ya en memoria
            if (!isset($this->loaded_modules[$module_id])) {
                require_once $module_data['file'];
            }

            if (!class_exists($module_data['class'])) {
                continue;
            }

            $module = isset($this->loaded_modules[$module_id])
                ? $this->loaded_modules[$module_id]
                : new $module_data['class']();

            $metadata[$module_id] = [
                'id' => $module->get_id(),
                'name' => $module->get_name(),
                'description' => $module->get_description(),
                'icon' => method_exists($module, 'get_icon') ? $module->get_icon() : 'dashicons-admin-plugins',
                'color' => method_exists($module, 'get_color') ? $module->get_color() : '#3b82f6',
                'ecosystem' => method_exists($module, 'get_ecosystem_metadata')
                    ? $module->get_ecosystem_metadata()
                    : [],
                'dashboard' => method_exists($module, 'get_dashboard_metadata')
                    ? $module->get_dashboard_metadata()
                    : [],
                'visibility' => method_exists($module, 'get_default_visibility')
                    ? $module->get_default_visibility()
                    : 'public',
                'capability' => method_exists($module, 'get_default_capability')
                    ? $module->get_default_capability()
                    : 'read',
                // Principios Gailu (filosofia regenerativa)
                'gailu_principios' => method_exists($module, 'get_gailu_principios')
                    ? $module->get_gailu_principios()
                    : [],
                'gailu_contribuye_a' => method_exists($module, 'get_gailu_contribuciones')
                    ? $module->get_gailu_contribuciones()
                    : [],
            ];
        }

        $this->save_metadata_cache($metadata);

        return $metadata;
    }

    /**
     * Descubre los módulos disponibles
     */
    private function discover_modules() {
        $modules_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/';

        // Módulos incorporados
        $builtin_modules = [
            'woocommerce' => [
                'file' => $modules_path . 'woocommerce/class-woocommerce-module.php',
                'class' => 'Flavor_Chat_WooCommerce_Module',
            ],
            'banco_tiempo' => [
                'file' => $modules_path . 'banco-tiempo/class-banco-tiempo-module.php',
                'class' => 'Flavor_Chat_Banco_Tiempo_Module',
            ],
            'marketplace' => [
                'file' => $modules_path . 'marketplace/class-marketplace-module.php',
                'class' => 'Flavor_Chat_Marketplace_Module',
            ],
            'grupos_consumo' => [
                'file' => $modules_path . 'grupos-consumo/class-grupos-consumo-module.php',
                'class' => 'Flavor_Chat_Grupos_Consumo_Module',
            ],
            'facturas' => [
                'file' => $modules_path . 'facturas/class-facturas-module.php',
                'class' => 'Flavor_Chat_Facturas_Module',
            ],
            'fichaje_empleados' => [
                'file' => $modules_path . 'fichaje-empleados/class-fichaje-empleados-module.php',
                'class' => 'Flavor_Chat_Fichaje_Empleados_Module',
            ],
            'eventos' => [
                'file' => $modules_path . 'eventos/class-eventos-module.php',
                'class' => 'Flavor_Chat_Eventos_Module',
            ],
            'socios' => [
                'file' => $modules_path . 'socios/class-socios-module.php',
                'class' => 'Flavor_Chat_Socios_Module',
            ],
            'incidencias' => [
                'file' => $modules_path . 'incidencias/class-incidencias-module.php',
                'class' => 'Flavor_Chat_Incidencias_Module',
            ],
            'participacion' => [
                'file' => $modules_path . 'participacion/class-participacion-module.php',
                'class' => 'Flavor_Chat_Participacion_Module',
            ],
            'presupuestos_participativos' => [
                'file' => $modules_path . 'presupuestos-participativos/class-presupuestos-participativos-module.php',
                'class' => 'Flavor_Chat_Presupuestos_Participativos_Module',
            ],
            'avisos_municipales' => [
                'file' => $modules_path . 'avisos-municipales/class-avisos-municipales-module.php',
                'class' => 'Flavor_Chat_Avisos_Municipales_Module',
            ],
            'advertising' => [
                'file' => $modules_path . 'advertising/class-advertising-module.php',
                'class' => 'Flavor_Chat_Advertising_Module',
            ],
            'ayuda_vecinal' => [
                'file' => $modules_path . 'ayuda-vecinal/class-ayuda-vecinal-module.php',
                'class' => 'Flavor_Chat_Ayuda_Vecinal_Module',
            ],
            'biblioteca' => [
                'file' => $modules_path . 'biblioteca/class-biblioteca-module.php',
                'class' => 'Flavor_Chat_Biblioteca_Module',
            ],
            'bicicletas_compartidas' => [
                'file' => $modules_path . 'bicicletas-compartidas/class-bicicletas-compartidas-module.php',
                'class' => 'Flavor_Chat_Bicicletas_Compartidas_Module',
            ],
            'carpooling' => [
                'file' => $modules_path . 'carpooling/class-carpooling-module.php',
                'class' => 'Flavor_Chat_Carpooling_Module',
            ],
            'chat_grupos' => [
                'file' => $modules_path . 'chat-grupos/class-chat-grupos-module.php',
                'class' => 'Flavor_Chat_Chat_Grupos_Module',
            ],
            'chat_interno' => [
                'file' => $modules_path . 'chat-interno/class-chat-interno-module.php',
                'class' => 'Flavor_Chat_Chat_Interno_Module',
            ],
            'chat_estados' => [
                'file' => $modules_path . 'chat-estados/class-chat-estados-module.php',
                'class' => 'Flavor_Chat_Estados_Module',
            ],
            'compostaje' => [
                'file' => $modules_path . 'compostaje/class-compostaje-module.php',
                'class' => 'Flavor_Chat_Compostaje_Module',
            ],
            'cursos' => [
                'file' => $modules_path . 'cursos/class-cursos-module.php',
                'class' => 'Flavor_Chat_Cursos_Module',
            ],
            'empresarial' => [
                'file' => $modules_path . 'empresarial/class-empresarial-module.php',
                'class' => 'Flavor_Chat_Empresarial_Module',
            ],
            'espacios_comunes' => [
                'file' => $modules_path . 'espacios-comunes/class-espacios-comunes-module.php',
                'class' => 'Flavor_Chat_Espacios_Comunes_Module',
            ],
            'huertos_urbanos' => [
                'file' => $modules_path . 'huertos-urbanos/class-huertos-urbanos-module.php',
                'class' => 'Flavor_Chat_Huertos_Urbanos_Module',
            ],
            'multimedia' => [
                'file' => $modules_path . 'multimedia/class-multimedia-module.php',
                'class' => 'Flavor_Chat_Multimedia_Module',
            ],
            'parkings' => [
                'file' => $modules_path . 'parkings/class-parkings-module.php',
                'class' => 'Flavor_Chat_Parkings_Module',
            ],
            'podcast' => [
                'file' => $modules_path . 'podcast/class-podcast-module.php',
                'class' => 'Flavor_Chat_Podcast_Module',
            ],
            'radio' => [
                'file' => $modules_path . 'radio/class-radio-module.php',
                'class' => 'Flavor_Chat_Radio_Module',
            ],
            'reciclaje' => [
                'file' => $modules_path . 'reciclaje/class-reciclaje-module.php',
                'class' => 'Flavor_Chat_Reciclaje_Module',
            ],
            'red_social' => [
                'file' => $modules_path . 'red-social/class-red-social-module.php',
                'class' => 'Flavor_Chat_Red_Social_Module',
            ],
            'talleres' => [
                'file' => $modules_path . 'talleres/class-talleres-module.php',
                'class' => 'Flavor_Chat_Talleres_Module',
            ],
            'tramites' => [
                'file' => $modules_path . 'tramites/class-tramites-module.php',
                'class' => 'Flavor_Chat_Tramites_Module',
            ],
            'transparencia' => [
                'file' => $modules_path . 'transparencia/class-transparencia-module.php',
                'class' => 'Flavor_Chat_Transparencia_Module',
            ],
            'colectivos' => [
                'file' => $modules_path . 'colectivos/class-colectivos-module.php',
                'class' => 'Flavor_Chat_Colectivos_Module',
            ],
            'foros' => [
                'file' => $modules_path . 'foros/class-foros-module.php',
                'class' => 'Flavor_Chat_Foros_Module',
            ],
            'clientes' => [
                'file' => $modules_path . 'clientes/class-clientes-module.php',
                'class' => 'Flavor_Chat_Clientes_Module',
            ],
            'comunidades' => [
                'file' => $modules_path . 'comunidades/class-comunidades-module.php',
                'class' => 'Flavor_Chat_Comunidades_Module',
            ],
            'bares' => [
                'file' => $modules_path . 'bares/class-bares-module.php',
                'class' => 'Flavor_Chat_Bares_Module',
            ],
            'trading_ia' => [
                'file' => $modules_path . 'trading-ia/class-trading-ia-module.php',
                'class' => 'Flavor_Chat_Trading_IA_Module',
            ],
            'dex_solana' => [
                'file' => $modules_path . 'dex-solana/class-dex-solana-module.php',
                'class' => 'Flavor_Chat_Dex_Solana_Module',
            ],
            'themacle' => [
                'file' => $modules_path . 'themacle/class-themacle-module.php',
                'class' => 'Flavor_Chat_Themacle_Module',
            ],
            'reservas' => [
                'file' => $modules_path . 'reservas/class-reservas-module.php',
                'class' => 'Flavor_Chat_Reservas_Module',
            ],
            'email_marketing' => [
                'file' => $modules_path . 'email-marketing/class-email-marketing-module.php',
                'class' => 'Flavor_Chat_Email_Marketing_Module',
            ],
            'sello_conciencia' => [
                'file' => $modules_path . 'sello-conciencia/class-sello-conciencia-module.php',
                'class' => 'Flavor_Chat_Sello_Conciencia_Module',
            ],
            'circulos_cuidados' => [
                'file' => $modules_path . 'circulos-cuidados/class-circulos-cuidados-module.php',
                'class' => 'Flavor_Chat_Circulos_Cuidados_Module',
            ],
            'economia_don' => [
                'file' => $modules_path . 'economia-don/class-economia-don-module.php',
                'class' => 'Flavor_Chat_Economia_Don_Module',
            ],
            'justicia_restaurativa' => [
                'file' => $modules_path . 'justicia-restaurativa/class-justicia-restaurativa-module.php',
                'class' => 'Flavor_Chat_Justicia_Restaurativa_Module',
            ],
            'huella_ecologica' => [
                'file' => $modules_path . 'huella-ecologica/class-huella-ecologica-module.php',
                'class' => 'Flavor_Chat_Huella_Ecologica_Module',
            ],
            'economia_suficiencia' => [
                'file' => $modules_path . 'economia-suficiencia/class-economia-suficiencia-module.php',
                'class' => 'Flavor_Chat_Economia_Suficiencia_Module',
            ],
            'energia_comunitaria' => [
                'file' => $modules_path . 'energia-comunitaria/class-energia-comunitaria-module.php',
                'class' => 'Flavor_Chat_Energia_Comunitaria_Module',
            ],
            'saberes_ancestrales' => [
                'file' => $modules_path . 'saberes-ancestrales/class-saberes-ancestrales-module.php',
                'class' => 'Flavor_Chat_Saberes_Ancestrales_Module',
            ],
            'biodiversidad_local' => [
                'file' => $modules_path . 'biodiversidad-local/class-biodiversidad-local-module.php',
                'class' => 'Flavor_Chat_Biodiversidad_Local_Module',
            ],
            'trabajo_digno' => [
                'file' => $modules_path . 'trabajo-digno/class-trabajo-digno-module.php',
                'class' => 'Flavor_Chat_Trabajo_Digno_Module',
            ],
            'recetas' => [
                'file' => $modules_path . 'recetas/class-recetas-module.php',
                'class' => 'Flavor_Chat_Recetas_Module',
            ],
            'campanias' => [
                'file' => $modules_path . 'campanias/class-campanias-module.php',
                'class' => 'Flavor_Chat_Campanias_Module',
            ],
            'documentacion_legal' => [
                'file' => $modules_path . 'documentacion-legal/class-documentacion-legal-module.php',
                'class' => 'Flavor_Chat_Documentacion_Legal_Module',
            ],
            'seguimiento_denuncias' => [
                'file' => $modules_path . 'seguimiento-denuncias/class-seguimiento-denuncias-module.php',
                'class' => 'Flavor_Chat_Seguimiento_Denuncias_Module',
            ],
            'mapa_actores' => [
                'file' => $modules_path . 'mapa-actores/class-mapa-actores-module.php',
                'class' => 'Flavor_Chat_Mapa_Actores_Module',
            ],
            'encuestas' => [
                'file' => $modules_path . 'encuestas/class-encuestas-module.php',
                'class' => 'Flavor_Chat_Encuestas_Module',
            ],
        ];

        foreach ($builtin_modules as $id => $module) {
            // Validar que el archivo del módulo es válido antes de verificar existencia
            if (!empty($module['file']) && is_string($module['file']) && file_exists($module['file'])) {
                $this->registered_modules[$id] = $module;
            }
        }

        // Permitir que otros plugins registren módulos
        $this->registered_modules = apply_filters('flavor_chat_ia_modules', $this->registered_modules);

        // Validar módulos añadidos por filtros externos
        foreach ($this->registered_modules as $id => $module) {
            if (empty($module['file']) || !is_string($module['file']) ||
                empty($module['class']) || !is_string($module['class'])) {
                unset($this->registered_modules[$id]);
            }
        }
    }

    /**
     * Carga los módulos activos
     *
     * @return array Módulos cargados
     */
    public function load_active_modules() {
        // Usar caché estática para evitar múltiples get_option
        $active_modules = self::get_active_modules_cached();

        foreach ($active_modules as $module_id) {
            if (isset($this->registered_modules[$module_id])) {
                $this->load_module($module_id);
            }
        }

        return $this->loaded_modules;
    }

    /**
     * Carga un módulo específico
     *
     * @param string $module_id
     * @return bool
     */
    private function load_module($module_id) {
        if (isset($this->loaded_modules[$module_id])) {
            return true; // Ya cargado
        }

        $module_info = $this->registered_modules[$module_id] ?? null;

        // Validar que la información del módulo existe y tiene archivo
        if (!$module_info || empty($module_info['file']) || !is_string($module_info['file'])) {
            flavor_chat_ia_log("Información de módulo inválida: {$module_id}", 'error');
            return false;
        }

        // Cargar archivo
        if (!file_exists($module_info['file'])) {
            flavor_chat_ia_log("Módulo no encontrado: {$module_id}", 'error');
            return false;
        }

        require_once $module_info['file'];

        // Instanciar
        if (!class_exists($module_info['class'])) {
            flavor_chat_ia_log("Clase de módulo no encontrada: {$module_info['class']}", 'error');
            return false;
        }

        $module = new $module_info['class']();

        // Verificar que implementa la interface
        if (!($module instanceof Flavor_Chat_Module_Interface)) {
            flavor_chat_ia_log("Módulo no implementa interface: {$module_id}", 'error');
            return false;
        }

        // Si el módulo registra shortcodes en init pero init ya ocurrió,
        // intentar registrarlos manualmente para evitar que el shortcode se muestre como texto.
        if (did_action('init') && is_callable([$module, 'register_shortcodes'])) {
            $module->register_shortcodes();
        }

        // Si no puede activarse, intentar crear tablas automáticamente
        if (!$module->can_activate()) {
            flavor_chat_ia_log("Intentando crear tablas para módulo: {$module_id}", 'info');

            // Intentar activate() primero (algunos módulos lo implementan)
            if (method_exists($module, 'activate')) {
                $module->activate();
            }

            // Si sigue sin poder, intentar maybe_create_tables() directamente
            if (!$module->can_activate() && method_exists($module, 'maybe_create_tables')) {
                $module->maybe_create_tables();
            }

            // Verificar de nuevo tras crear tablas
            if (!$module->can_activate()) {
                flavor_chat_ia_log("Módulo no puede activarse tras crear tablas: {$module_id} - " . $module->get_activation_error(), 'warning');
                return false;
            }
        }

        // Inicializar
        $module->init();

        $this->loaded_modules[$module_id] = $module;

        flavor_chat_ia_log("Módulo cargado: {$module_id}", 'info');

        return true;
    }

    /**
     * Caché estática de módulos activos (compartida entre todas las instancias)
     *
     * @var array|null
     */
    private static $active_modules_cache = null;

    /**
     * Obtiene la lista de módulos activos (con caché por request)
     *
     * @return array Lista de IDs de módulos activos
     */
    public static function get_active_modules_cached(): array {
        if (self::$active_modules_cache !== null) {
            return self::$active_modules_cache;
        }

        // Buscar en flavor_chat_ia_settings['active_modules'] (preferido)
        $configuracion_plugin = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion_plugin['active_modules'] ?? [];

        // También buscar en flavor_active_modules (legacy/compatibilidad)
        $modulos_activos_legacy = get_option('flavor_active_modules', []);
        if (!empty($modulos_activos_legacy)) {
            $modulos_activos = array_unique(array_merge($modulos_activos, $modulos_activos_legacy));
        }

        // Default: woocommerce siempre activo si no hay módulos configurados
        if (empty($modulos_activos)) {
            $modulos_activos = ['woocommerce'];
        }

        self::$active_modules_cache = $modulos_activos;
        return self::$active_modules_cache;
    }

    /**
     * Invalida la caché de módulos activos (llamar al cambiar configuración)
     *
     * @return void
     */
    public static function invalidate_active_modules_cache(): void {
        self::$active_modules_cache = null;
    }

    /**
     * Caché estática de visibilidades de módulos
     *
     * @var array|null
     */
    private static $visibility_cache = null;

    /**
     * Caché estática de capacidades requeridas de módulos
     *
     * @var array|null
     */
    private static $capabilities_cache = null;

    /**
     * Obtiene todas las visibilidades configuradas (con caché por request)
     *
     * @return array Mapa de module_id => visibilidad
     */
    public static function get_visibility_settings_cached(): array {
        if (self::$visibility_cache !== null) {
            return self::$visibility_cache;
        }

        self::$visibility_cache = get_option('flavor_modules_visibility', []);
        return self::$visibility_cache;
    }

    /**
     * Obtiene todas las capacidades configuradas (con caché por request)
     *
     * @return array Mapa de module_id => capability
     */
    public static function get_capabilities_settings_cached(): array {
        if (self::$capabilities_cache !== null) {
            return self::$capabilities_cache;
        }

        self::$capabilities_cache = get_option('flavor_modules_capabilities', []);
        return self::$capabilities_cache;
    }

    /**
     * Invalida las cachés de visibilidad y capacidades
     *
     * @return void
     */
    public static function invalidate_visibility_cache(): void {
        self::$visibility_cache = null;
        self::$capabilities_cache = null;
    }

    /**
     * Verifica si un módulo está activo (método estático para dependency checker)
     *
     * @param string $module_id ID del módulo (acepta guiones o guiones bajos)
     * @return bool
     */
    public static function is_module_active($module_id) {
        $modulos_activos = self::get_active_modules_cached();

        // Normalizar: el dependency checker puede enviar 'chat-core' pero
        // el loader usa 'chat_core' con guiones bajos
        $id_normalizado = str_replace('-', '_', $module_id);

        // Verificar tanto el ID original como el normalizado
        return in_array($module_id, $modulos_activos, true)
            || in_array($id_normalizado, $modulos_activos, true);
    }

    /**
     * Obtiene un módulo cargado
     *
     * @param string $module_id
     * @return Flavor_Chat_Module_Interface|null
     */
    public function get_module($module_id) {
        if (isset($this->loaded_modules[$module_id])) {
            return $this->loaded_modules[$module_id];
        }

        $id_normalizado = str_replace('-', '_', $module_id);
        if (isset($this->loaded_modules[$id_normalizado])) {
            return $this->loaded_modules[$id_normalizado];
        }

        if (!isset($this->registered_modules[$module_id]) && isset($this->registered_modules[$id_normalizado])) {
            $module_id = $id_normalizado;
        }

        if (!isset($this->registered_modules[$module_id])) {
            return null;
        }

        $allow_lazy_load = apply_filters('flavor_chat_ia_lazy_load_modules', true, $module_id);
        if ($allow_lazy_load || self::is_module_active($module_id)) {
            $this->load_module($module_id);
        }

        return $this->loaded_modules[$module_id] ?? null;
    }

    /**
     * Obtiene todos los módulos cargados
     *
     * @return array
     */
    public function get_loaded_modules() {
        return $this->loaded_modules;
    }

    /**
     * Obtiene todos los módulos registrados (para admin)
     *
     * @return array
     */
    public function get_registered_modules() {
        $modules_info = [];

        if ($this->modules_metadata_cache === null) {
            $this->rebuild_metadata_cache();
        }

        foreach ($this->registered_modules as $id => $module_data) {
            if (isset($this->loaded_modules[$id])) {
                $module = $this->loaded_modules[$id];
                $modules_info[$id] = [
                    'id' => $module->get_id(),
                    'name' => $module->get_name(),
                    'description' => $module->get_description(),
                    'icon' => method_exists($module, 'get_icon') ? $module->get_icon() : 'dashicons-admin-plugins',
                    'color' => method_exists($module, 'get_color') ? $module->get_color() : '#3b82f6',
                    'ecosystem' => method_exists($module, 'get_ecosystem_metadata')
                        ? $module->get_ecosystem_metadata()
                        : [],
                    'dashboard' => method_exists($module, 'get_dashboard_metadata')
                        ? $module->get_dashboard_metadata()
                        : [],
                    'can_activate' => $module->can_activate(),
                    'activation_error' => $module->get_activation_error(),
                    'is_loaded' => true,
                ];
                continue;
            }

            $cached_meta = $this->modules_metadata_cache[$id] ?? [];
            $fallback_name = ucwords(str_replace(['_', '-'], ' ', $id));
            $name = trim((string) ($cached_meta['name'] ?? ''));
            $description = trim((string) ($cached_meta['description'] ?? ''));

            $modules_info[$id] = [
                'id' => $id,
                'name' => $name !== '' ? $name : $fallback_name,
                'description' => $description,
                'icon' => $cached_meta['icon'] ?? 'dashicons-admin-plugins',
                'color' => $cached_meta['color'] ?? '#3b82f6',
                'ecosystem' => $cached_meta['ecosystem'] ?? [],
                'can_activate' => true,
                'activation_error' => '',
                'is_loaded' => false,
            ];
        }

        return $modules_info;
    }

    /**
     * Obtiene todas las acciones disponibles de todos los módulos
     *
     * @return array
     */
    public function get_all_actions() {
        $actions = [];

        foreach ($this->loaded_modules as $module_id => $module) {
            $module_actions = $module->get_actions();
            foreach ($module_actions as $action_name => $action_info) {
                $actions[$module_id . ':' . $action_name] = array_merge($action_info, [
                    'module' => $module_id,
                ]);
            }
        }

        return $actions;
    }

    /**
     * Ejecuta una acción de un módulo
     *
     * @param string $action_name Formato: "module_id:action" o simplemente "action"
     * @param array $params
     * @return array
     */
    public function execute_action($action_name, $params = []) {
        // Detectar formato module:action
        if (strpos($action_name, ':') !== false) {
            list($module_id, $action) = explode(':', $action_name, 2);

            if (isset($this->loaded_modules[$module_id])) {
                return $this->loaded_modules[$module_id]->execute_action($action, $params);
            }
        }

        // Buscar en todos los módulos
        foreach ($this->loaded_modules as $module) {
            $actions = $module->get_actions();
            if (isset($actions[$action_name])) {
                return $module->execute_action($action_name, $params);
            }
        }

        return [
            'success' => false,
            'error' => "Acción no encontrada: {$action_name}",
        ];
    }

    /**
     * Obtiene todas las definiciones de tools para Claude
     *
     * @return array
     */
    public function get_all_tool_definitions() {
        $tools = [];

        foreach ($this->loaded_modules as $module) {
            $module_tools = $module->get_tool_definitions();
            $tools = array_merge($tools, $module_tools);
        }

        return $tools;
    }

    /**
     * Obtiene todo el conocimiento base de los módulos
     *
     * @return string
     */
    public function get_combined_knowledge_base() {
        $knowledge = [];

        foreach ($this->loaded_modules as $module) {
            $module_knowledge = $module->get_knowledge_base();
            if (!empty($module_knowledge)) {
                $knowledge[] = "## " . $module->get_name() . "\n" . $module_knowledge;
            }
        }

        return implode("\n\n", $knowledge);
    }

    /**
     * Obtiene todas las FAQs de los módulos
     *
     * @return array
     */
    public function get_all_faqs() {
        $faqs = [];

        foreach ($this->loaded_modules as $module) {
            $module_faqs = $module->get_faqs();
            $faqs = array_merge($faqs, $module_faqs);
        }

        return $faqs;
    }

    /**
     * Obtiene la visibilidad de un módulo específico
     * Optimizado con caché para evitar cargar módulos
     *
     * @param string $module_id ID del módulo
     * @return string|null Visibilidad del módulo o null si no existe
     */
    public function get_module_visibility($module_id) {
        // Usar caché estática para evitar múltiples get_option
        $visibilidades_configuradas = self::get_visibility_settings_cached();

        if (isset($visibilidades_configuradas[$module_id])) {
            return $visibilidades_configuradas[$module_id];
        }

        // Si el módulo está cargado, obtener su visibilidad por defecto
        if (isset($this->loaded_modules[$module_id])) {
            return $this->loaded_modules[$module_id]->get_visibility();
        }

        // Usar caché de metadatos si está disponible
        $metadata = $this->get_module_metadata($module_id);
        if ($metadata && isset($metadata['visibility'])) {
            return $metadata['visibility'];
        }

        // Fallback: cargar módulo temporalmente (evitar si es posible)
        if (isset($this->registered_modules[$module_id])) {
            $module_data = $this->registered_modules[$module_id];

            if (file_exists($module_data['file'])) {
                require_once $module_data['file'];

                if (class_exists($module_data['class'])) {
                    $module_temporal = new $module_data['class']();
                    return $module_temporal->get_visibility();
                }
            }
        }

        return null;
    }

    /**
     * Obtiene la capacidad requerida de un módulo específico
     * Optimizado con caché para evitar cargar módulos
     *
     * @param string $module_id ID del módulo
     * @return string|null Capacidad requerida o null si no existe
     */
    public function get_module_required_capability($module_id) {
        // Usar caché estática para evitar múltiples get_option
        $capacidades_configuradas = self::get_capabilities_settings_cached();

        if (isset($capacidades_configuradas[$module_id])) {
            return $capacidades_configuradas[$module_id];
        }

        // Si el módulo está cargado, obtener su capacidad por defecto
        if (isset($this->loaded_modules[$module_id])) {
            return $this->loaded_modules[$module_id]->get_required_capability();
        }

        // Usar caché de metadatos si está disponible
        $metadata = $this->get_module_metadata($module_id);
        if ($metadata && isset($metadata['capability'])) {
            return $metadata['capability'];
        }

        // Fallback: cargar módulo temporalmente (evitar si es posible)
        if (isset($this->registered_modules[$module_id])) {
            $module_data = $this->registered_modules[$module_id];

            if (file_exists($module_data['file'])) {
                require_once $module_data['file'];

                if (class_exists($module_data['class'])) {
                    $module_temporal = new $module_data['class']();
                    return $module_temporal->get_required_capability();
                }
            }
        }

        return 'read';
    }

    /**
     * Obtiene información de visibilidad de todos los módulos registrados
     * Usa caché para evitar cargar todos los módulos en cada petición
     *
     * @return array Array con información de visibilidad por módulo
     */
    public function get_all_modules_visibility_info() {
        $informacion_visibilidad = [];
        // Usar cachés estáticas para evitar múltiples get_option
        $visibilidades_configuradas = self::get_visibility_settings_cached();
        $capacidades_configuradas = self::get_capabilities_settings_cached();

        // Intentar usar caché de metadatos primero
        if ($this->modules_metadata_cache === null) {
            // Si no hay caché, reconstruir (solo una vez)
            $this->rebuild_metadata_cache();
        }

        foreach ($this->registered_modules as $module_id => $module_data) {
            // Usar caché si está disponible
            $cached_meta = $this->modules_metadata_cache[$module_id] ?? null;

            if ($cached_meta) {
                $informacion_visibilidad[$module_id] = [
                    'id' => $module_id,
                    'name' => $cached_meta['name'],
                    'default_visibility' => $cached_meta['visibility'],
                    'current_visibility' => $visibilidades_configuradas[$module_id]
                        ?? $cached_meta['visibility'],
                    'default_capability' => $cached_meta['capability'],
                    'current_capability' => $capacidades_configuradas[$module_id]
                        ?? $cached_meta['capability'],
                ];
                continue;
            }

            // Fallback: cargar módulo si no está en caché (no debería ocurrir)
            if (file_exists($module_data['file'])) {
                require_once $module_data['file'];

                if (class_exists($module_data['class'])) {
                    $module_temporal = new $module_data['class']();

                    $informacion_visibilidad[$module_id] = [
                        'id' => $module_id,
                        'name' => $module_temporal->get_name(),
                        'default_visibility' => method_exists($module_temporal, 'get_default_visibility')
                            ? $module_temporal->get_default_visibility()
                            : 'public',
                        'current_visibility' => $visibilidades_configuradas[$module_id]
                            ?? (method_exists($module_temporal, 'get_default_visibility')
                                ? $module_temporal->get_default_visibility()
                                : 'public'),
                        'default_capability' => method_exists($module_temporal, 'get_default_capability')
                            ? $module_temporal->get_default_capability()
                            : 'read',
                        'current_capability' => $capacidades_configuradas[$module_id]
                            ?? (method_exists($module_temporal, 'get_default_capability')
                                ? $module_temporal->get_default_capability()
                                : 'read'),
                    ];
                }
            }
        }

        return $informacion_visibilidad;
    }

    /**
     * Verifica si un usuario puede acceder a un módulo
     *
     * @param string $module_id ID del módulo
     * @param int|null $user_id ID del usuario (null para usuario actual)
     * @return bool
     */
    public function user_can_access_module($module_id, $user_id = null) {
        // Delegar al Access Control si está disponible
        if (class_exists('Flavor_Module_Access_Control')) {
            $control_acceso = Flavor_Module_Access_Control::get_instance();
            return $control_acceso->user_can_access($module_id, $user_id);
        }

        // Fallback básico
        $visibilidad = $this->get_module_visibility($module_id);

        if ($visibilidad === 'public') {
            return true;
        }

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        $capacidad_requerida = $this->get_module_required_capability($module_id);

        return user_can($user_id, $capacidad_requerida);
    }

    /**
     * Verifica si un módulo existe (está registrado)
     *
     * @param string $module_id
     * @return bool
     */
    public function module_exists($module_id) {
        $registered = $this->get_registered_modules();
        return isset($registered[$module_id]);
    }

    /**
     * Verifica si un módulo está cargado en memoria
     *
     * @param string $module_id
     * @return bool
     */
    public function is_module_loaded($module_id) {
        $loaded = $this->get_loaded_modules();
        return isset($loaded[$module_id]);
    }

    /**
     * Obtiene la instancia de un módulo (cargado o registrado)
     *
     * @param string $module_id
     * @return Flavor_Chat_Module_Interface|null
     */
    public function get_module_instance($module_id) {
        // Primero intentar obtener de módulos cargados
        $loaded = $this->get_loaded_modules();
        if (isset($loaded[$module_id])) {
            return $loaded[$module_id];
        }

        // Si no está cargado, intentar instanciar desde registrados
        $registered = $this->get_registered_modules();
        if (!isset($registered[$module_id])) {
            return null;
        }

        $class_name = $registered[$module_id]['class'];
        if (!class_exists($class_name)) {
            return null;
        }

        try {
            return new $class_name();
        } catch (Exception $e) {
            flavor_log_error( "Error al instanciar módulo '{$module_id}': " . $e->getMessage(), 'ModuleLoader' );
            return null;
        }
    }
}

// Invalidar caché de módulos activos cuando se actualizan los settings
add_action('update_option_flavor_chat_ia_settings', function () {
    Flavor_Chat_Module_Loader::invalidate_active_modules_cache();
}, 10, 0);

add_action('update_option_flavor_active_modules', function () {
    Flavor_Chat_Module_Loader::invalidate_active_modules_cache();
}, 10, 0);

// Invalidar caché de visibilidad cuando cambian las configuraciones
add_action('update_option_flavor_modules_visibility', function () {
    Flavor_Chat_Module_Loader::invalidate_visibility_cache();
}, 10, 0);

add_action('update_option_flavor_modules_capabilities', function () {
    Flavor_Chat_Module_Loader::invalidate_visibility_cache();
}, 10, 0);

// Auto-rebuild metadata cache cuando se actualiza el plugin
add_action('upgrader_process_complete', function ($upgrader, $options) {
    if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
        return;
    }

    $our_plugin = plugin_basename(FLAVOR_CHAT_IA_FILE);
    $updated_plugins = $options['plugins'] ?? [];

    if (in_array($our_plugin, $updated_plugins, true)) {
        // Invalidar cachés para forzar rebuild en próxima carga
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $loader->invalidate_metadata_cache();
        Flavor_Chat_Module_Loader::invalidate_visibility_cache();
    }
}, 10, 2);
