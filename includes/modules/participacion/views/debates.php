<?php
/**
 * Vista Moderación de Debates - Módulo Participación Ciudadana (Admin)
 *
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

$tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
$tabla_comentarios = $wpdb->prefix . 'flavor_comentarios_propuesta';

// Procesar acciones de moderación
if (isset($_POST['accion_moderacion']) && wp_verify_nonce($_POST['_wpnonce'], 'participacion_moderacion_action')) {
    $accion = sanitize_text_field($_POST['accion_moderacion']);
    $comentario_id = intval($_POST['comentario_id'] ?? 0);

    if ($comentario_id > 0) {
        switch ($accion) {
            case 'aprobar':
                $wpdb->update($tabla_comentarios, ['estado' => 'publicado'], ['id' => $comentario_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Comentario aprobado.</p></div>';
                break;
            case 'ocultar':
                $wpdb->update($tabla_comentarios, ['estado' => 'oculto'], ['id' => $comentario_id]);
                echo '<div class="notice notice-warning is-dismissible"><p>Comentario ocultado.</p></div>';
                break;
            case 'eliminar':
                $wpdb->delete($tabla_comentarios, ['id' => $comentario_id]);
                echo '<div class="notice notice-warning is-dismissible"><p>Comentario eliminado.</p></div>';
                break;
            case 'marcar_oficial':
                $wpdb->update($tabla_comentarios, ['es_oficial' => 1, 'tipo' => 'respuesta_oficial'], ['id' => $comentario_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Marcado como respuesta oficial.</p></div>';
                break;
        }
    }
}

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$propuesta_filtro = isset($_GET['propuesta_id']) ? intval($_GET['propuesta_id']) : 0;

// Construir query
$where = ['1=1'];
$params = [];

if ($estado_filtro) {
    $where[] = 'c.estado = %s';
    $params[] = $estado_filtro;
}
if ($tipo_filtro) {
    $where[] = 'c.tipo = %s';
    $params[] = $tipo_filtro;
}
if ($propuesta_filtro) {
    $where[] = 'c.propuesta_id = %d';
    $params[] = $propuesta_filtro;
}

$where_sql = implode(' AND ', $where);
$sql = "SELECT c.*, p.titulo as propuesta_titulo, u.display_name as autor_nombre
        FROM {$tabla_comentarios} c
        LEFT JOIN {$tabla_propuestas} p ON c.propuesta_id = p.id
        LEFT JOIN {$wpdb->users} u ON c.usuario_id = u.ID
        WHERE {$where_sql}
        ORDER BY c.fecha_creacion DESC
        LIMIT 100";

$comentarios = !empty($params)
    ? $wpdb->get_results($wpdb->prepare($sql, ...$params))
    : $wpdb->get_results($sql);

// Estadísticas
$stats = $wpdb->get_row(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'publicado' THEN 1 ELSE 0 END) as publicados,
        SUM(CASE WHEN estado = 'oculto' THEN 1 ELSE 0 END) as ocultos,
        SUM(CASE WHEN tipo = 'respuesta_oficial' THEN 1 ELSE 0 END) as oficiales,
        SUM(CASE WHEN tipo = 'pregunta' THEN 1 ELSE 0 END) as preguntas
     FROM {$tabla_comentarios}"
);

// Propuestas con más debates
$propuestas_activas = $wpdb->get_results(
    "SELECT p.id, p.titulo, COUNT(c.id) as total_comentarios
     FROM {$tabla_propuestas} p
     LEFT JOIN {$tabla_comentarios} c ON c.propuesta_id = p.id
     GROUP BY p.id
     HAVING total_comentarios > 0
     ORDER BY total_comentarios DESC
     LIMIT 10"
);

$page_url = admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? '') . '&vista=debates');
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-format-chat"></span> Moderación de Debates</h1>
    <hr class="wp-header-end">

    <!-- KPIs -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="postbox" style="margin: 0; padding: 15px; text-align: center;">
            <h3 style="margin: 0; font-size: 28px; color: #2271b1;"><?php echo intval($stats->total ?? 0); ?></h3>
            <p style="margin: 5px 0 0; color: #646970;">Total Comentarios</p>
        </div>
        <div class="postbox" style="margin: 0; padding: 15px; text-align: center;">
            <h3 style="margin: 0; font-size: 28px; color: #00a32a;"><?php echo intval($stats->publicados ?? 0); ?></h3>
            <p style="margin: 5px 0 0; color: #646970;">Publicados</p>
        </div>
        <div class="postbox" style="margin: 0; padding: 15px; text-align: center;">
            <h3 style="margin: 0; font-size: 28px; color: #dba617;"><?php echo intval($stats->ocultos ?? 0); ?></h3>
            <p style="margin: 5px 0 0; color: #646970;">Ocultos</p>
        </div>
        <div class="postbox" style="margin: 0; padding: 15px; text-align: center;">
            <h3 style="margin: 0; font-size: 28px; color: #8c5ae8;"><?php echo intval($stats->oficiales ?? 0); ?></h3>
            <p style="margin: 5px 0 0; color: #646970;">Resp. Oficiales</p>
        </div>
        <div class="postbox" style="margin: 0; padding: 15px; text-align: center;">
            <h3 style="margin: 0; font-size: 28px; color: #d63638;"><?php echo intval($stats->preguntas ?? 0); ?></h3>
            <p style="margin: 5px 0 0; color: #646970;">Preguntas</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="postbox" style="margin: 0 0 20px 0; padding: 15px;">
        <form method="get" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
            <input type="hidden" name="vista" value="debates">

            <div>
                <label><strong>Estado:</strong></label>
                <select name="estado">
                    <option value="">Todos</option>
                    <option value="publicado" <?php selected($estado_filtro, 'publicado'); ?>>Publicados</option>
                    <option value="oculto" <?php selected($estado_filtro, 'oculto'); ?>>Ocultos</option>
                    <option value="eliminado" <?php selected($estado_filtro, 'eliminado'); ?>>Eliminados</option>
                </select>
            </div>

            <div>
                <label><strong>Tipo:</strong></label>
                <select name="tipo">
                    <option value="">Todos</option>
                    <option value="comentario" <?php selected($tipo_filtro, 'comentario'); ?>>Comentarios</option>
                    <option value="pregunta" <?php selected($tipo_filtro, 'pregunta'); ?>>Preguntas</option>
                    <option value="respuesta_oficial" <?php selected($tipo_filtro, 'respuesta_oficial'); ?>>Resp. Oficiales</option>
                </select>
            </div>

            <div>
                <label><strong>Propuesta:</strong></label>
                <select name="propuesta_id">
                    <option value="">Todas</option>
                    <?php foreach ($propuestas_activas as $p): ?>
                        <option value="<?php echo $p->id; ?>" <?php selected($propuesta_filtro, $p->id); ?>>
                            <?php echo esc_html(substr($p->titulo, 0, 40)); ?>... (<?php echo $p->total_comentarios; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="button"><span class="dashicons dashicons-filter" style="vertical-align: middle;"></span> Filtrar</button>
            <?php if ($estado_filtro || $tipo_filtro || $propuesta_filtro): ?>
                <a href="<?php echo esc_url($page_url); ?>" class="button">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <!-- Lista de Comentarios -->
        <div class="postbox" style="margin: 0;">
            <div class="postbox-header">
                <h2 style="padding: 10px 15px; margin: 0;">Comentarios (<?php echo count($comentarios); ?>)</h2>
            </div>
            <div class="inside" style="padding: 0; max-height: 600px; overflow-y: auto;">
                <?php if (empty($comentarios)): ?>
                    <p style="padding: 30px; text-align: center; color: #646970;">No hay comentarios que mostrar.</p>
                <?php else: ?>
                    <?php foreach ($comentarios as $c):
                        $estado_color = $c->estado === 'publicado' ? '#00a32a' : ($c->estado === 'oculto' ? '#dba617' : '#d63638');
                        $tipo_icons = ['comentario' => 'dashicons-format-chat', 'pregunta' => 'dashicons-editor-help', 'respuesta_oficial' => 'dashicons-businessman', 'moderacion' => 'dashicons-shield'];
                    ?>
                        <div style="padding: 15px; border-bottom: 1px solid #e0e0e0; <?php echo $c->es_oficial ? 'background: #f0f6fc;' : ''; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <div>
                                    <span class="dashicons <?php echo $tipo_icons[$c->tipo] ?? 'dashicons-format-chat'; ?>" style="color: #646970;"></span>
                                    <strong><?php echo esc_html($c->autor_nombre ?: $c->usuario_nombre ?: 'Usuario #' . $c->usuario_id); ?></strong>
                                    <?php if ($c->es_oficial): ?>
                                        <span style="background: #8c5ae8; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;">OFICIAL</span>
                                    <?php endif; ?>
                                    <br>
                                    <small style="color: #646970;">
                                        en <a href="<?php echo esc_url($page_url . '&propuesta_id=' . $c->propuesta_id); ?>"><?php echo esc_html($c->propuesta_titulo ?: 'Propuesta #' . $c->propuesta_id); ?></a>
                                        · <?php echo human_time_diff(strtotime($c->fecha_creacion), current_time('timestamp')); ?> atrás
                                    </small>
                                </div>
                                <span style="background: <?php echo $estado_color; ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 10px;">
                                    <?php echo ucfirst($c->estado); ?>
                                </span>
                            </div>

                            <p style="margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 4px; font-size: 13px;">
                                <?php echo nl2br(esc_html(substr($c->contenido, 0, 300))); ?>
                                <?php if (strlen($c->contenido) > 300): ?>...<?php endif; ?>
                            </p>

                            <div style="display: flex; gap: 5px; align-items: center;">
                                <form method="post" style="display: inline-flex; gap: 5px;">
                                    <?php wp_nonce_field('participacion_moderacion_action'); ?>
                                    <input type="hidden" name="comentario_id" value="<?php echo $c->id; ?>">

                                    <?php if ($c->estado !== 'publicado'): ?>
                                        <button type="submit" name="accion_moderacion" value="aprobar" class="button button-small button-primary" title="Aprobar">
                                            <span class="dashicons dashicons-yes"></span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($c->estado !== 'oculto'): ?>
                                        <button type="submit" name="accion_moderacion" value="ocultar" class="button button-small" title="Ocultar">
                                            <span class="dashicons dashicons-hidden"></span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if (!$c->es_oficial): ?>
                                        <button type="submit" name="accion_moderacion" value="marcar_oficial" class="button button-small" style="color: #8c5ae8;" title="Marcar como oficial">
                                            <span class="dashicons dashicons-star-filled"></span>
                                        </button>
                                    <?php endif; ?>

                                    <button type="submit" name="accion_moderacion" value="eliminar" class="button button-small" style="color: #d63638;" onclick="return confirm('¿Eliminar este comentario?');" title="Eliminar">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </form>

                                <?php if ($c->likes_count > 0): ?>
                                    <span style="margin-left: 10px; color: #646970; font-size: 12px;">
                                        <span class="dashicons dashicons-heart" style="font-size: 14px; color: #d63638;"></span>
                                        <?php echo $c->likes_count; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel lateral -->
        <div>
            <!-- Propuestas más debatidas -->
            <div class="postbox" style="margin: 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;"><span class="dashicons dashicons-chart-bar"></span> Más Debatidas</h2>
                </div>
                <div class="inside" style="padding: 0;">
                    <?php if (empty($propuestas_activas)): ?>
                        <p style="padding: 15px; text-align: center; color: #646970;">Sin debates aún</p>
                    <?php else: ?>
                        <ul style="margin: 0; padding: 0; list-style: none;">
                            <?php foreach ($propuestas_activas as $p): ?>
                                <li style="padding: 10px 15px; border-bottom: 1px solid #e0e0e0;">
                                    <a href="<?php echo esc_url($page_url . '&propuesta_id=' . $p->id); ?>" style="text-decoration: none; color: inherit;">
                                        <strong><?php echo esc_html(substr($p->titulo, 0, 35)); ?><?php echo strlen($p->titulo) > 35 ? '...' : ''; ?></strong>
                                        <br>
                                        <small style="color: #646970;">
                                            <span class="dashicons dashicons-format-chat" style="font-size: 14px;"></span>
                                            <?php echo $p->total_comentarios; ?> comentarios
                                        </small>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
