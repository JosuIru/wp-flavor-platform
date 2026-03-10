<?php
/**
 * Migration: Crear tablas ecológicas
 *
 * Tablas para: huertos urbanos, compostaje, reciclaje, energía comunitaria, carpooling
 *
 * @package FlavorPlatform
 * @subpackage Database\Migrations
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Migration_2024_01_01_000007_Create_Ecological_Tables extends Flavor_Migration_Base {

    protected $migration_name = 'create_ecological_tables';
    protected $description = 'Crear tablas ecológicas (huertos, compostaje, reciclaje, energía, carpooling)';

    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'flavor_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ─── Huertos ───
        $sql = "CREATE TABLE {$prefix}huertos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            ubicacion varchar(255) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            superficie_m2 decimal(10,2) DEFAULT NULL,
            num_parcelas int(11) DEFAULT 0,
            parcelas_disponibles int(11) DEFAULT 0,
            imagen_url varchar(500) DEFAULT '',
            horario text,
            normativa longtext,
            coordinador_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY coordinador_id (coordinador_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Parcelas de huerto ───
        $sql = "CREATE TABLE {$prefix}huerto_parcelas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) unsigned NOT NULL,
            codigo varchar(20) NOT NULL,
            superficie_m2 decimal(6,2) DEFAULT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            fecha_asignacion date DEFAULT NULL,
            fecha_fin date DEFAULT NULL,
            estado varchar(20) DEFAULT 'disponible',
            notas text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY huerto_codigo (huerto_id, codigo),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Cosechas ───
        $sql = "CREATE TABLE {$prefix}huerto_cosechas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            parcela_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            producto varchar(100) NOT NULL,
            cantidad_kg decimal(6,2) NOT NULL,
            fecha_cosecha date NOT NULL,
            compartido tinyint(1) DEFAULT 0,
            notas text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parcela_id (parcela_id),
            KEY usuario_id (usuario_id),
            KEY fecha_cosecha (fecha_cosecha)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Composteras ───
        $sql = "CREATE TABLE {$prefix}composteras (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            ubicacion varchar(255) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            capacidad_litros int(11) DEFAULT NULL,
            tipo varchar(50) DEFAULT 'comunitaria',
            responsable_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'activa',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY responsable_id (responsable_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Aportaciones de compostaje ───
        $sql = "CREATE TABLE {$prefix}compostaje_aportaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            compostera_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo_residuo varchar(50) NOT NULL,
            cantidad_kg decimal(6,2) NOT NULL,
            fecha_aportacion datetime DEFAULT CURRENT_TIMESTAMP,
            notas text,
            PRIMARY KEY (id),
            KEY compostera_id (compostera_id),
            KEY usuario_id (usuario_id),
            KEY fecha_aportacion (fecha_aportacion)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Puntos de reciclaje ───
        $sql = "CREATE TABLE {$prefix}puntos_reciclaje (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            ubicacion varchar(255) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            tipos_residuos text,
            horario text,
            responsable_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY responsable_id (responsable_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Registros de reciclaje ───
        $sql = "CREATE TABLE {$prefix}reciclaje_registros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            punto_id bigint(20) unsigned DEFAULT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo_residuo varchar(50) NOT NULL,
            cantidad_kg decimal(6,2) NOT NULL,
            puntos_obtenidos int(11) DEFAULT 0,
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY punto_id (punto_id),
            KEY usuario_id (usuario_id),
            KEY tipo_residuo (tipo_residuo)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Comunidades energéticas ───
        $sql = "CREATE TABLE {$prefix}comunidades_energia (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            tipo varchar(50) DEFAULT 'solar',
            potencia_instalada_kw decimal(10,2) DEFAULT NULL,
            ubicacion varchar(255) DEFAULT '',
            miembros_count int(11) DEFAULT 0,
            coordinador_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'activa',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY coordinador_id (coordinador_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Miembros de comunidad energética ───
        $sql = "CREATE TABLE {$prefix}energia_miembros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            cuota_participacion decimal(5,2) DEFAULT 0.00,
            fecha_alta date NOT NULL,
            estado varchar(20) DEFAULT 'activo',
            PRIMARY KEY (id),
            UNIQUE KEY comunidad_usuario (comunidad_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Producción energética ───
        $sql = "CREATE TABLE {$prefix}energia_produccion (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned NOT NULL,
            fecha date NOT NULL,
            produccion_kwh decimal(10,2) NOT NULL,
            consumo_kwh decimal(10,2) DEFAULT 0.00,
            excedente_kwh decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY comunidad_id (comunidad_id),
            KEY fecha (fecha)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Carpooling: Viajes ───
        $sql = "CREATE TABLE {$prefix}carpooling_viajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conductor_id bigint(20) unsigned NOT NULL,
            origen varchar(255) NOT NULL,
            origen_lat decimal(10,8) DEFAULT NULL,
            origen_lng decimal(11,8) DEFAULT NULL,
            destino varchar(255) NOT NULL,
            destino_lat decimal(10,8) DEFAULT NULL,
            destino_lng decimal(11,8) DEFAULT NULL,
            fecha_salida datetime NOT NULL,
            hora_flexibilidad int(11) DEFAULT 0,
            plazas_totales int(11) NOT NULL,
            plazas_disponibles int(11) NOT NULL,
            precio_plaza decimal(6,2) DEFAULT 0.00,
            descripcion text,
            vehiculo varchar(100) DEFAULT '',
            es_recurrente tinyint(1) DEFAULT 0,
            dias_recurrencia varchar(20) DEFAULT '',
            estado varchar(20) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conductor_id (conductor_id),
            KEY fecha_salida (fecha_salida),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Carpooling: Reservas ───
        $sql = "CREATE TABLE {$prefix}carpooling_reservas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            viaje_id bigint(20) unsigned NOT NULL,
            pasajero_id bigint(20) unsigned NOT NULL,
            num_plazas int(11) DEFAULT 1,
            punto_recogida varchar(255) DEFAULT '',
            estado varchar(20) DEFAULT 'pendiente',
            mensaje text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY viaje_pasajero (viaje_id, pasajero_id),
            KEY pasajero_id (pasajero_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        return true;
    }

    public function down() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tables = [
            'carpooling_reservas',
            'carpooling_viajes',
            'energia_produccion',
            'energia_miembros',
            'comunidades_energia',
            'reciclaje_registros',
            'puntos_reciclaje',
            'compostaje_aportaciones',
            'composteras',
            'huerto_cosechas',
            'huerto_parcelas',
            'huertos',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
        }

        return true;
    }
}
