<?php
/**
 * Admin page that shows module gap statuses.
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Gap_Admin {

    const PAGE_SLUG = 'flavor-module-gaps';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu() {
        add_menu_page(
            __('Estado de módulos', 'flavor-chat-ia'),
            __('Estado de módulos', 'flavor-chat-ia'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page'],
            'dashicons-chart-bar',
            80
        );
    }

    public function render_page() {
        $estado = isset($_GET['estado']) ? sanitize_key($_GET['estado']) : '';
        $rows = $this->load_module_rows();

        if ($estado) {
            $rows = array_values(array_filter($rows, function ($item) use ($estado) {
                return strcasecmp($item['estado'], $estado) === 0;
            }));
        }

        $summary = $this->build_summary($rows);

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Estado de los módulos', 'flavor-chat-ia'); ?></h1>
            <p><?php esc_html_e('Esta tabla resume los módulos que todavía tienen gaps o TODOs pendientes según la matriz de auditoría.', 'flavor-chat-ia'); ?></p>
            <form method="get" class="flavor-module-gap-filter">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>">
                <label>
                    <?php esc_html_e('Filtrar por estado', 'flavor-chat-ia'); ?>:
                    <select name="estado">
                        <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($this->get_estado_options() as $option): ?>
                            <option value="<?php echo esc_attr($option); ?>" <?php selected($estado, $option); ?>>
                                <?php echo esc_html(ucfirst($option)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="button"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button>
            </form>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Módulo', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Evidencia / Ubicación', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="3"><?php esc_html_e('No hay resultados para ese estado.', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><strong><?php echo esc_html($row['modulo']); ?></strong></td>
                            <td><?php echo wp_kses_post($this->render_state_label($row['estado'])); ?></td>
                            <td><?php echo esc_html($row['evidencia']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="flavor-module-gap-summary">
                <p><?php esc_html_e('Resumen:', 'flavor-chat-ia'); ?>
                    <?php printf(
                        esc_html__('Total: %d módulos · %s', 'flavor-chat-ia'),
                        $summary['total'],
                        esc_html($this->render_summary_by_estado($summary['by_estado']))
                    ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    private function render_state_label($estado) {
        if (!$estado) {
            return __('Desconocido', 'flavor-chat-ia');
        }

        switch (strtolower($estado)) {
            case 'sin todos detectados':
                return '<span style="color:green;">' . esc_html($estado) . '</span>';
            case 'parcial':
                return '<span style="color:#d67500;">' . esc_html($estado) . '</span>';
            case 'placeholder':
                return '<span style="color:#c00;">' . esc_html($estado) . '</span>';
            default:
                return esc_html($estado);
        }
    }

    private function render_summary_by_estado(array $byEstado) {
        $parts = [];
        foreach ($byEstado as $label => $count) {
            $parts[] = sprintf('%s: %d', ucfirst($label), $count);
        }
        return implode(' · ', $parts);
    }

    private function build_summary(array $rows) {
        $summary = [
            'total' => count($rows),
            'by_estado' => [],
        ];

        foreach ($rows as $row) {
            $label = $row['estado'] ?: 'desconocido';
            if (!isset($summary['by_estado'][$label])) {
                $summary['by_estado'][$label] = 0;
            }
            $summary['by_estado'][$label]++;
        }

        return $summary;
    }

    private function load_module_rows() {
        $path = FLAVOR_CHAT_IA_PATH . 'reports/modulos_matriz.csv';
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

    private function get_estado_options() {
        $rows = $this->load_module_rows();
        $options = [];
        foreach ($rows as $row) {
            $estado = sanitize_key(strtolower($row['estado']));
            if ($estado && !in_array($estado, $options, true)) {
                $options[] = $estado;
            }
        }
        return $options;
    }
}
