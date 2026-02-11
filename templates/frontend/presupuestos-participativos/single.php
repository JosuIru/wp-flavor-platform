<?php
/**
 * Frontend: Single Proyecto de Presupuesto Participativo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$proyecto = $proyecto ?? [];
$similares = $similares ?? [];
?>

<div class="flavor-frontend flavor-presupuestos-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-amber-600 transition-colors"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a>
        <span>&#8250;</span>
        <a href="<?php echo esc_url(home_url('/presupuestos-participativos/')); ?>" class="hover:text-amber-600 transition-colors"><?php echo esc_html__('Presupuestos', 'flavor-chat-ia'); ?></a>
        <span>&#8250;</span>
        <span class="text-gray-700"><?php echo esc_html($proyecto['titulo'] ?? 'Proyecto'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Header del proyecto -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <?php
                $fase_proyecto = $proyecto['fase'] ?? 'propuestas';
                $colores_fase = [
                    'propuestas'  => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Propuestas'],
                    'evaluacion'  => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'label' => 'Evaluacion'],
                    'votacion'    => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Votacion'],
                    'ejecucion'   => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'label' => 'Ejecucion'],
                    'completado'  => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Completado'],
                ];
                $color_fase = $colores_fase[$fase_proyecto] ?? $colores_fase['propuestas'];
                ?>
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="<?php echo esc_attr($color_fase['bg']); ?> <?php echo esc_attr($color_fase['text']); ?> px-4 py-2 rounded-full font-medium">
                        <?php echo esc_html($color_fase['label']); ?>
                    </span>
                    <span class="bg-amber-100 text-amber-700 px-4 py-2 rounded-full font-bold text-lg">
                        <?php echo esc_html($proyecto['presupuesto'] ?? '0'); ?>
                    </span>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo esc_html($proyecto['titulo'] ?? ''); ?></h1>

                <div class="prose max-w-none text-gray-700">
                    <?php echo wp_kses_post($proyecto['descripcion'] ?? ''); ?>
                </div>
            </div>

            <!-- Evaluacion tecnica -->
            <?php if (!empty($proyecto['evaluacion_tecnica'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo esc_html__('Evaluacion tecnica', 'flavor-chat-ia'); ?></h2>
                <div class="prose max-w-none text-gray-700">
                    <?php echo wp_kses_post($proyecto['evaluacion_tecnica']); ?>
                </div>
                <?php if (!empty($proyecto['viabilidad'])): ?>
                <div class="mt-4 p-4 bg-amber-50 rounded-xl border border-amber-100">
                    <p class="text-sm font-medium text-amber-800">
                        Viabilidad: <?php echo esc_html($proyecto['viabilidad']); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Seccion de votacion -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo esc_html__('Votacion del proyecto', 'flavor-chat-ia'); ?></h2>
                <?php
                $votos_proyecto = intval($proyecto['votos'] ?? 0);
                $umbral_votos = intval($proyecto['umbral'] ?? 100);
                $porcentaje_votos = $umbral_votos > 0 ? min(100, round(($votos_proyecto / $umbral_votos) * 100)) : 0;
                ?>
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="font-medium text-gray-700"><?php echo esc_html($votos_proyecto); ?> votos recibidos</span>
                        <span class="text-gray-500">Umbral: <?php echo esc_html($umbral_votos); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-gradient-to-r from-amber-500 to-yellow-500 h-4 rounded-full transition-all" style="width: <?php echo esc_attr($porcentaje_votos); ?>%"></div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2 text-center"><?php echo esc_html($porcentaje_votos); ?>% del umbral alcanzado</p>
                </div>

                <button class="w-full bg-gradient-to-r from-amber-500 to-yellow-500 text-white py-3 rounded-xl font-semibold hover:from-amber-600 hover:to-yellow-600 transition-all shadow-md"
                        onclick="flavorPresupuestos.votar(<?php echo esc_attr($proyecto['id'] ?? 0); ?>)">
                    <?php echo esc_html__('Votar por este proyecto', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <!-- Cronograma de ejecucion -->
            <?php if (!empty($proyecto['cronograma'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo esc_html__('Cronograma de ejecucion', 'flavor-chat-ia'); ?></h2>
                <div class="space-y-4">
                    <?php foreach ($proyecto['cronograma'] as $etapa_cronograma): ?>
                    <div class="flex items-start gap-4 p-3 rounded-xl <?php echo !empty($etapa_cronograma['completada']) ? 'bg-green-50' : 'bg-gray-50'; ?>">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 <?php echo !empty($etapa_cronograma['completada']) ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600'; ?>">
                            <?php echo !empty($etapa_cronograma['completada']) ? '&#10003;' : esc_html($etapa_cronograma['orden'] ?? ''); ?>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800"><?php echo esc_html($etapa_cronograma['titulo'] ?? ''); ?></p>
                            <p class="text-sm text-gray-500"><?php echo esc_html($etapa_cronograma['fecha'] ?? ''); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- CTA de voto -->
            <div class="bg-gradient-to-br from-amber-500 to-yellow-500 rounded-2xl p-6 text-white">
                <h3 class="font-bold text-lg mb-2"><?php echo esc_html__('Apoya este proyecto', 'flavor-chat-ia'); ?></h3>
                <p class="text-amber-100 text-sm mb-4"><?php echo esc_html__('Tu voto ayuda a que este proyecto se haga realidad', 'flavor-chat-ia'); ?></p>
                <button class="w-full bg-white text-amber-600 py-3 rounded-xl font-semibold hover:bg-amber-50 transition-colors"
                        onclick="flavorPresupuestos.votar(<?php echo esc_attr($proyecto['id'] ?? 0); ?>)">
                    <?php echo esc_html__('Votar ahora', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <!-- Estadisticas del proyecto -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Datos del proyecto', 'flavor-chat-ia'); ?></h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Presupuesto', 'flavor-chat-ia'); ?></dt>
                        <dd class="font-bold text-amber-600"><?php echo esc_html($proyecto['presupuesto'] ?? '0'); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($proyecto['categoria'] ?? ''); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Distrito', 'flavor-chat-ia'); ?></dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($proyecto['distrito'] ?? ''); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Fecha propuesta', 'flavor-chat-ia'); ?></dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($proyecto['fecha'] ?? ''); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Proponente', 'flavor-chat-ia'); ?></dt>
                        <dd class="font-medium text-gray-700"><?php echo esc_html($proyecto['autor'] ?? 'Anonimo'); ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Proyectos similares -->
            <?php if (!empty($similares)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Proyectos similares', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php foreach ($similares as $proyecto_similar): ?>
                    <a href="<?php echo esc_url($proyecto_similar['url'] ?? '#'); ?>"
                       class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <p class="font-medium text-gray-800 text-sm"><?php echo esc_html($proyecto_similar['titulo'] ?? ''); ?></p>
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-500"><?php echo esc_html($proyecto_similar['presupuesto'] ?? ''); ?></span>
                            <span class="text-xs text-amber-600"><?php echo esc_html($proyecto_similar['votos'] ?? 0); ?> votos</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
