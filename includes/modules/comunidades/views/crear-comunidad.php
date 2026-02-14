<?php
/**
 * Vista: Crear Comunidad
 *
 * Variables disponibles:
 * - $categorias: array de categorias disponibles
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-com-formulario-contenedor">
    <div class="flavor-com-formulario-header">
        <h2><?php esc_html_e('Crear una nueva comunidad', 'flavor-chat-ia'); ?></h2>
        <p class="flavor-com-formulario-intro">
            <?php esc_html_e('Crea un espacio para reunir personas con intereses comunes.', 'flavor-chat-ia'); ?>
        </p>
    </div>

    <form id="flavor-com-form-crear" class="flavor-com-formulario" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('flavor_comunidades_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="comunidades_crear">

        <div class="flavor-com-campo">
            <label for="com-nombre" class="flavor-com-label">
                <?php esc_html_e('Nombre de la comunidad', 'flavor-chat-ia'); ?>
                <span class="requerido">*</span>
            </label>
            <input type="text" id="com-nombre" name="nombre" class="flavor-com-input" required maxlength="100"
                   placeholder="<?php esc_attr_e('Ej: Amantes del cafe', 'flavor-chat-ia'); ?>">
        </div>

        <div class="flavor-com-campo">
            <label for="com-descripcion" class="flavor-com-label">
                <?php esc_html_e('Descripcion', 'flavor-chat-ia'); ?>
                <span class="requerido">*</span>
            </label>
            <textarea id="com-descripcion" name="descripcion" class="flavor-com-textarea" required rows="4" maxlength="500"
                      placeholder="<?php esc_attr_e('Describe de que trata tu comunidad y que tipo de personas buscas...', 'flavor-chat-ia'); ?>"></textarea>
            <span class="flavor-com-ayuda"><?php esc_html_e('Maximo 500 caracteres', 'flavor-chat-ia'); ?></span>
        </div>

        <div class="flavor-com-campo-grupo">
            <div class="flavor-com-campo flavor-com-campo-mitad">
                <label for="com-categoria" class="flavor-com-label">
                    <?php esc_html_e('Categoria', 'flavor-chat-ia'); ?>
                    <span class="requerido">*</span>
                </label>
                <select id="com-categoria" name="categoria" class="flavor-com-select" required>
                    <option value=""><?php esc_html_e('Selecciona una categoria', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categorias as $slug => $nombre): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-com-campo flavor-com-campo-mitad">
                <label for="com-tipo" class="flavor-com-label">
                    <?php esc_html_e('Tipo de comunidad', 'flavor-chat-ia'); ?>
                </label>
                <select id="com-tipo" name="tipo" class="flavor-com-select">
                    <option value="publica"><?php esc_html_e('Publica - Cualquiera puede unirse', 'flavor-chat-ia'); ?></option>
                    <option value="privada"><?php esc_html_e('Privada - Requiere aprobacion', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
        </div>

        <div class="flavor-com-campo">
            <label for="com-imagen" class="flavor-com-label">
                <?php esc_html_e('Imagen de portada (opcional)', 'flavor-chat-ia'); ?>
            </label>
            <div class="flavor-com-upload-area" id="com-upload-area">
                <input type="file" id="com-imagen" name="imagen" accept="image/jpeg,image/png,image/webp" class="flavor-com-input-file">
                <div class="flavor-com-upload-placeholder">
                    <span class="dashicons dashicons-format-image"></span>
                    <p><?php esc_html_e('Arrastra una imagen o haz clic para seleccionar', 'flavor-chat-ia'); ?></p>
                    <span class="flavor-com-ayuda"><?php esc_html_e('JPG, PNG o WebP. Maximo 2MB. Recomendado: 1200x400px', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-com-upload-preview" style="display: none;">
                    <img src="" alt="" id="com-imagen-preview">
                    <button type="button" class="flavor-com-btn-quitar-imagen">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="flavor-com-campo">
            <label class="flavor-com-label"><?php esc_html_e('Reglas de la comunidad (opcional)', 'flavor-chat-ia'); ?></label>
            <textarea id="com-reglas" name="reglas" class="flavor-com-textarea" rows="3" maxlength="1000"
                      placeholder="<?php esc_attr_e('Define las reglas o normas de convivencia de tu comunidad...', 'flavor-chat-ia'); ?>"></textarea>
        </div>

        <div class="flavor-com-formulario-acciones">
            <button type="submit" class="flavor-com-boton flavor-com-boton-primario flavor-com-boton-grande">
                <span class="dashicons dashicons-groups"></span>
                <?php esc_html_e('Crear comunidad', 'flavor-chat-ia'); ?>
            </button>
        </div>

        <div class="flavor-com-mensaje flavor-com-mensaje-oculto" id="com-mensaje-resultado"></div>
    </form>
</div>
