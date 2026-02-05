<?php
/**
 * Vista Ventas - Marketplace
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Filtrar por ventas (anuncios de tipo venta)
$args = [
    'post_type' => 'marketplace_item',
    'post_status' => 'publish',
    'posts_per_page' => 50,
    'tax_query' => [
        [
            'taxonomy' => 'marketplace_tipo',
            'field' => 'slug',
            'terms' => 'venta'
        ]
    ],
    'orderby' => 'date',
    'order' => 'DESC'
];

$ventas_query = new WP_Query($args);

?>

<div class="wrap">
    <h1><span class="dashicons dashicons-cart"></span> Ventas del Marketplace</h1>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Vendedor</th>
                <th>Precio</th>
                <th>Estado</th>
                <th>Ubicación</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($ventas_query->have_posts()): ?>
                <?php while ($ventas_query->have_posts()): $ventas_query->the_post(); ?>
                    <?php
                    $precio = get_post_meta(get_the_ID(), '_marketplace_precio', true);
                    $estado_conservacion = get_post_meta(get_the_ID(), '_marketplace_estado', true);
                    $ubicacion = get_post_meta(get_the_ID(), '_marketplace_ubicacion', true);
                    ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo get_edit_post_link(); ?>"><?php the_title(); ?></a></strong>
                            <?php if (has_post_thumbnail()): ?>
                                <br><?php the_post_thumbnail('thumbnail'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php the_author(); ?></td>
                        <td><strong><?php echo $precio ? number_format($precio, 2) . ' €' : 'N/A'; ?></strong></td>
                        <td><?php echo $estado_conservacion ? ucfirst(str_replace('_', ' ', $estado_conservacion)) : 'N/A'; ?></td>
                        <td><?php echo $ubicacion ?: 'N/A'; ?></td>
                        <td><?php echo get_the_date('d/m/Y'); ?></td>
                        <td>
                            <a href="<?php echo get_edit_post_link(); ?>" class="button button-small">Editar</a>
                            <a href="<?php the_permalink(); ?>" class="button button-small" target="_blank">Ver</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px;">No hay ventas registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
