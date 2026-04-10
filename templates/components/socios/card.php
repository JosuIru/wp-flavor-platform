<?php
/**
 * Componente: Card de Socio
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$socio = $item ?? $card_item ?? [];
if (empty($socio)) return;

$id = $socio['id'] ?? 0;
$nombre = $socio['nombre'] ?? '';
$iniciales = $socio['iniciales'] ?? mb_substr($nombre, 0, 2);
$nivel = $socio['nivel'] ?? 'Básico';
$miembro_desde = $socio['miembro_desde'] ?? '';
$intereses = $socio['intereses'] ?? [];
$color = $socio['color'] ?? 'bg-rose-500';

$nivel_clase = match($nivel) {
    'Premium' => 'bg-amber-100 text-amber-800',
    'Pro' => 'bg-purple-100 text-purple-800',
    default => 'bg-gray-100 text-gray-700',
};
?>

<article class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow"
         data-nivel="<?php echo esc_attr(sanitize_title($nivel)); ?>">
    <div class="flex items-center mb-4">
        <div class="<?php echo esc_attr($color); ?> w-14 h-14 rounded-full flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
            <?php echo esc_html($iniciales); ?>
        </div>
        <div class="ml-4">
            <h3 class="font-semibold text-gray-900"><?php echo esc_html($nombre); ?></h3>
            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo esc_attr($nivel_clase); ?>">
                <?php echo esc_html($nivel); ?>
            </span>
        </div>
    </div>

    <?php if ($miembro_desde): ?>
    <p class="text-sm text-gray-500 mb-3">
        <?php echo esc_html__('Socio desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        <?php echo esc_html(date_i18n('F Y', strtotime($miembro_desde))); ?>
    </p>
    <?php endif; ?>

    <?php if (!empty($intereses)): ?>
    <div class="flex flex-wrap gap-1.5 mb-4">
        <?php foreach ($intereses as $interes): ?>
        <span class="px-2 py-1 bg-rose-50 text-rose-700 rounded text-xs">
            <?php echo esc_html($interes); ?>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_item_url('socios', $id, 'perfil')); ?>"
       class="block w-full text-center py-2 border border-rose-300 text-rose-600 rounded-lg text-sm font-medium hover:bg-rose-50 transition-colors">
        <?php echo esc_html__('Contactar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>
</article>
