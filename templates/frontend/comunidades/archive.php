<?php
/**
 * Frontend: Archive Comunidades
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$comunidades = $comunidades ?? [];
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-comunidades-archive">
    <!-- Header -->
    <div class="bg-gradient-to-r from-rose-500 to-pink-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">🏘️ Comunidades</h1>
                <p class="text-rose-100">Encuentra tu comunidad y conecta con tus vecinos</p>
            </div>
            <button class="bg-white text-rose-600 px-6 py-3 rounded-xl font-semibold hover:bg-rose-50 transition-all shadow-md"
                    onclick="flavorComunidades.crearComunidad()">
                ➕ Crear comunidad
            </button>
        </div>
    </div>

    <!-- Estadísticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl font-bold text-rose-600"><?php echo esc_html($estadisticas['total_comunidades'] ?? 0); ?></div>
            <div class="text-gray-600 text-sm">Comunidades</div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl font-bold text-pink-600"><?php echo esc_html($estadisticas['total_miembros'] ?? 0); ?></div>
            <div class="text-gray-600 text-sm">Miembros</div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl font-bold text-fuchsia-600"><?php echo esc_html($estadisticas['eventos_mes'] ?? 0); ?></div>
            <div class="text-gray-600 text-sm">Eventos este mes</div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl font-bold text-purple-600"><?php echo esc_html($estadisticas['publicaciones_semana'] ?? 0); ?></div>
            <div class="text-gray-600 text-sm">Publicaciones/semana</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros rápidos por tipo -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="?tipo=" class="px-4 py-2 rounded-full text-sm font-medium <?php echo empty($_GET['tipo']) ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition-colors">
            Todas
        </a>
        <?php
        $tipos_comunidad = [
            'vecinal' => '🏠 Vecinal',
            'interes' => '💡 Interés común',
            'deportiva' => '⚽ Deportiva',
            'cultural' => '🎭 Cultural',
            'solidaria' => '🤝 Solidaria',
        ];
        foreach ($tipos_comunidad as $tipo_slug => $tipo_nombre):
        ?>
        <a href="?tipo=<?php echo esc_attr($tipo_slug); ?>"
           class="px-4 py-2 rounded-full text-sm font-medium <?php echo ($_GET['tipo'] ?? '') === $tipo_slug ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition-colors">
            <?php echo esc_html($tipo_nombre); ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Listado de comunidades -->
    <?php if (empty($comunidades)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">🏘️</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay comunidades todavía</h3>
        <p class="text-gray-500 mb-6">Sé el primero en crear una comunidad en tu zona</p>
        <button class="bg-rose-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-rose-600 transition-colors"
                onclick="flavorComunidades.crearComunidad()">
            Crear comunidad
        </button>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($comunidades as $comunidad): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <?php if (!empty($comunidad['imagen'])): ?>
            <div class="h-40 overflow-hidden">
                <img src="<?php echo esc_url($comunidad['imagen']); ?>" alt=""
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            </div>
            <?php else: ?>
            <div class="h-40 bg-gradient-to-br from-rose-100 to-pink-100 flex items-center justify-center">
                <span class="text-6xl">🏘️</span>
            </div>
            <?php endif; ?>

            <div class="p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="bg-rose-100 text-rose-700 text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html($comunidad['tipo'] ?? 'Vecinal'); ?>
                    </span>
                    <?php if (!empty($comunidad['verificada'])): ?>
                    <span class="text-green-500" title="Comunidad verificada">✓</span>
                    <?php endif; ?>
                </div>

                <h3 class="text-lg font-bold text-gray-800 mb-2">
                    <a href="<?php echo esc_url($comunidad['url']); ?>" class="hover:text-rose-600 transition-colors">
                        <?php echo esc_html($comunidad['nombre']); ?>
                    </a>
                </h3>

                <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo esc_html($comunidad['descripcion']); ?></p>

                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-4 text-gray-500">
                        <span>👥 <?php echo esc_html($comunidad['miembros'] ?? 0); ?></span>
                        <span>📍 <?php echo esc_html($comunidad['ubicacion'] ?? 'Local'); ?></span>
                    </div>
                    <?php if (!empty($comunidad['activa'])): ?>
                    <span class="w-2 h-2 bg-green-500 rounded-full" title="Comunidad activa"></span>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Comunidades destacadas -->
    <?php if (!empty($comunidades_destacadas)): ?>
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">⭐ Comunidades destacadas</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($comunidades_destacadas as $destacada): ?>
            <div class="bg-gradient-to-r from-rose-50 to-pink-50 rounded-2xl p-6 border border-rose-100">
                <div class="flex gap-4">
                    <div class="w-16 h-16 bg-rose-200 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">
                        <?php echo esc_html($destacada['emoji'] ?? '🏘️'); ?>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 mb-1">
                            <a href="<?php echo esc_url($destacada['url']); ?>" class="hover:text-rose-600">
                                <?php echo esc_html($destacada['nombre']); ?>
                            </a>
                        </h3>
                        <p class="text-gray-600 text-sm mb-2"><?php echo esc_html($destacada['descripcion']); ?></p>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span>👥 <?php echo esc_html($destacada['miembros']); ?> miembros</span>
                            <span>📅 <?php echo esc_html($destacada['eventos_proximos'] ?? 0); ?> eventos próximos</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
