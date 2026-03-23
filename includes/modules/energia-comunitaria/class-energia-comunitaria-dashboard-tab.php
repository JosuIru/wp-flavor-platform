<?php
/**
 * Dashboard tab para Energia Comunitaria
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Energia_Comunitaria_Dashboard_Tab {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public function registrar_tabs($tabs) {
        $tabs['energia-comunitaria-resumen'] = [
            'label' => __('Energia', 'flavor-chat-ia'),
            'icon' => 'lightbulb',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 67,
        ];

        $tabs['energia-comunitaria-mantenimiento'] = [
            'label' => __('Mantenimiento Energia', 'flavor-chat-ia'),
            'icon' => 'admin-tools',
            'callback' => [$this, 'render_tab_mantenimiento'],
            'orden' => 68,
        ];

        return $tabs;
    }

    public function render_tab_resumen() {
        echo '<div class="flavor-panel-actions" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px;">';
        echo '<a class="flavor-btn flavor-btn-primary" href="' . esc_url(Flavor_Chat_Helpers::get_action_url('energia_comunitaria', '')) . '">' . esc_html__('Abrir módulo', 'flavor-chat-ia') . '</a>';
        echo '<a class="flavor-btn flavor-btn-secondary" href="' . esc_url(Flavor_Chat_Helpers::get_action_url('energia_comunitaria', 'registrar-lectura')) . '">' . esc_html__('Registrar lectura', 'flavor-chat-ia') . '</a>';
        echo '<a class="flavor-btn flavor-btn-outline" href="' . esc_url(Flavor_Chat_Helpers::get_action_url('energia_comunitaria', 'mantenimiento')) . '">' . esc_html__('Ver mantenimiento', 'flavor-chat-ia') . '</a>';
        echo '</div>';
        echo do_shortcode('[flavor_energia_dashboard]');
    }

    public function render_tab_mantenimiento() {
        echo do_shortcode('[flavor_energia_mantenimiento]');
    }
}
