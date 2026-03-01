<?php
/**
 * Componente: Audio Player
 *
 * Reproductor de audio personalizado con controles avanzados.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $src        URL del archivo de audio
 * @param string $title      Título del audio
 * @param string $artist     Artista/autor
 * @param string $cover      URL de la imagen de portada
 * @param string $duration   Duración (si se conoce)
 * @param bool   $autoplay   Reproducir automáticamente
 * @param bool   $loop       Repetir
 * @param bool   $show_waveform Mostrar forma de onda (requiere wavesurfer.js)
 * @param string $variant    Variante: default, compact, minimal, card
 * @param string $color      Color del tema
 */

if (!defined('ABSPATH')) {
    exit;
}

$src = $src ?? '';
$title = $title ?? __('Audio', 'flavor-chat-ia');
$artist = $artist ?? '';
$cover = $cover ?? '';
$duration = $duration ?? '';
$autoplay = $autoplay ?? false;
$loop = $loop ?? false;
$show_waveform = $show_waveform ?? false;
$variant = $variant ?? 'default';
$color = $color ?? 'blue';

$player_id = 'flavor-audio-' . wp_rand(1000, 9999);

// Colores
$color_config = [
    'blue'   => ['bg' => 'bg-blue-600', 'text' => 'text-blue-600', 'hover' => 'hover:bg-blue-700', 'slider' => '#3B82F6'],
    'green'  => ['bg' => 'bg-green-600', 'text' => 'text-green-600', 'hover' => 'hover:bg-green-700', 'slider' => '#16A34A'],
    'purple' => ['bg' => 'bg-purple-600', 'text' => 'text-purple-600', 'hover' => 'hover:bg-purple-700', 'slider' => '#9333EA'],
    'red'    => ['bg' => 'bg-red-600', 'text' => 'text-red-600', 'hover' => 'hover:bg-red-700', 'slider' => '#DC2626'],
    'orange' => ['bg' => 'bg-orange-500', 'text' => 'text-orange-500', 'hover' => 'hover:bg-orange-600', 'slider' => '#F97316'],
];
$col = $color_config[$color] ?? $color_config['blue'];
?>

<?php if ($variant === 'minimal'): ?>
    <!-- Variante Minimal -->
    <div class="flavor-audio-player flavor-audio-minimal flex items-center gap-3 p-2" id="<?php echo esc_attr($player_id); ?>">
        <button type="button" class="audio-play-btn w-8 h-8 rounded-full <?php echo esc_attr($col['bg']); ?> text-white flex items-center justify-center <?php echo esc_attr($col['hover']); ?> transition-colors">
            <svg class="play-icon w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
            </svg>
            <svg class="pause-icon w-4 h-4 hidden" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
            </svg>
        </button>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate"><?php echo esc_html($title); ?></p>
        </div>
        <span class="audio-time text-xs text-gray-500 font-mono">0:00</span>
        <audio src="<?php echo esc_url($src); ?>" <?php echo $autoplay ? 'autoplay' : ''; ?> <?php echo $loop ? 'loop' : ''; ?> preload="metadata"></audio>
    </div>

<?php elseif ($variant === 'compact'): ?>
    <!-- Variante Compact -->
    <div class="flavor-audio-player flavor-audio-compact bg-white rounded-lg shadow-sm border p-3" id="<?php echo esc_attr($player_id); ?>">
        <div class="flex items-center gap-3">
            <?php if ($cover): ?>
                <img src="<?php echo esc_url($cover); ?>" alt="" class="w-10 h-10 rounded object-cover">
            <?php else: ?>
                <div class="w-10 h-10 rounded <?php echo esc_attr($col['bg']); ?> flex items-center justify-center text-white">
                    🎵
                </div>
            <?php endif; ?>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate"><?php echo esc_html($title); ?></p>
                <?php if ($artist): ?>
                    <p class="text-xs text-gray-500 truncate"><?php echo esc_html($artist); ?></p>
                <?php endif; ?>
            </div>

            <button type="button" class="audio-play-btn w-10 h-10 rounded-full <?php echo esc_attr($col['bg']); ?> text-white flex items-center justify-center <?php echo esc_attr($col['hover']); ?> transition-colors">
                <svg class="play-icon w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <svg class="pause-icon w-5 h-5 hidden" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                </svg>
            </button>
        </div>

        <!-- Progress bar -->
        <div class="mt-3 flex items-center gap-2">
            <span class="audio-current-time text-xs text-gray-500 font-mono w-10">0:00</span>
            <div class="audio-progress flex-1 h-1 bg-gray-200 rounded-full cursor-pointer overflow-hidden">
                <div class="audio-progress-bar h-full <?php echo esc_attr($col['bg']); ?> rounded-full" style="width: 0%;"></div>
            </div>
            <span class="audio-duration text-xs text-gray-500 font-mono w-10 text-right"><?php echo $duration ?: '0:00'; ?></span>
        </div>

        <audio src="<?php echo esc_url($src); ?>" <?php echo $autoplay ? 'autoplay' : ''; ?> <?php echo $loop ? 'loop' : ''; ?> preload="metadata"></audio>
    </div>

<?php elseif ($variant === 'card'): ?>
    <!-- Variante Card (grande con cover) -->
    <div class="flavor-audio-player flavor-audio-card bg-white rounded-xl shadow-lg overflow-hidden" id="<?php echo esc_attr($player_id); ?>">
        <!-- Cover -->
        <div class="relative aspect-square bg-gradient-to-br from-gray-700 to-gray-900">
            <?php if ($cover): ?>
                <img src="<?php echo esc_url($cover); ?>" alt="" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-8xl opacity-50">🎵</span>
                </div>
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
        </div>

        <!-- Info y controles -->
        <div class="p-4">
            <h3 class="font-bold text-gray-900 truncate"><?php echo esc_html($title); ?></h3>
            <?php if ($artist): ?>
                <p class="text-sm text-gray-500"><?php echo esc_html($artist); ?></p>
            <?php endif; ?>

            <!-- Progress -->
            <div class="mt-4 flex items-center gap-2">
                <span class="audio-current-time text-xs text-gray-500 font-mono">0:00</span>
                <div class="audio-progress flex-1 h-1.5 bg-gray-200 rounded-full cursor-pointer overflow-hidden">
                    <div class="audio-progress-bar h-full <?php echo esc_attr($col['bg']); ?> rounded-full transition-all" style="width: 0%;"></div>
                </div>
                <span class="audio-duration text-xs text-gray-500 font-mono"><?php echo $duration ?: '0:00'; ?></span>
            </div>

            <!-- Controls -->
            <div class="mt-4 flex items-center justify-center gap-4">
                <button type="button" class="audio-rewind p-2 text-gray-400 hover:text-gray-600 transition-colors" title="<?php esc_attr_e('-10s', 'flavor-chat-ia'); ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
                    </svg>
                </button>

                <button type="button" class="audio-play-btn w-14 h-14 rounded-full <?php echo esc_attr($col['bg']); ?> text-white flex items-center justify-center <?php echo esc_attr($col['hover']); ?> transition-colors shadow-lg">
                    <svg class="play-icon w-7 h-7 ml-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <svg class="pause-icon w-7 h-7 hidden" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                    </svg>
                </button>

                <button type="button" class="audio-forward p-2 text-gray-400 hover:text-gray-600 transition-colors" title="<?php esc_attr_e('+10s', 'flavor-chat-ia'); ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/>
                    </svg>
                </button>
            </div>

            <!-- Volume -->
            <div class="mt-4 flex items-center justify-center gap-2">
                <button type="button" class="audio-mute p-1 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="volume-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    </svg>
                    <svg class="mute-icon w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                    </svg>
                </button>
                <input type="range" class="audio-volume w-24 h-1" min="0" max="1" step="0.1" value="1">
            </div>
        </div>

        <audio src="<?php echo esc_url($src); ?>" <?php echo $autoplay ? 'autoplay' : ''; ?> <?php echo $loop ? 'loop' : ''; ?> preload="metadata"></audio>
    </div>

<?php else: ?>
    <!-- Variante Default -->
    <div class="flavor-audio-player flavor-audio-default bg-gray-100 rounded-xl p-4" id="<?php echo esc_attr($player_id); ?>">
        <div class="flex items-center gap-4">
            <?php if ($cover): ?>
                <img src="<?php echo esc_url($cover); ?>" alt="" class="w-16 h-16 rounded-lg object-cover shadow-md">
            <?php endif; ?>

            <div class="flex-1 min-w-0">
                <h3 class="font-medium text-gray-900 truncate"><?php echo esc_html($title); ?></h3>
                <?php if ($artist): ?>
                    <p class="text-sm text-gray-500 truncate"><?php echo esc_html($artist); ?></p>
                <?php endif; ?>

                <!-- Progress -->
                <div class="mt-2 flex items-center gap-2">
                    <span class="audio-current-time text-xs text-gray-500 font-mono">0:00</span>
                    <div class="audio-progress flex-1 h-1 bg-gray-300 rounded-full cursor-pointer overflow-hidden">
                        <div class="audio-progress-bar h-full <?php echo esc_attr($col['bg']); ?> rounded-full" style="width: 0%;"></div>
                    </div>
                    <span class="audio-duration text-xs text-gray-500 font-mono"><?php echo $duration ?: '0:00'; ?></span>
                </div>
            </div>

            <button type="button" class="audio-play-btn w-12 h-12 rounded-full <?php echo esc_attr($col['bg']); ?> text-white flex items-center justify-center <?php echo esc_attr($col['hover']); ?> transition-colors shadow-md">
                <svg class="play-icon w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <svg class="pause-icon w-6 h-6 hidden" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                </svg>
            </button>
        </div>

        <audio src="<?php echo esc_url($src); ?>" <?php echo $autoplay ? 'autoplay' : ''; ?> <?php echo $loop ? 'loop' : ''; ?> preload="metadata"></audio>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const player = document.getElementById('<?php echo esc_js($player_id); ?>');
    if (!player) return;

    const audio = player.querySelector('audio');
    const playBtn = player.querySelector('.audio-play-btn');
    const playIcon = player.querySelector('.play-icon');
    const pauseIcon = player.querySelector('.pause-icon');
    const progress = player.querySelector('.audio-progress');
    const progressBar = player.querySelector('.audio-progress-bar');
    const currentTime = player.querySelector('.audio-current-time');
    const duration = player.querySelector('.audio-duration');
    const rewindBtn = player.querySelector('.audio-rewind');
    const forwardBtn = player.querySelector('.audio-forward');
    const muteBtn = player.querySelector('.audio-mute');
    const volumeSlider = player.querySelector('.audio-volume');
    const volumeIcon = player.querySelector('.volume-icon');
    const muteIcon = player.querySelector('.mute-icon');
    const timeDisplay = player.querySelector('.audio-time'); // For minimal variant

    function formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Play/Pause
    playBtn?.addEventListener('click', function() {
        if (audio.paused) {
            audio.play();
        } else {
            audio.pause();
        }
    });

    audio.addEventListener('play', function() {
        playIcon?.classList.add('hidden');
        pauseIcon?.classList.remove('hidden');
    });

    audio.addEventListener('pause', function() {
        playIcon?.classList.remove('hidden');
        pauseIcon?.classList.add('hidden');
    });

    // Update progress
    audio.addEventListener('timeupdate', function() {
        const percent = (audio.currentTime / audio.duration) * 100;
        if (progressBar) progressBar.style.width = percent + '%';
        if (currentTime) currentTime.textContent = formatTime(audio.currentTime);
        if (timeDisplay) timeDisplay.textContent = formatTime(audio.currentTime);
    });

    audio.addEventListener('loadedmetadata', function() {
        if (duration) duration.textContent = formatTime(audio.duration);
    });

    // Seek
    progress?.addEventListener('click', function(e) {
        const rect = this.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        audio.currentTime = percent * audio.duration;
    });

    // Rewind/Forward
    rewindBtn?.addEventListener('click', () => audio.currentTime = Math.max(0, audio.currentTime - 10));
    forwardBtn?.addEventListener('click', () => audio.currentTime = Math.min(audio.duration, audio.currentTime + 10));

    // Volume
    volumeSlider?.addEventListener('input', function() {
        audio.volume = this.value;
        audio.muted = this.value == 0;
        updateVolumeIcon();
    });

    muteBtn?.addEventListener('click', function() {
        audio.muted = !audio.muted;
        updateVolumeIcon();
    });

    function updateVolumeIcon() {
        if (!volumeIcon || !muteIcon) return;
        if (audio.muted || audio.volume === 0) {
            volumeIcon.classList.add('hidden');
            muteIcon.classList.remove('hidden');
        } else {
            volumeIcon.classList.remove('hidden');
            muteIcon.classList.add('hidden');
        }
    }
});
</script>
