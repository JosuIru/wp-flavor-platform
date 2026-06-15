<?php
/**
 * Plantilla frontend: listado de mis círculos de ahorro + alta de uno nuevo.
 *
 * Variables disponibles: $circulos (array de objetos).
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="flavor-ar">
    <h2 class="flavor-ar__titulo"><?php esc_html_e('Mis círculos de ahorro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

    <?php if (empty($circulos)) : ?>
        <p class="flavor-ar-aviso"><?php esc_html_e('Aún no participas en ningún círculo. Crea uno para empezar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    <?php else : ?>
        <ul class="flavor-ar__lista">
            <?php foreach ($circulos as $circulo) :
                $bote = (float) $circulo->importe_aportacion * (int) $circulo->num_plazas; ?>
                <li class="flavor-ar__card">
                    <a href="<?php echo esc_url(add_query_arg('circulo', (int) $circulo->id)); ?>">
                        <span class="flavor-ar__card-nombre"><?php echo esc_html($circulo->nombre); ?></span>
                        <span class="flavor-ar__card-meta">
                            <?php echo esc_html(number_format_i18n($circulo->importe_aportacion, 2)) . ' ' . esc_html($circulo->moneda); ?>
                            · <?php echo (int) $circulo->num_plazas; ?> <?php esc_html_e('plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            · <?php esc_html_e('bote', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html(number_format_i18n($bote, 2)); ?>
                        </span>
                        <span class="flavor-ar__estado flavor-ar__estado--<?php echo esc_attr($circulo->estado); ?>"><?php echo esc_html($circulo->estado); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <details class="flavor-ar__crear">
        <summary><?php esc_html_e('Crear un círculo nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="flavor-ar__form">
            <input type="hidden" name="action" value="flavor_ar_crear_circulo">
            <?php wp_nonce_field('flavor_ar_crear_circulo'); ?>
            <label><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <input type="text" name="nombre" required>
            </label>
            <label><?php esc_html_e('Aportación por periodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <input type="number" name="importe_aportacion" min="1" step="0.01" required>
            </label>
            <label><?php esc_html_e('Número de plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <input type="number" name="num_plazas" min="2" max="50" value="6" required>
            </label>
            <label><?php esc_html_e('Periodicidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <select name="periodo_dias">
                    <option value="7"><?php esc_html_e('Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="30" selected><?php esc_html_e('Mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </label>
            <label><?php esc_html_e('Orden de cobro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <select name="modo_orden">
                    <option value="fijo"><?php esc_html_e('Por inscripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="sorteo"><?php esc_html_e('Sorteo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </label>
            <button type="submit" class="button button-primary"><?php esc_html_e('Crear círculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </form>
    </details>
</div>
