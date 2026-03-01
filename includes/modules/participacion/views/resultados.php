<?php
/**
 * Vista Resultados y Analytics - Módulo Participación Ciudadana (Admin)
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

$tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
$tabla_votos = $wpdb->prefix . 'flavor_votos';
$tabla_comentarios = $wpdb->prefix . 'flavor_comentarios_propuesta';
$tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';
// Las encuestas se manejan a través del sistema de votaciones
$tabla_encuestas = $wpdb->prefix . 'flavor_votaciones';
$tabla_respuestas = $wpdb->prefix . 'flavor_votos';

// Estadísticas generales
$total_propuestas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_propuestas}") ?: 0;
$total_votos = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_votos}") ?: 0;
$total_comentarios = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_comentarios}") ?: 0;
$total_participantes = $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_votos}") ?: 0;

// Propuestas más votadas
$top_propuestas = $wpdb->get_results(
    "SELECT p.id, p.titulo, p.categoria, p.estado,
            p.votos_favor, p.votos_contra, p.votos_abstencion,
            (p.votos_favor - p.votos_contra) as balance,
            (SELECT COUNT(*) FROM {$tabla_votos} v WHERE v.propuesta_id = p.id) as total_votos,
            (SELECT COUNT(*) FROM {$tabla_comentarios} c WHERE c.propuesta_id = p.id) as total_comentarios
     FROM {$tabla_propuestas} p
     ORDER BY total_votos DESC
     LIMIT 10"
);

// Distribución por estado
$por_estado = $wpdb->get_results("SELECT estado, COUNT(*) as total FROM {$tabla_propuestas} GROUP BY estado ORDER BY total DESC");

// Distribución por categoría
$por_categoria = $wpdb->get_results("SELECT categoria, COUNT(*) as total FROM {$tabla_propuestas} WHERE categoria IS NOT NULL GROUP BY categoria ORDER BY total DESC");

// Actividad por mes (últimos 6 meses)
$actividad_mensual = $wpdb->get_results(
    "SELECT DATE_FORMAT(fecha_voto, '%Y-%m') as mes, COUNT(*) as votos
     FROM {$tabla_votos}
     WHERE fecha_voto >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(fecha_voto, '%Y-%m')
     ORDER BY mes ASC"
);

// Top votantes
$top_votantes = $wpdb->get_results(
    "SELECT v.usuario_id, u.display_name, COUNT(*) as total_votos
     FROM {$tabla_votos} v
     LEFT JOIN {$wpdb->users} u ON v.usuario_id = u.ID
     GROUP BY v.usuario_id
     ORDER BY total_votos DESC
     LIMIT 10"
);

// Propuestas implementadas
$implementadas = $wpdb->get_results(
    "SELECT p.*, u.display_name as autor
     FROM {$tabla_propuestas} p
     LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
     WHERE p.estado = 'implementada'
     ORDER BY p.fecha_implementacion DESC
     LIMIT 5"
);

$estados_colores = [
    'borrador' => '#646970', 'pendiente' => '#dba617', 'en_revision' => '#72aee6',
    'aprobada' => '#00a32a', 'rechazada' => '#d63638', 'en_votacion' => '#2271b1',
    'aceptada' => '#8c5ae8', 'implementada' => '#1d2327', 'archivada' => '#50575e',
];
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-chart-bar"></span> Resultados y Analytics</h1>
    <hr class="wp-header-end">

    <!-- KPIs Principales -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="postbox" style="margin: 0; padding: 20px; text-align: center; background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: white;">
            <span class="dashicons dashicons-lightbulb" style="font-size: 40px;"></span>
            <h3 style="margin: 10px 0 0; font-size: 32px;"><?php echo number_format($total_propuestas); ?></h3>
            <p style="margin: 5px 0 0; opacity: 0.9;">Total Propuestas</p>
        </div>
        <div class="postbox" style="margin: 0; padding: 20px; text-align: center; background: linear-gradient(135deg, #00a32a 0%, #008a20 100%); color: white;">
            <span class="dashicons dashicons-thumbs-up" style="font-size: 40px;"></span>
            <h3 style="margin: 10px 0 0; font-size: 32px;"><?php echo number_format($total_votos); ?></h3>
            <p style="margin: 5px 0 0; opacity: 0.9;">Total Votos</p>
        </div>
        <div class="postbox" style="margin: 0; padding: 20px; text-align: center; background: linear-gradient(135deg, #dba617 0%, #c49515 100%); color: white;">
            <span class="dashicons dashicons-format-chat" style="font-size: 40px;"></span>
            <h3 style="margin: 10px 0 0; font-size: 32px;"><?php echo number_format($total_comentarios); ?></h3>
            <p style="margin: 5px 0 0; opacity: 0.9;">Comentarios</p>
        </div>
        <div class="postbox" style="margin: 0; padding: 20px; text-align: center; background: linear-gradient(135deg, #8c5ae8 0%, #7b4fd8 100%); color: white;">
            <span class="dashicons dashicons-groups" style="font-size: 40px;"></span>
            <h3 style="margin: 10px 0 0; font-size: 32px;"><?php echo number_format($total_participantes); ?></h3>
            <p style="margin: 5px 0 0; opacity: 0.9;">Participantes</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <!-- Top Propuestas -->
        <div class="postbox" style="margin: 0;">
            <div class="postbox-header">
                <h2 style="padding: 10px 15px; margin: 0;"><span class="dashicons dashicons-star-filled"></span> Propuestas Más Votadas</h2>
            </div>
            <div class="inside" style="padding: 0;">
                <?php if (empty($top_propuestas)): ?>
                    <p style="padding: 20px; text-align: center; color: #646970;">Sin datos aún</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 40%;">Propuesta</th>
                                <th style="width: 20%;">Votos</th>
                                <th style="width: 15%;">Balance</th>
                                <th style="width: 20%;">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $pos = 1; foreach ($top_propuestas as $p): ?>
                                <tr>
                                    <td>
                                        <?php if ($pos <= 3): ?>
                                            <span style="font-size: 18px;"><?php echo $pos === 1 ? '🥇' : ($pos === 2 ? '🥈' : '🥉'); ?></span>
                                        <?php else: ?>
                                            <strong><?php echo $pos; ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($p->titulo); ?></strong>
                                        <?php if ($p->categoria): ?>
                                            <br><small style="color: #646970;"><?php echo esc_html(ucfirst($p->categoria)); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $p->total_votos; ?></strong> votos
                                        <br><small style="color: #646970;"><?php echo $p->total_comentarios; ?> comentarios</small>
                                    </td>
                                    <td>
                                        <span style="color: <?php echo $p->balance >= 0 ? '#00a32a' : '#d63638'; ?>; font-weight: bold; font-size: 16px;">
                                            <?php echo $p->balance >= 0 ? '+' : ''; ?><?php echo $p->balance; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="background: <?php echo $estados_colores[$p->estado] ?? '#646970'; ?>; color: white; padding: 3px 8px; border-radius: 3px; font-size: 10px;">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $p->estado))); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php $pos++; endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <!-- Distribución por Estado -->
            <div class="postbox" style="margin: 0 0 20px 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;"><span class="dashicons dashicons-chart-pie"></span> Por Estado</h2>
                </div>
                <div class="inside">
                    <?php foreach ($por_estado as $e):
                        $porcentaje = $total_propuestas > 0 ? round(($e->total / $total_propuestas) * 100) : 0;
                        $color = $estados_colores[$e->estado] ?? '#646970';
                    ?>
                        <div style="margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span style="font-size: 12px;"><?php echo ucfirst(str_replace('_', ' ', $e->estado)); ?></span>
                                <span style="font-size: 12px;"><strong><?php echo $e->total; ?></strong> (<?php echo $porcentaje; ?>%)</span>
                            </div>
                            <div style="background: #e0e0e0; height: 8px; border-radius: 4px;">
                                <div style="background: <?php echo $color; ?>; height: 100%; width: <?php echo $porcentaje; ?>%; border-radius: 4px;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Votantes -->
            <div class="postbox" style="margin: 0 0 20px 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;"><span class="dashicons dashicons-awards"></span> Ciudadanos Activos</h2>
                </div>
                <div class="inside" style="padding: 0;">
                    <ul style="margin: 0; padding: 0; list-style: none;">
                        <?php foreach ($top_votantes as $v): ?>
                            <li style="padding: 8px 15px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between;">
                                <span><?php echo esc_html($v->display_name ?: 'Usuario #' . $v->usuario_id); ?></span>
                                <strong><?php echo $v->total_votos; ?> votos</strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Por Categoría -->
            <div class="postbox" style="margin: 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;"><span class="dashicons dashicons-category"></span> Por Categoría</h2>
                </div>
                <div class="inside">
                    <?php if (empty($por_categoria)): ?>
                        <p style="color: #646970; text-align: center;">Sin categorías</p>
                    <?php else: ?>
                        <?php
                        $colores = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
                        $i = 0;
                        foreach ($por_categoria as $c):
                            $color = $colores[$i % count($colores)];
                        ?>
                            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                                <span>
                                    <span style="display: inline-block; width: 12px; height: 12px; background: <?php echo $color; ?>; border-radius: 2px; margin-right: 8px;"></span>
                                    <?php echo esc_html(ucfirst($c->categoria)); ?>
                                </span>
                                <strong><?php echo $c->total; ?></strong>
                            </div>
                        <?php $i++; endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Propuestas Implementadas -->
    <?php if (!empty($implementadas)): ?>
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header">
            <h2 style="padding: 10px 15px; margin: 0;"><span class="dashicons dashicons-flag"></span> Propuestas Implementadas</h2>
        </div>
        <div class="inside">
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                <?php foreach ($implementadas as $p): ?>
                    <div style="background: #f0f6fc; border-left: 4px solid #00a32a; padding: 15px; border-radius: 0 4px 4px 0;">
                        <strong><?php echo esc_html($p->titulo); ?></strong>
                        <p style="margin: 8px 0 0; font-size: 13px; color: #646970;">
                            por <?php echo esc_html($p->autor ?: 'Ciudadano'); ?>
                            · Implementada: <?php echo $p->fecha_implementacion ? date_i18n('d/m/Y', strtotime($p->fecha_implementacion)) : '-'; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
