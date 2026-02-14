<?php
/**
 * Vista: Crear comunidad
 *
 * @package FlavorChatIA
 * @var array $categorias Categorías disponibles
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-com-crear">
    <div class="flavor-com-crear-header">
        <h2><?php esc_html_e('Crear nueva comunidad', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Crea un espacio para conectar personas con intereses comunes.', 'flavor-chat-ia'); ?></p>
    </div>

    <form id="flavor-com-form-crear" class="flavor-com-form" method="post">
        <?php wp_nonce_field('flavor_comunidades_nonce', 'nonce'); ?>

        <div class="flavor-com-form-group">
            <label for="com-nombre" class="flavor-com-label">
                <?php esc_html_e('Nombre de la comunidad', 'flavor-chat-ia'); ?>
                <span class="required">*</span>
            </label>
            <input type="text" id="com-nombre" name="nombre" class="flavor-com-input"
                   required maxlength="100"
                   placeholder="<?php esc_attr_e('Ej: Amantes del senderismo', 'flavor-chat-ia'); ?>">
        </div>

        <div class="flavor-com-form-group">
            <label for="com-descripcion" class="flavor-com-label">
                <?php esc_html_e('Descripción', 'flavor-chat-ia'); ?>
                <span class="required">*</span>
            </label>
            <textarea id="com-descripcion" name="descripcion" class="flavor-com-textarea"
                      required rows="4"
                      placeholder="<?php esc_attr_e('Describe de qué trata tu comunidad, qué actividades realizáis...', 'flavor-chat-ia'); ?>"></textarea>
        </div>

        <div class="flavor-com-form-row">
            <div class="flavor-com-form-group flavor-com-form-half">
                <label for="com-categoria" class="flavor-com-label">
                    <?php esc_html_e('Categoría', 'flavor-chat-ia'); ?>
                    <span class="required">*</span>
                </label>
                <select id="com-categoria" name="categoria" class="flavor-com-select" required>
                    <option value=""><?php esc_html_e('Selecciona...', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categorias as $clave => $etiqueta): ?>
                        <option value="<?php echo esc_attr($clave); ?>">
                            <?php echo esc_html($etiqueta); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-com-form-group flavor-com-form-half">
                <label for="com-tipo" class="flavor-com-label">
                    <?php esc_html_e('Tipo de comunidad', 'flavor-chat-ia'); ?>
                </label>
                <select id="com-tipo" name="tipo" class="flavor-com-select">
                    <option value="abierta"><?php esc_html_e('Abierta - Cualquiera puede unirse', 'flavor-chat-ia'); ?></option>
                    <option value="cerrada"><?php esc_html_e('Cerrada - Requiere aprobación', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
        </div>

        <div class="flavor-com-form-group">
            <label class="flavor-com-label"><?php esc_html_e('Imagen de portada', 'flavor-chat-ia'); ?></label>
            <div class="flavor-com-upload-area" id="com-upload-area">
                <input type="file" id="com-imagen" name="imagen" accept="image/*" style="display: none;">
                <div class="flavor-com-upload-placeholder">
                    <span class="dashicons dashicons-format-image"></span>
                    <p><?php esc_html_e('Arrastra una imagen o haz clic para seleccionar', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-com-preview" id="com-preview" style="display: none;"></div>
            </div>
        </div>

        <div class="flavor-com-form-actions">
            <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" class="flavor-com-btn flavor-com-btn-secondary">
                <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
            </a>
            <button type="submit" class="flavor-com-btn flavor-com-btn-primary" id="com-crear-btn">
                <span class="dashicons dashicons-groups"></span>
                <?php esc_html_e('Crear comunidad', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>

    <div class="flavor-com-mensaje-exito" id="com-mensaje-exito" style="display: none;">
        <span class="dashicons dashicons-yes-alt"></span>
        <h3><?php esc_html_e('¡Comunidad creada correctamente!', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('Ya puedes invitar a otros miembros a unirse.', 'flavor-chat-ia'); ?></p>
        <div class="flavor-com-acciones">
            <a href="#" id="com-ir-comunidad" class="flavor-com-btn flavor-com-btn-primary">
                <?php esc_html_e('Ir a mi comunidad', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</div>
