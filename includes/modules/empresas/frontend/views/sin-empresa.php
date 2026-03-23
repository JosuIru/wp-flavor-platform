<?php
/**
 * Vista Sin Empresa - Frontend
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="flavor-sin-empresa">
    <div class="flavor-card" style="text-align:center;padding:60px 40px;max-width:500px;margin:0 auto;">
        <div style="width:80px;height:80px;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
            <span class="dashicons dashicons-building" style="font-size:40px;width:40px;height:40px;color:#3730a3;"></span>
        </div>

        <h2 style="margin:0 0 12px;font-size:22px;"><?php esc_html_e('No perteneces a ninguna empresa', 'flavor-chat-ia'); ?></h2>

        <p style="color:#666;margin:0 0 24px;line-height:1.6;">
            <?php esc_html_e('Todavía no eres miembro de ninguna empresa. Puedes solicitar unirte a una empresa existente o crear la tuya propia.', 'flavor-chat-ia'); ?>
        </p>

        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <?php if ($puede_crear): ?>
            <button type="button" class="flavor-btn flavor-btn-primary" onclick="document.getElementById('modal-crear').style.display='flex';">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Crear mi empresa', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>

            <a href="#directorio" class="flavor-btn flavor-btn-secondary">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e('Buscar empresas', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Info adicional -->
    <div class="flavor-grid flavor-grid-3" style="margin-top:40px;">
        <div class="flavor-card" style="text-align:center;">
            <span class="dashicons dashicons-groups" style="font-size:32px;width:32px;height:32px;color:#3b82f6;margin-bottom:12px;"></span>
            <h4 style="margin:0 0 8px;"><?php esc_html_e('Colabora en equipo', 'flavor-chat-ia'); ?></h4>
            <p style="color:#666;font-size:13px;margin:0;">
                <?php esc_html_e('Gestiona miembros, roles y permisos de tu organización.', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <div class="flavor-card" style="text-align:center;">
            <span class="dashicons dashicons-chart-area" style="font-size:32px;width:32px;height:32px;color:#10b981;margin-bottom:12px;"></span>
            <h4 style="margin:0 0 8px;"><?php esc_html_e('Contabilidad propia', 'flavor-chat-ia'); ?></h4>
            <p style="color:#666;font-size:13px;margin:0;">
                <?php esc_html_e('Lleva la contabilidad y facturación de tu empresa de forma independiente.', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <div class="flavor-card" style="text-align:center;">
            <span class="dashicons dashicons-portfolio" style="font-size:32px;width:32px;height:32px;color:#8b5cf6;margin-bottom:12px;"></span>
            <h4 style="margin:0 0 8px;"><?php esc_html_e('Documentos seguros', 'flavor-chat-ia'); ?></h4>
            <p style="color:#666;font-size:13px;margin:0;">
                <?php esc_html_e('Almacena y comparte documentos de forma segura con tu equipo.', 'flavor-chat-ia'); ?>
            </p>
        </div>
    </div>
</div>

<?php if ($puede_crear): ?>
<!-- Modal crear empresa -->
<div id="modal-crear" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:100000;">
    <div style="background:#fff;padding:32px;border-radius:16px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <h3 style="margin:0;font-size:18px;"><?php esc_html_e('Crear mi empresa', 'flavor-chat-ia'); ?></h3>
            <button type="button" onclick="document.getElementById('modal-crear').style.display='none';" style="background:none;border:none;cursor:pointer;">
                <span class="dashicons dashicons-no-alt" style="font-size:24px;"></span>
            </button>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('crear_empresa_frontend'); ?>

            <div class="flavor-form-group">
                <label for="nombre"><?php esc_html_e('Nombre de la empresa', 'flavor-chat-ia'); ?> *</label>
                <input type="text" id="nombre" name="nombre" required class="flavor-input" />
            </div>

            <div class="flavor-form-group">
                <label for="cif_nif"><?php esc_html_e('CIF/NIF', 'flavor-chat-ia'); ?></label>
                <input type="text" id="cif_nif" name="cif_nif" class="flavor-input" />
            </div>

            <div class="flavor-form-group">
                <label for="tipo"><?php esc_html_e('Tipo de empresa', 'flavor-chat-ia'); ?></label>
                <select id="tipo" name="tipo" class="flavor-select">
                    <option value="sl"><?php esc_html_e('Sociedad Limitada (S.L.)', 'flavor-chat-ia'); ?></option>
                    <option value="sa"><?php esc_html_e('Sociedad Anónima (S.A.)', 'flavor-chat-ia'); ?></option>
                    <option value="autonomo"><?php esc_html_e('Autónomo', 'flavor-chat-ia'); ?></option>
                    <option value="cooperativa"><?php esc_html_e('Cooperativa', 'flavor-chat-ia'); ?></option>
                    <option value="asociacion"><?php esc_html_e('Asociación', 'flavor-chat-ia'); ?></option>
                    <option value="otro"><?php esc_html_e('Otro', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="flavor-form-group">
                <label for="email"><?php esc_html_e('Email de contacto', 'flavor-chat-ia'); ?></label>
                <input type="email" id="email" name="email" class="flavor-input" />
            </div>

            <div class="flavor-form-group">
                <label for="descripcion"><?php esc_html_e('Descripción breve', 'flavor-chat-ia'); ?></label>
                <textarea id="descripcion" name="descripcion" rows="3" class="flavor-textarea"></textarea>
            </div>

            <div style="margin-top:24px;display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" class="flavor-btn flavor-btn-secondary" onclick="document.getElementById('modal-crear').style.display='none';">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </button>
                <button type="submit" name="crear_empresa" class="flavor-btn flavor-btn-primary">
                    <?php esc_html_e('Crear empresa', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.flavor-sin-empresa .flavor-form-group {
    margin-bottom: 16px;
}
.flavor-sin-empresa .flavor-form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
}
.flavor-sin-empresa .flavor-input,
.flavor-sin-empresa .flavor-select,
.flavor-sin-empresa .flavor-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}
.flavor-sin-empresa .flavor-input:focus,
.flavor-sin-empresa .flavor-select:focus,
.flavor-sin-empresa .flavor-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
</style>
