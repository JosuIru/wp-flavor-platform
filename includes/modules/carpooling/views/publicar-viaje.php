<?php
/**
 * Vista: Publicar viaje
 */

if (!defined('ABSPATH')) {
    exit;
}

$mostrar_vehiculos = $atts['mostrar_vehiculos'] === 'true';
$permite_recurrente = $atts['permite_recurrente'] === 'true';
$usuario_id = get_current_user_id();
?>

<div class="carpooling-container">
    <div class="carpooling-publicar">
        <h2 class="carpooling-publicar__titulo"><?php esc_html_e('Publicar un viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="carpooling-publicar__subtitulo"><?php esc_html_e('Comparte tu viaje y recupera parte de los gastos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <form id="carpooling-form-publicar" data-redirect-url="<?php echo esc_url(home_url('/mis-viajes/')); ?>">

            <!-- Seccion: Ruta -->
            <div class="carpooling-publicar__seccion">
                <h3 class="carpooling-publicar__seccion-titulo"><?php esc_html_e('Ruta del viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <div class="carpooling-publicar__grid">
                    <div class="carpooling-campo carpooling-autocomplete">
                        <label class="carpooling-campo__label"><?php esc_html_e('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="text" name="origen" class="carpooling-campo__input carpooling-autocomplete__input" placeholder="<?php esc_attr_e('Desde donde sales?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" required autocomplete="off">
                        <input type="hidden" name="origen_lat">
                        <input type="hidden" name="origen_lng">
                        <input type="hidden" name="origen_place_id">
                        <div class="carpooling-autocomplete__lista"></div>
                    </div>

                    <div class="carpooling-campo carpooling-autocomplete">
                        <label class="carpooling-campo__label"><?php esc_html_e('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="text" name="destino" class="carpooling-campo__input carpooling-autocomplete__input" placeholder="<?php esc_attr_e('A donde vas?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" required autocomplete="off">
                        <input type="hidden" name="destino_lat">
                        <input type="hidden" name="destino_lng">
                        <input type="hidden" name="destino_place_id">
                        <div class="carpooling-autocomplete__lista"></div>
                    </div>
                </div>
            </div>

            <!-- Seccion: Fecha y hora -->
            <div class="carpooling-publicar__seccion">
                <h3 class="carpooling-publicar__seccion-titulo"><?php esc_html_e('Fecha y hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <div class="carpooling-publicar__grid">
                    <div class="carpooling-campo">
                        <label class="carpooling-campo__label"><?php esc_html_e('Fecha de salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="date" name="fecha" class="carpooling-campo__input" min="<?php echo esc_attr(date('Y-m-d')); ?>" required>
                    </div>

                    <div class="carpooling-campo">
                        <label class="carpooling-campo__label"><?php esc_html_e('Hora de salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="time" name="hora" class="carpooling-campo__input" required>
                    </div>
                </div>

                <?php if ($permite_recurrente) : ?>
                <div class="carpooling-campo" style="margin-top: 16px;">
                    <label class="carpooling-preferencia">
                        <input type="checkbox" name="es_recurrente" class="carpooling-preferencia__checkbox">
                        <span class="carpooling-preferencia__label"><?php esc_html_e('Este es un viaje recurrente (trabajo, universidad...)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </label>
                </div>

                <div id="carpooling-seccion-recurrente" style="display: none; margin-top: 16px; padding: 16px; background: #f9fafb; border-radius: 8px;">
                    <label class="carpooling-campo__label" style="margin-bottom: 12px; display: block;"><?php esc_html_e('Dias de la semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <label class="carpooling-preferencia">
                            <input type="checkbox" name="dias[]" value="1" class="carpooling-preferencia__checkbox">
                            <span class="carpooling-preferencia__label"><?php esc_html_e('Lunes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="carpooling-preferencia">
                            <input type="checkbox" name="dias[]" value="2" class="carpooling-preferencia__checkbox">
                            <span class="carpooling-preferencia__label"><?php esc_html_e('Martes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="carpooling-preferencia">
                            <input type="checkbox" name="dias[]" value="3" class="carpooling-preferencia__checkbox">
                            <span class="carpooling-preferencia__label"><?php esc_html_e('Miercoles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="carpooling-preferencia">
                            <input type="checkbox" name="dias[]" value="4" class="carpooling-preferencia__checkbox">
                            <span class="carpooling-preferencia__label"><?php esc_html_e('Jueves', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="carpooling-preferencia">
                            <input type="checkbox" name="dias[]" value="5" class="carpooling-preferencia__checkbox">
                            <span class="carpooling-preferencia__label"><?php esc_html_e('Viernes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="carpooling-preferencia">
                            <input type="checkbox" name="dias[]" value="6" class="carpooling-preferencia__checkbox">
                            <span class="carpooling-preferencia__label"><?php esc_html_e('Sabado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="carpooling-preferencia">
                            <input type="checkbox" name="dias[]" value="7" class="carpooling-preferencia__checkbox">
                            <span class="carpooling-preferencia__label"><?php esc_html_e('Domingo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Seccion: Plazas y precio -->
            <div class="carpooling-publicar__seccion">
                <h3 class="carpooling-publicar__seccion-titulo"><?php esc_html_e('Plazas y precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <div class="carpooling-publicar__grid">
                    <div class="carpooling-campo">
                        <label class="carpooling-campo__label"><?php esc_html_e('Plazas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <select name="plazas" class="carpooling-campo__select" required>
                            <option value="1">1 <?php esc_html_e('plaza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="2">2 <?php esc_html_e('plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="3" selected>3 <?php esc_html_e('plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="4">4 <?php esc_html_e('plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <div class="carpooling-campo">
                        <label class="carpooling-campo__label"><?php esc_html_e('Precio por plaza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> (EUR)</label>
                        <input type="number" name="precio" class="carpooling-campo__input" step="0.50" min="0" placeholder="0.00">
                        <div id="carpooling-precio-sugerido" style="display: none; font-size: 12px; color: #6b7280; margin-top: 4px;"></div>
                    </div>
                </div>
            </div>

            <!-- Seccion: Vehiculo -->
            <?php if ($mostrar_vehiculos) : ?>
            <div class="carpooling-publicar__seccion">
                <h3 class="carpooling-publicar__seccion-titulo"><?php esc_html_e('Tu vehiculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <input type="hidden" name="vehiculo_id" value="">
                <div id="carpooling-vehiculos-lista">
                    <p class="carpooling-empty__texto"><?php esc_html_e('Cargando vehiculos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>

                <button type="button" class="carpooling-btn carpooling-btn--outline carpooling-btn--sm" style="margin-top: 12px;" onclick="document.getElementById('carpooling-modal-vehiculo').classList.add('activo');">
                    + <?php esc_html_e('Agregar vehiculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
            <?php endif; ?>

            <!-- Seccion: Preferencias -->
            <div class="carpooling-publicar__seccion">
                <h3 class="carpooling-publicar__seccion-titulo"><?php esc_html_e('Preferencias del viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <div class="carpooling-preferencias">
                    <label class="carpooling-preferencia">
                        <input type="checkbox" name="permite_mascotas" class="carpooling-preferencia__checkbox">
                        <span class="carpooling-preferencia__label">🐾 <?php esc_html_e('Se permiten mascotas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </label>

                    <label class="carpooling-preferencia">
                        <input type="checkbox" name="permite_equipaje_grande" class="carpooling-preferencia__checkbox">
                        <span class="carpooling-preferencia__label">🧳 <?php esc_html_e('Equipaje grande OK', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </label>

                    <label class="carpooling-preferencia">
                        <input type="checkbox" name="permite_fumar" class="carpooling-preferencia__checkbox">
                        <span class="carpooling-preferencia__label">🚬 <?php esc_html_e('Se permite fumar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </label>

                    <label class="carpooling-preferencia">
                        <input type="checkbox" name="solo_mujeres" class="carpooling-preferencia__checkbox">
                        <span class="carpooling-preferencia__label">👩 <?php esc_html_e('Solo mujeres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </label>
                </div>
            </div>

            <!-- Seccion: Notas -->
            <div class="carpooling-publicar__seccion">
                <h3 class="carpooling-publicar__seccion-titulo"><?php esc_html_e('Informacion adicional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <div class="carpooling-campo">
                    <label class="carpooling-campo__label"><?php esc_html_e('Notas para los pasajeros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="notas" class="carpooling-campo__textarea" placeholder="<?php esc_attr_e('Ej: Salgo del parking del centro comercial. Puntualidad por favor...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>
            </div>

            <div style="margin-top: 32px;">
                <button type="submit" class="carpooling-btn carpooling-btn--primary carpooling-btn--lg carpooling-btn--full">
                    <?php esc_html_e('Publicar viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Agregar vehiculo -->
<div id="carpooling-modal-vehiculo" class="carpooling-modal">
    <div class="carpooling-modal__contenido">
        <div class="carpooling-modal__header">
            <h3 class="carpooling-modal__titulo"><?php esc_html_e('Agregar vehiculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <button type="button" class="carpooling-modal__cerrar" onclick="this.closest('.carpooling-modal').classList.remove('activo');"><?php echo esc_html__('&times;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
        <div class="carpooling-modal__body">
            <form id="carpooling-form-vehiculo">
                <input type="hidden" name="vehiculo_id" value="0">

                <div class="carpooling-publicar__grid">
                    <div class="carpooling-campo">
                        <label class="carpooling-campo__label"><?php esc_html_e('Marca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="text" name="marca" class="carpooling-campo__input" required placeholder="<?php echo esc_attr__('Ej: Seat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <div class="carpooling-campo">
                        <label class="carpooling-campo__label"><?php esc_html_e('Modelo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                        <input type="text" name="modelo" class="carpooling-campo__input" required placeholder="<?php echo esc_attr__('Ej: Ibiza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <div class="carpooling-campo">
                        <label class="carpooling-campo__label"><?php esc_html_e('Color', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" name="color" class="carpooling-campo__input" placeholder="<?php echo esc_attr__('Ej: Blanco', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <div class="carpooling-campo">
                        <label class="carpooling-campo__label"><?php esc_html_e('Anio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" name="anio" class="carpooling-campo__input" min="1990" max="<?php echo date('Y'); ?>" placeholder="<?php echo date('Y'); ?>">
                    </div>

                    <div class="carpooling-campo">
                        <label class="carpooling-campo__label"><?php esc_html_e('Matricula', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" name="matricula" class="carpooling-campo__input" placeholder="<?php echo esc_attr__('1234 ABC', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <div class="carpooling-campo">
                        <label class="carpooling-campo__label"><?php esc_html_e('Plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="plazas" class="carpooling-campo__select">
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4" selected>4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                        </select>
                    </div>
                </div>

                <div class="carpooling-campo" style="margin-top: 16px;">
                    <label class="carpooling-preferencia">
                        <input type="checkbox" name="predeterminado" class="carpooling-preferencia__checkbox">
                        <span class="carpooling-preferencia__label"><?php esc_html_e('Usar como vehiculo predeterminado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </label>
                </div>
            </form>
        </div>
        <div class="carpooling-modal__footer">
            <button type="button" class="carpooling-btn carpooling-btn--outline" onclick="this.closest('.carpooling-modal').classList.remove('activo');">
                <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="carpooling-btn carpooling-btn--primary" onclick="Carpooling.guardarVehiculo(document.getElementById('carpooling-form-vehiculo')); this.closest('.carpooling-modal').classList.remove('activo');">
                <?php esc_html_e('Guardar vehiculo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </div>
</div>
