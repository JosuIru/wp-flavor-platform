<?php
/**
 * Template: Calendario de Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_eventos = $wpdb->prefix . 'flavor_eventos';

// Obtener mes y año actual o del parámetro
$mes_actual = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('n'));
$anio_actual = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));

// Validar mes y año
if ($mes_actual < 1) { $mes_actual = 12; $anio_actual--; }
if ($mes_actual > 12) { $mes_actual = 1; $anio_actual++; }

// Calcular fechas del mes
$primer_dia = "$anio_actual-" . str_pad($mes_actual, 2, '0', STR_PAD_LEFT) . "-01";
$ultimo_dia = date('Y-m-t', strtotime($primer_dia));

// Obtener eventos del mes
$eventos = $wpdb->get_results($wpdb->prepare(
    "SELECT id, titulo, fecha_inicio, fecha_fin, tipo, ubicacion, imagen
     FROM $tabla_eventos
     WHERE estado = 'publicado'
     AND fecha_inicio BETWEEN %s AND %s
     ORDER BY fecha_inicio ASC",
    $primer_dia,
    $ultimo_dia
));

// Función auxiliar para extraer hora de datetime
$extraer_hora = function($datetime) {
    if (empty($datetime)) return '';
    return date('H:i', strtotime($datetime));
};

// Agrupar eventos por día
$eventos_por_dia = [];
foreach ($eventos as $evento) {
    $dia = intval(date('j', strtotime($evento->fecha_inicio)));
    if (!isset($eventos_por_dia[$dia])) {
        $eventos_por_dia[$dia] = [];
    }
    $eventos_por_dia[$dia][] = $evento;
}

// Información del calendario
$nombre_mes = date_i18n('F Y', strtotime($primer_dia));
$primer_dia_semana = intval(date('N', strtotime($primer_dia)));
$dias_mes = intval(date('t', strtotime($primer_dia)));
$hoy = intval(date('j'));
$mes_hoy = intval(date('n'));
$anio_hoy = intval(date('Y'));

$dias_semana = [
    __('Lun', 'flavor-chat-ia'),
    __('Mar', 'flavor-chat-ia'),
    __('Mié', 'flavor-chat-ia'),
    __('Jue', 'flavor-chat-ia'),
    __('Vie', 'flavor-chat-ia'),
    __('Sáb', 'flavor-chat-ia'),
    __('Dom', 'flavor-chat-ia'),
];

// URLs para navegación
$mes_anterior = $mes_actual - 1;
$anio_anterior = $anio_actual;
if ($mes_anterior < 1) { $mes_anterior = 12; $anio_anterior--; }

$mes_siguiente = $mes_actual + 1;
$anio_siguiente = $anio_actual;
if ($mes_siguiente > 12) { $mes_siguiente = 1; $anio_siguiente++; }

$url_anterior = add_query_arg(['mes' => $mes_anterior, 'anio' => $anio_anterior], get_permalink());
$url_siguiente = add_query_arg(['mes' => $mes_siguiente, 'anio' => $anio_siguiente], get_permalink());
?>

<div class="eventos-calendario-wrapper">
    <div class="calendario-header">
        <a href="<?php echo esc_url($url_anterior); ?>" class="calendario-nav-btn">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </a>
        <h2 class="calendario-mes"><?php echo esc_html(ucfirst($nombre_mes)); ?></h2>
        <a href="<?php echo esc_url($url_siguiente); ?>" class="calendario-nav-btn">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </a>
    </div>

    <div class="calendario-grid">
        <!-- Cabecera de días de la semana -->
        <?php foreach ($dias_semana as $dia_semana): ?>
            <div class="calendario-dia-semana"><?php echo esc_html($dia_semana); ?></div>
        <?php endforeach; ?>

        <!-- Días vacíos al inicio -->
        <?php for ($i = 1; $i < $primer_dia_semana; $i++): ?>
            <div class="calendario-dia calendario-dia--vacio"></div>
        <?php endfor; ?>

        <!-- Días del mes -->
        <?php for ($dia = 1; $dia <= $dias_mes; $dia++): ?>
            <?php
            $es_hoy = ($dia === $hoy && $mes_actual === $mes_hoy && $anio_actual === $anio_hoy);
            $tiene_eventos = isset($eventos_por_dia[$dia]);
            $eventos_dia = $eventos_por_dia[$dia] ?? [];
            ?>
            <div class="calendario-dia <?php echo $es_hoy ? 'calendario-dia--hoy' : ''; ?> <?php echo $tiene_eventos ? 'calendario-dia--con-eventos' : ''; ?>">
                <span class="calendario-dia-numero"><?php echo $dia; ?></span>
                <?php if ($tiene_eventos): ?>
                    <div class="calendario-dia-eventos">
                        <?php foreach (array_slice($eventos_dia, 0, 3) as $evento): ?>
                            <a href="<?php echo add_query_arg('evento_id', $evento->id, get_permalink()); ?>"
                               class="calendario-evento"
                               title="<?php echo esc_attr($evento->titulo); ?>">
                                <?php echo esc_html(mb_substr($evento->titulo, 0, 15)); ?>
                                <?php if (mb_strlen($evento->titulo) > 15): ?>...<?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if (count($eventos_dia) > 3): ?>
                            <span class="calendario-mas-eventos">
                                +<?php echo count($eventos_dia) - 3; ?> <?php _e('más', 'flavor-chat-ia'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Lista de eventos del mes -->
    <?php if ($eventos): ?>
        <div class="eventos-lista-mes">
            <h3><?php _e('Eventos este mes', 'flavor-chat-ia'); ?></h3>
            <div class="eventos-lista">
                <?php foreach ($eventos as $evento): ?>
                    <a href="<?php echo add_query_arg('evento_id', $evento->id, get_permalink()); ?>" class="evento-item">
                        <div class="evento-item-fecha">
                            <span class="evento-item-dia"><?php echo date('d', strtotime($evento->fecha_inicio)); ?></span>
                            <span class="evento-item-mes"><?php echo date_i18n('M', strtotime($evento->fecha_inicio)); ?></span>
                        </div>
                        <div class="evento-item-info">
                            <h4><?php echo esc_html($evento->titulo); ?></h4>
                            <span class="evento-item-meta">
                                <?php
                                $hora_evento = $extraer_hora($evento->fecha_inicio);
                                if ($hora_evento && $hora_evento !== '00:00'): ?>
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($hora_evento); ?>
                                <?php endif; ?>
                                <?php if ($evento->ubicacion): ?>
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($evento->ubicacion); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="evento-item-tipo"><?php echo esc_html(ucfirst($evento->tipo)); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.eventos-calendario-wrapper { max-width: 900px; margin: 0 auto; }
.calendario-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.calendario-mes { margin: 0; font-size: 1.5rem; color: #1f2937; }
.calendario-nav-btn { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f3f4f6; color: #374151; text-decoration: none; transition: all 0.2s; }
.calendario-nav-btn:hover { background: #4f46e5; color: white; }
.calendario-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; background: #e5e7eb; border-radius: 12px; overflow: hidden; }
.calendario-dia-semana { padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.875rem; color: #6b7280; background: #f9fafb; }
.calendario-dia { min-height: 100px; padding: 0.5rem; background: white; }
.calendario-dia--vacio { background: #f9fafb; }
.calendario-dia--hoy { background: #eff6ff; }
.calendario-dia--hoy .calendario-dia-numero { background: #4f46e5; color: white; }
.calendario-dia-numero { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; font-weight: 500; font-size: 0.875rem; }
.calendario-dia-eventos { margin-top: 0.5rem; display: flex; flex-direction: column; gap: 2px; }
.calendario-evento { display: block; padding: 2px 6px; background: #4f46e5; color: white; border-radius: 4px; font-size: 0.7rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-decoration: none; }
.calendario-evento:hover { background: #4338ca; }
.calendario-mas-eventos { font-size: 0.7rem; color: #6b7280; margin-top: 2px; }
.eventos-lista-mes { margin-top: 2rem; }
.eventos-lista-mes h3 { margin: 0 0 1rem; font-size: 1.25rem; }
.eventos-lista { display: flex; flex-direction: column; gap: 0.75rem; }
.evento-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); text-decoration: none; transition: all 0.2s; }
.evento-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.12); transform: translateY(-2px); }
.evento-item-fecha { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 50px; height: 50px; background: #4f46e5; color: white; border-radius: 8px; }
.evento-item-dia { font-size: 1.25rem; font-weight: 700; line-height: 1; }
.evento-item-mes { font-size: 0.7rem; text-transform: uppercase; }
.evento-item-info { flex: 1; }
.evento-item-info h4 { margin: 0 0 0.25rem; font-size: 1rem; color: #1f2937; }
.evento-item-meta { display: flex; gap: 1rem; font-size: 0.8rem; color: #6b7280; }
.evento-item-meta .dashicons { font-size: 14px; width: 14px; height: 14px; }
.evento-item-tipo { padding: 4px 10px; background: #f3f4f6; border-radius: 20px; font-size: 0.75rem; color: #6b7280; }
@media (max-width: 768px) {
    .calendario-dia { min-height: 60px; }
    .calendario-evento { display: none; }
    .calendario-dia--con-eventos::after { content: ''; display: block; width: 6px; height: 6px; background: #4f46e5; border-radius: 50%; margin: 4px auto 0; }
}
</style>
