<?php
/**
 * Template: Hero Comunidades
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$color = $color_primario ?? '#f43f5e';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> relative overflow-hidden" style="background: linear-gradient(135deg, <?php echo esc_attr($color); ?>, <?php echo esc_attr($color); ?>dd);">
    <div class="max-w-6xl mx-auto px-6 py-20 text-center text-white">
        <div class="text-6xl mb-6">🏘️</div>
        <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo esc_html($titulo ?? 'Comunidades'); ?></h1>
        <p class="text-xl md:text-2xl opacity-90 mb-8 max-w-2xl mx-auto"><?php echo esc_html($subtitulo ?? 'Conecta con tu vecindario'); ?></p>

        <div class="flex flex-wrap justify-center gap-3 mb-8">
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm">🏠 Vecinales</span>
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm">⚽ Deportivas</span>
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm">🎭 Culturales</span>
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm">🤝 Solidarias</span>
        </div>

        <div class="flex flex-wrap justify-center gap-4">
            <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" class="bg-white text-rose-700 px-8 py-3 rounded-xl font-semibold hover:bg-rose-50 transition-colors">
                Explorar Comunidades
            </a>
            <a href="<?php echo esc_url(home_url('/comunidades/crear/')); ?>" class="bg-white/20 text-white px-8 py-3 rounded-xl font-semibold hover:bg-white/30 transition-colors">
                Crear Comunidad
            </a>
        </div>
    </div>
</section>
