<?php
/**
 * Frontend: Archive de Talleres
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$talleres = $talleres ?? [];
$total_talleres = $total_talleres ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-talleres-archive">
    <!-- Header con gradiente morado -->
    <div class="bg-gradient-to-r from-purple-500 to-violet-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">🎨 Talleres</h1>
                <p class="text-purple-100">Aprende nuevas habilidades con talleres de tu comunidad</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_talleres); ?> talleres disponibles
                </span>
                <button class="bg-white text-violet-600 px-6 py-3 rounded-xl font-semibold hover:bg-purple-50 transition-all shadow-md"
                        onclick="flavorTalleres.crearTaller()">
                    ➕ Crear Taller
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">📚</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['talleres_disponibles'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Talleres disponibles</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">💺</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['plazas'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Plazas</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">👨‍🏫</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['instructores'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Instructores</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">⭐</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['valoracion_media'] ?? '4.8'); ?></p>
            <p class="text-sm text-gray-500">Valoracion media</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-purple-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">💡 ¿Como funciona?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🔍</div>
                <h3 class="font-semibold text-gray-800 mb-1">Explora</h3>
                <p class="text-sm text-gray-600">Busca talleres por categoria, nivel y horario</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">📝</div>
                <h3 class="font-semibold text-gray-800 mb-1">Reserva</h3>
                <p class="text-sm text-gray-600">Inscribete facilmente y asegura tu plaza</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🎓</div>
                <h3 class="font-semibold text-gray-800 mb-1">Aprende</h3>
                <p class="text-sm text-gray-600">Disfruta aprendiendo y valora la experiencia</p>
            </div>
        </div>
    </div>

    <!-- Filtros por categoria -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-purple-100 text-purple-700 font-medium hover:bg-purple-200 transition-colors filter-active" data-categoria="todos">
            Todos
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="arte">
            🎨 Arte
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="cocina">
            🍳 Cocina
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="tecnologia">
            💻 Tecnologia
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="manualidades">
            ✂️ Manualidades
        </button>
        <?php foreach ($categorias as $categoria_taller): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="<?php echo esc_attr($categoria_taller['slug']); ?>">
            <?php echo esc_html($categoria_taller['icono'] ?? ''); ?> <?php echo esc_html($categoria_taller['nombre']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de talleres -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($talleres)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">🎨</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay talleres disponibles</h3>
            <p class="text-gray-500 mb-6">¿Tienes algo que ensenar? ¡Crea un taller!</p>
            <button class="bg-purple-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-purple-600 transition-colors"
                    onclick="flavorTalleres.crearTaller()">
                Crear Taller
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($talleres as $taller): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="aspect-video bg-gray-100 relative overflow-hidden">
                <?php if (!empty($taller['imagen'])): ?>
                <img src="<?php echo esc_url($taller['imagen']); ?>" alt="<?php echo esc_attr($taller['titulo']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <span class="text-5xl">🎨</span>
                </div>
                <?php endif; ?>
                <span class="absolute top-3 right-3 <?php echo ($taller['nivel'] ?? '') === 'Principiante' ? 'bg-green-500' : (($taller['nivel'] ?? '') === 'Intermedio' ? 'bg-amber-500' : 'bg-red-500'); ?> text-white text-xs font-medium px-3 py-1 rounded-full shadow">
                    <?php echo esc_html($taller['nivel'] ?? 'Todos'); ?>
                </span>
            </div>
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-violet-600 transition-colors">
                    <a href="<?php echo esc_url($taller['url'] ?? '#'); ?>">
                        <?php echo esc_html($taller['titulo']); ?>
                    </a>
                </h3>

                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 text-xs font-medium">
                        <?php echo esc_html(mb_substr($taller['instructor_nombre'] ?? 'I', 0, 1)); ?>
                    </div>
                    <span class="text-sm text-gray-600"><?php echo esc_html($taller['instructor_nombre'] ?? 'Instructor'); ?></span>
                </div>

                <div class="flex flex-wrap gap-2 mb-3 text-xs text-gray-500">
                    <span class="bg-gray-100 px-2 py-1 rounded-full flex items-center gap-1">
                        📅 <?php echo esc_html($taller['fecha'] ?? ''); ?>
                    </span>
                    <span class="bg-gray-100 px-2 py-1 rounded-full flex items-center gap-1">
                        ⏱️ <?php echo esc_html($taller['duracion'] ?? ''); ?>
                    </span>
                    <span class="bg-gray-100 px-2 py-1 rounded-full flex items-center gap-1">
                        💺 <?php echo esc_html($taller['plazas_disponibles'] ?? 0); ?> plazas
                    </span>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <span class="text-lg font-bold <?php echo ($taller['precio'] ?? 0) == 0 ? 'text-green-600' : 'text-violet-600'; ?>">
                        <?php echo ($taller['precio'] ?? 0) == 0 ? 'Gratis' : esc_html($taller['precio']) . ' €'; ?>
                    </span>
                    <a href="<?php echo esc_url($taller['url'] ?? '#'); ?>"
                       class="bg-purple-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-600 transition-colors">
                        Inscribirse
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_talleres > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">← Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_talleres / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-purple-500 text-white hover:bg-purple-600 transition-colors">Siguiente →</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
