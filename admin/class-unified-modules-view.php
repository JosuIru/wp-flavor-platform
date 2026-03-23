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
        if (strpos($hook, 'flavor-module-dashboards') === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-unified-modules',
            FLAVOR_CHAT_IA_URL . 'admin/css/unified-modules.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // Alpine ya se carga en class-app-profile-admin.php con handle 'alpinejs'
        // Solo necesitamos cargar nuestro script después de Alpine
        wp_enqueue_script(
            'flavor-unified-modules',
            FLAVOR_CHAT_IA_URL . 'admin/js/unified-modules.js',
            ['jquery', 'alpinejs'],
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
     * Fuerza la carga de assets cuando render() se llama después de admin_enqueue_scripts
     * Esto ocurre cuando la clase se inicializa durante el renderizado de otra página
     */
    private function force_load_assets() {
        // Encolar y imprimir CSS inmediatamente
        wp_enqueue_style(
            'flavor-unified-modules',
            FLAVOR_CHAT_IA_URL . 'admin/css/unified-modules.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );
        wp_print_styles(['flavor-unified-modules']);

        // Datos para JavaScript
        $fum_data = [
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
        ];

        // Imprimir fumData y la función unifiedModulesState INLINE
        // Esto garantiza que esté disponible ANTES de que Alpine procese el DOM
        ?>
        <script>
        var fumData = <?php echo wp_json_encode($fum_data); ?>;

        // Definir la función global para Alpine
        window.unifiedModulesState = function() {
            return {
                // Estado de filtros
                searchQuery: '',
                activeCategory: 'all',
                filterStatus: 'all',
                filterVisibility: 'all',
                filterRole: 'all',

                // Estado de operaciones
                savingModules: [],
                hasVisibleModules: true,

                // Estado del modal de documentación
                docsModalOpen: false,
                docsModuleId: '',
                docsModuleName: '',
                docsLoading: false,
                docsData: null,
                docsError: null,

                // Inicialización
                init() {
                    this.updateVisibleCount();
                },

                // Filtrar módulos
                shouldShowModule(moduleId, moduleName, category, isActive, visibility, role) {
                    if (this.activeCategory !== 'all' && category !== this.activeCategory) return false;
                    if (this.searchQuery) {
                        var query = this.searchQuery.toLowerCase();
                        if (!moduleName.toLowerCase().includes(query) && !moduleId.toLowerCase().includes(query)) return false;
                    }
                    if (this.filterStatus === 'active' && !isActive) return false;
                    if (this.filterStatus === 'inactive' && isActive) return false;
                    if (this.filterVisibility !== 'all' && visibility !== this.filterVisibility) return false;
                    if (this.filterRole !== 'all' && role !== this.filterRole) return false;
                    return true;
                },

                setCategory(category) {
                    this.activeCategory = category;
                    this.updateVisibleCount();
                },

                filterModules() {
                    this.updateVisibleCount();
                },

                updateVisibleCount() {
                    var self = this;
                    this.$nextTick(function() {
                        var visibleCards = document.querySelectorAll('.fum-module-card:not([style*="display: none"])');
                        self.hasVisibleModules = visibleCards.length > 0;
                    });
                },

                async toggleModule(moduleId, activate) {
                    this.savingModules.push(moduleId);
                    try {
                        var response = await fetch(fumData.ajaxUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'fum_toggle_module',
                                nonce: fumData.nonce,
                                module_id: moduleId,
                                activate: activate ? '1' : '0',
                            }),
                        });
                        var data = await response.json();
                        if (data.success) {
                            var card = document.querySelector('.fum-module-card[data-module-id="' + moduleId + '"]');
                            if (card) {
                                card.classList.toggle('is-active', activate);
                                card.classList.toggle('is-inactive', !activate);
                            }
                        }
                    } catch (error) {
                        console.error('Error toggling module:', error);
                    } finally {
                        this.savingModules = this.savingModules.filter(function(id) { return id !== moduleId; });
                    }
                },

                async activatePack(moduleIds) {
                    if (!Array.isArray(moduleIds) || moduleIds.length === 0) {
                        return;
                    }

                    var activationOrder = this.resolveActivationOrder(moduleIds);
                    for (var i = 0; i < activationOrder.length; i++) {
                        await this.toggleModule(activationOrder[i], true);
                    }

                    window.location.reload();
                },

                getModuleDependencies(moduleId) {
                    var card = document.querySelector('.fum-module-card[data-module-id="' + moduleId + '"]');
                    if (!card) {
                        return [];
                    }

                    var raw = card.getAttribute('data-depends-on') || '[]';
                    try {
                        var parsed = JSON.parse(raw);
                        return Array.isArray(parsed) ? parsed : [];
                    } catch (error) {
                        return [];
                    }
                },

                resolveActivationOrder(moduleIds) {
                    var visited = new Set();
                    var ordered = [];
                    var self = this;

                    function visit(moduleId) {
                        if (!moduleId || visited.has(moduleId)) {
                            return;
                        }

                        visited.add(moduleId);
                        var dependencies = self.getModuleDependencies(moduleId);
                        for (var i = 0; i < dependencies.length; i++) {
                            visit(dependencies[i]);
                        }
                        ordered.push(moduleId);
                    }

                    for (var i = 0; i < moduleIds.length; i++) {
                        visit(moduleIds[i]);
                    }

                    return ordered;
                },

                async saveVisibility(moduleId, visibility, capability) {
                    this.savingModules.push(moduleId);
                    try {
                        var params = { action: 'fum_save_visibility', nonce: fumData.nonce, module_id: moduleId };
                        if (visibility !== null) params.visibility = visibility;
                        if (capability !== null) params.capability = capability;
                        await fetch(fumData.ajaxUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams(params),
                        });
                    } catch (error) {
                        console.error('Error saving visibility:', error);
                    } finally {
                        var self = this;
                        setTimeout(function() {
                            self.savingModules = self.savingModules.filter(function(id) { return id !== moduleId; });
                        }, 500);
                    }
                },

                async createLanding(moduleId) {
                    try {
                        var response = await fetch(fumData.ajaxUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'fum_create_landing',
                                nonce: fumData.nonce,
                                module_id: moduleId,
                            }),
                        });
                        var data = await response.json();
                        if (data.success) {
                            setTimeout(function() { window.location.reload(); }, 1000);
                        }
                    } catch (error) {
                        console.error('Error creating landing:', error);
                    }
                },

                async openDocs(moduleId, moduleName) {
                    this.docsModalOpen = true;
                    this.docsModuleId = moduleId;
                    this.docsModuleName = moduleName;
                    this.docsLoading = true;
                    this.docsData = null;
                    this.docsError = null;
                    try {
                        var response = await fetch(fumData.restUrl + 'modules/docs/' + moduleId, {
                            method: 'GET',
                            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': fumData.restNonce },
                        });
                        if (!response.ok) {
                            this.docsError = response.status === 404 ? fumData.i18n.docsNotFound : fumData.i18n.docsError;
                            return;
                        }
                        var data = await response.json();
                        if (data && data.success === false) {
                            this.docsError = data.error || fumData.i18n.docsNotFound;
                            return;
                        }
                        this.docsData = data.data || data;
                    } catch (error) {
                        this.docsError = fumData.i18n.docsError;
                    } finally {
                        this.docsLoading = false;
                    }
                },

                getVisibilityLabel(visibility) {
                    var labels = { 'public': 'Público', 'members_only': 'Miembros', 'private': 'Privado' };
                    return labels[visibility] || visibility;
                }
            };
        };

        // Registrar con Alpine si está disponible
        if (typeof Alpine !== 'undefined') {
            Alpine.data('unifiedModulesState', window.unifiedModulesState);
        }
        document.addEventListener('alpine:init', function() {
            if (typeof Alpine !== 'undefined') {
                Alpine.data('unifiedModulesState', window.unifiedModulesState);
            }
        });
        </script>
        <?php
    }

    /**
     * Renderiza la vista unificada de módulos
     */
    public function render() {
        // Forzar carga de scripts ya que admin_enqueue_scripts puede haber pasado
        $this->force_load_assets();

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos_registrados = $loader->get_registered_modules();
        $gestor_perfiles = Flavor_App_Profiles::get_instance();
        $categorias_modulos = $gestor_perfiles->obtener_categorias_modulos();

        $modulos_categorizados = [];
        foreach ($categorias_modulos as $categoria_datos) {
            foreach ($categoria_datos['modulos'] as $modulo_id) {
                $modulos_categorizados[$modulo_id] = true;
            }
        }

        $modulos_sin_categoria = array_diff(array_keys($modulos_registrados), array_keys($modulos_categorizados));
        if (!empty($modulos_sin_categoria)) {
            $categorias_modulos['otros'] = [
                'nombre' => __('Otros', 'flavor-chat-ia'),
                'icono' => 'dashicons-screenoptions',
                'modulos' => array_values($modulos_sin_categoria),
            ];
        }

        // CRÍTICO: Limpiar caché y leer directamente de BD
        wp_cache_delete('flavor_chat_ia_settings', 'options');
        wp_cache_delete('alloptions', 'options');

        global $wpdb;
        $configuracion_raw = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                'flavor_chat_ia_settings'
            )
        );
        $configuracion = $configuracion_raw ? maybe_unserialize($configuracion_raw) : [];
        if (!is_array($configuracion)) {
            $configuracion = [];
        }

        $modulos_activos = isset($configuracion['active_modules']) && is_array($configuracion['active_modules'])
            ? $configuracion['active_modules']
            : [];

        // Debug: log de módulos al renderizar
        error_log("FUM Render (BD directa): active_modules=" . implode(',', $modulos_activos));
        error_log("FUM Render: modulos_opcionales=" . implode(',', $configuracion['modulos_opcionales_activos'] ?? []));
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
        $recommended_packs = $this->get_recommended_ecosystem_packs($modulos_activos, $modulos_registrados);
        $active_ecosystem_summary = $this->get_active_ecosystem_summary($modulos_registrados, $modulos_activos);
        $ecosystem_gap_summary = $this->get_ecosystem_gap_summary($modulos_registrados, $modulos_activos);
        $ecosystem_map = $this->get_ecosystem_map($modulos_registrados, $modulos_activos);
        ?>
        <div class="flavor-unified-modules" x-data="unifiedModulesState()">
            <!-- Header -->
            <div class="fum-header">
                <div>
                    <h2 class="fum-header__title"><?php esc_html_e('Gestión de Módulos Web', 'flavor-chat-ia'); ?></h2>
                    <p class="fum-header__description">
                        <?php esc_html_e('Activa módulos del portal web, configura permisos de acceso y gestiona landings desde una sola vista.', 'flavor-chat-ia'); ?>
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

            <?php if (!empty($recommended_packs)) : ?>
            <div class="fum-packs">
                <?php foreach ($recommended_packs as $pack) : ?>
                <div class="fum-pack-card">
                    <div class="fum-pack-card__header">
                        <div>
                            <h3 class="fum-pack-card__title"><?php echo esc_html($pack['title']); ?></h3>
                            <p class="fum-pack-card__desc"><?php echo esc_html($pack['description']); ?></p>
                            <?php if (!empty($pack['hint'])) : ?>
                            <p class="fum-pack-card__hint"><?php echo esc_html($pack['hint']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="fum-pack-card__metrics">
                            <span class="fum-pack-card__progress"><?php echo esc_html($pack['active_count'] . '/' . $pack['total_count']); ?></span>
                            <?php if (!empty($pack['badge_label'])) : ?>
                            <span class="fum-pack-card__recommended fum-pack-card__recommended--<?php echo esc_attr($pack['badge_variant']); ?>">
                                <?php echo esc_html($pack['badge_label']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="fum-pack-card__tags">
                        <?php foreach ($pack['modules'] as $module_id) : ?>
                        <span class="fum-pack-card__tag <?php echo in_array($module_id, $modulos_activos, true) ? 'is-active' : ''; ?>">
                            <?php echo esc_html($this->format_module_label($module_id)); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="fum-pack-card__actions">
                        <?php if (!empty($pack['missing_modules'])) : ?>
                        <button type="button"
                                class="fum-btn fum-btn--primary fum-btn--small"
                                @click='activatePack(<?php echo wp_json_encode(array_values($pack['missing_modules'])); ?>)'>
                            <?php esc_html_e('Activar faltantes', 'flavor-chat-ia'); ?>
                        </button>
                        <?php else : ?>
                        <span class="fum-pack-card__ready"><?php esc_html_e('Pack completo', 'flavor-chat-ia'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($active_ecosystem_summary)) : ?>
            <div class="fum-ecosystem-summary">
                <?php foreach ($active_ecosystem_summary as $summary) : ?>
                <div class="fum-ecosystem-summary__card">
                    <div class="fum-ecosystem-summary__count"><?php echo esc_html($summary['count']); ?></div>
                    <div class="fum-ecosystem-summary__title"><?php echo esc_html($summary['title']); ?></div>
                    <?php if (!empty($summary['modules'])) : ?>
                    <div class="fum-ecosystem-summary__tags">
                        <?php foreach ($summary['modules'] as $module_name) : ?>
                        <span class="fum-ecosystem-summary__tag"><?php echo esc_html($module_name); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($ecosystem_gap_summary)) : ?>
            <div class="fum-ecosystem-gaps">
                <?php foreach ($ecosystem_gap_summary as $gap) : ?>
                <div class="fum-ecosystem-gaps__card">
                    <div class="fum-ecosystem-gaps__header">
                        <div class="fum-ecosystem-gaps__title"><?php echo esc_html($gap['title']); ?></div>
                        <div class="fum-ecosystem-gaps__count"><?php echo esc_html($gap['count']); ?></div>
                    </div>
                    <p class="fum-ecosystem-gaps__description"><?php echo esc_html($gap['description']); ?></p>
                    <?php if (!empty($gap['modules'])) : ?>
                    <div class="fum-ecosystem-gaps__tags">
                        <?php foreach ($gap['modules'] as $module) : ?>
                        <span class="fum-ecosystem-gaps__tag"><?php echo esc_html($module['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($gap['missing_ids'])) : ?>
                    <div class="fum-ecosystem-gaps__actions">
                        <button type="button"
                                class="fum-btn fum-btn--secondary fum-btn--small"
                                @click='activatePack(<?php echo wp_json_encode(array_values($gap['missing_ids'])); ?>)'>
                            <?php esc_html_e('Activar sugeridos', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($ecosystem_map)) : ?>
            <div class="fum-ecosystem-map">
                <div class="fum-ecosystem-map__header">
                    <h3 class="fum-ecosystem-map__title"><?php esc_html_e('Mapa del ecosistema activo', 'flavor-chat-ia'); ?></h3>
                    <p class="fum-ecosystem-map__description">
                        <?php esc_html_e('Muestra como se conectan los modulos activos con otras piezas ya activas o sugeridas del ecosistema.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <div class="fum-ecosystem-map__grid">
                    <?php foreach ($ecosystem_map as $node) : ?>
                    <div class="fum-ecosystem-node">
                        <div class="fum-ecosystem-node__head">
                            <div>
                                <div class="fum-ecosystem-node__name"><?php echo esc_html($node['name']); ?></div>
                                <div class="fum-ecosystem-node__role"><?php echo esc_html($node['role_label']); ?></div>
                            </div>
                            <span class="fum-ecosystem-node__badge"><?php echo esc_html($node['connection_count']); ?></span>
                        </div>
                        <?php foreach ($node['groups'] as $group) : ?>
                        <div class="fum-ecosystem-node__group">
                            <div class="fum-ecosystem-node__group-title"><?php echo esc_html($group['label']); ?></div>
                            <div class="fum-ecosystem-node__links">
                                <?php foreach ($group['modules'] as $linked_module) : ?>
                                <span class="fum-ecosystem-node__link <?php echo !empty($linked_module['is_active']) ? 'is-active' : 'is-suggested'; ?>">
                                    <?php echo esc_html($linked_module['name']); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

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
                <select class="fum-filter-select" x-model="filterRole" @change="filterModules()">
                    <option value="all"><?php esc_html_e('Todos los roles', 'flavor-chat-ia'); ?></option>
                    <option value="base"><?php esc_html_e('Base', 'flavor-chat-ia'); ?></option>
                    <option value="vertical"><?php esc_html_e('Vertical', 'flavor-chat-ia'); ?></option>
                    <option value="transversal"><?php esc_html_e('Transversal', 'flavor-chat-ia'); ?></option>
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

                        $nombre_modulo = trim((string) ($info_modulo['name'] ?? ''));
                        if ($nombre_modulo === '') {
                            $nombre_modulo = ucwords(str_replace(['_', '-'], ' ', $modulo_id));
                        }

                        $descripcion_modulo = trim((string) ($info_modulo['description'] ?? ''));
                        if ($descripcion_modulo === '') {
                            $descripcion_modulo = sprintf(
                                __('Módulo de %s disponible para activar y configurar.', 'flavor-chat-ia'),
                                $categoria_datos['nombre']
                            );
                        }

                        $icono_modulo = $info_modulo['icon'] ?? 'dashicons-admin-plugins';
                        $ecosystem = is_array($info_modulo['ecosystem'] ?? null) ? $info_modulo['ecosystem'] : [];
                        $module_role = $ecosystem['module_role'] ?? 'vertical';
                        $display_module_role = $this->get_display_ecosystem_role($ecosystem);
                        $depends_on = array_values(array_filter((array) ($ecosystem['depends_on'] ?? [])));
                        $supports_modules = array_values(array_filter((array) ($ecosystem['supports_modules'] ?? [])));
                        $measures_modules = array_values(array_filter((array) ($ecosystem['measures_modules'] ?? [])));
                        $governs_modules = array_values(array_filter((array) ($ecosystem['governs_modules'] ?? [])));
                        $teaches_modules = array_values(array_filter((array) ($ecosystem['teaches_modules'] ?? [])));
                        $base_for_modules = array_values(array_filter((array) ($ecosystem['base_for_modules'] ?? [])));
                        $missing_dependencies = array_values(array_filter($depends_on, static function ($dependency) use ($modulos_activos) {
                            return !in_array($dependency, $modulos_activos, true);
                        }));

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
                        $categoria_color = $info_modulo['color'] ?? $this->get_category_color($categoria_datos['nombre']);
                    ?>
                    <div class="fum-module-card <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>"
                         x-show="shouldShowModule('<?php echo esc_js($modulo_id); ?>', '<?php echo esc_js($nombre_modulo); ?>', '<?php echo esc_js($categoria_id); ?>', <?php echo $is_active ? 'true' : 'false'; ?>, '<?php echo esc_js($visibility); ?>', '<?php echo esc_js($module_role); ?>')"
                         x-transition
                         data-module-id="<?php echo esc_attr($modulo_id); ?>"
                         data-depends-on="<?php echo esc_attr(wp_json_encode(array_values($depends_on))); ?>"
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
                                    <span class="fum-badge fum-badge--role fum-badge--role-<?php echo esc_attr($display_module_role); ?>">
                                        <?php echo esc_html($this->get_ecosystem_role_label($display_module_role)); ?>
                                    </span>
                                    <span class="fum-badge fum-badge--<?php echo esc_attr($visibility); ?>">
                                        <?php echo esc_html($this->get_visibility_label($visibility)); ?>
                                    </span>
                                </h4>
                                <p class="fum-card__desc"><?php echo esc_html($descripcion_modulo); ?></p>
                                <?php if ($display_module_role === 'base-standalone') : ?>
                                    <p class="fum-card__role-note"><?php esc_html_e('Espacio base útil para gestión propia, pero sin satélites ecosistémicos declarados.', 'flavor-chat-ia'); ?></p>
                                <?php endif; ?>
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
                            <?php if (!empty($missing_dependencies)) : ?>
                            <div class="fum-dependency-warning">
                                <span class="dashicons dashicons-warning"></span>
                                <span>
                                    <?php
                                    printf(
                                        esc_html__('Faltan dependencias activas: %s', 'flavor-chat-ia'),
                                        esc_html(implode(', ', array_map([$this, 'format_module_label'], $missing_dependencies)))
                                    );
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($depends_on) || !empty($supports_modules) || !empty($measures_modules) || !empty($governs_modules) || !empty($teaches_modules) || !empty($base_for_modules)) : ?>
                            <div class="fum-ecosystem-meta">
                                <?php foreach ($this->get_ecosystem_relation_groups([
                                    'depends_on' => $depends_on,
                                    'supports_modules' => $supports_modules,
                                    'measures_modules' => $measures_modules,
                                    'governs_modules' => $governs_modules,
                                    'teaches_modules' => $teaches_modules,
                                    'base_for_modules' => $base_for_modules,
                                ]) as $group) : ?>
                                <div class="fum-ecosystem-group">
                                    <span class="fum-ecosystem-group__label"><?php echo esc_html($group['label']); ?></span>
                                    <div class="fum-ecosystem-tags">
                                        <?php foreach ($group['items'] as $item) : ?>
                                        <span class="fum-ecosystem-tag"><?php echo esc_html($this->format_module_label($item)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
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
                            <?php if ($is_active) :
                                $admin_pages = $this->get_module_admin_pages($modulo_id);
                                $dashboard_url = $this->get_module_dashboard_url($modulo_id);
                                $portal_url = $this->get_module_portal_url($modulo_id);
                                if (!empty($admin_pages)) :
                            ?>
                                <!-- Menú Administrar con dropdown -->
                                <div class="fum-admin-menu" x-data="{ open: false }">
                                    <a href="<?php echo esc_url($dashboard_url); ?>"
                                       class="fum-btn fum-btn--primary fum-btn--flex">
                                        <span class="dashicons dashicons-dashboard"></span>
                                        <?php esc_html_e('Administrar', 'flavor-chat-ia'); ?>
                                    </a>
                                    <?php if (count($admin_pages) > 1) : ?>
                                    <button type="button"
                                            class="fum-btn fum-btn--primary fum-btn--small fum-admin-menu__toggle"
                                            @click="open = !open"
                                            @click.outside="open = false">
                                        <span class="dashicons dashicons-arrow-down-alt2" :class="{ 'rotate-180': open }"></span>
                                    </button>
                                    <div class="fum-admin-menu__dropdown" x-show="open" x-transition x-cloak>
                                        <?php foreach ($admin_pages as $pagina) : ?>
                                        <a href="<?php echo esc_url($pagina['url']); ?>" class="fum-admin-menu__item">
                                            <span class="dashicons <?php echo esc_attr($pagina['icon']); ?>"></span>
                                            <?php echo esc_html($pagina['titulo']); ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($portal_url) : ?>
                                <a href="<?php echo esc_url($portal_url); ?>"
                                   target="_blank"
                                   class="fum-btn fum-btn--primary fum-btn--flex">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php esc_html_e('Abrir portal', 'flavor-chat-ia'); ?>
                                </a>
                            <?php endif; endif; ?>

                            <?php if ($has_landing) : ?>
                                <a href="<?php echo esc_url($landing_url); ?>"
                                   target="_blank"
                                   class="fum-btn fum-btn--secondary fum-btn--small"
                                   title="<?php esc_attr_e('Ver Landing', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                                <a href="<?php echo esc_url(get_edit_post_link($pagina_landing->ID)); ?>"
                                   class="fum-btn fum-btn--secondary fum-btn--small"
                                   title="<?php esc_attr_e('Editar Landing', 'flavor-chat-ia'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                            <?php else : ?>
                                <button type="button"
                                        class="fum-btn fum-btn--secondary fum-btn--small"
                                        title="<?php esc_attr_e('Crear Landing', 'flavor-chat-ia'); ?>"
                                        @click="createLanding('<?php echo esc_js($modulo_id); ?>')">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                </button>
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

    private function get_ecosystem_role_label($role) {
        $labels = [
            'base' => __('Base', 'flavor-chat-ia'),
            'base-standalone' => __('Base local', 'flavor-chat-ia'),
            'vertical' => __('Vertical', 'flavor-chat-ia'),
            'transversal' => __('Transversal', 'flavor-chat-ia'),
        ];

        return $labels[$role] ?? ucfirst((string) $role);
    }

    private function get_display_ecosystem_role($ecosystem) {
        return (string) ($ecosystem['display_role'] ?? $ecosystem['module_role'] ?? 'vertical');
    }

    private function get_ecosystem_relation_groups($relations) {
        $labels = [
            'depends_on' => __('Depende de', 'flavor-chat-ia'),
            'supports_modules' => __('Soporta', 'flavor-chat-ia'),
            'measures_modules' => __('Mide', 'flavor-chat-ia'),
            'governs_modules' => __('Gobierna', 'flavor-chat-ia'),
            'teaches_modules' => __('Enseña', 'flavor-chat-ia'),
            'base_for_modules' => __('Base de', 'flavor-chat-ia'),
        ];

        $groups = [];
        foreach ($labels as $key => $label) {
            $items = array_values(array_filter((array) ($relations[$key] ?? [])));
            if (empty($items)) {
                continue;
            }

            $groups[] = [
                'label' => $label,
                'items' => $items,
            ];
        }

        return $groups;
    }

    private function format_module_label($module_id) {
        return ucwords(str_replace(['_', '-'], ' ', (string) $module_id));
    }

    private function get_recommended_ecosystem_packs($active_modules, $registered_modules = []) {
        $packs = [
            [
                'title' => __('Autosuficiencia local', 'flavor-chat-ia'),
                'description' => __('Combina comunidad, energía, impacto y consumo local para construir resiliencia territorial.', 'flavor-chat-ia'),
                'modules' => ['comunidades', 'energia_comunitaria', 'huella_ecologica', 'grupos_consumo'],
            ],
            [
                'title' => __('Red de cuidados', 'flavor-chat-ia'),
                'description' => __('Activa ayuda mutua, tiempo compartido y tejido social de proximidad.', 'flavor-chat-ia'),
                'modules' => ['comunidades', 'ayuda_vecinal', 'banco_tiempo', 'eventos'],
            ],
            [
                'title' => __('Mercado regenerativo', 'flavor-chat-ia'),
                'description' => __('Articula consumo local, transparencia e impacto para economía circular y cooperativa.', 'flavor-chat-ia'),
                'modules' => ['grupos_consumo', 'marketplace', 'transparencia', 'economia_suficiencia'],
            ],
        ];

        $packs = array_merge($packs, $this->build_dynamic_ecosystem_packs($active_modules, $registered_modules));
        $packs = $this->deduplicate_ecosystem_packs($packs);

        foreach ($packs as &$pack) {
            $pack['active_count'] = count(array_intersect($pack['modules'], $active_modules));
            $pack['total_count'] = count($pack['modules']);
            $pack['missing_modules'] = array_values(array_diff($pack['modules'], $active_modules));
            $pack['overlap_modules'] = array_values(array_intersect($pack['modules'], $active_modules));
            $pack['score'] = $pack['active_count'] / max(1, $pack['total_count']);
            $pack['is_recommended'] = $pack['active_count'] > 0 && !empty($pack['missing_modules']);
            $pack['pack_state'] = $this->get_pack_state($pack);
            $pack['badge_label'] = $this->get_pack_badge_label($pack);
            $pack['badge_variant'] = $this->get_pack_badge_variant($pack);
            $pack['hint'] = $this->get_pack_hint($pack);
        }
        unset($pack);

        usort($packs, function ($a, $b) {
            $priority_map = [
                'suggested' => 0,
                'starter' => 1,
                'complete' => 2,
            ];

            $a_priority = $priority_map[$a['pack_state']] ?? 99;
            $b_priority = $priority_map[$b['pack_state']] ?? 99;
            if ($a_priority !== $b_priority) {
                return $a_priority <=> $b_priority;
            }

            if ($a['score'] !== $b['score']) {
                return $a['score'] > $b['score'] ? -1 : 1;
            }

            if (count($a['missing_modules']) !== count($b['missing_modules'])) {
                return count($a['missing_modules']) <=> count($b['missing_modules']);
            }

            return strcmp($a['title'], $b['title']);
        });

        return array_slice($packs, 0, 6);
    }

    private function build_dynamic_ecosystem_packs($active_modules, $registered_modules) {
        $packs = [];
        $relation_keys = [
            'depends_on',
            'supports_modules',
            'measures_modules',
            'governs_modules',
            'teaches_modules',
            'base_for_modules',
        ];

        foreach ($active_modules as $module_id) {
            $module = $registered_modules[$module_id] ?? null;
            if (!$module) {
                continue;
            }

            $ecosystem = is_array($module['ecosystem'] ?? null) ? $module['ecosystem'] : [];
            $related_modules = [];
            $used_relations = [];

            foreach ($relation_keys as $relation_key) {
                $relation_modules = array_values(array_filter((array) ($ecosystem[$relation_key] ?? [])));
                if (empty($relation_modules)) {
                    continue;
                }

                $related_modules = array_merge($related_modules, $relation_modules);
                $used_relations[] = $relation_key;
            }

            $related_modules = array_values(array_unique(array_filter($related_modules, function ($related_module_id) use ($registered_modules, $module_id) {
                return $related_module_id !== $module_id && isset($registered_modules[$related_module_id]);
            })));

            if (empty($related_modules)) {
                continue;
            }

            $module_name = $this->get_registered_module_name($module_id, $registered_modules);
            $pack_modules = array_values(array_unique(array_merge([$module_id], $related_modules)));
            $pack_modules = array_slice($pack_modules, 0, 6);

            if (count($pack_modules) < 2) {
                continue;
            }

            $packs[] = [
                'title' => sprintf(
                    __('Expandir %s', 'flavor-chat-ia'),
                    $module_name
                ),
                'description' => $this->build_dynamic_pack_description($module_name, $ecosystem, $used_relations),
                'modules' => $pack_modules,
                'source' => 'dynamic',
                'anchor_module' => $module_id,
            ];
        }

        return $packs;
    }

    private function deduplicate_ecosystem_packs($packs) {
        $unique_packs = [];
        $seen = [];

        foreach ($packs as $pack) {
            $modules = array_values(array_unique(array_filter((array) ($pack['modules'] ?? []))));
            $signature_modules = $modules;
            sort($signature_modules);
            $signature = implode('|', $signature_modules);
            if ($signature === '' || isset($seen[$signature])) {
                continue;
            }

            $pack['modules'] = $modules;
            $unique_packs[] = $pack;
            $seen[$signature] = true;
        }

        return $unique_packs;
    }

    private function build_dynamic_pack_description($module_name, $ecosystem, $used_relations) {
        $role = $ecosystem['module_role'] ?? 'vertical';
        $relation_labels = [
            'depends_on' => __('dependencias', 'flavor-chat-ia'),
            'supports_modules' => __('soportes', 'flavor-chat-ia'),
            'measures_modules' => __('metricas', 'flavor-chat-ia'),
            'governs_modules' => __('gobernanza', 'flavor-chat-ia'),
            'teaches_modules' => __('aprendizaje', 'flavor-chat-ia'),
            'base_for_modules' => __('extensiones base', 'flavor-chat-ia'),
        ];

        $used_labels = array_values(array_unique(array_filter(array_map(function ($relation_key) use ($relation_labels) {
            return $relation_labels[$relation_key] ?? null;
        }, $used_relations))));

        $relations_text = implode(', ', array_slice($used_labels, 0, 3));

        switch ($role) {
            case 'base':
                return sprintf(
                    __('Despliega capacidades alrededor de %1$s conectando %2$s.', 'flavor-chat-ia'),
                    $module_name,
                    $relations_text !== '' ? $relations_text : __('modulos relacionados', 'flavor-chat-ia')
                );
            case 'transversal':
                return sprintf(
                    __('Amplia %1$s como capa transversal para reforzar %2$s.', 'flavor-chat-ia'),
                    $module_name,
                    $relations_text !== '' ? $relations_text : __('otros modulos del ecosistema', 'flavor-chat-ia')
                );
            case 'vertical':
            default:
                return sprintf(
                    __('Extiende %1$s con %2$s para cerrar mejor el flujo operativo.', 'flavor-chat-ia'),
                    $module_name,
                    $relations_text !== '' ? $relations_text : __('modulos complementarios', 'flavor-chat-ia')
                );
        }
    }

    private function get_registered_module_name($module_id, $registered_modules) {
        $module = $registered_modules[$module_id] ?? null;
        if (!$module) {
            return $this->format_module_label($module_id);
        }

        $name = trim((string) ($module['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return $this->format_module_label($module_id);
    }

    private function get_pack_state($pack) {
        if (empty($pack['missing_modules'])) {
            return 'complete';
        }

        if (!empty($pack['overlap_modules'])) {
            return 'suggested';
        }

        return 'starter';
    }

    private function get_pack_badge_label($pack) {
        switch ($pack['pack_state']) {
            case 'complete':
                return __('Activo', 'flavor-chat-ia');
            case 'suggested':
                return __('Siguiente paso', 'flavor-chat-ia');
            case 'starter':
            default:
                return __('Pack base', 'flavor-chat-ia');
        }
    }

    private function get_pack_badge_variant($pack) {
        switch ($pack['pack_state']) {
            case 'complete':
                return 'complete';
            case 'suggested':
                return 'suggested';
            case 'starter':
            default:
                return 'starter';
        }
    }

    private function get_pack_hint($pack) {
        if ($pack['pack_state'] === 'suggested' && !empty($pack['overlap_modules'])) {
            $names = array_map([$this, 'format_module_label'], array_slice($pack['overlap_modules'], 0, 2));
            return sprintf(
                __('Ya tienes %1$d/%2$d modulos activos. Encaja con: %3$s', 'flavor-chat-ia'),
                $pack['active_count'],
                $pack['total_count'],
                implode(', ', $names)
            );
        }

        if ($pack['pack_state'] === 'complete') {
            return __('Ya tienes este pack completo y operativo.', 'flavor-chat-ia');
        }

        return __('Buen punto de partida si quieres activar esta capacidad del ecosistema desde cero.', 'flavor-chat-ia');
    }

    private function get_active_ecosystem_summary($registered_modules, $active_modules) {
        $summary = [
            'base' => [
                'title' => __('Bases activas', 'flavor-chat-ia'),
                'modules' => [],
            ],
            'vertical' => [
                'title' => __('Verticales activos', 'flavor-chat-ia'),
                'modules' => [],
            ],
            'transversal' => [
                'title' => __('Transversales activos', 'flavor-chat-ia'),
                'modules' => [],
            ],
        ];

        foreach ($active_modules as $module_id) {
            $module = $registered_modules[$module_id] ?? null;
            if (!$module) {
                continue;
            }

            $role = $module['ecosystem']['module_role'] ?? 'vertical';
            if (!isset($summary[$role])) {
                continue;
            }

            $name = trim((string) ($module['name'] ?? ''));
            if ($name === '') {
                $name = $this->format_module_label($module_id);
            }

            $summary[$role]['modules'][] = $name;
        }

        foreach ($summary as $role => &$data) {
            $data['modules'] = array_values(array_slice($data['modules'], 0, 6));
            $data['count'] = count(array_filter($active_modules, function ($module_id) use ($registered_modules, $role) {
                return (($registered_modules[$module_id]['ecosystem']['module_role'] ?? 'vertical') === $role);
            }));
        }

        return array_values($summary);
    }

    private function get_ecosystem_gap_summary($registered_modules, $active_modules) {
        $groups = [
            'dependencies' => [
                'title' => __('Dependencias faltantes', 'flavor-chat-ia'),
                'description' => __('Modulos necesarios para que algunas piezas activas funcionen con mejor coherencia.', 'flavor-chat-ia'),
                'modules' => [],
            ],
            'extensions' => [
                'title' => __('Extensiones relacionadas', 'flavor-chat-ia'),
                'description' => __('Modulos conectados a lo que ya tienes activo y que ampliarían el ecosistema.', 'flavor-chat-ia'),
                'modules' => [],
            ],
            'transversal' => [
                'title' => __('Capas transversales ausentes', 'flavor-chat-ia'),
                'description' => __('Gobernanza, metricas o aprendizaje que completarían mejor las capacidades actuales.', 'flavor-chat-ia'),
                'modules' => [],
            ],
        ];

        foreach ($active_modules as $module_id) {
            $module = $registered_modules[$module_id] ?? null;
            if (!$module) {
                continue;
            }

            $ecosystem = is_array($module['ecosystem'] ?? null) ? $module['ecosystem'] : [];

            foreach ((array) ($ecosystem['depends_on'] ?? []) as $dependency_id) {
                $this->add_gap_module($groups['dependencies']['modules'], $dependency_id, $registered_modules, $active_modules);
            }

            foreach (['supports_modules', 'base_for_modules'] as $relation_key) {
                foreach ((array) ($ecosystem[$relation_key] ?? []) as $related_id) {
                    $this->add_gap_module($groups['extensions']['modules'], $related_id, $registered_modules, $active_modules);
                }
            }

            foreach (['measures_modules', 'governs_modules', 'teaches_modules'] as $relation_key) {
                foreach ((array) ($ecosystem[$relation_key] ?? []) as $related_id) {
                    $this->add_gap_module($groups['transversal']['modules'], $related_id, $registered_modules, $active_modules);
                }
            }
        }

        $result = [];
        foreach ($groups as $group) {
            if (empty($group['modules'])) {
                continue;
            }

            $modules = array_values($group['modules']);
            usort($modules, function ($a, $b) {
                if (($a['priority'] ?? 0) === ($b['priority'] ?? 0)) {
                    return strcmp($a['name'], $b['name']);
                }

                return ($a['priority'] ?? 0) > ($b['priority'] ?? 0) ? -1 : 1;
            });

            $result[] = [
                'title' => $group['title'],
                'description' => $group['description'],
                'count' => count($modules),
                'modules' => array_slice(array_map(function ($module) {
                    return [
                        'id' => $module['id'],
                        'name' => $module['name'],
                    ];
                }, $modules), 0, 6),
                'missing_ids' => array_slice(array_values(array_map(function ($module) {
                    return $module['id'];
                }, $modules)), 0, 6),
            ];
        }

        return $result;
    }

    private function add_gap_module(&$collection, $module_id, $registered_modules, $active_modules) {
        if (in_array($module_id, $active_modules, true) || !isset($registered_modules[$module_id])) {
            return;
        }

        if (!isset($collection[$module_id])) {
            $collection[$module_id] = [
                'id' => $module_id,
                'name' => $this->get_registered_module_name($module_id, $registered_modules),
                'priority' => 0,
            ];
        }

        $collection[$module_id]['priority']++;
    }

    private function get_ecosystem_map($registered_modules, $active_modules) {
        $relation_labels = [
            'depends_on' => __('Depende de', 'flavor-chat-ia'),
            'supports_modules' => __('Soporta', 'flavor-chat-ia'),
            'measures_modules' => __('Mide', 'flavor-chat-ia'),
            'governs_modules' => __('Gobierna', 'flavor-chat-ia'),
            'teaches_modules' => __('Enseña', 'flavor-chat-ia'),
            'base_for_modules' => __('Base de', 'flavor-chat-ia'),
        ];

        $nodes = [];
        foreach ($active_modules as $module_id) {
            $module = $registered_modules[$module_id] ?? null;
            if (!$module) {
                continue;
            }

            $ecosystem = is_array($module['ecosystem'] ?? null) ? $module['ecosystem'] : [];
            $groups = [];
            $connection_count = 0;

            foreach ($relation_labels as $relation_key => $label) {
                $related_ids = array_values(array_unique(array_filter((array) ($ecosystem[$relation_key] ?? []))));
                if (empty($related_ids)) {
                    continue;
                }

                $related_modules = [];
                foreach ($related_ids as $related_id) {
                    if (!isset($registered_modules[$related_id])) {
                        continue;
                    }

                    $related_modules[] = [
                        'id' => $related_id,
                        'name' => $this->get_registered_module_name($related_id, $registered_modules),
                        'is_active' => in_array($related_id, $active_modules, true),
                    ];
                }

                if (empty($related_modules)) {
                    continue;
                }

                usort($related_modules, function ($a, $b) {
                    if ($a['is_active'] !== $b['is_active']) {
                        return $a['is_active'] ? -1 : 1;
                    }

                    return strcmp($a['name'], $b['name']);
                });

                $groups[] = [
                    'label' => $label,
                    'modules' => array_slice($related_modules, 0, 5),
                ];
                $connection_count += count($related_modules);
            }

            if (empty($groups)) {
                continue;
            }

            $nodes[] = [
                'id' => $module_id,
                'name' => $this->get_registered_module_name($module_id, $registered_modules),
                'role_label' => $this->get_ecosystem_role_label($ecosystem['module_role'] ?? 'vertical'),
                'groups' => $groups,
                'connection_count' => $connection_count,
            ];
        }

        usort($nodes, function ($a, $b) {
            if ($a['connection_count'] === $b['connection_count']) {
                return strcmp($a['name'], $b['name']);
            }

            return $a['connection_count'] > $b['connection_count'] ? -1 : 1;
        });

        return array_slice($nodes, 0, 8);
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
        $paginas = $this->get_module_admin_pages($modulo_id);

        // Buscar página de configuración
        foreach ($paginas as $pagina) {
            if (strpos($pagina['slug'], 'config') !== false || strpos($pagina['slug'], 'settings') !== false) {
                return admin_url('admin.php?page=' . $pagina['slug']);
            }
        }

        return null;
    }

    /**
     * Obtiene las páginas de administración de un módulo
     *
     * @param string $modulo_id ID del módulo
     * @return array Array de páginas con slug, titulo, url
     */
    public function get_module_admin_pages($modulo_id) {
        static $cache = [];

        if (isset($cache[$modulo_id])) {
            return $cache[$modulo_id];
        }

        $paginas = [];
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulo = $loader->get_module($modulo_id);

        if (!$modulo) {
            $cache[$modulo_id] = $paginas;
            return $paginas;
        }

        // Intentar obtener config de admin del módulo (verificar que sea público)
        if (method_exists($modulo, 'get_admin_config')) {
            try {
                $reflection = new ReflectionMethod($modulo, 'get_admin_config');
                if ($reflection->isPublic()) {
                    $config = $modulo->get_admin_config();
                    if (!empty($config['paginas'])) {
                        foreach ($config['paginas'] as $pagina) {
                            $paginas[] = [
                                'slug'   => $pagina['slug'],
                                'titulo' => $pagina['titulo'],
                                'url'    => admin_url('admin.php?page=' . $pagina['slug']),
                                'icon'   => $pagina['icon'] ?? 'dashicons-admin-generic',
                            ];
                        }
                    }
                }
            } catch (ReflectionException $e) {
                // Método no accesible, continuar
            }
        }

        // Fallback: Páginas conocidas para módulos específicos
        if (empty($paginas)) {
            $paginas_conocidas = $this->get_known_admin_pages($modulo_id);
            $paginas = $paginas_conocidas;
        }

        $paginas = $this->normalize_module_dashboard_page($modulo_id, $paginas);

        $cache[$modulo_id] = $paginas;
        return $paginas;
    }

    /**
     * Alinea la página principal con el dashboard canónico del módulo cuando existe.
     *
     * @param string $modulo_id
     * @param array  $paginas
     * @return array
     */
    private function normalize_module_dashboard_page($modulo_id, array $paginas) {
        $mapped_slug = class_exists('Flavor_Module_Admin_Pages_Trait')
            ? Flavor_Module_Admin_Pages_Helper::get_module_dashboard_page($modulo_id)
            : null;
        $mapped_url = class_exists('Flavor_Module_Admin_Pages_Trait')
            ? Flavor_Module_Admin_Pages_Helper::get_module_dashboard_url($modulo_id)
            : null;

        if (empty($mapped_slug) || empty($mapped_url)) {
            return $paginas;
        }

        $dashboard_index = null;
        foreach ($paginas as $index => $pagina) {
            $titulo = strtolower(wp_strip_all_tags((string) ($pagina['titulo'] ?? '')));
            if ($titulo === 'dashboard') {
                $dashboard_index = $index;
                break;
            }
        }

        if ($dashboard_index !== null) {
            $paginas[$dashboard_index]['slug'] = $mapped_slug;
            $paginas[$dashboard_index]['url'] = $mapped_url;
            $paginas[$dashboard_index]['icon'] = $paginas[$dashboard_index]['icon'] ?? 'dashicons-dashboard';
        } else {
            array_unshift($paginas, [
                'slug' => $mapped_slug,
                'titulo' => __('Dashboard', 'flavor-chat-ia'),
                'url' => $mapped_url,
                'icon' => 'dashicons-dashboard',
            ]);
        }

        $seen = [];
        $normalized = [];
        foreach ($paginas as $pagina) {
            $slug = (string) ($pagina['slug'] ?? '');
            if ($slug === '' || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $normalized[] = $pagina;
        }

        return $normalized;
    }

    /**
     * Páginas de admin conocidas para módulos que no usan el trait
     */
    private function get_known_admin_pages($modulo_id) {
        $paginas_por_modulo = [
            'grupos_consumo' => [
                ['slug' => 'grupos-consumo', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'gc-consumidores', 'titulo' => __('Consumidores', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                ['slug' => 'gc-pedidos', 'titulo' => __('Pedidos', 'flavor-chat-ia'), 'icon' => 'dashicons-cart'],
                ['slug' => 'gc-consolidado', 'titulo' => __('Consolidado', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view'],
                ['slug' => 'gc-reportes', 'titulo' => __('Reportes', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
                ['slug' => 'gc-configuracion', 'titulo' => __('Configuración', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-generic'],
            ],
            'email_marketing' => [
                ['slug' => 'flavor-email-marketing', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'flavor-email-campaigns', 'titulo' => __('Campañas', 'flavor-chat-ia'), 'icon' => 'dashicons-email'],
                ['slug' => 'flavor-email-lists', 'titulo' => __('Listas', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                ['slug' => 'flavor-email-templates', 'titulo' => __('Plantillas', 'flavor-chat-ia'), 'icon' => 'dashicons-media-text'],
            ],
            'banco_tiempo' => [
                ['slug' => 'banco-tiempo', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'bt-servicios', 'titulo' => __('Servicios', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard'],
                ['slug' => 'bt-intercambios', 'titulo' => __('Intercambios', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize'],
                ['slug' => 'bt-usuarios', 'titulo' => __('Usuarios', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
            ],
            'comunidades' => [
                ['slug' => 'comunidades', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'comunidades-listado', 'titulo' => __('Listado', 'flavor-chat-ia'), 'icon' => 'dashicons-networking'],
                ['slug' => 'comunidades-actividad', 'titulo' => __('Actividad', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-line'],
                ['slug' => 'comunidades-metricas', 'titulo' => __('Métricas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
            ],
            'colectivos' => [
                ['slug' => 'colectivos', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'colectivos-listado', 'titulo' => __('Listado', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                ['slug' => 'colectivos-proyectos', 'titulo' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                ['slug' => 'colectivos-asambleas', 'titulo' => __('Asambleas', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
            ],
            'eventos' => [
                ['slug' => 'eventos', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'eventos-listado', 'titulo' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                ['slug' => 'eventos-calendario', 'titulo' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                ['slug' => 'eventos-asistentes', 'titulo' => __('Asistentes', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                ['slug' => 'eventos-entradas', 'titulo' => __('Entradas', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt'],
            ],
            'cursos' => [
                ['slug' => 'cursos', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'cursos-listado', 'titulo' => __('Cursos', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more'],
                ['slug' => 'cursos-alumnos', 'titulo' => __('Alumnos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                ['slug' => 'cursos-instructores', 'titulo' => __('Instructores', 'flavor-chat-ia'), 'icon' => 'dashicons-businessman'],
                ['slug' => 'cursos-matriculas', 'titulo' => __('Matrículas', 'flavor-chat-ia'), 'icon' => 'dashicons-id-alt'],
            ],
            'marketplace' => [
                ['slug' => 'marketplace', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'marketplace-productos', 'titulo' => __('Productos', 'flavor-chat-ia'), 'icon' => 'dashicons-products'],
                ['slug' => 'marketplace-ventas', 'titulo' => __('Ventas', 'flavor-chat-ia'), 'icon' => 'dashicons-cart'],
                ['slug' => 'marketplace-vendedores', 'titulo' => __('Vendedores', 'flavor-chat-ia'), 'icon' => 'dashicons-store'],
                ['slug' => 'marketplace-categorias', 'titulo' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
            ],
            'talleres' => [
                ['slug' => 'talleres', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'talleres-listado', 'titulo' => __('Talleres', 'flavor-chat-ia'), 'icon' => 'dashicons-hammer'],
                ['slug' => 'talleres-inscripciones', 'titulo' => __('Inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard'],
                ['slug' => 'talleres-materiales', 'titulo' => __('Materiales', 'flavor-chat-ia'), 'icon' => 'dashicons-media-document'],
            ],
            'reservas' => [
                ['slug' => 'reservas', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'reservas-listado', 'titulo' => __('Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                ['slug' => 'reservas-recursos', 'titulo' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-home'],
                ['slug' => 'reservas-calendario', 'titulo' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
            ],
            'socios' => [
                ['slug' => 'socios', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'socios-listado', 'titulo' => __('Listado', 'flavor-chat-ia'), 'icon' => 'dashicons-id-alt'],
                ['slug' => 'socios-cuotas', 'titulo' => __('Cuotas', 'flavor-chat-ia'), 'icon' => 'dashicons-money'],
                ['slug' => 'socios-pagos', 'titulo' => __('Pagos', 'flavor-chat-ia'), 'icon' => 'dashicons-money-alt'],
            ],
            'incidencias' => [
                ['slug' => 'incidencias', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'incidencias-tickets', 'titulo' => __('Tickets', 'flavor-chat-ia'), 'icon' => 'dashicons-warning'],
                ['slug' => 'incidencias-categorias', 'titulo' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
                ['slug' => 'incidencias-estadisticas', 'titulo' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
            ],
            'foros' => [
                ['slug' => 'foros', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'foros-listado', 'titulo' => __('Foros', 'flavor-chat-ia'), 'icon' => 'dashicons-format-chat'],
                ['slug' => 'foros-hilos', 'titulo' => __('Hilos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-comments'],
                ['slug' => 'foros-moderacion', 'titulo' => __('Moderación', 'flavor-chat-ia'), 'icon' => 'dashicons-shield'],
            ],
            'podcast' => [
                ['slug' => 'podcast', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'podcast-series', 'titulo' => __('Series', 'flavor-chat-ia'), 'icon' => 'dashicons-playlist-audio'],
                ['slug' => 'podcast-episodios', 'titulo' => __('Episodios', 'flavor-chat-ia'), 'icon' => 'dashicons-microphone'],
                ['slug' => 'podcast-suscriptores', 'titulo' => __('Suscriptores', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                ['slug' => 'podcast-estadisticas', 'titulo' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
            ],
            'multimedia' => [
                ['slug' => 'multimedia', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'multimedia-galeria', 'titulo' => __('Galería', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery'],
                ['slug' => 'multimedia-albumes', 'titulo' => __('Álbumes', 'flavor-chat-ia'), 'icon' => 'dashicons-images-alt2'],
                ['slug' => 'multimedia-categorias', 'titulo' => __('Categorías', 'flavor-chat-ia'), 'icon' => 'dashicons-category'],
                ['slug' => 'multimedia-moderacion', 'titulo' => __('Moderación', 'flavor-chat-ia'), 'icon' => 'dashicons-shield'],
            ],
            'red_social' => [
                ['slug' => 'red-social', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'red-social-publicaciones', 'titulo' => __('Publicaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-format-status'],
                ['slug' => 'red-social-usuarios', 'titulo' => __('Usuarios', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                ['slug' => 'red-social-moderacion', 'titulo' => __('Moderación', 'flavor-chat-ia'), 'icon' => 'dashicons-shield'],
            ],
            'ayuda_vecinal' => [
                ['slug' => 'ayuda-vecinal', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'ayuda-solicitudes', 'titulo' => __('Solicitudes', 'flavor-chat-ia'), 'icon' => 'dashicons-heart'],
                ['slug' => 'ayuda-voluntarios', 'titulo' => __('Voluntarios', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                ['slug' => 'ayuda-matches', 'titulo' => __('Matches', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize'],
                ['slug' => 'ayuda-estadisticas', 'titulo' => __('Estadísticas', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
            ],
            'espacios_comunes' => [
                ['slug' => 'espacios-comunes', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'ec-espacios', 'titulo' => __('Espacios', 'flavor-chat-ia'), 'icon' => 'dashicons-building'],
                ['slug' => 'ec-reservas', 'titulo' => __('Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                ['slug' => 'ec-calendario', 'titulo' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                ['slug' => 'ec-normas', 'titulo' => __('Normas', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard'],
            ],
            'huertos_urbanos' => [
                ['slug' => 'huertos-urbanos', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'hu-parcelas', 'titulo' => __('Parcelas', 'flavor-chat-ia'), 'icon' => 'dashicons-grid-view'],
                ['slug' => 'hu-huertanos', 'titulo' => __('Huertanos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups'],
                ['slug' => 'hu-cosechas', 'titulo' => __('Cosechas', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'],
                ['slug' => 'hu-recursos', 'titulo' => __('Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-tools'],
            ],
            'participacion' => [
                ['slug' => 'participacion', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'part-propuestas', 'titulo' => __('Propuestas', 'flavor-chat-ia'), 'icon' => 'dashicons-lightbulb'],
                ['slug' => 'part-votaciones', 'titulo' => __('Votaciones', 'flavor-chat-ia'), 'icon' => 'dashicons-yes'],
                ['slug' => 'part-debates', 'titulo' => __('Debates', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone'],
                ['slug' => 'part-resultados', 'titulo' => __('Resultados', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-pie'],
            ],
            'presupuestos_participativos' => [
                ['slug' => 'presupuestos-participativos', 'titulo' => __('Dashboard', 'flavor-chat-ia'), 'icon' => 'dashicons-dashboard'],
                ['slug' => 'pp-proyectos', 'titulo' => __('Proyectos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio'],
                ['slug' => 'pp-presupuesto', 'titulo' => __('Presupuesto', 'flavor-chat-ia'), 'icon' => 'dashicons-money-alt'],
                ['slug' => 'pp-votos', 'titulo' => __('Votos', 'flavor-chat-ia'), 'icon' => 'dashicons-yes-alt'],
                ['slug' => 'pp-resultados', 'titulo' => __('Resultados', 'flavor-chat-ia'), 'icon' => 'dashicons-chart-bar'],
            ],
        ];

        $paginas = [];
        if (isset($paginas_por_modulo[$modulo_id])) {
            foreach ($paginas_por_modulo[$modulo_id] as $p) {
                $paginas[] = [
                    'slug'   => $p['slug'],
                    'titulo' => $p['titulo'],
                    'url'    => admin_url('admin.php?page=' . $p['slug']),
                    'icon'   => $p['icon'],
                ];
            }
        }

        return $paginas;
    }

    /**
     * Obtiene la URL del dashboard principal de un módulo
     *
     * @param string $modulo_id ID del módulo
     * @return string|null URL del dashboard o null
     */
    public function get_module_dashboard_url($modulo_id) {
        $mapped_url = class_exists('Flavor_Module_Admin_Pages_Trait')
            ? Flavor_Module_Admin_Pages_Helper::get_module_dashboard_url($modulo_id)
            : null;

        if (!empty($mapped_url)) {
            return $mapped_url;
        }

        $paginas = $this->get_module_admin_pages($modulo_id);

        if (!empty($paginas)) {
            // La primera página suele ser el dashboard
            return $paginas[0]['url'];
        }

        return null;
    }

    /**
     * Obtiene la URL principal del módulo en el portal web.
     *
     * @param string $modulo_id ID del módulo
     * @return string|null
     */
    private function get_module_portal_url($modulo_id) {
        $slug = str_replace('_', '-', $modulo_id);
        $portal_page = get_page_by_path('mi-portal/' . $slug);

        if ($portal_page) {
            return get_permalink($portal_page->ID);
        }

        $page = get_page_by_path($slug);
        if ($page) {
            return get_permalink($page->ID);
        }

        return null;
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

        // Debug log
        error_log("FUM Toggle Module: module_id={$modulo_id}, activate=" . ($activate ? 'true' : 'false'));

        if (empty($modulo_id)) {
            wp_send_json_error(['message' => __('ID de módulo inválido', 'flavor-chat-ia')]);
        }

        // CRÍTICO: Limpiar caché de opciones antes de leer
        wp_cache_delete('flavor_chat_ia_settings', 'options');
        wp_cache_delete('alloptions', 'options');

        // Leer directamente de BD para evitar problemas de caché
        global $wpdb;
        $configuracion_raw = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                'flavor_chat_ia_settings'
            )
        );
        $configuracion = $configuracion_raw ? maybe_unserialize($configuracion_raw) : [];
        if (!is_array($configuracion)) {
            $configuracion = [];
        }

        $modulos_activos = isset($configuracion['active_modules']) && is_array($configuracion['active_modules'])
            ? $configuracion['active_modules']
            : [];
        $modulos_opcionales_activos = isset($configuracion['modulos_opcionales_activos']) && is_array($configuracion['modulos_opcionales_activos'])
            ? $configuracion['modulos_opcionales_activos']
            : [];

        error_log("FUM PRE-toggle: active_modules=" . implode(',', $modulos_activos));

        // Obtener módulos requeridos del perfil activo para no permitir desactivarlos
        $gestor_perfiles = Flavor_App_Profiles::get_instance();
        $modulos_requeridos = $gestor_perfiles->obtener_modulos_requeridos();

        if ($activate) {
            // Activar módulo
            if (!in_array($modulo_id, $modulos_activos)) {
                $modulos_activos[] = $modulo_id;
            }
            // También añadir a opcionales activos si no es requerido
            if (!in_array($modulo_id, $modulos_requeridos) && !in_array($modulo_id, $modulos_opcionales_activos)) {
                $modulos_opcionales_activos[] = $modulo_id;
            }
        } else {
            // No permitir desactivar módulos requeridos
            if (in_array($modulo_id, $modulos_requeridos)) {
                wp_send_json_error(['message' => __('No se puede desactivar un módulo requerido por el perfil activo', 'flavor-chat-ia')]);
            }
            // Desactivar módulo
            $modulos_activos = array_values(array_diff($modulos_activos, [$modulo_id]));
            $modulos_opcionales_activos = array_values(array_diff($modulos_opcionales_activos, [$modulo_id]));
        }

        // Reindexar arrays para evitar índices no consecutivos
        $modulos_activos = array_values(array_unique($modulos_activos));
        $modulos_opcionales_activos = array_values(array_unique($modulos_opcionales_activos));

        $configuracion['active_modules'] = $modulos_activos;
        $configuracion['modulos_opcionales_activos'] = $modulos_opcionales_activos;

        error_log("FUM POST-toggle (antes de guardar): active_modules=" . implode(',', $modulos_activos));

        // Guardar directamente en BD para evitar problemas de caché
        $valor_serializado = maybe_serialize($configuracion);
        $resultado_guardado = $wpdb->update(
            $wpdb->options,
            ['option_value' => $valor_serializado],
            ['option_name' => 'flavor_chat_ia_settings'],
            ['%s'],
            ['%s']
        );

        if ($resultado_guardado === false) {
            error_log("FUM ERROR: Fallo al guardar en BD - " . $wpdb->last_error);
            wp_send_json_error(['message' => __('Error al guardar configuración', 'flavor-chat-ia')]);
        }

        // Limpiar caché después de guardar
        wp_cache_delete('flavor_chat_ia_settings', 'options');
        wp_cache_delete('alloptions', 'options');

        // Verificar lectura directa
        $verificacion_raw = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                'flavor_chat_ia_settings'
            )
        );
        $verificacion = maybe_unserialize($verificacion_raw);
        error_log("FUM Verificación POST-guardado (BD directa): active_modules=" . implode(',', $verificacion['active_modules'] ?? []));

        // También actualizar flavor_apps_config para mantener consistencia
        $apps_config = get_option('flavor_apps_config', []);
        if (!isset($apps_config['modules']) || !is_array($apps_config['modules'])) {
            $apps_config['modules'] = [];
        }
        if (!isset($apps_config['modules'][$modulo_id])) {
            $apps_config['modules'][$modulo_id] = [];
        }
        $apps_config['modules'][$modulo_id]['enabled'] = $activate ? 1 : 0;
        update_option('flavor_apps_config', $apps_config);

        // Invalidar caché de API
        delete_transient('flavor_api_system_info');
        delete_transient('flavor_api_available_modules');

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
