<?php
/**
 * Template: Mi Pedido - Grupos de Consumo
 *
 * Vista completa del pedido manual del usuario con gestion de productos.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Templates
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="gc-cesta-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Acceso restringido', 'flavor-chat-ia') . '</h3>';
    echo '<p>' . esc_html__('Inicia sesión para ver y gestionar tu pedido.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(home_url('/mi-portal/grupos-consumo/mi-pedido/'))) . '" class="gc-btn gc-btn-primary">';
    echo esc_html__('Iniciar sesión', 'flavor-chat-ia');
    echo '</a></div>';
    return;
}

global $wpdb;
$user_id = get_current_user_id();

// Obtener items del pedido actual desde la tabla
$tabla_lista = $wpdb->prefix . 'flavor_gc_lista_compra';
$items_cesta = $wpdb->get_results($wpdb->prepare(
    "SELECT lc.*,
            p.post_title as producto_nombre,
            p.post_status as producto_status,
            pm_precio.meta_value as precio,
            pm_unidad.meta_value as unidad,
            pm_stock.meta_value as stock_disponible,
            pm_productor.meta_value as productor_id,
            pm_imagen.meta_value as imagen_id
     FROM {$tabla_lista} lc
     LEFT JOIN {$wpdb->posts} p ON lc.producto_id = p.ID
     LEFT JOIN {$wpdb->postmeta} pm_precio ON lc.producto_id = pm_precio.post_id AND pm_precio.meta_key = '_gc_precio'
     LEFT JOIN {$wpdb->postmeta} pm_unidad ON lc.producto_id = pm_unidad.post_id AND pm_unidad.meta_key = '_gc_unidad'
     LEFT JOIN {$wpdb->postmeta} pm_stock ON lc.producto_id = pm_stock.post_id AND pm_stock.meta_key = '_gc_stock'
     LEFT JOIN {$wpdb->postmeta} pm_productor ON lc.producto_id = pm_productor.post_id AND pm_productor.meta_key = '_gc_productor_id'
     LEFT JOIN {$wpdb->postmeta} pm_imagen ON lc.producto_id = pm_imagen.post_id AND pm_imagen.meta_key = '_thumbnail_id'
     WHERE lc.usuario_id = %d
     ORDER BY lc.fecha_agregado DESC",
    $user_id
));

// Verificar ciclo activo
$ciclo_activo = null;
$fecha_cierre = null;
$tiempo_restante = 0;
$args_ciclo = [
    'post_type'      => 'gc_ciclo',
    'post_status'    => ['publish', 'gc_abierto'],
    'posts_per_page' => 1,
    'meta_query'     => [
        ['key' => '_gc_estado', 'value' => 'abierto'],
    ],
];
$query_ciclo = new WP_Query($args_ciclo);
if ($query_ciclo->have_posts()) {
    $ciclo_activo = $query_ciclo->posts[0];
    $fecha_cierre = get_post_meta($ciclo_activo->ID, '_gc_fecha_cierre', true);
    $tiempo_restante = $fecha_cierre ? strtotime($fecha_cierre) - current_time('timestamp') : 0;
}

// Calcular totales
$subtotal = 0;
$total_items = 0;
foreach ($items_cesta as $item) {
    $precio = (float) ($item->precio ?: 0);
    $cantidad = (float) $item->cantidad;
    $subtotal += $precio * $cantidad;
    $total_items++;
}

// Gastos de gestion
$porcentaje_gestion = $ciclo_activo ? (float) get_post_meta($ciclo_activo->ID, '_gc_gastos_gestion', true) : 0;
$gastos_gestion = $subtotal * ($porcentaje_gestion / 100);
$total_final = $subtotal + $gastos_gestion;
?>

<div class="gc-mi-cesta-container">
    <header class="gc-cesta-header">
        <div class="gc-cesta-titulo">
            <h2>
                <span class="dashicons dashicons-cart"></span>
                <?php esc_html_e('Pedido actual', 'flavor-chat-ia'); ?>
            </h2>
            <?php if ($total_items > 0) : ?>
            <span class="gc-cesta-count"><?php printf(_n('%d producto', '%d productos', $total_items, 'flavor-chat-ia'), $total_items); ?></span>
            <?php endif; ?>
        </div>

        <?php if ($ciclo_activo && $tiempo_restante > 0) : ?>
        <div class="gc-cesta-ciclo-info">
            <span class="gc-ciclo-badge gc-ciclo-abierto">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Ciclo abierto', 'flavor-chat-ia'); ?>
            </span>
            <div class="gc-ciclo-cierre">
                <span class="gc-cierre-label"><?php esc_html_e('Cierra en:', 'flavor-chat-ia'); ?></span>
                <strong class="gc-cierre-tiempo" id="gc-countdown" data-cierre="<?php echo esc_attr($fecha_cierre); ?>">
                    <?php
                    if ($tiempo_restante < 3600) {
                        printf(__('%d min', 'flavor-chat-ia'), ceil($tiempo_restante / 60));
                    } elseif ($tiempo_restante < 86400) {
                        printf(__('%d horas', 'flavor-chat-ia'), ceil($tiempo_restante / 3600));
                    } else {
                        printf(_n('%d dia', '%d dias', ceil($tiempo_restante / 86400), 'flavor-chat-ia'), ceil($tiempo_restante / 86400));
                    }
                    ?>
                </strong>
            </div>
        </div>
        <?php elseif (!$ciclo_activo) : ?>
        <div class="gc-cesta-ciclo-info gc-ciclo-cerrado-info">
            <span class="gc-ciclo-badge gc-ciclo-cerrado">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Sin ciclo activo', 'flavor-chat-ia'); ?>
            </span>
            <p class="gc-sin-ciclo-msg"><?php esc_html_e('Los productos se guardarán para el próximo ciclo.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php endif; ?>
    </header>

    <?php if (empty($items_cesta)) : ?>
    <div class="gc-cesta-empty">
        <span class="dashicons dashicons-products"></span>
        <h3><?php esc_html_e('Tu pedido está vacío', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('Explora los productos disponibles y añade artículos a tu pedido actual.', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/productos/')); ?>" class="gc-btn gc-btn-primary">
            <span class="dashicons dashicons-products"></span>
            <?php esc_html_e('Ver productos', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <?php else : ?>
    <form id="gc-cesta-form" class="gc-cesta-form">
        <?php wp_nonce_field('gc_cesta_nonce', 'gc_cesta_nonce'); ?>

        <div class="gc-cesta-content">
            <!-- Lista de productos -->
            <div class="gc-cesta-productos">
                <?php foreach ($items_cesta as $item) :
                    $precio = (float) ($item->precio ?: 0);
                    $cantidad = (float) $item->cantidad;
                    $item_subtotal = $precio * $cantidad;
                    $unidad = $item->unidad ?: 'ud';
                    $productor = $item->productor_id ? get_post($item->productor_id) : null;
                    $producto_disponible = $item->producto_status === 'publish';
                ?>
                <div class="gc-cesta-item <?php echo !$producto_disponible ? 'gc-item-no-disponible' : ''; ?>"
                     data-item-id="<?php echo esc_attr($item->id); ?>"
                     data-producto-id="<?php echo esc_attr($item->producto_id); ?>">

                    <div class="gc-item-imagen">
                        <?php if ($item->imagen_id) : ?>
                            <?php echo wp_get_attachment_image($item->imagen_id, 'thumbnail', false, ['class' => 'gc-item-thumb']); ?>
                        <?php else : ?>
                            <div class="gc-item-thumb-placeholder">
                                <span class="dashicons dashicons-carrot"></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="gc-item-info">
                        <h4 class="gc-item-nombre">
                            <?php if ($producto_disponible) : ?>
                            <a href="<?php echo esc_url(add_query_arg('product', intval($item->producto_id), home_url('/mi-portal/grupos-consumo/productos/'))); ?>">
                                <?php echo esc_html($item->producto_nombre ?: __('Producto', 'flavor-chat-ia')); ?>
                            </a>
                            <?php else : ?>
                            <span class="gc-producto-no-disponible">
                                <?php echo esc_html($item->producto_nombre ?: __('Producto', 'flavor-chat-ia')); ?>
                                <small>(<?php esc_html_e('No disponible', 'flavor-chat-ia'); ?>)</small>
                            </span>
                            <?php endif; ?>
                        </h4>

                        <?php if ($productor) : ?>
                        <span class="gc-item-productor">
                            <span class="dashicons dashicons-store"></span>
                            <?php echo esc_html($productor->post_title); ?>
                        </span>
                        <?php endif; ?>

                        <div class="gc-item-precio-unitario">
                            <?php echo number_format($precio, 2, ',', '.'); ?> &euro;/<?php echo esc_html($unidad); ?>
                        </div>

                        <?php if ($item->notas) : ?>
                        <div class="gc-item-notas">
                            <span class="dashicons dashicons-format-status"></span>
                            <?php echo esc_html($item->notas); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="gc-item-cantidad">
                        <label class="gc-cantidad-label"><?php esc_html_e('Cantidad', 'flavor-chat-ia'); ?></label>
                        <div class="gc-cantidad-control">
                            <button type="button" class="gc-btn-cantidad gc-btn-menos" aria-label="<?php esc_attr_e('Reducir', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-minus"></span>
                            </button>
                            <input type="number"
                                   name="items[<?php echo esc_attr($item->id); ?>][cantidad]"
                                   value="<?php echo esc_attr($cantidad); ?>"
                                   min="0"
                                   step="0.5"
                                   class="gc-input-cantidad"
                                   data-precio="<?php echo esc_attr($precio); ?>"
                                   aria-label="<?php esc_attr_e('Cantidad', 'flavor-chat-ia'); ?>">
                            <button type="button" class="gc-btn-cantidad gc-btn-mas" aria-label="<?php esc_attr_e('Aumentar', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-plus-alt2"></span>
                            </button>
                        </div>
                    </div>

                    <div class="gc-item-subtotal">
                        <span class="gc-subtotal-label"><?php esc_html_e('Subtotal', 'flavor-chat-ia'); ?></span>
                        <span class="gc-subtotal-valor">
                            <span class="gc-item-subtotal-valor"><?php echo number_format($item_subtotal, 2, ',', '.'); ?></span> &euro;
                        </span>
                    </div>

                    <div class="gc-item-acciones">
                        <button type="button" class="gc-btn-eliminar" title="<?php esc_attr_e('Eliminar producto', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Resumen -->
            <div class="gc-cesta-resumen">
                <div class="gc-resumen-card">
                    <h3><?php esc_html_e('Resumen del pedido', 'flavor-chat-ia'); ?></h3>

                    <div class="gc-resumen-lineas">
                        <div class="gc-resumen-linea">
                            <span><?php esc_html_e('Subtotal productos', 'flavor-chat-ia'); ?></span>
                            <span id="gc-resumen-subtotal"><?php echo number_format($subtotal, 2, ',', '.'); ?> &euro;</span>
                        </div>

                        <?php if ($porcentaje_gestion > 0) : ?>
                        <div class="gc-resumen-linea gc-linea-gestion">
                            <span>
                                <?php esc_html_e('Gastos de gestion', 'flavor-chat-ia'); ?>
                                <small>(<?php echo esc_html($porcentaje_gestion); ?>%)</small>
                            </span>
                            <span id="gc-resumen-gestion"><?php echo number_format($gastos_gestion, 2, ',', '.'); ?> &euro;</span>
                        </div>
                        <?php endif; ?>

                        <div class="gc-resumen-linea gc-linea-total">
                            <strong><?php esc_html_e('Total', 'flavor-chat-ia'); ?></strong>
                            <strong id="gc-resumen-total"><?php echo number_format($total_final, 2, ',', '.'); ?> &euro;</strong>
                        </div>
                    </div>

                    <div class="gc-resumen-acciones">
                        <button type="button" id="gc-btn-actualizar" class="gc-btn gc-btn-secondary gc-btn-block">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Guardar cambios', 'flavor-chat-ia'); ?>
                        </button>

                        <?php if ($ciclo_activo && $tiempo_restante > 0) : ?>
                        <button type="submit" id="gc-btn-confirmar" class="gc-btn gc-btn-primary gc-btn-block gc-btn-lg">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e('Confirmar pedido', 'flavor-chat-ia'); ?>
                        </button>
                        <?php else : ?>
                        <div class="gc-sin-ciclo-aviso">
                            <span class="dashicons dashicons-info"></span>
                            <p><?php esc_html_e('Podrás confirmar tu pedido cuando se abra el próximo ciclo.', 'flavor-chat-ia'); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="gc-resumen-info">
                        <p>
                            <span class="dashicons dashicons-location"></span>
                            <?php
                            if ($ciclo_activo) {
                                $lugar_entrega = get_post_meta($ciclo_activo->ID, '_gc_lugar_entrega', true);
                                $fecha_entrega = get_post_meta($ciclo_activo->ID, '_gc_fecha_entrega', true);
                                if ($lugar_entrega) {
                                    printf(
                                        esc_html__('Entrega: %s', 'flavor-chat-ia'),
                                        esc_html($lugar_entrega)
                                    );
                                }
                            } else {
                                esc_html_e('La entrega se confirmará al abrir el ciclo.', 'flavor-chat-ia');
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/productos/')); ?>" class="gc-btn gc-btn-text gc-btn-block">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php esc_html_e('Seguir añadiendo productos', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
(function($) {
    'use strict';

    function gcCestaAviso(mensaje, tipo) {
        tipo = tipo || 'error';
        $('.gc-cesta-inline-notice').remove();
        $('<div class="gc-cesta-inline-notice gc-cesta-inline-notice-' + tipo + '"><p>' + mensaje + '</p></div>').insertBefore('#gc-form-confirmar').hide().fadeIn(150);
    }

    var $form = $('#gc-cesta-form');
    var porcentajeGestion = <?php echo esc_js($porcentaje_gestion); ?>;

    // Recalcular totales
    function recalcularTotales() {
        var subtotal = 0;

        $('.gc-cesta-item').each(function() {
            var $item = $(this);
            var cantidad = parseFloat($item.find('.gc-input-cantidad').val()) || 0;
            var precio = parseFloat($item.find('.gc-input-cantidad').data('precio')) || 0;
            var itemSubtotal = cantidad * precio;

            $item.find('.gc-item-subtotal-valor').text(itemSubtotal.toFixed(2).replace('.', ','));
            subtotal += itemSubtotal;
        });

        var gestion = subtotal * (porcentajeGestion / 100);
        var total = subtotal + gestion;

        $('#gc-resumen-subtotal').text(subtotal.toFixed(2).replace('.', ',') + ' \u20AC');
        $('#gc-resumen-gestion').text(gestion.toFixed(2).replace('.', ',') + ' \u20AC');
        $('#gc-resumen-total').text(total.toFixed(2).replace('.', ',') + ' \u20AC');
    }

    function gcCestaConfirmar(mensaje, onConfirm) {
        $('.gc-cesta-inline-confirm').remove();
        var $confirm = $('<div class="gc-cesta-inline-confirm"><p></p><div class="gc-cesta-inline-confirm-actions"><button type="button" class="button button-primary gc-cesta-inline-confirm-ok"><?php echo esc_js(__('Confirmar', 'flavor-chat-ia')); ?></button><button type="button" class="button gc-cesta-inline-confirm-cancel"><?php echo esc_js(__('Cancelar', 'flavor-chat-ia')); ?></button></div></div>');
        $confirm.find('p').text(mensaje);
        $confirm.insertBefore('#gc-form-confirmar').hide().fadeIn(150);

        $confirm.on('click', '.gc-cesta-inline-confirm-ok', function() {
            $confirm.remove();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });

        $confirm.on('click', '.gc-cesta-inline-confirm-cancel', function() {
            $confirm.remove();
        });
    }

    // Botones +/-
    $form.on('click', '.gc-btn-menos', function() {
        var $input = $(this).siblings('.gc-input-cantidad');
        var valor = parseFloat($input.val()) || 0;
        var step = parseFloat($input.attr('step')) || 1;
        if (valor > 0) {
            $input.val(Math.max(0, valor - step).toFixed(1).replace(/\.0$/, ''));
            recalcularTotales();
        }
    });

    $form.on('click', '.gc-btn-mas', function() {
        var $input = $(this).siblings('.gc-input-cantidad');
        var valor = parseFloat($input.val()) || 0;
        var step = parseFloat($input.attr('step')) || 1;
        $input.val((valor + step).toFixed(1).replace(/\.0$/, ''));
        recalcularTotales();
    });

    $form.on('change', '.gc-input-cantidad', function() {
        recalcularTotales();
    });

    // Eliminar item
    $form.on('click', '.gc-btn-eliminar', function() {
        var $item = $(this).closest('.gc-cesta-item');
        var itemId = $item.data('item-id');

        gcCestaConfirmar('<?php echo esc_js(__('¿Eliminar este producto del pedido actual?', 'flavor-chat-ia')); ?>', function() {
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
                        $item.slideUp(300, function() {
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
        var textoOriginal = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js(__('Guardando...', 'flavor-chat-ia')); ?>');

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
                $btn.prop('disabled', false).html(textoOriginal);
                if (response.success) {
                    // Mostrar mensaje de exito
                    $('<div class="gc-toast gc-toast-success"><?php echo esc_js(__('Pedido actualizado', 'flavor-chat-ia')); ?></div>')
                        .appendTo('body')
                        .fadeIn()
                        .delay(2000)
                        .fadeOut(function() { $(this).remove(); });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html(textoOriginal);
            }
        });
    });

    // Confirmar pedido
    $form.on('submit', function(e) {
        e.preventDefault();

        var $btn = $('#gc-btn-confirmar');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js(__('Procesando...', 'flavor-chat-ia')); ?>');

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: $form.serialize() + '&action=gc_confirm_cart',
            success: function(response) {
                if (response.success && response.data.entrega_id) {
                    window.location.href = '<?php echo esc_url(home_url('/mi-portal/grupos-consumo/checkout/')); ?>?entrega_id=' + response.data.entrega_id;
                } else {
                    gcCestaAviso(response.data.error || '<?php echo esc_js(__('Error al confirmar el pedido.', 'flavor-chat-ia')); ?>', 'error');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Confirmar pedido', 'flavor-chat-ia')); ?>');
                }
            },
            error: function() {
                gcCestaAviso('<?php echo esc_js(__('Error de conexion.', 'flavor-chat-ia')); ?>', 'error');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Confirmar pedido', 'flavor-chat-ia')); ?>');
            }
        });
    });

})(jQuery);
</script>

<style>
.gc-cesta-inline-notice{margin:0 0 16px;padding:12px 14px;border-left:4px solid #d63638;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.gc-cesta-inline-notice-success{border-left-color:#00a32a}
.gc-cesta-inline-notice-error{border-left-color:#d63638}
.gc-cesta-inline-confirm{margin:0 0 16px;padding:12px 14px;border-left:4px solid #dba617;background:#fff8e1;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.gc-cesta-inline-confirm p{margin:0 0 10px}
.gc-cesta-inline-confirm-actions{display:flex;gap:8px;flex-wrap:wrap}
.gc-mi-cesta-container {
    max-width: 1000px;
}
.gc-cesta-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}
.gc-cesta-titulo {
    display: flex;
    align-items: center;
    gap: 15px;
}
.gc-cesta-titulo h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}
.gc-cesta-count {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.gc-cesta-ciclo-info {
    display: flex;
    align-items: center;
    gap: 15px;
}
.gc-ciclo-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.gc-ciclo-abierto {
    background: #4caf50;
    color: #fff;
}
.gc-ciclo-cerrado {
    background: #9e9e9e;
    color: #fff;
}
.gc-ciclo-cierre {
    text-align: right;
}
.gc-cierre-label {
    display: block;
    font-size: 12px;
    color: #666;
}
.gc-cierre-tiempo {
    font-size: 1.1rem;
    color: #f57c00;
}
.gc-sin-ciclo-msg {
    margin: 0;
    font-size: 13px;
    color: #666;
}
.gc-cesta-login-required,
.gc-cesta-empty {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 10px;
}
.gc-cesta-login-required .dashicons,
.gc-cesta-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ccc;
    margin-bottom: 15px;
}
.gc-cesta-login-required h3,
.gc-cesta-empty h3 {
    margin: 0 0 10px 0;
    color: #666;
}
.gc-cesta-login-required p,
.gc-cesta-empty p {
    color: #999;
    margin-bottom: 20px;
}
.gc-cesta-content {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 30px;
    align-items: start;
}
.gc-cesta-productos {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.gc-cesta-item {
    display: grid;
    grid-template-columns: 80px 1fr auto auto auto;
    gap: 15px;
    align-items: center;
    padding: 15px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.gc-item-no-disponible {
    opacity: 0.6;
    background: #fafafa;
}
.gc-item-imagen {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    background: #f5f5f5;
}
.gc-item-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.gc-item-thumb-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8f5e9;
}
.gc-item-thumb-placeholder .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #a5d6a7;
}
.gc-item-info {
    min-width: 0;
}
.gc-item-nombre {
    margin: 0 0 5px 0;
    font-size: 15px;
}
.gc-item-nombre a {
    color: #333;
    text-decoration: none;
}
.gc-item-nombre a:hover {
    color: #4caf50;
}
.gc-producto-no-disponible {
    color: #999;
}
.gc-producto-no-disponible small {
    color: #f44336;
}
.gc-item-productor {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: #757575;
    margin-bottom: 5px;
}
.gc-item-productor .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}
.gc-item-precio-unitario {
    font-size: 14px;
    color: #4caf50;
    font-weight: 600;
}
.gc-item-notas {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #9e9e9e;
    margin-top: 5px;
}
.gc-item-cantidad {
    text-align: center;
}
.gc-cantidad-label {
    display: block;
    font-size: 11px;
    color: #999;
    margin-bottom: 5px;
}
.gc-cantidad-control {
    display: inline-flex;
    align-items: center;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    overflow: hidden;
}
.gc-btn-cantidad {
    width: 36px;
    height: 36px;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.gc-btn-cantidad:hover {
    background: #e0e0e0;
}
.gc-btn-cantidad .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}
.gc-input-cantidad {
    width: 65px;
    min-width: 65px;
    max-width: 65px;
    flex: 0 0 65px;
    box-sizing: border-box;
    height: 36px;
    border: none;
    text-align: center;
    font-size: 15px;
    font-weight: 600;
}
.gc-input-cantidad::-webkit-inner-spin-button {
    -webkit-appearance: none;
}
.gc-item-subtotal {
    text-align: right;
    min-width: 80px;
}
.gc-subtotal-label {
    display: block;
    font-size: 11px;
    color: #999;
}
.gc-subtotal-valor {
    font-size: 16px;
    font-weight: 700;
    color: #333;
}
.gc-item-acciones {
    padding-left: 10px;
}
.gc-btn-eliminar {
    width: 36px;
    height: 36px;
    border: none;
    background: none;
    cursor: pointer;
    color: #bdbdbd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.gc-btn-eliminar:hover {
    background: #ffebee;
    color: #f44336;
}
.gc-resumen-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 20px;
    position: sticky;
    top: 20px;
}
.gc-resumen-card h3 {
    margin: 0 0 20px 0;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
    font-size: 16px;
}
.gc-resumen-lineas {
    margin-bottom: 20px;
}
.gc-resumen-linea {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 14px;
}
.gc-linea-gestion {
    color: #757575;
    border-bottom: 1px solid #eee;
}
.gc-linea-gestion small {
    font-size: 12px;
}
.gc-linea-total {
    font-size: 18px;
    padding-top: 15px;
}
.gc-resumen-acciones {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.gc-sin-ciclo-aviso {
    display: flex;
    gap: 10px;
    padding: 12px;
    background: #fff3e0;
    border-radius: 6px;
    font-size: 13px;
}
.gc-sin-ciclo-aviso .dashicons {
    color: #f57c00;
    flex-shrink: 0;
}
.gc-sin-ciclo-aviso p {
    margin: 0;
    color: #e65100;
}
.gc-resumen-info {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}
.gc-resumen-info p {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: 0;
    font-size: 13px;
    color: #666;
}
.gc-resumen-info .dashicons {
    color: #9e9e9e;
    flex-shrink: 0;
}
.gc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}
.gc-btn-block {
    width: 100%;
}
.gc-btn-lg {
    padding: 14px 24px;
    font-size: 16px;
}
.gc-btn-primary {
    background: #4caf50;
    color: #fff;
}
.gc-btn-primary:hover {
    background: #388e3c;
    color: #fff;
}
.gc-btn-secondary {
    background: #e0e0e0;
    color: #333;
}
.gc-btn-secondary:hover {
    background: #bdbdbd;
}
.gc-btn-text {
    background: none;
    color: #666;
    margin-top: 10px;
}
.gc-btn-text:hover {
    color: #333;
}
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
.gc-toast {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    z-index: 9999;
    display: none;
}
.gc-toast-success {
    background: #4caf50;
    color: #fff;
}
@media (max-width: 900px) {
    .gc-cesta-content {
        grid-template-columns: 1fr;
    }
    .gc-resumen-card {
        position: static;
    }
}
@media (max-width: 600px) {
    .gc-cesta-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .gc-cesta-item {
        grid-template-columns: 60px 1fr;
        grid-template-rows: auto auto auto;
    }
    .gc-item-imagen {
        width: 60px;
        height: 60px;
        grid-row: span 2;
    }
    .gc-item-cantidad,
    .gc-item-subtotal {
        grid-column: 2;
        text-align: left;
    }
    .gc-item-acciones {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    .gc-cesta-item {
        position: relative;
    }
}
</style>
