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
            <h2 class="carpooling-mis-viajes__titulo"><?php esc_html_e('Mis reservas', 'flavor-chat-ia'); ?></h2>
            <a href="<?php echo esc_url(home_url('/buscar-viaje/')); ?>" class="carpooling-btn carpooling-btn--primary carpooling-btn--sm">
                <?php esc_html_e('Buscar viajes', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <div class="carpooling-tabs">
            <button type="button" class="carpooling-tab activo" data-estado="">
                <?php esc_html_e('Todas', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="solicitada">
                <?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="confirmada">
                <?php esc_html_e('Confirmadas', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="completada">
                <?php esc_html_e('Completadas', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="carpooling-tab" data-estado="cancelada">
                <?php esc_html_e('Canceladas', 'flavor-chat-ia'); ?>
            </button>
        </div>

        <div id="carpooling-lista-mis-reservas">
            <!-- Cargado via AJAX -->
        </div>
    </div>
</div>
