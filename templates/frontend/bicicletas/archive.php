<?php
/**
 * Frontend: Archive de Bicicletas Compartidas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$estaciones = $estaciones ?? [];
$total_estaciones = $total_estaciones ?? 0;
$bicis_disponibles_total = $bicis_disponibles_total ?? 0;
?>

<div class="flavor-archive bicicletas">
    <!-- Header -->
    <div class="bg-gradient-to-r from-lime-500 to-green-500 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Bicicletas Compartidas</h1>
            <p class="text-white/90 text-lg">Muevete de forma sostenible por el barrio</p>
            <div class="mt-4 flex items-center gap-6 text-white/80 text-sm">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <?php echo esc_html($total_estaciones); ?> estaciones
                </span>
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <?php echo esc_html($bicis_disponibles_total); ?> bicis disponibles
                </span>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <!-- Mapa -->
        <div class="bg-white rounded-2xl shadow-md overflow-hidden mb-8">
            <div class="h-96 bg-gray-200 flex items-center justify-center">
                <div class="text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <p>Mapa interactivo de estaciones</p>
                </div>
            </div>
        </div>

        <!-- Lista de estaciones -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900">Estaciones Cercanas</h2>
            <select class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-lime-500">
                <option>Mas cercanas</option>
                <option>Mas bicis disponibles</option>
                <option>Nombre A-Z</option>
            </select>
        </div>

        <?php if (empty($estaciones)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay estaciones cercanas</h3>
                <p class="text-gray-500">Activa tu ubicacion para ver las estaciones mas proximas</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($estaciones as $estacion): ?>
                    <article class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                        <div class="p-5">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html($estacion['nombre']); ?></h3>
                                    <p class="text-sm text-gray-500 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                        <?php echo esc_html($estacion['distancia'] ?? '250m'); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-bold <?php echo ($estacion['bicis_disponibles'] ?? 0) > 0 ? 'text-lime-600' : 'text-red-500'; ?>">
                                        <?php echo esc_html($estacion['bicis_disponibles'] ?? 0); ?>
                                    </span>
                                    <p class="text-xs text-gray-500">bicis</p>
                                </div>
                            </div>

                            <!-- Barra de disponibilidad -->
                            <div class="mb-4">
                                <?php
                                $total = $estacion['capacidad'] ?? 10;
                                $disponibles = $estacion['bicis_disponibles'] ?? 0;
                                $porcentaje = ($disponibles / $total) * 100;
                                ?>
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full <?php echo $porcentaje > 30 ? 'bg-lime-500' : ($porcentaje > 0 ? 'bg-orange-500' : 'bg-red-500'); ?>"
                                         style="width: <?php echo $porcentaje; ?>%"></div>
                                </div>
                                <div class="flex justify-between mt-1 text-xs text-gray-500">
                                    <span><?php echo $disponibles; ?> disponibles</span>
                                    <span><?php echo $total - $disponibles; ?> huecos libres</span>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <a href="<?php echo esc_url($estacion['url'] ?? '#'); ?>"
                                   class="flex-1 py-2.5 rounded-xl text-center font-semibold text-white transition-all hover:scale-105"
                                   style="background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%);">
                                    Reservar Bici
                                </a>
                                <button class="px-4 py-2.5 rounded-xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
