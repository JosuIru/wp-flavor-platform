<?php
/**
 * Template: Grid de Productos - Grupos de Consumo
 *
 * Muestra una cuadricula de productos disponibles para pedido colectivo.
 * Cada tarjeta incluye nombre, productor, precio/kg, origen y certificacion.
 *
 * @var string $titulo_seccion
 * @var array  $productos_ejemplo
 * @var array  $opciones_ordenar
 * @var string $component_classes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_seccion = $titulo_seccion ?? 'Productos Disponibles';

$productos_ejemplo = $productos_ejemplo ?? [
    [
        'nombre'        => 'Naranjas de Valencia',
        'descripcion'   => 'Naranjas ecologicas de temporada, recogidas en las ultimas 48 horas. Dulces y jugosas.',
        'productor'     => 'Finca El Naranjo',
        'precio_kg'     => '2.50',
        'origen'        => 'Valencia',
        'certificacion' => 'Ecologico',
        'gradiente'     => 'from-green-500 to-emerald-600',
    ],
    [
        'nombre'        => 'Queso Manchego Curado',
        'descripcion'   => 'Queso artesanal de oveja manchega, curacion minima de 6 meses. Sabor intenso.',
        'productor'     => 'Queseria La Mancha',
        'precio_kg'     => '14.90',
        'origen'        => 'Ciudad Real',
        'certificacion' => 'Km0',
        'gradiente'     => 'from-emerald-500 to-teal-600',
    ],
    [
        'nombre'        => 'Pan de Masa Madre',
        'descripcion'   => 'Pan artesanal elaborado con harina ecologica y fermentacion lenta de 24 horas.',
        'productor'     => 'Obrador Artesano',
        'precio_kg'     => '5.80',
        'origen'        => 'Local',
        'certificacion' => 'Artesanal',
        'gradiente'     => 'from-lime-500 to-green-600',
    ],
];

$opciones_ordenar = $opciones_ordenar ?? [
    'recientes'      => 'Mas recientes',
    'precio_asc'     => 'Precio: menor a mayor',
    'precio_desc'    => 'Precio: mayor a menor',
    'productor'      => 'Por productor',
];
?>
<section class="flavor-component flavor-section py-12 lg:py-16 bg-gray-50">
    <div class="flavor-container">
        <!-- Cabecera con ordenacion -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <h2 class="text-2xl lg:text-3xl font-bold text-gray-800"><?php echo esc_html($titulo_seccion); ?></h2>
            <select name="ordenar_productos" class="px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-600 text-sm focus:outline-none focus:ring-2 focus:ring-green-300">
                <?php foreach ($opciones_ordenar as $valor_orden => $etiqueta_orden) : ?>
                    <option value="<?php echo esc_attr($valor_orden); ?>"><?php echo esc_html($etiqueta_orden); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Grid de productos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($productos_ejemplo as $producto_item) : ?>
                <div class="flavor-card bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 overflow-hidden group">
                    <!-- Imagen placeholder con gradiente -->
                    <div class="relative h-48 bg-gradient-to-br <?php echo esc_attr($producto_item['gradiente']); ?> overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <!-- Badge de precio -->
                        <div class="absolute top-3 left-3 px-3 py-1 rounded-lg bg-white/90 backdrop-blur text-green-700 font-bold text-lg shadow-sm">
                            <?php echo esc_html($producto_item['precio_kg']); ?>&euro;/kg
                        </div>
                        <!-- Badge de certificacion -->
                        <div class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-bold shadow-sm
                            <?php if ($producto_item['certificacion'] === 'Ecologico') : ?>
                                bg-green-100 text-green-700
                            <?php elseif ($producto_item['certificacion'] === 'Km0') : ?>
                                bg-blue-100 text-blue-700
                            <?php else : ?>
                                bg-amber-100 text-amber-700
                            <?php endif; ?>">
                            <?php echo esc_html($producto_item['certificacion']); ?>
                        </div>
                        <!-- Badge de origen -->
                        <div class="absolute bottom-3 left-3 px-2 py-1 rounded-md bg-black/50 backdrop-blur text-white text-xs font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <?php echo esc_html($producto_item['origen']); ?>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-green-600 transition-colors">
                            <?php echo esc_html($producto_item['nombre']); ?>
                        </h3>
                        <p class="text-sm text-gray-500 mb-3 line-clamp-2">
                            <?php echo esc_html($producto_item['descripcion']); ?>
                        </p>

                        <!-- Productor -->
                        <div class="flex items-center gap-2 mb-4 pb-4 border-b border-gray-100">
                            <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                <?php echo esc_html(mb_substr($producto_item['productor'], 0, 2)); ?>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700"><?php echo esc_html($producto_item['productor']); ?></p>
                            </div>
                        </div>

                        <!-- Boton anadir al pedido -->
                        <a href="<?php echo esc_url('/grupos-consumo/pedido/'); ?>" class="block w-full text-center px-5 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-lg transition-all">
                            <?php echo esc_html__('Anadir al Pedido', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Boton ver mas -->
        <div class="text-center mt-10">
            <a href="<?php echo esc_url('/grupos-consumo/productos/'); ?>" class="inline-flex items-center gap-2 px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition-all">
                <?php echo esc_html__('Ver Todos los Productos', 'flavor-chat-ia'); ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</section>
