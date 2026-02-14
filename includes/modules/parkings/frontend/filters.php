<?php
/**
 * Template: Filtros de búsqueda de parkings
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Parkings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener valores actuales de filtros
$ubicacion_filter   = isset($_GET['ubicacion']) ? sanitize_text_field($_GET['ubicacion']) : '';
$disponible_filter  = isset($_GET['disponible']) ? sanitize_text_field($_GET['disponible']) : '';

$has_filters = !empty($ubicacion_filter) || !empty($disponible_filter);
?>

<div class="bg-white rounded-xl shadow-md p-6 sticky top-6">
    <h3 class="text-xl font-bold mb-4">
        <?php esc_html_e('Filtros', 'flavor-chat-ia'); ?>
    </h3>

    <form method="get">
        <!-- Filtro de ubicación -->
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">
                <?php esc_html_e('Ubicación', 'flavor-chat-ia'); ?>
            </label>
            <input
                type="text"
                name="ubicacion"
                value="<?php echo esc_attr($ubicacion_filter); ?>"
                placeholder="<?php esc_attr_e('Barrio, calle...', 'flavor-chat-ia'); ?>"
                class="w-full px-4 py-2 border rounded-lg"
            />
        </div>

        <!-- Filtro de disponibilidad -->
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">
                <?php esc_html_e('Disponibilidad', 'flavor-chat-ia'); ?>
            </label>
            <select name="disponible" class="w-full px-4 py-2 border rounded-lg">
                <option value="">
                    <?php esc_html_e('Todas', 'flavor-chat-ia'); ?>
                </option>
                <option value="1" <?php selected($disponible_filter, '1'); ?>>
                    <?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?>
                </option>
            </select>
        </div>

        <!-- Botón filtrar -->
        <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
        </button>

        <?php if ($has_filters) : ?>
            <a href="<?php echo get_post_type_archive_link('parking'); ?>"
               class="block text-center mt-3 px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </form>
</div>
