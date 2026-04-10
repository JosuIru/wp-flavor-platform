<?php
/**
 * Vista Seguimiento de Votos - Módulo Presupuestos Participativos (Admin)
 *
 * @package FlavorPlatform
 * @subpackage Modules\PresupuestosParticipativos
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

// Tablas
$tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
$tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
$tabla_votos = $wpdb->prefix . 'flavor_pp_votos';

// Filtro de edición
$edicion_id = isset($_GET['edicion_id']) ? intval($_GET['edicion_id']) : 0;

// Obtener ediciones disponibles
$ediciones = $wpdb->get_results("SELECT id, nombre, anio, fase, estado, votos_por_ciudadano, fecha_inicio_votacion, fecha_fin_votacion FROM {$tabla_ediciones} ORDER BY anio DESC, id DESC");

// Si no hay edición seleccionada, usar la activa más reciente
if (!$edicion_id && !empty($ediciones)) {
    foreach ($ediciones as $edicion) {
        if ($edicion->estado === 'activo') {
            $edicion_id = $edicion->id;
            break;
        }
    }
    if (!$edicion_id) {
        $edicion_id = $ediciones[0]->id;
    }
}

// Datos de la edición actual
$edicion_actual = null;
if ($edicion_id) {
    $edicion_actual = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$tabla_ediciones} WHERE id = %d",
        $edicion_id
    ));
}

// Estadísticas generales de votación
$total_votos = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_votos} WHERE edicion_id = %d",
    $edicion_id
)) ?: 0;

$total_votantes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_votos} WHERE edicion_id = %d",
    $edicion_id
)) ?: 0;

$proyectos_votados = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT proyecto_id) FROM {$tabla_votos} WHERE edicion_id = %d",
    $edicion_id
)) ?: 0;

$total_proyectos_votacion = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_proyectos} WHERE edicion_id = %d AND estado = 'en_votacion'",
    $edicion_id
)) ?: 0;

// Ranking de proyectos por votos
$ranking_proyectos = $wpdb->get_results($wpdb->prepare(
    "SELECT p.id, p.titulo, p.categoria, p.presupuesto_solicitado, p.votos_recibidos,
            COUNT(v.id) as votos_conteo,
            u.display_name as proponente
     FROM {$tabla_proyectos} p
     LEFT JOIN {$tabla_votos} v ON v.proyecto_id = p.id
     LEFT JOIN {$wpdb->users} u ON p.proponente_id = u.ID
     WHERE p.edicion_id = %d AND p.estado IN ('en_votacion', 'seleccionado')
     GROUP BY p.id
     ORDER BY votos_conteo DESC, p.votos_recibidos DESC
     LIMIT 20",
    $edicion_id
));

// Evolución de votos por día (últimos 14 días)
$votos_por_dia = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE(fecha_voto) as dia, COUNT(*) as total
     FROM {$tabla_votos}
     WHERE edicion_id = %d AND fecha_voto >= DATE_SUB(NOW(), INTERVAL 14 DAY)
     GROUP BY DATE(fecha_voto)
     ORDER BY dia ASC",
    $edicion_id
));

// Votos por categoría
$votos_por_categoria = $wpdb->get_results($wpdb->prepare(
    "SELECT p.categoria, COUNT(v.id) as total_votos, COUNT(DISTINCT p.id) as proyectos
     FROM {$tabla_proyectos} p
     LEFT JOIN {$tabla_votos} v ON v.proyecto_id = p.id
     WHERE p.edicion_id = %d
     GROUP BY p.categoria
     ORDER BY total_votos DESC",
    $edicion_id
));

// Últimos votos emitidos
$ultimos_votos = $wpdb->get_results($wpdb->prepare(
    "SELECT v.*, p.titulo as proyecto_titulo, u.display_name as usuario_nombre
     FROM {$tabla_votos} v
     LEFT JOIN {$tabla_proyectos} p ON v.proyecto_id = p.id
     LEFT JOIN {$wpdb->users} u ON v.usuario_id = u.ID
     WHERE v.edicion_id = %d
     ORDER BY v.fecha_voto DESC
     LIMIT 15",
    $edicion_id
));

// Votantes más activos
$votantes_activos = $wpdb->get_results($wpdb->prepare(
    "SELECT v.usuario_id, u.display_name, u.user_email, COUNT(*) as total_votos, MAX(v.fecha_voto) as ultimo_voto
     FROM {$tabla_votos} v
     LEFT JOIN {$wpdb->users} u ON v.usuario_id = u.ID
     WHERE v.edicion_id = %d
     GROUP BY v.usuario_id
     ORDER BY total_votos DESC
     LIMIT 10",
    $edicion_id
));

$max_votos = $edicion_actual ? intval($edicion_actual->votos_por_ciudadano) : 3;
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-yes"></span> Seguimiento de Votos</h1>
    <hr class="wp-header-end">

    <!-- Selector de Edición -->
    <div class="postbox" style="margin-top: 20px; padding: 15px;">
        <form method="get" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
            <input type="hidden" name="vista" value="votos">
            <label for="edicion_id"><strong>Edición/Ciclo:</strong></label>
            <select name="edicion_id" id="edicion_id" onchange="this.form.submit()" style="min-width: 250px;">
                <?php foreach ($ediciones as $edicion): ?>
                    <option value="<?php echo $edicion->id; ?>" <?php selected($edicion_id, $edicion->id); ?>>
                        <?php echo esc_html($edicion->nombre . ' (' . $edicion->anio . ')'); ?>
                        <?php if ($edicion->estado === 'activo'): ?> - ACTIVO<?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($edicion_actual): ?>
                <span class="description">
                    Fase: <strong><?php echo esc_html(ucfirst($edicion_actual->fase)); ?></strong>
                    <?php if ($edicion_actual->fase === 'votacion'): ?>
                        <span style="color: #00a32a;">● En curso</span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!$edicion_actual): ?>
        <div class="notice notice-warning">
            <p>No hay ediciones configuradas.</p>
        </div>
    <?php else: ?>

    <!-- KPIs de Votación -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="postbox" style="margin: 0; padding: 20px; text-align: center;">
            <span class="dashicons dashicons-chart-bar" style="font-size: 40px; color: #2271b1; margin-bottom: 10px;"></span>
            <h3 style="margin: 0; font-size: 32px; color: #1d2327;"><?php echo number_format($total_votos); ?></h3>
            <p style="margin: 5px 0 0; color: #646970;">Votos Totales</p>
        </div>

        <div class="postbox" style="margin: 0; padding: 20px; text-align: center;">
            <span class="dashicons dashicons-groups" style="font-size: 40px; color: #00a32a; margin-bottom: 10px;"></span>
            <h3 style="margin: 0; font-size: 32px; color: #1d2327;"><?php echo number_format($total_votantes); ?></h3>
            <p style="margin: 5px 0 0; color: #646970;">Ciudadanos Participantes</p>
        </div>

        <div class="postbox" style="margin: 0; padding: 20px; text-align: center;">
            <span class="dashicons dashicons-portfolio" style="font-size: 40px; color: #dba617; margin-bottom: 10px;"></span>
            <h3 style="margin: 0; font-size: 32px; color: #1d2327;"><?php echo $proyectos_votados; ?>/<?php echo $total_proyectos_votacion; ?></h3>
            <p style="margin: 5px 0 0; color: #646970;">Proyectos con Votos</p>
        </div>

        <div class="postbox" style="margin: 0; padding: 20px; text-align: center;">
            <span class="dashicons dashicons-chart-line" style="font-size: 40px; color: #8c5ae8; margin-bottom: 10px;"></span>
            <h3 style="margin: 0; font-size: 32px; color: #1d2327;">
                <?php echo $total_votantes > 0 ? number_format($total_votos / $total_votantes, 1) : '0'; ?>
            </h3>
            <p style="margin: 5px 0 0; color: #646970;">Media votos/ciudadano (máx <?php echo $max_votos; ?>)</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <!-- Ranking de Proyectos -->
        <div class="postbox" style="margin: 0;">
            <div class="postbox-header">
                <h2 style="padding: 10px 15px; margin: 0;">
                    <span class="dashicons dashicons-awards"></span>
                    Ranking de Proyectos
                </h2>
            </div>
            <div class="inside" style="padding: 0;">
                <?php if (empty($ranking_proyectos)): ?>
                    <p style="padding: 20px; text-align: center; color: #646970;">No hay proyectos en fase de votación.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 8%;">#</th>
                                <th style="width: 40%;">Proyecto</th>
                                <th style="width: 20%;">Categoría</th>
                                <th style="width: 15%;">Presupuesto</th>
                                <th style="width: 17%;">Votos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $posicion = 1;
                            $max_votos_proyecto = $ranking_proyectos[0]->votos_conteo ?? 1;
                            foreach ($ranking_proyectos as $proyecto):
                                $porcentaje_barra = $max_votos_proyecto > 0 ? ($proyecto->votos_conteo / $max_votos_proyecto) * 100 : 0;
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($posicion <= 3): ?>
                                            <span style="font-size: 20px;">
                                                <?php echo $posicion === 1 ? '🥇' : ($posicion === 2 ? '🥈' : '🥉'); ?>
                                            </span>
                                        <?php else: ?>
                                            <strong><?php echo $posicion; ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($proyecto->titulo); ?></strong>
                                        <br><small style="color: #646970;">por <?php echo esc_html($proyecto->proponente ?: 'Anónimo'); ?></small>
                                    </td>
                                    <td><?php echo esc_html(ucfirst($proyecto->categoria)); ?></td>
                                    <td><?php echo number_format($proyecto->presupuesto_solicitado, 0, ',', '.'); ?> €</td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <strong style="min-width: 30px;"><?php echo intval($proyecto->votos_conteo); ?></strong>
                                            <div style="flex: 1; background: #e0e0e0; height: 12px; border-radius: 6px;">
                                                <div style="background: #2271b1; height: 100%; width: <?php echo $porcentaje_barra; ?>%; border-radius: 6px;"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php
                                $posicion++;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <!-- Votos por Categoría -->
            <div class="postbox" style="margin: 0 0 20px 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;">
                        <span class="dashicons dashicons-category"></span>
                        Votos por Categoría
                    </h2>
                </div>
                <div class="inside">
                    <?php if (empty($votos_por_categoria)): ?>
                        <p style="color: #646970; text-align: center;">Sin datos</p>
                    <?php else: ?>
                        <?php
                        $colores_categorias = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#6b7280'];
                        $i = 0;
                        foreach ($votos_por_categoria as $cat):
                            $color = $colores_categorias[$i % count($colores_categorias)];
                        ?>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span><strong><?php echo esc_html(ucfirst($cat->categoria ?: 'Sin categoría')); ?></strong></span>
                                    <span><strong><?php echo intval($cat->total_votos); ?></strong> votos</span>
                                </div>
                                <div style="background: #e0e0e0; height: 10px; border-radius: 5px;">
                                    <div style="background: <?php echo $color; ?>; height: 100%; width: <?php echo $total_votos > 0 ? min(($cat->total_votos / $total_votos) * 100, 100) : 0; ?>%; border-radius: 5px;"></div>
                                </div>
                                <small style="color: #646970;"><?php echo $cat->proyectos; ?> proyectos</small>
                            </div>
                        <?php
                            $i++;
                        endforeach;
                        ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Evolución Diaria -->
            <div class="postbox" style="margin: 0 0 20px 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;">
                        <span class="dashicons dashicons-chart-area"></span>
                        Evolución (14 días)
                    </h2>
                </div>
                <div class="inside">
                    <?php if (empty($votos_por_dia)): ?>
                        <p style="color: #646970; text-align: center;">Sin actividad reciente</p>
                    <?php else: ?>
                        <div style="display: flex; align-items: flex-end; gap: 4px; height: 100px;">
                            <?php
                            $max_dia = max(array_column($votos_por_dia, 'total'));
                            foreach ($votos_por_dia as $dia):
                                $altura = $max_dia > 0 ? ($dia->total / $max_dia) * 100 : 0;
                            ?>
                                <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                    <span style="font-size: 10px; color: #646970;"><?php echo $dia->total; ?></span>
                                    <div style="width: 100%; background: #2271b1; height: <?php echo max($altura, 5); ?>%; border-radius: 2px 2px 0 0;" title="<?php echo date_i18n('d/m', strtotime($dia->dia)); ?>: <?php echo $dia->total; ?> votos"></div>
                                    <span style="font-size: 9px; color: #646970; margin-top: 2px;"><?php echo date_i18n('d', strtotime($dia->dia)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Votantes Activos -->
            <div class="postbox" style="margin: 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;">
                        <span class="dashicons dashicons-groups"></span>
                        Top Votantes
                    </h2>
                </div>
                <div class="inside" style="padding: 0;">
                    <?php if (empty($votantes_activos)): ?>
                        <p style="padding: 15px; color: #646970; text-align: center;">Sin votantes aún</p>
                    <?php else: ?>
                        <ul style="margin: 0; padding: 0; list-style: none;">
                            <?php foreach ($votantes_activos as $votante): ?>
                                <li style="padding: 10px 15px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?php echo esc_html($votante->display_name ?: 'Usuario #' . $votante->usuario_id); ?></strong>
                                        <br><small style="color: #646970;"><?php echo esc_html($votante->user_email); ?></small>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="background: #2271b1; color: white; padding: 3px 10px; border-radius: 10px; font-size: 12px;">
                                            <?php echo $votante->total_votos; ?>/<?php echo $max_votos; ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos Votos -->
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header">
            <h2 style="padding: 10px 15px; margin: 0;">
                <span class="dashicons dashicons-clock"></span>
                Últimos Votos Emitidos
            </h2>
        </div>
        <div class="inside" style="padding: 0;">
            <?php if (empty($ultimos_votos)): ?>
                <p style="padding: 20px; text-align: center; color: #646970;">No hay votos registrados aún.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Fecha/Hora</th>
                            <th style="width: 25%;">Votante</th>
                            <th style="width: 35%;">Proyecto</th>
                            <th style="width: 10%;">Prioridad</th>
                            <th style="width: 10%;">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_votos as $voto): ?>
                            <tr>
                                <td>
                                    <span class="dashicons dashicons-clock" style="color: #646970;"></span>
                                    <?php echo date_i18n('d/m/Y H:i', strtotime($voto->fecha_voto)); ?>
                                </td>
                                <td>
                                    <?php if ($voto->usuario_nombre): ?>
                                        <a href="<?php echo get_edit_user_link($voto->usuario_id); ?>">
                                            <?php echo esc_html($voto->usuario_nombre); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #646970;">Usuario #<?php echo $voto->usuario_id; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($voto->proyecto_titulo ?: 'Proyecto #' . $voto->proyecto_id); ?></strong>
                                </td>
                                <td>
                                    <?php if ($voto->prioridad): ?>
                                        <span style="background: #f0f0f1; padding: 2px 8px; border-radius: 3px;">
                                            #<?php echo $voto->prioridad; ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small style="color: #646970;"><?php echo esc_html($voto->ip_address ?: '-'); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>
</div>
