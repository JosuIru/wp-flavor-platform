<?php
/**
 * Funcionalidades de Sello de Conciencia para módulo Incidencias
 * +13 puntos: Monitoreo ambiental, alertas comunitarias, voluntariado resolución,
 * gamificación y dashboard de impacto urbano
 *
 * @package FlavorPlatform
 * @subpackage Incidencias
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal de funcionalidades de conciencia para Incidencias
 */
class Flavor_Incidencias_Conciencia_Features {

    /** @var self|null */
    private static $instance = null;

    /** @var string */
    private $version = '1.0.0';

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
        add_action('wp_ajax_inc_solicitar_voluntariado', [$this, 'ajax_solicitar_voluntariado']);
        add_action('wp_ajax_inc_resolver_voluntario', [$this, 'ajax_resolver_voluntario']);
        add_action('wp_ajax_inc_obtener_alertas_zona', [$this, 'ajax_obtener_alertas_zona']);
        add_action('wp_ajax_nopriv_inc_obtener_alertas_zona', [$this, 'ajax_obtener_alertas_zona']);
        add_action('wp_ajax_inc_agregar_comentario_ambiental', [$this, 'ajax_agregar_comentario_ambiental']);
        add_action('wp_ajax_inc_valorar_resolucion', [$this, 'ajax_valorar_resolucion']);

        // Hooks de incidencias
        add_action('incidencia_created', [$this, 'on_incidencia_creada'], 10, 2);
        add_action('incidencia_resolved', [$this, 'on_incidencia_resuelta'], 10, 2);
        add_action('incidencia_comment_added', [$this, 'actualizar_participacion'], 10, 3);
    }

    /**
     * Crear tablas adicionales
     */
    public function maybe_create_tables() {
        $installed_version = get_option('flavor_inc_conciencia_db_version', '0');
        if (version_compare($installed_version, $this->version, '>=')) {
            return;
        }

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Tabla de impacto ambiental de incidencias
        $tabla_impacto = $wpdb->prefix . 'flavor_inc_impacto_ambiental';
        $sql_impacto = "CREATE TABLE $tabla_impacto (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            incidencia_id bigint(20) unsigned NOT NULL,
            tipo_impacto enum('ruido','contaminacion_aire','contaminacion_agua','residuos','visual','otro') NOT NULL,
            severidad tinyint(1) DEFAULT 1,
            afecta_salud tinyint(1) DEFAULT 0,
            radio_afectacion int(11) DEFAULT 100,
            descripcion_impacto text DEFAULT NULL,
            fecha_registro datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY incidencia_id (incidencia_id),
            KEY tipo_impacto (tipo_impacto)
        ) $charset_collate;";
        dbDelta($sql_impacto);

        // Tabla de alertas comunitarias
        $tabla_alertas = $wpdb->prefix . 'flavor_inc_alertas';
        $sql_alertas = "CREATE TABLE $tabla_alertas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            incidencia_id bigint(20) unsigned NOT NULL,
            tipo_alerta enum('urgente','moderada','informativa') NOT NULL DEFAULT 'informativa',
            radio_metros int(11) DEFAULT 500,
            titulo varchar(255) NOT NULL,
            mensaje text NOT NULL,
            activa tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT NULL,
            fecha_expiracion datetime DEFAULT NULL,
            notificaciones_enviadas int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY incidencia_id (incidencia_id),
            KEY activa (activa),
            KEY fecha_expiracion (fecha_expiracion)
        ) $charset_collate;";
        dbDelta($sql_alertas);

        // Tabla de voluntariado para resolución
        $tabla_voluntarios = $wpdb->prefix . 'flavor_inc_voluntarios';
        $sql_voluntarios = "CREATE TABLE $tabla_voluntarios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            incidencia_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo_ayuda enum('reparar','limpiar','vigilar','coordinar','otro') NOT NULL,
            estado enum('ofrecida','aceptada','en_proceso','completada','cancelada') DEFAULT 'ofrecida',
            descripcion text DEFAULT NULL,
            fecha_oferta datetime DEFAULT NULL,
            fecha_completado datetime DEFAULT NULL,
            horas_dedicadas decimal(4,2) DEFAULT 0,
            verificado_por bigint(20) unsigned DEFAULT NULL,
            puntos_otorgados int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY incidencia_id (incidencia_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";
        dbDelta($sql_voluntarios);

        // Tabla de participación ciudadana (gamificación)
        $tabla_participacion = $wpdb->prefix . 'flavor_inc_participacion';
        $sql_participacion = "CREATE TABLE $tabla_participacion (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            puntos_totales int(11) DEFAULT 0,
            incidencias_reportadas int(11) DEFAULT 0,
            incidencias_resueltas int(11) DEFAULT 0,
            votos_dados int(11) DEFAULT 0,
            comentarios_utiles int(11) DEFAULT 0,
            voluntariados_completados int(11) DEFAULT 0,
            horas_voluntariado decimal(6,2) DEFAULT 0,
            nivel int(11) DEFAULT 1,
            logros text DEFAULT NULL,
            fecha_ultimo_reporte datetime DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY puntos_totales (puntos_totales),
            KEY nivel (nivel)
        ) $charset_collate;";
        dbDelta($sql_participacion);

        // Tabla de valoraciones de resolución
        $tabla_valoraciones = $wpdb->prefix . 'flavor_inc_valoraciones';
        $sql_valoraciones = "CREATE TABLE $tabla_valoraciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            incidencia_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            calidad_resolucion tinyint(1) DEFAULT 3,
            tiempo_respuesta tinyint(1) DEFAULT 3,
            comunicacion tinyint(1) DEFAULT 3,
            comentario text DEFAULT NULL,
            fecha_valoracion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY incidencia_usuario (incidencia_id, usuario_id)
        ) $charset_collate;";
        dbDelta($sql_valoraciones);

        // Tabla de métricas urbanas
        $tabla_metricas = $wpdb->prefix . 'flavor_inc_metricas_urbanas';
        $sql_metricas = "CREATE TABLE $tabla_metricas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            periodo varchar(7) NOT NULL,
            zona varchar(100) DEFAULT NULL,
            total_incidencias int(11) DEFAULT 0,
            resueltas int(11) DEFAULT 0,
            tiempo_medio_resolucion decimal(8,2) DEFAULT NULL,
            participacion_ciudadana int(11) DEFAULT 0,
            indice_satisfaccion decimal(3,2) DEFAULT NULL,
            incidencias_ambientales int(11) DEFAULT 0,
            voluntariados int(11) DEFAULT 0,
            categoria_mas_frecuente varchar(50) DEFAULT NULL,
            fecha_calculo datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY periodo_zona (periodo, zona)
        ) $charset_collate;";
        dbDelta($sql_metricas);

        update_option('flavor_inc_conciencia_db_version', $this->version);
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('inc_mapa_impacto_ambiental', [$this, 'shortcode_mapa_impacto']);
        add_shortcode('inc_alertas_mi_zona', [$this, 'shortcode_alertas_zona']);
        add_shortcode('inc_voluntariado_disponible', [$this, 'shortcode_voluntariado']);
        add_shortcode('inc_mi_participacion', [$this, 'shortcode_mi_participacion']);
        add_shortcode('inc_dashboard_urbano', [$this, 'shortcode_dashboard_urbano']);
        add_shortcode('inc_ranking_ciudadanos', [$this, 'shortcode_ranking']);
    }

    /**
     * Shortcode: Mapa de impacto ambiental
     */
    public function shortcode_mapa_impacto($atts): string {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Inicia sesión para ver el mapa de impacto ambiental.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        global $wpdb;
        $tabla_inc = $wpdb->prefix . 'flavor_incidencias';
        $tabla_impacto = $wpdb->prefix . 'flavor_inc_impacto_ambiental';

        $incidencias = $wpdb->get_results("
            SELECT i.*, imp.tipo_impacto, imp.severidad, imp.radio_afectacion
            FROM $tabla_inc i
            LEFT JOIN $tabla_impacto imp ON i.id = imp.incidencia_id
            WHERE i.estado NOT IN ('cerrada', 'rechazada')
            AND imp.id IS NOT NULL
            ORDER BY imp.severidad DESC, i.fecha_creacion DESC
            LIMIT 100
        ");

        $nonce = wp_create_nonce('inc_conciencia_nonce');

        ob_start();
        include dirname(__FILE__) . '/templates/mapa-impacto-ambiental.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Alertas de mi zona
     */
    public function shortcode_alertas_zona($atts): string {
        global $wpdb;
        $tabla_alertas = $wpdb->prefix . 'flavor_inc_alertas';

        $alertas = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, i.titulo as incidencia_titulo, i.categoria, i.latitud, i.longitud
            FROM $tabla_alertas a
            JOIN {$wpdb->prefix}flavor_incidencias i ON a.incidencia_id = i.id
            WHERE a.activa = 1
            AND (a.fecha_expiracion IS NULL OR a.fecha_expiracion > NOW())
            ORDER BY
                CASE a.tipo_alerta
                    WHEN 'urgente' THEN 1
                    WHEN 'moderada' THEN 2
                    ELSE 3
                END,
                a.fecha_creacion DESC
            LIMIT %d
        ", 10));

        $nonce = wp_create_nonce('inc_conciencia_nonce');

        ob_start();
        include dirname(__FILE__) . '/templates/alertas-zona.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Voluntariado disponible
     */
    public function shortcode_voluntariado($atts): string {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Inicia sesión para ver oportunidades de voluntariado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_inc = $wpdb->prefix . 'flavor_incidencias';

        // Incidencias que pueden ser resueltas por voluntarios
        $categorias_voluntariado = ['limpieza', 'parques', 'mobiliario', 'senalizacion'];
        $placeholders = implode(',', array_fill(0, count($categorias_voluntariado), '%s'));

        $incidencias = $wpdb->get_results($wpdb->prepare("
            SELECT i.*,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_inc_voluntarios v
                    WHERE v.incidencia_id = i.id AND v.estado IN ('ofrecida', 'aceptada', 'en_proceso')) as voluntarios_activos
            FROM $tabla_inc i
            WHERE i.categoria IN ($placeholders)
            AND i.estado IN ('pendiente', 'validada')
            AND i.prioridad IN ('baja', 'media')
            ORDER BY i.votos DESC, i.fecha_creacion ASC
            LIMIT 20
        ", ...$categorias_voluntariado));

        // Mis voluntariados activos
        $mis_voluntariados = $wpdb->get_results($wpdb->prepare("
            SELECT v.*, i.titulo, i.categoria, i.direccion
            FROM {$wpdb->prefix}flavor_inc_voluntarios v
            JOIN $tabla_inc i ON v.incidencia_id = i.id
            WHERE v.usuario_id = %d
            AND v.estado IN ('ofrecida', 'aceptada', 'en_proceso')
            ORDER BY v.fecha_oferta DESC
        ", $user_id));

        $nonce = wp_create_nonce('inc_conciencia_nonce');

        ob_start();
        include dirname(__FILE__) . '/templates/voluntariado-incidencias.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi participación
     */
    public function shortcode_mi_participacion($atts): string {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Inicia sesión para ver tu participación.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_part = $wpdb->prefix . 'flavor_inc_participacion';

        $participacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_part WHERE usuario_id = %d",
            $user_id
        ));

        if (!$participacion) {
            $participacion = (object) [
                'puntos_totales' => 0,
                'incidencias_reportadas' => 0,
                'incidencias_resueltas' => 0,
                'votos_dados' => 0,
                'comentarios_utiles' => 0,
                'voluntariados_completados' => 0,
                'horas_voluntariado' => 0,
                'nivel' => 1,
                'logros' => '[]',
            ];
        }

        $logros = json_decode($participacion->logros ?: '[]', true) ?: [];

        ob_start();
        include dirname(__FILE__) . '/templates/mi-participacion-incidencias.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard urbano
     */
    public function shortcode_dashboard_urbano($atts): string {
        global $wpdb;
        $tabla_inc = $wpdb->prefix . 'flavor_incidencias';
        $tabla_metricas = $wpdb->prefix . 'flavor_inc_metricas_urbanas';

        $periodo = date('Y-m');

        // Métricas actuales
        $metricas = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_metricas WHERE periodo = %s AND zona IS NULL",
            $periodo
        ));

        if (!$metricas) {
            // Calcular métricas en tiempo real
            $metricas = $this->calcular_metricas_periodo($periodo);
        }

        // Resumen por categoría
        $por_categoria = $wpdb->get_results("
            SELECT categoria,
                   COUNT(*) as total,
                   SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas
            FROM $tabla_inc
            WHERE DATE_FORMAT(fecha_creacion, '%Y-%m') = '$periodo'
            GROUP BY categoria
            ORDER BY total DESC
            LIMIT 5
        ");

        // Tendencia mensual
        $tendencia = $wpdb->get_results("
            SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
                   COUNT(*) as total,
                   SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas
            FROM $tabla_inc
            WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY mes
            ORDER BY mes ASC
        ");

        ob_start();
        include dirname(__FILE__) . '/templates/dashboard-urbano.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Ranking de ciudadanos
     */
    public function shortcode_ranking($atts): string {
        $atts = shortcode_atts(['limite' => 10], $atts);

        global $wpdb;
        $tabla_part = $wpdb->prefix . 'flavor_inc_participacion';

        $ranking = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, u.display_name, u.user_email
            FROM $tabla_part p
            JOIN {$wpdb->users} u ON p.usuario_id = u.ID
            WHERE p.puntos_totales > 0
            ORDER BY p.puntos_totales DESC, p.incidencias_reportadas DESC
            LIMIT %d
        ", intval($atts['limite'])));

        $user_id = get_current_user_id();

        ob_start();
        include dirname(__FILE__) . '/templates/ranking-ciudadanos.php';
        return ob_get_clean();
    }

    /**
     * Calcular métricas de un período
     */
    private function calcular_metricas_periodo(string $periodo): object {
        global $wpdb;
        $tabla_inc = $wpdb->prefix . 'flavor_incidencias';
        $tabla_impacto = $wpdb->prefix . 'flavor_inc_impacto_ambiental';
        $tabla_vol = $wpdb->prefix . 'flavor_inc_voluntarios';
        $tabla_val = $wpdb->prefix . 'flavor_inc_valoraciones';

        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inc WHERE DATE_FORMAT(fecha_creacion, '%%Y-%%m') = %s",
            $periodo
        ));

        $resueltas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inc WHERE DATE_FORMAT(fecha_creacion, '%%Y-%%m') = %s AND estado = 'resuelta'",
            $periodo
        ));

        $tiempo_medio = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_resolucion))
             FROM $tabla_inc
             WHERE DATE_FORMAT(fecha_creacion, '%%Y-%%m') = %s AND estado = 'resuelta' AND fecha_resolucion IS NOT NULL",
            $periodo
        ));

        $usuarios_unicos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_inc WHERE DATE_FORMAT(fecha_creacion, '%%Y-%%m') = %s",
            $periodo
        ));

        $satisfaccion = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG((calidad_resolucion + tiempo_respuesta + comunicacion) / 3)
             FROM $tabla_val v
             JOIN $tabla_inc i ON v.incidencia_id = i.id
             WHERE DATE_FORMAT(i.fecha_creacion, '%%Y-%%m') = %s",
            $periodo
        ));

        $ambientales = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT imp.incidencia_id)
             FROM $tabla_impacto imp
             JOIN $tabla_inc i ON imp.incidencia_id = i.id
             WHERE DATE_FORMAT(i.fecha_creacion, '%%Y-%%m') = %s",
            $periodo
        ));

        $voluntariados = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_vol WHERE estado = 'completada' AND DATE_FORMAT(fecha_completado, '%%Y-%%m') = %s",
            $periodo
        ));

        $categoria_frecuente = $wpdb->get_var($wpdb->prepare(
            "SELECT categoria FROM $tabla_inc
             WHERE DATE_FORMAT(fecha_creacion, '%%Y-%%m') = %s
             GROUP BY categoria ORDER BY COUNT(*) DESC LIMIT 1",
            $periodo
        ));

        return (object) [
            'periodo' => $periodo,
            'total_incidencias' => $total,
            'resueltas' => $resueltas,
            'tiempo_medio_resolucion' => $tiempo_medio,
            'participacion_ciudadana' => $usuarios_unicos,
            'indice_satisfaccion' => $satisfaccion,
            'incidencias_ambientales' => $ambientales,
            'voluntariados' => $voluntariados,
            'categoria_mas_frecuente' => $categoria_frecuente,
        ];
    }

    /**
     * Hook: Incidencia creada
     */
    public function on_incidencia_creada($incidencia_id, $user_id) {
        global $wpdb;
        $tabla_part = $wpdb->prefix . 'flavor_inc_participacion';

        // Actualizar o crear participación
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_part WHERE usuario_id = %d",
            $user_id
        ));

        if ($existe) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_part SET
                    puntos_totales = puntos_totales + 10,
                    incidencias_reportadas = incidencias_reportadas + 1,
                    fecha_ultimo_reporte = NOW(),
                    fecha_actualizacion = NOW()
                WHERE usuario_id = %d",
                $user_id
            ));
        } else {
            $wpdb->insert($tabla_part, [
                'usuario_id' => $user_id,
                'puntos_totales' => 10,
                'incidencias_reportadas' => 1,
                'nivel' => 1,
                'logros' => '[]',
                'fecha_ultimo_reporte' => current_time('mysql'),
                'fecha_actualizacion' => current_time('mysql'),
            ]);
        }

        // Verificar logros
        $this->verificar_logros($user_id);

        // Actualizar nivel
        $this->actualizar_nivel($user_id);
    }

    /**
     * Hook: Incidencia resuelta
     */
    public function on_incidencia_resuelta($incidencia_id, $user_id) {
        global $wpdb;
        $tabla_part = $wpdb->prefix . 'flavor_inc_participacion';

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_part SET
                puntos_totales = puntos_totales + 5,
                incidencias_resueltas = incidencias_resueltas + 1,
                fecha_actualizacion = NOW()
            WHERE usuario_id = %d",
            $user_id
        ));

        $this->verificar_logros($user_id);
        $this->actualizar_nivel($user_id);
    }

    /**
     * Actualizar participación por comentario
     */
    public function actualizar_participacion($ticket_id, $user_id, $comment_author) {
        global $wpdb;
        $tabla_part = $wpdb->prefix . 'flavor_inc_participacion';

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_part WHERE usuario_id = %d",
            $user_id
        ));

        if ($existe) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_part SET
                    puntos_totales = puntos_totales + 2,
                    comentarios_utiles = comentarios_utiles + 1,
                    fecha_actualizacion = NOW()
                WHERE usuario_id = %d",
                $user_id
            ));
        }
    }

    /**
     * Verificar y otorgar logros
     */
    private function verificar_logros(int $user_id) {
        global $wpdb;
        $tabla_part = $wpdb->prefix . 'flavor_inc_participacion';

        $participacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_part WHERE usuario_id = %d",
            $user_id
        ));

        if (!$participacion) return;

        $logros_actuales = json_decode($participacion->logros ?: '[]', true) ?: [];
        $nuevos_logros = [];

        // Definir logros
        $logros_disponibles = [
            'primer_reporte' => ['condicion' => $participacion->incidencias_reportadas >= 1, 'nombre' => 'Primer reporte'],
            'reportero_activo' => ['condicion' => $participacion->incidencias_reportadas >= 10, 'nombre' => '10 reportes'],
            'reportero_experto' => ['condicion' => $participacion->incidencias_reportadas >= 50, 'nombre' => '50 reportes'],
            'solucionador' => ['condicion' => $participacion->incidencias_resueltas >= 5, 'nombre' => '5 resueltas'],
            'voluntario' => ['condicion' => $participacion->voluntariados_completados >= 1, 'nombre' => 'Primer voluntariado'],
            'voluntario_experto' => ['condicion' => $participacion->voluntariados_completados >= 10, 'nombre' => '10 voluntariados'],
            'comentarista' => ['condicion' => $participacion->comentarios_utiles >= 20, 'nombre' => '20 comentarios'],
            'nivel_5' => ['condicion' => $participacion->nivel >= 5, 'nombre' => 'Nivel 5'],
            'nivel_10' => ['condicion' => $participacion->nivel >= 10, 'nombre' => 'Nivel 10'],
        ];

        foreach ($logros_disponibles as $key => $logro) {
            if ($logro['condicion'] && !in_array($key, $logros_actuales)) {
                $logros_actuales[] = $key;
                $nuevos_logros[] = $logro['nombre'];
            }
        }

        if (!empty($nuevos_logros)) {
            $wpdb->update(
                $tabla_part,
                ['logros' => json_encode($logros_actuales)],
                ['usuario_id' => $user_id]
            );
        }
    }

    /**
     * Actualizar nivel del usuario
     */
    private function actualizar_nivel(int $user_id) {
        global $wpdb;
        $tabla_part = $wpdb->prefix . 'flavor_inc_participacion';

        $puntos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT puntos_totales FROM $tabla_part WHERE usuario_id = %d",
            $user_id
        ));

        // Calcular nivel basado en puntos
        $nivel = 1;
        if ($puntos >= 1000) $nivel = 10;
        elseif ($puntos >= 500) $nivel = 8;
        elseif ($puntos >= 300) $nivel = 6;
        elseif ($puntos >= 150) $nivel = 4;
        elseif ($puntos >= 75) $nivel = 3;
        elseif ($puntos >= 30) $nivel = 2;

        $wpdb->update(
            $tabla_part,
            ['nivel' => $nivel],
            ['usuario_id' => $user_id]
        );
    }

    /**
     * AJAX: Solicitar voluntariado
     */
    public function ajax_solicitar_voluntariado() {
        check_ajax_referer('inc_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $incidencia_id = intval($_POST['incidencia_id'] ?? 0);
        $tipo_ayuda = sanitize_text_field($_POST['tipo_ayuda'] ?? 'otro');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        if (!$incidencia_id) {
            wp_send_json_error(['message' => __('Incidencia no válida.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_vol = $wpdb->prefix . 'flavor_inc_voluntarios';
        $user_id = get_current_user_id();

        // Verificar si ya se ofreció
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_vol WHERE incidencia_id = %d AND usuario_id = %d AND estado NOT IN ('completada', 'cancelada')",
            $incidencia_id, $user_id
        ));

        if ($existe) {
            wp_send_json_error(['message' => __('Ya te has ofrecido para esta incidencia.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $wpdb->insert($tabla_vol, [
            'incidencia_id' => $incidencia_id,
            'usuario_id' => $user_id,
            'tipo_ayuda' => $tipo_ayuda,
            'descripcion' => $descripcion,
            'estado' => 'ofrecida',
            'fecha_oferta' => current_time('mysql'),
        ]);

        wp_send_json_success(['message' => __('Tu oferta de ayuda ha sido registrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Marcar voluntariado como resuelto
     */
    public function ajax_resolver_voluntario() {
        check_ajax_referer('inc_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $voluntariado_id = intval($_POST['voluntariado_id'] ?? 0);
        $horas = floatval($_POST['horas'] ?? 1);

        global $wpdb;
        $tabla_vol = $wpdb->prefix . 'flavor_inc_voluntarios';
        $tabla_part = $wpdb->prefix . 'flavor_inc_participacion';
        $user_id = get_current_user_id();

        $voluntariado = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_vol WHERE id = %d AND usuario_id = %d",
            $voluntariado_id, $user_id
        ));

        if (!$voluntariado) {
            wp_send_json_error(['message' => __('Voluntariado no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Calcular puntos
        $puntos = intval($horas * 15); // 15 puntos por hora

        $wpdb->update(
            $tabla_vol,
            [
                'estado' => 'completada',
                'fecha_completado' => current_time('mysql'),
                'horas_dedicadas' => $horas,
                'puntos_otorgados' => $puntos,
            ],
            ['id' => $voluntariado_id]
        );

        // Actualizar participación
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_part SET
                puntos_totales = puntos_totales + %d,
                voluntariados_completados = voluntariados_completados + 1,
                horas_voluntariado = horas_voluntariado + %f,
                fecha_actualizacion = NOW()
            WHERE usuario_id = %d",
            $puntos, $horas, $user_id
        ));

        $this->verificar_logros($user_id);
        $this->actualizar_nivel($user_id);

        wp_send_json_success([
            'message' => sprintf(__('¡Gracias! Has ganado %d puntos.', FLAVOR_PLATFORM_TEXT_DOMAIN), $puntos),
            'puntos' => $puntos,
        ]);
    }

    /**
     * AJAX: Obtener alertas de zona
     */
    public function ajax_obtener_alertas_zona() {
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        $radio = intval($_POST['radio'] ?? 1000);

        global $wpdb;
        $tabla_alertas = $wpdb->prefix . 'flavor_inc_alertas';
        $tabla_inc = $wpdb->prefix . 'flavor_incidencias';

        // Fórmula Haversine para distancia
        $alertas = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, i.titulo as incidencia_titulo, i.latitud, i.longitud,
                   (6371000 * acos(cos(radians(%f)) * cos(radians(i.latitud)) * cos(radians(i.longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(i.latitud)))) AS distancia
            FROM $tabla_alertas a
            JOIN $tabla_inc i ON a.incidencia_id = i.id
            WHERE a.activa = 1
            AND (a.fecha_expiracion IS NULL OR a.fecha_expiracion > NOW())
            HAVING distancia <= %d
            ORDER BY a.tipo_alerta ASC, distancia ASC
            LIMIT 20
        ", $lat, $lng, $lat, $radio));

        wp_send_json_success(['alertas' => $alertas]);
    }

    /**
     * AJAX: Agregar comentario ambiental
     */
    public function ajax_agregar_comentario_ambiental() {
        check_ajax_referer('inc_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $incidencia_id = intval($_POST['incidencia_id'] ?? 0);
        $tipo_impacto = sanitize_text_field($_POST['tipo_impacto'] ?? 'otro');
        $severidad = min(5, max(1, intval($_POST['severidad'] ?? 1)));
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $afecta_salud = intval($_POST['afecta_salud'] ?? 0);

        if (!$incidencia_id) {
            wp_send_json_error(['message' => __('Incidencia no válida.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_impacto = $wpdb->prefix . 'flavor_inc_impacto_ambiental';

        $wpdb->insert($tabla_impacto, [
            'incidencia_id' => $incidencia_id,
            'tipo_impacto' => $tipo_impacto,
            'severidad' => $severidad,
            'afecta_salud' => $afecta_salud,
            'descripcion_impacto' => $descripcion,
            'fecha_registro' => current_time('mysql'),
        ]);

        wp_send_json_success(['message' => __('Información de impacto ambiental registrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Valorar resolución de incidencia
     */
    public function ajax_valorar_resolucion() {
        check_ajax_referer('inc_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $incidencia_id = intval($_POST['incidencia_id'] ?? 0);
        $calidad = min(5, max(1, intval($_POST['calidad'] ?? 3)));
        $tiempo = min(5, max(1, intval($_POST['tiempo'] ?? 3)));
        $comunicacion = min(5, max(1, intval($_POST['comunicacion'] ?? 3)));
        $comentario = sanitize_textarea_field($_POST['comentario'] ?? '');

        global $wpdb;
        $tabla_val = $wpdb->prefix . 'flavor_inc_valoraciones';
        $user_id = get_current_user_id();

        // Verificar si ya valoró
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_val WHERE incidencia_id = %d AND usuario_id = %d",
            $incidencia_id, $user_id
        ));

        if ($existe) {
            wp_send_json_error(['message' => __('Ya has valorado esta resolución.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $wpdb->insert($tabla_val, [
            'incidencia_id' => $incidencia_id,
            'usuario_id' => $user_id,
            'calidad_resolucion' => $calidad,
            'tiempo_respuesta' => $tiempo,
            'comunicacion' => $comunicacion,
            'comentario' => $comentario,
            'fecha_valoracion' => current_time('mysql'),
        ]);

        wp_send_json_success(['message' => __('Gracias por tu valoración.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }
}
