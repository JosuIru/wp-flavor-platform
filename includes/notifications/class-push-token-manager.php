<?php
/**
 * Gestor de tokens FCM para Push Notifications
 *
 * Gestiona los tokens FCM de dispositivos de los usuarios.
 * Proporciona endpoints REST para registro/eliminacion de tokens.
 *
 * @package FlavorPlatform
 * @subpackage Notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Push_Token_Manager {

    const META_KEY_FCM_TOKENS = 'flavor_fcm_tokens';

    /**
     * Inicializar hooks
     */
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'registrar_rutas_rest']);
    }

    /**
     * Registrar rutas REST API
     */
    public static function registrar_rutas_rest() {
        flavor_register_rest_route(FLAVOR_PLATFORM_REST_NAMESPACE, '/push/register', [
            'methods'  => 'POST',
            'callback' => [__CLASS__, 'endpoint_registrar_token'],
            'permission_callback' => [__CLASS__, 'verificar_autenticacion'],
        ]);

        flavor_register_rest_route(FLAVOR_PLATFORM_REST_NAMESPACE, '/push/unregister', [
            'methods'  => 'DELETE',
            'callback' => [__CLASS__, 'endpoint_eliminar_token'],
            'permission_callback' => [__CLASS__, 'verificar_autenticacion'],
        ]);
    }

    /**
     * Verificar que el usuario esta autenticado (cookie WP o API key de app)
     */
    public static function verificar_autenticacion($request) {
        if (is_user_logged_in()) {
            return true;
        }
        $api_key_recibida = $request->get_header('X-Flavor-API-Key');
        if (!empty($api_key_recibida)) {
            $api_key_almacenada = get_option('flavor_app_api_key', '');
            if (!empty($api_key_almacenada) && hash_equals($api_key_almacenada, $api_key_recibida)) {
                $user_id_header = intval($request->get_header('X-Flavor-User-ID'));
                if ($user_id_header > 0 && get_user_by('ID', $user_id_header)) {
                    return true;
                }
            }
        }
        return new WP_Error('rest_forbidden', __('Autenticacion requerida.', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 401]);
    }

    /**
     * Obtener el user ID del request (desde sesion o header)
     */
    private static function obtener_usuario_id_del_request($request) {
        if (is_user_logged_in()) {
            return get_current_user_id();
        }
        return intval($request->get_header('X-Flavor-User-ID'));
    }

    /**
     * Endpoint: Registrar token FCM para el usuario autenticado
     */
    public static function endpoint_registrar_token($request) {
        $usuario_id = self::obtener_usuario_id_del_request($request);
        $token_fcm = sanitize_text_field($request->get_param('token'));
        $nombre_dispositivo = sanitize_text_field($request->get_param('device_name') ?? '');

        if (empty($token_fcm)) {
            return new WP_Error('missing_token', __('El token FCM es obligatorio.', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $registrado = self::registrar_token($usuario_id, $token_fcm, $nombre_dispositivo);

        if ($registrado) {
            return new WP_REST_Response(['success' => true, 'message' => __('Token registrado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
        }

        return new WP_Error('register_failed', __('Error al registrar el token.', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 500]);
    }

    /**
     * Endpoint: Eliminar token FCM del usuario autenticado
     */
    public static function endpoint_eliminar_token($request) {
        $usuario_id = self::obtener_usuario_id_del_request($request);
        $token_fcm = sanitize_text_field($request->get_param('token'));

        if (empty($token_fcm)) {
            return new WP_Error('missing_token', __('El token FCM es obligatorio.', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $eliminado = self::eliminar_token($usuario_id, $token_fcm);

        return new WP_REST_Response(['success' => $eliminado, 'message' => $eliminado ? __('Token eliminado.', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Token no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 200);
    }

    /**
     * Registrar un token FCM para un usuario
     */
    public static function registrar_token($usuario_id, $token_fcm, $nombre_dispositivo = '') {
        $tokens_existentes = self::obtener_tokens_con_metadata($usuario_id);

        // Eliminar duplicados del mismo token
        $tokens_filtrados = array_filter($tokens_existentes, function($entry) use ($token_fcm) {
            return $entry['token'] !== $token_fcm;
        });

        // Agregar el nuevo token
        $tokens_filtrados[] = [
            'token'       => $token_fcm,
            'device_name' => $nombre_dispositivo,
            'registered_at' => current_time('mysql'),
        ];

        return update_user_meta($usuario_id, self::META_KEY_FCM_TOKENS, wp_json_encode(array_values($tokens_filtrados)));
    }

    /**
     * Eliminar un token FCM de un usuario
     */
    public static function eliminar_token($usuario_id, $token_fcm) {
        $tokens_existentes = self::obtener_tokens_con_metadata($usuario_id);
        $tokens_filtrados = array_filter($tokens_existentes, function($entry) use ($token_fcm) {
            return $entry['token'] !== $token_fcm;
        });

        if (count($tokens_filtrados) === count($tokens_existentes)) {
            return false;
        }

        if (empty($tokens_filtrados)) {
            delete_user_meta($usuario_id, self::META_KEY_FCM_TOKENS);
        } else {
            update_user_meta($usuario_id, self::META_KEY_FCM_TOKENS, wp_json_encode(array_values($tokens_filtrados)));
        }

        return true;
    }

    /**
     * Obtener tokens FCM de un usuario (solo los strings de token)
     */
    public static function obtener_tokens($usuario_id) {
        $entradas = self::obtener_tokens_con_metadata($usuario_id);
        return array_map(function($entry) {
            return $entry['token'];
        }, $entradas);
    }

    /**
     * Obtener tokens FCM con metadata completa
     */
    private static function obtener_tokens_con_metadata($usuario_id) {
        $json_almacenado = get_user_meta($usuario_id, self::META_KEY_FCM_TOKENS, true);
        if (empty($json_almacenado)) {
            return [];
        }
        $tokens_decodificados = json_decode($json_almacenado, true);
        if (!is_array($tokens_decodificados)) {
            return [];
        }
        return $tokens_decodificados;
    }
}

// Inicializar
Flavor_Push_Token_Manager::init();
