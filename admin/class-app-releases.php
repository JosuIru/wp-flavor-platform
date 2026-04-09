<?php
/**
 * Gestión de Releases y Versiones de Apps
 *
 * Panel para gestionar versiones de APKs, changelogs y distribución.
 *
 * @package Flavor_Chat_IA
 * @subpackage Admin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_App_Releases {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Slug del menú
     */
    const MENU_SLUG = 'flavor-app-releases';

    /**
     * Tabla de releases
     */
    private $table_name;

    /**
     * Directorio de releases
     */
    private $releases_dir;

    /**
     * URL de releases
     */
    private $releases_url;

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'flavor_app_releases';
        $this->releases_dir = FLAVOR_CHAT_IA_PATH . 'releases/';
        $this->releases_url = FLAVOR_CHAT_IA_URL . 'releases/';

        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_flavor_release_create', array($this, 'ajax_create_release'));
        add_action('wp_ajax_flavor_release_update', array($this, 'ajax_update_release'));
        add_action('wp_ajax_flavor_release_delete', array($this, 'ajax_delete_release'));
        add_action('wp_ajax_flavor_release_upload', array($this, 'ajax_upload_apk'));
        add_action('wp_ajax_flavor_release_list', array($this, 'ajax_list_releases'));
        add_action('wp_ajax_flavor_release_publish', array($this, 'ajax_publish_release'));
        add_action('wp_ajax_nopriv_flavor_app_check_update', array($this, 'ajax_check_update'));

        // Crear tabla si no existe
        add_action('admin_init', array($this, 'ensure_table_exists'));
    }

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Asegurar que la tabla existe
     */
    public function ensure_table_exists() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            version varchar(20) NOT NULL,
            build_number int(11) NOT NULL,
            app_type varchar(20) NOT NULL DEFAULT 'client',
            platform varchar(20) NOT NULL DEFAULT 'android',
            channel varchar(20) NOT NULL DEFAULT 'stable',
            file_name varchar(255) DEFAULT NULL,
            file_size bigint(20) DEFAULT NULL,
            file_hash varchar(64) DEFAULT NULL,
            download_url varchar(500) DEFAULT NULL,
            changelog longtext DEFAULT NULL,
            min_os_version varchar(20) DEFAULT NULL,
            is_mandatory tinyint(1) DEFAULT 0,
            is_published tinyint(1) DEFAULT 0,
            published_at datetime DEFAULT NULL,
            downloads int(11) DEFAULT 0,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY version_platform (version, platform, app_type),
            KEY channel (channel),
            KEY is_published (is_published),
            KEY published_at (published_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Crear directorio de releases
        if (!is_dir($this->releases_dir)) {
            mkdir($this->releases_dir, 0755, true);
            file_put_contents($this->releases_dir . '.htaccess', 'Options -Indexes');
        }
    }

    /**
     * Agregar página de menú
     */
    public function add_menu_page() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Releases Apps', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Releases Apps', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            self::MENU_SLUG,
            array($this, 'render_page')
        );
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-app-releases',
            FLAVOR_CHAT_IA_URL . 'admin/css/app-releases.css',
            array(),
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-app-releases',
            FLAVOR_CHAT_IA_URL . 'admin/js/app-releases.js',
            array('jquery'),
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // QR Code library
        wp_enqueue_script(
            'qrcode-js',
            'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js',
            array(),
            '1.5.3',
            true
        );

        wp_localize_script('flavor-app-releases', 'flavorReleases', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_releases'),
            'uploadUrl' => admin_url('async-upload.php'),
            'uploadNonce' => wp_create_nonce('media-upload'),
            'i18n' => array(
                'confirmDelete' => __('¿Eliminar esta release?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmPublish' => __('¿Publicar esta release? Los usuarios recibirán notificación de actualización.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'uploading' => __('Subiendo...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'saved' => __('Guardado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error', FLAVOR_PLATFORM_TEXT_DOMAIN),
            )
        ));
    }

    /**
     * Renderizar página
     */
    public function render_page() {
        ?>
        <div class="wrap flavor-app-releases-wrap">
            <h1>
                <span class="dashicons dashicons-cloud-upload"></span>
                <?php _e('Gestión de Releases', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <button type="button" id="new-release-btn" class="page-title-action">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Nueva Release', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </h1>

            <!-- Estadísticas -->
            <div class="releases-stats">
                <?php $stats = $this->get_stats(); ?>
                <div class="stat-card">
                    <span class="stat-value"><?php echo esc_html($stats['total_releases']); ?></span>
                    <span class="stat-label"><?php _e('Total Releases', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo esc_html($stats['published']); ?></span>
                    <span class="stat-label"><?php _e('Publicadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo esc_html($stats['total_downloads']); ?></span>
                    <span class="stat-label"><?php _e('Descargas Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo esc_html($stats['latest_version']); ?></span>
                    <span class="stat-label"><?php _e('Última Versión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <!-- Filtros -->
            <div class="releases-filters">
                <select id="filter-app-type">
                    <option value=""><?php _e('Todas las apps', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="client"><?php _e('App Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="admin"><?php _e('App Admin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
                <select id="filter-channel">
                    <option value=""><?php _e('Todos los canales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="stable"><?php _e('Estable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="beta"><?php _e('Beta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="alpha"><?php _e('Alpha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
                <select id="filter-status">
                    <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="published"><?php _e('Publicadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="draft"><?php _e('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <!-- Lista de releases -->
            <div class="releases-list" id="releases-list">
                <div class="loading"><?php _e('Cargando releases...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>

            <!-- Modal: Nueva/Editar Release -->
            <div id="release-modal" class="flavor-modal" style="display:none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modal-title"><?php _e('Nueva Release', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <button type="button" class="modal-close">&times;</button>
                    </div>
                    <form id="release-form">
                        <input type="hidden" name="release_id" id="release_id" value="">

                        <div class="modal-body">
                            <div class="form-row two-cols">
                                <div class="form-field">
                                    <label for="release_version"><?php _e('Versión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                                    <input type="text" id="release_version" name="version"
                                           placeholder="1.0.0" required pattern="\d+\.\d+\.\d+">
                                </div>
                                <div class="form-field">
                                    <label for="release_build"><?php _e('Build Number', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                                    <input type="number" id="release_build" name="build_number"
                                           placeholder="1" required min="1">
                                </div>
                            </div>

                            <div class="form-row three-cols">
                                <div class="form-field">
                                    <label for="release_app_type"><?php _e('Tipo de App', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <select id="release_app_type" name="app_type">
                                        <option value="client"><?php _e('Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="admin"><?php _e('Admin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="release_platform"><?php _e('Plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <select id="release_platform" name="platform">
                                        <option value="android"><?php _e('Android', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="ios"><?php _e('iOS', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="release_channel"><?php _e('Canal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <select id="release_channel" name="channel">
                                        <option value="stable"><?php _e('Estable', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="beta"><?php _e('Beta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="alpha"><?php _e('Alpha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <label><?php _e('Archivo APK', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <div class="file-upload-area" id="apk-upload-area">
                                    <div class="upload-placeholder" id="upload-placeholder">
                                        <span class="dashicons dashicons-upload"></span>
                                        <span><?php _e('Arrastra el APK aquí o haz clic para seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    </div>
                                    <div class="upload-progress" id="upload-progress" style="display:none;">
                                        <div class="progress-bar"></div>
                                        <span class="progress-text">0%</span>
                                    </div>
                                    <div class="uploaded-file" id="uploaded-file" style="display:none;">
                                        <span class="dashicons dashicons-media-default"></span>
                                        <span class="file-name"></span>
                                        <span class="file-size"></span>
                                        <button type="button" class="remove-file">&times;</button>
                                    </div>
                                    <input type="file" id="apk_file" accept=".apk,.aab,.ipa" style="display:none;">
                                    <input type="hidden" name="file_name" id="file_name">
                                    <input type="hidden" name="file_size" id="file_size">
                                    <input type="hidden" name="file_hash" id="file_hash">
                                </div>
                            </div>

                            <div class="form-row">
                                <label for="release_changelog"><?php _e('Changelog', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <textarea id="release_changelog" name="changelog" rows="6"
                                          placeholder="- Nueva funcionalidad X&#10;- Corrección de bugs&#10;- Mejoras de rendimiento"></textarea>
                            </div>

                            <div class="form-row two-cols">
                                <div class="form-field">
                                    <label for="release_min_os"><?php _e('Versión Mínima OS', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <input type="text" id="release_min_os" name="min_os_version"
                                           placeholder="Android 5.0 / iOS 12.0">
                                </div>
                                <div class="form-field checkbox-field">
                                    <label>
                                        <input type="checkbox" id="release_mandatory" name="is_mandatory" value="1">
                                        <?php _e('Actualización obligatoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </label>
                                    <p class="description"><?php _e('Los usuarios deberán actualizar para seguir usando la app', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="button modal-close"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                            <button type="submit" class="button button-primary" id="save-release">
                                <?php _e('Guardar Release', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: QR Code -->
            <div id="qr-modal" class="flavor-modal" style="display:none;">
                <div class="modal-content modal-small">
                    <div class="modal-header">
                        <h2><?php _e('QR de Descarga', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <button type="button" class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body qr-body">
                        <div id="qr-code"></div>
                        <p class="qr-version"></p>
                        <p class="qr-url"></p>
                        <button type="button" id="download-qr" class="button">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Descargar QR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener estadísticas
     */
    private function get_stats() {
        global $wpdb;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $published = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_published = 1");
        $downloads = $wpdb->get_var("SELECT SUM(downloads) FROM {$this->table_name}");
        $latest = $wpdb->get_var("SELECT version FROM {$this->table_name} WHERE is_published = 1 ORDER BY published_at DESC LIMIT 1");

        return array(
            'total_releases' => (int) $total,
            'published' => (int) $published,
            'total_downloads' => (int) $downloads ?: 0,
            'latest_version' => $latest ?: '-',
        );
    }

    /**
     * AJAX: Listar releases
     */
    public function ajax_list_releases() {
        check_ajax_referer('flavor_releases', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;

        $where = array('1=1');
        $params = array();

        if (!empty($_POST['app_type'])) {
            $where[] = 'app_type = %s';
            $params[] = sanitize_text_field($_POST['app_type']);
        }

        if (!empty($_POST['channel'])) {
            $where[] = 'channel = %s';
            $params[] = sanitize_text_field($_POST['channel']);
        }

        if (!empty($_POST['status'])) {
            if ($_POST['status'] === 'published') {
                $where[] = 'is_published = 1';
            } else {
                $where[] = 'is_published = 0';
            }
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY created_at DESC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, ...$params);
        }

        $releases = $wpdb->get_results($sql, ARRAY_A);

        // Formatear datos
        foreach ($releases as &$release) {
            $release['file_size_formatted'] = size_format($release['file_size'] ?: 0);
            $release['created_at_formatted'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($release['created_at']));
            $release['published_at_formatted'] = $release['published_at'] ? date_i18n(get_option('date_format'), strtotime($release['published_at'])) : '-';
            $release['download_url'] = $release['download_url'] ?: ($release['file_name'] ? $this->releases_url . $release['file_name'] : '');
        }

        wp_send_json_success($releases);
    }

    /**
     * AJAX: Crear release
     */
    public function ajax_create_release() {
        check_ajax_referer('flavor_releases', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;

        $data = array(
            'version' => sanitize_text_field($_POST['version']),
            'build_number' => intval($_POST['build_number']),
            'app_type' => sanitize_text_field($_POST['app_type'] ?? 'client'),
            'platform' => sanitize_text_field($_POST['platform'] ?? 'android'),
            'channel' => sanitize_text_field($_POST['channel'] ?? 'stable'),
            'file_name' => sanitize_text_field($_POST['file_name'] ?? ''),
            'file_size' => intval($_POST['file_size'] ?? 0),
            'file_hash' => sanitize_text_field($_POST['file_hash'] ?? ''),
            'changelog' => wp_kses_post($_POST['changelog'] ?? ''),
            'min_os_version' => sanitize_text_field($_POST['min_os_version'] ?? ''),
            'is_mandatory' => !empty($_POST['is_mandatory']) ? 1 : 0,
            'created_by' => get_current_user_id(),
        );

        // Verificar que no existe
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE version = %s AND platform = %s AND app_type = %s",
            $data['version'],
            $data['platform'],
            $data['app_type']
        ));

        if ($existing) {
            wp_send_json_error('Ya existe una release con esta versión');
        }

        $result = $wpdb->insert($this->table_name, $data);

        if ($result) {
            wp_send_json_success(array(
                'id' => $wpdb->insert_id,
                'message' => 'Release creada correctamente',
            ));
        } else {
            wp_send_json_error('Error al crear release');
        }
    }

    /**
     * AJAX: Actualizar release
     */
    public function ajax_update_release() {
        check_ajax_referer('flavor_releases', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;

        $id = intval($_POST['release_id']);
        if (!$id) {
            wp_send_json_error('ID inválido');
        }

        $data = array(
            'version' => sanitize_text_field($_POST['version']),
            'build_number' => intval($_POST['build_number']),
            'app_type' => sanitize_text_field($_POST['app_type'] ?? 'client'),
            'platform' => sanitize_text_field($_POST['platform'] ?? 'android'),
            'channel' => sanitize_text_field($_POST['channel'] ?? 'stable'),
            'changelog' => wp_kses_post($_POST['changelog'] ?? ''),
            'min_os_version' => sanitize_text_field($_POST['min_os_version'] ?? ''),
            'is_mandatory' => !empty($_POST['is_mandatory']) ? 1 : 0,
        );

        // Actualizar archivo si se subió nuevo
        if (!empty($_POST['file_name'])) {
            $data['file_name'] = sanitize_text_field($_POST['file_name']);
            $data['file_size'] = intval($_POST['file_size'] ?? 0);
            $data['file_hash'] = sanitize_text_field($_POST['file_hash'] ?? '');
        }

        $result = $wpdb->update($this->table_name, $data, array('id' => $id));

        if ($result !== false) {
            wp_send_json_success('Release actualizada correctamente');
        } else {
            wp_send_json_error('Error al actualizar');
        }
    }

    /**
     * AJAX: Eliminar release
     */
    public function ajax_delete_release() {
        check_ajax_referer('flavor_releases', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;

        $id = intval($_POST['release_id']);

        // Obtener info del archivo
        $release = $wpdb->get_row($wpdb->prepare(
            "SELECT file_name FROM {$this->table_name} WHERE id = %d",
            $id
        ));

        // Eliminar archivo
        if ($release && $release->file_name) {
            $file_path = $this->releases_dir . $release->file_name;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Eliminar registro
        $result = $wpdb->delete($this->table_name, array('id' => $id));

        if ($result) {
            wp_send_json_success('Release eliminada');
        } else {
            wp_send_json_error('Error al eliminar');
        }
    }

    /**
     * AJAX: Subir APK
     */
    public function ajax_upload_apk() {
        check_ajax_referer('flavor_releases', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        if (empty($_FILES['apk_file'])) {
            wp_send_json_error('No se recibió archivo');
        }

        $file = $_FILES['apk_file'];
        $allowed_types = array('apk', 'aab', 'ipa');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_types)) {
            wp_send_json_error('Tipo de archivo no permitido');
        }

        // Generar nombre único
        $new_name = sprintf(
            '%s_%s_%s.%s',
            sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
            date('Ymd_His'),
            substr(md5(uniqid()), 0, 8),
            $ext
        );

        $destination = $this->releases_dir . $new_name;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $hash = hash_file('sha256', $destination);

            wp_send_json_success(array(
                'file_name' => $new_name,
                'file_size' => filesize($destination),
                'file_hash' => $hash,
                'download_url' => $this->releases_url . $new_name,
            ));
        } else {
            wp_send_json_error('Error al subir archivo');
        }
    }

    /**
     * AJAX: Publicar release
     */
    public function ajax_publish_release() {
        check_ajax_referer('flavor_releases', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;

        $id = intval($_POST['release_id']);

        $result = $wpdb->update(
            $this->table_name,
            array(
                'is_published' => 1,
                'published_at' => current_time('mysql'),
            ),
            array('id' => $id)
        );

        if ($result !== false) {
            // Obtener info para notificación
            $release = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ));

            // Disparar acción para notificaciones push
            do_action('flavor_app_release_published', $release);

            wp_send_json_success(array(
                'message' => 'Release publicada',
                'version' => $release->version,
            ));
        } else {
            wp_send_json_error('Error al publicar');
        }
    }

    /**
     * AJAX: Verificar actualizaciones (público)
     */
    public function ajax_check_update() {
        $app_type = sanitize_text_field($_REQUEST['app_type'] ?? 'client');
        $platform = sanitize_text_field($_REQUEST['platform'] ?? 'android');
        $current_version = sanitize_text_field($_REQUEST['version'] ?? '0.0.0');
        $channel = sanitize_text_field($_REQUEST['channel'] ?? 'stable');

        global $wpdb;

        $release = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE app_type = %s AND platform = %s AND channel = %s AND is_published = 1
             ORDER BY build_number DESC
             LIMIT 1",
            $app_type,
            $platform,
            $channel
        ));

        if (!$release) {
            wp_send_json_success(array(
                'update_available' => false,
            ));
        }

        // Comparar versiones
        $has_update = version_compare($release->version, $current_version, '>');

        if ($has_update) {
            // Incrementar contador de descargas si descarga
            if (!empty($_REQUEST['download'])) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$this->table_name} SET downloads = downloads + 1 WHERE id = %d",
                    $release->id
                ));
            }

            wp_send_json_success(array(
                'update_available' => true,
                'version' => $release->version,
                'build_number' => $release->build_number,
                'changelog' => $release->changelog,
                'download_url' => $release->download_url ?: ($this->releases_url . $release->file_name),
                'file_size' => (int) $release->file_size,
                'is_mandatory' => (bool) $release->is_mandatory,
                'min_os_version' => $release->min_os_version,
                'published_at' => $release->published_at,
            ));
        } else {
            wp_send_json_success(array(
                'update_available' => false,
                'current_is_latest' => true,
            ));
        }
    }

    /**
     * Obtener URL de descarga para una versión
     */
    public function get_download_url($version = null, $app_type = 'client', $platform = 'android') {
        global $wpdb;

        $where = "app_type = %s AND platform = %s AND is_published = 1";
        $params = array($app_type, $platform);

        if ($version) {
            $where .= " AND version = %s";
            $params[] = $version;
        }

        $release = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY build_number DESC LIMIT 1",
            ...$params
        ));

        if ($release && $release->file_name) {
            return $this->releases_url . $release->file_name;
        }

        return null;
    }
}

// Inicializar
Flavor_App_Releases::get_instance();
