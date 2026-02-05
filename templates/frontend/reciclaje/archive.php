<?php
/**
 * Frontend: Archive de Reciclaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$puntos = $puntos ?? [];
$total_puntos = $total_puntos ?? 0;
?>

<div class="flavor-archive reciclaje">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-500 to-green-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Puntos de Reciclaje</h1>
            <p class="text-white/90 text-lg">Encuentra donde reciclar cada tipo de residuo</p>
            <div class="mt-4 flex items-center gap-4 text-white/80 text-sm">
                <span><?php echo esc_html($total_puntos); ?> puntos de reciclaje</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <!-- Guia rapida de contenedores -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <?php
            $contenedores = [
                ['color' => 'bg-yellow-400', 'nombre' => 'Amarillo', 'residuos' => 'Plasticos y envases'],
                ['color' => 'bg-blue-500', 'nombre' => 'Azul', 'residuos' => 'Papel y carton'],
                ['color' => 'bg-green-600', 'nombre' => 'Verde', 'residuos' => 'Vidrio'],
                ['color' => 'bg-amber-700', 'nombre' => 'Marron', 'residuos' => 'Organico'],
                ['color' => 'bg-gray-500', 'nombre' => 'Gris', 'residuos' => 'Resto'],
            ];
            foreach ($contenedores as $cont):
            ?>
                <div class="bg-white rounded-xl p-4 shadow-md text-center">
                    <div class="w-12 h-12 rounded-xl <?php echo $cont['color']; ?> mx-auto mb-2"></div>
                    <p class="font-bold text-gray-900 text-sm"><?php echo esc_html($cont['nombre']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html($cont['residuos']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar de filtros -->
            <aside class="lg:w-72 flex-shrink-0">
                <?php include __DIR__ . '/filters.php'; ?>
            </aside>

            <!-- Lista de puntos -->
            <main class="flex-1">
                <!-- Mapa -->
                <div class="bg-white rounded-2xl shadow-md overflow-hidden mb-6">
                    <div class="h-64 bg-gray-200 flex items-center justify-center">
                        <div class="text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            <p class="text-sm">Mapa de puntos de reciclaje</p>
                        </div>
                    </div>
                </div>

                <?php if (empty($puntos)): ?>
                    <div class="bg-gray-50 rounded-2xl p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay puntos cercanos</h3>
                        <p class="text-gray-500">Activa tu ubicacion para ver los puntos mas proximos</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($puntos as $punto): ?>
                            <article class="bg-white rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900 mb-1"><?php echo esc_html($punto['nombre']); ?></h3>
                                        <p class="text-sm text-gray-500 mb-2"><?php echo esc_html($punto['direccion'] ?? ''); ?></p>

                                        <!-- Contenedores disponibles -->
                                        <div class="flex gap-2 mb-2">
                                            <?php foreach ($punto['contenedores'] ?? [] as $cont): ?>
                                                <span class="w-6 h-6 rounded <?php echo esc_attr($cont['color']); ?>" title="<?php echo esc_attr($cont['nombre']); ?>"></span>
                                            <?php endforeach; ?>
                                        </div>

                                        <span class="text-sm text-emerald-600"><?php echo esc_html($punto['distancia'] ?? '200m'); ?></span>
                                    </div>
                                    <a href="<?php echo esc_url($punto['url'] ?? '#'); ?>"
                                       class="px-4 py-2 rounded-xl bg-emerald-100 text-emerald-700 font-medium text-sm hover:bg-emerald-200 transition-colors flex-shrink-0">
                                        Como llegar
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>
