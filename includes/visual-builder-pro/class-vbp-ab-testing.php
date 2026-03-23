<?php
/**
 * Visual Builder Pro - A/B Testing
 *
 * Sistema de A/B testing para elementos del VBP.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para A/B Testing
 *
 * @since 2.0.0
 */
class Flavor_VBP_AB_Testing {

    /**
     * Nombre de la tabla de tests
     *
     * @var string
     */
    private $tabla_tests;

    /**
     * Nombre de la tabla de variantes
     *
     * @var string
     */
    private $tabla_variantes;

    /**
     * Nombre de la tabla de conversiones
     *
     * @var string
     */
    private $tabla_conversiones;

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_AB_Testing|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_AB_Testing
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_tests        = $wpdb->prefix . 'vbp_ab_tests';
        $this->tabla_variantes    = $wpdb->prefix . 'vbp_ab_variants';
        $this->tabla_conversiones = $wpdb->prefix . 'vbp_ab_conversions';

        add_action( 'rest_api_init', array( $this, 'registrar_rutas_rest' ) );
        add_action( 'wp_footer', array( $this, 'cargar_tracking_script' ) );

        // OPTIMIZACIÓN: Solo verificar tablas en admin o cuando sea necesario
        // No hacer queries DB en cada request de frontend
        if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            $this->maybe_crear_tablas();
        }
    }

    /**
     * Crea las tablas si no existen
     * Usa cache en opción de WP para evitar queries repetidas
     */
    private function maybe_crear_tablas() {
        // Usar opción cacheada para evitar SHOW TABLES en cada request
        $version_actual = '1.0.0';
        $version_db = get_option( 'vbp_ab_testing_db_version', '' );

        if ( $version_db === $version_actual ) {
            return; // Tablas ya creadas en esta versión
        }

        global $wpdb;

        // Verificar si las tablas existen
        $tabla_existe = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->tabla_tests
            )
        );

        if ( ! $tabla_existe ) {
            $this->crear_tablas();
        }

        // Marcar como creadas para no verificar de nuevo
        update_option( 'vbp_ab_testing_db_version', $version_actual, true );
    }

    /**
     * Crea las tablas necesarias
     */
    public function crear_tablas() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_tests = "CREATE TABLE IF NOT EXISTS {$this->tabla_tests} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            element_id varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            status enum('draft','running','paused','completed') DEFAULT 'draft',
            goal_type enum('click','view','conversion','scroll') DEFAULT 'click',
            goal_selector varchar(255) DEFAULT '',
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            winner_variant_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY element_id (element_id),
            KEY status (status)
        ) $charset_collate;";

        $sql_variantes = "CREATE TABLE IF NOT EXISTS {$this->tabla_variantes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            test_id bigint(20) unsigned NOT NULL,
            name varchar(100) NOT NULL,
            element_data longtext NOT NULL,
            traffic_weight int(11) DEFAULT 50,
            views int(11) DEFAULT 0,
            conversions int(11) DEFAULT 0,
            is_control tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id)
        ) $charset_collate;";

        $sql_conversiones = "CREATE TABLE IF NOT EXISTS {$this->tabla_conversiones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            variant_id bigint(20) unsigned NOT NULL,
            visitor_id varchar(100) NOT NULL,
            conversion_type varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY variant_id (variant_id),
            KEY visitor_id (visitor_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_tests );
        dbDelta( $sql_variantes );
        dbDelta( $sql_conversiones );
    }

    /**
     * Registra las rutas REST API
     */
    public function registrar_rutas_rest() {
        $namespace = 'flavor-vbp/v1';

        // Tests
        register_rest_route(
            $namespace,
            '/ab-tests',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'listar_tests' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'crear_test' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
            )
        );

        // Test específico
        register_rest_route(
            $namespace,
            '/ab-tests/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'obtener_test' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'actualizar_test' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'eliminar_test' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
            )
        );

        // Estadísticas de test
        register_rest_route(
            $namespace,
            '/ab-tests/(?P<id>\d+)/stats',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_estadisticas' ),
                'permission_callback' => array( $this, 'verificar_permiso' ),
            )
        );

        // Variantes
        register_rest_route(
            $namespace,
            '/ab-tests/(?P<test_id>\d+)/variants',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'listar_variantes' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'crear_variante' ),
                    'permission_callback' => array( $this, 'verificar_permiso' ),
                ),
            )
        );

        // Tracking (público)
        register_rest_route(
            $namespace,
            '/ab-track',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'registrar_evento' ),
                'permission_callback' => '__return_true',
            )
        );

        // Obtener variante para visitor (público)
        register_rest_route(
            $namespace,
            '/ab-variant/(?P<test_id>\d+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'obtener_variante_visitor' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Verifica permiso
     *
     * @return bool
     */
    public function verificar_permiso() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Lista todos los tests
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function listar_tests( $request ) {
        global $wpdb;

        $post_id = $request->get_param( 'post_id' );
        $where   = $post_id ? $wpdb->prepare( 'WHERE post_id = %d', $post_id ) : '';

        $tests = $wpdb->get_results(
            "SELECT * FROM {$this->tabla_tests} $where ORDER BY created_at DESC"
        );

        $resultado = array();
        foreach ( $tests as $test ) {
            $variantes = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, name, views, conversions, is_control FROM {$this->tabla_variantes} WHERE test_id = %d",
                    $test->id
                )
            );

            $resultado[] = array(
                'id'          => (int) $test->id,
                'postId'      => (int) $test->post_id,
                'elementId'   => $test->element_id,
                'name'        => $test->name,
                'status'      => $test->status,
                'goalType'    => $test->goal_type,
                'startDate'   => $test->start_date,
                'endDate'     => $test->end_date,
                'variants'    => array_map( function( $v ) {
                    return array(
                        'id'          => (int) $v->id,
                        'name'        => $v->name,
                        'views'       => (int) $v->views,
                        'conversions' => (int) $v->conversions,
                        'isControl'   => (bool) $v->is_control,
                        'rate'        => $v->views > 0 ? round( ( $v->conversions / $v->views ) * 100, 2 ) : 0,
                    );
                }, $variantes ),
                'createdAt'   => $test->created_at,
            );
        }

        return new WP_REST_Response( $resultado, 200 );
    }

    /**
     * Crea un nuevo test
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function crear_test( $request ) {
        global $wpdb;

        $post_id    = absint( $request->get_param( 'postId' ) );
        $element_id = sanitize_text_field( $request->get_param( 'elementId' ) );
        $name       = sanitize_text_field( $request->get_param( 'name' ) );
        $goal_type  = sanitize_text_field( $request->get_param( 'goalType' ) ) ?: 'click';

        if ( ! $post_id || ! $element_id ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Post ID y Element ID son requeridos', 'flavor-chat-ia' ) ),
                400
            );
        }

        $wpdb->insert(
            $this->tabla_tests,
            array(
                'post_id'    => $post_id,
                'element_id' => $element_id,
                'name'       => $name ?: __( 'Test A/B', 'flavor-chat-ia' ),
                'goal_type'  => $goal_type,
                'status'     => 'draft',
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );

        $test_id = $wpdb->insert_id;

        // Crear variante de control
        $element_data = $request->get_param( 'elementData' );
        $wpdb->insert(
            $this->tabla_variantes,
            array(
                'test_id'        => $test_id,
                'name'           => 'Control (A)',
                'element_data'   => wp_json_encode( $element_data ),
                'traffic_weight' => 50,
                'is_control'     => 1,
            ),
            array( '%d', '%s', '%s', '%d', '%d' )
        );

        return new WP_REST_Response(
            array(
                'id'      => $test_id,
                'message' => __( 'Test A/B creado', 'flavor-chat-ia' ),
            ),
            201
        );
    }

    /**
     * Obtiene un test específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_test( $request ) {
        global $wpdb;

        $test_id = absint( $request->get_param( 'id' ) );
        $test    = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->tabla_tests} WHERE id = %d", $test_id )
        );

        if ( ! $test ) {
            return new WP_REST_Response(
                array( 'error' => __( 'Test no encontrado', 'flavor-chat-ia' ) ),
                404
            );
        }

        $variantes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_variantes} WHERE test_id = %d",
                $test_id
            )
        );

        return new WP_REST_Response(
            array(
                'id'           => (int) $test->id,
                'postId'       => (int) $test->post_id,
                'elementId'    => $test->element_id,
                'name'         => $test->name,
                'status'       => $test->status,
                'goalType'     => $test->goal_type,
                'goalSelector' => $test->goal_selector,
                'startDate'    => $test->start_date,
                'endDate'      => $test->end_date,
                'variants'     => array_map( function( $v ) {
                    return array(
                        'id'            => (int) $v->id,
                        'name'          => $v->name,
                        'elementData'   => json_decode( $v->element_data, true ),
                        'trafficWeight' => (int) $v->traffic_weight,
                        'views'         => (int) $v->views,
                        'conversions'   => (int) $v->conversions,
                        'isControl'     => (bool) $v->is_control,
                    );
                }, $variantes ),
            ),
            200
        );
    }

    /**
     * Actualiza un test
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function actualizar_test( $request ) {
        global $wpdb;

        $test_id = absint( $request->get_param( 'id' ) );

        $datos = array();
        $tipos = array();

        if ( $request->has_param( 'name' ) ) {
            $datos['name'] = sanitize_text_field( $request->get_param( 'name' ) );
            $tipos[]       = '%s';
        }

        if ( $request->has_param( 'status' ) ) {
            $status = sanitize_text_field( $request->get_param( 'status' ) );
            if ( in_array( $status, array( 'draft', 'running', 'paused', 'completed' ), true ) ) {
                $datos['status'] = $status;
                $tipos[]         = '%s';

                // Si se inicia, establecer fecha de inicio
                if ( 'running' === $status ) {
                    $datos['start_date'] = current_time( 'mysql' );
                    $tipos[]             = '%s';
                }
            }
        }

        if ( $request->has_param( 'goalType' ) ) {
            $datos['goal_type'] = sanitize_text_field( $request->get_param( 'goalType' ) );
            $tipos[]            = '%s';
        }

        if ( $request->has_param( 'goalSelector' ) ) {
            $datos['goal_selector'] = sanitize_text_field( $request->get_param( 'goalSelector' ) );
            $tipos[]                = '%s';
        }

        if ( ! empty( $datos ) ) {
            $wpdb->update( $this->tabla_tests, $datos, array( 'id' => $test_id ), $tipos, array( '%d' ) );
        }

        return new WP_REST_Response(
            array( 'message' => __( 'Test actualizado', 'flavor-chat-ia' ) ),
            200
        );
    }

    /**
     * Elimina un test
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function eliminar_test( $request ) {
        global $wpdb;

        $test_id = absint( $request->get_param( 'id' ) );

        // Eliminar conversiones de las variantes
        $variantes = $wpdb->get_col(
            $wpdb->prepare( "SELECT id FROM {$this->tabla_variantes} WHERE test_id = %d", $test_id )
        );

        if ( ! empty( $variantes ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $variantes ), '%d' ) );
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$this->tabla_conversiones} WHERE variant_id IN ($placeholders)",
                    $variantes
                )
            );
        }

        // Eliminar variantes
        $wpdb->delete( $this->tabla_variantes, array( 'test_id' => $test_id ), array( '%d' ) );

        // Eliminar test
        $wpdb->delete( $this->tabla_tests, array( 'id' => $test_id ), array( '%d' ) );

        return new WP_REST_Response(
            array( 'message' => __( 'Test eliminado', 'flavor-chat-ia' ) ),
            200
        );
    }

    /**
     * Obtiene estadísticas de un test
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_estadisticas( $request ) {
        global $wpdb;

        $test_id = absint( $request->get_param( 'id' ) );

        $variantes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_variantes} WHERE test_id = %d",
                $test_id
            )
        );

        $stats = array();
        $total_views = 0;
        $total_conversions = 0;

        foreach ( $variantes as $v ) {
            $rate = $v->views > 0 ? ( $v->conversions / $v->views ) * 100 : 0;
            $total_views += $v->views;
            $total_conversions += $v->conversions;

            $stats[] = array(
                'id'          => (int) $v->id,
                'name'        => $v->name,
                'views'       => (int) $v->views,
                'conversions' => (int) $v->conversions,
                'rate'        => round( $rate, 2 ),
                'isControl'   => (bool) $v->is_control,
            );
        }

        // Calcular ganador (si hay suficientes datos)
        $ganador = null;
        if ( $total_views >= 100 ) {
            usort( $stats, function( $a, $b ) {
                return $b['rate'] <=> $a['rate'];
            });
            if ( ! empty( $stats ) && $stats[0]['rate'] > 0 ) {
                $ganador = $stats[0];
            }
        }

        return new WP_REST_Response(
            array(
                'variants'         => $stats,
                'totalViews'       => $total_views,
                'totalConversions' => $total_conversions,
                'overallRate'      => $total_views > 0 ? round( ( $total_conversions / $total_views ) * 100, 2 ) : 0,
                'winner'           => $ganador,
                'hasEnoughData'    => $total_views >= 100,
            ),
            200
        );
    }

    /**
     * Crea una variante
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function crear_variante( $request ) {
        global $wpdb;

        $test_id      = absint( $request->get_param( 'test_id' ) );
        $name         = sanitize_text_field( $request->get_param( 'name' ) );
        $element_data = $request->get_param( 'elementData' );
        $weight       = absint( $request->get_param( 'trafficWeight' ) ) ?: 50;

        // Contar variantes existentes para nombrar
        $count = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$this->tabla_variantes} WHERE test_id = %d", $test_id )
        );

        $letra = chr( 65 + $count ); // A, B, C...

        $wpdb->insert(
            $this->tabla_variantes,
            array(
                'test_id'        => $test_id,
                'name'           => $name ?: "Variante ($letra)",
                'element_data'   => wp_json_encode( $element_data ),
                'traffic_weight' => $weight,
                'is_control'     => 0,
            ),
            array( '%d', '%s', '%s', '%d', '%d' )
        );

        return new WP_REST_Response(
            array(
                'id'      => $wpdb->insert_id,
                'message' => __( 'Variante creada', 'flavor-chat-ia' ),
            ),
            201
        );
    }

    /**
     * Lista variantes de un test
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function listar_variantes( $request ) {
        global $wpdb;

        $test_id = absint( $request->get_param( 'test_id' ) );

        $variantes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_variantes} WHERE test_id = %d ORDER BY is_control DESC, id ASC",
                $test_id
            )
        );

        return new WP_REST_Response(
            array_map( function( $v ) {
                return array(
                    'id'            => (int) $v->id,
                    'name'          => $v->name,
                    'elementData'   => json_decode( $v->element_data, true ),
                    'trafficWeight' => (int) $v->traffic_weight,
                    'views'         => (int) $v->views,
                    'conversions'   => (int) $v->conversions,
                    'isControl'     => (bool) $v->is_control,
                );
            }, $variantes ),
            200
        );
    }

    /**
     * Registra un evento (view/conversion)
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function registrar_evento( $request ) {
        global $wpdb;

        $variant_id = absint( $request->get_param( 'variantId' ) );
        $event_type = sanitize_text_field( $request->get_param( 'eventType' ) );
        $visitor_id = sanitize_text_field( $request->get_param( 'visitorId' ) );

        if ( ! $variant_id || ! in_array( $event_type, array( 'view', 'conversion' ), true ) ) {
            return new WP_REST_Response( array( 'error' => 'Invalid data' ), 400 );
        }

        // Validar visitor_id (debe ser un hash válido de 32 caracteres o UUID)
        if ( empty( $visitor_id ) || strlen( $visitor_id ) < 16 || strlen( $visitor_id ) > 64 ) {
            return new WP_REST_Response( array( 'error' => 'Invalid visitor' ), 400 );
        }

        // SEGURIDAD: Verificar que la variante pertenece a un test activo con post publicado
        $variante_valida = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT v.id, t.post_id
                 FROM {$this->tabla_variantes} v
                 INNER JOIN {$this->tabla_tests} t ON v.test_id = t.id
                 WHERE v.id = %d AND t.status = 'running'",
                $variant_id
            )
        );

        if ( ! $variante_valida ) {
            return new WP_REST_Response( array( 'error' => 'Invalid variant' ), 404 );
        }

        // SEGURIDAD: Verificar que el post asociado está publicado
        $post_status = get_post_status( $variante_valida->post_id );
        if ( 'publish' !== $post_status ) {
            return new WP_REST_Response( array( 'error' => 'Test not available' ), 404 );
        }

        // Rate limiting básico por IP + visitor_id (máx 10 eventos/minuto)
        $rate_limit_key = 'vbp_ab_rate_' . md5( $this->obtener_ip_cliente() . $visitor_id );
        $rate_count = get_transient( $rate_limit_key );
        if ( false !== $rate_count && (int) $rate_count >= 10 ) {
            return new WP_REST_Response( array( 'error' => 'Rate limit exceeded' ), 429 );
        }
        set_transient( $rate_limit_key, ( (int) $rate_count ) + 1, 60 );

        if ( 'view' === $event_type ) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$this->tabla_variantes} SET views = views + 1 WHERE id = %d",
                    $variant_id
                )
            );
        } else {
            // Verificar que no se haya registrado ya esta conversión
            $existe = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$this->tabla_conversiones} WHERE variant_id = %d AND visitor_id = %s",
                    $variant_id,
                    $visitor_id
                )
            );

            if ( ! $existe ) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$this->tabla_variantes} SET conversions = conversions + 1 WHERE id = %d",
                        $variant_id
                    )
                );

                $wpdb->insert(
                    $this->tabla_conversiones,
                    array(
                        'variant_id'      => $variant_id,
                        'visitor_id'      => $visitor_id,
                        'conversion_type' => 'goal',
                    ),
                    array( '%d', '%s', '%s' )
                );
            }
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * Obtiene la IP del cliente de forma segura
     *
     * @return string
     */
    private function obtener_ip_cliente() {
        $headers = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
                // Si hay múltiples IPs, tomar la primera
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Obtiene la variante a mostrar para un visitante
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function obtener_variante_visitor( $request ) {
        global $wpdb;

        $test_id    = absint( $request->get_param( 'test_id' ) );
        $visitor_id = sanitize_text_field( $request->get_param( 'visitor_id' ) );

        // Validar visitor_id
        if ( empty( $visitor_id ) || strlen( $visitor_id ) < 16 || strlen( $visitor_id ) > 64 ) {
            return new WP_REST_Response( array( 'error' => 'Invalid visitor' ), 400 );
        }

        // Verificar que el test esté activo
        $test = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->tabla_tests} WHERE id = %d AND status = 'running'", $test_id )
        );

        if ( ! $test ) {
            return new WP_REST_Response( array( 'error' => 'Test not active' ), 404 );
        }

        // SEGURIDAD: Verificar que el post asociado está publicado
        $post_status = get_post_status( $test->post_id );
        if ( 'publish' !== $post_status ) {
            return new WP_REST_Response( array( 'error' => 'Test not available' ), 404 );
        }

        // Obtener variantes
        $variantes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_variantes} WHERE test_id = %d",
                $test_id
            )
        );

        if ( empty( $variantes ) ) {
            return new WP_REST_Response( array( 'error' => 'No variants' ), 404 );
        }

        // Seleccionar variante basada en visitor_id (consistente)
        $hash   = crc32( $visitor_id . $test_id );
        $total  = array_sum( array_column( $variantes, 'traffic_weight' ) );

        // Evitar división por cero
        if ( $total <= 0 ) {
            $total = count( $variantes );
        }

        $random = abs( $hash ) % $total;

        $acumulado = 0;
        $seleccionada = $variantes[0];

        foreach ( $variantes as $v ) {
            $acumulado += max( 1, (int) $v->traffic_weight );
            if ( $random < $acumulado ) {
                $seleccionada = $v;
                break;
            }
        }

        // SEGURIDAD: Solo devolver datos mínimos necesarios para aplicar la variante
        // No exponer element_data completo - solo estilos y propiedades visuales
        $element_data = json_decode( $seleccionada->element_data, true );
        $safe_data = $this->filtrar_datos_variante_publicos( $element_data );

        return new WP_REST_Response(
            array(
                'variantId'   => (int) $seleccionada->id,
                'elementData' => $safe_data,
                'isControl'   => (bool) $seleccionada->is_control,
            ),
            200
        );
    }

    /**
     * Filtra los datos de variante para exposición pública
     * Solo permite propiedades visuales seguras, no contenido sensible
     *
     * @param array $data Datos del elemento.
     * @return array Datos filtrados.
     */
    private function filtrar_datos_variante_publicos( $data ) {
        if ( ! is_array( $data ) ) {
            return array();
        }

        // Propiedades permitidas para variantes A/B (solo visuales)
        $allowed_keys = array(
            'styles',
            'variant',
            'className',
            'backgroundColor',
            'textColor',
            'fontSize',
            'fontWeight',
            'padding',
            'margin',
            'borderRadius',
            'borderColor',
            'borderWidth',
            'boxShadow',
            'opacity',
            'textAlign',
            'display',
            'flexDirection',
            'justifyContent',
            'alignItems',
            'gap',
            'width',
            'height',
            'maxWidth',
            'minHeight',
        );

        $filtered = array();
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $allowed_keys, true ) ) {
                if ( is_array( $value ) ) {
                    $filtered[ $key ] = $this->filtrar_datos_variante_publicos( $value );
                } else {
                    $filtered[ $key ] = $value;
                }
            }
        }

        return $filtered;
    }

    /**
     * Carga script de tracking en frontend
     */
    public function cargar_tracking_script() {
        if ( is_admin() ) {
            return;
        }

        global $wpdb, $post;

        if ( ! $post ) {
            return;
        }

        // Verificar si hay tests activos para este post
        $tests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tabla_tests} WHERE post_id = %d AND status = 'running'",
                $post->ID
            )
        );

        if ( empty( $tests ) ) {
            return;
        }

        $tests_data = array();
        foreach ( $tests as $test ) {
            $tests_data[] = array(
                'id'           => (int) $test->id,
                'elementId'    => $test->element_id,
                'goalType'     => $test->goal_type,
                'goalSelector' => $test->goal_selector,
            );
        }

        ?>
        <script>
        (function() {
            var VBP_AB = {
                tests: <?php echo wp_json_encode( $tests_data ); ?>,
                restUrl: '<?php echo esc_url( rest_url( 'flavor-vbp/v1/' ) ); ?>',
                nonce: '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
                visitorId: null,

                init: function() {
                    this.visitorId = this.getVisitorId();
                    this.loadVariants();
                },

                getVisitorId: function() {
                    var id = localStorage.getItem('vbp_visitor_id');
                    if (!id) {
                        id = 'v_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                        localStorage.setItem('vbp_visitor_id', id);
                    }
                    return id;
                },

                loadVariants: function() {
                    var self = this;
                    this.tests.forEach(function(test) {
                        self.fetchVariant(test);
                    });
                },

                fetchVariant: function(test) {
                    var self = this;
                    fetch(this.restUrl + 'ab-variant/' + test.id + '?visitor_id=' + this.visitorId)
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.variantId) {
                                self.applyVariant(test, data);
                                self.trackView(data.variantId);
                                self.setupGoalTracking(test, data.variantId);
                            }
                        })
                        .catch(function() {});
                },

                applyVariant: function(test, data) {
                    // Aplicar la variante al elemento
                    var element = document.querySelector('[data-element-id="' + test.elementId + '"]');
                    if (element && data.elementData) {
                        element.setAttribute('data-ab-variant', data.variantId);
                    }
                },

                trackView: function(variantId) {
                    fetch(this.restUrl + 'ab-track', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            variantId: variantId,
                            eventType: 'view',
                            visitorId: this.visitorId
                        })
                    }).catch(function() {});
                },

                setupGoalTracking: function(test, variantId) {
                    var self = this;
                    var tracked = false;

                    if (test.goalType === 'click' && test.goalSelector) {
                        document.addEventListener('click', function(e) {
                            if (!tracked && e.target.closest(test.goalSelector)) {
                                tracked = true;
                                self.trackConversion(variantId);
                            }
                        });
                    }
                },

                trackConversion: function(variantId) {
                    fetch(this.restUrl + 'ab-track', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            variantId: variantId,
                            eventType: 'conversion',
                            visitorId: this.visitorId
                        })
                    }).catch(function() {});
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() { VBP_AB.init(); });
            } else {
                VBP_AB.init();
            }
        })();
        </script>
        <?php
    }
}
