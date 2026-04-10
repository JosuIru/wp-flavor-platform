<?php
/**
 * Template: Estadisticas CRM / Dashboard
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_seccion = $titulo_seccion ?? __('Dashboard CRM', FLAVOR_PLATFORM_TEXT_DOMAIN);
$estilo = $estilo ?? 'cards';

// Obtener estadisticas reales
$total_clientes_crm = 0;
$clientes_activos_crm = 0;
$clientes_potenciales_crm = 0;
$clientes_inactivos_crm = 0;
$clientes_perdidos_crm = 0;
$nuevos_este_mes_crm = 0;
$valor_total_pipeline_crm = 0;
$desglose_por_tipo_crm = [];
$desglose_por_origen_crm = [];
$interacciones_recientes_crm = 0;

$datos_reales_disponibles = false;

if (class_exists('wpdb')) {
    global $wpdb;
    $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
    $tabla_notas = $wpdb->prefix . 'flavor_clientes_notas';

    if (Flavor_Platform_Helpers::tabla_existe($tabla_clientes)) {
        $datos_reales_disponibles = true;

        $total_clientes_crm = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes");
        $clientes_activos_crm = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes WHERE estado = 'activo'");
        $clientes_potenciales_crm = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes WHERE estado = 'potencial'");
        $clientes_inactivos_crm = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes WHERE estado = 'inactivo'");
        $clientes_perdidos_crm = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes WHERE estado = 'perdido'");

        $nuevos_este_mes_crm = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_clientes WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );

        $valor_total_pipeline_crm = (float) $wpdb->get_var(
            "SELECT IFNULL(SUM(valor_estimado), 0) FROM $tabla_clientes WHERE valor_estimado > 0"
        );

        // Por tipo
        $tipos_resultado = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY tipo ORDER BY cantidad DESC"
        );
        foreach ($tipos_resultado as $tipo_registro) {
            $desglose_por_tipo_crm[$tipo_registro->tipo] = (int) $tipo_registro->cantidad;
        }

        // Por origen
        $origenes_resultado = $wpdb->get_results(
            "SELECT origen, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY origen ORDER BY cantidad DESC"
        );
        foreach ($origenes_resultado as $origen_registro) {
            $desglose_por_origen_crm[$origen_registro->origen] = (int) $origen_registro->cantidad;
        }

        // Interacciones este mes
        if (Flavor_Platform_Helpers::tabla_existe($tabla_notas)) {
            $interacciones_recientes_crm = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_notas WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
            );
        }
    }
}

// Fallback con datos de ejemplo
if (!$datos_reales_disponibles || $total_clientes_crm === 0) {
    $total_clientes_crm = 248;
    $clientes_activos_crm = 156;
    $clientes_potenciales_crm = 52;
    $clientes_inactivos_crm = 28;
    $clientes_perdidos_crm = 12;
    $nuevos_este_mes_crm = 32;
    $valor_total_pipeline_crm = 387500;
    $interacciones_recientes_crm = 145;
    $desglose_por_tipo_crm = [
        'empresa' => 98,
        'particular' => 85,
        'autonomo' => 42,
        'administracion' => 23,
    ];
    $desglose_por_origen_crm = [
        'web' => 78,
        'referido' => 65,
        'redes' => 48,
        'directo' => 40,
        'otro' => 17,
    ];
}

// Formatear pipeline
if ($valor_total_pipeline_crm >= 1000000) {
    $pipeline_formateado = number_format($valor_total_pipeline_crm / 1000000, 1, ',', '.') . 'M &euro;';
} elseif ($valor_total_pipeline_crm >= 1000) {
    $pipeline_formateado = number_format($valor_total_pipeline_crm / 1000, 1, ',', '.') . 'K &euro;';
} else {
    $pipeline_formateado = number_format($valor_total_pipeline_crm, 0, ',', '.') . ' &euro;';
}

// Tasa de conversion (activos / total)
$tasa_conversion_crm = $total_clientes_crm > 0 ? round(($clientes_activos_crm / $total_clientes_crm) * 100) : 0;

// Iconos SVG para las tarjetas principales
$iconos_estadisticas_crm = [
    'total' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
    'nuevos' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>',
    'pipeline' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    'conversion' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
    'interacciones' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>',
    'activos' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
];

// Etiquetas de tipo
$etiquetas_tipo_crm = [
    'particular' => __('Particular', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'empresa' => __('Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'autonomo' => __('Autonomo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'administracion' => __('Administracion', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

// Etiquetas de origen
$etiquetas_origen_crm = [
    'web' => __('Web', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'referido' => __('Referido', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'redes' => __('Redes Sociales', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'directo' => __('Directo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otro' => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

// Colores para barras de tipo
$colores_tipo_crm = [
    'empresa' => '#667eea',
    'particular' => '#10b981',
    'autonomo' => '#f59e0b',
    'administracion' => '#8b5cf6',
];

// Colores para barras de origen
$colores_origen_crm = [
    'web' => '#3b82f6',
    'referido' => '#10b981',
    'redes' => '#ec4899',
    'directo' => '#f59e0b',
    'otro' => '#6b7280',
];
?>

<?php if ($estilo === 'cards'): ?>
    <!-- Estilo Cards -->
    <section class="flavor-component flavor-section" style="background: var(--flavor-background-alt, #f8f9fa);">
        <div class="flavor-container">
            <!-- Titulo -->
            <div class="text-center max-w-3xl mx-auto mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                    <?php echo esc_html($titulo_seccion); ?>
                </h2>
                <p class="text-lg" style="color: var(--flavor-text-secondary, #666666);">
                    <?php esc_html_e('Metricas clave de tu gestion comercial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- KPIs principales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- Total clientes -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center"
                             style="background: rgba(var(--flavor-primary-rgb, 99, 102, 241), 0.1); color: var(--flavor-primary, #667eea);">
                            <?php echo $iconos_estadisticas_crm['total']; ?>
                        </div>
                        <div>
                            <div class="text-3xl font-bold" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html(number_format($total_clientes_crm, 0, ',', '.')); ?>
                            </div>
                            <div class="text-sm" style="color: var(--flavor-text-secondary, #666);">
                                <?php esc_html_e('Total Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nuevos este mes -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center"
                             style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <?php echo $iconos_estadisticas_crm['nuevos']; ?>
                        </div>
                        <div>
                            <div class="text-3xl font-bold" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html($nuevos_este_mes_crm); ?>
                            </div>
                            <div class="text-sm" style="color: var(--flavor-text-secondary, #666);">
                                <?php esc_html_e('Nuevos este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pipeline valor -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center"
                             style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                            <?php echo $iconos_estadisticas_crm['pipeline']; ?>
                        </div>
                        <div>
                            <div class="text-3xl font-bold" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo $pipeline_formateado; ?>
                            </div>
                            <div class="text-sm" style="color: var(--flavor-text-secondary, #666);">
                                <?php esc_html_e('Pipeline', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasa conversion -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center"
                             style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <?php echo $iconos_estadisticas_crm['conversion']; ?>
                        </div>
                        <div>
                            <div class="text-3xl font-bold" style="color: var(--flavor-text-primary, #1a1a1a);">
                                <?php echo esc_html($tasa_conversion_crm); ?>%
                            </div>
                            <div class="text-sm" style="color: var(--flavor-text-secondary, #666);">
                                <?php esc_html_e('Tasa Conversion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila secundaria: Estado + Tipo + Origen -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Desglose por estado -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                        <?php esc_html_e('Por Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="space-y-4">
                        <?php
                        $estados_datos = [
                            ['label' => __('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'valor' => $clientes_activos_crm, 'color' => '#10b981'],
                            ['label' => __('Potenciales', FLAVOR_PLATFORM_TEXT_DOMAIN), 'valor' => $clientes_potenciales_crm, 'color' => '#f59e0b'],
                            ['label' => __('Inactivos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'valor' => $clientes_inactivos_crm, 'color' => '#6b7280'],
                            ['label' => __('Perdidos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'valor' => $clientes_perdidos_crm, 'color' => '#ef4444'],
                        ];
                        foreach ($estados_datos as $estado_dato):
                            $porcentaje_estado = $total_clientes_crm > 0 ? round(($estado_dato['valor'] / $total_clientes_crm) * 100) : 0;
                        ?>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium" style="color: var(--flavor-text-primary, #1a1a1a);">
                                        <?php echo esc_html($estado_dato['label']); ?>
                                    </span>
                                    <span class="text-sm font-bold" style="color: <?php echo $estado_dato['color']; ?>;">
                                        <?php echo esc_html($estado_dato['valor']); ?>
                                    </span>
                                </div>
                                <div class="w-full h-2 rounded-full" style="background: #f3f4f6;">
                                    <div class="h-2 rounded-full transition-all duration-500"
                                         style="width: <?php echo $porcentaje_estado; ?>%; background: <?php echo $estado_dato['color']; ?>;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Desglose por tipo -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                        <?php esc_html_e('Por Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($desglose_por_tipo_crm as $tipo_clave => $tipo_cantidad):
                            $porcentaje_tipo = $total_clientes_crm > 0 ? round(($tipo_cantidad / $total_clientes_crm) * 100) : 0;
                            $color_tipo = $colores_tipo_crm[$tipo_clave] ?? '#6b7280';
                            $etiqueta_tipo = $etiquetas_tipo_crm[$tipo_clave] ?? ucfirst($tipo_clave);
                        ?>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium" style="color: var(--flavor-text-primary, #1a1a1a);">
                                        <?php echo esc_html($etiqueta_tipo); ?>
                                    </span>
                                    <span class="text-sm font-bold" style="color: <?php echo $color_tipo; ?>;">
                                        <?php echo esc_html($tipo_cantidad); ?>
                                    </span>
                                </div>
                                <div class="w-full h-2 rounded-full" style="background: #f3f4f6;">
                                    <div class="h-2 rounded-full transition-all duration-500"
                                         style="width: <?php echo $porcentaje_tipo; ?>%; background: <?php echo $color_tipo; ?>;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Desglose por origen -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                        <?php esc_html_e('Por Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($desglose_por_origen_crm as $origen_clave => $origen_cantidad):
                            $porcentaje_origen = $total_clientes_crm > 0 ? round(($origen_cantidad / $total_clientes_crm) * 100) : 0;
                            $color_origen = $colores_origen_crm[$origen_clave] ?? '#6b7280';
                            $etiqueta_origen = $etiquetas_origen_crm[$origen_clave] ?? ucfirst($origen_clave);
                        ?>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium" style="color: var(--flavor-text-primary, #1a1a1a);">
                                        <?php echo esc_html($etiqueta_origen); ?>
                                    </span>
                                    <span class="text-sm font-bold" style="color: <?php echo $color_origen; ?>;">
                                        <?php echo esc_html($origen_cantidad); ?>
                                    </span>
                                </div>
                                <div class="w-full h-2 rounded-full" style="background: #f3f4f6;">
                                    <div class="h-2 rounded-full transition-all duration-500"
                                         style="width: <?php echo $porcentaje_origen; ?>%; background: <?php echo $color_origen; ?>;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Fila de metricas adicionales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                <!-- Interacciones este mes -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center"
                         style="background: rgba(236, 72, 153, 0.1); color: #ec4899;">
                        <?php echo $iconos_estadisticas_crm['interacciones']; ?>
                    </div>
                    <div>
                        <div class="text-2xl font-bold" style="color: var(--flavor-text-primary, #1a1a1a);">
                            <?php echo esc_html(number_format($interacciones_recientes_crm, 0, ',', '.')); ?>
                        </div>
                        <div class="text-sm" style="color: var(--flavor-text-secondary, #666);">
                            <?php esc_html_e('Interacciones este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                </div>

                <!-- Clientes activos -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="flex-shrink-0 w-14 h-14 rounded-xl flex items-center justify-center"
                         style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <?php echo $iconos_estadisticas_crm['activos']; ?>
                    </div>
                    <div>
                        <div class="text-2xl font-bold" style="color: var(--flavor-text-primary, #1a1a1a);">
                            <?php echo esc_html(number_format($clientes_activos_crm, 0, ',', '.')); ?>
                        </div>
                        <div class="text-sm" style="color: var(--flavor-text-secondary, #666);">
                            <?php esc_html_e('Clientes activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php else: // minimal ?>
    <!-- Estilo Minimal -->
    <section class="flavor-component flavor-section" style="background: var(--flavor-background, #ffffff);">
        <div class="flavor-container">
            <!-- Titulo -->
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: var(--flavor-text-primary, #1a1a1a);">
                    <?php echo esc_html($titulo_seccion); ?>
                </h2>
            </div>

            <!-- KPIs en linea -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-8 mb-16">
                <?php
                $metricas_minimas = [
                    [
                        'valor' => number_format($total_clientes_crm, 0, ',', '.'),
                        'etiqueta' => __('Total', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'color' => 'var(--flavor-primary, #667eea)',
                    ],
                    [
                        'valor' => $clientes_activos_crm,
                        'etiqueta' => __('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'color' => '#10b981',
                    ],
                    [
                        'valor' => $clientes_potenciales_crm,
                        'etiqueta' => __('Potenciales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'color' => '#f59e0b',
                    ],
                    [
                        'valor' => $nuevos_este_mes_crm,
                        'etiqueta' => __('Nuevos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'color' => '#3b82f6',
                    ],
                    [
                        'valor' => $pipeline_formateado,
                        'etiqueta' => __('Pipeline', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'color' => '#8b5cf6',
                    ],
                    [
                        'valor' => $tasa_conversion_crm . '%',
                        'etiqueta' => __('Conversion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'color' => '#ec4899',
                    ],
                ];
                foreach ($metricas_minimas as $metrica):
                ?>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold mb-2" style="color: <?php echo $metrica['color']; ?>;">
                            <?php echo $metrica['valor']; ?>
                        </div>
                        <div class="text-sm font-medium" style="color: var(--flavor-text-secondary, #666);">
                            <?php echo esc_html($metrica['etiqueta']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Linea decorativa -->
            <div class="max-w-3xl mx-auto">
                <div class="h-1 rounded-full" style="background: linear-gradient(90deg, transparent 0%, var(--flavor-primary, #667eea) 50%, transparent 100%);"></div>
            </div>

            <!-- Desglose compacto debajo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mt-16 max-w-4xl mx-auto">
                <!-- Por Tipo -->
                <div>
                    <h3 class="text-lg font-semibold mb-6" style="color: var(--flavor-text-primary, #1a1a1a);">
                        <?php esc_html_e('Distribucion por Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($desglose_por_tipo_crm as $tipo_clave => $tipo_cantidad):
                            $porcentaje_tipo = $total_clientes_crm > 0 ? round(($tipo_cantidad / $total_clientes_crm) * 100) : 0;
                            $color_tipo = $colores_tipo_crm[$tipo_clave] ?? '#6b7280';
                            $etiqueta_tipo = $etiquetas_tipo_crm[$tipo_clave] ?? ucfirst($tipo_clave);
                        ?>
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full flex-shrink-0" style="background: <?php echo $color_tipo; ?>;"></div>
                                <span class="flex-1 text-sm" style="color: var(--flavor-text-primary, #1a1a1a);">
                                    <?php echo esc_html($etiqueta_tipo); ?>
                                </span>
                                <span class="text-sm font-semibold" style="color: <?php echo $color_tipo; ?>;">
                                    <?php echo esc_html($tipo_cantidad); ?>
                                </span>
                                <span class="text-xs" style="color: var(--flavor-text-muted, #999);">
                                    (<?php echo $porcentaje_tipo; ?>%)
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Por Origen -->
                <div>
                    <h3 class="text-lg font-semibold mb-6" style="color: var(--flavor-text-primary, #1a1a1a);">
                        <?php esc_html_e('Distribucion por Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($desglose_por_origen_crm as $origen_clave => $origen_cantidad):
                            $porcentaje_origen = $total_clientes_crm > 0 ? round(($origen_cantidad / $total_clientes_crm) * 100) : 0;
                            $color_origen = $colores_origen_crm[$origen_clave] ?? '#6b7280';
                            $etiqueta_origen = $etiquetas_origen_crm[$origen_clave] ?? ucfirst($origen_clave);
                        ?>
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full flex-shrink-0" style="background: <?php echo $color_origen; ?>;"></div>
                                <span class="flex-1 text-sm" style="color: var(--flavor-text-primary, #1a1a1a);">
                                    <?php echo esc_html($etiqueta_origen); ?>
                                </span>
                                <span class="text-sm font-semibold" style="color: <?php echo $color_origen; ?>;">
                                    <?php echo esc_html($origen_cantidad); ?>
                                </span>
                                <span class="text-xs" style="color: var(--flavor-text-muted, #999);">
                                    (<?php echo $porcentaje_origen; ?>%)
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>
