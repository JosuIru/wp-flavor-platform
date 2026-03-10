<?php
/**
 * Migration: Crear tablas de chat
 *
 * Crea las tablas core del chat: conversaciones, mensajes y escalaciones.
 *
 * @package FlavorPlatform
 * @subpackage Database\Migrations
 * @since 3.3.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crear tablas de chat
 */
class Migration_2024_01_01_000001_Create_Chat_Tables extends Flavor_Migration_Base {

    /**
     * Nombre de la migration
     *
     * @var string
     */
    protected $migration_name = 'create_chat_tables';

    /**
     * Descripción
     *
     * @var string
     */
    protected $description = 'Crear tablas de chat (conversaciones, mensajes, escalaciones)';

    /**
     * Ejecuta la migration
     *
     * @return bool
     */
    public function up() {
        $success = true;

        // Tabla de conversaciones
        $success = $success && $this->create_conversations_table();

        // Tabla de mensajes
        $success = $success && $this->create_messages_table();

        // Tabla de escalaciones
        $success = $success && $this->create_escalations_table();

        return $success;
    }

    /**
     * Crea tabla de conversaciones
     *
     * @return bool
     */
    private function create_conversations_table() {
        // Usamos wpdb directamente para la tabla sin prefijo flavor_
        global $wpdb;
        $table_name = $wpdb->prefix . 'flavor_chat_conversations';

        $columns = [
            $this->column_id(),
            'session_id varchar(64) NOT NULL',
            'language varchar(10) DEFAULT \'es\'',
            'started_at datetime DEFAULT CURRENT_TIMESTAMP',
            'ended_at datetime DEFAULT NULL',
            'message_count int(11) DEFAULT 0',
            'escalated tinyint(1) DEFAULT 0',
            'escalation_reason text DEFAULT NULL',
            'conversion_type varchar(50) DEFAULT NULL',
            'conversion_value decimal(10,2) DEFAULT NULL',
            'ip_address varchar(45) DEFAULT NULL',
            'user_agent text DEFAULT NULL',
            $this->column_user_id(true),
        ];

        $keys = [
            $this->key_primary(),
            $this->key_index('session_id'),
            $this->key_index('started_at'),
            $this->key_index('user_id'),
        ];

        // Crear usando SQL directo para mantener nombre de tabla
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $columns_sql = implode(",\n            ", $columns);
        $keys_sql = ",\n            " . implode(",\n            ", $keys);

        $sql = "CREATE TABLE {$table_name} (
            {$columns_sql}{$keys_sql}
        ) {$this->charset_collate};";

        dbDelta($sql);

        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
    }

    /**
     * Crea tabla de mensajes
     *
     * @return bool
     */
    private function create_messages_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flavor_chat_messages';

        $columns = [
            $this->column_id(),
            'conversation_id bigint(20) unsigned NOT NULL',
            "role enum('user','assistant','system') NOT NULL",
            'content text NOT NULL',
            'tool_calls text DEFAULT NULL',
            'tokens_used int(11) DEFAULT 0',
            $this->column_created_at(),
        ];

        $keys = [
            $this->key_primary(),
            $this->key_index('conversation_id'),
            $this->key_index('created_at'),
        ];

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $columns_sql = implode(",\n            ", $columns);
        $keys_sql = ",\n            " . implode(",\n            ", $keys);

        $sql = "CREATE TABLE {$table_name} (
            {$columns_sql}{$keys_sql}
        ) {$this->charset_collate};";

        dbDelta($sql);

        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
    }

    /**
     * Crea tabla de escalaciones
     *
     * @return bool
     */
    private function create_escalations_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flavor_chat_escalations';

        $columns = [
            $this->column_id(),
            'conversation_id bigint(20) unsigned NOT NULL',
            'reason text NOT NULL',
            'summary text NOT NULL',
            'contact_method varchar(20) DEFAULT NULL',
            "status enum('pending','contacted','resolved') DEFAULT 'pending'",
            $this->column_created_at(),
            'resolved_at datetime DEFAULT NULL',
            'notes text DEFAULT NULL',
        ];

        $keys = [
            $this->key_primary(),
            $this->key_index('conversation_id'),
            $this->key_index('status'),
        ];

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $columns_sql = implode(",\n            ", $columns);
        $keys_sql = ",\n            " . implode(",\n            ", $keys);

        $sql = "CREATE TABLE {$table_name} (
            {$columns_sql}{$keys_sql}
        ) {$this->charset_collate};";

        dbDelta($sql);

        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
    }

    /**
     * Revierte la migration
     *
     * @return bool
     */
    public function down() {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'flavor_chat_escalations',
            $wpdb->prefix . 'flavor_chat_messages',
            $wpdb->prefix . 'flavor_chat_conversations',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        return true;
    }
}
