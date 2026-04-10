<?php
/**
 * Dashboard Tabs para el módulo Biblioteca
 *
 * Proporciona tabs en el panel de usuario para gestión de préstamos,
 * reservas, historial y libros favoritos.
 *
 * @package FlavorPlatform
 * @subpackage Biblioteca
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de Biblioteca
 */
class Flavor_Biblioteca_Dashboard_Tab {

    /**
     * Instancia singleton
     *
     * @var Flavor_Biblioteca_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Prefijo de tablas
     *
     * @var string
     */
    private $prefijo_tabla;

    /**
     * ID del usuario actual
     *
     * @var int
     */
    private $usuario_id;

    /**
     * Días antes de vencimiento para mostrar alerta
     *
     * @var int
     */
    private $dias_alerta_vencimiento = 3;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->prefijo_tabla = $wpdb->prefix;
        $this->init();
    }

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Biblioteca_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicialización de hooks
     */
    private function init() {
        // Registrar tabs en el dashboard de usuario
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 20);

        // Crear tabla de favoritos si no existe
        add_action('init', [$this, 'crear_tabla_favoritos']);

        // AJAX handlers para favoritos
        add_action('wp_ajax_biblioteca_toggle_favorito', [$this, 'ajax_toggle_favorito']);
        add_action('wp_ajax_biblioteca_obtener_favoritos', [$this, 'ajax_obtener_favoritos']);

        // Assets para el dashboard
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
    }

    /**
     * Registrar assets CSS/JS
     */
    public function registrar_assets() {
        if (!is_user_logged_in()) {
            return;
        }

        $version_plugin = defined('FLAVOR_VERSION') ? FLAVOR_VERSION : '1.0.0';
        $url_plugin = plugins_url('/', dirname(__FILE__));

        wp_register_style(
            'biblioteca-dashboard-tab',
            $url_plugin . 'assets/css/biblioteca-dashboard.css',
            [],
            $version_plugin
        );

        wp_register_script(
            'biblioteca-dashboard-tab',
            $url_plugin . 'assets/js/biblioteca-dashboard.js',
            ['jquery'],
            $version_plugin,
            true
        );

        wp_localize_script('biblioteca-dashboard-tab', 'bibliotecaDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biblioteca_dashboard_nonce'),
            'i18n' => [
                'confirmarEliminarFavorito' => __('¿Eliminar este libro de favoritos?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'agregadoFavoritos' => __('Libro agregado a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'eliminadoFavoritos' => __('Libro eliminado de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'errorGeneral' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encolar assets cuando se necesitan
     */
    private function encolar_assets() {
        wp_enqueue_style('biblioteca-dashboard-tab');
        wp_enqueue_script('biblioteca-dashboard-tab');
    }

    /**
     * Crear tabla de favoritos si no existe
     */
    public function crear_tabla_favoritos() {
        global $wpdb;

        $tabla_favoritos = $this->prefijo_tabla . 'flavor_biblioteca_favoritos';

        if ($this->tabla_existe($tabla_favoritos)) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql_favoritos = "CREATE TABLE IF NOT EXISTS {$tabla_favoritos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            libro_id bigint(20) unsigned NOT NULL,
            fecha_agregado datetime DEFAULT CURRENT_TIMESTAMP,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_libro (usuario_id, libro_id),
            KEY usuario_id (usuario_id),
            KEY libro_id (libro_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_favoritos);
    }

    /**
     * Verificar si una tabla existe
     *
     * @param string $nombre_tabla Nombre de la tabla
     * @return bool
     */
    private function tabla_existe($nombre_tabla) {
        global $wpdb;
        $resultado = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $nombre_tabla)
        );
        return $resultado === $nombre_tabla;
    }

    /**
     * Registrar tabs en el dashboard de usuario
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        if (!is_user_logged_in()) {
            return $tabs;
        }

        $this->usuario_id = get_current_user_id();
        $alertas_prestamos = $this->obtener_alertas_vencimiento();

        // Tab: Mis Préstamos Activos
        $tabs['biblioteca-mis-prestamos'] = [
            'label' => __('Mis Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'book',
            'callback' => [$this, 'render_tab_mis_prestamos'],
            'orden' => 40,
            'badge' => $this->contar_prestamos_activos(),
            'badge_class' => count($alertas_prestamos) > 0 ? 'badge-warning' : '',
        ];

        // Tab: Mis Reservas
        $tabs['biblioteca-mis-reservas'] = [
            'label' => __('Mis Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'calendar',
            'callback' => [$this, 'render_tab_mis_reservas'],
            'orden' => 41,
            'badge' => $this->contar_reservas_pendientes(),
        ];

        // Tab: Historial de Préstamos
        $tabs['biblioteca-historial'] = [
            'label' => __('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'clock',
            'callback' => [$this, 'render_tab_historial'],
            'orden' => 42,
        ];

        // Tab: Libros Favoritos
        $tabs['biblioteca-favoritos'] = [
            'label' => __('Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'heart',
            'callback' => [$this, 'render_tab_favoritos'],
            'orden' => 43,
            'badge' => $this->contar_favoritos(),
        ];

        return $tabs;
    }

    // =========================================================================
    // MÉTODOS DE CONTEO
    // =========================================================================

    /**
     * Contar préstamos activos del usuario
     *
     * @return int
     */
    private function contar_prestamos_activos() {
        global $wpdb;
        $tabla = $this->prefijo_tabla . 'flavor_biblioteca_prestamos';

        if (!$this->tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla}
             WHERE prestatario_id = %d
             AND estado IN ('activo', 'retrasado')",
            $this->usuario_id
        ));
    }

    /**
     * Contar reservas pendientes del usuario
     *
     * @return int
     */
    private function contar_reservas_pendientes() {
        global $wpdb;
        $tabla = $this->prefijo_tabla . 'flavor_biblioteca_reservas';

        if (!$this->tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla}
             WHERE usuario_id = %d
             AND estado IN ('pendiente', 'confirmada')",
            $this->usuario_id
        ));
    }

    /**
     * Contar libros favoritos del usuario
     *
     * @return int
     */
    private function contar_favoritos() {
        global $wpdb;
        $tabla = $this->prefijo_tabla . 'flavor_biblioteca_favoritos';

        if (!$this->tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE usuario_id = %d",
            $this->usuario_id
        ));
    }

    /**
     * Obtener alertas de préstamos próximos a vencer o vencidos
     *
     * @return array
     */
    private function obtener_alertas_vencimiento() {
        global $wpdb;
        $tabla_prestamos = $this->prefijo_tabla . 'flavor_biblioteca_prestamos';
        $tabla_libros = $this->prefijo_tabla . 'flavor_biblioteca_libros';

        if (!$this->tabla_existe($tabla_prestamos)) {
            return [];
        }

        $fecha_limite = date('Y-m-d', strtotime("+{$this->dias_alerta_vencimiento} days"));

        $alertas = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, l.titulo as libro_titulo, l.portada_url
             FROM {$tabla_prestamos} p
             LEFT JOIN {$tabla_libros} l ON p.libro_id = l.id
             WHERE p.prestatario_id = %d
             AND p.estado = 'activo'
             AND (p.fecha_devolucion_prevista <= %s OR p.fecha_devolucion_prevista < CURDATE())
             ORDER BY p.fecha_devolucion_prevista ASC",
            $this->usuario_id,
            $fecha_limite
        ));

        return $alertas ?: [];
    }

    // =========================================================================
    // TAB: MIS PRÉSTAMOS ACTIVOS
    // =========================================================================

    /**
     * Renderizar tab de préstamos activos
     */
    public function render_tab_mis_prestamos() {
        $this->encolar_assets();
        $this->usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_prestamos = $this->prefijo_tabla . 'flavor_biblioteca_prestamos';
        $tabla_libros = $this->prefijo_tabla . 'flavor_biblioteca_libros';

        $prestamos_activos = [];
        $alertas = [];

        if ($this->tabla_existe($tabla_prestamos)) {
            // Obtener préstamos activos
            $prestamos_activos = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, l.titulo as libro_titulo, l.autor as libro_autor,
                        l.portada_url, l.isbn, u.display_name as prestamista_nombre
                 FROM {$tabla_prestamos} p
                 LEFT JOIN {$tabla_libros} l ON p.libro_id = l.id
                 LEFT JOIN {$wpdb->users} u ON p.prestamista_id = u.ID
                 WHERE p.prestatario_id = %d
                 AND p.estado IN ('activo', 'retrasado')
                 ORDER BY p.fecha_devolucion_prevista ASC",
                $this->usuario_id
            ));

            // Obtener alertas
            $alertas = $this->obtener_alertas_vencimiento();
        }
        ?>
        <div class="biblioteca-dashboard-tab biblioteca-mis-prestamos">
            <div class="tab-header">
                <h2>
                    <span class="dashicons dashicons-book"></span>
                    <?php esc_html_e('Mis Préstamos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="tab-description">
                    <?php esc_html_e('Libros que tienes actualmente en préstamo. Recuerda devolverlos antes de la fecha límite.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <?php if (!empty($alertas)): ?>
                <div class="alertas-vencimiento">
                    <?php foreach ($alertas as $alerta):
                        $fecha_vencimiento = strtotime($alerta->fecha_devolucion_prevista);
                        $dias_restantes = floor(($fecha_vencimiento - time()) / DAY_IN_SECONDS);
                        $esta_vencido = $dias_restantes < 0;
                        $clase_alerta = $esta_vencido ? 'alerta-vencido' : 'alerta-proximo';
                    ?>
                        <div class="alerta-item <?php echo esc_attr($clase_alerta); ?>">
                            <span class="alerta-icono dashicons <?php echo $esta_vencido ? 'dashicons-warning' : 'dashicons-clock'; ?>"></span>
                            <div class="alerta-contenido">
                                <strong><?php echo esc_html($alerta->libro_titulo); ?></strong>
                                <?php if ($esta_vencido): ?>
                                    <span class="alerta-texto">
                                        <?php printf(
                                            esc_html__('Vencido hace %d días. Por favor, devuélvelo cuanto antes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                            abs($dias_restantes)
                                        ); ?>
                                    </span>
                                <?php elseif ($dias_restantes === 0): ?>
                                    <span class="alerta-texto"><?php esc_html_e('Vence hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php elseif ($dias_restantes === 1): ?>
                                    <span class="alerta-texto"><?php esc_html_e('Vence mañana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php else: ?>
                                    <span class="alerta-texto">
                                        <?php printf(esc_html__('Vence en %d días', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias_restantes); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($prestamos_activos)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-book-alt"></span>
                    <h3><?php esc_html_e('No tienes préstamos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Explora el catálogo y encuentra tu próxima lectura.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/biblioteca/')); ?>" class="btn btn-primary">
                        <?php esc_html_e('Explorar catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="prestamos-grid">
                    <?php foreach ($prestamos_activos as $prestamo):
                        $fecha_vencimiento = strtotime($prestamo->fecha_devolucion_prevista);
                        $dias_restantes = floor(($fecha_vencimiento - time()) / DAY_IN_SECONDS);
                        $esta_vencido = $dias_restantes < 0;
                        $proximo_vencer = $dias_restantes >= 0 && $dias_restantes <= $this->dias_alerta_vencimiento;
                        $clase_estado = $esta_vencido ? 'vencido' : ($proximo_vencer ? 'proximo-vencer' : 'normal');
                    ?>
                        <div class="prestamo-card estado-<?php echo esc_attr($clase_estado); ?>">
                            <div class="prestamo-portada">
                                <?php if (!empty($prestamo->portada_url)): ?>
                                    <img src="<?php echo esc_url($prestamo->portada_url); ?>"
                                         alt="<?php echo esc_attr($prestamo->libro_titulo); ?>">
                                <?php else: ?>
                                    <div class="sin-portada">
                                        <span class="dashicons dashicons-book-alt"></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($esta_vencido): ?>
                                    <span class="badge-estado badge-vencido"><?php esc_html_e('Vencido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php elseif ($proximo_vencer): ?>
                                    <span class="badge-estado badge-proximo"><?php esc_html_e('Por vencer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="prestamo-info">
                                <h4 class="libro-titulo"><?php echo esc_html($prestamo->libro_titulo); ?></h4>

                                <?php if (!empty($prestamo->libro_autor)): ?>
                                    <p class="libro-autor">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <?php echo esc_html($prestamo->libro_autor); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="prestamo-fechas">
                                    <p class="fecha-prestamo">
                                        <strong><?php esc_html_e('Prestado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                        <?php echo date_i18n(get_option('date_format'), strtotime($prestamo->fecha_prestamo)); ?>
                                    </p>
                                    <p class="fecha-devolucion <?php echo $esta_vencido ? 'fecha-vencida' : ''; ?>">
                                        <strong><?php esc_html_e('Devolver antes de:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                        <?php echo date_i18n(get_option('date_format'), $fecha_vencimiento); ?>
                                        <?php if ($dias_restantes >= 0): ?>
                                            <span class="dias-restantes">(<?php printf(esc_html__('%d días', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias_restantes); ?>)</span>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <?php if (!empty($prestamo->prestamista_nombre)): ?>
                                    <p class="prestamista">
                                        <span class="dashicons dashicons-businessman"></span>
                                        <?php printf(esc_html__('Prestado por: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($prestamo->prestamista_nombre)); ?>
                                    </p>
                                <?php endif; ?>

                                <p class="renovaciones">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php printf(
                                        esc_html__('Renovaciones: %d', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        intval($prestamo->renovaciones)
                                    ); ?>
                                </p>
                            </div>

                            <div class="prestamo-acciones">
                                <?php if (!$esta_vencido && $prestamo->renovaciones < 2): ?>
                                    <button type="button" class="btn btn-secondary btn-renovar"
                                            data-prestamo-id="<?php echo esc_attr($prestamo->id); ?>">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php esc_html_e('Renovar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                <?php endif; ?>

                                <button type="button" class="btn btn-outline btn-contactar"
                                        data-usuario-id="<?php echo esc_attr($prestamo->prestamista_id); ?>">
                                    <span class="dashicons dashicons-email"></span>
                                    <?php esc_html_e('Contactar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // =========================================================================
    // TAB: MIS RESERVAS
    // =========================================================================

    /**
     * Renderizar tab de reservas
     */
    public function render_tab_mis_reservas() {
        $this->encolar_assets();
        $this->usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_reservas = $this->prefijo_tabla . 'flavor_biblioteca_reservas';
        $tabla_libros = $this->prefijo_tabla . 'flavor_biblioteca_libros';

        $reservas = [];

        if ($this->tabla_existe($tabla_reservas)) {
            $reservas = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, l.titulo as libro_titulo, l.autor as libro_autor,
                        l.portada_url, l.isbn, u.display_name as propietario_nombre
                 FROM {$tabla_reservas} r
                 LEFT JOIN {$tabla_libros} l ON r.libro_id = l.id
                 LEFT JOIN {$wpdb->users} u ON l.propietario_id = u.ID
                 WHERE r.usuario_id = %d
                 AND r.estado IN ('pendiente', 'confirmada')
                 ORDER BY r.fecha_solicitud DESC",
                $this->usuario_id
            ));
        }
        ?>
        <div class="biblioteca-dashboard-tab biblioteca-mis-reservas">
            <div class="tab-header">
                <h2>
                    <span class="dashicons dashicons-calendar"></span>
                    <?php esc_html_e('Mis Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="tab-description">
                    <?php esc_html_e('Libros que has reservado y están pendientes de disponibilidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <?php if (empty($reservas)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-calendar-alt"></span>
                    <h3><?php esc_html_e('No tienes reservas pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Puedes reservar libros que actualmente están prestados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/biblioteca/')); ?>" class="btn btn-primary">
                        <?php esc_html_e('Explorar catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="reservas-lista">
                    <?php foreach ($reservas as $reserva):
                        $fecha_expiracion = strtotime($reserva->fecha_expiracion);
                        $dias_para_expirar = floor(($fecha_expiracion - time()) / DAY_IN_SECONDS);
                        $estado_label = $this->obtener_label_estado_reserva($reserva->estado);
                    ?>
                        <div class="reserva-item estado-<?php echo esc_attr($reserva->estado); ?>">
                            <div class="reserva-portada">
                                <?php if (!empty($reserva->portada_url)): ?>
                                    <img src="<?php echo esc_url($reserva->portada_url); ?>"
                                         alt="<?php echo esc_attr($reserva->libro_titulo); ?>">
                                <?php else: ?>
                                    <div class="sin-portada">
                                        <span class="dashicons dashicons-book-alt"></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="reserva-info">
                                <h4 class="libro-titulo"><?php echo esc_html($reserva->libro_titulo); ?></h4>

                                <?php if (!empty($reserva->libro_autor)): ?>
                                    <p class="libro-autor"><?php echo esc_html($reserva->libro_autor); ?></p>
                                <?php endif; ?>

                                <div class="reserva-detalles">
                                    <p class="fecha-reserva">
                                        <span class="dashicons dashicons-calendar"></span>
                                        <strong><?php esc_html_e('Reservado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                        <?php echo date_i18n(get_option('date_format'), strtotime($reserva->fecha_solicitud)); ?>
                                    </p>

                                    <?php if ($reserva->estado === 'pendiente' && $dias_para_expirar > 0): ?>
                                        <p class="fecha-expiracion">
                                            <span class="dashicons dashicons-clock"></span>
                                            <?php printf(
                                                esc_html__('Expira en %d días', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                                $dias_para_expirar
                                            ); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <span class="estado-badge estado-<?php echo esc_attr($reserva->estado); ?>">
                                    <?php echo esc_html($estado_label); ?>
                                </span>
                            </div>

                            <div class="reserva-acciones">
                                <?php if ($reserva->estado === 'pendiente'): ?>
                                    <button type="button" class="btn btn-danger btn-cancelar-reserva"
                                            data-reserva-id="<?php echo esc_attr($reserva->id); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                        <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                <?php elseif ($reserva->estado === 'confirmada'): ?>
                                    <a href="<?php echo esc_url(home_url('/biblioteca/libro/' . $reserva->libro_id)); ?>"
                                       class="btn btn-primary">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php esc_html_e('Recoger', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Obtener label del estado de reserva
     *
     * @param string $estado Estado de la reserva
     * @return string
     */
    private function obtener_label_estado_reserva($estado) {
        $estados = [
            'pendiente' => __('En espera', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'confirmada' => __('Disponible para recoger', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cancelada' => __('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'expirada' => __('Expirada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'convertida' => __('Convertida en préstamo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        return $estados[$estado] ?? $estado;
    }

    // =========================================================================
    // TAB: HISTORIAL DE PRÉSTAMOS
    // =========================================================================

    /**
     * Renderizar tab de historial
     */
    public function render_tab_historial() {
        $this->encolar_assets();
        $this->usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_prestamos = $this->prefijo_tabla . 'flavor_biblioteca_prestamos';
        $tabla_libros = $this->prefijo_tabla . 'flavor_biblioteca_libros';

        $historial = [];
        $estadisticas = [
            'total_prestamos' => 0,
            'libros_leidos' => 0,
            'dias_promedio' => 0,
        ];

        if ($this->tabla_existe($tabla_prestamos)) {
            // Historial completo
            $historial = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, l.titulo as libro_titulo, l.autor as libro_autor,
                        l.portada_url, l.genero
                 FROM {$tabla_prestamos} p
                 LEFT JOIN {$tabla_libros} l ON p.libro_id = l.id
                 WHERE p.prestatario_id = %d
                 AND p.estado = 'devuelto'
                 ORDER BY p.fecha_devolucion_real DESC
                 LIMIT 50",
                $this->usuario_id
            ));

            // Estadísticas
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT
                    COUNT(*) as total,
                    COUNT(DISTINCT libro_id) as libros_unicos,
                    AVG(DATEDIFF(fecha_devolucion_real, fecha_prestamo)) as dias_promedio
                 FROM {$tabla_prestamos}
                 WHERE prestatario_id = %d AND estado = 'devuelto'",
                $this->usuario_id
            ));

            if ($stats) {
                $estadisticas = [
                    'total_prestamos' => intval($stats->total),
                    'libros_leidos' => intval($stats->libros_unicos),
                    'dias_promedio' => round(floatval($stats->dias_promedio), 1),
                ];
            }
        }
        ?>
        <div class="biblioteca-dashboard-tab biblioteca-historial">
            <div class="tab-header">
                <h2>
                    <span class="dashicons dashicons-clock"></span>
                    <?php esc_html_e('Historial de Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="tab-description">
                    <?php esc_html_e('Todos los libros que has leído a través de la biblioteca.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <?php if ($estadisticas['total_prestamos'] > 0): ?>
                <div class="estadisticas-lectura">
                    <div class="stat-card">
                        <span class="stat-valor"><?php echo esc_html($estadisticas['total_prestamos']); ?></span>
                        <span class="stat-label"><?php esc_html_e('Préstamos totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-valor"><?php echo esc_html($estadisticas['libros_leidos']); ?></span>
                        <span class="stat-label"><?php esc_html_e('Libros diferentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-valor"><?php echo esc_html($estadisticas['dias_promedio']); ?></span>
                        <span class="stat-label"><?php esc_html_e('Días promedio/libro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($historial)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-book-alt"></span>
                    <h3><?php esc_html_e('Sin historial de préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Cuando devuelvas libros, aparecerán aquí.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="historial-timeline">
                    <?php
                    $mes_actual = '';
                    foreach ($historial as $item):
                        $fecha_devolucion = strtotime($item->fecha_devolucion_real);
                        $mes_item = date_i18n('F Y', $fecha_devolucion);

                        if ($mes_item !== $mes_actual):
                            $mes_actual = $mes_item;
                    ?>
                        <div class="timeline-mes">
                            <h3><?php echo esc_html(ucfirst($mes_actual)); ?></h3>
                        </div>
                    <?php endif; ?>

                        <div class="historial-item">
                            <div class="historial-portada">
                                <?php if (!empty($item->portada_url)): ?>
                                    <img src="<?php echo esc_url($item->portada_url); ?>"
                                         alt="<?php echo esc_attr($item->libro_titulo); ?>">
                                <?php else: ?>
                                    <div class="sin-portada">
                                        <span class="dashicons dashicons-book-alt"></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="historial-info">
                                <h4 class="libro-titulo"><?php echo esc_html($item->libro_titulo); ?></h4>

                                <?php if (!empty($item->libro_autor)): ?>
                                    <p class="libro-autor"><?php echo esc_html($item->libro_autor); ?></p>
                                <?php endif; ?>

                                <div class="historial-fechas">
                                    <span class="fecha-periodo">
                                        <?php
                                        $inicio = date_i18n('j M', strtotime($item->fecha_prestamo));
                                        $fin = date_i18n('j M Y', $fecha_devolucion);
                                        echo esc_html("{$inicio} - {$fin}");
                                        ?>
                                    </span>

                                    <?php
                                    $dias_prestamo = floor(($fecha_devolucion - strtotime($item->fecha_prestamo)) / DAY_IN_SECONDS);
                                    ?>
                                    <span class="duracion">
                                        <?php printf(esc_html__('%d días', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias_prestamo); ?>
                                    </span>
                                </div>

                                <?php if (!empty($item->genero)): ?>
                                    <span class="genero-badge"><?php echo esc_html($item->genero); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="historial-acciones">
                                <button type="button" class="btn btn-icon btn-agregar-favorito"
                                        data-libro-id="<?php echo esc_attr($item->libro_id); ?>"
                                        title="<?php esc_attr_e('Agregar a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-heart"></span>
                                </button>

                                <?php if (!empty($item->valoracion_libro)): ?>
                                    <div class="valoracion-dada">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="estrella <?php echo $i <= $item->valoracion_libro ? 'llena' : 'vacia'; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline btn-valorar"
                                            data-libro-id="<?php echo esc_attr($item->libro_id); ?>">
                                        <?php esc_html_e('Valorar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // =========================================================================
    // TAB: LIBROS FAVORITOS
    // =========================================================================

    /**
     * Renderizar tab de favoritos
     */
    public function render_tab_favoritos() {
        $this->encolar_assets();
        $this->usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_favoritos = $this->prefijo_tabla . 'flavor_biblioteca_favoritos';
        $tabla_libros = $this->prefijo_tabla . 'flavor_biblioteca_libros';

        $favoritos = [];

        if ($this->tabla_existe($tabla_favoritos) && $this->tabla_existe($tabla_libros)) {
            $favoritos = $wpdb->get_results($wpdb->prepare(
                "SELECT f.*, l.titulo, l.autor, l.portada_url, l.isbn, l.genero,
                        l.disponibilidad, l.valoracion_media, u.display_name as propietario_nombre
                 FROM {$tabla_favoritos} f
                 LEFT JOIN {$tabla_libros} l ON f.libro_id = l.id
                 LEFT JOIN {$wpdb->users} u ON l.propietario_id = u.ID
                 WHERE f.usuario_id = %d
                 ORDER BY f.fecha_agregado DESC",
                $this->usuario_id
            ));
        }
        ?>
        <div class="biblioteca-dashboard-tab biblioteca-favoritos">
            <div class="tab-header">
                <h2>
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Mis Libros Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="tab-description">
                    <?php esc_html_e('Libros que has marcado como favoritos para leer más adelante.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <?php if (empty($favoritos)): ?>
                <div class="empty-state">
                    <span class="empty-icon dashicons dashicons-heart"></span>
                    <h3><?php esc_html_e('Sin libros favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php esc_html_e('Marca libros como favoritos para encontrarlos fácilmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/biblioteca/')); ?>" class="btn btn-primary">
                        <?php esc_html_e('Explorar catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="favoritos-grid">
                    <?php foreach ($favoritos as $favorito):
                        $disponible = $favorito->disponibilidad === 'disponible';
                    ?>
                        <div class="favorito-card <?php echo $disponible ? 'disponible' : 'no-disponible'; ?>">
                            <div class="favorito-portada">
                                <?php if (!empty($favorito->portada_url)): ?>
                                    <img src="<?php echo esc_url($favorito->portada_url); ?>"
                                         alt="<?php echo esc_attr($favorito->titulo); ?>">
                                <?php else: ?>
                                    <div class="sin-portada">
                                        <span class="dashicons dashicons-book-alt"></span>
                                    </div>
                                <?php endif; ?>

                                <span class="disponibilidad-badge <?php echo $disponible ? 'disponible' : 'no-disponible'; ?>">
                                    <?php echo $disponible ? esc_html__('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Prestado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>

                                <button type="button" class="btn-quitar-favorito"
                                        data-favorito-id="<?php echo esc_attr($favorito->id); ?>"
                                        data-libro-id="<?php echo esc_attr($favorito->libro_id); ?>"
                                        title="<?php esc_attr_e('Quitar de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-heart"></span>
                                </button>
                            </div>

                            <div class="favorito-info">
                                <h4 class="libro-titulo">
                                    <a href="<?php echo esc_url(home_url('/biblioteca/libro/' . $favorito->libro_id)); ?>">
                                        <?php echo esc_html($favorito->titulo); ?>
                                    </a>
                                </h4>

                                <?php if (!empty($favorito->autor)): ?>
                                    <p class="libro-autor"><?php echo esc_html($favorito->autor); ?></p>
                                <?php endif; ?>

                                <?php if ($favorito->valoracion_media > 0): ?>
                                    <div class="valoracion">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="estrella <?php echo $i <= round($favorito->valoracion_media) ? 'llena' : 'vacia'; ?>">★</span>
                                        <?php endfor; ?>
                                        <span class="valoracion-numero"><?php echo number_format($favorito->valoracion_media, 1); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($favorito->genero)): ?>
                                    <span class="genero-badge"><?php echo esc_html($favorito->genero); ?></span>
                                <?php endif; ?>

                                <p class="fecha-agregado">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php printf(
                                        esc_html__('Agregado el %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                        date_i18n(get_option('date_format'), strtotime($favorito->fecha_agregado))
                                    ); ?>
                                </p>

                                <?php if (!empty($favorito->notas)): ?>
                                    <p class="notas-favorito">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php echo esc_html($favorito->notas); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="favorito-acciones">
                                <?php if ($disponible): ?>
                                    <button type="button" class="btn btn-primary btn-reservar"
                                            data-libro-id="<?php echo esc_attr($favorito->libro_id); ?>">
                                        <?php esc_html_e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline btn-lista-espera"
                                            data-libro-id="<?php echo esc_attr($favorito->libro_id); ?>">
                                        <?php esc_html_e('Lista de espera', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                <?php endif; ?>

                                <a href="<?php echo esc_url(home_url('/biblioteca/libro/' . $favorito->libro_id)); ?>"
                                   class="btn btn-secondary">
                                    <?php esc_html_e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Toggle favorito (agregar/quitar)
     */
    public function ajax_toggle_favorito() {
        check_ajax_referer('biblioteca_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $libro_id = intval($_POST['libro_id'] ?? 0);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');
        $accion = sanitize_text_field($_POST['accion'] ?? 'toggle');

        if (!$libro_id) {
            wp_send_json_error(['mensaje' => __('ID de libro inválido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_favoritos = $this->prefijo_tabla . 'flavor_biblioteca_favoritos';
        $usuario_id = get_current_user_id();

        // Verificar si ya es favorito
        $favorito_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_favoritos} WHERE usuario_id = %d AND libro_id = %d",
            $usuario_id,
            $libro_id
        ));

        if ($accion === 'eliminar' || ($accion === 'toggle' && $favorito_existente)) {
            // Eliminar de favoritos
            $wpdb->delete($tabla_favoritos, [
                'usuario_id' => $usuario_id,
                'libro_id' => $libro_id,
            ]);

            wp_send_json_success([
                'accion' => 'eliminado',
                'mensaje' => __('Libro eliminado de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            // Agregar a favoritos
            $resultado = $wpdb->insert($tabla_favoritos, [
                'usuario_id' => $usuario_id,
                'libro_id' => $libro_id,
                'notas' => $notas,
                'fecha_agregado' => current_time('mysql'),
            ]);

            if ($resultado) {
                wp_send_json_success([
                    'accion' => 'agregado',
                    'favorito_id' => $wpdb->insert_id,
                    'mensaje' => __('Libro agregado a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ]);
            } else {
                wp_send_json_error(['mensaje' => __('Error al agregar a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }
        }
    }

    /**
     * AJAX: Obtener lista de favoritos del usuario
     */
    public function ajax_obtener_favoritos() {
        check_ajax_referer('biblioteca_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_favoritos = $this->prefijo_tabla . 'flavor_biblioteca_favoritos';
        $usuario_id = get_current_user_id();

        $favoritos_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT libro_id FROM {$tabla_favoritos} WHERE usuario_id = %d",
            $usuario_id
        ));

        wp_send_json_success([
            'favoritos' => array_map('intval', $favoritos_ids),
        ]);
    }

    /**
     * Verificar si un libro es favorito del usuario actual
     *
     * @param int $libro_id ID del libro
     * @return bool
     */
    public function es_favorito($libro_id) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $tabla_favoritos = $this->prefijo_tabla . 'flavor_biblioteca_favoritos';

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_favoritos} WHERE usuario_id = %d AND libro_id = %d",
            get_current_user_id(),
            $libro_id
        ));

        return !empty($existe);
    }
}
