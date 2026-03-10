<?php
/**
 * Vista Admin: Calendario de Reservas
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_reservas = $wpdb->prefix . 'flavor_reservas';
$tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_reservas}'") === $tabla_reservas;

// Parámetros de fecha
$mes_actual = isset($_GET['mes']) ? absint($_GET['mes']) : (int) date('n');
$anio_actual = isset($_GET['anio']) ? absint($_GET['anio']) : (int) date('Y');
$recurso_filtro = isset($_GET['recurso']) ? absint($_GET['recurso']) : 0;

// Validar mes/año
if ($mes_actual < 1 || $mes_actual > 12) $mes_actual = (int) date('n');
if ($anio_actual < 2020 || $anio_actual > 2030) $anio_actual = (int) date('Y');

$primer_dia = mktime(0, 0, 0, $mes_actual, 1, $anio_actual);
$dias_en_mes = (int) date('t', $primer_dia);
$dia_semana_inicio = (int) date('N', $primer_dia); // 1=Lun, 7=Dom

// Navegación
$mes_anterior = $mes_actual - 1;
$anio_anterior = $anio_actual;
if ($mes_anterior < 1) { $mes_anterior = 12; $anio_anterior--; }

$mes_siguiente = $mes_actual + 1;
$anio_siguiente = $anio_actual;
if ($mes_siguiente > 12) { $mes_siguiente = 1; $anio_siguiente++; }

// Obtener recursos
$recursos = [];
if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_recursos}'") === $tabla_recursos) {
    $recursos = $wpdb->get_results("SELECT id, nombre FROM {$tabla_recursos} WHERE estado = 'disponible' ORDER BY nombre ASC");
}

// Obtener reservas del mes
$reservas_mes = [];
if ($tabla_existe) {
    $fecha_inicio = sprintf('%04d-%02d-01', $anio_actual, $mes_actual);
    $fecha_fin = sprintf('%04d-%02d-%02d', $anio_actual, $mes_actual, $dias_en_mes);

    $where = "fecha_inicio BETWEEN %s AND %s AND estado != 'cancelada'";
    $params = [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'];

    if ($recurso_filtro) {
        $where .= " AND recurso_id = %d";
        $params[] = $recurso_filtro;
    }

    $sql = "SELECT r.*, u.display_name, rec.nombre as recurso_nombre
            FROM {$tabla_reservas} r
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            LEFT JOIN {$tabla_recursos} rec ON r.recurso_id = rec.id
            WHERE {$where}
            ORDER BY fecha_inicio ASC";

    $reservas = $wpdb->get_results($wpdb->prepare($sql, $params));

    foreach ($reservas as $res) {
        $dia = (int) date('j', strtotime($res->fecha_inicio));
        if (!isset($reservas_mes[$dia])) $reservas_mes[$dia] = [];
        $reservas_mes[$dia][] = $res;
    }
}

$nombres_meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
$dias_semana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
?>

<div class="wrap">
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=reservas-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-calendar-alt" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Reservas', 'flavor-chat-ia'); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Calendario', 'flavor-chat-ia'); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-calendar"></span>
        <?php _e('Calendario de Reservas', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Navegación del calendario -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0; background: #fff; padding: 15px; border: 1px solid #c3c4c7;">
        <a href="<?php echo admin_url("admin.php?page=reservas-calendario&mes={$mes_anterior}&anio={$anio_anterior}&recurso={$recurso_filtro}"); ?>" class="button">
            <span class="dashicons dashicons-arrow-left-alt2" style="margin-top: 3px;"></span>
            <?php _e('Anterior', 'flavor-chat-ia'); ?>
        </a>

        <div style="text-align: center;">
            <h2 style="margin: 0;"><?php echo esc_html($nombres_meses[$mes_actual] . ' ' . $anio_actual); ?></h2>
            <form method="get" style="margin-top: 10px; display: flex; gap: 8px; justify-content: center;">
                <input type="hidden" name="page" value="reservas-calendario">
                <input type="hidden" name="mes" value="<?php echo esc_attr($mes_actual); ?>">
                <input type="hidden" name="anio" value="<?php echo esc_attr($anio_actual); ?>">
                <select name="recurso" onchange="this.form.submit()">
                    <option value=""><?php _e('Todos los recursos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($recursos as $rec): ?>
                    <option value="<?php echo esc_attr($rec->id); ?>" <?php selected($recurso_filtro, $rec->id); ?>><?php echo esc_html($rec->nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <a href="<?php echo admin_url("admin.php?page=reservas-calendario&mes={$mes_siguiente}&anio={$anio_siguiente}&recurso={$recurso_filtro}"); ?>" class="button">
            <?php _e('Siguiente', 'flavor-chat-ia'); ?>
            <span class="dashicons dashicons-arrow-right-alt2" style="margin-top: 3px;"></span>
        </a>
    </div>

    <!-- Calendario -->
    <div style="background: #fff; border: 1px solid #c3c4c7; overflow: hidden;">
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); background: #f0f0f1; border-bottom: 1px solid #c3c4c7;">
            <?php foreach ($dias_semana as $dia): ?>
            <div style="padding: 10px; text-align: center; font-weight: 600; color: #1d2327;"><?php echo esc_html($dia); ?></div>
            <?php endforeach; ?>
        </div>

        <div style="display: grid; grid-template-columns: repeat(7, 1fr);">
            <?php
            // Días vacíos antes del primer día
            for ($i = 1; $i < $dia_semana_inicio; $i++):
            ?>
            <div style="min-height: 100px; background: #f9f9f9; border: 1px solid #e5e5e5;"></div>
            <?php endfor; ?>

            <?php
            // Días del mes
            for ($dia = 1; $dia <= $dias_en_mes; $dia++):
                $es_hoy = ($dia == date('j') && $mes_actual == date('n') && $anio_actual == date('Y'));
                $tiene_reservas = isset($reservas_mes[$dia]) && count($reservas_mes[$dia]) > 0;
            ?>
            <div style="min-height: 100px; border: 1px solid #e5e5e5; padding: 5px; <?php echo $es_hoy ? 'background: #e7f3ff;' : ''; ?>">
                <div style="font-weight: <?php echo $es_hoy ? 'bold' : 'normal'; ?>; color: <?php echo $es_hoy ? '#2271b1' : '#1d2327'; ?>; margin-bottom: 5px;">
                    <?php echo $dia; ?>
                </div>
                <?php if ($tiene_reservas): ?>
                    <?php foreach (array_slice($reservas_mes[$dia], 0, 3) as $res): ?>
                    <div style="background: #2271b1; color: #fff; padding: 2px 5px; border-radius: 3px; font-size: 10px; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr($res->recurso_nombre . ' - ' . $res->display_name); ?>">
                        <?php echo esc_html(date('H:i', strtotime($res->fecha_inicio)) . ' ' . ($res->recurso_nombre ?? '')); ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($reservas_mes[$dia]) > 3): ?>
                    <div style="font-size: 10px; color: #646970;">+<?php echo count($reservas_mes[$dia]) - 3; ?> más</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endfor; ?>

            <?php
            // Días vacíos después del último día
            $ultimo_dia_semana = ($dia_semana_inicio + $dias_en_mes - 2) % 7 + 1;
            for ($i = $ultimo_dia_semana; $i < 7; $i++):
            ?>
            <div style="min-height: 100px; background: #f9f9f9; border: 1px solid #e5e5e5;"></div>
            <?php endfor; ?>
        </div>
    </div>

    <div style="margin-top: 20px; display: flex; gap: 20px;">
        <a href="<?php echo admin_url('admin.php?page=reservas-listado'); ?>" class="button button-primary"><?php _e('Ver listado completo', 'flavor-chat-ia'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=reservas-nueva'); ?>" class="button"><?php _e('Nueva reserva', 'flavor-chat-ia'); ?></a>
    </div>
</div>
