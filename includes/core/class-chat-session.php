<?php
/**
 * Gestión de sesiones de chat
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
     * Idioma de la sesión
     */
    private $language = 'es';

    /**
     * Mensajes de la conversación
     */
    private $messages = [];

    /**
     * ID de conversación en BD
     */
    private $conversation_id = null;

    /**
     * Datos de contexto
     */
    private $context = [];

    /**
     * Constructor
     *
     * @param string $session_id
     */
    public function __construct($session_id = null) {
        $this->session_id = $session_id ?: $this->generate_session_id();
        $this->load_session();
    }

    /**
     * Genera un ID de sesión único
     *
     * @return string
     */
    private function generate_session_id() {
        return 'fcia_' . wp_generate_password(32, false);
    }

    /**
     * Carga la sesión desde la BD
     */
    private function load_session() {
        global $wpdb;

        $table = $wpdb->prefix . 'flavor_chat_conversations';
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE session_id = %s ORDER BY id DESC LIMIT 1",
            $this->session_id
        ));

        if ($conversation) {
            $this->conversation_id = $conversation->id;
            $this->language = $conversation->language ?: 'es';

            // Cargar mensajes
            $this->load_messages();
        }
    }

    /**
     * Carga los mensajes de la conversación
     */
    private function load_messages() {
        global $wpdb;

        if (!$this->conversation_id) {
            return;
        }

        $table = $wpdb->prefix . 'flavor_chat_messages';
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT role, content FROM {$table} WHERE conversation_id = %d ORDER BY created_at ASC",
            $this->conversation_id
        ), ARRAY_A);

        $this->messages = $messages ?: [];
    }

    /**
     * Inicia una nueva conversación
     *
     * @param string $language
     * @return int ID de la conversación
     */
    public function start_conversation($language = 'es') {
        global $wpdb;

        // Validar idioma
        $supported_languages = ['es', 'eu', 'en', 'fr', 'ca'];
        if (!in_array($language, $supported_languages)) {
            $language = 'es';
        }

        $this->language = $language;

        $table = $wpdb->prefix . 'flavor_chat_conversations';

        // Verificar que la tabla existe
        $table_exists = Flavor_Chat_Helpers::tabla_existe($table);
        if (!$table_exists) {
            // Forzar creación de tablas
            $this->create_tables_if_missing();
        }

        $result = $wpdb->insert(
            $table,
            [
                'session_id' => $this->session_id,
                'language' => $language,
                'started_at' => current_time('mysql'),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'user_id' => get_current_user_id() ?: null,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d']
        );

        if ($result === false) {
            flavor_chat_ia_log('Error al crear conversación: ' . $wpdb->last_error, 'error');
        }

        $this->conversation_id = $wpdb->insert_id;
        $this->messages = [];

        return $this->conversation_id;
    }

    /**
     * Crea las tablas si no existen
     */
    private function create_tables_if_missing() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_conversations = $wpdb->prefix . 'flavor_chat_conversations';
        $sql = "CREATE TABLE IF NOT EXISTS $table_conversations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            language varchar(10) DEFAULT 'es',
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            ended_at datetime DEFAULT NULL,
            message_count int(11) DEFAULT 0,
            escalated tinyint(1) DEFAULT 0,
            escalation_reason text DEFAULT NULL,
            conversion_type varchar(50) DEFAULT NULL,
            conversion_value decimal(10,2) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY started_at (started_at)
        ) $charset_collate;";

        $table_messages = $wpdb->prefix . 'flavor_chat_messages';
        $sql2 = "CREATE TABLE IF NOT EXISTS $table_messages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) unsigned NOT NULL,
            role enum('user','assistant','system') NOT NULL,
            content text NOT NULL,
            tool_calls text DEFAULT NULL,
            tokens_used int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        dbDelta($sql2);
    }

    /**
     * Añade un mensaje a la conversación
     *
     * @param string $role 'user', 'assistant', 'system'
     * @param string $content
     * @param string $tool_calls JSON de tool calls si aplica
     * @return bool
     */
    public function add_message($role, $content, $tool_calls = null) {
        global $wpdb;

        if (!$this->conversation_id) {
            $this->start_conversation($this->language);
        }

        // Guardar en memoria
        $this->messages[] = [
            'role' => $role,
            'content' => $content,
        ];

        // Guardar en BD
        $result = $wpdb->insert(
            $wpdb->prefix . 'flavor_chat_messages',
            [
                'conversation_id' => $this->conversation_id,
                'role' => $role,
                'content' => $content,
                'tool_calls' => $tool_calls,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            flavor_chat_ia_log('Error al guardar mensaje: ' . $wpdb->last_error, 'error');
            return false;
        }

        // Actualizar contador de mensajes
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}flavor_chat_conversations SET message_count = message_count + 1 WHERE id = %d",
            $this->conversation_id
        ));

        return true;
    }

    /**
     * Obtiene los mensajes de la conversación
     *
     * @param int $limit Límite de mensajes (0 = todos)
     * @return array
     */
    public function get_messages($limit = 0) {
        if ($limit > 0 && count($this->messages) > $limit) {
            return array_slice($this->messages, -$limit);
        }
        return $this->messages;
    }

    /**
     * Obtiene el ID de la sesión
     *
     * @return string
     */
    public function get_session_id() {
        return $this->session_id;
    }

    /**
     * Obtiene el idioma de la sesión
     *
     * @return string
     */
    public function get_language() {
        return $this->language;
    }

    /**
     * Establece el idioma de la sesión
     *
     * @param string $language
     */
    public function set_language($language) {
        $this->language = $language;

        if ($this->conversation_id) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'flavor_chat_conversations',
                ['language' => $language],
                ['id' => $this->conversation_id],
                ['%s'],
                ['%d']
            );
        }
    }

    /**
     * Guarda datos de contexto
     *
     * @param string $key
     * @param mixed $value
     */
    public function set_context($key, $value) {
        $this->context[$key] = $value;
    }

    /**
     * Obtiene datos de contexto
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get_context($key, $default = null) {
        return $this->context[$key] ?? $default;
    }

    /**
     * Obtiene el ID de conversación
     *
     * @return int|null
     */
    public function get_conversation_id() {
        return $this->conversation_id;
    }

    /**
     * Marca la conversación como escalada
     *
     * @param string $reason
     */
    public function mark_escalated($reason) {
        global $wpdb;

        if ($this->conversation_id) {
            $wpdb->update(
                $wpdb->prefix . 'flavor_chat_conversations',
                [
                    'escalated' => 1,
                    'escalation_reason' => $reason,
                ],
                ['id' => $this->conversation_id],
                ['%d', '%s'],
                ['%d']
            );
        }
    }

    /**
     * Registra una conversión
     *
     * @param string $type Tipo de conversión
     * @param float $value Valor
     */
    public function record_conversion($type, $value = 0) {
        global $wpdb;

        if ($this->conversation_id) {
            $wpdb->update(
                $wpdb->prefix . 'flavor_chat_conversations',
                [
                    'conversion_type' => $type,
                    'conversion_value' => $value,
                ],
                ['id' => $this->conversation_id],
                ['%s', '%f'],
                ['%d']
            );
        }
    }

    /**
     * Finaliza la conversación
     */
    public function end_conversation() {
        global $wpdb;

        if ($this->conversation_id) {
            $wpdb->update(
                $wpdb->prefix . 'flavor_chat_conversations',
                ['ended_at' => current_time('mysql')],
                ['id' => $this->conversation_id],
                ['%s'],
                ['%d']
            );
        }
    }

    /**
     * Obtiene la IP del cliente
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Obtiene estadísticas de la sesión
     *
     * @return array
     */
    public function get_stats() {
        return [
            'session_id' => $this->session_id,
            'conversation_id' => $this->conversation_id,
            'language' => $this->language,
            'message_count' => count($this->messages),
        ];
    }
}
