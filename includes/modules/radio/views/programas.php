<?php
/**
 * Vista de Gestión de Programas de Radio
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
$tabla_locutores = $wpdb->prefix . 'flavor_radio_locutores';

// Obtener programas
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$where = $estado_filtro ? $wpdb->prepare("WHERE estado = %s", $estado_filtro) : '';

$programas = $wpdb->get_results("
    SELECT p.*, l.nombre as locutor_nombre
    FROM $tabla_programas p
    LEFT JOIN $tabla_locutores l ON p.locutor_principal_id = l.id
    $where
    ORDER BY p.fecha_creacion DESC
");

// Obtener locutores para el formulario
$locutores = $wpdb->get_results("SELECT id, nombre FROM $tabla_locutores WHERE estado = 'activo' ORDER BY nombre");
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-microphone"></span>
        <?php echo esc_html__('Gestión de Programas', 'flavor-chat-ia'); ?>
        <a href="#" class="page-title-action" onclick="abrirModalNuevoPrograma(); return false;">
            <span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html__('Nuevo Programa', 'flavor-chat-ia'); ?>
        </a>
    </h1>

    <!-- Filtros -->
    <div class="flavor-filters" style="background: #fff; padding: 15px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">

            <div style="flex: 1;">
                <label for="estado"><?php echo esc_html__('Estado:', 'flavor-chat-ia'); ?></label>
                <select name="estado" id="estado" class="regular-text">
                    <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('activo', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'activo'); ?>><?php echo esc_html__('Activo', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('inactivo', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'inactivo'); ?>><?php echo esc_html__('Inactivo', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('pausado', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'pausado'); ?>><?php echo esc_html__('Pausado', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?></button>
            <a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
        </form>
    </div>

    <!-- Tabla de programas -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Programa', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Locutor Principal', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Frecuencia', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php echo esc_html__('Horario', 'flavor-chat-ia'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($programas)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-microphone" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666;"><?php echo esc_html__('No hay programas registrados', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($programas as $programa): ?>
                        <tr>
                            <td><strong>#<?php echo $programa->id; ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($programa->nombre); ?></strong>
                                <div style="color: #666; font-size: 12px;">
                                    <?php echo wp_trim_words($programa->descripcion, 10); ?>
                                </div>
                            </td>
                            <td><?php echo esc_html($programa->locutor_nombre ?? 'Sin asignar'); ?></td>
                            <td><?php echo esc_html($programa->frecuencia ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($programa->horario ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                $estado_colors = ['activo' => '#00a32a', 'inactivo' => '#d63638', 'pausado' => '#dba617'];
                                ?>
                                <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; color: #fff; background-color: <?php echo $estado_colors[$programa->estado] ?? '#666'; ?>;">
                                    <?php echo ucfirst($programa->estado); ?>
                                </span>
                            </td>
                            <td>
                                <button class="button button-small" onclick="editarPrograma(<?php echo $programa->id; ?>)">
                                    <span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Editar', 'flavor-chat-ia'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Modal para nuevo programa -->
<div id="modal-nuevo-programa" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fff; margin: 5% auto; padding: 30px; width: 90%; max-width: 700px; border-radius: 8px; max-height: 80vh; overflow-y: auto;">
        <h2>
            <span class="dashicons dashicons-plus-alt"></span>
            <?php echo esc_html__('Nuevo Programa', 'flavor-chat-ia'); ?>
        </h2>

        <form id="form-nuevo-programa">
            <div style="margin-bottom: 20px;">
                <label><?php echo esc_html__('Nombre del Programa:', 'flavor-chat-ia'); ?></label>
                <input type="text" name="nombre" class="regular-text" required style="width: 100%;">
            </div>

            <div style="margin-bottom: 20px;">
                <label><?php echo esc_html__('Descripción:', 'flavor-chat-ia'); ?></label>
                <textarea name="descripcion" rows="4" class="large-text" style="width: 100%;"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label><?php echo esc_html__('Locutor Principal:', 'flavor-chat-ia'); ?></label>
                <select name="locutor_principal_id" class="regular-text">
                    <option value=""><?php echo esc_html__('Sin asignar', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($locutores as $locutor): ?>
                        <option value="<?php echo $locutor->id; ?>"><?php echo esc_html($locutor->nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label><?php echo esc_html__('Frecuencia:', 'flavor-chat-ia'); ?></label>
                <select name="frecuencia" class="regular-text">
                    <option value="<?php echo esc_attr__('diario', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Diario', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('semanal', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Semanal', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('quincenal', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Quincenal', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('mensual', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Mensual', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label><?php echo esc_html__('Horario:', 'flavor-chat-ia'); ?></label>
                <input type="text" name="horario" class="regular-text" placeholder="<?php echo esc_attr__('Ej: Lunes 20:00', 'flavor-chat-ia'); ?>">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" onclick="cerrarModalNuevoPrograma()" class="button button-large"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
                <button type="submit" class="button button-primary button-large"><?php echo esc_html__('Crear Programa', 'flavor-chat-ia'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNuevoPrograma() {
    document.getElementById('modal-nuevo-programa').style.display = 'block';
}

function cerrarModalNuevoPrograma() {
    document.getElementById('modal-nuevo-programa').style.display = 'none';
}

function editarPrograma(id) {
    alert('Editar programa #' + id);
}
</script>
