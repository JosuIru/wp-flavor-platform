<?php
/**
 * Dashboard Tab para Banco de Tiempo
 *
 * Compatible con el sistema de tabs de dashboard de cliente
 *
 * @package FlavorChatIA
 * @subpackage Modules\BancoTiempo
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de usuario para Banco de Tiempo
 */
class Flavor_Banco_Tiempo_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Banco_Tiempo_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Prefijo de tablas
     * @var string
     */
    private $tabla_servicios;
    private $tabla_transacciones;
    private $tabla_reputacion;
    private $tabla_valoraciones;

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
        $this->tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
        $this->tabla_reputacion = $wpdb->prefix . 'flavor_banco_tiempo_reputacion';
        $this->tabla_valoraciones = $wpdb->prefix . 'flavor_banco_tiempo_valoraciones';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Banco_Tiempo_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
        add_action('wp_enqueue_scripts', [$this, 'encolar_assets']);
    }

    /**
     * Encola CSS/JS si estamos en el dashboard
     */
    public function encolar_assets() {
        if (!is_page() || !is_user_logged_in()) {
            return;
        }

        // Solo en páginas de dashboard/mi-portal
        $pagina_actual = get_queried_object();
        if (!$pagina_actual || !isset($pagina_actual->post_name)) {
            return;
        }

        $paginas_dashboard = ['mi-portal', 'dashboard', 'mi-cuenta'];
        if (!in_array($pagina_actual->post_name, $paginas_dashboard, true)) {
            return;
        }

        // Registrar estilos inline para el gráfico
        wp_add_inline_style('flavor-frontend', $this->get_inline_styles());
    }

    /**
     * Estilos inline para el dashboard tab
     *
     * @return string
     */
    private function get_inline_styles() {
        return '
        .bt-grafico-horas {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            height: 200px;
            padding: 1rem;
            background: var(--flavor-bg-secondary, #f8f9fa);
            border-radius: 8px;
            margin: 1rem 0;
        }
        .bt-grafico-barra {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .bt-barra-contenedor {
            width: 100%;
            height: 150px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        .bt-barra {
            width: 60%;
            min-height: 10px;
            border-radius: 4px 4px 0 0;
            transition: height 0.3s ease;
        }
        .bt-barra-dadas {
            background: linear-gradient(180deg, #10b981, #059669);
        }
        .bt-barra-recibidas {
            background: linear-gradient(180deg, #3b82f6, #2563eb);
        }
        .bt-barra-label {
            font-size: 0.75rem;
            color: var(--flavor-text-secondary, #6b7280);
            text-align: center;
        }
        .bt-barra-valor {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--flavor-text-primary, #111827);
        }
        .bt-saldo-card {
            text-align: center;
            padding: 1.5rem;
            background: var(--flavor-bg-accent, #f0fdf4);
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .bt-saldo-card.positivo {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border: 1px solid #86efac;
        }
        .bt-saldo-card.negativo {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 1px solid #fca5a5;
        }
        .bt-saldo-card.neutro {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            border: 1px solid #d1d5db;
        }
        .bt-saldo-valor {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }
        .bt-saldo-card.positivo .bt-saldo-valor {
            color: #059669;
        }
        .bt-saldo-card.negativo .bt-saldo-valor {
            color: #dc2626;
        }
        .bt-saldo-card.neutro .bt-saldo-valor {
            color: #6b7280;
        }
        .bt-intercambio-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--flavor-border, #e5e7eb);
        }
        .bt-intercambio-item:last-child {
            border-bottom: none;
        }
        .bt-intercambio-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .bt-intercambio-icon.dado {
            background: #dcfce7;
        }
        .bt-intercambio-icon.recibido {
            background: #dbeafe;
        }
        .bt-intercambio-info {
            flex: 1;
        }
        .bt-intercambio-titulo {
            font-weight: 500;
            color: var(--flavor-text-primary, #111827);
        }
        .bt-intercambio-meta {
            font-size: 0.875rem;
            color: var(--flavor-text-secondary, #6b7280);
        }
        .bt-intercambio-horas {
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
        }
        .bt-intercambio-horas.dado {
            background: #dcfce7;
            color: #059669;
        }
        .bt-intercambio-horas.recibido {
            background: #dbeafe;
            color: #2563eb;
        }
        .bt-servicio-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--flavor-bg-secondary, #f8f9fa);
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }
        .bt-servicio-card:last-child {
            margin-bottom: 0;
        }
        .bt-servicio-icono {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: var(--flavor-primary, #f59e0b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .bt-servicio-info {
            flex: 1;
        }
        .bt-servicio-titulo {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        .bt-servicio-categoria {
            font-size: 0.75rem;
            color: var(--flavor-text-secondary, #6b7280);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .bt-servicio-estado {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .bt-servicio-estado.activo {
            background: #dcfce7;
            color: #059669;
        }
        .bt-servicio-estado.pausado {
            background: #fef3c7;
            color: #d97706;
        }
        .bt-servicio-estado.completado {
            background: #dbeafe;
            color: #2563eb;
        }
        ';
    }

    /**
     * Registra los tabs del módulo en el dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        $tabs['bt-mi-balance'] = [
            'label' => __('Mi Saldo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'clock',
            'callback' => [$this, 'render_tab_mi_balance'],
            'orden' => 50,
        ];

        $tabs['bt-mis-servicios'] = [
            'label' => __('Mis Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'hammer',
            'callback' => [$this, 'render_tab_mis_servicios'],
            'orden' => 51,
        ];

        $tabs['bt-mis-intercambios'] = [
            'label' => __('Mis Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'randomize',
            'callback' => [$this, 'render_tab_mis_intercambios'],
            'orden' => 52,
        ];

        $tabs['bt-mi-reputacion'] = [
            'label' => __('Mi Reputación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'star-filled',
            'callback' => [$this, 'render_tab_mi_reputacion'],
            'orden' => 53,
        ];

        $tabs['bt-ranking'] = [
            'label' => __('Ranking', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'awards',
            'callback' => [$this, 'render_tab_ranking'],
            'orden' => 54,
        ];

        return $tabs;
    }

    /**
     * Obtiene el balance de horas del usuario
     *
     * @param int $user_id ID del usuario
     * @return array Balance con horas dadas, recibidas y saldo
     */
    private function obtener_balance_usuario($user_id) {
        global $wpdb;

        $balance = [
            'horas_dadas' => 0,
            'horas_recibidas' => 0,
            'saldo' => 0,
            'intercambios_completados' => 0,
            'intercambios_pendientes' => 0,
            'valoracion_promedio' => 0,
        ];

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_transacciones)) {
            return $balance;
        }

        // Horas dadas (cuando el usuario es receptor/proveedor del servicio)
        $horas_dadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM {$this->tabla_transacciones}
             WHERE usuario_receptor_id = %d AND estado = 'completado'",
            $user_id
        ));

        // Horas recibidas (cuando el usuario es solicitante)
        $horas_recibidas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM {$this->tabla_transacciones}
             WHERE usuario_solicitante_id = %d AND estado = 'completado'",
            $user_id
        ));

        // Intercambios completados
        $intercambios_completados = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_transacciones}
             WHERE (usuario_receptor_id = %d OR usuario_solicitante_id = %d) AND estado = 'completado'",
            $user_id, $user_id
        ));

        // Intercambios pendientes
        $intercambios_pendientes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_transacciones}
             WHERE (usuario_receptor_id = %d OR usuario_solicitante_id = %d)
             AND estado IN ('pendiente', 'aceptado', 'en_curso')",
            $user_id, $user_id
        ));

        // Valoracion promedio si existe la tabla de reputacion
        $valoracion_promedio = 0;
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_reputacion)) {
            $valoracion_promedio = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(rating_promedio, 0) FROM {$this->tabla_reputacion} WHERE usuario_id = %d",
                $user_id
            ));
        }

        $balance['horas_dadas'] = (float) $horas_dadas;
        $balance['horas_recibidas'] = (float) $horas_recibidas;
        $balance['saldo'] = $balance['horas_dadas'] - $balance['horas_recibidas'];
        $balance['intercambios_completados'] = $intercambios_completados;
        $balance['intercambios_pendientes'] = $intercambios_pendientes;
        $balance['valoracion_promedio'] = $valoracion_promedio;

        return $balance;
    }

    /**
     * Renderiza el tab de Mi Balance
     */
    public function render_tab_mi_balance() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        $balance = $this->obtener_balance_usuario($user_id);
        $clase_saldo = $balance['saldo'] > 0 ? 'positivo' : ($balance['saldo'] < 0 ? 'negativo' : 'neutro');
        $signo_saldo = $balance['saldo'] > 0 ? '+' : '';

        // Calcular altura de barras (máximo 150px)
        $max_horas = max($balance['horas_dadas'], $balance['horas_recibidas'], 1);
        $altura_dadas = ($balance['horas_dadas'] / $max_horas) * 150;
        $altura_recibidas = ($balance['horas_recibidas'] / $max_horas) * 150;
        ?>
        <div class="flavor-panel flavor-bt-balance-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-clock"></span> <?php esc_html_e('Mi Balance de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Tu saldo de horas en el banco de tiempo comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <!-- Saldo actual -->
            <div class="bt-saldo-card <?php echo esc_attr($clase_saldo); ?>">
                <div class="bt-saldo-valor"><?php echo esc_html($signo_saldo . number_format($balance['saldo'], 1)); ?>h</div>
                <div class="bt-saldo-label"><?php esc_html_e('Saldo actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>

            <!-- Grafico de barras -->
            <div class="bt-grafico-horas">
                <div class="bt-grafico-barra">
                    <div class="bt-barra-contenedor">
                        <div class="bt-barra bt-barra-dadas" style="height: <?php echo esc_attr(max($altura_dadas, 10)); ?>px;"></div>
                    </div>
                    <div class="bt-barra-valor"><?php echo esc_html(number_format($balance['horas_dadas'], 1)); ?>h</div>
                    <div class="bt-barra-label"><?php esc_html_e('Horas Dadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="bt-grafico-barra">
                    <div class="bt-barra-contenedor">
                        <div class="bt-barra bt-barra-recibidas" style="height: <?php echo esc_attr(max($altura_recibidas, 10)); ?>px;"></div>
                    </div>
                    <div class="bt-barra-valor"><?php echo esc_html(number_format($balance['horas_recibidas'], 1)); ?>h</div>
                    <div class="bt-barra-label"><?php esc_html_e('Horas Recibidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>

            <!-- KPIs adicionales -->
            <div class="flavor-panel-kpis" style="margin-top: 1.5rem;">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($balance['intercambios_completados']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Completados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-warning">
                    <span class="flavor-kpi-icon dashicons dashicons-update"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($balance['intercambios_pendientes']); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <?php if ($balance['valoracion_promedio'] > 0): ?>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-star-filled"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format($balance['valoracion_promedio'], 1); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Valoración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Acciones -->
            <div class="flavor-panel-actions" style="margin-top: 1.5rem;">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'ofrecer')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Ofrecer Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'servicios')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Buscar Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Mis Servicios
     */
    public function render_tab_mis_servicios() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;
        $mis_servicios = [];

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_servicios)) {
            $mis_servicios = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->tabla_servicios}
                 WHERE usuario_id = %d
                 ORDER BY fecha_publicacion DESC
                 LIMIT 10",
                $user_id
            ));
        }

        // Iconos por categoria
        $iconos_categoria = [
            'cuidados' => '❤️',
            'educacion' => '📚',
            'bricolaje' => '🔧',
            'tecnologia' => '💻',
            'transporte' => '🚗',
            'otros' => '✨',
        ];

        ?>
        <div class="flavor-panel flavor-bt-servicios-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-hammer"></span> <?php esc_html_e('Mis Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'ofrecer')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <?php if (empty($mis_servicios)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-clock"></span>
                    <p><?php esc_html_e('Aun no has publicado ningun servicio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p><?php esc_html_e('Comparte tus habilidades con la comunidad y gana horas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'ofrecer')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Ofrecer mi primer servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="bt-servicios-lista">
                    <?php foreach ($mis_servicios as $servicio):
                        $icono = $iconos_categoria[$servicio->categoria] ?? '✨';
                    ?>
                        <div class="bt-servicio-card">
                            <div class="bt-servicio-icono"><?php echo esc_html($icono); ?></div>
                            <div class="bt-servicio-info">
                                <div class="bt-servicio-titulo"><?php echo esc_html($servicio->titulo); ?></div>
                                <div class="bt-servicio-categoria">
                                    <?php echo esc_html(ucfirst($servicio->categoria)); ?>
                                    &bull;
                                    <?php echo esc_html(number_format((float)$servicio->horas_estimadas, 1)); ?>h
                                </div>
                            </div>
                            <div class="bt-servicio-side">
                                <span class="bt-servicio-estado <?php echo esc_attr($servicio->estado); ?>">
                                    <?php echo esc_html(ucfirst($servicio->estado)); ?>
                                </span>
                                <div class="bt-servicio-acciones">
                                    <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-sm bt-btn-editar" data-servicio-id="<?php echo esc_attr($servicio->id); ?>">
                                        <?php esc_html_e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                    <?php if ($servicio->estado === 'activo') : ?>
                                        <button type="button" class="flavor-btn flavor-btn-secondary flavor-btn-sm bt-btn-pausar" data-servicio-id="<?php echo esc_attr($servicio->id); ?>">
                                            <?php esc_html_e('Pausar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php else : ?>
                                        <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-sm bt-btn-activar" data-servicio-id="<?php echo esc_attr($servicio->id); ?>">
                                            <?php esc_html_e('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-panel-footer">
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'servicios')); ?>" class="flavor-btn flavor-btn-outline">
                        <?php esc_html_e('Ver mis servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Mis Intercambios
     */
    public function render_tab_mis_intercambios() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;
        $mis_intercambios = [];

        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_transacciones)) {
            $mis_intercambios = $wpdb->get_results($wpdb->prepare(
                "SELECT t.*, s.titulo as servicio_titulo
                 FROM {$this->tabla_transacciones} t
                 LEFT JOIN {$this->tabla_servicios} s ON t.servicio_id = s.id
                 WHERE t.usuario_receptor_id = %d OR t.usuario_solicitante_id = %d
                 ORDER BY t.fecha_solicitud DESC
                 LIMIT 15",
                $user_id, $user_id
            ));
        }

        $estados_colores = [
            'pendiente' => 'warning',
            'aceptado' => 'info',
            'en_curso' => 'primary',
            'completado' => 'success',
            'cancelado' => 'secondary',
            'rechazado' => 'danger',
        ];

        ?>
        <div class="flavor-panel flavor-bt-intercambios-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-randomize"></span> <?php esc_html_e('Mis Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Historial de servicios dados y recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <?php if (empty($mis_intercambios)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-randomize"></span>
                    <p><?php esc_html_e('No tienes intercambios registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p><?php esc_html_e('Busca servicios que necesites o publica los tuyos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'servicios')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="bt-intercambios-lista">
                    <?php foreach ($mis_intercambios as $intercambio):
                        $es_proveedor = ((int)$intercambio->usuario_receptor_id === $user_id);
                        $tipo_intercambio = $es_proveedor ? 'dado' : 'recibido';
                        $icono_tipo = $es_proveedor ? '➡️' : '⬅️';

                        // Obtener nombre del otro usuario
                        $otro_usuario_id = $es_proveedor ? $intercambio->usuario_solicitante_id : $intercambio->usuario_receptor_id;
                        $otro_usuario = get_userdata($otro_usuario_id);
                        $nombre_otro = $otro_usuario ? $otro_usuario->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    ?>
                        <div class="bt-intercambio-item">
                            <div class="bt-intercambio-icon <?php echo esc_attr($tipo_intercambio); ?>">
                                <?php echo esc_html($icono_tipo); ?>
                            </div>
                            <div class="bt-intercambio-info">
                                <div class="bt-intercambio-titulo">
                                    <?php echo esc_html($intercambio->servicio_titulo ?? __('Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </div>
                                <div class="bt-intercambio-meta">
                                    <?php if ($es_proveedor): ?>
                                        <?php printf(esc_html__('Para %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($nombre_otro)); ?>
                                    <?php else: ?>
                                        <?php printf(esc_html__('De %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($nombre_otro)); ?>
                                    <?php endif; ?>
                                    &bull;
                                    <?php echo esc_html(date_i18n('d M Y', strtotime($intercambio->fecha_solicitud))); ?>
                                    &bull;
                                    <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_colores[$intercambio->estado] ?? 'secondary'); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $intercambio->estado))); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="bt-intercambio-side">
                                <span class="bt-intercambio-horas <?php echo esc_attr($tipo_intercambio); ?>">
                                    <?php echo esc_html(($es_proveedor ? '+' : '-') . number_format((float)$intercambio->horas, 1)); ?>h
                                </span>
                                <div class="bt-intercambio-acciones">
                                    <?php if ($intercambio->estado === 'pendiente' && $es_proveedor) : ?>
                                        <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-sm bt-btn-aceptar-intercambio" data-intercambio-id="<?php echo esc_attr($intercambio->id); ?>">
                                            <?php esc_html_e('Aceptar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                        <button type="button" class="flavor-btn flavor-btn-danger flavor-btn-sm bt-btn-rechazar-intercambio" data-intercambio-id="<?php echo esc_attr($intercambio->id); ?>">
                                            <?php esc_html_e('Rechazar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php elseif ($intercambio->estado === 'pendiente' && !$es_proveedor) : ?>
                                        <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-sm bt-btn-cancelar-intercambio" data-intercambio-id="<?php echo esc_attr($intercambio->id); ?>">
                                            <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php elseif (in_array($intercambio->estado, ['aceptado', 'en_curso'], true)) : ?>
                                        <button type="button" class="flavor-btn flavor-btn-success flavor-btn-sm bt-btn-completar-intercambio" data-intercambio-id="<?php echo esc_attr($intercambio->id); ?>">
                                            <?php esc_html_e('Completar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-panel-footer">
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('banco_tiempo', 'intercambios')); ?>" class="flavor-btn flavor-btn-outline">
                        <?php esc_html_e('Ver historial completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de Mi Reputación
     */
    public function render_tab_mi_reputacion() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión para ver este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        global $wpdb;
        $reputacion = null;
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_reputacion)) {
            $reputacion = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$this->tabla_reputacion} WHERE usuario_id = %d LIMIT 1", $user_id),
                ARRAY_A
            );
        }

        if (!$reputacion) {
            $user = wp_get_current_user();
            $reputacion = [
                'usuario_id' => $user_id,
                'nombre' => $user ? $user->display_name : '',
                'avatar' => get_avatar_url($user_id),
                'nivel' => 1,
                'puntos_confianza' => 0,
                'rating_promedio' => 0,
                'total_intercambios_completados' => 0,
                'total_horas_dadas' => 0,
                'total_horas_recibidas' => 0,
                'rating_puntualidad' => 0,
                'rating_calidad' => 0,
                'rating_comunicacion' => 0,
                'badges' => '',
                'estado_verificacion' => 'pendiente',
                'fecha_primer_intercambio' => '',
            ];
        } else {
            $reputacion['avatar'] = get_avatar_url($user_id);
            $reputacion['nombre'] = $reputacion['nombre'] ?? wp_get_current_user()->display_name;
        }

        $badges_info = [];
        if (!empty($reputacion['badges'])) {
            $badges = is_array($reputacion['badges']) ? $reputacion['badges'] : json_decode($reputacion['badges'], true);
            if (is_array($badges)) {
                foreach ($badges as $badge_id) {
                    $badges_info[] = [
                        'nombre' => ucwords(str_replace('_', ' ', (string) $badge_id)),
                        'descripcion' => (string) $badge_id,
                        'icono' => 'awards',
                    ];
                }
            }
        }

        $template = dirname(__FILE__) . '/templates/mi-reputacion.php';
        if (!file_exists($template)) {
            $template = dirname(__FILE__) . '/../banco-tiempo/templates/mi-reputacion.php';
        }

        if (file_exists($template)) {
            include $template;
            return;
        }

        echo '<p>' . esc_html__('La vista de reputación no está disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Renderiza el tab de Ranking
     */
    public function render_tab_ranking() {
        echo do_shortcode('[banco_tiempo_ranking limite="10"]');
    }
}
