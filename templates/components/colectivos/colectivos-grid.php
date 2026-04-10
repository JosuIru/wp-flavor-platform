<?php
/**
 * Template: Grid de Colectivos
 *
 * @var string $titulo_seccion
 * @var int    $columnas
 * @var string $tipo_filtro
 * @var string $component_classes
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_de_seccion    = !empty($titulo_seccion) ? $titulo_seccion : __('Nuestros Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN);
$numero_columnas      = !empty($columnas) ? (int) $columnas : 3;
$filtro_tipo_activo   = !empty($tipo_filtro) ? $tipo_filtro : 'todos';
$clases_componente    = !empty($component_classes) ? $component_classes : '';

// Mapeo de columnas a clases de grid
$mapa_clases_columnas = [
    2 => 'md:grid-cols-2',
    3 => 'md:grid-cols-2 lg:grid-cols-3',
    4 => 'md:grid-cols-2 lg:grid-cols-4',
];
$clase_grid_columnas = $mapa_clases_columnas[$numero_columnas] ?? 'md:grid-cols-2 lg:grid-cols-3';

// Intentar cargar colectivos reales de la base de datos
$colectivos_para_mostrar = [];

global $wpdb;
$tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';

if (Flavor_Platform_Helpers::tabla_existe($tabla_colectivos)) {
    $condicion_tipo = '';
    $valores_preparacion = [];

    if ($filtro_tipo_activo !== 'todos') {
        $condicion_tipo = ' AND tipo = %s';
        $valores_preparacion[] = $filtro_tipo_activo;
    }

    $consulta_colectivos = "SELECT * FROM $tabla_colectivos WHERE estado = 'activo' $condicion_tipo ORDER BY miembros_count DESC LIMIT 12";

    if (!empty($valores_preparacion)) {
        $colectivos_para_mostrar = $wpdb->get_results($wpdb->prepare($consulta_colectivos, ...$valores_preparacion));
    } else {
        $colectivos_para_mostrar = $wpdb->get_results($consulta_colectivos);
    }
}

// Datos fallback si no hay colectivos reales
if (empty($colectivos_para_mostrar)) {
    $colectivos_para_mostrar = [
        (object) [
            'id'              => 1,
            'nombre'          => 'Asociación Vecinal La Esperanza',
            'descripcion'     => 'Asociación de vecinos comprometida con la mejora del barrio, organizando actividades culturales y reivindicando servicios públicos de calidad.',
            'tipo'            => 'asociacion',
            'sector'          => 'Vecinal',
            'miembros_count'  => 145,
            'proyectos_count' => 8,
            'imagen'          => '',
            'email_contacto'  => '',
            'estado'          => 'activo',
        ],
        (object) [
            'id'              => 2,
            'nombre'          => 'Cooperativa Energía Verde',
            'descripcion'     => 'Cooperativa de energía renovable que promueve el autoconsumo colectivo y la transición energética justa en nuestra comunidad.',
            'tipo'            => 'cooperativa',
            'sector'          => 'Energía',
            'miembros_count'  => 312,
            'proyectos_count' => 5,
            'imagen'          => '',
            'email_contacto'  => '',
            'estado'          => 'activo',
        ],
        (object) [
            'id'              => 3,
            'nombre'          => 'ONG Puentes Sin Fronteras',
            'descripcion'     => 'Organización dedicada a la integración de personas migrantes mediante programas de formación, acompañamiento y sensibilización social.',
            'tipo'            => 'ong',
            'sector'          => 'Social',
            'miembros_count'  => 89,
            'proyectos_count' => 12,
            'imagen'          => '',
            'email_contacto'  => '',
            'estado'          => 'activo',
        ],
        (object) [
            'id'              => 4,
            'nombre'          => 'Colectivo Huerta Comunitaria',
            'descripcion'     => 'Grupo que gestiona huertos urbanos compartidos, fomentando la agricultura ecológica y la alimentación sostenible.',
            'tipo'            => 'colectivo',
            'sector'          => 'Medioambiente',
            'miembros_count'  => 67,
            'proyectos_count' => 3,
            'imagen'          => '',
            'email_contacto'  => '',
            'estado'          => 'activo',
        ],
        (object) [
            'id'              => 5,
            'nombre'          => 'Plataforma Vivienda Digna',
            'descripcion'     => 'Movimiento ciudadano que lucha por el derecho a una vivienda asequible y contra la especulación inmobiliaria en el municipio.',
            'tipo'            => 'plataforma',
            'sector'          => 'Vivienda',
            'miembros_count'  => 234,
            'proyectos_count' => 4,
            'imagen'          => '',
            'email_contacto'  => '',
            'estado'          => 'activo',
        ],
        (object) [
            'id'              => 6,
            'nombre'          => 'Cooperativa de Consumo Raíces',
            'descripcion'     => 'Cooperativa de consumo responsable que conecta productores locales con familias, promoviendo el comercio justo y de proximidad.',
            'tipo'            => 'cooperativa',
            'sector'          => 'Alimentación',
            'miembros_count'  => 178,
            'proyectos_count' => 6,
            'imagen'          => '',
            'email_contacto'  => '',
            'estado'          => 'activo',
        ],
    ];
}

// Etiquetas y colores por tipo
$etiquetas_tipo_colectivo = [
    'asociacion'  => __('Asociación', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cooperativa' => __('Cooperativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'ong'         => __('ONG', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'colectivo'   => __('Colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'plataforma'  => __('Plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$colores_tipo_colectivo = [
    'asociacion'  => ['desde' => '#3B82F6', 'hasta' => '#1D4ED8'],
    'cooperativa' => ['desde' => '#10B981', 'hasta' => '#059669'],
    'ong'         => ['desde' => '#F59E0B', 'hasta' => '#D97706'],
    'colectivo'   => ['desde' => '#8B5CF6', 'hasta' => '#7C3AED'],
    'plataforma'  => ['desde' => '#EF4444', 'hasta' => '#DC2626'],
];

$iconos_tipo_colectivo = [
    'asociacion'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
    'cooperativa' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>',
    'ong'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
    'colectivo'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'plataforma'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>',
];
?>

<section id="colectivos-grid" class="flavor-component flavor-section py-16 <?php echo esc_attr($clases_componente); ?>" style="background: var(--flavor-bg, #F9FAFB);">
    <div class="flavor-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header de seccion -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: var(--flavor-text, #111827);">
                <?php echo esc_html($titulo_de_seccion); ?>
            </h2>
            <p class="text-lg max-w-2xl mx-auto" style="color: var(--flavor-text-muted, #6B7280);">
                <?php esc_html_e('Organizaciones y grupos que trabajan por la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <!-- Filtros por tipo -->
        <div class="flex flex-wrap justify-center gap-3 mb-12">
            <button class="px-5 py-2 rounded-full font-medium text-sm transition-all duration-300 shadow-sm" style="background: var(--flavor-primary, #6366F1); color: white;" data-tipo-filtro="todos">
                <?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <?php foreach ($etiquetas_tipo_colectivo as $clave_tipo => $etiqueta_tipo): ?>
                <button class="px-5 py-2 rounded-full font-medium text-sm transition-all duration-300 border" style="background: white; color: var(--flavor-text, #374151); border-color: #E5E7EB;" data-tipo-filtro="<?php echo esc_attr($clave_tipo); ?>">
                    <?php echo esc_html($etiqueta_tipo); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de colectivos -->
        <div class="grid grid-cols-1 <?php echo esc_attr($clase_grid_columnas); ?> gap-8">
            <?php foreach ($colectivos_para_mostrar as $colectivo_item): ?>
                <?php
                $tipo_actual     = $colectivo_item->tipo ?? 'colectivo';
                $colores_actuales = $colores_tipo_colectivo[$tipo_actual] ?? $colores_tipo_colectivo['colectivo'];
                $icono_actual    = $iconos_tipo_colectivo[$tipo_actual] ?? $iconos_tipo_colectivo['colectivo'];
                $etiqueta_actual = $etiquetas_tipo_colectivo[$tipo_actual] ?? ucfirst($tipo_actual);
                ?>
                <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2" data-tipo-colectivo="<?php echo esc_attr($tipo_actual); ?>">
                    <!-- Cabecera con gradiente -->
                    <div class="relative h-40 overflow-hidden" style="background: linear-gradient(135deg, <?php echo esc_attr($colores_actuales['desde']); ?> 0%, <?php echo esc_attr($colores_actuales['hasta']); ?> 100%);">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-20 h-20 text-white opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php echo $icono_actual; ?>
                            </svg>
                        </div>
                        <!-- Badge de tipo -->
                        <div class="absolute top-4 left-4 px-3 py-1 bg-white rounded-full text-xs font-bold" style="color: <?php echo esc_attr($colores_actuales['desde']); ?>;">
                            <?php echo esc_html($etiqueta_actual); ?>
                        </div>
                        <!-- Sector -->
                        <?php if (!empty($colectivo_item->sector)): ?>
                            <div class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-medium text-white" style="background: rgba(0,0,0,0.3); backdrop-filter: blur(4px);">
                                <?php echo esc_html($colectivo_item->sector); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Contenido de la tarjeta -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2 transition-colors duration-300" style="color: var(--flavor-text, #111827);">
                            <?php echo esc_html($colectivo_item->nombre); ?>
                        </h3>
                        <p class="text-sm mb-4 line-clamp-3" style="color: var(--flavor-text-muted, #6B7280);">
                            <?php echo esc_html(wp_trim_words($colectivo_item->descripcion ?? '', 25)); ?>
                        </p>

                        <!-- Metadatos -->
                        <div class="flex items-center gap-4 text-sm mb-4 pb-4 border-b" style="border-color: #F3F4F6; color: var(--flavor-text-muted, #6B7280);">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <?php echo esc_html($colectivo_item->miembros_count ?? 0); ?> <?php esc_html_e('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <?php echo esc_html($colectivo_item->proyectos_count ?? 0); ?> <?php esc_html_e('proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        </div>

                        <!-- Boton -->
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium px-2 py-1 rounded-full" style="background: #F0FDF4; color: #16A34A;">
                                <?php esc_html_e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <a href="#colectivo-<?php echo esc_attr($colectivo_item->id); ?>" class="inline-flex items-center px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-300" style="background: <?php echo esc_attr($colores_actuales['desde']); ?>; color: white;">
                                <?php esc_html_e('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Ver mas -->
        <div class="text-center mt-12">
            <button class="inline-flex items-center px-8 py-3 rounded-full font-bold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1" style="background: var(--flavor-primary, #6366F1); color: white;">
                <?php esc_html_e('Ver Todos los Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </button>
        </div>
    </div>
</section>
