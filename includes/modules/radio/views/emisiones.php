<?php
/**
 * Vista de Gestión de Emisiones en Vivo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_emisiones = $wpdb->prefix . 'flavor_radio_emisiones';
$tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

// Emisión en vivo actual
$emision_en_vivo = $wpdb->get_row("
    SELECT e.*, p.nombre as programa_nombre
    FROM $tabla_emisiones e
    INNER JOIN $tabla_programas p ON e.programa_id = p.id
    WHERE e.estado = 'en_vivo'
    LIMIT 1
");

// Emisiones recientes y programadas
$emisiones = $wpdb->get_results("
    SELECT e.*, p.nombre as programa_nombre
    FROM $tabla_emisiones e
    INNER JOIN $tabla_programas p ON e.programa_id = p.id
    ORDER BY e.fecha_emision DESC
    LIMIT 50
");

$programas = $wpdb->get_results("SELECT id, nombre FROM $tabla_programas WHERE estado = 'activo' ORDER BY nombre");
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-album"></span>
        <?php echo esc_html__('Gestión de Emisiones', 'flavor-chat-ia'); ?>
        <a href="#" class="page-title-action" onclick="abrirModalNuevaEmision(); return false;">
            <span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html__('Nueva Emisión', 'flavor-chat-ia'); ?>
        </a>
    </h1>

    <!-- Estado de emisión en vivo -->
    <?php if ($emision_en_vivo): ?>
        <div class="notice notice-success" style="display: flex; align-items: center; padding: 20px; margin: 20px 0; border-left: 4px solid #00a32a;">
            <span class="dashicons dashicons-controls-play" style="font-size: 48px; color: #00a32a; margin-right: 20px; animation: pulse 2s infinite;"></span>
            <div style="flex: 1;">
                <h2 style="margin: 0; color: #00a32a;"><?php echo esc_html__('EN VIVO AHORA', 'flavor-chat-ia'); ?></h2>
                <h3 style="margin: 5px 0;"><?php echo esc_html($emision_en_vivo->programa_nombre); ?></h3>
                <p style="margin: 0;">
                    <strong><?php echo number_format($emision_en_vivo->oyentes_actual ?? 0); ?></strong> <?php echo esc_html__('oyentes conectados
                    | Pico:', 'flavor-chat-ia'); ?> <strong><?php echo number_format($emision_en_vivo->oyentes_pico ?? 0); ?></strong>
                </p>
            </div>
            <button class="button button-primary button-large" onclick="finalizarEmision(<?php echo $emision_en_vivo->id; ?>)">
                <span class="dashicons dashicons-controls-pause"></span> <?php echo esc_html__('Finalizar Emisión', 'flavor-chat-ia'); ?>
            </button>
        </div>
    <?php else: ?>
        <div class="notice notice-info" style="padding: 20px; margin: 20px 0;">
            <p style="margin: 0;"><strong><?php echo esc_html__('No hay emisiones en vivo', 'flavor-chat-ia'); ?></strong></p>
        </div>
    <?php endif; ?>

    <!-- Tabla de emisiones -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Programa', 'flavor-chat-ia'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Fecha/Hora', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Duración', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Oyentes Pico', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emisiones)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-album" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666;"><?php echo esc_html__('No hay emisiones registradas', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($emisiones as $emision): ?>
                        <tr>
                            <td><strong>#<?php echo $emision->id; ?></strong></td>
                            <td><strong><?php echo esc_html($emision->programa_nombre); ?></strong></td>
                            <td><?php echo date_i18n('d/m/Y H:i', strtotime($emision->fecha_emision)); ?></td>
                            <td>
                                <?php
                                if ($emision->duracion_minutos) {
                                    echo floor($emision->duracion_minutos / 60) . 'h ' . ($emision->duracion_minutos % 60) . 'min';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td style="text-align: center;"><?php echo number_format($emision->oyentes_pico ?? 0); ?></td>
                            <td>
                                <?php
                                $estado_colors = ['en_vivo' => '#00a32a', 'finalizada' => '#2271b1', 'programada' => '#dba617', 'cancelada' => '#d63638'];
                                ?>
                                <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; color: #fff; background-color: <?php echo $estado_colors[$emision->estado] ?? '#666'; ?>;">
                                    <?php echo ucfirst($emision->estado); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($emision->estado == 'programada'): ?>
                                    <button class="button button-small button-primary" onclick="iniciarEmision(<?php echo $emision->id; ?>)">
                                        <span class="dashicons dashicons-controls-play"></span> <?php echo esc_html__('Iniciar', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php else: ?>
                                    <button class="button button-small" onclick="verEmision(<?php echo $emision->id; ?>)">
                                        <span class="dashicons dashicons-visibility"></span> <?php echo esc_html__('Ver', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div id="modal-emision" class="flavor-modal" style="display:none;">
    <div class="flavor-modal-overlay" onclick="cerrarModal()"></div>
    <div class="flavor-modal-content">
        <button class="flavor-modal-close" onclick="cerrarModal()">&times;</button>
        <div id="modal-emision-contenido"></div>
    </div>
</div>

<script>
function abrirModalNuevaEmision() {
    var html = '<h3><?php echo esc_js(__('Nueva Emisión', 'flavor-chat-ia')); ?></h3>' +
        '<form id="form-nueva-emision">' +
        '<div class="form-row"><label><?php echo esc_js(__('Programa', 'flavor-chat-ia')); ?></label>' +
        '<select name="programa_id" required>' +
        '<?php
        global $wpdb;
        $programas = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}flavor_radio_programas WHERE estado = 'activo'");
        foreach ($programas as $p) {
            echo '<option value="' . esc_attr($p->id) . '">' . esc_js($p->nombre) . '</option>';
        }
        ?>' +
        '</select></div>' +
        '<div class="form-row"><label><?php echo esc_js(__('Fecha y hora', 'flavor-chat-ia')); ?></label>' +
        '<input type="datetime-local" name="fecha_emision" required></div>' +
        '<div class="form-row"><label><?php echo esc_js(__('Duración (min)', 'flavor-chat-ia')); ?></label>' +
        '<input type="number" name="duracion_minutos" value="60" min="15"></div>' +
        '<div class="flavor-modal-actions">' +
        '<button type="button" class="button" onclick="cerrarModal()"><?php echo esc_js(__('Cancelar', 'flavor-chat-ia')); ?></button> ' +
        '<button type="submit" class="button button-primary"><?php echo esc_js(__('Programar', 'flavor-chat-ia')); ?></button>' +
        '</div></form>';
    document.getElementById('modal-emision-contenido').innerHTML = html;
    document.getElementById('modal-emision').style.display = 'block';
}

function iniciarEmision(id) {
    if (confirm('<?php echo esc_js(__('¿Iniciar emisión en vivo?', 'flavor-chat-ia')); ?>')) {
        jQuery.post(ajaxurl, {
            action: 'flavor_radio_iniciar_emision',
            emision_id: id,
            nonce: '<?php echo wp_create_nonce('radio_emision_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || '<?php echo esc_js(__('Error al iniciar', 'flavor-chat-ia')); ?>');
            }
        });
    }
}

function finalizarEmision(id) {
    if (confirm('<?php echo esc_js(__('¿Finalizar la emisión?', 'flavor-chat-ia')); ?>')) {
        jQuery.post(ajaxurl, {
            action: 'flavor_radio_finalizar_emision',
            emision_id: id,
            nonce: '<?php echo wp_create_nonce('radio_emision_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || '<?php echo esc_js(__('Error al finalizar', 'flavor-chat-ia')); ?>');
            }
        });
    }
}

function verEmision(id) {
    document.getElementById('modal-emision-contenido').innerHTML = '<p><?php echo esc_js(__('Cargando...', 'flavor-chat-ia')); ?></p>';
    document.getElementById('modal-emision').style.display = 'block';

    jQuery.get(ajaxurl, {
        action: 'flavor_radio_ver_emision',
        emision_id: id,
        nonce: '<?php echo wp_create_nonce('radio_emision_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            var e = response.data;
            var html = '<h3>' + e.programa_nombre + '</h3>' +
                '<p><strong><?php echo esc_js(__('Fecha:', 'flavor-chat-ia')); ?></strong> ' + e.fecha + '</p>' +
                '<p><strong><?php echo esc_js(__('Duración:', 'flavor-chat-ia')); ?></strong> ' + e.duracion + ' min</p>' +
                '<p><strong><?php echo esc_js(__('Estado:', 'flavor-chat-ia')); ?></strong> ' + e.estado + '</p>' +
                '<p><strong><?php echo esc_js(__('Oyentes pico:', 'flavor-chat-ia')); ?></strong> ' + e.oyentes_pico + '</p>';
            document.getElementById('modal-emision-contenido').innerHTML = html;
        } else {
            document.getElementById('modal-emision-contenido').innerHTML = '<p><?php echo esc_js(__('Error al cargar', 'flavor-chat-ia')); ?></p>';
        }
    });
}

function cerrarModal() {
    document.getElementById('modal-emision').style.display = 'none';
}
</script>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
