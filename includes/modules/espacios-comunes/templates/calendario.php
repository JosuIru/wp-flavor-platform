<?php
/**
 * Template: Calendario de Espacios
 * Vista global de disponibilidad de todos los espacios
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
$tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

// Obtener mes y año actuales o de los parámetros
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('n'));
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : intval(date('Y'));

// Validar mes y año
if ($mes < 1) {
    $mes = 12;
    $ano--;
} elseif ($mes > 12) {
    $mes = 1;
    $ano++;
}

// Obtener espacios activos
$espacios = $wpdb->get_results(
    "SELECT id, nombre, tipo, capacidad_personas FROM $tabla_espacios WHERE estado = 'disponible' ORDER BY nombre"
);

// Filtro de espacio
$espacio_filtro = isset($_GET['espacio']) ? intval($_GET['espacio']) : 0;

// Calcular fechas del mes
$primer_dia_mes = new DateTime("$ano-$mes-01");
$ultimo_dia_mes = new DateTime($primer_dia_mes->format('Y-m-t'));
$dias_en_mes = intval($ultimo_dia_mes->format('d'));

// Día de la semana del primer día (0 = domingo, ajustar para lunes = 0)
$dia_semana_inicio = intval($primer_dia_mes->format('N')) - 1;

// Obtener reservas del mes
$where_espacio = $espacio_filtro ? $wpdb->prepare(" AND r.espacio_id = %d", $espacio_filtro) : "";
$reservas_mes = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, e.nombre as espacio_nombre
     FROM $tabla_reservas r
     INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
     WHERE YEAR(r.fecha_inicio) = %d AND MONTH(r.fecha_inicio) = %d
     AND r.estado IN ('solicitada', 'confirmada', 'en_curso')
     $where_espacio
     ORDER BY r.fecha_inicio",
    $ano,
    $mes
));

// Organizar reservas por día
$reservas_por_dia = [];
foreach ($reservas_mes as $reserva) {
    $dia = intval(date('j', strtotime($reserva->fecha_inicio)));
    if (!isset($reservas_por_dia[$dia])) {
        $reservas_por_dia[$dia] = [];
    }
    $reservas_por_dia[$dia][] = $reserva;
}

$meses = [
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

$dias_semana = [
    __('Lun', 'flavor-chat-ia'),
    __('Mar', 'flavor-chat-ia'),
    __('Mié', 'flavor-chat-ia'),
    __('Jue', 'flavor-chat-ia'),
    __('Vie', 'flavor-chat-ia'),
    __('Sáb', 'flavor-chat-ia'),
    __('Dom', 'flavor-chat-ia'),
];

$hoy = date('Y-m-d');
?>

<div class="espacios-wrapper">
    <div class="espacios-header">
        <h2 class="espacios-titulo"><?php _e('Calendario de Espacios', 'flavor-chat-ia'); ?></h2>
        <a href="<?php echo remove_query_arg(['vista', 'mes', 'ano', 'espacio']); ?>" class="btn btn-outline">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php _e('Ver espacios', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <div class="espacios-filtros">
        <div class="espacios-filtro-grupo">
            <label><?php _e('Filtrar por espacio', 'flavor-chat-ia'); ?></label>
            <select name="espacio" onchange="location.href='<?php echo add_query_arg(['espacio' => '']); ?>'.replace('espacio=', 'espacio=' + this.value);">
                <option value="0"><?php _e('Todos los espacios', 'flavor-chat-ia'); ?></option>
                <?php foreach ($espacios as $espacio): ?>
                    <option value="<?php echo $espacio->id; ?>" <?php selected($espacio_filtro, $espacio->id); ?>>
                        <?php echo esc_html($espacio->nombre); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="calendario-wrapper">
        <div class="calendario-header">
            <h3><?php echo $meses[$mes] . ' ' . $ano; ?></h3>
            <div class="calendario-nav">
                <?php
                $mes_ant = $mes - 1;
                $ano_ant = $ano;
                if ($mes_ant < 1) {
                    $mes_ant = 12;
                    $ano_ant--;
                }
                $mes_sig = $mes + 1;
                $ano_sig = $ano;
                if ($mes_sig > 12) {
                    $mes_sig = 1;
                    $ano_sig++;
                }
                ?>
                <a href="<?php echo add_query_arg(['mes' => $mes_ant, 'ano' => $ano_ant]); ?>" class="btn btn-outline btn-sm">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </a>
                <a href="<?php echo add_query_arg(['mes' => date('n'), 'ano' => date('Y')]); ?>" class="btn btn-outline btn-sm">
                    <?php _e('Hoy', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo add_query_arg(['mes' => $mes_sig, 'ano' => $ano_sig]); ?>" class="btn btn-outline btn-sm">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>
        </div>

        <div class="calendario-grid">
            <!-- Headers de días de la semana -->
            <?php foreach ($dias_semana as $dia): ?>
                <div class="calendario-dia-header"><?php echo $dia; ?></div>
            <?php endforeach; ?>

            <!-- Días vacíos al inicio -->
            <?php for ($i = 0; $i < $dia_semana_inicio; $i++): ?>
                <div class="calendario-dia otro-mes"></div>
            <?php endfor; ?>

            <!-- Días del mes -->
            <?php for ($dia = 1; $dia <= $dias_en_mes; $dia++): ?>
                <?php
                $fecha_dia = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                $es_hoy = $fecha_dia === $hoy;
                $es_pasado = $fecha_dia < $hoy;
                $reservas_dia = $reservas_por_dia[$dia] ?? [];

                $clases = 'calendario-dia';
                if ($es_hoy) $clases .= ' hoy';
                if ($es_pasado) $clases .= ' pasado';
                ?>
                <div class="<?php echo $clases; ?>" data-fecha="<?php echo $fecha_dia; ?>">
                    <span class="calendario-dia-numero"><?php echo $dia; ?></span>

                    <?php if (!empty($reservas_dia)): ?>
                        <div class="calendario-dia-reservas">
                            <?php
                            $mostradas = 0;
                            foreach ($reservas_dia as $reserva):
                                if ($mostradas >= 3) {
                                    echo '<span style="font-size: 0.65rem; color: #6b7280;">+' . (count($reservas_dia) - 3) . ' más</span>';
                                    break;
                                }
                                $mostradas++;
                                $hora_inicio_cal = date('H:i', strtotime($reserva->fecha_inicio));
                                $hora_fin_cal = date('H:i', strtotime($reserva->fecha_fin));
                            ?>
                                <div class="calendario-reserva <?php echo esc_attr($reserva->estado); ?>"
                                     title="<?php echo esc_attr($reserva->espacio_nombre . ' - ' . $hora_inicio_cal . ' a ' . $hora_fin_cal); ?>">
                                    <?php echo esc_html($hora_inicio_cal); ?>
                                    <?php if (!$espacio_filtro): ?>
                                        - <?php echo esc_html(mb_substr($reserva->espacio_nombre, 0, 10)); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>

            <!-- Días vacíos al final -->
            <?php
            $total_celdas = $dia_semana_inicio + $dias_en_mes;
            $celdas_faltantes = $total_celdas % 7 === 0 ? 0 : 7 - ($total_celdas % 7);
            for ($i = 0; $i < $celdas_faltantes; $i++):
            ?>
                <div class="calendario-dia otro-mes"></div>
            <?php endfor; ?>
        </div>

        <!-- Leyenda -->
        <div style="display: flex; gap: 1.5rem; margin-top: 1rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                <span style="width: 12px; height: 12px; background: rgba(245, 158, 11, 0.2); border-radius: 2px;"></span>
                <?php _e('Pendiente', 'flavor-chat-ia'); ?>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                <span style="width: 12px; height: 12px; background: rgba(16, 185, 129, 0.2); border-radius: 2px;"></span>
                <?php _e('Confirmada', 'flavor-chat-ia'); ?>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                <span style="width: 12px; height: 12px; background: rgba(99, 102, 241, 0.2); border-radius: 2px;"></span>
                <?php _e('En uso', 'flavor-chat-ia'); ?>
            </div>
        </div>
    </div>

    <!-- Próximas reservas -->
    <?php
    $proximas = $wpdb->get_results(
        "SELECT r.*, e.nombre as espacio_nombre, e.ubicacion
         FROM $tabla_reservas r
         INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
         WHERE DATE(r.fecha_inicio) >= CURDATE()
         AND r.estado IN ('confirmada', 'en_curso')
         $where_espacio
         ORDER BY r.fecha_inicio
         LIMIT 5"
    );

    if ($proximas):
    ?>
        <div style="margin-top: 2rem;">
            <h3 style="margin-bottom: 1rem;"><?php _e('Próximas reservas confirmadas', 'flavor-chat-ia'); ?></h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($proximas as $reserva): ?>
                    <?php
                    $hora_inicio_prox = date('H:i', strtotime($reserva->fecha_inicio));
                    $hora_fin_prox = date('H:i', strtotime($reserva->fecha_fin));
                    ?>
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                        <div style="text-align: center; padding: 0.5rem 1rem; background: #6366f1; color: #fff; border-radius: 8px; min-width: 60px;">
                            <div style="font-size: 1.5rem; font-weight: 700; line-height: 1;"><?php echo date('d', strtotime($reserva->fecha_inicio)); ?></div>
                            <div style="font-size: 0.7rem; text-transform: uppercase;"><?php echo date_i18n('M', strtotime($reserva->fecha_inicio)); ?></div>
                        </div>
                        <div style="flex: 1;">
                            <strong><?php echo esc_html($reserva->espacio_nombre); ?></strong>
                            <div style="font-size: 0.875rem; color: #6b7280;">
                                <?php echo esc_html($hora_inicio_prox); ?> - <?php echo esc_html($hora_fin_prox); ?>
                                &bull; <?php echo esc_html($reserva->ubicacion); ?>
                            </div>
                        </div>
                        <span class="reserva-card-estado <?php echo esc_attr($reserva->estado); ?>">
                            <?php echo $reserva->estado === 'en_curso' ? __('En uso', 'flavor-chat-ia') : __('Confirmada', 'flavor-chat-ia'); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
