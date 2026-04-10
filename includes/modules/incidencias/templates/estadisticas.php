<?php
/**
 * Template: Estadísticas de Incidencias (Frontend)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

// Verificar si existe la tabla
if (!Flavor_Platform_Helpers::tabla_existe($tabla_incidencias)) {
    echo '<div class="incidencias-empty"><p>' . esc_html__('El módulo de incidencias no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

// Usar variables pasadas desde el shortcode si existen
$titulo_seccion = isset($atributos['titulo']) ? $atributos['titulo'] : __('Estadísticas de Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN);

// Estadísticas generales
$total_incidencias = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado != 'eliminada'");
$total_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('pendiente', 'pending')");
$total_en_proceso = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('en_proceso', 'in_progress', 'validada')");
$total_resueltas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('resuelta', 'resolved', 'cerrada', 'closed')");

// Tasa de resolución
$tasa_resolucion = $total_incidencias > 0 ? round(($total_resueltas / $total_incidencias) * 100, 1) : 0;

// Tiempo promedio de resolución (en días)
$tiempo_promedio = $wpdb->get_var("
    SELECT ROUND(AVG(DATEDIFF(COALESCE(fecha_resolucion, updated_at), created_at)), 1)
    FROM $tabla_incidencias
    WHERE estado IN ('resuelta', 'resolved', 'cerrada', 'closed')
    AND created_at IS NOT NULL
");
$tiempo_promedio = $tiempo_promedio ?: 0;

// Top 5 categorías más reportadas
$top_categorias = $wpdb->get_results("
    SELECT
        COALESCE(categoria, tipo, 'Sin categoría') as categoria,
        COUNT(*) as total,
        SUM(CASE WHEN estado IN ('resuelta', 'resolved', 'cerrada', 'closed') THEN 1 ELSE 0 END) as resueltas
    FROM $tabla_incidencias
    WHERE estado != 'eliminada'
    GROUP BY COALESCE(categoria, tipo, 'Sin categoría')
    ORDER BY total DESC
    LIMIT 5
");

// Incidencias este mes vs mes anterior
$inicio_mes_actual = date('Y-m-01 00:00:00');
$inicio_mes_anterior = date('Y-m-01 00:00:00', strtotime('-1 month'));
$fin_mes_anterior = date('Y-m-t 23:59:59', strtotime('-1 month'));

$incidencias_este_mes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_incidencias WHERE created_at >= %s AND estado != 'eliminada'",
    $inicio_mes_actual
));

$incidencias_mes_anterior = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_incidencias WHERE created_at BETWEEN %s AND %s AND estado != 'eliminada'",
    $inicio_mes_anterior, $fin_mes_anterior
));

// Cambio porcentual
$cambio_porcentual = $incidencias_mes_anterior > 0
    ? round((($incidencias_este_mes - $incidencias_mes_anterior) / $incidencias_mes_anterior) * 100, 1)
    : ($incidencias_este_mes > 0 ? 100 : 0);
?>

<div class="incidencias-estadisticas-wrapper">
    <div class="estadisticas-header">
        <h2><?php echo esc_html($titulo_seccion); ?></h2>
        <p class="estadisticas-descripcion"><?php esc_html_e('Resumen del estado actual de las incidencias en la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>

    <!-- KPIs principales -->
    <div class="estadisticas-kpis">
        <div class="kpi-card">
            <span class="kpi-icon">📊</span>
            <span class="kpi-value"><?php echo number_format_i18n($total_incidencias); ?></span>
            <span class="kpi-label"><?php esc_html_e('Total Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>

        <div class="kpi-card kpi-warning">
            <span class="kpi-icon">🔴</span>
            <span class="kpi-value"><?php echo number_format_i18n($total_pendientes); ?></span>
            <span class="kpi-label"><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>

        <div class="kpi-card kpi-info">
            <span class="kpi-icon">🟡</span>
            <span class="kpi-value"><?php echo number_format_i18n($total_en_proceso); ?></span>
            <span class="kpi-label"><?php esc_html_e('En Proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>

        <div class="kpi-card kpi-success">
            <span class="kpi-icon">🟢</span>
            <span class="kpi-value"><?php echo number_format_i18n($total_resueltas); ?></span>
            <span class="kpi-label"><?php esc_html_e('Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <!-- Métricas secundarias -->
    <div class="estadisticas-metricas">
        <div class="metrica-card">
            <div class="metrica-header">
                <span class="metrica-titulo"><?php esc_html_e('Tasa de Resolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="metrica-valor"><?php echo esc_html($tasa_resolucion); ?>%</span>
            </div>
            <div class="metrica-barra">
                <div class="metrica-progreso" style="width: <?php echo esc_attr($tasa_resolucion); ?>%"></div>
            </div>
        </div>

        <div class="metrica-card">
            <div class="metrica-header">
                <span class="metrica-titulo"><?php esc_html_e('Tiempo Promedio de Resolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="metrica-valor"><?php echo esc_html($tiempo_promedio); ?> <?php esc_html_e('días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="metrica-card">
            <div class="metrica-header">
                <span class="metrica-titulo"><?php esc_html_e('Incidencias Este Mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="metrica-valor">
                    <?php echo number_format_i18n($incidencias_este_mes); ?>
                    <?php if ($cambio_porcentual != 0): ?>
                        <span class="metrica-cambio <?php echo $cambio_porcentual > 0 ? 'cambio-up' : 'cambio-down'; ?>">
                            <?php echo $cambio_porcentual > 0 ? '+' : ''; ?><?php echo esc_html($cambio_porcentual); ?>%
                        </span>
                    <?php endif; ?>
                </span>
            </div>
            <span class="metrica-subtitulo"><?php printf(esc_html__('vs %d el mes anterior', FLAVOR_PLATFORM_TEXT_DOMAIN), $incidencias_mes_anterior); ?></span>
        </div>
    </div>

    <!-- Top Categorías -->
    <?php if ($top_categorias): ?>
    <div class="estadisticas-categorias">
        <h3><?php esc_html_e('Categorías Más Reportadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <div class="categorias-lista">
            <?php foreach ($top_categorias as $index => $categoria): ?>
                <?php
                $porcentaje_categoria = $total_incidencias > 0 ? round(($categoria->total / $total_incidencias) * 100, 1) : 0;
                $tasa_resolucion_cat = $categoria->total > 0 ? round(($categoria->resueltas / $categoria->total) * 100, 1) : 0;
                ?>
                <div class="categoria-item">
                    <div class="categoria-posicion"><?php echo $index + 1; ?></div>
                    <div class="categoria-info">
                        <span class="categoria-nombre"><?php echo esc_html(ucfirst(str_replace('_', ' ', $categoria->categoria))); ?></span>
                        <div class="categoria-barra-container">
                            <div class="categoria-barra" style="width: <?php echo esc_attr($porcentaje_categoria); ?>%"></div>
                        </div>
                    </div>
                    <div class="categoria-stats">
                        <span class="categoria-total"><?php echo number_format_i18n($categoria->total); ?></span>
                        <span class="categoria-tasa"><?php echo esc_html($tasa_resolucion_cat); ?>% <?php esc_html_e('resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Leyenda de estados -->
    <div class="estadisticas-leyenda">
        <span class="leyenda-item">
            <span class="leyenda-color leyenda-pendiente"></span>
            <?php esc_html_e('Pendiente: Esperando revisión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </span>
        <span class="leyenda-item">
            <span class="leyenda-color leyenda-proceso"></span>
            <?php esc_html_e('En Proceso: Siendo atendida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </span>
        <span class="leyenda-item">
            <span class="leyenda-color leyenda-resuelta"></span>
            <?php esc_html_e('Resuelta: Solucionada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </span>
    </div>
</div>

<style>
.incidencias-estadisticas-wrapper {
    max-width: 900px;
    margin: 0 auto;
}

.estadisticas-header {
    text-align: center;
    margin-bottom: 2rem;
}

.estadisticas-header h2 {
    margin: 0 0 0.5rem;
    font-size: 1.75rem;
    color: #1f2937;
}

.estadisticas-descripcion {
    color: #6b7280;
    margin: 0;
}

/* KPIs */
.estadisticas-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.2s ease;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.kpi-icon {
    display: block;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.kpi-value {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
}

.kpi-label {
    display: block;
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.kpi-warning { border-top: 3px solid #f59e0b; }
.kpi-info { border-top: 3px solid #3b82f6; }
.kpi-success { border-top: 3px solid #10b981; }

/* Métricas */
.estadisticas-metricas {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.metrica-card {
    background: white;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.metrica-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.metrica-titulo {
    font-size: 0.85rem;
    color: #6b7280;
}

.metrica-valor {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.metrica-subtitulo {
    font-size: 0.75rem;
    color: #9ca3af;
}

.metrica-barra {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}

.metrica-progreso {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #34d399);
    border-radius: 3px;
    transition: width 0.5s ease;
}

.metrica-cambio {
    font-size: 0.8rem;
    font-weight: 500;
    padding: 2px 6px;
    border-radius: 4px;
    margin-left: 0.5rem;
}

.cambio-up {
    background: #dcfce7;
    color: #166534;
}

.cambio-down {
    background: #fef2f2;
    color: #991b1b;
}

/* Categorías */
.estadisticas-categorias {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 1.5rem;
}

.estadisticas-categorias h3 {
    margin: 0 0 1rem;
    font-size: 1rem;
    color: #374151;
}

.categorias-lista {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.categoria-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.categoria-posicion {
    width: 28px;
    height: 28px;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    color: #6b7280;
    flex-shrink: 0;
}

.categoria-info {
    flex: 1;
}

.categoria-nombre {
    display: block;
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.categoria-barra-container {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}

.categoria-barra {
    height: 100%;
    background: linear-gradient(90deg, #ef4444, #f87171);
    border-radius: 3px;
}

.categoria-stats {
    text-align: right;
    flex-shrink: 0;
}

.categoria-total {
    display: block;
    font-weight: 600;
    color: #1f2937;
    font-size: 1rem;
}

.categoria-tasa {
    display: block;
    font-size: 0.75rem;
    color: #10b981;
}

/* Leyenda */
.estadisticas-leyenda {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    justify-content: center;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 10px;
    font-size: 0.8rem;
    color: #6b7280;
}

.leyenda-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.leyenda-color {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.leyenda-pendiente { background: #f59e0b; }
.leyenda-proceso { background: #3b82f6; }
.leyenda-resuelta { background: #10b981; }

/* Responsive */
@media (max-width: 768px) {
    .estadisticas-kpis {
        grid-template-columns: repeat(2, 1fr);
    }

    .estadisticas-metricas {
        grid-template-columns: 1fr;
    }

    .estadisticas-leyenda {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
}

@media (max-width: 480px) {
    .estadisticas-kpis {
        grid-template-columns: 1fr;
    }

    .kpi-value {
        font-size: 1.75rem;
    }
}
</style>
