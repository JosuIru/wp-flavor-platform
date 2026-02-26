<?php
/**
 * Componente: Archive Header con Gradiente
 *
 * Header reutilizable para páginas de archivo con gradiente de color,
 * título, subtítulo, badge y CTA.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $title       Título principal (requerido)
 * @param string $subtitle    Subtítulo descriptivo
 * @param string $icon        Emoji o icono (opcional, se añade al título)
 * @param string $color       Color del gradiente (red, green, blue, yellow, purple, cyan, orange, teal, pink, indigo)
 * @param string $badge       Texto del badge (ej: "123 registrados")
 * @param string $cta_text    Texto del botón CTA
 * @param string $cta_action  Acción onclick del CTA (JS)
 * @param string $cta_url     URL del CTA (alternativa a cta_action)
 * @param string $cta_icon    Icono del CTA (emoji, opcional)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar funciones helper si no están cargadas
if (!function_exists('flavor_get_gradient_classes')) {
    require_once __DIR__ . '/_functions.php';
}

// Valores por defecto
$title = $title ?? '';
$subtitle = $subtitle ?? '';
$icon = $icon ?? '';
$color = $color ?? 'blue';
$badge = $badge ?? '';
$cta_text = $cta_text ?? '';
$cta_action = $cta_action ?? '';
$cta_url = $cta_url ?? '';
$cta_icon = $cta_icon ?? '';

// No renderizar si no hay título
if (empty($title)) {
    return;
}

// Obtener clases de gradiente
$gradient = flavor_get_gradient_classes($color);
$color_classes = flavor_get_color_classes($color);

// Construir título con icono
$display_title = $icon ? "{$icon} {$title}" : $title;
?>

<div class="bg-gradient-to-r <?php echo esc_attr($gradient['from']); ?> <?php echo esc_attr($gradient['to']); ?> text-white rounded-2xl p-8 mb-8 shadow-lg">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-3xl font-bold mb-2"><?php echo esc_html($display_title); ?></h1>
            <?php if ($subtitle): ?>
            <p class="text-white/80"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-4">
            <?php if ($badge): ?>
            <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                <?php echo esc_html($badge); ?>
            </span>
            <?php endif; ?>

            <?php if ($cta_text): ?>
                <?php if ($cta_url): ?>
                <a href="<?php echo esc_url($cta_url); ?>"
                   class="bg-white <?php echo esc_attr($color_classes['text']); ?> px-6 py-3 rounded-xl font-semibold hover:bg-opacity-90 transition-all shadow-md inline-flex items-center gap-2">
                    <?php if ($cta_icon): ?><span><?php echo esc_html($cta_icon); ?></span><?php endif; ?>
                    <?php echo esc_html($cta_text); ?>
                </a>
                <?php elseif ($cta_action): ?>
                <button class="bg-white <?php echo esc_attr($color_classes['text']); ?> px-6 py-3 rounded-xl font-semibold hover:bg-opacity-90 transition-all shadow-md inline-flex items-center gap-2"
                        onclick="<?php echo esc_attr($cta_action); ?>">
                    <?php if ($cta_icon): ?><span><?php echo esc_html($cta_icon); ?></span><?php endif; ?>
                    <?php echo esc_html($cta_text); ?>
                </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
