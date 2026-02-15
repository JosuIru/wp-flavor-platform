<?php
/**
 * Funcionalidades del Sello de Conciencia para Eventos
 *
 * Añade +12 puntos al módulo:
 * - Eventos Inclusivos: Accesibilidad, cupos solidarios
 * - Huella de Carbono: Cálculo de CO₂ del evento
 * - Voluntariado en Eventos: Sistema de voluntarios
 * - Eventos Colaborativos: Co-organización
 * - Dashboard de Impacto Social: Métricas de participación
 *
 * @package FlavorChatIA
 * @subpackage Eventos
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Eventos_Conciencia_Features {

    private static $instance = null;
    private $prefix;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_eventos_';

        $this->init_hooks();
        $this->maybe_create_tables();
    }

    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_ev_solicitar_plaza_solidaria', [$this, 'ajax_solicitar_plaza_solidaria']);
        add_action('wp_ajax_ev_apuntarse_voluntario', [$this, 'ajax_apuntarse_voluntario']);
        add_action('wp_ajax_ev_registrar_huella', [$this, 'ajax_registrar_huella']);
        add_action('wp_ajax_ev_proponer_colaboracion', [$this, 'ajax_proponer_colaboracion']);

        // Shortcodes
        add_shortcode('ev_eventos_inclusivos', [$this, 'shortcode_eventos_inclusivos']);
        add_shortcode('ev_huella_evento', [$this, 'shortcode_huella_evento']);
        add_shortcode('ev_voluntariado_eventos', [$this, 'shortcode_voluntariado_eventos']);
        add_shortcode('ev_dashboard_impacto', [$this, 'shortcode_dashboard_impacto']);
        add_shortcode('ev_mi_participacion', [$this, 'shortcode_mi_participacion']);

        // Hook al crear inscripción para calcular huella
        add_action('flavor_eventos_inscripcion_creada', [$this, 'calcular_huella_inscripcion'], 10, 2);
    }

    private function maybe_create_tables() {
        global $wpdb;
        $tabla_inclusividad = $this->prefix . 'inclusividad';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_inclusividad'") !== $tabla_inclusividad) {
            $this->create_tables();
        }
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tabla de opciones de inclusividad por evento
        $tabla_inclusividad = $this->prefix . 'inclusividad';
        $sql_inclusividad = "CREATE TABLE $tabla_inclusividad (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL,
            accesibilidad_fisica tinyint(1) DEFAULT 0,
            interprete_lse tinyint(1) DEFAULT 0,
            subtitulos tinyint(1) DEFAULT 0,
            material_braille tinyint(1) DEFAULT 0,
            bucle_magnetico tinyint(1) DEFAULT 0,
            cuidado_infantil tinyint(1) DEFAULT 0,
            transporte_adaptado tinyint(1) DEFAULT 0,
            plazas_solidarias int(11) DEFAULT 0,
            plazas_solidarias_usadas int(11) DEFAULT 0,
            precio_solidario decimal(10,2) DEFAULT 0.00,
            notas_accesibilidad text DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY evento_id (evento_id)
        ) $charset_collate;";
        dbDelta($sql_inclusividad);

        // Tabla de solicitudes de plazas solidarias
        $tabla_solidarias = $this->prefix . 'plazas_solidarias';
        $sql_solidarias = "CREATE TABLE $tabla_solidarias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            motivo text NOT NULL,
            situacion enum('desempleo','discapacidad','familia_numerosa','riesgo_exclusion','otro') NOT NULL,
            documentacion_adjunta varchar(500) DEFAULT NULL,
            estado enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
            motivo_rechazo text DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_resolucion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY evento_id (evento_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";
        dbDelta($sql_solidarias);

        // Tabla de huella de carbono de eventos
        $tabla_huella = $this->prefix . 'huella_carbono';
        $sql_huella = "CREATE TABLE $tabla_huella (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL,
            co2_organizacion decimal(10,2) DEFAULT 0 COMMENT 'kg CO2 organización',
            co2_desplazamientos decimal(10,2) DEFAULT 0 COMMENT 'kg CO2 asistentes',
            co2_catering decimal(10,2) DEFAULT 0,
            co2_materiales decimal(10,2) DEFAULT 0,
            co2_energia decimal(10,2) DEFAULT 0,
            co2_total decimal(10,2) DEFAULT 0,
            co2_por_asistente decimal(10,2) DEFAULT 0,
            compensacion_arboles decimal(10,2) DEFAULT 0,
            es_evento_verde tinyint(1) DEFAULT 0,
            medidas_reduccion text DEFAULT NULL,
            fecha_calculo datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY evento_id (evento_id)
        ) $charset_collate;";
        dbDelta($sql_huella);

        // Tabla de voluntarios en eventos
        $tabla_voluntarios = $this->prefix . 'voluntarios';
        $sql_voluntarios = "CREATE TABLE $tabla_voluntarios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            rol enum('apoyo_general','recepcion','logistica','fotografia','interprete','cuidado_infantil','otro') DEFAULT 'apoyo_general',
            horas_comprometidas decimal(4,2) DEFAULT 0,
            horas_realizadas decimal(4,2) DEFAULT 0,
            puntos_obtenidos int(11) DEFAULT 0,
            estado enum('inscrito','confirmado','asistio','no_asistio','cancelado') DEFAULT 'inscrito',
            notas text DEFAULT NULL,
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY evento_usuario (evento_id, usuario_id),
            KEY estado (estado)
        ) $charset_collate;";
        dbDelta($sql_voluntarios);

        // Tabla de necesidades de voluntarios por evento
        $tabla_necesidades = $this->prefix . 'voluntarios_necesidades';
        $sql_necesidades = "CREATE TABLE $tabla_necesidades (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL,
            rol varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            cantidad_necesaria int(11) DEFAULT 1,
            cantidad_cubierta int(11) DEFAULT 0,
            horas_estimadas decimal(4,2) DEFAULT 2,
            puntos_recompensa int(11) DEFAULT 10,
            requisitos text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY evento_id (evento_id)
        ) $charset_collate;";
        dbDelta($sql_necesidades);

        // Tabla de colaboraciones entre organizadores
        $tabla_colaboraciones = $this->prefix . 'colaboraciones';
        $sql_colaboraciones = "CREATE TABLE $tabla_colaboraciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL,
            organizador_principal_id bigint(20) unsigned NOT NULL,
            co_organizador_id bigint(20) unsigned NOT NULL,
            tipo_colaboracion enum('co_organizacion','patrocinio','apoyo_logistico','difusion') DEFAULT 'co_organizacion',
            porcentaje_participacion int(11) DEFAULT 50,
            estado enum('propuesta','aceptada','rechazada') DEFAULT 'propuesta',
            notas text DEFAULT NULL,
            fecha_propuesta datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_respuesta datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY evento_id (evento_id),
            KEY co_organizador_id (co_organizador_id)
        ) $charset_collate;";
        dbDelta($sql_colaboraciones);

        // Tabla de métricas de impacto social
        $tabla_impacto = $this->prefix . 'impacto_social';
        $sql_impacto = "CREATE TABLE $tabla_impacto (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned DEFAULT NULL,
            periodo varchar(20) NOT NULL,
            tipo_metrica varchar(50) NOT NULL,
            valor decimal(15,4) NOT NULL,
            metadata longtext DEFAULT NULL,
            fecha_calculo datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY evento_periodo_tipo (evento_id, periodo, tipo_metrica)
        ) $charset_collate;";
        dbDelta($sql_impacto);
    }

    // ─────────────────────────────────────────────────────────────
    // EVENTOS INCLUSIVOS
    // ─────────────────────────────────────────────────────────────

    public function guardar_opciones_inclusividad($evento_id, $opciones) {
        global $wpdb;
        $tabla = $this->prefix . 'inclusividad';

        $datos = [
            'evento_id' => $evento_id,
            'accesibilidad_fisica' => !empty($opciones['accesibilidad_fisica']) ? 1 : 0,
            'interprete_lse' => !empty($opciones['interprete_lse']) ? 1 : 0,
            'subtitulos' => !empty($opciones['subtitulos']) ? 1 : 0,
            'material_braille' => !empty($opciones['material_braille']) ? 1 : 0,
            'bucle_magnetico' => !empty($opciones['bucle_magnetico']) ? 1 : 0,
            'cuidado_infantil' => !empty($opciones['cuidado_infantil']) ? 1 : 0,
            'transporte_adaptado' => !empty($opciones['transporte_adaptado']) ? 1 : 0,
            'plazas_solidarias' => absint($opciones['plazas_solidarias'] ?? 0),
            'precio_solidario' => floatval($opciones['precio_solidario'] ?? 0),
            'notas_accesibilidad' => sanitize_textarea_field($opciones['notas'] ?? ''),
        ];

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE evento_id = %d", $evento_id
        ));

        if ($existe) {
            $wpdb->update($tabla, $datos, ['evento_id' => $evento_id]);
        } else {
            $wpdb->insert($tabla, $datos);
        }

        return ['success' => true];
    }

    public function obtener_opciones_inclusividad($evento_id) {
        global $wpdb;
        $tabla = $this->prefix . 'inclusividad';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE evento_id = %d", $evento_id
        ), ARRAY_A) ?: [];
    }

    public function solicitar_plaza_solidaria($evento_id, $usuario_id, $motivo, $situacion) {
        global $wpdb;
        $tabla_solidarias = $this->prefix . 'plazas_solidarias';
        $tabla_inclusividad = $this->prefix . 'inclusividad';

        // Verificar plazas disponibles
        $info = $wpdb->get_row($wpdb->prepare(
            "SELECT plazas_solidarias, plazas_solidarias_usadas FROM $tabla_inclusividad WHERE evento_id = %d",
            $evento_id
        ));

        if (!$info || $info->plazas_solidarias_usadas >= $info->plazas_solidarias) {
            return ['success' => false, 'error' => __('No hay plazas solidarias disponibles.', 'flavor-chat-ia')];
        }

        // Verificar que no haya solicitado ya
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_solidarias WHERE evento_id = %d AND usuario_id = %d",
            $evento_id, $usuario_id
        ));

        if ($existe) {
            return ['success' => false, 'error' => __('Ya has solicitado una plaza solidaria para este evento.', 'flavor-chat-ia')];
        }

        $wpdb->insert($tabla_solidarias, [
            'evento_id' => $evento_id,
            'usuario_id' => $usuario_id,
            'motivo' => sanitize_textarea_field($motivo),
            'situacion' => $situacion,
            'estado' => 'pendiente',
        ]);

        return [
            'success' => true,
            'message' => __('Solicitud enviada. Te notificaremos cuando sea revisada.', 'flavor-chat-ia'),
        ];
    }

    public function aprobar_plaza_solidaria($solicitud_id) {
        global $wpdb;
        $tabla_solidarias = $this->prefix . 'plazas_solidarias';
        $tabla_inclusividad = $this->prefix . 'inclusividad';

        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_solidarias WHERE id = %d", $solicitud_id
        ));

        if (!$solicitud || $solicitud->estado !== 'pendiente') {
            return ['success' => false, 'error' => __('Solicitud no válida.', 'flavor-chat-ia')];
        }

        $wpdb->update($tabla_solidarias, [
            'estado' => 'aprobada',
            'fecha_resolucion' => current_time('mysql'),
        ], ['id' => $solicitud_id]);

        // Incrementar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_inclusividad SET plazas_solidarias_usadas = plazas_solidarias_usadas + 1 WHERE evento_id = %d",
            $solicitud->evento_id
        ));

        // Notificar
        if (class_exists('Flavor_Notification_Center')) {
            Flavor_Notification_Center::get_instance()->send(
                $solicitud->usuario_id,
                __('Plaza solidaria aprobada', 'flavor-chat-ia'),
                __('Tu solicitud de plaza solidaria ha sido aprobada.', 'flavor-chat-ia'),
                ['type' => 'success', 'module_id' => 'eventos']
            );
        }

        return ['success' => true];
    }

    // ─────────────────────────────────────────────────────────────
    // HUELLA DE CARBONO
    // ─────────────────────────────────────────────────────────────

    public function calcular_huella_evento($evento_id) {
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tabla_huella = $this->prefix . 'huella_carbono';

        $evento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_eventos WHERE id = %d", $evento_id
        ));

        if (!$evento) {
            return ['success' => false, 'error' => 'Evento no encontrado'];
        }

        // Contar asistentes confirmados
        $asistentes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(num_plazas) FROM $tabla_inscripciones WHERE evento_id = %d AND estado = 'confirmada'",
            $evento_id
        ));

        // Factores de emisión (kg CO2)
        $co2_organizacion = $evento->es_online ? 5 : 50; // Base organizativa
        $co2_por_desplazamiento = $evento->es_online ? 0.5 : 8; // Por asistente
        $co2_desplazamientos = $asistentes * $co2_por_desplazamiento;

        // Calcular según duración (horas)
        $duracion_horas = 2; // Default
        if ($evento->fecha_fin && $evento->fecha_inicio) {
            $duracion_horas = max(1, (strtotime($evento->fecha_fin) - strtotime($evento->fecha_inicio)) / 3600);
        }

        $co2_energia = $evento->es_online ? ($duracion_horas * 0.2 * $asistentes) : ($duracion_horas * 5);
        $co2_catering = $duracion_horas > 4 ? ($asistentes * 2) : 0;
        $co2_materiales = $evento->es_online ? 0 : ($asistentes * 0.5);

        $co2_total = $co2_organizacion + $co2_desplazamientos + $co2_energia + $co2_catering + $co2_materiales;
        $co2_por_asistente = $asistentes > 0 ? $co2_total / $asistentes : 0;
        $arboles_compensacion = $co2_total / 21; // Un árbol absorbe ~21kg CO2/año

        // Determinar si es evento verde
        $es_verde = $co2_por_asistente < 5;

        $datos = [
            'evento_id' => $evento_id,
            'co2_organizacion' => round($co2_organizacion, 2),
            'co2_desplazamientos' => round($co2_desplazamientos, 2),
            'co2_catering' => round($co2_catering, 2),
            'co2_materiales' => round($co2_materiales, 2),
            'co2_energia' => round($co2_energia, 2),
            'co2_total' => round($co2_total, 2),
            'co2_por_asistente' => round($co2_por_asistente, 2),
            'compensacion_arboles' => round($arboles_compensacion, 2),
            'es_evento_verde' => $es_verde ? 1 : 0,
            'fecha_calculo' => current_time('mysql'),
        ];

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_huella WHERE evento_id = %d", $evento_id
        ));

        if ($existe) {
            $wpdb->update($tabla_huella, $datos, ['evento_id' => $evento_id]);
        } else {
            $wpdb->insert($tabla_huella, $datos);
        }

        return [
            'success' => true,
            'huella' => $datos,
        ];
    }

    public function obtener_huella_evento($evento_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->prefix}huella_carbono WHERE evento_id = %d",
            $evento_id
        ), ARRAY_A);
    }

    public function calcular_huella_inscripcion($inscripcion_id, $evento_id) {
        // Recalcular huella del evento cuando hay nueva inscripción
        $this->calcular_huella_evento($evento_id);
    }

    // ─────────────────────────────────────────────────────────────
    // VOLUNTARIADO EN EVENTOS
    // ─────────────────────────────────────────────────────────────

    public function crear_necesidad_voluntarios($evento_id, $datos) {
        global $wpdb;
        $tabla = $this->prefix . 'voluntarios_necesidades';

        $wpdb->insert($tabla, [
            'evento_id' => $evento_id,
            'rol' => sanitize_text_field($datos['rol']),
            'descripcion' => sanitize_textarea_field($datos['descripcion'] ?? ''),
            'cantidad_necesaria' => absint($datos['cantidad'] ?? 1),
            'horas_estimadas' => floatval($datos['horas'] ?? 2),
            'puntos_recompensa' => absint($datos['puntos'] ?? 10),
            'requisitos' => sanitize_textarea_field($datos['requisitos'] ?? ''),
        ]);

        return ['success' => true, 'id' => $wpdb->insert_id];
    }

    public function apuntarse_voluntario($evento_id, $usuario_id, $rol, $horas = 0) {
        global $wpdb;
        $tabla_voluntarios = $this->prefix . 'voluntarios';
        $tabla_necesidades = $this->prefix . 'voluntarios_necesidades';

        // Verificar que no esté ya apuntado
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_voluntarios WHERE evento_id = %d AND usuario_id = %d",
            $evento_id, $usuario_id
        ));

        if ($existe) {
            return ['success' => false, 'error' => __('Ya estás apuntado como voluntario.', 'flavor-chat-ia')];
        }

        // Verificar disponibilidad del rol
        $necesidad = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_necesidades WHERE evento_id = %d AND rol = %s",
            $evento_id, $rol
        ));

        if ($necesidad && $necesidad->cantidad_cubierta >= $necesidad->cantidad_necesaria) {
            return ['success' => false, 'error' => __('No hay más plazas para este rol.', 'flavor-chat-ia')];
        }

        $wpdb->insert($tabla_voluntarios, [
            'evento_id' => $evento_id,
            'usuario_id' => $usuario_id,
            'rol' => $rol,
            'horas_comprometidas' => $horas ?: ($necesidad ? $necesidad->horas_estimadas : 2),
            'estado' => 'inscrito',
        ]);

        // Actualizar contador
        if ($necesidad) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_necesidades SET cantidad_cubierta = cantidad_cubierta + 1 WHERE id = %d",
                $necesidad->id
            ));
        }

        return [
            'success' => true,
            'message' => __('¡Te has apuntado como voluntario! Te contactaremos con los detalles.', 'flavor-chat-ia'),
        ];
    }

    public function confirmar_asistencia_voluntario($evento_id, $usuario_id, $horas_realizadas) {
        global $wpdb;
        $tabla_voluntarios = $this->prefix . 'voluntarios';
        $tabla_necesidades = $this->prefix . 'voluntarios_necesidades';

        $voluntario = $wpdb->get_row($wpdb->prepare(
            "SELECT v.*, n.puntos_recompensa FROM $tabla_voluntarios v
             LEFT JOIN $tabla_necesidades n ON v.evento_id = n.evento_id AND v.rol = n.rol
             WHERE v.evento_id = %d AND v.usuario_id = %d",
            $evento_id, $usuario_id
        ));

        if (!$voluntario) {
            return ['success' => false, 'error' => 'Voluntario no encontrado'];
        }

        $puntos = intval(($voluntario->puntos_recompensa ?? 10) * ($horas_realizadas / ($voluntario->horas_comprometidas ?: 1)));

        $wpdb->update($tabla_voluntarios, [
            'estado' => 'asistio',
            'horas_realizadas' => $horas_realizadas,
            'puntos_obtenidos' => $puntos,
        ], ['evento_id' => $evento_id, 'usuario_id' => $usuario_id]);

        // Guardar puntos en user meta
        $puntos_actuales = (int) get_user_meta($usuario_id, 'ev_puntos_voluntariado', true);
        update_user_meta($usuario_id, 'ev_puntos_voluntariado', $puntos_actuales + $puntos);

        return ['success' => true, 'puntos' => $puntos];
    }

    public function obtener_necesidades_voluntarios($evento_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->prefix}voluntarios_necesidades WHERE evento_id = %d",
            $evento_id
        ));
    }

    public function obtener_voluntarios_evento($evento_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, u.display_name FROM {$this->prefix}voluntarios v
             LEFT JOIN {$wpdb->users} u ON v.usuario_id = u.ID
             WHERE v.evento_id = %d ORDER BY v.fecha_inscripcion",
            $evento_id
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // EVENTOS COLABORATIVOS
    // ─────────────────────────────────────────────────────────────

    public function proponer_colaboracion($evento_id, $organizador_id, $co_organizador_id, $tipo, $porcentaje = 50) {
        global $wpdb;
        $tabla = $this->prefix . 'colaboraciones';

        $wpdb->insert($tabla, [
            'evento_id' => $evento_id,
            'organizador_principal_id' => $organizador_id,
            'co_organizador_id' => $co_organizador_id,
            'tipo_colaboracion' => $tipo,
            'porcentaje_participacion' => $porcentaje,
            'estado' => 'propuesta',
        ]);

        // Notificar al co-organizador
        if (class_exists('Flavor_Notification_Center')) {
            Flavor_Notification_Center::get_instance()->send(
                $co_organizador_id,
                __('Propuesta de colaboración', 'flavor-chat-ia'),
                __('Te han invitado a co-organizar un evento.', 'flavor-chat-ia'),
                ['type' => 'info', 'module_id' => 'eventos']
            );
        }

        return ['success' => true, 'id' => $wpdb->insert_id];
    }

    public function responder_colaboracion($colaboracion_id, $aceptar) {
        global $wpdb;
        $tabla = $this->prefix . 'colaboraciones';

        $wpdb->update($tabla, [
            'estado' => $aceptar ? 'aceptada' : 'rechazada',
            'fecha_respuesta' => current_time('mysql'),
        ], ['id' => $colaboracion_id]);

        return ['success' => true];
    }

    // ─────────────────────────────────────────────────────────────
    // DASHBOARD DE IMPACTO SOCIAL
    // ─────────────────────────────────────────────────────────────

    public function calcular_metricas_impacto($periodo = null) {
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tabla_voluntarios = $this->prefix . 'voluntarios';
        $tabla_solidarias = $this->prefix . 'plazas_solidarias';
        $tabla_huella = $this->prefix . 'huella_carbono';

        if (!$periodo) {
            $periodo = date('Y-m');
        }

        $inicio_mes = $periodo . '-01';
        $fin_mes = date('Y-m-t', strtotime($inicio_mes));

        // Total eventos del período
        $total_eventos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_eventos WHERE DATE(fecha_inicio) BETWEEN %s AND %s AND estado = 'publicado'",
            $inicio_mes, $fin_mes
        ));

        // Total asistentes
        $total_asistentes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(i.num_plazas) FROM $tabla_inscripciones i
             JOIN $tabla_eventos e ON i.evento_id = e.id
             WHERE DATE(e.fecha_inicio) BETWEEN %s AND %s AND i.estado = 'confirmada'",
            $inicio_mes, $fin_mes
        ));

        // Usuarios únicos
        $usuarios_unicos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT i.user_id) FROM $tabla_inscripciones i
             JOIN $tabla_eventos e ON i.evento_id = e.id
             WHERE DATE(e.fecha_inicio) BETWEEN %s AND %s AND i.estado = 'confirmada' AND i.user_id IS NOT NULL",
            $inicio_mes, $fin_mes
        ));

        // Horas de voluntariado
        $horas_voluntariado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(v.horas_realizadas), 0) FROM $tabla_voluntarios v
             JOIN $tabla_eventos e ON v.evento_id = e.id
             WHERE DATE(e.fecha_inicio) BETWEEN %s AND %s AND v.estado = 'asistio'",
            $inicio_mes, $fin_mes
        ));

        // Plazas solidarias otorgadas
        $plazas_solidarias = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_solidarias s
             JOIN $tabla_eventos e ON s.evento_id = e.id
             WHERE DATE(e.fecha_inicio) BETWEEN %s AND %s AND s.estado = 'aprobada'",
            $inicio_mes, $fin_mes
        ));

        // CO2 total del período
        $co2_total = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(h.co2_total), 0) FROM $tabla_huella h
             JOIN $tabla_eventos e ON h.evento_id = e.id
             WHERE DATE(e.fecha_inicio) BETWEEN %s AND %s",
            $inicio_mes, $fin_mes
        ));

        // Eventos verdes
        $eventos_verdes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_huella h
             JOIN $tabla_eventos e ON h.evento_id = e.id
             WHERE DATE(e.fecha_inicio) BETWEEN %s AND %s AND h.es_evento_verde = 1",
            $inicio_mes, $fin_mes
        ));

        // Índice de diversidad (tipos de eventos diferentes)
        $tipos_diferentes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT tipo) FROM $tabla_eventos WHERE DATE(fecha_inicio) BETWEEN %s AND %s AND estado = 'publicado'",
            $inicio_mes, $fin_mes
        ));

        return [
            'periodo' => $periodo,
            'total_eventos' => $total_eventos,
            'total_asistentes' => $total_asistentes,
            'usuarios_unicos' => $usuarios_unicos,
            'tasa_participacion' => $total_eventos > 0 ? round($total_asistentes / $total_eventos, 1) : 0,
            'horas_voluntariado' => round($horas_voluntariado, 1),
            'plazas_solidarias' => $plazas_solidarias,
            'co2_total_kg' => round($co2_total, 1),
            'eventos_verdes' => $eventos_verdes,
            'porcentaje_verdes' => $total_eventos > 0 ? round(($eventos_verdes / $total_eventos) * 100, 1) : 0,
            'indice_diversidad' => $tipos_diferentes,
        ];
    }

    public function obtener_participacion_usuario($usuario_id) {
        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tabla_voluntarios = $this->prefix . 'voluntarios';
        $tabla_solidarias = $this->prefix . 'plazas_solidarias';

        // Eventos asistidos
        $eventos_asistidos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE user_id = %d AND estado = 'confirmada'",
            $usuario_id
        ));

        // Horas voluntariado
        $horas_voluntariado = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas_realizadas), 0) FROM $tabla_voluntarios WHERE usuario_id = %d AND estado = 'asistio'",
            $usuario_id
        ));

        // Puntos
        $puntos = (int) get_user_meta($usuario_id, 'ev_puntos_voluntariado', true);

        // Plazas solidarias obtenidas
        $plazas_solidarias = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_solidarias WHERE usuario_id = %d AND estado = 'aprobada'",
            $usuario_id
        ));

        return [
            'eventos_asistidos' => $eventos_asistidos,
            'horas_voluntariado' => round($horas_voluntariado, 1),
            'puntos_voluntariado' => $puntos,
            'plazas_solidarias_obtenidas' => $plazas_solidarias,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // AJAX HANDLERS
    // ─────────────────────────────────────────────────────────────

    public function ajax_solicitar_plaza_solidaria() {
        check_ajax_referer('ev_conciencia_nonce', 'nonce');

        $resultado = $this->solicitar_plaza_solidaria(
            absint($_POST['evento_id']),
            get_current_user_id(),
            sanitize_textarea_field($_POST['motivo']),
            sanitize_text_field($_POST['situacion'])
        );

        wp_send_json($resultado);
    }

    public function ajax_apuntarse_voluntario() {
        check_ajax_referer('ev_conciencia_nonce', 'nonce');

        $resultado = $this->apuntarse_voluntario(
            absint($_POST['evento_id']),
            get_current_user_id(),
            sanitize_text_field($_POST['rol']),
            floatval($_POST['horas'] ?? 0)
        );

        wp_send_json($resultado);
    }

    public function ajax_registrar_huella() {
        check_ajax_referer('ev_conciencia_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $resultado = $this->calcular_huella_evento(absint($_POST['evento_id']));
        wp_send_json($resultado);
    }

    public function ajax_proponer_colaboracion() {
        check_ajax_referer('ev_conciencia_nonce', 'nonce');

        $resultado = $this->proponer_colaboracion(
            absint($_POST['evento_id']),
            get_current_user_id(),
            absint($_POST['co_organizador_id']),
            sanitize_text_field($_POST['tipo']),
            absint($_POST['porcentaje'] ?? 50)
        );

        wp_send_json($resultado);
    }

    // ─────────────────────────────────────────────────────────────
    // SHORTCODES
    // ─────────────────────────────────────────────────────────────

    public function shortcode_eventos_inclusivos($atts) {
        $atts = shortcode_atts(['limite' => 10], $atts);

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inclusividad = $this->prefix . 'inclusividad';

        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, i.* FROM $tabla_eventos e
             JOIN $tabla_inclusividad i ON e.id = i.evento_id
             WHERE e.estado = 'publicado' AND e.fecha_inicio > NOW()
             AND (i.accesibilidad_fisica = 1 OR i.interprete_lse = 1 OR i.plazas_solidarias > 0)
             ORDER BY e.fecha_inicio ASC LIMIT %d",
            $atts['limite']
        ));

        $nonce = wp_create_nonce('ev_conciencia_nonce');

        ob_start();
        include dirname(__FILE__) . '/templates/eventos-inclusivos.php';
        return ob_get_clean();
    }

    public function shortcode_huella_evento($atts) {
        $atts = shortcode_atts(['evento_id' => 0], $atts);

        if (!$atts['evento_id']) {
            return '<p>' . __('Especifica un evento.', 'flavor-chat-ia') . '</p>';
        }

        $huella = $this->obtener_huella_evento($atts['evento_id']);

        if (!$huella) {
            $resultado = $this->calcular_huella_evento($atts['evento_id']);
            $huella = $resultado['huella'] ?? null;
        }

        ob_start();
        include dirname(__FILE__) . '/templates/huella-evento.php';
        return ob_get_clean();
    }

    public function shortcode_voluntariado_eventos($atts) {
        $atts = shortcode_atts(['evento_id' => 0], $atts);

        global $wpdb;

        if ($atts['evento_id']) {
            $necesidades = $this->obtener_necesidades_voluntarios($atts['evento_id']);
            $evento_id = $atts['evento_id'];
        } else {
            // Mostrar eventos que necesitan voluntarios
            $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
            $tabla_necesidades = $this->prefix . 'voluntarios_necesidades';

            $eventos = $wpdb->get_results(
                "SELECT DISTINCT e.id, e.titulo, e.fecha_inicio, COUNT(n.id) as total_necesidades
                 FROM $tabla_eventos e
                 JOIN $tabla_necesidades n ON e.id = n.evento_id
                 WHERE e.estado = 'publicado' AND e.fecha_inicio > NOW()
                 AND n.cantidad_cubierta < n.cantidad_necesaria
                 GROUP BY e.id
                 ORDER BY e.fecha_inicio ASC LIMIT 10"
            );
            $necesidades = null;
            $evento_id = 0;
        }

        $nonce = wp_create_nonce('ev_conciencia_nonce');

        ob_start();
        include dirname(__FILE__) . '/templates/voluntariado-eventos.php';
        return ob_get_clean();
    }

    public function shortcode_dashboard_impacto($atts) {
        $atts = shortcode_atts(['periodo' => ''], $atts);

        $metricas = $this->calcular_metricas_impacto($atts['periodo'] ?: null);

        ob_start();
        include dirname(__FILE__) . '/templates/dashboard-impacto.php';
        return ob_get_clean();
    }

    public function shortcode_mi_participacion($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para ver tu participación.', 'flavor-chat-ia') . '</p>';
        }

        $participacion = $this->obtener_participacion_usuario(get_current_user_id());

        ob_start();
        include dirname(__FILE__) . '/templates/mi-participacion.php';
        return ob_get_clean();
    }
}
