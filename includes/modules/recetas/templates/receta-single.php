<?php
/**
 * Template para mostrar una receta individual
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables disponibles: $receta (WP_Post)

$imagen = get_the_post_thumbnail_url($receta->ID, 'large');
$tiempo_preparacion = get_post_meta($receta->ID, '_receta_tiempo_preparacion', true);
$tiempo_coccion = get_post_meta($receta->ID, '_receta_tiempo_coccion', true);
$tiempo_total = intval($tiempo_preparacion) + intval($tiempo_coccion);
$porciones = get_post_meta($receta->ID, '_receta_porciones', true);
$dificultad = get_post_meta($receta->ID, '_receta_dificultad', true);
$calorias = get_post_meta($receta->ID, '_receta_calorias', true);
$ingredientes = get_post_meta($receta->ID, '_receta_ingredientes', true);
$pasos = get_post_meta($receta->ID, '_receta_pasos', true);
$categorias = wp_get_post_terms($receta->ID, 'receta_categoria', ['fields' => 'names']);
$dietas = wp_get_post_terms($receta->ID, 'receta_dieta', ['fields' => 'names']);

$dificultad_labels = [
    'facil' => __('Facil', 'flavor-platform'),
    'media' => __('Media', 'flavor-platform'),
    'dificil' => __('Dificil', 'flavor-platform'),
];
?>

<article class="flavor-receta-single">
    <?php if ($imagen): ?>
    <div class="receta-imagen" style="margin-bottom: 20px;">
        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($receta->post_title); ?>" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px;" />
    </div>
    <?php endif; ?>

    <header class="receta-header" style="margin-bottom: 30px;">
        <h1 style="margin: 0 0 15px; font-size: 28px;"><?php echo esc_html($receta->post_title); ?></h1>

        <?php if (!empty($categorias) || !empty($dietas)): ?>
        <div class="receta-tags" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px;">
            <?php foreach ($categorias as $categoria): ?>
                <span style="background: #0073aa; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px;">
                    <?php echo esc_html($categoria); ?>
                </span>
            <?php endforeach; ?>
            <?php foreach ($dietas as $dieta): ?>
                <span style="background: #00a32a; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px;">
                    <?php echo esc_html($dieta); ?>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="receta-meta" style="display: flex; flex-wrap: wrap; gap: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <?php if ($tiempo_total > 0): ?>
            <div class="meta-item">
                <span class="dashicons dashicons-clock" style="color: #0073aa;"></span>
                <strong><?php _e('Tiempo total:', 'flavor-platform'); ?></strong>
                <?php echo $tiempo_total; ?> min
                <?php if ($tiempo_preparacion && $tiempo_coccion): ?>
                    <small style="color: #666;">(<?php echo $tiempo_preparacion; ?> prep + <?php echo $tiempo_coccion; ?> coccion)</small>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($porciones): ?>
            <div class="meta-item">
                <span class="dashicons dashicons-groups" style="color: #0073aa;"></span>
                <strong><?php _e('Porciones:', 'flavor-platform'); ?></strong>
                <?php echo esc_html($porciones); ?>
            </div>
            <?php endif; ?>

            <?php if ($dificultad && isset($dificultad_labels[$dificultad])): ?>
            <div class="meta-item">
                <span class="dashicons dashicons-chart-bar" style="color: #0073aa;"></span>
                <strong><?php _e('Dificultad:', 'flavor-platform'); ?></strong>
                <?php echo esc_html($dificultad_labels[$dificultad]); ?>
            </div>
            <?php endif; ?>

            <?php if ($calorias): ?>
            <div class="meta-item">
                <span class="dashicons dashicons-heart" style="color: #0073aa;"></span>
                <strong><?php _e('Calorias:', 'flavor-platform'); ?></strong>
                <?php echo esc_html($calorias); ?> kcal/porcion
            </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($receta->post_content): ?>
    <div class="receta-descripcion" style="margin-bottom: 30px; font-size: 16px; line-height: 1.6;">
        <?php echo wpautop($receta->post_content); ?>
    </div>
    <?php endif; ?>

    <div class="receta-contenido" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">

        <?php if (!empty($ingredientes) && is_array($ingredientes)): ?>
        <aside class="receta-ingredientes" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin: 0 0 15px; font-size: 20px; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">
                <span class="dashicons dashicons-carrot" style="color: #0073aa;"></span>
                <?php _e('Ingredientes', 'flavor-platform'); ?>
            </h2>
            <ul style="list-style: none; margin: 0; padding: 0;">
                <?php foreach ($ingredientes as $ingrediente): ?>
                <li style="padding: 8px 0; border-bottom: 1px solid #eee; display: flex; gap: 10px;">
                    <span class="ingrediente-cantidad" style="font-weight: bold; min-width: 80px;">
                        <?php
                        if (!empty($ingrediente['cantidad'])) {
                            echo esc_html($ingrediente['cantidad']);
                            if (!empty($ingrediente['unidad'])) {
                                echo ' ' . esc_html($ingrediente['unidad']);
                            }
                        }
                        ?>
                    </span>
                    <span class="ingrediente-nombre">
                        <?php echo esc_html($ingrediente['nombre']); ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <?php endif; ?>

        <?php if (!empty($pasos) && is_array($pasos)): ?>
        <div class="receta-pasos" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin: 0 0 15px; font-size: 20px; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">
                <span class="dashicons dashicons-editor-ol" style="color: #0073aa;"></span>
                <?php _e('Preparacion', 'flavor-platform'); ?>
            </h2>
            <ol style="margin: 0; padding: 0; list-style: none; counter-reset: paso;">
                <?php foreach ($pasos as $indice => $paso): ?>
                <li style="padding: 15px 0; border-bottom: 1px solid #eee; display: flex; gap: 15px; align-items: flex-start;">
                    <span style="background: #0073aa; color: white; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: bold;">
                        <?php echo $indice + 1; ?>
                    </span>
                    <div style="flex: 1; line-height: 1.6;">
                        <?php echo nl2br(esc_html($paso)); ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>

    </div>

    <?php
    // Mostrar productos vinculados
    $productos_ids = get_post_meta($receta->ID, '_receta_productos_vinculados', true);
    if (!empty($productos_ids) && is_array($productos_ids) && class_exists('WooCommerce')):
        $productos = wc_get_products(['include' => $productos_ids, 'limit' => -1]);
        if (!empty($productos)):
    ?>
    <div class="receta-productos" style="margin-top: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 15px; font-size: 20px; border-bottom: 2px solid #00a32a; padding-bottom: 10px;">
            <span class="dashicons dashicons-cart" style="color: #00a32a;"></span>
            <?php _e('Comprar Ingredientes', 'flavor-platform'); ?>
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px;">
            <?php foreach ($productos as $producto):
                $imagen_producto = wp_get_attachment_image_url($producto->get_image_id(), 'thumbnail');
            ?>
            <a href="<?php echo get_permalink($producto->get_id()); ?>" style="display: block; text-decoration: none; color: inherit; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; transition: box-shadow 0.2s;">
                <?php if ($imagen_producto): ?>
                <img src="<?php echo esc_url($imagen_producto); ?>" alt="<?php echo esc_attr($producto->get_name()); ?>" style="width: 100%; height: 120px; object-fit: cover;" />
                <?php endif; ?>
                <div style="padding: 10px;">
                    <h4 style="margin: 0 0 5px; font-size: 14px;"><?php echo esc_html($producto->get_name()); ?></h4>
                    <span style="color: #00a32a; font-weight: bold;"><?php echo $producto->get_price_html(); ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
        endif;
    endif;
    ?>

</article>

<style>
.flavor-receta-single .meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}
.flavor-receta-single .meta-item .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}
@media (max-width: 768px) {
    .flavor-receta-single .receta-contenido {
        grid-template-columns: 1fr !important;
    }
}
</style>
