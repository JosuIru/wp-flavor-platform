<?php
/**
 * Clase de utilidades y helpers del plugin
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase con métodos de utilidad para el plugin
 */
class Flavor_Chat_Helpers {

    /**
     * Verifica si una tabla existe en la base de datos
     *
     * @param string $tabla Nombre completo de la tabla (con prefijo)
     * @return bool
     */
    public static function tabla_existe($tabla) {
        global $wpdb;

        $tabla = esc_sql($tabla);
        $result = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tabla
        ));

        return $result === $tabla;
    }

    /**
     * Obtiene el prefijo de las tablas del plugin
     *
     * @return string
     */
    public static function get_table_prefix() {
        global $wpdb;
        return $wpdb->prefix . 'flavor_';
    }

    /**
     * Formatea una fecha para mostrar
     *
     * @param string $fecha
     * @param string $formato
     * @return string
     */
    public static function formatear_fecha($fecha, $formato = '') {
        if (empty($formato)) {
            $formato = get_option('date_format') . ' ' . get_option('time_format');
        }
        return date_i18n($formato, strtotime($fecha));
    }

    /**
     * Tiempo transcurrido en formato legible
     *
     * @param string $fecha
     * @return string
     */
    public static function tiempo_transcurrido($fecha) {
        return human_time_diff(strtotime($fecha), current_time('timestamp'));
    }

    /**
     * Devuelve un color basado en un estado
     *
     * @param string $estado
     * @return string
     */
    public static function get_status_color($estado) {
        $colores = [
            'activo' => '#10b981',
            'completado' => '#10b981',
            'aprobado' => '#10b981',
            'confirmado' => '#10b981',
            'pagado' => '#10b981',
            'pendiente' => '#f59e0b',
            'en_proceso' => '#3b82f6',
            'procesando' => '#3b82f6',
            'cancelado' => '#ef4444',
            'rechazado' => '#ef4444',
            'error' => '#ef4444',
            'inactivo' => '#6b7280',
            'borrador' => '#6b7280',
        ];

        $estado_lower = strtolower($estado);
        return $colores[$estado_lower] ?? '#6b7280';
    }

    /**
     * Crea una respuesta JSON estándar para AJAX
     *
     * @param bool $success
     * @param string $message
     * @param array $data
     */
    public static function json_response($success, $message = '', $data = []) {
        wp_send_json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
