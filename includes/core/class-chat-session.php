<?php
/**
 * Gestión de sesiones del chat
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Session {

    /**
     * ID de la sesión
     */
    private $session_id;

    /**
     * ID de la conversación
     */
    private $conversation_id;

    /**
     * Mensajes de la conversación
     */
    private $messages = [];

    /**
     * Idioma de la conversación
     */
    private $language = 'es';

    /**
     * Constructor
     *
     * @param string|null $session_id ID de sesión existente o null para crear nueva
     */
    public function __construct($session_id = null) {
        if ($session_id) {
            $this->session_id = $session_id;
            $this->load_session();
        } else {
            $this->session_id = $this->generate_session_id();
        }
    }

    /**
     * Genera un ID de sesión único
     *
     * @return string
     */
    private function generate_session_id() {
        return 'fcia_' . wp_generate_password(16, false);
    }

    /**
     * Carga una sesión existente desde transient
     */
    private function load_session() {
        $data = get_transient('flavor_chat_session_' . $this->session_id);

        if ($data) {
            $this->conversation_id = $data['conversation_id'] ?? null;
            $this->messages = $data['messages'] ?? [];
            $this->language = $data['language'] ?? 'es';
        }
    }

    /**
     * Guarda la sesión en transient
     */
    private function save_session() {
        $data = [
            'conversation_id' => $this->conversation_id,
            'messages' => $this->messages,
            'language' => $this->language,
        ];

        // Guardar por 24 horas
        set_transient('flavor_chat_session_' . $this->session_id, $data, DAY_IN_SECONDS);
    }

    /**
     * Inicia una nueva conversación
     *
     * @param string $language
     * @return int ID de conversación
     */
    public function start_conversation($language = 'es') {
        $this->language = $language;
        $this->conversation_id = time() . '_' . wp_generate_password(8, false);
        $this->messages = [];

        $this->save_session();

        return $this->conversation_id;
    }

    /**
     * Obtiene el ID de sesión
     *
     * @return string
     */
    public function get_session_id() {
        return $this->session_id;
    }

    /**
     * Obtiene el ID de conversación
     *
     * @return string|null
     */
    public function get_conversation_id() {
        return $this->conversation_id;
    }

    /**
     * Añade un mensaje a la conversación
     *
     * @param string $role 'user' o 'assistant'
     * @param string $content Contenido del mensaje
     */
    public function add_message($role, $content) {
        $this->messages[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time(),
        ];

        // Limitar historial a últimos 20 mensajes para no sobrecargar
        if (count($this->messages) > 20) {
            $this->messages = array_slice($this->messages, -20);
        }

        $this->save_session();
    }

    /**
     * Obtiene todos los mensajes de la conversación
     *
     * @return array
     */
    public function get_messages() {
        return $this->messages;
    }

    /**
     * Obtiene el idioma de la conversación
     *
     * @return string
     */
    public function get_language() {
        return $this->language;
    }

    /**
     * Limpia la conversación
     */
    public function clear() {
        $this->messages = [];
        $this->conversation_id = null;
        delete_transient('flavor_chat_session_' . $this->session_id);
    }
}
