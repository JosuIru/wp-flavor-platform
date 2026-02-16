<?php
/**
 * Widget Dashboard: Huella Ecológica
 *
 * @package FlavorChatIA
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
     * @var Flavor_Chat_Huella_Ecologica_Module
     */
    private $module;

    /**
     * Constructor
     *
     * @param Flavor_Chat_Huella_Ecologica_Module $module
     */
    public function __construct($module) {
        $this->module = $module;

        $this->widget_id = 'huella-ecologica';
        $this->title = __('Huella Ecológica', 'flavor-chat-ia');
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
                'empty_state' => __('Inicia sesión para ver tu huella ecológica', 'flavor-chat-ia'),
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
                'label' => __('Emitido', 'flavor-chat-ia'),
                'color' => 'red',
            ],
            [
                'icon' => 'dashicons-yes-alt',
                'valor' => $stats['reduccion_total'] . ' kg',
                'label' => __('Compensado', 'flavor-chat-ia'),
                'color' => 'green',
            ],
            [
                'icon' => 'dashicons-performance',
                'valor' => $stats['huella_neta'] . ' kg',
                'label' => __('Neta', 'flavor-chat-ia'),
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
                'title' => sprintf(__('Logro: %s', 'flavor-chat-ia'), $ultimo_logro['nombre']),
                'meta' => $ultimo_logro['icono'],
                'badge' => sprintf(__('%d pts', 'flavor-chat-ia'), $ultimo_logro['puntos']),
                'badge_color' => 'green',
            ];
        }

        // Proyectos activos
        if ($stats['proyectos'] > 0) {
            $items[] = [
                'icon' => 'dashicons-groups',
                'title' => sprintf(
                    _n('Participas en %d proyecto', 'Participas en %d proyectos', $stats['proyectos'], 'flavor-chat-ia'),
                    $stats['proyectos']
                ),
                'url' => $this->get_context_url('/mi-portal/huella-ecologica/proyectos/', 'flavor-huella-ecologica'),
            ];
        }

        // Mensaje de tendencia
        if ($tendencia === 'positiva') {
            $items[] = [
                'icon' => 'dashicons-thumbs-up',
                'title' => __('¡Eres carbono neutro este mes!', 'flavor-chat-ia'),
                'badge' => '🌍',
                'badge_color' => 'green',
            ];
        }

        return [
            'stats' => $stats_array,
            'items' => $items,
            'empty_state' => __('Calcula tu huella ecológica', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => $this->is_admin_context() ? __('Ver panel', 'flavor-chat-ia') : __('Calculadora', 'flavor-chat-ia'),
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
