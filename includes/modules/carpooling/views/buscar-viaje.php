<?php
/**
 * Vista: Buscar viaje
 */

if (!defined('ABSPATH')) {
    exit;
}

$radio_defecto = $atts['radio_defecto'] ?? 5;
$mostrar_mapa = $atts['mostrar_mapa'] === 'true';
?>

<div class="carpooling-container">
    <div class="carpooling-buscador">
        <h2 class="carpooling-buscador__titulo"><?php esc_html_e('Buscar viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

        <form id="carpooling-form-buscar" class="carpooling-buscador__form">
            <div class="carpooling-campo carpooling-autocomplete">
                <label class="carpooling-campo__label"><?php esc_html_e('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text" name="origen" class="carpooling-campo__input carpooling-autocomplete__input" placeholder="<?php esc_attr_e('Ciudad o direccion de salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" autocomplete="off">
                <input type="hidden" name="origen_lat">
                <input type="hidden" name="origen_lng">
                <input type="hidden" name="origen_place_id">
                <div class="carpooling-autocomplete__lista"></div>
            </div>

            <div class="carpooling-campo carpooling-autocomplete">
                <label class="carpooling-campo__label"><?php esc_html_e('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text" name="destino" class="carpooling-campo__input carpooling-autocomplete__input" placeholder="<?php esc_attr_e('Ciudad o direccion de llegada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" autocomplete="off">
                <input type="hidden" name="destino_lat">
                <input type="hidden" name="destino_lng">
                <input type="hidden" name="destino_place_id">
                <div class="carpooling-autocomplete__lista"></div>
            </div>

            <div class="carpooling-campo">
                <label class="carpooling-campo__label"><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="date" name="fecha" class="carpooling-campo__input" value="<?php echo esc_attr(date('Y-m-d')); ?>" min="<?php echo esc_attr(date('Y-m-d')); ?>">
            </div>

            <div class="carpooling-campo">
                <label class="carpooling-campo__label"><?php esc_html_e('Plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="plazas" class="carpooling-campo__select">
                    <option value="1">1 <?php esc_html_e('pasajero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="2">2 <?php esc_html_e('pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="3">3 <?php esc_html_e('pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="4">4 <?php esc_html_e('pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <div class="carpooling-campo">
                <label class="carpooling-campo__label"><?php echo esc_html__('&nbsp;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <button type="submit" class="carpooling-btn carpooling-btn--primary carpooling-btn--lg">
                    <?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>

    <div id="carpooling-resultados">
        <div class="carpooling-empty">
            <div class="carpooling-empty__icono">🔍</div>
            <h3 class="carpooling-empty__titulo"><?php esc_html_e('Encuentra tu viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="carpooling-empty__texto"><?php esc_html_e('Introduce origen, destino y fecha para buscar viajes disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>
</div>
