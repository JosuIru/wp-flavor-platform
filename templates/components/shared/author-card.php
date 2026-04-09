<?php
/**
 * Componente: Author Card
 *
 * Tarjeta de autor/usuario reutilizable para sidebars.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $author     Datos del autor: name, avatar, bio, verified, rating, stats, url
 * @param string $color      Color del tema
 * @param array  $actions    Acciones: [['label' => 'Contactar', 'icon' => '💬', 'action' => 'fn()', 'primary' => true]]
 * @param string $title      Título de la sección (ej: "Publicado por", "Conductor")
 */

if (!defined('ABSPATH')) {
    exit;
}

$author = $author ?? [];
$color = $color ?? 'blue';
$actions = $actions ?? [];
$title = $title ?? __('Publicado por', FLAVOR_PLATFORM_TEXT_DOMAIN);

// Extraer datos del autor
$name = $author['name'] ?? $author['nombre'] ?? __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN);
$avatar = $author['avatar'] ?? $author['imagen'] ?? '';
$bio = $author['bio'] ?? $author['descripcion'] ?? '';
$verified = $author['verified'] ?? $author['verificado'] ?? false;
$rating = $author['rating'] ?? $author['valoracion'] ?? 0;
$reviews_count = $author['reviews_count'] ?? $author['num_valoraciones'] ?? 0;
$member_since = $author['member_since'] ?? $author['miembro_desde'] ?? '';
$url = $author['url'] ?? '';
$stats = $author['stats'] ?? [];

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = [
        'bg' => 'bg-blue-100',
        'text' => 'text-blue-700',
        'bg_solid' => 'bg-blue-500',
    ];
}
?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <?php if ($title): ?>
        <h3 class="text-sm font-medium text-gray-500 mb-4"><?php echo esc_html($title); ?></h3>
    <?php endif; ?>

    <div class="flex items-center gap-4 mb-4">
        <?php if ($avatar): ?>
            <img src="<?php echo esc_url($avatar); ?>"
                 alt="<?php echo esc_attr($name); ?>"
                 class="w-16 h-16 rounded-full object-cover border-2 border-gray-100">
        <?php else: ?>
            <div class="w-16 h-16 rounded-full <?php echo esc_attr($color_classes['bg']); ?> flex items-center justify-center">
                <span class="text-2xl">👤</span>
            </div>
        <?php endif; ?>

        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <?php if ($url): ?>
                    <a href="<?php echo esc_url($url); ?>" class="font-bold text-gray-900 hover:<?php echo esc_attr($color_classes['text']); ?> transition-colors truncate">
                        <?php echo esc_html($name); ?>
                    </a>
                <?php else: ?>
                    <span class="font-bold text-gray-900 truncate"><?php echo esc_html($name); ?></span>
                <?php endif; ?>

                <?php if ($verified): ?>
                    <span class="text-blue-500 flex-shrink-0" title="<?php esc_attr_e('Verificado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">✓</span>
                <?php endif; ?>
            </div>

            <?php if ($rating > 0): ?>
                <div class="flex items-center gap-1 text-sm">
                    <span class="text-amber-500">⭐</span>
                    <span class="font-medium text-gray-700"><?php echo esc_html(number_format($rating, 1)); ?></span>
                    <?php if ($reviews_count > 0): ?>
                        <span class="text-gray-400">(<?php echo esc_html($reviews_count); ?>)</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($member_since): ?>
                <p class="text-xs text-gray-500 mt-1">
                    <?php echo esc_html__('Miembro desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html($member_since); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($bio): ?>
        <p class="text-sm text-gray-600 mb-4 line-clamp-3"><?php echo esc_html($bio); ?></p>
    <?php endif; ?>

    <?php if (!empty($stats)): ?>
        <div class="grid grid-cols-3 gap-2 mb-4 py-3 border-y border-gray-100">
            <?php foreach ($stats as $stat): ?>
                <div class="text-center">
                    <p class="font-bold text-gray-900"><?php echo esc_html($stat['value'] ?? 0); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html($stat['label'] ?? ''); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($actions)): ?>
        <div class="space-y-2">
            <?php foreach ($actions as $action):
                $is_primary = $action['primary'] ?? false;
                $action_label = $action['label'] ?? '';
                $action_icon = $action['icon'] ?? '';
                $action_url = $action['url'] ?? '';
                $action_onclick = $action['action'] ?? '';

                if ($is_primary) {
                    $btn_class = "w-full py-3 px-4 rounded-xl text-sm font-medium text-white {$color_classes['bg_solid']} hover:opacity-90 transition-all text-center flex items-center justify-center gap-2";
                } else {
                    $btn_class = "w-full py-3 px-4 rounded-xl text-sm font-medium {$color_classes['bg']} {$color_classes['text']} hover:opacity-80 transition-all text-center flex items-center justify-center gap-2";
                }
            ?>
                <?php if ($action_onclick): ?>
                    <button onclick="<?php echo esc_attr($action_onclick); ?>" class="<?php echo esc_attr($btn_class); ?>">
                        <?php if ($action_icon): ?><span><?php echo esc_html($action_icon); ?></span><?php endif; ?>
                        <?php echo esc_html($action_label); ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo esc_url($action_url ?: '#'); ?>" class="<?php echo esc_attr($btn_class); ?>">
                        <?php if ($action_icon): ?><span><?php echo esc_html($action_icon); ?></span><?php endif; ?>
                        <?php echo esc_html($action_label); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
