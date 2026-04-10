<?php
/**
 * Vista Procesos de Votación - Módulo Participación Ciudadana (Admin)
 *
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

$tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
$tabla_votos = $wpdb->prefix . 'flavor_votos';
$tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';
// Nota: encuestas y respuestas se manejan a través del sistema de votaciones
$tabla_encuestas = $wpdb->prefix . 'flavor_votaciones';
$tabla_respuestas = $wpdb->prefix . 'flavor_votos';

// Propuestas en votación
$propuestas_votacion = $wpdb->get_results(
    "SELECT p.*, u.display_name as autor,
            (SELECT COUNT(*) FROM {$tabla_votos} v WHERE v.propuesta_id = p.id AND v.tipo_voto = 'favor') as favor,
            (SELECT COUNT(*) FROM {$tabla_votos} v WHERE v.propuesta_id = p.id AND v.tipo_voto = 'contra') as contra,
            (SELECT COUNT(*) FROM {$tabla_votos} v WHERE v.propuesta_id = p.id AND v.tipo_voto = 'abstencion') as abstencion,
            (SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_votos} v WHERE v.propuesta_id = p.id) as participantes
     FROM {$tabla_propuestas} p
     LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
     WHERE p.estado = 'en_votacion'
     ORDER BY p.fecha_inicio_votacion DESC"
);

// Encuestas activas
$encuestas_activas = $wpdb->get_results(
    "SELECT e.*, u.display_name as creador,
            (SELECT COUNT(*) FROM {$tabla_respuestas} r WHERE r.encuesta_id = e.id) as respuestas
     FROM {$tabla_encuestas} e
     LEFT JOIN {$wpdb->users} u ON e.creado_por = u.ID
     WHERE e.estado = 'activa'
     ORDER BY e.fecha_fin ASC"
);

// Estadísticas generales
$total_votos_hoy = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_votos} WHERE DATE(fecha_voto) = CURDATE()") ?: 0;
$total_votantes_unicos = $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_votos}") ?: 0;
$propuestas_activas = count($propuestas_votacion);
$encuestas_count = count($encuestas_activas);

// Últimas votaciones
$ultimas_votaciones = $wpdb->get_results(
    "SELECT v.*, p.titulo as propuesta, u.display_name as votante
     FROM {$tabla_votos} v
     LEFT JOIN {$tabla_propuestas} p ON v.propuesta_id = p.id
     LEFT JOIN {$wpdb->users} u ON v.usuario_id = u.ID
     ORDER BY v.fecha_voto DESC
     LIMIT 10"
);
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-thumbs-up"></span> Procesos de Votación</h1>
    <hr class="wp-header-end">

    <!-- KPIs -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="postbox" style="margin: 0; padding: 15px; text-align: center;">
            <span class="dashicons dashicons-chart-bar" style="font-size: 32px; color: #2271b1;"></span>
            <h3 style="margin: 10px 0 0; font-size: 24px;"><?php echo $total_votos_hoy; ?></h3>
            <p style="margin: 0; color: #646970;">Votos Hoy</p>
        </div>
        <div class="postbox" style="margin: 0; padding: 15px; text-align: center;">
            <span class="dashicons dashicons-groups" style="font-size: 32px; color: #00a32a;"></span>
            <h3 style="margin: 10px 0 0; font-size: 24px;"><?php echo $total_votantes_unicos; ?></h3>
            <p style="margin: 0; color: #646970;">Votantes Únicos</p>
        </div>
        <div class="postbox" style="margin: 0; padding: 15px; text-align: center;">
            <span class="dashicons dashicons-lightbulb" style="font-size: 32px; color: #dba617;"></span>
            <h3 style="margin: 10px 0 0; font-size: 24px;"><?php echo $propuestas_activas; ?></h3>
            <p style="margin: 0; color: #646970;">Propuestas Activas</p>
        </div>
        <div class="postbox" style="margin: 0; padding: 15px; text-align: center;">
            <span class="dashicons dashicons-forms" style="font-size: 32px; color: #8c5ae8;"></span>
            <h3 style="margin: 10px 0 0; font-size: 24px;"><?php echo $encuestas_count; ?></h3>
            <p style="margin: 0; color: #646970;">Encuestas Activas</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <!-- Propuestas en Votación -->
        <div class="postbox" style="margin: 0;">
            <div class="postbox-header">
                <h2 style="padding: 10px 15px; margin: 0;">
                    <span class="dashicons dashicons-megaphone"></span> Propuestas en Votación
                </h2>
            </div>
            <div class="inside" style="padding: 0;">
                <?php if (empty($propuestas_votacion)): ?>
                    <p style="padding: 20px; text-align: center; color: #646970;">No hay propuestas en votación actualmente.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Propuesta</th>
                                <th style="width: 25%;">Votos</th>
                                <th style="width: 20%;">Participación</th>
                                <th style="width: 20%;">Fechas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($propuestas_votacion as $p):
                                $total = $p->favor + $p->contra + $p->abstencion;
                                $porcentaje_favor = $total > 0 ? round(($p->favor / $total) * 100) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($p->titulo); ?></strong>
                                        <br><small style="color: #646970;">por <?php echo esc_html($p->autor ?: 'Anónimo'); ?></small>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <span style="color: #00a32a;" title="A favor">+<?php echo $p->favor; ?></span>
                                            <span style="color: #d63638;" title="En contra">-<?php echo $p->contra; ?></span>
                                            <span style="color: #646970;" title="Abstención">~<?php echo $p->abstencion; ?></span>
                                        </div>
                                        <div style="background: #e0e0e0; height: 8px; border-radius: 4px; margin-top: 5px;">
                                            <div style="background: linear-gradient(to right, #00a32a <?php echo $porcentaje_favor; ?>%, #d63638 <?php echo $porcentaje_favor; ?>%); height: 100%; border-radius: 4px;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo $p->participantes; ?></strong> ciudadanos
                                    </td>
                                    <td>
                                        <small>
                                            Inicio: <?php echo $p->fecha_inicio_votacion ? date_i18n('d/m/Y', strtotime($p->fecha_inicio_votacion)) : '-'; ?>
                                            <br>Fin: <?php echo $p->fecha_fin_votacion ? date_i18n('d/m/Y', strtotime($p->fecha_fin_votacion)) : 'Sin fecha'; ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <!-- Encuestas Activas -->
            <div class="postbox" style="margin: 0 0 20px 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;">
                        <span class="dashicons dashicons-forms"></span> Encuestas Activas
                    </h2>
                </div>
                <div class="inside">
                    <?php if (empty($encuestas_activas)): ?>
                        <p style="text-align: center; color: #646970;">Sin encuestas activas</p>
                    <?php else: ?>
                        <?php foreach ($encuestas_activas as $e): ?>
                            <div style="padding: 10px; border-bottom: 1px solid #e0e0e0;">
                                <strong><?php echo esc_html($e->titulo); ?></strong>
                                <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                                    <small style="color: #646970;"><?php echo $e->respuestas; ?> respuestas</small>
                                    <small style="color: #d63638;">Cierra: <?php echo date_i18n('d/m H:i', strtotime($e->fecha_fin)); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Últimas Votaciones -->
            <div class="postbox" style="margin: 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;">
                        <span class="dashicons dashicons-clock"></span> Actividad Reciente
                    </h2>
                </div>
                <div class="inside" style="padding: 0;">
                    <?php if (empty($ultimas_votaciones)): ?>
                        <p style="padding: 15px; text-align: center; color: #646970;">Sin actividad</p>
                    <?php else: ?>
                        <ul style="margin: 0; padding: 0; list-style: none;">
                            <?php foreach ($ultimas_votaciones as $v): ?>
                                <li style="padding: 10px 15px; border-bottom: 1px solid #e0e0e0; font-size: 13px;">
                                    <span style="color: <?php echo $v->tipo_voto === 'favor' ? '#00a32a' : ($v->tipo_voto === 'contra' ? '#d63638' : '#646970'); ?>;">
                                        <?php echo $v->tipo_voto === 'favor' ? '+1' : ($v->tipo_voto === 'contra' ? '-1' : '~'); ?>
                                    </span>
                                    <strong><?php echo esc_html($v->votante ?: 'Anónimo'); ?></strong>
                                    → <?php echo esc_html(substr($v->propuesta ?: 'Propuesta', 0, 30)); ?>...
                                    <br><small style="color: #646970;"><?php echo human_time_diff(strtotime($v->fecha_voto), current_time('timestamp')); ?> atrás</small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
