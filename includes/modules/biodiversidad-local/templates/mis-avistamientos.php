<?php
/**
 * Template: Mis Avistamientos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="bl-empty-state"><p>' . esc_html__('Debes iniciar sesión para ver tus avistamientos.', 'flavor-chat-ia') . '</p></div>';
    return;
}

$user_id = get_current_user_id();
$categorias = Flavor_Chat_Biodiversidad_Local_Module::CATEGORIAS_ESPECIES;

$mis_avistamientos = get_posts([
    'post_type' => 'bl_avistamiento',
    'author' => $user_id,
    'posts_per_page' => 50,
    'post_status' => ['publish', 'pending'],
    'orderby' => 'date',
    'order' => 'DESC',
]);

$publicados = array_filter($mis_avistamientos, fn($a) => $a->post_status === 'publish');
$pendientes = array_filter($mis_avistamientos, fn($a) => $a->post_status === 'pending');
?>

<div class="bl-container">
    <header class="bl-header">
        <h2><?php esc_html_e('Mis Avistamientos', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Tu contribución al catálogo de biodiversidad local', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Estadísticas personales -->
    <div class="bl-stats-bar">
        <div class="bl-stat-item">
            <div class="bl-stat-item__valor"><?php echo esc_html(count($mis_avistamientos)); ?></div>
            <div class="bl-stat-item__label"><?php esc_html_e('Total', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="bl-stat-item">
            <div class="bl-stat-item__valor" style="color: #22c55e"><?php echo esc_html(count($publicados)); ?></div>
            <div class="bl-stat-item__label"><?php esc_html_e('Validados', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="bl-stat-item">
            <div class="bl-stat-item__valor" style="color: #f59e0b"><?php echo esc_html(count($pendientes)); ?></div>
            <div class="bl-stat-item__label"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <?php if ($mis_avistamientos) : ?>
    <div class="bl-avistamientos-lista">
        <?php foreach ($mis_avistamientos as $avistamiento) :
            $especie_id = get_post_meta($avistamiento->ID, '_bl_especie_id', true);
            $especie = get_post($especie_id);
            $especie_nombre = $especie ? $especie->post_title : __('Especie no identificada', 'flavor-chat-ia');

            $terms = $especie ? wp_get_post_terms($especie->ID, 'bl_categoria') : [];
            $cat_slug = !empty($terms) ? $terms[0]->slug : '';
            $cat_data = $categorias[$cat_slug] ?? ['nombre' => 'Otro', 'color' => '#6b7280'];

            $fecha = get_post_meta($avistamiento->ID, '_bl_fecha', true);
            $cantidad = get_post_meta($avistamiento->ID, '_bl_cantidad', true) ?: 1;
            $validaciones = get_post_meta($avistamiento->ID, '_bl_validaciones', true) ?: [];
            $positivas = count(array_filter($validaciones, fn($v) => $v['es_valido']));

            $es_pendiente = $avistamiento->post_status === 'pending';
        ?>
        <article class="bl-avistamiento-item" style="<?php echo $es_pendiente ? 'opacity: 0.7;' : ''; ?>">
            <div class="bl-avistamiento-item__imagen" style="border: 3px solid <?php echo esc_attr($cat_data['color']); ?>">
                <?php if (has_post_thumbnail($avistamiento->ID)) : ?>
                    <?php echo get_the_post_thumbnail($avistamiento->ID, 'thumbnail'); ?>
                <?php else : ?>
                    <span class="dashicons dashicons-camera" style="color: <?php echo esc_attr($cat_data['color']); ?>"></span>
                <?php endif; ?>
            </div>

            <div class="bl-avistamiento-item__body">
                <div class="bl-avistamiento-item__especie">
                    <?php echo esc_html($especie_nombre); ?>
                    <?php if ($es_pendiente) : ?>
                    <span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: 0.5rem;">
                        <?php esc_html_e('Pendiente', 'flavor-chat-ia'); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="bl-avistamiento-item__meta">
                    <span><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html(date_i18n('j M Y', strtotime($fecha ?: $avistamiento->post_date))); ?></span>
                    <span><span class="dashicons dashicons-groups"></span> <?php echo esc_html($cantidad); ?> <?php esc_html_e('ejemplar(es)', 'flavor-chat-ia'); ?></span>
                    <?php if (!empty($validaciones)) : ?>
                    <span><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html($positivas); ?>/<?php echo esc_html(count($validaciones)); ?> <?php esc_html_e('validaciones', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($avistamiento->post_content) : ?>
                <p style="margin-top: 0.5rem; color: var(--bl-text-light); font-size: 0.9rem;">
                    <?php echo esc_html(wp_trim_words($avistamiento->post_content, 20)); ?>
                </p>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="bl-empty-state">
        <span class="dashicons dashicons-camera"></span>
        <p><?php esc_html_e('Aún no has registrado ningún avistamiento.', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url(home_url('/biodiversidad/registrar/')); ?>" class="bl-btn bl-btn--primary">
            <?php esc_html_e('Registrar mi primer avistamiento', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- Enlace a nuevo registro -->
    <?php if ($mis_avistamientos) : ?>
    <div style="text-align: center; margin-top: 2rem;">
        <a href="<?php echo esc_url(home_url('/biodiversidad/registrar/')); ?>" class="bl-btn bl-btn--primary">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Nuevo avistamiento', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php endif; ?>
</div>
