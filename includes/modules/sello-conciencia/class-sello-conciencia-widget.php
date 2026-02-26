<?php
/**
 * Widget de Dashboard para Sello de Conciencia
 *
 * Muestra el nivel de conciencia de la aplicación en el dashboard unificado.
 *
 * @package FlavorChatIA
 * @subpackage Modules\SelloConciencia
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Flavor_Dashboard_Widget_Base')) {
    require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
}

/**
 * Widget de Dashboard para Sello de Conciencia
 *
 * @since 4.2.0
 */
class Flavor_Sello_Conciencia_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * Referencia al módulo
     *
     * @var Flavor_Chat_Sello_Conciencia_Module
     */
    private $modulo;

    /**
     * Constructor
     *
     * @param Flavor_Chat_Sello_Conciencia_Module $modulo Instancia del módulo
     */
    public function __construct(Flavor_Chat_Sello_Conciencia_Module $modulo) {
        $this->modulo = $modulo;

        parent::__construct([
            'id' => 'sello-conciencia',
            'title' => __('Sello de Conciencia', 'flavor-chat-ia'),
            'icon' => 'dashicons-heart',
            'size' => 'medium',
            'category' => 'sistema',
            'priority' => 5,
            'refreshable' => true,
            'cache_time' => 300,
            'description' => __('Nivel de conciencia de la aplicación', 'flavor-chat-ia'),
        ]);
    }

    /**
     * Obtiene los datos del widget
     *
     * @return array
     */
    public function get_widget_data(): array {
        return $this->get_cached_data(function() {
            return $this->fetch_widget_data();
        });
    }

    /**
     * Obtiene los datos frescos del widget
     *
     * @return array
     */
    private function fetch_widget_data(): array {
        $evaluacion = $this->modulo->evaluar();
        $nivel = $evaluacion['nivel'];
        $premisas = $this->modulo->get_premisas();

        // Estadísticas principales
        $stats = [
            [
                'icon' => $nivel['icono'],
                'valor' => $nivel['nombre'],
                'label' => __('Nivel actual', 'flavor-chat-ia'),
                'color' => $this->map_color($nivel['color']),
            ],
            [
                'icon' => 'dashicons-chart-bar',
                'valor' => $evaluacion['puntuacion_global'] . '/100',
                'label' => __('Puntuación', 'flavor-chat-ia'),
                'color' => $this->map_color($nivel['color']),
            ],
            [
                'icon' => 'dashicons-admin-plugins',
                'valor' => $evaluacion['num_modulos'],
                'label' => __('Módulos', 'flavor-chat-ia'),
                'color' => 'primary',
            ],
        ];

        // Items: puntuación por premisa
        $items = [];
        foreach ($premisas as $premisa_id => $premisa) {
            $puntuacion = $evaluacion['puntuaciones_premisas'][$premisa_id] ?? 0;
            $items[] = [
                'icon' => $premisa['icono'],
                'title' => $premisa['nombre'],
                'meta' => $puntuacion . '/100',
                'badge' => $this->get_badge_nivel($puntuacion),
                'badge_color' => $this->get_badge_color($puntuacion),
            ];
        }

        return [
            'stats' => $stats,
            'items' => $items,
            'empty_state' => __('Activa módulos para ver tu nivel de conciencia', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver detalles', 'flavor-chat-ia'),
                    'url' => $this->get_context_url('/mi-portal/sello-conciencia/', 'sello-conciencia'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
            'extra' => [
                'nivel_id' => $nivel['id'],
                'nivel_color' => $nivel['color'],
                'descripcion' => $nivel['descripcion'],
            ],
        ];
    }

    /**
     * Mapea color hex a nombre de color del sistema
     *
     * @param string $hex Color hexadecimal
     * @return string Nombre de color
     */
    private function map_color(string $hex): string {
        $map = [
            '#95a5a6' => 'gray',
            '#e74c3c' => 'danger',
            '#f39c12' => 'warning',
            '#27ae60' => 'success',
            '#9b59b6' => 'primary',
            '#3498db' => 'info',
        ];

        return $map[$hex] ?? 'primary';
    }

    /**
     * Obtiene el texto del badge según puntuación
     *
     * @param int $puntuacion Puntuación
     * @return string Texto del badge
     */
    private function get_badge_nivel(int $puntuacion): string {
        if ($puntuacion >= 76) {
            return __('Excelente', 'flavor-chat-ia');
        }
        if ($puntuacion >= 51) {
            return __('Bueno', 'flavor-chat-ia');
        }
        if ($puntuacion >= 26) {
            return __('Regular', 'flavor-chat-ia');
        }
        return __('Bajo', 'flavor-chat-ia');
    }

    /**
     * Obtiene el color del badge según puntuación
     *
     * @param int $puntuacion Puntuación
     * @return string Color del badge
     */
    private function get_badge_color(int $puntuacion): string {
        if ($puntuacion >= 76) {
            return 'success';
        }
        if ($puntuacion >= 51) {
            return 'info';
        }
        if ($puntuacion >= 26) {
            return 'warning';
        }
        return 'danger';
    }

    /**
     * Renderiza el contenido del widget
     *
     * @return void
     */
    public function render_widget(): void {
        $data = $this->get_widget_data();
        $nivel_color = $data['extra']['nivel_color'] ?? '#9b59b6';

        // Header especial con el sello visual
        ?>
        <div class="fsc-seal-header" style="--seal-color: <?php echo esc_attr($nivel_color); ?>">
            <div class="fsc-seal-circle">
                <span class="fsc-seal-score"><?php echo esc_html($data['stats'][1]['valor']); ?></span>
            </div>
            <div class="fsc-seal-info">
                <span class="fsc-seal-level"><?php echo esc_html($data['stats'][0]['valor']); ?></span>
                <span class="fsc-seal-desc"><?php echo esc_html($data['extra']['descripcion'] ?? ''); ?></span>
            </div>
        </div>
        <?php

        // Renderizar el resto con el método estándar
        $this->render_widget_content([
            'stats' => [],
            'items' => $data['items'],
            'empty_state' => $data['empty_state'],
            'footer' => $data['footer'],
        ]);
    }
}
