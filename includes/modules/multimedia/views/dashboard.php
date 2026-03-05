<?php
/**
 * Vista Dashboard del módulo Multimedia
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$is_dashboard_viewer = isset($is_dashboard_viewer) ? (bool) $is_dashboard_viewer : (current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options'));
$tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
$tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';
$tabla_visitas = $wpdb->prefix . 'flavor_multimedia_visitas';

// Verificar existencia de tablas
$tabla_multimedia_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_multimedia'") === $tabla_multimedia;
$tabla_albumes_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_albumes'") === $tabla_albumes;
$tabla_visitas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_visitas'") === $tabla_visitas;

// Inicializar estadísticas
$total_multimedia = 0;
$total_fotos = 0;
$total_videos = 0;
$total_audios = 0;
$total_albumes = 0;
$pendientes_moderacion = 0;
$total_visitas = 0;
$visitas_hoy = 0;
$descargas_totales = 0;
$almacenamiento_mb = 0;
$multimedia_reciente = [];
$por_categoria = [];
$actividad_diaria = [];
$multimedia_popular = [];

if ($tabla_multimedia_existe) {
    $total_multimedia = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE estado IN ('publico', 'comunidad')");
    $total_fotos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE tipo = 'imagen' AND estado IN ('publico', 'comunidad')");
    $total_videos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE tipo = 'video' AND estado IN ('publico', 'comunidad')");
    $total_audios = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE tipo = 'audio' AND estado IN ('publico', 'comunidad')");
    $pendientes_moderacion = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE estado = 'pendiente'");
    $descargas_totales = (int) $wpdb->get_var("SELECT COALESCE(SUM(descargas), 0) FROM $tabla_multimedia");
    $almacenamiento_mb = (float) $wpdb->get_var("SELECT COALESCE(SUM(tamano_bytes), 0) / 1048576 FROM $tabla_multimedia");

    // Contenido reciente
    $multimedia_reciente = $wpdb->get_results("
        SELECT m.*, u.display_name as autor_nombre
        FROM $tabla_multimedia m
        LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
        WHERE m.estado IN ('publico', 'comunidad')
        ORDER BY m.fecha_creacion DESC
        LIMIT 8
    ");

    // Estadísticas por categoría
    $por_categoria = $wpdb->get_results("
        SELECT categoria, COUNT(*) as total
        FROM $tabla_multimedia
        WHERE estado IN ('publico', 'comunidad') AND categoria IS NOT NULL AND categoria != ''
        GROUP BY categoria
        ORDER BY total DESC
        LIMIT 6
    ");

    // Actividad diaria (últimos 7 días)
    $actividad_diaria = $wpdb->get_results("
        SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
        FROM $tabla_multimedia
        WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(fecha_creacion)
        ORDER BY fecha ASC
    ");

    // Contenido más popular
    $multimedia_popular = $wpdb->get_results("
        SELECT m.*, u.display_name as autor_nombre
        FROM $tabla_multimedia m
        LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
        WHERE m.estado IN ('publico', 'comunidad')
        ORDER BY m.visitas DESC, m.descargas DESC
        LIMIT 5
    ");
}

if ($tabla_albumes_existe) {
    $total_albumes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_albumes");
}

if ($tabla_visitas_existe) {
    $total_visitas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_visitas");
    $visitas_hoy = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_visitas WHERE DATE(fecha) = %s",
        date('Y-m-d')
    ));
}

// Datos de demostración si no hay datos reales
$usar_datos_demo = ($total_multimedia == 0);

if ($usar_datos_demo) {
    $total_multimedia = 248;
    $total_fotos = 189;
    $total_videos = 42;
    $total_audios = 17;
    $total_albumes = 24;
    $pendientes_moderacion = 5;
    $total_visitas = 3456;
    $visitas_hoy = 127;
    $descargas_totales = 892;
    $almacenamiento_mb = 1456.8;

    $por_categoria = [
        (object) ['categoria' => 'Eventos', 'total' => 78],
        (object) ['categoria' => 'Comunidad', 'total' => 56],
        (object) ['categoria' => 'Naturaleza', 'total' => 45],
        (object) ['categoria' => 'Cultura', 'total' => 38],
        (object) ['categoria' => 'Deportes', 'total' => 21],
        (object) ['categoria' => 'Otros', 'total' => 10],
    ];

    $actividad_diaria = [];
    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        $actividad_diaria[] = (object) [
            'fecha' => $fecha,
            'total' => rand(5, 25)
        ];
    }

    $multimedia_reciente = [
        (object) ['id' => 1, 'titulo' => 'Festival de Primavera 2024', 'tipo' => 'foto', 'categoria' => 'Eventos', 'autor_nombre' => 'María García', 'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'visitas' => 156, 'url' => ''],
        (object) ['id' => 2, 'titulo' => 'Documental Barrio Histórico', 'tipo' => 'video', 'categoria' => 'Cultura', 'autor_nombre' => 'Carlos López', 'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-5 hours')), 'visitas' => 89, 'url' => ''],
        (object) ['id' => 3, 'titulo' => 'Jornada de Limpieza', 'tipo' => 'foto', 'categoria' => 'Comunidad', 'autor_nombre' => 'Ana Martínez', 'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-1 day')), 'visitas' => 234, 'url' => ''],
        (object) ['id' => 4, 'titulo' => 'Podcast Vecinal #23', 'tipo' => 'audio', 'categoria' => 'Comunidad', 'autor_nombre' => 'Pedro Sánchez', 'fecha_creacion' => date('Y-m-d H:i:s', strtotime('-2 days')), 'visitas' => 67, 'url' => ''],
    ];

    $multimedia_popular = [
        (object) ['id' => 10, 'titulo' => 'Inauguración Centro Cívico', 'tipo' => 'video', 'autor_nombre' => 'Admin', 'visitas' => 1245, 'descargas' => 89],
        (object) ['id' => 11, 'titulo' => 'Fiestas Patronales 2023', 'tipo' => 'foto', 'autor_nombre' => 'María García', 'visitas' => 987, 'descargas' => 156],
        (object) ['id' => 12, 'titulo' => 'Torneo Deportivo Anual', 'tipo' => 'foto', 'autor_nombre' => 'Carlos López', 'visitas' => 756, 'descargas' => 45],
        (object) ['id' => 13, 'titulo' => 'Concierto Banda Municipal', 'tipo' => 'audio', 'autor_nombre' => 'Pedro Sánchez', 'visitas' => 543, 'descargas' => 234],
        (object) ['id' => 14, 'titulo' => 'Taller de Artesanía', 'tipo' => 'foto', 'autor_nombre' => 'Ana Martínez', 'visitas' => 432, 'descargas' => 28],
    ];
}

// Preparar datos para gráficos
$categorias_labels = array_map(function($c) { return $c->categoria ?: 'Sin categoría'; }, $por_categoria);
$categorias_data = array_map(function($c) { return (int) $c->total; }, $por_categoria);

$actividad_labels = array_map(function($a) {
    return date_i18n('D', strtotime($a->fecha));
}, $actividad_diaria);
$actividad_data = array_map(function($a) { return (int) $a->total; }, $actividad_diaria);

// Calcular porcentaje de almacenamiento (asumiendo límite de 5GB)
$limite_almacenamiento_mb = 5120;
$porcentaje_almacenamiento = min(100, round(($almacenamiento_mb / $limite_almacenamiento_mb) * 100, 1));
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-format-gallery"></span>
        <?php echo esc_html__('Dashboard Multimedia', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <?php if ($usar_datos_demo): ?>
        <div class="notice notice-info">
            <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('Mostrando datos de demostración. Los datos reales aparecerán cuando haya contenido multimedia.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($is_dashboard_viewer): ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('Vista resumida para gestor de grupos. La galería administrativa, la configuración y la moderación siguen reservadas a administración.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($pendientes_moderacion > 0 && !$is_dashboard_viewer): ?>
        <div class="notice notice-warning">
            <p>
                <span class="dashicons dashicons-warning"></span>
                <?php printf(
                    esc_html__('Hay %d elementos pendientes de moderación.', 'flavor-chat-ia'),
                    $pendientes_moderacion
                ); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=multimedia-moderacion')); ?>" class="button button-small" style="margin-left: 10px;">
                    <?php echo esc_html__('Moderar ahora', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Accesos Rápidos -->
    <div class="multimedia-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin: 20px 0;">
        <a href="<?php echo esc_url($is_dashboard_viewer ? home_url('/mi-portal/multimedia/') : admin_url('admin.php?page=multimedia-galeria')); ?>" class="multimedia-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-format-gallery" style="font-size: 22px; color: #2271b1;"></span>
            <span><?php echo esc_html($is_dashboard_viewer ? __('Ver en portal', 'flavor-chat-ia') : __('Galería', 'flavor-chat-ia')); ?></span>
        </a>
        <a href="<?php echo esc_url($is_dashboard_viewer ? home_url('/mi-portal/multimedia/albumes/') : admin_url('admin.php?page=multimedia-albumes')); ?>" class="multimedia-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-images-alt" style="font-size: 22px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Álbumes', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url($is_dashboard_viewer ? home_url('/mi-portal/multimedia/subir/') : admin_url('admin.php?page=multimedia-subir')); ?>" class="multimedia-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-upload" style="font-size: 22px; color: #8c49d8;"></span>
            <span><?php echo esc_html__('Subir', 'flavor-chat-ia'); ?></span>
        </a>
        <?php if (!$is_dashboard_viewer): ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=multimedia-moderacion')); ?>" class="multimedia-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-shield" style="font-size: 22px; color: #dba617;"></span>
            <span><?php echo esc_html__('Moderación', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=multimedia-estadisticas')); ?>" class="multimedia-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-chart-bar" style="font-size: 22px; color: #d63638;"></span>
            <span><?php echo esc_html__('Estadísticas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-multimedia-configuracion')); ?>" class="multimedia-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 22px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Estadísticas principales -->
    <div id="multimedia-stats" class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Total Contenido', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($total_multimedia); ?></h2>
                </div>
                <span class="dashicons dashicons-format-gallery" style="font-size: 40px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #00a32a; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Fotos', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($total_fotos); ?></h2>
                </div>
                <span class="dashicons dashicons-camera" style="font-size: 40px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #8c49d8; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Videos', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($total_videos); ?></h2>
                </div>
                <span class="dashicons dashicons-video-alt3" style="font-size: 40px; color: #8c49d8; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #dba617; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Álbumes', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($total_albumes); ?></h2>
                </div>
                <span class="dashicons dashicons-images-alt2" style="font-size: 40px; color: #dba617; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #135e96; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Visitas Hoy', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($visitas_hoy); ?></h2>
                </div>
                <span class="dashicons dashicons-visibility" style="font-size: 40px; color: #135e96; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #d63638; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Descargas', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($descargas_totales); ?></h2>
                </div>
                <span class="dashicons dashicons-download" style="font-size: 40px; color: #d63638; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 20px 0;">

        <!-- Gráfico por categoría -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-category" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Distribución por Categoría', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 20px;">
                <canvas id="grafico-categorias" style="max-height: 280px;"></canvas>
            </div>
        </div>

        <!-- Gráfico de actividad -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-chart-line" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Actividad Últimos 7 Días', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 20px;">
                <canvas id="grafico-actividad" style="max-height: 280px;"></canvas>
            </div>
        </div>

    </div>

    <!-- Tablas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin: 20px 0;">

        <!-- Contenido reciente -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-clock" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Contenido Reciente', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 0;">
                <table class="wp-list-table widefat fixed striped" style="border: none;">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></th>
                            <th><?php echo esc_html__('Título', 'flavor-chat-ia'); ?></th>
                            <th><?php echo esc_html__('Autor', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($multimedia_reciente)): ?>
                            <?php foreach ($multimedia_reciente as $item): ?>
                                <?php
                                $tipo_icon = 'dashicons-camera';
                                $tipo_color = '#00a32a';
                                if ($item->tipo === 'video') {
                                    $tipo_icon = 'dashicons-video-alt3';
                                    $tipo_color = '#8c49d8';
                                } elseif ($item->tipo === 'audio') {
                                    $tipo_icon = 'dashicons-format-audio';
                                    $tipo_color = '#dba617';
                                }
                                ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <span class="dashicons <?php echo esc_attr($tipo_icon); ?>" style="color: <?php echo esc_attr($tipo_color); ?>;"></span>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($item->titulo ?: __('Sin título', 'flavor-chat-ia')); ?></strong>
                                        <?php if (!empty($item->categoria)): ?>
                                            <br><small style="color: #646970;"><?php echo esc_html($item->categoria); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($item->autor_nombre ?: __('Anónimo', 'flavor-chat-ia')); ?></td>
                                    <td>
                                        <small><?php echo esc_html(human_time_diff(strtotime($item->fecha_creacion), current_time('timestamp'))); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: #646970;">
                                    <?php echo esc_html__('No hay contenido reciente', 'flavor-chat-ia'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Contenido popular -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-star-filled" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Contenido Más Visto', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 0;">
                <table class="wp-list-table widefat fixed striped" style="border: none;">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></th>
                            <th><?php echo esc_html__('Título', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php echo esc_html__('Visitas', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php echo esc_html__('Descargas', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($multimedia_popular)): ?>
                            <?php foreach ($multimedia_popular as $item): ?>
                                <?php
                                $tipo_icon = 'dashicons-camera';
                                $tipo_color = '#00a32a';
                                if ($item->tipo === 'video') {
                                    $tipo_icon = 'dashicons-video-alt3';
                                    $tipo_color = '#8c49d8';
                                } elseif ($item->tipo === 'audio') {
                                    $tipo_icon = 'dashicons-format-audio';
                                    $tipo_color = '#dba617';
                                }
                                ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <span class="dashicons <?php echo esc_attr($tipo_icon); ?>" style="color: <?php echo esc_attr($tipo_color); ?>;"></span>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($item->titulo ?: __('Sin título', 'flavor-chat-ia')); ?></strong>
                                        <br><small style="color: #646970;"><?php echo esc_html($item->autor_nombre ?: __('Anónimo', 'flavor-chat-ia')); ?></small>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="background: #e7f3ff; color: #2271b1; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                            <?php echo number_format($item->visitas ?? 0); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="background: #fef3e7; color: #996800; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                            <?php echo number_format($item->descargas ?? 0); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: #646970;">
                                    <?php echo esc_html__('No hay datos de popularidad', 'flavor-chat-ia'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Indicador de almacenamiento -->
    <div class="postbox" style="margin: 20px 0;">
        <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
            <span class="dashicons dashicons-database" style="margin-right: 8px;"></span>
            <?php echo esc_html__('Uso de Almacenamiento', 'flavor-chat-ia'); ?>
        </h2>
        <div class="inside" style="padding: 20px;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="flex: 1;">
                    <div style="background: #f0f0f1; border-radius: 10px; height: 20px; overflow: hidden;">
                        <div style="background: <?php echo $porcentaje_almacenamiento > 80 ? '#d63638' : ($porcentaje_almacenamiento > 60 ? '#dba617' : '#00a32a'); ?>; height: 100%; width: <?php echo $porcentaje_almacenamiento; ?>%; transition: width 0.3s;"></div>
                    </div>
                </div>
                <div style="min-width: 200px; text-align: right;">
                    <strong><?php echo number_format($almacenamiento_mb, 1); ?> MB</strong>
                    <span style="color: #646970;"> / <?php echo number_format($limite_almacenamiento_mb / 1024, 1); ?> GB</span>
                    <span style="color: <?php echo $porcentaje_almacenamiento > 80 ? '#d63638' : '#646970'; ?>; margin-left: 10px;">
                        (<?php echo $porcentaje_almacenamiento; ?>%)
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Verificar que Chart.js esté disponible
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js no está cargado');
        return;
    }

    // Gráfico de categorías (Doughnut)
    var ctxCategorias = document.getElementById('grafico-categorias');
    if (ctxCategorias) {
        new Chart(ctxCategorias.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo wp_json_encode($categorias_labels); ?>,
                datasets: [{
                    data: <?php echo wp_json_encode($categorias_data); ?>,
                    backgroundColor: ['#2271b1', '#00a32a', '#8c49d8', '#dba617', '#d63638', '#135e96'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Gráfico de actividad (Barras)
    var ctxActividad = document.getElementById('grafico-actividad');
    if (ctxActividad) {
        new Chart(ctxActividad.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo wp_json_encode($actividad_labels); ?>,
                datasets: [{
                    label: '<?php echo esc_js(__('Subidas', 'flavor-chat-ia')); ?>',
                    data: <?php echo wp_json_encode($actividad_data); ?>,
                    backgroundColor: '#2271b1',
                    borderRadius: 4,
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
.multimedia-quick-link:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}
.flavor-stat-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.12) !important;
}
.postbox {
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    background: #fff;
}
.postbox .hndle {
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
}
</style>
