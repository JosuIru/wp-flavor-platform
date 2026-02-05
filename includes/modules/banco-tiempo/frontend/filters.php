<?php
/**
 * Banco de Tiempo - Filters Component
 */

if (!defined('ABSPATH')) {
    exit;
}

$categoria_actual = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$ordenar_por = isset($_GET['ordenar']) ? sanitize_text_field($_GET['ordenar']) : 'reciente';
?>

<div class="flavor-component bg-white rounded-xl shadow-md p-6 sticky top-6">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Filtros</h3>

    <form method="get" action="<?php echo esc_url(get_post_type_archive_link('banco_tiempo')); ?>" id="filtros-banco-tiempo">
        <!-- Categories Filter -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Categoría</label>
            <?php
            $categorias_terms = get_terms(array(
                'taxonomy' => 'categoria_servicio',
                'hide_empty' => true,
            ));

            if (!is_wp_error($categorias_terms) && !empty($categorias_terms)) :
            ?>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                        <input
                            type="radio"
                            name="categoria"
                            value=""
                            <?php checked($categoria_actual, ''); ?>
                            class="w-4 h-4 text-primary focus:ring-primary"
                            onchange="this.form.submit()"
                        />
                        <span class="text-gray-700">Todas las categorías</span>
                    </label>
                    <?php foreach ($categorias_terms as $term) : ?>
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded transition-colors">
                            <input
                                type="radio"
                                name="categoria"
                                value="<?php echo esc_attr($term->slug); ?>"
                                <?php checked($categoria_actual, $term->slug); ?>
                                class="w-4 h-4 text-primary focus:ring-primary"
                                onchange="this.form.submit()"
                            />
                            <span class="text-gray-700">
                                <?php echo esc_html($term->name); ?>
                                <span class="text-gray-500 text-sm">(<?php echo $term->count; ?>)</span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sort By -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Ordenar por</label>
            <select
                name="ordenar"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                onchange="this.form.submit()"
            >
                <option value="reciente" <?php selected($ordenar_por, 'reciente'); ?>>Más recientes</option>
                <option value="antiguo" <?php selected($ordenar_por, 'antiguo'); ?>>Más antiguos</option>
                <option value="titulo_asc" <?php selected($ordenar_por, 'titulo_asc'); ?>>Título A-Z</option>
                <option value="titulo_desc" <?php selected($ordenar_por, 'titulo_desc'); ?>>Título Z-A</option>
            </select>
        </div>

        <!-- Clear Filters Button -->
        <?php if ($categoria_actual || $ordenar_por !== 'reciente') : ?>
            <div class="pt-4 border-t">
                <a
                    href="<?php echo esc_url(get_post_type_archive_link('banco_tiempo')); ?>"
                    class="block w-full text-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
                >
                    Limpiar filtros
                </a>
            </div>
        <?php endif; ?>

        <!-- Hidden search parameter -->
        <?php if (!empty($_GET['s'])) : ?>
            <input type="hidden" name="s" value="<?php echo esc_attr($_GET['s']); ?>" />
        <?php endif; ?>
    </form>

    <!-- Quick Stats -->
    <div class="mt-6 pt-6 border-t">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Estadísticas</h4>
        <?php
        $total_servicios = wp_count_posts('banco_tiempo');
        $servicios_publicados = $total_servicios->publish;
        ?>
        <div class="space-y-2 text-sm text-gray-600">
            <div class="flex justify-between">
                <span>Servicios totales:</span>
                <span class="font-semibold text-primary"><?php echo number_format($servicios_publicados); ?></span>
            </div>
            <?php if (!empty($categorias_terms)) : ?>
                <div class="flex justify-between">
                    <span>Categorías:</span>
                    <span class="font-semibold text-primary"><?php echo count($categorias_terms); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Help Section -->
    <div class="mt-6 pt-6 border-t">
        <div class="bg-primary bg-opacity-10 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-primary mb-2 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                ¿Qué es el Banco de Tiempo?
            </h4>
            <p class="text-sm text-gray-700">
                Un sistema donde puedes intercambiar servicios con otros miembros de la comunidad. Cada hora vale lo mismo independientemente del servicio.
            </p>
        </div>
    </div>
</div>

<style>
input[type="radio"]:checked {
    background-color: var(--flavor-primary);
    border-color: var(--flavor-primary);
}
</style>
