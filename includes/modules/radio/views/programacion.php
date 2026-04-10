<?php
/**
 * Vista de Programación de Radio (Parrilla Horaria)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
$tabla_programacion = $wpdb->prefix . 'flavor_radio_programacion';

// Obtener programación semanal
$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$horas = range(0, 23);

$programacion = $wpdb->get_results("
    SELECT pr.*, p.nombre as programa_nombre, p.descripcion
    FROM $tabla_programacion pr
    INNER JOIN $tabla_programas p ON pr.programa_id = p.id
    WHERE pr.activo = 1
    ORDER BY pr.dia_semana, pr.hora_inicio
");
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php echo esc_html__('Programación / Parrilla Horaria', 'flavor-platform'); ?>
        <a href="#" class="page-title-action" onclick="abrirModalAgregarSlot(); return false;">
            <span class="dashicons dashicons-plus-alt"></span> <?php echo esc_html__('Agregar Slot', 'flavor-platform'); ?>
        </a>
    </h1>

    <!-- Parrilla horaria -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto;">
        <table class="flavor-parrilla" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 10px; border: 1px solid #ddd; background: #f0f0f1; min-width: 80px;"><?php echo esc_html__('Hora', 'flavor-platform'); ?></th>
                    <?php foreach ($dias_semana as $dia): ?>
                        <th style="padding: 10px; border: 1px solid #ddd; background: #f0f0f1;"><?php echo $dia; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach (range(6, 23) as $hora): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; background: #f9f9f9; font-weight: 600; text-align: center;">
                            <?php echo sprintf('%02d:00', $hora); ?>
                        </td>
                        <?php foreach ($dias_semana as $indice_dia => $dia): ?>
                            <?php
                            $slot = array_values(array_filter($programacion, function($p) use ($indice_dia, $hora) {
                                return $p->dia_semana == $indice_dia && date('H', strtotime($p->hora_inicio)) == $hora;
                            }));
                            ?>
                            <td style="padding: 5px; border: 1px solid #ddd; vertical-align: top; min-height: 60px;">
                                <?php if (!empty($slot)): ?>
                                    <?php foreach ($slot as $programa): ?>
                                        <div style="background: <?php echo ['#2271b1', '#00a32a', '#8c49d8', '#dba617'][$programa->programa_id % 4]; ?>; color: #fff; padding: 8px; border-radius: 4px; margin-bottom: 5px; cursor: pointer;" onclick="editarSlot(<?php echo $programa->id; ?>)">
                                            <strong style="display: block; font-size: 12px;"><?php echo esc_html($programa->programa_nombre); ?></strong>
                                            <small><?php echo date('H:i', strtotime($programa->hora_inicio)) . ' - ' . date('H:i', strtotime($programa->hora_fin)); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Modal Nuevo Slot -->
<div id="modal-slot" class="flavor-modal" style="display:none;">
    <div class="flavor-modal-overlay" onclick="cerrarModalSlot()"></div>
    <div class="flavor-modal-content" style="min-width:400px;">
        <button class="flavor-modal-close" onclick="cerrarModalSlot()">&times;</button>
        <h3><?php echo esc_html__('Nuevo Slot de Programación', 'flavor-platform'); ?></h3>
        <form id="form-slot" method="post">
            <?php wp_nonce_field('nuevo_slot', 'slot_nonce'); ?>
            <input type="hidden" name="accion" value="crear_slot">
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;"><?php echo esc_html__('Programa', 'flavor-platform'); ?></label>
                <select name="programa_id" required style="width:100%;padding:8px;">
                    <?php
                    global $wpdb;
                    $programas = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}flavor_radio_programas WHERE estado = 'activo'");
                    foreach ($programas as $p) {
                        echo '<option value="' . esc_attr($p->id) . '">' . esc_html($p->nombre) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;"><?php echo esc_html__('Día', 'flavor-platform'); ?></label>
                <select name="dia_semana" required style="width:100%;padding:8px;">
                    <option value="1"><?php echo esc_html__('Lunes', 'flavor-platform'); ?></option>
                    <option value="2"><?php echo esc_html__('Martes', 'flavor-platform'); ?></option>
                    <option value="3"><?php echo esc_html__('Miércoles', 'flavor-platform'); ?></option>
                    <option value="4"><?php echo esc_html__('Jueves', 'flavor-platform'); ?></option>
                    <option value="5"><?php echo esc_html__('Viernes', 'flavor-platform'); ?></option>
                    <option value="6"><?php echo esc_html__('Sábado', 'flavor-platform'); ?></option>
                    <option value="0"><?php echo esc_html__('Domingo', 'flavor-platform'); ?></option>
                </select>
            </div>
            <div style="display:flex;gap:15px;margin-bottom:15px;">
                <div style="flex:1;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;"><?php echo esc_html__('Hora inicio', 'flavor-platform'); ?></label>
                    <input type="time" name="hora_inicio" required style="width:100%;padding:8px;">
                </div>
                <div style="flex:1;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;"><?php echo esc_html__('Hora fin', 'flavor-platform'); ?></label>
                    <input type="time" name="hora_fin" required style="width:100%;padding:8px;">
                </div>
            </div>
            <div style="text-align:right;">
                <button type="button" class="button" onclick="cerrarModalSlot()"><?php echo esc_html__('Cancelar', 'flavor-platform'); ?></button>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Guardar', 'flavor-platform'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
.flavor-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100000; }
.flavor-modal-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); }
.flavor-modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 25px; border-radius: 8px; }
.flavor-modal-close { position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; }
</style>

<script>
function abrirModalAgregarSlot() {
    document.getElementById('modal-slot').style.display = 'block';
}

function cerrarModalSlot() {
    document.getElementById('modal-slot').style.display = 'none';
}

function editarSlot(id) {
    window.location.href = '<?php echo admin_url('admin.php?page=flavor-radio&tab=programacion&editar='); ?>' + id;
}
</script>

<style>
.flavor-parrilla tbody tr:hover {
    background-color: #f6f7f7;
}
</style>
