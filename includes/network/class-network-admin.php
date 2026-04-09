<?php
/**
 * Panel de Administración de Red de Comunidades
 *
 * Interfaz de administración para gestionar el nodo local,
 * conexiones, contenido compartido, colaboraciones y configuración.
 *
 * @package FlavorChatIA\Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // El menú se registra centralizadamente en admin/class-admin-menu-manager.php
        // No registrar aquí para evitar duplicados
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Registra menús de administración
     */
    public function add_admin_menus() {
        // Menú principal de Red
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Red de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Red', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-platform-network',
            [$this, 'render_main_page']
        );
    }

    /**
     * Encola assets del admin
     */
    public function enqueue_admin_assets($hook) {
        $hook = (string) $hook;
        if (strpos($hook, 'flavor-network') === false && strpos($hook, 'flavor-platform-network') === false) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_media();

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-network-admin',
            FLAVOR_CHAT_IA_URL . "assets/css/admin/network-admin{$sufijo_asset}.css",
            [],
            Flavor_Network_Manager::VERSION
        );

        // Leaflet para mapa en admin
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

        wp_enqueue_script(
            'flavor-network-admin',
            FLAVOR_CHAT_IA_URL . "assets/js/network-admin{$sufijo_asset}.js",
            ['jquery', 'wp-color-picker', 'leaflet'],
            Flavor_Network_Manager::VERSION,
            true
        );

        wp_localize_script('flavor-network-admin', 'flavorNetworkAdmin', [
            'apiUrl'  => rest_url(Flavor_Network_API::API_NAMESPACE),
            'nonce'   => wp_create_nonce('wp_rest'),
            'siteUrl' => get_site_url(),
            'i18n'    => [
                'guardado'        => __('Guardado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error'           => __('Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmar_eliminar' => __('¿Seguro que quieres eliminar?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando'        => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sin_resultados'  => __('No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'conexion_enviada' => __('Solicitud de conexión enviada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'mensaje_enviado' => __('Mensaje enviado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Renderiza la página principal de Red
     */
    public function render_main_page() {
        $network_manager = Flavor_Network_Manager::get_instance();
        $nodo_local = Flavor_Network_Node::get_local_node();
        $tab_activa = sanitize_text_field($_GET['tab'] ?? 'dashboard');

        // Tabs agrupadas por categoría para mejor navegación
        $tab_groups = [
            'main' => [
                'dashboard' => ['label' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashboard'],
                'mi-nodo'   => ['label' => __('Mi Nodo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'admin-home'],
            ],
            'red' => [
                'group_label' => __('Red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'group_icon'  => 'networking',
                'items' => [
                    'directorio' => __('Directorio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'mapa'       => __('Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'conexiones' => __('Conexiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'matching'   => __('Matching', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ],
            'actividad' => [
                'group_label' => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'group_icon'  => 'portfolio',
                'items' => [
                    'contenido'      => __('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'eventos'        => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'colaboraciones' => __('Colaboraciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'banco-tiempo'   => __('Banco Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'tablon'         => __('Tablón', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ],
            'comunicacion' => [
                'group_label' => __('Comunicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'group_icon'  => 'email',
                'items' => [
                    'mensajes'   => __('Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'alertas'    => __('Alertas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'newsletter' => __('Newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ],
            'extras' => [
                'group_label' => __('Más', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'group_icon'  => 'ellipsis',
                'items' => [
                    'favoritos'       => __('Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'recomendaciones' => __('Recomendaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'preguntas'       => __('Preguntas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'sellos'          => __('Sellos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'modulos'         => __('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
            ],
        ];

        // Para compatibilidad, mantener array plano de tabs
        $tabs = [];
        foreach ($tab_groups as $group_key => $group) {
            if ($group_key === 'main') {
                foreach ($group as $tab_slug => $tab_data) {
                    $tabs[$tab_slug] = $tab_data['label'];
                }
            } elseif (isset($group['items'])) {
                foreach ($group['items'] as $tab_slug => $tab_label) {
                    $tabs[$tab_slug] = $tab_label;
                }
            }
        }
        ?>
        <div class="wrap flavor-network-admin">
            <h1>
                <span class="dashicons dashicons-networking"></span>
                <?php _e('Red de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>

            <?php if (!$nodo_local && $tab_activa !== 'mi-nodo'): ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php _e('Configura tu nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> -
                        <?php _e('Para participar en la red, primero configura tu nodo local.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <a href="<?php echo admin_url('admin.php?page=flavor-platform-network&tab=mi-nodo'); ?>" class="button button-primary" style="margin-left:10px;">
                            <?php _e('Configurar ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <nav class="nav-tab-wrapper flavor-network-tabs flavor-grouped-tabs">
                <?php foreach ($tab_groups as $group_key => $group): ?>
                    <?php if ($group_key === 'main'): ?>
                        <?php foreach ($group as $tab_slug => $tab_data): ?>
                            <a href="<?php echo admin_url('admin.php?page=flavor-platform-network&tab=' . $tab_slug); ?>"
                               class="nav-tab <?php echo $tab_activa === $tab_slug ? 'nav-tab-active' : ''; ?>">
                                <span class="dashicons dashicons-<?php echo esc_attr($tab_data['icon']); ?>"></span>
                                <?php echo esc_html($tab_data['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php
                        $group_active = isset($group['items']) && array_key_exists($tab_activa, $group['items']);
                        ?>
                        <div class="nav-tab-dropdown <?php echo $group_active ? 'has-active' : ''; ?>">
                            <button type="button" class="nav-tab nav-tab-dropdown-toggle <?php echo $group_active ? 'nav-tab-active' : ''; ?>">
                                <span class="dashicons dashicons-<?php echo esc_attr($group['group_icon']); ?>"></span>
                                <?php echo esc_html($group['group_label']); ?>
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                            <div class="nav-tab-dropdown-menu">
                                <?php foreach ($group['items'] as $tab_slug => $tab_label): ?>
                                    <a href="<?php echo admin_url('admin.php?page=flavor-platform-network&tab=' . $tab_slug); ?>"
                                       class="nav-tab-dropdown-item <?php echo $tab_activa === $tab_slug ? 'is-active' : ''; ?>">
                                        <?php echo esc_html($tab_label); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div class="flavor-network-content" style="margin-top:20px;">
                <?php
                switch ($tab_activa) {
                    case 'mi-nodo':
                        $this->render_tab_mi_nodo($nodo_local);
                        break;
                    case 'directorio':
                        $this->render_tab_directorio();
                        break;
                    case 'mapa':
                        $this->render_tab_mapa();
                        break;
                    case 'conexiones':
                        $this->render_tab_conexiones($nodo_local);
                        break;
                    case 'contenido':
                        $this->render_tab_contenido($nodo_local);
                        break;
                    case 'matching':
                        $this->render_tab_matching($nodo_local);
                        break;
                    case 'eventos':
                        $this->render_tab_eventos($nodo_local);
                        break;
                    case 'colaboraciones':
                        $this->render_tab_colaboraciones($nodo_local);
                        break;
                    case 'banco-tiempo':
                        $this->render_tab_banco_tiempo($nodo_local);
                        break;
                    case 'tablon':
                        $this->render_tab_tablon($nodo_local);
                        break;
                    case 'mensajes':
                        $this->render_tab_mensajes($nodo_local);
                        break;
                    case 'alertas':
                        $this->render_tab_alertas($nodo_local);
                        break;
                    case 'favoritos':
                        $this->render_tab_favoritos($nodo_local);
                        break;
                    case 'recomendaciones':
                        $this->render_tab_recomendaciones($nodo_local);
                        break;
                    case 'preguntas':
                        $this->render_tab_preguntas($nodo_local);
                        break;
                    case 'sellos':
                        $this->render_tab_sellos($nodo_local);
                        break;
                    case 'newsletter':
                        $this->render_tab_newsletter($nodo_local);
                        break;
                    case 'modulos':
                        $this->render_tab_modulos($nodo_local);
                        break;
                    default:
                        $this->render_tab_dashboard($nodo_local);
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Dashboard ───

    private function render_tab_dashboard($nodo_local) {
        global $wpdb;
        $prefix = Flavor_Network_Installer::get_table_name('');

        $total_nodos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}nodes WHERE estado = 'activo'");
        $total_nodos_inactivos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}nodes WHERE estado != 'activo'");
        $total_conexiones = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}connections WHERE estado = 'aprobada'");
        $total_conexiones_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}connections WHERE estado = 'pendiente'");
        $total_contenido = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}shared_content WHERE estado = 'activo'");
        $total_eventos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}events WHERE estado = 'activo' AND fecha_inicio >= NOW()");
        $total_colaboraciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}collaborations WHERE estado = 'abierta'");
        $mensajes_sin_leer = 0;
        $ultima_sync = $wpdb->get_var("SELECT MAX(ultima_sincronizacion) FROM {$prefix}nodes");
        $ultima_sync_local = $nodo_local ? $nodo_local->ultima_sincronizacion : null;

        if ($nodo_local) {
            $mensajes_sin_leer = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$prefix}messages WHERE a_nodo_id = %d AND leido = 0",
                $nodo_local->id
            ));
        }
        ?>
        <div class="flavor-network-dashboard">
            <div class="flavor-network-dashboard-header">
                <div>
                    <h2><?php _e('Resumen de la red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p class="description">
                        <?php _e('Estado general de la red, métricas clave y accesos rápidos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>
                <div class="flavor-network-status">
                    <span class="network-status-indicator <?php echo $nodo_local ? 'is-online' : 'is-offline'; ?>"></span>
                    <span>
                        <?php echo $nodo_local ? __('Nodo local activo', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Nodo local sin configurar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </div>
            </div>

            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-groups"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $total_nodos; ?></span>
                        <span class="stat-label"><?php _e('Nodos en la red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-networking"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $total_conexiones; ?></span>
                        <span class="stat-label"><?php _e('Conexiones activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-portfolio"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $total_contenido; ?></span>
                        <span class="stat-label"><?php _e('Contenidos compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $total_eventos; ?></span>
                        <span class="stat-label"><?php _e('Eventos próximos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $total_colaboraciones; ?></span>
                        <span class="stat-label"><?php _e('Colaboraciones abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card <?php echo $mensajes_sin_leer > 0 ? 'has-notifications' : ''; ?>">
                    <span class="dashicons dashicons-email"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $mensajes_sin_leer; ?></span>
                        <span class="stat-label"><?php _e('Mensajes sin leer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-network-dashboard-grid">
                <div class="flavor-network-card">
                    <h3><?php _e('Acciones rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="flavor-quick-actions">
                        <a class="button button-primary" href="<?php echo admin_url('admin.php?page=flavor-platform-network&tab=mi-nodo'); ?>">
                            <?php _e('Configurar nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('admin.php?page=flavor-platform-network&tab=directorio'); ?>">
                            <?php _e('Ver directorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('admin.php?page=flavor-platform-network&tab=mapa'); ?>">
                            <?php _e('Mapa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('admin.php?page=flavor-platform-network&tab=conexiones'); ?>">
                            <?php _e('Conexiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('admin.php?page=flavor-platform-network&tab=contenido'); ?>">
                            <?php _e('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('admin.php?page=flavor-platform-network&tab=matching'); ?>">
                            <?php _e('Matching', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>

                <div class="flavor-network-card">
                    <h3><?php _e('Indicadores clave', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="flavor-summary-grid">
                        <div>
                            <span class="summary-label"><?php _e('Conexiones pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="summary-value"><?php echo $total_conexiones_pendientes; ?></span>
                        </div>
                        <div>
                            <span class="summary-label"><?php _e('Nodos inactivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="summary-value"><?php echo $total_nodos_inactivos; ?></span>
                        </div>
                        <div>
                            <span class="summary-label"><?php _e('Última sync global', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="summary-value">
                                <?php echo $ultima_sync ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ultima_sync))) : __('Nunca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>
                        <div>
                            <span class="summary-label"><?php _e('Última sync local', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="summary-value">
                                <?php echo $ultima_sync_local ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ultima_sync_local))) : __('Nunca', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($nodo_local): ?>
                <div class="flavor-network-info-card" style="margin-top:20px;">
                    <h3><?php _e('Tu nodo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html($nodo_local->nombre); ?></h3>
                    <p><strong><?php _e('Tipo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html(Flavor_Network_Node::TIPOS_ENTIDAD[$nodo_local->tipo_entidad] ?? $nodo_local->tipo_entidad); ?></p>
                    <p><strong><?php _e('Nivel:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html(Flavor_Network_Node::NIVELES_CONSCIENCIA[$nodo_local->nivel_consciencia] ?? $nodo_local->nivel_consciencia); ?></p>
                    <p><strong><?php _e('API Endpoint:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <code><?php echo esc_url(rest_url(Flavor_Network_API::API_NAMESPACE)); ?></code></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ─── TAB: Mi Nodo ───

    private function render_tab_mi_nodo($nodo_local) {
        ?>
        <div class="flavor-network-mi-nodo">
            <h2><?php _e('Configuración de tu nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php _e('Configura los datos de tu comunidad/entidad para aparecer en la red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <form id="flavor-nodo-form" class="flavor-network-form">
                <table class="form-table">
                    <tr>
                        <th><label for="nodo-nombre"><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><input type="text" id="nodo-nombre" name="nombre" value="<?php echo esc_attr($nodo_local->nombre ?? get_bloginfo('name')); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-slug"><?php _e('Slug (URL)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><input type="text" id="nodo-slug" name="slug" value="<?php echo esc_attr($nodo_local->slug ?? sanitize_title(get_bloginfo('name'))); ?>" class="regular-text" required pattern="[a-z0-9-]+"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-descripcion-corta"><?php _e('Descripción corta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="nodo-descripcion-corta" name="descripcion_corta" value="<?php echo esc_attr($nodo_local->descripcion_corta ?? ''); ?>" class="large-text" maxlength="500"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-descripcion"><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><textarea id="nodo-descripcion" name="descripcion" rows="4" class="large-text"><?php echo esc_textarea($nodo_local->descripcion ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-tipo"><?php _e('Tipo de entidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="nodo-tipo" name="tipo_entidad">
                                <?php foreach (Flavor_Network_Node::TIPOS_ENTIDAD as $clave_tipo => $etiqueta_tipo): ?>
                                    <option value="<?php echo esc_attr($clave_tipo); ?>" <?php selected($nodo_local->tipo_entidad ?? 'comunidad', $clave_tipo); ?>>
                                        <?php echo esc_html($etiqueta_tipo); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nodo-sector"><?php _e('Sector', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="nodo-sector" name="sector" value="<?php echo esc_attr($nodo_local->sector ?? ''); ?>" class="regular-text" placeholder="<?php esc_attr_e('ej: alimentación, educación, tecnología...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-nivel"><?php _e('Nivel de consciencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="nodo-nivel" name="nivel_consciencia">
                                <?php foreach (Flavor_Network_Node::NIVELES_CONSCIENCIA as $clave_nivel => $etiqueta_nivel): ?>
                                    <option value="<?php echo esc_attr($clave_nivel); ?>" <?php selected($nodo_local->nivel_consciencia ?? 'basico', $clave_nivel); ?>>
                                        <?php echo esc_html($etiqueta_nivel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nodo-logo"><?php _e('Logo URL', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <input type="url" id="nodo-logo" name="logo_url" value="<?php echo esc_url($nodo_local->logo_url ?? ''); ?>" class="regular-text">
                            <button type="button" class="button flavor-upload-media" data-target="#nodo-logo"><?php _e('Subir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                            <?php if (!empty($nodo_local->logo_url)): ?>
                                <br><img src="<?php echo esc_url($nodo_local->logo_url); ?>" style="max-height:60px;margin-top:5px;">
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="nodo-direccion"><?php _e('Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="nodo-direccion" name="direccion" value="<?php echo esc_attr($nodo_local->direccion ?? ''); ?>" class="large-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-ciudad"><?php _e('Ciudad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="nodo-ciudad" name="ciudad" value="<?php echo esc_attr($nodo_local->ciudad ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-provincia"><?php _e('Provincia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="nodo-provincia" name="provincia" value="<?php echo esc_attr($nodo_local->provincia ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-pais"><?php _e('País', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="nodo-pais" name="pais" value="<?php echo esc_attr($nodo_local->pais ?? 'ES'); ?>" class="small-text" maxlength="5"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Coordenadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <input type="number" id="nodo-latitud" name="latitud" value="<?php echo esc_attr($nodo_local->latitud ?? ''); ?>" step="0.00000001" placeholder="Latitud" style="width:150px;">
                            <input type="number" id="nodo-longitud" name="longitud" value="<?php echo esc_attr($nodo_local->longitud ?? ''); ?>" step="0.00000001" placeholder="Longitud" style="width:150px;">
                            <button type="button" class="button" id="btn-geolocate"><?php _e('Obtener ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                            <div id="nodo-map-preview" style="height:250px;margin-top:10px;border:1px solid #ddd;"></div>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="nodo-email"><?php _e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="email" id="nodo-email" name="email" value="<?php echo esc_attr($nodo_local->email ?? get_option('admin_email')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-telefono"><?php _e('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="tel" id="nodo-telefono" name="telefono" value="<?php echo esc_attr($nodo_local->telefono ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-web"><?php _e('Web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="url" id="nodo-web" name="web" value="<?php echo esc_url($nodo_local->web ?? get_site_url()); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-tags"><?php _e('Tags', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <input type="text" id="nodo-tags" name="tags" value="<?php echo esc_attr(implode(', ', $nodo_local ? $nodo_local->get_tags() : [])); ?>" class="large-text" placeholder="<?php esc_attr_e('tag1, tag2, tag3...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <p class="description"><?php _e('Separados por comas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary button-hero" id="btn-guardar-nodo">
                        <?php _e('Guardar configuración del nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <span id="nodo-save-status" style="margin-left:10px;"></span>
                </p>
            </form>
            <?php if ($nodo_local): ?>
            <div style="margin-top:30px;padding:20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                <h3><?php _e('Código QR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="description"><?php _e('Genera un código QR para compartir tu perfil de nodo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <div style="display:flex;gap:20px;align-items:flex-start;margin-top:15px;">
                    <div id="qr-preview" style="text-align:center;">
                        <img id="qr-img" src="" alt="QR" style="width:200px;height:200px;border:1px solid #e5e7eb;border-radius:8px;display:none;">
                        <p id="qr-loading" class="description"><?php _e('Haz clic en "Generar QR" para ver el código', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                    <div>
                        <button type="button" class="button button-primary" id="btn-generar-qr" data-slug="<?php echo esc_attr($nodo_local->slug); ?>"><?php _e('Generar QR del perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button><br><br>
                        <button type="button" class="button" id="btn-generar-qr-vcard" data-slug="<?php echo esc_attr($nodo_local->slug); ?>"><?php _e('Generar QR vCard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button><br><br>
                        <select id="qr-size">
                            <option value="200">200x200</option>
                            <option value="300" selected>300x300</option>
                            <option value="500">500x500</option>
                        </select>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ─── TAB: Directorio ───

    private function render_tab_directorio() {
        ?>
        <div class="flavor-network-directorio">
            <h2><?php _e('Directorio de la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-directory-filters" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;">
                <input type="text" id="dir-busqueda" placeholder="<?php esc_attr_e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="regular-text">
                <select id="dir-tipo">
                    <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach (Flavor_Network_Node::TIPOS_ENTIDAD as $clave_tipo => $etiqueta_tipo): ?>
                        <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="dir-nivel">
                    <option value=""><?php _e('Todos los niveles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach (Flavor_Network_Node::NIVELES_CONSCIENCIA as $clave_nivel => $etiqueta_nivel): ?>
                        <option value="<?php echo esc_attr($clave_nivel); ?>"><?php echo esc_html($etiqueta_nivel); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-primary" id="btn-buscar-dir"><?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <button type="button" class="button" id="btn-add-node"><?php _e('Añadir nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div id="directorio-resultados" class="flavor-directory-grid">
                <p class="description"><?php _e('Cargando directorio...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div id="directorio-paginacion" style="margin-top:15px;"></div>
        </div>
        <?php
    }

    // ─── TAB: Mapa ───

    private function render_tab_mapa() {
        ?>
        <div class="flavor-network-mapa">
            <h2><?php _e('Mapa de la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-map-filters" style="margin-bottom:15px;display:flex;gap:10px;flex-wrap:wrap;">
                <select id="map-tipo">
                    <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach (Flavor_Network_Node::TIPOS_ENTIDAD as $clave_tipo => $etiqueta_tipo): ?>
                        <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="map-nivel">
                    <option value=""><?php _e('Todos los niveles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach (Flavor_Network_Node::NIVELES_CONSCIENCIA as $clave_nivel => $etiqueta_nivel): ?>
                        <option value="<?php echo esc_attr($clave_nivel); ?>"><?php echo esc_html($etiqueta_nivel); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button" id="btn-filtrar-mapa"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <button type="button" class="button" id="btn-mi-ubicacion"><?php _e('Mi ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div id="network-admin-map" style="height:500px;border:1px solid #ddd;border-radius:4px;"></div>
            <p class="description" style="margin-top:5px;"><?php _e('Haz clic en un nodo para ver su información.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
        <?php
    }

    // ─── TAB: Conexiones ───

    private function render_tab_conexiones($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-conexiones">
            <h2><?php _e('Mis Conexiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div id="conexiones-lista" class="flavor-connections-list">
                <p class="description"><?php _e('Cargando conexiones...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Contenido ───

    private function render_tab_contenido($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }

        $tipos_contenido = [
            'producto'   => __('Producto', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'servicio'   => __('Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'espacio'    => __('Espacio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'recurso'    => __('Recurso', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'excedente'  => __('Excedente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'necesidad'  => __('Necesidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'saber'      => __('Saber/Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        ?>
        <div class="flavor-network-contenido">
            <h2><?php _e('Contenido Compartido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div style="margin-bottom:20px;display:flex;gap:10px;">
                <select id="contenido-tipo-filtro">
                    <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($tipos_contenido as $clave_tipo => $etiqueta_tipo): ?>
                        <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="contenido-busqueda" placeholder="<?php esc_attr_e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="regular-text">
                <button type="button" class="button" id="btn-buscar-contenido"><?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <button type="button" class="button button-primary" id="btn-nuevo-contenido"><?php _e('Nuevo contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <!-- Formulario nuevo contenido (oculto) -->
            <div id="form-nuevo-contenido" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Publicar contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="nuevo-contenido-tipo">
                                <?php foreach ($tipos_contenido as $clave_tipo => $etiqueta_tipo): ?>
                                    <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><input type="text" id="nuevo-contenido-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><textarea id="nuevo-contenido-desc" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="number" id="nuevo-contenido-precio" step="0.01" style="width:100px;"> EUR</td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="nuevo-contenido-ubicacion" class="regular-text"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-publicar-contenido"><?php _e('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" id="btn-cancelar-contenido"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </div>

            <div id="contenido-lista" class="flavor-content-grid">
                <p class="description"><?php _e('Cargando contenido...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Eventos ───

    private function render_tab_eventos($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-eventos">
            <h2><?php _e('Eventos de la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div style="margin-bottom:15px;">
                <button type="button" class="button button-primary" id="btn-nuevo-evento"><?php _e('Crear evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div id="form-nuevo-evento" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Nuevo evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><input type="text" id="evento-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><textarea id="evento-desc" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Fecha inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><input type="datetime-local" id="evento-inicio" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Fecha fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="datetime-local" id="evento-fin"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="evento-tipo">
                                <option value="presencial"><?php _e('Presencial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="online"><?php _e('Online', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="hibrido"><?php _e('Híbrido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="evento-ubicacion" class="regular-text"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-crear-evento"><?php _e('Crear', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" id="btn-cancelar-evento"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </div>

            <div id="eventos-lista">
                <p class="description"><?php _e('Cargando eventos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Colaboraciones ───

    private function render_tab_colaboraciones($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }

        $tipos_colaboracion = [
            'compra_colectiva' => __('Compra colectiva', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'logistica'        => __('Logística compartida', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'proyecto'         => __('Proyecto conjunto', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'alianza'          => __('Alianza temática', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'hermanamiento'    => __('Hermanamiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'mentoria'         => __('Mentoría cruzada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        ?>
        <div class="flavor-network-colaboraciones">
            <h2><?php _e('Colaboraciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div style="margin-bottom:15px;display:flex;gap:10px;">
                <select id="colab-tipo-filtro">
                    <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($tipos_colaboracion as $clave_tipo => $etiqueta_tipo): ?>
                        <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-primary" id="btn-nueva-colab"><?php _e('Nueva colaboración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div id="form-nueva-colab" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Crear colaboración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="colab-tipo">
                                <?php foreach ($tipos_colaboracion as $clave_tipo => $etiqueta_tipo): ?>
                                    <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><input type="text" id="colab-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><textarea id="colab-desc" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Objetivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><textarea id="colab-objetivo" rows="2" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Fecha límite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="datetime-local" id="colab-fecha-limite"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-crear-colab"><?php _e('Crear', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" id="btn-cancelar-colab"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </div>

            <div id="colaboraciones-lista">
                <p class="description"><?php _e('Cargando colaboraciones...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Tablón ───

    private function render_tab_tablon($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-tablon">
            <h2><?php _e('Tablón de la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div style="margin-bottom:15px;">
                <button type="button" class="button button-primary" id="btn-nueva-publicacion"><?php _e('Nueva publicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div id="form-nueva-pub" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Publicar en el tablón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><input type="text" id="pub-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><textarea id="pub-contenido" rows="4" class="large-text" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="pub-tipo">
                                <option value="anuncio"><?php _e('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="noticia"><?php _e('Noticia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="pregunta"><?php _e('Pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="oferta"><?php _e('Oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-enviar-pub"><?php _e('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" id="btn-cancelar-pub"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </div>

            <div id="tablon-lista">
                <p class="description"><?php _e('Cargando tablón...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Mensajes ───

    private function render_tab_mensajes($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-mensajes">
            <h2><?php _e('Mensajería Inter-nodos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div style="margin-bottom:15px;display:flex;gap:10px;">
                <button type="button" class="button btn-msg-tab active" data-tipo="recibidos"><?php _e('Recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <button type="button" class="button btn-msg-tab" data-tipo="enviados"><?php _e('Enviados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <button type="button" class="button button-primary" id="btn-nuevo-mensaje"><?php _e('Nuevo mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div id="form-nuevo-msg" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Enviar mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Destinatario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><select id="msg-destinatario" class="regular-text" required>
                            <option value=""><?php _e('Seleccionar nodo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                        <p class="description"><?php _e('Se cargará la lista de nodos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Asunto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="msg-asunto" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><textarea id="msg-contenido" rows="4" class="large-text" required></textarea></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-enviar-msg"><?php _e('Enviar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" id="btn-cancelar-msg"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </div>

            <div id="mensajes-lista">
                <p class="description"><?php _e('Cargando mensajes...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Alertas solidarias ───

    private function render_tab_alertas($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-alertas">
            <h2><?php _e('Alertas Solidarias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div style="margin-bottom:15px;">
                <button type="button" class="button button-primary" id="btn-nueva-alerta"><?php _e('Crear alerta solidaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div id="form-nueva-alerta" style="display:none;background:#fff3cd;padding:20px;border:1px solid #ffc107;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Nueva alerta solidaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><input type="text" id="alerta-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><textarea id="alerta-desc" rows="3" class="large-text" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Urgencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="alerta-urgencia">
                                <option value="baja"><?php _e('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="media" selected><?php _e('Media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="alta"><?php _e('Alta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="critica"><?php _e('Crítica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="alerta-ubicacion" class="regular-text"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-crear-alerta"><?php _e('Publicar alerta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" id="btn-cancelar-alerta"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </div>

            <div id="alertas-lista">
                <p class="description"><?php _e('Cargando alertas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Banco de Tiempo ───

    private function render_tab_banco_tiempo($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-banco-tiempo">
            <h2><?php _e('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php _e('Intercambia horas de servicio con otros nodos de la red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div style="margin-bottom:15px;display:flex;gap:10px;">
                <select id="tiempo-tipo-filtro">
                    <option value=""><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="oferta"><?php _e('Ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="demanda"><?php _e('Demandas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
                <button type="button" class="button button-primary" id="btn-nueva-oferta-tiempo"><?php _e('Nueva oferta de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div id="form-nueva-oferta-tiempo" style="display:none;background:#f0f9ff;padding:20px;border:1px solid #bae6fd;margin-bottom:20px;border-radius:4px;">
                <h3 id="form-tiempo-titulo"><?php _e('Crear oferta de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <input type="hidden" id="tiempo-edit-id" value="">
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="tiempo-tipo">
                                <option value="oferta"><?php _e('Ofrezco tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="demanda"><?php _e('Necesito tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><input type="text" id="tiempo-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><textarea id="tiempo-desc" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="tiempo-categoria">
                                <option value="formacion"><?php _e('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="tecnologia"><?php _e('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="comunicacion"><?php _e('Comunicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="diseno"><?php _e('Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="administracion"><?php _e('Administración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="logistica"><?php _e('Logística', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="cocina"><?php _e('Cocina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="limpieza"><?php _e('Limpieza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="mantenimiento"><?php _e('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="cuidados"><?php _e('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="otro"><?php _e('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Horas estimadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="number" id="tiempo-horas" step="0.5" min="0.5" style="width:80px;"> h</td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Modalidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="tiempo-modalidad">
                                <option value="presencial"><?php _e('Presencial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="online"><?php _e('Online', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="ambas"><?php _e('Ambas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-guardar-tiempo"><?php _e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" id="btn-cancelar-tiempo"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </div>

            <div id="tiempo-lista">
                <p class="description"><?php _e('Cargando ofertas de tiempo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Favoritos ───

    private function render_tab_favoritos($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-favoritos">
            <h2><?php _e('Mis Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php _e('Nodos que has marcado como favoritos para acceder rápidamente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div id="favoritos-lista">
                <p class="description"><?php _e('Cargando favoritos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Recomendaciones ───

    private function render_tab_recomendaciones($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-recomendaciones">
            <h2><?php _e('Recomendaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php _e('Recomienda nodos a otros o mira las recomendaciones que te han hecho.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div style="margin-bottom:15px;">
                <button type="button" class="button button-primary" id="btn-nueva-recomendacion"><?php _e('Nueva recomendación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div id="form-nueva-recomendacion" style="display:none;background:#f0fdf4;padding:20px;border:1px solid #bbf7d0;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Recomendar un nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Nodo a recomendar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><select id="rec-nodo-recomendado" class="regular-text" required>
                            <option value=""><?php _e('Seleccionar nodo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Recomendar a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><select id="rec-destinatario" class="regular-text" required>
                            <option value=""><?php _e('Seleccionar destinatario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Motivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><textarea id="rec-motivo" rows="3" class="large-text" placeholder="<?php esc_attr_e('¿Por qué recomiendas este nodo?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-enviar-recomendacion"><?php _e('Enviar recomendación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" id="btn-cancelar-recomendacion"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </div>

            <div id="recomendaciones-lista">
                <p class="description"><?php _e('Cargando recomendaciones...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }


    // ─── TAB: Sellos de Calidad ───

    private function render_tab_sellos($nodo_local) {
        ?>
        <div class="flavor-network-sellos">
            <h2><?php _e('Sellos de Calidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php _e('Gestiona los sellos "App Consciente" otorgados a los nodos de la red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div style="margin-bottom:15px;">
                <button type="button" class="button button-primary" id="btn-nuevo-sello"><?php _e('Otorgar sello', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <div id="form-nuevo-sello" style="display:none;background:#fffbeb;padding:20px;border:1px solid #fde68a;margin-bottom:20px;border-radius:4px;">
                <h3 id="form-sello-titulo"><?php _e('Otorgar sello de calidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <input type="hidden" id="sello-edit-id" value="">
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                        <td><select id="sello-nodo" class="regular-text" required>
                            <option value=""><?php _e('Seleccionar nodo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Nivel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="sello-nivel">
                                <option value="basico"><?php _e('Básico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="transicion"><?php _e('Transición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="consciente"><?php _e('Consciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="referente"><?php _e('Referente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Puntuación (0-100)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="number" id="sello-puntuacion" min="0" max="100" value="0" style="width:80px;"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Criterios cumplidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><textarea id="sello-criterios" rows="4" class="large-text" placeholder="<?php esc_attr_e('Lista de criterios que cumple este nodo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Fecha expiración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="date" id="sello-expiracion"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-guardar-sello"><?php _e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" id="btn-cancelar-sello"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </div>

            <div id="sellos-lista">
                <p class="description"><?php _e('Cargando sellos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Newsletter ───

    private function render_tab_newsletter($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-newsletter">
            <div style="display:flex;gap:20px;flex-wrap:wrap;">
                <div style="flex:2;min-width:400px;">
                    <h2><?php _e('Newsletters', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <div style="margin-bottom:15px;display:flex;gap:10px;">
                        <button type="button" class="button button-primary" id="btn-nueva-newsletter"><?php _e('Nueva newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    </div>

                    <div id="form-nueva-newsletter" style="display:none;background:#f0f9ff;padding:20px;border:1px solid #bae6fd;margin-bottom:20px;border-radius:4px;">
                        <h3 id="form-newsletter-titulo"><?php _e('Crear newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <input type="hidden" id="newsletter-edit-id" value="">
                        <table class="form-table">
                            <tr>
                                <th><label><?php _e('Asunto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label></th>
                                <td><input type="text" id="newsletter-asunto" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                                <td>
                                    <select id="newsletter-tipo">
                                        <option value="resumen"><?php _e('Resumen semanal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="noticia"><?php _e('Noticia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="alerta"><?php _e('Alerta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="custom"><?php _e('Personalizada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                                <td>
                                    <textarea id="newsletter-contenido" rows="10" class="large-text"></textarea>
                                    <p class="description">
                                        <button type="button" class="button button-small" id="btn-auto-contenido"><?php _e('Generar contenido automático (últimos 7 días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <p>
                            <button type="button" class="button button-primary" id="btn-guardar-newsletter"><?php _e('Guardar borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                            <button type="button" class="button" id="btn-cancelar-newsletter"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                        </p>
                    </div>

                    <div id="newsletter-lista">
                        <p class="description"><?php _e('Cargando newsletters...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>

                <div style="flex:1;min-width:250px;">
                    <h2><?php _e('Suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <div style="background:#f8fafc;padding:15px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:15px;">
                        <h4 style="margin:0 0 10px;"><?php _e('Añadir suscriptor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <input type="text" id="sub-nombre" class="regular-text" placeholder="<?php esc_attr_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="width:100%;margin-bottom:8px;">
                        <input type="email" id="sub-email" class="regular-text" placeholder="<?php esc_attr_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="width:100%;margin-bottom:8px;">
                        <button type="button" class="button button-primary" id="btn-add-sub" style="width:100%;"><?php _e('Añadir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    </div>
                    <div id="suscriptores-lista">
                        <p class="description"><?php _e('Cargando suscriptores...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Preguntas a la Red ───

    private function render_tab_preguntas($nodo_local) {
        global $wpdb;
        $prefix = Flavor_Network_Installer::get_table_name('');
        $tabla_questions = $prefix . 'questions';

        // Verificar si la tabla existe antes de hacer queries
        $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_questions)) === $tabla_questions;

        $total_preguntas = $tabla_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_questions}") : 0;
        $sin_responder = $tabla_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_questions} WHERE estado = 'abierta' AND respuestas_count = 0") : 0;
        $respondidas = $tabla_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_questions} WHERE estado = 'respondida'") : 0;
        ?>
        <div class="flavor-network-preguntas">
            <h2><?php _e('Preguntas a la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php _e('Inteligencia colectiva: haz preguntas a la red y responde a otros nodos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <!-- Estadisticas -->
            <div class="flavor-stats-grid" style="margin-bottom:20px;">
                <div class="flavor-stat-card">
                    <span class="stat-number"><?php echo esc_html($total_preguntas); ?></span>
                    <span class="stat-label"><?php _e('Total preguntas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="stat-number"><?php echo esc_html($sin_responder); ?></span>
                    <span class="stat-label"><?php _e('Sin responder', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="stat-number"><?php echo esc_html($respondidas); ?></span>
                    <span class="stat-label"><?php _e('Respondidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <!-- Botones y filtros -->
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:15px;align-items:center;">
                <button class="button button-primary" id="btn-nueva-pregunta">
                    <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;"></span>
                    <?php _e('Nueva pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <select id="preguntas-categoria-filtro">
                    <option value=""><?php _e('Todas las categorias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="general"><?php _e('General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="tecnica"><?php _e('Tecnica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="comercial"><?php _e('Comercial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="logistica"><?php _e('Logistica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="legal"><?php _e('Legal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="otra"><?php _e('Otra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
                <select id="preguntas-estado-filtro">
                    <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="abierta"><?php _e('Abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="respondida"><?php _e('Respondidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="cerrada"><?php _e('Cerradas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
                <input type="text" id="preguntas-busqueda" placeholder="<?php _e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="min-width:200px;">
                <button class="button" id="btn-buscar-preguntas"><?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>

            <!-- Formulario nueva/editar pregunta -->
            <div id="form-nueva-pregunta" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:20px;">
                <h3><?php _e('Nueva pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="pregunta-titulo"><?php _e('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="pregunta-titulo" class="large-text" placeholder="<?php _e('Escribe tu pregunta...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="pregunta-descripcion"><?php _e('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><textarea id="pregunta-descripcion" rows="5" class="large-text" placeholder="<?php _e('Detalla tu pregunta...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="pregunta-categoria"><?php _e('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select id="pregunta-categoria">
                                <option value="general"><?php _e('General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="tecnica"><?php _e('Tecnica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="comercial"><?php _e('Comercial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="logistica"><?php _e('Logistica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="legal"><?php _e('Legal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="otra"><?php _e('Otra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pregunta-tags"><?php _e('Tags', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td><input type="text" id="pregunta-tags" class="regular-text" placeholder="<?php _e('tag1, tag2, tag3', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></td>
                    </tr>
                </table>
                <p>
                    <button class="button button-primary" id="btn-publicar-pregunta"><?php _e('Publicar pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button class="button" id="btn-cancelar-pregunta"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </p>
            </div>

            <!-- Lista de preguntas -->
            <div id="preguntas-list"></div>

            <!-- Seccion de respuestas (se muestra al hacer click en una pregunta) -->
            <div id="pregunta-detalle" style="display:none;background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;margin-top:20px;">
                <div id="pregunta-detalle-header"></div>
                <div id="respuestas-lista" style="margin-top:15px;"></div>

                <!-- Formulario nueva respuesta -->
                <div id="form-nueva-respuesta" style="margin-top:20px;padding-top:15px;border-top:1px solid #eee;">
                    <h4><?php _e('Tu respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <textarea id="respuesta-contenido" rows="4" class="large-text" placeholder="<?php _e('Escribe tu respuesta...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                    <p style="margin-top:10px;">
                        <button class="button button-primary" id="btn-publicar-respuesta"><?php _e('Responder', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                        <button class="button" id="btn-cerrar-detalle"><?php _e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Módulos ───

    private function render_tab_modulos($nodo_local) {
        $network_manager = Flavor_Network_Manager::get_instance();
        $categorias = $network_manager->get_categorias();
        $modulos = $network_manager->get_modulos();
        $modulos_activos = $nodo_local ? $nodo_local->get_modulos_activos() : [];
        ?>
        <div class="flavor-network-modulos">
            <h2><?php _e('Módulos de la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php _e('Activa los módulos que quieres usar en tu nodo. Los módulos activos determinan qué funcionalidades están disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <form id="modulos-form">
                <?php foreach ($categorias as $clave_categoria => $nombre_categoria): ?>
                    <div class="flavor-module-category" style="margin-bottom:25px;">
                        <h3><?php echo esc_html($nombre_categoria); ?></h3>
                        <div class="flavor-modules-grid">
                            <?php
                            $modulos_categoria = $network_manager->get_modulos_por_categoria($clave_categoria);
                            foreach ($modulos_categoria as $modulo_id => $modulo_info):
                                $esta_activo = in_array($modulo_id, $modulos_activos);
                            ?>
                                <label class="flavor-module-card <?php echo $esta_activo ? 'active' : ''; ?>">
                                    <input type="checkbox" name="modulos[]" value="<?php echo esc_attr($modulo_id); ?>" <?php checked($esta_activo); ?>>
                                    <span class="dashicons <?php echo esc_attr($modulo_info['icono']); ?>"></span>
                                    <strong><?php echo esc_html($modulo_info['nombre']); ?></strong>
                                    <span class="module-desc"><?php echo esc_html($modulo_info['descripcion']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <p class="submit">
                    <button type="submit" class="button button-primary button-hero" id="btn-guardar-modulos">
                        <?php _e('Guardar módulos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <span id="modulos-save-status" style="margin-left:10px;"></span>
                </p>
            </form>
        </div>
        <?php
    }

    // ─── TAB: Matching Necesidades/Excedentes ───

    private function render_tab_matching($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-matching">
            <h2><?php _e('Matching: Necesidades ↔ Excedentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php _e('El sistema busca coincidencias entre las necesidades de tu nodo y los excedentes de otros (y viceversa).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div style="margin-bottom:15px;display:flex;gap:10px;align-items:center;">
                <button type="button" class="button button-primary" id="btn-buscar-matches"><?php _e('Buscar nuevos matches', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <select id="match-filtro-estado">
                    <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="sugerido"><?php _e('Sugeridos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="aceptado"><?php _e('Aceptados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="contactado"><?php _e('Contactados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="en_proceso"><?php _e('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="rechazado"><?php _e('Rechazados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
                <span id="match-count" style="color:#6b7280;font-size:13px;"></span>
            </div>

            <div id="matches-lista">
                <p class="description"><?php _e('Cargando matches...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <?php
    }

}
