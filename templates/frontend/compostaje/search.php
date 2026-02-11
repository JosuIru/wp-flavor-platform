<?php
/**
 * Frontend: Busqueda de Composteras
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$consulta_busqueda = $consulta_busqueda ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search compostaje">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center"><?php echo esc_html__('Buscar composteras', 'flavor-chat-ia'); ?></h1>

            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           name="q"
                           value="<?php echo esc_attr($consulta_busqueda); ?>"
                           placeholder="<?php echo esc_attr__('Buscar composteras por nombre o ubicacion...', 'flavor-chat-ia'); ?>"
                           class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 text-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <!-- Sugerencias -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-500"><?php echo esc_html__('Sugerencias:', 'flavor-chat-ia'); ?></span>
                    <a href="?q=centro" class="px-3 py-1 rounded-full text-sm bg-green-50 text-green-600 hover:bg-green-100 transition-colors"><?php echo esc_html__('centro', 'flavor-chat-ia'); ?></a>
                    <a href="?q=norte" class="px-3 py-1 rounded-full text-sm bg-green-50 text-green-600 hover:bg-green-100 transition-colors"><?php echo esc_html__('norte', 'flavor-chat-ia'); ?></a>
                    <a href="?q=barrio" class="px-3 py-1 rounded-full text-sm bg-green-50 text-green-600 hover:bg-green-100 transition-colors"><?php echo esc_html__('barrio', 'flavor-chat-ia'); ?></a>
                    <a href="?q=disponible" class="px-3 py-1 rounded-full text-sm bg-green-50 text-green-600 hover:bg-green-100 transition-colors"><?php echo esc_html__('disponible', 'flavor-chat-ia'); ?></a>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #22c55e 0%, #059669 100%);">
                        <?php echo esc_html__('Buscar Composteras', 'flavor-chat-ia'); ?>
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
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No se encontraron composteras', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500 mb-4"><?php echo esc_html__('Prueba con otra ubicacion o nombre', 'flavor-chat-ia'); ?></p>
                <a href="?" class="text-green-600 font-medium hover:text-green-700"><?php echo esc_html__('Ver todas las composteras', 'flavor-chat-ia'); ?></a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($resultados as $resultado): ?>
                    <?php
                    $estado_resultado = $resultado['estado'] ?? 'activa';
                    $colores_estado_resultado = [
                        'activa'         => 'bg-green-100 text-green-700',
                        'llena'          => 'bg-amber-100 text-amber-700',
                        'mantenimiento'  => 'bg-red-100 text-red-700',
                    ];
                    $clase_estado_resultado = $colores_estado_resultado[$estado_resultado] ?? $colores_estado_resultado['activa'];
                    ?>
                    <article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-gray-900 group-hover:text-green-600 transition-colors">
                                <a href="<?php echo esc_url($resultado['url'] ?? '#'); ?>">
                                    <?php echo esc_html($resultado['nombre'] ?? 'Compostera'); ?>
                                </a>
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold <?php echo esc_attr($clase_estado_resultado); ?>">
                                <?php echo esc_html(ucfirst($estado_resultado)); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mb-2">
                            <?php echo esc_html($resultado['ubicacion'] ?? ''); ?>
                        </p>
                        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-green-500" style="width: <?php echo esc_attr($resultado['capacidad'] ?? 50); ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($resultado['capacidad'] ?? 50); ?>% capacidad</p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Composteras disponibles -->
            <h2 class="text-xl font-bold text-gray-900 mb-6"><?php echo esc_html__('Composteras disponibles', 'flavor-chat-ia'); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $composteras_ejemplo = [
                    ['nombre' => 'Compostera Parque Central', 'ubicacion' => 'Barrio Centro', 'capacidad' => 45, 'estado' => 'activa'],
                    ['nombre' => 'Compostera Plaza Norte', 'ubicacion' => 'Barrio Norte', 'capacidad' => 72, 'estado' => 'activa'],
                    ['nombre' => 'Compostera Jardin Sur', 'ubicacion' => 'Barrio Sur', 'capacidad' => 30, 'estado' => 'activa'],
                    ['nombre' => 'Compostera Avenida Este', 'ubicacion' => 'Barrio Este', 'capacidad' => 95, 'estado' => 'llena'],
                    ['nombre' => 'Compostera Rio Oeste', 'ubicacion' => 'Barrio Oeste', 'capacidad' => 10, 'estado' => 'activa'],
                    ['nombre' => 'Compostera Mercado', 'ubicacion' => 'Centro Comercial', 'capacidad' => 60, 'estado' => 'mantenimiento'],
                ];
                foreach ($composteras_ejemplo as $compostera_ejemplo):
                    $color_ejemplo = $compostera_ejemplo['estado'] === 'activa' ? 'bg-green-100 text-green-700' : ($compostera_ejemplo['estado'] === 'llena' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700');
                ?>
                    <article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-gray-900 group-hover:text-green-600 transition-colors">
                                <?php echo esc_html($compostera_ejemplo['nombre']); ?>
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold <?php echo esc_attr($color_ejemplo); ?>">
                                <?php echo esc_html(ucfirst($compostera_ejemplo['estado'])); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mb-2"><?php echo esc_html($compostera_ejemplo['ubicacion']); ?></p>
                        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full <?php echo $compostera_ejemplo['capacidad'] > 80 ? 'bg-amber-500' : 'bg-green-500'; ?>"
                                 style="width: <?php echo esc_attr($compostera_ejemplo['capacidad']); ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($compostera_ejemplo['capacidad']); ?>% capacidad</p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
