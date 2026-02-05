<?php
/**
 * Controlador frontend: Biblioteca
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Biblioteca
 */
class Flavor_Frontend_Biblioteca_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'biblioteca';
    protected $nombre = 'Biblioteca';
    protected $icono = '📚';
    protected $color_primario = 'indigo';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_data();
        }

        $filtros = $this->get_filters_from_url(['categoria', 'disponible', 'autor']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 16;

        $where = ["1=1"];
        if (!empty($filtros['categoria'])) {
            $where[] = $wpdb->prepare('categoria = %s', $filtros['categoria']);
        }
        if (!empty($filtros['disponible'])) {
            $where[] = "disponible = 1";
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE " . implode(' AND ', $where));
        $libros = $wpdb->get_results(
            "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) .
            " ORDER BY titulo LIMIT $per_page OFFSET " . (($pagina - 1) * $per_page)
        );

        return [
            'titulo_pagina' => $this->nombre,
            'libros' => $this->procesar_libros($libros),
            'total_libros' => intval($total),
            'categorias' => $this->get_categorias(),
            'filtros_activos' => $filtros,
            'pagination' => $this->get_pagination($total, $per_page, $pagina),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_single_data($item_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_single($item_id);
        }

        $libro = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", intval($item_id)));

        if (!$libro) {
            return null;
        }

        return [
            'titulo_pagina' => $libro->titulo,
            'libro' => $this->procesar_libro($libro),
            'libros_relacionados' => $this->get_relacionados($libro->id, $libro->categoria),
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
                'sugerencias' => ['novela', 'historia', 'cocina', 'infantil', 'ciencia'],
            ];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_biblioteca';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['resultados' => [], 'total_resultados' => 0, 'sugerencias' => []];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $libros = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE titulo LIKE %s OR autor LIKE %s OR isbn LIKE %s LIMIT 20",
            $like, $like, $like
        ));

        return [
            'resultados' => $this->procesar_libros($libros),
            'total_resultados' => count($libros),
            'sugerencias' => [],
        ];
    }

    private function procesar_libros($libros) {
        return array_map([$this, 'procesar_libro'], $libros);
    }

    private function procesar_libro($libro) {
        return [
            'id' => $libro->id,
            'titulo' => $libro->titulo,
            'autor' => $libro->autor ?? 'Desconocido',
            'isbn' => $libro->isbn ?? '',
            'categoria' => $libro->categoria ?? 'general',
            'categoria_label' => $this->get_categoria_label($libro->categoria ?? 'general'),
            'descripcion' => wp_trim_words($libro->descripcion ?? '', 30),
            'portada' => $libro->portada_url ?? null,
            'disponible' => (bool)($libro->disponible ?? true),
            'valoracion' => floatval($libro->valoracion ?? 0),
            'url' => home_url('/' . $this->slug . '/' . $libro->id . '/'),
        ];
    }

    private function get_demo_data() {
        return [
            'titulo_pagina' => $this->nombre,
            'libros' => [
                [
                    'id' => 1,
                    'titulo' => 'Cien años de soledad',
                    'autor' => 'Gabriel García Márquez',
                    'isbn' => '978-0307474728',
                    'categoria' => 'novela',
                    'categoria_label' => 'Novela',
                    'descripcion' => 'Una obra maestra del realismo mágico.',
                    'portada' => null,
                    'disponible' => true,
                    'valoracion' => 4.8,
                    'url' => home_url('/' . $this->slug . '/1/'),
                ],
            ],
            'total_libros' => 1,
            'categorias' => $this->get_categorias(),
            'filtros_activos' => [],
            'pagination' => $this->get_pagination(1, 16, 1),
        ];
    }

    private function get_demo_single($item_id) {
        return [
            'titulo_pagina' => 'Libro de ejemplo',
            'libro' => [
                'id' => $item_id,
                'titulo' => 'Libro de ejemplo',
                'autor' => 'Autor desconocido',
                'isbn' => '',
                'categoria' => 'general',
                'categoria_label' => 'General',
                'descripcion' => 'Descripción del libro.',
                'portada' => null,
                'disponible' => true,
                'valoracion' => 0,
                'url' => home_url('/' . $this->slug . '/' . $item_id . '/'),
            ],
            'libros_relacionados' => [],
        ];
    }

    private function get_categorias() {
        return [
            ['slug' => 'novela', 'nombre' => 'Novela', 'count' => 45],
            ['slug' => 'ensayo', 'nombre' => 'Ensayo', 'count' => 23],
            ['slug' => 'infantil', 'nombre' => 'Infantil', 'count' => 34],
            ['slug' => 'cocina', 'nombre' => 'Cocina', 'count' => 12],
            ['slug' => 'historia', 'nombre' => 'Historia', 'count' => 18],
            ['slug' => 'ciencia', 'nombre' => 'Ciencia', 'count' => 15],
        ];
    }

    private function get_categoria_label($categoria) {
        $map = [
            'novela' => 'Novela',
            'ensayo' => 'Ensayo',
            'infantil' => 'Infantil',
            'cocina' => 'Cocina',
            'historia' => 'Historia',
            'ciencia' => 'Ciencia',
            'general' => 'General',
        ];
        return $map[$categoria] ?? ucfirst($categoria);
    }

    private function get_relacionados($libro_id, $categoria) {
        return [];
    }

    protected function ajax_solicitar_prestamo($data) {
        if (!is_user_logged_in()) {
            return ['error' => 'Debes iniciar sesión'];
        }
        return ['success' => true, 'mensaje' => 'Préstamo solicitado'];
    }

    protected function ajax_devolver_libro($data) {
        if (!is_user_logged_in()) {
            return ['error' => 'Debes iniciar sesión'];
        }
        return ['success' => true, 'mensaje' => 'Libro devuelto'];
    }
}
