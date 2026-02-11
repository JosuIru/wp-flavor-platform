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
    <h1><span class="dashicons dashicons-cart"></span> <?php echo esc_html__('Ventas del Marketplace', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Producto', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Vendedor', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Ubicación', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
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
                            <a href="<?php echo get_edit_post_link(); ?>" class="button button-small"><?php echo esc_html__('Editar', 'flavor-chat-ia'); ?></a>
                            <a href="<?php the_permalink(); ?>" class="button button-small" target="_blank"><?php echo esc_html__('Ver', 'flavor-chat-ia'); ?></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px;"><?php echo esc_html__('No hay ventas registradas', 'flavor-chat-ia'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
