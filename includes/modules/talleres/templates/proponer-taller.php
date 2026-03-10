<?php
/**
 * Template: Proponer Taller
 *
 * Variables disponibles:
 * - $settings: configuracion del modulo
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_actual = wp_get_current_user();
$categorias = $settings['categorias'] ?? ['general' => 'General', 'arte' => 'Arte', 'tecnologia' => 'Tecnologia', 'cocina' => 'Cocina', 'idiomas' => 'Idiomas', 'otros' => 'Otros'];
$niveles = ['principiante' => __('Principiante', 'flavor-chat-ia'), 'intermedio' => __('Intermedio', 'flavor-chat-ia'), 'avanzado' => __('Avanzado', 'flavor-chat-ia')];
?>

<div class="talleres-proponer">
    <h2><?php _e('Proponer un Taller', 'flavor-chat-ia'); ?></h2>
    <p class="talleres-proponer-intro">
        <?php _e('Comparte tus conocimientos con la comunidad. Completa el formulario para proponer un nuevo taller.', 'flavor-chat-ia'); ?>
    </p>

    <form method="post" class="talleres-form talleres-proponer-form" enctype="multipart/form-data">
        <?php wp_nonce_field('talleres_proponer', 'talleres_proponer_nonce'); ?>

        <div class="talleres-form-section">
            <h3><?php _e('Informacion basica', 'flavor-chat-ia'); ?></h3>

            <div class="talleres-form-group">
                <label for="taller_titulo"><?php _e('Titulo del taller', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                <input type="text" id="taller_titulo" name="titulo" required maxlength="200" placeholder="<?php esc_attr_e('Ej: Introduccion a la ceramica', 'flavor-chat-ia'); ?>">
            </div>

            <div class="talleres-form-row">
                <div class="talleres-form-group">
                    <label for="taller_categoria"><?php _e('Categoria', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                    <select id="taller_categoria" name="categoria" required>
                        <option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($categorias as $slug => $nombre): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="talleres-form-group">
                    <label for="taller_nivel"><?php _e('Nivel', 'flavor-chat-ia'); ?></label>
                    <select id="taller_nivel" name="nivel">
                        <option value=""><?php _e('Todos los niveles', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($niveles as $slug => $nombre): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="talleres-form-group">
                <label for="taller_descripcion"><?php _e('Descripcion', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                <textarea id="taller_descripcion" name="descripcion" rows="5" required placeholder="<?php esc_attr_e('Describe de que trata el taller, que aprenderan los participantes...', 'flavor-chat-ia'); ?>"></textarea>
            </div>
        </div>

        <div class="talleres-form-section">
            <h3><?php _e('Detalles practicos', 'flavor-chat-ia'); ?></h3>

            <div class="talleres-form-row">
                <div class="talleres-form-group">
                    <label for="taller_duracion"><?php _e('Duracion estimada', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="taller_duracion" name="duracion" placeholder="<?php esc_attr_e('Ej: 2 horas', 'flavor-chat-ia'); ?>">
                </div>

                <div class="talleres-form-group">
                    <label for="taller_capacidad"><?php _e('Plazas maximas', 'flavor-chat-ia'); ?></label>
                    <input type="number" id="taller_capacidad" name="capacidad" min="1" max="100" value="15">
                </div>
            </div>

            <div class="talleres-form-group">
                <label for="taller_requisitos"><?php _e('Requisitos previos', 'flavor-chat-ia'); ?></label>
                <textarea id="taller_requisitos" name="requisitos" rows="3" placeholder="<?php esc_attr_e('Conocimientos o materiales que deben traer los participantes...', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div class="talleres-form-group">
                <label for="taller_materiales"><?php _e('Materiales necesarios', 'flavor-chat-ia'); ?></label>
                <textarea id="taller_materiales" name="materiales" rows="3" placeholder="<?php esc_attr_e('Lista de materiales que se utilizaran...', 'flavor-chat-ia'); ?>"></textarea>
            </div>
        </div>

        <div class="talleres-form-section">
            <h3><?php _e('Disponibilidad', 'flavor-chat-ia'); ?></h3>

            <div class="talleres-form-group">
                <label for="taller_disponibilidad"><?php _e('Cuando podrias impartir el taller?', 'flavor-chat-ia'); ?></label>
                <textarea id="taller_disponibilidad" name="disponibilidad" rows="3" placeholder="<?php esc_attr_e('Indica tus preferencias de horario y fechas...', 'flavor-chat-ia'); ?>"></textarea>
            </div>
        </div>

        <div class="talleres-form-actions">
            <button type="submit" name="talleres_proponer" class="talleres-btn talleres-btn-primary">
                <?php _e('Enviar propuesta', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>
