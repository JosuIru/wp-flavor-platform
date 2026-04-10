<?php
/**
 * Componente: Accordion
 *
 * Acordeón/colapsable genérico para FAQs, menús, etc.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $items       Items: [['title' => 'Pregunta', 'content' => 'Respuesta', 'icon' => '❓', 'open' => false]]
 * @param string $color       Color del tema
 * @param bool   $allow_multiple Permitir múltiples abiertos
 * @param bool   $bordered    Con bordes
 * @param bool   $separated   Separados (con gap)
 * @param string $icon_position Posición del icono: 'left', 'right'
 * @param string $id          ID único
 */

if (!defined('ABSPATH')) {
    exit;
}

$items = $items ?? [];
$color = $color ?? 'blue';
$allow_multiple = $allow_multiple ?? false;
$bordered = $bordered ?? true;
$separated = $separated ?? false;
$icon_position = $icon_position ?? 'right';
$accordion_id = $id ?? 'accordion-' . wp_rand(1000, 9999);

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

$container_class = $separated ? 'space-y-3' : ($bordered ? 'border border-gray-200 rounded-2xl divide-y divide-gray-200' : 'divide-y divide-gray-100');
?>

<div id="<?php echo esc_attr($accordion_id); ?>"
     class="flavor-accordion <?php echo esc_attr($container_class); ?>"
     data-allow-multiple="<?php echo $allow_multiple ? 'true' : 'false'; ?>">

    <?php foreach ($items as $index => $item):
        $item_id = $accordion_id . '-item-' . $index;
        $is_open = $item['open'] ?? false;
        $item_title = $item['title'] ?? '';
        $item_content = $item['content'] ?? '';
        $item_icon = $item['icon'] ?? '';
        $item_disabled = $item['disabled'] ?? false;

        $item_class = $separated
            ? 'bg-white rounded-xl border border-gray-200 overflow-hidden'
            : ($bordered ? '' : '');
    ?>
        <div class="accordion-item <?php echo esc_attr($item_class); ?>"
             data-item-id="<?php echo esc_attr($item_id); ?>">

            <!-- Header -->
            <button type="button"
                    id="<?php echo esc_attr($item_id); ?>-header"
                    aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
                    aria-controls="<?php echo esc_attr($item_id); ?>-content"
                    class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors <?php echo $item_disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'; ?>"
                    onclick="<?php echo $item_disabled ? '' : "flavorAccordion.toggle('" . esc_js($accordion_id) . "', '" . esc_js($item_id) . "')"; ?>"
                    <?php if ($item_disabled): ?>disabled<?php endif; ?>>

                <?php if ($icon_position === 'left'): ?>
                    <span class="accordion-icon mr-3 text-gray-400 transition-transform <?php echo $is_open ? 'rotate-180' : ''; ?>">
                        ▼
                    </span>
                <?php endif; ?>

                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <?php if ($item_icon): ?>
                        <span class="text-lg flex-shrink-0"><?php echo esc_html($item_icon); ?></span>
                    <?php endif; ?>
                    <span class="font-medium text-gray-900 truncate"><?php echo esc_html($item_title); ?></span>
                </div>

                <?php if ($icon_position === 'right'): ?>
                    <span class="accordion-icon ml-3 text-gray-400 transition-transform flex-shrink-0 <?php echo $is_open ? 'rotate-180' : ''; ?>">
                        ▼
                    </span>
                <?php endif; ?>
            </button>

            <!-- Content -->
            <div id="<?php echo esc_attr($item_id); ?>-content"
                 role="region"
                 aria-labelledby="<?php echo esc_attr($item_id); ?>-header"
                 class="accordion-content overflow-hidden transition-all duration-300 <?php echo $is_open ? '' : 'hidden'; ?>">
                <div class="p-4 pt-0 text-gray-600">
                    <?php
                    if (!empty($item_content)) {
                        echo wp_kses_post($item_content);
                    } elseif (!empty($item['callback']) && is_callable($item['callback'])) {
                        call_user_func($item['callback'], $item);
                    }
                    ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
window.flavorAccordion = window.flavorAccordion || {
    toggle: function(accordionId, itemId) {
        const accordion = document.getElementById(accordionId);
        const item = accordion.querySelector('[data-item-id="' + itemId + '"]');
        if (!accordion || !item) return;

        const header = item.querySelector('[aria-expanded]');
        const content = item.querySelector('.accordion-content');
        const icon = item.querySelector('.accordion-icon');
        const isOpen = header.getAttribute('aria-expanded') === 'true';
        const allowMultiple = accordion.dataset.allowMultiple === 'true';

        // Close others if not allowing multiple
        if (!allowMultiple && !isOpen) {
            accordion.querySelectorAll('.accordion-item').forEach(otherItem => {
                if (otherItem !== item) {
                    const otherHeader = otherItem.querySelector('[aria-expanded]');
                    const otherContent = otherItem.querySelector('.accordion-content');
                    const otherIcon = otherItem.querySelector('.accordion-icon');

                    if (otherHeader.getAttribute('aria-expanded') === 'true') {
                        otherHeader.setAttribute('aria-expanded', 'false');
                        otherContent.classList.add('hidden');
                        if (otherIcon) otherIcon.classList.remove('rotate-180');
                    }
                }
            });
        }

        // Toggle current
        header.setAttribute('aria-expanded', !isOpen);
        content.classList.toggle('hidden');
        if (icon) icon.classList.toggle('rotate-180');

        // Dispatch event
        accordion.dispatchEvent(new CustomEvent('accordion-toggle', {
            detail: { itemId, isOpen: !isOpen }
        }));
    },

    openAll: function(accordionId) {
        const accordion = document.getElementById(accordionId);
        if (!accordion) return;

        accordion.querySelectorAll('.accordion-item').forEach(item => {
            const header = item.querySelector('[aria-expanded]');
            const content = item.querySelector('.accordion-content');
            const icon = item.querySelector('.accordion-icon');

            header.setAttribute('aria-expanded', 'true');
            content.classList.remove('hidden');
            if (icon) icon.classList.add('rotate-180');
        });
    },

    closeAll: function(accordionId) {
        const accordion = document.getElementById(accordionId);
        if (!accordion) return;

        accordion.querySelectorAll('.accordion-item').forEach(item => {
            const header = item.querySelector('[aria-expanded]');
            const content = item.querySelector('.accordion-content');
            const icon = item.querySelector('.accordion-icon');

            header.setAttribute('aria-expanded', 'false');
            content.classList.add('hidden');
            if (icon) icon.classList.remove('rotate-180');
        });
    }
};
</script>
