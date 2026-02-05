<?php
/**
 * Vista Calendario - Módulo Reciclaje
 * Calendario de recogidas programadas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';

// Procesar acciones
$mensaje_exito = '';
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('flavor_reciclaje_recogida_action')) {
    if (isset($_POST['crear_recogida'])) {
        $datos_recogida = [
            'tipo_recogida' => sanitize_text_field($_POST['tipo_recogida']),
            'zona' => sanitize_text_field($_POST['zona']),
            'tipos_residuos' => json_encode($_POST['tipos_residuos'] ?? []),
            'fecha_programada' => sanitize_text_field($_POST['fecha_programada']),
            'hora_inicio' => sanitize_text_field($_POST['hora_inicio']),
            'hora_fin' => sanitize_text_field($_POST['hora_fin']),
            'notas' => sanitize_textarea_field($_POST['notas']),
            'estado' => 'programada',
        ];

        $resultado = $wpdb->insert($tabla_recogidas, $datos_recogida);
        if ($resultado) {
            $mensaje_exito = __('Recogida programada correctamente.', 'flavor-chat-ia');
        } else {
            $mensaje_error = __('Error al programar la recogida.', 'flavor-chat-ia');
        }
    } elseif (isset($_POST['actualizar_estado'])) {
        $recogida_id = intval($_POST['recogida_id']);
        $nuevo_estado = sanitize_text_field($_POST['nuevo_estado']);

        $wpdb->update(
            $tabla_recogidas,
            ['estado' => $nuevo_estado],
            ['id' => $recogida_id]
        );
        $mensaje_exito = __('Estado actualizado correctamente.', 'flavor-chat-ia');
    }
}

// Obtener mes y año actual o filtrado
$mes_actual = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$anio_actual = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

// Obtener recogidas del mes
$primer_dia_mes = "$anio_actual-" . str_pad($mes_actual, 2, '0', STR_PAD_LEFT) . "-01";
$ultimo_dia_mes = date('Y-m-t', strtotime($primer_dia_mes));

$recogidas_mes = $wpdb->get_results($wpdb->prepare("
    SELECT *
    FROM $tabla_recogidas
    WHERE fecha_programada >= %s
    AND fecha_programada <= %s
    ORDER BY fecha_programada ASC, hora_inicio ASC
", $primer_dia_mes, $ultimo_dia_mes));

// Agrupar recogidas por fecha
$recogidas_por_fecha = [];
foreach ($recogidas_mes as $recogida) {
    $fecha = date('Y-m-d', strtotime($recogida->fecha_programada));
    if (!isset($recogidas_por_fecha[$fecha])) {
        $recogidas_por_fecha[$fecha] = [];
    }
    $recogidas_por_fecha[$fecha][] = $recogida;
}

// Próximas recogidas (siguiente semana)
$proximas_recogidas = $wpdb->get_results("
    SELECT *
    FROM $tabla_recogidas
    WHERE fecha_programada >= CURRENT_DATE()
    AND fecha_programada <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
    AND estado IN ('programada', 'en_curso')
    ORDER BY fecha_programada ASC, hora_inicio ASC
");

// Generar estructura del calendario
$primer_dia_semana = date('N', strtotime($primer_dia_mes));
$total_dias_mes = date('t', strtotime($primer_dia_mes));

$nombres_meses = [
    1 => __('Enero', 'flavor-chat-ia'),
    2 => __('Febrero', 'flavor-chat-ia'),
    3 => __('Marzo', 'flavor-chat-ia'),
    4 => __('Abril', 'flavor-chat-ia'),
    5 => __('Mayo', 'flavor-chat-ia'),
    6 => __('Junio', 'flavor-chat-ia'),
    7 => __('Julio', 'flavor-chat-ia'),
    8 => __('Agosto', 'flavor-chat-ia'),
    9 => __('Septiembre', 'flavor-chat-ia'),
    10 => __('Octubre', 'flavor-chat-ia'),
    11 => __('Noviembre', 'flavor-chat-ia'),
    12 => __('Diciembre', 'flavor-chat-ia'),
];
?>

<div class="wrap flavor-reciclaje-calendario">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php echo esc_html__('Calendario de Recogidas', 'flavor-chat-ia'); ?>
    </h1>

    <button type="button" class="page-title-action" onclick="abrirModalRecogida()">
        <?php echo esc_html__('Programar Recogida', 'flavor-chat-ia'); ?>
    </button>

    <hr class="wp-header-end">

    <?php if ($mensaje_exito) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_exito); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($mensaje_error) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($mensaje_error); ?></p>
        </div>
    <?php endif; ?>

    <div class="flavor-calendario-container">
        <!-- Navegación del calendario -->
        <div class="flavor-calendario-nav">
            <?php
            $mes_anterior = $mes_actual - 1;
            $anio_anterior = $anio_actual;
            if ($mes_anterior < 1) {
                $mes_anterior = 12;
                $anio_anterior--;
            }

            $mes_siguiente = $mes_actual + 1;
            $anio_siguiente = $anio_actual;
            if ($mes_siguiente > 12) {
                $mes_siguiente = 1;
                $anio_siguiente++;
            }
            ?>

            <a href="?page=flavor-reciclaje-calendario&mes=<?php echo $mes_anterior; ?>&anio=<?php echo $anio_anterior; ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                <?php echo esc_html__('Anterior', 'flavor-chat-ia'); ?>
            </a>

            <h2><?php echo esc_html($nombres_meses[$mes_actual]) . ' ' . $anio_actual; ?></h2>

            <a href="?page=flavor-reciclaje-calendario&mes=<?php echo $mes_siguiente; ?>&anio=<?php echo $anio_siguiente; ?>" class="button">
                <?php echo esc_html__('Siguiente', 'flavor-chat-ia'); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>

        <div class="flavor-calendario-layout">
            <!-- Calendario visual -->
            <div class="flavor-calendario-grid-wrapper">
                <div class="flavor-calendario-grid">
                    <!-- Encabezados de días -->
                    <div class="flavor-dia-header"><?php echo esc_html__('Lun', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-dia-header"><?php echo esc_html__('Mar', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-dia-header"><?php echo esc_html__('Mié', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-dia-header"><?php echo esc_html__('Jue', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-dia-header"><?php echo esc_html__('Vie', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-dia-header"><?php echo esc_html__('Sáb', 'flavor-chat-ia'); ?></div>
                    <div class="flavor-dia-header"><?php echo esc_html__('Dom', 'flavor-chat-ia'); ?></div>

                    <?php
                    // Celdas vacías antes del primer día
                    for ($i = 1; $i < $primer_dia_semana; $i++) {
                        echo '<div class="flavor-dia-celda flavor-dia-vacio"></div>';
                    }

                    // Días del mes
                    for ($dia = 1; $dia <= $total_dias_mes; $dia++) {
                        $fecha = "$anio_actual-" . str_pad($mes_actual, 2, '0', STR_PAD_LEFT) . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);
                        $es_hoy = $fecha === date('Y-m-d');
                        $tiene_recogidas = isset($recogidas_por_fecha[$fecha]);
                        $recogidas_dia = $tiene_recogidas ? $recogidas_por_fecha[$fecha] : [];
                        ?>
                        <div class="flavor-dia-celda <?php echo $es_hoy ? 'flavor-dia-hoy' : ''; ?> <?php echo $tiene_recogidas ? 'flavor-dia-con-recogidas' : ''; ?>"
                             data-fecha="<?php echo esc_attr($fecha); ?>">
                            <div class="flavor-dia-numero"><?php echo $dia; ?></div>
                            <?php if ($tiene_recogidas) : ?>
                                <div class="flavor-dia-recogidas">
                                    <?php foreach ($recogidas_dia as $recogida) : ?>
                                        <?php
                                        $estado_clase = match($recogida->estado) {
                                            'programada' => 'flavor-estado-programada',
                                            'en_curso' => 'flavor-estado-en-curso',
                                            'completada' => 'flavor-estado-completada',
                                            'cancelada' => 'flavor-estado-cancelada',
                                            default => ''
                                        };
                                        ?>
                                        <div class="flavor-recogida-mini <?php echo $estado_clase; ?>" onclick="verDetalleRecogida(<?php echo $recogida->id; ?>)">
                                            <strong><?php echo esc_html($recogida->zona); ?></strong>
                                            <span><?php echo esc_html($recogida->hora_inicio); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }

                    // Celdas vacías después del último día
                    $dia_semana_ultimo = date('N', strtotime($ultimo_dia_mes));
                    for ($i = $dia_semana_ultimo; $i < 7; $i++) {
                        echo '<div class="flavor-dia-celda flavor-dia-vacio"></div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Panel lateral: Próximas recogidas -->
            <div class="flavor-proximas-recogidas">
                <h3><?php echo esc_html__('Próximas Recogidas', 'flavor-chat-ia'); ?></h3>

                <?php if (!empty($proximas_recogidas)) : ?>
                    <?php foreach ($proximas_recogidas as $recogida) : ?>
                        <div class="flavor-recogida-card">
                            <div class="flavor-recogida-fecha">
                                <span class="flavor-fecha-dia"><?php echo date('d', strtotime($recogida->fecha_programada)); ?></span>
                                <span class="flavor-fecha-mes"><?php echo date('M', strtotime($recogida->fecha_programada)); ?></span>
                            </div>
                            <div class="flavor-recogida-info">
                                <h4><?php echo esc_html($recogida->zona); ?></h4>
                                <p>
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($recogida->hora_inicio . ' - ' . $recogida->hora_fin); ?>
                                </p>
                                <p>
                                    <span class="dashicons dashicons-category"></span>
                                    <?php
                                    $tipos_residuos = json_decode($recogida->tipos_residuos, true);
                                    echo esc_html(implode(', ', $tipos_residuos ?? []));
                                    ?>
                                </p>
                                <?php
                                $estado_badges = [
                                    'programada' => 'flavor-badge-info',
                                    'en_curso' => 'flavor-badge-warning',
                                    'completada' => 'flavor-badge-success',
                                    'cancelada' => 'flavor-badge-danger',
                                ];
                                ?>
                                <span class="flavor-badge <?php echo $estado_badges[$recogida->estado] ?? ''; ?>">
                                    <?php echo esc_html(ucfirst($recogida->estado)); ?>
                                </span>
                            </div>
                            <div class="flavor-recogida-acciones">
                                <button class="button button-small" onclick="verDetalleRecogida(<?php echo $recogida->id; ?>)">
                                    <?php echo esc_html__('Ver', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="flavor-no-data"><?php echo esc_html__('No hay recogidas programadas próximamente.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear/editar recogida -->
<div id="modal-recogida" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h2><?php echo esc_html__('Programar Recogida', 'flavor-chat-ia'); ?></h2>
            <button type="button" class="flavor-modal-close" onclick="cerrarModalRecogida()">&times;</button>
        </div>
        <form method="post" action="">
            <?php wp_nonce_field('flavor_reciclaje_recogida_action'); ?>
            <div class="flavor-modal-body">
                <table class="form-table">
                    <tr>
                        <th><label for="tipo_recogida"><?php echo esc_html__('Tipo de Recogida', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select name="tipo_recogida" id="tipo_recogida" required>
                                <option value="programada"><?php echo esc_html__('Programada', 'flavor-chat-ia'); ?></option>
                                <option value="a_demanda"><?php echo esc_html__('A Demanda', 'flavor-chat-ia'); ?></option>
                                <option value="urgente"><?php echo esc_html__('Urgente', 'flavor-chat-ia'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="zona"><?php echo esc_html__('Zona', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="text" name="zona" id="zona" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php echo esc_html__('Tipos de Residuos', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <?php
                            $tipos_residuos = ['papel', 'plastico', 'vidrio', 'organico', 'electronico', 'voluminosos'];
                            foreach ($tipos_residuos as $tipo) :
                            ?>
                                <label style="display: inline-block; margin-right: 15px;">
                                    <input type="checkbox" name="tipos_residuos[]" value="<?php echo esc_attr($tipo); ?>">
                                    <?php echo esc_html(ucfirst($tipo)); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fecha_programada"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="date" name="fecha_programada" id="fecha_programada" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="hora_inicio"><?php echo esc_html__('Hora Inicio', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="time" name="hora_inicio" id="hora_inicio" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="hora_fin"><?php echo esc_html__('Hora Fin', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="time" name="hora_fin" id="hora_fin" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="notas"><?php echo esc_html__('Notas', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <textarea name="notas" id="notas" rows="4" class="large-text"></textarea>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="flavor-modal-footer">
                <button type="submit" name="crear_recogida" class="button button-primary">
                    <?php echo esc_html__('Programar', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="button" onclick="cerrarModalRecogida()">
                    <?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalRecogida() {
    document.getElementById('modal-recogida').style.display = 'flex';
}

function cerrarModalRecogida() {
    document.getElementById('modal-recogida').style.display = 'none';
}

function verDetalleRecogida(id) {
    // Aquí se puede implementar un modal con los detalles de la recogida
    alert('Ver recogida #' + id);
}
</script>

<style>
.flavor-calendario-container {
    margin-top: 20px;
}

.flavor-calendario-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-calendario-nav h2 {
    margin: 0;
}

.flavor-calendario-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 20px;
}

.flavor-calendario-grid-wrapper {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-calendario-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
}

.flavor-dia-header {
    padding: 10px;
    text-align: center;
    font-weight: 700;
    background: #f8f9fa;
    border-radius: 4px;
}

.flavor-dia-celda {
    min-height: 100px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
    cursor: pointer;
    transition: all 0.3s;
}

.flavor-dia-celda:hover {
    background: #f8f9fa;
}

.flavor-dia-vacio {
    background: #f8f9fa;
    cursor: default;
}

.flavor-dia-hoy {
    background: #e7f3ff;
    border-color: #0073aa;
}

.flavor-dia-con-recogidas {
    background: #fff9e6;
}

.flavor-dia-numero {
    font-weight: 700;
    margin-bottom: 5px;
}

.flavor-dia-recogidas {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.flavor-recogida-mini {
    padding: 5px;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
}

.flavor-recogida-mini strong {
    display: block;
}

.flavor-estado-programada {
    background: #d1ecf1;
    border-left: 3px solid #17a2b8;
}

.flavor-estado-en-curso {
    background: #fff3cd;
    border-left: 3px solid #ffc107;
}

.flavor-estado-completada {
    background: #d4edda;
    border-left: 3px solid #28a745;
}

.flavor-estado-cancelada {
    background: #f8d7da;
    border-left: 3px solid #dc3545;
}

.flavor-proximas-recogidas {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-proximas-recogidas h3 {
    margin-top: 0;
    margin-bottom: 20px;
}

.flavor-recogida-card {
    display: flex;
    gap: 15px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 15px;
}

.flavor-recogida-fecha {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 60px;
    padding: 10px;
    background: #0073aa;
    color: #fff;
    border-radius: 8px;
}

.flavor-fecha-dia {
    font-size: 24px;
    font-weight: 700;
}

.flavor-fecha-mes {
    font-size: 12px;
    text-transform: uppercase;
}

.flavor-recogida-info {
    flex: 1;
}

.flavor-recogida-info h4 {
    margin: 0 0 10px;
}

.flavor-recogida-info p {
    margin: 5px 0;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.flavor-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    align-items: center;
    justify-content: center;
}

.flavor-modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.flavor-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}

.flavor-modal-header h2 {
    margin: 0;
}

.flavor-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
}

.flavor-modal-body {
    padding: 20px;
}

.flavor-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

@media (max-width: 1200px) {
    .flavor-calendario-layout {
        grid-template-columns: 1fr;
    }
}
</style>
