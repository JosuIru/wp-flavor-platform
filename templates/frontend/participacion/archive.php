<?php
/**
 * Frontend: Archive de Participacion Ciudadana
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-participacion-archive">
    <!-- Header con gradiente amber/orange -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">Participacion Ciudadana</h1>
                <p class="text-amber-100">Propuestas, debates y votaciones de tu comunidad</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total); ?> propuestas registradas
                </span>
                <button class="bg-white text-orange-600 px-6 py-3 rounded-xl font-semibold hover:bg-orange-50 transition-all shadow-md"
                        onclick="flavorParticipacion.nuevaPropuesta()">
                    Hacer Propuesta
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas rapidas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 font-bold">P</div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['propuestas_activas'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">Propuestas activas</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 font-bold">V</div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['votos_emitidos'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">Votos emitidos</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center text-yellow-600 font-bold">C</div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['ciudadanos'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">Ciudadanos</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600 font-bold">A</div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['propuestas_aprobadas'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500">Propuestas aprobadas</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl p-6 mb-8 border border-amber-100">
        <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">Como funciona la participacion</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
            <div>
                <div class="w-12 h-12 bg-amber-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">1</div>
                <h3 class="font-semibold text-gray-800">Propon</h3>
                <p class="text-sm text-gray-600 mt-1">Presenta tu idea para mejorar la comunidad</p>
            </div>
            <div>
                <div class="w-12 h-12 bg-orange-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">2</div>
                <h3 class="font-semibold text-gray-800">Debate</h3>
                <p class="text-sm text-gray-600 mt-1">Discute y enriquece las propuestas con otros vecinos</p>
            </div>
            <div>
                <div class="w-12 h-12 bg-orange-600 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">3</div>
                <h3 class="font-semibold text-gray-800">Vota</h3>
                <p class="text-sm text-gray-600 mt-1">Apoya las propuestas que te parezcan mejores</p>
            </div>
        </div>
    </div>

    <!-- Filtros rapidos por categoria -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-amber-100 text-amber-700 font-medium hover:bg-amber-200 transition-colors filter-active"
                data-categoria="todos">
            Todas
        </button>
        <?php foreach ($categorias as $categoria_item): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors"
                data-categoria="<?php echo esc_attr($categoria_item['slug'] ?? ''); ?>">
            <?php echo esc_html($categoria_item['nombre'] ?? ''); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de propuestas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($items)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">&#x1F4AC;</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay propuestas aun</h3>
            <p class="text-gray-500 mb-6">Se el primero en proponer una idea para tu comunidad</p>
            <button class="bg-amber-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-amber-600 transition-colors"
                    onclick="flavorParticipacion.nuevaPropuesta()">
                Hacer Propuesta
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($items as $propuesta): ?>
        <?php
        $estado_propuesta = $propuesta['estado'] ?? 'abierta';
        $colores_estado = [
            'abierta'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
            'en-debate' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
            'votacion'  => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
            'aprobada'  => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
            'rechazada' => ['bg' => 'bg-red-100', 'text' => 'text-red-700'],
        ];
        $color_estado = $colores_estado[$estado_propuesta] ?? $colores_estado['abierta'];
        ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 overflow-hidden flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-center justify-between mb-3">
                    <span class="inline-flex items-center <?php echo esc_attr($color_estado['bg']); ?> <?php echo esc_attr($color_estado['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                        <?php echo esc_html(ucfirst(str_replace('-', ' ', $estado_propuesta))); ?>
                    </span>
                    <span class="text-xs text-gray-400"><?php echo esc_html($propuesta['fecha'] ?? ''); ?></span>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    <a href="<?php echo esc_url($propuesta['url'] ?? '#'); ?>" class="hover:text-amber-600 transition-colors">
                        <?php echo esc_html($propuesta['titulo'] ?? ''); ?>
                    </a>
                </h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo esc_html($propuesta['descripcion'] ?? ''); ?></p>

                <!-- Autor -->
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-6 h-6 rounded-full bg-amber-200 flex items-center justify-center text-xs font-bold text-amber-700">
                        <?php echo esc_html(mb_substr($propuesta['autor'] ?? 'A', 0, 1)); ?>
                    </div>
                    <span class="text-sm text-gray-500"><?php echo esc_html($propuesta['autor'] ?? 'Anonimo'); ?></span>
                </div>

                <!-- Barra de votos -->
                <?php
                $votos_favor = intval($propuesta['votos_favor'] ?? 0);
                $votos_contra = intval($propuesta['votos_contra'] ?? 0);
                $votos_totales = $votos_favor + $votos_contra;
                $porcentaje_favor = $votos_totales > 0 ? round(($votos_favor / $votos_totales) * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span><?php echo esc_html($votos_favor); ?> a favor</span>
                        <span><?php echo esc_html($votos_contra); ?> en contra</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-2 rounded-full" style="width: <?php echo esc_attr($porcentaje_favor); ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Footer de tarjeta -->
            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-500"><?php echo esc_html($propuesta['categoria'] ?? ''); ?></span>
                <span class="text-xs text-gray-500 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <?php echo esc_html($propuesta['comentarios'] ?? 0); ?>
                </span>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo esc_html(ceil($total / 12)); ?></span>
            <button class="px-4 py-2 rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors">Siguiente</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
