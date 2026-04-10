<?php
/**
 * Módulo de Crowdfunding / Recaudación de Fondos
 *
 * Módulo reutilizable para financiación colectiva que puede ser usado
 * por cualquier otro módulo (artistas, eventos, colectivos, etc.)
 *
 * @package FlavorPlatform
 * @subpackage Modules\Crowdfunding
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del módulo de Crowdfunding
 */
class Flavor_Platform_Crowdfunding_Module extends Flavor_Platform_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'crowdfunding';
        $this->name = __('Crowdfunding', 'flavor-platform');
        $this->description = __('Financiación colectiva para proyectos culturales, sociales y comunitarios. Integrable con artistas, eventos, colectivos y más.', 'flavor-platform');
        $this->module_role = 'base';
        $this->ecosystem_supports_modules = ['eventos', 'colectivos', 'comunidades', 'campanias', 'socios'];
        $this->dashboard_parent_module = 'crowdfunding';
        $this->dashboard_satellite_priority = 20;
        $this->dashboard_client_contexts = ['crowdfunding', 'proyectos', 'financiacion', 'mecenazgo'];
        $this->dashboard_admin_contexts = ['crowdfunding', 'recaudacion', 'admin'];

        $this->gailu_principios = ['economia_solidaria', 'cooperacion'];
        $this->gailu_contribuye_a = ['redistribucion', 'cultura'];

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        return Flavor_Platform_Helpers::tabla_existe($wpdb->prefix . 'flavor_crowdfunding_proyectos');
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Crowdfunding no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-platform');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            // Comisiones
            'comision_plataforma' => 5.0,
            'comision_pasarela' => 2.5,

            // Distribución por defecto (estilo Kulturaka)
            'distribucion_default' => [
                'creador' => 70,
                'espacio' => 10,
                'comunidad' => 10,
                'plataforma' => 5,
                'emergencia' => 5,
            ],

            // Límites
            'minimo_objetivo' => 100,
            'maximo_objetivo' => 100000,
            'minimo_aportacion' => 1,
            'maximo_duracion_dias' => 90,

            // Monedas aceptadas
            'acepta_eur' => true,
            'acepta_semilla' => true,
            'acepta_hours' => true,
            'tasa_semilla_eur' => 0.10,
            'tasa_hours_eur' => 10.00,

            // Verificación
            'requiere_verificacion' => false,
            'auto_aprobar' => true,

            // Notificaciones
            'notificar_hitos' => [25, 50, 75, 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->maybe_create_tables();

        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Panel Unificado y Dashboard
        $this->inicializar_dashboard_tab();
        $this->registrar_en_panel_unificado();

        // Cargar frontend controller
        $this->cargar_frontend_controller();

        // Cargar API
        $this->cargar_api();

        // Admin
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // Hooks para integración con otros módulos
        $this->registrar_hooks_integracion();

        // Cron para verificar proyectos finalizados
        add_action('flavor_crowdfunding_check_finalizados', [$this, 'procesar_proyectos_finalizados']);
        if (!wp_next_scheduled('flavor_crowdfunding_check_finalizados')) {
            wp_schedule_event(time(), 'hourly', 'flavor_crowdfunding_check_finalizados');
        }
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        if (!$this->can_activate()) {
            require_once dirname(__FILE__) . '/install.php';
            flavor_crowdfunding_crear_tablas();
        }
    }

    /**
     * Carga el controlador frontend
     */
    private function cargar_frontend_controller() {
        $archivo = dirname(__FILE__) . '/frontend/class-crowdfunding-frontend-controller.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            Flavor_Crowdfunding_Frontend_Controller::get_instance();
        }
    }

    /**
     * Carga la API
     */
    private function cargar_api() {
        $archivo = dirname(__FILE__) . '/class-crowdfunding-api.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            Flavor_Crowdfunding_API::get_instance();
        }
    }

    /**
     * Registra hooks para integración con otros módulos
     */
    private function registrar_hooks_integracion() {
        // Permitir a otros módulos crear proyectos de crowdfunding
        add_action('flavor_crowdfunding_crear_para_entidad', [$this, 'crear_proyecto_para_entidad'], 10, 3);

        // Filtro para obtener proyectos de una entidad
        add_filter('flavor_crowdfunding_proyectos_entidad', [$this, 'filtrar_proyectos_entidad'], 10, 3);
    }

    // =========================================================
    // CRUD de Proyectos
    // =========================================================

    /**
     * Crea un nuevo proyecto de crowdfunding
     *
     * @param array $datos
     * @return int|WP_Error
     */
    public function crear_proyecto($datos) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_proyectos';

        $creador_id = $datos['creador_id'] ?? get_current_user_id();

        if (!$creador_id) {
            return new WP_Error('sin_usuario', __('Debes iniciar sesión para crear un proyecto.', 'flavor-platform'));
        }

        $titulo = sanitize_text_field($datos['titulo'] ?? '');
        if (empty($titulo)) {
            return new WP_Error('sin_titulo', __('El título es obligatorio.', 'flavor-platform'));
        }

        $objetivo = floatval($datos['objetivo_eur'] ?? 0);
        $minimo = $this->get_setting('minimo_objetivo', 100);
        $maximo = $this->get_setting('maximo_objetivo', 100000);

        if ($objetivo < $minimo || $objetivo > $maximo) {
            return new WP_Error('objetivo_invalido', sprintf(
                __('El objetivo debe estar entre %s€ y %s€.', 'flavor-platform'),
                number_format_i18n($minimo),
                number_format_i18n($maximo)
            ));
        }

        $slug = $this->generar_slug_unico($titulo);
        $distribucion = $this->get_setting('distribucion_default', []);

        $datos_insertar = [
            'titulo' => $titulo,
            'slug' => $slug,
            'descripcion' => sanitize_textarea_field($datos['descripcion'] ?? ''),
            'contenido' => wp_kses_post($datos['contenido'] ?? ''),
            'extracto' => sanitize_text_field(wp_trim_words($datos['descripcion'] ?? '', 30)),
            'creador_id' => $creador_id,
            'colectivo_id' => absint($datos['colectivo_id'] ?? 0) ?: null,
            'comunidad_id' => absint($datos['comunidad_id'] ?? 0) ?: null,
            'modulo_origen' => sanitize_key($datos['modulo_origen'] ?? ''),
            'entidad_tipo' => sanitize_key($datos['entidad_tipo'] ?? ''),
            'entidad_id' => absint($datos['entidad_id'] ?? 0) ?: null,
            'tipo' => sanitize_key($datos['tipo'] ?? 'otro'),
            'categoria' => sanitize_text_field($datos['categoria'] ?? ''),
            'objetivo_eur' => $objetivo,
            'objetivo_semilla' => floatval($datos['objetivo_semilla'] ?? 0),
            'objetivo_hours' => floatval($datos['objetivo_hours'] ?? 0),
            'moneda_principal' => in_array($datos['moneda_principal'] ?? '', ['eur', 'semilla', 'hours', 'mixta']) ? $datos['moneda_principal'] : 'eur',
            'acepta_eur' => isset($datos['acepta_eur']) ? (int) $datos['acepta_eur'] : 1,
            'acepta_semilla' => isset($datos['acepta_semilla']) ? (int) $datos['acepta_semilla'] : 1,
            'acepta_hours' => isset($datos['acepta_hours']) ? (int) $datos['acepta_hours'] : 1,
            'minimo_aportacion' => floatval($datos['minimo_aportacion'] ?? $this->get_setting('minimo_aportacion', 1)),
            'permite_aportacion_libre' => isset($datos['permite_aportacion_libre']) ? (int) $datos['permite_aportacion_libre'] : 1,
            'modalidad' => in_array($datos['modalidad'] ?? '', ['todo_o_nada', 'flexible', 'donacion']) ? $datos['modalidad'] : 'flexible',
            'fecha_inicio' => current_time('mysql'),
            'dias_duracion' => min(absint($datos['dias_duracion'] ?? 30), $this->get_setting('maximo_duracion_dias', 90)),
            'porcentaje_creador' => floatval($distribucion['creador'] ?? 70),
            'porcentaje_espacio' => floatval($distribucion['espacio'] ?? 10),
            'porcentaje_comunidad' => floatval($distribucion['comunidad'] ?? 10),
            'porcentaje_plataforma' => floatval($distribucion['plataforma'] ?? 5),
            'porcentaje_emergencia' => floatval($distribucion['emergencia'] ?? 5),
            'imagen_principal' => esc_url_raw($datos['imagen_principal'] ?? ''),
            'video_principal' => esc_url_raw($datos['video_principal'] ?? ''),
            'estado' => $this->get_setting('auto_aprobar', true) ? 'activo' : 'revision',
            'visibilidad' => in_array($datos['visibilidad'] ?? '', ['publico', 'comunidad', 'privado']) ? $datos['visibilidad'] : 'publico',
        ];

        // Calcular fecha fin
        $datos_insertar['fecha_fin'] = date('Y-m-d H:i:s', strtotime("+{$datos_insertar['dias_duracion']} days"));

        $insertado = $wpdb->insert($tabla, $datos_insertar);

        if (!$insertado) {
            return new WP_Error('error_crear', __('Error al crear el proyecto.', 'flavor-platform'));
        }

        $proyecto_id = $wpdb->insert_id;

        // Crear tiers si se proporcionaron
        if (!empty($datos['tiers']) && is_array($datos['tiers'])) {
            foreach ($datos['tiers'] as $tier_data) {
                $this->crear_tier($proyecto_id, $tier_data);
            }
        }

        /**
         * Acción después de crear un proyecto
         *
         * @param int $proyecto_id ID del proyecto
         * @param array $datos Datos del proyecto
         */
        do_action('flavor_crowdfunding_proyecto_creado', $proyecto_id, $datos);

        return $proyecto_id;
    }

    /**
     * Obtiene un proyecto por ID o slug
     *
     * @param int|string $identificador
     * @return object|null
     */
    public function get_proyecto($identificador) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_proyectos';

        if (is_numeric($identificador)) {
            $proyecto = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla WHERE id = %d",
                $identificador
            ));
        } else {
            $proyecto = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla WHERE slug = %s",
                $identificador
            ));
        }

        if ($proyecto) {
            $proyecto = $this->enriquecer_proyecto($proyecto);
        }

        return $proyecto;
    }

    /**
     * Enriquece un proyecto con datos adicionales
     */
    private function enriquecer_proyecto($proyecto) {
        global $wpdb;

        // Calcular porcentaje recaudado
        $objetivo_total = $proyecto->objetivo_eur + ($proyecto->objetivo_semilla * $this->get_setting('tasa_semilla_eur', 0.1));
        $recaudado_total = $proyecto->recaudado_eur + ($proyecto->recaudado_semilla * $this->get_setting('tasa_semilla_eur', 0.1));

        $proyecto->porcentaje_recaudado = $objetivo_total > 0 ? round(($recaudado_total / $objetivo_total) * 100, 1) : 0;
        $proyecto->recaudado_total_eur = $recaudado_total;
        $proyecto->objetivo_total_eur = $objetivo_total;

        // Días restantes
        if ($proyecto->fecha_fin) {
            $fecha_fin = new DateTime($proyecto->fecha_fin);
            $ahora = new DateTime();
            $diferencia = $ahora->diff($fecha_fin);
            $proyecto->dias_restantes = $diferencia->invert ? 0 : $diferencia->days;
            $proyecto->finalizado = $diferencia->invert;
        } else {
            $proyecto->dias_restantes = null;
            $proyecto->finalizado = false;
        }

        // Datos del creador
        $proyecto->creador = get_userdata($proyecto->creador_id);
        if ($proyecto->creador) {
            $proyecto->creador_nombre = $proyecto->creador->display_name;
            $proyecto->creador_avatar = get_avatar_url($proyecto->creador_id, ['size' => 80]);
        }

        // Tiers
        $proyecto->tiers = $this->get_tiers($proyecto->id);

        // Decodificar JSON
        $proyecto->desglose_presupuesto = json_decode($proyecto->desglose_presupuesto, true) ?: [];
        $proyecto->galeria = json_decode($proyecto->galeria, true) ?: [];
        $proyecto->metadata = json_decode($proyecto->metadata, true) ?: [];

        // Labels
        $proyecto->estado_label = $this->get_estado_label($proyecto->estado);
        $proyecto->tipo_label = $this->get_tipo_label($proyecto->tipo);
        $proyecto->modalidad_label = $this->get_modalidad_label($proyecto->modalidad);

        return $proyecto;
    }

    /**
     * Lista proyectos con filtros
     */
    public function listar_proyectos($argumentos = []) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_proyectos';

        $defaults = [
            'limite' => 12,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'estado' => 'activo',
            'tipo' => '',
            'categoria' => '',
            'creador_id' => '',
            'comunidad_id' => '',
            'modulo_origen' => '',
            'entidad_id' => '',
            'destacados' => false,
            'busqueda' => '',
        ];

        $argumentos = wp_parse_args($argumentos, $defaults);

        $where = ['1=1'];
        $valores = [];

        if (!empty($argumentos['estado'])) {
            if (is_array($argumentos['estado'])) {
                $placeholders = implode(',', array_fill(0, count($argumentos['estado']), '%s'));
                $where[] = "estado IN ($placeholders)";
                $valores = array_merge($valores, $argumentos['estado']);
            } else {
                $where[] = 'estado = %s';
                $valores[] = $argumentos['estado'];
            }
        }

        if (!empty($argumentos['tipo'])) {
            $where[] = 'tipo = %s';
            $valores[] = $argumentos['tipo'];
        }

        if (!empty($argumentos['categoria'])) {
            $where[] = 'categoria = %s';
            $valores[] = $argumentos['categoria'];
        }

        if (!empty($argumentos['creador_id'])) {
            $where[] = 'creador_id = %d';
            $valores[] = $argumentos['creador_id'];
        }

        if (!empty($argumentos['comunidad_id'])) {
            $where[] = 'comunidad_id = %d';
            $valores[] = $argumentos['comunidad_id'];
        }

        if (!empty($argumentos['modulo_origen'])) {
            $where[] = 'modulo_origen = %s';
            $valores[] = $argumentos['modulo_origen'];
        }

        if (!empty($argumentos['entidad_id'])) {
            $where[] = 'entidad_id = %d';
            $valores[] = $argumentos['entidad_id'];
        }

        if ($argumentos['destacados']) {
            $where[] = 'destacado = 1';
        }

        if (!empty($argumentos['busqueda'])) {
            $where[] = '(titulo LIKE %s OR descripcion LIKE %s)';
            $busqueda = '%' . $wpdb->esc_like($argumentos['busqueda']) . '%';
            $valores[] = $busqueda;
            $valores[] = $busqueda;
        }

        $where_sql = implode(' AND ', $where);

        // Orderby válidos
        $columnas_validas = ['created_at', 'recaudado_eur', 'aportantes_count', 'fecha_fin', 'porcentaje'];
        $orderby = in_array($argumentos['orderby'], $columnas_validas) ? $argumentos['orderby'] : 'created_at';

        // Ordenar por porcentaje es especial
        if ($orderby === 'porcentaje') {
            $orderby = '(recaudado_eur / NULLIF(objetivo_eur, 0))';
        }

        $order = strtoupper($argumentos['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Contar total
        $sql_count = "SELECT COUNT(*) FROM $tabla WHERE $where_sql";
        if (!empty($valores)) {
            $sql_count = $wpdb->prepare($sql_count, $valores);
        }
        $total = (int) $wpdb->get_var($sql_count);

        // Obtener proyectos
        $sql = "SELECT * FROM $tabla WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $valores[] = $argumentos['limite'];
        $valores[] = $argumentos['offset'];

        $proyectos = $wpdb->get_results($wpdb->prepare($sql, $valores));

        foreach ($proyectos as &$proyecto) {
            $proyecto = $this->enriquecer_proyecto($proyecto);
        }

        return [
            'proyectos' => $proyectos,
            'total' => $total,
            'paginas' => ceil($total / $argumentos['limite']),
        ];
    }

    // =========================================================
    // Tiers
    // =========================================================

    /**
     * Crea un tier de recompensa
     */
    public function crear_tier($proyecto_id, $datos) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_tiers';

        $datos_insertar = [
            'proyecto_id' => $proyecto_id,
            'nombre' => sanitize_text_field($datos['nombre'] ?? ''),
            'descripcion' => sanitize_textarea_field($datos['descripcion'] ?? ''),
            'importe_eur' => floatval($datos['importe_eur'] ?? 0) ?: null,
            'importe_semilla' => floatval($datos['importe_semilla'] ?? 0) ?: null,
            'importe_hours' => floatval($datos['importe_hours'] ?? 0) ?: null,
            'recompensas' => wp_json_encode($datos['recompensas'] ?? []),
            'cantidad_limitada' => absint($datos['cantidad_limitada'] ?? 0) ?: null,
            'fecha_entrega_estimada' => !empty($datos['fecha_entrega']) ? sanitize_text_field($datos['fecha_entrega']) : null,
            'requiere_envio' => !empty($datos['requiere_envio']) ? 1 : 0,
            'imagen' => esc_url_raw($datos['imagen'] ?? ''),
            'orden' => absint($datos['orden'] ?? 0),
            'activo' => 1,
        ];

        return $wpdb->insert($tabla, $datos_insertar) ? $wpdb->insert_id : false;
    }

    /**
     * Obtiene los tiers de un proyecto
     */
    public function get_tiers($proyecto_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_tiers';

        $tiers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE proyecto_id = %d AND activo = 1 ORDER BY COALESCE(importe_eur, 0) ASC, orden ASC",
            $proyecto_id
        ));

        foreach ($tiers as &$tier) {
            $tier->recompensas = json_decode($tier->recompensas, true) ?: [];
            $tier->disponible = is_null($tier->cantidad_limitada) || $tier->cantidad_vendida < $tier->cantidad_limitada;
            $tier->restantes = is_null($tier->cantidad_limitada) ? null : $tier->cantidad_limitada - $tier->cantidad_vendida;
        }

        return $tiers;
    }

    // =========================================================
    // Aportaciones
    // =========================================================

    /**
     * Registra una aportación
     */
    public function registrar_aportacion($datos) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_aportaciones';
        $tabla_proyectos = $wpdb->prefix . 'flavor_crowdfunding_proyectos';
        $tabla_tiers = $wpdb->prefix . 'flavor_crowdfunding_tiers';

        $proyecto_id = absint($datos['proyecto_id'] ?? 0);
        $proyecto = $this->get_proyecto($proyecto_id);

        if (!$proyecto) {
            return new WP_Error('proyecto_no_existe', __('El proyecto no existe.', 'flavor-platform'));
        }

        if ($proyecto->estado !== 'activo') {
            return new WP_Error('proyecto_inactivo', __('Este proyecto no está aceptando aportaciones.', 'flavor-platform'));
        }

        $importe = floatval($datos['importe'] ?? 0);
        $moneda = in_array($datos['moneda'] ?? '', ['eur', 'semilla', 'hours']) ? $datos['moneda'] : 'eur';

        if ($importe < $proyecto->minimo_aportacion) {
            return new WP_Error('importe_minimo', sprintf(
                __('La aportación mínima es %s.', 'flavor-platform'),
                number_format_i18n($proyecto->minimo_aportacion, 2) . ($moneda === 'eur' ? '€' : ' ' . strtoupper($moneda))
            ));
        }

        // Calcular equivalente en EUR
        $importe_eur = $importe;
        if ($moneda === 'semilla') {
            $importe_eur = $importe * $this->get_setting('tasa_semilla_eur', 0.1);
        } elseif ($moneda === 'hours') {
            $importe_eur = $importe * $this->get_setting('tasa_hours_eur', 10);
        }

        $tier_id = absint($datos['tier_id'] ?? 0) ?: null;

        // Verificar disponibilidad del tier
        if ($tier_id) {
            $tier = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_tiers WHERE id = %d AND proyecto_id = %d",
                $tier_id,
                $proyecto_id
            ));

            if (!$tier) {
                return new WP_Error('tier_invalido', __('El nivel de recompensa no es válido.', 'flavor-platform'));
            }

            if ($tier->cantidad_limitada && $tier->cantidad_vendida >= $tier->cantidad_limitada) {
                return new WP_Error('tier_agotado', __('Este nivel de recompensa está agotado.', 'flavor-platform'));
            }
        }

        // Calcular comisiones
        $comision_plataforma = $importe_eur * ($this->get_setting('comision_plataforma', 5) / 100);
        $comision_pasarela = $importe_eur * ($this->get_setting('comision_pasarela', 2.5) / 100);
        $importe_neto = $importe_eur - $comision_plataforma - $comision_pasarela;

        $usuario_id = get_current_user_id() ?: null;

        $datos_insertar = [
            'proyecto_id' => $proyecto_id,
            'tier_id' => $tier_id,
            'usuario_id' => $usuario_id,
            'nombre' => sanitize_text_field($datos['nombre'] ?? ''),
            'email' => sanitize_email($datos['email'] ?? ''),
            'telefono' => sanitize_text_field($datos['telefono'] ?? ''),
            'importe' => $importe,
            'moneda' => $moneda,
            'importe_eur_equivalente' => $importe_eur,
            'estado' => 'pendiente',
            'metodo_pago' => sanitize_text_field($datos['metodo_pago'] ?? ''),
            'anonimo' => !empty($datos['anonimo']) ? 1 : 0,
            'mensaje_publico' => sanitize_textarea_field($datos['mensaje_publico'] ?? ''),
            'mensaje_privado' => sanitize_textarea_field($datos['mensaje_privado'] ?? ''),
            'comision_plataforma' => $comision_plataforma,
            'comision_pasarela' => $comision_pasarela,
            'importe_neto' => $importe_neto,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        $insertado = $wpdb->insert($tabla, $datos_insertar);

        if (!$insertado) {
            return new WP_Error('error_aportacion', __('Error al registrar la aportación.', 'flavor-platform'));
        }

        $aportacion_id = $wpdb->insert_id;

        /**
         * Acción después de registrar una aportación (antes del pago)
         */
        do_action('flavor_crowdfunding_aportacion_registrada', $aportacion_id, $proyecto_id, $datos);

        return $aportacion_id;
    }

    /**
     * Confirma una aportación (después del pago exitoso)
     */
    public function confirmar_aportacion($aportacion_id, $datos_pago = []) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_aportaciones';
        $tabla_proyectos = $wpdb->prefix . 'flavor_crowdfunding_proyectos';
        $tabla_tiers = $wpdb->prefix . 'flavor_crowdfunding_tiers';

        $aportacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $aportacion_id
        ));

        if (!$aportacion) {
            return new WP_Error('aportacion_no_existe', __('La aportación no existe.', 'flavor-platform'));
        }

        if ($aportacion->estado === 'completada') {
            return true; // Ya confirmada
        }

        // Actualizar aportación
        $wpdb->update($tabla, [
            'estado' => 'completada',
            'fecha_pago' => current_time('mysql'),
            'referencia_pago' => sanitize_text_field($datos_pago['referencia'] ?? ''),
            'transaccion_id' => sanitize_text_field($datos_pago['transaccion_id'] ?? ''),
        ], ['id' => $aportacion_id]);

        // Actualizar contadores del proyecto
        $campo_recaudado = 'recaudado_' . $aportacion->moneda;
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_proyectos SET
                $campo_recaudado = $campo_recaudado + %f,
                aportantes_count = aportantes_count + 1
            WHERE id = %d",
            $aportacion->importe,
            $aportacion->proyecto_id
        ));

        // Actualizar tier si aplica
        if ($aportacion->tier_id) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_tiers SET cantidad_vendida = cantidad_vendida + 1 WHERE id = %d",
                $aportacion->tier_id
            ));
        }

        // Verificar hitos
        $this->verificar_hitos($aportacion->proyecto_id);

        /**
         * Acción después de confirmar una aportación
         */
        do_action('flavor_crowdfunding_aportacion_completada', $aportacion_id, $aportacion->proyecto_id);

        return true;
    }

    /**
     * Verifica si se alcanzaron hitos de recaudación
     */
    private function verificar_hitos($proyecto_id) {
        $proyecto = $this->get_proyecto($proyecto_id);
        if (!$proyecto) return;

        $hitos = $this->get_setting('notificar_hitos', [25, 50, 75, 100]);
        $porcentaje = $proyecto->porcentaje_recaudado;

        foreach ($hitos as $hito) {
            $meta_key = "_crowdfunding_hito_{$hito}_notificado";
            $ya_notificado = get_post_meta($proyecto_id, $meta_key, true);

            if ($porcentaje >= $hito && !$ya_notificado) {
                do_action('flavor_crowdfunding_hito_alcanzado', $proyecto_id, $hito, $porcentaje);
                update_post_meta($proyecto_id, $meta_key, current_time('mysql'));
            }
        }
    }

    // =========================================================
    // Integración con otros módulos
    // =========================================================

    /**
     * Crea un proyecto para una entidad de otro módulo
     */
    public function crear_proyecto_para_entidad($modulo, $entidad_tipo, $entidad_id, $datos = []) {
        $datos['modulo_origen'] = $modulo;
        $datos['entidad_tipo'] = $entidad_tipo;
        $datos['entidad_id'] = $entidad_id;

        return $this->crear_proyecto($datos);
    }

    /**
     * Obtiene proyectos de una entidad
     */
    public function filtrar_proyectos_entidad($proyectos, $modulo, $entidad_id) {
        return $this->listar_proyectos([
            'modulo_origen' => $modulo,
            'entidad_id' => $entidad_id,
            'estado' => ['activo', 'exitoso'],
        ]);
    }

    // =========================================================
    // Helpers
    // =========================================================

    /**
     * Genera un slug único
     */
    private function generar_slug_unico($titulo, $excluir_id = 0) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_proyectos';

        $slug_base = sanitize_title($titulo);
        $slug = $slug_base;
        $contador = 1;

        while (true) {
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla WHERE slug = %s AND id != %d",
                $slug,
                $excluir_id
            ));

            if (!$existe) break;

            $slug = $slug_base . '-' . $contador;
            $contador++;
        }

        return $slug;
    }

    private function get_estado_label($estado) {
        $estados = [
            'borrador' => __('Borrador', 'flavor-platform'),
            'revision' => __('En revisión', 'flavor-platform'),
            'activo' => __('Activo', 'flavor-platform'),
            'pausado' => __('Pausado', 'flavor-platform'),
            'exitoso' => __('Financiado', 'flavor-platform'),
            'fallido' => __('No financiado', 'flavor-platform'),
            'cancelado' => __('Cancelado', 'flavor-platform'),
        ];
        return $estados[$estado] ?? $estado;
    }

    private function get_tipo_label($tipo) {
        $tipos = [
            'album' => __('Álbum/Grabación', 'flavor-platform'),
            'tour' => __('Gira/Tour', 'flavor-platform'),
            'produccion' => __('Producción', 'flavor-platform'),
            'equipamiento' => __('Equipamiento', 'flavor-platform'),
            'espacio' => __('Espacio', 'flavor-platform'),
            'evento' => __('Evento', 'flavor-platform'),
            'social' => __('Proyecto Social', 'flavor-platform'),
            'emergencia' => __('Emergencia', 'flavor-platform'),
            'otro' => __('Otro', 'flavor-platform'),
        ];
        return $tipos[$tipo] ?? $tipo;
    }

    private function get_modalidad_label($modalidad) {
        $modalidades = [
            'todo_o_nada' => __('Todo o nada', 'flavor-platform'),
            'flexible' => __('Flexible', 'flavor-platform'),
            'donacion' => __('Donación', 'flavor-platform'),
        ];
        return $modalidades[$modalidad] ?? $modalidad;
    }

    /**
     * Procesa proyectos finalizados (cron)
     */
    public function procesar_proyectos_finalizados() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_crowdfunding_proyectos';

        // Proyectos activos que han pasado su fecha fin
        $proyectos = $wpdb->get_results("
            SELECT id, objetivo_eur, recaudado_eur, modalidad
            FROM $tabla
            WHERE estado = 'activo'
            AND fecha_fin IS NOT NULL
            AND fecha_fin < NOW()
        ");

        foreach ($proyectos as $proyecto) {
            $nuevo_estado = 'fallido';

            // Si alcanzó el objetivo, es exitoso
            if ($proyecto->recaudado_eur >= $proyecto->objetivo_eur) {
                $nuevo_estado = 'exitoso';
            }
            // Si es flexible y recaudó algo, también es exitoso
            elseif ($proyecto->modalidad === 'flexible' && $proyecto->recaudado_eur > 0) {
                $nuevo_estado = 'exitoso';
            }
            // Si es donación, siempre es exitoso
            elseif ($proyecto->modalidad === 'donacion') {
                $nuevo_estado = 'exitoso';
            }

            $wpdb->update($tabla, ['estado' => $nuevo_estado], ['id' => $proyecto->id]);

            do_action('flavor_crowdfunding_proyecto_finalizado', $proyecto->id, $nuevo_estado);
        }
    }

    // =========================================================
    // Shortcodes
    // =========================================================

    /**
     * Registra shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('crowdfunding_listado', [$this, 'shortcode_listado']);
        add_shortcode('crowdfunding_proyecto', [$this, 'shortcode_proyecto']);
        add_shortcode('crowdfunding_destacados', [$this, 'shortcode_destacados']);
        add_shortcode('crowdfunding_crear', [$this, 'shortcode_crear']);
        add_shortcode('crowdfunding_mis_proyectos', [$this, 'shortcode_mis_proyectos']);
    }

    /**
     * Shortcode: Listado de proyectos
     */
    public function shortcode_listado($atributos) {
        $atributos = shortcode_atts([
            'limite' => 12,
            'tipo' => '',
            'categoria' => '',
            'columnas' => 3,
        ], $atributos);

        $resultado = $this->listar_proyectos([
            'limite' => absint($atributos['limite']),
            'tipo' => $atributos['tipo'],
            'categoria' => $atributos['categoria'],
            'estado' => 'activo',
        ]);

        $proyectos = $resultado['proyectos'];
        $columnas = absint($atributos['columnas']);

        ob_start();
        include dirname(__FILE__) . '/templates/listado.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Proyecto individual
     */
    public function shortcode_proyecto($atributos) {
        $atributos = shortcode_atts([
            'id' => '',
            'slug' => '',
        ], $atributos);

        $identificador = $atributos['id'] ?: $atributos['slug'];
        if (empty($identificador)) {
            $identificador = get_query_var('proyecto') ?: ($_GET['proyecto'] ?? '');
        }

        if (empty($identificador)) {
            return '<p class="flavor-error">' . __('No se especificó ningún proyecto.', 'flavor-platform') . '</p>';
        }

        $proyecto = $this->get_proyecto($identificador);

        if (!$proyecto) {
            return '<p class="flavor-error">' . __('Proyecto no encontrado.', 'flavor-platform') . '</p>';
        }

        // Incrementar visualizaciones
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}flavor_crowdfunding_proyectos SET visualizaciones = visualizaciones + 1 WHERE id = %d",
            $proyecto->id
        ));

        $es_creador = is_user_logged_in() && get_current_user_id() == $proyecto->creador_id;

        ob_start();
        include dirname(__FILE__) . '/templates/proyecto-single.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Proyectos destacados
     */
    public function shortcode_destacados($atributos) {
        $atributos = shortcode_atts([
            'limite' => 4,
        ], $atributos);

        $resultado = $this->listar_proyectos([
            'limite' => absint($atributos['limite']),
            'destacados' => true,
            'estado' => 'activo',
        ]);

        $proyectos = $resultado['proyectos'];

        ob_start();
        include dirname(__FILE__) . '/templates/destacados.php';
        return ob_get_clean();
    }
}

if (!class_exists('Flavor_Chat_Crowdfunding_Module', false)) {
    class_alias('Flavor_Platform_Crowdfunding_Module', 'Flavor_Chat_Crowdfunding_Module');
}
