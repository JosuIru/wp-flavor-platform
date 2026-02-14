<?php
/**
 * Template: Filtros de búsqueda de biblioteca
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Biblioteca
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener valores actuales de filtros
$genero_filter     = isset($_GET['genero']) ? sanitize_text_field($_GET['genero']) : '';
$disponible_filter = isset($_GET['disponible']) ? sanitize_text_field($_GET['disponible']) : '';

$has_filters = !empty($genero_filter) || $disponible_filter !== '';

// Obtener géneros de libros
$generos = get_terms(array(
    'taxonomy'   => 'genero_libro',
    'hide_empty' => true,
));
?>

<div class="bg-white rounded-xl shadow-md p-6 sticky top-6">
    <h3 class="text-xl font-bold mb-4">
        <?php esc_html_e('Filtros', 'flavor-chat-ia'); ?>
    </h3>

    <form method="get">
        <!-- Filtro de género -->
        <?php if ($generos && !is_wp_error($generos)) : ?>
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2">
                    <?php esc_html_e('Género', 'flavor-chat-ia'); ?>
                </label>
                <select name="genero" class="w-full px-4 py-2 border rounded-lg">
                    <option value="">
                        <?php esc_html_e('Todos', 'flavor-chat-ia'); ?>
                    </option>
                    <?php foreach ($generos as $genero) : ?>
                        <option value="<?php echo esc_attr($genero->slug); ?>" <?php selected($genero_filter, $genero->slug); ?>>
                            <?php echo esc_html($genero->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- Filtro de disponibilidad -->
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">
                <?php esc_html_e('Disponibilidad', 'flavor-chat-ia'); ?>
            </label>
            <select name="disponible" class="w-full px-4 py-2 border rounded-lg">
                <option value="">
                    <?php esc_html_e('Todos', 'flavor-chat-ia'); ?>
                </option>
                <option value="1" <?php selected($disponible_filter, '1'); ?>>
                    <?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?>
                </option>
                <option value="0" <?php selected($disponible_filter, '0'); ?>>
                    <?php esc_html_e('Prestados', 'flavor-chat-ia'); ?>
                </option>
            </select>
        </div>

        <!-- Botón filtrar -->
        <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
        </button>

        <?php if ($has_filters) : ?>
            <a href="<?php echo get_post_type_archive_link('biblioteca'); ?>"
               class="block text-center mt-3 px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </form>
</div>
