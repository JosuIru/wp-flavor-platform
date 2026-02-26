<?php
/**
 * Template: Marketplace Anuncios Grid
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_anuncios = $titulo_anuncios ?? 'Anuncios Recientes';
$mostrar_filtros = $mostrar_filtros ?? true;
$cantidad_columnas = $cantidad_columnas ?? 'lg:grid-cols-3';
$limite_anuncios = $limite_anuncios ?? 12;

// Cargar anuncios reales de la base de datos
if (!isset($anuncios_marketplace) || empty($anuncios_marketplace)) {
    $args_anuncios = [
        'post_type' => 'marketplace_item',
        'post_status' => 'publish',
        'posts_per_page' => intval($limite_anuncios),
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    // Aplicar filtro de categoría si existe
    if (!empty($_GET['categoria'])) {
        $args_anuncios['tax_query'] = [[
            'taxonomy' => 'marketplace_categoria',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['categoria']),
        ]];
    }

    // Aplicar filtro de tipo si existe
    if (!empty($_GET['tipo'])) {
        $args_anuncios['tax_query'][] = [
            'taxonomy' => 'marketplace_tipo',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['tipo']),
        ];
    }

    $posts_anuncios = get_posts($args_anuncios);
    $anuncios_marketplace = [];

    foreach ($posts_anuncios as $post_anuncio) {
        $autor_anuncio = get_userdata($post_anuncio->post_author);
        $precio_anuncio = get_post_meta($post_anuncio->ID, '_marketplace_precio', true);
        $ubicacion_anuncio = get_post_meta($post_anuncio->ID, '_marketplace_ubicacion', true);
        $condicion_anuncio = get_post_meta($post_anuncio->ID, '_marketplace_condicion', true);
        $intercambio_por = get_post_meta($post_anuncio->ID, '_marketplace_intercambio_por', true);
        $imagen_anuncio = get_the_post_thumbnail_url($post_anuncio->ID, 'medium');

        // Obtener tipo desde taxonomía
        $tipos_terminos = wp_get_post_terms($post_anuncio->ID, 'marketplace_tipo', ['fields' => 'slugs']);
        $tipo_anuncio = !empty($tipos_terminos) && !is_wp_error($tipos_terminos) ? $tipos_terminos[0] : 'venta';

        // Determinar precio según tipo
        $precio_mostrar = $precio_anuncio;
        if ($tipo_anuncio === 'regalo') {
            $precio_mostrar = 'Gratis';
        } elseif ($tipo_anuncio === 'intercambio' || $tipo_anuncio === 'cambio') {
            $precio_mostrar = 'Intercambio';
        }

        // Colores de fondo según tipo
        $colores_tipo = [
            'venta' => 'from-green-400 to-green-600',
            'regalo' => 'from-blue-400 to-blue-600',
            'intercambio' => 'from-orange-400 to-orange-600',
            'cambio' => 'from-orange-400 to-orange-600',
            'alquiler' => 'from-purple-400 to-purple-600',
        ];

        $anuncios_marketplace[] = [
            'id'              => $post_anuncio->ID,
            'titulo'          => $post_anuncio->post_title,
            'descripcion'     => wp_trim_words($post_anuncio->post_content, 20, '...'),
            'precio'          => $precio_mostrar,
            'tipo_anuncio'    => $tipo_anuncio,
            'imagen'          => $imagen_anuncio,
            'imagen_fondo'    => $colores_tipo[$tipo_anuncio] ?? 'from-gray-400 to-gray-600',
            'usuario_nombre'  => $autor_anuncio ? $autor_anuncio->display_name : __('Usuario', 'flavor-chat-ia'),
            'usuario_avatar'  => '👤',
            'ubicacion'       => $ubicacion_anuncio ?: __('Sin ubicación', 'flavor-chat-ia'),
            'fechaPublicacion' => human_time_diff(get_the_time('U', $post_anuncio), current_time('timestamp')),
            'condicion'       => $condicion_anuncio ?: __('No especificada', 'flavor-chat-ia'),
            'intercambio_por' => $intercambio_por,
            'url'             => get_permalink($post_anuncio->ID),
        ];
    }
}

// Variable para saber si hay anuncios
$hay_anuncios = !empty($anuncios_marketplace);

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
            <?php if (!$hay_anuncios): ?>
                <!-- Se mostrará el estado vacío al final -->
            <?php endif; ?>
            <?php foreach ($anuncios_marketplace as $anuncio_item) : ?>
                <div class="flavor-anuncio-card bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden group flex flex-col h-full">

                    <!-- Imagen del anuncio -->
                    <div class="relative h-56 bg-gradient-to-br <?php echo esc_attr($anuncio_item['imagen_fondo']); ?> overflow-hidden">
                        <?php if (!empty($anuncio_item['imagen'])): ?>
                            <a href="<?php echo esc_url($anuncio_item['url'] ?? '#'); ?>">
                                <img src="<?php echo esc_url($anuncio_item['imagen']); ?>"
                                     alt="<?php echo esc_attr($anuncio_item['titulo']); ?>"
                                     class="absolute inset-0 w-full h-full object-cover">
                            </a>
                        <?php else: ?>
                            <!-- Icono placeholder -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-20 h-20 text-white/25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        <?php endif; ?>

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
                            <a href="<?php echo esc_url($anuncio_item['url'] ?? get_permalink($anuncio_item['id'] ?? 0)); ?>" class="flavor-btn-contactar text-green-600 hover:text-green-700 font-semibold text-sm transition-colors">
                                <?php echo esc_html__('Ver', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sin resultados (solo se muestra si no hay anuncios) -->
        <?php if (!$hay_anuncios): ?>
        <div class="mt-12 p-12 text-center rounded-2xl bg-gray-50 border border-gray-200">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 015.646 5.646 9 9 0 0120.354 15.354z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-600 mb-2"><?php echo esc_html__('No hay anuncios', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500"><?php echo esc_html__('Se el primero en publicar un anuncio', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(home_url('/mi-portal/marketplace/publicar/')); ?>" class="inline-flex items-center gap-2 mt-4 px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <?php echo esc_html__('Publicar Anuncio', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>
