<?php
/**
 * Controlador frontend: Huertos Urbanos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Huertos Urbanos
 */
class Flavor_Frontend_Huertos_Urbanos_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'huertos-urbanos';
    protected $nombre = 'Huertos Urbanos';
    protected $icono = '🌱';
    protected $color_primario = 'green';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_huertos';

        // Verificar si la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_data();
        }

        $filtros = $this->get_filters_from_url(['estado', 'tipo', 'zona']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 12;

        $where = ["1=1"];
        if (!empty($filtros['estado'])) {
            $where[] = $wpdb->prepare('estado = %s', $filtros['estado']);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE " . implode(' AND ', $where));
        $huertos = $wpdb->get_results(
            "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) .
            " ORDER BY nombre LIMIT $per_page OFFSET " . (($pagina - 1) * $per_page)
        );

        return [
            'titulo_pagina' => $this->nombre,
            'huertos' => $this->procesar_huertos($huertos),
            'total_huertos' => intval($total),
            'zonas' => $this->get_zonas(),
            'filtros_activos' => $filtros,
            'estadisticas' => $this->get_estadisticas(),
            'pagination' => $this->get_pagination($total, $per_page, $pagina),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_single_data($item_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_huertos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_single($item_id);
        }

        $huerto = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", intval($item_id)));

        if (!$huerto) {
            return null;
        }

        return [
            'titulo_pagina' => $huerto->nombre,
            'huerto' => $this->procesar_huerto($huerto),
            'parcelas' => $this->get_parcelas($huerto->id),
            'actividades' => $this->get_actividades($huerto->id),
            'galeria' => json_decode($huerto->fotos ?? '[]', true) ?: [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_search_data($query) {
        if (empty($query)) {
            return [
                'resultados' => [],
                'total_resultados' => 0,
                'sugerencias' => ['parcela disponible', 'compostaje', 'taller', 'riego'],
            ];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_huertos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['resultados' => [], 'total_resultados' => 0, 'sugerencias' => []];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $huertos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE nombre LIKE %s OR descripcion LIKE %s LIMIT 20",
            $like, $like
        ));

        return [
            'resultados' => $this->procesar_huertos($huertos),
            'total_resultados' => count($huertos),
            'sugerencias' => [],
        ];
    }

    private function procesar_huertos($huertos) {
        return array_map([$this, 'procesar_huerto'], $huertos);
    }

    private function procesar_huerto($huerto) {
        $fotos = json_decode($huerto->fotos ?? '[]', true) ?: [];
        return [
            'id' => $huerto->id,
            'nombre' => $huerto->nombre,
            'descripcion' => wp_trim_words($huerto->descripcion ?? '', 25),
            'ubicacion' => $huerto->ubicacion ?? '',
            'estado' => $huerto->estado ?? 'activo',
            'total_parcelas' => $huerto->total_parcelas ?? 0,
            'parcelas_disponibles' => $huerto->parcelas_disponibles ?? 0,
            'superficie' => $huerto->superficie_m2 ?? 0,
            'imagen' => !empty($fotos) ? $fotos[0] : null,
            'url' => home_url('/' . $this->slug . '/' . $huerto->id . '/'),
        ];
    }

    private function get_demo_data() {
        return [
            'titulo_pagina' => $this->nombre,
            'huertos' => [
                [
                    'id' => 1,
                    'nombre' => 'Huerto Comunitario Norte',
                    'descripcion' => 'Huerto comunitario con 20 parcelas disponibles para vecinos del barrio.',
                    'ubicacion' => 'Parque Norte, junto al centro cívico',
                    'estado' => 'activo',
                    'total_parcelas' => 20,
                    'parcelas_disponibles' => 3,
                    'superficie' => 500,
                    'imagen' => null,
                    'url' => home_url('/' . $this->slug . '/1/'),
                ],
            ],
            'total_huertos' => 1,
            'zonas' => [],
            'filtros_activos' => [],
            'estadisticas' => ['total_huertos' => 1, 'parcelas_activas' => 17, 'horticultores' => 17],
            'pagination' => $this->get_pagination(1, 12, 1),
        ];
    }

    private function get_demo_single($item_id) {
        return [
            'titulo_pagina' => 'Huerto Comunitario',
            'huerto' => [
                'id' => $item_id,
                'nombre' => 'Huerto Comunitario Norte',
                'descripcion' => 'Huerto comunitario con parcelas disponibles.',
                'ubicacion' => 'Parque Norte',
                'estado' => 'activo',
                'total_parcelas' => 20,
                'parcelas_disponibles' => 3,
                'superficie' => 500,
                'imagen' => null,
                'url' => home_url('/' . $this->slug . '/' . $item_id . '/'),
            ],
            'parcelas' => [],
            'actividades' => [],
            'galeria' => [],
        ];
    }

    private function get_zonas() {
        return [];
    }

    private function get_estadisticas() {
        return ['total_huertos' => 0, 'parcelas_activas' => 0, 'horticultores' => 0];
    }

    private function get_parcelas($huerto_id) {
        return [];
    }

    private function get_actividades($huerto_id) {
        return [];
    }

    protected function ajax_solicitar_parcela($data) {
        if (!is_user_logged_in()) {
            return ['error' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }
        return ['success' => true, 'mensaje' => __('Solicitud enviada', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }
}
