<?php
/**
 * Frontend: Busqueda de Chat Grupos
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['vecinos', 'deportes', 'padres', 'mascotas', 'cultura'];
?>

<div class="flavor-frontend flavor-chat-grupos-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-violet-500 to-purple-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center"><?php echo esc_html__('🔍 Buscar grupos', 'flavor-chat-ia'); ?></h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="<?php echo esc_attr__('Busca grupos por nombre o tema (ej: vecinos, deportes, cocina...)', 'flavor-chat-ia'); ?>"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-violet-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-purple-600 text-white p-3 rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-violet-100 text-sm"><?php echo esc_html__('Populares:', 'flavor-chat-ia'); ?></span>
            <?php foreach ($sugerencias as $sugerencia_grupo): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_grupo); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_grupo); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> grupo<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-purple-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-purple-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">💬</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos grupos', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500 mb-6"><?php echo esc_html__('¿Por que no creas un grupo sobre ese tema?', 'flavor-chat-ia'); ?></p>
        <button class="bg-violet-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-violet-600 transition-colors"
                onclick="flavorChatGrupos.crearGrupo()">
            <?php echo esc_html__('Crear Grupo', 'flavor-chat-ia'); ?>
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $resultado_grupo): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <div class="flex items-start gap-4 mb-3">
                    <div class="w-12 h-12 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <?php echo esc_html(mb_substr($resultado_grupo['nombre'] ?? 'G', 0, 1)); ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-purple-600 transition-colors">
                            <a href="<?php echo esc_url($resultado_grupo['url'] ?? '#'); ?>">
                                <?php echo esc_html($resultado_grupo['nombre']); ?>
                            </a>
                        </h3>
                        <p class="text-gray-600 text-sm line-clamp-2"><?php echo esc_html($resultado_grupo['descripcion'] ?? ''); ?></p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">
                        👥 <?php echo esc_html($resultado_grupo['miembros'] ?? 0); ?> miembros
                    </span>
                    <span class="<?php echo ($resultado_grupo['tipo'] ?? 'publico') === 'publico' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'; ?> text-xs px-3 py-1 rounded-full">
                        <?php echo ($resultado_grupo['tipo'] ?? 'publico') === 'publico' ? '🌐 Publico' : '🔒 Privado'; ?>
                    </span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <p class="text-xs text-gray-500 truncate flex-1"><?php echo esc_html($resultado_grupo['ultimo_mensaje'] ?? 'Sin mensajes'); ?></p>
                    <a href="<?php echo esc_url($resultado_grupo['url'] ?? '#'); ?>"
                       class="text-purple-600 hover:text-purple-700 font-medium text-sm ml-3">
                        <?php echo esc_html__('Ver grupo →', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
