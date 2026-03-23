<?php
/**
 * Panel de Usuarios de la App
 *
 * Gestión de usuarios de la aplicación móvil,
 * dispositivos registrados, sesiones y estadísticas.
 *
 * @package Flavor_Chat_IA
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_App_Users_Panel {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Nombre de la tabla de dispositivos
     */
    private $devices_table;

    /**
     * Nombre de la tabla de sesiones
     */
    private $sessions_table;

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;

        $this->devices_table = $wpdb->prefix . 'flavor_app_devices';
        $this->sessions_table = $wpdb->prefix . 'flavor_app_sessions';

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 25 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_flavor_get_app_users', array( $this, 'ajax_get_users' ) );
        add_action( 'wp_ajax_flavor_get_user_devices', array( $this, 'ajax_get_user_devices' ) );
        add_action( 'wp_ajax_flavor_revoke_device', array( $this, 'ajax_revoke_device' ) );
        add_action( 'wp_ajax_flavor_send_push_notification', array( $this, 'ajax_send_push' ) );
        add_action( 'wp_ajax_flavor_export_app_users', array( $this, 'ajax_export_users' ) );

        // REST API para la app
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-dashboard',
            __( 'Usuarios App', 'flavor-chat-ia' ),
            __( 'Usuarios App', 'flavor-chat-ia' ),
            'manage_options',
            'flavor-app-users',
            array( $this, 'render_page' )
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets( $hook ) {
        if ( 'flavor_page_flavor-app-users' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'flavor-app-users',
            FLAVOR_CHAT_IA_URL . 'admin/css/app-users.css',
            array(),
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-app-users',
            FLAVOR_CHAT_IA_URL . 'admin/js/app-users.js',
            array( 'jquery', 'wp-util' ),
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script( 'flavor-app-users', 'flavorAppUsers', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'flavor_app_users' ),
            'i18n'    => array(
                'confirm_revoke' => __( '¿Revocar acceso de este dispositivo?', 'flavor-chat-ia' ),
                'confirm_push'   => __( '¿Enviar notificación push?', 'flavor-chat-ia' ),
                'loading'        => __( 'Cargando...', 'flavor-chat-ia' ),
                'no_results'     => __( 'No se encontraron usuarios', 'flavor-chat-ia' ),
            ),
        ) );
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route( 'flavor-app/v2', '/devices/register', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_register_device' ),
            'permission_callback' => array( $this, 'check_app_auth' ),
        ) );

        register_rest_route( 'flavor-app/v2', '/devices/(?P<device_id>[a-zA-Z0-9_-]+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'rest_unregister_device' ),
            'permission_callback' => array( $this, 'check_app_auth' ),
        ) );

        register_rest_route( 'flavor-app/v2', '/sessions/start', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_start_session' ),
            'permission_callback' => array( $this, 'check_app_auth' ),
        ) );

        register_rest_route( 'flavor-app/v2', '/sessions/end', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_end_session' ),
            'permission_callback' => array( $this, 'check_app_auth' ),
        ) );
    }

    /**
     * Verificar autenticación de la app
     */
    public function check_app_auth( $request ) {
        $user_id = get_current_user_id();
        return $user_id > 0;
    }

    /**
     * Renderizar página
     */
    public function render_page() {
        $stats = $this->get_stats();
        ?>
        <div class="wrap flavor-app-users">
            <div class="page-header">
                <h1>
                    <span class="dashicons dashicons-smartphone"></span>
                    <?php esc_html_e( 'Usuarios de la App', 'flavor-chat-ia' ); ?>
                </h1>
                <div class="header-actions">
                    <button type="button" class="button" id="export-users">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e( 'Exportar', 'flavor-chat-ia' ); ?>
                    </button>
                    <button type="button" class="button button-primary" id="send-broadcast">
                        <span class="dashicons dashicons-megaphone"></span>
                        <?php esc_html_e( 'Enviar Notificación', 'flavor-chat-ia' ); ?>
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="users-stats">
                <div class="stat-card total">
                    <div class="stat-icon"><span class="dashicons dashicons-groups"></span></div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo esc_html( $stats['total_users'] ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Usuarios Totales', 'flavor-chat-ia' ); ?></div>
                    </div>
                </div>
                <div class="stat-card active">
                    <div class="stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo esc_html( $stats['active_users'] ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Activos (7 días)', 'flavor-chat-ia' ); ?></div>
                    </div>
                </div>
                <div class="stat-card devices">
                    <div class="stat-icon"><span class="dashicons dashicons-smartphone"></span></div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo esc_html( $stats['total_devices'] ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Dispositivos', 'flavor-chat-ia' ); ?></div>
                    </div>
                </div>
                <div class="stat-card sessions">
                    <div class="stat-icon"><span class="dashicons dashicons-clock"></span></div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo esc_html( $stats['avg_session_time'] ); ?></div>
                        <div class="stat-label"><?php esc_html_e( 'Tiempo Promedio', 'flavor-chat-ia' ); ?></div>
                    </div>
                </div>
            </div>

            <!-- Platform Distribution -->
            <div class="platform-distribution">
                <div class="platform-bar">
                    <div class="platform-segment ios" style="width: <?php echo esc_attr( $stats['ios_percentage'] ); ?>%">
                        <span class="platform-label">iOS <?php echo esc_html( $stats['ios_percentage'] ); ?>%</span>
                    </div>
                    <div class="platform-segment android" style="width: <?php echo esc_attr( $stats['android_percentage'] ); ?>%">
                        <span class="platform-label">Android <?php echo esc_html( $stats['android_percentage'] ); ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="users-toolbar">
                <div class="search-box">
                    <input type="search" id="search-users" placeholder="<?php esc_attr_e( 'Buscar usuarios...', 'flavor-chat-ia' ); ?>">
                </div>
                <div class="filter-group">
                    <select id="filter-platform">
                        <option value=""><?php esc_html_e( 'Todas las plataformas', 'flavor-chat-ia' ); ?></option>
                        <option value="ios">iOS</option>
                        <option value="android">Android</option>
                    </select>
                    <select id="filter-status">
                        <option value=""><?php esc_html_e( 'Todos los estados', 'flavor-chat-ia' ); ?></option>
                        <option value="active"><?php esc_html_e( 'Activos', 'flavor-chat-ia' ); ?></option>
                        <option value="inactive"><?php esc_html_e( 'Inactivos', 'flavor-chat-ia' ); ?></option>
                    </select>
                </div>
            </div>

            <!-- Users Table -->
            <div class="users-table-container">
                <table class="wp-list-table widefat fixed striped" id="users-table">
                    <thead>
                        <tr>
                            <th class="column-avatar"></th>
                            <th class="column-user"><?php esc_html_e( 'Usuario', 'flavor-chat-ia' ); ?></th>
                            <th class="column-devices"><?php esc_html_e( 'Dispositivos', 'flavor-chat-ia' ); ?></th>
                            <th class="column-platform"><?php esc_html_e( 'Plataforma', 'flavor-chat-ia' ); ?></th>
                            <th class="column-last-seen"><?php esc_html_e( 'Última sesión', 'flavor-chat-ia' ); ?></th>
                            <th class="column-sessions"><?php esc_html_e( 'Sesiones', 'flavor-chat-ia' ); ?></th>
                            <th class="column-actions"><?php esc_html_e( 'Acciones', 'flavor-chat-ia' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="users-list">
                        <tr class="loading-row">
                            <td colspan="7">
                                <span class="spinner is-active"></span>
                                <?php esc_html_e( 'Cargando usuarios...', 'flavor-chat-ia' ); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num"></span>
                        <span class="pagination-links">
                            <button class="button prev-page" disabled>&lsaquo;</button>
                            <span class="paging-input">
                                <span class="current-page">1</span>
                                /
                                <span class="total-pages">1</span>
                            </span>
                            <button class="button next-page" disabled>&rsaquo;</button>
                        </span>
                    </div>
                </div>
            </div>

            <!-- User Detail Modal -->
            <div class="user-modal-overlay" id="user-modal">
                <div class="user-modal">
                    <div class="user-modal-header">
                        <h3><?php esc_html_e( 'Detalles del Usuario', 'flavor-chat-ia' ); ?></h3>
                        <button type="button" class="user-modal-close">&times;</button>
                    </div>
                    <div class="user-modal-body">
                        <!-- Content loaded via AJAX -->
                    </div>
                </div>
            </div>

            <!-- Broadcast Modal -->
            <div class="user-modal-overlay" id="broadcast-modal">
                <div class="user-modal">
                    <div class="user-modal-header">
                        <h3><?php esc_html_e( 'Enviar Notificación Push', 'flavor-chat-ia' ); ?></h3>
                        <button type="button" class="user-modal-close">&times;</button>
                    </div>
                    <div class="user-modal-body">
                        <form id="broadcast-form">
                            <div class="form-group">
                                <label for="push-title"><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></label>
                                <input type="text" id="push-title" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="push-body"><?php esc_html_e( 'Mensaje', 'flavor-chat-ia' ); ?></label>
                                <textarea id="push-body" name="body" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="push-target"><?php esc_html_e( 'Destinatarios', 'flavor-chat-ia' ); ?></label>
                                <select id="push-target" name="target">
                                    <option value="all"><?php esc_html_e( 'Todos los usuarios', 'flavor-chat-ia' ); ?></option>
                                    <option value="ios"><?php esc_html_e( 'Solo iOS', 'flavor-chat-ia' ); ?></option>
                                    <option value="android"><?php esc_html_e( 'Solo Android', 'flavor-chat-ia' ); ?></option>
                                    <option value="active"><?php esc_html_e( 'Usuarios activos (7 días)', 'flavor-chat-ia' ); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="push-data"><?php esc_html_e( 'Datos adicionales (JSON)', 'flavor-chat-ia' ); ?></label>
                                <textarea id="push-data" name="data" rows="2" placeholder='{"action": "open_screen", "screen": "eventos"}'></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="user-modal-footer">
                        <button type="button" class="button btn-cancel"><?php esc_html_e( 'Cancelar', 'flavor-chat-ia' ); ?></button>
                        <button type="button" class="button button-primary" id="send-push-btn">
                            <span class="dashicons dashicons-megaphone"></span>
                            <?php esc_html_e( 'Enviar', 'flavor-chat-ia' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener estadísticas
     */
    private function get_stats() {
        global $wpdb;

        $stats = array(
            'total_users'        => 0,
            'active_users'       => 0,
            'total_devices'      => 0,
            'avg_session_time'   => '0m',
            'ios_percentage'     => 50,
            'android_percentage' => 50,
        );

        // Total usuarios con dispositivos registrados
        $stats['total_users'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$this->devices_table}"
        );

        if ( ! $stats['total_users'] ) {
            $stats['total_users'] = 0;
        }

        // Usuarios activos (última sesión en 7 días)
        $stats['active_users'] = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$this->sessions_table}
             WHERE started_at >= %s",
            date( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
        ) );

        if ( ! $stats['active_users'] ) {
            $stats['active_users'] = 0;
        }

        // Total dispositivos
        $stats['total_devices'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->devices_table} WHERE status = 'active'"
        );

        if ( ! $stats['total_devices'] ) {
            $stats['total_devices'] = 0;
        }

        // Tiempo promedio de sesión
        $avg_seconds = $wpdb->get_var(
            "SELECT AVG(TIMESTAMPDIFF(SECOND, started_at, ended_at))
             FROM {$this->sessions_table}
             WHERE ended_at IS NOT NULL"
        );

        if ( $avg_seconds ) {
            $minutes = round( $avg_seconds / 60 );
            $stats['avg_session_time'] = $minutes . 'm';
        }

        // Distribución por plataforma
        $platforms = $wpdb->get_results(
            "SELECT platform, COUNT(*) as count FROM {$this->devices_table}
             WHERE status = 'active' GROUP BY platform"
        );

        $total_platforms = 0;
        $ios_count = 0;
        $android_count = 0;

        foreach ( $platforms as $platform ) {
            $total_platforms += $platform->count;
            if ( $platform->platform === 'ios' ) {
                $ios_count = $platform->count;
            } elseif ( $platform->platform === 'android' ) {
                $android_count = $platform->count;
            }
        }

        if ( $total_platforms > 0 ) {
            $stats['ios_percentage'] = round( ( $ios_count / $total_platforms ) * 100 );
            $stats['android_percentage'] = round( ( $android_count / $total_platforms ) * 100 );
        }

        return $stats;
    }

    /**
     * AJAX: Obtener usuarios
     */
    public function ajax_get_users() {
        check_ajax_referer( 'flavor_app_users', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Sin permisos' ) );
        }

        global $wpdb;

        $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = 20;
        $offset = ( $page - 1 ) * $per_page;

        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $platform = isset( $_POST['platform'] ) ? sanitize_text_field( $_POST['platform'] ) : '';
        $status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

        // Build query
        $where = array( '1=1' );
        $params = array();

        if ( $search ) {
            $where[] = "(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)";
            $search_param = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }

        if ( $platform ) {
            $where[] = "d.platform = %s";
            $params[] = $platform;
        }

        if ( $status === 'active' ) {
            $where[] = "d.last_active >= %s";
            $params[] = date( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
        } elseif ( $status === 'inactive' ) {
            $where[] = "(d.last_active < %s OR d.last_active IS NULL)";
            $params[] = date( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
        }

        $where_sql = implode( ' AND ', $where );

        // Count total
        $total_query = "SELECT COUNT(DISTINCT d.user_id)
                        FROM {$this->devices_table} d
                        LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
                        WHERE {$where_sql}";

        if ( $params ) {
            $total = $wpdb->get_var( $wpdb->prepare( $total_query, $params ) );
        } else {
            $total = $wpdb->get_var( $total_query );
        }

        // Get users
        $query = "SELECT DISTINCT d.user_id, u.user_login, u.user_email, u.display_name,
                         COUNT(d.id) as device_count,
                         MAX(d.last_active) as last_seen,
                         GROUP_CONCAT(DISTINCT d.platform) as platforms
                  FROM {$this->devices_table} d
                  LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
                  WHERE {$where_sql}
                  GROUP BY d.user_id
                  ORDER BY last_seen DESC
                  LIMIT %d OFFSET %d";

        $params[] = $per_page;
        $params[] = $offset;

        $users = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

        // Get session counts
        $user_ids = wp_list_pluck( $users, 'user_id' );
        $session_counts = array();

        if ( $user_ids ) {
            $placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
            $sessions = $wpdb->get_results( $wpdb->prepare(
                "SELECT user_id, COUNT(*) as count FROM {$this->sessions_table}
                 WHERE user_id IN ($placeholders) GROUP BY user_id",
                $user_ids
            ) );

            foreach ( $sessions as $s ) {
                $session_counts[ $s->user_id ] = $s->count;
            }
        }

        // Format response
        $formatted_users = array();
        foreach ( $users as $user ) {
            $formatted_users[] = array(
                'id'            => $user->user_id,
                'username'      => $user->user_login,
                'email'         => $user->user_email,
                'display_name'  => $user->display_name,
                'avatar'        => get_avatar_url( $user->user_id, array( 'size' => 40 ) ),
                'device_count'  => intval( $user->device_count ),
                'platforms'     => $user->platforms,
                'last_seen'     => $user->last_seen ? human_time_diff( strtotime( $user->last_seen ) ) . ' ' . __( 'ago', 'flavor-chat-ia' ) : '-',
                'last_seen_raw' => $user->last_seen,
                'session_count' => isset( $session_counts[ $user->user_id ] ) ? $session_counts[ $user->user_id ] : 0,
            );
        }

        wp_send_json_success( array(
            'users'      => $formatted_users,
            'total'      => intval( $total ),
            'page'       => $page,
            'per_page'   => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        ) );
    }

    /**
     * AJAX: Obtener dispositivos de usuario
     */
    public function ajax_get_user_devices() {
        check_ajax_referer( 'flavor_app_users', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Sin permisos' ) );
        }

        global $wpdb;

        $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'ID de usuario requerido' ) );
        }

        $devices = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->devices_table} WHERE user_id = %d ORDER BY last_active DESC",
            $user_id
        ) );

        $sessions = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->sessions_table}
             WHERE user_id = %d ORDER BY started_at DESC LIMIT 10",
            $user_id
        ) );

        $user = get_userdata( $user_id );

        wp_send_json_success( array(
            'user'     => array(
                'id'           => $user->ID,
                'username'     => $user->user_login,
                'email'        => $user->user_email,
                'display_name' => $user->display_name,
                'avatar'       => get_avatar_url( $user_id, array( 'size' => 80 ) ),
                'registered'   => $user->user_registered,
            ),
            'devices'  => $devices,
            'sessions' => $sessions,
        ) );
    }

    /**
     * AJAX: Revocar dispositivo
     */
    public function ajax_revoke_device() {
        check_ajax_referer( 'flavor_app_users', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Sin permisos' ) );
        }

        global $wpdb;

        $device_id = isset( $_POST['device_id'] ) ? sanitize_text_field( $_POST['device_id'] ) : '';

        if ( ! $device_id ) {
            wp_send_json_error( array( 'message' => 'ID de dispositivo requerido' ) );
        }

        $result = $wpdb->update(
            $this->devices_table,
            array( 'status' => 'revoked' ),
            array( 'device_id' => $device_id )
        );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => 'Error al revocar dispositivo' ) );
        }

        wp_send_json_success( array( 'message' => 'Dispositivo revocado' ) );
    }

    /**
     * AJAX: Enviar notificación push
     */
    public function ajax_send_push() {
        check_ajax_referer( 'flavor_app_users', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Sin permisos' ) );
        }

        $title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
        $body = isset( $_POST['body'] ) ? sanitize_textarea_field( $_POST['body'] ) : '';
        $target = isset( $_POST['target'] ) ? sanitize_text_field( $_POST['target'] ) : 'all';
        $data = isset( $_POST['data'] ) ? json_decode( stripslashes( $_POST['data'] ), true ) : array();

        if ( ! $title || ! $body ) {
            wp_send_json_error( array( 'message' => 'Título y mensaje requeridos' ) );
        }

        // Get FCM tokens based on target
        global $wpdb;
        $where = array( "status = 'active'", "fcm_token IS NOT NULL", "fcm_token != ''" );

        if ( $target === 'ios' ) {
            $where[] = "platform = 'ios'";
        } elseif ( $target === 'android' ) {
            $where[] = "platform = 'android'";
        } elseif ( $target === 'active' ) {
            $where[] = "last_active >= '" . date( 'Y-m-d H:i:s', strtotime( '-7 days' ) ) . "'";
        }

        $where_sql = implode( ' AND ', $where );
        $tokens = $wpdb->get_col( "SELECT fcm_token FROM {$this->devices_table} WHERE {$where_sql}" );

        if ( empty( $tokens ) ) {
            wp_send_json_error( array( 'message' => 'No hay dispositivos para notificar' ) );
        }

        // Send via Firebase (requires FCM integration)
        $sent_count = $this->send_fcm_notifications( $tokens, $title, $body, $data );

        wp_send_json_success( array(
            'message' => sprintf(
                __( 'Notificación enviada a %d dispositivos', 'flavor-chat-ia' ),
                $sent_count
            ),
            'sent_count' => $sent_count,
        ) );
    }

    /**
     * Enviar notificaciones FCM
     */
    private function send_fcm_notifications( $tokens, $title, $body, $data = array() ) {
        $server_key = get_option( 'flavor_fcm_server_key' );

        if ( ! $server_key ) {
            return 0;
        }

        $sent = 0;
        $chunks = array_chunk( $tokens, 500 ); // FCM limit

        foreach ( $chunks as $chunk ) {
            $payload = array(
                'registration_ids' => $chunk,
                'notification'     => array(
                    'title' => $title,
                    'body'  => $body,
                ),
                'data'             => $data,
            );

            $response = wp_remote_post( 'https://fcm.googleapis.com/fcm/send', array(
                'headers' => array(
                    'Authorization' => 'key=' . $server_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $payload ),
                'timeout' => 30,
            ) );

            if ( ! is_wp_error( $response ) ) {
                $result = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $result['success'] ) ) {
                    $sent += $result['success'];
                }
            }
        }

        return $sent;
    }

    /**
     * AJAX: Exportar usuarios
     */
    public function ajax_export_users() {
        check_ajax_referer( 'flavor_app_users', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Sin permisos' ) );
        }

        global $wpdb;

        $users = $wpdb->get_results(
            "SELECT DISTINCT d.user_id, u.user_login, u.user_email, u.display_name,
                    COUNT(d.id) as device_count,
                    MAX(d.last_active) as last_seen,
                    GROUP_CONCAT(DISTINCT d.platform) as platforms
             FROM {$this->devices_table} d
             LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
             GROUP BY d.user_id
             ORDER BY last_seen DESC"
        );

        $csv_data = array();
        $csv_data[] = array( 'ID', 'Username', 'Email', 'Display Name', 'Devices', 'Platforms', 'Last Seen' );

        foreach ( $users as $user ) {
            $csv_data[] = array(
                $user->user_id,
                $user->user_login,
                $user->user_email,
                $user->display_name,
                $user->device_count,
                $user->platforms,
                $user->last_seen,
            );
        }

        wp_send_json_success( array( 'data' => $csv_data ) );
    }

    /**
     * REST: Registrar dispositivo
     */
    public function rest_register_device( $request ) {
        global $wpdb;

        $user_id = get_current_user_id();
        $device_id = sanitize_text_field( $request->get_param( 'device_id' ) );
        $platform = sanitize_text_field( $request->get_param( 'platform' ) );
        $fcm_token = sanitize_text_field( $request->get_param( 'fcm_token' ) );
        $app_version = sanitize_text_field( $request->get_param( 'app_version' ) );
        $device_info = $request->get_param( 'device_info' );

        if ( ! $device_id || ! $platform ) {
            return new WP_Error( 'missing_params', 'device_id y platform requeridos', array( 'status' => 400 ) );
        }

        // Check if device exists
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$this->devices_table} WHERE device_id = %s",
            $device_id
        ) );

        $data = array(
            'user_id'     => $user_id,
            'device_id'   => $device_id,
            'platform'    => $platform,
            'fcm_token'   => $fcm_token,
            'app_version' => $app_version,
            'device_info' => is_array( $device_info ) ? wp_json_encode( $device_info ) : $device_info,
            'last_active' => current_time( 'mysql' ),
            'status'      => 'active',
        );

        if ( $existing ) {
            $wpdb->update( $this->devices_table, $data, array( 'device_id' => $device_id ) );
        } else {
            $data['registered_at'] = current_time( 'mysql' );
            $wpdb->insert( $this->devices_table, $data );
        }

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Dispositivo registrado',
        ) );
    }

    /**
     * REST: Desregistrar dispositivo
     */
    public function rest_unregister_device( $request ) {
        global $wpdb;

        $device_id = sanitize_text_field( $request->get_param( 'device_id' ) );

        $wpdb->update(
            $this->devices_table,
            array( 'status' => 'unregistered' ),
            array( 'device_id' => $device_id )
        );

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Dispositivo desregistrado',
        ) );
    }

    /**
     * REST: Iniciar sesión
     */
    public function rest_start_session( $request ) {
        global $wpdb;

        $user_id = get_current_user_id();
        $device_id = sanitize_text_field( $request->get_param( 'device_id' ) );

        $session_id = wp_generate_uuid4();

        $wpdb->insert( $this->sessions_table, array(
            'session_id' => $session_id,
            'user_id'    => $user_id,
            'device_id'  => $device_id,
            'started_at' => current_time( 'mysql' ),
        ) );

        // Update device last_active
        $wpdb->update(
            $this->devices_table,
            array( 'last_active' => current_time( 'mysql' ) ),
            array( 'device_id' => $device_id )
        );

        return rest_ensure_response( array(
            'success'    => true,
            'session_id' => $session_id,
        ) );
    }

    /**
     * REST: Finalizar sesión
     */
    public function rest_end_session( $request ) {
        global $wpdb;

        $session_id = sanitize_text_field( $request->get_param( 'session_id' ) );

        $wpdb->update(
            $this->sessions_table,
            array( 'ended_at' => current_time( 'mysql' ) ),
            array( 'session_id' => $session_id )
        );

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Sesión finalizada',
        ) );
    }

    /**
     * Crear tablas en instalación
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $devices_table = $wpdb->prefix . 'flavor_app_devices';
        $sessions_table = $wpdb->prefix . 'flavor_app_sessions';

        $sql_devices = "CREATE TABLE IF NOT EXISTS {$devices_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            device_id VARCHAR(255) NOT NULL,
            platform VARCHAR(20) NOT NULL,
            fcm_token TEXT,
            app_version VARCHAR(20),
            device_info TEXT,
            registered_at DATETIME NOT NULL,
            last_active DATETIME,
            status VARCHAR(20) DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY device_id (device_id),
            KEY user_id (user_id),
            KEY platform (platform),
            KEY status (status)
        ) {$charset_collate};";

        $sql_sessions = "CREATE TABLE IF NOT EXISTS {$sessions_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(36) NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            device_id VARCHAR(255),
            started_at DATETIME NOT NULL,
            ended_at DATETIME,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY started_at (started_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_devices );
        dbDelta( $sql_sessions );
    }
}

// Initialize
Flavor_App_Users_Panel::get_instance();
