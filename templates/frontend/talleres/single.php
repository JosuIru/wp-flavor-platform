<?php
/**
 * Frontend: Single Taller
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$taller = $taller ?? [];
$instructor = $instructor ?? [];
$resenas_taller = $resenas_taller ?? [];
$talleres_relacionados = $talleres_relacionados ?? [];
?>

<div class="flavor-frontend flavor-talleres-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/talleres/')); ?>" class="hover:text-violet-600 transition-colors">Talleres</a>
        <span>›</span>
        <?php if (!empty($taller['categoria'])): ?>
        <a href="<?php echo esc_url(home_url('/talleres/?cat=' . ($taller['categoria_slug'] ?? ''))); ?>" class="hover:text-violet-600 transition-colors">
            <?php echo esc_html($taller['categoria']); ?>
        </a>
        <span>›</span>
        <?php endif; ?>
        <span class="text-gray-700"><?php echo esc_html($taller['titulo'] ?? 'Taller'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Imagen del taller -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="aspect-video bg-gray-100 relative">
                    <?php if (!empty($taller['imagen'])): ?>
                    <img src="<?php echo esc_url($taller['imagen']); ?>" alt="<?php echo esc_attr($taller['titulo'] ?? ''); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full bg-gradient-to-br from-purple-400 to-violet-600 flex items-center justify-center">
                        <span class="text-6xl">🎨</span>
                    </div>
                    <?php endif; ?>
                    <span class="absolute top-4 right-4 <?php echo ($taller['nivel'] ?? '') === 'Principiante' ? 'bg-green-500' : (($taller['nivel'] ?? '') === 'Intermedio' ? 'bg-amber-500' : 'bg-red-500'); ?> text-white px-4 py-2 rounded-full font-medium shadow">
                        <?php echo esc_html($taller['nivel'] ?? 'Todos los niveles'); ?>
                    </span>
                </div>
            </div>

            <!-- Informacion del taller -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="bg-purple-100 text-purple-700 px-4 py-2 rounded-full font-medium text-sm">
                        <?php echo esc_html($taller['categoria'] ?? 'General'); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                        ⏱️ <?php echo esc_html($taller['duracion'] ?? ''); ?>
                    </span>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($taller['titulo'] ?? ''); ?>
                </h1>

                <!-- Info rapida -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-purple-50 rounded-xl">
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Fecha</p>
                        <p class="font-medium text-gray-800"><?php echo esc_html($taller['fecha'] ?? ''); ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Horario</p>
                        <p class="font-medium text-gray-800"><?php echo esc_html($taller['horario'] ?? ''); ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Duracion</p>
                        <p class="font-medium text-gray-800"><?php echo esc_html($taller['duracion'] ?? ''); ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Plazas</p>
                        <p class="font-medium text-gray-800"><?php echo esc_html($taller['plazas_disponibles'] ?? 0); ?> libres</p>
                    </div>
                </div>

                <div class="prose prose-purple max-w-none mb-6">
                    <?php echo wp_kses_post($taller['descripcion'] ?? ''); ?>
                </div>

                <!-- Programa / Contenido -->
                <?php if (!empty($taller['programa'])): ?>
                <div class="border-t border-gray-100 pt-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">📋 Programa del taller</h2>
                    <ol class="space-y-3">
                        <?php foreach ($taller['programa'] as $indice_programa => $punto_programa): ?>
                        <li class="flex items-start gap-3">
                            <span class="bg-purple-100 text-purple-700 w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">
                                <?php echo esc_html($indice_programa + 1); ?>
                            </span>
                            <span class="text-gray-700"><?php echo esc_html($punto_programa); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                <?php endif; ?>
            </div>

            <!-- Materiales necesarios -->
            <?php if (!empty($taller['materiales'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">🧰 Materiales necesarios</h2>
                <ul class="space-y-2">
                    <?php foreach ($taller['materiales'] as $material_necesario): ?>
                    <li class="flex items-center gap-2 text-gray-700">
                        <span class="text-purple-500">✓</span>
                        <?php echo esc_html($material_necesario); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (!empty($taller['materiales_incluidos'])): ?>
                <p class="mt-4 text-sm text-green-600 bg-green-50 px-4 py-2 rounded-lg">
                    ✓ Materiales incluidos en el precio
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Resenas -->
            <?php if (!empty($resenas_taller)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">⭐ Resenas de alumnos</h2>
                <div class="space-y-4">
                    <?php foreach ($resenas_taller as $resena_alumno): ?>
                    <div class="border-b border-gray-100 pb-4 last:border-0">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex text-yellow-400">
                                <?php for ($indice_val = 0; $indice_val < 5; $indice_val++): ?>
                                <span><?php echo $indice_val < ($resena_alumno['puntuacion'] ?? 0) ? '★' : '☆'; ?></span>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm text-gray-500"><?php echo esc_html($resena_alumno['fecha'] ?? ''); ?></span>
                        </div>
                        <p class="text-gray-600 text-sm"><?php echo esc_html($resena_alumno['comentario'] ?? ''); ?></p>
                        <p class="text-xs text-gray-400 mt-1">— <?php echo esc_html($resena_alumno['autor'] ?? ''); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- CTA Inscripcion -->
            <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl p-6 text-white">
                <div class="text-center mb-4">
                    <p class="text-3xl font-bold">
                        <?php echo ($taller['precio'] ?? 0) == 0 ? 'Gratis' : esc_html($taller['precio']) . ' €'; ?>
                    </p>
                    <?php if (!empty($taller['plazas_disponibles'])): ?>
                    <p class="text-purple-100 text-sm mt-1">Quedan <?php echo esc_html($taller['plazas_disponibles']); ?> plazas disponibles</p>
                    <?php endif; ?>
                </div>
                <button class="w-full bg-white text-violet-600 py-3 px-4 rounded-xl font-semibold hover:bg-purple-50 transition-colors"
                        onclick="flavorTalleres.inscribirse(<?php echo esc_attr($taller['id'] ?? 0); ?>)">
                    🎓 Inscribirse al taller
                </button>
            </div>

            <!-- Instructor -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 text-3xl font-bold mx-auto mb-4">
                    <?php echo esc_html(mb_substr($instructor['nombre'] ?? 'I', 0, 1)); ?>
                </div>
                <p class="text-xs text-gray-500 mb-1">Instructor</p>
                <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($instructor['nombre'] ?? 'Instructor'); ?></h3>

                <?php if (!empty($instructor['bio'])): ?>
                <p class="text-sm text-gray-600 mt-3 mb-4"><?php echo esc_html($instructor['bio']); ?></p>
                <?php endif; ?>

                <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                    <div>
                        <p class="text-xl font-bold text-purple-600"><?php echo esc_html($instructor['talleres_impartidos'] ?? 0); ?></p>
                        <p class="text-xs text-gray-500">Talleres</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($instructor['alumnos'] ?? 0); ?></p>
                        <p class="text-xs text-gray-500">Alumnos</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-yellow-500">⭐ <?php echo esc_html($instructor['valoracion'] ?? '5.0'); ?></p>
                        <p class="text-xs text-gray-500">Valoracion</p>
                    </div>
                </div>

                <?php if (!empty($instructor['verificado'])): ?>
                <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full">
                    ✓ Instructor verificado
                </span>
                <?php endif; ?>
            </div>

            <!-- Talleres relacionados -->
            <?php if (!empty($talleres_relacionados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Talleres similares</h3>
                <div class="space-y-3">
                    <?php foreach ($talleres_relacionados as $taller_similar): ?>
                    <a href="<?php echo esc_url($taller_similar['url'] ?? '#'); ?>" class="flex gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="w-14 h-14 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                            <?php if (!empty($taller_similar['imagen'])): ?>
                            <img src="<?php echo esc_url($taller_similar['imagen']); ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400">🎨</div>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 text-sm truncate"><?php echo esc_html($taller_similar['titulo'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($taller_similar['instructor_nombre'] ?? ''); ?></p>
                            <p class="text-xs text-violet-600 font-medium">
                                <?php echo ($taller_similar['precio'] ?? 0) == 0 ? 'Gratis' : esc_html($taller_similar['precio']) . ' €'; ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
