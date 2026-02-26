<?php
/**
 * Componente: Empty State
 *
 * Estado vacío reutilizable con icono, mensaje y CTA opcional.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $icon       Emoji o icono grande
 * @param string $title      Título del estado vacío
 * @param string $text       Texto descriptivo
 * @param string $cta_text   Texto del botón CTA (opcional)
 * @param string $cta_action Acción onclick del CTA (JS)
 * @param string $cta_url    URL del CTA (alternativa a cta_action)
 * @param string $color      Color del botón CTA (red, green, blue, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar funciones helper si no están cargadas
if (!function_exists('flavor_get_color_classes')) {
    require_once __DIR__ . '/_functions.php';
}

// Valores por defecto
$icon = $icon ?? '📭';
$title = $title ?? __('No hay elementos', 'flavor-chat-ia');
$text = $text ?? '';
$cta_text = $cta_text ?? '';
$cta_action = $cta_action ?? '';
$cta_url = $cta_url ?? '';
$color = $color ?? 'blue';

$color_classes = flavor_get_color_classes($color);
?>

<div class="text-center py-16 bg-gray-50 rounded-2xl">
    <div class="text-6xl mb-4"><?php echo esc_html($icon); ?></div>

    <h3 class="text-xl font-semibold text-gray-700 mb-2">
        <?php echo esc_html($title); ?>
    </h3>

    <?php if ($text): ?>
    <p class="text-gray-500 mb-6">
        <?php echo esc_html($text); ?>
    </p>
    <?php endif; ?>

    <?php if ($cta_text): ?>
        <?php if ($cta_url): ?>
        <a href="<?php echo esc_url($cta_url); ?>"
           class="<?php echo esc_attr($color_classes['bg_solid']); ?> text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition-opacity inline-block">
            <?php echo esc_html($cta_text); ?>
        </a>
        <?php elseif ($cta_action): ?>
        <button class="<?php echo esc_attr($color_classes['bg_solid']); ?> text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition-opacity"
                onclick="<?php echo esc_attr($cta_action); ?>">
            <?php echo esc_html($cta_text); ?>
        </button>
        <?php endif; ?>
    <?php endif; ?>
</div>
