<?php
/**
 * Frontend: Single Programa de Radio
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$programa = $programa ?? [];
$titulo = $programa['titulo'] ?? 'Programa';
$descripcion = $programa['descripcion'] ?? '';
$imagen = $programa['imagen'] ?? 'https://picsum.photos/seed/radio1/800/450';
$locutor = $programa['locutor'] ?? [];
$horario = $programa['horario'] ?? 'Lunes a Viernes 10:00-12:00';
$episodios = $programa['episodios'] ?? [];
?>

<div class="flavor-single radio">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-600 to-rose-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <nav class="flex items-center gap-2 text-sm text-white/80 mb-6">
                <a href="#" class="hover:text-white">Radio</a>
                <span>/</span>
                <span class="text-white"><?php echo esc_html($titulo); ?></span>
            </nav>

            <div class="flex flex-col md:flex-row items-start gap-8">
                <div class="w-48 h-48 rounded-2xl overflow-hidden shadow-2xl flex-shrink-0">
                    <img src="<?php echo esc_url($imagen); ?>"
                         alt="<?php echo esc_attr($titulo); ?>"
                         class="w-full h-full object-cover">
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?php echo esc_html($titulo); ?></h1>
                    <p class="text-white/90 mb-4"><?php echo esc_html($horario); ?></p>
                    <div class="flex items-center gap-4">
                        <button class="px-6 py-3 rounded-xl bg-white text-red-600 font-semibold hover:bg-gray-100 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            Escuchar en directo
                        </button>
                        <button class="px-6 py-3 rounded-xl bg-white/20 text-white font-semibold hover:bg-white/30 transition-colors">
                            Seguir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Episodios anteriores -->
            <div class="lg:col-span-2">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Programas Anteriores</h2>

                <?php if (empty($episodios)): ?>
                    <div class="bg-gray-50 rounded-xl p-8 text-center">
                        <p class="text-gray-500">No hay programas grabados disponibles</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($episodios as $episodio): ?>
                            <article class="bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition-shadow">
                                <div class="flex items-center gap-4">
                                    <button class="w-12 h-12 rounded-full bg-red-600 text-white flex items-center justify-center flex-shrink-0 hover:bg-red-700 transition-colors">
                                        <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900"><?php echo esc_html($episodio['titulo'] ?? 'Programa'); ?></h3>
                                        <div class="flex items-center gap-3 text-sm text-gray-500">
                                            <span><?php echo esc_html($episodio['fecha'] ?? 'Ayer'); ?></span>
                                            <span><?php echo esc_html($episodio['duracion'] ?? '2h'); ?></span>
                                        </div>
                                    </div>
                                    <button class="p-2 text-gray-400 hover:text-red-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Sobre el programa -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h3 class="font-bold text-gray-900 mb-4">Sobre el programa</h3>
                    <p class="text-gray-700 text-sm"><?php echo esc_html($descripcion); ?></p>
                </div>

                <!-- Locutor -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h3 class="font-bold text-gray-900 mb-4">Locutor</h3>
                    <div class="flex items-center gap-4">
                        <img src="<?php echo esc_url($locutor['avatar'] ?? 'https://i.pravatar.cc/150?img=1'); ?>"
                             alt="<?php echo esc_attr($locutor['nombre'] ?? 'Locutor'); ?>"
                             class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo esc_html($locutor['nombre'] ?? 'Nombre'); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html($locutor['programas'] ?? 1); ?> programas</p>
                        </div>
                    </div>
                </div>

                <!-- Redes -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h3 class="font-bold text-gray-900 mb-4">Escuchar en</h3>
                    <div class="space-y-2">
                        <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-red-50 transition-colors">
                            <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                            </svg>
                            <span class="font-medium text-gray-700">Spotify</span>
                        </a>
                        <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-red-50 transition-colors">
                            <svg class="w-6 h-6 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701"/>
                            </svg>
                            <span class="font-medium text-gray-700">Apple Podcasts</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
