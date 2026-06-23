<?php
/**
 * Vista admin: resumen del módulo Ahorro Rotativo (panel unificado).
 *
 * Variables disponibles: $estadisticas (array).
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap flavor-ar-admin">
    <h1><?php esc_html_e('Ahorro Rotativo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <p class="description">
        <?php esc_html_e('Panderos y círculos de ahorro rotativo. La plataforma coordina y da transparencia; no custodia dinero.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </p>

    <div class="flavor-ar-admin__stats">
        <div class="flavor-ar-admin__stat">
            <span class="flavor-ar-admin__num"><?php echo (int) ($estadisticas['circulos_activos'] ?? 0); ?></span>
            <span class="flavor-ar-admin__label"><?php esc_html_e('Círculos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-ar-admin__stat">
            <span class="flavor-ar-admin__num"><?php echo (int) ($estadisticas['circulos_totales'] ?? 0); ?></span>
            <span class="flavor-ar-admin__label"><?php esc_html_e('Círculos totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-ar-admin__stat">
            <span class="flavor-ar-admin__num"><?php echo (int) ($estadisticas['participantes'] ?? 0); ?></span>
            <span class="flavor-ar-admin__label"><?php esc_html_e('Participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>
</div>
