<?php
/**
 * Vista Hortelanos - Huertos Urbanos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
$tabla_huertos = $wpdb->prefix . 'flavor_huertos';

// Verificar si las tablas existen
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_parcelas'") === $tabla_parcelas;

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtros
$filtro_huerto = isset($_GET['huerto_id']) ? intval($_GET['huerto_id']) : 0;
$filtro_busqueda = isset($_GET['busqueda']) ? sanitize_text_field($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['estado_hortelano']) ? sanitize_text_field($_GET['estado_hortelano']) : '';

// Construir consulta base
$where_clauses = ["p.estado = 'ocupada'"];
$where_values = [];

if ($filtro_huerto > 0) {
    $where_clauses[] = "p.huerto_id = %d";
    $where_values[] = $filtro_huerto;
}

if (!empty($filtro_busqueda)) {
    $where_clauses[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
    $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
    $where_values[] = $busqueda_like;
    $where_values[] = $busqueda_like;
}

$where_sql = implode(' AND ', $where_clauses);

// Datos reales o demo
if ($tabla_existe) {
    // Estadísticas
    $total_hortelanos = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT u.ID)
        FROM {$wpdb->users} u
        INNER JOIN $tabla_parcelas p ON u.ID = p.usuario_id
        WHERE p.estado = 'ocupada'
    ");

    $hortelanos_nuevos_mes = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT u.ID)
        FROM {$wpdb->users} u
        INNER JOIN $tabla_parcelas p ON u.ID = p.usuario_id
        WHERE p.estado = 'ocupada'
        AND p.fecha_asignacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");

    $total_parcelas_ocupadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_parcelas WHERE estado = 'ocupada'");
    $promedio_parcelas = $total_hortelanos > 0 ? round($total_parcelas_ocupadas / $total_hortelanos, 1) : 0;

    $hortelanos_veteranos = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT u.ID)
        FROM {$wpdb->users} u
        INNER JOIN $tabla_parcelas p ON u.ID = p.usuario_id
        WHERE p.estado = 'ocupada'
        AND p.fecha_asignacion <= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");

    // Obtener huertos para filtro
    $huertos_disponibles = $wpdb->get_results("SELECT id, nombre FROM $tabla_huertos ORDER BY nombre");

    // Contar total para paginación
    $query_count = "
        SELECT COUNT(DISTINCT u.ID)
        FROM {$wpdb->users} u
        INNER JOIN $tabla_parcelas p ON u.ID = p.usuario_id
        WHERE $where_sql
    ";

    if (!empty($where_values)) {
        $total_registros = (int) $wpdb->get_var($wpdb->prepare($query_count, $where_values));
    } else {
        $total_registros = (int) $wpdb->get_var($query_count);
    }

    // Obtener hortelanos con paginación
    $query = "
        SELECT u.ID, u.display_name, u.user_email, u.user_registered,
               COUNT(p.id) as total_parcelas,
               MIN(p.fecha_asignacion) as primera_parcela,
               GROUP_CONCAT(DISTINCT h.nombre SEPARATOR ', ') as huertos
        FROM {$wpdb->users} u
        INNER JOIN $tabla_parcelas p ON u.ID = p.usuario_id
        LEFT JOIN $tabla_huertos h ON p.huerto_id = h.id
        WHERE $where_sql
        GROUP BY u.ID
        ORDER BY u.display_name ASC
        LIMIT %d OFFSET %d
    ";

    $query_values = array_merge($where_values, [$por_pagina, $offset]);
    $hortelanos = $wpdb->get_results($wpdb->prepare($query, $query_values));

    $usar_demo = empty($hortelanos) && empty($filtro_busqueda) && $filtro_huerto === 0;
} else {
    $usar_demo = true;
}

// Demo data
if ($usar_demo) {
    $total_hortelanos = 45;
    $hortelanos_nuevos_mes = 3;
    $promedio_parcelas = 1.2;
    $hortelanos_veteranos = 18;
    $total_registros = 12;

    $huertos_disponibles = [
        (object) ['id' => 1, 'nombre' => 'Huerto Central'],
        (object) ['id' => 2, 'nombre' => 'Huerto Norte'],
        (object) ['id' => 3, 'nombre' => 'Huerto del Parque'],
    ];

    $hortelanos = [
        (object) ['ID' => 1, 'display_name' => 'María García López', 'user_email' => 'maria.garcia@ejemplo.com', 'user_registered' => '2023-03-15', 'total_parcelas' => 2, 'primera_parcela' => '2023-04-01', 'huertos' => 'Huerto Central'],
        (object) ['ID' => 2, 'display_name' => 'Carlos Rodríguez', 'user_email' => 'carlos.r@ejemplo.com', 'user_registered' => '2022-06-20', 'total_parcelas' => 1, 'primera_parcela' => '2022-07-15', 'huertos' => 'Huerto Norte'],
        (object) ['ID' => 3, 'display_name' => 'Ana Martínez Sánchez', 'user_email' => 'ana.ms@ejemplo.com', 'user_registered' => '2024-01-10', 'total_parcelas' => 1, 'primera_parcela' => '2024-02-01', 'huertos' => 'Huerto del Parque'],
        (object) ['ID' => 4, 'display_name' => 'Pedro López Fernández', 'user_email' => 'pedro.lf@ejemplo.com', 'user_registered' => '2021-09-05', 'total_parcelas' => 3, 'primera_parcela' => '2021-10-01', 'huertos' => 'Huerto Central, Huerto Norte'],
        (object) ['ID' => 5, 'display_name' => 'Laura Sánchez Gil', 'user_email' => 'laura.sg@ejemplo.com', 'user_registered' => '2023-11-22', 'total_parcelas' => 1, 'primera_parcela' => '2023-12-01', 'huertos' => 'Huerto Central'],
        (object) ['ID' => 6, 'display_name' => 'Miguel Ángel Torres', 'user_email' => 'miguel.t@ejemplo.com', 'user_registered' => '2022-02-14', 'total_parcelas' => 2, 'primera_parcela' => '2022-03-01', 'huertos' => 'Huerto del Parque'],
        (object) ['ID' => 7, 'display_name' => 'Carmen Ruiz Vega', 'user_email' => 'carmen.rv@ejemplo.com', 'user_registered' => '2024-02-28', 'total_parcelas' => 1, 'primera_parcela' => '2024-03-15', 'huertos' => 'Huerto Norte'],
        (object) ['ID' => 8, 'display_name' => 'Francisco Díaz Moral', 'user_email' => 'fran.dm@ejemplo.com', 'user_registered' => '2020-05-10', 'total_parcelas' => 2, 'primera_parcela' => '2020-06-01', 'huertos' => 'Huerto Central'],
    ];
}

$total_paginas = ceil($total_registros / $por_pagina);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups" style="color: #28a745;"></span>
        <?php echo esc_html__('Hortelanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <?php if ($usar_demo): ?>
        <div class="notice notice-info" style="margin: 15px 0;">
            <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('Mostrando datos de demostración. Los datos reales aparecerán cuando haya hortelanos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div class="hortelanos-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #28a745;"><?php echo number_format($total_hortelanos); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Total Hortelanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <span class="dashicons dashicons-groups" style="font-size: 36px; color: #28a745; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #17a2b8;"><?php echo number_format($hortelanos_nuevos_mes); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Nuevos (30 días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <span class="dashicons dashicons-plus-alt" style="font-size: 36px; color: #17a2b8; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #ffc107;"><?php echo $promedio_parcelas; ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Parcelas/Huertano', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <span class="dashicons dashicons-layout" style="font-size: 36px; color: #ffc107; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #6f42c1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #6f42c1;"><?php echo number_format($hortelanos_veteranos); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Veteranos (+1 año)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <span class="dashicons dashicons-awards" style="font-size: 36px; color: #6f42c1; opacity: 0.3;"></span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="tablenav top" style="background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <form method="get" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'huertos-urbanos-hortelanos'); ?>">

            <div>
                <label style="font-weight: 500; margin-right: 5px;"><?php echo esc_html__('Huerto:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="huerto_id" style="min-width: 180px;">
                    <option value="0"><?php echo esc_html__('Todos los huertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($huertos_disponibles as $huerto): ?>
                        <option value="<?php echo esc_attr($huerto->id); ?>" <?php selected($filtro_huerto, $huerto->id); ?>>
                            <?php echo esc_html($huerto->nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-weight: 500; margin-right: 5px;"><?php echo esc_html__('Buscar:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text" name="busqueda" value="<?php echo esc_attr($filtro_busqueda); ?>"
                       placeholder="<?php echo esc_attr__('Nombre o email...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="min-width: 200px;">
            </div>

            <button type="submit" class="button"><?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

            <?php if ($filtro_huerto > 0 || !empty($filtro_busqueda)): ?>
                <a href="<?php echo admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'huertos-urbanos-hortelanos')); ?>" class="button">
                    <?php echo esc_html__('Limpiar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla de hortelanos -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php echo esc_html__('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 80px; text-align: center;"><?php echo esc_html__('Parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Miembro desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Antigüedad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hortelanos)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-groups" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666; margin-top: 10px;"><?php echo esc_html__('No se encontraron hortelanos con los filtros aplicados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hortelanos as $hortelano):
                        $fecha_primera = strtotime($hortelano->primera_parcela);
                        $dias_antiguedad = floor((time() - $fecha_primera) / 86400);

                        if ($dias_antiguedad > 365) {
                            $antiguedad_texto = floor($dias_antiguedad / 365) . ' ' . __('años', FLAVOR_PLATFORM_TEXT_DOMAIN);
                            $antiguedad_clase = 'veterano';
                        } elseif ($dias_antiguedad > 30) {
                            $antiguedad_texto = floor($dias_antiguedad / 30) . ' ' . __('meses', FLAVOR_PLATFORM_TEXT_DOMAIN);
                            $antiguedad_clase = 'intermedio';
                        } else {
                            $antiguedad_texto = $dias_antiguedad . ' ' . __('días', FLAVOR_PLATFORM_TEXT_DOMAIN);
                            $antiguedad_clase = 'nuevo';
                        }
                    ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($hortelano->ID); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($hortelano->display_name); ?></strong>
                                <?php if ($dias_antiguedad > 365): ?>
                                    <span class="dashicons dashicons-star-filled" style="color: #ffc107; font-size: 14px;" title="<?php echo esc_attr__('Huertano veterano', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($hortelano->user_email); ?>">
                                    <?php echo esc_html($hortelano->user_email); ?>
                                </a>
                            </td>
                            <td>
                                <span style="color: #28a745;"><?php echo esc_html($hortelano->huertos ?: '-'); ?></span>
                            </td>
                            <td style="text-align: center;">
                                <span class="hortelano-parcelas" style="background: #e7f5ea; color: #28a745; padding: 4px 10px; border-radius: 12px; font-weight: 600;">
                                    <?php echo $hortelano->total_parcelas; ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($hortelano->primera_parcela)); ?></td>
                            <td>
                                <span class="antiguedad-badge antiguedad-<?php echo $antiguedad_clase; ?>">
                                    <?php echo esc_html($antiguedad_texto); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $hortelano->ID); ?>"
                                   class="button button-small" title="<?php echo esc_attr__('Ver perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-admin-users" style="vertical-align: text-bottom;"></span>
                                </a>
                                <a href="mailto:<?php echo esc_attr($hortelano->user_email); ?>"
                                   class="button button-small" title="<?php echo esc_attr__('Enviar email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-email" style="vertical-align: text-bottom;"></span>
                                </a>
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
                    <?php printf(
                        esc_html__('%s hortelanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        number_format($total_registros)
                    ); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    $url_base = admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'huertos-urbanos-hortelanos'));
                    if ($filtro_huerto > 0) $url_base .= '&huerto_id=' . $filtro_huerto;
                    if (!empty($filtro_busqueda)) $url_base .= '&busqueda=' . urlencode($filtro_busqueda);

                    if ($pagina_actual > 1): ?>
                        <a class="first-page button" href="<?php echo esc_url($url_base . '&paged=1'); ?>">
                            <span class="screen-reader-text"><?php echo esc_html__('Primera página', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                        <a class="prev-page button" href="<?php echo esc_url($url_base . '&paged=' . ($pagina_actual - 1)); ?>">
                            <span class="screen-reader-text"><?php echo esc_html__('Página anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span aria-hidden="true">&lsaquo;</span>
                        </a>
                    <?php endif; ?>

                    <span class="paging-input">
                        <span class="tablenav-paging-text">
                            <?php echo $pagina_actual; ?> <?php echo esc_html__('de', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <span class="total-pages"><?php echo $total_paginas; ?></span>
                        </span>
                    </span>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a class="next-page button" href="<?php echo esc_url($url_base . '&paged=' . ($pagina_actual + 1)); ?>">
                            <span class="screen-reader-text"><?php echo esc_html__('Página siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span aria-hidden="true">&rsaquo;</span>
                        </a>
                        <a class="last-page button" href="<?php echo esc_url($url_base . '&paged=' . $total_paginas); ?>">
                            <span class="screen-reader-text"><?php echo esc_html__('Última página', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.antiguedad-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}
.antiguedad-nuevo {
    background: #d4edda;
    color: #155724;
}
.antiguedad-intermedio {
    background: #fff3cd;
    color: #856404;
}
.antiguedad-veterano {
    background: #e2d5f1;
    color: #6f42c1;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
}
</style>
