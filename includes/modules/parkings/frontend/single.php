<?php
/**
 * Template: Detalle de plaza de parking
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Parkings
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    // Obtener metadatos
    $ubicacion       = get_post_meta(get_the_ID(), '_ubicacion', true);
    $precio_hora     = get_post_meta(get_the_ID(), '_precio_hora', true);
    $precio_dia      = get_post_meta(get_the_ID(), '_precio_dia', true);
    $disponible      = get_post_meta(get_the_ID(), '_disponible', true);
    $caracteristicas = get_post_meta(get_the_ID(), '_caracteristicas', true);
?>

<div class="flavor-container py-8">
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2">
            <article class="bg-white rounded-xl shadow-lg p-8">
                <h1 class="text-4xl font-bold mb-6">
                    <?php the_title(); ?>
                </h1>

                <?php if ($ubicacion) : ?>
                    <p class="text-xl text-gray-600 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <?php echo esc_html($ubicacion); ?>
                    </p>
                <?php endif; ?>

                <!-- Estado de disponibilidad -->
                <div class="mb-8">
                    <?php if ($disponible) : ?>
                        <span class="bg-green-100 text-green-700 px-6 py-3 rounded-lg font-bold text-lg">
                            <?php esc_html_e('Plaza Disponible', 'flavor-chat-ia'); ?>
                        </span>
                    <?php else : ?>
                        <span class="bg-red-100 text-red-700 px-6 py-3 rounded-lg font-bold text-lg">
                            <?php esc_html_e('Plaza Ocupada', 'flavor-chat-ia'); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Descripción -->
                <div class="prose mb-8">
                    <?php the_content(); ?>
                </div>

                <?php if ($caracteristicas) : ?>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-bold mb-3">
                            <?php esc_html_e('Características', 'flavor-chat-ia'); ?>
                        </h3>
                        <p><?php echo esc_html($caracteristicas); ?></p>
                    </div>
                <?php endif; ?>
            </article>
        </div>

        <!-- Sidebar de precios y reserva -->
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-6 sticky top-6">
                <h3 class="text-xl font-bold mb-4">
                    <?php esc_html_e('Precio', 'flavor-chat-ia'); ?>
                </h3>

                <?php if ($precio_hora) : ?>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            <?php esc_html_e('Por hora', 'flavor-chat-ia'); ?>
                        </p>
                        <p class="text-4xl font-bold text-primary">
                            <?php echo esc_html($precio_hora); ?>€
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($precio_dia) : ?>
                    <div class="mb-6">
                        <p class="text-sm text-gray-600">
                            <?php esc_html_e('Por día', 'flavor-chat-ia'); ?>
                        </p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo esc_html($precio_dia); ?>€
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (is_user_logged_in() && $disponible) : ?>
                    <button class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark font-bold">
                        <?php esc_html_e('Reservar Plaza', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif (!is_user_logged_in()) : ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>"
                       class="block text-center w-full px-6 py-3 bg-primary text-white rounded-lg font-bold">
                        <?php esc_html_e('Inicia sesión', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<?php
endwhile;

get_footer();
?>
