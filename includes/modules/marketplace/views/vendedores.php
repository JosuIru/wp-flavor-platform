<?php
/**
 * Vista Vendedores - Marketplace
 * Panel de administración de vendedores
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Parámetros de filtrado
$busqueda = isset($_GET['busqueda']) ? sanitize_text_field($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'anuncios';
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$por_pagina = 15;
$offset = ($pagina_actual - 1) * $por_pagina;

// Verificar si existe el CPT marketplace_item
$cpt_existe = post_type_exists('marketplace_item');

// Estadísticas y datos
$total_vendedores = 0;
$total_anuncios = 0;
$total_ventas = 0;
$promedio_anuncios = 0;
$vendedores = [];
$top_vendedores = [];
$total_registros = 0;
$total_paginas = 0;

if ($cpt_existe) {
    // Estadísticas globales
    $stats = $wpdb->get_row("
        SELECT
            COUNT(DISTINCT p.post_author) as total_vendedores,
            COUNT(p.ID) as total_anuncios
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'marketplace_item'
        AND p.post_status = 'publish'
    ");

    if ($stats) {
        $total_vendedores = intval($stats->total_vendedores);
        $total_anuncios = intval($stats->total_anuncios);
        $promedio_anuncios = $total_vendedores > 0 ? round($total_anuncios / $total_vendedores, 1) : 0;
    }

    // Intentar obtener ventas si existe la tabla de transacciones
    $tabla_ventas = $wpdb->prefix . 'flavor_marketplace_transacciones';
    $tabla_ventas_existe = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        DB_NAME, $tabla_ventas
    )) > 0;

    if ($tabla_ventas_existe) {
        $total_ventas = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_ventas WHERE estado IN ('completada', 'completado')"
        ));
    }

    // Construir WHERE para filtros
    $where_conditions = ["p.post_type = 'marketplace_item'", "p.post_status = 'publish'"];
    $where_values = [];

    if (!empty($busqueda)) {
        $where_conditions[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
    }

    $where_sql = implode(' AND ', $where_conditions);

    // HAVING para filtro de estado
    $having_sql = "";
    if ($filtro_estado === 'activo') {
        $having_sql = "HAVING total_anuncios >= 3";
    } elseif ($filtro_estado === 'moderado') {
        $having_sql = "HAVING total_anuncios BETWEEN 1 AND 2";
    } elseif ($filtro_estado === 'nuevo') {
        $having_sql = "HAVING total_anuncios = 1";
    }

    // Ordenamiento
    $order_sql = match($orden) {
        'nombre' => 'u.display_name ASC',
        'reciente' => 'ultimo_anuncio DESC',
        'valoracion' => 'valoracion_promedio DESC',
        default => 'total_anuncios DESC'
    };

    // Contar total para paginación
    $count_query = "
        SELECT COUNT(*) FROM (
            SELECT u.ID
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->posts} p ON u.ID = p.post_author
            WHERE $where_sql
            GROUP BY u.ID
            $having_sql
        ) as subquery
    ";

    if (!empty($where_values)) {
        $total_registros = $wpdb->get_var($wpdb->prepare($count_query, ...$where_values));
    } else {
        $total_registros = $wpdb->get_var($count_query);
    }

    $total_paginas = ceil($total_registros / $por_pagina);

    // Obtener vendedores con estadísticas
    $query = "
        SELECT u.ID, u.display_name, u.user_email, u.user_registered,
               COUNT(p.ID) as total_anuncios,
               MAX(p.post_date) as ultimo_anuncio,
               COALESCE(AVG(CAST(pm_val.meta_value AS DECIMAL(3,2))), 0) as valoracion_promedio,
               COUNT(DISTINCT pm_cat.meta_value) as categorias_distintas
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->posts} p ON u.ID = p.post_author
        LEFT JOIN {$wpdb->postmeta} pm_val ON p.ID = pm_val.post_id AND pm_val.meta_key = '_valoracion_vendedor'
        LEFT JOIN {$wpdb->postmeta} pm_cat ON p.ID = pm_cat.post_id AND pm_cat.meta_key = '_categoria_marketplace'
        WHERE $where_sql
        GROUP BY u.ID
        $having_sql
        ORDER BY $order_sql
        LIMIT $por_pagina OFFSET $offset
    ";

    if (!empty($where_values)) {
        $vendedores = $wpdb->get_results($wpdb->prepare($query, ...$where_values));
    } else {
        $vendedores = $wpdb->get_results($query);
    }

    // Top 5 vendedores
    $top_vendedores = $wpdb->get_results("
        SELECT u.ID, u.display_name, COUNT(p.ID) as total_anuncios
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->posts} p ON u.ID = p.post_author
        WHERE p.post_type = 'marketplace_item' AND p.post_status = 'publish'
        GROUP BY u.ID
        ORDER BY total_anuncios DESC
        LIMIT 5
    ");

}

// Función para obtener nivel de vendedor
function obtener_nivel_vendedor($anuncios, $valoracion) {
    if ($anuncios >= 15 && $valoracion >= 4.5) return ['nivel' => 'Premium', 'clase' => 'premium', 'icono' => 'star-filled'];
    if ($anuncios >= 8) return ['nivel' => 'Destacado', 'clase' => 'destacado', 'icono' => 'star-half'];
    if ($anuncios >= 3) return ['nivel' => 'Activo', 'clase' => 'activo', 'icono' => 'marker'];
    return ['nivel' => 'Nuevo', 'clase' => 'nuevo', 'icono' => 'admin-users'];
}

// Función para renderizar estrellas
function renderizar_estrellas_vendedor($valoracion) {
    $html = '<span class="flavor-stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($valoracion >= $i) {
            $html .= '<span class="dashicons dashicons-star-filled" style="color: #f59e0b;"></span>';
        } elseif ($valoracion >= $i - 0.5) {
            $html .= '<span class="dashicons dashicons-star-half" style="color: #f59e0b;"></span>';
        } else {
            $html .= '<span class="dashicons dashicons-star-empty" style="color: #ddd;"></span>';
        }
    }
    $html .= '</span>';
    return $html;
}
?>

<div class="wrap flavor-marketplace-vendedores">
    <h1>
        <span class="dashicons dashicons-groups"></span>
        <?php echo esc_html__('Vendedores del Marketplace', 'flavor-chat-ia'); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if (!$cpt_existe): ?>
    <div class="notice notice-info">
        <p><span class="dashicons dashicons-info"></span> <?php _e('No hay datos disponibles: falta registrar el tipo de contenido marketplace_item.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($total_vendedores); ?></span>
                <span class="flavor-stat-label"><?php _e('Vendedores Activos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <span class="dashicons dashicons-megaphone"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($total_anuncios); ?></span>
                <span class="flavor-stat-label"><?php _e('Anuncios Publicados', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($total_ventas); ?></span>
                <span class="flavor-stat-label"><?php _e('Ventas Completadas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo $promedio_anuncios; ?></span>
                <span class="flavor-stat-label"><?php _e('Promedio Anuncios/Vendedor', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <div class="flavor-layout-grid">
        <!-- Panel Principal -->
        <div class="flavor-main-panel">
            <!-- Filtros -->
            <div class="flavor-filters-bar">
                <form method="get" action="">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
                    <input type="hidden" name="tab" value="<?php echo esc_attr($_GET['tab'] ?? ''); ?>">

                    <div class="flavor-filter-group">
                        <input type="text" name="busqueda" placeholder="<?php esc_attr_e('Buscar vendedor...', 'flavor-chat-ia'); ?>" value="<?php echo esc_attr($busqueda); ?>" class="flavor-search-input">
                    </div>

                    <div class="flavor-filter-group">
                        <select name="estado" class="flavor-select">
                            <option value=""><?php _e('Todos los niveles', 'flavor-chat-ia'); ?></option>
                            <option value="activo" <?php selected($filtro_estado, 'activo'); ?>><?php _e('Vendedores activos (3+)', 'flavor-chat-ia'); ?></option>
                            <option value="moderado" <?php selected($filtro_estado, 'moderado'); ?>><?php _e('Actividad moderada (1-2)', 'flavor-chat-ia'); ?></option>
                            <option value="nuevo" <?php selected($filtro_estado, 'nuevo'); ?>><?php _e('Nuevos (1 anuncio)', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filter-group">
                        <select name="orden" class="flavor-select">
                            <option value="anuncios" <?php selected($orden, 'anuncios'); ?>><?php _e('Más anuncios', 'flavor-chat-ia'); ?></option>
                            <option value="valoracion" <?php selected($orden, 'valoracion'); ?>><?php _e('Mejor valoración', 'flavor-chat-ia'); ?></option>
                            <option value="reciente" <?php selected($orden, 'reciente'); ?>><?php _e('Actividad reciente', 'flavor-chat-ia'); ?></option>
                            <option value="nombre" <?php selected($orden, 'nombre'); ?>><?php _e('Nombre A-Z', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <button type="submit" class="button"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>

                    <?php if (!empty($busqueda) || !empty($filtro_estado)): ?>
                        <a href="?page=<?php echo esc_attr($_GET['page'] ?? ''); ?>&tab=<?php echo esc_attr($_GET['tab'] ?? ''); ?>" class="button"><?php _e('Limpiar', 'flavor-chat-ia'); ?></a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabla de Vendedores -->
            <div class="flavor-card">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th><?php _e('Vendedor', 'flavor-chat-ia'); ?></th>
                            <th style="width: 100px; text-align: center;"><?php _e('Anuncios', 'flavor-chat-ia'); ?></th>
                            <th style="width: 140px;"><?php _e('Valoración', 'flavor-chat-ia'); ?></th>
                            <th style="width: 110px;"><?php _e('Nivel', 'flavor-chat-ia'); ?></th>
                            <th style="width: 120px;"><?php _e('Último Anuncio', 'flavor-chat-ia'); ?></th>
                            <th style="width: 130px;"><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($vendedores)):
                            $posicion = $offset + 1;
                            foreach ($vendedores as $vendedor):
                                $nivel = obtener_nivel_vendedor($vendedor->total_anuncios, $vendedor->valoracion_promedio);
                                $avatar = get_avatar($vendedor->ID, 40);
                        ?>
                        <tr>
                            <td>
                                <span class="flavor-position <?php echo $posicion <= 3 ? 'top-' . $posicion : ''; ?>">
                                    <?php echo $posicion++; ?>
                                </span>
                            </td>
                            <td>
                                <div class="flavor-vendedor-info">
                                    <?php echo $avatar; ?>
                                    <div class="flavor-vendedor-details">
                                        <strong>
                                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $vendedor->ID); ?>">
                                                <?php echo esc_html($vendedor->display_name); ?>
                                            </a>
                                        </strong>
                                        <small><?php echo esc_html($vendedor->user_email); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="flavor-text-center">
                                <span class="flavor-anuncios-count"><?php echo number_format($vendedor->total_anuncios); ?></span>
                                <?php if ($vendedor->categorias_distintas > 1): ?>
                                    <br><small style="color: #666;"><?php printf(__('%d categorías', 'flavor-chat-ia'), $vendedor->categorias_distintas); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo renderizar_estrellas_vendedor($vendedor->valoracion_promedio); ?>
                                <span class="flavor-valoracion-num"><?php echo number_format($vendedor->valoracion_promedio, 1); ?></span>
                            </td>
                            <td>
                                <span class="flavor-badge nivel-<?php echo esc_attr($nivel['clase']); ?>">
                                    <span class="dashicons dashicons-<?php echo esc_attr($nivel['icono']); ?>"></span>
                                    <?php echo esc_html($nivel['nivel']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $fecha_ultimo = strtotime($vendedor->ultimo_anuncio);
                                $dias_desde = floor((time() - $fecha_ultimo) / 86400);
                                if ($dias_desde == 0) {
                                    echo '<span style="color: #10b981;">' . __('Hoy', 'flavor-chat-ia') . '</span>';
                                } elseif ($dias_desde == 1) {
                                    echo __('Ayer', 'flavor-chat-ia');
                                } elseif ($dias_desde < 7) {
                                    printf(__('Hace %d días', 'flavor-chat-ia'), $dias_desde);
                                } elseif ($dias_desde < 30) {
                                    printf(__('Hace %d sem.', 'flavor-chat-ia'), floor($dias_desde / 7));
                                } else {
                                    echo date_i18n('d M Y', $fecha_ultimo);
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('edit.php?post_type=marketplace_item&author=' . $vendedor->ID); ?>" class="button button-small" title="<?php esc_attr_e('Ver anuncios', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-megaphone"></span>
                                </a>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $vendedor->ID); ?>" class="button button-small" title="<?php esc_attr_e('Editar perfil', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-admin-users"></span>
                                </a>
                                <button type="button" class="button button-small btn-contactar" data-user="<?php echo esc_attr($vendedor->ID); ?>" data-email="<?php echo esc_attr($vendedor->user_email); ?>" title="<?php esc_attr_e('Contactar', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-email"></span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <span class="dashicons dashicons-info" style="font-size: 48px; color: #ccc;"></span>
                                <p style="margin-top: 10px; color: #666;"><?php _e('No se encontraron vendedores con los filtros seleccionados', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <?php if (isset($total_paginas) && $total_paginas > 1): ?>
                <div class="flavor-pagination">
                    <span class="flavor-pagination-info">
                        <?php printf(__('Mostrando %d-%d de %d vendedores', 'flavor-chat-ia'),
                            $offset + 1,
                            min($offset + $por_pagina, $total_registros),
                            $total_registros
                        ); ?>
                    </span>
                    <div class="flavor-pagination-links">
                        <?php
                        $url_base = add_query_arg([
                            'page' => $_GET['page'] ?? '',
                            'tab' => $_GET['tab'] ?? '',
                            'busqueda' => $busqueda,
                            'estado' => $filtro_estado,
                            'orden' => $orden
                        ], admin_url('admin.php'));

                        if ($pagina_actual > 1): ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $pagina_actual - 1, $url_base)); ?>" class="button">&laquo; <?php _e('Anterior', 'flavor-chat-ia'); ?></a>
                        <?php endif;

                        for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                            <?php if ($i == $pagina_actual): ?>
                                <span class="button button-primary"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo esc_url(add_query_arg('paged', $i, $url_base)); ?>" class="button"><?php echo $i; ?></a>
                            <?php endif;
                        endfor;

                        if ($pagina_actual < $total_paginas): ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $pagina_actual + 1, $url_base)); ?>" class="button"><?php _e('Siguiente', 'flavor-chat-ia'); ?> &raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="flavor-side-panel">
            <!-- Top Vendedores -->
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h3><span class="dashicons dashicons-awards"></span> <?php _e('Top 5 Vendedores', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="flavor-card-body">
                    <?php if (!empty($top_vendedores)): ?>
                        <ul class="flavor-ranking-list">
                            <?php foreach ($top_vendedores as $index => $top_vendedor):
                                $medalla = match($index) {
                                    0 => '🥇',
                                    1 => '🥈',
                                    2 => '🥉',
                                    default => ($index + 1) . '.'
                                };
                            ?>
                            <li class="flavor-ranking-item">
                                <span class="flavor-ranking-position"><?php echo $medalla; ?></span>
                                <div class="flavor-ranking-info">
                                    <strong><?php echo esc_html($top_vendedor->display_name); ?></strong>
                                    <small><?php printf(__('%d anuncios', 'flavor-chat-ia'), $top_vendedor->total_anuncios); ?></small>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; padding: 20px;"><?php _e('Sin datos disponibles', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Distribución por Nivel -->
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h3><span class="dashicons dashicons-chart-pie"></span> <?php _e('Distribución por Nivel', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="flavor-card-body">
                    <canvas id="grafico-niveles" height="200"></canvas>
                </div>
            </div>

            <!-- Actividad Mensual -->
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h3><span class="dashicons dashicons-chart-area"></span> <?php _e('Nuevos Anuncios (Mensual)', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="flavor-card-body">
                    <canvas id="grafico-actividad" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-marketplace-vendedores { margin: 20px; }

.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.flavor-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: box-shadow 0.2s;
}

.flavor-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

.flavor-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.flavor-stat-icon .dashicons { font-size: 28px; width: 28px; height: 28px; }

.flavor-stat-content { flex: 1; }
.flavor-stat-value { display: block; font-size: 28px; font-weight: 700; color: #1d2327; line-height: 1.2; }
.flavor-stat-label { display: block; font-size: 13px; color: #666; margin-top: 4px; }

.flavor-layout-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 25px;
}

@media (max-width: 1200px) {
    .flavor-layout-grid { grid-template-columns: 1fr; }
}

.flavor-filters-bar {
    background: #fff;
    padding: 15px 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 20px;
}

.flavor-filters-bar form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}

.flavor-search-input {
    padding: 6px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-width: 200px;
}

.flavor-select {
    padding: 6px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.flavor-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
}

.flavor-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    background: #fafafa;
}

.flavor-card-header h3 {
    margin: 0;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-card-body { padding: 15px 20px; }

.flavor-text-center { text-align: center; }

.flavor-position {
    display: inline-flex;
    width: 28px;
    height: 28px;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f3f4f6;
    font-weight: 600;
    font-size: 12px;
}

.flavor-position.top-1 { background: #fef3c7; color: #92400e; }
.flavor-position.top-2 { background: #e5e7eb; color: #374151; }
.flavor-position.top-3 { background: #fed7aa; color: #9a3412; }

.flavor-vendedor-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-vendedor-info img { border-radius: 50%; }

.flavor-vendedor-details strong { display: block; }
.flavor-vendedor-details small { color: #666; }

.flavor-anuncios-count {
    font-size: 18px;
    font-weight: 700;
    color: #2271b1;
}

.flavor-stars .dashicons {
    width: 16px;
    height: 16px;
    font-size: 16px;
}

.flavor-valoracion-num {
    display: inline-block;
    margin-left: 8px;
    font-weight: 600;
    color: #1d2327;
}

.flavor-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-badge .dashicons { width: 14px; height: 14px; font-size: 14px; }

.flavor-badge.nivel-premium { background: #fef3c7; color: #92400e; }
.flavor-badge.nivel-destacado { background: #dbeafe; color: #1e40af; }
.flavor-badge.nivel-activo { background: #d1fae5; color: #065f46; }
.flavor-badge.nivel-nuevo { background: #f3f4f6; color: #6b7280; }

.flavor-pagination {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.flavor-pagination-info { color: #666; font-size: 13px; }
.flavor-pagination-links { display: flex; gap: 5px; }

.flavor-ranking-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-ranking-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.flavor-ranking-item:last-child { border-bottom: none; }

.flavor-ranking-position {
    font-size: 18px;
    width: 30px;
    text-align: center;
}

.flavor-ranking-info { flex: 1; }
.flavor-ranking-info strong { display: block; font-size: 13px; }
.flavor-ranking-info small { color: #666; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    // Gráfico de distribución por nivel
    const ctxNiveles = document.getElementById('grafico-niveles');
    if (ctxNiveles) {
        new Chart(ctxNiveles.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['<?php _e('Premium', 'flavor-chat-ia'); ?>', '<?php _e('Destacado', 'flavor-chat-ia'); ?>', '<?php _e('Activo', 'flavor-chat-ia'); ?>', '<?php _e('Nuevo', 'flavor-chat-ia'); ?>'],
                datasets: [{
                    data: [5, 12, 15, 10],
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#9ca3af']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, font: { size: 11 } }
                    }
                }
            }
        });
    }

    // Gráfico de actividad mensual
    const ctxActividad = document.getElementById('grafico-actividad');
    if (ctxActividad) {
        new Chart(ctxActividad.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['<?php _e('Ene', 'flavor-chat-ia'); ?>', '<?php _e('Feb', 'flavor-chat-ia'); ?>', '<?php _e('Mar', 'flavor-chat-ia'); ?>', '<?php _e('Abr', 'flavor-chat-ia'); ?>', '<?php _e('May', 'flavor-chat-ia'); ?>', '<?php _e('Jun', 'flavor-chat-ia'); ?>'],
                datasets: [{
                    label: '<?php _e('Anuncios', 'flavor-chat-ia'); ?>',
                    data: [12, 19, 15, 25, 22, 30],
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // Botón contactar
    $('.btn-contactar').on('click', function() {
        const email = $(this).data('email');
        window.location.href = 'mailto:' + email;
    });
});
</script>
