<?php
/**
 * Vista Admin: Estadísticas de Red Social
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas
$tabla_posts = $wpdb->prefix . 'flavor_red_social_posts';
$tabla_interacciones = $wpdb->prefix . 'flavor_red_social_interacciones';
$tabla_comentarios = $wpdb->prefix . 'flavor_red_social_comentarios';
$tabla_seguidores = $wpdb->prefix . 'flavor_red_social_seguidores';

// Verificar existencia de tablas
$tabla_posts_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_posts'") === $tabla_posts;
$tabla_interacciones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_interacciones'") === $tabla_interacciones;
$tabla_comentarios_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_comentarios'") === $tabla_comentarios;
$tabla_seguidores_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_seguidores'") === $tabla_seguidores;

// Periodo de análisis
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : '7';
$fecha_inicio = date('Y-m-d', strtotime("-{$periodo} days"));

// Estadísticas generales
$stats = [
    'total_publicaciones' => 0,
    'publicaciones_periodo' => 0,
    'total_usuarios' => 0,
    'usuarios_activos' => 0,
    'total_interacciones' => 0,
    'interacciones_periodo' => 0,
    'total_comentarios' => 0,
    'comentarios_periodo' => 0,
    'total_seguidores' => 0,
];

if ($tabla_posts_existe) {
    $stats['total_publicaciones'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_posts WHERE estado = 'publicado'");
    $stats['publicaciones_periodo'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_posts WHERE estado = 'publicado' AND fecha_creacion >= %s",
        $fecha_inicio
    ));
    $stats['total_usuarios'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_posts");
    $stats['usuarios_activos'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_posts WHERE fecha_creacion >= %s",
        $fecha_inicio
    ));
}

if ($tabla_interacciones_existe) {
    $stats['total_interacciones'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_interacciones");
    $stats['interacciones_periodo'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_interacciones WHERE fecha >= %s",
        $fecha_inicio
    ));
}

if ($tabla_comentarios_existe) {
    $stats['total_comentarios'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_comentarios");
    $stats['comentarios_periodo'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_comentarios WHERE fecha_creacion >= %s",
        $fecha_inicio
    ));
}

if ($tabla_seguidores_existe) {
    $stats['total_seguidores'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_seguidores");
}

// Actividad diaria
$actividad_diaria = [];
if ($tabla_posts_existe) {
    $actividad_diaria = $wpdb->get_results($wpdb->prepare("
        SELECT DATE(fecha_creacion) as fecha, COUNT(*) as publicaciones
        FROM $tabla_posts
        WHERE fecha_creacion >= %s
        GROUP BY DATE(fecha_creacion)
        ORDER BY fecha ASC
    ", $fecha_inicio));
}

// Usuarios más activos
$usuarios_activos_lista = [];
if ($tabla_posts_existe) {
    $usuarios_activos_lista = $wpdb->get_results($wpdb->prepare("
        SELECT p.usuario_id, u.display_name, COUNT(*) as total_posts
        FROM $tabla_posts p
        LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        WHERE p.fecha_creacion >= %s AND p.estado = 'publicado'
        GROUP BY p.usuario_id
        ORDER BY total_posts DESC
        LIMIT 10
    ", $fecha_inicio));
}

// Distribución por tipo
$distribucion_tipo = [];
if ($tabla_posts_existe) {
    $distribucion_tipo = $wpdb->get_results("
        SELECT COALESCE(tipo, 'texto') as tipo, COUNT(*) as total
        FROM $tabla_posts
        WHERE estado = 'publicado'
        GROUP BY tipo
        ORDER BY total DESC
    ");
}

// Colores para tipos
$colores_tipo = [
    'texto' => '#3b82f6',
    'imagen' => '#10b981',
    'video' => '#f59e0b',
    'enlace' => '#8b5cf6',
    'encuesta' => '#ec4899',
];
?>

<div class="wrap">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=flavor-red-social-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-share" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-bar"></span>
        <?php _e('Estadísticas de Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <!-- Selector de periodo -->
    <div style="margin: 20px 0;">
        <form method="get" action="" style="display: inline-flex; gap: 10px; align-items: center;">
            <input type="hidden" name="page" value="flavor-red-social-estadisticas">
            <label for="periodo"><?php _e('Periodo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select name="periodo" id="periodo" onchange="this.form.submit()">
                <option value="7" <?php selected($periodo, '7'); ?>><?php _e('Últimos 7 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="30" <?php selected($periodo, '30'); ?>><?php _e('Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="90" <?php selected($periodo, '90'); ?>><?php _e('Últimos 90 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="365" <?php selected($periodo, '365'); ?>><?php _e('Último año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </form>
    </div>

    <hr class="wp-header-end">

    <!-- Tarjetas de estadísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div style="background: #fff; padding: 20px; border-left: 4px solid #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['total_publicaciones']); ?></div>
            <div style="color: #646970;"><?php _e('Total publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div style="font-size: 13px; color: #10b981; margin-top: 5px;">
                +<?php echo number_format($stats['publicaciones_periodo']); ?> <?php _e('en el periodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>

        <div style="background: #fff; padding: 20px; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['usuarios_activos']); ?></div>
            <div style="color: #646970;"><?php _e('Usuarios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div style="font-size: 13px; color: #646970; margin-top: 5px;">
                <?php printf(__('de %s totales', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($stats['total_usuarios'])); ?>
            </div>
        </div>

        <div style="background: #fff; padding: 20px; border-left: 4px solid #f59e0b; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['total_interacciones']); ?></div>
            <div style="color: #646970;"><?php _e('Interacciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div style="font-size: 13px; color: #10b981; margin-top: 5px;">
                +<?php echo number_format($stats['interacciones_periodo']); ?> <?php _e('en el periodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>

        <div style="background: #fff; padding: 20px; border-left: 4px solid #8b5cf6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['total_comentarios']); ?></div>
            <div style="color: #646970;"><?php _e('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div style="font-size: 13px; color: #10b981; margin-top: 5px;">
                +<?php echo number_format($stats['comentarios_periodo']); ?> <?php _e('en el periodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 20px 0;">
        <!-- Usuarios más activos -->
        <div class="postbox">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Usuarios más activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <div class="inside" style="padding: 0;">
                <?php if (empty($usuarios_activos_lista)): ?>
                <p style="padding: 20px; text-align: center; color: #646970;"><?php _e('No hay datos disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                <table class="wp-list-table widefat fixed striped" style="border: none;">
                    <thead>
                        <tr>
                            <th><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 100px; text-align: right;"><?php _e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_activos_lista as $usuario): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php echo get_avatar($usuario->usuario_id, 32); ?>
                                    <?php echo esc_html($usuario->display_name ?: __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <strong><?php echo number_format($usuario->total_posts); ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Distribución por tipo -->
        <div class="postbox">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-chart-pie"></span>
                <?php _e('Distribución por tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <div class="inside">
                <?php if (empty($distribucion_tipo)): ?>
                <p style="text-align: center; color: #646970;"><?php _e('No hay datos disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                <?php
                $total_tipos = array_sum(array_column($distribucion_tipo, 'total'));
                foreach ($distribucion_tipo as $tipo):
                    $porcentaje = $total_tipos > 0 ? ($tipo->total / $total_tipos) * 100 : 0;
                    $color = $colores_tipo[$tipo->tipo] ?? '#6b7280';
                ?>
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="text-transform: capitalize;"><?php echo esc_html($tipo->tipo); ?></span>
                        <span><?php echo number_format($tipo->total); ?> (<?php echo number_format($porcentaje, 1); ?>%)</span>
                    </div>
                    <div style="background: #f0f0f1; border-radius: 4px; height: 8px;">
                        <div style="background: <?php echo esc_attr($color); ?>; width: <?php echo esc_attr($porcentaje); ?>%; height: 100%; border-radius: 4px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actividad diaria -->
    <div class="postbox">
        <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
            <span class="dashicons dashicons-chart-line"></span>
            <?php _e('Actividad diaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>
        <div class="inside">
            <?php if (empty($actividad_diaria)): ?>
            <p style="text-align: center; color: #646970;"><?php _e('No hay datos disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else: ?>
            <div style="display: flex; align-items: flex-end; gap: 4px; height: 150px; padding: 10px 0;">
                <?php
                $max_valor = max(array_column($actividad_diaria, 'publicaciones'));
                foreach ($actividad_diaria as $dia):
                    $altura = $max_valor > 0 ? ($dia->publicaciones / $max_valor) * 100 : 0;
                ?>
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                    <div style="background: #3b82f6; width: 100%; height: <?php echo esc_attr($altura); ?>%; min-height: 4px; border-radius: 2px 2px 0 0;" title="<?php echo esc_attr($dia->publicaciones . ' publicaciones'); ?>"></div>
                    <div style="font-size: 10px; color: #646970; margin-top: 5px; transform: rotate(-45deg); white-space: nowrap;">
                        <?php echo esc_html(date_i18n('d/m', strtotime($dia->fecha))); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
