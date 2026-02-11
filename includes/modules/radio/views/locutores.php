<?php
/**
 * Vista de Gestión de Locutores
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_locutores = $wpdb->prefix . 'flavor_radio_locutores';
$tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

$locutores = $wpdb->get_results("
    SELECT l.*, COUNT(p.id) as total_programas
    FROM $tabla_locutores l
    LEFT JOIN $tabla_programas p ON l.id = p.locutor_principal_id
    GROUP BY l.id
    ORDER BY l.nombre
");
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-admin-users"></span>
        <?php echo esc_html__('Gestión de Locutores', 'flavor-chat-ia'); ?>
        <a href="#" class="page-title-action" onclick="abrirModalNuevoLocutor(); return false;">
            <span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html__('Nuevo Locutor', 'flavor-chat-ia'); ?>
        </a>
    </h1>

    <!-- Grid de locutores -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
        <?php if (empty($locutores)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px; background: #fff; border-radius: 8px;">
                <span class="dashicons dashicons-admin-users" style="font-size: 64px; color: #ddd;"></span>
                <h3 style="color: #666;"><?php echo esc_html__('No hay locutores registrados', 'flavor-chat-ia'); ?></h3>
            </div>
        <?php else: ?>
            <?php foreach ($locutores as $locutor): ?>
                <div style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="position: relative; padding-top: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <?php if (!empty($locutor->foto_url)): ?>
                            <img src="<?php echo esc_url($locutor->foto_url); ?>" alt="<?php echo esc_attr($locutor->nombre); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-admin-users" style="font-size: 64px; color: rgba(255,255,255,0.3);"></span>
                            </div>
                        <?php endif; ?>
                        <div style="position: absolute; top: 10px; right: 10px; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; color: #fff; background-color: <?php echo $locutor->estado == 'activo' ? '#00a32a' : '#d63638'; ?>;">
                            <?php echo ucfirst($locutor->estado); ?>
                        </div>
                    </div>

                    <div style="padding: 20px;">
                        <h3 style="margin: 0 0 10px 0; font-size: 18px;"><?php echo esc_html($locutor->nombre); ?></h3>
                        <p style="color: #666; font-size: 14px; margin: 0 0 15px 0;"><?php echo wp_trim_words($locutor->bio ?? '', 20); ?></p>

                        <div style="display: flex; gap: 10px; margin-bottom: 10px; padding-top: 10px; border-top: 1px solid #f0f0f1;">
                            <div style="flex: 1;">
                                <span class="dashicons dashicons-microphone" style="color: #2271b1;"></span>
                                <strong><?php echo $locutor->total_programas; ?></strong>
                                <div style="color: #666; font-size: 11px;"><?php echo esc_html__('Programas', 'flavor-chat-ia'); ?></div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button onclick="editarLocutor(<?php echo $locutor->id; ?>)" class="button button-small" style="flex: 1;">
                                <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Editar', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
function abrirModalNuevoLocutor() {
    alert('Agregar nuevo locutor');
}

function editarLocutor(id) {
    alert('Editar locutor #' + id);
}
</script>
