<?php
/**
 * Frontend: Busqueda de Espacios Comunes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search espacios-comunes">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-rose-500 to-pink-600 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center"><?php echo esc_html__('Buscar Espacios', 'flavor-chat-ia'); ?></h1>

            <!-- Formulario de busqueda -->
            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Que buscas -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Que buscas?', 'flavor-chat-ia'); ?></label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
                                   name="q"
                                   value="<?php echo esc_attr($query); ?>"
                                   placeholder="<?php echo esc_attr__('Salon de actos, sala de reuniones...', 'flavor-chat-ia'); ?>"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                        </div>
                    </div>

                    <!-- Fecha -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></label>
                        <input type="date"
                               name="fecha"
                               min="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    </div>

                    <!-- Capacidad -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Personas', 'flavor-chat-ia'); ?></label>
                        <select name="capacidad" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                            <option value=""><?php echo esc_html__('Cualquiera', 'flavor-chat-ia'); ?></option>
                            <option value="1-10"><?php echo esc_html__('1-10 personas', 'flavor-chat-ia'); ?></option>
                            <option value="11-25"><?php echo esc_html__('11-25 personas', 'flavor-chat-ia'); ?></option>
                            <option value="26-50"><?php echo esc_html__('26-50 personas', 'flavor-chat-ia'); ?></option>
                            <option value="51-100"><?php echo esc_html__('51-100 personas', 'flavor-chat-ia'); ?></option>
                            <option value="100+"><?php echo esc_html__('Mas de 100', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Filtros avanzados -->
                <details class="mt-4">
                    <summary class="text-sm text-rose-600 font-medium cursor-pointer hover:text-rose-700">
                        <?php echo esc_html__('Filtros avanzados', 'flavor-chat-ia'); ?>
                    </summary>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-100">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Tipo de espacio', 'flavor-chat-ia'); ?></label>
                            <select name="tipo" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500">
                                <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('salon', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Salon de actos', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('sala', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Sala de reuniones', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('aula', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Aula de formacion', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('cocina', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Cocina', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('exterior', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Espacio exterior', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Precio maximo', 'flavor-chat-ia'); ?></label>
                            <select name="precio_max" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500">
                                <option value=""><?php echo esc_html__('Sin limite', 'flavor-chat-ia'); ?></option>
                                <option value="20"><?php echo esc_html__('Hasta 20€/h', 'flavor-chat-ia'); ?></option>
                                <option value="50"><?php echo esc_html__('Hasta 50€/h', 'flavor-chat-ia'); ?></option>
                                <option value="100"><?php echo esc_html__('Hasta 100€/h', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Hora inicio', 'flavor-chat-ia'); ?></label>
                            <select name="hora_inicio" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500">
                                <option value=""><?php echo esc_html__('Cualquiera', 'flavor-chat-ia'); ?></option>
                                <?php for ($h = 8; $h <= 20; $h++): ?>
                                    <option value="<?php echo $h; ?>"><?php echo sprintf('%02d:00', $h); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Equipamiento', 'flavor-chat-ia'); ?></label>
                            <select name="equipamiento" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500">
                                <option value=""><?php echo esc_html__('Cualquiera', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('proyector', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Proyector', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('wifi', 'flavor-chat-ia'); ?>"><?php echo esc_html__('WiFi', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('tv', 'flavor-chat-ia'); ?>"><?php echo esc_html__('TV/Pantalla', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('cocina', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Cocina equipada', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                    </div>
                </details>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <?php echo esc_html__('Buscar Espacios', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <?php if (!empty($query)): ?>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                    <?php echo esc_html($total_resultados); ?> resultados para "<?php echo esc_html($query); ?>"
                </h2>
            </div>
        <?php endif; ?>

        <?php if (empty($resultados) && !empty($query)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos resultados', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500 mb-4"><?php echo esc_html__('Prueba con otros terminos de busqueda o filtros diferentes', 'flavor-chat-ia'); ?></p>
                <a href="?" class="text-rose-600 font-medium hover:text-rose-700"><?php echo esc_html__('Ver todos los espacios', 'flavor-chat-ia'); ?></a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($resultados as $espacio): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                        <div class="relative aspect-[16/10] overflow-hidden">
                            <img src="<?php echo esc_url($espacio['imagen'] ?? 'https://picsum.photos/seed/esp' . rand(1,100) . '/400/250'); ?>"
                                 alt="<?php echo esc_attr($espacio['nombre']); ?>"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-gray-900 group-hover:text-rose-600 transition-colors mb-2">
                                <?php echo esc_html($espacio['nombre']); ?>
                            </h3>
                            <p class="text-sm text-gray-500 mb-3">
                                <?php echo esc_html($espacio['capacidad'] ?? '?'); ?> personas · <?php echo esc_html($espacio['precio'] ?? '0€'); ?>/hora
                            </p>
                            <a href="<?php echo esc_url($espacio['url'] ?? '#'); ?>"
                               class="text-rose-600 font-medium text-sm hover:text-rose-700">
                                <?php echo esc_html__('Ver detalles →', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Espacios destacados cuando no hay busqueda -->
            <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Espacios Destacados', 'flavor-chat-ia'); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                        <div class="relative aspect-[16/10] overflow-hidden">
                            <img src="https://picsum.photos/seed/destacado<?php echo $i; ?>/400/250"
                                 alt="<?php echo esc_attr__('Espacio destacado', 'flavor-chat-ia'); ?>"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-gray-900 group-hover:text-rose-600 transition-colors mb-2">
                                Espacio Ejemplo <?php echo $i; ?>
                            </h3>
                            <p class="text-sm text-gray-500 mb-3">
                                <?php echo esc_html__('25 personas · 30€/hora', 'flavor-chat-ia'); ?>
                            </p>
                            <a href="#" class="text-rose-600 font-medium text-sm hover:text-rose-700">
                                <?php echo esc_html__('Ver detalles →', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
