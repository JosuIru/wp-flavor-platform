<?php
/**
 * Dashboard Tab para Fichaje de Empleados
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Fichaje_Empleados_Dashboard_Tab {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public function registrar_tabs($tabs) {
        $tabs['fichaje'] = [
            'label' => __('Fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-clock',
            'callback' => [$this, 'render_tab'],
            'priority' => 70,
        ];
        return $tabs;
    }

    public function render_tab() {
        $datos = $this->obtener_datos_usuario();
        $subtab = isset($_GET['subtab']) ? sanitize_text_field($_GET['subtab']) : 'fichar';

        ?>
        <div class="flavor-fichaje-dashboard">
            <!-- Navegación interna -->
            <div class="flavor-dashboard-subtabs">
                <a href="?tab=fichaje&subtab=fichar" class="subtab <?php echo $subtab === 'fichar' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-clock"></span> Fichar
                </a>
                <a href="?tab=fichaje&subtab=historial" class="subtab <?php echo $subtab === 'historial' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span> Historial
                </a>
                <a href="?tab=fichaje&subtab=resumen" class="subtab <?php echo $subtab === 'resumen' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-chart-bar"></span> Resumen
                </a>
                <?php if (current_user_can('manage_options')): ?>
                    <a href="?tab=fichaje&subtab=admin" class="subtab <?php echo $subtab === 'admin' ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-admin-users"></span> Administrar
                    </a>
                <?php endif; ?>
            </div>

            <div class="flavor-dashboard-content">
                <?php
                switch ($subtab) {
                    case 'historial':
                        echo do_shortcode('[fichaje_historial]');
                        break;
                    case 'resumen':
                        echo do_shortcode('[fichaje_resumen]');
                        break;
                    case 'admin':
                        if (current_user_can('manage_options')) {
                            $this->render_admin($datos);
                        }
                        break;
                    default:
                        echo do_shortcode('[fichaje_panel]');
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_fichar($datos) {
        $fichaje_activo = $datos['fichaje_activo'];
        ?>
        <div class="fichaje-principal">
            <!-- Reloj -->
            <div class="fichaje-reloj">
                <div class="reloj-hora" id="reloj-hora"><?php echo date('H:i:s'); ?></div>
                <div class="reloj-fecha"><?php echo date_i18n('l, j \d\e F \d\e Y'); ?></div>
            </div>

            <!-- Estado actual -->
            <div class="fichaje-estado <?php echo $fichaje_activo ? 'trabajando' : 'fuera'; ?>">
                <?php if ($fichaje_activo): ?>
                    <div class="estado-info">
                        <span class="estado-icono"><span class="dashicons dashicons-yes-alt"></span></span>
                        <div>
                            <strong>Trabajando</strong>
                            <span>Entrada: <?php echo date('H:i', strtotime($fichaje_activo->hora_entrada)); ?></span>
                        </div>
                    </div>
                    <div class="tiempo-trabajado">
                        <span class="label">Tiempo trabajado hoy:</span>
                        <span class="tiempo" id="tiempo-trabajado"><?php echo $this->calcular_tiempo_trabajado($fichaje_activo->hora_entrada); ?></span>
                    </div>
                <?php else: ?>
                    <div class="estado-info">
                        <span class="estado-icono fuera"><span class="dashicons dashicons-minus"></span></span>
                        <div>
                            <strong>Fuera</strong>
                            <span>No has fichado hoy</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Botones de fichaje -->
            <div class="fichaje-botones">
                <?php if ($fichaje_activo): ?>
                    <button id="btn-salida" class="fichaje-btn salida">
                        <span class="dashicons dashicons-migrate"></span>
                        Fichar Salida
                    </button>
                    <button id="btn-pausa" class="fichaje-btn pausa">
                        <span class="dashicons dashicons-coffee"></span>
                        Pausa
                    </button>
                <?php else: ?>
                    <button id="btn-entrada" class="fichaje-btn entrada">
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                        Fichar Entrada
                    </button>
                <?php endif; ?>
            </div>

            <!-- Resumen del día -->
            <div class="fichaje-resumen-dia">
                <h4>Resumen de hoy</h4>
                <div class="resumen-grid">
                    <div class="resumen-item">
                        <span class="label">Horas trabajadas</span>
                        <span class="valor"><?php echo $datos['horas_hoy']; ?></span>
                    </div>
                    <div class="resumen-item">
                        <span class="label">Pausas</span>
                        <span class="valor"><?php echo $datos['pausas_hoy']; ?></span>
                    </div>
                    <div class="resumen-item">
                        <span class="label">Esta semana</span>
                        <span class="valor"><?php echo $datos['horas_semana']; ?>h</span>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .fichaje-principal { max-width: 500px; margin: 0 auto; text-align: center; }
            .fichaje-reloj { padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 12px; margin-bottom: 30px; }
            .reloj-hora { font-size: 64px; font-weight: 700; font-family: monospace; }
            .reloj-fecha { font-size: 16px; opacity: 0.9; margin-top: 5px; text-transform: capitalize; }
            .fichaje-estado { padding: 20px; border-radius: 12px; margin-bottom: 30px; }
            .fichaje-estado.trabajando { background: #e8f5e9; border: 2px solid #4caf50; }
            .fichaje-estado.fuera { background: #fafafa; border: 2px solid #ddd; }
            .estado-info { display: flex; align-items: center; gap: 15px; justify-content: center; }
            .estado-icono { width: 50px; height: 50px; border-radius: 50%; background: #4caf50; color: #fff; display: flex; align-items: center; justify-content: center; }
            .estado-icono .dashicons { font-size: 24px; width: 24px; height: 24px; }
            .estado-icono.fuera { background: #9e9e9e; }
            .tiempo-trabajado { margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1); }
            .tiempo-trabajado .tiempo { font-size: 28px; font-weight: 700; color: #4caf50; display: block; margin-top: 5px; }
            .fichaje-botones { display: flex; gap: 15px; justify-content: center; margin-bottom: 30px; }
            .fichaje-btn { padding: 20px 40px; font-size: 18px; border: none; border-radius: 12px; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: transform 0.2s; }
            .fichaje-btn:hover { transform: scale(1.05); }
            .fichaje-btn.entrada { background: #4caf50; color: #fff; }
            .fichaje-btn.salida { background: #f44336; color: #fff; }
            .fichaje-btn.pausa { background: #ff9800; color: #fff; }
            .fichaje-btn .dashicons { font-size: 24px; width: 24px; height: 24px; }
            .fichaje-resumen-dia { background: #f5f5f5; padding: 20px; border-radius: 12px; }
            .resumen-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px; }
            .resumen-item { text-align: center; }
            .resumen-item .label { display: block; font-size: 12px; color: #666; }
            .resumen-item .valor { display: block; font-size: 20px; font-weight: 700; color: #333; }
        </style>

        <script>
            // Actualizar reloj
            setInterval(function() {
                const now = new Date();
                document.getElementById('reloj-hora').textContent =
                    now.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
            }, 1000);
        </script>
        <?php
    }

    private function render_historial($datos) {
        ?>
        <div class="fichaje-historial">
            <h3>Historial de fichajes</h3>

            <!-- Filtros -->
            <div class="historial-filtros">
                <form method="get">
                    <input type="hidden" name="tab" value="fichaje">
                    <input type="hidden" name="subtab" value="historial">
                    <input type="date" name="desde" value="<?php echo esc_attr($_GET['desde'] ?? date('Y-m-01')); ?>">
                    <span>hasta</span>
                    <input type="date" name="hasta" value="<?php echo esc_attr($_GET['hasta'] ?? date('Y-m-d')); ?>">
                    <button type="submit" class="flavor-btn">Filtrar</button>
                </form>
            </div>

            <?php if (empty($datos['historial'])): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-clock"></span>
                    <p>No hay fichajes en el período seleccionado</p>
                </div>
            <?php else: ?>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Pausas</th>
                            <th>Total</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['historial'] as $fichaje): ?>
                            <tr>
                                <td><?php echo date_i18n('D j M', strtotime($fichaje->fecha)); ?></td>
                                <td><?php echo $fichaje->hora_entrada ? date('H:i', strtotime($fichaje->hora_entrada)) : '-'; ?></td>
                                <td><?php echo $fichaje->hora_salida ? date('H:i', strtotime($fichaje->hora_salida)) : '-'; ?></td>
                                <td><?php echo $fichaje->minutos_pausa ? $fichaje->minutos_pausa . ' min' : '-'; ?></td>
                                <td>
                                    <strong>
                                        <?php
                                        if ($fichaje->hora_entrada && $fichaje->hora_salida) {
                                            $minutos = (strtotime($fichaje->hora_salida) - strtotime($fichaje->hora_entrada)) / 60;
                                            $minutos -= $fichaje->minutos_pausa ?? 0;
                                            echo floor($minutos / 60) . 'h ' . ($minutos % 60) . 'm';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </strong>
                                </td>
                                <td><?php echo esc_html($fichaje->notas); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_resumen($datos) {
        ?>
        <div class="fichaje-resumen">
            <h3>Resumen mensual</h3>

            <!-- KPIs -->
            <div class="flavor-kpi-grid">
                <?php
                flavor_render_component('shared/kpi-card', [
                    'label' => 'Horas Este Mes',
                    'value' => $datos['horas_mes'] . 'h',
                    'icon' => 'dashicons-clock',
                    'color' => 'blue'
                ]);
                flavor_render_component('shared/kpi-card', [
                    'label' => 'Días Trabajados',
                    'value' => $datos['dias_trabajados'],
                    'icon' => 'dashicons-calendar-alt',
                    'color' => 'green'
                ]);
                flavor_render_component('shared/kpi-card', [
                    'label' => 'Media Diaria',
                    'value' => $datos['media_diaria'] . 'h',
                    'icon' => 'dashicons-chart-line',
                    'color' => 'purple'
                ]);
                flavor_render_component('shared/kpi-card', [
                    'label' => 'Pausas Totales',
                    'value' => $datos['pausas_totales'] . ' min',
                    'icon' => 'dashicons-coffee',
                    'color' => 'yellow'
                ]);
                ?>
            </div>

            <!-- Gráfico semanal (simplificado) -->
            <div class="resumen-semanal flavor-card">
                <h4>Esta semana</h4>
                <div class="grafico-barras">
                    <?php
                    $dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                    foreach ($datos['horas_por_dia'] as $indice => $horas):
                        $porcentaje = min(100, ($horas / 10) * 100);
                    ?>
                        <div class="barra-dia">
                            <div class="barra" style="height: <?php echo $porcentaje; ?>%">
                                <span class="valor"><?php echo $horas; ?>h</span>
                            </div>
                            <span class="dia"><?php echo $dias[$indice]; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Comparativa mensual -->
            <div class="resumen-comparativa flavor-card">
                <h4>Últimos 3 meses</h4>
                <table class="flavor-table">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Horas</th>
                            <th>Días</th>
                            <th>Media</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['ultimos_meses'] as $mes): ?>
                            <tr>
                                <td><?php echo esc_html($mes['nombre']); ?></td>
                                <td><?php echo $mes['horas']; ?>h</td>
                                <td><?php echo $mes['dias']; ?></td>
                                <td><?php echo $mes['media']; ?>h</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            .grafico-barras { display: flex; align-items: flex-end; justify-content: space-around; height: 200px; padding: 20px 0; }
            .barra-dia { display: flex; flex-direction: column; align-items: center; width: 40px; }
            .barra-dia .barra { width: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 5px 5px 0 0; min-height: 5px; position: relative; }
            .barra-dia .valor { position: absolute; top: -25px; left: 50%; transform: translateX(-50%); font-size: 11px; font-weight: 600; }
            .barra-dia .dia { margin-top: 10px; font-size: 12px; color: #666; }
        </style>
        <?php
    }

    private function render_admin($datos) {
        ?>
        <div class="fichaje-admin">
            <h3>Administración de Fichajes</h3>

            <!-- Empleados fichados hoy -->
            <div class="admin-seccion">
                <h4>Estado actual de empleados</h4>
                <?php if (empty($datos['empleados_estado'])): ?>
                    <p>No hay empleados registrados</p>
                <?php else: ?>
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Estado</th>
                                <th>Entrada</th>
                                <th>Tiempo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datos['empleados_estado'] as $empleado): ?>
                                <tr>
                                    <td>
                                        <?php echo get_avatar($empleado->ID, 32); ?>
                                        <?php echo esc_html($empleado->display_name); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $empleado->estado; ?>">
                                            <?php echo ucfirst($empleado->estado); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $empleado->hora_entrada ? date('H:i', strtotime($empleado->hora_entrada)) : '-'; ?></td>
                                    <td><?php echo $empleado->tiempo_trabajado; ?></td>
                                    <td>
                                        <button class="flavor-btn flavor-btn-sm ver-historial" data-id="<?php echo $empleado->ID; ?>">
                                            Historial
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Exportar -->
            <div class="admin-seccion">
                <h4>Exportar registros</h4>
                <form method="post" class="form-exportar">
                    <div class="form-row">
                        <input type="date" name="desde" value="<?php echo date('Y-m-01'); ?>">
                        <input type="date" name="hasta" value="<?php echo date('Y-m-d'); ?>">
                        <select name="formato">
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                        <button type="submit" class="flavor-btn flavor-btn-primary">Exportar</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    private function obtener_datos_usuario() {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        // Fichaje activo (entrada sin salida hoy)
        $fichaje_activo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_fichajes
             WHERE usuario_id = %d AND DATE(fecha) = CURDATE() AND hora_salida IS NULL
             ORDER BY id DESC LIMIT 1",
            $user_id
        ));

        // Horas trabajadas hoy
        $horas_hoy = '0:00';
        $fichajes_hoy = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_fichajes WHERE usuario_id = %d AND DATE(fecha) = CURDATE()",
            $user_id
        ));
        $minutos_hoy = 0;
        foreach ($fichajes_hoy as $f) {
            if ($f->hora_entrada && $f->hora_salida) {
                $minutos_hoy += (strtotime($f->hora_salida) - strtotime($f->hora_entrada)) / 60;
                $minutos_hoy -= $f->minutos_pausa ?? 0;
            } elseif ($f->hora_entrada) {
                $minutos_hoy += (time() - strtotime($f->hora_entrada)) / 60;
            }
        }
        $horas_hoy = floor($minutos_hoy / 60) . ':' . str_pad($minutos_hoy % 60, 2, '0', STR_PAD_LEFT);

        // Pausas hoy
        $pausas_hoy = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(minutos_pausa) FROM $tabla_fichajes WHERE usuario_id = %d AND DATE(fecha) = CURDATE()",
            $user_id
        )) ?: 0;

        // Horas esta semana
        $horas_semana = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(TIMESTAMPDIFF(MINUTE, hora_entrada, COALESCE(hora_salida, NOW())) - COALESCE(minutos_pausa, 0)) / 60
             FROM $tabla_fichajes WHERE usuario_id = %d AND YEARWEEK(fecha) = YEARWEEK(CURDATE())",
            $user_id
        )) ?: 0;

        // Historial
        $desde = isset($_GET['desde']) ? sanitize_text_field($_GET['desde']) : date('Y-m-01');
        $hasta = isset($_GET['hasta']) ? sanitize_text_field($_GET['hasta']) : date('Y-m-d');
        $historial = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_fichajes WHERE usuario_id = %d AND fecha BETWEEN %s AND %s ORDER BY fecha DESC",
            $user_id, $desde, $hasta
        ));

        // Resumen mes
        $horas_mes = round($wpdb->get_var($wpdb->prepare(
            "SELECT SUM(TIMESTAMPDIFF(MINUTE, hora_entrada, COALESCE(hora_salida, hora_entrada)) - COALESCE(minutos_pausa, 0)) / 60
             FROM $tabla_fichajes WHERE usuario_id = %d AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())",
            $user_id
        )) ?: 0, 1);

        $dias_trabajados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT fecha) FROM $tabla_fichajes WHERE usuario_id = %d AND MONTH(fecha) = MONTH(CURDATE())",
            $user_id
        )) ?: 0;

        $media_diaria = $dias_trabajados > 0 ? round($horas_mes / $dias_trabajados, 1) : 0;

        $pausas_totales = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(minutos_pausa) FROM $tabla_fichajes WHERE usuario_id = %d AND MONTH(fecha) = MONTH(CURDATE())",
            $user_id
        )) ?: 0;

        // Horas por día de la semana
        $horas_por_dia = [];
        for ($i = 0; $i < 7; $i++) {
            $fecha = date('Y-m-d', strtotime("monday this week +{$i} days"));
            $horas = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(TIMESTAMPDIFF(MINUTE, hora_entrada, COALESCE(hora_salida, hora_entrada))) / 60
                 FROM $tabla_fichajes WHERE usuario_id = %d AND fecha = %s",
                $user_id, $fecha
            )) ?: 0;
            $horas_por_dia[] = round($horas, 1);
        }

        // Últimos 3 meses
        $ultimos_meses = [];
        for ($i = 0; $i < 3; $i++) {
            $mes = date('Y-m', strtotime("-{$i} months"));
            $horas = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(TIMESTAMPDIFF(MINUTE, hora_entrada, COALESCE(hora_salida, hora_entrada))) / 60
                 FROM $tabla_fichajes WHERE usuario_id = %d AND DATE_FORMAT(fecha, '%%Y-%%m') = %s",
                $user_id, $mes
            )) ?: 0;
            $dias = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT fecha) FROM $tabla_fichajes WHERE usuario_id = %d AND DATE_FORMAT(fecha, '%%Y-%%m') = %s",
                $user_id, $mes
            )) ?: 0;
            $ultimos_meses[] = [
                'nombre' => date_i18n('F Y', strtotime($mes . '-01')),
                'horas' => round($horas, 1),
                'dias' => $dias,
                'media' => $dias > 0 ? round($horas / $dias, 1) : 0
            ];
        }

        // Admin: estado empleados (si tiene permisos)
        $empleados_estado = [];
        if (current_user_can('manage_options')) {
            $empleados_estado = $wpdb->get_results(
                "SELECT u.ID, u.display_name,
                        f.hora_entrada, f.hora_salida,
                        CASE WHEN f.hora_entrada IS NOT NULL AND f.hora_salida IS NULL THEN 'trabajando'
                             WHEN f.hora_entrada IS NOT NULL AND f.hora_salida IS NOT NULL THEN 'salido'
                             ELSE 'ausente' END as estado,
                        CASE WHEN f.hora_entrada IS NOT NULL AND f.hora_salida IS NULL
                             THEN SEC_TO_TIME(TIMESTAMPDIFF(SECOND, f.hora_entrada, NOW()))
                             ELSE '-' END as tiempo_trabajado
                 FROM {$wpdb->users} u
                 LEFT JOIN $tabla_fichajes f ON u.ID = f.usuario_id AND DATE(f.fecha) = CURDATE()
                 WHERE u.ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'flavor_es_empleado' AND meta_value = '1')
                 ORDER BY estado, u.display_name"
            );
        }

        return [
            'fichaje_activo' => $fichaje_activo,
            'horas_hoy' => $horas_hoy,
            'pausas_hoy' => $pausas_hoy . ' min',
            'horas_semana' => round($horas_semana, 1),
            'historial' => $historial ?: [],
            'horas_mes' => $horas_mes,
            'dias_trabajados' => $dias_trabajados,
            'media_diaria' => $media_diaria,
            'pausas_totales' => $pausas_totales,
            'horas_por_dia' => $horas_por_dia,
            'ultimos_meses' => $ultimos_meses,
            'empleados_estado' => $empleados_estado,
        ];
    }

    private function calcular_tiempo_trabajado($hora_entrada) {
        $segundos = time() - strtotime($hora_entrada);
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segs = $segundos % 60;
        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segs);
    }
}

Flavor_Fichaje_Empleados_Dashboard_Tab::get_instance();
