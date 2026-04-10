<?php
/**
 * Funcionalidades de Sello de Conciencia para módulo Reciclaje
 * +13 puntos: Economía circular, huella de carbono, retos comunitarios,
 * red de reparadores y dashboard de impacto
 *
 * @package FlavorPlatform
 * @subpackage Reciclaje
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal de funcionalidades de conciencia para Reciclaje
 */
class Flavor_Reciclaje_Conciencia_Features {

    /** @var self|null */
    private static $instance = null;

    /** @var string */
    private $version = '1.0.0';

    /**
     * Factores de CO2 por material (kg CO2 ahorrado por kg reciclado)
     */
    private $factores_co2 = [
        'papel' => 0.7,
        'plastico' => 1.5,
        'vidrio' => 0.3,
        'organico' => 0.2,
        'electronico' => 2.0,
        'ropa' => 3.0,
        'aceite' => 2.5,
        'pilas' => 5.0,
        'metal' => 4.0,
    ];

    /**
     * Singleton
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
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);

        // AJAX handlers
        add_action('wp_ajax_rec_registrar_reutilizacion', [$this, 'ajax_registrar_reutilizacion']);
        add_action('wp_ajax_rec_unirse_reto', [$this, 'ajax_unirse_reto']);
        add_action('wp_ajax_rec_registrar_reparacion', [$this, 'ajax_registrar_reparacion']);
        add_action('wp_ajax_rec_ofrecer_material', [$this, 'ajax_ofrecer_material']);
        add_action('wp_ajax_rec_solicitar_material', [$this, 'ajax_solicitar_material']);

        // Hooks de reciclaje
        add_action('reciclaje_deposito_registrado', [$this, 'on_deposito_registrado'], 10, 3);
    }

    /**
     * Crear tablas adicionales
     */
    public function maybe_create_tables() {
        $installed_version = get_option('flavor_rec_conciencia_db_version', '0');
        if (version_compare($installed_version, $this->version, '>=')) {
            return;
        }

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Tabla de reutilizaciones (economía circular)
        $tabla_reutilizaciones = $wpdb->prefix . 'flavor_rec_reutilizaciones';
        $sql_reutilizaciones = "CREATE TABLE $tabla_reutilizaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            material_tipo varchar(50) NOT NULL,
            descripcion text DEFAULT NULL,
            cantidad decimal(8,2) DEFAULT 1,
            unidad varchar(20) DEFAULT 'unidad',
            estado enum('disponible','reservado','entregado') DEFAULT 'disponible',
            receptor_id bigint(20) unsigned DEFAULT NULL,
            ubicacion varchar(255) DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            co2_ahorrado decimal(8,2) DEFAULT 0,
            fecha_creacion datetime DEFAULT NULL,
            fecha_entrega datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY material_tipo (material_tipo)
        ) $charset_collate;";
        dbDelta($sql_reutilizaciones);

        // Tabla de huella de carbono personal
        $tabla_huella = $wpdb->prefix . 'flavor_rec_huella_carbono';
        $sql_huella = "CREATE TABLE $tabla_huella (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            periodo varchar(7) NOT NULL,
            co2_reciclaje decimal(10,2) DEFAULT 0,
            co2_reutilizacion decimal(10,2) DEFAULT 0,
            co2_reparacion decimal(10,2) DEFAULT 0,
            co2_total_ahorrado decimal(10,2) DEFAULT 0,
            kg_reciclados decimal(10,2) DEFAULT 0,
            items_reutilizados int(11) DEFAULT 0,
            items_reparados int(11) DEFAULT 0,
            fecha_actualizacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_periodo (usuario_id, periodo)
        ) $charset_collate;";
        dbDelta($sql_huella);

        // Tabla de retos comunitarios
        $tabla_retos = $wpdb->prefix . 'flavor_rec_retos';
        $sql_retos = "CREATE TABLE $tabla_retos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('reciclaje','reutilizacion','reparacion','reduccion') NOT NULL,
            material_objetivo varchar(50) DEFAULT NULL,
            meta_cantidad decimal(10,2) NOT NULL,
            unidad varchar(20) DEFAULT 'kg',
            progreso_actual decimal(10,2) DEFAULT 0,
            fecha_inicio date NOT NULL,
            fecha_fin date NOT NULL,
            estado enum('activo','completado','expirado') DEFAULT 'activo',
            puntos_recompensa int(11) DEFAULT 50,
            participantes int(11) DEFAULT 0,
            creado_por bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY fecha_fin (fecha_fin)
        ) $charset_collate;";
        dbDelta($sql_retos);

        // Tabla de participaciones en retos
        $tabla_participaciones = $wpdb->prefix . 'flavor_rec_reto_participaciones';
        $sql_participaciones = "CREATE TABLE $tabla_participaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reto_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            contribucion decimal(10,2) DEFAULT 0,
            puntos_ganados int(11) DEFAULT 0,
            fecha_inscripcion datetime DEFAULT NULL,
            fecha_ultima_contribucion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY reto_usuario (reto_id, usuario_id)
        ) $charset_collate;";
        dbDelta($sql_participaciones);

        // Tabla de reparadores
        $tabla_reparadores = $wpdb->prefix . 'flavor_rec_reparadores';
        $sql_reparadores = "CREATE TABLE $tabla_reparadores (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            especialidades text DEFAULT NULL,
            descripcion text DEFAULT NULL,
            disponibilidad varchar(100) DEFAULT NULL,
            ubicacion varchar(255) DEFAULT NULL,
            valoracion_media decimal(3,2) DEFAULT 0,
            reparaciones_completadas int(11) DEFAULT 0,
            verificado tinyint(1) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            fecha_registro datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY activo (activo)
        ) $charset_collate;";
        dbDelta($sql_reparadores);

        // Tabla de reparaciones
        $tabla_reparaciones = $wpdb->prefix . 'flavor_rec_reparaciones';
        $sql_reparaciones = "CREATE TABLE $tabla_reparaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            solicitante_id bigint(20) unsigned NOT NULL,
            reparador_id bigint(20) unsigned DEFAULT NULL,
            categoria varchar(50) NOT NULL,
            descripcion text NOT NULL,
            fotos text DEFAULT NULL,
            estado enum('abierta','asignada','en_proceso','completada','cancelada') DEFAULT 'abierta',
            co2_ahorrado decimal(8,2) DEFAULT 0,
            valoracion tinyint(1) DEFAULT NULL,
            comentario_valoracion text DEFAULT NULL,
            fecha_creacion datetime DEFAULT NULL,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY solicitante_id (solicitante_id),
            KEY reparador_id (reparador_id),
            KEY estado (estado)
        ) $charset_collate;";
        dbDelta($sql_reparaciones);

        // Tabla de métricas comunitarias
        $tabla_metricas = $wpdb->prefix . 'flavor_rec_metricas';
        $sql_metricas = "CREATE TABLE $tabla_metricas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            periodo varchar(7) NOT NULL,
            kg_reciclados decimal(12,2) DEFAULT 0,
            items_reutilizados int(11) DEFAULT 0,
            reparaciones_completadas int(11) DEFAULT 0,
            co2_total_ahorrado decimal(12,2) DEFAULT 0,
            usuarios_activos int(11) DEFAULT 0,
            retos_completados int(11) DEFAULT 0,
            material_top varchar(50) DEFAULT NULL,
            fecha_calculo datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY periodo (periodo)
        ) $charset_collate;";
        dbDelta($sql_metricas);

        update_option('flavor_rec_conciencia_db_version', $this->version);
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('rec_economia_circular', [$this, 'shortcode_economia_circular']);
        add_shortcode('rec_mi_huella_reciclaje', [$this, 'shortcode_mi_huella']);
        add_shortcode('rec_retos_activos', [$this, 'shortcode_retos_activos']);
        add_shortcode('rec_red_reparadores', [$this, 'shortcode_red_reparadores']);
        add_shortcode('rec_dashboard_impacto', [$this, 'shortcode_dashboard_impacto']);
    }

    /**
     * Shortcode: Economía circular (intercambio de materiales)
     */
    public function shortcode_economia_circular($atts): string {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Inicia sesión para participar en la economía circular.', 'flavor-platform') . '</p>';
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_reut = $wpdb->prefix . 'flavor_rec_reutilizaciones';

        // Materiales disponibles de otros usuarios
        $materiales_disponibles = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, u.display_name
            FROM $tabla_reut r
            JOIN {$wpdb->users} u ON r.usuario_id = u.ID
            WHERE r.estado = 'disponible'
            AND r.usuario_id != %d
            ORDER BY r.fecha_creacion DESC
            LIMIT 20
        ", $user_id));

        // Mis ofertas
        $mis_ofertas = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $tabla_reut
            WHERE usuario_id = %d
            AND estado IN ('disponible', 'reservado')
            ORDER BY fecha_creacion DESC
        ", $user_id));

        $nonce = wp_create_nonce('rec_conciencia_nonce');

        ob_start();
        include dirname(__FILE__) . '/templates/economia-circular.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi huella de reciclaje
     */
    public function shortcode_mi_huella($atts): string {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Inicia sesión para ver tu huella de reciclaje.', 'flavor-platform') . '</p>';
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_huella = $wpdb->prefix . 'flavor_rec_huella_carbono';

        // Huella del mes actual
        $periodo_actual = date('Y-m');
        $huella_mes = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_huella WHERE usuario_id = %d AND periodo = %s",
            $user_id, $periodo_actual
        ));

        // Histórico de últimos 6 meses
        $historico = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $tabla_huella
            WHERE usuario_id = %d
            ORDER BY periodo DESC
            LIMIT 6
        ", $user_id));

        // Totales acumulados
        $totales = $wpdb->get_row($wpdb->prepare("
            SELECT
                SUM(co2_total_ahorrado) as co2_total,
                SUM(kg_reciclados) as kg_total,
                SUM(items_reutilizados) as items_reut,
                SUM(items_reparados) as items_rep
            FROM $tabla_huella
            WHERE usuario_id = %d
        ", $user_id));

        ob_start();
        include dirname(__FILE__) . '/templates/mi-huella-reciclaje.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Retos activos
     */
    public function shortcode_retos_activos($atts): string {
        global $wpdb;
        $tabla_retos = $wpdb->prefix . 'flavor_rec_retos';
        $tabla_part = $wpdb->prefix . 'flavor_rec_reto_participaciones';
        $user_id = get_current_user_id();

        $retos = $wpdb->get_results("
            SELECT r.*,
                   (SELECT COUNT(*) FROM $tabla_part p WHERE p.reto_id = r.id) as total_participantes
            FROM $tabla_retos r
            WHERE r.estado = 'activo'
            AND r.fecha_fin >= CURDATE()
            ORDER BY r.fecha_fin ASC
            LIMIT 10
        ");

        // Mis participaciones
        $mis_retos = [];
        if ($user_id) {
            $mis_part = $wpdb->get_results($wpdb->prepare("
                SELECT reto_id FROM $tabla_part WHERE usuario_id = %d
            ", $user_id));
            $mis_retos = array_column($mis_part, 'reto_id');
        }

        $nonce = wp_create_nonce('rec_conciencia_nonce');

        ob_start();
        include dirname(__FILE__) . '/templates/retos-reciclaje.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Red de reparadores
     */
    public function shortcode_red_reparadores($atts): string {
        global $wpdb;
        $tabla_rep = $wpdb->prefix . 'flavor_rec_reparadores';
        $tabla_reparaciones = $wpdb->prefix . 'flavor_rec_reparaciones';

        $reparadores = $wpdb->get_results("
            SELECT r.*, u.display_name, u.user_email
            FROM $tabla_rep r
            JOIN {$wpdb->users} u ON r.usuario_id = u.ID
            WHERE r.activo = 1
            ORDER BY r.verificado DESC, r.valoracion_media DESC, r.reparaciones_completadas DESC
            LIMIT 20
        ");

        // Solicitudes abiertas
        $solicitudes = $wpdb->get_results("
            SELECT s.*, u.display_name as solicitante_nombre
            FROM $tabla_reparaciones s
            JOIN {$wpdb->users} u ON s.solicitante_id = u.ID
            WHERE s.estado = 'abierta'
            ORDER BY s.fecha_creacion DESC
            LIMIT 10
        ");

        $nonce = wp_create_nonce('rec_conciencia_nonce');
        $user_id = get_current_user_id();

        ob_start();
        include dirname(__FILE__) . '/templates/red-reparadores.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard de impacto
     */
    public function shortcode_dashboard_impacto($atts): string {
        global $wpdb;
        $tabla_metricas = $wpdb->prefix . 'flavor_rec_metricas';

        $periodo = date('Y-m');

        $metricas = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_metricas WHERE periodo = %s",
            $periodo
        ));

        if (!$metricas) {
            $metricas = $this->calcular_metricas_periodo($periodo);
        }

        // Tendencia
        $tendencia = $wpdb->get_results("
            SELECT * FROM $tabla_metricas
            ORDER BY periodo DESC
            LIMIT 6
        ");

        // Top recicladores
        $tabla_huella = $wpdb->prefix . 'flavor_rec_huella_carbono';
        $top_recicladores = $wpdb->get_results($wpdb->prepare("
            SELECT h.*, u.display_name
            FROM $tabla_huella h
            JOIN {$wpdb->users} u ON h.usuario_id = u.ID
            WHERE h.periodo = %s
            ORDER BY h.co2_total_ahorrado DESC
            LIMIT 5
        ", $periodo));

        ob_start();
        include dirname(__FILE__) . '/templates/dashboard-impacto-reciclaje.php';
        return ob_get_clean();
    }

    /**
     * Calcular métricas de un período
     */
    private function calcular_metricas_periodo(string $periodo): object {
        global $wpdb;

        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $tabla_reut = $wpdb->prefix . 'flavor_rec_reutilizaciones';
        $tabla_rep = $wpdb->prefix . 'flavor_rec_reparaciones';
        $tabla_retos = $wpdb->prefix . 'flavor_rec_retos';

        $kg = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(cantidad), 0) FROM $tabla_depositos WHERE DATE_FORMAT(fecha, '%%Y-%%m') = %s",
            $periodo
        ));

        $items_reut = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reut WHERE estado = 'entregado' AND DATE_FORMAT(fecha_entrega, '%%Y-%%m') = %s",
            $periodo
        ));

        $reparaciones = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_rep WHERE estado = 'completada' AND DATE_FORMAT(fecha_completado, '%%Y-%%m') = %s",
            $periodo
        ));

        $usuarios = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_depositos WHERE DATE_FORMAT(fecha, '%%Y-%%m') = %s",
            $periodo
        ));

        $retos_comp = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_retos WHERE estado = 'completado' AND DATE_FORMAT(fecha_fin, '%%Y-%%m') = %s",
            $periodo
        ));

        // Calcular CO2 ahorrado (aproximado)
        $co2 = $kg * 0.8; // Factor promedio

        return (object) [
            'periodo' => $periodo,
            'kg_reciclados' => $kg,
            'items_reutilizados' => $items_reut,
            'reparaciones_completadas' => $reparaciones,
            'co2_total_ahorrado' => $co2,
            'usuarios_activos' => $usuarios,
            'retos_completados' => $retos_comp,
        ];
    }

    /**
     * Hook: Depósito registrado
     */
    public function on_deposito_registrado($deposito_id, $user_id, $datos) {
        global $wpdb;
        $tabla_huella = $wpdb->prefix . 'flavor_rec_huella_carbono';

        $material = $datos['material'] ?? 'otro';
        $cantidad = floatval($datos['cantidad'] ?? 0);
        $factor_co2 = $this->factores_co2[$material] ?? 0.5;
        $co2_ahorrado = $cantidad * $factor_co2;

        $periodo = date('Y-m');

        // Actualizar o crear registro de huella
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_huella WHERE usuario_id = %d AND periodo = %s",
            $user_id, $periodo
        ));

        if ($existe) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_huella SET
                    co2_reciclaje = co2_reciclaje + %f,
                    co2_total_ahorrado = co2_total_ahorrado + %f,
                    kg_reciclados = kg_reciclados + %f,
                    fecha_actualizacion = NOW()
                WHERE usuario_id = %d AND periodo = %s",
                $co2_ahorrado, $co2_ahorrado, $cantidad, $user_id, $periodo
            ));
        } else {
            $wpdb->insert($tabla_huella, [
                'usuario_id' => $user_id,
                'periodo' => $periodo,
                'co2_reciclaje' => $co2_ahorrado,
                'co2_total_ahorrado' => $co2_ahorrado,
                'kg_reciclados' => $cantidad,
                'fecha_actualizacion' => current_time('mysql'),
            ]);
        }

        // Actualizar progreso en retos activos
        $this->actualizar_progreso_retos($user_id, 'reciclaje', $material, $cantidad);
    }

    /**
     * Actualizar progreso en retos
     */
    private function actualizar_progreso_retos(int $user_id, string $tipo, string $material, float $cantidad) {
        global $wpdb;
        $tabla_retos = $wpdb->prefix . 'flavor_rec_retos';
        $tabla_part = $wpdb->prefix . 'flavor_rec_reto_participaciones';

        // Obtener retos activos donde participa el usuario
        $retos = $wpdb->get_results($wpdb->prepare("
            SELECT r.* FROM $tabla_retos r
            JOIN $tabla_part p ON r.id = p.reto_id
            WHERE p.usuario_id = %d
            AND r.estado = 'activo'
            AND r.tipo = %s
            AND (r.material_objetivo IS NULL OR r.material_objetivo = %s)
            AND r.fecha_fin >= CURDATE()
        ", $user_id, $tipo, $material));

        foreach ($retos as $reto) {
            // Actualizar contribución del usuario
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_part SET
                    contribucion = contribucion + %f,
                    fecha_ultima_contribucion = NOW()
                WHERE reto_id = %d AND usuario_id = %d",
                $cantidad, $reto->id, $user_id
            ));

            // Actualizar progreso global del reto
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_retos SET progreso_actual = progreso_actual + %f WHERE id = %d",
                $cantidad, $reto->id
            ));

            // Verificar si se completó
            $progreso = $wpdb->get_var($wpdb->prepare(
                "SELECT progreso_actual FROM $tabla_retos WHERE id = %d",
                $reto->id
            ));

            if ($progreso >= $reto->meta_cantidad) {
                $this->completar_reto($reto->id);
            }
        }
    }

    /**
     * Completar un reto
     */
    private function completar_reto(int $reto_id) {
        global $wpdb;
        $tabla_retos = $wpdb->prefix . 'flavor_rec_retos';
        $tabla_part = $wpdb->prefix . 'flavor_rec_reto_participaciones';

        $reto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_retos WHERE id = %d",
            $reto_id
        ));

        if (!$reto || $reto->estado !== 'activo') return;

        // Marcar como completado
        $wpdb->update(
            $tabla_retos,
            ['estado' => 'completado'],
            ['id' => $reto_id]
        );

        // Otorgar puntos a participantes
        $participantes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_part WHERE reto_id = %d",
            $reto_id
        ));

        foreach ($participantes as $part) {
            $puntos = intval($reto->puntos_recompensa * ($part->contribucion / $reto->meta_cantidad));
            $wpdb->update(
                $tabla_part,
                ['puntos_ganados' => $puntos],
                ['id' => $part->id]
            );
        }
    }

    /**
     * AJAX: Registrar reutilización
     */
    public function ajax_registrar_reutilizacion() {
        check_ajax_referer('rec_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $material_tipo = sanitize_text_field($_POST['material_tipo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $cantidad = floatval($_POST['cantidad'] ?? 1);
        $ubicacion = sanitize_text_field($_POST['ubicacion'] ?? '');

        if (empty($material_tipo)) {
            wp_send_json_error(['message' => __('Indica el tipo de material.', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_reut = $wpdb->prefix . 'flavor_rec_reutilizaciones';

        $factor_co2 = $this->factores_co2[$material_tipo] ?? 0.5;
        $co2 = $cantidad * $factor_co2 * 0.5; // 50% del factor para reutilización

        $wpdb->insert($tabla_reut, [
            'usuario_id' => get_current_user_id(),
            'material_tipo' => $material_tipo,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'ubicacion' => $ubicacion,
            'co2_ahorrado' => $co2,
            'estado' => 'disponible',
            'fecha_creacion' => current_time('mysql'),
        ]);

        wp_send_json_success([
            'message' => __('Material publicado para reutilización.', 'flavor-platform'),
            'id' => $wpdb->insert_id,
        ]);
    }

    /**
     * AJAX: Unirse a reto
     */
    public function ajax_unirse_reto() {
        check_ajax_referer('rec_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $reto_id = intval($_POST['reto_id'] ?? 0);

        if (!$reto_id) {
            wp_send_json_error(['message' => __('Reto no válido.', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_retos = $wpdb->prefix . 'flavor_rec_retos';
        $tabla_part = $wpdb->prefix . 'flavor_rec_reto_participaciones';
        $user_id = get_current_user_id();

        // Verificar que el reto existe y está activo
        $reto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_retos WHERE id = %d AND estado = 'activo'",
            $reto_id
        ));

        if (!$reto) {
            wp_send_json_error(['message' => __('El reto no está disponible.', 'flavor-platform')]);
        }

        // Verificar si ya participa
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_part WHERE reto_id = %d AND usuario_id = %d",
            $reto_id, $user_id
        ));

        if ($existe) {
            wp_send_json_error(['message' => __('Ya participas en este reto.', 'flavor-platform')]);
        }

        $wpdb->insert($tabla_part, [
            'reto_id' => $reto_id,
            'usuario_id' => $user_id,
            'fecha_inscripcion' => current_time('mysql'),
        ]);

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_retos SET participantes = participantes + 1 WHERE id = %d",
            $reto_id
        ));

        wp_send_json_success(['message' => __('¡Te has unido al reto!', 'flavor-platform')]);
    }

    /**
     * AJAX: Registrar reparación
     */
    public function ajax_registrar_reparacion() {
        check_ajax_referer('rec_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        if (empty($categoria) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Completa todos los campos.', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_rep = $wpdb->prefix . 'flavor_rec_reparaciones';

        $wpdb->insert($tabla_rep, [
            'solicitante_id' => get_current_user_id(),
            'categoria' => $categoria,
            'descripcion' => $descripcion,
            'estado' => 'abierta',
            'fecha_creacion' => current_time('mysql'),
        ]);

        wp_send_json_success([
            'message' => __('Solicitud de reparación publicada.', 'flavor-platform'),
            'id' => $wpdb->insert_id,
        ]);
    }

    /**
     * AJAX: Ofrecer material
     */
    public function ajax_ofrecer_material() {
        check_ajax_referer('rec_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $material_id = intval($_POST['material_id'] ?? 0);
        $accion = sanitize_text_field($_POST['accion'] ?? 'reservar');

        global $wpdb;
        $tabla_reut = $wpdb->prefix . 'flavor_rec_reutilizaciones';
        $user_id = get_current_user_id();

        $material = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reut WHERE id = %d AND estado = 'disponible'",
            $material_id
        ));

        if (!$material) {
            wp_send_json_error(['message' => __('Material no disponible.', 'flavor-platform')]);
        }

        if ($material->usuario_id == $user_id) {
            wp_send_json_error(['message' => __('No puedes solicitar tu propio material.', 'flavor-platform')]);
        }

        $wpdb->update(
            $tabla_reut,
            [
                'estado' => 'reservado',
                'receptor_id' => $user_id,
            ],
            ['id' => $material_id]
        );

        wp_send_json_success(['message' => __('Material reservado. Contacta con el dueño para recogerlo.', 'flavor-platform')]);
    }

    /**
     * AJAX: Solicitar material
     */
    public function ajax_solicitar_material() {
        return $this->ajax_ofrecer_material();
    }
}
