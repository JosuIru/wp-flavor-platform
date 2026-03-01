<?php
/**
 * Dashboard Tab para Facturas
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Facturas_Dashboard_Tab {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public function registrar_tabs($tabs) {
        // Solo mostrar a usuarios con permisos
        if (!current_user_can('edit_posts')) {
            return $tabs;
        }

        $tabs['facturas'] = [
            'label' => __('Facturas', 'flavor-chat-ia'),
            'icon' => 'dashicons-media-text',
            'callback' => [$this, 'render_tab'],
            'priority' => 62,
        ];
        return $tabs;
    }

    public function render_tab() {
        $datos = $this->obtener_datos_usuario();
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'lista';

        ?>
        <div class="flavor-facturas-dashboard">
            <!-- Navegación interna -->
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=facturas&subtab=lista" class="subtab <?php echo $subtab === 'lista' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span> Facturas
                </a>
                <a href="?tab=facturas&subtab=nueva" class="subtab <?php echo $subtab === 'nueva' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-plus-alt"></span> Nueva Factura
                </a>
                <a href="?tab=facturas&subtab=pagos" class="subtab <?php echo $subtab === 'pagos' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-money-alt"></span> Pagos
                </a>
                <a href="?tab=facturas&subtab=configuracion" class="subtab <?php echo $subtab === 'configuracion' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span> Configuración
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                switch ($subtab) {
                    case 'nueva':
                        $this->render_nueva_factura();
                        break;
                    case 'pagos':
                        $this->render_pagos($datos);
                        break;
                    case 'configuracion':
                        $this->render_configuracion($datos);
                        break;
                    default:
                        $this->render_lista_facturas($datos);
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_lista_facturas($datos) {
        ?>
        <!-- KPIs -->
        <div class="flavor-kpi-grid">
            <?php
            flavor_render_component('shared/kpi-card', [
                'label' => 'Total Facturas',
                'value' => $datos['total_facturas'],
                'icon' => 'dashicons-media-text',
                'color' => 'blue'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Pendientes',
                'value' => $datos['facturas_pendientes'],
                'icon' => 'dashicons-clock',
                'color' => 'yellow',
                'subtitle' => number_format($datos['importe_pendiente'], 2, ',', '.') . '€'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Pagadas',
                'value' => $datos['facturas_pagadas'],
                'icon' => 'dashicons-yes-alt',
                'color' => 'green'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Este Mes',
                'value' => number_format($datos['facturado_mes'], 2, ',', '.') . '€',
                'icon' => 'dashicons-calendar-alt',
                'color' => 'purple'
            ]);
            ?>
        </div>

        <!-- Filtros -->
        <div class="facturas-filtros">
            <form method="get" class="form-filtros">
                <input type="hidden" name="tab" value="facturas">
                <input type="text" name="buscar" placeholder="Buscar factura..."
                       value="<?php echo esc_attr($_GET['buscar'] ?? ''); ?>">
                <select name="estado">
                    <option value="">Todos los estados</option>
                    <option value="borrador" <?php selected($_GET['estado'] ?? '', 'borrador'); ?>>Borrador</option>
                    <option value="enviada" <?php selected($_GET['estado'] ?? '', 'enviada'); ?>>Enviada</option>
                    <option value="pagada" <?php selected($_GET['estado'] ?? '', 'pagada'); ?>>Pagada</option>
                    <option value="vencida" <?php selected($_GET['estado'] ?? '', 'vencida'); ?>>Vencida</option>
                    <option value="anulada" <?php selected($_GET['estado'] ?? '', 'anulada'); ?>>Anulada</option>
                </select>
                <input type="month" name="mes" value="<?php echo esc_attr($_GET['mes'] ?? ''); ?>">
                <button type="submit" class="flavor-btn">Filtrar</button>
            </form>
        </div>

        <!-- Lista de facturas -->
        <div class="facturas-tabla">
            <?php if (empty($datos['facturas'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-media-text"></span>
                    <p>No hay facturas registradas</p>
                    <a href="?tab=facturas&subtab=nueva" class="flavor-btn flavor-btn-primary">Crear primera factura</a>
                </div>
            <?php else: ?>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Vencimiento</th>
                            <th>Importe</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['facturas'] as $factura): ?>
                            <tr class="factura-estado-<?php echo $factura->estado; ?>">
                                <td>
                                    <strong><?php echo esc_html($factura->numero_factura); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html($factura->cliente_nombre); ?>
                                    <?php if ($factura->cliente_nif): ?>
                                        <small>(<?php echo esc_html($factura->cliente_nif); ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date_i18n('j M Y', strtotime($factura->fecha_emision)); ?></td>
                                <td>
                                    <?php
                                    $vencimiento = strtotime($factura->fecha_vencimiento);
                                    $hoy = strtotime('today');
                                    $clase_vencimiento = '';
                                    if ($factura->estado !== 'pagada' && $factura->estado !== 'anulada') {
                                        if ($vencimiento < $hoy) {
                                            $clase_vencimiento = 'vencida';
                                        } elseif ($vencimiento < strtotime('+7 days')) {
                                            $clase_vencimiento = 'proxima';
                                        }
                                    }
                                    ?>
                                    <span class="fecha-vencimiento <?php echo $clase_vencimiento; ?>">
                                        <?php echo date_i18n('j M Y', $vencimiento); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo number_format($factura->total, 2, ',', '.'); ?>€</strong>
                                </td>
                                <td>
                                    <span class="badge badge-estado-<?php echo $factura->estado; ?>">
                                        <?php echo ucfirst($factura->estado); ?>
                                    </span>
                                </td>
                                <td class="acciones-factura">
                                    <a href="?tab=facturas&subtab=ver&id=<?php echo $factura->id; ?>" class="flavor-btn flavor-btn-sm" title="Ver">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                    <a href="?tab=facturas&subtab=pdf&id=<?php echo $factura->id; ?>" class="flavor-btn flavor-btn-sm" title="PDF">
                                        <span class="dashicons dashicons-pdf"></span>
                                    </a>
                                    <?php if ($factura->estado === 'enviada'): ?>
                                        <button class="flavor-btn flavor-btn-sm flavor-btn-success marcar-pagada" data-id="<?php echo $factura->id; ?>" title="Marcar pagada">
                                            <span class="dashicons dashicons-yes"></span>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_nueva_factura() {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $clientes = $wpdb->get_results("SELECT id, nombre, email, nif FROM $tabla_clientes WHERE estado = 'activo' ORDER BY nombre");
        $config = $this->obtener_configuracion();
        ?>
        <div class="nueva-factura-form">
            <h3>Nueva factura</h3>

            <form id="form-nueva-factura" method="post">
                <?php wp_nonce_field('flavor_nueva_factura', 'factura_nonce'); ?>

                <!-- Datos básicos -->
                <div class="factura-seccion">
                    <h4>Datos de la factura</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Serie</label>
                            <select name="serie">
                                <option value="<?php echo esc_attr($config['serie_default']); ?>">
                                    <?php echo esc_html($config['serie_default']); ?>
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha emisión</label>
                            <input type="date" name="fecha_emision" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha vencimiento</label>
                            <input type="date" name="fecha_vencimiento"
                                   value="<?php echo date('Y-m-d', strtotime('+' . $config['dias_vencimiento'] . ' days')); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Cliente -->
                <div class="factura-seccion">
                    <h4>Cliente</h4>
                    <div class="form-row">
                        <div class="form-group form-group-lg">
                            <label>Seleccionar cliente existente</label>
                            <select name="cliente_id" id="select-cliente">
                                <option value="">-- Cliente nuevo --</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente->id; ?>"
                                            data-nombre="<?php echo esc_attr($cliente->nombre); ?>"
                                            data-email="<?php echo esc_attr($cliente->email); ?>"
                                            data-nif="<?php echo esc_attr($cliente->nif ?? ''); ?>">
                                        <?php echo esc_html($cliente->nombre); ?>
                                        <?php if ($cliente->nif): ?>(<?php echo esc_html($cliente->nif); ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div id="datos-cliente-nuevo">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre *</label>
                                <input type="text" name="cliente_nombre" required>
                            </div>
                            <div class="form-group">
                                <label>NIF/CIF</label>
                                <input type="text" name="cliente_nif">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="cliente_email">
                            </div>
                            <div class="form-group form-group-lg">
                                <label>Dirección</label>
                                <input type="text" name="cliente_direccion">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Líneas de factura -->
                <div class="factura-seccion">
                    <h4>Conceptos</h4>
                    <table class="tabla-lineas" id="lineas-factura">
                        <thead>
                            <tr>
                                <th style="width:40%">Concepto</th>
                                <th style="width:15%">Cantidad</th>
                                <th style="width:15%">Precio Unit.</th>
                                <th style="width:10%">IVA %</th>
                                <th style="width:15%">Subtotal</th>
                                <th style="width:5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="linea-factura">
                                <td><input type="text" name="lineas[0][concepto]" required></td>
                                <td><input type="number" name="lineas[0][cantidad]" value="1" min="0.01" step="0.01" class="calc-cantidad"></td>
                                <td><input type="number" name="lineas[0][precio]" min="0" step="0.01" class="calc-precio"></td>
                                <td><input type="number" name="lineas[0][iva]" value="<?php echo $config['iva_default']; ?>" min="0" max="100" class="calc-iva"></td>
                                <td><span class="linea-subtotal">0,00€</span></td>
                                <td><button type="button" class="eliminar-linea">&times;</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" id="agregar-linea" class="flavor-btn flavor-btn-sm">
                        <span class="dashicons dashicons-plus"></span> Añadir línea
                    </button>
                </div>

                <!-- Totales -->
                <div class="factura-totales">
                    <div class="total-row">
                        <span>Base imponible:</span>
                        <span id="total-base">0,00€</span>
                    </div>
                    <div class="total-row">
                        <span>IVA:</span>
                        <span id="total-iva">0,00€</span>
                    </div>
                    <div class="total-row total-final">
                        <span>Total:</span>
                        <span id="total-factura">0,00€</span>
                    </div>
                </div>

                <!-- Notas -->
                <div class="factura-seccion">
                    <div class="form-group">
                        <label>Notas / Observaciones</label>
                        <textarea name="notas" rows="3"></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="accion" value="borrador" class="flavor-btn">Guardar borrador</button>
                    <button type="submit" name="accion" value="enviar" class="flavor-btn flavor-btn-primary">Guardar y enviar</button>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Calcular totales
            function calcularTotales() {
                let base = 0, iva = 0;
                $('.linea-factura').each(function() {
                    const cantidad = parseFloat($(this).find('.calc-cantidad').val()) || 0;
                    const precio = parseFloat($(this).find('.calc-precio').val()) || 0;
                    const ivaPorc = parseFloat($(this).find('.calc-iva').val()) || 0;

                    const subtotal = cantidad * precio;
                    const ivaLinea = subtotal * (ivaPorc / 100);

                    base += subtotal;
                    iva += ivaLinea;

                    $(this).find('.linea-subtotal').text(subtotal.toFixed(2).replace('.', ',') + '€');
                });

                $('#total-base').text(base.toFixed(2).replace('.', ',') + '€');
                $('#total-iva').text(iva.toFixed(2).replace('.', ',') + '€');
                $('#total-factura').text((base + iva).toFixed(2).replace('.', ',') + '€');
            }

            $(document).on('input', '.calc-cantidad, .calc-precio, .calc-iva', calcularTotales);

            // Agregar línea
            let contadorLineas = 1;
            $('#agregar-linea').on('click', function() {
                const nuevaLinea = `
                    <tr class="linea-factura">
                        <td><input type="text" name="lineas[${contadorLineas}][concepto]" required></td>
                        <td><input type="number" name="lineas[${contadorLineas}][cantidad]" value="1" min="0.01" step="0.01" class="calc-cantidad"></td>
                        <td><input type="number" name="lineas[${contadorLineas}][precio]" min="0" step="0.01" class="calc-precio"></td>
                        <td><input type="number" name="lineas[${contadorLineas}][iva]" value="21" min="0" max="100" class="calc-iva"></td>
                        <td><span class="linea-subtotal">0,00€</span></td>
                        <td><button type="button" class="eliminar-linea">&times;</button></td>
                    </tr>
                `;
                $('#lineas-factura tbody').append(nuevaLinea);
                contadorLineas++;
            });

            // Eliminar línea
            $(document).on('click', '.eliminar-linea', function() {
                if ($('.linea-factura').length > 1) {
                    $(this).closest('tr').remove();
                    calcularTotales();
                }
            });

            // Seleccionar cliente
            $('#select-cliente').on('change', function() {
                const clienteId = $(this).val();
                if (clienteId) {
                    const option = $(this).find('option:selected');
                    $('input[name="cliente_nombre"]').val(option.data('nombre'));
                    $('input[name="cliente_email"]').val(option.data('email'));
                    $('input[name="cliente_nif"]').val(option.data('nif'));
                    $('#datos-cliente-nuevo').slideUp();
                } else {
                    $('input[name="cliente_nombre"]').val('');
                    $('input[name="cliente_email"]').val('');
                    $('input[name="cliente_nif"]').val('');
                    $('#datos-cliente-nuevo').slideDown();
                }
            });
        });
        </script>
        <?php
    }

    private function render_pagos($datos) {
        ?>
        <div class="facturas-pagos">
            <h3>Historial de pagos</h3>

            <?php if (empty($datos['pagos'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-money-alt"></span>
                    <p>No hay pagos registrados</p>
                </div>
            <?php else: ?>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Factura</th>
                            <th>Cliente</th>
                            <th>Importe</th>
                            <th>Método</th>
                            <th>Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['pagos'] as $pago): ?>
                            <tr>
                                <td><?php echo date_i18n('j M Y', strtotime($pago->fecha_pago)); ?></td>
                                <td>
                                    <a href="?tab=facturas&subtab=ver&id=<?php echo $pago->factura_id; ?>">
                                        <?php echo esc_html($pago->numero_factura); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($pago->cliente_nombre); ?></td>
                                <td><strong><?php echo number_format($pago->importe, 2, ',', '.'); ?>€</strong></td>
                                <td><?php echo ucfirst($pago->metodo_pago); ?></td>
                                <td><?php echo esc_html($pago->referencia); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_configuracion($datos) {
        $config = $this->obtener_configuracion();
        ?>
        <div class="facturas-configuracion">
            <h3>Configuración de facturación</h3>

            <form id="form-config-facturas" method="post">
                <?php wp_nonce_field('flavor_config_facturas', 'config_nonce'); ?>

                <!-- Datos empresa -->
                <div class="config-seccion">
                    <h4>Datos de la empresa</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre / Razón social</label>
                            <input type="text" name="empresa_nombre" value="<?php echo esc_attr($config['empresa_nombre'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>NIF/CIF</label>
                            <input type="text" name="empresa_nif" value="<?php echo esc_attr($config['empresa_nif'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-group-lg">
                            <label>Dirección</label>
                            <textarea name="empresa_direccion" rows="2"><?php echo esc_textarea($config['empresa_direccion'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="empresa_email" value="<?php echo esc_attr($config['empresa_email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="tel" name="empresa_telefono" value="<?php echo esc_attr($config['empresa_telefono'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Numeración -->
                <div class="config-seccion">
                    <h4>Numeración</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Serie por defecto</label>
                            <input type="text" name="serie_default" value="<?php echo esc_attr($config['serie_default']); ?>" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label>Siguiente número</label>
                            <input type="number" name="siguiente_numero" value="<?php echo esc_attr($config['siguiente_numero'] ?? 1); ?>" min="1">
                        </div>
                        <div class="form-group">
                            <label>Formato número</label>
                            <input type="text" name="formato_numero" value="<?php echo esc_attr($config['formato_numero']); ?>"
                                   placeholder="{SERIE}-{YEAR}-{NUM}">
                            <small>Variables: {SERIE}, {YEAR}, {MONTH}, {NUM}</small>
                        </div>
                    </div>
                </div>

                <!-- Impuestos -->
                <div class="config-seccion">
                    <h4>Impuestos y vencimiento</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>IVA por defecto (%)</label>
                            <input type="number" name="iva_default" value="<?php echo esc_attr($config['iva_default']); ?>" min="0" max="100">
                        </div>
                        <div class="form-group">
                            <label>Días de vencimiento</label>
                            <input type="number" name="dias_vencimiento" value="<?php echo esc_attr($config['dias_vencimiento']); ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label>Moneda</label>
                            <select name="moneda">
                                <option value="EUR" <?php selected($config['moneda'], 'EUR'); ?>>Euro (€)</option>
                                <option value="USD" <?php selected($config['moneda'], 'USD'); ?>>Dólar ($)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Pie de factura -->
                <div class="config-seccion">
                    <h4>Pie de factura</h4>
                    <div class="form-group">
                        <label>Cuenta bancaria</label>
                        <input type="text" name="cuenta_bancaria" value="<?php echo esc_attr($config['cuenta_bancaria'] ?? ''); ?>"
                               placeholder="ESXX XXXX XXXX XXXX XXXX XXXX">
                    </div>
                    <div class="form-group">
                        <label>Texto pie de factura</label>
                        <textarea name="pie_factura" rows="3"><?php echo esc_textarea($config['pie_factura'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">Guardar configuración</button>
                </div>
            </form>
        </div>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';
        $tabla_pagos = $wpdb->prefix . 'flavor_facturas_pagos';

        // Filtros
        $where = "1=1";
        $buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';
        $estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $mes_filtro = isset($_GET['mes']) ? sanitize_text_field($_GET['mes']) : '';

        if ($buscar) {
            $where .= $wpdb->prepare(" AND (numero_factura LIKE %s OR cliente_nombre LIKE %s)",
                '%' . $buscar . '%', '%' . $buscar . '%');
        }
        if ($estado_filtro) {
            $where .= $wpdb->prepare(" AND estado = %s", $estado_filtro);
        }
        if ($mes_filtro) {
            $where .= $wpdb->prepare(" AND DATE_FORMAT(fecha_emision, '%%Y-%%m') = %s", $mes_filtro);
        }

        // Facturas
        $facturas = $wpdb->get_results(
            "SELECT * FROM $tabla_facturas WHERE $where ORDER BY fecha_emision DESC LIMIT 50"
        );

        // Estadísticas
        $total_facturas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_facturas");
        $facturas_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_facturas WHERE estado IN ('enviada', 'vencida')");
        $facturas_pagadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_facturas WHERE estado = 'pagada'");
        $importe_pendiente = $wpdb->get_var("SELECT SUM(total) FROM $tabla_facturas WHERE estado IN ('enviada', 'vencida')") ?: 0;

        // Facturado este mes
        $facturado_mes = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(total) FROM $tabla_facturas WHERE estado = 'pagada' AND DATE_FORMAT(fecha_emision, '%%Y-%%m') = %s",
            date('Y-m')
        )) ?: 0;

        // Últimos pagos
        $pagos = $wpdb->get_results(
            "SELECT p.*, f.numero_factura, f.cliente_nombre
             FROM $tabla_pagos p
             JOIN $tabla_facturas f ON p.factura_id = f.id
             ORDER BY p.fecha_pago DESC
             LIMIT 20"
        );

        return [
            'facturas' => $facturas ?: [],
            'total_facturas' => $total_facturas ?: 0,
            'facturas_pendientes' => $facturas_pendientes ?: 0,
            'facturas_pagadas' => $facturas_pagadas ?: 0,
            'importe_pendiente' => $importe_pendiente,
            'facturado_mes' => $facturado_mes,
            'pagos' => $pagos ?: [],
        ];
    }

    private function obtener_configuracion() {
        $default = [
            'serie_default' => 'F',
            'formato_numero' => '{SERIE}-{YEAR}-{NUM}',
            'iva_default' => 21,
            'dias_vencimiento' => 30,
            'moneda' => 'EUR',
        ];

        $config = get_option('flavor_facturas_config', []);
        return wp_parse_args($config, $default);
    }
}

Flavor_Facturas_Dashboard_Tab::get_instance();
