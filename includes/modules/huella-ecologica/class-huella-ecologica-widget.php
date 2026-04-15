<?php
/**
 * Widget Dashboard: Huella Ecológica
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget de dashboard para el módulo Huella Ecológica
 */
class Flavor_Huella_Ecologica_Widget extends Flavor_Dashboard_Widget_Base {

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

        $this->widget_id = 'huella-ecologica';
        $this->title = __('Huella Ecológica', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->icon = 'dashicons-palmtree';
        $this->size = 'medium';
        $this->category = 'ecologia';
        $this->priority = 20;
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
                'empty_state' => __('Inicia sesión para ver tu huella ecológica', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $stats = $this->module->get_estadisticas_usuario($user_id, 'mes');
        $stats_comunidad = $this->module->get_estadisticas_comunidad();

        // Calcular tendencia (comparar con mes anterior)
        $huella_neta = $stats['huella_neta'];
        $tendencia = $huella_neta <= 0 ? 'positiva' : ($huella_neta < 5 ? 'neutral' : 'negativa');

        $stats_array = [
            [
                'icon' => 'dashicons-cloud',
                'valor' => $stats['huella_total'] . ' kg',
                'label' => __('Emitido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'red',
            ],
            [
                'icon' => 'dashicons-yes-alt',
                'valor' => $stats['reduccion_total'] . ' kg',
                'label' => __('Compensado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => 'green',
            ],
            [
                'icon' => 'dashicons-performance',
                'valor' => $stats['huella_neta'] . ' kg',
                'label' => __('Neta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $huella_neta <= 0 ? 'green' : ($huella_neta < 5 ? 'orange' : 'red'),
            ],
        ];

        $items = [];

        // Logros desbloqueados recientemente
        $logros_obtenidos = array_filter($stats['logros'], fn($l) => $l['obtenido']);
        if (count($logros_obtenidos) > 0) {
            $ultimo_logro = end($logros_obtenidos);
            $items[] = [
                'icon' => 'dashicons-awards',
                'title' => sprintf(__('Logro: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $ultimo_logro['nombre']),
                'meta' => $ultimo_logro['icono'],
                'badge' => sprintf(__('%d pts', FLAVOR_PLATFORM_TEXT_DOMAIN), $ultimo_logro['puntos']),
                'badge_color' => 'green',
            ];
        }

        // Proyectos activos
        if ($stats['proyectos'] > 0) {
            $items[] = [
                'icon' => 'dashicons-groups',
                'title' => sprintf(
                    _n('Participas en %d proyecto', 'Participas en %d proyectos', $stats['proyectos'], FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $stats['proyectos']
                ),
                'url' => $this->get_context_url('/mi-portal/huella-ecologica/proyectos/', 'flavor-huella-ecologica'),
            ];
        }

        // Mensaje de tendencia
        if ($tendencia === 'positiva') {
            $items[] = [
                'icon' => 'dashicons-thumbs-up',
                'title' => __('¡Eres carbono neutro este mes!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'badge' => '🌍',
                'badge_color' => 'green',
            ];
        }

        return [
            'stats' => $stats_array,
            'items' => $items,
            'empty_state' => __('Calcula tu huella ecológica', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer' => [
                [
                    'label' => $this->is_admin_context() ? __('Ver panel', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Calculadora', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => $this->get_context_url('/mi-portal/huella-ecologica/calculadora/', 'flavor-huella-ecologica'),
                    'icon' => 'dashicons-chart-bar',
                ],
            ],
            'extra' => [
                'comunidad_huella_neta' => $stats_comunidad['huella_neta'],
                'comunidad_usuarios' => $stats_comunidad['usuarios_activos'],
            ],
        ];
    }
}
