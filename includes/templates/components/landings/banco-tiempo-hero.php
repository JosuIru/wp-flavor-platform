<?php
/**
 * Template: Hero Banco de Tiempo
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;
$color = $color_primario ?? '#8b5cf6';
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> relative overflow-hidden" style="background: linear-gradient(135deg, <?php echo esc_attr($color); ?>, <?php echo esc_attr($color); ?>dd);">
    <?php if (!empty($imagen_fondo)): ?>
    <div class="absolute inset-0 opacity-20">
        <?php echo wp_get_attachment_image($imagen_fondo, 'full', false, ['class' => 'w-full h-full object-cover']); ?>
    </div>
    <?php endif; ?>

    <div class="relative max-w-6xl mx-auto px-6 py-20 text-center text-white">
        <div class="text-6xl mb-6">⏰</div>
        <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo esc_html($titulo ?? 'Banco de Tiempo'); ?></h1>
        <p class="text-xl md:text-2xl opacity-90 mb-8 max-w-2xl mx-auto"><?php echo esc_html($subtitulo ?? 'Intercambia habilidades con tu comunidad'); ?></p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto mb-8">
            <div class="bg-white/10 rounded-xl p-6">
                <div class="text-3xl mb-2">🎁</div>
                <h3 class="font-semibold mb-1">Ofrece</h3>
                <p class="text-sm opacity-80">Comparte tus habilidades</p>
            </div>
            <div class="bg-white/10 rounded-xl p-6">
                <div class="text-3xl mb-2">🔄</div>
                <h3 class="font-semibold mb-1">Intercambia</h3>
                <p class="text-sm opacity-80">1 hora = 1 hora</p>
            </div>
            <div class="bg-white/10 rounded-xl p-6">
                <div class="text-3xl mb-2">🙋</div>
                <h3 class="font-semibold mb-1">Recibe</h3>
                <p class="text-sm opacity-80">Lo que necesites</p>
            </div>
        </div>

        <div class="flex flex-wrap justify-center gap-4">
            <a href="<?php echo esc_url(home_url('/banco-tiempo/')); ?>" class="bg-white text-violet-700 px-8 py-3 rounded-xl font-semibold hover:bg-violet-50 transition-colors">
                Ver Servicios
            </a>
            <a href="<?php echo esc_url(home_url('/banco-tiempo/publicar/')); ?>" class="bg-white/20 text-white px-8 py-3 rounded-xl font-semibold hover:bg-white/30 transition-colors">
                Ofrecer Servicio
            </a>
        </div>
    </div>
</section>
