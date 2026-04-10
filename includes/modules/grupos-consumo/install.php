<?php
/**
 * Instalación de tablas para Grupos de Consumo
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas necesarias para Grupos de Consumo
 */
function flavor_grupos_consumo_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de pedidos
    $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
    $sql_pedidos = "CREATE TABLE $tabla_pedidos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ciclo_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        producto_id bigint(20) unsigned NOT NULL,
        cantidad decimal(10,2) NOT NULL,
        precio_unitario decimal(10,2) NOT NULL,
        estado varchar(20) DEFAULT 'pendiente',
        notas text DEFAULT NULL,
        fecha_pedido datetime DEFAULT NULL,
        fecha_modificacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY ciclo_id (ciclo_id),
        KEY usuario_id (usuario_id),
        KEY producto_id (producto_id),
        KEY estado (estado)
    ) $charset_collate;";

    // Tabla de entregas
    $tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';
    $sql_entregas = "CREATE TABLE $tabla_entregas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ciclo_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        total_pedido decimal(10,2) NOT NULL,
        gastos_gestion decimal(10,2) DEFAULT 0.00,
        total_final decimal(10,2) NOT NULL,
        estado_pago varchar(20) DEFAULT 'pendiente',
        fecha_pago datetime DEFAULT NULL,
        metodo_pago varchar(50) DEFAULT NULL,
        estado_recogida varchar(20) DEFAULT 'pendiente',
        fecha_recogida datetime DEFAULT NULL,
        recogido_por varchar(255) DEFAULT NULL,
        notas text DEFAULT NULL,
        fecha_creacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY ciclo_id (ciclo_id),
        KEY usuario_id (usuario_id),
        KEY estado_pago (estado_pago),
        KEY estado_recogida (estado_recogida)
    ) $charset_collate;";

    // Tabla de consolidado de pedidos por productor
    $tabla_consolidado = $wpdb->prefix . 'flavor_gc_consolidado';
    $sql_consolidado = "CREATE TABLE $tabla_consolidado (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ciclo_id bigint(20) unsigned NOT NULL,
        productor_id bigint(20) unsigned NOT NULL,
        producto_id bigint(20) unsigned NOT NULL,
        cantidad_total decimal(10,2) NOT NULL,
        numero_pedidos int(11) NOT NULL,
        estado varchar(20) DEFAULT 'pendiente',
        fecha_solicitud datetime DEFAULT NULL,
        fecha_confirmacion datetime DEFAULT NULL,
        fecha_entrega datetime DEFAULT NULL,
        notas text DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY ciclo_producto (ciclo_id, producto_id),
        KEY productor_id (productor_id),
        KEY estado (estado)
    ) $charset_collate;";

    // Tabla de notificaciones
    $tabla_notificaciones = $wpdb->prefix . 'flavor_gc_notificaciones';
    $sql_notificaciones = "CREATE TABLE $tabla_notificaciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        tipo varchar(50) NOT NULL,
        titulo varchar(255) NOT NULL,
        mensaje text NOT NULL,
        relacionado_tipo varchar(50) DEFAULT NULL,
        relacionado_id bigint(20) unsigned DEFAULT NULL,
        leida tinyint(1) DEFAULT 0,
        fecha_creacion datetime DEFAULT NULL,
        fecha_lectura datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id),
        KEY leida (leida),
        KEY tipo (tipo),
        KEY fecha_creacion (fecha_creacion)
    ) $charset_collate;";

    // Tabla de consumidores (miembros del grupo)
    $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
    $sql_consumidores = "CREATE TABLE $tabla_consumidores (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        grupo_id bigint(20) unsigned NOT NULL,
        rol enum('consumidor','coordinador','productor') DEFAULT 'consumidor',
        estado enum('pendiente','activo','suspendido','baja') DEFAULT 'pendiente',
        preferencias_alimentarias text DEFAULT NULL,
        alergias text DEFAULT NULL,
        saldo_pendiente decimal(10,2) DEFAULT 0.00,
        notas_internas text DEFAULT NULL,
        fecha_alta datetime DEFAULT NULL,
        fecha_baja datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_grupo (usuario_id, grupo_id),
        KEY grupo_id (grupo_id),
        KEY estado (estado),
        KEY rol (rol)
    ) $charset_collate;";

    // Tabla de suscripciones/cestas
    $tabla_suscripciones = $wpdb->prefix . 'flavor_gc_suscripciones';
    $sql_suscripciones = "CREATE TABLE $tabla_suscripciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        consumidor_id bigint(20) unsigned NOT NULL,
        tipo_cesta_id bigint(20) unsigned NOT NULL,
        frecuencia enum('semanal','quincenal','mensual') DEFAULT 'semanal',
        importe decimal(10,2) NOT NULL,
        estado enum('activa','pausada','cancelada') DEFAULT 'activa',
        fecha_inicio date NOT NULL,
        fecha_proximo_cargo date DEFAULT NULL,
        fecha_pausa date DEFAULT NULL,
        fecha_cancelacion date DEFAULT NULL,
        notas text DEFAULT NULL,
        metodo_pago varchar(50) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY consumidor_id (consumidor_id),
        KEY tipo_cesta_id (tipo_cesta_id),
        KEY estado (estado),
        KEY fecha_proximo_cargo (fecha_proximo_cargo)
    ) $charset_collate;";

    // Tabla de tipos de cestas
    $tabla_cestas_tipo = $wpdb->prefix . 'flavor_gc_cestas_tipo';
    $sql_cestas_tipo = "CREATE TABLE $tabla_cestas_tipo (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        descripcion text DEFAULT NULL,
        precio_base decimal(10,2) NOT NULL,
        productos_incluidos longtext DEFAULT NULL,
        imagen_id bigint(20) unsigned DEFAULT NULL,
        orden int(11) DEFAULT 0,
        activa tinyint(1) DEFAULT 1,
        fecha_creacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY activa (activa),
        KEY orden (orden)
    ) $charset_collate;";

    // Tabla de lista de compra
    $tabla_lista_compra = $wpdb->prefix . 'flavor_gc_lista_compra';
    $sql_lista_compra = "CREATE TABLE $tabla_lista_compra (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        producto_id bigint(20) unsigned NOT NULL,
        cantidad decimal(10,2) DEFAULT 1.00,
        fecha_agregado datetime DEFAULT NULL,
        fecha_modificado datetime DEFAULT NULL,
        notas varchar(255) DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_producto (usuario_id, producto_id),
        KEY usuario_id (usuario_id),
        KEY producto_id (producto_id),
        KEY fecha_agregado (fecha_agregado)
    ) $charset_collate;";

    // Tabla de historial de suscripciones (para tracking de cargos)
    $tabla_suscripciones_historial = $wpdb->prefix . 'flavor_gc_suscripciones_historial';
    $sql_suscripciones_historial = "CREATE TABLE $tabla_suscripciones_historial (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        suscripcion_id bigint(20) unsigned NOT NULL,
        ciclo_id bigint(20) unsigned DEFAULT NULL,
        importe decimal(10,2) NOT NULL,
        estado enum('pendiente','procesado','fallido') DEFAULT 'pendiente',
        fecha_cargo datetime DEFAULT NULL,
        notas text DEFAULT NULL,
        PRIMARY KEY (id),
        KEY suscripcion_id (suscripcion_id),
        KEY ciclo_id (ciclo_id),
        KEY estado (estado)
    ) $charset_collate;";

    // Tabla de pagos (v4.1.0)
    $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';
    $sql_pagos = "CREATE TABLE $tabla_pagos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        entrega_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        ciclo_id bigint(20) unsigned NOT NULL,
        pasarela varchar(50) NOT NULL,
        transaction_id varchar(255) DEFAULT NULL,
        importe decimal(10,2) NOT NULL,
        moneda varchar(3) DEFAULT 'EUR',
        estado enum('pendiente','procesando','completado','fallido','reembolsado','cancelado') DEFAULT 'pendiente',
        datos_pasarela longtext DEFAULT NULL,
        ip_cliente varchar(45) DEFAULT NULL,
        fecha_creacion datetime DEFAULT NULL,
        fecha_actualizacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY entrega_id (entrega_id),
        KEY usuario_id (usuario_id),
        KEY ciclo_id (ciclo_id),
        KEY pasarela (pasarela),
        KEY estado (estado),
        KEY transaction_id (transaction_id(191)),
        KEY fecha_creacion (fecha_creacion)
    ) $charset_collate;";

    // =====================================================
    // TABLAS v4.2.0 - Sello de Conciencia (+7 pts)
    // =====================================================

    // Tabla de excedentes solidarios
    // Gestiona productos sobrantes de cada ciclo para donar o redistribuir
    $tabla_excedentes = $wpdb->prefix . 'flavor_gc_excedentes';
    $sql_excedentes = "CREATE TABLE $tabla_excedentes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ciclo_id bigint(20) unsigned NOT NULL,
        producto_id bigint(20) unsigned NOT NULL,
        cantidad_sobrante decimal(10,2) NOT NULL,
        cantidad_reclamada decimal(10,2) DEFAULT 0.00,
        cantidad_donada decimal(10,2) DEFAULT 0.00,
        estado enum('disponible','parcial','agotado','donado','descartado') DEFAULT 'disponible',
        destino_donacion varchar(255) DEFAULT NULL,
        precio_solidario decimal(10,2) DEFAULT NULL,
        motivo_excedente varchar(100) DEFAULT NULL,
        notas text DEFAULT NULL,
        fecha_registro datetime DEFAULT NULL,
        fecha_cierre datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY ciclo_id (ciclo_id),
        KEY producto_id (producto_id),
        KEY estado (estado),
        KEY fecha_registro (fecha_registro)
    ) $charset_collate;";

    // Tabla de reclamaciones de excedentes
    $tabla_excedentes_reclamaciones = $wpdb->prefix . 'flavor_gc_excedentes_reclamaciones';
    $sql_excedentes_reclamaciones = "CREATE TABLE $tabla_excedentes_reclamaciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        excedente_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        cantidad decimal(10,2) NOT NULL,
        precio_pagado decimal(10,2) DEFAULT 0.00,
        estado enum('pendiente','confirmada','recogida','cancelada') DEFAULT 'pendiente',
        fecha_reclamacion datetime DEFAULT NULL,
        fecha_recogida datetime DEFAULT NULL,
        notas varchar(255) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY excedente_id (excedente_id),
        KEY usuario_id (usuario_id),
        KEY estado (estado)
    ) $charset_collate;";

    // Tabla de huella de ciclo (impacto ecológico)
    // Calcula y muestra métricas de sostenibilidad por ciclo
    $tabla_huella_ciclo = $wpdb->prefix . 'flavor_gc_huella_ciclo';
    $sql_huella_ciclo = "CREATE TABLE $tabla_huella_ciclo (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ciclo_id bigint(20) unsigned NOT NULL,
        km_evitados decimal(10,2) DEFAULT 0.00,
        co2_evitado_kg decimal(10,2) DEFAULT 0.00,
        plastico_evitado_kg decimal(10,4) DEFAULT 0.00,
        agua_ahorrada_litros decimal(12,2) DEFAULT 0.00,
        productores_locales int(11) DEFAULT 0,
        productos_eco_porcentaje decimal(5,2) DEFAULT 0.00,
        km_medio_producto decimal(8,2) DEFAULT 0.00,
        num_participantes int(11) DEFAULT 0,
        total_kg_productos decimal(10,2) DEFAULT 0.00,
        puntuacion_sostenibilidad int(3) DEFAULT 0,
        datos_detalle longtext DEFAULT NULL,
        fecha_calculo datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY ciclo_id (ciclo_id),
        KEY puntuacion_sostenibilidad (puntuacion_sostenibilidad)
    ) $charset_collate;";

    // Tabla de precio justo visible (desglose transparente)
    // Almacena el desglose de costes para cada producto/ciclo
    $tabla_precio_desglose = $wpdb->prefix . 'flavor_gc_precio_desglose';
    $sql_precio_desglose = "CREATE TABLE $tabla_precio_desglose (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        producto_id bigint(20) unsigned NOT NULL,
        ciclo_id bigint(20) unsigned DEFAULT NULL,
        precio_productor decimal(10,2) NOT NULL,
        coste_transporte decimal(10,2) DEFAULT 0.00,
        coste_gestion decimal(10,2) DEFAULT 0.00,
        coste_mermas decimal(10,2) DEFAULT 0.00,
        aportacion_fondo_social decimal(10,2) DEFAULT 0.00,
        iva decimal(10,2) DEFAULT 0.00,
        precio_final decimal(10,2) NOT NULL,
        margen_productor_porcentaje decimal(5,2) DEFAULT NULL,
        origen_km int(11) DEFAULT NULL,
        certificaciones varchar(255) DEFAULT NULL,
        visible_publico tinyint(1) DEFAULT 1,
        fecha_actualizacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY producto_id (producto_id),
        KEY ciclo_id (ciclo_id),
        KEY visible_publico (visible_publico)
    ) $charset_collate;";

    // Tabla de cestas de trueque
    // Permite intercambios de productos entre consumidores
    $tabla_trueque = $wpdb->prefix . 'flavor_gc_trueque';
    $sql_trueque = "CREATE TABLE $tabla_trueque (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_ofrece_id bigint(20) unsigned NOT NULL,
        usuario_recibe_id bigint(20) unsigned DEFAULT NULL,
        titulo varchar(255) NOT NULL,
        descripcion text DEFAULT NULL,
        productos_ofrecidos longtext NOT NULL,
        productos_deseados longtext DEFAULT NULL,
        valor_estimado decimal(10,2) DEFAULT NULL,
        tipo enum('trueque','regalo','prestamo') DEFAULT 'trueque',
        estado enum('abierto','en_negociacion','acordado','completado','cancelado','expirado') DEFAULT 'abierto',
        fecha_publicacion datetime DEFAULT NULL,
        fecha_expiracion datetime DEFAULT NULL,
        fecha_acuerdo datetime DEFAULT NULL,
        fecha_completado datetime DEFAULT NULL,
        ubicacion_intercambio varchar(255) DEFAULT NULL,
        notas_intercambio text DEFAULT NULL,
        valoracion_ofrece tinyint(1) DEFAULT NULL,
        valoracion_recibe tinyint(1) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY usuario_ofrece_id (usuario_ofrece_id),
        KEY usuario_recibe_id (usuario_recibe_id),
        KEY estado (estado),
        KEY tipo (tipo),
        KEY fecha_publicacion (fecha_publicacion),
        KEY fecha_expiracion (fecha_expiracion)
    ) $charset_collate;";

    // Tabla de mensajes de trueque
    $tabla_trueque_mensajes = $wpdb->prefix . 'flavor_gc_trueque_mensajes';
    $sql_trueque_mensajes = "CREATE TABLE $tabla_trueque_mensajes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        trueque_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        mensaje text NOT NULL,
        propuesta_modificada longtext DEFAULT NULL,
        fecha_mensaje datetime DEFAULT NULL,
        leido tinyint(1) DEFAULT 0,
        PRIMARY KEY (id),
        KEY trueque_id (trueque_id),
        KEY usuario_id (usuario_id),
        KEY fecha_mensaje (fecha_mensaje),
        KEY leido (leido)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_pedidos);
    dbDelta($sql_entregas);
    dbDelta($sql_consolidado);
    dbDelta($sql_notificaciones);
    dbDelta($sql_consumidores);
    dbDelta($sql_suscripciones);
    dbDelta($sql_cestas_tipo);
    dbDelta($sql_lista_compra);
    dbDelta($sql_suscripciones_historial);
    dbDelta($sql_pagos);

    // Tablas v4.2.0 - Sello de Conciencia
    dbDelta($sql_excedentes);
    dbDelta($sql_excedentes_reclamaciones);
    dbDelta($sql_huella_ciclo);
    dbDelta($sql_precio_desglose);
    dbDelta($sql_trueque);
    dbDelta($sql_trueque_mensajes);

    // Insertar cestas tipo por defecto
    flavor_grupos_consumo_insertar_cestas_defecto();

    // Insertar datos de ejemplo
    if ($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'gc_productor'") == 0) {
        flavor_grupos_consumo_insertar_datos_ejemplo();
    }
}

/**
 * Inserta cestas tipo por defecto
 */
function flavor_grupos_consumo_insertar_cestas_defecto() {
    global $wpdb;
    $tabla_cestas = $wpdb->prefix . 'flavor_gc_cestas_tipo';

    // Solo insertar si no hay cestas
    if ($wpdb->get_var("SELECT COUNT(*) FROM $tabla_cestas") > 0) {
        return;
    }

    $cestas_defecto = [
        [
            'nombre' => 'Cesta Mixta',
            'slug' => 'mixta',
            'descripcion' => 'Cesta variada con frutas, verduras y otros productos de temporada.',
            'precio_base' => 25.00,
            'productos_incluidos' => json_encode([
                'categorias' => ['frutas', 'verduras'],
                'num_productos' => 8,
            ]),
            'orden' => 1,
        ],
        [
            'nombre' => 'Cesta de Verduras',
            'slug' => 'verduras',
            'descripcion' => 'Selección de verduras frescas de temporada.',
            'precio_base' => 18.00,
            'productos_incluidos' => json_encode([
                'categorias' => ['verduras'],
                'num_productos' => 6,
            ]),
            'orden' => 2,
        ],
        [
            'nombre' => 'Cesta de Frutas',
            'slug' => 'fruta',
            'descripcion' => 'Variedad de frutas de temporada.',
            'precio_base' => 15.00,
            'productos_incluidos' => json_encode([
                'categorias' => ['frutas'],
                'num_productos' => 5,
            ]),
            'orden' => 3,
        ],
        [
            'nombre' => 'Cesta de Lácteos',
            'slug' => 'lacteos',
            'descripcion' => 'Productos lácteos artesanales: leche, quesos, yogures.',
            'precio_base' => 20.00,
            'productos_incluidos' => json_encode([
                'categorias' => ['lacteos'],
                'num_productos' => 4,
            ]),
            'orden' => 4,
        ],
        [
            'nombre' => 'Cesta Personalizada',
            'slug' => 'personalizada',
            'descripcion' => 'Elige tú mismo los productos que quieres recibir.',
            'precio_base' => 0.00,
            'productos_incluidos' => json_encode([
                'tipo' => 'personalizada',
            ]),
            'orden' => 5,
        ],
    ];

    foreach ($cestas_defecto as $cesta) {
        $wpdb->insert(
            $tabla_cestas,
            array_merge($cesta, [
                'activa' => 1,
                'fecha_creacion' => current_time('mysql'),
            ]),
            ['%s', '%s', '%s', '%f', '%s', '%d', '%d', '%s']
        );
    }
}

/**
 * Inserta datos de ejemplo
 */
function flavor_grupos_consumo_insertar_datos_ejemplo() {
    // Crear productor de ejemplo
    $productor_id = wp_insert_post([
        'post_type' => 'gc_productor',
        'post_title' => 'Huerta de Martín',
        'post_content' => 'Productor local de verduras ecológicas de temporada. Cultivo tradicional sin químicos.',
        'post_status' => 'publish',
    ]);

    if ($productor_id && !is_wp_error($productor_id)) {
        update_post_meta($productor_id, '_gc_contacto_nombre', 'Martín García');
        update_post_meta($productor_id, '_gc_contacto_telefono', '666 123 456');
        update_post_meta($productor_id, '_gc_contacto_email', 'martin@huerta.com');
        update_post_meta($productor_id, '_gc_ubicacion', 'Valle del Ebro, Zaragoza');
        update_post_meta($productor_id, '_gc_certificacion_eco', '1');
        update_post_meta($productor_id, '_gc_numero_certificado', 'ES-ECO-025-AR');
        update_post_meta($productor_id, '_gc_metodos_produccion', 'Agricultura ecológica certificada. Rotación de cultivos, compostaje natural, control biológico de plagas.');

        // Crear productos de ejemplo
        $productos_ejemplo = [
            [
                'titulo' => 'Tomates de rama',
                'descripcion' => 'Tomates de rama ecológicos, madurados en la planta. Sabor intenso.',
                'precio' => 2.50,
                'unidad' => 'kg',
                'categoria' => 'verduras',
            ],
            [
                'titulo' => 'Lechugas variadas',
                'descripcion' => 'Mix de lechugas: romana, hoja de roble, lollo rosso.',
                'precio' => 1.80,
                'unidad' => 'unidad',
                'categoria' => 'verduras',
            ],
            [
                'titulo' => 'Calabacines',
                'descripcion' => 'Calabacines tiernos de temporada.',
                'precio' => 2.00,
                'unidad' => 'kg',
                'categoria' => 'verduras',
            ],
            [
                'titulo' => 'Pimientos',
                'descripcion' => 'Pimientos verdes y rojos de Padrón.',
                'precio' => 3.50,
                'unidad' => 'kg',
                'categoria' => 'verduras',
            ],
            [
                'titulo' => 'Cebollas',
                'descripcion' => 'Cebollas dulces de Fuentes.',
                'precio' => 1.50,
                'unidad' => 'kg',
                'categoria' => 'verduras',
            ],
        ];

        foreach ($productos_ejemplo as $prod) {
            $producto_id = wp_insert_post([
                'post_type' => 'gc_producto',
                'post_title' => $prod['titulo'],
                'post_content' => $prod['descripcion'],
                'post_status' => 'publish',
            ]);

            if ($producto_id && !is_wp_error($producto_id)) {
                update_post_meta($producto_id, '_gc_productor_id', $productor_id);
                update_post_meta($producto_id, '_gc_precio', $prod['precio']);
                update_post_meta($producto_id, '_gc_unidad', $prod['unidad']);
                update_post_meta($producto_id, '_gc_cantidad_minima', 1);
                update_post_meta($producto_id, '_gc_temporada', 'Primavera-Verano');
                update_post_meta($producto_id, '_gc_origen', 'Valle del Ebro');

                // Asignar categoría
                wp_set_object_terms($producto_id, $prod['categoria'], 'gc_categoria');
            }
        }

        // Crear ciclo de ejemplo
        $proxima_semana = strtotime('+7 days');
        $ciclo_id = wp_insert_post([
            'post_type' => 'gc_ciclo',
            'post_title' => 'Ciclo ' . date('W', $proxima_semana) . ' - ' . date('Y', $proxima_semana),
            'post_status' => 'gc_abierto',
        ]);

        if ($ciclo_id && !is_wp_error($ciclo_id)) {
            update_post_meta($ciclo_id, '_gc_fecha_inicio', date('Y-m-d\TH:i', current_time('timestamp')));
            update_post_meta($ciclo_id, '_gc_fecha_cierre', date('Y-m-d\TH:i', strtotime('+5 days')));
            update_post_meta($ciclo_id, '_gc_fecha_entrega', date('Y-m-d', $proxima_semana));
            update_post_meta($ciclo_id, '_gc_hora_entrega', '18:00');
            update_post_meta($ciclo_id, '_gc_lugar_entrega', 'Centro Social - Plaza Mayor');
            update_post_meta($ciclo_id, '_gc_notas', 'Traer bolsas reutilizables para recoger el pedido.');
        }
    }
}
