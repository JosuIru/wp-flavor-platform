<?php
/**
 * Frontend: Busqueda de Chat Interno
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['documentos', 'reunion', 'propuesta', 'urgente', 'presupuesto'];
?>

<div class="flavor-frontend flavor-chat-interno-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-sky-500 to-blue-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center"><?php echo esc_html__('🔍 Buscar mensajes', 'flavor-chat-ia'); ?></h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="<?php echo esc_attr__('Busca mensajes o contactos...', 'flavor-chat-ia'); ?>"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-sky-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-sky-100 text-sm"><?php echo esc_html__('Busquedas frecuentes:', 'flavor-chat-ia'); ?></span>
            <?php foreach ($sugerencias as $sugerencia_mensaje): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_mensaje); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_mensaje); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> resultado<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-blue-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-blue-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">✉️</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos mensajes', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500 mb-6"><?php echo esc_html__('Intenta con otros terminos de busqueda', 'flavor-chat-ia'); ?></p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="divide-y divide-gray-100">
            <?php foreach ($resultados as $resultado_mensaje): ?>
            <a href="<?php echo esc_url($resultado_mensaje['url'] ?? '#'); ?>"
               class="flex items-center gap-4 p-4 hover:bg-sky-50 transition-colors block">
                <!-- Avatar -->
                <div class="w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center text-sky-700 text-sm font-bold flex-shrink-0">
                    <?php echo esc_html(mb_substr($resultado_mensaje['contacto_nombre'] ?? 'U', 0, 1)); ?>
                </div>

                <!-- Contenido -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-semibold text-gray-800 truncate">
                            <?php echo esc_html($resultado_mensaje['contacto_nombre'] ?? ''); ?>
                        </h3>
                        <span class="text-xs text-gray-400 flex-shrink-0 ml-2"><?php echo esc_html($resultado_mensaje['fecha'] ?? ''); ?></span>
                    </div>
                    <p class="text-sm text-gray-600 truncate"><?php echo esc_html($resultado_mensaje['extracto'] ?? ''); ?></p>
                </div>

                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
