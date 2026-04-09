<?php
/**
 * Vista Usuarios Biblioteca - Panel de administración
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
$tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

// Verificar existencia de tablas
$tablas_existen = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name IN (%s, %s)",
    DB_NAME, $tabla_libros, $tabla_prestamos
)) >= 2;

// Parámetros de filtrado
$busqueda = isset($_GET['busqueda']) ? sanitize_text_field($_GET['busqueda']) : '';
$filtro_actividad = isset($_GET['actividad']) ? sanitize_text_field($_GET['actividad']) : '';
$orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'compartidos';
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$por_pagina = 15;
$offset = ($pagina_actual - 1) * $por_pagina;

// Estadísticas y datos
$total_usuarios = 0;
$total_libros_compartidos = 0;
$total_prestamos_activos = 0;
$promedio_libros_usuario = 0;
$usuarios = [];
$top_prestadores = [];

if ($tablas_existen) {
    // Estadísticas globales
    $stats = $wpdb->get_row("
        SELECT
            COUNT(DISTINCT l.propietario_id) as usuarios_activos,
            COUNT(DISTINCT l.id) as total_libros,
            (SELECT COUNT(*) FROM $tabla_prestamos WHERE estado = 'activo') as prestamos_activos
        FROM $tabla_libros l
    ");

    if ($stats) {
        $total_usuarios = intval($stats->usuarios_activos);
        $total_libros_compartidos = intval($stats->total_libros);
        $total_prestamos_activos = intval($stats->prestamos_activos);
        $promedio_libros_usuario = $total_usuarios > 0 ? round($total_libros_compartidos / $total_usuarios, 1) : 0;
    }

    // Construir WHERE para filtros
    $where_conditions = ["(l.id IS NOT NULL OR p1.id IS NOT NULL OR p2.id IS NOT NULL)"];
    $where_values = [];

    if (!empty($busqueda)) {
        $where_conditions[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
    }

    $where_sql = implode(' AND ', $where_conditions);

    // HAVING para filtro de actividad
    $having_sql = "";
    if ($filtro_actividad === 'activo') {
        $having_sql = "HAVING libros_compartidos > 0 OR prestamos_activos > 0";
    } elseif ($filtro_actividad === 'inactivo') {
        $having_sql = "HAVING libros_compartidos = 0 AND prestamos_activos = 0";
    } elseif ($filtro_actividad === 'prestadores') {
        $having_sql = "HAVING libros_prestados > 0";
    } elseif ($filtro_actividad === 'lectores') {
        $having_sql = "HAVING libros_tomados > 0";
    }

    // Ordenamiento
    $order_sql = match($orden) {
        'prestados' => 'libros_prestados DESC',
        'tomados' => 'libros_tomados DESC',
        'nombre' => 'u.display_name ASC',
        default => 'libros_compartidos DESC'
    };

    // Contar total para paginación
    $count_query = "
        SELECT COUNT(*) FROM (
            SELECT u.ID
            FROM {$wpdb->users} u
            LEFT JOIN $tabla_libros l ON u.ID = l.propietario_id
            LEFT JOIN $tabla_prestamos p1 ON u.ID = p1.prestamista_id
            LEFT JOIN $tabla_prestamos p2 ON u.ID = p2.prestatario_id
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

    // Obtener usuarios con estadísticas
    $query = "
        SELECT u.ID, u.display_name, u.user_email, u.user_registered,
               COUNT(DISTINCT l.id) as libros_compartidos,
               COUNT(DISTINCT CASE WHEN p1.estado = 'activo' THEN p1.id END) as prestamos_activos,
               COUNT(DISTINCT p1.id) as libros_prestados,
               COUNT(DISTINCT p2.id) as libros_tomados,
               MAX(GREATEST(
                   COALESCE(l.fecha_creacion, '1970-01-01'),
                   COALESCE(p1.fecha_prestamo, '1970-01-01'),
                   COALESCE(p2.fecha_prestamo, '1970-01-01')
               )) as ultima_actividad
        FROM {$wpdb->users} u
        LEFT JOIN $tabla_libros l ON u.ID = l.propietario_id
        LEFT JOIN $tabla_prestamos p1 ON u.ID = p1.prestamista_id
        LEFT JOIN $tabla_prestamos p2 ON u.ID = p2.prestatario_id
        WHERE $where_sql
        GROUP BY u.ID
        $having_sql
        ORDER BY $order_sql
        LIMIT $por_pagina OFFSET $offset
    ";

    if (!empty($where_values)) {
        $usuarios = $wpdb->get_results($wpdb->prepare($query, ...$where_values));
    } else {
        $usuarios = $wpdb->get_results($query);
    }

    // Top 5 prestadores
    $top_prestadores = $wpdb->get_results("
        SELECT u.ID, u.display_name, COUNT(DISTINCT p.id) as total_prestamos
        FROM {$wpdb->users} u
        INNER JOIN $tabla_prestamos p ON u.ID = p.prestamista_id
        GROUP BY u.ID
        ORDER BY total_prestamos DESC
        LIMIT 5
    ");

} else {
    $total_registros = 0;
    $total_paginas = 0;
    $usuarios = [];
    $top_prestadores = [];
}

// Función para calcular nivel de actividad
function obtener_nivel_actividad_biblioteca($libros, $prestamos) {
    $score = $libros * 2 + $prestamos;
    if ($score >= 30) return ['nivel' => 'Muy Activo', 'clase' => 'muy-activo'];
    if ($score >= 15) return ['nivel' => 'Activo', 'clase' => 'activo'];
    if ($score >= 5) return ['nivel' => 'Moderado', 'clase' => 'moderado'];
    return ['nivel' => 'Nuevo', 'clase' => 'nuevo'];
}

$niveles_distribucion = [0, 0, 0, 0];
foreach ($usuarios as $usuario_item) {
    $score = ((int) ($usuario_item->libros_compartidos ?? 0) * 2) + (int) ($usuario_item->libros_prestados ?? 0);
    if ($score >= 30) {
        $niveles_distribucion[0]++;
    } elseif ($score >= 15) {
        $niveles_distribucion[1]++;
    } elseif ($score >= 5) {
        $niveles_distribucion[2]++;
    } else {
        $niveles_distribucion[3]++;
    }
}
?>

<div class="wrap flavor-biblioteca-usuarios">
    <h1>
        <span class="dashicons dashicons-groups"></span>
        <?php echo esc_html__('Usuarios de la Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if (!$tablas_existen): ?>
    <div class="notice notice-info">
        <p><span class="dashicons dashicons-info"></span> <?php _e('No se han encontrado las tablas requeridas de biblioteca. Mostrando únicamente datos reales disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($total_usuarios); ?></span>
                <span class="flavor-stat-label"><?php _e('Usuarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <span class="dashicons dashicons-book-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($total_libros_compartidos); ?></span>
                <span class="flavor-stat-label"><?php _e('Libros Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <span class="dashicons dashicons-randomize"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($total_prestamos_activos); ?></span>
                <span class="flavor-stat-label"><?php _e('Préstamos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo $promedio_libros_usuario; ?></span>
                <span class="flavor-stat-label"><?php _e('Promedio Libros/Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
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
                        <input type="text" name="busqueda" placeholder="<?php esc_attr_e('Buscar usuario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" value="<?php echo esc_attr($busqueda); ?>" class="flavor-search-input">
                    </div>

                    <div class="flavor-filter-group">
                        <select name="actividad" class="flavor-select">
                            <option value=""><?php _e('Toda actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="activo" <?php selected($filtro_actividad, 'activo'); ?>><?php _e('Usuarios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="inactivo" <?php selected($filtro_actividad, 'inactivo'); ?>><?php _e('Usuarios inactivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="prestadores" <?php selected($filtro_actividad, 'prestadores'); ?>><?php _e('Han prestado libros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="lectores" <?php selected($filtro_actividad, 'lectores'); ?>><?php _e('Han tomado libros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="flavor-filter-group">
                        <select name="orden" class="flavor-select">
                            <option value="compartidos" <?php selected($orden, 'compartidos'); ?>><?php _e('Más libros compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="prestados" <?php selected($orden, 'prestados'); ?>><?php _e('Más préstamos realizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="tomados" <?php selected($orden, 'tomados'); ?>><?php _e('Más libros leídos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="nombre" <?php selected($orden, 'nombre'); ?>><?php _e('Nombre A-Z', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

                    <?php if (!empty($busqueda) || !empty($filtro_actividad)): ?>
                        <a href="?page=<?php echo esc_attr($_GET['page'] ?? ''); ?>&tab=<?php echo esc_attr($_GET['tab'] ?? ''); ?>" class="button"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabla de Usuarios -->
            <div class="flavor-card">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php _e('Foto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 100px; text-align: center;"><?php _e('Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 100px; text-align: center;"><?php _e('Prestados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 100px; text-align: center;"><?php _e('Leídos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 110px;"><?php _e('Nivel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 100px;"><?php _e('Última Act.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 100px;"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $usuario):
                                $nivel = obtener_nivel_actividad_biblioteca($usuario->libros_compartidos, $usuario->libros_prestados);
                                $avatar = get_avatar($usuario->ID, 40);
                            ?>
                            <tr>
                                <td><?php echo $avatar; ?></td>
                                <td>
                                    <strong><?php echo esc_html($usuario->display_name); ?></strong>
                                    <br>
                                    <small style="color: #666;"><?php echo esc_html($usuario->user_email); ?></small>
                                </td>
                                <td class="flavor-text-center">
                                    <span class="flavor-counter books"><?php echo number_format($usuario->libros_compartidos); ?></span>
                                </td>
                                <td class="flavor-text-center">
                                    <span class="flavor-counter prestados">
                                        <?php echo number_format($usuario->libros_prestados); ?>
                                        <?php if ($usuario->prestamos_activos > 0): ?>
                                            <small style="color: #f59e0b;">(<?php echo $usuario->prestamos_activos; ?> activos)</small>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="flavor-text-center">
                                    <span class="flavor-counter leidos"><?php echo number_format($usuario->libros_tomados); ?></span>
                                </td>
                                <td>
                                    <span class="flavor-badge nivel-<?php echo esc_attr($nivel['clase']); ?>">
                                        <?php echo esc_html($nivel['nivel']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $fecha_actividad = strtotime($usuario->ultima_actividad);
                                    $dias_desde = floor((time() - $fecha_actividad) / 86400);
                                    if ($dias_desde == 0) {
                                        echo '<span style="color: #10b981;">' . __('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span>';
                                    } elseif ($dias_desde == 1) {
                                        echo __('Ayer', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                    } elseif ($dias_desde < 7) {
                                        printf(__('Hace %d días', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias_desde);
                                    } elseif ($dias_desde < 30) {
                                        printf(__('Hace %d sem.', FLAVOR_PLATFORM_TEXT_DOMAIN), floor($dias_desde / 7));
                                    } else {
                                        echo date_i18n('d M Y', $fecha_actividad);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $usuario->ID); ?>" class="button button-small" title="<?php esc_attr_e('Ver perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-admin-users"></span>
                                    </a>
                                    <button type="button" class="button button-small btn-ver-historial" data-user="<?php echo esc_attr($usuario->ID); ?>" title="<?php esc_attr_e('Ver historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-list-view"></span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <span class="dashicons dashicons-info" style="font-size: 48px; color: #ccc;"></span>
                                    <p style="margin-top: 10px; color: #666;"><?php _e('No se encontraron usuarios con los filtros seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <?php if (isset($total_paginas) && $total_paginas > 1): ?>
                <div class="flavor-pagination">
                    <span class="flavor-pagination-info">
                        <?php printf(__('Mostrando %d-%d de %d usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
                            'actividad' => $filtro_actividad,
                            'orden' => $orden
                        ], admin_url('admin.php'));

                        if ($pagina_actual > 1): ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $pagina_actual - 1, $url_base)); ?>" class="button">&laquo; <?php _e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        <?php endif;

                        for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                            <?php if ($i == $pagina_actual): ?>
                                <span class="button button-primary"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo esc_url(add_query_arg('paged', $i, $url_base)); ?>" class="button"><?php echo $i; ?></a>
                            <?php endif;
                        endfor;

                        if ($pagina_actual < $total_paginas): ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $pagina_actual + 1, $url_base)); ?>" class="button"><?php _e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> &raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="flavor-side-panel">
            <!-- Top Prestadores -->
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h3><span class="dashicons dashicons-awards"></span> <?php _e('Top 5 Prestadores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="flavor-card-body">
                    <?php if (!empty($top_prestadores)): ?>
                        <ul class="flavor-ranking-list">
                            <?php foreach ($top_prestadores as $index => $prestador):
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
                                    <strong><?php echo esc_html($prestador->display_name); ?></strong>
                                    <small><?php printf(__('%d préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN), $prestador->total_prestamos); ?></small>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; padding: 20px;"><?php _e('Sin datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Distribución por Nivel -->
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h3><span class="dashicons dashicons-chart-pie"></span> <?php _e('Distribución por Nivel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="flavor-card-body">
                    <canvas id="grafico-niveles" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-biblioteca-usuarios { margin: 20px; }

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

.flavor-counter {
    display: inline-block;
    font-weight: 600;
    font-size: 16px;
}

.flavor-counter.books { color: #3b82f6; }
.flavor-counter.prestados { color: #10b981; }
.flavor-counter.leidos { color: #8b5cf6; }

.flavor-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-badge.nivel-muy-activo { background: #d1fae5; color: #065f46; }
.flavor-badge.nivel-activo { background: #dbeafe; color: #1e40af; }
.flavor-badge.nivel-moderado { background: #fef3c7; color: #92400e; }
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

.wp-list-table img { border-radius: 50%; }
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
                labels: ['<?php _e('Muy Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', '<?php _e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', '<?php _e('Moderado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>', '<?php _e('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>'],
                datasets: [{
                    data: [<?php echo esc_js(implode(', ', array_map('intval', $niveles_distribucion))); ?>],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#9ca3af']
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

    // Botón ver historial
    $('.btn-ver-historial').on('click', function() {
        const userId = $(this).data('user');
        alert('<?php _e('Función de historial en desarrollo para usuario #', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>' + userId);
    });
});
</script>
