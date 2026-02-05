<?php
/**
 * API AJAX para el Dashboard de usuario frontend
 *
 * Gestiona las peticiones AJAX de actualizacion de perfil,
 * notificaciones y otras acciones del dashboard.
 *
 * @package FlavorChatIA
 * @subpackage Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para los endpoints AJAX del dashboard de usuario
 */
class Flavor_User_Dashboard_API {

    /**
     * Constructor: registra los hooks AJAX
     */
    public function __construct() {
        // Endpoints AJAX (solo para usuarios logueados)
        add_action('wp_ajax_flavor_update_profile', [$this, 'ajax_actualizar_perfil']);
        add_action('wp_ajax_flavor_update_password', [$this, 'ajax_actualizar_contrasena']);
        add_action('wp_ajax_flavor_get_notifications', [$this, 'ajax_obtener_notificaciones']);
        add_action('wp_ajax_flavor_mark_notification_read', [$this, 'ajax_marcar_notificacion_leida']);
        add_action('wp_ajax_flavor_mark_all_notifications_read', [$this, 'ajax_marcar_todas_notificaciones_leidas']);
        add_action('wp_ajax_flavor_get_unread_count', [$this, 'ajax_obtener_cantidad_sin_leer']);
    }

    /**
     * Verifica que el usuario esta logueado y el nonce es valido
     *
     * @return int|false ID del usuario o false si no autorizado
     */
    private function verificar_autorizacion() {
        if (!check_ajax_referer('flavor_user_dashboard', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Token de seguridad invalido. Recarga la pagina.', 'flavor-chat-ia'),
            ]);
            return false;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Debes iniciar sesion para realizar esta accion.', 'flavor-chat-ia'),
            ]);
            return false;
        }

        return get_current_user_id();
    }

    /**
     * AJAX: Actualizar datos del perfil (nombre, email, telefono)
     */
    public function ajax_actualizar_perfil() {
        $id_usuario_actual = $this->verificar_autorizacion();
        if (!$id_usuario_actual) {
            return;
        }

        $nombre_nuevo   = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';
        $apellido_nuevo = isset($_POST['apellido']) ? sanitize_text_field($_POST['apellido']) : '';
        $email_nuevo    = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $telefono_nuevo = isset($_POST['telefono']) ? sanitize_text_field($_POST['telefono']) : '';

        // Validar email
        if (!empty($email_nuevo) && !is_email($email_nuevo)) {
            wp_send_json_error([
                'message' => __('El email introducido no es valido.', 'flavor-chat-ia'),
            ]);
            return;
        }

        // Verificar que el email no lo use otro usuario
        if (!empty($email_nuevo)) {
            $usuario_con_mismo_email = get_user_by('email', $email_nuevo);
            if ($usuario_con_mismo_email && $usuario_con_mismo_email->ID !== $id_usuario_actual) {
                wp_send_json_error([
                    'message' => __('Este email ya esta registrado por otro usuario.', 'flavor-chat-ia'),
                ]);
                return;
            }
        }

        // Preparar datos para actualizar
        $datos_actualizacion_usuario = [
            'ID' => $id_usuario_actual,
        ];

        if (!empty($nombre_nuevo)) {
            $datos_actualizacion_usuario['first_name'] = $nombre_nuevo;
        }

        if (!empty($apellido_nuevo)) {
            $datos_actualizacion_usuario['last_name'] = $apellido_nuevo;
        }

        if (!empty($email_nuevo)) {
            $datos_actualizacion_usuario['user_email'] = $email_nuevo;
        }

        // Actualizar nombre para mostrar
        $nombre_para_mostrar = trim($nombre_nuevo . ' ' . $apellido_nuevo);
        if (!empty($nombre_para_mostrar)) {
            $datos_actualizacion_usuario['display_name'] = $nombre_para_mostrar;
        }

        // Actualizar datos del usuario en WordPress
        $resultado_actualizacion = wp_update_user($datos_actualizacion_usuario);

        if (is_wp_error($resultado_actualizacion)) {
            wp_send_json_error([
                'message' => $resultado_actualizacion->get_error_message(),
            ]);
            return;
        }

        // Actualizar telefono como meta del usuario
        if (!empty($telefono_nuevo)) {
            update_user_meta($id_usuario_actual, 'billing_phone', $telefono_nuevo);
        }

        // Permitir que otros plugins actualicen datos adicionales
        do_action('flavor_user_profile_updated', $id_usuario_actual, $_POST);

        // Registrar actividad si el sistema esta disponible
        if (class_exists('Flavor_Activity_Log')) {
            Flavor_Activity_Log::get_instance()->log(
                'profile_updated',
                __('Perfil actualizado desde Mi Cuenta', 'flavor-chat-ia'),
                $id_usuario_actual
            );
        }

        wp_send_json_success([
            'message' => __('Perfil actualizado correctamente.', 'flavor-chat-ia'),
            'data'    => [
                'nombre'         => $nombre_nuevo,
                'apellido'       => $apellido_nuevo,
                'email'          => $email_nuevo,
                'telefono'       => $telefono_nuevo,
                'nombre_mostrar' => $nombre_para_mostrar,
            ],
        ]);
    }

    /**
     * AJAX: Actualizar contrasena del usuario
     */
    public function ajax_actualizar_contrasena() {
        $id_usuario_actual = $this->verificar_autorizacion();
        if (!$id_usuario_actual) {
            return;
        }

        $contrasena_actual       = isset($_POST['password_actual']) ? $_POST['password_actual'] : '';
        $contrasena_nueva        = isset($_POST['password_nueva']) ? $_POST['password_nueva'] : '';
        $confirmacion_contrasena = isset($_POST['password_confirmar']) ? $_POST['password_confirmar'] : '';

        // Validaciones
        if (empty($contrasena_actual) || empty($contrasena_nueva) || empty($confirmacion_contrasena)) {
            wp_send_json_error([
                'message' => __('Todos los campos de contrasena son obligatorios.', 'flavor-chat-ia'),
            ]);
            return;
        }

        // Verificar contrasena actual
        $usuario_actual = wp_get_current_user();
        if (!wp_check_password($contrasena_actual, $usuario_actual->user_pass, $id_usuario_actual)) {
            wp_send_json_error([
                'message' => __('La contrasena actual no es correcta.', 'flavor-chat-ia'),
            ]);
            return;
        }

        // Verificar que las nuevas contrasenas coinciden
        if ($contrasena_nueva !== $confirmacion_contrasena) {
            wp_send_json_error([
                'message' => __('Las contrasenas nuevas no coinciden.', 'flavor-chat-ia'),
            ]);
            return;
        }

        // Verificar longitud minima
        if (strlen($contrasena_nueva) < 8) {
            wp_send_json_error([
                'message' => __('La contrasena debe tener al menos 8 caracteres.', 'flavor-chat-ia'),
            ]);
            return;
        }

        // Actualizar contrasena
        wp_set_password($contrasena_nueva, $id_usuario_actual);

        // Re-autenticar al usuario para que no pierda la sesion
        wp_set_auth_cookie($id_usuario_actual);
        wp_set_current_user($id_usuario_actual);

        // Registrar actividad
        if (class_exists('Flavor_Activity_Log')) {
            Flavor_Activity_Log::get_instance()->log(
                'password_changed',
                __('Contrasena cambiada desde Mi Cuenta', 'flavor-chat-ia'),
                $id_usuario_actual
            );
        }

        wp_send_json_success([
            'message' => __('Contrasena actualizada correctamente.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Obtener notificaciones del usuario
     */
    public function ajax_obtener_notificaciones() {
        $id_usuario_actual = $this->verificar_autorizacion();
        if (!$id_usuario_actual) {
            return;
        }

        $limite           = isset($_POST['limite']) ? intval($_POST['limite']) : 20;
        $desplazamiento   = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $solo_sin_leer    = !empty($_POST['solo_sin_leer']);

        $lista_notificaciones = [];
        $cantidad_sin_leer    = 0;

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();

            $argumentos_consulta = [
                'limit'       => $limite,
                'offset'      => $desplazamiento,
                'unread_only' => $solo_sin_leer,
            ];

            $lista_notificaciones = $gestor_notificaciones->get_user_notifications($id_usuario_actual, $argumentos_consulta);
            $cantidad_sin_leer    = $gestor_notificaciones->get_unread_count($id_usuario_actual);
        }

        wp_send_json_success([
            'notificaciones'   => $lista_notificaciones,
            'cantidad_sin_leer' => $cantidad_sin_leer,
        ]);
    }

    /**
     * AJAX: Marcar una notificacion como leida
     */
    public function ajax_marcar_notificacion_leida() {
        $id_usuario_actual = $this->verificar_autorizacion();
        if (!$id_usuario_actual) {
            return;
        }

        $id_notificacion = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

        if (!$id_notificacion) {
            wp_send_json_error([
                'message' => __('ID de notificacion no valido.', 'flavor-chat-ia'),
            ]);
            return;
        }

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $gestor_notificaciones->mark_as_read($id_notificacion, $id_usuario_actual);

            wp_send_json_success([
                'message'           => __('Notificacion marcada como leida.', 'flavor-chat-ia'),
                'cantidad_sin_leer' => $gestor_notificaciones->get_unread_count($id_usuario_actual),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Sistema de notificaciones no disponible.', 'flavor-chat-ia'),
            ]);
        }
    }

    /**
     * AJAX: Marcar todas las notificaciones como leidas
     */
    public function ajax_marcar_todas_notificaciones_leidas() {
        $id_usuario_actual = $this->verificar_autorizacion();
        if (!$id_usuario_actual) {
            return;
        }

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $gestor_notificaciones->mark_all_as_read($id_usuario_actual);

            wp_send_json_success([
                'message'           => __('Todas las notificaciones marcadas como leidas.', 'flavor-chat-ia'),
                'cantidad_sin_leer' => 0,
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Sistema de notificaciones no disponible.', 'flavor-chat-ia'),
            ]);
        }
    }

    /**
     * AJAX: Obtener cantidad de notificaciones sin leer (para polling)
     */
    public function ajax_obtener_cantidad_sin_leer() {
        $id_usuario_actual = $this->verificar_autorizacion();
        if (!$id_usuario_actual) {
            return;
        }

        $cantidad_sin_leer = 0;

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $cantidad_sin_leer = $gestor_notificaciones->get_unread_count($id_usuario_actual);
        }

        wp_send_json_success([
            'cantidad_sin_leer' => $cantidad_sin_leer,
        ]);
    }
}
