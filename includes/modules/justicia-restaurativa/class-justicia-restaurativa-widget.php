<?php
/**
 * Widget Dashboard: Justicia Restaurativa
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget de dashboard para el módulo Justicia Restaurativa
 */
class Flavor_Justicia_Restaurativa_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * Referencia al módulo
     *
     * @var Flavor_Chat_Justicia_Restaurativa_Module
     */
    private $module;

    /**
     * Constructor
     *
     * @param Flavor_Chat_Justicia_Restaurativa_Module $module
     */
    public function __construct($module) {
        $this->module = $module;

        $this->widget_id = 'justicia-restaurativa';
        $this->title = __('Justicia Restaurativa', 'flavor-chat-ia');
        $this->icon = 'dashicons-shield';
        $this->size = 'small';
        $this->category = 'gobernanza';
        $this->priority = 25;
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
                'empty_state' => __('Inicia sesión para ver tus procesos', 'flavor-chat-ia'),
            ];
        }

        $stats = $this->module->get_estadisticas_usuario($user_id);

        // Procesos pendientes de respuesta
        global $wpdb;
        $procesos_pendientes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
             WHERE p.post_type = 'jr_proceso'
               AND pm.meta_key = '_jr_otra_parte_id'
               AND pm.meta_value = %d
               AND pm2.meta_key = '_jr_estado'
               AND pm2.meta_value = 'solicitado'",
            $user_id
        ));

        $stats_array = [
            [
                'icon' => 'dashicons-shield',
                'valor' => $stats['procesos'],
                'label' => __('Procesos', 'flavor-chat-ia'),
                'color' => 'purple',
            ],
            [
                'icon' => 'dashicons-yes-alt',
                'valor' => $stats['acuerdos'],
                'label' => __('Acuerdos', 'flavor-chat-ia'),
                'color' => 'green',
            ],
        ];

        $items = [];
        if ($procesos_pendientes > 0) {
            $items[] = [
                'icon' => 'dashicons-warning',
                'title' => sprintf(
                    _n('%d invitación pendiente', '%d invitaciones pendientes', $procesos_pendientes, 'flavor-chat-ia'),
                    $procesos_pendientes
                ),
                'url' => home_url('/mi-portal/justicia-restaurativa/mis-procesos/'),
                'badge' => __('Pendiente', 'flavor-chat-ia'),
                'badge_color' => 'orange',
            ];
        }

        if ($stats['es_mediador']) {
            $stats_array[] = [
                'icon' => 'dashicons-businessman',
                'valor' => '✓',
                'label' => __('Mediador', 'flavor-chat-ia'),
                'color' => 'blue',
            ];
        }

        return [
            'stats' => $stats_array,
            'items' => $items,
            'empty_state' => __('No hay procesos activos', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Más info', 'flavor-chat-ia'),
                    'url' => home_url('/mi-portal/justicia-restaurativa/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }
}
