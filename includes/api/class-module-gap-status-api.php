<?php
/**
 * API REST para exponer el estado de los módulos según la matriz de auditoría.
 *
 * @package Flavor_Platform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Gap_Status_API {

    const API_NAMESPACE = 'flavor-module-gaps/v1';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Restringe el acceso a auditorías internas.
     *
     * @return bool
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    public function register_routes() {
        register_rest_route(self::API_NAMESPACE, '/status', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_status'],
            'permission_callback' => [$this, 'check_admin_permission'],
            'args' => [
                'estado' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_key',
                ],
            ],
        ]);
    }

    public function get_status(WP_REST_Request $request) {
        $estado = $request->get_param('estado');
        $rows = $this->load_module_status_rows();

        if ($rows && $estado) {
            $rows = array_values(array_filter($rows, function ($item) use ($estado) {
                return strcasecmp($item['estado'] ?? '', $estado) === 0;
            }));
        }

        return rest_ensure_response([
            'success' => true,
            'summary' => $this->build_summary($rows),
            'data' => $rows,
        ]);
    }

    private function load_module_status_rows() {
        $path = $this->resolve_matrix_path();
        if (!file_exists($path)) {
            return [];
        }

        $rows = [];
        if (($handle = fopen($path, 'r')) === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return [];
        }

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 3) {
                continue;
            }
            $row = array_combine($header, $data);
            if ($row === false) {
                continue;
            }

            $rows[] = [
                'modulo' => $row['modulo'] ?? '',
                'estado' => $row['estado'] ?? '',
                'evidencia' => $row['evidencia'] ?? '',
            ];
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Resuelve la ruta de la matriz de módulos más confiable disponible.
     *
     * @return string
     */
    private function resolve_matrix_path() {
        $candidates = [
            FLAVOR_PLATFORM_PATH . 'reports/modulos_matriz_actual_2026-03-01.csv',
            FLAVOR_PLATFORM_PATH . 'reports/modulos_matriz.csv',
        ];

        foreach ($candidates as $candidate) {
            if (!$this->is_usable_csv($candidate)) {
                continue;
            }
            return $candidate;
        }

        return FLAVOR_PLATFORM_PATH . 'reports/modulos_matriz.csv';
    }

    /**
     * Verifica si un CSV existe y tiene al menos cabecera + una fila de datos.
     *
     * @param string $path
     * @return bool
     */
    private function is_usable_csv($path) {
        if (!file_exists($path) || !is_readable($path)) {
            return false;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return false;
        }

        $header = fgetcsv($handle);
        $row = fgetcsv($handle);
        fclose($handle);

        return $header !== false && $row !== false;
    }

    private function build_summary(array $rows) {
        $summary = [
            'total' => count($rows),
            'by_estado' => [],
        ];

        foreach ($rows as $row) {
            $label = $row['estado'] ?: 'unknown';
            if (!isset($summary['by_estado'][$label])) {
                $summary['by_estado'][$label] = 0;
            }
            $summary['by_estado'][$label]++;
        }

        return $summary;
    }
}

Flavor_Module_Gap_Status_API::get_instance();
