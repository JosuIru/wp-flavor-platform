<?php
/**
 * Componente: Dashboard Section
 *
 * Sección de dashboard reutilizable con header, contenido y acciones.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $title       Título de la sección
 * @param string $subtitle    Subtítulo opcional
 * @param string $icon        Icono emoji
 * @param string $color       Color del tema
 * @param array  $actions     Acciones en header: [['label' => 'Ver todo', 'url' => '#', 'icon' => '→']]
 * @param string $content     Contenido HTML o usar slot
 * @param bool   $collapsible Permitir colapsar
 * @param bool   $collapsed   Estado inicial colapsado
 * @param string $id          ID único para la sección
 */

if (!defined('ABSPATH')) {
    exit;
}

$title = $title ?? '';
$subtitle = $subtitle ?? '';
$icon = $icon ?? '';
$color = $color ?? 'blue';
$actions = $actions ?? [];
$collapsible = $collapsible ?? false;
$collapsed = $collapsed ?? false;
$section_id = $id ?? 'section-' . wp_rand(1000, 9999);

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}
?>

<section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
         id="<?php echo esc_attr($section_id); ?>">

    <!-- Header -->
    <div class="flex items-center justify-between p-5 border-b border-gray-100 <?php echo $collapsible ? 'cursor-pointer hover:bg-gray-50 transition-colors' : ''; ?>"
         <?php if ($collapsible): ?>
         onclick="document.getElementById('<?php echo esc_attr($section_id); ?>-content').classList.toggle('hidden'); this.querySelector('.collapse-icon').classList.toggle('rotate-180');"
         <?php endif; ?>>

        <div class="flex items-center gap-3">
            <?php if ($icon): ?>
                <span class="text-2xl"><?php echo esc_html($icon); ?></span>
            <?php endif; ?>
            <div>
                <h3 class="font-bold text-gray-900"><?php echo esc_html($title); ?></h3>
                <?php if ($subtitle): ?>
                    <p class="text-sm text-gray-500"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <?php foreach ($actions as $action): ?>
                <a href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                   class="text-sm font-medium <?php echo esc_attr($color_classes['text']); ?> hover:opacity-80 transition-opacity flex items-center gap-1"
                   <?php if (!empty($action['onclick'])): ?>onclick="<?php echo esc_attr($action['onclick']); ?>"<?php endif; ?>>
                    <?php echo esc_html($action['label'] ?? ''); ?>
                    <?php if (!empty($action['icon'])): ?>
                        <span><?php echo esc_html($action['icon']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <?php if ($collapsible): ?>
                <span class="collapse-icon text-gray-400 transition-transform <?php echo $collapsed ? '' : 'rotate-180'; ?>">▼</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Content -->
    <div id="<?php echo esc_attr($section_id); ?>-content"
         class="p-5 <?php echo $collapsed ? 'hidden' : ''; ?>">
        <?php
        // Si hay contenido como parámetro, mostrarlo
        if (!empty($content)) {
            echo wp_kses_post($content);
        }
        // Si hay callback, ejecutarlo
        elseif (!empty($content_callback) && is_callable($content_callback)) {
            call_user_func($content_callback);
        }
        ?>
    </div>
</section>
