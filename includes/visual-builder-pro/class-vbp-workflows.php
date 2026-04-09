<?php
/**
 * Visual Builder Pro - Workflows System
 * Sistema de flujos de aprobación para publicación
 *
 * @package Flavor_Chat_IA
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar workflows de publicación
 */
class Flavor_VBP_Workflows {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Estados del workflow
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_CHANGES_REQUESTED = 'changes_requested';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'publish';
    const STATUS_SCHEDULED = 'scheduled';

    /**
     * Roles del workflow
     */
    const ROLE_AUTHOR = 'author';
    const ROLE_REVIEWER = 'reviewer';
    const ROLE_PUBLISHER = 'publisher';
    const ROLE_ADMIN = 'admin';

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
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // REST API
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

        // Custom post status
        add_action( 'init', array( $this, 'register_post_statuses' ) );

        // Notificaciones
        add_action( 'vbp_workflow_status_changed', array( $this, 'send_notification' ), 10, 4 );

        // Admin columns
        add_filter( 'manage_page_posts_columns', array( $this, 'add_workflow_column' ) );
        add_action( 'manage_page_posts_custom_column', array( $this, 'render_workflow_column' ), 10, 2 );
    }

    /**
     * Registrar estados personalizados
     */
    public function register_post_statuses() {
        register_post_status( 'pending_review', array(
            'label'                     => _x( 'Pending Review', 'post status', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Pending Review <span class="count">(%s)</span>',
                'Pending Review <span class="count">(%s)</span>',
                FLAVOR_PLATFORM_TEXT_DOMAIN
            ),
        ) );

        register_post_status( 'changes_requested', array(
            'label'                     => _x( 'Changes Requested', 'post status', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Changes Requested <span class="count">(%s)</span>',
                'Changes Requested <span class="count">(%s)</span>',
                FLAVOR_PLATFORM_TEXT_DOMAIN
            ),
        ) );

        register_post_status( 'approved', array(
            'label'                     => _x( 'Approved', 'post status', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Approved <span class="count">(%s)</span>',
                'Approved <span class="count">(%s)</span>',
                FLAVOR_PLATFORM_TEXT_DOMAIN
            ),
        ) );

        register_post_status( 'scheduled', array(
            'label'                     => _x( 'Scheduled', 'post status', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Scheduled <span class="count">(%s)</span>',
                'Scheduled <span class="count">(%s)</span>',
                FLAVOR_PLATFORM_TEXT_DOMAIN
            ),
        ) );
    }

    /**
     * Registrar rutas REST
     */
    public function register_routes() {
        $namespace = 'flavor-vbp/v1';

        // Obtener estado del workflow
        register_rest_route( $namespace, '/workflow/(?P<post_id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_workflow_status' ),
            'permission_callback' => array( $this, 'check_permissions' ),
            'args'                => array(
                'post_id' => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                ),
            ),
        ) );

        // Cambiar estado del workflow
        register_rest_route( $namespace, '/workflow/(?P<post_id>\d+)/transition', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'transition_status' ),
            'permission_callback' => array( $this, 'check_permissions' ),
            'args'                => array(
                'post_id' => array(
                    'required' => true,
                ),
                'action' => array(
                    'required' => true,
                    'enum'     => array( 'submit_review', 'approve', 'request_changes', 'publish', 'schedule', 'unpublish', 'revert_draft' ),
                ),
            ),
        ) );

        // Historial del workflow
        register_rest_route( $namespace, '/workflow/(?P<post_id>\d+)/history', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_workflow_history' ),
            'permission_callback' => array( $this, 'check_permissions' ),
        ) );

        // Asignar revisores
        register_rest_route( $namespace, '/workflow/(?P<post_id>\d+)/reviewers', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'assign_reviewers' ),
            'permission_callback' => array( $this, 'check_admin_permissions' ),
        ) );

        // Lista de posts pendientes
        register_rest_route( $namespace, '/workflow/pending', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_pending_reviews' ),
            'permission_callback' => array( $this, 'check_reviewer_permissions' ),
        ) );

        // Configuración del workflow
        register_rest_route( $namespace, '/workflow/settings', array(
            'methods'             => array( 'GET', 'POST' ),
            'callback'            => array( $this, 'handle_settings' ),
            'permission_callback' => array( $this, 'check_admin_permissions' ),
        ) );

        // Lista de usuarios por rol
        register_rest_route( $namespace, '/workflow/users', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_workflow_users' ),
            'permission_callback' => array( $this, 'check_permissions' ),
        ) );
    }

    /**
     * Verificar permisos básicos
     */
    public function check_permissions() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Verificar permisos de revisor
     */
    public function check_reviewer_permissions() {
        return current_user_can( 'edit_others_posts' ) || $this->is_reviewer( get_current_user_id() );
    }

    /**
     * Verificar permisos de admin
     */
    public function check_admin_permissions() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Verificar si usuario es revisor
     */
    private function is_reviewer( $user_id ) {
        $reviewers = get_option( 'vbp_workflow_reviewers', array() );
        return in_array( $user_id, $reviewers, true );
    }

    /**
     * Obtener estado del workflow
     */
    public function get_workflow_status( $request ) {
        $post_id = intval( $request['post_id'] );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'not_found', 'Post no encontrado', array( 'status' => 404 ) );
        }

        $workflow_data = get_post_meta( $post_id, '_vbp_workflow', true );
        $current_status = $post->post_status;

        // Mapear estados de WordPress a workflow
        $workflow_status = $this->map_post_status_to_workflow( $current_status );

        $available_transitions = $this->get_available_transitions( $post_id, $workflow_status );
        $reviewers = get_post_meta( $post_id, '_vbp_reviewers', true ) ?: array();
        $scheduled_date = get_post_meta( $post_id, '_vbp_scheduled_date', true );

        return rest_ensure_response( array(
            'success'       => true,
            'post_id'       => $post_id,
            'status'        => $workflow_status,
            'status_label'  => $this->get_status_label( $workflow_status ),
            'transitions'   => $available_transitions,
            'reviewers'     => $this->format_users( $reviewers ),
            'scheduled_date' => $scheduled_date,
            'can_edit'      => $this->can_edit_post( $post_id ),
            'can_publish'   => $this->can_publish_post( $post_id ),
            'can_review'    => $this->can_review_post( $post_id ),
            'workflow_data' => $workflow_data ?: array(),
        ) );
    }

    /**
     * Mapear estado de post a workflow
     */
    private function map_post_status_to_workflow( $post_status ) {
        $map = array(
            'draft'             => self::STATUS_DRAFT,
            'pending'           => self::STATUS_PENDING_REVIEW,
            'pending_review'    => self::STATUS_PENDING_REVIEW,
            'changes_requested' => self::STATUS_CHANGES_REQUESTED,
            'approved'          => self::STATUS_APPROVED,
            'publish'           => self::STATUS_PUBLISHED,
            'scheduled'         => self::STATUS_SCHEDULED,
            'future'            => self::STATUS_SCHEDULED,
        );

        return isset( $map[ $post_status ] ) ? $map[ $post_status ] : self::STATUS_DRAFT;
    }

    /**
     * Obtener transiciones disponibles
     */
    private function get_available_transitions( $post_id, $current_status ) {
        $user_id = get_current_user_id();
        $transitions = array();

        $is_author = get_post_field( 'post_author', $post_id ) == $user_id;
        $is_reviewer = $this->can_review_post( $post_id );
        $is_publisher = current_user_can( 'publish_pages' );
        $is_admin = current_user_can( 'manage_options' );

        switch ( $current_status ) {
            case self::STATUS_DRAFT:
                if ( $is_author || $is_admin ) {
                    $transitions[] = array(
                        'action' => 'submit_review',
                        'label'  => 'Enviar a revisión',
                        'icon'   => '📤',
                        'color'  => '#3b82f6',
                    );
                }
                if ( $is_publisher || $is_admin ) {
                    $transitions[] = array(
                        'action' => 'publish',
                        'label'  => 'Publicar directamente',
                        'icon'   => '🚀',
                        'color'  => '#22c55e',
                    );
                }
                break;

            case self::STATUS_PENDING_REVIEW:
                if ( $is_reviewer || $is_admin ) {
                    $transitions[] = array(
                        'action' => 'approve',
                        'label'  => 'Aprobar',
                        'icon'   => '✅',
                        'color'  => '#22c55e',
                    );
                    $transitions[] = array(
                        'action' => 'request_changes',
                        'label'  => 'Solicitar cambios',
                        'icon'   => '✏️',
                        'color'  => '#f59e0b',
                    );
                }
                if ( $is_author || $is_admin ) {
                    $transitions[] = array(
                        'action' => 'revert_draft',
                        'label'  => 'Volver a borrador',
                        'icon'   => '↩️',
                        'color'  => '#6b7280',
                    );
                }
                break;

            case self::STATUS_CHANGES_REQUESTED:
                if ( $is_author || $is_admin ) {
                    $transitions[] = array(
                        'action' => 'submit_review',
                        'label'  => 'Reenviar a revisión',
                        'icon'   => '📤',
                        'color'  => '#3b82f6',
                    );
                    $transitions[] = array(
                        'action' => 'revert_draft',
                        'label'  => 'Volver a borrador',
                        'icon'   => '↩️',
                        'color'  => '#6b7280',
                    );
                }
                break;

            case self::STATUS_APPROVED:
                if ( $is_publisher || $is_admin ) {
                    $transitions[] = array(
                        'action' => 'publish',
                        'label'  => 'Publicar',
                        'icon'   => '🚀',
                        'color'  => '#22c55e',
                    );
                    $transitions[] = array(
                        'action' => 'schedule',
                        'label'  => 'Programar',
                        'icon'   => '📅',
                        'color'  => '#8b5cf6',
                    );
                }
                if ( $is_reviewer || $is_admin ) {
                    $transitions[] = array(
                        'action' => 'request_changes',
                        'label'  => 'Revocar aprobación',
                        'icon'   => '↩️',
                        'color'  => '#f59e0b',
                    );
                }
                break;

            case self::STATUS_PUBLISHED:
                if ( $is_publisher || $is_admin ) {
                    $transitions[] = array(
                        'action' => 'unpublish',
                        'label'  => 'Despublicar',
                        'icon'   => '📥',
                        'color'  => '#ef4444',
                    );
                }
                break;

            case self::STATUS_SCHEDULED:
                if ( $is_publisher || $is_admin ) {
                    $transitions[] = array(
                        'action' => 'publish',
                        'label'  => 'Publicar ahora',
                        'icon'   => '🚀',
                        'color'  => '#22c55e',
                    );
                    $transitions[] = array(
                        'action' => 'revert_draft',
                        'label'  => 'Cancelar programación',
                        'icon'   => '❌',
                        'color'  => '#ef4444',
                    );
                }
                break;
        }

        return $transitions;
    }

    /**
     * Realizar transición de estado
     */
    public function transition_status( $request ) {
        $post_id = intval( $request['post_id'] );
        $action = sanitize_text_field( $request['action'] );
        $comment = isset( $request['comment'] ) ? sanitize_textarea_field( $request['comment'] ) : '';
        $scheduled_date = isset( $request['scheduled_date'] ) ? sanitize_text_field( $request['scheduled_date'] ) : '';

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'not_found', 'Post no encontrado', array( 'status' => 404 ) );
        }

        $current_status = $this->map_post_status_to_workflow( $post->post_status );
        $user_id = get_current_user_id();
        $new_status = '';
        $new_post_status = '';

        // Validar y ejecutar transición
        switch ( $action ) {
            case 'submit_review':
                if ( ! in_array( $current_status, array( self::STATUS_DRAFT, self::STATUS_CHANGES_REQUESTED ) ) ) {
                    return new WP_Error( 'invalid_transition', 'Transición no válida', array( 'status' => 400 ) );
                }
                $new_status = self::STATUS_PENDING_REVIEW;
                $new_post_status = 'pending_review';
                break;

            case 'approve':
                if ( $current_status !== self::STATUS_PENDING_REVIEW ) {
                    return new WP_Error( 'invalid_transition', 'Transición no válida', array( 'status' => 400 ) );
                }
                if ( ! $this->can_review_post( $post_id ) ) {
                    return new WP_Error( 'forbidden', 'No tienes permisos para aprobar', array( 'status' => 403 ) );
                }
                $new_status = self::STATUS_APPROVED;
                $new_post_status = 'approved';
                break;

            case 'request_changes':
                if ( ! in_array( $current_status, array( self::STATUS_PENDING_REVIEW, self::STATUS_APPROVED ) ) ) {
                    return new WP_Error( 'invalid_transition', 'Transición no válida', array( 'status' => 400 ) );
                }
                if ( ! $this->can_review_post( $post_id ) ) {
                    return new WP_Error( 'forbidden', 'No tienes permisos para solicitar cambios', array( 'status' => 403 ) );
                }
                $new_status = self::STATUS_CHANGES_REQUESTED;
                $new_post_status = 'changes_requested';
                break;

            case 'publish':
                if ( ! in_array( $current_status, array( self::STATUS_DRAFT, self::STATUS_APPROVED, self::STATUS_SCHEDULED ) ) ) {
                    return new WP_Error( 'invalid_transition', 'Transición no válida', array( 'status' => 400 ) );
                }
                if ( ! $this->can_publish_post( $post_id ) ) {
                    return new WP_Error( 'forbidden', 'No tienes permisos para publicar', array( 'status' => 403 ) );
                }
                $new_status = self::STATUS_PUBLISHED;
                $new_post_status = 'publish';
                break;

            case 'schedule':
                if ( $current_status !== self::STATUS_APPROVED ) {
                    return new WP_Error( 'invalid_transition', 'Transición no válida', array( 'status' => 400 ) );
                }
                if ( empty( $scheduled_date ) ) {
                    return new WP_Error( 'missing_date', 'Fecha de programación requerida', array( 'status' => 400 ) );
                }
                if ( ! $this->can_publish_post( $post_id ) ) {
                    return new WP_Error( 'forbidden', 'No tienes permisos para programar', array( 'status' => 403 ) );
                }
                $new_status = self::STATUS_SCHEDULED;
                $new_post_status = 'future';
                update_post_meta( $post_id, '_vbp_scheduled_date', $scheduled_date );
                break;

            case 'unpublish':
                if ( $current_status !== self::STATUS_PUBLISHED ) {
                    return new WP_Error( 'invalid_transition', 'Transición no válida', array( 'status' => 400 ) );
                }
                if ( ! $this->can_publish_post( $post_id ) ) {
                    return new WP_Error( 'forbidden', 'No tienes permisos para despublicar', array( 'status' => 403 ) );
                }
                $new_status = self::STATUS_DRAFT;
                $new_post_status = 'draft';
                break;

            case 'revert_draft':
                if ( ! $this->can_edit_post( $post_id ) ) {
                    return new WP_Error( 'forbidden', 'No tienes permisos para editar', array( 'status' => 403 ) );
                }
                $new_status = self::STATUS_DRAFT;
                $new_post_status = 'draft';
                delete_post_meta( $post_id, '_vbp_scheduled_date' );
                break;

            default:
                return new WP_Error( 'invalid_action', 'Acción no válida', array( 'status' => 400 ) );
        }

        // Actualizar estado del post
        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => $new_post_status,
            'post_date'   => $action === 'schedule' ? $scheduled_date : $post->post_date,
        ) );

        // Registrar en historial
        $this->add_history_entry( $post_id, array(
            'action'      => $action,
            'from_status' => $current_status,
            'to_status'   => $new_status,
            'user_id'     => $user_id,
            'comment'     => $comment,
            'timestamp'   => current_time( 'mysql' ),
        ) );

        // Disparar acción para notificaciones
        do_action( 'vbp_workflow_status_changed', $post_id, $current_status, $new_status, $user_id );

        // Log de auditoría
        if ( function_exists( 'vbp_audit_log' ) ) {
            vbp_audit_log( 'workflow_' . $action, array(
                'post_id'     => $post_id,
                'from_status' => $current_status,
                'to_status'   => $new_status,
                'comment'     => $comment,
            ) );
        }

        return rest_ensure_response( array(
            'success'    => true,
            'message'    => $this->get_transition_message( $action ),
            'new_status' => $new_status,
            'post_id'    => $post_id,
        ) );
    }

    /**
     * Obtener historial del workflow
     */
    public function get_workflow_history( $request ) {
        $post_id = intval( $request['post_id'] );
        $history = get_post_meta( $post_id, '_vbp_workflow_history', true ) ?: array();

        // Formatear historial con datos de usuario
        $formatted_history = array_map( function( $entry ) {
            $user = get_user_by( 'id', $entry['user_id'] );
            return array(
                'action'       => $entry['action'],
                'action_label' => $this->get_action_label( $entry['action'] ),
                'from_status'  => $entry['from_status'],
                'to_status'    => $entry['to_status'],
                'user'         => array(
                    'id'     => $entry['user_id'],
                    'name'   => $user ? $user->display_name : 'Usuario desconocido',
                    'avatar' => get_avatar_url( $entry['user_id'], array( 'size' => 32 ) ),
                ),
                'comment'      => $entry['comment'],
                'timestamp'    => $entry['timestamp'],
                'time_ago'     => human_time_diff( strtotime( $entry['timestamp'] ), current_time( 'timestamp' ) ),
            );
        }, array_reverse( $history ) );

        return rest_ensure_response( array(
            'success' => true,
            'history' => $formatted_history,
        ) );
    }

    /**
     * Añadir entrada al historial
     */
    private function add_history_entry( $post_id, $entry ) {
        $history = get_post_meta( $post_id, '_vbp_workflow_history', true ) ?: array();
        $history[] = $entry;

        // Limitar historial a 100 entradas
        if ( count( $history ) > 100 ) {
            $history = array_slice( $history, -100 );
        }

        update_post_meta( $post_id, '_vbp_workflow_history', $history );
    }

    /**
     * Asignar revisores a un post
     */
    public function assign_reviewers( $request ) {
        $post_id = intval( $request['post_id'] );
        $reviewers = isset( $request['reviewers'] ) ? array_map( 'intval', $request['reviewers'] ) : array();

        update_post_meta( $post_id, '_vbp_reviewers', $reviewers );

        // Notificar a revisores asignados
        foreach ( $reviewers as $reviewer_id ) {
            $this->notify_user( $reviewer_id, 'reviewer_assigned', $post_id );
        }

        return rest_ensure_response( array(
            'success'   => true,
            'message'   => 'Revisores asignados',
            'reviewers' => $this->format_users( $reviewers ),
        ) );
    }

    /**
     * Obtener posts pendientes de revisión
     */
    public function get_pending_reviews( $request ) {
        $user_id = get_current_user_id();
        $page = isset( $request['page'] ) ? intval( $request['page'] ) : 1;
        $per_page = isset( $request['per_page'] ) ? intval( $request['per_page'] ) : 10;

        $args = array(
            'post_type'      => 'page',
            'post_status'    => array( 'pending_review', 'pending' ),
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        );

        // Si no es admin, filtrar solo los asignados
        if ( ! current_user_can( 'manage_options' ) ) {
            $args['meta_query'] = array(
                array(
                    'key'     => '_vbp_reviewers',
                    'value'   => serialize( strval( $user_id ) ),
                    'compare' => 'LIKE',
                ),
            );
        }

        $query = new WP_Query( $args );
        $posts = array();

        foreach ( $query->posts as $post ) {
            $author = get_user_by( 'id', $post->post_author );
            $posts[] = array(
                'id'            => $post->ID,
                'title'         => $post->post_title,
                'author'        => array(
                    'id'     => $post->post_author,
                    'name'   => $author ? $author->display_name : 'Desconocido',
                    'avatar' => get_avatar_url( $post->post_author, array( 'size' => 32 ) ),
                ),
                'modified'      => $post->post_modified,
                'modified_ago'  => human_time_diff( strtotime( $post->post_modified ), current_time( 'timestamp' ) ),
                'edit_url'      => admin_url( 'admin.php?page=vbp-editor&post_id=' . $post->ID ),
            );
        }

        return rest_ensure_response( array(
            'success' => true,
            'posts'   => $posts,
            'total'   => $query->found_posts,
            'pages'   => $query->max_num_pages,
            'page'    => $page,
        ) );
    }

    /**
     * Manejar configuración
     */
    public function handle_settings( $request ) {
        if ( $request->get_method() === 'GET' ) {
            return rest_ensure_response( array(
                'success'  => true,
                'settings' => array(
                    'enabled'              => get_option( 'vbp_workflow_enabled', true ),
                    'require_review'       => get_option( 'vbp_workflow_require_review', true ),
                    'auto_assign_reviewers' => get_option( 'vbp_workflow_auto_assign', false ),
                    'default_reviewers'    => get_option( 'vbp_workflow_default_reviewers', array() ),
                    'notify_on_submit'     => get_option( 'vbp_workflow_notify_submit', true ),
                    'notify_on_approve'    => get_option( 'vbp_workflow_notify_approve', true ),
                    'notify_on_publish'    => get_option( 'vbp_workflow_notify_publish', true ),
                ),
            ) );
        }

        // POST - Guardar configuración
        $settings = $request->get_json_params();

        if ( isset( $settings['enabled'] ) ) {
            update_option( 'vbp_workflow_enabled', (bool) $settings['enabled'] );
        }
        if ( isset( $settings['require_review'] ) ) {
            update_option( 'vbp_workflow_require_review', (bool) $settings['require_review'] );
        }
        if ( isset( $settings['auto_assign_reviewers'] ) ) {
            update_option( 'vbp_workflow_auto_assign', (bool) $settings['auto_assign_reviewers'] );
        }
        if ( isset( $settings['default_reviewers'] ) ) {
            update_option( 'vbp_workflow_default_reviewers', array_map( 'intval', $settings['default_reviewers'] ) );
        }
        if ( isset( $settings['notify_on_submit'] ) ) {
            update_option( 'vbp_workflow_notify_submit', (bool) $settings['notify_on_submit'] );
        }
        if ( isset( $settings['notify_on_approve'] ) ) {
            update_option( 'vbp_workflow_notify_approve', (bool) $settings['notify_on_approve'] );
        }
        if ( isset( $settings['notify_on_publish'] ) ) {
            update_option( 'vbp_workflow_notify_publish', (bool) $settings['notify_on_publish'] );
        }

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Configuración guardada',
        ) );
    }

    /**
     * Obtener usuarios para workflow
     */
    public function get_workflow_users( $request ) {
        $role = isset( $request['role'] ) ? sanitize_text_field( $request['role'] ) : '';

        $args = array(
            'orderby' => 'display_name',
            'order'   => 'ASC',
        );

        if ( $role === 'reviewers' ) {
            $args['role__in'] = array( 'administrator', 'editor' );
        } elseif ( $role === 'publishers' ) {
            $args['capability'] = 'publish_pages';
        }

        $users = get_users( $args );
        $formatted_users = array();

        foreach ( $users as $user ) {
            $formatted_users[] = array(
                'id'     => $user->ID,
                'name'   => $user->display_name,
                'email'  => $user->user_email,
                'avatar' => get_avatar_url( $user->ID, array( 'size' => 32 ) ),
                'roles'  => $user->roles,
            );
        }

        return rest_ensure_response( array(
            'success' => true,
            'users'   => $formatted_users,
        ) );
    }

    /**
     * Enviar notificación
     */
    public function send_notification( $post_id, $from_status, $to_status, $user_id ) {
        $post = get_post( $post_id );
        $actor = get_user_by( 'id', $user_id );

        // Determinar destinatarios según el cambio
        $recipients = array();

        switch ( $to_status ) {
            case self::STATUS_PENDING_REVIEW:
                if ( get_option( 'vbp_workflow_notify_submit', true ) ) {
                    // Notificar a revisores asignados o por defecto
                    $reviewers = get_post_meta( $post_id, '_vbp_reviewers', true );
                    if ( empty( $reviewers ) ) {
                        $reviewers = get_option( 'vbp_workflow_default_reviewers', array() );
                    }
                    $recipients = $reviewers;
                }
                break;

            case self::STATUS_APPROVED:
            case self::STATUS_CHANGES_REQUESTED:
                if ( get_option( 'vbp_workflow_notify_approve', true ) ) {
                    // Notificar al autor
                    $recipients = array( $post->post_author );
                }
                break;

            case self::STATUS_PUBLISHED:
                if ( get_option( 'vbp_workflow_notify_publish', true ) ) {
                    // Notificar al autor y revisores
                    $reviewers = get_post_meta( $post_id, '_vbp_reviewers', true ) ?: array();
                    $recipients = array_merge( array( $post->post_author ), $reviewers );
                }
                break;
        }

        // Enviar emails
        foreach ( array_unique( $recipients ) as $recipient_id ) {
            if ( $recipient_id != $user_id ) { // No notificar al actor
                $this->send_email_notification( $recipient_id, $post_id, $from_status, $to_status, $actor );
            }
        }
    }

    /**
     * Enviar email de notificación
     */
    private function send_email_notification( $user_id, $post_id, $from_status, $to_status, $actor ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return;
        }

        $post = get_post( $post_id );
        $site_name = get_bloginfo( 'name' );
        $edit_url = admin_url( 'admin.php?page=vbp-editor&post_id=' . $post_id );

        $subject = sprintf(
            '[%s] %s - %s',
            $site_name,
            $post->post_title,
            $this->get_status_label( $to_status )
        );

        $message = sprintf(
            "Hola %s,\n\n" .
            "%s ha cambiado el estado de \"%s\" a \"%s\".\n\n" .
            "Puedes ver y editar la página aquí:\n%s\n\n" .
            "Saludos,\n%s",
            $user->display_name,
            $actor->display_name,
            $post->post_title,
            $this->get_status_label( $to_status ),
            $edit_url,
            $site_name
        );

        wp_mail( $user->user_email, $subject, $message );
    }

    /**
     * Notificar a usuario específico
     */
    private function notify_user( $user_id, $type, $post_id ) {
        // Implementación simplificada - se puede expandir
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return;
        }

        $post = get_post( $post_id );
        $site_name = get_bloginfo( 'name' );
        $edit_url = admin_url( 'admin.php?page=vbp-editor&post_id=' . $post_id );

        if ( $type === 'reviewer_assigned' ) {
            $subject = sprintf( '[%s] Se te ha asignado como revisor', $site_name );
            $message = sprintf(
                "Hola %s,\n\n" .
                "Se te ha asignado como revisor de \"%s\".\n\n" .
                "Puedes revisar la página aquí:\n%s\n\n" .
                "Saludos,\n%s",
                $user->display_name,
                $post->post_title,
                $edit_url,
                $site_name
            );

            wp_mail( $user->user_email, $subject, $message );
        }
    }

    // ============ HELPERS ============

    /**
     * Verificar si puede editar
     */
    private function can_edit_post( $post_id ) {
        return current_user_can( 'edit_post', $post_id );
    }

    /**
     * Verificar si puede publicar
     */
    private function can_publish_post( $post_id ) {
        return current_user_can( 'publish_pages' ) || current_user_can( 'manage_options' );
    }

    /**
     * Verificar si puede revisar
     */
    private function can_review_post( $post_id ) {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        $user_id = get_current_user_id();
        $reviewers = get_post_meta( $post_id, '_vbp_reviewers', true ) ?: array();

        if ( in_array( $user_id, $reviewers ) ) {
            return true;
        }

        // Verificar si es revisor global
        return current_user_can( 'edit_others_posts' );
    }

    /**
     * Formatear usuarios
     */
    private function format_users( $user_ids ) {
        $users = array();
        foreach ( $user_ids as $user_id ) {
            $user = get_user_by( 'id', $user_id );
            if ( $user ) {
                $users[] = array(
                    'id'     => $user->ID,
                    'name'   => $user->display_name,
                    'avatar' => get_avatar_url( $user->ID, array( 'size' => 32 ) ),
                );
            }
        }
        return $users;
    }

    /**
     * Obtener etiqueta de estado
     */
    private function get_status_label( $status ) {
        $labels = array(
            self::STATUS_DRAFT             => 'Borrador',
            self::STATUS_PENDING_REVIEW    => 'Pendiente de revisión',
            self::STATUS_CHANGES_REQUESTED => 'Cambios solicitados',
            self::STATUS_APPROVED          => 'Aprobado',
            self::STATUS_PUBLISHED         => 'Publicado',
            self::STATUS_SCHEDULED         => 'Programado',
        );
        return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
    }

    /**
     * Obtener etiqueta de acción
     */
    private function get_action_label( $action ) {
        $labels = array(
            'submit_review'    => 'Enviado a revisión',
            'approve'          => 'Aprobado',
            'request_changes'  => 'Cambios solicitados',
            'publish'          => 'Publicado',
            'schedule'         => 'Programado',
            'unpublish'        => 'Despublicado',
            'revert_draft'     => 'Revertido a borrador',
        );
        return isset( $labels[ $action ] ) ? $labels[ $action ] : $action;
    }

    /**
     * Obtener mensaje de transición
     */
    private function get_transition_message( $action ) {
        $messages = array(
            'submit_review'    => 'Enviado a revisión correctamente',
            'approve'          => 'Página aprobada',
            'request_changes'  => 'Cambios solicitados',
            'publish'          => 'Página publicada',
            'schedule'         => 'Página programada',
            'unpublish'        => 'Página despublicada',
            'revert_draft'     => 'Revertido a borrador',
        );
        return isset( $messages[ $action ] ) ? $messages[ $action ] : 'Estado actualizado';
    }

    /**
     * Añadir columna de workflow en admin
     */
    public function add_workflow_column( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            if ( $key === 'title' ) {
                $new_columns['workflow_status'] = 'Workflow';
            }
        }
        return $new_columns;
    }

    /**
     * Renderizar columna de workflow
     */
    public function render_workflow_column( $column, $post_id ) {
        if ( $column !== 'workflow_status' ) {
            return;
        }

        $post = get_post( $post_id );
        $status = $this->map_post_status_to_workflow( $post->post_status );
        $label = $this->get_status_label( $status );

        $colors = array(
            self::STATUS_DRAFT             => '#6b7280',
            self::STATUS_PENDING_REVIEW    => '#f59e0b',
            self::STATUS_CHANGES_REQUESTED => '#ef4444',
            self::STATUS_APPROVED          => '#22c55e',
            self::STATUS_PUBLISHED         => '#3b82f6',
            self::STATUS_SCHEDULED         => '#8b5cf6',
        );

        $color = isset( $colors[ $status ] ) ? $colors[ $status ] : '#6b7280';

        printf(
            '<span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;background:%s;color:white;">%s</span>',
            esc_attr( $color ),
            esc_html( $label )
        );
    }
}
