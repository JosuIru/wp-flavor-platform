<?php
/**
 * Integracion de Grupos de Consumo con Facturas
 *
 * Añade funcionalidades de facturacion a productores y consumidores
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase de integracion GC-Facturas
 */
class Flavor_GC_Facturas_Integration {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Solo inicializar si el modulo de facturas esta activo
        add_action('admin_init', [$this, 'maybe_init']);
    }

    /**
     * Inicializar si facturas esta activo
     */
    public function maybe_init() {
        if (!$this->is_facturas_active()) {
            return;
        }

        // Añadir metabox a productores
        add_action('add_meta_boxes', [$this, 'registrar_metabox_productor']);

        // Añadir columna de acciones a la tabla de productores
        add_filter('manage_gc_productor_posts_columns', [$this, 'agregar_columna_facturas']);
        add_action('manage_gc_productor_posts_custom_column', [$this, 'contenido_columna_facturas'], 10, 2);

        // AJAX para crear factura rapida
        add_action('wp_ajax_gc_crear_factura_productor', [$this, 'ajax_crear_factura_productor']);
        add_action('wp_ajax_gc_crear_factura_consumidor', [$this, 'ajax_crear_factura_consumidor']);
    }

    /**
     * Verificar si el modulo de facturas esta activo
     */
    private function is_facturas_active() {
        // Verificar que la clase del modulo existe (significa que esta cargado)
        if (class_exists('Flavor_Chat_Facturas_Module')) {
            return true;
        }

        // Verificar en la configuracion
        $settings = get_option('flavor_chat_ia_settings', []);
        $active_modules = $settings['active_modules'] ?? [];

        if (in_array('facturas', $active_modules)) {
            // Verificar que la tabla de facturas existe (modulo instalado)
            global $wpdb;
            $tabla = $wpdb->prefix . 'flavor_facturas';
            return $wpdb->get_var("SHOW TABLES LIKE '$tabla'") === $tabla;
        }

        return false;
    }

    /**
     * Registrar metabox en productores
     */
    public function registrar_metabox_productor() {
        add_meta_box(
            'gc_productor_facturas',
            __('Facturacion', 'flavor-platform'),
            [$this, 'render_metabox_facturas'],
            'gc_productor',
            'side',
            'default'
        );
    }

    /**
     * Render metabox de facturacion para productor
     */
    public function render_metabox_facturas($post) {
        $url_nueva_factura = admin_url(
            'admin.php?page=facturas-nueva' .
            '&cliente_id=' . $post->ID .
            '&cliente_tipo=productor' .
            '&cliente_nombre=' . urlencode($post->post_title)
        );

        $url_ver_facturas = admin_url(
            'admin.php?page=facturas-listado' .
            '&filtro_cliente=' . $post->ID
        );

        // Contar facturas existentes
        $total_facturas = $this->contar_facturas_cliente($post->ID, 'productor');
        $facturas_pendientes = $this->contar_facturas_cliente($post->ID, 'productor', 'pendiente');
        ?>
        <div class="gc-facturas-box">
            <?php if ($total_facturas > 0): ?>
            <div class="gc-facturas-stats" style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span><?php _e('Total facturas:', 'flavor-platform'); ?></span>
                    <strong><?php echo $total_facturas; ?></strong>
                </div>
                <?php if ($facturas_pendientes > 0): ?>
                <div style="display: flex; justify-content: space-between; color: #d63638;">
                    <span><?php _e('Pendientes de pago:', 'flavor-platform'); ?></span>
                    <strong><?php echo $facturas_pendientes; ?></strong>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <p style="margin-bottom: 10px;">
                <a href="<?php echo esc_url($url_nueva_factura); ?>" class="button button-primary" style="width: 100%; text-align: center;">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php _e('Crear Factura', 'flavor-platform'); ?>
                </a>
            </p>

            <?php if ($total_facturas > 0): ?>
            <p>
                <a href="<?php echo esc_url($url_ver_facturas); ?>" class="button" style="width: 100%; text-align: center;">
                    <span class="dashicons dashicons-media-text" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php _e('Ver Facturas', 'flavor-platform'); ?>
                </a>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Agregar columna de facturas a la tabla de productores
     */
    public function agregar_columna_facturas($columns) {
        $nueva_columnas = [];

        foreach ($columns as $key => $value) {
            $nueva_columnas[$key] = $value;

            // Insertar despues de la columna de titulo
            if ($key === 'title') {
                $nueva_columnas['gc_facturas'] = __('Facturas', 'flavor-platform');
            }
        }

        return $nueva_columnas;
    }

    /**
     * Contenido de la columna de facturas
     */
    public function contenido_columna_facturas($column, $post_id) {
        if ($column !== 'gc_facturas') {
            return;
        }

        $total = $this->contar_facturas_cliente($post_id, 'productor');
        $pendientes = $this->contar_facturas_cliente($post_id, 'productor', 'pendiente');

        $url_nueva = admin_url(
            'admin.php?page=facturas-nueva' .
            '&cliente_id=' . $post_id .
            '&cliente_tipo=productor'
        );

        ?>
        <div class="gc-facturas-col">
            <?php if ($total > 0): ?>
                <span class="gc-facturas-count">
                    <?php echo $total; ?>
                    <?php if ($pendientes > 0): ?>
                        <span style="color: #d63638;" title="<?php esc_attr_e('Pendientes', 'flavor-platform'); ?>">
                            (<?php echo $pendientes; ?>)
                        </span>
                    <?php endif; ?>
                </span>
            <?php else: ?>
                <span style="color: #999;">-</span>
            <?php endif; ?>
            <a href="<?php echo esc_url($url_nueva); ?>" class="button button-small" style="margin-left: 5px;">
                <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle; font-size: 14px;"></span>
            </a>
        </div>
        <?php
    }

    /**
     * Contar facturas de un cliente
     */
    private function contar_facturas_cliente($cliente_id, $tipo, $estado = null) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_facturas';

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return 0;
        }

        $where = "WHERE cliente_id = %d AND cliente_tipo = %s";
        $params = [$cliente_id, $tipo];

        if ($estado) {
            $where .= " AND estado = %s";
            $params[] = $estado;
        }

        $query = $wpdb->prepare("SELECT COUNT(*) FROM $tabla $where", ...$params);

        return (int) $wpdb->get_var($query);
    }

    /**
     * AJAX: Crear factura rapida para productor
     */
    public function ajax_crear_factura_productor() {
        check_ajax_referer('gc_facturas_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-platform'));
        }

        $productor_id = absint($_POST['productor_id'] ?? 0);

        if (!$productor_id) {
            wp_send_json_error(__('ID de productor invalido', 'flavor-platform'));
        }

        $productor = get_post($productor_id);

        if (!$productor || $productor->post_type !== 'gc_productor') {
            wp_send_json_error(__('Productor no encontrado', 'flavor-platform'));
        }

        // Redirigir a la pagina de nueva factura con datos pre-rellenados
        $url = admin_url(
            'admin.php?page=facturas-nueva' .
            '&cliente_id=' . $productor_id .
            '&cliente_tipo=productor' .
            '&cliente_nombre=' . urlencode($productor->post_title) .
            '&cliente_email=' . urlencode(get_post_meta($productor_id, '_gc_contacto_email', true))
        );

        wp_send_json_success(['redirect' => $url]);
    }

    /**
     * AJAX: Crear factura rapida para consumidor
     */
    public function ajax_crear_factura_consumidor() {
        check_ajax_referer('gc_facturas_nonce', 'nonce');

        if (!current_user_can('manage_options') && !current_user_can('gc_gestionar_pedidos')) {
            wp_send_json_error(__('Sin permisos', 'flavor-platform'));
        }

        $consumidor_id = absint($_POST['consumidor_id'] ?? 0);
        $usuario_id = absint($_POST['usuario_id'] ?? 0);

        if (!$usuario_id) {
            wp_send_json_error(__('ID de usuario invalido', 'flavor-platform'));
        }

        $usuario = get_user_by('ID', $usuario_id);

        if (!$usuario) {
            wp_send_json_error(__('Usuario no encontrado', 'flavor-platform'));
        }

        // Redirigir a la pagina de nueva factura con datos pre-rellenados
        $url = admin_url(
            'admin.php?page=facturas-nueva' .
            '&cliente_id=' . $usuario_id .
            '&cliente_tipo=consumidor' .
            '&cliente_nombre=' . urlencode($usuario->display_name) .
            '&cliente_email=' . urlencode($usuario->user_email)
        );

        wp_send_json_success(['redirect' => $url]);
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_GC_Facturas_Integration::get_instance();
}, 20);
