<?php
/**
 * Template: Listado de Círculos de Cuidados
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$args = [
    'post_type' => 'cc_circulo',
    'posts_per_page' => $atts['limite'],
    'post_status' => 'publish',
];

if (!empty($atts['tipo'])) {
    $args['meta_query'] = [
        ['key' => '_cc_tipo', 'value' => $atts['tipo']],
    ];
}

$circulos = new WP_Query($args);
$circulos_cuidados_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Circulos_Cuidados_Module')
    : 'Flavor_Chat_Circulos_Cuidados_Module';
$tipos = $circulos_cuidados_module_class::TIPOS_CIRCULO;
?>

<div class="cc-listado">
    <header class="cc-listado__header">
        <h2><?php esc_html_e('Círculos de Cuidados', 'flavor-platform'); ?></h2>
        <p><?php esc_html_e('Redes de apoyo mutuo para cuidarnos entre todas', 'flavor-platform'); ?></p>
    </header>

    <?php if ($circulos->have_posts()) : ?>
    <div class="cc-listado__grid">
        <?php while ($circulos->have_posts()) : $circulos->the_post();
            $tipo = get_post_meta(get_the_ID(), '_cc_tipo', true);
            $tipo_data = $tipos[$tipo] ?? $tipos['mayores'];
            $miembros = get_post_meta(get_the_ID(), '_cc_miembros', true) ?: [];
            $max_miembros = get_post_meta(get_the_ID(), '_cc_max_miembros', true) ?: 15;
            $zona = get_post_meta(get_the_ID(), '_cc_zona', true);
        ?>
        <article class="cc-circulo-card" style="--cc-color: <?php echo esc_attr($tipo_data['color']); ?>">
            <header class="cc-circulo-card__header">
                <span class="cc-circulo-card__icono dashicons <?php echo esc_attr($tipo_data['icono']); ?>"></span>
                <span class="cc-circulo-card__tipo"><?php echo esc_html($tipo_data['nombre']); ?></span>
            </header>

            <h3 class="cc-circulo-card__titulo">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>

            <?php if ($zona) : ?>
            <p class="cc-circulo-card__zona">
                <span class="dashicons dashicons-location"></span>
                <?php echo esc_html($zona); ?>
            </p>
            <?php endif; ?>

            <div class="cc-circulo-card__excerpt">
                <?php the_excerpt(); ?>
            </div>

            <footer class="cc-circulo-card__footer">
                <span class="cc-circulo-card__miembros">
                    <span class="dashicons dashicons-groups"></span>
                    <?php printf(
                        esc_html__('%d/%d miembros', 'flavor-platform'),
                        count($miembros),
                        $max_miembros
                    ); ?>
                </span>

                <?php if (is_user_logged_in() && !in_array(get_current_user_id(), $miembros)) : ?>
                <button class="cc-btn-unirse" data-circulo="<?php echo esc_attr(get_the_ID()); ?>">
                    <?php esc_html_e('Unirme', 'flavor-platform'); ?>
                </button>
                <?php elseif (in_array(get_current_user_id(), $miembros)) : ?>
                <span class="cc-badge-miembro"><?php esc_html_e('Eres miembro', 'flavor-platform'); ?></span>
                <?php endif; ?>
            </footer>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php else : ?>
    <div class="cc-empty-state">
        <span class="dashicons dashicons-heart"></span>
        <p><?php esc_html_e('No hay círculos de cuidados aún. ¡Crea el primero!', 'flavor-platform'); ?></p>
    </div>
    <?php endif; ?>
</div>
