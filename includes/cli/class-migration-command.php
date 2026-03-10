<?php
/**
 * Migration Command - Comandos WP-CLI para migrations
 *
 * Proporciona comandos de línea de comandos para gestionar
 * las migrations de base de datos.
 *
 * @package FlavorPlatform
 * @subpackage CLI
 * @since 3.3.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Solo cargar si WP-CLI está disponible
if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * Gestiona las migrations de base de datos de Flavor Platform
 *
 * ## EXAMPLES
 *
 *     # Ejecutar todas las migrations pendientes
 *     $ wp flavor migrate
 *
 *     # Ver estado de las migrations
 *     $ wp flavor migrate:status
 *
 *     # Revertir última batch
 *     $ wp flavor migrate:rollback
 *
 *     # Revertir todas las migrations
 *     $ wp flavor migrate:reset
 *
 *     # Refrescar (reset + migrate)
 *     $ wp flavor migrate:refresh
 *
 * @package FlavorPlatform
 */
class Flavor_Migration_Command {

    /**
     * Instancia del runner
     *
     * @var Flavor_Migration_Runner
     */
    private $runner;

    /**
     * Constructor
     */
    public function __construct() {
        // Cargar dependencias
        $base_path = FLAVOR_CHAT_IA_PATH . 'includes/database/';
        require_once $base_path . 'class-migration-base.php';
        require_once $base_path . 'class-migration-runner.php';

        $this->runner = Flavor_Migration_Runner::get_instance();
        $this->runner->init();
    }

    /**
     * Ejecuta todas las migrations pendientes
     *
     * ## OPTIONS
     *
     * [--force]
     * : Forzar ejecución incluso si no hay pendientes
     *
     * [--step=<number>]
     * : Número de migrations a ejecutar
     *
     * ## EXAMPLES
     *
     *     $ wp flavor migrate
     *     Running migrations...
     *     ✓ 2024_01_01_000001_create_core_tables
     *     ✓ 2024_01_01_000002_create_eventos_tables
     *     Migrations completed: 2 executed, 0 errors
     *
     * @param array $args       Argumentos posicionales
     * @param array $assoc_args Argumentos nombrados
     * @return void
     */
    public function migrate($args, $assoc_args) {
        WP_CLI::log('Running migrations...');
        WP_CLI::log('');

        $results = $this->runner->run_pending();

        if (empty($results['executed']) && empty($results['errors'])) {
            WP_CLI::success('No hay migrations pendientes.');
            return;
        }

        foreach ($results['executed'] as $migration) {
            WP_CLI::log(WP_CLI::colorize('%G✓%n ') . $migration);
        }

        foreach ($results['errors'] as $migration) {
            WP_CLI::log(WP_CLI::colorize('%R✗%n ') . $migration);
        }

        WP_CLI::log('');

        $executed_count = count($results['executed']);
        $error_count = count($results['errors']);

        if ($error_count > 0) {
            WP_CLI::error("Migrations: {$executed_count} ejecutadas, {$error_count} errores", false);
        } else {
            WP_CLI::success("Migrations completadas: {$executed_count} ejecutadas");
        }
    }

    /**
     * Muestra el estado de todas las migrations
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Formato de salida (table, json, csv)
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - csv
     * ---
     *
     * ## EXAMPLES
     *
     *     $ wp flavor migrate:status
     *     +----------------------------------------+-----------+-------+---------------------+
     *     | Migration                              | Status    | Batch | Executed At         |
     *     +----------------------------------------+-----------+-------+---------------------+
     *     | 2024_01_01_000001_create_core_tables   | executed  | 1     | 2024-01-01 10:00:00 |
     *     | 2024_01_01_000002_create_eventos       | pending   | -     | -                   |
     *     +----------------------------------------+-----------+-------+---------------------+
     *
     *     $ wp flavor migrate:status --format=json
     *
     * @subcommand status
     * @param array $args       Argumentos posicionales
     * @param array $assoc_args Argumentos nombrados
     * @return void
     */
    public function status($args, $assoc_args) {
        $format = $assoc_args['format'] ?? 'table';
        $status = $this->runner->get_status();

        if (empty($status)) {
            WP_CLI::log('No se encontraron migrations.');
            return;
        }

        // Preparar datos para tabla
        $items = [];
        foreach ($status as $item) {
            $items[] = [
                'Migration'   => $item['migration'],
                'Status'      => $item['status'],
                'Batch'       => $item['batch'] ?: '-',
                'Executed At' => $item['executed_at'] ?: '-',
            ];
        }

        // Mostrar resumen
        $counts = $this->runner->get_counts();
        WP_CLI::log('');
        WP_CLI::log(sprintf(
            'Total: %d | Ejecutadas: %d | Pendientes: %d',
            $counts['total'],
            $counts['executed'],
            $counts['pending']
        ));
        WP_CLI::log('');

        WP_CLI\Utils\format_items($format, $items, ['Migration', 'Status', 'Batch', 'Executed At']);
    }

    /**
     * Revierte la última batch de migrations
     *
     * ## OPTIONS
     *
     * [--step=<number>]
     * : Número de batches a revertir
     *
     * [--force]
     * : No pedir confirmación
     *
     * ## EXAMPLES
     *
     *     $ wp flavor migrate:rollback
     *     Rolling back last batch...
     *     ✓ 2024_01_01_000002_create_eventos_tables
     *     Rollback completed: 1 reverted
     *
     * @subcommand rollback
     * @param array $args       Argumentos posicionales
     * @param array $assoc_args Argumentos nombrados
     * @return void
     */
    public function rollback($args, $assoc_args) {
        $force = isset($assoc_args['force']);

        if (!$force) {
            WP_CLI::confirm('¿Seguro que quieres revertir la última batch de migrations?');
        }

        WP_CLI::log('Rolling back last batch...');
        WP_CLI::log('');

        $results = $this->runner->rollback_last();

        if (empty($results['rolled_back']) && empty($results['errors'])) {
            WP_CLI::log('No hay migrations para revertir.');
            return;
        }

        foreach ($results['rolled_back'] as $migration) {
            WP_CLI::log(WP_CLI::colorize('%Y↩%n ') . $migration);
        }

        foreach ($results['errors'] as $migration) {
            WP_CLI::log(WP_CLI::colorize('%R✗%n ') . $migration);
        }

        WP_CLI::log('');

        $count = count($results['rolled_back']);
        $errors = count($results['errors']);

        if ($errors > 0) {
            WP_CLI::error("Rollback: {$count} revertidas, {$errors} errores", false);
        } else {
            WP_CLI::success("Rollback completado: {$count} revertidas");
        }
    }

    /**
     * Revierte todas las migrations
     *
     * ## OPTIONS
     *
     * [--force]
     * : No pedir confirmación
     *
     * ## EXAMPLES
     *
     *     $ wp flavor migrate:reset
     *     Are you sure you want to reset ALL migrations? [y/n] y
     *     Resetting all migrations...
     *     ✓ Reverted 5 migrations
     *
     * @subcommand reset
     * @param array $args       Argumentos posicionales
     * @param array $assoc_args Argumentos nombrados
     * @return void
     */
    public function reset($args, $assoc_args) {
        $force = isset($assoc_args['force']);

        if (!$force) {
            WP_CLI::confirm(
                WP_CLI::colorize('%R¡CUIDADO!%n ¿Seguro que quieres revertir TODAS las migrations? Esto puede eliminar datos.')
            );
        }

        WP_CLI::log('Resetting all migrations...');
        WP_CLI::log('');

        $results = $this->runner->rollback_all();

        $count = count($results['rolled_back']);
        $errors = count($results['errors']);

        if ($count > 0) {
            WP_CLI::log("Revertidas: {$count} migrations");
        }

        if ($errors > 0) {
            WP_CLI::error("Reset completado con {$errors} errores", false);
        } else {
            WP_CLI::success('Reset completado');
        }
    }

    /**
     * Refresca todas las migrations (reset + migrate)
     *
     * ## OPTIONS
     *
     * [--force]
     * : No pedir confirmación
     *
     * [--seed]
     * : Ejecutar seeders después de refresh
     *
     * ## EXAMPLES
     *
     *     $ wp flavor migrate:refresh
     *     Are you sure? [y/n] y
     *     Rolling back all migrations...
     *     Running all migrations...
     *     Refresh completed!
     *
     * @subcommand refresh
     * @param array $args       Argumentos posicionales
     * @param array $assoc_args Argumentos nombrados
     * @return void
     */
    public function refresh($args, $assoc_args) {
        $force = isset($assoc_args['force']);

        if (!$force) {
            WP_CLI::confirm(
                WP_CLI::colorize('%R¡CUIDADO!%n ¿Seguro que quieres hacer refresh? Esto revierte y re-ejecuta todas las migrations.')
            );
        }

        WP_CLI::log('Refreshing migrations...');
        WP_CLI::log('');

        $results = $this->runner->refresh();

        // Mostrar rollbacks
        if (!empty($results['rolled_back'])) {
            WP_CLI::log('Rolled back:');
            foreach ($results['rolled_back'] as $migration) {
                WP_CLI::log(WP_CLI::colorize('%Y  ↩%n ') . $migration);
            }
            WP_CLI::log('');
        }

        // Mostrar ejecutadas
        if (!empty($results['executed'])) {
            WP_CLI::log('Executed:');
            foreach ($results['executed'] as $migration) {
                WP_CLI::log(WP_CLI::colorize('%G  ✓%n ') . $migration);
            }
            WP_CLI::log('');
        }

        // Mostrar errores
        if (!empty($results['errors'])) {
            WP_CLI::log('Errors:');
            foreach ($results['errors'] as $migration) {
                WP_CLI::log(WP_CLI::colorize('%R  ✗%n ') . $migration);
            }
            WP_CLI::log('');
        }

        $rolled = count($results['rolled_back']);
        $executed = count($results['executed']);
        $errors = count($results['errors']);

        if ($errors > 0) {
            WP_CLI::error("Refresh: {$rolled} revertidas, {$executed} ejecutadas, {$errors} errores", false);
        } else {
            WP_CLI::success("Refresh completado: {$rolled} revertidas, {$executed} ejecutadas");
        }
    }

    /**
     * Crea un nuevo archivo de migration
     *
     * ## OPTIONS
     *
     * <name>
     * : Nombre descriptivo de la migration (ej: create_users_table)
     *
     * [--table=<table>]
     * : Nombre de la tabla para generar template de create
     *
     * ## EXAMPLES
     *
     *     $ wp flavor migrate:make create_notifications_table
     *     Created: 2024_03_09_143022_create_notifications_table.php
     *
     *     $ wp flavor migrate:make add_status_to_eventos --table=eventos
     *
     * @subcommand make
     * @param array $args       Argumentos posicionales
     * @param array $assoc_args Argumentos nombrados
     * @return void
     */
    public function make($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Debes proporcionar un nombre para la migration.');
            return;
        }

        $name = sanitize_file_name($args[0]);
        $table = $assoc_args['table'] ?? null;

        // Generar timestamp
        $timestamp = gmdate('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = FLAVOR_CHAT_IA_PATH . "includes/database/migrations/{$filename}";

        // Generar nombre de clase
        $class_name = 'Migration_' . $timestamp . '_' . str_replace(' ', '_', ucwords(str_replace('_', ' ', $name)));

        // Template de contenido
        $is_create_table = str_contains($name, 'create_') && $table;
        $template = $this->get_migration_template($class_name, $name, $table, $is_create_table);

        // Escribir archivo
        $result = file_put_contents($filepath, $template);

        if ($result === false) {
            WP_CLI::error("No se pudo crear el archivo: {$filepath}");
            return;
        }

        WP_CLI::success("Migration creada: {$filename}");
        WP_CLI::log("Ruta: {$filepath}");
    }

    /**
     * Genera el template para una nueva migration
     *
     * @param string      $class_name     Nombre de la clase
     * @param string      $name           Nombre descriptivo
     * @param string|null $table          Nombre de la tabla
     * @param bool        $is_create      Si es migration de creación
     * @return string
     */
    private function get_migration_template($class_name, $name, $table, $is_create) {
        $description = str_replace('_', ' ', ucfirst($name));
        $table_name = $table ?: 'table_name';

        if ($is_create) {
            $up_content = <<<PHP
        \$columns = [
            \$this->column_id(),
            'name varchar(255) NOT NULL',
            \$this->column_user_id(true),
            \$this->column_status(['pending', 'active', 'inactive'], 'pending'),
            \$this->column_created_at(),
            \$this->column_updated_at(),
        ];

        \$keys = [
            \$this->key_primary(),
            \$this->key_index('user_id'),
            \$this->key_index('status'),
        ];

        return \$this->create_table('{$table_name}', \$columns, \$keys);
PHP;
            $down_content = "        return \$this->drop_table('{$table_name}');";
        } else {
            $up_content = <<<PHP
        // TODO: Implementar migration
        // Ejemplos:
        // \$this->add_column('table', 'column', 'varchar(255) DEFAULT NULL');
        // \$this->add_index('table', 'idx_column', 'column');
        return true;
PHP;
            $down_content = <<<PHP
        // TODO: Revertir migration
        // Ejemplos:
        // \$this->drop_column('table', 'column');
        // \$this->drop_index('table', 'idx_column');
        return true;
PHP;
        }

        return <<<PHP
<?php
/**
 * Migration: {$description}
 *
 * @package FlavorPlatform
 * @subpackage Database\\Migrations
 * @since 3.3.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * {$description}
 */
class {$class_name} extends Flavor_Migration_Base {

    /**
     * Nombre de la migration
     *
     * @var string
     */
    protected \$migration_name = '{$name}';

    /**
     * Descripción
     *
     * @var string
     */
    protected \$description = '{$description}';

    /**
     * Ejecuta la migration
     *
     * @return bool
     */
    public function up() {
{$up_content}
    }

    /**
     * Revierte la migration
     *
     * @return bool
     */
    public function down() {
{$down_content}
    }
}

PHP;
    }

    /**
     * Ejecuta una migration específica
     *
     * ## OPTIONS
     *
     * <migration>
     * : Nombre del archivo de migration
     *
     * ## EXAMPLES
     *
     *     $ wp flavor migrate:run 2024_01_01_000001_create_core_tables
     *
     * @subcommand run
     * @param array $args       Argumentos posicionales
     * @param array $assoc_args Argumentos nombrados
     * @return void
     */
    public function run($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Debes especificar el nombre de la migration.');
            return;
        }

        $migration = $args[0];
        WP_CLI::log("Ejecutando: {$migration}");

        $result = $this->runner->run_specific($migration);

        if ($result) {
            WP_CLI::success("Migration ejecutada: {$migration}");
        } else {
            WP_CLI::error("Error ejecutando migration: {$migration}");
        }
    }
}

// Registrar comando
WP_CLI::add_command('flavor migrate', 'Flavor_Migration_Command', [
    'shortdesc' => 'Ejecuta migrations pendientes de Flavor Platform',
]);

WP_CLI::add_command('flavor migrate:status', ['Flavor_Migration_Command', 'status'], [
    'shortdesc' => 'Muestra el estado de las migrations',
]);

WP_CLI::add_command('flavor migrate:rollback', ['Flavor_Migration_Command', 'rollback'], [
    'shortdesc' => 'Revierte la última batch de migrations',
]);

WP_CLI::add_command('flavor migrate:reset', ['Flavor_Migration_Command', 'reset'], [
    'shortdesc' => 'Revierte todas las migrations',
]);

WP_CLI::add_command('flavor migrate:refresh', ['Flavor_Migration_Command', 'refresh'], [
    'shortdesc' => 'Revierte y re-ejecuta todas las migrations',
]);

WP_CLI::add_command('flavor migrate:make', ['Flavor_Migration_Command', 'make'], [
    'shortdesc' => 'Crea un nuevo archivo de migration',
]);

WP_CLI::add_command('flavor migrate:run', ['Flavor_Migration_Command', 'run'], [
    'shortdesc' => 'Ejecuta una migration específica',
]);
