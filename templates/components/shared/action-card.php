<?php
/**
 * Componente: Action Card
 *
 * Tarjeta de acción rápida para dashboards.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $title       Título de la acción
 * @param string $description Descripción breve
 * @param string $icon        Icono emoji o clase
 * @param string $color       Color del tema
 * @param string $url         URL de destino
 * @param string $action      Función JavaScript onclick
 * @param string $badge       Badge opcional (ej: "Nuevo", "3")
 * @param string $badge_color Color del badge
 * @param bool   $disabled    Deshabilitado
 * @param string $size        Tamaño: 'sm', 'md', 'lg'
 * @param string $layout      Layout: 'vertical', 'horizontal'
 */

if (!defined('ABSPATH')) {
    exit;
}

$title = $title ?? '';
$description = $description ?? '';
$icon = $icon ?? '';
$color = $color ?? 'blue';
$url = $url ?? '';
$action = $action ?? '';
$badge = $badge ?? '';
$badge_color = $badge_color ?? 'red';
$disabled = $disabled ?? false;
$size = $size ?? 'md';
$layout = $layout ?? 'vertical';

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
    $badge_classes = flavor_get_color_classes($badge_color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
    $badge_classes = ['bg' => 'bg-red-100', 'text' => 'text-red-700'];
}

// Clases de tamaño
$size_config = [
    'sm' => [
        'padding' => 'p-3',
        'icon'    => 'w-10 h-10 text-xl',
        'title'   => 'text-sm',
        'desc'    => 'text-xs',
    ],
    'md' => [
        'padding' => 'p-4',
        'icon'    => 'w-12 h-12 text-2xl',
        'title'   => 'text-base',
        'desc'    => 'text-sm',
    ],
    'lg' => [
        'padding' => 'p-6',
        'icon'    => 'w-16 h-16 text-3xl',
        'title'   => 'text-lg',
        'desc'    => 'text-base',
    ],
];
$sizes = $size_config[$size] ?? $size_config['md'];

// Clases de layout
$layout_class = $layout === 'horizontal'
    ? 'flex items-center gap-4'
    : 'flex flex-col items-center text-center';

// Clases base de la card
$card_base = "block bg-white rounded-2xl border border-gray-100 shadow-sm transition-all {$sizes['padding']}";
$card_hover = $disabled
    ? 'opacity-50 cursor-not-allowed'
    : 'hover:shadow-md hover:border-' . $color . '-200 hover:scale-[1.02] cursor-pointer';

$tag = ($url && !$disabled) ? 'a' : 'button';
$onclick = ($action && !$disabled) ? "onclick=\"{$action}\"" : '';
$href = ($url && !$disabled) ? "href=\"{$url}\"" : '';
?>

<<?php echo $tag; ?>
    <?php echo $href; ?>
    <?php echo $onclick; ?>
    class="<?php echo esc_attr($card_base . ' ' . $card_hover . ' ' . $layout_class); ?>"
    <?php if ($disabled): ?>disabled aria-disabled="true"<?php endif; ?>
>
    <!-- Icon -->
    <div class="relative flex-shrink-0">
        <div class="<?php echo esc_attr($sizes['icon']); ?> rounded-xl <?php echo esc_attr($color_classes['bg']); ?> flex items-center justify-center">
            <span><?php echo esc_html($icon); ?></span>
        </div>

        <?php if ($badge): ?>
            <span class="absolute -top-1 -right-1 px-2 py-0.5 text-xs font-medium rounded-full <?php echo esc_attr($badge_classes['bg']); ?> <?php echo esc_attr($badge_classes['text']); ?>">
                <?php echo esc_html($badge); ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- Content -->
    <div class="<?php echo $layout === 'horizontal' ? 'flex-1 text-left' : 'mt-3'; ?>">
        <h4 class="font-semibold text-gray-900 <?php echo esc_attr($sizes['title']); ?>">
            <?php echo esc_html($title); ?>
        </h4>
        <?php if ($description): ?>
            <p class="text-gray-500 <?php echo esc_attr($sizes['desc']); ?> <?php echo $layout === 'horizontal' ? '' : 'mt-1'; ?>">
                <?php echo esc_html($description); ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if ($layout === 'horizontal' && !$disabled): ?>
        <span class="text-gray-400 group-hover:<?php echo esc_attr($color_classes['text']); ?>">→</span>
    <?php endif; ?>
</<?php echo $tag; ?>>
