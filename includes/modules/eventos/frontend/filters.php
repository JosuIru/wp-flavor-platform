<?php
/**
 * Template: Filtros de búsqueda de eventos
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener valores actuales de filtros
$fecha_filter     = isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : '';
$categoria_filter = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$precio_filter    = isset($_GET['precio']) ? sanitize_text_field($_GET['precio']) : '';

$has_filters = !empty($fecha_filter) || !empty($categoria_filter) || !empty($precio_filter);

// Obtener categorías de eventos
$categorias = get_terms(array(
    'taxonomy'   => 'categoria_evento',
    'hide_empty' => true,
));
?>

<div class="bg-white rounded-xl shadow-md p-6 sticky top-6">
    <h3 class="text-xl font-bold mb-4">
        <?php esc_html_e('Filtros', 'flavor-chat-ia'); ?>
    </h3>

    <form method="get">
        <!-- Filtro de fecha -->
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">
                <?php esc_html_e('Fecha desde', 'flavor-chat-ia'); ?>
            </label>
            <input
                type="date"
                name="fecha_desde"
                value="<?php echo esc_attr($fecha_filter); ?>"
                min="<?php echo date('Y-m-d'); ?>"
                class="w-full px-4 py-2 border rounded-lg"
            />
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

        <!-- Filtro de precio -->
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">
                <?php esc_html_e('Precio', 'flavor-chat-ia'); ?>
            </label>
            <select name="precio" class="w-full px-4 py-2 border rounded-lg">
                <option value="">
                    <?php esc_html_e('Todos', 'flavor-chat-ia'); ?>
                </option>
                <option value="gratis" <?php selected($precio_filter, 'gratis'); ?>>
                    <?php esc_html_e('Gratis', 'flavor-chat-ia'); ?>
                </option>
                <option value="pago" <?php selected($precio_filter, 'pago'); ?>>
                    <?php esc_html_e('De pago', 'flavor-chat-ia'); ?>
                </option>
            </select>
        </div>

        <!-- Botón filtrar -->
        <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
        </button>

        <?php if ($has_filters) : ?>
            <a href="<?php echo get_post_type_archive_link('evento'); ?>"
               class="block text-center mt-3 px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </form>
</div>
