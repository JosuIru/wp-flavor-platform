<?php
/**
 * Frontend: Archive de Grupos de Consumo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$grupos = $grupos ?? [];
$total_grupos = $total_grupos ?? 0;
$estadisticas = $estadisticas ?? [];
?>

<div class="flavor-frontend flavor-grupos-consumo-archive">
    <!-- Header con gradiente verde lima -->
    <div class="bg-gradient-to-r from-lime-500 to-green-500 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">🥕 Grupos de Consumo</h1>
                <p class="text-lime-100">Consume productos locales, ecológicos y de temporada junto a tus vecinos</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_grupos); ?> grupos activos
                </span>
                <button class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-green-50 transition-all shadow-md"
                        onclick="flavorGruposConsumo.crearGrupo()">
                    ➕ Crear grupo
                </button>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">👥</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['total_miembros'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Miembros activos</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🌾</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['productores'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Productores locales</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">📦</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['pedidos_mes'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Pedidos este mes</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">💚</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['kg_co2_ahorrados'] ?? 0); ?>kg</p>
            <p class="text-sm text-gray-500">CO₂ ahorrado</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cómo funciona -->
    <div class="bg-lime-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">🌱 ¿Cómo funciona?</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="text-center">
                <div class="w-12 h-12 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">1</div>
                <p class="text-sm text-gray-600">Únete a un grupo o crea el tuyo</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">2</div>
                <p class="text-sm text-gray-600">Haz tu pedido semanal</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">3</div>
                <p class="text-sm text-gray-600">Recoge en el punto acordado</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-2 text-xl font-bold">4</div>
                <p class="text-sm text-gray-600">Disfruta productos frescos</p>
            </div>
        </div>
    </div>

    <!-- Grid de grupos -->
    <h2 class="text-xl font-bold text-gray-800 mb-4">Grupos disponibles</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($grupos)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">🥬</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay grupos todavía</h3>
            <p class="text-gray-500 mb-6">¡Sé el primero en crear un grupo de consumo!</p>
            <button class="bg-lime-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-lime-600 transition-colors"
                    onclick="flavorGruposConsumo.crearGrupo()">
                Crear grupo de consumo
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($grupos as $grupo): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="relative h-40 bg-gradient-to-br from-lime-400 to-green-500">
                <?php if (!empty($grupo['imagen'])): ?>
                <img src="<?php echo esc_url($grupo['imagen']); ?>" alt="<?php echo esc_attr($grupo['nombre']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                <div class="absolute inset-0 flex items-center justify-center text-white text-6xl opacity-30">🥕</div>
                <?php endif; ?>
                <?php if ($grupo['abierto_inscripciones'] ?? true): ?>
                <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                    Abierto
                </span>
                <?php endif; ?>
            </div>

            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-lime-600 transition-colors">
                    <a href="<?php echo esc_url($grupo['url']); ?>">
                        <?php echo esc_html($grupo['nombre']); ?>
                    </a>
                </h3>

                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                    <?php echo esc_html($grupo['descripcion']); ?>
                </p>

                <div class="flex flex-wrap gap-2 mb-4">
                    <?php if (!empty($grupo['categorias'])): ?>
                    <?php foreach (array_slice($grupo['categorias'], 0, 3) as $cat): ?>
                    <span class="bg-lime-100 text-lime-700 text-xs px-2 py-1 rounded-full">
                        <?php echo esc_html($cat); ?>
                    </span>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="flex items-center justify-between text-sm text-gray-500 border-t border-gray-100 pt-4">
                    <span class="flex items-center gap-1">
                        👥 <?php echo esc_html($grupo['num_miembros'] ?? 0); ?> miembros
                    </span>
                    <span class="flex items-center gap-1">
                        📍 <?php echo esc_html($grupo['zona'] ?? 'Sin zona'); ?>
                    </span>
                </div>

                <a href="<?php echo esc_url($grupo['url']); ?>"
                   class="block mt-4 text-center bg-lime-500 hover:bg-lime-600 text-white py-2 px-4 rounded-xl font-medium transition-colors">
                    Ver grupo
                </a>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <?php if ($total_grupos > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">← Anterior</button>
            <span class="px-4 py-2 text-gray-600">Página 1 de <?php echo ceil($total_grupos / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-lime-500 text-white hover:bg-lime-600 transition-colors">Siguiente →</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
