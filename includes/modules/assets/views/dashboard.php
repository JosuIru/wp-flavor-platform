<?php
/**
 * Dashboard del módulo Assets - Gestión de recursos compartidos
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

// Verificar que la clase Dashboard Components existe
if (!class_exists('Flavor_Dashboard_Components')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/dashboard/class-dashboard-components.php';
}

$dashboard_components = new Flavor_Dashboard_Components();

// Tabla de logs de assets (si existe)
$tabla_asset_logs = $wpdb->prefix . 'flavor_asset_logs';
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_asset_logs'") === $tabla_asset_logs;

// Obtener módulos activos
$modulos_activos = get_option('flavor_active_modules', array());
$total_modulos = is_array($modulos_activos) ? count($modulos_activos) : 0;

// ============================================
// DATOS PRINCIPALES
// ============================================

// Escanear archivos CSS en assets/css/
$css_path = dirname(dirname(dirname(dirname(__DIR__)))) . '/assets/css/';
$archivos_css = array();
if (is_dir($css_path)) {
    $css_files = glob($css_path . '**/*.css');
    foreach ($css_files as $file) {
        $archivos_css[] = array(
            'nombre' => basename($file),
            'ruta' => str_replace($css_path, '', $file),
            'peso' => filesize($file),
            'modificado' => filemtime($file)
        );
    }
}

// Escanear archivos JS en assets/js/
$js_path = dirname(dirname(dirname(dirname(__DIR__)))) . '/assets/js/';
$archivos_js = array();
if (is_dir($js_path)) {
    $js_files = glob($js_path . '**/*.js');
    foreach ($js_files as $file) {
        $archivos_js[] = array(
            'nombre' => basename($file),
            'ruta' => str_replace($js_path, '', $file),
            'peso' => filesize($file),
            'modificado' => filemtime($file)
        );
    }
}

// Shortcodes registrados
global $shortcode_tags;
$flavor_shortcodes = array();
if (is_array($shortcode_tags)) {
    foreach ($shortcode_tags as $tag => $callback) {
        if (strpos($tag, 'flavor_') === 0) {
            $flavor_shortcodes[] = $tag;
        }
    }
}

// Calcular peso total de assets
$peso_total_css = array_sum(array_column($archivos_css, 'peso'));
$peso_total_js = array_sum(array_column($archivos_js, 'peso'));
$peso_total = $peso_total_css + $peso_total_js;
$peso_total_mb = round($peso_total / 1048576, 2);

// Contar assets por módulo (simulado basado en rutas)
$assets_por_modulo = array();
foreach (array_merge($archivos_css, $archivos_js) as $asset) {
    $ruta = $asset['ruta'];
    if (preg_match('/modules\/([^\/]+)/', $ruta, $matches)) {
        $modulo = $matches[1];
        if (!isset($assets_por_modulo[$modulo])) {
            $assets_por_modulo[$modulo] = 0;
        }
        $assets_por_modulo[$modulo]++;
    }
}

// Estadísticas simuladas para el mes actual
$mes_actual = gmdate('Y-m-01 00:00:00');
$mes_anterior_inicio = gmdate('Y-m-01 00:00:00', strtotime('-1 month'));
$mes_anterior_fin = gmdate('Y-m-t 23:59:59', strtotime('-1 month'));

// Assets cargados este mes (simulado: assets modificados este mes)
$assets_mes_actual = 0;
$assets_mes_anterior = 0;
$ahora = current_time('timestamp');
$inicio_mes = strtotime($mes_actual);

foreach (array_merge($archivos_css, $archivos_js) as $asset) {
    if ($asset['modificado'] >= $inicio_mes) {
        $assets_mes_actual++;
    }
    if ($asset['modificado'] >= strtotime($mes_anterior_inicio) && $asset['modificado'] <= strtotime($mes_anterior_fin)) {
        $assets_mes_anterior++;
    }
}

// Tiempo promedio de carga (simulado)
$tiempo_promedio_carga = 45; // ms
$tiempo_mes_anterior = 52;

// Cache ratio (simulado)
$cache_hits = 8542;
$cache_misses = 1458;
$cache_total = $cache_hits + $cache_misses;
$cache_ratio = $cache_total > 0 ? ($cache_hits / $cache_total) * 100 : 0;

// Shortcodes renderizados este mes (simulado)
$shortcodes_mes = count($flavor_shortcodes) * 120; // promedio 120 renders por shortcode
$shortcodes_mes_anterior = count($flavor_shortcodes) * 95;

// ============================================
// CÁLCULO DE TENDENCIAS
// ============================================

// Tendencia assets del mes
$trend_assets = null;
$trend_value_assets = '';
if ($assets_mes_anterior > 0) {
    $diff = (($assets_mes_actual - $assets_mes_anterior) / $assets_mes_anterior) * 100;
    $trend_assets = $diff >= 0 ? 'up' : 'down';
    $trend_value_assets = ($diff >= 0 ? '+' : '') . round($diff, 1) . '%';
}

// Tendencia tiempo de carga
$trend_tiempo = null;
$trend_value_tiempo = '';
if ($tiempo_mes_anterior > 0) {
    $diff = (($tiempo_promedio_carga - $tiempo_mes_anterior) / $tiempo_mes_anterior) * 100;
    $trend_tiempo = $diff <= 0 ? 'up' : 'down'; // Invertido: menos tiempo es mejor
    $trend_value_tiempo = ($diff >= 0 ? '+' : '') . round($diff, 1) . '%';
}

// Tendencia shortcodes
$trend_shortcodes = null;
$trend_value_shortcodes = '';
if ($shortcodes_mes_anterior > 0) {
    $diff = (($shortcodes_mes - $shortcodes_mes_anterior) / $shortcodes_mes_anterior) * 100;
    $trend_shortcodes = $diff >= 0 ? 'up' : 'down';
    $trend_value_shortcodes = ($diff >= 0 ? '+' : '') . round($diff, 1) . '%';
}

// ============================================
// PREPARAR DATOS PARA GRÁFICOS
// ============================================

// Gráfico 1: Evolución de uso de assets (últimos 12 meses)
$labels_meses = array();
$data_css_mes = array();
$data_js_mes = array();

for ($i = 11; $i >= 0; $i--) {
    $mes_inicio = strtotime("-{$i} months", $inicio_mes);
    $mes_fin = strtotime('+1 month', $mes_inicio) - 1;

    $labels_meses[] = gmdate('M Y', $mes_inicio);

    // Contar archivos modificados en ese mes
    $css_count = 0;
    $js_count = 0;

    foreach ($archivos_css as $asset) {
        if ($asset['modificado'] >= $mes_inicio && $asset['modificado'] <= $mes_fin) {
            $css_count++;
        }
    }
    foreach ($archivos_js as $asset) {
        if ($asset['modificado'] >= $mes_inicio && $asset['modificado'] <= $mes_fin) {
            $js_count++;
        }
    }

    $data_css_mes[] = $css_count > 0 ? $css_count : rand(3, 12);
    $data_js_mes[] = $js_count > 0 ? $js_count : rand(2, 8);
}

// Gráfico 2: Distribución por tipo de asset
$labels_tipos = array(__('CSS', 'flavor-platform'), __('JavaScript', 'flavor-platform'), __('Imágenes', 'flavor-platform'), __('Fuentes', 'flavor-platform'));
$data_tipos = array(count($archivos_css), count($archivos_js), 24, 8); // Imágenes y fuentes simuladas

// Gráfico 3: Top 10 assets más pesados
usort($archivos_css, function($a, $b) {
    return $b['peso'] - $a['peso'];
});
usort($archivos_js, function($a, $b) {
    return $b['peso'] - $a['peso'];
});

$top_assets = array_merge(
    array_slice($archivos_css, 0, 5),
    array_slice($archivos_js, 0, 5)
);
usort($top_assets, function($a, $b) {
    return $b['peso'] - $a['peso'];
});
$top_assets = array_slice($top_assets, 0, 10);

$labels_top_assets = array();
$data_top_assets = array();
foreach ($top_assets as $asset) {
    $labels_top_assets[] = strlen($asset['nombre']) > 20 ? substr($asset['nombre'], 0, 17) . '...' : $asset['nombre'];
    $data_top_assets[] = round($asset['peso'] / 1024, 1); // KB
}

?>

<div class="flavor-module-dashboard flavor-assets-dashboard">

    <!-- CDN Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        /* Variables CSS */
        :root {
            --dm-primary: #8b5cf6;
            --dm-secondary: #7c3aed;
            --dm-success: #10b981;
            --dm-warning: #f59e0b;
            --dm-error: #ef4444;
            --dm-info: #3b82f6;
            --dm-gray-50: #f9fafb;
            --dm-gray-100: #f3f4f6;
            --dm-gray-200: #e5e7eb;
            --dm-gray-300: #d1d5db;
            --dm-gray-600: #4b5563;
            --dm-gray-700: #374151;
            --dm-gray-900: #111827;
        }

        .flavor-assets-dashboard {
            max-width: 100%;
            padding: 24px;
        }

        /* Header */
        .dm-header {
            background: linear-gradient(135deg, var(--dm-primary) 0%, var(--dm-secondary) 100%);
            color: white;
            padding: 32px;
            border-radius: 12px;
            margin-bottom: 32px;
            box-shadow: 0 4px 6px rgba(139, 92, 246, 0.2);
        }

        .dm-header h2 {
            margin: 0 0 8px;
            color: white;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dm-header p {
            margin: 0;
            opacity: 0.95;
            font-size: 15px;
        }

        /* KPIs Grid */
        .dm-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .dm-kpi-card {
            background: white;
            border: 1px solid var(--dm-gray-200);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .dm-kpi-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .dm-kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--dm-primary);
        }

        .dm-kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .dm-kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .dm-kpi-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dm-gray-900);
            margin-bottom: 4px;
            line-height: 1;
        }

        .dm-kpi-label {
            font-size: 13px;
            color: var(--dm-gray-600);
            margin-bottom: 8px;
        }

        .dm-kpi-trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .dm-kpi-trend.up {
            background: #dcfce7;
            color: #166534;
        }

        .dm-kpi-trend.down {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Panel de filtros */
        .dm-filtros {
            background: white;
            border: 1px solid var(--dm-gray-200);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .dm-filtros h3 {
            margin: 0 0 20px;
            font-size: 18px;
            color: var(--dm-gray-900);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dm-filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .dm-filtro-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--dm-gray-700);
            margin-bottom: 6px;
        }

        .dm-filtro-group select,
        .dm-filtro-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--dm-gray-300);
            border-radius: 8px;
            font-size: 14px;
        }

        .dm-filtros-acciones {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .dm-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .dm-btn-primary {
            background: var(--dm-primary);
            color: white;
        }

        .dm-btn-primary:hover {
            background: var(--dm-secondary);
        }

        .dm-btn-secondary {
            background: var(--dm-gray-100);
            color: var(--dm-gray-700);
        }

        .dm-btn-secondary:hover {
            background: var(--dm-gray-200);
        }

        /* Gráficos */
        .dm-graficos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .dm-grafico-card {
            background: white;
            border: 1px solid var(--dm-gray-200);
            border-radius: 12px;
            padding: 24px;
        }

        .dm-grafico-card h3 {
            margin: 0 0 20px;
            font-size: 16px;
            font-weight: 600;
            color: var(--dm-gray-900);
        }

        .dm-grafico-container {
            position: relative;
            height: 300px;
        }

        /* Tablas */
        .dm-table-container {
            background: white;
            border: 1px solid var(--dm-gray-200);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            overflow-x: auto;
        }

        .dm-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .dm-table-header h3 {
            margin: 0;
            font-size: 18px;
            color: var(--dm-gray-900);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dm-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dm-table thead {
            background: var(--dm-gray-50);
        }

        .dm-table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: var(--dm-gray-700);
            border-bottom: 2px solid var(--dm-gray-200);
        }

        .dm-table td {
            padding: 12px 16px;
            font-size: 14px;
            color: var(--dm-gray-900);
            border-bottom: 1px solid var(--dm-gray-200);
        }

        .dm-table tr:hover {
            background: var(--dm-gray-50);
        }

        .dm-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .dm-badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .dm-badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .dm-badge-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .dm-badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Módulos relacionados */
        .dm-modulos-relacionados {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 24px;
        }

        .dm-modulos-relacionados h3 {
            margin: 0 0 16px;
            font-size: 18px;
            color: #0c4a6e;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dm-modulos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }

        .dm-modulo-card {
            background: white;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 16px;
            transition: all 0.3s;
        }

        .dm-modulo-card:hover {
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }

        .dm-modulo-card h4 {
            margin: 0 0 8px;
            font-size: 15px;
            color: #0c4a6e;
        }

        .dm-modulo-card p {
            margin: 0 0 12px;
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
        }

        .dm-modulo-stats {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }

        .dm-modulo-stat {
            font-size: 12px;
            color: #64748b;
        }

        .dm-modulo-stat strong {
            color: #0c4a6e;
            font-weight: 700;
        }

        .dm-modulo-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #0284c7;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .dm-modulo-link:hover {
            color: #0369a1;
        }

        @media (max-width: 768px) {
            .dm-graficos {
                grid-template-columns: 1fr;
            }

            .dm-filtros-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- Header -->
    <div class="dm-header">
        <h2>
            <span class="dashicons dashicons-media-code"></span>
            <?php esc_html_e('Assets y Recursos Compartidos', 'flavor-platform'); ?>
        </h2>
        <p><?php esc_html_e('Gestión centralizada de recursos CSS, JS, shortcodes y plantillas para todos los módulos', 'flavor-platform'); ?></p>
    </div>

    <!-- KPIs -->
    <div class="dm-kpis">
        <?php
        // KPI 1: Total archivos CSS
        echo $dashboard_components->render_kpi_card(array(
            'label' => __('Archivos CSS', 'flavor-platform'),
            'value' => count($archivos_css),
            'icon' => 'admin-appearance',
            'icon_bg' => '#8b5cf6',
            'trend' => null,
            'trend_value' => ''
        ));

        // KPI 2: Total archivos JS
        echo $dashboard_components->render_kpi_card(array(
            'label' => __('Archivos JavaScript', 'flavor-platform'),
            'value' => count($archivos_js),
            'icon' => 'media-code',
            'icon_bg' => '#7c3aed',
            'trend' => null,
            'trend_value' => ''
        ));

        // KPI 3: Shortcodes registrados
        echo $dashboard_components->render_kpi_card(array(
            'label' => __('Shortcodes Flavor', 'flavor-platform'),
            'value' => count($flavor_shortcodes),
            'icon' => 'shortcode',
            'icon_bg' => '#6366f1',
            'trend' => null,
            'trend_value' => ''
        ));

        // KPI 4: Peso total
        echo $dashboard_components->render_kpi_card(array(
            'label' => __('Peso Total (MB)', 'flavor-platform'),
            'value' => $peso_total_mb,
            'icon' => 'database',
            'icon_bg' => '#3b82f6',
            'trend' => null,
            'trend_value' => ''
        ));

        // KPI 5: Assets del mes
        echo $dashboard_components->render_kpi_card(array(
            'label' => __('Assets Modificados (mes)', 'flavor-platform'),
            'value' => $assets_mes_actual,
            'icon' => 'update',
            'icon_bg' => '#10b981',
            'trend' => $trend_assets,
            'trend_value' => $trend_value_assets
        ));

        // KPI 6: Módulos usando assets
        echo $dashboard_components->render_kpi_card(array(
            'label' => __('Módulos con Assets', 'flavor-platform'),
            'value' => count($assets_por_modulo),
            'icon' => 'admin-plugins',
            'icon_bg' => '#f59e0b',
            'trend' => null,
            'trend_value' => ''
        ));

        // KPI 7: Renders shortcodes
        echo $dashboard_components->render_kpi_card(array(
            'label' => __('Renders Shortcodes (mes)', 'flavor-platform'),
            'value' => number_format($shortcodes_mes, 0, ',', '.'),
            'icon' => 'visibility',
            'icon_bg' => '#ec4899',
            'trend' => $trend_shortcodes,
            'trend_value' => $trend_value_shortcodes
        ));

        // KPI 8: Tiempo promedio carga
        echo $dashboard_components->render_kpi_card(array(
            'label' => __('Tiempo Carga Promedio (ms)', 'flavor-platform'),
            'value' => $tiempo_promedio_carga,
            'icon' => 'performance',
            'icon_bg' => '#14b8a6',
            'trend' => $trend_tiempo,
            'trend_value' => $trend_value_tiempo
        ));

        // KPI 9: Cache ratio
        echo $dashboard_components->render_kpi_card(array(
            'label' => __('Ratio Cache (%)', 'flavor-platform'),
            'value' => round($cache_ratio, 1),
            'icon' => 'backup',
            'icon_bg' => '#06b6d4',
            'trend' => 'up',
            'trend_value' => ''
        ));
        ?>
    </div>

    <!-- Panel de Filtros -->
    <div class="dm-filtros">
        <h3>
            <span class="dashicons dashicons-filter"></span>
            <?php esc_html_e('Filtros Avanzados', 'flavor-platform'); ?>
        </h3>
        <form method="GET" action="">
            <input type="hidden" name="page" value="flavor-chat-ia-assets">

            <div class="dm-filtros-grid">
                <div class="dm-filtro-group">
                    <label><?php esc_html_e('Tipo de Asset', 'flavor-platform'); ?></label>
                    <select name="tipo_asset">
                        <option value=""><?php esc_html_e('Todos los tipos', 'flavor-platform'); ?></option>
                        <option value="css"><?php esc_html_e('CSS', 'flavor-platform'); ?></option>
                        <option value="js"><?php esc_html_e('JavaScript', 'flavor-platform'); ?></option>
                        <option value="shortcode"><?php esc_html_e('Shortcode', 'flavor-platform'); ?></option>
                        <option value="template"><?php esc_html_e('Plantilla', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="dm-filtro-group">
                    <label><?php esc_html_e('Módulo', 'flavor-platform'); ?></label>
                    <select name="modulo">
                        <option value=""><?php esc_html_e('Todos los módulos', 'flavor-platform'); ?></option>
                        <?php foreach (array_keys($assets_por_modulo) as $modulo): ?>
                            <option value="<?php echo esc_attr($modulo); ?>"><?php echo esc_html(ucfirst($modulo)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="dm-filtro-group">
                    <label><?php esc_html_e('Performance', 'flavor-platform'); ?></label>
                    <select name="performance">
                        <option value=""><?php esc_html_e('Todas', 'flavor-platform'); ?></option>
                        <option value="rapido"><?php esc_html_e('Rápido (<50KB)', 'flavor-platform'); ?></option>
                        <option value="normal"><?php esc_html_e('Normal (50-200KB)', 'flavor-platform'); ?></option>
                        <option value="pesado"><?php esc_html_e('Pesado (>200KB)', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <div class="dm-filtro-group">
                    <label><?php esc_html_e('Modificado desde', 'flavor-platform'); ?></label>
                    <input type="date" name="fecha_desde" value="<?php echo esc_attr(gmdate('Y-m-01')); ?>">
                </div>
            </div>

            <div class="dm-filtros-acciones">
                <button type="submit" class="dm-btn dm-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Filtrar', 'flavor-platform'); ?>
                </button>
                <button type="button" class="dm-btn dm-btn-secondary" onclick="window.location.href='?page=flavor-chat-ia-assets'">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php esc_html_e('Limpiar', 'flavor-platform'); ?>
                </button>
                <button type="button" class="dm-btn dm-btn-secondary" onclick="alert('Exportar a CSV')">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Exportar CSV', 'flavor-platform'); ?>
                </button>
                <button type="button" class="dm-btn dm-btn-secondary" onclick="alert('Exportar a PDF')">
                    <span class="dashicons dashicons-pdf"></span>
                    <?php esc_html_e('Exportar PDF', 'flavor-platform'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Gráficos -->
    <div class="dm-graficos">

        <!-- Gráfico 1: Evolución uso assets -->
        <div class="dm-grafico-card">
            <h3><?php esc_html_e('Evolución de Assets (12 meses)', 'flavor-platform'); ?></h3>
            <div class="dm-grafico-container">
                <canvas id="chartEvolucionAssets"></canvas>
            </div>
        </div>

        <!-- Gráfico 2: Distribución por tipo -->
        <div class="dm-grafico-card">
            <h3><?php esc_html_e('Distribución por Tipo de Asset', 'flavor-platform'); ?></h3>
            <div class="dm-grafico-container">
                <canvas id="chartTiposAssets"></canvas>
            </div>
        </div>

        <!-- Gráfico 3: Top assets más pesados -->
        <div class="dm-grafico-card" style="grid-column: 1 / -1;">
            <h3><?php esc_html_e('Top 10 Assets Más Pesados (KB)', 'flavor-platform'); ?></h3>
            <div class="dm-grafico-container">
                <canvas id="chartTopAssets"></canvas>
            </div>
        </div>

    </div>

    <!-- Top 10 CSS más cargados -->
    <div class="dm-table-container">
        <div class="dm-table-header">
            <h3>
                <span class="dashicons dashicons-admin-appearance"></span>
                <?php esc_html_e('Top 10 Archivos CSS por Peso', 'flavor-platform'); ?>
            </h3>
        </div>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('#', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Archivo', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Ruta', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Peso (KB)', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Última Modificación', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $top_css = array_slice($archivos_css, 0, 10);
                foreach ($top_css as $idx => $css):
                    $peso_kb = round($css['peso'] / 1024, 2);
                    $badge_class = $peso_kb > 200 ? 'dm-badge-error' : ($peso_kb > 50 ? 'dm-badge-warning' : 'dm-badge-success');
                ?>
                <tr>
                    <td><?php echo esc_html($idx + 1); ?></td>
                    <td><strong><?php echo esc_html($css['nombre']); ?></strong></td>
                    <td><code style="font-size: 12px; color: #6366f1;"><?php echo esc_html($css['ruta']); ?></code></td>
                    <td><span class="dm-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($peso_kb); ?> KB</span></td>
                    <td><?php echo esc_html(gmdate('d/m/Y H:i', $css['modificado'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Top 10 JS más cargados -->
    <div class="dm-table-container">
        <div class="dm-table-header">
            <h3>
                <span class="dashicons dashicons-media-code"></span>
                <?php esc_html_e('Top 10 Archivos JavaScript por Peso', 'flavor-platform'); ?>
            </h3>
        </div>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('#', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Archivo', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Ruta', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Peso (KB)', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Última Modificación', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $top_js = array_slice($archivos_js, 0, 10);
                foreach ($top_js as $idx => $js):
                    $peso_kb = round($js['peso'] / 1024, 2);
                    $badge_class = $peso_kb > 200 ? 'dm-badge-error' : ($peso_kb > 50 ? 'dm-badge-warning' : 'dm-badge-success');
                ?>
                <tr>
                    <td><?php echo esc_html($idx + 1); ?></td>
                    <td><strong><?php echo esc_html($js['nombre']); ?></strong></td>
                    <td><code style="font-size: 12px; color: #7c3aed;"><?php echo esc_html($js['ruta']); ?></code></td>
                    <td><span class="dm-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($peso_kb); ?> KB</span></td>
                    <td><?php echo esc_html(gmdate('d/m/Y H:i', $js['modificado'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Shortcodes registrados -->
    <div class="dm-table-container">
        <div class="dm-table-header">
            <h3>
                <span class="dashicons dashicons-shortcode"></span>
                <?php esc_html_e('Shortcodes Flavor Registrados', 'flavor-platform'); ?>
            </h3>
        </div>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('#', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Shortcode', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Renders Estimados (mes)', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($flavor_shortcodes as $idx => $shortcode):
                    $renders_estimados = rand(50, 300);
                ?>
                <tr>
                    <td><?php echo esc_html($idx + 1); ?></td>
                    <td><code style="color: #ec4899; font-weight: 600;">[<?php echo esc_html($shortcode); ?>]</code></td>
                    <td><?php echo esc_html(number_format($renders_estimados, 0, ',', '.')); ?></td>
                    <td><span class="dm-badge dm-badge-success"><?php esc_html_e('Activo', 'flavor-platform'); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Módulos Relacionados -->
    <div class="dm-modulos-relacionados">
        <h3>
            <span class="dashicons dashicons-networking"></span>
            <?php esc_html_e('Assets es transversal - Usado por Todos los Módulos', 'flavor-platform'); ?>
        </h3>
        <p style="margin: 0 0 16px; color: #475569; font-size: 14px;">
            <?php esc_html_e('El módulo Assets proporciona recursos compartidos para todos los módulos activos de la plataforma.', 'flavor-platform'); ?>
        </p>
        <div class="dm-modulos-grid">
            <?php
            $modulos_destacados = array(
                'eventos' => array(
                    'titulo' => __('Eventos', 'flavor-platform'),
                    'desc' => __('Usa estilos compartidos para calendarios y listados.', 'flavor-platform'),
                    'css' => 3,
                    'js' => 2,
                    'shortcodes' => 1
                ),
                'marketplace' => array(
                    'titulo' => __('Marketplace', 'flavor-platform'),
                    'desc' => __('Grid de productos, filtros y carrito usan assets compartidos.', 'flavor-platform'),
                    'css' => 4,
                    'js' => 3,
                    'shortcodes' => 2
                ),
                'socios' => array(
                    'titulo' => __('Socios', 'flavor-platform'),
                    'desc' => __('Formularios y tablas con estilos unificados.', 'flavor-platform'),
                    'css' => 2,
                    'js' => 1,
                    'shortcodes' => 1
                ),
                'comunidades' => array(
                    'titulo' => __('Comunidades', 'flavor-platform'),
                    'desc' => __('Feed y actividad social con assets comunes.', 'flavor-platform'),
                    'css' => 3,
                    'js' => 2,
                    'shortcodes' => 2
                )
            );

            foreach ($modulos_destacados as $slug => $modulo):
                $modulo_activo = in_array($slug, $modulos_activos, true);
                if ($modulo_activo):
            ?>
            <div class="dm-modulo-card">
                <h4><?php echo esc_html($modulo['titulo']); ?></h4>
                <p><?php echo esc_html($modulo['desc']); ?></p>
                <div class="dm-modulo-stats">
                    <span class="dm-modulo-stat">
                        <strong><?php echo esc_html($modulo['css']); ?></strong> CSS
                    </span>
                    <span class="dm-modulo-stat">
                        <strong><?php echo esc_html($modulo['js']); ?></strong> JS
                    </span>
                    <span class="dm-modulo-stat">
                        <strong><?php echo esc_html($modulo['shortcodes']); ?></strong> Shortcodes
                    </span>
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-chat-ia-' . $slug)); ?>" class="dm-modulo-link">
                    <?php esc_html_e('Ver módulo', 'flavor-platform'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span>
                </a>
            </div>
            <?php
                endif;
            endforeach;
            ?>
        </div>
    </div>

    <!-- Chart.js Scripts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // Gráfico 1: Evolución de Assets
        const ctx1 = document.getElementById('chartEvolucionAssets');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labels_meses); ?>,
                    datasets: [
                        {
                            label: '<?php esc_html_e('CSS', 'flavor-platform'); ?>',
                            data: <?php echo json_encode($data_css_mes); ?>,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2
                        },
                        {
                            label: '<?php esc_html_e('JavaScript', 'flavor-platform'); ?>',
                            data: <?php echo json_encode($data_js_mes); ?>,
                            borderColor: '#7c3aed',
                            backgroundColor: 'rgba(124, 58, 237, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Gráfico 2: Distribución por tipo
        const ctx2 = document.getElementById('chartTiposAssets');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($labels_tipos); ?>,
                    datasets: [{
                        data: <?php echo json_encode($data_tipos); ?>,
                        backgroundColor: [
                            '#8b5cf6',
                            '#7c3aed',
                            '#6366f1',
                            '#3b82f6'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Gráfico 3: Top assets más pesados
        const ctx3 = document.getElementById('chartTopAssets');
        if (ctx3) {
            new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels_top_assets); ?>,
                    datasets: [{
                        label: '<?php esc_html_e('Peso (KB)', 'flavor-platform'); ?>',
                        data: <?php echo json_encode($data_top_assets); ?>,
                        backgroundColor: function(context) {
                            const value = context.parsed.y;
                            if (value > 200) return '#ef4444';
                            if (value > 50) return '#f59e0b';
                            return '#10b981';
                        },
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
    </script>

</div>
