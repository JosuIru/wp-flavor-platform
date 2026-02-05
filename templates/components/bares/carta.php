<?php
/**
 * Template: Carta / Menu de un Bar o Restaurante
 *
 * Muestra la carta completa agrupada por categorias con precios,
 * descripciones y alergenos.
 *
 * @var string $titulo_seccion
 * @var bool   $mostrar_precios
 * @var bool   $mostrar_alergenos
 * @var string $component_classes
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$mostrar_columna_precios   = !isset($mostrar_precios) || $mostrar_precios;
$mostrar_info_alergenos    = !isset($mostrar_alergenos) || $mostrar_alergenos;

// Datos de ejemplo (fallback) con carta realista
$categorias_carta_ejemplo = [
    'Tapas y Pinchos' => [
        [
            'nombre'      => 'Pintxo de tortilla',
            'descripcion' => 'Tortilla de patata casera con cebolla caramelizada sobre pan de cristal',
            'precio'      => 3.50,
            'alergenos'   => ['gluten', 'huevo', 'lacteos'],
            'es_destacado'=> true,
        ],
        [
            'nombre'      => 'Croquetas de jamon iberico',
            'descripcion' => 'Croquetas cremosas de jamon iberico de bellota (6 unidades)',
            'precio'      => 8.90,
            'alergenos'   => ['gluten', 'lacteos', 'huevo'],
            'es_destacado'=> true,
        ],
        [
            'nombre'      => 'Gildas',
            'descripcion' => 'Pincho clasico de aceituna, guindilla y anchoa del Cantabrico',
            'precio'      => 2.80,
            'alergenos'   => ['pescado'],
            'es_destacado'=> false,
        ],
        [
            'nombre'      => 'Patatas bravas',
            'descripcion' => 'Patatas fritas crujientes con salsa brava casera y alioli',
            'precio'      => 6.50,
            'alergenos'   => ['huevo'],
            'es_destacado'=> false,
        ],
    ],
    'Raciones' => [
        [
            'nombre'      => 'Pulpo a la gallega',
            'descripcion' => 'Pulpo cocido con patata, pimenton de la Vera y aceite de oliva virgen extra',
            'precio'      => 16.90,
            'alergenos'   => ['moluscos'],
            'es_destacado'=> true,
        ],
        [
            'nombre'      => 'Tabla de quesos artesanos',
            'descripcion' => 'Seleccion de 5 quesos locales con membrillo, nueces y miel',
            'precio'      => 14.50,
            'alergenos'   => ['lacteos', 'frutos_secos'],
            'es_destacado'=> false,
        ],
        [
            'nombre'      => 'Calamares a la romana',
            'descripcion' => 'Calamares frescos rebozados con limon y salsa tartara casera',
            'precio'      => 12.90,
            'alergenos'   => ['gluten', 'huevo', 'moluscos'],
            'es_destacado'=> false,
        ],
    ],
    'Carnes' => [
        [
            'nombre'      => 'Txuleton de vaca vieja',
            'descripcion' => 'Chuleton de vaca vieja de caserio vasco a la brasa (min. 800g)',
            'precio'      => 38.00,
            'alergenos'   => [],
            'es_destacado'=> true,
        ],
        [
            'nombre'      => 'Secreto iberico',
            'descripcion' => 'Secreto de cerdo iberico a la plancha con pimientos de padron',
            'precio'      => 16.50,
            'alergenos'   => [],
            'es_destacado'=> false,
        ],
    ],
    'Bebidas' => [
        [
            'nombre'      => 'Cerveza artesanal (cana)',
            'descripcion' => 'Cerveza artesanal del dia - pregunta al camarero por las variedades disponibles',
            'precio'      => 3.00,
            'alergenos'   => ['gluten'],
            'es_destacado'=> false,
        ],
        [
            'nombre'      => 'Txakoli D.O.',
            'descripcion' => 'Txakoli Getariako Txakolina de la ultima anada, fresco y afrutado',
            'precio'      => 4.50,
            'alergenos'   => ['sulfitos'],
            'es_destacado'=> false,
        ],
        [
            'nombre'      => 'Vermut rojo de grifo',
            'descripcion' => 'Vermut artesanal servido con aceituna y naranja',
            'precio'      => 3.50,
            'alergenos'   => ['sulfitos'],
            'es_destacado'=> false,
        ],
    ],
    'Postres' => [
        [
            'nombre'      => 'Tarta de queso vasca',
            'descripcion' => 'La autentica tarta de queso al estilo de San Sebastian, cremosa por dentro',
            'precio'      => 6.90,
            'alergenos'   => ['lacteos', 'huevo', 'gluten'],
            'es_destacado'=> true,
        ],
        [
            'nombre'      => 'Pantxineta',
            'descripcion' => 'Hojaldre relleno de crema pastelera cubierto con almendras laminadas',
            'precio'      => 5.50,
            'alergenos'   => ['gluten', 'lacteos', 'huevo', 'frutos_secos'],
            'es_destacado'=> false,
        ],
    ],
];

// Iconos y etiquetas de alergenos
$mapa_alergenos = [
    'gluten'        => ['icono' => '🌾', 'label' => __('Gluten', 'flavor-chat-ia')],
    'lacteos'       => ['icono' => '🥛', 'label' => __('Lacteos', 'flavor-chat-ia')],
    'huevo'         => ['icono' => '🥚', 'label' => __('Huevo', 'flavor-chat-ia')],
    'pescado'       => ['icono' => '🐟', 'label' => __('Pescado', 'flavor-chat-ia')],
    'moluscos'      => ['icono' => '🦑', 'label' => __('Moluscos', 'flavor-chat-ia')],
    'crustaceos'    => ['icono' => '🦐', 'label' => __('Crustaceos', 'flavor-chat-ia')],
    'frutos_secos'  => ['icono' => '🥜', 'label' => __('Frutos secos', 'flavor-chat-ia')],
    'soja'          => ['icono' => '🫘', 'label' => __('Soja', 'flavor-chat-ia')],
    'sulfitos'      => ['icono' => '🍷', 'label' => __('Sulfitos', 'flavor-chat-ia')],
    'apio'          => ['icono' => '🌿', 'label' => __('Apio', 'flavor-chat-ia')],
    'mostaza'       => ['icono' => '🟡', 'label' => __('Mostaza', 'flavor-chat-ia')],
    'sesamo'        => ['icono' => '🟤', 'label' => __('Sesamo', 'flavor-chat-ia')],
    'cacahuetes'    => ['icono' => '🥜', 'label' => __('Cacahuetes', 'flavor-chat-ia')],
    'altramuces'    => ['icono' => '🌱', 'label' => __('Altramuces', 'flavor-chat-ia')],
];
?>

<section class="flavor-component flavor-section py-16 <?php echo esc_attr($component_classes ?? ''); ?>" style="background: var(--flavor-bg, #ffffff);">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-6" style="background: var(--flavor-primary); opacity: 0.1;">
                </div>
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-6 -mt-16 relative" style="background: rgba(var(--flavor-primary-rgb, 139, 92, 246), 0.1);">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color: var(--flavor-primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <h2 class="text-4xl md:text-5xl font-black mb-4" style="color: var(--flavor-text, #111827);">
                    <?php echo esc_html($titulo_seccion ?? __('Nuestra Carta', 'flavor-chat-ia')); ?>
                </h2>
                <p class="text-lg max-w-2xl mx-auto" style="color: var(--flavor-text-muted, #6b7280);">
                    <?php _e('Seleccion de platos elaborados con productos frescos y de temporada', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <!-- Navegacion por categorias -->
            <div class="flex flex-wrap justify-center gap-2 mb-12 sticky top-0 py-4 z-10" style="background: var(--flavor-bg, #ffffff);">
                <?php
                $indice_categoria = 0;
                foreach ($categorias_carta_ejemplo as $nombre_categoria => $items_categoria):
                    $indice_categoria++;
                ?>
                    <a href="#carta-<?php echo esc_attr(sanitize_title($nombre_categoria)); ?>"
                       class="px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200 hover:shadow-md <?php echo $indice_categoria === 1 ? '' : 'bg-white border'; ?>"
                       style="<?php echo $indice_categoria === 1 ? 'background: var(--flavor-primary); color: white;' : 'color: var(--flavor-text, #374151); border-color: #e5e7eb;'; ?>">
                        <?php echo esc_html($nombre_categoria); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Categorias y platos -->
            <?php foreach ($categorias_carta_ejemplo as $nombre_categoria => $items_categoria): ?>
            <div id="carta-<?php echo esc_attr(sanitize_title($nombre_categoria)); ?>" class="mb-12">
                <!-- Titulo de categoria -->
                <div class="flex items-center gap-4 mb-6">
                    <h3 class="text-2xl font-bold whitespace-nowrap" style="color: var(--flavor-text, #111827);">
                        <?php echo esc_html($nombre_categoria); ?>
                    </h3>
                    <div class="flex-1 h-px" style="background: #e5e7eb;"></div>
                    <span class="text-sm font-medium px-3 py-1 rounded-full" style="background: #f3f4f6; color: var(--flavor-text-muted, #6b7280);">
                        <?php echo count($items_categoria); ?> <?php _e('platos', 'flavor-chat-ia'); ?>
                    </span>
                </div>

                <!-- Lista de platos -->
                <div class="space-y-4">
                    <?php foreach ($items_categoria as $item_plato): ?>
                    <div class="group flex items-start gap-4 p-4 rounded-xl transition-all duration-200 hover:shadow-md <?php echo $item_plato['es_destacado'] ? 'ring-2' : ''; ?>"
                         style="background: white; <?php echo $item_plato['es_destacado'] ? 'ring-color: var(--flavor-primary); box-shadow: 0 0 0 2px rgba(var(--flavor-primary-rgb, 139, 92, 246), 0.2);' : ''; ?>">

                        <!-- Info del plato -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-lg font-bold" style="color: var(--flavor-text, #111827);">
                                    <?php echo esc_html($item_plato['nombre']); ?>
                                </h4>
                                <?php if ($item_plato['es_destacado']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold text-white" style="background: var(--flavor-primary);">
                                        <?php _e('Destacado', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="text-sm mb-2" style="color: var(--flavor-text-muted, #6b7280);">
                                <?php echo esc_html($item_plato['descripcion']); ?>
                            </p>

                            <?php if ($mostrar_info_alergenos && !empty($item_plato['alergenos'])): ?>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($item_plato['alergenos'] as $clave_alergeno): ?>
                                    <?php if (isset($mapa_alergenos[$clave_alergeno])): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs" style="background: #fef3c7; color: #92400e;" title="<?php echo esc_attr($mapa_alergenos[$clave_alergeno]['label']); ?>">
                                            <?php echo $mapa_alergenos[$clave_alergeno]['icono']; ?>
                                            <?php echo esc_html($mapa_alergenos[$clave_alergeno]['label']); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Precio -->
                        <?php if ($mostrar_columna_precios): ?>
                        <div class="flex-shrink-0 text-right">
                            <span class="text-xl font-bold" style="color: var(--flavor-primary);">
                                <?php echo esc_html(number_format($item_plato['precio'], 2, ',', '.') . ' EUR'); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ($mostrar_info_alergenos): ?>
            <!-- Leyenda de alergenos -->
            <div class="mt-12 p-6 rounded-2xl" style="background: #fffbeb; border: 1px solid #fde68a;">
                <h4 class="text-lg font-bold mb-4" style="color: #92400e;">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php _e('Informacion sobre alergenos', 'flavor-chat-ia'); ?>
                </h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <?php foreach ($mapa_alergenos as $clave_alergeno => $datos_alergeno): ?>
                        <div class="flex items-center gap-2 text-sm" style="color: #78350f;">
                            <span><?php echo $datos_alergeno['icono']; ?></span>
                            <span><?php echo esc_html($datos_alergeno['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="mt-4 text-xs" style="color: #92400e;">
                    <?php _e('Si tienes alguna alergia o intolerancia alimentaria, por favor consulta al personal del establecimiento antes de realizar tu pedido.', 'flavor-chat-ia'); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
