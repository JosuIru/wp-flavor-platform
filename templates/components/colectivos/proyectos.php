<?php
/**
 * Template: Proyectos de Colectivos
 *
 * @var string $titulo_seccion
 * @var bool   $mostrar_progreso
 * @var string $component_classes
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_de_seccion          = !empty($titulo_seccion) ? $titulo_seccion : __('Proyectos en Marcha', FLAVOR_PLATFORM_TEXT_DOMAIN);
$debe_mostrar_progreso      = isset($mostrar_progreso) ? (bool) $mostrar_progreso : true;
$clases_componente          = !empty($component_classes) ? $component_classes : '';

// Intentar cargar proyectos reales
$proyectos_para_mostrar = [];

global $wpdb;
$tabla_colectivos_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';
$tabla_colectivos           = $wpdb->prefix . 'flavor_colectivos';

if (Flavor_Platform_Helpers::tabla_existe($tabla_colectivos_proyectos)) {
    $proyectos_para_mostrar = $wpdb->get_results(
        "SELECT p.*, c.nombre as colectivo_nombre, c.tipo as colectivo_tipo
         FROM $tabla_colectivos_proyectos p
         INNER JOIN $tabla_colectivos c ON p.colectivo_id = c.id
         WHERE p.estado IN ('planificado', 'en_curso')
         ORDER BY p.updated_at DESC
         LIMIT 6"
    );
}

// Datos fallback si no hay proyectos reales
if (empty($proyectos_para_mostrar)) {
    $proyectos_para_mostrar = [
        (object) [
            'id'                => 1,
            'titulo'            => 'Rehabilitación del Centro Comunitario',
            'descripcion'       => 'Reforma integral del centro comunitario del barrio para crear un espacio polivalente con sala de reuniones, biblioteca y zona infantil.',
            'estado'            => 'en_curso',
            'presupuesto'       => 45000.00,
            'fecha_inicio'      => '2025-09-01',
            'fecha_fin'         => '2026-03-31',
            'progreso'          => 65,
            'responsable_id'    => 1,
            'colectivo_nombre'  => 'Asociación Vecinal La Esperanza',
            'colectivo_tipo'    => 'asociacion',
            'participantes'     => json_encode([1, 2, 3, 4, 5]),
        ],
        (object) [
            'id'                => 2,
            'titulo'            => 'Instalación de Paneles Solares Comunitarios',
            'descripcion'       => 'Proyecto de autoconsumo compartido con instalación de paneles solares en edificios del barrio para reducir la factura energética.',
            'estado'            => 'en_curso',
            'presupuesto'       => 120000.00,
            'fecha_inicio'      => '2025-06-15',
            'fecha_fin'         => '2026-06-15',
            'progreso'          => 40,
            'responsable_id'    => 2,
            'colectivo_nombre'  => 'Cooperativa Energía Verde',
            'colectivo_tipo'    => 'cooperativa',
            'participantes'     => json_encode([1, 2, 3, 4, 5, 6, 7, 8]),
        ],
        (object) [
            'id'                => 3,
            'titulo'            => 'Programa de Mentoría para Jóvenes',
            'descripcion'       => 'Programa de acompañamiento y formación para jóvenes en riesgo de exclusión, con talleres de habilidades y prácticas laborales.',
            'estado'            => 'planificado',
            'presupuesto'       => 18500.00,
            'fecha_inicio'      => '2026-02-01',
            'fecha_fin'         => '2026-12-31',
            'progreso'          => 10,
            'responsable_id'    => 3,
            'colectivo_nombre'  => 'ONG Puentes Sin Fronteras',
            'colectivo_tipo'    => 'ong',
            'participantes'     => json_encode([1, 2, 3]),
        ],
        (object) [
            'id'                => 4,
            'titulo'            => 'Huertos Escolares Ecológicos',
            'descripcion'       => 'Creación de huertos ecológicos en colegios del municipio para educar sobre alimentación saludable y sostenibilidad.',
            'estado'            => 'en_curso',
            'presupuesto'       => 8200.00,
            'fecha_inicio'      => '2025-10-01',
            'fecha_fin'         => '2026-06-30',
            'progreso'          => 55,
            'responsable_id'    => 4,
            'colectivo_nombre'  => 'Colectivo Huerta Comunitaria',
            'colectivo_tipo'    => 'colectivo',
            'participantes'     => json_encode([1, 2, 3, 4]),
        ],
    ];
}

// Mapeo de colores por estado de proyecto
$colores_estado_proyecto = [
    'planificado' => ['fondo' => '#EFF6FF', 'texto' => '#2563EB', 'barra' => '#3B82F6'],
    'en_curso'    => ['fondo' => '#F0FDF4', 'texto' => '#16A34A', 'barra' => '#22C55E'],
    'completado'  => ['fondo' => '#F5F3FF', 'texto' => '#7C3AED', 'barra' => '#8B5CF6'],
    'cancelado'   => ['fondo' => '#FEF2F2', 'texto' => '#DC2626', 'barra' => '#EF4444'],
];

$etiquetas_estado_proyecto = [
    'planificado' => __('Planificado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'en_curso'    => __('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'completado'  => __('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cancelado'   => __('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<section class="flavor-component flavor-section py-16 <?php echo esc_attr($clases_componente); ?>" style="background: white;">
    <div class="flavor-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header de seccion -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: var(--flavor-text, #111827);">
                <?php echo esc_html($titulo_de_seccion); ?>
            </h2>
            <p class="text-lg max-w-2xl mx-auto" style="color: var(--flavor-text-muted, #6B7280);">
                <?php esc_html_e('Iniciativas impulsadas por los colectivos de nuestra comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <!-- Grid de proyectos -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($proyectos_para_mostrar as $proyecto_item): ?>
                <?php
                $estado_actual       = $proyecto_item->estado ?? 'planificado';
                $colores_actuales    = $colores_estado_proyecto[$estado_actual] ?? $colores_estado_proyecto['planificado'];
                $etiqueta_estado     = $etiquetas_estado_proyecto[$estado_actual] ?? ucfirst($estado_actual);
                $progreso_actual     = (int) ($proyecto_item->progreso ?? 0);
                $presupuesto_actual  = (float) ($proyecto_item->presupuesto ?? 0);
                $participantes_decodificados = !empty($proyecto_item->participantes)
                    ? json_decode($proyecto_item->participantes, true)
                    : [];
                $numero_participantes = is_array($participantes_decodificados) ? count($participantes_decodificados) : 0;
                ?>
                <div class="bg-white rounded-2xl border overflow-hidden transition-all duration-300 hover:shadow-xl" style="border-color: #E5E7EB;">
                    <div class="p-6">
                        <!-- Cabecera del proyecto -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full" style="background: <?php echo esc_attr($colores_actuales['fondo']); ?>; color: <?php echo esc_attr($colores_actuales['texto']); ?>;">
                                        <?php echo esc_html($etiqueta_estado); ?>
                                    </span>
                                    <?php if (!empty($proyecto_item->colectivo_nombre)): ?>
                                        <span class="text-xs" style="color: var(--flavor-text-muted, #9CA3AF);">
                                            <?php echo esc_html($proyecto_item->colectivo_nombre); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-lg font-bold" style="color: var(--flavor-text, #111827);">
                                    <?php echo esc_html($proyecto_item->titulo); ?>
                                </h3>
                            </div>
                        </div>

                        <!-- Descripcion -->
                        <p class="text-sm mb-4 line-clamp-2" style="color: var(--flavor-text-muted, #6B7280);">
                            <?php echo esc_html(wp_trim_words($proyecto_item->descripcion ?? '', 25)); ?>
                        </p>

                        <!-- Barra de progreso -->
                        <?php if ($debe_mostrar_progreso): ?>
                            <div class="mb-4">
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span style="color: var(--flavor-text-muted, #6B7280);"><?php esc_html_e('Progreso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <span class="font-semibold" style="color: <?php echo esc_attr($colores_actuales['texto']); ?>;">
                                        <?php echo esc_html($progreso_actual); ?>%
                                    </span>
                                </div>
                                <div class="w-full h-2.5 rounded-full" style="background: #F3F4F6;">
                                    <div class="h-2.5 rounded-full transition-all duration-500" style="width: <?php echo esc_attr($progreso_actual); ?>%; background: <?php echo esc_attr($colores_actuales['barra']); ?>;"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Metadatos -->
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t" style="border-color: #F3F4F6;">
                            <!-- Presupuesto -->
                            <div>
                                <div class="text-xs mb-1" style="color: var(--flavor-text-muted, #9CA3AF);">
                                    <?php esc_html_e('Presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </div>
                                <div class="text-sm font-semibold" style="color: var(--flavor-text, #111827);">
                                    <?php
                                    if ($presupuesto_actual > 0) {
                                        echo esc_html(number_format($presupuesto_actual, 0, ',', '.') . ' EUR');
                                    } else {
                                        esc_html_e('Sin definir', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- Participantes -->
                            <div>
                                <div class="text-xs mb-1" style="color: var(--flavor-text-muted, #9CA3AF);">
                                    <?php esc_html_e('Participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </div>
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--flavor-text-muted, #9CA3AF);">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <span class="text-sm font-semibold" style="color: var(--flavor-text, #111827);">
                                        <?php echo esc_html($numero_participantes); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Fecha inicio -->
                            <?php if (!empty($proyecto_item->fecha_inicio)): ?>
                                <div>
                                    <div class="text-xs mb-1" style="color: var(--flavor-text-muted, #9CA3AF);">
                                        <?php esc_html_e('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </div>
                                    <div class="text-sm font-medium" style="color: var(--flavor-text, #374151);">
                                        <?php echo esc_html(date_i18n('M Y', strtotime($proyecto_item->fecha_inicio))); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Fecha fin -->
                            <?php if (!empty($proyecto_item->fecha_fin)): ?>
                                <div>
                                    <div class="text-xs mb-1" style="color: var(--flavor-text-muted, #9CA3AF);">
                                        <?php esc_html_e('Fin previsto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </div>
                                    <div class="text-sm font-medium" style="color: var(--flavor-text, #374151);">
                                        <?php echo esc_html(date_i18n('M Y', strtotime($proyecto_item->fecha_fin))); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Ver todos los proyectos -->
        <div class="text-center mt-12">
            <button class="inline-flex items-center px-8 py-3 rounded-full font-bold transition-all duration-300 border-2 hover:shadow-lg" style="border-color: var(--flavor-primary, #6366F1); color: var(--flavor-primary, #6366F1); background: transparent;">
                <?php esc_html_e('Ver Todos los Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </button>
        </div>
    </div>
</section>
