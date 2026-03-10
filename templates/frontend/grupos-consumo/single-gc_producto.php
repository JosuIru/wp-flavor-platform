<?php
/**
 * Template: Single Producto
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    $producto_id = get_the_ID();
    $precio = get_post_meta($producto_id, '_gc_precio', true);
    $unidad = get_post_meta($producto_id, '_gc_unidad', true) ?: 'ud';
    $stock = get_post_meta($producto_id, '_gc_stock', true);
    $productor_id = get_post_meta($producto_id, '_gc_productor_id', true);
    $ciclo_id = get_post_meta($producto_id, '_gc_ciclo_id', true);
    $categoria = get_post_meta($producto_id, '_gc_categoria', true);
    $origen = get_post_meta($producto_id, '_gc_origen', true);
    $disponible = get_post_meta($producto_id, '_gc_disponible', true) === '1';

    $productor = $productor_id ? get_post($productor_id) : null;
    $ciclo = $ciclo_id ? get_post($ciclo_id) : null;
?>

<div class="gc-single-producto">
    <article id="producto-<?php echo esc_attr($producto_id); ?>" class="gc-producto-article">
        <div class="gc-producto-layout">
            <div class="gc-producto-galeria">
                <?php if (has_post_thumbnail()): ?>
                <div class="gc-producto-imagen-principal">
                    <?php the_post_thumbnail('large'); ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="gc-producto-info">
                <header class="gc-producto-header">
                    <?php if (!empty($categoria)): ?>
                    <p class="gc-producto-categoria"><?php echo esc_html(ucfirst($categoria)); ?></p>
                    <?php endif; ?>

                    <h1 class="gc-producto-titulo"><?php the_title(); ?></h1>

                    <?php if ($productor): ?>
                    <p class="gc-producto-productor">
                        <?php _e('Por', 'flavor-chat-ia'); ?>
                        <a href="<?php echo get_permalink($productor->ID); ?>"><?php echo esc_html($productor->post_title); ?></a>
                    </p>
                    <?php endif; ?>

                    <div class="gc-producto-precio-box">
                        <span class="gc-producto-precio"><?php echo esc_html(number_format((float)$precio, 2)); ?></span>
                        <span class="gc-producto-unidad">/ <?php echo esc_html($unidad); ?></span>
                    </div>

                    <div class="gc-producto-disponibilidad">
                        <?php if ($disponible && $stock !== '' && (int)$stock > 0): ?>
                        <span class="gc-badge gc-badge-disponible"><?php _e('Disponible', 'flavor-chat-ia'); ?></span>
                        <span class="gc-stock"><?php printf(__('Stock: %s', 'flavor-chat-ia'), esc_html($stock)); ?></span>
                        <?php elseif (!$disponible): ?>
                        <span class="gc-badge gc-badge-agotado"><?php _e('No disponible', 'flavor-chat-ia'); ?></span>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if ($disponible && is_user_logged_in()): ?>
                <div class="gc-producto-agregar">
                    <form method="post" class="gc-agregar-form">
                        <?php wp_nonce_field('gc_agregar_' . $producto_id); ?>
                        <input type="hidden" name="producto_id" value="<?php echo esc_attr($producto_id); ?>">

                        <div class="gc-cantidad-control">
                            <label for="cantidad"><?php _e('Cantidad', 'flavor-chat-ia'); ?></label>
                            <div class="gc-cantidad-input">
                                <button type="button" class="gc-cantidad-btn gc-cantidad-menos">-</button>
                                <input type="number" id="cantidad" name="cantidad" value="1" min="1" max="<?php echo esc_attr($stock ?: 99); ?>">
                                <button type="button" class="gc-cantidad-btn gc-cantidad-mas">+</button>
                            </div>
                        </div>

                        <button type="submit" name="gc_agregar_cesta" class="gc-btn gc-btn-primary gc-btn-block">
                            <span class="dashicons dashicons-cart"></span>
                            <?php _e('Agregar a la cesta', 'flavor-chat-ia'); ?>
                        </button>
                    </form>
                </div>
                <?php elseif (!is_user_logged_in()): ?>
                <div class="gc-producto-login">
                    <p><?php _e('Inicia sesion para hacer pedidos.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="gc-btn gc-btn-primary">
                        <?php _e('Iniciar sesion', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php endif; ?>

                <div class="gc-producto-detalles">
                    <h2><?php _e('Descripcion', 'flavor-chat-ia'); ?></h2>
                    <div class="gc-producto-descripcion">
                        <?php the_content(); ?>
                    </div>

                    <?php if (!empty($origen)): ?>
                    <p><strong><?php _e('Origen:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($origen); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <nav class="gc-producto-nav">
            <?php if ($ciclo): ?>
            <a href="<?php echo get_permalink($ciclo->ID); ?>" class="gc-btn gc-btn-link">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Volver al ciclo', 'flavor-chat-ia'); ?>
            </a>
            <?php else: ?>
            <a href="<?php echo esc_url(home_url('/grupos-consumo/')); ?>" class="gc-btn gc-btn-link">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Volver', 'flavor-chat-ia'); ?>
            </a>
            <?php endif; ?>
        </nav>
    </article>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menos = document.querySelector('.gc-cantidad-menos');
    const mas = document.querySelector('.gc-cantidad-mas');
    const input = document.querySelector('.gc-cantidad-input input');

    if (menos && mas && input) {
        menos.addEventListener('click', function() {
            const val = parseInt(input.value) || 1;
            if (val > 1) input.value = val - 1;
        });
        mas.addEventListener('click', function() {
            const val = parseInt(input.value) || 1;
            const max = parseInt(input.max) || 99;
            if (val < max) input.value = val + 1;
        });
    }
});
</script>

<?php
endwhile;
get_footer();
