<?php
/**
 * Template: Ofrecer Servicio - Banco de Tiempo
 *
 * Variables disponibles:
 * - $comunidad_id: ID de la comunidad (opcional)
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = [
    'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'educacion' => __('Educacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'bricolaje' => __('Bricolaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'tecnologia' => __('Tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'transporte' => __('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'compras' => __('Compras y recados', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cocina' => __('Cocina', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'jardineria' => __('Jardineria', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'idiomas' => __('Idiomas', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="bt-ofrecer-servicio">
    <h2><?php _e('Ofrecer un Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
    <p class="bt-intro"><?php _e('Comparte tus habilidades con la comunidad y gana horas para intercambiar por otros servicios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

    <form id="form-ofrecer-servicio" class="bt-form" method="post">
        <?php wp_nonce_field('banco_tiempo_nonce', 'bt_nonce_field'); ?>
        <?php if (!empty($comunidad_id) && $comunidad_id > 0): ?>
        <input type="hidden" name="comunidad_id" value="<?php echo esc_attr($comunidad_id); ?>">
        <?php endif; ?>

        <div class="bt-form-section">
            <h3><?php _e('Informacion del servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="bt-form-group">
                <label for="servicio_titulo"><?php _e('Titulo del servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                <input type="text" id="servicio_titulo" name="titulo" required maxlength="150" placeholder="<?php esc_attr_e('Ej: Clases de ingles nivel basico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <small><?php _e('Describe brevemente que ofreces', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
            </div>

            <div class="bt-form-group">
                <label for="servicio_descripcion"><?php _e('Descripcion detallada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                <textarea id="servicio_descripcion" name="descripcion" rows="5" required placeholder="<?php esc_attr_e('Explica en que consiste tu servicio, tu experiencia, disponibilidad...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div class="bt-form-row">
                <div class="bt-form-group">
                    <label for="servicio_categoria"><?php _e('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                    <select id="servicio_categoria" name="categoria" required>
                        <option value=""><?php _e('Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($categorias as $slug => $nombre): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bt-form-group">
                    <label for="servicio_horas"><?php _e('Horas estimadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                    <input type="number" id="servicio_horas" name="horas_estimadas" min="0.5" max="8" step="0.5" value="1" required>
                    <small><?php _e('Duracion aproximada del servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                </div>
            </div>
        </div>

        <div class="bt-form-section">
            <h3><?php _e('Disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="bt-form-group">
                <label><?php _e('Dias disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <div class="bt-checkbox-group">
                    <label><input type="checkbox" name="dias[]" value="lunes"> <?php _e('Lunes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <label><input type="checkbox" name="dias[]" value="martes"> <?php _e('Martes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <label><input type="checkbox" name="dias[]" value="miercoles"> <?php _e('Miercoles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <label><input type="checkbox" name="dias[]" value="jueves"> <?php _e('Jueves', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <label><input type="checkbox" name="dias[]" value="viernes"> <?php _e('Viernes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <label><input type="checkbox" name="dias[]" value="sabado"> <?php _e('Sabado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <label><input type="checkbox" name="dias[]" value="domingo"> <?php _e('Domingo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                </div>
            </div>

            <div class="bt-form-group">
                <label for="servicio_horario"><?php _e('Franja horaria preferida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select id="servicio_horario" name="horario">
                    <option value="flexible"><?php _e('Flexible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="manana"><?php _e('Mananas (9:00 - 14:00)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="tarde"><?php _e('Tardes (16:00 - 21:00)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="finde"><?php _e('Fines de semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <div class="bt-form-group">
                <label for="servicio_zona"><?php _e('Zona/Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="text" id="servicio_zona" name="zona" placeholder="<?php esc_attr_e('Ej: Centro, zona norte...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>
        </div>

        <div class="bt-form-actions">
            <button type="submit" name="bt_ofrecer_servicio" class="bt-btn bt-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Publicar Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <a href="<?php echo esc_url(home_url('/banco-tiempo/')); ?>" class="bt-btn bt-btn-secondary">
                <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </form>
</div>
