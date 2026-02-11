<?php
/**
 * Controlador frontend: Reciclaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Reciclaje
 */
class Flavor_Frontend_Reciclaje_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'reciclaje';
    protected $nombre = 'Puntos de Reciclaje';
    protected $icono = '♻️';
    protected $color_primario = 'emerald';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reciclaje_puntos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_data();
        }

        $filtros = $this->get_filters_from_url(['tipo', 'zona', 'material']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 12;

        $where = ["activo = 1"];
        if (!empty($filtros['tipo'])) {
            $where[] = $wpdb->prepare('tipo = %s', $filtros['tipo']);
        }
        if (!empty($filtros['material'])) {
            $where[] = $wpdb->prepare('FIND_IN_SET(%s, materiales_aceptados)', $filtros['material']);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE " . implode(' AND ', $where));
        $puntos = $wpdb->get_results(
            "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) .
            " ORDER BY nombre LIMIT $per_page OFFSET " . (($pagina - 1) * $per_page)
        );

        return [
            'titulo_pagina' => $this->nombre,
            'puntos' => $this->procesar_puntos($puntos),
            'total_puntos' => intval($total),
            'tipos' => $this->get_tipos(),
            'materiales' => $this->get_materiales(),
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
        $tabla = $wpdb->prefix . 'flavor_reciclaje_puntos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_single($item_id);
        }

        $punto = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", intval($item_id)));

        if (!$punto) {
            return null;
        }

        return [
            'titulo_pagina' => $punto->nombre,
            'punto' => $this->procesar_punto_detalle($punto),
            'puntos_cercanos' => $this->get_puntos_cercanos($punto->latitud, $punto->longitud, $punto->id),
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
                'sugerencias' => ['vidrio', 'papel', 'pilas', 'aceite', 'ropa'],
            ];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reciclaje_puntos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['resultados' => [], 'total_resultados' => 0, 'sugerencias' => []];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $puntos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE activo = 1 AND (nombre LIKE %s OR direccion LIKE %s OR materiales_aceptados LIKE %s) LIMIT 20",
            $like, $like, $like
        ));

        return [
            'resultados' => $this->procesar_puntos($puntos),
            'total_resultados' => count($puntos),
            'sugerencias' => [],
        ];
    }

    private function procesar_puntos($puntos) {
        return array_map([$this, 'procesar_punto'], $puntos);
    }

    private function procesar_punto($punto) {
        $materiales = explode(',', $punto->materiales_aceptados ?? '');
        return [
            'id' => $punto->id,
            'nombre' => $punto->nombre,
            'direccion' => $punto->direccion ?? '',
            'tipo' => $punto->tipo ?? 'contenedor',
            'tipo_label' => $this->get_tipo_label($punto->tipo ?? 'contenedor'),
            'latitud' => floatval($punto->latitud ?? 0),
            'longitud' => floatval($punto->longitud ?? 0),
            'materiales' => $materiales,
            'materiales_labels' => array_map([$this, 'get_material_label'], $materiales),
            'horario' => $punto->horario ?? '24 horas',
            'imagen' => $punto->imagen_url ?? null,
            'url' => home_url('/' . $this->slug . '/' . $punto->id . '/'),
        ];
    }

    private function procesar_punto_detalle($punto) {
        $base = $this->procesar_punto($punto);
        $base['descripcion'] = $punto->descripcion ?? '';
        $base['instrucciones'] = $punto->instrucciones ?? '';
        $base['telefono'] = $punto->telefono ?? '';
        $base['servicios'] = json_decode($punto->servicios ?? '[]', true) ?: [];
        return $base;
    }

    private function get_demo_data() {
        return [
            'titulo_pagina' => $this->nombre,
            'puntos' => [
                [
                    'id' => 1,
                    'nombre' => 'Punto Limpio Municipal',
                    'direccion' => 'Polígono Industrial, Parcela 5',
                    'tipo' => 'punto_limpio',
                    'tipo_label' => 'Punto Limpio',
                    'latitud' => 43.3183,
                    'longitud' => -1.9812,
                    'materiales' => ['electronica', 'aceite', 'pilas', 'voluminosos'],
                    'materiales_labels' => ['Electrónica', 'Aceite', 'Pilas', 'Voluminosos'],
                    'horario' => 'L-S 9:00-20:00',
                    'imagen' => null,
                    'url' => home_url('/' . $this->slug . '/1/'),
                ],
            ],
            'total_puntos' => 1,
            'tipos' => $this->get_tipos(),
            'materiales' => $this->get_materiales(),
            'estadisticas' => ['total_puntos' => 1, 'kg_reciclados_mes' => 5420],
            'filtros_activos' => [],
            'pagination' => $this->get_pagination(1, 12, 1),
        ];
    }

    private function get_demo_single($item_id) {
        return [
            'titulo_pagina' => 'Punto de reciclaje',
            'punto' => [
                'id' => $item_id,
                'nombre' => 'Punto de ejemplo',
                'direccion' => '',
                'tipo' => 'contenedor',
                'tipo_label' => 'Contenedor',
                'latitud' => 0,
                'longitud' => 0,
                'materiales' => [],
                'materiales_labels' => [],
                'horario' => '24 horas',
                'imagen' => null,
                'descripcion' => '',
                'instrucciones' => '',
                'telefono' => '',
                'servicios' => [],
                'url' => home_url('/' . $this->slug . '/' . $item_id . '/'),
            ],
            'puntos_cercanos' => [],
        ];
    }

    private function get_tipos() {
        return [
            ['slug' => 'contenedor', 'nombre' => 'Contenedor', 'icono' => '🗑️'],
            ['slug' => 'punto_limpio', 'nombre' => 'Punto Limpio', 'icono' => '🏭'],
            ['slug' => 'iglú', 'nombre' => 'Iglú de vidrio', 'icono' => '🫙'],
            ['slug' => 'ropa', 'nombre' => 'Contenedor de ropa', 'icono' => '👕'],
            ['slug' => 'aceite', 'nombre' => 'Contenedor de aceite', 'icono' => '🛢️'],
        ];
    }

    private function get_tipo_label($tipo) {
        foreach ($this->get_tipos() as $t) {
            if ($t['slug'] === $tipo) return $t['nombre'];
        }
        return ucfirst($tipo);
    }

    private function get_materiales() {
        return [
            ['slug' => 'papel', 'nombre' => 'Papel y cartón', 'color' => 'blue'],
            ['slug' => 'plastico', 'nombre' => 'Plástico y envases', 'color' => 'yellow'],
            ['slug' => 'vidrio', 'nombre' => 'Vidrio', 'color' => 'green'],
            ['slug' => 'organico', 'nombre' => 'Orgánico', 'color' => 'brown'],
            ['slug' => 'pilas', 'nombre' => 'Pilas y baterías', 'color' => 'red'],
            ['slug' => 'electronica', 'nombre' => 'Electrónica', 'color' => 'gray'],
            ['slug' => 'aceite', 'nombre' => 'Aceite usado', 'color' => 'orange'],
            ['slug' => 'ropa', 'nombre' => 'Ropa y textil', 'color' => 'purple'],
            ['slug' => 'voluminosos', 'nombre' => 'Voluminosos', 'color' => 'gray'],
        ];
    }

    private function get_material_label($material) {
        foreach ($this->get_materiales() as $m) {
            if ($m['slug'] === trim($material)) return $m['nombre'];
        }
        return ucfirst($material);
    }

    private function get_estadisticas() {
        return ['total_puntos' => 0, 'kg_reciclados_mes' => 0];
    }

    private function get_puntos_cercanos($lat, $lng, $exclude_id) {
        return [];
    }

    protected function ajax_reportar_incidencia($data) {
        if (!is_user_logged_in()) {
            return ['error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }
        return ['success' => true, 'mensaje' => __('Incidencia reportada', 'flavor-chat-ia')];
    }
}
