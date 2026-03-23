<?php
/**
 * Vista Gestión de Reservas - Biblioteca
 * Panel de administración de reservas de libros
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

// Verificar existencia de tablas
$tablas_existen = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name IN (%s, %s)",
    DB_NAME, $tabla_reservas, $tabla_libros
)) >= 2;

// Parámetros de filtrado
$busqueda = isset($_GET['busqueda']) ? sanitize_text_field($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$orden = isset($_GET['orden']) ? sanitize_text_field($_GET['orden']) : 'reciente';
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$por_pagina = 15;
$offset = ($pagina_actual - 1) * $por_pagina;

// Estadísticas y datos
$total_reservas = 0;
$reservas_pendientes = 0;
$reservas_confirmadas = 0;
$reservas_expiradas = 0;
$reservas = [];

if ($tablas_existen) {
    // Estadísticas globales
    $stats = $wpdb->get_row("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
            SUM(CASE WHEN estado IN ('pendiente', 'confirmada') AND fecha_expiracion < NOW() THEN 1 ELSE 0 END) as expiradas
        FROM $tabla_reservas
    ");

    if ($stats) {
        $total_reservas = intval($stats->total);
        $reservas_pendientes = intval($stats->pendientes);
        $reservas_confirmadas = intval($stats->confirmadas);
        $reservas_expiradas = intval($stats->expiradas);
    }

    // Construir WHERE para filtros
    $where_conditions = ["1=1"];
    $where_values = [];

    if (!empty($busqueda)) {
        $where_conditions[] = "(l.titulo LIKE %s OR u.display_name LIKE %s)";
        $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
        $where_values[] = $busqueda_like;
        $where_values[] = $busqueda_like;
    }

    if (!empty($filtro_estado)) {
        if ($filtro_estado === 'expirada') {
            $where_conditions[] = "r.estado IN ('pendiente', 'confirmada') AND r.fecha_expiracion < NOW()";
        } else {
            $where_conditions[] = "r.estado = %s";
            $where_values[] = $filtro_estado;
        }
    } else {
        $where_conditions[] = "r.estado IN ('pendiente', 'confirmada')";
    }

    $where_sql = implode(' AND ', $where_conditions);

    // Ordenamiento
    $order_sql = match($orden) {
        'libro' => 'l.titulo ASC',
        'usuario' => 'u.display_name ASC',
        'expiracion' => 'r.fecha_expiracion ASC',
        default => 'r.fecha_solicitud DESC'
    };

    // Contar total para paginación
    $count_query = "
        SELECT COUNT(*)
        FROM $tabla_reservas r
        INNER JOIN $tabla_libros l ON r.libro_id = l.id
        INNER JOIN {$wpdb->users} u ON r.usuario_id = u.ID
        WHERE $where_sql
    ";

    if (!empty($where_values)) {
        $total_registros = $wpdb->get_var($wpdb->prepare($count_query, ...$where_values));
    } else {
        $total_registros = $wpdb->get_var($count_query);
    }

    $total_paginas = ceil($total_registros / $por_pagina);

    // Obtener reservas
    $query = "
        SELECT r.*,
               l.titulo as libro_titulo,
               l.autor as libro_autor,
               l.imagen_url as libro_imagen,
               u.display_name as usuario_nombre,
               u.user_email as usuario_email,
               u.ID as usuario_id,
               DATEDIFF(r.fecha_expiracion, NOW()) as dias_restantes
        FROM $tabla_reservas r
        INNER JOIN $tabla_libros l ON r.libro_id = l.id
        INNER JOIN {$wpdb->users} u ON r.usuario_id = u.ID
        WHERE $where_sql
        ORDER BY $order_sql
        LIMIT $por_pagina OFFSET $offset
    ";

    if (!empty($where_values)) {
        $reservas = $wpdb->get_results($wpdb->prepare($query, ...$where_values));
    } else {
        $reservas = $wpdb->get_results($query);
    }

} else {
    $total_registros = 0;
    $total_paginas = 0;
    $reservas = [];
}

// Función para obtener clase de urgencia
function obtener_urgencia_reserva($dias_restantes, $estado) {
    if ($dias_restantes < 0) return ['clase' => 'expirada', 'texto' => __('Expirada', 'flavor-chat-ia'), 'icono' => 'warning'];
    if ($dias_restantes == 0) return ['clase' => 'hoy', 'texto' => __('Expira hoy', 'flavor-chat-ia'), 'icono' => 'clock'];
    if ($dias_restantes <= 2) return ['clase' => 'urgente', 'texto' => sprintf(__('%d días', 'flavor-chat-ia'), $dias_restantes), 'icono' => 'clock'];
    return ['clase' => 'normal', 'texto' => sprintf(__('%d días', 'flavor-chat-ia'), $dias_restantes), 'icono' => 'calendar-alt'];
}
?>

<div class="wrap flavor-biblioteca-reservas">
    <h1>
        <span class="dashicons dashicons-book"></span>
        <?php echo esc_html__('Gestión de Reservas', 'flavor-chat-ia'); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if (!$tablas_existen): ?>
    <div class="notice notice-info">
        <p><span class="dashicons dashicons-info"></span> <?php _e('No se han encontrado las tablas requeridas de biblioteca. Mostrando únicamente datos reales disponibles.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <span class="dashicons dashicons-book-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($total_reservas); ?></span>
                <span class="flavor-stat-label"><?php _e('Total Reservas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($reservas_pendientes); ?></span>
                <span class="flavor-stat-label"><?php _e('Pendientes', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($reservas_confirmadas); ?></span>
                <span class="flavor-stat-label"><?php _e('Confirmadas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="flavor-stat-content">
                <span class="flavor-stat-value"><?php echo number_format($reservas_expiradas); ?></span>
                <span class="flavor-stat-label"><?php _e('Expiradas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="flavor-filters-bar">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
            <input type="hidden" name="tab" value="<?php echo esc_attr($_GET['tab'] ?? ''); ?>">

            <div class="flavor-filter-group">
                <input type="text" name="busqueda" placeholder="<?php esc_attr_e('Buscar libro o usuario...', 'flavor-chat-ia'); ?>" value="<?php echo esc_attr($busqueda); ?>" class="flavor-search-input">
            </div>

            <div class="flavor-filter-group">
                <select name="estado" class="flavor-select">
                    <option value=""><?php _e('Activas (Pendientes y Confirmadas)', 'flavor-chat-ia'); ?></option>
                    <option value="pendiente" <?php selected($filtro_estado, 'pendiente'); ?>><?php _e('Solo pendientes', 'flavor-chat-ia'); ?></option>
                    <option value="confirmada" <?php selected($filtro_estado, 'confirmada'); ?>><?php _e('Solo confirmadas', 'flavor-chat-ia'); ?></option>
                    <option value="expirada" <?php selected($filtro_estado, 'expirada'); ?>><?php _e('Expiradas', 'flavor-chat-ia'); ?></option>
                    <option value="cancelada" <?php selected($filtro_estado, 'cancelada'); ?>><?php _e('Canceladas', 'flavor-chat-ia'); ?></option>
                    <option value="completada" <?php selected($filtro_estado, 'completada'); ?>><?php _e('Completadas', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="flavor-filter-group">
                <select name="orden" class="flavor-select">
                    <option value="reciente" <?php selected($orden, 'reciente'); ?>><?php _e('Más recientes', 'flavor-chat-ia'); ?></option>
                    <option value="expiracion" <?php selected($orden, 'expiracion'); ?>><?php _e('Próximas a expirar', 'flavor-chat-ia'); ?></option>
                    <option value="libro" <?php selected($orden, 'libro'); ?>><?php _e('Por libro', 'flavor-chat-ia'); ?></option>
                    <option value="usuario" <?php selected($orden, 'usuario'); ?>><?php _e('Por usuario', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <button type="submit" class="button"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>

            <?php if (!empty($busqueda) || !empty($filtro_estado)): ?>
                <a href="?page=<?php echo esc_attr($_GET['page'] ?? ''); ?>&tab=<?php echo esc_attr($_GET['tab'] ?? ''); ?>" class="button"><?php _e('Limpiar', 'flavor-chat-ia'); ?></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla de Reservas -->
    <div class="flavor-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php _e('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Libro', 'flavor-chat-ia'); ?></th>
                    <th style="width: 180px;"><?php _e('Usuario', 'flavor-chat-ia'); ?></th>
                    <th style="width: 130px;"><?php _e('Fecha Solicitud', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php _e('Expira en', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                    <th style="width: 150px;"><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reservas)): ?>
                    <?php foreach ($reservas as $reserva):
                        $urgencia = obtener_urgencia_reserva($reserva->dias_restantes, $reserva->estado);
                        $avatar = get_avatar($reserva->usuario_id, 32);
                    ?>
                    <tr class="<?php echo $reserva->dias_restantes < 0 ? 'reserva-expirada' : ''; ?>">
                        <td>
                            <strong>#<?php echo esc_html($reserva->id); ?></strong>
                        </td>
                        <td>
                            <div class="flavor-libro-info">
                                <div class="flavor-libro-icono">
                                    <span class="dashicons dashicons-book"></span>
                                </div>
                                <div class="flavor-libro-detalles">
                                    <strong><?php echo esc_html($reserva->libro_titulo); ?></strong>
                                    <small><?php echo esc_html($reserva->libro_autor); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flavor-usuario-info">
                                <?php echo $avatar; ?>
                                <div class="flavor-usuario-detalles">
                                    <strong><?php echo esc_html($reserva->usuario_nombre); ?></strong>
                                    <small><?php echo esc_html($reserva->usuario_email); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php echo date_i18n('d M Y', strtotime($reserva->fecha_solicitud)); ?>
                            <br><small style="color: #666;"><?php echo date_i18n('H:i', strtotime($reserva->fecha_solicitud)); ?></small>
                        </td>
                        <td>
                            <span class="flavor-urgencia <?php echo esc_attr($urgencia['clase']); ?>">
                                <span class="dashicons dashicons-<?php echo esc_attr($urgencia['icono']); ?>"></span>
                                <?php echo esc_html($urgencia['texto']); ?>
                            </span>
                            <br><small style="color: #666;"><?php echo date_i18n('d M', strtotime($reserva->fecha_expiracion)); ?></small>
                        </td>
                        <td>
                            <span class="flavor-badge estado-<?php echo esc_attr($reserva->estado); ?>">
                                <?php echo ucfirst(esc_html($reserva->estado)); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($reserva->estado === 'pendiente'): ?>
                                <button type="button" class="button button-small button-primary btn-confirmar" data-id="<?php echo esc_attr($reserva->id); ?>" title="<?php esc_attr_e('Confirmar reserva', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-yes"></span>
                                </button>
                            <?php endif; ?>
                            <?php if (in_array($reserva->estado, ['pendiente', 'confirmada'])): ?>
                                <button type="button" class="button button-small btn-cancelar" data-id="<?php echo esc_attr($reserva->id); ?>" title="<?php esc_attr_e('Cancelar reserva', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-no"></span>
                                </button>
                            <?php endif; ?>
                            <?php if ($reserva->estado === 'confirmada'): ?>
                                <button type="button" class="button button-small btn-completar" data-id="<?php echo esc_attr($reserva->id); ?>" title="<?php esc_attr_e('Marcar como recogido', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-book-alt"></span>
                                </button>
                            <?php endif; ?>
                            <a href="mailto:<?php echo esc_attr($reserva->usuario_email); ?>" class="button button-small" title="<?php esc_attr_e('Contactar usuario', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-email"></span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-book" style="font-size: 48px; color: #ccc;"></span>
                            <p style="margin-top: 10px; color: #666;"><?php _e('No hay reservas con los filtros seleccionados', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if (isset($total_paginas) && $total_paginas > 1): ?>
        <div class="flavor-pagination">
            <span class="flavor-pagination-info">
                <?php printf(__('Mostrando %d-%d de %d reservas', 'flavor-chat-ia'),
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

<style>
.flavor-biblioteca-reservas { margin: 20px; }

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

.flavor-libro-info, .flavor-usuario-info {
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

.flavor-libro-detalles strong, .flavor-usuario-detalles strong { display: block; font-size: 13px; }
.flavor-libro-detalles small, .flavor-usuario-detalles small { color: #666; font-size: 11px; }

.flavor-usuario-info img { border-radius: 50%; }

.flavor-urgencia {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-urgencia .dashicons { width: 14px; height: 14px; font-size: 14px; }

.flavor-urgencia.normal { background: #d1fae5; color: #065f46; }
.flavor-urgencia.urgente { background: #fef3c7; color: #92400e; }
.flavor-urgencia.hoy { background: #fed7aa; color: #9a3412; }
.flavor-urgencia.expirada { background: #fee2e2; color: #991b1b; }

.flavor-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-badge.estado-pendiente { background: #fef3c7; color: #92400e; }
.flavor-badge.estado-confirmada { background: #d1fae5; color: #065f46; }
.flavor-badge.estado-cancelada { background: #fee2e2; color: #991b1b; }
.flavor-badge.estado-completada { background: #dbeafe; color: #1e40af; }

.reserva-expirada { background: #fef2f2 !important; }

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
    // Confirmar reserva
    $('.btn-confirmar').on('click', function() {
        const id = $(this).data('id');
        if (confirm('<?php _e('¿Confirmar esta reserva?', 'flavor-chat-ia'); ?>')) {
            $.post(ajaxurl, {
                action: 'biblioteca_confirmar_reserva',
                reserva_id: id,
                _wpnonce: '<?php echo wp_create_nonce('biblioteca_reserva'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || '<?php _e('Error al confirmar', 'flavor-chat-ia'); ?>');
                }
            });
        }
    });

    // Cancelar reserva
    $('.btn-cancelar').on('click', function() {
        const id = $(this).data('id');
        if (confirm('<?php _e('¿Cancelar esta reserva?', 'flavor-chat-ia'); ?>')) {
            $.post(ajaxurl, {
                action: 'biblioteca_cancelar_reserva',
                reserva_id: id,
                _wpnonce: '<?php echo wp_create_nonce('biblioteca_reserva'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || '<?php _e('Error al cancelar', 'flavor-chat-ia'); ?>');
                }
            });
        }
    });

    // Completar reserva
    $('.btn-completar').on('click', function() {
        const id = $(this).data('id');
        if (confirm('<?php _e('¿Marcar como recogido/completado?', 'flavor-chat-ia'); ?>')) {
            $.post(ajaxurl, {
                action: 'biblioteca_completar_reserva',
                reserva_id: id,
                _wpnonce: '<?php echo wp_create_nonce('biblioteca_reserva'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || '<?php _e('Error al completar', 'flavor-chat-ia'); ?>');
                }
            });
        }
    });
});
</script>
