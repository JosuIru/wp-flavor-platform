<?php
/**
 * Frontend: Archive de Chat Grupos
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$grupos = $grupos ?? [];
$mis_grupos = $mis_grupos ?? [];
$total_grupos = $total_grupos ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias_grupo = $categorias_grupo ?? [];
?>

<div class="flavor-frontend flavor-chat-grupos-archive">
    <!-- Header con gradiente violeta -->
    <div class="bg-gradient-to-r from-violet-500 to-purple-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">💬 Chat de Grupos</h1>
                <p class="text-violet-100">Conecta con tu comunidad en canales de conversacion</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_grupos); ?> grupos activos
                </span>
                <button class="bg-white text-purple-600 px-6 py-3 rounded-xl font-semibold hover:bg-violet-50 transition-all shadow-md"
                        onclick="flavorChatGrupos.crearGrupo()">
                    ➕ Crear Grupo
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">💬</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['grupos_activos'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Grupos activos</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">👥</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['miembros_totales'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Miembros totales</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">📨</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['mensajes_hoy'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Mensajes hoy</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mis grupos -->
    <?php if (!empty($mis_grupos)): ?>
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">⭐ Mis grupos</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($mis_grupos as $mi_grupo): ?>
            <a href="<?php echo esc_url($mi_grupo['url'] ?? '#'); ?>" class="bg-violet-50 border border-violet-200 rounded-2xl p-4 hover:bg-violet-100 transition-colors group">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-violet-500 text-white flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <?php echo esc_html(mb_substr($mi_grupo['nombre'] ?? 'G', 0, 1)); ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-semibold text-gray-800 truncate group-hover:text-purple-600 transition-colors">
                            <?php echo esc_html($mi_grupo['nombre']); ?>
                        </h3>
                        <p class="text-sm text-gray-500 truncate"><?php echo esc_html($mi_grupo['ultimo_mensaje'] ?? 'Sin mensajes'); ?></p>
                    </div>
                    <?php if (!empty($mi_grupo['no_leidos'])): ?>
                    <span class="bg-violet-500 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0">
                        <?php echo esc_html($mi_grupo['no_leidos']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-violet-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">💡 ¿Como funciona?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-violet-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🔍</div>
                <h3 class="font-semibold text-gray-800 mb-1">Explora</h3>
                <p class="text-sm text-gray-600">Descubre grupos publicos sobre temas que te interesan</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-violet-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">✅</div>
                <h3 class="font-semibold text-gray-800 mb-1">Unete</h3>
                <p class="text-sm text-gray-600">Solicita acceso o unete directamente a grupos abiertos</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-violet-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">💬</div>
                <h3 class="font-semibold text-gray-800 mb-1">Conversa</h3>
                <p class="text-sm text-gray-600">Participa en las conversaciones y conecta con tu comunidad</p>
            </div>
        </div>
    </div>

    <!-- Filtros por categoria -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-violet-100 text-violet-700 font-medium hover:bg-violet-200 transition-colors filter-active" data-categoria="todos">
            Todos
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="vecinos">
            🏘️ Vecinos
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="deportes">
            ⚽ Deportes
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="ocio">
            🎮 Ocio
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="padres">
            👨‍👩‍👧 Padres
        </button>
        <?php foreach ($categorias_grupo as $categoria_grupo_item): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="<?php echo esc_attr($categoria_grupo_item['slug']); ?>">
            <?php echo esc_html($categoria_grupo_item['icono'] ?? ''); ?> <?php echo esc_html($categoria_grupo_item['nombre']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de grupos publicos -->
    <h2 class="text-xl font-bold text-gray-800 mb-4">🌐 Grupos publicos</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($grupos)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">💬</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay grupos disponibles</h3>
            <p class="text-gray-500 mb-6">¡Crea el primer grupo de la comunidad!</p>
            <button class="bg-violet-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-violet-600 transition-colors"
                    onclick="flavorChatGrupos.crearGrupo()">
                Crear Grupo
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($grupos as $grupo): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-14 h-14 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center text-2xl font-bold flex-shrink-0">
                        <?php echo esc_html(mb_substr($grupo['nombre'] ?? 'G', 0, 1)); ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-purple-600 transition-colors">
                            <a href="<?php echo esc_url($grupo['url'] ?? '#'); ?>">
                                <?php echo esc_html($grupo['nombre']); ?>
                            </a>
                        </h3>
                        <p class="text-gray-600 text-sm line-clamp-2"><?php echo esc_html($grupo['descripcion'] ?? ''); ?></p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        👥 <?php echo esc_html($grupo['miembros'] ?? 0); ?> miembros
                    </span>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        📨 <?php echo esc_html($grupo['ultimo_mensaje_hora'] ?? ''); ?>
                    </span>
                    <span class="<?php echo ($grupo['tipo'] ?? 'publico') === 'publico' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'; ?> text-xs px-3 py-1 rounded-full">
                        <?php echo ($grupo['tipo'] ?? 'publico') === 'publico' ? '🌐 Publico' : '🔒 Privado'; ?>
                    </span>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <p class="text-xs text-gray-500 truncate flex-1"><?php echo esc_html($grupo['ultimo_mensaje'] ?? 'Sin mensajes aun'); ?></p>
                    <a href="<?php echo esc_url($grupo['url'] ?? '#'); ?>"
                       class="bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-violet-600 transition-colors ml-3 flex-shrink-0">
                        Unirse
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_grupos > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">← Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_grupos / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-violet-500 text-white hover:bg-violet-600 transition-colors">Siguiente →</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
