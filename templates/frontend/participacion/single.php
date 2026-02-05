<?php
/**
 * Frontend: Single Propuesta de Participacion
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$propuesta = $propuesta ?? [];
$historial_estados = $historial_estados ?? [];
$relacionadas = $relacionadas ?? [];
?>

<div class="flavor-frontend flavor-participacion-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-amber-600 transition-colors">Inicio</a>
        <span>&#8250;</span>
        <a href="<?php echo esc_url(home_url('/participacion/')); ?>" class="hover:text-amber-600 transition-colors">Participacion</a>
        <span>&#8250;</span>
        <span class="text-gray-700"><?php echo esc_html($propuesta['titulo'] ?? 'Propuesta'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Header de la propuesta -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <!-- Estado -->
                <?php
                $estado_propuesta = $propuesta['estado'] ?? 'abierta';
                $colores_estado = [
                    'abierta'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Abierta'],
                    'en-debate' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'En debate'],
                    'votacion'  => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'En votacion'],
                    'aprobada'  => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => 'Aprobada'],
                    'rechazada' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Rechazada'],
                ];
                $color_estado = $colores_estado[$estado_propuesta] ?? $colores_estado['abierta'];
                ?>
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="<?php echo esc_attr($color_estado['bg']); ?> <?php echo esc_attr($color_estado['text']); ?> px-4 py-2 rounded-full font-medium">
                        <?php echo esc_html($color_estado['label']); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-full text-sm">
                        <?php echo esc_html($propuesta['categoria'] ?? 'General'); ?>
                    </span>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo esc_html($propuesta['titulo'] ?? ''); ?></h1>

                <div class="prose max-w-none text-gray-700">
                    <?php echo wp_kses_post($propuesta['descripcion'] ?? ''); ?>
                </div>
            </div>

            <!-- Linea de tiempo de estados -->
            <?php if (!empty($historial_estados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Linea de tiempo</h2>
                <div class="relative pl-8 space-y-6">
                    <div class="absolute left-3 top-2 bottom-2 w-0.5 bg-amber-200"></div>
                    <?php foreach ($historial_estados as $evento_estado): ?>
                    <div class="relative">
                        <div class="absolute -left-5 w-4 h-4 rounded-full bg-amber-500 border-2 border-white shadow"></div>
                        <div>
                            <p class="font-medium text-gray-800"><?php echo esc_html($evento_estado['descripcion'] ?? ''); ?></p>
                            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html($evento_estado['fecha'] ?? ''); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Seccion de votacion -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Resultados de la votacion</h2>
                <?php
                $votos_favor = intval($propuesta['votos_favor'] ?? 0);
                $votos_contra = intval($propuesta['votos_contra'] ?? 0);
                $votos_totales = $votos_favor + $votos_contra;
                $porcentaje_favor = $votos_totales > 0 ? round(($votos_favor / $votos_totales) * 100) : 0;
                $porcentaje_contra = $votos_totales > 0 ? round(($votos_contra / $votos_totales) * 100) : 0;
                ?>
                <div class="space-y-4">
                    <!-- A favor -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-green-700">A favor</span>
                            <span class="text-gray-600"><?php echo esc_html($votos_favor); ?> votos (<?php echo esc_html($porcentaje_favor); ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-green-500 h-4 rounded-full transition-all" style="width: <?php echo esc_attr($porcentaje_favor); ?>%"></div>
                        </div>
                    </div>
                    <!-- En contra -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-red-700">En contra</span>
                            <span class="text-gray-600"><?php echo esc_html($votos_contra); ?> votos (<?php echo esc_html($porcentaje_contra); ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-red-500 h-4 rounded-full transition-all" style="width: <?php echo esc_attr($porcentaje_contra); ?>%"></div>
                        </div>
                    </div>
                    <p class="text-center text-sm text-gray-500 mt-2">Total: <?php echo esc_html($votos_totales); ?> votos emitidos</p>
                </div>

                <!-- Botones de voto -->
                <div class="flex gap-4 mt-6">
                    <button class="flex-1 bg-green-100 text-green-700 py-3 rounded-xl font-semibold hover:bg-green-200 transition-colors"
                            onclick="flavorParticipacion.votar(<?php echo esc_attr($propuesta['id'] ?? 0); ?>, 'favor')">
                        A favor
                    </button>
                    <button class="flex-1 bg-red-100 text-red-700 py-3 rounded-xl font-semibold hover:bg-red-200 transition-colors"
                            onclick="flavorParticipacion.votar(<?php echo esc_attr($propuesta['id'] ?? 0); ?>, 'contra')">
                        En contra
                    </button>
                </div>
            </div>

            <!-- Seccion de comentarios / debate -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Debate (<?php echo esc_html($propuesta['comentarios'] ?? 0); ?>)</h2>
                <form class="space-y-4" onsubmit="return flavorParticipacion.enviarComentario(event, <?php echo esc_attr($propuesta['id'] ?? 0); ?>)">
                    <textarea name="comentario" rows="3" placeholder="Comparte tu opinion sobre esta propuesta..."
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-none"></textarea>
                    <button type="submit" class="bg-amber-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-amber-600 transition-colors">
                        Enviar comentario
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Tarjeta del autor -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Autor de la propuesta</h3>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-amber-200 flex items-center justify-center text-lg font-bold text-amber-700">
                        <?php echo esc_html(mb_substr($propuesta['autor'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800"><?php echo esc_html($propuesta['autor'] ?? 'Anonimo'); ?></p>
                        <p class="text-sm text-gray-500">Publicado el <?php echo esc_html($propuesta['fecha'] ?? ''); ?></p>
                    </div>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Propuestas</dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($propuesta['autor_propuestas'] ?? 0); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Participaciones</dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($propuesta['autor_participaciones'] ?? 0); ?></dd>
                    </div>
                </dl>
            </div>

            <!-- CTA de votacion -->
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white">
                <h3 class="font-bold text-lg mb-2">Tu voto cuenta</h3>
                <p class="text-amber-100 text-sm mb-4">Participa en la toma de decisiones de tu comunidad</p>
                <button class="w-full bg-white text-orange-600 py-3 rounded-xl font-semibold hover:bg-orange-50 transition-colors"
                        onclick="flavorParticipacion.votar(<?php echo esc_attr($propuesta['id'] ?? 0); ?>, 'favor')">
                    Votar esta propuesta
                </button>
            </div>

            <!-- Propuestas relacionadas -->
            <?php if (!empty($relacionadas)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Propuestas relacionadas</h3>
                <div class="space-y-3">
                    <?php foreach ($relacionadas as $propuesta_relacionada): ?>
                    <a href="<?php echo esc_url($propuesta_relacionada['url'] ?? '#'); ?>"
                       class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($propuesta_relacionada['titulo'] ?? ''); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($propuesta_relacionada['votos'] ?? 0); ?> votos</p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
