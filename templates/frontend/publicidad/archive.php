<?php
/**
 * Frontend: Archive de Publicidad
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$campanias = $campanias ?? [];
$total_campanias = $total_campanias ?? 0;
$estadisticas = $estadisticas ?? [];
$tipos_campania = $tipos_campania ?? [];
?>

<div class="flavor-frontend flavor-publicidad-archive">
    <!-- Header con gradiente rosa -->
    <div class="bg-gradient-to-r from-pink-500 to-rose-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('📢 Publicidad', 'flavor-chat-ia'); ?></h1>
                <p class="text-pink-100"><?php echo esc_html__('Gestiona campanas publicitarias eticas para tu comunidad', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_campanias); ?> campanas activas
                </span>
                <button class="bg-white text-rose-600 px-6 py-3 rounded-xl font-semibold hover:bg-pink-50 transition-all shadow-md"
                        onclick="flavorPublicidad.crearCampania()">
                    <?php echo esc_html__('➕ Nueva Campana', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">📊</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['campanias_activas'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Campanas activas', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">👁️</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['impresiones'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Impresiones', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🖱️</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['clics'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Clics', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🎯</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['tasa_conversion'] ?? '0%'); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Tasa de conversion', 'flavor-chat-ia'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-pink-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4"><?php echo esc_html__('💡 ¿Como funciona?', 'flavor-chat-ia'); ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-pink-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🎨</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Disena', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Crea tu anuncio con imagenes y texto atractivo para tu audiencia', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-pink-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🎯</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Segmenta', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Elige tu publico objetivo por zona, intereses y demografia', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-pink-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">📈</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Mide', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Analiza el rendimiento y optimiza tus campanas en tiempo real', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>

    <!-- Filtros por tipo -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-pink-100 text-pink-700 font-medium hover:bg-pink-200 transition-colors filter-active" data-tipo="todos">
            <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-tipo="banner">
            <?php echo esc_html__('🖼️ Banner', 'flavor-chat-ia'); ?>
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-tipo="video">
            <?php echo esc_html__('🎬 Video', 'flavor-chat-ia'); ?>
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-tipo="nativo">
            <?php echo esc_html__('📝 Nativo', 'flavor-chat-ia'); ?>
        </button>
        <?php foreach ($tipos_campania as $tipo_campania_item): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-tipo="<?php echo esc_attr($tipo_campania_item['slug']); ?>">
            <?php echo esc_html($tipo_campania_item['icono'] ?? ''); ?> <?php echo esc_html($tipo_campania_item['nombre']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de campanas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($campanias)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">📢</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay campanas publicitarias', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500 mb-6"><?php echo esc_html__('¡Crea tu primera campana para llegar a la comunidad!', 'flavor-chat-ia'); ?></p>
            <button class="bg-pink-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-pink-600 transition-colors"
                    onclick="flavorPublicidad.crearCampania()">
                <?php echo esc_html__('Nueva Campana', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($campanias as $campania): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <!-- Cabecera de campana -->
                <div class="flex items-center justify-between mb-3">
                    <span class="bg-pink-100 text-pink-700 text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html($campania['tipo'] ?? 'Banner'); ?>
                    </span>
                    <span class="<?php
                        $estado_campania = $campania['estado'] ?? 'activa';
                        echo $estado_campania === 'activa' ? 'bg-green-100 text-green-700' : ($estado_campania === 'pausada' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600');
                    ?> text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html(ucfirst($estado_campania)); ?>
                    </span>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-pink-600 transition-colors">
                    <a href="<?php echo esc_url($campania['url'] ?? '#'); ?>">
                        <?php echo esc_html($campania['titulo']); ?>
                    </a>
                </h3>

                <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                    <?php echo esc_html($campania['descripcion'] ?? ''); ?>
                </p>

                <!-- Metricas -->
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Presupuesto', 'flavor-chat-ia'); ?></p>
                        <p class="font-semibold text-gray-800 text-sm"><?php echo esc_html($campania['presupuesto'] ?? '0'); ?> €</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Impresiones', 'flavor-chat-ia'); ?></p>
                        <p class="font-semibold text-gray-800 text-sm"><?php echo esc_html($campania['impresiones'] ?? 0); ?></p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-pink-100 flex items-center justify-center text-pink-700 text-xs font-medium">
                            <?php echo esc_html(mb_substr($campania['anunciante_nombre'] ?? 'A', 0, 1)); ?>
                        </div>
                        <span class="text-sm text-gray-600"><?php echo esc_html($campania['anunciante_nombre'] ?? 'Anunciante'); ?></span>
                    </div>
                    <a href="<?php echo esc_url($campania['url'] ?? '#'); ?>"
                       class="text-pink-600 hover:text-pink-700 font-medium text-sm">
                        <?php echo esc_html__('Ver detalle →', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_campanias > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"><?php echo esc_html__('← Anterior', 'flavor-chat-ia'); ?></button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_campanias / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-pink-500 text-white hover:bg-pink-600 transition-colors"><?php echo esc_html__('Siguiente →', 'flavor-chat-ia'); ?></button>
        </nav>
    </div>
    <?php endif; ?>
</div>
