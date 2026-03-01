<?php
/**
 * Componente: Legend Box
 *
 * Leyenda para mapas, gráficos o cualquier contenido con códigos de color.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $items      Array de items: [['label' => '', 'color' => '', 'icon' => '', 'value' => '', 'description' => '']]
 * @param string $title      Título de la leyenda
 * @param string $layout     Layout: vertical, horizontal, grid
 * @param string $position   Posición: static, top-left, top-right, bottom-left, bottom-right
 * @param bool   $collapsible Permitir colapsar
 * @param bool   $interactive Items clickeables (para filtrar mapa/gráfico)
 * @param string $marker_style Estilo del marcador: circle, square, line, dot
 * @param string $size       Tamaño: sm, md, lg
 */

if (!defined('ABSPATH')) {
    exit;
}

$items = $items ?? [];
$title = $title ?? __('Leyenda', 'flavor-chat-ia');
$layout = $layout ?? 'vertical';
$position = $position ?? 'static';
$collapsible = $collapsible ?? false;
$interactive = $interactive ?? false;
$marker_style = $marker_style ?? 'circle';
$size = $size ?? 'md';

// Clases de posición
$position_classes = [
    'static'       => '',
    'top-left'     => 'absolute top-3 left-3 z-10',
    'top-right'    => 'absolute top-3 right-3 z-10',
    'bottom-left'  => 'absolute bottom-3 left-3 z-10',
    'bottom-right' => 'absolute bottom-3 right-3 z-10',
];
$pos_class = $position_classes[$position] ?? '';

// Clases de layout
$layout_classes = [
    'vertical'   => 'flex flex-col gap-2',
    'horizontal' => 'flex flex-wrap gap-3',
    'grid'       => 'grid grid-cols-2 gap-2',
];
$layout_class = $layout_classes[$layout] ?? $layout_classes['vertical'];

// Clases de tamaño
$size_config = [
    'sm' => ['text' => 'text-xs', 'marker' => 'w-2 h-2', 'padding' => 'p-2'],
    'md' => ['text' => 'text-sm', 'marker' => 'w-3 h-3', 'padding' => 'p-3'],
    'lg' => ['text' => 'text-base', 'marker' => 'w-4 h-4', 'padding' => 'p-4'],
];
$sz = $size_config[$size] ?? $size_config['md'];

// Estilos de marcador
$marker_render = [
    'circle' => 'rounded-full',
    'square' => 'rounded-sm',
    'line'   => 'h-0.5 w-4 rounded',
    'dot'    => 'rounded-full w-2 h-2',
];

$legend_id = 'flavor-legend-' . wp_rand(1000, 9999);
?>

<div class="flavor-legend-box <?php echo esc_attr($pos_class); ?> bg-white rounded-lg shadow-md <?php echo esc_attr($sz['padding']); ?> max-w-xs"
     id="<?php echo esc_attr($legend_id); ?>">

    <!-- Header -->
    <?php if ($title || $collapsible): ?>
        <div class="flex items-center justify-between gap-2 <?php echo !empty($items) ? 'mb-2 pb-2 border-b border-gray-100' : ''; ?>">
            <?php if ($title): ?>
                <span class="font-medium text-gray-700 <?php echo esc_attr($sz['text']); ?>">
                    <?php echo esc_html($title); ?>
                </span>
            <?php endif; ?>

            <?php if ($collapsible): ?>
                <button type="button" class="flavor-legend-toggle p-1 hover:bg-gray-100 rounded transition-colors" aria-expanded="true">
                    <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Items -->
    <div class="flavor-legend-items <?php echo esc_attr($layout_class); ?>">
        <?php foreach ($items as $index => $item): ?>
            <?php
            $item_label = $item['label'] ?? '';
            $item_color = $item['color'] ?? '#6B7280';
            $item_icon = $item['icon'] ?? '';
            $item_value = $item['value'] ?? '';
            $item_desc = $item['description'] ?? '';
            $item_id = $item['id'] ?? sanitize_title($item_label);
            $item_active = $item['active'] ?? true;

            // Si el color es un nombre de Tailwind, usar clase; sino usar style
            $is_tailwind = preg_match('/^(red|blue|green|yellow|purple|orange|gray|pink|indigo|cyan|teal|emerald|amber|lime|sky|violet|fuchsia|rose)-\d{3}$/', $item_color);
            ?>
            <div class="flavor-legend-item flex items-center gap-2 <?php echo esc_attr($sz['text']); ?> <?php echo $interactive ? 'cursor-pointer hover:bg-gray-50 p-1 -m-1 rounded transition-colors' : ''; ?> <?php echo !$item_active ? 'opacity-40' : ''; ?>"
                 data-id="<?php echo esc_attr($item_id); ?>"
                 <?php if ($interactive): ?>role="button" tabindex="0"<?php endif; ?>>

                <!-- Marcador de color -->
                <?php if ($item_icon): ?>
                    <span class="flex-shrink-0"><?php echo esc_html($item_icon); ?></span>
                <?php elseif ($marker_style === 'line'): ?>
                    <span class="flex-shrink-0 h-0.5 w-4 rounded"
                          style="background-color: <?php echo esc_attr($item_color); ?>;"></span>
                <?php else: ?>
                    <span class="flex-shrink-0 <?php echo esc_attr($sz['marker']); ?> <?php echo esc_attr($marker_render[$marker_style] ?? $marker_render['circle']); ?>"
                          style="background-color: <?php echo esc_attr($item_color); ?>;"></span>
                <?php endif; ?>

                <!-- Label y valor -->
                <div class="flex-1 min-w-0 flex items-center justify-between gap-2">
                    <span class="text-gray-700 truncate"><?php echo esc_html($item_label); ?></span>
                    <?php if ($item_value !== ''): ?>
                        <span class="text-gray-500 font-medium"><?php echo esc_html($item_value); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Indicador de toggle (si es interactivo) -->
                <?php if ($interactive): ?>
                    <svg class="flex-shrink-0 w-4 h-4 text-green-500 flavor-legend-check <?php echo !$item_active ? 'hidden' : ''; ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                <?php endif; ?>
            </div>

            <?php if ($item_desc): ?>
                <p class="text-xs text-gray-400 ml-5 -mt-1"><?php echo esc_html($item_desc); ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Estado vacío -->
    <?php if (empty($items)): ?>
        <p class="<?php echo esc_attr($sz['text']); ?> text-gray-400 text-center py-2">
            <?php esc_html_e('Sin elementos', 'flavor-chat-ia'); ?>
        </p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const legend = document.getElementById('<?php echo esc_js($legend_id); ?>');
    if (!legend) return;

    // Toggle colapsar
    const toggleBtn = legend.querySelector('.flavor-legend-toggle');
    const items = legend.querySelector('.flavor-legend-items');

    if (toggleBtn && items) {
        toggleBtn.addEventListener('click', function() {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
            items.classList.toggle('hidden', expanded);
            this.querySelector('svg').style.transform = expanded ? 'rotate(-90deg)' : '';
        });
    }

    // Items interactivos
    <?php if ($interactive): ?>
    legend.querySelectorAll('.flavor-legend-item').forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            const isActive = !this.classList.contains('opacity-40');

            // Toggle visual
            this.classList.toggle('opacity-40', isActive);
            const check = this.querySelector('.flavor-legend-check');
            if (check) check.classList.toggle('hidden', isActive);

            // Disparar evento custom
            legend.dispatchEvent(new CustomEvent('legend-toggle', {
                detail: { id: id, active: !isActive },
                bubbles: true
            }));
        });

        // Accesibilidad: activar con Enter/Space
        item.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    <?php endif; ?>
});
</script>
