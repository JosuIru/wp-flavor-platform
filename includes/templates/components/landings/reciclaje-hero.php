<?php
/**
 * Template: Hero Reciclaje
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$color = $color_primario ?? '#10b981';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?>" style="background: linear-gradient(135deg, <?php echo esc_attr($color); ?>, <?php echo esc_attr($color); ?>dd);">
    <div class="max-w-6xl mx-auto px-6 py-20 text-center text-white">
        <div class="text-6xl mb-6">♻️</div>
        <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo esc_html($titulo ?? 'Reciclaje'); ?></h1>
        <p class="text-xl md:text-2xl opacity-90 mb-8 max-w-2xl mx-auto"><?php echo esc_html($subtitulo ?? 'Reduce, reutiliza, recicla'); ?></p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="<?php echo esc_url(home_url('/reciclaje/')); ?>" class="bg-white text-emerald-700 px-8 py-3 rounded-xl font-semibold hover:bg-emerald-50 transition-colors">Puntos de Reciclaje</a>
            <a href="<?php echo esc_url(home_url('/reciclaje/guia/')); ?>" class="bg-white/20 text-white px-8 py-3 rounded-xl font-semibold hover:bg-white/30 transition-colors">Guía de Reciclaje</a>
        </div>
    </div>
</section>
