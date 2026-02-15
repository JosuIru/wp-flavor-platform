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
    private $module;

    /**
     * Constructor
     *
     * @param Flavor_Chat_Economia_Suficiencia_Module $module
     */
    public function __construct($module) {
        $this->module = $module;

        $this->widget_id = 'economia-suficiencia';
        $this->title = __('Economía de Suficiencia', 'flavor-chat-ia');
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
                'empty_state' => __('Inicia sesión para ver tu camino de suficiencia', 'flavor-chat-ia'),
            ];
        }

        $stats = $this->module->get_estadisticas_usuario($user_id);
        $nivel = $stats['nivel'];

        $iconos_nivel = ['explorando' => '🌱', 'consciente' => '🌿', 'practicante' => '🌳', 'mentor' => '🌲', 'sabio' => '🏔️'];

        $stats_array = [
            [
                'icon' => 'dashicons-awards',
                'valor' => $nivel['nivel']['nombre'],
                'label' => __('Nivel', 'flavor-chat-ia'),
                'color' => 'green',
                'extra' => $iconos_nivel[$nivel['nivel']['id']] ?? '🌱',
            ],
            [
                'icon' => 'dashicons-yes-alt',
                'valor' => $stats['compromisos_activos'],
                'label' => __('Compromisos', 'flavor-chat-ia'),
                'color' => 'blue',
            ],
        ];

        $items = [];

        if ($stats['compromisos_activos'] > 0) {
            $items[] = [
                'icon' => 'dashicons-calendar-alt',
                'title' => sprintf(
                    _n('%d práctica este mes', '%d prácticas este mes', $stats['practicas_mes'], 'flavor-chat-ia'),
                    $stats['practicas_mes']
                ),
                'url' => home_url('/mi-portal/economia-suficiencia/compromisos/'),
            ];
        }

        if ($stats['recursos_compartidos'] > 0) {
            $items[] = [
                'icon' => 'dashicons-share',
                'title' => sprintf(
                    _n('%d objeto compartido', '%d objetos compartidos', $stats['recursos_compartidos'], 'flavor-chat-ia'),
                    $stats['recursos_compartidos']
                ),
                'url' => home_url('/mi-portal/economia-suficiencia/biblioteca/'),
            ];
        }

        return [
            'stats' => $stats_array,
            'items' => $items,
            'empty_state' => __('Empieza tu camino de suficiencia', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Mi camino', 'flavor-chat-ia'),
                    'url' => home_url('/mi-portal/economia-suficiencia/mi-camino/'),
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
