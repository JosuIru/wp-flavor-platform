<?php
/**
 * Frontend: Archive de Reciclaje
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

// Cargar funciones helper
if (!function_exists('flavor_render_component')) {
    require_once FLAVOR_PLUGIN_PATH . 'templates/components/shared/_functions.php';
}

// Variables esperadas
$puntos = $puntos ?? [];
$total_puntos = $total_puntos ?? count($puntos);
$estadisticas = $estadisticas ?? [];
$current_page = $current_page ?? 1;
$per_page = $per_page ?? 12;

// Contenedores guía
$contenedores_guia = [
    ['color' => 'bg-yellow-400', 'nombre' => __('Amarillo', 'flavor-chat-ia'), 'residuos' => __('Plásticos y envases', 'flavor-chat-ia')],
    ['color' => 'bg-blue-500', 'nombre' => __('Azul', 'flavor-chat-ia'), 'residuos' => __('Papel y cartón', 'flavor-chat-ia')],
    ['color' => 'bg-green-600', 'nombre' => __('Verde', 'flavor-chat-ia'), 'residuos' => __('Vidrio', 'flavor-chat-ia')],
    ['color' => 'bg-amber-700', 'nombre' => __('Marrón', 'flavor-chat-ia'), 'residuos' => __('Orgánico', 'flavor-chat-ia')],
    ['color' => 'bg-gray-500', 'nombre' => __('Gris', 'flavor-chat-ia'), 'residuos' => __('Resto', 'flavor-chat-ia')],
];

// Construir stats
$stats = [];
if (!empty($estadisticas)) {
    $stats = [
        ['value' => $estadisticas['puntos_totales'] ?? 0, 'label' => __('Puntos de reciclaje', 'flavor-chat-ia'), 'icon' => '📍'],
        ['value' => $estadisticas['kg_reciclados'] ?? '0', 'label' => __('Kg reciclados', 'flavor-chat-ia'), 'icon' => '♻️'],
        ['value' => $estadisticas['usuarios_activos'] ?? 0, 'label' => __('Usuarios activos', 'flavor-chat-ia'), 'icon' => '👥'],
        ['value' => $estadisticas['co2_evitado'] ?? '0 kg', 'label' => __('CO2 evitado', 'flavor-chat-ia'), 'icon' => '🌿'],
    ];
}

// Filtros por tipo de contenedor
$filters = [
    ['id' => 'todos', 'label' => __('Todos', 'flavor-chat-ia'), 'active' => true],
    ['id' => 'amarillo', 'label' => __('Amarillo', 'flavor-chat-ia'), 'icon' => '🟡'],
    ['id' => 'azul', 'label' => __('Azul', 'flavor-chat-ia'), 'icon' => '🔵'],
    ['id' => 'verde', 'label' => __('Verde', 'flavor-chat-ia'), 'icon' => '🟢'],
    ['id' => 'marron', 'label' => __('Marrón', 'flavor-chat-ia'), 'icon' => '🟤'],
    ['id' => 'punto_limpio', 'label' => __('Punto Limpio', 'flavor-chat-ia'), 'icon' => '🏭'],
];

// Pasos de "Cómo funciona"
$como_funciona_steps = [
    ['icon' => '🔍', 'title' => __('Localiza', 'flavor-chat-ia'), 'text' => __('Encuentra el punto de reciclaje más cercano', 'flavor-chat-ia')],
    ['icon' => '🗑️', 'title' => __('Separa', 'flavor-chat-ia'), 'text' => __('Clasifica tus residuos por tipo de contenedor', 'flavor-chat-ia')],
    ['icon' => '🌍', 'title' => __('Recicla', 'flavor-chat-ia'), 'text' => __('Deposita cada residuo en su contenedor correcto', 'flavor-chat-ia')],
];

// Renderizar usando el Archive Renderer
$renderer = new Flavor_Archive_Renderer();

echo $renderer->render([
    'module'       => 'reciclaje',
    'title'        => __('Puntos de Reciclaje', 'flavor-chat-ia'),
    'subtitle'     => __('Encuentra dónde reciclar cada tipo de residuo', 'flavor-chat-ia'),
    'icon'         => '♻️',
    'color'        => 'emerald',
    'items'        => $puntos,
    'total'        => $total_puntos,
    'per_page'     => $per_page,
    'current_page' => $current_page,
    'stats'        => $stats,
    'stats_layout' => 'vertical',
    'columns'      => 1,
    'layout'       => 'list',
    'filters'      => $filters,
    'filter_data_attr' => 'tipo',
    'card_template' => 'reciclaje/card',
    'extra_content' => function() use ($como_funciona_steps, $contenedores_guia) {
        // Guía de contenedores
        ?>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <?php foreach ($contenedores_guia as $cont): ?>
            <div class="bg-white rounded-xl p-4 shadow-md text-center">
                <div class="w-12 h-12 rounded-xl <?php echo esc_attr($cont['color']); ?> mx-auto mb-2"></div>
                <p class="font-bold text-gray-900 text-sm"><?php echo esc_html($cont['nombre']); ?></p>
                <p class="text-xs text-gray-500"><?php echo esc_html($cont['residuos']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        flavor_render_component('como-funciona', [
            'steps' => $como_funciona_steps,
            'color' => 'emerald',
        ]);
    },
    'empty_state'  => [
        'icon'       => '📍',
        'title'      => __('No hay puntos cercanos', 'flavor-chat-ia'),
        'text'       => __('Activa tu ubicación para ver los puntos más próximos', 'flavor-chat-ia'),
    ],
]);
