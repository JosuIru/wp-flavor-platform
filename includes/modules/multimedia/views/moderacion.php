<?php
/**
 * Vista de Moderación de Contenido Multimedia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';

// Obtener contenido pendiente de moderación
$pendientes = $wpdb->get_results("
    SELECT m.*, u.display_name as autor_nombre
    FROM $tabla_multimedia m
    INNER JOIN {$wpdb->users} u ON m.usuario_id = u.ID
    WHERE m.estado = 'pendiente'
    ORDER BY m.fecha_subida ASC
");

// Obtener estadísticas de moderación
$total_pendientes = count($pendientes);
$rechazados_hoy = $wpdb->get_var("
    SELECT COUNT(*) FROM $tabla_multimedia
    WHERE estado = 'rechazado' AND DATE(fecha_subida) = CURDATE()
");
$aprobados_hoy = $wpdb->get_var("
    SELECT COUNT(*) FROM $tabla_multimedia
    WHERE estado = 'aprobado' AND DATE(fecha_subida) = CURDATE()
");
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-shield"></span>
        Moderación de Contenido
    </h1>

    <!-- Estadísticas de moderación -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 14px;">Pendientes</p>
            <h2 style="margin: 10px 0; font-size: 32px; color: #dba617;"><?php echo number_format($total_pendientes); ?></h2>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 14px;">Aprobados Hoy</p>
            <h2 style="margin: 10px 0; font-size: 32px; color: #00a32a;"><?php echo number_format($aprobados_hoy); ?></h2>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <p style="margin: 0; color: #666; font-size: 14px;">Rechazados Hoy</p>
            <h2 style="margin: 10px 0; font-size: 32px; color: #d63638;"><?php echo number_format($rechazados_hoy); ?></h2>
        </div>

    </div>

    <!-- Cola de moderación -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

        <?php if (empty($pendientes)): ?>
            <div style="text-align: center; padding: 60px;">
                <span class="dashicons dashicons-yes-alt" style="font-size: 64px; color: #00a32a;"></span>
                <h3 style="color: #666;">No hay contenido pendiente de moderación</h3>
                <p style="color: #999;">Todo el contenido ha sido revisado</p>
            </div>
        <?php else: ?>
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-list-view"></span>
                Cola de Moderación (<?php echo $total_pendientes; ?> elementos)
            </h3>

            <div style="display: grid; gap: 20px;">
                <?php foreach ($pendientes as $item): ?>
                    <div style="display: grid; grid-template-columns: 200px 1fr auto; gap: 20px; padding: 15px; border: 1px solid #f0f0f1; border-radius: 8px; align-items: center;">

                        <!-- Vista previa -->
                        <div style="position: relative; padding-top: 100%; background: #f0f0f1; border-radius: 4px; overflow: hidden;">
                            <?php if ($item->tipo == 'foto'): ?>
                                <img src="<?php echo esc_url($item->url); ?>" alt="<?php echo esc_attr($item->titulo ?? ''); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                    <span class="dashicons dashicons-video-alt3" style="font-size: 48px; color: #fff;"></span>
                                </div>
                            <?php endif; ?>

                            <div style="position: absolute; top: 10px; right: 10px; padding: 4px 8px; background: #dba617; color: #fff; border-radius: 3px; font-size: 10px; font-weight: 600;">
                                <?php echo strtoupper($item->tipo); ?>
                            </div>
                        </div>

                        <!-- Información -->
                        <div>
                            <h4 style="margin: 0 0 10px 0;">
                                <?php echo esc_html($item->titulo ?: 'Sin título'); ?>
                                <span style="color: #666; font-weight: normal; font-size: 12px;">
                                    #<?php echo $item->id; ?>
                                </span>
                            </h4>

                            <?php if ($item->descripcion): ?>
                                <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
                                    <?php echo wp_trim_words($item->descripcion, 20); ?>
                                </p>
                            <?php endif; ?>

                            <div style="display: flex; gap: 20px; font-size: 13px; color: #666;">
                                <span>
                                    <span class="dashicons dashicons-admin-users" style="font-size: 14px;"></span>
                                    <?php echo esc_html($item->autor_nombre); ?>
                                </span>
                                <span>
                                    <span class="dashicons dashicons-calendar" style="font-size: 14px;"></span>
                                    <?php echo date_i18n('d/m/Y H:i', strtotime($item->fecha_subida)); ?>
                                </span>
                                <?php if ($item->categoria): ?>
                                    <span>
                                        <span class="dashicons dashicons-category" style="font-size: 14px;"></span>
                                        <?php echo esc_html($item->categoria); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Acciones de moderación -->
                        <div style="display: flex; flex-direction: column; gap: 10px; min-width: 140px;">
                            <button onclick="aprobar(<?php echo $item->id; ?>)" class="button button-primary" style="width: 100%;">
                                <span class="dashicons dashicons-yes"></span> Aprobar
                            </button>
                            <button onclick="rechazar(<?php echo $item->id; ?>)" class="button" style="width: 100%;">
                                <span class="dashicons dashicons-no"></span> Rechazar
                            </button>
                            <button onclick="verDetalle(<?php echo $item->id; ?>)" class="button button-small" style="width: 100%;">
                                <span class="dashicons dashicons-visibility"></span> Ver Detalle
                            </button>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Acciones en lote -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #f0f0f1; display: flex; justify-content: space-between;">
                <div>
                    <button class="button" onclick="seleccionarTodos()">
                        <span class="dashicons dashicons-yes"></span> Seleccionar Todos
                    </button>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="button button-primary" onclick="aprobarSeleccionados()">
                        Aprobar Seleccionados
                    </button>
                    <button class="button" onclick="rechazarSeleccionados()">
                        Rechazar Seleccionados
                    </button>
                </div>
            </div>

        <?php endif; ?>

    </div>

</div>

<script>
function aprobar(id) {
    if (confirm('¿Aprobar este contenido?')) {
        alert('Aprobar multimedia #' + id);
        // Implementar aprobación
        location.reload();
    }
}

function rechazar(id) {
    const motivo = prompt('Motivo del rechazo (opcional):');
    if (motivo !== null) {
        alert('Rechazar multimedia #' + id + ' - Motivo: ' + motivo);
        // Implementar rechazo
        location.reload();
    }
}

function verDetalle(id) {
    alert('Ver detalle completo de multimedia #' + id);
}

function seleccionarTodos() {
    alert('Seleccionar todos los elementos');
}

function aprobarSeleccionados() {
    if (confirm('¿Aprobar todos los elementos seleccionados?')) {
        alert('Aprobar seleccionados');
    }
}

function rechazarSeleccionados() {
    if (confirm('¿Rechazar todos los elementos seleccionados?')) {
        alert('Rechazar seleccionados');
    }
}
</script>

<style>
.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}
</style>
