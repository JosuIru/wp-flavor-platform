<?php
/**
 * Exportación de Datos para Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para exportar datos en PDF y Excel
 */
class Flavor_GC_Export {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicialización
     */
    private function init() {
        add_action('admin_post_gc_exportar_consolidado', [$this, 'exportar_consolidado']);
        add_action('admin_post_gc_exportar_pedidos', [$this, 'exportar_pedidos']);
        add_action('admin_post_gc_exportar_pedidos_filtrado', [$this, 'exportar_pedidos_filtrado']);
        add_action('admin_post_gc_exportar_consumidores', [$this, 'exportar_consumidores']);
        add_action('admin_post_gc_exportar_suscripciones', [$this, 'exportar_suscripciones']);

        // AJAX para generar exportaciones
        add_action('wp_ajax_gc_generar_exportacion', [$this, 'ajax_generar_exportacion']);
    }

    /**
     * Exportar pedidos con filtros (ciclo/productor/periodo)
     */
    public function exportar_pedidos_filtrado() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'flavor-chat-ia'));
        }

        check_admin_referer('gc_exportar_pedidos_filtrado');

        $ciclo_id = absint($_GET['ciclo_id'] ?? 0);
        $productor_id = absint($_GET['productor_id'] ?? 0);
        $desde = sanitize_text_field($_GET['desde'] ?? '');
        $hasta = sanitize_text_field($_GET['hasta'] ?? '');

        $where = [];
        $params = [];

        if ($desde) {
            $where[] = 'p.fecha_pedido >= %s';
            $params[] = $desde . ' 00:00:00';
        }
        if ($hasta) {
            $where[] = 'p.fecha_pedido <= %s';
            $params[] = $hasta . ' 23:59:59';
        }
        if ($ciclo_id) {
            $where[] = 'p.ciclo_id = %d';
            $params[] = $ciclo_id;
        }
        if ($productor_id) {
            $where[] = 'pm.meta_value = %d';
            $params[] = $productor_id;
        }

        $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        $sql = "SELECT p.*, pr.post_title as producto_nombre, u.display_name as consumidor
                FROM {$tabla_pedidos} p
                LEFT JOIN {$wpdb->posts} pr ON pr.ID = p.producto_id
                LEFT JOIN {$wpdb->users} u ON u.ID = p.usuario_id
                LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.producto_id AND pm.meta_key = '_gc_productor_id'
                {$where_sql}
                ORDER BY p.fecha_pedido DESC";

        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=gc-pedidos-filtrado.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Ciclo', 'Consumidor', 'Producto', 'Cantidad', 'Precio', 'Total', 'Fecha'], ';');

        foreach ($rows as $row) {
            $ciclo = get_post($row->ciclo_id);
            fputcsv($output, [
                $ciclo ? $ciclo->post_title : ('#' . $row->ciclo_id),
                $row->consumidor ?: '',
                $row->producto_nombre ?: '',
                number_format($row->cantidad, 2, ',', '.'),
                number_format($row->precio_unitario, 2, ',', '.'),
                number_format($row->cantidad * $row->precio_unitario, 2, ',', '.'),
                $row->fecha_pedido,
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Exportar consolidado de pedidos por ciclo
     */
    public function exportar_consolidado() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'flavor-chat-ia'));
        }

        check_admin_referer('gc_exportar_consolidado');

        $ciclo_id = absint($_GET['ciclo_id'] ?? 0);
        $formato = sanitize_text_field($_GET['formato'] ?? 'excel');

        if (!$ciclo_id) {
            wp_die(__('Ciclo no especificado', 'flavor-chat-ia'));
        }

        $datos = $this->obtener_datos_consolidado($ciclo_id);

        if ($formato === 'pdf') {
            $this->generar_pdf_consolidado($datos, $ciclo_id);
        } else {
            $this->generar_excel_consolidado($datos, $ciclo_id);
        }
    }

    /**
     * Obtener datos del consolidado
     */
    private function obtener_datos_consolidado($ciclo_id) {
        global $wpdb;

        // Obtener consolidado por productor
        $consolidado = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, p.post_title as producto_nombre, pr.post_title as productor_nombre
             FROM {$wpdb->prefix}flavor_gc_consolidado c
             LEFT JOIN {$wpdb->posts} p ON c.producto_id = p.ID
             LEFT JOIN {$wpdb->posts} pr ON c.productor_id = pr.ID
             WHERE c.ciclo_id = %d
             ORDER BY pr.post_title, p.post_title",
            $ciclo_id
        ));

        // Agrupar por productor
        $por_productor = [];
        foreach ($consolidado as $item) {
            $productor_id = $item->productor_id;
            if (!isset($por_productor[$productor_id])) {
                $por_productor[$productor_id] = [
                    'nombre' => $item->productor_nombre,
                    'productos' => [],
                    'total' => 0,
                ];
            }
            $por_productor[$productor_id]['productos'][] = $item;
            $por_productor[$productor_id]['total'] += $item->total;
        }

        return [
            'ciclo' => get_post($ciclo_id),
            'por_productor' => $por_productor,
            'consolidado_raw' => $consolidado,
        ];
    }

    /**
     * Generar Excel del consolidado
     */
    private function generar_excel_consolidado($datos, $ciclo_id) {
        $ciclo_nombre = sanitize_file_name($datos['ciclo']->post_title);
        $filename = "consolidado-{$ciclo_nombre}-" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM para UTF-8 en Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabecera del documento
        fputcsv($output, ['CONSOLIDADO DE PEDIDOS'], ';');
        fputcsv($output, ['Ciclo:', $datos['ciclo']->post_title], ';');
        fputcsv($output, ['Fecha de exportación:', date_i18n('d/m/Y H:i')], ';');
        fputcsv($output, [], ';');

        foreach ($datos['por_productor'] as $productor_id => $productor_data) {
            fputcsv($output, [], ';');
            fputcsv($output, ['PRODUCTOR: ' . $productor_data['nombre']], ';');
            fputcsv($output, ['Producto', 'Cantidad', 'Unidad', 'Precio Unit.', 'Total'], ';');

            foreach ($productor_data['productos'] as $producto) {
                $unidad = get_post_meta($producto->producto_id, '_gc_unidad', true) ?: 'ud';
                $precio_unitario = $producto->cantidad_total > 0 ? $producto->total / $producto->cantidad_total : 0;

                fputcsv($output, [
                    $producto->producto_nombre,
                    number_format($producto->cantidad_total, 2, ',', ''),
                    $unidad,
                    number_format($precio_unitario, 2, ',', '') . '€',
                    number_format($producto->total, 2, ',', '') . '€',
                ], ';');
            }

            fputcsv($output, ['', '', '', 'TOTAL:', number_format($productor_data['total'], 2, ',', '') . '€'], ';');
        }

        // Total general
        $total_general = array_sum(array_column($datos['por_productor'], 'total'));
        fputcsv($output, [], ';');
        fputcsv($output, ['', '', '', 'TOTAL GENERAL:', number_format($total_general, 2, ',', '') . '€'], ';');

        fclose($output);
        exit;
    }

    /**
     * Generar PDF del consolidado
     */
    private function generar_pdf_consolidado($datos, $ciclo_id) {
        $ciclo_nombre = sanitize_file_name($datos['ciclo']->post_title);
        $filename = "consolidado-{$ciclo_nombre}-" . date('Y-m-d') . ".pdf";

        // Generar HTML para PDF
        $html = $this->generar_html_consolidado($datos);

        // Intentar usar librería PDF si está disponible
        if (class_exists('TCPDF')) {
            $this->generar_pdf_tcpdf($html, $filename);
        } elseif (class_exists('Dompdf\Dompdf')) {
            $this->generar_pdf_dompdf($html, $filename);
        } else {
            // Fallback: descargar como HTML
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
            echo $html;
            exit;
        }
    }

    /**
     * Generar HTML para consolidado
     */
    private function generar_html_consolidado($datos) {
        $sitio_nombre = get_bloginfo('name');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Consolidado - <?php echo esc_html($datos['ciclo']->post_title); ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
                h1 { color: #2c5530; font-size: 20px; margin-bottom: 5px; }
                h2 { color: #4a7c59; font-size: 16px; margin-top: 20px; border-bottom: 2px solid #4a7c59; padding-bottom: 5px; }
                .header { margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
                .meta { color: #666; font-size: 11px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #f5f5f5; font-weight: bold; }
                .text-right { text-align: right; }
                .total-row { background: #f9f9f9; font-weight: bold; }
                .grand-total { background: #2c5530; color: white; font-size: 14px; }
                .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 10px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html($sitio_nombre); ?></h1>
                <p class="meta">
                    <strong><?php echo esc_html__('Consolidado de Pedidos', 'flavor-chat-ia'); ?></strong><br>
                    Ciclo: <?php echo esc_html($datos['ciclo']->post_title); ?><br>
                    Fecha de exportación: <?php echo date_i18n('d/m/Y H:i'); ?>
                </p>
            </div>

            <?php
            $total_general = 0;
            foreach ($datos['por_productor'] as $productor_id => $productor_data):
                $total_general += $productor_data['total'];
            ?>
                <h2><?php echo esc_html($productor_data['nombre']); ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Producto', 'flavor-chat-ia'); ?></th>
                            <th class="text-right"><?php echo esc_html__('Cantidad', 'flavor-chat-ia'); ?></th>
                            <th><?php echo esc_html__('Unidad', 'flavor-chat-ia'); ?></th>
                            <th class="text-right"><?php echo esc_html__('Precio Unit.', 'flavor-chat-ia'); ?></th>
                            <th class="text-right"><?php echo esc_html__('Total', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productor_data['productos'] as $producto):
                            $unidad = get_post_meta($producto->producto_id, '_gc_unidad', true) ?: 'ud';
                            $precio_unitario = $producto->cantidad_total > 0 ? $producto->total / $producto->cantidad_total : 0;
                        ?>
                            <tr>
                                <td><?php echo esc_html($producto->producto_nombre); ?></td>
                                <td class="text-right"><?php echo number_format($producto->cantidad_total, 2, ',', '.'); ?></td>
                                <td><?php echo esc_html($unidad); ?></td>
                                <td class="text-right"><?php echo number_format($precio_unitario, 2, ',', '.'); ?>€</td>
                                <td class="text-right"><?php echo number_format($producto->total, 2, ',', '.'); ?>€</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="4"><strong>Total <?php echo esc_html($productor_data['nombre']); ?></strong></td>
                            <td class="text-right"><strong><?php echo number_format($productor_data['total'], 2, ',', '.'); ?>€</strong></td>
                        </tr>
                    </tbody>
                </table>
            <?php endforeach; ?>

            <table>
                <tr class="grand-total">
                    <td colspan="4"><strong><?php echo esc_html__('TOTAL GENERAL', 'flavor-chat-ia'); ?></strong></td>
                    <td class="text-right"><strong><?php echo number_format($total_general, 2, ',', '.'); ?>€</strong></td>
                </tr>
            </table>

            <div class="footer">
                <p>Documento generado automáticamente por <?php echo esc_html($sitio_nombre); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Exportar listado de pedidos
     */
    public function exportar_pedidos() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'flavor-chat-ia'));
        }

        check_admin_referer('gc_exportar_pedidos');

        $ciclo_id = absint($_GET['ciclo_id'] ?? 0);
        $formato = sanitize_text_field($_GET['formato'] ?? 'excel');

        $datos = $this->obtener_datos_pedidos($ciclo_id);

        $filename = $ciclo_id
            ? "pedidos-ciclo-{$ciclo_id}-" . date('Y-m-d') . ".csv"
            : "pedidos-todos-" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabecera
        fputcsv($output, [
            'ID Pedido',
            'Fecha',
            'Usuario',
            'Email',
            'Ciclo',
            'Total',
            'Estado',
            'Productos',
        ], ';');

        foreach ($datos as $pedido) {
            $usuario = get_userdata($pedido->usuario_id);
            $productos = $this->formatear_productos_pedido($pedido->id);

            fputcsv($output, [
                $pedido->id,
                date_i18n('d/m/Y H:i', strtotime($pedido->fecha_pedido)),
                $usuario ? $usuario->display_name : 'N/A',
                $usuario ? $usuario->user_email : 'N/A',
                $pedido->ciclo_nombre ?: 'N/A',
                number_format($pedido->total, 2, ',', '') . '€',
                $pedido->estado,
                $productos,
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Obtener datos de pedidos
     */
    private function obtener_datos_pedidos($ciclo_id = 0) {
        global $wpdb;

        $where = '';
        if ($ciclo_id) {
            $where = $wpdb->prepare(' AND p.ciclo_id = %d', $ciclo_id);
        }

        return $wpdb->get_results(
            "SELECT p.*, c.post_title as ciclo_nombre
             FROM {$wpdb->prefix}flavor_gc_pedidos p
             LEFT JOIN {$wpdb->posts} c ON p.ciclo_id = c.ID
             WHERE 1=1 {$where}
             ORDER BY p.fecha_pedido DESC"
        );
    }

    /**
     * Formatear productos del pedido para exportación
     */
    private function formatear_productos_pedido($pedido_id) {
        global $wpdb;

        // Asumiendo que hay una tabla de items del pedido o están en JSON
        $pedido = $wpdb->get_row($wpdb->prepare(
            "SELECT detalles FROM {$wpdb->prefix}flavor_gc_pedidos WHERE id = %d",
            $pedido_id
        ));

        if (!$pedido || empty($pedido->detalles)) {
            return '';
        }

        $detalles = json_decode($pedido->detalles, true);
        if (!is_array($detalles)) {
            return '';
        }

        $productos = [];
        foreach ($detalles as $item) {
            $productos[] = sprintf(
                '%s x%s',
                $item['nombre'] ?? 'Producto',
                $item['cantidad'] ?? 1
            );
        }

        return implode(', ', $productos);
    }

    /**
     * Exportar listado de consumidores
     */
    public function exportar_consumidores() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'flavor-chat-ia'));
        }

        check_admin_referer('gc_exportar_consumidores');

        $grupo_id = absint($_GET['grupo_id'] ?? 0);

        global $wpdb;

        $where = $grupo_id ? $wpdb->prepare(' AND c.grupo_id = %d', $grupo_id) : '';

        $consumidores = $wpdb->get_results(
            "SELECT c.*, u.display_name, u.user_email, g.post_title as grupo_nombre
             FROM {$wpdb->prefix}flavor_gc_consumidores c
             LEFT JOIN {$wpdb->users} u ON c.usuario_id = u.ID
             LEFT JOIN {$wpdb->posts} g ON c.grupo_id = g.ID
             WHERE 1=1 {$where}
             ORDER BY u.display_name"
        );

        $filename = "consumidores-" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            'ID',
            'Nombre',
            'Email',
            'Grupo',
            'Rol',
            'Estado',
            'Fecha Alta',
            'Preferencias',
            'Alergias',
            'Saldo Pendiente',
        ], ';');

        foreach ($consumidores as $consumidor) {
            fputcsv($output, [
                $consumidor->id,
                $consumidor->display_name,
                $consumidor->user_email,
                $consumidor->grupo_nombre ?: 'Sin grupo',
                $consumidor->rol,
                $consumidor->estado,
                date_i18n('d/m/Y', strtotime($consumidor->fecha_alta)),
                $consumidor->preferencias_alimentarias,
                $consumidor->alergias,
                number_format($consumidor->saldo_pendiente, 2, ',', '') . '€',
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Exportar suscripciones
     */
    public function exportar_suscripciones() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'flavor-chat-ia'));
        }

        check_admin_referer('gc_exportar_suscripciones');

        global $wpdb;

        $suscripciones = $wpdb->get_results(
            "SELECT s.*, c.nombre as cesta_nombre, u.display_name, u.user_email
             FROM {$wpdb->prefix}flavor_gc_suscripciones s
             LEFT JOIN {$wpdb->prefix}flavor_gc_cestas_tipo c ON s.tipo_cesta = c.slug
             LEFT JOIN {$wpdb->prefix}flavor_gc_consumidores con ON s.consumidor_id = con.id
             LEFT JOIN {$wpdb->users} u ON con.usuario_id = u.ID
             ORDER BY s.fecha_inicio DESC"
        );

        $filename = "suscripciones-" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            'ID',
            'Usuario',
            'Email',
            'Tipo Cesta',
            'Frecuencia',
            'Importe',
            'Estado',
            'Fecha Inicio',
            'Próximo Cargo',
        ], ';');

        foreach ($suscripciones as $suscripcion) {
            fputcsv($output, [
                $suscripcion->id,
                $suscripcion->display_name,
                $suscripcion->user_email,
                $suscripcion->cesta_nombre,
                $suscripcion->frecuencia,
                number_format($suscripcion->importe, 2, ',', '') . '€',
                $suscripcion->estado,
                date_i18n('d/m/Y', strtotime($suscripcion->fecha_inicio)),
                $suscripcion->fecha_proximo_cargo ? date_i18n('d/m/Y', strtotime($suscripcion->fecha_proximo_cargo)) : 'N/A',
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * AJAX para generar exportaciones desde admin
     */
    public function ajax_generar_exportacion() {
        check_ajax_referer('gc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $formato = sanitize_text_field($_POST['formato'] ?? 'excel');
        $params = $_POST['params'] ?? [];

        $url_params = [
            'action' => 'gc_exportar_' . $tipo,
            'formato' => $formato,
            '_wpnonce' => wp_create_nonce('gc_exportar_' . $tipo),
        ];

        foreach ($params as $key => $value) {
            $url_params[$key] = sanitize_text_field($value);
        }

        $url = add_query_arg($url_params, admin_url('admin-post.php'));

        wp_send_json_success(['url' => $url]);
    }

    /**
     * Generar PDF con TCPDF
     */
    private function generar_pdf_tcpdf($html, $filename) {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Flavor Chat IA');
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle('Consolidado de Pedidos');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Generar PDF con Dompdf
     */
    private function generar_pdf_dompdf($html, $filename) {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /**
     * Exportar reporte general
     */
    public function generar_reporte_general($fecha_inicio, $fecha_fin) {
        global $wpdb;

        $datos = [
            'periodo' => [
                'inicio' => $fecha_inicio,
                'fin' => $fecha_fin,
            ],
        ];

        // Total de pedidos
        $datos['pedidos'] = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total, SUM(total) as importe
             FROM {$wpdb->prefix}flavor_gc_pedidos
             WHERE fecha_pedido BETWEEN %s AND %s",
            $fecha_inicio,
            $fecha_fin
        ));

        // Pedidos por estado
        $datos['pedidos_por_estado'] = $wpdb->get_results($wpdb->prepare(
            "SELECT estado, COUNT(*) as total, SUM(total) as importe
             FROM {$wpdb->prefix}flavor_gc_pedidos
             WHERE fecha_pedido BETWEEN %s AND %s
             GROUP BY estado",
            $fecha_inicio,
            $fecha_fin
        ));

        // Top productos
        $datos['top_productos'] = $wpdb->get_results($wpdb->prepare(
            "SELECT c.producto_id, p.post_title as nombre, SUM(c.cantidad_total) as cantidad, SUM(c.total) as importe
             FROM {$wpdb->prefix}flavor_gc_consolidado c
             LEFT JOIN {$wpdb->posts} p ON c.producto_id = p.ID
             LEFT JOIN {$wpdb->posts} ciclo ON c.ciclo_id = ciclo.ID
             LEFT JOIN {$wpdb->postmeta} pm ON ciclo.ID = pm.post_id AND pm.meta_key = '_gc_fecha_cierre'
             WHERE pm.meta_value BETWEEN %s AND %s
             GROUP BY c.producto_id
             ORDER BY cantidad DESC
             LIMIT 10",
            $fecha_inicio,
            $fecha_fin
        ));

        // Top consumidores
        $datos['top_consumidores'] = $wpdb->get_results($wpdb->prepare(
            "SELECT p.usuario_id, u.display_name, COUNT(*) as pedidos, SUM(p.total) as importe
             FROM {$wpdb->prefix}flavor_gc_pedidos p
             LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
             WHERE p.fecha_pedido BETWEEN %s AND %s
             GROUP BY p.usuario_id
             ORDER BY importe DESC
             LIMIT 10",
            $fecha_inicio,
            $fecha_fin
        ));

        // Suscripciones activas
        $datos['suscripciones'] = $wpdb->get_row(
            "SELECT COUNT(*) as total, SUM(importe) as mrr
             FROM {$wpdb->prefix}flavor_gc_suscripciones
             WHERE estado = 'activa'"
        );

        return $datos;
    }
}
