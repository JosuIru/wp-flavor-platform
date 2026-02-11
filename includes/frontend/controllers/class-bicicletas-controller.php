<?php
/**
 * Controlador frontend: Bicicletas Compartidas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Bicicletas Compartidas
 */
class Flavor_Frontend_Bicicletas_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'bicicletas';
    protected $nombre = 'Bicicletas Compartidas';
    protected $icono = '🚲';
    protected $color_primario = 'lime';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_data();
        }

        $filtros = $this->get_filters_from_url(['zona', 'disponibilidad']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 12;

        $where = ["activa = 1"];
        if (!empty($filtros['zona'])) {
            $where[] = $wpdb->prepare('zona = %s', $filtros['zona']);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE " . implode(' AND ', $where));
        $estaciones = $wpdb->get_results(
            "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) .
            " ORDER BY nombre LIMIT $per_page OFFSET " . (($pagina - 1) * $per_page)
        );

        return [
            'titulo_pagina' => $this->nombre,
            'estaciones' => $this->procesar_estaciones($estaciones),
            'total_estaciones' => intval($total),
            'zonas' => $this->get_zonas(),
            'estadisticas' => $this->get_estadisticas(),
            'filtros_activos' => $filtros,
            'pagination' => $this->get_pagination($total, $per_page, $pagina),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_single_data($item_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_single($item_id);
        }

        $estacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            intval($item_id)
        ));

        if (!$estacion) {
            return null;
        }

        return [
            'titulo_pagina' => $estacion->nombre,
            'estacion' => $this->procesar_estacion_detalle($estacion),
            'bicicletas' => $this->get_bicicletas_disponibles($estacion->id),
            'estaciones_cercanas' => $this->get_estaciones_cercanas($estacion->latitud, $estacion->longitud, $estacion->id),
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
                'sugerencias' => ['centro', 'parque', 'estación tren', 'mercado'],
            ];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_bicicletas_estaciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['resultados' => [], 'total_resultados' => 0, 'sugerencias' => []];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $estaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE activa = 1 AND (nombre LIKE %s OR direccion LIKE %s) LIMIT 20",
            $like, $like
        ));

        return [
            'resultados' => $this->procesar_estaciones($estaciones),
            'total_resultados' => count($estaciones),
            'sugerencias' => [],
        ];
    }

    private function procesar_estaciones($estaciones) {
        return array_map([$this, 'procesar_estacion'], $estaciones);
    }

    private function procesar_estacion($estacion) {
        return [
            'id' => $estacion->id,
            'nombre' => $estacion->nombre,
            'direccion' => $estacion->direccion ?? '',
            'zona' => $estacion->zona ?? '',
            'latitud' => floatval($estacion->latitud ?? 0),
            'longitud' => floatval($estacion->longitud ?? 0),
            'capacidad' => intval($estacion->capacidad ?? 0),
            'bicis_disponibles' => intval($estacion->bicis_disponibles ?? 0),
            'anclajes_libres' => intval($estacion->anclajes_libres ?? 0),
            'estado' => $this->get_estado_estacion($estacion),
            'imagen' => $estacion->imagen_url ?? null,
            'url' => home_url('/' . $this->slug . '/' . $estacion->id . '/'),
        ];
    }

    private function procesar_estacion_detalle($estacion) {
        $base = $this->procesar_estacion($estacion);
        $base['horario'] = $estacion->horario ?? '24 horas';
        $base['servicios'] = json_decode($estacion->servicios ?? '[]', true) ?: [];
        $base['instrucciones'] = $estacion->instrucciones ?? '';
        return $base;
    }

    private function get_estado_estacion($estacion) {
        $bicis = intval($estacion->bicis_disponibles ?? 0);
        $anclajes = intval($estacion->anclajes_libres ?? 0);

        if ($bicis === 0 && $anclajes === 0) return ['codigo' => 'cerrada', 'label' => 'Cerrada', 'color' => 'gray'];
        if ($bicis === 0) return ['codigo' => 'sin_bicis', 'label' => 'Sin bicicletas', 'color' => 'red'];
        if ($anclajes === 0) return ['codigo' => 'llena', 'label' => 'Estación llena', 'color' => 'yellow'];
        if ($bicis <= 2) return ['codigo' => 'pocas_bicis', 'label' => 'Pocas bicis', 'color' => 'orange'];
        return ['codigo' => 'disponible', 'label' => 'Disponible', 'color' => 'green'];
    }

    private function get_demo_data() {
        return [
            'titulo_pagina' => $this->nombre,
            'estaciones' => [
                [
                    'id' => 1,
                    'nombre' => 'Estación Plaza Mayor',
                    'direccion' => 'Plaza Mayor, 1',
                    'zona' => 'centro',
                    'latitud' => 43.3183,
                    'longitud' => -1.9812,
                    'capacidad' => 20,
                    'bicis_disponibles' => 8,
                    'anclajes_libres' => 12,
                    'estado' => ['codigo' => 'disponible', 'label' => 'Disponible', 'color' => 'green'],
                    'imagen' => null,
                    'url' => home_url('/' . $this->slug . '/1/'),
                ],
            ],
            'total_estaciones' => 1,
            'zonas' => $this->get_zonas(),
            'estadisticas' => ['total_estaciones' => 1, 'total_bicis' => 8, 'bicis_uso' => 2],
            'filtros_activos' => [],
            'pagination' => $this->get_pagination(1, 12, 1),
        ];
    }

    private function get_demo_single($item_id) {
        return [
            'titulo_pagina' => 'Estación de bicicletas',
            'estacion' => [
                'id' => $item_id,
                'nombre' => 'Estación de ejemplo',
                'direccion' => '',
                'zona' => '',
                'latitud' => 0,
                'longitud' => 0,
                'capacidad' => 0,
                'bicis_disponibles' => 0,
                'anclajes_libres' => 0,
                'estado' => ['codigo' => 'cerrada', 'label' => 'Cerrada', 'color' => 'gray'],
                'imagen' => null,
                'horario' => '24 horas',
                'servicios' => [],
                'instrucciones' => '',
                'url' => home_url('/' . $this->slug . '/' . $item_id . '/'),
            ],
            'bicicletas' => [],
            'estaciones_cercanas' => [],
        ];
    }

    private function get_zonas() {
        return [
            ['slug' => 'centro', 'nombre' => 'Centro'],
            ['slug' => 'norte', 'nombre' => 'Zona Norte'],
            ['slug' => 'sur', 'nombre' => 'Zona Sur'],
            ['slug' => 'este', 'nombre' => 'Zona Este'],
            ['slug' => 'oeste', 'nombre' => 'Zona Oeste'],
        ];
    }

    private function get_estadisticas() {
        return ['total_estaciones' => 0, 'total_bicis' => 0, 'bicis_uso' => 0];
    }

    private function get_bicicletas_disponibles($estacion_id) {
        return [];
    }

    private function get_estaciones_cercanas($lat, $lng, $exclude_id) {
        return [];
    }

    protected function ajax_reservar_bici($data) {
        if (!is_user_logged_in()) {
            return ['error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }
        return ['success' => true, 'mensaje' => __('Bicicleta reservada por 15 minutos', 'flavor-chat-ia')];
    }

    protected function ajax_devolver_bici($data) {
        if (!is_user_logged_in()) {
            return ['error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }
        return ['success' => true, 'mensaje' => __('Bicicleta devuelta correctamente', 'flavor-chat-ia')];
    }
}
