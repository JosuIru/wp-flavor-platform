<?php
/**
 * Ejemplo de Dashboard Mejorado
 *
 * Esta es una plantilla de ejemplo que muestra cómo usar
 * los componentes mejorados para crear dashboards atractivos.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

// Cargar componentes
require_once dirname(__DIR__, 3) . '/dashboard/class-dashboard-components.php';
$DC = 'Flavor_Dashboard_Components';

// Encolar assets
wp_enqueue_style('flavor-dashboard-enhanced', plugins_url('assets/css/dashboard-components-enhanced.css', dirname(__DIR__, 3)), [], '3.3.0');
wp_enqueue_script('flavor-dashboard-components', plugins_url('assets/js/dashboard-components.js', dirname(__DIR__, 3)), ['jquery'], '3.3.0', true);

// Datos de ejemplo (en un módulo real, estos vendrían de la BD)
$stats_data = [
    [
        'value' => '1,234',
        'label' => __('Total Recursos', 'flavor-platform'),
        'icon' => 'dashicons-admin-appearance',
        'color' => 'primary',
        'trend' => 'up',
        'trend_value' => '+12%',
        'meta' => __('vs mes anterior', 'flavor-platform'),
    ],
    [
        'value' => '856',
        'label' => __('Descargas', 'flavor-platform'),
        'icon' => 'dashicons-download',
        'color' => 'success',
        'trend' => 'up',
        'trend_value' => '+23%',
    ],
    [
        'value' => '45',
        'label' => __('En Uso', 'flavor-platform'),
        'icon' => 'dashicons-editor-code',
        'color' => 'info',
    ],
    [
        'value' => '98%',
        'label' => __('Disponibilidad', 'flavor-platform'),
        'icon' => 'dashicons-yes-alt',
        'color' => 'eco',
        'highlight' => true,
    ],
];

$table_data = [
    'title' => __('Recursos Recientes', 'flavor-platform'),
    'icon' => 'dashicons-list-view',
    'columns' => [
        'name' => __('Nombre', 'flavor-platform'),
        'type' => __('Tipo', 'flavor-platform'),
        'size' => __('Tamaño', 'flavor-platform'),
        'downloads' => __('Descargas', 'flavor-platform'),
        'status' => __('Estado', 'flavor-platform'),
    ],
    'data' => [
        [
            'name' => 'admin-common.css',
            'type' => 'CSS',
            'size' => '24 KB',
            'downloads' => '1,245',
            'status' => $DC::badge('Activo', 'success'),
        ],
        [
            'name' => 'utilities.css',
            'type' => 'CSS',
            'size' => '18 KB',
            'downloads' => '856',
            'status' => $DC::badge('Activo', 'success'),
        ],
        [
            'name' => 'helpers.js',
            'type' => 'JavaScript',
            'size' => '32 KB',
            'downloads' => '643',
            'status' => $DC::badge('Activo', 'success'),
        ],
        [
            'name' => 'legacy-bridge.css',
            'type' => 'CSS',
            'size' => '12 KB',
            'downloads' => '234',
            'status' => $DC::badge('Deprecated', 'warning'),
        ],
    ],
    'striped' => true,
    'hoverable' => true,
];
?>

<div class="wrap flavor-dashboard-wrap">

    <!-- Header del dashboard -->
    <div class="dm-dashboard-header">
        <h1 class="dm-dashboard-title">
            <span class="dashicons dashicons-media-code"></span>
            <?php _e('Dashboard de Assets y Recursos', 'flavor-platform'); ?>
        </h1>
        <p class="dm-dashboard-subtitle">
            <?php _e('Gestión centralizada de recursos CSS, JS y plantillas', 'flavor-platform'); ?>
        </p>
    </div>

    <!-- Alerta informativa -->
    <?php echo $DC::alert(
        __('Este dashboard utiliza componentes mejorados con animaciones y mejor UX. Los datos se actualizan automáticamente.', 'flavor-platform'),
        'info',
        true
    ); ?>

    <!-- Grid de estadísticas -->
    <?php echo $DC::stats_grid($stats_data, 4); ?>

    <!-- Secciones -->
    <div class="dm-grid-2">

        <!-- Tabla de recursos -->
        <div>
            <?php echo $DC::data_table($table_data); ?>
        </div>

        <!-- Sección colapsable con progreso -->
        <div>
            <?php
            $progress_content = '';
            $progress_content .= $DC::progress_bar(856, 1000, __('CSS Files', 'flavor-platform'), 'primary');
            $progress_content .= $DC::progress_bar(643, 800, __('JS Files', 'flavor-platform'), 'success');
            $progress_content .= $DC::progress_bar(234, 500, __('Templates', 'flavor-platform'), 'warning');
            $progress_content .= $DC::progress_bar(45, 50, __('Assets en Uso', 'flavor-platform'), 'info');

            echo $DC::section(
                __('Uso de Recursos', 'flavor-platform'),
                $progress_content,
                [
                    'icon' => 'dashicons-chart-bar',
                    'collapsible' => true,
                ]
            );
            ?>

            <!-- Mini chart de actividad -->
            <?php
            $chart_html = '<div class="dm-mb-2">';
            $chart_html .= '<p style="font-size: 13px; color: var(--dm-text-secondary); margin-bottom: 8px;">';
            $chart_html .= __('Descargas de los últimos 7 días', 'flavor-platform');
            $chart_html .= '</p>';
            $chart_html .= $DC::mini_chart([45, 52, 48, 61, 58, 73, 69], 'success');
            $chart_html .= '</div>';

            echo $DC::section(
                __('Actividad Reciente', 'flavor-platform'),
                $chart_html,
                [
                    'icon' => 'dashicons-chart-line',
                ]
            );
            ?>
        </div>
    </div>

    <!-- Estado vacío (ejemplo) -->
    <?php
    /*
    echo $DC::empty_state(
        __('No hay recursos disponibles', 'flavor-platform'),
        'dashicons-admin-media',
        '<a href="#" class="button button-primary">Subir Recursos</a>'
    );
    */
    ?>

    <!-- Sección de ayuda -->
    <?php
    $help_content = '
    <p>' . __('Este dashboard muestra las estadísticas y estado de todos los recursos compartidos del sistema:', 'flavor-platform') . '</p>
    <ul style="list-style: disc; margin-left: 20px;">
        <li>' . __('CSS Común de Administración', 'flavor-platform') . '</li>
        <li>' . __('JavaScript Helpers', 'flavor-platform') . '</li>
        <li>' . __('Templates Compartidas', 'flavor-platform') . '</li>
        <li>' . __('Shortcodes de Utilidad', 'flavor-platform') . '</li>
    </ul>
    ';

    echo $DC::section(
        __('Ayuda', 'flavor-platform'),
        $help_content,
        [
            'icon' => 'dashicons-info-outline',
            'collapsible' => true,
            'collapsed' => true,
        ]
    );
    ?>

</div>

<style>
.flavor-dashboard-wrap {
    max-width: 1400px;
}

.dm-dashboard-header {
    margin-bottom: 32px;
}

.dm-dashboard-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 28px;
    margin: 0 0 8px;
    color: var(--dm-text);
}

.dm-dashboard-title .dashicons {
    color: var(--dm-primary);
}

.dm-dashboard-subtitle {
    font-size: 15px;
    color: var(--dm-text-secondary);
    margin: 0;
}
</style>
