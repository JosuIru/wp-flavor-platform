<?php
/**
 * Listado Público de Empresas - Frontend
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipos_labels = [
    'sl' => 'S.L.',
    'sa' => 'S.A.',
    'autonomo' => __('Autónomo', 'flavor-platform'),
    'cooperativa' => __('Cooperativa', 'flavor-platform'),
    'asociacion' => __('Asociación', 'flavor-platform'),
    'comunidad_bienes' => 'C.B.',
    'sociedad_civil' => 'S.C.',
    'otro' => __('Otro', 'flavor-platform'),
];
?>
<div class="flavor-empresas-listado">
    <div class="flavor-listado-header" style="margin-bottom:24px;">
        <h2 style="margin:0 0 8px;"><?php esc_html_e('Directorio de empresas', 'flavor-platform'); ?></h2>
        <p style="color:#666;margin:0;"><?php printf(esc_html__('%d empresas registradas', 'flavor-platform'), count($empresas)); ?></p>
    </div>

    <!-- Filtros -->
    <div class="flavor-card" style="margin-bottom:24px;">
        <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:200px;">
                <label style="display:block;font-size:12px;color:#666;margin-bottom:4px;"><?php esc_html_e('Buscar', 'flavor-platform'); ?></label>
                <input type="text" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>" placeholder="<?php esc_attr_e('Nombre de empresa...', 'flavor-platform'); ?>" class="flavor-input" />
            </div>
            <div>
                <label style="display:block;font-size:12px;color:#666;margin-bottom:4px;"><?php esc_html_e('Sector', 'flavor-platform'); ?></label>
                <select name="sector" class="flavor-select">
                    <option value=""><?php esc_html_e('Todos', 'flavor-platform'); ?></option>
                    <?php
                    $sectores = array_unique(array_filter(wp_list_pluck($empresas, 'sector')));
                    foreach ($sectores as $sector):
                    ?>
                    <option value="<?php echo esc_attr($sector); ?>" <?php selected($_GET['sector'] ?? '', $sector); ?>>
                        <?php echo esc_html(ucfirst($sector)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="flavor-btn flavor-btn-primary">
                <?php esc_html_e('Filtrar', 'flavor-platform'); ?>
            </button>
        </form>
    </div>

    <!-- Grid de empresas -->
    <?php if (!empty($empresas)): ?>
    <div class="flavor-grid flavor-grid-3">
        <?php foreach ($empresas as $emp): ?>
        <div class="flavor-card flavor-empresa-card">
            <div class="flavor-empresa-header">
                <?php if ($emp->logo_url): ?>
                <img src="<?php echo esc_url($emp->logo_url); ?>" alt="" class="flavor-empresa-logo" />
                <?php else: ?>
                <div class="flavor-empresa-logo-placeholder">
                    <span class="dashicons dashicons-building"></span>
                </div>
                <?php endif; ?>

                <div class="flavor-empresa-info">
                    <h3 style="margin:0 0 4px;font-size:16px;"><?php echo esc_html($emp->nombre); ?></h3>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <span class="flavor-badge" style="background:#e0e7ff;color:#3730a3;">
                            <?php echo esc_html($tipos_labels[$emp->tipo] ?? strtoupper($emp->tipo)); ?>
                        </span>
                        <?php if ($emp->sector): ?>
                        <span class="flavor-badge" style="background:#dcfce7;color:#166534;">
                            <?php echo esc_html(ucfirst($emp->sector)); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($emp->descripcion): ?>
            <p class="flavor-empresa-descripcion">
                <?php echo esc_html(wp_trim_words($emp->descripcion, 20)); ?>
            </p>
            <?php endif; ?>

            <div class="flavor-empresa-contacto">
                <?php if ($emp->ciudad): ?>
                <span><span class="dashicons dashicons-location"></span> <?php echo esc_html($emp->ciudad); ?></span>
                <?php endif; ?>
                <?php if ($emp->web): ?>
                <a href="<?php echo esc_url($emp->web); ?>" target="_blank"><span class="dashicons dashicons-admin-site"></span> <?php esc_html_e('Web', 'flavor-platform'); ?></a>
                <?php endif; ?>
            </div>

            <div class="flavor-empresa-actions">
                <a href="<?php echo esc_url(add_query_arg(['empresa_id' => $emp->id, 'vista' => 'ver'])); ?>" class="flavor-btn flavor-btn-secondary flavor-btn-sm">
                    <?php esc_html_e('Ver perfil', 'flavor-platform'); ?>
                </a>
                <?php if (is_user_logged_in()): ?>
                <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-sm" onclick="solicitarUnirse(<?php echo esc_attr($emp->id); ?>)">
                    <?php esc_html_e('Solicitar unirse', 'flavor-platform'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="flavor-card" style="text-align:center;padding:60px;">
        <span class="dashicons dashicons-building" style="font-size:48px;width:48px;height:48px;color:#94a3b8;"></span>
        <h3><?php esc_html_e('No hay empresas disponibles', 'flavor-platform'); ?></h3>
        <p style="color:#666;"><?php esc_html_e('No se encontraron empresas con los filtros seleccionados.', 'flavor-platform'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.flavor-empresas-listado .flavor-empresa-card {
    display: flex;
    flex-direction: column;
}
.flavor-empresas-listado .flavor-empresa-header {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
}
.flavor-empresas-listado .flavor-empresa-logo {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    object-fit: cover;
}
.flavor-empresas-listado .flavor-empresa-logo-placeholder {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: #e0e7ff;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-empresas-listado .flavor-empresa-logo-placeholder .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #3730a3;
}
.flavor-empresas-listado .flavor-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 500;
}
.flavor-empresas-listado .flavor-empresa-descripcion {
    color: #666;
    font-size: 13px;
    line-height: 1.5;
    margin: 0 0 12px;
    flex: 1;
}
.flavor-empresas-listado .flavor-empresa-contacto {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #666;
    margin-bottom: 16px;
}
.flavor-empresas-listado .flavor-empresa-contacto a {
    color: #3b82f6;
    text-decoration: none;
}
.flavor-empresas-listado .flavor-empresa-contacto .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    vertical-align: middle;
}
.flavor-empresas-listado .flavor-empresa-actions {
    display: flex;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #f3f4f6;
}
.flavor-empresas-listado .flavor-btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}
.flavor-empresas-listado .flavor-btn-outline {
    background: transparent;
    border: 1px solid #d1d5db;
    color: #374151;
}
.flavor-empresas-listado .flavor-btn-outline:hover {
    background: #f3f4f6;
}
.flavor-empresas-listado .flavor-input,
.flavor-empresas-listado .flavor-select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}
</style>

<script>
function solicitarUnirse(empresaId) {
    if (confirm('<?php echo esc_js(__('¿Solicitar unirse a esta empresa?', 'flavor-platform')); ?>')) {
        // AJAX para solicitar unirse
        jQuery.post(flavorAjax.url, {
            action: 'flavor_solicitar_unirse_empresa',
            nonce: flavorAjax.nonce,
            empresa_id: empresaId
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert(response.data.message || '<?php echo esc_js(__('Error al procesar la solicitud.', 'flavor-platform')); ?>');
            }
        });
    }
}
</script>
