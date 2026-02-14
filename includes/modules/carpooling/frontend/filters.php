<?php
/**
 * Template: Filtros de búsqueda de viajes
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Carpooling
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener valores actuales de filtros
$fecha_filter   = isset($_GET['fecha']) ? sanitize_text_field($_GET['fecha']) : '';
$origen_filter  = isset($_GET['origen']) ? sanitize_text_field($_GET['origen']) : '';
$destino_filter = isset($_GET['destino']) ? sanitize_text_field($_GET['destino']) : '';

$has_filters = !empty($fecha_filter) || !empty($origen_filter) || !empty($destino_filter);
?>

<div class="bg-white rounded-xl shadow-md p-6 sticky top-6">
    <h3 class="text-xl font-bold mb-4">
        <?php esc_html_e('Filtros', 'flavor-chat-ia'); ?>
    </h3>

    <form method="get">
        <!-- Filtro de fecha -->
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">
                <?php esc_html_e('Fecha', 'flavor-chat-ia'); ?>
            </label>
            <input
                type="date"
                name="fecha"
                value="<?php echo esc_attr($fecha_filter); ?>"
                min="<?php echo date('Y-m-d'); ?>"
                class="w-full px-4 py-2 border rounded-lg"
            />
        </div>

        <!-- Filtro de origen -->
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">
                <?php esc_html_e('Origen', 'flavor-chat-ia'); ?>
            </label>
            <input
                type="text"
                name="origen"
                value="<?php echo esc_attr($origen_filter); ?>"
                placeholder="<?php esc_attr_e('Ciudad de salida...', 'flavor-chat-ia'); ?>"
                class="w-full px-4 py-2 border rounded-lg"
            />
        </div>

        <!-- Filtro de destino -->
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">
                <?php esc_html_e('Destino', 'flavor-chat-ia'); ?>
            </label>
            <input
                type="text"
                name="destino"
                value="<?php echo esc_attr($destino_filter); ?>"
                placeholder="<?php esc_attr_e('Ciudad de llegada...', 'flavor-chat-ia'); ?>"
                class="w-full px-4 py-2 border rounded-lg"
            />
        </div>

        <!-- Botón filtrar -->
        <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
        </button>

        <?php if ($has_filters) : ?>
            <!-- Botón limpiar filtros -->
            <a href="<?php echo get_post_type_archive_link('carpooling'); ?>"
               class="block text-center mt-3 px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </form>
</div>
