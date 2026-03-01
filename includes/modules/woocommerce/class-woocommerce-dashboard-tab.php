<?php
/**
 * Dashboard Tab para integración WooCommerce
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Woocommerce_Dashboard_Tab {

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
        // Solo si WooCommerce está activo
        if (!class_exists('WooCommerce')) {
            return $tabs;
        }

        $tabs['tienda'] = [
            'label' => __('Mi Tienda', 'flavor-chat-ia'),
            'icon' => 'dashicons-cart',
            'callback' => [$this, 'render_tab'],
            'priority' => 55,
        ];
        return $tabs;
    }

    public function render_tab() {
        $datos = $this->obtener_datos_usuario();
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'pedidos';

        ?>
        <div class="flavor-woocommerce-dashboard">
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=tienda&subtab=pedidos" class="subtab <?php echo $subtab === 'pedidos' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-clipboard"></span> Mis Pedidos
                    <?php if ($datos['pedidos_pendientes'] > 0): ?>
                        <span class="badge"><?php echo $datos['pedidos_pendientes']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=tienda&subtab=descargas" class="subtab <?php echo $subtab === 'descargas' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-download"></span> Descargas
                </a>
                <a href="?tab=tienda&subtab=direcciones" class="subtab <?php echo $subtab === 'direcciones' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-location"></span> Direcciones
                </a>
                <a href="?tab=tienda&subtab=suscripciones" class="subtab <?php echo $subtab === 'suscripciones' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-update"></span> Suscripciones
                </a>
                <a href="?tab=tienda&subtab=favoritos" class="subtab <?php echo $subtab === 'favoritos' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-heart"></span> Favoritos
                </a>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                switch ($subtab) {
                    case 'descargas':
                        $this->render_descargas($datos);
                        break;
                    case 'direcciones':
                        $this->render_direcciones($datos);
                        break;
                    case 'suscripciones':
                        $this->render_suscripciones($datos);
                        break;
                    case 'favoritos':
                        $this->render_favoritos($datos);
                        break;
                    default:
                        $this->render_pedidos($datos);
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_pedidos($datos) {
        ?>
        <!-- KPIs -->
        <div class="flavor-kpi-grid">
            <?php
            flavor_render_component('shared/kpi-card', [
                'label' => 'Total Pedidos',
                'value' => $datos['total_pedidos'],
                'icon' => 'dashicons-clipboard',
                'color' => 'blue'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'En Proceso',
                'value' => $datos['pedidos_pendientes'],
                'icon' => 'dashicons-update',
                'color' => 'yellow'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Total Gastado',
                'value' => wc_price($datos['total_gastado']),
                'icon' => 'dashicons-money-alt',
                'color' => 'green'
            ]);
            flavor_render_component('shared/kpi-card', [
                'label' => 'Puntos',
                'value' => $datos['puntos_fidelidad'],
                'icon' => 'dashicons-star-filled',
                'color' => 'purple'
            ]);
            ?>
        </div>

        <!-- Lista de pedidos -->
        <div class="pedidos-lista">
            <?php if (empty($datos['pedidos'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-cart"></span>
                    <p>Aún no has realizado ningún pedido</p>
                    <a href="<?php echo wc_get_page_permalink('shop'); ?>" class="flavor-btn flavor-btn-primary">Ir a la tienda</a>
                </div>
            <?php else: ?>
                <?php foreach ($datos['pedidos'] as $pedido): ?>
                    <div class="pedido-card estado-<?php echo $pedido->get_status(); ?>">
                        <div class="pedido-header">
                            <div class="pedido-numero">
                                <strong>#<?php echo $pedido->get_order_number(); ?></strong>
                                <span class="fecha"><?php echo wc_format_datetime($pedido->get_date_created()); ?></span>
                            </div>
                            <span class="badge badge-<?php echo $pedido->get_status(); ?>">
                                <?php echo wc_get_order_status_name($pedido->get_status()); ?>
                            </span>
                        </div>

                        <div class="pedido-items">
                            <?php
                            $items = $pedido->get_items();
                            $mostrar = 3;
                            $contador = 0;
                            foreach ($items as $item):
                                if ($contador >= $mostrar) break;
                                $producto = $item->get_product();
                            ?>
                                <div class="pedido-item">
                                    <?php if ($producto && $producto->get_image_id()): ?>
                                        <?php echo $producto->get_image([50, 50]); ?>
                                    <?php endif; ?>
                                    <div class="item-info">
                                        <span class="item-nombre"><?php echo esc_html($item->get_name()); ?></span>
                                        <span class="item-cantidad">x<?php echo $item->get_quantity(); ?></span>
                                    </div>
                                </div>
                            <?php
                                $contador++;
                            endforeach;

                            if (count($items) > $mostrar):
                            ?>
                                <span class="mas-items">+<?php echo count($items) - $mostrar; ?> productos más</span>
                            <?php endif; ?>
                        </div>

                        <div class="pedido-footer">
                            <div class="pedido-total">
                                <span>Total:</span>
                                <strong><?php echo $pedido->get_formatted_order_total(); ?></strong>
                            </div>
                            <div class="pedido-acciones">
                                <a href="<?php echo $pedido->get_view_order_url(); ?>" class="flavor-btn flavor-btn-sm">Ver detalle</a>
                                <?php if ($pedido->get_status() === 'completed'): ?>
                                    <a href="<?php echo $pedido->get_view_order_url(); ?>#reviews" class="flavor-btn flavor-btn-sm">Valorar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <style>
            .pedido-card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
            .pedido-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
            .pedido-numero strong { font-size: 16px; }
            .pedido-numero .fecha { display: block; font-size: 13px; color: #666; margin-top: 3px; }
            .pedido-items { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
            .pedido-item { display: flex; align-items: center; gap: 10px; background: #f8f9fa; padding: 8px 12px; border-radius: 8px; }
            .pedido-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
            .item-nombre { font-size: 13px; }
            .item-cantidad { font-size: 12px; color: #666; }
            .mas-items { font-size: 13px; color: #666; align-self: center; }
            .pedido-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #eee; }
            .pedido-total strong { font-size: 18px; color: #333; }
            .badge-processing { background: #ff9800; color: #fff; }
            .badge-completed { background: #4caf50; color: #fff; }
            .badge-pending { background: #9e9e9e; color: #fff; }
            .badge-on-hold { background: #2196f3; color: #fff; }
        </style>
        <?php
    }

    private function render_descargas($datos) {
        ?>
        <div class="descargas-lista">
            <h3>Mis descargas</h3>

            <?php if (empty($datos['descargas'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-download"></span>
                    <p>No tienes productos descargables</p>
                </div>
            <?php else: ?>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Descargas restantes</th>
                            <th>Expira</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['descargas'] as $descarga): ?>
                            <tr>
                                <td><?php echo esc_html($descarga['product_name']); ?></td>
                                <td>
                                    <?php
                                    if ($descarga['downloads_remaining'] === '') {
                                        echo 'Ilimitadas';
                                    } else {
                                        echo $descarga['downloads_remaining'];
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($descarga['access_expires']) {
                                        echo date_i18n('j M Y', strtotime($descarga['access_expires']));
                                    } else {
                                        echo 'Nunca';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($descarga['download_url']); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                        <span class="dashicons dashicons-download"></span> Descargar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_direcciones($datos) {
        $customer = new WC_Customer(get_current_user_id());
        ?>
        <div class="direcciones-grid">
            <!-- Dirección de facturación -->
            <div class="direccion-card">
                <h4>Dirección de facturación</h4>
                <address>
                    <?php
                    $facturacion = $customer->get_billing();
                    if (!empty(array_filter($facturacion))) {
                        echo esc_html($facturacion['first_name'] . ' ' . $facturacion['last_name']) . '<br>';
                        echo esc_html($facturacion['address_1']) . '<br>';
                        if ($facturacion['address_2']) echo esc_html($facturacion['address_2']) . '<br>';
                        echo esc_html($facturacion['postcode'] . ' ' . $facturacion['city']) . '<br>';
                        echo esc_html(WC()->countries->countries[$facturacion['country']] ?? $facturacion['country']) . '<br>';
                        if ($facturacion['phone']) echo '<br>Tel: ' . esc_html($facturacion['phone']);
                        if ($facturacion['email']) echo '<br>Email: ' . esc_html($facturacion['email']);
                    } else {
                        echo '<em>No configurada</em>';
                    }
                    ?>
                </address>
                <a href="<?php echo wc_get_endpoint_url('edit-address', 'billing'); ?>" class="flavor-btn flavor-btn-sm">Editar</a>
            </div>

            <!-- Dirección de envío -->
            <div class="direccion-card">
                <h4>Dirección de envío</h4>
                <address>
                    <?php
                    $envio = $customer->get_shipping();
                    if (!empty(array_filter($envio))) {
                        echo esc_html($envio['first_name'] . ' ' . $envio['last_name']) . '<br>';
                        echo esc_html($envio['address_1']) . '<br>';
                        if ($envio['address_2']) echo esc_html($envio['address_2']) . '<br>';
                        echo esc_html($envio['postcode'] . ' ' . $envio['city']) . '<br>';
                        echo esc_html(WC()->countries->countries[$envio['country']] ?? $envio['country']);
                    } else {
                        echo '<em>No configurada</em>';
                    }
                    ?>
                </address>
                <a href="<?php echo wc_get_endpoint_url('edit-address', 'shipping'); ?>" class="flavor-btn flavor-btn-sm">Editar</a>
            </div>
        </div>

        <style>
            .direcciones-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
            .direccion-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
            .direccion-card h4 { margin-bottom: 15px; }
            .direccion-card address { font-style: normal; line-height: 1.6; margin-bottom: 15px; }
        </style>
        <?php
    }

    private function render_suscripciones($datos) {
        ?>
        <div class="suscripciones-lista">
            <h3>Mis suscripciones</h3>

            <?php if (!class_exists('WC_Subscriptions')): ?>
                <p>El módulo de suscripciones no está activo.</p>
            <?php elseif (empty($datos['suscripciones'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-update"></span>
                    <p>No tienes suscripciones activas</p>
                </div>
            <?php else: ?>
                <?php foreach ($datos['suscripciones'] as $suscripcion): ?>
                    <div class="suscripcion-card">
                        <div class="suscripcion-info">
                            <h4><?php echo $suscripcion->get_formatted_order_total(); ?> / <?php echo wcs_get_subscription_period_strings($suscripcion->get_billing_period()); ?></h4>
                            <span class="badge badge-<?php echo $suscripcion->get_status(); ?>">
                                <?php echo wcs_get_subscription_status_name($suscripcion->get_status()); ?>
                            </span>
                        </div>
                        <div class="suscripcion-proxima">
                            <span>Próximo pago:</span>
                            <strong><?php echo $suscripcion->get_date_to_display('next_payment'); ?></strong>
                        </div>
                        <div class="suscripcion-acciones">
                            <a href="<?php echo $suscripcion->get_view_order_url(); ?>" class="flavor-btn flavor-btn-sm">Ver detalles</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_favoritos($datos) {
        ?>
        <div class="favoritos-lista">
            <h3>Mis productos favoritos</h3>

            <?php if (empty($datos['favoritos'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-heart"></span>
                    <p>No has guardado productos en favoritos</p>
                    <a href="<?php echo wc_get_page_permalink('shop'); ?>" class="flavor-btn flavor-btn-primary">Explorar tienda</a>
                </div>
            <?php else: ?>
                <div class="favoritos-grid">
                    <?php foreach ($datos['favoritos'] as $producto_id):
                        $producto = wc_get_product($producto_id);
                        if (!$producto) continue;
                    ?>
                        <div class="favorito-card">
                            <a href="<?php echo $producto->get_permalink(); ?>">
                                <?php echo $producto->get_image('woocommerce_thumbnail'); ?>
                            </a>
                            <div class="favorito-info">
                                <h4><?php echo esc_html($producto->get_name()); ?></h4>
                                <span class="precio"><?php echo $producto->get_price_html(); ?></span>
                            </div>
                            <div class="favorito-acciones">
                                <a href="<?php echo $producto->add_to_cart_url(); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <span class="dashicons dashicons-cart"></span>
                                </a>
                                <button class="flavor-btn flavor-btn-sm quitar-favorito" data-id="<?php echo $producto_id; ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .favoritos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
            .favorito-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
            .favorito-card img { width: 100%; aspect-ratio: 1; object-fit: cover; }
            .favorito-info { padding: 15px; }
            .favorito-info h4 { font-size: 14px; margin-bottom: 5px; }
            .favorito-info .precio { font-weight: 700; color: #333; }
            .favorito-acciones { display: flex; gap: 10px; padding: 0 15px 15px; }
        </style>
        <?php
    }

    private function obtener_datos_usuario() {
        $user_id = get_current_user_id();

        // Pedidos
        $pedidos = wc_get_orders([
            'customer_id' => $user_id,
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $total_pedidos = wc_get_customer_order_count($user_id);
        $total_gastado = wc_get_customer_total_spent($user_id);

        // Pedidos pendientes
        $pedidos_pendientes = count(wc_get_orders([
            'customer_id' => $user_id,
            'status' => ['processing', 'pending', 'on-hold'],
        ]));

        // Descargas
        $descargas = wc_get_customer_available_downloads($user_id);

        // Suscripciones (si WC Subscriptions está activo)
        $suscripciones = [];
        if (class_exists('WC_Subscriptions')) {
            $suscripciones = wcs_get_users_subscriptions($user_id);
        }

        // Favoritos (usando meta de usuario)
        $favoritos = get_user_meta($user_id, 'flavor_wc_favoritos', true) ?: [];

        // Puntos de fidelidad (si existe sistema)
        $puntos_fidelidad = get_user_meta($user_id, 'wc_points_balance', true) ?: 0;

        return [
            'pedidos' => $pedidos,
            'total_pedidos' => $total_pedidos,
            'total_gastado' => $total_gastado,
            'pedidos_pendientes' => $pedidos_pendientes,
            'descargas' => $descargas,
            'suscripciones' => $suscripciones,
            'favoritos' => $favoritos,
            'puntos_fidelidad' => $puntos_fidelidad,
        ];
    }
}

Flavor_Woocommerce_Dashboard_Tab::get_instance();
