<?php
/**
 * Widget Dashboard: Biodiversidad Local
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Biodiversidad_Local_Widget extends Flavor_Dashboard_Widget_Base {

    protected $module;

    public function __construct($module) {
        $this->module = $module;

        $this->widget_id = 'biodiversidad-local';
        $this->title = __('Biodiversidad Local', 'flavor-chat-ia');
        $this->icon = 'dashicons-admin-site-alt3';
        $this->size = 'medium';
        $this->category = 'medioambiente';
        $this->priority = 25;
    }

    public function get_widget_data(): array {
        $user_id = get_current_user_id();
        $stats = $this->module->get_estadisticas();

        $stats_array = [
            [
                'icon' => 'dashicons-admin-site-alt3',
                'valor' => $stats['especies_catalogadas'],
                'label' => __('Especies', 'flavor-chat-ia'),
                'color' => 'green',
            ],
            [
                'icon' => 'dashicons-visibility',
                'valor' => $stats['avistamientos_total'],
                'label' => __('Avistamientos', 'flavor-chat-ia'),
                'color' => 'blue',
            ],
            [
                'icon' => 'dashicons-shield',
                'valor' => $stats['proyectos_activos'],
                'label' => __('Proyectos', 'flavor-chat-ia'),
                'color' => 'orange',
            ],
        ];

        $items = [];

        // Mis avistamientos
        if ($stats['mis_avistamientos'] > 0) {
            $items[] = [
                'icon' => 'dashicons-camera',
                'title' => sprintf(
                    _n('%d avistamiento registrado', '%d avistamientos registrados', $stats['mis_avistamientos'], 'flavor-chat-ia'),
                    $stats['mis_avistamientos']
                ),
                'url' => $this->get_context_url('/mi-portal/biodiversidad/', 'flavor-biodiversidad'),
            ];
        }

        // Avistamientos pendientes de validar
        if ($stats['avistamientos_pendientes'] > 0) {
            $items[] = [
                'icon' => 'dashicons-yes-alt',
                'title' => sprintf(
                    _n('%d avistamiento pendiente de validar', '%d avistamientos pendientes de validar', $stats['avistamientos_pendientes'], 'flavor-chat-ia'),
                    $stats['avistamientos_pendientes']
                ),
                'url' => home_url('/biodiversidad/mapa/'),
                'badge' => __('Ayuda', 'flavor-chat-ia'),
            ];
        }

        // Proyectos activos
        if ($stats['proyectos_activos'] > 0) {
            $items[] = [
                'icon' => 'dashicons-shield',
                'title' => sprintf(
                    _n('%d proyecto de conservación activo', '%d proyectos de conservación activos', $stats['proyectos_activos'], 'flavor-chat-ia'),
                    $stats['proyectos_activos']
                ),
                'url' => home_url('/biodiversidad/proyectos/'),
            ];
        }

        return [
            'stats' => $stats_array,
            'items' => $items,
            'empty_state' => __('Explora la biodiversidad de tu territorio', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver catálogo', 'flavor-chat-ia'),
                    'url' => home_url('/biodiversidad/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
                [
                    'label' => __('Registrar', 'flavor-chat-ia'),
                    'url' => home_url('/biodiversidad/registrar/'),
                    'icon' => 'dashicons-camera',
                ],
            ],
        ];
    }
}
