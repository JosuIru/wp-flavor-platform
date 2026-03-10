<?php
/**
 * Migration: Crear tablas de reservas
 *
 * Crea las tablas del módulo de reservas de espacios y recursos.
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
 * Crear tablas de reservas
 */
class Migration_2024_01_01_000003_Create_Reservas_Tables extends Flavor_Migration_Base {

    /**
     * Nombre de la migration
     *
     * @var string
     */
    protected $migration_name = 'create_reservas_tables';

    /**
     * Descripción
     *
     * @var string
     */
    protected $description = 'Crear tablas del módulo de reservas';

    /**
     * Ejecuta la migration
     *
     * @return bool
     */
    public function up() {
        $success = true;

        // Tabla de espacios/recursos
        $columns_espacios = [
            $this->column_id(),
            'nombre varchar(255) NOT NULL',
            'descripcion text DEFAULT NULL',
            'tipo varchar(50) DEFAULT \'espacio\'',
            'capacidad int(11) DEFAULT 0',
            'imagen varchar(500) DEFAULT NULL',
            'ubicacion varchar(255) DEFAULT NULL',
            'equipamiento text DEFAULT NULL',
            'precio_hora decimal(10,2) DEFAULT 0.00',
            'precio_dia decimal(10,2) DEFAULT 0.00',
            'requiere_aprobacion tinyint(1) DEFAULT 0',
            'responsable_id bigint(20) unsigned DEFAULT NULL',
            'horario_apertura time DEFAULT \'09:00:00\'',
            'horario_cierre time DEFAULT \'21:00:00\'',
            'dias_disponibles varchar(20) DEFAULT \'1,2,3,4,5\'',
            'tiempo_minimo int(11) DEFAULT 60',
            'tiempo_maximo int(11) DEFAULT 480',
            'antelacion_minima int(11) DEFAULT 24',
            'antelacion_maxima int(11) DEFAULT 720',
            $this->column_status(['activo', 'inactivo', 'mantenimiento'], 'activo'),
            'meta_data longtext DEFAULT NULL',
            $this->column_created_at(),
            $this->column_updated_at(),
        ];

        $keys_espacios = [
            $this->key_primary(),
            $this->key_index('tipo'),
            $this->key_index('status'),
            $this->key_index('responsable_id'),
        ];

        $success = $success && $this->create_table('espacios', $columns_espacios, $keys_espacios);

        // Tabla de reservas
        $columns_reservas = [
            $this->column_id(),
            'espacio_id bigint(20) unsigned NOT NULL',
            $this->column_user_id(),
            'titulo varchar(255) DEFAULT NULL',
            'descripcion text DEFAULT NULL',
            'fecha date NOT NULL',
            'hora_inicio time NOT NULL',
            'hora_fin time NOT NULL',
            'num_personas int(11) DEFAULT 1',
            $this->column_status(['pendiente', 'aprobada', 'rechazada', 'cancelada', 'completada'], 'pendiente'),
            'motivo_rechazo text DEFAULT NULL',
            'precio_total decimal(10,2) DEFAULT 0.00',
            'pago_id bigint(20) unsigned DEFAULT NULL',
            'codigo_reserva varchar(32) DEFAULT NULL',
            'notas text DEFAULT NULL',
            'aprobado_por bigint(20) unsigned DEFAULT NULL',
            'fecha_aprobacion datetime DEFAULT NULL',
            $this->column_created_at(),
            $this->column_updated_at(),
        ];

        $keys_reservas = [
            $this->key_primary(),
            $this->key_index('espacio_id'),
            $this->key_index('user_id'),
            $this->key_index('status'),
            $this->key_index('fecha'),
            $this->key_index(['espacio_id', 'fecha'], 'idx_espacio_fecha'),
            $this->key_unique('codigo_reserva'),
        ];

        $success = $success && $this->create_table('reservas', $columns_reservas, $keys_reservas);

        // Tabla de bloqueos (horarios no disponibles)
        $columns_bloqueos = [
            $this->column_id(),
            'espacio_id bigint(20) unsigned NOT NULL',
            'fecha_inicio date NOT NULL',
            'fecha_fin date NOT NULL',
            'hora_inicio time DEFAULT NULL',
            'hora_fin time DEFAULT NULL',
            'motivo varchar(255) DEFAULT NULL',
            'creado_por bigint(20) unsigned DEFAULT NULL',
            $this->column_created_at(),
        ];

        $keys_bloqueos = [
            $this->key_primary(),
            $this->key_index('espacio_id'),
            $this->key_index(['espacio_id', 'fecha_inicio', 'fecha_fin'], 'idx_espacio_fechas'),
        ];

        $success = $success && $this->create_table('reservas_bloqueos', $columns_bloqueos, $keys_bloqueos);

        // Tabla de configuración de espacios
        $columns_config = [
            $this->column_id(),
            'espacio_id bigint(20) unsigned NOT NULL',
            'config_key varchar(100) NOT NULL',
            'config_value longtext DEFAULT NULL',
            $this->column_updated_at(),
        ];

        $keys_config = [
            $this->key_primary(),
            $this->key_unique(['espacio_id', 'config_key'], 'unique_espacio_config'),
        ];

        $success = $success && $this->create_table('espacios_config', $columns_config, $keys_config);

        return $success;
    }

    /**
     * Revierte la migration
     *
     * @return bool
     */
    public function down() {
        $this->drop_table('espacios_config');
        $this->drop_table('reservas_bloqueos');
        $this->drop_table('reservas');
        $this->drop_table('espacios');

        return true;
    }
}
