<?php
/**
 * Template: Ciclo de Pedidos - Grupos de Consumo
 *
 * Muestra el ciclo actual del pedido colectivo con timeline visual:
 * apertura pedidos, cierre, reparto. Incluye estado actual del ciclo.
 *
 * @var string $titulo_seccion
 * @var string $subtitulo_seccion
 * @var string $estado_ciclo_actual
 * @var string $fecha_apertura
 * @var string $fecha_cierre
 * @var string $fecha_reparto
 * @var int    $pedidos_en_curso
 * @var int    $productos_en_cesta
 * @var string $component_classes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_seccion       = $titulo_seccion ?? 'Ciclo de Pedidos Actual';
$subtitulo_seccion    = $subtitulo_seccion ?? 'Consulta las fechas importantes del ciclo de compra colectiva en curso';
$estado_ciclo_actual  = $estado_ciclo_actual ?? 'abierto';
$fecha_apertura       = $fecha_apertura ?? 'Lunes 3 Feb';
$fecha_cierre         = $fecha_cierre ?? 'Viernes 7 Feb';
$fecha_reparto        = $fecha_reparto ?? 'Martes 11 Feb';
$pedidos_en_curso     = $pedidos_en_curso ?? 42;
$productos_en_cesta   = $productos_en_cesta ?? 156;

$fases_ciclo = $fases_ciclo ?? [
    [
        'nombre'      => 'Apertura de Pedidos',
        'fecha'       => $fecha_apertura,
        'descripcion' => 'Se abren los pedidos. Explora el catalogo y anade productos a tu cesta.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>',
        'estado'      => 'completado',
    ],
    [
        'nombre'      => 'Cierre de Pedidos',
        'fecha'       => $fecha_cierre,
        'descripcion' => 'Se cierran los pedidos y se confirman las cantidades con los productores.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'estado'      => 'activo',
    ],
    [
        'nombre'      => 'Dia de Reparto',
        'fecha'       => $fecha_reparto,
        'descripcion' => 'Recoge tu pedido en el punto de reparto asignado a tu grupo de consumo.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>',
        'estado'      => 'pendiente',
    ],
];
?>
<section class="flavor-component flavor-section py-16 lg:py-24 bg-white">
    <div class="flavor-container">
        <!-- Titulo -->
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo_seccion); ?>
            </p>

            <!-- Badge de estado -->
            <div class="inline-flex items-center gap-2 mt-6 px-4 py-2 rounded-full
                <?php if ($estado_ciclo_actual === 'abierto') : ?>
                    bg-green-100 text-green-700
                <?php elseif ($estado_ciclo_actual === 'cerrado') : ?>
                    bg-red-100 text-red-700
                <?php else : ?>
                    bg-yellow-100 text-yellow-700
                <?php endif; ?>">
                <span class="w-2 h-2 rounded-full
                    <?php if ($estado_ciclo_actual === 'abierto') : ?>
                        bg-green-500 animate-pulse
                    <?php elseif ($estado_ciclo_actual === 'cerrado') : ?>
                        bg-red-500
                    <?php else : ?>
                        bg-yellow-500 animate-pulse
                    <?php endif; ?>">
                </span>
                <span class="text-sm font-semibold">
                    <?php
                    if ($estado_ciclo_actual === 'abierto') {
                        echo esc_html__('Pedidos Abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    } elseif ($estado_ciclo_actual === 'cerrado') {
                        echo esc_html__('Pedidos Cerrados', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    } else {
                        echo esc_html__('En Preparacion', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    }
                    ?>
                </span>
            </div>
        </div>

        <!-- Timeline de fases -->
        <div class="max-w-5xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Linea conectora (desktop) -->
                <div class="hidden md:block absolute top-12 left-0 right-0 h-1 bg-green-200" style="width: calc(100% - 8rem); margin-left: 4rem;"></div>

                <?php foreach ($fases_ciclo as $fase_item) : ?>
                    <div class="relative">
                        <div class="flex flex-col items-center text-center">
                            <!-- Icono de estado -->
                            <div class="relative z-10 w-24 h-24 rounded-full flex items-center justify-center shadow-xl mb-6 transition-transform duration-300 hover:scale-110
                                <?php if ($fase_item['estado'] === 'completado') : ?>
                                    bg-gradient-to-br from-green-500 to-emerald-600
                                <?php elseif ($fase_item['estado'] === 'activo') : ?>
                                    bg-gradient-to-br from-green-400 to-emerald-500 ring-4 ring-green-200 ring-offset-2
                                <?php else : ?>
                                    bg-gradient-to-br from-gray-300 to-gray-400
                                <?php endif; ?>">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php echo $fase_item['icono']; ?>
                                </svg>
                            </div>

                            <!-- Fecha -->
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold mb-3
                                <?php if ($fase_item['estado'] === 'activo') : ?>
                                    bg-green-100 text-green-700
                                <?php else : ?>
                                    bg-gray-100 text-gray-600
                                <?php endif; ?>">
                                <?php echo esc_html($fase_item['fecha']); ?>
                            </span>

                            <!-- Nombre -->
                            <h3 class="text-xl font-bold text-gray-900 mb-3">
                                <?php echo esc_html($fase_item['nombre']); ?>
                            </h3>

                            <!-- Descripcion -->
                            <p class="text-gray-600 leading-relaxed text-sm">
                                <?php echo esc_html($fase_item['descripcion']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Resumen del ciclo -->
            <div class="mt-16 grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
                <div class="bg-green-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2"><?php echo esc_html($pedidos_en_curso); ?></div>
                    <div class="text-gray-700"><?php echo esc_html__('Pedidos en Este Ciclo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bg-emerald-50 rounded-xl p-6 text-center">
                    <div class="text-3xl font-bold text-emerald-600 mb-2"><?php echo esc_html($productos_en_cesta); ?></div>
                    <div class="text-gray-700"><?php echo esc_html__('Productos en Cestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
