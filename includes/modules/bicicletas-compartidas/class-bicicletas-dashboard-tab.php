<?php
/**
 * Dashboard Tabs para el módulo de Bicicletas Compartidas
 *
 * Registra tabs en el dashboard de usuario frontend "Mi Cuenta"
 * para mostrar historial de viajes, cuenta y estadísticas.
 *
 * @package FlavorChatIA
 * @subpackage Modules/BicicletasCompartidas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de bicicletas
 */
class Flavor_Bicicletas_Dashboard_Tab {

    /**
     * Instancia singleton
     *
     * @var Flavor_Bicicletas_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * ID del usuario actual
     *
     * @var int
     */
    private $usuario_id;

    /**
     * Nombres de las tablas de la base de datos
     *
     * @var array
     */
    private $tablas = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Bicicletas_Dashboard_Tab
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;

        $this->tablas = [
            'bicicletas'    => $wpdb->prefix . 'flavor_bicicletas',
            'prestamos'     => $wpdb->prefix . 'flavor_bicicletas_prestamos',
            'estaciones'    => $wpdb->prefix . 'flavor_bicicletas_estaciones',
            'mantenimiento' => $wpdb->prefix . 'flavor_bicicletas_mantenimiento',
        ];

        $this->init();
    }

    /**
     * Inicializa hooks y filtros
     */
    private function init() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 20);
        add_action('wp_enqueue_scripts', [$this, 'encolar_assets']);
        add_action('wp_ajax_flavor_bicicletas_cargar_mas_viajes', [$this, 'ajax_cargar_mas_viajes']);
    }

    /**
     * Registra los tabs de bicicletas en el dashboard de usuario
     *
     * @param array $tabs_existentes Tabs actuales del dashboard
     * @return array Tabs modificados con los de bicicletas
     */
    public function registrar_tabs($tabs_existentes) {
        // Verificar que el módulo esté activo
        if (!$this->modulo_esta_activo()) {
            return $tabs_existentes;
        }

        $tabs_existentes['bicicletas-mis-viajes'] = [
            'label'    => __('Mis Viajes en Bici', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => 'bike',
            'callback' => [$this, 'render_tab_mis_viajes'],
            'orden'    => 35,
        ];

        $tabs_existentes['bicicletas-mi-cuenta'] = [
            'label'    => __('Mi Cuenta Bici', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => 'wallet',
            'callback' => [$this, 'render_tab_mi_cuenta'],
            'orden'    => 36,
        ];

        $tabs_existentes['bicicletas-estadisticas'] = [
            'label'    => __('Mis Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => 'chart',
            'callback' => [$this, 'render_tab_estadisticas'],
            'orden'    => 37,
        ];

        return $tabs_existentes;
    }

    /**
     * Encola assets CSS/JS cuando corresponde
     */
    public function encolar_assets() {
        if (!is_user_logged_in()) {
            return;
        }

        $tab_actual = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
        $tabs_bicicletas = ['bicicletas-mis-viajes', 'bicicletas-mi-cuenta', 'bicicletas-estadisticas'];

        if (!in_array($tab_actual, $tabs_bicicletas, true)) {
            return;
        }

        // Encolar Chart.js para gráficos
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        // Estilos inline específicos para los tabs de bicicletas
        $estilos_css = $this->obtener_estilos_css();
        wp_add_inline_style('flavor-user-dashboard', $estilos_css);
    }

    /**
     * Verifica si el módulo de bicicletas está activo
     *
     * @return bool
     */
    private function modulo_esta_activo() {
        global $wpdb;
        return Flavor_Chat_Helpers::tabla_existe($this->tablas['bicicletas']);
    }

    /**
     * Obtiene el ID del usuario actual
     *
     * @return int
     */
    private function obtener_usuario_id() {
        if (!$this->usuario_id) {
            $this->usuario_id = get_current_user_id();
        }
        return $this->usuario_id;
    }

    // =========================================================================
    // TAB: MIS VIAJES (Historial de uso)
    // =========================================================================

    /**
     * Renderiza el tab "Mis Viajes en Bici"
     */
    public function render_tab_mis_viajes() {
        $usuario_id = $this->obtener_usuario_id();
        $datos_viajes = $this->obtener_historial_viajes($usuario_id, 10, 0);
        $viaje_activo = $this->obtener_viaje_activo($usuario_id);
        $total_viajes = $this->contar_total_viajes($usuario_id);

        ?>
        <div class="flavor-bicicletas-tab flavor-bicicletas-mis-viajes">
            <div class="flavor-tab-header">
                <h2><?php esc_html_e('Mis Viajes en Bici', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-tab-description">
                    <?php esc_html_e('Historial completo de tus préstamos y viajes en bicicleta.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <?php if ($viaje_activo) : ?>
                <div class="flavor-viaje-activo flavor-alert flavor-alert--info">
                    <div class="flavor-viaje-activo-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="5.5" cy="17.5" r="3.5"/>
                            <circle cx="18.5" cy="17.5" r="3.5"/>
                            <path d="M15 6a1 1 0 100-2 1 1 0 000 2z"/>
                            <path d="M12 17.5V14l-3-3 4-3 2 3h3"/>
                        </svg>
                    </div>
                    <div class="flavor-viaje-activo-info">
                        <strong><?php esc_html_e('Viaje en curso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <p>
                            <?php
                            printf(
                                /* translators: %1$s: código bicicleta, %2$s: estación salida, %3$s: tiempo transcurrido */
                                esc_html__('Bicicleta %1$s desde %2$s - %3$s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                '<strong>' . esc_html($viaje_activo->bicicleta_codigo) . '</strong>',
                                esc_html($viaje_activo->estacion_nombre),
                                $this->formatear_duracion_viaje($viaje_activo->fecha_inicio)
                            );
                            ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($datos_viajes)) : ?>
                <div class="flavor-empty-state">
                    <div class="flavor-empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="5.5" cy="17.5" r="3.5"/>
                            <circle cx="18.5" cy="17.5" r="3.5"/>
                            <path d="M15 6a1 1 0 100-2 1 1 0 000 2z"/>
                            <path d="M12 17.5V14l-3-3 4-3 2 3h3"/>
                        </svg>
                    </div>
                    <h3><?php esc_html_e('Sin viajes registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Aún no has realizado ningún viaje. ¡Encuentra una estación cercana y comienza a pedalear!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else : ?>
                <div class="flavor-viajes-lista" id="flavor-viajes-lista">
                    <?php foreach ($datos_viajes as $viaje) : ?>
                        <?php $this->render_tarjeta_viaje($viaje); ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_viajes > count($datos_viajes)) : ?>
                    <div class="flavor-cargar-mas-container">
                        <button type="button"
                                class="flavor-btn flavor-btn--secondary"
                                id="flavor-cargar-mas-viajes"
                                data-offset="10"
                                data-total="<?php echo esc_attr($total_viajes); ?>">
                            <?php esc_html_e('Cargar más viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btnCargarMas = document.getElementById('flavor-cargar-mas-viajes');
            if (btnCargarMas) {
                btnCargarMas.addEventListener('click', function() {
                    var offset = parseInt(this.dataset.offset);
                    var total = parseInt(this.dataset.total);
                    var btn = this;

                    btn.disabled = true;
                    btn.textContent = '<?php echo esc_js(__('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';

                    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=flavor_bicicletas_cargar_mas_viajes&offset=' + offset + '&nonce=<?php echo esc_js(wp_create_nonce('flavor_bicicletas_viajes')); ?>'
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success && data.data.html) {
                            document.getElementById('flavor-viajes-lista').insertAdjacentHTML('beforeend', data.data.html);
                            var nuevoOffset = offset + 10;
                            if (nuevoOffset >= total) {
                                btn.remove();
                            } else {
                                btn.dataset.offset = nuevoOffset;
                                btn.disabled = false;
                                btn.textContent = '<?php echo esc_js(__('Cargar más viajes', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                            }
                        }
                    })
                    .catch(function() {
                        btn.disabled = false;
                        btn.textContent = '<?php echo esc_js(__('Error. Reintentar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                    });
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Renderiza una tarjeta de viaje individual
     *
     * @param object $viaje Datos del viaje
     */
    private function render_tarjeta_viaje($viaje) {
        $fecha_formateada = date_i18n('d M Y, H:i', strtotime($viaje->fecha_inicio));
        $duracion_texto = $this->formatear_duracion_minutos($viaje->duracion_minutos);
        $estado_clase = $viaje->estado === 'finalizado' ? 'completado' : 'activo';
        ?>
        <div class="flavor-viaje-card flavor-viaje-card--<?php echo esc_attr($estado_clase); ?>">
            <div class="flavor-viaje-card-header">
                <div class="flavor-viaje-fecha">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <?php echo esc_html($fecha_formateada); ?>
                </div>
                <span class="flavor-viaje-estado flavor-badge flavor-badge--<?php echo esc_attr($estado_clase); ?>">
                    <?php echo $viaje->estado === 'finalizado' ? esc_html__('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
            </div>

            <div class="flavor-viaje-card-body">
                <div class="flavor-viaje-bici">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="5.5" cy="17.5" r="3.5"/>
                        <circle cx="18.5" cy="17.5" r="3.5"/>
                        <path d="M15 6a1 1 0 100-2 1 1 0 000 2z"/>
                        <path d="M12 17.5V14l-3-3 4-3 2 3h3"/>
                    </svg>
                    <div>
                        <strong><?php echo esc_html($viaje->bicicleta_codigo); ?></strong>
                        <span class="flavor-viaje-tipo"><?php echo esc_html(ucfirst($viaje->bicicleta_tipo)); ?></span>
                    </div>
                </div>

                <div class="flavor-viaje-ruta">
                    <div class="flavor-viaje-estacion flavor-viaje-estacion--salida">
                        <span class="flavor-estacion-punto"></span>
                        <span><?php echo esc_html($viaje->estacion_salida_nombre ?: __('Estación de salida', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                    </div>
                    <?php if ($viaje->estacion_llegada_nombre) : ?>
                        <div class="flavor-viaje-linea"></div>
                        <div class="flavor-viaje-estacion flavor-viaje-estacion--llegada">
                            <span class="flavor-estacion-punto"></span>
                            <span><?php echo esc_html($viaje->estacion_llegada_nombre); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flavor-viaje-card-footer">
                <div class="flavor-viaje-stat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span><?php echo esc_html($duracion_texto); ?></span>
                </div>
                <?php if ($viaje->kilometros_recorridos > 0) : ?>
                    <div class="flavor-viaje-stat">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span><?php echo esc_html(number_format($viaje->kilometros_recorridos, 1)); ?> km</span>
                    </div>
                <?php endif; ?>
                <?php if ($viaje->valoracion) : ?>
                    <div class="flavor-viaje-stat flavor-viaje-valoracion">
                        <?php for ($estrella = 1; $estrella <= 5; $estrella++) : ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="<?php echo $estrella <= $viaje->valoracion ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // TAB: MI CUENTA (Saldo, plan activo)
    // =========================================================================

    /**
     * Renderiza el tab "Mi Cuenta Bici"
     */
    public function render_tab_mi_cuenta() {
        $usuario_id = $this->obtener_usuario_id();
        $datos_cuenta = $this->obtener_datos_cuenta($usuario_id);
        $historial_transacciones = $this->obtener_historial_transacciones($usuario_id, 5);

        ?>
        <div class="flavor-bicicletas-tab flavor-bicicletas-mi-cuenta">
            <div class="flavor-tab-header">
                <h2><?php esc_html_e('Mi Cuenta Bici', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-tab-description">
                    <?php esc_html_e('Gestiona tu saldo, plan de suscripción y preferencias.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- Tarjetas de estado de cuenta -->
            <div class="flavor-cuenta-grid">
                <!-- Saldo actual -->
                <div class="flavor-cuenta-card flavor-cuenta-card--saldo">
                    <div class="flavor-cuenta-card-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                    </div>
                    <div class="flavor-cuenta-card-content">
                        <span class="flavor-cuenta-label"><?php esc_html_e('Saldo disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-cuenta-valor"><?php echo esc_html(number_format($datos_cuenta['saldo'], 2)); ?> &euro;</span>
                    </div>
                    <button type="button" class="flavor-btn flavor-btn--primary flavor-btn--sm">
                        <?php esc_html_e('Recargar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <!-- Plan activo -->
                <div class="flavor-cuenta-card flavor-cuenta-card--plan">
                    <div class="flavor-cuenta-card-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </div>
                    <div class="flavor-cuenta-card-content">
                        <span class="flavor-cuenta-label"><?php esc_html_e('Plan actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-cuenta-valor"><?php echo esc_html($datos_cuenta['plan_nombre']); ?></span>
                        <?php if ($datos_cuenta['plan_vencimiento']) : ?>
                            <span class="flavor-cuenta-detalle">
                                <?php
                                printf(
                                    /* translators: %s: fecha de vencimiento */
                                    esc_html__('Válido hasta: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    date_i18n('d M Y', strtotime($datos_cuenta['plan_vencimiento']))
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="flavor-btn flavor-btn--secondary flavor-btn--sm">
                        <?php esc_html_e('Cambiar plan', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <!-- Fianza depositada -->
                <div class="flavor-cuenta-card flavor-cuenta-card--fianza">
                    <div class="flavor-cuenta-card-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div class="flavor-cuenta-card-content">
                        <span class="flavor-cuenta-label"><?php esc_html_e('Fianza depositada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="flavor-cuenta-valor"><?php echo esc_html(number_format($datos_cuenta['fianza'], 2)); ?> &euro;</span>
                    </div>
                </div>
            </div>

            <!-- Planes disponibles -->
            <div class="flavor-planes-section">
                <h3><?php esc_html_e('Planes disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="flavor-planes-grid">
                    <?php
                    $planes_disponibles = $this->obtener_planes_disponibles();
                    foreach ($planes_disponibles as $plan) :
                        $plan_activo = ($datos_cuenta['plan_id'] === $plan['id']);
                    ?>
                        <div class="flavor-plan-card <?php echo $plan_activo ? 'flavor-plan-card--activo' : ''; ?>">
                            <?php if ($plan['destacado']) : ?>
                                <span class="flavor-plan-badge"><?php esc_html_e('Popular', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php endif; ?>
                            <h4><?php echo esc_html($plan['nombre']); ?></h4>
                            <div class="flavor-plan-precio">
                                <span class="flavor-plan-cantidad"><?php echo esc_html(number_format($plan['precio'], 2)); ?></span>
                                <span class="flavor-plan-periodo">&euro;/<?php echo esc_html($plan['periodo']); ?></span>
                            </div>
                            <ul class="flavor-plan-caracteristicas">
                                <?php foreach ($plan['caracteristicas'] as $caracteristica) : ?>
                                    <li>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        <?php echo esc_html($caracteristica); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($plan_activo) : ?>
                                <span class="flavor-btn flavor-btn--disabled"><?php esc_html_e('Plan actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php else : ?>
                                <button type="button" class="flavor-btn flavor-btn--primary">
                                    <?php esc_html_e('Seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Historial de transacciones -->
            <?php if (!empty($historial_transacciones)) : ?>
                <div class="flavor-transacciones-section">
                    <h3><?php esc_html_e('Últimas transacciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="flavor-transacciones-lista">
                        <?php foreach ($historial_transacciones as $transaccion) : ?>
                            <div class="flavor-transaccion-item">
                                <div class="flavor-transaccion-info">
                                    <span class="flavor-transaccion-concepto"><?php echo esc_html($transaccion['concepto']); ?></span>
                                    <span class="flavor-transaccion-fecha"><?php echo esc_html(date_i18n('d M Y', strtotime($transaccion['fecha']))); ?></span>
                                </div>
                                <span class="flavor-transaccion-importe <?php echo $transaccion['importe'] >= 0 ? 'positivo' : 'negativo'; ?>">
                                    <?php echo $transaccion['importe'] >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($transaccion['importe'], 2)); ?> &euro;
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // =========================================================================
    // TAB: ESTADÍSTICAS (km recorridos, CO2 ahorrado)
    // =========================================================================

    /**
     * Renderiza el tab "Mis Estadísticas"
     */
    public function render_tab_estadisticas() {
        $usuario_id = $this->obtener_usuario_id();
        $estadisticas = $this->calcular_estadisticas_usuario($usuario_id);
        $datos_grafico_mensual = $this->obtener_datos_grafico_mensual($usuario_id);

        ?>
        <div class="flavor-bicicletas-tab flavor-bicicletas-estadisticas">
            <div class="flavor-tab-header">
                <h2><?php esc_html_e('Mis Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="flavor-tab-description">
                    <?php esc_html_e('Tu impacto ambiental y resumen de actividad ciclista.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- Tarjetas de estadísticas principales -->
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card flavor-stat-card--km">
                    <div class="flavor-stat-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <div class="flavor-stat-content">
                        <span class="flavor-stat-valor"><?php echo esc_html(number_format($estadisticas['total_km'], 1)); ?></span>
                        <span class="flavor-stat-label"><?php esc_html_e('Kilómetros recorridos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-stat-card flavor-stat-card--co2">
                    <div class="flavor-stat-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10H12V2z"/>
                            <path d="M12 2a10 10 0 0 1 10 10"/>
                            <circle cx="12" cy="12" r="6"/>
                        </svg>
                    </div>
                    <div class="flavor-stat-content">
                        <span class="flavor-stat-valor"><?php echo esc_html(number_format($estadisticas['co2_ahorrado'], 2)); ?></span>
                        <span class="flavor-stat-label"><?php esc_html_e('kg CO₂ ahorrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <span class="flavor-stat-detalle">
                        <?php
                        /* translators: %d: número de árboles equivalentes */
                        printf(esc_html__('Equivale a %d árboles plantados', FLAVOR_PLATFORM_TEXT_DOMAIN), $estadisticas['arboles_equivalentes']);
                        ?>
                    </span>
                </div>

                <div class="flavor-stat-card flavor-stat-card--tiempo">
                    <div class="flavor-stat-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="flavor-stat-content">
                        <span class="flavor-stat-valor"><?php echo esc_html($this->formatear_tiempo_total($estadisticas['total_minutos'])); ?></span>
                        <span class="flavor-stat-label"><?php esc_html_e('Tiempo total pedaleando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-stat-card flavor-stat-card--viajes">
                    <div class="flavor-stat-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="5.5" cy="17.5" r="3.5"/>
                            <circle cx="18.5" cy="17.5" r="3.5"/>
                            <path d="M15 6a1 1 0 100-2 1 1 0 000 2z"/>
                            <path d="M12 17.5V14l-3-3 4-3 2 3h3"/>
                        </svg>
                    </div>
                    <div class="flavor-stat-content">
                        <span class="flavor-stat-valor"><?php echo esc_html(number_format($estadisticas['total_viajes'])); ?></span>
                        <span class="flavor-stat-label"><?php esc_html_e('Viajes realizados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-stat-card flavor-stat-card--calorias">
                    <div class="flavor-stat-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <div class="flavor-stat-content">
                        <span class="flavor-stat-valor"><?php echo esc_html(number_format($estadisticas['calorias_quemadas'])); ?></span>
                        <span class="flavor-stat-label"><?php esc_html_e('Calorías quemadas (aprox.)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <div class="flavor-stat-card flavor-stat-card--dinero">
                    <div class="flavor-stat-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="flavor-stat-content">
                        <span class="flavor-stat-valor"><?php echo esc_html(number_format($estadisticas['dinero_ahorrado'], 2)); ?> &euro;</span>
                        <span class="flavor-stat-label"><?php esc_html_e('Dinero ahorrado vs coche', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <!-- Gráfico de uso mensual -->
            <div class="flavor-grafico-section">
                <h3><?php esc_html_e('Uso mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="flavor-grafico-container">
                    <canvas id="flavor-grafico-uso-mensual" height="300"></canvas>
                </div>
            </div>

            <!-- Logros y badges -->
            <div class="flavor-logros-section">
                <h3><?php esc_html_e('Tus logros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="flavor-logros-grid">
                    <?php
                    $logros = $this->obtener_logros_usuario($estadisticas);
                    foreach ($logros as $logro) :
                    ?>
                        <div class="flavor-logro-card <?php echo $logro['desbloqueado'] ? 'flavor-logro-card--activo' : 'flavor-logro-card--bloqueado'; ?>">
                            <div class="flavor-logro-icono"><?php echo $logro['icono']; ?></div>
                            <div class="flavor-logro-info">
                                <strong><?php echo esc_html($logro['nombre']); ?></strong>
                                <span><?php echo esc_html($logro['descripcion']); ?></span>
                            </div>
                            <?php if (!$logro['desbloqueado']) : ?>
                                <div class="flavor-logro-progreso">
                                    <div class="flavor-logro-barra" style="width: <?php echo esc_attr($logro['progreso']); ?>%"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart !== 'undefined') {
                var ctx = document.getElementById('flavor-grafico-uso-mensual');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo wp_json_encode($datos_grafico_mensual['etiquetas']); ?>,
                            datasets: [
                                {
                                    label: '<?php echo esc_js(__('Kilómetros', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                                    data: <?php echo wp_json_encode($datos_grafico_mensual['kilometros']); ?>,
                                    backgroundColor: 'rgba(34, 197, 94, 0.7)',
                                    borderColor: 'rgb(34, 197, 94)',
                                    borderWidth: 1,
                                    yAxisID: 'y'
                                },
                                {
                                    label: '<?php echo esc_js(__('Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                                    data: <?php echo wp_json_encode($datos_grafico_mensual['viajes']); ?>,
                                    type: 'line',
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    yAxisID: 'y1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            scales: {
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    title: {
                                        display: true,
                                        text: '<?php echo esc_js(__('Kilómetros', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>'
                                    }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    title: {
                                        display: true,
                                        text: '<?php echo esc_js(__('Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>'
                                    },
                                    grid: {
                                        drawOnChartArea: false
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            }
                        }
                    });
                }
            }
        });
        </script>
        <?php
    }

    // =========================================================================
    // MÉTODOS DE CONSULTA A BASE DE DATOS
    // =========================================================================

    /**
     * Obtiene el historial de viajes del usuario
     *
     * @param int $usuario_id ID del usuario
     * @param int $limite Cantidad de registros
     * @param int $offset Desplazamiento
     * @return array
     */
    private function obtener_historial_viajes($usuario_id, $limite = 10, $offset = 0) {
        global $wpdb;

        $consulta_sql = $wpdb->prepare(
            "SELECT p.*,
                b.codigo as bicicleta_codigo,
                b.tipo as bicicleta_tipo,
                b.marca as bicicleta_marca,
                es.nombre as estacion_salida_nombre,
                el.nombre as estacion_llegada_nombre
            FROM {$this->tablas['prestamos']} p
            LEFT JOIN {$this->tablas['bicicletas']} b ON p.bicicleta_id = b.id
            LEFT JOIN {$this->tablas['estaciones']} es ON p.estacion_salida_id = es.id
            LEFT JOIN {$this->tablas['estaciones']} el ON p.estacion_llegada_id = el.id
            WHERE p.usuario_id = %d
            ORDER BY p.fecha_inicio DESC
            LIMIT %d OFFSET %d",
            $usuario_id,
            $limite,
            $offset
        );

        return $wpdb->get_results($consulta_sql);
    }

    /**
     * Obtiene el viaje activo del usuario (si existe)
     *
     * @param int $usuario_id ID del usuario
     * @return object|null
     */
    private function obtener_viaje_activo($usuario_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT p.*,
                b.codigo as bicicleta_codigo,
                es.nombre as estacion_nombre
            FROM {$this->tablas['prestamos']} p
            LEFT JOIN {$this->tablas['bicicletas']} b ON p.bicicleta_id = b.id
            LEFT JOIN {$this->tablas['estaciones']} es ON p.estacion_salida_id = es.id
            WHERE p.usuario_id = %d AND p.estado = 'activo'
            LIMIT 1",
            $usuario_id
        ));
    }

    /**
     * Cuenta el total de viajes del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return int
     */
    private function contar_total_viajes($usuario_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tablas['prestamos']} WHERE usuario_id = %d",
            $usuario_id
        ));
    }

    /**
     * Obtiene los datos de cuenta del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_datos_cuenta($usuario_id) {
        $saldo_actual = (float) get_user_meta($usuario_id, '_bicicletas_saldo', true);
        $plan_activo_id = get_user_meta($usuario_id, '_bicicletas_plan_id', true);
        $fecha_vencimiento_plan = get_user_meta($usuario_id, '_bicicletas_plan_vencimiento', true);
        $fianza_depositada = (float) get_user_meta($usuario_id, '_bicicletas_fianza', true);

        $planes = $this->obtener_planes_disponibles();
        $nombre_plan = __('Sin plan', FLAVOR_PLATFORM_TEXT_DOMAIN);

        foreach ($planes as $plan) {
            if ($plan['id'] === $plan_activo_id) {
                $nombre_plan = $plan['nombre'];
                break;
            }
        }

        return [
            'saldo'            => $saldo_actual ?: 0,
            'plan_id'          => $plan_activo_id ?: '',
            'plan_nombre'      => $nombre_plan,
            'plan_vencimiento' => $fecha_vencimiento_plan ?: '',
            'fianza'           => $fianza_depositada ?: 0,
        ];
    }

    /**
     * Obtiene el historial de transacciones del usuario
     *
     * @param int $usuario_id ID del usuario
     * @param int $limite Cantidad de registros
     * @return array
     */
    private function obtener_historial_transacciones($usuario_id, $limite = 5) {
        global $wpdb;

        // Obtener últimos préstamos con coste como transacciones
        $prestamos = $wpdb->get_results($wpdb->prepare(
            "SELECT fecha_fin as fecha, coste_total as importe, 'Viaje en bicicleta' as concepto
            FROM {$this->tablas['prestamos']}
            WHERE usuario_id = %d AND estado = 'finalizado' AND coste_total > 0
            ORDER BY fecha_fin DESC
            LIMIT %d",
            $usuario_id,
            $limite
        ), ARRAY_A);

        // Convertir importes a negativos (gastos)
        return array_map(function($transaccion) {
            $transaccion['importe'] = -abs((float) $transaccion['importe']);
            return $transaccion;
        }, $prestamos);
    }

    /**
     * Obtiene los planes de suscripción disponibles
     *
     * @return array
     */
    private function obtener_planes_disponibles() {
        return [
            [
                'id'              => 'gratuito',
                'nombre'          => __('Gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'precio'          => 0,
                'periodo'         => __('mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'destacado'       => false,
                'caracteristicas' => [
                    __('30 minutos gratis/día', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Acceso a bicicletas urbanas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ],
            [
                'id'              => 'basico',
                'nombre'          => __('Básico', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'precio'          => 5,
                'periodo'         => __('mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'destacado'       => false,
                'caracteristicas' => [
                    __('2 horas gratis/día', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Acceso a todas las bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Descuentos en hora extra', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ],
            [
                'id'              => 'premium',
                'nombre'          => __('Premium', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'precio'          => 15,
                'periodo'         => __('mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'destacado'       => true,
                'caracteristicas' => [
                    __('Uso ilimitado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Acceso a bicis eléctricas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Reservas anticipadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Soporte prioritario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ],
            [
                'id'              => 'anual',
                'nombre'          => __('Anual', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'precio'          => 99,
                'periodo'         => __('año', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'destacado'       => false,
                'caracteristicas' => [
                    __('Todo lo del Premium', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Ahorra 2 meses', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Sin fianza', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ],
        ];
    }

    /**
     * Calcula las estadísticas del usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function calcular_estadisticas_usuario($usuario_id) {
        global $wpdb;

        $resultado_estadisticas = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_viajes,
                COALESCE(SUM(kilometros_recorridos), 0) as total_km,
                COALESCE(SUM(duracion_minutos), 0) as total_minutos
            FROM {$this->tablas['prestamos']}
            WHERE usuario_id = %d AND estado = 'finalizado'",
            $usuario_id
        ));

        $total_kilometros = (float) ($resultado_estadisticas->total_km ?? 0);
        $total_minutos = (int) ($resultado_estadisticas->total_minutos ?? 0);
        $total_viajes = (int) ($resultado_estadisticas->total_viajes ?? 0);

        // Cálculos de impacto ambiental
        // CO2 ahorrado: ~120g por km en coche (media)
        $co2_ahorrado_kg = $total_kilometros * 0.12;

        // Árboles equivalentes: un árbol absorbe ~21kg CO2/año
        $arboles_equivalentes = (int) round($co2_ahorrado_kg / 21);

        // Calorías: ~30 calorías por km en bici (media)
        $calorias_quemadas = (int) ($total_kilometros * 30);

        // Dinero ahorrado: ~0.25€/km en coche (combustible + desgaste)
        $dinero_ahorrado = $total_kilometros * 0.25;

        return [
            'total_viajes'         => $total_viajes,
            'total_km'             => $total_kilometros,
            'total_minutos'        => $total_minutos,
            'co2_ahorrado'         => $co2_ahorrado_kg,
            'arboles_equivalentes' => max(1, $arboles_equivalentes),
            'calorias_quemadas'    => $calorias_quemadas,
            'dinero_ahorrado'      => $dinero_ahorrado,
        ];
    }

    /**
     * Obtiene los datos para el gráfico de uso mensual
     *
     * @param int $usuario_id ID del usuario
     * @return array
     */
    private function obtener_datos_grafico_mensual($usuario_id) {
        global $wpdb;

        $etiquetas_meses = [];
        $datos_kilometros = [];
        $datos_viajes = [];

        // Últimos 6 meses
        for ($indice_mes = 5; $indice_mes >= 0; $indice_mes--) {
            $fecha_mes = date('Y-m', strtotime("-{$indice_mes} months"));
            $nombre_mes = date_i18n('M Y', strtotime($fecha_mes . '-01'));
            $etiquetas_meses[] = $nombre_mes;

            $datos_mes = $wpdb->get_row($wpdb->prepare(
                "SELECT
                    COUNT(*) as viajes,
                    COALESCE(SUM(kilometros_recorridos), 0) as km
                FROM {$this->tablas['prestamos']}
                WHERE usuario_id = %d
                    AND estado = 'finalizado'
                    AND DATE_FORMAT(fecha_inicio, '%%Y-%%m') = %s",
                $usuario_id,
                $fecha_mes
            ));

            $datos_kilometros[] = (float) ($datos_mes->km ?? 0);
            $datos_viajes[] = (int) ($datos_mes->viajes ?? 0);
        }

        return [
            'etiquetas'   => $etiquetas_meses,
            'kilometros'  => $datos_kilometros,
            'viajes'      => $datos_viajes,
        ];
    }

    /**
     * Obtiene los logros del usuario basados en sus estadísticas
     *
     * @param array $estadisticas Estadísticas del usuario
     * @return array
     */
    private function obtener_logros_usuario($estadisticas) {
        $total_km = $estadisticas['total_km'];
        $total_viajes = $estadisticas['total_viajes'];

        return [
            [
                'id'           => 'primer_viaje',
                'nombre'       => __('Primer Pedaleo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion'  => __('Completa tu primer viaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'        => '🚲',
                'desbloqueado' => $total_viajes >= 1,
                'progreso'     => min(100, $total_viajes * 100),
            ],
            [
                'id'           => 'explorador',
                'nombre'       => __('Explorador', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion'  => __('Recorre 10 km en total', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'        => '🗺️',
                'desbloqueado' => $total_km >= 10,
                'progreso'     => min(100, ($total_km / 10) * 100),
            ],
            [
                'id'           => 'ciclista_urbano',
                'nombre'       => __('Ciclista Urbano', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion'  => __('Completa 10 viajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'        => '🏙️',
                'desbloqueado' => $total_viajes >= 10,
                'progreso'     => min(100, ($total_viajes / 10) * 100),
            ],
            [
                'id'           => 'eco_warrior',
                'nombre'       => __('Eco Warrior', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion'  => __('Ahorra 5 kg de CO₂', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'        => '🌱',
                'desbloqueado' => $estadisticas['co2_ahorrado'] >= 5,
                'progreso'     => min(100, ($estadisticas['co2_ahorrado'] / 5) * 100),
            ],
            [
                'id'           => 'centurion',
                'nombre'       => __('Centurión', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion'  => __('Recorre 100 km en total', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'        => '🏆',
                'desbloqueado' => $total_km >= 100,
                'progreso'     => min(100, ($total_km / 100) * 100),
            ],
            [
                'id'           => 'leyenda',
                'nombre'       => __('Leyenda del Pedal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion'  => __('Completa 100 viajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'        => '👑',
                'desbloqueado' => $total_viajes >= 100,
                'progreso'     => min(100, ($total_viajes / 100) * 100),
            ],
        ];
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Formatea la duración de un viaje en curso
     *
     * @param string $fecha_inicio Fecha de inicio del viaje
     * @return string Duración formateada
     */
    private function formatear_duracion_viaje($fecha_inicio) {
        $inicio_timestamp = strtotime($fecha_inicio);
        $ahora_timestamp = current_time('timestamp');
        $diferencia_segundos = $ahora_timestamp - $inicio_timestamp;

        $horas = floor($diferencia_segundos / 3600);
        $minutos = floor(($diferencia_segundos % 3600) / 60);

        if ($horas > 0) {
            return sprintf('%d h %d min', $horas, $minutos);
        }

        return sprintf('%d min', $minutos);
    }

    /**
     * Formatea minutos en texto legible
     *
     * @param int $minutos_totales Minutos totales
     * @return string Duración formateada
     */
    private function formatear_duracion_minutos($minutos_totales) {
        if (!$minutos_totales) {
            return __('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        if ($minutos_totales < 60) {
            return sprintf('%d min', $minutos_totales);
        }

        $horas = floor($minutos_totales / 60);
        $minutos = $minutos_totales % 60;

        if ($minutos > 0) {
            return sprintf('%d h %d min', $horas, $minutos);
        }

        return sprintf('%d h', $horas);
    }

    /**
     * Formatea tiempo total en horas y minutos
     *
     * @param int $minutos_totales Minutos totales
     * @return string Tiempo formateado
     */
    private function formatear_tiempo_total($minutos_totales) {
        if ($minutos_totales < 60) {
            return sprintf('%d min', $minutos_totales);
        }

        $horas = floor($minutos_totales / 60);
        $minutos = $minutos_totales % 60;

        if ($horas >= 24) {
            $dias = floor($horas / 24);
            $horas_restantes = $horas % 24;
            return sprintf('%d d %d h', $dias, $horas_restantes);
        }

        return sprintf('%d h %d min', $horas, $minutos);
    }

    /**
     * AJAX: Cargar más viajes
     */
    public function ajax_cargar_mas_viajes() {
        check_ajax_referer('flavor_bicicletas_viajes', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        $usuario_id = get_current_user_id();
        $viajes = $this->obtener_historial_viajes($usuario_id, 10, $offset);

        if (empty($viajes)) {
            wp_send_json_success(['html' => '']);
        }

        ob_start();
        foreach ($viajes as $viaje) {
            $this->render_tarjeta_viaje($viaje);
        }
        $html_viajes = ob_get_clean();

        wp_send_json_success(['html' => $html_viajes]);
    }

    /**
     * Obtiene los estilos CSS para los tabs de bicicletas
     *
     * @return string CSS
     */
    private function obtener_estilos_css() {
        return '
        /* Tabs de Bicicletas Compartidas */
        .flavor-bicicletas-tab {
            padding: 0;
        }
        .flavor-tab-header {
            margin-bottom: 2rem;
        }
        .flavor-tab-header h2 {
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .flavor-tab-description {
            color: #64748b;
            margin: 0;
        }

        /* Viaje activo */
        .flavor-viaje-activo {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .flavor-viaje-activo-icon {
            color: #3b82f6;
        }
        .flavor-viaje-activo-info strong {
            display: block;
            margin-bottom: 0.25rem;
        }
        .flavor-viaje-activo-info p {
            margin: 0;
            font-size: 0.875rem;
        }

        /* Tarjetas de viaje */
        .flavor-viajes-lista {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .flavor-viaje-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .flavor-viaje-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .flavor-viaje-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .flavor-viaje-fecha {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        .flavor-viaje-card-body {
            padding: 1.5rem;
        }
        .flavor-viaje-bici {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .flavor-viaje-bici strong {
            display: block;
        }
        .flavor-viaje-tipo {
            font-size: 0.75rem;
            color: #64748b;
        }
        .flavor-viaje-ruta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .flavor-viaje-estacion {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .flavor-estacion-punto {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #22c55e;
        }
        .flavor-viaje-estacion--llegada .flavor-estacion-punto {
            background: #ef4444;
        }
        .flavor-viaje-linea {
            flex: 1;
            min-width: 30px;
            height: 2px;
            background: linear-gradient(90deg, #22c55e, #ef4444);
        }
        .flavor-viaje-card-footer {
            display: flex;
            gap: 1.5rem;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .flavor-viaje-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        .flavor-viaje-valoracion {
            color: #f59e0b;
        }

        /* Badges de estado */
        .flavor-badge {
            display: inline-flex;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .flavor-badge--completado {
            background: #dcfce7;
            color: #166534;
        }
        .flavor-badge--activo {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Grid de cuenta */
        .flavor-cuenta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .flavor-cuenta-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .flavor-cuenta-card-icon {
            color: #3b82f6;
        }
        .flavor-cuenta-label {
            font-size: 0.875rem;
            color: #64748b;
        }
        .flavor-cuenta-valor {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }
        .flavor-cuenta-detalle {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* Planes */
        .flavor-planes-section h3,
        .flavor-transacciones-section h3,
        .flavor-grafico-section h3,
        .flavor-logros-section h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0 0 1rem;
        }
        .flavor-planes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }
        .flavor-plan-card {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            position: relative;
            transition: border-color 0.2s;
        }
        .flavor-plan-card:hover {
            border-color: #3b82f6;
        }
        .flavor-plan-card--activo {
            border-color: #22c55e;
            background: #f0fdf4;
        }
        .flavor-plan-badge {
            position: absolute;
            top: -10px;
            right: 15px;
            background: #3b82f6;
            color: #fff;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .flavor-plan-card h4 {
            margin: 0 0 1rem;
            font-size: 1.125rem;
        }
        .flavor-plan-precio {
            margin-bottom: 1rem;
        }
        .flavor-plan-cantidad {
            font-size: 2rem;
            font-weight: 700;
        }
        .flavor-plan-periodo {
            color: #64748b;
        }
        .flavor-plan-caracteristicas {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem;
            text-align: left;
        }
        .flavor-plan-caracteristicas li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            font-size: 0.875rem;
            color: #64748b;
        }
        .flavor-plan-caracteristicas svg {
            color: #22c55e;
            flex-shrink: 0;
        }

        /* Transacciones */
        .flavor-transacciones-lista {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .flavor-transaccion-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .flavor-transaccion-item:last-child {
            border-bottom: none;
        }
        .flavor-transaccion-concepto {
            font-weight: 500;
        }
        .flavor-transaccion-fecha {
            font-size: 0.75rem;
            color: #64748b;
        }
        .flavor-transaccion-importe {
            font-weight: 600;
        }
        .flavor-transaccion-importe.positivo {
            color: #22c55e;
        }
        .flavor-transaccion-importe.negativo {
            color: #ef4444;
        }

        /* Estadísticas */
        .flavor-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .flavor-stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.75rem;
        }
        .flavor-stat-icon {
            color: #3b82f6;
        }
        .flavor-stat-card--km .flavor-stat-icon { color: #22c55e; }
        .flavor-stat-card--co2 .flavor-stat-icon { color: #10b981; }
        .flavor-stat-card--tiempo .flavor-stat-icon { color: #6366f1; }
        .flavor-stat-card--viajes .flavor-stat-icon { color: #f59e0b; }
        .flavor-stat-card--calorias .flavor-stat-icon { color: #ef4444; }
        .flavor-stat-card--dinero .flavor-stat-icon { color: #8b5cf6; }
        .flavor-stat-valor {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
        }
        .flavor-stat-label {
            font-size: 0.875rem;
            color: #64748b;
        }
        .flavor-stat-detalle {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* Gráfico */
        .flavor-grafico-section {
            margin-bottom: 2rem;
        }
        .flavor-grafico-container {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            height: 350px;
        }

        /* Logros */
        .flavor-logros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }
        .flavor-logro-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .flavor-logro-card--bloqueado {
            opacity: 0.6;
        }
        .flavor-logro-card--activo {
            background: #f0fdf4;
            border-color: #22c55e;
        }
        .flavor-logro-icono {
            font-size: 2rem;
        }
        .flavor-logro-info strong {
            display: block;
            margin-bottom: 0.25rem;
        }
        .flavor-logro-info span {
            font-size: 0.75rem;
            color: #64748b;
        }
        .flavor-logro-progreso {
            flex: 1;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
        }
        .flavor-logro-barra {
            height: 100%;
            background: #3b82f6;
            border-radius: 3px;
        }

        /* Estados vacíos */
        .flavor-empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
        }
        .flavor-empty-icon {
            color: #cbd5e1;
            margin-bottom: 1rem;
        }
        .flavor-empty-state h3 {
            margin: 0 0 0.5rem;
            color: #64748b;
        }
        .flavor-empty-state p {
            margin: 0;
            color: #94a3b8;
        }

        /* Botones */
        .flavor-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .flavor-btn--primary {
            background: #3b82f6;
            color: #fff;
        }
        .flavor-btn--primary:hover {
            background: #2563eb;
        }
        .flavor-btn--secondary {
            background: #f1f5f9;
            color: #475569;
        }
        .flavor-btn--secondary:hover {
            background: #e2e8f0;
        }
        .flavor-btn--sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }
        .flavor-btn--disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
        }

        /* Cargar más */
        .flavor-cargar-mas-container {
            text-align: center;
            margin-top: 1.5rem;
        }

        /* Alert */
        .flavor-alert {
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
        }
        .flavor-alert--info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }
        ';
    }
}
