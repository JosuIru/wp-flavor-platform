<?php
/**
 * Template: Single Ciclo de Compra
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    $ciclo_id = get_the_ID();
    $grupo_id = get_post_meta($ciclo_id, '_gc_grupo_id', true);
    $estado = get_post_meta($ciclo_id, '_gc_estado', true) ?: 'abierto';
    $fecha_cierre = get_post_meta($ciclo_id, '_gc_fecha_cierre', true);
    $fecha_recogida = get_post_meta($ciclo_id, '_gc_fecha_recogida', true);
    $pedidos_count = get_post_meta($ciclo_id, '_gc_pedidos_count', true) ?: 0;

    $grupo = $grupo_id ? get_post($grupo_id) : null;
?>

<div class="gc-single-ciclo">
    <article id="ciclo-<?php echo esc_attr($ciclo_id); ?>" class="gc-ciclo-article">
        <header class="gc-ciclo-header">
            <div class="gc-ciclo-info">
                <?php if ($grupo): ?>
                <p class="gc-ciclo-grupo">
                    <a href="<?php echo get_permalink($grupo->ID); ?>"><?php echo esc_html($grupo->post_title); ?></a>
                </p>
                <?php endif; ?>

                <h1 class="gc-ciclo-titulo"><?php the_title(); ?></h1>

                <div class="gc-ciclo-meta">
                    <span class="gc-badge gc-estado-<?php echo esc_attr($estado); ?>">
                        <?php
                        $estados_label = [
                            'abierto' => __('Abierto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'cerrado' => __('Cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'procesando' => __('Procesando', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'entregado' => __('Entregado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        ];
                        echo esc_html($estados_label[$estado] ?? ucfirst($estado));
                        ?>
                    </span>
                    <span class="gc-pedidos">
                        <span class="dashicons dashicons-cart"></span>
                        <?php printf(_n('%d pedido', '%d pedidos', $pedidos_count, FLAVOR_PLATFORM_TEXT_DOMAIN), $pedidos_count); ?>
                    </span>
                </div>
            </div>
        </header>

        <div class="gc-ciclo-fechas">
            <?php if ($fecha_cierre): ?>
            <div class="gc-fecha-item">
                <span class="gc-fecha-label"><?php _e('Cierre de pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="gc-fecha-valor"><?php echo esc_html(date_i18n('d M Y, H:i', strtotime($fecha_cierre))); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($fecha_recogida): ?>
            <div class="gc-fecha-item">
                <span class="gc-fecha-label"><?php _e('Fecha de recogida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="gc-fecha-valor"><?php echo esc_html(date_i18n('d M Y, H:i', strtotime($fecha_recogida))); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="gc-ciclo-body">
            <div class="gc-ciclo-contenido">
                <h2><?php _e('Informacion del ciclo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <div class="gc-ciclo-descripcion">
                    <?php the_content(); ?>
                </div>

                <h2><?php _e('Productos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <?php
                $productos = get_posts([
                    'post_type' => 'gc_producto',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        ['key' => '_gc_ciclo_id', 'value' => $ciclo_id],
                        ['key' => '_gc_disponible', 'value' => '1'],
                    ],
                ]);

                if ($productos):
                ?>
                <div class="gc-productos-grid">
                    <?php foreach ($productos as $producto):
                        $precio = get_post_meta($producto->ID, '_gc_precio', true);
                        $unidad = get_post_meta($producto->ID, '_gc_unidad', true) ?: 'ud';
                        $stock = get_post_meta($producto->ID, '_gc_stock', true);
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
                        <?php if ($stock !== ''): ?>
                        <p class="gc-producto-stock"><?php printf(__('Disponible: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($stock)); ?></p>
                        <?php endif; ?>
                        <a href="<?php echo get_permalink($producto->ID); ?>" class="gc-btn gc-btn-small">
                            <?php _e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p><?php _e('No hay productos disponibles en este ciclo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>

            <aside class="gc-ciclo-sidebar">
                <?php if ($estado === 'abierto' && is_user_logged_in()): ?>
                <div class="gc-card gc-card-pedir">
                    <h3><?php _e('Hacer pedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Selecciona los productos que deseas y realiza tu pedido.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(add_query_arg('ciclo_id', $ciclo_id, home_url('/grupos-consumo/mi-cesta/'))); ?>" class="gc-btn gc-btn-primary gc-btn-block">
                        <?php _e('Ir a mi cesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <?php endif; ?>
            </aside>
        </div>

        <nav class="gc-ciclo-nav">
            <?php if ($grupo): ?>
            <a href="<?php echo get_permalink($grupo->ID); ?>" class="gc-btn gc-btn-link">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Volver al grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <?php endif; ?>
        </nav>
    </article>
</div>

<?php
endwhile;
get_footer();
