<?php
/**
 * Template parcial: Productos Destacados de Grupos de Consumo
 *
 * Muestra productos disponibles en el ciclo actual
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$id_seccion = $id_seccion ?? 'productos';

// Obtener productos
$productos = get_posts([
    'post_type' => 'gc_producto',
    'post_status' => 'publish',
    'posts_per_page' => 8,
    'orderby' => 'rand',
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => '_gc_stock',
            'value' => '0',
            'compare' => '>',
            'type' => 'NUMERIC',
        ],
        [
            'key' => '_gc_stock',
            'compare' => 'NOT EXISTS',
        ],
    ],
]);
?>

<section id="<?php echo esc_attr($id_seccion); ?>" class="flavor-landing__section flavor-gc-productos-section">
    <div class="flavor-container">
        <header class="flavor-section-header">
            <h2 class="flavor-section-title"><?php _e('Productos Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="flavor-section-subtitle"><?php _e('Productos frescos y de temporada de nuestros productores locales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </header>

        <?php if (!empty($productos)): ?>
            <div class="flavor-gc-productos-grid">
                <?php foreach ($productos as $producto):
                    $imagen_url = get_the_post_thumbnail_url($producto->ID, 'medium');
                    $precio = get_post_meta($producto->ID, '_gc_precio', true);
                    $unidad = get_post_meta($producto->ID, '_gc_unidad', true) ?: 'ud';
                    $productor_id = get_post_meta($producto->ID, '_gc_productor_id', true);
                    $productor = $productor_id ? get_post($productor_id) : null;
                    $stock = get_post_meta($producto->ID, '_gc_stock', true);
                ?>
                    <article class="flavor-gc-producto-card">
                        <div class="flavor-gc-producto-imagen">
                            <?php if ($imagen_url): ?>
                                <img src="<?php echo esc_url($imagen_url); ?>" alt="<?php echo esc_attr($producto->post_title); ?>">
                            <?php else: ?>
                                <div class="flavor-gc-producto-placeholder">
                                    <span class="dashicons dashicons-carrot"></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($stock !== '' && intval($stock) <= 5 && intval($stock) > 0): ?>
                                <span class="flavor-gc-badge flavor-gc-badge--warning"><?php _e('Últimas unidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-gc-producto-content">
                            <h3 class="flavor-gc-producto-titulo"><?php echo esc_html($producto->post_title); ?></h3>
                            <?php if ($productor): ?>
                                <p class="flavor-gc-producto-productor">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php echo esc_html($productor->post_title); ?>
                                </p>
                            <?php endif; ?>
                            <div class="flavor-gc-producto-precio">
                                <?php if ($precio): ?>
                                    <span class="flavor-gc-precio-valor"><?php echo number_format(floatval($precio), 2); ?> <?php esc_html_e('&euro;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <span class="flavor-gc-precio-unidad">/ <?php echo esc_html($unidad); ?></span>
                                <?php else: ?>
                                    <span class="flavor-gc-precio-consultar"><?php _e('Consultar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flavor-gc-producto-actions">
                            <?php if (is_user_logged_in()): ?>
                                <button type="button" class="flavor-btn flavor-btn--primary flavor-btn--sm flavor-btn--full gc-agregar-lista" data-producto-id="<?php echo esc_attr($producto->ID); ?>">
                                    <span class="dashicons dashicons-plus"></span>
                                    <?php _e('Añadir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            <?php else: ?>
                                <a href="<?php echo esc_url(wp_login_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'productos'))); ?>" class="flavor-btn flavor-btn--outline flavor-btn--sm flavor-btn--full">
                                    <?php _e('Inicia sesión para pedir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="flavor-section-footer">
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'productos')); ?>" class="flavor-btn flavor-btn--primary">
                    <?php _e('Ver todo el catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </a>
            </div>
        <?php else: ?>
            <div class="flavor-gc-empty-state">
                <span class="dashicons dashicons-carrot"></span>
                <p><?php _e('Los productos estarán disponibles cuando abra el próximo ciclo de pedidos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-gc-productos-section {
    padding: 4rem 0;
    background: #fff;
}
.flavor-gc-productos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1.25rem;
    margin-top: 2rem;
}
.flavor-gc-producto-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-gc-producto-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.flavor-gc-producto-imagen {
    position: relative;
    height: 180px;
    background: #f1f5f9;
}
.flavor-gc-producto-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.flavor-gc-producto-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #84cc16;
}
.flavor-gc-producto-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}
.flavor-gc-badge--warning {
    position: absolute;
    top: 8px;
    left: 8px;
    background: #f59e0b;
    color: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.flavor-gc-producto-content {
    padding: 1rem;
}
.flavor-gc-producto-titulo {
    margin: 0 0 0.5rem;
    font-size: 1rem;
    color: #1e293b;
    line-height: 1.3;
}
.flavor-gc-producto-productor {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin: 0 0 0.75rem;
    font-size: 0.813rem;
    color: #64748b;
}
.flavor-gc-producto-productor .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}
.flavor-gc-producto-precio {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}
.flavor-gc-precio-valor {
    font-size: 1.25rem;
    font-weight: 700;
    color: #84cc16;
}
.flavor-gc-precio-unidad {
    font-size: 0.813rem;
    color: #94a3b8;
}
.flavor-gc-precio-consultar {
    font-size: 0.875rem;
    color: #64748b;
    font-style: italic;
}
.flavor-gc-producto-actions {
    padding: 0.75rem 1rem 1rem;
}
.flavor-btn--full {
    width: 100%;
}
</style>
