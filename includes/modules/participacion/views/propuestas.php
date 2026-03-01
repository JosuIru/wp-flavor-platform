<?php
/**
 * Vista Gestión de Propuestas - Módulo Participación Ciudadana (Admin)
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

$tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
$tabla_votos = $wpdb->prefix . 'flavor_votos';

// Procesar acciones
if (isset($_POST['accion_propuesta']) && wp_verify_nonce($_POST['_wpnonce'], 'participacion_propuesta_action')) {
    $accion = sanitize_text_field($_POST['accion_propuesta']);
    $propuesta_id = intval($_POST['propuesta_id'] ?? 0);

    if ($propuesta_id > 0) {
        switch ($accion) {
            case 'aprobar':
                $wpdb->update($tabla_propuestas, ['estado' => 'aprobada'], ['id' => $propuesta_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Propuesta aprobada.</p></div>';
                break;
            case 'rechazar':
                $motivo = sanitize_textarea_field($_POST['motivo_rechazo'] ?? '');
                $wpdb->update($tabla_propuestas, ['estado' => 'rechazada', 'motivo_rechazo' => $motivo], ['id' => $propuesta_id]);
                echo '<div class="notice notice-warning is-dismissible"><p>Propuesta rechazada.</p></div>';
                break;
            case 'votacion':
                $wpdb->update($tabla_propuestas, ['estado' => 'en_votacion', 'fecha_inicio_votacion' => current_time('mysql')], ['id' => $propuesta_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Propuesta pasada a votación.</p></div>';
                break;
            case 'implementar':
                $wpdb->update($tabla_propuestas, ['estado' => 'implementada', 'fecha_implementacion' => current_time('mysql')], ['id' => $propuesta_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Propuesta marcada como implementada.</p></div>';
                break;
            case 'eliminar':
                $wpdb->delete($tabla_propuestas, ['id' => $propuesta_id]);
                $wpdb->delete($tabla_votos, ['propuesta_id' => $propuesta_id]);
                echo '<div class="notice notice-warning is-dismissible"><p>Propuesta eliminada.</p></div>';
                break;
        }
    }
}

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$categoria_filtro = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$busqueda = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';

// Construir query
$where = ['1=1'];
$params = [];

if ($estado_filtro) {
    $where[] = 'p.estado = %s';
    $params[] = $estado_filtro;
}
if ($categoria_filtro) {
    $where[] = 'p.categoria = %s';
    $params[] = $categoria_filtro;
}
if ($busqueda) {
    $where[] = '(p.titulo LIKE %s OR p.descripcion LIKE %s)';
    $like = '%' . $wpdb->esc_like($busqueda) . '%';
    $params[] = $like;
    $params[] = $like;
}

$where_sql = implode(' AND ', $where);
$sql = "SELECT p.*, u.display_name as autor_nombre,
               (SELECT COUNT(*) FROM {$tabla_votos} v WHERE v.propuesta_id = p.id AND v.tipo_voto = 'favor') as votos_favor_calc,
               (SELECT COUNT(*) FROM {$tabla_votos} v WHERE v.propuesta_id = p.id AND v.tipo_voto = 'contra') as votos_contra_calc
        FROM {$tabla_propuestas} p
        LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        WHERE {$where_sql}
        ORDER BY p.fecha_creacion DESC";

$propuestas = !empty($params)
    ? $wpdb->get_results($wpdb->prepare($sql, ...$params))
    : $wpdb->get_results($sql);

// Estadísticas por estado
$stats_estado = $wpdb->get_results("SELECT estado, COUNT(*) as total FROM {$tabla_propuestas} GROUP BY estado", OBJECT_K);

// Categorías únicas
$categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM {$tabla_propuestas} WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");

$estados = [
    'borrador' => ['label' => 'Borrador', 'color' => '#646970'],
    'pendiente' => ['label' => 'Pendiente', 'color' => '#dba617'],
    'en_revision' => ['label' => 'En Revisión', 'color' => '#72aee6'],
    'aprobada' => ['label' => 'Aprobada', 'color' => '#00a32a'],
    'rechazada' => ['label' => 'Rechazada', 'color' => '#d63638'],
    'en_votacion' => ['label' => 'En Votación', 'color' => '#2271b1'],
    'aceptada' => ['label' => 'Aceptada', 'color' => '#8c5ae8'],
    'implementada' => ['label' => 'Implementada', 'color' => '#1d2327'],
    'archivada' => ['label' => 'Archivada', 'color' => '#50575e'],
];

$page_url = admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? '') . '&vista=propuestas');
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-lightbulb"></span> Gestión de Propuestas</h1>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="postbox" style="margin-top: 20px; padding: 15px;">
        <form method="get" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
            <input type="hidden" name="vista" value="propuestas">

            <div>
                <label><strong>Estado:</strong></label>
                <select name="estado" style="min-width: 150px;">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $key => $info): ?>
                        <option value="<?php echo $key; ?>" <?php selected($estado_filtro, $key); ?>>
                            <?php echo $info['label']; ?> (<?php echo intval($stats_estado[$key]->total ?? 0); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label><strong>Categoría:</strong></label>
                <select name="categoria" style="min-width: 150px;">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo esc_attr($cat); ?>" <?php selected($categoria_filtro, $cat); ?>>
                            <?php echo esc_html(ucfirst($cat)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label><strong>Buscar:</strong></label>
                <input type="text" name="buscar" value="<?php echo esc_attr($busqueda); ?>" placeholder="Título o descripción...">
            </div>

            <button type="submit" class="button"><span class="dashicons dashicons-search" style="vertical-align: middle;"></span> Filtrar</button>
            <?php if ($estado_filtro || $categoria_filtro || $busqueda): ?>
                <a href="<?php echo esc_url($page_url); ?>" class="button">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Resumen por Estado -->
    <div style="display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap;">
        <?php foreach ($estados as $key => $info):
            $cantidad = intval($stats_estado[$key]->total ?? 0);
            if ($cantidad === 0) continue;
        ?>
            <a href="<?php echo esc_url($page_url . '&estado=' . $key); ?>"
               style="display: flex; align-items: center; gap: 8px; padding: 8px 15px; background: white; border: 2px solid <?php echo $info['color']; ?>; border-radius: 20px; text-decoration: none; color: <?php echo $info['color']; ?>; <?php echo $estado_filtro === $key ? 'background: ' . $info['color'] . '; color: white;' : ''; ?>">
                <?php echo $info['label']; ?> <strong>(<?php echo $cantidad; ?>)</strong>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Tabla de Propuestas -->
    <div class="postbox" style="margin: 0;">
        <div class="inside" style="padding: 0;">
            <?php if (empty($propuestas)): ?>
                <p style="padding: 30px; text-align: center; color: #646970;">
                    <span class="dashicons dashicons-lightbulb" style="font-size: 48px; display: block; margin-bottom: 15px;"></span>
                    No se encontraron propuestas.
                </p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 30%;">Propuesta</th>
                            <th style="width: 15%;">Autor</th>
                            <th style="width: 12%;">Votos</th>
                            <th style="width: 12%;">Estado</th>
                            <th style="width: 10%;">Fecha</th>
                            <th style="width: 16%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($propuestas as $p):
                            $votos_favor = $p->votos_favor_calc ?: $p->votos_favor;
                            $votos_contra = $p->votos_contra_calc ?: $p->votos_contra;
                            $total_votos = $votos_favor + $votos_contra + $p->votos_abstencion;
                        ?>
                            <tr>
                                <td><strong>#<?php echo $p->id; ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html($p->titulo); ?></strong>
                                    <?php if ($p->categoria): ?>
                                        <br><small style="color: #646970;"><?php echo esc_html(ucfirst($p->categoria)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($p->autor_nombre ?: $p->usuario_nombre ?: 'Usuario #' . $p->usuario_id); ?>
                                </td>
                                <td>
                                    <?php if ($total_votos > 0): ?>
                                        <span style="color: #00a32a;">+<?php echo $votos_favor; ?></span> /
                                        <span style="color: #d63638;">-<?php echo $votos_contra; ?></span>
                                        <br><small style="color: #646970;"><?php echo $total_votos; ?> total</small>
                                    <?php else: ?>
                                        <span style="color: #646970;">Sin votos</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $estado_info = $estados[$p->estado] ?? ['label' => $p->estado, 'color' => '#646970']; ?>
                                    <span style="background: <?php echo $estado_info['color']; ?>; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                        <?php echo $estado_info['label']; ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date_i18n('d/m/Y', strtotime($p->fecha_creacion)); ?></small>
                                </td>
                                <td>
                                    <form method="post" style="display: inline-flex; gap: 3px; flex-wrap: wrap;">
                                        <?php wp_nonce_field('participacion_propuesta_action'); ?>
                                        <input type="hidden" name="propuesta_id" value="<?php echo $p->id; ?>">

                                        <?php if (in_array($p->estado, ['pendiente', 'en_revision'])): ?>
                                            <button type="submit" name="accion_propuesta" value="aprobar" class="button button-small button-primary" title="Aprobar">
                                                <span class="dashicons dashicons-yes"></span>
                                            </button>
                                            <button type="submit" name="accion_propuesta" value="rechazar" class="button button-small" style="color: #d63638;" title="Rechazar">
                                                <span class="dashicons dashicons-no"></span>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($p->estado === 'aprobada'): ?>
                                            <button type="submit" name="accion_propuesta" value="votacion" class="button button-small button-primary" title="Pasar a votación">
                                                <span class="dashicons dashicons-megaphone"></span>
                                            </button>
                                        <?php endif; ?>

                                        <?php if (in_array($p->estado, ['aceptada', 'en_votacion'])): ?>
                                            <button type="submit" name="accion_propuesta" value="implementar" class="button button-small" style="color: #00a32a;" title="Marcar implementada">
                                                <span class="dashicons dashicons-flag"></span>
                                            </button>
                                        <?php endif; ?>

                                        <button type="submit" name="accion_propuesta" value="eliminar" class="button button-small" style="color: #d63638;" onclick="return confirm('¿Eliminar esta propuesta?');" title="Eliminar">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="padding: 15px; background: #f0f0f1;"><strong>Total:</strong> <?php echo count($propuestas); ?> propuestas</div>
            <?php endif; ?>
        </div>
    </div>
</div>
