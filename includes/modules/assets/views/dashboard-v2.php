<?php
/**
 * Dashboard del módulo Assets - Versión Mejorada
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

// Cargar componentes
require_once dirname(__DIR__, 3) . '/dashboard/class-dashboard-components.php';
$DC = 'Flavor_Dashboard_Components';

// Encolar assets mejorados
wp_enqueue_style('flavor-dashboard-enhanced', plugins_url('assets/css/dashboard-components-enhanced.css', dirname(__DIR__, 3)), [], '3.3.0');
wp_enqueue_script('flavor-dashboard-components', plugins_url('assets/js/dashboard-components.js', dirname(__DIR__, 3)), ['jquery'], '3.3.0', true);

// Datos reales del sistema
global $wpdb;

// Contar archivos reales
$plugin_path = dirname(__DIR__, 3);
$css_count = count(glob($plugin_path . '/assets/css/**/*.css'));
$js_count = count(glob($plugin_path . '/assets/js/**/*.js'));
$modules_count = count(glob($plugin_path . '/includes/modules/*/'));

// Estadísticas
$stats = [
    [
        'value' => $css_count,
        'label' => __('Archivos CSS', 'flavor-platform'),
        'icon' => 'dashicons-admin-appearance',
        'color' => 'primary',
        'meta' => __('Estilos compartidos', 'flavor-platform'),
    ],
    [
        'value' => $js_count,
        'label' => __('Archivos JS', 'flavor-platform'),
        'icon' => 'dashicons-media-code',
        'color' => 'success',
        'meta' => __('Scripts helpers', 'flavor-platform'),
    ],
    [
        'value' => '2',
        'label' => __('Shortcodes', 'flavor-platform'),
        'icon' => 'dashicons-shortcode',
        'color' => 'info',
        'meta' => __('Utilidades disponibles', 'flavor-platform'),
    ],
    [
        'value' => $modules_count,
        'label' => __('Módulos Soportados', 'flavor-platform'),
        'icon' => 'dashicons-admin-plugins',
        'color' => 'eco',
        'highlight' => true,
    ],
];

// Recursos principales
$recursos_table = [
    'title' => __('Recursos Principales', 'flavor-platform'),
    'icon' => 'dashicons-editor-code',
    'columns' => [
        'resource' => __('Recurso', 'flavor-platform'),
        'type' => __('Tipo', 'flavor-platform'),
        'description' => __('Descripción', 'flavor-platform'),
        'status' => __('Estado', 'flavor-platform'),
    ],
    'data' => [
        [
            'resource' => '<strong>flavor-admin-common</strong>',
            'type' => 'CSS',
            'description' => __('Estilos comunes para admin', 'flavor-platform'),
            'status' => $DC::badge('Activo', 'success'),
        ],
        [
            'resource' => '<strong>flavor-utilities</strong>',
            'type' => 'CSS',
            'description' => __('Clases de utilidad frontend', 'flavor-platform'),
            'status' => $DC::badge('Registrado', 'info'),
        ],
        [
            'resource' => '<strong>flavor-helpers</strong>',
            'type' => 'JS',
            'description' => __('Funciones JavaScript comunes', 'flavor-platform'),
            'status' => $DC::badge('Registrado', 'info'),
        ],
        [
            'resource' => '<strong>dashboard-components-enhanced</strong>',
            'type' => 'CSS',
            'description' => __('Componentes visuales mejorados', 'flavor-platform'),
            'status' => $DC::badge('Nuevo', 'success'),
        ],
    ],
    'striped' => true,
    'hoverable' => true,
];

// Shortcodes disponibles
$shortcodes_table = [
    'title' => __('Shortcodes Disponibles', 'flavor-platform'),
    'icon' => 'dashicons-shortcode',
    'columns' => [
        'shortcode' => __('Shortcode', 'flavor-platform'),
        'description' => __('Descripción', 'flavor-platform'),
        'example' => __('Ejemplo de Uso', 'flavor-platform'),
    ],
    'data' => [
        [
            'shortcode' => '<code>[flavor_icon]</code>',
            'description' => __('Renderiza iconos dashicons personalizables', 'flavor-platform'),
            'example' => '<code>[flavor_icon icon="dashicons-star" color="#f59e0b" size="20"]</code>',
        ],
        [
            'shortcode' => '<code>[flavor_badge]</code>',
            'description' => __('Crea badges coloridos', 'flavor-platform'),
            'example' => '<code>[flavor_badge text="Nuevo" color="green"]</code>',
        ],
    ],
    'compact' => true,
];
?>

<div class="wrap flavor-dashboard-wrap">

    <!-- Header -->
    <div class="dm-dashboard-header">
        <h1 class="dm-dashboard-title">
            <span class="dashicons dashicons-media-code"></span>
            <?php _e('Assets y Recursos Compartidos', 'flavor-platform'); ?>
        </h1>
        <p class="dm-dashboard-subtitle">
            <?php _e('Sistema centralizado de recursos CSS, JS y plantillas para todos los módulos', 'flavor-platform'); ?>
        </p>
    </div>

    <!-- Alerta de bienvenida -->
    <?php echo $DC::alert(
        sprintf(
            __('Este dashboard utiliza <strong>componentes visuales mejorados</strong> para una mejor experiencia. Los datos se actualizan automáticamente. %s', 'flavor-platform'),
            '<a href="?page=flavor-modules-assets&tab=example" style="color: inherit; text-decoration: underline;">Ver ejemplo completo</a>'
        ),
        'info',
        true
    ); ?>

    <!-- Grid de estadísticas -->
    <?php echo $DC::stats_grid($stats, 4); ?>

    <!-- Secciones principales -->
    <div class="dm-grid-2">

        <!-- Tabla de recursos principales -->
        <div>
            <?php echo $DC::data_table($recursos_table); ?>

            <!-- Progreso de assets -->
            <?php
            $progress_html = '';
            $progress_html .= $DC::progress_bar($css_count, 100, __('CSS Files Coverage', 'flavor-platform'), 'primary');
            $progress_html .= $DC::progress_bar($js_count, 50, __('JS Helpers Coverage', 'flavor-platform'), 'success');
            $progress_html .= $DC::progress_bar($modules_count, 70, __('Module Support', 'flavor-platform'), 'info');

            echo $DC::section(
                __('Cobertura del Sistema', 'flavor-platform'),
                $progress_html,
                [
                    'icon' => 'dashicons-chart-bar',
                    'collapsible' => true,
                ]
            );
            ?>
        </div>

        <!-- Shortcodes y ayuda -->
        <div>
            <?php echo $DC::data_table($shortcodes_table); ?>

            <!-- Componentes disponibles -->
            <?php
            $components_html = '
            <div style="display: grid; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--dm-bg-secondary); border-radius: 8px;">
                    <span class="dashicons dashicons-yes-alt" style="color: var(--dm-success);"></span>
                    <div>
                        <strong>' . __('Stat Cards', 'flavor-platform') . '</strong>
                        <p style="margin: 0; font-size: 13px; color: var(--dm-text-secondary);">' . __('Tarjetas de estadísticas con variantes de color', 'flavor-platform') . '</p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--dm-bg-secondary); border-radius: 8px;">
                    <span class="dashicons dashicons-yes-alt" style="color: var(--dm-success);"></span>
                    <div>
                        <strong>' . __('Data Tables', 'flavor-platform') . '</strong>
                        <p style="margin: 0; font-size: 13px; color: var(--dm-text-secondary);">' . __('Tablas responsivas con estilos mejorados', 'flavor-platform') . '</p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--dm-bg-secondary); border-radius: 8px;">
                    <span class="dashicons dashicons-yes-alt" style="color: var(--dm-success);"></span>
                    <div>
                        <strong>' . __('Progress Bars', 'flavor-platform') . '</strong>
                        <p style="margin: 0; font-size: 13px; color: var(--dm-text-secondary);">' . __('Barras de progreso animadas', 'flavor-platform') . '</p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--dm-bg-secondary); border-radius: 8px;">
                    <span class="dashicons dashicons-yes-alt" style="color: var(--dm-success);"></span>
                    <div>
                        <strong>' . __('Alerts & Badges', 'flavor-platform') . '</strong>
                        <p style="margin: 0; font-size: 13px; color: var(--dm-text-secondary);">' . __('Alertas descartables y badges de estado', 'flavor-platform') . '</p>
                    </div>
                </div>
            </div>
            ';

            echo $DC::section(
                __('Componentes Disponibles', 'flavor-platform'),
                $components_html,
                [
                    'icon' => 'dashicons-admin-generic',
                    'collapsible' => true,
                ]
            );
            ?>

            <!-- Ayuda -->
            <?php
            $help_html = '
            <p><strong>' . __('¿Cómo usar estos componentes?', 'flavor-platform') . '</strong></p>
            <p>' . __('Todos los módulos pueden usar estos componentes para crear dashboards consistentes y atractivos:', 'flavor-platform') . '</p>
            <pre style="background: var(--dm-bg-secondary); padding: 12px; border-radius: 8px; font-size: 12px; overflow-x: auto;">
require_once FLAVOR_CHAT_IA_PATH . \'/includes/dashboard/class-dashboard-components.php\';
$DC = \'Flavor_Dashboard_Components\';

// Stat card
echo $DC::stat_card([
    \'value\' => \'1,234\',
    \'label\' => \'Total Items\',
    \'icon\' => \'dashicons-star\',
    \'color\' => \'success\'
]);

// Data table
echo $DC::data_table([
    \'title\' => \'My Data\',
    \'columns\' => [...],
    \'data\' => [...]
]);
            </pre>
            ';

            echo $DC::section(
                __('Documentación', 'flavor-platform'),
                $help_html,
                [
                    'icon' => 'dashicons-book',
                    'collapsible' => true,
                    'collapsed' => true,
                ]
            );
            ?>
        </div>

    </div>

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
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.dm-dashboard-subtitle {
    font-size: 15px;
    color: var(--dm-text-secondary);
    margin: 0;
    line-height: 1.6;
}
</style>
