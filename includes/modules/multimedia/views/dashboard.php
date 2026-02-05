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
$tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
$tabla_albumes = $wpdb->prefix . 'flavor_multimedia_albumes';

// Estadísticas generales
$total_multimedia = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE estado = 'aprobado'");
$total_fotos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE tipo = 'foto' AND estado = 'aprobado'");
$total_videos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE tipo = 'video' AND estado = 'aprobado'");
$total_albumes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_albumes");
$pendientes_moderacion = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_multimedia WHERE estado = 'pendiente'");

// Contenido reciente
$multimedia_reciente = $wpdb->get_results("
    SELECT * FROM $tabla_multimedia
    WHERE estado = 'aprobado'
    ORDER BY fecha_subida DESC
    LIMIT 12
");

// Estadísticas por categoría
$por_categoria = $wpdb->get_results("
    SELECT categoria, COUNT(*) as total
    FROM $tabla_multimedia
    WHERE estado = 'aprobado' AND categoria IS NOT NULL
    GROUP BY categoria
    ORDER BY total DESC
    LIMIT 10
");
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-format-gallery"></span>
        Dashboard Multimedia
    </h1>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;">Total Multimedia</p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #2271b1;"><?php echo number_format($total_multimedia); ?></h2>
                </div>
                <span class="dashicons dashicons-format-gallery" style="font-size: 48px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;">Fotos</p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #00a32a;"><?php echo number_format($total_fotos); ?></h2>
                </div>
                <span class="dashicons dashicons-camera" style="font-size: 48px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;">Videos</p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #8c49d8;"><?php echo number_format($total_videos); ?></h2>
                </div>
                <span class="dashicons dashicons-video-alt3" style="font-size: 48px; color: #8c49d8; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #666; font-size: 14px;">Álbumes</p>
                    <h2 style="margin: 10px 0; font-size: 32px; color: #dba617;"><?php echo number_format($total_albumes); ?></h2>
                </div>
                <span class="dashicons dashicons-images-alt2" style="font-size: 48px; color: #dba617; opacity: 0.3;"></span>
            </div>
        </div>

        <?php if ($pendientes_moderacion > 0): ?>
            <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #d63638;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <p style="margin: 0; color: #666; font-size: 14px;">Pendientes</p>
                        <h2 style="margin: 10px 0; font-size: 32px; color: #d63638;"><?php echo number_format($pendientes_moderacion); ?></h2>
                        <a href="?page=flavor-chat-multimedia-moderacion" class="button button-small" style="margin-top: 10px;">
                            Moderar
                        </a>
                    </div>
                    <span class="dashicons dashicons-warning" style="font-size: 48px; color: #d63638; opacity: 0.3;"></span>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">

        <!-- Contenido reciente -->
        <div class="flavor-gallery-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-images-alt"></span>
                Contenido Reciente
            </h3>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                <?php foreach ($multimedia_reciente as $item): ?>
                    <div style="position: relative; padding-top: 100%; background: #f0f0f1; border-radius: 4px; overflow: hidden; cursor: pointer;" onclick="verMultimedia(<?php echo $item->id; ?>)">
                        <?php if ($item->tipo == 'foto'): ?>
                            <img src="<?php echo esc_url($item->url); ?>" alt="<?php echo esc_attr($item->titulo ?? ''); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-video-alt3" style="font-size: 32px; color: #fff;"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Por categoría -->
        <div class="flavor-chart-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;">
                <span class="dashicons dashicons-category"></span>
                Por Categoría
            </h3>
            <canvas id="grafico-categorias" style="max-height: 300px;"></canvas>
        </div>

    </div>

</div>

<script>
jQuery(document).ready(function($) {
    var ctxCategorias = document.getElementById('grafico-categorias').getContext('2d');
    new Chart(ctxCategorias, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map(function($c) { return $c->categoria; }, $por_categoria)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_map(function($c) { return $c->total; }, $por_categoria)); ?>,
                backgroundColor: ['#2271b1', '#00a32a', '#8c49d8', '#dba617', '#d63638', '#f0f0f1', '#135e96', '#008a20', '#69327f', '#996800']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

function verMultimedia(id) {
    alert('Ver multimedia #' + id);
}
</script>

<style>
.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}
</style>
