<?php
/**
 * Módulo de Fichaje de Empleados para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Fichaje de Empleados - Control de horarios y asistencia
 */
class Flavor_Chat_Fichaje_Empleados_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'fichaje_empleados';
        $this->name = __('Fichaje de Empleados', 'flavor-chat-ia');
        $this->description = __('Control de horarios, asistencia y fichaje de empleados desde la app móvil.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        return Flavor_Chat_Helpers::tabla_existe($tabla_fichajes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Fichajes no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'horario_entrada' => '09:00',
            'horario_salida' => '18:00',
            'tiempo_gracia' => 15, // minutos
            'requiere_geolocalizacion' => false,
            'radio_maximo' => 100, // metros
            'dias_laborables' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'permite_fichaje_remoto' => true,
            'notificar_retrasos' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_fichajes)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';
        $tabla_horarios = $wpdb->prefix . 'flavor_empleados_horarios';

        $sql_fichajes = "CREATE TABLE IF NOT EXISTS $tabla_fichajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo enum('entrada','salida','pausa_inicio','pausa_fin') NOT NULL,
            fecha_hora datetime NOT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            dispositivo varchar(100) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            notas text DEFAULT NULL,
            validado tinyint(1) DEFAULT 1,
            validado_por bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY fecha_hora (fecha_hora),
            KEY tipo (tipo)
        ) $charset_collate;";

        $sql_horarios = "CREATE TABLE IF NOT EXISTS $tabla_horarios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            dia_semana enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
            hora_entrada time NOT NULL,
            hora_salida time NOT NULL,
            es_laboral tinyint(1) DEFAULT 1,
            activo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_dia (usuario_id, dia_semana)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_fichajes);
        dbDelta($sql_horarios);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'fichar' => [
                'description' => 'Registrar entrada, salida o pausa',
                'params' => ['tipo', 'latitud', 'longitud', 'notas'],
            ],
            'ver_fichajes_hoy' => [
                'description' => 'Ver fichajes del día actual',
                'params' => ['usuario_id'],
            ],
            'historial_fichajes' => [
                'description' => 'Ver historial de fichajes por periodo',
                'params' => ['usuario_id', 'desde', 'hasta'],
            ],
            'resumen_mensual' => [
                'description' => 'Obtener resumen de horas trabajadas del mes',
                'params' => ['usuario_id', 'mes', 'anio'],
            ],
            'estado_actual' => [
                'description' => 'Ver si el empleado está fichado o no',
                'params' => ['usuario_id'],
            ],
            'configurar_horario' => [
                'description' => 'Configurar horario semanal del empleado',
                'params' => ['usuario_id', 'horarios'],
            ],
            'empleados_presentes' => [
                'description' => 'Listar empleados actualmente en el lugar de trabajo',
                'params' => [],
            ],
            'fichar_entrada' => [
                'description' => 'Registrar fichaje de entrada',
                'params' => ['notas', 'latitud', 'longitud'],
            ],
            'fichar_salida' => [
                'description' => 'Registrar fichaje de salida',
                'params' => ['notas', 'latitud', 'longitud'],
            ],
            'pausar_jornada' => [
                'description' => 'Iniciar una pausa en la jornada',
                'params' => ['tipo_pausa'],
            ],
            'reanudar_jornada' => [
                'description' => 'Reanudar la jornada tras una pausa',
                'params' => [],
            ],
            'solicitar_cambio' => [
                'description' => 'Solicitar corrección de un fichaje',
                'params' => ['fecha', 'tipo', 'hora', 'motivo'],
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
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Fichar (entrada/salida/pausa)
     */
    private function action_fichar($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesión para fichar.',
            ];
        }

        $tipo = $params['tipo'] ?? 'entrada';
        $tipos_validos = ['entrada', 'salida', 'pausa_inicio', 'pausa_fin'];

        if (!in_array($tipo, $tipos_validos)) {
            return [
                'success' => false,
                'error' => 'Tipo de fichaje inválido.',
            ];
        }

        // Validar ubicación si está configurado
        $settings = $this->settings;
        if ($settings['requiere_geolocalizacion']) {
            $latitud = floatval($params['latitud'] ?? 0);
            $longitud = floatval($params['longitud'] ?? 0);

            if (!$latitud || !$longitud) {
                return [
                    'success' => false,
                    'error' => 'Se requiere geolocalización para fichar.',
                ];
            }
        }

        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        $resultado = $wpdb->insert(
            $tabla_fichajes,
            [
                'usuario_id' => $usuario_id,
                'tipo' => $tipo,
                'fecha_hora' => current_time('mysql'),
                'latitud' => $params['latitud'] ?? null,
                'longitud' => $params['longitud'] ?? null,
                'dispositivo' => $params['dispositivo'] ?? 'app_movil',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'notas' => sanitize_textarea_field($params['notas'] ?? ''),
                'validado' => 1,
            ],
            ['%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => 'Error al registrar el fichaje.',
            ];
        }

        $usuario = get_userdata($usuario_id);
        $tipo_texto = [
            'entrada' => 'entrada',
            'salida' => 'salida',
            'pausa_inicio' => 'inicio de pausa',
            'pausa_fin' => 'fin de pausa',
        ];

        return [
            'success' => true,
            'fichaje_id' => $wpdb->insert_id,
            'mensaje' => sprintf(
                '%s ha fichado %s correctamente a las %s',
                $usuario->display_name,
                $tipo_texto[$tipo],
                current_time('H:i')
            ),
        ];
    }

    /**
     * Acción: Ver fichajes de hoy
     */
    private function action_ver_fichajes_hoy($params) {
        $usuario_id = $params['usuario_id'] ?? get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Usuario no identificado.',
            ];
        }

        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        $hoy = current_time('Y-m-d');

        $fichajes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_fichajes
            WHERE usuario_id = %d
            AND DATE(fecha_hora) = %s
            ORDER BY fecha_hora ASC",
            $usuario_id,
            $hoy
        ));

        $fichajes_formateados = array_map(function($f) {
            return [
                'tipo' => $f->tipo,
                'hora' => date('H:i', strtotime($f->fecha_hora)),
                'notas' => $f->notas,
            ];
        }, $fichajes);

        // Calcular horas trabajadas
        $horas_trabajadas = $this->calcular_horas_trabajadas($fichajes);

        return [
            'success' => true,
            'fecha' => $hoy,
            'fichajes' => $fichajes_formateados,
            'total_fichajes' => count($fichajes),
            'horas_trabajadas' => $horas_trabajadas,
            'mensaje' => sprintf(
                'Hoy has trabajado %.2f horas con %d fichajes registrados.',
                $horas_trabajadas,
                count($fichajes)
            ),
        ];
    }

    /**
     * Acción: Estado actual del empleado
     */
    private function action_estado_actual($params) {
        $usuario_id = $params['usuario_id'] ?? get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Usuario no identificado.',
            ];
        }

        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        $ultimo_fichaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_fichajes
            WHERE usuario_id = %d
            ORDER BY fecha_hora DESC
            LIMIT 1",
            $usuario_id
        ));

        if (!$ultimo_fichaje) {
            return [
                'success' => true,
                'estado' => 'sin_fichar',
                'mensaje' => 'No has fichado hoy todavía.',
            ];
        }

        $estados = [
            'entrada' => 'trabajando',
            'salida' => 'fuera',
            'pausa_inicio' => 'en_pausa',
            'pausa_fin' => 'trabajando',
        ];

        $estado = $estados[$ultimo_fichaje->tipo] ?? 'desconocido';

        return [
            'success' => true,
            'estado' => $estado,
            'ultimo_fichaje' => [
                'tipo' => $ultimo_fichaje->tipo,
                'hora' => date('H:i', strtotime($ultimo_fichaje->fecha_hora)),
            ],
            'mensaje' => sprintf(
                'Tu último fichaje fue de %s a las %s. Estado actual: %s.',
                $ultimo_fichaje->tipo,
                date('H:i', strtotime($ultimo_fichaje->fecha_hora)),
                $estado
            ),
        ];
    }

    /**
     * Calcula horas trabajadas del día
     */
    private function calcular_horas_trabajadas($fichajes) {
        $horas = 0;
        $ultima_entrada = null;

        foreach ($fichajes as $fichaje) {
            if ($fichaje->tipo === 'entrada' || $fichaje->tipo === 'pausa_fin') {
                $ultima_entrada = strtotime($fichaje->fecha_hora);
            } elseif (($fichaje->tipo === 'salida' || $fichaje->tipo === 'pausa_inicio') && $ultima_entrada) {
                $salida = strtotime($fichaje->fecha_hora);
                $horas += ($salida - $ultima_entrada) / 3600;
                $ultima_entrada = null;
            }
        }

        return round($horas, 2);
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'fichaje_registrar',
                'description' => 'Registra un fichaje (entrada, salida o pausa)',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => [
                            'type' => 'string',
                            'description' => 'Tipo de fichaje',
                            'enum' => ['entrada', 'salida', 'pausa_inicio', 'pausa_fin'],
                        ],
                        'notas' => [
                            'type' => 'string',
                            'description' => 'Notas opcionales sobre el fichaje',
                        ],
                    ],
                    'required' => ['tipo'],
                ],
            ],
            [
                'name' => 'fichaje_ver_hoy',
                'description' => 'Ver fichajes del día actual y horas trabajadas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Fichaje de Empleados**

Control completo de horarios y asistencia de empleados desde la app móvil.

**Funcionalidades:**
- Fichaje de entrada y salida
- Control de pausas
- Registro de ubicación (opcional)
- Historial de fichajes
- Cálculo automático de horas trabajadas
- Resumen mensual de asistencia
- Alertas de retrasos
- Configuración de horarios personalizados

**Tipos de fichaje:**
- Entrada: Inicio de jornada laboral
- Salida: Fin de jornada laboral
- Pausa inicio: Comienzo de descanso
- Pausa fin: Fin del descanso

**Validación:**
- Se puede configurar verificación por geolocalización
- Radio máximo permitido desde el centro de trabajo
- Fichaje remoto (opcional)
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo fichar desde la app?',
                'respuesta' => 'Abre la app, ve a la sección Fichaje y pulsa el botón correspondiente (Entrada, Salida, Pausa).',
            ],
            [
                'pregunta' => '¿Puedo ver mis horas trabajadas?',
                'respuesta' => 'Sí, puedes ver tus fichajes del día, semana o mes con el total de horas trabajadas.',
            ],
            [
                'pregunta' => '¿Qué pasa si olvido fichar?',
                'respuesta' => 'Los administradores pueden validar y corregir fichajes manualmente desde el panel de control.',
            ],
        ];
    }

    /**
     * Configuración de formularios del módulo
     *
     * @param string $action_name Nombre de la acción
     * @return array Configuración del formulario
     */
    public function get_form_config($action_name) {
        $configs = [
            'fichar_entrada' => [
                'title' => __('Fichar Entrada', 'flavor-chat-ia'),
                'description' => __('Registra tu entrada al trabajo', 'flavor-chat-ia'),
                'fields' => [
                    'notas' => [
                        'type' => 'textarea',
                        'label' => __('Notas (opcional)', 'flavor-chat-ia'),
                        'rows' => 2,
                        'placeholder' => __('Proyecto, tarea, ubicación...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Fichar Entrada', 'flavor-chat-ia'),
                'success_message' => __('Entrada registrada correctamente', 'flavor-chat-ia'),
            ],
            'fichar_salida' => [
                'title' => __('Fichar Salida', 'flavor-chat-ia'),
                'description' => __('Registra tu salida del trabajo', 'flavor-chat-ia'),
                'fields' => [
                    'notas' => [
                        'type' => 'textarea',
                        'label' => __('Resumen del día (opcional)', 'flavor-chat-ia'),
                        'rows' => 3,
                        'placeholder' => __('¿Qué has hecho hoy?', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Fichar Salida', 'flavor-chat-ia'),
                'success_message' => __('Salida registrada correctamente. ¡Buen trabajo!', 'flavor-chat-ia'),
            ],
            'pausar_jornada' => [
                'title' => __('Iniciar Pausa', 'flavor-chat-ia'),
                'description' => __('Pausa tu jornada temporalmente', 'flavor-chat-ia'),
                'fields' => [
                    'tipo_pausa' => [
                        'type' => 'select',
                        'label' => __('Tipo de pausa', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'comida' => __('Comida', 'flavor-chat-ia'),
                            'descanso' => __('Descanso', 'flavor-chat-ia'),
                            'reunion' => __('Reunión externa', 'flavor-chat-ia'),
                            'otros' => __('Otros', 'flavor-chat-ia'),
                        ],
                    ],
                ],
                'submit_text' => __('Iniciar Pausa', 'flavor-chat-ia'),
                'success_message' => __('Pausa iniciada', 'flavor-chat-ia'),
            ],
            'reanudar_jornada' => [
                'title' => __('Reanudar Jornada', 'flavor-chat-ia'),
                'description' => __('Vuelve al trabajo tras la pausa', 'flavor-chat-ia'),
                'fields' => [],
                'submit_text' => __('Reanudar Jornada', 'flavor-chat-ia'),
                'success_message' => __('Jornada reanudada', 'flavor-chat-ia'),
            ],
            'solicitar_cambio' => [
                'title' => __('Solicitar Corrección de Fichaje', 'flavor-chat-ia'),
                'description' => __('¿Olvidaste fichar? Solicita una corrección', 'flavor-chat-ia'),
                'fields' => [
                    'fecha' => [
                        'type' => 'date',
                        'label' => __('Fecha del fichaje', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'tipo' => [
                        'type' => 'select',
                        'label' => __('Tipo de fichaje', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'entrada' => __('Entrada', 'flavor-chat-ia'),
                            'salida' => __('Salida', 'flavor-chat-ia'),
                        ],
                    ],
                    'hora' => [
                        'type' => 'time',
                        'label' => __('Hora correcta', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'motivo' => [
                        'type' => 'textarea',
                        'label' => __('Motivo de la corrección', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 3,
                        'placeholder' => __('Explica por qué necesitas esta corrección...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Solicitar Corrección', 'flavor-chat-ia'),
                'success_message' => __('Solicitud enviada. Pendiente de validación.', 'flavor-chat-ia'),
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Fichaje', 'flavor-chat-ia'),
                'description' => __('Sección hero con botón de fichaje rápido', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Control de Presencia', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Registra tu entrada y salida de forma sencilla', 'flavor-chat-ia'),
                    ],
                    'mostrar_reloj' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar reloj', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'fichaje-empleados/hero',
            ],
            'boton_fichaje' => [
                'label' => __('Botón de Fichaje', 'flavor-chat-ia'),
                'description' => __('Botón grande para fichar entrada/salida', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-yes-alt',
                'fields' => [
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['grande', 'compacto'],
                        'default' => 'grande',
                    ],
                    'mostrar_estado' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estado actual', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'fichaje-empleados/boton-fichaje',
            ],
            'historial' => [
                'label' => __('Historial de Fichajes', 'flavor-chat-ia'),
                'description' => __('Tabla con registro de fichajes', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Mis Fichajes', 'flavor-chat-ia'),
                    ],
                    'periodo' => [
                        'type' => 'select',
                        'label' => __('Periodo', 'flavor-chat-ia'),
                        'options' => ['hoy', 'semana', 'mes'],
                        'default' => 'semana',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 30,
                    ],
                ],
                'template' => 'fichaje-empleados/historial',
            ],
            'resumen_horas' => [
                'label' => __('Resumen de Horas', 'flavor-chat-ia'),
                'description' => __('Estadísticas de horas trabajadas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Resumen de Horas', 'flavor-chat-ia'),
                    ],
                    'mostrar_grafico' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar gráfico', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'fichaje-empleados/resumen-horas',
            ],
        ];
    }

    // =========================================================
    // Acciones delegadas para formularios frontend
    // =========================================================

    private function action_fichar_entrada($params) {
        $params['tipo'] = 'entrada';
        return $this->action_fichar($params);
    }

    private function action_fichar_salida($params) {
        $params['tipo'] = 'salida';
        return $this->action_fichar($params);
    }

    private function action_pausar_jornada($params) {
        $params['tipo'] = 'pausa_inicio';
        return $this->action_fichar($params);
    }

    private function action_reanudar_jornada($params) {
        $params['tipo'] = 'pausa_fin';
        return $this->action_fichar($params);
    }

    private function action_solicitar_cambio($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesion para solicitar un cambio.',
            ];
        }

        $fecha = sanitize_text_field($params['fecha'] ?? '');
        $tipo = sanitize_text_field($params['tipo'] ?? '');
        $hora = sanitize_text_field($params['hora'] ?? '');
        $motivo = sanitize_textarea_field($params['motivo'] ?? '');

        if (empty($fecha) || empty($tipo) || empty($hora) || empty($motivo)) {
            return [
                'success' => false,
                'error' => 'Todos los campos son obligatorios.',
            ];
        }

        // Registrar la solicitud de correccion
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_fichajes';

        $wpdb->insert($tabla, [
            'usuario_id' => $usuario_id,
            'tipo' => sanitize_text_field($tipo),
            'fecha_hora' => $fecha . ' ' . $hora . ':00',
            'notas' => '[CORRECCION SOLICITADA] ' . $motivo,
            'estado' => 'pendiente_revision',
        ]);

        return [
            'success' => true,
            'mensaje' => 'Solicitud de correccion enviada. Un administrador la revisara.',
        ];
    }
}
