<?php
/**
 * Limpieza automática de nodos y datos obsoletos
 *
 * Gestiona el ciclo de vida de los nodos de red, incluyendo:
 * - Marcado de nodos inactivos
 * - Limpieza de conexiones pendientes antiguas
 * - Limpieza de caché expirada
 * - Rotación de tokens API expirados
 *
 * @package FlavorPlatform\Network
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Cleanup {

    /**
     * Instancia singleton
     *
     * @var Flavor_Network_Cleanup|null
     */
    private static $instance = null;

    /**
     * Días de inactividad antes de marcar un nodo como inactivo
     */
    const INACTIVITY_DAYS = 30;

    /**
     * Días antes de eliminar conexiones pendientes
     */
    const PENDING_CONNECTION_DAYS = 90;

    /**
     * Hook del cron de limpieza diaria
     */
    const CLEANUP_CRON_HOOK = 'flavor_network_daily_cleanup';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Network_Cleanup
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado para singleton
     */
    private function __construct() {
        // Registrar el hook del cron
        add_action(self::CLEANUP_CRON_HOOK, [$this, 'run_cleanup']);

        // Programar el cron si no está programado
        if (!wp_next_scheduled(self::CLEANUP_CRON_HOOK)) {
            wp_schedule_event(time(), 'daily', self::CLEANUP_CRON_HOOK);
        }

        // Hook para limpieza manual desde admin
        add_action('wp_ajax_flavor_network_manual_cleanup', [$this, 'ajax_manual_cleanup']);
    }

    /**
     * Ejecuta todas las tareas de limpieza
     *
     * @return array Resultados de cada tarea
     */
    public function run_cleanup() {
        $resultados = [
            'timestamp'            => current_time('mysql'),
            'nodos_inactivos'      => $this->mark_inactive_nodes(),
            'conexiones_eliminadas' => $this->cleanup_pending_connections(),
            'cache_limpiada'       => $this->cleanup_expired_cache(),
            'tokens_rotados'       => $this->rotate_expired_tokens(),
            'mensajes_antiguos'    => $this->cleanup_old_read_messages(),
        ];

        // Log de resultados
        if (defined('FLAVOR_PLATFORM_DEBUG') && FLAVOR_PLATFORM_DEBUG) {
            flavor_log_debug(
                'Network Cleanup ejecutado: ' . wp_json_encode($resultados),
                'NetworkCleanup'
            );
        }

        // Hook para extensiones
        do_action('flavor_network_cleanup_completed', $resultados);

        // Guardar última ejecución
        update_option('flavor_network_last_cleanup', $resultados);

        return $resultados;
    }

    /**
     * Marca nodos como inactivos si no han sincronizado en X días
     *
     * @return int Número de nodos marcados como inactivos
     */
    private function mark_inactive_nodes() {
        global $wpdb;
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $fecha_limite = date('Y-m-d H:i:s', strtotime('-' . self::INACTIVITY_DAYS . ' days'));

        // Solo marcar nodos remotos (no el local)
        $nodos_afectados = $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla_nodos} SET estado = 'inactivo'
             WHERE estado = 'activo'
             AND es_nodo_local = 0
             AND (ultima_sincronizacion IS NULL OR ultima_sincronizacion < %s)",
            $fecha_limite
        ));

        if ($nodos_afectados > 0) {
            // Invalidar caché del directorio
            if (class_exists('Flavor_Network_Node')) {
                Flavor_Network_Node::invalidate_directory_cache();
            }

            // Hook para notificaciones
            do_action('flavor_network_nodes_marked_inactive', $nodos_afectados);
        }

        return (int) $nodos_afectados;
    }

    /**
     * Elimina conexiones pendientes antiguas
     *
     * @return int Número de conexiones eliminadas
     */
    private function cleanup_pending_connections() {
        global $wpdb;
        $tabla_conexiones = Flavor_Network_Installer::get_table_name('connections');

        $fecha_limite = date('Y-m-d H:i:s', strtotime('-' . self::PENDING_CONNECTION_DAYS . ' days'));

        $conexiones_eliminadas = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$tabla_conexiones}
             WHERE estado = 'pendiente'
             AND fecha_solicitud < %s",
            $fecha_limite
        ));

        return (int) $conexiones_eliminadas;
    }

    /**
     * Limpia transients expirados relacionados con la red
     *
     * @return int Número de transients eliminados
     */
    private function cleanup_expired_cache() {
        global $wpdb;

        // Limpiar transients expirados de network
        $transients_eliminados = $wpdb->query(
            "DELETE a, b FROM {$wpdb->options} a
             INNER JOIN {$wpdb->options} b ON b.option_name = REPLACE(a.option_name, '_timeout', '')
             WHERE a.option_name LIKE '_transient_timeout_flavor_network_%'
             AND a.option_value < " . time()
        );

        // Limpiar también transients huérfanos (sin timeout correspondiente)
        $huerfanos_eliminados = $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_flavor_network_%'
             AND option_name NOT LIKE '_transient_timeout_%'
             AND NOT EXISTS (
                 SELECT 1 FROM (SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_flavor_network_%') AS t
                 WHERE t.option_name = CONCAT('_transient_timeout_', SUBSTRING(option_name, 12))
             )"
        );

        return (int) ($transients_eliminados + $huerfanos_eliminados);
    }

    /**
     * Rota tokens API expirados
     *
     * @return int Número de tokens rotados
     */
    private function rotate_expired_tokens() {
        global $wpdb;
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        // Verificar si existen los campos de expiración
        $campo_existe = $wpdb->get_var(
            "SHOW COLUMNS FROM {$tabla_nodos} LIKE 'api_key_expires_at'"
        );

        if (!$campo_existe) {
            return 0;
        }

        // Obtener nodos con tokens expirados (solo remotos activos)
        $nodos_expirados = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre FROM {$tabla_nodos}
             WHERE es_nodo_local = 0
             AND estado = 'activo'
             AND api_key_expires_at IS NOT NULL
             AND api_key_expires_at < %s",
            current_time('mysql')
        ));

        $tokens_rotados = 0;

        foreach ($nodos_expirados as $nodo) {
            if (class_exists('Flavor_Network_Node') && method_exists('Flavor_Network_Node', 'rotate_api_key')) {
                $resultado = Flavor_Network_Node::rotate_api_key($nodo->id);
                if ($resultado) {
                    $tokens_rotados++;

                    // Hook para notificar al nodo remoto
                    do_action('flavor_network_token_rotated', $nodo->id, $resultado);
                }
            }
        }

        return $tokens_rotados;
    }

    /**
     * Limpia mensajes antiguos ya leídos
     *
     * @return int Número de mensajes eliminados
     */
    private function cleanup_old_read_messages() {
        global $wpdb;
        $tabla_mensajes = Flavor_Network_Installer::get_table_name('messages');

        // Mantener mensajes de los últimos 180 días o no leídos
        $fecha_limite = date('Y-m-d H:i:s', strtotime('-180 days'));

        $mensajes_eliminados = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$tabla_mensajes}
             WHERE leido = 1
             AND fecha_envio < %s",
            $fecha_limite
        ));

        return (int) $mensajes_eliminados;
    }

    /**
     * Obtiene estadísticas del estado de la red
     *
     * @return array Estadísticas
     */
    public function get_network_stats() {
        global $wpdb;
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');
        $tabla_conexiones = Flavor_Network_Installer::get_table_name('connections');

        $stats = [
            'nodos_totales'       => 0,
            'nodos_activos'       => 0,
            'nodos_inactivos'     => 0,
            'nodos_pendientes'    => 0,
            'conexiones_activas'  => 0,
            'conexiones_pendientes' => 0,
            'ultima_limpieza'     => get_option('flavor_network_last_cleanup', []),
            'proxima_limpieza'    => wp_next_scheduled(self::CLEANUP_CRON_HOOK),
        ];

        // Contar nodos por estado
        $nodos_por_estado = $wpdb->get_results(
            "SELECT estado, COUNT(*) as total FROM {$tabla_nodos} GROUP BY estado"
        );

        foreach ($nodos_por_estado as $registro) {
            $stats['nodos_totales'] += (int) $registro->total;
            switch ($registro->estado) {
                case 'activo':
                    $stats['nodos_activos'] = (int) $registro->total;
                    break;
                case 'inactivo':
                    $stats['nodos_inactivos'] = (int) $registro->total;
                    break;
                case 'pendiente':
                    $stats['nodos_pendientes'] = (int) $registro->total;
                    break;
            }
        }

        // Contar conexiones
        $conexiones_por_estado = $wpdb->get_results(
            "SELECT estado, COUNT(*) as total FROM {$tabla_conexiones} GROUP BY estado"
        );

        foreach ($conexiones_por_estado as $registro) {
            switch ($registro->estado) {
                case 'aprobada':
                    $stats['conexiones_activas'] = (int) $registro->total;
                    break;
                case 'pendiente':
                    $stats['conexiones_pendientes'] = (int) $registro->total;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Reactiva un nodo previamente marcado como inactivo
     *
     * @param int $nodo_id ID del nodo a reactivar
     * @return bool Éxito de la operación
     */
    public function reactivate_node($nodo_id) {
        global $wpdb;
        $tabla_nodos = Flavor_Network_Installer::get_table_name('nodes');

        $resultado = $wpdb->update(
            $tabla_nodos,
            [
                'estado'                 => 'activo',
                'ultima_sincronizacion'  => current_time('mysql'),
            ],
            ['id' => $nodo_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($resultado !== false) {
            // Invalidar caché
            if (class_exists('Flavor_Network_Node')) {
                Flavor_Network_Node::invalidate_directory_cache();
                delete_transient(Flavor_Network_Node::CACHE_PREFIX . 'node_' . $nodo_id);
            }

            do_action('flavor_network_node_reactivated', $nodo_id);
            return true;
        }

        return false;
    }

    /**
     * Manejador AJAX para limpieza manual
     */
    public function ajax_manual_cleanup() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para esta acción', 'flavor-network-communities')]);
        }

        // Verificar nonce
        check_ajax_referer('flavor_network_cleanup', 'nonce');

        // Ejecutar limpieza
        $resultados = $this->run_cleanup();

        wp_send_json_success([
            'message'    => __('Limpieza completada', 'flavor-network-communities'),
            'resultados' => $resultados,
        ]);
    }

    /**
     * Desactiva el cron de limpieza
     * Llamar al desactivar el addon
     */
    public static function deactivate() {
        wp_clear_scheduled_hook(self::CLEANUP_CRON_HOOK);
    }

    /**
     * Fuerza la ejecución inmediata de la limpieza
     *
     * @return array Resultados de la limpieza
     */
    public function force_cleanup() {
        return $this->run_cleanup();
    }
}
