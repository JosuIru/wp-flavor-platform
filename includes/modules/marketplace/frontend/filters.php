<?php
/**
 * Template: Filtros de búsqueda del marketplace
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Marketplace
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener valor actual del filtro
$categoria_filter = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';

// Obtener categorías del marketplace
$categorias = get_terms(array(
    'taxonomy'   => 'categoria_marketplace',
    'hide_empty' => true,
));
?>

<div class="bg-white rounded-xl shadow-md p-6 sticky top-6">
    <h3 class="text-xl font-bold mb-4">
        <?php esc_html_e('Filtros', 'flavor-chat-ia'); ?>
    </h3>

    <form method="get">
        <!-- Filtro de categoría -->
        <?php if ($categorias && !is_wp_error($categorias)) : ?>
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-3">
                    <?php esc_html_e('Categoría', 'flavor-chat-ia'); ?>
                </label>
                <div class="space-y-2">
                    <?php foreach ($categorias as $categoria) : ?>
                        <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                            <input
                                type="radio"
                                name="categoria"
                                value="<?php echo esc_attr($categoria->slug); ?>"
                                <?php checked($categoria_filter, $categoria->slug); ?>
                                onchange="this.form.submit()"
                                class="text-primary focus:ring-primary"
                            />
                            <span><?php echo esc_html($categoria->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($categoria_filter) : ?>
            <a href="<?php echo get_post_type_archive_link('marketplace'); ?>"
               class="block text-center px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </form>
</div>
