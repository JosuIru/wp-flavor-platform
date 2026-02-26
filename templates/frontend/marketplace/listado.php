<?php
/**
 * Frontend: Archive de Marketplace Local
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

// Si no se pasan las variables, consultarlas directamente
if (!isset($productos) || empty($productos)) {
    $query_args = [
        'post_type'      => 'marketplace_item',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $query = new WP_Query($query_args);
    $productos = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $autor_id = get_post_field('post_author', $post_id);
            $autor = get_userdata($autor_id);

            $productos[] = [
                'id'              => $post_id,
                'titulo'          => get_the_title(),
                'descripcion'     => wp_trim_words(get_the_excerpt(), 20),
                'precio'          => get_post_meta($post_id, '_precio', true) ?: get_post_meta($post_id, 'precio', true) ?: '0',
                'imagen'          => get_the_post_thumbnail_url($post_id, 'medium') ?: '',
                'url'             => get_permalink($post_id),
                'condicion'       => get_post_meta($post_id, '_condicion', true) ?: get_post_meta($post_id, 'condicion', true) ?: 'Usado',
                'ubicacion'       => get_post_meta($post_id, '_ubicacion', true) ?: get_post_meta($post_id, 'ubicacion', true) ?: 'Local',
                'vendedor_nombre' => $autor ? $autor->display_name : 'Vendedor',
                'vendedor_id'     => $autor_id,
            ];
        }
        wp_reset_postdata();
    }

    $total_productos = $query->found_posts;
}

// Si no hay estadisticas, calcularlas
if (!isset($estadisticas) || empty($estadisticas)) {
    $total_items = wp_count_posts('marketplace_item');
    $vendedores_query = new WP_Query([
        'post_type'      => 'marketplace_item',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);
    $vendedores_ids = [];
    if ($vendedores_query->have_posts()) {
        foreach ($vendedores_query->posts as $post_id) {
            $vendedores_ids[] = get_post_field('post_author', $post_id);
        }
    }

    $estadisticas = [
        'productos_activos' => isset($total_items->publish) ? $total_items->publish : 0,
        'vendedores'        => count(array_unique($vendedores_ids)),
        'transacciones'     => 0,
        'valoracion_media'  => '4.8',
    ];
}

// Si no hay categorias, obtenerlas de la taxonomia
if (!isset($categorias) || empty($categorias)) {
    $terms = get_terms([
        'taxonomy'   => 'marketplace_categoria',
        'hide_empty' => false,
    ]);

    $categorias = [];
    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            $icono = get_term_meta($term->term_id, 'icono', true);
            $categorias[] = [
                'id'     => $term->term_id,
                'nombre' => $term->name,
                'slug'   => $term->slug,
                'icono'  => $icono ?: '',
                'count'  => $term->count,
            ];
        }
    }
}

$total_productos = $total_productos ?? count($productos);
?>

<div class="flavor-frontend flavor-marketplace-archive">
    <!-- Header con gradiente verde -->
    <div class="bg-gradient-to-r from-lime-500 to-green-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('🛒 Marketplace Local', 'flavor-chat-ia'); ?></h1>
                <p class="text-lime-100"><?php echo esc_html__('Compra, vende e intercambia productos en tu comunidad', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_productos); ?> productos disponibles
                </span>
                <button class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-lime-50 transition-all shadow-md"
                        onclick="flavorMarketplace.publicarAnuncio()">
                    <?php echo esc_html__('📢 Publicar Anuncio', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">📦</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['productos_activos'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Productos activos', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">👤</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['vendedores'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Vendedores', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🤝</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['transacciones'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Transacciones', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">⭐</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['valoracion_media'] ?? '4.7'); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Valoracion media', 'flavor-chat-ia'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-lime-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4"><?php echo esc_html__('💡 ¿Como funciona?', 'flavor-chat-ia'); ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">📸</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Publica', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Sube fotos y describe tu producto para que otros lo encuentren', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">💬</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Contacta', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Habla directamente con compradores o vendedores cercanos', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-lime-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🎉</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Intercambia', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Acuerda el precio y recoge el producto en tu barrio', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>

    <!-- Filtros por categoria -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-lime-100 text-lime-700 font-medium hover:bg-lime-200 transition-colors filter-active" data-categoria="todos">
            <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
        </button>
        <?php if (!empty($categorias)): ?>
            <?php foreach ($categorias as $categoria_item): ?>
            <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-categoria="<?php echo esc_attr($categoria_item['slug']); ?>">
                <?php echo esc_html($categoria_item['icono'] ?? '🏷️'); ?> <?php echo esc_html($categoria_item['nombre']); ?>
                <?php if (!empty($categoria_item['count'])): ?>
                    <span class="text-xs text-gray-400">(<?php echo esc_html($categoria_item['count']); ?>)</span>
                <?php endif; ?>
            </button>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Grid de productos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($productos)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">🛒</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay productos disponibles', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500 mb-6"><?php echo esc_html__('¡Se el primero en publicar un anuncio!', 'flavor-chat-ia'); ?></p>
            <button class="bg-lime-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-lime-600 transition-colors"
                    onclick="flavorMarketplace.publicarAnuncio()">
                <?php echo esc_html__('Publicar Anuncio', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($productos as $producto): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="aspect-video bg-gray-100 relative overflow-hidden">
                <?php if (!empty($producto['imagen'])): ?>
                <img src="<?php echo esc_url($producto['imagen']); ?>" alt="<?php echo esc_attr($producto['titulo']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <span class="text-5xl">📷</span>
                </div>
                <?php endif; ?>
                <span class="absolute top-3 right-3 bg-green-500 text-white font-bold px-3 py-1 rounded-full text-sm shadow">
                    <?php echo esc_html($producto['precio'] ?? '0'); ?> €
                </span>
            </div>
            <div class="p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="bg-lime-100 text-lime-700 text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html($producto['condicion'] ?? 'Usado'); ?>
                    </span>
                    <span class="text-xs text-gray-500 flex items-center gap-1">
                        📍 <?php echo esc_html($producto['ubicacion'] ?? 'Cerca'); ?>
                    </span>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-green-600 transition-colors">
                    <a href="<?php echo esc_url($producto['url'] ?? '#'); ?>">
                        <?php echo esc_html($producto['titulo']); ?>
                    </a>
                </h3>
                <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                    <?php echo esc_html($producto['descripcion'] ?? ''); ?>
                </p>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 text-xs font-medium">
                            <?php echo esc_html(mb_substr($producto['vendedor_nombre'] ?? 'V', 0, 1)); ?>
                        </div>
                        <span class="text-sm text-gray-600"><?php echo esc_html($producto['vendedor_nombre'] ?? 'Vendedor'); ?></span>
                    </div>
                    <a href="<?php echo esc_url($producto['url'] ?? '#'); ?>"
                       class="text-green-600 hover:text-green-700 font-medium text-sm">
                        <?php echo esc_html__('Ver mas →', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_productos > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2" role="navigation" aria-label="<?php esc_attr_e('Paginación de productos', 'flavor-chat-ia'); ?>">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"
                    aria-label="<?php esc_attr_e('Página anterior', 'flavor-chat-ia'); ?>">
                <?php echo esc_html__('← Anterior', 'flavor-chat-ia'); ?>
            </button>
            <span class="px-4 py-2 text-gray-600" aria-live="polite">
                <?php printf(esc_html__('Página 1 de %d', 'flavor-chat-ia'), ceil($total_productos / 12)); ?>
            </span>
            <button class="px-4 py-2 rounded-lg bg-lime-500 text-white hover:bg-lime-600 transition-colors"
                    aria-label="<?php esc_attr_e('Página siguiente', 'flavor-chat-ia'); ?>">
                <?php echo esc_html__('Siguiente →', 'flavor-chat-ia'); ?>
            </button>
        </nav>
    </div>
    <?php endif; ?>
</div>
