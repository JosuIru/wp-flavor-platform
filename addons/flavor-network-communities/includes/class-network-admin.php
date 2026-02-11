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
        // NOTA: El menú se registra centralizadamente en class-admin-menu-manager.php
        // add_action('admin_menu', [$this, 'add_admin_menus'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Registra menús de administración
     */
    public function add_admin_menus() {
        // Menú principal de Red
        add_submenu_page(
            'flavor-chat-ia',
            __('Red de Comunidades', 'flavor-chat-ia'),
            __('Red', 'flavor-chat-ia'),
            'manage_options',
            'flavor-network',
            [$this, 'render_main_page']
        );
    }

    /**
     * Encola assets del admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'flavor-network') === false) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_media();

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-network-admin',
            FLAVOR_NETWORK_URL . "assets/css/network-admin{$sufijo_asset}.css",
            [],
            Flavor_Network_Manager::VERSION
        );

        // Leaflet para mapa en admin
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

        wp_enqueue_script(
            'flavor-network-admin',
            FLAVOR_NETWORK_URL . "assets/js/network-admin{$sufijo_asset}.js",
            ['jquery', 'wp-color-picker', 'leaflet'],
            Flavor_Network_Manager::VERSION,
            true
        );

        wp_localize_script('flavor-network-admin', 'flavorNetworkAdmin', [
            'apiUrl'  => rest_url(Flavor_Network_API::API_NAMESPACE),
            'nonce'   => wp_create_nonce('wp_rest'),
            'siteUrl' => get_site_url(),
            'i18n'    => [
                'guardado'        => __('Guardado correctamente', 'flavor-chat-ia'),
                'error'           => __('Error al guardar', 'flavor-chat-ia'),
                'confirmar_eliminar' => __('¿Seguro que quieres eliminar?', 'flavor-chat-ia'),
                'cargando'        => __('Cargando...', 'flavor-chat-ia'),
                'sin_resultados'  => __('No se encontraron resultados', 'flavor-chat-ia'),
                'conexion_enviada' => __('Solicitud de conexión enviada', 'flavor-chat-ia'),
                'mensaje_enviado' => __('Mensaje enviado', 'flavor-chat-ia'),
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

        $tabs = [
            'dashboard'       => __('Dashboard', 'flavor-chat-ia'),
            'mi-nodo'         => __('Mi Nodo', 'flavor-chat-ia'),
            'directorio'      => __('Directorio', 'flavor-chat-ia'),
            'mapa'            => __('Mapa', 'flavor-chat-ia'),
            'conexiones'      => __('Conexiones', 'flavor-chat-ia'),
            'contenido'       => __('Contenido', 'flavor-chat-ia'),
            'matching'        => __('Matching', 'flavor-chat-ia'),
            'eventos'         => __('Eventos', 'flavor-chat-ia'),
            'colaboraciones'  => __('Colaboraciones', 'flavor-chat-ia'),
            'banco-tiempo'    => __('Banco Tiempo', 'flavor-chat-ia'),
            'tablon'          => __('Tablón', 'flavor-chat-ia'),
            'mensajes'        => __('Mensajes', 'flavor-chat-ia'),
            'alertas'         => __('Alertas', 'flavor-chat-ia'),
            'favoritos'       => __('Favoritos', 'flavor-chat-ia'),
            'recomendaciones' => __('Recomendaciones', 'flavor-chat-ia'),
            'preguntas'       => __('Preguntas', 'flavor-chat-ia'),
            'sellos'          => __('Sellos', 'flavor-chat-ia'),
            'newsletter'      => __('Newsletter', 'flavor-chat-ia'),
            'modulos'         => __('Módulos', 'flavor-chat-ia'),
        ];
        ?>
        <div class="wrap flavor-network-admin">
            <h1>
                <span class="dashicons dashicons-networking"></span>
                <?php _e('Red de Comunidades', 'flavor-chat-ia'); ?>
            </h1>

            <?php if (!$nodo_local && $tab_activa !== 'mi-nodo'): ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php _e('Configura tu nodo', 'flavor-chat-ia'); ?></strong> -
                        <?php _e('Para participar en la red, primero configura tu nodo local.', 'flavor-chat-ia'); ?>
                        <a href="<?php echo admin_url('admin.php?page=flavor-network&tab=mi-nodo'); ?>" class="button button-primary" style="margin-left:10px;">
                            <?php _e('Configurar ahora', 'flavor-chat-ia'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <nav class="nav-tab-wrapper flavor-network-tabs">
                <?php foreach ($tabs as $tab_slug => $tab_label): ?>
                    <a href="<?php echo admin_url('admin.php?page=flavor-network&tab=' . $tab_slug); ?>"
                       class="nav-tab <?php echo $tab_activa === $tab_slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
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
                    <h2><?php _e('Resumen de la red', 'flavor-chat-ia'); ?></h2>
                    <p class="description">
                        <?php _e('Estado general de la red, métricas clave y accesos rápidos.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <div class="flavor-network-status">
                    <span class="network-status-indicator <?php echo $nodo_local ? 'is-online' : 'is-offline'; ?>"></span>
                    <span>
                        <?php echo $nodo_local ? __('Nodo local activo', 'flavor-chat-ia') : __('Nodo local sin configurar', 'flavor-chat-ia'); ?>
                    </span>
                </div>
            </div>

            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-groups"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $total_nodos; ?></span>
                        <span class="stat-label"><?php _e('Nodos en la red', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-networking"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $total_conexiones; ?></span>
                        <span class="stat-label"><?php _e('Conexiones activas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-portfolio"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $total_contenido; ?></span>
                        <span class="stat-label"><?php _e('Contenidos compartidos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $total_eventos; ?></span>
                        <span class="stat-label"><?php _e('Eventos próximos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $total_colaboraciones; ?></span>
                        <span class="stat-label"><?php _e('Colaboraciones abiertas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-stat-card <?php echo $mensajes_sin_leer > 0 ? 'has-notifications' : ''; ?>">
                    <span class="dashicons dashicons-email"></span>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo $mensajes_sin_leer; ?></span>
                        <span class="stat-label"><?php _e('Mensajes sin leer', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-network-dashboard-grid">
                <div class="flavor-network-card">
                    <h3><?php _e('Acciones rápidas', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-quick-actions">
                        <a class="button button-primary" href="<?php echo admin_url('admin.php?page=flavor-network&tab=mi-nodo'); ?>">
                            <?php _e('Configurar nodo', 'flavor-chat-ia'); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('admin.php?page=flavor-network&tab=directorio'); ?>">
                            <?php _e('Ver directorio', 'flavor-chat-ia'); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('admin.php?page=flavor-network&tab=mapa'); ?>">
                            <?php _e('Mapa', 'flavor-chat-ia'); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('admin.php?page=flavor-network&tab=conexiones'); ?>">
                            <?php _e('Conexiones', 'flavor-chat-ia'); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('admin.php?page=flavor-network&tab=contenido'); ?>">
                            <?php _e('Contenido', 'flavor-chat-ia'); ?>
                        </a>
                        <a class="button" href="<?php echo admin_url('admin.php?page=flavor-network&tab=matching'); ?>">
                            <?php _e('Matching', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>

                <div class="flavor-network-card">
                    <h3><?php _e('Indicadores clave', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-summary-grid">
                        <div>
                            <span class="summary-label"><?php _e('Conexiones pendientes', 'flavor-chat-ia'); ?></span>
                            <span class="summary-value"><?php echo $total_conexiones_pendientes; ?></span>
                        </div>
                        <div>
                            <span class="summary-label"><?php _e('Nodos inactivos', 'flavor-chat-ia'); ?></span>
                            <span class="summary-value"><?php echo $total_nodos_inactivos; ?></span>
                        </div>
                        <div>
                            <span class="summary-label"><?php _e('Última sync global', 'flavor-chat-ia'); ?></span>
                            <span class="summary-value">
                                <?php echo $ultima_sync ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ultima_sync))) : __('Nunca', 'flavor-chat-ia'); ?>
                            </span>
                        </div>
                        <div>
                            <span class="summary-label"><?php _e('Última sync local', 'flavor-chat-ia'); ?></span>
                            <span class="summary-value">
                                <?php echo $ultima_sync_local ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ultima_sync_local))) : __('Nunca', 'flavor-chat-ia'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($nodo_local): ?>
                <div class="flavor-network-info-card" style="margin-top:20px;">
                    <h3><?php _e('Tu nodo:', 'flavor-chat-ia'); ?> <?php echo esc_html($nodo_local->nombre); ?></h3>
                    <p><strong><?php _e('Tipo:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html(Flavor_Network_Node::TIPOS_ENTIDAD[$nodo_local->tipo_entidad] ?? $nodo_local->tipo_entidad); ?></p>
                    <p><strong><?php _e('Nivel:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html(Flavor_Network_Node::NIVELES_CONSCIENCIA[$nodo_local->nivel_consciencia] ?? $nodo_local->nivel_consciencia); ?></p>
                    <p><strong><?php _e('API Endpoint:', 'flavor-chat-ia'); ?></strong> <code><?php echo esc_url(rest_url(Flavor_Network_API::API_NAMESPACE)); ?></code></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ─── TAB: Mi Nodo ───

    private function render_tab_mi_nodo($nodo_local) {
        ?>
        <div class="flavor-network-mi-nodo">
            <h2><?php _e('Configuración de tu nodo', 'flavor-chat-ia'); ?></h2>
            <p class="description"><?php _e('Configura los datos de tu comunidad/entidad para aparecer en la red.', 'flavor-chat-ia'); ?></p>

            <form id="flavor-nodo-form" class="flavor-network-form">
                <table class="form-table">
                    <tr>
                        <th><label for="nodo-nombre"><?php _e('Nombre', 'flavor-chat-ia'); ?> *</label></th>
                        <td><input type="text" id="nodo-nombre" name="nombre" value="<?php echo esc_attr($nodo_local->nombre ?? get_bloginfo('name')); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-slug"><?php _e('Slug (URL)', 'flavor-chat-ia'); ?> *</label></th>
                        <td><input type="text" id="nodo-slug" name="slug" value="<?php echo esc_attr($nodo_local->slug ?? sanitize_title(get_bloginfo('name'))); ?>" class="regular-text" required pattern="[a-z0-9-]+"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-descripcion-corta"><?php _e('Descripción corta', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="nodo-descripcion-corta" name="descripcion_corta" value="<?php echo esc_attr($nodo_local->descripcion_corta ?? ''); ?>" class="large-text" maxlength="500"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?></label></th>
                        <td><textarea id="nodo-descripcion" name="descripcion" rows="4" class="large-text"><?php echo esc_textarea($nodo_local->descripcion ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-tipo"><?php _e('Tipo de entidad', 'flavor-chat-ia'); ?></label></th>
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
                        <th><label for="nodo-sector"><?php _e('Sector', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="nodo-sector" name="sector" value="<?php echo esc_attr($nodo_local->sector ?? ''); ?>" class="regular-text" placeholder="<?php esc_attr_e('ej: alimentación, educación, tecnología...', 'flavor-chat-ia'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-nivel"><?php _e('Nivel de consciencia', 'flavor-chat-ia'); ?></label></th>
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
                        <th><label for="nodo-logo"><?php _e('Logo URL', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="url" id="nodo-logo" name="logo_url" value="<?php echo esc_url($nodo_local->logo_url ?? ''); ?>" class="regular-text">
                            <button type="button" class="button flavor-upload-media" data-target="#nodo-logo"><?php _e('Subir', 'flavor-chat-ia'); ?></button>
                            <?php if (!empty($nodo_local->logo_url)): ?>
                                <br><img src="<?php echo esc_url($nodo_local->logo_url); ?>" style="max-height:60px;margin-top:5px;">
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Ubicación', 'flavor-chat-ia'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="nodo-direccion"><?php _e('Dirección', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="nodo-direccion" name="direccion" value="<?php echo esc_attr($nodo_local->direccion ?? ''); ?>" class="large-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-ciudad"><?php _e('Ciudad', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="nodo-ciudad" name="ciudad" value="<?php echo esc_attr($nodo_local->ciudad ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-provincia"><?php _e('Provincia', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="nodo-provincia" name="provincia" value="<?php echo esc_attr($nodo_local->provincia ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-pais"><?php _e('País', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="nodo-pais" name="pais" value="<?php echo esc_attr($nodo_local->pais ?? 'ES'); ?>" class="small-text" maxlength="5"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Coordenadas', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="number" id="nodo-latitud" name="latitud" value="<?php echo esc_attr($nodo_local->latitud ?? ''); ?>" step="0.00000001" placeholder="Latitud" style="width:150px;">
                            <input type="number" id="nodo-longitud" name="longitud" value="<?php echo esc_attr($nodo_local->longitud ?? ''); ?>" step="0.00000001" placeholder="Longitud" style="width:150px;">
                            <button type="button" class="button" id="btn-geolocate"><?php _e('Obtener ubicación', 'flavor-chat-ia'); ?></button>
                            <div id="nodo-map-preview" style="height:250px;margin-top:10px;border:1px solid #ddd;"></div>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Contacto', 'flavor-chat-ia'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="nodo-email"><?php _e('Email', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="email" id="nodo-email" name="email" value="<?php echo esc_attr($nodo_local->email ?? get_option('admin_email')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-telefono"><?php _e('Teléfono', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="tel" id="nodo-telefono" name="telefono" value="<?php echo esc_attr($nodo_local->telefono ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-web"><?php _e('Web', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="url" id="nodo-web" name="web" value="<?php echo esc_url($nodo_local->web ?? get_site_url()); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="nodo-tags"><?php _e('Tags', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="text" id="nodo-tags" name="tags" value="<?php echo esc_attr(implode(', ', $nodo_local ? $nodo_local->get_tags() : [])); ?>" class="large-text" placeholder="<?php esc_attr_e('tag1, tag2, tag3...', 'flavor-chat-ia'); ?>">
                            <p class="description"><?php _e('Separados por comas', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary button-hero" id="btn-guardar-nodo">
                        <?php _e('Guardar configuración del nodo', 'flavor-chat-ia'); ?>
                    </button>
                    <span id="nodo-save-status" style="margin-left:10px;"></span>
                </p>
            </form>
            <?php if ($nodo_local): ?>
            <div style="margin-top:30px;padding:20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                <h3><?php _e('Código QR', 'flavor-chat-ia'); ?></h3>
                <p class="description"><?php _e('Genera un código QR para compartir tu perfil de nodo.', 'flavor-chat-ia'); ?></p>
                <div style="display:flex;gap:20px;align-items:flex-start;margin-top:15px;">
                    <div id="qr-preview" style="text-align:center;">
                        <img id="qr-img" src="" alt="QR" style="width:200px;height:200px;border:1px solid #e5e7eb;border-radius:8px;display:none;">
                        <p id="qr-loading" class="description"><?php _e('Haz clic en "Generar QR" para ver el código', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div>
                        <button type="button" class="button button-primary" id="btn-generar-qr" data-slug="<?php echo esc_attr($nodo_local->slug); ?>"><?php _e('Generar QR del perfil', 'flavor-chat-ia'); ?></button><br><br>
                        <button type="button" class="button" id="btn-generar-qr-vcard" data-slug="<?php echo esc_attr($nodo_local->slug); ?>"><?php _e('Generar QR vCard', 'flavor-chat-ia'); ?></button><br><br>
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
            <h2><?php _e('Directorio de la Red', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-directory-filters" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;">
                <input type="text" id="dir-busqueda" placeholder="<?php esc_attr_e('Buscar...', 'flavor-chat-ia'); ?>" class="regular-text">
                <select id="dir-tipo">
                    <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <?php foreach (Flavor_Network_Node::TIPOS_ENTIDAD as $clave_tipo => $etiqueta_tipo): ?>
                        <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="dir-nivel">
                    <option value=""><?php _e('Todos los niveles', 'flavor-chat-ia'); ?></option>
                    <?php foreach (Flavor_Network_Node::NIVELES_CONSCIENCIA as $clave_nivel => $etiqueta_nivel): ?>
                        <option value="<?php echo esc_attr($clave_nivel); ?>"><?php echo esc_html($etiqueta_nivel); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-primary" id="btn-buscar-dir"><?php _e('Buscar', 'flavor-chat-ia'); ?></button>
                <button type="button" class="button" id="btn-add-node"><?php _e('Añadir nodo', 'flavor-chat-ia'); ?></button>
            </div>

            <div id="directorio-resultados" class="flavor-directory-grid">
                <p class="description"><?php _e('Cargando directorio...', 'flavor-chat-ia'); ?></p>
            </div>

            <div id="directorio-paginacion" style="margin-top:15px;"></div>
        </div>
        <?php
    }

    // ─── TAB: Mapa ───

    private function render_tab_mapa() {
        ?>
        <div class="flavor-network-mapa">
            <h2><?php _e('Mapa de la Red', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-map-filters" style="margin-bottom:15px;display:flex;gap:10px;flex-wrap:wrap;">
                <select id="map-tipo">
                    <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <?php foreach (Flavor_Network_Node::TIPOS_ENTIDAD as $clave_tipo => $etiqueta_tipo): ?>
                        <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="map-nivel">
                    <option value=""><?php _e('Todos los niveles', 'flavor-chat-ia'); ?></option>
                    <?php foreach (Flavor_Network_Node::NIVELES_CONSCIENCIA as $clave_nivel => $etiqueta_nivel): ?>
                        <option value="<?php echo esc_attr($clave_nivel); ?>"><?php echo esc_html($etiqueta_nivel); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button" id="btn-filtrar-mapa"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>
                <button type="button" class="button" id="btn-mi-ubicacion"><?php _e('Mi ubicación', 'flavor-chat-ia'); ?></button>
            </div>

            <div id="network-admin-map" style="height:500px;border:1px solid #ddd;border-radius:4px;"></div>
            <p class="description" style="margin-top:5px;"><?php _e('Haz clic en un nodo para ver su información.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php
    }

    // ─── TAB: Conexiones ───

    private function render_tab_conexiones($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-conexiones">
            <h2><?php _e('Mis Conexiones', 'flavor-chat-ia'); ?></h2>
            <div id="conexiones-lista" class="flavor-connections-list">
                <p class="description"><?php _e('Cargando conexiones...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Contenido ───

    private function render_tab_contenido($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $tipos_contenido = [
            'producto'   => __('Producto', 'flavor-chat-ia'),
            'servicio'   => __('Servicio', 'flavor-chat-ia'),
            'espacio'    => __('Espacio', 'flavor-chat-ia'),
            'recurso'    => __('Recurso', 'flavor-chat-ia'),
            'excedente'  => __('Excedente', 'flavor-chat-ia'),
            'necesidad'  => __('Necesidad', 'flavor-chat-ia'),
            'saber'      => __('Saber/Formación', 'flavor-chat-ia'),
        ];
        ?>
        <div class="flavor-network-contenido">
            <h2><?php _e('Contenido Compartido', 'flavor-chat-ia'); ?></h2>

            <div style="margin-bottom:20px;display:flex;gap:10px;">
                <select id="contenido-tipo-filtro">
                    <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($tipos_contenido as $clave_tipo => $etiqueta_tipo): ?>
                        <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="contenido-busqueda" placeholder="<?php esc_attr_e('Buscar...', 'flavor-chat-ia'); ?>" class="regular-text">
                <button type="button" class="button" id="btn-buscar-contenido"><?php _e('Buscar', 'flavor-chat-ia'); ?></button>
                <button type="button" class="button button-primary" id="btn-nuevo-contenido"><?php _e('Nuevo contenido', 'flavor-chat-ia'); ?></button>
            </div>

            <!-- Formulario nuevo contenido (oculto) -->
            <div id="form-nuevo-contenido" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Publicar contenido', 'flavor-chat-ia'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Tipo', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="nuevo-contenido-tipo">
                                <?php foreach ($tipos_contenido as $clave_tipo => $etiqueta_tipo): ?>
                                    <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Título', 'flavor-chat-ia'); ?> *</label></th>
                        <td><input type="text" id="nuevo-contenido-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Descripción', 'flavor-chat-ia'); ?></label></th>
                        <td><textarea id="nuevo-contenido-desc" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Precio', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="number" id="nuevo-contenido-precio" step="0.01" style="width:100px;"> EUR</td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Ubicación', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="nuevo-contenido-ubicacion" class="regular-text"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-publicar-contenido"><?php _e('Publicar', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="btn-cancelar-contenido"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                </p>
            </div>

            <div id="contenido-lista" class="flavor-content-grid">
                <p class="description"><?php _e('Cargando contenido...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Eventos ───

    private function render_tab_eventos($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-eventos">
            <h2><?php _e('Eventos de la Red', 'flavor-chat-ia'); ?></h2>
            <div style="margin-bottom:15px;">
                <button type="button" class="button button-primary" id="btn-nuevo-evento"><?php _e('Crear evento', 'flavor-chat-ia'); ?></button>
            </div>

            <div id="form-nuevo-evento" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Nuevo evento', 'flavor-chat-ia'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Título', 'flavor-chat-ia'); ?> *</label></th>
                        <td><input type="text" id="evento-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Descripción', 'flavor-chat-ia'); ?></label></th>
                        <td><textarea id="evento-desc" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Fecha inicio', 'flavor-chat-ia'); ?> *</label></th>
                        <td><input type="datetime-local" id="evento-inicio" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Fecha fin', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="datetime-local" id="evento-fin"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Tipo', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="evento-tipo">
                                <option value="presencial"><?php _e('Presencial', 'flavor-chat-ia'); ?></option>
                                <option value="online"><?php _e('Online', 'flavor-chat-ia'); ?></option>
                                <option value="hibrido"><?php _e('Híbrido', 'flavor-chat-ia'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Ubicación', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="evento-ubicacion" class="regular-text"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-crear-evento"><?php _e('Crear', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="btn-cancelar-evento"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                </p>
            </div>

            <div id="eventos-lista">
                <p class="description"><?php _e('Cargando eventos...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Colaboraciones ───

    private function render_tab_colaboraciones($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $tipos_colaboracion = [
            'compra_colectiva' => __('Compra colectiva', 'flavor-chat-ia'),
            'logistica'        => __('Logística compartida', 'flavor-chat-ia'),
            'proyecto'         => __('Proyecto conjunto', 'flavor-chat-ia'),
            'alianza'          => __('Alianza temática', 'flavor-chat-ia'),
            'hermanamiento'    => __('Hermanamiento', 'flavor-chat-ia'),
            'mentoria'         => __('Mentoría cruzada', 'flavor-chat-ia'),
        ];
        ?>
        <div class="flavor-network-colaboraciones">
            <h2><?php _e('Colaboraciones', 'flavor-chat-ia'); ?></h2>
            <div style="margin-bottom:15px;display:flex;gap:10px;">
                <select id="colab-tipo-filtro">
                    <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($tipos_colaboracion as $clave_tipo => $etiqueta_tipo): ?>
                        <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-primary" id="btn-nueva-colab"><?php _e('Nueva colaboración', 'flavor-chat-ia'); ?></button>
            </div>

            <div id="form-nueva-colab" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Crear colaboración', 'flavor-chat-ia'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Tipo', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="colab-tipo">
                                <?php foreach ($tipos_colaboracion as $clave_tipo => $etiqueta_tipo): ?>
                                    <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($etiqueta_tipo); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Título', 'flavor-chat-ia'); ?> *</label></th>
                        <td><input type="text" id="colab-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Descripción', 'flavor-chat-ia'); ?></label></th>
                        <td><textarea id="colab-desc" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Objetivo', 'flavor-chat-ia'); ?></label></th>
                        <td><textarea id="colab-objetivo" rows="2" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Fecha límite', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="datetime-local" id="colab-fecha-limite"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-crear-colab"><?php _e('Crear', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="btn-cancelar-colab"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                </p>
            </div>

            <div id="colaboraciones-lista">
                <p class="description"><?php _e('Cargando colaboraciones...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Tablón ───

    private function render_tab_tablon($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-tablon">
            <h2><?php _e('Tablón de la Red', 'flavor-chat-ia'); ?></h2>
            <div style="margin-bottom:15px;">
                <button type="button" class="button button-primary" id="btn-nueva-publicacion"><?php _e('Nueva publicación', 'flavor-chat-ia'); ?></button>
            </div>

            <div id="form-nueva-pub" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Publicar en el tablón', 'flavor-chat-ia'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Título', 'flavor-chat-ia'); ?> *</label></th>
                        <td><input type="text" id="pub-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Contenido', 'flavor-chat-ia'); ?> *</label></th>
                        <td><textarea id="pub-contenido" rows="4" class="large-text" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Tipo', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="pub-tipo">
                                <option value="anuncio"><?php _e('Anuncio', 'flavor-chat-ia'); ?></option>
                                <option value="noticia"><?php _e('Noticia', 'flavor-chat-ia'); ?></option>
                                <option value="pregunta"><?php _e('Pregunta', 'flavor-chat-ia'); ?></option>
                                <option value="oferta"><?php _e('Oferta', 'flavor-chat-ia'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-enviar-pub"><?php _e('Publicar', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="btn-cancelar-pub"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                </p>
            </div>

            <div id="tablon-lista">
                <p class="description"><?php _e('Cargando tablón...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Mensajes ───

    private function render_tab_mensajes($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-mensajes">
            <h2><?php _e('Mensajería Inter-nodos', 'flavor-chat-ia'); ?></h2>
            <div style="margin-bottom:15px;display:flex;gap:10px;">
                <button type="button" class="button btn-msg-tab active" data-tipo="recibidos"><?php _e('Recibidos', 'flavor-chat-ia'); ?></button>
                <button type="button" class="button btn-msg-tab" data-tipo="enviados"><?php _e('Enviados', 'flavor-chat-ia'); ?></button>
                <button type="button" class="button button-primary" id="btn-nuevo-mensaje"><?php _e('Nuevo mensaje', 'flavor-chat-ia'); ?></button>
            </div>

            <div id="form-nuevo-msg" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Enviar mensaje', 'flavor-chat-ia'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Destinatario', 'flavor-chat-ia'); ?></label></th>
                        <td><select id="msg-destinatario" class="regular-text" required>
                            <option value=""><?php _e('Seleccionar nodo...', 'flavor-chat-ia'); ?></option>
                        </select>
                        <p class="description"><?php _e('Se cargará la lista de nodos disponibles', 'flavor-chat-ia'); ?></p></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Asunto', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="msg-asunto" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Mensaje', 'flavor-chat-ia'); ?> *</label></th>
                        <td><textarea id="msg-contenido" rows="4" class="large-text" required></textarea></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-enviar-msg"><?php _e('Enviar', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="btn-cancelar-msg"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                </p>
            </div>

            <div id="mensajes-lista">
                <p class="description"><?php _e('Cargando mensajes...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Alertas solidarias ───

    private function render_tab_alertas($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-alertas">
            <h2><?php _e('Alertas Solidarias', 'flavor-chat-ia'); ?></h2>
            <div style="margin-bottom:15px;">
                <button type="button" class="button button-primary" id="btn-nueva-alerta"><?php _e('Crear alerta solidaria', 'flavor-chat-ia'); ?></button>
            </div>

            <div id="form-nueva-alerta" style="display:none;background:#fff3cd;padding:20px;border:1px solid #ffc107;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Nueva alerta solidaria', 'flavor-chat-ia'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Título', 'flavor-chat-ia'); ?> *</label></th>
                        <td><input type="text" id="alerta-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Descripción', 'flavor-chat-ia'); ?> *</label></th>
                        <td><textarea id="alerta-desc" rows="3" class="large-text" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Urgencia', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="alerta-urgencia">
                                <option value="baja"><?php _e('Baja', 'flavor-chat-ia'); ?></option>
                                <option value="media" selected><?php _e('Media', 'flavor-chat-ia'); ?></option>
                                <option value="alta"><?php _e('Alta', 'flavor-chat-ia'); ?></option>
                                <option value="critica"><?php _e('Crítica', 'flavor-chat-ia'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Ubicación', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="alerta-ubicacion" class="regular-text"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-crear-alerta"><?php _e('Publicar alerta', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="btn-cancelar-alerta"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                </p>
            </div>

            <div id="alertas-lista">
                <p class="description"><?php _e('Cargando alertas...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Banco de Tiempo ───

    private function render_tab_banco_tiempo($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-banco-tiempo">
            <h2><?php _e('Banco de Tiempo', 'flavor-chat-ia'); ?></h2>
            <p class="description"><?php _e('Intercambia horas de servicio con otros nodos de la red.', 'flavor-chat-ia'); ?></p>

            <div style="margin-bottom:15px;display:flex;gap:10px;">
                <select id="tiempo-tipo-filtro">
                    <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                    <option value="oferta"><?php _e('Ofertas', 'flavor-chat-ia'); ?></option>
                    <option value="demanda"><?php _e('Demandas', 'flavor-chat-ia'); ?></option>
                </select>
                <button type="button" class="button button-primary" id="btn-nueva-oferta-tiempo"><?php _e('Nueva oferta de tiempo', 'flavor-chat-ia'); ?></button>
            </div>

            <div id="form-nueva-oferta-tiempo" style="display:none;background:#f0f9ff;padding:20px;border:1px solid #bae6fd;margin-bottom:20px;border-radius:4px;">
                <h3 id="form-tiempo-titulo"><?php _e('Crear oferta de tiempo', 'flavor-chat-ia'); ?></h3>
                <input type="hidden" id="tiempo-edit-id" value="">
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Tipo', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="tiempo-tipo">
                                <option value="oferta"><?php _e('Ofrezco tiempo', 'flavor-chat-ia'); ?></option>
                                <option value="demanda"><?php _e('Necesito tiempo', 'flavor-chat-ia'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Título', 'flavor-chat-ia'); ?> *</label></th>
                        <td><input type="text" id="tiempo-titulo" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Descripción', 'flavor-chat-ia'); ?></label></th>
                        <td><textarea id="tiempo-desc" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Categoría', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="tiempo-categoria">
                                <option value="formacion"><?php _e('Formación', 'flavor-chat-ia'); ?></option>
                                <option value="tecnologia"><?php _e('Tecnología', 'flavor-chat-ia'); ?></option>
                                <option value="comunicacion"><?php _e('Comunicación', 'flavor-chat-ia'); ?></option>
                                <option value="diseno"><?php _e('Diseño', 'flavor-chat-ia'); ?></option>
                                <option value="administracion"><?php _e('Administración', 'flavor-chat-ia'); ?></option>
                                <option value="logistica"><?php _e('Logística', 'flavor-chat-ia'); ?></option>
                                <option value="cocina"><?php _e('Cocina', 'flavor-chat-ia'); ?></option>
                                <option value="limpieza"><?php _e('Limpieza', 'flavor-chat-ia'); ?></option>
                                <option value="mantenimiento"><?php _e('Mantenimiento', 'flavor-chat-ia'); ?></option>
                                <option value="cuidados"><?php _e('Cuidados', 'flavor-chat-ia'); ?></option>
                                <option value="otro"><?php _e('Otro', 'flavor-chat-ia'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Horas estimadas', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="number" id="tiempo-horas" step="0.5" min="0.5" style="width:80px;"> h</td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Modalidad', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="tiempo-modalidad">
                                <option value="presencial"><?php _e('Presencial', 'flavor-chat-ia'); ?></option>
                                <option value="online"><?php _e('Online', 'flavor-chat-ia'); ?></option>
                                <option value="ambas"><?php _e('Ambas', 'flavor-chat-ia'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-guardar-tiempo"><?php _e('Guardar', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="btn-cancelar-tiempo"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                </p>
            </div>

            <div id="tiempo-lista">
                <p class="description"><?php _e('Cargando ofertas de tiempo...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Favoritos ───

    private function render_tab_favoritos($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-favoritos">
            <h2><?php _e('Mis Favoritos', 'flavor-chat-ia'); ?></h2>
            <p class="description"><?php _e('Nodos que has marcado como favoritos para acceder rápidamente.', 'flavor-chat-ia'); ?></p>

            <div id="favoritos-lista">
                <p class="description"><?php _e('Cargando favoritos...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Recomendaciones ───

    private function render_tab_recomendaciones($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-recomendaciones">
            <h2><?php _e('Recomendaciones', 'flavor-chat-ia'); ?></h2>
            <p class="description"><?php _e('Recomienda nodos a otros o mira las recomendaciones que te han hecho.', 'flavor-chat-ia'); ?></p>

            <div style="margin-bottom:15px;">
                <button type="button" class="button button-primary" id="btn-nueva-recomendacion"><?php _e('Nueva recomendación', 'flavor-chat-ia'); ?></button>
            </div>

            <div id="form-nueva-recomendacion" style="display:none;background:#f0fdf4;padding:20px;border:1px solid #bbf7d0;margin-bottom:20px;border-radius:4px;">
                <h3><?php _e('Recomendar un nodo', 'flavor-chat-ia'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Nodo a recomendar', 'flavor-chat-ia'); ?> *</label></th>
                        <td><select id="rec-nodo-recomendado" class="regular-text" required>
                            <option value=""><?php _e('Seleccionar nodo...', 'flavor-chat-ia'); ?></option>
                        </select></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Recomendar a', 'flavor-chat-ia'); ?> *</label></th>
                        <td><select id="rec-destinatario" class="regular-text" required>
                            <option value=""><?php _e('Seleccionar destinatario...', 'flavor-chat-ia'); ?></option>
                        </select></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Motivo', 'flavor-chat-ia'); ?></label></th>
                        <td><textarea id="rec-motivo" rows="3" class="large-text" placeholder="<?php esc_attr_e('¿Por qué recomiendas este nodo?', 'flavor-chat-ia'); ?>"></textarea></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-enviar-recomendacion"><?php _e('Enviar recomendación', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="btn-cancelar-recomendacion"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                </p>
            </div>

            <div id="recomendaciones-lista">
                <p class="description"><?php _e('Cargando recomendaciones...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }


    // ─── TAB: Sellos de Calidad ───

    private function render_tab_sellos($nodo_local) {
        ?>
        <div class="flavor-network-sellos">
            <h2><?php _e('Sellos de Calidad', 'flavor-chat-ia'); ?></h2>
            <p class="description"><?php _e('Gestiona los sellos "App Consciente" otorgados a los nodos de la red.', 'flavor-chat-ia'); ?></p>

            <div style="margin-bottom:15px;">
                <button type="button" class="button button-primary" id="btn-nuevo-sello"><?php _e('Otorgar sello', 'flavor-chat-ia'); ?></button>
            </div>

            <div id="form-nuevo-sello" style="display:none;background:#fffbeb;padding:20px;border:1px solid #fde68a;margin-bottom:20px;border-radius:4px;">
                <h3 id="form-sello-titulo"><?php _e('Otorgar sello de calidad', 'flavor-chat-ia'); ?></h3>
                <input type="hidden" id="sello-edit-id" value="">
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Nodo', 'flavor-chat-ia'); ?> *</label></th>
                        <td><select id="sello-nodo" class="regular-text" required>
                            <option value=""><?php _e('Seleccionar nodo...', 'flavor-chat-ia'); ?></option>
                        </select></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Nivel', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="sello-nivel">
                                <option value="basico"><?php _e('Básico', 'flavor-chat-ia'); ?></option>
                                <option value="transicion"><?php _e('Transición', 'flavor-chat-ia'); ?></option>
                                <option value="consciente"><?php _e('Consciente', 'flavor-chat-ia'); ?></option>
                                <option value="referente"><?php _e('Referente', 'flavor-chat-ia'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Puntuación (0-100)', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="number" id="sello-puntuacion" min="0" max="100" value="0" style="width:80px;"></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Criterios cumplidos', 'flavor-chat-ia'); ?></label></th>
                        <td><textarea id="sello-criterios" rows="4" class="large-text" placeholder="<?php esc_attr_e('Lista de criterios que cumple este nodo...', 'flavor-chat-ia'); ?>"></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('Fecha expiración', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="date" id="sello-expiracion"></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="btn-guardar-sello"><?php _e('Guardar', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="btn-cancelar-sello"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                </p>
            </div>

            <div id="sellos-lista">
                <p class="description"><?php _e('Cargando sellos...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

    // ─── TAB: Newsletter ───

    private function render_tab_newsletter($nodo_local) {
        if (!$nodo_local) {
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-newsletter">
            <div style="display:flex;gap:20px;flex-wrap:wrap;">
                <div style="flex:2;min-width:400px;">
                    <h2><?php _e('Newsletters', 'flavor-chat-ia'); ?></h2>
                    <div style="margin-bottom:15px;display:flex;gap:10px;">
                        <button type="button" class="button button-primary" id="btn-nueva-newsletter"><?php _e('Nueva newsletter', 'flavor-chat-ia'); ?></button>
                    </div>

                    <div id="form-nueva-newsletter" style="display:none;background:#f0f9ff;padding:20px;border:1px solid #bae6fd;margin-bottom:20px;border-radius:4px;">
                        <h3 id="form-newsletter-titulo"><?php _e('Crear newsletter', 'flavor-chat-ia'); ?></h3>
                        <input type="hidden" id="newsletter-edit-id" value="">
                        <table class="form-table">
                            <tr>
                                <th><label><?php _e('Asunto', 'flavor-chat-ia'); ?> *</label></th>
                                <td><input type="text" id="newsletter-asunto" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Tipo', 'flavor-chat-ia'); ?></label></th>
                                <td>
                                    <select id="newsletter-tipo">
                                        <option value="resumen"><?php _e('Resumen semanal', 'flavor-chat-ia'); ?></option>
                                        <option value="noticia"><?php _e('Noticia', 'flavor-chat-ia'); ?></option>
                                        <option value="alerta"><?php _e('Alerta', 'flavor-chat-ia'); ?></option>
                                        <option value="custom"><?php _e('Personalizada', 'flavor-chat-ia'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Contenido', 'flavor-chat-ia'); ?></label></th>
                                <td>
                                    <textarea id="newsletter-contenido" rows="10" class="large-text"></textarea>
                                    <p class="description">
                                        <button type="button" class="button button-small" id="btn-auto-contenido"><?php _e('Generar contenido automático (últimos 7 días)', 'flavor-chat-ia'); ?></button>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <p>
                            <button type="button" class="button button-primary" id="btn-guardar-newsletter"><?php _e('Guardar borrador', 'flavor-chat-ia'); ?></button>
                            <button type="button" class="button" id="btn-cancelar-newsletter"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                        </p>
                    </div>

                    <div id="newsletter-lista">
                        <p class="description"><?php _e('Cargando newsletters...', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>

                <div style="flex:1;min-width:250px;">
                    <h2><?php _e('Suscriptores', 'flavor-chat-ia'); ?></h2>
                    <div style="background:#f8fafc;padding:15px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:15px;">
                        <h4 style="margin:0 0 10px;"><?php _e('Añadir suscriptor', 'flavor-chat-ia'); ?></h4>
                        <input type="text" id="sub-nombre" class="regular-text" placeholder="<?php esc_attr_e('Nombre', 'flavor-chat-ia'); ?>" style="width:100%;margin-bottom:8px;">
                        <input type="email" id="sub-email" class="regular-text" placeholder="<?php esc_attr_e('Email', 'flavor-chat-ia'); ?>" style="width:100%;margin-bottom:8px;">
                        <button type="button" class="button button-primary" id="btn-add-sub" style="width:100%;"><?php _e('Añadir', 'flavor-chat-ia'); ?></button>
                    </div>
                    <div id="suscriptores-lista">
                        <p class="description"><?php _e('Cargando suscriptores...', 'flavor-chat-ia'); ?></p>
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
            <h2><?php _e('Preguntas a la Red', 'flavor-chat-ia'); ?></h2>
            <p class="description"><?php _e('Inteligencia colectiva: haz preguntas a la red y responde a otros nodos.', 'flavor-chat-ia'); ?></p>

            <!-- Estadisticas -->
            <div class="flavor-stats-grid" style="margin-bottom:20px;">
                <div class="flavor-stat-card">
                    <span class="stat-number"><?php echo esc_html($total_preguntas); ?></span>
                    <span class="stat-label"><?php _e('Total preguntas', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="stat-number"><?php echo esc_html($sin_responder); ?></span>
                    <span class="stat-label"><?php _e('Sin responder', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flavor-stat-card">
                    <span class="stat-number"><?php echo esc_html($respondidas); ?></span>
                    <span class="stat-label"><?php _e('Respondidas', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <!-- Botones y filtros -->
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:15px;align-items:center;">
                <button class="button button-primary" id="btn-nueva-pregunta">
                    <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;"></span>
                    <?php _e('Nueva pregunta', 'flavor-chat-ia'); ?>
                </button>
                <select id="preguntas-categoria-filtro">
                    <option value=""><?php _e('Todas las categorias', 'flavor-chat-ia'); ?></option>
                    <option value="general"><?php _e('General', 'flavor-chat-ia'); ?></option>
                    <option value="tecnica"><?php _e('Tecnica', 'flavor-chat-ia'); ?></option>
                    <option value="comercial"><?php _e('Comercial', 'flavor-chat-ia'); ?></option>
                    <option value="logistica"><?php _e('Logistica', 'flavor-chat-ia'); ?></option>
                    <option value="legal"><?php _e('Legal', 'flavor-chat-ia'); ?></option>
                    <option value="otra"><?php _e('Otra', 'flavor-chat-ia'); ?></option>
                </select>
                <select id="preguntas-estado-filtro">
                    <option value=""><?php _e('Todos los estados', 'flavor-chat-ia'); ?></option>
                    <option value="abierta"><?php _e('Abiertas', 'flavor-chat-ia'); ?></option>
                    <option value="respondida"><?php _e('Respondidas', 'flavor-chat-ia'); ?></option>
                    <option value="cerrada"><?php _e('Cerradas', 'flavor-chat-ia'); ?></option>
                </select>
                <input type="text" id="preguntas-busqueda" placeholder="<?php _e('Buscar...', 'flavor-chat-ia'); ?>" style="min-width:200px;">
                <button class="button" id="btn-buscar-preguntas"><?php _e('Buscar', 'flavor-chat-ia'); ?></button>
            </div>

            <!-- Formulario nueva/editar pregunta -->
            <div id="form-nueva-pregunta" style="display:none;background:#f9f9f9;padding:20px;border:1px solid #ddd;border-radius:8px;margin-bottom:20px;">
                <h3><?php _e('Nueva pregunta', 'flavor-chat-ia'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="pregunta-titulo"><?php _e('Titulo', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="pregunta-titulo" class="large-text" placeholder="<?php _e('Escribe tu pregunta...', 'flavor-chat-ia'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="pregunta-descripcion"><?php _e('Descripcion', 'flavor-chat-ia'); ?></label></th>
                        <td><textarea id="pregunta-descripcion" rows="5" class="large-text" placeholder="<?php _e('Detalla tu pregunta...', 'flavor-chat-ia'); ?>"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="pregunta-categoria"><?php _e('Categoria', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select id="pregunta-categoria">
                                <option value="general"><?php _e('General', 'flavor-chat-ia'); ?></option>
                                <option value="tecnica"><?php _e('Tecnica', 'flavor-chat-ia'); ?></option>
                                <option value="comercial"><?php _e('Comercial', 'flavor-chat-ia'); ?></option>
                                <option value="logistica"><?php _e('Logistica', 'flavor-chat-ia'); ?></option>
                                <option value="legal"><?php _e('Legal', 'flavor-chat-ia'); ?></option>
                                <option value="otra"><?php _e('Otra', 'flavor-chat-ia'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pregunta-tags"><?php _e('Tags', 'flavor-chat-ia'); ?></label></th>
                        <td><input type="text" id="pregunta-tags" class="regular-text" placeholder="<?php _e('tag1, tag2, tag3', 'flavor-chat-ia'); ?>"></td>
                    </tr>
                </table>
                <p>
                    <button class="button button-primary" id="btn-publicar-pregunta"><?php _e('Publicar pregunta', 'flavor-chat-ia'); ?></button>
                    <button class="button" id="btn-cancelar-pregunta"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
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
                    <h4><?php _e('Tu respuesta', 'flavor-chat-ia'); ?></h4>
                    <textarea id="respuesta-contenido" rows="4" class="large-text" placeholder="<?php _e('Escribe tu respuesta...', 'flavor-chat-ia'); ?>"></textarea>
                    <p style="margin-top:10px;">
                        <button class="button button-primary" id="btn-publicar-respuesta"><?php _e('Responder', 'flavor-chat-ia'); ?></button>
                        <button class="button" id="btn-cerrar-detalle"><?php _e('Cerrar', 'flavor-chat-ia'); ?></button>
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
            <h2><?php _e('Módulos de la Red', 'flavor-chat-ia'); ?></h2>
            <p class="description"><?php _e('Activa los módulos que quieres usar en tu nodo. Los módulos activos determinan qué funcionalidades están disponibles.', 'flavor-chat-ia'); ?></p>
            <div class="notice notice-info" style="margin: 15px 0;">
                <?php if ($nodo_local): ?>
                    <p>
                        <strong><?php _e('Nodo local:', 'flavor-chat-ia'); ?></strong>
                        <?php echo esc_html($nodo_local->nombre ?? __('Sin nombre', 'flavor-chat-ia')); ?>
                        <?php if (!empty($modulos_activos)): ?>
                            <br>
                            <strong><?php _e('Módulos activos:', 'flavor-chat-ia'); ?></strong>
                            <?php echo esc_html(implode(', ', $modulos_activos)); ?>
                        <?php else: ?>
                            <br>
                            <strong><?php _e('Módulos activos:', 'flavor-chat-ia'); ?></strong>
                            <?php _e('Ninguno', 'flavor-chat-ia'); ?>
                        <?php endif; ?>
                        <?php if (!empty($nodo_local->updated_at)): ?>
                            <br>
                            <strong><?php _e('Última actualización:', 'flavor-chat-ia'); ?></strong>
                            <?php echo esc_html($nodo_local->updated_at); ?>
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p><?php _e('Primero configura tu nodo local para poder activar módulos.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>

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
                        <?php _e('Guardar módulos activos', 'flavor-chat-ia'); ?>
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
            echo '<div class="notice notice-warning"><p>' . __('Configura tu nodo primero.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <div class="flavor-network-matching">
            <h2><?php _e('Matching: Necesidades ↔ Excedentes', 'flavor-chat-ia'); ?></h2>
            <p class="description"><?php _e('El sistema busca coincidencias entre las necesidades de tu nodo y los excedentes de otros (y viceversa).', 'flavor-chat-ia'); ?></p>

            <div style="margin-bottom:15px;display:flex;gap:10px;align-items:center;">
                <button type="button" class="button button-primary" id="btn-buscar-matches"><?php _e('Buscar nuevos matches', 'flavor-chat-ia'); ?></button>
                <select id="match-filtro-estado">
                    <option value=""><?php _e('Todos los estados', 'flavor-chat-ia'); ?></option>
                    <option value="sugerido"><?php _e('Sugeridos', 'flavor-chat-ia'); ?></option>
                    <option value="aceptado"><?php _e('Aceptados', 'flavor-chat-ia'); ?></option>
                    <option value="contactado"><?php _e('Contactados', 'flavor-chat-ia'); ?></option>
                    <option value="en_proceso"><?php _e('En proceso', 'flavor-chat-ia'); ?></option>
                    <option value="rechazado"><?php _e('Rechazados', 'flavor-chat-ia'); ?></option>
                </select>
                <span id="match-count" style="color:#6b7280;font-size:13px;"></span>
            </div>

            <div id="matches-lista">
                <p class="description"><?php _e('Cargando matches...', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <?php
    }

}
