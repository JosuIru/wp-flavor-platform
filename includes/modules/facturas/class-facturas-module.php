<?php
/**
 * Modulo de Facturas para Chat IA
 * Sistema completo de facturacion para servicios comunitarios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Facturas - Gestion de facturacion completa
 */
class Flavor_Chat_Facturas_Module extends Flavor_Chat_Module_Base {

    /** @var string Version del modulo */
    const VERSION = '2.0.0';

    /** @var string Prefijo para tablas */
    private $tabla_prefijo;

    /** @var array Nombres de tablas */
    private $tablas = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'facturas';
        $this->name = __('Facturas', 'flavor-chat-ia');
        $this->description = __('Sistema completo de facturacion para servicios comunitarios con generacion PDF, pagos y recordatorios.', 'flavor-chat-ia');

        global $wpdb;
        $this->tabla_prefijo = $wpdb->prefix . 'flavor_';
        $this->tablas = [
            'facturas' => $this->tabla_prefijo . 'facturas',
            'lineas' => $this->tabla_prefijo . 'facturas_lineas',
            'pagos' => $this->tabla_prefijo . 'facturas_pagos',
            'series' => $this->tabla_prefijo . 'facturas_series',
            'config' => $this->tabla_prefijo . 'facturas_config',
        ];

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        return Flavor_Chat_Helpers::tabla_existe($this->tablas['facturas']);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Facturas no estan creadas. Activa el modulo para crearlas automaticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'serie_predeterminada' => 'F',
            'numeracion_inicial' => 1,
            'iva_predeterminado' => 21,
            'requiere_aprobacion' => false,
            'enviar_email_automatico' => true,
            'formato_numero' => '{SERIE}-{YEAR}-{NUM}',
            'dias_vencimiento' => 30,
            'moneda' => 'EUR',
            'simbolo_moneda' => '€',
            'decimales' => 2,
            'empresa_nombre' => '',
            'empresa_nif' => '',
            'empresa_direccion' => '',
            'empresa_email' => '',
            'empresa_telefono' => '',
            'empresa_logo' => '',
            'cuenta_bancaria' => '',
            'pie_factura' => '',
            'enviar_recordatorios' => true,
            'dias_recordatorio' => [7, 3, 1],
            'retenciones' => [
                'ninguna' => __('Sin retencion', 'flavor-chat-ia'),
                'irpf_15' => __('IRPF 15%', 'flavor-chat-ia'),
                'irpf_7' => __('IRPF 7%', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_facturas_listar', [$this, 'ajax_listar_facturas']);
        add_action('wp_ajax_flavor_facturas_crear', [$this, 'ajax_crear_factura']);
        add_action('wp_ajax_flavor_facturas_generar_pdf', [$this, 'ajax_generar_pdf']);
        add_action('wp_ajax_flavor_facturas_registrar_pago', [$this, 'ajax_registrar_pago']);
        add_action('wp_ajax_flavor_facturas_enviar_email', [$this, 'ajax_enviar_email']);
        add_action('wp_ajax_flavor_facturas_cancelar', [$this, 'ajax_cancelar_factura']);
        add_action('wp_ajax_flavor_facturas_estadisticas', [$this, 'ajax_estadisticas']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cron para recordatorios
        add_action('flavor_facturas_enviar_recordatorios', [$this, 'enviar_recordatorios_vencimiento']);

        if (!wp_next_scheduled('flavor_facturas_enviar_recordatorios')) {
            wp_schedule_event(time(), 'daily', 'flavor_facturas_enviar_recordatorios');
        }
    }

    /**
     * Registra shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('flavor_mis_facturas', [$this, 'shortcode_mis_facturas']);
        add_shortcode('flavor_detalle_factura', [$this, 'shortcode_detalle_factura']);
        add_shortcode('flavor_pagar_factura', [$this, 'shortcode_pagar_factura']);
        add_shortcode('flavor_historial_pagos', [$this, 'shortcode_historial_pagos']);
        add_shortcode('flavor_nueva_factura', [$this, 'shortcode_nueva_factura']);
    }

    /**
     * Encola assets frontend
     */
    public function enqueue_assets() {
        if ($this->should_load_assets()) {
            $modulo_url = plugin_dir_url(__FILE__);

            wp_enqueue_style(
                'flavor-facturas',
                $modulo_url . 'assets/css/facturas.css',
                [],
                self::VERSION
            );

            wp_enqueue_script(
                'flavor-facturas',
                $modulo_url . 'assets/js/facturas.js',
                ['jquery'],
                self::VERSION,
                true
            );

            wp_localize_script('flavor-facturas', 'flavorFacturasConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('flavor_facturas_nonce'),
                'currency' => $this->get_setting('moneda'),
                'locale' => get_locale(),
            ]);
        }
    }

    /**
     * Encola assets admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'flavor-chat') !== false) {
            $this->enqueue_assets();
        }
    }

    /**
     * Determina si cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['flavor_mis_facturas', 'flavor_detalle_factura', 'flavor_pagar_factura', 'flavor_historial_pagos', 'flavor_nueva_factura'];
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        if (!Flavor_Chat_Helpers::tabla_existe($this->tablas['facturas'])) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_facturas = "CREATE TABLE IF NOT EXISTS {$this->tablas['facturas']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            numero_factura varchar(50) NOT NULL,
            serie varchar(10) DEFAULT 'F',
            numero_serie int(11) NOT NULL,
            año int(4) NOT NULL,
            cliente_id bigint(20) unsigned DEFAULT NULL,
            cliente_nombre varchar(255) NOT NULL,
            cliente_nif varchar(50) DEFAULT NULL,
            cliente_direccion text DEFAULT NULL,
            cliente_email varchar(255) DEFAULT NULL,
            cliente_telefono varchar(50) DEFAULT NULL,
            fecha_emision date NOT NULL,
            fecha_vencimiento date DEFAULT NULL,
            base_imponible decimal(12,2) NOT NULL DEFAULT 0.00,
            total_iva decimal(12,2) NOT NULL DEFAULT 0.00,
            total_retencion decimal(12,2) DEFAULT 0.00,
            total decimal(12,2) NOT NULL DEFAULT 0.00,
            total_pagado decimal(12,2) DEFAULT 0.00,
            estado enum('borrador','emitida','parcial','pagada','vencida','cancelada') DEFAULT 'borrador',
            observaciones text DEFAULT NULL,
            notas_internas text DEFAULT NULL,
            metodo_pago varchar(50) DEFAULT NULL,
            referencia_externa varchar(100) DEFAULT NULL,
            creado_por bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            fecha_envio_email datetime DEFAULT NULL,
            pdf_generado tinyint(1) DEFAULT 0,
            pdf_ruta varchar(500) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY numero_factura (numero_factura),
            KEY cliente_id (cliente_id),
            KEY estado (estado),
            KEY fecha_emision (fecha_emision),
            KEY fecha_vencimiento (fecha_vencimiento),
            KEY serie_año (serie, año)
        ) $charset_collate;";

        $sql_lineas = "CREATE TABLE IF NOT EXISTS {$this->tablas['lineas']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            factura_id bigint(20) unsigned NOT NULL,
            concepto varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            cantidad decimal(12,4) NOT NULL DEFAULT 1.0000,
            precio_unitario decimal(12,4) NOT NULL DEFAULT 0.0000,
            descuento_porcentaje decimal(5,2) DEFAULT 0.00,
            descuento_importe decimal(12,2) DEFAULT 0.00,
            iva_porcentaje decimal(5,2) NOT NULL DEFAULT 21.00,
            iva_importe decimal(12,2) NOT NULL DEFAULT 0.00,
            retencion_porcentaje decimal(5,2) DEFAULT 0.00,
            retencion_importe decimal(12,2) DEFAULT 0.00,
            base_linea decimal(12,2) NOT NULL DEFAULT 0.00,
            total_linea decimal(12,2) NOT NULL DEFAULT 0.00,
            orden int(11) DEFAULT 0,
            servicio_id bigint(20) unsigned DEFAULT NULL,
            periodo_desde date DEFAULT NULL,
            periodo_hasta date DEFAULT NULL,
            PRIMARY KEY (id),
            KEY factura_id (factura_id),
            KEY servicio_id (servicio_id)
        ) $charset_collate;";

        $sql_pagos = "CREATE TABLE IF NOT EXISTS {$this->tablas['pagos']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            factura_id bigint(20) unsigned NOT NULL,
            importe decimal(12,2) NOT NULL,
            fecha_pago date NOT NULL,
            metodo_pago varchar(50) NOT NULL,
            referencia varchar(255) DEFAULT NULL,
            notas text DEFAULT NULL,
            estado enum('pendiente','confirmado','rechazado','devuelto') DEFAULT 'confirmado',
            registrado_por bigint(20) unsigned DEFAULT NULL,
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY factura_id (factura_id),
            KEY fecha_pago (fecha_pago),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_series = "CREATE TABLE IF NOT EXISTS {$this->tablas['series']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            codigo varchar(10) NOT NULL,
            nombre varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            formato varchar(100) DEFAULT '{SERIE}-{YEAR}-{NUM}',
            ultimo_numero int(11) DEFAULT 0,
            año_actual int(4) DEFAULT NULL,
            activa tinyint(1) DEFAULT 1,
            predeterminada tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo)
        ) $charset_collate;";

        $sql_config = "CREATE TABLE IF NOT EXISTS {$this->tablas['config']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            clave varchar(100) NOT NULL,
            valor longtext DEFAULT NULL,
            tipo varchar(20) DEFAULT 'string',
            PRIMARY KEY (id),
            UNIQUE KEY clave (clave)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_facturas);
        dbDelta($sql_lineas);
        dbDelta($sql_pagos);
        dbDelta($sql_series);
        dbDelta($sql_config);

        $this->insertar_serie_predeterminada();
    }

    /**
     * Inserta serie predeterminada
     */
    private function insertar_serie_predeterminada() {
        global $wpdb;

        $existe = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tablas['series']} WHERE codigo = 'F'");

        if (!$existe) {
            $wpdb->insert($this->tablas['series'], [
                'codigo' => 'F',
                'nombre' => 'Facturas',
                'descripcion' => 'Serie principal de facturas',
                'formato' => '{SERIE}-{YEAR}-{NUM}',
                'ultimo_numero' => 0,
                'año_actual' => date('Y'),
                'activa' => 1,
                'predeterminada' => 1,
            ]);
        }
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor-chat/v1';

        register_rest_route($namespace, '/facturas', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'rest_listar_facturas'],
                'permission_callback' => [$this, 'rest_permisos_lectura'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'rest_crear_factura'],
                'permission_callback' => [$this, 'rest_permisos_escritura'],
            ],
        ]);

        register_rest_route($namespace, '/facturas/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'rest_obtener_factura'],
                'permission_callback' => [$this, 'rest_permisos_lectura'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'rest_actualizar_factura'],
                'permission_callback' => [$this, 'rest_permisos_escritura'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'rest_eliminar_factura'],
                'permission_callback' => [$this, 'rest_permisos_admin'],
            ],
        ]);

        register_rest_route($namespace, '/facturas/(?P<id>\d+)/pdf', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_generar_pdf'],
            'permission_callback' => [$this, 'rest_permisos_lectura'],
        ]);

        register_rest_route($namespace, '/facturas/(?P<id>\d+)/pagos', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'rest_listar_pagos'],
                'permission_callback' => [$this, 'rest_permisos_lectura'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'rest_registrar_pago'],
                'permission_callback' => [$this, 'rest_permisos_escritura'],
            ],
        ]);

        register_rest_route($namespace, '/facturas/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_estadisticas'],
            'permission_callback' => [$this, 'rest_permisos_lectura'],
        ]);
    }

    /**
     * Permisos REST - Lectura
     */
    public function rest_permisos_lectura() {
        return current_user_can('read');
    }

    /**
     * Permisos REST - Escritura
     */
    public function rest_permisos_escritura() {
        return current_user_can('edit_posts');
    }

    /**
     * Permisos REST - Admin
     */
    public function rest_permisos_admin() {
        return current_user_can('manage_options');
    }

    // =========================================================================
    // OPERACIONES CRUD DE FACTURAS
    // =========================================================================

    /**
     * Obtiene una factura por ID
     */
    public function obtener_factura($factura_id) {
        global $wpdb;

        $factura = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tablas['facturas']} WHERE id = %d",
            $factura_id
        ));

        if (!$factura) {
            return null;
        }

        $factura->lineas = $this->obtener_lineas_factura($factura_id);
        $factura->pagos = $this->obtener_pagos_factura($factura_id);

        return $factura;
    }

    /**
     * Obtiene lineas de una factura
     */
    public function obtener_lineas_factura($factura_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tablas['lineas']} WHERE factura_id = %d ORDER BY orden ASC",
            $factura_id
        ));
    }

    /**
     * Obtiene pagos de una factura
     */
    public function obtener_pagos_factura($factura_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tablas['pagos']} WHERE factura_id = %d ORDER BY fecha_pago DESC",
            $factura_id
        ));
    }

    /**
     * Lista facturas con filtros
     */
    public function listar_facturas($argumentos = []) {
        global $wpdb;

        $defaults = [
            'estado' => '',
            'cliente_id' => 0,
            'desde' => '',
            'hasta' => '',
            'busqueda' => '',
            'serie' => '',
            'pagina' => 1,
            'por_pagina' => 20,
            'orderby' => 'fecha_emision',
            'order' => 'DESC',
        ];

        $argumentos = wp_parse_args($argumentos, $defaults);

        $where = ['1=1'];
        $valores_preparados = [];

        if (!empty($argumentos['estado'])) {
            $where[] = 'estado = %s';
            $valores_preparados[] = $argumentos['estado'];
        }

        if (!empty($argumentos['cliente_id'])) {
            $where[] = 'cliente_id = %d';
            $valores_preparados[] = $argumentos['cliente_id'];
        }

        if (!empty($argumentos['desde'])) {
            $where[] = 'fecha_emision >= %s';
            $valores_preparados[] = $argumentos['desde'];
        }

        if (!empty($argumentos['hasta'])) {
            $where[] = 'fecha_emision <= %s';
            $valores_preparados[] = $argumentos['hasta'];
        }

        if (!empty($argumentos['busqueda'])) {
            $where[] = '(numero_factura LIKE %s OR cliente_nombre LIKE %s OR cliente_nif LIKE %s)';
            $termino_busqueda = '%' . $wpdb->esc_like($argumentos['busqueda']) . '%';
            $valores_preparados[] = $termino_busqueda;
            $valores_preparados[] = $termino_busqueda;
            $valores_preparados[] = $termino_busqueda;
        }

        if (!empty($argumentos['serie'])) {
            $where[] = 'serie = %s';
            $valores_preparados[] = $argumentos['serie'];
        }

        $sql_where = implode(' AND ', $where);

        // Contar total
        $sql_count = "SELECT COUNT(*) FROM {$this->tablas['facturas']} WHERE $sql_where";
        if (!empty($valores_preparados)) {
            $total = $wpdb->get_var($wpdb->prepare($sql_count, ...$valores_preparados));
        } else {
            $total = $wpdb->get_var($sql_count);
        }

        // Obtener resultados paginados
        $offset = ($argumentos['pagina'] - 1) * $argumentos['por_pagina'];
        $orderby = sanitize_sql_orderby($argumentos['orderby'] . ' ' . $argumentos['order']);

        $sql = "SELECT * FROM {$this->tablas['facturas']} WHERE $sql_where ORDER BY $orderby LIMIT %d OFFSET %d";
        $valores_preparados[] = $argumentos['por_pagina'];
        $valores_preparados[] = $offset;

        $facturas = $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparados));

        return [
            'facturas' => $facturas,
            'total' => (int) $total,
            'pagina' => (int) $argumentos['pagina'],
            'por_pagina' => (int) $argumentos['por_pagina'],
            'total_paginas' => ceil($total / $argumentos['por_pagina']),
        ];
    }

    /**
     * Crea una nueva factura
     */
    public function crear_factura($datos) {
        global $wpdb;

        // Validar datos requeridos
        if (empty($datos['cliente_nombre'])) {
            return new WP_Error('datos_invalidos', 'El nombre del cliente es obligatorio');
        }

        if (empty($datos['lineas']) || !is_array($datos['lineas'])) {
            return new WP_Error('datos_invalidos', 'Debe incluir al menos una linea de factura');
        }

        // Obtener siguiente numero de factura
        $serie = $datos['serie'] ?? $this->get_setting('serie_predeterminada');
        $numero_factura = $this->generar_numero_factura($serie);

        if (is_wp_error($numero_factura)) {
            return $numero_factura;
        }

        // Calcular totales
        $totales = $this->calcular_totales_factura($datos['lineas']);

        // Preparar datos de factura
        $datos_factura = [
            'numero_factura' => $numero_factura['numero_completo'],
            'serie' => $serie,
            'numero_serie' => $numero_factura['numero'],
            'año' => date('Y'),
            'cliente_id' => $datos['cliente_id'] ?? null,
            'cliente_nombre' => sanitize_text_field($datos['cliente_nombre']),
            'cliente_nif' => sanitize_text_field($datos['cliente_nif'] ?? ''),
            'cliente_direccion' => sanitize_textarea_field($datos['cliente_direccion'] ?? ''),
            'cliente_email' => sanitize_email($datos['cliente_email'] ?? ''),
            'cliente_telefono' => sanitize_text_field($datos['cliente_telefono'] ?? ''),
            'fecha_emision' => $datos['fecha_emision'] ?? date('Y-m-d'),
            'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+' . $this->get_setting('dias_vencimiento') . ' days')),
            'base_imponible' => $totales['base_imponible'],
            'total_iva' => $totales['total_iva'],
            'total_retencion' => $totales['total_retencion'],
            'total' => $totales['total'],
            'total_pagado' => 0,
            'estado' => $datos['estado'] ?? 'borrador',
            'observaciones' => sanitize_textarea_field($datos['observaciones'] ?? ''),
            'notas_internas' => sanitize_textarea_field($datos['notas_internas'] ?? ''),
            'metodo_pago' => sanitize_text_field($datos['metodo_pago'] ?? 'transferencia'),
            'creado_por' => get_current_user_id(),
        ];

        $resultado_insercion = $wpdb->insert($this->tablas['facturas'], $datos_factura);

        if (!$resultado_insercion) {
            return new WP_Error('error_db', 'Error al crear la factura');
        }

        $factura_id = $wpdb->insert_id;

        // Insertar lineas
        foreach ($datos['lineas'] as $indice => $linea) {
            $this->insertar_linea_factura($factura_id, $linea, $indice);
        }

        // Actualizar contador de serie
        $this->actualizar_contador_serie($serie);

        do_action('flavor_factura_creada', $factura_id, $datos_factura);

        return $factura_id;
    }

    /**
     * Genera numero de factura
     */
    private function generar_numero_factura($serie) {
        global $wpdb;

        $año_actual = date('Y');

        $datos_serie = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tablas['series']} WHERE codigo = %s",
            $serie
        ));

        if (!$datos_serie) {
            return new WP_Error('serie_invalida', 'Serie de factura no encontrada');
        }

        // Resetear contador si cambio el año
        if ($datos_serie->año_actual != $año_actual) {
            $wpdb->update(
                $this->tablas['series'],
                ['año_actual' => $año_actual, 'ultimo_numero' => 0],
                ['codigo' => $serie]
            );
            $numero_siguiente = 1;
        } else {
            $numero_siguiente = $datos_serie->ultimo_numero + 1;
        }

        // Generar numero formateado
        $formato = $datos_serie->formato ?: '{SERIE}-{YEAR}-{NUM}';
        $numero_completo = str_replace(
            ['{SERIE}', '{YEAR}', '{NUM}'],
            [$serie, $año_actual, str_pad($numero_siguiente, 5, '0', STR_PAD_LEFT)],
            $formato
        );

        return [
            'numero' => $numero_siguiente,
            'numero_completo' => $numero_completo,
        ];
    }

    /**
     * Actualiza contador de serie
     */
    private function actualizar_contador_serie($serie) {
        global $wpdb;

        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tablas['series']} SET ultimo_numero = ultimo_numero + 1 WHERE codigo = %s",
            $serie
        ));
    }

    /**
     * Calcula totales de factura
     */
    private function calcular_totales_factura($lineas) {
        $base_imponible = 0;
        $total_iva = 0;
        $total_retencion = 0;

        foreach ($lineas as $linea) {
            $cantidad = floatval($linea['cantidad'] ?? 1);
            $precio = floatval($linea['precio_unitario'] ?? $linea['precio'] ?? 0);
            $descuento_porcentaje = floatval($linea['descuento_porcentaje'] ?? $linea['descuento'] ?? 0);
            $iva_porcentaje = floatval($linea['iva_porcentaje'] ?? $linea['iva'] ?? 21);
            $retencion_porcentaje = floatval($linea['retencion_porcentaje'] ?? 0);

            $subtotal = $cantidad * $precio;
            $descuento_importe = $subtotal * ($descuento_porcentaje / 100);
            $base_linea = $subtotal - $descuento_importe;
            $iva_importe = $base_linea * ($iva_porcentaje / 100);
            $retencion_importe = $base_linea * ($retencion_porcentaje / 100);

            $base_imponible += $base_linea;
            $total_iva += $iva_importe;
            $total_retencion += $retencion_importe;
        }

        return [
            'base_imponible' => round($base_imponible, 2),
            'total_iva' => round($total_iva, 2),
            'total_retencion' => round($total_retencion, 2),
            'total' => round($base_imponible + $total_iva - $total_retencion, 2),
        ];
    }

    /**
     * Inserta linea de factura
     */
    private function insertar_linea_factura($factura_id, $linea, $orden) {
        global $wpdb;

        $cantidad = floatval($linea['cantidad'] ?? 1);
        $precio = floatval($linea['precio_unitario'] ?? $linea['precio'] ?? 0);
        $descuento_porcentaje = floatval($linea['descuento_porcentaje'] ?? $linea['descuento'] ?? 0);
        $iva_porcentaje = floatval($linea['iva_porcentaje'] ?? $linea['iva'] ?? 21);
        $retencion_porcentaje = floatval($linea['retencion_porcentaje'] ?? 0);

        $subtotal = $cantidad * $precio;
        $descuento_importe = $subtotal * ($descuento_porcentaje / 100);
        $base_linea = $subtotal - $descuento_importe;
        $iva_importe = $base_linea * ($iva_porcentaje / 100);
        $retencion_importe = $base_linea * ($retencion_porcentaje / 100);
        $total_linea = $base_linea + $iva_importe - $retencion_importe;

        $wpdb->insert($this->tablas['lineas'], [
            'factura_id' => $factura_id,
            'concepto' => sanitize_text_field($linea['concepto'] ?? ''),
            'descripcion' => sanitize_textarea_field($linea['descripcion'] ?? ''),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'descuento_porcentaje' => $descuento_porcentaje,
            'descuento_importe' => round($descuento_importe, 2),
            'iva_porcentaje' => $iva_porcentaje,
            'iva_importe' => round($iva_importe, 2),
            'retencion_porcentaje' => $retencion_porcentaje,
            'retencion_importe' => round($retencion_importe, 2),
            'base_linea' => round($base_linea, 2),
            'total_linea' => round($total_linea, 2),
            'orden' => $orden,
            'servicio_id' => $linea['servicio_id'] ?? null,
            'periodo_desde' => $linea['periodo_desde'] ?? null,
            'periodo_hasta' => $linea['periodo_hasta'] ?? null,
        ]);
    }

    /**
     * Actualiza estado de factura
     */
    public function actualizar_estado_factura($factura_id, $nuevo_estado) {
        global $wpdb;

        $estados_validos = ['borrador', 'emitida', 'parcial', 'pagada', 'vencida', 'cancelada'];

        if (!in_array($nuevo_estado, $estados_validos)) {
            return new WP_Error('estado_invalido', 'Estado de factura no valido');
        }

        $resultado = $wpdb->update(
            $this->tablas['facturas'],
            ['estado' => $nuevo_estado],
            ['id' => $factura_id]
        );

        if ($resultado === false) {
            return new WP_Error('error_db', 'Error al actualizar estado');
        }

        do_action('flavor_factura_estado_actualizado', $factura_id, $nuevo_estado);

        return true;
    }

    // =========================================================================
    // GESTION DE PAGOS
    // =========================================================================

    /**
     * Registra un pago
     */
    public function registrar_pago($datos) {
        global $wpdb;

        $factura_id = absint($datos['factura_id']);
        $importe = floatval($datos['importe']);

        if (!$factura_id || $importe <= 0) {
            return new WP_Error('datos_invalidos', 'Datos de pago invalidos');
        }

        $factura = $this->obtener_factura($factura_id);

        if (!$factura) {
            return new WP_Error('factura_no_encontrada', 'Factura no encontrada');
        }

        $pendiente = $factura->total - $factura->total_pagado;

        if ($importe > $pendiente) {
            return new WP_Error('importe_excedido', 'El importe excede el pendiente de pago');
        }

        $wpdb->insert($this->tablas['pagos'], [
            'factura_id' => $factura_id,
            'importe' => $importe,
            'fecha_pago' => $datos['fecha_pago'] ?? date('Y-m-d'),
            'metodo_pago' => sanitize_text_field($datos['metodo_pago'] ?? 'transferencia'),
            'referencia' => sanitize_text_field($datos['referencia'] ?? ''),
            'notas' => sanitize_textarea_field($datos['notas'] ?? ''),
            'estado' => 'confirmado',
            'registrado_por' => get_current_user_id(),
        ]);

        $pago_id = $wpdb->insert_id;

        // Actualizar total pagado en factura
        $nuevo_total_pagado = $factura->total_pagado + $importe;

        $wpdb->update(
            $this->tablas['facturas'],
            ['total_pagado' => $nuevo_total_pagado],
            ['id' => $factura_id]
        );

        // Actualizar estado si corresponde
        if ($nuevo_total_pagado >= $factura->total) {
            $this->actualizar_estado_factura($factura_id, 'pagada');
        } elseif ($nuevo_total_pagado > 0) {
            $this->actualizar_estado_factura($factura_id, 'parcial');
        }

        do_action('flavor_factura_pago_registrado', $pago_id, $factura_id, $importe);

        return $pago_id;
    }

    // =========================================================================
    // GENERACION DE PDF
    // =========================================================================

    /**
     * Genera PDF de factura
     */
    public function generar_pdf($factura_id) {
        $factura = $this->obtener_factura($factura_id);

        if (!$factura) {
            return new WP_Error('factura_no_encontrada', 'Factura no encontrada');
        }

        // Verificar si TCPDF esta disponible
        if (!class_exists('TCPDF')) {
            $tcpdf_path = WP_PLUGIN_DIR . '/flavor-chat-ia/vendor/tcpdf/tcpdf.php';
            if (file_exists($tcpdf_path)) {
                require_once $tcpdf_path;
            } else {
                return new WP_Error('tcpdf_no_disponible', 'TCPDF no esta instalado');
            }
        }

        // Crear directorio si no existe
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/facturas/' . date('Y') . '/' . date('m');

        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        $nombre_archivo = 'factura-' . $factura->numero_factura . '.pdf';
        $ruta_pdf = $pdf_dir . '/' . $nombre_archivo;
        $url_pdf = $upload_dir['baseurl'] . '/facturas/' . date('Y') . '/' . date('m') . '/' . $nombre_archivo;

        // Crear PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');

        $pdf->SetCreator('Flavor Chat IA');
        $pdf->SetAuthor($this->get_setting('empresa_nombre'));
        $pdf->SetTitle('Factura ' . $factura->numero_factura);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        $pdf->AddPage();

        // Generar contenido HTML
        $html = $this->generar_html_factura($factura);

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output($ruta_pdf, 'F');

        // Actualizar factura con ruta PDF
        global $wpdb;
        $wpdb->update(
            $this->tablas['facturas'],
            ['pdf_generado' => 1, 'pdf_ruta' => $ruta_pdf],
            ['id' => $factura_id]
        );

        return [
            'ruta' => $ruta_pdf,
            'url' => $url_pdf,
            'nombre' => $nombre_archivo,
        ];
    }

    /**
     * Genera HTML para PDF
     */
    private function generar_html_factura($factura) {
        $configuracion = $this->get_all_settings();
        $moneda = $configuracion['simbolo_moneda'] ?? '€';

        $html = '<style>
            body { font-family: helvetica; font-size: 10pt; }
            .header { margin-bottom: 20px; }
            .empresa { font-size: 14pt; font-weight: bold; color: #2563eb; }
            .factura-titulo { font-size: 18pt; font-weight: bold; text-align: right; }
            .factura-numero { font-size: 12pt; text-align: right; color: #666; }
            .datos-grid { width: 100%; margin: 20px 0; }
            .datos-bloque { background: #f3f4f6; padding: 10px; border-radius: 5px; }
            .datos-titulo { font-weight: bold; font-size: 9pt; color: #666; margin-bottom: 5px; }
            table.lineas { width: 100%; border-collapse: collapse; margin: 20px 0; }
            table.lineas th { background: #2563eb; color: white; padding: 8px; text-align: left; font-size: 9pt; }
            table.lineas td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
            .totales { width: 300px; float: right; margin-top: 20px; }
            .totales tr td { padding: 5px; }
            .totales .total-final { font-size: 14pt; font-weight: bold; background: #f3f4f6; }
            .pie { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 9pt; color: #666; }
        </style>';

        // Cabecera
        $html .= '<table class="header" width="100%"><tr>';
        $html .= '<td width="60%">';

        if (!empty($configuracion['empresa_logo'])) {
            $html .= '<img src="' . esc_url($configuracion['empresa_logo']) . '" height="50"><br>';
        }

        $html .= '<span class="empresa">' . esc_html($configuracion['empresa_nombre']) . '</span><br>';
        $html .= esc_html($configuracion['empresa_nif']) . '<br>';
        $html .= nl2br(esc_html($configuracion['empresa_direccion']));
        $html .= '</td>';

        $html .= '<td width="40%" style="text-align: right;">';
        $html .= '<span class="factura-titulo">FACTURA</span><br>';
        $html .= '<span class="factura-numero">' . esc_html($factura->numero_factura) . '</span>';
        $html .= '</td></tr></table>';

        // Datos cliente y fechas
        $html .= '<table class="datos-grid" width="100%"><tr>';
        $html .= '<td width="50%" class="datos-bloque">';
        $html .= '<div class="datos-titulo">CLIENTE</div>';
        $html .= '<strong>' . esc_html($factura->cliente_nombre) . '</strong><br>';
        if ($factura->cliente_nif) {
            $html .= 'NIF: ' . esc_html($factura->cliente_nif) . '<br>';
        }
        $html .= nl2br(esc_html($factura->cliente_direccion));
        $html .= '</td>';

        $html .= '<td width="10%"></td>';

        $html .= '<td width="40%" class="datos-bloque">';
        $html .= '<div class="datos-titulo">DATOS FACTURA</div>';
        $html .= 'Fecha emision: ' . date('d/m/Y', strtotime($factura->fecha_emision)) . '<br>';
        if ($factura->fecha_vencimiento) {
            $html .= 'Vencimiento: ' . date('d/m/Y', strtotime($factura->fecha_vencimiento)) . '<br>';
        }
        $html .= 'Metodo pago: ' . ucfirst($factura->metodo_pago);
        $html .= '</td></tr></table>';

        // Lineas
        $html .= '<table class="lineas">';
        $html .= '<thead><tr>';
        $html .= '<th width="40%">Concepto</th>';
        $html .= '<th width="10%">Cant.</th>';
        $html .= '<th width="15%">Precio</th>';
        $html .= '<th width="10%">Dto.</th>';
        $html .= '<th width="10%">IVA</th>';
        $html .= '<th width="15%">Total</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($factura->lineas as $linea) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($linea->concepto);
            if ($linea->descripcion) {
                $html .= '<br><small style="color:#666;">' . esc_html($linea->descripcion) . '</small>';
            }
            $html .= '</td>';
            $html .= '<td>' . number_format($linea->cantidad, 2, ',', '.') . '</td>';
            $html .= '<td>' . number_format($linea->precio_unitario, 2, ',', '.') . ' ' . $moneda . '</td>';
            $html .= '<td>' . number_format($linea->descuento_porcentaje, 0) . '%</td>';
            $html .= '<td>' . number_format($linea->iva_porcentaje, 0) . '%</td>';
            $html .= '<td style="text-align:right;"><strong>' . number_format($linea->total_linea, 2, ',', '.') . ' ' . $moneda . '</strong></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Totales
        $html .= '<table class="totales">';
        $html .= '<tr><td>Base imponible:</td><td style="text-align:right;">' . number_format($factura->base_imponible, 2, ',', '.') . ' ' . $moneda . '</td></tr>';
        $html .= '<tr><td>IVA:</td><td style="text-align:right;">' . number_format($factura->total_iva, 2, ',', '.') . ' ' . $moneda . '</td></tr>';

        if ($factura->total_retencion > 0) {
            $html .= '<tr><td>Retencion:</td><td style="text-align:right;">-' . number_format($factura->total_retencion, 2, ',', '.') . ' ' . $moneda . '</td></tr>';
        }

        $html .= '<tr class="total-final"><td><strong>TOTAL:</strong></td><td style="text-align:right;"><strong>' . number_format($factura->total, 2, ',', '.') . ' ' . $moneda . '</strong></td></tr>';
        $html .= '</table>';

        // Observaciones
        if ($factura->observaciones) {
            $html .= '<div style="clear:both; margin-top: 30px;">';
            $html .= '<strong>Observaciones:</strong><br>';
            $html .= nl2br(esc_html($factura->observaciones));
            $html .= '</div>';
        }

        // Datos bancarios
        if (!empty($configuracion['cuenta_bancaria'])) {
            $html .= '<div style="margin-top: 20px; background: #f3f4f6; padding: 10px; border-radius: 5px;">';
            $html .= '<strong>Datos para transferencia:</strong><br>';
            $html .= esc_html($configuracion['cuenta_bancaria']);
            $html .= '</div>';
        }

        // Pie
        if (!empty($configuracion['pie_factura'])) {
            $html .= '<div class="pie">' . nl2br(esc_html($configuracion['pie_factura'])) . '</div>';
        }

        return $html;
    }

    // =========================================================================
    // ENVIO DE EMAIL
    // =========================================================================

    /**
     * Envia factura por email
     */
    public function enviar_factura_email($factura_id, $email_destinatario, $opciones = []) {
        $factura = $this->obtener_factura($factura_id);

        if (!$factura) {
            return new WP_Error('factura_no_encontrada', 'Factura no encontrada');
        }

        // Generar PDF si no existe
        if (!$factura->pdf_generado || !file_exists($factura->pdf_ruta)) {
            $resultado_pdf = $this->generar_pdf($factura_id);
            if (is_wp_error($resultado_pdf)) {
                return $resultado_pdf;
            }
            $ruta_pdf = $resultado_pdf['ruta'];
        } else {
            $ruta_pdf = $factura->pdf_ruta;
        }

        $configuracion = $this->get_all_settings();

        // Preparar email
        $asunto = $opciones['asunto'] ?? sprintf(
            __('Factura %s - %s', 'flavor-chat-ia'),
            $factura->numero_factura,
            $configuracion['empresa_nombre']
        );

        $mensaje = $opciones['mensaje'] ?? $this->generar_mensaje_email($factura, $configuracion);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $configuracion['empresa_nombre'] . ' <' . $configuracion['empresa_email'] . '>',
        ];

        $adjuntos = [$ruta_pdf];

        $enviado = wp_mail($email_destinatario, $asunto, $mensaje, $headers, $adjuntos);

        if ($enviado) {
            global $wpdb;
            $wpdb->update(
                $this->tablas['facturas'],
                ['fecha_envio_email' => current_time('mysql')],
                ['id' => $factura_id]
            );

            do_action('flavor_factura_email_enviado', $factura_id, $email_destinatario);
        }

        return $enviado;
    }

    /**
     * Genera mensaje de email
     */
    private function generar_mensaje_email($factura, $configuracion) {
        $moneda = $configuracion['simbolo_moneda'] ?? '€';

        $mensaje = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6;">';
        $mensaje .= '<h2>Factura ' . esc_html($factura->numero_factura) . '</h2>';
        $mensaje .= '<p>Estimado/a ' . esc_html($factura->cliente_nombre) . ',</p>';
        $mensaje .= '<p>Adjuntamos la factura correspondiente con los siguientes datos:</p>';
        $mensaje .= '<ul>';
        $mensaje .= '<li><strong>Numero:</strong> ' . esc_html($factura->numero_factura) . '</li>';
        $mensaje .= '<li><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($factura->fecha_emision)) . '</li>';
        $mensaje .= '<li><strong>Importe:</strong> ' . number_format($factura->total, 2, ',', '.') . ' ' . $moneda . '</li>';

        if ($factura->fecha_vencimiento) {
            $mensaje .= '<li><strong>Vencimiento:</strong> ' . date('d/m/Y', strtotime($factura->fecha_vencimiento)) . '</li>';
        }

        $mensaje .= '</ul>';

        if (!empty($configuracion['cuenta_bancaria'])) {
            $mensaje .= '<p><strong>Datos bancarios para el pago:</strong><br>' . nl2br(esc_html($configuracion['cuenta_bancaria'])) . '</p>';
        }

        $mensaje .= '<p>Gracias por su confianza.</p>';
        $mensaje .= '<p>Atentamente,<br><strong>' . esc_html($configuracion['empresa_nombre']) . '</strong></p>';
        $mensaje .= '</body></html>';

        return $mensaje;
    }

    /**
     * Envia recordatorios de vencimiento
     */
    public function enviar_recordatorios_vencimiento() {
        if (!$this->get_setting('enviar_recordatorios')) {
            return;
        }

        global $wpdb;
        $dias_recordatorio = $this->get_setting('dias_recordatorio');

        foreach ($dias_recordatorio as $dias) {
            $fecha_objetivo = date('Y-m-d', strtotime("+{$dias} days"));

            $facturas_vencer = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->tablas['facturas']}
                WHERE estado IN ('emitida', 'parcial')
                AND fecha_vencimiento = %s
                AND total_pagado < total",
                $fecha_objetivo
            ));

            foreach ($facturas_vencer as $factura) {
                if ($factura->cliente_email) {
                    $this->enviar_recordatorio($factura, $dias);
                }
            }
        }
    }

    /**
     * Envia recordatorio individual
     */
    private function enviar_recordatorio($factura, $dias_restantes) {
        $configuracion = $this->get_all_settings();
        $moneda = $configuracion['simbolo_moneda'] ?? '€';
        $pendiente = $factura->total - $factura->total_pagado;

        $asunto = sprintf(
            __('Recordatorio: Factura %s vence en %d dias', 'flavor-chat-ia'),
            $factura->numero_factura,
            $dias_restantes
        );

        $mensaje = '<html><body style="font-family: Arial, sans-serif;">';
        $mensaje .= '<h2>Recordatorio de Pago</h2>';
        $mensaje .= '<p>Estimado/a ' . esc_html($factura->cliente_nombre) . ',</p>';
        $mensaje .= '<p>Le recordamos que la factura <strong>' . esc_html($factura->numero_factura) . '</strong> ';
        $mensaje .= 'vence en <strong>' . $dias_restantes . ' dias</strong> (' . date('d/m/Y', strtotime($factura->fecha_vencimiento)) . ').</p>';
        $mensaje .= '<p>Importe pendiente: <strong>' . number_format($pendiente, 2, ',', '.') . ' ' . $moneda . '</strong></p>';

        if (!empty($configuracion['cuenta_bancaria'])) {
            $mensaje .= '<p><strong>Datos bancarios:</strong><br>' . nl2br(esc_html($configuracion['cuenta_bancaria'])) . '</p>';
        }

        $mensaje .= '<p>Si ya ha realizado el pago, ignore este mensaje.</p>';
        $mensaje .= '<p>Atentamente,<br>' . esc_html($configuracion['empresa_nombre']) . '</p>';
        $mensaje .= '</body></html>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $configuracion['empresa_nombre'] . ' <' . $configuracion['empresa_email'] . '>',
        ];

        wp_mail($factura->cliente_email, $asunto, $mensaje, $headers);

        do_action('flavor_factura_recordatorio_enviado', $factura->id, $dias_restantes);
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Listar facturas
     */
    public function ajax_listar_facturas() {
        check_ajax_referer('flavor_facturas_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $filtros = $_POST['filtros'] ?? [];
        $resultado = $this->listar_facturas($filtros);

        wp_send_json_success($resultado);
    }

    /**
     * AJAX: Crear factura
     */
    public function ajax_crear_factura() {
        check_ajax_referer('flavor_facturas_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $datos = [
            'cliente_nombre' => sanitize_text_field($_POST['cliente_nombre'] ?? ''),
            'cliente_nif' => sanitize_text_field($_POST['cliente_nif'] ?? ''),
            'cliente_direccion' => sanitize_textarea_field($_POST['cliente_direccion'] ?? ''),
            'cliente_email' => sanitize_email($_POST['cliente_email'] ?? ''),
            'fecha_emision' => sanitize_text_field($_POST['fecha_emision'] ?? ''),
            'fecha_vencimiento' => sanitize_text_field($_POST['fecha_vencimiento'] ?? ''),
            'lineas' => $_POST['lineas'] ?? [],
            'observaciones' => sanitize_textarea_field($_POST['observaciones'] ?? ''),
            'metodo_pago' => sanitize_text_field($_POST['metodo_pago'] ?? 'transferencia'),
        ];

        $resultado = $this->crear_factura($datos);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'factura_id' => $resultado,
            'message' => 'Factura creada correctamente',
        ]);
    }

    /**
     * AJAX: Generar PDF
     */
    public function ajax_generar_pdf() {
        check_ajax_referer('flavor_facturas_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $factura_id = absint($_POST['factura_id'] ?? 0);
        $resultado = $this->generar_pdf($factura_id);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'pdf_url' => $resultado['url'],
            'pdf_nombre' => $resultado['nombre'],
        ]);
    }

    /**
     * AJAX: Registrar pago
     */
    public function ajax_registrar_pago() {
        check_ajax_referer('flavor_facturas_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $datos = [
            'factura_id' => absint($_POST['factura_id'] ?? 0),
            'importe' => floatval($_POST['importe'] ?? 0),
            'fecha_pago' => sanitize_text_field($_POST['fecha_pago'] ?? ''),
            'metodo_pago' => sanitize_text_field($_POST['metodo_pago'] ?? ''),
            'referencia' => sanitize_text_field($_POST['referencia'] ?? ''),
        ];

        $resultado = $this->registrar_pago($datos);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'pago_id' => $resultado,
            'message' => 'Pago registrado correctamente',
        ]);
    }

    /**
     * AJAX: Enviar email
     */
    public function ajax_enviar_email() {
        check_ajax_referer('flavor_facturas_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $factura_id = absint($_POST['factura_id'] ?? 0);
        $email = sanitize_email($_POST['email'] ?? '');

        if (!$email) {
            wp_send_json_error(['message' => 'Email invalido']);
        }

        $opciones = [
            'asunto' => sanitize_text_field($_POST['asunto'] ?? ''),
            'mensaje' => wp_kses_post($_POST['mensaje'] ?? ''),
        ];

        $resultado = $this->enviar_factura_email($factura_id, $email, $opciones);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        if ($resultado) {
            wp_send_json_success(['message' => 'Email enviado correctamente']);
        } else {
            wp_send_json_error(['message' => 'Error al enviar email']);
        }
    }

    /**
     * AJAX: Cancelar factura
     */
    public function ajax_cancelar_factura() {
        check_ajax_referer('flavor_facturas_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $factura_id = absint($_POST['factura_id'] ?? 0);
        $resultado = $this->actualizar_estado_factura($factura_id, 'cancelada');

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Factura cancelada']);
    }

    /**
     * AJAX: Estadisticas
     */
    public function ajax_estadisticas() {
        check_ajax_referer('flavor_facturas_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $periodo = sanitize_text_field($_POST['periodo'] ?? 'mes');
        $estadisticas = $this->obtener_estadisticas($periodo);

        wp_send_json_success($estadisticas);
    }

    /**
     * Obtiene estadisticas de facturacion
     */
    public function obtener_estadisticas($periodo = 'mes') {
        global $wpdb;

        switch ($periodo) {
            case 'año':
                $fecha_desde = date('Y-01-01');
                break;
            case 'trimestre':
                $trimestre = ceil(date('n') / 3);
                $mes_inicio = (($trimestre - 1) * 3) + 1;
                $fecha_desde = date('Y-' . str_pad($mes_inicio, 2, '0', STR_PAD_LEFT) . '-01');
                break;
            default:
                $fecha_desde = date('Y-m-01');
        }

        $total_facturado = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM {$this->tablas['facturas']}
            WHERE estado NOT IN ('borrador', 'cancelada') AND fecha_emision >= %s",
            $fecha_desde
        ));

        $total_cobrado = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total_pagado), 0) FROM {$this->tablas['facturas']}
            WHERE fecha_emision >= %s",
            $fecha_desde
        ));

        $total_pendiente = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total - total_pagado), 0) FROM {$this->tablas['facturas']}
            WHERE estado IN ('emitida', 'parcial') AND fecha_emision >= %s",
            $fecha_desde
        ));

        $total_vencido = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total - total_pagado), 0) FROM {$this->tablas['facturas']}
            WHERE estado IN ('emitida', 'parcial', 'vencida')
            AND fecha_vencimiento < CURDATE()
            AND fecha_emision >= %s",
            $fecha_desde
        ));

        $numero_facturas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tablas['facturas']}
            WHERE estado NOT IN ('borrador', 'cancelada') AND fecha_emision >= %s",
            $fecha_desde
        ));

        $por_estado = $wpdb->get_results($wpdb->prepare(
            "SELECT estado, COUNT(*) as cantidad, SUM(total) as importe
            FROM {$this->tablas['facturas']}
            WHERE fecha_emision >= %s
            GROUP BY estado",
            $fecha_desde
        ), OBJECT_K);

        return [
            'periodo' => $periodo,
            'fecha_desde' => $fecha_desde,
            'total_facturado' => floatval($total_facturado),
            'total_cobrado' => floatval($total_cobrado),
            'total_pendiente' => floatval($total_pendiente),
            'total_vencido' => floatval($total_vencido),
            'numero_facturas' => intval($numero_facturas),
            'por_estado' => $por_estado,
        ];
    }

    // =========================================================================
    // REST API HANDLERS
    // =========================================================================

    /**
     * REST: Listar facturas
     */
    public function rest_listar_facturas($request) {
        $argumentos = [
            'estado' => $request->get_param('estado'),
            'cliente_id' => $request->get_param('cliente_id'),
            'desde' => $request->get_param('desde'),
            'hasta' => $request->get_param('hasta'),
            'pagina' => $request->get_param('pagina') ?: 1,
            'por_pagina' => $request->get_param('por_pagina') ?: 20,
        ];

        return rest_ensure_response($this->listar_facturas($argumentos));
    }

    /**
     * REST: Crear factura
     */
    public function rest_crear_factura($request) {
        $datos = $request->get_json_params();
        $resultado = $this->crear_factura($datos);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response(['error' => $resultado->get_error_message()], 400);
        }

        return rest_ensure_response([
            'factura_id' => $resultado,
            'factura' => $this->obtener_factura($resultado),
        ]);
    }

    /**
     * REST: Obtener factura
     */
    public function rest_obtener_factura($request) {
        $factura_id = $request->get_param('id');
        $factura = $this->obtener_factura($factura_id);

        if (!$factura) {
            return new WP_REST_Response(['error' => 'Factura no encontrada'], 404);
        }

        return rest_ensure_response($factura);
    }

    /**
     * REST: Actualizar factura
     */
    public function rest_actualizar_factura($request) {
        $factura_id = $request->get_param('id');
        $datos = $request->get_json_params();

        if (isset($datos['estado'])) {
            $resultado = $this->actualizar_estado_factura($factura_id, $datos['estado']);

            if (is_wp_error($resultado)) {
                return new WP_REST_Response(['error' => $resultado->get_error_message()], 400);
            }
        }

        return rest_ensure_response($this->obtener_factura($factura_id));
    }

    /**
     * REST: Eliminar factura
     */
    public function rest_eliminar_factura($request) {
        $factura_id = $request->get_param('id');
        $factura = $this->obtener_factura($factura_id);

        if (!$factura) {
            return new WP_REST_Response(['error' => 'Factura no encontrada'], 404);
        }

        if ($factura->estado !== 'borrador') {
            return new WP_REST_Response(['error' => 'Solo se pueden eliminar facturas en borrador'], 400);
        }

        global $wpdb;
        $wpdb->delete($this->tablas['lineas'], ['factura_id' => $factura_id]);
        $wpdb->delete($this->tablas['pagos'], ['factura_id' => $factura_id]);
        $wpdb->delete($this->tablas['facturas'], ['id' => $factura_id]);

        return rest_ensure_response(['deleted' => true]);
    }

    /**
     * REST: Generar PDF
     */
    public function rest_generar_pdf($request) {
        $factura_id = $request->get_param('id');
        $resultado = $this->generar_pdf($factura_id);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response(['error' => $resultado->get_error_message()], 400);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * REST: Listar pagos
     */
    public function rest_listar_pagos($request) {
        $factura_id = $request->get_param('id');
        $pagos = $this->obtener_pagos_factura($factura_id);

        return rest_ensure_response($pagos);
    }

    /**
     * REST: Registrar pago
     */
    public function rest_registrar_pago($request) {
        $factura_id = $request->get_param('id');
        $datos = $request->get_json_params();
        $datos['factura_id'] = $factura_id;

        $resultado = $this->registrar_pago($datos);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response(['error' => $resultado->get_error_message()], 400);
        }

        return rest_ensure_response([
            'pago_id' => $resultado,
            'factura' => $this->obtener_factura($factura_id),
        ]);
    }

    /**
     * REST: Estadisticas
     */
    public function rest_estadisticas($request) {
        $periodo = $request->get_param('periodo') ?: 'mes';
        return rest_ensure_response($this->obtener_estadisticas($periodo));
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Mis facturas
     */
    public function shortcode_mis_facturas($atts) {
        if (!is_user_logged_in()) {
            return '<div class="facturas-mensaje facturas-mensaje-warning">Debes iniciar sesion para ver tus facturas.</div>';
        }

        $atts = shortcode_atts([
            'limite' => 20,
            'estado' => '',
        ], $atts);

        $usuario_id = get_current_user_id();

        $argumentos = [
            'cliente_id' => $usuario_id,
            'por_pagina' => $atts['limite'],
        ];

        if ($atts['estado']) {
            $argumentos['estado'] = $atts['estado'];
        }

        $resultado = $this->listar_facturas($argumentos);

        ob_start();
        ?>
        <div class="facturas-container">
            <div class="facturas-header">
                <h2 class="facturas-title"><?php _e('Mis Facturas', 'flavor-chat-ia'); ?></h2>
            </div>

            <div class="facturas-filtros">
                <div class="facturas-filtro-grupo">
                    <label class="facturas-filtro-label"><?php _e('Estado', 'flavor-chat-ia'); ?></label>
                    <select id="filtro-estado" class="facturas-select facturas-filtro-select">
                        <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                        <option value="emitida"><?php _e('Pendientes', 'flavor-chat-ia'); ?></option>
                        <option value="pagada"><?php _e('Pagadas', 'flavor-chat-ia'); ?></option>
                        <option value="vencida"><?php _e('Vencidas', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </div>

            <div class="facturas-lista-container">
                <?php if (empty($resultado['facturas'])): ?>
                    <div class="facturas-empty">
                        <div class="facturas-empty-icon">📋</div>
                        <p class="facturas-empty-texto"><?php _e('No tienes facturas', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="facturas-table-wrapper">
                        <table class="facturas-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Numero', 'flavor-chat-ia'); ?></th>
                                    <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                                    <th><?php _e('Total', 'flavor-chat-ia'); ?></th>
                                    <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                                    <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultado['facturas'] as $factura): ?>
                                    <tr>
                                        <td class="numero-factura"><?php echo esc_html($factura->numero_factura); ?></td>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($factura->fecha_emision)); ?></td>
                                        <td class="importe"><?php echo number_format($factura->total, 2, ',', '.'); ?> <?php echo esc_html($this->get_setting('simbolo_moneda')); ?></td>
                                        <td><span class="facturas-estado facturas-estado-<?php echo esc_attr($factura->estado); ?>"><?php echo esc_html(ucfirst($factura->estado)); ?></span></td>
                                        <td>
                                            <a href="?factura_id=<?php echo $factura->id; ?>" class="facturas-btn facturas-btn-sm facturas-btn-secondary"><?php _e('Ver', 'flavor-chat-ia'); ?></a>
                                            <button class="facturas-btn facturas-btn-sm facturas-btn-primary btn-descargar-pdf" data-factura-id="<?php echo $factura->id; ?>"><?php _e('PDF', 'flavor-chat-ia'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle factura
     */
    public function shortcode_detalle_factura($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $factura_id = $atts['id'] ?: (isset($_GET['factura_id']) ? absint($_GET['factura_id']) : 0);

        if (!$factura_id) {
            return '<div class="facturas-mensaje facturas-mensaje-error">Factura no especificada.</div>';
        }

        $factura = $this->obtener_factura($factura_id);

        if (!$factura) {
            return '<div class="facturas-mensaje facturas-mensaje-error">Factura no encontrada.</div>';
        }

        // Verificar permisos
        if (!current_user_can('manage_options') && $factura->cliente_id != get_current_user_id()) {
            return '<div class="facturas-mensaje facturas-mensaje-error">No tienes permiso para ver esta factura.</div>';
        }

        $moneda = $this->get_setting('simbolo_moneda');
        $pendiente = $factura->total - $factura->total_pagado;

        ob_start();
        ?>
        <div class="facturas-container">
            <div class="factura-detalle">
                <div class="factura-detalle-header">
                    <div>
                        <div class="factura-numero"><?php echo esc_html($factura->numero_factura); ?></div>
                        <div class="factura-fecha"><?php echo date_i18n(get_option('date_format'), strtotime($factura->fecha_emision)); ?></div>
                    </div>
                    <span class="facturas-estado facturas-estado-<?php echo esc_attr($factura->estado); ?>"><?php echo esc_html(ucfirst($factura->estado)); ?></span>
                </div>

                <div class="factura-detalle-body">
                    <div class="factura-info-grid">
                        <div class="factura-info-bloque">
                            <div class="factura-info-titulo"><?php _e('Cliente', 'flavor-chat-ia'); ?></div>
                            <div class="factura-info-valor">
                                <strong><?php echo esc_html($factura->cliente_nombre); ?></strong><br>
                                <?php if ($factura->cliente_nif): ?>
                                    NIF: <?php echo esc_html($factura->cliente_nif); ?><br>
                                <?php endif; ?>
                                <?php echo nl2br(esc_html($factura->cliente_direccion)); ?>
                            </div>
                        </div>

                        <div class="factura-info-bloque">
                            <div class="factura-info-titulo"><?php _e('Datos Factura', 'flavor-chat-ia'); ?></div>
                            <div class="factura-info-valor">
                                <strong><?php _e('Vencimiento:', 'flavor-chat-ia'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($factura->fecha_vencimiento)); ?><br>
                                <strong><?php _e('Metodo pago:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html(ucfirst($factura->metodo_pago)); ?>
                            </div>
                        </div>
                    </div>

                    <div class="factura-lineas">
                        <div class="factura-lineas-titulo"><?php _e('Conceptos', 'flavor-chat-ia'); ?></div>
                        <div class="facturas-table-wrapper">
                            <table class="facturas-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Concepto', 'flavor-chat-ia'); ?></th>
                                        <th><?php _e('Cantidad', 'flavor-chat-ia'); ?></th>
                                        <th><?php _e('Precio', 'flavor-chat-ia'); ?></th>
                                        <th><?php _e('IVA', 'flavor-chat-ia'); ?></th>
                                        <th><?php _e('Total', 'flavor-chat-ia'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($factura->lineas as $linea): ?>
                                        <tr>
                                            <td>
                                                <?php echo esc_html($linea->concepto); ?>
                                                <?php if ($linea->descripcion): ?>
                                                    <br><small><?php echo esc_html($linea->descripcion); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format($linea->cantidad, 2, ',', '.'); ?></td>
                                            <td><?php echo number_format($linea->precio_unitario, 2, ',', '.'); ?> <?php echo esc_html($moneda); ?></td>
                                            <td><?php echo number_format($linea->iva_porcentaje, 0); ?>%</td>
                                            <td class="importe"><?php echo number_format($linea->total_linea, 2, ',', '.'); ?> <?php echo esc_html($moneda); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="factura-totales">
                        <div class="factura-total-fila">
                            <span><?php _e('Base imponible', 'flavor-chat-ia'); ?></span>
                            <span><?php echo number_format($factura->base_imponible, 2, ',', '.'); ?> <?php echo esc_html($moneda); ?></span>
                        </div>
                        <div class="factura-total-fila">
                            <span><?php _e('IVA', 'flavor-chat-ia'); ?></span>
                            <span><?php echo number_format($factura->total_iva, 2, ',', '.'); ?> <?php echo esc_html($moneda); ?></span>
                        </div>
                        <?php if ($factura->total_retencion > 0): ?>
                            <div class="factura-total-fila">
                                <span><?php _e('Retencion', 'flavor-chat-ia'); ?></span>
                                <span>-<?php echo number_format($factura->total_retencion, 2, ',', '.'); ?> <?php echo esc_html($moneda); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="factura-total-fila total-final">
                            <span><?php _e('TOTAL', 'flavor-chat-ia'); ?></span>
                            <span><?php echo number_format($factura->total, 2, ',', '.'); ?> <?php echo esc_html($moneda); ?></span>
                        </div>
                        <?php if ($factura->total_pagado > 0): ?>
                            <div class="factura-total-fila">
                                <span><?php _e('Pagado', 'flavor-chat-ia'); ?></span>
                                <span style="color: var(--facturas-success);">-<?php echo number_format($factura->total_pagado, 2, ',', '.'); ?> <?php echo esc_html($moneda); ?></span>
                            </div>
                            <div class="factura-total-fila">
                                <span><strong><?php _e('Pendiente', 'flavor-chat-ia'); ?></strong></span>
                                <span><strong><?php echo number_format($pendiente, 2, ',', '.'); ?> <?php echo esc_html($moneda); ?></strong></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($factura->observaciones): ?>
                        <div style="margin-top: 20px;">
                            <strong><?php _e('Observaciones:', 'flavor-chat-ia'); ?></strong>
                            <p><?php echo nl2br(esc_html($factura->observaciones)); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="factura-acciones">
                        <button class="facturas-btn facturas-btn-primary btn-descargar-pdf" data-factura-id="<?php echo $factura->id; ?>">
                            <?php _e('Descargar PDF', 'flavor-chat-ia'); ?>
                        </button>

                        <?php if (current_user_can('manage_options')): ?>
                            <button class="facturas-btn facturas-btn-secondary btn-enviar-email" data-factura-id="<?php echo $factura->id; ?>">
                                <?php _e('Enviar por Email', 'flavor-chat-ia'); ?>
                            </button>

                            <?php if ($pendiente > 0 && in_array($factura->estado, ['emitida', 'parcial'])): ?>
                                <button class="facturas-btn facturas-btn-success btn-registrar-pago" data-factura-id="<?php echo $factura->id; ?>" data-pendiente="<?php echo $pendiente; ?>">
                                    <?php _e('Registrar Pago', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ($factura->estado !== 'cancelada'): ?>
                                <button class="facturas-btn facturas-btn-danger btn-cancelar-factura" data-factura-id="<?php echo $factura->id; ?>">
                                    <?php _e('Cancelar Factura', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div id="facturas-modales-container"></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Pagar factura
     */
    public function shortcode_pagar_factura($atts) {
        return '<div class="facturas-mensaje facturas-mensaje-info">Sistema de pago online proximamente disponible.</div>';
    }

    /**
     * Shortcode: Historial pagos
     */
    public function shortcode_historial_pagos($atts) {
        $atts = shortcode_atts([
            'factura_id' => 0,
        ], $atts);

        $factura_id = $atts['factura_id'] ?: (isset($_GET['factura_id']) ? absint($_GET['factura_id']) : 0);

        if (!$factura_id) {
            return '';
        }

        $pagos = $this->obtener_pagos_factura($factura_id);
        $moneda = $this->get_setting('simbolo_moneda');

        if (empty($pagos)) {
            return '<div class="facturas-empty"><p>' . __('No hay pagos registrados', 'flavor-chat-ia') . '</p></div>';
        }

        ob_start();
        ?>
        <div class="historial-pagos">
            <h3><?php _e('Historial de Pagos', 'flavor-chat-ia'); ?></h3>
            <?php foreach ($pagos as $pago): ?>
                <div class="historial-pagos-item">
                    <div class="pago-info">
                        <span class="pago-fecha"><?php echo date_i18n(get_option('date_format'), strtotime($pago->fecha_pago)); ?></span>
                        <span class="pago-metodo"><?php echo esc_html(ucfirst($pago->metodo_pago)); ?></span>
                        <?php if ($pago->referencia): ?>
                            <small>Ref: <?php echo esc_html($pago->referencia); ?></small>
                        <?php endif; ?>
                    </div>
                    <span class="pago-importe">+<?php echo number_format($pago->importe, 2, ',', '.'); ?> <?php echo esc_html($moneda); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Nueva factura (admin)
     */
    public function shortcode_nueva_factura($atts) {
        if (!current_user_can('edit_posts')) {
            return '<div class="facturas-mensaje facturas-mensaje-error">No tienes permisos para crear facturas.</div>';
        }

        ob_start();
        ?>
        <div class="facturas-container">
            <div class="facturas-header">
                <h2 class="facturas-title"><?php _e('Nueva Factura', 'flavor-chat-ia'); ?></h2>
            </div>

            <form id="form-nueva-factura" class="factura-formulario">
                <?php wp_nonce_field('flavor_facturas_crear', 'factura_nonce'); ?>

                <div class="factura-info-grid">
                    <div class="factura-info-bloque">
                        <div class="factura-info-titulo"><?php _e('Datos del Cliente', 'flavor-chat-ia'); ?></div>

                        <div class="facturas-form-grupo">
                            <label class="facturas-form-label required"><?php _e('Nombre', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="cliente_nombre" class="facturas-form-input" required>
                        </div>

                        <div class="facturas-form-grupo">
                            <label class="facturas-form-label"><?php _e('NIF/CIF', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="cliente_nif" class="facturas-form-input">
                        </div>

                        <div class="facturas-form-grupo">
                            <label class="facturas-form-label"><?php _e('Direccion', 'flavor-chat-ia'); ?></label>
                            <textarea name="cliente_direccion" class="facturas-form-input" rows="3"></textarea>
                        </div>

                        <div class="facturas-form-grupo">
                            <label class="facturas-form-label"><?php _e('Email', 'flavor-chat-ia'); ?></label>
                            <input type="email" name="cliente_email" class="facturas-form-input">
                        </div>
                    </div>

                    <div class="factura-info-bloque">
                        <div class="factura-info-titulo"><?php _e('Datos de la Factura', 'flavor-chat-ia'); ?></div>

                        <div class="facturas-form-grupo">
                            <label class="facturas-form-label required"><?php _e('Fecha emision', 'flavor-chat-ia'); ?></label>
                            <input type="date" name="fecha_emision" class="facturas-form-input" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="facturas-form-grupo">
                            <label class="facturas-form-label"><?php _e('Fecha vencimiento', 'flavor-chat-ia'); ?></label>
                            <input type="date" name="fecha_vencimiento" class="facturas-form-input" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        </div>

                        <div class="facturas-form-grupo">
                            <label class="facturas-form-label"><?php _e('Metodo de pago', 'flavor-chat-ia'); ?></label>
                            <select name="metodo_pago" class="facturas-form-input">
                                <option value="transferencia"><?php _e('Transferencia', 'flavor-chat-ia'); ?></option>
                                <option value="efectivo"><?php _e('Efectivo', 'flavor-chat-ia'); ?></option>
                                <option value="tarjeta"><?php _e('Tarjeta', 'flavor-chat-ia'); ?></option>
                                <option value="bizum"><?php _e('Bizum', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div class="facturas-form-grupo">
                            <label class="facturas-form-label"><?php _e('Retencion IRPF', 'flavor-chat-ia'); ?></label>
                            <select name="retencion_porcentaje" id="retencion-porcentaje" class="facturas-form-input">
                                <option value="0"><?php _e('Sin retencion', 'flavor-chat-ia'); ?></option>
                                <option value="7">7%</option>
                                <option value="15">15%</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="factura-lineas" style="margin-top: 24px;">
                    <div class="factura-lineas-titulo">
                        <?php _e('Lineas de Factura', 'flavor-chat-ia'); ?>
                        <button type="button" class="facturas-btn facturas-btn-sm facturas-btn-primary btn-agregar-linea"><?php _e('+ Agregar linea', 'flavor-chat-ia'); ?></button>
                    </div>

                    <div class="factura-lineas-container">
                        <div class="factura-linea-item" data-indice="0">
                            <div class="linea-grid" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto; gap: 8px; align-items: center;">
                                <input type="text" name="lineas[0][concepto]" class="facturas-form-input" placeholder="<?php _e('Concepto', 'flavor-chat-ia'); ?>" required>
                                <input type="number" name="lineas[0][cantidad]" class="facturas-form-input linea-cantidad" value="1" min="0.01" step="0.01" required>
                                <input type="number" name="lineas[0][precio]" class="facturas-form-input linea-precio" placeholder="0.00" min="0" step="0.01" required>
                                <input type="number" name="lineas[0][descuento]" class="facturas-form-input linea-descuento" value="0" min="0" max="100" step="0.01">
                                <select name="lineas[0][iva]" class="facturas-form-input linea-iva">
                                    <option value="21">21%</option>
                                    <option value="10">10%</option>
                                    <option value="4">4%</option>
                                    <option value="0">0%</option>
                                </select>
                                <span class="linea-total-valor">0.00</span>
                                <button type="button" class="facturas-btn facturas-btn-danger facturas-btn-sm btn-eliminar-linea">X</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="factura-totales" style="margin-top: 24px;">
                    <div class="factura-total-fila">
                        <span><?php _e('Base imponible', 'flavor-chat-ia'); ?></span>
                        <span id="resumen-base">0.00 <?php echo esc_html($this->get_setting('simbolo_moneda')); ?></span>
                    </div>
                    <div class="factura-total-fila">
                        <span><?php _e('IVA', 'flavor-chat-ia'); ?></span>
                        <span id="resumen-iva">0.00 <?php echo esc_html($this->get_setting('simbolo_moneda')); ?></span>
                    </div>
                    <div class="factura-total-fila">
                        <span><?php _e('Retencion', 'flavor-chat-ia'); ?></span>
                        <span id="resumen-retencion">0.00 <?php echo esc_html($this->get_setting('simbolo_moneda')); ?></span>
                    </div>
                    <div class="factura-total-fila total-final">
                        <span><?php _e('TOTAL', 'flavor-chat-ia'); ?></span>
                        <span id="resumen-total">0.00 <?php echo esc_html($this->get_setting('simbolo_moneda')); ?></span>
                    </div>

                    <input type="hidden" name="base_imponible" value="0">
                    <input type="hidden" name="total_iva" value="0">
                    <input type="hidden" name="total_retencion" value="0">
                    <input type="hidden" name="total" value="0">
                </div>

                <div class="facturas-form-grupo" style="margin-top: 24px;">
                    <label class="facturas-form-label"><?php _e('Observaciones', 'flavor-chat-ia'); ?></label>
                    <textarea name="observaciones" class="facturas-form-input" rows="3"></textarea>
                </div>

                <div class="factura-acciones">
                    <button type="submit" class="facturas-btn facturas-btn-primary"><?php _e('Crear Factura', 'flavor-chat-ia'); ?></button>
                    <button type="submit" name="emitir" value="1" class="facturas-btn facturas-btn-success"><?php _e('Crear y Emitir', 'flavor-chat-ia'); ?></button>
                </div>
            </form>
        </div>
        <div id="facturas-modales-container"></div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // MODULO BASE METHODS
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_facturas' => [
                'description' => 'Listar facturas con filtros',
                'params' => ['estado', 'cliente_id', 'desde', 'hasta', 'limite'],
            ],
            'ver_factura' => [
                'description' => 'Ver detalles de una factura',
                'params' => ['factura_id'],
            ],
            'crear_factura' => [
                'description' => 'Crear nueva factura',
                'params' => ['cliente_nombre', 'cliente_nif', 'lineas', 'fecha_emision'],
            ],
            'actualizar_estado' => [
                'description' => 'Cambiar estado de factura',
                'params' => ['factura_id', 'nuevo_estado'],
            ],
            'registrar_pago' => [
                'description' => 'Registrar pago de factura',
                'params' => ['factura_id', 'importe', 'metodo_pago'],
            ],
            'generar_pdf' => [
                'description' => 'Generar PDF de factura',
                'params' => ['factura_id'],
            ],
            'enviar_email' => [
                'description' => 'Enviar factura por email',
                'params' => ['factura_id', 'email'],
            ],
            'estadisticas' => [
                'description' => 'Obtener estadisticas de facturacion',
                'params' => ['periodo'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Accion no implementada: {$action_name}",
        ];
    }

    /**
     * Accion: Listar facturas
     */
    private function action_listar_facturas($params) {
        if (!current_user_can('read')) {
            return ['success' => false, 'error' => 'Sin permisos'];
        }

        $resultado = $this->listar_facturas($params);

        return [
            'success' => true,
            'total' => $resultado['total'],
            'facturas' => array_map(function($factura) {
                return [
                    'id' => $factura->id,
                    'numero' => $factura->numero_factura,
                    'cliente' => $factura->cliente_nombre,
                    'fecha' => $factura->fecha_emision,
                    'total' => floatval($factura->total),
                    'pagado' => floatval($factura->total_pagado),
                    'estado' => $factura->estado,
                ];
            }, $resultado['facturas']),
        ];
    }

    /**
     * Accion: Ver factura
     */
    private function action_ver_factura($params) {
        if (!current_user_can('read')) {
            return ['success' => false, 'error' => 'Sin permisos'];
        }

        $factura_id = absint($params['factura_id'] ?? 0);
        $factura = $this->obtener_factura($factura_id);

        if (!$factura) {
            return ['success' => false, 'error' => 'Factura no encontrada'];
        }

        return [
            'success' => true,
            'factura' => [
                'id' => $factura->id,
                'numero' => $factura->numero_factura,
                'cliente' => [
                    'nombre' => $factura->cliente_nombre,
                    'nif' => $factura->cliente_nif,
                    'direccion' => $factura->cliente_direccion,
                    'email' => $factura->cliente_email,
                ],
                'fecha_emision' => $factura->fecha_emision,
                'fecha_vencimiento' => $factura->fecha_vencimiento,
                'base_imponible' => floatval($factura->base_imponible),
                'iva' => floatval($factura->total_iva),
                'retencion' => floatval($factura->total_retencion),
                'total' => floatval($factura->total),
                'pagado' => floatval($factura->total_pagado),
                'estado' => $factura->estado,
                'lineas' => array_map(function($linea) {
                    return [
                        'concepto' => $linea->concepto,
                        'cantidad' => floatval($linea->cantidad),
                        'precio' => floatval($linea->precio_unitario),
                        'iva' => floatval($linea->iva_porcentaje),
                        'total' => floatval($linea->total_linea),
                    ];
                }, $factura->lineas),
                'pagos' => array_map(function($pago) {
                    return [
                        'fecha' => $pago->fecha_pago,
                        'importe' => floatval($pago->importe),
                        'metodo' => $pago->metodo_pago,
                    ];
                }, $factura->pagos),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'facturas_listar',
                'description' => 'Lista las facturas del sistema',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => [
                            'type' => 'string',
                            'enum' => ['borrador', 'emitida', 'parcial', 'pagada', 'vencida', 'cancelada'],
                        ],
                        'limite' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'facturas_crear',
                'description' => 'Crea una nueva factura',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'cliente_nombre' => ['type' => 'string'],
                        'cliente_nif' => ['type' => 'string'],
                        'lineas' => ['type' => 'array'],
                    ],
                    'required' => ['cliente_nombre', 'lineas'],
                ],
            ],
            [
                'name' => 'facturas_estadisticas',
                'description' => 'Obtiene estadisticas de facturacion',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'periodo' => [
                            'type' => 'string',
                            'enum' => ['mes', 'trimestre', 'año'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Facturas Comunitarias**

Gestion completa de facturacion para servicios comunitarios con las siguientes funcionalidades:

**Caracteristicas:**
- Creacion y edicion de facturas con multiples lineas
- Generacion automatica de numero de factura
- Soporte para IVA, descuentos y retenciones IRPF
- Generacion de PDF profesional
- Envio automatico por email
- Registro de pagos parciales y totales
- Recordatorios de vencimiento automaticos
- Estadisticas de facturacion

**Estados de factura:**
- Borrador: En preparacion, no enviada
- Emitida: Enviada al cliente, pendiente de pago
- Parcial: Con pagos parciales registrados
- Pagada: Completamente pagada
- Vencida: Fecha de vencimiento superada
- Cancelada: Anulada

**Shortcodes disponibles:**
- [flavor_mis_facturas] - Lista de facturas del usuario
- [flavor_detalle_factura id="X"] - Detalle de factura
- [flavor_historial_pagos factura_id="X"] - Pagos de una factura
- [flavor_nueva_factura] - Formulario crear factura (admin)
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como crear una factura?',
                'respuesta' => 'Usa el shortcode [flavor_nueva_factura] o accede desde el panel de administracion.',
            ],
            [
                'pregunta' => 'Como registrar un pago?',
                'respuesta' => 'Desde el detalle de la factura, pulsa "Registrar Pago" e indica el importe y metodo.',
            ],
            [
                'pregunta' => 'Se pueden enviar facturas por email?',
                'respuesta' => 'Si, desde el detalle de factura puedes enviarla por email con el PDF adjunto.',
            ],
            [
                'pregunta' => 'Como funcionan los recordatorios?',
                'respuesta' => 'Se envian automaticamente antes del vencimiento segun la configuracion (7, 3 y 1 dias antes).',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_form_config($action_name) {
        $configs = [
            'crear_factura' => [
                'title' => __('Crear Nueva Factura', 'flavor-chat-ia'),
                'fields' => [
                    'cliente_nombre' => [
                        'type' => 'text',
                        'label' => __('Nombre del cliente', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'cliente_nif' => [
                        'type' => 'text',
                        'label' => __('NIF/CIF', 'flavor-chat-ia'),
                    ],
                    'fecha_emision' => [
                        'type' => 'date',
                        'label' => __('Fecha emision', 'flavor-chat-ia'),
                        'default' => date('Y-m-d'),
                    ],
                ],
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function get_web_components() {
        return [
            'facturas_lista' => [
                'label' => __('Lista de Facturas', 'flavor-chat-ia'),
                'description' => __('Tabla de facturas con filtros', 'flavor-chat-ia'),
                'category' => 'listings',
                'template' => 'facturas/lista',
            ],
            'facturas_estadisticas' => [
                'label' => __('Estadisticas Facturacion', 'flavor-chat-ia'),
                'description' => __('Resumen y metricas', 'flavor-chat-ia'),
                'category' => 'content',
                'template' => 'facturas/estadisticas',
            ],
        ];
    }
}
