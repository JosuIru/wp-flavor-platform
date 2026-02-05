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
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center">Buscar Espacios</h1>

            <!-- Formulario de busqueda -->
            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Que buscas -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Que buscas?</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
                                   name="q"
                                   value="<?php echo esc_attr($query); ?>"
                                   placeholder="Salon de actos, sala de reuniones..."
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                        </div>
                    </div>

                    <!-- Fecha -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Fecha</label>
                        <input type="date"
                               name="fecha"
                               min="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    </div>

                    <!-- Capacidad -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Personas</label>
                        <select name="capacidad" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                            <option value="">Cualquiera</option>
                            <option value="1-10">1-10 personas</option>
                            <option value="11-25">11-25 personas</option>
                            <option value="26-50">26-50 personas</option>
                            <option value="51-100">51-100 personas</option>
                            <option value="100+">Mas de 100</option>
                        </select>
                    </div>
                </div>

                <!-- Filtros avanzados -->
                <details class="mt-4">
                    <summary class="text-sm text-rose-600 font-medium cursor-pointer hover:text-rose-700">
                        Filtros avanzados
                    </summary>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-100">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Tipo de espacio</label>
                            <select name="tipo" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500">
                                <option value="">Todos</option>
                                <option value="salon">Salon de actos</option>
                                <option value="sala">Sala de reuniones</option>
                                <option value="aula">Aula de formacion</option>
                                <option value="cocina">Cocina</option>
                                <option value="exterior">Espacio exterior</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Precio maximo</label>
                            <select name="precio_max" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500">
                                <option value="">Sin limite</option>
                                <option value="20">Hasta 20€/h</option>
                                <option value="50">Hasta 50€/h</option>
                                <option value="100">Hasta 100€/h</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Hora inicio</label>
                            <select name="hora_inicio" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500">
                                <option value="">Cualquiera</option>
                                <?php for ($h = 8; $h <= 20; $h++): ?>
                                    <option value="<?php echo $h; ?>"><?php echo sprintf('%02d:00', $h); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Equipamiento</label>
                            <select name="equipamiento" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-rose-500">
                                <option value="">Cualquiera</option>
                                <option value="proyector">Proyector</option>
                                <option value="wifi">WiFi</option>
                                <option value="tv">TV/Pantalla</option>
                                <option value="cocina">Cocina equipada</option>
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
                        Buscar Espacios
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
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos resultados</h3>
                <p class="text-gray-500 mb-4">Prueba con otros terminos de busqueda o filtros diferentes</p>
                <a href="?" class="text-rose-600 font-medium hover:text-rose-700">Ver todos los espacios</a>
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
                                Ver detalles →
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Espacios destacados cuando no hay busqueda -->
            <h2 class="text-xl font-bold text-gray-900 mb-6">Espacios Destacados</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                        <div class="relative aspect-[16/10] overflow-hidden">
                            <img src="https://picsum.photos/seed/destacado<?php echo $i; ?>/400/250"
                                 alt="Espacio destacado"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-gray-900 group-hover:text-rose-600 transition-colors mb-2">
                                Espacio Ejemplo <?php echo $i; ?>
                            </h3>
                            <p class="text-sm text-gray-500 mb-3">
                                25 personas · 30€/hora
                            </p>
                            <a href="#" class="text-rose-600 font-medium text-sm hover:text-rose-700">
                                Ver detalles →
                            </a>
                        </div>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
