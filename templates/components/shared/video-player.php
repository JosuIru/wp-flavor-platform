<?php
/**
 * Componente: Video Player
 *
 * Reproductor de vídeo con soporte para múltiples fuentes y embeds.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $src        URL del vídeo o embed (YouTube, Vimeo)
 * @param string $poster     Imagen de portada
 * @param string $title      Título del vídeo
 * @param bool   $autoplay   Reproducir automáticamente
 * @param bool   $loop       Repetir
 * @param bool   $muted      Silenciado (requerido para autoplay)
 * @param bool   $controls   Mostrar controles
 * @param string $aspect     Ratio: 16:9, 4:3, 1:1, 9:16
 * @param string $variant    Variante: default, card, minimal
 * @param int    $width      Ancho máximo en px
 */

if (!defined('ABSPATH')) {
    exit;
}

$src = $src ?? '';
$poster = $poster ?? '';
$title = $title ?? '';
$autoplay = $autoplay ?? false;
$loop = $loop ?? false;
$muted = $muted ?? false;
$controls = $controls ?? true;
$aspect = $aspect ?? '16:9';
$variant = $variant ?? 'default';
$width = $width ?? 0;

$player_id = 'flavor-video-' . wp_rand(1000, 9999);

// Detectar tipo de vídeo
$video_type = 'native';
$embed_id = '';

if (strpos($src, 'youtube.com') !== false || strpos($src, 'youtu.be') !== false) {
    $video_type = 'youtube';
    // Extraer ID de YouTube
    preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $src, $matches);
    $embed_id = $matches[1] ?? '';
} elseif (strpos($src, 'vimeo.com') !== false) {
    $video_type = 'vimeo';
    // Extraer ID de Vimeo
    preg_match('/vimeo\.com\/(\d+)/', $src, $matches);
    $embed_id = $matches[1] ?? '';
}

// Aspect ratio classes
$aspect_classes = [
    '16:9' => 'aspect-video',
    '4:3'  => 'aspect-[4/3]',
    '1:1'  => 'aspect-square',
    '9:16' => 'aspect-[9/16]',
    '21:9' => 'aspect-[21/9]',
];
$aspect_class = $aspect_classes[$aspect] ?? $aspect_classes['16:9'];

// Width style
$width_style = $width ? "max-width: {$width}px;" : '';
?>

<?php if ($variant === 'card'): ?>
    <!-- Variante Card -->
    <div class="flavor-video-player flavor-video-card bg-white rounded-xl shadow-lg overflow-hidden" id="<?php echo esc_attr($player_id); ?>" <?php if ($width_style): ?>style="<?php echo esc_attr($width_style); ?>"<?php endif; ?>>
        <div class="<?php echo esc_attr($aspect_class); ?> relative bg-black">
            <?php if ($video_type === 'youtube' && $embed_id): ?>
                <iframe
                    src="https://www.youtube.com/embed/<?php echo esc_attr($embed_id); ?>?rel=0<?php echo $autoplay ? '&autoplay=1' : ''; ?><?php echo $muted ? '&mute=1' : ''; ?><?php echo $loop ? '&loop=1&playlist=' . esc_attr($embed_id) : ''; ?>"
                    class="absolute inset-0 w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy">
                </iframe>
            <?php elseif ($video_type === 'vimeo' && $embed_id): ?>
                <iframe
                    src="https://player.vimeo.com/video/<?php echo esc_attr($embed_id); ?>?<?php echo $autoplay ? 'autoplay=1&' : ''; ?><?php echo $muted ? 'muted=1&' : ''; ?><?php echo $loop ? 'loop=1&' : ''; ?>"
                    class="absolute inset-0 w-full h-full"
                    frameborder="0"
                    allow="autoplay; fullscreen; picture-in-picture"
                    allowfullscreen
                    loading="lazy">
                </iframe>
            <?php else: ?>
                <video
                    class="absolute inset-0 w-full h-full object-cover"
                    <?php echo $poster ? 'poster="' . esc_url($poster) . '"' : ''; ?>
                    <?php echo $autoplay ? 'autoplay' : ''; ?>
                    <?php echo $loop ? 'loop' : ''; ?>
                    <?php echo $muted ? 'muted' : ''; ?>
                    <?php echo $controls ? 'controls' : ''; ?>
                    playsinline>
                    <source src="<?php echo esc_url($src); ?>" type="video/mp4">
                    <?php esc_html_e('Tu navegador no soporta vídeos HTML5.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </video>
            <?php endif; ?>
        </div>

        <?php if ($title): ?>
            <div class="p-4">
                <h3 class="font-medium text-gray-900"><?php echo esc_html($title); ?></h3>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($variant === 'minimal'): ?>
    <!-- Variante Minimal (sin bordes ni sombras) -->
    <div class="flavor-video-player flavor-video-minimal <?php echo esc_attr($aspect_class); ?> relative bg-black rounded overflow-hidden" id="<?php echo esc_attr($player_id); ?>" <?php if ($width_style): ?>style="<?php echo esc_attr($width_style); ?>"<?php endif; ?>>
        <?php if ($video_type === 'youtube' && $embed_id): ?>
            <iframe
                src="https://www.youtube.com/embed/<?php echo esc_attr($embed_id); ?>?rel=0&modestbranding=1<?php echo $autoplay ? '&autoplay=1' : ''; ?><?php echo $muted ? '&mute=1' : ''; ?>"
                class="absolute inset-0 w-full h-full"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                loading="lazy">
            </iframe>
        <?php elseif ($video_type === 'vimeo' && $embed_id): ?>
            <iframe
                src="https://player.vimeo.com/video/<?php echo esc_attr($embed_id); ?>?"
                class="absolute inset-0 w-full h-full"
                frameborder="0"
                allow="autoplay; fullscreen; picture-in-picture"
                allowfullscreen
                loading="lazy">
            </iframe>
        <?php else: ?>
            <video
                class="absolute inset-0 w-full h-full object-contain"
                <?php echo $poster ? 'poster="' . esc_url($poster) . '"' : ''; ?>
                <?php echo $autoplay ? 'autoplay' : ''; ?>
                <?php echo $loop ? 'loop' : ''; ?>
                <?php echo $muted ? 'muted' : ''; ?>
                <?php echo $controls ? 'controls' : ''; ?>
                playsinline>
                <source src="<?php echo esc_url($src); ?>" type="video/mp4">
            </video>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Variante Default -->
    <div class="flavor-video-player flavor-video-default rounded-xl overflow-hidden shadow-lg" id="<?php echo esc_attr($player_id); ?>" <?php if ($width_style): ?>style="<?php echo esc_attr($width_style); ?>"<?php endif; ?>>
        <div class="<?php echo esc_attr($aspect_class); ?> relative bg-black">
            <?php if ($video_type === 'youtube' && $embed_id): ?>
                <!-- YouTube con lazy loading y poster personalizado -->
                <?php if ($poster && !$autoplay): ?>
                    <div class="video-poster-overlay absolute inset-0 cursor-pointer group">
                        <img src="<?php echo esc_url($poster); ?>" alt="" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/30 group-hover:bg-black/40 transition-colors flex items-center justify-center">
                            <span class="w-16 h-16 rounded-full bg-red-600 text-white flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    <iframe
                        class="video-iframe absolute inset-0 w-full h-full hidden"
                        data-src="https://www.youtube.com/embed/<?php echo esc_attr($embed_id); ?>?rel=0&autoplay=1"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                <?php else: ?>
                    <iframe
                        src="https://www.youtube.com/embed/<?php echo esc_attr($embed_id); ?>?rel=0<?php echo $autoplay ? '&autoplay=1' : ''; ?><?php echo $muted ? '&mute=1' : ''; ?>"
                        class="absolute inset-0 w-full h-full"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        loading="lazy">
                    </iframe>
                <?php endif; ?>

            <?php elseif ($video_type === 'vimeo' && $embed_id): ?>
                <iframe
                    src="https://player.vimeo.com/video/<?php echo esc_attr($embed_id); ?>?<?php echo $autoplay ? 'autoplay=1&' : ''; ?><?php echo $muted ? 'muted=1&' : ''; ?>"
                    class="absolute inset-0 w-full h-full"
                    frameborder="0"
                    allow="autoplay; fullscreen; picture-in-picture"
                    allowfullscreen
                    loading="lazy">
                </iframe>

            <?php else: ?>
                <!-- Vídeo nativo con controles personalizados opcionales -->
                <video
                    class="video-element absolute inset-0 w-full h-full object-contain"
                    <?php echo $poster ? 'poster="' . esc_url($poster) . '"' : ''; ?>
                    <?php echo $autoplay ? 'autoplay' : ''; ?>
                    <?php echo $loop ? 'loop' : ''; ?>
                    <?php echo $muted ? 'muted' : ''; ?>
                    <?php echo $controls ? 'controls' : ''; ?>
                    playsinline>
                    <source src="<?php echo esc_url($src); ?>" type="video/mp4">
                    <?php esc_html_e('Tu navegador no soporta vídeos HTML5.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </video>

                <?php if (!$controls): ?>
                    <!-- Overlay play button para vídeos sin controles -->
                    <div class="video-play-overlay absolute inset-0 flex items-center justify-center bg-black/30 cursor-pointer transition-opacity hover:bg-black/40">
                        <span class="w-20 h-20 rounded-full bg-white/90 text-gray-900 flex items-center justify-center shadow-xl">
                            <svg class="w-10 h-10 ml-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const player = document.getElementById('<?php echo esc_js($player_id); ?>');
    if (!player) return;

    // Lazy load YouTube con poster
    const posterOverlay = player.querySelector('.video-poster-overlay');
    const iframe = player.querySelector('.video-iframe');

    if (posterOverlay && iframe) {
        posterOverlay.addEventListener('click', function() {
            iframe.src = iframe.dataset.src;
            iframe.classList.remove('hidden');
            posterOverlay.remove();
        });
    }

    // Play overlay para vídeos nativos sin controles
    const playOverlay = player.querySelector('.video-play-overlay');
    const video = player.querySelector('.video-element');

    if (playOverlay && video) {
        playOverlay.addEventListener('click', function() {
            if (video.paused) {
                video.play();
                playOverlay.style.opacity = '0';
                playOverlay.style.pointerEvents = 'none';
            }
        });

        video.addEventListener('pause', function() {
            playOverlay.style.opacity = '1';
            playOverlay.style.pointerEvents = 'auto';
        });

        video.addEventListener('ended', function() {
            playOverlay.style.opacity = '1';
            playOverlay.style.pointerEvents = 'auto';
        });
    }
});
</script>
