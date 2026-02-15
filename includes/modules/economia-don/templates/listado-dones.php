<?php
/**
 * Template: Listado de Dones
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$args = [
    'post_type' => 'ed_don',
    'posts_per_page' => $atts['limite'],
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => '_ed_estado',
            'value' => 'disponible',
        ],
    ],
];

if (!empty($atts['categoria'])) {
    $args['meta_query'][] = [
        'key' => '_ed_categoria',
        'value' => $atts['categoria'],
    ];
}

$dones = new WP_Query($args);
$categorias = Flavor_Chat_Economia_Don_Module::CATEGORIAS_DON;
$estados = Flavor_Chat_Economia_Don_Module::ESTADOS_DON;
?>

<div class="ed-listado">
    <header class="ed-listado__header">
        <h2><?php esc_html_e('Economía del Don', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Dar y recibir sin esperar nada a cambio', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Filtros por categoría -->
    <div class="ed-filtros">
        <button class="ed-filtro-btn is-active" data-categoria="todos">
            <span class="dashicons dashicons-grid-view"></span>
            <?php esc_html_e('Todos', 'flavor-chat-ia'); ?>
        </button>
        <?php foreach ($categorias as $cat_id => $cat_data) : ?>
        <button class="ed-filtro-btn" data-categoria="<?php echo esc_attr($cat_id); ?>"
                style="--cat-color: <?php echo esc_attr($cat_data['color']); ?>">
            <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>"></span>
            <?php echo esc_html($cat_data['nombre']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <?php if (is_user_logged_in()) : ?>
    <div style="text-align: center; margin-bottom: 2rem;">
        <a href="<?php echo esc_url(home_url('/mi-portal/economia-don/ofrecer/')); ?>" class="ed-btn-publicar">
            <span class="dashicons dashicons-heart"></span>
            <?php esc_html_e('Ofrecer un don', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php endif; ?>

    <?php if ($dones->have_posts()) : ?>
    <div class="ed-listado__grid">
        <?php while ($dones->have_posts()) : $dones->the_post();
            $categoria = get_post_meta(get_the_ID(), '_ed_categoria', true);
            $cat_data = $categorias[$categoria] ?? $categorias['objetos'];
            $estado = get_post_meta(get_the_ID(), '_ed_estado', true) ?: 'disponible';
            $ubicacion = get_post_meta(get_the_ID(), '_ed_ubicacion', true);
            $anonimo = get_post_meta(get_the_ID(), '_ed_anonimo', true);
            $disponibilidad = get_post_meta(get_the_ID(), '_ed_disponibilidad', true);

            $donante_nombre = $anonimo
                ? __('Donante anónimo', 'flavor-chat-ia')
                : get_the_author();
        ?>
        <article class="ed-don-card" data-categoria="<?php echo esc_attr($categoria); ?>"
                 style="--don-color: <?php echo esc_attr($cat_data['color']); ?>">

            <div class="ed-don-card__imagen">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium'); ?>
                <?php else : ?>
                    <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>"></span>
                <?php endif; ?>

                <span class="ed-don-card__categoria"><?php echo esc_html($cat_data['nombre']); ?></span>
                <span class="ed-don-card__estado ed-don-card__estado--<?php echo esc_attr($estado); ?>">
                    <?php echo esc_html($estados[$estado]['nombre']); ?>
                </span>
            </div>

            <div class="ed-don-card__body">
                <h3 class="ed-don-card__titulo">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>

                <div class="ed-don-card__excerpt">
                    <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                </div>

                <div class="ed-don-card__meta">
                    <?php if ($ubicacion) : ?>
                    <span>
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($ubicacion); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($disponibilidad) : ?>
                    <span>
                        <span class="dashicons dashicons-clock"></span>
                        <?php echo esc_html($disponibilidad); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <footer class="ed-don-card__footer">
                    <div class="ed-don-card__donante">
                        <?php echo get_avatar(get_the_author_meta('ID'), 28, '', '', ['class' => 'ed-don-card__donante-avatar']); ?>
                        <span><?php echo esc_html($donante_nombre); ?></span>
                    </div>

                    <?php if (is_user_logged_in() && get_current_user_id() != get_the_author_meta('ID')) : ?>
                        <?php if ($estado === 'disponible') : ?>
                        <button class="ed-btn-solicitar" data-don="<?php echo esc_attr(get_the_ID()); ?>">
                            <span class="dashicons dashicons-heart"></span>
                            <?php esc_html_e('Lo quiero', 'flavor-chat-ia'); ?>
                        </button>
                        <?php endif; ?>
                    <?php elseif (!is_user_logged_in()) : ?>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="ed-btn-solicitar">
                            <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </footer>
            </div>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php else : ?>
    <div class="ed-empty-state">
        <span class="dashicons dashicons-heart"></span>
        <p><?php esc_html_e('No hay dones disponibles aún. ¡Sé el primero en ofrecer algo!', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>
</div>
