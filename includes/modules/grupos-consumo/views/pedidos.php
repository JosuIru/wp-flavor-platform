<?php
/**
 * Vista Pedidos - Grupos de Consumo
 *
 * Los handlers AJAX están en class-grupos-consumo-module.php
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

// Filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_ciclo = isset($_GET['ciclo']) ? absint($_GET['ciclo']) : 0;
$vista = isset($_GET['vista']) ? sanitize_text_field($_GET['vista']) : 'usuario';

$where = ['1=1'];
$preparar = [];

if ($filtro_estado) {
    $where[] = "estado = %s";
    $preparar[] = $filtro_estado;
}

if ($filtro_ciclo) {
    $where[] = "ciclo_id = %d";
    $preparar[] = $filtro_ciclo;
}

$where_sql = implode(' AND ', $where);

// Obtener pedidos
if (!empty($preparar)) {
    $pedidos = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_pedidos WHERE $where_sql ORDER BY usuario_id, fecha_pedido DESC",
        ...$preparar
    ));
} else {
    $pedidos = $wpdb->get_results("SELECT * FROM $tabla_pedidos WHERE $where_sql ORDER BY usuario_id, fecha_pedido DESC");
}

// Ciclos disponibles
$ciclos = get_posts(['post_type' => 'gc_ciclo', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC']);

// Agrupar pedidos por usuario
$pedidos_por_usuario = [];
$totales_usuario = [];

foreach ($pedidos as $pedido) {
    $uid = $pedido->usuario_id;
    if (!isset($pedidos_por_usuario[$uid])) {
        $pedidos_por_usuario[$uid] = [];
        $totales_usuario[$uid] = 0;
    }
    $pedidos_por_usuario[$uid][] = $pedido;
    $totales_usuario[$uid] += $pedido->cantidad * $pedido->precio_unitario;
}

// Colores para badges de estado
$colores_estado = [
    'pendiente' => '#dba617',
    'confirmado' => '#2271b1',
    'completado' => '#00a32a',
    'cancelado' => '#d63638',
    'sin_stock' => '#8b5cf6',
];

?>

<style>
.gc-pedidos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.gc-vista-toggle {
    display: inline-flex;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow: hidden;
}
.gc-vista-toggle a {
    padding: 8px 16px;
    text-decoration: none;
    color: #50575e;
    background: #fff;
    border-right: 1px solid #c3c4c7;
}
.gc-vista-toggle a:last-child {
    border-right: none;
}
.gc-vista-toggle a.active {
    background: #2271b1;
    color: #fff;
}
.gc-usuario-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    margin-bottom: 15px;
    overflow: hidden;
}
.gc-usuario-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
    color: #fff;
    cursor: pointer;
    user-select: none;
    transition: background 0.2s ease;
}
.gc-usuario-header:hover {
    background: linear-gradient(135deg, #1e6091 0%, #104a7a 100%);
}
.gc-usuario-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    margin-left: 15px;
    transition: background 0.2s ease;
}
.gc-usuario-toggle:hover {
    background: rgba(255,255,255,0.25);
}
.gc-usuario-toggle .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.gc-usuario-card.collapsed .gc-usuario-toggle .dashicons {
    transform: rotate(-90deg);
}
.gc-usuario-body {
    max-height: 2000px;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
    opacity: 1;
}
.gc-usuario-card.collapsed .gc-usuario-body {
    max-height: 0;
    opacity: 0;
}
.gc-usuario-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.gc-usuario-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}
.gc-usuario-nombre {
    font-size: 16px;
    font-weight: 600;
}
.gc-usuario-email {
    font-size: 12px;
    opacity: 0.8;
}
.gc-usuario-total {
    text-align: right;
}
.gc-usuario-total-cantidad {
    font-size: 20px;
    font-weight: 700;
}
.gc-usuario-total-label {
    font-size: 11px;
    opacity: 0.8;
    text-transform: uppercase;
}
.gc-usuario-productos {
    padding: 0;
}
.gc-usuario-productos table {
    margin: 0;
    border: none;
}
.gc-usuario-productos th {
    background: #f0f0f1;
}
.gc-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    color: #fff;
}
.gc-acciones-usuario {
    padding: 12px 20px;
    background: #f6f7f7;
    border-top: 1px solid #c3c4c7;
    display: flex;
    gap: 10px;
}
.gc-btn-imprimir {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.gc-resumen-preparacion {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.gc-resumen-preparacion h3 {
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.gc-resumen-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}
.gc-resumen-stat {
    text-align: center;
    padding: 15px;
    background: #f6f7f7;
    border-radius: 6px;
}
.gc-resumen-stat-valor {
    font-size: 24px;
    font-weight: 700;
    color: #2271b1;
}
.gc-resumen-stat-label {
    font-size: 12px;
    color: #646970;
    margin-top: 5px;
}
.gc-estado-select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    border: none;
    font-size: 12px;
    min-width: 100px;
    text-align: center;
    transition: all 0.2s ease;
}
.gc-estado-select:hover {
    opacity: 0.9;
    transform: scale(1.02);
}
.gc-estado-select:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.4);
}
.gc-estado-select:disabled {
    opacity: 0.7;
    cursor: wait;
}
@media print {
    .gc-pedidos-header, .tablenav, .gc-acciones-usuario, .gc-resumen-preparacion { display: none !important; }
    .gc-usuario-card { break-inside: avoid; page-break-inside: avoid; }
    .gc-estado-select { -webkit-appearance: none; background: transparent !important; color: #000 !important; border: 1px solid #000 !important; }
}
</style>

<div class="wrap">
    <div class="gc-pedidos-header">
        <h1><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Gestión de Pedidos', 'flavor-platform'); ?></h1>

        <div class="gc-vista-toggle">
            <a href="<?php echo esc_url(add_query_arg('vista', 'usuario')); ?>" class="<?php echo $vista === 'usuario' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-groups"></span> <?php esc_html_e('Por Usuario', 'flavor-platform'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('vista', 'lista')); ?>" class="<?php echo $vista === 'lista' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-list-view"></span> <?php esc_html_e('Lista', 'flavor-platform'); ?>
            </a>
        </div>
    </div>
    <hr class="wp-header-end">

    <div class="tablenav top">
        <form method="get" style="display: inline-flex; gap: 8px; align-items: center;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
            <input type="hidden" name="vista" value="<?php echo esc_attr($vista); ?>">
            <select name="estado">
                <option value=""><?php esc_html_e('Todos los estados', 'flavor-platform'); ?></option>
                <option value="pendiente" <?php selected($filtro_estado, 'pendiente'); ?>><?php esc_html_e('Pendiente', 'flavor-platform'); ?></option>
                <option value="confirmado" <?php selected($filtro_estado, 'confirmado'); ?>><?php esc_html_e('Confirmado', 'flavor-platform'); ?></option>
                <option value="completado" <?php selected($filtro_estado, 'completado'); ?>><?php esc_html_e('Completado', 'flavor-platform'); ?></option>
                <option value="cancelado" <?php selected($filtro_estado, 'cancelado'); ?>><?php esc_html_e('Cancelado', 'flavor-platform'); ?></option>
                <option value="sin_stock" <?php selected($filtro_estado, 'sin_stock'); ?>><?php esc_html_e('Sin stock', 'flavor-platform'); ?></option>
            </select>
            <select name="ciclo">
                <option value="0"><?php esc_html_e('Todos los ciclos', 'flavor-platform'); ?></option>
                <?php foreach ($ciclos as $ciclo): ?>
                    <option value="<?php echo $ciclo->ID; ?>" <?php selected($filtro_ciclo, $ciclo->ID); ?>>
                        <?php echo esc_html($ciclo->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button"><?php esc_html_e('Filtrar', 'flavor-platform'); ?></button>

            <?php if ($vista === 'usuario' && !empty($pedidos)): ?>
                <button type="button" class="button" onclick="window.print();">
                    <span class="dashicons dashicons-printer" style="margin-top: 3px;"></span> <?php esc_html_e('Imprimir Todo', 'flavor-platform'); ?>
                </button>
                <span style="margin-left: 10px; border-left: 1px solid #c3c4c7; padding-left: 10px;">
                    <button type="button" class="button gc-expandir-todos" title="<?php esc_attr_e('Expandir todos', 'flavor-platform'); ?>">
                        <span class="dashicons dashicons-arrow-down-alt2" style="margin-top: 3px;"></span>
                    </button>
                    <button type="button" class="button gc-colapsar-todos" title="<?php esc_attr_e('Colapsar todos', 'flavor-platform'); ?>">
                        <span class="dashicons dashicons-arrow-up-alt2" style="margin-top: 3px;"></span>
                    </button>
                </span>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($vista === 'usuario'): ?>

        <!-- Resumen para preparación -->
        <?php if (!empty($pedidos)): ?>
        <div class="gc-resumen-preparacion">
            <h3><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e('Resumen de Preparación', 'flavor-platform'); ?></h3>
            <div class="gc-resumen-stats">
                <div class="gc-resumen-stat">
                    <div class="gc-resumen-stat-valor"><?php echo count($pedidos_por_usuario); ?></div>
                    <div class="gc-resumen-stat-label"><?php esc_html_e('Usuarios', 'flavor-platform'); ?></div>
                </div>
                <div class="gc-resumen-stat">
                    <div class="gc-resumen-stat-valor"><?php echo count($pedidos); ?></div>
                    <div class="gc-resumen-stat-label"><?php esc_html_e('Líneas de pedido', 'flavor-platform'); ?></div>
                </div>
                <div class="gc-resumen-stat">
                    <div class="gc-resumen-stat-valor"><?php echo number_format(array_sum($totales_usuario), 2); ?> €</div>
                    <div class="gc-resumen-stat-label"><?php esc_html_e('Total general', 'flavor-platform'); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Vista agrupada por usuario -->
        <?php foreach ($pedidos_por_usuario as $usuario_id => $pedidos_usuario):
            $usuario = get_userdata($usuario_id);
            $nombre_usuario = $usuario ? $usuario->display_name : __('Usuario desconocido', 'flavor-platform');
            $email_usuario = $usuario ? $usuario->user_email : '';
            $total_usuario = $totales_usuario[$usuario_id];
        ?>
        <div class="gc-usuario-card">
            <div class="gc-usuario-header">
                <div class="gc-usuario-info">
                    <div class="gc-usuario-avatar">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    <div>
                        <div class="gc-usuario-nombre"><?php echo esc_html($nombre_usuario); ?></div>
                        <?php if ($email_usuario): ?>
                            <div class="gc-usuario-email"><?php echo esc_html($email_usuario); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="gc-usuario-total">
                    <div class="gc-usuario-total-cantidad"><?php echo number_format($total_usuario, 2); ?> €</div>
                    <div class="gc-usuario-total-label"><?php echo sprintf(_n('%d producto', '%d productos', count($pedidos_usuario), 'flavor-platform'), count($pedidos_usuario)); ?></div>
                </div>
                <div class="gc-usuario-toggle">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
            </div>

            <div class="gc-usuario-body">
            <div class="gc-usuario-productos">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40%;"><?php esc_html_e('Producto', 'flavor-platform'); ?></th>
                            <th style="width: 15%;"><?php esc_html_e('Cantidad', 'flavor-platform'); ?></th>
                            <th style="width: 15%;"><?php esc_html_e('Precio/u', 'flavor-platform'); ?></th>
                            <th style="width: 15%;"><?php esc_html_e('Subtotal', 'flavor-platform'); ?></th>
                            <th style="width: 15%;"><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos_usuario as $pedido):
                            $producto = get_post($pedido->producto_id);
                            $subtotal = $pedido->cantidad * $pedido->precio_unitario;
                            $color_estado = $colores_estado[$pedido->estado] ?? '#646970';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $producto ? esc_html($producto->post_title) : 'Producto #' . $pedido->producto_id; ?></strong>
                            </td>
                            <td><?php echo number_format($pedido->cantidad, 2); ?></td>
                            <td><?php echo number_format($pedido->precio_unitario, 2); ?> €</td>
                            <td><strong><?php echo number_format($subtotal, 2); ?> €</strong></td>
                            <td>
                                <select class="gc-estado-select" data-pedido-id="<?php echo $pedido->id; ?>" data-estado-original="<?php echo esc_attr($pedido->estado); ?>">
                                    <option value="pendiente" <?php selected($pedido->estado, 'pendiente'); ?>><?php esc_html_e('Pendiente', 'flavor-platform'); ?></option>
                                    <option value="confirmado" <?php selected($pedido->estado, 'confirmado'); ?>><?php esc_html_e('Confirmado', 'flavor-platform'); ?></option>
                                    <option value="completado" <?php selected($pedido->estado, 'completado'); ?>><?php esc_html_e('Completado', 'flavor-platform'); ?></option>
                                    <option value="sin_stock" <?php selected($pedido->estado, 'sin_stock'); ?>><?php esc_html_e('Sin stock', 'flavor-platform'); ?></option>
                                    <option value="cancelado" <?php selected($pedido->estado, 'cancelado'); ?>><?php esc_html_e('Cancelado', 'flavor-platform'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="gc-acciones-usuario">
                <button type="button" class="button gc-btn-imprimir" onclick="imprimirUsuario(<?php echo $usuario_id; ?>)">
                    <span class="dashicons dashicons-printer"></span> <?php esc_html_e('Imprimir', 'flavor-platform'); ?>
                </button>
                <button type="button" class="button gc-btn-marcar-completado" data-usuario="<?php echo $usuario_id; ?>">
                    <span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Marcar como preparado', 'flavor-platform'); ?>
                </button>
            </div>
            </div><!-- /.gc-usuario-body -->
        </div>
        <?php endforeach; ?>

        <?php if (empty($pedidos)): ?>
            <div class="gc-usuario-card" style="padding: 40px; text-align: center;">
                <span class="dashicons dashicons-clipboard" style="font-size: 48px; color: #c3c4c7;"></span>
                <p style="color: #646970; margin-top: 15px;"><?php esc_html_e('No hay pedidos con los filtros seleccionados', 'flavor-platform'); ?></p>
            </div>
        <?php endif; ?>

    <?php else: ?>

        <!-- Vista de lista tradicional -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Producto', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Usuario', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Cantidad', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Precio', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Total', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">
                            <?php esc_html_e('No hay pedidos con los filtros seleccionados', 'flavor-platform'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pedidos as $pedido):
                        $producto = get_post($pedido->producto_id);
                        $usuario = get_userdata($pedido->usuario_id);
                        $total = $pedido->cantidad * $pedido->precio_unitario;
                        $color_estado = $colores_estado[$pedido->estado] ?? '#646970';
                    ?>
                    <tr>
                        <td>#<?php echo $pedido->id; ?></td>
                        <td><?php echo $producto ? esc_html($producto->post_title) : 'N/A'; ?></td>
                        <td><?php echo $usuario ? esc_html($usuario->display_name) : 'N/A'; ?></td>
                        <td><?php echo number_format($pedido->cantidad, 2); ?></td>
                        <td><?php echo number_format($pedido->precio_unitario, 2); ?> €</td>
                        <td><strong><?php echo number_format($total, 2); ?> €</strong></td>
                        <td>
                            <select class="gc-estado-select" data-pedido-id="<?php echo $pedido->id; ?>" data-estado-original="<?php echo esc_attr($pedido->estado); ?>">
                                <option value="pendiente" <?php selected($pedido->estado, 'pendiente'); ?>><?php esc_html_e('Pendiente', 'flavor-platform'); ?></option>
                                <option value="confirmado" <?php selected($pedido->estado, 'confirmado'); ?>><?php esc_html_e('Confirmado', 'flavor-platform'); ?></option>
                                <option value="completado" <?php selected($pedido->estado, 'completado'); ?>><?php esc_html_e('Completado', 'flavor-platform'); ?></option>
                                <option value="sin_stock" <?php selected($pedido->estado, 'sin_stock'); ?>><?php esc_html_e('Sin stock', 'flavor-platform'); ?></option>
                                <option value="cancelado" <?php selected($pedido->estado, 'cancelado'); ?>><?php esc_html_e('Cancelado', 'flavor-platform'); ?></option>
                            </select>
                        </td>
                        <td><?php echo date_i18n('d/m/Y', strtotime($pedido->fecha_pedido)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>

<script>
function imprimirUsuario(usuarioId) {
    // Ocultar temporalmente las otras tarjetas
    document.querySelectorAll('.gc-usuario-card').forEach(card => {
        if (!card.querySelector('[data-usuario="' + usuarioId + '"]')) {
            card.style.display = 'none';
        }
    });
    window.print();
    // Restaurar
    document.querySelectorAll('.gc-usuario-card').forEach(card => {
        card.style.display = '';
    });
}

jQuery(document).ready(function($) {
    var $notice = $('<div class="gc-inline-notice"></div>').insertBefore('.wrap h1').hide();

    function gcPedidosAviso(mensaje, tipo) {
        $notice.removeClass('success error').addClass(tipo || 'error').text(mensaje).show();
    }

    function gcPedidosConfirmar(mensaje, onConfirm) {
        $notice.removeClass('success error').addClass('error').html(
            '<p>' + mensaje + '</p>' +
            '<div class="gc-inline-confirm-actions">' +
                '<button type="button" class="button button-primary gc-confirmar"><?php echo esc_js(__('Confirmar', 'flavor-platform')); ?></button>' +
                '<button type="button" class="button gc-cancelar"><?php echo esc_js(__('Cancelar', 'flavor-platform')); ?></button>' +
            '</div>'
        ).show();

        $notice.off('click', '.gc-confirmar').on('click', '.gc-confirmar', function() {
            $notice.hide().empty();
            onConfirm();
        });

        $notice.off('click', '.gc-cancelar').on('click', '.gc-cancelar', function() {
            $notice.hide().empty();
        });
    }

    // Toggle de tarjetas de usuario (expandir/colapsar)
    $('.gc-usuario-header').on('click', function(e) {
        // No colapsar si se hace clic en el select de estado
        if ($(e.target).is('select, option')) return;

        var $card = $(this).closest('.gc-usuario-card');
        $card.toggleClass('collapsed');
    });

    // Expandir todos
    $('.gc-expandir-todos').on('click', function() {
        $('.gc-usuario-card').removeClass('collapsed');
    });

    // Colapsar todos
    $('.gc-colapsar-todos').on('click', function() {
        $('.gc-usuario-card').addClass('collapsed');
    });

    var coloresEstado = {
        'pendiente': '#dba617',
        'confirmado': '#2271b1',
        'completado': '#00a32a',
        'sin_stock': '#8b5cf6',
        'cancelado': '#d63638'
    };

    // Aplicar colores iniciales a los selectores
    function aplicarColorSelect($select) {
        var estado = $select.val();
        var color = coloresEstado[estado] || '#646970';
        $select.css({
            'background-color': color,
            'color': '#fff',
            'border-color': color,
            'font-weight': '600',
            'padding': '4px 8px',
            'border-radius': '4px',
            'cursor': 'pointer'
        });
    }

    // Inicializar colores
    $('.gc-estado-select').each(function() {
        aplicarColorSelect($(this));
    });

    // Cambiar estado individual
    $('.gc-estado-select').on('change', function() {
        var $select = $(this);
        var pedidoId = $select.data('pedido-id');
        var nuevoEstado = $select.val();
        var estadoOriginal = $select.data('estado-original');

        $select.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gc_cambiar_estado_pedido',
                pedido_id: pedidoId,
                estado: nuevoEstado,
                nonce: '<?php echo wp_create_nonce('gc_pedidos_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $select.data('estado-original', nuevoEstado);
                    aplicarColorSelect($select);
                    // Feedback visual
                    $select.css('box-shadow', '0 0 0 2px rgba(0,163,42,0.5)');
                    setTimeout(function() {
                        $select.css('box-shadow', '');
                    }, 1000);
                } else {
                    gcPedidosAviso(response.data || '<?php echo esc_js(__('Error al actualizar', 'flavor-platform')); ?>', 'error');
                    $select.val(estadoOriginal);
                    aplicarColorSelect($select);
                }
            },
            error: function() {
                gcPedidosAviso('<?php echo esc_js(__('Error de conexión', 'flavor-platform')); ?>', 'error');
                $select.val(estadoOriginal);
                aplicarColorSelect($select);
            },
            complete: function() {
                $select.prop('disabled', false);
            }
        });
    });

    // Marcar como preparado (todos los pedidos de un usuario)
    $('.gc-btn-marcar-completado').on('click', function() {
        var usuarioId = $(this).data('usuario');
        var $btn = $(this);
        var $card = $btn.closest('.gc-usuario-card');

        gcPedidosConfirmar('<?php echo esc_js(__('¿Marcar todos los pedidos de este usuario como completados?', 'flavor-platform')); ?>', function() {
            $btn.prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gc_marcar_pedidos_completados',
                    usuario_id: usuarioId,
                    ciclo_id: <?php echo $filtro_ciclo ?: 0; ?>,
                    nonce: '<?php echo wp_create_nonce('gc_pedidos_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar todos los selectores de este usuario
                        $card.find('.gc-estado-select').each(function() {
                            $(this).val('completado').data('estado-original', 'completado');
                            aplicarColorSelect($(this));
                        });
                        $card.find('.gc-usuario-header').css('background', 'linear-gradient(135deg, #00a32a 0%, #008a20 100%)');
                        $btn.html('<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Preparado', 'flavor-platform')); ?>');
                        gcPedidosAviso('<?php echo esc_js(__('Pedidos marcados como completados.', 'flavor-platform')); ?>', 'success');
                    } else {
                        gcPedidosAviso(response.data || '<?php echo esc_js(__('Error al actualizar', 'flavor-platform')); ?>', 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    gcPedidosAviso('<?php echo esc_js(__('Error de conexión', 'flavor-platform')); ?>', 'error');
                    $btn.prop('disabled', false);
                }
            });
        });
    });
});
</script>

<style>
.gc-inline-notice {
    margin: 12px 0 16px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 14px;
}
.gc-inline-notice.error { background: #fee2e2; color: #991b1b; }
.gc-inline-notice.success { background: #dcfce7; color: #166534; }
.gc-inline-confirm-actions { display:flex; gap:8px; margin-top:10px; }
</style>
