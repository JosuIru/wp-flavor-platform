<?php
/**
 * Frontend: Búsqueda de Comunidades
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['vecinos', 'deportes', 'cultura', 'familias', 'solidaridad'];
?>

<div class="flavor-frontend flavor-comunidades-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-rose-500 to-pink-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center"><?php echo esc_html__('🔍 Buscar comunidades', 'flavor-chat-ia'); ?></h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="<?php echo esc_attr__('Buscar por nombre, tipo o ubicación...', 'flavor-chat-ia'); ?>"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-rose-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-rose-600 text-white p-3 rounded-lg hover:bg-rose-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-rose-100 text-sm"><?php echo esc_html__('Populares:', 'flavor-chat-ia'); ?></span>
            <?php foreach ($sugerencias as $sugerencia): ?>
            <a href="?q=<?php echo esc_attr($sugerencia); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> comunidad<?php echo $total_resultados !== 1 ? 'es' : ''; ?>
                para "<span class="text-rose-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-rose-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">🔍</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos comunidades', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500 mb-6"><?php echo esc_html__('¿Por qué no creas una comunidad con ese nombre?', 'flavor-chat-ia'); ?></p>
        <button class="bg-rose-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-rose-600 transition-colors"
                onclick="flavorComunidades.crearComunidad()">
            <?php echo esc_html__('Crear comunidad', 'flavor-chat-ia'); ?>
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $comunidad): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <?php if (!empty($comunidad['imagen'])): ?>
            <div class="h-32 overflow-hidden">
                <img src="<?php echo esc_url($comunidad['imagen']); ?>" alt=""
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            </div>
            <?php else: ?>
            <div class="h-32 bg-gradient-to-br from-rose-100 to-pink-100 flex items-center justify-center">
                <span class="text-4xl">🏘️</span>
            </div>
            <?php endif; ?>

            <div class="p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="bg-rose-100 text-rose-700 text-xs font-medium px-2 py-1 rounded-full">
                        <?php echo esc_html($comunidad['tipo'] ?? 'Vecinal'); ?>
                    </span>
                    <?php if (!empty($comunidad['verificada'])): ?>
                    <span class="text-green-500 text-sm" title="<?php echo esc_attr__('Verificada', 'flavor-chat-ia'); ?>">✓</span>
                    <?php endif; ?>
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-2">
                    <a href="<?php echo esc_url($comunidad['url']); ?>" class="hover:text-rose-600 transition-colors">
                        <?php echo esc_html($comunidad['nombre']); ?>
                    </a>
                </h3>

                <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo esc_html($comunidad['descripcion']); ?></p>

                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>👥 <?php echo esc_html($comunidad['miembros'] ?? 0); ?> miembros</span>
                    <span>📍 <?php echo esc_html($comunidad['ubicacion'] ?? 'Local'); ?></span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
