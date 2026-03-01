<?php
/**
 * Card Genérica Reutilizable
 *
 * Componente de card adaptable a cualquier módulo mediante configuración.
 * Elimina la necesidad de crear archivos de card separados para cada módulo.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * Uso:
 * flavor_render_component('generic-card', [
 *     'item'   => $item,
 *     'config' => [
 *         'color' => 'lime',
 *         'fields' => [
 *             'id'          => 'id',
 *             'title'       => 'nombre',
 *             'subtitle'    => 'descripcion',
 *             'image'       => 'imagen',
 *             'url'         => 'url',
 *         ],
 *         'badge' => [
 *             'field' => 'estado',
 *             'colors' => [
 *                 'activo' => 'green',
 *                 'pendiente' => 'yellow',
 *             ],
 *         ],
 *         'meta' => [
 *             ['icon' => '👥', 'field' => 'miembros', 'suffix' => ' miembros'],
 *             ['icon' => '📍', 'field' => 'ubicacion'],
 *         ],
 *         'progress' => [
 *             'field' => 'porcentaje',
 *             'label' => 'Completado',
 *         ],
 *         'actions' => [
 *             ['label' => 'Ver', 'icon' => '👁️', 'url_field' => 'url', 'primary' => true],
 *             ['label' => 'Unirse', 'icon' => '➕', 'action' => 'flavorModule.unirse({id})'],
 *         ],
 *         'data_attrs' => ['estado', 'categoria'],
 *     ],
 * ]);
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer parámetros
$item = $item ?? $card_item ?? [];
$config = $config ?? $card_config ?? [];

if (empty($item) || empty($config)) {
    return;
}

// Configuración por defecto
$default_config = [
    'color'       => 'blue',
    'icon'        => '📄',
    'fields'      => [
        'id'       => 'id',
        'title'    => 'titulo',
        'subtitle' => 'descripcion',
        'image'    => 'imagen',
        'url'      => 'url',
    ],
    'badge'       => null,
    'secondary_badge' => null,
    'meta'        => [],
    'progress'    => null,
    'actions'     => [],
    'data_attrs'  => [],
    'show_image'  => true,
    'image_aspect' => 'aspect-video', // aspect-square, aspect-video, aspect-[4/3]
    'layout'      => 'vertical', // vertical, horizontal
];

$config = array_merge($default_config, $config);
$fields = $config['fields'];

// Función helper para obtener valor de item
$get_value = function($field, $default = '') use ($item) {
    if (is_array($field)) {
        // Permite campos anidados: ['nested', 'key']
        $value = $item;
        foreach ($field as $key) {
            $value = $value[$key] ?? null;
            if ($value === null) return $default;
        }
        return $value;
    }
    return $item[$field] ?? $default;
};

// Extraer valores principales
$item_id = $get_value($fields['id'] ?? 'id', 0);
$title = $get_value($fields['title'] ?? 'titulo', __('Sin título', 'flavor-chat-ia'));
$subtitle = $get_value($fields['subtitle'] ?? 'descripcion', '');
$image = $get_value($fields['image'] ?? 'imagen', '');
$url = $get_value($fields['url'] ?? 'url', '#');

// Obtener clases de color
$color_classes = flavor_get_color_classes($config['color']);

// Construir data attributes
$data_attrs_html = '';
foreach ($config['data_attrs'] as $attr) {
    $attr_value = $get_value($attr, '');
    if (is_string($attr)) {
        $data_attrs_html .= ' data-' . esc_attr($attr) . '="' . esc_attr($attr_value) . '"';
    }
}

// Procesar badge principal
$badge_html = '';
if (!empty($config['badge'])) {
    $badge_config = $config['badge'];
    $badge_value = $get_value($badge_config['field'] ?? '', '');

    if ($badge_value) {
        $badge_color = 'gray';
        if (isset($badge_config['colors'][$badge_value])) {
            $badge_color = $badge_config['colors'][$badge_value];
        }
        $badge_classes = flavor_get_color_classes($badge_color);
        $badge_label = $badge_config['labels'][$badge_value] ?? ucfirst($badge_value);
        $badge_icon = $badge_config['icons'][$badge_value] ?? '';

        $badge_html = sprintf(
            '<span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-medium %s %s">%s%s</span>',
            esc_attr($badge_classes['bg']),
            esc_attr($badge_classes['text']),
            $badge_icon ? esc_html($badge_icon) . ' ' : '',
            esc_html($badge_label)
        );
    }
}

// Procesar badge secundario
$secondary_badge_html = '';
if (!empty($config['secondary_badge'])) {
    $sbadge = $config['secondary_badge'];
    $sbadge_value = $get_value($sbadge['field'] ?? '', '');

    if ($sbadge_value) {
        $sbadge_color = $sbadge['color'] ?? 'gray';
        $sbadge_classes = flavor_get_color_classes($sbadge_color);

        $secondary_badge_html = sprintf(
            '<span class="px-2 py-0.5 rounded text-xs font-medium %s %s">%s</span>',
            esc_attr($sbadge_classes['bg']),
            esc_attr($sbadge_classes['text']),
            esc_html($sbadge_value)
        );
    }
}

// Procesar metadatos
$meta_items = [];
foreach ($config['meta'] as $meta) {
    $meta_value = $get_value($meta['field'] ?? '', '');
    if ($meta_value !== '' && $meta_value !== null) {
        $prefix = $meta['prefix'] ?? '';
        $suffix = $meta['suffix'] ?? '';
        $icon = $meta['icon'] ?? '';

        $meta_items[] = [
            'icon'  => $icon,
            'value' => $prefix . $meta_value . $suffix,
        ];
    }
}

// Procesar barra de progreso
$progress_html = '';
if (!empty($config['progress'])) {
    $prog = $config['progress'];
    $prog_value = (float) $get_value($prog['field'] ?? '', 0);
    $prog_label = $prog['label'] ?? '';
    $prog_max = $prog['max'] ?? 100;
    $prog_percentage = min(100, ($prog_value / $prog_max) * 100);

    $progress_html = sprintf(
        '<div class="mt-3">
            %s
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full %s rounded-full transition-all" style="width: %s%%"></div>
            </div>
        </div>',
        $prog_label ? '<div class="flex justify-between text-xs text-gray-500 mb-1"><span>' . esc_html($prog_label) . '</span><span>' . esc_html($prog_value) . '%</span></div>' : '',
        esc_attr($color_classes['bg_solid']),
        esc_attr($prog_percentage)
    );
}

// Procesar acciones
$actions_html = '';
if (!empty($config['actions'])) {
    $actions_parts = [];
    foreach ($config['actions'] as $action) {
        $action_label = $action['label'] ?? '';
        $action_icon = $action['icon'] ?? '';
        $is_primary = $action['primary'] ?? false;

        // URL o acción JS
        if (!empty($action['url_field'])) {
            $action_url = $get_value($action['url_field'], '#');
            $action_onclick = '';
        } elseif (!empty($action['url'])) {
            $action_url = $action['url'];
            $action_onclick = '';
        } elseif (!empty($action['action'])) {
            $action_url = 'javascript:void(0)';
            // Reemplazar {field} con valores del item
            $action_js = preg_replace_callback('/\{(\w+)\}/', function($matches) use ($get_value) {
                return $get_value($matches[1], '');
            }, $action['action']);
            $action_onclick = esc_attr($action_js);
        } else {
            $action_url = '#';
            $action_onclick = '';
        }

        if ($is_primary) {
            $btn_classes = "flex-1 py-2 px-4 rounded-xl text-sm font-medium text-white {$color_classes['bg_solid']} hover:opacity-90 transition-all text-center";
        } else {
            $btn_classes = "flex-1 py-2 px-4 rounded-xl text-sm font-medium {$color_classes['bg']} {$color_classes['text']} {$color_classes['hover']} transition-all text-center";
        }

        $actions_parts[] = sprintf(
            '<a href="%s" %s class="%s">%s%s</a>',
            esc_url($action_url),
            $action_onclick ? 'onclick="' . $action_onclick . '"' : '',
            esc_attr($btn_classes),
            $action_icon ? esc_html($action_icon) . ' ' : '',
            esc_html($action_label)
        );
    }

    if (!empty($actions_parts)) {
        $actions_html = '<div class="flex gap-2 mt-4 pt-4 border-t border-gray-100">' . implode('', $actions_parts) . '</div>';
    }
}

?>
<article
    class="group bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100"
    data-id="<?php echo esc_attr($item_id); ?>"
    <?php echo $data_attrs_html; ?>
>
    <?php if ($config['show_image']): ?>
    <div class="relative <?php echo esc_attr($config['image_aspect']); ?> bg-gray-100 overflow-hidden">
        <?php if ($image): ?>
            <img
                src="<?php echo esc_url($image); ?>"
                alt="<?php echo esc_attr($title); ?>"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                loading="lazy"
            >
        <?php else: ?>
            <div class="w-full h-full flex items-center justify-center <?php echo esc_attr($color_classes['bg']); ?>">
                <span class="text-4xl"><?php echo esc_html($config['icon']); ?></span>
            </div>
        <?php endif; ?>

        <?php echo $badge_html; ?>
    </div>
    <?php endif; ?>

    <div class="p-5">
        <?php if ($secondary_badge_html): ?>
            <div class="mb-2">
                <?php echo $secondary_badge_html; ?>
            </div>
        <?php endif; ?>

        <h3 class="font-bold text-gray-900 text-lg mb-1 line-clamp-2 group-hover:<?php echo esc_attr($color_classes['text']); ?> transition-colors">
            <?php if ($url && $url !== '#'): ?>
                <a href="<?php echo esc_url($url); ?>" class="hover:underline">
                    <?php echo esc_html($title); ?>
                </a>
            <?php else: ?>
                <?php echo esc_html($title); ?>
            <?php endif; ?>
        </h3>

        <?php if ($subtitle): ?>
            <p class="text-gray-600 text-sm line-clamp-2 mb-3">
                <?php echo esc_html($subtitle); ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($meta_items)): ?>
            <div class="flex flex-wrap gap-3 text-sm text-gray-500">
                <?php foreach ($meta_items as $meta_item): ?>
                    <span class="flex items-center gap-1">
                        <?php if ($meta_item['icon']): ?>
                            <span><?php echo esc_html($meta_item['icon']); ?></span>
                        <?php endif; ?>
                        <?php echo esc_html($meta_item['value']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php echo $progress_html; ?>

        <?php echo $actions_html; ?>
    </div>
</article>
