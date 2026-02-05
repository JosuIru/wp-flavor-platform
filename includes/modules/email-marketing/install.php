<?php
/**
 * Instalación de tablas para Email Marketing
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crear tablas del módulo Email Marketing
 */
function flavor_email_marketing_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de listas de suscriptores
    $tabla_listas = $wpdb->prefix . 'flavor_em_listas';
    $sql_listas = "CREATE TABLE IF NOT EXISTS $tabla_listas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(255) NOT NULL,
        slug varchar(100) NOT NULL,
        descripcion text,
        tipo enum('newsletter','segmento','importada','automatica') DEFAULT 'newsletter',
        doble_optin tinyint(1) DEFAULT 1,
        mensaje_confirmacion text,
        mensaje_bienvenida text,
        total_suscriptores int(11) DEFAULT 0,
        activa tinyint(1) DEFAULT 1,
        metadata longtext,
        creado_en datetime DEFAULT CURRENT_TIMESTAMP,
        actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY activa (activa),
        KEY tipo (tipo)
    ) $charset_collate;";

    // Tabla de suscriptores
    $tabla_suscriptores = $wpdb->prefix . 'flavor_em_suscriptores';
    $sql_suscriptores = "CREATE TABLE IF NOT EXISTS $tabla_suscriptores (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        nombre varchar(255),
        apellidos varchar(255),
        usuario_id bigint(20) unsigned,
        estado enum('pendiente','activo','baja','rebotado','spam') DEFAULT 'pendiente',
        origen varchar(100),
        ip_registro varchar(45),
        fecha_confirmacion datetime,
        fecha_baja datetime,
        motivo_baja varchar(255),
        tags longtext,
        campos_personalizados longtext,
        puntuacion int(11) DEFAULT 0,
        total_emails_enviados int(11) DEFAULT 0,
        total_abiertos int(11) DEFAULT 0,
        total_clicks int(11) DEFAULT 0,
        ultimo_email datetime,
        ultima_apertura datetime,
        ultimo_click datetime,
        creado_en datetime DEFAULT CURRENT_TIMESTAMP,
        actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY email (email),
        KEY usuario_id (usuario_id),
        KEY estado (estado),
        KEY puntuacion (puntuacion)
    ) $charset_collate;";

    // Tabla de relación suscriptor-lista
    $tabla_suscriptor_lista = $wpdb->prefix . 'flavor_em_suscriptor_lista';
    $sql_suscriptor_lista = "CREATE TABLE IF NOT EXISTS $tabla_suscriptor_lista (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        suscriptor_id bigint(20) unsigned NOT NULL,
        lista_id bigint(20) unsigned NOT NULL,
        estado enum('pendiente','activo','baja') DEFAULT 'pendiente',
        fecha_suscripcion datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_baja datetime,
        PRIMARY KEY (id),
        UNIQUE KEY suscriptor_lista (suscriptor_id, lista_id),
        KEY lista_id (lista_id),
        KEY estado (estado)
    ) $charset_collate;";

    // Tabla de campañas
    $tabla_campanias = $wpdb->prefix . 'flavor_em_campanias';
    $sql_campanias = "CREATE TABLE IF NOT EXISTS $tabla_campanias (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(255) NOT NULL,
        asunto varchar(255) NOT NULL,
        asunto_alternativo varchar(255),
        preview_text varchar(255),
        contenido_html longtext,
        contenido_texto longtext,
        plantilla_id bigint(20) unsigned,
        tipo enum('regular','ab_test','automatica','rss') DEFAULT 'regular',
        estado enum('borrador','programada','enviando','enviada','pausada','cancelada') DEFAULT 'borrador',
        listas_ids longtext,
        segmentos longtext,
        excluir_listas longtext,
        remitente_nombre varchar(255),
        remitente_email varchar(255),
        responder_a varchar(255),
        fecha_programada datetime,
        fecha_inicio_envio datetime,
        fecha_fin_envio datetime,
        total_destinatarios int(11) DEFAULT 0,
        total_enviados int(11) DEFAULT 0,
        total_entregados int(11) DEFAULT 0,
        total_abiertos int(11) DEFAULT 0,
        total_clicks int(11) DEFAULT 0,
        total_bajas int(11) DEFAULT 0,
        total_rebotes int(11) DEFAULT 0,
        total_spam int(11) DEFAULT 0,
        ab_variante_ganadora varchar(10),
        metadata longtext,
        creado_por bigint(20) unsigned,
        creado_en datetime DEFAULT CURRENT_TIMESTAMP,
        actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY estado (estado),
        KEY tipo (tipo),
        KEY fecha_programada (fecha_programada),
        KEY creado_por (creado_por)
    ) $charset_collate;";

    // Tabla de automatizaciones
    $tabla_automatizaciones = $wpdb->prefix . 'flavor_em_automatizaciones';
    $sql_automatizaciones = "CREATE TABLE IF NOT EXISTS $tabla_automatizaciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(255) NOT NULL,
        descripcion text,
        trigger_tipo varchar(100) NOT NULL,
        trigger_config longtext,
        estado enum('activa','pausada','borrador') DEFAULT 'borrador',
        pasos longtext,
        total_inscritos int(11) DEFAULT 0,
        total_completados int(11) DEFAULT 0,
        total_salidos int(11) DEFAULT 0,
        metadata longtext,
        creado_por bigint(20) unsigned,
        creado_en datetime DEFAULT CURRENT_TIMESTAMP,
        actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY estado (estado),
        KEY trigger_tipo (trigger_tipo)
    ) $charset_collate;";

    // Tabla de suscriptores en automatización
    $tabla_auto_suscriptores = $wpdb->prefix . 'flavor_em_auto_suscriptores';
    $sql_auto_suscriptores = "CREATE TABLE IF NOT EXISTS $tabla_auto_suscriptores (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        automatizacion_id bigint(20) unsigned NOT NULL,
        suscriptor_id bigint(20) unsigned NOT NULL,
        paso_actual int(11) DEFAULT 0,
        estado enum('activo','completado','salido','pausado') DEFAULT 'activo',
        fecha_entrada datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_proximo_paso datetime,
        fecha_completado datetime,
        historial longtext,
        PRIMARY KEY (id),
        UNIQUE KEY auto_suscriptor (automatizacion_id, suscriptor_id),
        KEY estado (estado),
        KEY fecha_proximo_paso (fecha_proximo_paso)
    ) $charset_collate;";

    // Tabla de plantillas de email
    $tabla_plantillas = $wpdb->prefix . 'flavor_em_plantillas';
    $sql_plantillas = "CREATE TABLE IF NOT EXISTS $tabla_plantillas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(255) NOT NULL,
        categoria varchar(100),
        contenido_html longtext,
        thumbnail varchar(500),
        es_predefinida tinyint(1) DEFAULT 0,
        activa tinyint(1) DEFAULT 1,
        metadata longtext,
        creado_en datetime DEFAULT CURRENT_TIMESTAMP,
        actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY categoria (categoria),
        KEY activa (activa)
    ) $charset_collate;";

    // Tabla de tracking de emails
    $tabla_tracking = $wpdb->prefix . 'flavor_em_tracking';
    $sql_tracking = "CREATE TABLE IF NOT EXISTS $tabla_tracking (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        campania_id bigint(20) unsigned,
        automatizacion_id bigint(20) unsigned,
        suscriptor_id bigint(20) unsigned NOT NULL,
        email_hash varchar(64) NOT NULL,
        tipo enum('enviado','entregado','abierto','click','baja','rebote','spam') NOT NULL,
        url_clickeada varchar(500),
        ip varchar(45),
        user_agent varchar(500),
        pais varchar(100),
        ciudad varchar(100),
        dispositivo varchar(50),
        cliente_email varchar(100),
        fecha datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY campania_id (campania_id),
        KEY automatizacion_id (automatizacion_id),
        KEY suscriptor_id (suscriptor_id),
        KEY email_hash (email_hash),
        KEY tipo (tipo),
        KEY fecha (fecha)
    ) $charset_collate;";

    // Tabla de cola de envíos
    $tabla_cola = $wpdb->prefix . 'flavor_em_cola';
    $sql_cola = "CREATE TABLE IF NOT EXISTS $tabla_cola (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        campania_id bigint(20) unsigned,
        automatizacion_id bigint(20) unsigned,
        paso_auto int(11),
        suscriptor_id bigint(20) unsigned NOT NULL,
        email varchar(255) NOT NULL,
        asunto varchar(255),
        contenido longtext,
        prioridad int(11) DEFAULT 5,
        intentos int(11) DEFAULT 0,
        max_intentos int(11) DEFAULT 3,
        estado enum('pendiente','procesando','enviado','fallido') DEFAULT 'pendiente',
        error_mensaje text,
        programado_para datetime,
        enviado_en datetime,
        creado_en datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY estado (estado),
        KEY prioridad (prioridad),
        KEY programado_para (programado_para),
        KEY campania_id (campania_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta($sql_listas);
    dbDelta($sql_suscriptores);
    dbDelta($sql_suscriptor_lista);
    dbDelta($sql_campanias);
    dbDelta($sql_automatizaciones);
    dbDelta($sql_auto_suscriptores);
    dbDelta($sql_plantillas);
    dbDelta($sql_tracking);
    dbDelta($sql_cola);

    // Insertar plantillas predefinidas
    flavor_email_marketing_insertar_plantillas_predefinidas();

    // Crear lista por defecto
    flavor_email_marketing_crear_lista_defecto();

    update_option('flavor_email_marketing_db_version', '1.0.0');
}

/**
 * Insertar plantillas predefinidas
 */
function flavor_email_marketing_insertar_plantillas_predefinidas() {
    global $wpdb;

    $tabla = $wpdb->prefix . 'flavor_em_plantillas';

    // Verificar si ya existen
    $existe = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE es_predefinida = 1");
    if ($existe > 0) {
        return;
    }

    $plantillas = [
        [
            'nombre' => 'Básica - Texto Simple',
            'categoria' => 'basica',
            'contenido_html' => flavor_em_plantilla_basica(),
            'es_predefinida' => 1,
        ],
        [
            'nombre' => 'Newsletter - Una Columna',
            'categoria' => 'newsletter',
            'contenido_html' => flavor_em_plantilla_newsletter(),
            'es_predefinida' => 1,
        ],
        [
            'nombre' => 'Promocional - CTA Grande',
            'categoria' => 'promocional',
            'contenido_html' => flavor_em_plantilla_promocional(),
            'es_predefinida' => 1,
        ],
        [
            'nombre' => 'Bienvenida',
            'categoria' => 'transaccional',
            'contenido_html' => flavor_em_plantilla_bienvenida(),
            'es_predefinida' => 1,
        ],
    ];

    foreach ($plantillas as $plantilla) {
        $wpdb->insert($tabla, $plantilla);
    }
}

/**
 * Crear lista de suscriptores por defecto
 */
function flavor_email_marketing_crear_lista_defecto() {
    global $wpdb;

    $tabla = $wpdb->prefix . 'flavor_em_listas';

    $existe = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE slug = 'newsletter-principal'");
    if ($existe > 0) {
        return;
    }

    $wpdb->insert($tabla, [
        'nombre' => 'Newsletter Principal',
        'slug' => 'newsletter-principal',
        'descripcion' => 'Lista principal de suscriptores del newsletter',
        'tipo' => 'newsletter',
        'doble_optin' => 1,
        'mensaje_confirmacion' => 'Por favor, confirma tu suscripción haciendo clic en el enlace que te hemos enviado.',
        'mensaje_bienvenida' => '¡Gracias por suscribirte a nuestro newsletter!',
        'activa' => 1,
    ]);
}

/**
 * Plantilla básica
 */
function flavor_em_plantilla_basica() {
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{asunto}}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #333333; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px;">
                    <tr>
                        <td style="padding: 40px;">
                            {{contenido}}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 40px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; text-align: center; font-size: 12px; color: #666666;">
                            <p style="margin: 0;">{{nombre_sitio}}</p>
                            <p style="margin: 10px 0 0 0;">
                                <a href="{{url_baja}}" style="color: #666666;">Darse de baja</a> |
                                <a href="{{url_preferencias}}" style="color: #666666;">Preferencias</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Plantilla newsletter
 */
function flavor_em_plantilla_newsletter() {
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{asunto}}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #333333; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px; background-color: {{color_primario}}; text-align: center;">
                            <img src="{{logo_url}}" alt="{{nombre_sitio}}" style="max-width: 200px; height: auto;">
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            {{contenido}}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px; background-color: #f8f9fa; text-align: center; font-size: 12px; color: #666666;">
                            <p style="margin: 0;">{{nombre_sitio}} - {{direccion}}</p>
                            <p style="margin: 10px 0 0 0;">
                                <a href="{{url_baja}}" style="color: #666666;">Darse de baja</a> |
                                <a href="{{url_preferencias}}" style="color: #666666;">Preferencias</a> |
                                <a href="{{url_ver_online}}" style="color: #666666;">Ver online</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Plantilla promocional
 */
function flavor_em_plantilla_promocional() {
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{asunto}}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #333333; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    <!-- Hero -->
                    <tr>
                        <td style="padding: 60px 40px; background: linear-gradient(135deg, {{color_primario}} 0%, {{color_secundario}} 100%); text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px;">{{titulo_hero}}</h1>
                            <p style="margin: 20px 0; color: #ffffff; font-size: 18px;">{{subtitulo_hero}}</p>
                            <a href="{{url_cta}}" style="display: inline-block; padding: 15px 40px; background-color: #ffffff; color: {{color_primario}}; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 18px;">{{texto_cta}}</a>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            {{contenido}}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px; background-color: #f8f9fa; text-align: center; font-size: 12px; color: #666666;">
                            <p style="margin: 0;">{{nombre_sitio}}</p>
                            <p style="margin: 10px 0 0 0;">
                                <a href="{{url_baja}}" style="color: #666666;">Darse de baja</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Plantilla de bienvenida
 */
function flavor_em_plantilla_bienvenida() {
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bienvenido/a!</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #333333; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px; text-align: center; background-color: {{color_primario}};">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px;">¡Bienvenido/a, {{nombre}}!</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p>Gracias por unirte a nuestra comunidad. Estamos encantados de tenerte con nosotros.</p>

                            {{contenido}}

                            <p style="margin-top: 30px;">Si tienes alguna pregunta, no dudes en contactarnos.</p>

                            <p>¡Un saludo!</p>
                            <p><strong>El equipo de {{nombre_sitio}}</strong></p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px; background-color: #f8f9fa; text-align: center; font-size: 12px; color: #666666;">
                            <p style="margin: 0;">{{nombre_sitio}}</p>
                            <p style="margin: 10px 0 0 0;">
                                <a href="{{url_baja}}" style="color: #666666;">Darse de baja</a> |
                                <a href="{{url_preferencias}}" style="color: #666666;">Preferencias</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Eliminar tablas del módulo
 */
function flavor_email_marketing_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        'flavor_em_listas',
        'flavor_em_suscriptores',
        'flavor_em_suscriptor_lista',
        'flavor_em_campanias',
        'flavor_em_automatizaciones',
        'flavor_em_auto_suscriptores',
        'flavor_em_plantillas',
        'flavor_em_tracking',
        'flavor_em_cola',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$tabla}");
    }

    delete_option('flavor_email_marketing_db_version');
}
