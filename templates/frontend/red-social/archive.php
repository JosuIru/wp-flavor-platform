<?php
/**
 * Frontend: Archive de Red Social
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$publicaciones = $publicaciones ?? [];
$total_publicaciones = $total_publicaciones ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
?>

<div class="flavor-archive red-social">
    <!-- Header con gradiente -->
    <div class="bg-gradient-to-r from-pink-500 to-rose-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Red Social</h1>
            <p class="text-white/90 text-lg">Conecta con tu comunidad y comparte experiencias</p>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <!-- Estadisticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-pink-600">1.2k</span>
                <span class="text-sm text-gray-500">Miembros</span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-rose-600">3.4k</span>
                <span class="text-sm text-gray-500">Publicaciones</span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-pink-600">56</span>
                <span class="text-sm text-gray-500">Grupos</span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-rose-600">892</span>
                <span class="text-sm text-gray-500">Conexiones</span>
            </div>
        </div>

        <!-- CTA Crear Publicacion -->
        <div class="flex items-center justify-between mb-6">
            <p class="text-gray-600">
                Mostrando <span class="font-semibold"><?php echo count($publicaciones); ?></span> de <?php echo esc_html($total_publicaciones); ?> publicaciones
            </p>
            <button class="px-6 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                    style="background: linear-gradient(135deg, #ec4899 0%, #e11d48 100%);">
                + Crear Publicacion
            </button>
        </div>

        <?php if (empty($publicaciones)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay publicaciones todavia</h3>
                <p class="text-gray-500">Se el primero en compartir algo con la comunidad</p>
            </div>
        <?php else: ?>
            <!-- Feed de publicaciones -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($publicaciones as $publicacion): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                        <!-- Cabecera del post -->
                        <div class="p-5">
                            <div class="flex items-center gap-3 mb-4">
                                <img src="<?php echo esc_url($publicacion['autor_avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1,70)); ?>"
                                     alt="<?php echo esc_attr($publicacion['autor'] ?? 'Usuario'); ?>"
                                     class="w-10 h-10 rounded-full object-cover">
                                <div>
                                    <p class="font-bold text-gray-900 text-sm"><?php echo esc_html($publicacion['autor'] ?? 'Usuario'); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo esc_html($publicacion['tiempo'] ?? 'hace 2h'); ?></p>
                                </div>
                            </div>

                            <!-- Contenido -->
                            <p class="text-gray-700 text-sm line-clamp-3 mb-3">
                                <?php echo esc_html($publicacion['contenido'] ?? ''); ?>
                            </p>
                        </div>

                        <!-- Imagen placeholder -->
                        <?php if (!empty($publicacion['tiene_imagen'])): ?>
                            <div class="aspect-video bg-gradient-to-br from-pink-100 to-rose-100 flex items-center justify-center">
                                <svg class="w-12 h-12 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <!-- Interacciones -->
                        <div class="px-5 py-3 border-t border-gray-100">
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <button class="flex items-center gap-1 hover:text-pink-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    <?php echo esc_html($publicacion['likes'] ?? 0); ?>
                                </button>
                                <button class="flex items-center gap-1 hover:text-pink-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <?php echo esc_html($publicacion['comentarios'] ?? 0); ?>
                                </button>
                                <button class="flex items-center gap-1 hover:text-pink-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                    </svg>
                                    <?php echo esc_html($publicacion['compartidos'] ?? 0); ?>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginacion -->
            <?php if ($total_publicaciones > $por_pagina): ?>
                <nav class="flex items-center justify-center gap-2 mt-8">
                    <?php
                    $total_paginas = ceil($total_publicaciones / $por_pagina);
                    for ($contador_pagina = 1; $contador_pagina <= $total_paginas; $contador_pagina++):
                    ?>
                        <a href="?pagina=<?php echo $contador_pagina; ?>"
                           class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $contador_pagina === $pagina_actual ? 'bg-pink-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-pink-100'; ?>">
                            <?php echo $contador_pagina; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
