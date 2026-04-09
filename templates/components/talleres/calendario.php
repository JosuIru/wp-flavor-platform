<?php
/**
 * Template: Calendario de Talleres
 *
 * @package FlavorChatIA
 * @var array $args Parámetros opcionales del template
 */

if (!defined('ABSPATH')) exit;

// Parámetros opcionales
$titulo = $args['titulo'] ?? 'Calendario de Talleres';
$descripcion = $args['descripcion'] ?? 'Planifica tu formación con nuestro calendario mensual';
$mes_actual = $args['mes_actual'] ?? 2; // Febrero
$ano_actual = $args['ano_actual'] ?? 2026;
$mostrar_leyenda = $args['mostrar_leyenda'] ?? true;

// Datos de eventos por día
$eventos_calendario = [
    '2026-02-12' => [
        ['titulo' => 'Reparaciones del Hogar', 'hora' => '11:00', 'instructor' => 'Carlos López', 'categoria' => 'Práctico', 'color' => 'yellow'],
    ],
    '2026-02-13' => [
        ['titulo' => 'Tecnología para Mayores', 'hora' => '17:00', 'instructor' => 'Pedro García', 'categoria' => 'Tecnología', 'color' => 'blue'],
    ],
    '2026-02-15' => [
        ['titulo' => 'Huerto Urbano Sostenible', 'hora' => '10:00', 'instructor' => 'Ana Verde', 'categoria' => 'Ecología', 'color' => 'green'],
    ],
    '2026-02-17' => [
        ['titulo' => 'Cocina Saludable', 'hora' => '18:00', 'instructor' => 'Laura García', 'categoria' => 'Cocina', 'color' => 'orange'],
        ['titulo' => 'Inglés Conversacional', 'hora' => '19:00', 'instructor' => 'Sarah Johnson', 'categoria' => 'Idiomas', 'color' => 'red'],
    ],
    '2026-02-18' => [
        ['titulo' => 'Yoga y Meditación', 'hora' => '18:30', 'instructor' => 'Marta Sánchez', 'categoria' => 'Bienestar', 'color' => 'indigo'],
    ],
    '2026-02-19' => [
        ['titulo' => 'Escritura Creativa', 'hora' => '18:00', 'instructor' => 'Elena Torres', 'categoria' => 'Creatividad', 'color' => 'teal'],
    ],
    '2026-02-20' => [
        ['titulo' => 'Fotografía Digital', 'hora' => '19:00', 'instructor' => 'Roberto Soto', 'categoria' => 'Fotografía', 'color' => 'purple'],
    ],
    '2026-02-22' => [
        ['titulo' => 'Bisutería Creativa', 'hora' => '17:00', 'instructor' => 'María Díaz', 'categoria' => 'Manualidades', 'color' => 'pink'],
    ],
];

// Funciones para generar el calendario
function obtener_dias_mes($mes, $ano) {
    return cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
}

function obtener_primer_dia_semana($mes, $ano) {
    $fecha = new DateTime("{$ano}-{$mes}-01");
    return (int)$fecha->format('N'); // 1 = lunes, 7 = domingo
}

function obtener_nombre_mes($mes, $ano) {
    $meses = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    return $meses[$mes - 1] . ' ' . $ano;
}

$dias_en_mes = obtener_dias_mes($mes_actual, $ano_actual);
$primer_dia_semana = obtener_primer_dia_semana($mes_actual, $ano_actual);
$nombre_mes = obtener_nombre_mes($mes_actual, $ano_actual);

// Próximos eventos (para sidebar)
$proximos_eventos = [
    ['fecha' => '12 Feb', 'titulo' => 'Reparaciones del Hogar', 'hora' => '11:00', 'instructor' => 'Carlos López', 'disponibles' => 3],
    ['fecha' => '15 Feb', 'titulo' => 'Huerto Urbano Sostenible', 'hora' => '10:00', 'instructor' => 'Ana Verde', 'disponibles' => 3],
    ['fecha' => '17 Feb', 'titulo' => 'Cocina Saludable', 'hora' => '18:00', 'instructor' => 'Laura García', 'disponibles' => 2],
    ['fecha' => '18 Feb', 'titulo' => 'Yoga y Meditación', 'hora' => '18:30', 'instructor' => 'Marta Sánchez', 'disponibles' => 5],
    ['fecha' => '22 Feb', 'titulo' => 'Bisutería Creativa', 'hora' => '17:00', 'instructor' => 'María Díaz', 'disponibles' => 3],
];
?>

<section class="flavor-component py-16 bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    <?php echo esc_html($titulo); ?>
                </h2>
                <p class="text-xl text-gray-600">
                    <?php echo esc_html($descripcion); ?>
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Calendario Principal -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                    <!-- Header del Calendario -->
                    <div class="flex items-center justify-between mb-8">
                        <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors" title="<?php echo esc_attr__('Mes anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <h3 class="text-2xl font-bold text-gray-900">
                            <?php echo esc_html($nombre_mes); ?>
                        </h3>
                        <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors" title="<?php echo esc_attr__('Mes siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Días de la Semana -->
                    <div class="grid grid-cols-7 gap-2 mb-2">
                        <?php $dias_semana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']; ?>
                        <?php foreach ($dias_semana as $dia): ?>
                            <div class="text-center font-bold text-gray-600 py-2">
                                <?php echo esc_html($dia); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Grid de Días -->
                    <div class="grid grid-cols-7 gap-2">
                        <!-- Celdas vacías antes del primer día -->
                        <?php for ($i = 1; $i < $primer_dia_semana; $i++): ?>
                            <div class="aspect-square"></div>
                        <?php endfor; ?>

                        <!-- Días del mes -->
                        <?php for ($dia = 1; $dia <= $dias_en_mes; $dia++): ?>
                            <?php
                            $fecha_formateada = sprintf('%04d-%02d-%02d', $ano_actual, $mes_actual, $dia);
                            $tiene_eventos = isset($eventos_calendario[$fecha_formateada]);
                            $es_hoy = false; // Puedes calcular esto si es necesario
                            ?>
                            <div class="aspect-square rounded-lg border-2 <?php echo $tiene_eventos ? 'border-blue-300 bg-blue-50' : 'border-gray-200 bg-white'; ?> hover:shadow-md transition-all cursor-pointer p-2 flex flex-col">
                                <span class="text-sm font-bold text-gray-900 mb-1">
                                    <?php echo esc_html($dia); ?>
                                </span>
                                <?php if ($tiene_eventos): ?>
                                    <div class="flex-1 space-y-1 min-h-0">
                                        <?php foreach (array_slice($eventos_calendario[$fecha_formateada], 0, 2) as $evento): ?>
                                            <div class="text-xs px-1.5 py-0.5 rounded-full bg-blue-200 text-blue-800 font-medium truncate hover:bg-blue-300 transition-colors" title="<?php echo esc_attr($evento['titulo']); ?>">
                                                <?php echo esc_html(substr($evento['titulo'], 0, 15)); ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($eventos_calendario[$fecha_formateada]) > 2): ?>
                                            <div class="text-xs px-1.5 py-0.5 text-gray-600 font-medium">
                                                +<?php echo count($eventos_calendario[$fecha_formateada]) - 2; ?> más
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Leyenda -->
                    <?php if ($mostrar_leyenda): ?>
                        <div class="mt-8 pt-8 border-t border-gray-200">
                            <h4 class="font-bold text-gray-900 mb-4">
                                <?php echo esc_html__('Leyenda de Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-full" style="background-color: #10b981;"></div>
                                    <span class="text-sm text-gray-700"><?php echo esc_html__('Ecología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-full" style="background-color: #f97316;"></div>
                                    <span class="text-sm text-gray-700"><?php echo esc_html__('Cocina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-full" style="background-color: #3b82f6;"></div>
                                    <span class="text-sm text-gray-700"><?php echo esc_html__('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-full" style="background-color: #a855f7;"></div>
                                    <span class="text-sm text-gray-700"><?php echo esc_html__('Fotografía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-full" style="background-color: #6366f1;"></div>
                                    <span class="text-sm text-gray-700"><?php echo esc_html__('Bienestar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-full" style="background-color: #ec4899;"></div>
                                    <span class="text-sm text-gray-700"><?php echo esc_html__('Manualidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar: Próximos Eventos -->
                <div class="space-y-6 lg:sticky lg:top-8 lg:h-fit">
                    <!-- Próximos Eventos -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <?php echo esc_html__('Próximos Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>

                        <div class="space-y-4">
                            <?php foreach ($proximos_eventos as $evento): ?>
                                <div class="flavor-event-item p-4 rounded-lg hover:bg-blue-50 transition-colors border border-gray-100 cursor-pointer">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-gray-900"><?php echo esc_html($evento['titulo']); ?></p>
                                            <p class="text-xs text-gray-600 mt-1">
                                                <span class="font-medium"><?php echo esc_html($evento['fecha']); ?></span>
                                                @ <?php echo esc_html($evento['hora']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-0.5"><?php echo esc_html($evento['instructor']); ?></p>
                                            <p class="text-xs text-green-600 font-medium mt-1">
                                                <?php echo esc_html($evento['disponibles']); ?> plazas disponibles
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button class="w-full mt-6 py-2 px-4 rounded-lg border border-blue-300 text-blue-600 font-semibold hover:bg-blue-50 transition-colors">
                            <?php echo esc_html__('Ver todos los eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>

                    <!-- Estadísticas -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <?php echo esc_html__('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="text-gray-600"><?php echo esc_html__('Talleres este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="font-bold text-lg text-purple-600">12</span>
                            </div>
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="text-gray-600"><?php echo esc_html__('Inscripciones totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="font-bold text-lg text-blue-600">87</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600"><?php echo esc_html__('Ocupación promedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="font-bold text-lg text-green-600">78%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros Rápidos -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-lg border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">
                            <?php echo esc_html__('Filtrar por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                                <input type="checkbox" checked class="w-4 h-4 rounded text-blue-600 cursor-pointer">
                                <span class="text-gray-700 font-medium"><?php echo esc_html__('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                                <input type="checkbox" class="w-4 h-4 rounded text-green-600 cursor-pointer">
                                <span class="text-gray-700 font-medium"><?php echo esc_html__('Ecología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                                <input type="checkbox" class="w-4 h-4 rounded text-orange-600 cursor-pointer">
                                <span class="text-gray-700 font-medium"><?php echo esc_html__('Cocina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                                <input type="checkbox" class="w-4 h-4 rounded text-blue-600 cursor-pointer">
                                <span class="text-gray-700 font-medium"><?php echo esc_html__('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer hover:bg-white p-2 rounded-lg transition-colors">
                                <input type="checkbox" class="w-4 h-4 rounded text-indigo-600 cursor-pointer">
                                <span class="text-gray-700 font-medium"><?php echo esc_html__('Bienestar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
