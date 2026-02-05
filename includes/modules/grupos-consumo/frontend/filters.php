<?php
/**
 * Grupos de Consumo - Filters Component
 */
if (!defined('ABSPATH')) exit;
$categoria_actual = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
?>
<div class="flavor-component bg-white rounded-xl shadow-md p-6 sticky top-6">
    <h3 class="text-xl font-bold mb-4">Filtros</h3>
    <form method="get">
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Categoría</label>
            <?php
            $categorias = get_terms(array('taxonomy' => 'categoria_producto', 'hide_empty' => true));
            if ($categorias) :
                echo '<div class="space-y-2">';
                echo '<label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">';
                echo '<input type="radio" name="categoria" value="" ' . checked($categoria_actual, '', false) . ' onchange="this.form.submit()" class="text-primary"/>';
                echo '<span>Todos</span></label>';
                foreach ($categorias as $cat) :
                    echo '<label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">';
                    echo '<input type="radio" name="categoria" value="' . esc_attr($cat->slug) . '" ' . checked($categoria_actual, $cat->slug, false) . ' onchange="this.form.submit()" class="text-primary"/>';
                    echo '<span>' . esc_html($cat->name) . ' <span class="text-gray-500 text-sm">(' . $cat->count . ')</span></span></label>';
                endforeach;
                echo '</div>';
            endif;
            ?>
        </div>
        <?php if ($categoria_actual) : ?>
            <a href="<?php echo esc_url(get_post_type_archive_link('grupo_consumo')); ?>" class="block text-center px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">Limpiar filtros</a>
        <?php endif; ?>
    </form>
</div>
