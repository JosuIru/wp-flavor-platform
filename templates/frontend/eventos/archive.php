<?php
/**
 * Frontend: Archive de Eventos
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$eventos = $eventos ?? [];
$total_eventos = $total_eventos ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
$vista_activa = $vista_activa ?? 'lista';
?>

<div class="flavor-frontend flavor-eventos-archive">
    <!-- Header con gradiente rosa -->
    <div class="bg-gradient-to-r from-rose-500 to-pink-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">🎉 Eventos</h1>
                <p class="text-rose-100">Descubre y participa en los eventos de tu comunidad</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_eventos); ?> eventos activos
                </span>
                <button class="bg-white text-pink-600 px-6 py-3 rounded-xl font-semibold hover:bg-rose-50 transition-all shadow-md"
                        onclick="flavorEventos.crearEvento()">
                    ➕ Crear Evento
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">📅</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['eventos_activos'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Eventos activos</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">👥</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['asistentes'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Asistentes</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🎤</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['organizadores'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Organizadores</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🗓️</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['este_mes'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Este mes</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-rose-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">💡 ¿Como funciona?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-rose-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🔍</div>
                <h3 class="font-semibold text-gray-800 mb-1">Descubre</h3>
                <p class="text-sm text-gray-600">Encuentra eventos cerca de ti por categoria y fecha</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-rose-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">✅</div>
                <h3 class="font-semibold text-gray-800 mb-1">Inscribete</h3>
                <p class="text-sm text-gray-600">Reserva tu plaza facilmente con un solo clic</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-rose-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🎊</div>
                <h3 class="font-semibold text-gray-800 mb-1">Participa</h3>
                <p class="text-sm text-gray-600">Disfruta del evento y comparte tu experiencia</p>
            </div>
        </div>
    </div>

    <!-- Toggle vista y filtros -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex flex-wrap gap-2">
            <button class="px-4 py-2 rounded-full bg-rose-100 text-rose-700 font-medium hover:bg-rose-200 transition-colors filter-active" data-categoria="todos">
                Todos
            </button>
            <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="musica">
                🎵 Musica
            </button>
            <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="deporte">
                ⚽ Deporte
            </button>
            <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="cultura">
                🎭 Cultura
            </button>
            <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="infantil">
                👶 Infantil
            </button>
            <?php foreach ($categorias as $categoria_evento): ?>
            <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="<?php echo esc_attr($categoria_evento['slug']); ?>">
                <?php echo esc_html($categoria_evento['icono'] ?? ''); ?> <?php echo esc_html($categoria_evento['nombre']); ?>
            </button>
            <?php endforeach; ?>
        </div>
        <div class="flex items-center gap-2 ml-4">
            <button class="p-2 rounded-lg <?php echo $vista_activa === 'lista' ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-500'; ?> hover:bg-rose-100 transition-colors" data-vista="lista" title="Vista lista">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <button class="p-2 rounded-lg <?php echo $vista_activa === 'calendario' ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-500'; ?> hover:bg-rose-100 transition-colors" data-vista="calendario" title="Vista calendario">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </button>
        </div>
    </div>

    <!-- Grid de eventos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($eventos)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">🎉</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay eventos programados</h3>
            <p class="text-gray-500 mb-6">¡Crea el primer evento de la comunidad!</p>
            <button class="bg-rose-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-rose-600 transition-colors"
                    onclick="flavorEventos.crearEvento()">
                Crear Evento
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($eventos as $evento): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <!-- Fecha destacada -->
                <div class="flex items-start gap-4 mb-4">
                    <div class="bg-rose-100 text-rose-700 rounded-xl p-3 text-center min-w-[60px] flex-shrink-0">
                        <p class="text-xs font-medium uppercase"><?php echo esc_html($evento['mes'] ?? 'Ene'); ?></p>
                        <p class="text-2xl font-bold leading-none"><?php echo esc_html($evento['dia'] ?? '01'); ?></p>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-pink-600 transition-colors">
                            <a href="<?php echo esc_url($evento['url'] ?? '#'); ?>">
                                <?php echo esc_html($evento['titulo']); ?>
                            </a>
                        </h3>
                        <p class="text-gray-600 text-sm line-clamp-2"><?php echo esc_html($evento['descripcion'] ?? ''); ?></p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        📍 <?php echo esc_html($evento['ubicacion'] ?? 'Por confirmar'); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        🕐 <?php echo esc_html($evento['hora'] ?? ''); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        👥 <?php echo esc_html($evento['asistentes'] ?? 0); ?> asistentes
                    </span>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <span class="<?php echo ($evento['precio'] ?? 0) == 0 ? 'bg-green-100 text-green-700' : 'bg-rose-100 text-rose-700'; ?> text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo ($evento['precio'] ?? 0) == 0 ? 'Gratis' : esc_html($evento['precio']) . ' €'; ?>
                    </span>
                    <a href="<?php echo esc_url($evento['url'] ?? '#'); ?>"
                       class="bg-rose-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-rose-600 transition-colors">
                        Inscribirse
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_eventos > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">← Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_eventos / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-rose-500 text-white hover:bg-rose-600 transition-colors">Siguiente →</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
