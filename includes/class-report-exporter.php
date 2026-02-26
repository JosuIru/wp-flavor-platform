<?php
/**
 * Exportador de Reportes de Analytics
 *
 * @package Flavor_Chat_IA
 * @since 1.8.0
 */

namespace Flavor_Chat_IA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para exportar reportes de analytics a CSV y PDF
 */
class Report_Exporter {

    /**
     * Instancia singleton
     *
     * @var Report_Exporter|null
     */
    private static $instance = null;

    /**
     * Directorio de exportaciones
     *
     * @var string
     */
    private $export_dir;

    /**
     * URL del directorio de exportaciones
     *
     * @var string
     */
    private $export_url;

    /**
     * Constructor privado
     */
    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->export_dir = $upload_dir['basedir'] . '/flavor-exports/';
        $this->export_url = $upload_dir['baseurl'] . '/flavor-exports/';

        // Crear directorio si no existe
        if (!file_exists($this->export_dir)) {
            wp_mkdir_p($this->export_dir);
            // Crear .htaccess para proteger archivos
            file_put_contents($this->export_dir . '.htaccess', 'deny from all');
        }

        // Limpiar exportaciones antiguas
        add_action('flavor_cleanup_exports', array($this, 'cleanup_old_exports'));
        if (!wp_next_scheduled('flavor_cleanup_exports')) {
            wp_schedule_event(time(), 'daily', 'flavor_cleanup_exports');
        }
    }

    /**
     * Obtener instancia singleton
     *
     * @return Report_Exporter
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Exportar datos a CSV
     *
     * @param array  $data     Datos a exportar
     * @param string $filename Nombre del archivo (sin extensión)
     * @param array  $headers  Cabeceras de columnas
     * @return array|WP_Error URL y nombre del archivo o error
     */
    public function export_csv($data, $filename = 'export', $headers = array()) {
        if (empty($data)) {
            return new \WP_Error('empty_data', __('No hay datos para exportar', 'flavor-chat-ia'));
        }

        $filename = sanitize_file_name($filename) . '-' . date('Y-m-d-His') . '.csv';
        $filepath = $this->export_dir . $filename;

        $file_handle = fopen($filepath, 'w');
        if (!$file_handle) {
            return new \WP_Error('file_error', __('No se pudo crear el archivo', 'flavor-chat-ia'));
        }

        // BOM para UTF-8
        fprintf($file_handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeceras
        if (!empty($headers)) {
            fputcsv($file_handle, $headers, ';');
        } elseif (!empty($data[0]) && is_array($data[0])) {
            fputcsv($file_handle, array_keys($data[0]), ';');
        }

        // Datos
        foreach ($data as $row) {
            if (is_array($row)) {
                fputcsv($file_handle, $row, ';');
            }
        }

        fclose($file_handle);

        // Generar token temporal para descarga
        $token = $this->generate_download_token($filename);

        return array(
            'url'      => add_query_arg(array(
                'action' => 'flavor_download_export',
                'file'   => $filename,
                'token'  => $token,
            ), admin_url('admin-ajax.php')),
            'filename' => $filename,
            'path'     => $filepath,
        );
    }

    /**
     * Exportar reporte completo de analytics
     *
     * @param string $periodo Período de tiempo
     * @param string $formato Formato de exportación (csv)
     * @return array|WP_Error
     */
    public function export_analytics_report($periodo = 'mes', $formato = 'csv') {
        global $wpdb;

        $fecha_inicio = $this->get_fecha_inicio($periodo);
        $datos_reporte = array();

        // KPIs generales
        $datos_reporte['kpis'] = $this->obtener_kpis_export($fecha_inicio);

        // Usuarios activos
        $datos_reporte['usuarios'] = $this->obtener_usuarios_export($fecha_inicio);

        // Contenido popular
        $datos_reporte['contenido'] = $this->obtener_contenido_export($fecha_inicio);

        // Actividad por módulo
        $datos_reporte['modulos'] = $this->obtener_modulos_export($fecha_inicio);

        if ($formato === 'csv') {
            return $this->generar_csv_completo($datos_reporte, $periodo);
        }

        return new \WP_Error('formato_invalido', __('Formato no soportado', 'flavor-chat-ia'));
    }

    /**
     * Obtener fecha de inicio según período
     *
     * @param string $periodo
     * @return string
     */
    private function get_fecha_inicio($periodo) {
        switch ($periodo) {
            case 'hoy':
                return date('Y-m-d 00:00:00');
            case 'semana':
                return date('Y-m-d 00:00:00', strtotime('-7 days'));
            case 'ano':
                return date('Y-m-d 00:00:00', strtotime('-1 year'));
            case 'mes':
            default:
                return date('Y-m-d 00:00:00', strtotime('-30 days'));
        }
    }

    /**
     * Obtener KPIs para exportación
     *
     * @param string $fecha_inicio
     * @return array
     */
    private function obtener_kpis_export($fecha_inicio) {
        global $wpdb;

        $tabla_activity = $wpdb->prefix . 'flavor_chat_activity_log';
        $tabla_engagement = $wpdb->prefix . 'flavor_chat_social_engagement';

        $usuarios_activos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$tabla_activity} WHERE created_at >= %s",
            $fecha_inicio
        ));

        $usuarios_totales = $wpdb->get_var(
            "SELECT COUNT(ID) FROM {$wpdb->users}"
        );

        $publicaciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_activity}
             WHERE action_type = 'publicacion' AND created_at >= %s",
            $fecha_inicio
        ));

        $comentarios = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_activity}
             WHERE action_type = 'comentario' AND created_at >= %s",
            $fecha_inicio
        ));

        $interacciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_engagement} WHERE created_at >= %s",
            $fecha_inicio
        ));

        $nuevos_usuarios = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(ID) FROM {$wpdb->users} WHERE user_registered >= %s",
            $fecha_inicio
        ));

        return array(
            array(
                'Métrica'      => __('Usuarios Activos', 'flavor-chat-ia'),
                'Valor'        => intval($usuarios_activos),
                'Porcentaje'   => $usuarios_totales > 0
                    ? round(($usuarios_activos / $usuarios_totales) * 100, 2) . '%'
                    : '0%',
            ),
            array(
                'Métrica'      => __('Usuarios Totales', 'flavor-chat-ia'),
                'Valor'        => intval($usuarios_totales),
                'Porcentaje'   => '100%',
            ),
            array(
                'Métrica'      => __('Publicaciones', 'flavor-chat-ia'),
                'Valor'        => intval($publicaciones),
                'Porcentaje'   => '-',
            ),
            array(
                'Métrica'      => __('Comentarios', 'flavor-chat-ia'),
                'Valor'        => intval($comentarios),
                'Porcentaje'   => '-',
            ),
            array(
                'Métrica'      => __('Interacciones', 'flavor-chat-ia'),
                'Valor'        => intval($interacciones),
                'Porcentaje'   => '-',
            ),
            array(
                'Métrica'      => __('Nuevos Usuarios', 'flavor-chat-ia'),
                'Valor'        => intval($nuevos_usuarios),
                'Porcentaje'   => '-',
            ),
        );
    }

    /**
     * Obtener usuarios para exportación
     *
     * @param string $fecha_inicio
     * @return array
     */
    private function obtener_usuarios_export($fecha_inicio) {
        global $wpdb;

        $tabla_activity = $wpdb->prefix . 'flavor_chat_activity_log';
        $tabla_reputacion = $wpdb->prefix . 'flavor_chat_social_reputacion';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT
                u.ID,
                u.display_name,
                u.user_email,
                COALESCE(r.puntos_totales, 0) as puntos,
                COALESCE(r.nivel, 'nuevo') as nivel,
                COUNT(CASE WHEN a.action_type = 'publicacion' THEN 1 END) as publicaciones,
                COUNT(CASE WHEN a.action_type = 'comentario' THEN 1 END) as comentarios
             FROM {$wpdb->users} u
             LEFT JOIN {$tabla_reputacion} r ON u.ID = r.user_id
             LEFT JOIN {$tabla_activity} a ON u.ID = a.user_id AND a.created_at >= %s
             GROUP BY u.ID
             ORDER BY puntos DESC
             LIMIT 100",
            $fecha_inicio
        ), ARRAY_A);

        $datos_formateados = array();
        foreach ($resultados as $usuario) {
            $datos_formateados[] = array(
                'ID'            => $usuario['ID'],
                'Nombre'        => $usuario['display_name'],
                'Email'         => $usuario['user_email'],
                'Puntos'        => $usuario['puntos'],
                'Nivel'         => ucfirst($usuario['nivel']),
                'Publicaciones' => $usuario['publicaciones'],
                'Comentarios'   => $usuario['comentarios'],
            );
        }

        return $datos_formateados;
    }

    /**
     * Obtener contenido para exportación
     *
     * @param string $fecha_inicio
     * @return array
     */
    private function obtener_contenido_export($fecha_inicio) {
        global $wpdb;

        $tabla_engagement = $wpdb->prefix . 'flavor_chat_social_engagement';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT
                p.ID,
                p.post_title,
                p.post_type,
                p.post_date,
                u.display_name as autor,
                COUNT(CASE WHEN e.type = 'like' THEN 1 END) as likes,
                COUNT(CASE WHEN e.type = 'comentario' THEN 1 END) as comentarios
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
             LEFT JOIN {$tabla_engagement} e ON p.ID = e.post_id AND e.created_at >= %s
             WHERE p.post_status = 'publish'
               AND p.post_date >= %s
             GROUP BY p.ID
             ORDER BY likes DESC, comentarios DESC
             LIMIT 50",
            $fecha_inicio,
            $fecha_inicio
        ), ARRAY_A);

        $datos_formateados = array();
        foreach ($resultados as $contenido) {
            $datos_formateados[] = array(
                'ID'          => $contenido['ID'],
                'Título'      => $contenido['post_title'],
                'Tipo'        => $contenido['post_type'],
                'Autor'       => $contenido['autor'],
                'Fecha'       => $contenido['post_date'],
                'Likes'       => $contenido['likes'],
                'Comentarios' => $contenido['comentarios'],
            );
        }

        return $datos_formateados;
    }

    /**
     * Obtener estadísticas de módulos para exportación
     *
     * @param string $fecha_inicio
     * @return array
     */
    private function obtener_modulos_export($fecha_inicio) {
        global $wpdb;

        $modulos_config = apply_filters('flavor_modules_config', array());
        $datos_formateados = array();

        foreach ($modulos_config as $slug => $config) {
            if (empty($config['post_type'])) {
                continue;
            }

            $post_type = $config['post_type'];
            $entradas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                 WHERE post_type = %s AND post_status = 'publish' AND post_date >= %s",
                $post_type,
                $fecha_inicio
            ));

            $datos_formateados[] = array(
                'Módulo'       => $config['name'] ?? $slug,
                'Post Type'    => $post_type,
                'Entradas'     => intval($entradas),
                'Estado'       => !empty($config['enabled']) ? 'Activo' : 'Inactivo',
            );
        }

        return $datos_formateados;
    }

    /**
     * Generar CSV completo con múltiples hojas
     *
     * @param array  $datos
     * @param string $periodo
     * @return array|WP_Error
     */
    private function generar_csv_completo($datos, $periodo) {
        $filename = 'analytics-report-' . $periodo . '-' . date('Y-m-d-His') . '.csv';
        $filepath = $this->export_dir . $filename;

        $file_handle = fopen($filepath, 'w');
        if (!$file_handle) {
            return new \WP_Error('file_error', __('No se pudo crear el archivo', 'flavor-chat-ia'));
        }

        // BOM para UTF-8
        fprintf($file_handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Título del reporte
        fputcsv($file_handle, array('REPORTE DE ANALYTICS - ' . strtoupper($periodo)), ';');
        fputcsv($file_handle, array('Generado: ' . date('Y-m-d H:i:s')), ';');
        fputcsv($file_handle, array(''), ';');

        // Sección KPIs
        fputcsv($file_handle, array('=== KPIs PRINCIPALES ==='), ';');
        if (!empty($datos['kpis'])) {
            fputcsv($file_handle, array_keys($datos['kpis'][0]), ';');
            foreach ($datos['kpis'] as $row) {
                fputcsv($file_handle, $row, ';');
            }
        }
        fputcsv($file_handle, array(''), ';');

        // Sección Usuarios
        fputcsv($file_handle, array('=== USUARIOS MÁS ACTIVOS ==='), ';');
        if (!empty($datos['usuarios'])) {
            fputcsv($file_handle, array_keys($datos['usuarios'][0]), ';');
            foreach ($datos['usuarios'] as $row) {
                fputcsv($file_handle, $row, ';');
            }
        }
        fputcsv($file_handle, array(''), ';');

        // Sección Contenido
        fputcsv($file_handle, array('=== CONTENIDO POPULAR ==='), ';');
        if (!empty($datos['contenido'])) {
            fputcsv($file_handle, array_keys($datos['contenido'][0]), ';');
            foreach ($datos['contenido'] as $row) {
                fputcsv($file_handle, $row, ';');
            }
        }
        fputcsv($file_handle, array(''), ';');

        // Sección Módulos
        fputcsv($file_handle, array('=== ESTADÍSTICAS POR MÓDULO ==='), ';');
        if (!empty($datos['modulos'])) {
            fputcsv($file_handle, array_keys($datos['modulos'][0]), ';');
            foreach ($datos['modulos'] as $row) {
                fputcsv($file_handle, $row, ';');
            }
        }

        fclose($file_handle);

        // Generar token temporal para descarga
        $token = $this->generate_download_token($filename);

        return array(
            'url'      => add_query_arg(array(
                'action' => 'flavor_download_export',
                'file'   => $filename,
                'token'  => $token,
            ), admin_url('admin-ajax.php')),
            'filename' => $filename,
            'path'     => $filepath,
        );
    }

    /**
     * Generar token de descarga temporal
     *
     * @param string $filename
     * @return string
     */
    private function generate_download_token($filename) {
        $token = wp_generate_password(32, false);
        $tokens = get_transient('flavor_export_tokens') ?: array();
        $tokens[$token] = array(
            'filename' => $filename,
            'user_id'  => get_current_user_id(),
            'expires'  => time() + HOUR_IN_SECONDS,
        );
        set_transient('flavor_export_tokens', $tokens, HOUR_IN_SECONDS);
        return $token;
    }

    /**
     * Validar token de descarga
     *
     * @param string $token
     * @param string $filename
     * @return bool
     */
    public function validate_download_token($token, $filename) {
        $tokens = get_transient('flavor_export_tokens') ?: array();

        if (empty($tokens[$token])) {
            return false;
        }

        $token_data = $tokens[$token];

        if ($token_data['filename'] !== $filename) {
            return false;
        }

        if ($token_data['expires'] < time()) {
            return false;
        }

        if ($token_data['user_id'] !== get_current_user_id()) {
            return false;
        }

        return true;
    }

    /**
     * Servir archivo de descarga
     *
     * @param string $filename
     */
    public function serve_download($filename) {
        $filepath = $this->export_dir . sanitize_file_name($filename);

        if (!file_exists($filepath)) {
            wp_die(__('Archivo no encontrado', 'flavor-chat-ia'));
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        readfile($filepath);
        exit;
    }

    /**
     * Limpiar exportaciones antiguas (más de 24 horas)
     */
    public function cleanup_old_exports() {
        $files = glob($this->export_dir . '*.csv');
        $limite = time() - DAY_IN_SECONDS;

        foreach ($files as $file) {
            if (filemtime($file) < $limite) {
                unlink($file);
            }
        }
    }
}
