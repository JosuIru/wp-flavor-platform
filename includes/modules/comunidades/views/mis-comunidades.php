<?php
/**
 * Vista: Mis Comunidades
 *
 * Usa el sistema de componentes dinámicos para renderizar
 * la lista de comunidades del usuario.
 *
 * Variables disponibles:
 * - $comunidades: array de comunidades del usuario
 * - $categorias: array de categorias
 * - $limite: límite de items (opcional)
 * - $compacto: modo compacto (opcional)
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar funciones helper
if (!function_exists('flavor_render_component')) {
    $functions_path = FLAVOR_CHAT_IA_PATH . 'templates/components/shared/_functions.php';
    if (file_exists($functions_path)) {
        require_once $functions_path;
    }
}

// Valores por defecto
$comunidades = $comunidades ?? [];
$categorias = $categorias ?? [];
$limite = $limite ?? 0;
$compacto = $compacto ?? false;

// Aplicar límite si está definido
if ($limite > 0 && count($comunidades) > $limite) {
    $comunidades = array_slice($comunidades, 0, $limite);
}

// Mapeo de roles para badges
$roles_config = [
    'fundador'  => ['label' => __('Fundador', 'flavor-chat-ia'), 'color' => 'purple', 'icon' => '👑'],
    'admin'     => ['label' => __('Admin', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '⚙️'],
    'moderador' => ['label' => __('Moderador', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🛡️'],
    'miembro'   => ['label' => __('Miembro', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '👤'],
];

// Preparar items para el componente
$items_preparados = [];
foreach ($comunidades as $comunidad) {
    // Convertir a objeto si es array
    if (is_array($comunidad)) {
        $comunidad = (object) $comunidad;
    }

    $rol = $comunidad->rol ?? 'miembro';
    $rol_info = $roles_config[$rol] ?? $roles_config['miembro'];
    $categoria = $comunidad->categoria ?? 'otros';
    $categoria_nombre = $categorias[$categoria] ?? ucfirst($categoria);

    $items_preparados[] = [
        'id'              => $comunidad->id ?? 0,
        'titulo'          => $comunidad->nombre ?? '',
        'descripcion'     => $comunidad->descripcion ?? '',
        'imagen'          => $comunidad->imagen_portada ?? $comunidad->imagen ?? '',
        'url'             => add_query_arg('comunidad_id', ($comunidad->id ?? 0), Flavor_Chat_Helpers::get_action_url('comunidades', 'detalle')),
        'categoria'       => $categoria,
        'categoria_nombre'=> $categoria_nombre,
        'rol'             => $rol,
        'rol_label'       => $rol_info['label'],
        'rol_color'       => $rol_info['color'],
        'rol_icon'        => $rol_info['icon'],
        'miembros'        => $comunidad->total_miembros ?? $comunidad->miembros_count ?? 0,
        'actividad'       => $comunidad->actividad_reciente ?? '',
        'es_admin'        => in_array($rol, ['fundador', 'admin']),
    ];
}

// Configuración de la card
$card_config = [
    'color'      => 'teal',
    'icon'       => '👥',
    'show_image' => true,
    'layout'     => $compacto ? 'horizontal' : 'vertical',

    'fields' => [
        'id'       => 'id',
        'title'    => 'titulo',
        'subtitle' => 'descripcion',
        'image'    => 'imagen',
        'url'      => 'url',
    ],

    'badge' => [
        'field'  => 'rol',
        'colors' => [
            'fundador'  => 'purple',
            'admin'     => 'blue',
            'moderador' => 'green',
            'miembro'   => 'gray',
        ],
        'icons' => [
            'fundador'  => '👑',
            'admin'     => '⚙️',
            'moderador' => '🛡️',
            'miembro'   => '👤',
        ],
        'labels' => [
            'fundador'  => __('Fundador', 'flavor-chat-ia'),
            'admin'     => __('Admin', 'flavor-chat-ia'),
            'moderador' => __('Moderador', 'flavor-chat-ia'),
            'miembro'   => __('Miembro', 'flavor-chat-ia'),
        ],
    ],

    'secondary_badge' => [
        'field'      => 'categoria_nombre',
        'color'      => 'slate',
        'icon_field' => null,
    ],

    'meta' => [
        ['icon' => '👥', 'field' => 'miembros', 'suffix' => ' ' . __('miembros', 'flavor-chat-ia')],
    ],

    'actions' => [
        [
            'label'     => __('Entrar', 'flavor-chat-ia'),
            'icon'      => '🚀',
            'url_field' => 'url',
            'primary'   => true,
        ],
    ],

    'data_attrs' => ['categoria', 'rol'],
];

// Si es admin, añadir botón de gestión
if (!$compacto) {
    $card_config['actions'][] = [
        'label'     => __('Gestionar', 'flavor-chat-ia'),
        'icon'      => '⚙️',
        'url_field' => 'url',
        'url_suffix'=> '?gestionar=1',
        'primary'   => false,
        'condition' => 'es_admin',
    ];
}

// Configuración del estado vacío
$empty_config = [
    'icon'     => '🏘️',
    'title'    => __('Aún no perteneces a ninguna comunidad', 'flavor-chat-ia'),
    'text'     => __('Explora las comunidades disponibles y únete a las que te interesen.', 'flavor-chat-ia'),
    'cta_text' => __('Explorar comunidades', 'flavor-chat-ia'),
    'cta_url'  => Flavor_Chat_Helpers::get_action_url('comunidades', ''),
    'color'    => 'teal',
];
?>

<div class="flavor-mis-comunidades">
    <?php if (!$compacto): ?>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">
                <?php esc_html_e('Mis Comunidades', 'flavor-chat-ia'); ?>
            </h2>
            <p class="text-gray-500 mt-1">
                <?php
                $total = count($items_preparados);
                printf(
                    esc_html(_n('Perteneces a %d comunidad', 'Perteneces a %d comunidades', $total, 'flavor-chat-ia')),
                    $total
                );
                ?>
            </p>
        </div>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('comunidades', 'crear')); ?>"
           class="inline-flex items-center gap-2 px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white rounded-xl font-medium transition-colors">
            <span>➕</span>
            <?php esc_html_e('Crear comunidad', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- Grid de comunidades -->
    <?php
    if (function_exists('flavor_render_component')) {
        flavor_render_component('items-grid', [
            'items'       => $items_preparados,
            'columns'     => $compacto ? 1 : 3,
            'gap'         => $compacto ? 'sm' : 'md',
            'card_config' => $card_config,
            'empty_state' => $empty_config,
            'data_attr'   => 'categoria',
        ]);
    } else {
        // Fallback básico si no hay componentes
        if (empty($items_preparados)) {
            echo '<div class="text-center py-12 text-gray-500">';
            echo '<p>🏘️</p>';
            echo '<p>' . esc_html__('Aún no perteneces a ninguna comunidad', 'flavor-chat-ia') . '</p>';
            echo '</div>';
        } else {
            echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
            foreach ($items_preparados as $item) {
                ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
                    <h3 class="font-bold text-lg mb-2">
                        <a href="<?php echo esc_url($item['url']); ?>" class="hover:text-teal-600">
                            <?php echo esc_html($item['titulo']); ?>
                        </a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-3"><?php echo esc_html($item['descripcion']); ?></p>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <span>👥 <?php echo esc_html($item['miembros']); ?></span>
                        <span class="px-2 py-0.5 bg-gray-100 rounded-full text-xs"><?php echo esc_html($item['rol_label']); ?></span>
                    </div>
                    <a href="<?php echo esc_url($item['url']); ?>" class="mt-3 block w-full text-center py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors">
                        <?php esc_html_e('Entrar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php
            }
            echo '</div>';
        }
    }
    ?>

    <?php if ($compacto && count($items_preparados) > 0): ?>
    <!-- Ver más en modo compacto -->
    <div class="mt-4 text-center">
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('comunidades', 'mis-comunidades')); ?>"
           class="text-teal-600 hover:text-teal-700 font-medium inline-flex items-center gap-1">
            <?php esc_html_e('Ver todas mis comunidades', 'flavor-chat-ia'); ?>
            <span>→</span>
        </a>
    </div>
    <?php endif; ?>
</div>
