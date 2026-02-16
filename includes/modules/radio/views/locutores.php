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

<!-- Modal Nuevo Locutor -->
<div id="modal-locutor" class="flavor-modal" style="display:none;">
    <div class="flavor-modal-overlay" onclick="cerrarModalLocutor()"></div>
    <div class="flavor-modal-content" style="min-width:450px;">
        <button class="flavor-modal-close" onclick="cerrarModalLocutor()">&times;</button>
        <h3><?php echo esc_html__('Nuevo Locutor', 'flavor-chat-ia'); ?></h3>
        <form id="form-locutor" method="post">
            <?php wp_nonce_field('nuevo_locutor', 'locutor_nonce'); ?>
            <input type="hidden" name="accion" value="crear_locutor">
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;"><?php echo esc_html__('Nombre', 'flavor-chat-ia'); ?></label>
                <input type="text" name="nombre" required style="width:100%;padding:8px;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;"><?php echo esc_html__('Email', 'flavor-chat-ia'); ?></label>
                <input type="email" name="email" style="width:100%;padding:8px;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;"><?php echo esc_html__('Biografía', 'flavor-chat-ia'); ?></label>
                <textarea name="bio" rows="3" style="width:100%;padding:8px;"></textarea>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;"><?php echo esc_html__('URL Foto', 'flavor-chat-ia'); ?></label>
                <input type="url" name="foto_url" style="width:100%;padding:8px;">
            </div>
            <div style="text-align:right;">
                <button type="button" class="button" onclick="cerrarModalLocutor()"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Guardar', 'flavor-chat-ia'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
.flavor-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100000; }
.flavor-modal-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); }
.flavor-modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 25px; border-radius: 8px; }
.flavor-modal-close { position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; }
</style>

<script>
function abrirModalNuevoLocutor() {
    document.getElementById('modal-locutor').style.display = 'block';
}

function cerrarModalLocutor() {
    document.getElementById('modal-locutor').style.display = 'none';
}

function editarLocutor(id) {
    window.location.href = '<?php echo admin_url('admin.php?page=flavor-radio&tab=locutores&editar='); ?>' + id;
}
</script>
