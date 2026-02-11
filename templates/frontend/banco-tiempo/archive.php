<?php
/**
 * Frontend: Archive de Banco de Tiempo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$servicios = $servicios ?? [];
$total_servicios = $total_servicios ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-banco-tiempo-archive">
    <!-- Header con gradiente violeta -->
    <div class="bg-gradient-to-r from-violet-500 to-purple-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('⏰ Banco de Tiempo', 'flavor-chat-ia'); ?></h1>
                <p class="text-violet-100"><?php echo esc_html__('Intercambia servicios con tus vecinos: 1 hora = 1 hora', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_servicios); ?> servicios disponibles
                </span>
                <button class="bg-white text-violet-600 px-6 py-3 rounded-xl font-semibold hover:bg-violet-50 transition-all shadow-md"
                        onclick="flavorBancoTiempo.ofrecerServicio()">
                    <?php echo esc_html__('➕ Ofrecer servicio', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">👥</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['miembros'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Miembros activos', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🔄</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['intercambios'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Intercambios realizados', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">⏱️</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['horas_intercambiadas'] ?? 0); ?>h</p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Horas intercambiadas', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">⭐</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['valoracion_media'] ?? '4.8'); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Valoración media', 'flavor-chat-ia'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cómo funciona -->
    <div class="bg-violet-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4"><?php echo esc_html__('💡 ¿Cómo funciona?', 'flavor-chat-ia'); ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-violet-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🎁</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Ofrece', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Comparte tus habilidades y talentos con la comunidad', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-violet-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🤝</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Intercambia', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('1 hora de tu tiempo = 1 hora de cualquier servicio', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-violet-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">✨</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Recibe', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Utiliza tus horas ganadas para recibir servicios', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>

    <!-- Filtros por categoría -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-violet-100 text-violet-700 font-medium hover:bg-violet-200 transition-colors filter-active" data-categoria="todos">
            <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
        </button>
        <?php foreach ($categorias as $cat): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="<?php echo esc_attr($cat['slug']); ?>">
            <?php echo esc_html($cat['icono'] ?? ''); ?> <?php echo esc_html($cat['nombre']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de servicios -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($servicios)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">⏰</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay servicios disponibles', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500 mb-6"><?php echo esc_html__('¡Sé el primero en ofrecer un servicio!', 'flavor-chat-ia'); ?></p>
            <button class="bg-violet-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-violet-600 transition-colors"
                    onclick="flavorBancoTiempo.ofrecerServicio()">
                <?php echo esc_html__('Ofrecer servicio', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($servicios as $servicio): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-6">
                <!-- Tipo: oferta o demanda -->
                <div class="flex items-center justify-between mb-3">
                    <span class="<?php echo $servicio['tipo'] === 'oferta' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?> text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo $servicio['tipo'] === 'oferta' ? '🎁 Ofrezco' : '🙋 Busco'; ?>
                    </span>
                    <span class="text-violet-600 font-bold"><?php echo esc_html($servicio['horas']); ?>h</span>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-violet-600 transition-colors">
                    <a href="<?php echo esc_url($servicio['url']); ?>">
                        <?php echo esc_html($servicio['titulo']); ?>
                    </a>
                </h3>

                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                    <?php echo esc_html($servicio['descripcion']); ?>
                </p>

                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 font-medium">
                        <?php echo esc_html(mb_substr($servicio['usuario_nombre'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800"><?php echo esc_html($servicio['usuario_nombre']); ?></p>
                        <div class="flex items-center gap-1 text-xs text-gray-500">
                            <span>⭐ <?php echo esc_html($servicio['usuario_valoracion'] ?? '5.0'); ?></span>
                            <span>•</span>
                            <span><?php echo esc_html($servicio['usuario_intercambios'] ?? 0); ?> intercambios</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <span class="text-xs text-gray-500 flex items-center gap-1">
                        🏷️ <?php echo esc_html($servicio['categoria'] ?? 'General'); ?>
                    </span>
                    <a href="<?php echo esc_url($servicio['url']); ?>"
                       class="text-violet-600 hover:text-violet-700 font-medium text-sm">
                        <?php echo esc_html__('Ver más →', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <?php if ($total_servicios > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"><?php echo esc_html__('← Anterior', 'flavor-chat-ia'); ?></button>
            <span class="px-4 py-2 text-gray-600">Página 1 de <?php echo ceil($total_servicios / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-violet-500 text-white hover:bg-violet-600 transition-colors"><?php echo esc_html__('Siguiente →', 'flavor-chat-ia'); ?></button>
        </nav>
    </div>
    <?php endif; ?>
</div>
