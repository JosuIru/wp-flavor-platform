<?php
/**
 * Template: Ofrecer Don
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fallback: asegurar assets frontend del módulo cuando se renderiza fuera de su flujo principal.
if (!wp_style_is('flavor-economia-don', 'registered')) {
    wp_register_style(
        'flavor-economia-don',
        FLAVOR_CHAT_IA_URL . 'includes/modules/economia-don/assets/css/economia-don-frontend.css',
        [],
        FLAVOR_CHAT_IA_VERSION
    );
}
if (!wp_style_is('flavor-economia-don', 'enqueued')) {
    wp_enqueue_style('flavor-economia-don');
}
if (!wp_script_is('flavor-economia-don', 'registered')) {
    wp_register_script(
        'flavor-economia-don',
        FLAVOR_CHAT_IA_URL . 'includes/modules/economia-don/assets/js/economia-don.js',
        ['jquery'],
        FLAVOR_CHAT_IA_VERSION,
        true
    );
}
if (!wp_script_is('flavor-economia-don', 'enqueued')) {
    wp_enqueue_script('flavor-economia-don');
}
wp_localize_script('flavor-economia-don', 'edData', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('ed_nonce'),
    'i18n' => [
        'confirmSolicitar' => __('¿Deseas solicitar este don?', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'confirmEntrega' => __('¿Confirmas que has entregado este don?', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'gracias' => __('¡Gracias por tu generosidad!', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ],
]);

$categorias = Flavor_Chat_Economia_Don_Module::CATEGORIAS_DON;
?>

<div class="ed-ofrecer-form">
    <header class="ed-ofrecer-form__header">
        <h2><?php esc_html_e('Ofrecer un don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Comparte lo que te sobra o puedes ofrecer sin esperar nada a cambio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <form class="ed-form-ofrecer" method="post">
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('ed_nonce')); ?>">
        <div class="ed-form-grupo">
            <label for="ed-titulo"><?php esc_html_e('¿Qué ofreces?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
            <input type="text" name="titulo" id="ed-titulo" required
                   placeholder="<?php esc_attr_e('Ej: Bicicleta infantil, Clases de guitarra, Comida casera...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>

        <div class="ed-form-grupo">
            <label><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
            <div class="ed-categorias-selector">
                <?php $first = true; foreach ($categorias as $cat_id => $cat_data) : ?>
                <div class="ed-categoria-opcion">
                    <input type="radio" name="categoria" id="cat-<?php echo esc_attr($cat_id); ?>"
                           value="<?php echo esc_attr($cat_id); ?>" <?php checked($first); ?>>
                    <label for="cat-<?php echo esc_attr($cat_id); ?>"
                           style="--cat-color: <?php echo esc_attr($cat_data['color']); ?>">
                        <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>"></span>
                        <span><?php echo esc_html($cat_data['nombre']); ?></span>
                    </label>
                </div>
                <?php $first = false; endforeach; ?>
            </div>
        </div>

        <div class="ed-form-grupo">
            <label for="ed-descripcion"><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <textarea name="descripcion" id="ed-descripcion" rows="4"
                      placeholder="<?php esc_attr_e('Describe lo que ofreces: estado, características, por qué lo regalas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
        </div>

        <div class="ed-form-grupo">
            <label for="ed-ubicacion"><?php esc_html_e('Zona/Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <input type="text" name="ubicacion" id="ed-ubicacion"
                   placeholder="<?php esc_attr_e('Ej: Centro, Barrio Norte, Pueblo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>

        <div class="ed-form-grupo">
            <label for="ed-disponibilidad"><?php esc_html_e('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <input type="text" name="disponibilidad" id="ed-disponibilidad"
                   placeholder="<?php esc_attr_e('Ej: Tardes de 17-20h, Fines de semana...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>

        <div class="ed-form-grupo">
            <div class="ed-checkbox-grupo">
                <input type="checkbox" name="anonimo" id="ed-anonimo" value="1">
                <label for="ed-anonimo"><?php esc_html_e('Quiero hacer esta donación de forma anónima', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            </div>
        </div>

        <div class="ed-ofrecer-form__submit">
            <button type="submit" class="ed-btn-publicar">
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e('Publicar don', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </form>
</div>
