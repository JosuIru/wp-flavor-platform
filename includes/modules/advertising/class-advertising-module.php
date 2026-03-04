<?php
/**
 * Módulo de Publicidad Ética
 *
 * Sistema de anuncios éticos con reparto de beneficios a la comunidad.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Advertising_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        // Auto-registered AJAX handlers
        add_action('wp_ajax_advertising_obtener_stats', [$this, 'ajax_obtener_stats']);
        add_action('wp_ajax_nopriv_advertising_obtener_stats', [$this, 'ajax_obtener_stats']);
        add_action('wp_ajax_advertising_admin_aprobar', [$this, 'ajax_admin_aprobar']);
        add_action('wp_ajax_nopriv_advertising_admin_aprobar', [$this, 'ajax_admin_aprobar']);
        add_action('wp_ajax_advertising_admin_rechazar', [$this, 'ajax_admin_rechazar']);
        add_action('wp_ajax_nopriv_advertising_admin_rechazar', [$this, 'ajax_admin_rechazar']);
        add_action('wp_ajax_advertising_admin_procesar_pago', [$this, 'ajax_admin_procesar_pago']);
        add_action('wp_ajax_nopriv_advertising_admin_procesar_pago', [$this, 'ajax_admin_procesar_pago']);

        $this->id = 'advertising';
        $this->name = 'Publicidad Ética'; // Translation loaded on init
        $this->description = 'Sistema de anuncios éticos con reparto de beneficios.'; // Translation loaded on init

        parent::__construct();
    }

    public function can_activate() {
        return true;
    }

    public function get_activation_error() {
        return '';
    }

    
    /**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

/**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'advertising',
            'label' => __('Publicidad Ética', 'flavor-chat-ia'),
            'icon' => 'dashicons-megaphone',
            'capability' => 'manage_options',
            'categoria' => 'economia',
            'paginas' => [
                [
                    'slug' => 'advertising-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'advertising-anuncios',
                    'titulo' => __('Anuncios', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_anuncios'],
                    'badge' => [$this, 'contar_anuncios_pendientes'],
                ],
                [
                    'slug' => 'advertising-campanas',
                    'titulo' => __('Campañas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_campanas'],
                ],
                [
                    'slug' => 'advertising-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta anuncios pendientes de aprobación
     *
     * @return int
     */
    public function contar_anuncios_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        $anuncios_pendientes = get_posts([
            'post_type' => 'flavor_ad',
            'post_status' => 'pending',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        return count($anuncios_pendientes);
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';
        $estadisticas = [];

        // Anuncios activos
        $anuncios_activos = get_posts([
            'post_type' => 'flavor_ad',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        $total_anuncios_activos = count($anuncios_activos);

        $estadisticas[] = [
            'icon' => 'dashicons-megaphone',
            'valor' => $total_anuncios_activos,
            'label' => __('Anuncios activos', 'flavor-chat-ia'),
            'color' => $total_anuncios_activos > 0 ? 'blue' : 'gray',
            'enlace' => admin_url('admin.php?page=advertising-anuncios'),
        ];

        // Anuncios pendientes
        $anuncios_pendientes = $this->contar_anuncios_pendientes();
        if ($anuncios_pendientes > 0) {
            $estadisticas[] = [
                'icon' => 'dashicons-clock',
                'valor' => $anuncios_pendientes,
                'label' => __('Pendientes de aprobación', 'flavor-chat-ia'),
                'color' => 'orange',
                'enlace' => admin_url('admin.php?page=advertising-anuncios&estado=pending'),
            ];
        }

        // Ingresos del mes
        $tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_stats'") === $tabla_stats;
        if ($tabla_existe) {
            $primer_dia_mes = date('Y-m-01');
            $ingresos_mes = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(gasto), 0) FROM $tabla_stats WHERE fecha >= %s",
                $primer_dia_mes
            ));
            $estadisticas[] = [
                'icon' => 'dashicons-chart-bar',
                'valor' => number_format((float)$ingresos_mes, 2) . '€',
                'label' => __('Ingresos este mes', 'flavor-chat-ia'),
                'color' => (float)$ingresos_mes > 0 ? 'green' : 'gray',
                'enlace' => admin_url('admin.php?page=advertising-dashboard'),
            ];
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de publicidad
     */
    public function render_admin_dashboard() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Publicidad', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Anuncio', 'flavor-chat-ia'), 'url' => admin_url('post-new.php?post_type=flavor_ad'), 'class' => 'button-primary'],
        ]);

        // Estadísticas generales
        $resultado_estadisticas = $this->action_ver_estadisticas(['periodo' => 'month']);
        if ($resultado_estadisticas['success'] && !empty($resultado_estadisticas['data'])) {
            $datos = $resultado_estadisticas['data'];
            echo '<div class="flavor-stats-grid">';
            echo '<div class="flavor-stat-card"><span class="stat-number">' . number_format($datos['impresiones']) . '</span><span class="stat-label">' . __('Impresiones', 'flavor-chat-ia') . '</span></div>';
            echo '<div class="flavor-stat-card"><span class="stat-number">' . number_format($datos['clics']) . '</span><span class="stat-label">' . __('Clics', 'flavor-chat-ia') . '</span></div>';
            echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($datos['ctr']) . '%</span><span class="stat-label">' . __('CTR', 'flavor-chat-ia') . '</span></div>';
            echo '<div class="flavor-stat-card"><span class="stat-number">' . number_format($datos['gasto'], 2) . '€</span><span class="stat-label">' . __('Ingresos', 'flavor-chat-ia') . '</span></div>';
            echo '</div>';
        }

        // Pool de la comunidad
        $pool_comunidad = get_option('flavor_ads_pool_comunidad', 0);
        echo '<div class="postbox" style="margin-top: 20px;">';
        echo '<h2 class="hndle" style="padding: 12px;"><span class="dashicons dashicons-groups" style="margin-right: 8px;"></span>' . __('Reparto Comunitario', 'flavor-chat-ia') . '</h2>';
        echo '<div class="inside"><p>' . sprintf(__('Pool acumulado para la comunidad: <strong>%s€</strong>', 'flavor-chat-ia'), number_format((float)$pool_comunidad, 2)) . '</p></div>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Renderiza la página de gestión de anuncios
     */
    public function render_admin_anuncios() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestión de Anuncios', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Anuncio', 'flavor-chat-ia'), 'url' => admin_url('post-new.php?post_type=flavor_ad'), 'class' => 'button-primary'],
        ]);

        // Tabs para filtrar por estado
        $estado_actual = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'all';
        $this->render_page_tabs([
            ['slug' => 'all', 'label' => __('Todos', 'flavor-chat-ia')],
            ['slug' => 'publish', 'label' => __('Activos', 'flavor-chat-ia')],
            ['slug' => 'pending', 'label' => __('Pendientes', 'flavor-chat-ia'), 'badge' => $this->contar_anuncios_pendientes()],
            ['slug' => 'draft', 'label' => __('Borradores', 'flavor-chat-ia')],
        ], $estado_actual);

        // Listado de anuncios
        $args_query = [
            'post_type' => 'flavor_ad',
            'posts_per_page' => 20,
            'post_status' => $estado_actual === 'all' ? ['publish', 'pending', 'draft'] : $estado_actual,
        ];
        $anuncios = get_posts($args_query);

        if (!empty($anuncios)) {
            echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">';
            echo '<thead><tr>';
            echo '<th>' . __('Título', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Tipo', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Anunciante', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Presupuesto', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Estado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($anuncios as $anuncio) {
                $tipo_anuncio = get_post_meta($anuncio->ID, '_ad_tipo', true);
                $anunciante_id = get_post_meta($anuncio->ID, '_ad_anunciante_id', true);
                $presupuesto_anuncio = get_post_meta($anuncio->ID, '_ad_presupuesto', true);
                $datos_anunciante = $anunciante_id ? get_userdata($anunciante_id) : null;

                $clase_estado = '';
                switch ($anuncio->post_status) {
                    case 'publish': $clase_estado = 'status-active'; break;
                    case 'pending': $clase_estado = 'status-pending'; break;
                    case 'draft': $clase_estado = 'status-draft'; break;
                }

                echo '<tr>';
                echo '<td><strong><a href="' . esc_url(get_edit_post_link($anuncio->ID)) . '">' . esc_html($anuncio->post_title) . '</a></strong></td>';
                echo '<td>' . esc_html(ucfirst(str_replace('_', ' ', $tipo_anuncio))) . '</td>';
                echo '<td>' . ($datos_anunciante ? esc_html($datos_anunciante->display_name) : '-') . '</td>';
                echo '<td>' . ($presupuesto_anuncio ? esc_html(number_format((float)$presupuesto_anuncio, 2)) . '€' : '-') . '</td>';
                echo '<td><span class="' . esc_attr($clase_estado) . '">' . esc_html(ucfirst($anuncio->post_status)) . '</span></td>';
                echo '<td>';
                echo '<a href="' . esc_url(get_edit_post_link($anuncio->ID)) . '" class="button button-small">' . __('Editar', 'flavor-chat-ia') . '</a> ';
                if ($anuncio->post_status === 'pending') {
                    echo '<button class="button button-small button-primary aprobar-anuncio" data-id="' . esc_attr($anuncio->ID) . '">' . __('Aprobar', 'flavor-chat-ia') . '</button>';
                }
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p style="margin-top: 20px;">' . __('No hay anuncios con este filtro.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de campañas
     */
    public function render_admin_campanas() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Campañas Publicitarias', 'flavor-chat-ia'), [
            ['label' => __('Nueva Campaña', 'flavor-chat-ia'), 'url' => admin_url('post-new.php?post_type=flavor_ad_campaign'), 'class' => 'button-primary'],
        ]);

        // Listado de campañas
        $campanas = get_posts([
            'post_type' => 'flavor_ad_campaign',
            'posts_per_page' => 20,
            'post_status' => ['publish', 'draft'],
        ]);

        if (!empty($campanas)) {
            echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">';
            echo '<thead><tr>';
            echo '<th>' . __('Nombre', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Estado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Fecha creación', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($campanas as $campana) {
                echo '<tr>';
                echo '<td><strong><a href="' . esc_url(get_edit_post_link($campana->ID)) . '">' . esc_html($campana->post_title) . '</a></strong></td>';
                echo '<td>' . esc_html(ucfirst($campana->post_status)) . '</td>';
                echo '<td>' . esc_html(date_i18n('d/m/Y', strtotime($campana->post_date))) . '</td>';
                echo '<td><a href="' . esc_url(get_edit_post_link($campana->ID)) . '" class="button button-small">' . __('Editar', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p style="margin-top: 20px;">' . __('No hay campañas creadas. Crea tu primera campaña para agrupar anuncios.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Publicidad', 'flavor-chat-ia'));

        $configuracion_actual = $this->get_settings();

        echo '<form method="post" action="">';
        wp_nonce_field('flavor_advertising_config', 'flavor_advertising_nonce');
        echo '<table class="form-table">';

        echo '<tr><th scope="row"><label for="reparto_comunidad_default">' . __('% Reparto comunidad (por defecto)', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="reparto_comunidad_default" id="reparto_comunidad_default" value="' . esc_attr($configuracion_actual['reparto_comunidad_default']) . '" min="0" max="100" step="5" class="small-text" />';
        echo '<p class="description">' . __('Porcentaje de ingresos que se reparte con la comunidad.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="precio_clic_default">' . __('Precio por clic (€)', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="precio_clic_default" id="precio_clic_default" value="' . esc_attr($configuracion_actual['precio_clic_default']) . '" min="0" step="0.01" class="small-text" /></td></tr>';

        echo '<tr><th scope="row"><label for="precio_cpm_default">' . __('Precio CPM (€)', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="precio_cpm_default" id="precio_cpm_default" value="' . esc_attr($configuracion_actual['precio_cpm_default']) . '" min="0" step="0.01" class="small-text" />';
        echo '<p class="description">' . __('Precio por cada 1000 impresiones.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="minimo_pago">' . __('Mínimo para pago (€)', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="minimo_pago" id="minimo_pago" value="' . esc_attr($configuracion_actual['minimo_pago']) . '" min="1" step="1" class="small-text" />';
        echo '<p class="description">' . __('Cantidad mínima acumulada para procesar pagos a la comunidad.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="aprobacion_automatica">' . __('Aprobación automática', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="aprobacion_automatica" id="aprobacion_automatica" ' . checked($configuracion_actual['aprobacion_automatica'], true, false) . ' />';
        echo '<p class="description">' . __('Los anuncios se publican automáticamente sin revisión.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="mostrar_etiqueta">' . __('Mostrar etiqueta "Anuncio"', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="mostrar_etiqueta" id="mostrar_etiqueta" ' . checked($configuracion_actual['mostrar_etiqueta'], true, false) . ' /></td></tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="guardar_config_advertising" class="button-primary" value="' . __('Guardar Configuración', 'flavor-chat-ia') . '" /></p>';
        echo '</form>';
        echo '</div>';

        // Procesar guardado
        if (isset($_POST['guardar_config_advertising']) && wp_verify_nonce($_POST['flavor_advertising_nonce'], 'flavor_advertising_config')) {
            $nueva_configuracion = [
                'reparto_comunidad_default' => absint($_POST['reparto_comunidad_default'] ?? 30),
                'precio_clic_default' => floatval($_POST['precio_clic_default'] ?? 0.10),
                'precio_cpm_default' => floatval($_POST['precio_cpm_default'] ?? 1.00),
                'minimo_pago' => absint($_POST['minimo_pago'] ?? 10),
                'aprobacion_automatica' => !empty($_POST['aprobacion_automatica']),
                'mostrar_etiqueta' => !empty($_POST['mostrar_etiqueta']),
            ];
            $this->save_settings($nueva_configuracion);
            echo '<div class="notice notice-success"><p>' . __('Configuración guardada correctamente.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Inicialización del módulo
     */
    public function init() {
        // Registrar en Panel Unificado de Gestión
        
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
        $this->registrar_en_panel_unificado();

        // Crear tablas de base de datos si no existen
        add_action('init', [$this, 'maybe_create_tables']);

        // Registrar CPT y taxonomías
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);

        // Metaboxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_flavor_ad', [$this, 'save_ad_meta']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_flavor_ads_track_impression', [$this, 'ajax_track_impression']);
        add_action('wp_ajax_nopriv_flavor_ads_track_impression', [$this, 'ajax_track_impression']);
        add_action('wp_ajax_flavor_ads_track_click', [$this, 'ajax_track_click']);
        add_action('wp_ajax_nopriv_flavor_ads_track_click', [$this, 'ajax_track_click']);
        add_action('wp_ajax_flavor_ads_crear_campana', [$this, 'ajax_crear_campana']);
        add_action('wp_ajax_flavor_ads_pausar_campana', [$this, 'ajax_pausar_campana']);
        add_action('wp_ajax_flavor_ads_stats', [$this, 'ajax_obtener_stats']);

        // Admin AJAX
        add_action('wp_ajax_flavor_ads_aprobar', [$this, 'ajax_admin_aprobar']);
        add_action('wp_ajax_flavor_ads_rechazar', [$this, 'ajax_admin_rechazar']);
        add_action('wp_ajax_flavor_ads_procesar_pago', [$this, 'ajax_admin_procesar_pago']);

        // Shortcodes
        $this->register_shortcodes();

        // WP Cron
        add_action('flavor_ads_procesar_pagos', [$this, 'cron_procesar_pagos']);
        add_action('flavor_ads_actualizar_estadisticas', [$this, 'cron_actualizar_estadisticas']);

        if (!wp_next_scheduled('flavor_ads_procesar_pagos')) {
            wp_schedule_event(time(), 'daily', 'flavor_ads_procesar_pagos');
        }

        if (!wp_next_scheduled('flavor_ads_actualizar_estadisticas')) {
            wp_schedule_event(time(), 'hourly', 'flavor_ads_actualizar_estadisticas');
        }

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Registra shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('flavor_ad', [$this, 'shortcode_ad']);
        add_shortcode('flavor_ads_dashboard', [$this, 'shortcode_dashboard']);
        add_shortcode('flavor_ads_crear', [$this, 'shortcode_crear']);
        add_shortcode('flavor_ads_ingresos', [$this, 'shortcode_ingresos']);
    }

    /**
     * Crear tablas de base de datos
     */
    public function maybe_create_tables() {
        global $wpdb;

        $version_key = 'flavor_ads_db_version';
        $current_version = '1.1';
        $installed_version = get_option($version_key, '0');

        if (version_compare($installed_version, $current_version, '>=')) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de estadísticas de anuncios
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';
        $sql_stats = "CREATE TABLE IF NOT EXISTS $tabla_stats (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) UNSIGNED NOT NULL,
            fecha date NOT NULL,
            impresiones int(11) UNSIGNED NOT NULL DEFAULT 0,
            clics int(11) UNSIGNED NOT NULL DEFAULT 0,
            gasto decimal(10,4) NOT NULL DEFAULT 0.0000,
            PRIMARY KEY (id),
            UNIQUE KEY ad_fecha (ad_id, fecha),
            KEY ad_id (ad_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Tabla de ingresos para usuarios (reparto comunitario)
        $tabla_ingresos = $wpdb->prefix . 'flavor_ads_ingresos';
        $sql_ingresos = "CREATE TABLE IF NOT EXISTS $tabla_ingresos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            cantidad decimal(10,4) NOT NULL DEFAULT 0.0000,
            concepto varchar(255) DEFAULT NULL,
            fecha datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            estado varchar(20) NOT NULL DEFAULT 'pendiente',
            fecha_pago datetime DEFAULT NULL,
            metodo_pago varchar(50) DEFAULT NULL,
            referencia varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Tabla de transacciones de anunciantes
        $tabla_transacciones = $wpdb->prefix . 'flavor_ads_transactions';
        $sql_transacciones = "CREATE TABLE IF NOT EXISTS $tabla_transacciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            anunciante_id bigint(20) UNSIGNED NOT NULL,
            ad_id bigint(20) UNSIGNED DEFAULT NULL,
            tipo varchar(20) NOT NULL,
            cantidad decimal(10,2) NOT NULL,
            descripcion varchar(255) DEFAULT NULL,
            fecha datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            estado varchar(20) NOT NULL DEFAULT 'completado',
            metodo_pago varchar(50) DEFAULT NULL,
            referencia_externa varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY anunciante_id (anunciante_id),
            KEY ad_id (ad_id),
            KEY tipo (tipo),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Tabla de pagos a la comunidad
        $tabla_payouts = $wpdb->prefix . 'flavor_ads_payouts';
        $sql_payouts = "CREATE TABLE IF NOT EXISTS $tabla_payouts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            periodo varchar(20) NOT NULL,
            cantidad_total decimal(10,2) NOT NULL,
            usuarios_beneficiados int(11) UNSIGNED NOT NULL DEFAULT 0,
            fecha_inicio date NOT NULL,
            fecha_fin date NOT NULL,
            fecha_procesado datetime DEFAULT NULL,
            estado varchar(20) NOT NULL DEFAULT 'pendiente',
            detalles longtext,
            PRIMARY KEY (id),
            KEY periodo (periodo),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql_stats);
        dbDelta($sql_ingresos);
        dbDelta($sql_transacciones);
        dbDelta($sql_payouts);

        update_option($version_key, $current_version);
    }

    /**
     * Registrar Custom Post Types
     */
    public function register_post_types() {
        register_post_type('flavor_ad', [
            'labels' => [
                'name' => __('Anuncios', 'flavor-chat-ia'),
                'singular_name' => __('Anuncio', 'flavor-chat-ia'),
                'add_new' => __('Añadir nuevo', 'flavor-chat-ia'),
                'add_new_item' => __('Añadir nuevo anuncio', 'flavor-chat-ia'),
                'edit_item' => __('Editar anuncio', 'flavor-chat-ia'),
                'view_item' => __('Ver anuncio', 'flavor-chat-ia'),
                'search_items' => __('Buscar anuncios', 'flavor-chat-ia'),
                'not_found' => __('No se encontraron anuncios', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'flavor-chat-ia',
            'supports' => ['title', 'thumbnail'],
            'menu_icon' => 'dashicons-megaphone',
            'has_archive' => false,
            'rewrite' => false,
        ]);

        register_post_type('flavor_ad_campaign', [
            'labels' => [
                'name' => __('Campañas', 'flavor-chat-ia'),
                'singular_name' => __('Campaña', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=flavor_ad',
            'supports' => ['title'],
        ]);
    }

    /**
     * Registrar taxonomías
     */
    public function register_taxonomies() {
        register_taxonomy('ad_ubicacion', 'flavor_ad', [
            'labels' => [
                'name' => __('Ubicaciones', 'flavor-chat-ia'),
                'singular_name' => __('Ubicación', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => false,
        ]);

        register_taxonomy('ad_categoria', 'flavor_ad', [
            'labels' => [
                'name' => __('Categorías de anuncio', 'flavor-chat-ia'),
                'singular_name' => __('Categoría', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => false,
        ]);
    }

    /**
     * Añadir meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'flavor_ad_settings',
            __('Configuración del anuncio', 'flavor-chat-ia'),
            [$this, 'render_ad_settings_metabox'],
            'flavor_ad',
            'normal',
            'high'
        );

        add_meta_box(
            'flavor_ad_stats',
            __('Estadísticas', 'flavor-chat-ia'),
            [$this, 'render_ad_stats_metabox'],
            'flavor_ad',
            'side'
        );
    }

    /**
     * Renderizar metabox de configuración
     */
    public function render_ad_settings_metabox($post) {
        wp_nonce_field('flavor_ad_meta', 'flavor_ad_nonce');

        $tipo = get_post_meta($post->ID, '_ad_tipo', true) ?: 'banner_horizontal';
        $url_destino = get_post_meta($post->ID, '_ad_url_destino', true);
        $fecha_inicio = get_post_meta($post->ID, '_ad_fecha_inicio', true);
        $fecha_fin = get_post_meta($post->ID, '_ad_fecha_fin', true);
        $presupuesto = get_post_meta($post->ID, '_ad_presupuesto', true);
        $precio_clic = get_post_meta($post->ID, '_ad_precio_clic', true) ?: '0.10';
        $precio_impresion = get_post_meta($post->ID, '_ad_precio_impresion', true) ?: '0.001';
        $reparto_comunidad = get_post_meta($post->ID, '_ad_reparto_comunidad', true) ?: '30';
        $anunciante_id = get_post_meta($post->ID, '_ad_anunciante_id', true);
        $contenido_html = get_post_meta($post->ID, '_ad_contenido_html', true);
        $imagen_url = get_post_meta($post->ID, '_ad_imagen_url', true);
        $texto_cta = get_post_meta($post->ID, '_ad_texto_cta', true) ?: __('Saber más', 'flavor-chat-ia');
        $segmentacion = get_post_meta($post->ID, '_ad_segmentacion', true) ?: [];
        ?>
        <style>
            .flavor-ad-metabox { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .flavor-ad-metabox .full-width { grid-column: 1 / -1; }
            .flavor-ad-metabox label { display: block; font-weight: 600; margin-bottom: 5px; }
            .flavor-ad-metabox input, .flavor-ad-metabox select, .flavor-ad-metabox textarea {
                width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;
            }
            .flavor-ad-metabox .description { font-size: 12px; color: #666; margin-top: 4px; }
        </style>
        <div class="flavor-ad-metabox">
            <div>
                <label><?php _e('Tipo de anuncio', 'flavor-chat-ia'); ?></label>
                <select name="ad_tipo">
                    <option value="<?php echo esc_attr__('banner_horizontal', 'flavor-chat-ia'); ?>" <?php selected($tipo, 'banner_horizontal'); ?>><?php _e('Banner Horizontal (728x90)', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('banner_sidebar', 'flavor-chat-ia'); ?>" <?php selected($tipo, 'banner_sidebar'); ?>><?php _e('Banner Sidebar (300x250)', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('banner_card', 'flavor-chat-ia'); ?>" <?php selected($tipo, 'banner_card'); ?>><?php _e('Tarjeta', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('banner_nativo', 'flavor-chat-ia'); ?>" <?php selected($tipo, 'banner_nativo'); ?>><?php _e('Nativo', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('video', 'flavor-chat-ia'); ?>" <?php selected($tipo, 'video'); ?>><?php _e('Video', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
            <div>
                <label><?php _e('Anunciante', 'flavor-chat-ia'); ?></label>
                <?php
                wp_dropdown_users([
                    'name' => 'ad_anunciante_id',
                    'selected' => $anunciante_id,
                    'show_option_none' => __('Seleccionar...', 'flavor-chat-ia'),
                    'option_none_value' => '',
                    'role__in' => ['administrator', 'editor', 'author', 'subscriber'],
                ]);
                ?>
            </div>
            <div class="full-width">
                <label><?php _e('URL de destino', 'flavor-chat-ia'); ?></label>
                <input type="url" name="ad_url_destino" value="<?php echo esc_attr($url_destino); ?>" placeholder="<?php echo esc_attr__('https://...', 'flavor-chat-ia'); ?>">
            </div>
            <div class="full-width">
                <label><?php _e('Imagen del anuncio', 'flavor-chat-ia'); ?></label>
                <input type="url" name="ad_imagen_url" value="<?php echo esc_attr($imagen_url); ?>" placeholder="<?php echo esc_attr__('URL de la imagen', 'flavor-chat-ia'); ?>">
                <p class="description"><?php _e('O usa la imagen destacada del post', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="full-width">
                <label><?php _e('Contenido HTML (opcional)', 'flavor-chat-ia'); ?></label>
                <textarea name="ad_contenido_html" rows="4" placeholder="<?php echo esc_attr__('HTML personalizado...', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($contenido_html); ?></textarea>
            </div>
            <div>
                <label><?php _e('Texto del botón CTA', 'flavor-chat-ia'); ?></label>
                <input type="text" name="ad_texto_cta" value="<?php echo esc_attr($texto_cta); ?>">
            </div>
            <div>
                <label><?php _e('% para la comunidad', 'flavor-chat-ia'); ?></label>
                <input type="number" name="ad_reparto_comunidad" value="<?php echo esc_attr($reparto_comunidad); ?>" min="0" max="100" step="5">
                <p class="description"><?php _e('Porcentaje de ingresos que se reparten con la comunidad', 'flavor-chat-ia'); ?></p>
            </div>
            <div>
                <label><?php _e('Fecha inicio', 'flavor-chat-ia'); ?></label>
                <input type="date" name="ad_fecha_inicio" value="<?php echo esc_attr($fecha_inicio); ?>">
            </div>
            <div>
                <label><?php _e('Fecha fin', 'flavor-chat-ia'); ?></label>
                <input type="date" name="ad_fecha_fin" value="<?php echo esc_attr($fecha_fin); ?>">
            </div>
            <div>
                <label><?php _e('Presupuesto total (€)', 'flavor-chat-ia'); ?></label>
                <input type="number" name="ad_presupuesto" value="<?php echo esc_attr($presupuesto); ?>" min="0" step="0.01">
            </div>
            <div>
                <label><?php _e('Precio por clic (€)', 'flavor-chat-ia'); ?></label>
                <input type="number" name="ad_precio_clic" value="<?php echo esc_attr($precio_clic); ?>" min="0" step="0.01">
            </div>
            <div>
                <label><?php _e('Precio por 1000 impresiones (€)', 'flavor-chat-ia'); ?></label>
                <input type="number" name="ad_precio_impresion" value="<?php echo esc_attr($precio_impresion); ?>" min="0" step="0.001">
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar metabox de estadísticas
     */
    public function render_ad_stats_metabox($post) {
        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(impresiones) as total_impresiones,
                SUM(clics) as total_clics,
                SUM(gasto) as total_gasto
             FROM $tabla_stats WHERE ad_id = %d",
            $post->ID
        ));

        $impresiones = $stats->total_impresiones ?? 0;
        $clics = $stats->total_clics ?? 0;
        $gasto = $stats->total_gasto ?? 0;
        $ctr = $impresiones > 0 ? round(($clics / $impresiones) * 100, 2) : 0;
        ?>
        <div style="padding: 10px 0;">
            <p><strong><?php _e('Impresiones:', 'flavor-chat-ia'); ?></strong> <?php echo number_format($impresiones); ?></p>
            <p><strong><?php _e('Clics:', 'flavor-chat-ia'); ?></strong> <?php echo number_format($clics); ?></p>
            <p><strong><?php _e('CTR:', 'flavor-chat-ia'); ?></strong> <?php echo $ctr; ?>%</p>
            <p><strong><?php _e('Gasto total:', 'flavor-chat-ia'); ?></strong> <?php echo number_format($gasto, 2); ?>€</p>
        </div>
        <?php
    }

    /**
     * Guardar meta del anuncio
     */
    public function save_ad_meta($post_id) {
        if (!isset($_POST['flavor_ad_nonce']) || !wp_verify_nonce($_POST['flavor_ad_nonce'], 'flavor_ad_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $campos = [
            'ad_tipo', 'ad_url_destino', 'ad_fecha_inicio', 'ad_fecha_fin',
            'ad_presupuesto', 'ad_precio_clic', 'ad_precio_impresion',
            'ad_reparto_comunidad', 'ad_anunciante_id', 'ad_contenido_html',
            'ad_imagen_url', 'ad_texto_cta',
        ];

        foreach ($campos as $campo) {
            if (isset($_POST[$campo])) {
                update_post_meta($post_id, '_' . $campo, sanitize_text_field($_POST[$campo]));
            }
        }
    }

    /**
     * Acciones disponibles
     */
    public function get_actions() {
        return [
            'ver_estadisticas' => [
                'description' => 'Ver estadísticas de publicidad',
                'params' => ['periodo', 'ad_id'],
            ],
            'listar_anuncios' => [
                'description' => 'Listar anuncios activos',
                'params' => ['estado', 'tipo'],
            ],
            'crear_anuncio' => [
                'description' => 'Crear un nuevo anuncio',
                'params' => ['titulo', 'tipo', 'url_destino', 'imagen', 'presupuesto'],
            ],
            'mis_anuncios' => [
                'description' => 'Ver mis anuncios como anunciante',
                'params' => [],
            ],
            'pausar_anuncio' => [
                'description' => 'Pausar un anuncio activo',
                'params' => ['ad_id'],
            ],
            'reanudar_anuncio' => [
                'description' => 'Reanudar un anuncio pausado',
                'params' => ['ad_id'],
            ],
            'mis_ingresos' => [
                'description' => 'Ver ingresos de publicidad compartidos',
                'params' => ['periodo'],
            ],
            'ubicaciones_disponibles' => [
                'description' => 'Ver ubicaciones disponibles para anuncios',
                'params' => [],
            ],
        ];
    }

    /**
     * Ejecutar acción
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'listar_anuncios',
            'listado' => 'listar_anuncios',
            'crear' => 'crear_anuncio',
            'nuevo' => 'crear_anuncio',
            'mis_items' => 'mis_anuncios',
            'mis-anuncios' => 'mis_anuncios',
            'stats' => 'ver_estadisticas',
            'estadisticas' => 'ver_estadisticas',
            'pausar' => 'pausar_anuncio',
            'reanudar' => 'reanudar_anuncio',
            'ingresos' => 'mis_ingresos',
            'ubicaciones' => 'ubicaciones_disponibles',
            'foro' => 'foro_anuncio',
            'chat' => 'chat_anuncio',
            'multimedia' => 'multimedia_anuncio',
            'red-social' => 'red_social_anuncio',
            'red_social' => 'red_social_anuncio',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $metodo = 'action_' . $action_name;

        if (method_exists($this, $metodo)) {
            return $this->$metodo($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    private function resolve_contextual_anuncio($params = []) {
        $ad_id = absint(
            $params['ad_id']
            ?? $params['id']
            ?? $_GET['ad_id']
            ?? $_GET['id']
            ?? 0
        );

        if (!$ad_id) {
            return null;
        }

        $ad = get_post($ad_id);
        if (!$ad || $ad->post_type !== 'flavor_ad') {
            return null;
        }

        return [
            'id' => (int) $ad->ID,
            'titulo' => (string) $ad->post_title,
            'descripcion' => (string) ($ad->post_excerpt ?: wp_trim_words(wp_strip_all_tags((string) $ad->post_content), 24)),
        ];
    }

    private function action_foro_anuncio($params) {
        $anuncio = $this->resolve_contextual_anuncio($params);
        if (!$anuncio) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un anuncio para ver su foro.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-foro">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;">'
            . '<h2>' . esc_html__('Foro del anuncio', 'flavor-chat-ia') . '</h2>'
            . '<p>' . esc_html($anuncio['titulo']) . '</p>'
            . '</div>'
            . do_shortcode('[flavor_foros_integrado entidad="advertising_ad" entidad_id="' . absint($anuncio['id']) . '"]')
            . '</div>';
    }

    private function action_chat_anuncio($params) {
        $anuncio = $this->resolve_contextual_anuncio($params);
        if (!$anuncio) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un anuncio para ver su chat.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en el chat de este anuncio.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-chat">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Chat del anuncio', 'flavor-chat-ia') . '</h2><p>' . esc_html($anuncio['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/chat-grupos/mensajes/?ad_id=' . absint($anuncio['id']))) . '" class="button button-secondary">'
            . esc_html__('Abrir chat completo', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_chat_grupo_integrado entidad="advertising_ad" entidad_id="' . absint($anuncio['id']) . '"]')
            . '</div>';
    }

    private function action_multimedia_anuncio($params) {
        $anuncio = $this->resolve_contextual_anuncio($params);
        if (!$anuncio) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un anuncio para ver sus archivos.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-multimedia">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Multimedia del anuncio', 'flavor-chat-ia') . '</h2><p>' . esc_html($anuncio['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/multimedia/subir/?ad_id=' . absint($anuncio['id']))) . '" class="button button-primary">'
            . esc_html__('Subir archivo', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_multimedia_galeria entidad="advertising_ad" entidad_id="' . absint($anuncio['id']) . '"]')
            . '</div>';
    }

    private function action_red_social_anuncio($params) {
        $anuncio = $this->resolve_contextual_anuncio($params);
        if (!$anuncio) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un anuncio para ver su actividad social.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en la actividad social de este anuncio.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-red-social">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Actividad social del anuncio', 'flavor-chat-ia') . '</h2><p>' . esc_html($anuncio['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/red-social/crear/?ad_id=' . absint($anuncio['id']))) . '" class="button button-primary">'
            . esc_html__('Publicar', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_social_feed entidad="advertising_ad" entidad_id="' . absint($anuncio['id']) . '"]')
            . '</div>';
    }

    /**
     * Acción: Ver estadísticas
     */
    private function action_ver_estadisticas($params) {
        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';

        $periodo = $params['periodo'] ?? 'month';
        $ad_id = $params['ad_id'] ?? null;

        $fecha_inicio = $this->get_fecha_inicio_periodo($periodo);
        $where = "fecha >= %s";
        $valores = [$fecha_inicio];

        if ($ad_id) {
            $where .= " AND ad_id = %d";
            $valores[] = intval($ad_id);
        }

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(impresiones) as total_impresiones,
                SUM(clics) as total_clics,
                SUM(gasto) as total_gasto,
                COUNT(DISTINCT ad_id) as total_anuncios
             FROM $tabla_stats WHERE $where",
            $valores
        ));

        $ctr = $stats->total_impresiones > 0
            ? round(($stats->total_clics / $stats->total_impresiones) * 100, 2)
            : 0;

        return [
            'success' => true,
            'data' => [
                'periodo' => $periodo,
                'impresiones' => (int)($stats->total_impresiones ?? 0),
                'clics' => (int)($stats->total_clics ?? 0),
                'ctr' => $ctr,
                'gasto' => (float)($stats->total_gasto ?? 0),
                'anuncios_activos' => (int)($stats->total_anuncios ?? 0),
            ],
        ];
    }

    /**
     * Acción: Listar anuncios
     */
    private function action_listar_anuncios($params) {
        $estado = $params['estado'] ?? 'publish';
        $tipo = $params['tipo'] ?? '';

        $args = [
            'post_type' => 'flavor_ad',
            'posts_per_page' => 50,
            'post_status' => $estado,
        ];

        if ($tipo) {
            $args['meta_query'] = [
                [
                    'key' => '_ad_tipo',
                    'value' => $tipo,
                ],
            ];
        }

        $anuncios = get_posts($args);
        $resultado = [];

        foreach ($anuncios as $ad) {
            $resultado[] = [
                'id' => $ad->ID,
                'titulo' => $ad->post_title,
                'tipo' => get_post_meta($ad->ID, '_ad_tipo', true),
                'url_destino' => get_post_meta($ad->ID, '_ad_url_destino', true),
                'presupuesto' => get_post_meta($ad->ID, '_ad_presupuesto', true),
                'fecha_inicio' => get_post_meta($ad->ID, '_ad_fecha_inicio', true),
                'fecha_fin' => get_post_meta($ad->ID, '_ad_fecha_fin', true),
                'estado' => $ad->post_status,
            ];
        }

        return [
            'success' => true,
            'data' => $resultado,
        ];
    }

    /**
     * Acción: Crear anuncio
     */
    private function action_crear_anuncio($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $titulo = sanitize_text_field($params['titulo'] ?? '');
        $tipo = sanitize_text_field($params['tipo'] ?? 'banner_horizontal');
        $url_destino = esc_url_raw($params['url_destino'] ?? '');
        $imagen = esc_url_raw($params['imagen'] ?? '');
        $presupuesto = floatval($params['presupuesto'] ?? 0);

        if (empty($titulo)) {
            return ['success' => false, 'error' => __('El título es requerido', 'flavor-chat-ia')];
        }

        $post_id = wp_insert_post([
            'post_type' => 'flavor_ad',
            'post_title' => $titulo,
            'post_status' => 'pending',
            'post_author' => $usuario_id,
        ]);

        if (is_wp_error($post_id)) {
            return ['success' => false, 'error' => $post_id->get_error_message()];
        }

        update_post_meta($post_id, '_ad_tipo', $tipo);
        update_post_meta($post_id, '_ad_url_destino', $url_destino);
        update_post_meta($post_id, '_ad_imagen_url', $imagen);
        update_post_meta($post_id, '_ad_presupuesto', $presupuesto);
        update_post_meta($post_id, '_ad_anunciante_id', $usuario_id);
        update_post_meta($post_id, '_ad_reparto_comunidad', 30);

        // Notificar a admins
        do_action('flavor_notificacion_enviar', [
            'tipo' => 'ads_nuevo_pendiente',
            'destinatario' => 'admins',
            'datos' => [
                'ad_id' => $post_id,
                'titulo' => $titulo,
                'anunciante' => get_userdata($usuario_id)->display_name,
            ],
        ]);

        return [
            'success' => true,
            'message' => __('Anuncio creado y pendiente de aprobación', 'flavor-chat-ia'),
            'data' => ['ad_id' => $post_id],
        ];
    }

    /**
     * Acción: Mis anuncios
     */
    private function action_mis_anuncios($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $anuncios = get_posts([
            'post_type' => 'flavor_ad',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'pending', 'draft'],
            'meta_query' => [
                [
                    'key' => '_ad_anunciante_id',
                    'value' => $usuario_id,
                ],
            ],
        ]);

        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';

        $resultado = [];
        foreach ($anuncios as $ad) {
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT SUM(impresiones) as imp, SUM(clics) as cli, SUM(gasto) as gasto
                 FROM $tabla_stats WHERE ad_id = %d",
                $ad->ID
            ));

            $resultado[] = [
                'id' => $ad->ID,
                'titulo' => $ad->post_title,
                'tipo' => get_post_meta($ad->ID, '_ad_tipo', true),
                'estado' => $ad->post_status,
                'presupuesto' => get_post_meta($ad->ID, '_ad_presupuesto', true),
                'impresiones' => (int)($stats->imp ?? 0),
                'clics' => (int)($stats->cli ?? 0),
                'gasto' => (float)($stats->gasto ?? 0),
            ];
        }

        return ['success' => true, 'data' => $resultado];
    }

    /**
     * Acción: Pausar anuncio
     */
    private function action_pausar_anuncio($params) {
        $ad_id = intval($params['ad_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$ad_id) {
            return ['success' => false, 'error' => __('ID de anuncio requerido', 'flavor-chat-ia')];
        }

        $anunciante = get_post_meta($ad_id, '_ad_anunciante_id', true);
        if ($anunciante != $usuario_id && !current_user_can('manage_options')) {
            return ['success' => false, 'error' => __('No tienes permiso', 'flavor-chat-ia')];
        }

        wp_update_post([
            'ID' => $ad_id,
            'post_status' => 'draft',
        ]);

        return ['success' => true, 'message' => __('Anuncio pausado', 'flavor-chat-ia')];
    }

    /**
     * Acción: Reanudar anuncio
     */
    private function action_reanudar_anuncio($params) {
        $ad_id = intval($params['ad_id'] ?? 0);
        $usuario_id = get_current_user_id();

        $anunciante = get_post_meta($ad_id, '_ad_anunciante_id', true);
        if ($anunciante != $usuario_id && !current_user_can('manage_options')) {
            return ['success' => false, 'error' => __('No tienes permiso', 'flavor-chat-ia')];
        }

        wp_update_post([
            'ID' => $ad_id,
            'post_status' => 'publish',
        ]);

        return ['success' => true, 'message' => __('Anuncio reanudado', 'flavor-chat-ia')];
    }

    /**
     * Acción: Mis ingresos (por visualizar anuncios)
     */
    private function action_mis_ingresos($params) {
        global $wpdb;
        $tabla_ingresos = $wpdb->prefix . 'flavor_ads_ingresos';
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesión', 'flavor-chat-ia')];
        }

        $periodo = $params['periodo'] ?? 'month';
        $fecha_inicio = $this->get_fecha_inicio_periodo($periodo);

        $ingresos = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(cantidad) as total,
                COUNT(*) as transacciones
             FROM $tabla_ingresos
             WHERE usuario_id = %d AND fecha >= %s AND estado = 'completado'",
            $usuario_id,
            $fecha_inicio
        ));

        $pendiente = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cantidad) FROM $tabla_ingresos WHERE usuario_id = %d AND estado = 'pendiente'",
            $usuario_id
        ));

        return [
            'success' => true,
            'data' => [
                'total_periodo' => (float)($ingresos->total ?? 0),
                'transacciones' => (int)($ingresos->transacciones ?? 0),
                'pendiente_pago' => (float)($pendiente ?? 0),
            ],
        ];
    }

    /**
     * Acción: Ubicaciones disponibles
     */
    private function action_ubicaciones_disponibles($params) {
        $ubicaciones = get_terms([
            'taxonomy' => 'ad_ubicacion',
            'hide_empty' => false,
        ]);

        $resultado = [];
        foreach ($ubicaciones as $ub) {
            $resultado[] = [
                'id' => $ub->term_id,
                'nombre' => $ub->name,
                'slug' => $ub->slug,
                'descripcion' => $ub->description,
            ];
        }

        return ['success' => true, 'data' => $resultado];
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        register_rest_route($namespace, '/ads', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_listar_anuncios'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/ads/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_anuncio'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/ads', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_anuncio'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/ads/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'rest_actualizar_anuncio'],
            'permission_callback' => [$this, 'check_ad_permission'],
        ]);

        register_rest_route($namespace, '/ads/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'rest_eliminar_anuncio'],
            'permission_callback' => [$this, 'check_ad_permission'],
        ]);

        register_rest_route($namespace, '/ads/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_estadisticas'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/ads/serve', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_servir_anuncio'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/ads/track/impression', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_track_impression'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/ads/track/click', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_track_click'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/ads/mis-anuncios', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_anuncios'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);

        register_rest_route($namespace, '/ads/ingresos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_ingresos'],
            'permission_callback' => [$this, 'check_user_logged_in'],
        ]);
    }

    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    public function check_ad_permission($request) {
        $ad_id = $request['id'];
        $anunciante = get_post_meta($ad_id, '_ad_anunciante_id', true);
        return get_current_user_id() == $anunciante || current_user_can('manage_options');
    }

    /**
     * REST: Listar anuncios
     */
    public function rest_listar_anuncios($request) {
        $tipo = $request->get_param('tipo');
        $ubicacion = $request->get_param('ubicacion');

        $args = [
            'post_type' => 'flavor_ad',
            'posts_per_page' => 50,
            'post_status' => 'publish',
        ];

        $meta_query = [];
        if ($tipo) {
            $meta_query[] = ['key' => '_ad_tipo', 'value' => $tipo];
        }

        // Filtrar por fecha activa
        $hoy = date('Y-m-d');
        $meta_query[] = [
            'relation' => 'OR',
            ['key' => '_ad_fecha_inicio', 'compare' => 'NOT EXISTS'],
            ['key' => '_ad_fecha_inicio', 'value' => $hoy, 'compare' => '<='],
        ];
        $meta_query[] = [
            'relation' => 'OR',
            ['key' => '_ad_fecha_fin', 'compare' => 'NOT EXISTS'],
            ['key' => '_ad_fecha_fin', 'value' => $hoy, 'compare' => '>='],
        ];

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        if ($ubicacion) {
            $args['tax_query'] = [
                ['taxonomy' => 'ad_ubicacion', 'field' => 'slug', 'terms' => $ubicacion],
            ];
        }

        $anuncios = get_posts($args);
        $resultado = array_map([$this, 'format_ad_response'], $anuncios);

        return rest_ensure_response($resultado);
    }

    /**
     * REST: Obtener anuncio
     */
    public function rest_obtener_anuncio($request) {
        $ad = get_post($request['id']);

        if (!$ad || $ad->post_type !== 'flavor_ad') {
            return new WP_Error('not_found', __('Anuncio no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        return rest_ensure_response($this->format_ad_response($ad));
    }

    /**
     * REST: Crear anuncio
     */
    public function rest_crear_anuncio($request) {
        $resultado = $this->action_crear_anuncio($request->get_params());

        if (!$resultado['success']) {
            return new WP_Error('error', $resultado['error'], ['status' => 400]);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * REST: Servir anuncio (para mostrar)
     */
    public function rest_servir_anuncio($request) {
        $tipo = $request->get_param('tipo') ?: 'banner_horizontal';
        $ubicacion = $request->get_param('ubicacion');

        $args = [
            'post_type' => 'flavor_ad',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'orderby' => 'rand',
            'meta_query' => [
                ['key' => '_ad_tipo', 'value' => $tipo],
            ],
        ];

        if ($ubicacion) {
            $args['tax_query'] = [
                ['taxonomy' => 'ad_ubicacion', 'field' => 'slug', 'terms' => $ubicacion],
            ];
        }

        $anuncios = get_posts($args);

        if (empty($anuncios)) {
            return rest_ensure_response(null);
        }

        $ad = $anuncios[0];

        return rest_ensure_response([
            'id' => $ad->ID,
            'titulo' => $ad->post_title,
            'tipo' => get_post_meta($ad->ID, '_ad_tipo', true),
            'imagen' => get_post_meta($ad->ID, '_ad_imagen_url', true) ?: get_the_post_thumbnail_url($ad->ID, 'large'),
            'url' => get_post_meta($ad->ID, '_ad_url_destino', true),
            'cta' => get_post_meta($ad->ID, '_ad_texto_cta', true),
            'html' => get_post_meta($ad->ID, '_ad_contenido_html', true),
        ]);
    }

    /**
     * REST: Track impression
     */
    public function rest_track_impression($request) {
        $ad_id = intval($request->get_param('ad_id'));
        $this->registrar_impresion($ad_id);
        return rest_ensure_response(['success' => true]);
    }

    /**
     * REST: Track click
     */
    public function rest_track_click($request) {
        $ad_id = intval($request->get_param('ad_id'));
        $this->registrar_clic($ad_id);
        return rest_ensure_response(['success' => true]);
    }

    /**
     * REST: Estadísticas
     */
    public function rest_estadisticas($request) {
        $periodo = $request->get_param('periodo') ?: 'month';
        $resultado = $this->action_ver_estadisticas(['periodo' => $periodo]);
        return rest_ensure_response($resultado);
    }

    /**
     * REST: Mis anuncios
     */
    public function rest_mis_anuncios($request) {
        $resultado = $this->action_mis_anuncios([]);
        return rest_ensure_response($resultado);
    }

    /**
     * REST: Mis ingresos
     */
    public function rest_mis_ingresos($request) {
        $periodo = $request->get_param('periodo') ?: 'month';
        $resultado = $this->action_mis_ingresos(['periodo' => $periodo]);
        return rest_ensure_response($resultado);
    }

    /**
     * AJAX: Track impression
     */
    public function ajax_track_impression() {
        $ad_id = intval($_POST['ad_id'] ?? 0);
        $this->registrar_impresion($ad_id);
        wp_send_json_success();
    }

    /**
     * AJAX: Track click
     */
    public function ajax_track_click() {
        $ad_id = intval($_POST['ad_id'] ?? 0);
        $this->registrar_clic($ad_id);
        wp_send_json_success();
    }

    /**
     * AJAX: Crear campaña
     */
    public function ajax_crear_campana() {
        check_ajax_referer('flavor_ads_nonce', 'nonce');

        $resultado = $this->action_crear_anuncio($_POST);
        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado['error']);
        }
    }

    /**
     * AJAX: Pausar campaña
     */
    public function ajax_pausar_campana() {
        check_ajax_referer('flavor_ads_nonce', 'nonce');

        $resultado = $this->action_pausar_anuncio($_POST);
        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado['error']);
        }
    }

    /**
     * AJAX: Obtener stats
     */
    public function ajax_obtener_stats() {
        check_ajax_referer('flavor_ads_nonce', 'nonce');

        $resultado = $this->action_ver_estadisticas($_POST);
        wp_send_json($resultado);
    }

    /**
     * AJAX Admin: Aprobar anuncio
     */
    public function ajax_admin_aprobar() {
        check_ajax_referer('flavor_ads_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permiso', 'flavor-chat-ia'));
        }

        $ad_id = intval($_POST['ad_id']);
        wp_update_post([
            'ID' => $ad_id,
            'post_status' => 'publish',
        ]);

        $anunciante = get_post_meta($ad_id, '_ad_anunciante_id', true);
        if ($anunciante) {
            do_action('flavor_notificacion_enviar', [
                'tipo' => 'ads_anuncio_aprobado',
                'destinatario' => $anunciante,
                'datos' => [
                    'ad_id' => $ad_id,
                    'titulo' => get_the_title($ad_id),
                ],
            ]);
        }

        wp_send_json_success(__('Anuncio aprobado', 'flavor-chat-ia'));
    }

    /**
     * AJAX Admin: Rechazar anuncio
     */
    public function ajax_admin_rechazar() {
        check_ajax_referer('flavor_ads_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permiso', 'flavor-chat-ia'));
        }

        $ad_id = intval($_POST['ad_id']);
        $motivo = sanitize_textarea_field($_POST['motivo'] ?? '');

        wp_update_post([
            'ID' => $ad_id,
            'post_status' => 'draft',
        ]);

        update_post_meta($ad_id, '_ad_motivo_rechazo', $motivo);

        $anunciante = get_post_meta($ad_id, '_ad_anunciante_id', true);
        if ($anunciante) {
            do_action('flavor_notificacion_enviar', [
                'tipo' => 'ads_anuncio_rechazado',
                'destinatario' => $anunciante,
                'datos' => [
                    'ad_id' => $ad_id,
                    'titulo' => get_the_title($ad_id),
                    'motivo' => $motivo,
                ],
            ]);
        }

        wp_send_json_success(__('Anuncio rechazado', 'flavor-chat-ia'));
    }

    /**
     * AJAX Admin: Procesar pago
     */
    public function ajax_admin_procesar_pago() {
        check_ajax_referer('flavor_ads_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permiso', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_ingresos = $wpdb->prefix . 'flavor_ads_ingresos';

        $usuario_id = intval($_POST['usuario_id']);
        $cantidad = floatval($_POST['cantidad']);

        $wpdb->update(
            $tabla_ingresos,
            ['estado' => 'completado', 'fecha_pago' => current_time('mysql')],
            ['usuario_id' => $usuario_id, 'estado' => 'pendiente']
        );

        do_action('flavor_notificacion_enviar', [
            'tipo' => 'ads_pago_procesado',
            'destinatario' => $usuario_id,
            'datos' => ['cantidad' => $cantidad],
        ]);

        wp_send_json_success(__('Pago procesado', 'flavor-chat-ia'));
    }

    /**
     * Registrar impresión
     */
    private function registrar_impresion($ad_id) {
        if (!$ad_id) return;

        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';
        $hoy = date('Y-m-d');

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_stats WHERE ad_id = %d AND fecha = %s",
            $ad_id, $hoy
        ));

        $precio_cpm = floatval(get_post_meta($ad_id, '_ad_precio_impresion', true)) ?: 0.001;
        $costo = $precio_cpm / 1000;

        if ($existe) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_stats SET impresiones = impresiones + 1, gasto = gasto + %f WHERE id = %d",
                $costo, $existe
            ));
        } else {
            $wpdb->insert($tabla_stats, [
                'ad_id' => $ad_id,
                'fecha' => $hoy,
                'impresiones' => 1,
                'clics' => 0,
                'gasto' => $costo,
            ]);
        }

        // Repartir con la comunidad
        $this->repartir_ingreso($ad_id, $costo);
    }

    /**
     * Registrar clic
     */
    private function registrar_clic($ad_id) {
        if (!$ad_id) return;

        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';
        $hoy = date('Y-m-d');

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_stats WHERE ad_id = %d AND fecha = %s",
            $ad_id, $hoy
        ));

        $precio_clic = floatval(get_post_meta($ad_id, '_ad_precio_clic', true)) ?: 0.10;

        if ($existe) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_stats SET clics = clics + 1, gasto = gasto + %f WHERE id = %d",
                $precio_clic, $existe
            ));
        } else {
            $wpdb->insert($tabla_stats, [
                'ad_id' => $ad_id,
                'fecha' => $hoy,
                'impresiones' => 0,
                'clics' => 1,
                'gasto' => $precio_clic,
            ]);
        }

        // Repartir con la comunidad
        $this->repartir_ingreso($ad_id, $precio_clic);
    }

    /**
     * Repartir ingreso con la comunidad
     */
    private function repartir_ingreso($ad_id, $cantidad) {
        $porcentaje_comunidad = floatval(get_post_meta($ad_id, '_ad_reparto_comunidad', true)) ?: 30;
        $cantidad_comunidad = $cantidad * ($porcentaje_comunidad / 100);

        if ($cantidad_comunidad <= 0) return;

        // Por ahora, acumular en un pool comunitario
        // En una implementación completa, se distribuiría entre usuarios activos
        $pool_actual = get_option('flavor_ads_pool_comunidad', 0);
        update_option('flavor_ads_pool_comunidad', $pool_actual + $cantidad_comunidad);
    }

    /**
     * Shortcode: Mostrar anuncio
     */
    public function shortcode_ad($atts) {
        $atts = shortcode_atts([
            'id' => 0,
            'tipo' => 'banner_horizontal',
            'ubicacion' => '',
            'class' => '',
        ], $atts);

        $this->enqueue_frontend_assets();

        if ($atts['id']) {
            $ad = get_post($atts['id']);
        } else {
            $args = [
                'post_type' => 'flavor_ad',
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'orderby' => 'rand',
                'meta_query' => [
                    ['key' => '_ad_tipo', 'value' => $atts['tipo']],
                ],
            ];

            if ($atts['ubicacion']) {
                $args['tax_query'] = [
                    ['taxonomy' => 'ad_ubicacion', 'field' => 'slug', 'terms' => $atts['ubicacion']],
                ];
            }

            $ads = get_posts($args);
            $ad = $ads[0] ?? null;
        }

        if (!$ad) return '';

        $imagen = get_post_meta($ad->ID, '_ad_imagen_url', true) ?: get_the_post_thumbnail_url($ad->ID, 'large');
        $url = get_post_meta($ad->ID, '_ad_url_destino', true);
        $cta = get_post_meta($ad->ID, '_ad_texto_cta', true);
        $html = get_post_meta($ad->ID, '_ad_contenido_html', true);
        $tipo = get_post_meta($ad->ID, '_ad_tipo', true);

        ob_start();
        ?>
        <div class="flavor-ad flavor-ad-<?php echo esc_attr($tipo); ?> <?php echo esc_attr($atts['class']); ?>"
             data-ad-id="<?php echo $ad->ID; ?>">
            <span class="flavor-ad-label"><?php _e('Anuncio', 'flavor-chat-ia'); ?></span>
            <?php if ($html): ?>
                <?php echo wp_kses_post($html); ?>
            <?php else: ?>
                <a href="<?php echo esc_url($url); ?>" class="flavor-ad-link"
                   target="_blank" rel="noopener sponsored">
                    <?php if ($imagen): ?>
                        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($ad->post_title); ?>" class="flavor-ad-image">
                    <?php endif; ?>
                    <?php if ($tipo === 'banner_card' || $tipo === 'banner_nativo'): ?>
                        <div class="flavor-ad-content">
                            <h4 class="flavor-ad-title"><?php echo esc_html($ad->post_title); ?></h4>
                            <?php if ($cta): ?>
                                <span class="flavor-ad-cta"><?php echo esc_html($cta); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard de anunciante
     */
    public function shortcode_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tu panel de anunciante.', 'flavor-chat-ia') . '</p>';
        }

        $this->enqueue_frontend_assets();

        $template_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/advertising/templates/dashboard.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Template inline si no existe el archivo
        $resultado = $this->action_mis_anuncios([]);
        $anuncios = $resultado['data'] ?? [];

        ob_start();
        ?>
        <div class="flavor-ads-dashboard">
            <div class="ads-dashboard-header">
                <h2><?php _e('Mis Anuncios', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo add_query_arg('vista', 'crear'); ?>" class="btn btn-primary">
                    <?php _e('Crear anuncio', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if ($anuncios): ?>
                <div class="ads-grid">
                    <?php foreach ($anuncios as $ad): ?>
                        <div class="ads-card">
                            <h4><?php echo esc_html($ad['titulo']); ?></h4>
                            <div class="ads-card-meta">
                                <span class="ads-status <?php echo esc_attr($ad['estado']); ?>">
                                    <?php echo esc_html($ad['estado']); ?>
                                </span>
                                <span><?php echo esc_html($ad['tipo']); ?></span>
                            </div>
                            <div class="ads-card-stats">
                                <div>
                                    <strong><?php echo number_format($ad['impresiones']); ?></strong>
                                    <span><?php _e('Impresiones', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div>
                                    <strong><?php echo number_format($ad['clics']); ?></strong>
                                    <span><?php _e('Clics', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div>
                                    <strong><?php echo number_format($ad['gasto'], 2); ?>€</strong>
                                    <span><?php _e('Gasto', 'flavor-chat-ia'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ads-empty">
                    <p><?php _e('Aún no tienes anuncios. ¡Crea tu primera campaña!', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear anuncio
     */
    public function shortcode_crear($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para crear anuncios.', 'flavor-chat-ia') . '</p>';
        }

        $this->enqueue_frontend_assets();

        $template_path = FLAVOR_CHAT_IA_PATH . 'includes/modules/advertising/templates/crear.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div class="flavor-ads-crear">
            <h2><?php _e('Crear nuevo anuncio', 'flavor-chat-ia'); ?></h2>
            <form class="ads-form" id="crear-anuncio-form">
                <?php wp_nonce_field('flavor_ads_nonce', 'nonce'); ?>

                <div class="form-grupo">
                    <label><?php _e('Título del anuncio', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" name="titulo" required>
                </div>

                <div class="form-grupo">
                    <label><?php _e('Tipo de anuncio', 'flavor-chat-ia'); ?></label>
                    <select name="tipo">
                        <option value="<?php echo esc_attr__('banner_horizontal', 'flavor-chat-ia'); ?>"><?php _e('Banner Horizontal', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('banner_sidebar', 'flavor-chat-ia'); ?>"><?php _e('Banner Sidebar', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('banner_card', 'flavor-chat-ia'); ?>"><?php _e('Tarjeta', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('banner_nativo', 'flavor-chat-ia'); ?>"><?php _e('Nativo', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="form-grupo">
                    <label><?php _e('URL de destino', 'flavor-chat-ia'); ?> *</label>
                    <input type="url" name="url_destino" required placeholder="<?php echo esc_attr__('https://...', 'flavor-chat-ia'); ?>">
                </div>

                <div class="form-grupo">
                    <label><?php _e('Imagen del anuncio', 'flavor-chat-ia'); ?></label>
                    <input type="url" name="imagen" placeholder="<?php echo esc_attr__('URL de la imagen', 'flavor-chat-ia'); ?>">
                </div>

                <div class="form-grupo">
                    <label><?php _e('Presupuesto (€)', 'flavor-chat-ia'); ?></label>
                    <input type="number" name="presupuesto" min="0" step="0.01" value="50">
                </div>

                <button type="submit" class="btn btn-primary"><?php _e('Enviar para revisión', 'flavor-chat-ia'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis ingresos
     */
    public function shortcode_ingresos($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tus ingresos.', 'flavor-chat-ia') . '</p>';
        }

        $this->enqueue_frontend_assets();

        $template_path = __DIR__ . '/templates/mis-ingresos.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Fallback al template inline
        $resultado = $this->action_mis_ingresos(['periodo' => 'month']);
        $data = $resultado['data'] ?? [];

        ob_start();
        ?>
        <div class="flavor-ads-ingresos">
            <h2><?php esc_html_e('Mis Ingresos por Publicidad', 'flavor-chat-ia'); ?></h2>
            <p style="color: #6b7280;"><?php esc_html_e('Como miembro de la comunidad, recibes una parte de los ingresos publicitarios.', 'flavor-chat-ia'); ?></p>

            <div class="ingresos-stats">
                <div class="ingreso-card">
                    <span class="ingreso-valor"><?php echo esc_html(number_format($data['total_periodo'] ?? 0, 2)); ?>€</span>
                    <span class="ingreso-label"><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="ingreso-card">
                    <span class="ingreso-valor"><?php echo esc_html(number_format($data['pendiente_pago'] ?? 0, 2)); ?>€</span>
                    <span class="ingreso-label"><?php esc_html_e('Pendiente de pago', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue scripts y estilos frontend
     */
    private function enqueue_frontend_assets() {
        if (!$this->can_activate()) {
            return;
        }

        static $enqueued = false;
        if ($enqueued) return;

        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_style(
            'flavor-ads-frontend',
            $base_url . 'css/advertising-frontend.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-ads-frontend',
            $base_url . 'js/advertising-frontend.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-ads-frontend', 'flavorAdsData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_ads_nonce'),
            'strings' => [
                'confirmar' => __('¿Estás seguro?', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'exito' => __('Operación completada', 'flavor-chat-ia'),
            ],
        ]);

        $enqueued = true;
    }

    /**
     * Enqueue scripts para frontend global (tracking)
     */
    public function enqueue_scripts() {
        if (is_admin()) return;

        $base_url = plugins_url('assets/', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_enqueue_script(
            'flavor-ads-tracking',
            $base_url . 'js/tracking.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-ads-tracking', 'flavorAdsTracking', [
            'ajaxurl' => admin_url('admin-ajax.php'),
        ]);
    }

    /**
     * WP Cron: Procesar pagos pendientes
     */
    public function cron_procesar_pagos() {
        global $wpdb;
        $tabla_ingresos = $wpdb->prefix . 'flavor_ads_ingresos';

        // Obtener usuarios con saldo pendiente superior al mínimo
        $minimo_pago = 10; // 10€ mínimo para pagar

        $usuarios = $wpdb->get_results($wpdb->prepare(
            "SELECT usuario_id, SUM(cantidad) as total
             FROM $tabla_ingresos
             WHERE estado = 'pendiente'
             GROUP BY usuario_id
             HAVING total >= %f",
            $minimo_pago
        ));

        foreach ($usuarios as $usuario) {
            // Notificar que hay pago disponible
            do_action('flavor_notificacion_enviar', [
                'tipo' => 'ads_pago_disponible',
                'destinatario' => $usuario->usuario_id,
                'datos' => ['cantidad' => $usuario->total],
            ]);
        }
    }

    /**
     * WP Cron: Actualizar estadísticas
     */
    public function cron_actualizar_estadisticas() {
        // Verificar presupuestos agotados
        $anuncios = get_posts([
            'post_type' => 'flavor_ad',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        global $wpdb;
        $tabla_stats = $wpdb->prefix . 'flavor_ads_stats';

        foreach ($anuncios as $ad) {
            $presupuesto = floatval(get_post_meta($ad->ID, '_ad_presupuesto', true));
            if ($presupuesto <= 0) continue;

            $gasto = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(gasto) FROM $tabla_stats WHERE ad_id = %d",
                $ad->ID
            ));

            if ($gasto >= $presupuesto) {
                wp_update_post(['ID' => $ad->ID, 'post_status' => 'draft']);

                $anunciante = get_post_meta($ad->ID, '_ad_anunciante_id', true);
                if ($anunciante) {
                    do_action('flavor_notificacion_enviar', [
                        'tipo' => 'ads_presupuesto_agotado',
                        'destinatario' => $anunciante,
                        'datos' => [
                            'ad_id' => $ad->ID,
                            'titulo' => $ad->post_title,
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * Helper: Fecha inicio según periodo
     */
    private function get_fecha_inicio_periodo($periodo) {
        switch ($periodo) {
            case 'today':
                return date('Y-m-d');
            case 'week':
                return date('Y-m-d', strtotime('-7 days'));
            case 'month':
            default:
                return date('Y-m-d', strtotime('-30 days'));
            case 'year':
                return date('Y-m-d', strtotime('-1 year'));
        }
    }

    /**
     * Helper: Formatear respuesta de anuncio
     */
    private function format_ad_response($ad) {
        return [
            'id' => $ad->ID,
            'titulo' => $ad->post_title,
            'tipo' => get_post_meta($ad->ID, '_ad_tipo', true),
            'imagen' => get_post_meta($ad->ID, '_ad_imagen_url', true) ?: get_the_post_thumbnail_url($ad->ID, 'large'),
            'url' => get_post_meta($ad->ID, '_ad_url_destino', true),
            'cta' => get_post_meta($ad->ID, '_ad_texto_cta', true),
            'estado' => $ad->post_status,
        ];
    }

    /**
     * Definiciones de herramientas IA
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'advertising_stats',
                'description' => 'Obtener estadísticas de publicidad',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'periodo' => ['type' => 'string', 'description' => 'Periodo: today, week, month, year'],
                        'ad_id' => ['type' => 'integer', 'description' => 'ID del anuncio (opcional)'],
                    ],
                ],
            ],
            [
                'name' => 'advertising_crear',
                'description' => 'Crear un nuevo anuncio',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => ['type' => 'string', 'description' => 'Título del anuncio'],
                        'tipo' => ['type' => 'string', 'description' => 'Tipo: banner_horizontal, banner_sidebar, banner_card, banner_nativo'],
                        'url_destino' => ['type' => 'string', 'description' => 'URL de destino'],
                        'imagen' => ['type' => 'string', 'description' => 'URL de la imagen'],
                        'presupuesto' => ['type' => 'number', 'description' => 'Presupuesto en euros'],
                    ],
                    'required' => ['titulo', 'url_destino'],
                ],
            ],
        ];
    }

    /**
     * Base de conocimiento IA
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Publicidad Ética**

Sistema de anuncios responsables con reparto de beneficios a la comunidad.

**Tipos de anuncios:**
- Banner horizontal (728x90)
- Banner sidebar (300x250)
- Anuncio tipo tarjeta
- Anuncio nativo

**Características:**
- Publicidad no intrusiva
- Etiquetado transparente ("Anuncio")
- Reparto de beneficios con la comunidad (configurable, por defecto 30%)
- Sistema de pago por clic (CPC) e impresiones (CPM)
- Aprobación manual de anuncios
- Segmentación por ubicación

**Modelo de ingresos:**
- Los anunciantes pagan por clics e impresiones
- Un porcentaje se reparte con la comunidad
- Los usuarios activos reciben parte de los ingresos
- Pagos cuando se alcanza el mínimo (10€)

**Comandos disponibles:**
- ver_estadisticas: Consultar métricas de publicidad
- listar_anuncios: Ver anuncios activos
- crear_anuncio: Crear nueva campaña
- mis_anuncios: Ver mis campañas como anunciante
- pausar_anuncio / reanudar_anuncio: Gestionar estado
- mis_ingresos: Ver ingresos compartidos
KNOWLEDGE;
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'banner_horizontal' => [
                'label' => __('Banner Horizontal', 'flavor-chat-ia'),
                'description' => __('Banner publicitario horizontal (728x90 o similar)', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-slides',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', 'flavor-chat-ia'),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'position' => [
                        'type' => 'select',
                        'label' => __('Posición', 'flavor-chat-ia'),
                        'options' => ['header', 'content_top', 'content_bottom', 'footer'],
                        'default' => 'content_top',
                    ],
                    'mostrar_etiqueta' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar etiqueta "Anuncio"', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'advertising/banner-horizontal',
            ],
            'banner_sidebar' => [
                'label' => __('Banner Sidebar', 'flavor-chat-ia'),
                'description' => __('Banner vertical para barra lateral (300x250)', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-slides',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', 'flavor-chat-ia'),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'mostrar_etiqueta' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar etiqueta "Anuncio"', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'sticky' => [
                        'type' => 'toggle',
                        'label' => __('Fijo al hacer scroll', 'flavor-chat-ia'),
                        'default' => false,
                    ],
                ],
                'template' => 'advertising/banner-sidebar',
            ],
            'banner_card' => [
                'label' => __('Anuncio Tipo Tarjeta', 'flavor-chat-ia'),
                'description' => __('Anuncio integrado como tarjeta de contenido', 'flavor-chat-ia'),
                'category' => 'cards',
                'icon' => 'dashicons-format-aside',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', 'flavor-chat-ia'),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['minimal', 'card', 'featured'],
                        'default' => 'card',
                    ],
                ],
                'template' => 'advertising/banner-card',
            ],
            'banner_nativo' => [
                'label' => __('Anuncio Nativo', 'flavor-chat-ia'),
                'description' => __('Anuncio que se integra con el diseño del contenido', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-admin-page',
                'fields' => [
                    'ad_id' => [
                        'type' => 'select',
                        'label' => __('Anuncio', 'flavor-chat-ia'),
                        'options' => $this->get_available_ads(),
                        'default' => '',
                    ],
                    'titulo_personalizado' => [
                        'type' => 'text',
                        'label' => __('Título personalizado', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
                'template' => 'advertising/banner-nativo',
            ],
        ];
    }

    /**
     * Obtener anuncios disponibles
     */
    private function get_available_ads() {
        $ads = get_posts([
            'post_type' => 'flavor_ad',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $options = ['' => __('Seleccionar anuncio', 'flavor-chat-ia')];
        foreach ($ads as $ad) {
            $options[$ad->ID] = $ad->post_title;
        }

        return $options;
    }

    /**
     * Configuración del módulo
     */
    public function get_settings() {
        return get_option('flavor_advertising_settings', [
            'reparto_comunidad_default' => 30,
            'precio_clic_default' => 0.10,
            'precio_cpm_default' => 1.00,
            'minimo_pago' => 10,
            'aprobacion_automatica' => false,
            'mostrar_etiqueta' => true,
        ]);
    }

    /**
     * Guardar configuración
     */
    public function save_settings($settings) {
        update_option('flavor_advertising_settings', $settings);
    }


    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Define las páginas del módulo para V3
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Publicidad', 'flavor-chat-ia'),
                'slug' => 'publicidad',
                'content' => '<h1>' . __('Publicidad', 'flavor-chat-ia') . '</h1>
<p>' . __('Promociona tu negocio o servicios', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="advertising" action="listar_anuncios" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Crear Anuncio', 'flavor-chat-ia'),
                'slug' => 'crear-anuncio',
                'content' => '<h1>' . __('Crear Anuncio', 'flavor-chat-ia') . '</h1>
<p>' . __('Crea un nuevo anuncio publicitario', 'flavor-chat-ia') . '</p>

[flavor_module_form module="advertising" action="crear_anuncio"]',
                'parent' => 'publicidad',
            ],
            [
                'title' => __('Mis Campañas', 'flavor-chat-ia'),
                'slug' => 'mis-campanas',
                'content' => '<h1>' . __('Mis Campañas', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona tus campañas publicitarias', 'flavor-chat-ia') . '</p>

[flavor_module_dashboard module="advertising" action="mis_campanas"]',
                'parent' => 'publicidad',
            ],
            [
                'title' => __('Estadísticas', 'flavor-chat-ia'),
                'slug' => 'estadisticas-publicidad',
                'content' => '<h1>' . __('Estadísticas', 'flavor-chat-ia') . '</h1>
<p>' . __('Analiza el rendimiento de tus anuncios', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="advertising" action="estadisticas"]',
                'parent' => 'publicidad',
            ],
        ];
    }

    /**
     * Definición de shortcodes disponibles
     *
     * @return array
     */
    public function get_shortcodes() {
        return [
            'flavor_ad' => [
                'name' => __('Mostrar Anuncio', 'flavor-chat-ia'),
                'description' => __('Muestra un anuncio específico o aleatorio según el tipo', 'flavor-chat-ia'),
                'atts' => [
                    'id' => [
                        'type' => 'number',
                        'label' => __('ID del anuncio', 'flavor-chat-ia'),
                        'description' => __('ID específico del anuncio a mostrar (opcional)', 'flavor-chat-ia'),
                        'default' => 0,
                    ],
                    'tipo' => [
                        'type' => 'select',
                        'label' => __('Tipo de anuncio', 'flavor-chat-ia'),
                        'options' => [
                            'banner_horizontal' => __('Banner Horizontal', 'flavor-chat-ia'),
                            'banner_sidebar' => __('Banner Sidebar', 'flavor-chat-ia'),
                            'banner_card' => __('Tarjeta', 'flavor-chat-ia'),
                            'banner_nativo' => __('Nativo', 'flavor-chat-ia'),
                        ],
                        'default' => 'banner_horizontal',
                    ],
                    'ubicacion' => [
                        'type' => 'text',
                        'label' => __('Ubicación', 'flavor-chat-ia'),
                        'description' => __('Slug de la taxonomía de ubicación', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                    'class' => [
                        'type' => 'text',
                        'label' => __('Clases CSS adicionales', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
            ],
            'flavor_ads_dashboard' => [
                'name' => __('Dashboard del Anunciante', 'flavor-chat-ia'),
                'description' => __('Panel de control para gestionar anuncios propios', 'flavor-chat-ia'),
                'atts' => [],
            ],
            'flavor_ads_crear' => [
                'name' => __('Formulario Crear Anuncio', 'flavor-chat-ia'),
                'description' => __('Formulario para crear un nuevo anuncio', 'flavor-chat-ia'),
                'atts' => [],
            ],
            'flavor_ads_ingresos' => [
                'name' => __('Mis Ingresos Publicitarios', 'flavor-chat-ia'),
                'description' => __('Muestra los ingresos del usuario por reparto publicitario', 'flavor-chat-ia'),
                'atts' => [],
            ],
        ];
    }

    /**
     * Configuración del formulario de creación de anuncios
     *
     * @return array
     */
    public function get_form_config() {
        return [
            'titulo' => __('Crear Anuncio', 'flavor-chat-ia'),
            'descripcion' => __('Completa el formulario para crear un nuevo anuncio publicitario', 'flavor-chat-ia'),
            'campos' => [
                'titulo' => [
                    'type' => 'text',
                    'label' => __('Título del anuncio', 'flavor-chat-ia'),
                    'required' => true,
                    'placeholder' => __('Ej: Promoción de verano', 'flavor-chat-ia'),
                ],
                'tipo' => [
                    'type' => 'select',
                    'label' => __('Tipo de anuncio', 'flavor-chat-ia'),
                    'required' => true,
                    'options' => [
                        'banner_horizontal' => __('Banner Horizontal (728x90)', 'flavor-chat-ia'),
                        'banner_sidebar' => __('Banner Sidebar (300x250)', 'flavor-chat-ia'),
                        'banner_card' => __('Tarjeta con imagen', 'flavor-chat-ia'),
                        'banner_nativo' => __('Anuncio nativo', 'flavor-chat-ia'),
                    ],
                ],
                'url_destino' => [
                    'type' => 'url',
                    'label' => __('URL de destino', 'flavor-chat-ia'),
                    'required' => true,
                    'placeholder' => 'https://tu-sitio.com/landing',
                ],
                'imagen' => [
                    'type' => 'url',
                    'label' => __('URL de la imagen', 'flavor-chat-ia'),
                    'required' => false,
                    'placeholder' => 'https://...',
                    'description' => __('Tamaño recomendado según tipo de anuncio', 'flavor-chat-ia'),
                ],
                'texto_cta' => [
                    'type' => 'text',
                    'label' => __('Texto del botón', 'flavor-chat-ia'),
                    'required' => false,
                    'default' => __('Saber más', 'flavor-chat-ia'),
                ],
                'presupuesto' => [
                    'type' => 'number',
                    'label' => __('Presupuesto (€)', 'flavor-chat-ia'),
                    'required' => false,
                    'min' => 5,
                    'step' => 0.01,
                    'default' => 50,
                ],
                'fecha_inicio' => [
                    'type' => 'date',
                    'label' => __('Fecha de inicio', 'flavor-chat-ia'),
                    'required' => false,
                ],
                'fecha_fin' => [
                    'type' => 'date',
                    'label' => __('Fecha de fin', 'flavor-chat-ia'),
                    'required' => false,
                ],
            ],
            'submit_text' => __('Enviar para revisión', 'flavor-chat-ia'),
            'action' => 'crear_anuncio',
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo_tab = dirname(__FILE__) . '/class-advertising-dashboard-tab.php';
        if (file_exists($archivo_tab)) {
            require_once $archivo_tab;
            Flavor_Advertising_Dashboard_Tab::get_instance();
        }
    }
}
