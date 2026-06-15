<?php
/**
 * Módulo Ahorro Rotativo (panderos / tandas / círculos de ahorro).
 *
 * Un grupo aporta una cantidad fija cada periodo y, por turnos, a cada
 * miembro le toca llevarse el bote. La plataforma coordina y da
 * transparencia; no mueve dinero. La confianza se autorregula con la
 * reputación de reciprocidad derivada de las aportaciones cumplidas.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_Ahorro_Rotativo_Module extends Flavor_Platform_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    public function __construct() {
        $this->id = 'ahorro_rotativo';
        $this->name = 'Ahorro Rotativo';
        $this->description = 'Panderos y círculos de ahorro rotativo entre miembros, con transparencia y reputación.';
        $this->module_role = 'vertical';
        $this->ecosystem_supports_modules = ['socios', 'colectivos', 'comunidades'];
        $this->dashboard_parent_module = 'colectivos';
        $this->dashboard_satellite_priority = 45;
        $this->dashboard_client_contexts = ['economia', 'ahorro', 'comunidad'];
        $this->dashboard_admin_contexts = ['economia', 'gestion', 'admin'];

        // Principios Gailu (el "motor invisible": reciprocidad y economía local).
        $this->gailu_principios = ['economia_local', 'cuidados'];
        $this->gailu_contribuye_a = ['cohesion', 'resiliencia', 'autonomia'];

        parent::__construct();
    }

    public function can_activate() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
        return Flavor_Platform_Helpers::tabla_existe($tabla);
    }

    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Ahorro Rotativo no están creadas. Se crearán automáticamente al activar.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }
        return '';
    }

    public function is_active() {
        return $this->can_activate();
    }

    protected function get_default_settings() {
        return [
            'importe_minimo' => 1,
            'plazas_maximas' => 50,
            'dias_gracia_defecto' => 3,
            'permitir_sorteo' => true,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Inicialización del módulo: tablas, API móvil, frontend web,
     * panel de administración unificado y assets.
     */
    public function init() {
        $this->maybe_create_tables();

        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        $this->cargar_api();
        $this->cargar_frontend_controller();

        // Registra el módulo en el panel de administración unificado.
        $this->registrar_en_panel_unificado();
    }

    /**
     * Crea las tablas si aún no existen.
     */
    public function maybe_create_tables() {
        if (!$this->can_activate()) {
            require_once dirname(__FILE__) . '/install.php';
            flavor_ahorro_rotativo_crear_tablas();
        }
    }

    private function cargar_api() {
        $archivo = dirname(__FILE__) . '/class-ahorro-rotativo-api.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            Flavor_Ahorro_Rotativo_API::get_instance();
        }
    }

    private function cargar_frontend_controller() {
        $archivo = dirname(__FILE__) . '/frontend/class-ahorro-rotativo-frontend-controller.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            Flavor_Ahorro_Rotativo_Frontend_Controller::get_instance();
        }
    }

    /**
     * Encola los assets del frontend solo donde se usa el módulo.
     */
    public function enqueue_frontend_assets() {
        $base = plugin_dir_url(__FILE__);
        wp_register_style('flavor-ahorro-rotativo', $base . 'assets/css/ahorro-rotativo.css', [], '0.1.0');
        wp_register_script('flavor-ahorro-rotativo', $base . 'assets/js/ahorro-rotativo.js', [], '0.1.0', true);
    }

    // ------------------------------------------------------------------
    // Frontend (shortcodes).
    // ------------------------------------------------------------------

    public function register_shortcodes() {
        if (!shortcode_exists('ahorro_rotativo_circulos')) {
            add_shortcode('ahorro_rotativo_circulos', [$this, 'shortcode_circulos']);
        }
        if (!shortcode_exists('ahorro_rotativo_circulo')) {
            add_shortcode('ahorro_rotativo_circulo', [$this, 'shortcode_circulo']);
        }
    }

    /** [ahorro_rotativo_circulos] — listado de mis círculos. */
    public function shortcode_circulos($atributos) {
        wp_enqueue_style('flavor-ahorro-rotativo');
        if (!is_user_logged_in()) {
            return '<p class="flavor-ar-aviso">' . esc_html__('Inicia sesión para ver tus círculos de ahorro.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }
        $circulos = $this->listar_circulos_usuario(get_current_user_id());
        ob_start();
        include dirname(__FILE__) . '/templates/listado.php';
        return ob_get_clean();
    }

    /** [ahorro_rotativo_circulo id="N"] — detalle de un círculo. */
    public function shortcode_circulo($atributos) {
        wp_enqueue_style('flavor-ahorro-rotativo');
        $atributos = shortcode_atts(['id' => ''], $atributos);
        $circulo_id = absint($atributos['id'] ?: (isset($_GET['circulo']) ? $_GET['circulo'] : 0));
        if (!$circulo_id) {
            return '<p class="flavor-ar-aviso">' . esc_html__('No se especificó ningún círculo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }
        $detalle = $this->obtener_detalle_circulo($circulo_id);
        if (!$detalle) {
            return '<p class="flavor-ar-aviso">' . esc_html__('Círculo no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }
        ob_start();
        include dirname(__FILE__) . '/templates/circulo-single.php';
        return ob_get_clean();
    }

    // ------------------------------------------------------------------
    // Lecturas compartidas (frontend web + panel admin).
    // ------------------------------------------------------------------

    /** Círculos donde el usuario es creador o miembro. */
    public function listar_circulos_usuario($usuario_id) {
        global $wpdb;
        $t_circulos = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
        $t_miembros = $wpdb->prefix . 'flavor_ahorro_rotativo_miembros';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT c.* FROM $t_circulos c
             LEFT JOIN $t_miembros m ON m.circulo_id = c.id AND m.usuario_id = %d
             WHERE c.creado_por = %d OR m.id IS NOT NULL
             ORDER BY c.fecha_creacion DESC",
            $usuario_id, $usuario_id
        )) ?: [];
    }

    /** Datos de un círculo + sus miembros (para la vista de detalle). */
    public function obtener_detalle_circulo($circulo_id) {
        global $wpdb;
        $t_circulos = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
        $t_miembros = $wpdb->prefix . 'flavor_ahorro_rotativo_miembros';
        $circulo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t_circulos WHERE id = %d", $circulo_id));
        if (!$circulo) {
            return null;
        }
        $miembros = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $t_miembros WHERE circulo_id = %d ORDER BY posicion_turno IS NULL, posicion_turno",
            $circulo_id
        ));
        return ['circulo' => $circulo, 'miembros' => $miembros ?: []];
    }

    // ------------------------------------------------------------------
    // Panel de administración unificado.
    // ------------------------------------------------------------------

    protected function get_admin_config() {
        return [
            'id' => 'ahorro_rotativo',
            'label' => __('Ahorro Rotativo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-update',
            'capability' => 'read',
            'categoria' => 'economia',
            'paginas' => [
                [
                    'slug' => 'ahorro-rotativo-dashboard',
                    'titulo' => __('Resumen', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'ahorro-rotativo-circulos',
                    'titulo' => __('Círculos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_circulos'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /** Métricas para el dashboard del panel unificado. */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $t_circulos = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
        $t_miembros = $wpdb->prefix . 'flavor_ahorro_rotativo_miembros';
        return [
            'circulos_activos' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $t_circulos WHERE estado = 'activo'"),
            'circulos_totales' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $t_circulos"),
            'participantes' => (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $t_miembros WHERE usuario_id IS NOT NULL"),
        ];
    }

    public function render_admin_dashboard() {
        $estadisticas = $this->get_estadisticas_dashboard();
        include dirname(__FILE__) . '/views/dashboard.php';
    }

    public function render_admin_circulos() {
        global $wpdb;
        $t_circulos = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
        $circulos = $wpdb->get_results("SELECT * FROM $t_circulos ORDER BY fecha_creacion DESC LIMIT 200");
        include dirname(__FILE__) . '/views/circulos.php';
    }

    public function get_tool_definitions() {
        return [
            [
                'name' => 'ahorro_rotativo_crear_circulo',
                'description' => 'Crea un círculo de ahorro rotativo (pandero/tanda) en borrador.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'nombre' => ['type' => 'string', 'description' => 'Nombre del círculo'],
                        'importe_aportacion' => ['type' => 'number', 'description' => 'Aportación por periodo'],
                        'num_plazas' => ['type' => 'integer', 'description' => 'Número de miembros'],
                        'periodo_dias' => ['type' => 'integer', 'description' => '7 semanal, 30 mensual', 'default' => 30],
                        'modo_orden' => ['type' => 'string', 'description' => 'fijo o sorteo', 'default' => 'fijo'],
                    ],
                    'required' => ['nombre', 'importe_aportacion', 'num_plazas'],
                ],
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Acciones del módulo (las puede invocar el chat IA o la API móvil).
    // ------------------------------------------------------------------

    public function get_actions() {
        return [
            'crear_circulo' => [
                'description' => 'Crear un círculo de ahorro en borrador',
                'params' => ['nombre', 'importe_aportacion', 'num_plazas', 'periodo_dias', 'modo_orden'],
            ],
            'activar_circulo' => [
                'description' => 'Activar un círculo: asigna turnos y genera las rondas',
                'params' => ['circulo_id'],
            ],
            'marcar_pagada' => [
                'description' => 'Un miembro marca su aportación como pagada',
                'params' => ['aportacion_id'],
            ],
            'confirmar_recepcion' => [
                'description' => 'El beneficiario de la ronda confirma que recibió la aportación',
                'params' => ['aportacion_id'],
            ],
            'invitar_miembro' => [
                'description' => 'Añadir/invitar un miembro a un círculo en borrador',
                'params' => ['circulo_id', 'nombre_visible', 'usuario_id'],
            ],
            'unirse' => [
                'description' => 'Aceptar una invitación con su código y unirse al círculo',
                'params' => ['codigo_invitacion'],
            ],
        ];
    }

    public function execute_action($nombre_accion, $parametros) {
        $metodo = 'action_' . $nombre_accion;
        if (method_exists($this, $metodo)) {
            return $this->$metodo($parametros);
        }
        return ['success' => false, 'error' => sprintf(__('Acción no implementada: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $nombre_accion)];
    }

    private function action_crear_circulo($parametros) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }
        global $wpdb;
        $nombre = sanitize_text_field($parametros['nombre'] ?? '');
        $importe = floatval($parametros['importe_aportacion'] ?? 0);
        $plazas = absint($parametros['num_plazas'] ?? 0);
        if ($nombre === '' || $importe <= 0 || $plazas < 2) {
            return ['success' => false, 'error' => __('Datos del círculo no válidos.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }
        $tabla = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
        $wpdb->insert($tabla, [
            'nombre' => $nombre,
            'importe_aportacion' => $importe,
            'num_plazas' => $plazas,
            'periodo_dias' => absint($parametros['periodo_dias'] ?? 30),
            'modo_orden' => in_array($parametros['modo_orden'] ?? 'fijo', ['fijo', 'sorteo'], true) ? $parametros['modo_orden'] : 'fijo',
            'estado' => 'borrador',
            'creado_por' => get_current_user_id(),
            'fecha_creacion' => current_time('mysql'),
        ]);
        return ['success' => true, 'circulo_id' => $wpdb->insert_id, 'mensaje' => __('Círculo creado.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    private function action_activar_circulo($parametros) {
        global $wpdb;
        $circulo_id = absint($parametros['circulo_id'] ?? 0);
        $t_circulos = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
        $t_miembros = $wpdb->prefix . 'flavor_ahorro_rotativo_miembros';
        $t_rondas = $wpdb->prefix . 'flavor_ahorro_rotativo_rondas';
        $t_aport = $wpdb->prefix . 'flavor_ahorro_rotativo_aportaciones';

        $circulo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t_circulos WHERE id = %d", $circulo_id));
        if (!$circulo) {
            return ['success' => false, 'error' => __('Círculo no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }
        if ((int) $circulo->creado_por !== get_current_user_id()) {
            return ['success' => false, 'error' => __('Solo quien creó el círculo puede activarlo.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $miembros = $wpdb->get_results($wpdb->prepare("SELECT id FROM $t_miembros WHERE circulo_id = %d", $circulo_id));
        $ids_miembros = wp_list_pluck($miembros, 'id');
        if (count($ids_miembros) < 2) {
            return ['success' => false, 'error' => __('Hacen falta al menos 2 miembros.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        // Asignar posiciones de turno (sorteo verificable con semilla, o por inscripción).
        $posiciones = $this->asignar_posiciones($ids_miembros, $circulo->modo_orden, $circulo->semilla_sorteo);
        foreach ($posiciones as $miembro_id => $posicion) {
            $wpdb->update($t_miembros, ['posicion_turno' => $posicion], ['id' => $miembro_id]);
        }

        // Generar una ronda por miembro, con sus aportaciones pendientes.
        $inicio_base = $circulo->fecha_inicio ? strtotime($circulo->fecha_inicio) : current_time('timestamp');
        $total = count($ids_miembros);
        for ($numero = 1; $numero <= $total; $numero++) {
            $inicio = strtotime("+" . (($numero - 1) * (int) $circulo->periodo_dias) . " days", $inicio_base);
            $fin = strtotime("+" . (int) $circulo->periodo_dias . " days", $inicio);
            $beneficiario_id = array_search($numero, $posiciones, true);

            $wpdb->insert($t_rondas, [
                'circulo_id' => $circulo_id,
                'numero_ronda' => $numero,
                'periodo_inicio' => date('Y-m-d', $inicio),
                'periodo_fin' => date('Y-m-d', $fin),
                'miembro_beneficiario_id' => $beneficiario_id,
            ]);
            $ronda_id = $wpdb->insert_id;

            foreach ($ids_miembros as $miembro_id) {
                $wpdb->insert($t_aport, [
                    'ronda_id' => $ronda_id,
                    'miembro_id' => $miembro_id,
                    'importe' => $circulo->importe_aportacion,
                ]);
            }
        }

        $wpdb->update($t_circulos, ['estado' => 'activo'], ['id' => $circulo_id]);
        return ['success' => true, 'mensaje' => __('Círculo activado.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    private function action_marcar_pagada($parametros) {
        global $wpdb;
        $aportacion_id = absint($parametros['aportacion_id'] ?? 0);
        $t_aport = $wpdb->prefix . 'flavor_ahorro_rotativo_aportaciones';
        $wpdb->update($t_aport, [
            'estado' => 'pagada',
            'fecha_pago' => current_time('mysql'),
        ], ['id' => $aportacion_id]);
        return ['success' => true, 'mensaje' => __('Aportación marcada como pagada.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    private function action_confirmar_recepcion($parametros) {
        global $wpdb;
        $aportacion_id = absint($parametros['aportacion_id'] ?? 0);
        $t_aport = $wpdb->prefix . 'flavor_ahorro_rotativo_aportaciones';
        $t_miembros = $wpdb->prefix . 'flavor_ahorro_rotativo_miembros';
        $miembro_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $t_miembros WHERE usuario_id = %d", get_current_user_id()
        ));
        $wpdb->update($t_aport, [
            'estado' => 'confirmada',
            'confirmada_por' => $miembro_id,
            'fecha_confirmacion' => current_time('mysql'),
        ], ['id' => $aportacion_id]);
        return ['success' => true, 'mensaje' => __('Recepción confirmada.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    private function action_invitar_miembro($parametros) {
        global $wpdb;
        $circulo_id = absint($parametros['circulo_id'] ?? 0);
        $t_circulos = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
        $t_miembros = $wpdb->prefix . 'flavor_ahorro_rotativo_miembros';

        $circulo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t_circulos WHERE id = %d", $circulo_id));
        if (!$circulo) {
            return ['success' => false, 'error' => __('Círculo no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }
        if ((int) $circulo->creado_por !== get_current_user_id()) {
            return ['success' => false, 'error' => __('Solo quien creó el círculo puede invitar.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }
        if ($circulo->estado !== 'borrador') {
            return ['success' => false, 'error' => __('Solo se puede invitar mientras el círculo está en borrador.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }
        $miembros_actuales = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t_miembros WHERE circulo_id = %d", $circulo_id));
        if ($miembros_actuales >= (int) $circulo->num_plazas) {
            return ['success' => false, 'error' => __('El círculo ya tiene todas las plazas cubiertas.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $usuario_id = isset($parametros['usuario_id']) ? absint($parametros['usuario_id']) : null;
        $codigo = wp_generate_password(12, false);
        $wpdb->insert($t_miembros, [
            'circulo_id' => $circulo_id,
            'usuario_id' => $usuario_id ?: null,
            'nombre_visible' => sanitize_text_field($parametros['nombre_visible'] ?? ''),
            'estado_invitacion' => $usuario_id ? 'aceptado' : 'invitado',
            'codigo_invitacion' => $codigo,
            'fecha_alta' => current_time('mysql'),
        ]);
        return ['success' => true, 'miembro_id' => $wpdb->insert_id, 'codigo_invitacion' => $codigo];
    }

    private function action_unirse($parametros) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }
        global $wpdb;
        $codigo = sanitize_text_field($parametros['codigo_invitacion'] ?? '');
        $t_miembros = $wpdb->prefix . 'flavor_ahorro_rotativo_miembros';
        $miembro = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t_miembros WHERE codigo_invitacion = %s", $codigo));
        if (!$miembro) {
            return ['success' => false, 'error' => __('Invitación no válida.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }
        $wpdb->update($t_miembros, [
            'usuario_id' => get_current_user_id(),
            'estado_invitacion' => 'aceptado',
        ], ['id' => $miembro->id]);
        return ['success' => true, 'circulo_id' => (int) $miembro->circulo_id, 'mensaje' => __('Te has unido al círculo.', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    /**
     * Asigna posiciones de turno 1..N. En modo 'sorteo' baraja de forma
     * determinista a partir de la semilla, para que sea verificable.
     *
     * @return array<int,int> [miembro_id => posicion]
     */
    private function asignar_posiciones(array $ids_miembros, $modo, $semilla) {
        $ordenados = array_values($ids_miembros);
        if ($modo === 'sorteo') {
            $estado = crc32($semilla ?: 'sin-semilla');
            for ($i = count($ordenados) - 1; $i > 0; $i--) {
                $estado = ($estado * 1103515245 + 12345) & 0x7fffffff;
                $j = $estado % ($i + 1);
                $tmp = $ordenados[$i];
                $ordenados[$i] = $ordenados[$j];
                $ordenados[$j] = $tmp;
            }
        }
        $posiciones = [];
        foreach ($ordenados as $indice => $miembro_id) {
            $posiciones[$miembro_id] = $indice + 1;
        }
        return $posiciones;
    }

    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Ahorro Rotativo (panderos / tandas / círculos de ahorro)**

Un grupo de personas ahorra junto: cada miembro aporta una cantidad fija cada
periodo y, por turnos, a cada uno le toca llevarse el bote completo.

La plataforma coordina y da transparencia; NO mueve dinero (no es un banco).
La confianza se autorregula: cada miembro acumula reputación de reciprocidad
según cumple sus aportaciones a tiempo.

Funciona así:
- Se crea el círculo (importe, nº de plazas, periodicidad, orden por inscripción o sorteo).
- Se invita a los miembros (idealmente socios del colectivo).
- Al activarlo se asignan los turnos y se generan las rondas.
- Cada ronda: todos aportan, el beneficiario cobra el bote, y confirma lo recibido.
KNOWLEDGE;
    }

    public function get_faqs() {
        return [
            [
                'pregunta' => '¿La app guarda mi dinero?',
                'respuesta' => 'No. Solo registra y coordina las aportaciones; el dinero se mueve entre vosotros como siempre.',
            ],
            [
                'pregunta' => '¿Qué pasa si alguien no paga?',
                'respuesta' => 'Su retraso es visible para todos y su reputación baja. El grupo se autorregula con transparencia.',
            ],
            [
                'pregunta' => '¿Cómo se decide el orden de los turnos?',
                'respuesta' => 'Por orden de inscripción o por sorteo verificable con una semilla pública.',
            ],
        ];
    }
}

// Alias de compatibilidad (igual que el resto de módulos).
if (!class_exists('Flavor_Chat_Ahorro_Rotativo_Module', false)) {
    class_alias('Flavor_Platform_Ahorro_Rotativo_Module', 'Flavor_Chat_Ahorro_Rotativo_Module');
}
