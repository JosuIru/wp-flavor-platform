<?php
/**
 * Canal de Push Notifications via Firebase Cloud Messaging (HTTP v1 API)
 *
 * Envia notificaciones push a dispositivos moviles usando FCM.
 * Se integra con el sistema de canales de Flavor_Notification_Manager.
 *
 * @package FlavorPlatform
 * @subpackage Notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Push_Notification_Channel {

    const FCM_API_BASE_URL = 'https://fcm.googleapis.com/v1/projects/';
    const GOOGLE_OAUTH2_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    const TOKEN_CACHE_DURATION_SECONDS = 3000;
    const TOKEN_TRANSIENT_KEY = 'flavor_fcm_access_token';

    public function send($usuario_id, $titulo, $mensaje, $datos = []) {
        $tokens_fcm_del_usuario = Flavor_Push_Token_Manager::obtener_tokens($usuario_id);
        if (empty($tokens_fcm_del_usuario)) {
            flavor_platform_log(sprintf('Push: No hay tokens FCM para usuario %d', $usuario_id), 'info');
            return ['enviados' => 0, 'fallidos' => 0, 'sin_token' => true];
        }
        $access_token_oauth = self::get_access_token();
        if (!$access_token_oauth) {
            flavor_platform_log('Push: No se pudo obtener access token de Firebase', 'error');
            return ['enviados' => 0, 'fallidos' => count($tokens_fcm_del_usuario), 'error' => 'no_access_token'];
        }
        $configuracion_firebase = get_option('flavor_firebase_config', []);
        $proyecto_id_firebase = $configuracion_firebase['project_id'] ?? '';
        if (empty($proyecto_id_firebase)) {
            flavor_platform_log('Push: No se ha configurado el project_id de Firebase', 'error');
            return ['enviados' => 0, 'fallidos' => count($tokens_fcm_del_usuario), 'error' => 'no_project_id'];
        }
        $url_endpoint_fcm = self::FCM_API_BASE_URL . $proyecto_id_firebase . '/messages:send';
        $contador_enviados = 0;
        $contador_fallidos = 0;
        $tokens_invalidos_a_eliminar = [];
        foreach ($tokens_fcm_del_usuario as $token_dispositivo) {
            $resultado_envio = $this->enviar_a_dispositivo(
                $url_endpoint_fcm, $access_token_oauth, $token_dispositivo,
                $titulo, $mensaje, $datos
            );
            if ($resultado_envio['success']) {
                $contador_enviados++;
            } else {
                $contador_fallidos++;
                if ($resultado_envio['token_invalido']) {
                    $tokens_invalidos_a_eliminar[] = $token_dispositivo;
                }
            }
        }
        if (!empty($tokens_invalidos_a_eliminar)) {
            foreach ($tokens_invalidos_a_eliminar as $token_invalido) {
                Flavor_Push_Token_Manager::eliminar_token($usuario_id, $token_invalido);
            }
            flavor_platform_log(sprintf('Push: Se eliminaron %d tokens invalidos del usuario %d', count($tokens_invalidos_a_eliminar), $usuario_id), 'info');
        }
        return ['enviados' => $contador_enviados, 'fallidos' => $contador_fallidos, 'tokens_eliminados' => count($tokens_invalidos_a_eliminar)];
    }

    public function send_batch($usuario_ids, $titulo, $mensaje, $datos = []) {
        $resumen_total = ['total_usuarios' => count($usuario_ids), 'enviados' => 0, 'fallidos' => 0, 'sin_token' => 0, 'tokens_eliminados' => 0, 'resultados' => []];
        foreach ($usuario_ids as $usuario_id) {
            $resultado_usuario = $this->send($usuario_id, $titulo, $mensaje, $datos);
            $resumen_total['enviados'] += $resultado_usuario['enviados'];
            $resumen_total['fallidos'] += $resultado_usuario['fallidos'];
            $resumen_total['tokens_eliminados'] += $resultado_usuario['tokens_eliminados'] ?? 0;
            if (!empty($resultado_usuario['sin_token'])) { $resumen_total['sin_token']++; }
            $resumen_total['resultados'][$usuario_id] = $resultado_usuario;
        }
        return $resumen_total;
    }

    private function enviar_a_dispositivo($url_endpoint, $access_token, $token_fcm, $titulo, $mensaje, $datos = []) {
        $cuerpo_mensaje_fcm = ['message' => ['token' => $token_fcm, 'notification' => ['title' => $titulo, 'body' => $mensaje]]];
        if (!empty($datos)) {
            $datos_serializados_para_fcm = [];
            foreach ($datos as $clave_dato => $valor_dato) {
                $datos_serializados_para_fcm[$clave_dato] = is_string($valor_dato) ? $valor_dato : wp_json_encode($valor_dato);
            }
            $cuerpo_mensaje_fcm['message']['data'] = $datos_serializados_para_fcm;
        }
        $cuerpo_mensaje_fcm['message']['android'] = ['priority' => 'high', 'notification' => ['sound' => 'default', 'channel_id' => 'flavor_notifications', 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']];
        $cuerpo_mensaje_fcm['message']['apns'] = ['payload' => ['aps' => ['sound' => 'default', 'badge' => 1, 'content-available' => 1]]];
        $respuesta_http = wp_remote_post($url_endpoint, ['headers' => ['Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json; UTF-8'], 'body' => wp_json_encode($cuerpo_mensaje_fcm), 'timeout' => 15]);
        if (is_wp_error($respuesta_http)) {
            flavor_platform_log('Push FCM Error: ' . $respuesta_http->get_error_message(), 'error');
            return ['success' => false, 'token_invalido' => false, 'error' => $respuesta_http->get_error_message()];
        }
        $codigo_respuesta_http = wp_remote_retrieve_response_code($respuesta_http);
        $cuerpo_respuesta = wp_remote_retrieve_body($respuesta_http);
        $respuesta_decodificada = json_decode($cuerpo_respuesta, true);
        if ($codigo_respuesta_http === 200) {
            return ['success' => true, 'token_invalido' => false, 'message_id' => $respuesta_decodificada['name'] ?? ''];
        }
        $token_es_invalido = false;
        $codigo_error_fcm = $respuesta_decodificada['error']['details'][0]['errorCode'] ?? '';
        $estado_error_fcm = $respuesta_decodificada['error']['status'] ?? '';
        if ($codigo_error_fcm === 'UNREGISTERED' || $estado_error_fcm === 'NOT_FOUND' || ($estado_error_fcm === 'INVALID_ARGUMENT' && strpos($cuerpo_respuesta, 'token') !== false)) {
            $token_es_invalido = true;
        }
        flavor_platform_log(sprintf('Push FCM Error (HTTP %d): %s', $codigo_respuesta_http, $cuerpo_respuesta), 'error');
        return ['success' => false, 'token_invalido' => $token_es_invalido, 'error' => $cuerpo_respuesta, 'http_code' => $codigo_respuesta_http];
    }

    public static function get_access_token() {
        $token_cacheado = get_transient(self::TOKEN_TRANSIENT_KEY);
        if ($token_cacheado) { return $token_cacheado; }
        $configuracion_firebase = get_option('flavor_firebase_config', []);
        $json_service_account = $configuracion_firebase['service_account_json'] ?? '';
        if (empty($json_service_account)) {
            flavor_platform_log('Push: No se ha configurado el service account de Firebase', 'error');
            return false;
        }
        $datos_service_account = json_decode($json_service_account, true);
        if (!$datos_service_account) {
            flavor_platform_log('Push: El JSON del service account es invalido', 'error');
            return false;
        }
        $email_cuenta_servicio = $datos_service_account['client_email'] ?? '';
        $clave_privada_pem = $datos_service_account['private_key'] ?? '';
        if (empty($email_cuenta_servicio) || empty($clave_privada_pem)) {
            flavor_platform_log('Push: Faltan client_email o private_key en el service account', 'error');
            return false;
        }
        $timestamp_actual = time();
        $timestamp_expiracion = $timestamp_actual + 3600;
        $cabecera_jwt = self::base64url_encode(wp_json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload_jwt = self::base64url_encode(wp_json_encode(['iss' => $email_cuenta_servicio, 'scope' => self::FCM_SCOPE, 'aud' => self::GOOGLE_OAUTH2_TOKEN_URL, 'iat' => $timestamp_actual, 'exp' => $timestamp_expiracion]));
        $contenido_a_firmar = $cabecera_jwt . '.' . $payload_jwt;
        $firma_binaria = '';
        $resultado_firma = openssl_sign($contenido_a_firmar, $firma_binaria, $clave_privada_pem, OPENSSL_ALGO_SHA256);
        if (!$resultado_firma) {
            flavor_platform_log('Push: Error al firmar JWT - ' . openssl_error_string(), 'error');
            return false;
        }
        $firma_codificada = self::base64url_encode($firma_binaria);
        $jwt_completo = $contenido_a_firmar . '.' . $firma_codificada;
        $respuesta_oauth = wp_remote_post(self::GOOGLE_OAUTH2_TOKEN_URL, ['body' => ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt_completo], 'timeout' => 15]);
        if (is_wp_error($respuesta_oauth)) {
            flavor_platform_log('Push: Error al obtener access token - ' . $respuesta_oauth->get_error_message(), 'error');
            return false;
        }
        $cuerpo_respuesta_oauth = json_decode(wp_remote_retrieve_body($respuesta_oauth), true);
        $access_token_obtenido = $cuerpo_respuesta_oauth['access_token'] ?? '';
        if (empty($access_token_obtenido)) {
            flavor_platform_log('Push: Respuesta OAuth2 sin access_token - ' . wp_remote_retrieve_body($respuesta_oauth), 'error');
            return false;
        }
        set_transient(self::TOKEN_TRANSIENT_KEY, $access_token_obtenido, self::TOKEN_CACHE_DURATION_SECONDS);
        return $access_token_obtenido;
    }

    private static function base64url_encode($datos_a_codificar) {
        return rtrim(strtr(base64_encode($datos_a_codificar), '+/', '-_'), '=');
    }

    public function handle_push_from_queue($payload) {
        $usuario_id = $payload['user_id'] ?? 0;
        $titulo = $payload['title'] ?? '';
        $mensaje = $payload['message'] ?? '';
        if (!$usuario_id || !$titulo) { return false; }
        $datos_adicionales = ['notification_id' => $payload['notification_id'] ?? '', 'type' => $payload['type'] ?? 'general', 'link' => $payload['link'] ?? '', 'icon' => $payload['icon'] ?? ''];
        if (!empty($payload['data']) && is_array($payload['data'])) {
            $datos_adicionales = array_merge($datos_adicionales, $payload['data']);
        }
        $resultado_envio = $this->send($usuario_id, $titulo, $mensaje, $datos_adicionales);
        return $resultado_envio['enviados'] > 0;
    }
}
