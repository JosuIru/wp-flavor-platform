<?php
/**
 * Template: Mi Pedido - Grupos de Consumo
 *
 * Lista de productos del pedido manual del usuario.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Templates
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="gc-cesta-login">';
    echo '<p>' . esc_html__('Inicia sesión para ver tu pedido.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mi-pedido'))) . '" class="gc-btn gc-btn-primary">';
    echo esc_html__('Iniciar sesión', 'flavor-chat-ia');
    echo '</a></div>';
    return;
}

$user_id = get_current_user_id();
global $wpdb;

$tabla_lista = $wpdb->prefix . 'flavor_gc_lista_compra';
$items = $wpdb->get_results($wpdb->prepare(
    "SELECT lc.*, p.post_title as producto_nombre,
            pm_precio.meta_value as precio,
            pm_unidad.meta_value as unidad
     FROM {$tabla_lista} lc
     LEFT JOIN {$wpdb->posts} p ON lc.producto_id = p.ID
     LEFT JOIN {$wpdb->postmeta} pm_precio ON lc.producto_id = pm_precio.post_id AND pm_precio.meta_key = '_gc_precio'
     LEFT JOIN {$wpdb->postmeta} pm_unidad ON lc.producto_id = pm_unidad.post_id AND pm_unidad.meta_key = '_gc_unidad'
     WHERE lc.usuario_id = %d
     ORDER BY lc.fecha_agregado DESC",
    $user_id
));

// Calcular totales
$subtotal = 0;
foreach ($items as $item) {
    $precio = (float) ($item->precio ?: 0);
    $cantidad = (float) $item->cantidad;
    $subtotal += $precio * $cantidad;
}

// Verificar si hay ciclo activo
$ciclo_activo = null;
$args = [
    'post_type' => 'gc_ciclo',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'meta_query' => [
        ['key' => '_gc_estado', 'value' => 'abierto'],
    ],
];
$query = new WP_Query($args);
if ($query->have_posts()) {
    $ciclo_activo = $query->posts[0];
}
?>

<div class="gc-cesta-container">
    <h2 class="gc-cesta-title">
        <span class="dashicons dashicons-cart"></span>
        <?php esc_html_e('Pedido actual', 'flavor-chat-ia'); ?>
    </h2>
    <div class="gc-inline-notice" id="gc-cesta-notice" style="display:none;"></div>

    <?php if (!$ciclo_activo) : ?>
    <div class="gc-cesta-notice gc-notice-warning">
        <span class="dashicons dashicons-info"></span>
        <?php esc_html_e('No hay ningún ciclo de pedido activo en este momento.', 'flavor-chat-ia'); ?>
    </div>
    <?php else : ?>
    <div class="gc-cesta-ciclo-info">
        <strong><?php echo esc_html($ciclo_activo->post_title); ?></strong>
        <?php
        $fecha_cierre = get_post_meta($ciclo_activo->ID, '_gc_fecha_cierre', true);
        if ($fecha_cierre) :
            $tiempo_restante = strtotime($fecha_cierre) - current_time('timestamp');
            if ($tiempo_restante > 0) :
        ?>
        <span class="gc-ciclo-cierre">
            <?php esc_html_e('Cierra en:', 'flavor-chat-ia'); ?>
            <strong>
            <?php
            if ($tiempo_restante < 3600) {
                printf(__('%d minutos', 'flavor-chat-ia'), ceil($tiempo_restante / 60));
            } elseif ($tiempo_restante < 86400) {
                printf(__('%d horas', 'flavor-chat-ia'), ceil($tiempo_restante / 3600));
            } else {
                printf(_n('%d día', '%d días', ceil($tiempo_restante / 86400), 'flavor-chat-ia'), ceil($tiempo_restante / 86400));
            }
            ?>
            </strong>
        </span>
        <?php else : ?>
        <span class="gc-ciclo-cerrado"><?php esc_html_e('Ciclo cerrado', 'flavor-chat-ia'); ?></span>
        <?php
            endif;
        endif;
        ?>
    </div>
    <?php endif; ?>

    <?php if (empty($items)) : ?>
    <div class="gc-cesta-empty">
        <span class="dashicons dashicons-products"></span>
        <p><?php esc_html_e('Tu pedido está vacío.', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'productos')); ?>" class="gc-btn gc-btn-primary">
            <?php esc_html_e('Ver productos', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php else : ?>

    <form id="gc-cesta-form" class="gc-cesta-form">
        <?php wp_nonce_field('gc_cesta_nonce', 'gc_cesta_nonce'); ?>

        <table class="gc-cesta-table">
            <thead>
                <tr>
                    <th class="gc-col-producto"><?php esc_html_e('Producto', 'flavor-chat-ia'); ?></th>
                    <th class="gc-col-precio"><?php esc_html_e('Precio', 'flavor-chat-ia'); ?></th>
                    <th class="gc-col-cantidad"><?php esc_html_e('Cantidad', 'flavor-chat-ia'); ?></th>
                    <th class="gc-col-subtotal"><?php esc_html_e('Subtotal', 'flavor-chat-ia'); ?></th>
                    <th class="gc-col-acciones"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item) :
                    $precio = (float) ($item->precio ?: 0);
                    $cantidad = (float) $item->cantidad;
                    $item_subtotal = $precio * $cantidad;
                    $unidad = $item->unidad ?: 'ud';
                ?>
                <tr class="gc-cesta-item" data-item-id="<?php echo esc_attr($item->id); ?>">
                    <td class="gc-col-producto">
                        <span class="gc-producto-nombre"><?php echo esc_html($item->producto_nombre ?: __('Producto', 'flavor-chat-ia')); ?></span>
                        <?php if ($item->notas) : ?>
                        <span class="gc-producto-notas"><?php echo esc_html($item->notas); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="gc-col-precio">
                        <?php echo number_format($precio, 2, ',', '.'); ?> €/<?php echo esc_html($unidad); ?>
                    </td>
                    <td class="gc-col-cantidad">
                        <div class="gc-cantidad-control">
                            <button type="button" class="gc-btn-cantidad gc-btn-menos" aria-label="<?php esc_attr_e('Reducir cantidad', 'flavor-chat-ia'); ?>">-</button>
                            <input type="number"
                                   name="items[<?php echo esc_attr($item->id); ?>][cantidad]"
                                   value="<?php echo esc_attr($cantidad); ?>"
                                   min="0"
                                   step="0.5"
                                   class="gc-input-cantidad"
                                   data-precio="<?php echo esc_attr($precio); ?>">
                            <button type="button" class="gc-btn-cantidad gc-btn-mas" aria-label="<?php esc_attr_e('Aumentar cantidad', 'flavor-chat-ia'); ?>">+</button>
                        </div>
                    </td>
                    <td class="gc-col-subtotal">
                        <span class="gc-item-subtotal"><?php echo number_format($item_subtotal, 2, ',', '.'); ?></span> €
                    </td>
                    <td class="gc-col-acciones">
                        <button type="button" class="gc-btn-eliminar" title="<?php esc_attr_e('Eliminar', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="gc-cesta-total">
                    <td colspan="3"><?php esc_html_e('Total', 'flavor-chat-ia'); ?></td>
                    <td colspan="2">
                        <strong><span id="gc-cesta-total"><?php echo number_format($subtotal, 2, ',', '.'); ?></span> €</strong>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="gc-cesta-actions">
            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'productos')); ?>" class="gc-btn gc-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                <?php esc_html_e('Seguir añadiendo productos', 'flavor-chat-ia'); ?>
            </a>

            <div class="gc-cesta-actions-right">
                <button type="button" id="gc-btn-actualizar" class="gc-btn gc-btn-secondary">
                    <?php esc_html_e('Actualizar pedido', 'flavor-chat-ia'); ?>
                </button>

                <?php if ($ciclo_activo && $tiempo_restante > 0) : ?>
                <button type="submit" id="gc-btn-confirmar" class="gc-btn gc-btn-primary">
                    <?php esc_html_e('Confirmar pedido', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <?php endif; ?>
</div>

<script>
(function($) {
    'use strict';

    var $form = $('#gc-cesta-form');

    function gcTabCestaNotice() {
        var $notice = $('#gc-tab-cesta-notice');
        if (!$notice.length) {
            $notice = $('<div id="gc-tab-cesta-notice" class="gc-cesta-inline-notice" style="display:none;"></div>').prependTo('.gc-cesta-container');
        }
        return $notice;
    }

    function gcTabCestaConfirmar(mensaje, onConfirm) {
        var $notice = gcTabCestaNotice();
        $notice.removeClass('success error')
            .addClass('error')
            .html('<p>' + mensaje + '</p><div class="gc-cesta-inline-confirm-actions"><button type="button" class="button button-primary gc-confirmar"><?php echo esc_js(__('Confirmar', 'flavor-chat-ia')); ?></button><button type="button" class="button gc-cancelar"><?php echo esc_js(__('Cancelar', 'flavor-chat-ia')); ?></button></div>')
            .show();

        $notice.off('click', '.gc-confirmar').on('click', '.gc-confirmar', function() {
            $notice.hide().empty();
            onConfirm();
        });

        $notice.off('click', '.gc-cancelar').on('click', '.gc-cancelar', function() {
            $notice.hide().empty();
        });
    }

    // Recalcular totales
    function recalcularTotales() {
        var total = 0;
        $('.gc-cesta-item').each(function() {
            var $row = $(this);
            var cantidad = parseFloat($row.find('.gc-input-cantidad').val()) || 0;
            var precio = parseFloat($row.find('.gc-input-cantidad').data('precio')) || 0;
            var subtotal = cantidad * precio;
            $row.find('.gc-item-subtotal').text(subtotal.toFixed(2).replace('.', ','));
            total += subtotal;
        });
        $('#gc-cesta-total').text(total.toFixed(2).replace('.', ','));
    }

    // Botones +/-
    $form.on('click', '.gc-btn-menos', function() {
        var $input = $(this).siblings('.gc-input-cantidad');
        var val = parseFloat($input.val()) || 0;
        var step = parseFloat($input.attr('step')) || 1;
        if (val > 0) {
            $input.val((val - step).toFixed(1).replace(/\.0$/, ''));
            recalcularTotales();
        }
    });

    $form.on('click', '.gc-btn-mas', function() {
        var $input = $(this).siblings('.gc-input-cantidad');
        var val = parseFloat($input.val()) || 0;
        var step = parseFloat($input.attr('step')) || 1;
        $input.val((val + step).toFixed(1).replace(/\.0$/, ''));
        recalcularTotales();
    });

    // Cambio manual de cantidad
    $form.on('change', '.gc-input-cantidad', function() {
        recalcularTotales();
    });

    // Eliminar item
    $form.on('click', '.gc-btn-eliminar', function() {
        var $row = $(this).closest('.gc-cesta-item');
        var itemId = $row.data('item-id');

        gcTabCestaConfirmar('<?php echo esc_js(__('¿Eliminar este producto del pedido actual?', 'flavor-chat-ia')); ?>', function() {
            $.ajax({
                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                type: 'POST',
                data: {
                    action: 'gc_remove_from_cart',
                    nonce: $('#gc_cesta_nonce').val(),
                    item_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            recalcularTotales();
                            if ($('.gc-cesta-item').length === 0) {
                                location.reload();
                            }
                        });
                    }
                }
            });
        });
    });

    // Actualizar pedido actual
    $('#gc-btn-actualizar').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Actualizando...', 'flavor-chat-ia')); ?>');

        var items = {};
        $('.gc-cesta-item').each(function() {
            var id = $(this).data('item-id');
            var cantidad = $(this).find('.gc-input-cantidad').val();
            items[id] = cantidad;
        });

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'gc_update_cart',
                nonce: $('#gc_cesta_nonce').val(),
                items: items
            },
            success: function(response) {
                $btn.prop('disabled', false).text('<?php echo esc_js(__('Actualizar pedido', 'flavor-chat-ia')); ?>');
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    // Confirmar pedido
    $form.on('submit', function(e) {
        e.preventDefault();

        var $btn = $('#gc-btn-confirmar');
        var $notice = $('#gc-cesta-notice');

        function gcAviso(mensaje) {
            $notice.addClass('error').text(mensaje).show();
        }

        $btn.prop('disabled', true).text('<?php echo esc_js(__('Procesando...', 'flavor-chat-ia')); ?>');

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: $form.serialize() + '&action=gc_confirm_cart',
            success: function(response) {
                if (response.success && response.data.entrega_id) {
                    window.location.href = '<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'checkout')); ?>?entrega_id=' + response.data.entrega_id;
                } else {
                    gcAviso(response.data.error || '<?php echo esc_js(__('Error al confirmar el pedido.', 'flavor-chat-ia')); ?>');
                    $btn.prop('disabled', false).text('<?php echo esc_js(__('Confirmar pedido', 'flavor-chat-ia')); ?>');
                }
            },
            error: function() {
                gcAviso('<?php echo esc_js(__('Error de conexión.', 'flavor-chat-ia')); ?>');
                $btn.prop('disabled', false).text('<?php echo esc_js(__('Confirmar pedido', 'flavor-chat-ia')); ?>');
            }
        });
    });

})(jQuery);
</script>

<style>
.gc-inline-notice {
    margin: 0 0 16px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 0.95rem;
}
.gc-inline-notice.error {
    display: block !important;
    background: #fee2e2;
    color: #991b1b;
}
.gc-cesta-container {
    max-width: 900px;
}
.gc-cesta-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}
.gc-cesta-ciclo-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #e7f5e9;
    border-radius: 6px;
    margin-bottom: 20px;
}
.gc-ciclo-cierre {
    color: #666;
}
.gc-ciclo-cerrado {
    color: #dc3232;
    font-weight: 600;
}
.gc-cesta-notice {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.gc-notice-warning {
    background: #fff3cd;
    color: #856404;
}
.gc-cesta-empty {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 8px;
}
.gc-cesta-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ccc;
}
.gc-cesta-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.gc-cesta-table th,
.gc-cesta-table td {
    padding: 15px;
    text-align: left;
}
.gc-cesta-table th {
    background: #f5f5f5;
    font-weight: 600;
    font-size: 14px;
}
.gc-cesta-item td {
    border-bottom: 1px solid #eee;
}
.gc-producto-nombre {
    font-weight: 500;
}
.gc-producto-notas {
    display: block;
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}
.gc-cantidad-control {
    display: inline-flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.gc-btn-cantidad {
    width: 32px;
    height: 32px;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
    font-size: 16px;
}
.gc-btn-cantidad:hover {
    background: #eee;
}
.gc-input-cantidad {
    width: 65px;
    min-width: 65px;
    max-width: 65px;
    flex: 0 0 65px;
    box-sizing: border-box;
    text-align: center;
    border: none;
    padding: 5px;
}
.gc-input-cantidad::-webkit-inner-spin-button {
    -webkit-appearance: none;
}
.gc-btn-eliminar {
    background: none;
    border: none;
    cursor: pointer;
    color: #dc3232;
    padding: 5px;
}
.gc-btn-eliminar:hover {
    color: #a00;
}
.gc-cesta-total td {
    font-size: 18px;
    padding: 20px 15px;
}
.gc-cesta-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    flex-wrap: wrap;
    gap: 15px;
}
.gc-cesta-actions-right {
    display: flex;
    gap: 10px;
}
.gc-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
}
.gc-btn-primary {
    background: #0073aa;
    color: #fff;
}
.gc-btn-primary:hover {
    background: #005a87;
    color: #fff;
}
.gc-btn-secondary {
    background: #f0f0f0;
    color: #333;
}
.gc-btn-secondary:hover {
    background: #e0e0e0;
}
.gc-cesta-login {
    text-align: center;
    padding: 40px;
}
@media (max-width: 600px) {
    .gc-cesta-table th:nth-child(2),
    .gc-cesta-table td:nth-child(2) {
        display: none;
    }
    .gc-cesta-actions {
        flex-direction: column;
    }
    .gc-cesta-actions-right {
        flex-direction: column;
        width: 100%;
    }
    .gc-cesta-actions-right .gc-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
