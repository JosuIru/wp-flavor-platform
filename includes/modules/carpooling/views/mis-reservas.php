<?php
/**
 * Vista: Mis reservas (pasajero)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="carpooling-container">
    <div id="carpooling-mis-reservas" class="carpooling-mis-viajes">
        <div class="carpooling-mis-viajes__header">
            <h2 class="carpooling-mis-viajes__titulo"><?php esc_html_e('Mis reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <a href="<?php echo esc_url(home_url('/buscar-viaje/')); ?>" class="carpooling-btn carpooling-btn--primary carpooling-btn--sm">
                <?php esc_html_e('Buscar viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>

        <div class="carpooling-tabs">
            <button type="button" class="carpooling-tab activo" data-estado="">
                <?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="solicitada">
                <?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="confirmada">
                <?php esc_html_e('Confirmadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="completada">
                <?php esc_html_e('Completadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="cancelada">
                <?php esc_html_e('Canceladas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>

        <div id="carpooling-lista-mis-reservas">
            <!-- Cargado via AJAX -->
        </div>
    </div>
</div>
