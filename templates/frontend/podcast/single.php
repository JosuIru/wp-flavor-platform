<?php
/**
 * Frontend: Single Podcast
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$podcast = $podcast ?? [];
$titulo = $podcast['titulo'] ?? 'Podcast';
$descripcion = $podcast['descripcion'] ?? '';
$portada = $podcast['portada'] ?? 'https://picsum.photos/seed/podcast1/400/400';
$autor = $podcast['autor'] ?? [];
$episodios = $podcast['episodios'] ?? [];
$categoria = $podcast['categoria'] ?? 'General';
?>

<div class="flavor-single podcast">
    <!-- Header con portada -->
    <div class="bg-gradient-to-r from-teal-500 to-emerald-500 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="w-48 h-48 rounded-2xl overflow-hidden shadow-2xl flex-shrink-0">
                    <img src="<?php echo esc_url($portada); ?>"
                         alt="<?php echo esc_attr($titulo); ?>"
                         class="w-full h-full object-cover">
                </div>
                <div class="text-center md:text-left">
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-white/20 text-white mb-4">
                        <?php echo esc_html($categoria); ?>
                    </span>
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?php echo esc_html($titulo); ?></h1>
                    <p class="text-white/90 mb-4"><?php echo esc_html($autor['nombre'] ?? 'Creador'); ?></p>
                    <div class="flex items-center justify-center md:justify-start gap-4">
                        <button class="px-6 py-3 rounded-xl bg-white text-teal-600 font-semibold hover:bg-gray-100 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <?php echo esc_html__('Reproducir', 'flavor-chat-ia'); ?>
                        </button>
                        <button class="px-6 py-3 rounded-xl bg-white/20 text-white font-semibold hover:bg-white/30 transition-colors">
                            <?php echo esc_html__('Suscribirse', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Lista de episodios -->
            <div class="lg:col-span-2">
                <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo count($episodios); ?> Episodios</h2>

                <?php if (empty($episodios)): ?>
                    <div class="bg-gray-50 rounded-xl p-8 text-center">
                        <p class="text-gray-500"><?php echo esc_html__('Aun no hay episodios publicados', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($episodios as $indice => $episodio): ?>
                            <article class="bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition-shadow">
                                <div class="flex items-center gap-4">
                                    <button class="w-12 h-12 rounded-full bg-teal-500 text-white flex items-center justify-center flex-shrink-0 hover:bg-teal-600 transition-colors">
                                        <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-xs text-teal-600 font-semibold">EP. <?php echo count($episodios) - $indice; ?></span>
                                        <h3 class="font-bold text-gray-900 truncate"><?php echo esc_html($episodio['titulo'] ?? 'Episodio'); ?></h3>
                                        <div class="flex items-center gap-3 text-sm text-gray-500">
                                            <span><?php echo esc_html($episodio['fecha'] ?? 'Hace 1 dia'); ?></span>
                                            <span><?php echo esc_html($episodio['duracion'] ?? '45 min'); ?></span>
                                        </div>
                                    </div>
                                    <button class="p-2 text-gray-400 hover:text-teal-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
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
                <!-- Sobre el podcast -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h3 class="font-bold text-gray-900 mb-4"><?php echo esc_html__('Sobre el podcast', 'flavor-chat-ia'); ?></h3>
                    <p class="text-gray-700 text-sm"><?php echo esc_html($descripcion); ?></p>
                </div>

                <!-- Creador -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h3 class="font-bold text-gray-900 mb-4"><?php echo esc_html__('Creador', 'flavor-chat-ia'); ?></h3>
                    <div class="flex items-center gap-4">
                        <img src="<?php echo esc_url($autor['avatar'] ?? 'https://i.pravatar.cc/150?img=1'); ?>"
                             alt="<?php echo esc_attr($autor['nombre'] ?? 'Creador'); ?>"
                             class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <p class="font-bold text-gray-900"><?php echo esc_html($autor['nombre'] ?? 'Nombre'); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html($autor['podcasts'] ?? 1); ?> podcasts</p>
                        </div>
                    </div>
                </div>

                <!-- Compartir -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h3 class="font-bold text-gray-900 mb-4"><?php echo esc_html__('Compartir', 'flavor-chat-ia'); ?></h3>
                    <div class="flex items-center gap-3">
                        <button class="w-10 h-10 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center hover:bg-teal-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                        </button>
                        <button class="w-10 h-10 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center hover:bg-teal-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
