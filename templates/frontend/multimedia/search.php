<?php
/**
 * Frontend: Busqueda de Multimedia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$consulta_busqueda = $consulta_busqueda ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search multimedia">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-700 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center"><?php echo esc_html__('Buscar multimedia', 'flavor-chat-ia'); ?></h1>

            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           name="q"
                           value="<?php echo esc_attr($consulta_busqueda); ?>"
                           placeholder="<?php echo esc_attr__('Buscar videos, fotos, podcasts...', 'flavor-chat-ia'); ?>"
                           class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 text-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Sugerencias -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-500"><?php echo esc_html__('Sugerencias:', 'flavor-chat-ia'); ?></span>
                    <a href="?q=documental" class="px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"><?php echo esc_html__('documental', 'flavor-chat-ia'); ?></a>
                    <a href="?q=musica" class="px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"><?php echo esc_html__('musica', 'flavor-chat-ia'); ?></a>
                    <a href="?q=fotos+barrio" class="px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"><?php echo esc_html__('fotos barrio', 'flavor-chat-ia'); ?></a>
                    <a href="?q=reportaje" class="px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"><?php echo esc_html__('reportaje', 'flavor-chat-ia'); ?></a>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);">
                        <?php echo esc_html__('Buscar Multimedia', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <?php if (!empty($consulta_busqueda)): ?>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                    <?php echo esc_html($total_resultados); ?> resultados para "<?php echo esc_html($consulta_busqueda); ?>"
                </h2>
            </div>
        <?php endif; ?>

        <?php if (empty($resultados) && !empty($consulta_busqueda)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No se encontro contenido', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500 mb-4"><?php echo esc_html__('Prueba con otros terminos de busqueda', 'flavor-chat-ia'); ?></p>
                <a href="?" class="text-indigo-600 font-medium hover:text-indigo-700"><?php echo esc_html__('Ver todo el multimedia', 'flavor-chat-ia'); ?></a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($resultados as $resultado): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        <div class="relative aspect-video">
                            <?php $tipo_resultado = $resultado['tipo'] ?? 'video'; ?>
                            <div class="w-full h-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center">
                                <?php if ($tipo_resultado === 'video'): ?>
                                    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                <?php else: ?>
                                    <svg class="w-10 h-10 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <span class="absolute top-3 left-3 px-2 py-0.5 rounded-full text-xs font-bold bg-white/90 text-indigo-700">
                                <?php echo esc_html(ucfirst($tipo_resultado)); ?>
                            </span>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-1">
                                <a href="<?php echo esc_url($resultado['url'] ?? '#'); ?>">
                                    <?php echo esc_html($resultado['titulo'] ?? 'Multimedia'); ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500"><?php echo esc_html($resultado['vistas'] ?? 0); ?> vistas - <?php echo esc_html($resultado['fecha'] ?? ''); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Contenido destacado -->
            <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Contenido destacado', 'flavor-chat-ia'); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $tipos_ejemplo = ['video', 'foto', 'audio', 'video', 'foto', 'video'];
                for ($indice_medio = 0; $indice_medio < 6; $indice_medio++):
                    $tipo_ejemplo = $tipos_ejemplo[$indice_medio];
                ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        <div class="relative aspect-video">
                            <div class="w-full h-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center">
                                <?php if ($tipo_ejemplo === 'video'): ?>
                                    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                <?php else: ?>
                                    <svg class="w-10 h-10 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <span class="absolute top-3 left-3 px-2 py-0.5 rounded-full text-xs font-bold bg-white/90 text-indigo-700">
                                <?php echo esc_html(ucfirst($tipo_ejemplo)); ?>
                            </span>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-1">
                                Contenido destacado <?php echo ($indice_medio + 1); ?>
                            </h3>
                            <p class="text-sm text-gray-500"><?php echo (($indice_medio + 1) * 48); ?> vistas</p>
                        </div>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
