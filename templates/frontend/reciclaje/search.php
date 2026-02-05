<?php
/**
 * Frontend: Busqueda de Reciclaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search reciclaje">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-emerald-500 to-green-600 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center">Donde reciclar?</h1>

            <!-- Formulario de busqueda -->
            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Busqueda -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Que quieres reciclar?</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
                                   name="q"
                                   value="<?php echo esc_attr($query); ?>"
                                   placeholder="Ej: botellas de plastico, pilas, ropa vieja..."
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Contenedor</label>
                        <select name="contenedor" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">Todos</option>
                            <option value="amarillo">Amarillo (plasticos)</option>
                            <option value="azul">Azul (papel)</option>
                            <option value="verde">Verde (vidrio)</option>
                            <option value="marron">Marron (organico)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
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
    <div class="container mx-auto max-w-4xl px-4 py-8">
        <?php if (!empty($query)): ?>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                    Donde reciclar "<?php echo esc_html($query); ?>"
                </h2>
            </div>

            <!-- Respuesta directa -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 mb-8">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-yellow-400 flex-shrink-0"></div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-1">Contenedor Amarillo</h3>
                        <p class="text-gray-700">Las botellas de plastico van al contenedor amarillo. Recuerda vaciarlas y aplastarlas antes de tirarlas.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($resultados) && !empty($query)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos informacion</h3>
                <p class="text-gray-500 mb-4">Prueba con otros terminos o consulta la guia de reciclaje</p>
                <a href="?" class="text-emerald-600 font-medium hover:text-emerald-700">Ver guia completa</a>
            </div>
        <?php else: ?>
            <!-- Puntos cercanos -->
            <h3 class="text-lg font-bold text-gray-900 mb-4">Puntos de reciclaje cercanos</h3>
            <div class="space-y-4">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <article class="bg-white rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900">Punto de reciclaje <?php echo $i; ?></h4>
                            <p class="text-sm text-gray-500"><?php echo $i * 100; ?>m</p>
                        </div>
                        <div class="flex gap-1">
                            <span class="w-5 h-5 rounded bg-yellow-400"></span>
                            <span class="w-5 h-5 rounded bg-blue-500"></span>
                            <span class="w-5 h-5 rounded bg-green-600"></span>
                        </div>
                    </article>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
