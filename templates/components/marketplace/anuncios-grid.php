<?php
/**
 * Template: Marketplace Anuncios Grid
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_anuncios = $titulo_anuncios ?? 'Anuncios Recientes';
$mostrar_filtros = $mostrar_filtros ?? true;
$cantidad_columnas = $cantidad_columnas ?? 'lg:grid-cols-3';

$anuncios_ejemplo = $anuncios_ejemplo ?? [
    [
        'titulo'          => 'Microondas de acero inoxidable',
        'descripcion'     => 'Microondas Samsung en excelente estado, sin defectos. Incluye manual de uso.',
        'precio'          => '65',
        'tipo_anuncio'    => 'venta',
        'imagen_fondo'    => 'from-blue-400 to-blue-600',
        'usuario_nombre'  => 'Maria G.',
        'usuario_avatar'  => '👩',
        'ubicacion'       => 'Barrio Centro',
        'fechaPublicacion' => '2 horas',
        'condicion'       => 'Muy bueno',
        'intercambio_por' => null,
    ],
    [
        'titulo'          => 'Curso de Programacion - Regalo',
        'descripcion'     => 'Tengo un acceso completo a curso de Python que no utilizare. Te lo regalo si lo aprovechas.',
        'precio'          => 'Gratis',
        'tipo_anuncio'    => 'regalo',
        'imagen_fondo'    => 'from-purple-400 to-purple-600',
        'usuario_nombre'  => 'Juan P.',
        'usuario_avatar'  => '👨',
        'ubicacion'       => 'Zona Norte',
        'fechaPublicacion' => '1 dia',
        'condicion'       => 'Acceso digital',
        'intercambio_por' => null,
    ],
    [
        'titulo'          => 'Intercambio: Libros por Discos',
        'descripcion'     => 'Tengo novelas de ciencia ficcion que quiero cambiar por discos de vinilo o rock clasico.',
        'precio'          => 'Intercambio',
        'tipo_anuncio'    => 'intercambio',
        'imagen_fondo'    => 'from-orange-400 to-orange-600',
        'usuario_nombre'  => 'Sofia M.',
        'usuario_avatar'  => '👩',
        'ubicacion'       => 'Zona Sur',
        'fechaPublicacion' => '3 horas',
        'condicion'       => 'Como nuevo',
        'intercambio_por' => 'Discos de vinilo',
    ],
    [
        'titulo'          => 'Bicicleta de ruta Decathlon',
        'descripcion'     => 'Bicicleta de ruta en perfecto estado. Poco uso, se vende por falta de tiempo.',
        'precio'          => '320',
        'tipo_anuncio'    => 'venta',
        'imagen_fondo'    => 'from-red-400 to-red-600',
        'usuario_nombre'  => 'Carlos L.',
        'usuario_avatar'  => '👨',
        'ubicacion'       => 'Barrio Este',
        'fechaPublicacion' => '5 horas',
        'condicion'       => 'Excelente',
        'intercambio_por' => null,
    ],
    [
        'titulo'          => 'Libros de texto - Regalo',
        'descripcion'     => 'Libros de bachillerato, los regalo si alguien los necesita para estudiar este ano.',
        'precio'          => 'Gratis',
        'tipo_anuncio'    => 'regalo',
        'imagen_fondo'    => 'from-green-400 to-green-600',
        'usuario_nombre'  => 'Isabel R.',
        'usuario_avatar'  => '👩',
        'ubicacion'       => 'Barrio Oeste',
        'fechaPublicacion' => '1 dia',
        'condicion'       => 'Buen estado',
        'intercambio_por' => null,
    ],
    [
        'titulo'          => 'Cambio: Movil por Tablet',
        'descripcion'     => 'Cambio mi telefono Android por una tablet o laptop. El movil esta nuevo sin usar.',
        'precio'          => 'Intercambio',
        'tipo_anuncio'    => 'intercambio',
        'imagen_fondo'    => 'from-yellow-400 to-yellow-600',
        'usuario_nombre'  => 'David T.',
        'usuario_avatar'  => '👨',
        'ubicacion'       => 'Zona Centro',
        'fechaPublicacion' => '2 horas',
        'condicion'       => 'Nuevo',
        'intercambio_por' => 'Tablet o laptop',
    ],
];

$opciones_filtro = $opciones_filtro ?? [
    'todos'        => 'Todos los anuncios',
    'venta'        => 'Venta',
    'regalo'       => 'Regalo',
    'intercambio'  => 'Intercambio',
];

$ordenar_opciones = $ordenar_opciones ?? [
    'recientes'    => 'Mas recientes',
    'antigios'     => 'Mas antiguos',
    'precio_asc'   => 'Precio: menor a mayor',
    'precio_desc'  => 'Precio: mayor a menor',
];

/**
 * Obtener clase CSS del badge segun tipo de anuncio
 */
function obtener_clase_tipo_anuncio($tipo_anuncio_variable) {
    switch ($tipo_anuncio_variable) {
        case 'venta':
            return 'bg-green-100 text-green-700';
        case 'regalo':
            return 'bg-blue-100 text-blue-700';
        case 'intercambio':
            return 'bg-orange-100 text-orange-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

/**
 * Obtener etiqueta legible del tipo de anuncio
 */
function obtener_etiqueta_tipo_anuncio($tipo_anuncio_variable) {
    switch ($tipo_anuncio_variable) {
        case 'venta':
            return __('Venta', 'flavor-chat-ia');
        case 'regalo':
            return __('Regalo', 'flavor-chat-ia');
        case 'intercambio':
            return __('Intercambio', 'flavor-chat-ia');
        default:
            return __('Anuncio', 'flavor-chat-ia');
    }
}
?>

<section class="flavor-component flavor-section py-12 lg:py-16 bg-white">
    <div class="flavor-container">
        <!-- Cabecera con titulo y controles -->
        <div class="mb-10">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-6">
                <?php echo esc_html($titulo_anuncios); ?>
            </h2>

            <?php if ($mostrar_filtros) : ?>
                <!-- Filtros y ordenacion -->
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($opciones_filtro as $valor_filtro => $etiqueta_filtro) : ?>
                            <button class="flavor-filtro-btn px-4 py-2 rounded-lg border-2 text-sm font-medium transition-all duration-300 <?php echo esc_attr($valor_filtro === 'todos' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'); ?>" data-filtro="<?php echo esc_attr($valor_filtro); ?>">
                                <?php echo esc_html($etiqueta_filtro); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <select name="flavor-ordenar-anuncios" class="px-4 py-2 rounded-lg border border-gray-200 bg-white text-gray-600 text-sm focus:outline-none focus:ring-2 focus:ring-green-300 transition-all">
                        <?php foreach ($ordenar_opciones as $valor_orden => $etiqueta_orden) : ?>
                            <option value="<?php echo esc_attr($valor_orden); ?>"><?php echo esc_html($etiqueta_orden); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <!-- Grid de anuncios -->
        <div class="grid grid-cols-1 md:grid-cols-2 <?php echo esc_attr($cantidad_columnas); ?> gap-6">
            <?php foreach ($anuncios_ejemplo as $anuncio_item) : ?>
                <div class="flavor-anuncio-card bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden group flex flex-col h-full">

                    <!-- Imagen del anuncio -->
                    <div class="relative h-56 bg-gradient-to-br <?php echo esc_attr($anuncio_item['imagen_fondo']); ?> overflow-hidden">
                        <!-- Icono placeholder -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-20 h-20 text-white/25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>

                        <!-- Badge de tipo de anuncio -->
                        <div class="absolute top-3 left-3 px-3 py-1 rounded-lg font-semibold text-xs <?php echo esc_attr(obtener_clase_tipo_anuncio($anuncio_item['tipo_anuncio'])); ?> backdrop-blur shadow-sm">
                            <?php echo esc_html(obtener_etiqueta_tipo_anuncio($anuncio_item['tipo_anuncio'])); ?>
                        </div>

                        <!-- Boton favorito -->
                        <button class="flavor-btn-favorito absolute top-3 right-3 w-10 h-10 rounded-full bg-white/90 backdrop-blur flex items-center justify-center shadow-sm hover:bg-white transition-all group/fav" aria-label="<?php echo esc_attr__('Anadir a favoritos', 'flavor-chat-ia'); ?>">
                            <svg class="w-5 h-5 text-gray-400 group-hover/fav:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>

                        <!-- Info del anuncio superpuesta -->
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-4">
                            <div class="text-white text-xs opacity-90">
                                <?php echo esc_html($anuncio_item['fechaPublicacion']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido del anuncio -->
                    <div class="p-5 flex flex-col flex-grow">
                        <!-- Titulo -->
                        <h3 class="text-lg font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-green-600 transition-colors">
                            <?php echo esc_html($anuncio_item['titulo']); ?>
                        </h3>

                        <!-- Descripcion -->
                        <p class="text-sm text-gray-600 mb-4 line-clamp-2 flex-grow">
                            <?php echo esc_html($anuncio_item['descripcion']); ?>
                        </p>

                        <!-- Ubicacion -->
                        <div class="flex items-center gap-1 text-sm text-gray-500 mb-4">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span><?php echo esc_html($anuncio_item['ubicacion']); ?></span>
                        </div>

                        <!-- Condicion -->
                        <div class="text-xs text-gray-400 mb-4 pb-4 border-b border-gray-100">
                            <?php echo esc_html($anuncio_item['condicion']); ?>
                        </div>

                        <!-- Precio o tipo de intercambio -->
                        <div class="mb-4">
                            <?php if ($anuncio_item['tipo_anuncio'] === 'intercambio' && !empty($anuncio_item['intercambio_por'])) : ?>
                                <p class="text-sm font-semibold text-orange-600">
                                    <?php echo esc_html__('Busca: ', 'flavor-chat-ia'); ?><?php echo esc_html($anuncio_item['intercambio_por']); ?>
                                </p>
                            <?php else : ?>
                                <div class="text-2xl font-bold text-green-600">
                                    <?php if ($anuncio_item['tipo_anuncio'] === 'regalo') : ?>
                                        <span class="text-lg text-blue-600"><?php echo esc_html__('GRATIS', 'flavor-chat-ia'); ?></span>
                                    <?php elseif ($anuncio_item['tipo_anuncio'] === 'intercambio') : ?>
                                        <span class="text-lg text-orange-600"><?php echo esc_html__('INTERCAMBIO', 'flavor-chat-ia'); ?></span>
                                    <?php else : ?>
                                        <?php echo esc_html($anuncio_item['precio']); ?>&euro;
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Usuario -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-sm">
                                    <?php echo esc_html($anuncio_item['usuario_avatar']); ?>
                                </div>
                                <span class="text-sm font-medium text-gray-700"><?php echo esc_html($anuncio_item['usuario_nombre']); ?></span>
                            </div>
                            <button class="flavor-btn-contactar text-green-600 hover:text-green-700 font-semibold text-sm transition-colors">
                                <?php echo esc_html__('Ver', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sin resultados -->
        <div class="mt-12 p-12 text-center rounded-2xl bg-gray-50 border border-gray-200">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 015.646 5.646 9 9 0 0120.354 15.354z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-600 mb-2"><?php echo esc_html__('No hay anuncios', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500"><?php echo esc_html__('Intenta cambiar los filtros o vuelve mas tarde', 'flavor-chat-ia'); ?></p>
        </div>
    </div>
</section>
