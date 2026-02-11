<?php
/**
 * Frontend: Archive de Espacios Comunes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$espacios = $espacios ?? [];
$total_espacios = $total_espacios ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
?>

<div class="flavor-archive espacios-comunes">
    <!-- Header -->
    <div class="bg-gradient-to-r from-rose-500 to-pink-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?php echo esc_html__('Espacios Comunes', 'flavor-chat-ia'); ?></h1>
            <p class="text-white/90 text-lg"><?php echo esc_html__('Reserva espacios para tus actividades y eventos', 'flavor-chat-ia'); ?></p>
            <div class="mt-4 flex items-center gap-4 text-white/80 text-sm">
                <span><?php echo esc_html($total_espacios); ?> espacios disponibles</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar de filtros -->
            <aside class="lg:w-72 flex-shrink-0">
                <?php include __DIR__ . '/filters.php'; ?>
            </aside>

            <!-- Lista de espacios -->
            <main class="flex-1">
                <!-- Ordenacion -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-600">
                        <?php echo esc_html__('Mostrando', 'flavor-chat-ia'); ?> <span class="font-semibold"><?php echo count($espacios); ?></span> de <?php echo esc_html($total_espacios); ?> espacios
                    </p>
                    <select class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                        <option><?php echo esc_html__('Mas relevantes', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Precio: menor a mayor', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Precio: mayor a menor', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Capacidad: mayor a menor', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Mas reservados', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <?php if (empty($espacios)): ?>
                    <div class="bg-gray-50 rounded-2xl p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay espacios disponibles', 'flavor-chat-ia'); ?></h3>
                        <p class="text-gray-500"><?php echo esc_html__('Prueba a modificar los filtros de busqueda', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <!-- Grid de espacios -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($espacios as $espacio): ?>
                            <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                                <div class="relative aspect-[16/10] overflow-hidden">
                                    <img src="<?php echo esc_url($espacio['imagen'] ?? 'https://picsum.photos/seed/esp' . rand(1,100) . '/600/400'); ?>"
                                         alt="<?php echo esc_attr($espacio['nombre']); ?>"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>

                                    <!-- Badge disponibilidad -->
                                    <?php if (!empty($espacio['disponible'])): ?>
                                        <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white"><?php echo esc_html__('Disponible', 'flavor-chat-ia'); ?></span>
                                    <?php else: ?>
                                        <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-red-500 text-white"><?php echo esc_html__('Ocupado', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>

                                    <!-- Capacidad -->
                                    <div class="absolute bottom-3 left-3 flex items-center gap-2 text-white text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <span><?php echo esc_html($espacio['capacidad'] ?? '?'); ?> personas</span>
                                    </div>
                                </div>

                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <h2 class="text-lg font-bold text-gray-900 group-hover:text-rose-600 transition-colors">
                                            <a href="<?php echo esc_url($espacio['url'] ?? '#'); ?>">
                                                <?php echo esc_html($espacio['nombre']); ?>
                                            </a>
                                        </h2>
                                        <span class="text-lg font-bold text-rose-600 whitespace-nowrap">
                                            <?php echo esc_html($espacio['precio'] ?? '0€'); ?>/h
                                        </span>
                                    </div>

                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                        <?php echo esc_html($espacio['descripcion'] ?? ''); ?>
                                    </p>

                                    <!-- Equipamiento -->
                                    <?php if (!empty($espacio['equipamiento'])): ?>
                                        <div class="flex flex-wrap gap-1 mb-4">
                                            <?php foreach (array_slice($espacio['equipamiento'], 0, 3) as $equipo): ?>
                                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-700">
                                                    <?php echo esc_html($equipo); ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($espacio['equipamiento']) > 3): ?>
                                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                    +<?php echo count($espacio['equipamiento']) - 3; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <a href="<?php echo esc_url($espacio['url'] ?? '#'); ?>"
                                       class="block w-full py-2.5 rounded-xl text-center font-semibold text-white transition-all hover:scale-105"
                                       style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);">
                                        <?php echo esc_html__('Ver Disponibilidad', 'flavor-chat-ia'); ?>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginacion -->
                    <?php if ($total_espacios > $por_pagina): ?>
                        <nav class="flex items-center justify-center gap-2 mt-8">
                            <?php
                            $total_paginas = ceil($total_espacios / $por_pagina);
                            for ($i = 1; $i <= $total_paginas; $i++):
                            ?>
                                <a href="?pagina=<?php echo $i; ?>"
                                   class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $i === $pagina_actual ? 'bg-rose-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-rose-100'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>
