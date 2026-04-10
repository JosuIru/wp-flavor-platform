<?php
/**
 * Template: Hero Biblioteca
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;
$color = $color_primario ?? '#6366f1';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?>" style="background: linear-gradient(135deg, <?php echo esc_attr($color); ?>, <?php echo esc_attr($color); ?>dd);">
    <div class="max-w-6xl mx-auto px-6 py-20 text-center text-white">
        <div class="text-6xl mb-6">📚</div>
        <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo esc_html($titulo ?? 'Biblioteca Comunitaria'); ?></h1>
        <p class="text-xl md:text-2xl opacity-90 mb-8 max-w-2xl mx-auto"><?php echo esc_html($subtitulo ?? 'Comparte y descubre libros con tus vecinos'); ?></p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="<?php echo esc_url(home_url('/biblioteca/')); ?>" class="bg-white text-indigo-700 px-8 py-3 rounded-xl font-semibold hover:bg-indigo-50 transition-colors">Ver Catálogo</a>
            <a href="<?php echo esc_url(home_url('/biblioteca/donar/')); ?>" class="bg-white/20 text-white px-8 py-3 rounded-xl font-semibold hover:bg-white/30 transition-colors">Donar Libro</a>
        </div>
    </div>
</section>
