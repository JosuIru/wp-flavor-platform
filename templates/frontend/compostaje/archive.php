<?php
/**
 * Frontend: Archive de Compostaje Comunitario
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$composteras = $composteras ?? [];
$total_composteras = $total_composteras ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
?>

<div class="flavor-archive compostaje">
    <!-- Header con gradiente -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?php echo esc_html__('Compostaje Comunitario', 'flavor-chat-ia'); ?></h1>
            <p class="text-white/90 text-lg"><?php echo esc_html__('Transforma residuos organicos en recursos para la comunidad', 'flavor-chat-ia'); ?></p>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <!-- Estadisticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-green-600">12</span>
                <span class="text-sm text-gray-500"><?php echo esc_html__('Composteras activas', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-emerald-600"><?php echo esc_html__('3.2t', 'flavor-chat-ia'); ?></span>
                <span class="text-sm text-gray-500"><?php echo esc_html__('Kg compostados', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-green-600">186</span>
                <span class="text-sm text-gray-500"><?php echo esc_html__('Participantes', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-md text-center">
                <span class="block text-3xl font-bold text-emerald-600"><?php echo esc_html__('1.8t', 'flavor-chat-ia'); ?></span>
                <span class="text-sm text-gray-500"><?php echo esc_html__('CO2 evitado', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <!-- Como funciona -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-4 text-center"><?php echo esc_html__('Como funciona', 'flavor-chat-ia'); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html__('1. Separa organico', 'flavor-chat-ia'); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo esc_html__('Separa tus residuos organicos en casa', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="text-center">
                    <div class="w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html__('2. Llevalo a compostera', 'flavor-chat-ia'); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo esc_html__('Deposita en la compostera mas cercana', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="text-center">
                    <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html__('3. Recoge compost', 'flavor-chat-ia'); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo esc_html__('Utiliza el compost en tu jardin o huerto', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>

        <!-- Barra de herramientas -->
        <div class="flex items-center justify-between mb-6">
            <p class="text-gray-600">
                <?php echo esc_html__('Mostrando', 'flavor-chat-ia'); ?> <span class="font-semibold"><?php echo count($composteras); ?></span> de <?php echo esc_html($total_composteras); ?> composteras
            </p>
        </div>

        <?php if (empty($composteras)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay composteras registradas', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500"><?php echo esc_html__('Pronto se anunciaran nuevas composteras comunitarias', 'flavor-chat-ia'); ?></p>
            </div>
        <?php else: ?>
            <!-- Grid de composteras -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($composteras as $compostera): ?>
                    <?php
                    $estado_compostera = $compostera['estado'] ?? 'activa';
                    $colores_estado = [
                        'activa'         => 'bg-green-100 text-green-700',
                        'llena'          => 'bg-amber-100 text-amber-700',
                        'mantenimiento'  => 'bg-red-100 text-red-700',
                    ];
                    $clase_estado = $colores_estado[$estado_compostera] ?? $colores_estado['activa'];
                    $capacidad_compostera = $compostera['capacidad'] ?? 65;
                    ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                        <div class="p-6">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-bold text-gray-900 group-hover:text-green-600 transition-colors">
                                    <a href="<?php echo esc_url($compostera['url'] ?? '#'); ?>">
                                        <?php echo esc_html($compostera['nombre'] ?? 'Compostera'); ?>
                                    </a>
                                </h2>
                                <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo esc_attr($clase_estado); ?>">
                                    <?php echo esc_html(ucfirst($estado_compostera)); ?>
                                </span>
                            </div>

                            <!-- Ubicacion -->
                            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span><?php echo esc_html($compostera['ubicacion'] ?? 'Barrio centro'); ?></span>
                            </div>

                            <!-- Barra de capacidad -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="text-gray-600"><?php echo esc_html__('Capacidad', 'flavor-chat-ia'); ?></span>
                                    <span class="font-bold <?php echo $capacidad_compostera > 80 ? 'text-amber-600' : 'text-green-600'; ?>">
                                        <?php echo esc_html($capacidad_compostera); ?>%
                                    </span>
                                </div>
                                <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all <?php echo $capacidad_compostera > 80 ? 'bg-amber-500' : 'bg-green-500'; ?>"
                                         style="width: <?php echo esc_attr($capacidad_compostera); ?>%"></div>
                                </div>
                            </div>

                            <!-- Info adicional -->
                            <div class="space-y-2 text-sm text-gray-500">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Proxima recogida: <?php echo esc_html($compostera['proxima_recogida'] ?? '15 Feb'); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span><?php echo esc_html($compostera['usuarios'] ?? 0); ?> usuarios</span>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <a href="<?php echo esc_url($compostera['url'] ?? '#'); ?>"
                                   class="block w-full py-2 rounded-xl text-center text-green-600 font-semibold text-sm bg-green-50 hover:bg-green-100 transition-colors">
                                    <?php echo esc_html__('Ver compostera', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginacion -->
            <?php if ($total_composteras > $por_pagina): ?>
                <nav class="flex items-center justify-center gap-2 mt-8">
                    <?php
                    $total_paginas = ceil($total_composteras / $por_pagina);
                    for ($contador_pagina = 1; $contador_pagina <= $total_paginas; $contador_pagina++):
                    ?>
                        <a href="?pagina=<?php echo $contador_pagina; ?>"
                           class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $contador_pagina === $pagina_actual ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-green-100'; ?>">
                            <?php echo $contador_pagina; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
