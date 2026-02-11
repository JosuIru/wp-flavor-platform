<?php
/**
 * Frontend: Archive de Presupuestos Participativos
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-presupuestos-archive">
    <!-- Header con gradiente amber/yellow -->
    <div class="bg-gradient-to-r from-amber-500 to-yellow-500 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('Presupuestos Participativos', 'flavor-chat-ia'); ?></h1>
                <p class="text-amber-100"><?php echo esc_html__('Decide como se invierte el presupuesto de tu municipio', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total); ?> proyectos registrados
                </span>
                <button class="bg-white text-amber-600 px-6 py-3 rounded-xl font-semibold hover:bg-amber-50 transition-all shadow-md"
                        onclick="flavorPresupuestos.nuevoProyecto()">
                    <?php echo esc_html__('Proponer Proyecto', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas rapidas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 font-bold">$</div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['presupuesto_total'] ?? '0'); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Presupuesto total', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center text-yellow-600 font-bold"><?php echo esc_html__('P', 'flavor-chat-ia'); ?></div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['proyectos_propuestos'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Proyectos propuestos', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 font-bold"><?php echo esc_html__('V', 'flavor-chat-ia'); ?></div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['votos_ciudadanos'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Votos ciudadanos', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600 font-bold"><?php echo esc_html__('E', 'flavor-chat-ia'); ?></div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['proyectos_ejecutados'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Proyectos ejecutados', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros rapidos por fase -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-amber-100 text-amber-700 font-medium hover:bg-amber-200 transition-colors filter-active"
                data-fase="todos">
            <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
        </button>
        <?php
        $fases_filtro = ['Propuestas', 'Evaluacion', 'Votacion', 'Ejecucion', 'Completado'];
        foreach ($fases_filtro as $fase_nombre): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors"
                data-fase="<?php echo esc_attr(strtolower($fase_nombre)); ?>">
            <?php echo esc_html($fase_nombre); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de proyectos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($items)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4"><?php echo esc_html__('&#x1F4B0;', 'flavor-chat-ia'); ?></div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay proyectos aun', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500 mb-6"><?php echo esc_html__('Propone un proyecto para tu municipio', 'flavor-chat-ia'); ?></p>
            <button class="bg-amber-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-amber-600 transition-colors"
                    onclick="flavorPresupuestos.nuevoProyecto()">
                <?php echo esc_html__('Proponer Proyecto', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($items as $proyecto): ?>
        <?php
        $fase_proyecto = $proyecto['fase'] ?? 'propuestas';
        $colores_fase = [
            'propuestas'  => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
            'evaluacion'  => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'],
            'votacion'    => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
            'ejecucion'   => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
            'completado'  => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
        ];
        $color_fase = $colores_fase[$fase_proyecto] ?? $colores_fase['propuestas'];
        $votos_proyecto = intval($proyecto['votos'] ?? 0);
        $umbral_votos = intval($proyecto['umbral'] ?? 100);
        $porcentaje_umbral = $umbral_votos > 0 ? min(100, round(($votos_proyecto / $umbral_votos) * 100)) : 0;
        ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 overflow-hidden flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-center justify-between mb-3">
                    <span class="<?php echo esc_attr($color_fase['bg']); ?> <?php echo esc_attr($color_fase['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                        <?php echo esc_html(ucfirst($fase_proyecto)); ?>
                    </span>
                    <span class="text-lg font-bold text-amber-600"><?php echo esc_html($proyecto['presupuesto'] ?? '0'); ?></span>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    <a href="<?php echo esc_url($proyecto['url'] ?? '#'); ?>" class="hover:text-amber-600 transition-colors">
                        <?php echo esc_html($proyecto['titulo'] ?? ''); ?>
                    </a>
                </h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo esc_html($proyecto['descripcion'] ?? ''); ?></p>

                <span class="inline-block bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs mb-3">
                    <?php echo esc_html($proyecto['categoria'] ?? ''); ?>
                </span>

                <!-- Barra de progreso de votos -->
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span><?php echo esc_html($votos_proyecto); ?> votos</span>
                        <span>Umbral: <?php echo esc_html($umbral_votos); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-amber-500 to-yellow-500 h-2 rounded-full" style="width: <?php echo esc_attr($porcentaje_umbral); ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-500"><?php echo esc_html($proyecto['distrito'] ?? ''); ?></span>
                <a href="<?php echo esc_url($proyecto['url'] ?? '#'); ?>" class="text-amber-600 text-sm font-medium hover:text-amber-700 transition-colors">
                    <?php echo esc_html__('Ver proyecto', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"><?php echo esc_html__('Anterior', 'flavor-chat-ia'); ?></button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo esc_html(ceil($total / 12)); ?></span>
            <button class="px-4 py-2 rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors"><?php echo esc_html__('Siguiente', 'flavor-chat-ia'); ?></button>
        </nav>
    </div>
    <?php endif; ?>
</div>
