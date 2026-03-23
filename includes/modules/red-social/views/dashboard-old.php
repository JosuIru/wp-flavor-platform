<?php
/**
 * Vista Dashboard - Red Social
 *
 * Panel principal con estadísticas de publicaciones y actividad
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');

// Tablas
$tabla_posts = $wpdb->prefix . 'flavor_red_social_posts';
$tabla_interacciones = $wpdb->prefix . 'flavor_red_social_interacciones';
$tabla_comentarios = $wpdb->prefix . 'flavor_red_social_comentarios';
$tabla_seguidores = $wpdb->prefix . 'flavor_red_social_seguidores';
$tabla_reportes = $wpdb->prefix . 'flavor_red_social_reportes';

// Verificar existencia de tablas
$tabla_posts_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_posts'") === $tabla_posts;
$tabla_interacciones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_interacciones'") === $tabla_interacciones;
$tabla_comentarios_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_comentarios'") === $tabla_comentarios;
$tabla_reportes_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_reportes'") === $tabla_reportes;

// Inicializar estadísticas
$total_publicaciones = 0;
$publicaciones_hoy = 0;
$usuarios_activos = 0;
$total_interacciones = 0;
$total_comentarios = 0;
$reportes_pendientes = 0;
$nuevos_usuarios_semana = 0;
$publicaciones_recientes = [];
$actividad_diaria = [];
$distribucion_tipo = [];
$usuarios_mas_activos = [];

$fecha_hoy = date('Y-m-d');

if ($tabla_posts_existe) {
    $total_publicaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_posts WHERE estado = 'publicado'");

    $publicaciones_hoy = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_posts WHERE DATE(fecha_creacion) = %s AND estado = 'publicado'",
        $fecha_hoy
    ));

    $usuarios_activos = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_posts WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );

    // Publicaciones recientes
    $publicaciones_recientes = $wpdb->get_results("
        SELECT p.*, u.display_name as autor_nombre
        FROM $tabla_posts p
        LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        WHERE p.estado = 'publicado'
        ORDER BY p.fecha_creacion DESC
        LIMIT 5
    ");

    // Actividad diaria (últimos 7 días)
    $actividad_diaria = $wpdb->get_results("
        SELECT DATE(fecha_creacion) as fecha, COUNT(*) as publicaciones
        FROM $tabla_posts
        WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(fecha_creacion)
        ORDER BY fecha ASC
    ");

    // Distribución por tipo de contenido
    $distribucion_tipo = $wpdb->get_results("
        SELECT
            COALESCE(tipo, 'texto') as tipo,
            COUNT(*) as total
        FROM $tabla_posts
        WHERE estado = 'publicado'
        GROUP BY tipo
        ORDER BY total DESC
    ");

    // Usuarios más activos
    $usuarios_mas_activos = $wpdb->get_results("
        SELECT
            p.usuario_id,
            u.display_name as nombre,
            COUNT(*) as publicaciones,
            COALESCE(SUM(p.likes), 0) as total_likes
        FROM $tabla_posts p
        LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        WHERE p.estado = 'publicado'
        GROUP BY p.usuario_id
        ORDER BY publicaciones DESC
        LIMIT 5
    ");
}

if ($tabla_interacciones_existe) {
    $total_interacciones = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_interacciones WHERE fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
}

if ($tabla_comentarios_existe) {
    $total_comentarios = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_comentarios WHERE fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
}

if ($tabla_reportes_existe) {
    $reportes_pendientes = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_reportes WHERE estado = 'pendiente'"
    );
}

// Nuevos usuarios esta semana (de WordPress)
$nuevos_usuarios_semana = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
);

// Preparar datos para gráficos
$tipos_labels = array_map(function($t) {
    $nombres = ['texto' => 'Texto', 'imagen' => 'Imagen', 'video' => 'Video', 'enlace' => 'Enlace'];
    return $nombres[$t->tipo] ?? ucfirst($t->tipo);
}, $distribucion_tipo);
$tipos_data = array_map(function($t) { return (int) $t->total; }, $distribucion_tipo);

$actividad_labels = array_map(function($a) {
    return date_i18n('D', strtotime($a->fecha));
}, $actividad_diaria);
$actividad_data = array_map(function($a) { return (int) $a->publicaciones; }, $actividad_diaria);
?>

<div class="wrap">
    <?php
    // Sección de ayuda
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('red_social');
    }
    ?>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-share"></span>
        <?php echo esc_html__('Dashboard - Red Social', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <?php if (!$tabla_posts_existe): ?>
        <div class="notice notice-info">
            <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('No hay datos disponibles: faltan tablas del módulo Red Social.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($is_dashboard_viewer): ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('Vista resumida para gestor de grupos. La moderación y configuración avanzada siguen reservadas a administración.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($reportes_pendientes > 0 && !$is_dashboard_viewer): ?>
        <div class="notice notice-warning">
            <p>
                <span class="dashicons dashicons-warning"></span>
                <?php printf(
                    esc_html__('Hay %d reportes pendientes de revisión.', 'flavor-chat-ia'),
                    $reportes_pendientes
                ); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-moderacion')); ?>" class="button button-small" style="margin-left: 10px;">
                    <?php echo esc_html__('Revisar ahora', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Accesos Rápidos -->
    <div class="flavor-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin: 20px 0;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-publicaciones')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-post" style="font-size: 22px; color: #2271b1;"></span>
            <span><?php esc_html_e('Publicaciones', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-usuarios')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-groups" style="font-size: 22px; color: #00a32a;"></span>
            <span><?php esc_html_e('Usuarios', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-moderacion')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-shield" style="font-size: 22px; color: #dba617;"></span>
            <span><?php esc_html_e('Moderación', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('red_social', '')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-external" style="font-size: 22px; color: #8c52ff;"></span>
            <span><?php esc_html_e('Ver Portal', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-red-social-estadisticas')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-chart-bar" style="font-size: 22px; color: #d63638;"></span>
            <span><?php esc_html_e('Estadísticas', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Estadísticas Principales -->
    <div id="red-social-stats" class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Total Publicaciones', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($total_publicaciones); ?></h2>
                </div>
                <span class="dashicons dashicons-admin-post" style="font-size: 40px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #00a32a; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Publicaciones Hoy', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($publicaciones_hoy); ?></h2>
                </div>
                <span class="dashicons dashicons-calendar-alt" style="font-size: 40px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #8c52ff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Usuarios Activos', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($usuarios_activos); ?></h2>
                    <small style="color: #646970;"><?php echo esc_html__('últimos 7 días', 'flavor-chat-ia'); ?></small>
                </div>
                <span class="dashicons dashicons-groups" style="font-size: 40px; color: #8c52ff; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #d63638; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Interacciones (24h)', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($total_interacciones); ?></h2>
                </div>
                <span class="dashicons dashicons-heart" style="font-size: 40px; color: #d63638; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #dba617; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Comentarios (7d)', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($total_comentarios); ?></h2>
                </div>
                <span class="dashicons dashicons-format-chat" style="font-size: 40px; color: #dba617; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; border-left: 4px solid #135e96; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px;"><?php echo esc_html__('Nuevos Usuarios', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 8px 0 0; font-size: 28px; color: #1d2327;"><?php echo number_format($nuevos_usuarios_semana); ?></h2>
                    <small style="color: #646970;"><?php echo esc_html__('esta semana', 'flavor-chat-ia'); ?></small>
                </div>
                <span class="dashicons dashicons-admin-users" style="font-size: 40px; color: #135e96; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 20px 0;">

        <!-- Gráfico de tipos de contenido -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-chart-pie" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Tipos de Contenido', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 20px;">
                <canvas id="grafico-tipos" style="max-height: 280px;"></canvas>
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

        <!-- Publicaciones Recientes -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-update" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Publicaciones Recientes', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 0;">
                <table class="wp-list-table widefat fixed striped" style="border: none;">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Contenido', 'flavor-chat-ia'); ?></th>
                            <th style="width: 100px;"><?php echo esc_html__('Autor', 'flavor-chat-ia'); ?></th>
                            <th style="width: 60px;"><?php echo esc_html__('Likes', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($publicaciones_recientes)): ?>
                            <?php foreach ($publicaciones_recientes as $post): ?>
                                <?php
                                $tipo_icon = 'dashicons-edit';
                                $tipo_color = '#2271b1';
                                if (isset($post->tipo)) {
                                    if ($post->tipo === 'imagen') {
                                        $tipo_icon = 'dashicons-format-image';
                                        $tipo_color = '#00a32a';
                                    } elseif ($post->tipo === 'video') {
                                        $tipo_icon = 'dashicons-video-alt3';
                                        $tipo_color = '#8c52ff';
                                    } elseif ($post->tipo === 'enlace') {
                                        $tipo_icon = 'dashicons-admin-links';
                                        $tipo_color = '#dba617';
                                    }
                                }
                                ?>
                                <tr>
                                    <td>
                                        <span class="dashicons <?php echo esc_attr($tipo_icon); ?>" style="color: <?php echo esc_attr($tipo_color); ?>; font-size: 14px; margin-right: 5px;"></span>
                                        <?php echo esc_html(wp_trim_words($post->contenido ?? '', 10, '...')); ?>
                                    </td>
                                    <td><?php echo esc_html($post->autor_nombre ?: __('Anónimo', 'flavor-chat-ia')); ?></td>
                                    <td style="text-align: center;">
                                        <span style="color: #d63638;">
                                            <span class="dashicons dashicons-heart" style="font-size: 14px;"></span>
                                            <?php echo number_format($post->likes ?? 0); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo esc_html(human_time_diff(strtotime($post->fecha_creacion), current_time('timestamp'))); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: #646970;">
                                    <?php echo esc_html__('No hay publicaciones recientes', 'flavor-chat-ia'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Usuarios más activos -->
        <div class="postbox" style="margin: 0;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-star-filled" style="margin-right: 8px;"></span>
                <?php echo esc_html__('Usuarios Más Activos', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside" style="padding: 0;">
                <table class="wp-list-table widefat fixed striped" style="border: none;">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th><?php echo esc_html__('Usuario', 'flavor-chat-ia'); ?></th>
                            <th style="width: 100px;"><?php echo esc_html__('Publicaciones', 'flavor-chat-ia'); ?></th>
                            <th style="width: 100px;"><?php echo esc_html__('Total Likes', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios_mas_activos)): ?>
                            <?php $posicion = 1; ?>
                            <?php foreach ($usuarios_mas_activos as $usuario): ?>
                                <?php
                                $medalla_color = '#646970';
                                if ($posicion === 1) $medalla_color = '#dba617';
                                elseif ($posicion === 2) $medalla_color = '#a7aaad';
                                elseif ($posicion === 3) $medalla_color = '#cd7f32';
                                ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <?php if ($posicion <= 3): ?>
                                            <span class="dashicons dashicons-awards" style="color: <?php echo esc_attr($medalla_color); ?>;"></span>
                                        <?php else: ?>
                                            <?php echo $posicion; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($usuario->nombre ?: __('Usuario', 'flavor-chat-ia')); ?></strong>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="background: #e7f3ff; color: #2271b1; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                            <?php echo number_format($usuario->publicaciones); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="background: #fce8e8; color: #d63638; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                            <?php echo number_format($usuario->total_likes); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php $posicion++; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: #646970;">
                                    <?php echo esc_html__('No hay datos de usuarios', 'flavor-chat-ia'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

    // Gráfico de tipos de contenido (Doughnut)
    var ctxTipos = document.getElementById('grafico-tipos');
    if (ctxTipos) {
        new Chart(ctxTipos.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo wp_json_encode($tipos_labels); ?>,
                datasets: [{
                    data: <?php echo wp_json_encode($tipos_data); ?>,
                    backgroundColor: ['#2271b1', '#00a32a', '#8c52ff', '#dba617'],
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

    // Gráfico de actividad (Línea)
    var ctxActividad = document.getElementById('grafico-actividad');
    if (ctxActividad) {
        new Chart(ctxActividad.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo wp_json_encode($actividad_labels); ?>,
                datasets: [{
                    label: '<?php echo esc_js(__('Publicaciones', 'flavor-chat-ia')); ?>',
                    data: <?php echo wp_json_encode($actividad_data); ?>,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#2271b1',
                    pointRadius: 4
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
                            stepSize: 10
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
.flavor-quick-link:hover {
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
