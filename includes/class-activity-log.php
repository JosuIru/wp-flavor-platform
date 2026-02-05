<?php
/**
 * Sistema de Registro de Actividad Cross-Module
 *
 * Registra acciones de todos los modulos en una tabla centralizada
 * para seguimiento, auditoria y diagnostico.
 *
 * @package FlavorChatIA
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Activity_Log {

    private static $instancia = null;

    const TIPO_INFO = 'info';
    const TIPO_EXITO = 'exito';
    const TIPO_ADVERTENCIA = 'advertencia';
    const TIPO_ERROR = 'error';

    private $nombre_tabla = '';

    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        global $wpdb;
        $this->nombre_tabla = $wpdb->prefix . 'flavor_activity_log';

        $this->maybe_create_table();
        $this->registrar_hooks();
    }

    private function registrar_hooks() {
        add_action('flavor_activity_log', [$this, 'hook_registrar'], 10, 4);
        add_action('flavor_activity_log_cleanup', [$this, 'limpiar_registros_antiguos']);

        if (!wp_next_scheduled('flavor_activity_log_cleanup')) {
            wp_schedule_event(time(), 'daily', 'flavor_activity_log_cleanup');
        }
    }

    // =========================================================================
    // TABLAS
    // =========================================================================

    private function maybe_create_table() {
        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($this->nombre_tabla)) {
            $this->create_table();
        }
    }

    private function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_tabla = "CREATE TABLE {$this->nombre_tabla} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            modulo_id varchar(50) NOT NULL DEFAULT 'sistema',
            accion varchar(100) NOT NULL,
            tipo varchar(20) NOT NULL DEFAULT 'info',
            titulo varchar(255) NOT NULL,
            descripcion text,
            usuario_id bigint(20) UNSIGNED DEFAULT 0,
            objeto_tipo varchar(50) DEFAULT '',
            objeto_id bigint(20) UNSIGNED DEFAULT 0,
            datos_extra longtext,
            ip_address varchar(45) DEFAULT '',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_modulo (modulo_id),
            KEY idx_tipo (tipo),
            KEY idx_usuario (usuario_id),
            KEY idx_fecha (fecha),
            KEY idx_accion (accion),
            KEY idx_objeto (objeto_tipo, objeto_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_tabla);
    }

    // =========================================================================
    // API ESTATICA
    // =========================================================================

    public static function registrar($modulo_id, $accion, $titulo, $opciones = []) {
        $instancia = self::get_instance();
        return $instancia->insertar_registro($modulo_id, $accion, $titulo, $opciones);
    }

    public static function info($modulo_id, $accion, $titulo, $opciones = []) {
        $opciones['tipo'] = self::TIPO_INFO;
        return self::registrar($modulo_id, $accion, $titulo, $opciones);
    }

    public static function exito($modulo_id, $accion, $titulo, $opciones = []) {
        $opciones['tipo'] = self::TIPO_EXITO;
        return self::registrar($modulo_id, $accion, $titulo, $opciones);
    }

    public static function advertencia($modulo_id, $accion, $titulo, $opciones = []) {
        $opciones['tipo'] = self::TIPO_ADVERTENCIA;
        return self::registrar($modulo_id, $accion, $titulo, $opciones);
    }

    public static function error($modulo_id, $accion, $titulo, $opciones = []) {
        $opciones['tipo'] = self::TIPO_ERROR;
        return self::registrar($modulo_id, $accion, $titulo, $opciones);
    }

    // =========================================================================
    // REGISTRO
    // =========================================================================

    private function insertar_registro($modulo_id, $accion, $titulo, $opciones = []) {
        global $wpdb;

        $tipo = $opciones['tipo'] ?? self::TIPO_INFO;
        $descripcion = $opciones['descripcion'] ?? '';
        $usuario_id = $opciones['usuario_id'] ?? get_current_user_id();
        $objeto_tipo = $opciones['objeto_tipo'] ?? '';
        $objeto_id = intval($opciones['objeto_id'] ?? 0);
        $datos_extra = $opciones['datos_extra'] ?? null;
        $ip_address = $this->obtener_ip_usuario();

        $datos_insercion = [
            'modulo_id'   => sanitize_key($modulo_id),
            'accion'      => sanitize_key($accion),
            'tipo'        => sanitize_key($tipo),
            'titulo'      => sanitize_text_field($titulo),
            'descripcion' => sanitize_textarea_field($descripcion),
            'usuario_id'  => intval($usuario_id),
            'objeto_tipo' => sanitize_key($objeto_tipo),
            'objeto_id'   => $objeto_id,
            'datos_extra'  => $datos_extra ? wp_json_encode($datos_extra) : null,
            'ip_address'  => sanitize_text_field($ip_address),
        ];

        $resultado = $wpdb->insert($this->nombre_tabla, $datos_insercion);

        return $resultado !== false ? $wpdb->insert_id : false;
    }

    public function hook_registrar($modulo_id, $accion, $titulo, $opciones = []) {
        $this->insertar_registro($modulo_id, $accion, $titulo, $opciones);
    }

    // =========================================================================
    // CONSULTAS
    // =========================================================================

    public function obtener_actividad($filtros = []) {
        global $wpdb;

        $pagina = max(1, intval($filtros['pagina'] ?? 1));
        $por_pagina = min(100, max(1, intval($filtros['por_pagina'] ?? 25)));
        $offset = ($pagina - 1) * $por_pagina;

        $condiciones = ['1=1'];
        $valores = [];

        if (!empty($filtros['modulo_id'])) {
            $condiciones[] = 'modulo_id = %s';
            $valores[] = sanitize_key($filtros['modulo_id']);
        }
        if (!empty($filtros['tipo'])) {
            $condiciones[] = 'tipo = %s';
            $valores[] = sanitize_key($filtros['tipo']);
        }
        if (!empty($filtros['usuario_id'])) {
            $condiciones[] = 'usuario_id = %d';
            $valores[] = intval($filtros['usuario_id']);
        }
        if (!empty($filtros['accion'])) {
            $condiciones[] = 'accion = %s';
            $valores[] = sanitize_key($filtros['accion']);
        }
        if (!empty($filtros['fecha_desde'])) {
            $condiciones[] = 'fecha >= %s';
            $valores[] = sanitize_text_field($filtros['fecha_desde']) . ' 00:00:00';
        }
        if (!empty($filtros['fecha_hasta'])) {
            $condiciones[] = 'fecha <= %s';
            $valores[] = sanitize_text_field($filtros['fecha_hasta']) . ' 23:59:59';
        }
        if (!empty($filtros['buscar'])) {
            $condiciones[] = '(titulo LIKE %s OR descripcion LIKE %s)';
            $termino_like = '%' . $wpdb->esc_like($filtros['buscar']) . '%';
            $valores[] = $termino_like;
            $valores[] = $termino_like;
        }

        $clausula_where = implode(' AND ', $condiciones);

        // Total
        $consulta_total = "SELECT COUNT(*) FROM {$this->nombre_tabla} WHERE {$clausula_where}";
        $total = $wpdb->get_var(
            !empty($valores) ? $wpdb->prepare($consulta_total, $valores) : $consulta_total
        );

        // Resultados
        $consulta_registros = "SELECT * FROM {$this->nombre_tabla} WHERE {$clausula_where} ORDER BY fecha DESC LIMIT %d OFFSET %d";
        $valores_con_paginacion = array_merge($valores, [$por_pagina, $offset]);

        $registros = $wpdb->get_results(
            $wpdb->prepare($consulta_registros, $valores_con_paginacion)
        );

        // Enriquecer con datos de usuario
        foreach ($registros as &$registro) {
            if ($registro->usuario_id > 0) {
                $informacion_usuario = get_userdata($registro->usuario_id);
                $registro->nombre_usuario = $informacion_usuario ? $informacion_usuario->display_name : __('Usuario eliminado', 'flavor-chat-ia');
            } else {
                $registro->nombre_usuario = __('Sistema', 'flavor-chat-ia');
            }
            if ($registro->datos_extra) {
                $registro->datos_extra = json_decode($registro->datos_extra, true);
            }
        }
        unset($registro);

        return [
            'registros'  => $registros,
            'total'      => intval($total),
            'pagina'     => $pagina,
            'por_pagina' => $por_pagina,
            'paginas'    => ceil(intval($total) / $por_pagina),
        ];
    }

    public function obtener_resumen($dias = 7) {
        global $wpdb;

        $fecha_desde = gmdate('Y-m-d H:i:s', strtotime("-{$dias} days"));

        $resumen_por_tipo = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo, COUNT(*) as total
             FROM {$this->nombre_tabla}
             WHERE fecha >= %s
             GROUP BY tipo
             ORDER BY total DESC",
            $fecha_desde
        ));

        $resumen_por_modulo = $wpdb->get_results($wpdb->prepare(
            "SELECT modulo_id, COUNT(*) as total
             FROM {$this->nombre_tabla}
             WHERE fecha >= %s
             GROUP BY modulo_id
             ORDER BY total DESC
             LIMIT 10",
            $fecha_desde
        ));

        $actividad_por_dia = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(fecha) as dia, COUNT(*) as total
             FROM {$this->nombre_tabla}
             WHERE fecha >= %s
             GROUP BY DATE(fecha)
             ORDER BY dia ASC",
            $fecha_desde
        ));

        $total_periodo = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->nombre_tabla} WHERE fecha >= %s",
            $fecha_desde
        ));

        return [
            'periodo_dias'     => $dias,
            'total'            => intval($total_periodo),
            'por_tipo'         => $resumen_por_tipo,
            'por_modulo'       => $resumen_por_modulo,
            'por_dia'          => $actividad_por_dia,
        ];
    }

    public function obtener_actividad_reciente($limite = 10) {
        global $wpdb;

        $registros = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->nombre_tabla} ORDER BY fecha DESC LIMIT %d",
            $limite
        ));

        foreach ($registros as &$registro) {
            if ($registro->usuario_id > 0) {
                $informacion_usuario = get_userdata($registro->usuario_id);
                $registro->nombre_usuario = $informacion_usuario ? $informacion_usuario->display_name : __('Usuario eliminado', 'flavor-chat-ia');
            } else {
                $registro->nombre_usuario = __('Sistema', 'flavor-chat-ia');
            }
        }
        unset($registro);

        return $registros;
    }

    // =========================================================================
    // LIMPIEZA
    // =========================================================================

    public function limpiar_registros_antiguos() {
        global $wpdb;

        $dias_retencion = apply_filters('flavor_activity_log_retention_days', 90);
        $fecha_limite = gmdate('Y-m-d H:i:s', strtotime("-{$dias_retencion} days"));

        $registros_eliminados = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->nombre_tabla} WHERE fecha < %s",
            $fecha_limite
        ));

        if ($registros_eliminados > 0) {
            flavor_chat_ia_log(
                sprintf('Activity Log: eliminados %d registros anteriores a %s', $registros_eliminados, $fecha_limite),
                'info'
            );
        }
    }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    private function obtener_ip_usuario() {
        $claves_ip = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($claves_ip as $clave_ip) {
            if (!empty($_SERVER[$clave_ip])) {
                $ip_encontrada = sanitize_text_field(wp_unslash($_SERVER[$clave_ip]));
                if (filter_var($ip_encontrada, FILTER_VALIDATE_IP)) {
                    return $ip_encontrada;
                }
            }
        }
        return '0.0.0.0';
    }

    public function obtener_modulos_con_actividad() {
        global $wpdb;

        return $wpdb->get_col(
            "SELECT DISTINCT modulo_id FROM {$this->nombre_tabla} ORDER BY modulo_id ASC"
        );
    }

    public static function desactivar_cron() {
        $timestamp_programado = wp_next_scheduled('flavor_activity_log_cleanup');
        if ($timestamp_programado) {
            wp_unschedule_event($timestamp_programado, 'flavor_activity_log_cleanup');
        }
    }
}
