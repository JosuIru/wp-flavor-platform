<?php
/**
 * Template: Timeline de Fases del Proceso Participativo
 *
 * Muestra un timeline visual de las fases del proceso de participación ciudadana
 * con indicadores de progreso y fechas clave.
 *
 * @package FlavorPlatform
 * @var array $args Variables pasadas al template
 */

if (!defined('ABSPATH')) exit;

// Configuración por defecto
$titulo = $args['titulo'] ?? 'Fases del Proceso Participativo';
$subtitulo = $args['subtitulo'] ?? 'Conoce el calendario de participación ciudadana';
$mostrar_fechas = $args['mostrar_fechas'] ?? true;
$orientacion = $args['orientacion'] ?? 'vertical'; // 'vertical' o 'horizontal'
$color_primario = $args['color_primario'] ?? '#f59e0b'; // Ámbar
$clase_componente = $args['clase_componente'] ?? '';

// Fases del timeline
$fases = $args['fases'] ?? [
    [
        'numero' => 1,
        'titulo' => 'Convocatoria Abierta',
        'descripcion' => 'Período de recepción de propuestas ciudadanas',
        'fecha_inicio' => '1 de Enero 2025',
        'fecha_fin' => '15 de Enero 2025',
        'estado' => 'completado',
        'icono' => 'megaphone',
        'color' => 'blue'
    ],
    [
        'numero' => 2,
        'titulo' => 'Evaluación Técnica',
        'descripcion' => 'Análisis de viabilidad y coherencia de propuestas',
        'fecha_inicio' => '16 de Enero 2025',
        'fecha_fin' => '31 de Enero 2025',
        'estado' => 'completado',
        'icono' => 'document-check',
        'color' => 'indigo'
    ],
    [
        'numero' => 3,
        'titulo' => 'Debate Público',
        'descripcion' => 'Foros de discusión y mejora de propuestas',
        'fecha_inicio' => '1 de Febrero 2025',
        'fecha_fin' => '14 de Febrero 2025',
        'estado' => 'en-progreso',
        'icono' => 'chat-bubble',
        'color' => 'amber'
    ],
    [
        'numero' => 4,
        'titulo' => 'Votación Ciudadana',
        'descripcion' => 'Cada ciudadano emite su voto en las propuestas',
        'fecha_inicio' => '15 de Febrero 2025',
        'fecha_fin' => '28 de Febrero 2025',
        'estado' => 'pendiente',
        'icono' => 'check-circle',
        'color' => 'green'
    ],
    [
        'numero' => 5,
        'titulo' => 'Implementación',
        'descripcion' => 'Ejecución de propuestas ganadores y seguimiento',
        'fecha_inicio' => '1 de Marzo 2025',
        'fecha_fin' => 'Diciembre 2025',
        'estado' => 'pendiente',
        'icono' => 'rocket',
        'color' => 'purple'
    ]
];

// Iconos SVG por tipo
$iconos_svg = [
    'megaphone' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.961 1.961 0 01-2.437-1.974V5.882m14.147 0a2 2 0 00-2.437-1.974V19.24a2 2 0 002.437 1.974zm0 0C18.809 21.646 15.169 22 12 22c-2.4 0-4.804-.247-7.078-.667m14.147-21C9.591 2.354 6.322 2 3 2c1.053 0 2.102.85 2.748 2.338.848 1.972 1.573 4.738 1.573 7.662s-.725 5.69-1.573 7.662C5.1 21.15 4.051 22 3 22c3.322 0 6.591-.354 9.147-1.33" /></svg>',
    'document-check' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    'chat-bubble' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" /></svg>',
    'check-circle' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    'rocket' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>'
];

// Iconos por color
$colores_bg = [
    'blue' => 'from-blue-500 to-blue-600',
    'indigo' => 'from-indigo-500 to-indigo-600',
    'amber' => 'from-amber-500 to-amber-600',
    'green' => 'from-green-500 to-green-600',
    'purple' => 'from-purple-500 to-purple-600'
];

$colores_texto = [
    'blue' => 'text-blue-600',
    'indigo' => 'text-indigo-600',
    'amber' => 'text-amber-600',
    'green' => 'text-green-600',
    'purple' => 'text-purple-600'
];

$colores_border = [
    'blue' => 'border-blue-200 bg-blue-50',
    'indigo' => 'border-indigo-200 bg-indigo-50',
    'amber' => 'border-amber-200 bg-amber-50',
    'green' => 'border-green-200 bg-green-50',
    'purple' => 'border-purple-200 bg-purple-50'
];

// Función para obtener icono SVG
$get_icono = function($tipo) use ($iconos_svg) {
    return $iconos_svg[$tipo] ?? $iconos_svg['rocket'];
};

// Calcular progreso
$fases_completadas = count(array_filter($fases, function($f) { return $f['estado'] === 'completado'; }));
$total_fases = count($fases);
$porcentaje_progreso = ($fases_completadas / $total_fases) * 100;

?>

<section class="flavor-component py-16 md:py-20 bg-gradient-to-br from-slate-50 to-gray-100 <?php echo esc_attr($clase_componente); ?>">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl">

        <!-- Header -->
        <div class="text-center mb-12 md:mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
                <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo); ?>
            </p>
            <div class="w-20 h-1 bg-amber-500 mx-auto rounded-full mt-6"></div>
        </div>

        <!-- Barra de progreso -->
        <div class="mb-12 bg-white rounded-xl p-6 shadow-lg border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold text-gray-700">Progreso General</span>
                <span class="text-sm font-bold text-amber-600"><?php echo round($porcentaje_progreso); ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-400 to-amber-600 h-full rounded-full transition-all duration-500" style="width: <?php echo $porcentaje_progreso; ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-3">
                <?php printf(
                    esc_html__('%d de %d fases completadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $fases_completadas,
                    $total_fases
                ); ?>
            </p>
        </div>

        <!-- Timeline -->
        <?php if ($orientacion === 'horizontal'): ?>
            <!-- Timeline Horizontal (Desktop) -->
            <div class="hidden lg:block">
                <div class="relative">
                    <!-- Línea conectora -->
                    <div class="absolute top-20 left-0 right-0 h-1 bg-gray-300" style="z-index: 0;"></div>

                    <!-- Fases -->
                    <div class="grid grid-cols-5 gap-4 relative z-10">
                        <?php foreach ($fases as $indice => $fase): ?>
                            <?php
                            $color_clase = $colores_bg[$fase['color']] ?? 'from-gray-500 to-gray-600';
                            $color_texto_clase = $colores_texto[$fase['color']] ?? 'text-gray-600';
                            $color_border_clase = $colores_border[$fase['color']] ?? 'border-gray-200 bg-gray-50';
                            $es_completado = $fase['estado'] === 'completado';
                            $es_progreso = $fase['estado'] === 'en-progreso';
                            ?>
                            <div class="text-center">
                                <!-- Círculo del nodo -->
                                <div class="mb-6 flex justify-center">
                                    <div class="relative">
                                        <div class="absolute inset-0 bg-gradient-to-br <?php echo $color_clase; ?> rounded-full blur-lg opacity-40"></div>
                                        <div class="relative w-20 h-20 bg-gradient-to-br <?php echo $color_clase; ?> rounded-full flex items-center justify-center text-white shadow-lg <?php echo $es_progreso ? 'ring-4 ring-offset-2 ring-amber-300 animate-pulse' : ''; ?>">
                                            <div class="w-16 h-16 bg-gradient-to-br <?php echo $color_clase; ?> rounded-full flex items-center justify-center">
                                                <?php
                                                if ($es_completado) {
                                                    echo '<svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>';
                                                } else {
                                                    echo $get_icono($fase['icono']);
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información de la fase -->
                                <div class="bg-white rounded-lg p-4 border <?php echo $color_border_clase; ?> shadow">
                                    <h3 class="font-bold text-gray-900 mb-2">
                                        <?php echo esc_html($fase['titulo']); ?>
                                    </h3>
                                    <p class="text-xs text-gray-600 mb-3">
                                        <?php echo esc_html($fase['descripcion']); ?>
                                    </p>

                                    <!-- Fechas -->
                                    <?php if ($mostrar_fechas): ?>
                                        <div class="text-xs space-y-1 pt-3 border-t border-gray-200">
                                            <div class="text-gray-500">
                                                <svg class="inline w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                <?php echo esc_html($fase['fecha_inicio']); ?>
                                            </div>
                                            <div class="text-gray-500">
                                                <?php echo esc_html($fase['fecha_fin']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Estado -->
                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <?php
                                        $clase_estado = match($fase['estado']) {
                                            'completado' => 'bg-green-100 text-green-700',
                                            'en-progreso' => 'bg-amber-100 text-amber-700',
                                            'pendiente' => 'bg-gray-100 text-gray-700'
                                        };
                                        $texto_estado = match($fase['estado']) {
                                            'completado' => 'Completado',
                                            'en-progreso' => 'En Progreso',
                                            'pendiente' => 'Próximamente'
                                        };
                                        ?>
                                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full <?php echo $clase_estado; ?>">
                                            <?php echo esc_html__($texto_estado, FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <!-- Timeline Vertical (Mobile/Tablet y fallback) -->
        <div class="<?php echo $orientacion === 'horizontal' ? 'lg:hidden' : ''; ?>">
            <div class="relative">
                <!-- Línea conectora vertical -->
                <div class="absolute left-8 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-amber-600 rounded-full"></div>

                <!-- Fases -->
                <div class="space-y-8">
                    <?php foreach ($fases as $indice => $fase): ?>
                        <?php
                        $color_clase = $colores_bg[$fase['color']] ?? 'from-gray-500 to-gray-600';
                        $color_border_clase = $colores_border[$fase['color']] ?? 'border-gray-200 bg-gray-50';
                        $es_completado = $fase['estado'] === 'completado';
                        $es_progreso = $fase['estado'] === 'en-progreso';
                        ?>
                        <div class="relative pl-28">
                            <!-- Círculo del nodo -->
                            <div class="absolute left-0 w-16 h-16 bg-gradient-to-br <?php echo $color_clase; ?> rounded-full flex items-center justify-center text-white shadow-lg <?php echo $es_progreso ? 'ring-4 ring-offset-2 ring-amber-300 animate-pulse' : ''; ?>">
                                <?php
                                if ($es_completado) {
                                    echo '<svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>';
                                } else {
                                    echo $get_icono($fase['icono']);
                                }
                                ?>
                            </div>

                            <!-- Contenido -->
                            <div class="bg-white rounded-lg p-5 border <?php echo $color_border_clase; ?> shadow-md">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <h3 class="font-bold text-gray-900 text-lg">
                                        <?php echo esc_html($fase['titulo']); ?>
                                    </h3>
                                    <?php
                                    $clase_estado = match($fase['estado']) {
                                        'completado' => 'bg-green-100 text-green-700',
                                        'en-progreso' => 'bg-amber-100 text-amber-700',
                                        'pendiente' => 'bg-gray-100 text-gray-700'
                                    };
                                    $texto_estado = match($fase['estado']) {
                                        'completado' => 'Completado',
                                        'en-progreso' => 'En Progreso',
                                        'pendiente' => 'Próximamente'
                                    };
                                    ?>
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap <?php echo $clase_estado; ?>">
                                        <?php echo esc_html__($texto_estado, FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mb-3">
                                    <?php echo esc_html($fase['descripcion']); ?>
                                </p>

                                <!-- Fechas -->
                                <?php if ($mostrar_fechas): ?>
                                    <div class="bg-gray-50 rounded p-3 space-y-1 text-xs text-gray-600 border border-gray-200">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            <span><?php echo esc_html($fase['fecha_inicio']); ?> - <?php echo esc_html($fase['fecha_fin']); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- CTA o información adicional -->
        <div class="mt-12 text-center">
            <p class="text-gray-600 mb-6">
                <?php echo esc_html__('Descubre cómo puedes participar en cada fase del proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
            <a href="#" class="inline-flex items-center px-8 py-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg transition duration-300 transform hover:scale-105">
                <span><?php echo esc_html__('Participar Ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </div>

    </div>
</section>
