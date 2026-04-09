<?php
/**
 * Componente: Card de Colectivo/Asociación
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$colectivo = $item ?? $card_item ?? [];
if (empty($colectivo)) return;

$id = $colectivo['id'] ?? 0;
$nombre = $colectivo['nombre'] ?? $colectivo['title'] ?? __('Colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN);
$descripcion = $colectivo['descripcion'] ?? '';
$url = $colectivo['url'] ?? '#';
$categoria = $colectivo['categoria'] ?? __('General', FLAVOR_PLATFORM_TEXT_DOMAIN);
$miembros = $colectivo['miembros'] ?? 0;
$reunion = $colectivo['reunion'] ?? '';
$logo = $colectivo['logo'] ?? '';
$inicial = mb_substr($nombre, 0, 1);
?>

<article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="p-6">
        <!-- Logo y nombre -->
        <div class="flex items-center gap-4 mb-4">
            <?php if ($logo): ?>
            <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($nombre); ?>"
                 class="w-14 h-14 rounded-xl object-cover flex-shrink-0">
            <?php else: ?>
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-rose-400 to-red-500 flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-xl"><?php echo esc_html($inicial); ?></span>
            </div>
            <?php endif; ?>
            <div>
                <h2 class="text-lg font-bold text-gray-900 group-hover:text-rose-600 transition-colors">
                    <a href="<?php echo esc_url($url); ?>">
                        <?php echo esc_html($nombre); ?>
                    </a>
                </h2>
                <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-700">
                    <?php echo esc_html($categoria); ?>
                </span>
            </div>
        </div>

        <?php if ($descripcion): ?>
        <p class="text-sm text-gray-600 line-clamp-2 mb-4">
            <?php echo esc_html($descripcion); ?>
        </p>
        <?php endif; ?>

        <div class="space-y-2 text-sm text-gray-500">
            <div class="flex items-center gap-2">
                👥 <span><?php echo esc_html($miembros); ?> <?php echo esc_html__('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <?php if ($reunion): ?>
            <div class="flex items-center gap-2">
                📅 <span><?php echo esc_html($reunion); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="<?php echo esc_url($url); ?>"
               class="block w-full py-2 rounded-xl text-center text-rose-600 font-semibold text-sm bg-rose-50 hover:bg-rose-100 transition-colors">
                <?php echo esc_html__('Ver colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</article>
