<?php
/**
 * Template: Compartir Saber
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = Flavor_Chat_Saberes_Ancestrales_Module::CATEGORIAS_SABER;
?>

<div class="sa-container">
    <header class="sa-header">
        <h2><?php esc_html_e('Compartir un Saber', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Documenta el conocimiento tradicional para preservarlo y transmitirlo', 'flavor-chat-ia'); ?></p>
    </header>

    <form class="sa-form sa-form-saber">
        <div class="sa-form-grupo">
            <label for="sa-titulo"><?php esc_html_e('Nombre del saber', 'flavor-chat-ia'); ?> *</label>
            <input type="text" name="titulo" id="sa-titulo" required
                   placeholder="<?php esc_attr_e('Ej: Elaboración de queso artesano', 'flavor-chat-ia'); ?>">
        </div>

        <div class="sa-form-grupo">
            <label for="sa-categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label>
            <select name="categoria" id="sa-categoria">
                <?php foreach ($categorias as $cat_id => $cat_data) : ?>
                <option value="<?php echo esc_attr($cat_id); ?>"><?php echo esc_html($cat_data['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="sa-form-grupo">
            <label for="sa-descripcion"><?php esc_html_e('Descripción detallada', 'flavor-chat-ia'); ?> *</label>
            <textarea name="descripcion" id="sa-descripcion" rows="8" required
                      placeholder="<?php esc_attr_e('Describe el saber: en qué consiste, cómo se hace, qué materiales se necesitan, trucos y consejos...', 'flavor-chat-ia'); ?>"></textarea>
        </div>

        <div class="sa-form-grupo">
            <label for="sa-origen"><?php esc_html_e('Origen / Lugar', 'flavor-chat-ia'); ?></label>
            <input type="text" name="origen" id="sa-origen"
                   placeholder="<?php esc_attr_e('Ej: Valle de Arán, Pirineos', 'flavor-chat-ia'); ?>">
        </div>

        <div class="sa-form-grupo">
            <label for="sa-portador"><?php esc_html_e('¿De quién lo aprendiste?', 'flavor-chat-ia'); ?></label>
            <input type="text" name="portador" id="sa-portador"
                   placeholder="<?php esc_attr_e('Ej: Mi abuela María, 1950', 'flavor-chat-ia'); ?>">
            <small style="color: var(--sa-text-light);"><?php esc_html_e('Honramos a quienes nos transmitieron este conocimiento', 'flavor-chat-ia'); ?></small>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <button type="submit" class="sa-btn sa-btn--primary">
                <span class="dashicons dashicons-book-alt"></span>
                <?php esc_html_e('Documentar este saber', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>
