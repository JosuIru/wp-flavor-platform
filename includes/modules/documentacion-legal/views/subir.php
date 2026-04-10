<?php
/**
 * Vista: Subir Documento Legal
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = [
    'normativa' => __('Normativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'estatutos' => __('Estatutos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'contratos' => __('Contratos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'formularios' => __('Formularios', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'guias' => __('Guias', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$formatos_permitidos = ['pdf', 'doc', 'docx', 'odt', 'txt', 'rtf'];
$tamano_maximo = wp_max_upload_size();
?>

<div class="doc-legal-subir">
    <h2><?php _e('Subir documento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
    <p class="doc-legal-intro">
        <?php _e('Comparte documentos de interes con la comunidad. Los documentos seran revisados antes de su publicacion.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </p>

    <form id="form-subir-documento" class="doc-legal-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('documentacion_legal_subir', 'doc_legal_nonce'); ?>

        <div class="doc-legal-form-section">
            <h3><?php _e('Informacion del documento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="doc-legal-form-group">
                <label for="doc_titulo"><?php _e('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                <input type="text" id="doc_titulo" name="titulo" required maxlength="200" placeholder="<?php esc_attr_e('Titulo descriptivo del documento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>

            <div class="doc-legal-form-group">
                <label for="doc_descripcion"><?php _e('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <textarea id="doc_descripcion" name="descripcion" rows="4" placeholder="<?php esc_attr_e('Breve descripcion del contenido y utilidad del documento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div class="doc-legal-form-row">
                <div class="doc-legal-form-group">
                    <label for="doc_categoria"><?php _e('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                    <select id="doc_categoria" name="categoria" required>
                        <option value=""><?php _e('Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($categorias as $slug => $nombre): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="doc-legal-form-group">
                    <label for="doc_tags"><?php _e('Etiquetas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" id="doc_tags" name="tags" placeholder="<?php esc_attr_e('Separadas por comas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>
        </div>

        <div class="doc-legal-form-section">
            <h3><?php _e('Archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="doc-legal-form-group">
                <label for="doc_archivo"><?php _e('Seleccionar archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                <div class="doc-legal-upload-area" id="upload-area">
                    <span class="dashicons dashicons-cloud-upload"></span>
                    <p><?php _e('Arrastra un archivo aqui o haz clic para seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <input type="file" id="doc_archivo" name="archivo" required accept=".pdf,.doc,.docx,.odt,.txt,.rtf">
                </div>
                <small class="doc-legal-help">
                    <?php printf(
                        __('Formatos permitidos: %s. Tamano maximo: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        implode(', ', $formatos_permitidos),
                        size_format($tamano_maximo)
                    ); ?>
                </small>
            </div>

            <div id="archivo-preview" class="doc-legal-archivo-preview" style="display: none;">
                <span class="dashicons dashicons-media-document"></span>
                <span class="archivo-nombre"></span>
                <span class="archivo-tamano"></span>
                <button type="button" class="doc-legal-btn-icon doc-legal-quitar-archivo">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        </div>

        <div class="doc-legal-form-actions">
            <button type="submit" class="doc-legal-btn doc-legal-btn-primary">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Enviar documento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <a href="<?php echo esc_url(home_url('/documentacion-legal/')); ?>" class="doc-legal-btn doc-legal-btn-secondary">
                <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('doc_archivo');
    const preview = document.getElementById('archivo-preview');

    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', () => fileInput.click());
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                mostrarPreview(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                mostrarPreview(this.files[0]);
            }
        });

        function mostrarPreview(file) {
            preview.querySelector('.archivo-nombre').textContent = file.name;
            preview.querySelector('.archivo-tamano').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            preview.style.display = 'flex';
            uploadArea.style.display = 'none';
        }

        preview.querySelector('.doc-legal-quitar-archivo').addEventListener('click', function() {
            fileInput.value = '';
            preview.style.display = 'none';
            uploadArea.style.display = 'block';
        });
    }
});
</script>
