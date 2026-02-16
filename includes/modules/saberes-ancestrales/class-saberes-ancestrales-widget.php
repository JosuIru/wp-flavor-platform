<?php
/**
 * Widget Dashboard: Saberes Ancestrales
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Saberes_Ancestrales_Widget extends Flavor_Dashboard_Widget_Base {

    private $module;

    public function __construct($module) {
        $this->module = $module;

        $this->widget_id = 'saberes-ancestrales';
        $this->title = __('Saberes Ancestrales', 'flavor-chat-ia');
        $this->icon = 'dashicons-book';
        $this->size = 'small';
        $this->category = 'cultura';
        $this->priority = 30;
    }

    public function get_widget_data(): array {
        $user_id = get_current_user_id();
        $stats = $this->module->get_estadisticas();

        $stats_array = [
            [
                'icon' => 'dashicons-book-alt',
                'valor' => $stats['saberes_total'],
                'label' => __('Saberes', 'flavor-chat-ia'),
                'color' => 'brown',
            ],
            [
                'icon' => 'dashicons-calendar-alt',
                'valor' => $stats['talleres_proximos'],
                'label' => __('Talleres', 'flavor-chat-ia'),
                'color' => 'orange',
            ],
        ];

        $items = [];
        if ($stats['talleres_proximos'] > 0) {
            $items[] = [
                'icon' => 'dashicons-calendar-alt',
                'title' => sprintf(
                    _n('%d taller próximo', '%d talleres próximos', $stats['talleres_proximos'], 'flavor-chat-ia'),
                    $stats['talleres_proximos']
                ),
                'url' => ->get_context_url('/mi-portal/saberes-ancestrales/talleres/', 'flavor-saberes-ancestrales'),
            ];
        }

        return [
            'stats' => $stats_array,
            'items' => $items,
            'empty_state' => __('Explora los saberes de la comunidad', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver catálogo', 'flavor-chat-ia'),
                    'url' => ->get_context_url('/mi-portal/saberes-ancestrales/', 'flavor-saberes-ancestrales'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }
}
