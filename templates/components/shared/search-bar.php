<?php
/**
 * Componente: Search Bar
 *
 * Barra de búsqueda con filtros opcionales.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $placeholder  Placeholder del input
 * @param string $value        Valor actual de búsqueda
 * @param string $color        Color del tema
 * @param array  $filters      Filtros: [['name' => 'categoria', 'label' => 'Categoría', 'options' => [...]]]
 * @param bool   $live_search  Búsqueda en tiempo real
 * @param int    $debounce     Debounce en ms para live search
 * @param string $action       URL de acción del form
 * @param string $method       Método: GET, POST
 * @param array  $buttons      Botones adicionales: [['label' => 'Filtros', 'icon' => '🔍', 'action' => 'openFilters()']]
 * @param string $size         Tamaño: 'sm', 'md', 'lg'
 * @param string $id           ID único
 */

if (!defined('ABSPATH')) {
    exit;
}

$placeholder = $placeholder ?? __('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN);
$value = $value ?? '';
$color = $color ?? 'blue';
$filters = $filters ?? [];
$live_search = $live_search ?? false;
$debounce = $debounce ?? 300;
$action = $action ?? '';
$method = $method ?? 'GET';
$buttons = $buttons ?? [];
$size = $size ?? 'md';
$search_id = $id ?? 'search-' . wp_rand(1000, 9999);

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Clases de tamaño
$size_config = [
    'sm' => [
        'input' => 'py-2 px-4 text-sm',
        'icon'  => 'text-base pl-10',
        'btn'   => 'py-2 px-3 text-sm',
    ],
    'md' => [
        'input' => 'py-3 px-4',
        'icon'  => 'text-lg pl-12',
        'btn'   => 'py-3 px-4',
    ],
    'lg' => [
        'input' => 'py-4 px-5 text-lg',
        'icon'  => 'text-xl pl-14',
        'btn'   => 'py-4 px-5',
    ],
];
$sizes = $size_config[$size] ?? $size_config['md'];
?>

<div id="<?php echo esc_attr($search_id); ?>" class="flavor-search-bar">
    <form action="<?php echo esc_url($action); ?>"
          method="<?php echo esc_attr($method); ?>"
          class="flex flex-col md:flex-row gap-3">

        <!-- Search Input -->
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 pointer-events-none">
                🔍
            </span>
            <input type="search"
                   name="s"
                   id="<?php echo esc_attr($search_id); ?>-input"
                   value="<?php echo esc_attr($value); ?>"
                   placeholder="<?php echo esc_attr($placeholder); ?>"
                   class="w-full <?php echo esc_attr($sizes['input']); ?> <?php echo esc_attr($sizes['icon']); ?> pr-4 rounded-xl border border-gray-200 focus:border-<?php echo esc_attr($color); ?>-500 focus:ring-2 focus:ring-<?php echo esc_attr($color); ?>-500/20 outline-none transition-all"
                   <?php if ($live_search): ?>
                   data-live-search="true"
                   data-debounce="<?php echo esc_attr($debounce); ?>"
                   <?php endif; ?>>

            <?php if ($value): ?>
                <button type="button"
                        onclick="document.getElementById('<?php echo esc_js($search_id); ?>-input').value = ''; this.closest('form').submit();"
                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600">
                    ✕
                </button>
            <?php endif; ?>
        </div>

        <!-- Inline Filters -->
        <?php if (!empty($filters)): ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($filters as $filter):
                    $filter_name = $filter['name'] ?? '';
                    $filter_label = $filter['label'] ?? '';
                    $filter_options = $filter['options'] ?? [];
                    $filter_value = $filter['value'] ?? '';
                    $filter_type = $filter['type'] ?? 'select';
                ?>
                    <?php if ($filter_type === 'select'): ?>
                        <select name="<?php echo esc_attr($filter_name); ?>"
                                class="<?php echo esc_attr($sizes['btn']); ?> rounded-xl border border-gray-200 bg-white focus:border-<?php echo esc_attr($color); ?>-500 focus:ring-2 focus:ring-<?php echo esc_attr($color); ?>-500/20 outline-none transition-all appearance-none pr-10 cursor-pointer"
                                <?php if ($live_search): ?>onchange="this.closest('form').submit()"<?php endif; ?>>
                            <option value=""><?php echo esc_html($filter_label); ?></option>
                            <?php foreach ($filter_options as $option): ?>
                                <option value="<?php echo esc_attr($option['value'] ?? ''); ?>"
                                        <?php selected($filter_value, $option['value'] ?? ''); ?>>
                                    <?php echo esc_html($option['label'] ?? $option['value'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Buttons -->
        <div class="flex items-center gap-2">
            <?php foreach ($buttons as $btn):
                $btn_label = $btn['label'] ?? '';
                $btn_icon = $btn['icon'] ?? '';
                $btn_action = $btn['action'] ?? '';
                $btn_url = $btn['url'] ?? '';
                $btn_primary = $btn['primary'] ?? false;

                if ($btn_primary) {
                    $btn_class = "{$sizes['btn']} rounded-xl font-medium text-white {$color_classes['bg_solid']} hover:opacity-90 transition-all";
                } else {
                    $btn_class = "{$sizes['btn']} rounded-xl font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors";
                }
            ?>
                <?php if ($btn_action): ?>
                    <button type="button"
                            onclick="<?php echo esc_attr($btn_action); ?>"
                            class="<?php echo esc_attr($btn_class); ?> flex items-center gap-2">
                        <?php if ($btn_icon): ?><span><?php echo esc_html($btn_icon); ?></span><?php endif; ?>
                        <span class="hidden md:inline"><?php echo esc_html($btn_label); ?></span>
                    </button>
                <?php elseif ($btn_url): ?>
                    <a href="<?php echo esc_url($btn_url); ?>"
                       class="<?php echo esc_attr($btn_class); ?> flex items-center gap-2">
                        <?php if ($btn_icon): ?><span><?php echo esc_html($btn_icon); ?></span><?php endif; ?>
                        <span class="hidden md:inline"><?php echo esc_html($btn_label); ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Submit button -->
            <button type="submit"
                    class="<?php echo esc_attr($sizes['btn']); ?> rounded-xl font-medium text-white <?php echo esc_attr($color_classes['bg_solid']); ?> hover:opacity-90 transition-all flex items-center gap-2">
                🔍
                <span class="hidden md:inline"><?php echo esc_html__('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </button>
        </div>
    </form>
</div>

<?php if ($live_search): ?>
<script>
(function() {
    const searchInput = document.getElementById('<?php echo esc_js($search_id); ?>-input');
    if (!searchInput || !searchInput.dataset.liveSearch) return;

    let debounceTimer;
    const debounceMs = parseInt(searchInput.dataset.debounce) || 300;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            // Dispatch custom event for live search
            const event = new CustomEvent('flavor-search', {
                detail: {
                    query: this.value,
                    searchId: '<?php echo esc_js($search_id); ?>'
                }
            });
            document.dispatchEvent(event);

            // Optionally auto-submit
            // this.closest('form').submit();
        }, debounceMs);
    });
})();
</script>
<?php endif; ?>
