<?php
/**
 * Template: Widget de busqueda rapida de viajes
 *
 * Widget compacto para busqueda rapida de viajes compartidos.
 * Ideal para sidebars, headers o secciones destacadas.
 *
 * @package FlavorPlatform
 * @subpackage Modules/Carpooling
 */

if (!defined('ABSPATH')) {
    exit;
}

// Atributos del widget (pueden venir de shortcode o widget)
$atributos_widget = wp_parse_args($args ?? [], [
    'titulo'        => __('Buscar viaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'mostrar_titulo' => true,
    'estilo'        => 'horizontal', // horizontal, vertical, minimal
    'redirect_url'  => '', // URL de redireccion tras buscar
]);

$clases_contenedor = [
    'flavor-carpooling-busqueda-rapida',
    'flavor-carpooling-busqueda-rapida--' . esc_attr($atributos_widget['estilo']),
];

$fecha_hoy = date('Y-m-d');
?>

<div class="<?php echo esc_attr(implode(' ', $clases_contenedor)); ?>">
    <?php if ($atributos_widget['mostrar_titulo']) : ?>
        <h3 class="flavor-carpooling-busqueda-rapida__titulo">
            <span class="dashicons dashicons-car"></span>
            <?php echo esc_html($atributos_widget['titulo']); ?>
        </h3>
    <?php endif; ?>

    <form class="flavor-carpooling-busqueda-rapida__form" id="carpooling-busqueda-rapida-form">
        <?php wp_nonce_field('carpooling_buscar_nonce', 'carpooling_nonce_rapida'); ?>

        <?php if (!empty($atributos_widget['redirect_url'])) : ?>
            <input type="hidden" name="redirect_url" value="<?php echo esc_url($atributos_widget['redirect_url']); ?>" />
        <?php endif; ?>

        <div class="flavor-carpooling-busqueda-rapida__campos">
            <!-- Origen -->
            <div class="flavor-carpooling-busqueda-rapida__campo">
                <label for="br-origen" class="sr-only"><?php esc_html_e('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <div class="flavor-input-icon">
                    <span class="dashicons dashicons-location" aria-hidden="true"></span>
                    <input
                        type="text"
                        name="origen"
                        id="br-origen"
                        class="flavor-input"
                        placeholder="<?php esc_attr_e('Desde...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                        required
                    />
                </div>
            </div>

            <!-- Destino -->
            <div class="flavor-carpooling-busqueda-rapida__campo">
                <label for="br-destino" class="sr-only"><?php esc_html_e('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <div class="flavor-input-icon">
                    <span class="dashicons dashicons-flag" aria-hidden="true"></span>
                    <input
                        type="text"
                        name="destino"
                        id="br-destino"
                        class="flavor-input"
                        placeholder="<?php esc_attr_e('Hasta...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                        required
                    />
                </div>
            </div>

            <!-- Fecha -->
            <div class="flavor-carpooling-busqueda-rapida__campo flavor-carpooling-busqueda-rapida__campo--fecha">
                <label for="br-fecha" class="sr-only"><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <div class="flavor-input-icon">
                    <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                    <input
                        type="date"
                        name="fecha"
                        id="br-fecha"
                        class="flavor-input"
                        value="<?php echo esc_attr($fecha_hoy); ?>"
                        min="<?php echo esc_attr($fecha_hoy); ?>"
                        required
                    />
                </div>
            </div>

            <!-- Boton buscar -->
            <div class="flavor-carpooling-busqueda-rapida__campo flavor-carpooling-busqueda-rapida__campo--boton">
                <button type="submit" class="cp-btn cp-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <span class="flavor-carpooling-busqueda-rapida__btn-texto">
                        <?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </button>
            </div>
        </div>
    </form>

    <!-- Link a busqueda avanzada -->
    <div class="flavor-carpooling-busqueda-rapida__footer">
        <a href="<?php echo esc_url(home_url('/carpooling/buscar/')); ?>" class="flavor-carpooling-busqueda-rapida__link-avanzado">
            <?php esc_html_e('Busqueda avanzada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </a>
    </div>
</div>

<style>
/* === Widget de busqueda rapida === */
.flavor-carpooling-busqueda-rapida {
    background: var(--cp-bg-card);
    border-radius: var(--cp-radius);
    box-shadow: var(--cp-shadow);
    padding: var(--fl-space-4, 1rem);
}

.flavor-carpooling-busqueda-rapida__titulo {
    display: flex;
    align-items: center;
    gap: var(--fl-space-2, 0.5rem);
    font-size: var(--fl-font-size-lg, 1.125rem);
    font-weight: var(--fl-font-weight-semibold, 600);
    color: var(--cp-text-primary);
    margin: 0 0 var(--fl-space-4, 1rem);
}

.flavor-carpooling-busqueda-rapida__titulo .dashicons {
    color: var(--cp-primary);
}

/* Estilo horizontal */
.flavor-carpooling-busqueda-rapida--horizontal .flavor-carpooling-busqueda-rapida__campos {
    display: flex;
    flex-wrap: wrap;
    gap: var(--fl-space-3, 0.75rem);
    align-items: stretch;
}

.flavor-carpooling-busqueda-rapida--horizontal .flavor-carpooling-busqueda-rapida__campo {
    flex: 1;
    min-width: 150px;
}

.flavor-carpooling-busqueda-rapida--horizontal .flavor-carpooling-busqueda-rapida__campo--fecha {
    flex: 0 0 auto;
    min-width: 160px;
}

.flavor-carpooling-busqueda-rapida--horizontal .flavor-carpooling-busqueda-rapida__campo--boton {
    flex: 0 0 auto;
}

/* Estilo vertical */
.flavor-carpooling-busqueda-rapida--vertical .flavor-carpooling-busqueda-rapida__campos {
    display: flex;
    flex-direction: column;
    gap: var(--fl-space-3, 0.75rem);
}

.flavor-carpooling-busqueda-rapida--vertical .flavor-carpooling-busqueda-rapida__campo--boton {
    margin-top: var(--fl-space-2, 0.5rem);
}

.flavor-carpooling-busqueda-rapida--vertical .cp-btn {
    width: 100%;
}

/* Estilo minimal */
.flavor-carpooling-busqueda-rapida--minimal {
    background: transparent;
    box-shadow: none;
    padding: 0;
}

.flavor-carpooling-busqueda-rapida--minimal .flavor-carpooling-busqueda-rapida__campos {
    display: flex;
    gap: var(--fl-space-2, 0.5rem);
}

.flavor-carpooling-busqueda-rapida--minimal .flavor-input {
    padding: var(--fl-space-2, 0.5rem) var(--fl-space-3, 0.75rem);
    font-size: var(--fl-font-size-sm, 0.875rem);
}

/* Input con icono */
.flavor-input-icon {
    position: relative;
}

.flavor-input-icon .dashicons {
    position: absolute;
    left: var(--fl-space-3, 0.75rem);
    top: 50%;
    transform: translateY(-50%);
    color: var(--cp-text-muted);
    font-size: 18px;
    width: 18px;
    height: 18px;
    pointer-events: none;
}

.flavor-input-icon .flavor-input {
    padding-left: calc(var(--fl-space-3, 0.75rem) * 2 + 18px);
    width: 100%;
    border: 1px solid var(--cp-border);
    border-radius: var(--cp-radius-sm);
    padding-top: var(--fl-space-3, 0.75rem);
    padding-bottom: var(--fl-space-3, 0.75rem);
    padding-right: var(--fl-space-3, 0.75rem);
    font-size: var(--fl-font-size-base, 1rem);
    transition: border-color var(--cp-transition), box-shadow var(--cp-transition);
}

.flavor-input-icon .flavor-input:focus {
    outline: none;
    border-color: var(--cp-primary);
    box-shadow: 0 0 0 3px var(--cp-primary-light);
}

/* Footer del widget */
.flavor-carpooling-busqueda-rapida__footer {
    margin-top: var(--fl-space-3, 0.75rem);
    text-align: center;
    border-top: 1px solid var(--cp-border);
    padding-top: var(--fl-space-3, 0.75rem);
}

.flavor-carpooling-busqueda-rapida__link-avanzado {
    display: inline-flex;
    align-items: center;
    gap: var(--fl-space-1, 0.25rem);
    font-size: var(--fl-font-size-sm, 0.875rem);
    color: var(--cp-primary);
    text-decoration: none;
    transition: color var(--cp-transition);
}

.flavor-carpooling-busqueda-rapida__link-avanzado:hover {
    color: var(--cp-primary-hover);
    text-decoration: underline;
}

.flavor-carpooling-busqueda-rapida__link-avanzado .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Screen reader only */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-carpooling-busqueda-rapida--horizontal .flavor-carpooling-busqueda-rapida__campos {
        flex-direction: column;
    }

    .flavor-carpooling-busqueda-rapida--horizontal .flavor-carpooling-busqueda-rapida__campo,
    .flavor-carpooling-busqueda-rapida--horizontal .flavor-carpooling-busqueda-rapida__campo--fecha,
    .flavor-carpooling-busqueda-rapida--horizontal .flavor-carpooling-busqueda-rapida__campo--boton {
        flex: 1 1 100%;
        min-width: 100%;
    }

    .flavor-carpooling-busqueda-rapida--horizontal .cp-btn {
        width: 100%;
    }

    .flavor-carpooling-busqueda-rapida__btn-texto {
        display: inline;
    }
}

@media (min-width: 769px) {
    .flavor-carpooling-busqueda-rapida--minimal .flavor-carpooling-busqueda-rapida__btn-texto {
        display: none;
    }
}
</style>
