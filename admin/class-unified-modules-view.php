<?php
/**
 * Vista Unificada de Módulos
 *
 * Combina las tres pantallas de gestión de módulos en una sola vista:
 * - Activación/Desactivación de módulos
 * - Control de visibilidad y permisos
 * - Gestión de landings
 *
 * @package FlavorChatIA
 * @subpackage Admin
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Unified_Modules_View {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Unified_Modules_View
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_fum_toggle_module', [$this, 'ajax_toggle_module']);
        add_action('wp_ajax_fum_save_visibility', [$this, 'ajax_save_visibility']);
        add_action('wp_ajax_fum_create_landing', [$this, 'ajax_create_landing']);
    }

    /**
     * Encola assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'flavor-app-composer') === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-unified-modules',
            FLAVOR_CHAT_IA_URL . 'admin/css/unified-modules.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-unified-modules',
            FLAVOR_CHAT_IA_URL . 'admin/js/unified-modules.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-unified-modules', 'fumData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('flavor/v1/'),
            'nonce' => wp_create_nonce('fum_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'i18n' => [
                'saving' => __('Guardando...', 'flavor-chat-ia'),
                'saved' => __('Guardado', 'flavor-chat-ia'),
                'error' => __('Error al guardar', 'flavor-chat-ia'),
                'confirmActivate' => __('¿Activar este módulo?', 'flavor-chat-ia'),
                'confirmDeactivate' => __('¿Desactivar este módulo?', 'flavor-chat-ia'),
                'creatingLanding' => __('Creando landing...', 'flavor-chat-ia'),
                'landingCreated' => __('Landing creada', 'flavor-chat-ia'),
                'docsError' => __('Error al cargar la documentación', 'flavor-chat-ia'),
                'docsNotFound' => __('Documentación no disponible para este módulo', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Renderiza la vista unificada de módulos
     */
    public function render() {
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos_registrados = $loader->get_registered_modules();
        $gestor_perfiles = Flavor_App_Profiles::get_instance();
        $categorias_modulos = $gestor_perfiles->obtener_categorias_modulos();

        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];
        $visibilidades = get_option('flavor_modules_visibility', []);
        $capacidades = get_option('flavor_modules_capabilities', []);

        // Obtener info de perfiles activos para saber módulos requeridos
        $perfil_activo_id = $gestor_perfiles->obtener_perfil_activo();
        $perfiles = $gestor_perfiles->obtener_perfiles();
        $perfil_activo = $perfiles[$perfil_activo_id] ?? null;
        $modulos_requeridos = $perfil_activo['modulos_requeridos'] ?? [];

        // Estadísticas
        $total_modulos = 0;
        $modulos_activos_count = 0;
        foreach ($categorias_modulos as $categoria_datos) {
            foreach ($categoria_datos['modulos'] as $modulo_id) {
                $total_modulos++;
                if (in_array($modulo_id, $modulos_activos)) {
                    $modulos_activos_count++;
                }
            }
        }

        // Obtener tipos de visibilidad y capacidades
        $tipos_visibilidad = $this->get_visibility_types();
        $capacidades_disponibles = $this->get_available_capabilities();
        ?>
        <div class="flavor-unified-modules" x-data="unifiedModulesState()">
            <!-- Header -->
            <div class="fum-header">
                <div>
                    <h2 class="fum-header__title"><?php esc_html_e('Gestión de Módulos', 'flavor-chat-ia'); ?></h2>
                    <p class="fum-header__description">
                        <?php esc_html_e('Activa módulos, configura permisos de acceso y gestiona landings desde una sola vista.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <div class="fum-header__stats">
                    <div class="fum-stat-badge fum-stat-badge--success">
                        <span class="fum-stat-badge__value"><?php echo esc_html($modulos_activos_count); ?></span>
                        <span class="fum-stat-badge__label"><?php esc_html_e('Activos', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="fum-stat-badge">
                        <span class="fum-stat-badge__value"><?php echo esc_html($total_modulos); ?></span>
                        <span class="fum-stat-badge__label"><?php esc_html_e('Total', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="fum-toolbar">
                <div class="fum-search">
                    <span class="dashicons dashicons-search fum-search__icon"></span>
                    <input type="text"
                           class="fum-search__input"
                           placeholder="<?php esc_attr_e('Buscar módulos...', 'flavor-chat-ia'); ?>"
                           x-model="searchQuery"
                           @input="filterModules()">
                </div>
                <select class="fum-filter-select" x-model="filterStatus" @change="filterModules()">
                    <option value="all"><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
                    <option value="active"><?php esc_html_e('Solo activos', 'flavor-chat-ia'); ?></option>
                    <option value="inactive"><?php esc_html_e('Solo inactivos', 'flavor-chat-ia'); ?></option>
                </select>
                <select class="fum-filter-select" x-model="filterVisibility" @change="filterModules()">
                    <option value="all"><?php esc_html_e('Toda visibilidad', 'flavor-chat-ia'); ?></option>
                    <option value="public"><?php esc_html_e('Públicos', 'flavor-chat-ia'); ?></option>
                    <option value="members_only"><?php esc_html_e('Solo miembros', 'flavor-chat-ia'); ?></option>
                    <option value="private"><?php esc_html_e('Privados', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Category Tabs -->
            <div class="fum-category-tabs">
                <button type="button"
                        class="fum-category-tab"
                        :class="{ 'active': activeCategory === 'all' }"
                        @click="setCategory('all')">
                    <?php esc_html_e('Todos', 'flavor-chat-ia'); ?>
                    <span class="fum-category-tab__count"><?php echo esc_html($total_modulos); ?></span>
                </button>
                <?php foreach ($categorias_modulos as $categoria_id => $categoria_datos) : ?>
                    <?php
                    $count_categoria = count($categoria_datos['modulos']);
                    $activos_categoria = count(array_intersect($categoria_datos['modulos'], $modulos_activos));
                    ?>
                    <button type="button"
                            class="fum-category-tab"
                            :class="{ 'active': activeCategory === '<?php echo esc_js($categoria_id); ?>' }"
                            @click="setCategory('<?php echo esc_js($categoria_id); ?>')">
                        <?php echo esc_html($categoria_datos['nombre']); ?>
                        <span class="fum-category-tab__count"><?php echo esc_html($activos_categoria); ?>/<?php echo esc_html($count_categoria); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Modules Grid -->
            <div class="fum-modules-grid">
                <?php foreach ($categorias_modulos as $categoria_id => $categoria_datos) : ?>
                    <?php foreach ($categoria_datos['modulos'] as $modulo_id) :
                        $info_modulo = $modulos_registrados[$modulo_id] ?? null;
                        if (!$info_modulo) continue;

                        $nombre_modulo = $info_modulo['name'] ?? ucfirst(str_replace('_', ' ', $modulo_id));
                        $descripcion_modulo = $info_modulo['description'] ?? '';
                        $icono_modulo = $info_modulo['icon'] ?? 'dashicons-admin-plugins';

                        $is_active = in_array($modulo_id, $modulos_activos);
                        $is_required = in_array($modulo_id, $modulos_requeridos);
                        $visibility = $visibilidades[$modulo_id] ?? 'public';
                        $capability = $capacidades[$modulo_id] ?? 'read';

                        // Verificar si tiene landing
                        $modulo_slug = str_replace('_', '-', $modulo_id);
                        $pagina_landing = get_page_by_path($modulo_slug);
                        $has_landing = !empty($pagina_landing);
                        $landing_url = $has_landing ? get_permalink($pagina_landing->ID) : '';

                        // Color de categoría
                        $categoria_color = $this->get_category_color($categoria_datos['nombre']);
                    ?>
                    <div class="fum-module-card <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>"
                         x-show="shouldShowModule('<?php echo esc_js($modulo_id); ?>', '<?php echo esc_js($nombre_modulo); ?>', '<?php echo esc_js($categoria_id); ?>', <?php echo $is_active ? 'true' : 'false'; ?>, '<?php echo esc_js($visibility); ?>')"
                         x-transition
                         data-module-id="<?php echo esc_attr($modulo_id); ?>"
                         data-category="<?php echo esc_attr($categoria_id); ?>">

                        <!-- Card Header -->
                        <div class="fum-card__header">
                            <div class="fum-card__icon" style="background: <?php echo esc_attr($categoria_color); ?>15;">
                                <span class="dashicons <?php echo esc_attr($icono_modulo); ?>"
                                      style="color: <?php echo esc_attr($categoria_color); ?>;"></span>
                            </div>
                            <div class="fum-card__info">
                                <h4 class="fum-card__name">
                                    <?php echo esc_html($nombre_modulo); ?>
                                    <?php if ($is_required) : ?>
                                        <span class="fum-badge fum-badge--required"><?php esc_html_e('Requerido', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>
                                    <span class="fum-badge fum-badge--<?php echo esc_attr($visibility); ?>">
                                        <?php echo esc_html($this->get_visibility_label($visibility)); ?>
                                    </span>
                                </h4>
                                <p class="fum-card__desc"><?php echo esc_html($descripcion_modulo); ?></p>
                            </div>
                            <div class="fum-card__toggle">
                                <label class="fum-toggle">
                                    <input type="checkbox"
                                           <?php checked($is_active || $is_required); ?>
                                           <?php disabled($is_required); ?>
                                           @change="toggleModule('<?php echo esc_js($modulo_id); ?>', $event.target.checked)">
                                    <span class="fum-toggle__slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Card Body - Controls -->
                        <div class="fum-card__body">
                            <div class="fum-control-row">
                                <div class="fum-control-group">
                                    <label class="fum-control-group__label"><?php esc_html_e('Visibilidad', 'flavor-chat-ia'); ?></label>
                                    <select @change="saveVisibility('<?php echo esc_js($modulo_id); ?>', $event.target.value, null)">
                                        <?php foreach ($tipos_visibilidad as $valor => $etiqueta) : ?>
                                            <option value="<?php echo esc_attr($valor); ?>" <?php selected($visibility, $valor); ?>>
                                                <?php echo esc_html($etiqueta); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="fum-control-group">
                                    <label class="fum-control-group__label"><?php esc_html_e('Permiso requerido', 'flavor-chat-ia'); ?></label>
                                    <select @change="saveVisibility('<?php echo esc_js($modulo_id); ?>', null, $event.target.value)">
                                        <?php foreach ($capacidades_disponibles as $valor => $etiqueta) : ?>
                                            <option value="<?php echo esc_attr($valor); ?>" <?php selected($capability, $valor); ?>>
                                                <?php echo esc_html($etiqueta); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <span class="fum-saving" :class="{ 'is-visible': savingModules.includes('<?php echo esc_js($modulo_id); ?>') }">
                                <span class="dashicons dashicons-update fum-saving__icon"></span>
                                <?php esc_html_e('Guardando...', 'flavor-chat-ia'); ?>
                            </span>
                        </div>

                        <!-- Card Footer - Actions -->
                        <div class="fum-card__footer">
                            <?php if ($has_landing) : ?>
                                <a href="<?php echo esc_url($landing_url); ?>"
                                   target="_blank"
                                   class="fum-btn fum-btn--secondary fum-btn--flex">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php esc_html_e('Ver Landing', 'flavor-chat-ia'); ?>
                                </a>
                                <a href="<?php echo esc_url(get_edit_post_link($pagina_landing->ID)); ?>"
                                   class="fum-btn fum-btn--secondary fum-btn--small">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                            <?php else : ?>
                                <button type="button"
                                        class="fum-btn fum-btn--primary fum-btn--flex"
                                        @click="createLanding('<?php echo esc_js($modulo_id); ?>')">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    <?php esc_html_e('Crear Landing', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ($is_active) : ?>
                                <?php
                                // Obtener URL de configuración del módulo si existe
                                $config_url = $this->get_module_config_url($modulo_id);
                                if ($config_url) : ?>
                                    <a href="<?php echo esc_url($config_url); ?>"
                                       class="fum-btn fum-btn--secondary fum-btn--small"
                                       title="<?php esc_attr_e('Configuración', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Botón de documentación -->
                            <button type="button"
                                    class="fum-btn fum-btn--secondary fum-btn--small"
                                    title="<?php esc_attr_e('Ver documentación', 'flavor-chat-ia'); ?>"
                                    @click="openDocs('<?php echo esc_js($modulo_id); ?>', '<?php echo esc_js($nombre_modulo); ?>')">
                                <span class="dashicons dashicons-info-outline"></span>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <!-- Empty State -->
            <div class="fum-empty-state" x-show="!hasVisibleModules" x-cloak>
                <span class="dashicons dashicons-admin-plugins fum-empty-state__icon"></span>
                <h3 class="fum-empty-state__title"><?php esc_html_e('No se encontraron módulos', 'flavor-chat-ia'); ?></h3>
                <p class="fum-empty-state__text"><?php esc_html_e('Prueba a cambiar los filtros de búsqueda.', 'flavor-chat-ia'); ?></p>
            </div>

            <!-- Modal de Documentación -->
            <div class="fum-docs-modal" x-show="docsModalOpen" x-cloak @keydown.escape.window="docsModalOpen = false">
                <div class="fum-docs-modal__backdrop" @click="docsModalOpen = false"></div>
                <div class="fum-docs-modal__content" @click.stop>
                    <div class="fum-docs-modal__header">
                        <h3 class="fum-docs-modal__title" x-text="docsModuleName"></h3>
                        <button type="button" class="fum-docs-modal__close" @click="docsModalOpen = false">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="fum-docs-modal__body">
                        <!-- Estado de carga -->
                        <div class="fum-docs-loading" x-show="docsLoading">
                            <span class="dashicons dashicons-update fum-docs-loading__spinner"></span>
                            <span><?php esc_html_e('Cargando documentación...', 'flavor-chat-ia'); ?></span>
                        </div>

                        <!-- Contenido de documentación -->
                        <div class="fum-docs-content" x-show="!docsLoading && docsData">
                            <!-- Descripción -->
                            <div class="fum-docs-section">
                                <h4 class="fum-docs-section__title">
                                    <span class="dashicons dashicons-info"></span>
                                    <?php esc_html_e('Descripción', 'flavor-chat-ia'); ?>
                                </h4>
                                <p class="fum-docs-section__text" x-text="docsData?.descripcion"></p>
                            </div>

                            <!-- Características -->
                            <div class="fum-docs-section" x-show="docsData?.caracteristicas?.length > 0">
                                <h4 class="fum-docs-section__title">
                                    <span class="dashicons dashicons-star-filled"></span>
                                    <?php esc_html_e('Características', 'flavor-chat-ia'); ?>
                                </h4>
                                <ul class="fum-docs-list">
                                    <template x-for="item in docsData?.caracteristicas || []" :key="item">
                                        <li x-text="item"></li>
                                    </template>
                                </ul>
                            </div>

                            <!-- Casos de uso -->
                            <div class="fum-docs-section" x-show="docsData?.casos_uso?.length > 0">
                                <h4 class="fum-docs-section__title">
                                    <span class="dashicons dashicons-lightbulb"></span>
                                    <?php esc_html_e('Casos de uso', 'flavor-chat-ia'); ?>
                                </h4>
                                <ul class="fum-docs-list fum-docs-list--cases">
                                    <template x-for="item in docsData?.casos_uso || []" :key="item">
                                        <li x-text="item"></li>
                                    </template>
                                </ul>
                            </div>

                            <!-- Módulos relacionados -->
                            <div class="fum-docs-section" x-show="docsData?.modulos_relacionados?.length > 0">
                                <h4 class="fum-docs-section__title">
                                    <span class="dashicons dashicons-networking"></span>
                                    <?php esc_html_e('Módulos relacionados', 'flavor-chat-ia'); ?>
                                </h4>
                                <div class="fum-docs-tags">
                                    <template x-for="modulo in docsData?.modulos_relacionados || []" :key="modulo">
                                        <span class="fum-docs-tag" x-text="modulo"></span>
                                    </template>
                                </div>
                            </div>

                            <!-- Requisitos -->
                            <div class="fum-docs-section" x-show="docsData?.requisitos?.length > 0">
                                <h4 class="fum-docs-section__title">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Requisitos', 'flavor-chat-ia'); ?>
                                </h4>
                                <ul class="fum-docs-list fum-docs-list--requirements">
                                    <template x-for="req in docsData?.requisitos || []" :key="req">
                                        <li x-text="req"></li>
                                    </template>
                                </ul>
                            </div>

                            <!-- Tablas de base de datos -->
                            <div class="fum-docs-section" x-show="docsData?.tablas?.length > 0">
                                <h4 class="fum-docs-section__title">
                                    <span class="dashicons dashicons-database"></span>
                                    <?php esc_html_e('Base de datos', 'flavor-chat-ia'); ?>
                                </h4>
                                <p class="fum-docs-section__subtitle" x-show="docsData?.tabla_principal">
                                    <?php esc_html_e('Tabla principal:', 'flavor-chat-ia'); ?>
                                    <code x-text="docsData?.tabla_principal"></code>
                                </p>
                                <div class="fum-docs-tables" x-show="docsData?.tablas?.length > 1">
                                    <template x-for="tabla in docsData?.tablas || []" :key="tabla">
                                        <code class="fum-docs-table-name" x-text="tabla"></code>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Error state -->
                        <div class="fum-docs-error" x-show="!docsLoading && docsError">
                            <span class="dashicons dashicons-warning"></span>
                            <span x-text="docsError"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene los tipos de visibilidad
     */
    private function get_visibility_types() {
        return [
            'public' => __('Público', 'flavor-chat-ia'),
            'members_only' => __('Solo miembros', 'flavor-chat-ia'),
            'private' => __('Privado', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene las capacidades disponibles
     */
    private function get_available_capabilities() {
        return [
            'read' => __('Cualquier usuario', 'flavor-chat-ia'),
            'edit_posts' => __('Colaboradores+', 'flavor-chat-ia'),
            'publish_posts' => __('Autores+', 'flavor-chat-ia'),
            'edit_others_posts' => __('Editores+', 'flavor-chat-ia'),
            'manage_options' => __('Solo administradores', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene la etiqueta de visibilidad
     */
    private function get_visibility_label($visibility) {
        $labels = [
            'public' => __('Público', 'flavor-chat-ia'),
            'members_only' => __('Miembros', 'flavor-chat-ia'),
            'private' => __('Privado', 'flavor-chat-ia'),
        ];
        return $labels[$visibility] ?? $visibility;
    }

    /**
     * Obtiene el color de categoría
     */
    private function get_category_color($nombre_categoria) {
        $colores = [
            'Comercio' => '#3b82f6',
            'Comunidad' => '#ec4899',
            'Gobernanza' => '#6366f1',
            'Sostenibilidad' => '#22c55e',
            'Contenido' => '#f59e0b',
            'Economía' => '#0ea5e9',
            'Servicios' => '#8b5cf6',
            'Cultura' => '#f97316',
        ];
        return $colores[$nombre_categoria] ?? '#6b7280';
    }

    /**
     * Obtiene la URL de configuración del módulo
     */
    private function get_module_config_url($modulo_id) {
        $modulo_slug = str_replace('_', '-', $modulo_id);
        $admin_pages = [
            'grupos_consumo' => 'admin.php?page=flavor-gc-settings',
            'banco_tiempo' => 'admin.php?page=flavor-bt-settings',
            'eventos' => 'admin.php?page=flavor-eventos-settings',
            'reciclaje' => 'admin.php?page=flavor-reciclaje-settings',
            'espacios_comunes' => 'admin.php?page=flavor-ec-settings',
        ];

        if (isset($admin_pages[$modulo_id])) {
            return admin_url($admin_pages[$modulo_id]);
        }

        // Intentar página genérica
        $generic_page = admin_url("admin.php?page=flavor-{$modulo_slug}-settings");
        return null; // Solo retornar si existe la página
    }

    /**
     * AJAX: Toggle módulo
     */
    public function ajax_toggle_module() {
        check_ajax_referer('fum_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos suficientes', 'flavor-chat-ia')]);
        }

        $modulo_id = sanitize_key($_POST['module_id'] ?? '');
        $activate = filter_var($_POST['activate'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (empty($modulo_id)) {
            wp_send_json_error(['message' => __('ID de módulo inválido', 'flavor-chat-ia')]);
        }

        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        if ($activate) {
            if (!in_array($modulo_id, $modulos_activos)) {
                $modulos_activos[] = $modulo_id;
            }
        } else {
            $modulos_activos = array_values(array_diff($modulos_activos, [$modulo_id]));
        }

        $configuracion['active_modules'] = $modulos_activos;
        update_option('flavor_chat_ia_settings', $configuracion);

        // Disparar acción para que el módulo pueda hacer setup/teardown
        do_action('flavor_module_toggled', $modulo_id, $activate);

        wp_send_json_success([
            'message' => $activate
                ? __('Módulo activado', 'flavor-chat-ia')
                : __('Módulo desactivado', 'flavor-chat-ia'),
            'module_id' => $modulo_id,
            'is_active' => $activate,
        ]);
    }

    /**
     * AJAX: Guardar visibilidad
     */
    public function ajax_save_visibility() {
        check_ajax_referer('fum_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos suficientes', 'flavor-chat-ia')]);
        }

        $modulo_id = sanitize_key($_POST['module_id'] ?? '');
        $visibility = isset($_POST['visibility']) ? sanitize_key($_POST['visibility']) : null;
        $capability = isset($_POST['capability']) ? sanitize_key($_POST['capability']) : null;

        if (empty($modulo_id)) {
            wp_send_json_error(['message' => __('ID de módulo inválido', 'flavor-chat-ia')]);
        }

        // Guardar visibilidad si se proporciona
        if ($visibility !== null) {
            $visibilidades_validas = ['public', 'private', 'members_only'];
            if (in_array($visibility, $visibilidades_validas, true)) {
                $visibilidades = get_option('flavor_modules_visibility', []);
                $visibilidades[$modulo_id] = $visibility;
                update_option('flavor_modules_visibility', $visibilidades);
            }
        }

        // Guardar capacidad si se proporciona
        if ($capability !== null) {
            $capacidades = get_option('flavor_modules_capabilities', []);
            $capacidades[$modulo_id] = $capability;
            update_option('flavor_modules_capabilities', $capacidades);
        }

        // Limpiar caché de acceso si existe
        if (class_exists('Flavor_Module_Access_Control')) {
            Flavor_Module_Access_Control::get_instance()->limpiar_cache();
        }

        wp_send_json_success([
            'message' => __('Configuración guardada', 'flavor-chat-ia'),
            'module_id' => $modulo_id,
        ]);
    }

    /**
     * AJAX: Crear landing
     */
    public function ajax_create_landing() {
        check_ajax_referer('fum_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos suficientes', 'flavor-chat-ia')]);
        }

        $modulo_id = sanitize_key($_POST['module_id'] ?? '');

        if (empty($modulo_id)) {
            wp_send_json_error(['message' => __('ID de módulo inválido', 'flavor-chat-ia')]);
        }

        $modulo_slug = str_replace('_', '-', $modulo_id);

        // Verificar si ya existe
        $pagina_existente = get_page_by_path($modulo_slug);
        if ($pagina_existente) {
            wp_send_json_success([
                'message' => __('La landing ya existe', 'flavor-chat-ia'),
                'url' => get_permalink($pagina_existente->ID),
                'edit_url' => get_edit_post_link($pagina_existente->ID),
            ]);
        }

        // Obtener información del módulo
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos_registrados = $loader->get_registered_modules();
        $info_modulo = $modulos_registrados[$modulo_id] ?? null;

        $nombre_modulo = $info_modulo['name'] ?? ucfirst(str_replace('_', ' ', $modulo_id));

        // Crear la página
        $page_id = wp_insert_post([
            'post_title' => $nombre_modulo,
            'post_name' => $modulo_slug,
            'post_content' => sprintf('[flavor_landing module="%s"]', $modulo_slug),
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($page_id)) {
            wp_send_json_error(['message' => $page_id->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Landing creada correctamente', 'flavor-chat-ia'),
            'page_id' => $page_id,
            'url' => get_permalink($page_id),
            'edit_url' => get_edit_post_link($page_id),
        ]);
    }
}

// Inicializar
Flavor_Unified_Modules_View::get_instance();
