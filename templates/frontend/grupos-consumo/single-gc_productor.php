<?php
/**
 * Template: Single Productor
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
    $productor_id = get_the_ID();
    $ubicacion = get_post_meta($productor_id, '_gc_ubicacion', true);
    $telefono = get_post_meta($productor_id, '_gc_telefono', true);
    $email = get_post_meta($productor_id, '_gc_email', true);
    $web = get_post_meta($productor_id, '_gc_web', true);
    $certificaciones = get_post_meta($productor_id, '_gc_certificaciones', true);
    $km_aproximado = get_post_meta($productor_id, '_gc_km', true);
?>

<div class="gc-single-productor">
    <article id="productor-<?php echo esc_attr($productor_id); ?>" class="gc-productor-article">
        <header class="gc-productor-header">
            <?php if (has_post_thumbnail()): ?>
            <div class="gc-productor-imagen">
                <?php the_post_thumbnail('large'); ?>
            </div>
            <?php endif; ?>

            <div class="gc-productor-info">
                <h1 class="gc-productor-titulo"><?php the_title(); ?></h1>

                <div class="gc-productor-meta">
                    <?php if (!empty($ubicacion)): ?>
                    <span class="gc-ubicacion">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($ubicacion); ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($km_aproximado)): ?>
                    <span class="gc-distancia">
                        <span class="dashicons dashicons-car"></span>
                        <?php printf(__('%s km', 'flavor-chat-ia'), esc_html($km_aproximado)); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($certificaciones)): ?>
                <div class="gc-productor-certificaciones">
                    <?php
                    $certs = is_array($certificaciones) ? $certificaciones : explode(',', $certificaciones);
                    foreach ($certs as $cert):
                        $cert = trim($cert);
                        if ($cert):
                    ?>
                    <span class="gc-badge gc-badge-cert"><?php echo esc_html($cert); ?></span>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <div class="gc-productor-body">
            <div class="gc-productor-contenido">
                <h2><?php _e('Sobre el productor', 'flavor-chat-ia'); ?></h2>
                <div class="gc-productor-descripcion">
                    <?php the_content(); ?>
                </div>

                <h2><?php _e('Productos', 'flavor-chat-ia'); ?></h2>
                <?php
                $productos = get_posts([
                    'post_type' => 'gc_producto',
                    'posts_per_page' => 12,
                    'meta_query' => [
                        ['key' => '_gc_productor_id', 'value' => $productor_id],
                    ],
                ]);

                if ($productos):
                ?>
                <div class="gc-productos-grid">
                    <?php foreach ($productos as $producto):
                        $precio = get_post_meta($producto->ID, '_gc_precio', true);
                        $unidad = get_post_meta($producto->ID, '_gc_unidad', true) ?: 'ud';
                    ?>
                    <div class="gc-producto-card">
                        <?php if (has_post_thumbnail($producto->ID)): ?>
                        <div class="gc-producto-imagen">
                            <?php echo get_the_post_thumbnail($producto->ID, 'medium'); ?>
                        </div>
                        <?php endif; ?>
                        <h4><?php echo esc_html($producto->post_title); ?></h4>
                        <p class="gc-producto-precio">
                            <?php echo esc_html(number_format((float)$precio, 2)); ?> / <?php echo esc_html($unidad); ?>
                        </p>
                        <a href="<?php echo get_permalink($producto->ID); ?>" class="gc-btn gc-btn-small">
                            <?php _e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p><?php _e('Este productor no tiene productos listados actualmente.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>

            <aside class="gc-productor-sidebar">
                <div class="gc-card">
                    <h3><?php _e('Contacto', 'flavor-chat-ia'); ?></h3>
                    <ul class="gc-contacto-lista">
                        <?php if (!empty($telefono)): ?>
                        <li>
                            <span class="dashicons dashicons-phone"></span>
                            <a href="tel:<?php echo esc_attr($telefono); ?>"><?php echo esc_html($telefono); ?></a>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($email)): ?>
                        <li>
                            <span class="dashicons dashicons-email"></span>
                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($web)): ?>
                        <li>
                            <span class="dashicons dashicons-admin-site"></span>
                            <a href="<?php echo esc_url($web); ?>" target="_blank" rel="noopener"><?php echo esc_html(parse_url($web, PHP_URL_HOST)); ?></a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </aside>
        </div>

        <nav class="gc-productor-nav">
            <a href="<?php echo esc_url(home_url('/grupos-consumo/')); ?>" class="gc-btn gc-btn-link">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Volver', 'flavor-chat-ia'); ?>
            </a>
        </nav>
    </article>
</div>

<?php
endwhile;
get_footer();
