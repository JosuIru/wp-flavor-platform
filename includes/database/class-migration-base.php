<?php
/**
 * Migration Base - Clase abstracta base para migrations
 *
 * Proporciona la estructura y helpers para crear migrations
 * de base de datos de forma consistente.
 *
 * @package FlavorPlatform
 * @subpackage Database
 * @since 3.3.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase abstracta base para migrations de base de datos
 */
abstract class Flavor_Migration_Base {

    /**
     * Instancia de wpdb
     *
     * @var wpdb
     */
    protected $wpdb;

    /**
     * Prefijo de tablas
     *
     * @var string
     */
    protected $prefix;

    /**
     * Charset y collate para tablas
     *
     * @var string
     */
    protected $charset_collate;

    /**
     * Nombre de la migration
     *
     * @var string
     */
    protected $migration_name = '';

    /**
     * Descripción de la migration
     *
     * @var string
     */
    protected $description = '';

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    /**
     * Ejecuta la migration (crear/modificar estructura)
     *
     * @return bool True si exitoso
     */
    abstract public function up();

    /**
     * Revierte la migration
     *
     * @return bool True si exitoso
     */
    abstract public function down();

    /**
     * Obtiene el nombre de la migration
     *
     * @return string
     */
    public function get_name() {
        return $this->migration_name;
    }

    /**
     * Obtiene la descripción de la migration
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    // =========================================================================
    // HELPERS PARA CREAR TABLAS
    // =========================================================================

    /**
     * Crea una tabla usando dbDelta
     *
     * @param string $table_name Nombre de la tabla (sin prefijo)
     * @param array  $columns    Array de definiciones de columnas
     * @param array  $keys       Array de definiciones de keys/índices
     * @return bool True si exitoso
     */
    protected function create_table($table_name, $columns, $keys = []) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $full_table_name = $this->prefix . $table_name;
        $columns_sql = implode(",\n            ", $columns);
        $keys_sql = !empty($keys) ? ",\n            " . implode(",\n            ", $keys) : '';

        $sql = "CREATE TABLE {$full_table_name} (
            {$columns_sql}{$keys_sql}
        ) {$this->charset_collate};";

        dbDelta($sql);

        // Verificar que la tabla existe
        return $this->table_exists($table_name);
    }

    /**
     * Elimina una tabla
     *
     * @param string $table_name Nombre de la tabla (sin prefijo)
     * @return bool True si exitoso
     */
    protected function drop_table($table_name) {
        $full_table_name = $this->prefix . $table_name;
        $this->wpdb->query("DROP TABLE IF EXISTS {$full_table_name}");
        return !$this->table_exists($table_name);
    }

    /**
     * Verifica si una tabla existe
     *
     * @param string $table_name Nombre de la tabla (sin prefijo)
     * @return bool
     */
    protected function table_exists($table_name) {
        $full_table_name = $this->prefix . $table_name;
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $full_table_name)
        );
        return $result === $full_table_name;
    }

    /**
     * Verifica si una columna existe en una tabla
     *
     * @param string $table_name  Nombre de la tabla (sin prefijo)
     * @param string $column_name Nombre de la columna
     * @return bool
     */
    protected function column_exists($table_name, $column_name) {
        $full_table_name = $this->prefix . $table_name;
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = %s
             AND TABLE_NAME = %s
             AND COLUMN_NAME = %s",
            DB_NAME,
            $full_table_name,
            $column_name
        ));
        return (int) $result > 0;
    }

    /**
     * Añade una columna a una tabla existente
     *
     * @param string $table_name    Nombre de la tabla (sin prefijo)
     * @param string $column_name   Nombre de la columna
     * @param string $column_def    Definición de la columna (tipo, default, etc)
     * @param string $after_column  Columna después de la cual insertar (opcional)
     * @return bool True si exitoso
     */
    protected function add_column($table_name, $column_name, $column_def, $after_column = null) {
        if ($this->column_exists($table_name, $column_name)) {
            return true; // Ya existe
        }

        $full_table_name = $this->prefix . $table_name;
        $after_clause = $after_column ? " AFTER {$after_column}" : '';

        $result = $this->wpdb->query(
            "ALTER TABLE {$full_table_name} ADD COLUMN {$column_name} {$column_def}{$after_clause}"
        );

        return $result !== false;
    }

    /**
     * Elimina una columna de una tabla
     *
     * @param string $table_name  Nombre de la tabla (sin prefijo)
     * @param string $column_name Nombre de la columna
     * @return bool True si exitoso
     */
    protected function drop_column($table_name, $column_name) {
        if (!$this->column_exists($table_name, $column_name)) {
            return true; // No existe
        }

        $full_table_name = $this->prefix . $table_name;
        $result = $this->wpdb->query(
            "ALTER TABLE {$full_table_name} DROP COLUMN {$column_name}"
        );

        return $result !== false;
    }

    /**
     * Modifica una columna existente
     *
     * @param string $table_name  Nombre de la tabla (sin prefijo)
     * @param string $column_name Nombre de la columna
     * @param string $column_def  Nueva definición
     * @return bool True si exitoso
     */
    protected function modify_column($table_name, $column_name, $column_def) {
        if (!$this->column_exists($table_name, $column_name)) {
            return false;
        }

        $full_table_name = $this->prefix . $table_name;
        $result = $this->wpdb->query(
            "ALTER TABLE {$full_table_name} MODIFY COLUMN {$column_name} {$column_def}"
        );

        return $result !== false;
    }

    /**
     * Renombra una columna
     *
     * @param string $table_name     Nombre de la tabla (sin prefijo)
     * @param string $old_name       Nombre actual de la columna
     * @param string $new_name       Nuevo nombre
     * @param string $column_def     Definición de la columna
     * @return bool True si exitoso
     */
    protected function rename_column($table_name, $old_name, $new_name, $column_def) {
        if (!$this->column_exists($table_name, $old_name)) {
            return false;
        }

        $full_table_name = $this->prefix . $table_name;
        $result = $this->wpdb->query(
            "ALTER TABLE {$full_table_name} CHANGE {$old_name} {$new_name} {$column_def}"
        );

        return $result !== false;
    }

    // =========================================================================
    // HELPERS PARA ÍNDICES
    // =========================================================================

    /**
     * Verifica si un índice existe
     *
     * @param string $table_name Nombre de la tabla (sin prefijo)
     * @param string $index_name Nombre del índice
     * @return bool
     */
    protected function index_exists($table_name, $index_name) {
        $full_table_name = $this->prefix . $table_name;
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = %s
             AND TABLE_NAME = %s
             AND INDEX_NAME = %s",
            DB_NAME,
            $full_table_name,
            $index_name
        ));
        return (int) $result > 0;
    }

    /**
     * Añade un índice a una tabla
     *
     * @param string       $table_name Nombre de la tabla (sin prefijo)
     * @param string       $index_name Nombre del índice
     * @param string|array $columns    Columna(s) a indexar
     * @param bool         $unique     Si es un índice único
     * @return bool True si exitoso
     */
    protected function add_index($table_name, $index_name, $columns, $unique = false) {
        if ($this->index_exists($table_name, $index_name)) {
            return true;
        }

        $full_table_name = $this->prefix . $table_name;
        $columns_list = is_array($columns) ? implode(', ', $columns) : $columns;
        $unique_keyword = $unique ? 'UNIQUE ' : '';

        $result = $this->wpdb->query(
            "ALTER TABLE {$full_table_name} ADD {$unique_keyword}INDEX {$index_name} ({$columns_list})"
        );

        return $result !== false;
    }

    /**
     * Elimina un índice de una tabla
     *
     * @param string $table_name Nombre de la tabla (sin prefijo)
     * @param string $index_name Nombre del índice
     * @return bool True si exitoso
     */
    protected function drop_index($table_name, $index_name) {
        if (!$this->index_exists($table_name, $index_name)) {
            return true;
        }

        $full_table_name = $this->prefix . $table_name;
        $result = $this->wpdb->query(
            "ALTER TABLE {$full_table_name} DROP INDEX {$index_name}"
        );

        return $result !== false;
    }

    // =========================================================================
    // HELPERS PARA FOREIGN KEYS
    // =========================================================================

    /**
     * Añade una foreign key
     *
     * @param string $table_name     Nombre de la tabla (sin prefijo)
     * @param string $fk_name        Nombre de la FK
     * @param string $column         Columna local
     * @param string $ref_table      Tabla referenciada (sin prefijo)
     * @param string $ref_column     Columna referenciada
     * @param string $on_delete      Acción ON DELETE (CASCADE, SET NULL, etc)
     * @param string $on_update      Acción ON UPDATE
     * @return bool True si exitoso
     */
    protected function add_foreign_key(
        $table_name,
        $fk_name,
        $column,
        $ref_table,
        $ref_column,
        $on_delete = 'CASCADE',
        $on_update = 'CASCADE'
    ) {
        $full_table_name = $this->prefix . $table_name;
        $full_ref_table = $this->prefix . $ref_table;

        $result = $this->wpdb->query(
            "ALTER TABLE {$full_table_name}
             ADD CONSTRAINT {$fk_name}
             FOREIGN KEY ({$column}) REFERENCES {$full_ref_table}({$ref_column})
             ON DELETE {$on_delete} ON UPDATE {$on_update}"
        );

        return $result !== false;
    }

    /**
     * Elimina una foreign key
     *
     * @param string $table_name Nombre de la tabla (sin prefijo)
     * @param string $fk_name    Nombre de la FK
     * @return bool True si exitoso
     */
    protected function drop_foreign_key($table_name, $fk_name) {
        $full_table_name = $this->prefix . $table_name;

        $result = $this->wpdb->query(
            "ALTER TABLE {$full_table_name} DROP FOREIGN KEY {$fk_name}"
        );

        return $result !== false;
    }

    // =========================================================================
    // HELPERS PARA DATOS
    // =========================================================================

    /**
     * Inserta datos iniciales en una tabla
     *
     * @param string $table_name Nombre de la tabla (sin prefijo)
     * @param array  $data       Array de filas a insertar
     * @return int Número de filas insertadas
     */
    protected function seed_data($table_name, $data) {
        $full_table_name = $this->prefix . $table_name;
        $inserted = 0;

        foreach ($data as $row) {
            $result = $this->wpdb->insert($full_table_name, $row);
            if ($result) {
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Trunca una tabla (elimina todos los datos)
     *
     * @param string $table_name Nombre de la tabla (sin prefijo)
     * @return bool True si exitoso
     */
    protected function truncate_table($table_name) {
        $full_table_name = $this->prefix . $table_name;
        $result = $this->wpdb->query("TRUNCATE TABLE {$full_table_name}");
        return $result !== false;
    }

    // =========================================================================
    // HELPERS PARA DEFINICIONES COMUNES DE COLUMNAS
    // =========================================================================

    /**
     * Definición de columna ID autoincremental
     *
     * @return string
     */
    protected function column_id() {
        return 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT';
    }

    /**
     * Definición de columna timestamps (created_at)
     *
     * @return string
     */
    protected function column_created_at() {
        return 'created_at datetime DEFAULT CURRENT_TIMESTAMP';
    }

    /**
     * Definición de columna timestamps (updated_at)
     *
     * @return string
     */
    protected function column_updated_at() {
        return 'updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
    }

    /**
     * Definición de columna user_id (FK a users)
     *
     * @param bool $nullable Si permite NULL
     * @return string
     */
    protected function column_user_id($nullable = false) {
        $null_clause = $nullable ? 'DEFAULT NULL' : 'NOT NULL';
        return "user_id bigint(20) unsigned {$null_clause}";
    }

    /**
     * Definición de columna status enum común
     *
     * @param array  $values  Valores del enum
     * @param string $default Valor por defecto
     * @return string
     */
    protected function column_status($values = ['pending', 'active', 'inactive'], $default = 'pending') {
        $values_str = "'" . implode("','", $values) . "'";
        return "status enum({$values_str}) DEFAULT '{$default}'";
    }

    /**
     * Definición de PRIMARY KEY
     *
     * @param string $column Columna para la PK (default: id)
     * @return string
     */
    protected function key_primary($column = 'id') {
        return "PRIMARY KEY ({$column})";
    }

    /**
     * Definición de KEY índice simple
     *
     * @param string|array $columns Columna(s) a indexar
     * @param string       $name    Nombre del índice (opcional)
     * @return string
     */
    protected function key_index($columns, $name = null) {
        $columns_list = is_array($columns) ? implode(', ', $columns) : $columns;
        $index_name = $name ?: (is_array($columns) ? implode('_', $columns) : $columns);
        return "KEY {$index_name} ({$columns_list})";
    }

    /**
     * Definición de UNIQUE KEY
     *
     * @param string|array $columns Columna(s)
     * @param string       $name    Nombre del índice (opcional)
     * @return string
     */
    protected function key_unique($columns, $name = null) {
        $columns_list = is_array($columns) ? implode(', ', $columns) : $columns;
        $index_name = $name ?: 'unique_' . (is_array($columns) ? implode('_', $columns) : $columns);
        return "UNIQUE KEY {$index_name} ({$columns_list})";
    }

    // =========================================================================
    // LOGGING
    // =========================================================================

    /**
     * Registra mensaje de log
     *
     * @param string $message Mensaje
     * @param string $level   Nivel (info, error, debug)
     * @return void
     */
    protected function log($message, $level = 'info') {
        if (function_exists('flavor_chat_ia_log')) {
            flavor_chat_ia_log("[Migration: {$this->migration_name}] {$message}", $level, 'database');
        }
    }
}
