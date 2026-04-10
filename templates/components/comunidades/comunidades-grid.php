<?php
/**
 * Template: Grid de Comunidades
 *
 * @var string $titulo_seccion
 * @var string $columnas
 * @var string $tipo_filtro
 * @var bool   $mostrar_miembros
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_seccion_valor  = $titulo_seccion ?? __('Explora Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN);
$columnas_valor        = $columnas ?? '3';
$tipo_filtro_valor     = $tipo_filtro ?? 'todos';
$mostrar_miembros_valor = isset($mostrar_miembros) ? (bool) $mostrar_miembros : true;

// Intentar obtener datos reales de la base de datos
$comunidades_listado = [];
global $wpdb;
$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

if (Flavor_Platform_Helpers::tabla_existe($tabla_comunidades)) {
    $condiciones_where   = ["estado = 'activa'", "tipo != 'secreta'"];
    $valores_preparacion = [];

    if ($tipo_filtro_valor !== 'todos') {
        $condiciones_where[]   = "tipo = %s";
        $valores_preparacion[] = $tipo_filtro_valor;
    }

    $sql_condiciones = implode(' AND ', $condiciones_where);
    $sql_consulta    = "SELECT * FROM $tabla_comunidades WHERE $sql_condiciones ORDER BY miembros_count DESC, created_at DESC LIMIT 12";

    if (!empty($valores_preparacion)) {
        $comunidades_listado = $wpdb->get_results($wpdb->prepare($sql_consulta, ...$valores_preparacion));
    } else {
        $comunidades_listado = $wpdb->get_results($sql_consulta);
    }
}

// Datos de ejemplo fallback si no hay datos reales
if (empty($comunidades_listado)) {
    $comunidades_listado = [
        (object) [
            'id'             => 1,
            'nombre'         => 'Huertos Urbanos del Barrio',
            'descripcion'    => 'Comunidad para compartir experiencias, consejos y semillas entre los hortelanos del barrio.',
            'tipo'           => 'abierta',
            'categoria'      => 'medioambiente',
            'ubicacion'      => 'Plaza Central',
            'miembros_count' => 42,
        ],
        (object) [
            'id'             => 2,
            'nombre'         => 'Club de Lectura Local',
            'descripcion'    => 'Nos reunimos mensualmente para comentar libros. Todos los generos son bienvenidos.',
            'tipo'           => 'abierta',
            'categoria'      => 'cultura',
            'ubicacion'      => 'Biblioteca Municipal',
            'miembros_count' => 28,
        ],
        (object) [
            'id'             => 3,
            'nombre'         => 'Runners del Parque',
            'descripcion'    => 'Grupo de corredores de todos los niveles. Quedamos 3 veces por semana para entrenar.',
            'tipo'           => 'abierta',
            'categoria'      => 'deportes',
            'ubicacion'      => 'Parque Municipal',
            'miembros_count' => 65,
        ],
        (object) [
            'id'             => 4,
            'nombre'         => 'Desarrolladores Web Local',
            'descripcion'    => 'Comunidad para desarrolladores web. Compartimos recursos y organizamos hackathons.',
            'tipo'           => 'cerrada',
            'categoria'      => 'tecnologia',
            'ubicacion'      => 'Coworking Central',
            'miembros_count' => 18,
        ],
        (object) [
            'id'             => 5,
            'nombre'         => 'Padres y Madres Activos',
            'descripcion'    => 'Espacio para familias del barrio. Organizamos actividades y nos apoyamos mutuamente.',
            'tipo'           => 'abierta',
            'categoria'      => 'vecinal',
            'ubicacion'      => 'Centro Civico',
            'miembros_count' => 53,
        ],
        (object) [
            'id'             => 6,
            'nombre'         => 'Meditacion y Mindfulness',
            'descripcion'    => 'Practica de meditacion y mindfulness. Sesiones guiadas para todos los niveles.',
            'tipo'           => 'abierta',
            'categoria'      => 'salud',
            'ubicacion'      => 'Centro Civico',
            'miembros_count' => 31,
        ],
    ];
}

// Mapa de colores por categoria
$colores_por_categoria = [
    'tecnologia'    => ['from-blue-500', 'to-cyan-600', 'text-blue-600', 'border-blue-300', 'bg-blue-50'],
    'deportes'      => ['from-green-500', 'to-emerald-600', 'text-green-600', 'border-green-300', 'bg-green-50'],
    'cultura'       => ['from-purple-500', 'to-violet-600', 'text-purple-600', 'border-purple-300', 'bg-purple-50'],
    'educacion'     => ['from-amber-500', 'to-yellow-600', 'text-amber-600', 'border-amber-300', 'bg-amber-50'],
    'medioambiente' => ['from-emerald-500', 'to-teal-600', 'text-emerald-600', 'border-emerald-300', 'bg-emerald-50'],
    'salud'         => ['from-rose-500', 'to-pink-600', 'text-rose-600', 'border-rose-300', 'bg-rose-50'],
    'ocio'          => ['from-orange-500', 'to-red-600', 'text-orange-600', 'border-orange-300', 'bg-orange-50'],
    'vecinal'       => ['from-indigo-500', 'to-blue-600', 'text-indigo-600', 'border-indigo-300', 'bg-indigo-50'],
    'otros'         => ['from-gray-500', 'to-slate-600', 'text-gray-600', 'border-gray-300', 'bg-gray-50'],
];

// Iconos SVG por categoria
$iconos_por_categoria = [
    'tecnologia'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
    'deportes'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
    'cultura'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    'educacion'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>',
    'medioambiente' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'salud'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
    'ocio'          => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'vecinal'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
    'otros'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
];

// Clase de columnas segun configuracion
$clase_columnas = 'lg:grid-cols-3';
switch ($columnas_valor) {
    case '2':
        $clase_columnas = 'lg:grid-cols-2';
        break;
    case '4':
        $clase_columnas = 'lg:grid-cols-4';
        break;
}
?>

<section class="flavor-component flavor-section py-16" style="background: var(--flavor-bg, #f9fafb);" id="comunidades">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header de la seccion -->
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-black mb-4" style="color: var(--flavor-text, #111827);">
                    <?php echo esc_html($titulo_seccion_valor); ?>
                </h2>
                <p class="text-lg max-w-2xl mx-auto" style="color: var(--flavor-text-secondary, #6b7280);">
                    <?php esc_html_e('Unete a comunidades activas o crea la tuya propia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- Filtros de categoria -->
            <div class="flex flex-wrap justify-center gap-3 mb-10">
                <button class="px-4 py-2 rounded-full text-sm font-semibold transition-all" style="background: var(--flavor-primary); color: white;">
                    <?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <?php
                $categorias_disponibles = [
                    'tecnologia'    => __('Tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'deportes'      => __('Deportes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'cultura'       => __('Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'medioambiente' => __('Medio Ambiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'salud'         => __('Salud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'vecinal'       => __('Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
                foreach ($categorias_disponibles as $clave_categoria => $etiqueta_categoria): ?>
                    <button class="px-4 py-2 rounded-full text-sm font-semibold transition-all border-2" style="border-color: var(--flavor-border, #e5e7eb); color: var(--flavor-text-secondary, #6b7280); background: white;">
                        <?php echo esc_html($etiqueta_categoria); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Grid de comunidades -->
            <div class="grid md:grid-cols-2 <?php echo esc_attr($clase_columnas); ?> gap-8">
                <?php foreach ($comunidades_listado as $comunidad_item):
                    $categoria_actual = $comunidad_item->categoria ?? 'otros';
                    $colores_actuales = $colores_por_categoria[$categoria_actual] ?? $colores_por_categoria['otros'];
                    $icono_actual     = $iconos_por_categoria[$categoria_actual] ?? $iconos_por_categoria['otros'];
                    $tipo_actual      = $comunidad_item->tipo ?? 'abierta';
                ?>
                    <div class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border-2 border-gray-100 hover:<?php echo esc_attr($colores_actuales[3]); ?>">
                        <!-- Cabecera con gradiente -->
                        <div class="relative h-40 bg-gradient-to-br <?php echo esc_attr($colores_actuales[0] . ' ' . $colores_actuales[1]); ?> overflow-hidden">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-20 h-20 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php echo $icono_actual; ?>
                                </svg>
                            </div>

                            <!-- Badge de tipo -->
                            <div class="absolute top-4 right-4 px-3 py-1 bg-white rounded-full text-xs font-bold <?php echo esc_attr($colores_actuales[2]); ?>">
                                <?php
                                $etiquetas_tipo = [
                                    'abierta' => __('Abierta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'cerrada' => __('Cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'secreta' => __('Secreta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                ];
                                echo esc_html($etiquetas_tipo[$tipo_actual] ?? $tipo_actual);
                                ?>
                            </div>

                            <!-- Badge de categoria -->
                            <div class="absolute top-4 left-4 px-3 py-1 bg-black/30 backdrop-blur-sm rounded-full text-xs font-medium text-white">
                                <?php echo esc_html(ucfirst($categoria_actual)); ?>
                            </div>

                            <?php if (!empty($comunidad_item->ubicacion)): ?>
                            <!-- Ubicacion -->
                            <div class="absolute bottom-3 left-4 flex items-center gap-1 text-white/80 text-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span><?php echo esc_html($comunidad_item->ubicacion); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Contenido de la tarjeta -->
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2 transition-colors group-hover:<?php echo esc_attr($colores_actuales[2]); ?>" style="color: var(--flavor-text, #111827);">
                                <?php echo esc_html($comunidad_item->nombre); ?>
                            </h3>
                            <p class="text-sm mb-4 line-clamp-2" style="color: var(--flavor-text-secondary, #6b7280);">
                                <?php echo esc_html($comunidad_item->descripcion); ?>
                            </p>

                            <!-- Footer de la tarjeta -->
                            <div class="flex items-center justify-between pt-4 border-t" style="border-color: var(--flavor-border, #f3f4f6);">
                                <?php if ($mostrar_miembros_valor): ?>
                                <div class="flex items-center gap-2 text-sm" style="color: var(--flavor-text-secondary, #6b7280);">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <span>
                                        <?php echo esc_html(sprintf(
                                            _n('%d miembro', '%d miembros', $comunidad_item->miembros_count, FLAVOR_PLATFORM_TEXT_DOMAIN),
                                            $comunidad_item->miembros_count
                                        )); ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <a href="#unirse-<?php echo esc_attr($comunidad_item->id); ?>"
                                   class="px-5 py-2 text-white font-semibold rounded-lg transition-all text-sm bg-gradient-to-r <?php echo esc_attr($colores_actuales[0] . ' ' . $colores_actuales[1]); ?> hover:opacity-90 hover:shadow-md">
                                    <?php
                                    if ($tipo_actual === 'cerrada') {
                                        esc_html_e('Solicitar Acceso', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                    } else {
                                        esc_html_e('Unirse', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                    }
                                    ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Boton ver mas -->
            <div class="text-center mt-12">
                <a href="#todas-comunidades" class="inline-flex items-center gap-2 px-8 py-3 font-bold rounded-full shadow-lg hover:shadow-xl transition-all" style="background: var(--flavor-primary); color: white;">
                    <?php esc_html_e('Ver Todas las Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>
