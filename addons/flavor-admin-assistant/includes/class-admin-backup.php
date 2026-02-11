<?php
/**
 * Sistema de Backups para Admin Assistant
 *
 * Proporciona backups automáticos antes de cambios críticos
 * y permite restaurar configuraciones anteriores
 *
 * @package ChatIAAddon
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chat_IA_Admin_Backup {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Directorio de backups
     */
    private $backup_dir;

    /**
     * Opciones que se respaldan
     */
    private $backup_options = [
        'calendario_experiencias_dias',
        'calendario_experiencias_estados',
        'calendario_experiencias_ticket_types',
        'calendario_experiencias_state_ticket_mapping',
        'calendario_experiencias_config',
        'calendario_experiencias_options',
        'calendario_experiencias_purchase_limit',
        'calendario_experiencias_festivos',
    ];

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->backup_dir = $upload_dir['basedir'] . '/calendario-ia-backups/';

        // Crear directorio si no existe
        if (!file_exists($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
            // Proteger directorio con .htaccess
            file_put_contents($this->backup_dir . '.htaccess', 'deny from all');
        }
    }

    /**
     * Crea un backup automático antes de un cambio
     *
     * @param string $operacion Descripción de la operación que se va a realizar
     * @param string $tipo Tipo de backup: 'calendario', 'estados', 'tickets', 'completo'
     * @return array Resultado con success y backup_id
     */
    public function crear_backup_automatico($operacion, $tipo = 'completo') {
        $timestamp = current_time('Y-m-d_H-i-s');
        $backup_id = 'auto_' . $timestamp . '_' . sanitize_title($tipo);

        $opciones_a_respaldar = $this->get_opciones_por_tipo($tipo);

        $backup_data = [
            'id' => $backup_id,
            'tipo' => $tipo,
            'operacion' => sanitize_text_field($operacion),
            'fecha' => current_time('mysql'),
            'timestamp' => time(),
            'usuario_id' => get_current_user_id(),
            'usuario_nombre' => wp_get_current_user()->display_name,
            'automatico' => true,
            'datos' => [],
        ];

        foreach ($opciones_a_respaldar as $opcion) {
            $backup_data['datos'][$opcion] = get_option($opcion, []);
        }

        $filename = $this->backup_dir . $backup_id . '.json';
        $resultado = file_put_contents(
            $filename,
            json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('No se pudo crear el archivo de backup', 'flavor-chat-ia'),
            ];
        }

        // Log del backup
        $this->registrar_log('BACKUP_CREADO', $backup_id, $operacion);

        // Limpiar backups antiguos (mantener últimos 50)
        $this->limpiar_backups_antiguos(50);

        return [
            'success' => true,
            'backup_id' => $backup_id,
            'archivo' => $filename,
            'fecha' => $backup_data['fecha'],
        ];
    }

    /**
     * Crea un backup manual
     *
     * @param string $descripcion Descripción del backup
     * @return array
     */
    public function crear_backup_manual($descripcion = '') {
        $timestamp = current_time('Y-m-d_H-i-s');
        $backup_id = 'manual_' . $timestamp;

        $backup_data = [
            'id' => $backup_id,
            'tipo' => 'completo',
            'operacion' => $descripcion ?: 'Backup manual',
            'fecha' => current_time('mysql'),
            'timestamp' => time(),
            'usuario_id' => get_current_user_id(),
            'usuario_nombre' => wp_get_current_user()->display_name,
            'automatico' => false,
            'datos' => [],
        ];

        foreach ($this->backup_options as $opcion) {
            $backup_data['datos'][$opcion] = get_option($opcion, []);
        }

        $filename = $this->backup_dir . $backup_id . '.json';
        $resultado = file_put_contents(
            $filename,
            json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('No se pudo crear el archivo de backup', 'flavor-chat-ia'),
            ];
        }

        $this->registrar_log('BACKUP_MANUAL', $backup_id, $descripcion);

        return [
            'success' => true,
            'backup_id' => $backup_id,
            'mensaje' => __('Backup creado correctamente', 'chat-ia-addon'),
            'fecha' => $backup_data['fecha'],
        ];
    }

    /**
     * Lista los backups disponibles
     *
     * @param int $limite Número máximo de backups a listar
     * @return array
     */
    public function listar_backups($limite = 20) {
        $archivos = glob($this->backup_dir . '*.json');

        if (empty($archivos)) {
            return [
                'success' => true,
                'total' => 0,
                'backups' => [],
            ];
        }

        // Ordenar por fecha de modificación (más recientes primero)
        usort($archivos, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $backups = [];
        $contador = 0;

        foreach ($archivos as $archivo) {
            if ($contador >= $limite) break;

            $contenido = file_get_contents($archivo);
            $datos = json_decode($contenido, true);

            if ($datos) {
                $backups[] = [
                    'id' => $datos['id'],
                    'tipo' => $datos['tipo'],
                    'operacion' => $datos['operacion'],
                    'fecha' => $datos['fecha'],
                    'automatico' => $datos['automatico'],
                    'usuario' => $datos['usuario_nombre'] ?? 'Sistema',
                    'tamano' => $this->formatear_tamano(filesize($archivo)),
                ];
                $contador++;
            }
        }

        return [
            'success' => true,
            'total' => count($archivos),
            'mostrando' => count($backups),
            'backups' => $backups,
        ];
    }

    /**
     * Restaura un backup
     *
     * @param string $backup_id ID del backup a restaurar
     * @return array
     */
    public function restaurar_backup($backup_id) {
        $backup_id = sanitize_file_name($backup_id);
        $filename = $this->backup_dir . $backup_id . '.json';

        if (!file_exists($filename)) {
            return [
                'success' => false,
                'error' => "Backup '{$backup_id}' no encontrado",
            ];
        }

        $contenido = file_get_contents($filename);
        $backup_data = json_decode($contenido, true);

        if (!$backup_data || empty($backup_data['datos'])) {
            return [
                'success' => false,
                'error' => __('Archivo de backup corrupto o vacío', 'flavor-chat-ia'),
            ];
        }

        // Crear backup de seguridad antes de restaurar
        $backup_seguridad = $this->crear_backup_automatico(
            "Antes de restaurar backup {$backup_id}",
            'completo'
        );

        // Restaurar opciones
        $opciones_restauradas = [];
        $errores = [];

        foreach ($backup_data['datos'] as $opcion => $valor) {
            $resultado = update_option($opcion, $valor);
            if ($resultado || get_option($opcion) === $valor) {
                $opciones_restauradas[] = $opcion;
            } else {
                $errores[] = $opcion;
            }
        }

        $this->registrar_log('BACKUP_RESTAURADO', $backup_id,
            'Opciones restauradas: ' . implode(', ', $opciones_restauradas));

        return [
            'success' => empty($errores),
            'mensaje' => __('Backup restaurado correctamente', 'chat-ia-addon'),
            'backup_restaurado' => $backup_id,
            'fecha_backup' => $backup_data['fecha'],
            'opciones_restauradas' => $opciones_restauradas,
            'backup_seguridad' => $backup_seguridad['backup_id'] ?? null,
            'errores' => $errores,
        ];
    }

    /**
     * Obtiene detalles de un backup específico
     *
     * @param string $backup_id
     * @return array
     */
    public function obtener_backup($backup_id) {
        $backup_id = sanitize_file_name($backup_id);
        $filename = $this->backup_dir . $backup_id . '.json';

        if (!file_exists($filename)) {
            return [
                'success' => false,
                'error' => "Backup '{$backup_id}' no encontrado",
            ];
        }

        $contenido = file_get_contents($filename);
        $backup_data = json_decode($contenido, true);

        if (!$backup_data) {
            return [
                'success' => false,
                'error' => __('Archivo de backup corrupto', 'flavor-chat-ia'),
            ];
        }

        // Resumen de contenido sin datos completos
        $resumen_datos = [];
        foreach ($backup_data['datos'] as $opcion => $valor) {
            if (is_array($valor)) {
                $resumen_datos[$opcion] = count($valor) . ' elementos';
            } else {
                $resumen_datos[$opcion] = 'valor configurado';
            }
        }

        return [
            'success' => true,
            'backup' => [
                'id' => $backup_data['id'],
                'tipo' => $backup_data['tipo'],
                'operacion' => $backup_data['operacion'],
                'fecha' => $backup_data['fecha'],
                'automatico' => $backup_data['automatico'],
                'usuario' => $backup_data['usuario_nombre'] ?? 'Sistema',
                'contenido' => $resumen_datos,
            ],
        ];
    }

    /**
     * Elimina un backup
     *
     * @param string $backup_id
     * @return array
     */
    public function eliminar_backup($backup_id) {
        $backup_id = sanitize_file_name($backup_id);
        $filename = $this->backup_dir . $backup_id . '.json';

        if (!file_exists($filename)) {
            return [
                'success' => false,
                'error' => "Backup '{$backup_id}' no encontrado",
            ];
        }

        // No permitir eliminar backups automáticos recientes (últimas 24h)
        $info = $this->obtener_backup($backup_id);
        if ($info['success'] && $info['backup']['automatico']) {
            $fecha_backup = strtotime($info['backup']['fecha']);
            if (time() - $fecha_backup < 86400) {
                return [
                    'success' => false,
                    'error' => __('No se pueden eliminar backups automáticos de las últimas 24 horas', 'flavor-chat-ia'),
                ];
            }
        }

        if (unlink($filename)) {
            $this->registrar_log('BACKUP_ELIMINADO', $backup_id, '');
            return [
                'success' => true,
                'mensaje' => "Backup '{$backup_id}' eliminado",
            ];
        }

        return [
            'success' => false,
            'error' => __('No se pudo eliminar el archivo', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene opciones a respaldar según el tipo
     */
    private function get_opciones_por_tipo($tipo) {
        switch ($tipo) {
            case 'calendario':
                return [
                    'calendario_experiencias_dias',
                    'calendario_experiencias_festivos',
                ];
            case 'estados':
                return [
                    'calendario_experiencias_estados',
                    'calendario_experiencias_state_ticket_mapping',
                ];
            case 'tickets':
                return [
                    'calendario_experiencias_ticket_types',
                    'calendario_experiencias_state_ticket_mapping',
                ];
            case 'completo':
            default:
                return $this->backup_options;
        }
    }

    /**
     * Limpia backups antiguos manteniendo los más recientes
     */
    private function limpiar_backups_antiguos($mantener = 50) {
        $archivos = glob($this->backup_dir . 'auto_*.json');

        if (count($archivos) <= $mantener) {
            return;
        }

        // Ordenar por fecha (más antiguos primero)
        usort($archivos, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Eliminar los más antiguos
        $a_eliminar = count($archivos) - $mantener;
        for ($i = 0; $i < $a_eliminar; $i++) {
            unlink($archivos[$i]);
        }
    }

    /**
     * Registra actividad en el log
     */
    private function registrar_log($accion, $backup_id, $detalle) {
        $log_file = $this->backup_dir . 'backup_history.log';
        $linea = sprintf(
            "[%s] %s | %s | Usuario: %d | %s\n",
            current_time('Y-m-d H:i:s'),
            $accion,
            $backup_id,
            get_current_user_id(),
            $detalle
        );
        file_put_contents($log_file, $linea, FILE_APPEND);
    }

    /**
     * Formatea tamaño de archivo
     */
    private function formatear_tamano($bytes) {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Obtiene el historial de cambios
     *
     * @param int $limite
     * @return array
     */
    public function obtener_historial($limite = 50) {
        $log_file = $this->backup_dir . 'backup_history.log';

        if (!file_exists($log_file)) {
            return [
                'success' => true,
                'total' => 0,
                'historial' => [],
            ];
        }

        $lineas = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lineas = array_reverse($lineas); // Más recientes primero
        $lineas = array_slice($lineas, 0, $limite);

        $historial = [];
        foreach ($lineas as $linea) {
            if (preg_match('/\[(.+?)\] (.+?) \| (.+?) \| Usuario: (\d+) \| (.*)/', $linea, $matches)) {
                $historial[] = [
                    'fecha' => $matches[1],
                    'accion' => $matches[2],
                    'backup_id' => $matches[3],
                    'usuario_id' => intval($matches[4]),
                    'detalle' => $matches[5],
                ];
            }
        }

        return [
            'success' => true,
            'total' => count($lineas),
            'historial' => $historial,
        ];
    }
}
