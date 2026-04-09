<?php
/**
 * Administrador de Relaciones entre Módulos
 *
 * Permite configurar dinámicamente qué módulos horizontales se vinculan
 * a cada módulo vertical, por contexto (global o específico de comunidad).
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin_page_chrome_file = dirname(__FILE__) . '/class-admin-page-chrome.php';
if (!class_exists('Flavor_Admin_Page_Chrome') && file_exists($admin_page_chrome_file)) {
    require_once $admin_page_chrome_file;
}

class Flavor_Module_Relations_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Cache local de relaciones por defecto.
     *
     * @var array<string,array<int,string>>|null
     */
    private $default_relations_cache = null;

    /**
     * Normaliza IDs de módulos.
     *
     * @param string $module_id
     * @return string
     */
    private function normalizar_module_id($module_id) {
        $module_id = sanitize_key((string) $module_id);
        return str_replace('-', '_', $module_id);
    }

    /**
     * Devuelve el mapa normalizado de relaciones por defecto.
     *
     * @return array<string,array<int,string>>
     */
    private function obtener_mapa_relaciones_por_defecto() {
        if ($this->default_relations_cache !== null) {
            return $this->default_relations_cache;
        }

        $file = dirname(__FILE__) . '/../includes/modules/data/default-module-relations.php';
        if (file_exists($file)) {
            require_once $file;
        }

        $relations = function_exists('flavor_get_default_module_relations')
            ? (array) flavor_get_default_module_relations()
            : [];

        $normalized = [];
        foreach ($relations as $parent_id => $children) {
            $normalized_parent = $this->normalizar_module_id($parent_id);
            $normalized[$normalized_parent] = array_values(array_unique(array_filter(array_map([$this, 'normalizar_module_id'], (array) $children))));
        }

        $this->default_relations_cache = $normalized;

        return $this->default_relations_cache;
    }

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // El menú se registra centralizadamente en Flavor_Admin_Menu_Manager.
        add_action('admin_enqueue_scripts', [$this, 'registrar_assets']);
        add_action('wp_ajax_flavor_save_module_relations', [$this, 'ajax_guardar_relaciones']);
        add_action('wp_ajax_flavor_get_module_relations', [$this, 'ajax_obtener_relaciones']);
        add_action('wp_ajax_flavor_reset_module_relations', [$this, 'ajax_resetear_relaciones']);
    }

    /**
     * Registrar página de administración
     */
    public function registrar_pagina_admin() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Relaciones entre Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Relaciones Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-module-relations',
            [$this, 'renderizar_pagina']
        );
    }

    /**
     * Registrar assets CSS/JS
     */
    public function registrar_assets($hook) {
        $current_page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        $is_relations_page = ($current_page === 'flavor-module-relations');
        $matches_legacy_hook = ('flavor-chat-ia_page_flavor-module-relations' === $hook);
        $matches_dynamic_hook = (is_string($hook) && strpos($hook, 'page_flavor-module-relations') !== false);

        if (!$is_relations_page && !$matches_legacy_hook && !$matches_dynamic_hook) {
            return;
        }

        $style_path = dirname(__FILE__) . '/css/module-relations.css';
        $script_path = dirname(__FILE__) . '/js/module-relations.js';
        $asset_version = defined('FLAVOR_CHAT_IA_VERSION')
            ? FLAVOR_CHAT_IA_VERSION
            : '1.0.0';
        $style_version = file_exists($style_path) ? (string) filemtime($style_path) : $asset_version;
        $script_version = file_exists($script_path) ? (string) filemtime($script_path) : $asset_version;

        wp_enqueue_style(
            'flavor-module-relations',
            plugins_url('/css/module-relations.css', __FILE__),
            [],
            $style_version
        );

        wp_enqueue_script(
            'flavor-module-relations',
            plugins_url('/js/module-relations.js', __FILE__),
            ['jquery', 'wp-util'],
            $script_version,
            true
        );

        wp_localize_script('flavor-module-relations', 'flavorModuleRelations', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_module_relations'),
            'i18n' => [
                'guardado' => __('Relaciones guardadas correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al guardar relaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmReset' => __('¿Estás seguro de resetear todas las relaciones? Se cargarán los valores por defecto del código.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Renderizar página de administración
     */
    public function renderizar_pagina() {
        $modulos_verticales = $this->obtener_modulos_verticales();
        $modulos_horizontales = $this->obtener_modulos_horizontales();
        $contextos = $this->obtener_contextos_disponibles();
        $contexto_actual = $_GET['context'] ?? 'global';

        ?>
        <div class="wrap flavor-module-relations-page">
            <?php if (class_exists('Flavor_Admin_Page_Chrome')) : ?>
                <?php Flavor_Admin_Page_Chrome::render_breadcrumbs('ecosystem', 'flavor-module-relations', __('Relaciones', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                <?php Flavor_Admin_Page_Chrome::render_compact_nav(Flavor_Admin_Page_Chrome::get_section_links('ecosystem', 'flavor-module-relations')); ?>
            <?php endif; ?>
            <h1><?php _e('Configuración de Relaciones entre Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <div class="flavor-mr-description">
                <p><?php _e('Configura qué módulos horizontales (servicios/herramientas) se muestran en cada módulo vertical (principal).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <p><strong><?php _e('Módulos Verticales:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Son los módulos principales de negocio (Grupos de Consumo, Eventos, Comunidades, etc.).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <p><strong><?php _e('Módulos Horizontales:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php _e('Son servicios que se integran (Foro, Chat, Multimedia, Recetas, Biblioteca, Podcast, etc.).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <!-- Selector de Contexto -->
            <div class="flavor-mr-context-selector">
                <label for="context-select">
                    <strong><?php _e('Contexto:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                </label>
                <select id="context-select" class="flavor-context-select">
                    <option value="global" <?php selected($contexto_actual, 'global'); ?>>
                        <?php _e('Global (por defecto)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </option>
                    <?php foreach ($contextos as $ctx_id => $ctx_label): ?>
                        <option value="<?php echo esc_attr($ctx_id); ?>" <?php selected($contexto_actual, $ctx_id); ?>>
                            <?php echo esc_html($ctx_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php _e('Global aplica a todos. También puedes configurar relaciones específicas por comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <div class="flavor-mr-notice flavor-mr-notice--hidden"></div>

            <!-- Formulario de Relaciones -->
            <form id="flavor-module-relations-form" class="flavor-mr-form">
                <?php wp_nonce_field('flavor_module_relations', 'flavor_mr_nonce'); ?>
                <input type="hidden" name="context" value="<?php echo esc_attr($contexto_actual); ?>">

                <?php foreach ($modulos_verticales as $vertical_id => $vertical_data): ?>
                    <div class="flavor-mr-vertical-module">
                        <div class="flavor-mr-module-header">
                            <span class="dashicons <?php echo esc_attr($vertical_data['icon']); ?>"></span>
                            <h2><?php echo esc_html($vertical_data['name']); ?></h2>
                            <span class="flavor-mr-module-id">(<?php echo esc_html($vertical_id); ?>)</span>
                        </div>

                        <div class="flavor-mr-horizontal-modules">
                            <h3><?php _e('Módulos Horizontales Vinculados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                            <?php
                            $relaciones_actuales = $this->obtener_relaciones($vertical_id, $contexto_actual);
                            ?>

                            <div class="flavor-mr-checkboxes">
                                <?php foreach ($modulos_horizontales as $horizontal_id => $horizontal_data): ?>
                                    <?php
                                    $checked = in_array($horizontal_id, $relaciones_actuales);
                                    $priority = $checked ? array_search($horizontal_id, $relaciones_actuales) : 50;
                                    ?>
                                    <label class="flavor-mr-checkbox-label">
                                        <input
                                            type="checkbox"
                                            name="relations[<?php echo esc_attr($vertical_id); ?>][]"
                                            value="<?php echo esc_attr($horizontal_id); ?>"
                                            data-parent="<?php echo esc_attr($vertical_id); ?>"
                                            data-child="<?php echo esc_attr($horizontal_id); ?>"
                                            <?php checked($checked); ?>
                                        >
                                        <span class="dashicons <?php echo esc_attr($horizontal_data['icon']); ?>"></span>
                                        <span class="flavor-mr-module-label"><?php echo esc_html($horizontal_data['name']); ?></span>
                                        <span class="flavor-mr-module-id-small">(<?php echo esc_html($horizontal_id); ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div class="flavor-mr-config-hint">
                                <span class="dashicons dashicons-info"></span>
                                <?php _e('Los módulos seleccionados aparecerán en la navegación del portal unificado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="flavor-mr-actions">
                    <button type="submit" class="button button-primary button-large">
                        <?php _e('Guardar Relaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="flavor-reset-relations">
                        <?php _e('Resetear a Valores por Defecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>

            <!-- Previsualización de Navegación -->
            <div class="flavor-mr-preview">
                <h2><?php _e('Previsualización de Navegación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="description">
                    <?php _e('Así se verá la navegación para cada módulo vertical según la configuración actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
                <div id="flavor-nav-preview-container"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener módulos verticales activos
     */
    private function obtener_modulos_verticales() {
        $active_modules = get_option('flavor_active_modules', []);
        $module_loader = class_exists('Flavor_Chat_Module_Loader') ? Flavor_Chat_Module_Loader::get_instance() : null;

        if (!$module_loader) {
            return [];
        }

        $all_modules = $this->obtener_todos_los_modulos($module_loader);
        $verticales = [];
        $default_relations = $this->obtener_mapa_relaciones_por_defecto();

        foreach ($all_modules as $module_id => $module) {
            if (!in_array($module_id, $active_modules)) {
                continue;
            }

            if (!is_object($module)) {
                continue;
            }

            $metadata = $this->obtener_metadata_modulo($module);
            $role = $metadata['module_role'] ?? 'vertical';
            $normalized_module_id = $this->normalizar_module_id($module_id);

            if ($role === 'vertical' || $role === 'base' || isset($default_relations[$normalized_module_id])) {
                $verticales[$module_id] = [
                    'name' => $this->obtener_nombre_modulo($module, $module_id),
                    'icon' => $this->obtener_icono_modulo($module),
                    'role' => $role,
                ];
            }
        }

        return $verticales;
    }

    /**
     * Obtener módulos horizontales (transversales y service)
     */
    private function obtener_modulos_horizontales() {
        $active_modules = get_option('flavor_active_modules', []);
        $module_loader = class_exists('Flavor_Chat_Module_Loader') ? Flavor_Chat_Module_Loader::get_instance() : null;

        if (!$module_loader) {
            return [];
        }

        $all_modules = $this->obtener_todos_los_modulos($module_loader);
        $horizontales = [];
        $default_relations = $this->obtener_mapa_relaciones_por_defecto();
        $default_children = [];
        foreach ($default_relations as $children) {
            foreach ($children as $child_id) {
                $default_children[$child_id] = true;
            }
        }

        foreach ($all_modules as $module_id => $module) {
            if (!in_array($module_id, $active_modules)) {
                continue;
            }

            if (!is_object($module)) {
                continue;
            }

            $metadata = $this->obtener_metadata_modulo($module);
            $role = $metadata['module_role'] ?? 'vertical';
            $normalized_module_id = $this->normalizar_module_id($module_id);

            // Módulos horizontales: transversal, service, o específicos conocidos
            if (
                in_array($role, ['transversal', 'service', 'ecosystem'], true) ||
                in_array($normalized_module_id, ['foros', 'chat_interno', 'chat_grupos', 'multimedia', 'recetas', 'biblioteca', 'podcast', 'radio', 'red_social', 'participacion', 'transparencia', 'presupuestos_participativos', 'espacios_comunes', 'reservas', 'talleres', 'cursos', 'eventos', 'socios', 'reciclaje', 'marketplace', 'trabajo_digno', 'banco_tiempo', 'economia_don', 'incidencias', 'proyectos'], true) ||
                isset($default_children[$normalized_module_id])
            ) {
                $horizontales[$module_id] = [
                    'name' => $this->obtener_nombre_modulo($module, $module_id),
                    'icon' => $this->obtener_icono_modulo($module),
                    'role' => $role,
                ];
            }
        }

        return $horizontales;
    }

    /**
     * Obtener contextos disponibles (global + comunidades)
     */
    private function obtener_contextos_disponibles() {
        global $wpdb;
        $contextos = [];

        // Obtener comunidades activas
        $table_comunidades = $wpdb->prefix . 'flavor_comunidades';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_comunidades'") === $table_comunidades) {
            $comunidades = $wpdb->get_results(
                "SELECT id, nombre FROM $table_comunidades WHERE estado = 'activa' ORDER BY nombre ASC"
            );

            foreach ($comunidades as $comunidad) {
                $contextos['comunidad_' . $comunidad->id] = sprintf(
                    __('Comunidad: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $comunidad->nombre
                );
            }
        }

        return $contextos;
    }

    /**
     * Obtener relaciones de un módulo vertical
     */
    public function obtener_relaciones($parent_module_id, $context = 'global') {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_module_relations';
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        $parent_module_id = $this->normalizar_module_id($parent_module_id);
        $context = sanitize_text_field($context ?: 'global');

        if (!$table_exists) {
            return $this->obtener_relaciones_desde_codigo($parent_module_id, $context);
        }

        $relaciones = $wpdb->get_col($wpdb->prepare(
            "SELECT child_module_id
             FROM $table
             WHERE parent_module_id = %s AND context = %s AND enabled = 1
             ORDER BY priority ASC",
            $parent_module_id,
            $context
        ));

        // Si no hay relaciones en BD para este contexto, obtener del código (fallback)
        if (empty($relaciones) && $context === 'global') {
            $relaciones = $this->obtener_relaciones_desde_codigo($parent_module_id, $context);
        }

        return array_values(array_unique(array_filter(array_map([$this, 'normalizar_module_id'], (array) $relaciones))));
    }

    /**
     * Obtener metadata del módulo de forma segura
     */
    private function obtener_metadata_modulo($module) {
        if (!is_object($module) || !method_exists($module, 'get_ecosystem_metadata')) {
            return [];
        }

        $metadata = $module->get_ecosystem_metadata();
        return is_array($metadata) ? $metadata : [];
    }

    /**
     * Obtener nombre del módulo de forma segura
     */
    private function obtener_nombre_modulo($module, $module_id) {
        if (is_object($module) && method_exists($module, 'get_name')) {
            $name = $module->get_name();
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        return ucwords(str_replace(['_', '-'], ' ', (string) $module_id));
    }

    /**
     * Obtener icono del módulo de forma segura
     */
    private function obtener_icono_modulo($module) {
        if (is_object($module) && method_exists($module, 'get_icon')) {
            $icon = $module->get_icon();
            if (is_string($icon) && $icon !== '') {
                return $icon;
            }
        }

        return 'dashicons-admin-plugins';
    }

    /**
     * Obtener relaciones fallback desde el código del módulo
     */
    private function obtener_relaciones_desde_codigo($parent_module_id, $context = 'global') {
        if ($context !== 'global') {
            return [];
        }

        $module_loader = class_exists('Flavor_Chat_Module_Loader') ? Flavor_Chat_Module_Loader::get_instance() : null;
        if (!$module_loader) {
            return [];
        }

        $all_modules = $this->obtener_todos_los_modulos($module_loader);
        if (empty($all_modules[$parent_module_id]) || !is_object($all_modules[$parent_module_id])) {
            return [];
        }

        $metadata = $this->obtener_metadata_modulo($all_modules[$parent_module_id]);
        $relaciones = $metadata['ecosystem_supports_modules'] ?? [];

        return is_array($relaciones) ? $relaciones : [];
    }

    /**
     * Obtener módulos desde la API disponible del loader
     */
    private function obtener_todos_los_modulos($module_loader) {
        if (!is_object($module_loader)) {
            return [];
        }

        if (method_exists($module_loader, 'get_all_modules')) {
            $modules = $module_loader->get_all_modules();
            return is_array($modules) ? $modules : [];
        }

        if (method_exists($module_loader, 'get_loaded_modules')) {
            $modules = $module_loader->get_loaded_modules();
            return is_array($modules) ? $modules : [];
        }

        return [];
    }

    /**
     * Guardar relaciones de módulos
     */
    public function guardar_relaciones($parent_module_id, $child_modules, $context = 'global') {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_module_relations';
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        if (!$table_exists) {
            return false;
        }

        $parent_module_id = $this->normalizar_module_id($parent_module_id);
        $context = sanitize_text_field($context ?: 'global');
        $child_modules = array_values(array_unique(array_filter(array_map([$this, 'normalizar_module_id'], (array) $child_modules))));

        // Limpiar relaciones existentes para este parent y contexto
        $wpdb->delete($table, [
            'parent_module_id' => $parent_module_id,
            'context' => $context,
        ]);

        // Insertar nuevas relaciones
        foreach ($child_modules as $priority => $child_module_id) {
            $wpdb->insert($table, [
                'parent_module_id' => $parent_module_id,
                'child_module_id' => $child_module_id,
                'context' => $context,
                'priority' => ($priority + 1) * 10, // 10, 20, 30...
                'enabled' => 1,
                'created_at' => current_time('mysql'),
            ]);
        }

        return true;
    }

    /**
     * AJAX: Guardar relaciones
     */
    public function ajax_guardar_relaciones() {
        check_ajax_referer('flavor_module_relations', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $relations = $_POST['relations'] ?? [];
        $context = sanitize_text_field($_POST['context'] ?? 'global');

        foreach ($relations as $parent_id => $children) {
            $parent_id = $this->normalizar_module_id($parent_id);
            $children = array_values(array_unique(array_filter(array_map([$this, 'normalizar_module_id'], (array) $children))));
            $this->guardar_relaciones($parent_id, $children, $context);
        }

        wp_send_json_success([
            'message' => __('Relaciones guardadas correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Obtener relaciones
     */
    public function ajax_obtener_relaciones() {
        check_ajax_referer('flavor_module_relations', 'nonce');

        $parent_id = $this->normalizar_module_id($_POST['parent_id'] ?? '');
        $context = sanitize_text_field($_POST['context'] ?? 'global');

        if (empty($parent_id)) {
            wp_send_json_error(['message' => __('ID de módulo requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $relaciones = $this->obtener_relaciones($parent_id, $context);

        wp_send_json_success([
            'relations' => $relaciones,
        ]);
    }

    /**
     * AJAX: Resetear todas las relaciones
     */
    public function ajax_resetear_relaciones() {
        check_ajax_referer('flavor_module_relations', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'flavor_module_relations';
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;

        if (!$table_exists) {
            wp_send_json_success([
                'message' => __('No existían relaciones persistidas. Se usarán los valores del código.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        // Eliminar todas las relaciones
        $wpdb->query("DELETE FROM $table");

        wp_send_json_success([
            'message' => __('Relaciones reseteadas. Se usarán los valores del código.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }
}

// Inicializar
Flavor_Module_Relations_Admin::get_instance();
