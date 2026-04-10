<?php
/**
 * Template: Reproductor de Radio en Vivo
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

$nombre_radio = $nombre_radio ?? 'Radio Vecinal';
$programa_actual = $programa_actual ?? 'Magazine de la Manana';
$locutor = $locutor ?? 'Maria Garcia';
$oyentes = $oyentes ?? '234';
$stream_url = $stream_url ?? '#';
?>

<section class="flavor-component py-12" style="background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%);">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 shadow-2xl">
                <!-- Header del reproductor -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-400 to-indigo-600 flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                </svg>
                            </div>
                            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500"></span>
                            </span>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white"><?php echo esc_html($nombre_radio); ?></h2>
                            <p class="text-white/80 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-500 text-white">
                                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                    <?php echo esc_html__('EN VIVO', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center gap-2 text-white/80">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span class="text-xl font-bold text-white"><?php echo esc_html($oyentes); ?></span>
                            <span class="text-sm"><?php echo esc_html__('oyentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Info del programa actual -->
                <div class="bg-white/10 rounded-2xl p-6 mb-8">
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-white/60 text-sm mb-1"><?php echo esc_html__('Ahora sonando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <h3 class="text-xl font-bold text-white mb-1"><?php echo esc_html($programa_actual); ?></h3>
                            <p class="text-white/80 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                con <?php echo esc_html($locutor); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-white/60 text-sm"><?php echo esc_html__('Horario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <p class="text-white font-semibold">8:00 - 11:00</p>
                        </div>
                    </div>
                </div>

                <!-- Visualizador de audio -->
                <div class="flex items-center justify-center gap-1 h-16 mb-8">
                    <?php for ($i = 0; $i < 40; $i++): ?>
                        <div class="w-1.5 bg-white/80 rounded-full animate-pulse" style="height: <?php echo rand(20, 100); ?>%; animation-delay: <?php echo $i * 0.05; ?>s;"></div>
                    <?php endfor; ?>
                </div>

                <!-- Controles -->
                <div class="flex items-center justify-center gap-6 mb-8">
                    <button class="p-3 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button id="play-btn" class="p-6 rounded-full bg-white text-purple-600 hover:scale-110 transition-transform shadow-lg">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>
                    <button class="p-3 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                <!-- Control de volumen -->
                <div class="flex items-center justify-center gap-4">
                    <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    </svg>
                    <input type="range" min="0" max="100" value="75" class="w-48 h-2 bg-white/20 rounded-full appearance-none cursor-pointer">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    </svg>
                </div>

                <!-- Acciones adicionales -->
                <div class="flex items-center justify-center gap-4 mt-8 pt-6 border-t border-white/10">
                    <button class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <?php echo esc_html__('Compartir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <?php echo esc_html__('Chat en vivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <?php echo esc_html__('Llamar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
