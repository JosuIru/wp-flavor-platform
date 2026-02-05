<?php
/**
 * Tabs de Dashboard para Grupos de Consumo
 *
 * Integra el módulo con el dashboard de usuario de Flavor.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario
 */
class Flavor_GC_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_GC_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Tabla de lista de compra
     * @var string
     */
    private $tabla_lista_compra;

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_lista_compra = $wpdb->prefix . 'flavor_gc_lista_compra';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_GC_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Registrar tabs en el dashboard de usuario
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);

        // AJAX handlers para lista de compra
        add_action('wp_ajax_gc_agregar_lista_compra', [$this, 'ajax_agregar_lista_compra']);
        add_action('wp_ajax_gc_quitar_lista_compra', [$this, 'ajax_quitar_lista_compra']);
        add_action('wp_ajax_gc_actualizar_cantidad_lista', [$this, 'ajax_actualizar_cantidad']);
        add_action('wp_ajax_gc_vaciar_lista_compra', [$this, 'ajax_vaciar_lista']);
        add_action('wp_ajax_gc_convertir_lista_pedido', [$this, 'ajax_convertir_a_pedido']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Registra los tabs del módulo en el dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        // Tab: Lista de la Compra
        $tabs['gc-lista-compra'] = [
            'label' => __('Lista de la Compra', 'flavor-chat-ia'),
            'icon' => 'cart',
            'callback' => [$this, 'render_tab_lista_compra'],
            'orden' => 25,
            'badge' => $this->obtener_count_lista_compra(),
        ];

        // Tab: Mis Pedidos
        $tabs['gc-mis-pedidos'] = [
            'label' => __('Mis Pedidos', 'flavor-chat-ia'),
            'icon' => 'box',
            'callback' => [$this, 'render_tab_mis_pedidos'],
            'orden' => 26,
        ];

        // Tab: Mi Suscripción/Cesta
        $tabs['gc-mi-suscripcion'] = [
            'label' => __('Mi Cesta', 'flavor-chat-ia'),
            'icon' => 'heart',
            'callback' => [$this, 'render_tab_mi_suscripcion'],
            'orden' => 27,
        ];

        return $tabs;
    }

    /**
     * Obtiene el contador de items en la lista de compra
     *
     * @return int
     */
    private function obtener_count_lista_compra() {
        if (!is_user_logged_in()) {
            return 0;
        }

        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_lista_compra} WHERE usuario_id = %d",
            get_current_user_id()
        ));
    }

    /**
     * Enqueue de assets
     */
    public function enqueue_assets() {
        if (!is_page() || !is_user_logged_in()) {
            return;
        }

        wp_enqueue_style(
            'gc-dashboard',
            plugins_url('assets/gc-dashboard.css', __FILE__),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'gc-dashboard',
            plugins_url('assets/gc-dashboard.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('gc-dashboard', 'gcDashboardData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gc_lista_compra_nonce'),
            'i18n' => [
                'confirmar_vaciar' => __('¿Estás seguro de vaciar la lista?', 'flavor-chat-ia'),
                'confirmar_convertir' => __('¿Convertir la lista de compra en un pedido?', 'flavor-chat-ia'),
                'error_generico' => __('Ha ocurrido un error.', 'flavor-chat-ia'),
            ],
        ]);
    }

    // ========================================
    // Tab: Lista de la Compra
    // ========================================

    /**
     * Renderiza el tab de lista de compra
     */
    public function render_tab_lista_compra() {
        $usuario_id = get_current_user_id();
        $items = $this->obtener_lista_compra($usuario_id);
        $total = $this->calcular_total_lista($items);

        ?>
        <div class="gc-dashboard-tab gc-lista-compra">
            <div class="gc-tab-header">
                <h2><?php _e('Mi Lista de la Compra', 'flavor-chat-ia'); ?></h2>
                <?php if (!empty($items)): ?>
                    <div class="gc-acciones-header">
                        <button type="button" class="gc-btn gc-btn-outline gc-vaciar-lista">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Vaciar', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="gc-btn gc-btn-primary gc-convertir-pedido">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Hacer Pedido', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($items)): ?>
                <div class="gc-empty-state">
                    <span class="gc-empty-icon dashicons dashicons-cart"></span>
                    <p><?php _e('Tu lista de la compra está vacía.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('gc_producto')); ?>" class="gc-btn gc-btn-primary">
                        <?php _e('Ver Productos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="gc-lista-items">
                    <?php foreach ($items as $item): ?>
                        <div class="gc-lista-item" data-item-id="<?php echo esc_attr($item->id); ?>">
                            <div class="gc-item-imagen">
                                <?php if ($item->imagen_url): ?>
                                    <img src="<?php echo esc_url($item->imagen_url); ?>" alt="<?php echo esc_attr($item->producto_nombre); ?>">
                                <?php else: ?>
                                    <span class="gc-placeholder-imagen dashicons dashicons-carrot"></span>
                                <?php endif; ?>
                            </div>
                            <div class="gc-item-info">
                                <h4><?php echo esc_html($item->producto_nombre); ?></h4>
                                <p class="gc-item-productor"><?php echo esc_html($item->productor_nombre); ?></p>
                                <p class="gc-item-precio">
                                    <?php echo number_format($item->precio, 2); ?> € / <?php echo esc_html($item->unidad); ?>
                                </p>
                            </div>
                            <div class="gc-item-cantidad">
                                <button type="button" class="gc-cantidad-btn gc-cantidad-menos" aria-label="<?php _e('Reducir cantidad', 'flavor-chat-ia'); ?>">-</button>
                                <input type="number"
                                       class="gc-cantidad-input"
                                       value="<?php echo esc_attr($item->cantidad); ?>"
                                       min="0.5"
                                       step="0.5"
                                       data-producto-id="<?php echo esc_attr($item->producto_id); ?>">
                                <button type="button" class="gc-cantidad-btn gc-cantidad-mas" aria-label="<?php _e('Aumentar cantidad', 'flavor-chat-ia'); ?>">+</button>
                            </div>
                            <div class="gc-item-subtotal">
                                <?php echo number_format($item->precio * $item->cantidad, 2); ?> €
                            </div>
                            <button type="button" class="gc-item-quitar" aria-label="<?php _e('Quitar', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="gc-lista-footer">
                    <div class="gc-total">
                        <span class="gc-total-label"><?php _e('Total estimado:', 'flavor-chat-ia'); ?></span>
                        <span class="gc-total-valor"><?php echo number_format($total, 2); ?> €</span>
                    </div>
                    <?php if ($item->notas): ?>
                        <p class="gc-notas-lista"><?php echo esc_html($item->notas); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Obtiene la lista de compra de un usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    public function obtener_lista_compra($usuario_id) {
        global $wpdb;

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT lc.*,
                    p.post_title as producto_nombre,
                    pm_precio.meta_value as precio,
                    pm_unidad.meta_value as unidad,
                    pm_productor.meta_value as productor_id
            FROM {$this->tabla_lista_compra} lc
            LEFT JOIN {$wpdb->posts} p ON lc.producto_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm_precio ON p.ID = pm_precio.post_id AND pm_precio.meta_key = '_gc_precio'
            LEFT JOIN {$wpdb->postmeta} pm_unidad ON p.ID = pm_unidad.post_id AND pm_unidad.meta_key = '_gc_unidad'
            LEFT JOIN {$wpdb->postmeta} pm_productor ON p.ID = pm_productor.post_id AND pm_productor.meta_key = '_gc_productor_id'
            WHERE lc.usuario_id = %d
            ORDER BY lc.fecha_agregado DESC",
            $usuario_id
        ));

        // Añadir información adicional
        foreach ($items as &$item) {
            $item->imagen_url = get_the_post_thumbnail_url($item->producto_id, 'thumbnail');
            $item->precio = floatval($item->precio);

            // Nombre del productor
            if ($item->productor_id) {
                $productor = get_post($item->productor_id);
                $item->productor_nombre = $productor ? $productor->post_title : '';
            } else {
                $item->productor_nombre = '';
            }
        }

        return $items;
    }

    /**
     * Calcula el total de la lista
     *
     * @param array $items Items de la lista
     * @return float
     */
    private function calcular_total_lista($items) {
        $total = 0;
        foreach ($items as $item) {
            $total += floatval($item->precio) * floatval($item->cantidad);
        }
        return $total;
    }

    /**
     * Agrega un producto a la lista de compra
     *
     * @param int   $usuario_id  ID del usuario
     * @param int   $producto_id ID del producto
     * @param float $cantidad    Cantidad
     * @param string $notas      Notas opcionales
     * @return array
     */
    public function agregar_a_lista($usuario_id, $producto_id, $cantidad = 1, $notas = '') {
        global $wpdb;

        // Verificar producto
        $producto = get_post($producto_id);
        if (!$producto || $producto->post_type !== 'gc_producto') {
            return [
                'success' => false,
                'error' => __('Producto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Verificar si ya está en la lista
        $existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id, cantidad FROM {$this->tabla_lista_compra}
            WHERE usuario_id = %d AND producto_id = %d",
            $usuario_id,
            $producto_id
        ));

        if ($existente) {
            // Actualizar cantidad
            $nueva_cantidad = floatval($existente->cantidad) + floatval($cantidad);
            $wpdb->update(
                $this->tabla_lista_compra,
                ['cantidad' => $nueva_cantidad],
                ['id' => $existente->id],
                ['%f'],
                ['%d']
            );

            return [
                'success' => true,
                'mensaje' => __('Cantidad actualizada.', 'flavor-chat-ia'),
                'cantidad' => $nueva_cantidad,
            ];
        }

        // Insertar nuevo
        $resultado = $wpdb->insert(
            $this->tabla_lista_compra,
            [
                'usuario_id' => $usuario_id,
                'producto_id' => $producto_id,
                'cantidad' => floatval($cantidad),
                'fecha_agregado' => current_time('mysql'),
                'notas' => sanitize_text_field($notas),
            ],
            ['%d', '%d', '%f', '%s', '%s']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al agregar producto.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'mensaje' => __('Producto agregado a la lista.', 'flavor-chat-ia'),
            'item_id' => $wpdb->insert_id,
        ];
    }

    /**
     * Quita un producto de la lista
     *
     * @param int $usuario_id ID del usuario
     * @param int $item_id    ID del item en la lista
     * @return array
     */
    public function quitar_de_lista($usuario_id, $item_id) {
        global $wpdb;

        $resultado = $wpdb->delete(
            $this->tabla_lista_compra,
            [
                'id' => $item_id,
                'usuario_id' => $usuario_id,
            ],
            ['%d', '%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al quitar producto.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'mensaje' => __('Producto quitado de la lista.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Actualiza cantidad de un item
     *
     * @param int   $usuario_id ID del usuario
     * @param int   $item_id    ID del item
     * @param float $cantidad   Nueva cantidad
     * @return array
     */
    public function actualizar_cantidad($usuario_id, $item_id, $cantidad) {
        global $wpdb;

        $cantidad = floatval($cantidad);

        if ($cantidad <= 0) {
            return $this->quitar_de_lista($usuario_id, $item_id);
        }

        $resultado = $wpdb->update(
            $this->tabla_lista_compra,
            ['cantidad' => $cantidad],
            [
                'id' => $item_id,
                'usuario_id' => $usuario_id,
            ],
            ['%f'],
            ['%d', '%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al actualizar cantidad.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'mensaje' => __('Cantidad actualizada.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Vacía la lista de compra
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    public function vaciar_lista($usuario_id) {
        global $wpdb;

        $wpdb->delete(
            $this->tabla_lista_compra,
            ['usuario_id' => $usuario_id],
            ['%d']
        );

        return [
            'success' => true,
            'mensaje' => __('Lista vaciada.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Convierte la lista de compra en un pedido
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    public function convertir_lista_a_pedido($usuario_id) {
        global $wpdb;

        // Obtener items de la lista
        $items = $this->obtener_lista_compra($usuario_id);

        if (empty($items)) {
            return [
                'success' => false,
                'error' => __('La lista está vacía.', 'flavor-chat-ia'),
            ];
        }

        // Verificar ciclo abierto
        $ciclo_abierto = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => 1,
        ]);

        if (empty($ciclo_abierto)) {
            return [
                'success' => false,
                'error' => __('No hay ningún ciclo de pedido abierto.', 'flavor-chat-ia'),
            ];
        }

        $ciclo_id = $ciclo_abierto[0]->ID;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        // Verificar si ya tiene pedido en este ciclo
        $pedido_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_pedidos}
            WHERE ciclo_id = %d AND usuario_id = %d",
            $ciclo_id,
            $usuario_id
        ));

        if ($pedido_existente > 0) {
            return [
                'success' => false,
                'error' => __('Ya tienes un pedido en este ciclo. Modifícalo desde Mis Pedidos.', 'flavor-chat-ia'),
            ];
        }

        // Crear pedido
        $total = 0;
        foreach ($items as $item) {
            $precio = floatval($item->precio);

            $wpdb->insert(
                $tabla_pedidos,
                [
                    'ciclo_id' => $ciclo_id,
                    'usuario_id' => $usuario_id,
                    'producto_id' => $item->producto_id,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $precio,
                    'estado' => 'pendiente',
                    'fecha_pedido' => current_time('mysql'),
                    'notas' => $item->notas,
                ],
                ['%d', '%d', '%d', '%f', '%f', '%s', '%s', '%s']
            );

            $total += $precio * $item->cantidad;
        }

        // Vaciar lista
        $this->vaciar_lista($usuario_id);

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Pedido creado correctamente. Total: %.2f €', 'flavor-chat-ia'),
                $total
            ),
            'total' => $total,
        ];
    }

    // ========================================
    // Tab: Mis Pedidos
    // ========================================

    /**
     * Renderiza el tab de mis pedidos
     */
    public function render_tab_mis_pedidos() {
        $usuario_id = get_current_user_id();
        $pedidos = $this->obtener_pedidos_usuario($usuario_id);

        ?>
        <div class="gc-dashboard-tab gc-mis-pedidos">
            <div class="gc-tab-header">
                <h2><?php _e('Mis Pedidos', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (empty($pedidos)): ?>
                <div class="gc-empty-state">
                    <span class="gc-empty-icon dashicons dashicons-clipboard"></span>
                    <p><?php _e('No tienes pedidos todavía.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="gc-pedidos-lista">
                    <?php foreach ($pedidos as $ciclo_id => $pedido_ciclo): ?>
                        <div class="gc-pedido-card <?php echo esc_attr('gc-estado-' . $pedido_ciclo['estado_ciclo']); ?>">
                            <div class="gc-pedido-header">
                                <h3><?php echo esc_html($pedido_ciclo['ciclo_nombre']); ?></h3>
                                <span class="gc-estado-badge"><?php echo esc_html($pedido_ciclo['estado_label']); ?></span>
                            </div>
                            <div class="gc-pedido-fechas">
                                <p>
                                    <strong><?php _e('Entrega:', 'flavor-chat-ia'); ?></strong>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($pedido_ciclo['fecha_entrega']))); ?>
                                </p>
                                <?php if ($pedido_ciclo['lugar_entrega']): ?>
                                    <p>
                                        <strong><?php _e('Lugar:', 'flavor-chat-ia'); ?></strong>
                                        <?php echo esc_html($pedido_ciclo['lugar_entrega']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="gc-pedido-items">
                                <table class="gc-items-table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Producto', 'flavor-chat-ia'); ?></th>
                                            <th><?php _e('Cantidad', 'flavor-chat-ia'); ?></th>
                                            <th><?php _e('Precio', 'flavor-chat-ia'); ?></th>
                                            <th><?php _e('Subtotal', 'flavor-chat-ia'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pedido_ciclo['items'] as $item): ?>
                                            <tr>
                                                <td><?php echo esc_html($item->producto_nombre); ?></td>
                                                <td><?php echo esc_html($item->cantidad . ' ' . $item->unidad); ?></td>
                                                <td><?php echo number_format($item->precio_unitario, 2); ?> €</td>
                                                <td><?php echo number_format($item->precio_unitario * $item->cantidad, 2); ?> €</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="gc-total-label"><?php _e('Total:', 'flavor-chat-ia'); ?></td>
                                            <td class="gc-total-valor"><?php echo number_format($pedido_ciclo['total'], 2); ?> €</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php if ($pedido_ciclo['puede_modificar']): ?>
                                <div class="gc-pedido-acciones">
                                    <button type="button" class="gc-btn gc-btn-outline gc-modificar-pedido"
                                            data-ciclo-id="<?php echo esc_attr($ciclo_id); ?>">
                                        <?php _e('Modificar Pedido', 'flavor-chat-ia'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Obtiene los pedidos del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_pedidos_usuario($usuario_id) {
        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT ped.*,
                    p.post_title as producto_nombre,
                    pm_unidad.meta_value as unidad,
                    c.post_title as ciclo_nombre,
                    c.post_status as estado_ciclo
            FROM {$tabla_pedidos} ped
            LEFT JOIN {$wpdb->posts} p ON ped.producto_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm_unidad ON p.ID = pm_unidad.post_id AND pm_unidad.meta_key = '_gc_unidad'
            LEFT JOIN {$wpdb->posts} c ON ped.ciclo_id = c.ID
            WHERE ped.usuario_id = %d
            ORDER BY ped.fecha_pedido DESC",
            $usuario_id
        ));

        // Agrupar por ciclo
        $pedidos_agrupados = [];
        foreach ($items as $item) {
            $ciclo_id = $item->ciclo_id;

            if (!isset($pedidos_agrupados[$ciclo_id])) {
                $fecha_entrega = get_post_meta($ciclo_id, '_gc_fecha_entrega', true);
                $lugar_entrega = get_post_meta($ciclo_id, '_gc_lugar_entrega', true);
                $fecha_cierre = get_post_meta($ciclo_id, '_gc_fecha_cierre', true);

                $puede_modificar = $item->estado_ciclo === 'gc_abierto'
                    && strtotime($fecha_cierre) > current_time('timestamp') + (24 * 3600);

                $pedidos_agrupados[$ciclo_id] = [
                    'ciclo_nombre' => $item->ciclo_nombre,
                    'estado_ciclo' => $item->estado_ciclo,
                    'estado_label' => $this->obtener_etiqueta_estado_ciclo($item->estado_ciclo),
                    'fecha_entrega' => $fecha_entrega,
                    'lugar_entrega' => $lugar_entrega,
                    'puede_modificar' => $puede_modificar,
                    'items' => [],
                    'total' => 0,
                ];
            }

            $pedidos_agrupados[$ciclo_id]['items'][] = $item;
            $pedidos_agrupados[$ciclo_id]['total'] += $item->precio_unitario * $item->cantidad;
        }

        return $pedidos_agrupados;
    }

    /**
     * Obtiene etiqueta de estado del ciclo
     *
     * @param string $estado Estado del ciclo
     * @return string
     */
    private function obtener_etiqueta_estado_ciclo($estado) {
        $etiquetas = [
            'gc_abierto' => __('Abierto', 'flavor-chat-ia'),
            'gc_cerrado' => __('Cerrado', 'flavor-chat-ia'),
            'gc_entregado' => __('Entregado', 'flavor-chat-ia'),
        ];
        return $etiquetas[$estado] ?? $estado;
    }

    // ========================================
    // Tab: Mi Suscripción
    // ========================================

    /**
     * Renderiza el tab de suscripción
     */
    public function render_tab_mi_suscripcion() {
        $usuario_id = get_current_user_id();

        // Obtener consumidor
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        // Por ahora buscar grupo por defecto (primer grupo)
        $grupos = get_posts([
            'post_type' => 'gc_grupo',
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ]);

        $consumidor = null;
        if (!empty($grupos)) {
            $consumidor = $consumidor_manager->obtener_consumidor($usuario_id, $grupos[0]->ID);
        }

        // Obtener suscripciones
        $suscripciones = [];
        if ($consumidor) {
            $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
            $suscripciones = $suscripciones_manager->listar_suscripciones_consumidor($consumidor->id);
        }

        // Obtener cestas disponibles
        $suscripciones_manager = Flavor_GC_Subscriptions::get_instance();
        $cestas_disponibles = $suscripciones_manager->listar_tipos_cestas();

        ?>
        <div class="gc-dashboard-tab gc-mi-suscripcion">
            <div class="gc-tab-header">
                <h2><?php _e('Mi Cesta', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (!$consumidor): ?>
                <div class="gc-aviso gc-aviso-info">
                    <p><?php _e('Para suscribirte a una cesta, primero debes ser miembro de un grupo de consumo.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('gc_grupo')); ?>" class="gc-btn gc-btn-primary">
                        <?php _e('Ver Grupos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <?php if (!empty($suscripciones)): ?>
                    <div class="gc-suscripciones-actuales">
                        <h3><?php _e('Mis Suscripciones Activas', 'flavor-chat-ia'); ?></h3>
                        <?php foreach ($suscripciones as $suscripcion): ?>
                            <div class="gc-suscripcion-card gc-estado-<?php echo esc_attr($suscripcion->estado); ?>">
                                <div class="gc-suscripcion-imagen">
                                    <?php if ($suscripcion->imagen_id): ?>
                                        <?php echo wp_get_attachment_image($suscripcion->imagen_id, 'thumbnail'); ?>
                                    <?php else: ?>
                                        <span class="gc-placeholder dashicons dashicons-carrot"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="gc-suscripcion-info">
                                    <h4><?php echo esc_html($suscripcion->cesta_nombre); ?></h4>
                                    <p class="gc-frecuencia">
                                        <?php echo esc_html($suscripciones_manager->obtener_etiqueta_frecuencia($suscripcion->frecuencia)); ?>
                                        - <?php echo number_format($suscripcion->importe, 2); ?> €
                                    </p>
                                    <p class="gc-proxima-entrega">
                                        <strong><?php _e('Próxima cesta:', 'flavor-chat-ia'); ?></strong>
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($suscripcion->fecha_proximo_cargo))); ?>
                                    </p>
                                    <span class="gc-estado-badge gc-estado-<?php echo esc_attr($suscripcion->estado); ?>">
                                        <?php echo esc_html($suscripciones_manager->obtener_etiqueta_estado($suscripcion->estado)); ?>
                                    </span>
                                </div>
                                <div class="gc-suscripcion-acciones">
                                    <?php if ($suscripcion->estado === 'activa'): ?>
                                        <button type="button" class="gc-btn gc-btn-outline gc-pausar-suscripcion"
                                                data-suscripcion-id="<?php echo esc_attr($suscripcion->id); ?>">
                                            <?php _e('Pausar', 'flavor-chat-ia'); ?>
                                        </button>
                                    <?php elseif ($suscripcion->estado === 'pausada'): ?>
                                        <button type="button" class="gc-btn gc-btn-primary gc-reanudar-suscripcion"
                                                data-suscripcion-id="<?php echo esc_attr($suscripcion->id); ?>">
                                            <?php _e('Reanudar', 'flavor-chat-ia'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($suscripcion->estado !== 'cancelada'): ?>
                                        <button type="button" class="gc-btn gc-btn-danger gc-cancelar-suscripcion"
                                                data-suscripcion-id="<?php echo esc_attr($suscripcion->id); ?>">
                                            <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="gc-cestas-disponibles">
                    <h3><?php _e('Cestas Disponibles', 'flavor-chat-ia'); ?></h3>
                    <div class="gc-cestas-grid">
                        <?php foreach ($cestas_disponibles as $cesta): ?>
                            <div class="gc-cesta-card">
                                <div class="gc-cesta-imagen">
                                    <?php if ($cesta->imagen_id): ?>
                                        <?php echo wp_get_attachment_image($cesta->imagen_id, 'medium'); ?>
                                    <?php else: ?>
                                        <span class="gc-placeholder dashicons dashicons-carrot"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="gc-cesta-info">
                                    <h4><?php echo esc_html($cesta->nombre); ?></h4>
                                    <p class="gc-cesta-descripcion"><?php echo esc_html($cesta->descripcion); ?></p>
                                    <p class="gc-cesta-precio">
                                        <?php if ($cesta->precio_base > 0): ?>
                                            <?php printf(__('Desde %s €', 'flavor-chat-ia'), number_format($cesta->precio_base, 2)); ?>
                                        <?php else: ?>
                                            <?php _e('Precio variable', 'flavor-chat-ia'); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <button type="button" class="gc-btn gc-btn-primary gc-suscribirse-cesta"
                                        data-cesta-id="<?php echo esc_attr($cesta->id); ?>"
                                        data-consumidor-id="<?php echo esc_attr($consumidor->id); ?>">
                                    <?php _e('Suscribirse', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ========================================
    // AJAX Handlers
    // ========================================

    /**
     * AJAX: Agregar a lista de compra
     */
    public function ajax_agregar_lista_compra() {
        check_ajax_referer('gc_lista_compra_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $producto_id = isset($_POST['producto_id']) ? absint($_POST['producto_id']) : 0;
        $cantidad = isset($_POST['cantidad']) ? floatval($_POST['cantidad']) : 1;

        $resultado = $this->agregar_a_lista(get_current_user_id(), $producto_id, $cantidad);

        if ($resultado['success']) {
            $resultado['count'] = $this->obtener_count_lista_compra();
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Quitar de lista
     */
    public function ajax_quitar_lista_compra() {
        check_ajax_referer('gc_lista_compra_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;

        $resultado = $this->quitar_de_lista(get_current_user_id(), $item_id);

        if ($resultado['success']) {
            $resultado['count'] = $this->obtener_count_lista_compra();
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Actualizar cantidad
     */
    public function ajax_actualizar_cantidad() {
        check_ajax_referer('gc_lista_compra_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
        $cantidad = isset($_POST['cantidad']) ? floatval($_POST['cantidad']) : 1;

        $resultado = $this->actualizar_cantidad(get_current_user_id(), $item_id, $cantidad);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Vaciar lista
     */
    public function ajax_vaciar_lista() {
        check_ajax_referer('gc_lista_compra_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $resultado = $this->vaciar_lista(get_current_user_id());
        wp_send_json_success($resultado);
    }

    /**
     * AJAX: Convertir lista a pedido
     */
    public function ajax_convertir_a_pedido() {
        check_ajax_referer('gc_lista_compra_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $resultado = $this->convertir_lista_a_pedido(get_current_user_id());

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }
}
