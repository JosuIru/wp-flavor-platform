<?php
/**
 * Frontend: Busqueda de Huertos Urbanos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search huertos-urbanos">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center"><?php echo esc_html__('Buscar Huertos', 'flavor-chat-ia'); ?></h1>

            <!-- Formulario de busqueda -->
            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Ubicacion -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Ubicacion', 'flavor-chat-ia'); ?></label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            <input type="text"
                                   name="q"
                                   value="<?php echo esc_attr($query); ?>"
                                   placeholder="<?php echo esc_attr__('Tu direccion o barrio...', 'flavor-chat-ia'); ?>"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>

                    <!-- Distancia -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Radio', 'flavor-chat-ia'); ?></label>
                        <select name="distancia" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="2000"><?php echo esc_html__('2 km', 'flavor-chat-ia'); ?></option>
                            <option value="5000"><?php echo esc_html__('5 km', 'flavor-chat-ia'); ?></option>
                            <option value="10000"><?php echo esc_html__('10 km', 'flavor-chat-ia'); ?></option>
                            <option value=""><?php echo esc_html__('Sin limite', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Filtros rapidos -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <label class="flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 cursor-pointer hover:bg-green-100 transition-colors">
                        <input type="checkbox" name="solo_disponibles" value="1" class="w-4 h-4 rounded text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700"><?php echo esc_html__('Con parcelas libres', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 cursor-pointer hover:bg-green-100 transition-colors">
                        <input type="checkbox" name="con_agua" value="1" class="w-4 h-4 rounded text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700"><?php echo esc_html__('Con agua de riego', 'flavor-chat-ia'); ?></span>
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 cursor-pointer hover:bg-green-100 transition-colors">
                        <input type="checkbox" name="con_herramientas" value="1" class="w-4 h-4 rounded text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700"><?php echo esc_html__('Con herramientas', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <?php echo esc_html__('Buscar Huertos', 'flavor-chat-ia'); ?>
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
                    <?php echo esc_html($total_resultados); ?> huertos cerca de "<?php echo esc_html($query); ?>"
                </h2>
            </div>
        <?php endif; ?>

        <?php if (empty($resultados) && !empty($query)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos huertos cercanos', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500 mb-4"><?php echo esc_html__('Prueba a ampliar el radio de busqueda', 'flavor-chat-ia'); ?></p>
                <a href="?" class="text-green-600 font-medium hover:text-green-700"><?php echo esc_html__('Ver todos los huertos', 'flavor-chat-ia'); ?></a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($resultados as $huerto): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        <div class="relative aspect-[16/10] overflow-hidden">
                            <img src="<?php echo esc_url($huerto['imagen'] ?? 'https://picsum.photos/seed/huerto' . rand(1,100) . '/400/250'); ?>"
                                 alt="<?php echo esc_attr($huerto['nombre']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <?php if (!empty($huerto['parcelas_libres'])): ?>
                                <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white">
                                    <?php echo esc_html($huerto['parcelas_libres']); ?> libres
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-gray-900 group-hover:text-green-600 transition-colors mb-2">
                                <a href="<?php echo esc_url($huerto['url'] ?? '#'); ?>">
                                    <?php echo esc_html($huerto['nombre']); ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500 mb-3">
                                <?php echo esc_html($huerto['ubicacion'] ?? ''); ?> · <?php echo esc_html($huerto['distancia'] ?? ''); ?>
                            </p>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-green-600"><?php echo esc_html($huerto['precio'] ?? '20€'); ?>/mes</span>
                                <a href="<?php echo esc_url($huerto['url'] ?? '#'); ?>" class="text-green-600 font-medium text-sm hover:text-green-700">
                                    <?php echo esc_html__('Ver detalles →', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Huertos destacados -->
            <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Huertos Destacados', 'flavor-chat-ia'); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        <div class="relative aspect-[16/10] overflow-hidden">
                            <img src="https://picsum.photos/seed/huerto<?php echo $i; ?>/400/250"
                                 alt="<?php echo esc_attr__('Huerto destacado', 'flavor-chat-ia'); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white">
                                <?php echo rand(1, 8); ?> libres
                            </span>
                        </div>
                        <div class="p-5">
                            <h3 class="font-bold text-gray-900 group-hover:text-green-600 transition-colors mb-2">
                                Huerto Ejemplo <?php echo $i; ?>
                            </h3>
                            <p class="text-sm text-gray-500 mb-3">
                                <?php echo esc_html__('Zona ejemplo · 1.5 km', 'flavor-chat-ia'); ?>
                            </p>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-green-600"><?php echo esc_html__('25€/mes', 'flavor-chat-ia'); ?></span>
                                <a href="#" class="text-green-600 font-medium text-sm hover:text-green-700">
                                    <?php echo esc_html__('Ver detalles →', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
