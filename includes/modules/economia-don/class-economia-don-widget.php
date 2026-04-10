<?php
/**
 * Widget Dashboard: Economía del Don
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget de dashboard para el módulo Economía del Don
 */
class Flavor_Economia_Don_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * Referencia al módulo
     *
     * @var Flavor_Platform_Module_Interface
     */
    protected $module;

    /**
     * Constructor
     *
     * @param Flavor_Platform_Module_Interface $module
     */
    public function __construct($module) {
        $this->module = $module;

        $this->widget_id = 'economia-don';
        $this->title = __('Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->icon = 'dashicons-heart';
        $this->size = 'medium';
        $this->category = 'economia';
        $this->priority = 20;
    }

    /**
     * Obtiene los datos del widget
     *
     * @return array
     */
    public function get_widget_data(): array {
        $user_id = get_current_user_id();

        // Estadísticas del usuario
        $stats = [];
        if ($user_id) {
            $user_stats = $this->module->get_estadisticas_usuario($user_id);
            $stats = [
                [
                    'icon' => 'dashicons-upload',
                    'valor' => $user_stats['dones_dados'],
                    'label' => __('Dados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => 'red',
                ],
                [
                    'icon' => 'dashicons-download',
                    'valor' => $user_stats['dones_recibidos'],
                    'label' => __('Recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => 'purple',
                ],
                [
                    'icon' => 'dashicons-marker',
                    'valor' => $user_stats['dones_activos'],
                    'label' => __('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => 'green',
                ],
            ];
        }

        // Últimos dones disponibles
        $dones_disponibles = get_posts([
            'post_type' => 'ed_don',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'meta_query' => [
                ['key' => '_ed_estado', 'value' => 'disponible'],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $economia_don_module_class = function_exists('flavor_get_runtime_class_name')
            ? flavor_get_runtime_class_name('Flavor_Chat_Economia_Don_Module')
            : 'Flavor_Chat_Economia_Don_Module';
        $categorias = $economia_don_module_class::CATEGORIAS_DON;
        $items = [];

        foreach ($dones_disponibles as $don) {
            $categoria = get_post_meta($don->ID, '_ed_categoria', true);
            $cat_data = $categorias[$categoria] ?? $categorias['objetos'];
            $ubicacion = get_post_meta($don->ID, '_ed_ubicacion', true);

            $items[] = [
                'icon' => $cat_data['icono'],
                'title' => $don->post_title,
                'meta' => $ubicacion ?: $cat_data['nombre'],
                'url' => get_permalink($don->ID),
                'color' => $cat_data['color'],
            ];
        }

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('No hay dones disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => [
                [
                    'label' => __('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => $this->get_context_url('/mi-portal/economia-don/', 'flavor-economia-don'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
                [
                    'label' => __('Ofrecer don', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => $this->get_context_url('/mi-portal/economia-don/ofrecer/', 'flavor-economia-don'),
                    'icon' => 'dashicons-heart',
                ],
            ],
        ];
    }
}
