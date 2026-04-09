<?php
/**
 * Template: Estadísticas de Incidencias
 * Cards con métricas de incidencias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo_estadisticas = $titulo_estadisticas ?? 'Estadísticas de Incidencias';
$descripcion_estadisticas = $descripcion_estadisticas ?? 'Métricas y tendencias de reportes';
$mostrar_graficos = $mostrar_graficos ?? true;
$mostrar_tendencias = $mostrar_tendencias ?? true;

// Obtener datos reales de la base de datos
global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
$tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_incidencias)) === $tabla_incidencias;

// Valores por defecto
$total = 0;
$resueltas = 0;
$pendientes = 0;
$en_proceso = 0;
$incidencias_por_categoria = [];

if ($tabla_existe) {
    $total = intval($wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado != 'eliminada'"));
    $resueltas = intval($wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('resuelta', 'resolved', 'cerrada', 'closed')"));
    $pendientes = intval($wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('pendiente', 'pending')"));
    $en_proceso = intval($wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('en_proceso', 'in_progress')"));

    // Obtener incidencias por categoría/tipo
    $categorias_db = $wpdb->get_results(
        "SELECT tipo as categoria,
                COUNT(*) as cantidad,
                SUM(CASE WHEN estado IN ('resuelta', 'resolved', 'cerrada', 'closed') THEN 1 ELSE 0 END) as resueltas
         FROM $tabla_incidencias
         WHERE estado != 'eliminada' AND tipo IS NOT NULL AND tipo != ''
         GROUP BY tipo
         ORDER BY cantidad DESC
         LIMIT 6"
    );

    $colores_categorias = ['#FF5722', '#FFC107', '#4CAF50', '#F44336', '#2196F3', '#9C27B0'];
    $indice_color = 0;

    foreach ($categorias_db as $cat) {
        $porcentaje = $total > 0 ? ($cat->cantidad / $total) * 100 : 0;
        $incidencias_por_categoria[] = [
            'categoria' => ucfirst($cat->categoria),
            'cantidad' => intval($cat->cantidad),
            'porcentaje' => round($porcentaje, 1),
            'resueltas' => intval($cat->resueltas),
            'color' => $colores_categorias[$indice_color % count($colores_categorias)],
        ];
        $indice_color++;
    }
}

// Calcular porcentajes
$porcentaje_resueltas = $total > 0 ? round(($resueltas / $total) * 100, 1) : 0;
$porcentaje_pendientes = $total > 0 ? round(($pendientes / $total) * 100, 1) : 0;
$porcentaje_en_proceso = $total > 0 ? round(($en_proceso / $total) * 100, 1) : 0;

// Datos de métricas principales
$metricas_principales = [
    [
        'id' => 'total',
        'titulo' => __('Total Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => $total,
        'unidad' => __('reportes', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => '📊',
        'color' => 'gray',
        'tendencia' => 'up',
    ],
    [
        'id' => 'resueltas',
        'titulo' => __('Incidencias Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => $resueltas,
        'unidad' => __('completadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => '✅',
        'color' => 'green',
        'tendencia' => 'up',
        'porcentaje_total' => $porcentaje_resueltas,
    ],
    [
        'id' => 'pendientes',
        'titulo' => __('Incidencias Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => $pendientes,
        'unidad' => __('sin resolver', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => '⏳',
        'color' => 'yellow',
        'tendencia' => 'down',
        'porcentaje_total' => $porcentaje_pendientes,
    ],
    [
        'id' => 'en_proceso',
        'titulo' => __('En Proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => $en_proceso,
        'unidad' => __('en curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => '🔧',
        'color' => 'blue',
        'tendencia' => 'up',
        'porcentaje_total' => $porcentaje_en_proceso,
    ],
];

// Métricas temporales (simplificadas sin datos históricos)
$metricas_temporales = [
    [
        'titulo' => __('Tasa de Resolución', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => $porcentaje_resueltas . '%',
        'subtitulo' => '',
        'comparativa' => sprintf(__('%d de %d incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN), $resueltas, $total),
        'icono' => '📈',
        'color' => 'purple',
        'mejora' => $porcentaje_resueltas >= 50,
        'porcentaje_mejora' => $porcentaje_resueltas,
    ],
    [
        'titulo' => __('En Gestión', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => $en_proceso,
        'subtitulo' => __('incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'comparativa' => __('Actualmente en proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => '⚡',
        'color' => 'orange',
        'mejora' => true,
        'porcentaje_mejora' => $porcentaje_en_proceso,
    ],
    [
        'titulo' => __('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => $pendientes,
        'subtitulo' => __('incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'comparativa' => __('Esperando atención', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => '⏳',
        'color' => 'amber',
        'mejora' => $pendientes == 0,
        'porcentaje_mejora' => 100 - $porcentaje_pendientes,
    ],
];

// Tendencias - obtener de la DB si hay datos
$tendencias_ultimas_semanas = [];
if ($tabla_existe) {
    for ($i = 3; $i >= 0; $i--) {
        $fecha_inicio = date('Y-m-d', strtotime("-$i weeks monday"));
        $fecha_fin = date('Y-m-d', strtotime("-$i weeks sunday"));

        $reportadas = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_incidencias WHERE DATE(created_at) BETWEEN %s AND %s",
            $fecha_inicio, $fecha_fin
        )));

        $resueltas_semana = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_incidencias
             WHERE estado IN ('resuelta', 'resolved', 'cerrada', 'closed')
             AND DATE(created_at) BETWEEN %s AND %s",
            $fecha_inicio, $fecha_fin
        )));

        $tendencias_ultimas_semanas[] = [
            'semana' => sprintf(__('Semana %d', FLAVOR_PLATFORM_TEXT_DOMAIN), 4 - $i),
            'reportadas' => $reportadas,
            'resueltas' => $resueltas_semana,
        ];
    }
}
?>

<section class="flavor-component py-16 bg-white">
    <div class="flavor-container mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Encabezado -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <?php echo esc_html__('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo_estadisticas); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion_estadisticas); ?></p>
        </div>

        <!-- Métricas Principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <?php foreach ($metricas_principales as $metrica): ?>
                <div class="flavor-metrica-card bg-gradient-to-br from-white to-gray-50 rounded-2xl p-6 shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600 mb-1"><?php echo esc_html($metrica['titulo']); ?></p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-bold text-gray-900"><?php echo esc_html($metrica['valor']); ?></span>
                                <span class="text-sm text-gray-500"><?php echo esc_html($metrica['unidad']); ?></span>
                            </div>
                        </div>
                        <div class="text-4xl"><?php echo esc_html($metrica['icono']); ?></div>
                    </div>

                    <?php if (isset($metrica['porcentaje_total'])): ?>
                        <div class="mb-3">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-600"><?php echo esc_html__('Del total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <span class="font-semibold text-gray-700"><?php echo number_format($metrica['porcentaje_total'], 1); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500" style="width: <?php echo esc_attr($metrica['porcentaje_total']); ?>%; background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-1 pt-3 border-t border-gray-200">
                        <?php if ($metrica['tendencia'] === 'up'): ?>
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                            <span class="text-xs font-semibold text-green-600">
                                +<?php echo esc_html($metrica['cambio_porcentaje']); ?>%
                            </span>
                        <?php else: ?>
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                            <span class="text-xs font-semibold text-green-600">
                                -<?php echo esc_html($metrica['cambio_porcentaje']); ?>%
                            </span>
                        <?php endif; ?>
                        <span class="text-xs text-gray-500"><?php echo esc_html__('vs mes anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Métricas de Tiempo y Satisfacción -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <?php foreach ($metricas_temporales as $metrica_tiempo): ?>
                <div class="flavor-metrica-temporal bg-gradient-to-br from-white to-gray-50 rounded-2xl p-6 shadow-lg border border-gray-200">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600 mb-2"><?php echo esc_html($metrica_tiempo['titulo']); ?></p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-3xl font-bold text-gray-900"><?php echo esc_html($metrica_tiempo['valor']); ?></span>
                                <span class="text-xs text-gray-500"><?php echo esc_html($metrica_tiempo['subtitulo']); ?></span>
                            </div>
                        </div>
                        <div class="text-3xl"><?php echo esc_html($metrica_tiempo['icono']); ?></div>
                    </div>

                    <p class="text-xs text-gray-600 mb-3"><?php echo esc_html($metrica_tiempo['comparativa']); ?></p>

                    <?php if ($metrica_tiempo['mejora']): ?>
                        <div class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100">
                            <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                            <span class="text-xs font-semibold text-green-700">
                                <?php echo number_format($metrica_tiempo['porcentaje_mejora'], 1); ?>% mejor
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Incidencias por Categoría -->
        <?php if (!empty($incidencias_por_categoria)): ?>
        <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl p-8 shadow-lg border border-gray-200 mb-12">
            <h3 class="text-2xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Incidencias por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="space-y-4">
                <?php foreach ($incidencias_por_categoria as $categoria): ?>
                    <div class="group">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full" style="background-color: <?php echo esc_attr($categoria['color']); ?>;"></div>
                                <span class="font-semibold text-gray-900"><?php echo esc_html($categoria['categoria']); ?></span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-gray-900"><?php echo esc_html($categoria['cantidad']); ?> reportes</div>
                                <div class="text-xs text-gray-600"><?php echo number_format($categoria['porcentaje'], 1); ?>% del total</div>
                            </div>
                        </div>

                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 group-hover:shadow-lg" style="width: <?php echo esc_attr($categoria['porcentaje']); ?>%; background-color: <?php echo esc_attr($categoria['color']); ?>;"></div>
                        </div>

                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-600">
                            <span><?php echo esc_html__('Resueltas:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="font-semibold text-green-600"><?php echo esc_html($categoria['resueltas']); ?>/<?php echo esc_html($categoria['cantidad']); ?></span>
                            <span class="text-gray-500">(<?php echo $categoria['cantidad'] > 0 ? number_format(($categoria['resueltas'] / $categoria['cantidad']) * 100, 0) : 0; ?>%)</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tendencias de Últimas Semanas -->
        <?php if ($mostrar_tendencias && !empty($tendencias_ultimas_semanas)): ?>
            <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl p-8 shadow-lg border border-gray-200 mb-12">
                <h3 class="text-2xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Últimas 4 Semanas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-900"><?php echo esc_html__('Período', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-900"><?php echo esc_html__('Reportadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-900"><?php echo esc_html__('Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-900"><?php echo esc_html__('Tasa Resolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tendencias_ultimas_semanas as $semana):
                                $tasa_resolucion = $semana['reportadas'] > 0 ? ($semana['resueltas'] / $semana['reportadas']) * 100 : 0;
                            ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-4 text-gray-900 font-medium"><?php echo esc_html($semana['semana']); ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-block px-3 py-1 rounded-full text-white font-semibold" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                                            <?php echo esc_html($semana['reportadas']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-block px-3 py-1 rounded-full bg-green-100 text-green-700 font-semibold">
                                            <?php echo esc_html($semana['resueltas']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                                <div class="h-full bg-green-500 rounded-full" style="width: <?php echo esc_attr($tasa_resolucion); ?>%;"></div>
                                            </div>
                                            <span class="text-gray-700 font-medium text-xs"><?php echo number_format($tasa_resolucion, 0); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Llamada a la Acción -->
        <div class="bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl p-8 text-white text-center">
            <h3 class="text-2xl font-bold mb-2"><?php echo esc_html__('¿Encontraste un Problema?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="mb-6 opacity-90"><?php echo esc_html__('Ayuda a tu comunidad reportando incidencias en tu zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="#reportar" class="inline-flex items-center gap-2 px-8 py-3 bg-white text-red-600 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <?php echo esc_html__('Reportar Incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</section>
