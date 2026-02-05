<?php
/**
 * Vista Gestión de Materiales
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_talleres = $wpdb->prefix . 'flavor_talleres';

// Obtener talleres que requieren materiales
$talleres_con_materiales = $wpdb->get_results(
    "SELECT t.*, u.display_name as organizador
     FROM $tabla_talleres t
     LEFT JOIN {$wpdb->users} u ON t.organizador_id = u.ID
     WHERE t.materiales_necesarios IS NOT NULL
     AND t.materiales_necesarios != ''
     AND t.estado IN ('publicado', 'confirmado', 'en_curso')
     ORDER BY t.fecha_creacion DESC"
);

?>

<div class="wrap">
    <h1>Gestión de Materiales</h1>
    <hr class="wp-header-end">

    <div class="flavor-card">
        <div class="flavor-card-header">
            <h2>Talleres con Materiales Requeridos</h2>
        </div>
        <div class="flavor-card-body">
            <?php if (!empty($talleres_con_materiales)): ?>
                <?php foreach ($talleres_con_materiales as $taller): ?>
                    <div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px;">
                        <h3 style="margin-top: 0;"><?php echo esc_html($taller->titulo); ?></h3>
                        <p><strong>Organizador:</strong> <?php echo esc_html($taller->organizador); ?></p>
                        <p>
                            <strong>Estado:</strong>
                            <span class="flavor-badge flavor-badge-<?php
                                echo $taller->estado === 'confirmado' ? 'success' : 'info';
                            ?>">
                                <?php echo ucfirst($taller->estado); ?>
                            </span>
                        </p>
                        <p>
                            <strong>Participantes:</strong>
                            <?php echo $taller->inscritos_actuales; ?>/<?php echo $taller->max_participantes; ?>
                        </p>
                        <p>
                            <strong>Materiales incluidos:</strong>
                            <?php echo $taller->materiales_incluidos ? 'Sí' : 'No'; ?>
                        </p>
                        <div style="background: #f8fafc; padding: 10px; border-radius: 4px;">
                            <strong>Materiales necesarios:</strong>
                            <p><?php echo nl2br(esc_html($taller->materiales_necesarios)); ?></p>
                        </div>
                        <button class="button button-small" style="margin-top: 10px;">Editar Materiales</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="flavor-no-data">No hay talleres con materiales especificados</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumen de materiales -->
    <div class="flavor-card" style="margin-top: 20px;">
        <div class="flavor-card-header">
            <h2>Resumen</h2>
        </div>
        <div class="flavor-card-body">
            <div class="flavor-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="flavor-stat-card">
                    <div class="flavor-stat-content">
                        <div class="flavor-stat-value"><?php echo count($talleres_con_materiales); ?></div>
                        <div class="flavor-stat-label">Talleres con Materiales</div>
                    </div>
                </div>

                <div class="flavor-stat-card">
                    <div class="flavor-stat-content">
                        <div class="flavor-stat-value">
                            <?php echo count(array_filter($talleres_con_materiales, function($t) {
                                return $t->materiales_incluidos == 1;
                            })); ?>
                        </div>
                        <div class="flavor-stat-label">Materiales Incluidos</div>
                    </div>
                </div>

                <div class="flavor-stat-card">
                    <div class="flavor-stat-content">
                        <div class="flavor-stat-value">
                            <?php echo count(array_filter($talleres_con_materiales, function($t) {
                                return $t->materiales_incluidos == 0;
                            })); ?>
                        </div>
                        <div class="flavor-stat-label">Material Propio</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>
