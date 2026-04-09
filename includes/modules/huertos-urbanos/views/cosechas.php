<?php
/**
 * Vista Registro de Cosechas - Huertos Urbanos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_cultivos = $wpdb->prefix . 'flavor_huertos_cultivos';
$tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
$tabla_huertos = $wpdb->prefix . 'flavor_huertos';

// Verificar si las tablas existen
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_cultivos'") === $tabla_cultivos;

// Paginación
$por_pagina = 25;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtros
$filtro_tipo = isset($_GET['tipo_cultivo']) ? sanitize_text_field($_GET['tipo_cultivo']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_huerto = isset($_GET['huerto_id']) ? intval($_GET['huerto_id']) : 0;
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : '';

// Construir WHERE
$where_clauses = ['1=1'];
$where_values = [];

if (!empty($filtro_tipo)) {
    $where_clauses[] = "c.tipo_cultivo LIKE %s";
    $where_values[] = '%' . $wpdb->esc_like($filtro_tipo) . '%';
}

if (!empty($filtro_estado)) {
    $where_clauses[] = "c.estado = %s";
    $where_values[] = $filtro_estado;
}

if ($filtro_huerto > 0) {
    $where_clauses[] = "p.huerto_id = %d";
    $where_values[] = $filtro_huerto;
}

if (!empty($filtro_fecha_desde)) {
    $where_clauses[] = "c.fecha_plantacion >= %s";
    $where_values[] = $filtro_fecha_desde;
}

if (!empty($filtro_fecha_hasta)) {
    $where_clauses[] = "c.fecha_plantacion <= %s";
    $where_values[] = $filtro_fecha_hasta;
}

$where_sql = implode(' AND ', $where_clauses);

// Datos reales o demo
if ($tabla_existe) {
    // Estadísticas
    $total_cultivos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cultivos");
    $cultivos_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cultivos WHERE estado IN ('plantado', 'creciendo', 'floreciendo')");
    $cosechados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cultivos WHERE estado = 'cosechado'");
    $kg_totales = (float) $wpdb->get_var("SELECT COALESCE(SUM(cantidad_kg), 0) FROM $tabla_cultivos WHERE estado = 'cosechado'");
    $tipos_distintos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT tipo_cultivo) FROM $tabla_cultivos");

    // Para gráfico de tipos
    $cultivos_por_tipo = $wpdb->get_results("
        SELECT tipo_cultivo, COUNT(*) as total
        FROM $tabla_cultivos
        GROUP BY tipo_cultivo
        ORDER BY total DESC
        LIMIT 8
    ");

    // Tipos para filtro
    $tipos_disponibles = $wpdb->get_col("SELECT DISTINCT tipo_cultivo FROM $tabla_cultivos ORDER BY tipo_cultivo");

    // Huertos para filtro
    $huertos_disponibles = $wpdb->get_results("SELECT id, nombre FROM $tabla_huertos ORDER BY nombre");

    // Contar total
    $query_count = "
        SELECT COUNT(*)
        FROM $tabla_cultivos c
        LEFT JOIN $tabla_parcelas p ON c.parcela_id = p.id
        WHERE $where_sql
    ";

    if (!empty($where_values)) {
        $total_registros = (int) $wpdb->get_var($wpdb->prepare($query_count, $where_values));
    } else {
        $total_registros = (int) $wpdb->get_var($query_count);
    }

    // Obtener cultivos
    $query = "
        SELECT c.*, p.codigo as parcela_codigo, h.nombre as huerto_nombre, u.display_name as responsable
        FROM $tabla_cultivos c
        LEFT JOIN $tabla_parcelas p ON c.parcela_id = p.id
        LEFT JOIN $tabla_huertos h ON p.huerto_id = h.id
        LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        WHERE $where_sql
        ORDER BY c.fecha_plantacion DESC
        LIMIT %d OFFSET %d
    ";

    $query_values = array_merge($where_values, [$por_pagina, $offset]);
    $cultivos = $wpdb->get_results($wpdb->prepare($query, $query_values));

    $usar_demo = empty($cultivos) && empty($filtro_tipo) && empty($filtro_estado) && $filtro_huerto === 0;
} else {
    $usar_demo = true;
}

// Demo data
if ($usar_demo) {
    $total_cultivos = 156;
    $cultivos_activos = 42;
    $cosechados = 98;
    $kg_totales = 523.5;
    $tipos_distintos = 24;
    $total_registros = 15;

    $cultivos_por_tipo = [
        (object) ['tipo_cultivo' => 'Tomates', 'total' => 28],
        (object) ['tipo_cultivo' => 'Lechugas', 'total' => 22],
        (object) ['tipo_cultivo' => 'Pimientos', 'total' => 18],
        (object) ['tipo_cultivo' => 'Zanahorias', 'total' => 15],
        (object) ['tipo_cultivo' => 'Calabacines', 'total' => 14],
        (object) ['tipo_cultivo' => 'Judías', 'total' => 12],
        (object) ['tipo_cultivo' => 'Cebollas', 'total' => 10],
        (object) ['tipo_cultivo' => 'Otros', 'total' => 37],
    ];

    $tipos_disponibles = ['Tomates', 'Lechugas', 'Pimientos', 'Zanahorias', 'Calabacines', 'Judías', 'Cebollas', 'Pepinos', 'Berenjenas', 'Ajos'];

    $huertos_disponibles = [
        (object) ['id' => 1, 'nombre' => 'Huerto Central'],
        (object) ['id' => 2, 'nombre' => 'Huerto Norte'],
        (object) ['id' => 3, 'nombre' => 'Huerto del Parque'],
    ];

    $cultivos = [
        (object) ['id' => 1, 'tipo_cultivo' => 'Tomates Cherry', 'fecha_plantacion' => '2024-03-15', 'fecha_cosecha' => '2024-07-20', 'cantidad_kg' => 12.5, 'estado' => 'cosechado', 'parcela_codigo' => 'A-01', 'huerto_nombre' => 'Huerto Central', 'responsable' => 'María García'],
        (object) ['id' => 2, 'tipo_cultivo' => 'Lechugas Romanas', 'fecha_plantacion' => '2024-04-01', 'fecha_cosecha' => '2024-05-15', 'cantidad_kg' => 8.2, 'estado' => 'cosechado', 'parcela_codigo' => 'B-03', 'huerto_nombre' => 'Huerto Norte', 'responsable' => 'Carlos Rodríguez'],
        (object) ['id' => 3, 'tipo_cultivo' => 'Pimientos Padrón', 'fecha_plantacion' => '2024-04-10', 'fecha_cosecha' => null, 'cantidad_kg' => null, 'estado' => 'creciendo', 'parcela_codigo' => 'A-02', 'huerto_nombre' => 'Huerto Central', 'responsable' => 'Ana Martínez'],
        (object) ['id' => 4, 'tipo_cultivo' => 'Zanahorias', 'fecha_plantacion' => '2024-03-20', 'fecha_cosecha' => '2024-06-25', 'cantidad_kg' => 15.8, 'estado' => 'cosechado', 'parcela_codigo' => 'C-01', 'huerto_nombre' => 'Huerto del Parque', 'responsable' => 'Pedro López'],
        (object) ['id' => 5, 'tipo_cultivo' => 'Calabacines', 'fecha_plantacion' => '2024-05-01', 'fecha_cosecha' => null, 'cantidad_kg' => null, 'estado' => 'floreciendo', 'parcela_codigo' => 'A-03', 'huerto_nombre' => 'Huerto Central', 'responsable' => 'Laura Sánchez'],
        (object) ['id' => 6, 'tipo_cultivo' => 'Judías Verdes', 'fecha_plantacion' => '2024-04-20', 'fecha_cosecha' => '2024-07-10', 'cantidad_kg' => 6.3, 'estado' => 'cosechado', 'parcela_codigo' => 'B-01', 'huerto_nombre' => 'Huerto Norte', 'responsable' => 'Miguel Torres'],
        (object) ['id' => 7, 'tipo_cultivo' => 'Cebollas', 'fecha_plantacion' => '2024-02-15', 'fecha_cosecha' => '2024-06-01', 'cantidad_kg' => 20.1, 'estado' => 'cosechado', 'parcela_codigo' => 'C-02', 'huerto_nombre' => 'Huerto del Parque', 'responsable' => 'Carmen Ruiz'],
        (object) ['id' => 8, 'tipo_cultivo' => 'Pepinos', 'fecha_plantacion' => '2024-05-10', 'fecha_cosecha' => null, 'cantidad_kg' => null, 'estado' => 'plantado', 'parcela_codigo' => 'A-04', 'huerto_nombre' => 'Huerto Central', 'responsable' => 'Francisco Díaz'],
        (object) ['id' => 9, 'tipo_cultivo' => 'Berenjenas', 'fecha_plantacion' => '2024-04-25', 'fecha_cosecha' => null, 'cantidad_kg' => null, 'estado' => 'creciendo', 'parcela_codigo' => 'B-02', 'huerto_nombre' => 'Huerto Norte', 'responsable' => 'María García'],
        (object) ['id' => 10, 'tipo_cultivo' => 'Tomates Raf', 'fecha_plantacion' => '2024-03-01', 'fecha_cosecha' => '2024-07-15', 'cantidad_kg' => 18.7, 'estado' => 'cosechado', 'parcela_codigo' => 'C-03', 'huerto_nombre' => 'Huerto del Parque', 'responsable' => 'Carlos Rodríguez'],
    ];
}

$total_paginas = ceil($total_registros / $por_pagina);

// Estados disponibles
$estados_cultivo = [
    'plantado' => ['label' => __('Plantado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#17a2b8'],
    'creciendo' => ['label' => __('Creciendo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#28a745'],
    'floreciendo' => ['label' => __('Floreciendo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#ffc107'],
    'cosechado' => ['label' => __('Cosechado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#6f42c1'],
    'fallido' => ['label' => __('Fallido', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => '#dc3545'],
];
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-carrot" style="color: #f39c12;"></span>
        <?php echo esc_html__('Registro de Cosechas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <?php if ($usar_demo): ?>
        <div class="notice notice-info" style="margin: 15px 0;">
            <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('Mostrando datos de demostración. Los datos reales aparecerán cuando se registren cultivos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div class="cosechas-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #28a745;"><?php echo number_format($total_cultivos); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Total Cultivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <span class="dashicons dashicons-carrot" style="font-size: 32px; color: #28a745; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #17a2b8;"><?php echo number_format($cultivos_activos); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('En Crecimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <span class="dashicons dashicons-update" style="font-size: 32px; color: #17a2b8; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #6f42c1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #6f42c1;"><?php echo number_format($cosechados); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Cosechados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <span class="dashicons dashicons-yes-alt" style="font-size: 32px; color: #6f42c1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #f39c12; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #f39c12;"><?php echo number_format($kg_totales, 1); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Kg Cosechados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <span class="dashicons dashicons-chart-bar" style="font-size: 32px; color: #f39c12; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #e74c3c; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #e74c3c;"><?php echo number_format($tipos_distintos); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Variedades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <span class="dashicons dashicons-tag" style="font-size: 32px; color: #e74c3c; opacity: 0.3;"></span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
        <!-- Filtros -->
        <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <form method="get" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'huertos-urbanos-cosechas'); ?>">

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="tipo_cultivo" style="min-width: 140px;">
                        <option value=""><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($tipos_disponibles as $tipo): ?>
                            <option value="<?php echo esc_attr($tipo); ?>" <?php selected($filtro_tipo, $tipo); ?>>
                                <?php echo esc_html($tipo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="estado" style="min-width: 120px;">
                        <option value=""><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($estados_cultivo as $estado_key => $estado_data): ?>
                            <option value="<?php echo esc_attr($estado_key); ?>" <?php selected($filtro_estado, $estado_key); ?>>
                                <?php echo esc_html($estado_data['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="huerto_id" style="min-width: 150px;">
                        <option value="0"><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($huertos_disponibles as $huerto): ?>
                            <option value="<?php echo esc_attr($huerto->id); ?>" <?php selected($filtro_huerto, $huerto->id); ?>>
                                <?php echo esc_html($huerto->nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="date" name="fecha_desde" value="<?php echo esc_attr($filtro_fecha_desde); ?>" style="width: 130px;">
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Hasta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="date" name="fecha_hasta" value="<?php echo esc_attr($filtro_fecha_hasta); ?>" style="width: 130px;">
                </div>

                <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

                <?php if (!empty($filtro_tipo) || !empty($filtro_estado) || $filtro_huerto > 0 || !empty($filtro_fecha_desde) || !empty($filtro_fecha_hasta)): ?>
                    <a href="<?php echo admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'huertos-urbanos-cosechas')); ?>" class="button">
                        <?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Gráfico de tipos -->
        <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">
                <span class="dashicons dashicons-chart-pie" style="font-size: 16px;"></span>
                <?php echo esc_html__('Cultivos por Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h4>
            <canvas id="chartTiposCultivo" height="120"></canvas>
        </div>
    </div>

    <!-- Tabla de cultivos -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php echo esc_html__('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Cultivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Responsable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Plantación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Cosecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 80px; text-align: right;"><?php echo esc_html__('Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cultivos)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-carrot" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666; margin-top: 10px;"><?php echo esc_html__('No se encontraron cultivos con los filtros aplicados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cultivos as $cultivo):
                        $estado_info = $estados_cultivo[$cultivo->estado] ?? ['label' => ucfirst($cultivo->estado), 'color' => '#666'];
                    ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($cultivo->id); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($cultivo->tipo_cultivo); ?></strong>
                            </td>
                            <td>
                                <span style="background: #e9ecef; padding: 2px 8px; border-radius: 4px; font-family: monospace;">
                                    <?php echo esc_html($cultivo->parcela_codigo ?: '-'); ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: #28a745;"><?php echo esc_html($cultivo->huerto_nombre ?: '-'); ?></span>
                            </td>
                            <td><?php echo esc_html($cultivo->responsable ?: '-'); ?></td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($cultivo->fecha_plantacion)); ?></td>
                            <td>
                                <?php if ($cultivo->fecha_cosecha): ?>
                                    <?php echo date_i18n('d/m/Y', strtotime($cultivo->fecha_cosecha)); ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($cultivo->cantidad_kg): ?>
                                    <strong><?php echo number_format($cultivo->cantidad_kg, 1); ?></strong> kg
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; background: <?php echo esc_attr($estado_info['color']); ?>20; color: <?php echo esc_attr($estado_info['color']); ?>;">
                                    <?php echo esc_html($estado_info['label']); ?>
                                </span>
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
                <span class="displaying-num">
                    <?php printf(esc_html__('%s cultivos', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_registros)); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    $url_base = admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'huertos-urbanos-cosechas'));
                    if (!empty($filtro_tipo)) $url_base .= '&tipo_cultivo=' . urlencode($filtro_tipo);
                    if (!empty($filtro_estado)) $url_base .= '&estado=' . urlencode($filtro_estado);
                    if ($filtro_huerto > 0) $url_base .= '&huerto_id=' . $filtro_huerto;
                    if (!empty($filtro_fecha_desde)) $url_base .= '&fecha_desde=' . urlencode($filtro_fecha_desde);
                    if (!empty($filtro_fecha_hasta)) $url_base .= '&fecha_hasta=' . urlencode($filtro_fecha_hasta);

                    if ($pagina_actual > 1): ?>
                        <a class="first-page button" href="<?php echo esc_url($url_base . '&paged=1'); ?>">&laquo;</a>
                        <a class="prev-page button" href="<?php echo esc_url($url_base . '&paged=' . ($pagina_actual - 1)); ?>">&lsaquo;</a>
                    <?php endif; ?>

                    <span class="paging-input">
                        <?php echo $pagina_actual; ?> <?php echo esc_html__('de', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo $total_paginas; ?>
                    </span>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a class="next-page button" href="<?php echo esc_url($url_base . '&paged=' . ($pagina_actual + 1)); ?>">&rsaquo;</a>
                        <a class="last-page button" href="<?php echo esc_url($url_base . '&paged=' . $total_paginas); ?>">&raquo;</a>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    const ctx = document.getElementById('chartTiposCultivo');
    if (ctx) {
        const data = <?php echo json_encode($cultivos_por_tipo); ?>;
        const colores = ['#28a745', '#17a2b8', '#ffc107', '#6f42c1', '#e74c3c', '#f39c12', '#3498db', '#95a5a6'];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.tipo_cultivo),
                datasets: [{
                    data: data.map(d => parseInt(d.total)),
                    backgroundColor: colores.slice(0, data.length)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12, font: { size: 11 } }
                    }
                }
            }
        });
    }
});
</script>

<style>
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
}
</style>
