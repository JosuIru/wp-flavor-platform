<?php
/**
 * Widget Dashboard: Círculos de Cuidados
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget de dashboard para el módulo de Círculos de Cuidados
 */
class Flavor_Circulos_Cuidados_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * Referencia al módulo
     *
     * @var Flavor_Chat_Circulos_Cuidados_Module
     */
    private $module;

    /**
     * Constructor
     *
     * @param Flavor_Chat_Circulos_Cuidados_Module $module
     */
    public function __construct($module) {
        $this->module = $module;

        $this->widget_id = 'circulos-cuidados';
        $this->title = __('Círculos de Cuidados', 'flavor-chat-ia');
        $this->icon = 'dashicons-heart';
        $this->size = 'medium';
        $this->category = 'cuidados';
        $this->priority = 15;
    }

    /**
     * Obtiene los datos del widget
     *
     * @return array
     */
    public function get_widget_data(): array {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return [
                'stats' => [],
                'items' => [],
                'empty_state' => __('Inicia sesión para ver tus círculos de cuidados', 'flavor-chat-ia'),
            ];
        }

        $stats = $this->module->get_estadisticas_usuario($user_id);

        // Obtener necesidades urgentes de mis círculos
        global $wpdb;

        // IDs de mis círculos
        $mis_circulos = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'cc_circulo'
               AND pm.meta_key = '_cc_miembros'
               AND pm.meta_value LIKE %s",
            '%"' . $user_id . '"%'
        ));

        $necesidades_urgentes = [];
        if (!empty($mis_circulos)) {
            $placeholders = implode(',', array_fill(0, count($mis_circulos), '%d'));
            $necesidades_urgentes = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, p.post_title, pm_u.meta_value as urgencia
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm_c ON p.ID = pm_c.post_id
                 INNER JOIN {$wpdb->postmeta} pm_e ON p.ID = pm_e.post_id
                 INNER JOIN {$wpdb->postmeta} pm_u ON p.ID = pm_u.post_id
                 WHERE p.post_type = 'cc_necesidad'
                   AND p.post_status = 'publish'
                   AND pm_c.meta_key = '_cc_circulo_id'
                   AND pm_c.meta_value IN ($placeholders)
                   AND pm_e.meta_key = '_cc_estado'
                   AND pm_e.meta_value = 'abierta'
                   AND pm_u.meta_key = '_cc_urgencia'
                 ORDER BY FIELD(pm_u.meta_value, 'urgente', 'alta', 'normal', 'baja')
                 LIMIT 5",
                ...$mis_circulos
            ));
        }

        // Formatear items para el widget
        $items = [];
        foreach ($necesidades_urgentes as $necesidad) {
            $urgencia_colores = [
                'urgente' => 'red',
                'alta' => 'orange',
                'normal' => 'blue',
                'baja' => 'gray',
            ];

            $items[] = [
                'icon' => 'dashicons-sos',
                'title' => $necesidad->post_title,
                'meta' => ucfirst($necesidad->urgencia),
                'url' => get_permalink($necesidad->ID),
                'badge' => $necesidad->urgencia === 'urgente' ? __('Urgente', 'flavor-chat-ia') : null,
                'badge_color' => $urgencia_colores[$necesidad->urgencia] ?? 'gray',
            ];
        }

        return [
            'stats' => [
                [
                    'icon' => 'dashicons-heart',
                    'valor' => $stats['circulos'],
                    'label' => __('Círculos', 'flavor-chat-ia'),
                    'color' => 'red',
                ],
                [
                    'icon' => 'dashicons-clock',
                    'valor' => number_format($stats['horas_cuidado'], 1) . 'h',
                    'label' => __('Horas donadas', 'flavor-chat-ia'),
                    'color' => 'green',
                ],
                [
                    'icon' => 'dashicons-groups',
                    'valor' => $stats['necesidades_ayudadas'],
                    'label' => __('Ayudas', 'flavor-chat-ia'),
                    'color' => 'purple',
                ],
            ],
            'items' => $items,
            'empty_state' => __('No hay necesidades pendientes en tus círculos', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver círculos', 'flavor-chat-ia'),
                    'url' => home_url('/mi-portal/circulos-cuidados/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
                [
                    'label' => __('Ver necesidades', 'flavor-chat-ia'),
                    'url' => home_url('/mi-portal/circulos-cuidados/necesidades/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * Renderiza contenido personalizado
     *
     * @param array $data
     */
    public function render_custom_content(array $data): void {
        if (empty($data['items'])) {
            return;
        }

        // Mostrar alerta si hay necesidades urgentes
        $urgentes = array_filter($data['items'], function($item) {
            return ($item['badge'] ?? '') === __('Urgente', 'flavor-chat-ia');
        });

        if (!empty($urgentes)) {
            ?>
            <div class="cc-widget-alerta">
                <span class="dashicons dashicons-warning"></span>
                <?php printf(
                    esc_html(_n(
                        'Hay %d necesidad urgente en tus círculos',
                        'Hay %d necesidades urgentes en tus círculos',
                        count($urgentes),
                        'flavor-chat-ia'
                    )),
                    count($urgentes)
                ); ?>
            </div>
            <?php
        }
    }
}
