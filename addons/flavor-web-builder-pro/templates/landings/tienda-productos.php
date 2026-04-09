<?php
/**
 * Template para Tienda - Sección de Productos Destacados
 *
 * Variables disponibles: $titulo, $productos, $columnas, $mostrar_precios, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? __('Productos Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN);
$columnas = $columnas ?? 4;
$mostrar_precios = $mostrar_precios ?? true;
$color_primario = $color_primario ?? '#00a0d2';

// Obtener productos de WooCommerce si está activo
$productos = [];
if (class_exists('WooCommerce')) {
    $args = [
        'status' => 'publish',
        'limit' => 8,
        'featured' => true,
        'orderby' => 'date',
        'order' => 'DESC',
    ];
    $productos = wc_get_products($args);

    // Si no hay destacados, mostrar los más recientes
    if (empty($productos)) {
        $args['featured'] = false;
        $productos = wc_get_products($args);
    }
}
?>

<section class="flavor-tienda-productos" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <div class="flavor-section-header">
            <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
            <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="flavor-ver-todos">
                <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>

        <div class="flavor-productos-grid" style="--columnas: <?php echo intval($columnas); ?>;">
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $producto): ?>
                    <article class="flavor-producto-card">
                        <a href="<?php echo esc_url($producto->get_permalink()); ?>" class="flavor-producto-link">
                            <div class="flavor-producto-imagen">
                                <?php echo $producto->get_image('woocommerce_thumbnail'); ?>
                                <?php if ($producto->is_on_sale()): ?>
                                    <span class="flavor-producto-badge flavor-badge--oferta">
                                        <?php esc_html_e('Oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-producto-info">
                                <h3 class="flavor-producto-nombre"><?php echo esc_html($producto->get_name()); ?></h3>
                                <?php if ($mostrar_precios): ?>
                                    <div class="flavor-producto-precio">
                                        <?php echo $producto->get_price_html(); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <button class="flavor-producto-carrito" data-product-id="<?php echo esc_attr($producto->get_id()); ?>">
                            <span class="dashicons dashicons-cart"></span>
                            <span class="flavor-sr-only"><?php esc_html_e('Añadir al carrito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </button>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <article class="flavor-producto-card flavor-placeholder">
                        <div class="flavor-producto-imagen"></div>
                        <div class="flavor-producto-info">
                            <h3 class="flavor-producto-nombre">Producto de ejemplo</h3>
                            <div class="flavor-producto-precio">€29.99</div>
                        </div>
                    </article>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.flavor-tienda-productos {
    padding: 4rem 0;
    background: #f9fafb;
}
.flavor-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}
.flavor-section-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
    color: #1f2937;
}
.flavor-ver-todos {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--color-primario);
    text-decoration: none;
    font-weight: 500;
    transition: gap 0.2s;
}
.flavor-ver-todos:hover {
    gap: 0.5rem;
}
.flavor-productos-grid {
    display: grid;
    grid-template-columns: repeat(var(--columnas, 4), 1fr);
    gap: 1.5rem;
}
@media (max-width: 1024px) {
    .flavor-productos-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 768px) {
    .flavor-productos-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .flavor-section-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
@media (max-width: 480px) {
    .flavor-productos-grid {
        grid-template-columns: 1fr;
    }
}
.flavor-producto-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}
.flavor-producto-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.flavor-producto-link {
    display: block;
    text-decoration: none;
    color: inherit;
}
.flavor-producto-imagen {
    position: relative;
    aspect-ratio: 1;
    background: #f3f4f6;
    overflow: hidden;
}
.flavor-producto-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}
.flavor-producto-card:hover .flavor-producto-imagen img {
    transform: scale(1.05);
}
.flavor-producto-badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}
.flavor-badge--oferta {
    background: #ef4444;
    color: white;
}
.flavor-producto-info {
    padding: 1rem;
}
.flavor-producto-nombre {
    font-size: 0.9375rem;
    font-weight: 500;
    margin: 0 0 0.5rem;
    color: #1f2937;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.flavor-producto-precio {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-primario);
}
.flavor-producto-precio del {
    color: #9ca3af;
    font-weight: 400;
    margin-right: 0.5rem;
}
.flavor-producto-precio ins {
    text-decoration: none;
}
.flavor-producto-carrito {
    position: absolute;
    bottom: 1rem;
    right: 1rem;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--color-primario);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.8);
    transition: opacity 0.2s, transform 0.2s;
}
.flavor-producto-card:hover .flavor-producto-carrito {
    opacity: 1;
    transform: scale(1);
}
.flavor-producto-carrito:hover {
    transform: scale(1.1);
}
.flavor-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
.flavor-placeholder .flavor-producto-imagen {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
