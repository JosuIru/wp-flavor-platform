<?php
/**
 * Controlador frontend: Espacios Comunes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador para el módulo de Espacios Comunes
 */
class Flavor_Frontend_Espacios_Comunes_Controller extends Flavor_Frontend_Controller_Base {

    protected $slug = 'espacios-comunes';
    protected $nombre = 'Espacios Comunes';
    protected $icono = '🏛️';
    protected $color_primario = 'cyan';

    /**
     * {@inheritdoc}
     */
    protected function get_archive_data() {
        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        // Obtener filtros
        $filtros = $this->get_filters_from_url(['tipo', 'capacidad', 'precio']);
        $pagina = max(1, intval($_GET['pag'] ?? 1));
        $per_page = 12;
        $offset = ($pagina - 1) * $per_page;

        // Construir query
        $where = ["estado = 'disponible'"];
        $prepare_values = [];

        if (!empty($filtros['tipo'])) {
            $where[] = 'tipo = %s';
            $prepare_values[] = $filtros['tipo'];
        }

        if (!empty($filtros['capacidad'])) {
            $where[] = 'capacidad_personas >= %d';
            $prepare_values[] = intval($filtros['capacidad']);
        }

        $where_sql = implode(' AND ', $where);

        // Contar total
        $count_sql = "SELECT COUNT(*) FROM $tabla_espacios WHERE $where_sql";
        if (!empty($prepare_values)) {
            $total = $wpdb->get_var($wpdb->prepare($count_sql, ...$prepare_values));
        } else {
            $total = $wpdb->get_var($count_sql);
        }

        // Obtener espacios
        $sql = "SELECT * FROM $tabla_espacios WHERE $where_sql ORDER BY nombre LIMIT %d OFFSET %d";
        $prepare_values[] = $per_page;
        $prepare_values[] = $offset;

        $espacios = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        // Procesar espacios
        $espacios_procesados = array_map(function($espacio) {
            $fotos = json_decode($espacio->fotos, true) ?: [];
            return [
                'id' => $espacio->id,
                'nombre' => $espacio->nombre,
                'descripcion' => wp_trim_words($espacio->descripcion, 20),
                'tipo' => $espacio->tipo,
                'tipo_label' => $this->get_tipo_label($espacio->tipo),
                'ubicacion' => $espacio->ubicacion,
                'capacidad' => $espacio->capacidad_personas,
                'superficie' => $espacio->superficie_m2,
                'precio_hora' => floatval($espacio->precio_hora),
                'precio_dia' => floatval($espacio->precio_dia),
                'requiere_fianza' => (bool)$espacio->requiere_fianza,
                'fianza' => floatval($espacio->importe_fianza),
                'imagen' => !empty($fotos) ? $fotos[0] : null,
                'valoracion' => floatval($espacio->valoracion_media),
                'num_valoraciones' => intval($espacio->numero_valoraciones),
                'url' => home_url('/' . $this->slug . '/' . $espacio->id . '/'),
            ];
        }, $espacios);

        // Obtener tipos para filtros
        $tipos = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as count FROM $tabla_espacios WHERE estado = 'disponible' GROUP BY tipo"
        );

        return [
            'titulo_pagina' => $this->nombre,
            'espacios' => $espacios_procesados,
            'total_espacios' => intval($total),
            'tipos' => array_map(function($t) {
                return [
                    'slug' => $t->tipo,
                    'nombre' => $this->get_tipo_label($t->tipo),
                    'count' => intval($t->count),
                ];
            }, $tipos),
            'filtros_activos' => $filtros,
            'pagination' => $this->get_pagination($total, $per_page, $pagina),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_single_data($item_id) {
        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_equipamiento = $wpdb->prefix . 'flavor_espacios_equipamiento';

        // Obtener espacio
        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_espacios WHERE id = %d AND estado = 'disponible'",
            intval($item_id)
        ));

        if (!$espacio) {
            return null;
        }

        $fotos = json_decode($espacio->fotos, true) ?: [];
        $equipamiento_ids = json_decode($espacio->equipamiento, true) ?: [];

        // Obtener equipamiento
        $equipamiento = [];
        if (!empty($equipamiento_ids)) {
            $placeholders = implode(',', array_fill(0, count($equipamiento_ids), '%d'));
            $equipamiento = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_equipamiento WHERE id IN ($placeholders)",
                ...$equipamiento_ids
            ));
        }

        // Obtener próximas reservas (para calendario)
        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha_inicio, fecha_fin FROM $tabla_reservas
             WHERE espacio_id = %d AND estado IN ('confirmada', 'solicitada')
             AND fecha_fin >= NOW() ORDER BY fecha_inicio LIMIT 50",
            $espacio->id
        ));

        // Obtener últimas valoraciones
        $valoraciones = $wpdb->get_results($wpdb->prepare(
            "SELECT r.valoracion, r.comentario_valoracion, r.fecha_fin, u.display_name as usuario
             FROM $tabla_reservas r
             LEFT JOIN {$wpdb->users} u ON r.usuario_id = u.ID
             WHERE r.espacio_id = %d AND r.valoracion IS NOT NULL
             ORDER BY r.fecha_fin DESC LIMIT 5",
            $espacio->id
        ));

        return [
            'titulo_pagina' => $espacio->nombre,
            'espacio' => [
                'id' => $espacio->id,
                'nombre' => $espacio->nombre,
                'descripcion' => $espacio->descripcion,
                'tipo' => $espacio->tipo,
                'tipo_label' => $this->get_tipo_label($espacio->tipo),
                'ubicacion' => $espacio->ubicacion,
                'latitud' => $espacio->latitud,
                'longitud' => $espacio->longitud,
                'capacidad' => $espacio->capacidad_personas,
                'superficie' => $espacio->superficie_m2,
                'precio_hora' => floatval($espacio->precio_hora),
                'precio_dia' => floatval($espacio->precio_dia),
                'requiere_fianza' => (bool)$espacio->requiere_fianza,
                'fianza' => floatval($espacio->importe_fianza),
                'horario_apertura' => $espacio->horario_apertura,
                'horario_cierre' => $espacio->horario_cierre,
                'dias_disponibles' => explode(',', $espacio->dias_disponibles),
                'normas_uso' => $espacio->normas_uso,
                'instrucciones_acceso' => $espacio->instrucciones_acceso,
                'imagen' => !empty($fotos) ? $fotos[0] : null,
                'galeria' => $fotos,
                'valoracion' => floatval($espacio->valoracion_media),
                'num_valoraciones' => intval($espacio->numero_valoraciones),
            ],
            'equipamiento' => array_map(function($e) {
                return [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'descripcion' => $e->descripcion,
                    'categoria' => $e->categoria,
                    'cantidad' => $e->cantidad,
                    'foto' => $e->foto_url,
                ];
            }, $equipamiento),
            'reservas' => array_map(function($r) {
                return [
                    'inicio' => $r->fecha_inicio,
                    'fin' => $r->fecha_fin,
                ];
            }, $reservas),
            'valoraciones' => array_map(function($v) {
                return [
                    'puntuacion' => intval($v->valoracion),
                    'comentario' => $v->comentario_valoracion,
                    'fecha' => $v->fecha_fin,
                    'usuario' => $v->usuario ?: 'Anónimo',
                ];
            }, $valoraciones),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_search_data($query) {
        global $wpdb;
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        if (empty($query)) {
            return [
                'resultados' => [],
                'total_resultados' => 0,
                'sugerencias' => ['salón', 'terraza', 'sala reuniones', 'cocina', 'gimnasio'],
            ];
        }

        $like = '%' . $wpdb->esc_like($query) . '%';

        $espacios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_espacios
             WHERE estado = 'disponible'
             AND (nombre LIKE %s OR descripcion LIKE %s OR ubicacion LIKE %s)
             ORDER BY valoracion_media DESC LIMIT 20",
            $like, $like, $like
        ));

        $resultados = array_map(function($espacio) {
            $fotos = json_decode($espacio->fotos, true) ?: [];
            return [
                'id' => $espacio->id,
                'nombre' => $espacio->nombre,
                'descripcion' => wp_trim_words($espacio->descripcion, 20),
                'tipo' => $espacio->tipo,
                'tipo_label' => $this->get_tipo_label($espacio->tipo),
                'ubicacion' => $espacio->ubicacion,
                'capacidad' => $espacio->capacidad_personas,
                'precio_hora' => floatval($espacio->precio_hora),
                'imagen' => !empty($fotos) ? $fotos[0] : null,
                'valoracion' => floatval($espacio->valoracion_media),
                'url' => home_url('/' . $this->slug . '/' . $espacio->id . '/'),
            ];
        }, $espacios);

        return [
            'resultados' => $resultados,
            'total_resultados' => count($resultados),
            'sugerencias' => [],
        ];
    }

    /**
     * Obtiene la etiqueta legible del tipo de espacio
     *
     * @param string $tipo Tipo de espacio
     * @return string
     */
    private function get_tipo_label($tipo) {
        $tipos = [
            'salon_eventos' => 'Salón de Eventos',
            'sala_reuniones' => 'Sala de Reuniones',
            'cocina' => 'Cocina Comunitaria',
            'taller' => 'Taller',
            'terraza' => 'Terraza',
            'jardin' => 'Jardín',
            'gimnasio' => 'Gimnasio',
            'ludoteca' => 'Ludoteca',
            'otro' => 'Otro',
        ];
        return $tipos[$tipo] ?? ucfirst($tipo);
    }

    /**
     * AJAX: Crear reserva
     *
     * @param array $data Datos del formulario
     * @return array
     */
    protected function ajax_crear_reserva($data) {
        if (!is_user_logged_in()) {
            return ['error' => __('Debes iniciar sesión para reservar', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $espacio_id = intval($data['espacio_id'] ?? 0);
        $fecha_inicio = sanitize_text_field($data['fecha_inicio'] ?? '');
        $fecha_fin = sanitize_text_field($data['fecha_fin'] ?? '');
        $motivo = sanitize_textarea_field($data['motivo'] ?? '');
        $num_asistentes = intval($data['num_asistentes'] ?? 0);

        // Validaciones básicas
        if (!$espacio_id || !$fecha_inicio || !$fecha_fin) {
            return ['error' => __('Faltan datos obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        // Verificar disponibilidad
        $conflicto = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas
             WHERE espacio_id = %d AND estado IN ('confirmada', 'solicitada')
             AND ((fecha_inicio <= %s AND fecha_fin >= %s) OR (fecha_inicio <= %s AND fecha_fin >= %s))",
            $espacio_id, $fecha_inicio, $fecha_inicio, $fecha_fin, $fecha_fin
        ));

        if ($conflicto > 0) {
            return ['error' => __('El espacio no está disponible en esas fechas', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        // Crear reserva
        $resultado = $wpdb->insert($tabla_reservas, [
            'espacio_id' => $espacio_id,
            'usuario_id' => get_current_user_id(),
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'motivo' => $motivo,
            'num_asistentes' => $num_asistentes,
            'estado' => 'solicitada',
            'fecha_solicitud' => current_time('mysql'),
        ]);

        if ($resultado) {
            return [
                'success' => true,
                'reserva_id' => $wpdb->insert_id,
                'mensaje' => __('Reserva solicitada correctamente. Recibirás confirmación pronto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return ['error' => __('Error al crear la reserva', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    /**
     * AJAX: Obtener disponibilidad
     *
     * @param array $data Datos de la petición
     * @return array
     */
    protected function ajax_get_disponibilidad($data) {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $espacio_id = intval($data['espacio_id'] ?? 0);
        $mes = sanitize_text_field($data['mes'] ?? date('Y-m'));

        $inicio_mes = $mes . '-01 00:00:00';
        $fin_mes = date('Y-m-t 23:59:59', strtotime($inicio_mes));

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha_inicio, fecha_fin, estado FROM $tabla_reservas
             WHERE espacio_id = %d AND estado IN ('confirmada', 'solicitada')
             AND fecha_inicio <= %s AND fecha_fin >= %s",
            $espacio_id, $fin_mes, $inicio_mes
        ));

        return [
            'reservas' => array_map(function($r) {
                return [
                    'inicio' => $r->fecha_inicio,
                    'fin' => $r->fecha_fin,
                    'estado' => $r->estado,
                ];
            }, $reservas),
        ];
    }
}
