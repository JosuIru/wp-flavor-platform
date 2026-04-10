<?php
/**
 * Template: Radio Player
 *
 * Live radio player with now playing information
 *
 * @package FlavorPlatform
 * @subpackage Templates/Components/Radio
 */

defined('ABSPATH') || exit;

// Default values
$titulo_seccion = $args['titulo_seccion'] ?? 'Reproductor en Vivo';
$url_stream = $args['url_stream'] ?? '';
$nombre_emisora = $args['nombre_emisora'] ?? 'Radio Comunitaria';
$programa_actual = $args['programa_actual'] ?? 'Programación General';
$locutor_actual = $args['locutor_actual'] ?? 'Equipo de Radio';
$horario_actual = $args['horario_actual'] ?? '';
$imagen_programa = $args['imagen_programa'] ?? '';
$descripcion_programa = $args['descripcion_programa'] ?? 'En este momento estamos transmitiendo nuestra programación comunitaria.';
$mostrar_chat = $args['mostrar_chat'] ?? true;
$mostrar_llamada = $args['mostrar_llamada'] ?? true;
$telefono_emisora = $args['telefono_emisora'] ?? '';
$color_principal = $args['color_principal'] ?? 'purple';
?>

<section class="py-12 md:py-16 bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="container mx-auto px-4">
        <!-- Section Title -->
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <div class="flex items-center justify-center gap-2">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                </span>
                <span class="text-red-600 font-semibold text-sm uppercase tracking-wide">En Vivo</span>
            </div>
        </div>

        <div class="max-w-5xl mx-auto">
            <!-- Main Player Card -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-200">
                <div class="grid md:grid-cols-2 gap-0">
                    <!-- Album Art / Visual Section -->
                    <div class="relative bg-gradient-to-br from-<?php echo esc_attr($color_principal); ?>-600 to-<?php echo esc_attr($color_principal); ?>-800 p-8 md:p-12 flex items-center justify-center">
                        <?php if ($imagen_programa): ?>
                            <img src="<?php echo esc_url($imagen_programa); ?>"
                                 alt="<?php echo esc_attr($programa_actual); ?>"
                                 class="w-64 h-64 object-cover rounded-2xl shadow-2xl">
                        <?php else: ?>
                            <!-- Default Radio Icon -->
                            <div class="w-64 h-64 bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl flex items-center justify-center border-4 border-white/30">
                                <svg class="w-32 h-32 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.348 14.651a3.75 3.75 0 010-5.303m5.304 0a3.75 3.75 0 010 5.303m-7.425 2.122a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.789m13.788 0c3.808 3.808 3.808 9.981 0 13.79M12 12h.008v.007H12V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <!-- Animated Sound Waves -->
                        <div class="absolute bottom-8 left-8 right-8 flex items-end justify-center gap-1 h-16">
                            <?php for ($i = 0; $i < 24; $i++): ?>
                                <div class="w-1 bg-white/40 rounded-full animate-pulse"
                                     style="height: <?php echo rand(20, 100); ?>%; animation-delay: <?php echo $i * 0.05; ?>s; animation-duration: <?php echo rand(5, 15) / 10; ?>s;"></div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Player Controls & Info -->
                    <div class="p-8 md:p-10 flex flex-col justify-between">
                        <!-- Now Playing Info -->
                        <div class="mb-6">
                            <div class="text-sm font-semibold text-<?php echo esc_attr($color_principal); ?>-600 mb-2 uppercase tracking-wide">
                                Ahora en el aire
                            </div>
                            <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                                <?php echo esc_html($programa_actual); ?>
                            </h3>
                            <div class="flex items-center gap-2 text-gray-600 mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="font-medium"><?php echo esc_html($locutor_actual); ?></span>
                            </div>
                            <?php if ($horario_actual): ?>
                                <div class="flex items-center gap-2 text-gray-600 mb-4">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm"><?php echo esc_html($horario_actual); ?></span>
                                </div>
                            <?php endif; ?>
                            <p class="text-gray-600 text-sm leading-relaxed">
                                <?php echo esc_html($descripcion_programa); ?>
                            </p>
                        </div>

                        <!-- Player Controls -->
                        <div class="space-y-6">
                            <!-- Main Play Button -->
                            <div class="flex items-center justify-center">
                                <button id="playPauseBtn"
                                        class="group relative w-20 h-20 bg-gradient-to-br from-<?php echo esc_attr($color_principal); ?>-500 to-<?php echo esc_attr($color_principal); ?>-700 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 flex items-center justify-center">
                                    <!-- Play Icon -->
                                    <svg id="playIcon" class="w-10 h-10 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                    <!-- Pause Icon (hidden by default) -->
                                    <svg id="pauseIcon" class="w-10 h-10 text-white hidden" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Volume Control -->
                            <div class="flex items-center gap-4">
                                <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                </svg>
                                <input type="range"
                                       id="volumeControl"
                                       min="0"
                                       max="100"
                                       value="80"
                                       class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider">
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3">
                                <?php if ($mostrar_chat): ?>
                                    <button class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors duration-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                        <span class="hidden sm:inline">Chat</span>
                                    </button>
                                <?php endif; ?>

                                <?php if ($mostrar_llamada && $telefono_emisora): ?>
                                    <a href="tel:<?php echo esc_attr($telefono_emisora); ?>"
                                       class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-green-500 hover:bg-green-600 text-white font-medium rounded-xl transition-colors duration-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <span class="hidden sm:inline">Llamar</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Listener Counter (Optional) -->
            <div class="mt-6 text-center">
                <div class="inline-flex items-center gap-2 px-6 py-3 bg-white rounded-full shadow-md">
                    <svg class="w-5 h-5 text-<?php echo esc_attr($color_principal); ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="text-gray-700 font-medium">
                        <span id="listenerCount" class="font-bold text-<?php echo esc_attr($color_principal); ?>-600">0</span> oyentes conectados
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Audio Element (Hidden) -->
<?php if ($url_stream): ?>
    <audio id="radioStream" preload="none">
        <source src="<?php echo esc_url($url_stream); ?>" type="audio/mpeg">
        Tu navegador no soporta el elemento de audio.
    </audio>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const audio = document.getElementById('radioStream');
    const playPauseBtn = document.getElementById('playPauseBtn');
    const playIcon = document.getElementById('playIcon');
    const pauseIcon = document.getElementById('pauseIcon');
    const volumeControl = document.getElementById('volumeControl');

    if (!audio) return;

    let isPlaying = false;

    // Play/Pause functionality
    playPauseBtn.addEventListener('click', function() {
        if (isPlaying) {
            audio.pause();
            playIcon.classList.remove('hidden');
            pauseIcon.classList.add('hidden');
        } else {
            audio.play().catch(error => {
                console.error('Error al reproducir:', error);
                alert('No se pudo iniciar la reproducción. Por favor, intenta de nuevo.');
            });
            playIcon.classList.add('hidden');
            pauseIcon.classList.remove('hidden');
        }
        isPlaying = !isPlaying;
    });

    // Volume control
    if (volumeControl) {
        volumeControl.addEventListener('input', function() {
            audio.volume = this.value / 100;
        });

        // Set initial volume
        audio.volume = 0.8;
    }

    // Random listener count simulation (replace with real data)
    const listenerCountElement = document.getElementById('listenerCount');
    if (listenerCountElement) {
        setInterval(function() {
            const randomCount = Math.floor(Math.random() * 50) + 120;
            listenerCountElement.textContent = randomCount;
        }, 5000);
        listenerCountElement.textContent = Math.floor(Math.random() * 50) + 120;
    }
});
</script>

<style>
/* Custom Range Slider Styling */
.slider::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    background: <?php echo esc_attr($color_principal === 'purple' ? '#9333ea' : '#3b82f6'); ?>;
    cursor: pointer;
    border-radius: 50%;
}

.slider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    background: <?php echo esc_attr($color_principal === 'purple' ? '#9333ea' : '#3b82f6'); ?>;
    cursor: pointer;
    border-radius: 50%;
    border: none;
}
</style>
