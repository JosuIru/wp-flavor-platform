<?php
/**
 * Frontend: Archive Comunidades
 *
 * Versión refactorizada usando componentes shared y Archive Renderer.
 *
 * @package FlavorChatIA
 * @since 5.0.0 Refactorizado con componentes reutilizables
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar el Archive Renderer
if (!class_exists('Flavor_Archive_Renderer')) {
    require_once FLAVOR_PLUGIN_PATH . 'includes/class-archive-renderer.php';
}

// Variables esperadas
$comunidades = $comunidades ?? [];
$estadisticas = $estadisticas ?? [];
$comunidades_destacadas = $comunidades_destacadas ?? [];
$current_page = $current_page ?? 1;
$per_page = $per_page ?? 12;
$total_comunidades = $total_comunidades ?? count($comunidades);

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        [
            'value' => $estadisticas['total_comunidades'] ?? 0,
            'label' => __('Comunidades', 'flavor-chat-ia'),
            'icon'  => '🏘️',
            'color' => 'pink',
        ],
        [
            'value' => $estadisticas['total_miembros'] ?? 0,
            'label' => __('Miembros', 'flavor-chat-ia'),
            'icon'  => '👥',
            'color' => 'pink',
        ],
        [
            'value' => $estadisticas['eventos_mes'] ?? 0,
            'label' => __('Eventos este mes', 'flavor-chat-ia'),
            'icon'  => '📅',
            'color' => 'purple',
        ],
        [
            'value' => $estadisticas['publicaciones_semana'] ?? 0,
            'label' => __('Publicaciones/semana', 'flavor-chat-ia'),
            'icon'  => '💬',
            'color' => 'purple',
        ],
    ];
}

// Filtros por tipo de comunidad
$filters = [
    ['id' => 'todos', 'label' => __('Todas', 'flavor-chat-ia'), 'active' => true],
    ['id' => 'vecinal', 'label' => __('Vecinal', 'flavor-chat-ia'), 'icon' => '🏠'],
    ['id' => 'interes', 'label' => __('Interés común', 'flavor-chat-ia'), 'icon' => '💡'],
    ['id' => 'deportiva', 'label' => __('Deportiva', 'flavor-chat-ia'), 'icon' => '⚽'],
    ['id' => 'cultural', 'label' => __('Cultural', 'flavor-chat-ia'), 'icon' => '🎭'],
    ['id' => 'solidaria', 'label' => __('Solidaria', 'flavor-chat-ia'), 'icon' => '🤝'],
];

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'comunidades',
    'title'        => __('Comunidades', 'flavor-chat-ia'),
    'subtitle'     => __('Encuentra tu comunidad y conecta con tus vecinos', 'flavor-chat-ia'),
    'icon'         => '🏘️',
    'color'        => 'pink',
    'items'        => $comunidades,
    'total'        => $total_comunidades,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 3,
    'layout'       => 'grid',
    'filters'      => $filters,
    'filter_data_attr' => 'tipo',
    'cta_text'     => __('Crear comunidad', 'flavor-chat-ia'),
    'cta_icon'     => '➕',
    'cta_action'   => 'flavorComunidades.crearComunidad()',
    'card_template' => 'comunidades/card',
    'empty_state'  => [
        'icon'       => '🏘️',
        'title'      => __('No hay comunidades todavía', 'flavor-chat-ia'),
        'text'       => __('Sé el primero en crear una comunidad en tu zona', 'flavor-chat-ia'),
        'cta_text'   => __('Crear comunidad', 'flavor-chat-ia'),
        'cta_action' => 'flavorComunidades.crearComunidad()',
    ],
]);

// Comunidades destacadas (sección adicional)
if (!empty($comunidades_destacadas)):
?>
<div class="mt-12">
    <h2 class="text-2xl font-bold text-gray-800 mb-6"><?php echo esc_html__('⭐ Comunidades destacadas', 'flavor-chat-ia'); ?></h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($comunidades_destacadas as $destacada): ?>
        <div class="bg-gradient-to-r from-rose-50 to-pink-50 rounded-2xl p-6 border border-rose-100">
            <div class="flex gap-4">
                <div class="w-16 h-16 bg-rose-200 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">
                    <?php echo esc_html($destacada['emoji'] ?? '🏘️'); ?>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-gray-800 mb-1">
                        <a href="<?php echo esc_url($destacada['url']); ?>" class="hover:text-rose-600">
                            <?php echo esc_html($destacada['nombre']); ?>
                        </a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-2"><?php echo esc_html($destacada['descripcion']); ?></p>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>👥 <?php echo esc_html($destacada['miembros']); ?> miembros</span>
                        <span>📅 <?php echo esc_html($destacada['eventos_proximos'] ?? 0); ?> eventos próximos</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
