<?php
/**
 * Template: Guía de Compostaje
 *
 * @package FlavorChatIA
 * @var array $args Parámetros opcionales
 */

if (!defined('ABSPATH')) exit;

// Valores por defecto
$titulo = $args['titulo'] ?? __('Guía Completa de Compostaje', 'flavor-chat-ia');
$subtitulo = $args['subtitulo'] ?? __('Aprende cómo compostar correctamente', 'flavor-chat-ia');
$mostrar_seccion_beneficios = $args['mostrar_seccion_beneficios'] ?? true;
$mostrar_seccion_errores = $args['mostrar_seccion_errores'] ?? true;
?>

<section class="flavor-compostaje-guia flavor-component py-16 bg-white">
    <div class="flavor-container">
        <!-- Encabezado -->
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                🌱 <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-xl text-gray-600 mb-6">
                <?php echo esc_html($subtitulo); ?>
            </p>
            <div class="w-20 h-1 bg-green-500 mx-auto rounded-full"></div>
        </div>

        <!-- Qué se puede compostar -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-8 mb-12">
            <h3 class="text-2xl font-bold text-gray-900 mb-8">
                ✅ <?php echo esc_html__('¿Qué Puedo Compostar?', 'flavor-chat-ia'); ?>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Sí se puede -->
                <div>
                    <h4 class="text-lg font-bold text-green-700 mb-4">
                        ✓ <?php echo esc_html__('SÍ SE PUEDE', 'flavor-chat-ia'); ?>
                    </h4>
                    <div class="space-y-3">
                        <?php
                        $si_pueden = [
                            [
                                'titulo' => __('Residuos Verdes', 'flavor-chat-ia'),
                                'items' => [
                                    __('Hojas secas y verdes', 'flavor-chat-ia'),
                                    __('Ramas pequeñas', 'flavor-chat-ia'),
                                    __('Césped cortado', 'flavor-chat-ia'),
                                    __('Flores marchitas', 'flavor-chat-ia'),
                                ],
                            ],
                            [
                                'titulo' => __('Restos de Comida', 'flavor-chat-ia'),
                                'items' => [
                                    __('Cáscaras de fruta y verdura', 'flavor-chat-ia'),
                                    __('Restos de café y té', 'flavor-chat-ia'),
                                    __('Cáscaras de huevo (trituradas)', 'flavor-chat-ia'),
                                    __('Pan y cereales', 'flavor-chat-ia'),
                                ],
                            ],
                            [
                                'titulo' => __('Papel y Cartón', 'flavor-chat-ia'),
                                'items' => [
                                    __('Papel de periódico', 'flavor-chat-ia'),
                                    __('Cartón sin tinta', 'flavor-chat-ia'),
                                    __('Servilletas usadas', 'flavor-chat-ia'),
                                    __('Papel de cocina', 'flavor-chat-ia'),
                                ],
                            ],
                        ];

                        foreach ($si_pueden as $categoria):
                        ?>
                        <div class="bg-white rounded-lg p-4 border-l-4 border-green-500">
                            <h5 class="font-semibold text-gray-900 mb-2">
                                🟢 <?php echo esc_html($categoria['titulo']); ?>
                            </h5>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <?php foreach ($categoria['items'] as $item): ?>
                                <li class="flex items-start gap-2">
                                    <span class="text-green-500 font-bold mt-0.5">•</span>
                                    <span><?php echo esc_html($item); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- No se puede -->
                <div>
                    <h4 class="text-lg font-bold text-red-700 mb-4">
                        ✗ <?php echo esc_html__('NO SE PUEDE', 'flavor-chat-ia'); ?>
                    </h4>
                    <div class="space-y-3">
                        <?php
                        $no_pueden = [
                            [
                                'titulo' => __('Productos de Origen Animal', 'flavor-chat-ia'),
                                'items' => [
                                    __('Carne y pescado', 'flavor-chat-ia'),
                                    __('Productos lácteos', 'flavor-chat-ia'),
                                    __('Huesos', 'flavor-chat-ia'),
                                    __('Grasas y aceites', 'flavor-chat-ia'),
                                ],
                            ],
                            [
                                'titulo' => __('Materiales Nocivos', 'flavor-chat-ia'),
                                'items' => [
                                    __('Vidrio y plástico', 'flavor-chat-ia'),
                                    __('Metales', 'flavor-chat-ia'),
                                    __('Materiales tratados químicamente', 'flavor-chat-ia'),
                                    __('Colillas de cigarrillos', 'flavor-chat-ia'),
                                ],
                            ],
                            [
                                'titulo' => __('Otros Materiales', 'flavor-chat-ia'),
                                'items' => [
                                    __('Plantas enfermas o con plagas', 'flavor-chat-ia'),
                                    __('Maleza con semillas', 'flavor-chat-ia'),
                                    __('Madera tratada', 'flavor-chat-ia'),
                                    __('Papel brillante o coloreado', 'flavor-chat-ia'),
                                ],
                            ],
                        ];

                        foreach ($no_pueden as $categoria):
                        ?>
                        <div class="bg-white rounded-lg p-4 border-l-4 border-red-500">
                            <h5 class="font-semibold text-gray-900 mb-2">
                                🔴 <?php echo esc_html($categoria['titulo']); ?>
                            </h5>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <?php foreach ($categoria['items'] as $item): ?>
                                <li class="flex items-start gap-2">
                                    <span class="text-red-500 font-bold mt-0.5">•</span>
                                    <span><?php echo esc_html($item); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pasos para compostar -->
        <div class="mb-12">
            <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">
                📋 <?php echo esc_html__('Pasos para Compostar Correctamente', 'flavor-chat-ia'); ?>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php
                $pasos_compostaje = [
                    [
                        'numero' => 1,
                        'titulo' => __('Recoge tus residuos', 'flavor-chat-ia'),
                        'descripcion' => __('Reúne todos los restos de comida y residuos verdes en un contenedor.', 'flavor-chat-ia'),
                        'icono' => '🗑️',
                        'color' => '#3b82f6',
                    ],
                    [
                        'numero' => 2,
                        'titulo' => __('Tritura los restos', 'flavor-chat-ia'),
                        'descripcion' => __('Corta o tritura los residuos en trozos pequeños para acelerar el compostaje.', 'flavor-chat-ia'),
                        'icono' => '✂️',
                        'color' => '#f59e0b',
                    ],
                    [
                        'numero' => 3,
                        'titulo' => __('Mezcla capas', 'flavor-chat-ia'),
                        'descripcion' => __('Alterna capas de "verdes" (húmedos) y "marrones" (secos) en proporción 1:2.', 'flavor-chat-ia'),
                        'icono' => '🔀',
                        'color' => '#10b981',
                    ],
                    [
                        'numero' => 4,
                        'titulo' => __('Mantén la humedad', 'flavor-chat-ia'),
                        'descripcion' => __('El compost debe estar como una esponja exprimida. Riega si es necesario.', 'flavor-chat-ia'),
                        'icono' => '💧',
                        'color' => '#06b6d4',
                    ],
                ];

                foreach ($pasos_compostaje as $paso):
                ?>
                <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl"><?php echo esc_html($paso['icono']); ?></div>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                             style="background-color: <?php echo esc_attr($paso['color']); ?>;">
                            <?php echo (int)$paso['numero']; ?>
                        </div>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">
                        <?php echo esc_html($paso['titulo']); ?>
                    </h4>
                    <p class="text-sm text-gray-600">
                        <?php echo esc_html($paso['descripcion']); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Beneficios -->
        <?php if ($mostrar_seccion_beneficios): ?>
        <div class="bg-blue-50 rounded-2xl p-8 mb-12">
            <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">
                🌍 <?php echo esc_html__('Beneficios del Compostaje', 'flavor-chat-ia'); ?>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg p-6">
                    <div class="text-4xl mb-4">♻️</div>
                    <h4 class="font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Reduce Residuos', 'flavor-chat-ia'); ?>
                    </h4>
                    <p class="text-gray-600 text-sm">
                        <?php echo esc_html__('El compostaje reduce hasta el 30% de los residuos que generamos.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg p-6">
                    <div class="text-4xl mb-4">🌱</div>
                    <h4 class="font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Enriquece el Suelo', 'flavor-chat-ia'); ?>
                    </h4>
                    <p class="text-gray-600 text-sm">
                        <?php echo esc_html__('Obtienes abono natural para plantas y jardines.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg p-6">
                    <div class="text-4xl mb-4">🌎</div>
                    <h4 class="font-bold text-gray-900 mb-2">
                        <?php echo esc_html__('Cuida el Planeta', 'flavor-chat-ia'); ?>
                    </h4>
                    <p class="text-gray-600 text-sm">
                        <?php echo esc_html__('Reducimos emisiones de metano y protegemos el medio ambiente.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Errores comunes -->
        <?php if ($mostrar_seccion_errores): ?>
        <div class="mb-12">
            <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">
                ⚠️ <?php echo esc_html__('Errores Comunes a Evitar', 'flavor-chat-ia'); ?>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php
                $errores = [
                    [
                        'error' => __('Compactar demasiado', 'flavor-chat-ia'),
                        'consejos' => __('El compost necesita aire. Mezcla frecuentemente en lugar de compactar.', 'flavor-chat-ia'),
                    ],
                    [
                        'error' => __('Demasiada humedad', 'flavor-chat-ia'),
                        'consejos' => __('Esto causa malos olores. Añade más materiales secos ("marrones").', 'flavor-chat-ia'),
                    ],
                    [
                        'error' => __('Demasiada sequedad', 'flavor-chat-ia'),
                        'consejos' => __('El proceso se ralentiza. Riega o añade residuos verdes.', 'flavor-chat-ia'),
                    ],
                    [
                        'error' => __('Malas proporciones', 'flavor-chat-ia'),
                        'consejos' => __('Mantén una relación 1:2 de verdes a marrones para óptimos resultados.', 'flavor-chat-ia'),
                    ],
                ];

                foreach ($errores as $error):
                ?>
                <div class="bg-red-50 rounded-lg p-6 border border-red-200">
                    <h4 class="font-bold text-red-700 mb-2 flex items-center gap-2">
                        ❌ <?php echo esc_html($error['error']); ?>
                    </h4>
                    <p class="text-gray-600 text-sm">
                        <?php echo esc_html($error['consejos']); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-12 text-center text-white">
            <h3 class="text-3xl font-bold mb-4">
                🌿 <?php echo esc_html__('¿Listo para Compostar?', 'flavor-chat-ia'); ?>
            </h3>
            <p class="text-lg mb-6 opacity-90">
                <?php echo esc_html__('Encuentra el punto de compostaje más cercano y comienza hoy mismo.', 'flavor-chat-ia'); ?>
            </p>
            <a href="#mapa-compostaje" class="inline-block bg-white text-green-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition-colors">
                🗺️ <?php echo esc_html__('Ir al Mapa de Puntos', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</section>

<style>
.flavor-compostaje-guia {
    background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);
}
</style>
