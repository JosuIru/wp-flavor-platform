<?php
/**
 * Facade/Wrapper para Push Notifications
 *
 * Proporciona una interfaz singleton para enviar notificaciones push
 * a través del canal FCM (Firebase Cloud Messaging).
 *
 * @package FlavorPlatform
 * @subpackage Notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Facade para Push Notifications
 */
class Flavor_Push_Notifications {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Instancia del canal de push
     *
     * @var Flavor_Push_Notification_Channel|null
     */
    private $channel = null;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $this->channel = new Flavor_Push_Notification_Channel();
    }

    /**
     * Obtener instancia singleton
     *
     * @return self
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Verificar si push notifications está disponible
     *
     * @return bool
     */
    public static function is_available() {
        $config = get_option('flavor_firebase_config', []);
        return !empty($config['project_id']) && !empty($config['service_account']);
    }

    /**
     * Enviar notificación push a múltiples usuarios
     *
     * @param array $usuario_ids Array de IDs de usuarios
     * @param string $titulo Título de la notificación
     * @param string $mensaje Cuerpo del mensaje
     * @param array $datos Datos adicionales para la notificación
     * @return array Resultados del envío
     */
    public function enviar_a_usuarios($usuario_ids, $titulo, $mensaje, $datos = []) {
        if (!is_array($usuario_ids)) {
            $usuario_ids = [$usuario_ids];
        }

        $resultados = [
            'enviados' => 0,
            'fallidos' => 0,
            'sin_token' => 0,
            'errores' => [],
        ];

        foreach ($usuario_ids as $usuario_id) {
            $resultado = $this->channel->send($usuario_id, $titulo, $mensaje, $datos);

            if (isset($resultado['sin_token']) && $resultado['sin_token']) {
                $resultados['sin_token']++;
                continue;
            }

            $resultados['enviados'] += $resultado['enviados'] ?? 0;
            $resultados['fallidos'] += $resultado['fallidos'] ?? 0;

            if (!empty($resultado['error'])) {
                $resultados['errores'][$usuario_id] = $resultado['error'];
            }
        }

        return $resultados;
    }

    /**
     * Enviar notificación push a un único usuario
     *
     * @param int $usuario_id ID del usuario
     * @param string $titulo Título de la notificación
     * @param string $mensaje Cuerpo del mensaje
     * @param array $datos Datos adicionales
     * @return array Resultado del envío
     */
    public function enviar($usuario_id, $titulo, $mensaje, $datos = []) {
        return $this->channel->send($usuario_id, $titulo, $mensaje, $datos);
    }

    /**
     * Enviar notificación a todos los usuarios con token push
     *
     * @param string $titulo Título de la notificación
     * @param string $mensaje Cuerpo del mensaje
     * @param array $datos Datos adicionales
     * @return array Resultados del envío
     */
    public function enviar_a_todos($titulo, $mensaje, $datos = []) {
        $usuarios_con_tokens = Flavor_Push_Token_Manager::obtener_todos_usuarios_con_tokens();

        if (empty($usuarios_con_tokens)) {
            return [
                'enviados' => 0,
                'fallidos' => 0,
                'error' => 'No hay usuarios con tokens push registrados',
            ];
        }

        return $this->enviar_a_usuarios($usuarios_con_tokens, $titulo, $mensaje, $datos);
    }
}
