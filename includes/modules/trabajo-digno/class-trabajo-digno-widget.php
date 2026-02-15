<?php
/**
 * Widget Dashboard: Trabajo Digno
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Trabajo_Digno_Widget extends Flavor_Dashboard_Widget_Base {

    private $module;

    public function __construct($module) {
        $this->module = $module;

        $this->widget_id = 'trabajo-digno';
        $this->title = __('Trabajo Digno', 'flavor-chat-ia');
        $this->icon = 'dashicons-businessman';
        $this->size = 'medium';
        $this->category = 'economia';
        $this->priority = 35;
    }

    public function get_widget_data(): array {
        $user_id = get_current_user_id();
        $stats = $this->module->get_estadisticas();

        $stats_array = [
            [
                'icon' => 'dashicons-businessman',
                'valor' => $stats['ofertas_activas'],
                'label' => __('Ofertas', 'flavor-chat-ia'),
                'color' => 'primary',
            ],
            [
                'icon' => 'dashicons-welcome-learn-more',
                'valor' => $stats['formaciones_disponibles'],
                'label' => __('Formación', 'flavor-chat-ia'),
                'color' => 'purple',
            ],
            [
                'icon' => 'dashicons-store',
                'valor' => $stats['emprendimientos_locales'],
                'label' => __('Empresas', 'flavor-chat-ia'),
                'color' => 'green',
            ],
        ];

        $items = [];

        // Mis postulaciones
        if ($stats['mis_postulaciones'] > 0) {
            $items[] = [
                'icon' => 'dashicons-portfolio',
                'title' => sprintf(
                    _n('%d postulación activa', '%d postulaciones activas', $stats['mis_postulaciones'], 'flavor-chat-ia'),
                    $stats['mis_postulaciones']
                ),
                'url' => home_url('/mi-portal/trabajo-digno/'),
            ];
        }

        // Ofertas recientes
        $ofertas_recientes = get_posts([
            'post_type' => 'td_oferta',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        foreach ($ofertas_recientes as $oferta) {
            $tipo = get_post_meta($oferta->ID, '_td_tipo', true);
            $tipos = Flavor_Chat_Trabajo_Digno_Module::TIPOS_OFERTA;
            $tipo_data = $tipos[$tipo] ?? ['nombre' => ''];

            $items[] = [
                'icon' => 'dashicons-businessman',
                'title' => $oferta->post_title,
                'meta' => $tipo_data['nombre'],
                'url' => get_permalink($oferta->ID),
            ];
        }

        return [
            'stats' => $stats_array,
            'items' => $items,
            'empty_state' => __('Explora oportunidades de trabajo digno', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver ofertas', 'flavor-chat-ia'),
                    'url' => home_url('/trabajo-digno/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
                [
                    'label' => __('Publicar', 'flavor-chat-ia'),
                    'url' => home_url('/trabajo-digno/publicar/'),
                    'icon' => 'dashicons-plus-alt',
                ],
            ],
        ];
    }
}
