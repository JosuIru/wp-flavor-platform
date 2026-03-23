<?php
/**
 * Documentos de la Empresa - Frontend
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias_iconos = [
    'legal' => 'dashicons-media-document',
    'fiscal' => 'dashicons-media-spreadsheet',
    'contrato' => 'dashicons-media-text',
    'factura' => 'dashicons-money-alt',
    'presentacion' => 'dashicons-slides',
    'imagen' => 'dashicons-format-image',
    'otro' => 'dashicons-portfolio',
];
?>
<div class="flavor-empresa-documentos">
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
                <h2 style="margin:0 0 4px;font-size:20px;"><?php esc_html_e('Documentos', 'flavor-chat-ia'); ?></h2>
                <p style="margin:0;color:#666;"><?php printf(esc_html__('%d documentos en %s', 'flavor-chat-ia'), count($documentos), esc_html($empresa->nombre)); ?></p>
            </div>
            <?php if ($es_admin || $miembro->rol === 'contable'): ?>
            <button type="button" class="flavor-btn flavor-btn-primary" onclick="document.getElementById('modal-subir').style.display='flex';">
                <span class="dashicons dashicons-upload"></span>
                <?php esc_html_e('Subir documento', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lista de documentos -->
    <?php if (!empty($documentos)): ?>
    <div class="flavor-documentos-list">
        <?php foreach ($documentos as $doc): ?>
        <?php
        $extension = strtolower(pathinfo($doc->nombre_archivo, PATHINFO_EXTENSION));
        $icono = $categorias_iconos[$doc->categoria] ?? 'dashicons-media-default';

        // Icono por extensión
        if (in_array($extension, ['pdf'])) {
            $icono = 'dashicons-pdf';
        } elseif (in_array($extension, ['doc', 'docx'])) {
            $icono = 'dashicons-media-document';
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            $icono = 'dashicons-media-spreadsheet';
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $icono = 'dashicons-format-image';
        }
        ?>
        <div class="flavor-card flavor-documento-item">
            <div class="flavor-documento-icono">
                <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
            </div>

            <div class="flavor-documento-info">
                <h4 style="margin:0 0 4px;font-size:14px;"><?php echo esc_html($doc->titulo ?: $doc->nombre_archivo); ?></h4>
                <div class="flavor-documento-meta">
                    <span><?php echo esc_html(strtoupper($extension)); ?></span>
                    <?php if ($doc->tamano): ?>
                    <span><?php echo esc_html(size_format($doc->tamano)); ?></span>
                    <?php endif; ?>
                    <span><?php echo esc_html(human_time_diff(strtotime($doc->created_at), current_time('timestamp'))); ?></span>
                    <?php if ($doc->subido_por_nombre): ?>
                    <span><?php echo esc_html($doc->subido_por_nombre); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flavor-documento-actions">
                <a href="<?php echo esc_url($doc->url); ?>" target="_blank" class="flavor-btn flavor-btn-sm flavor-btn-secondary" download>
                    <span class="dashicons dashicons-download"></span>
                </a>
                <?php if ($es_admin): ?>
                <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-danger" onclick="eliminarDocumento(<?php echo esc_attr($doc->id); ?>)">
                    <span class="dashicons dashicons-trash"></span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="flavor-card" style="text-align:center;padding:60px;">
        <span class="dashicons dashicons-portfolio" style="font-size:48px;width:48px;height:48px;color:#94a3b8;"></span>
        <h3><?php esc_html_e('Sin documentos', 'flavor-chat-ia'); ?></h3>
        <p style="color:#666;"><?php esc_html_e('Esta empresa aún no tiene documentos subidos.', 'flavor-chat-ia'); ?></p>
        <?php if ($es_admin || $miembro->rol === 'contable'): ?>
        <button type="button" class="flavor-btn flavor-btn-primary" onclick="document.getElementById('modal-subir').style.display='flex';" style="margin-top:16px;">
            <span class="dashicons dashicons-upload"></span>
            <?php esc_html_e('Subir primer documento', 'flavor-chat-ia'); ?>
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($es_admin || $miembro->rol === 'contable'): ?>
<!-- Modal subir -->
<div id="modal-subir" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:100000;">
    <div style="background:#fff;padding:32px;border-radius:16px;max-width:450px;width:90%;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <h3 style="margin:0;font-size:18px;"><?php esc_html_e('Subir documento', 'flavor-chat-ia'); ?></h3>
            <button type="button" onclick="document.getElementById('modal-subir').style.display='none';" style="background:none;border:none;cursor:pointer;">
                <span class="dashicons dashicons-no-alt" style="font-size:24px;"></span>
            </button>
        </div>

        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('subir_documento'); ?>
            <input type="hidden" name="empresa_id" value="<?php echo esc_attr($empresa->id); ?>" />

            <div class="flavor-form-group">
                <label><?php esc_html_e('Archivo', 'flavor-chat-ia'); ?> *</label>
                <input type="file" name="documento" required class="flavor-input" />
                <p class="flavor-description"><?php esc_html_e('Máx. 10MB. Formatos: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-form-group">
                <label><?php esc_html_e('Título', 'flavor-chat-ia'); ?></label>
                <input type="text" name="titulo" class="flavor-input" placeholder="<?php esc_attr_e('Nombre descriptivo del documento', 'flavor-chat-ia'); ?>" />
            </div>

            <div class="flavor-form-group">
                <label><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label>
                <select name="categoria" class="flavor-select">
                    <option value="otro"><?php esc_html_e('Otro', 'flavor-chat-ia'); ?></option>
                    <option value="legal"><?php esc_html_e('Legal', 'flavor-chat-ia'); ?></option>
                    <option value="fiscal"><?php esc_html_e('Fiscal', 'flavor-chat-ia'); ?></option>
                    <option value="contrato"><?php esc_html_e('Contrato', 'flavor-chat-ia'); ?></option>
                    <option value="factura"><?php esc_html_e('Factura', 'flavor-chat-ia'); ?></option>
                    <option value="presentacion"><?php esc_html_e('Presentación', 'flavor-chat-ia'); ?></option>
                    <option value="imagen"><?php esc_html_e('Imagen', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="flavor-form-group">
                <label><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                <textarea name="descripcion" rows="2" class="flavor-textarea" placeholder="<?php esc_attr_e('Notas adicionales...', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div style="margin-top:24px;display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" class="flavor-btn flavor-btn-secondary" onclick="document.getElementById('modal-subir').style.display='none';">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </button>
                <button type="submit" name="subir_documento" class="flavor-btn flavor-btn-primary">
                    <?php esc_html_e('Subir', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.flavor-empresa-documentos .flavor-documentos-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.flavor-empresa-documentos .flavor-documento-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
}
.flavor-empresa-documentos .flavor-documento-icono {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-empresa-documentos .flavor-documento-icono .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #6b7280;
}
.flavor-empresa-documentos .flavor-documento-info {
    flex: 1;
}
.flavor-empresa-documentos .flavor-documento-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #666;
}
.flavor-empresa-documentos .flavor-documento-meta span::before {
    content: '·';
    margin-right: 12px;
}
.flavor-empresa-documentos .flavor-documento-meta span:first-child::before {
    display: none;
}
.flavor-empresa-documentos .flavor-documento-actions {
    display: flex;
    gap: 8px;
}
.flavor-empresa-documentos .flavor-btn-sm {
    padding: 8px;
    font-size: 12px;
}
.flavor-empresa-documentos .flavor-btn-danger {
    background: #fee2e2;
    color: #dc2626;
    border: none;
}
.flavor-empresa-documentos .flavor-form-group {
    margin-bottom: 16px;
}
.flavor-empresa-documentos .flavor-form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
}
.flavor-empresa-documentos .flavor-input,
.flavor-empresa-documentos .flavor-select,
.flavor-empresa-documentos .flavor-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}
.flavor-empresa-documentos .flavor-description {
    font-size: 12px;
    color: #666;
    margin: 6px 0 0;
}
</style>

<script>
function eliminarDocumento(id) {
    if (confirm('<?php echo esc_js(__('¿Estás seguro de eliminar este documento?', 'flavor-chat-ia')); ?>')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<?php echo wp_nonce_field('eliminar_documento', '_wpnonce', true, false); ?>' +
            '<input type="hidden" name="documento_id" value="' + id + '" />' +
            '<input type="hidden" name="eliminar_documento" value="1" />';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
