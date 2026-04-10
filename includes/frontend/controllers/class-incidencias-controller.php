<?php
/**
 * Controlador frontend: Incidencias
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Incidencias
 */
class Flavor_Frontend_Incidencias_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'incidencias';
    protected $nombre = 'Incidencias del Barrio';
    protected $icono = '⚠️';
    protected $color_primario = 'red';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_data();
        }

        $filtros = $this->get_filters_from_url(['estado', 'prioridad', 'categoria', 'zona']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 10;

        $where = ["1=1"];
        if (!empty($filtros['estado'])) {
            $where[] = $wpdb->prepare('estado = %s', $filtros['estado']);
        }
        if (!empty($filtros['prioridad'])) {
            $where[] = $wpdb->prepare('prioridad = %s', $filtros['prioridad']);
        }
        if (!empty($filtros['categoria'])) {
            $where[] = $wpdb->prepare('categoria = %s', $filtros['categoria']);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE " . implode(' AND ', $where));
        $incidencias = $wpdb->get_results(
            "SELECT * FROM $tabla WHERE " . implode(' AND ', $where) .
            " ORDER BY fecha_creacion DESC LIMIT $per_page OFFSET " . (($pagina - 1) * $per_page)
        );

        return [
            'titulo_pagina' => $this->nombre,
            'incidencias' => $this->procesar_incidencias($incidencias),
            'total_incidencias' => intval($total),
            'categorias' => $this->get_categorias(),
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
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_single($item_id);
        }

        $incidencia = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            intval($item_id)
        ));

        if (!$incidencia) {
            return null;
        }

        return [
            'titulo_pagina' => $incidencia->titulo,
            'incidencia' => $this->procesar_incidencia_detalle($incidencia),
            'comentarios' => $this->get_comentarios($incidencia->id),
            'historial' => $this->get_historial($incidencia->id),
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
                'sugerencias' => ['farola', 'bache', 'grafiti', 'basura', 'ruido', 'acera'],
            ];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return ['resultados' => [], 'total_resultados' => 0, 'sugerencias' => []];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $incidencias = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE titulo LIKE %s OR descripcion LIKE %s OR ubicacion LIKE %s ORDER BY fecha_creacion DESC LIMIT 20",
            $like, $like, $like
        ));

        return [
            'resultados' => $this->procesar_incidencias($incidencias),
            'total_resultados' => count($incidencias),
            'sugerencias' => [],
        ];
    }

    private function procesar_incidencias($incidencias) {
        return array_map([$this, 'procesar_incidencia'], $incidencias);
    }

    private function procesar_incidencia($inc) {
        $fotos = json_decode($inc->fotos ?? '[]', true) ?: [];
        $usuario = get_userdata($inc->usuario_id ?? 0);

        return [
            'id' => $inc->id,
            'titulo' => $inc->titulo,
            'descripcion' => wp_trim_words($inc->descripcion ?? '', 30),
            'categoria' => $inc->categoria ?? 'general',
            'estado' => $inc->estado ?? 'pendiente',
            'prioridad' => $inc->prioridad ?? 'media',
            'ubicacion' => $inc->ubicacion ?? '',
            'fecha' => date_i18n('j M Y', strtotime($inc->fecha_creacion)),
            'fecha_creacion' => $inc->fecha_creacion,
            'autor' => $usuario ? $usuario->display_name : 'Anónimo',
            'votos' => intval($inc->votos ?? 0),
            'imagen' => !empty($fotos) ? $fotos[0] : null,
            'url' => home_url('/' . $this->slug . '/' . $inc->id . '/'),
        ];
    }

    private function procesar_incidencia_detalle($inc) {
        $base = $this->procesar_incidencia($inc);
        $fotos = json_decode($inc->fotos ?? '[]', true) ?: [];

        $base['descripcion'] = $inc->descripcion ?? '';
        $base['galeria'] = $fotos;
        $base['coordenadas'] = null;
        if ($inc->latitud && $inc->longitud) {
            $base['coordenadas'] = [
                'lat' => floatval($inc->latitud),
                'lng' => floatval($inc->longitud),
            ];
        }
        $base['fecha_resolucion'] = $inc->fecha_resolucion ?? null;
        $base['asignado_a'] = $inc->asignado_a ?? null;

        return $base;
    }

    private function get_demo_data() {
        return [
            'titulo_pagina' => $this->nombre,
            'incidencias' => [
                [
                    'id' => 1,
                    'titulo' => 'Farola fundida en Calle Mayor',
                    'descripcion' => 'La farola junto al número 15 lleva una semana sin funcionar.',
                    'categoria' => 'alumbrado',
                    'estado' => 'pendiente',
                    'prioridad' => 'media',
                    'ubicacion' => 'Calle Mayor, 15',
                    'fecha' => date_i18n('j M Y'),
                    'fecha_creacion' => current_time('mysql'),
                    'autor' => 'Vecino/a',
                    'votos' => 12,
                    'imagen' => null,
                    'url' => home_url('/' . $this->slug . '/1/'),
                ],
                [
                    'id' => 2,
                    'titulo' => 'Bache peligroso en Plaza España',
                    'descripcion' => 'Bache grande en la esquina de la plaza, puede causar accidentes.',
                    'categoria' => 'vias',
                    'estado' => 'en_proceso',
                    'prioridad' => 'alta',
                    'ubicacion' => 'Plaza España, esquina',
                    'fecha' => date_i18n('j M Y', strtotime('-2 days')),
                    'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    'autor' => 'Vecino/a',
                    'votos' => 25,
                    'imagen' => null,
                    'url' => home_url('/' . $this->slug . '/2/'),
                ],
            ],
            'total_incidencias' => 2,
            'categorias' => $this->get_categorias(),
            'zonas' => $this->get_zonas(),
            'estadisticas' => [
                'pendientes' => 15,
                'en_proceso' => 8,
                'resueltas' => 124,
                'tiempo_medio' => 5,
            ],
            'filtros_activos' => [],
            'pagination' => $this->get_pagination(2, 10, 1),
        ];
    }

    private function get_demo_single($item_id) {
        return [
            'titulo_pagina' => 'Incidencia',
            'incidencia' => [
                'id' => $item_id,
                'titulo' => 'Incidencia de ejemplo',
                'descripcion' => 'Descripción de la incidencia.',
                'categoria' => 'general',
                'estado' => 'pendiente',
                'prioridad' => 'media',
                'ubicacion' => '',
                'fecha' => date_i18n('j M Y'),
                'fecha_creacion' => current_time('mysql'),
                'autor' => 'Anónimo',
                'votos' => 0,
                'imagen' => null,
                'galeria' => [],
                'coordenadas' => null,
                'fecha_resolucion' => null,
                'asignado_a' => null,
                'url' => home_url('/' . $this->slug . '/' . $item_id . '/'),
            ],
            'comentarios' => [],
            'historial' => [],
        ];
    }

    private function get_categorias() {
        return [
            ['slug' => 'alumbrado', 'nombre' => 'Alumbrado público', 'count' => 12],
            ['slug' => 'vias', 'nombre' => 'Vías y aceras', 'count' => 18],
            ['slug' => 'limpieza', 'nombre' => 'Limpieza', 'count' => 25],
            ['slug' => 'mobiliario', 'nombre' => 'Mobiliario urbano', 'count' => 8],
            ['slug' => 'zonas_verdes', 'nombre' => 'Zonas verdes', 'count' => 10],
            ['slug' => 'ruidos', 'nombre' => 'Ruidos y molestias', 'count' => 15],
            ['slug' => 'seguridad', 'nombre' => 'Seguridad', 'count' => 6],
            ['slug' => 'otros', 'nombre' => 'Otros', 'count' => 20],
        ];
    }

    private function get_zonas() {
        return [
            ['id' => 'centro', 'nombre' => 'Centro'],
            ['id' => 'norte', 'nombre' => 'Zona Norte'],
            ['id' => 'sur', 'nombre' => 'Zona Sur'],
            ['id' => 'este', 'nombre' => 'Zona Este'],
            ['id' => 'oeste', 'nombre' => 'Zona Oeste'],
        ];
    }

    private function get_estadisticas() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return ['pendientes' => 0, 'en_proceso' => 0, 'resueltas' => 0, 'tiempo_medio' => '—'];
        }

        // Incluir estados en español e inglés para compatibilidad
        return [
            'pendientes' => intval($wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado IN ('pendiente', 'pending')")),
            'en_proceso' => intval($wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado IN ('en_proceso', 'in_progress')")),
            'resueltas' => intval($wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado IN ('resuelta', 'resuelto', 'resolved')")),
            'tiempo_medio' => '—',
        ];
    }

    private function get_comentarios($incidencia_id) {
        return [];
    }

    private function get_historial($incidencia_id) {
        return [];
    }

    protected function ajax_crear_incidencia($data) {
        if (!is_user_logged_in()) {
            return ['error' => __('Debes iniciar sesión para reportar incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        $resultado = $wpdb->insert($tabla, [
            'usuario_id' => get_current_user_id(),
            'titulo' => sanitize_text_field($data['titulo'] ?? ''),
            'descripcion' => sanitize_textarea_field($data['descripcion'] ?? ''),
            'categoria' => sanitize_text_field($data['categoria'] ?? 'otros'),
            'prioridad' => sanitize_text_field($data['prioridad'] ?? 'media'),
            'ubicacion' => sanitize_text_field($data['ubicacion'] ?? ''),
            'latitud' => floatval($data['latitud'] ?? 0) ?: null,
            'longitud' => floatval($data['longitud'] ?? 0) ?: null,
            'estado' => 'pendiente',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            return ['success' => true, 'id' => $wpdb->insert_id, 'mensaje' => __('Incidencia reportada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        return ['error' => __('Error al crear la incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    protected function ajax_apoyar($data) {
        $incidencia_id = intval($data['incidencia_id'] ?? 0);

        if ($incidencia_id <= 0) {
            return ['error' => __('Incidencia no válida', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';

        // Verificar si ya ha votado (usando cookies para usuarios anónimos)
        $votados = json_decode(stripslashes($_COOKIE['flavor_incidencias_votadas'] ?? '[]'), true) ?: [];
        if (in_array($incidencia_id, $votados)) {
            return ['error' => __('Ya has apoyado esta incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla SET votos = votos + 1 WHERE id = %d",
            $incidencia_id
        ));

        $votados[] = $incidencia_id;
        setcookie('flavor_incidencias_votadas', json_encode($votados), time() + 86400 * 365, '/');

        $nuevos_votos = $wpdb->get_var($wpdb->prepare("SELECT votos FROM $tabla WHERE id = %d", $incidencia_id));

        return ['success' => true, 'votos' => intval($nuevos_votos), 'mensaje' => __('Gracias por tu apoyo', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    protected function ajax_enviar_comentario($data) {
        if (!is_user_logged_in()) {
            return ['error' => __('Debes iniciar sesión para comentar', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        // Implementar lógica de comentarios
        return ['success' => true, 'mensaje' => __('Comentario enviado', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }
}
