<?php
/**
 * Migration: Añadir índices compuestos para rendimiento
 *
 * Añade índices compuestos a tablas con queries frecuentes
 * para mejorar el rendimiento de listados y filtros.
 *
 * @package FlavorPlatform
 * @subpackage Database\Migrations
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Migration_2024_04_17_000001_Add_Performance_Indexes extends Flavor_Migration_Base {

    protected $migration_name = 'add_performance_indexes';
    protected $description = 'Añade índices compuestos para queries frecuentes';

    /**
     * Índices a crear en tablas con prefijo flavor_
     */
    private $flavor_indexes = [
        // Eventos: listado público ordenado por fecha
        'eventos' => [
            'idx_estado_fecha_inicio' => ['estado', 'fecha_inicio'],
        ],
        // Eventos: filtrado avanzado por tipo
        'eventos' => [
            'idx_estado_fecha_inicio' => ['estado', 'fecha_inicio'],
            'idx_estado_fecha_tipo' => ['estado', 'fecha_inicio', 'tipo'],
        ],
        // Inscripciones: conteo de plazas disponibles
        'eventos_inscripciones' => [
            'idx_evento_estado' => ['evento_id', 'estado'],
        ],
        // Grupos de consumo: panel de usuario
        'gc_consumidores' => [
            'idx_usuario_estado' => ['usuario_id', 'estado'],
        ],
        // Lista de compra: carga de carrito
        'gc_lista_compra' => [
            'idx_usuario_producto' => ['usuario_id', 'producto_id'],
        ],
        // Entregas: contador de pendientes
        'gc_entregas' => [
            'idx_usuario_estado_pago' => ['usuario_id', 'estado_pago'],
        ],
    ];

    /**
     * Índices a crear en tablas sin prefijo flavor_ (restaurant)
     */
    private $restaurant_indexes = [
        'restaurant_reservations' => [
            'idx_status_reservation_date' => ['status', 'reservation_date'],
        ],
        'restaurant_orders' => [
            'idx_status_created_at' => ['status', 'created_at'],
        ],
    ];

    /**
     * Ejecuta la migration
     */
    public function up() {
        $success = true;

        // Índices en tablas flavor_
        foreach ($this->flavor_indexes as $table => $indexes) {
            foreach ($indexes as $index_name => $columns) {
                if (!$this->add_index($table, $index_name, $columns)) {
                    $this->log("Error al crear índice {$index_name} en {$table}", 'error');
                    $success = false;
                } else {
                    $this->log("Índice {$index_name} creado en {$table}", 'info');
                }
            }
        }

        // Índices en tablas restaurant (sin prefijo flavor_)
        foreach ($this->restaurant_indexes as $table => $indexes) {
            foreach ($indexes as $index_name => $columns) {
                if (!$this->add_restaurant_index($table, $index_name, $columns)) {
                    $this->log("Error al crear índice {$index_name} en {$table}", 'error');
                    $success = false;
                } else {
                    $this->log("Índice {$index_name} creado en {$table}", 'info');
                }
            }
        }

        return $success;
    }

    /**
     * Revierte la migration
     */
    public function down() {
        $success = true;

        // Eliminar índices de tablas flavor_
        foreach ($this->flavor_indexes as $table => $indexes) {
            foreach ($indexes as $index_name => $columns) {
                if (!$this->drop_index($table, $index_name)) {
                    $success = false;
                }
            }
        }

        // Eliminar índices de tablas restaurant
        foreach ($this->restaurant_indexes as $table => $indexes) {
            foreach ($indexes as $index_name => $columns) {
                if (!$this->drop_restaurant_index($table, $index_name)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Verifica si un índice existe en tabla restaurant (sin prefijo flavor_)
     */
    private function restaurant_index_exists($table_name, $index_name) {
        $full_table_name = $this->wpdb->prefix . $table_name;
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
     * Verifica si una tabla restaurant existe
     */
    private function restaurant_table_exists($table_name) {
        $full_table_name = $this->wpdb->prefix . $table_name;
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $full_table_name)
        );
        return $result === $full_table_name;
    }

    /**
     * Añade índice a tabla restaurant (sin prefijo flavor_)
     */
    private function add_restaurant_index($table_name, $index_name, $columns) {
        if (!$this->restaurant_table_exists($table_name)) {
            $this->log("Tabla {$table_name} no existe, omitiendo índice", 'info');
            return true;
        }

        if ($this->restaurant_index_exists($table_name, $index_name)) {
            return true;
        }

        $full_table_name = $this->wpdb->prefix . $table_name;
        $columns_list = is_array($columns) ? implode(', ', $columns) : $columns;

        $result = $this->wpdb->query(
            "ALTER TABLE {$full_table_name} ADD INDEX {$index_name} ({$columns_list})"
        );

        return $result !== false;
    }

    /**
     * Elimina índice de tabla restaurant
     */
    private function drop_restaurant_index($table_name, $index_name) {
        if (!$this->restaurant_table_exists($table_name)) {
            return true;
        }

        if (!$this->restaurant_index_exists($table_name, $index_name)) {
            return true;
        }

        $full_table_name = $this->wpdb->prefix . $table_name;
        $result = $this->wpdb->query(
            "ALTER TABLE {$full_table_name} DROP INDEX {$index_name}"
        );

        return $result !== false;
    }
}
