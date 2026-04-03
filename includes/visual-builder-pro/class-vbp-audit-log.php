<?php
/**
 * Visual Builder Pro - Audit Log System
 *
 * Sistema de registro de auditoría para tracking de acciones.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestión de logs de auditoría
 *
 * @since 2.3.0
 */
class Flavor_VBP_Audit_Log {

    /**
     * Nombre de la tabla de logs
     *
     * @var string
     */
    private $table_name;

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Audit_Log|null
     */
    private static $instance = null;

    /**
     * Tipos de acciones registrables
     *
     * @var array
     */
    const ACTION_TYPES = array(
        'page_created'      => 'Página creada',
        'page_updated'      => 'Página actualizada',
        'page_published'    => 'Página publicada',
        'page_unpublished'  => 'Página despublicada',
        'page_deleted'      => 'Página eliminada',
        'page_trashed'      => 'Página movida a papelera',
        'page_restored'     => 'Página restaurada',
        'revision_created'  => 'Revisión creada',
        'revision_restored' => 'Revisión restaurada',
        'element_added'     => 'Elemento añadido',
        'element_updated'   => 'Elemento modificado',
        'element_deleted'   => 'Elemento eliminado',
        'element_moved'     => 'Elemento movido',
        'element_duplicated'=> 'Elemento duplicado',
        'style_changed'     => 'Estilo modificado',
        'settings_changed'  => 'Configuración cambiada',
        'template_applied'  => 'Plantilla aplicada',
        'template_saved'    => 'Plantilla guardada',
        'export_created'    => 'Exportación realizada',
        'import_completed'  => 'Importación completada',
        'collaboration_joined'  => 'Usuario se unió',
        'collaboration_left'    => 'Usuario salió',
        'comment_added'     => 'Comentario añadido',
        'comment_resolved'  => 'Comentario resuelto',
        'ab_test_created'   => 'Test A/B creado',
        'ab_test_ended'     => 'Test A/B finalizado',
        'popup_created'     => 'Popup creado',
        'popup_activated'   => 'Popup activado',
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Audit_Log
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vbp_audit_log';

        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Registrar endpoints REST
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Hooks para capturar acciones
        add_action( 'save_post', array( $this, 'on_post_save' ), 10, 3 );
        add_action( 'before_delete_post', array( $this, 'on_post_delete' ) );
        add_action( 'wp_trash_post', array( $this, 'on_post_trash' ) );
        add_action( 'untrash_post', array( $this, 'on_post_untrash' ) );
        add_action( 'transition_post_status', array( $this, 'on_status_change' ), 10, 3 );

        // Admin menu para visualizar logs
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

        // Limpiar logs antiguos periódicamente
        add_action( 'vbp_cleanup_audit_logs', array( $this, 'cleanup_old_logs' ) );

        // Programar limpieza si no existe
        if ( ! wp_next_scheduled( 'vbp_cleanup_audit_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'vbp_cleanup_audit_logs' );
        }
    }

    /**
     * Crear tabla de logs en activación
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vbp_audit_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned NOT NULL,
            action_type varchar(50) NOT NULL,
            action_label varchar(255) DEFAULT NULL,
            details longtext DEFAULT NULL,
            element_id varchar(100) DEFAULT NULL,
            element_type varchar(50) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            session_id varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY created_at (created_at),
            KEY session_id (session_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Registra rutas REST
     */
    public function register_rest_routes() {
        register_rest_route(
            'flavor-vbp/v1',
            '/audit-log',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_logs' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
                'args'                => array(
                    'post_id'     => array(
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'user_id'     => array(
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'action_type' => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'date_from'   => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'date_to'     => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'per_page'    => array(
                        'type'              => 'integer',
                        'default'           => 50,
                        'sanitize_callback' => 'absint',
                    ),
                    'page'        => array(
                        'type'              => 'integer',
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        register_rest_route(
            'flavor-vbp/v1',
            '/audit-log/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_log_entry' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
            )
        );

        register_rest_route(
            'flavor-vbp/v1',
            '/audit-log/stats',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_stats' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
            )
        );

        register_rest_route(
            'flavor-vbp/v1',
            '/audit-log/export',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'export_logs' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
            )
        );
    }

    /**
     * Verifica permisos de administrador
     *
     * @return bool
     */
    public function check_admin_permission() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Registra una acción en el log
     *
     * @param string $action_type Tipo de acción.
     * @param array  $data        Datos adicionales.
     * @return int|false ID del log creado o false en error.
     */
    public function log( $action_type, $data = array() ) {
        global $wpdb;

        $user_id = get_current_user_id();
        if ( ! $user_id && isset( $data['user_id'] ) ) {
            $user_id = absint( $data['user_id'] );
        }

        $log_data = array(
            'post_id'      => isset( $data['post_id'] ) ? absint( $data['post_id'] ) : null,
            'user_id'      => $user_id,
            'action_type'  => sanitize_key( $action_type ),
            'action_label' => isset( self::ACTION_TYPES[ $action_type ] ) ? self::ACTION_TYPES[ $action_type ] : $action_type,
            'details'      => isset( $data['details'] ) ? wp_json_encode( $data['details'] ) : null,
            'element_id'   => isset( $data['element_id'] ) ? sanitize_text_field( $data['element_id'] ) : null,
            'element_type' => isset( $data['element_type'] ) ? sanitize_text_field( $data['element_type'] ) : null,
            'ip_address'   => $this->get_client_ip(),
            'user_agent'   => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : null,
            'session_id'   => isset( $data['session_id'] ) ? sanitize_text_field( $data['session_id'] ) : $this->get_session_id(),
            'created_at'   => current_time( 'mysql' ),
        );

        $result = $wpdb->insert( $this->table_name, $log_data );

        if ( false === $result ) {
            error_log( '[VBP Audit] Error inserting log: ' . $wpdb->last_error );
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtiene logs filtrados
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function get_logs( $request ) {
        global $wpdb;

        $per_page = min( $request->get_param( 'per_page' ), 100 );
        $page     = max( 1, $request->get_param( 'page' ) );
        $offset   = ( $page - 1 ) * $per_page;

        $where_clauses = array( '1=1' );
        $where_values  = array();

        if ( $request->get_param( 'post_id' ) ) {
            $where_clauses[] = 'post_id = %d';
            $where_values[]  = $request->get_param( 'post_id' );
        }

        if ( $request->get_param( 'user_id' ) ) {
            $where_clauses[] = 'user_id = %d';
            $where_values[]  = $request->get_param( 'user_id' );
        }

        if ( $request->get_param( 'action_type' ) ) {
            $where_clauses[] = 'action_type = %s';
            $where_values[]  = $request->get_param( 'action_type' );
        }

        if ( $request->get_param( 'date_from' ) ) {
            $where_clauses[] = 'created_at >= %s';
            $where_values[]  = $request->get_param( 'date_from' ) . ' 00:00:00';
        }

        if ( $request->get_param( 'date_to' ) ) {
            $where_clauses[] = 'created_at <= %s';
            $where_values[]  = $request->get_param( 'date_to' ) . ' 23:59:59';
        }

        $where_sql = implode( ' AND ', $where_clauses );

        // Contar total
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";
        if ( ! empty( $where_values ) ) {
            $count_sql = $wpdb->prepare( $count_sql, $where_values );
        }
        $total = $wpdb->get_var( $count_sql );

        // Obtener registros
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;

        $results = $wpdb->get_results(
            $wpdb->prepare( $query, $where_values ),
            ARRAY_A
        );

        // Enriquecer datos
        $logs = array_map( array( $this, 'enrich_log_entry' ), $results );

        return new WP_REST_Response(
            array(
                'success' => true,
                'logs'    => $logs,
                'total'   => (int) $total,
                'pages'   => ceil( $total / $per_page ),
                'page'    => $page,
            ),
            200
        );
    }

    /**
     * Obtiene una entrada específica del log
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function get_log_entry( $request ) {
        global $wpdb;

        $id = absint( $request->get_param( 'id' ) );

        $entry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        if ( ! $entry ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Entrada no encontrada',
                ),
                404
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'log'     => $this->enrich_log_entry( $entry ),
            ),
            200
        );
    }

    /**
     * Obtiene estadísticas del audit log
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function get_stats( $request ) {
        global $wpdb;

        // Total de registros
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );

        // Registros por tipo de acción
        $by_action = $wpdb->get_results(
            "SELECT action_type, COUNT(*) as count FROM {$this->table_name} GROUP BY action_type ORDER BY count DESC",
            ARRAY_A
        );

        // Registros por usuario (top 10)
        $by_user = $wpdb->get_results(
            "SELECT user_id, COUNT(*) as count FROM {$this->table_name} GROUP BY user_id ORDER BY count DESC LIMIT 10",
            ARRAY_A
        );

        // Enriquecer usuarios
        foreach ( $by_user as &$item ) {
            $user = get_userdata( $item['user_id'] );
            $item['user_name']   = $user ? $user->display_name : 'Usuario eliminado';
            $item['user_avatar'] = $user ? get_avatar_url( $item['user_id'], array( 'size' => 32 ) ) : '';
        }

        // Actividad últimos 7 días
        $last_7_days = $wpdb->get_results(
            "SELECT DATE(created_at) as date, COUNT(*) as count
             FROM {$this->table_name}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            ARRAY_A
        );

        // Páginas más editadas
        $top_pages = $wpdb->get_results(
            "SELECT post_id, COUNT(*) as count
             FROM {$this->table_name}
             WHERE post_id IS NOT NULL
             GROUP BY post_id
             ORDER BY count DESC
             LIMIT 10",
            ARRAY_A
        );

        foreach ( $top_pages as &$item ) {
            $post = get_post( $item['post_id'] );
            $item['post_title'] = $post ? $post->post_title : 'Página eliminada';
            $item['post_url']   = $post ? get_edit_post_link( $item['post_id'], 'raw' ) : '';
        }

        return new WP_REST_Response(
            array(
                'success'     => true,
                'total'       => (int) $total,
                'by_action'   => $by_action,
                'by_user'     => $by_user,
                'last_7_days' => $last_7_days,
                'top_pages'   => $top_pages,
            ),
            200
        );
    }

    /**
     * Exporta logs como CSV
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function export_logs( $request ) {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT 10000",
            ARRAY_A
        );

        $csv_data = array();
        $csv_data[] = array(
            'ID',
            'Fecha',
            'Usuario',
            'Acción',
            'Página',
            'Elemento',
            'IP',
            'Detalles',
        );

        foreach ( $results as $row ) {
            $user = get_userdata( $row['user_id'] );
            $post = $row['post_id'] ? get_post( $row['post_id'] ) : null;

            $csv_data[] = array(
                $row['id'],
                $row['created_at'],
                $user ? $user->display_name : 'Usuario ' . $row['user_id'],
                $row['action_label'],
                $post ? $post->post_title : '',
                $row['element_id'] ? $row['element_type'] . ':' . $row['element_id'] : '',
                $row['ip_address'],
                $row['details'],
            );
        }

        return new WP_REST_Response(
            array(
                'success'  => true,
                'data'     => $csv_data,
                'filename' => 'vbp-audit-log-' . gmdate( 'Y-m-d' ) . '.csv',
            ),
            200
        );
    }

    /**
     * Enriquece una entrada del log con datos adicionales
     *
     * @param array $entry Entrada del log.
     * @return array
     */
    private function enrich_log_entry( $entry ) {
        $user = get_userdata( $entry['user_id'] );
        $post = $entry['post_id'] ? get_post( $entry['post_id'] ) : null;

        $entry['user'] = array(
            'id'     => $entry['user_id'],
            'name'   => $user ? $user->display_name : 'Usuario eliminado',
            'email'  => $user ? $user->user_email : '',
            'avatar' => $user ? get_avatar_url( $entry['user_id'], array( 'size' => 32 ) ) : '',
        );

        $entry['post'] = $post ? array(
            'id'    => $post->ID,
            'title' => $post->post_title,
            'url'   => get_edit_post_link( $post->ID, 'raw' ),
        ) : null;

        if ( $entry['details'] ) {
            $entry['details'] = json_decode( $entry['details'], true );
        }

        // Tiempo relativo
        $entry['time_ago'] = human_time_diff( strtotime( $entry['created_at'] ), current_time( 'timestamp' ) );

        return $entry;
    }

    /**
     * Obtiene la IP del cliente
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                // Si hay múltiples IPs (X-Forwarded-For), tomar la primera
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
     * Obtiene o genera un ID de sesión
     *
     * @return string
     */
    private function get_session_id() {
        if ( ! session_id() && ! headers_sent() ) {
            session_start();
        }
        return session_id() ? session_id() : wp_generate_uuid4();
    }

    /**
     * Hook: Al guardar un post
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @param bool    $update  Si es actualización.
     */
    public function on_post_save( $post_id, $post, $update ) {
        if ( ! in_array( $post->post_type, array( 'page', 'flavor_landing' ), true ) ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        $action_type = $update ? 'page_updated' : 'page_created';

        $this->log( $action_type, array(
            'post_id' => $post_id,
            'details' => array(
                'post_title'  => $post->post_title,
                'post_status' => $post->post_status,
            ),
        ) );
    }

    /**
     * Hook: Al cambiar estado del post
     *
     * @param string  $new_status Nuevo estado.
     * @param string  $old_status Estado anterior.
     * @param WP_Post $post       Post.
     */
    public function on_status_change( $new_status, $old_status, $post ) {
        if ( ! in_array( $post->post_type, array( 'page', 'flavor_landing' ), true ) ) {
            return;
        }

        if ( $old_status === $new_status ) {
            return;
        }

        if ( 'publish' === $new_status && 'publish' !== $old_status ) {
            $this->log( 'page_published', array(
                'post_id' => $post->ID,
                'details' => array(
                    'post_title'  => $post->post_title,
                    'from_status' => $old_status,
                ),
            ) );
        } elseif ( 'publish' === $old_status && 'publish' !== $new_status ) {
            $this->log( 'page_unpublished', array(
                'post_id' => $post->ID,
                'details' => array(
                    'post_title' => $post->post_title,
                    'to_status'  => $new_status,
                ),
            ) );
        }
    }

    /**
     * Hook: Al eliminar post
     *
     * @param int $post_id Post ID.
     */
    public function on_post_delete( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || ! in_array( $post->post_type, array( 'page', 'flavor_landing' ), true ) ) {
            return;
        }

        $this->log( 'page_deleted', array(
            'post_id' => $post_id,
            'details' => array(
                'post_title' => $post->post_title,
            ),
        ) );
    }

    /**
     * Hook: Al mover a papelera
     *
     * @param int $post_id Post ID.
     */
    public function on_post_trash( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || ! in_array( $post->post_type, array( 'page', 'flavor_landing' ), true ) ) {
            return;
        }

        $this->log( 'page_trashed', array(
            'post_id' => $post_id,
            'details' => array(
                'post_title' => $post->post_title,
            ),
        ) );
    }

    /**
     * Hook: Al restaurar de papelera
     *
     * @param int $post_id Post ID.
     */
    public function on_post_untrash( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || ! in_array( $post->post_type, array( 'page', 'flavor_landing' ), true ) ) {
            return;
        }

        $this->log( 'page_restored', array(
            'post_id' => $post_id,
            'details' => array(
                'post_title' => $post->post_title,
            ),
        ) );
    }

    /**
     * Limpia logs antiguos (más de 90 días)
     */
    public function cleanup_old_logs() {
        global $wpdb;

        $retention_days = apply_filters( 'vbp_audit_log_retention_days', 90 );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_days
            )
        );
    }

    /**
     * Añade menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-chat-ia',
            __( 'Audit Log VBP', 'flavor-chat-ia' ),
            __( 'Audit Log', 'flavor-chat-ia' ),
            'manage_options',
            'vbp-audit-log',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Renderiza la página de administración
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Visual Builder Pro - Audit Log', 'flavor-chat-ia' ); ?></h1>
            <div id="vbp-audit-log-app"></div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // La app React/Vue se montará aquí
            // Por ahora mostramos tabla básica
            fetch('<?php echo esc_url( rest_url( 'flavor-vbp/v1/audit-log' ) ); ?>', {
                headers: {
                    'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.logs) {
                    var html = '<table class="wp-list-table widefat fixed striped">';
                    html += '<thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Página</th><th>IP</th></tr></thead>';
                    html += '<tbody>';
                    data.logs.forEach(function(log) {
                        html += '<tr>';
                        html += '<td>' + log.created_at + '</td>';
                        html += '<td>' + (log.user ? log.user.name : '-') + '</td>';
                        html += '<td>' + log.action_label + '</td>';
                        html += '<td>' + (log.post ? log.post.title : '-') + '</td>';
                        html += '<td>' + (log.ip_address || '-') + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                    html += '<p>Total: ' + data.total + ' registros</p>';
                    document.getElementById('vbp-audit-log-app').innerHTML = html;
                }
            });
        });
        </script>
        <?php
    }
}

/**
 * Helper function para registrar logs fácilmente
 *
 * @param string $action_type Tipo de acción.
 * @param array  $data        Datos adicionales.
 * @return int|false
 */
function vbp_audit_log( $action_type, $data = array() ) {
    return Flavor_VBP_Audit_Log::get_instance()->log( $action_type, $data );
}
