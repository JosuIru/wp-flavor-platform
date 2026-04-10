<?php
/**
 * Controlador frontend: Ayuda Vecinal
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Ayuda Vecinal
 */
class Flavor_Frontend_Ayuda_Vecinal_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'ayuda-vecinal';
    protected $nombre = 'Ayuda Vecinal';
    protected $icono = '🤝';
    protected $color_primario = 'orange';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_ayuda_vecinal';

        // Verificar si la tabla existe
        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_archive_data();
        }

        $filtros = $this->get_filters_from_url(['tipo', 'estado', 'urgencia']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 12;
        $offset = ($pagina - 1) * $per_page;

        $where = ["1=1"];
        $prepare_values = [];

        if (!empty($filtros['tipo'])) {
            $where[] = 'tipo = %s';
            $prepare_values[] = $filtros['tipo'];
        }

        if (!empty($filtros['estado'])) {
            $where[] = 'estado = %s';
            $prepare_values[] = $filtros['estado'];
        }

        $where_sql = implode(' AND ', $where);

        // Contar total
        $count_sql = "SELECT COUNT(*) FROM $tabla WHERE $where_sql";
        $total = !empty($prepare_values)
            ? $wpdb->get_var($wpdb->prepare($count_sql, ...$prepare_values))
            : $wpdb->get_var($count_sql);

        // Obtener solicitudes
        $sql = "SELECT * FROM $tabla WHERE $where_sql ORDER BY fecha_creacion DESC LIMIT %d OFFSET %d";
        $prepare_values[] = $per_page;
        $prepare_values[] = $offset;

        $solicitudes = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        return [
            'titulo_pagina' => $this->nombre,
            'solicitudes' => $this->procesar_solicitudes($solicitudes),
            'total_solicitudes' => intval($total),
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
        $tabla = $wpdb->prefix . 'flavor_ayuda_vecinal';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return $this->get_demo_single_data($item_id);
        }

        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            intval($item_id)
        ));

        if (!$solicitud) {
            return null;
        }

        return [
            'titulo_pagina' => $solicitud->titulo,
            'solicitud' => $this->procesar_solicitud($solicitud),
            'respuestas' => $this->get_respuestas($solicitud->id),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_search_data($query) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_ayuda_vecinal';

        if (empty($query) || !Flavor_Platform_Helpers::tabla_existe($tabla)) {
            return [
                'resultados' => [],
                'total_resultados' => 0,
                'sugerencias' => ['cuidado niños', 'compras', 'transporte', 'reparaciones', 'mascotas'],
            ];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';
        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE titulo LIKE %s OR descripcion LIKE %s ORDER BY fecha_creacion DESC LIMIT 20",
            $like, $like
        ));

        return [
            'resultados' => $this->procesar_solicitudes($solicitudes),
            'total_resultados' => count($solicitudes),
            'sugerencias' => [],
        ];
    }

    /**
     * Procesa una lista de solicitudes
     */
    private function procesar_solicitudes($solicitudes) {
        return array_map([$this, 'procesar_solicitud'], $solicitudes);
    }

    /**
     * Procesa una solicitud individual
     */
    private function procesar_solicitud($sol) {
        $usuario = get_userdata($sol->usuario_id ?? 0);
        return [
            'id' => $sol->id,
            'titulo' => $sol->titulo,
            'descripcion' => $sol->descripcion,
            'tipo' => $sol->tipo ?? 'general',
            'tipo_label' => $this->get_tipo_label($sol->tipo ?? 'general'),
            'estado' => $sol->estado ?? 'abierta',
            'urgencia' => $sol->urgencia ?? 'normal',
            'fecha' => date_i18n('j M Y', strtotime($sol->fecha_creacion)),
            'autor' => $usuario ? $usuario->display_name : 'Vecino/a',
            'url' => home_url('/' . $this->slug . '/' . $sol->id . '/'),
        ];
    }

    /**
     * Datos demo para archivo
     */
    private function get_demo_archive_data() {
        return [
            'titulo_pagina' => $this->nombre,
            'solicitudes' => [
                [
                    'id' => 1,
                    'titulo' => 'Necesito ayuda para hacer la compra',
                    'descripcion' => 'Soy mayor y me cuesta cargar peso. ¿Alguien podría ayudarme?',
                    'tipo' => 'compras',
                    'tipo_label' => 'Compras',
                    'estado' => 'abierta',
                    'urgencia' => 'normal',
                    'fecha' => date_i18n('j M Y'),
                    'autor' => 'María García',
                    'url' => home_url('/' . $this->slug . '/1/'),
                ],
                [
                    'id' => 2,
                    'titulo' => 'Cuidado de mascota fin de semana',
                    'descripcion' => 'Busco a alguien que pueda cuidar a mi gato este fin de semana.',
                    'tipo' => 'mascotas',
                    'tipo_label' => 'Mascotas',
                    'estado' => 'abierta',
                    'urgencia' => 'baja',
                    'fecha' => date_i18n('j M Y', strtotime('-1 day')),
                    'autor' => 'Pedro López',
                    'url' => home_url('/' . $this->slug . '/2/'),
                ],
            ],
            'total_solicitudes' => 2,
            'categorias' => $this->get_categorias(),
            'filtros_activos' => [],
            'pagination' => $this->get_pagination(2, 12, 1),
        ];
    }

    /**
     * Datos demo para single
     */
    private function get_demo_single_data($item_id) {
        return [
            'titulo_pagina' => 'Solicitud de ayuda',
            'solicitud' => [
                'id' => $item_id,
                'titulo' => 'Ejemplo de solicitud de ayuda',
                'descripcion' => 'Esta es una solicitud de ejemplo para demostrar el funcionamiento del sistema.',
                'tipo' => 'general',
                'tipo_label' => 'General',
                'estado' => 'abierta',
                'urgencia' => 'normal',
                'fecha' => date_i18n('j M Y'),
                'autor' => 'Vecino/a',
                'url' => home_url('/' . $this->slug . '/' . $item_id . '/'),
            ],
            'respuestas' => [],
        ];
    }

    /**
     * Obtiene las categorías disponibles
     */
    private function get_categorias() {
        return [
            ['slug' => 'compras', 'nombre' => 'Compras', 'count' => 5],
            ['slug' => 'transporte', 'nombre' => 'Transporte', 'count' => 3],
            ['slug' => 'cuidados', 'nombre' => 'Cuidados', 'count' => 4],
            ['slug' => 'mascotas', 'nombre' => 'Mascotas', 'count' => 2],
            ['slug' => 'reparaciones', 'nombre' => 'Reparaciones', 'count' => 6],
            ['slug' => 'general', 'nombre' => 'General', 'count' => 8],
        ];
    }

    /**
     * Obtiene las respuestas de una solicitud
     */
    private function get_respuestas($solicitud_id) {
        // Implementar cuando exista la tabla
        return [];
    }

    /**
     * Obtiene la etiqueta del tipo
     */
    private function get_tipo_label($tipo) {
        $tipos = [
            'compras' => 'Compras',
            'transporte' => 'Transporte',
            'cuidados' => 'Cuidados',
            'mascotas' => 'Mascotas',
            'reparaciones' => 'Reparaciones',
            'general' => 'General',
        ];
        return $tipos[$tipo] ?? ucfirst($tipo);
    }

    /**
     * AJAX: Crear solicitud
     */
    protected function ajax_crear_solicitud($data) {
        if (!is_user_logged_in()) {
            return ['error' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_ayuda_vecinal';

        $resultado = $wpdb->insert($tabla, [
            'usuario_id' => get_current_user_id(),
            'titulo' => sanitize_text_field($data['titulo'] ?? ''),
            'descripcion' => sanitize_textarea_field($data['descripcion'] ?? ''),
            'tipo' => sanitize_text_field($data['tipo'] ?? 'general'),
            'urgencia' => sanitize_text_field($data['urgencia'] ?? 'normal'),
            'estado' => 'abierta',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            return ['success' => true, 'id' => $wpdb->insert_id];
        }

        return ['error' => __('Error al crear la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    /**
     * AJAX: Ofrecer ayuda
     */
    protected function ajax_ofrecer_ayuda($data) {
        if (!is_user_logged_in()) {
            return ['error' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        // Implementar lógica de respuesta
        return ['success' => true, 'mensaje' => __('Oferta de ayuda enviada', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }
}
