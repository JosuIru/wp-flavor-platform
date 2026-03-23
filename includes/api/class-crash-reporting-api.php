<?php
/**
 * API de Crash Reporting para apps móviles
 * 
 * Gestiona reportes de errores y crashes de las apps Flutter.
 * 
 * @package Flavor_Chat_IA
 * @subpackage API
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Flavor_Crash_Reporting_API
 * 
 * Endpoints para crash reporting:
 * - POST /crashes - Reportar un crash
 * - POST /crashes/batch - Reportar múltiples crashes
 * - GET /crashes - Listar crashes (admin)
 * - GET /crashes/{id} - Detalle de crash
 * - GET /crashes/stats - Estadísticas de crashes
 * - POST /crashes/{id}/resolve - Marcar como resuelto
 * - DELETE /crashes/{id} - Eliminar crash
 * - GET /crashes/export - Exportar crashes
 */
class Flavor_Crash_Reporting_API {

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor-app/v2';

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Nombre de la opción para almacenar crashes
     */
    const OPTION_CRASHES = 'flavor_app_crashes';

    /**
     * Nombre de la opción para estadísticas
     */
    const OPTION_CRASH_STATS = 'flavor_app_crash_stats';

    /**
     * Máximo de crashes a almacenar
     */
    const MAX_CRASHES = 5000;

    /**
     * Días de retención
     */
    const RETENTION_DAYS = 90;

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        
        // Limpieza programada
        if (!wp_next_scheduled('flavor_cleanup_old_crashes')) {
            wp_schedule_event(time(), 'daily', 'flavor_cleanup_old_crashes');
        }
        add_action('flavor_cleanup_old_crashes', array($this, 'cleanup_old_crashes'));
    }

    /**
     * Registra las rutas de la API
     */
    public function register_routes() {
        // POST /crashes - Reportar un crash
        register_rest_route(self::NAMESPACE, '/crashes', array(
            'methods' => 'POST',
            'callback' => array($this, 'report_crash'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'error_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Tipo de error (crash, exception, anr, etc.)',
                ),
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Mensaje de error',
                ),
                'stack_trace' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Stack trace del error',
                ),
                'device_id' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'app_version' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'platform' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'os_version' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'device_model' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'context' => array(
                    'required' => false,
                    'type' => 'object',
                    'description' => 'Contexto adicional (screen, action, etc.)',
                ),
            ),
        ));

        // POST /crashes/batch - Reportar múltiples crashes
        register_rest_route(self::NAMESPACE, '/crashes/batch', array(
            'methods' => 'POST',
            'callback' => array($this, 'report_crashes_batch'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'crashes' => array(
                    'required' => true,
                    'type' => 'array',
                    'description' => 'Array de crashes a reportar',
                ),
            ),
        ));

        // GET /crashes - Listar crashes
        register_rest_route(self::NAMESPACE, '/crashes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_crashes'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'type' => 'integer',
                ),
                'per_page' => array(
                    'default' => 50,
                    'type' => 'integer',
                ),
                'error_type' => array(
                    'type' => 'string',
                    'description' => 'Filtrar por tipo de error',
                ),
                'status' => array(
                    'type' => 'string',
                    'enum' => array('open', 'resolved', 'ignored'),
                    'description' => 'Filtrar por estado',
                ),
                'platform' => array(
                    'type' => 'string',
                    'description' => 'Filtrar por plataforma',
                ),
                'from_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                ),
                'to_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                ),
            ),
        ));

        // GET /crashes/stats - Estadísticas (ANTES de {id} para evitar conflicto)
        register_rest_route(self::NAMESPACE, '/crashes/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_crash_stats'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'period' => array(
                    'type' => 'string',
                    'enum' => array('24h', '7d', '30d', '90d'),
                    'default' => '7d',
                ),
            ),
        ));

        // GET /crashes/groups - Agrupar crashes similares (ANTES de {id})
        register_rest_route(self::NAMESPACE, '/crashes/groups', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_crash_groups'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        // GET /crashes/export - Exportar crashes (ANTES de {id})
        register_rest_route(self::NAMESPACE, '/crashes/export', array(
            'methods' => 'GET',
            'callback' => array($this, 'export_crashes'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'format' => array(
                    'type' => 'string',
                    'enum' => array('json', 'csv'),
                    'default' => 'json',
                ),
                'from_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                ),
                'to_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                ),
            ),
        ));

        // GET /crashes/{id} - Detalle de crash
        register_rest_route(self::NAMESPACE, '/crashes/(?P<id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_crash'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));

        // POST /crashes/{id}/resolve - Marcar como resuelto
        register_rest_route(self::NAMESPACE, '/crashes/(?P<id>[a-zA-Z0-9_-]+)/resolve', array(
            'methods' => 'POST',
            'callback' => array($this, 'resolve_crash'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'resolution_notes' => array(
                    'type' => 'string',
                    'description' => 'Notas de resolución',
                ),
            ),
        ));

        // POST /crashes/{id}/ignore - Marcar como ignorado
        register_rest_route(self::NAMESPACE, '/crashes/(?P<id>[a-zA-Z0-9_-]+)/ignore', array(
            'methods' => 'POST',
            'callback' => array($this, 'ignore_crash'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        // DELETE /crashes/{id} - Eliminar crash
        register_rest_route(self::NAMESPACE, '/crashes/(?P<id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_crash'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
    }

    /**
     * Verifica permisos de API (key válida)
     */
    public function check_api_permission($request) {
        $api_key = $request->get_header('X-VBP-Key');
        if (empty($api_key)) {
            $api_key = $request->get_param('api_key');
        }
        return $api_key === 'flavor-vbp-2024';
    }

    /**
     * Verifica permisos de administrador
     */
    public function check_admin_permission($request) {
        // Primero verificar API key
        if (!$this->check_api_permission($request)) {
            return false;
        }
        
        // Para endpoints admin, verificar también usuario o permitir con API key
        return true; // API key válida es suficiente para admin endpoints en este contexto
    }

    /**
     * POST /crashes - Reportar un crash
     */
    public function report_crash($request) {
        $crash_data = array(
            'id' => 'crash_' . uniqid() . '_' . time(),
            'error_type' => sanitize_text_field($request->get_param('error_type')),
            'message' => sanitize_text_field($request->get_param('message')),
            'stack_trace' => $request->get_param('stack_trace') ? sanitize_textarea_field($request->get_param('stack_trace')) : null,
            'device_id' => sanitize_text_field($request->get_param('device_id')),
            'app_version' => sanitize_text_field($request->get_param('app_version') ?: 'unknown'),
            'platform' => sanitize_text_field($request->get_param('platform') ?: 'unknown'),
            'os_version' => sanitize_text_field($request->get_param('os_version') ?: 'unknown'),
            'device_model' => sanitize_text_field($request->get_param('device_model') ?: 'unknown'),
            'context' => $request->get_param('context') ?: array(),
            'status' => 'open',
            'fingerprint' => $this->generate_fingerprint($request->get_param('error_type'), $request->get_param('message'), $request->get_param('stack_trace')),
            'occurred_at' => current_time('mysql'),
            'reported_at' => current_time('mysql'),
        );

        $this->save_crash($crash_data);
        $this->update_stats($crash_data);

        // Notificar si es crítico
        if (in_array($crash_data['error_type'], array('crash', 'fatal', 'anr'))) {
            $this->notify_critical_crash($crash_data);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'crash_id' => $crash_data['id'],
            'message' => 'Crash reportado correctamente',
        ), 201);
    }

    /**
     * POST /crashes/batch - Reportar múltiples crashes
     */
    public function report_crashes_batch($request) {
        $crashes_input = $request->get_param('crashes');
        
        if (!is_array($crashes_input) || empty($crashes_input)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'No se proporcionaron crashes',
            ), 400);
        }

        $processed = 0;
        $crash_ids = array();

        foreach ($crashes_input as $crash_input) {
            if (empty($crash_input['error_type']) || empty($crash_input['message']) || empty($crash_input['device_id'])) {
                continue;
            }

            $crash_data = array(
                'id' => 'crash_' . uniqid() . '_' . time() . '_' . $processed,
                'error_type' => sanitize_text_field($crash_input['error_type']),
                'message' => sanitize_text_field($crash_input['message']),
                'stack_trace' => isset($crash_input['stack_trace']) ? sanitize_textarea_field($crash_input['stack_trace']) : null,
                'device_id' => sanitize_text_field($crash_input['device_id']),
                'app_version' => sanitize_text_field($crash_input['app_version'] ?? 'unknown'),
                'platform' => sanitize_text_field($crash_input['platform'] ?? 'unknown'),
                'os_version' => sanitize_text_field($crash_input['os_version'] ?? 'unknown'),
                'device_model' => sanitize_text_field($crash_input['device_model'] ?? 'unknown'),
                'context' => $crash_input['context'] ?? array(),
                'status' => 'open',
                'fingerprint' => $this->generate_fingerprint($crash_input['error_type'], $crash_input['message'], $crash_input['stack_trace'] ?? ''),
                'occurred_at' => isset($crash_input['timestamp']) ? sanitize_text_field($crash_input['timestamp']) : current_time('mysql'),
                'reported_at' => current_time('mysql'),
            );

            $this->save_crash($crash_data);
            $this->update_stats($crash_data);
            $crash_ids[] = $crash_data['id'];
            $processed++;
        }

        return new WP_REST_Response(array(
            'success' => true,
            'processed' => $processed,
            'crash_ids' => $crash_ids,
        ), 201);
    }

    /**
     * GET /crashes - Listar crashes
     */
    public function get_crashes($request) {
        $crashes = get_option(self::OPTION_CRASHES, array());
        
        // Filtros
        $error_type = $request->get_param('error_type');
        $status = $request->get_param('status');
        $platform = $request->get_param('platform');
        $from_date = $request->get_param('from_date');
        $to_date = $request->get_param('to_date');

        // Aplicar filtros
        $filtered = array_filter($crashes, function($crash) use ($error_type, $status, $platform, $from_date, $to_date) {
            if ($error_type && $crash['error_type'] !== $error_type) {
                return false;
            }
            if ($status && $crash['status'] !== $status) {
                return false;
            }
            if ($platform && $crash['platform'] !== $platform) {
                return false;
            }
            if ($from_date) {
                $crash_date = date('Y-m-d', strtotime($crash['occurred_at']));
                if ($crash_date < $from_date) {
                    return false;
                }
            }
            if ($to_date) {
                $crash_date = date('Y-m-d', strtotime($crash['occurred_at']));
                if ($crash_date > $to_date) {
                    return false;
                }
            }
            return true;
        });

        // Ordenar por fecha descendente
        usort($filtered, function($a, $b) {
            return strtotime($b['occurred_at']) - strtotime($a['occurred_at']);
        });

        // Paginación
        $page = intval($request->get_param('page'));
        $per_page = intval($request->get_param('per_page'));
        $total = count($filtered);
        $offset = ($page - 1) * $per_page;
        $items = array_slice($filtered, $offset, $per_page);

        return new WP_REST_Response(array(
            'success' => true,
            'crashes' => array_values($items),
            'pagination' => array(
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page),
            ),
        ));
    }

    /**
     * GET /crashes/{id} - Detalle de crash
     */
    public function get_crash($request) {
        $crash_id = $request->get_param('id');
        $crashes = get_option(self::OPTION_CRASHES, array());

        $crash = null;
        foreach ($crashes as $c) {
            if ($c['id'] === $crash_id) {
                $crash = $c;
                break;
            }
        }

        if (!$crash) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Crash no encontrado',
            ), 404);
        }

        // Buscar crashes similares
        $similar = array_filter($crashes, function($c) use ($crash) {
            return $c['id'] !== $crash['id'] && $c['fingerprint'] === $crash['fingerprint'];
        });

        return new WP_REST_Response(array(
            'success' => true,
            'crash' => $crash,
            'similar_count' => count($similar),
            'similar_crashes' => array_slice(array_values($similar), 0, 10),
        ));
    }

    /**
     * POST /crashes/{id}/resolve - Marcar como resuelto
     */
    public function resolve_crash($request) {
        $crash_id = $request->get_param('id');
        $resolution_notes = sanitize_textarea_field($request->get_param('resolution_notes') ?: '');
        
        $crashes = get_option(self::OPTION_CRASHES, array());
        $found = false;

        foreach ($crashes as &$crash) {
            if ($crash['id'] === $crash_id) {
                $crash['status'] = 'resolved';
                $crash['resolved_at'] = current_time('mysql');
                $crash['resolution_notes'] = $resolution_notes;
                $found = true;
                break;
            }
        }

        if (!$found) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Crash no encontrado',
            ), 404);
        }

        update_option(self::OPTION_CRASHES, $crashes);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Crash marcado como resuelto',
        ));
    }

    /**
     * POST /crashes/{id}/ignore - Marcar como ignorado
     */
    public function ignore_crash($request) {
        $crash_id = $request->get_param('id');
        
        $crashes = get_option(self::OPTION_CRASHES, array());
        $found = false;

        foreach ($crashes as &$crash) {
            if ($crash['id'] === $crash_id) {
                $crash['status'] = 'ignored';
                $crash['ignored_at'] = current_time('mysql');
                $found = true;
                break;
            }
        }

        if (!$found) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Crash no encontrado',
            ), 404);
        }

        update_option(self::OPTION_CRASHES, $crashes);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Crash marcado como ignorado',
        ));
    }

    /**
     * DELETE /crashes/{id} - Eliminar crash
     */
    public function delete_crash($request) {
        $crash_id = $request->get_param('id');
        
        $crashes = get_option(self::OPTION_CRASHES, array());
        $initial_count = count($crashes);

        $crashes = array_filter($crashes, function($crash) use ($crash_id) {
            return $crash['id'] !== $crash_id;
        });

        if (count($crashes) === $initial_count) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Crash no encontrado',
            ), 404);
        }

        update_option(self::OPTION_CRASHES, array_values($crashes));

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Crash eliminado',
        ));
    }

    /**
     * GET /crashes/stats - Estadísticas de crashes
     */
    public function get_crash_stats($request) {
        $period = $request->get_param('period');
        $crashes = get_option(self::OPTION_CRASHES, array());
        
        // Calcular fecha de inicio según periodo
        $now = time();
        switch ($period) {
            case '24h':
                $start_time = $now - (24 * 60 * 60);
                break;
            case '30d':
                $start_time = $now - (30 * 24 * 60 * 60);
                break;
            case '90d':
                $start_time = $now - (90 * 24 * 60 * 60);
                break;
            case '7d':
            default:
                $start_time = $now - (7 * 24 * 60 * 60);
        }

        // Filtrar crashes del periodo
        $period_crashes = array_filter($crashes, function($crash) use ($start_time) {
            return strtotime($crash['occurred_at']) >= $start_time;
        });

        // Calcular estadísticas
        $stats = array(
            'total_crashes' => count($period_crashes),
            'open_crashes' => 0,
            'resolved_crashes' => 0,
            'ignored_crashes' => 0,
            'by_type' => array(),
            'by_platform' => array(),
            'by_version' => array(),
            'by_day' => array(),
            'unique_devices' => array(),
            'top_errors' => array(),
        );

        $error_counts = array();

        foreach ($period_crashes as $crash) {
            // Por estado
            switch ($crash['status']) {
                case 'resolved':
                    $stats['resolved_crashes']++;
                    break;
                case 'ignored':
                    $stats['ignored_crashes']++;
                    break;
                default:
                    $stats['open_crashes']++;
            }

            // Por tipo
            $type = $crash['error_type'];
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = 0;
            }
            $stats['by_type'][$type]++;

            // Por plataforma
            $platform = $crash['platform'];
            if (!isset($stats['by_platform'][$platform])) {
                $stats['by_platform'][$platform] = 0;
            }
            $stats['by_platform'][$platform]++;

            // Por versión
            $version = $crash['app_version'];
            if (!isset($stats['by_version'][$version])) {
                $stats['by_version'][$version] = 0;
            }
            $stats['by_version'][$version]++;

            // Por día
            $day = date('Y-m-d', strtotime($crash['occurred_at']));
            if (!isset($stats['by_day'][$day])) {
                $stats['by_day'][$day] = 0;
            }
            $stats['by_day'][$day]++;

            // Dispositivos únicos
            $stats['unique_devices'][$crash['device_id']] = true;

            // Top errores
            $fingerprint = $crash['fingerprint'];
            if (!isset($error_counts[$fingerprint])) {
                $error_counts[$fingerprint] = array(
                    'count' => 0,
                    'message' => $crash['message'],
                    'error_type' => $crash['error_type'],
                );
            }
            $error_counts[$fingerprint]['count']++;
        }

        // Calcular dispositivos únicos
        $stats['unique_devices'] = count($stats['unique_devices']);

        // Ordenar top errores
        uasort($error_counts, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        $stats['top_errors'] = array_slice($error_counts, 0, 10, true);

        // Ordenar versiones
        uksort($stats['by_version'], 'version_compare');
        $stats['by_version'] = array_reverse($stats['by_version'], true);

        return new WP_REST_Response(array(
            'success' => true,
            'period' => $period,
            'stats' => $stats,
        ));
    }

    /**
     * GET /crashes/groups - Agrupar crashes similares
     */
    public function get_crash_groups($request) {
        $crashes = get_option(self::OPTION_CRASHES, array());
        $groups = array();

        foreach ($crashes as $crash) {
            $fingerprint = $crash['fingerprint'];
            
            if (!isset($groups[$fingerprint])) {
                $groups[$fingerprint] = array(
                    'fingerprint' => $fingerprint,
                    'message' => $crash['message'],
                    'error_type' => $crash['error_type'],
                    'count' => 0,
                    'first_seen' => $crash['occurred_at'],
                    'last_seen' => $crash['occurred_at'],
                    'status' => $crash['status'],
                    'affected_versions' => array(),
                    'affected_devices' => array(),
                );
            }

            $group = &$groups[$fingerprint];
            $group['count']++;
            
            if (strtotime($crash['occurred_at']) < strtotime($group['first_seen'])) {
                $group['first_seen'] = $crash['occurred_at'];
            }
            if (strtotime($crash['occurred_at']) > strtotime($group['last_seen'])) {
                $group['last_seen'] = $crash['occurred_at'];
            }

            if (!in_array($crash['app_version'], $group['affected_versions'])) {
                $group['affected_versions'][] = $crash['app_version'];
            }
            if (!in_array($crash['device_id'], $group['affected_devices'])) {
                $group['affected_devices'][] = $crash['device_id'];
            }
        }

        // Convertir a array y ordenar por count
        $groups_array = array_values($groups);
        usort($groups_array, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        // Simplificar affected_devices a count
        foreach ($groups_array as &$group) {
            $group['affected_devices_count'] = count($group['affected_devices']);
            unset($group['affected_devices']);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'groups' => $groups_array,
            'total_groups' => count($groups_array),
        ));
    }

    /**
     * GET /crashes/export - Exportar crashes
     */
    public function export_crashes($request) {
        $format = $request->get_param('format');
        $from_date = $request->get_param('from_date');
        $to_date = $request->get_param('to_date');

        $crashes = get_option(self::OPTION_CRASHES, array());

        // Aplicar filtros de fecha
        if ($from_date || $to_date) {
            $crashes = array_filter($crashes, function($crash) use ($from_date, $to_date) {
                $crash_date = date('Y-m-d', strtotime($crash['occurred_at']));
                if ($from_date && $crash_date < $from_date) {
                    return false;
                }
                if ($to_date && $crash_date > $to_date) {
                    return false;
                }
                return true;
            });
        }

        if ($format === 'csv') {
            $csv_lines = array();
            $csv_lines[] = 'ID,Error Type,Message,Platform,App Version,Device ID,Status,Occurred At';
            
            foreach ($crashes as $crash) {
                $csv_lines[] = sprintf(
                    '"%s","%s","%s","%s","%s","%s","%s","%s"',
                    $crash['id'],
                    $crash['error_type'],
                    str_replace('"', '""', $crash['message']),
                    $crash['platform'],
                    $crash['app_version'],
                    $crash['device_id'],
                    $crash['status'],
                    $crash['occurred_at']
                );
            }

            return new WP_REST_Response(array(
                'success' => true,
                'format' => 'csv',
                'data' => implode("\n", $csv_lines),
                'count' => count($crashes),
            ));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'format' => 'json',
            'data' => array_values($crashes),
            'count' => count($crashes),
        ));
    }

    // =========================================================================
    // MÉTODOS PRIVADOS
    // =========================================================================

    /**
     * Guarda un crash
     */
    private function save_crash($crash_data) {
        $crashes = get_option(self::OPTION_CRASHES, array());
        
        // Añadir al inicio
        array_unshift($crashes, $crash_data);

        // Limitar cantidad
        if (count($crashes) > self::MAX_CRASHES) {
            $crashes = array_slice($crashes, 0, self::MAX_CRASHES);
        }

        update_option(self::OPTION_CRASHES, $crashes);
    }

    /**
     * Actualiza estadísticas agregadas
     */
    private function update_stats($crash_data) {
        $stats = get_option(self::OPTION_CRASH_STATS, array(
            'total_crashes' => 0,
            'crashes_today' => 0,
            'last_crash_at' => null,
            'daily_stats' => array(),
        ));

        $stats['total_crashes']++;
        $stats['last_crash_at'] = $crash_data['occurred_at'];

        // Estadísticas diarias
        $today = date('Y-m-d');
        if (!isset($stats['daily_stats'][$today])) {
            $stats['daily_stats'][$today] = 0;
        }
        $stats['daily_stats'][$today]++;

        // Limpiar estadísticas antiguas (más de 90 días)
        $cutoff = date('Y-m-d', strtotime('-90 days'));
        $stats['daily_stats'] = array_filter($stats['daily_stats'], function($date) use ($cutoff) {
            return $date >= $cutoff;
        }, ARRAY_FILTER_USE_KEY);

        update_option(self::OPTION_CRASH_STATS, $stats);
    }

    /**
     * Genera fingerprint único para agrupar crashes similares
     */
    private function generate_fingerprint($error_type, $message, $stack_trace = '') {
        // Normalizar mensaje (quitar números específicos, IDs, etc.)
        $normalized_message = preg_replace('/\d+/', 'N', $message);
        $normalized_message = preg_replace('/0x[a-fA-F0-9]+/', 'ADDR', $normalized_message);
        
        // Usar primera línea del stack trace si existe
        $stack_first_line = '';
        if ($stack_trace) {
            $lines = explode("\n", $stack_trace);
            $stack_first_line = isset($lines[0]) ? trim($lines[0]) : '';
        }

        return md5($error_type . '|' . $normalized_message . '|' . $stack_first_line);
    }

    /**
     * Notifica sobre crash crítico
     */
    private function notify_critical_crash($crash_data) {
        // Obtener email del admin
        $admin_email = get_option('admin_email');
        
        // Solo notificar si hay más de 5 crashes similares en la última hora
        $crashes = get_option(self::OPTION_CRASHES, array());
        $recent_similar = array_filter($crashes, function($c) use ($crash_data) {
            return $c['fingerprint'] === $crash_data['fingerprint']
                && strtotime($c['occurred_at']) > strtotime('-1 hour');
        });

        if (count($recent_similar) >= 5) {
            $subject = sprintf('[%s] Alerta de Crashes Críticos', get_bloginfo('name'));
            $message = sprintf(
                "Se han detectado %d crashes similares en la última hora.\n\n" .
                "Tipo: %s\n" .
                "Mensaje: %s\n" .
                "Plataforma: %s\n" .
                "Versión: %s\n\n" .
                "Por favor revisa el panel de crashes para más detalles.",
                count($recent_similar),
                $crash_data['error_type'],
                $crash_data['message'],
                $crash_data['platform'],
                $crash_data['app_version']
            );

            wp_mail($admin_email, $subject, $message);
        }
    }

    /**
     * Limpia crashes antiguos
     */
    public function cleanup_old_crashes() {
        $crashes = get_option(self::OPTION_CRASHES, array());
        $cutoff = strtotime('-' . self::RETENTION_DAYS . ' days');

        $crashes = array_filter($crashes, function($crash) use ($cutoff) {
            return strtotime($crash['occurred_at']) >= $cutoff;
        });

        update_option(self::OPTION_CRASHES, array_values($crashes));
    }
}

// Inicializar la API
// Flavor_Crash_Reporting_API::get_instance();
