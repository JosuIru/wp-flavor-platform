<?php
/**
 * Widget Dashboard: Economía de Suficiencia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget de dashboard para el módulo Economía de Suficiencia
 */
class Flavor_Economia_Suficiencia_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * Referencia al módulo
     *
     * @var Flavor_Chat_Economia_Suficiencia_Module
     */
    protected $module;

    /**
     * Constructor
     *
     * @param Flavor_Chat_Economia_Suficiencia_Module $module
     */
    public function __construct($module) {
        $this->module = $module;

        $this->widget_id = 'economia-suficiencia';
        $this->title = __('Economía de Suficiencia', 'flavor-platform');
        $this->icon = 'dashicons-editor-expand';
        $this->size = 'small';
        $this->category = 'economia';
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
                'empty_state' => __('Inicia sesión para ver tu camino de suficiencia', 'flavor-platform'),
            ];
        }

        $stats = $this->module->get_estadisticas_usuario($user_id);
        $nivel = $stats['nivel'];

        $iconos_nivel = ['explorando' => '🌱', 'consciente' => '🌿', 'practicante' => '🌳', 'mentor' => '🌲', 'sabio' => '🏔️'];

        $stats_array = [
            [
                'icon' => 'dashicons-awards',
                'valor' => $nivel['nivel']['nombre'],
                'label' => __('Nivel', 'flavor-platform'),
                'color' => 'green',
                'extra' => $iconos_nivel[$nivel['nivel']['id']] ?? '🌱',
            ],
            [
                'icon' => 'dashicons-yes-alt',
                'valor' => $stats['compromisos_activos'],
                'label' => __('Compromisos', 'flavor-platform'),
                'color' => 'blue',
            ],
        ];

        $items = [];

        if ($stats['compromisos_activos'] > 0) {
            $items[] = [
                'icon' => 'dashicons-calendar-alt',
                'title' => sprintf(
                    _n('%d práctica este mes', '%d prácticas este mes', $stats['practicas_mes'], 'flavor-platform'),
                    $stats['practicas_mes']
                ),
                'url' => $this->get_context_url('/mi-portal/economia-suficiencia/compromisos/', 'flavor-economia-suficiencia'),
            ];
        }

        if ($stats['recursos_compartidos'] > 0) {
            $items[] = [
                'icon' => 'dashicons-share',
                'title' => sprintf(
                    _n('%d objeto compartido', '%d objetos compartidos', $stats['recursos_compartidos'], 'flavor-platform'),
                    $stats['recursos_compartidos']
                ),
                'url' => $this->get_context_url('/mi-portal/economia-suficiencia/biblioteca/', 'flavor-economia-suficiencia'),
            ];
        }

        return [
            'stats' => $stats_array,
            'items' => $items,
            'empty_state' => __('Empieza tu camino de suficiencia', 'flavor-platform'),
            'footer' => [
                [
                    'label' => __('Mi camino', 'flavor-platform'),
                    'url' => $this->get_context_url('/mi-portal/economia-suficiencia/mi-camino/', 'flavor-economia-suficiencia'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
            'extra' => [
                'puntos' => $nivel['puntos'],
                'progreso_nivel' => $nivel['progreso'],
            ],
        ];
    }
}
