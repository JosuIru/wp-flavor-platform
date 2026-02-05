<?php
/**
 * Vista de Programación de Radio (Parrilla Horaria)
 *
 * @package FlavorChatIA
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
        Programación / Parrilla Horaria
        <a href="#" class="page-title-action" onclick="abrirModalAgregarSlot(); return false;">
            <span class="dashicons dashicons-plus-alt"></span> Agregar Slot
        </a>
    </h1>

    <!-- Parrilla horaria -->
    <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto;">
        <table class="flavor-parrilla" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 10px; border: 1px solid #ddd; background: #f0f0f1; min-width: 80px;">Hora</th>
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

<script>
function abrirModalAgregarSlot() {
    alert('Agregar nuevo slot de programación');
}

function editarSlot(id) {
    alert('Editar slot #' + id);
}
</script>

<style>
.flavor-parrilla tbody tr:hover {
    background-color: #f6f7f7;
}
</style>
