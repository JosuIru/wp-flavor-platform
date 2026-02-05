<?php
/**
 * Frontend: Archive de Bares y Restaurantes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$locales = $locales ?? [];
$total_locales = $total_locales ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-bares-archive">
    <!-- Header con gradiente ambar -->
    <div class="bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">🍽️ Bares y Restaurantes</h1>
                <p class="text-amber-100">Descubre los mejores locales de tu barrio</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_locales); ?> locales registrados
                </span>
                <button class="bg-white text-orange-600 px-6 py-3 rounded-xl font-semibold hover:bg-amber-50 transition-all shadow-md"
                        onclick="flavorBares.registrarLocal()">
                    ➕ Registrar Local
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🏪</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['locales_registrados'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Locales registrados</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">⭐</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['resenas'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Resenas</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🍳</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['platos_dia'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Platos del dia</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🎉</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['eventos'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Eventos</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-amber-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">💡 ¿Como funciona?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-amber-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🔍</div>
                <h3 class="font-semibold text-gray-800 mb-1">Explora</h3>
                <p class="text-sm text-gray-600">Encuentra bares y restaurantes cerca de ti por tipo de cocina</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-amber-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">📖</div>
                <h3 class="font-semibold text-gray-800 mb-1">Consulta</h3>
                <p class="text-sm text-gray-600">Mira la carta, horarios, resenas y platos del dia</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-amber-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">📝</div>
                <h3 class="font-semibold text-gray-800 mb-1">Reserva</h3>
                <p class="text-sm text-gray-600">Reserva mesa directamente y comparte tu experiencia</p>
            </div>
        </div>
    </div>

    <!-- Filtros por categoria -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-amber-100 text-amber-700 font-medium hover:bg-amber-200 transition-colors filter-active" data-categoria="todos">
            Todos
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="tapas">
            🍢 Tapas
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="restaurante">
            🍽️ Restaurante
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="cafeteria">
            ☕ Cafeteria
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="pub">
            🍺 Pub
        </button>
        <?php foreach ($categorias as $categoria_bar): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="<?php echo esc_attr($categoria_bar['slug']); ?>">
            <?php echo esc_html($categoria_bar['icono'] ?? ''); ?> <?php echo esc_html($categoria_bar['nombre']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de locales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($locales)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">🍽️</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay locales registrados</h3>
            <p class="text-gray-500 mb-6">¡Registra tu bar o restaurante!</p>
            <button class="bg-amber-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-amber-600 transition-colors"
                    onclick="flavorBares.registrarLocal()">
                Registrar Local
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($locales as $local): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="aspect-video bg-gray-100 relative overflow-hidden">
                <?php if (!empty($local['imagen'])): ?>
                <img src="<?php echo esc_url($local['imagen']); ?>" alt="<?php echo esc_attr($local['nombre']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <span class="text-5xl">🍽️</span>
                </div>
                <?php endif; ?>
                <span class="absolute top-3 left-3 <?php echo ($local['abierto'] ?? false) ? 'bg-green-500' : 'bg-red-500'; ?> text-white text-xs font-medium px-3 py-1 rounded-full shadow">
                    <?php echo ($local['abierto'] ?? false) ? 'Abierto' : 'Cerrado'; ?>
                </span>
            </div>
            <div class="p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="bg-amber-100 text-amber-700 text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html($local['tipo_cocina'] ?? 'Variada'); ?>
                    </span>
                    <span class="text-amber-600 font-bold text-sm">
                        <?php echo esc_html(str_repeat('€', $local['rango_precio'] ?? 1)); ?>
                    </span>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-orange-600 transition-colors">
                    <a href="<?php echo esc_url($local['url'] ?? '#'); ?>">
                        <?php echo esc_html($local['nombre']); ?>
                    </a>
                </h3>
                <div class="flex items-center gap-1 mb-2">
                    <div class="flex text-yellow-400 text-sm">
                        <?php
                        $valoracion_local = floatval($local['valoracion'] ?? 0);
                        for ($estrella = 1; $estrella <= 5; $estrella++):
                        ?>
                        <span><?php echo $estrella <= $valoracion_local ? '★' : '☆'; ?></span>
                        <?php endfor; ?>
                    </div>
                    <span class="text-sm text-gray-500">(<?php echo esc_html($local['total_resenas'] ?? 0); ?>)</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100 text-sm text-gray-500">
                    <span>📍 <?php echo esc_html($local['distancia'] ?? ''); ?></span>
                    <a href="<?php echo esc_url($local['url'] ?? '#'); ?>" class="text-orange-600 hover:text-orange-700 font-medium">
                        Ver mas →
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_locales > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">← Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_locales / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors">Siguiente →</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
