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
    'cuidados' => __('Cuidados', 'flavor-chat-ia'),
    'educacion' => __('Educacion', 'flavor-chat-ia'),
    'bricolaje' => __('Bricolaje', 'flavor-chat-ia'),
    'tecnologia' => __('Tecnologia', 'flavor-chat-ia'),
    'transporte' => __('Transporte', 'flavor-chat-ia'),
    'compras' => __('Compras y recados', 'flavor-chat-ia'),
    'cocina' => __('Cocina', 'flavor-chat-ia'),
    'jardineria' => __('Jardineria', 'flavor-chat-ia'),
    'idiomas' => __('Idiomas', 'flavor-chat-ia'),
    'otros' => __('Otros', 'flavor-chat-ia'),
];
?>

<div class="bt-ofrecer-servicio">
    <h2><?php _e('Ofrecer un Servicio', 'flavor-chat-ia'); ?></h2>
    <p class="bt-intro"><?php _e('Comparte tus habilidades con la comunidad y gana horas para intercambiar por otros servicios.', 'flavor-chat-ia'); ?></p>

    <form id="form-ofrecer-servicio" class="bt-form" method="post">
        <?php wp_nonce_field('banco_tiempo_nonce', 'bt_nonce_field'); ?>
        <?php if (!empty($comunidad_id) && $comunidad_id > 0): ?>
        <input type="hidden" name="comunidad_id" value="<?php echo esc_attr($comunidad_id); ?>">
        <?php endif; ?>

        <div class="bt-form-section">
            <h3><?php _e('Informacion del servicio', 'flavor-chat-ia'); ?></h3>

            <div class="bt-form-group">
                <label for="servicio_titulo"><?php _e('Titulo del servicio', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                <input type="text" id="servicio_titulo" name="titulo" required maxlength="150" placeholder="<?php esc_attr_e('Ej: Clases de ingles nivel basico', 'flavor-chat-ia'); ?>">
                <small><?php _e('Describe brevemente que ofreces', 'flavor-chat-ia'); ?></small>
            </div>

            <div class="bt-form-group">
                <label for="servicio_descripcion"><?php _e('Descripcion detallada', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                <textarea id="servicio_descripcion" name="descripcion" rows="5" required placeholder="<?php esc_attr_e('Explica en que consiste tu servicio, tu experiencia, disponibilidad...', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div class="bt-form-row">
                <div class="bt-form-group">
                    <label for="servicio_categoria"><?php _e('Categoria', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                    <select id="servicio_categoria" name="categoria" required>
                        <option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($categorias as $slug => $nombre): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bt-form-group">
                    <label for="servicio_horas"><?php _e('Horas estimadas', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                    <input type="number" id="servicio_horas" name="horas_estimadas" min="0.5" max="8" step="0.5" value="1" required>
                    <small><?php _e('Duracion aproximada del servicio', 'flavor-chat-ia'); ?></small>
                </div>
            </div>
        </div>

        <div class="bt-form-section">
            <h3><?php _e('Disponibilidad', 'flavor-chat-ia'); ?></h3>

            <div class="bt-form-group">
                <label><?php _e('Dias disponibles', 'flavor-chat-ia'); ?></label>
                <div class="bt-checkbox-group">
                    <label><input type="checkbox" name="dias[]" value="lunes"> <?php _e('Lunes', 'flavor-chat-ia'); ?></label>
                    <label><input type="checkbox" name="dias[]" value="martes"> <?php _e('Martes', 'flavor-chat-ia'); ?></label>
                    <label><input type="checkbox" name="dias[]" value="miercoles"> <?php _e('Miercoles', 'flavor-chat-ia'); ?></label>
                    <label><input type="checkbox" name="dias[]" value="jueves"> <?php _e('Jueves', 'flavor-chat-ia'); ?></label>
                    <label><input type="checkbox" name="dias[]" value="viernes"> <?php _e('Viernes', 'flavor-chat-ia'); ?></label>
                    <label><input type="checkbox" name="dias[]" value="sabado"> <?php _e('Sabado', 'flavor-chat-ia'); ?></label>
                    <label><input type="checkbox" name="dias[]" value="domingo"> <?php _e('Domingo', 'flavor-chat-ia'); ?></label>
                </div>
            </div>

            <div class="bt-form-group">
                <label for="servicio_horario"><?php _e('Franja horaria preferida', 'flavor-chat-ia'); ?></label>
                <select id="servicio_horario" name="horario">
                    <option value="flexible"><?php _e('Flexible', 'flavor-chat-ia'); ?></option>
                    <option value="manana"><?php _e('Mananas (9:00 - 14:00)', 'flavor-chat-ia'); ?></option>
                    <option value="tarde"><?php _e('Tardes (16:00 - 21:00)', 'flavor-chat-ia'); ?></option>
                    <option value="finde"><?php _e('Fines de semana', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="bt-form-group">
                <label for="servicio_zona"><?php _e('Zona/Barrio', 'flavor-chat-ia'); ?></label>
                <input type="text" id="servicio_zona" name="zona" placeholder="<?php esc_attr_e('Ej: Centro, zona norte...', 'flavor-chat-ia'); ?>">
            </div>
        </div>

        <div class="bt-form-actions">
            <button type="submit" name="bt_ofrecer_servicio" class="bt-btn bt-btn-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Publicar Servicio', 'flavor-chat-ia'); ?>
            </button>
            <a href="<?php echo esc_url(home_url('/banco-tiempo/')); ?>" class="bt-btn bt-btn-secondary">
                <?php _e('Cancelar', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </form>
</div>
