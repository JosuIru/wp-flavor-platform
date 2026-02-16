<?php
/**
 * Vista de Gestión de Suscriptores de Podcast
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_podcasts = $wpdb->prefix . 'flavor_podcasts';
$tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';

// Obtener podcasts para selector
$podcasts = $wpdb->get_results("SELECT id, titulo FROM $tabla_podcasts ORDER BY titulo");
$podcast_seleccionado = isset($_GET['podcast_id']) ? intval($_GET['podcast_id']) : 0;

// Obtener suscriptores con filtro
$where_clauses = ['1=1'];
$prepare_values = [];

if ($podcast_seleccionado > 0) {
    $where_clauses[] = 's.podcast_id = %d';
    $prepare_values[] = $podcast_seleccionado;
}

$where_sql = implode(' AND ', $where_clauses);

if (!empty($prepare_values)) {
    $suscriptores = $wpdb->get_results($wpdb->prepare("
        SELECT s.*, u.display_name, u.user_email, p.titulo as podcast_titulo
        FROM $tabla_suscripciones s
        INNER JOIN {$wpdb->users} u ON s.usuario_id = u.ID
        INNER JOIN $tabla_podcasts p ON s.podcast_id = p.id
        WHERE $where_sql
        ORDER BY s.fecha_suscripcion DESC
    ", ...$prepare_values));
} else {
    $suscriptores = $wpdb->get_results("
        SELECT s.*, u.display_name, u.user_email, p.titulo as podcast_titulo
        FROM $tabla_suscripciones s
        INNER JOIN {$wpdb->users} u ON s.usuario_id = u.ID
        INNER JOIN $tabla_podcasts p ON s.podcast_id = p.id
        WHERE $where_sql
        ORDER BY s.fecha_suscripcion DESC
    ");
}

// Estadísticas generales de suscriptores
$total_suscriptores = count($suscriptores);
$con_notificaciones = count(array_filter($suscriptores, function($s) { return $s->notificaciones_activas == 1; }));

// Actividad reciente (últimos 30 días)
$suscriptores_recientes = array_filter($suscriptores, function($s) {
    return strtotime($s->fecha_suscripcion) > strtotime('-30 days');
});
$nuevos_ultimos_30_dias = count($suscriptores_recientes);
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-groups"></span>
        <?php echo esc_html__('Gestión de Suscriptores', 'flavor-chat-ia'); ?>
    </h1>

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Total Suscriptores', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #2271b1;"><?php echo number_format($total_suscriptores); ?></h2>
                </div>
                <span class="dashicons dashicons-groups" style="font-size: 48px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Con Notificaciones', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #00a32a;"><?php echo number_format($con_notificaciones); ?></h2>
                    <p style="margin: 0; color: #666; font-size: 12px;">
                        <?php echo $total_suscriptores > 0 ? round(($con_notificaciones / $total_suscriptores) * 100, 1) : 0; ?>%
                    </p>
                </div>
                <span class="dashicons dashicons-bell" style="font-size: 48px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;"><?php echo esc_html__('Nuevos (30 días)', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #8c49d8;"><?php echo number_format($nuevos_ultimos_30_dias); ?></h2>
                    <p style="margin: 0; color: #666; font-size: 12px;">
                        <?php echo $total_suscriptores > 0 ? round(($nuevos_ultimos_30_dias / $total_suscriptores) * 100, 1) : 0; ?>%
                    </p>
                </div>
                <span class="dashicons dashicons-arrow-up-alt" style="font-size: 48px; color: #8c49d8; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <!-- Filtro por podcast -->
    <div class="flavor-filters" style="background: #fff; padding: 15px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">

            <div style="flex: 1; min-width: 250px;">
                <label for="podcast_id" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html__('Filtrar por Podcast:', 'flavor-chat-ia'); ?></label>
                <select name="podcast_id" id="podcast_id" class="regular-text" style="width: 100%;">
                    <option value="0"><?php echo esc_html__('Todos los podcasts', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($podcasts as $podcast): ?>
                        <option value="<?php echo $podcast->id; ?>" <?php selected($podcast_seleccionado, $podcast->id); ?>>
                            <?php echo esc_html($podcast->titulo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-filter"></span> <?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?>
            </button>

            <a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button">
                <span class="dashicons dashicons-no"></span> <?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?>
            </a>

            <div style="flex: 1;"></div>

            <button type="button" class="button button-primary" onclick="enviarNotificacion()">
                <span class="dashicons dashicons-email"></span> <?php echo esc_html__('Enviar Notificación', 'flavor-chat-ia'); ?>
            </button>

            <button type="button" class="button" onclick="exportarSuscriptores()">
                <span class="dashicons dashicons-download"></span> <?php echo esc_html__('Exportar CSV', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>

    <!-- Tabla de suscriptores -->
    <div class="flavor-table-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">
                        <input type="checkbox" id="seleccionar-todos" onclick="seleccionarTodos(this)">
                    </th>
                    <th style="width: 60px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Usuario', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Email', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Podcast', 'flavor-chat-ia'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Notificaciones', 'flavor-chat-ia'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Fecha Suscripción', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suscriptores)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-groups" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666;">No hay suscriptores
                                <?php if ($podcast_seleccionado > 0): ?>
                                    para el podcast seleccionado
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suscriptores as $suscriptor): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="suscriptores[]" value="<?php echo $suscriptor->id; ?>" class="checkbox-suscriptor">
                            </td>
                            <td><strong>#<?php echo $suscriptor->id; ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($suscriptor->display_name); ?></strong>
                                <div style="color: #666; font-size: 12px;">ID: <?php echo $suscriptor->usuario_id; ?></div>
                            </td>
                            <td><?php echo esc_html($suscriptor->user_email); ?></td>
                            <td>
                                <strong><?php echo esc_html($suscriptor->podcast_titulo); ?></strong>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($suscriptor->notificaciones_activas): ?>
                                    <span style="color: #00a32a;">
                                        <span class="dashicons dashicons-bell"></span>
                                        <?php echo esc_html__('Activas', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">
                                        <span class="dashicons dashicons-bell" style="opacity: 0.3;"></span>
                                        <?php echo esc_html__('Desactivadas', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date_i18n('d/m/Y H:i', strtotime($suscriptor->fecha_suscripcion)); ?>
                                <?php if (strtotime($suscriptor->fecha_suscripcion) > strtotime('-7 days')): ?>
                                    <span style="display: inline-block; padding: 2px 6px; background: #00a32a; color: #fff; border-radius: 3px; font-size: 10px; margin-left: 5px;">
                                        <?php echo esc_html__('NUEVO', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button button-small" onclick="toggleNotificaciones(<?php echo $suscriptor->id; ?>, <?php echo $suscriptor->notificaciones_activas ? 0 : 1; ?>)" title="<?php echo esc_attr__('Cambiar notificaciones', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-bell"></span>
                                </button>
                                <button class="button button-small button-link-delete" onclick="eliminarSuscripcion(<?php echo $suscriptor->id; ?>)" title="<?php echo esc_attr__('Eliminar suscripción', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

</div>

<script>
function seleccionarTodos(checkbox) {
    const checkboxes = document.querySelectorAll('.checkbox-suscriptor');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function toggleNotificaciones(suscripcionId, nuevoEstado) {
    if (confirm('<?php echo esc_js(__('¿Cambiar el estado de las notificaciones?', 'flavor-chat-ia')); ?>')) {
        jQuery.post(ajaxurl, {
            action: 'flavor_podcast_toggle_notificaciones',
            suscripcion_id: suscripcionId,
            estado: nuevoEstado ? 1 : 0,
            nonce: '<?php echo wp_create_nonce('podcast_suscriptores_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || '<?php echo esc_js(__('Error al cambiar', 'flavor-chat-ia')); ?>');
            }
        });
    }
}

function eliminarSuscripcion(suscripcionId) {
    if (confirm('<?php echo esc_js(__('¿Eliminar esta suscripción?', 'flavor-chat-ia')); ?>')) {
        jQuery.post(ajaxurl, {
            action: 'flavor_podcast_eliminar_suscripcion',
            suscripcion_id: suscripcionId,
            nonce: '<?php echo wp_create_nonce('podcast_suscriptores_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || '<?php echo esc_js(__('Error al eliminar', 'flavor-chat-ia')); ?>');
            }
        });
    }
}

function enviarNotificacion() {
    const seleccionados = document.querySelectorAll('.checkbox-suscriptor:checked');

    if (seleccionados.length === 0) {
        alert('<?php echo esc_js(__('Selecciona al menos un suscriptor', 'flavor-chat-ia')); ?>');
        return;
    }

    const mensaje = prompt('<?php echo esc_js(__('Mensaje de notificación:', 'flavor-chat-ia')); ?>', '<?php echo esc_js(__('Nuevo episodio disponible', 'flavor-chat-ia')); ?>');
    if (mensaje) {
        const ids = Array.from(seleccionados).map(cb => cb.value);
        jQuery.post(ajaxurl, {
            action: 'flavor_podcast_enviar_notificacion',
            suscriptor_ids: ids,
            mensaje: mensaje,
            nonce: '<?php echo wp_create_nonce('podcast_suscriptores_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php echo esc_js(__('Notificaciones enviadas', 'flavor-chat-ia')); ?>');
            } else {
                alert(response.data || '<?php echo esc_js(__('Error al enviar', 'flavor-chat-ia')); ?>');
            }
        });
    }
}

function exportarSuscriptores() {
    const podcast_id = document.getElementById('podcast_id').value;
    let url = '<?php echo admin_url('admin-ajax.php'); ?>?action=exportar_suscriptores_podcast';

    if (podcast_id > 0) {
        url += '&podcast_id=' + podcast_id;
    }

    window.location.href = url;
}
</script>

<style>
.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}

.wp-list-table tbody tr:hover {
    background-color: #f6f7f7;
}

@media (max-width: 768px) {
    .flavor-stats-grid {
        grid-template-columns: 1fr !important;
    }

    .flavor-filters form {
        flex-direction: column;
    }

    .flavor-filters form > div {
        width: 100% !important;
    }
}
</style>
