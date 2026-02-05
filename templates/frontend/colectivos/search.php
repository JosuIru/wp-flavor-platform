<?php
/**
 * Frontend: Busqueda de Colectivos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$consulta_busqueda = $consulta_busqueda ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search colectivos">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-rose-500 to-red-600 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center">Buscar colectivos</h1>

            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           name="q"
                           value="<?php echo esc_attr($consulta_busqueda); ?>"
                           placeholder="Buscar colectivos y asociaciones..."
                           class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 text-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                </div>

                <!-- Sugerencias -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-500">Sugerencias:</span>
                    <a href="?q=cultural" class="px-3 py-1 rounded-full text-sm bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">cultural</a>
                    <a href="?q=deportivo" class="px-3 py-1 rounded-full text-sm bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">deportivo</a>
                    <a href="?q=medioambiental" class="px-3 py-1 rounded-full text-sm bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">medioambiental</a>
                    <a href="?q=vecinal" class="px-3 py-1 rounded-full text-sm bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">vecinal</a>
                    <a href="?q=solidario" class="px-3 py-1 rounded-full text-sm bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">solidario</a>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #f43f5e 0%, #dc2626 100%);">
                        Buscar Colectivos
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <?php if (!empty($consulta_busqueda)): ?>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                    <?php echo esc_html($total_resultados); ?> resultados para "<?php echo esc_html($consulta_busqueda); ?>"
                </h2>
            </div>
        <?php endif; ?>

        <?php if (empty($resultados) && !empty($consulta_busqueda)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No se encontraron colectivos</h3>
                <p class="text-gray-500 mb-4">Prueba con otros terminos de busqueda</p>
                <a href="?" class="text-rose-600 font-medium hover:text-rose-700">Ver todos los colectivos</a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($resultados as $resultado): ?>
                    <article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-shadow">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-400 to-red-500 flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold">
                                    <?php echo esc_html(mb_substr($resultado['nombre'] ?? 'C', 0, 1)); ?>
                                </span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 group-hover:text-rose-600 transition-colors">
                                    <a href="<?php echo esc_url($resultado['url'] ?? '#'); ?>">
                                        <?php echo esc_html($resultado['nombre'] ?? 'Colectivo'); ?>
                                    </a>
                                </h3>
                                <span class="text-xs text-rose-600 font-medium"><?php echo esc_html($resultado['categoria'] ?? 'General'); ?></span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-2 mb-2">
                            <?php echo esc_html($resultado['descripcion'] ?? ''); ?>
                        </p>
                        <p class="text-xs text-gray-500"><?php echo esc_html($resultado['miembros'] ?? 0); ?> miembros</p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Colectivos destacados -->
            <h2 class="text-xl font-bold text-gray-900 mb-6">Colectivos destacados</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $colectivos_ejemplo = ['Cultural del Barrio', 'Club Deportivo Vecinal', 'Asociacion Medioambiental', 'Colectivo Solidario', 'Grupo Vecinal Norte', 'Plataforma Ciudadana'];
                foreach ($colectivos_ejemplo as $indice_colectivo => $nombre_ejemplo):
                ?>
                    <article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-shadow">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-400 to-red-500 flex items-center justify-center">
                                <span class="text-white font-bold"><?php echo esc_html(mb_substr($nombre_ejemplo, 0, 1)); ?></span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 group-hover:text-rose-600 transition-colors"><?php echo esc_html($nombre_ejemplo); ?></h3>
                                <span class="text-xs text-rose-600 font-medium">General</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500"><?php echo (($indice_colectivo + 1) * 15); ?> miembros</p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
