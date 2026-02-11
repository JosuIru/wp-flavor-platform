<?php
/**
 * Frontend: Archive de Multimedia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$elementos_multimedia = $elementos_multimedia ?? [];
$total_elementos = $total_elementos ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
$vista_actual = $vista_actual ?? 'grid';
?>

<div class="flavor-archive multimedia">
    <!-- Header con gradiente -->
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-700 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?php echo esc_html__('Multimedia', 'flavor-chat-ia'); ?></h1>
            <p class="text-white/90 text-lg"><?php echo esc_html__('Videos, fotos, podcasts y mas contenido de la comunidad', 'flavor-chat-ia'); ?></p>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <!-- Estadisticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-indigo-600">284</span>
                <span class="text-sm text-gray-500"><?php echo esc_html__('Videos', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-indigo-700"><?php echo esc_html__('1.2k', 'flavor-chat-ia'); ?></span>
                <span class="text-sm text-gray-500"><?php echo esc_html__('Fotos', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-indigo-600">45</span>
                <span class="text-sm text-gray-500"><?php echo esc_html__('Podcasts', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-indigo-700"><?php echo esc_html__('52k', 'flavor-chat-ia'); ?></span>
                <span class="text-sm text-gray-500"><?php echo esc_html__('Visualizaciones', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <!-- Barra de herramientas -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <p class="text-gray-600">
                    <?php echo esc_html__('Mostrando', 'flavor-chat-ia'); ?> <span class="font-semibold"><?php echo count($elementos_multimedia); ?></span> de <?php echo esc_html($total_elementos); ?>
                </p>
                <!-- Toggle vista -->
                <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                    <button class="p-2 rounded-md <?php echo $vista_actual === 'grid' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-indigo-600'; ?> transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </button>
                    <button class="p-2 rounded-md <?php echo $vista_actual === 'list' ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-indigo-600'; ?> transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
            <button class="px-6 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                    style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
                <?php echo esc_html__('+ Subir Contenido', 'flavor-chat-ia'); ?>
            </button>
        </div>

        <?php if (empty($elementos_multimedia)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay contenido multimedia', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500"><?php echo esc_html__('Sube tu primer contenido para la comunidad', 'flavor-chat-ia'); ?></p>
            </div>
        <?php else: ?>
            <!-- Grid de multimedia -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($elementos_multimedia as $elemento): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                        <!-- Thumbnail -->
                        <div class="relative aspect-video overflow-hidden">
                            <?php
                            $tipo_medio = $elemento['tipo'] ?? 'video';
                            $gradiente_thumbnail = $tipo_medio === 'video' ? 'from-indigo-400 to-indigo-600' : ($tipo_medio === 'foto' ? 'from-purple-400 to-indigo-500' : 'from-indigo-500 to-indigo-700');
                            ?>
                            <div class="w-full h-full bg-gradient-to-br <?php echo esc_attr($gradiente_thumbnail); ?> flex items-center justify-center">
                                <?php if ($tipo_medio === 'video'): ?>
                                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm group-hover:scale-110 transition-transform">
                                        <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                <?php elseif ($tipo_medio === 'foto'): ?>
                                    <svg class="w-12 h-12 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-12 h-12 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                    </svg>
                                <?php endif; ?>
                            </div>

                            <!-- Badge de tipo -->
                            <span class="absolute top-3 left-3 px-2 py-1 rounded-full text-xs font-bold bg-white/90 text-indigo-700">
                                <?php echo esc_html(ucfirst($tipo_medio)); ?>
                            </span>

                            <!-- Duracion -->
                            <?php if ($tipo_medio === 'video' || $tipo_medio === 'audio'): ?>
                                <span class="absolute bottom-3 right-3 px-2 py-1 rounded text-xs font-bold bg-black/70 text-white">
                                    <?php echo esc_html($elemento['duracion'] ?? '12:34'); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="p-5">
                            <h2 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-2">
                                <a href="<?php echo esc_url($elemento['url'] ?? '#'); ?>">
                                    <?php echo esc_html($elemento['titulo'] ?? 'Contenido multimedia'); ?>
                                </a>
                            </h2>

                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <?php echo esc_html($elemento['autor'] ?? 'Autor'); ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <?php echo esc_html($elemento['vistas'] ?? 0); ?>
                                </span>
                                <span><?php echo esc_html($elemento['fecha'] ?? 'hace 3 dias'); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginacion -->
            <?php if ($total_elementos > $por_pagina): ?>
                <nav class="flex items-center justify-center gap-2 mt-8">
                    <?php
                    $total_paginas = ceil($total_elementos / $por_pagina);
                    for ($contador_pagina = 1; $contador_pagina <= $total_paginas; $contador_pagina++):
                    ?>
                        <a href="?pagina=<?php echo $contador_pagina; ?>"
                           class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $contador_pagina === $pagina_actual ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-indigo-100'; ?>">
                            <?php echo $contador_pagina; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
