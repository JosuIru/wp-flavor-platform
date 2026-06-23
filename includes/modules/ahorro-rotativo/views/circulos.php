<?php
/**
 * Vista admin: tabla de círculos (panel unificado).
 *
 * Variables disponibles: $circulos (array de objetos).
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap flavor-ar-admin">
    <h1><?php esc_html_e('Círculos de ahorro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <?php if (empty($circulos)) : ?>
        <p><?php esc_html_e('Todavía no hay círculos creados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Aportación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Periodo (días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($circulos as $circulo) : ?>
                    <tr>
                        <td><?php echo esc_html($circulo->nombre); ?></td>
                        <td><?php echo esc_html(number_format_i18n($circulo->importe_aportacion, 2)) . ' ' . esc_html($circulo->moneda); ?></td>
                        <td><?php echo (int) $circulo->num_plazas; ?></td>
                        <td><?php echo (int) $circulo->periodo_dias; ?></td>
                        <td><?php echo esc_html($circulo->estado); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
