<?php
/**
 * Vista completa de calendario de acciones.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_acciones = $wpdb->prefix . 'flavor_campanias_acciones';
$tabla_campanias = $wpdb->prefix . 'flavor_campanias';

$mes = isset($_GET['mes']) ? max(1, min(12, (int) $_GET['mes'])) : (int) current_time('n');
$anio = isset($_GET['anio']) ? max(2000, min(2100, (int) $_GET['anio'])) : (int) current_time('Y');

$inicio = sprintf('%04d-%02d-01 00:00:00', $anio, $mes);
$fin = date('Y-m-d 23:59:59', strtotime($inicio . ' +1 month -1 day'));

$acciones = $wpdb->get_results($wpdb->prepare(
    "SELECT a.id, a.titulo, a.tipo, a.fecha, a.ubicacion, c.id AS campania_id, c.titulo AS campania_titulo
     FROM {$tabla_acciones} a
     INNER JOIN {$tabla_campanias} c ON c.id = a.campania_id
     WHERE a.fecha BETWEEN %s AND %s
     ORDER BY a.fecha ASC",
    $inicio,
    $fin
));

$acciones_por_dia = [];
foreach ($acciones as $accion) {
    $dia = mysql2date('Y-m-d', $accion->fecha);
    if (!isset($acciones_por_dia[$dia])) {
        $acciones_por_dia[$dia] = [];
    }
    $acciones_por_dia[$dia][] = $accion;
}
?>

<section class="flavor-campanias-calendario">
    <header>
        <h2><?php esc_html_e('Calendario de acciones', 'flavor-chat-ia'); ?></h2>
        <form method="get" aria-label="<?php echo esc_attr__('Formulario de filtro del calendario de campanias', 'flavor-chat-ia'); ?>" style="display:flex;gap:0.5rem;align-items:flex-end;flex-wrap:wrap;">
            <p>
                <label for="cal_mes"><?php esc_html_e('Mes', 'flavor-chat-ia'); ?></label><br>
                <input id="cal_mes" type="number" name="mes" min="1" max="12" value="<?php echo esc_attr($mes); ?>">
            </p>
            <p>
                <label for="cal_anio"><?php esc_html_e('Anio', 'flavor-chat-ia'); ?></label><br>
                <input id="cal_anio" type="number" name="anio" min="2000" max="2100" value="<?php echo esc_attr($anio); ?>">
            </p>
            <p><button type="submit" class="button"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button></p>
        </form>
    </header>

    <?php if (empty($acciones_por_dia)): ?>
        <p><?php esc_html_e('No hay acciones registradas para este periodo.', 'flavor-chat-ia'); ?></p>
    <?php else: ?>
        <?php foreach ($acciones_por_dia as $dia => $acciones_dia): ?>
            <article style="border:1px solid #dcdcde;border-radius:6px;padding:0.75rem;margin:0 0 0.75rem;">
                <h3 style="margin-top:0;"><?php echo esc_html(mysql2date(get_option('date_format'), $dia)); ?></h3>
                <ul style="margin:0;padding-left:1.2rem;">
                    <?php foreach ($acciones_dia as $accion): ?>
                        <li>
                            <strong><?php echo esc_html(mysql2date(get_option('time_format'), $accion->fecha)); ?></strong>
                            - <?php echo esc_html($accion->titulo); ?>
                            (<?php echo esc_html($accion->tipo); ?>)
                            - <a href="<?php echo esc_url(add_query_arg('campania_id', (int) $accion->campania_id, home_url('/campanias/'))); ?>"><?php echo esc_html($accion->campania_titulo); ?></a>
                            <?php if (!empty($accion->ubicacion)): ?>
                                - <?php echo esc_html($accion->ubicacion); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
