<?php
/**
 * Perfil de Empresa - Frontend
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipos_labels = [
    'sl' => __('Sociedad Limitada', 'flavor-chat-ia'),
    'sa' => __('Sociedad Anónima', 'flavor-chat-ia'),
    'autonomo' => __('Autónomo', 'flavor-chat-ia'),
    'cooperativa' => __('Cooperativa', 'flavor-chat-ia'),
    'asociacion' => __('Asociación', 'flavor-chat-ia'),
    'comunidad_bienes' => __('Comunidad de Bienes', 'flavor-chat-ia'),
    'sociedad_civil' => __('Sociedad Civil', 'flavor-chat-ia'),
    'otro' => __('Otro', 'flavor-chat-ia'),
];
?>
<div class="flavor-empresa-perfil">
    <!-- Navegación -->
    <div style="margin-bottom:20px;">
        <a href="<?php echo esc_url(remove_query_arg('vista')); ?>" class="flavor-btn flavor-btn-link">
            ← <?php esc_html_e('Volver al dashboard', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Cabecera -->
    <div class="flavor-card" style="margin-bottom:24px;">
        <div style="display:flex;align-items:start;gap:20px;">
            <div class="flavor-empresa-logo-container">
                <?php if ($empresa->logo_url): ?>
                <img src="<?php echo esc_url($empresa->logo_url); ?>" alt="" class="flavor-empresa-logo-grande" />
                <?php else: ?>
                <div class="flavor-empresa-logo-grande-placeholder">
                    <span class="dashicons dashicons-building"></span>
                </div>
                <?php endif; ?>
                <?php if ($es_admin): ?>
                <button type="button" class="flavor-btn-upload" onclick="document.getElementById('upload-logo').click();">
                    <span class="dashicons dashicons-camera"></span>
                </button>
                <input type="file" id="upload-logo" accept="image/*" style="display:none;" onchange="uploadLogo(this)" />
                <?php endif; ?>
            </div>

            <div style="flex:1;">
                <h1 style="margin:0 0 8px;font-size:24px;"><?php echo esc_html($empresa->nombre); ?></h1>

                <?php if ($empresa->razon_social && $empresa->razon_social !== $empresa->nombre): ?>
                <p style="margin:0 0 12px;color:#666;"><?php echo esc_html($empresa->razon_social); ?></p>
                <?php endif; ?>

                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                    <span class="flavor-badge flavor-badge-primary">
                        <?php echo esc_html($tipos_labels[$empresa->tipo] ?? strtoupper($empresa->tipo)); ?>
                    </span>
                    <?php if ($empresa->sector): ?>
                    <span class="flavor-badge flavor-badge-success">
                        <?php echo esc_html(ucfirst($empresa->sector)); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($empresa->cif_nif): ?>
                    <span style="font-family:monospace;color:#666;">
                        <?php echo esc_html($empresa->cif_nif); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($es_admin): ?>
            <button type="button" class="flavor-btn flavor-btn-primary" onclick="document.getElementById('modal-editar').style.display='flex';">
                <span class="dashicons dashicons-edit"></span>
                <?php esc_html_e('Editar perfil', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="flavor-grid flavor-grid-2">
        <!-- Información de contacto -->
        <div class="flavor-card">
            <h3 style="margin:0 0 20px;font-size:16px;">
                <span class="dashicons dashicons-phone" style="color:#3b82f6;"></span>
                <?php esc_html_e('Información de contacto', 'flavor-chat-ia'); ?>
            </h3>

            <div class="flavor-info-list">
                <?php if ($empresa->email): ?>
                <div class="flavor-info-item">
                    <span class="flavor-info-label"><?php esc_html_e('Email', 'flavor-chat-ia'); ?></span>
                    <a href="mailto:<?php echo esc_attr($empresa->email); ?>"><?php echo esc_html($empresa->email); ?></a>
                </div>
                <?php endif; ?>

                <?php if ($empresa->telefono): ?>
                <div class="flavor-info-item">
                    <span class="flavor-info-label"><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></span>
                    <a href="tel:<?php echo esc_attr($empresa->telefono); ?>"><?php echo esc_html($empresa->telefono); ?></a>
                </div>
                <?php endif; ?>

                <?php if ($empresa->web): ?>
                <div class="flavor-info-item">
                    <span class="flavor-info-label"><?php esc_html_e('Sitio web', 'flavor-chat-ia'); ?></span>
                    <a href="<?php echo esc_url($empresa->web); ?>" target="_blank"><?php echo esc_html($empresa->web); ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dirección -->
        <div class="flavor-card">
            <h3 style="margin:0 0 20px;font-size:16px;">
                <span class="dashicons dashicons-location" style="color:#10b981;"></span>
                <?php esc_html_e('Dirección', 'flavor-chat-ia'); ?>
            </h3>

            <?php if ($empresa->direccion || $empresa->ciudad): ?>
            <div class="flavor-direccion">
                <?php if ($empresa->direccion): ?>
                <p style="margin:0 0 4px;"><?php echo esc_html($empresa->direccion); ?></p>
                <?php endif; ?>
                <p style="margin:0;color:#666;">
                    <?php
                    $ubicacion = array_filter([
                        $empresa->codigo_postal,
                        $empresa->ciudad,
                        $empresa->provincia,
                        $empresa->pais,
                    ]);
                    echo esc_html(implode(', ', $ubicacion));
                    ?>
                </p>
            </div>
            <?php else: ?>
            <p style="color:#666;"><?php esc_html_e('Sin dirección registrada.', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Descripción -->
    <?php if ($empresa->descripcion): ?>
    <div class="flavor-card" style="margin-top:20px;">
        <h3 style="margin:0 0 16px;font-size:16px;">
            <span class="dashicons dashicons-text" style="color:#8b5cf6;"></span>
            <?php esc_html_e('Acerca de', 'flavor-chat-ia'); ?>
        </h3>
        <div class="flavor-descripcion">
            <?php echo nl2br(esc_html($empresa->descripcion)); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Info adicional -->
    <div class="flavor-card" style="margin-top:20px;">
        <h3 style="margin:0 0 16px;font-size:16px;">
            <span class="dashicons dashicons-info" style="color:#f59e0b;"></span>
            <?php esc_html_e('Información adicional', 'flavor-chat-ia'); ?>
        </h3>
        <div class="flavor-info-list">
            <div class="flavor-info-item">
                <span class="flavor-info-label"><?php esc_html_e('Registrada el', 'flavor-chat-ia'); ?></span>
                <span><?php echo esc_html(date_i18n('d/m/Y', strtotime($empresa->created_at))); ?></span>
            </div>
            <div class="flavor-info-item">
                <span class="flavor-info-label"><?php esc_html_e('Tu rol', 'flavor-chat-ia'); ?></span>
                <span><?php echo esc_html(ucfirst($miembro->rol)); ?></span>
            </div>
            <div class="flavor-info-item">
                <span class="flavor-info-label"><?php esc_html_e('Fecha de alta', 'flavor-chat-ia'); ?></span>
                <span><?php echo esc_html(date_i18n('d/m/Y', strtotime($miembro->fecha_alta))); ?></span>
            </div>
        </div>
    </div>
</div>

<?php if ($es_admin): ?>
<!-- Modal editar -->
<div id="modal-editar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:100000;overflow-y:auto;padding:20px;">
    <div style="background:#fff;padding:32px;border-radius:16px;max-width:600px;width:100%;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <h3 style="margin:0;font-size:18px;"><?php esc_html_e('Editar información', 'flavor-chat-ia'); ?></h3>
            <button type="button" onclick="document.getElementById('modal-editar').style.display='none';" style="background:none;border:none;cursor:pointer;">
                <span class="dashicons dashicons-no-alt" style="font-size:24px;"></span>
            </button>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('editar_empresa_frontend'); ?>
            <input type="hidden" name="empresa_id" value="<?php echo esc_attr($empresa->id); ?>" />

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label><?php esc_html_e('Nombre comercial', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" name="nombre" value="<?php echo esc_attr($empresa->nombre); ?>" required class="flavor-input" />
                </div>
                <div class="flavor-form-group">
                    <label><?php esc_html_e('Razón social', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="razon_social" value="<?php echo esc_attr($empresa->razon_social); ?>" class="flavor-input" />
                </div>
            </div>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label><?php esc_html_e('Email', 'flavor-chat-ia'); ?></label>
                    <input type="email" name="email" value="<?php echo esc_attr($empresa->email); ?>" class="flavor-input" />
                </div>
                <div class="flavor-form-group">
                    <label><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></label>
                    <input type="tel" name="telefono" value="<?php echo esc_attr($empresa->telefono); ?>" class="flavor-input" />
                </div>
            </div>

            <div class="flavor-form-group">
                <label><?php esc_html_e('Sitio web', 'flavor-chat-ia'); ?></label>
                <input type="url" name="web" value="<?php echo esc_attr($empresa->web); ?>" class="flavor-input" placeholder="https://" />
            </div>

            <div class="flavor-form-group">
                <label><?php esc_html_e('Dirección', 'flavor-chat-ia'); ?></label>
                <input type="text" name="direccion" value="<?php echo esc_attr($empresa->direccion); ?>" class="flavor-input" />
            </div>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label><?php esc_html_e('Ciudad', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="ciudad" value="<?php echo esc_attr($empresa->ciudad); ?>" class="flavor-input" />
                </div>
                <div class="flavor-form-group">
                    <label><?php esc_html_e('Código postal', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="codigo_postal" value="<?php echo esc_attr($empresa->codigo_postal); ?>" class="flavor-input" />
                </div>
            </div>

            <div class="flavor-form-group">
                <label><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                <textarea name="descripcion" rows="4" class="flavor-textarea"><?php echo esc_textarea($empresa->descripcion); ?></textarea>
            </div>

            <div style="margin-top:24px;display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" class="flavor-btn flavor-btn-secondary" onclick="document.getElementById('modal-editar').style.display='none';">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </button>
                <button type="submit" name="actualizar_empresa" class="flavor-btn flavor-btn-primary">
                    <?php esc_html_e('Guardar cambios', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.flavor-empresa-perfil .flavor-empresa-logo-container {
    position: relative;
}
.flavor-empresa-perfil .flavor-empresa-logo-grande {
    width: 120px;
    height: 120px;
    border-radius: 16px;
    object-fit: cover;
}
.flavor-empresa-perfil .flavor-empresa-logo-grande-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 16px;
    background: #e0e7ff;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-empresa-perfil .flavor-empresa-logo-grande-placeholder .dashicons {
    font-size: 56px;
    width: 56px;
    height: 56px;
    color: #3730a3;
}
.flavor-empresa-perfil .flavor-btn-upload {
    position: absolute;
    bottom: -8px;
    right: -8px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #e5e7eb;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-empresa-perfil .flavor-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
.flavor-empresa-perfil .flavor-badge-primary {
    background: #e0e7ff;
    color: #3730a3;
}
.flavor-empresa-perfil .flavor-badge-success {
    background: #dcfce7;
    color: #166534;
}
.flavor-empresa-perfil .flavor-info-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.flavor-empresa-perfil .flavor-info-item {
    display: flex;
    justify-content: space-between;
    padding-bottom: 12px;
    border-bottom: 1px solid #f3f4f6;
}
.flavor-empresa-perfil .flavor-info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.flavor-empresa-perfil .flavor-info-label {
    color: #666;
    font-size: 13px;
}
.flavor-empresa-perfil .flavor-descripcion {
    line-height: 1.7;
    color: #374151;
}
.flavor-empresa-perfil .flavor-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.flavor-empresa-perfil .flavor-form-group {
    margin-bottom: 16px;
}
.flavor-empresa-perfil .flavor-form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 13px;
}
.flavor-empresa-perfil .flavor-input,
.flavor-empresa-perfil .flavor-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}
@media (max-width: 640px) {
    .flavor-empresa-perfil .flavor-form-row {
        grid-template-columns: 1fr;
    }
}
</style>
