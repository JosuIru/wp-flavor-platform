<?php
/**
 * Template: Hero Grupos de Consumo
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$color = $color_primario ?? '#84cc16';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> relative overflow-hidden" style="background: linear-gradient(135deg, <?php echo esc_attr($color); ?>, <?php echo esc_attr($color); ?>dd);">
    <?php if (!empty($imagen_fondo)): ?>
    <div class="absolute inset-0 opacity-20">
        <?php echo wp_get_attachment_image($imagen_fondo, 'full', false, ['class' => 'w-full h-full object-cover']); ?>
    </div>
    <?php endif; ?>

    <div class="relative max-w-6xl mx-auto px-6 py-20 text-center text-white">
        <div class="text-6xl mb-6">🥕</div>
        <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo esc_html($titulo ?? 'Grupos de Consumo'); ?></h1>
        <p class="text-xl md:text-2xl opacity-90 mb-8 max-w-2xl mx-auto"><?php echo esc_html($subtitulo ?? 'Consume local, apoya a productores cercanos'); ?></p>

        <?php if (!empty($mostrar_buscador)): ?>
        <form action="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/buscar/')); ?>" method="get" class="max-w-xl mx-auto">
            <div class="flex gap-2">
                <input type="text" name="q" placeholder="Buscar grupos de consumo..."
                       class="flex-1 px-6 py-4 rounded-xl text-gray-800 text-lg focus:outline-none focus:ring-4 focus:ring-white/30">
                <button type="submit" class="bg-white/20 hover:bg-white/30 px-6 py-4 rounded-xl font-semibold transition-colors">
                    Buscar
                </button>
            </div>
        </form>
        <?php endif; ?>

        <div class="flex flex-wrap justify-center gap-4 mt-8">
            <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/')); ?>" class="bg-white text-green-700 px-8 py-3 rounded-xl font-semibold hover:bg-green-50 transition-colors">
                Ver Grupos
            </a>
            <a href="#como-funciona" class="bg-white/20 text-white px-8 py-3 rounded-xl font-semibold hover:bg-white/30 transition-colors">
                Cómo Funciona
            </a>
        </div>
    </div>
</section>
