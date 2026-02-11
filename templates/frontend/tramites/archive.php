<?php
/**
 * Frontend: Archive de Tramites Online
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-tramites-archive">
    <!-- Header con gradiente orange/amber -->
    <div class="bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('Tramites Online', 'flavor-chat-ia'); ?></h1>
                <p class="text-orange-100"><?php echo esc_html__('Realiza tus gestiones municipales desde cualquier lugar', 'flavor-chat-ia'); ?></p>
            </div>
            <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                <?php echo esc_html($total); ?> tramites disponibles
            </span>
        </div>

        <!-- Buscador destacado -->
        <form action="" method="get" class="mt-6 max-w-2xl">
            <div class="relative">
                <input type="text" name="q" placeholder="<?php echo esc_attr__('Busca tu tramite (ej: empadronamiento, licencia de obra...)', 'flavor-chat-ia'); ?>"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg text-gray-800 border-0 shadow-lg focus:ring-4 focus:ring-orange-300"
                       autocomplete="off">
                <button type="submit"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-orange-600 text-white p-3 rounded-lg hover:bg-orange-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Estadisticas rapidas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 font-bold"><?php echo esc_html__('T', 'flavor-chat-ia'); ?></div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['tramites_disponibles'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Tramites disponibles', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 font-bold"><?php echo esc_html__('S', 'flavor-chat-ia'); ?></div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['solicitudes_procesadas'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Solicitudes procesadas', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 font-bold"><?php echo esc_html__('R', 'flavor-chat-ia'); ?></div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['tiempo_medio'] ?? '0d'); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Tiempo medio', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600 font-bold">%</div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['satisfaccion'] ?? '0%'); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Satisfaccion', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros rapidos por categoria -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-orange-100 text-orange-700 font-medium hover:bg-orange-200 transition-colors filter-active"
                data-categoria="todos">
            <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
        </button>
        <?php
        $categorias_tramites = ['Empadronamiento', 'Licencias', 'Impuestos', 'Certificados'];
        foreach ($categorias_tramites as $categoria_tramite): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors"
                data-categoria="<?php echo esc_attr(strtolower($categoria_tramite)); ?>">
            <?php echo esc_html($categoria_tramite); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de tramites -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($items)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4"><?php echo esc_html__('&#x1F4CB;', 'flavor-chat-ia'); ?></div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay tramites disponibles', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500"><?php echo esc_html__('Los tramites se actualizaran proximamente', 'flavor-chat-ia'); ?></p>
        </div>
        <?php else: ?>
        <?php foreach ($items as $tramite): ?>
        <?php
        $modalidad_tramite = $tramite['modalidad'] ?? 'online';
        $colores_modalidad = [
            'online'     => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
            'presencial' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
            'ambos'      => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
        ];
        $color_modalidad = $colores_modalidad[$modalidad_tramite] ?? $colores_modalidad['online'];
        ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 overflow-hidden flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="<?php echo esc_attr($color_modalidad['bg']); ?> <?php echo esc_attr($color_modalidad['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                        <?php echo esc_html(ucfirst($modalidad_tramite)); ?>
                    </span>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    <a href="<?php echo esc_url($tramite['url'] ?? '#'); ?>" class="hover:text-orange-600 transition-colors">
                        <?php echo esc_html($tramite['titulo'] ?? ''); ?>
                    </a>
                </h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo esc_html($tramite['descripcion'] ?? ''); ?></p>

                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?php echo esc_html($tramite['tiempo_estimado'] ?? ''); ?>
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <?php echo esc_html($tramite['requisitos_count'] ?? 0); ?> requisitos
                    </span>
                </div>
            </div>

            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100">
                <a href="<?php echo esc_url($tramite['url'] ?? '#'); ?>"
                   class="w-full inline-flex items-center justify-center gap-2 bg-orange-500 text-white py-2 px-4 rounded-lg font-medium hover:bg-orange-600 transition-colors text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    <?php echo esc_html__('Iniciar tramite', 'flavor-chat-ia'); ?>
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
            <button class="px-4 py-2 rounded-lg bg-orange-500 text-white hover:bg-orange-600 transition-colors"><?php echo esc_html__('Siguiente', 'flavor-chat-ia'); ?></button>
        </nav>
    </div>
    <?php endif; ?>
</div>
