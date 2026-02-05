<?php
/**
 * Vista Pedidos - Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

// Filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_ciclo = isset($_GET['ciclo']) ? absint($_GET['ciclo']) : 0;

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

if (!empty($preparar)) {
    $pedidos = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_pedidos WHERE $where_sql ORDER BY fecha_pedido DESC LIMIT 100",
        ...$preparar
    ));
} else {
    $pedidos = $wpdb->get_results("SELECT * FROM $tabla_pedidos WHERE $where_sql ORDER BY fecha_pedido DESC LIMIT 100");
}

// Ciclos disponibles
$ciclos = get_posts(['post_type' => 'gc_ciclo', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC']);

?>

<div class="wrap">
    <h1><span class="dashicons dashicons-clipboard"></span> Gestión de Pedidos</h1>
    <hr class="wp-header-end">

    <div class="tablenav top">
        <form method="get" style="display: inline-flex; gap: 8px;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
            <select name="estado">
                <option value="">Todos los estados</option>
                <option value="pendiente" <?php selected($filtro_estado, 'pendiente'); ?>>Pendiente</option>
                <option value="confirmado" <?php selected($filtro_estado, 'confirmado'); ?>>Confirmado</option>
                <option value="completado" <?php selected($filtro_estado, 'completado'); ?>>Completado</option>
                <option value="cancelado" <?php selected($filtro_estado, 'cancelado'); ?>>Cancelado</option>
            </select>
            <select name="ciclo">
                <option value="0">Todos los ciclos</option>
                <?php foreach ($ciclos as $ciclo): ?>
                    <option value="<?php echo $ciclo->ID; ?>" <?php selected($filtro_ciclo, $ciclo->ID); ?>>
                        <?php echo esc_html($ciclo->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button">Filtrar</button>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Usuario</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido):
                $producto = get_post($pedido->producto_id);
                $usuario = get_userdata($pedido->usuario_id);
                $total = $pedido->cantidad * $pedido->precio_unitario;
            ?>
            <tr>
                <td>#<?php echo $pedido->id; ?></td>
                <td><?php echo $producto ? esc_html($producto->post_title) : 'N/A'; ?></td>
                <td><?php echo $usuario ? esc_html($usuario->display_name) : 'N/A'; ?></td>
                <td><?php echo number_format($pedido->cantidad, 2); ?></td>
                <td><?php echo number_format($pedido->precio_unitario, 2); ?> €</td>
                <td><strong><?php echo number_format($total, 2); ?> €</strong></td>
                <td><?php echo ucfirst($pedido->estado); ?></td>
                <td><?php echo date_i18n('d/m/Y', strtotime($pedido->fecha_pedido)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
