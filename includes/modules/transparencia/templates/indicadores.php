<?php
/**
 * Template: Dashboard de Indicadores de Gestion
 *
 * Muestra indicadores clave de rendimiento y gestion.
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$prefijo_tabla = $wpdb->prefix . 'flavor_transparencia_';
$tabla_presupuestos = $prefijo_tabla . 'presupuestos';
$tabla_gastos = $prefijo_tabla . 'gastos';
$tabla_solicitudes = $prefijo_tabla . 'solicitudes_info';
$tabla_actas = $prefijo_tabla . 'actas';
$tabla_documentos = $prefijo_tabla . 'documentos_publicos';

$ejercicio_actual = date('Y');
$ejercicio_anterior = $ejercicio_actual - 1;

// Inicializar indicadores
$indicadores = [
    'economicos' => [],
    'transparencia' => [],
    'participacion' => [],
    'eficiencia' => [],
];

// Indicadores economicos
if (Flavor_Chat_Helpers::tabla_existe($tabla_presupuestos)) {
    // Presupuesto total del ejercicio actual
    $presupuesto_ingresos = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(credito_definitivo) FROM $tabla_presupuestos WHERE ejercicio = %d AND tipo = 'ingresos'",
        $ejercicio_actual
    ));

    $presupuesto_gastos = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(credito_definitivo) FROM $tabla_presupuestos WHERE ejercicio = %d AND tipo = 'gastos'",
        $ejercicio_actual
    ));

    // Ejecucion presupuestaria
    $obligaciones = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(obligaciones_reconocidas) FROM $tabla_presupuestos WHERE ejercicio = %d AND tipo = 'gastos'",
        $ejercicio_actual
    ));

    $porcentaje_ejecucion = $presupuesto_gastos > 0 ? round(($obligaciones / $presupuesto_gastos) * 100, 1) : 0;

    $indicadores['economicos'][] = [
        'titulo' => __('Presupuesto Ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => number_format($presupuesto_ingresos ?: 0, 0, ',', '.') . ' EUR',
        'icono' => 'dashicons-chart-line',
        'color' => '#10b981',
        'descripcion' => sprintf(__('Ejercicio %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $ejercicio_actual),
    ];

    $indicadores['economicos'][] = [
        'titulo' => __('Presupuesto Gastos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => number_format($presupuesto_gastos ?: 0, 0, ',', '.') . ' EUR',
        'icono' => 'dashicons-chart-bar',
        'color' => '#3b82f6',
        'descripcion' => sprintf(__('Ejercicio %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $ejercicio_actual),
    ];

    $indicadores['economicos'][] = [
        'titulo' => __('Ejecucion Presupuestaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => $porcentaje_ejecucion . '%',
        'icono' => 'dashicons-performance',
        'color' => $porcentaje_ejecucion >= 75 ? '#10b981' : ($porcentaje_ejecucion >= 50 ? '#f59e0b' : '#ef4444'),
        'descripcion' => __('Obligaciones / Credito definitivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'progreso' => $porcentaje_ejecucion,
    ];
}

// Indicadores de gastos
if (Flavor_Chat_Helpers::tabla_existe($tabla_gastos)) {
    $total_gastos = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(importe_total) FROM $tabla_gastos WHERE ejercicio = %d AND estado_pago != 'anulado'",
        $ejercicio_actual
    ));

    $gastos_pagados = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(importe_total) FROM $tabla_gastos WHERE ejercicio = %d AND estado_pago = 'pagado'",
        $ejercicio_actual
    ));

    $porcentaje_pagado = $total_gastos > 0 ? round(($gastos_pagados / $total_gastos) * 100, 1) : 0;

    $indicadores['economicos'][] = [
        'titulo' => __('Gastos Totales', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => number_format($total_gastos ?: 0, 0, ',', '.') . ' EUR',
        'icono' => 'dashicons-money-alt',
        'color' => '#8b5cf6',
        'descripcion' => sprintf(__('Ejercicio %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $ejercicio_actual),
    ];

    $indicadores['economicos'][] = [
        'titulo' => __('Tasa de Pago', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => $porcentaje_pagado . '%',
        'icono' => 'dashicons-yes-alt',
        'color' => $porcentaje_pagado >= 80 ? '#10b981' : '#f59e0b',
        'descripcion' => __('Pagos realizados / Total gastos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'progreso' => $porcentaje_pagado,
    ];
}

// Indicadores de transparencia
if (Flavor_Chat_Helpers::tabla_existe($tabla_documentos)) {
    $total_documentos = $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_documentos WHERE estado = 'publicado'"
    );

    $documentos_este_anio = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_documentos WHERE estado = 'publicado' AND YEAR(fecha_publicacion) = %d",
        $ejercicio_actual
    ));

    $indicadores['transparencia'][] = [
        'titulo' => __('Documentos Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => number_format($total_documentos ?: 0),
        'icono' => 'dashicons-media-document',
        'color' => '#6366f1',
        'descripcion' => __('Total acumulado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];

    $indicadores['transparencia'][] = [
        'titulo' => __('Publicados Este Ano', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => number_format($documentos_este_anio ?: 0),
        'icono' => 'dashicons-calendar',
        'color' => '#14b8a6',
        'descripcion' => sprintf(__('Ejercicio %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $ejercicio_actual),
    ];
}

if (Flavor_Chat_Helpers::tabla_existe($tabla_actas)) {
    $total_actas = $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_actas WHERE estado IN ('aprobada', 'publicada')"
    );

    $actas_con_video = $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_actas WHERE estado IN ('aprobada', 'publicada') AND video_url IS NOT NULL"
    );

    $indicadores['transparencia'][] = [
        'titulo' => __('Actas Publicadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => number_format($total_actas ?: 0),
        'icono' => 'dashicons-text-page',
        'color' => '#ec4899',
        'descripcion' => __('Total disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];

    $indicadores['transparencia'][] = [
        'titulo' => __('Sesiones con Video', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => number_format($actas_con_video ?: 0),
        'icono' => 'dashicons-video-alt3',
        'color' => '#f97316',
        'descripcion' => __('Retransmisiones disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];
}

// Indicadores de participacion
if (Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
    $total_solicitudes = $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_solicitudes"
    );

    $solicitudes_resueltas = $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'resuelta'"
    );

    $solicitudes_pendientes = $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado IN ('recibida', 'admitida', 'en_tramite')"
    );

    $tasa_respuesta = $total_solicitudes > 0 ? round(($solicitudes_resueltas / $total_solicitudes) * 100, 1) : 0;

    // Tiempo medio de respuesta
    $tiempo_medio = $wpdb->get_var(
        "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_solicitud)) FROM $tabla_solicitudes WHERE estado = 'resuelta' AND fecha_resolucion IS NOT NULL"
    );

    $indicadores['participacion'][] = [
        'titulo' => __('Solicitudes Recibidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => number_format($total_solicitudes ?: 0),
        'icono' => 'dashicons-email',
        'color' => '#3b82f6',
        'descripcion' => __('Total acumulado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];

    $indicadores['participacion'][] = [
        'titulo' => __('Solicitudes Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => number_format($solicitudes_resueltas ?: 0),
        'icono' => 'dashicons-yes-alt',
        'color' => '#10b981',
        'descripcion' => __('Tramitadas completamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];

    $indicadores['participacion'][] = [
        'titulo' => __('Tasa de Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => $tasa_respuesta . '%',
        'icono' => 'dashicons-chart-pie',
        'color' => $tasa_respuesta >= 80 ? '#10b981' : ($tasa_respuesta >= 60 ? '#f59e0b' : '#ef4444'),
        'descripcion' => __('Solicitudes resueltas / Total', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'progreso' => $tasa_respuesta,
    ];

    $indicadores['participacion'][] = [
        'titulo' => __('Pendientes de Tramitar', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'valor' => number_format($solicitudes_pendientes ?: 0),
        'icono' => 'dashicons-clock',
        'color' => $solicitudes_pendientes > 10 ? '#ef4444' : '#f59e0b',
        'descripcion' => __('En proceso actualmente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];

    if ($tiempo_medio) {
        $indicadores['eficiencia'][] = [
            'titulo' => __('Tiempo Medio Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'valor' => round($tiempo_medio) . ' ' . __('dias', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-clock',
            'color' => $tiempo_medio <= 15 ? '#10b981' : ($tiempo_medio <= 30 ? '#f59e0b' : '#ef4444'),
            'descripcion' => __('Desde solicitud hasta resolucion', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }
}

// Categorias de indicadores
$categorias_indicadores = [
    'economicos' => [
        'titulo' => __('Indicadores Economicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-chart-bar',
        'color' => '#3b82f6',
    ],
    'transparencia' => [
        'titulo' => __('Indicadores de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-visibility',
        'color' => '#6366f1',
    ],
    'participacion' => [
        'titulo' => __('Participacion Ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-groups',
        'color' => '#10b981',
    ],
    'eficiencia' => [
        'titulo' => __('Eficiencia y Calidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-performance',
        'color' => '#f59e0b',
    ],
];
?>

<div class="transparencia-indicadores">
    <header class="transparencia-indicadores__header">
        <div class="transparencia-indicadores__titulo">
            <span class="dashicons dashicons-chart-bar"></span>
            <h2><?php esc_html_e('Indicadores de Gestion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        </div>
        <p class="transparencia-indicadores__descripcion">
            <?php esc_html_e('Metricas clave de rendimiento y gestion institucional.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
        <div class="transparencia-indicadores__fecha">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php printf(
                esc_html__('Ultima actualizacion: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                date_i18n('d F Y, H:i')
            ); ?>
        </div>
    </header>

    <?php foreach ($categorias_indicadores as $categoria_clave => $categoria_info) :
        if (empty($indicadores[$categoria_clave])) continue;
    ?>
    <section class="transparencia-indicadores__seccion">
        <header class="transparencia-indicadores__seccion-header" style="--seccion-color: <?php echo esc_attr($categoria_info['color']); ?>">
            <span class="dashicons <?php echo esc_attr($categoria_info['icono']); ?>"></span>
            <h3><?php echo esc_html($categoria_info['titulo']); ?></h3>
        </header>
        <div class="transparencia-indicadores__grid">
            <?php foreach ($indicadores[$categoria_clave] as $indicador) : ?>
            <div class="transparencia-indicador-card" style="--indicador-color: <?php echo esc_attr($indicador['color']); ?>">
                <div class="transparencia-indicador-card__icono">
                    <span class="dashicons <?php echo esc_attr($indicador['icono']); ?>"></span>
                </div>
                <div class="transparencia-indicador-card__contenido">
                    <span class="transparencia-indicador-card__valor"><?php echo esc_html($indicador['valor']); ?></span>
                    <h4 class="transparencia-indicador-card__titulo"><?php echo esc_html($indicador['titulo']); ?></h4>
                    <?php if (!empty($indicador['descripcion'])) : ?>
                    <span class="transparencia-indicador-card__descripcion"><?php echo esc_html($indicador['descripcion']); ?></span>
                    <?php endif; ?>
                    <?php if (isset($indicador['progreso'])) : ?>
                    <div class="transparencia-indicador-card__progreso">
                        <div class="transparencia-barra-progreso">
                            <div class="transparencia-barra-progreso__fill" style="width: <?php echo esc_attr(min(100, $indicador['progreso'])); ?>%; background-color: <?php echo esc_attr($indicador['color']); ?>"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

    <?php if (array_filter($indicadores, fn($cat) => !empty($cat)) === []) : ?>
    <div class="transparencia-empty-state">
        <span class="dashicons dashicons-chart-bar"></span>
        <h3><?php esc_html_e('Sin indicadores disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <p><?php esc_html_e('No hay datos suficientes para mostrar indicadores de gestion.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>

    <!-- Nota informativa -->
    <div class="transparencia-indicadores__nota">
        <span class="dashicons dashicons-info"></span>
        <p>
            <?php esc_html_e('Los indicadores se calculan automaticamente a partir de los datos registrados en el sistema. Los valores pueden variar segun la informacion disponible y su actualizacion.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>
</div>

<style>
.transparencia-indicadores {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.transparencia-indicadores__header {
    margin-bottom: 2rem;
}

.transparencia-indicadores__titulo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.transparencia-indicadores__titulo .dashicons {
    font-size: 1.75rem;
    width: 1.75rem;
    height: 1.75rem;
    color: var(--flavor-primary, #14b8a6);
}

.transparencia-indicadores__titulo h2 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
}

.transparencia-indicadores__descripcion {
    color: var(--flavor-text-light, #6b7280);
    margin: 0 0 0.75rem;
}

.transparencia-indicadores__fecha {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #9ca3af);
}

.transparencia-indicadores__fecha .dashicons {
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

/* Secciones */
.transparencia-indicadores__seccion {
    margin-bottom: 2.5rem;
}

.transparencia-indicadores__seccion-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--seccion-color);
}

.transparencia-indicadores__seccion-header .dashicons {
    font-size: 1.25rem;
    width: 1.25rem;
    height: 1.25rem;
    color: var(--seccion-color);
}

.transparencia-indicadores__seccion-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--flavor-text, #1f2937);
}

/* Grid de indicadores */
.transparencia-indicadores__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.25rem;
}

/* Card de indicador */
.transparencia-indicador-card {
    display: flex;
    gap: 1rem;
    background: var(--flavor-card-bg, #fff);
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid var(--indicador-color);
    transition: transform 0.2s, box-shadow 0.2s;
}

.transparencia-indicador-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.transparencia-indicador-card__icono {
    width: 48px;
    height: 48px;
    background: color-mix(in srgb, var(--indicador-color) 15%, transparent);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.transparencia-indicador-card__icono .dashicons {
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
    color: var(--indicador-color);
}

.transparencia-indicador-card__contenido {
    flex: 1;
    min-width: 0;
}

.transparencia-indicador-card__valor {
    display: block;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-text, #1f2937);
    line-height: 1.2;
}

.transparencia-indicador-card__titulo {
    margin: 0.25rem 0 0;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--flavor-text, #374151);
}

.transparencia-indicador-card__descripcion {
    display: block;
    font-size: 0.8125rem;
    color: var(--flavor-text-light, #6b7280);
    margin-top: 0.25rem;
}

/* Barra de progreso */
.transparencia-indicador-card__progreso {
    margin-top: 0.75rem;
}

.transparencia-barra-progreso {
    height: 6px;
    background: var(--flavor-border, #e5e7eb);
    border-radius: 3px;
    overflow: hidden;
}

.transparencia-barra-progreso__fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

/* Nota informativa */
.transparencia-indicadores__nota {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    background: var(--flavor-bg-light, #f9fafb);
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-top: 2rem;
}

.transparencia-indicadores__nota .dashicons {
    color: var(--flavor-text-light, #6b7280);
    flex-shrink: 0;
}

.transparencia-indicadores__nota p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--flavor-text-light, #6b7280);
    line-height: 1.5;
}

/* Empty state */
.transparencia-empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--flavor-card-bg, #fff);
    border-radius: 12px;
}

.transparencia-empty-state .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    color: var(--flavor-text-light, #9ca3af);
    margin-bottom: 1rem;
}

.transparencia-empty-state h3 {
    margin: 0 0 0.5rem;
    color: var(--flavor-text, #374151);
}

.transparencia-empty-state p {
    color: var(--flavor-text-light, #6b7280);
    margin: 0;
}

@media (max-width: 640px) {
    .transparencia-indicadores__grid {
        grid-template-columns: 1fr;
    }

    .transparencia-indicador-card {
        padding: 1.25rem;
    }

    .transparencia-indicador-card__valor {
        font-size: 1.5rem;
    }
}
</style>
