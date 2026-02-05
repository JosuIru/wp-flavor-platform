<?php
/**
 * Modulo de Reservas Generico para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Reservas - Gestion generica de reservas para distintos tipos de negocio
 */
class Flavor_Chat_Reservas_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'reservas';
        $this->name = __('Reservas', 'flavor-chat-ia');
        $this->description = __('Gestion generica de reservas: mesas, espacios, clases y mas. Permite crear, cancelar, modificar y consultar disponibilidad.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * Verifica si el modulo puede activarse
     */
    public function can_activate() {
        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        return Flavor_Chat_Helpers::tabla_existe($nombre_tabla_reservas);
    }

    /**
     * Mensaje si no puede activarse
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Reservas no estan creadas. Activa el modulo para crearlas automaticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * Configuracion por defecto
     */
    protected function get_default_settings() {
        return [
            'hora_apertura'        => '09:00',
            'hora_cierre'          => '22:00',
            'duracion_por_defecto' => 60,
            'capacidad_maxima'     => 50,
            'dias_antelacion'      => 30,
            'tipos_servicio'       => [
                'mesa_restaurante'  => __('Mesa de Restaurante', 'flavor-chat-ia'),
                'espacio_coworking' => __('Espacio Coworking', 'flavor-chat-ia'),
                'clase_deportiva'   => __('Clase Deportiva', 'flavor-chat-ia'),
            ],
            'estados_reserva'      => [
                'pendiente'  => __('Pendiente', 'flavor-chat-ia'),
                'confirmada' => __('Confirmada', 'flavor-chat-ia'),
                'cancelada'  => __('Cancelada', 'flavor-chat-ia'),
                'completada' => __('Completada', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Inicializa el modulo
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
    }

    /**
     * Crea la tabla si no existe
     */
    public function maybe_create_tables() {
        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        if (!Flavor_Chat_Helpers::tabla_existe($nombre_tabla_reservas)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        $ruta_instalador = FLAVOR_CHAT_IA_PATH . 'includes/modules/reservas/install.php';

        if (file_exists($ruta_instalador)) {
            require_once $ruta_instalador;
            flavor_reservas_crear_tabla();
        }
    }

    /**
     * Obtiene las acciones disponibles del modulo
     */
    public function get_actions() {
        return [
            'crear_reserva' => [
                'description' => 'Crear una nueva reserva',
                'params'      => ['tipo_servicio', 'nombre_cliente', 'email_cliente', 'telefono_cliente', 'fecha_reserva', 'hora_inicio', 'hora_fin', 'num_personas', 'notas'],
            ],
            'cancelar_reserva' => [
                'description' => 'Cancelar una reserva existente por su ID',
                'params'      => ['reserva_id'],
            ],
            'mis_reservas' => [
                'description' => 'Listar las reservas del usuario actual (por email o user_id)',
                'params'      => ['email', 'estado', 'limite'],
            ],
            'disponibilidad' => [
                'description' => 'Comprobar disponibilidad en una fecha y hora concretas',
                'params'      => ['tipo_servicio', 'fecha_reserva', 'hora_inicio', 'hora_fin', 'num_personas'],
            ],
            'modificar_reserva' => [
                'description' => 'Modificar fecha u hora de una reserva existente',
                'params'      => ['reserva_id', 'fecha_reserva', 'hora_inicio', 'hora_fin', 'num_personas'],
            ],
        ];
    }

    /**
     * Ejecuta una accion del modulo
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error'   => "Accion no implementada: {$action_name}",
        ];
    }

    // =========================================================================
    // Acciones del modulo
    // =========================================================================

    /**
     * Accion: Crear una nueva reserva
     */
    private function action_crear_reserva($params) {
        $campos_obligatorios = ['nombre_cliente', 'email_cliente', 'fecha_reserva', 'hora_inicio'];
        foreach ($campos_obligatorios as $campo_requerido) {
            if (empty($params[$campo_requerido])) {
                return [
                    'success' => false,
                    'error'   => sprintf(__('El campo %s es obligatorio.', 'flavor-chat-ia'), $campo_requerido),
                ];
            }
        }

        $email_sanitizado = sanitize_email($params['email_cliente']);
        if (!is_email($email_sanitizado)) {
            return ['success' => false, 'error' => __('El email proporcionado no es valido.', 'flavor-chat-ia')];
        }

        $fecha_reserva = sanitize_text_field($params['fecha_reserva']);
        $fecha_hoy     = current_time('Y-m-d');
        if ($fecha_reserva < $fecha_hoy) {
            return ['success' => false, 'error' => __('No se puede reservar en una fecha pasada.', 'flavor-chat-ia')];
        }

        $dias_antelacion_maxima = $this->get_setting('dias_antelacion', 30);
        $fecha_limite = date('Y-m-d', strtotime("+{$dias_antelacion_maxima} days"));
        if ($fecha_reserva > $fecha_limite) {
            return ['success' => false, 'error' => sprintf(__('No se puede reservar con mas de %d dias de antelacion.', 'flavor-chat-ia'), $dias_antelacion_maxima)];
        }

        $hora_inicio = sanitize_text_field($params['hora_inicio']);
        $hora_fin    = !empty($params['hora_fin'])
            ? sanitize_text_field($params['hora_fin'])
            : date('H:i:s', strtotime($hora_inicio) + ($this->get_setting('duracion_por_defecto', 60) * 60));

        $hora_apertura = $this->get_setting('hora_apertura', '09:00');
        $hora_cierre   = $this->get_setting('hora_cierre', '22:00');

        if ($hora_inicio < $hora_apertura || $hora_fin > $hora_cierre) {
            return ['success' => false, 'error' => sprintf(__('El horario de reservas es de %s a %s.', 'flavor-chat-ia'), $hora_apertura, $hora_cierre)];
        }

        $numero_personas = absint($params['num_personas'] ?? 1);
        if ($numero_personas < 1) { $numero_personas = 1; }

        $capacidad_maxima = $this->get_setting('capacidad_maxima', 50);
        $ocupacion_actual = $this->obtener_ocupacion_en_franja($fecha_reserva, $hora_inicio, $hora_fin);

        if (($ocupacion_actual + $numero_personas) > $capacidad_maxima) {
            return ['success' => false, 'error' => __('No hay disponibilidad suficiente para esa franja horaria.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $tipo_servicio         = sanitize_text_field($params['tipo_servicio'] ?? 'mesa_restaurante');
        $nombre_cliente        = sanitize_text_field($params['nombre_cliente']);
        $telefono_cliente      = sanitize_text_field($params['telefono_cliente'] ?? '');
        $notas_reserva         = sanitize_textarea_field($params['notas'] ?? '');
        $identificador_usuario = get_current_user_id() ?: null;

        $resultado_insercion = $wpdb->insert($nombre_tabla_reservas, [
            'tipo_servicio'    => $tipo_servicio,
            'nombre_cliente'   => $nombre_cliente,
            'email_cliente'    => $email_sanitizado,
            'telefono_cliente' => $telefono_cliente,
            'fecha_reserva'    => $fecha_reserva,
            'hora_inicio'      => $hora_inicio,
            'hora_fin'         => $hora_fin,
            'num_personas'     => $numero_personas,
            'estado'           => 'pendiente',
            'notas'            => $notas_reserva,
            'user_id'          => $identificador_usuario,
            'created_at'       => current_time('mysql'),
            'updated_at'       => current_time('mysql'),
        ], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s']);

        if ($resultado_insercion === false) {
            return ['success' => false, 'error' => __('Error al crear la reserva. Intentalo de nuevo.', 'flavor-chat-ia')];
        }

        $identificador_reserva = $wpdb->insert_id;

        return [
            'success' => true,
            'mensaje' => sprintf(__('Reserva #%d creada correctamente para el %s a las %s.', 'flavor-chat-ia'), $identificador_reserva, date('d/m/Y', strtotime($fecha_reserva)), $hora_inicio),
            'reserva' => [
                'id'             => $identificador_reserva,
                'tipo_servicio'  => $tipo_servicio,
                'fecha'          => $fecha_reserva,
                'hora_inicio'    => $hora_inicio,
                'hora_fin'       => $hora_fin,
                'num_personas'   => $numero_personas,
                'estado'         => 'pendiente',
                'nombre_cliente' => $nombre_cliente,
            ],
        ];
    }

    /**
     * Accion: Cancelar una reserva existente
     */
    private function action_cancelar_reserva($params) {
        if (empty($params['reserva_id'])) {
            return ['success' => false, 'error' => __('Se requiere el ID de la reserva.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $identificador_reserva = absint($params['reserva_id']);

        $reserva_encontrada = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $nombre_tabla_reservas WHERE id = %d",
            $identificador_reserva
        ));

        if (!$reserva_encontrada) {
            return ['success' => false, 'error' => __('Reserva no encontrada.', 'flavor-chat-ia')];
        }

        $identificador_usuario_actual = get_current_user_id();
        $es_propietario   = ($reserva_encontrada->user_id && $reserva_encontrada->user_id == $identificador_usuario_actual);
        $es_administrador = current_user_can('manage_options');

        if (!$es_propietario && !$es_administrador && $identificador_usuario_actual) {
            return ['success' => false, 'error' => __('No tienes permisos para cancelar esta reserva.', 'flavor-chat-ia')];
        }

        if ($reserva_encontrada->estado === 'cancelada') {
            return ['success' => false, 'error' => __('Esta reserva ya esta cancelada.', 'flavor-chat-ia')];
        }

        if ($reserva_encontrada->estado === 'completada') {
            return ['success' => false, 'error' => __('No se puede cancelar una reserva ya completada.', 'flavor-chat-ia')];
        }

        $resultado_actualizacion = $wpdb->update($nombre_tabla_reservas, ['estado' => 'cancelada', 'updated_at' => current_time('mysql')], ['id' => $identificador_reserva], ['%s', '%s'], ['%d']);

        if ($resultado_actualizacion === false) {
            return ['success' => false, 'error' => __('Error al cancelar la reserva.', 'flavor-chat-ia')];
        }

        return ['success' => true, 'mensaje' => sprintf(__('Reserva #%d cancelada correctamente.', 'flavor-chat-ia'), $identificador_reserva)];
    }

    /**
     * Accion: Listar reservas del usuario
     */
    private function action_mis_reservas($params) {
        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $condiciones_where  = [];
        $valores_preparados = [];

        $identificador_usuario_actual = get_current_user_id();
        if ($identificador_usuario_actual) {
            $condiciones_where[]  = 'user_id = %d';
            $valores_preparados[] = $identificador_usuario_actual;
        } elseif (!empty($params['email'])) {
            $condiciones_where[]  = 'email_cliente = %s';
            $valores_preparados[] = sanitize_email($params['email']);
        } else {
            return ['success' => false, 'error' => __('Debes iniciar sesion o proporcionar un email.', 'flavor-chat-ia')];
        }

        if (!empty($params['estado'])) {
            $condiciones_where[]  = 'estado = %s';
            $valores_preparados[] = sanitize_text_field($params['estado']);
        }

        $limite_resultados    = absint($params['limite'] ?? 20);
        $clausula_where       = implode(' AND ', $condiciones_where);
        $valores_preparados[] = $limite_resultados;

        $consulta_sql = "SELECT * FROM $nombre_tabla_reservas WHERE $clausula_where ORDER BY fecha_reserva DESC, hora_inicio DESC LIMIT %d";
        $listado_reservas = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$valores_preparados));

        if (empty($listado_reservas)) {
            return ['success' => true, 'mensaje' => __('No se encontraron reservas.', 'flavor-chat-ia'), 'reservas' => []];
        }

        $reservas_formateadas = array_map(function ($reserva) {
            return [
                'id'             => $reserva->id,
                'tipo_servicio'  => $reserva->tipo_servicio,
                'fecha'          => date('d/m/Y', strtotime($reserva->fecha_reserva)),
                'hora_inicio'    => $reserva->hora_inicio,
                'hora_fin'       => $reserva->hora_fin,
                'num_personas'   => $reserva->num_personas,
                'estado'         => $reserva->estado,
                'nombre_cliente' => $reserva->nombre_cliente,
                'notas'          => $reserva->notas,
            ];
        }, $listado_reservas);

        return ['success' => true, 'total' => count($reservas_formateadas), 'reservas' => $reservas_formateadas];
    }

    /**
     * Accion: Comprobar disponibilidad
     */
    private function action_disponibilidad($params) {
        if (empty($params['fecha_reserva'])) {
            return ['success' => false, 'error' => __('Se requiere la fecha para consultar disponibilidad.', 'flavor-chat-ia')];
        }

        $fecha_reserva    = sanitize_text_field($params['fecha_reserva']);
        $hora_inicio      = sanitize_text_field($params['hora_inicio'] ?? $this->get_setting('hora_apertura', '09:00'));
        $hora_fin         = sanitize_text_field($params['hora_fin'] ?? $this->get_setting('hora_cierre', '22:00'));
        $numero_personas  = absint($params['num_personas'] ?? 1);
        $capacidad_maxima = $this->get_setting('capacidad_maxima', 50);

        $fecha_hoy = current_time('Y-m-d');
        if ($fecha_reserva < $fecha_hoy) {
            return ['success' => false, 'error' => __('No se puede consultar disponibilidad para fechas pasadas.', 'flavor-chat-ia')];
        }

        $ocupacion_en_franja = $this->obtener_ocupacion_en_franja($fecha_reserva, $hora_inicio, $hora_fin);
        $plazas_disponibles  = $capacidad_maxima - $ocupacion_en_franja;
        $hay_disponibilidad  = $plazas_disponibles >= $numero_personas;
        $franjas_disponibles = $this->obtener_franjas_disponibles($fecha_reserva);

        return [
            'success'       => true,
            'disponible'    => $hay_disponibilidad,
            'fecha'         => date('d/m/Y', strtotime($fecha_reserva)),
            'hora_inicio'   => $hora_inicio,
            'hora_fin'      => $hora_fin,
            'ocupacion'     => $ocupacion_en_franja,
            'capacidad'     => $capacidad_maxima,
            'plazas_libres' => max(0, $plazas_disponibles),
            'franjas'       => $franjas_disponibles,
            'mensaje'       => $hay_disponibilidad
                ? sprintf(__('Hay %d plazas disponibles.', 'flavor-chat-ia'), $plazas_disponibles)
                : __('No hay disponibilidad para esa franja. Consulta otras franjas horarias.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Modificar fecha/hora de una reserva
     */
    private function action_modificar_reserva($params) {
        if (empty($params['reserva_id'])) {
            return ['success' => false, 'error' => __('Se requiere el ID de la reserva.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $identificador_reserva = absint($params['reserva_id']);

        $reserva_encontrada = $wpdb->get_row($wpdb->prepare("SELECT * FROM $nombre_tabla_reservas WHERE id = %d", $identificador_reserva));

        if (!$reserva_encontrada) {
            return ['success' => false, 'error' => __('Reserva no encontrada.', 'flavor-chat-ia')];
        }

        $identificador_usuario_actual = get_current_user_id();
        $es_propietario   = ($reserva_encontrada->user_id && $reserva_encontrada->user_id == $identificador_usuario_actual);
        $es_administrador = current_user_can('manage_options');

        if (!$es_propietario && !$es_administrador && $identificador_usuario_actual) {
            return ['success' => false, 'error' => __('No tienes permisos para modificar esta reserva.', 'flavor-chat-ia')];
        }

        if (in_array($reserva_encontrada->estado, ['cancelada', 'completada'], true)) {
            return ['success' => false, 'error' => __('No se puede modificar una reserva cancelada o completada.', 'flavor-chat-ia')];
        }

        $datos_actualizados = [];
        $formatos_datos     = [];

        if (!empty($params['fecha_reserva'])) {
            $nueva_fecha = sanitize_text_field($params['fecha_reserva']);
            if ($nueva_fecha < current_time('Y-m-d')) {
                return ['success' => false, 'error' => __('No se puede cambiar a una fecha pasada.', 'flavor-chat-ia')];
            }
            $datos_actualizados['fecha_reserva'] = $nueva_fecha;
            $formatos_datos[] = '%s';
        }

        if (!empty($params['hora_inicio'])) {
            $datos_actualizados['hora_inicio'] = sanitize_text_field($params['hora_inicio']);
            $formatos_datos[] = '%s';
        }

        if (!empty($params['hora_fin'])) {
            $datos_actualizados['hora_fin'] = sanitize_text_field($params['hora_fin']);
            $formatos_datos[] = '%s';
        }

        if (!empty($params['num_personas'])) {
            $datos_actualizados['num_personas'] = absint($params['num_personas']);
            $formatos_datos[] = '%d';
        }

        if (empty($datos_actualizados)) {
            return ['success' => false, 'error' => __('No se proporcionaron datos para modificar.', 'flavor-chat-ia')];
        }

        $fecha_verificacion       = $datos_actualizados['fecha_reserva'] ?? $reserva_encontrada->fecha_reserva;
        $hora_inicio_verificacion = $datos_actualizados['hora_inicio'] ?? $reserva_encontrada->hora_inicio;
        $hora_fin_verificacion    = $datos_actualizados['hora_fin'] ?? $reserva_encontrada->hora_fin;
        $personas_verificacion    = $datos_actualizados['num_personas'] ?? $reserva_encontrada->num_personas;

        $ocupacion_franja = $this->obtener_ocupacion_en_franja($fecha_verificacion, $hora_inicio_verificacion, $hora_fin_verificacion, $identificador_reserva);
        $capacidad_maxima = $this->get_setting('capacidad_maxima', 50);

        if (($ocupacion_franja + $personas_verificacion) > $capacidad_maxima) {
            return ['success' => false, 'error' => __('No hay disponibilidad para los nuevos datos solicitados.', 'flavor-chat-ia')];
        }

        $datos_actualizados['updated_at'] = current_time('mysql');
        $formatos_datos[] = '%s';

        $resultado_actualizacion = $wpdb->update($nombre_tabla_reservas, $datos_actualizados, ['id' => $identificador_reserva], $formatos_datos, ['%d']);

        if ($resultado_actualizacion === false) {
            return ['success' => false, 'error' => __('Error al modificar la reserva.', 'flavor-chat-ia')];
        }

        return ['success' => true, 'mensaje' => sprintf(__('Reserva #%d modificada correctamente.', 'flavor-chat-ia'), $identificador_reserva)];
    }

    // =========================================================================
    // Metodos auxiliares
    // =========================================================================

    /**
     * Obtiene la ocupacion total en una franja horaria
     */
    private function obtener_ocupacion_en_franja($fecha_consulta, $hora_inicio_consulta, $hora_fin_consulta, $excluir_reserva_id = null) {
        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        $consulta_sql = "SELECT IFNULL(SUM(num_personas), 0)
            FROM $nombre_tabla_reservas
            WHERE fecha_reserva = %s
              AND estado IN ('pendiente', 'confirmada')
              AND hora_inicio < %s
              AND hora_fin > %s";

        $valores_preparados = [$fecha_consulta, $hora_fin_consulta, $hora_inicio_consulta];

        if ($excluir_reserva_id) {
            $consulta_sql .= " AND id != %d";
            $valores_preparados[] = $excluir_reserva_id;
        }

        $ocupacion_total = $wpdb->get_var($wpdb->prepare($consulta_sql, ...$valores_preparados));
        return absint($ocupacion_total);
    }

    /**
     * Obtiene las franjas horarias disponibles para un dia
     */
    private function obtener_franjas_disponibles($fecha_consulta) {
        $hora_apertura    = $this->get_setting('hora_apertura', '09:00');
        $hora_cierre      = $this->get_setting('hora_cierre', '22:00');
        $duracion_franja  = $this->get_setting('duracion_por_defecto', 60);
        $capacidad_maxima = $this->get_setting('capacidad_maxima', 50);

        $franjas_resultado   = [];
        $marca_tiempo_inicio = strtotime($fecha_consulta . ' ' . $hora_apertura);
        $marca_tiempo_cierre = strtotime($fecha_consulta . ' ' . $hora_cierre);

        while ($marca_tiempo_inicio < $marca_tiempo_cierre) {
            $marca_tiempo_fin_franja = $marca_tiempo_inicio + ($duracion_franja * 60);
            if ($marca_tiempo_fin_franja > $marca_tiempo_cierre) { break; }

            $hora_inicio_franja = date('H:i', $marca_tiempo_inicio);
            $hora_fin_franja    = date('H:i', $marca_tiempo_fin_franja);
            $ocupacion_franja   = $this->obtener_ocupacion_en_franja($fecha_consulta, $hora_inicio_franja, $hora_fin_franja);
            $plazas_libres      = $capacidad_maxima - $ocupacion_franja;

            $franjas_resultado[] = [
                'hora_inicio'   => $hora_inicio_franja,
                'hora_fin'      => $hora_fin_franja,
                'ocupacion'     => $ocupacion_franja,
                'plazas_libres' => max(0, $plazas_libres),
                'disponible'    => $plazas_libres > 0,
            ];

            $marca_tiempo_inicio = $marca_tiempo_fin_franja;
        }

        return $franjas_resultado;
    }

    // =========================================================================
    // Definiciones de herramientas para IA
    // =========================================================================

    /**
     * Obtiene las definiciones de tools para Claude
     */
    public function get_tool_definitions() {
        return [
            [
                'name'         => 'reservas_crear_reserva',
                'description'  => 'Crear una nueva reserva (mesa, espacio, clase, etc.)',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'tipo_servicio'   => ['type' => 'string', 'description' => 'Tipo de servicio a reservar', 'enum' => ['mesa_restaurante', 'espacio_coworking', 'clase_deportiva']],
                        'nombre_cliente'  => ['type' => 'string', 'description' => 'Nombre completo del cliente'],
                        'email_cliente'   => ['type' => 'string', 'description' => 'Email de contacto del cliente'],
                        'telefono_cliente' => ['type' => 'string', 'description' => 'Telefono de contacto'],
                        'fecha_reserva'   => ['type' => 'string', 'description' => 'Fecha de la reserva en formato YYYY-MM-DD'],
                        'hora_inicio'     => ['type' => 'string', 'description' => 'Hora de inicio en formato HH:MM'],
                        'hora_fin'        => ['type' => 'string', 'description' => 'Hora de fin en formato HH:MM (opcional)'],
                        'num_personas'    => ['type' => 'integer', 'description' => 'Numero de personas'],
                        'notas'           => ['type' => 'string', 'description' => 'Notas adicionales sobre la reserva'],
                    ],
                    'required' => ['nombre_cliente', 'email_cliente', 'fecha_reserva', 'hora_inicio'],
                ],
            ],
            [
                'name'         => 'reservas_cancelar_reserva',
                'description'  => 'Cancelar una reserva existente por su ID',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => ['reserva_id' => ['type' => 'integer', 'description' => 'ID de la reserva a cancelar']],
                    'required'   => ['reserva_id'],
                ],
            ],
            [
                'name'         => 'reservas_mis_reservas',
                'description'  => 'Listar las reservas del usuario actual o buscar por email',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'email'  => ['type' => 'string', 'description' => 'Email para buscar reservas (si no hay sesion)'],
                        'estado' => ['type' => 'string', 'description' => 'Filtrar por estado', 'enum' => ['pendiente', 'confirmada', 'cancelada', 'completada']],
                        'limite' => ['type' => 'integer', 'description' => 'Numero maximo de resultados'],
                    ],
                ],
            ],
            [
                'name'         => 'reservas_disponibilidad',
                'description'  => 'Comprobar disponibilidad de reservas en una fecha y franja horaria',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'tipo_servicio' => ['type' => 'string', 'description' => 'Tipo de servicio'],
                        'fecha_reserva' => ['type' => 'string', 'description' => 'Fecha a consultar en formato YYYY-MM-DD'],
                        'hora_inicio'   => ['type' => 'string', 'description' => 'Hora de inicio de la franja (HH:MM)'],
                        'hora_fin'      => ['type' => 'string', 'description' => 'Hora de fin de la franja (HH:MM)'],
                        'num_personas'  => ['type' => 'integer', 'description' => 'Numero de personas para la reserva'],
                    ],
                    'required' => ['fecha_reserva'],
                ],
            ],
            [
                'name'         => 'reservas_modificar_reserva',
                'description'  => 'Modificar la fecha, hora o numero de personas de una reserva existente',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'reserva_id'    => ['type' => 'integer', 'description' => 'ID de la reserva a modificar'],
                        'fecha_reserva' => ['type' => 'string', 'description' => 'Nueva fecha en formato YYYY-MM-DD'],
                        'hora_inicio'   => ['type' => 'string', 'description' => 'Nueva hora de inicio (HH:MM)'],
                        'hora_fin'      => ['type' => 'string', 'description' => 'Nueva hora de fin (HH:MM)'],
                        'num_personas'  => ['type' => 'integer', 'description' => 'Nuevo numero de personas'],
                    ],
                    'required' => ['reserva_id'],
                ],
            ],
        ];
    }

    // =========================================================================
    // Knowledge base y FAQs
    // =========================================================================

    /**
     * Obtiene el conocimiento base del modulo
     */
    public function get_knowledge_base() {
        $hora_apertura = $this->get_setting('hora_apertura', '09:00');
        $hora_cierre   = $this->get_setting('hora_cierre', '22:00');
        $capacidad     = $this->get_setting('capacidad_maxima', 50);
        $antelacion    = $this->get_setting('dias_antelacion', 30);

        return "**Sistema de Reservas**\n\n" .
            "Gestion de reservas para distintos tipos de servicio.\n\n" .
            "**Funcionalidades:**\n" .
            "- Crear nuevas reservas con fecha, hora y numero de personas\n" .
            "- Cancelar reservas existentes\n" .
            "- Modificar fecha/hora de reservas\n" .
            "- Consultar disponibilidad por fecha y franja horaria\n" .
            "- Ver historial de reservas del usuario\n\n" .
            "**Configuracion actual:**\n" .
            "- Horario: de $hora_apertura a $hora_cierre\n" .
            "- Capacidad maxima simultanea: $capacidad personas\n" .
            "- Reservas con hasta $antelacion dias de antelacion\n\n" .
            "**Estados de reserva:**\n" .
            "- Pendiente: Reserva recien creada\n" .
            "- Confirmada: Reserva confirmada por el establecimiento\n" .
            "- Cancelada: Reserva cancelada\n" .
            "- Completada: Reserva que ya se ha realizado\n\n" .
            "**Tipos de servicio:** mesa_restaurante, espacio_coworking, clase_deportiva";
    }

    /**
     * Obtiene las FAQs del modulo
     */
    public function get_faqs() {
        return [
            ['pregunta' => 'Como puedo hacer una reserva?', 'respuesta' => 'Puedes hacer una reserva indicandome la fecha, hora y numero de personas. Yo me encargo de comprobar la disponibilidad y crear la reserva.'],
            ['pregunta' => 'Puedo cancelar mi reserva?', 'respuesta' => 'Si, puedes cancelar tu reserva en cualquier momento siempre que no se haya completado ya. Solo necesito el numero de reserva.'],
            ['pregunta' => 'Como cambio la fecha u hora de mi reserva?', 'respuesta' => 'Indicame el numero de reserva y los nuevos datos. Compruebo disponibilidad y la modifico al momento.'],
            ['pregunta' => 'Como puedo ver mis reservas?', 'respuesta' => 'Si tienes cuenta, veo tus reservas automaticamente. Si no, indicame tu email y busco las reservas asociadas.'],
        ];
    }
}
