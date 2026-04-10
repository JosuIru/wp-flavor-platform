<?php
/**
 * Componente: Filter Pills
 *
 * Botones de filtro tipo pill reutilizables con soporte para
 * filtrado por atributos data-*.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $filters       Array de filtros, cada uno con: id, label, icon (opcional), active (bool)
 * @param string $color         Color activo (red, green, blue, etc.)
 * @param string $data_attr     Nombre del atributo data-* para filtrar (ej: 'estado', 'categoria')
 * @param string $target        Selector CSS del contenedor de items a filtrar
 * @param string $filter_class  Clase JS para manejar el filtrado (opcional)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar funciones helper si no están cargadas
if (!function_exists('flavor_get_color_classes')) {
    require_once __DIR__ . '/_functions.php';
}

// Valores por defecto
$filters = $filters ?? [];
$color = $color ?? 'blue';
$data_attr = $data_attr ?? 'filter';
$target = $target ?? '';
$filter_class = $filter_class ?? 'flavor-filter-pill';

// No renderizar si no hay filtros
if (empty($filters)) {
    return;
}

$color_classes = flavor_get_color_classes($color);
$container_id = flavor_unique_id('filter-pills');
?>

<div id="<?php echo esc_attr($container_id); ?>"
     class="flex flex-wrap gap-2 mb-6"
     <?php if ($target): ?>data-target="<?php echo esc_attr($target); ?>"<?php endif; ?>>

    <?php foreach ($filters as $index => $filter):
        $filter_id = $filter['id'] ?? $filter['slug'] ?? sanitize_key($filter['label'] ?? '');
        $filter_label = $filter['label'] ?? '';
        $filter_icon = $filter['icon'] ?? '';
        $is_active = $filter['active'] ?? ($index === 0);

        // Clases según estado activo
        $active_classes = $is_active
            ? "{$color_classes['bg']} {$color_classes['text']} filter-active"
            : 'bg-gray-100 text-gray-600';
    ?>

    <button type="button"
            class="<?php echo esc_attr($filter_class); ?> px-4 py-2 rounded-full font-medium <?php echo esc_attr($active_classes); ?> <?php echo esc_attr($color_classes['hover']); ?> transition-colors"
            data-<?php echo esc_attr($data_attr); ?>="<?php echo esc_attr($filter_id); ?>"
            <?php if ($is_active): ?>aria-pressed="true"<?php endif; ?>>
        <?php if ($filter_icon): ?>
            <span class="mr-1"><?php echo esc_html($filter_icon); ?></span>
        <?php endif; ?>
        <?php echo esc_html($filter_label); ?>
        <?php if (isset($filter['count'])): ?>
            <span class="text-xs opacity-70 ml-1">(<?php echo esc_html($filter['count']); ?>)</span>
        <?php endif; ?>
    </button>

    <?php endforeach; ?>
</div>

<script>
(function() {
    const container = document.getElementById('<?php echo esc_js($container_id); ?>');
    if (!container) return;

    const pills = container.querySelectorAll('.<?php echo esc_js($filter_class); ?>');
    const targetSelector = container.dataset.target;
    const dataAttr = '<?php echo esc_js($data_attr); ?>';
    const activeColorBg = '<?php echo esc_js($color_classes['bg']); ?>';
    const activeColorText = '<?php echo esc_js($color_classes['text']); ?>';

    pills.forEach(pill => {
        pill.addEventListener('click', function() {
            // Actualizar estado visual
            pills.forEach(p => {
                p.classList.remove(activeColorBg, activeColorText, 'filter-active');
                p.classList.add('bg-gray-100', 'text-gray-600');
                p.removeAttribute('aria-pressed');
            });

            this.classList.remove('bg-gray-100', 'text-gray-600');
            this.classList.add(activeColorBg, activeColorText, 'filter-active');
            this.setAttribute('aria-pressed', 'true');

            // Filtrar items si hay target
            if (targetSelector) {
                const filterValue = this.dataset[dataAttr];
                const items = document.querySelectorAll(targetSelector + ' [data-' + dataAttr + ']');

                items.forEach(item => {
                    if (filterValue === 'todos' || filterValue === 'all' || item.dataset[dataAttr] === filterValue) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }

            // Emitir evento personalizado
            container.dispatchEvent(new CustomEvent('filter:change', {
                detail: { filter: this.dataset[dataAttr] },
                bubbles: true
            }));
        });
    });
})();
</script>
