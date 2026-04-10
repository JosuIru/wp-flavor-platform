<?php
/**
 * Template: Formulario de busqueda de viajes
 *
 * Formulario completo para buscar viajes compartidos con filtros de
 * origen, destino, fecha y numero de plazas.
 *
 * @package FlavorPlatform
 * @subpackage Modules/Carpooling
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener valores de busqueda anteriores si existen
$origen_anterior = isset($_GET['origen']) ? sanitize_text_field($_GET['origen']) : '';
$destino_anterior = isset($_GET['destino']) ? sanitize_text_field($_GET['destino']) : '';
$fecha_anterior = isset($_GET['fecha']) ? sanitize_text_field($_GET['fecha']) : '';
$plazas_anteriores = isset($_GET['plazas']) ? absint($_GET['plazas']) : 1;

// Fecha minima: hoy
$fecha_minima = date('Y-m-d');
// Fecha maxima: 30 dias adelante (configurable)
$dias_anticipacion = apply_filters('carpooling_dias_anticipacion_maxima', 30);
$fecha_maxima = date('Y-m-d', strtotime("+{$dias_anticipacion} days"));
?>

<div class="flavor-carpooling-buscar carpooling-container">
    <header class="flavor-carpooling-buscar__header">
        <h2 class="flavor-carpooling-buscar__titulo">
            <?php esc_html_e('Buscar viajes compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>
        <p class="flavor-carpooling-buscar__subtitulo">
            <?php esc_html_e('Encuentra viajes que se adapten a tu ruta y horario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </header>

    <form class="carpooling-form flavor-carpooling-buscar__form" id="carpooling-buscar-form" method="get">
        <?php wp_nonce_field('carpooling_buscar_nonce', 'carpooling_nonce'); ?>

        <!-- Origen y Destino -->
        <div class="carpooling-form-row">
            <div class="carpooling-form-group">
                <label for="carpooling-origen">
                    <span class="dashicons dashicons-location"></span>
                    <?php esc_html_e('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="required">*</span>
                </label>
                <input
                    type="text"
                    name="origen"
                    id="carpooling-origen"
                    class="flavor-input flavor-input--with-icon"
                    value="<?php echo esc_attr($origen_anterior); ?>"
                    placeholder="<?php esc_attr_e('Ciudad o direccion de salida...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    autocomplete="off"
                    required
                />
                <input type="hidden" name="origen_lat" id="carpooling-origen-lat" value="" />
                <input type="hidden" name="origen_lng" id="carpooling-origen-lng" value="" />
            </div>

            <div class="carpooling-form-group">
                <label for="carpooling-destino">
                    <span class="dashicons dashicons-flag"></span>
                    <?php esc_html_e('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="required">*</span>
                </label>
                <input
                    type="text"
                    name="destino"
                    id="carpooling-destino"
                    class="flavor-input flavor-input--with-icon"
                    value="<?php echo esc_attr($destino_anterior); ?>"
                    placeholder="<?php esc_attr_e('Ciudad o direccion de llegada...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    autocomplete="off"
                    required
                />
                <input type="hidden" name="destino_lat" id="carpooling-destino-lat" value="" />
                <input type="hidden" name="destino_lng" id="carpooling-destino-lng" value="" />
            </div>
        </div>

        <!-- Boton intercambiar origen/destino -->
        <div class="flavor-carpooling-buscar__swap">
            <button type="button" class="cp-btn cp-btn-outline" id="carpooling-swap-btn" title="<?php esc_attr_e('Intercambiar origen y destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-randomize"></span>
            </button>
        </div>

        <!-- Fecha y Plazas -->
        <div class="carpooling-form-row">
            <div class="carpooling-form-group">
                <label for="carpooling-fecha">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Fecha del viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="required">*</span>
                </label>
                <input
                    type="date"
                    name="fecha"
                    id="carpooling-fecha"
                    class="flavor-input"
                    value="<?php echo esc_attr($fecha_anterior ?: $fecha_minima); ?>"
                    min="<?php echo esc_attr($fecha_minima); ?>"
                    max="<?php echo esc_attr($fecha_maxima); ?>"
                    required
                />
            </div>

            <div class="carpooling-form-group">
                <label for="carpooling-plazas">
                    <span class="dashicons dashicons-groups"></span>
                    <?php esc_html_e('Plazas necesarias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
                <select name="plazas" id="carpooling-plazas" class="flavor-select">
                    <?php for ($plazas_contador = 1; $plazas_contador <= 4; $plazas_contador++) : ?>
                        <option value="<?php echo esc_attr($plazas_contador); ?>" <?php selected($plazas_anteriores, $plazas_contador); ?>>
                            <?php echo esc_html($plazas_contador); ?> <?php echo esc_html(_n('plaza', 'plazas', $plazas_contador, FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <!-- Filtros avanzados (colapsables) -->
        <details class="flavor-carpooling-buscar__filtros-avanzados">
            <summary class="flavor-carpooling-buscar__filtros-toggle">
                <span class="dashicons dashicons-filter"></span>
                <?php esc_html_e('Filtros avanzados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </summary>

            <div class="flavor-carpooling-buscar__filtros-contenido">
                <div class="carpooling-form-row">
                    <div class="carpooling-form-group">
                        <label for="carpooling-hora-desde">
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e('Hora desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input
                            type="time"
                            name="hora_desde"
                            id="carpooling-hora-desde"
                            class="flavor-input"
                        />
                    </div>

                    <div class="carpooling-form-group">
                        <label for="carpooling-hora-hasta">
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e('Hora hasta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input
                            type="time"
                            name="hora_hasta"
                            id="carpooling-hora-hasta"
                            class="flavor-input"
                        />
                    </div>
                </div>

                <div class="carpooling-form-row">
                    <div class="carpooling-form-group">
                        <label for="carpooling-precio-max">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php esc_html_e('Precio maximo por plaza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input
                            type="number"
                            name="precio_max"
                            id="carpooling-precio-max"
                            class="flavor-input"
                            min="0"
                            step="0.50"
                            placeholder="<?php esc_attr_e('Sin limite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                        />
                    </div>

                    <div class="carpooling-form-group">
                        <label for="carpooling-radio">
                            <span class="dashicons dashicons-location-alt"></span>
                            <?php esc_html_e('Radio de busqueda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <select name="radio_km" id="carpooling-radio" class="flavor-select">
                            <option value="5"><?php esc_html_e('5 km', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="10" selected><?php esc_html_e('10 km', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="20"><?php esc_html_e('20 km', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="50"><?php esc_html_e('50 km', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Preferencias del viaje -->
                <div class="flavor-carpooling-buscar__preferencias">
                    <label class="flavor-carpooling-buscar__preferencias-titulo">
                        <?php esc_html_e('Preferencias del viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <div class="flavor-carpooling-buscar__preferencias-grid">
                        <label class="flavor-checkbox">
                            <input type="checkbox" name="permite_mascotas" value="1" />
                            <span class="flavor-checkbox__mark"></span>
                            <span class="flavor-checkbox__label">
                                <span class="dashicons dashicons-pets"></span>
                                <?php esc_html_e('Acepta mascotas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </label>

                        <label class="flavor-checkbox">
                            <input type="checkbox" name="permite_equipaje_grande" value="1" />
                            <span class="flavor-checkbox__mark"></span>
                            <span class="flavor-checkbox__label">
                                <span class="dashicons dashicons-portfolio"></span>
                                <?php esc_html_e('Equipaje grande', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </label>

                        <label class="flavor-checkbox">
                            <input type="checkbox" name="no_fumador" value="1" />
                            <span class="flavor-checkbox__mark"></span>
                            <span class="flavor-checkbox__label">
                                <span class="dashicons dashicons-no"></span>
                                <?php esc_html_e('Sin fumar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </details>

        <!-- Boton buscar -->
        <div class="flavor-carpooling-buscar__acciones">
            <button type="submit" class="cp-btn cp-btn-primary cp-btn--lg" id="carpooling-buscar-btn">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e('Buscar viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </form>

    <!-- Contenedor de resultados -->
    <div id="carpooling-resultados" class="flavor-carpooling-buscar__resultados">
        <!-- Los resultados se cargan via AJAX -->
    </div>
</div>

<style>
/* Estilos especificos del formulario de busqueda */
.flavor-carpooling-buscar__header {
    text-align: center;
    margin-bottom: var(--fl-space-6, 1.5rem);
}

.flavor-carpooling-buscar__titulo {
    font-size: var(--fl-font-size-2xl, 1.5rem);
    font-weight: var(--fl-font-weight-bold, 700);
    color: var(--cp-text-primary);
    margin: 0 0 var(--fl-space-2, 0.5rem);
}

.flavor-carpooling-buscar__subtitulo {
    color: var(--cp-text-muted);
    margin: 0;
}

.flavor-carpooling-buscar__swap {
    display: flex;
    justify-content: center;
    margin: calc(-1 * var(--fl-space-3, 0.75rem)) 0;
    position: relative;
    z-index: 1;
}

.flavor-carpooling-buscar__filtros-avanzados {
    margin-top: var(--fl-space-4, 1rem);
    border: 1px solid var(--cp-border);
    border-radius: var(--cp-radius-sm);
    overflow: hidden;
}

.flavor-carpooling-buscar__filtros-toggle {
    padding: var(--fl-space-3, 0.75rem) var(--fl-space-4, 1rem);
    background: var(--cp-bg-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: var(--fl-space-2, 0.5rem);
    font-weight: var(--fl-font-weight-medium, 500);
    color: var(--cp-text-secondary);
}

.flavor-carpooling-buscar__filtros-toggle:hover {
    background: var(--cp-primary-light);
    color: var(--cp-primary);
}

.flavor-carpooling-buscar__filtros-contenido {
    padding: var(--fl-space-4, 1rem);
    border-top: 1px solid var(--cp-border);
}

.flavor-carpooling-buscar__preferencias {
    margin-top: var(--fl-space-4, 1rem);
}

.flavor-carpooling-buscar__preferencias-titulo {
    display: block;
    font-weight: var(--fl-font-weight-medium, 500);
    color: var(--cp-text-primary);
    margin-bottom: var(--fl-space-3, 0.75rem);
}

.flavor-carpooling-buscar__preferencias-grid {
    display: flex;
    flex-wrap: wrap;
    gap: var(--fl-space-4, 1rem);
}

.flavor-carpooling-buscar__acciones {
    margin-top: var(--fl-space-6, 1.5rem);
    text-align: center;
}

.cp-btn--lg {
    padding: var(--fl-space-4, 1rem) var(--fl-space-8, 2rem);
    font-size: var(--fl-font-size-base, 1rem);
}

.flavor-carpooling-buscar__resultados {
    margin-top: var(--fl-space-8, 2rem);
}

/* Checkbox personalizado */
.flavor-checkbox {
    display: flex;
    align-items: center;
    gap: var(--fl-space-2, 0.5rem);
    cursor: pointer;
}

.flavor-checkbox input {
    display: none;
}

.flavor-checkbox__mark {
    width: 20px;
    height: 20px;
    border: 2px solid var(--cp-border);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--cp-transition);
}

.flavor-checkbox input:checked + .flavor-checkbox__mark {
    background: var(--cp-primary);
    border-color: var(--cp-primary);
}

.flavor-checkbox input:checked + .flavor-checkbox__mark::after {
    content: '\f147';
    font-family: dashicons;
    color: white;
    font-size: 14px;
}

.flavor-checkbox__label {
    display: flex;
    align-items: center;
    gap: var(--fl-space-1, 0.25rem);
    color: var(--cp-text-secondary);
    font-size: var(--fl-font-size-sm, 0.875rem);
}

.flavor-checkbox__label .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
</style>
