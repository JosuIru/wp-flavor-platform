<?php
/**
 * Controlador frontend: Cursos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Cursos
 */
class Flavor_Frontend_Cursos_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'cursos';
    protected $nombre = 'Cursos y Talleres';
    protected $icono = '🎓';
    protected $color_primario = 'purple';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_data();
        }

        $filtros = $this->get_filters_from_url(['categoria', 'nivel', 'modalidad', 'precio']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 12;

        $where = ["fecha_inicio >= CURDATE()"];
        if (!empty($filtros['categoria'])) {
            $where[] = $wpdb->prepare('categoria = %s', $filtros['categoria']);
        }
        if (!empty($filtros['nivel'])) {
            $where[] = $wpdb->prepare('nivel = %s', $filtros['nivel']);
        }
        if (!empty($filtros['modalidad'])) {
            $where[] = $wpdb->prepare('modalidad = %s', $filtros['modalidad']);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE " . implode(' AND ', $where));
        $cursos = $wpdb->get_results(
            "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) .
            " ORDER BY fecha_inicio ASC LIMIT $per_page OFFSET " . (($pagina - 1) * $per_page)
        );

        return [
            'titulo_pagina' => $this->nombre,
            'cursos' => $this->procesar_cursos($cursos),
            'total_cursos' => intval($total),
            'categorias' => $this->get_categorias(),
            'niveles' => $this->get_niveles(),
            'filtros_activos' => $filtros,
            'pagination' => $this->get_pagination($total, $per_page, $pagina),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_single_data($item_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_single($item_id);
        }

        $curso = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", intval($item_id)));

        if (!$curso) {
            return null;
        }

        return [
            'titulo_pagina' => $curso->titulo,
            'curso' => $this->procesar_curso_detalle($curso),
            'sesiones' => $this->get_sesiones($curso->id),
            'instructor' => $this->get_instructor($curso->instructor_id ?? 0),
            'cursos_relacionados' => $this->get_relacionados($curso->id, $curso->categoria),
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
                'sugerencias' => ['cocina', 'idiomas', 'yoga', 'fotografía', 'informática'],
            ];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_cursos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['resultados' => [], 'total_resultados' => 0, 'sugerencias' => []];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $cursos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE titulo LIKE %s OR descripcion LIKE %s AND fecha_inicio >= CURDATE() LIMIT 20",
            $like, $like
        ));

        return [
            'resultados' => $this->procesar_cursos($cursos),
            'total_resultados' => count($cursos),
            'sugerencias' => [],
        ];
    }

    private function procesar_cursos($cursos) {
        return array_map([$this, 'procesar_curso'], $cursos);
    }

    private function procesar_curso($curso) {
        return [
            'id' => $curso->id,
            'titulo' => $curso->titulo,
            'descripcion' => wp_trim_words($curso->descripcion ?? '', 25),
            'categoria' => $curso->categoria ?? 'general',
            'categoria_label' => $this->get_categoria_label($curso->categoria ?? 'general'),
            'nivel' => $curso->nivel ?? 'todos',
            'nivel_label' => $this->get_nivel_label($curso->nivel ?? 'todos'),
            'modalidad' => $curso->modalidad ?? 'presencial',
            'fecha_inicio' => date_i18n('j M Y', strtotime($curso->fecha_inicio)),
            'fecha_fin' => $curso->fecha_fin ? date_i18n('j M Y', strtotime($curso->fecha_fin)) : null,
            'horario' => $curso->horario ?? '',
            'duracion' => $curso->duracion_horas ?? 0,
            'precio' => floatval($curso->precio ?? 0),
            'precio_formateado' => $curso->precio > 0 ? number_format($curso->precio, 0) . '€' : 'Gratuito',
            'plazas_totales' => intval($curso->plazas ?? 20),
            'plazas_disponibles' => intval($curso->plazas_disponibles ?? 20),
            'imagen' => $curso->imagen_url ?? null,
            'instructor' => $curso->instructor_nombre ?? '',
            'url' => home_url('/' . $this->slug . '/' . $curso->id . '/'),
        ];
    }

    private function procesar_curso_detalle($curso) {
        $base = $this->procesar_curso($curso);
        $base['descripcion_completa'] = $curso->descripcion ?? '';
        $base['objetivos'] = json_decode($curso->objetivos ?? '[]', true) ?: [];
        $base['requisitos'] = $curso->requisitos ?? '';
        $base['materiales'] = $curso->materiales ?? '';
        $base['ubicacion'] = $curso->ubicacion ?? '';
        return $base;
    }

    private function get_demo_data() {
        return [
            'titulo_pagina' => $this->nombre,
            'cursos' => [
                [
                    'id' => 1,
                    'titulo' => 'Taller de cocina mediterránea',
                    'descripcion' => 'Aprende a preparar los platos más típicos de la dieta mediterránea.',
                    'categoria' => 'cocina',
                    'categoria_label' => 'Cocina',
                    'nivel' => 'principiante',
                    'nivel_label' => 'Principiante',
                    'modalidad' => 'presencial',
                    'fecha_inicio' => date_i18n('j M Y', strtotime('+7 days')),
                    'fecha_fin' => date_i18n('j M Y', strtotime('+30 days')),
                    'horario' => 'Sábados 10:00-13:00',
                    'duracion' => 12,
                    'precio' => 45,
                    'precio_formateado' => '45€',
                    'plazas_totales' => 12,
                    'plazas_disponibles' => 4,
                    'imagen' => null,
                    'instructor' => 'Chef María López',
                    'url' => home_url('/' . $this->slug . '/1/'),
                ],
            ],
            'total_cursos' => 1,
            'categorias' => $this->get_categorias(),
            'niveles' => $this->get_niveles(),
            'filtros_activos' => [],
            'pagination' => $this->get_pagination(1, 12, 1),
        ];
    }

    private function get_demo_single($item_id) {
        return [
            'titulo_pagina' => 'Curso de ejemplo',
            'curso' => [
                'id' => $item_id,
                'titulo' => 'Curso de ejemplo',
                'descripcion' => 'Descripción del curso.',
                'descripcion_completa' => 'Descripción completa del curso.',
                'categoria' => 'general',
                'categoria_label' => 'General',
                'nivel' => 'todos',
                'nivel_label' => 'Todos los niveles',
                'modalidad' => 'presencial',
                'fecha_inicio' => date_i18n('j M Y'),
                'fecha_fin' => null,
                'horario' => '',
                'duracion' => 0,
                'precio' => 0,
                'precio_formateado' => 'Gratuito',
                'plazas_totales' => 20,
                'plazas_disponibles' => 20,
                'imagen' => null,
                'instructor' => '',
                'objetivos' => [],
                'requisitos' => '',
                'materiales' => '',
                'ubicacion' => '',
                'url' => home_url('/' . $this->slug . '/' . $item_id . '/'),
            ],
            'sesiones' => [],
            'instructor' => null,
            'cursos_relacionados' => [],
        ];
    }

    private function get_categorias() {
        return [
            ['slug' => 'cocina', 'nombre' => 'Cocina', 'count' => 8],
            ['slug' => 'idiomas', 'nombre' => 'Idiomas', 'count' => 12],
            ['slug' => 'informatica', 'nombre' => 'Informática', 'count' => 6],
            ['slug' => 'manualidades', 'nombre' => 'Manualidades', 'count' => 10],
            ['slug' => 'deporte', 'nombre' => 'Deporte y Salud', 'count' => 15],
            ['slug' => 'musica', 'nombre' => 'Música', 'count' => 5],
        ];
    }

    private function get_niveles() {
        return [
            ['slug' => 'principiante', 'nombre' => 'Principiante'],
            ['slug' => 'intermedio', 'nombre' => 'Intermedio'],
            ['slug' => 'avanzado', 'nombre' => 'Avanzado'],
            ['slug' => 'todos', 'nombre' => 'Todos los niveles'],
        ];
    }

    private function get_categoria_label($categoria) {
        foreach ($this->get_categorias() as $cat) {
            if ($cat['slug'] === $categoria) return $cat['nombre'];
        }
        return ucfirst($categoria);
    }

    private function get_nivel_label($nivel) {
        foreach ($this->get_niveles() as $n) {
            if ($n['slug'] === $nivel) return $n['nombre'];
        }
        return ucfirst($nivel);
    }

    private function get_sesiones($curso_id) {
        return [];
    }

    private function get_instructor($instructor_id) {
        return null;
    }

    private function get_relacionados($curso_id, $categoria) {
        return [];
    }

    protected function ajax_inscribirse($data) {
        if (!is_user_logged_in()) {
            return ['error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }
        return ['success' => true, 'mensaje' => __('Inscripción realizada', 'flavor-chat-ia')];
    }
}
