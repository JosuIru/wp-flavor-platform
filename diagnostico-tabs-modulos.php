<?php
/**
 * Diagnóstico de Tabs de Módulos
 *
 * Detecta problemas en la configuración de tabs:
 * - Shortcodes no registrados
 * - Templates inexistentes
 * - Dependencias de $this en templates
 * - Tablas de BD faltantes
 * - Módulos inactivos
 *
 * Ejecutar: /wp-content/plugins/flavor-chat-ia/diagnostico-tabs-modulos.php
 *
 * @package FlavorChatIA
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('Sin permisos');
}

// Inicializar resultados
$resultados = [
    'modulos_analizados' => 0,
    'problemas_criticos' => [],
    'problemas_menores' => [],
    'modulos_ok' => [],
    'detalle_modulos' => [],
];

// ============================================================
// 1. OBTENER CONFIGURACIÓN DE TABS DE DYNAMIC PAGES
// ============================================================

function obtener_tabs_config() {
    // Simular la configuración de tabs de Dynamic Pages
    // Esto debería coincidir con get_module_tabs() en class-dynamic-pages.php
    return [
        'grupos-consumo' => [
            'productos'   => ['label' => 'Catálogo', 'icon' => 'dashicons-products'],
            'pedidos'     => ['label' => 'Mis Pedidos', 'icon' => 'dashicons-cart'],
            'productores' => ['label' => 'Productores', 'icon' => 'dashicons-groups'],
            'ciclos'      => ['label' => 'Ciclos', 'icon' => 'dashicons-calendar-alt'],
        ],
        'eventos' => [
            'listado'       => ['label' => 'Todos', 'icon' => 'dashicons-list-view', 'content' => 'template:_archive.php'],
            'proximos'      => ['label' => 'Próximos', 'icon' => 'dashicons-calendar'],
            'inscripciones' => ['label' => 'Mis Inscripciones', 'icon' => 'dashicons-tickets-alt'],
            'calendario'    => ['label' => 'Calendario', 'icon' => 'dashicons-calendar-alt'],
            'mapa'          => ['label' => 'Mapa', 'icon' => 'dashicons-location'],
        ],
        'reservas' => [
            'recursos'     => ['label' => 'Recursos', 'icon' => 'dashicons-admin-home'],
            'mis-reservas' => ['label' => 'Mis Reservas', 'icon' => 'dashicons-calendar-alt'],
            'calendario'   => ['label' => 'Calendario', 'icon' => 'dashicons-calendar'],
        ],
        'marketplace' => [
            'listado'      => ['label' => 'Anuncios', 'icon' => 'dashicons-megaphone', 'content' => 'template:_archive.php'],
            'mis-anuncios' => ['label' => 'Mis Anuncios', 'icon' => 'dashicons-welcome-write-blog'],
            'categorias'   => ['label' => 'Categorías', 'icon' => 'dashicons-category'],
        ],
        'incidencias' => [
            'listado'      => ['label' => 'Todas', 'icon' => 'dashicons-list-view', 'content' => 'template:_archive.php'],
            'mis-reportes' => ['label' => 'Mis Reportes', 'icon' => 'dashicons-flag'],
            'mapa'         => ['label' => 'Mapa', 'icon' => 'dashicons-location-alt'],
        ],
        'banco-tiempo' => [
            'servicios'    => ['label' => 'Servicios', 'icon' => 'dashicons-admin-users', 'content' => 'template:_archive.php'],
            'mi-saldo'     => ['label' => 'Mi Saldo', 'icon' => 'dashicons-clock'],
            'intercambios' => ['label' => 'Intercambios', 'icon' => 'dashicons-randomize'],
            'ranking'      => ['label' => 'Ranking', 'icon' => 'dashicons-awards'],
        ],
        'presupuestos-participativos' => [
            'proyectos'      => ['label' => 'Proyectos', 'icon' => 'dashicons-portfolio', 'content' => '[presupuestos_listado]'],
            'votaciones'     => ['label' => 'Votaciones', 'icon' => 'dashicons-thumbs-up', 'content' => '[presupuestos_votar]'],
            'fases'          => ['label' => 'Fases', 'icon' => 'dashicons-chart-line', 'content' => '[presupuesto_estado_actual]'],
            'mis-propuestas' => ['label' => 'Mis Propuestas', 'icon' => 'dashicons-edit', 'content' => '[presupuestos_mi_proyecto]'],
            'resultados'     => ['label' => 'Resultados', 'icon' => 'dashicons-chart-bar', 'content' => '[presupuestos_resultados]'],
        ],
        'participacion' => [
            'propuestas' => ['label' => 'Propuestas', 'icon' => 'dashicons-lightbulb'],
            'votaciones' => ['label' => 'Votaciones', 'icon' => 'dashicons-thumbs-up'],
            'resultados' => ['label' => 'Resultados', 'icon' => 'dashicons-chart-bar'],
        ],
        'cursos' => [
            'catalogo'   => ['label' => 'Catálogo', 'icon' => 'dashicons-welcome-learn-more'],
            'mis-cursos' => ['label' => 'Mis Cursos', 'icon' => 'dashicons-awards'],
            'calendario' => ['label' => 'Calendario', 'icon' => 'dashicons-calendar'],
        ],
        'talleres' => [
            'proximos'      => ['label' => 'Próximos', 'icon' => 'dashicons-calendar'],
            'inscripciones' => ['label' => 'Inscripciones', 'icon' => 'dashicons-tickets-alt'],
            'calendario'    => ['label' => 'Calendario', 'icon' => 'dashicons-calendar-alt'],
        ],
        'biblioteca' => [
            'catalogo'      => ['label' => 'Catálogo', 'icon' => 'dashicons-book-alt'],
            'mis-prestamos' => ['label' => 'Mis Préstamos', 'icon' => 'dashicons-book'],
            'novedades'     => ['label' => 'Novedades', 'icon' => 'dashicons-star-filled'],
        ],
        'comunidades' => [
            'directorio' => ['label' => 'Directorio', 'icon' => 'dashicons-networking'],
            'mapa'       => ['label' => 'Mapa', 'icon' => 'dashicons-location'],
            'tablon'     => ['label' => 'Tablón', 'icon' => 'dashicons-megaphone'],
        ],
        'colectivos' => [
            'listado'        => ['label' => 'Colectivos', 'icon' => 'dashicons-groups'],
            'mis-colectivos' => ['label' => 'Mis Colectivos', 'icon' => 'dashicons-admin-users'],
        ],
        'socios' => [
            'mi-membresia' => ['label' => 'Mi Membresía', 'icon' => 'dashicons-id-alt'],
            'directorio'   => ['label' => 'Directorio', 'icon' => 'dashicons-groups'],
        ],
        'huertos-urbanos' => [
            'listado'    => ['label' => 'Huertos', 'icon' => 'dashicons-admin-site-alt3'],
            'mi-parcela' => ['label' => 'Mi Parcela', 'icon' => 'dashicons-admin-home'],
            'mapa'       => ['label' => 'Mapa', 'icon' => 'dashicons-location'],
            'calendario' => ['label' => 'Calendario', 'icon' => 'dashicons-calendar'],
        ],
        'espacios-comunes' => [
            'espacios'     => ['label' => 'Espacios', 'icon' => 'dashicons-admin-home'],
            'mis-reservas' => ['label' => 'Mis Reservas', 'icon' => 'dashicons-calendar-alt'],
            'calendario'   => ['label' => 'Calendario', 'icon' => 'dashicons-calendar'],
        ],
        'reciclaje' => [
            'puntos-cercanos' => ['label' => 'Puntos', 'icon' => 'dashicons-location'],
            'mis-puntos'      => ['label' => 'Mi Impacto', 'icon' => 'dashicons-chart-bar'],
            'guia'            => ['label' => 'Guía', 'icon' => 'dashicons-info'],
        ],
        'compostaje' => [
            'mapa'             => ['label' => 'Composteras', 'icon' => 'dashicons-location-alt'],
            'mis-aportaciones' => ['label' => 'Mis Aportaciones', 'icon' => 'dashicons-admin-site-alt3'],
            'estadisticas'     => ['label' => 'Estadísticas', 'icon' => 'dashicons-chart-area'],
        ],
        'ayuda-vecinal' => [
            'solicitudes' => ['label' => 'Solicitudes', 'icon' => 'dashicons-heart'],
            'ofrecer'     => ['label' => 'Ofrecer Ayuda', 'icon' => 'dashicons-plus-alt'],
            'mapa'        => ['label' => 'Mapa', 'icon' => 'dashicons-location'],
        ],
        'tramites' => [
            'catalogo'        => ['label' => 'Catálogo', 'icon' => 'dashicons-media-document'],
            'mis-expedientes' => ['label' => 'Mis Expedientes', 'icon' => 'dashicons-portfolio'],
        ],
        'transparencia' => [
            'portal'      => ['label' => 'Portal', 'icon' => 'dashicons-visibility'],
            'presupuesto' => ['label' => 'Presupuesto', 'icon' => 'dashicons-chart-pie'],
            'actas'       => ['label' => 'Actas', 'icon' => 'dashicons-media-document'],
        ],
        'avisos-municipales' => [
            'activos'   => ['label' => 'Activos', 'icon' => 'dashicons-megaphone'],
            'urgentes'  => ['label' => 'Urgentes', 'icon' => 'dashicons-warning'],
            'historial' => ['label' => 'Historial', 'icon' => 'dashicons-backup'],
        ],
        'carpooling' => [
            'buscar'     => ['label' => 'Buscar Viaje', 'icon' => 'dashicons-search'],
            'mis-viajes' => ['label' => 'Mis Viajes', 'icon' => 'dashicons-car'],
            'ofrecer'    => ['label' => 'Ofrecer', 'icon' => 'dashicons-plus-alt'],
        ],
        'parkings' => [
            'disponibilidad' => ['label' => 'Disponibilidad', 'icon' => 'dashicons-visibility'],
            'mis-reservas'   => ['label' => 'Mis Reservas', 'icon' => 'dashicons-calendar-alt'],
            'mapa'           => ['label' => 'Mapa', 'icon' => 'dashicons-location'],
        ],
        'bicicletas-compartidas' => [
            'disponibles'   => ['label' => 'Disponibles', 'icon' => 'dashicons-admin-site'],
            'mis-prestamos' => ['label' => 'Mis Préstamos', 'icon' => 'dashicons-dashboard'],
            'mapa'          => ['label' => 'Mapa', 'icon' => 'dashicons-location'],
        ],
        'foros' => [
            'listado'    => ['label' => 'Discusiones', 'icon' => 'dashicons-format-chat'],
            'categorias' => ['label' => 'Categorías', 'icon' => 'dashicons-category'],
            'actividad'  => ['label' => 'Actividad', 'icon' => 'dashicons-bell'],
        ],
        'multimedia' => [
            'galeria'    => ['label' => 'Galería', 'icon' => 'dashicons-format-gallery'],
            'mi-galeria' => ['label' => 'Mi Galería', 'icon' => 'dashicons-images-alt2'],
            'albumes'    => ['label' => 'Álbumes', 'icon' => 'dashicons-portfolio'],
        ],
        'podcast' => [
            'episodios' => ['label' => 'Episodios', 'icon' => 'dashicons-microphone'],
            'series'    => ['label' => 'Series', 'icon' => 'dashicons-playlist-audio'],
        ],
        'radio' => [
            'en-directo'   => ['label' => 'En Directo', 'icon' => 'dashicons-controls-volumeon'],
            'programacion' => ['label' => 'Programación', 'icon' => 'dashicons-calendar-alt'],
            'podcasts'     => ['label' => 'Podcasts', 'icon' => 'dashicons-microphone'],
        ],
        // === MÓDULOS ADICIONALES ===
        'advertising' => [
            'campanas'     => ['label' => 'Campañas', 'icon' => 'dashicons-megaphone'],
            'espacios'     => ['label' => 'Espacios', 'icon' => 'dashicons-admin-page'],
            'estadisticas' => ['label' => 'Estadísticas', 'icon' => 'dashicons-chart-bar'],
        ],
        'bares' => [
            'listado'  => ['label' => 'Bares', 'icon' => 'dashicons-store'],
            'mapa'     => ['label' => 'Mapa', 'icon' => 'dashicons-location'],
            'eventos'  => ['label' => 'Eventos', 'icon' => 'dashicons-calendar'],
        ],
        'biodiversidad-local' => [
            'especies'    => ['label' => 'Especies', 'icon' => 'dashicons-palmtree'],
            'avistamientos' => ['label' => 'Avistamientos', 'icon' => 'dashicons-visibility'],
            'mapa'        => ['label' => 'Mapa', 'icon' => 'dashicons-location-alt'],
        ],
        'campanias' => [
            'activas'  => ['label' => 'Activas', 'icon' => 'dashicons-megaphone'],
            'archivadas' => ['label' => 'Archivadas', 'icon' => 'dashicons-archive'],
            'mis-campanias' => ['label' => 'Mis Campañas', 'icon' => 'dashicons-admin-users'],
        ],
        'chat-estados' => [
            'estados'  => ['label' => 'Estados', 'icon' => 'dashicons-format-status'],
            'historial' => ['label' => 'Historial', 'icon' => 'dashicons-backup'],
        ],
        'chat-grupos' => [
            'grupos'     => ['label' => 'Grupos', 'icon' => 'dashicons-groups'],
            'mis-grupos' => ['label' => 'Mis Grupos', 'icon' => 'dashicons-admin-users'],
            'crear'      => ['label' => 'Crear', 'icon' => 'dashicons-plus-alt'],
        ],
        'chat-interno' => [
            'conversaciones' => ['label' => 'Conversaciones', 'icon' => 'dashicons-format-chat'],
            'contactos'      => ['label' => 'Contactos', 'icon' => 'dashicons-admin-users'],
        ],
        'circulos-cuidados' => [
            'circulos'    => ['label' => 'Círculos', 'icon' => 'dashicons-heart'],
            'mis-circulos' => ['label' => 'Mis Círculos', 'icon' => 'dashicons-admin-users'],
            'solicitudes' => ['label' => 'Solicitudes', 'icon' => 'dashicons-plus-alt'],
        ],
        'clientes' => [
            'listado'     => ['label' => 'Clientes', 'icon' => 'dashicons-businessman'],
            'mis-clientes' => ['label' => 'Mis Clientes', 'icon' => 'dashicons-admin-users'],
            'importar'    => ['label' => 'Importar', 'icon' => 'dashicons-upload'],
        ],
        'dex-solana' => [
            'trading'  => ['label' => 'Trading', 'icon' => 'dashicons-chart-line'],
            'cartera'  => ['label' => 'Cartera', 'icon' => 'dashicons-portfolio'],
            'historial' => ['label' => 'Historial', 'icon' => 'dashicons-backup'],
        ],
        'documentacion-legal' => [
            'documentos'  => ['label' => 'Documentos', 'icon' => 'dashicons-media-document'],
            'categorias'  => ['label' => 'Categorías', 'icon' => 'dashicons-category'],
            'mis-descargas' => ['label' => 'Mis Descargas', 'icon' => 'dashicons-download'],
        ],
        'economia-don' => [
            'ofertas'   => ['label' => 'Ofertas', 'icon' => 'dashicons-heart'],
            'necesidades' => ['label' => 'Necesidades', 'icon' => 'dashicons-admin-users'],
            'mis-intercambios' => ['label' => 'Mis Intercambios', 'icon' => 'dashicons-randomize'],
        ],
        'economia-suficiencia' => [
            'recursos'    => ['label' => 'Recursos', 'icon' => 'dashicons-admin-site-alt3'],
            'calculadora' => ['label' => 'Calculadora', 'icon' => 'dashicons-calculator'],
            'guias'       => ['label' => 'Guías', 'icon' => 'dashicons-book'],
        ],
        'email-marketing' => [
            'campanas'    => ['label' => 'Campañas', 'icon' => 'dashicons-email'],
            'listas'      => ['label' => 'Listas', 'icon' => 'dashicons-groups'],
            'estadisticas' => ['label' => 'Estadísticas', 'icon' => 'dashicons-chart-bar'],
        ],
        'empresarial' => [
            'directorio' => ['label' => 'Directorio', 'icon' => 'dashicons-building'],
            'mi-empresa' => ['label' => 'Mi Empresa', 'icon' => 'dashicons-admin-home'],
            'servicios'  => ['label' => 'Servicios', 'icon' => 'dashicons-admin-tools'],
        ],
        'encuestas' => [
            'activas'     => ['label' => 'Activas', 'icon' => 'dashicons-forms'],
            'mis-votos'   => ['label' => 'Mis Votos', 'icon' => 'dashicons-thumbs-up'],
            'resultados'  => ['label' => 'Resultados', 'icon' => 'dashicons-chart-pie'],
        ],
        'facturas' => [
            'listado'     => ['label' => 'Facturas', 'icon' => 'dashicons-media-document'],
            'crear'       => ['label' => 'Crear', 'icon' => 'dashicons-plus-alt'],
            'clientes'    => ['label' => 'Clientes', 'icon' => 'dashicons-businessman'],
        ],
        'fichaje-empleados' => [
            'fichar'      => ['label' => 'Fichar', 'icon' => 'dashicons-clock'],
            'mi-historial' => ['label' => 'Mi Historial', 'icon' => 'dashicons-backup'],
            'calendario'  => ['label' => 'Calendario', 'icon' => 'dashicons-calendar'],
        ],
        'huella-ecologica' => [
            'calculadora' => ['label' => 'Calculadora', 'icon' => 'dashicons-calculator'],
            'mi-huella'   => ['label' => 'Mi Huella', 'icon' => 'dashicons-admin-site-alt3'],
            'consejos'    => ['label' => 'Consejos', 'icon' => 'dashicons-lightbulb'],
        ],
        'justicia-restaurativa' => [
            'procesos'    => ['label' => 'Procesos', 'icon' => 'dashicons-admin-generic'],
            'mediadores'  => ['label' => 'Mediadores', 'icon' => 'dashicons-groups'],
            'recursos'    => ['label' => 'Recursos', 'icon' => 'dashicons-book'],
        ],
        'mapa-actores' => [
            'mapa'        => ['label' => 'Mapa', 'icon' => 'dashicons-location-alt'],
            'actores'     => ['label' => 'Actores', 'icon' => 'dashicons-groups'],
            'categorias'  => ['label' => 'Categorías', 'icon' => 'dashicons-category'],
        ],
        'recetas' => [
            'listado'     => ['label' => 'Recetas', 'icon' => 'dashicons-carrot'],
            'categorias'  => ['label' => 'Categorías', 'icon' => 'dashicons-category'],
            'mis-recetas' => ['label' => 'Mis Recetas', 'icon' => 'dashicons-welcome-write-blog'],
        ],
        'red-social' => [
            'feed'        => ['label' => 'Feed', 'icon' => 'dashicons-rss'],
            'amigos'      => ['label' => 'Amigos', 'icon' => 'dashicons-groups'],
            'mi-perfil'   => ['label' => 'Mi Perfil', 'icon' => 'dashicons-admin-users'],
        ],
        'saberes-ancestrales' => [
            'saberes'     => ['label' => 'Saberes', 'icon' => 'dashicons-book-alt'],
            'tradiciones' => ['label' => 'Tradiciones', 'icon' => 'dashicons-welcome-learn-more'],
            'aportar'     => ['label' => 'Aportar', 'icon' => 'dashicons-plus-alt'],
        ],
        'seguimiento-denuncias' => [
            'mis-denuncias' => ['label' => 'Mis Denuncias', 'icon' => 'dashicons-flag'],
            'nueva'       => ['label' => 'Nueva', 'icon' => 'dashicons-plus-alt'],
            'seguimiento' => ['label' => 'Seguimiento', 'icon' => 'dashicons-visibility'],
        ],
        'sello-conciencia' => [
            'certificados' => ['label' => 'Certificados', 'icon' => 'dashicons-awards'],
            'criterios'   => ['label' => 'Criterios', 'icon' => 'dashicons-list-view'],
            'solicitar'   => ['label' => 'Solicitar', 'icon' => 'dashicons-plus-alt'],
        ],
        'themacle' => [
            'temas'       => ['label' => 'Temas', 'icon' => 'dashicons-admin-customizer'],
            'componentes' => ['label' => 'Componentes', 'icon' => 'dashicons-layout'],
        ],
        'trabajo-digno' => [
            'ofertas'     => ['label' => 'Ofertas', 'icon' => 'dashicons-clipboard'],
            'empresas'    => ['label' => 'Empresas', 'icon' => 'dashicons-building'],
            'mi-cv'       => ['label' => 'Mi CV', 'icon' => 'dashicons-id-alt'],
        ],
        'trading-ia' => [
            'senales'     => ['label' => 'Señales', 'icon' => 'dashicons-chart-line'],
            'estrategias' => ['label' => 'Estrategias', 'icon' => 'dashicons-admin-generic'],
            'historial'   => ['label' => 'Historial', 'icon' => 'dashicons-backup'],
        ],
        'woocommerce' => [
            'productos'   => ['label' => 'Productos', 'icon' => 'dashicons-products'],
            'pedidos'     => ['label' => 'Pedidos', 'icon' => 'dashicons-cart'],
            'mi-cuenta'   => ['label' => 'Mi Cuenta', 'icon' => 'dashicons-admin-users'],
        ],
    ];
}

// ============================================================
// 2. FUNCIONES DE VERIFICACIÓN
// ============================================================

/**
 * Verificar si un shortcode existe
 */
function verificar_shortcode($shortcode_tag) {
    global $shortcode_tags;
    // Extraer el nombre del shortcode (sin corchetes ni atributos)
    if (preg_match('/\[([a-z0-9_-]+)/i', $shortcode_tag, $matches)) {
        $tag = $matches[1];
        return isset($shortcode_tags[$tag]);
    }
    return false;
}

/**
 * Verificar si un template existe
 */
function verificar_template($module_slug, $template_name) {
    $paths = [
        FLAVOR_CHAT_IA_PATH . "templates/frontend/{$module_slug}/tabs/{$template_name}",
        FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/views/tabs/{$template_name}",
        FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/frontend/tabs/{$template_name}",
        FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/views/{$template_name}",
        FLAVOR_CHAT_IA_PATH . "templates/frontend/{$module_slug}/{$template_name}",
        FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/templates/{$template_name}",
        FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/frontend/{$template_name}",
        FLAVOR_CHAT_IA_PATH . "templates/frontend/{$template_name}",
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return ['existe' => true, 'path' => $path];
        }
    }

    return ['existe' => false, 'paths_buscados' => $paths];
}

/**
 * Verificar dependencias de $this en un archivo
 */
function verificar_dependencias_this($filepath) {
    if (!file_exists($filepath)) {
        return ['error' => 'Archivo no existe'];
    }

    $contenido = file_get_contents($filepath);
    $problemas = [];

    // Buscar usos de $this->
    if (preg_match_all('/\$this->([a-zA-Z_]+)/', $contenido, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $index => $match) {
            $linea = substr_count(substr($contenido, 0, $match[1]), "\n") + 1;
            $problemas[] = [
                'linea' => $linea,
                'codigo' => $match[0],
                'propiedad' => $matches[1][$index][0],
            ];
        }
    }

    return $problemas;
}

/**
 * Obtener tablas requeridas por módulo
 *
 * NOTA: Los nombres de tablas deben coincidir con los definidos en
 * class-database-installer.php. El prefijo de WP se añade automáticamente.
 */
function obtener_tablas_modulo($module_slug) {
    $tablas_por_modulo = [
        'presupuestos-participativos' => ['flavor_presupuestos_propuestas'],
        'eventos' => ['flavor_eventos', 'flavor_eventos_inscripciones'],
        'banco-tiempo' => ['flavor_banco_tiempo_saldo'],
        'marketplace' => ['flavor_marketplace'],
        'incidencias' => ['flavor_incidencias'],
        'cursos' => ['flavor_cursos', 'flavor_cursos_inscripciones'],
        'talleres' => ['flavor_talleres'],
        'reservas' => ['flavor_reservas'],
        'espacios-comunes' => ['flavor_espacios', 'flavor_reservas'],
        'huertos-urbanos' => ['flavor_huertos_parcelas', 'flavor_huertos_asignaciones'],
        'comunidades' => [], // Usa CPTs, no tablas custom
        'colectivos' => ['flavor_colectivos', 'flavor_colectivos_miembros'],
        'socios' => ['flavor_socios'],
        'participacion' => ['flavor_participacion_procesos'],
        'reciclaje' => ['flavor_reciclaje_puntos'],
        'ayuda-vecinal' => [], // Usa CPTs
        'tramites' => ['flavor_tramites'],
        'transparencia' => [], // Usa CPTs
        'avisos-municipales' => ['flavor_avisos'],
        'carpooling' => [], // Usa CPTs
        'parkings' => [], // Usa CPTs
        'bicicletas-compartidas' => [], // Usa CPTs
        'foros' => ['flavor_foros', 'flavor_foros_temas', 'flavor_foros_respuestas'],
        'multimedia' => [], // Usa media de WordPress
        'podcast' => [], // Usa CPTs
        'radio' => [], // Usa CPTs
        'grupos-consumo' => [], // Usa CPTs y tablas propias del módulo
        'biblioteca' => [], // Usa CPTs
        'compostaje' => [], // Usa CPTs
    ];

    return $tablas_por_modulo[$module_slug] ?? [];
}

/**
 * Verificar si una tabla existe
 */
function verificar_tabla($tabla) {
    global $wpdb;
    $tabla_completa = $wpdb->prefix . $tabla;
    $existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_completa}'");
    return $existe ? true : false;
}

/**
 * Obtener clase del módulo
 *
 * Los módulos usan el patrón: Flavor_Chat_[NombreModulo]_Module
 * Ejemplo: eventos → Flavor_Chat_Eventos_Module
 *
 * Esta función intenta cargar el archivo del módulo si la clase no existe,
 * ya que el diagnóstico puede ejecutarse antes de que se carguen los módulos.
 */
function obtener_clase_modulo($module_slug) {
    $slug_normalizado = str_replace('-', '_', $module_slug);

    // Casos especiales donde el nombre de clase no sigue el patrón estándar
    $casos_especiales = [
        'chat-estados' => 'Flavor_Chat_Estados_Module',
        'chat-grupos' => 'Flavor_Chat_Chat_Grupos_Module',
        'chat-interno' => 'Flavor_Chat_Chat_Interno_Module',
    ];

    if (isset($casos_especiales[$module_slug])) {
        $clase_base = $casos_especiales[$module_slug];
    } else {
        // El prefijo correcto es Flavor_Chat_, no solo Flavor_
        $clase_base = 'Flavor_Chat_' . implode('_', array_map('ucfirst', explode('_', $slug_normalizado))) . '_Module';
    }

    // Si la clase ya existe, devolverla
    if (class_exists($clase_base)) {
        return $clase_base;
    }

    // Intentar cargar el archivo del módulo
    $module_file = FLAVOR_CHAT_IA_PATH . 'includes/modules/' . $module_slug . '/class-' . $module_slug . '-module.php';

    if (file_exists($module_file)) {
        require_once $module_file;

        if (class_exists($clase_base)) {
            return $clase_base;
        }
    }

    return null;
}

/**
 * Verificar si el módulo está activo
 *
 * Los módulos se activan mediante configuración en settings.
 * Si la clase existe, el módulo está disponible para usar.
 */
function verificar_modulo_activo($module_slug) {
    $clase = obtener_clase_modulo($module_slug);

    if (!$clase) {
        return ['activo' => false, 'razon' => 'Clase no existe'];
    }

    // La clase existe, verificar si está en la lista de módulos activos
    $settings = get_option('flavor_chat_ia_settings', []);
    $modulos_activos = isset($settings['active_modules']) ? $settings['active_modules'] : [];

    // Normalizar el slug para comparación
    $slug_normalizado = str_replace('-', '_', $module_slug);

    // Verificar si está explícitamente activo en configuración
    if (in_array($slug_normalizado, $modulos_activos) || in_array($module_slug, $modulos_activos)) {
        return ['activo' => true, 'razon' => 'OK'];
    }

    // Si no hay configuración de módulos, asumir que todos están activos por defecto
    if (empty($modulos_activos)) {
        return ['activo' => true, 'razon' => 'Activo por defecto (sin restricciones)'];
    }

    return ['activo' => false, 'razon' => 'No está en lista de módulos activos'];
}

/**
 * Obtener shortcodes registrados por un módulo
 */
function obtener_shortcodes_modulo($module_slug) {
    global $shortcode_tags;

    $prefijos = [
        str_replace('-', '_', $module_slug) . '_',
        'flavor_' . str_replace('-', '_', $module_slug) . '_',
        str_replace('-', '', $module_slug) . '_',
    ];

    $shortcodes_encontrados = [];

    foreach ($shortcode_tags as $tag => $callback) {
        foreach ($prefijos as $prefijo) {
            if (strpos($tag, $prefijo) === 0 || $tag === str_replace('-', '_', $module_slug)) {
                $shortcodes_encontrados[] = $tag;
                break;
            }
        }
    }

    // Casos especiales
    $casos_especiales = [
        'presupuestos-participativos' => ['presupuestos_listado', 'presupuestos_votar', 'presupuestos_resultados', 'presupuestos_proponer', 'presupuestos_mi_proyecto', 'presupuesto_estado_actual', 'presupuestos_seguimiento'],
        'banco-tiempo' => ['banco_tiempo_servicios', 'banco_tiempo_mi_saldo', 'banco_tiempo_intercambios'],
        'grupos-consumo' => ['gc_catalogo', 'gc_mis_pedidos', 'gc_productores', 'gc_ciclos'],
    ];

    if (isset($casos_especiales[$module_slug])) {
        foreach ($casos_especiales[$module_slug] as $shortcode) {
            if (isset($shortcode_tags[$shortcode]) && !in_array($shortcode, $shortcodes_encontrados)) {
                $shortcodes_encontrados[] = $shortcode;
            }
        }
    }

    return $shortcodes_encontrados;
}

// ============================================================
// 3. EJECUTAR DIAGNÓSTICO
// ============================================================

$tabs_config = obtener_tabs_config();
$plugin_path = FLAVOR_CHAT_IA_PATH;

foreach ($tabs_config as $module_slug => $tabs) {
    $resultados['modulos_analizados']++;

    $detalle = [
        'modulo' => $module_slug,
        'tabs' => count($tabs),
        'problemas' => [],
        'advertencias' => [],
        'estado' => 'ok',
    ];

    // 1. Verificar si el módulo existe
    $clase_modulo = obtener_clase_modulo($module_slug);
    if (!$clase_modulo) {
        $detalle['problemas'][] = "Clase del módulo no encontrada";
        $detalle['estado'] = 'error';
    }

    // 2. Verificar si está activo
    $estado_activo = verificar_modulo_activo($module_slug);
    $detalle['activo'] = $estado_activo['activo'];
    if (!$estado_activo['activo']) {
        $detalle['problemas'][] = "Módulo inactivo: " . $estado_activo['razon'];
        $detalle['estado'] = 'error';
    }

    // 3. Verificar tablas de BD
    $tablas_requeridas = obtener_tablas_modulo($module_slug);
    $detalle['tablas'] = [];
    foreach ($tablas_requeridas as $tabla) {
        $existe = verificar_tabla($tabla);
        $detalle['tablas'][$tabla] = $existe;
        if (!$existe) {
            $detalle['problemas'][] = "Tabla faltante: {$tabla}";
            $detalle['estado'] = 'error';
        }
    }

    // 4. Verificar shortcodes del módulo
    $shortcodes_modulo = obtener_shortcodes_modulo($module_slug);
    $detalle['shortcodes_registrados'] = $shortcodes_modulo;

    // 5. Verificar cada tab
    $detalle['tabs_detalle'] = [];
    foreach ($tabs as $tab_id => $tab_info) {
        $tab_detalle = [
            'id' => $tab_id,
            'label' => $tab_info['label'],
            'estado' => 'ok',
            'problemas' => [],
        ];

        // Si tiene content definido
        if (isset($tab_info['content'])) {
            $content = $tab_info['content'];

            // Es un shortcode
            if (strpos($content, '[') === 0) {
                $shortcode_existe = verificar_shortcode($content);
                $tab_detalle['tipo'] = 'shortcode';
                $tab_detalle['shortcode'] = $content;

                if (!$shortcode_existe) {
                    $tab_detalle['problemas'][] = "Shortcode no registrado: {$content}";
                    $tab_detalle['estado'] = 'error';
                    $detalle['estado'] = 'warning';
                }
            }
            // Es un template
            elseif (strpos($content, 'template:') === 0) {
                $template_name = str_replace('template:', '', $content);
                $template_check = verificar_template($module_slug, $template_name);
                $tab_detalle['tipo'] = 'template';
                $tab_detalle['template'] = $template_name;

                if (!$template_check['existe']) {
                    $tab_detalle['problemas'][] = "Template no encontrado: {$template_name}";
                    $tab_detalle['estado'] = 'error';
                    $detalle['estado'] = 'warning';
                } else {
                    // Verificar dependencias de $this
                    $deps_this = verificar_dependencias_this($template_check['path']);
                    if (!empty($deps_this) && !isset($deps_this['error'])) {
                        $tab_detalle['advertencias'][] = "Template usa \$this-> en " . count($deps_this) . " lugares";
                        foreach ($deps_this as $dep) {
                            $tab_detalle['dependencias_this'][] = "Línea {$dep['linea']}: {$dep['codigo']}";
                        }
                    }
                }
            }
        } else {
            // Sin content definido - usa sistema legacy
            $tab_detalle['tipo'] = 'legacy';
        }

        $detalle['tabs_detalle'][$tab_id] = $tab_detalle;
    }

    // Clasificar resultado
    if ($detalle['estado'] === 'error') {
        $resultados['problemas_criticos'][] = $module_slug;
    } elseif ($detalle['estado'] === 'warning') {
        $resultados['problemas_menores'][] = $module_slug;
    } else {
        $resultados['modulos_ok'][] = $module_slug;
    }

    $resultados['detalle_modulos'][$module_slug] = $detalle;
}

// ============================================================
// 4. ESCANEAR TODOS LOS TEMPLATES EN BUSCA DE $this
// ============================================================

$templates_con_this = [];
$views_path = $plugin_path . 'includes/modules/';

if (is_dir($views_path)) {
    $modules_dirs = scandir($views_path);
    foreach ($modules_dirs as $module_dir) {
        if ($module_dir === '.' || $module_dir === '..') continue;

        $views_dir = $views_path . $module_dir . '/views/';
        if (is_dir($views_dir)) {
            $view_files = glob($views_dir . '*.php');
            foreach ($view_files as $view_file) {
                $deps = verificar_dependencias_this($view_file);
                if (!empty($deps) && !isset($deps['error'])) {
                    $templates_con_this[] = [
                        'modulo' => $module_dir,
                        'archivo' => basename($view_file),
                        'path' => $view_file,
                        'dependencias' => $deps,
                    ];
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Tabs - Módulos Flavor</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 1400px;
            margin: 0 auto;
            line-height: 1.5;
        }
        h1 { color: #1e3a5f; margin-bottom: 10px; }
        h2 { color: #374151; margin-top: 30px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        h3 { color: #4b5563; margin-top: 20px; }

        .resumen {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat .valor { font-size: 42px; font-weight: bold; }
        .stat.ok .valor { color: #22c55e; }
        .stat.warning .valor { color: #f59e0b; }
        .stat.error .valor { color: #ef4444; }
        .stat .label { color: #6b7280; margin-top: 5px; }

        .modulo-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .modulo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .modulo-nombre {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge.ok { background: #dcfce7; color: #166534; }
        .badge.warning { background: #fef3c7; color: #92400e; }
        .badge.error { background: #fee2e2; color: #991b1b; }

        .tabs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
        }
        .tab-item {
            padding: 12px;
            border-radius: 8px;
            background: #f9fafb;
            border-left: 3px solid #e5e7eb;
        }
        .tab-item.ok { border-left-color: #22c55e; }
        .tab-item.warning { border-left-color: #f59e0b; }
        .tab-item.error { border-left-color: #ef4444; }

        .tab-nombre { font-weight: 500; }
        .tab-tipo { font-size: 12px; color: #6b7280; }
        .tab-problema { font-size: 12px; color: #ef4444; margin-top: 5px; }

        .problema-lista {
            background: #fef2f2;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .problema-lista h4 { color: #991b1b; margin: 0 0 10px 0; font-size: 14px; }
        .problema-lista ul { margin: 0; padding-left: 20px; }
        .problema-lista li { color: #7f1d1d; font-size: 13px; margin-bottom: 5px; }

        .shortcodes-lista {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }
        .shortcode-chip {
            background: #e0e7ff;
            color: #3730a3;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-family: monospace;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th { background: #f9fafb; font-weight: 600; }

        .this-warning {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .this-warning h4 { color: #92400e; margin: 0 0 10px 0; }

        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }

        .expandible { cursor: pointer; }
        .expandible:hover { background: #f3f4f6; }
        .detalles { display: none; padding: 15px; background: #f9fafb; }
        .detalles.visible { display: block; }

        .filtros {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filtro-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            background: #e5e7eb;
            color: #374151;
        }
        .filtro-btn.active { background: #3b82f6; color: white; }
        .filtro-btn:hover { opacity: 0.9; }
    </style>
</head>
<body>

<h1>🔍 Diagnóstico de Tabs - Módulos Flavor</h1>
<p>Análisis de configuración de tabs, shortcodes y templates</p>

<div class="resumen">
    <div class="stat ok">
        <div class="valor"><?= count($resultados['modulos_ok']) ?></div>
        <div class="label">Módulos OK</div>
    </div>
    <div class="stat warning">
        <div class="valor"><?= count($resultados['problemas_menores']) ?></div>
        <div class="label">Con advertencias</div>
    </div>
    <div class="stat error">
        <div class="valor"><?= count($resultados['problemas_criticos']) ?></div>
        <div class="label">Con errores</div>
    </div>
    <div class="stat">
        <div class="valor"><?= $resultados['modulos_analizados'] ?></div>
        <div class="label">Total analizados</div>
    </div>
</div>

<div class="filtros">
    <button class="filtro-btn active" onclick="filtrarModulos('todos')">Todos</button>
    <button class="filtro-btn" onclick="filtrarModulos('error')">Solo errores</button>
    <button class="filtro-btn" onclick="filtrarModulos('warning')">Con advertencias</button>
    <button class="filtro-btn" onclick="filtrarModulos('ok')">Sin problemas</button>
</div>

<h2>📋 Detalle por Módulo</h2>

<?php foreach ($resultados['detalle_modulos'] as $module_slug => $detalle): ?>
<div class="modulo-card" data-estado="<?= $detalle['estado'] ?>">
    <div class="modulo-header">
        <span class="modulo-nombre"><?= esc_html($module_slug) ?></span>
        <span class="badge <?= $detalle['estado'] ?>">
            <?php
            if ($detalle['estado'] === 'ok') echo '✓ OK';
            elseif ($detalle['estado'] === 'warning') echo '⚠ Advertencias';
            else echo '✗ Errores';
            ?>
        </span>
    </div>

    <div style="display: flex; gap: 20px; font-size: 14px; color: #6b7280; margin-bottom: 15px;">
        <span>📑 <?= $detalle['tabs'] ?> tabs</span>
        <span><?= $detalle['activo'] ? '✅ Activo' : '❌ Inactivo' ?></span>
        <span>🔌 <?= count($detalle['shortcodes_registrados']) ?> shortcodes</span>
    </div>

    <?php if (!empty($detalle['shortcodes_registrados'])): ?>
    <div class="shortcodes-lista">
        <?php foreach ($detalle['shortcodes_registrados'] as $sc): ?>
            <span class="shortcode-chip">[<?= esc_html($sc) ?>]</span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($detalle['problemas'])): ?>
    <div class="problema-lista">
        <h4>❌ Problemas detectados:</h4>
        <ul>
            <?php foreach ($detalle['problemas'] as $problema): ?>
                <li><?= esc_html($problema) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <h3 style="margin-top: 20px; font-size: 14px;">Tabs configurados:</h3>
    <div class="tabs-grid">
        <?php foreach ($detalle['tabs_detalle'] as $tab_id => $tab): ?>
        <div class="tab-item <?= $tab['estado'] ?>">
            <div class="tab-nombre"><?= esc_html($tab['label']) ?></div>
            <div class="tab-tipo">
                <?php if ($tab['tipo'] === 'shortcode'): ?>
                    📌 <?= esc_html($tab['shortcode']) ?>
                <?php elseif ($tab['tipo'] === 'template'): ?>
                    📄 <?= esc_html($tab['template']) ?>
                <?php else: ?>
                    🔧 Sistema legacy
                <?php endif; ?>
            </div>
            <?php if (!empty($tab['problemas'])): ?>
                <?php foreach ($tab['problemas'] as $prob): ?>
                    <div class="tab-problema">⚠ <?= esc_html($prob) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($tab['dependencias_this'])): ?>
                <div class="tab-problema">⚠ Usa $this-> (<?= count($tab['dependencias_this']) ?> usos)</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php if (!empty($templates_con_this)): ?>
<h2>⚠️ Templates con dependencias de $this</h2>
<p>Estos templates usan <code>$this-></code> lo cual puede fallar cuando se cargan fuera del contexto del módulo:</p>

<table>
    <tr>
        <th>Módulo</th>
        <th>Archivo</th>
        <th>Líneas afectadas</th>
        <th>Código</th>
    </tr>
    <?php foreach ($templates_con_this as $template): ?>
    <tr>
        <td><strong><?= esc_html($template['modulo']) ?></strong></td>
        <td><code><?= esc_html($template['archivo']) ?></code></td>
        <td>
            <?php
            $lineas = array_column($template['dependencias'], 'linea');
            echo implode(', ', $lineas);
            ?>
        </td>
        <td>
            <?php foreach ($template['dependencias'] as $dep): ?>
                <code><?= esc_html($dep['codigo']) ?></code><br>
            <?php endforeach; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<div class="this-warning">
    <h4>📝 Cómo corregir:</h4>
    <p>Reemplazar <code>$this->settings['propiedad']</code> por una variable que se pase desde el shortcode:</p>
    <pre style="background: #1f2937; color: #e5e7eb; padding: 15px; border-radius: 8px; overflow-x: auto;">
// En el shortcode (class-*-module.php):
$mi_variable = $this->settings['propiedad'] ?? 'valor_default';

ob_start();
include dirname(__FILE__) . '/views/mi-template.php';
return ob_get_clean();

// En el template (views/mi-template.php):
// Usar $mi_variable en lugar de $this->settings['propiedad']
$valor = $mi_variable ?? 'valor_default';
    </pre>
</div>
<?php endif; ?>

<h2>📊 Resumen de Shortcodes Globales</h2>
<?php
// Contar todos los shortcodes
global $shortcode_tags;
$total_shortcodes = count($shortcode_tags);
$flavor_shortcodes = array_filter(array_keys($shortcode_tags), function($tag) {
    return strpos($tag, 'flavor') !== false ||
           strpos($tag, 'presupuestos') !== false ||
           strpos($tag, 'banco_tiempo') !== false ||
           strpos($tag, 'gc_') !== false ||
           strpos($tag, 'incidencias') !== false ||
           strpos($tag, 'eventos') !== false ||
           strpos($tag, 'marketplace') !== false;
});
?>
<p>Total shortcodes registrados en WordPress: <strong><?= $total_shortcodes ?></strong></p>
<p>Shortcodes de Flavor detectados: <strong><?= count($flavor_shortcodes) ?></strong></p>

<div class="shortcodes-lista" style="margin-top: 15px;">
    <?php foreach ($flavor_shortcodes as $sc): ?>
        <span class="shortcode-chip">[<?= esc_html($sc) ?>]</span>
    <?php endforeach; ?>
</div>

<script>
function filtrarModulos(estado) {
    document.querySelectorAll('.filtro-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    document.querySelectorAll('.modulo-card').forEach(card => {
        if (estado === 'todos') {
            card.style.display = 'block';
        } else {
            card.style.display = card.dataset.estado === estado ? 'block' : 'none';
        }
    });
}
</script>

<p style="margin-top: 40px; color: #6b7280; font-size: 13px;">
    Generado: <?= date('Y-m-d H:i:s') ?> |
    Plugin: Flavor Chat IA |
    <a href="<?= admin_url() ?>">Volver al admin</a>
</p>

</body>
</html>
<?php
