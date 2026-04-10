<?php
/**
 * Dashboard Tab para Reservas
 *
 * Integra pestanas de reservas en el dashboard del usuario.
 * - reservas-activas: Reservas activas del usuario
 * - reservas-proximas: Proximas reservas programadas
 * - reservas-historial: Historial de reservas pasadas
 *
 * @package FlavorPlatform
 * @subpackage Modules\Reservas
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona las pestanas de reservas en el dashboard del cliente
 */
class Flavor_Reservas_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Reservas_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Nombre de la tabla de reservas
     * @var string
     */
    private $nombre_tabla_reservas;

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Reservas_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Registra las pestanas en el dashboard del usuario
     *
     * @param array $tabs Pestanas existentes
     * @return array Pestanas modificadas
     */
    public function registrar_tabs($tabs) {
        $tabs['reservas-activas'] = [
            'label'    => __('Reservas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => 'calendar-alt',
            'callback' => [$this, 'render_tab_reservas_activas'],
            'orden'    => 50,
        ];

        $tabs['reservas-proximas'] = [
            'label'    => __('Proximas Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => 'clock',
            'callback' => [$this, 'render_tab_reservas_proximas'],
            'orden'    => 51,
        ];

        $tabs['reservas-historial'] = [
            'label'    => __('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => 'backup',
            'callback' => [$this, 'render_tab_historial'],
            'orden'    => 52,
        ];

        return $tabs;
    }

    /**
     * Verifica si la tabla de reservas existe
     *
     * @return bool
     */
    private function tabla_existe() {
        return Flavor_Platform_Helpers::tabla_existe($this->nombre_tabla_reservas);
    }

    /**
     * Obtiene el ID del usuario actual
     *
     * @return int|false ID del usuario o false si no hay sesion
     */
    private function obtener_usuario_actual() {
        $identificador_usuario = get_current_user_id();
        return $identificador_usuario > 0 ? $identificador_usuario : false;
    }

    /**
     * Mapeo de colores por estado de reserva
     *
     * @return array
     */
    private function obtener_colores_estados() {
        return [
            'pendiente'  => 'warning',
            'confirmada' => 'success',
            'cancelada'  => 'danger',
            'completada' => 'secondary',
        ];
    }

    /**
     * Obtiene las reservas activas del usuario (pendientes y confirmadas)
     *
     * @param int $identificador_usuario ID del usuario
     * @param int $limite_resultados Limite de resultados
     * @return array
     */
    private function obtener_reservas_activas($identificador_usuario, $limite_resultados = 10) {
        global $wpdb;

        if (!$this->tabla_existe()) {
            return [];
        }

        $fecha_actual = current_time('Y-m-d');

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->nombre_tabla_reservas}
             WHERE user_id = %d
               AND estado IN ('pendiente', 'confirmada')
               AND fecha_reserva >= %s
             ORDER BY fecha_reserva ASC, hora_inicio ASC
             LIMIT %d",
            $identificador_usuario,
            $fecha_actual,
            $limite_resultados
        ));
    }

    /**
     * Obtiene las proximas reservas del usuario (solo confirmadas)
     *
     * @param int $identificador_usuario ID del usuario
     * @param int $dias_adelante Dias hacia adelante
     * @param int $limite_resultados Limite de resultados
     * @return array
     */
    private function obtener_reservas_proximas($identificador_usuario, $dias_adelante = 30, $limite_resultados = 10) {
        global $wpdb;

        if (!$this->tabla_existe()) {
            return [];
        }

        $fecha_actual = current_time('Y-m-d');
        $fecha_limite = date('Y-m-d', strtotime("+{$dias_adelante} days"));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->nombre_tabla_reservas}
             WHERE user_id = %d
               AND estado = 'confirmada'
               AND fecha_reserva BETWEEN %s AND %s
             ORDER BY fecha_reserva ASC, hora_inicio ASC
             LIMIT %d",
            $identificador_usuario,
            $fecha_actual,
            $fecha_limite,
            $limite_resultados
        ));
    }

    /**
     * Obtiene el historial de reservas pasadas del usuario
     *
     * @param int $identificador_usuario ID del usuario
     * @param int $limite_resultados Limite de resultados
     * @return array
     */
    private function obtener_historial_reservas($identificador_usuario, $limite_resultados = 20) {
        global $wpdb;

        if (!$this->tabla_existe()) {
            return [];
        }

        $fecha_actual = current_time('Y-m-d');

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->nombre_tabla_reservas}
             WHERE user_id = %d
               AND (fecha_reserva < %s OR estado IN ('cancelada', 'completada'))
             ORDER BY fecha_reserva DESC, hora_inicio DESC
             LIMIT %d",
            $identificador_usuario,
            $fecha_actual,
            $limite_resultados
        ));
    }

    /**
     * Obtiene estadisticas de reservas del usuario
     *
     * @param int $identificador_usuario ID del usuario
     * @return array
     */
    private function obtener_estadisticas_usuario($identificador_usuario) {
        global $wpdb;

        if (!$this->tabla_existe()) {
            return [
                'total_reservas'     => 0,
                'reservas_activas'   => 0,
                'reservas_completadas' => 0,
                'reservas_canceladas'  => 0,
            ];
        }

        $fecha_actual = current_time('Y-m-d');

        $total_reservas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->nombre_tabla_reservas} WHERE user_id = %d",
            $identificador_usuario
        ));

        $reservas_activas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->nombre_tabla_reservas}
             WHERE user_id = %d
               AND estado IN ('pendiente', 'confirmada')
               AND fecha_reserva >= %s",
            $identificador_usuario,
            $fecha_actual
        ));

        $reservas_completadas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->nombre_tabla_reservas}
             WHERE user_id = %d AND estado = 'completada'",
            $identificador_usuario
        ));

        $reservas_canceladas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->nombre_tabla_reservas}
             WHERE user_id = %d AND estado = 'cancelada'",
            $identificador_usuario
        ));

        return [
            'total_reservas'       => $total_reservas,
            'reservas_activas'     => $reservas_activas,
            'reservas_completadas' => $reservas_completadas,
            'reservas_canceladas'  => $reservas_canceladas,
        ];
    }

    /**
     * Obtiene las reservas del usuario para un mes especifico (para el mini calendario)
     *
     * @param int $identificador_usuario ID del usuario
     * @param int $mes Mes (1-12)
     * @param int $anio Ano
     * @return array Dias con reservas
     */
    private function obtener_reservas_mes($identificador_usuario, $mes = null, $anio = null) {
        global $wpdb;

        if (!$this->tabla_existe()) {
            return [];
        }

        $mes = $mes ?: (int) current_time('n');
        $anio = $anio ?: (int) current_time('Y');

        $fecha_inicio_mes = sprintf('%04d-%02d-01', $anio, $mes);
        $fecha_fin_mes = date('Y-m-t', strtotime($fecha_inicio_mes));

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha_reserva, estado, COUNT(*) as cantidad
             FROM {$this->nombre_tabla_reservas}
             WHERE user_id = %d
               AND fecha_reserva BETWEEN %s AND %s
               AND estado IN ('pendiente', 'confirmada')
             GROUP BY fecha_reserva, estado
             ORDER BY fecha_reserva ASC",
            $identificador_usuario,
            $fecha_inicio_mes,
            $fecha_fin_mes
        ));

        $dias_con_reservas = [];
        foreach ($reservas as $reserva) {
            $dia = (int) date('j', strtotime($reserva->fecha_reserva));
            if (!isset($dias_con_reservas[$dia])) {
                $dias_con_reservas[$dia] = [
                    'fecha'    => $reserva->fecha_reserva,
                    'estados'  => [],
                    'cantidad' => 0,
                ];
            }
            $dias_con_reservas[$dia]['estados'][] = $reserva->estado;
            $dias_con_reservas[$dia]['cantidad'] += $reserva->cantidad;
        }

        return $dias_con_reservas;
    }

    /**
     * Renderiza un mini calendario con las reservas del usuario
     *
     * @param int $identificador_usuario ID del usuario
     * @param int $mes Mes (opcional)
     * @param int $anio Ano (opcional)
     */
    private function renderizar_mini_calendario($identificador_usuario, $mes = null, $anio = null) {
        $mes = $mes ?: (int) current_time('n');
        $anio = $anio ?: (int) current_time('Y');

        $dias_con_reservas = $this->obtener_reservas_mes($identificador_usuario, $mes, $anio);

        // Calcular datos del calendario
        $primer_dia_mes = mktime(0, 0, 0, $mes, 1, $anio);
        $numero_dias_mes = (int) date('t', $primer_dia_mes);
        $dia_semana_inicio = (int) date('N', $primer_dia_mes); // 1 = Lunes, 7 = Domingo
        $dia_actual = (int) current_time('j');
        $mes_actual = (int) current_time('n');
        $anio_actual = (int) current_time('Y');

        $nombre_mes = date_i18n('F Y', $primer_dia_mes);

        // Meses anterior y siguiente
        $mes_anterior = $mes - 1;
        $anio_mes_anterior = $anio;
        if ($mes_anterior < 1) {
            $mes_anterior = 12;
            $anio_mes_anterior--;
        }

        $mes_siguiente = $mes + 1;
        $anio_mes_siguiente = $anio;
        if ($mes_siguiente > 12) {
            $mes_siguiente = 1;
            $anio_mes_siguiente++;
        }

        $dias_semana = [
            __('L', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('M', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('X', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('J', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('V', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('S', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('D', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        ?>
        <div class="flavor-mini-calendario">
            <div class="flavor-calendario-header">
                <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-outline" data-calendario-nav="prev" data-mes="<?php echo esc_attr($mes_anterior); ?>" data-anio="<?php echo esc_attr($anio_mes_anterior); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <span class="flavor-calendario-titulo"><?php echo esc_html(ucfirst($nombre_mes)); ?></span>
                <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-outline" data-calendario-nav="next" data-mes="<?php echo esc_attr($mes_siguiente); ?>" data-anio="<?php echo esc_attr($anio_mes_siguiente); ?>">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>

            <div class="flavor-calendario-grid">
                <div class="flavor-calendario-dias-semana">
                    <?php foreach ($dias_semana as $dia_semana_nombre): ?>
                        <span><?php echo esc_html($dia_semana_nombre); ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-calendario-dias">
                    <?php
                    // Celdas vacias antes del primer dia
                    for ($i = 1; $i < $dia_semana_inicio; $i++) {
                        echo '<span class="flavor-calendario-dia flavor-calendario-dia-vacio"></span>';
                    }

                    // Dias del mes
                    for ($dia = 1; $dia <= $numero_dias_mes; $dia++) {
                        $clases_css = ['flavor-calendario-dia'];
                        $tiene_reserva = isset($dias_con_reservas[$dia]);
                        $es_hoy = ($dia === $dia_actual && $mes === $mes_actual && $anio === $anio_actual);

                        if ($es_hoy) {
                            $clases_css[] = 'flavor-calendario-dia-hoy';
                        }

                        if ($tiene_reserva) {
                            $clases_css[] = 'flavor-calendario-dia-reserva';
                            $info_reserva = $dias_con_reservas[$dia];
                            if (in_array('confirmada', $info_reserva['estados'])) {
                                $clases_css[] = 'flavor-calendario-dia-confirmada';
                            } else {
                                $clases_css[] = 'flavor-calendario-dia-pendiente';
                            }
                        }

                        $titulo_dia = '';
                        if ($tiene_reserva) {
                            $titulo_dia = sprintf(
                                _n('%d reserva', '%d reservas', $dias_con_reservas[$dia]['cantidad'], FLAVOR_PLATFORM_TEXT_DOMAIN),
                                $dias_con_reservas[$dia]['cantidad']
                            );
                        }

                        printf(
                            '<span class="%s" title="%s" data-fecha="%s">%d%s</span>',
                            esc_attr(implode(' ', $clases_css)),
                            esc_attr($titulo_dia),
                            esc_attr(sprintf('%04d-%02d-%02d', $anio, $mes, $dia)),
                            $dia,
                            $tiene_reserva ? '<span class="flavor-calendario-indicador"></span>' : ''
                        );
                    }

                    // Celdas vacias despues del ultimo dia
                    $dias_restantes = (7 - (($dia_semana_inicio - 1 + $numero_dias_mes) % 7)) % 7;
                    for ($i = 0; $i < $dias_restantes; $i++) {
                        echo '<span class="flavor-calendario-dia flavor-calendario-dia-vacio"></span>';
                    }
                    ?>
                </div>
            </div>

            <div class="flavor-calendario-leyenda">
                <span class="flavor-leyenda-item">
                    <span class="flavor-leyenda-color flavor-leyenda-confirmada"></span>
                    <?php esc_html_e('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
                <span class="flavor-leyenda-item">
                    <span class="flavor-leyenda-color flavor-leyenda-pendiente"></span>
                    <?php esc_html_e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
            </div>
        </div>

        <style>
            .flavor-mini-calendario {
                background: var(--flavor-bg-card, #fff);
                border-radius: 12px;
                padding: 16px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            }
            .flavor-calendario-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }
            .flavor-calendario-titulo {
                font-weight: 600;
                font-size: 1rem;
                color: var(--flavor-text-primary, #1a1a1a);
            }
            .flavor-calendario-grid {
                width: 100%;
            }
            .flavor-calendario-dias-semana {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 4px;
                margin-bottom: 8px;
            }
            .flavor-calendario-dias-semana span {
                text-align: center;
                font-size: 0.75rem;
                font-weight: 600;
                color: var(--flavor-text-muted, #6b7280);
                padding: 4px 0;
            }
            .flavor-calendario-dias {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 4px;
            }
            .flavor-calendario-dia {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                aspect-ratio: 1;
                font-size: 0.875rem;
                border-radius: 8px;
                cursor: default;
                transition: all 0.2s ease;
            }
            .flavor-calendario-dia-vacio {
                background: transparent;
            }
            .flavor-calendario-dia-hoy {
                background: var(--flavor-primary, #3b82f6);
                color: #fff;
                font-weight: 600;
            }
            .flavor-calendario-dia-reserva {
                cursor: pointer;
            }
            .flavor-calendario-dia-confirmada {
                background: var(--flavor-success-light, #dcfce7);
                color: var(--flavor-success, #16a34a);
            }
            .flavor-calendario-dia-pendiente {
                background: var(--flavor-warning-light, #fef3c7);
                color: var(--flavor-warning, #d97706);
            }
            .flavor-calendario-dia-hoy.flavor-calendario-dia-reserva {
                background: var(--flavor-primary, #3b82f6);
                color: #fff;
            }
            .flavor-calendario-indicador {
                position: absolute;
                bottom: 2px;
                left: 50%;
                transform: translateX(-50%);
                width: 4px;
                height: 4px;
                border-radius: 50%;
                background: currentColor;
            }
            .flavor-calendario-leyenda {
                display: flex;
                gap: 16px;
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px solid var(--flavor-border, #e5e7eb);
            }
            .flavor-leyenda-item {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 0.75rem;
                color: var(--flavor-text-muted, #6b7280);
            }
            .flavor-leyenda-color {
                width: 12px;
                height: 12px;
                border-radius: 4px;
            }
            .flavor-leyenda-confirmada {
                background: var(--flavor-success-light, #dcfce7);
                border: 2px solid var(--flavor-success, #16a34a);
            }
            .flavor-leyenda-pendiente {
                background: var(--flavor-warning-light, #fef3c7);
                border: 2px solid var(--flavor-warning, #d97706);
            }
        </style>
        <?php
    }

    /**
     * Renderiza una fila de reserva en la tabla
     *
     * @param object $reserva Objeto de reserva
     * @param bool $mostrar_acciones Si mostrar acciones de cancelar
     */
    private function renderizar_fila_reserva($reserva, $mostrar_acciones = true) {
        $colores_estados = $this->obtener_colores_estados();
        $fecha_actual = current_time('timestamp');
        $fecha_reserva_timestamp = strtotime($reserva->fecha_reserva . ' ' . $reserva->hora_inicio);
        $es_futura = $fecha_reserva_timestamp > $fecha_actual;
        $puede_cancelar = $mostrar_acciones && $es_futura && in_array($reserva->estado, ['pendiente', 'confirmada']);

        ?>
        <tr>
            <td>
                <div class="flavor-reserva-info">
                    <span class="flavor-reserva-tipo"><?php echo esc_html($this->formatear_tipo_servicio($reserva->tipo_servicio)); ?></span>
                    <?php if (!empty($reserva->notas)): ?>
                        <span class="flavor-text-muted flavor-text-truncate" title="<?php echo esc_attr($reserva->notas); ?>">
                            <?php echo esc_html(wp_trim_words($reserva->notas, 5)); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <span class="flavor-fecha">
                    <?php echo esc_html(date_i18n('d M Y', strtotime($reserva->fecha_reserva))); ?>
                </span>
            </td>
            <td>
                <span class="flavor-horario">
                    <?php echo esc_html(substr($reserva->hora_inicio, 0, 5)); ?>
                    -
                    <?php echo esc_html(substr($reserva->hora_fin, 0, 5)); ?>
                </span>
            </td>
            <td>
                <span class="flavor-badge flavor-badge-<?php echo esc_attr($colores_estados[$reserva->estado] ?? 'secondary'); ?>">
                    <?php echo esc_html(ucfirst($reserva->estado)); ?>
                </span>
            </td>
            <td>
                <?php echo esc_html($reserva->num_personas); ?> <span class="dashicons dashicons-admin-users" style="font-size: 14px; width: 14px; height: 14px;"></span>
            </td>
            <td>
                <?php if ($puede_cancelar): ?>
                    <button type="button"
                            class="flavor-btn flavor-btn-sm flavor-btn-danger flavor-btn-outline"
                            data-cancelar-reserva="<?php echo esc_attr($reserva->id); ?>"
                            data-nonce="<?php echo esc_attr(wp_create_nonce('cancelar_reserva_' . $reserva->id)); ?>">
                        <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                <?php else: ?>
                    <span class="flavor-text-muted">-</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Formatea el tipo de servicio para mostrar
     *
     * @param string $tipo_servicio Clave del tipo de servicio
     * @return string
     */
    private function formatear_tipo_servicio($tipo_servicio) {
        $tipos = [
            'mesa_restaurante'  => __('Mesa Restaurante', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'espacio_coworking' => __('Espacio Coworking', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'clase_deportiva'   => __('Clase Deportiva', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $tipos[$tipo_servicio] ?? ucfirst(str_replace('_', ' ', $tipo_servicio));
    }

    /**
     * Renderiza el tab de Reservas Activas
     */
    public function render_tab_reservas_activas() {
        $identificador_usuario = $this->obtener_usuario_actual();

        if (!$identificador_usuario) {
            echo '<div class="flavor-alert flavor-alert-warning">';
            echo '<span class="dashicons dashicons-warning"></span> ';
            esc_html_e('Debes iniciar sesion para ver tus reservas.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            echo '</div>';
            return;
        }

        $estadisticas = $this->obtener_estadisticas_usuario($identificador_usuario);
        $reservas_activas = $this->obtener_reservas_activas($identificador_usuario);

        ?>
        <div class="flavor-panel flavor-reservas-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Mis Reservas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="flavor-panel-subtitle">
                    <?php esc_html_e('Gestiona tus reservas pendientes y confirmadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- KPIs -->
            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-calendar-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['reservas_activas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['reservas_completadas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Completadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-secondary">
                    <span class="flavor-kpi-icon dashicons dashicons-chart-bar"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['total_reservas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <!-- Layout de dos columnas: Tabla + Calendario -->
            <div class="flavor-reservas-layout">
                <div class="flavor-reservas-tabla-wrapper">
                    <?php if (empty($reservas_activas)): ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <p><?php esc_html_e('No tienes reservas activas en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <a href="<?php echo esc_url(add_query_arg('tab', 'nueva-reserva', Flavor_Platform_Helpers::get_action_url('reservas', ''))); ?>" class="flavor-btn flavor-btn-primary">
                                <?php esc_html_e('Hacer una reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="flavor-table-responsive">
                            <table class="flavor-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                        <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                        <th><?php esc_html_e('Horario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                        <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                        <th><?php esc_html_e('Personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                        <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservas_activas as $reserva): ?>
                                        <?php $this->renderizar_fila_reserva($reserva, true); ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flavor-reservas-calendario-wrapper">
                    <?php $this->renderizar_mini_calendario($identificador_usuario); ?>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(add_query_arg('tab', 'nueva-reserva', Flavor_Platform_Helpers::get_action_url('reservas', ''))); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nueva Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'mis-reservas', Flavor_Platform_Helpers::get_action_url('reservas', ''))); ?>" class="flavor-btn flavor-btn-secondary">
                    <?php esc_html_e('Ver todas mis reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>

        <style>
            .flavor-reservas-layout {
                display: grid;
                grid-template-columns: 1fr 300px;
                gap: 24px;
                margin: 20px 0;
            }
            @media (max-width: 992px) {
                .flavor-reservas-layout {
                    grid-template-columns: 1fr;
                }
            }
            .flavor-reservas-tabla-wrapper {
                min-width: 0;
            }
            .flavor-reservas-calendario-wrapper {
                min-width: 280px;
            }
            .flavor-reserva-info {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .flavor-reserva-tipo {
                font-weight: 500;
            }
        </style>
        <?php
    }

    /**
     * Renderiza el tab de Proximas Reservas
     */
    public function render_tab_reservas_proximas() {
        $identificador_usuario = $this->obtener_usuario_actual();

        if (!$identificador_usuario) {
            echo '<div class="flavor-alert flavor-alert-warning">';
            echo '<span class="dashicons dashicons-warning"></span> ';
            esc_html_e('Debes iniciar sesion para ver tus reservas.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            echo '</div>';
            return;
        }

        $reservas_proximas = $this->obtener_reservas_proximas($identificador_usuario, 30, 15);

        // Agrupar por fecha
        $reservas_por_fecha = [];
        foreach ($reservas_proximas as $reserva) {
            $fecha = $reserva->fecha_reserva;
            if (!isset($reservas_por_fecha[$fecha])) {
                $reservas_por_fecha[$fecha] = [];
            }
            $reservas_por_fecha[$fecha][] = $reserva;
        }

        ?>
        <div class="flavor-panel flavor-reservas-proximas-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-clock"></span>
                    <?php esc_html_e('Proximas Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="flavor-panel-subtitle">
                    <?php esc_html_e('Tus reservas confirmadas para los proximos 30 dias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <?php if (empty($reservas_proximas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-clock"></span>
                    <p><?php esc_html_e('No tienes reservas confirmadas para los proximos dias.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'nueva-reserva', Flavor_Platform_Helpers::get_action_url('reservas', ''))); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Hacer una reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-timeline-reservas">
                    <?php foreach ($reservas_por_fecha as $fecha => $reservas_del_dia): ?>
                        <div class="flavor-timeline-dia">
                            <div class="flavor-timeline-fecha">
                                <span class="flavor-timeline-dia-semana">
                                    <?php echo esc_html(date_i18n('l', strtotime($fecha))); ?>
                                </span>
                                <span class="flavor-timeline-dia-numero">
                                    <?php echo esc_html(date_i18n('j M', strtotime($fecha))); ?>
                                </span>
                            </div>
                            <div class="flavor-timeline-reservas-lista">
                                <?php foreach ($reservas_del_dia as $reserva): ?>
                                    <div class="flavor-timeline-reserva-card">
                                        <div class="flavor-timeline-hora">
                                            <?php echo esc_html(substr($reserva->hora_inicio, 0, 5)); ?>
                                            <span>-</span>
                                            <?php echo esc_html(substr($reserva->hora_fin, 0, 5)); ?>
                                        </div>
                                        <div class="flavor-timeline-detalle">
                                            <strong><?php echo esc_html($this->formatear_tipo_servicio($reserva->tipo_servicio)); ?></strong>
                                            <span class="flavor-text-muted">
                                                <?php echo esc_html($reserva->num_personas); ?> <?php esc_html_e('personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </span>
                                        </div>
                                        <div class="flavor-timeline-acciones">
                                            <?php if (strtotime($reserva->fecha_reserva . ' ' . $reserva->hora_inicio) > current_time('timestamp')): ?>
                                                <button type="button"
                                                        class="flavor-btn flavor-btn-sm flavor-btn-danger flavor-btn-outline"
                                                        data-cancelar-reserva="<?php echo esc_attr($reserva->id); ?>"
                                                        data-nonce="<?php echo esc_attr(wp_create_nonce('cancelar_reserva_' . $reserva->id)); ?>">
                                                    <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(add_query_arg('tab', 'nueva-reserva', Flavor_Platform_Helpers::get_action_url('reservas', ''))); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nueva Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>

        <style>
            .flavor-timeline-reservas {
                display: flex;
                flex-direction: column;
                gap: 20px;
                margin: 20px 0;
            }
            .flavor-timeline-dia {
                display: grid;
                grid-template-columns: 100px 1fr;
                gap: 16px;
            }
            @media (max-width: 576px) {
                .flavor-timeline-dia {
                    grid-template-columns: 1fr;
                }
            }
            .flavor-timeline-fecha {
                display: flex;
                flex-direction: column;
                padding: 12px;
                background: var(--flavor-bg-secondary, #f9fafb);
                border-radius: 8px;
                text-align: center;
            }
            .flavor-timeline-dia-semana {
                font-size: 0.75rem;
                color: var(--flavor-text-muted, #6b7280);
                text-transform: capitalize;
            }
            .flavor-timeline-dia-numero {
                font-size: 1rem;
                font-weight: 600;
                color: var(--flavor-text-primary, #1a1a1a);
            }
            .flavor-timeline-reservas-lista {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .flavor-timeline-reserva-card {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 12px 16px;
                background: var(--flavor-bg-card, #fff);
                border: 1px solid var(--flavor-border, #e5e7eb);
                border-radius: 8px;
                border-left: 4px solid var(--flavor-success, #16a34a);
            }
            .flavor-timeline-hora {
                display: flex;
                flex-direction: column;
                align-items: center;
                font-size: 0.875rem;
                font-weight: 500;
                color: var(--flavor-primary, #3b82f6);
                min-width: 60px;
            }
            .flavor-timeline-hora span {
                font-size: 0.75rem;
                color: var(--flavor-text-muted, #6b7280);
            }
            .flavor-timeline-detalle {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .flavor-timeline-acciones {
                flex-shrink: 0;
            }
        </style>
        <?php
    }

    /**
     * Renderiza el tab de Historial de Reservas
     */
    public function render_tab_historial() {
        $identificador_usuario = $this->obtener_usuario_actual();

        if (!$identificador_usuario) {
            echo '<div class="flavor-alert flavor-alert-warning">';
            echo '<span class="dashicons dashicons-warning"></span> ';
            esc_html_e('Debes iniciar sesion para ver tu historial.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            echo '</div>';
            return;
        }

        $historial_reservas = $this->obtener_historial_reservas($identificador_usuario, 25);
        $estadisticas = $this->obtener_estadisticas_usuario($identificador_usuario);

        ?>
        <div class="flavor-panel flavor-reservas-historial-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-backup"></span>
                    <?php esc_html_e('Historial de Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="flavor-panel-subtitle">
                    <?php esc_html_e('Consulta tu historial de reservas pasadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- Estadisticas del historial -->
            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['reservas_completadas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Completadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-danger">
                    <span class="flavor-kpi-icon dashicons dashicons-dismiss"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['reservas_canceladas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Canceladas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-chart-bar"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas['total_reservas']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Total historico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($historial_reservas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-backup"></span>
                    <p><?php esc_html_e('No tienes reservas en tu historial.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-table-responsive" style="margin: 20px 0;">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Horario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial_reservas as $reserva): ?>
                                <?php $this->renderizar_fila_reserva($reserva, false); ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(add_query_arg('tab', 'nueva-reserva', Flavor_Platform_Helpers::get_action_url('reservas', ''))); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nueva Reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
