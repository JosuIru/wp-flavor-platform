<?php
/**
 * Migration: Crear tablas de eventos
 *
 * Crea las tablas del módulo de eventos: eventos, inscripciones, etc.
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
 * Crear tablas de eventos
 */
class Migration_2024_01_01_000002_Create_Eventos_Tables extends Flavor_Migration_Base {

    /**
     * Nombre de la migration
     *
     * @var string
     */
    protected $migration_name = 'create_eventos_tables';

    /**
     * Descripción
     *
     * @var string
     */
    protected $description = 'Crear tablas del módulo de eventos';

    /**
     * Ejecuta la migration
     *
     * @return bool
     */
    public function up() {
        $success = true;

        // Tabla principal de eventos
        $columns = [
            $this->column_id(),
            'titulo varchar(255) NOT NULL',
            'descripcion text',
            'fecha_inicio datetime NOT NULL',
            'fecha_fin datetime DEFAULT NULL',
            'ubicacion varchar(255) DEFAULT NULL',
            'ubicacion_lat decimal(10,8) DEFAULT NULL',
            'ubicacion_lng decimal(11,8) DEFAULT NULL',
            'imagen varchar(500) DEFAULT NULL',
            'capacidad int(11) DEFAULT 0',
            'precio decimal(10,2) DEFAULT 0.00',
            'organizador_id bigint(20) unsigned DEFAULT NULL',
            'categoria_id bigint(20) unsigned DEFAULT NULL',
            $this->column_status(['borrador', 'publicado', 'cancelado', 'finalizado'], 'borrador'),
            'es_online tinyint(1) DEFAULT 0',
            'enlace_online varchar(500) DEFAULT NULL',
            'requiere_inscripcion tinyint(1) DEFAULT 1',
            'inscripcion_hasta datetime DEFAULT NULL',
            'meta_data longtext DEFAULT NULL',
            $this->column_created_at(),
            $this->column_updated_at(),
        ];

        $keys = [
            $this->key_primary(),
            $this->key_index('organizador_id'),
            $this->key_index('categoria_id'),
            $this->key_index('status'),
            $this->key_index('fecha_inicio'),
            $this->key_index(['fecha_inicio', 'status'], 'idx_fecha_status'),
        ];

        $success = $success && $this->create_table('eventos', $columns, $keys);

        // Tabla de categorías de eventos
        $columns_cat = [
            $this->column_id(),
            'nombre varchar(100) NOT NULL',
            'slug varchar(100) NOT NULL',
            'descripcion text DEFAULT NULL',
            'icono varchar(50) DEFAULT NULL',
            'color varchar(7) DEFAULT \'#3b82f6\'',
            'parent_id bigint(20) unsigned DEFAULT NULL',
            'orden int(11) DEFAULT 0',
        ];

        $keys_cat = [
            $this->key_primary(),
            $this->key_unique('slug'),
            $this->key_index('parent_id'),
        ];

        $success = $success && $this->create_table('eventos_categorias', $columns_cat, $keys_cat);

        // Tabla de inscripciones
        $columns_insc = [
            $this->column_id(),
            'evento_id bigint(20) unsigned NOT NULL',
            $this->column_user_id(),
            'nombre varchar(100) DEFAULT NULL',
            'email varchar(100) DEFAULT NULL',
            'telefono varchar(20) DEFAULT NULL',
            $this->column_status(['pendiente', 'confirmada', 'cancelada', 'asistio'], 'pendiente'),
            'num_acompanantes int(11) DEFAULT 0',
            'notas text DEFAULT NULL',
            'codigo_confirmacion varchar(32) DEFAULT NULL',
            'pago_id bigint(20) unsigned DEFAULT NULL',
            $this->column_created_at(),
            $this->column_updated_at(),
        ];

        $keys_insc = [
            $this->key_primary(),
            $this->key_index('evento_id'),
            $this->key_index('user_id'),
            $this->key_index('status'),
            $this->key_unique(['evento_id', 'user_id'], 'unique_evento_user'),
        ];

        $success = $success && $this->create_table('eventos_inscripciones', $columns_insc, $keys_insc);

        // Tabla de recordatorios
        $columns_rec = [
            $this->column_id(),
            'evento_id bigint(20) unsigned NOT NULL',
            $this->column_user_id(),
            'tipo_recordatorio varchar(20) DEFAULT \'email\'',
            'tiempo_antes int(11) DEFAULT 1440',
            'enviado tinyint(1) DEFAULT 0',
            'fecha_envio datetime DEFAULT NULL',
            $this->column_created_at(),
        ];

        $keys_rec = [
            $this->key_primary(),
            $this->key_index('evento_id'),
            $this->key_index('user_id'),
            $this->key_index('enviado'),
        ];

        $success = $success && $this->create_table('eventos_recordatorios', $columns_rec, $keys_rec);

        // Insertar categorías por defecto
        if ($success && !$this->has_default_categories()) {
            $this->seed_default_categories();
        }

        return $success;
    }

    /**
     * Verifica si ya existen categorías
     *
     * @return bool
     */
    private function has_default_categories() {
        $count = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}eventos_categorias"
        );
        return (int) $count > 0;
    }

    /**
     * Inserta categorías por defecto
     *
     * @return void
     */
    private function seed_default_categories() {
        $categories = [
            ['nombre' => 'Cultural', 'slug' => 'cultural', 'icono' => 'theater', 'color' => '#8b5cf6', 'orden' => 1],
            ['nombre' => 'Deportivo', 'slug' => 'deportivo', 'icono' => 'running', 'color' => '#10b981', 'orden' => 2],
            ['nombre' => 'Educativo', 'slug' => 'educativo', 'icono' => 'book', 'color' => '#3b82f6', 'orden' => 3],
            ['nombre' => 'Social', 'slug' => 'social', 'icono' => 'users', 'color' => '#f59e0b', 'orden' => 4],
            ['nombre' => 'Medioambiental', 'slug' => 'medioambiental', 'icono' => 'leaf', 'color' => '#22c55e', 'orden' => 5],
            ['nombre' => 'Gastronomía', 'slug' => 'gastronomia', 'icono' => 'utensils', 'color' => '#ef4444', 'orden' => 6],
            ['nombre' => 'Música', 'slug' => 'musica', 'icono' => 'music', 'color' => '#ec4899', 'orden' => 7],
            ['nombre' => 'Reunión', 'slug' => 'reunion', 'icono' => 'calendar', 'color' => '#6366f1', 'orden' => 8],
        ];

        $this->seed_data('eventos_categorias', $categories);
    }

    /**
     * Revierte la migration
     *
     * @return bool
     */
    public function down() {
        // Orden inverso por dependencias
        $this->drop_table('eventos_recordatorios');
        $this->drop_table('eventos_inscripciones');
        $this->drop_table('eventos_categorias');
        $this->drop_table('eventos');

        return true;
    }
}
