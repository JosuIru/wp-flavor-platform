<?php
/**
 * Dashboard Tab para el módulo de Bares
 *
 * Proporciona tabs en el dashboard del cliente para gestionar
 * reservas, valoraciones y favoritos del usuario.
 *
 * @package FlavorChatIA
 * @subpackage Modules\Bares
 * @since 4.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar los tabs del dashboard de bares
 */
class Flavor_Bares_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Bares_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Nombres de las tablas
     */
    private $tabla_bares;
    private $tabla_reservas;
    private $tabla_valoraciones;
    private $tabla_favoritos;

    /**
     * Etiquetas de tipos de establecimiento
     */
    private $etiquetas_tipos = [];

    /**
     * Etiquetas de estados de reserva
     */
    private $etiquetas_estados_reserva = [];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_bares = $wpdb->prefix . 'flavor_bares';
        $this->tabla_reservas = $wpdb->prefix . 'flavor_bares_reservas';
        $this->tabla_valoraciones = $wpdb->prefix . 'flavor_bares_valoraciones';
        $this->tabla_favoritos = $wpdb->prefix . 'flavor_bares_favoritos';

        $this->etiquetas_tipos = [
            'bar'         => __('Bar', 'flavor-chat-ia'),
            'restaurante' => __('Restaurante', 'flavor-chat-ia'),
            'cafeteria'   => __('Cafetería', 'flavor-chat-ia'),
            'pub'         => __('Pub', 'flavor-chat-ia'),
            'terraza'     => __('Terraza', 'flavor-chat-ia'),
            'cocteleria'  => __('Coctelería', 'flavor-chat-ia'),
        ];

        $this->etiquetas_estados_reserva = [
            'pendiente'  => __('Pendiente', 'flavor-chat-ia'),
            'confirmada' => __('Confirmada', 'flavor-chat-ia'),
            'cancelada'  => __('Cancelada', 'flavor-chat-ia'),
            'completada' => __('Completada', 'flavor-chat-ia'),
            'no_show'    => __('No presentado', 'flavor-chat-ia'),
        ];

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Bares_Dashboard_Tab
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
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 25);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_flavor_bares_toggle_favorito', [$this, 'ajax_toggle_favorito']);
        add_action('wp_ajax_flavor_bares_cancelar_reserva', [$this, 'ajax_cancelar_reserva']);
    }

    /**
     * Registra los tabs del módulo en el dashboard
     *
     * @param array $tabs Tabs existentes
     * @return array
     */
    public function registrar_tabs($tabs) {
        // Tab principal: Resumen de Bares
        $tabs['bares-resumen'] = [
            'label'    => __('Bares y Restaurantes', 'flavor-chat-ia'),
            'icon'     => 'food',
            'callback' => [$this, 'render_tab_resumen'],
            'orden'    => 70,
        ];

        // Tab: Mis Reservas
        $tabs['bares-mis-reservas'] = [
            'label'    => __('Mis Reservas', 'flavor-chat-ia'),
            'icon'     => 'calendar-alt',
            'callback' => [$this, 'render_tab_mis_reservas'],
            'orden'    => 71,
        ];

        // Tab: Mis Valoraciones
        $tabs['bares-mis-valoraciones'] = [
            'label'    => __('Mis Reseñas', 'flavor-chat-ia'),
            'icon'     => 'star-filled',
            'callback' => [$this, 'render_tab_mis_valoraciones'],
            'orden'    => 72,
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de resumen
     */
    public function render_tab_resumen() {
        $identificador_usuario = get_current_user_id();
        if (!$identificador_usuario) {
            echo '<p class="flavor-alert flavor-alert-warning">' .
                 esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;

        // Verificar que las tablas existen
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_bares)) {
            echo '<div class="flavor-alert flavor-alert-info">' .
                 esc_html__('El módulo de bares no está configurado.', 'flavor-chat-ia') . '</div>';
            return;
        }

        // KPIs del usuario
        $total_bares_disponibles = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_bares} WHERE estado = 'activo'"
        );

        $total_reservas_usuario = 0;
        $reservas_proximas = 0;
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_reservas)) {
            $total_reservas_usuario = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_reservas} WHERE user_id = %d",
                $identificador_usuario
            ));

            $reservas_proximas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_reservas}
                 WHERE user_id = %d AND estado IN ('pendiente', 'confirmada') AND fecha >= CURDATE()",
                $identificador_usuario
            ));
        }

        $total_valoraciones_usuario = 0;
        $bares_valorados = 0;
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_valoraciones)) {
            $total_valoraciones_usuario = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_valoraciones} WHERE user_id = %d",
                $identificador_usuario
            ));
            $bares_valorados = $total_valoraciones_usuario;
        }

        $total_favoritos = 0;
        if (Flavor_Chat_Helpers::tabla_existe($this->tabla_favoritos)) {
            $total_favoritos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_favoritos} WHERE user_id = %d",
                $identificador_usuario
            ));
        }

        // Obtener bares favoritos del usuario
        $bares_favoritos = $this->obtener_bares_favoritos($identificador_usuario, 4);

        // Obtener próximas reservas
        $proximas_reservas_listado = $this->obtener_proximas_reservas($identificador_usuario, 3);

        // Obtener bares mejor valorados
        $bares_destacados = $wpdb->get_results(
            "SELECT * FROM {$this->tabla_bares}
             WHERE estado = 'activo' AND valoraciones_count >= 1
             ORDER BY valoracion_media DESC, valoraciones_count DESC
             LIMIT 4"
        );

        ?>
        <div class="flavor-panel flavor-bares-dashboard-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-food"></span>
                    <?php esc_html_e('Bares y Restaurantes', 'flavor-chat-ia'); ?>
                </h2>
                <p class="flavor-panel-subtitle">
                    <?php esc_html_e('Descubre, reserva y valora los mejores locales de hostelería.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <!-- KPIs -->
            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-store"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_bares_disponibles); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Locales Disponibles', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-highlight">
                    <span class="flavor-kpi-icon dashicons dashicons-calendar-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($reservas_proximas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Reservas Próximas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-star-filled"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($bares_valorados); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Bares Valorados', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-heart"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_favoritos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Favoritos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(home_url('/bares/')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Explorar Bares', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/bares/mapa-bares/')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-location"></span>
                    <?php esc_html_e('Ver en Mapa', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- Próximas Reservas -->
            <?php if (!empty($proximas_reservas_listado)): ?>
                <div class="flavor-panel-section">
                    <h3>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Próximas Reservas', 'flavor-chat-ia'); ?>
                    </h3>
                    <div class="flavor-cards-list">
                        <?php foreach ($proximas_reservas_listado as $reserva): ?>
                            <div class="flavor-list-item flavor-reserva-item">
                                <div class="flavor-list-item-icon">
                                    <span class="dashicons dashicons-food"></span>
                                </div>
                                <div class="flavor-list-item-content">
                                    <strong><?php echo esc_html($reserva->bar_nombre); ?></strong>
                                    <span class="flavor-meta">
                                        <?php echo esc_html(date_i18n('d M Y', strtotime($reserva->fecha))); ?>
                                        <?php esc_html_e('a las', 'flavor-chat-ia'); ?>
                                        <?php echo esc_html(substr($reserva->hora, 0, 5)); ?>
                                        - <?php echo esc_html($reserva->comensales); ?> <?php esc_html_e('personas', 'flavor-chat-ia'); ?>
                                    </span>
                                </div>
                                <div class="flavor-list-item-badge">
                                    <span class="flavor-badge flavor-badge-<?php echo esc_attr($reserva->estado); ?>">
                                        <?php echo esc_html($this->etiquetas_estados_reserva[$reserva->estado] ?? ucfirst($reserva->estado)); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="#" class="flavor-link-ver-mas" data-tab="bares-mis-reservas">
                        <?php esc_html_e('Ver todas las reservas', 'flavor-chat-ia'); ?> &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <!-- Mis Favoritos -->
            <?php if (!empty($bares_favoritos)): ?>
                <div class="flavor-panel-section">
                    <h3>
                        <span class="dashicons dashicons-heart"></span>
                        <?php esc_html_e('Mis Favoritos', 'flavor-chat-ia'); ?>
                    </h3>
                    <div class="flavor-cards-grid flavor-grid-4">
                        <?php foreach ($bares_favoritos as $bar): ?>
                            <?php $this->render_card_bar_mini($bar); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Bares Destacados -->
            <?php if (!empty($bares_destacados)): ?>
                <div class="flavor-panel-section">
                    <h3>
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php esc_html_e('Mejor Valorados', 'flavor-chat-ia'); ?>
                    </h3>
                    <div class="flavor-cards-grid flavor-grid-4">
                        <?php foreach ($bares_destacados as $bar): ?>
                            <?php $this->render_card_bar_mini($bar); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de mis reservas
     */
    public function render_tab_mis_reservas() {
        $identificador_usuario = get_current_user_id();
        if (!$identificador_usuario) {
            echo '<p class="flavor-alert flavor-alert-warning">' .
                 esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_reservas)) {
            echo '<div class="flavor-alert flavor-alert-info">' .
                 esc_html__('El sistema de reservas no está configurado.', 'flavor-chat-ia') . '</div>';
            return;
        }

        // Contadores por estado
        $contador_proximas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_reservas}
             WHERE user_id = %d AND estado IN ('pendiente', 'confirmada') AND fecha >= CURDATE()",
            $identificador_usuario
        ));

        $contador_pasadas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_reservas}
             WHERE user_id = %d AND (estado = 'completada' OR fecha < CURDATE())",
            $identificador_usuario
        ));

        $contador_canceladas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_reservas}
             WHERE user_id = %d AND estado = 'cancelada'",
            $identificador_usuario
        ));

        // Obtener reservas según filtro
        $filtro_actual = isset($_GET['filtro_reservas']) ? sanitize_text_field($_GET['filtro_reservas']) : 'proximas';

        $condicion_extra = '';
        switch ($filtro_actual) {
            case 'pasadas':
                $condicion_extra = "AND (estado = 'completada' OR fecha < CURDATE())";
                break;
            case 'canceladas':
                $condicion_extra = "AND estado = 'cancelada'";
                break;
            case 'todas':
                $condicion_extra = '';
                break;
            default: // proximas
                $condicion_extra = "AND estado IN ('pendiente', 'confirmada') AND fecha >= CURDATE()";
                break;
        }

        $reservas_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, b.nombre as bar_nombre, b.direccion as bar_direccion, b.telefono as bar_telefono, b.imagen as bar_imagen, b.tipo as bar_tipo
             FROM {$this->tabla_reservas} r
             LEFT JOIN {$this->tabla_bares} b ON r.bar_id = b.id
             WHERE r.user_id = %d {$condicion_extra}
             ORDER BY r.fecha DESC, r.hora DESC
             LIMIT 50",
            $identificador_usuario
        ));

        ?>
        <div class="flavor-panel flavor-reservas-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Mis Reservas', 'flavor-chat-ia'); ?>
                </h2>
                <a href="<?php echo esc_url(home_url('/bares/')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Nueva Reserva', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- Filtros tipo tabs -->
            <div class="flavor-filter-tabs">
                <a href="?tab=bares-mis-reservas&filtro_reservas=proximas"
                   class="flavor-filter-tab <?php echo ($filtro_actual === 'proximas') ? 'active' : ''; ?>">
                    <?php esc_html_e('Próximas', 'flavor-chat-ia'); ?>
                    <span class="flavor-badge-count"><?php echo $contador_proximas; ?></span>
                </a>
                <a href="?tab=bares-mis-reservas&filtro_reservas=pasadas"
                   class="flavor-filter-tab <?php echo ($filtro_actual === 'pasadas') ? 'active' : ''; ?>">
                    <?php esc_html_e('Pasadas', 'flavor-chat-ia'); ?>
                    <span class="flavor-badge-count"><?php echo $contador_pasadas; ?></span>
                </a>
                <a href="?tab=bares-mis-reservas&filtro_reservas=canceladas"
                   class="flavor-filter-tab <?php echo ($filtro_actual === 'canceladas') ? 'active' : ''; ?>">
                    <?php esc_html_e('Canceladas', 'flavor-chat-ia'); ?>
                    <span class="flavor-badge-count"><?php echo $contador_canceladas; ?></span>
                </a>
                <a href="?tab=bares-mis-reservas&filtro_reservas=todas"
                   class="flavor-filter-tab <?php echo ($filtro_actual === 'todas') ? 'active' : ''; ?>">
                    <?php esc_html_e('Todas', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- Listado de reservas -->
            <?php if (empty($reservas_usuario)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <h3><?php esc_html_e('No tienes reservas', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Explora los bares y restaurantes disponibles para hacer tu primera reserva.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/bares/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar Bares', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid flavor-grid-2">
                    <?php foreach ($reservas_usuario as $reserva): ?>
                        <?php $this->render_card_reserva($reserva); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el tab de mis valoraciones/reseñas
     */
    public function render_tab_mis_valoraciones() {
        $identificador_usuario = get_current_user_id();
        if (!$identificador_usuario) {
            echo '<p class="flavor-alert flavor-alert-warning">' .
                 esc_html__('Debes iniciar sesión para ver este contenido.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_valoraciones)) {
            echo '<div class="flavor-alert flavor-alert-info">' .
                 esc_html__('El sistema de valoraciones no está configurado.', 'flavor-chat-ia') . '</div>';
            return;
        }

        // Estadísticas de valoraciones del usuario
        $estadisticas_valoraciones = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total, AVG(puntuacion) as media_dada
             FROM {$this->tabla_valoraciones}
             WHERE user_id = %d",
            $identificador_usuario
        ));

        $total_valoraciones = (int) ($estadisticas_valoraciones->total ?? 0);
        $media_puntuacion_dada = round(floatval($estadisticas_valoraciones->media_dada ?? 0), 1);

        // Obtener valoraciones del usuario
        $valoraciones_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, b.nombre as bar_nombre, b.tipo as bar_tipo, b.imagen as bar_imagen, b.direccion as bar_direccion, b.valoracion_media as bar_valoracion
             FROM {$this->tabla_valoraciones} v
             LEFT JOIN {$this->tabla_bares} b ON v.bar_id = b.id
             WHERE v.user_id = %d
             ORDER BY v.created_at DESC
             LIMIT 50",
            $identificador_usuario
        ));

        ?>
        <div class="flavor-panel flavor-valoraciones-panel">
            <div class="flavor-panel-header">
                <h2>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Mis Reseñas y Valoraciones', 'flavor-chat-ia'); ?>
                </h2>
                <p class="flavor-panel-subtitle">
                    <?php esc_html_e('Historial de locales que has valorado.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <!-- KPIs de valoraciones -->
            <div class="flavor-panel-kpis flavor-kpis-small">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-edit"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_valoraciones); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Reseñas Escritas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-star-filled"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo $media_puntuacion_dada; ?>/5</span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Puntuación Media', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Listado de valoraciones -->
            <?php if (empty($valoraciones_usuario)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-star-empty"></span>
                    <h3><?php esc_html_e('Aún no has valorado ningún local', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Visita bares y restaurantes y comparte tu experiencia con la comunidad.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/bares/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar Bares', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid flavor-grid-2">
                    <?php foreach ($valoraciones_usuario as $valoracion): ?>
                        <?php $this->render_card_valoracion($valoracion); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza una tarjeta de bar en formato mini
     *
     * @param object $bar Datos del bar
     */
    private function render_card_bar_mini($bar) {
        $imagen_url = !empty($bar->imagen) ? esc_url($bar->imagen) : '';
        $tipo_etiqueta = $this->etiquetas_tipos[$bar->tipo] ?? ucfirst($bar->tipo);
        $url_bar = add_query_arg('bar_id', $bar->id, home_url('/bares/'));

        ?>
        <div class="flavor-card flavor-bar-card-mini">
            <?php if ($imagen_url): ?>
                <div class="flavor-card-image" style="background-image: url('<?php echo $imagen_url; ?>');"></div>
            <?php else: ?>
                <div class="flavor-card-image flavor-card-image-placeholder">
                    <span class="dashicons dashicons-food"></span>
                </div>
            <?php endif; ?>
            <div class="flavor-card-body">
                <h4 class="flavor-card-title">
                    <a href="<?php echo esc_url($url_bar); ?>"><?php echo esc_html($bar->nombre); ?></a>
                </h4>
                <span class="flavor-card-meta"><?php echo esc_html($tipo_etiqueta); ?></span>
                <div class="flavor-card-rating">
                    <?php echo $this->generar_estrellas_html($bar->valoracion_media); ?>
                    <span class="flavor-rating-count">(<?php echo (int) $bar->valoraciones_count; ?>)</span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza una tarjeta de reserva
     *
     * @param object $reserva Datos de la reserva
     */
    private function render_card_reserva($reserva) {
        $imagen_url = !empty($reserva->bar_imagen) ? esc_url($reserva->bar_imagen) : '';
        $tipo_etiqueta = $this->etiquetas_tipos[$reserva->bar_tipo] ?? ucfirst($reserva->bar_tipo ?? '');
        $estado_etiqueta = $this->etiquetas_estados_reserva[$reserva->estado] ?? ucfirst($reserva->estado);
        $fecha_formateada = date_i18n('l, d \d\e F Y', strtotime($reserva->fecha));
        $hora_formateada = substr($reserva->hora, 0, 5);
        $puede_cancelar = in_array($reserva->estado, ['pendiente', 'confirmada']) && strtotime($reserva->fecha) > time();

        $clase_estado = 'flavor-badge-' . $reserva->estado;

        ?>
        <div class="flavor-card flavor-reserva-card">
            <div class="flavor-card-header">
                <?php if ($imagen_url): ?>
                    <div class="flavor-card-thumb" style="background-image: url('<?php echo $imagen_url; ?>');"></div>
                <?php else: ?>
                    <div class="flavor-card-thumb flavor-card-thumb-placeholder">
                        <span class="dashicons dashicons-food"></span>
                    </div>
                <?php endif; ?>
                <div class="flavor-card-header-content">
                    <h4><?php echo esc_html($reserva->bar_nombre ?: __('Local eliminado', 'flavor-chat-ia')); ?></h4>
                    <span class="flavor-badge <?php echo esc_attr($clase_estado); ?>">
                        <?php echo esc_html($estado_etiqueta); ?>
                    </span>
                </div>
            </div>
            <div class="flavor-card-body">
                <div class="flavor-reserva-detalles">
                    <div class="flavor-detalle">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php echo esc_html($fecha_formateada); ?></span>
                    </div>
                    <div class="flavor-detalle">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php echo esc_html($hora_formateada); ?></span>
                    </div>
                    <div class="flavor-detalle">
                        <span class="dashicons dashicons-groups"></span>
                        <span><?php echo esc_html($reserva->comensales); ?> <?php esc_html_e('personas', 'flavor-chat-ia'); ?></span>
                    </div>
                    <?php if (!empty($reserva->bar_direccion)): ?>
                        <div class="flavor-detalle">
                            <span class="dashicons dashicons-location"></span>
                            <span><?php echo esc_html(wp_trim_words($reserva->bar_direccion, 6)); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($reserva->notas)): ?>
                    <div class="flavor-reserva-notas">
                        <small><strong><?php esc_html_e('Notas:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($reserva->notas); ?></small>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($puede_cancelar): ?>
                <div class="flavor-card-footer">
                    <button type="button" class="flavor-btn flavor-btn-danger flavor-btn-sm flavor-btn-cancelar-reserva"
                            data-reserva-id="<?php echo esc_attr($reserva->id); ?>"
                            data-bar-nombre="<?php echo esc_attr($reserva->bar_nombre); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php esc_html_e('Cancelar Reserva', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza una tarjeta de valoración
     *
     * @param object $valoracion Datos de la valoración
     */
    private function render_card_valoracion($valoracion) {
        $imagen_url = !empty($valoracion->bar_imagen) ? esc_url($valoracion->bar_imagen) : '';
        $tipo_etiqueta = $this->etiquetas_tipos[$valoracion->bar_tipo] ?? ucfirst($valoracion->bar_tipo ?? '');
        $fecha_formateada = date_i18n('d \d\e F \d\e Y', strtotime($valoracion->created_at));
        $url_bar = add_query_arg('bar_id', $valoracion->bar_id, home_url('/bares/'));

        ?>
        <div class="flavor-card flavor-valoracion-card">
            <div class="flavor-card-header">
                <?php if ($imagen_url): ?>
                    <div class="flavor-card-thumb" style="background-image: url('<?php echo $imagen_url; ?>');"></div>
                <?php else: ?>
                    <div class="flavor-card-thumb flavor-card-thumb-placeholder">
                        <span class="dashicons dashicons-food"></span>
                    </div>
                <?php endif; ?>
                <div class="flavor-card-header-content">
                    <h4>
                        <a href="<?php echo esc_url($url_bar); ?>">
                            <?php echo esc_html($valoracion->bar_nombre ?: __('Local eliminado', 'flavor-chat-ia')); ?>
                        </a>
                    </h4>
                    <span class="flavor-text-muted"><?php echo esc_html($tipo_etiqueta); ?></span>
                </div>
            </div>
            <div class="flavor-card-body">
                <div class="flavor-valoracion-puntuacion">
                    <span class="flavor-puntuacion-texto"><?php esc_html_e('Mi puntuación:', 'flavor-chat-ia'); ?></span>
                    <div class="flavor-puntuacion-estrellas">
                        <?php echo $this->generar_estrellas_html($valoracion->puntuacion); ?>
                        <strong><?php echo esc_html($valoracion->puntuacion); ?>/5</strong>
                    </div>
                </div>
                <?php if (!empty($valoracion->comentario)): ?>
                    <div class="flavor-valoracion-comentario">
                        <p><?php echo esc_html($valoracion->comentario); ?></p>
                    </div>
                <?php endif; ?>
                <div class="flavor-valoracion-meta">
                    <span class="flavor-text-muted">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo esc_html($fecha_formateada); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene los bares favoritos del usuario
     *
     * @param int $user_id ID del usuario
     * @param int $limite Límite de resultados
     * @return array
     */
    private function obtener_bares_favoritos($user_id, $limite = 10) {
        global $wpdb;

        // Verificar si existe la tabla de favoritos
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_favoritos)) {
            // Si no hay tabla de favoritos, devolver bares valorados por el usuario como alternativa
            return $wpdb->get_results($wpdb->prepare(
                "SELECT b.*, v.puntuacion as mi_puntuacion
                 FROM {$this->tabla_bares} b
                 INNER JOIN {$this->tabla_valoraciones} v ON b.id = v.bar_id
                 WHERE v.user_id = %d AND b.estado = 'activo'
                 ORDER BY v.puntuacion DESC, v.created_at DESC
                 LIMIT %d",
                $user_id,
                $limite
            ));
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT b.*
             FROM {$this->tabla_bares} b
             INNER JOIN {$this->tabla_favoritos} f ON b.id = f.bar_id
             WHERE f.user_id = %d AND b.estado = 'activo'
             ORDER BY f.created_at DESC
             LIMIT %d",
            $user_id,
            $limite
        ));
    }

    /**
     * Obtiene las próximas reservas del usuario
     *
     * @param int $user_id ID del usuario
     * @param int $limite Límite de resultados
     * @return array
     */
    private function obtener_proximas_reservas($user_id, $limite = 5) {
        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_reservas)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, b.nombre as bar_nombre, b.direccion as bar_direccion
             FROM {$this->tabla_reservas} r
             LEFT JOIN {$this->tabla_bares} b ON r.bar_id = b.id
             WHERE r.user_id = %d AND r.estado IN ('pendiente', 'confirmada') AND r.fecha >= CURDATE()
             ORDER BY r.fecha ASC, r.hora ASC
             LIMIT %d",
            $user_id,
            $limite
        ));
    }

    /**
     * Genera HTML de estrellas para una valoración
     *
     * @param float $valoracion Valoración (0-5)
     * @return string HTML de estrellas
     */
    private function generar_estrellas_html($valoracion) {
        $valoracion = floatval($valoracion);
        $estrellas_completas = floor($valoracion);
        $tiene_media_estrella = ($valoracion - $estrellas_completas) >= 0.5;
        $estrellas_vacias = 5 - $estrellas_completas - ($tiene_media_estrella ? 1 : 0);

        $html = '<span class="flavor-estrellas">';

        for ($i = 0; $i < $estrellas_completas; $i++) {
            $html .= '<span class="dashicons dashicons-star-filled" style="color: #f59e0b;"></span>';
        }

        if ($tiene_media_estrella) {
            $html .= '<span class="dashicons dashicons-star-half" style="color: #f59e0b;"></span>';
        }

        for ($i = 0; $i < $estrellas_vacias; $i++) {
            $html .= '<span class="dashicons dashicons-star-empty" style="color: #d1d5db;"></span>';
        }

        $html .= '</span>';

        return $html;
    }

    /**
     * AJAX: Toggle favorito
     */
    public function ajax_toggle_favorito() {
        check_ajax_referer('flavor_bares_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $bar_id = absint($_POST['bar_id'] ?? 0);
        if (!$bar_id) {
            wp_send_json_error(['mensaje' => __('ID de bar no válido.', 'flavor-chat-ia')]);
        }

        global $wpdb;

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_favoritos)) {
            wp_send_json_error(['mensaje' => __('Sistema de favoritos no disponible.', 'flavor-chat-ia')]);
        }

        // Verificar si ya es favorito
        $es_favorito = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_favoritos} WHERE user_id = %d AND bar_id = %d",
            $user_id,
            $bar_id
        ));

        if ($es_favorito) {
            // Quitar de favoritos
            $wpdb->delete($this->tabla_favoritos, ['user_id' => $user_id, 'bar_id' => $bar_id], ['%d', '%d']);
            wp_send_json_success([
                'es_favorito' => false,
                'mensaje' => __('Bar eliminado de favoritos.', 'flavor-chat-ia'),
            ]);
        } else {
            // Añadir a favoritos
            $wpdb->insert(
                $this->tabla_favoritos,
                ['user_id' => $user_id, 'bar_id' => $bar_id, 'created_at' => current_time('mysql')],
                ['%d', '%d', '%s']
            );
            wp_send_json_success([
                'es_favorito' => true,
                'mensaje' => __('Bar añadido a favoritos.', 'flavor-chat-ia'),
            ]);
        }
    }

    /**
     * AJAX: Cancelar reserva
     */
    public function ajax_cancelar_reserva() {
        check_ajax_referer('flavor_bares_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $reserva_id = absint($_POST['reserva_id'] ?? 0);
        if (!$reserva_id) {
            wp_send_json_error(['mensaje' => __('ID de reserva no válido.', 'flavor-chat-ia')]);
        }

        global $wpdb;

        // Verificar propiedad de la reserva
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_reservas} WHERE id = %d AND user_id = %d",
            $reserva_id,
            $user_id
        ));

        if (!$reserva) {
            wp_send_json_error(['mensaje' => __('Reserva no encontrada.', 'flavor-chat-ia')]);
        }

        if (!in_array($reserva->estado, ['pendiente', 'confirmada'])) {
            wp_send_json_error(['mensaje' => __('Esta reserva no puede ser cancelada.', 'flavor-chat-ia')]);
        }

        // Cancelar la reserva
        $resultado = $wpdb->update(
            $this->tabla_reservas,
            ['estado' => 'cancelada'],
            ['id' => $reserva_id],
            ['%s'],
            ['%d']
        );

        if ($resultado !== false) {
            wp_send_json_success(['mensaje' => __('Reserva cancelada correctamente.', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['mensaje' => __('Error al cancelar la reserva.', 'flavor-chat-ia')]);
        }
    }

    /**
     * Enqueue de assets para el dashboard
     */
    public function enqueue_assets() {
        if (!is_page() || !is_user_logged_in()) {
            return;
        }

        // Verificar si estamos en una página de dashboard
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($current_url, 'mi-portal') === false && strpos($current_url, 'dashboard') === false) {
            return;
        }

        wp_enqueue_style('dashicons');

        // Script para manejar favoritos y cancelaciones
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Cancelar reserva
                $(".flavor-btn-cancelar-reserva").on("click", function() {
                    var $btn = $(this);
                    var reservaId = $btn.data("reserva-id");
                    var barNombre = $btn.data("bar-nombre");

                    if (!confirm("¿Estás seguro de que deseas cancelar la reserva en " + barNombre + "?")) {
                        return;
                    }

                    $.ajax({
                        url: "' . admin_url('admin-ajax.php') . '",
                        type: "POST",
                        data: {
                            action: "flavor_bares_cancelar_reserva",
                            nonce: "' . wp_create_nonce('flavor_bares_nonce') . '",
                            reserva_id: reservaId
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.data.mensaje || "Error al cancelar la reserva");
                            }
                        },
                        error: function() {
                            alert("Error de conexión");
                        }
                    });
                });
            });
        ');
    }
}
