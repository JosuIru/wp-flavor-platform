<?php
/**
 * Vista: Gestión de Parcelas - Huertos Urbanos
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
$tabla_huertos = $wpdb->prefix . 'flavor_huertos';

// Verificar existencia de tablas
$tabla_parcelas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_parcelas'") === $tabla_parcelas;
$tabla_huertos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_huertos'") === $tabla_huertos;

// Parámetros de filtrado y paginación
$filtro_huerto = isset($_GET['huerto_id']) ? (int) $_GET['huerto_id'] : 0;
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$pagina_actual = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
$por_pagina = 20;
$offset = ($pagina_actual - 1) * $por_pagina;

// Inicializar estadísticas
$total_parcelas = 0;
$parcelas_ocupadas = 0;
$parcelas_disponibles = 0;
$parcelas_mantenimiento = 0;
$metros_totales = 0;
$parcelas = [];
$huertos_lista = [];

if ($tabla_parcelas_existe) {
    // Estadísticas generales
    $total_parcelas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_parcelas");
    $parcelas_ocupadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_parcelas WHERE estado = 'ocupada'");
    $parcelas_disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_parcelas WHERE estado = 'disponible'");
    $parcelas_mantenimiento = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_parcelas WHERE estado = 'mantenimiento'");
    $metros_totales = (float) $wpdb->get_var("SELECT COALESCE(SUM(tamano_m2), 0) FROM $tabla_parcelas");

    // Construir consulta con filtros
    $where_clauses = ["1=1"];
    $where_values = [];

    if ($filtro_huerto > 0) {
        $where_clauses[] = "p.huerto_id = %d";
        $where_values[] = $filtro_huerto;
    }

    if (!empty($filtro_estado)) {
        $where_clauses[] = "p.estado = %s";
        $where_values[] = $filtro_estado;
    }

    if (!empty($busqueda)) {
        $where_clauses[] = "(h.nombre LIKE %s OR p.numero_parcela LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
    }

    $where_sql = implode(' AND ', $where_clauses);

    // Total de resultados para paginación
    $total_query = "SELECT COUNT(*) FROM $tabla_parcelas p LEFT JOIN $tabla_huertos h ON p.huerto_id = h.id WHERE $where_sql";
    if (!empty($where_values)) {
        $total_query = $wpdb->prepare($total_query, ...$where_values);
    }
    $total_resultados = (int) $wpdb->get_var($total_query);
    $total_paginas = ceil($total_resultados / $por_pagina);

    // Obtener parcelas
    $query = "
        SELECT p.*, h.nombre as huerto_nombre, u.display_name as responsable_nombre
        FROM $tabla_parcelas p
        LEFT JOIN $tabla_huertos h ON p.huerto_id = h.id
        LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        WHERE $where_sql
        ORDER BY h.nombre ASC, p.numero_parcela ASC
        LIMIT %d OFFSET %d
    ";
    $query_values = array_merge($where_values, [$por_pagina, $offset]);
    $parcelas = $wpdb->get_results($wpdb->prepare($query, ...$query_values));

    // Lista de huertos para filtro
    if ($tabla_huertos_existe) {
        $huertos_lista = $wpdb->get_results("SELECT id, nombre FROM $tabla_huertos ORDER BY nombre");
    }
}

// Datos de demostración si no hay datos reales
$usar_datos_demo = ($total_parcelas == 0);

if ($usar_datos_demo) {
    $total_parcelas = 48;
    $parcelas_ocupadas = 38;
    $parcelas_disponibles = 7;
    $parcelas_mantenimiento = 3;
    $metros_totales = 1440;
    $total_resultados = 48;
    $total_paginas = 3;

    $huertos_lista = [
        (object) ['id' => 1, 'nombre' => 'Huerto Central'],
        (object) ['id' => 2, 'nombre' => 'Huerto Norte'],
        (object) ['id' => 3, 'nombre' => 'Huerto del Parque'],
    ];

    $parcelas = [
        (object) ['id' => 1, 'huerto_id' => 1, 'huerto_nombre' => 'Huerto Central', 'numero_parcela' => 'A-01', 'tamano_m2' => 30, 'estado' => 'ocupada', 'usuario_id' => 1, 'responsable_nombre' => 'María García', 'fecha_asignacion' => '2024-01-15'],
        (object) ['id' => 2, 'huerto_id' => 1, 'huerto_nombre' => 'Huerto Central', 'numero_parcela' => 'A-02', 'tamano_m2' => 30, 'estado' => 'ocupada', 'usuario_id' => 2, 'responsable_nombre' => 'Carlos López', 'fecha_asignacion' => '2024-02-01'],
        (object) ['id' => 3, 'huerto_id' => 1, 'huerto_nombre' => 'Huerto Central', 'numero_parcela' => 'A-03', 'tamano_m2' => 30, 'estado' => 'disponible', 'usuario_id' => null, 'responsable_nombre' => null, 'fecha_asignacion' => null],
        (object) ['id' => 4, 'huerto_id' => 1, 'huerto_nombre' => 'Huerto Central', 'numero_parcela' => 'A-04', 'tamano_m2' => 30, 'estado' => 'mantenimiento', 'usuario_id' => null, 'responsable_nombre' => null, 'fecha_asignacion' => null],
        (object) ['id' => 5, 'huerto_id' => 2, 'huerto_nombre' => 'Huerto Norte', 'numero_parcela' => 'B-01', 'tamano_m2' => 25, 'estado' => 'ocupada', 'usuario_id' => 3, 'responsable_nombre' => 'Ana Martínez', 'fecha_asignacion' => '2023-11-20'],
        (object) ['id' => 6, 'huerto_id' => 2, 'huerto_nombre' => 'Huerto Norte', 'numero_parcela' => 'B-02', 'tamano_m2' => 25, 'estado' => 'ocupada', 'usuario_id' => 4, 'responsable_nombre' => 'Pedro Sánchez', 'fecha_asignacion' => '2024-03-01'],
        (object) ['id' => 7, 'huerto_id' => 3, 'huerto_nombre' => 'Huerto del Parque', 'numero_parcela' => 'C-01', 'tamano_m2' => 35, 'estado' => 'disponible', 'usuario_id' => null, 'responsable_nombre' => null, 'fecha_asignacion' => null],
        (object) ['id' => 8, 'huerto_id' => 3, 'huerto_nombre' => 'Huerto del Parque', 'numero_parcela' => 'C-02', 'tamano_m2' => 35, 'estado' => 'ocupada', 'usuario_id' => 5, 'responsable_nombre' => 'Laura Fernández', 'fecha_asignacion' => '2024-01-10'],
    ];
}

// Colores por estado
$colores_estado = [
    'ocupada' => ['bg' => '#d4edda', 'color' => '#155724'],
    'disponible' => ['bg' => '#cce5ff', 'color' => '#004085'],
    'mantenimiento' => ['bg' => '#fff3cd', 'color' => '#856404'],
    'reservada' => ['bg' => '#e2e3e5', 'color' => '#383d41'],
];
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-layout" style="color: #28a745;"></span>
        <?php echo esc_html__('Gestión de Parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-parcelas&action=new')); ?>" class="page-title-action">
        <?php echo esc_html__('Añadir Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <hr class="wp-header-end">

    <?php if ($usar_datos_demo): ?>
        <div class="notice notice-info">
            <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('Mostrando datos de demostración.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #28a745; padding: 15px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <p style="margin: 0; color: #646970; font-size: 12px;"><?php echo esc_html__('Total Parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <h3 style="margin: 5px 0 0; font-size: 24px; color: #1d2327;"><?php echo number_format($total_parcelas); ?></h3>
        </div>
        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #155724; padding: 15px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <p style="margin: 0; color: #646970; font-size: 12px;"><?php echo esc_html__('Ocupadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <h3 style="margin: 5px 0 0; font-size: 24px; color: #155724;"><?php echo number_format($parcelas_ocupadas); ?></h3>
        </div>
        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #004085; padding: 15px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <p style="margin: 0; color: #646970; font-size: 12px;"><?php echo esc_html__('Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <h3 style="margin: 5px 0 0; font-size: 24px; color: #004085;"><?php echo number_format($parcelas_disponibles); ?></h3>
        </div>
        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #856404; padding: 15px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <p style="margin: 0; color: #646970; font-size: 12px;"><?php echo esc_html__('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <h3 style="margin: 5px 0 0; font-size: 24px; color: #856404;"><?php echo number_format($parcelas_mantenimiento); ?></h3>
        </div>
        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 15px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <p style="margin: 0; color: #646970; font-size: 12px;"><?php echo esc_html__('Metros Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <h3 style="margin: 5px 0 0; font-size: 24px; color: #2271b1;"><?php echo number_format($metros_totales); ?> m²</h3>
        </div>
    </div>

    <!-- Filtros -->
    <div class="tablenav top" style="margin-bottom: 10px;">
        <form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="huertos-parcelas">

            <select name="huerto_id" style="min-width: 150px;">
                <option value=""><?php echo esc_html__('Todos los huertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($huertos_lista as $huerto): ?>
                    <option value="<?php echo esc_attr($huerto->id); ?>" <?php selected($filtro_huerto, $huerto->id); ?>>
                        <?php echo esc_html($huerto->nombre); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="estado" style="min-width: 130px;">
                <option value=""><?php echo esc_html__('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="ocupada" <?php selected($filtro_estado, 'ocupada'); ?>><?php echo esc_html__('Ocupada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="disponible" <?php selected($filtro_estado, 'disponible'); ?>><?php echo esc_html__('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="mantenimiento" <?php selected($filtro_estado, 'mantenimiento'); ?>><?php echo esc_html__('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>

            <input type="search" name="s" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php echo esc_attr__('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="min-width: 200px;">

            <button type="submit" class="button"><?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

            <?php if ($filtro_huerto || $filtro_estado || $busqueda): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-parcelas')); ?>" class="button"><?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            <?php endif; ?>

            <span class="displaying-num" style="margin-left: auto;">
                <?php printf(esc_html__('%s parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_resultados)); ?>
            </span>
        </form>
    </div>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;"><?php echo esc_html__('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th><?php echo esc_html__('Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php echo esc_html__('Parcela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 80px;"><?php echo esc_html__('Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 110px;"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th><?php echo esc_html__('Responsable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php echo esc_html__('Desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 120px;"><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($parcelas)): ?>
                <?php foreach ($parcelas as $parcela): ?>
                    <?php
                    $estado_style = $colores_estado[$parcela->estado] ?? ['bg' => '#f0f0f1', 'color' => '#646970'];
                    ?>
                    <tr>
                        <td><strong>#<?php echo esc_html($parcela->id); ?></strong></td>
                        <td>
                            <strong><?php echo esc_html($parcela->huerto_nombre ?: __('Sin huerto', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                        </td>
                        <td>
                            <span style="background: #f0f0f1; padding: 3px 8px; border-radius: 3px; font-family: monospace;">
                                <?php echo esc_html($parcela->numero_parcela); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($parcela->tamano_m2 ?? 0); ?> m²</td>
                        <td>
                            <span style="background: <?php echo esc_attr($estado_style['bg']); ?>; color: <?php echo esc_attr($estado_style['color']); ?>; padding: 4px 10px; border-radius: 3px; font-size: 12px; display: inline-block;">
                                <?php echo esc_html(ucfirst($parcela->estado)); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($parcela->responsable_nombre): ?>
                                <span class="dashicons dashicons-admin-users" style="font-size: 14px; color: #646970;"></span>
                                <?php echo esc_html($parcela->responsable_nombre); ?>
                            <?php else: ?>
                                <span style="color: #646970;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($parcela->fecha_asignacion)): ?>
                                <?php echo esc_html(date_i18n('d/m/Y', strtotime($parcela->fecha_asignacion))); ?>
                            <?php else: ?>
                                <span style="color: #646970;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-parcelas&action=edit&id=' . $parcela->id)); ?>" class="button button-small" title="<?php echo esc_attr__('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-edit" style="font-size: 14px; line-height: 1.8;"></span>
                            </a>
                            <?php if ($parcela->estado === 'disponible'): ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-parcelas&action=assign&id=' . $parcela->id)); ?>" class="button button-small button-primary" title="<?php echo esc_attr__('Asignar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-admin-users" style="font-size: 14px; line-height: 1.8;"></span>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px;">
                        <span class="dashicons dashicons-layout" style="font-size: 48px; color: #ddd;"></span>
                        <p style="color: #646970; margin-top: 10px;"><?php echo esc_html__('No se encontraron parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(esc_html__('%s parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_resultados)); ?></span>
                <span class="pagination-links">
                    <?php
                    $base_url = admin_url('admin.php?page=huertos-parcelas');
                    if ($filtro_huerto) $base_url = add_query_arg('huerto_id', $filtro_huerto, $base_url);
                    if ($filtro_estado) $base_url = add_query_arg('estado', $filtro_estado, $base_url);
                    if ($busqueda) $base_url = add_query_arg('s', $busqueda, $base_url);

                    if ($pagina_actual > 1): ?>
                        <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $pagina_actual - 1, $base_url)); ?>">‹</a>
                    <?php else: ?>
                        <span class="tablenav-pages-navspan button disabled">‹</span>
                    <?php endif; ?>

                    <span class="paging-input">
                        <span class="tablenav-paging-text">
                            <?php echo $pagina_actual; ?> de <span class="total-pages"><?php echo $total_paginas; ?></span>
                        </span>
                    </span>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $pagina_actual + 1, $base_url)); ?>">›</a>
                    <?php else: ?>
                        <span class="tablenav-pages-navspan button disabled">›</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

</div>

<style>
.flavor-stat-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.12) !important;
}
</style>
