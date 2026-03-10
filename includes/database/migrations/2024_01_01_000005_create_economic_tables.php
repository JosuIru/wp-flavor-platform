<?php
/**
 * Migration: Crear tablas económicas
 *
 * Tablas para: marketplace, banco de tiempo, economía del don, socios
 *
 * @package FlavorPlatform
 * @subpackage Database\Migrations
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Migration_2024_01_01_000005_Create_Economic_Tables extends Flavor_Migration_Base {

    protected $migration_name = 'create_economic_tables';
    protected $description = 'Crear tablas económicas (marketplace, banco tiempo, economía don, socios)';

    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'flavor_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ─── Marketplace: Anuncios ───
        $sql = "CREATE TABLE {$prefix}marketplace_anuncios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion longtext,
            precio decimal(10,2) DEFAULT NULL,
            moneda varchar(10) DEFAULT 'EUR',
            tipo varchar(50) DEFAULT 'venta',
            categoria varchar(100) DEFAULT '',
            subcategoria varchar(100) DEFAULT '',
            imagenes longtext,
            ubicacion varchar(255) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            vendedor_id bigint(20) unsigned NOT NULL,
            vistas int(11) DEFAULT 0,
            favoritos_count int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            destacado tinyint(1) DEFAULT 0,
            fecha_expiracion datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY vendedor_id (vendedor_id),
            KEY categoria (categoria),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY precio (precio)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Marketplace: Favoritos ───
        $sql = "CREATE TABLE {$prefix}marketplace_favoritos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            anuncio_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY anuncio_user (anuncio_id, user_id),
            KEY user_id (user_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Marketplace: Mensajes ───
        $sql = "CREATE TABLE {$prefix}marketplace_mensajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            anuncio_id bigint(20) unsigned NOT NULL,
            remitente_id bigint(20) unsigned NOT NULL,
            destinatario_id bigint(20) unsigned NOT NULL,
            mensaje text NOT NULL,
            leido tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY anuncio_id (anuncio_id),
            KEY remitente_id (remitente_id),
            KEY destinatario_id (destinatario_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Banco de Tiempo: Servicios ───
        $sql = "CREATE TABLE {$prefix}banco_tiempo_servicios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text,
            tipo varchar(50) DEFAULT 'oferta',
            categoria varchar(100) DEFAULT '',
            horas_estimadas decimal(5,2) DEFAULT 1.00,
            modalidad varchar(50) DEFAULT 'presencial',
            ubicacion varchar(255) DEFAULT '',
            disponibilidad text,
            usuario_id bigint(20) unsigned NOT NULL,
            vistas int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Banco de Tiempo: Transacciones ───
        $sql = "CREATE TABLE {$prefix}banco_tiempo_transacciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            servicio_id bigint(20) unsigned DEFAULT NULL,
            proveedor_id bigint(20) unsigned NOT NULL,
            receptor_id bigint(20) unsigned NOT NULL,
            horas decimal(5,2) NOT NULL,
            descripcion text,
            fecha_servicio datetime NOT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            valoracion_proveedor tinyint(1) DEFAULT NULL,
            valoracion_receptor tinyint(1) DEFAULT NULL,
            comentario_proveedor text,
            comentario_receptor text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY servicio_id (servicio_id),
            KEY proveedor_id (proveedor_id),
            KEY receptor_id (receptor_id),
            KEY estado (estado),
            KEY fecha_servicio (fecha_servicio)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Banco de Tiempo: Saldos ───
        $sql = "CREATE TABLE {$prefix}banco_tiempo_saldos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            horas_disponibles decimal(10,2) DEFAULT 0.00,
            horas_dadas decimal(10,2) DEFAULT 0.00,
            horas_recibidas decimal(10,2) DEFAULT 0.00,
            ultima_transaccion datetime DEFAULT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Economía del Don ───
        $sql = "CREATE TABLE {$prefix}economia_don (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text,
            tipo varchar(50) DEFAULT 'regalo',
            categoria varchar(100) DEFAULT '',
            imagenes longtext,
            ubicacion varchar(255) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            donante_id bigint(20) unsigned NOT NULL,
            receptor_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'disponible',
            fecha_entrega datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY donante_id (donante_id),
            KEY receptor_id (receptor_id),
            KEY tipo (tipo),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Socios ───
        $sql = "CREATE TABLE {$prefix}socios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            numero_socio varchar(50) NOT NULL,
            tipo_socio varchar(50) DEFAULT 'ordinario',
            fecha_alta date NOT NULL,
            fecha_baja date DEFAULT NULL,
            cuota_mensual decimal(10,2) DEFAULT 0.00,
            estado varchar(20) DEFAULT 'activo',
            datos_adicionales longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY numero_socio (numero_socio),
            KEY tipo_socio (tipo_socio),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Cuotas de socios ───
        $sql = "CREATE TABLE {$prefix}socios_cuotas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            socio_id bigint(20) unsigned NOT NULL,
            concepto varchar(255) NOT NULL,
            importe decimal(10,2) NOT NULL,
            periodo varchar(20) DEFAULT NULL,
            fecha_emision date NOT NULL,
            fecha_vencimiento date NOT NULL,
            fecha_pago date DEFAULT NULL,
            metodo_pago varchar(50) DEFAULT NULL,
            referencia_pago varchar(100) DEFAULT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY socio_id (socio_id),
            KEY estado (estado),
            KEY fecha_vencimiento (fecha_vencimiento)
        ) {$charset_collate};";
        dbDelta($sql);

        return true;
    }

    public function down() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tables = [
            'socios_cuotas',
            'socios',
            'economia_don',
            'banco_tiempo_saldos',
            'banco_tiempo_transacciones',
            'banco_tiempo_servicios',
            'marketplace_mensajes',
            'marketplace_favoritos',
            'marketplace_anuncios',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
        }

        return true;
    }
}
