<?php
/**
 * Vista Gestión de Préstamos - Biblioteca
 * Panel de administración de préstamos de libros
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

// Verificar existencia de tablas
$tablas_existen = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name IN (%s, %s)",
    DB_NAME, $tabla_prestamos, $tabla_libros
)) >= 2;

// Parámetros de filtrado
$busqueda = isset($_GET['busqueda']) ? sanitize_text_field($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'activo';
$orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'reciente';
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$por_pagina = 15;
$offset = ($pagina_actual - 1) * $por_pagina;

// Estadísticas y datos
$total_prestamos = 0;
$prestamos_activos = 0;
$prestamos_retrasados = 0;
$devueltos_este_mes = 0;
$prestamos = [];

if ($tablas_existen) {
    // Estadísticas globales
    $stats = $wpdb->get_row("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
            SUM(CASE WHEN estado = 'activo' AND fecha_devolucion_prevista < NOW() THEN 1 ELSE 0 END) as retrasados,
            SUM(CASE WHEN estado = 'devuelto' AND MONTH(fecha_devolucion_real) = MONTH(NOW()) AND YEAR(fecha_devolucion_real) = YEAR(NOW()) THEN 1 ELSE 0 END) as devueltos_mes
        FROM $tabla_prestamos
    ");

    if ($stats) {
        $total_prestamos = intval($stats->total);
        $prestamos_activos = intval($stats->activos);
        $prestamos_retrasados = intval($stats->retrasados);
        $devueltos_este_mes = intval($stats->devueltos_mes);
    }

    // Construir WHERE para filtros
    $where_conditions = ["1=1"];
    $where_values = [];

    if (!empty($busqueda)) {
        $where_conditions[] = "(l.titulo LIKE %s OR u1.display_name LIKE %s OR u2.display_name LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
    }

    if ($filtro_estado === 'retrasado') {
        $where_conditions[] = "p.estado = 'activo' AND p.fecha_devolucion_prevista < NOW()";
    } elseif (!empty($filtro_estado)) {
        $where_conditions[] = "p.estado = %s";
        $where_values[] = $filtro_estado;
    }

    $where_sql = implode(' AND ', $where_conditions);

    // Ordenamiento
    $order_sql = match($orden) {
        'libro' => 'l.titulo ASC',
        'devolucion' => 'p.fecha_devolucion_prevista ASC',
        'prestamista' => 'u1.display_name ASC',
        default => 'p.fecha_prestamo DESC'
    };

    // Contar total para paginación
    $count_query = "
        SELECT COUNT(*)
        FROM $tabla_prestamos p
        INNER JOIN $tabla_libros l ON p.libro_id = l.id
        INNER JOIN {$wpdb->users} u1 ON p.prestamista_id = u1.ID
        INNER JOIN {$wpdb->users} u2 ON p.prestatario_id = u2.ID
        WHERE $where_sql
    ";

    if (!empty($where_values)) {
        $total_registros = $wpdb->get_var($wpdb->prepare($count_query, ...$where_values));
    } else {
        $total_registros = $wpdb->get_var($count_query);
    }

    $total_paginas = ceil($total_registros / $por_pagina);

    // Obtener préstamos
    $query = "
        SELECT p.*,
               l.titulo as libro_titulo,
               l.autor as libro_autor,
               l.imagen_url as libro_imagen,
               u1.display_name as prestamista,
               u1.user_email as prestamista_email,
               u1.ID as prestamista_id,
               u2.display_name as prestatario,
               u2.user_email as prestatario_email,
               u2.ID as prestatario_id,
               DATEDIFF(p.fecha_devolucion_prevista, NOW()) as dias_restantes,
               DATEDIFF(NOW(), p.fecha_prestamo) as dias_prestado
        FROM $tabla_prestamos p
        INNER JOIN $tabla_libros l ON p.libro_id = l.id
        INNER JOIN {$wpdb->users} u1 ON p.prestamista_id = u1.ID
        INNER JOIN {$wpdb->users} u2 ON p.prestatario_id = u2.ID
        WHERE $where_sql
        ORDER BY $order_sql
        LIMIT $por_pagina OFFSET $offset
    ";

    if (!empty($where_values)) {
        $prestamos = $wpdb->get_results($wpdb->prepare($query, ...$where_values));
    } else {
        $prestamos = $wpdb->get_results($query);
    }

} else {
    $total_registros = 0;
    $total_paginas = 0;
    $prestamos = [];
}

// Función para obtener estado visual del préstamo
function obtener_estado_prestamo($dias_restantes, $estado) {
    if ($estado === 'devuelto') return ['clase' => 'devuelto', 'texto' => __('Devuelto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'yes-alt'];
    if ($estado === 'perdido') return ['clase' => 'perdido', 'texto' => __('Perdido', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'warning'];
    if ($dias_restantes < 0) return ['clase' => 'retrasado', 'texto' => sprintf(__('%d días retraso', FLAVOR_PLATFORM_TEXT_DOMAIN), abs($dias_restantes)), 'icono' => 'warning'];
    if ($dias_restantes == 0) return ['clase' => 'vence-hoy', 'texto' => __('Vence hoy', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'clock'];
    if ($dias_restantes <= 3) return ['clase' => 'urgente', 'texto' => sprintf(__('%d días', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias_restantes), 'icono' => 'clock'];
    return ['clase' => 'normal', 'texto' => sprintf(__('%d días', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias_restantes), 'icono' => 'calendar-alt'];
}
?>

<div class="wrap flavor-biblioteca-prestamos">
    <h1>
        <span class="dashicons dashicons-randomize"></span>
        <?php echo esc_html__('Gestión de Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                <span class="dashicons dashicons-book-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($total_prestamos); ?></span>
                <span class="flavor-stat-label"><?php _e('Total Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <span class="dashicons dashicons-randomize"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($prestamos_activos); ?></span>
                <span class="flavor-stat-label"><?php _e('Préstamos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($prestamos_retrasados); ?></span>
                <span class="flavor-stat-label"><?php _e('Con Retraso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($devueltos_este_mes); ?></span>
                <span class="flavor-stat-label"><?php _e('Devueltos Este Mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="flavor-filters-bar">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
            <input type="hidden" name="tab" value="<?php echo esc_attr($_GET['tab'] ?? ''); ?>">

            <div class="flavor-filter-group">
                <input type="text" name="busqueda" placeholder="<?php esc_attr_e('Buscar libro o usuario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" value="<?php echo esc_attr($busqueda); ?>" class="flavor-search-input">
            </div>

            <div class="flavor-filter-group">
                <select name="estado" class="flavor-select">
                    <option value="activo" <?php selected($filtro_estado, 'activo'); ?>><?php _e('Préstamos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="retrasado" <?php selected($filtro_estado, 'retrasado'); ?>><?php _e('Con retraso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="devuelto" <?php selected($filtro_estado, 'devuelto'); ?>><?php _e('Devueltos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="perdido" <?php selected($filtro_estado, 'perdido'); ?>><?php _e('Perdidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="" <?php selected($filtro_estado, ''); ?>><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <div class="flavor-filter-group">
                <select name="orden" class="flavor-select">
                    <option value="reciente" <?php selected($orden, 'reciente'); ?>><?php _e('Más recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="devolucion" <?php selected($orden, 'devolucion'); ?>><?php _e('Próximos a vencer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="libro" <?php selected($orden, 'libro'); ?>><?php _e('Por libro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="prestamista" <?php selected($orden, 'prestamista'); ?>><?php _e('Por prestamista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

            <?php if (!empty($busqueda) || $filtro_estado !== 'activo'): ?>
                <a href="?page=<?php echo esc_attr($_GET['page'] ?? ''); ?>&tab=<?php echo esc_attr($_GET['tab'] ?? ''); ?>" class="button"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla de Préstamos -->
    <div class="flavor-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php _e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Libro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 160px;"><?php _e('Prestamista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 160px;"><?php _e('Prestatario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 100px;"><?php _e('Prestado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 120px;"><?php _e('Devolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 130px;"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($prestamos)): ?>
                    <?php foreach ($prestamos as $prestamo):
                        $estado_visual = obtener_estado_prestamo($prestamo->dias_restantes, $prestamo->estado);
                        $avatar_prestamista = get_avatar($prestamo->prestamista_id, 28);
                        $avatar_prestatario = get_avatar($prestamo->prestatario_id, 28);
                        $es_retrasado = $prestamo->estado === 'activo' && $prestamo->dias_restantes < 0;
                    ?>
                    <tr class="<?php echo $es_retrasado ? 'prestamo-retrasado' : ''; ?>">
                        <td>
                            <strong>#<?php echo esc_html($prestamo->id); ?></strong>
                        </td>
                        <td>
                            <div class="flavor-libro-info">
                                <div class="flavor-libro-icono">
                                    <span class="dashicons dashicons-book"></span>
                                </div>
                                <div class="flavor-libro-detalles">
                                    <strong><?php echo esc_html($prestamo->libro_titulo); ?></strong>
                                    <small><?php echo esc_html($prestamo->libro_autor); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flavor-usuario-mini">
                                <?php echo $avatar_prestamista; ?>
                                <div>
                                    <strong><?php echo esc_html($prestamo->prestamista); ?></strong>
                                    <span class="flavor-rol-badge"><?php _e('Presta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flavor-usuario-mini">
                                <?php echo $avatar_prestatario; ?>
                                <div>
                                    <strong><?php echo esc_html($prestamo->prestatario); ?></strong>
                                    <span class="flavor-rol-badge receptor"><?php _e('Recibe', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php echo date_i18n('d M', strtotime($prestamo->fecha_prestamo)); ?>
                            <br><small style="color: #666;"><?php printf(__('%d días', FLAVOR_PLATFORM_TEXT_DOMAIN), $prestamo->dias_prestado); ?></small>
                        </td>
                        <td>
                            <span class="flavor-estado-prestamo <?php echo esc_attr($estado_visual['clase']); ?>">
                                <span class="dashicons dashicons-<?php echo esc_attr($estado_visual['icono']); ?>"></span>
                                <?php echo esc_html($estado_visual['texto']); ?>
                            </span>
                            <br><small style="color: #666;"><?php echo date_i18n('d M', strtotime($prestamo->fecha_devolucion_prevista)); ?></small>
                        </td>
                        <td>
                            <?php if ($prestamo->estado === 'activo'): ?>
                                <button type="button" class="button button-small button-primary btn-devolver" data-id="<?php echo esc_attr($prestamo->id); ?>" title="<?php esc_attr_e('Registrar devolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-yes"></span>
                                </button>
                                <?php if ($es_retrasado): ?>
                                    <button type="button" class="button button-small btn-recordatorio" data-id="<?php echo esc_attr($prestamo->id); ?>" data-email="<?php echo esc_attr($prestamo->prestatario_email); ?>" title="<?php esc_attr_e('Enviar recordatorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-email-alt"></span>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <button type="button" class="button button-small btn-ver-detalles" data-id="<?php echo esc_attr($prestamo->id); ?>" title="<?php esc_attr_e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-randomize" style="font-size: 48px; color: #ccc;"></span>
                            <p style="margin-top: 10px; color: #666;"><?php _e('No hay préstamos con los filtros seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if (isset($total_paginas) && $total_paginas > 1): ?>
        <div class="flavor-pagination">
            <span class="flavor-pagination-info">
                <?php printf(__('Mostrando %d-%d de %d préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN),
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

<style>
.flavor-biblioteca-prestamos { margin: 20px; }

.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.flavor-stat-icon .dashicons { font-size: 24px; width: 24px; height: 24px; }

.flavor-stat-content { flex: 1; }
.flavor-stat-value { display: block; font-size: 26px; font-weight: 700; color: #1d2327; line-height: 1.2; }
.flavor-stat-label { display: block; font-size: 12px; color: #666; margin-top: 4px; }

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
}

.flavor-libro-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-libro-icono {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.flavor-libro-detalles strong { display: block; font-size: 13px; }
.flavor-libro-detalles small { color: #666; font-size: 11px; }

.flavor-usuario-mini {
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-usuario-mini img { border-radius: 50%; }
.flavor-usuario-mini strong { display: block; font-size: 12px; }

.flavor-rol-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    background: #dbeafe;
    color: #1e40af;
}

.flavor-rol-badge.receptor {
    background: #d1fae5;
    color: #065f46;
}

.flavor-estado-prestamo {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-estado-prestamo .dashicons { width: 14px; height: 14px; font-size: 14px; }

.flavor-estado-prestamo.normal { background: #d1fae5; color: #065f46; }
.flavor-estado-prestamo.urgente { background: #fef3c7; color: #92400e; }
.flavor-estado-prestamo.vence-hoy { background: #fed7aa; color: #9a3412; }
.flavor-estado-prestamo.retrasado { background: #fee2e2; color: #991b1b; }
.flavor-estado-prestamo.devuelto { background: #dbeafe; color: #1e40af; }
.flavor-estado-prestamo.perdido { background: #fef2f2; color: #991b1b; }

.prestamo-retrasado { background: #fef2f2 !important; }

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

.wp-list-table .button-small .dashicons {
    width: 16px;
    height: 16px;
    font-size: 16px;
    line-height: 1;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Registrar devolución
    $('.btn-devolver').on('click', function() {
        const id = $(this).data('id');
        if (confirm('<?php _e('¿Confirmar la devolución de este libro?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>')) {
            $.post(ajaxurl, {
                action: 'biblioteca_devolver_libro',
                prestamo_id: id,
                _wpnonce: '<?php echo wp_create_nonce('biblioteca_prestamo'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || '<?php _e('Error al registrar devolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                }
            });
        }
    });

    // Enviar recordatorio
    $('.btn-recordatorio').on('click', function() {
        const id = $(this).data('id');
        const email = $(this).data('email');
        if (confirm('<?php _e('¿Enviar recordatorio de devolución a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> ' + email + '?')) {
            $.post(ajaxurl, {
                action: 'biblioteca_enviar_recordatorio',
                prestamo_id: id,
                _wpnonce: '<?php echo wp_create_nonce('biblioteca_prestamo'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php _e('Recordatorio enviado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                } else {
                    alert(response.data || '<?php _e('Error al enviar recordatorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                }
            });
        }
    });

    // Ver detalles
    $('.btn-ver-detalles').on('click', function() {
        const id = $(this).data('id');
        alert('<?php _e('Detalles del préstamo #', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>' + id + ' - <?php _e('Función en desarrollo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
    });
});
</script>
