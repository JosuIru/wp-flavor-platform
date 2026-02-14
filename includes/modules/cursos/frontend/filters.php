<?php
/**
 * Template: Filtros de búsqueda de cursos
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Cursos
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener valores actuales de filtros
$nivel_filter     = isset($_GET['nivel']) ? sanitize_text_field($_GET['nivel']) : '';
$categoria_filter = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';

$has_filters = !empty($nivel_filter) || !empty($categoria_filter);

// Obtener categorías de cursos
$categorias = get_terms(array(
    'taxonomy'   => 'categoria_curso',
    'hide_empty' => true,
));
?>

<div class="bg-white rounded-xl shadow-md p-6 sticky top-6">
    <h3 class="text-xl font-bold mb-4">
        <?php esc_html_e('Filtros', 'flavor-chat-ia'); ?>
    </h3>

    <form method="get">
        <!-- Filtro de nivel -->
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">
                <?php esc_html_e('Nivel', 'flavor-chat-ia'); ?>
            </label>
            <select name="nivel" class="w-full px-4 py-2 border rounded-lg">
                <option value="">
                    <?php esc_html_e('Todos', 'flavor-chat-ia'); ?>
                </option>
                <option value="principiante" <?php selected($nivel_filter, 'principiante'); ?>>
                    <?php esc_html_e('Principiante', 'flavor-chat-ia'); ?>
                </option>
                <option value="intermedio" <?php selected($nivel_filter, 'intermedio'); ?>>
                    <?php esc_html_e('Intermedio', 'flavor-chat-ia'); ?>
                </option>
                <option value="avanzado" <?php selected($nivel_filter, 'avanzado'); ?>>
                    <?php esc_html_e('Avanzado', 'flavor-chat-ia'); ?>
                </option>
            </select>
        </div>

        <!-- Filtro de categoría -->
        <?php if ($categorias && !is_wp_error($categorias)) : ?>
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2">
                    <?php esc_html_e('Categoría', 'flavor-chat-ia'); ?>
                </label>
                <select name="categoria" class="w-full px-4 py-2 border rounded-lg">
                    <option value="">
                        <?php esc_html_e('Todas', 'flavor-chat-ia'); ?>
                    </option>
                    <?php foreach ($categorias as $categoria) : ?>
                        <option value="<?php echo esc_attr($categoria->slug); ?>" <?php selected($categoria_filter, $categoria->slug); ?>>
                            <?php echo esc_html($categoria->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- Botón filtrar -->
        <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
        </button>

        <?php if ($has_filters) : ?>
            <a href="<?php echo get_post_type_archive_link('curso'); ?>"
               class="block text-center mt-3 px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </form>
</div>
