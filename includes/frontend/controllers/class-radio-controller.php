<?php
/**
 * Controlador frontend: Radio
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Radio
 */
class Flavor_Frontend_Radio_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'radio';
    protected $nombre = 'Radio Comunitaria';
    protected $icono = '📻';
    protected $color_primario = 'red';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_programas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_data();
        }

        $filtros = $this->get_filters_from_url(['categoria', 'dia']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 12;

        $where = ["activo = 1"];
        if (!empty($filtros['categoria'])) {
            $where[] = $wpdb->prepare('categoria = %s', $filtros['categoria']);
        }
        if (!empty($filtros['dia'])) {
            $where[] = $wpdb->prepare('FIND_IN_SET(%s, dias_emision)', $filtros['dia']);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE " . implode(' AND ', $where));
        $programas = $wpdb->get_results(
            "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) .
            " ORDER BY hora_inicio LIMIT $per_page OFFSET " . (($pagina - 1) * $per_page)
        );

        return [
            'titulo_pagina' => $this->nombre,
            'programas' => $this->procesar_programas($programas),
            'total_programas' => intval($total),
            'categorias' => $this->get_categorias(),
            'parrilla' => $this->get_parrilla_hoy(),
            'en_directo' => $this->get_programa_actual(),
            'stream_url' => $this->get_stream_url(),
            'filtros_activos' => $filtros,
            'pagination' => $this->get_pagination($total, $per_page, $pagina),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_single_data($item_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_programas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_single($item_id);
        }

        $programa = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            intval($item_id)
        ));

        if (!$programa) {
            return null;
        }

        return [
            'titulo_pagina' => $programa->nombre,
            'programa' => $this->procesar_programa_detalle($programa),
            'episodios' => $this->get_episodios($programa->id),
            'stream_url' => $this->get_stream_url(),
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
                'sugerencias' => ['música', 'noticias', 'entrevistas', 'deportes', 'cultura'],
            ];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_programas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return ['resultados' => [], 'total_resultados' => 0, 'sugerencias' => []];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $programas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE activo = 1 AND (nombre LIKE %s OR descripcion LIKE %s) LIMIT 20",
            $like, $like
        ));

        return [
            'resultados' => $this->procesar_programas($programas),
            'total_resultados' => count($programas),
            'sugerencias' => [],
        ];
    }

    private function procesar_programas($programas) {
        return array_map([$this, 'procesar_programa'], $programas);
    }

    private function procesar_programa($programa) {
        return [
            'id' => $programa->id,
            'nombre' => $programa->nombre,
            'descripcion' => wp_trim_words($programa->descripcion ?? '', 25),
            'categoria' => $programa->categoria ?? 'general',
            'categoria_label' => $this->get_categoria_label($programa->categoria ?? 'general'),
            'horario' => $this->format_horario($programa),
            'dias' => explode(',', $programa->dias_emision ?? ''),
            'dias_label' => $this->format_dias($programa->dias_emision ?? ''),
            'presentador' => $programa->presentador ?? '',
            'imagen' => $programa->imagen_url ?? null,
            'url' => home_url('/' . $this->slug . '/' . $programa->id . '/'),
        ];
    }

    private function procesar_programa_detalle($programa) {
        $base = $this->procesar_programa($programa);
        $base['descripcion_completa'] = $programa->descripcion ?? '';
        $base['equipo'] = json_decode($programa->equipo ?? '[]', true) ?: [];
        $base['redes_sociales'] = json_decode($programa->redes_sociales ?? '[]', true) ?: [];
        return $base;
    }

    private function get_demo_data() {
        return [
            'titulo_pagina' => $this->nombre,
            'programas' => [
                [
                    'id' => 1,
                    'nombre' => 'Buenos Días Vecinos',
                    'descripcion' => 'El programa matinal con las noticias del barrio y música para empezar el día.',
                    'categoria' => 'magazine',
                    'categoria_label' => 'Magazine',
                    'horario' => '08:00 - 10:00',
                    'dias' => ['L', 'M', 'X', 'J', 'V'],
                    'dias_label' => 'Lunes a Viernes',
                    'presentador' => 'Ana Martínez',
                    'imagen' => null,
                    'url' => home_url('/' . $this->slug . '/1/'),
                ],
            ],
            'total_programas' => 1,
            'categorias' => $this->get_categorias(),
            'parrilla' => [],
            'en_directo' => null,
            'stream_url' => '',
            'filtros_activos' => [],
            'pagination' => $this->get_pagination(1, 12, 1),
        ];
    }

    private function get_demo_single($item_id) {
        return [
            'titulo_pagina' => 'Programa de radio',
            'programa' => [
                'id' => $item_id,
                'nombre' => 'Programa de ejemplo',
                'descripcion' => 'Descripción del programa.',
                'descripcion_completa' => 'Descripción completa del programa.',
                'categoria' => 'general',
                'categoria_label' => 'General',
                'horario' => '',
                'dias' => [],
                'dias_label' => '',
                'presentador' => '',
                'imagen' => null,
                'equipo' => [],
                'redes_sociales' => [],
                'url' => home_url('/' . $this->slug . '/' . $item_id . '/'),
            ],
            'episodios' => [],
            'stream_url' => '',
        ];
    }

    private function get_categorias() {
        return [
            ['slug' => 'magazine', 'nombre' => 'Magazine', 'count' => 5],
            ['slug' => 'musical', 'nombre' => 'Musical', 'count' => 8],
            ['slug' => 'informativo', 'nombre' => 'Informativo', 'count' => 3],
            ['slug' => 'cultural', 'nombre' => 'Cultural', 'count' => 6],
            ['slug' => 'deportivo', 'nombre' => 'Deportivo', 'count' => 2],
        ];
    }

    private function get_categoria_label($categoria) {
        foreach ($this->get_categorias() as $cat) {
            if ($cat['slug'] === $categoria) return $cat['nombre'];
        }
        return ucfirst($categoria);
    }

    private function format_horario($programa) {
        $inicio = $programa->hora_inicio ?? '00:00';
        $fin = $programa->hora_fin ?? '00:00';
        return substr($inicio, 0, 5) . ' - ' . substr($fin, 0, 5);
    }

    private function format_dias($dias_string) {
        $dias_map = ['L' => 'Lunes', 'M' => 'Martes', 'X' => 'Miércoles', 'J' => 'Jueves', 'V' => 'Viernes', 'S' => 'Sábado', 'D' => 'Domingo'];
        $dias = explode(',', $dias_string);

        if (count($dias) === 7) return 'Todos los días';
        if (count($dias) === 5 && !in_array('S', $dias) && !in_array('D', $dias)) return 'Lunes a Viernes';
        if (count($dias) === 2 && in_array('S', $dias) && in_array('D', $dias)) return 'Fines de semana';

        return implode(', ', array_map(fn($d) => $dias_map[$d] ?? $d, $dias));
    }

    private function get_parrilla_hoy() {
        return [];
    }

    private function get_programa_actual() {
        return null;
    }

    private function get_stream_url() {
        return get_option('flavor_radio_stream_url', '');
    }

    private function get_episodios($programa_id) {
        return [];
    }
}
