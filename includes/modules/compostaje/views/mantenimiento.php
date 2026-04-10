<?php
/**
 * Vista Mantenimiento - Compostaje
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_mantenimiento = $wpdb->prefix . 'flavor_compostaje_mantenimiento';
$tabla_composteras = $wpdb->prefix . 'flavor_compostaje_composteras';

// Verificar si la tabla existe
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_mantenimiento'") === $tabla_mantenimiento;

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtros
$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_compostera = isset($_GET['compostera_id']) ? intval($_GET['compostera_id']) : 0;

// Construir WHERE
$where_clauses = ['1=1'];
$where_values = [];

if (!empty($filtro_tipo)) {
    $where_clauses[] = "m.tipo_mantenimiento = %s";
    $where_values[] = $filtro_tipo;
}

if (!empty($filtro_estado)) {
    $where_clauses[] = "m.estado = %s";
    $where_values[] = $filtro_estado;
}

if ($filtro_compostera > 0) {
    $where_clauses[] = "m.compostera_id = %d";
    $where_values[] = $filtro_compostera;
}

$where_sql = implode(' AND ', $where_clauses);

// Datos reales o demo
if ($tabla_existe) {
    // Estadísticas
    $total_tareas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_mantenimiento");
    $tareas_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_mantenimiento WHERE estado = 'pendiente'");
    $tareas_completadas_mes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_mantenimiento WHERE estado = 'completada' AND MONTH(fecha_realizada) = MONTH(CURDATE())");
    $tareas_vencidas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_mantenimiento WHERE estado = 'pendiente' AND fecha_programada < CURDATE()");

    // Tipos para filtro
    $tipos_disponibles = $wpdb->get_col("SELECT DISTINCT tipo_mantenimiento FROM $tabla_mantenimiento ORDER BY tipo_mantenimiento");

    // Composteras para filtro
    $composteras_disponibles = $wpdb->get_results("SELECT id, nombre FROM $tabla_composteras ORDER BY nombre");

    // Contar total
    $query_count = "SELECT COUNT(*) FROM $tabla_mantenimiento m WHERE $where_sql";
    if (!empty($where_values)) {
        $total_registros = (int) $wpdb->get_var($wpdb->prepare($query_count, $where_values));
    } else {
        $total_registros = (int) $wpdb->get_var($query_count);
    }

    // Obtener tareas
    $query = "
        SELECT m.*, c.nombre as compostera_nombre, u.display_name as responsable
        FROM $tabla_mantenimiento m
        LEFT JOIN $tabla_composteras c ON m.compostera_id = c.id
        LEFT JOIN {$wpdb->users} u ON m.responsable_id = u.ID
        WHERE $where_sql
        ORDER BY m.fecha_programada DESC
        LIMIT %d OFFSET %d
    ";

    $query_values = array_merge($where_values, [$por_pagina, $offset]);
    $tareas = $wpdb->get_results($wpdb->prepare($query, $query_values));

    $usar_demo = empty($tareas) && empty($filtro_tipo) && empty($filtro_estado) && $filtro_compostera === 0;
} else {
    $usar_demo = true;
}

// Demo data
if ($usar_demo) {
    $total_tareas = 48;
    $tareas_pendientes = 8;
    $tareas_completadas_mes = 12;
    $tareas_vencidas = 2;
    $total_registros = 10;

    $tipos_disponibles = ['Volteo', 'Riego', 'Control temperatura', 'Cribado', 'Vaciado', 'Limpieza'];

    $composteras_disponibles = [
        (object) ['id' => 1, 'nombre' => 'Compostera A'],
        (object) ['id' => 2, 'nombre' => 'Compostera B'],
        (object) ['id' => 3, 'nombre' => 'Compostera C'],
    ];

    $tareas = [
        (object) ['id' => 1, 'tipo_mantenimiento' => 'Volteo', 'fecha_programada' => date('Y-m-d', strtotime('+2 days')), 'fecha_realizada' => null, 'estado' => 'pendiente', 'compostera_nombre' => 'Compostera A', 'responsable' => 'María García', 'notas' => 'Volteo semanal programado'],
        (object) ['id' => 2, 'tipo_mantenimiento' => 'Riego', 'fecha_programada' => date('Y-m-d', strtotime('-1 day')), 'fecha_realizada' => date('Y-m-d', strtotime('-1 day')), 'estado' => 'completada', 'compostera_nombre' => 'Compostera B', 'responsable' => 'Carlos Rodríguez', 'notas' => 'Humedad ajustada al 60%'],
        (object) ['id' => 3, 'tipo_mantenimiento' => 'Control temperatura', 'fecha_programada' => date('Y-m-d'), 'fecha_realizada' => null, 'estado' => 'pendiente', 'compostera_nombre' => 'Compostera A', 'responsable' => 'Ana Martínez', 'notas' => 'Verificar temperatura del núcleo'],
        (object) ['id' => 4, 'tipo_mantenimiento' => 'Cribado', 'fecha_programada' => date('Y-m-d', strtotime('-5 days')), 'fecha_realizada' => date('Y-m-d', strtotime('-5 days')), 'estado' => 'completada', 'compostera_nombre' => 'Compostera C', 'responsable' => 'Pedro López', 'notas' => 'Compost maduro extraído: 50kg'],
        (object) ['id' => 5, 'tipo_mantenimiento' => 'Vaciado', 'fecha_programada' => date('Y-m-d', strtotime('-10 days')), 'fecha_realizada' => null, 'estado' => 'vencida', 'compostera_nombre' => 'Compostera B', 'responsable' => 'Laura Sánchez', 'notas' => 'Pendiente de programar'],
        (object) ['id' => 6, 'tipo_mantenimiento' => 'Limpieza', 'fecha_programada' => date('Y-m-d', strtotime('+5 days')), 'fecha_realizada' => null, 'estado' => 'pendiente', 'compostera_nombre' => 'Compostera A', 'responsable' => 'Miguel Torres', 'notas' => 'Limpieza trimestral'],
        (object) ['id' => 7, 'tipo_mantenimiento' => 'Volteo', 'fecha_programada' => date('Y-m-d', strtotime('-3 days')), 'fecha_realizada' => date('Y-m-d', strtotime('-3 days')), 'estado' => 'completada', 'compostera_nombre' => 'Compostera C', 'responsable' => 'Carmen Ruiz', 'notas' => 'Volteo realizado correctamente'],
        (object) ['id' => 8, 'tipo_mantenimiento' => 'Riego', 'fecha_programada' => date('Y-m-d', strtotime('+1 day')), 'fecha_realizada' => null, 'estado' => 'pendiente', 'compostera_nombre' => 'Compostera A', 'responsable' => 'Francisco Díaz', 'notas' => 'Riego programado'],
    ];
}

$total_paginas = ceil($total_registros / $por_pagina);

// Estados para badges
$estados_tarea = [
    'pendiente' => ['label' => __('Pendiente', 'flavor-platform'), 'color' => '#ffc107'],
    'completada' => ['label' => __('Completada', 'flavor-platform'), 'color' => '#28a745'],
    'vencida' => ['label' => __('Vencida', 'flavor-platform'), 'color' => '#dc3545'],
    'cancelada' => ['label' => __('Cancelada', 'flavor-platform'), 'color' => '#6c757d'],
];

// Tipos con iconos
$tipos_iconos = [
    'Volteo' => 'dashicons-update',
    'Riego' => 'dashicons-admin-site-alt3',
    'Control temperatura' => 'dashicons-chart-line',
    'Cribado' => 'dashicons-filter',
    'Vaciado' => 'dashicons-download',
    'Limpieza' => 'dashicons-trash',
];
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-tools" style="color: #795548;"></span>
        <?php echo esc_html__('Mantenimiento - Compostaje', 'flavor-platform'); ?>
    </h1>

    <?php if ($usar_demo): ?>
        <div class="notice notice-info" style="margin: 15px 0;">
            <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('Mostrando datos de demostración.', 'flavor-platform'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($tareas_vencidas > 0 && !$usar_demo): ?>
        <div class="notice notice-warning" style="margin: 15px 0;">
            <p><span class="dashicons dashicons-warning"></span> <?php printf(esc_html__('Hay %d tareas de mantenimiento vencidas que requieren atención.', 'flavor-platform'), $tareas_vencidas); ?></p>
        </div>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div class="mantenimiento-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #795548; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #795548;"><?php echo number_format($total_tareas); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Total Tareas', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-clipboard" style="font-size: 32px; color: #795548; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #ffc107;"><?php echo number_format($tareas_pendientes); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Pendientes', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-clock" style="font-size: 32px; color: #ffc107; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #28a745;"><?php echo number_format($tareas_completadas_mes); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Completadas (mes)', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-yes-alt" style="font-size: 32px; color: #28a745; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #dc3545;"><?php echo number_format($tareas_vencidas); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Vencidas', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-warning" style="font-size: 32px; color: #dc3545; opacity: 0.3;"></span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'compostaje-mantenimiento'); ?>">

            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Tipo', 'flavor-platform'); ?></label>
                <select name="tipo" style="min-width: 150px;">
                    <option value=""><?php echo esc_html__('Todos', 'flavor-platform'); ?></option>
                    <?php foreach ($tipos_disponibles as $tipo): ?>
                        <option value="<?php echo esc_attr($tipo); ?>" <?php selected($filtro_tipo, $tipo); ?>>
                            <?php echo esc_html($tipo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Estado', 'flavor-platform'); ?></label>
                <select name="estado" style="min-width: 130px;">
                    <option value=""><?php echo esc_html__('Todos', 'flavor-platform'); ?></option>
                    <?php foreach ($estados_tarea as $estado_key => $estado_data): ?>
                        <option value="<?php echo esc_attr($estado_key); ?>" <?php selected($filtro_estado, $estado_key); ?>>
                            <?php echo esc_html($estado_data['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Compostera', 'flavor-platform'); ?></label>
                <select name="compostera_id" style="min-width: 150px;">
                    <option value="0"><?php echo esc_html__('Todas', 'flavor-platform'); ?></option>
                    <?php foreach ($composteras_disponibles as $compostera): ?>
                        <option value="<?php echo esc_attr($compostera->id); ?>" <?php selected($filtro_compostera, $compostera->id); ?>>
                            <?php echo esc_html($compostera->nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', 'flavor-platform'); ?></button>

            <?php if (!empty($filtro_tipo) || !empty($filtro_estado) || $filtro_compostera > 0): ?>
                <a href="<?php echo admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'compostaje-mantenimiento')); ?>" class="button">
                    <?php echo esc_html__('Limpiar', 'flavor-platform'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-platform'); ?></th>
                    <th><?php echo esc_html__('Tipo', 'flavor-platform'); ?></th>
                    <th><?php echo esc_html__('Compostera', 'flavor-platform'); ?></th>
                    <th><?php echo esc_html__('Responsable', 'flavor-platform'); ?></th>
                    <th style="width: 110px;"><?php echo esc_html__('Programada', 'flavor-platform'); ?></th>
                    <th style="width: 110px;"><?php echo esc_html__('Realizada', 'flavor-platform'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Estado', 'flavor-platform'); ?></th>
                    <th style="width: 80px;"><?php echo esc_html__('Acciones', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tareas)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-admin-tools" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666; margin-top: 10px;"><?php echo esc_html__('No hay tareas de mantenimiento.', 'flavor-platform'); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tareas as $tarea):
                        $estado_info = $estados_tarea[$tarea->estado] ?? ['label' => ucfirst($tarea->estado), 'color' => '#666'];
                        $icono = $tipos_iconos[$tarea->tipo_mantenimiento] ?? 'dashicons-admin-generic';
                    ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($tarea->id); ?></strong></td>
                            <td>
                                <span class="dashicons <?php echo esc_attr($icono); ?>" style="color: #795548; margin-right: 5px;"></span>
                                <strong><?php echo esc_html($tarea->tipo_mantenimiento); ?></strong>
                                <?php if (!empty($tarea->notas)): ?>
                                    <br><small style="color: #666;"><?php echo esc_html($tarea->notas); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="background: #e9ecef; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                                    <?php echo esc_html($tarea->compostera_nombre ?: '-'); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($tarea->responsable ?: '-'); ?></td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($tarea->fecha_programada)); ?></td>
                            <td>
                                <?php if ($tarea->fecha_realizada): ?>
                                    <?php echo date_i18n('d/m/Y', strtotime($tarea->fecha_realizada)); ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; background: <?php echo esc_attr($estado_info['color']); ?>20; color: <?php echo esc_attr($estado_info['color']); ?>;">
                                    <?php echo esc_html($estado_info['label']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($tarea->estado === 'pendiente'): ?>
                                    <button class="button button-small button-primary" title="<?php echo esc_attr__('Marcar completada', 'flavor-platform'); ?>">
                                        <span class="dashicons dashicons-yes" style="vertical-align: text-bottom;"></span>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <div class="tablenav bottom" style="margin-top: 20px;">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(esc_html__('%s tareas', 'flavor-platform'), number_format($total_registros)); ?></span>
                <span class="pagination-links">
                    <?php
                    $url_base = admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'compostaje-mantenimiento'));
                    if (!empty($filtro_tipo)) $url_base .= '&tipo=' . urlencode($filtro_tipo);
                    if (!empty($filtro_estado)) $url_base .= '&estado=' . urlencode($filtro_estado);
                    if ($filtro_compostera > 0) $url_base .= '&compostera_id=' . $filtro_compostera;

                    if ($pagina_actual > 1): ?>
                        <a class="first-page button" href="<?php echo esc_url($url_base . '&paged=1'); ?>">&laquo;</a>
                        <a class="prev-page button" href="<?php echo esc_url($url_base . '&paged=' . ($pagina_actual - 1)); ?>">&lsaquo;</a>
                    <?php endif; ?>

                    <span class="paging-input"><?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a class="next-page button" href="<?php echo esc_url($url_base . '&paged=' . ($pagina_actual + 1)); ?>">&rsaquo;</a>
                        <a class="last-page button" href="<?php echo esc_url($url_base . '&paged=' . $total_paginas); ?>">&raquo;</a>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
}
</style>
