<?php
/**
 * Frontend: Archive de Ayuda Vecinal
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$solicitudes = $solicitudes ?? [];
$total_solicitudes = $total_solicitudes ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-archive ayuda-vecinal">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-500 to-amber-500 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?php echo esc_html__('Ayuda Vecinal', 'flavor-chat-ia'); ?></h1>
            <p class="text-white/90 text-lg"><?php echo esc_html__('Conecta con vecinos que necesitan o ofrecen ayuda', 'flavor-chat-ia'); ?></p>
            <div class="mt-4 flex items-center gap-4 text-white/80 text-sm">
                <span><?php echo esc_html($total_solicitudes); ?> solicitudes activas</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar de filtros -->
            <aside class="lg:w-72 flex-shrink-0">
                <?php include __DIR__ . '/filters.php'; ?>
            </aside>

            <!-- Lista de solicitudes -->
            <main class="flex-1">
                <!-- Tabs -->
                <div class="flex items-center gap-4 mb-6 border-b border-gray-200">
                    <button class="px-4 py-3 font-semibold text-orange-600 border-b-2 border-orange-500">
                        <?php echo esc_html__('Necesito ayuda', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="px-4 py-3 font-semibold text-gray-500 hover:text-orange-600">
                        <?php echo esc_html__('Ofrezco ayuda', 'flavor-chat-ia'); ?>
                    </button>
                </div>

                <!-- Ordenacion -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-600">
                        <?php echo esc_html__('Mostrando', 'flavor-chat-ia'); ?> <span class="font-semibold"><?php echo count($solicitudes); ?></span> de <?php echo esc_html($total_solicitudes); ?> solicitudes
                    </p>
                    <select class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option><?php echo esc_html__('Mas recientes', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Mas urgentes', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Mas cercanos', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Mas comentados', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <?php if (empty($solicitudes)): ?>
                    <div class="bg-gray-50 rounded-2xl p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay solicitudes activas', 'flavor-chat-ia'); ?></h3>
                        <p class="text-gray-500 mb-4"><?php echo esc_html__('Se el primero en pedir o ofrecer ayuda', 'flavor-chat-ia'); ?></p>
                        <button class="px-6 py-3 rounded-xl text-white font-semibold" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
                            <?php echo esc_html__('Crear solicitud', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Grid de solicitudes -->
                    <div class="space-y-4">
                        <?php foreach ($solicitudes as $solicitud): ?>
                            <article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100">
                                <div class="flex items-start gap-4">
                                    <img src="<?php echo esc_url($solicitud['avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1,70)); ?>"
                                         alt="<?php echo esc_attr($solicitud['autor'] ?? 'Usuario'); ?>"
                                         class="w-12 h-12 rounded-full object-cover">

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-bold text-gray-900"><?php echo esc_html($solicitud['autor'] ?? 'Anonimo'); ?></span>
                                            <?php if (!empty($solicitud['urgente'])): ?>
                                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700"><?php echo esc_html__('Urgente', 'flavor-chat-ia'); ?></span>
                                            <?php endif; ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
                                                <?php echo esc_html($solicitud['categoria'] ?? 'General'); ?>
                                            </span>
                                        </div>
                                        <span class="text-sm text-gray-500"><?php echo esc_html($solicitud['tiempo'] ?? 'Hace 1 hora'); ?></span>

                                        <h2 class="text-lg font-bold text-gray-900 mt-2 group-hover:text-orange-600 transition-colors">
                                            <a href="<?php echo esc_url($solicitud['url'] ?? '#'); ?>">
                                                <?php echo esc_html($solicitud['titulo'] ?? 'Sin titulo'); ?>
                                            </a>
                                        </h2>

                                        <p class="text-gray-600 mt-2 line-clamp-2">
                                            <?php echo esc_html($solicitud['descripcion'] ?? ''); ?>
                                        </p>

                                        <div class="flex items-center gap-6 mt-4">
                                            <span class="flex items-center gap-1 text-sm text-gray-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                </svg>
                                                <?php echo esc_html($solicitud['ubicacion'] ?? 'Sin ubicacion'); ?>
                                            </span>
                                            <span class="flex items-center gap-1 text-sm text-gray-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                                </svg>
                                                <?php echo esc_html($solicitud['respuestas'] ?? 0); ?> respuestas
                                            </span>
                                        </div>
                                    </div>

                                    <a href="<?php echo esc_url($solicitud['url'] ?? '#'); ?>"
                                       class="px-4 py-2 rounded-xl text-white font-semibold text-sm transition-all hover:scale-105 flex-shrink-0"
                                       style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
                                        <?php echo esc_html__('Responder', 'flavor-chat-ia'); ?>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginacion -->
                    <?php if ($total_solicitudes > $por_pagina): ?>
                        <nav class="flex items-center justify-center gap-2 mt-8">
                            <?php
                            $total_paginas = ceil($total_solicitudes / $por_pagina);
                            for ($i = 1; $i <= $total_paginas; $i++):
                            ?>
                                <a href="?pagina=<?php echo $i; ?>"
                                   class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $i === $pagina_actual ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-orange-100'; ?>">
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
