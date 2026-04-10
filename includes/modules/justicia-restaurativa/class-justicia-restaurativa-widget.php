<?php
/**
 * Widget Dashboard: Justicia Restaurativa
 *
 * @package FlavorPlatform
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

        $this->widget_id = 'justicia-restaurativa';
        $this->title = __('Justicia Restaurativa', FLAVOR_PLATFORM_TEXT_DOMAIN);
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
                'empty_state' => __('Inicia sesión para ver tus procesos', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
                'label' => __('Procesos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'purple',
            ],
            [
                'icon' => 'dashicons-yes-alt',
                'valor' => $stats['acuerdos'],
                'label' => __('Acuerdos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'green',
            ],
        ];

        $items = [];
        if ($procesos_pendientes > 0) {
            $items[] = [
                'icon' => 'dashicons-warning',
                'title' => sprintf(
                    _n('%d invitación pendiente', '%d invitaciones pendientes', $procesos_pendientes, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $procesos_pendientes
                ),
                'url' => $this->get_context_url('/mi-portal/justicia-restaurativa/mis-procesos/', 'flavor-justicia-restaurativa'),
                'badge' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'badge_color' => 'orange',
            ];
        }

        if ($stats['es_mediador']) {
            $stats_array[] = [
                'icon' => 'dashicons-businessman',
                'valor' => '✓',
                'label' => __('Mediador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'blue',
            ];
        }

        return [
            'stats' => $stats_array,
            'items' => $items,
            'empty_state' => __('No hay procesos activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => [
                [
                    'label' => __('Más info', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => $this->get_context_url('/mi-portal/justicia-restaurativa/', 'flavor-justicia-restaurativa'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }
}
