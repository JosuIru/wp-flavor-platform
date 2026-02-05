<?php
/**
 * Controlador frontend: Podcast
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Podcast
 */
class Flavor_Frontend_Podcast_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'podcast';
    protected $nombre = 'Podcast';
    protected $icono = '🎙️';
    protected $color_primario = 'teal';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_podcasts';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_data();
        }

        $filtros = $this->get_filters_from_url(['categoria', 'serie']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 12;

        $where = ["estado = 'publicado'"];
        if (!empty($filtros['categoria'])) {
            $where[] = $wpdb->prepare('categoria = %s', $filtros['categoria']);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE " . implode(' AND ', $where));
        $podcasts = $wpdb->get_results(
            "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) .
            " ORDER BY fecha_publicacion DESC LIMIT $per_page OFFSET " . (($pagina - 1) * $per_page)
        );

        return [
            'titulo_pagina' => $this->nombre,
            'podcasts' => $this->procesar_podcasts($podcasts),
            'total_podcasts' => intval($total),
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
        $tabla = $wpdb->prefix . 'flavor_podcasts';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_single($item_id);
        }

        $podcast = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d AND estado = 'publicado'",
            intval($item_id)
        ));

        if (!$podcast) {
            return null;
        }

        return [
            'titulo_pagina' => $podcast->titulo,
            'podcast' => $this->procesar_podcast_detalle($podcast),
            'episodios_relacionados' => $this->get_relacionados($podcast->id, $podcast->serie_id ?? 0),
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
                'sugerencias' => ['entrevistas', 'cultura', 'vecinos', 'historia', 'música'],
            ];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_podcasts';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['resultados' => [], 'total_resultados' => 0, 'sugerencias' => []];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $podcasts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE estado = 'publicado' AND (titulo LIKE %s OR descripcion LIKE %s) LIMIT 20",
            $like, $like
        ));

        return [
            'resultados' => $this->procesar_podcasts($podcasts),
            'total_resultados' => count($podcasts),
            'sugerencias' => [],
        ];
    }

    private function procesar_podcasts($podcasts) {
        return array_map([$this, 'procesar_podcast'], $podcasts);
    }

    private function procesar_podcast($podcast) {
        return [
            'id' => $podcast->id,
            'titulo' => $podcast->titulo,
            'descripcion' => wp_trim_words($podcast->descripcion ?? '', 25),
            'categoria' => $podcast->categoria ?? 'general',
            'categoria_label' => $this->get_categoria_label($podcast->categoria ?? 'general'),
            'fecha' => date_i18n('j M Y', strtotime($podcast->fecha_publicacion)),
            'duracion' => $this->format_duracion($podcast->duracion_segundos ?? 0),
            'duracion_segundos' => intval($podcast->duracion_segundos ?? 0),
            'portada' => $podcast->portada_url ?? null,
            'audio_url' => $podcast->audio_url ?? '',
            'reproducciones' => intval($podcast->reproducciones ?? 0),
            'serie' => $podcast->serie_nombre ?? null,
            'url' => home_url('/' . $this->slug . '/' . $podcast->id . '/'),
        ];
    }

    private function procesar_podcast_detalle($podcast) {
        $base = $this->procesar_podcast($podcast);
        $base['descripcion_completa'] = $podcast->descripcion ?? '';
        $base['transcripcion'] = $podcast->transcripcion ?? '';
        $base['invitados'] = json_decode($podcast->invitados ?? '[]', true) ?: [];
        $base['notas'] = $podcast->notas ?? '';
        $base['enlaces'] = json_decode($podcast->enlaces ?? '[]', true) ?: [];
        return $base;
    }

    private function get_demo_data() {
        return [
            'titulo_pagina' => $this->nombre,
            'podcasts' => [
                [
                    'id' => 1,
                    'titulo' => 'Voces del Barrio #15: Historia viva',
                    'descripcion' => 'En este episodio hablamos con los vecinos más veteranos sobre la historia del barrio.',
                    'categoria' => 'entrevistas',
                    'categoria_label' => 'Entrevistas',
                    'fecha' => date_i18n('j M Y'),
                    'duracion' => '45:30',
                    'duracion_segundos' => 2730,
                    'portada' => null,
                    'audio_url' => '',
                    'reproducciones' => 234,
                    'serie' => 'Voces del Barrio',
                    'url' => home_url('/' . $this->slug . '/1/'),
                ],
            ],
            'total_podcasts' => 1,
            'categorias' => $this->get_categorias(),
            'filtros_activos' => [],
            'pagination' => $this->get_pagination(1, 12, 1),
        ];
    }

    private function get_demo_single($item_id) {
        return [
            'titulo_pagina' => 'Episodio de podcast',
            'podcast' => [
                'id' => $item_id,
                'titulo' => 'Episodio de ejemplo',
                'descripcion' => 'Descripción del episodio.',
                'descripcion_completa' => 'Descripción completa del episodio.',
                'categoria' => 'general',
                'categoria_label' => 'General',
                'fecha' => date_i18n('j M Y'),
                'duracion' => '00:00',
                'duracion_segundos' => 0,
                'portada' => null,
                'audio_url' => '',
                'reproducciones' => 0,
                'serie' => null,
                'transcripcion' => '',
                'invitados' => [],
                'notas' => '',
                'enlaces' => [],
                'url' => home_url('/' . $this->slug . '/' . $item_id . '/'),
            ],
            'episodios_relacionados' => [],
        ];
    }

    private function get_categorias() {
        return [
            ['slug' => 'entrevistas', 'nombre' => 'Entrevistas', 'count' => 25],
            ['slug' => 'cultura', 'nombre' => 'Cultura', 'count' => 18],
            ['slug' => 'actualidad', 'nombre' => 'Actualidad', 'count' => 30],
            ['slug' => 'historia', 'nombre' => 'Historia', 'count' => 12],
            ['slug' => 'musica', 'nombre' => 'Música', 'count' => 15],
        ];
    }

    private function get_categoria_label($categoria) {
        foreach ($this->get_categorias() as $cat) {
            if ($cat['slug'] === $categoria) return $cat['nombre'];
        }
        return ucfirst($categoria);
    }

    private function format_duracion($segundos) {
        $minutos = floor($segundos / 60);
        $segs = $segundos % 60;
        return sprintf('%02d:%02d', $minutos, $segs);
    }

    private function get_relacionados($podcast_id, $serie_id) {
        return [];
    }

    protected function ajax_registrar_reproduccion($data) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_podcasts';
        $podcast_id = intval($data['podcast_id'] ?? 0);

        if ($podcast_id > 0) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla SET reproducciones = reproducciones + 1 WHERE id = %d",
                $podcast_id
            ));
        }

        return ['success' => true];
    }
}
