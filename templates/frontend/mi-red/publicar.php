<?php
/**
 * Publicar - Mi Red Social
 *
 * Formulario de creación de publicaciones.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipos_permitidos = $datos_vista['tipos_permitidos'] ?? [];
$comunidades = $datos_vista['comunidades'] ?? [];
?>

<div class="mi-red-publicar">
    <header class="mi-red-publicar__header">
        <h1 class="mi-red-publicar__title"><?php esc_html_e('Crear publicación', 'flavor-chat-ia'); ?></h1>
    </header>

    <form class="mi-red-publicar__form" id="form-publicar-completo">
        <!-- Tipo de contenido -->
        <div class="mi-red-form-group">
            <label class="mi-red-form-label"><?php esc_html_e('Tipo de contenido', 'flavor-chat-ia'); ?></label>
            <div class="mi-red-tipo-selector">
                <?php foreach ($tipos_permitidos as $key => $tipo) : ?>
                    <label class="mi-red-tipo-option">
                        <input type="radio" name="tipo" value="<?php echo esc_attr($key); ?>" <?php checked($key, 'texto'); ?>>
                        <span class="mi-red-tipo-option__icon"><?php echo $tipo['icon']; ?></span>
                        <span class="mi-red-tipo-option__label"><?php echo esc_html($tipo['label']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Contenido -->
        <div class="mi-red-form-group">
            <label class="mi-red-form-label" for="contenido"><?php esc_html_e('¿Qué quieres compartir?', 'flavor-chat-ia'); ?></label>
            <textarea name="contenido" id="contenido" class="mi-red-textarea" rows="6" placeholder="<?php esc_attr_e('Escribe tu publicación...', 'flavor-chat-ia'); ?>" required></textarea>
        </div>

        <!-- Adjuntos -->
        <div class="mi-red-form-group">
            <label class="mi-red-form-label"><?php esc_html_e('Adjuntos', 'flavor-chat-ia'); ?></label>
            <div class="mi-red-upload-area" id="upload-area">
                <div class="mi-red-upload-area__content">
                    <span class="mi-red-upload-area__icon">📎</span>
                    <p class="mi-red-upload-area__text"><?php esc_html_e('Arrastra archivos aquí o haz clic para seleccionar', 'flavor-chat-ia'); ?></p>
                    <p class="mi-red-upload-area__hint"><?php esc_html_e('Imágenes, videos o audios', 'flavor-chat-ia'); ?></p>
                </div>
                <input type="file" name="adjuntos[]" multiple accept="image/*,video/*,audio/*" hidden>
            </div>
            <div class="mi-red-upload-preview" id="upload-preview"></div>
        </div>

        <!-- Visibilidad -->
        <div class="mi-red-form-group">
            <label class="mi-red-form-label" for="visibilidad"><?php esc_html_e('Visibilidad', 'flavor-chat-ia'); ?></label>
            <select name="visibilidad" id="visibilidad" class="mi-red-select">
                <option value="comunidad"><?php esc_html_e('Comunidad - Visible para todos los miembros', 'flavor-chat-ia'); ?></option>
                <option value="publica"><?php esc_html_e('Público - Visible para todos', 'flavor-chat-ia'); ?></option>
                <option value="seguidores"><?php esc_html_e('Seguidores - Solo tus seguidores', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <!-- Publicar en comunidad -->
        <?php if (!empty($comunidades)) : ?>
            <div class="mi-red-form-group">
                <label class="mi-red-form-label" for="comunidad"><?php esc_html_e('Publicar en comunidad', 'flavor-chat-ia'); ?></label>
                <select name="comunidad_id" id="comunidad" class="mi-red-select">
                    <option value=""><?php esc_html_e('Ninguna - Solo en mi perfil', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($comunidades as $com) : ?>
                        <option value="<?php echo esc_attr($com['id']); ?>"><?php echo esc_html($com['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- Acciones -->
        <div class="mi-red-form-actions">
            <a href="<?php echo esc_url($base_url); ?>" class="mi-red-btn mi-red-btn--secondary">
                <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
            </a>
            <button type="submit" class="mi-red-btn mi-red-btn--primary">
                <?php esc_html_e('Publicar', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.mi-red-publicar {
    max-width: 600px;
    margin: 0 auto;
}

.mi-red-publicar__header {
    margin-bottom: var(--mir-spacing-6);
}

.mi-red-publicar__title {
    font-size: var(--mir-font-size-2xl);
    font-weight: 700;
    margin: 0;
}

.mi-red-publicar__form {
    background: white;
    border-radius: var(--mir-radius-xl);
    padding: var(--mir-spacing-6);
    box-shadow: var(--mir-shadow-sm);
}

.mi-red-form-group {
    margin-bottom: var(--mir-spacing-5);
}

.mi-red-form-label {
    display: block;
    font-weight: 600;
    margin-bottom: var(--mir-spacing-2);
    color: var(--mir-gray-700);
}

.mi-red-tipo-selector {
    display: flex;
    gap: var(--mir-spacing-2);
    flex-wrap: wrap;
}

.mi-red-tipo-option {
    display: flex;
    align-items: center;
    gap: var(--mir-spacing-2);
    padding: var(--mir-spacing-3) var(--mir-spacing-4);
    background: var(--mir-gray-100);
    border-radius: var(--mir-radius-lg);
    cursor: pointer;
    transition: all 0.2s;
}

.mi-red-tipo-option input {
    display: none;
}

.mi-red-tipo-option:has(input:checked) {
    background: var(--mir-primary);
    color: white;
}

.mi-red-tipo-option__icon {
    font-size: 1.25rem;
}

.mi-red-textarea {
    width: 100%;
    padding: var(--mir-spacing-4);
    border: 2px solid var(--mir-gray-200);
    border-radius: var(--mir-radius-lg);
    font-size: var(--mir-font-size-base);
    font-family: inherit;
    resize: vertical;
    transition: border-color 0.2s;
}

.mi-red-textarea:focus {
    outline: none;
    border-color: var(--mir-primary);
}

.mi-red-select {
    width: 100%;
    padding: var(--mir-spacing-3) var(--mir-spacing-4);
    border: 2px solid var(--mir-gray-200);
    border-radius: var(--mir-radius-lg);
    font-size: var(--mir-font-size-base);
    background: white;
    cursor: pointer;
}

.mi-red-select:focus {
    outline: none;
    border-color: var(--mir-primary);
}

.mi-red-upload-area {
    border: 2px dashed var(--mir-gray-300);
    border-radius: var(--mir-radius-lg);
    padding: var(--mir-spacing-8);
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.mi-red-upload-area:hover {
    border-color: var(--mir-primary);
    background: var(--mir-gray-50);
}

.mi-red-upload-area__icon {
    font-size: 2rem;
}

.mi-red-upload-area__text {
    margin: var(--mir-spacing-2) 0 0;
    color: var(--mir-gray-700);
}

.mi-red-upload-area__hint {
    margin: var(--mir-spacing-1) 0 0;
    font-size: var(--mir-font-size-sm);
    color: var(--mir-gray-500);
}

.mi-red-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--mir-spacing-3);
    padding-top: var(--mir-spacing-4);
    border-top: 1px solid var(--mir-gray-200);
}
</style>
