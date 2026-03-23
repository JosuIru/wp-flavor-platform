<?php
/**
 * Modulo de Contabilidad transversal para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Contabilidad_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /** @var string */
    const VERSION = '1.0.0';

    /** @var string */
    private $tabla_movimientos;

    public function __construct() {
        $this->id = 'contabilidad';
        $this->name = 'Contabilidad'; // Translation loaded on init
        $this->description = 'Libro contable transversal para ingresos/gastos con desglose fiscal y comparativas.'; // Translation loaded on init

        global $wpdb;
        $this->tabla_movimientos = $wpdb->prefix . 'flavor_contabilidad_movimientos';

        parent::__construct();
    }

    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe($this->tabla_movimientos);
    }

    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Contabilidad no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
        }

        return '';
    }

    public function is_active() {
        return $this->can_activate();
    }

    protected function get_default_settings() {
        return [
            'moneda' => 'EUR',
            'simbolo_moneda' => '€',
            'permitir_asientos_manual' => true,
            'coste_adquisicion_cliente' => 0,
            'coste_email_por_envio' => 0,
            'coste_nuevo_suscriptor' => 0,
            'categorias_gasto' => ['compras', 'servicios', 'nominas', 'impuestos', 'alquiler', 'suministros', 'marketing', 'otros'],
            'categorias_ingreso' => ['ventas', 'suscripciones', 'servicios', 'subvenciones', 'donaciones', 'otros'],
        ];
    }

    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);

        $this->registrar_en_panel_unificado();

        add_action('flavor_contabilidad_registrar_movimiento', [$this, 'registrar_movimiento_externo'], 10, 1);

        // Integracion nativa con Facturas: desacoplada via eventos ya existentes.
        add_action('flavor_factura_creada', [$this, 'on_factura_creada'], 10, 2);
        add_action('flavor_factura_pago_registrado', [$this, 'on_factura_pago_registrado'], 10, 3);

        // Integracion con Socios (cuotas pagadas).
        add_action('flavor_socios_cuota_pagada', [$this, 'on_socios_cuota_pagada'], 10, 3);

        // Integracion con Reservas.
        add_action('flavor_reserva_creada', [$this, 'on_reserva_creada'], 10, 2);
        add_action('flavor_reserva_cancelada', [$this, 'on_reserva_cancelada'], 10, 2);

        // Integracion con Marketplace.
        add_action('flavor_marketplace_anuncio_vendido', [$this, 'on_marketplace_anuncio_vendido'], 10, 2);

        // Integracion con Crowdfunding.
        add_action('flavor_crowdfunding_aportacion_completada', [$this, 'on_crowdfunding_aportacion_completada'], 10, 2);

        // Integracion con Clientes.
        add_action('flavor_cliente_creado', [$this, 'on_cliente_creado'], 10, 2);

        // Integracion con Email Marketing.
        add_action('flavor_em_campaign_status_changed', [$this, 'on_email_marketing_campaign_status_changed'], 10, 2);
        add_action('flavor_em_subscriber_created', [$this, 'on_email_marketing_subscriber_created'], 10, 2);
    }

    protected function get_admin_config() {
        return [
            'id' => 'contabilidad',
            'label' => __('Contabilidad', 'flavor-chat-ia'),
            'icon' => 'dashicons-chart-pie',
            'capability' => 'manage_options',
            'categoria' => 'economia',
            'paginas' => [
                [
                    'slug' => 'contabilidad-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'contabilidad-movimientos',
                    'titulo' => __('Movimientos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_movimientos'],
                ],
                [
                    'slug' => 'contabilidad-informes',
                    'titulo' => __('Informes', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_informes'],
                ],
                [
                    'slug' => 'contabilidad-config',
                    'titulo' => __('Configuracion', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    public function maybe_create_tables() {
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_movimientos)) {
            $this->create_tables();
        } else {
            // Migración: añadir columna empresa_id si no existe
            $this->maybe_add_empresa_id_columns();
        }
    }

    /**
     * Añade columna empresa_id a la tabla de movimientos (migración multi-empresa)
     */
    private function maybe_add_empresa_id_columns() {
        global $wpdb;

        $columnas = $wpdb->get_col("DESCRIBE {$this->tabla_movimientos}", 0);
        if (!in_array('empresa_id', $columnas)) {
            $wpdb->query("ALTER TABLE {$this->tabla_movimientos} ADD COLUMN empresa_id bigint(20) unsigned DEFAULT NULL AFTER id");
            $wpdb->query("ALTER TABLE {$this->tabla_movimientos} ADD INDEX idx_empresa_id (empresa_id)");
        }
    }

    /**
     * Obtiene el ID de empresa del usuario actual
     *
     * @param int|null $user_id ID del usuario (opcional)
     * @return int|null ID de empresa o null
     */
    public function get_empresa_usuario($user_id = null) {
        $user_id = $user_id ?: get_current_user_id();

        if (class_exists('Flavor_Chat_Empresas_Module')) {
            $empresas_module = Flavor_Chat_Module_Loader::get_instance()->get_module('empresas');
            if ($empresas_module && method_exists($empresas_module, 'get_empresa_actual_usuario')) {
                return $empresas_module->get_empresa_actual_usuario($user_id);
            }
        }

        $empresa_id = get_user_meta($user_id, '_flavor_empresa_actual', true);
        return $empresa_id ? absint($empresa_id) : null;
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->tabla_movimientos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            empresa_id bigint(20) unsigned DEFAULT NULL,
            fecha_movimiento date NOT NULL,
            tipo_movimiento enum('ingreso','gasto','ajuste') NOT NULL DEFAULT 'ingreso',
            estado enum('borrador','confirmado','anulado') NOT NULL DEFAULT 'confirmado',
            concepto varchar(255) NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            subcategoria varchar(100) DEFAULT NULL,
            modulo_origen varchar(100) DEFAULT NULL,
            entidad_tipo varchar(100) DEFAULT NULL,
            entidad_id bigint(20) unsigned DEFAULT NULL,
            referencia_tipo varchar(100) DEFAULT NULL,
            referencia_id bigint(20) unsigned DEFAULT NULL,
            tercero_tipo varchar(100) DEFAULT NULL,
            tercero_id bigint(20) unsigned DEFAULT NULL,
            tercero_nombre varchar(255) DEFAULT NULL,
            moneda varchar(10) NOT NULL DEFAULT 'EUR',
            base_imponible decimal(12,2) NOT NULL DEFAULT 0.00,
            iva_porcentaje decimal(6,2) NOT NULL DEFAULT 0.00,
            iva_importe decimal(12,2) NOT NULL DEFAULT 0.00,
            retencion_porcentaje decimal(6,2) NOT NULL DEFAULT 0.00,
            retencion_importe decimal(12,2) NOT NULL DEFAULT 0.00,
            total decimal(12,2) NOT NULL DEFAULT 0.00,
            metadata longtext DEFAULT NULL,
            clave_unica varchar(191) DEFAULT NULL,
            creado_por bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_clave_unica (clave_unica),
            KEY idx_empresa_id (empresa_id),
            KEY idx_fecha_movimiento (fecha_movimiento),
            KEY idx_tipo_movimiento (tipo_movimiento),
            KEY idx_estado (estado),
            KEY idx_modulo_origen (modulo_origen),
            KEY idx_referencia (referencia_tipo, referencia_id),
            KEY idx_entidad (entidad_tipo, entidad_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function registrar_movimiento_externo($data) {
        if (!is_array($data)) {
            return;
        }

        $this->registrar_movimiento($data);
    }

    private function registrar_movimiento($data) {
        global $wpdb;

        $tipo = sanitize_key((string) ($data['tipo_movimiento'] ?? 'ingreso'));
        if (!in_array($tipo, ['ingreso', 'gasto', 'ajuste'], true)) {
            $tipo = 'ingreso';
        }

        $estado = sanitize_key((string) ($data['estado'] ?? 'confirmado'));
        if (!in_array($estado, ['borrador', 'confirmado', 'anulado'], true)) {
            $estado = 'confirmado';
        }

        // Obtener empresa_id del usuario actual o de los datos proporcionados
        $empresa_id = isset($data['empresa_id']) ? absint($data['empresa_id']) : $this->get_empresa_usuario();

        $base = (float) ($data['base_imponible'] ?? 0);
        $iva_importe = (float) ($data['iva_importe'] ?? 0);
        $retencion_importe = (float) ($data['retencion_importe'] ?? 0);
        $total = isset($data['total']) ? (float) $data['total'] : ($base + $iva_importe - $retencion_importe);

        $fila = [
            'empresa_id' => $empresa_id,
            'fecha_movimiento' => sanitize_text_field((string) ($data['fecha_movimiento'] ?? current_time('Y-m-d'))),
            'tipo_movimiento' => $tipo,
            'estado' => $estado,
            'concepto' => sanitize_text_field((string) ($data['concepto'] ?? 'Movimiento contable')),
            'categoria' => sanitize_text_field((string) ($data['categoria'] ?? 'otros')),
            'subcategoria' => sanitize_text_field((string) ($data['subcategoria'] ?? '')),
            'modulo_origen' => sanitize_key((string) ($data['modulo_origen'] ?? 'manual')),
            'entidad_tipo' => sanitize_key((string) ($data['entidad_tipo'] ?? '')),
            'entidad_id' => absint($data['entidad_id'] ?? 0),
            'referencia_tipo' => sanitize_key((string) ($data['referencia_tipo'] ?? '')),
            'referencia_id' => absint($data['referencia_id'] ?? 0),
            'tercero_tipo' => sanitize_key((string) ($data['tercero_tipo'] ?? '')),
            'tercero_id' => absint($data['tercero_id'] ?? 0),
            'tercero_nombre' => sanitize_text_field((string) ($data['tercero_nombre'] ?? '')),
            'moneda' => sanitize_text_field((string) ($data['moneda'] ?? $this->get_setting('moneda', 'EUR'))),
            'base_imponible' => round($base, 2),
            'iva_porcentaje' => (float) ($data['iva_porcentaje'] ?? 0),
            'iva_importe' => round($iva_importe, 2),
            'retencion_porcentaje' => (float) ($data['retencion_porcentaje'] ?? 0),
            'retencion_importe' => round($retencion_importe, 2),
            'total' => round($total, 2),
            'metadata' => !empty($data['metadata']) ? wp_json_encode($data['metadata']) : null,
            'clave_unica' => !empty($data['clave_unica']) ? sanitize_text_field((string) $data['clave_unica']) : null,
            'creado_por' => get_current_user_id() ?: null,
        ];

        $format = [
            '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%s',
            '%s', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%d',
        ];

        if ($fila['clave_unica']) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->tabla_movimientos} WHERE clave_unica = %s LIMIT 1",
                $fila['clave_unica']
            ));
            if ($exists) {
                return (int) $exists;
            }
        }

        $ok = $wpdb->insert($this->tabla_movimientos, $fila, $format);
        if ($ok === false) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    public function on_factura_creada($factura_id, $datos_factura) {
        global $wpdb;

        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_facturas)) {
            return;
        }

        $factura = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_facturas} WHERE id = %d", absint($factura_id)));
        if (!$factura) {
            return;
        }

        $this->registrar_movimiento([
            'clave_unica' => 'factura_emitida_' . absint($factura_id),
            'fecha_movimiento' => $factura->fecha_emision,
            'tipo_movimiento' => 'ingreso',
            // Evita doble contabilizacion: el ingreso confirmado se reconoce en el cobro.
            'estado' => 'borrador',
            'concepto' => sprintf(__('Factura emitida %s', 'flavor-chat-ia'), (string) $factura->numero_factura),
            'categoria' => 'ventas',
            'modulo_origen' => 'facturas',
            'entidad_tipo' => 'factura',
            'entidad_id' => absint($factura->id),
            'referencia_tipo' => 'factura',
            'referencia_id' => absint($factura->id),
            'tercero_tipo' => (string) ($factura->cliente_tipo ?? 'cliente'),
            'tercero_id' => absint($factura->cliente_id),
            'tercero_nombre' => (string) $factura->cliente_nombre,
            'base_imponible' => (float) $factura->base_imponible,
            'iva_importe' => (float) $factura->total_iva,
            'retencion_importe' => (float) ($factura->total_retencion ?? 0),
            'total' => (float) $factura->total,
            'metadata' => [
                'numero_factura' => $factura->numero_factura,
                'estado_factura' => $factura->estado,
            ],
        ]);
    }

    public function on_factura_pago_registrado($pago_id, $factura_id, $importe) {
        global $wpdb;

        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_facturas)) {
            return;
        }

        $factura = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_facturas} WHERE id = %d", absint($factura_id)));
        if (!$factura) {
            return;
        }

        $this->registrar_movimiento([
            'clave_unica' => 'factura_pago_' . absint($pago_id),
            'fecha_movimiento' => current_time('Y-m-d'),
            'tipo_movimiento' => 'ingreso',
            'estado' => 'confirmado',
            'concepto' => sprintf(__('Cobro factura %s', 'flavor-chat-ia'), (string) $factura->numero_factura),
            'categoria' => 'cobros',
            'subcategoria' => 'facturas',
            'modulo_origen' => 'facturas',
            'entidad_tipo' => 'pago_factura',
            'entidad_id' => absint($pago_id),
            'referencia_tipo' => 'factura',
            'referencia_id' => absint($factura->id),
            'tercero_tipo' => (string) ($factura->cliente_tipo ?? 'cliente'),
            'tercero_id' => absint($factura->cliente_id),
            'tercero_nombre' => (string) $factura->cliente_nombre,
            'base_imponible' => (float) $importe,
            'iva_importe' => 0,
            'retencion_importe' => 0,
            'total' => (float) $importe,
        ]);
    }

    /**
     * Integra pagos de cuotas del modulo socios.
     *
     * @param int $cuota_id
     * @param int $socio_id
     * @param int $transaccion_id
     * @return void
     */
    public function on_socios_cuota_pagada($cuota_id, $socio_id, $transaccion_id) {
        global $wpdb;

        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
        $tabla_transacciones = $wpdb->prefix . 'flavor_socios_transacciones';
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_cuotas)) {
            return;
        }

        $cuota = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_cuotas} WHERE id = %d",
            absint($cuota_id)
        ));
        if (!$cuota) {
            return;
        }

        $transaccion = null;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_transacciones) && $transaccion_id) {
            $transaccion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_transacciones} WHERE id = %d",
                absint($transaccion_id)
            ));
        }

        $socio = null;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_socios) && $socio_id) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_socios} WHERE id = %d",
                absint($socio_id)
            ));
        }

        $tercero_nombre = '';
        if ($socio && !empty($socio->nombre_completo)) {
            $tercero_nombre = (string) $socio->nombre_completo;
        } elseif ($socio && !empty($socio->nombre)) {
            $tercero_nombre = (string) $socio->nombre;
        } elseif ($socio && !empty($socio->usuario_id)) {
            $user = get_userdata((int) $socio->usuario_id);
            if ($user) {
                $tercero_nombre = (string) $user->display_name;
            }
        }

        $importe = 0.0;
        if ($transaccion && isset($transaccion->importe)) {
            $importe = (float) $transaccion->importe;
        } elseif (isset($cuota->importe_pagado) && (float) $cuota->importe_pagado > 0) {
            $importe = (float) $cuota->importe_pagado;
        } elseif (isset($cuota->importe)) {
            $importe = (float) $cuota->importe;
        }

        $fecha = current_time('Y-m-d');
        if (!empty($cuota->fecha_pago)) {
            $fecha = date('Y-m-d', strtotime((string) $cuota->fecha_pago));
        } elseif ($transaccion && !empty($transaccion->fecha_completado)) {
            $fecha = date('Y-m-d', strtotime((string) $transaccion->fecha_completado));
        }

        $concepto = __('Cobro de cuota de socio', 'flavor-chat-ia');
        if (!empty($cuota->concepto)) {
            $concepto = sprintf(
                __('Cobro cuota socio: %s', 'flavor-chat-ia'),
                (string) $cuota->concepto
            );
        }

        $this->registrar_movimiento([
            'clave_unica' => 'socios_cuota_pagada_' . absint($cuota_id) . '_' . absint($transaccion_id),
            'fecha_movimiento' => $fecha,
            'tipo_movimiento' => 'ingreso',
            'estado' => 'confirmado',
            'concepto' => $concepto,
            'categoria' => 'suscripciones',
            'subcategoria' => 'cuotas_socios',
            'modulo_origen' => 'socios',
            'entidad_tipo' => 'cuota_socio',
            'entidad_id' => absint($cuota_id),
            'referencia_tipo' => 'transaccion_socio',
            'referencia_id' => absint($transaccion_id),
            'tercero_tipo' => 'socio',
            'tercero_id' => absint($socio_id),
            'tercero_nombre' => $tercero_nombre,
            'base_imponible' => $importe,
            'iva_importe' => 0,
            'retencion_importe' => 0,
            'total' => $importe,
            'metadata' => [
                'cuota_tipo' => (string) ($cuota->tipo ?? 'cuota'),
                'periodo' => (string) ($cuota->periodo ?? ''),
                'metodo_pago' => (string) ($cuota->metodo_pago ?? ''),
            ],
        ]);
    }

    /**
     * Integra reservas con contabilidad cuando incluyen importe.
     *
     * @param int   $reserva_id
     * @param array $datos
     * @return void
     */
    public function on_reserva_creada($reserva_id, $datos = []) {
        $importe = isset($datos['importe']) ? (float) $datos['importe'] : 0.0;
        if ($importe <= 0) {
            return;
        }

        $fecha = current_time('Y-m-d');
        if (!empty($datos['fecha_reserva'])) {
            $fecha = sanitize_text_field((string) $datos['fecha_reserva']);
        } elseif (!empty($datos['fecha_inicio'])) {
            $fecha = date('Y-m-d', strtotime((string) $datos['fecha_inicio']));
        }

        $nombre_cliente = '';
        if (!empty($datos['nombre_cliente'])) {
            $nombre_cliente = sanitize_text_field((string) $datos['nombre_cliente']);
        } elseif (!empty($datos['usuario_id'])) {
            $user = get_userdata((int) $datos['usuario_id']);
            if ($user) {
                $nombre_cliente = (string) $user->display_name;
            }
        }

        $this->registrar_movimiento([
            'clave_unica' => 'reserva_cobro_' . absint($reserva_id),
            'fecha_movimiento' => $fecha,
            'tipo_movimiento' => 'ingreso',
            'estado' => 'confirmado',
            'concepto' => sprintf(__('Cobro reserva #%d', 'flavor-chat-ia'), absint($reserva_id)),
            'categoria' => 'servicios',
            'subcategoria' => 'reservas',
            'modulo_origen' => 'reservas',
            'entidad_tipo' => 'reserva',
            'entidad_id' => absint($reserva_id),
            'referencia_tipo' => 'reserva',
            'referencia_id' => absint($reserva_id),
            'tercero_tipo' => 'cliente_reserva',
            'tercero_id' => absint($datos['usuario_id'] ?? $datos['user_id'] ?? 0),
            'tercero_nombre' => $nombre_cliente,
            'base_imponible' => $importe,
            'iva_importe' => 0,
            'retencion_importe' => 0,
            'total' => $importe,
            'metadata' => [
                'origen' => sanitize_text_field((string) ($datos['origen'] ?? 'reservas')),
                'tipo_servicio' => sanitize_text_field((string) ($datos['tipo_servicio'] ?? '')),
                'num_personas' => (int) ($datos['num_personas'] ?? 0),
            ],
        ]);
    }

    /**
     * Integra devoluciones de reservas como gasto.
     *
     * @param int   $reserva_id
     * @param array $datos
     * @return void
     */
    public function on_reserva_cancelada($reserva_id, $datos = []) {
        $devolucion = isset($datos['devolucion']) ? (float) $datos['devolucion'] : 0.0;
        if ($devolucion <= 0) {
            return;
        }

        $fecha = current_time('Y-m-d');
        if (!empty($datos['fecha_reserva'])) {
            $fecha = sanitize_text_field((string) $datos['fecha_reserva']);
        }

        $this->registrar_movimiento([
            'clave_unica' => 'reserva_devolucion_' . absint($reserva_id),
            'fecha_movimiento' => $fecha,
            'tipo_movimiento' => 'gasto',
            'estado' => 'confirmado',
            'concepto' => sprintf(__('Devolución reserva #%d', 'flavor-chat-ia'), absint($reserva_id)),
            'categoria' => 'servicios',
            'subcategoria' => 'devoluciones_reservas',
            'modulo_origen' => 'reservas',
            'entidad_tipo' => 'reserva',
            'entidad_id' => absint($reserva_id),
            'referencia_tipo' => 'reserva_cancelada',
            'referencia_id' => absint($reserva_id),
            'base_imponible' => $devolucion,
            'iva_importe' => 0,
            'retencion_importe' => 0,
            'total' => $devolucion,
            'metadata' => [
                'origen' => sanitize_text_field((string) ($datos['origen'] ?? 'reservas')),
            ],
        ]);
    }

    /**
     * Integra ventas cerradas en Marketplace.
     *
     * @param int   $anuncio_id
     * @param array $datos
     * @return void
     */
    public function on_marketplace_anuncio_vendido($anuncio_id, $datos = []) {
        $anuncio_id = absint($anuncio_id);
        if ($anuncio_id <= 0) {
            return;
        }

        $precio = isset($datos['precio']) ? (float) $datos['precio'] : 0.0;
        if ($precio <= 0) {
            $precio = (float) get_post_meta($anuncio_id, '_marketplace_precio', true);
        }
        if ($precio <= 0) {
            return;
        }

        $vendedor_id = absint($datos['vendedor_id'] ?? 0);
        if ($vendedor_id <= 0) {
            $anuncio = get_post($anuncio_id);
            if ($anuncio) {
                $vendedor_id = (int) $anuncio->post_author;
            }
        }

        $vendedor_nombre = '';
        if ($vendedor_id > 0) {
            $user = get_userdata($vendedor_id);
            if ($user) {
                $vendedor_nombre = (string) $user->display_name;
            }
        }

        $titulo = sanitize_text_field((string) ($datos['titulo'] ?? get_the_title($anuncio_id)));
        $this->registrar_movimiento([
            'clave_unica' => 'marketplace_venta_' . $anuncio_id,
            'fecha_movimiento' => current_time('Y-m-d'),
            'tipo_movimiento' => 'ingreso',
            'estado' => 'confirmado',
            'concepto' => sprintf(__('Venta marketplace: %s', 'flavor-chat-ia'), $titulo ?: ('#' . $anuncio_id)),
            'categoria' => 'ventas',
            'subcategoria' => 'marketplace',
            'modulo_origen' => 'marketplace',
            'entidad_tipo' => 'marketplace_anuncio',
            'entidad_id' => $anuncio_id,
            'referencia_tipo' => 'anuncio_vendido',
            'referencia_id' => $anuncio_id,
            'tercero_tipo' => 'vendedor',
            'tercero_id' => $vendedor_id,
            'tercero_nombre' => $vendedor_nombre,
            'base_imponible' => $precio,
            'iva_importe' => 0,
            'retencion_importe' => 0,
            'total' => $precio,
            'metadata' => [
                'origen' => sanitize_text_field((string) ($datos['origen'] ?? 'marketplace')),
            ],
        ]);
    }

    /**
     * Integra aportaciones confirmadas de Crowdfunding.
     *
     * @param int $aportacion_id
     * @param int $proyecto_id
     * @return void
     */
    public function on_crowdfunding_aportacion_completada($aportacion_id, $proyecto_id) {
        global $wpdb;

        $tabla_aportaciones = $wpdb->prefix . 'flavor_crowdfunding_aportaciones';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_aportaciones)) {
            return;
        }

        $aportacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_aportaciones} WHERE id = %d LIMIT 1",
            absint($aportacion_id)
        ));
        if (!$aportacion) {
            return;
        }

        $importe = isset($aportacion->importe_eur_equivalente) && (float) $aportacion->importe_eur_equivalente > 0
            ? (float) $aportacion->importe_eur_equivalente
            : (float) $aportacion->importe;
        if ($importe <= 0) {
            return;
        }

        $aportante_id = absint($aportacion->usuario_id ?? 0);
        $aportante_nombre = '';
        if ($aportante_id > 0) {
            $user = get_userdata($aportante_id);
            if ($user) {
                $aportante_nombre = (string) $user->display_name;
            }
        }

        $this->registrar_movimiento([
            'clave_unica' => 'crowdfunding_aportacion_' . absint($aportacion_id),
            'fecha_movimiento' => current_time('Y-m-d'),
            'tipo_movimiento' => 'ingreso',
            'estado' => 'confirmado',
            'concepto' => sprintf(__('Aportación crowdfunding proyecto #%d', 'flavor-chat-ia'), absint($proyecto_id)),
            'categoria' => 'donaciones',
            'subcategoria' => 'crowdfunding',
            'modulo_origen' => 'crowdfunding',
            'entidad_tipo' => 'crowdfunding_aportacion',
            'entidad_id' => absint($aportacion_id),
            'referencia_tipo' => 'crowdfunding_proyecto',
            'referencia_id' => absint($proyecto_id),
            'tercero_tipo' => 'aportante',
            'tercero_id' => $aportante_id,
            'tercero_nombre' => $aportante_nombre,
            'base_imponible' => $importe,
            'iva_importe' => 0,
            'retencion_importe' => 0,
            'total' => $importe,
            'metadata' => [
                'moneda_origen' => sanitize_text_field((string) ($aportacion->moneda ?? 'eur')),
                'importe_origen' => (float) ($aportacion->importe ?? $importe),
            ],
        ]);
    }

    /**
     * Integra alta de cliente como coste opcional de adquisición.
     *
     * @param int   $cliente_id
     * @param array $datos
     * @return void
     */
    public function on_cliente_creado($cliente_id, $datos = []) {
        $coste = (float) $this->get_setting('coste_adquisicion_cliente', 0);
        if ($coste <= 0) {
            return;
        }

        $nombre = sanitize_text_field((string) ($datos['nombre'] ?? ''));
        $origen = sanitize_text_field((string) ($datos['origen'] ?? 'directo'));

        $this->registrar_movimiento([
            'clave_unica' => 'clientes_alta_' . absint($cliente_id),
            'fecha_movimiento' => current_time('Y-m-d'),
            'tipo_movimiento' => 'gasto',
            'estado' => 'confirmado',
            'concepto' => sprintf(__('Coste adquisición cliente: %s', 'flavor-chat-ia'), $nombre ?: ('#' . absint($cliente_id))),
            'categoria' => 'marketing',
            'subcategoria' => 'captacion_clientes',
            'modulo_origen' => 'clientes',
            'entidad_tipo' => 'cliente',
            'entidad_id' => absint($cliente_id),
            'referencia_tipo' => 'cliente_alta',
            'referencia_id' => absint($cliente_id),
            'tercero_tipo' => 'cliente',
            'tercero_id' => absint($cliente_id),
            'tercero_nombre' => $nombre,
            'base_imponible' => $coste,
            'iva_importe' => 0,
            'retencion_importe' => 0,
            'total' => $coste,
            'metadata' => [
                'origen_cliente' => $origen,
            ],
        ]);
    }

    /**
     * Integra costes de envío en campañas de Email Marketing al pasar a enviada.
     *
     * @param int    $campaign_id
     * @param string $status
     * @return void
     */
    public function on_email_marketing_campaign_status_changed($campaign_id, $status) {
        $status = sanitize_key((string) $status);
        if ($status !== 'enviada') {
            return;
        }

        $coste_unitario = (float) $this->get_setting('coste_email_por_envio', 0);
        if ($coste_unitario <= 0 || !class_exists('Flavor_EM_Campaign_Manager')) {
            return;
        }

        $manager = Flavor_EM_Campaign_Manager::get_instance();
        $campaign = $manager->get_campaign(absint($campaign_id));
        if (empty($campaign)) {
            return;
        }

        $stats = $campaign['stats'] ?? [];
        $enviados = absint($stats['enviados'] ?? 0);
        if ($enviados <= 0) {
            return;
        }

        $coste_total = round($enviados * $coste_unitario, 2);
        if ($coste_total <= 0) {
            return;
        }

        $this->registrar_movimiento([
            'clave_unica' => 'em_campaign_sent_' . absint($campaign_id),
            'fecha_movimiento' => current_time('Y-m-d'),
            'tipo_movimiento' => 'gasto',
            'estado' => 'confirmado',
            'concepto' => sprintf(__('Coste envío campaña email: %s', 'flavor-chat-ia'), sanitize_text_field((string) ($campaign['nombre'] ?? ('#' . absint($campaign_id))))),
            'categoria' => 'marketing',
            'subcategoria' => 'email_marketing',
            'modulo_origen' => 'email_marketing',
            'entidad_tipo' => 'em_campaign',
            'entidad_id' => absint($campaign_id),
            'referencia_tipo' => 'campania_enviada',
            'referencia_id' => absint($campaign_id),
            'base_imponible' => $coste_total,
            'iva_importe' => 0,
            'retencion_importe' => 0,
            'total' => $coste_total,
            'metadata' => [
                'enviados' => $enviados,
                'coste_unitario' => $coste_unitario,
            ],
        ]);
    }

    /**
     * Integra coste opcional por nuevo suscriptor de Email Marketing.
     *
     * @param int   $subscriber_id
     * @param array $data
     * @return void
     */
    public function on_email_marketing_subscriber_created($subscriber_id, $data = []) {
        $coste = (float) $this->get_setting('coste_nuevo_suscriptor', 0);
        if ($coste <= 0) {
            return;
        }

        $email = sanitize_email((string) ($data['email'] ?? ''));
        $nombre = sanitize_text_field((string) ($data['nombre'] ?? $email));

        $this->registrar_movimiento([
            'clave_unica' => 'em_subscriber_' . absint($subscriber_id),
            'fecha_movimiento' => current_time('Y-m-d'),
            'tipo_movimiento' => 'gasto',
            'estado' => 'confirmado',
            'concepto' => sprintf(__('Coste captación suscriptor: %s', 'flavor-chat-ia'), $nombre ?: ('#' . absint($subscriber_id))),
            'categoria' => 'marketing',
            'subcategoria' => 'captacion_suscriptores',
            'modulo_origen' => 'email_marketing',
            'entidad_tipo' => 'em_subscriber',
            'entidad_id' => absint($subscriber_id),
            'referencia_tipo' => 'subscriber_alta',
            'referencia_id' => absint($subscriber_id),
            'tercero_tipo' => 'suscriptor',
            'tercero_id' => absint($subscriber_id),
            'tercero_nombre' => $nombre,
            'base_imponible' => $coste,
            'iva_importe' => 0,
            'retencion_importe' => 0,
            'total' => $coste,
            'metadata' => [
                'email' => $email,
            ],
        ]);
    }

    public function render_admin_dashboard() {
        $periodo = isset($_GET['periodo']) ? sanitize_key((string) $_GET['periodo']) : 'mes';
        $modulo_origen = isset($_GET['modulo_origen']) ? sanitize_key((string) $_GET['modulo_origen']) : '';
        if (!in_array($periodo, ['mes', 'trimestre', 'ano'], true)) {
            $periodo = 'mes';
        }

        $actual = $this->obtener_estadisticas_periodo($periodo, 0, $modulo_origen);
        $anterior = $this->obtener_estadisticas_periodo($periodo, -1, $modulo_origen);
        $facturas_stats = $this->obtener_metricas_facturas_periodo($actual['desde'], $actual['hasta'], $modulo_origen);
        $modulos_disponibles = $this->obtener_modulos_origen_disponibles();
        $evolucion = $this->obtener_evolucion_mensual($modulo_origen);
        $desglose_ingresos = $this->obtener_desglose_categoria($actual['desde'], $actual['hasta'], 'ingreso');
        $desglose_gastos = $this->obtener_desglose_categoria($actual['desde'], $actual['hasta'], 'gasto');
        $ultimos_movimientos = $this->obtener_ultimos_movimientos(8);
        $totales_ano = $this->obtener_totales_ano();
        $desglose_modulo = $this->obtener_desglose_modulo($actual['desde'], $actual['hasta'], $modulo_origen);
        $simbolo = $this->get_setting('simbolo_moneda', '€');

        // Enqueue Chart.js
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true);

        ?>
        <div class="wrap dm-dashboard">
            <div class="dm-header">
                <div class="dm-header-content">
                    <h1 class="dm-title">
                        <span class="dashicons dashicons-chart-pie"></span>
                        <?php esc_html_e('Dashboard de Contabilidad', 'flavor-chat-ia'); ?>
                    </h1>
                    <p class="dm-subtitle"><?php esc_html_e('Control financiero integrado de todos los módulos', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="dm-header-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&action=nuevo&tipo=ingreso')); ?>" class="button">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Nuevo ingreso', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&action=nuevo&tipo=gasto')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-minus"></span>
                        <?php esc_html_e('Nuevo gasto', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>

            <!-- Filtros -->
            <div class="dm-filters-bar" style="background:#fff;padding:16px 20px;border-radius:8px;margin-bottom:20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <form method="get" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="page" value="contabilidad-dashboard" />

                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <label style="font-size:11px;color:#666;font-weight:500;"><?php esc_html_e('Periodo', 'flavor-chat-ia'); ?></label>
                        <select name="periodo" style="min-width:120px;">
                            <option value="mes" <?php selected($periodo, 'mes'); ?>><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></option>
                            <option value="trimestre" <?php selected($periodo, 'trimestre'); ?>><?php esc_html_e('Este trimestre', 'flavor-chat-ia'); ?></option>
                            <option value="ano" <?php selected($periodo, 'ano'); ?>><?php esc_html_e('Este año', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <label style="font-size:11px;color:#666;font-weight:500;"><?php esc_html_e('Módulo', 'flavor-chat-ia'); ?></label>
                        <select name="modulo_origen" style="min-width:150px;">
                            <option value=""><?php esc_html_e('Todos los módulos', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($modulos_disponibles as $mod): ?>
                                <option value="<?php echo esc_attr($mod); ?>" <?php selected($modulo_origen, $mod); ?>><?php echo esc_html(ucfirst($mod)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="button" style="margin-top:18px;">
                        <span class="dashicons dashicons-filter" style="vertical-align:middle;"></span>
                        <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
                    </button>
                </form>

                <div style="margin-left:auto;display:flex;gap:8px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&export_csv=1')); ?>" class="button" title="<?php esc_attr_e('Exportar CSV', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-download"></span>
                        CSV
                    </a>
                </div>
            </div>

            <?php if ($modulo_origen !== ''): ?>
            <div style="background:#e0f2fe;padding:10px 16px;border-radius:6px;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                <span class="dashicons dashicons-filter" style="color:#0369a1;"></span>
                <span><?php printf(esc_html__('Filtrando por módulo: %s', 'flavor-chat-ia'), '<strong>' . esc_html(ucfirst($modulo_origen)) . '</strong>'); ?></span>
                <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-dashboard&periodo=' . $periodo)); ?>" style="margin-left:auto;color:#0369a1;"><?php esc_html_e('Quitar filtro', 'flavor-chat-ia'); ?></a>
            </div>
            <?php endif; ?>

            <!-- KPIs principales -->
            <div class="dm-kpi-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
                <div class="dm-kpi-card" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:20px;border-radius:12px;">
                    <div style="font-size:12px;opacity:0.9;margin-bottom:4px;"><?php esc_html_e('Ingresos', 'flavor-chat-ia'); ?></div>
                    <div style="font-size:28px;font-weight:700;"><?php echo esc_html($this->format_money($actual['ingresos'])); ?></div>
                    <?php $variacion_ing = $anterior['ingresos'] > 0 ? (($actual['ingresos'] - $anterior['ingresos']) / $anterior['ingresos'] * 100) : 0; ?>
                    <div style="font-size:12px;margin-top:8px;opacity:0.9;">
                        <?php echo $variacion_ing >= 0 ? '↑' : '↓'; ?> <?php echo esc_html(number_format(abs($variacion_ing), 1)); ?>% vs anterior
                    </div>
                </div>

                <div class="dm-kpi-card" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:20px;border-radius:12px;">
                    <div style="font-size:12px;opacity:0.9;margin-bottom:4px;"><?php esc_html_e('Gastos', 'flavor-chat-ia'); ?></div>
                    <div style="font-size:28px;font-weight:700;"><?php echo esc_html($this->format_money($actual['gastos'])); ?></div>
                    <?php $variacion_gas = $anterior['gastos'] > 0 ? (($actual['gastos'] - $anterior['gastos']) / $anterior['gastos'] * 100) : 0; ?>
                    <div style="font-size:12px;margin-top:8px;opacity:0.9;">
                        <?php echo $variacion_gas >= 0 ? '↑' : '↓'; ?> <?php echo esc_html(number_format(abs($variacion_gas), 1)); ?>% vs anterior
                    </div>
                </div>

                <div class="dm-kpi-card" style="background:linear-gradient(135deg,<?php echo $actual['resultado'] >= 0 ? '#3b82f6,#2563eb' : '#f97316,#ea580c'; ?>);color:#fff;padding:20px;border-radius:12px;">
                    <div style="font-size:12px;opacity:0.9;margin-bottom:4px;"><?php esc_html_e('Resultado neto', 'flavor-chat-ia'); ?></div>
                    <div style="font-size:28px;font-weight:700;"><?php echo esc_html($this->format_money($actual['resultado'])); ?></div>
                    <div style="font-size:12px;margin-top:8px;opacity:0.9;">
                        <?php echo $actual['resultado'] >= 0 ? '✓ Beneficio' : '⚠ Pérdida'; ?>
                    </div>
                </div>

                <div class="dm-kpi-card" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;padding:20px;border-radius:12px;">
                    <div style="font-size:12px;opacity:0.9;margin-bottom:4px;"><?php esc_html_e('IVA neto a liquidar', 'flavor-chat-ia'); ?></div>
                    <div style="font-size:28px;font-weight:700;"><?php echo esc_html($this->format_money($actual['iva_repercutido'] - $actual['iva_soportado'])); ?></div>
                    <div style="font-size:12px;margin-top:8px;opacity:0.9;">
                        Rep: <?php echo esc_html($this->format_money($actual['iva_repercutido'])); ?> | Sop: <?php echo esc_html($this->format_money($actual['iva_soportado'])); ?>
                    </div>
                </div>
            </div>

            <!-- Gráficos y tablas -->
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">
                <!-- Gráfico de evolución -->
                <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;"><?php esc_html_e('Evolución últimos 12 meses', 'flavor-chat-ia'); ?></h3>
                    <div style="position:relative;height:250px;width:100%;">
                        <canvas id="chart-evolucion"></canvas>
                    </div>
                </div>

                <!-- Desglose por módulo -->
                <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;"><?php esc_html_e('Desglose por módulo', 'flavor-chat-ia'); ?></h3>
                    <?php if (!empty($desglose_modulo)): ?>
                    <table class="widefat" style="border:none;">
                        <thead>
                            <tr>
                                <th style="padding:8px 12px;"><?php esc_html_e('Módulo', 'flavor-chat-ia'); ?></th>
                                <th style="padding:8px 12px;text-align:right;"><?php esc_html_e('Ingresos', 'flavor-chat-ia'); ?></th>
                                <th style="padding:8px 12px;text-align:right;"><?php esc_html_e('Gastos', 'flavor-chat-ia'); ?></th>
                                <th style="padding:8px 12px;text-align:right;"><?php esc_html_e('Neto', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($desglose_modulo as $row):
                                $ing = (float) $row->ingresos;
                                $gas = (float) $row->gastos;
                                $neto = $ing - $gas;
                            ?>
                            <tr>
                                <td style="padding:8px 12px;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-dashboard&periodo=' . $periodo . '&modulo_origen=' . esc_attr($row->modulo_origen ?: 'manual'))); ?>">
                                        <?php echo esc_html(ucfirst($row->modulo_origen ?: 'manual')); ?>
                                    </a>
                                </td>
                                <td style="padding:8px 12px;text-align:right;color:#10b981;"><?php echo esc_html($this->format_money($ing)); ?></td>
                                <td style="padding:8px 12px;text-align:right;color:#ef4444;"><?php echo esc_html($this->format_money($gas)); ?></td>
                                <td style="padding:8px 12px;text-align:right;font-weight:600;color:<?php echo $neto >= 0 ? '#10b981' : '#ef4444'; ?>;">
                                    <?php echo esc_html($this->format_money($neto)); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="color:#666;text-align:center;padding:20px;"><?php esc_html_e('Sin movimientos en el periodo', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Categorías y movimientos recientes -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:24px;">
                <!-- Categorías de ingresos -->
                <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;color:#10b981;">
                        <span class="dashicons dashicons-arrow-down-alt" style="color:#10b981;"></span>
                        <?php esc_html_e('Ingresos por categoría', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (!empty($desglose_ingresos)): ?>
                    <ul style="list-style:none;margin:0;padding:0;">
                        <?php foreach (array_slice($desglose_ingresos, 0, 6) as $cat): ?>
                        <li style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                            <span><?php echo esc_html(ucfirst($cat->categoria ?: 'otros')); ?></span>
                            <span style="font-weight:600;color:#10b981;"><?php echo esc_html($this->format_money($cat->total)); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p style="color:#666;"><?php esc_html_e('Sin ingresos', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Categorías de gastos -->
                <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;color:#ef4444;">
                        <span class="dashicons dashicons-arrow-up-alt" style="color:#ef4444;"></span>
                        <?php esc_html_e('Gastos por categoría', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (!empty($desglose_gastos)): ?>
                    <ul style="list-style:none;margin:0;padding:0;">
                        <?php foreach (array_slice($desglose_gastos, 0, 6) as $cat): ?>
                        <li style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                            <span><?php echo esc_html(ucfirst($cat->categoria ?: 'otros')); ?></span>
                            <span style="font-weight:600;color:#ef4444;"><?php echo esc_html($this->format_money($cat->total)); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p style="color:#666;"><?php esc_html_e('Sin gastos', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Últimos movimientos -->
                <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php esc_html_e('Últimos movimientos', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (!empty($ultimos_movimientos)): ?>
                    <ul style="list-style:none;margin:0;padding:0;max-height:280px;overflow-y:auto;">
                        <?php foreach ($ultimos_movimientos as $mov):
                            $es_ingreso = $mov->tipo_movimiento === 'ingreso';
                        ?>
                        <li style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <div>
                                    <div style="font-size:13px;font-weight:500;"><?php echo esc_html(wp_trim_words($mov->concepto, 6)); ?></div>
                                    <div style="font-size:11px;color:#666;">
                                        <?php echo esc_html(date_i18n('d M', strtotime($mov->fecha_movimiento))); ?>
                                        · <?php echo esc_html(ucfirst($mov->modulo_origen ?: 'manual')); ?>
                                    </div>
                                </div>
                                <span style="font-weight:600;color:<?php echo $es_ingreso ? '#10b981' : '#ef4444'; ?>;">
                                    <?php echo $es_ingreso ? '+' : '-'; ?><?php echo esc_html($this->format_money($mov->total)); ?>
                                </span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos')); ?>" style="display:block;text-align:center;margin-top:12px;color:#2563eb;font-size:13px;">
                        <?php esc_html_e('Ver todos los movimientos →', 'flavor-chat-ia'); ?>
                    </a>
                    <?php else: ?>
                    <p style="color:#666;text-align:center;padding:20px;"><?php esc_html_e('Sin movimientos', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comparativa y resumen anual -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <!-- Comparativa -->
                <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;"><?php esc_html_e('Comparativa vs periodo anterior', 'flavor-chat-ia'); ?></h3>
                    <table class="widefat" style="border:none;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Métrica', 'flavor-chat-ia'); ?></th>
                                <th style="text-align:right;"><?php esc_html_e('Actual', 'flavor-chat-ia'); ?></th>
                                <th style="text-align:right;"><?php esc_html_e('Anterior', 'flavor-chat-ia'); ?></th>
                                <th style="text-align:right;"><?php esc_html_e('Var.', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $metricas = [
                                ['label' => __('Ingresos', 'flavor-chat-ia'), 'actual' => $actual['ingresos'], 'anterior' => $anterior['ingresos']],
                                ['label' => __('Gastos', 'flavor-chat-ia'), 'actual' => $actual['gastos'], 'anterior' => $anterior['gastos']],
                                ['label' => __('Resultado', 'flavor-chat-ia'), 'actual' => $actual['resultado'], 'anterior' => $anterior['resultado']],
                                ['label' => __('IVA neto', 'flavor-chat-ia'), 'actual' => $actual['iva_repercutido'] - $actual['iva_soportado'], 'anterior' => $anterior['iva_repercutido'] - $anterior['iva_soportado']],
                            ];
                            foreach ($metricas as $m):
                                $var = $m['anterior'] != 0 ? (($m['actual'] - $m['anterior']) / abs($m['anterior']) * 100) : 0;
                            ?>
                            <tr>
                                <td style="padding:10px 12px;"><?php echo esc_html($m['label']); ?></td>
                                <td style="padding:10px 12px;text-align:right;font-weight:500;"><?php echo esc_html($this->format_money($m['actual'])); ?></td>
                                <td style="padding:10px 12px;text-align:right;color:#666;"><?php echo esc_html($this->format_money($m['anterior'])); ?></td>
                                <td style="padding:10px 12px;text-align:right;color:<?php echo $var >= 0 ? '#10b981' : '#ef4444'; ?>;">
                                    <?php echo $var >= 0 ? '↑' : '↓'; ?> <?php echo esc_html(number_format(abs($var), 1)); ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Resumen anual -->
                <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;"><?php printf(esc_html__('Acumulado %s', 'flavor-chat-ia'), date('Y')); ?></h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div style="padding:16px;background:#f0fdf4;border-radius:8px;text-align:center;">
                            <div style="font-size:12px;color:#166534;margin-bottom:4px;"><?php esc_html_e('Total ingresos', 'flavor-chat-ia'); ?></div>
                            <div style="font-size:20px;font-weight:700;color:#166534;"><?php echo esc_html($this->format_money($totales_ano['ingresos'])); ?></div>
                        </div>
                        <div style="padding:16px;background:#fef2f2;border-radius:8px;text-align:center;">
                            <div style="font-size:12px;color:#991b1b;margin-bottom:4px;"><?php esc_html_e('Total gastos', 'flavor-chat-ia'); ?></div>
                            <div style="font-size:20px;font-weight:700;color:#991b1b;"><?php echo esc_html($this->format_money($totales_ano['gastos'])); ?></div>
                        </div>
                        <div style="padding:16px;background:<?php echo $totales_ano['resultado'] >= 0 ? '#eff6ff' : '#fff7ed'; ?>;border-radius:8px;text-align:center;">
                            <div style="font-size:12px;color:<?php echo $totales_ano['resultado'] >= 0 ? '#1e40af' : '#9a3412'; ?>;margin-bottom:4px;"><?php esc_html_e('Resultado', 'flavor-chat-ia'); ?></div>
                            <div style="font-size:20px;font-weight:700;color:<?php echo $totales_ano['resultado'] >= 0 ? '#1e40af' : '#9a3412'; ?>;"><?php echo esc_html($this->format_money($totales_ano['resultado'])); ?></div>
                        </div>
                        <div style="padding:16px;background:#faf5ff;border-radius:8px;text-align:center;">
                            <div style="font-size:12px;color:#6b21a8;margin-bottom:4px;"><?php esc_html_e('IVA a liquidar', 'flavor-chat-ia'); ?></div>
                            <div style="font-size:20px;font-weight:700;color:#6b21a8;"><?php echo esc_html($this->format_money($totales_ano['iva_neto'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart === 'undefined') return;

            // Datos para el gráfico
            const evolucionData = <?php echo wp_json_encode($evolucion); ?>;

            // Gráfico de evolución
            const ctxEvolucion = document.getElementById('chart-evolucion');
            if (ctxEvolucion) {
                new Chart(ctxEvolucion, {
                    type: 'line',
                    data: {
                        labels: evolucionData.map(d => d.mes),
                        datasets: [
                            {
                                label: '<?php esc_html_e('Ingresos', 'flavor-chat-ia'); ?>',
                                data: evolucionData.map(d => d.ingresos),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: '<?php esc_html_e('Gastos', 'flavor-chat-ia'); ?>',
                                data: evolucionData.map(d => d.gastos),
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: '<?php esc_html_e('Resultado', 'flavor-chat-ia'); ?>',
                                data: evolucionData.map(d => d.resultado),
                                borderColor: '#3b82f6',
                                borderDash: [5, 5],
                                fill: false,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString() + ' <?php echo esc_js($simbolo); ?>';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }

    public function render_admin_movimientos() {
        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : '';
        $filtros = $this->obtener_filtros_movimientos_desde_request();

        if (isset($_GET['export_csv']) && absint($_GET['export_csv']) === 1 && $action !== 'nuevo') {
            $this->exportar_movimientos_csv($filtros);
            exit;
        }

        // Procesar anulación de movimiento
        if ($action === 'anular' && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            $movimiento_id = absint($_GET['id']);
            if (wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_GET['_wpnonce'])), 'anular_movimiento_' . $movimiento_id)) {
                $this->anular_movimiento($movimiento_id);
                wp_safe_redirect(admin_url('admin.php?page=contabilidad-movimientos&anulado=1'));
                exit;
            }
        }

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Movimientos contables', 'flavor-chat-ia'), [
            ['label' => __('Nuevo gasto', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=contabilidad-movimientos&action=nuevo&tipo=gasto'), 'class' => 'button-primary'],
            ['label' => __('Nuevo ingreso', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=contabilidad-movimientos&action=nuevo&tipo=ingreso'), 'class' => 'button'],
        ]);

        if ($action === 'nuevo' || $action === 'editar') {
            $this->render_admin_form_movimiento();
            echo '</div>';
            return;
        }

        if ($action === 'ver' && isset($_GET['id'])) {
            $this->render_admin_detalle_movimiento(absint($_GET['id']));
            echo '</div>';
            return;
        }

        $this->render_admin_tabla_movimientos($filtros);
        echo '</div>';
    }

    /**
     * Anula un movimiento contable.
     *
     * @param int $movimiento_id
     * @return bool
     */
    private function anular_movimiento($movimiento_id) {
        global $wpdb;

        return $wpdb->update(
            $this->tabla_movimientos,
            ['estado' => 'anulado'],
            ['id' => $movimiento_id],
            ['%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Muestra el detalle de un movimiento.
     *
     * @param int $movimiento_id
     */
    private function render_admin_detalle_movimiento($movimiento_id) {
        global $wpdb;

        $mov = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_movimientos} WHERE id = %d",
            $movimiento_id
        ));

        if (!$mov) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Movimiento no encontrado.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $metadata = !empty($mov->metadata) ? json_decode($mov->metadata, true) : [];
        $es_ingreso = $mov->tipo_movimiento === 'ingreso';
        ?>
        <div style="max-width:800px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos')); ?>" class="button" style="margin-bottom:16px;">
                ← <?php esc_html_e('Volver a movimientos', 'flavor-chat-ia'); ?>
            </a>

            <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:20px;">
                    <div>
                        <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500;background:<?php echo $es_ingreso ? '#dcfce7' : '#fee2e2'; ?>;color:<?php echo $es_ingreso ? '#166534' : '#991b1b'; ?>;">
                            <?php echo $es_ingreso ? esc_html__('Ingreso', 'flavor-chat-ia') : esc_html__('Gasto', 'flavor-chat-ia'); ?>
                        </span>
                        <?php if ($mov->estado === 'anulado'): ?>
                        <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500;background:#fee2e2;color:#991b1b;margin-left:8px;">
                            <?php esc_html_e('ANULADO', 'flavor-chat-ia'); ?>
                        </span>
                        <?php endif; ?>
                        <h2 style="margin:8px 0 0;font-size:20px;"><?php echo esc_html($mov->concepto); ?></h2>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:28px;font-weight:700;color:<?php echo $es_ingreso ? '#10b981' : '#ef4444'; ?>;">
                            <?php echo $es_ingreso ? '+' : '-'; ?><?php echo esc_html($this->format_money($mov->total)); ?>
                        </div>
                        <div style="font-size:13px;color:#666;">
                            <?php echo esc_html(date_i18n('j F Y', strtotime($mov->fecha_movimiento))); ?>
                        </div>
                    </div>
                </div>

                <table class="widefat" style="border:none;">
                    <tbody>
                        <tr>
                            <td style="padding:10px 12px;width:200px;color:#666;"><?php esc_html_e('ID', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;font-weight:500;">#<?php echo esc_html($mov->id); ?></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 12px;color:#666;"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;"><?php echo esc_html(ucfirst($mov->categoria ?: 'otros')); ?></td>
                        </tr>
                        <?php if ($mov->subcategoria): ?>
                        <tr>
                            <td style="padding:10px 12px;color:#666;"><?php esc_html_e('Subcategoría', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;"><?php echo esc_html($mov->subcategoria); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td style="padding:10px 12px;color:#666;"><?php esc_html_e('Módulo origen', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&modulo_origen=' . esc_attr($mov->modulo_origen ?: 'manual'))); ?>">
                                    <?php echo esc_html(ucfirst($mov->modulo_origen ?: 'manual')); ?>
                                </a>
                            </td>
                        </tr>
                        <?php if ($mov->tercero_nombre): ?>
                        <tr>
                            <td style="padding:10px 12px;color:#666;"><?php esc_html_e('Tercero', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;"><?php echo esc_html($mov->tercero_nombre); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr style="background:#f8fafc;">
                            <td style="padding:10px 12px;color:#666;"><?php esc_html_e('Base imponible', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;"><?php echo esc_html($this->format_money($mov->base_imponible)); ?></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 12px;color:#666;"><?php esc_html_e('IVA', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;"><?php echo esc_html(number_format($mov->iva_porcentaje, 1)); ?>% = <?php echo esc_html($this->format_money($mov->iva_importe)); ?></td>
                        </tr>
                        <?php if ((float) $mov->retencion_importe > 0): ?>
                        <tr>
                            <td style="padding:10px 12px;color:#666;"><?php esc_html_e('Retención', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;"><?php echo esc_html(number_format($mov->retencion_porcentaje, 1)); ?>% = <?php echo esc_html($this->format_money($mov->retencion_importe)); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr style="background:#f8fafc;">
                            <td style="padding:10px 12px;color:#666;font-weight:600;"><?php esc_html_e('Total', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;font-weight:700;font-size:16px;"><?php echo esc_html($this->format_money($mov->total)); ?></td>
                        </tr>
                        <?php if (!empty($metadata)): ?>
                        <tr>
                            <td style="padding:10px 12px;color:#666;"><?php esc_html_e('Metadatos', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;"><code style="font-size:12px;"><?php echo esc_html(wp_json_encode($metadata, JSON_PRETTY_PRINT)); ?></code></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td style="padding:10px 12px;color:#666;"><?php esc_html_e('Registrado', 'flavor-chat-ia'); ?></td>
                            <td style="padding:10px 12px;"><?php echo esc_html($mov->created_at); ?></td>
                        </tr>
                    </tbody>
                </table>

                <?php if ($mov->estado !== 'anulado'): ?>
                <div style="margin-top:20px;padding-top:16px;border-top:1px solid #e5e7eb;display:flex;gap:8px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&action=editar&id=' . $mov->id)); ?>" class="button">
                        <span class="dashicons dashicons-edit" style="vertical-align:middle;"></span>
                        <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=contabilidad-movimientos&action=anular&id=' . $mov->id), 'anular_movimiento_' . $mov->id)); ?>" class="button" style="color:#dc2626;" onclick="return confirm('<?php esc_attr_e('¿Seguro que deseas anular este movimiento?', 'flavor-chat-ia'); ?>');">
                        <span class="dashicons dashicons-dismiss" style="vertical-align:middle;"></span>
                        <?php esc_html_e('Anular', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_admin_form_movimiento() {
        global $wpdb;

        $editando_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $movimiento = null;

        // Cargar datos si estamos editando
        if ($editando_id > 0) {
            $movimiento = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->tabla_movimientos} WHERE id = %d",
                $editando_id
            ));
            if (!$movimiento) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Movimiento no encontrado.', 'flavor-chat-ia') . '</p></div>';
                return;
            }
        }

        $tipo = $movimiento ? $movimiento->tipo_movimiento : (isset($_GET['tipo']) ? sanitize_key((string) $_GET['tipo']) : 'gasto');
        if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
            $tipo = 'gasto';
        }

        // Procesar guardado
        if (isset($_POST['conta_guardar'])) {
            $nonce = isset($_POST['conta_nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['conta_nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'conta_guardar_movimiento')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            } else {
                $base = (float) str_replace(',', '.', (string) ($_POST['base_imponible'] ?? '0'));
                $iva_porcentaje = (float) str_replace(',', '.', (string) ($_POST['iva_porcentaje'] ?? '0'));
                $iva_importe = round($base * ($iva_porcentaje / 100), 2);

                $datos = [
                    'fecha_movimiento' => sanitize_text_field((string) ($_POST['fecha_movimiento'] ?? current_time('Y-m-d'))),
                    'tipo_movimiento' => sanitize_key((string) ($_POST['tipo_movimiento'] ?? $tipo)),
                    'concepto' => sanitize_text_field((string) ($_POST['concepto'] ?? '')),
                    'categoria' => sanitize_text_field((string) ($_POST['categoria'] ?? 'otros')),
                    'subcategoria' => sanitize_text_field((string) ($_POST['subcategoria'] ?? '')),
                    'modulo_origen' => sanitize_key((string) ($_POST['modulo_origen'] ?? 'manual')),
                    'base_imponible' => $base,
                    'iva_porcentaje' => $iva_porcentaje,
                    'iva_importe' => $iva_importe,
                    'total' => $base + $iva_importe,
                    'tercero_nombre' => sanitize_text_field((string) ($_POST['tercero_nombre'] ?? '')),
                ];

                if ($editando_id > 0) {
                    // Actualizar existente
                    $actualizado = $wpdb->update(
                        $this->tabla_movimientos,
                        $datos,
                        ['id' => $editando_id],
                        ['%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s'],
                        ['%d']
                    );

                    if ($actualizado !== false) {
                        wp_safe_redirect(admin_url('admin.php?page=contabilidad-movimientos&action=ver&id=' . $editando_id . '&updated=1'));
                        exit;
                    }
                } else {
                    // Crear nuevo
                    $datos['metadata'] = ['origen' => 'manual_admin'];
                    $id = $this->registrar_movimiento($datos);

                    if ($id > 0) {
                        wp_safe_redirect(admin_url('admin.php?page=contabilidad-movimientos&created=1'));
                        exit;
                    }
                }

                echo '<div class="notice notice-error"><p>' . esc_html__('No se pudo guardar el movimiento.', 'flavor-chat-ia') . '</p></div>';
            }
        }

        // Valores por defecto o del movimiento existente
        $valores = [
            'tipo_movimiento' => $movimiento->tipo_movimiento ?? $tipo,
            'fecha_movimiento' => $movimiento->fecha_movimiento ?? current_time('Y-m-d'),
            'concepto' => $movimiento->concepto ?? '',
            'categoria' => $movimiento->categoria ?? 'otros',
            'subcategoria' => $movimiento->subcategoria ?? '',
            'tercero_nombre' => $movimiento->tercero_nombre ?? '',
            'modulo_origen' => $movimiento->modulo_origen ?? 'manual',
            'base_imponible' => $movimiento->base_imponible ?? 0,
            'iva_porcentaje' => $movimiento->iva_porcentaje ?? 21,
        ];

        $titulo_form = $editando_id > 0
            ? sprintf(__('Editar movimiento #%d', 'flavor-chat-ia'), $editando_id)
            : ($tipo === 'ingreso' ? __('Nuevo ingreso', 'flavor-chat-ia') : __('Nuevo gasto', 'flavor-chat-ia'));

        ?>
        <div style="max-width:700px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos')); ?>" class="button" style="margin-bottom:16px;">
                ← <?php esc_html_e('Volver a movimientos', 'flavor-chat-ia'); ?>
            </a>

            <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin:0 0 20px;"><?php echo esc_html($titulo_form); ?></h3>

                <form method="post">
                    <?php wp_nonce_field('conta_guardar_movimiento', 'conta_nonce'); ?>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th><label for="tipo_movimiento"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></label></th>
                                <td>
                                    <select id="tipo_movimiento" name="tipo_movimiento">
                                        <option value="ingreso" <?php selected($valores['tipo_movimiento'], 'ingreso'); ?>><?php esc_html_e('Ingreso', 'flavor-chat-ia'); ?></option>
                                        <option value="gasto" <?php selected($valores['tipo_movimiento'], 'gasto'); ?>><?php esc_html_e('Gasto', 'flavor-chat-ia'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="fecha_movimiento"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></label></th>
                                <td><input type="date" id="fecha_movimiento" name="fecha_movimiento" value="<?php echo esc_attr($valores['fecha_movimiento']); ?>" /></td>
                            </tr>
                            <tr>
                                <th><label for="concepto"><?php esc_html_e('Concepto', 'flavor-chat-ia'); ?> *</label></th>
                                <td><input type="text" class="regular-text" id="concepto" name="concepto" value="<?php echo esc_attr($valores['concepto']); ?>" required /></td>
                            </tr>
                            <tr>
                                <th><label for="categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label></th>
                                <td><input type="text" class="regular-text" id="categoria" name="categoria" value="<?php echo esc_attr($valores['categoria']); ?>" /></td>
                            </tr>
                            <tr>
                                <th><label for="subcategoria"><?php esc_html_e('Subcategoría', 'flavor-chat-ia'); ?></label></th>
                                <td><input type="text" class="regular-text" id="subcategoria" name="subcategoria" value="<?php echo esc_attr($valores['subcategoria']); ?>" /></td>
                            </tr>
                            <tr>
                                <th><label for="tercero_nombre"><?php esc_html_e('Tercero', 'flavor-chat-ia'); ?></label></th>
                                <td><input type="text" class="regular-text" id="tercero_nombre" name="tercero_nombre" value="<?php echo esc_attr($valores['tercero_nombre']); ?>" /></td>
                            </tr>
                            <tr>
                                <th><label for="modulo_origen"><?php esc_html_e('Módulo origen', 'flavor-chat-ia'); ?></label></th>
                                <td><input type="text" class="regular-text" id="modulo_origen" name="modulo_origen" value="<?php echo esc_attr($valores['modulo_origen']); ?>" /></td>
                            </tr>
                            <tr>
                                <th><label for="base_imponible"><?php esc_html_e('Base imponible', 'flavor-chat-ia'); ?></label></th>
                                <td><input type="number" step="0.01" id="base_imponible" name="base_imponible" value="<?php echo esc_attr($valores['base_imponible']); ?>" /></td>
                            </tr>
                            <tr>
                                <th><label for="iva_porcentaje"><?php esc_html_e('IVA %', 'flavor-chat-ia'); ?></label></th>
                                <td><input type="number" step="0.01" id="iva_porcentaje" name="iva_porcentaje" value="<?php echo esc_attr($valores['iva_porcentaje']); ?>" /></td>
                            </tr>
                        </tbody>
                    </table>

                    <?php
                    $boton_texto = $editando_id > 0 ? __('Actualizar movimiento', 'flavor-chat-ia') : __('Guardar movimiento', 'flavor-chat-ia');
                    submit_button($boton_texto, 'primary', 'conta_guardar');
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    private function render_admin_tabla_movimientos($filtros = []) {
        if (isset($_GET['created']) && absint($_GET['created']) === 1) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Movimiento guardado.', 'flavor-chat-ia') . '</p></div>';
        }
        if (isset($_GET['anulado']) && absint($_GET['anulado']) === 1) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Movimiento anulado.', 'flavor-chat-ia') . '</p></div>';
        }

        if (empty($filtros) || !is_array($filtros)) {
            $filtros = $this->obtener_filtros_movimientos_desde_request();
        }
        $desde = (string) ($filtros['desde'] ?? '');
        $hasta = (string) ($filtros['hasta'] ?? '');
        $tipo = (string) ($filtros['tipo'] ?? '');
        $modulo_origen = (string) ($filtros['modulo_origen'] ?? '');

        $rows = $this->obtener_movimientos_filtrados($filtros, 300);

        $modulos_disponibles = $this->obtener_modulos_origen_disponibles();

        // Calcular totales
        $total_ingresos = 0;
        $total_gastos = 0;
        foreach ($rows as $row) {
            if ($row->estado === 'confirmado') {
                if ($row->tipo_movimiento === 'ingreso') {
                    $total_ingresos += (float) $row->total;
                } else {
                    $total_gastos += (float) $row->total;
                }
            }
        }

        ?>
        <!-- Filtros mejorados -->
        <div style="background:#fff;padding:16px 20px;border-radius:8px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            <form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                <input type="hidden" name="page" value="contabilidad-movimientos" />

                <div>
                    <label style="display:block;font-size:11px;color:#666;margin-bottom:4px;"><?php esc_html_e('Desde', 'flavor-chat-ia'); ?></label>
                    <input type="date" name="desde" value="<?php echo esc_attr($desde); ?>" />
                </div>

                <div>
                    <label style="display:block;font-size:11px;color:#666;margin-bottom:4px;"><?php esc_html_e('Hasta', 'flavor-chat-ia'); ?></label>
                    <input type="date" name="hasta" value="<?php echo esc_attr($hasta); ?>" />
                </div>

                <div>
                    <label style="display:block;font-size:11px;color:#666;margin-bottom:4px;"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></label>
                    <select name="tipo">
                        <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                        <option value="ingreso" <?php selected($tipo, 'ingreso'); ?>><?php esc_html_e('Ingreso', 'flavor-chat-ia'); ?></option>
                        <option value="gasto" <?php selected($tipo, 'gasto'); ?>><?php esc_html_e('Gasto', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-size:11px;color:#666;margin-bottom:4px;"><?php esc_html_e('Módulo', 'flavor-chat-ia'); ?></label>
                    <select name="modulo_origen">
                        <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($modulos_disponibles as $mod): ?>
                        <option value="<?php echo esc_attr($mod); ?>" <?php selected($modulo_origen, $mod); ?>><?php echo esc_html(ucfirst($mod)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="button"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button>
                <button class="button" type="submit" name="export_csv" value="1">
                    <span class="dashicons dashicons-download" style="vertical-align:middle;"></span>
                    <?php esc_html_e('CSV', 'flavor-chat-ia'); ?>
                </button>
            </form>
        </div>

        <!-- Resumen rápido -->
        <div style="display:flex;gap:16px;margin-bottom:16px;">
            <div style="background:#dcfce7;padding:12px 20px;border-radius:8px;flex:1;">
                <span style="font-size:12px;color:#166534;"><?php esc_html_e('Ingresos', 'flavor-chat-ia'); ?></span>
                <div style="font-size:20px;font-weight:700;color:#166534;"><?php echo esc_html($this->format_money($total_ingresos)); ?></div>
            </div>
            <div style="background:#fee2e2;padding:12px 20px;border-radius:8px;flex:1;">
                <span style="font-size:12px;color:#991b1b;"><?php esc_html_e('Gastos', 'flavor-chat-ia'); ?></span>
                <div style="font-size:20px;font-weight:700;color:#991b1b;"><?php echo esc_html($this->format_money($total_gastos)); ?></div>
            </div>
            <div style="background:<?php echo ($total_ingresos - $total_gastos) >= 0 ? '#dbeafe' : '#ffedd5'; ?>;padding:12px 20px;border-radius:8px;flex:1;">
                <span style="font-size:12px;color:<?php echo ($total_ingresos - $total_gastos) >= 0 ? '#1e40af' : '#9a3412'; ?>;"><?php esc_html_e('Resultado', 'flavor-chat-ia'); ?></span>
                <div style="font-size:20px;font-weight:700;color:<?php echo ($total_ingresos - $total_gastos) >= 0 ? '#1e40af' : '#9a3412'; ?>;"><?php echo esc_html($this->format_money($total_ingresos - $total_gastos)); ?></div>
            </div>
            <div style="background:#f3f4f6;padding:12px 20px;border-radius:8px;">
                <span style="font-size:12px;color:#666;"><?php esc_html_e('Movimientos', 'flavor-chat-ia'); ?></span>
                <div style="font-size:20px;font-weight:700;"><?php echo esc_html(count($rows)); ?></div>
            </div>
        </div>

        <!-- Tabla de movimientos -->
        <table class="widefat striped" style="background:#fff;border-radius:8px;overflow:hidden;">
            <thead>
                <tr>
                    <th style="padding:12px;"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th style="padding:12px;"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                    <th style="padding:12px;"><?php esc_html_e('Concepto', 'flavor-chat-ia'); ?></th>
                    <th style="padding:12px;"><?php esc_html_e('Módulo', 'flavor-chat-ia'); ?></th>
                    <th style="padding:12px;text-align:right;"><?php esc_html_e('Total', 'flavor-chat-ia'); ?></th>
                    <th style="padding:12px;text-align:center;"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    <th style="padding:12px;text-align:center;"><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row):
                        $es_ingreso = $row->tipo_movimiento === 'ingreso';
                        $anulado = $row->estado === 'anulado';
                    ?>
                    <tr style="<?php echo $anulado ? 'opacity:0.5;' : ''; ?>">
                        <td style="padding:10px 12px;"><?php echo esc_html(date_i18n('d/m/Y', strtotime($row->fecha_movimiento))); ?></td>
                        <td style="padding:10px 12px;">
                            <span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:500;background:<?php echo $es_ingreso ? '#dcfce7' : '#fee2e2'; ?>;color:<?php echo $es_ingreso ? '#166534' : '#991b1b'; ?>;">
                                <?php echo $es_ingreso ? esc_html__('Ingreso', 'flavor-chat-ia') : esc_html__('Gasto', 'flavor-chat-ia'); ?>
                            </span>
                        </td>
                        <td style="padding:10px 12px;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&action=ver&id=' . $row->id)); ?>" style="font-weight:500;color:#1e40af;">
                                <?php echo esc_html(wp_trim_words($row->concepto, 8)); ?>
                            </a>
                        </td>
                        <td style="padding:10px 12px;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&modulo_origen=' . esc_attr($row->modulo_origen ?: 'manual'))); ?>" style="color:#666;">
                                <?php echo esc_html(ucfirst($row->modulo_origen ?: 'manual')); ?>
                            </a>
                        </td>
                        <td style="padding:10px 12px;text-align:right;font-weight:600;color:<?php echo $es_ingreso ? '#10b981' : '#ef4444'; ?>;">
                            <?php echo $es_ingreso ? '+' : '-'; ?><?php echo esc_html($this->format_money($row->total)); ?>
                        </td>
                        <td style="padding:10px 12px;text-align:center;">
                            <?php if ($anulado): ?>
                            <span style="color:#dc2626;font-size:11px;"><?php esc_html_e('Anulado', 'flavor-chat-ia'); ?></span>
                            <?php else: ?>
                            <span style="color:#10b981;font-size:11px;">✓</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px 12px;text-align:center;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&action=ver&id=' . $row->id)); ?>" title="<?php esc_attr_e('Ver', 'flavor-chat-ia'); ?>" style="margin-right:4px;">
                                <span class="dashicons dashicons-visibility" style="color:#666;"></span>
                            </a>
                            <?php if (!$anulado): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&action=editar&id=' . $row->id)); ?>" title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>" style="margin-right:4px;">
                                <span class="dashicons dashicons-edit" style="color:#2563eb;"></span>
                            </a>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=contabilidad-movimientos&action=anular&id=' . $row->id), 'anular_movimiento_' . $row->id)); ?>" title="<?php esc_attr_e('Anular', 'flavor-chat-ia'); ?>" onclick="return confirm('<?php esc_attr_e('¿Seguro que deseas anular este movimiento?', 'flavor-chat-ia'); ?>');">
                                <span class="dashicons dashicons-dismiss" style="color:#dc2626;"></span>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="padding:20px;text-align:center;color:#666;"><?php esc_html_e('No hay movimientos.', 'flavor-chat-ia'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    public function render_admin_config() {
        if (isset($_POST['conta_config_guardar'])) {
            $nonce = isset($_POST['conta_config_nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['conta_config_nonce'])) : '';
            if (wp_verify_nonce($nonce, 'conta_config_guardar')) {
                $this->update_setting('moneda', sanitize_text_field((string) ($_POST['moneda'] ?? 'EUR')));
                $this->update_setting('simbolo_moneda', sanitize_text_field((string) ($_POST['simbolo_moneda'] ?? '€')));
                $this->update_setting('coste_adquisicion_cliente', (float) str_replace(',', '.', (string) ($_POST['coste_adquisicion_cliente'] ?? '0')));
                $this->update_setting('coste_email_por_envio', (float) str_replace(',', '.', (string) ($_POST['coste_email_por_envio'] ?? '0')));
                $this->update_setting('coste_nuevo_suscriptor', (float) str_replace(',', '.', (string) ($_POST['coste_nuevo_suscriptor'] ?? '0')));
                echo '<div class="notice notice-success"><p>' . esc_html__('Configuración guardada.', 'flavor-chat-ia') . '</p></div>';
            }
        }

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Contabilidad', 'flavor-chat-ia'));
        echo '<form method="post">';
        wp_nonce_field('conta_config_guardar', 'conta_config_nonce');
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th><label for="moneda">' . esc_html__('Moneda', 'flavor-chat-ia') . '</label></th><td><input id="moneda" name="moneda" class="regular-text" value="' . esc_attr((string) $this->get_setting('moneda', 'EUR')) . '" /></td></tr>';
        echo '<tr><th><label for="simbolo_moneda">' . esc_html__('Símbolo', 'flavor-chat-ia') . '</label></th><td><input id="simbolo_moneda" name="simbolo_moneda" class="regular-text" value="' . esc_attr((string) $this->get_setting('simbolo_moneda', '€')) . '" /></td></tr>';
        echo '<tr><th><label for="coste_adquisicion_cliente">' . esc_html__('Coste adquisición cliente', 'flavor-chat-ia') . '</label></th><td><input type="number" min="0" step="0.01" id="coste_adquisicion_cliente" name="coste_adquisicion_cliente" class="small-text" value="' . esc_attr((string) $this->get_setting('coste_adquisicion_cliente', 0)) . '" /> <span class="description">' . esc_html__('Gasto automático por alta de cliente (0 desactiva)', 'flavor-chat-ia') . '</span></td></tr>';
        echo '<tr><th><label for="coste_email_por_envio">' . esc_html__('Coste por email enviado', 'flavor-chat-ia') . '</label></th><td><input type="number" min="0" step="0.0001" id="coste_email_por_envio" name="coste_email_por_envio" class="small-text" value="' . esc_attr((string) $this->get_setting('coste_email_por_envio', 0)) . '" /> <span class="description">' . esc_html__('Se aplica al pasar campaña a enviada (0 desactiva)', 'flavor-chat-ia') . '</span></td></tr>';
        echo '<tr><th><label for="coste_nuevo_suscriptor">' . esc_html__('Coste nuevo suscriptor', 'flavor-chat-ia') . '</label></th><td><input type="number" min="0" step="0.01" id="coste_nuevo_suscriptor" name="coste_nuevo_suscriptor" class="small-text" value="' . esc_attr((string) $this->get_setting('coste_nuevo_suscriptor', 0)) . '" /> <span class="description">' . esc_html__('Gasto automático por cada suscriptor creado (0 desactiva)', 'flavor-chat-ia') . '</span></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Guardar', 'flavor-chat-ia'), 'primary', 'conta_config_guardar');
        echo '</form>';
        echo '<p>' . esc_html__('Integración transversal: cualquier módulo puede enviar movimientos con el hook flavor_contabilidad_registrar_movimiento.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }

    /**
     * Página de informes fiscales y contables.
     */
    public function render_admin_informes() {
        $ano = isset($_GET['ano']) ? absint($_GET['ano']) : (int) date('Y');
        $trimestre = isset($_GET['trimestre']) ? absint($_GET['trimestre']) : (int) ceil((int) date('n') / 3);
        $tipo_informe = isset($_GET['tipo']) ? sanitize_key((string) $_GET['tipo']) : 'trimestral';

        // Calcular rangos según tipo de informe
        if ($tipo_informe === 'anual') {
            $desde = "{$ano}-01-01";
            $hasta = "{$ano}-12-31";
            $titulo_periodo = sprintf(__('Año %d', 'flavor-chat-ia'), $ano);
        } else {
            $mes_inicio = (($trimestre - 1) * 3) + 1;
            $mes_fin = $trimestre * 3;
            $desde = sprintf('%d-%02d-01', $ano, $mes_inicio);
            $ultimo_dia = (int) date('t', strtotime($desde . ' +2 months'));
            $hasta = sprintf('%d-%02d-%02d', $ano, $mes_fin, $ultimo_dia);
            $titulo_periodo = sprintf(__('%dT %d', 'flavor-chat-ia'), $trimestre, $ano);
        }

        // Obtener datos del periodo
        $datos_iva = $this->obtener_datos_modelo_iva($desde, $hasta);
        $desglose_modulo = $this->obtener_desglose_modulo($desde, $hasta);
        $desglose_ingresos = $this->obtener_desglose_categoria($desde, $hasta, 'ingreso');
        $desglose_gastos = $this->obtener_desglose_categoria($desde, $hasta, 'gasto');
        $simbolo = $this->get_setting('simbolo_moneda', '€');

        ?>
        <div class="wrap flavor-modulo-page">
            <?php $this->render_page_header(__('Informes Contables', 'flavor-chat-ia')); ?>

            <!-- Selector de periodo -->
            <div style="background:#fff;padding:16px 20px;border-radius:8px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                <form method="get" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
                    <input type="hidden" name="page" value="contabilidad-informes" />

                    <div>
                        <label style="display:block;font-size:11px;color:#666;margin-bottom:4px;"><?php esc_html_e('Tipo de informe', 'flavor-chat-ia'); ?></label>
                        <select name="tipo">
                            <option value="trimestral" <?php selected($tipo_informe, 'trimestral'); ?>><?php esc_html_e('Trimestral (Modelo 303)', 'flavor-chat-ia'); ?></option>
                            <option value="anual" <?php selected($tipo_informe, 'anual'); ?>><?php esc_html_e('Anual (Modelo 390)', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block;font-size:11px;color:#666;margin-bottom:4px;"><?php esc_html_e('Año', 'flavor-chat-ia'); ?></label>
                        <select name="ano">
                            <?php for ($a = (int) date('Y'); $a >= (int) date('Y') - 5; $a--): ?>
                            <option value="<?php echo esc_attr($a); ?>" <?php selected($ano, $a); ?>><?php echo esc_html($a); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <?php if ($tipo_informe === 'trimestral'): ?>
                    <div>
                        <label style="display:block;font-size:11px;color:#666;margin-bottom:4px;"><?php esc_html_e('Trimestre', 'flavor-chat-ia'); ?></label>
                        <select name="trimestre">
                            <option value="1" <?php selected($trimestre, 1); ?>>1T (Ene-Mar)</option>
                            <option value="2" <?php selected($trimestre, 2); ?>>2T (Abr-Jun)</option>
                            <option value="3" <?php selected($trimestre, 3); ?>>3T (Jul-Sep)</option>
                            <option value="4" <?php selected($trimestre, 4); ?>>4T (Oct-Dic)</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-chart-bar" style="vertical-align:middle;"></span>
                        <?php esc_html_e('Generar informe', 'flavor-chat-ia'); ?>
                    </button>

                    <a href="<?php echo esc_url(add_query_arg('export_pdf', '1')); ?>" class="button" style="margin-left:auto;">
                        <span class="dashicons dashicons-pdf"></span>
                        <?php esc_html_e('Exportar PDF', 'flavor-chat-ia'); ?>
                    </a>
                </form>
            </div>

            <!-- Informe IVA (Modelo 303/390) -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 20px;font-size:18px;display:flex;align-items:center;gap:8px;">
                        <span class="dashicons dashicons-media-spreadsheet" style="color:#8b5cf6;"></span>
                        <?php printf(esc_html__('Liquidación IVA - %s', 'flavor-chat-ia'), esc_html($titulo_periodo)); ?>
                    </h3>

                    <table class="widefat" style="border:none;">
                        <tbody>
                            <tr style="background:#f0fdf4;">
                                <td colspan="2" style="padding:12px;font-weight:600;color:#166534;">
                                    <?php esc_html_e('IVA Repercutido (Ventas)', 'flavor-chat-ia'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;"><?php esc_html_e('Base imponible ventas', 'flavor-chat-ia'); ?></td>
                                <td style="padding:10px 12px;text-align:right;font-weight:500;"><?php echo esc_html($this->format_money($datos_iva['base_ventas'])); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;"><?php esc_html_e('IVA repercutido', 'flavor-chat-ia'); ?></td>
                                <td style="padding:10px 12px;text-align:right;font-weight:500;color:#10b981;"><?php echo esc_html($this->format_money($datos_iva['iva_repercutido'])); ?></td>
                            </tr>

                            <tr style="background:#fef2f2;">
                                <td colspan="2" style="padding:12px;font-weight:600;color:#991b1b;">
                                    <?php esc_html_e('IVA Soportado (Compras)', 'flavor-chat-ia'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;"><?php esc_html_e('Base imponible compras', 'flavor-chat-ia'); ?></td>
                                <td style="padding:10px 12px;text-align:right;font-weight:500;"><?php echo esc_html($this->format_money($datos_iva['base_compras'])); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;"><?php esc_html_e('IVA soportado deducible', 'flavor-chat-ia'); ?></td>
                                <td style="padding:10px 12px;text-align:right;font-weight:500;color:#ef4444;"><?php echo esc_html($this->format_money($datos_iva['iva_soportado'])); ?></td>
                            </tr>

                            <tr style="background:#eff6ff;">
                                <td style="padding:14px 12px;font-weight:700;font-size:15px;"><?php esc_html_e('Resultado a liquidar', 'flavor-chat-ia'); ?></td>
                                <td style="padding:14px 12px;text-align:right;font-weight:700;font-size:18px;color:<?php echo $datos_iva['resultado_iva'] >= 0 ? '#1e40af' : '#dc2626'; ?>;">
                                    <?php echo esc_html($this->format_money($datos_iva['resultado_iva'])); ?>
                                    <?php if ($datos_iva['resultado_iva'] >= 0): ?>
                                        <span style="font-size:12px;font-weight:400;">(<?php esc_html_e('A ingresar', 'flavor-chat-ia'); ?>)</span>
                                    <?php else: ?>
                                        <span style="font-size:12px;font-weight:400;">(<?php esc_html_e('A compensar', 'flavor-chat-ia'); ?>)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Cuenta de resultados -->
                <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0 0 20px;font-size:18px;display:flex;align-items:center;gap:8px;">
                        <span class="dashicons dashicons-chart-line" style="color:#3b82f6;"></span>
                        <?php printf(esc_html__('Cuenta de Resultados - %s', 'flavor-chat-ia'), esc_html($titulo_periodo)); ?>
                    </h3>

                    <table class="widefat" style="border:none;">
                        <tbody>
                            <tr>
                                <td style="padding:10px 12px;"><?php esc_html_e('Total ingresos', 'flavor-chat-ia'); ?></td>
                                <td style="padding:10px 12px;text-align:right;font-weight:500;color:#10b981;">+<?php echo esc_html($this->format_money($datos_iva['total_ingresos'])); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;"><?php esc_html_e('Total gastos', 'flavor-chat-ia'); ?></td>
                                <td style="padding:10px 12px;text-align:right;font-weight:500;color:#ef4444;">-<?php echo esc_html($this->format_money($datos_iva['total_gastos'])); ?></td>
                            </tr>
                            <tr style="border-top:2px solid #e5e7eb;">
                                <td style="padding:12px;font-weight:600;"><?php esc_html_e('Resultado bruto', 'flavor-chat-ia'); ?></td>
                                <td style="padding:12px;text-align:right;font-weight:700;font-size:16px;color:<?php echo $datos_iva['resultado_bruto'] >= 0 ? '#10b981' : '#ef4444'; ?>;">
                                    <?php echo esc_html($this->format_money($datos_iva['resultado_bruto'])); ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;color:#666;"><?php esc_html_e('(-) IVA neto a liquidar', 'flavor-chat-ia'); ?></td>
                                <td style="padding:10px 12px;text-align:right;color:#666;"><?php echo esc_html($this->format_money($datos_iva['resultado_iva'])); ?></td>
                            </tr>
                            <tr style="background:#f8fafc;">
                                <td style="padding:14px 12px;font-weight:700;font-size:15px;"><?php esc_html_e('Resultado neto estimado', 'flavor-chat-ia'); ?></td>
                                <td style="padding:14px 12px;text-align:right;font-weight:700;font-size:18px;color:<?php echo ($datos_iva['resultado_bruto'] - $datos_iva['resultado_iva']) >= 0 ? '#059669' : '#dc2626'; ?>;">
                                    <?php echo esc_html($this->format_money($datos_iva['resultado_bruto'] - max(0, $datos_iva['resultado_iva']))); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p style="margin-top:12px;font-size:12px;color:#666;">
                        <?php esc_html_e('Nota: Este es un cálculo simplificado. Consulta con tu asesor fiscal.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
            </div>

            <!-- Desgloses detallados -->
            <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:20px;">
                <!-- Desglose por módulo -->
                <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h4 style="margin:0 0 16px;font-size:15px;font-weight:600;">
                        <span class="dashicons dashicons-admin-plugins" style="color:#6366f1;"></span>
                        <?php esc_html_e('Por módulo', 'flavor-chat-ia'); ?>
                    </h4>
                    <?php if (!empty($desglose_modulo)): ?>
                    <table class="widefat" style="border:none;font-size:13px;">
                        <thead>
                            <tr>
                                <th style="padding:6px 8px;"><?php esc_html_e('Módulo', 'flavor-chat-ia'); ?></th>
                                <th style="padding:6px 8px;text-align:right;"><?php esc_html_e('Ing.', 'flavor-chat-ia'); ?></th>
                                <th style="padding:6px 8px;text-align:right;"><?php esc_html_e('Gas.', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($desglose_modulo as $mod): ?>
                            <tr>
                                <td style="padding:6px 8px;"><?php echo esc_html(ucfirst($mod->modulo_origen ?: 'manual')); ?></td>
                                <td style="padding:6px 8px;text-align:right;color:#10b981;"><?php echo esc_html($this->format_money($mod->ingresos)); ?></td>
                                <td style="padding:6px 8px;text-align:right;color:#ef4444;"><?php echo esc_html($this->format_money($mod->gastos)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="color:#666;text-align:center;"><?php esc_html_e('Sin datos', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Desglose ingresos -->
                <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h4 style="margin:0 0 16px;font-size:15px;font-weight:600;color:#10b981;">
                        <span class="dashicons dashicons-arrow-down-alt"></span>
                        <?php esc_html_e('Ingresos por categoría', 'flavor-chat-ia'); ?>
                    </h4>
                    <?php if (!empty($desglose_ingresos)): ?>
                    <ul style="list-style:none;margin:0;padding:0;">
                        <?php foreach ($desglose_ingresos as $cat): ?>
                        <li style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f3f4f6;">
                            <span><?php echo esc_html(ucfirst($cat->categoria ?: 'otros')); ?></span>
                            <span style="font-weight:500;"><?php echo esc_html($this->format_money($cat->total)); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p style="color:#666;text-align:center;"><?php esc_html_e('Sin datos', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Desglose gastos -->
                <div style="background:#fff;padding:20px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h4 style="margin:0 0 16px;font-size:15px;font-weight:600;color:#ef4444;">
                        <span class="dashicons dashicons-arrow-up-alt"></span>
                        <?php esc_html_e('Gastos por categoría', 'flavor-chat-ia'); ?>
                    </h4>
                    <?php if (!empty($desglose_gastos)): ?>
                    <ul style="list-style:none;margin:0;padding:0;">
                        <?php foreach ($desglose_gastos as $cat): ?>
                        <li style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f3f4f6;">
                            <span><?php echo esc_html(ucfirst($cat->categoria ?: 'otros')); ?></span>
                            <span style="font-weight:500;"><?php echo esc_html($this->format_money($cat->total)); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p style="color:#666;text-align:center;"><?php esc_html_e('Sin datos', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Enlace a movimientos del periodo -->
            <div style="margin-top:20px;text-align:center;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&desde=' . $desde . '&hasta=' . $hasta)); ?>" class="button">
                    <?php esc_html_e('Ver todos los movimientos del periodo →', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene datos para el modelo de IVA.
     *
     * @param string $desde
     * @param string $hasta
     * @return array
     */
    private function obtener_datos_modelo_iva($desde, $hasta) {
        global $wpdb;

        $empresa_id = $this->get_empresa_usuario();
        $filtro_empresa = $empresa_id ? $wpdb->prepare(" AND empresa_id = %d", $empresa_id) : "";

        // Bases imponibles e IVA de ingresos
        $row_ingresos = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COALESCE(SUM(base_imponible), 0) as base,
                COALESCE(SUM(iva_importe), 0) as iva,
                COALESCE(SUM(total), 0) as total
             FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = 'ingreso'
             AND fecha_movimiento BETWEEN %s AND %s" . $filtro_empresa,
            $desde, $hasta
        ));

        // Bases imponibles e IVA de gastos
        $row_gastos = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COALESCE(SUM(base_imponible), 0) as base,
                COALESCE(SUM(iva_importe), 0) as iva,
                COALESCE(SUM(total), 0) as total
             FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = 'gasto'
             AND fecha_movimiento BETWEEN %s AND %s" . $filtro_empresa,
            $desde, $hasta
        ));

        $base_ventas = (float) ($row_ingresos->base ?? 0);
        $iva_repercutido = (float) ($row_ingresos->iva ?? 0);
        $total_ingresos = (float) ($row_ingresos->total ?? 0);

        $base_compras = (float) ($row_gastos->base ?? 0);
        $iva_soportado = (float) ($row_gastos->iva ?? 0);
        $total_gastos = (float) ($row_gastos->total ?? 0);

        return [
            'base_ventas' => $base_ventas,
            'iva_repercutido' => $iva_repercutido,
            'total_ingresos' => $total_ingresos,
            'base_compras' => $base_compras,
            'iva_soportado' => $iva_soportado,
            'total_gastos' => $total_gastos,
            'resultado_iva' => $iva_repercutido - $iva_soportado,
            'resultado_bruto' => $total_ingresos - $total_gastos,
        ];
    }

    public function get_estadisticas_dashboard() {
        $actual = $this->obtener_estadisticas_periodo('mes', 0);

        return [
            [
                'icon' => 'dashicons-arrow-up-alt',
                'valor' => $this->format_money($actual['ingresos']),
                'label' => __('Ingresos mes', 'flavor-chat-ia'),
                'color' => 'green',
                'enlace' => admin_url('admin.php?page=contabilidad-dashboard&periodo=mes'),
            ],
            [
                'icon' => 'dashicons-arrow-down-alt',
                'valor' => $this->format_money($actual['gastos']),
                'label' => __('Gastos mes', 'flavor-chat-ia'),
                'color' => 'orange',
                'enlace' => admin_url('admin.php?page=contabilidad-dashboard&periodo=mes'),
            ],
            [
                'icon' => 'dashicons-chart-line',
                'valor' => $this->format_money($actual['resultado']),
                'label' => __('Resultado', 'flavor-chat-ia'),
                'color' => $actual['resultado'] >= 0 ? 'blue' : 'red',
                'enlace' => admin_url('admin.php?page=contabilidad-dashboard&periodo=mes'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_movimientos' => [
                'description' => __('Listar movimientos contables con filtros', 'flavor-chat-ia'),
                'params' => ['desde', 'hasta', 'tipo', 'estado', 'modulo_origen', 'limite'],
            ],
            'registrar_movimiento' => [
                'description' => __('Registrar un movimiento contable', 'flavor-chat-ia'),
                'params' => ['tipo_movimiento', 'concepto', 'base_imponible', 'iva_porcentaje', 'fecha_movimiento', 'categoria'],
            ],
            'estadisticas' => [
                'description' => __('Obtener resumen contable por periodo', 'flavor-chat-ia'),
                'params' => ['periodo', 'offset'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'listar_movimientos',
            'movimientos' => 'listar_movimientos',
            'nuevo' => 'registrar_movimiento',
            'crear' => 'registrar_movimiento',
            'stats' => 'estadisticas',
            'resumen' => 'estadisticas',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $method = 'action_' . $action_name;

        if (method_exists($this, $method)) {
            return $this->$method((array) $params);
        }

        return [
            'success' => false,
            'error' => __('Acción no disponible en Contabilidad.', 'flavor-chat-ia'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'contabilidad_listar_movimientos',
                'description' => 'Lista movimientos contables con filtros opcionales.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'desde' => ['type' => 'string', 'description' => 'Fecha inicio (YYYY-MM-DD)'],
                        'hasta' => ['type' => 'string', 'description' => 'Fecha fin (YYYY-MM-DD)'],
                        'tipo' => ['type' => 'string', 'enum' => ['ingreso', 'gasto', 'ajuste']],
                        'estado' => ['type' => 'string', 'enum' => ['borrador', 'confirmado', 'anulado']],
                        'limite' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 500],
                    ],
                ],
            ],
            [
                'name' => 'contabilidad_estadisticas',
                'description' => 'Obtiene estadisticas de contabilidad por periodo.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'periodo' => ['type' => 'string', 'enum' => ['mes', 'trimestre', 'ano']],
                        'offset' => ['type' => 'integer', 'description' => '0 actual, -1 anterior, etc.'],
                    ],
                ],
            ],
            [
                'name' => 'contabilidad_registrar_movimiento',
                'description' => 'Registra un ingreso o gasto manual.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo_movimiento' => ['type' => 'string', 'enum' => ['ingreso', 'gasto', 'ajuste']],
                        'concepto' => ['type' => 'string'],
                        'categoria' => ['type' => 'string'],
                        'fecha_movimiento' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'base_imponible' => ['type' => 'number'],
                        'iva_porcentaje' => ['type' => 'number'],
                        'tercero_nombre' => ['type' => 'string'],
                    ],
                    'required' => ['tipo_movimiento', 'concepto', 'base_imponible'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        $stats = $this->obtener_estadisticas_periodo('mes', 0);

        return sprintf(
            __("Módulo de Contabilidad:\n- Ingresos del mes: %s\n- Gastos del mes: %s\n- Resultado del mes: %s\n- IVA repercutido: %s\n- IVA soportado: %s\n\nPermite registrar movimientos manuales y automáticos, consolidar módulos y analizar periodos mensuales, trimestrales y anuales.", 'flavor-chat-ia'),
            $this->format_money($stats['ingresos']),
            $this->format_money($stats['gastos']),
            $this->format_money($stats['resultado']),
            $this->format_money($stats['iva_repercutido']),
            $this->format_money($stats['iva_soportado'])
        );
    }

    /**
     * Lista movimientos con filtros.
     *
     * @param array $params
     * @return array
     */
    private function action_listar_movimientos($params) {
        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_movimientos)) {
            return ['success' => false, 'error' => __('Tabla de contabilidad no disponible.', 'flavor-chat-ia')];
        }

        $desde = isset($params['desde']) ? sanitize_text_field((string) $params['desde']) : '';
        $hasta = isset($params['hasta']) ? sanitize_text_field((string) $params['hasta']) : '';
        $tipo = isset($params['tipo']) ? sanitize_key((string) $params['tipo']) : '';
        $estado = isset($params['estado']) ? sanitize_key((string) $params['estado']) : 'confirmado';
        $modulo_origen = isset($params['modulo_origen']) ? sanitize_key((string) $params['modulo_origen']) : '';
        $limite = isset($params['limite']) ? absint($params['limite']) : 50;
        if ($limite < 1 || $limite > 500) {
            $limite = 50;
        }

        $where = ['1=1'];
        $sql_params = [];

        if ($desde !== '') {
            $where[] = 'fecha_movimiento >= %s';
            $sql_params[] = $desde;
        }
        if ($hasta !== '') {
            $where[] = 'fecha_movimiento <= %s';
            $sql_params[] = $hasta;
        }
        if (in_array($tipo, ['ingreso', 'gasto', 'ajuste'], true)) {
            $where[] = 'tipo_movimiento = %s';
            $sql_params[] = $tipo;
        }
        if (in_array($estado, ['borrador', 'confirmado', 'anulado'], true)) {
            $where[] = 'estado = %s';
            $sql_params[] = $estado;
        }
        if ($modulo_origen !== '') {
            $where[] = 'modulo_origen = %s';
            $sql_params[] = $modulo_origen;
        }

        $sql = "SELECT id, fecha_movimiento, tipo_movimiento, estado, concepto, categoria, modulo_origen, total, moneda
                FROM {$this->tabla_movimientos}
                WHERE " . implode(' AND ', $where) . '
                ORDER BY fecha_movimiento DESC, id DESC
                LIMIT %d';
        $sql_params[] = $limite;

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$sql_params));

        return [
            'success' => true,
            'total' => count((array) $rows),
            'items' => array_map(function($row) {
                return [
                    'id' => (int) $row->id,
                    'fecha' => (string) $row->fecha_movimiento,
                    'tipo' => (string) $row->tipo_movimiento,
                    'estado' => (string) $row->estado,
                    'concepto' => (string) $row->concepto,
                    'categoria' => (string) $row->categoria,
                    'modulo_origen' => (string) ($row->modulo_origen ?: 'manual'),
                    'total' => (float) $row->total,
                    'moneda' => (string) ($row->moneda ?: $this->get_setting('moneda', 'EUR')),
                ];
            }, (array) $rows),
        ];
    }

    /**
     * Registra un movimiento manual.
     *
     * @param array $params
     * @return array
     */
    private function action_registrar_movimiento($params) {
        if (!current_user_can('manage_options')) {
            return ['success' => false, 'error' => __('Sin permisos para registrar movimientos.', 'flavor-chat-ia')];
        }

        $base = (float) ($params['base_imponible'] ?? 0);
        $iva_porcentaje = (float) ($params['iva_porcentaje'] ?? 0);
        $iva_importe = round($base * ($iva_porcentaje / 100), 2);
        $total = isset($params['total']) ? (float) $params['total'] : ($base + $iva_importe);

        $id = $this->registrar_movimiento([
            'fecha_movimiento' => sanitize_text_field((string) ($params['fecha_movimiento'] ?? current_time('Y-m-d'))),
            'tipo_movimiento' => sanitize_key((string) ($params['tipo_movimiento'] ?? 'gasto')),
            'estado' => sanitize_key((string) ($params['estado'] ?? 'confirmado')),
            'concepto' => sanitize_text_field((string) ($params['concepto'] ?? 'Movimiento manual')),
            'categoria' => sanitize_text_field((string) ($params['categoria'] ?? 'otros')),
            'subcategoria' => sanitize_text_field((string) ($params['subcategoria'] ?? '')),
            'modulo_origen' => sanitize_key((string) ($params['modulo_origen'] ?? 'manual')),
            'tercero_nombre' => sanitize_text_field((string) ($params['tercero_nombre'] ?? '')),
            'base_imponible' => $base,
            'iva_porcentaje' => $iva_porcentaje,
            'iva_importe' => $iva_importe,
            'total' => $total,
            'metadata' => ['origen' => 'tool_action'],
        ]);

        if ($id <= 0) {
            return ['success' => false, 'error' => __('No se pudo registrar el movimiento.', 'flavor-chat-ia')];
        }

        return ['success' => true, 'id' => (int) $id];
    }

    /**
     * Devuelve estadisticas de un periodo.
     *
     * @param array $params
     * @return array
     */
    private function action_estadisticas($params) {
        $periodo = sanitize_key((string) ($params['periodo'] ?? 'mes'));
        if (!in_array($periodo, ['mes', 'trimestre', 'ano'], true)) {
            $periodo = 'mes';
        }
        $offset = isset($params['offset']) ? (int) $params['offset'] : 0;
        $modulo_origen = isset($params['modulo_origen']) ? sanitize_key((string) $params['modulo_origen']) : '';
        $stats = $this->obtener_estadisticas_periodo($periodo, $offset, $modulo_origen);

        return [
            'success' => true,
            'periodo' => $periodo,
            'desde' => $stats['desde'],
            'hasta' => $stats['hasta'],
            'ingresos' => (float) $stats['ingresos'],
            'gastos' => (float) $stats['gastos'],
            'resultado' => (float) $stats['resultado'],
            'iva_repercutido' => (float) $stats['iva_repercutido'],
            'iva_soportado' => (float) $stats['iva_soportado'],
        ];
    }

    private function obtener_estadisticas_periodo($periodo = 'mes', $offset_periodos = 0, $modulo_origen = '') {
        global $wpdb;

        [$desde, $hasta] = $this->obtener_rango_periodo($periodo, $offset_periodos);

        // Filtro por empresa y módulo
        $filtro_extra_sql = '';
        $filtro_extra_params = [];

        $empresa_id = $this->get_empresa_usuario();
        if ($empresa_id) {
            $filtro_extra_sql .= ' AND empresa_id = %d';
            $filtro_extra_params[] = $empresa_id;
        }

        if ($modulo_origen !== '') {
            $filtro_extra_sql .= ' AND modulo_origen = %s';
            $filtro_extra_params[] = $modulo_origen;
        }

        $ingresos = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total),0) FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = 'ingreso' AND fecha_movimiento BETWEEN %s AND %s{$filtro_extra_sql}",
            ...array_merge([$desde, $hasta], $filtro_extra_params)
        ));

        $gastos = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total),0) FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = 'gasto' AND fecha_movimiento BETWEEN %s AND %s{$filtro_extra_sql}",
            ...array_merge([$desde, $hasta], $filtro_extra_params)
        ));

        $iva_repercutido = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(iva_importe),0) FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = 'ingreso' AND fecha_movimiento BETWEEN %s AND %s{$filtro_extra_sql}",
            ...array_merge([$desde, $hasta], $filtro_extra_params)
        ));

        $iva_soportado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(iva_importe),0) FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = 'gasto' AND fecha_movimiento BETWEEN %s AND %s{$filtro_extra_sql}",
            ...array_merge([$desde, $hasta], $filtro_extra_params)
        ));

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'resultado' => $ingresos - $gastos,
            'iva_repercutido' => $iva_repercutido,
            'iva_soportado' => $iva_soportado,
        ];
    }

    private function obtener_rango_periodo($periodo, $offset_periodos = 0) {
        $hoy = new DateTimeImmutable(current_time('Y-m-d'));

        if ($periodo === 'ano') {
            $fecha_ref = $hoy->modify(($offset_periodos >= 0 ? '+' : '') . $offset_periodos . ' year');
            $desde = $fecha_ref->setDate((int) $fecha_ref->format('Y'), 1, 1);
            $hasta = $fecha_ref->setDate((int) $fecha_ref->format('Y'), 12, 31);
            return [$desde->format('Y-m-d'), $hasta->format('Y-m-d')];
        }

        if ($periodo === 'trimestre') {
            $mes_actual = (int) $hoy->format('n');
            $inicio_trim = ((int) floor(($mes_actual - 1) / 3) * 3) + 1;
            $base = $hoy->setDate((int) $hoy->format('Y'), $inicio_trim, 1);
            $fecha_ref = $base->modify(($offset_periodos >= 0 ? '+' : '') . ($offset_periodos * 3) . ' months');
            $desde = $fecha_ref;
            $hasta = $fecha_ref->modify('+2 months')->modify('last day of this month');
            return [$desde->format('Y-m-d'), $hasta->format('Y-m-d')];
        }

        $base = $hoy->modify('first day of this month');
        $fecha_ref = $base->modify(($offset_periodos >= 0 ? '+' : '') . $offset_periodos . ' month');
        $desde = $fecha_ref;
        $hasta = $fecha_ref->modify('last day of this month');

        return [$desde->format('Y-m-d'), $hasta->format('Y-m-d')];
    }

    private function obtener_desglose_modulo($desde, $hasta, $modulo_origen = '') {
        global $wpdb;

        $where = "estado='confirmado' AND fecha_movimiento BETWEEN %s AND %s";
        $params = [$desde, $hasta];

        $empresa_id = $this->get_empresa_usuario();
        if ($empresa_id) {
            $where .= ' AND empresa_id = %d';
            $params[] = $empresa_id;
        }

        if ($modulo_origen !== '') {
            $where .= ' AND modulo_origen = %s';
            $params[] = $modulo_origen;
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT modulo_origen,
                    SUM(CASE WHEN tipo_movimiento='ingreso' THEN total ELSE 0 END) AS ingresos,
                    SUM(CASE WHEN tipo_movimiento='gasto' THEN total ELSE 0 END) AS gastos
             FROM {$this->tabla_movimientos}
             WHERE {$where}
             GROUP BY modulo_origen
             ORDER BY modulo_origen ASC",
            ...$params
        ));
    }

    /**
     * Obtiene métricas de facturas en el periodo contable.
     *
     * @param string $desde
     * @param string $hasta
     * @return array<string, int|float>
     */
    private function obtener_metricas_facturas_periodo($desde, $hasta, $modulo_origen = '') {
        global $wpdb;

        $filtro_modulo_sql = '';
        $filtro_modulo_params = [];
        if ($modulo_origen !== '') {
            $filtro_modulo_sql = ' AND modulo_origen = %s';
            $filtro_modulo_params[] = $modulo_origen;
        }

        $emitido_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_movimientos}
             WHERE estado IN ('borrador','confirmado') AND entidad_tipo='factura' AND fecha_movimiento BETWEEN %s AND %s{$filtro_modulo_sql}",
            ...array_merge([$desde, $hasta], $filtro_modulo_params)
        ));

        $emitido_total = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total),0) FROM {$this->tabla_movimientos}
             WHERE estado IN ('borrador','confirmado') AND entidad_tipo='factura' AND fecha_movimiento BETWEEN %s AND %s{$filtro_modulo_sql}",
            ...array_merge([$desde, $hasta], $filtro_modulo_params)
        ));

        $cobrado_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_movimientos}
             WHERE estado='confirmado' AND entidad_tipo='pago_factura' AND fecha_movimiento BETWEEN %s AND %s{$filtro_modulo_sql}",
            ...array_merge([$desde, $hasta], $filtro_modulo_params)
        ));

        $cobrado_total = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total),0) FROM {$this->tabla_movimientos}
             WHERE estado='confirmado' AND entidad_tipo='pago_factura' AND fecha_movimiento BETWEEN %s AND %s{$filtro_modulo_sql}",
            ...array_merge([$desde, $hasta], $filtro_modulo_params)
        ));

        return [
            'emitido_count' => $emitido_count,
            'emitido_total' => $emitido_total,
            'cobrado_count' => $cobrado_count,
            'cobrado_total' => $cobrado_total,
        ];
    }

    /**
     * Devuelve lista de módulos origen presentes en movimientos.
     *
     * @return array<int, string>
     */
    private function obtener_modulos_origen_disponibles() {
        global $wpdb;

        $rows = $wpdb->get_col(
            "SELECT DISTINCT modulo_origen FROM {$this->tabla_movimientos}
             WHERE modulo_origen IS NOT NULL AND modulo_origen != ''
             ORDER BY modulo_origen ASC"
        );

        $modulos = array_values(array_filter(array_map('strval', (array) $rows)));

        // Fallback: incluir módulos registrados aunque todavía no tengan movimientos.
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $registered = (array) $loader->get_registered_modules();
            if (!empty($registered)) {
                foreach (array_keys($registered) as $module_id) {
                    $module_id = sanitize_key((string) $module_id);
                    if ($module_id !== '' && $module_id !== 'contabilidad') {
                        $modulos[] = $module_id;
                    }
                }
            }
        }

        $modulos = array_values(array_unique($modulos));
        sort($modulos, SORT_NATURAL | SORT_FLAG_CASE);

        return $modulos;
    }

    /**
     * Obtiene filtros de movimientos desde query string.
     *
     * @return array<string, string>
     */
    private function obtener_filtros_movimientos_desde_request() {
        return [
            'desde' => isset($_GET['desde']) ? sanitize_text_field((string) $_GET['desde']) : '',
            'hasta' => isset($_GET['hasta']) ? sanitize_text_field((string) $_GET['hasta']) : '',
            'tipo' => isset($_GET['tipo']) ? sanitize_key((string) $_GET['tipo']) : '',
            'modulo_origen' => isset($_GET['modulo_origen']) ? sanitize_key((string) $_GET['modulo_origen']) : '',
        ];
    }

    /**
     * Devuelve SQL WHERE y parámetros para listar movimientos.
     *
     * @param array<string, string> $filtros
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function construir_where_movimientos($filtros) {
        $desde = (string) ($filtros['desde'] ?? '');
        $hasta = (string) ($filtros['hasta'] ?? '');
        $tipo = (string) ($filtros['tipo'] ?? '');
        $modulo_origen = (string) ($filtros['modulo_origen'] ?? '');

        $where = ['1=1'];
        $params = [];

        // Filtrar por empresa del usuario actual
        $empresa_id = $this->get_empresa_usuario();
        if ($empresa_id) {
            $where[] = 'empresa_id = %d';
            $params[] = $empresa_id;
        }

        if ($desde !== '') {
            $where[] = 'fecha_movimiento >= %s';
            $params[] = $desde;
        }
        if ($hasta !== '') {
            $where[] = 'fecha_movimiento <= %s';
            $params[] = $hasta;
        }
        if (in_array($tipo, ['ingreso', 'gasto', 'ajuste'], true)) {
            $where[] = 'tipo_movimiento = %s';
            $params[] = $tipo;
        }
        if ($modulo_origen !== '') {
            $where[] = 'modulo_origen = %s';
            $params[] = $modulo_origen;
        }

        return [$where, $params];
    }

    /**
     * Lista movimientos según filtros.
     *
     * @param array<string, string> $filtros
     * @param int                   $limit
     * @return array<int, object>
     */
    private function obtener_movimientos_filtrados($filtros, $limit = 300) {
        global $wpdb;

        $limit = absint($limit);
        if ($limit < 1) {
            $limit = 300;
        }

        [$where, $params] = $this->construir_where_movimientos($filtros);
        $sql = "SELECT * FROM {$this->tabla_movimientos} WHERE " . implode(' AND ', $where) . ' ORDER BY fecha_movimiento DESC, id DESC LIMIT %d';
        $params[] = $limit;

        return (array) $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    /**
     * Exporta a CSV la lista de movimientos con los filtros actuales.
     *
     * @param array<string, string> $filtros
     * @return void
     */
    private function exportar_movimientos_csv($filtros) {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sin permisos para exportar.', 'flavor-chat-ia'));
        }

        $rows = $this->obtener_movimientos_filtrados($filtros, 2000);
        $filename = 'contabilidad-movimientos-' . gmdate('Ymd-His') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('No se pudo generar el CSV.', 'flavor-chat-ia'));
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['id', 'fecha_movimiento', 'tipo_movimiento', 'estado', 'concepto', 'categoria', 'subcategoria', 'modulo_origen', 'base_imponible', 'iva_importe', 'total', 'moneda']);

        foreach ($rows as $row) {
            fputcsv($output, [
                (int) $row->id,
                (string) $row->fecha_movimiento,
                (string) $row->tipo_movimiento,
                (string) $row->estado,
                (string) $row->concepto,
                (string) $row->categoria,
                (string) $row->subcategoria,
                (string) ($row->modulo_origen ?: 'manual'),
                (float) $row->base_imponible,
                (float) $row->iva_importe,
                (float) $row->total,
                (string) ($row->moneda ?: $this->get_setting('moneda', 'EUR')),
            ]);
        }

        fclose($output);
    }

    private function format_money($amount) {
        $decimales = 2;
        $simbolo = (string) $this->get_setting('simbolo_moneda', '€');
        return number_format((float) $amount, $decimales, ',', '.') . ' ' . $simbolo;
    }

    private function render_stat_card($label, $value, $color = 'blue') {
        echo '<div class="flavor-stat-card flavor-stat-' . esc_attr($color) . '">';
        echo '<span class="stat-number">' . esc_html($this->format_money((float) $value)) . '</span>';
        echo '<span class="stat-label">' . esc_html((string) $label) . '</span>';
        echo '</div>';
    }

    private function render_compare_row($label, $actual, $anterior) {
        $variacion = ((float) $anterior !== 0.0) ? (((float) $actual - (float) $anterior) / abs((float) $anterior) * 100) : 0;
        echo '<tr>';
        echo '<td>' . esc_html((string) $label) . '</td>';
        echo '<td>' . esc_html($this->format_money((float) $actual)) . '</td>';
        echo '<td>' . esc_html($this->format_money((float) $anterior)) . '</td>';
        echo '<td>' . esc_html(number_format($variacion, 2, ',', '.') . '%') . '</td>';
        echo '</tr>';
    }

    /**
     * Obtiene evolución mensual para gráficos (últimos 12 meses).
     *
     * @param string $modulo_origen Filtro opcional por módulo
     * @return array
     */
    private function obtener_evolucion_mensual($modulo_origen = '') {
        global $wpdb;

        $datos = [];
        $hoy = new DateTimeImmutable(current_time('Y-m-d'));
        $empresa_id = $this->get_empresa_usuario();

        for ($i = 11; $i >= 0; $i--) {
            $fecha = $hoy->modify("-{$i} months");
            $desde = $fecha->modify('first day of this month')->format('Y-m-d');
            $hasta = $fecha->modify('last day of this month')->format('Y-m-d');
            $mes_label = $fecha->format('M Y');

            $filtro_extra = '';
            $params = [$desde, $hasta];

            if ($empresa_id) {
                $filtro_extra .= ' AND empresa_id = %d';
                $params[] = $empresa_id;
            }

            if ($modulo_origen !== '') {
                $filtro_extra .= ' AND modulo_origen = %s';
                $params[] = $modulo_origen;
            }

            $ingresos = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total),0) FROM {$this->tabla_movimientos}
                 WHERE estado = 'confirmado' AND tipo_movimiento = 'ingreso'
                 AND fecha_movimiento BETWEEN %s AND %s{$filtro_extra}",
                ...$params
            ));

            $params_gastos = [$desde, $hasta];
            if ($empresa_id) {
                $params_gastos[] = $empresa_id;
            }
            if ($modulo_origen !== '') {
                $params_gastos[] = $modulo_origen;
            }

            $gastos = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total),0) FROM {$this->tabla_movimientos}
                 WHERE estado = 'confirmado' AND tipo_movimiento = 'gasto'
                 AND fecha_movimiento BETWEEN %s AND %s{$filtro_extra}",
                ...$params_gastos
            ));

            $datos[] = [
                'mes' => $mes_label,
                'ingresos' => $ingresos,
                'gastos' => $gastos,
                'resultado' => $ingresos - $gastos,
            ];
        }

        return $datos;
    }

    /**
     * Obtiene desglose por categoría para el periodo.
     *
     * @param string $desde
     * @param string $hasta
     * @param string $tipo_movimiento
     * @return array
     */
    private function obtener_desglose_categoria($desde, $hasta, $tipo_movimiento = 'ingreso') {
        global $wpdb;

        $empresa_id = $this->get_empresa_usuario();
        $filtro_empresa = $empresa_id ? $wpdb->prepare(" AND empresa_id = %d", $empresa_id) : "";

        return (array) $wpdb->get_results($wpdb->prepare(
            "SELECT categoria, SUM(total) as total, COUNT(*) as cantidad
             FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = %s
             AND fecha_movimiento BETWEEN %s AND %s" . $filtro_empresa . "
             GROUP BY categoria
             ORDER BY total DESC",
            $tipo_movimiento,
            $desde,
            $hasta
        ));
    }

    /**
     * Obtiene los últimos movimientos.
     *
     * @param int $limit
     * @return array
     */
    private function obtener_ultimos_movimientos($limit = 10) {
        global $wpdb;

        $empresa_id = $this->get_empresa_usuario();
        $filtro_empresa = $empresa_id ? $wpdb->prepare(" AND empresa_id = %d", $empresa_id) : "";

        return (array) $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado'" . $filtro_empresa . "
             ORDER BY fecha_movimiento DESC, id DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Obtiene totales acumulados del año.
     *
     * @return array
     */
    private function obtener_totales_ano() {
        global $wpdb;

        $ano_actual = date('Y');
        $desde = "{$ano_actual}-01-01";
        $hasta = "{$ano_actual}-12-31";

        $empresa_id = $this->get_empresa_usuario();
        $filtro_empresa = $empresa_id ? $wpdb->prepare(" AND empresa_id = %d", $empresa_id) : "";

        $ingresos = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total),0) FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = 'ingreso'
             AND fecha_movimiento BETWEEN %s AND %s" . $filtro_empresa,
            $desde, $hasta
        ));

        $gastos = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total),0) FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = 'gasto'
             AND fecha_movimiento BETWEEN %s AND %s" . $filtro_empresa,
            $desde, $hasta
        ));

        $iva_repercutido = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(iva_importe),0) FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = 'ingreso'
             AND fecha_movimiento BETWEEN %s AND %s" . $filtro_empresa,
            $desde, $hasta
        ));

        $iva_soportado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(iva_importe),0) FROM {$this->tabla_movimientos}
             WHERE estado = 'confirmado' AND tipo_movimiento = 'gasto'
             AND fecha_movimiento BETWEEN %s AND %s" . $filtro_empresa,
            $desde, $hasta
        ));

        return [
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'resultado' => $ingresos - $gastos,
            'iva_repercutido' => $iva_repercutido,
            'iva_soportado' => $iva_soportado,
            'iva_neto' => $iva_repercutido - $iva_soportado,
        ];
    }
}
