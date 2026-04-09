<?php
/**
 * Template: Formulario para publicar viaje
 *
 * Formulario completo para que los conductores publiquen
 * nuevos viajes compartidos.
 *
 * @package FlavorChatIA
 * @subpackage Modules/Carpooling
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar login
if (!is_user_logged_in()) {
    ?>
    <div class="flavor-carpooling-publicar flavor-carpooling-publicar--no-login">
        <div class="flavor-carpooling-publicar__aviso">
            <span class="dashicons dashicons-lock"></span>
            <h3><?php esc_html_e('Inicia sesion para publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Necesitas una cuenta para publicar viajes compartidos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="cp-btn cp-btn-primary">
                <?php esc_html_e('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
    <?php
    return;
}

global $wpdb;
$usuario_id = get_current_user_id();
$tabla_vehiculos = $wpdb->prefix . 'flavor_carpooling_vehiculos';

// Obtener vehiculos del usuario
$vehiculos_usuario = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$tabla_vehiculos} WHERE propietario_id = %d AND activo = 1 ORDER BY created_at DESC",
    $usuario_id
));

// Configuracion del modulo
$max_pasajeros = apply_filters('carpooling_max_pasajeros_por_viaje', 4);
$dias_anticipacion = apply_filters('carpooling_dias_anticipacion_maxima', 30);
$precio_sugerido_km = apply_filters('carpooling_precio_por_km', 0.15);

// Fechas
$fecha_minima = date('Y-m-d');
$fecha_maxima = date('Y-m-d', strtotime("+{$dias_anticipacion} days"));
$hora_minima = date('H:i', strtotime('+1 hour'));
?>

<div class="flavor-carpooling-publicar carpooling-container">
    <header class="flavor-carpooling-publicar__header">
        <h2 class="flavor-carpooling-publicar__titulo">
            <span class="dashicons dashicons-car"></span>
            <?php esc_html_e('Publicar un viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>
        <p class="flavor-carpooling-publicar__subtitulo">
            <?php esc_html_e('Comparte tu viaje con otros vecinos y reduce costes y emisiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </header>

    <form class="carpooling-form flavor-carpooling-publicar__form" id="carpooling-publicar-form">
        <?php wp_nonce_field('carpooling_publicar_nonce', 'carpooling_nonce'); ?>

        <!-- Seccion: Ruta -->
        <fieldset class="flavor-carpooling-publicar__seccion">
            <legend class="flavor-carpooling-publicar__seccion-titulo">
                <span class="dashicons dashicons-location-alt"></span>
                <?php esc_html_e('Ruta del viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </legend>

            <div class="carpooling-form-row">
                <div class="carpooling-form-group">
                    <label for="cp-origen">
                        <?php esc_html_e('Punto de salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="flavor-input-icon">
                        <span class="dashicons dashicons-location"></span>
                        <input
                            type="text"
                            name="origen"
                            id="cp-origen"
                            class="flavor-input"
                            placeholder="<?php esc_attr_e('Direccion o lugar de salida...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                            autocomplete="off"
                            required
                        />
                    </div>
                    <input type="hidden" name="origen_lat" id="cp-origen-lat" />
                    <input type="hidden" name="origen_lng" id="cp-origen-lng" />
                    <input type="hidden" name="origen_place_id" id="cp-origen-place-id" />
                </div>

                <div class="carpooling-form-group">
                    <label for="cp-destino">
                        <?php esc_html_e('Punto de llegada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="flavor-input-icon">
                        <span class="dashicons dashicons-flag"></span>
                        <input
                            type="text"
                            name="destino"
                            id="cp-destino"
                            class="flavor-input"
                            placeholder="<?php esc_attr_e('Direccion o lugar de destino...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                            autocomplete="off"
                            required
                        />
                    </div>
                    <input type="hidden" name="destino_lat" id="cp-destino-lat" />
                    <input type="hidden" name="destino_lng" id="cp-destino-lng" />
                    <input type="hidden" name="destino_place_id" id="cp-destino-place-id" />
                </div>
            </div>

            <!-- Paradas intermedias (opcional) -->
            <div class="carpooling-form-group">
                <label>
                    <?php esc_html_e('Paradas intermedias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="flavor-label-hint"><?php esc_html_e('(opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </label>
                <div id="cp-paradas-container" class="flavor-carpooling-publicar__paradas">
                    <!-- Las paradas se agregan dinamicamente -->
                </div>
                <button type="button" class="cp-btn cp-btn-outline cp-btn--sm" id="cp-agregar-parada">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Agregar parada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </fieldset>

        <!-- Seccion: Fecha y hora -->
        <fieldset class="flavor-carpooling-publicar__seccion">
            <legend class="flavor-carpooling-publicar__seccion-titulo">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Fecha y hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </legend>

            <div class="carpooling-form-row">
                <div class="carpooling-form-group">
                    <label for="cp-fecha">
                        <?php esc_html_e('Fecha de salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="date"
                        name="fecha"
                        id="cp-fecha"
                        class="flavor-input"
                        min="<?php echo esc_attr($fecha_minima); ?>"
                        max="<?php echo esc_attr($fecha_maxima); ?>"
                        required
                    />
                </div>

                <div class="carpooling-form-group">
                    <label for="cp-hora">
                        <?php esc_html_e('Hora de salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="time"
                        name="hora"
                        id="cp-hora"
                        class="flavor-input"
                        required
                    />
                </div>
            </div>

            <!-- Viaje recurrente -->
            <div class="carpooling-form-group">
                <label class="flavor-checkbox">
                    <input type="checkbox" name="es_recurrente" id="cp-es-recurrente" value="1" />
                    <span class="flavor-checkbox__mark"></span>
                    <span class="flavor-checkbox__label">
                        <?php esc_html_e('Este viaje se repite semanalmente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </label>

                <div id="cp-recurrente-opciones" class="flavor-carpooling-publicar__recurrente" style="display: none;">
                    <label><?php esc_html_e('Dias de la semana:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <div class="flavor-carpooling-publicar__dias-semana">
                        <?php
                        $dias_semana = [
                            'lunes'     => __('L', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'martes'    => __('M', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'miercoles' => __('X', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'jueves'    => __('J', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'viernes'   => __('V', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'sabado'    => __('S', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'domingo'   => __('D', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        ];
                        foreach ($dias_semana as $dia_id => $dia_label) :
                        ?>
                            <label class="flavor-carpooling-publicar__dia">
                                <input type="checkbox" name="dias_semana[]" value="<?php echo esc_attr($dia_id); ?>" />
                                <span><?php echo esc_html($dia_label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- Seccion: Plazas y precio -->
        <fieldset class="flavor-carpooling-publicar__seccion">
            <legend class="flavor-carpooling-publicar__seccion-titulo">
                <span class="dashicons dashicons-groups"></span>
                <?php esc_html_e('Plazas y precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </legend>

            <div class="carpooling-form-row">
                <div class="carpooling-form-group">
                    <label for="cp-plazas">
                        <?php esc_html_e('Plazas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="required">*</span>
                    </label>
                    <select name="plazas" id="cp-plazas" class="flavor-select" required>
                        <?php for ($contador_plazas = 1; $contador_plazas <= $max_pasajeros; $contador_plazas++) : ?>
                            <option value="<?php echo esc_attr($contador_plazas); ?>">
                                <?php echo esc_html($contador_plazas); ?> <?php echo esc_html(_n('plaza', 'plazas', $contador_plazas, FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="carpooling-form-group">
                    <label for="cp-precio">
                        <?php esc_html_e('Precio por plaza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="flavor-label-hint">(€)</span>
                    </label>
                    <div class="flavor-input-addon">
                        <input
                            type="number"
                            name="precio"
                            id="cp-precio"
                            class="flavor-input"
                            min="0"
                            step="0.50"
                            placeholder="0.00"
                        />
                        <span class="flavor-input-addon__suffix">€</span>
                    </div>
                    <p class="flavor-help-text" id="cp-precio-sugerido">
                        <!-- El precio sugerido se calculara dinamicamente -->
                    </p>
                </div>
            </div>

            <div class="flavor-carpooling-publicar__precio-info">
                <span class="dashicons dashicons-info-outline"></span>
                <p>
                    <?php esc_html_e('Si dejas el precio en blanco, se calculara automaticamente basandose en la distancia del viaje.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>
        </fieldset>

        <!-- Seccion: Vehiculo -->
        <fieldset class="flavor-carpooling-publicar__seccion">
            <legend class="flavor-carpooling-publicar__seccion-titulo">
                <span class="dashicons dashicons-car"></span>
                <?php esc_html_e('Vehiculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </legend>

            <?php if (!empty($vehiculos_usuario)) : ?>
                <div class="carpooling-form-group">
                    <label for="cp-vehiculo"><?php esc_html_e('Selecciona tu vehiculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="vehiculo_id" id="cp-vehiculo" class="flavor-select">
                        <option value=""><?php esc_html_e('Sin especificar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($vehiculos_usuario as $vehiculo) : ?>
                            <option value="<?php echo esc_attr($vehiculo->id); ?>">
                                <?php echo esc_html($vehiculo->marca . ' ' . $vehiculo->modelo . ' (' . $vehiculo->color . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else : ?>
                <div class="flavor-carpooling-publicar__sin-vehiculo">
                    <p><?php esc_html_e('No tienes vehiculos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button type="button" class="cp-btn cp-btn-outline cp-btn--sm" id="cp-agregar-vehiculo-btn">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Agregar vehiculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            <?php endif; ?>
        </fieldset>

        <!-- Seccion: Preferencias -->
        <fieldset class="flavor-carpooling-publicar__seccion">
            <legend class="flavor-carpooling-publicar__seccion-titulo">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e('Preferencias del viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </legend>

            <div class="flavor-carpooling-publicar__preferencias">
                <label class="flavor-checkbox">
                    <input type="checkbox" name="permite_mascotas" value="1" />
                    <span class="flavor-checkbox__mark"></span>
                    <span class="flavor-checkbox__label">
                        <span class="dashicons dashicons-pets"></span>
                        <?php esc_html_e('Acepto mascotas pequenas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </label>

                <label class="flavor-checkbox">
                    <input type="checkbox" name="permite_equipaje_grande" value="1" />
                    <span class="flavor-checkbox__mark"></span>
                    <span class="flavor-checkbox__label">
                        <span class="dashicons dashicons-portfolio"></span>
                        <?php esc_html_e('Espacio para equipaje grande', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </label>

                <label class="flavor-checkbox">
                    <input type="checkbox" name="permite_fumar" value="1" />
                    <span class="flavor-checkbox__mark"></span>
                    <span class="flavor-checkbox__label">
                        <span class="dashicons dashicons-marker"></span>
                        <?php esc_html_e('Se permite fumar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </label>

                <label class="flavor-checkbox">
                    <input type="checkbox" name="solo_mujeres" value="1" />
                    <span class="flavor-checkbox__mark"></span>
                    <span class="flavor-checkbox__label">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php esc_html_e('Solo para mujeres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </label>
            </div>
        </fieldset>

        <!-- Seccion: Notas adicionales -->
        <fieldset class="flavor-carpooling-publicar__seccion">
            <legend class="flavor-carpooling-publicar__seccion-titulo">
                <span class="dashicons dashicons-edit"></span>
                <?php esc_html_e('Informacion adicional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </legend>

            <div class="carpooling-form-group">
                <label for="cp-notas">
                    <?php esc_html_e('Notas para los pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="flavor-label-hint"><?php esc_html_e('(opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </label>
                <textarea
                    name="notas"
                    id="cp-notas"
                    class="flavor-textarea"
                    rows="4"
                    placeholder="<?php esc_attr_e('Ej: Salgo puntual, punto de encuentro exacto, prefiero no hablar mucho...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                ></textarea>
            </div>
        </fieldset>

        <!-- Acciones -->
        <div class="flavor-carpooling-publicar__acciones">
            <button type="button" class="cp-btn cp-btn-outline" id="cp-vista-previa-btn">
                <span class="dashicons dashicons-visibility"></span>
                <?php esc_html_e('Vista previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>

            <button type="submit" class="cp-btn cp-btn-primary cp-btn--lg" id="cp-publicar-btn">
                <span class="dashicons dashicons-megaphone"></span>
                <?php esc_html_e('Publicar viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </form>
</div>

<!-- Modal agregar vehiculo -->
<div id="cp-modal-vehiculo" class="flavor-modal" style="display: none;">
    <div class="flavor-modal__contenido">
        <div class="flavor-modal__header">
            <h3><?php esc_html_e('Agregar vehiculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <button type="button" class="flavor-modal__cerrar" data-close-modal>
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="flavor-modal__body">
            <form id="cp-vehiculo-form">
                <?php wp_nonce_field('carpooling_vehiculo_nonce', 'vehiculo_nonce'); ?>

                <div class="carpooling-form-row">
                    <div class="carpooling-form-group">
                        <label for="vehiculo-marca"><?php esc_html_e('Marca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                        <input type="text" name="marca" id="vehiculo-marca" class="flavor-input" required />
                    </div>
                    <div class="carpooling-form-group">
                        <label for="vehiculo-modelo"><?php esc_html_e('Modelo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                        <input type="text" name="modelo" id="vehiculo-modelo" class="flavor-input" required />
                    </div>
                </div>

                <div class="carpooling-form-row">
                    <div class="carpooling-form-group">
                        <label for="vehiculo-color"><?php esc_html_e('Color', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" name="color" id="vehiculo-color" class="flavor-input" />
                    </div>
                    <div class="carpooling-form-group">
                        <label for="vehiculo-matricula"><?php esc_html_e('Matricula', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                        <input type="text" name="matricula" id="vehiculo-matricula" class="flavor-input" required />
                    </div>
                </div>

                <div class="carpooling-form-row">
                    <div class="carpooling-form-group">
                        <label for="vehiculo-ano"><?php esc_html_e('Ano', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" name="ano" id="vehiculo-ano" class="flavor-input" min="1990" max="<?php echo esc_attr(date('Y') + 1); ?>" />
                    </div>
                    <div class="carpooling-form-group">
                        <label for="vehiculo-plazas"><?php esc_html_e('Plazas totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                        <select name="plazas_totales" id="vehiculo-plazas" class="flavor-select" required>
                            <?php for ($contador_plazas_vehiculo = 2; $contador_plazas_vehiculo <= 9; $contador_plazas_vehiculo++) : ?>
                                <option value="<?php echo esc_attr($contador_plazas_vehiculo); ?>" <?php selected($contador_plazas_vehiculo, 5); ?>>
                                    <?php echo esc_html($contador_plazas_vehiculo); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="flavor-modal__acciones">
                    <button type="button" class="cp-btn cp-btn-outline" data-close-modal>
                        <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="submit" class="cp-btn cp-btn-primary">
                        <?php esc_html_e('Guardar vehiculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* === Formulario publicar viaje === */
.flavor-carpooling-publicar__header {
    text-align: center;
    margin-bottom: var(--fl-space-6, 1.5rem);
}

.flavor-carpooling-publicar__titulo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--fl-space-3, 0.75rem);
    font-size: var(--fl-font-size-2xl, 1.5rem);
    font-weight: var(--fl-font-weight-bold, 700);
    color: var(--cp-text-primary);
    margin: 0 0 var(--fl-space-2, 0.5rem);
}

.flavor-carpooling-publicar__titulo .dashicons {
    color: var(--cp-primary);
}

.flavor-carpooling-publicar__subtitulo {
    color: var(--cp-text-muted);
    margin: 0;
}

/* Secciones */
.flavor-carpooling-publicar__seccion {
    border: 1px solid var(--cp-border);
    border-radius: var(--cp-radius);
    padding: var(--fl-space-5, 1.25rem);
    margin-bottom: var(--fl-space-5, 1.25rem);
}

.flavor-carpooling-publicar__seccion-titulo {
    display: flex;
    align-items: center;
    gap: var(--fl-space-2, 0.5rem);
    font-size: var(--fl-font-size-base, 1rem);
    font-weight: var(--fl-font-weight-semibold, 600);
    color: var(--cp-text-primary);
    padding: 0 var(--fl-space-2, 0.5rem);
    margin-left: calc(-1 * var(--fl-space-2, 0.5rem));
}

.flavor-carpooling-publicar__seccion-titulo .dashicons {
    color: var(--cp-primary);
}

/* Labels */
.flavor-label-hint {
    font-weight: var(--fl-font-weight-normal, 400);
    color: var(--cp-text-muted);
    font-size: var(--fl-font-size-sm, 0.875rem);
}

.flavor-help-text {
    margin-top: var(--fl-space-2, 0.5rem);
    font-size: var(--fl-font-size-sm, 0.875rem);
    color: var(--cp-text-muted);
}

/* Input con addon */
.flavor-input-addon {
    display: flex;
    align-items: stretch;
}

.flavor-input-addon .flavor-input {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    flex: 1;
}

.flavor-input-addon__suffix {
    display: flex;
    align-items: center;
    padding: 0 var(--fl-space-4, 1rem);
    background: var(--cp-bg-muted);
    border: 1px solid var(--cp-border);
    border-left: none;
    border-radius: 0 var(--cp-radius-sm) var(--cp-radius-sm) 0;
    color: var(--cp-text-muted);
    font-weight: var(--fl-font-weight-medium, 500);
}

/* Textarea */
.flavor-textarea {
    width: 100%;
    padding: var(--fl-space-3, 0.75rem);
    border: 1px solid var(--cp-border);
    border-radius: var(--cp-radius-sm);
    font-size: var(--fl-font-size-base, 1rem);
    font-family: inherit;
    resize: vertical;
    transition: border-color var(--cp-transition), box-shadow var(--cp-transition);
}

.flavor-textarea:focus {
    outline: none;
    border-color: var(--cp-primary);
    box-shadow: 0 0 0 3px var(--cp-primary-light);
}

/* Select */
.flavor-select {
    width: 100%;
    padding: var(--fl-space-3, 0.75rem);
    border: 1px solid var(--cp-border);
    border-radius: var(--cp-radius-sm);
    font-size: var(--fl-font-size-base, 1rem);
    background: var(--cp-bg-card);
    cursor: pointer;
    transition: border-color var(--cp-transition), box-shadow var(--cp-transition);
}

.flavor-select:focus {
    outline: none;
    border-color: var(--cp-primary);
    box-shadow: 0 0 0 3px var(--cp-primary-light);
}

/* Paradas */
.flavor-carpooling-publicar__paradas {
    display: flex;
    flex-direction: column;
    gap: var(--fl-space-3, 0.75rem);
    margin-bottom: var(--fl-space-3, 0.75rem);
}

.flavor-carpooling-publicar__parada {
    display: flex;
    gap: var(--fl-space-2, 0.5rem);
    align-items: center;
}

.flavor-carpooling-publicar__parada .flavor-input {
    flex: 1;
}

.flavor-carpooling-publicar__parada-eliminar {
    padding: var(--fl-space-2, 0.5rem);
    background: transparent;
    border: none;
    color: var(--cp-danger);
    cursor: pointer;
    border-radius: var(--cp-radius-sm);
    transition: background var(--cp-transition);
}

.flavor-carpooling-publicar__parada-eliminar:hover {
    background: color-mix(in srgb, var(--cp-danger) 10%, transparent);
}

/* Viaje recurrente */
.flavor-carpooling-publicar__recurrente {
    margin-top: var(--fl-space-4, 1rem);
    padding: var(--fl-space-4, 1rem);
    background: var(--cp-bg-muted);
    border-radius: var(--cp-radius-sm);
}

.flavor-carpooling-publicar__dias-semana {
    display: flex;
    gap: var(--fl-space-2, 0.5rem);
    margin-top: var(--fl-space-3, 0.75rem);
}

.flavor-carpooling-publicar__dia {
    cursor: pointer;
}

.flavor-carpooling-publicar__dia input {
    display: none;
}

.flavor-carpooling-publicar__dia span {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--cp-bg-card);
    border: 2px solid var(--cp-border);
    font-weight: var(--fl-font-weight-semibold, 600);
    transition: all var(--cp-transition);
}

.flavor-carpooling-publicar__dia input:checked + span {
    background: var(--cp-primary);
    border-color: var(--cp-primary);
    color: white;
}

/* Preferencias */
.flavor-carpooling-publicar__preferencias {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--fl-space-4, 1rem);
}

/* Info precio */
.flavor-carpooling-publicar__precio-info {
    display: flex;
    gap: var(--fl-space-2, 0.5rem);
    padding: var(--fl-space-3, 0.75rem);
    background: var(--cp-primary-light);
    border-radius: var(--cp-radius-sm);
    margin-top: var(--fl-space-4, 1rem);
}

.flavor-carpooling-publicar__precio-info .dashicons {
    color: var(--cp-primary);
    flex-shrink: 0;
}

.flavor-carpooling-publicar__precio-info p {
    margin: 0;
    font-size: var(--fl-font-size-sm, 0.875rem);
    color: var(--cp-text-secondary);
}

/* Sin vehiculo */
.flavor-carpooling-publicar__sin-vehiculo {
    text-align: center;
    padding: var(--fl-space-4, 1rem);
    background: var(--cp-bg-muted);
    border-radius: var(--cp-radius-sm);
}

.flavor-carpooling-publicar__sin-vehiculo p {
    margin: 0 0 var(--fl-space-3, 0.75rem);
    color: var(--cp-text-muted);
}

/* Acciones */
.flavor-carpooling-publicar__acciones {
    display: flex;
    gap: var(--fl-space-4, 1rem);
    justify-content: center;
    margin-top: var(--fl-space-6, 1.5rem);
}

.cp-btn--sm {
    padding: var(--fl-space-2, 0.5rem) var(--fl-space-3, 0.75rem);
    font-size: var(--fl-font-size-sm, 0.875rem);
}

/* Estado no login */
.flavor-carpooling-publicar--no-login {
    text-align: center;
    padding: var(--fl-space-8, 2rem);
}

.flavor-carpooling-publicar__aviso {
    max-width: 400px;
    margin: 0 auto;
}

.flavor-carpooling-publicar__aviso .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--cp-text-muted);
    margin-bottom: var(--fl-space-4, 1rem);
}

.flavor-carpooling-publicar__aviso h3 {
    margin: 0 0 var(--fl-space-2, 0.5rem);
}

.flavor-carpooling-publicar__aviso p {
    margin: 0 0 var(--fl-space-4, 1rem);
    color: var(--cp-text-muted);
}

/* Modal */
.flavor-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: var(--fl-space-4, 1rem);
}

.flavor-modal__contenido {
    background: var(--cp-bg-card);
    border-radius: var(--cp-radius);
    box-shadow: var(--cp-shadow-hover);
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.flavor-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--fl-space-4, 1rem) var(--fl-space-5, 1.25rem);
    border-bottom: 1px solid var(--cp-border);
}

.flavor-modal__header h3 {
    margin: 0;
    font-size: var(--fl-font-size-lg, 1.125rem);
}

.flavor-modal__cerrar {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: var(--fl-space-1, 0.25rem);
    color: var(--cp-text-muted);
    transition: color var(--cp-transition);
}

.flavor-modal__cerrar:hover {
    color: var(--cp-text-primary);
}

.flavor-modal__body {
    padding: var(--fl-space-5, 1.25rem);
}

.flavor-modal__acciones {
    display: flex;
    gap: var(--fl-space-3, 0.75rem);
    justify-content: flex-end;
    margin-top: var(--fl-space-5, 1.25rem);
    padding-top: var(--fl-space-4, 1rem);
    border-top: 1px solid var(--cp-border);
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-carpooling-publicar__acciones {
        flex-direction: column;
    }

    .flavor-carpooling-publicar__acciones .cp-btn {
        width: 100%;
    }

    .flavor-carpooling-publicar__dias-semana {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>
