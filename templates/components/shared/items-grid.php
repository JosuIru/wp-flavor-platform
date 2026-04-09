<?php
/**
 * Componente: Items Grid
 *
 * Grid de items/cards reutilizable con soporte para templates personalizados,
 * card genérica configurable y estado vacío integrado.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array    $items          Array de items a mostrar
 * @param int      $columns        Número de columnas (1, 2, 3 o 4)
 * @param array    $card_config    Configuración para la card genérica (preferido)
 * @param string   $card_template  Nombre del template de card a usar (sin .php) - fallback
 * @param callable $card_callback  Callback para renderizar cada card (alternativa a card_template)
 * @param string   $layout         Layout: 'grid' o 'list'
 * @param string   $gap            Espacio entre items: 'sm', 'md', 'lg'
 * @param string   $data_attr      Atributo data-* para filtrado
 * @param array    $empty_state    Config del empty state: icon, title, text, cta_text, cta_action, color
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar funciones helper si no están cargadas
if (!function_exists('flavor_render_component')) {
    require_once __DIR__ . '/_functions.php';
}

// Valores por defecto
$items = $items ?? [];
$columns = $columns ?? 3;
$card_config = $card_config ?? null;
$card_template = $card_template ?? '';
$card_callback = $card_callback ?? null;
$layout = $layout ?? 'grid';
$gap = $gap ?? 'md';
$data_attr = $data_attr ?? '';
$empty_state = $empty_state ?? [];

// Clases de gap
$gap_classes = [
    'sm' => 'gap-3',
    'md' => 'gap-6',
    'lg' => 'gap-8',
];
$gap_class = $gap_classes[$gap] ?? $gap_classes['md'];

// Clases de columnas
$columns_classes = [
    1 => 'grid-cols-1',
    2 => 'grid-cols-1 md:grid-cols-2',
    3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
];
$columns_class = $columns_classes[$columns] ?? $columns_classes[3];

// Estado vacío por defecto
$empty_defaults = [
    'icon'       => '📭',
    'title'      => __('No hay elementos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'text'       => '',
    'cta_text'   => '',
    'cta_action' => '',
    'cta_url'    => '',
    'color'      => 'blue',
];
$empty_config = wp_parse_args($empty_state, $empty_defaults);

$grid_id = flavor_unique_id('items-grid');
?>

<?php if (empty($items)): ?>
    <!-- Estado vacío -->
    <?php flavor_render_component('empty-state', $empty_config); ?>

<?php else: ?>
    <!-- Grid de items -->
    <?php if ($layout === 'list'): ?>
        <div id="<?php echo esc_attr($grid_id); ?>" class="space-y-4">
    <?php else: ?>
        <div id="<?php echo esc_attr($grid_id); ?>" class="grid <?php echo esc_attr($columns_class); ?> <?php echo esc_attr($gap_class); ?>">
    <?php endif; ?>

        <?php foreach ($items as $index => $item):
            // Atributo data para filtrado
            $data_value = '';
            if ($data_attr && isset($item[$data_attr])) {
                $data_value = $item[$data_attr];
            } elseif ($data_attr && isset($item['estado'])) {
                $data_value = $item['estado'];
            } elseif ($data_attr && isset($item['categoria'])) {
                $data_value = $item['categoria'];
            }
        ?>

            <?php if ($data_attr && $data_value): ?>
            <div data-<?php echo esc_attr($data_attr); ?>="<?php echo esc_attr($data_value); ?>">
            <?php endif; ?>

            <?php
            // Prioridad 1: Card genérica con configuración dinámica (PREFERIDO)
            if (!empty($card_config)) {
                flavor_render_component('generic-card', [
                    'item'   => $item,
                    'config' => $card_config,
                ]);
            }
            // Prioridad 2: Callback personalizado
            elseif (is_callable($card_callback)) {
                call_user_func($card_callback, $item, $index);
            }
            // Prioridad 3: Template de card específico (legacy)
            elseif ($card_template) {
                // Buscar primero en shared, luego en la carpeta del módulo
                $template_paths = [
                    FLAVOR_PLUGIN_PATH . "templates/components/shared/{$card_template}.php",
                    FLAVOR_PLUGIN_PATH . "templates/components/{$card_template}.php",
                ];

                $template_found = false;
                foreach ($template_paths as $template_path) {
                    if (file_exists($template_path)) {
                        // Pasar el item como variable
                        $card_item = $item;
                        $card_index = $index;
                        include $template_path;
                        $template_found = true;
                        break;
                    }
                }

                if (!$template_found && defined('WP_DEBUG') && WP_DEBUG) {
                    echo "<!-- Template no encontrado: {$card_template} -->";
                }
            }
            // Fallback: Card genérica con configuración mínima
            else {
                flavor_render_component('generic-card', [
                    'item'   => $item,
                    'config' => [
                        'fields' => [
                            'id'       => 'id',
                            'title'    => 'titulo',
                            'subtitle' => 'descripcion',
                            'image'    => 'imagen',
                            'url'      => 'url',
                        ],
                    ],
                ]);
            }
            ?>

            <?php if ($data_attr && $data_value): ?>
            </div>
            <?php endif; ?>

        <?php endforeach; ?>

    </div>
<?php endif; ?>
