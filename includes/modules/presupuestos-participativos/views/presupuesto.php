<?php
/**
 * Vista Asignación de Presupuesto - Módulo Presupuestos Participativos
 *
 * @package FlavorChatIA
 * @subpackage Modules\PresupuestosParticipativos
 */
if (!defined('ABSPATH')) exit;

global $wpdb;

// Tablas
$tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
$tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
$tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
$tabla_categorias = $wpdb->prefix . 'flavor_pp_categorias';
$tabla_ejecucion = $wpdb->prefix . 'flavor_pp_ejecucion';

// Procesar acciones
if (isset($_POST['accion_presupuesto']) && wp_verify_nonce($_POST['_wpnonce'], 'pp_presupuesto_action')) {
    $accion = sanitize_text_field($_POST['accion_presupuesto']);

    if ($accion === 'asignar_presupuesto' && isset($_POST['proyecto_id'])) {
        $proyecto_id = intval($_POST['proyecto_id']);
        $presupuesto_asignado = floatval($_POST['presupuesto_asignado']);

        // Actualizar presupuesto aprobado en proyectos
        $wpdb->update(
            $tabla_proyectos,
            ['presupuesto_aprobado' => $presupuesto_asignado],
            ['id' => $proyecto_id],
            ['%f'],
            ['%d']
        );

        // Crear o actualizar registro de ejecución
        $existe_ejecucion = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_ejecucion} WHERE propuesta_id = %d",
            $proyecto_id
        ));

        if ($existe_ejecucion) {
            $wpdb->update(
                $tabla_ejecucion,
                ['presupuesto_asignado' => $presupuesto_asignado],
                ['propuesta_id' => $proyecto_id],
                ['%f'],
                ['%d']
            );
        } else {
            $wpdb->insert($tabla_ejecucion, [
                'propuesta_id' => $proyecto_id,
                'presupuesto_asignado' => $presupuesto_asignado,
                'presupuesto_ejecutado' => 0,
                'estado' => 'planificacion',
                'created_at' => current_time('mysql'),
            ]);
        }

        echo '<div class="notice notice-success is-dismissible"><p>Presupuesto asignado correctamente.</p></div>';
    }

    if ($accion === 'actualizar_categoria' && isset($_POST['categoria_id'])) {
        $categoria_id = intval($_POST['categoria_id']);
        $presupuesto_reservado = floatval($_POST['presupuesto_reservado']);

        $wpdb->update(
            $tabla_categorias,
            ['presupuesto_reservado' => $presupuesto_reservado],
            ['id' => $categoria_id],
            ['%f'],
            ['%d']
        );

        echo '<div class="notice notice-success is-dismissible"><p>Presupuesto de categoría actualizado.</p></div>';
    }
}

// Filtro de edición
$edicion_id = isset($_GET['edicion_id']) ? intval($_GET['edicion_id']) : 0;

// Obtener ediciones disponibles
$ediciones = $wpdb->get_results("SELECT id, nombre, anio, presupuesto_total, fase, estado FROM {$tabla_ediciones} ORDER BY anio DESC, id DESC");

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

// Estadísticas de presupuesto
$presupuesto_total = $edicion_actual ? floatval($edicion_actual->presupuesto_total) : 0;

// Presupuesto asignado (proyectos con presupuesto_aprobado)
$presupuesto_asignado = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(presupuesto_aprobado), 0) FROM {$tabla_proyectos} WHERE edicion_id = %d AND presupuesto_aprobado IS NOT NULL",
    $edicion_id
)) ?: 0;

// Presupuesto solicitado total
$presupuesto_solicitado = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(presupuesto_solicitado), 0) FROM {$tabla_proyectos} WHERE edicion_id = %d",
    $edicion_id
)) ?: 0;

// Presupuesto ejecutado
$presupuesto_ejecutado = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(e.presupuesto_ejecutado), 0)
     FROM {$tabla_ejecucion} e
     INNER JOIN {$tabla_proyectos} p ON e.propuesta_id = p.id
     WHERE p.edicion_id = %d",
    $edicion_id
)) ?: 0;

$presupuesto_disponible = $presupuesto_total - $presupuesto_asignado;
$porcentaje_asignado = $presupuesto_total > 0 ? round(($presupuesto_asignado / $presupuesto_total) * 100, 1) : 0;
$porcentaje_ejecutado = $presupuesto_asignado > 0 ? round(($presupuesto_ejecutado / $presupuesto_asignado) * 100, 1) : 0;

// Proyectos seleccionados/aprobados para asignación
$proyectos_para_asignar = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*,
            COALESCE(e.presupuesto_ejecutado, 0) as ejecutado,
            e.estado as estado_ejecucion,
            e.porcentaje_avance
     FROM {$tabla_proyectos} p
     LEFT JOIN {$tabla_ejecucion} e ON e.propuesta_id = p.id
     WHERE p.edicion_id = %d
     AND p.estado IN ('seleccionado', 'en_ejecucion', 'ejecutado', 'validado')
     ORDER BY p.votos_recibidos DESC, p.ranking ASC",
    $edicion_id
));

// Distribución por categoría
$distribucion_categorias = $wpdb->get_results($wpdb->prepare(
    "SELECT
        p.categoria,
        COUNT(*) as total_proyectos,
        COALESCE(SUM(p.presupuesto_solicitado), 0) as solicitado,
        COALESCE(SUM(p.presupuesto_aprobado), 0) as asignado
     FROM {$tabla_proyectos} p
     WHERE p.edicion_id = %d
     GROUP BY p.categoria
     ORDER BY asignado DESC",
    $edicion_id
));

// Categorías con presupuesto reservado
$categorias = $wpdb->get_results("SELECT * FROM {$tabla_categorias} WHERE activa = 1 ORDER BY orden ASC");
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-calculator"></span> Asignación de Presupuesto</h1>
    <hr class="wp-header-end">

    <!-- Selector de Edición -->
    <div class="postbox" style="margin-top: 20px; padding: 15px;">
        <form method="get" style="display: flex; align-items: center; gap: 15px;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
            <input type="hidden" name="vista" value="presupuesto">
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
                </span>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!$edicion_actual): ?>
        <div class="notice notice-warning">
            <p>No hay ediciones de presupuestos participativos configuradas. <a href="<?php echo admin_url('admin.php?page=flavor-pp&vista=dashboard'); ?>">Crear una edición</a></p>
        </div>
    <?php else: ?>

    <!-- KPIs de Presupuesto -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin: 20px 0;">
        <!-- Presupuesto Total -->
        <div class="postbox" style="margin: 0; padding: 20px; text-align: center;">
            <span class="dashicons dashicons-money-alt" style="font-size: 40px; color: #2271b1; margin-bottom: 10px;"></span>
            <h3 style="margin: 0; font-size: 28px; color: #1d2327;">
                <?php echo number_format($presupuesto_total, 2, ',', '.'); ?> €
            </h3>
            <p style="margin: 5px 0 0; color: #646970;">Presupuesto Total</p>
        </div>

        <!-- Presupuesto Asignado -->
        <div class="postbox" style="margin: 0; padding: 20px; text-align: center;">
            <span class="dashicons dashicons-yes-alt" style="font-size: 40px; color: #00a32a; margin-bottom: 10px;"></span>
            <h3 style="margin: 0; font-size: 28px; color: #1d2327;">
                <?php echo number_format($presupuesto_asignado, 2, ',', '.'); ?> €
            </h3>
            <p style="margin: 5px 0 0; color: #646970;">
                Asignado (<?php echo $porcentaje_asignado; ?>%)
            </p>
            <div style="background: #e0e0e0; height: 8px; border-radius: 4px; margin-top: 10px;">
                <div style="background: #00a32a; height: 100%; width: <?php echo min($porcentaje_asignado, 100); ?>%; border-radius: 4px;"></div>
            </div>
        </div>

        <!-- Presupuesto Disponible -->
        <div class="postbox" style="margin: 0; padding: 20px; text-align: center;">
            <span class="dashicons dashicons-bank" style="font-size: 40px; color: #dba617; margin-bottom: 10px;"></span>
            <h3 style="margin: 0; font-size: 28px; color: <?php echo $presupuesto_disponible < 0 ? '#d63638' : '#1d2327'; ?>;">
                <?php echo number_format($presupuesto_disponible, 2, ',', '.'); ?> €
            </h3>
            <p style="margin: 5px 0 0; color: #646970;">Disponible</p>
            <?php if ($presupuesto_disponible < 0): ?>
                <span style="color: #d63638; font-size: 12px;">⚠️ Sobrepasado</span>
            <?php endif; ?>
        </div>

        <!-- Presupuesto Ejecutado -->
        <div class="postbox" style="margin: 0; padding: 20px; text-align: center;">
            <span class="dashicons dashicons-chart-line" style="font-size: 40px; color: #8c5ae8; margin-bottom: 10px;"></span>
            <h3 style="margin: 0; font-size: 28px; color: #1d2327;">
                <?php echo number_format($presupuesto_ejecutado, 2, ',', '.'); ?> €
            </h3>
            <p style="margin: 5px 0 0; color: #646970;">
                Ejecutado (<?php echo $porcentaje_ejecutado; ?>% del asignado)
            </p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <!-- Proyectos para Asignación -->
        <div class="postbox" style="margin: 0;">
            <div class="postbox-header">
                <h2 style="padding: 10px 15px; margin: 0;">
                    <span class="dashicons dashicons-portfolio"></span>
                    Proyectos para Asignación de Presupuesto
                </h2>
            </div>
            <div class="inside" style="padding: 0;">
                <?php if (empty($proyectos_para_asignar)): ?>
                    <p style="padding: 20px; text-align: center; color: #646970;">
                        No hay proyectos seleccionados para asignar presupuesto en esta edición.
                    </p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Proyecto</th>
                                <th style="width: 15%;">Solicitado</th>
                                <th style="width: 15%;">Asignado</th>
                                <th style="width: 10%;">Votos</th>
                                <th style="width: 12%;">Estado</th>
                                <th style="width: 18%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proyectos_para_asignar as $proyecto): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($proyecto->titulo); ?></strong>
                                        <br><small style="color: #646970;"><?php echo esc_html(ucfirst($proyecto->categoria)); ?></small>
                                    </td>
                                    <td><?php echo number_format($proyecto->presupuesto_solicitado, 2, ',', '.'); ?> €</td>
                                    <td>
                                        <?php if ($proyecto->presupuesto_aprobado): ?>
                                            <strong style="color: #00a32a;"><?php echo number_format($proyecto->presupuesto_aprobado, 2, ',', '.'); ?> €</strong>
                                            <?php if ($proyecto->ejecutado > 0): ?>
                                                <br><small>Ejecutado: <?php echo number_format($proyecto->ejecutado, 2, ',', '.'); ?> €</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #dba617;">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="dashicons dashicons-thumbs-up" style="color: #2271b1;"></span>
                                        <?php echo intval($proyecto->votos_recibidos); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $estado_labels = [
                                            'seleccionado' => ['label' => 'Seleccionado', 'color' => '#2271b1'],
                                            'en_ejecucion' => ['label' => 'En Ejecución', 'color' => '#dba617'],
                                            'ejecutado' => ['label' => 'Completado', 'color' => '#00a32a'],
                                            'validado' => ['label' => 'Validado', 'color' => '#72aee6'],
                                        ];
                                        $estado_info = $estado_labels[$proyecto->estado] ?? ['label' => ucfirst($proyecto->estado), 'color' => '#646970'];
                                        ?>
                                        <span style="background: <?php echo $estado_info['color']; ?>; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                            <?php echo $estado_info['label']; ?>
                                        </span>
                                        <?php if ($proyecto->porcentaje_avance): ?>
                                            <br><small><?php echo $proyecto->porcentaje_avance; ?>% avance</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small" onclick="abrirModalAsignacion(<?php echo $proyecto->id; ?>, '<?php echo esc_js($proyecto->titulo); ?>', <?php echo floatval($proyecto->presupuesto_solicitado); ?>, <?php echo floatval($proyecto->presupuesto_aprobado ?: $proyecto->presupuesto_solicitado); ?>)">
                                            <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                            <?php echo $proyecto->presupuesto_aprobado ? 'Modificar' : 'Asignar'; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f0f0f1; font-weight: bold;">
                                <td>TOTALES</td>
                                <td><?php echo number_format($presupuesto_solicitado, 2, ',', '.'); ?> €</td>
                                <td><?php echo number_format($presupuesto_asignado, 2, ',', '.'); ?> €</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Distribución por Categoría -->
        <div>
            <div class="postbox" style="margin: 0 0 20px 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;">
                        <span class="dashicons dashicons-category"></span>
                        Distribución por Categoría
                    </h2>
                </div>
                <div class="inside">
                    <?php if (empty($distribucion_categorias)): ?>
                        <p style="color: #646970; text-align: center;">Sin datos de categorías</p>
                    <?php else: ?>
                        <?php foreach ($distribucion_categorias as $cat):
                            $porcentaje_cat = $presupuesto_asignado > 0 ? round(($cat->asignado / $presupuesto_asignado) * 100, 1) : 0;
                        ?>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span><strong><?php echo esc_html(ucfirst($cat->categoria ?: 'Sin categoría')); ?></strong></span>
                                    <span><?php echo number_format($cat->asignado, 0, ',', '.'); ?> €</span>
                                </div>
                                <div style="background: #e0e0e0; height: 10px; border-radius: 5px;">
                                    <div style="background: #2271b1; height: 100%; width: <?php echo min($porcentaje_cat, 100); ?>%; border-radius: 5px;"></div>
                                </div>
                                <small style="color: #646970;"><?php echo $cat->total_proyectos; ?> proyectos · <?php echo $porcentaje_cat; ?>%</small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Presupuesto Reservado por Categoría -->
            <div class="postbox" style="margin: 0;">
                <div class="postbox-header">
                    <h2 style="padding: 10px 15px; margin: 0;">
                        <span class="dashicons dashicons-lock"></span>
                        Presupuesto Reservado
                    </h2>
                </div>
                <div class="inside">
                    <?php foreach ($categorias as $categoria): ?>
                        <form method="post" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #e0e0e0;">
                            <?php wp_nonce_field('pp_presupuesto_action'); ?>
                            <input type="hidden" name="accion_presupuesto" value="actualizar_categoria">
                            <input type="hidden" name="categoria_id" value="<?php echo $categoria->id; ?>">
                            <span class="dashicons <?php echo esc_attr($categoria->icono); ?>" style="color: <?php echo esc_attr($categoria->color); ?>;"></span>
                            <span style="flex: 1; font-size: 12px;"><?php echo esc_html($categoria->nombre); ?></span>
                            <input type="number" name="presupuesto_reservado" value="<?php echo floatval($categoria->presupuesto_reservado); ?>" step="0.01" min="0" style="width: 100px; font-size: 12px;">
                            <button type="submit" class="button button-small">
                                <span class="dashicons dashicons-saved" style="vertical-align: middle;"></span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Modal de Asignación -->
<div id="modal-asignacion" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 100000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 450px; width: 90%;">
        <h3 style="margin-top: 0;">
            <span class="dashicons dashicons-calculator"></span>
            Asignar Presupuesto
        </h3>
        <p id="modal-proyecto-titulo" style="font-weight: bold;"></p>
        <form method="post">
            <?php wp_nonce_field('pp_presupuesto_action'); ?>
            <input type="hidden" name="accion_presupuesto" value="asignar_presupuesto">
            <input type="hidden" name="proyecto_id" id="modal-proyecto-id">

            <table class="form-table">
                <tr>
                    <th>Solicitado:</th>
                    <td><span id="modal-solicitado"></span> €</td>
                </tr>
                <tr>
                    <th><label for="presupuesto_asignado">Asignar:</label></th>
                    <td>
                        <input type="number" name="presupuesto_asignado" id="modal-presupuesto" step="0.01" min="0" class="regular-text" style="width: 150px;" required>
                        <span>€</span>
                    </td>
                </tr>
                <tr>
                    <th>Disponible:</th>
                    <td><strong style="color: <?php echo $presupuesto_disponible < 0 ? '#d63638' : '#00a32a'; ?>;"><?php echo number_format($presupuesto_disponible, 2, ',', '.'); ?> €</strong></td>
                </tr>
            </table>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="button" onclick="cerrarModalAsignacion()">Cancelar</button>
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
                    Guardar Asignación
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalAsignacion(proyectoId, titulo, solicitado, asignado) {
    document.getElementById('modal-proyecto-id').value = proyectoId;
    document.getElementById('modal-proyecto-titulo').textContent = titulo;
    document.getElementById('modal-solicitado').textContent = solicitado.toLocaleString('es-ES', {minimumFractionDigits: 2});
    document.getElementById('modal-presupuesto').value = asignado;
    document.getElementById('modal-asignacion').style.display = 'flex';
}

function cerrarModalAsignacion() {
    document.getElementById('modal-asignacion').style.display = 'none';
}

document.getElementById('modal-asignacion').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalAsignacion();
});
</script>
