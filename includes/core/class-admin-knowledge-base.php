<?php
/**
 * Base de conocimiento para el Asistente Admin de Flavor Platform
 *
 * Proporciona contexto completo sobre módulos, addons, configuración
 * y documentación para que el asistente IA sea un experto.
 *
 * @package FlavorPlatform
 * @since 3.3.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Admin_Knowledge_Base {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Cache de conocimiento
     */
    private $knowledge_cache = null;

    /**
     * ID del usuario actual para filtrar contexto
     */
    private $current_user_id = 0;

    /**
     * Nivel de acceso del usuario actual
     * Valores: 'admin', 'gestor', 'moderador', 'socio', 'visitante'
     */
    private $access_level = 'visitante';

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
    private function __construct() {}

    /**
     * Determina el nivel de acceso del usuario actual
     *
     * @param int|null $user_id ID del usuario (null = usuario actual)
     * @return string Nivel de acceso: 'admin', 'gestor', 'moderador', 'socio', 'visitante'
     */
    public function get_user_access_level($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return 'visitante';
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return 'visitante';
        }

        // Verificar roles en orden de prioridad
        // 1. Administrator de WordPress o flavor_admin = acceso total
        if (user_can($user_id, 'manage_options') || in_array('flavor_admin', $user->roles)) {
            return 'admin';
        }

        // 2. Gestor = acceso a gestión de módulos
        if (in_array('flavor_gestor', $user->roles) || user_can($user_id, 'flavor_view_analytics')) {
            return 'gestor';
        }

        // 3. Moderador = acceso a moderación
        if (in_array('flavor_moderador', $user->roles) || user_can($user_id, 'flavor_moderate_content')) {
            return 'moderador';
        }

        // 4. Socio = acceso limitado a sus propios datos
        if (in_array('flavor_socio', $user->roles) || user_can($user_id, 'flavor_view_dashboard')) {
            return 'socio';
        }

        // 5. Visitante = solo información pública
        return 'visitante';
    }

    /**
     * Obtiene los módulos a los que el usuario tiene acceso de gestión
     *
     * @param int|null $user_id ID del usuario
     * @return array Lista de IDs de módulos con acceso
     */
    public function get_user_accessible_modules($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $access_level = $this->get_user_access_level($user_id);

        // Admin y gestor ven todos los módulos activos
        if (in_array($access_level, ['admin', 'gestor'])) {
            return get_option('flavor_active_modules', []);
        }

        // Moderador ve módulos que puede moderar
        if ($access_level === 'moderador') {
            return ['incidencias', 'foros', 'red-social']; // Módulos típicos de moderación
        }

        // Para socio/visitante, verificar roles de módulo específicos
        if (class_exists('Flavor_Role_Manager')) {
            $role_manager = Flavor_Role_Manager::get_instance();
            $module_roles = $role_manager->obtener_roles_modulo_usuario($user_id);
            return array_keys($module_roles);
        }

        return [];
    }

    /**
     * Obtiene el contexto completo para el assistant según el rol del usuario
     *
     * @param string $current_page Página actual del admin
     * @param int|null $user_id ID del usuario (null = usuario actual)
     * @return string
     */
    public function get_full_admin_context($current_page = '', $user_id = null) {
        // Establecer usuario y nivel de acceso
        $this->current_user_id = $user_id ?? get_current_user_id();
        $this->access_level = $this->get_user_access_level($this->current_user_id);

        // Cache key incluye usuario y su nivel de acceso
        $cache_key = 'flavor_admin_kb_' . md5($current_page . '_' . $this->current_user_id . '_' . $this->access_level);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $context = [];

        // 0. Información del usuario y su rol (siempre incluir)
        $context[] = $this->get_user_context();

        // 1. Información general de Flavor Platform
        $context[] = $this->get_platform_overview();

        // 2. Módulos disponibles y activos (filtrado por acceso)
        $context[] = $this->get_modules_context();

        // Solo para admin/gestor: addons y perfil
        if (in_array($this->access_level, ['admin', 'gestor'])) {
            // 3. Addons instalados
            $context[] = $this->get_addons_context();

            // 4. Perfil de aplicación actual
            $context[] = $this->get_app_profile_context();
        }

        // 5. DATOS EN TIEMPO REAL (filtrado por permisos)
        $context[] = $this->get_live_modules_data();

        // 6. Contexto de la página actual
        if (!empty($current_page)) {
            $context[] = $this->get_page_context($current_page);
        }

        // Solo para admin/gestor: guías de administración
        if (in_array($this->access_level, ['admin', 'gestor'])) {
            // 7. Estado de configuración de módulos
            $context[] = $this->get_setup_status_context();

            // 8. Guías rápidas de administración
            $context[] = $this->get_admin_guides();

            // 9. FAQs de administración
            $context[] = $this->get_admin_faqs();
        } else {
            // Para otros roles: guías específicas
            $context[] = $this->get_member_guides();
        }

        $full_context = implode("\n\n", array_filter($context));

        // Cache por 5 minutos (los datos en tiempo real cambian)
        set_transient($cache_key, $full_context, 5 * MINUTE_IN_SECONDS);

        return $full_context;
    }

    /**
     * Genera contexto sobre el usuario actual y sus permisos
     *
     * @return string
     */
    private function get_user_context() {
        $user = get_userdata($this->current_user_id);
        if (!$user) {
            return "=== CONTEXTO DEL USUARIO ===
Usuario: Visitante (no autenticado)
Nivel de acceso: Público
Nota: Solo puedo proporcionar información pública.";
        }

        $roles_texto = implode(', ', $user->roles);
        $accessible_modules = $this->get_user_accessible_modules($this->current_user_id);

        $nivel_descripcion = [
            'admin' => 'Administrador - Acceso completo a toda la plataforma',
            'gestor' => 'Gestor - Acceso a gestión de módulos y estadísticas',
            'moderador' => 'Moderador - Acceso a moderación de contenido',
            'socio' => 'Socio/Miembro - Acceso a datos propios',
            'visitante' => 'Visitante - Solo información pública',
        ];

        $output = "=== CONTEXTO DEL USUARIO ===
Usuario: {$user->display_name}
Roles WordPress: {$roles_texto}
Nivel de acceso Flavor: {$nivel_descripcion[$this->access_level]}";

        if (!empty($accessible_modules)) {
            $output .= "\nMódulos con acceso: " . implode(', ', $accessible_modules);
        }

        // Añadir restricciones según nivel
        if ($this->access_level === 'socio') {
            $output .= "\n\nNOTA: Este usuario solo puede consultar información sobre sus propios datos (reservas, inscripciones, cuotas, etc.)";
        } elseif ($this->access_level === 'visitante') {
            $output .= "\n\nNOTA: Este usuario solo puede consultar información pública. No mostrar datos sensibles.";
        }

        return $output;
    }

    /**
     * Guías específicas para miembros (no administradores)
     *
     * @return string
     */
    private function get_member_guides() {
        return "=== GUÍAS PARA MIEMBROS ===

ACCEDER A TU PANEL:
- Ve a la página 'Mi Portal' o 'Mi Panel' del sitio
- Desde ahí puedes ver tus datos, reservas, inscripciones, etc.

ACTUALIZAR TUS DATOS:
- En tu panel, busca la sección 'Mi Perfil' o 'Mis Datos'
- Puedes actualizar tu información de contacto

HACER UNA RESERVA:
- Ve a la sección 'Reservas' de tu panel
- Selecciona el recurso y la fecha disponible
- Confirma la reserva

INSCRIBIRTE EN UN EVENTO:
- Busca el evento en la sección 'Eventos'
- Haz clic en 'Inscribirse'
- Revisa el correo de confirmación

VER TUS CUOTAS:
- En tu panel, busca 'Mis Cuotas' o 'Estado de Cuenta'
- Ahí verás el estado de tus pagos

REPORTAR UNA INCIDENCIA:
- Ve a la sección 'Incidencias' o 'Soporte'
- Describe el problema y envíalo
- Recibirás actualizaciones por correo";
    }

    /**
     * Resumen general de Flavor Platform
     */
    private function get_platform_overview() {
        return "=== FLAVOR PLATFORM - VISIÓN GENERAL ===

Flavor Platform es un plugin de WordPress modular para gestionar comunidades, asociaciones,
cooperativas, ayuntamientos, empresas y organizaciones.

CARACTERÍSTICAS PRINCIPALES:
- Sistema modular: activa solo los módulos que necesites
- Perfiles de aplicación: configuraciones preestablecidas según tipo de organización
- Dashboard unificado: panel de control centralizado para miembros
- Multi-idioma: soporte para español, euskera, inglés, francés y catalán
- Apps móviles: generador de aplicaciones Android/iOS
- Chat IA: asistente virtual integrado

ESTRUCTURA DEL PLUGIN:
- Módulos: funcionalidades independientes (Socios, Eventos, Reservas, etc.)
- Addons: extensiones opcionales (Admin Assistant, Web Builder, etc.)
- Perfiles: configuraciones según tipo de organización
- Dashboard: panel frontal para usuarios registrados";
    }

    /**
     * Contexto de módulos disponibles y activos
     */
    private function get_modules_context() {
        $output = ["=== MÓDULOS DE FLAVOR PLATFORM ==="];

        // Obtener módulos activos
        $active_modules = get_option('flavor_active_modules', []);

        // Catálogo completo de módulos con descripciones
        $modules_catalog = $this->get_modules_catalog();

        $output[] = "\nMÓDULOS ACTIVOS EN ESTE SITIO:";
        foreach ($active_modules as $module_id) {
            if (isset($modules_catalog[$module_id])) {
                $mod = $modules_catalog[$module_id];
                $output[] = "- {$mod['nombre']}: {$mod['descripcion']}";
            }
        }

        $output[] = "\nCATÁLOGO COMPLETO DE MÓDULOS DISPONIBLES:";

        // Agrupar por categoría
        $categories = [
            'gestion' => 'Gestión de Miembros',
            'actividades' => 'Actividades y Eventos',
            'espacios' => 'Espacios y Reservas',
            'comunicacion' => 'Comunicación',
            'participacion' => 'Participación',
            'economia' => 'Economía y Finanzas',
            'sostenibilidad' => 'Sostenibilidad',
            'contenidos' => 'Contenidos',
        ];

        foreach ($categories as $cat_id => $cat_name) {
            $cat_modules = array_filter($modules_catalog, fn($m) => ($m['categoria'] ?? '') === $cat_id);
            if (!empty($cat_modules)) {
                $output[] = "\n## {$cat_name}:";
                foreach ($cat_modules as $id => $mod) {
                    $status = in_array($id, $active_modules) ? '✓' : '○';
                    $output[] = "  {$status} {$mod['nombre']}: {$mod['descripcion']}";
                }
            }
        }

        return implode("\n", $output);
    }

    /**
     * Catálogo completo de módulos
     */
    private function get_modules_catalog() {
        return [
            // Gestión de Miembros
            'socios' => [
                'nombre' => 'Socios/Miembros',
                'descripcion' => 'Gestión completa de miembros, cuotas, carnets digitales y directorio',
                'categoria' => 'gestion',
            ],
            'clientes' => [
                'nombre' => 'Clientes',
                'descripcion' => 'CRM básico para gestionar clientes y relaciones comerciales',
                'categoria' => 'gestion',
            ],
            'comunidades' => [
                'nombre' => 'Comunidades',
                'descripcion' => 'Gestión de comunidades de vecinos y propietarios',
                'categoria' => 'gestion',
            ],
            'colectivos' => [
                'nombre' => 'Colectivos',
                'descripcion' => 'Grupos de trabajo, comisiones y equipos internos',
                'categoria' => 'gestion',
            ],

            // Actividades y Eventos
            'eventos' => [
                'nombre' => 'Eventos',
                'descripcion' => 'Calendario de eventos con inscripciones y gestión de asistentes',
                'categoria' => 'actividades',
            ],
            'cursos' => [
                'nombre' => 'Cursos',
                'descripcion' => 'Formación online con lecciones, matrículas y certificados',
                'categoria' => 'actividades',
            ],
            'talleres' => [
                'nombre' => 'Talleres',
                'descripcion' => 'Talleres presenciales con inscripciones y materiales',
                'categoria' => 'actividades',
            ],
            'campanias' => [
                'nombre' => 'Campañas',
                'descripcion' => 'Campañas de recogida de firmas y sensibilización',
                'categoria' => 'actividades',
            ],

            // Espacios y Reservas
            'reservas' => [
                'nombre' => 'Reservas',
                'descripcion' => 'Sistema de reservas de recursos, salas y equipamiento',
                'categoria' => 'espacios',
            ],
            'espacios-comunes' => [
                'nombre' => 'Espacios Comunes',
                'descripcion' => 'Gestión de espacios compartidos con calendario',
                'categoria' => 'espacios',
            ],
            'parkings' => [
                'nombre' => 'Parkings',
                'descripcion' => 'Gestión de plazas de parking y rotación',
                'categoria' => 'espacios',
            ],

            // Comunicación
            'foros' => [
                'nombre' => 'Foros',
                'descripcion' => 'Foros de discusión por categorías y temas',
                'categoria' => 'comunicacion',
            ],
            'chat-interno' => [
                'nombre' => 'Chat Interno',
                'descripcion' => 'Mensajería privada entre miembros',
                'categoria' => 'comunicacion',
            ],
            'chat-grupos' => [
                'nombre' => 'Chat de Grupos',
                'descripcion' => 'Salas de chat grupales por colectivo o tema',
                'categoria' => 'comunicacion',
            ],
            'avisos-municipales' => [
                'nombre' => 'Avisos/Tablón',
                'descripcion' => 'Tablón de anuncios y avisos oficiales',
                'categoria' => 'comunicacion',
            ],
            'email-marketing' => [
                'nombre' => 'Email Marketing',
                'descripcion' => 'Newsletters, listas de correo y automatizaciones',
                'categoria' => 'comunicacion',
            ],
            'red-social' => [
                'nombre' => 'Red Social',
                'descripcion' => 'Red social interna con publicaciones y seguimiento',
                'categoria' => 'comunicacion',
            ],

            // Participación
            'encuestas' => [
                'nombre' => 'Encuestas',
                'descripcion' => 'Encuestas y votaciones con resultados en tiempo real',
                'categoria' => 'participacion',
            ],
            'participacion' => [
                'nombre' => 'Participación',
                'descripcion' => 'Propuestas ciudadanas y debates participativos',
                'categoria' => 'participacion',
            ],
            'presupuestos-participativos' => [
                'nombre' => 'Presupuestos Participativos',
                'descripcion' => 'Votación de proyectos con asignación de presupuesto',
                'categoria' => 'participacion',
            ],
            'transparencia' => [
                'nombre' => 'Transparencia',
                'descripcion' => 'Portal de transparencia con presupuestos y actas',
                'categoria' => 'participacion',
            ],

            // Economía
            'marketplace' => [
                'nombre' => 'Marketplace',
                'descripcion' => 'Tienda de productos y servicios entre miembros',
                'categoria' => 'economia',
            ],
            'grupos-consumo' => [
                'nombre' => 'Grupos de Consumo',
                'descripcion' => 'Pedidos colectivos a productores locales',
                'categoria' => 'economia',
            ],
            'banco-tiempo' => [
                'nombre' => 'Banco de Tiempo',
                'descripcion' => 'Intercambio de servicios y habilidades',
                'categoria' => 'economia',
            ],
            'crowdfunding' => [
                'nombre' => 'Crowdfunding',
                'descripcion' => 'Financiación colectiva de proyectos',
                'categoria' => 'economia',
            ],
            'facturas' => [
                'nombre' => 'Facturación',
                'descripcion' => 'Emisión de facturas y gestión de cobros',
                'categoria' => 'economia',
            ],

            // Sostenibilidad
            'huertos-urbanos' => [
                'nombre' => 'Huertos Urbanos',
                'descripcion' => 'Gestión de parcelas y huertos comunitarios',
                'categoria' => 'sostenibilidad',
            ],
            'compostaje' => [
                'nombre' => 'Compostaje',
                'descripcion' => 'Puntos de compostaje comunitario',
                'categoria' => 'sostenibilidad',
            ],
            'bicicletas-compartidas' => [
                'nombre' => 'Bicicletas',
                'descripcion' => 'Sistema de préstamo de bicicletas',
                'categoria' => 'sostenibilidad',
            ],
            'carpooling' => [
                'nombre' => 'Carpooling',
                'descripcion' => 'Compartir coche para trayectos',
                'categoria' => 'sostenibilidad',
            ],
            'reciclaje' => [
                'nombre' => 'Reciclaje',
                'descripcion' => 'Puntos de reciclaje y gamificación',
                'categoria' => 'sostenibilidad',
            ],

            // Contenidos
            'biblioteca' => [
                'nombre' => 'Biblioteca',
                'descripcion' => 'Catálogo de libros con préstamos y reservas',
                'categoria' => 'contenidos',
            ],
            'multimedia' => [
                'nombre' => 'Multimedia',
                'descripcion' => 'Galería de fotos, vídeos y documentos',
                'categoria' => 'contenidos',
            ],
            'podcast' => [
                'nombre' => 'Podcast',
                'descripcion' => 'Publicación y gestión de podcasts',
                'categoria' => 'contenidos',
            ],
            'radio' => [
                'nombre' => 'Radio',
                'descripcion' => 'Radio comunitaria con programación',
                'categoria' => 'contenidos',
            ],

            // Otros
            'incidencias' => [
                'nombre' => 'Incidencias',
                'descripcion' => 'Sistema de tickets de soporte y averías',
                'categoria' => 'gestion',
            ],
            'tramites' => [
                'nombre' => 'Trámites',
                'descripcion' => 'Gestión de trámites y solicitudes',
                'categoria' => 'gestion',
            ],
            'fichaje-empleados' => [
                'nombre' => 'Fichaje',
                'descripcion' => 'Control de horarios de empleados',
                'categoria' => 'gestion',
            ],
            'ayuda-vecinal' => [
                'nombre' => 'Ayuda Vecinal',
                'descripcion' => 'Red de ayuda mutua entre vecinos',
                'categoria' => 'gestion',
            ],
        ];
    }

    /**
     * Contexto de addons instalados
     */
    private function get_addons_context() {
        $output = ["=== ADDONS INSTALADOS ==="];

        $addons_dir = FLAVOR_PLATFORM_PATH . 'addons/';
        if (!is_dir($addons_dir)) {
            return "";
        }

        $addons = [
            'flavor-admin-assistant' => [
                'nombre' => 'Admin Assistant',
                'descripcion' => 'Asistente IA avanzado para administradores con atajos y comandos',
            ],
            'flavor-network-communities' => [
                'nombre' => 'Network Communities',
                'descripcion' => 'Red de comunidades conectadas (multisite)',
            ],
            'flavor-restaurant-ordering' => [
                'nombre' => 'Restaurant Ordering',
                'descripcion' => 'Sistema de pedidos y reservas para restaurantes',
            ],
            'flavor-demo-orchestrator' => [
                'nombre' => 'Demo Orchestrator',
                'descripcion' => 'Generador de datos de demostración',
            ],
        ];

        foreach ($addons as $slug => $info) {
            if (is_dir($addons_dir . $slug)) {
                $output[] = "✓ {$info['nombre']}: {$info['descripcion']}";
            }
        }

        return implode("\n", $output);
    }

    /**
     * Contexto del perfil de aplicación actual
     */
    private function get_app_profile_context() {
        $output = ["=== PERFIL DE APLICACIÓN ACTUAL ==="];

        $current_profile = get_option('flavor_app_profile', 'asociacion');

        $profiles = [
            'asociacion' => [
                'nombre' => 'Asociación/Colectivo',
                'descripcion' => 'Para asociaciones vecinales, culturales, deportivas',
                'modulos_recomendados' => ['socios', 'eventos', 'foros', 'reservas', 'encuestas'],
            ],
            'ayuntamiento' => [
                'nombre' => 'Ayuntamiento/Institución',
                'descripcion' => 'Para ayuntamientos y administraciones públicas',
                'modulos_recomendados' => ['avisos-municipales', 'tramites', 'participacion', 'transparencia', 'incidencias'],
            ],
            'cooperativa' => [
                'nombre' => 'Cooperativa',
                'descripcion' => 'Para cooperativas de trabajo, consumo o vivienda',
                'modulos_recomendados' => ['socios', 'grupos-consumo', 'transparencia', 'encuestas', 'foros'],
            ],
            'empresa' => [
                'nombre' => 'Empresa/PYME',
                'descripcion' => 'Para pequeñas y medianas empresas',
                'modulos_recomendados' => ['clientes', 'facturas', 'fichaje-empleados', 'reservas', 'incidencias'],
            ],
            'comunidad' => [
                'nombre' => 'Comunidad de Vecinos',
                'descripcion' => 'Para comunidades de propietarios',
                'modulos_recomendados' => ['comunidades', 'incidencias', 'reservas', 'foros', 'encuestas'],
            ],
            'educativo' => [
                'nombre' => 'Centro Educativo',
                'descripcion' => 'Para centros de formación y escuelas',
                'modulos_recomendados' => ['cursos', 'talleres', 'biblioteca', 'eventos', 'foros'],
            ],
        ];

        if (isset($profiles[$current_profile])) {
            $p = $profiles[$current_profile];
            $output[] = "Perfil activo: {$p['nombre']}";
            $output[] = "Descripción: {$p['descripcion']}";
            $output[] = "Módulos recomendados: " . implode(', ', $p['modulos_recomendados']);
        }

        $output[] = "\nPERFILES DISPONIBLES:";
        foreach ($profiles as $id => $p) {
            $status = ($id === $current_profile) ? '→' : ' ';
            $output[] = "{$status} {$p['nombre']}: {$p['descripcion']}";
        }

        return implode("\n", $output);
    }

    /**
     * Contexto de la página actual
     */
    private function get_page_context($page_slug) {
        $pages_help = [
            'flavor-dashboard' => [
                'titulo' => 'Dashboard Principal',
                'ayuda' => 'Panel central con métricas, alertas y accesos rápidos. Muestra el estado general del sitio, módulos activos y actividad reciente.',
            ],
            'flavor-modules' => [
                'titulo' => 'Gestión de Módulos',
                'ayuda' => 'Activa/desactiva módulos según las necesidades. Cada módulo es independiente. Al activar un módulo se crean sus tablas y páginas automáticamente.',
            ],
            'flavor-settings' => [
                'titulo' => 'Configuración General',
                'ayuda' => 'Ajustes globales del plugin: colores, logo, idioma por defecto, configuración del chat IA, y opciones de integración.',
            ],
            'flavor-app-profile' => [
                'titulo' => 'Perfil de Aplicación',
                'ayuda' => 'Selecciona el tipo de organización para preconfigurar módulos recomendados. Cambiarlo no desactiva módulos ya configurados.',
            ],
            'flavor-platform' => [
                'titulo' => 'Configuración Chat IA',
                'ayuda' => 'Configura el asistente virtual: proveedor de IA (Claude, OpenAI, Mistral, DeepSeek), API keys, personalidad del asistente y base de conocimiento.',
            ],
            'flavor-app-generator' => [
                'titulo' => 'Generador de Apps',
                'ayuda' => 'Describe tu proyecto y la IA generará la estructura completa: páginas, módulos activos y configuración inicial.',
            ],
            'flavor-addons' => [
                'titulo' => 'Gestión de Addons',
                'ayuda' => 'Instala y configura extensiones adicionales como Web Builder Pro, Admin Assistant o Network Communities.',
            ],
            'flavor-tours' => [
                'titulo' => 'Tours Guiados',
                'ayuda' => 'Tours interactivos para aprender a usar la plataforma. Recomendados para nuevos administradores.',
            ],
            'flavor-platform-docs' => [
                'titulo' => 'Documentación',
                'ayuda' => 'Documentación completa del plugin, guías de uso y referencia técnica.',
            ],
            'flavor-docs' => [
                'titulo' => 'Documentación',
                'ayuda' => 'Documentación completa del plugin, guías de uso y referencia técnica.',
            ],
        ];

        if (isset($pages_help[$page_slug])) {
            $p = $pages_help[$page_slug];
            return "=== PÁGINA ACTUAL: {$p['titulo']} ===\n{$p['ayuda']}";
        }

        // Detectar páginas de módulos
        if (strpos($page_slug, 'flavor-module-') === 0) {
            $module_id = str_replace('flavor-module-', '', $page_slug);
            $catalog = $this->get_modules_catalog();
            if (isset($catalog[$module_id])) {
                $m = $catalog[$module_id];
                return "=== MÓDULO: {$m['nombre']} ===\n{$m['descripcion']}\n\nDesde aquí puedes gestionar todos los aspectos de este módulo: listados, configuración, estadísticas y acciones.";
            }
        }

        return "";
    }

    /**
     * Obtiene datos en tiempo real de los módulos activos
     * Incluye estadísticas, registros recientes, pendientes, etc.
     * FILTRADO POR ROL: admin/gestor ven todo, socio solo sus datos, visitante nada sensible
     */
    private function get_live_modules_data() {
        global $wpdb;

        // Visitantes no ven datos en tiempo real
        if ($this->access_level === 'visitante') {
            return "=== INFORMACIÓN DEL SITIO ===
Este sitio usa Flavor Platform para gestionar su comunidad.
Para ver más información, inicia sesión con tu cuenta de miembro.";
        }

        // Para socios, mostrar solo SUS datos
        if ($this->access_level === 'socio') {
            return $this->get_member_own_data();
        }

        // Para admin/gestor/moderador: mostrar datos completos
        $output = ["=== DATOS EN TIEMPO REAL DEL SITIO ==="];

        // Módulos accesibles según el rol
        $accessible_modules = $this->get_user_accessible_modules($this->current_user_id);
        $active_modules = get_option('flavor_active_modules', []);

        // Filtrar solo módulos activos Y accesibles
        $modules_to_show = array_intersect($active_modules, $accessible_modules);

        // Para admin/gestor, mostrar todos los activos
        if (in_array($this->access_level, ['admin', 'gestor'])) {
            $modules_to_show = $active_modules;

            // Estadísticas generales de WordPress (solo admin/gestor)
            $output[] = "\n## ESTADÍSTICAS GENERALES:";
            $total_users = count_users();
            $output[] = "- Usuarios totales: " . $total_users['total_users'];
            $output[] = "- Posts publicados: " . wp_count_posts('post')->publish;
            $output[] = "- Páginas publicadas: " . wp_count_posts('page')->publish;
        }

        // Datos de cada módulo
        foreach ($modules_to_show as $module_id) {
            $module_data = $this->get_module_live_data($module_id);
            if (!empty($module_data)) {
                $output[] = $module_data;
            }
        }

        // Actividad reciente (solo admin/gestor)
        if (in_array($this->access_level, ['admin', 'gestor'])) {
            $output[] = $this->get_recent_activity();
        }

        return implode("\n", $output);
    }

    /**
     * Obtiene los datos propios del miembro actual
     * Para usuarios con rol 'socio' que solo pueden ver sus propios datos
     *
     * @return string
     */
    private function get_member_own_data() {
        global $wpdb;

        $user_id = $this->current_user_id;
        $user = get_userdata($user_id);
        $user_email = $user ? $user->user_email : '';

        $output = ["=== TUS DATOS EN LA PLATAFORMA ==="];
        $output[] = "Usuario: {$user->display_name}";
        $output[] = "Email: {$user_email}";

        $table_prefix = $wpdb->prefix . 'flavor_';

        // Datos de socio
        $table_socios = $table_prefix . 'socios';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_socios}'") === $table_socios) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_socios} WHERE user_id = %d OR email = %s LIMIT 1",
                $user_id, $user_email
            ));
            if ($socio) {
                $output[] = "\n## TU FICHA DE SOCIO:";
                $output[] = "- Estado: {$socio->estado}";
                $output[] = "- Número de socio: " . ($socio->numero_socio ?? 'N/A');

                // Cuotas pendientes
                $table_cuotas = $table_prefix . 'socios_cuotas';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table_cuotas}'") === $table_cuotas) {
                    $cuotas_pendientes = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$table_cuotas} WHERE socio_id = %d AND estado = 'pendiente'",
                        $socio->id
                    ));
                    $output[] = "- Cuotas pendientes: {$cuotas_pendientes}";
                }
            }
        }

        // Reservas del usuario
        $table_reservas = $table_prefix . 'reservas';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_reservas}'") === $table_reservas) {
            $mis_reservas = $wpdb->get_results($wpdb->prepare(
                "SELECT recurso_nombre, fecha_inicio, estado FROM {$table_reservas}
                WHERE user_id = %d AND fecha_inicio >= NOW()
                ORDER BY fecha_inicio ASC LIMIT 5",
                $user_id
            ));
            if (!empty($mis_reservas)) {
                $output[] = "\n## TUS PRÓXIMAS RESERVAS:";
                foreach ($mis_reservas as $r) {
                    $fecha = date('d/m/Y H:i', strtotime($r->fecha_inicio));
                    $output[] = "- {$r->recurso_nombre} - {$fecha} ({$r->estado})";
                }
            }
        }

        // Inscripciones en eventos
        $table_eventos_insc = $table_prefix . 'eventos_inscripciones';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_eventos_insc}'") === $table_eventos_insc) {
            $mis_inscripciones = $wpdb->get_results($wpdb->prepare(
                "SELECT ei.*, e.titulo, e.fecha FROM {$table_eventos_insc} ei
                JOIN {$table_prefix}eventos e ON ei.evento_id = e.id
                WHERE ei.user_id = %d AND e.fecha >= CURDATE()
                ORDER BY e.fecha ASC LIMIT 5",
                $user_id
            ));
            if (!empty($mis_inscripciones)) {
                $output[] = "\n## TUS PRÓXIMOS EVENTOS:";
                foreach ($mis_inscripciones as $i) {
                    $fecha = date('d/m/Y', strtotime($i->fecha));
                    $output[] = "- {$i->titulo} - {$fecha} ({$i->estado})";
                }
            }
        }

        // Incidencias propias
        $table_incidencias = $table_prefix . 'incidencias';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_incidencias}'") === $table_incidencias) {
            $mis_incidencias = $wpdb->get_results($wpdb->prepare(
                "SELECT titulo, estado, prioridad, created_at FROM {$table_incidencias}
                WHERE user_id = %d
                ORDER BY created_at DESC LIMIT 5",
                $user_id
            ));
            if (!empty($mis_incidencias)) {
                $output[] = "\n## TUS INCIDENCIAS:";
                foreach ($mis_incidencias as $i) {
                    $fecha = date('d/m/Y', strtotime($i->created_at));
                    $output[] = "- [{$i->estado}] {$i->titulo} ({$fecha})";
                }
            }
        }

        // Pedidos en grupos de consumo
        $table_pedidos = $table_prefix . 'gc_pedidos';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_pedidos}'") === $table_pedidos) {
            $mis_pedidos = $wpdb->get_results($wpdb->prepare(
                "SELECT id, total, estado, created_at FROM {$table_pedidos}
                WHERE user_id = %d
                ORDER BY created_at DESC LIMIT 5",
                $user_id
            ));
            if (!empty($mis_pedidos)) {
                $output[] = "\n## TUS ÚLTIMOS PEDIDOS:";
                foreach ($mis_pedidos as $p) {
                    $fecha = date('d/m/Y', strtotime($p->created_at));
                    $output[] = "- Pedido #{$p->id} - {$p->total}€ ({$p->estado}) - {$fecha}";
                }
            }
        }

        return implode("\n", $output);
    }

    /**
     * Obtiene datos en tiempo real de un módulo específico
     * FILTRADO POR ROL: admin/gestor ven datos personales, moderador solo estadísticas
     */
    private function get_module_live_data($module_id) {
        global $wpdb;

        $table_prefix = $wpdb->prefix . 'flavor_';

        // Determinar si puede ver datos personales (nombres, emails)
        $can_see_personal_data = in_array($this->access_level, ['admin', 'gestor']);

        switch ($module_id) {
            case 'socios':
                $table = $table_prefix . 'socios';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    $activos = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE estado = 'activo'");
                    $pendientes = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE estado = 'pendiente'");

                    $output = "\n## MÓDULO SOCIOS/MIEMBROS:";
                    $output .= "\n- Total miembros: {$total}";
                    $output .= "\n- Miembros activos: {$activos}";
                    $output .= "\n- Solicitudes pendientes: {$pendientes}";

                    // Solo admin/gestor ven datos personales de los últimos registros
                    if ($can_see_personal_data) {
                        $recientes = $wpdb->get_results("SELECT nombre, apellidos, created_at FROM {$table} ORDER BY created_at DESC LIMIT 5");
                        if (!empty($recientes)) {
                            $output .= "\n- Últimos 5 registros:";
                            foreach ($recientes as $s) {
                                $nombre = trim($s->nombre . ' ' . $s->apellidos);
                                $fecha = date('d/m/Y', strtotime($s->created_at));
                                $output .= "\n  · {$nombre} ({$fecha})";
                            }
                        }
                    }
                    return $output;
                }
                break;

            case 'eventos':
                $table = $table_prefix . 'eventos';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    $proximos = $wpdb->get_results("SELECT titulo, fecha, hora FROM {$table} WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 5");
                    $inscripciones_pendientes = 0;
                    $table_insc = $table_prefix . 'eventos_inscripciones';
                    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_insc}'") === $table_insc) {
                        $inscripciones_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM {$table_insc} WHERE estado = 'pendiente'");
                    }

                    $output = "\n## MÓDULO EVENTOS:";
                    $output .= "\n- Total eventos: {$total}";
                    $output .= "\n- Inscripciones pendientes: {$inscripciones_pendientes}";

                    if (!empty($proximos)) {
                        $output .= "\n- Próximos eventos:";
                        foreach ($proximos as $e) {
                            $fecha = date('d/m/Y', strtotime($e->fecha));
                            $output .= "\n  · {$e->titulo} ({$fecha} {$e->hora})";
                        }
                    }
                    return $output;
                }
                break;

            case 'reservas':
                $table = $table_prefix . 'reservas';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    $pendientes = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE estado = 'pendiente'");
                    $hoy = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE DATE(fecha_inicio) = CURDATE()");

                    $output = "\n## MÓDULO RESERVAS:";
                    $output .= "\n- Total reservas: {$total}";
                    $output .= "\n- Reservas pendientes de aprobar: {$pendientes}";
                    $output .= "\n- Reservas para hoy: {$hoy}";

                    // Solo admin/gestor ven nombres de usuarios en reservas
                    if ($can_see_personal_data) {
                        $proximas = $wpdb->get_results("SELECT recurso_nombre, fecha_inicio, usuario_nombre FROM {$table} WHERE fecha_inicio >= NOW() ORDER BY fecha_inicio ASC LIMIT 5");
                        if (!empty($proximas)) {
                            $output .= "\n- Próximas reservas:";
                            foreach ($proximas as $r) {
                                $fecha = date('d/m/Y H:i', strtotime($r->fecha_inicio));
                                $output .= "\n  · {$r->recurso_nombre} - {$r->usuario_nombre} ({$fecha})";
                            }
                        }
                    }
                    return $output;
                }
                break;

            case 'incidencias':
                $table = $table_prefix . 'incidencias';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    $abiertas = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE estado IN ('abierta', 'pendiente', 'en_proceso')");
                    $urgentes = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE prioridad = 'alta' AND estado != 'cerrada'");
                    $recientes = $wpdb->get_results("SELECT titulo, estado, prioridad, created_at FROM {$table} ORDER BY created_at DESC LIMIT 5");

                    $output = "\n## MÓDULO INCIDENCIAS:";
                    $output .= "\n- Total incidencias: {$total}";
                    $output .= "\n- Incidencias abiertas: {$abiertas}";
                    $output .= "\n- Incidencias urgentes: {$urgentes}";

                    if (!empty($recientes)) {
                        $output .= "\n- Últimas incidencias:";
                        foreach ($recientes as $i) {
                            $fecha = date('d/m/Y', strtotime($i->created_at));
                            $output .= "\n  · [{$i->estado}] {$i->titulo} ({$fecha})";
                        }
                    }
                    return $output;
                }
                break;

            case 'grupos-consumo':
                $table_pedidos = $table_prefix . 'gc_pedidos';
                $table_ciclos = $table_prefix . 'gc_ciclos';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table_pedidos}'") === $table_pedidos) {
                    $total_pedidos = $wpdb->get_var("SELECT COUNT(*) FROM {$table_pedidos}");
                    $pendientes = $wpdb->get_var("SELECT COUNT(*) FROM {$table_pedidos} WHERE estado = 'pendiente'");

                    $output = "\n## MÓDULO GRUPOS DE CONSUMO:";
                    $output .= "\n- Total pedidos: {$total_pedidos}";
                    $output .= "\n- Pedidos pendientes: {$pendientes}";

                    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_ciclos}'") === $table_ciclos) {
                        $ciclo_activo = $wpdb->get_row("SELECT nombre, fecha_cierre FROM {$table_ciclos} WHERE estado = 'abierto' ORDER BY fecha_cierre ASC LIMIT 1");
                        if ($ciclo_activo) {
                            $output .= "\n- Ciclo activo: {$ciclo_activo->nombre} (cierra: " . date('d/m/Y', strtotime($ciclo_activo->fecha_cierre)) . ")";
                        }
                    }
                    return $output;
                }
                break;

            case 'marketplace':
                $table = $table_prefix . 'marketplace_productos';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    $activos = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE estado = 'publicado'");
                    $pendientes = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE estado = 'pendiente'");

                    $output = "\n## MÓDULO MARKETPLACE:";
                    $output .= "\n- Total anuncios: {$total}";
                    $output .= "\n- Anuncios activos: {$activos}";
                    $output .= "\n- Pendientes de aprobar: {$pendientes}";
                    return $output;
                }
                break;

            case 'cursos':
                $table = $table_prefix . 'cursos';
                $table_matriculas = $table_prefix . 'cursos_matriculas';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    $activos = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE estado = 'publicado'");

                    $output = "\n## MÓDULO CURSOS:";
                    $output .= "\n- Total cursos: {$total}";
                    $output .= "\n- Cursos activos: {$activos}";

                    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_matriculas}'") === $table_matriculas) {
                        $matriculas = $wpdb->get_var("SELECT COUNT(*) FROM {$table_matriculas}");
                        $pendientes = $wpdb->get_var("SELECT COUNT(*) FROM {$table_matriculas} WHERE estado = 'pendiente'");
                        $output .= "\n- Total matrículas: {$matriculas}";
                        $output .= "\n- Matrículas pendientes: {$pendientes}";
                    }
                    return $output;
                }
                break;

            case 'foros':
                $table = $table_prefix . 'foros';
                $table_temas = $table_prefix . 'foros_temas';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total_foros = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

                    $output = "\n## MÓDULO FOROS:";
                    $output .= "\n- Total foros: {$total_foros}";

                    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_temas}'") === $table_temas) {
                        $total_temas = $wpdb->get_var("SELECT COUNT(*) FROM {$table_temas}");
                        $recientes = $wpdb->get_results("SELECT titulo, created_at FROM {$table_temas} ORDER BY created_at DESC LIMIT 3");
                        $output .= "\n- Total temas: {$total_temas}";

                        if (!empty($recientes)) {
                            $output .= "\n- Últimos temas:";
                            foreach ($recientes as $t) {
                                $fecha = date('d/m/Y', strtotime($t->created_at));
                                $output .= "\n  · {$t->titulo} ({$fecha})";
                            }
                        }
                    }
                    return $output;
                }
                break;

            case 'biblioteca':
                $table = $table_prefix . 'biblioteca';
                $table_prestamos = $table_prefix . 'biblioteca_prestamos';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

                    $output = "\n## MÓDULO BIBLIOTECA:";
                    $output .= "\n- Total libros: {$total}";

                    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_prestamos}'") === $table_prestamos) {
                        $prestamos_activos = $wpdb->get_var("SELECT COUNT(*) FROM {$table_prestamos} WHERE estado = 'activo'");
                        $vencidos = $wpdb->get_var("SELECT COUNT(*) FROM {$table_prestamos} WHERE estado = 'activo' AND fecha_devolucion < CURDATE()");
                        $output .= "\n- Préstamos activos: {$prestamos_activos}";
                        $output .= "\n- Préstamos vencidos: {$vencidos}";
                    }
                    return $output;
                }
                break;

            case 'encuestas':
                $table = $table_prefix . 'encuestas';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    $activas = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE estado = 'activa'");

                    $output = "\n## MÓDULO ENCUESTAS:";
                    $output .= "\n- Total encuestas: {$total}";
                    $output .= "\n- Encuestas activas: {$activas}";
                    return $output;
                }
                break;

            case 'banco-tiempo':
                $table = $table_prefix . 'banco_tiempo_servicios';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    $activos = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE estado = 'activo'");

                    $output = "\n## MÓDULO BANCO DE TIEMPO:";
                    $output .= "\n- Total servicios: {$total}";
                    $output .= "\n- Servicios activos: {$activos}";
                    return $output;
                }
                break;

            case 'huertos-urbanos':
                $table = $table_prefix . 'huertos';
                if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                    $disponibles = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE estado = 'disponible'");

                    $output = "\n## MÓDULO HUERTOS URBANOS:";
                    $output .= "\n- Total parcelas: {$total}";
                    $output .= "\n- Parcelas disponibles: {$disponibles}";
                    return $output;
                }
                break;
        }

        return '';
    }

    /**
     * Obtiene actividad reciente del sitio
     */
    private function get_recent_activity() {
        $output = "\n## ACTIVIDAD RECIENTE:";

        // Últimos posts
        $posts = get_posts([
            'numberposts' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (!empty($posts)) {
            $output .= "\n- Últimas publicaciones:";
            foreach ($posts as $post) {
                $fecha = date('d/m/Y', strtotime($post->post_date));
                $output .= "\n  · {$post->post_title} ({$fecha})";
            }
        }

        // Últimos usuarios registrados
        $users = get_users([
            'number' => 5,
            'orderby' => 'registered',
            'order' => 'DESC',
        ]);

        if (!empty($users)) {
            $output .= "\n- Últimos usuarios registrados:";
            foreach ($users as $user) {
                $fecha = date('d/m/Y', strtotime($user->user_registered));
                $output .= "\n  · {$user->display_name} ({$fecha})";
            }
        }

        // Últimos comentarios
        $comments = get_comments([
            'number' => 3,
            'status' => 'approve',
            'orderby' => 'comment_date',
            'order' => 'DESC',
        ]);

        if (!empty($comments)) {
            $output .= "\n- Últimos comentarios:";
            foreach ($comments as $comment) {
                $fecha = date('d/m/Y', strtotime($comment->comment_date));
                $titulo = get_the_title($comment->comment_post_ID);
                $output .= "\n  · {$comment->comment_author} en '{$titulo}' ({$fecha})";
            }
        }

        return $output;
    }

    /**
     * Guías rápidas de administración
     */
    private function get_admin_guides() {
        return "=== GUÍAS RÁPIDAS PARA ADMINISTRADORES ===

PRIMEROS PASOS:
1. Selecciona tu perfil de aplicación en 'Flavor → Perfil'
2. Activa los módulos que necesites en 'Flavor → Módulos'
3. Configura los colores y logo en 'Flavor → Configuración'
4. Crea las páginas del portal con el 'Generador de Apps' o manualmente

ACTIVAR UN MÓDULO:
1. Ve a Flavor → Módulos
2. Busca el módulo deseado
3. Haz clic en 'Activar'
4. El módulo creará automáticamente sus tablas y menús

CONFIGURAR EL CHAT IA:
1. Ve a Flavor → Chat IA
2. Selecciona un proveedor (Mistral y DeepSeek tienen tier gratuito)
3. Introduce tu API key
4. Personaliza el nombre y rol del asistente
5. Añade información de tu negocio en 'Base de conocimiento'

CREAR PÁGINAS DEL PORTAL:
1. Usa el Generador de Apps para crear estructura automática
2. O crea páginas manualmente con los shortcodes de cada módulo
3. Los shortcodes disponibles aparecen en la documentación de cada módulo

GESTIONAR USUARIOS:
- Los miembros se gestionan desde el módulo 'Socios' o 'Clientes'
- Los roles de WordPress se sincronizan automáticamente
- Puedes crear roles personalizados desde 'Usuarios → Roles'";
    }

    /**
     * FAQs de administración
     */
    private function get_admin_faqs() {
        return "=== PREGUNTAS FRECUENTES DE ADMINISTRACIÓN ===

P: ¿Cómo activo un módulo?
R: Ve a Flavor → Módulos, busca el módulo y haz clic en Activar.

P: ¿Qué proveedor de IA es mejor?
R: Mistral y DeepSeek tienen tier gratuito. Claude (Anthropic) y OpenAI son de pago pero más potentes.

P: ¿Cómo añado mi logo?
R: Ve a Flavor → Configuración → Apariencia y sube tu logo.

P: ¿Dónde ven los usuarios su panel?
R: En la página /mi-panel/ o /dashboard/ según la configuración.

P: ¿Cómo creo un evento?
R: Activa el módulo Eventos, luego ve a Flavor → Eventos → Añadir nuevo.

P: ¿Puedo tener varios idiomas?
R: Sí, Flavor soporta ES, EU, EN, FR y CA. Configúralo en Ajustes.

P: ¿Cómo genero datos de prueba?
R: Activa el addon Demo Orchestrator y úsalo desde Flavor → Demo.

P: ¿Qué son los perfiles de aplicación?
R: Configuraciones predefinidas de módulos según el tipo de organización.

P: ¿Cómo exporto los datos de miembros?
R: Desde el módulo Socios, usa el botón Exportar en la lista.

P: ¿El plugin es compatible con WooCommerce?
R: Sí, hay integración opcional para pagos y productos.";
    }

    /**
     * Obtiene el estado de configuración de módulos para el asistente IA
     *
     * @return string
     */
    private function get_setup_status_context() {
        if (!class_exists('Flavor_Module_Setup_Assistant')) {
            return '';
        }

        $assistant = Flavor_Module_Setup_Assistant::get_instance();
        return $assistant->get_setup_context_for_ai();
    }

    /**
     * Invalida el cache de conocimiento
     */
    public function invalidate_cache() {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_flavor_admin_kb_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_flavor_admin_kb_%'");
    }
}
