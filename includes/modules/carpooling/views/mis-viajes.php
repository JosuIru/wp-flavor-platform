<?php
/**
 * Vista: Mis viajes (conductor)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="carpooling-container">
    <div id="carpooling-mis-viajes" class="carpooling-mis-viajes">
        <div class="carpooling-mis-viajes__header">
            <h2 class="carpooling-mis-viajes__titulo"><?php esc_html_e('Mis viajes como conductor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('carpooling', 'publicar-viaje')); ?>" class="carpooling-btn carpooling-btn--primary carpooling-btn--sm">
                + <?php esc_html_e('Publicar viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>

        <div class="carpooling-tabs">
            <button type="button" class="carpooling-tab activo" data-estado="">
                <?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="publicado">
                <?php esc_html_e('Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="completo">
                <?php esc_html_e('Completos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="finalizado">
                <?php esc_html_e('Finalizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="cancelado">
                <?php esc_html_e('Cancelados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>

        <div id="carpooling-lista-mis-viajes">
            <!-- Cargado via AJAX -->
        </div>
    </div>
</div>
