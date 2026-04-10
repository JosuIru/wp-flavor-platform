<?php
/**
 * Migration Runner - Motor de ejecución de migrations
 *
 * Gestiona la ejecución, tracking y rollback de migrations
 * de base de datos del plugin.
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
 * Clase que ejecuta y gestiona las migrations
 */
class Flavor_Migration_Runner {

    /**
     * Instancia singleton
     *
     * @var Flavor_Migration_Runner|null
     */
    private static $instance = null;

    /**
     * Instancia de wpdb
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Nombre de la tabla de tracking
     *
     * @var string
     */
    private $tracking_table;

    /**
     * Directorio de migrations
     *
     * @var string
     */
    private $migrations_path;

    /**
     * Batch actual para agrupar migrations
     *
     * @var int
     */
    private $current_batch;

    /**
     * Resultados de la última ejecución
     *
     * @var array
     */
    private $last_run_results = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Migration_Runner
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tracking_table = $wpdb->prefix . 'flavor_migrations';
        $this->migrations_path = FLAVOR_PLATFORM_PATH . 'includes/database/migrations/';
    }

    /**
     * Inicializa el sistema de migrations
     *
     * Crea la tabla de tracking si no existe
     *
     * @return void
     */
    public function init() {
        $this->create_tracking_table();
        $this->current_batch = $this->get_last_batch() + 1;
    }

    /**
     * Crea la tabla de tracking de migrations
     *
     * @return void
     */
    private function create_tracking_table() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->tracking_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            migration varchar(255) NOT NULL,
            batch int(11) NOT NULL,
            executed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY migration (migration)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Obtiene el último número de batch
     *
     * @return int
     */
    private function get_last_batch() {
        $batch = $this->wpdb->get_var(
            "SELECT MAX(batch) FROM {$this->tracking_table}"
        );
        return (int) $batch;
    }

    /**
     * Ejecuta todas las migrations pendientes
     *
     * @return array Resultado con migrations ejecutadas
     */
    public function run_pending() {
        $this->last_run_results = [
            'executed' => [],
            'skipped'  => [],
            'errors'   => [],
        ];

        $pending_migrations = $this->get_pending_migrations();

        if (empty($pending_migrations)) {
            return $this->last_run_results;
        }

        foreach ($pending_migrations as $migration_file) {
            $result = $this->run_migration($migration_file);

            if ($result === true) {
                $this->last_run_results['executed'][] = $migration_file;
            } elseif ($result === false) {
                $this->last_run_results['errors'][] = $migration_file;
                // Detener en caso de error
                break;
            } else {
                $this->last_run_results['skipped'][] = $migration_file;
            }
        }

        return $this->last_run_results;
    }

    /**
     * Ejecuta una migration específica
     *
     * @param string $migration_file Nombre del archivo de migration
     * @return bool|null True si exitoso, false si error, null si skip
     */
    private function run_migration($migration_file) {
        $file_path = $this->migrations_path . $migration_file;

        if (!file_exists($file_path)) {
            $this->log("Migration no encontrada: {$migration_file}", 'error');
            return false;
        }

        // Cargar el archivo
        require_once $file_path;

        // Obtener nombre de clase desde el archivo
        $class_name = $this->get_class_name_from_file($migration_file);

        if (!class_exists($class_name)) {
            $this->log("Clase no encontrada: {$class_name}", 'error');
            return false;
        }

        // Instanciar y ejecutar
        $migration_instance = new $class_name();

        if (!$migration_instance instanceof Flavor_Migration_Base) {
            $this->log("La clase {$class_name} no extiende Flavor_Migration_Base", 'error');
            return false;
        }

        try {
            $this->log("Ejecutando migration: {$migration_file}", 'info');

            $result = $migration_instance->up();

            if ($result) {
                $this->mark_as_run($migration_file);
                $this->log("Migration completada: {$migration_file}", 'info');
                return true;
            } else {
                $this->log("Migration falló: {$migration_file}", 'error');
                return false;
            }
        } catch (Exception $exception) {
            $this->log("Error en migration {$migration_file}: " . $exception->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Revierte la última batch de migrations
     *
     * @return array Resultado con migrations revertidas
     */
    public function rollback_last() {
        $this->last_run_results = [
            'rolled_back' => [],
            'errors'      => [],
        ];

        $last_batch = $this->get_last_batch();

        if ($last_batch === 0) {
            return $this->last_run_results;
        }

        $migrations_to_rollback = $this->get_migrations_by_batch($last_batch);

        // Revertir en orden inverso
        $migrations_to_rollback = array_reverse($migrations_to_rollback);

        foreach ($migrations_to_rollback as $migration) {
            $result = $this->rollback_migration($migration->migration);

            if ($result) {
                $this->last_run_results['rolled_back'][] = $migration->migration;
            } else {
                $this->last_run_results['errors'][] = $migration->migration;
            }
        }

        return $this->last_run_results;
    }

    /**
     * Revierte una migration específica
     *
     * @param string $migration_file Nombre del archivo
     * @return bool
     */
    private function rollback_migration($migration_file) {
        $file_path = $this->migrations_path . $migration_file;

        if (!file_exists($file_path)) {
            $this->log("Migration no encontrada para rollback: {$migration_file}", 'error');
            return false;
        }

        require_once $file_path;

        $class_name = $this->get_class_name_from_file($migration_file);

        if (!class_exists($class_name)) {
            return false;
        }

        $migration_instance = new $class_name();

        try {
            $this->log("Revirtiendo migration: {$migration_file}", 'info');

            $result = $migration_instance->down();

            if ($result) {
                $this->mark_as_not_run($migration_file);
                $this->log("Rollback completado: {$migration_file}", 'info');
                return true;
            }

            return false;
        } catch (Exception $exception) {
            $this->log("Error en rollback {$migration_file}: " . $exception->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Revierte todas las migrations
     *
     * @return array
     */
    public function rollback_all() {
        $results = [
            'rolled_back' => [],
            'errors'      => [],
        ];

        while ($this->get_last_batch() > 0) {
            $batch_result = $this->rollback_last();
            $results['rolled_back'] = array_merge($results['rolled_back'], $batch_result['rolled_back']);
            $results['errors'] = array_merge($results['errors'], $batch_result['errors']);

            if (!empty($batch_result['errors'])) {
                break;
            }
        }

        return $results;
    }

    /**
     * Obtiene el estado de todas las migrations
     *
     * @return array Lista de migrations con su estado
     */
    public function get_status() {
        $all_migrations = $this->get_all_migration_files();
        $executed_migrations = $this->get_executed_migrations();
        $executed_names = wp_list_pluck($executed_migrations, 'migration');

        $status = [];

        foreach ($all_migrations as $migration_file) {
            $is_executed = in_array($migration_file, $executed_names, true);
            $batch = 0;
            $executed_at = null;

            if ($is_executed) {
                $index = array_search($migration_file, $executed_names, true);
                if ($index !== false && isset($executed_migrations[$index])) {
                    $batch = $executed_migrations[$index]->batch;
                    $executed_at = $executed_migrations[$index]->executed_at;
                }
            }

            $status[] = [
                'migration'   => $migration_file,
                'status'      => $is_executed ? 'executed' : 'pending',
                'batch'       => $batch,
                'executed_at' => $executed_at,
            ];
        }

        return $status;
    }

    /**
     * Obtiene migrations pendientes
     *
     * @return array Lista de archivos de migration pendientes
     */
    public function get_pending_migrations() {
        $all_migrations = $this->get_all_migration_files();
        $executed_migrations = $this->get_executed_migrations();
        $executed_names = wp_list_pluck($executed_migrations, 'migration');

        return array_diff($all_migrations, $executed_names);
    }

    /**
     * Obtiene todos los archivos de migration
     *
     * @return array Lista de archivos ordenados
     */
    private function get_all_migration_files() {
        if (!is_dir($this->migrations_path)) {
            return [];
        }

        $files = glob($this->migrations_path . '*.php');
        $migration_files = [];

        foreach ($files as $file) {
            $migration_files[] = basename($file);
        }

        sort($migration_files);

        return $migration_files;
    }

    /**
     * Obtiene migrations ejecutadas
     *
     * @return array Registros de la tabla de tracking
     */
    private function get_executed_migrations() {
        $results = $this->wpdb->get_results(
            "SELECT * FROM {$this->tracking_table} ORDER BY batch, id"
        );

        return $results ?: [];
    }

    /**
     * Obtiene migrations de un batch específico
     *
     * @param int $batch Número de batch
     * @return array
     */
    private function get_migrations_by_batch($batch) {
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->tracking_table} WHERE batch = %d ORDER BY id",
            $batch
        ));

        return $results ?: [];
    }

    /**
     * Marca una migration como ejecutada
     *
     * @param string $migration_file Nombre del archivo
     * @return void
     */
    private function mark_as_run($migration_file) {
        $this->wpdb->insert(
            $this->tracking_table,
            [
                'migration' => $migration_file,
                'batch'     => $this->current_batch,
            ],
            ['%s', '%d']
        );
    }

    /**
     * Elimina el registro de una migration ejecutada
     *
     * @param string $migration_file Nombre del archivo
     * @return void
     */
    private function mark_as_not_run($migration_file) {
        $this->wpdb->delete(
            $this->tracking_table,
            ['migration' => $migration_file],
            ['%s']
        );
    }

    /**
     * Obtiene el nombre de clase desde el nombre del archivo
     *
     * Convierte 2024_01_01_000001_create_core_tables.php
     * a Migration_2024_01_01_000001_Create_Core_Tables
     *
     * @param string $filename Nombre del archivo
     * @return string Nombre de la clase
     */
    private function get_class_name_from_file($filename) {
        // Quitar extensión
        $name = str_replace('.php', '', $filename);

        // Convertir a PascalCase
        $parts = explode('_', $name);
        $class_name = 'Migration';

        foreach ($parts as $part) {
            $class_name .= '_' . ucfirst($part);
        }

        return $class_name;
    }

    /**
     * Registra un mensaje de log
     *
     * @param string $message Mensaje
     * @param string $level   Nivel
     * @return void
     */
    private function log($message, $level = 'info') {
        if (function_exists('flavor_platform_log')) {
            flavor_platform_log("[Migration Runner] {$message}", $level, 'database');
        }
    }

    /**
     * Verifica si el sistema de migrations está inicializado
     *
     * @return bool
     */
    public function is_initialized() {
        $table_exists = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $this->tracking_table)
        );

        return $table_exists === $this->tracking_table;
    }

    /**
     * Obtiene el conteo de migrations
     *
     * @return array ['total' => int, 'executed' => int, 'pending' => int]
     */
    public function get_counts() {
        $all = count($this->get_all_migration_files());
        $executed = count($this->get_executed_migrations());

        return [
            'total'    => $all,
            'executed' => $executed,
            'pending'  => $all - $executed,
        ];
    }

    /**
     * Obtiene los resultados de la última ejecución
     *
     * @return array
     */
    public function get_last_run_results() {
        return $this->last_run_results;
    }

    /**
     * Ejecuta una migration específica por nombre
     *
     * @param string $migration_name Nombre de la migration
     * @return bool
     */
    public function run_specific($migration_name) {
        $file_name = $migration_name;

        // Añadir extensión si no la tiene
        if (!str_ends_with($file_name, '.php')) {
            $file_name .= '.php';
        }

        // Verificar que existe
        if (!file_exists($this->migrations_path . $file_name)) {
            $this->log("Migration específica no encontrada: {$file_name}", 'error');
            return false;
        }

        // Verificar que no se ha ejecutado
        $executed = $this->get_executed_migrations();
        $executed_names = wp_list_pluck($executed, 'migration');

        if (in_array($file_name, $executed_names, true)) {
            $this->log("Migration ya ejecutada: {$file_name}", 'info');
            return true;
        }

        return $this->run_migration($file_name);
    }

    /**
     * Resetea todas las migrations y vuelve a ejecutar
     *
     * @return array
     */
    public function refresh() {
        $rollback_results = $this->rollback_all();
        $run_results = $this->run_pending();

        return [
            'rolled_back' => $rollback_results['rolled_back'],
            'executed'    => $run_results['executed'],
            'errors'      => array_merge($rollback_results['errors'], $run_results['errors']),
        ];
    }
}
