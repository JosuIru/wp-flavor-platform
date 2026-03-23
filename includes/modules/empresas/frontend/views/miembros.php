<?php
/**
 * Miembros de la Empresa - Frontend
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$roles_labels = [
    'admin' => __('Administrador', 'flavor-chat-ia'),
    'contable' => __('Contable', 'flavor-chat-ia'),
    'empleado' => __('Empleado', 'flavor-chat-ia'),
    'colaborador' => __('Colaborador', 'flavor-chat-ia'),
    'observador' => __('Observador', 'flavor-chat-ia'),
];

$roles_colores = [
    'admin' => ['bg' => '#fef3c7', 'color' => '#92400e'],
    'contable' => ['bg' => '#dbeafe', 'color' => '#1e40af'],
    'empleado' => ['bg' => '#dcfce7', 'color' => '#166534'],
    'colaborador' => ['bg' => '#f3e8ff', 'color' => '#6b21a8'],
    'observador' => ['bg' => '#f3f4f6', 'color' => '#4b5563'],
];
?>
<div class="flavor-empresa-miembros">
    <!-- Navegación -->
    <div style="margin-bottom:20px;">
        <a href="<?php echo esc_url(remove_query_arg('vista')); ?>" class="flavor-btn flavor-btn-link">
            ← <?php esc_html_e('Volver al dashboard', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Cabecera -->
    <div class="flavor-card" style="margin-bottom:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h2 style="margin:0 0 4px;font-size:20px;"><?php esc_html_e('Miembros del equipo', 'flavor-chat-ia'); ?></h2>
                <p style="margin:0;color:#666;"><?php printf(esc_html__('%d miembros en %s', 'flavor-chat-ia'), count($miembros), esc_html($empresa->nombre)); ?></p>
            </div>
            <?php if ($es_admin): ?>
            <button type="button" class="flavor-btn flavor-btn-primary" onclick="document.getElementById('modal-invitar').style.display='flex';">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Invitar miembro', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lista de miembros -->
    <?php if (!empty($miembros)): ?>
    <div class="flavor-miembros-grid">
        <?php foreach ($miembros as $m): ?>
        <div class="flavor-card flavor-miembro-card">
            <div class="flavor-miembro-avatar">
                <?php echo get_avatar($m->user_id, 64, '', '', ['extra_attr' => 'style="border-radius:50%;"']); ?>
            </div>

            <div class="flavor-miembro-info">
                <h4 style="margin:0 0 4px;font-size:16px;"><?php echo esc_html($m->display_name); ?></h4>
                <?php if ($m->cargo): ?>
                <p style="margin:0 0 8px;color:#666;font-size:13px;"><?php echo esc_html($m->cargo); ?></p>
                <?php endif; ?>

                <?php
                $rol_style = $roles_colores[$m->rol] ?? $roles_colores['empleado'];
                ?>
                <span class="flavor-badge" style="background:<?php echo esc_attr($rol_style['bg']); ?>;color:<?php echo esc_attr($rol_style['color']); ?>;">
                    <?php echo esc_html($roles_labels[$m->rol] ?? ucfirst($m->rol)); ?>
                </span>
            </div>

            <div class="flavor-miembro-contacto">
                <a href="mailto:<?php echo esc_attr($m->user_email); ?>" title="<?php esc_attr_e('Enviar email', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-email"></span>
                </a>
                <?php if ($m->telefono_corporativo): ?>
                <a href="tel:<?php echo esc_attr($m->telefono_corporativo); ?>" title="<?php esc_attr_e('Llamar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-phone"></span>
                </a>
                <?php endif; ?>
            </div>

            <?php if ($es_admin && $m->user_id != get_current_user_id()): ?>
            <div class="flavor-miembro-actions">
                <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-outline" onclick="editarMiembro(<?php echo esc_attr($m->id); ?>, '<?php echo esc_attr($m->rol); ?>', '<?php echo esc_attr($m->cargo); ?>')">
                    <span class="dashicons dashicons-edit"></span>
                </button>
                <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-danger" onclick="eliminarMiembro(<?php echo esc_attr($m->id); ?>)">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="flavor-card" style="text-align:center;padding:60px;">
        <span class="dashicons dashicons-groups" style="font-size:48px;width:48px;height:48px;color:#94a3b8;"></span>
        <h3><?php esc_html_e('Sin miembros', 'flavor-chat-ia'); ?></h3>
        <p style="color:#666;"><?php esc_html_e('Esta empresa aún no tiene miembros registrados.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>
</div>

<?php if ($es_admin): ?>
<!-- Modal invitar -->
<div id="modal-invitar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:100000;">
    <div style="background:#fff;padding:32px;border-radius:16px;max-width:450px;width:90%;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <h3 style="margin:0;font-size:18px;"><?php esc_html_e('Invitar miembro', 'flavor-chat-ia'); ?></h3>
            <button type="button" onclick="document.getElementById('modal-invitar').style.display='none';" style="background:none;border:none;cursor:pointer;">
                <span class="dashicons dashicons-no-alt" style="font-size:24px;"></span>
            </button>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('invitar_miembro'); ?>
            <input type="hidden" name="empresa_id" value="<?php echo esc_attr($empresa->id); ?>" />

            <div class="flavor-form-group">
                <label><?php esc_html_e('Email del usuario', 'flavor-chat-ia'); ?> *</label>
                <input type="email" name="email" required class="flavor-input" placeholder="usuario@email.com" />
                <p class="flavor-description"><?php esc_html_e('El usuario debe tener una cuenta registrada.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-form-group">
                <label><?php esc_html_e('Rol', 'flavor-chat-ia'); ?></label>
                <select name="rol" class="flavor-select">
                    <?php foreach ($roles_labels as $rol => $label): ?>
                    <option value="<?php echo esc_attr($rol); ?>" <?php selected($rol, 'empleado'); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-form-group">
                <label><?php esc_html_e('Cargo', 'flavor-chat-ia'); ?></label>
                <input type="text" name="cargo" class="flavor-input" placeholder="<?php esc_attr_e('Ej: Director comercial', 'flavor-chat-ia'); ?>" />
            </div>

            <div style="margin-top:24px;display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" class="flavor-btn flavor-btn-secondary" onclick="document.getElementById('modal-invitar').style.display='none';">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </button>
                <button type="submit" name="invitar_miembro" class="flavor-btn flavor-btn-primary">
                    <?php esc_html_e('Enviar invitación', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal editar -->
<div id="modal-editar-miembro" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:100000;">
    <div style="background:#fff;padding:32px;border-radius:16px;max-width:400px;width:90%;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <h3 style="margin:0;font-size:18px;"><?php esc_html_e('Editar miembro', 'flavor-chat-ia'); ?></h3>
            <button type="button" onclick="document.getElementById('modal-editar-miembro').style.display='none';" style="background:none;border:none;cursor:pointer;">
                <span class="dashicons dashicons-no-alt" style="font-size:24px;"></span>
            </button>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('editar_miembro'); ?>
            <input type="hidden" name="miembro_id" id="editar-miembro-id" value="" />

            <div class="flavor-form-group">
                <label><?php esc_html_e('Rol', 'flavor-chat-ia'); ?></label>
                <select name="rol" id="editar-miembro-rol" class="flavor-select">
                    <?php foreach ($roles_labels as $rol => $label): ?>
                    <option value="<?php echo esc_attr($rol); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-form-group">
                <label><?php esc_html_e('Cargo', 'flavor-chat-ia'); ?></label>
                <input type="text" name="cargo" id="editar-miembro-cargo" class="flavor-input" />
            </div>

            <div style="margin-top:24px;display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" class="flavor-btn flavor-btn-secondary" onclick="document.getElementById('modal-editar-miembro').style.display='none';">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </button>
                <button type="submit" name="actualizar_miembro" class="flavor-btn flavor-btn-primary">
                    <?php esc_html_e('Guardar', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.flavor-empresa-miembros .flavor-miembros-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}
.flavor-empresa-miembros .flavor-miembro-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 24px;
}
.flavor-empresa-miembros .flavor-miembro-avatar {
    margin-bottom: 12px;
}
.flavor-empresa-miembros .flavor-miembro-info {
    margin-bottom: 12px;
}
.flavor-empresa-miembros .flavor-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}
.flavor-empresa-miembros .flavor-miembro-contacto {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}
.flavor-empresa-miembros .flavor-miembro-contacto a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #374151;
    text-decoration: none;
    transition: background 0.2s;
}
.flavor-empresa-miembros .flavor-miembro-contacto a:hover {
    background: #e5e7eb;
}
.flavor-empresa-miembros .flavor-miembro-actions {
    display: flex;
    gap: 8px;
}
.flavor-empresa-miembros .flavor-btn-sm {
    padding: 6px;
    font-size: 12px;
}
.flavor-empresa-miembros .flavor-btn-outline {
    background: transparent;
    border: 1px solid #d1d5db;
}
.flavor-empresa-miembros .flavor-btn-danger {
    background: #fee2e2;
    color: #dc2626;
    border: none;
}
.flavor-empresa-miembros .flavor-form-group {
    margin-bottom: 16px;
}
.flavor-empresa-miembros .flavor-form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
}
.flavor-empresa-miembros .flavor-input,
.flavor-empresa-miembros .flavor-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}
.flavor-empresa-miembros .flavor-description {
    font-size: 12px;
    color: #666;
    margin: 6px 0 0;
}
</style>

<script>
function editarMiembro(id, rol, cargo) {
    document.getElementById('editar-miembro-id').value = id;
    document.getElementById('editar-miembro-rol').value = rol;
    document.getElementById('editar-miembro-cargo').value = cargo;
    document.getElementById('modal-editar-miembro').style.display = 'flex';
}

function eliminarMiembro(id) {
    if (confirm('<?php echo esc_js(__('¿Estás seguro de eliminar este miembro del equipo?', 'flavor-chat-ia')); ?>')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<?php echo wp_nonce_field('eliminar_miembro', '_wpnonce', true, false); ?>' +
            '<input type="hidden" name="miembro_id" value="' + id + '" />' +
            '<input type="hidden" name="eliminar_miembro" value="1" />';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
