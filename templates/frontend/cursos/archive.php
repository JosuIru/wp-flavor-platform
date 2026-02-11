<?php
/**
 * Frontend: Archive de Cursos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$cursos = $cursos ?? [];
$total_cursos = $total_cursos ?? 0;
$pagina_actual = $pagina_actual ?? 1;
$por_pagina = $por_pagina ?? 12;
?>

<div class="flavor-archive cursos">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-violet-600 py-12 px-4">
        <div class="container mx-auto max-w-6xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?php echo esc_html__('Cursos y Talleres', 'flavor-chat-ia'); ?></h1>
            <p class="text-white/90 text-lg"><?php echo esc_html__('Aprende nuevas habilidades con tus vecinos', 'flavor-chat-ia'); ?></p>
            <div class="mt-4 flex items-center gap-4 text-white/80 text-sm">
                <span><?php echo esc_html($total_cursos); ?> cursos disponibles</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar de filtros -->
            <aside class="lg:w-72 flex-shrink-0">
                <?php include __DIR__ . '/filters.php'; ?>
            </aside>

            <!-- Lista de cursos -->
            <main class="flex-1">
                <!-- Tabs -->
                <div class="flex items-center gap-4 mb-6 border-b border-gray-200">
                    <button class="px-4 py-3 font-semibold text-purple-600 border-b-2 border-purple-500">
                        <?php echo esc_html__('Proximos', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="px-4 py-3 font-semibold text-gray-500 hover:text-purple-600">
                        <?php echo esc_html__('En curso', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="px-4 py-3 font-semibold text-gray-500 hover:text-purple-600">
                        <?php echo esc_html__('Online', 'flavor-chat-ia'); ?>
                    </button>
                </div>

                <!-- Ordenacion -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-600">
                        <?php echo esc_html__('Mostrando', 'flavor-chat-ia'); ?> <span class="font-semibold"><?php echo count($cursos); ?></span> de <?php echo esc_html($total_cursos); ?> cursos
                    </p>
                    <select class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm focus:ring-2 focus:ring-purple-500">
                        <option><?php echo esc_html__('Fecha: mas proximos', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Precio: menor a mayor', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Mejor valorados', 'flavor-chat-ia'); ?></option>
                        <option><?php echo esc_html__('Mas inscritos', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <?php if (empty($cursos)): ?>
                    <div class="bg-gray-50 rounded-2xl p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay cursos disponibles', 'flavor-chat-ia'); ?></h3>
                        <p class="text-gray-500"><?php echo esc_html__('Prueba a modificar los filtros', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <!-- Grid de cursos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($cursos as $curso): ?>
                            <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                                <div class="relative aspect-[16/9] overflow-hidden">
                                    <img src="<?php echo esc_url($curso['imagen'] ?? 'https://picsum.photos/seed/curso' . rand(1,100) . '/600/340'); ?>"
                                         alt="<?php echo esc_attr($curso['titulo']); ?>"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

                                    <!-- Fecha -->
                                    <div class="absolute bottom-3 left-3 flex items-center gap-2 text-white text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span><?php echo esc_html($curso['fecha'] ?? '15 Feb'); ?></span>
                                    </div>

                                    <!-- Badge -->
                                    <?php if (!empty($curso['gratuito'])): ?>
                                        <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white"><?php echo esc_html__('Gratuito', 'flavor-chat-ia'); ?></span>
                                    <?php elseif (!empty($curso['plazas_limitadas'])): ?>
                                        <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-orange-500 text-white"><?php echo esc_html__('Ultimas plazas', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="p-5">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-700 mb-2">
                                        <?php echo esc_html($curso['categoria'] ?? 'General'); ?>
                                    </span>

                                    <h2 class="text-lg font-bold text-gray-900 group-hover:text-purple-600 transition-colors mb-2">
                                        <a href="<?php echo esc_url($curso['url'] ?? '#'); ?>">
                                            <?php echo esc_html($curso['titulo']); ?>
                                        </a>
                                    </h2>

                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                        <?php echo esc_html($curso['descripcion'] ?? ''); ?>
                                    </p>

                                    <!-- Instructor -->
                                    <div class="flex items-center gap-2 mb-4">
                                        <img src="<?php echo esc_url($curso['instructor_avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1,70)); ?>"
                                             alt="" class="w-6 h-6 rounded-full object-cover">
                                        <span class="text-sm text-gray-500"><?php echo esc_html($curso['instructor'] ?? 'Instructor'); ?></span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="text-lg font-bold <?php echo !empty($curso['gratuito']) ? 'text-green-600' : 'text-purple-600'; ?>">
                                            <?php echo !empty($curso['gratuito']) ? 'Gratis' : esc_html($curso['precio'] ?? '25€'); ?>
                                        </span>
                                        <a href="<?php echo esc_url($curso['url'] ?? '#'); ?>"
                                           class="px-4 py-2 rounded-xl text-white font-semibold text-sm transition-all hover:scale-105"
                                           style="background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);">
                                            <?php echo esc_html__('Inscribirse', 'flavor-chat-ia'); ?>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginacion -->
                    <?php if ($total_cursos > $por_pagina): ?>
                        <nav class="flex items-center justify-center gap-2 mt-8">
                            <?php
                            $total_paginas = ceil($total_cursos / $por_pagina);
                            for ($i = 1; $i <= $total_paginas; $i++):
                            ?>
                                <a href="?pagina=<?php echo $i; ?>"
                                   class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-colors <?php echo $i === $pagina_actual ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-purple-100'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>
