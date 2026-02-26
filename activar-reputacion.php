<?php
/**
 * Script temporal para activar el sistema de reputación
 * Ejecutar una vez y luego eliminar
 *
 * Acceder vía: /wp-content/plugins/flavor-chat-ia/activar-reputacion.php
 */

// Cargar WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Solo admins
if (!current_user_can('manage_options')) {
    wp_die('Acceso denegado');
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();
$resultados = [];

// Tabla reputación
$tabla_reputacion = $wpdb->prefix . 'flavor_social_reputacion';
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reputacion'") != $tabla_reputacion) {
    $wpdb->query("CREATE TABLE $tabla_reputacion (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        puntos_totales int(11) DEFAULT 0,
        nivel varchar(50) DEFAULT 'nuevo',
        puntos_semana int(11) DEFAULT 0,
        puntos_mes int(11) DEFAULT 0,
        racha_dias int(11) DEFAULT 0,
        ultima_actividad datetime DEFAULT NULL,
        fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_id (usuario_id),
        KEY nivel (nivel),
        KEY puntos_totales (puntos_totales)
    ) $charset_collate");
    $resultados[] = '✅ Tabla reputación creada';
} else {
    $resultados[] = '⏭️ Tabla reputación ya existe';
}

// Tabla badges
$tabla_badges = $wpdb->prefix . 'flavor_social_badges';
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_badges'") != $tabla_badges) {
    $wpdb->query("CREATE TABLE $tabla_badges (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        descripcion text,
        icono varchar(255) DEFAULT NULL,
        color varchar(20) DEFAULT '#3b82f6',
        categoria enum('participacion','creacion','comunidad','especial','temporal') DEFAULT 'participacion',
        puntos_requeridos int(11) DEFAULT 0,
        condicion_especial text DEFAULT NULL,
        es_unico tinyint(1) DEFAULT 0,
        activo tinyint(1) DEFAULT 1,
        orden int(11) DEFAULT 0,
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY categoria (categoria),
        KEY activo (activo)
    ) $charset_collate");
    $resultados[] = '✅ Tabla badges creada';
} else {
    $resultados[] = '⏭️ Tabla badges ya existe';
}

// Tabla usuario_badges
$tabla_usuario_badges = $wpdb->prefix . 'flavor_social_usuario_badges';
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_usuario_badges'") != $tabla_usuario_badges) {
    $wpdb->query("CREATE TABLE $tabla_usuario_badges (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        badge_id bigint(20) unsigned NOT NULL,
        fecha_obtenido datetime DEFAULT CURRENT_TIMESTAMP,
        destacado tinyint(1) DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_badge (usuario_id, badge_id),
        KEY badge_id (badge_id),
        KEY fecha_obtenido (fecha_obtenido)
    ) $charset_collate");
    $resultados[] = '✅ Tabla usuario_badges creada';
} else {
    $resultados[] = '⏭️ Tabla usuario_badges ya existe';
}

// Tabla historial_puntos
$tabla_historial = $wpdb->prefix . 'flavor_social_historial_puntos';
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_historial'") != $tabla_historial) {
    $wpdb->query("CREATE TABLE $tabla_historial (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        puntos int(11) NOT NULL,
        tipo_accion varchar(50) NOT NULL,
        descripcion varchar(255) DEFAULT NULL,
        referencia_id bigint(20) unsigned DEFAULT NULL,
        referencia_tipo varchar(50) DEFAULT NULL,
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id),
        KEY tipo_accion (tipo_accion),
        KEY fecha_creacion (fecha_creacion)
    ) $charset_collate");
    $resultados[] = '✅ Tabla historial_puntos creada';
} else {
    $resultados[] = '⏭️ Tabla historial_puntos ya existe';
}

// Tabla engagement
$tabla_engagement = $wpdb->prefix . 'flavor_social_engagement';
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_engagement'") != $tabla_engagement) {
    $wpdb->query("CREATE TABLE $tabla_engagement (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        autor_id bigint(20) unsigned NOT NULL,
        tipo_interaccion enum('like','comentario','compartido','guardado','clic','tiempo_lectura') NOT NULL,
        contenido_id bigint(20) unsigned DEFAULT NULL,
        peso decimal(5,2) DEFAULT 1.00,
        fecha_interaccion datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY usuario_autor (usuario_id, autor_id),
        KEY autor_id (autor_id),
        KEY fecha_interaccion (fecha_interaccion),
        KEY tipo_interaccion (tipo_interaccion)
    ) $charset_collate");
    $resultados[] = '✅ Tabla engagement creada';
} else {
    $resultados[] = '⏭️ Tabla engagement ya existe';
}

// Insertar badges predeterminados
$badges_predeterminados = [
    ['Primeros Pasos', 'primeros-pasos', 'Completaste tu perfil y publicaste tu primer contenido', '🎯', '#3b82f6', 'participacion', 25, null, 1],
    ['Comunicador', 'comunicador', 'Has comentado en más de 50 publicaciones', '💬', '#8b5cf6', 'participacion', 0, json_encode(['tipo' => 'comentarios_count', 'valor' => 50]), 2],
    ['Creador Activo', 'creador-activo', 'Has creado más de 20 publicaciones', '✍️', '#f59e0b', 'creacion', 0, json_encode(['tipo' => 'publicaciones_count', 'valor' => 20]), 3],
    ['Influencer', 'influencer', 'Has conseguido más de 100 seguidores', '⭐', '#ec4899', 'comunidad', 0, json_encode(['tipo' => 'seguidores_count', 'valor' => 100]), 4],
    ['Constante', 'constante', 'Has mantenido una racha de 7 días de actividad', '🔥', '#ef4444', 'participacion', 0, json_encode(['tipo' => 'racha_dias', 'valor' => 7]), 5],
    ['Maratonista', 'maratonista', 'Has mantenido una racha de 30 días de actividad', '🏃', '#10b981', 'participacion', 0, json_encode(['tipo' => 'racha_dias', 'valor' => 30]), 6],
    ['Centenario', 'centenario', 'Has alcanzado 100 puntos de reputación', '💯', '#6366f1', 'participacion', 100, null, 7],
    ['Veterano', 'veterano', 'Has alcanzado 1000 puntos de reputación', '🏆', '#f97316', 'especial', 1000, null, 8],
];

$badges_insertados = 0;
foreach ($badges_predeterminados as $badge) {
    $existe = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $tabla_badges WHERE slug = %s",
        $badge[1]
    ));

    if (!$existe) {
        $wpdb->insert($tabla_badges, [
            'nombre' => $badge[0],
            'slug' => $badge[1],
            'descripcion' => $badge[2],
            'icono' => $badge[3],
            'color' => $badge[4],
            'categoria' => $badge[5],
            'puntos_requeridos' => $badge[6],
            'condicion_especial' => $badge[7],
            'orden' => $badge[8],
            'es_unico' => 1,
            'activo' => 1,
            'fecha_creacion' => current_time('mysql'),
        ]);
        $badges_insertados++;
    }
}

if ($badges_insertados > 0) {
    $resultados[] = "✅ $badges_insertados badges predeterminados insertados";
} else {
    $resultados[] = '⏭️ Badges ya existían';
}

// Activar algoritmo inteligente
update_option('flavor_module_red_social_timeline_algoritmo', 'inteligente');
$resultados[] = '✅ Algoritmo de feed inteligente activado';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Activación Sistema de Reputación</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; background: #f0f0f0; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1e3a5f; margin-bottom: 20px; }
        .resultado { padding: 10px 15px; margin: 8px 0; background: #f8f9fa; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .btn { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .btn:hover { background: #2563eb; }
        .note { margin-top: 20px; padding: 15px; background: #fef3c7; border-radius: 4px; color: #92400e; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Sistema de Reputación Activado</h1>

        <?php foreach ($resultados as $resultado): ?>
        <div class="resultado <?php echo strpos($resultado, '✅') !== false ? 'success' : 'warning'; ?>">
            <?php echo $resultado; ?>
        </div>
        <?php endforeach; ?>

        <div class="note">
            <strong>⚠️ Importante:</strong> Elimina este archivo después de ejecutarlo por seguridad.
        </div>

        <a href="<?php echo admin_url('admin.php?page=flavor-modules'); ?>" class="btn">Ir al Panel de Módulos</a>
    </div>
</body>
</html>
<?php
// Auto-eliminarse (opcional, comentado por seguridad)
// unlink(__FILE__);
