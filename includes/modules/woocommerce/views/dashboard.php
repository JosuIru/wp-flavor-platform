<?php
/**
 * Dashboard mejorado de WooCommerce con Widgets de Datos en Vivo
 * Vista del Dashboard con integración a módulos relacionados
 *
 * @package FlavorChatIA
 * @subpackage WooCommerce
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_woocommerce')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

// Verificar que WooCommerce está activo
if (!class_exists('WooCommerce')) {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Dashboard de WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        <div class="notice notice-error">
            <p><?php esc_html_e('WooCommerce no está instalado o activado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>
    <?php
    return;
}

global $wpdb;

// Obtener estadísticas
$fecha_inicio_mes = date('Y-m-01');
$fecha_inicio_semana = date('Y-m-d', strtotime('monday this week'));
$fecha_hoy = date('Y-m-d');

// Pedidos de hoy
$pedidos_hoy = wc_get_orders([
    'date_created' => '>=' . strtotime('today midnight'),
    'return' => 'ids',
    'limit' => -1,
]);
$total_pedidos_hoy = count($pedidos_hoy);

// Ventas de hoy
$ventas_hoy = 0;
foreach ($pedidos_hoy as $order_id) {
    $order = wc_get_order($order_id);
    if ($order && in_array($order->get_status(), ['completed', 'processing', 'on-hold'])) {
        $ventas_hoy += floatval($order->get_total());
    }
}

// Pedidos de la semana
$pedidos_semana = wc_get_orders([
    'date_created' => '>=' . strtotime($fecha_inicio_semana),
    'return' => 'ids',
    'limit' => -1,
]);
$total_pedidos_semana = count($pedidos_semana);

// Ventas de la semana
$ventas_semana = 0;
foreach ($pedidos_semana as $order_id) {
    $order = wc_get_order($order_id);
    if ($order && in_array($order->get_status(), ['completed', 'processing', 'on-hold'])) {
        $ventas_semana += floatval($order->get_total());
    }
}

// Pedidos del mes
$pedidos_mes = wc_get_orders([
    'date_created' => '>=' . strtotime($fecha_inicio_mes),
    'return' => 'ids',
    'limit' => -1,
]);
$total_pedidos_mes = count($pedidos_mes);

// Ventas del mes
$ventas_mes = 0;
foreach ($pedidos_mes as $order_id) {
    $order = wc_get_order($order_id);
    if ($order && in_array($order->get_status(), ['completed', 'processing', 'on-hold'])) {
        $ventas_mes += floatval($order->get_total());
    }
}

// Pedidos por estado
$pedidos_pendientes = wc_get_orders([
    'status' => 'pending',
    'return' => 'ids',
    'limit' => -1,
]);
$total_pendientes = count($pedidos_pendientes);

$pedidos_procesando = wc_get_orders([
    'status' => 'processing',
    'return' => 'ids',
    'limit' => -1,
]);
$total_procesando = count($pedidos_procesando);

$pedidos_en_espera = wc_get_orders([
    'status' => 'on-hold',
    'return' => 'ids',
    'limit' => -1,
]);
$total_en_espera = count($pedidos_en_espera);

// Productos
$productos_totales = wp_count_posts('product');
$total_productos = $productos_totales->publish ?? 0;

// Productos sin stock
$productos_sin_stock_query = new WP_Query([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
        [
            'key' => '_stock_status',
            'value' => 'outofstock',
        ],
    ],
]);
$productos_sin_stock = $productos_sin_stock_query->found_posts;

// Productos con stock bajo
$umbral_stock_bajo = get_option('woocommerce_notify_low_stock_amount', 2);
$productos_stock_bajo_query = new WP_Query([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
        [
            'key' => '_stock',
            'value' => $umbral_stock_bajo,
            'compare' => '<=',
            'type' => 'NUMERIC',
        ],
        [
            'key' => '_stock_status',
            'value' => 'instock',
        ],
    ],
]);
$productos_stock_bajo = $productos_stock_bajo_query->found_posts;

// Pedidos recientes
$pedidos_recientes = wc_get_orders([
    'limit' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
]);

// Productos más vendidos
$productos_mas_vendidos = wc_get_products([
    'limit' => 5,
    'orderby' => 'meta_value_num',
    'meta_key' => 'total_sales',
    'order' => 'DESC',
]);

// Formatear precios
$currency_symbol = get_woocommerce_currency_symbol();

// ==================== WIDGETS DE DATOS EN VIVO ====================
$modulos_relacionados = [];
$active_modules = get_option('flavor_active_modules', []);

// 1. Marketplace - Productos del marketplace local
if (in_array('marketplace', $active_modules)) {
    $tabla_marketplace = $wpdb->prefix . 'flavor_marketplace_productos';
    $tabla_marketplace_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_marketplace'") === $tabla_marketplace;

    if ($tabla_marketplace_existe) {
        $productos_marketplace = $wpdb->get_results(
            "SELECT id, nombre, precio, stock, created_at
             FROM $tabla_marketplace
             WHERE estado = 'publicado'
             ORDER BY created_at DESC
             LIMIT 3"
        );

        if (!empty($productos_marketplace)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($productos_marketplace as $producto) {
                $precio_formateado = !empty($producto->precio) ? number_format((float)$producto->precio, 2) . '€' : 'N/D';
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">💰 %s · Stock: %d</span>
                    </div>',
                    esc_html(wp_trim_words($producto->nombre, 5)),
                    esc_html($precio_formateado),
                    (int)$producto->stock
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['marketplace'] = [
                'titulo' => sprintf(__('Productos Marketplace (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($productos_marketplace)),
                'descripcion' => __('Productos del marketplace local', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-store',
                'url' => admin_url('admin.php?page=flavor-marketplace'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 2. Socios - Socios activos que compran
if (in_array('socios', $active_modules)) {
    $tabla_socios = $wpdb->prefix . 'flavor_socios';
    $tabla_socios_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_socios'") === $tabla_socios;

    if ($tabla_socios_existe) {
        $socios_recientes = $wpdb->get_results(
            "SELECT id, nombre, apellidos, numero_socio, created_at
             FROM $tabla_socios
             WHERE estado = 'activo'
             ORDER BY created_at DESC
             LIMIT 3"
        );

        if (!empty($socios_recientes)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($socios_recientes as $socio) {
                $dias_desde_alta = floor((time() - strtotime($socio->created_at)) / DAY_IN_SECONDS);
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">👤 Alta hace %d días · Nº %s</span>
                    </div>',
                    esc_html($socio->nombre . ' ' . $socio->apellidos),
                    $dias_desde_alta,
                    esc_html($socio->numero_socio)
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['socios'] = [
                'titulo' => sprintf(__('Socios Recientes (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($socios_recientes)),
                'descripcion' => __('Socios activos de la plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-groups',
                'url' => admin_url('admin.php?page=socios-dashboard'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 3. Eventos - Eventos con entradas/tickets
if (in_array('eventos', $active_modules)) {
    $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
    $tabla_eventos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos;

    if ($tabla_eventos_existe) {
        $eventos_con_precio = $wpdb->get_results(
            "SELECT id, titulo, fecha, precio, precio_socio, plazas_max, estado
             FROM $tabla_eventos
             WHERE estado = 'publicado'
             AND fecha >= CURDATE()
             AND (precio > 0 OR precio_socio > 0)
             ORDER BY fecha ASC
             LIMIT 3"
        );

        if (!empty($eventos_con_precio)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($eventos_con_precio as $evento) {
                $fecha_formateada = date_i18n('d M', strtotime($evento->fecha));
                $precio_info = !empty($evento->precio) ? number_format((float)$evento->precio, 2) . '€' : 'Gratis';
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">🎟️ %s · %s · %d plazas</span>
                    </div>',
                    esc_html(wp_trim_words($evento->titulo, 5)),
                    esc_html($precio_info),
                    esc_html($fecha_formateada),
                    (int)$evento->plazas_max
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['eventos'] = [
                'titulo' => sprintf(__('Eventos de Pago (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($eventos_con_precio)),
                'descripcion' => __('Eventos con venta de entradas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-tickets-alt',
                'url' => admin_url('admin.php?page=flavor-eventos'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 4. Grupos Consumo - Pedidos de productos ecológicos
if (in_array('grupos_consumo', $active_modules)) {
    $tabla_pedidos_gc = $wpdb->prefix . 'flavor_gc_pedidos';
    $tabla_pedidos_gc_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_pedidos_gc'") === $tabla_pedidos_gc;

    if ($tabla_pedidos_gc_existe) {
        $pedidos_gc_activos = $wpdb->get_results(
            "SELECT id, ciclo_id, total, estado, fecha_cierre
             FROM $tabla_pedidos_gc
             WHERE estado IN ('abierto', 'cerrado')
             ORDER BY fecha_cierre ASC
             LIMIT 3"
        );

        if (!empty($pedidos_gc_activos)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($pedidos_gc_activos as $pedido_gc) {
                $total_formateado = !empty($pedido_gc->total) ? number_format((float)$pedido_gc->total, 2) . '€' : '0€';
                $estado_emoji = $pedido_gc->estado === 'abierto' ? '🟢' : '🔵';
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>Ciclo #%d</strong>
                        <span class="dm-widget-meta">%s %s · Total: %s</span>
                    </div>',
                    (int)$pedido_gc->ciclo_id,
                    $estado_emoji,
                    esc_html(ucfirst($pedido_gc->estado)),
                    esc_html($total_formateado)
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['grupos_consumo'] = [
                'titulo' => sprintf(__('Pedidos G. Consumo (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($pedidos_gc_activos)),
                'descripcion' => __('Pedidos activos de grupos de consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-carrot',
                'url' => admin_url('admin.php?page=flavor-grupos-consumo'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 5. Biblioteca - Préstamos o material en venta
if (in_array('biblioteca', $active_modules)) {
    $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
    $tabla_prestamos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_prestamos'") === $tabla_prestamos;

    if ($tabla_prestamos_existe) {
        $prestamos_activos = $wpdb->get_results(
            "SELECT p.*, i.titulo
             FROM $tabla_prestamos p
             LEFT JOIN {$wpdb->prefix}flavor_biblioteca_items i ON p.item_id = i.id
             WHERE p.estado = 'activo'
             ORDER BY p.fecha_devolucion ASC
             LIMIT 3"
        );

        if (!empty($prestamos_activos)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($prestamos_activos as $prestamo) {
                $dias_restantes = floor((strtotime($prestamo->fecha_devolucion) - time()) / DAY_IN_SECONDS);
                $color_emoji = $dias_restantes < 3 ? '🔴' : ($dias_restantes < 7 ? '🟡' : '🟢');
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">%s Devolución en %d días</span>
                    </div>',
                    esc_html(wp_trim_words($prestamo->titulo, 5)),
                    $color_emoji,
                    $dias_restantes
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['biblioteca'] = [
                'titulo' => sprintf(__('Préstamos Activos (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($prestamos_activos)),
                'descripcion' => __('Material prestado de la biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-book',
                'url' => admin_url('admin.php?page=flavor-biblioteca'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 6. Talleres - Talleres con inscripción de pago
if (in_array('talleres', $active_modules)) {
    $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
    $tabla_talleres_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_talleres'") === $tabla_talleres;

    if ($tabla_talleres_existe) {
        $talleres_pago = $wpdb->get_results(
            "SELECT id, titulo, precio, plazas_max, inscritos, fecha_inicio, fecha_fin, estado
             FROM $tabla_talleres
             WHERE estado = 'activo'
             AND (precio > 0 OR precio_socio > 0)
             AND (fecha_fin >= CURDATE() OR fecha_fin IS NULL)
             ORDER BY fecha_inicio ASC
             LIMIT 3"
        );

        if (!empty($talleres_pago)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($talleres_pago as $taller) {
                $precio_formateado = !empty($taller->precio) ? number_format((float)$taller->precio, 2) . '€' : 'Gratis';
                $plazas_disponibles = (int)$taller->plazas_max - (int)$taller->inscritos;
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">🎓 %s · %d plazas libres</span>
                    </div>',
                    esc_html(wp_trim_words($taller->titulo, 5)),
                    esc_html($precio_formateado),
                    $plazas_disponibles
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['talleres'] = [
                'titulo' => sprintf(__('Talleres de Pago (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($talleres_pago)),
                'descripcion' => __('Talleres con inscripción de pago', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-welcome-learn-more',
                'url' => admin_url('admin.php?page=flavor-talleres'),
                'datos' => $datos_html,
            ];
        }
    }
}

?>
<div class="wrap flavor-admin-page flavor-woocommerce-dashboard">
    <h1>
        <span class="dashicons dashicons-cart"></span>
        <?php esc_html_e('Dashboard de WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <!-- ==================== WIDGETS DE MÓDULOS RELACIONADOS ==================== -->
    <?php if (!empty($modulos_relacionados)): ?>
    <div class="dm-widgets-relacionados">
        <h2 class="dm-widgets-titulo">
            <span class="dashicons dashicons-networking"></span>
            <?php _e('Ecosistema de Ventas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>
        <div class="dm-widgets-grid">
            <?php foreach ($modulos_relacionados as $modulo_key => $modulo): ?>
                <div class="dm-widget-card">
                    <div class="dm-widget-header">
                        <span class="dashicons <?php echo esc_attr($modulo['icono']); ?>"></span>
                        <div class="dm-widget-header-text">
                            <h3><?php echo esc_html($modulo['titulo']); ?></h3>
                            <p><?php echo esc_html($modulo['descripcion']); ?></p>
                        </div>
                    </div>
                    <div class="dm-widget-content">
                        <?php echo $modulo['datos']; ?>
                    </div>
                    <div class="dm-widget-footer">
                        <a href="<?php echo esc_url($modulo['url']); ?>" class="dm-widget-link">
                            <?php _e('Ver todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-grid">
        <div class="stat-card stat-ventas-hoy">
            <div class="stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo wc_price($ventas_hoy); ?></span>
                <span class="stat-label"><?php esc_html_e('Ventas hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="stat-secondary"><?php printf(esc_html__('%d pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_pedidos_hoy); ?></span>
            </div>
        </div>

        <div class="stat-card stat-ventas-semana">
            <div class="stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo wc_price($ventas_semana); ?></span>
                <span class="stat-label"><?php esc_html_e('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="stat-secondary"><?php printf(esc_html__('%d pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_pedidos_semana); ?></span>
            </div>
        </div>

        <div class="stat-card stat-ventas-mes">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo wc_price($ventas_mes); ?></span>
                <span class="stat-label"><?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="stat-secondary"><?php printf(esc_html__('%d pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_pedidos_mes); ?></span>
            </div>
        </div>

        <div class="stat-card stat-productos">
            <div class="stat-icon">
                <span class="dashicons dashicons-archive"></span>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($total_productos); ?></span>
                <span class="stat-label"><?php esc_html_e('Productos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="stat-secondary">
                    <?php if ($productos_sin_stock > 0): ?>
                        <span class="text-danger"><?php printf(esc_html__('%d sin stock', FLAVOR_PLATFORM_TEXT_DOMAIN), $productos_sin_stock); ?></span>
                    <?php else: ?>
                        <?php esc_html_e('Todos en stock', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Estado de pedidos -->
    <div class="flavor-section">
        <h2><?php esc_html_e('Estado de Pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <div class="flavor-order-status-grid">
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order&post_status=wc-pending')); ?>" class="order-status-card status-pending">
                <span class="status-count"><?php echo esc_html($total_pendientes); ?></span>
                <span class="status-label"><?php esc_html_e('Pendientes de pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order&post_status=wc-processing')); ?>" class="order-status-card status-processing">
                <span class="status-count"><?php echo esc_html($total_procesando); ?></span>
                <span class="status-label"><?php esc_html_e('Procesando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order&post_status=wc-on-hold')); ?>" class="order-status-card status-on-hold">
                <span class="status-count"><?php echo esc_html($total_en_espera); ?></span>
                <span class="status-label"><?php esc_html_e('En espera', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <div class="flavor-columns">
        <!-- Pedidos recientes -->
        <div class="flavor-column">
            <div class="flavor-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Pedidos Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order')); ?>" class="button button-small">
                        <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <div class="card-content">
                    <?php if (!empty($pedidos_recientes)): ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Pedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    <th><?php esc_html_e('Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    <th><?php esc_html_e('Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos_recientes as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url($order->get_edit_order_url()); ?>">
                                                #<?php echo esc_html($order->get_order_number()); ?>
                                            </a>
                                            <br>
                                            <small><?php echo esc_html($order->get_date_created()->date_i18n('d/m/Y H:i')); ?></small>
                                        </td>
                                        <td>
                                            <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?>
                                        </td>
                                        <td>
                                            <span class="order-status status-<?php echo esc_attr($order->get_status()); ?>">
                                                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data"><?php esc_html_e('No hay pedidos recientes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Productos más vendidos -->
        <div class="flavor-column">
            <div class="flavor-card">
                <div class="card-header">
                    <h3><?php esc_html_e('Productos Más Vendidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>" class="button button-small">
                        <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <div class="card-content">
                    <?php if (!empty($productos_mas_vendidos)): ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Producto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    <th><?php esc_html_e('Precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    <th><?php esc_html_e('Ventas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_mas_vendidos as $producto): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url(get_edit_post_link($producto->get_id())); ?>">
                                                <?php echo esc_html($producto->get_name()); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo wp_kses_post($producto->get_price_html()); ?>
                                        </td>
                                        <td>
                                            <?php echo esc_html($producto->get_total_sales()); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data"><?php esc_html_e('No hay datos de ventas disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Alertas de stock -->
            <?php if ($productos_sin_stock > 0 || $productos_stock_bajo > 0): ?>
                <div class="flavor-card flavor-card-warning">
                    <div class="card-header">
                        <h3><?php esc_html_e('Alertas de Stock', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    </div>
                    <div class="card-content">
                        <ul class="stock-alerts">
                            <?php if ($productos_sin_stock > 0): ?>
                                <li class="alert-danger">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php printf(
                                        esc_html__('%d productos sin stock', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        $productos_sin_stock
                                    ); ?>
                                </li>
                            <?php endif; ?>
                            <?php if ($productos_stock_bajo > 0): ?>
                                <li class="alert-warning">
                                    <span class="dashicons dashicons-info"></span>
                                    <?php printf(
                                        esc_html__('%d productos con stock bajo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        $productos_stock_bajo
                                    ); ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* ==================== ESTILOS WIDGETS DE DATOS EN VIVO ==================== */
.dm-widgets-relacionados {
    margin: 0 0 32px;
    background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
    border-radius: 16px;
    padding: 24px;
    border: 2px solid #60a5fa;
}

.dm-widgets-titulo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0 0 20px;
    font-size: 20px;
    color: #1e40af;
    font-weight: 600;
}

.dm-widgets-titulo .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.dm-widgets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.dm-widget-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(30, 64, 175, 0.08);
    transition: all 0.3s ease;
    border: 1px solid #dbeafe;
}

.dm-widget-card:hover {
    box-shadow: 0 6px 20px rgba(30, 64, 175, 0.15);
    transform: translateY(-2px);
    border-color: #60a5fa;
}

.dm-widget-header {
    display: flex;
    gap: 12px;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f9ff;
}

.dm-widget-header .dashicons {
    color: #2563eb;
    font-size: 24px;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.dm-widget-header-text h3 {
    margin: 0 0 4px;
    font-size: 15px;
    color: #1e40af;
    font-weight: 600;
}

.dm-widget-header-text p {
    margin: 0;
    font-size: 12px;
    color: #9ca3af;
}

.dm-widget-content {
    margin-bottom: 12px;
}

.dm-widget-data-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.dm-widget-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: #f0f9ff;
    border-radius: 6px;
    border: 1px solid #e0f2fe;
    transition: all 0.2s;
}

.dm-widget-item:hover {
    border-color: #60a5fa;
    box-shadow: 0 1px 3px rgba(37, 99, 235, 0.1);
}

.dm-widget-item strong {
    color: #374151;
    font-size: 13px;
    font-weight: 500;
}

.dm-widget-meta {
    font-size: 11px;
    color: #9ca3af;
    white-space: nowrap;
}

.dm-widget-footer {
    padding-top: 10px;
    border-top: 1px solid #f0f9ff;
}

.dm-widget-link {
    display: inline-flex;
    align-items: center;
    font-size: 13px;
    color: #2563eb;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.dm-widget-link:hover {
    color: #1d4ed8;
    gap: 6px;
}

/* ==================== ESTILOS ORIGINALES WOOCOMMERCE ==================== */
.flavor-woocommerce-dashboard h1 {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-card .stat-icon {
    background: #f0f6fc;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-card .stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #2271b1;
}

.stat-card .stat-content {
    display: flex;
    flex-direction: column;
}

.stat-card .stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.stat-card .stat-label {
    color: #646970;
    font-size: 14px;
}

.stat-card .stat-secondary {
    color: #787c82;
    font-size: 12px;
    margin-top: 4px;
}

.stat-card .text-danger {
    color: #d63638;
}

.flavor-section {
    margin: 30px 0;
}

.flavor-section h2 {
    margin-bottom: 15px;
}

.flavor-order-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
}

.order-status-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s ease;
}

.order-status-card:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.order-status-card .status-count {
    display: block;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 5px;
}

.order-status-card .status-label {
    color: #646970;
    font-size: 14px;
}

.order-status-card.status-pending .status-count { color: #996800; }
.order-status-card.status-processing .status-count { color: #0073aa; }
.order-status-card.status-on-hold .status-count { color: #94660c; }

.flavor-columns {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.flavor-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 20px;
}

.flavor-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e0e0e0;
}

.flavor-card .card-header h3 {
    margin: 0;
    font-size: 16px;
}

.flavor-card .card-content {
    padding: 15px 20px;
}

.flavor-card-warning {
    border-color: #dba617;
}

.stock-alerts {
    margin: 0;
    padding: 0;
    list-style: none;
}

.stock-alerts li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
}

.stock-alerts .alert-danger { color: #d63638; }
.stock-alerts .alert-warning { color: #996800; }

.order-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.order-status.status-pending { background: #f0f0f0; color: #996800; }
.order-status.status-processing { background: #e5f4ff; color: #0073aa; }
.order-status.status-on-hold { background: #fff3e3; color: #94660c; }
.order-status.status-completed { background: #e6f4ea; color: #2e7d32; }
.order-status.status-cancelled { background: #fce8e8; color: #d63638; }
.order-status.status-refunded { background: #f0f0f0; color: #646970; }
.order-status.status-failed { background: #fce8e8; color: #d63638; }

.no-data {
    color: #646970;
    text-align: center;
    padding: 20px;
}

@media (max-width: 782px) {
    .flavor-columns {
        grid-template-columns: 1fr;
    }

    .dm-widgets-grid {
        grid-template-columns: 1fr;
    }
}
</style>
