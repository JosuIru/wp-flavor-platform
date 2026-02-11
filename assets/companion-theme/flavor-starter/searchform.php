<?php
/**
 * Template para formulario de búsqueda
 *
 * @package Flavor_Starter
 */

$unique_id = wp_unique_id('search-form-');
?>

<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label for="<?php echo esc_attr($unique_id); ?>" class="screen-reader-text">
        <?php esc_html_e('Buscar:', 'flavor-starter'); ?>
    </label>
    <div class="relative">
        <input
            type="search"
            id="<?php echo esc_attr($unique_id); ?>"
            class="w-full px-4 py-3 pl-12 bg-gray-100 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            placeholder="<?php esc_attr_e('Buscar...', 'flavor-starter'); ?>"
            value="<?php echo get_search_query(); ?>"
            name="s"
        />
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
            <?php esc_html_e('Buscar', 'flavor-starter'); ?>
        </button>
    </div>
</form>
