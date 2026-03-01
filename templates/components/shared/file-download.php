<?php
/**
 * Componente: File Download
 *
 * Enlace de descarga de archivo con información y preview.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $url        URL del archivo
 * @param string $filename   Nombre del archivo
 * @param string $size       Tamaño del archivo (1.2 MB)
 * @param string $type       Tipo de archivo: pdf, doc, xls, zip, image, video, audio
 * @param string $description Descripción del archivo
 * @param string $preview    URL de preview (para PDFs, imágenes)
 * @param string $variant    Variante: default, card, inline, minimal
 * @param bool   $track_download Rastrear descarga via AJAX
 * @param int    $downloads  Número de descargas
 */

if (!defined('ABSPATH')) {
    exit;
}

$url = $url ?? '#';
$filename = $filename ?? __('Archivo', 'flavor-chat-ia');
$size = $size ?? '';
$type = $type ?? 'file';
$description = $description ?? '';
$preview = $preview ?? '';
$variant = $variant ?? 'default';
$track_download = $track_download ?? false;
$downloads = intval($downloads ?? 0);

// Auto-detectar tipo por extensión si no se especifica
if ($type === 'file' || empty($type)) {
    $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    $type_map = [
        'pdf' => 'pdf',
        'doc' => 'doc', 'docx' => 'doc',
        'xls' => 'xls', 'xlsx' => 'xls',
        'ppt' => 'ppt', 'pptx' => 'ppt',
        'zip' => 'zip', 'rar' => 'zip', '7z' => 'zip',
        'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image', 'webp' => 'image',
        'mp4' => 'video', 'webm' => 'video', 'mov' => 'video',
        'mp3' => 'audio', 'wav' => 'audio', 'ogg' => 'audio',
        'txt' => 'text', 'csv' => 'text',
    ];
    $type = $type_map[$extension] ?? 'file';
}

// Iconos y colores por tipo
$type_config = [
    'pdf'   => ['icon' => '📄', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-600'],
    'doc'   => ['icon' => '📝', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
    'xls'   => ['icon' => '📊', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-600'],
    'ppt'   => ['icon' => '📽️', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
    'zip'   => ['icon' => '📦', 'color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
    'image' => ['icon' => '🖼️', 'color' => 'purple', 'bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
    'video' => ['icon' => '🎬', 'color' => 'pink', 'bg' => 'bg-pink-100', 'text' => 'text-pink-600'],
    'audio' => ['icon' => '🎵', 'color' => 'cyan', 'bg' => 'bg-cyan-100', 'text' => 'text-cyan-600'],
    'text'  => ['icon' => '📃', 'color' => 'gray', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600'],
    'file'  => ['icon' => '📁', 'color' => 'gray', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600'],
];
$config = $type_config[$type] ?? $type_config['file'];

$download_id = 'flavor-download-' . wp_rand(1000, 9999);
?>

<?php if ($variant === 'minimal'): ?>
    <!-- Variante Minimal -->
    <a href="<?php echo esc_url($url); ?>"
       class="flavor-file-download inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 hover:underline"
       id="<?php echo esc_attr($download_id); ?>"
       download
       <?php if ($track_download): ?>data-track="true"<?php endif; ?>>
        <span><?php echo esc_html($config['icon']); ?></span>
        <span><?php echo esc_html($filename); ?></span>
        <?php if ($size): ?>
            <span class="text-gray-400 text-sm">(<?php echo esc_html($size); ?>)</span>
        <?php endif; ?>
    </a>

<?php elseif ($variant === 'inline'): ?>
    <!-- Variante Inline -->
    <a href="<?php echo esc_url($url); ?>"
       class="flavor-file-download flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors group"
       id="<?php echo esc_attr($download_id); ?>"
       download
       <?php if ($track_download): ?>data-track="true"<?php endif; ?>>
        <span class="flex-shrink-0 w-10 h-10 rounded-lg <?php echo esc_attr($config['bg']); ?> flex items-center justify-center text-xl">
            <?php echo esc_html($config['icon']); ?>
        </span>
        <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-900 truncate group-hover:text-blue-600"><?php echo esc_html($filename); ?></p>
            <?php if ($size): ?>
                <p class="text-xs text-gray-500"><?php echo esc_html($size); ?></p>
            <?php endif; ?>
        </div>
        <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
    </a>

<?php elseif ($variant === 'card'): ?>
    <!-- Variante Card (con preview) -->
    <div class="flavor-file-download bg-white rounded-xl shadow-md overflow-hidden" id="<?php echo esc_attr($download_id); ?>">
        <?php if ($preview): ?>
            <div class="aspect-video bg-gray-100 relative">
                <?php if ($type === 'image'): ?>
                    <img src="<?php echo esc_url($preview); ?>" alt="" class="w-full h-full object-cover">
                <?php elseif ($type === 'pdf'): ?>
                    <iframe src="<?php echo esc_url($preview); ?>#toolbar=0" class="w-full h-full" loading="lazy"></iframe>
                <?php else: ?>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-6xl opacity-50"><?php echo esc_html($config['icon']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="p-4">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-12 h-12 rounded-lg <?php echo esc_attr($config['bg']); ?> flex items-center justify-center text-2xl">
                    <?php echo esc_html($config['icon']); ?>
                </span>
                <div class="flex-1 min-w-0">
                    <h4 class="font-medium text-gray-900 truncate"><?php echo esc_html($filename); ?></h4>
                    <?php if ($description): ?>
                        <p class="text-sm text-gray-500 mt-0.5 line-clamp-2"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                    <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                        <?php if ($size): ?>
                            <span><?php echo esc_html($size); ?></span>
                        <?php endif; ?>
                        <?php if ($downloads > 0): ?>
                            <span>📥 <?php echo number_format_i18n($downloads); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <a href="<?php echo esc_url($url); ?>"
               class="mt-4 flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
               download
               <?php if ($track_download): ?>data-track="true"<?php endif; ?>>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <?php esc_html_e('Descargar', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- Variante Default -->
    <div class="flavor-file-download bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow" id="<?php echo esc_attr($download_id); ?>">
        <div class="flex items-center gap-4">
            <span class="flex-shrink-0 w-14 h-14 rounded-xl <?php echo esc_attr($config['bg']); ?> flex items-center justify-center text-3xl shadow-sm">
                <?php echo esc_html($config['icon']); ?>
            </span>

            <div class="flex-1 min-w-0">
                <h4 class="font-medium text-gray-900 truncate"><?php echo esc_html($filename); ?></h4>
                <?php if ($description): ?>
                    <p class="text-sm text-gray-500 truncate"><?php echo esc_html($description); ?></p>
                <?php endif; ?>
                <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                    <?php if ($size): ?>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <?php echo esc_html($size); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($downloads > 0): ?>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <?php echo number_format_i18n($downloads); ?> <?php esc_html_e('descargas', 'flavor-chat-ia'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <a href="<?php echo esc_url($url); ?>"
               class="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
               download
               <?php if ($track_download): ?>data-track="true"<?php endif; ?>>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span class="hidden sm:inline"><?php esc_html_e('Descargar', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>
<?php endif; ?>

<?php if ($track_download): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('<?php echo esc_js($download_id); ?>');
    if (!container) return;

    const links = container.querySelectorAll('a[data-track="true"]');

    links.forEach(link => {
        link.addEventListener('click', function() {
            // Enviar tracking via AJAX
            fetch(flavorAjax?.url || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'flavor_track_download',
                    url: this.href,
                    _wpnonce: flavorAjax?.nonce || ''
                })
            }).catch(() => {}); // Silenciar errores

            // Actualizar contador visual si existe
            const counterEl = container.querySelector('.download-counter');
            if (counterEl) {
                const current = parseInt(counterEl.textContent.replace(/\D/g, '')) || 0;
                counterEl.textContent = (current + 1).toLocaleString();
            }
        });
    });
});
</script>
<?php endif; ?>
