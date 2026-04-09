<?php
/**
 * Sistema de Suscripciones para Grupos de Consumo
 *
 * Gestiona suscripciones a cestas de productos con frecuencia configurable.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar suscripciones de cestas
 */
class Flavor_GC_Subscriptions {

    /**
     * Instancia singleton
     * @var Flavor_GC_Subscriptions|null
     */
    private static $instancia = null;

    /**
     * Tablas
     */
    private $tabla_suscripciones;
    private $tabla_cestas_tipo;
    private $tabla_historial;

    /**
     * Hook del cron
     */
    const CRON_HOOK = 'gc_procesar_suscripciones';

    /**
     * Frecuencias disponibles
     */
    const FRECUENCIAS = [
        'semanal' => 7,
        'quincenal' => 14,
        'mensual' => 30,
    ];

    /**
     * Estados de suscripción
     */
    const ESTADOS = ['activa', 'pausada', 'cancelada'];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_suscripciones = $wpdb->prefix . 'flavor_gc_suscripciones';
        $this->tabla_cestas_tipo = $wpdb->prefix . 'flavor_gc_cestas_tipo';
        $this->tabla_historial = $wpdb->prefix . 'flavor_gc_suscripciones_historial';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_GC_Subscriptions
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
        // Cron para procesar suscripciones
        add_action(self::CRON_HOOK, [$this, 'procesar_suscripciones_pendientes']);

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            // Ejecutar diariamente a las 6:00 AM
            $hora_ejecucion = strtotime('tomorrow 06:00:00');
            wp_schedule_event($hora_ejecucion, 'daily', self::CRON_HOOK);
        }

        // AJAX handlers
        add_action('wp_ajax_gc_crear_suscripcion', [$this, 'ajax_crear_suscripcion']);
        add_action('wp_ajax_gc_pausar_suscripcion', [$this, 'ajax_pausar_suscripcion']);
        add_action('wp_ajax_gc_reanudar_suscripcion', [$this, 'ajax_reanudar_suscripcion']);
        add_action('wp_ajax_gc_cancelar_suscripcion', [$this, 'ajax_cancelar_suscripcion']);
        add_action('wp_ajax_gc_cambiar_frecuencia', [$this, 'ajax_cambiar_frecuencia']);
    }

    /**
     * Valida nonces de suscripción con compatibilidad para clientes antiguos.
     *
     * @return void
     */
    private function verificar_nonce_ajax() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        $acciones_validas = [
            'gc_suscripcion_nonce',
            'gc_lista_compra_nonce',
            'gc_nonce',
        ];

        foreach ($acciones_validas as $accion) {
            if ($nonce && wp_verify_nonce($nonce, $accion)) {
                return;
            }
        }

        wp_send_json_error(
            ['mensaje' => __('Token de seguridad invalido. Recarga la pagina e intentalo de nuevo.', 'flavor-platform')],
            403
        );
    }

    /**
     * Resuelve el consumidor del usuario autenticado para operaciones AJAX.
     *
     * Si el ID recibido no pertenece al usuario actual, intenta recuperar su
     * membresia activa mas reciente para evitar fallos por HTML o cache obsoleta.
     *
     * @param int $consumidor_id ID recibido desde el frontend.
     * @return object|null
     */
    private function resolver_consumidor_ajax($consumidor_id) {
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $consumidor = $consumidor_id > 0 ? $consumidor_manager->obtener_por_id($consumidor_id) : null;
        $user_id = get_current_user_id();

        if ($consumidor && (int) $consumidor->usuario_id === $user_id) {
            return $consumidor;
        }

        global $wpdb;
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_consumidores)) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT *
             FROM {$tabla_consumidores}
             WHERE usuario_id = %d
             ORDER BY (estado = 'activo') DESC, fecha_alta DESC, id DESC
             LIMIT 1",
            $user_id
        ));
    }

    /**
     * Obtiene una suscripcion y verifica que pertenezca al usuario actual.
     *
     * @param int $suscripcion_id ID de la suscripcion.
     * @return array{suscripcion:?object, consumidor:?object}
     */
    private function resolver_suscripcion_ajax($suscripcion_id) {
        $suscripcion = $suscripcion_id > 0 ? $this->obtener_suscripcion($suscripcion_id) : null;
        if (!$suscripcion) {
            return ['suscripcion' => null, 'consumidor' => null];
        }

        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $consumidor = $consumidor_manager->obtener_por_id((int) $suscripcion->consumidor_id);

        if (!$consumidor) {
            return ['suscripcion' => $suscripcion, 'consumidor' => null];
        }

        if ((int) $consumidor->usuario_id !== get_current_user_id() && !current_user_can('gc_gestionar_suscripciones')) {
            return ['suscripcion' => $suscripcion, 'consumidor' => null];
        }

        return ['suscripcion' => $suscripcion, 'consumidor' => $consumidor];
    }

    /**
     * Crea una nueva suscripción
     *
     * @param int    $consumidor_id ID del consumidor
     * @param int    $tipo_cesta_id ID del tipo de cesta
     * @param string $frecuencia    Frecuencia (semanal, quincenal, mensual)
     * @param array  $opciones      Opciones adicionales
     * @return array
     */
    public function crear_suscripcion($consumidor_id, $tipo_cesta_id, $frecuencia = 'semanal', $opciones = []) {
        global $wpdb;

        // Validar consumidor
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $consumidor = $consumidor_manager->obtener_por_id($consumidor_id);

        if (!$consumidor || $consumidor->estado !== 'activo') {
            return [
                'success' => false,
                'error' => __('El consumidor no está activo o no existe.', 'flavor-platform'),
            ];
        }

        // Validar tipo de cesta
        $cesta = $this->obtener_tipo_cesta($tipo_cesta_id);
        if (!$cesta || !$cesta->activa) {
            return [
                'success' => false,
                'error' => __('El tipo de cesta no existe o no está disponible.', 'flavor-platform'),
            ];
        }

        // Validar frecuencia
        if (!isset(self::FRECUENCIAS[$frecuencia])) {
            $frecuencia = 'semanal';
        }

        // Verificar si ya tiene suscripción activa a esta cesta
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_suscripciones}
            WHERE consumidor_id = %d AND tipo_cesta_id = %d AND estado = 'activa'",
            $consumidor_id,
            $tipo_cesta_id
        ));

        if ($existente) {
            return [
                'success' => false,
                'error' => __('Ya tienes una suscripción activa a esta cesta.', 'flavor-platform'),
            ];
        }

        // Calcular fecha próximo cargo
        $fecha_inicio = date('Y-m-d');
        $dias_frecuencia = self::FRECUENCIAS[$frecuencia];
        $fecha_proximo_cargo = date('Y-m-d', strtotime("+{$dias_frecuencia} days"));

        // Importe
        $importe = isset($opciones['importe_personalizado'])
            ? floatval($opciones['importe_personalizado'])
            : floatval($cesta->precio_base);

        $datos = [
            'consumidor_id' => $consumidor_id,
            'tipo_cesta_id' => $tipo_cesta_id,
            'frecuencia' => $frecuencia,
            'importe' => $importe,
            'estado' => 'activa',
            'fecha_inicio' => $fecha_inicio,
            'fecha_proximo_cargo' => $fecha_proximo_cargo,
            'metodo_pago' => isset($opciones['metodo_pago']) ? sanitize_text_field($opciones['metodo_pago']) : null,
            'notas' => isset($opciones['notas']) ? sanitize_textarea_field($opciones['notas']) : null,
        ];

        $resultado = $wpdb->insert(
            $this->tabla_suscripciones,
            $datos,
            ['%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al crear la suscripción.', 'flavor-platform'),
            ];
        }

        $suscripcion_id = $wpdb->insert_id;

        // Disparar acción
        do_action('gc_suscripcion_creada', $suscripcion_id, $consumidor_id, $tipo_cesta_id);

        return [
            'success' => true,
            'suscripcion_id' => $suscripcion_id,
            'mensaje' => sprintf(
                __('Suscripción creada correctamente. Próxima cesta: %s', 'flavor-platform'),
                date_i18n(get_option('date_format'), strtotime($fecha_proximo_cargo))
            ),
        ];
    }

    /**
     * Obtiene una suscripción por ID
     *
     * @param int $suscripcion_id ID de la suscripción
     * @return object|null
     */
    public function obtener_suscripcion($suscripcion_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, c.nombre as cesta_nombre, c.precio_base, c.descripcion as cesta_descripcion
            FROM {$this->tabla_suscripciones} s
            LEFT JOIN {$this->tabla_cestas_tipo} c ON s.tipo_cesta_id = c.id
            WHERE s.id = %d",
            $suscripcion_id
        ));
    }

    /**
     * Lista suscripciones de un consumidor
     *
     * @param int   $consumidor_id ID del consumidor
     * @param array $filtros       Filtros opcionales
     * @return array
     */
    public function listar_suscripciones_consumidor($consumidor_id, $filtros = []) {
        global $wpdb;

        $where = 's.consumidor_id = %d';
        $params = [$consumidor_id];

        if (!empty($filtros['estado'])) {
            $where .= ' AND s.estado = %s';
            $params[] = sanitize_text_field($filtros['estado']);
        }

        $suscripciones = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, c.nombre as cesta_nombre, c.precio_base, c.descripcion as cesta_descripcion, c.imagen_id
            FROM {$this->tabla_suscripciones} s
            LEFT JOIN {$this->tabla_cestas_tipo} c ON s.tipo_cesta_id = c.id
            WHERE {$where}
            ORDER BY s.estado = 'activa' DESC, s.fecha_inicio DESC",
            ...$params
        ));

        return $suscripciones;
    }

    /**
     * Pausa una suscripción
     *
     * @param int $suscripcion_id ID de la suscripción
     * @return array
     */
    public function pausar_suscripcion($suscripcion_id) {
        global $wpdb;

        $suscripcion = $this->obtener_suscripcion($suscripcion_id);
        if (!$suscripcion) {
            return [
                'success' => false,
                'error' => __('Suscripción no encontrada.', 'flavor-platform'),
            ];
        }

        if ($suscripcion->estado !== 'activa') {
            return [
                'success' => false,
                'error' => __('Solo se pueden pausar suscripciones activas.', 'flavor-platform'),
            ];
        }

        $resultado = $wpdb->update(
            $this->tabla_suscripciones,
            [
                'estado' => 'pausada',
                'fecha_pausa' => current_time('mysql'),
            ],
            ['id' => $suscripcion_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al pausar la suscripción.', 'flavor-platform'),
            ];
        }

        do_action('gc_suscripcion_pausada', $suscripcion_id);

        return [
            'success' => true,
            'mensaje' => __('Suscripción pausada correctamente.', 'flavor-platform'),
        ];
    }

    /**
     * Reanuda una suscripción pausada
     *
     * @param int $suscripcion_id ID de la suscripción
     * @return array
     */
    public function reanudar_suscripcion($suscripcion_id) {
        global $wpdb;

        $suscripcion = $this->obtener_suscripcion($suscripcion_id);
        if (!$suscripcion) {
            return [
                'success' => false,
                'error' => __('Suscripción no encontrada.', 'flavor-platform'),
            ];
        }

        if ($suscripcion->estado !== 'pausada') {
            return [
                'success' => false,
                'error' => __('Solo se pueden reanudar suscripciones pausadas.', 'flavor-platform'),
            ];
        }

        // Calcular nueva fecha de próximo cargo
        $dias_frecuencia = self::FRECUENCIAS[$suscripcion->frecuencia] ?? 7;
        $fecha_proximo_cargo = date('Y-m-d', strtotime("+{$dias_frecuencia} days"));

        $resultado = $wpdb->update(
            $this->tabla_suscripciones,
            [
                'estado' => 'activa',
                'fecha_pausa' => null,
                'fecha_proximo_cargo' => $fecha_proximo_cargo,
            ],
            ['id' => $suscripcion_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al reanudar la suscripción.', 'flavor-platform'),
            ];
        }

        do_action('gc_suscripcion_reanudada', $suscripcion_id);

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Suscripción reanudada. Próxima cesta: %s', 'flavor-platform'),
                date_i18n(get_option('date_format'), strtotime($fecha_proximo_cargo))
            ),
        ];
    }

    /**
     * Cancela una suscripción
     *
     * @param int    $suscripcion_id ID de la suscripción
     * @param string $motivo         Motivo de cancelación
     * @return array
     */
    public function cancelar_suscripcion($suscripcion_id, $motivo = '') {
        global $wpdb;

        $suscripcion = $this->obtener_suscripcion($suscripcion_id);
        if (!$suscripcion) {
            return [
                'success' => false,
                'error' => __('Suscripción no encontrada.', 'flavor-platform'),
            ];
        }

        if ($suscripcion->estado === 'cancelada') {
            return [
                'success' => false,
                'error' => __('La suscripción ya está cancelada.', 'flavor-platform'),
            ];
        }

        $notas = $suscripcion->notas;
        if ($motivo) {
            $notas .= "\n[Cancelación " . current_time('mysql') . "]: " . sanitize_text_field($motivo);
        }

        $resultado = $wpdb->update(
            $this->tabla_suscripciones,
            [
                'estado' => 'cancelada',
                'fecha_cancelacion' => current_time('mysql'),
                'notas' => $notas,
            ],
            ['id' => $suscripcion_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al cancelar la suscripción.', 'flavor-platform'),
            ];
        }

        do_action('gc_suscripcion_cancelada', $suscripcion_id, $motivo);

        return [
            'success' => true,
            'mensaje' => __('Suscripción cancelada correctamente.', 'flavor-platform'),
        ];
    }

    /**
     * Cambia la frecuencia de una suscripción
     *
     * @param int    $suscripcion_id ID de la suscripción
     * @param string $nueva_frecuencia Nueva frecuencia
     * @return array
     */
    public function cambiar_frecuencia($suscripcion_id, $nueva_frecuencia) {
        global $wpdb;

        if (!isset(self::FRECUENCIAS[$nueva_frecuencia])) {
            return [
                'success' => false,
                'error' => __('Frecuencia no válida.', 'flavor-platform'),
            ];
        }

        $suscripcion = $this->obtener_suscripcion($suscripcion_id);
        if (!$suscripcion || $suscripcion->estado === 'cancelada') {
            return [
                'success' => false,
                'error' => __('Suscripción no encontrada o cancelada.', 'flavor-platform'),
            ];
        }

        // Recalcular próximo cargo desde hoy
        $dias_frecuencia = self::FRECUENCIAS[$nueva_frecuencia];
        $fecha_proximo_cargo = date('Y-m-d', strtotime("+{$dias_frecuencia} days"));

        $resultado = $wpdb->update(
            $this->tabla_suscripciones,
            [
                'frecuencia' => $nueva_frecuencia,
                'fecha_proximo_cargo' => $fecha_proximo_cargo,
            ],
            ['id' => $suscripcion_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al cambiar la frecuencia.', 'flavor-platform'),
            ];
        }

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Frecuencia cambiada a %s. Próxima cesta: %s', 'flavor-platform'),
                $this->obtener_etiqueta_frecuencia($nueva_frecuencia),
                date_i18n(get_option('date_format'), strtotime($fecha_proximo_cargo))
            ),
        ];
    }

    /**
     * Procesa suscripciones pendientes de cargo (ejecutado por cron)
     */
    public function procesar_suscripciones_pendientes() {
        global $wpdb;

        $hoy = date('Y-m-d');

        // Obtener suscripciones activas con cargo pendiente para hoy o antes
        $suscripciones_pendientes = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, c.consumidor_id as cons_id
            FROM {$this->tabla_suscripciones} s
            LEFT JOIN {$wpdb->prefix}flavor_gc_consumidores c ON s.consumidor_id = c.id
            WHERE s.estado = 'activa'
            AND s.fecha_proximo_cargo <= %s",
            $hoy
        ));

        if (empty($suscripciones_pendientes)) {
            return;
        }

        // Buscar ciclo abierto
        $ciclo_abierto = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        foreach ($suscripciones_pendientes as $suscripcion) {
            $this->procesar_suscripcion_individual($suscripcion, $ciclo_abierto ? $ciclo_abierto[0]->ID : null);
        }
    }

    /**
     * Procesa una suscripción individual
     *
     * @param object   $suscripcion Datos de la suscripción
     * @param int|null $ciclo_id    ID del ciclo actual
     */
    private function procesar_suscripcion_individual($suscripcion, $ciclo_id) {
        global $wpdb;

        // Registrar en historial
        $wpdb->insert(
            $this->tabla_historial,
            [
                'suscripcion_id' => $suscripcion->id,
                'ciclo_id' => $ciclo_id,
                'importe' => $suscripcion->importe,
                'estado' => 'procesado',
                'fecha_cargo' => current_time('mysql'),
            ],
            ['%d', '%d', '%f', '%s', '%s']
        );

        // Si hay ciclo abierto, crear pedido automático basado en la cesta
        if ($ciclo_id) {
            $this->crear_pedido_desde_suscripcion($suscripcion, $ciclo_id);
        }

        // Actualizar fecha próximo cargo
        $dias_frecuencia = self::FRECUENCIAS[$suscripcion->frecuencia] ?? 7;
        $nueva_fecha = date('Y-m-d', strtotime("+{$dias_frecuencia} days"));

        $wpdb->update(
            $this->tabla_suscripciones,
            ['fecha_proximo_cargo' => $nueva_fecha],
            ['id' => $suscripcion->id],
            ['%s'],
            ['%d']
        );

        // Disparar acción
        do_action('gc_suscripcion_procesada', $suscripcion->id, $ciclo_id);
    }

    /**
     * Crea un pedido automático desde una suscripción
     *
     * @param object $suscripcion Datos de la suscripción
     * @param int    $ciclo_id    ID del ciclo
     */
    private function crear_pedido_desde_suscripcion($suscripcion, $ciclo_id) {
        global $wpdb;

        // Obtener configuración de la cesta
        $cesta = $this->obtener_tipo_cesta($suscripcion->tipo_cesta_id);
        if (!$cesta) {
            return;
        }

        $productos_config = json_decode($cesta->productos_incluidos, true);
        if (empty($productos_config) || isset($productos_config['tipo']) && $productos_config['tipo'] === 'personalizada') {
            // Cesta personalizada no genera pedido automático
            return;
        }

        // Obtener consumidor para saber el usuario
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $consumidor = $consumidor_manager->obtener_por_id($suscripcion->consumidor_id);
        if (!$consumidor) {
            return;
        }

        // Obtener productos de las categorías configuradas
        $categorias = $productos_config['categorias'] ?? [];
        $num_productos = $productos_config['num_productos'] ?? 5;

        if (empty($categorias)) {
            return;
        }

        $productos = get_posts([
            'post_type' => 'gc_producto',
            'post_status' => 'publish',
            'posts_per_page' => $num_productos,
            'orderby' => 'rand',
            'tax_query' => [
                [
                    'taxonomy' => 'gc_categoria',
                    'field' => 'slug',
                    'terms' => $categorias,
                ],
            ],
        ]);

        if (empty($productos)) {
            return;
        }

        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
        $total_pedido = 0;

        foreach ($productos as $producto) {
            $precio = floatval(get_post_meta($producto->ID, '_gc_precio', true));
            $cantidad_minima = floatval(get_post_meta($producto->ID, '_gc_cantidad_minima', true)) ?: 1;

            $wpdb->insert(
                $tabla_pedidos,
                [
                    'ciclo_id' => $ciclo_id,
                    'usuario_id' => $consumidor->usuario_id,
                    'producto_id' => $producto->ID,
                    'cantidad' => $cantidad_minima,
                    'precio_unitario' => $precio,
                    'estado' => 'pendiente',
                    'fecha_pedido' => current_time('mysql'),
                    'notas' => sprintf(__('Pedido automático - Suscripción %s', 'flavor-platform'), $cesta->nombre),
                ],
                ['%d', '%d', '%d', '%f', '%f', '%s', '%s', '%s']
            );

            $total_pedido += $precio * $cantidad_minima;
        }

        // Actualizar saldo del consumidor
        $consumidor_manager->actualizar_saldo($suscripcion->consumidor_id, $total_pedido);

        // Disparar acción
        do_action('gc_pedido_automatico_creado', $consumidor->usuario_id, $ciclo_id, $suscripcion->id);
    }

    // ========================================
    // Tipos de Cestas
    // ========================================

    /**
     * Obtiene un tipo de cesta por ID
     *
     * @param int $tipo_cesta_id ID del tipo
     * @return object|null
     */
    public function obtener_tipo_cesta($tipo_cesta_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_cestas_tipo} WHERE id = %d",
            $tipo_cesta_id
        ));
    }

    /**
     * Lista todos los tipos de cestas activas
     *
     * @param bool $incluir_inactivas Incluir cestas inactivas
     * @return array
     */
    public function listar_tipos_cestas($incluir_inactivas = false) {
        global $wpdb;

        $where = $incluir_inactivas ? '1=1' : 'activa = 1';

        return $wpdb->get_results(
            "SELECT * FROM {$this->tabla_cestas_tipo}
            WHERE {$where}
            ORDER BY orden ASC, nombre ASC"
        );
    }

    /**
     * Crea un nuevo tipo de cesta
     *
     * @param array $datos Datos de la cesta
     * @return array
     */
    public function crear_tipo_cesta($datos) {
        global $wpdb;

        $nombre = isset($datos['nombre']) ? sanitize_text_field($datos['nombre']) : '';
        $slug = isset($datos['slug']) ? sanitize_title($datos['slug']) : sanitize_title($nombre);

        if (empty($nombre)) {
            return [
                'success' => false,
                'error' => __('El nombre es obligatorio.', 'flavor-platform'),
            ];
        }

        // Verificar slug único
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_cestas_tipo} WHERE slug = %s",
            $slug
        ));

        if ($existente) {
            $slug .= '-' . time();
        }

        $resultado = $wpdb->insert(
            $this->tabla_cestas_tipo,
            [
                'nombre' => $nombre,
                'slug' => $slug,
                'descripcion' => isset($datos['descripcion']) ? sanitize_textarea_field($datos['descripcion']) : '',
                'precio_base' => isset($datos['precio_base']) ? floatval($datos['precio_base']) : 0.00,
                'productos_incluidos' => isset($datos['productos_incluidos']) ? wp_json_encode($datos['productos_incluidos']) : null,
                'imagen_id' => isset($datos['imagen_id']) ? absint($datos['imagen_id']) : null,
                'orden' => isset($datos['orden']) ? absint($datos['orden']) : 0,
                'activa' => isset($datos['activa']) ? (int) $datos['activa'] : 1,
                'fecha_creacion' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%f', '%s', '%d', '%d', '%d', '%s']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al crear el tipo de cesta.', 'flavor-platform'),
            ];
        }

        return [
            'success' => true,
            'cesta_id' => $wpdb->insert_id,
            'mensaje' => __('Tipo de cesta creado correctamente.', 'flavor-platform'),
        ];
    }

    /**
     * Actualiza un tipo de cesta
     *
     * @param int   $tipo_cesta_id ID del tipo
     * @param array $datos         Datos a actualizar
     * @return array
     */
    public function actualizar_tipo_cesta($tipo_cesta_id, $datos) {
        global $wpdb;

        $cesta = $this->obtener_tipo_cesta($tipo_cesta_id);
        if (!$cesta) {
            return [
                'success' => false,
                'error' => __('Tipo de cesta no encontrado.', 'flavor-platform'),
            ];
        }

        $datos_actualizar = [];
        $formato = [];

        if (isset($datos['nombre'])) {
            $datos_actualizar['nombre'] = sanitize_text_field($datos['nombre']);
            $formato[] = '%s';
        }

        if (isset($datos['descripcion'])) {
            $datos_actualizar['descripcion'] = sanitize_textarea_field($datos['descripcion']);
            $formato[] = '%s';
        }

        if (isset($datos['precio_base'])) {
            $datos_actualizar['precio_base'] = floatval($datos['precio_base']);
            $formato[] = '%f';
        }

        if (isset($datos['productos_incluidos'])) {
            $datos_actualizar['productos_incluidos'] = wp_json_encode($datos['productos_incluidos']);
            $formato[] = '%s';
        }

        if (isset($datos['imagen_id'])) {
            $datos_actualizar['imagen_id'] = absint($datos['imagen_id']);
            $formato[] = '%d';
        }

        if (isset($datos['orden'])) {
            $datos_actualizar['orden'] = absint($datos['orden']);
            $formato[] = '%d';
        }

        if (isset($datos['activa'])) {
            $datos_actualizar['activa'] = (int) $datos['activa'];
            $formato[] = '%d';
        }

        if (empty($datos_actualizar)) {
            return [
                'success' => false,
                'error' => __('No hay datos para actualizar.', 'flavor-platform'),
            ];
        }

        $resultado = $wpdb->update(
            $this->tabla_cestas_tipo,
            $datos_actualizar,
            ['id' => $tipo_cesta_id],
            $formato,
            ['%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al actualizar.', 'flavor-platform'),
            ];
        }

        return [
            'success' => true,
            'mensaje' => __('Tipo de cesta actualizado.', 'flavor-platform'),
        ];
    }

    // ========================================
    // Helpers
    // ========================================

    /**
     * Obtiene etiqueta de frecuencia
     *
     * @param string $frecuencia Frecuencia
     * @return string
     */
    public function obtener_etiqueta_frecuencia($frecuencia) {
        $etiquetas = [
            'semanal' => __('Semanal', 'flavor-platform'),
            'quincenal' => __('Quincenal', 'flavor-platform'),
            'mensual' => __('Mensual', 'flavor-platform'),
        ];
        return $etiquetas[$frecuencia] ?? $frecuencia;
    }

    /**
     * Obtiene etiqueta de estado
     *
     * @param string $estado Estado
     * @return string
     */
    public function obtener_etiqueta_estado($estado) {
        $etiquetas = [
            'activa' => __('Activa', 'flavor-platform'),
            'pausada' => __('Pausada', 'flavor-platform'),
            'cancelada' => __('Cancelada', 'flavor-platform'),
        ];
        return $etiquetas[$estado] ?? $estado;
    }

    /**
     * Obtiene estadísticas de suscripciones
     *
     * @return array
     */
    public function obtener_estadisticas() {
        global $wpdb;

        $estadisticas = [
            'total_activas' => 0,
            'total_pausadas' => 0,
            'total_canceladas' => 0,
            'ingresos_mensuales' => 0,
            'por_tipo_cesta' => [],
        ];

        // Por estado
        $por_estado = $wpdb->get_results(
            "SELECT estado, COUNT(*) as cantidad
            FROM {$this->tabla_suscripciones}
            GROUP BY estado"
        );

        foreach ($por_estado as $item) {
            $clave = 'total_' . $item->estado . 's';
            if (isset($estadisticas[$clave])) {
                $estadisticas[$clave] = (int) $item->cantidad;
            }
        }

        // Ingresos mensuales estimados (activas)
        $estadisticas['ingresos_mensuales'] = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(
                CASE frecuencia
                    WHEN 'semanal' THEN importe * 4
                    WHEN 'quincenal' THEN importe * 2
                    WHEN 'mensual' THEN importe
                END
            ), 0)
            FROM {$this->tabla_suscripciones}
            WHERE estado = 'activa'"
        );

        // Por tipo de cesta (activas)
        $por_tipo = $wpdb->get_results(
            "SELECT s.tipo_cesta_id, c.nombre, COUNT(*) as cantidad
            FROM {$this->tabla_suscripciones} s
            LEFT JOIN {$this->tabla_cestas_tipo} c ON s.tipo_cesta_id = c.id
            WHERE s.estado = 'activa'
            GROUP BY s.tipo_cesta_id"
        );

        foreach ($por_tipo as $item) {
            $estadisticas['por_tipo_cesta'][$item->nombre] = (int) $item->cantidad;
        }

        return $estadisticas;
    }

    // ========================================
    // AJAX Handlers
    // ========================================

    /**
     * AJAX: Crear suscripción
     */
    public function ajax_crear_suscripcion() {
        $this->verificar_nonce_ajax();

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $consumidor_id = isset($_POST['consumidor_id']) ? absint($_POST['consumidor_id']) : 0;
        $tipo_cesta_id = isset($_POST['tipo_cesta_id']) ? absint($_POST['tipo_cesta_id']) : 0;
        $frecuencia = isset($_POST['frecuencia']) ? sanitize_text_field($_POST['frecuencia']) : 'semanal';

        // Verificar permisos
        $consumidor = $this->resolver_consumidor_ajax($consumidor_id);

        if (!$consumidor || ($consumidor->usuario_id !== get_current_user_id() && !current_user_can('gc_gestionar_suscripciones'))) {
            wp_send_json_error(['mensaje' => __('No tienes permisos.', 'flavor-platform')]);
        }

        $resultado = $this->crear_suscripcion((int) $consumidor->id, $tipo_cesta_id, $frecuencia);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Pausar suscripción
     */
    public function ajax_pausar_suscripcion() {
        $this->verificar_nonce_ajax();

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $suscripcion_id = isset($_POST['suscripcion_id']) ? absint($_POST['suscripcion_id']) : 0;
        $contexto = $this->resolver_suscripcion_ajax($suscripcion_id);
        $suscripcion = $contexto['suscripcion'];
        $consumidor = $contexto['consumidor'];

        if (!$suscripcion) {
            wp_send_json_error(['mensaje' => __('Suscripción no encontrada.', 'flavor-platform')]);
        }

        if (!$consumidor || ($consumidor->usuario_id !== get_current_user_id() && !current_user_can('gc_gestionar_suscripciones'))) {
            wp_send_json_error(['mensaje' => __('No tienes permisos.', 'flavor-platform')]);
        }

        $resultado = $this->pausar_suscripcion($suscripcion_id);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Reanudar suscripción
     */
    public function ajax_reanudar_suscripcion() {
        $this->verificar_nonce_ajax();

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $suscripcion_id = isset($_POST['suscripcion_id']) ? absint($_POST['suscripcion_id']) : 0;
        $contexto = $this->resolver_suscripcion_ajax($suscripcion_id);
        $suscripcion = $contexto['suscripcion'];
        $consumidor = $contexto['consumidor'];

        if (!$suscripcion) {
            wp_send_json_error(['mensaje' => __('Suscripción no encontrada.', 'flavor-platform')]);
        }

        if (!$consumidor || ($consumidor->usuario_id !== get_current_user_id() && !current_user_can('gc_gestionar_suscripciones'))) {
            wp_send_json_error(['mensaje' => __('No tienes permisos.', 'flavor-platform')]);
        }

        $resultado = $this->reanudar_suscripcion($suscripcion_id);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Cancelar suscripción
     */
    public function ajax_cancelar_suscripcion() {
        $this->verificar_nonce_ajax();

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $suscripcion_id = isset($_POST['suscripcion_id']) ? absint($_POST['suscripcion_id']) : 0;
        $motivo = isset($_POST['motivo']) ? sanitize_text_field($_POST['motivo']) : '';
        $contexto = $this->resolver_suscripcion_ajax($suscripcion_id);
        $suscripcion = $contexto['suscripcion'];
        $consumidor = $contexto['consumidor'];

        if (!$suscripcion) {
            wp_send_json_error(['mensaje' => __('Suscripción no encontrada.', 'flavor-platform')]);
        }

        if (!$consumidor || ($consumidor->usuario_id !== get_current_user_id() && !current_user_can('gc_gestionar_suscripciones'))) {
            wp_send_json_error(['mensaje' => __('No tienes permisos.', 'flavor-platform')]);
        }

        $resultado = $this->cancelar_suscripcion($suscripcion_id, $motivo);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Cambiar frecuencia
     */
    public function ajax_cambiar_frecuencia() {
        $this->verificar_nonce_ajax();

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $suscripcion_id = isset($_POST['suscripcion_id']) ? absint($_POST['suscripcion_id']) : 0;
        $frecuencia = isset($_POST['frecuencia']) ? sanitize_text_field($_POST['frecuencia']) : '';
        $contexto = $this->resolver_suscripcion_ajax($suscripcion_id);
        $suscripcion = $contexto['suscripcion'];
        $consumidor = $contexto['consumidor'];

        if (!$suscripcion) {
            wp_send_json_error(['mensaje' => __('Suscripción no encontrada.', 'flavor-platform')]);
        }

        if (!$consumidor || ($consumidor->usuario_id !== get_current_user_id() && !current_user_can('gc_gestionar_suscripciones'))) {
            wp_send_json_error(['mensaje' => __('No tienes permisos.', 'flavor-platform')]);
        }

        $resultado = $this->cambiar_frecuencia($suscripcion_id, $frecuencia);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }
}
