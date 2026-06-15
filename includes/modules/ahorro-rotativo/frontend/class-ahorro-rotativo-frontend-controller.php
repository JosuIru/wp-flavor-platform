<?php
/**
 * Frontend Controller para Ahorro Rotativo.
 *
 * Maneja los envíos de formularios web (crear/activar/invitar/pagar/confirmar)
 * delegándolos al módulo, y añade la pestaña del dashboard de usuario.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Ahorro_Rotativo_Frontend_Controller {

    private static $instance = null;
    private $module = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Manejo de formularios web (usuario autenticado).
        add_action('admin_post_flavor_ar_crear_circulo', [$this, 'handle_crear_circulo']);
        add_action('admin_post_flavor_ar_activar_circulo', [$this, 'handle_activar_circulo']);
        add_action('admin_post_flavor_ar_invitar_miembro', [$this, 'handle_invitar_miembro']);
        add_action('admin_post_flavor_ar_pagar', [$this, 'handle_pagar']);
        add_action('admin_post_flavor_ar_confirmar', [$this, 'handle_confirmar']);

        // Pestaña en el dashboard de usuario.
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tab'], 45, 1);
    }

    private function module() {
        if ($this->module === null) {
            $this->module = new Flavor_Platform_Ahorro_Rotativo_Module();
        }
        return $this->module;
    }

    public function registrar_tab($tabs) {
        $tabs['ahorro-rotativo'] = [
            'titulo' => __('Ahorro Rotativo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-update',
            'shortcode' => 'ahorro_rotativo_circulos',
        ];
        return $tabs;
    }

    // ---------------------- Handlers de formularios ----------------------

    private function verificar($accion) {
        if (!is_user_logged_in()) {
            wp_die(esc_html__('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }
        check_admin_referer($accion);
    }

    private function volver($circulo_id = 0) {
        $url = wp_get_referer() ?: home_url('/');
        if ($circulo_id) {
            $url = add_query_arg('circulo', absint($circulo_id), $url);
        }
        wp_safe_redirect($url);
        exit;
    }

    public function handle_crear_circulo() {
        $this->verificar('flavor_ar_crear_circulo');
        $resultado = $this->module()->execute_action('crear_circulo', [
            'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
            'importe_aportacion' => floatval($_POST['importe_aportacion'] ?? 0),
            'num_plazas' => absint($_POST['num_plazas'] ?? 0),
            'periodo_dias' => absint($_POST['periodo_dias'] ?? 30),
            'modo_orden' => sanitize_text_field($_POST['modo_orden'] ?? 'fijo'),
        ]);
        $this->volver($resultado['circulo_id'] ?? 0);
    }

    public function handle_activar_circulo() {
        $this->verificar('flavor_ar_activar_circulo');
        $circulo_id = absint($_POST['circulo_id'] ?? 0);
        $this->module()->execute_action('activar_circulo', ['circulo_id' => $circulo_id]);
        $this->volver($circulo_id);
    }

    public function handle_invitar_miembro() {
        $this->verificar('flavor_ar_invitar_miembro');
        $circulo_id = absint($_POST['circulo_id'] ?? 0);
        $this->module()->execute_action('invitar_miembro', [
            'circulo_id' => $circulo_id,
            'nombre_visible' => sanitize_text_field($_POST['nombre_visible'] ?? ''),
            'usuario_id' => absint($_POST['usuario_id'] ?? 0) ?: null,
        ]);
        $this->volver($circulo_id);
    }

    public function handle_pagar() {
        $this->verificar('flavor_ar_pagar');
        $this->module()->execute_action('marcar_pagada', ['aportacion_id' => absint($_POST['aportacion_id'] ?? 0)]);
        $this->volver(absint($_POST['circulo_id'] ?? 0));
    }

    public function handle_confirmar() {
        $this->verificar('flavor_ar_confirmar');
        $this->module()->execute_action('confirmar_recepcion', ['aportacion_id' => absint($_POST['aportacion_id'] ?? 0)]);
        $this->volver(absint($_POST['circulo_id'] ?? 0));
    }
}
