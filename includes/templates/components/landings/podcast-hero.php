<?php
/**
 * Template: Hero Podcast
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;
$color = $color_primario ?? '#14b8a6';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?>" style="background: linear-gradient(135deg, <?php echo esc_attr($color); ?>, <?php echo esc_attr($color); ?>dd);">
    <div class="max-w-6xl mx-auto px-6 py-20 text-center text-white">
        <div class="text-6xl mb-6">🎙️</div>
        <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo esc_html($titulo ?? 'Nuestro Podcast'); ?></h1>
        <p class="text-xl md:text-2xl opacity-90 mb-8 max-w-2xl mx-auto"><?php echo esc_html($subtitulo ?? 'Voces de la comunidad'); ?></p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="<?php echo esc_url(home_url('/podcast/')); ?>" class="bg-white text-teal-700 px-8 py-3 rounded-xl font-semibold hover:bg-teal-50 transition-colors">Escuchar Episodios</a>
            <a href="<?php echo esc_url(home_url('/podcast/suscribirse/')); ?>" class="bg-white/20 text-white px-8 py-3 rounded-xl font-semibold hover:bg-white/30 transition-colors">Suscribirse</a>
        </div>
    </div>
</section>
