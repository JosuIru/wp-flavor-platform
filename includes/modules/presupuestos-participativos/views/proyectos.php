<?php
/**
 * Vista Gestión de Proyectos - Módulo Presupuestos Participativos (Admin)
 *
 * @package FlavorChatIA
 * @subpackage Modules\PresupuestosParticipativos
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

// Tablas
$tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
$tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
$tabla_votos = $wpdb->prefix . 'flavor_pp_votos';

// Procesar acciones
if (isset($_POST['accion_proyecto']) && wp_verify_nonce($_POST['_wpnonce'], 'pp_proyecto_action')) {
    $accion = sanitize_text_field($_POST['accion_proyecto']);
    $proyecto_id = intval($_POST['proyecto_id'] ?? 0);

    if ($proyecto_id > 0) {
        switch ($accion) {
            case 'validar':
                $wpdb->update($tabla_proyectos, ['estado' => 'validado'], ['id' => $proyecto_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Proyecto validado correctamente.</p></div>';
                break;
            case 'rechazar':
                $wpdb->update($tabla_proyectos, ['estado' => 'rechazado'], ['id' => $proyecto_id]);
                echo '<div class="notice notice-warning is-dismissible"><p>Proyecto rechazado.</p></div>';
                break;
            case 'seleccionar':
                $wpdb->update($tabla_proyectos, ['estado' => 'seleccionado'], ['id' => $proyecto_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Proyecto seleccionado para financiación.</p></div>';
                break;
            case 'pasar_votacion':
                $wpdb->update($tabla_proyectos, ['estado' => 'en_votacion'], ['id' => $proyecto_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Proyecto pasado a fase de votación.</p></div>';
                break;
            case 'eliminar':
                $wpdb->delete($tabla_proyectos, ['id' => $proyecto_id]);
                $wpdb->delete($tabla_votos, ['proyecto_id' => $proyecto_id]);
                echo '<div class="notice notice-warning is-dismissible"><p>Proyecto eliminado.</p></div>';
                break;
        }
    }
}

// Filtros
$edicion_id = isset($_GET['edicion_id']) ? intval($_GET['edicion_id']) : 0;
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$busqueda = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';

// Obtener ediciones disponibles
$ediciones = $wpdb->get_results("SELECT id, nombre, anio, fase, estado FROM {$tabla_ediciones} ORDER BY anio DESC, id DESC");

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

// Construir query de proyectos
$where_clauses = ["p.edicion_id = %d"];
$where_values = [$edicion_id];

if ($estado_filtro) {
    $where_clauses[] = "p.estado = %s";
    $where_values[] = $estado_filtro;
}

if ($busqueda) {
    $where_clauses[] = "(p.titulo LIKE %s OR p.descripcion LIKE %s)";
    $like_busqueda = '%' . $wpdb->esc_like($busqueda) . '%';
    $where_values[] = $like_busqueda;
    $where_values[] = $like_busqueda;
}

$where_sql = implode(' AND ', $where_clauses);

$proyectos = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*,
            u.display_name as proponente_nombre,
            u.user_email as proponente_email,
            (SELECT COUNT(*) FROM {$tabla_votos} v WHERE v.proyecto_id = p.id) as total_votos
     FROM {$tabla_proyectos} p
     LEFT JOIN {$wpdb->users} u ON p.proponente_id = u.ID
     WHERE {$where_sql}
     ORDER BY p.votos_recibidos DESC, p.fecha_creacion DESC",
    ...$where_values
));

// Estadísticas por estado
$stats_por_estado = $wpdb->get_results($wpdb->prepare(
    "SELECT estado, COUNT(*) as total FROM {$tabla_proyectos} WHERE edicion_id = %d GROUP BY estado",
    $edicion_id
), OBJECT_K);

$estados_disponibles = [
    'borrador' => ['label' => 'Borrador', 'color' => '#646970', 'icon' => 'dashicons-edit'],
    'pendiente_validacion' => ['label' => 'Pendiente', 'color' => '#dba617', 'icon' => 'dashicons-clock'],
    'validado' => ['label' => 'Validado', 'color' => '#72aee6', 'icon' => 'dashicons-yes'],
    'en_votacion' => ['label' => 'En Votación', 'color' => '#2271b1', 'icon' => 'dashicons-megaphone'],
    'seleccionado' => ['label' => 'Seleccionado', 'color' => '#00a32a', 'icon' => 'dashicons-awards'],
    'en_ejecucion' => ['label' => 'En Ejecución', 'color' => '#8c5ae8', 'icon' => 'dashicons-hammer'],
    'ejecutado' => ['label' => 'Completado', 'color' => '#1d2327', 'icon' => 'dashicons-flag'],
    'rechazado' => ['label' => 'Rechazado', 'color' => '#d63638', 'icon' => 'dashicons-no'],
];

$page_url = admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? '') . '&vista=proyectos');
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-portfolio"></span> Gestión de Proyectos</h1>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="postbox" style="margin-top: 20px; padding: 15px;">
        <form method="get" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
            <input type="hidden" name="vista" value="proyectos">

            <div>
                <label for="edicion_id"><strong>Edición:</strong></label>
                <select name="edicion_id" id="edicion_id" onchange="this.form.submit()" style="min-width: 200px;">
                    <?php foreach ($ediciones as $edicion): ?>
                        <option value="<?php echo $edicion->id; ?>" <?php selected($edicion_id, $edicion->id); ?>>
                            <?php echo esc_html($edicion->nombre . ' (' . $edicion->anio . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="estado"><strong>Estado:</strong></label>
                <select name="estado" id="estado" style="min-width: 150px;">
                    <option value="">Todos</option>
                    <?php foreach ($estados_disponibles as $estado_key => $estado_info): ?>
                        <option value="<?php echo esc_attr($estado_key); ?>" <?php selected($estado_filtro, $estado_key); ?>>
                            <?php echo esc_html($estado_info['label']); ?>
                            (<?php echo intval($stats_por_estado[$estado_key]->total ?? 0); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="buscar"><strong>Buscar:</strong></label>
                <input type="text" name="buscar" id="buscar" value="<?php echo esc_attr($busqueda); ?>" placeholder="Título o descripción..." style="min-width: 200px;">
            </div>

            <button type="submit" class="button">
                <span class="dashicons dashicons-search" style="vertical-align: middle;"></span> Filtrar
            </button>

            <?php if ($estado_filtro || $busqueda): ?>
                <a href="<?php echo esc_url($page_url . '&edicion_id=' . $edicion_id); ?>" class="button">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Resumen por Estado -->
    <div style="display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap;">
        <?php foreach ($estados_disponibles as $estado_key => $estado_info):
            $cantidad = intval($stats_por_estado[$estado_key]->total ?? 0);
            if ($cantidad === 0) continue;
        ?>
            <a href="<?php echo esc_url($page_url . '&edicion_id=' . $edicion_id . '&estado=' . $estado_key); ?>"
               style="display: flex; align-items: center; gap: 8px; padding: 8px 15px; background: white; border: 2px solid <?php echo $estado_info['color']; ?>; border-radius: 20px; text-decoration: none; color: <?php echo $estado_info['color']; ?>; <?php echo $estado_filtro === $estado_key ? 'background: ' . $estado_info['color'] . '; color: white;' : ''; ?>">
                <span class="dashicons <?php echo $estado_info['icon']; ?>"></span>
                <span><?php echo $estado_info['label']; ?></span>
                <strong>(<?php echo $cantidad; ?>)</strong>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Tabla de Proyectos -->
    <div class="postbox" style="margin: 0;">
        <div class="inside" style="padding: 0;">
            <?php if (empty($proyectos)): ?>
                <p style="padding: 30px; text-align: center; color: #646970;">
                    <span class="dashicons dashicons-portfolio" style="font-size: 48px; display: block; margin-bottom: 15px;"></span>
                    No se encontraron proyectos con los filtros seleccionados.
                </p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 25%;">Proyecto</th>
                            <th style="width: 15%;">Proponente</th>
                            <th style="width: 12%;">Presupuesto</th>
                            <th style="width: 8%;">Votos</th>
                            <th style="width: 12%;">Estado</th>
                            <th style="width: 23%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proyectos as $proyecto): ?>
                            <tr>
                                <td><strong>#<?php echo $proyecto->id; ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html($proyecto->titulo); ?></strong>
                                    <br><small style="color: #646970;">
                                        <?php echo esc_html(ucfirst($proyecto->categoria)); ?> ·
                                        <?php echo date_i18n('d/m/Y', strtotime($proyecto->fecha_creacion)); ?>
                                    </small>
                                    <?php if ($proyecto->ubicacion): ?>
                                        <br><small><span class="dashicons dashicons-location" style="font-size: 14px;"></span> <?php echo esc_html(substr($proyecto->ubicacion, 0, 40)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($proyecto->proponente_nombre): ?>
                                        <a href="<?php echo get_edit_user_link($proyecto->proponente_id); ?>">
                                            <?php echo esc_html($proyecto->proponente_nombre); ?>
                                        </a>
                                        <br><small style="color: #646970;"><?php echo esc_html($proyecto->proponente_email); ?></small>
                                    <?php else: ?>
                                        <span style="color: #646970;">Usuario #<?php echo $proyecto->proponente_id; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format($proyecto->presupuesto_solicitado, 0, ',', '.'); ?> €</strong>
                                    <?php if ($proyecto->presupuesto_aprobado): ?>
                                        <br><small style="color: #00a32a;">Aprobado: <?php echo number_format($proyecto->presupuesto_aprobado, 0, ',', '.'); ?> €</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 18px; font-weight: bold; color: #2271b1;">
                                        <span class="dashicons dashicons-thumbs-up" style="vertical-align: middle;"></span>
                                        <?php echo intval($proyecto->votos_recibidos ?: $proyecto->total_votos); ?>
                                    </span>
                                    <?php if ($proyecto->ranking > 0): ?>
                                        <br><small>#<?php echo $proyecto->ranking; ?> ranking</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $estado_info = $estados_disponibles[$proyecto->estado] ?? ['label' => $proyecto->estado, 'color' => '#646970']; ?>
                                    <span style="background: <?php echo $estado_info['color']; ?>; color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; white-space: nowrap;">
                                        <?php echo esc_html($estado_info['label']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" style="display: inline-flex; gap: 5px; flex-wrap: wrap;">
                                        <?php wp_nonce_field('pp_proyecto_action'); ?>
                                        <input type="hidden" name="proyecto_id" value="<?php echo $proyecto->id; ?>">

                                        <?php if ($proyecto->estado === 'pendiente_validacion'): ?>
                                            <button type="submit" name="accion_proyecto" value="validar" class="button button-primary button-small" title="Validar">
                                                <span class="dashicons dashicons-yes"></span>
                                            </button>
                                            <button type="submit" name="accion_proyecto" value="rechazar" class="button button-small" style="color: #d63638;" title="Rechazar">
                                                <span class="dashicons dashicons-no"></span>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($proyecto->estado === 'validado'): ?>
                                            <button type="submit" name="accion_proyecto" value="pasar_votacion" class="button button-primary button-small" title="Pasar a Votación">
                                                <span class="dashicons dashicons-megaphone"></span>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($proyecto->estado === 'en_votacion'): ?>
                                            <button type="submit" name="accion_proyecto" value="seleccionar" class="button button-primary button-small" title="Seleccionar">
                                                <span class="dashicons dashicons-awards"></span>
                                            </button>
                                        <?php endif; ?>

                                        <button type="button" class="button button-small" onclick="verDetalleProyecto(<?php echo $proyecto->id; ?>)" title="Ver Detalle">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>

                                        <button type="submit" name="accion_proyecto" value="eliminar" class="button button-small" style="color: #d63638;" title="Eliminar" onclick="return confirm('¿Eliminar este proyecto permanentemente?');">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="padding: 15px; background: #f0f0f1; border-top: 1px solid #c3c4c7;">
                    <strong>Total:</strong> <?php echo count($proyectos); ?> proyectos
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Detalle Proyecto -->
<div id="modal-detalle" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 100000; justify-content: center; align-items: center; overflow: auto;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 700px; width: 90%; max-height: 90vh; overflow: auto; margin: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;" id="modal-titulo"></h2>
            <button type="button" onclick="cerrarModalDetalle()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div id="modal-contenido"></div>
    </div>
</div>

<script>
function verDetalleProyecto(id) {
    // Buscar datos del proyecto en la tabla
    const row = document.querySelector(`input[name="proyecto_id"][value="${id}"]`).closest('tr');
    const titulo = row.cells[1].querySelector('strong').textContent;

    document.getElementById('modal-titulo').textContent = titulo;
    document.getElementById('modal-contenido').innerHTML = '<p>Cargando detalles...</p>';
    document.getElementById('modal-detalle').style.display = 'flex';

    // En producción, esto cargaría via AJAX los detalles completos
    const contenido = `
        <table class="form-table">
            <tr><th>ID:</th><td>${id}</td></tr>
            <tr><th>Título:</th><td>${titulo}</td></tr>
            <tr><th>Proponente:</th><td>${row.cells[2].innerHTML}</td></tr>
            <tr><th>Presupuesto:</th><td>${row.cells[3].innerHTML}</td></tr>
            <tr><th>Votos:</th><td>${row.cells[4].textContent.trim()}</td></tr>
            <tr><th>Estado:</th><td>${row.cells[5].innerHTML}</td></tr>
        </table>
    `;
    document.getElementById('modal-contenido').innerHTML = contenido;
}

function cerrarModalDetalle() {
    document.getElementById('modal-detalle').style.display = 'none';
}

document.getElementById('modal-detalle').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalDetalle();
});
</script>
