<?php
/**
 * Funcionalidades del Sello de Conciencia para Espacios Comunes
 *
 * Añade +5 puntos al módulo:
 * - Uso Solidario: Cesión de reservas y lista de espera prioritaria
 * - Huella de Uso: Métricas de consumo por espacio
 * - Cuidado Comunitario: Sistema de voluntariado para mantenimiento
 * - Dashboard de Sostenibilidad: Índices de equidad y rotación
 *
 * @package FlavorChatIA
 * @subpackage EspaciosComunes
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_EC_Conciencia_Features {

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
    public static function get_instance() {
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
        $this->prefix = $wpdb->prefix . 'flavor_ec_';

        $this->init_hooks();
        $this->maybe_create_tables();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_ec_ceder_reserva', [$this, 'ajax_ceder_reserva']);
        add_action('wp_ajax_ec_reclamar_cesion', [$this, 'ajax_reclamar_cesion']);
        add_action('wp_ajax_ec_registrar_consumo', [$this, 'ajax_registrar_consumo']);
        add_action('wp_ajax_ec_apuntarse_voluntariado', [$this, 'ajax_apuntarse_voluntariado']);
        add_action('wp_ajax_ec_completar_voluntariado', [$this, 'ajax_completar_voluntariado']);
        add_action('wp_ajax_ec_lista_espera', [$this, 'ajax_lista_espera']);

        // Shortcodes
        add_shortcode('ec_cesiones_disponibles', [$this, 'shortcode_cesiones_disponibles']);
        add_shortcode('ec_huella_espacio', [$this, 'shortcode_huella_espacio']);
        add_shortcode('ec_voluntariado', [$this, 'shortcode_voluntariado']);
        add_shortcode('ec_dashboard_sostenibilidad', [$this, 'shortcode_dashboard_sostenibilidad']);
        add_shortcode('ec_mi_impacto', [$this, 'shortcode_mi_impacto']);

        // Cron para alertas
        add_action('flavor_ec_check_consumos', [$this, 'check_consumos_excesivos']);
        if (!wp_next_scheduled('flavor_ec_check_consumos')) {
            wp_schedule_event(time(), 'daily', 'flavor_ec_check_consumos');
        }
    }

    /**
     * Crear tablas si no existen
     */
    private function maybe_create_tables() {
        global $wpdb;

        $tabla_cesiones = $this->prefix . 'cesiones';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_cesiones'") !== $tabla_cesiones) {
            $this->create_tables();
        }
    }

    /**
     * Crear todas las tablas
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tabla de cesiones de reservas
        $tabla_cesiones = $this->prefix . 'cesiones';
        $sql_cesiones = "CREATE TABLE $tabla_cesiones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reserva_id bigint(20) unsigned NOT NULL,
            cedente_id bigint(20) unsigned NOT NULL,
            receptor_id bigint(20) unsigned DEFAULT NULL,
            espacio_id bigint(20) unsigned NOT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            motivo text DEFAULT NULL,
            es_solidaria tinyint(1) DEFAULT 0 COMMENT 'Si es para grupo vulnerable',
            estado enum('disponible','reclamada','confirmada','expirada','cancelada') DEFAULT 'disponible',
            fecha_cesion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_reclamacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY reserva_id (reserva_id),
            KEY cedente_id (cedente_id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";
        dbDelta($sql_cesiones);

        // Tabla de lista de espera
        $tabla_lista_espera = $this->prefix . 'lista_espera';
        $sql_lista_espera = "CREATE TABLE $tabla_lista_espera (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            espacio_id bigint(20) unsigned NOT NULL,
            fecha_deseada date DEFAULT NULL,
            rango_horario varchar(50) DEFAULT NULL COMMENT 'mañana/tarde/noche/cualquiera',
            es_prioritario tinyint(1) DEFAULT 0 COMMENT 'Grupo vulnerable',
            motivo_prioridad text DEFAULT NULL,
            estado enum('activo','notificado','reservado','expirado') DEFAULT 'activo',
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_notificacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY espacio_id (espacio_id),
            KEY es_prioritario (es_prioritario),
            KEY estado (estado)
        ) $charset_collate;";
        dbDelta($sql_lista_espera);

        // Tabla de consumos (huella de uso)
        $tabla_consumos = $this->prefix . 'consumos';
        $sql_consumos = "CREATE TABLE $tabla_consumos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) unsigned NOT NULL,
            reserva_id bigint(20) unsigned DEFAULT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            tipo_consumo enum('electricidad','agua','gas','climatizacion','otro') NOT NULL,
            cantidad decimal(10,2) NOT NULL,
            unidad varchar(20) NOT NULL DEFAULT 'kwh',
            coste_estimado decimal(10,2) DEFAULT NULL,
            co2_estimado decimal(10,2) DEFAULT NULL COMMENT 'kg CO2',
            fecha_registro date NOT NULL,
            notas text DEFAULT NULL,
            registrado_por bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY reserva_id (reserva_id),
            KEY tipo_consumo (tipo_consumo),
            KEY fecha_registro (fecha_registro)
        ) $charset_collate;";
        dbDelta($sql_consumos);

        // Tabla de tareas de voluntariado
        $tabla_voluntariado = $this->prefix . 'voluntariado';
        $sql_voluntariado = "CREATE TABLE $tabla_voluntariado (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('limpieza','mantenimiento','jardineria','pintura','reparacion','organizacion','otro') DEFAULT 'otro',
            urgencia enum('baja','media','alta') DEFAULT 'media',
            personas_necesarias int(11) DEFAULT 1,
            personas_apuntadas int(11) DEFAULT 0,
            horas_estimadas decimal(4,2) DEFAULT 1.00,
            puntos_recompensa int(11) DEFAULT 10,
            fecha_tarea date DEFAULT NULL,
            hora_inicio time DEFAULT NULL,
            materiales_necesarios text DEFAULT NULL,
            estado enum('abierta','en_curso','completada','cancelada') DEFAULT 'abierta',
            creado_por bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completada datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY estado (estado),
            KEY urgencia (urgencia),
            KEY fecha_tarea (fecha_tarea)
        ) $charset_collate;";
        dbDelta($sql_voluntariado);

        // Tabla de participaciones en voluntariado
        $tabla_participaciones = $this->prefix . 'participaciones';
        $sql_participaciones = "CREATE TABLE $tabla_participaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tarea_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            horas_trabajadas decimal(4,2) DEFAULT NULL,
            puntos_obtenidos int(11) DEFAULT 0,
            comentario text DEFAULT NULL,
            estado enum('apuntado','confirmado','completado','no_asistio') DEFAULT 'apuntado',
            fecha_apuntado datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tarea_usuario (tarea_id, usuario_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";
        dbDelta($sql_participaciones);

        // Tabla de métricas de sostenibilidad
        $tabla_metricas = $this->prefix . 'metricas';
        $sql_metricas = "CREATE TABLE $tabla_metricas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) unsigned DEFAULT NULL COMMENT 'NULL = global',
            periodo varchar(20) NOT NULL COMMENT 'YYYY-MM o YYYY',
            tipo_metrica varchar(50) NOT NULL,
            valor decimal(15,4) NOT NULL,
            metadata longtext DEFAULT NULL COMMENT 'JSON con detalles',
            fecha_calculo datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY espacio_periodo_tipo (espacio_id, periodo, tipo_metrica),
            KEY tipo_metrica (tipo_metrica),
            KEY periodo (periodo)
        ) $charset_collate;";
        dbDelta($sql_metricas);
    }

    // ─────────────────────────────────────────────────────────────
    // USO SOLIDARIO - Cesión de Reservas
    // ─────────────────────────────────────────────────────────────

    /**
     * Ceder una reserva
     */
    public function ceder_reserva($reserva_id, $cedente_id, $es_solidaria = false, $motivo = '') {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $tabla_cesiones = $this->prefix . 'cesiones';

        // Verificar que la reserva existe y pertenece al usuario
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reservas WHERE id = %d AND usuario_id = %d AND estado IN ('pendiente', 'confirmada')",
            $reserva_id,
            $cedente_id
        ));

        if (!$reserva) {
            return ['success' => false, 'error' => __('Reserva no encontrada o no tienes permiso.', 'flavor-platform')];
        }

        // Verificar que no esté ya cedida
        $ya_cedida = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_cesiones WHERE reserva_id = %d AND estado IN ('disponible', 'reclamada')",
            $reserva_id
        ));

        if ($ya_cedida) {
            return ['success' => false, 'error' => __('Esta reserva ya está cedida.', 'flavor-platform')];
        }

        // Crear cesión
        $resultado = $wpdb->insert($tabla_cesiones, [
            'reserva_id' => $reserva_id,
            'cedente_id' => $cedente_id,
            'espacio_id' => $reserva->espacio_id,
            'fecha_inicio' => $reserva->fecha_inicio,
            'fecha_fin' => $reserva->fecha_fin,
            'motivo' => sanitize_textarea_field($motivo),
            'es_solidaria' => $es_solidaria ? 1 : 0,
            'estado' => 'disponible',
            'fecha_cesion' => current_time('mysql'),
        ]);

        if ($resultado) {
            // Notificar a lista de espera
            $this->notificar_lista_espera($reserva->espacio_id, $reserva->fecha_inicio, $es_solidaria);

            return [
                'success' => true,
                'cesion_id' => $wpdb->insert_id,
                'message' => $es_solidaria
                    ? __('Reserva cedida al fondo solidario. ¡Gracias por tu generosidad!', 'flavor-platform')
                    : __('Reserva publicada para cesión.', 'flavor-platform'),
            ];
        }

        return ['success' => false, 'error' => __('Error al procesar la cesión.', 'flavor-platform')];
    }

    /**
     * Reclamar una cesión
     */
    public function reclamar_cesion($cesion_id, $usuario_id) {
        global $wpdb;
        $tabla_cesiones = $this->prefix . 'cesiones';
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        // Verificar que la cesión está disponible
        $cesion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_cesiones WHERE id = %d AND estado = 'disponible'",
            $cesion_id
        ));

        if (!$cesion) {
            return ['success' => false, 'error' => __('Cesión no disponible.', 'flavor-platform')];
        }

        // No puede reclamar el mismo que cede
        if ($cesion->cedente_id == $usuario_id) {
            return ['success' => false, 'error' => __('No puedes reclamar tu propia cesión.', 'flavor-platform')];
        }

        // Actualizar cesión
        $wpdb->update(
            $tabla_cesiones,
            [
                'receptor_id' => $usuario_id,
                'estado' => 'confirmada',
                'fecha_reclamacion' => current_time('mysql'),
            ],
            ['id' => $cesion_id]
        );

        // Transferir la reserva
        $wpdb->update(
            $tabla_reservas,
            ['usuario_id' => $usuario_id],
            ['id' => $cesion->reserva_id]
        );

        // Registrar puntos de solidaridad al cedente
        $this->registrar_puntos_solidaridad($cesion->cedente_id, $cesion->es_solidaria ? 20 : 10);

        return [
            'success' => true,
            'message' => __('¡Reserva reclamada con éxito! Ya es tuya.', 'flavor-platform'),
        ];
    }

    /**
     * Obtener cesiones disponibles
     */
    public function obtener_cesiones_disponibles($espacio_id = null, $solo_solidarias = false) {
        global $wpdb;
        $tabla_cesiones = $this->prefix . 'cesiones';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios';

        $where = ["c.estado = 'disponible'", "c.fecha_inicio > NOW()"];
        $params = [];

        if ($espacio_id) {
            $where[] = "c.espacio_id = %d";
            $params[] = $espacio_id;
        }

        if ($solo_solidarias) {
            $where[] = "c.es_solidaria = 1";
        }

        $sql = "SELECT c.*, e.nombre as espacio_nombre, e.ubicacion, e.capacidad
                FROM $tabla_cesiones c
                LEFT JOIN $tabla_espacios e ON c.espacio_id = e.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.fecha_inicio ASC";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($sql, ...$params));
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Notificar a lista de espera
     */
    private function notificar_lista_espera($espacio_id, $fecha_inicio, $es_solidaria) {
        global $wpdb;
        $tabla_lista = $this->prefix . 'lista_espera';

        $fecha = date('Y-m-d', strtotime($fecha_inicio));

        // Priorizar usuarios prioritarios si es solidaria
        $order = $es_solidaria ? "es_prioritario DESC, fecha_solicitud ASC" : "fecha_solicitud ASC";

        $usuarios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_lista
             WHERE espacio_id = %d AND estado = 'activo'
             AND (fecha_deseada IS NULL OR fecha_deseada = %s)
             ORDER BY $order LIMIT 5",
            $espacio_id,
            $fecha
        ));

        foreach ($usuarios as $usuario) {
            // Actualizar estado
            $wpdb->update(
                $tabla_lista,
                ['estado' => 'notificado', 'fecha_notificacion' => current_time('mysql')],
                ['id' => $usuario->id]
            );

            // Enviar notificación
            if (class_exists('Flavor_Notification_Center')) {
                $nc = Flavor_Notification_Center::get_instance();
                $nc->send(
                    $usuario->usuario_id,
                    __('¡Espacio disponible!', 'flavor-platform'),
                    sprintf(
                        __('Hay una reserva disponible para el espacio que buscabas el %s. ¡Reclámala antes que otros!', 'flavor-platform'),
                        date_i18n(get_option('date_format'), strtotime($fecha_inicio))
                    ),
                    ['type' => 'success', 'link' => home_url('/espacios-comunes/cesiones/')]
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // LISTA DE ESPERA PRIORITARIA
    // ─────────────────────────────────────────────────────────────

    /**
     * Añadir a lista de espera
     */
    public function añadir_lista_espera($usuario_id, $espacio_id, $fecha_deseada = null, $rango_horario = 'cualquiera', $es_prioritario = false, $motivo = '') {
        global $wpdb;
        $tabla_lista = $this->prefix . 'lista_espera';

        // Verificar que no esté ya en lista
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_lista
             WHERE usuario_id = %d AND espacio_id = %d AND estado = 'activo'",
            $usuario_id,
            $espacio_id
        ));

        if ($existe) {
            return ['success' => false, 'error' => __('Ya estás en la lista de espera para este espacio.', 'flavor-platform')];
        }

        $resultado = $wpdb->insert($tabla_lista, [
            'usuario_id' => $usuario_id,
            'espacio_id' => $espacio_id,
            'fecha_deseada' => $fecha_deseada,
            'rango_horario' => sanitize_text_field($rango_horario),
            'es_prioritario' => $es_prioritario ? 1 : 0,
            'motivo_prioridad' => $es_prioritario ? sanitize_textarea_field($motivo) : null,
            'estado' => 'activo',
        ]);

        if ($resultado) {
            return [
                'success' => true,
                'message' => $es_prioritario
                    ? __('Añadido a lista prioritaria. Serás notificado primero.', 'flavor-platform')
                    : __('Añadido a lista de espera. Te avisaremos cuando haya disponibilidad.', 'flavor-platform'),
            ];
        }

        return ['success' => false, 'error' => __('Error al añadir a la lista.', 'flavor-platform')];
    }

    // ─────────────────────────────────────────────────────────────
    // HUELLA DE USO - Consumos
    // ─────────────────────────────────────────────────────────────

    /**
     * Registrar consumo
     */
    public function registrar_consumo($espacio_id, $tipo_consumo, $cantidad, $unidad, $fecha, $reserva_id = null, $usuario_id = null) {
        global $wpdb;
        $tabla_consumos = $this->prefix . 'consumos';

        // Calcular estimaciones
        $coste_estimado = $this->calcular_coste_estimado($tipo_consumo, $cantidad, $unidad);
        $co2_estimado = $this->calcular_co2($tipo_consumo, $cantidad, $unidad);

        $resultado = $wpdb->insert($tabla_consumos, [
            'espacio_id' => $espacio_id,
            'reserva_id' => $reserva_id,
            'usuario_id' => $usuario_id,
            'tipo_consumo' => $tipo_consumo,
            'cantidad' => $cantidad,
            'unidad' => $unidad,
            'coste_estimado' => $coste_estimado,
            'co2_estimado' => $co2_estimado,
            'fecha_registro' => $fecha,
            'registrado_por' => get_current_user_id(),
        ]);

        if ($resultado) {
            // Actualizar métricas
            $this->actualizar_metricas_espacio($espacio_id);

            return [
                'success' => true,
                'consumo_id' => $wpdb->insert_id,
                'co2_estimado' => $co2_estimado,
                'coste_estimado' => $coste_estimado,
            ];
        }

        return ['success' => false, 'error' => __('Error al registrar consumo.', 'flavor-platform')];
    }

    /**
     * Calcular coste estimado
     */
    private function calcular_coste_estimado($tipo, $cantidad, $unidad) {
        $tarifas = [
            'electricidad' => 0.15, // €/kWh
            'agua' => 2.50,         // €/m3
            'gas' => 0.08,          // €/kWh
            'climatizacion' => 0.12,
        ];

        return isset($tarifas[$tipo]) ? $cantidad * $tarifas[$tipo] : 0;
    }

    /**
     * Calcular CO2 estimado
     */
    private function calcular_co2($tipo, $cantidad, $unidad) {
        $factores = [
            'electricidad' => 0.233, // kg CO2/kWh (mix español)
            'agua' => 0.344,         // kg CO2/m3
            'gas' => 0.202,          // kg CO2/kWh
            'climatizacion' => 0.25,
        ];

        return isset($factores[$tipo]) ? $cantidad * $factores[$tipo] : 0;
    }

    /**
     * Obtener huella de un espacio
     */
    public function obtener_huella_espacio($espacio_id, $periodo = null) {
        global $wpdb;
        $tabla_consumos = $this->prefix . 'consumos';

        if (!$periodo) {
            $periodo = date('Y-m');
        }

        $consumos = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_consumo,
                    SUM(cantidad) as total_cantidad,
                    SUM(coste_estimado) as total_coste,
                    SUM(co2_estimado) as total_co2,
                    COUNT(*) as num_registros
             FROM $tabla_consumos
             WHERE espacio_id = %d AND DATE_FORMAT(fecha_registro, '%%Y-%%m') = %s
             GROUP BY tipo_consumo",
            $espacio_id,
            $periodo
        ), ARRAY_A);

        $total_co2 = array_sum(array_column($consumos, 'total_co2'));
        $total_coste = array_sum(array_column($consumos, 'total_coste'));

        // Comparar con mes anterior
        $periodo_anterior = date('Y-m', strtotime($periodo . '-01 -1 month'));
        $co2_anterior = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(co2_estimado) FROM $tabla_consumos
             WHERE espacio_id = %d AND DATE_FORMAT(fecha_registro, '%%Y-%%m') = %s",
            $espacio_id,
            $periodo_anterior
        ));

        $variacion = $co2_anterior > 0 ? (($total_co2 - $co2_anterior) / $co2_anterior) * 100 : 0;

        return [
            'consumos' => $consumos,
            'total_co2' => round($total_co2, 2),
            'total_coste' => round($total_coste, 2),
            'variacion_porcentaje' => round($variacion, 1),
            'periodo' => $periodo,
            'tendencia' => $variacion > 0 ? 'subiendo' : ($variacion < 0 ? 'bajando' : 'estable'),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // CUIDADO COMUNITARIO - Voluntariado
    // ─────────────────────────────────────────────────────────────

    /**
     * Crear tarea de voluntariado
     */
    public function crear_tarea_voluntariado($datos) {
        global $wpdb;
        $tabla = $this->prefix . 'voluntariado';

        $resultado = $wpdb->insert($tabla, [
            'espacio_id' => absint($datos['espacio_id']),
            'titulo' => sanitize_text_field($datos['titulo']),
            'descripcion' => sanitize_textarea_field($datos['descripcion'] ?? ''),
            'tipo' => sanitize_text_field($datos['tipo'] ?? 'otro'),
            'urgencia' => sanitize_text_field($datos['urgencia'] ?? 'media'),
            'personas_necesarias' => absint($datos['personas_necesarias'] ?? 1),
            'horas_estimadas' => floatval($datos['horas_estimadas'] ?? 1),
            'puntos_recompensa' => absint($datos['puntos_recompensa'] ?? 10),
            'fecha_tarea' => $datos['fecha_tarea'] ?? null,
            'hora_inicio' => $datos['hora_inicio'] ?? null,
            'materiales_necesarios' => sanitize_textarea_field($datos['materiales'] ?? ''),
            'estado' => 'abierta',
            'creado_por' => get_current_user_id(),
        ]);

        if ($resultado) {
            return ['success' => true, 'tarea_id' => $wpdb->insert_id];
        }

        return ['success' => false, 'error' => __('Error al crear la tarea.', 'flavor-platform')];
    }

    /**
     * Apuntarse a tarea de voluntariado
     */
    public function apuntarse_voluntariado($tarea_id, $usuario_id) {
        global $wpdb;
        $tabla_voluntariado = $this->prefix . 'voluntariado';
        $tabla_participaciones = $this->prefix . 'participaciones';

        // Verificar tarea
        $tarea = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_voluntariado WHERE id = %d AND estado = 'abierta'",
            $tarea_id
        ));

        if (!$tarea) {
            return ['success' => false, 'error' => __('Tarea no disponible.', 'flavor-platform')];
        }

        // Verificar plazas
        if ($tarea->personas_apuntadas >= $tarea->personas_necesarias) {
            return ['success' => false, 'error' => __('No quedan plazas disponibles.', 'flavor-platform')];
        }

        // Verificar que no esté ya apuntado
        $ya_apuntado = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_participaciones WHERE tarea_id = %d AND usuario_id = %d",
            $tarea_id,
            $usuario_id
        ));

        if ($ya_apuntado) {
            return ['success' => false, 'error' => __('Ya estás apuntado a esta tarea.', 'flavor-platform')];
        }

        // Apuntar
        $wpdb->insert($tabla_participaciones, [
            'tarea_id' => $tarea_id,
            'usuario_id' => $usuario_id,
            'estado' => 'apuntado',
        ]);

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_voluntariado SET personas_apuntadas = personas_apuntadas + 1 WHERE id = %d",
            $tarea_id
        ));

        return [
            'success' => true,
            'message' => __('¡Te has apuntado! Recibirás un recordatorio.', 'flavor-platform'),
        ];
    }

    /**
     * Completar participación en voluntariado
     */
    public function completar_voluntariado($tarea_id, $usuario_id, $horas_trabajadas = null) {
        global $wpdb;
        $tabla_voluntariado = $this->prefix . 'voluntariado';
        $tabla_participaciones = $this->prefix . 'participaciones';

        $tarea = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_voluntariado WHERE id = %d",
            $tarea_id
        ));

        if (!$tarea) {
            return ['success' => false, 'error' => __('Tarea no encontrada.', 'flavor-platform')];
        }

        $horas = $horas_trabajadas ?? $tarea->horas_estimadas;
        $puntos = intval($tarea->puntos_recompensa * ($horas / $tarea->horas_estimadas));

        $wpdb->update(
            $tabla_participaciones,
            [
                'estado' => 'completado',
                'horas_trabajadas' => $horas,
                'puntos_obtenidos' => $puntos,
                'fecha_completado' => current_time('mysql'),
            ],
            ['tarea_id' => $tarea_id, 'usuario_id' => $usuario_id]
        );

        // Registrar puntos
        $this->registrar_puntos_solidaridad($usuario_id, $puntos);

        return [
            'success' => true,
            'puntos' => $puntos,
            'message' => sprintf(__('¡Gracias! Has ganado %d puntos de cuidado comunitario.', 'flavor-platform'), $puntos),
        ];
    }

    /**
     * Obtener tareas de voluntariado
     */
    public function obtener_tareas_voluntariado($espacio_id = null, $estado = 'abierta') {
        global $wpdb;
        $tabla = $this->prefix . 'voluntariado';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios';

        $where = ["v.estado = %s"];
        $params = [$estado];

        if ($espacio_id) {
            $where[] = "v.espacio_id = %d";
            $params[] = $espacio_id;
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, e.nombre as espacio_nombre
             FROM $tabla v
             LEFT JOIN $tabla_espacios e ON v.espacio_id = e.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY v.urgencia DESC, v.fecha_tarea ASC",
            ...$params
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // PUNTOS DE SOLIDARIDAD
    // ─────────────────────────────────────────────────────────────

    /**
     * Registrar puntos de solidaridad
     */
    private function registrar_puntos_solidaridad($usuario_id, $puntos) {
        $puntos_actuales = (int) get_user_meta($usuario_id, 'ec_puntos_solidaridad', true);
        update_user_meta($usuario_id, 'ec_puntos_solidaridad', $puntos_actuales + $puntos);

        // Registrar en historial
        $historial = get_user_meta($usuario_id, 'ec_historial_puntos', true) ?: [];
        $historial[] = [
            'puntos' => $puntos,
            'fecha' => current_time('mysql'),
            'tipo' => 'solidaridad',
        ];
        update_user_meta($usuario_id, 'ec_historial_puntos', array_slice($historial, -50));
    }

    /**
     * Obtener puntos de usuario
     */
    public function obtener_puntos_usuario($usuario_id) {
        return (int) get_user_meta($usuario_id, 'ec_puntos_solidaridad', true);
    }

    // ─────────────────────────────────────────────────────────────
    // DASHBOARD DE SOSTENIBILIDAD
    // ─────────────────────────────────────────────────────────────

    /**
     * Actualizar métricas de espacio
     */
    public function actualizar_metricas_espacio($espacio_id) {
        global $wpdb;
        $periodo = date('Y-m');

        $huella = $this->obtener_huella_espacio($espacio_id, $periodo);

        // Guardar métrica de CO2
        $this->guardar_metrica($espacio_id, $periodo, 'co2_total', $huella['total_co2']);
        $this->guardar_metrica($espacio_id, $periodo, 'coste_total', $huella['total_coste']);
    }

    /**
     * Guardar métrica
     */
    private function guardar_metrica($espacio_id, $periodo, $tipo, $valor, $metadata = null) {
        global $wpdb;
        $tabla = $this->prefix . 'metricas';

        $wpdb->replace($tabla, [
            'espacio_id' => $espacio_id,
            'periodo' => $periodo,
            'tipo_metrica' => $tipo,
            'valor' => $valor,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'fecha_calculo' => current_time('mysql'),
        ]);
    }

    /**
     * Calcular métricas globales de sostenibilidad
     */
    public function calcular_metricas_globales($periodo = null) {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios';
        $tabla_cesiones = $this->prefix . 'cesiones';
        $tabla_consumos = $this->prefix . 'consumos';
        $tabla_voluntariado = $this->prefix . 'voluntariado';

        if (!$periodo) {
            $periodo = date('Y-m');
        }

        $inicio_mes = $periodo . '-01';
        $fin_mes = date('Y-m-t', strtotime($inicio_mes));

        // Total reservas del período
        $total_reservas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas
             WHERE DATE(fecha_inicio) BETWEEN %s AND %s",
            $inicio_mes, $fin_mes
        ));

        // Usuarios únicos que reservaron
        $usuarios_unicos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_reservas
             WHERE DATE(fecha_inicio) BETWEEN %s AND %s",
            $inicio_mes, $fin_mes
        ));

        // Índice de rotación (reservas / usuarios)
        $indice_rotacion = $usuarios_unicos > 0 ? round($total_reservas / $usuarios_unicos, 2) : 0;

        // Cesiones solidarias
        $cesiones_solidarias = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_cesiones
             WHERE es_solidaria = 1 AND DATE(fecha_cesion) BETWEEN %s AND %s",
            $inicio_mes, $fin_mes
        ));

        // CO2 total del período
        $co2_total = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(co2_estimado), 0) FROM $tabla_consumos
             WHERE DATE(fecha_registro) BETWEEN %s AND %s",
            $inicio_mes, $fin_mes
        ));

        // Horas voluntariado completadas
        $horas_voluntariado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas_estimadas), 0) FROM $tabla_voluntariado
             WHERE estado = 'completada' AND DATE(fecha_completada) BETWEEN %s AND %s",
            $inicio_mes, $fin_mes
        ));

        // Índice de equidad (distribución de uso entre usuarios)
        $distribucion = $wpdb->get_results($wpdb->prepare(
            "SELECT usuario_id, COUNT(*) as num_reservas
             FROM $tabla_reservas
             WHERE DATE(fecha_inicio) BETWEEN %s AND %s
             GROUP BY usuario_id",
            $inicio_mes, $fin_mes
        ), ARRAY_A);

        $indice_equidad = $this->calcular_indice_gini($distribucion);

        return [
            'periodo' => $periodo,
            'total_reservas' => $total_reservas,
            'usuarios_unicos' => $usuarios_unicos,
            'indice_rotacion' => $indice_rotacion,
            'cesiones_solidarias' => $cesiones_solidarias,
            'co2_total_kg' => round($co2_total, 2),
            'horas_voluntariado' => round($horas_voluntariado, 1),
            'indice_equidad' => round((1 - $indice_equidad) * 100, 1), // 0-100, mayor = más equitativo
        ];
    }

    /**
     * Calcular índice de Gini (desigualdad)
     */
    private function calcular_indice_gini($distribucion) {
        if (empty($distribucion)) {
            return 0;
        }

        $valores = array_column($distribucion, 'num_reservas');
        sort($valores);
        $n = count($valores);
        $suma_total = array_sum($valores);

        if ($suma_total == 0) {
            return 0;
        }

        $suma_acumulada = 0;
        $suma_gini = 0;

        foreach ($valores as $i => $valor) {
            $suma_acumulada += $valor;
            $suma_gini += ($i + 1) * $valor;
        }

        return (2 * $suma_gini) / ($n * $suma_total) - ($n + 1) / $n;
    }

    /**
     * Obtener alertas de sostenibilidad
     */
    public function obtener_alertas() {
        global $wpdb;
        $tabla_consumos = $this->prefix . 'consumos';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios';

        $alertas = [];

        // Espacios con consumo excesivo (>20% sobre media)
        $consumo_medio = (float) $wpdb->get_var(
            "SELECT AVG(co2_estimado) FROM $tabla_consumos WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        if ($consumo_medio > 0) {
            $espacios_alto_consumo = $wpdb->get_results($wpdb->prepare(
                "SELECT c.espacio_id, e.nombre, SUM(c.co2_estimado) as co2_total
                 FROM $tabla_consumos c
                 LEFT JOIN $tabla_espacios e ON c.espacio_id = e.id
                 WHERE c.fecha_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY c.espacio_id
                 HAVING co2_total > %f",
                $consumo_medio * 1.2
            ));

            foreach ($espacios_alto_consumo as $espacio) {
                $alertas[] = [
                    'tipo' => 'warning',
                    'icono' => 'dashicons-warning',
                    'titulo' => sprintf(__('Alto consumo en %s', 'flavor-platform'), $espacio->nombre),
                    'descripcion' => sprintf(__('CO2: %.1f kg (20%% sobre la media)', 'flavor-platform'), $espacio->co2_total),
                ];
            }
        }

        // Tareas de voluntariado urgentes sin completar
        $tabla_voluntariado = $this->prefix . 'voluntariado';
        $tareas_urgentes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_voluntariado WHERE urgencia = 'alta' AND estado = 'abierta'"
        );

        if ($tareas_urgentes > 0) {
            $alertas[] = [
                'tipo' => 'error',
                'icono' => 'dashicons-hammer',
                'titulo' => sprintf(_n('%d tarea urgente pendiente', '%d tareas urgentes pendientes', $tareas_urgentes, 'flavor-platform'), $tareas_urgentes),
                'descripcion' => __('Necesitamos voluntarios para mantenimiento urgente.', 'flavor-platform'),
            ];
        }

        return $alertas;
    }

    // ─────────────────────────────────────────────────────────────
    // AJAX HANDLERS
    // ─────────────────────────────────────────────────────────────

    public function ajax_ceder_reserva() {
        check_ajax_referer('ec_conciencia_nonce', 'nonce');

        $reserva_id = absint($_POST['reserva_id'] ?? 0);
        $es_solidaria = !empty($_POST['es_solidaria']);
        $motivo = sanitize_textarea_field($_POST['motivo'] ?? '');

        $resultado = $this->ceder_reserva($reserva_id, get_current_user_id(), $es_solidaria, $motivo);
        wp_send_json($resultado);
    }

    public function ajax_reclamar_cesion() {
        check_ajax_referer('ec_conciencia_nonce', 'nonce');

        $cesion_id = absint($_POST['cesion_id'] ?? 0);
        $resultado = $this->reclamar_cesion($cesion_id, get_current_user_id());
        wp_send_json($resultado);
    }

    public function ajax_registrar_consumo() {
        check_ajax_referer('ec_conciencia_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos.', 'flavor-platform')]);
        }

        $resultado = $this->registrar_consumo(
            absint($_POST['espacio_id']),
            sanitize_text_field($_POST['tipo_consumo']),
            floatval($_POST['cantidad']),
            sanitize_text_field($_POST['unidad'] ?? 'kwh'),
            sanitize_text_field($_POST['fecha']),
            absint($_POST['reserva_id'] ?? 0) ?: null
        );

        wp_send_json($resultado);
    }

    public function ajax_apuntarse_voluntariado() {
        check_ajax_referer('ec_conciencia_nonce', 'nonce');

        $tarea_id = absint($_POST['tarea_id'] ?? 0);
        $resultado = $this->apuntarse_voluntariado($tarea_id, get_current_user_id());
        wp_send_json($resultado);
    }

    public function ajax_completar_voluntariado() {
        check_ajax_referer('ec_conciencia_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos.', 'flavor-platform')]);
        }

        $tarea_id = absint($_POST['tarea_id'] ?? 0);
        $usuario_id = absint($_POST['usuario_id'] ?? 0);
        $horas = floatval($_POST['horas'] ?? 0) ?: null;

        $resultado = $this->completar_voluntariado($tarea_id, $usuario_id, $horas);
        wp_send_json($resultado);
    }

    public function ajax_lista_espera() {
        check_ajax_referer('ec_conciencia_nonce', 'nonce');

        $resultado = $this->añadir_lista_espera(
            get_current_user_id(),
            absint($_POST['espacio_id']),
            sanitize_text_field($_POST['fecha_deseada'] ?? '') ?: null,
            sanitize_text_field($_POST['rango_horario'] ?? 'cualquiera'),
            !empty($_POST['es_prioritario']),
            sanitize_textarea_field($_POST['motivo'] ?? '')
        );

        wp_send_json($resultado);
    }

    // ─────────────────────────────────────────────────────────────
    // SHORTCODES
    // ─────────────────────────────────────────────────────────────

    public function shortcode_cesiones_disponibles($atts) {
        $atts = shortcode_atts([
            'espacio_id' => 0,
            'solo_solidarias' => false,
        ], $atts);

        $cesiones = $this->obtener_cesiones_disponibles(
            $atts['espacio_id'] ?: null,
            filter_var($atts['solo_solidarias'], FILTER_VALIDATE_BOOLEAN)
        );

        ob_start();
        include dirname(__FILE__) . '/templates/cesiones-disponibles.php';
        return ob_get_clean();
    }

    public function shortcode_huella_espacio($atts) {
        $atts = shortcode_atts([
            'espacio_id' => 0,
            'periodo' => '',
        ], $atts);

        if (!$atts['espacio_id']) {
            return '<p>' . __('Especifica un espacio.', 'flavor-platform') . '</p>';
        }

        $huella = $this->obtener_huella_espacio($atts['espacio_id'], $atts['periodo'] ?: null);

        ob_start();
        include dirname(__FILE__) . '/templates/huella-espacio.php';
        return ob_get_clean();
    }

    public function shortcode_voluntariado($atts) {
        $atts = shortcode_atts([
            'espacio_id' => 0,
            'estado' => 'abierta',
        ], $atts);

        $tareas = $this->obtener_tareas_voluntariado(
            $atts['espacio_id'] ?: null,
            $atts['estado']
        );
        $nonce = wp_create_nonce('ec_conciencia_nonce');

        ob_start();
        include dirname(__FILE__) . '/templates/voluntariado.php';
        return ob_get_clean();
    }

    public function shortcode_dashboard_sostenibilidad($atts) {
        $atts = shortcode_atts(['periodo' => ''], $atts);

        $metricas = $this->calcular_metricas_globales($atts['periodo'] ?: null);
        $alertas = $this->obtener_alertas();

        ob_start();
        include dirname(__FILE__) . '/templates/dashboard-sostenibilidad.php';
        return ob_get_clean();
    }

    public function shortcode_mi_impacto($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para ver tu impacto.', 'flavor-platform') . '</p>';
        }

        $usuario_id = get_current_user_id();
        $puntos = $this->obtener_puntos_usuario($usuario_id);

        global $wpdb;
        $tabla_participaciones = $this->prefix . 'participaciones';
        $tabla_cesiones = $this->prefix . 'cesiones';

        // Horas voluntariado
        $horas_voluntariado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas_trabajadas), 0) FROM $tabla_participaciones
             WHERE usuario_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        // Cesiones realizadas
        $cesiones_realizadas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_cesiones WHERE cedente_id = %d AND estado = 'confirmada'",
            $usuario_id
        ));

        $impacto = [
            'puntos' => $puntos,
            'horas_voluntariado' => round($horas_voluntariado, 1),
            'cesiones_realizadas' => $cesiones_realizadas,
        ];

        ob_start();
        include dirname(__FILE__) . '/templates/mi-impacto.php';
        return ob_get_clean();
    }

    /**
     * Check consumos excesivos (cron)
     */
    public function check_consumos_excesivos() {
        $alertas = $this->obtener_alertas();

        foreach ($alertas as $alerta) {
            if ($alerta['tipo'] === 'error' && class_exists('Flavor_Notification_Center')) {
                // Notificar a administradores
                $admins = get_users(['role' => 'administrator']);
                $nc = Flavor_Notification_Center::get_instance();

                foreach ($admins as $admin) {
                    $nc->send(
                        $admin->ID,
                        $alerta['titulo'],
                        $alerta['descripcion'],
                        ['type' => 'warning', 'module_id' => 'espacios_comunes']
                    );
                }
            }
        }
    }
}
