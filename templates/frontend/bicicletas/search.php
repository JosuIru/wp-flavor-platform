<?php
/**
 * Frontend: Busqueda de Bicicletas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search bicicletas">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-lime-500 to-green-500 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center">Buscar Estaciones</h1>

            <!-- Formulario de busqueda -->
            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Busqueda -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Direccion o zona</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            <input type="text"
                                   name="q"
                                   value="<?php echo esc_attr($query); ?>"
                                   placeholder="Buscar cerca de..."
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                        </div>
                    </div>

                    <!-- Distancia -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Radio</label>
                        <select name="distancia" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-lime-500 focus:border-lime-500">
                            <option value="500">500m</option>
                            <option value="1000">1 km</option>
                            <option value="2000">2 km</option>
                        </select>
                    </div>
                </div>

                <!-- Filtros rapidos -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <label class="flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 cursor-pointer hover:bg-lime-100 transition-colors">
                        <input type="checkbox" name="con_bicis" value="1" class="w-4 h-4 rounded text-lime-600 focus:ring-lime-500">
                        <span class="text-sm text-gray-700">Con bicis disponibles</span>
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-full bg-gray-100 cursor-pointer hover:bg-lime-100 transition-colors">
                        <input type="checkbox" name="electricas" value="1" class="w-4 h-4 rounded text-lime-600 focus:ring-lime-500">
                        <span class="text-sm text-gray-700">Con bicis electricas</span>
                    </label>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%);">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Buscar
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
                    <?php echo esc_html($total_resultados); ?> estaciones cerca de "<?php echo esc_html($query); ?>"
                </h2>
            </div>
        <?php endif; ?>

        <?php if (empty($resultados) && !empty($query)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos estaciones</h3>
                <p class="text-gray-500 mb-4">Prueba a ampliar el radio de busqueda</p>
                <a href="?" class="text-lime-600 font-medium hover:text-lime-700">Ver todas las estaciones</a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="space-y-4">
                <?php foreach ($resultados as $estacion): ?>
                    <article class="bg-white rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow flex items-center gap-4">
                        <div class="w-16 h-16 rounded-xl bg-lime-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-2xl font-bold text-lime-600"><?php echo esc_html($estacion['bicis_disponibles'] ?? 0); ?></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-900"><?php echo esc_html($estacion['nombre']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo esc_html($estacion['direccion'] ?? ''); ?> · <?php echo esc_html($estacion['distancia'] ?? ''); ?></p>
                        </div>
                        <a href="<?php echo esc_url($estacion['url'] ?? '#'); ?>"
                           class="px-4 py-2 rounded-xl text-white font-semibold text-sm transition-all hover:scale-105 flex-shrink-0"
                           style="background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%);">
                            Reservar
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
