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
        Gestión de Emisiones
        <a href="#" class="page-title-action" onclick="abrirModalNuevaEmision(); return false;">
            <span class="dashicons dashicons-plus-alt"></span> Nueva Emisión
        </a>
    </h1>

    <!-- Estado de emisión en vivo -->
    <?php if ($emision_en_vivo): ?>
        <div class="notice notice-success" style="display: flex; align-items: center; padding: 20px; margin: 20px 0; border-left: 4px solid #00a32a;">
            <span class="dashicons dashicons-controls-play" style="font-size: 48px; color: #00a32a; margin-right: 20px; animation: pulse 2s infinite;"></span>
            <div style="flex: 1;">
                <h2 style="margin: 0; color: #00a32a;">EN VIVO AHORA</h2>
                <h3 style="margin: 5px 0;"><?php echo esc_html($emision_en_vivo->programa_nombre); ?></h3>
                <p style="margin: 0;">
                    <strong><?php echo number_format($emision_en_vivo->oyentes_actual ?? 0); ?></strong> oyentes conectados
                    | Pico: <strong><?php echo number_format($emision_en_vivo->oyentes_pico ?? 0); ?></strong>
                </p>
            </div>
            <button class="button button-primary button-large" onclick="finalizarEmision(<?php echo $emision_en_vivo->id; ?>)">
                <span class="dashicons dashicons-controls-pause"></span> Finalizar Emisión
            </button>
        </div>
    <?php else: ?>
        <div class="notice notice-info" style="padding: 20px; margin: 20px 0;">
            <p style="margin: 0;"><strong>No hay emisiones en vivo</strong></p>
        </div>
    <?php endif; ?>

    <!-- Tabla de emisiones -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Programa</th>
                    <th style="width: 150px;">Fecha/Hora</th>
                    <th style="width: 100px;">Duración</th>
                    <th style="width: 120px;">Oyentes Pico</th>
                    <th style="width: 100px;">Estado</th>
                    <th style="width: 150px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emisiones)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-album" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666;">No hay emisiones registradas</p>
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
                                        <span class="dashicons dashicons-controls-play"></span> Iniciar
                                    </button>
                                <?php else: ?>
                                    <button class="button button-small" onclick="verEmision(<?php echo $emision->id; ?>)">
                                        <span class="dashicons dashicons-visibility"></span> Ver
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

<script>
function abrirModalNuevaEmision() {
    alert('Programar nueva emisión');
}

function iniciarEmision(id) {
    if (confirm('¿Iniciar emisión en vivo?')) {
        alert('Iniciar emisión #' + id);
    }
}

function finalizarEmision(id) {
    if (confirm('¿Finalizar la emisión en vivo?')) {
        alert('Finalizar emisión #' + id);
    }
}

function verEmision(id) {
    alert('Ver detalles de emisión #' + id);
}
</script>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
