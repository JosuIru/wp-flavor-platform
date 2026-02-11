<?php
/**
 * Frontend: Archive de Avisos Municipales
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-avisos-archive">
    <!-- Header con gradiente sky/blue -->
    <div class="bg-gradient-to-r from-sky-500 to-blue-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('Avisos Municipales', 'flavor-chat-ia'); ?></h1>
                <p class="text-sky-100"><?php echo esc_html__('Mantente informado sobre las novedades de tu municipio', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total); ?> avisos publicados
                </span>
                <button class="bg-white text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-blue-50 transition-all shadow-md"
                        onclick="flavorAvisos.suscribirse()">
                    <?php echo esc_html__('Suscribirse a Avisos', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas rapidas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sky-100 rounded-lg flex items-center justify-center text-sky-600 font-bold"><?php echo esc_html__('A', 'flavor-chat-ia'); ?></div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['avisos_activos'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Avisos activos', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 font-bold"><?php echo esc_html__('M', 'flavor-chat-ia'); ?></div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['este_mes'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-red-600 font-bold"><?php echo esc_html__('U', 'flavor-chat-ia'); ?></div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['urgentes'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Urgentes', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600 font-bold"><?php echo esc_html__('B', 'flavor-chat-ia'); ?></div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['barrios'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Barrios informados', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros rapidos por categoria -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-sky-100 text-sky-700 font-medium hover:bg-sky-200 transition-colors filter-active"
                data-categoria="todos">
            <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
        </button>
        <?php
        $categorias_avisos = ['Obras', 'Servicios', 'Trafico', 'Medio Ambiente', 'Cultural'];
        foreach ($categorias_avisos as $categoria_aviso): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors"
                data-categoria="<?php echo esc_attr(strtolower(str_replace(' ', '-', $categoria_aviso))); ?>">
            <?php echo esc_html($categoria_aviso); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de avisos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($items)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4"><?php echo esc_html__('&#x1F4E2;', 'flavor-chat-ia'); ?></div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay avisos activos', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500 mb-6"><?php echo esc_html__('No hay avisos municipales en este momento', 'flavor-chat-ia'); ?></p>
            <button class="bg-sky-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-sky-600 transition-colors"
                    onclick="flavorAvisos.suscribirse()">
                <?php echo esc_html__('Suscribirse a Avisos', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($items as $aviso): ?>
        <?php
        $urgencia_aviso = $aviso['urgencia'] ?? 'informativo';
        $colores_urgencia = [
            'informativo' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-700', 'borde' => 'border-l-sky-500'],
            'importante'  => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'borde' => 'border-l-amber-500'],
            'urgente'     => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'borde' => 'border-l-red-500'],
        ];
        $color_urgencia = $colores_urgencia[$urgencia_aviso] ?? $colores_urgencia['informativo'];

        $iconos_categoria_aviso = [
            'obras'           => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
            'servicios'       => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'trafico'         => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
            'medio-ambiente'  => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'cultural'        => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>',
        ];
        $categoria_slug_aviso = strtolower(str_replace(' ', '-', $aviso['categoria'] ?? 'servicios'));
        $icono_categoria_svg = $iconos_categoria_aviso[$categoria_slug_aviso] ?? $iconos_categoria_aviso['servicios'];
        ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 overflow-hidden flex flex-col border-l-4 <?php echo esc_attr($color_urgencia['borde']); ?>">
            <div class="p-5 flex-1">
                <div class="flex items-center justify-between mb-3">
                    <span class="<?php echo esc_attr($color_urgencia['bg']); ?> <?php echo esc_attr($color_urgencia['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                        <?php echo esc_html(ucfirst($urgencia_aviso)); ?>
                    </span>
                    <div class="text-sky-500">
                        <?php echo $icono_categoria_svg; ?>
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    <a href="<?php echo esc_url($aviso['url'] ?? '#'); ?>" class="hover:text-sky-600 transition-colors">
                        <?php echo esc_html($aviso['titulo'] ?? ''); ?>
                    </a>
                </h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo esc_html($aviso['resumen'] ?? ''); ?></p>

                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <?php echo esc_html($aviso['fecha'] ?? ''); ?>
                    </span>
                    <?php if (!empty($aviso['zona_afectada'])): ?>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php echo esc_html($aviso['zona_afectada']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-500"><?php echo esc_html($aviso['categoria'] ?? ''); ?></span>
                <a href="<?php echo esc_url($aviso['url'] ?? '#'); ?>" class="text-sky-600 text-sm font-medium hover:text-sky-700 transition-colors">
                    <?php echo esc_html__('Leer mas', 'flavor-chat-ia'); ?>
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
            <button class="px-4 py-2 rounded-lg bg-sky-500 text-white hover:bg-sky-600 transition-colors"><?php echo esc_html__('Siguiente', 'flavor-chat-ia'); ?></button>
        </nav>
    </div>
    <?php endif; ?>
</div>
