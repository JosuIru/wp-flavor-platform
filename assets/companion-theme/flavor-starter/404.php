<?php
/**
 * Template para página 404
 *
 * @package Flavor_Starter
 */

get_header();
?>

<main id="main-content" class="flex-1 flex items-center justify-center">
    <div class="max-w-lg mx-auto px-4 py-20 text-center">

        <!-- Ilustración 404 -->
        <div class="mb-8">
            <svg class="w-48 h-48 mx-auto text-gray-300" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="100" cy="100" r="80" stroke="currentColor" stroke-width="2" stroke-dasharray="8 8"/>
                <text x="100" y="115" text-anchor="middle" class="text-6xl font-bold" fill="currentColor">404</text>
            </svg>
        </div>

        <!-- Mensaje -->
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            <?php esc_html_e('Página no encontrada', 'flavor-starter'); ?>
        </h1>

        <p class="text-lg text-gray-600 mb-8">
            <?php esc_html_e('Lo sentimos, la página que buscas no existe o ha sido movida.', 'flavor-starter'); ?>
        </p>

        <!-- Acciones -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <?php esc_html_e('Volver al inicio', 'flavor-starter'); ?>
            </a>

            <button onclick="history.back()" class="inline-flex items-center justify-center px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <?php esc_html_e('Volver atrás', 'flavor-starter'); ?>
            </button>
        </div>

        <!-- Búsqueda -->
        <div class="mt-12 pt-8 border-t border-gray-200">
            <p class="text-gray-600 mb-4"><?php esc_html_e('¿Buscas algo específico?', 'flavor-starter'); ?></p>
            <?php get_search_form(); ?>
        </div>

    </div>
</main>

<?php
get_footer();
