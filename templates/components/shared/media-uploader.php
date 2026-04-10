<?php
/**
 * Componente: Media Uploader
 *
 * Subidor de archivos con preview, drag & drop y validación.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param string $name        Nombre del campo
 * @param string $label       Etiqueta del campo
 * @param string $accept      Tipos de archivo: image/*, video/*, audio/*, .pdf, etc.
 * @param int    $max_files   Máximo de archivos (1 = single, >1 = multiple)
 * @param int    $max_size    Tamaño máximo en MB
 * @param array  $existing    Archivos existentes [['id' => x, 'url' => '', 'name' => '']]
 * @param bool   $required    Campo requerido
 * @param string $help        Texto de ayuda
 * @param string $module      Módulo para subir via AJAX
 * @param string $color       Color del tema
 */

if (!defined('ABSPATH')) {
    exit;
}

$name = $name ?? 'files';
$label = $label ?? __('Archivos', FLAVOR_PLATFORM_TEXT_DOMAIN);
$accept = $accept ?? 'image/*';
$max_files = intval($max_files ?? 5);
$max_size = intval($max_size ?? 5); // MB
$existing = $existing ?? [];
$required = $required ?? false;
$help = $help ?? '';
$module = $module ?? '';
$color = $color ?? 'blue';

$uploader_id = 'uploader-' . wp_rand(1000, 9999);
$is_image = strpos($accept, 'image') !== false;
$is_single = $max_files === 1;

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['border' => 'border-blue-500', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'];
}
?>

<div class="flavor-media-uploader" id="<?php echo esc_attr($uploader_id); ?>">

    <!-- Label -->
    <?php if ($label): ?>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            <?php echo esc_html($label); ?>
            <?php if ($required): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>

    <!-- Zona de drop -->
    <div class="flavor-upload-zone relative border-2 border-dashed border-gray-300 rounded-xl p-6 text-center transition-all hover:border-gray-400 <?php echo $is_single && !empty($existing) ? 'hidden' : ''; ?>"
         id="<?php echo esc_attr($uploader_id); ?>-zone"
         data-accept="<?php echo esc_attr($accept); ?>"
         data-max-files="<?php echo esc_attr($max_files); ?>"
         data-max-size="<?php echo esc_attr($max_size); ?>">

        <input type="file"
               id="<?php echo esc_attr($uploader_id); ?>-input"
               name="<?php echo esc_attr($name); ?><?php echo $is_single ? '' : '[]'; ?>"
               accept="<?php echo esc_attr($accept); ?>"
               <?php echo $is_single ? '' : 'multiple'; ?>
               <?php echo $required && empty($existing) ? 'required' : ''; ?>
               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

        <div class="pointer-events-none">
            <!-- Icono -->
            <div class="mx-auto w-12 h-12 mb-3 text-gray-400">
                <?php if ($is_image): ?>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                <?php else: ?>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                <?php endif; ?>
            </div>

            <p class="text-sm text-gray-600 mb-1">
                <span class="font-medium <?php echo esc_attr($color_classes['text']); ?>"><?php esc_html_e('Haz clic para subir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php esc_html_e('o arrastra aquí', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <p class="text-xs text-gray-500">
                <?php
                $accept_text = str_replace(['image/*', 'video/*', 'audio/*', '.'], ['Imágenes', 'Videos', 'Audio', ''], $accept);
                echo esc_html($accept_text);
                ?>
                · <?php printf(esc_html__('Máx. %dMB', FLAVOR_PLATFORM_TEXT_DOMAIN), $max_size); ?>
                <?php if (!$is_single): ?>
                    · <?php printf(esc_html__('Hasta %d archivos', FLAVOR_PLATFORM_TEXT_DOMAIN), $max_files); ?>
                <?php endif; ?>
            </p>
        </div>

        <!-- Estado de carga -->
        <div class="flavor-upload-loading hidden absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center rounded-xl">
            <div class="text-center">
                <svg class="w-8 h-8 mx-auto mb-2 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-sm text-gray-600"><?php esc_html_e('Subiendo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <div class="w-32 h-1.5 bg-gray-200 rounded-full mt-2 mx-auto overflow-hidden">
                    <div class="flavor-upload-progress h-full bg-blue-500 rounded-full transition-all" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview de archivos -->
    <div class="flavor-upload-preview mt-3 <?php echo $is_image ? 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3' : 'space-y-2'; ?>"
         id="<?php echo esc_attr($uploader_id); ?>-preview">

        <?php foreach ($existing as $file): ?>
            <div class="flavor-file-item relative group <?php echo $is_image ? 'aspect-square rounded-lg overflow-hidden bg-gray-100' : 'flex items-center gap-3 p-3 bg-gray-50 rounded-lg'; ?>"
                 data-file-id="<?php echo esc_attr($file['id'] ?? ''); ?>">

                <?php if ($is_image): ?>
                    <img src="<?php echo esc_url($file['url']); ?>"
                         alt="<?php echo esc_attr($file['name'] ?? ''); ?>"
                         class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-10 h-10 flex-shrink-0 bg-gray-200 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700 truncate"><?php echo esc_html($file['name'] ?? 'Archivo'); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html($file['size'] ?? ''); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Botón eliminar -->
                <button type="button"
                        class="flavor-remove-file absolute <?php echo $is_image ? 'top-1 right-1 opacity-0 group-hover:opacity-100' : 'right-2'; ?> p-1.5 bg-red-500 text-white rounded-full hover:bg-red-600 transition-all"
                        title="<?php esc_attr_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <!-- Input oculto para IDs existentes -->
                <input type="hidden" name="<?php echo esc_attr($name); ?>_existing[]" value="<?php echo esc_attr($file['id'] ?? ''); ?>">
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Ayuda -->
    <?php if ($help): ?>
        <p class="mt-2 text-xs text-gray-500"><?php echo esc_html($help); ?></p>
    <?php endif; ?>

    <!-- Errores -->
    <div class="flavor-upload-errors mt-2 hidden" id="<?php echo esc_attr($uploader_id); ?>-errors">
        <p class="text-sm text-red-600"></p>
    </div>
</div>

<script>
(function() {
    const uploaderId = '<?php echo esc_js($uploader_id); ?>';
    const container = document.getElementById(uploaderId);
    const zone = document.getElementById(uploaderId + '-zone');
    const input = document.getElementById(uploaderId + '-input');
    const preview = document.getElementById(uploaderId + '-preview');
    const errors = document.getElementById(uploaderId + '-errors');
    const loading = zone.querySelector('.flavor-upload-loading');
    const progress = zone.querySelector('.flavor-upload-progress');

    const maxFiles = parseInt(zone.dataset.maxFiles);
    const maxSize = parseInt(zone.dataset.maxSize) * 1024 * 1024; // Convert to bytes
    const accept = zone.dataset.accept;
    const isImage = accept.includes('image');
    const isSingle = maxFiles === 1;
    const module = '<?php echo esc_js($module); ?>';

    let fileCount = preview.querySelectorAll('.flavor-file-item').length;

    // Drag & Drop
    ['dragenter', 'dragover'].forEach(evt => {
        zone.addEventListener(evt, e => {
            e.preventDefault();
            zone.classList.add('border-blue-500', 'bg-blue-50');
        });
    });

    ['dragleave', 'drop'].forEach(evt => {
        zone.addEventListener(evt, e => {
            e.preventDefault();
            zone.classList.remove('border-blue-500', 'bg-blue-50');
        });
    });

    zone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    // Input change
    input.addEventListener('change', function() {
        handleFiles(this.files);
        this.value = ''; // Reset para permitir seleccionar el mismo archivo
    });

    // Procesar archivos
    function handleFiles(files) {
        hideError();

        const filesArray = Array.from(files);
        const remaining = maxFiles - fileCount;

        if (filesArray.length > remaining) {
            showError(`<?php esc_html_e('Máximo %d archivos permitidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>`.replace('%d', maxFiles));
            return;
        }

        filesArray.forEach(file => {
            // Validar tamaño
            if (file.size > maxSize) {
                showError(`<?php esc_html_e('El archivo "%s" excede el tamaño máximo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>`.replace('%s', file.name));
                return;
            }

            // Validar tipo
            if (accept !== '*' && !matchAccept(file.type, accept)) {
                showError(`<?php esc_html_e('Tipo de archivo no permitido: %s', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>`.replace('%s', file.type));
                return;
            }

            uploadFile(file);
        });
    }

    function matchAccept(fileType, accept) {
        const accepts = accept.split(',').map(a => a.trim());
        return accepts.some(a => {
            if (a.endsWith('/*')) {
                return fileType.startsWith(a.replace('/*', '/'));
            }
            if (a.startsWith('.')) {
                return fileType.includes(a.slice(1));
            }
            return fileType === a;
        });
    }

    // Subir archivo
    function uploadFile(file) {
        loading.classList.remove('hidden');
        progress.style.width = '0%';

        const formData = new FormData();
        formData.append('action', 'flavor_upload_media');
        formData.append('file', file);
        formData.append('module', module);
        formData.append('_wpnonce', flavorAjax.nonce);

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', e => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progress.style.width = percent + '%';
            }
        });

        xhr.addEventListener('load', function() {
            loading.classList.add('hidden');

            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        addFilePreview(response.data);
                        fileCount++;

                        if (isSingle) {
                            zone.classList.add('hidden');
                        }
                    } else {
                        showError(response.data || '<?php esc_html_e('Error al subir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                    }
                } catch (e) {
                    showError('<?php esc_html_e('Error de respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                }
            } else {
                showError('<?php esc_html_e('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
            }
        });

        xhr.addEventListener('error', () => {
            loading.classList.add('hidden');
            showError('<?php esc_html_e('Error de red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
        });

        xhr.open('POST', flavorAjax.url);
        xhr.send(formData);
    }

    // Añadir preview
    function addFilePreview(fileData) {
        const item = document.createElement('div');
        item.className = 'flavor-file-item relative group ' + (isImage ? 'aspect-square rounded-lg overflow-hidden bg-gray-100' : 'flex items-center gap-3 p-3 bg-gray-50 rounded-lg');
        item.dataset.fileId = fileData.id;

        if (isImage) {
            item.innerHTML = `
                <img src="${fileData.url}" alt="${fileData.name}" class="w-full h-full object-cover">
                <button type="button" class="flavor-remove-file absolute top-1 right-1 opacity-0 group-hover:opacity-100 p-1.5 bg-red-500 text-white rounded-full hover:bg-red-600 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <input type="hidden" name="<?php echo esc_attr($name); ?>_ids[]" value="${fileData.id}">
            `;
        } else {
            item.innerHTML = `
                <div class="w-10 h-10 flex-shrink-0 bg-gray-200 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-700 truncate">${fileData.name}</p>
                    <p class="text-xs text-gray-500">${fileData.size}</p>
                </div>
                <button type="button" class="flavor-remove-file p-1.5 bg-red-500 text-white rounded-full hover:bg-red-600 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <input type="hidden" name="<?php echo esc_attr($name); ?>_ids[]" value="${fileData.id}">
            `;
        }

        preview.appendChild(item);
        bindRemoveButton(item);
    }

    // Eliminar archivo
    function bindRemoveButton(item) {
        const btn = item.querySelector('.flavor-remove-file');
        btn.addEventListener('click', function() {
            const fileId = item.dataset.fileId;

            // Eliminar via AJAX si tiene ID
            if (fileId) {
                fetch(flavorAjax.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'flavor_delete_media',
                        file_id: fileId,
                        _wpnonce: flavorAjax.nonce
                    })
                });
            }

            item.remove();
            fileCount--;

            if (isSingle) {
                zone.classList.remove('hidden');
            }
        });
    }

    // Bind initial remove buttons
    preview.querySelectorAll('.flavor-remove-file').forEach(btn => {
        bindRemoveButton(btn.closest('.flavor-file-item'));
    });

    // Errores
    function showError(msg) {
        errors.querySelector('p').textContent = msg;
        errors.classList.remove('hidden');
    }

    function hideError() {
        errors.classList.add('hidden');
    }
})();
</script>
