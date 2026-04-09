<?php
/**
 * Funcionalidades de Sello de Conciencia para Grupos de Consumo
 *
 * Incluye:
 * - Excedentes Solidarios: gestión de productos sobrantes
 * - Huella de Ciclo: métricas de sostenibilidad
 * - Precio Justo Visible: desglose transparente de costes
 * - Cestas de Trueque: intercambios entre consumidores
 *
 * @package FlavorChatIA
 * @subpackage GruposConsumo
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para las funcionalidades de Sello de Conciencia
 */
class Flavor_GC_Conciencia_Features {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Prefijo de tablas
     */
    private $prefix;

    /**
     * Obtener instancia singleton
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_gc_';

        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks(): void {
        // AJAX handlers
        add_action('wp_ajax_gc_registrar_excedente', [$this, 'ajax_registrar_excedente']);
        add_action('wp_ajax_gc_reclamar_excedente', [$this, 'ajax_reclamar_excedente']);
        add_action('wp_ajax_gc_calcular_huella', [$this, 'ajax_calcular_huella']);
        add_action('wp_ajax_gc_obtener_precio_desglose', [$this, 'ajax_obtener_precio_desglose']);
        add_action('wp_ajax_gc_publicar_trueque', [$this, 'ajax_publicar_trueque']);
        add_action('wp_ajax_gc_responder_trueque', [$this, 'ajax_responder_trueque']);
        add_action('wp_ajax_gc_listar_trueques', [$this, 'ajax_listar_trueques']);

        // Hook para calcular huella al cerrar ciclo
        add_action('gc_ciclo_cerrado', [$this, 'calcular_huella_ciclo'], 10, 1);

        // Shortcodes
        add_shortcode('gc_excedentes_disponibles', [$this, 'shortcode_excedentes']);
        add_shortcode('gc_huella_ciclo', [$this, 'shortcode_huella_ciclo']);
        add_shortcode('gc_precio_transparente', [$this, 'shortcode_precio_transparente']);
        add_shortcode('gc_tablero_trueques', [$this, 'shortcode_tablero_trueques']);
    }

    // =========================================================================
    // EXCEDENTES SOLIDARIOS
    // =========================================================================

    /**
     * Registrar un excedente de producto
     */
    public function registrar_excedente(array $datos): int|WP_Error {
        global $wpdb;

        $requeridos = ['ciclo_id', 'producto_id', 'cantidad_sobrante'];
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                return new WP_Error('campo_requerido', sprintf(__('El campo %s es requerido', 'flavor-platform'), $campo));
            }
        }

        $resultado = $wpdb->insert(
            $this->prefix . 'excedentes',
            [
                'ciclo_id'           => intval($datos['ciclo_id']),
                'producto_id'        => intval($datos['producto_id']),
                'cantidad_sobrante'  => floatval($datos['cantidad_sobrante']),
                'precio_solidario'   => isset($datos['precio_solidario']) ? floatval($datos['precio_solidario']) : null,
                'motivo_excedente'   => sanitize_text_field($datos['motivo_excedente'] ?? ''),
                'notas'              => sanitize_textarea_field($datos['notas'] ?? ''),
                'estado'             => 'disponible',
                'fecha_registro'     => current_time('mysql'),
            ],
            ['%d', '%d', '%f', '%f', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al registrar el excedente', 'flavor-platform'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtener excedentes disponibles
     */
    public function obtener_excedentes_disponibles(?int $ciclo_id = null): array {
        global $wpdb;

        $tabla_excedentes = $this->prefix . 'excedentes';
        $sql = "SELECT e.*, p.post_title as nombre_producto,
                       pm.meta_value as unidad_producto,
                       (e.cantidad_sobrante - e.cantidad_reclamada - e.cantidad_donada) as cantidad_disponible
                FROM $tabla_excedentes e
                JOIN {$wpdb->posts} p ON e.producto_id = p.ID
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_gc_unidad'
                WHERE e.estado IN ('disponible', 'parcial')";

        if ($ciclo_id) {
            $sql .= $wpdb->prepare(" AND e.ciclo_id = %d", $ciclo_id);
        }

        $sql .= " ORDER BY e.fecha_registro DESC";

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Reclamar un excedente
     */
    public function reclamar_excedente(int $excedente_id, int $usuario_id, float $cantidad, ?float $precio = null): int|WP_Error {
        global $wpdb;

        $tabla_excedentes = $this->prefix . 'excedentes';
        $tabla_reclamaciones = $this->prefix . 'excedentes_reclamaciones';

        // Verificar disponibilidad
        $excedente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_excedentes WHERE id = %d",
            $excedente_id
        ), ARRAY_A);

        if (!$excedente) {
            return new WP_Error('no_encontrado', __('Excedente no encontrado', 'flavor-platform'));
        }

        $disponible = $excedente['cantidad_sobrante'] - $excedente['cantidad_reclamada'] - $excedente['cantidad_donada'];

        if ($cantidad > $disponible) {
            return new WP_Error('cantidad_excedida', sprintf(
                __('Solo hay %s unidades disponibles', 'flavor-platform'),
                number_format($disponible, 2)
            ));
        }

        // Registrar reclamación
        $precio_pagado = $precio ?? ($excedente['precio_solidario'] ?? 0);

        $resultado = $wpdb->insert(
            $tabla_reclamaciones,
            [
                'excedente_id'       => $excedente_id,
                'usuario_id'         => $usuario_id,
                'cantidad'           => $cantidad,
                'precio_pagado'      => $precio_pagado,
                'estado'             => 'pendiente',
                'fecha_reclamacion'  => current_time('mysql'),
            ],
            ['%d', '%d', '%f', '%f', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al registrar la reclamación', 'flavor-platform'));
        }

        // Actualizar excedente
        $nueva_cantidad_reclamada = $excedente['cantidad_reclamada'] + $cantidad;
        $nuevo_estado = ($nueva_cantidad_reclamada >= $excedente['cantidad_sobrante']) ? 'agotado' : 'parcial';

        $wpdb->update(
            $tabla_excedentes,
            [
                'cantidad_reclamada' => $nueva_cantidad_reclamada,
                'estado'             => $nuevo_estado,
            ],
            ['id' => $excedente_id],
            ['%f', '%s'],
            ['%d']
        );

        return $wpdb->insert_id;
    }

    /**
     * AJAX: Registrar excedente
     */
    public function ajax_registrar_excedente(): void {
        check_ajax_referer('gc_conciencia_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-platform')]);
        }

        $resultado = $this->registrar_excedente($_POST);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Excedente registrado correctamente', 'flavor-platform'),
            'id'      => $resultado,
        ]);
    }

    /**
     * AJAX: Reclamar excedente
     */
    public function ajax_reclamar_excedente(): void {
        check_ajax_referer('gc_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $excedente_id = intval($_POST['excedente_id'] ?? 0);
        $cantidad = floatval($_POST['cantidad'] ?? 0);

        if (!$excedente_id || !$cantidad) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-platform')]);
        }

        $resultado = $this->reclamar_excedente(
            $excedente_id,
            get_current_user_id(),
            $cantidad,
            isset($_POST['precio']) ? floatval($_POST['precio']) : null
        );

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Excedente reclamado. Te contactaremos para la recogida.', 'flavor-platform'),
            'id'      => $resultado,
        ]);
    }

    // =========================================================================
    // HUELLA DE CICLO
    // =========================================================================

    /**
     * Calcular huella ecológica de un ciclo
     */
    public function calcular_huella_ciclo(int $ciclo_id): array {
        global $wpdb;

        $tabla_pedidos = $this->prefix . 'pedidos';
        $tabla_huella = $this->prefix . 'huella_ciclo';

        // Obtener datos del ciclo
        $pedidos = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, pr.post_title as nombre_producto
             FROM $tabla_pedidos p
             JOIN {$wpdb->posts} pr ON p.producto_id = pr.ID
             WHERE p.ciclo_id = %d AND p.estado != 'cancelado'",
            $ciclo_id
        ), ARRAY_A);

        if (empty($pedidos)) {
            return [];
        }

        // Calcular métricas
        $num_participantes = count(array_unique(array_column($pedidos, 'usuario_id')));
        $total_kg = 0;
        $km_totales = 0;
        $productos_eco = 0;
        $productores_ids = [];

        foreach ($pedidos as $pedido) {
            $producto_id = $pedido['producto_id'];

            // Obtener datos del producto
            $productor_id = get_post_meta($producto_id, '_gc_productor_id', true);
            $es_eco = get_post_meta($producto_id, '_gc_ecologico', true);
            $origen_km = get_post_meta($producto_id, '_gc_distancia_km', true) ?: $this->estimar_distancia($producto_id);

            // Acumular
            $peso_estimado = $this->estimar_peso($pedido['cantidad'], $producto_id);
            $total_kg += $peso_estimado;

            if ($productor_id) {
                $productores_ids[$productor_id] = true;
            }

            if ($es_eco) {
                $productos_eco++;
            }

            $km_totales += $origen_km;
        }

        $num_productos = count($pedidos);
        $km_medio = $num_productos > 0 ? $km_totales / $num_productos : 0;

        // Calcular ahorros vs supermercado convencional
        $km_supermercado = 1500; // km promedio en cadena convencional
        $km_evitados = max(0, ($km_supermercado - $km_medio) * $total_kg / 100);

        // CO2: 0.15 kg por km*kg transportado
        $co2_evitado = $km_evitados * 0.15;

        // Plástico: estimación 50g por kg de producto
        $plastico_evitado = $total_kg * 0.05;

        // Agua: estimación comparativa
        $agua_ahorrada = $total_kg * 20; // litros

        // Puntuación de sostenibilidad (0-100)
        $puntuacion = $this->calcular_puntuacion_sostenibilidad([
            'km_medio'         => $km_medio,
            'productos_eco'    => $productos_eco,
            'num_productos'    => $num_productos,
            'productores'      => count($productores_ids),
            'participantes'    => $num_participantes,
        ]);

        $huella_datos = [
            'ciclo_id'                   => $ciclo_id,
            'km_evitados'                => round($km_evitados, 2),
            'co2_evitado_kg'             => round($co2_evitado, 2),
            'plastico_evitado_kg'        => round($plastico_evitado, 4),
            'agua_ahorrada_litros'       => round($agua_ahorrada, 2),
            'productores_locales'        => count($productores_ids),
            'productos_eco_porcentaje'   => $num_productos > 0 ? round(($productos_eco / $num_productos) * 100, 2) : 0,
            'km_medio_producto'          => round($km_medio, 2),
            'num_participantes'          => $num_participantes,
            'total_kg_productos'         => round($total_kg, 2),
            'puntuacion_sostenibilidad'  => $puntuacion,
            'datos_detalle'              => wp_json_encode([
                'productos_analizados' => $num_productos,
                'productores_ids'      => array_keys($productores_ids),
            ]),
            'fecha_calculo'              => current_time('mysql'),
        ];

        // Guardar o actualizar
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_huella WHERE ciclo_id = %d",
            $ciclo_id
        ));

        if ($existente) {
            $wpdb->update($tabla_huella, $huella_datos, ['ciclo_id' => $ciclo_id]);
        } else {
            $wpdb->insert($tabla_huella, $huella_datos);
        }

        return $huella_datos;
    }

    /**
     * Obtener huella de un ciclo
     */
    public function obtener_huella_ciclo(int $ciclo_id): ?array {
        global $wpdb;
        $tabla = $this->prefix . 'huella_ciclo';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE ciclo_id = %d",
            $ciclo_id
        ), ARRAY_A);
    }

    /**
     * Calcular puntuación de sostenibilidad
     */
    private function calcular_puntuacion_sostenibilidad(array $datos): int {
        $puntos = 0;

        // Distancia media (max 30 pts)
        if ($datos['km_medio'] < 50) {
            $puntos += 30;
        } elseif ($datos['km_medio'] < 100) {
            $puntos += 25;
        } elseif ($datos['km_medio'] < 200) {
            $puntos += 15;
        } elseif ($datos['km_medio'] < 500) {
            $puntos += 10;
        }

        // Productos ecológicos (max 25 pts)
        $porcentaje_eco = $datos['num_productos'] > 0
            ? ($datos['productos_eco'] / $datos['num_productos']) * 100
            : 0;
        $puntos += min(25, intval($porcentaje_eco * 0.25));

        // Productores locales (max 25 pts)
        $puntos += min(25, $datos['productores'] * 5);

        // Participación comunitaria (max 20 pts)
        $puntos += min(20, $datos['participantes'] * 2);

        return min(100, $puntos);
    }

    /**
     * Estimar distancia del producto
     */
    private function estimar_distancia(int $producto_id): float {
        $productor_id = get_post_meta($producto_id, '_gc_productor_id', true);
        if ($productor_id) {
            $ubicacion = get_post_meta($productor_id, '_gc_ubicacion', true);
            // Estimación simple por palabras clave
            if (stripos($ubicacion, 'local') !== false || stripos($ubicacion, 'huerta') !== false) {
                return 15;
            }
            if (stripos($ubicacion, 'provincia') !== false || stripos($ubicacion, 'comarca') !== false) {
                return 50;
            }
            if (stripos($ubicacion, 'comunidad') !== false || stripos($ubicacion, 'región') !== false) {
                return 150;
            }
        }
        return 100; // Por defecto
    }

    /**
     * Estimar peso del producto
     */
    private function estimar_peso(float $cantidad, int $producto_id): float {
        $unidad = get_post_meta($producto_id, '_gc_unidad', true);

        switch (strtolower($unidad)) {
            case 'kg':
            case 'kilo':
            case 'kilos':
                return $cantidad;
            case 'g':
            case 'gramo':
            case 'gramos':
                return $cantidad / 1000;
            case 'unidad':
            case 'ud':
            case 'pieza':
                return $cantidad * 0.3; // Peso estimado por unidad
            case 'litro':
            case 'l':
                return $cantidad;
            case 'docena':
                return $cantidad * 0.8;
            default:
                return $cantidad * 0.5;
        }
    }

    /**
     * AJAX: Calcular huella
     */
    public function ajax_calcular_huella(): void {
        check_ajax_referer('gc_conciencia_nonce', 'nonce');

        $ciclo_id = intval($_POST['ciclo_id'] ?? 0);

        if (!$ciclo_id) {
            wp_send_json_error(['message' => __('Ciclo no especificado', 'flavor-platform')]);
        }

        $huella = $this->calcular_huella_ciclo($ciclo_id);

        if (empty($huella)) {
            wp_send_json_error(['message' => __('No hay datos suficientes para calcular', 'flavor-platform')]);
        }

        wp_send_json_success([
            'message' => __('Huella calculada correctamente', 'flavor-platform'),
            'huella'  => $huella,
        ]);
    }

    // =========================================================================
    // PRECIO JUSTO VISIBLE
    // =========================================================================

    /**
     * Guardar desglose de precio
     */
    public function guardar_precio_desglose(int $producto_id, array $desglose, ?int $ciclo_id = null): int|WP_Error {
        global $wpdb;
        $tabla = $this->prefix . 'precio_desglose';

        $precio_productor = floatval($desglose['precio_productor'] ?? 0);
        $coste_transporte = floatval($desglose['coste_transporte'] ?? 0);
        $coste_gestion = floatval($desglose['coste_gestion'] ?? 0);
        $coste_mermas = floatval($desglose['coste_mermas'] ?? 0);
        $aportacion_fondo = floatval($desglose['aportacion_fondo_social'] ?? 0);
        $iva = floatval($desglose['iva'] ?? 0);

        $precio_final = $precio_productor + $coste_transporte + $coste_gestion + $coste_mermas + $aportacion_fondo + $iva;

        $margen_productor = $precio_final > 0 ? ($precio_productor / $precio_final) * 100 : 0;

        $datos = [
            'producto_id'               => $producto_id,
            'ciclo_id'                  => $ciclo_id,
            'precio_productor'          => $precio_productor,
            'coste_transporte'          => $coste_transporte,
            'coste_gestion'             => $coste_gestion,
            'coste_mermas'              => $coste_mermas,
            'aportacion_fondo_social'   => $aportacion_fondo,
            'iva'                       => $iva,
            'precio_final'              => $precio_final,
            'margen_productor_porcentaje' => round($margen_productor, 2),
            'origen_km'                 => intval($desglose['origen_km'] ?? 0),
            'certificaciones'           => sanitize_text_field($desglose['certificaciones'] ?? ''),
            'visible_publico'           => isset($desglose['visible_publico']) ? 1 : 0,
            'fecha_actualizacion'       => current_time('mysql'),
        ];

        // Buscar existente
        $existente_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE producto_id = %d AND (ciclo_id = %d OR (ciclo_id IS NULL AND %d IS NULL))",
            $producto_id,
            $ciclo_id,
            $ciclo_id
        ));

        if ($existente_id) {
            $wpdb->update($tabla, $datos, ['id' => $existente_id]);
            return $existente_id;
        }

        $wpdb->insert($tabla, $datos);
        return $wpdb->insert_id;
    }

    /**
     * Obtener desglose de precio
     */
    public function obtener_precio_desglose(int $producto_id, ?int $ciclo_id = null): ?array {
        global $wpdb;
        $tabla = $this->prefix . 'precio_desglose';

        // Primero buscar específico del ciclo
        if ($ciclo_id) {
            $desglose = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla WHERE producto_id = %d AND ciclo_id = %d",
                $producto_id,
                $ciclo_id
            ), ARRAY_A);

            if ($desglose) {
                return $desglose;
            }
        }

        // Si no hay específico, buscar general
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE producto_id = %d AND ciclo_id IS NULL",
            $producto_id
        ), ARRAY_A);
    }

    /**
     * AJAX: Obtener desglose de precio
     */
    public function ajax_obtener_precio_desglose(): void {
        check_ajax_referer('gc_conciencia_nonce', 'nonce');

        $producto_id = intval($_POST['producto_id'] ?? 0);
        $ciclo_id = isset($_POST['ciclo_id']) ? intval($_POST['ciclo_id']) : null;

        if (!$producto_id) {
            wp_send_json_error(['message' => __('Producto no especificado', 'flavor-platform')]);
        }

        $desglose = $this->obtener_precio_desglose($producto_id, $ciclo_id);

        if (!$desglose) {
            // Generar desglose estimado basado en precio actual
            $precio_actual = floatval(get_post_meta($producto_id, '_gc_precio', true));
            $desglose = $this->generar_desglose_estimado($producto_id, $precio_actual);
        }

        wp_send_json_success(['desglose' => $desglose]);
    }

    /**
     * Generar desglose estimado
     */
    private function generar_desglose_estimado(int $producto_id, float $precio_total): array {
        // Estimaciones por defecto (porcentajes)
        $porcentaje_productor = 0.70;
        $porcentaje_transporte = 0.10;
        $porcentaje_gestion = 0.08;
        $porcentaje_mermas = 0.02;
        $porcentaje_iva = 0.10;

        return [
            'producto_id'               => $producto_id,
            'precio_productor'          => round($precio_total * $porcentaje_productor, 2),
            'coste_transporte'          => round($precio_total * $porcentaje_transporte, 2),
            'coste_gestion'             => round($precio_total * $porcentaje_gestion, 2),
            'coste_mermas'              => round($precio_total * $porcentaje_mermas, 2),
            'aportacion_fondo_social'   => 0,
            'iva'                       => round($precio_total * $porcentaje_iva, 2),
            'precio_final'              => $precio_total,
            'margen_productor_porcentaje' => $porcentaje_productor * 100,
            'es_estimado'               => true,
        ];
    }

    // =========================================================================
    // CESTAS DE TRUEQUE
    // =========================================================================

    /**
     * Publicar oferta de trueque
     */
    public function publicar_trueque(array $datos): int|WP_Error {
        global $wpdb;
        $tabla = $this->prefix . 'trueque';

        $requeridos = ['titulo', 'productos_ofrecidos'];
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                return new WP_Error('campo_requerido', sprintf(__('El campo %s es requerido', 'flavor-platform'), $campo));
            }
        }

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return new WP_Error('no_logueado', __('Debes iniciar sesión', 'flavor-platform'));
        }

        $productos_ofrecidos = is_array($datos['productos_ofrecidos'])
            ? wp_json_encode($datos['productos_ofrecidos'])
            : sanitize_text_field($datos['productos_ofrecidos']);

        $productos_deseados = null;
        if (!empty($datos['productos_deseados'])) {
            $productos_deseados = is_array($datos['productos_deseados'])
                ? wp_json_encode($datos['productos_deseados'])
                : sanitize_text_field($datos['productos_deseados']);
        }

        $fecha_expiracion = null;
        if (!empty($datos['dias_vigencia'])) {
            $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+' . intval($datos['dias_vigencia']) . ' days'));
        }

        $resultado = $wpdb->insert(
            $tabla,
            [
                'usuario_ofrece_id'    => $usuario_id,
                'titulo'               => sanitize_text_field($datos['titulo']),
                'descripcion'          => sanitize_textarea_field($datos['descripcion'] ?? ''),
                'productos_ofrecidos'  => $productos_ofrecidos,
                'productos_deseados'   => $productos_deseados,
                'valor_estimado'       => isset($datos['valor_estimado']) ? floatval($datos['valor_estimado']) : null,
                'tipo'                 => in_array($datos['tipo'] ?? '', ['trueque', 'regalo', 'prestamo']) ? $datos['tipo'] : 'trueque',
                'estado'               => 'abierto',
                'fecha_publicacion'    => current_time('mysql'),
                'fecha_expiracion'     => $fecha_expiracion,
                'ubicacion_intercambio' => sanitize_text_field($datos['ubicacion'] ?? ''),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al publicar el trueque', 'flavor-platform'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtener trueques activos
     */
    public function obtener_trueques_activos(array $filtros = []): array {
        global $wpdb;
        $tabla = $this->prefix . 'trueque';

        $sql = "SELECT t.*, u.display_name as nombre_usuario
                FROM $tabla t
                JOIN {$wpdb->users} u ON t.usuario_ofrece_id = u.ID
                WHERE t.estado IN ('abierto', 'en_negociacion')
                AND (t.fecha_expiracion IS NULL OR t.fecha_expiracion > NOW())";

        if (!empty($filtros['tipo'])) {
            $sql .= $wpdb->prepare(" AND t.tipo = %s", $filtros['tipo']);
        }

        if (!empty($filtros['usuario_id'])) {
            $sql .= $wpdb->prepare(" AND t.usuario_ofrece_id = %d", $filtros['usuario_id']);
        }

        if (!empty($filtros['busqueda'])) {
            $busqueda = '%' . $wpdb->esc_like($filtros['busqueda']) . '%';
            $sql .= $wpdb->prepare(" AND (t.titulo LIKE %s OR t.descripcion LIKE %s OR t.productos_ofrecidos LIKE %s)", $busqueda, $busqueda, $busqueda);
        }

        $sql .= " ORDER BY t.fecha_publicacion DESC";

        if (!empty($filtros['limit'])) {
            $sql .= $wpdb->prepare(" LIMIT %d", $filtros['limit']);
        }

        $resultados = $wpdb->get_results($sql, ARRAY_A) ?: [];

        // Decodificar productos JSON
        foreach ($resultados as &$trueque) {
            $trueque['productos_ofrecidos'] = json_decode($trueque['productos_ofrecidos'], true) ?: $trueque['productos_ofrecidos'];
            $trueque['productos_deseados'] = $trueque['productos_deseados']
                ? (json_decode($trueque['productos_deseados'], true) ?: $trueque['productos_deseados'])
                : null;
        }

        return $resultados;
    }

    /**
     * Responder a un trueque
     */
    public function responder_trueque(int $trueque_id, int $usuario_id, string $mensaje, ?array $propuesta = null): int|WP_Error {
        global $wpdb;
        $tabla_trueque = $this->prefix . 'trueque';
        $tabla_mensajes = $this->prefix . 'trueque_mensajes';

        // Verificar trueque existe y está abierto
        $trueque = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_trueque WHERE id = %d",
            $trueque_id
        ), ARRAY_A);

        if (!$trueque) {
            return new WP_Error('no_encontrado', __('Trueque no encontrado', 'flavor-platform'));
        }

        if (!in_array($trueque['estado'], ['abierto', 'en_negociacion'])) {
            return new WP_Error('cerrado', __('Este trueque ya no acepta respuestas', 'flavor-platform'));
        }

        // Insertar mensaje
        $resultado = $wpdb->insert(
            $tabla_mensajes,
            [
                'trueque_id'          => $trueque_id,
                'usuario_id'          => $usuario_id,
                'mensaje'             => sanitize_textarea_field($mensaje),
                'propuesta_modificada' => $propuesta ? wp_json_encode($propuesta) : null,
                'fecha_mensaje'       => current_time('mysql'),
                'leido'               => 0,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%d']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al enviar el mensaje', 'flavor-platform'));
        }

        // Actualizar estado del trueque si es primera respuesta
        if ($trueque['estado'] === 'abierto') {
            $wpdb->update(
                $tabla_trueque,
                [
                    'estado'            => 'en_negociacion',
                    'usuario_recibe_id' => $usuario_id,
                ],
                ['id' => $trueque_id],
                ['%s', '%d'],
                ['%d']
            );
        }

        return $wpdb->insert_id;
    }

    /**
     * Completar trueque
     */
    public function completar_trueque(int $trueque_id, int $usuario_id): bool|WP_Error {
        global $wpdb;
        $tabla = $this->prefix . 'trueque';

        $trueque = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $trueque_id
        ), ARRAY_A);

        if (!$trueque) {
            return new WP_Error('no_encontrado', __('Trueque no encontrado', 'flavor-platform'));
        }

        // Solo el dueño puede marcar como completado
        if ($trueque['usuario_ofrece_id'] != $usuario_id && !current_user_can('manage_options')) {
            return new WP_Error('sin_permisos', __('No tienes permisos para esta acción', 'flavor-platform'));
        }

        $wpdb->update(
            $tabla,
            [
                'estado'           => 'completado',
                'fecha_completado' => current_time('mysql'),
            ],
            ['id' => $trueque_id],
            ['%s', '%s'],
            ['%d']
        );

        return true;
    }

    /**
     * AJAX: Publicar trueque
     */
    public function ajax_publicar_trueque(): void {
        check_ajax_referer('gc_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $resultado = $this->publicar_trueque($_POST);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Trueque publicado correctamente', 'flavor-platform'),
            'id'      => $resultado,
        ]);
    }

    /**
     * AJAX: Responder trueque
     */
    public function ajax_responder_trueque(): void {
        check_ajax_referer('gc_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $trueque_id = intval($_POST['trueque_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

        if (!$trueque_id || !$mensaje) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-platform')]);
        }

        $propuesta = null;
        if (!empty($_POST['propuesta'])) {
            $propuesta = is_array($_POST['propuesta']) ? $_POST['propuesta'] : json_decode(stripslashes($_POST['propuesta']), true);
        }

        $resultado = $this->responder_trueque($trueque_id, get_current_user_id(), $mensaje, $propuesta);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Mensaje enviado', 'flavor-platform'),
            'id'      => $resultado,
        ]);
    }

    /**
     * AJAX: Listar trueques
     */
    public function ajax_listar_trueques(): void {
        check_ajax_referer('gc_conciencia_nonce', 'nonce');

        $filtros = [
            'tipo'      => sanitize_text_field($_POST['tipo'] ?? ''),
            'busqueda'  => sanitize_text_field($_POST['busqueda'] ?? ''),
            'limit'     => intval($_POST['limit'] ?? 20),
        ];

        if (!empty($_POST['mis_trueques']) && is_user_logged_in()) {
            $filtros['usuario_id'] = get_current_user_id();
        }

        $trueques = $this->obtener_trueques_activos($filtros);

        wp_send_json_success(['trueques' => $trueques]);
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Excedentes disponibles
     */
    public function shortcode_excedentes(array $atts): string {
        $atts = shortcode_atts([
            'ciclo_id' => null,
            'mostrar'  => 10,
        ], $atts);

        $excedentes = $this->obtener_excedentes_disponibles(
            $atts['ciclo_id'] ? intval($atts['ciclo_id']) : null
        );

        ob_start();
        include dirname(__FILE__) . '/templates/excedentes-lista.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Huella de ciclo
     */
    public function shortcode_huella_ciclo(array $atts): string {
        $atts = shortcode_atts([
            'ciclo_id' => null,
            'estilo'   => 'completo', // completo, resumen, badge
        ], $atts);

        $ciclo_id = $atts['ciclo_id'];
        if (!$ciclo_id) {
            // Obtener ciclo activo o último
            $ciclo = get_posts([
                'post_type'      => 'gc_ciclo',
                'posts_per_page' => 1,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]);
            $ciclo_id = $ciclo ? $ciclo[0]->ID : null;
        }

        if (!$ciclo_id) {
            return '<p class="gc-aviso">' . __('No hay ciclos disponibles', 'flavor-platform') . '</p>';
        }

        $huella = $this->obtener_huella_ciclo(intval($ciclo_id));

        if (!$huella) {
            $huella = $this->calcular_huella_ciclo(intval($ciclo_id));
        }

        ob_start();
        include dirname(__FILE__) . '/templates/huella-ciclo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Precio transparente
     */
    public function shortcode_precio_transparente(array $atts): string {
        $atts = shortcode_atts([
            'producto_id' => null,
            'ciclo_id'    => null,
            'estilo'      => 'grafico', // grafico, tabla, compacto
        ], $atts);

        if (!$atts['producto_id']) {
            return '<p class="gc-aviso">' . __('Producto no especificado', 'flavor-platform') . '</p>';
        }

        $desglose = $this->obtener_precio_desglose(
            intval($atts['producto_id']),
            $atts['ciclo_id'] ? intval($atts['ciclo_id']) : null
        );

        if (!$desglose) {
            $precio = floatval(get_post_meta($atts['producto_id'], '_gc_precio', true));
            $desglose = $this->generar_desglose_estimado(intval($atts['producto_id']), $precio);
        }

        ob_start();
        include dirname(__FILE__) . '/templates/precio-transparente.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Tablero de trueques
     */
    public function shortcode_tablero_trueques(array $atts): string {
        $atts = shortcode_atts([
            'tipo'    => '', // trueque, regalo, prestamo o vacío para todos
            'mostrar' => 12,
        ], $atts);

        $filtros = [
            'tipo'  => $atts['tipo'],
            'limit' => intval($atts['mostrar']),
        ];

        $trueques = $this->obtener_trueques_activos($filtros);

        wp_enqueue_style('gc-trueques', plugins_url('assets/css/trueques.css', dirname(__FILE__)));
        wp_enqueue_script('gc-trueques', plugins_url('assets/js/trueques.js', dirname(__FILE__)), ['jquery'], '1.0.0', true);
        wp_localize_script('gc-trueques', 'gcTruequeData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('gc_conciencia_nonce'),
            'i18n'    => [
                'enviando'   => __('Enviando...', 'flavor-platform'),
                'error'      => __('Error al procesar', 'flavor-platform'),
                'confirmado' => __('Operación completada', 'flavor-platform'),
            ],
        ]);

        ob_start();
        include dirname(__FILE__) . '/templates/tablero-trueques.php';
        return ob_get_clean();
    }

    // =========================================================================
    // ESTADÍSTICAS PARA DASHBOARD
    // =========================================================================

    /**
     * Obtener estadísticas para el dashboard del usuario
     */
    public function get_estadisticas_dashboard(int $usuario_id): array {
        global $wpdb;

        $stats = [];

        // Excedentes reclamados por el usuario
        $tabla_reclamaciones = $this->prefix . 'excedentes_reclamaciones';
        $excedentes_reclamados = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reclamaciones WHERE usuario_id = %d",
            $usuario_id
        ));
        $stats[] = [
            'value' => $excedentes_reclamados,
            'label' => __('Excedentes aprovechados', 'flavor-platform'),
            'icon'  => 'carrot',
        ];

        // Trueques activos del usuario
        $tabla_trueque = $this->prefix . 'trueque';
        $trueques_activos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_trueque
             WHERE (usuario_ofrece_id = %d OR usuario_recibe_id = %d)
             AND estado IN ('abierto', 'en_negociacion')",
            $usuario_id,
            $usuario_id
        ));
        $stats[] = [
            'value' => $trueques_activos,
            'label' => __('Trueques activos', 'flavor-platform'),
            'icon'  => 'randomize',
        ];

        return $stats;
    }
}
