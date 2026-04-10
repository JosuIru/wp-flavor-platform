<?php
/**
 * Componente: Tabs
 *
 * Navegación por pestañas reutilizable.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $tabs       Pestañas: [['id' => 'tab1', 'label' => 'Pestaña 1', 'icon' => '📋', 'badge' => 5, 'content' => '']]
 * @param string $active     ID de la pestaña activa
 * @param string $color      Color del tema
 * @param string $style      Estilo: 'pills', 'underline', 'boxed'
 * @param string $size       Tamaño: 'sm', 'md', 'lg'
 * @param bool   $vertical   Disposición vertical
 * @param string $id         ID del contenedor
 */

if (!defined('ABSPATH')) {
    exit;
}

$tabs = $tabs ?? [];
$active = $active ?? ($tabs[0]['id'] ?? '');
$color = $color ?? 'blue';
$style = $style ?? 'underline';
$size = $size ?? 'md';
$vertical = $vertical ?? false;
$tabs_id = $id ?? 'tabs-' . wp_rand(1000, 9999);

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Clases de tamaño
$size_classes = [
    'sm' => 'text-sm px-3 py-2',
    'md' => 'text-base px-4 py-3',
    'lg' => 'text-lg px-6 py-4',
];
$tab_size = $size_classes[$size] ?? $size_classes['md'];

// Clases de estilo
$style_config = [
    'pills' => [
        'nav'    => 'flex gap-2 p-1 bg-gray-100 rounded-xl',
        'tab'    => 'rounded-lg font-medium transition-all',
        'active' => "text-white {$color_classes['bg_solid']}",
        'inactive' => 'text-gray-600 hover:bg-gray-200',
    ],
    'underline' => [
        'nav'    => 'flex border-b border-gray-200',
        'tab'    => 'font-medium border-b-2 -mb-px transition-colors',
        'active' => "{$color_classes['text']} border-current",
        'inactive' => 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300',
    ],
    'boxed' => [
        'nav'    => 'flex border-b border-gray-200',
        'tab'    => 'font-medium border border-b-0 rounded-t-lg -mb-px transition-colors',
        'active' => "bg-white {$color_classes['text']} border-gray-200",
        'inactive' => 'text-gray-500 border-transparent hover:text-gray-700',
    ],
];
$current_style = $style_config[$style] ?? $style_config['underline'];
?>

<div id="<?php echo esc_attr($tabs_id); ?>"
     class="flavor-tabs <?php echo $vertical ? 'flex gap-6' : ''; ?>">

    <!-- Tab Navigation -->
    <nav class="<?php echo esc_attr($current_style['nav']); ?> <?php echo $vertical ? 'flex-col w-48 flex-shrink-0' : ''; ?>"
         role="tablist">
        <?php foreach ($tabs as $tab):
            $tab_id = $tab['id'] ?? '';
            $is_active = $tab_id === $active;
            $tab_classes = $tab_size . ' ' . $current_style['tab'] . ' ' . ($is_active ? $current_style['active'] : $current_style['inactive']);
        ?>
            <button type="button"
                    id="<?php echo esc_attr($tabs_id . '-tab-' . $tab_id); ?>"
                    role="tab"
                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                    aria-controls="<?php echo esc_attr($tabs_id . '-panel-' . $tab_id); ?>"
                    class="<?php echo esc_attr($tab_classes); ?> flex items-center gap-2"
                    onclick="flavorTabs.switch('<?php echo esc_js($tabs_id); ?>', '<?php echo esc_js($tab_id); ?>')">
                <?php if (!empty($tab['icon'])): ?>
                    <span><?php echo esc_html($tab['icon']); ?></span>
                <?php endif; ?>
                <span><?php echo esc_html($tab['label'] ?? ''); ?></span>
                <?php if (isset($tab['badge'])): ?>
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full <?php echo esc_attr($color_classes['bg']); ?> <?php echo esc_attr($color_classes['text']); ?>">
                        <?php echo esc_html($tab['badge']); ?>
                    </span>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </nav>

    <!-- Tab Panels -->
    <div class="<?php echo $vertical ? 'flex-1' : 'mt-4'; ?>">
        <?php foreach ($tabs as $tab):
            $tab_id = $tab['id'] ?? '';
            $is_active = $tab_id === $active;
        ?>
            <div id="<?php echo esc_attr($tabs_id . '-panel-' . $tab_id); ?>"
                 role="tabpanel"
                 aria-labelledby="<?php echo esc_attr($tabs_id . '-tab-' . $tab_id); ?>"
                 class="<?php echo $is_active ? '' : 'hidden'; ?>">
                <?php
                if (!empty($tab['content'])) {
                    echo wp_kses_post($tab['content']);
                } elseif (!empty($tab['callback']) && is_callable($tab['callback'])) {
                    call_user_func($tab['callback'], $tab);
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
window.flavorTabs = window.flavorTabs || {
    switch: function(containerId, tabId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Update tabs
        container.querySelectorAll('[role="tab"]').forEach(tab => {
            const isActive = tab.id === containerId + '-tab-' + tabId;
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');

            // Update classes based on style
            const activeClass = tab.dataset.activeClass || '';
            const inactiveClass = tab.dataset.inactiveClass || '';

            if (isActive) {
                tab.classList.remove(...inactiveClass.split(' ').filter(Boolean));
                tab.classList.add(...activeClass.split(' ').filter(Boolean));
            } else {
                tab.classList.remove(...activeClass.split(' ').filter(Boolean));
                tab.classList.add(...inactiveClass.split(' ').filter(Boolean));
            }
        });

        // Update panels
        container.querySelectorAll('[role="tabpanel"]').forEach(panel => {
            const isActive = panel.id === containerId + '-panel-' + tabId;
            panel.classList.toggle('hidden', !isActive);
        });

        // Dispatch event
        container.dispatchEvent(new CustomEvent('tab-change', {
            detail: { tabId, containerId }
        }));
    }
};
</script>
