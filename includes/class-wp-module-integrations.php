<?php
/**
 * Integración de Posts de WordPress con Módulos del Plugin
 *
 * Permite compartir posts en múltiples módulos:
 * - Email Marketing (newsletters)
 * - Comunidades/Colectivos
 * - Eventos (asociar posts)
 * - Foros (crear debates)
 * - Cursos/Talleres (material)
 * - Campañas
 * - Biblioteca
 *
 * @package FlavorPlatform
 * @since 4.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_WP_Module_Integrations {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Módulos disponibles para integración
     */
    private $modulos_disponibles = [];

    /**
     * Post types habilitados
     */
    private $post_types_habilitados = ['post', 'page'];

    /**
     * Caché en memoria para consultas frecuentes
     */
    private static $cache = [];

    /**
     * Tiempo de caché en segundos (transients)
     */
    private $cache_ttl = 300; // 5 minutos

    /**
     * Roles con permisos de integración
     */
    private $roles_permitidos = ['administrator', 'editor'];

    /**
     * Capacidad requerida para integrar
     */
    private $capability_integrar = 'edit_posts';

    /**
     * Capacidad requerida para gestionar
     */
    private $capability_gestionar = 'manage_options';

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->cargar_configuracion();
        $this->detectar_modulos_activos();
        $this->init_hooks();
    }

    /**
     * Carga configuración desde opciones
     */
    private function cargar_configuracion() {
        $config = get_option('flavor_integraciones_config', []);

        if (!empty($config['post_types'])) {
            $this->post_types_habilitados = (array) $config['post_types'];
        }

        if (!empty($config['roles_permitidos'])) {
            $this->roles_permitidos = (array) $config['roles_permitidos'];
        }

        if (!empty($config['cache_ttl'])) {
            $this->cache_ttl = absint($config['cache_ttl']);
        }

        // Permitir filtrar configuración
        $this->post_types_habilitados = apply_filters(
            'flavor_integraciones_post_types',
            $this->post_types_habilitados
        );

        $this->roles_permitidos = apply_filters(
            'flavor_integraciones_roles_permitidos',
            $this->roles_permitidos
        );
    }

    // =====================================================
    // SISTEMA DE CACHÉ
    // =====================================================

    /**
     * Obtiene valor de caché
     */
    private function cache_get($key) {
        // Primero buscar en memoria
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // Luego en transients
        $transient_key = 'flavor_int_' . md5($key);
        $value = get_transient($transient_key);

        if ($value !== false) {
            self::$cache[$key] = $value;
            return $value;
        }

        return null;
    }

    /**
     * Guarda valor en caché
     */
    private function cache_set($key, $value, $ttl = null) {
        $ttl = $ttl ?: $this->cache_ttl;

        // Guardar en memoria
        self::$cache[$key] = $value;

        // Guardar en transient
        $transient_key = 'flavor_int_' . md5($key);
        set_transient($transient_key, $value, $ttl);
    }

    /**
     * Invalida caché por patrón
     */
    private function cache_invalidate($pattern = null) {
        // Limpiar memoria
        if ($pattern === null) {
            self::$cache = [];
        } else {
            foreach (array_keys(self::$cache) as $key) {
                if (strpos($key, $pattern) !== false) {
                    unset(self::$cache[$key]);
                }
            }
        }

        // Para transients, usamos un hook de invalidación
        do_action('flavor_integraciones_cache_invalidate', $pattern);
    }

    /**
     * Invalida caché de un post específico
     */
    public function invalidar_cache_post($post_id) {
        $this->cache_invalidate('post_' . $post_id);
        $this->cache_invalidate('integraciones_post_' . $post_id);

        // Limpiar transients específicos
        delete_transient('flavor_int_' . md5('integraciones_post_' . $post_id));
    }

    /**
     * Invalida caché de un módulo
     */
    public function invalidar_cache_modulo($modulo, $elemento_id = null) {
        $pattern = $elemento_id ? "modulo_{$modulo}_{$elemento_id}" : "modulo_{$modulo}";
        $this->cache_invalidate($pattern);
    }

    // =====================================================
    // SISTEMA DE PERMISOS
    // =====================================================

    /**
     * Verifica si el usuario actual puede integrar posts
     */
    public function usuario_puede_integrar($post_id = null) {
        // Permitir filtrar
        $puede = apply_filters('flavor_usuario_puede_integrar', null, $post_id);
        if ($puede !== null) {
            return $puede;
        }

        // Verificar capacidad básica
        if (!current_user_can($this->capability_integrar)) {
            return false;
        }

        // Verificar si puede editar el post específico
        if ($post_id && !current_user_can('edit_post', $post_id)) {
            return false;
        }

        // Verificar rol
        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;
        $roles_intersect = array_intersect($user_roles, $this->roles_permitidos);

        return !empty($roles_intersect);
    }

    /**
     * Verifica si el usuario puede gestionar integraciones (admin)
     */
    public function usuario_puede_gestionar() {
        $puede = apply_filters('flavor_usuario_puede_gestionar_integraciones', null);
        if ($puede !== null) {
            return $puede;
        }

        return current_user_can($this->capability_gestionar);
    }

    /**
     * Verifica si el usuario puede integrar con un módulo específico
     */
    public function usuario_puede_integrar_modulo($modulo, $post_id = null) {
        // Primero verificar permiso general
        if (!$this->usuario_puede_integrar($post_id)) {
            return false;
        }

        // Filtro específico por módulo
        return apply_filters(
            "flavor_usuario_puede_integrar_{$modulo}",
            true,
            $post_id,
            get_current_user_id()
        );
    }

    /**
     * Obtiene los módulos que el usuario puede usar
     */
    public function get_modulos_para_usuario() {
        if (!$this->usuario_puede_integrar()) {
            return [];
        }

        $modulos = [];
        foreach ($this->modulos_disponibles as $key => $modulo) {
            if ($this->usuario_puede_integrar_modulo($key)) {
                $modulos[$key] = $modulo;
            }
        }

        return $modulos;
    }

    /**
     * Detecta qué módulos están activos
     */
    private function detectar_modulos_activos() {
        $settings = flavor_get_main_settings();
        $active_modules = $settings['active_modules'] ?? [];

        // Mapeo de módulos y sus integraciones
        $modulos_integrables = [
            'email_marketing' => [
                'slug'        => 'email-marketing',
                'nombre'      => __('Email Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'       => 'email-alt',
                'descripcion' => __('Incluir en próxima newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'comunidades' => [
                'slug'        => 'comunidades',
                'nombre'      => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'       => 'groups',
                'descripcion' => __('Compartir en una comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'colectivos' => [
                'slug'        => 'colectivos',
                'nombre'      => __('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'       => 'buddicons-groups',
                'descripcion' => __('Publicar en un colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'eventos' => [
                'slug'        => 'eventos',
                'nombre'      => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'       => 'calendar-alt',
                'descripcion' => __('Asociar a un evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'foros' => [
                'slug'        => 'foros',
                'nombre'      => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'       => 'format-chat',
                'descripcion' => __('Crear debate en foro', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'cursos' => [
                'slug'        => 'cursos',
                'nombre'      => __('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'       => 'welcome-learn-more',
                'descripcion' => __('Vincular como material de curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'talleres' => [
                'slug'        => 'talleres',
                'nombre'      => __('Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'       => 'hammer',
                'descripcion' => __('Asociar a un taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'campanias' => [
                'slug'        => 'campanias',
                'nombre'      => __('Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'       => 'megaphone',
                'descripcion' => __('Vincular a una campaña activa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'biblioteca' => [
                'slug'        => 'biblioteca',
                'nombre'      => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'       => 'book',
                'descripcion' => __('Catalogar como recurso', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];

        foreach ($modulos_integrables as $key => $config) {
            $slug = $config['slug'];
            $slug_alt = str_replace('-', '_', $slug);

            if (in_array($slug, $active_modules) || in_array($slug_alt, $active_modules)) {
                $this->modulos_disponibles[$key] = $config;
            }
        }
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        if (empty($this->modulos_disponibles)) {
            return;
        }

        // Metabox adicional para integraciones
        add_action('add_meta_boxes', [$this, 'registrar_metabox'], 20);

        // Guardar meta
        add_action('save_post', [$this, 'guardar_meta'], 20, 2);

        // Hook al publicar - procesar integraciones
        add_action('flavor_post_compartido_social', [$this, 'procesar_integraciones'], 10, 3);

        // AJAX para obtener elementos de módulos
        add_action('wp_ajax_flavor_obtener_elementos_modulo', [$this, 'ajax_obtener_elementos']);
        add_action('wp_ajax_flavor_integrar_post_modulo', [$this, 'ajax_integrar_post']);
        add_action('wp_ajax_flavor_eliminar_integracion', [$this, 'ajax_eliminar_integracion']);
        add_action('wp_ajax_flavor_obtener_historial_integraciones', [$this, 'ajax_obtener_historial']);

        // Assets
        add_action('admin_enqueue_scripts', [$this, 'cargar_assets_admin']);
        add_action('wp_enqueue_scripts', [$this, 'cargar_assets_frontend']);

        // Extender modal de compartir en frontend
        add_action('flavor_modal_compartir_opciones', [$this, 'renderizar_opciones_modal']);

        // Filtros en listado de posts admin
        add_action('restrict_manage_posts', [$this, 'agregar_filtro_modulos']);
        add_filter('pre_get_posts', [$this, 'filtrar_por_modulo']);

        // Limpieza al eliminar posts
        add_action('before_delete_post', [$this, 'limpiar_integraciones_post']);

        // Exportación CSV
        add_action('admin_init', [$this, 'manejar_exportacion_csv']);
    }

    /**
     * Registra el metabox de integraciones
     */
    public function registrar_metabox() {
        foreach ($this->post_types_habilitados as $post_type) {
            add_meta_box(
                'flavor_module_integrations',
                __('Integrar con Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                [$this, 'renderizar_metabox'],
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Renderiza el metabox
     */
    public function renderizar_metabox($post) {
        // Verificar permisos
        if (!$this->usuario_puede_integrar($post->ID)) {
            echo '<p class="description">' . esc_html__('No tienes permisos para integrar este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }

        wp_nonce_field('flavor_module_integrations_nonce', 'flavor_module_integrations_nonce');

        $integraciones_guardadas = get_post_meta($post->ID, '_flavor_integraciones_modulos', true) ?: [];
        $modulos_usuario = $this->get_modulos_para_usuario();

        if (empty($modulos_usuario)) {
            echo '<p class="description">' . esc_html__('No hay módulos disponibles para integración.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            return;
        }
        ?>
        <div class="flavor-module-integrations-metabox">
            <p class="description" style="margin-bottom:12px;">
                <?php esc_html_e('Selecciona dónde quieres integrar este contenido:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <?php foreach ($modulos_usuario as $key => $modulo):
                $checked = !empty($integraciones_guardadas[$key]['enabled']);
                $elemento_id = $integraciones_guardadas[$key]['elemento_id'] ?? '';
                ?>
                <div class="flavor-integracion-item" data-modulo="<?php echo esc_attr($key); ?>">
                    <label class="flavor-integracion-toggle">
                        <input type="checkbox"
                               name="flavor_integraciones[<?php echo esc_attr($key); ?>][enabled]"
                               value="1"
                               <?php checked($checked); ?>
                               class="flavor-integracion-checkbox">
                        <span class="dashicons dashicons-<?php echo esc_attr($modulo['icono']); ?>"></span>
                        <span class="flavor-integracion-nombre"><?php echo esc_html($modulo['nombre']); ?></span>
                    </label>

                    <div class="flavor-integracion-config" style="<?php echo $checked ? '' : 'display:none;'; ?>">
                        <select name="flavor_integraciones[<?php echo esc_attr($key); ?>][elemento_id]"
                                class="flavor-select-elemento"
                                data-modulo="<?php echo esc_attr($key); ?>"
                                style="width:100%;margin-top:5px;">
                            <option value=""><?php echo esc_html($this->get_placeholder_select($key)); ?></option>
                            <?php
                            $elementos = $this->obtener_elementos_modulo($key);
                            foreach ($elementos as $el): ?>
                                <option value="<?php echo esc_attr($el['id']); ?>" <?php selected($elemento_id, $el['id']); ?>>
                                    <?php echo esc_html($el['titulo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($post->post_status === 'publish'): ?>
                <hr style="margin:15px 0;">
                <button type="button"
                        class="button"
                        id="flavor-integrar-ahora"
                        data-post-id="<?php echo esc_attr($post->ID); ?>"
                        style="width:100%;">
                    <span class="dashicons dashicons-update" style="margin-top:4px;"></span>
                    <?php esc_html_e('Integrar ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>

                <?php
                // Mostrar historial de integraciones
                $historial = self::obtener_integraciones_post($post->ID);
                if (!empty($historial)):
                ?>
                <div class="flavor-historial-seccion" style="margin-top:15px;">
                    <h4 class="flavor-historial-titulo" style="margin:0 0 8px;font-size:12px;color:#666;">
                        <span class="dashicons dashicons-clock" style="font-size:14px;"></span>
                        <?php esc_html_e('Integraciones activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="flavor-count" style="background:#2271b1;color:#fff;padding:1px 6px;border-radius:10px;font-size:10px;margin-left:5px;">
                            <?php echo count($historial); ?>
                        </span>
                    </h4>
                    <div id="flavor-historial-integraciones" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <?php foreach ($historial as $integracion):
                            $modulo_info = $this->modulos_disponibles[$integracion['modulo']] ?? null;
                            if (!$modulo_info) continue;
                            $elemento_nombre = $this->obtener_nombre_elemento($integracion['modulo'], $integracion['elemento_id']);
                        ?>
                        <div class="flavor-historial-item" style="display:flex;align-items:center;gap:6px;padding:6px;background:#f9f9f9;border-radius:4px;margin-bottom:4px;font-size:11px;">
                            <span class="dashicons dashicons-<?php echo esc_attr($modulo_info['icono']); ?>" style="color:#2271b1;font-size:16px;"></span>
                            <div style="flex:1;">
                                <strong><?php echo esc_html($modulo_info['nombre']); ?></strong>
                                <?php if ($elemento_nombre): ?>
                                    <span style="color:#666;"> - <?php echo esc_html($elemento_nombre); ?></span>
                                <?php endif; ?>
                            </div>
                            <button type="button"
                                    class="flavor-eliminar-integracion button-link"
                                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                                    data-modulo="<?php echo esc_attr($integracion['modulo']); ?>"
                                    data-elemento-id="<?php echo esc_attr($integracion['elemento_id']); ?>"
                                    style="color:#a00;padding:2px;cursor:pointer;"
                                    title="<?php esc_attr_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-no-alt" style="font-size:14px;"></span>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div id="flavor-historial-integraciones" data-post-id="<?php echo esc_attr($post->ID); ?>" style="margin-top:10px;"></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <style>
            .flavor-module-integrations-metabox .flavor-integracion-item {
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            .flavor-module-integrations-metabox .flavor-integracion-item:last-child {
                border-bottom: none;
            }
            .flavor-integracion-toggle {
                display: flex;
                align-items: center;
                gap: 6px;
                cursor: pointer;
            }
            .flavor-integracion-toggle .dashicons {
                color: #666;
            }
            .flavor-integracion-checkbox:checked + .dashicons {
                color: #2271b1;
            }
            /* Spinner animation */
            @keyframes flavor-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .flavor-module-integrations-metabox .spin {
                animation: flavor-spin 1s linear infinite;
                display: inline-block;
            }
            /* Estilos eliminar */
            .flavor-eliminar-integracion:hover .dashicons {
                color: #dc3232 !important;
            }
        </style>
        <?php
    }

    /**
     * Obtiene placeholder para select según módulo
     */
    private function get_placeholder_select($modulo) {
        $placeholders = [
            'email_marketing' => __('Seleccionar newsletter...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comunidades'     => __('Seleccionar comunidad...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colectivos'      => __('Seleccionar colectivo...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eventos'         => __('Seleccionar evento...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'foros'           => __('Seleccionar foro...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cursos'          => __('Seleccionar curso...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'talleres'        => __('Seleccionar taller...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'campanias'       => __('Seleccionar campaña...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'biblioteca'      => __('Seleccionar categoría...', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
        return $placeholders[$modulo] ?? __('Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Obtiene elementos de un módulo
     */
    public function obtener_elementos_modulo($modulo, $limite = 50) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';
        $elementos = [];

        switch ($modulo) {
            case 'email_marketing':
                // Newsletters/Campañas de email
                $tabla = $prefix . 'email_campaigns';
                if ($this->tabla_existe($tabla)) {
                    $items = $wpdb->get_results(
                        "SELECT id, nombre as titulo FROM {$tabla}
                         WHERE estado IN ('borrador', 'programada')
                         ORDER BY fecha_creacion DESC LIMIT {$limite}"
                    );
                    foreach ($items as $item) {
                        $elementos[] = ['id' => $item->id, 'titulo' => $item->titulo];
                    }
                }
                break;

            case 'comunidades':
                $tabla = $prefix . 'comunidades';
                if ($this->tabla_existe($tabla)) {
                    $items = $wpdb->get_results(
                        "SELECT id, nombre as titulo FROM {$tabla}
                         WHERE estado = 'activa'
                         ORDER BY nombre ASC LIMIT {$limite}"
                    );
                    foreach ($items as $item) {
                        $elementos[] = ['id' => $item->id, 'titulo' => $item->titulo];
                    }
                }
                break;

            case 'colectivos':
                $tabla = $prefix . 'colectivos';
                if ($this->tabla_existe($tabla)) {
                    $items = $wpdb->get_results(
                        "SELECT id, nombre as titulo FROM {$tabla}
                         WHERE estado = 'activo'
                         ORDER BY nombre ASC LIMIT {$limite}"
                    );
                    foreach ($items as $item) {
                        $elementos[] = ['id' => $item->id, 'titulo' => $item->titulo];
                    }
                }
                break;

            case 'eventos':
                $tabla = $prefix . 'eventos';
                if ($this->tabla_existe($tabla)) {
                    $items = $wpdb->get_results(
                        "SELECT id, titulo FROM {$tabla}
                         WHERE fecha_inicio >= NOW() OR estado = 'activo'
                         ORDER BY fecha_inicio ASC LIMIT {$limite}"
                    );
                    foreach ($items as $item) {
                        $elementos[] = ['id' => $item->id, 'titulo' => $item->titulo];
                    }
                }
                break;

            case 'foros':
                $tabla = $prefix . 'foros';
                if ($this->tabla_existe($tabla)) {
                    $items = $wpdb->get_results(
                        "SELECT id, nombre as titulo FROM {$tabla}
                         WHERE estado = 'activo'
                         ORDER BY nombre ASC LIMIT {$limite}"
                    );
                    foreach ($items as $item) {
                        $elementos[] = ['id' => $item->id, 'titulo' => $item->titulo];
                    }
                }
                break;

            case 'cursos':
                $tabla = $prefix . 'cursos';
                if ($this->tabla_existe($tabla)) {
                    $items = $wpdb->get_results(
                        "SELECT id, titulo FROM {$tabla}
                         WHERE estado IN ('publicado', 'activo', 'borrador')
                         ORDER BY titulo ASC LIMIT {$limite}"
                    );
                    foreach ($items as $item) {
                        $elementos[] = ['id' => $item->id, 'titulo' => $item->titulo];
                    }
                }
                break;

            case 'talleres':
                $tabla = $prefix . 'talleres';
                if ($this->tabla_existe($tabla)) {
                    $items = $wpdb->get_results(
                        "SELECT id, titulo FROM {$tabla}
                         WHERE estado IN ('activo', 'programado')
                         ORDER BY fecha_inicio DESC LIMIT {$limite}"
                    );
                    foreach ($items as $item) {
                        $elementos[] = ['id' => $item->id, 'titulo' => $item->titulo];
                    }
                }
                break;

            case 'campanias':
                $tabla = $prefix . 'campanias';
                if ($this->tabla_existe($tabla)) {
                    $items = $wpdb->get_results(
                        "SELECT id, titulo FROM {$tabla}
                         WHERE estado = 'activa'
                         ORDER BY fecha_inicio DESC LIMIT {$limite}"
                    );
                    foreach ($items as $item) {
                        $elementos[] = ['id' => $item->id, 'titulo' => $item->titulo];
                    }
                }
                break;

            case 'biblioteca':
                $tabla = $prefix . 'biblioteca_categorias';
                if ($this->tabla_existe($tabla)) {
                    $items = $wpdb->get_results(
                        "SELECT id, nombre as titulo FROM {$tabla}
                         ORDER BY nombre ASC LIMIT {$limite}"
                    );
                    foreach ($items as $item) {
                        $elementos[] = ['id' => $item->id, 'titulo' => $item->titulo];
                    }
                } else {
                    // Si no hay tabla de categorías, usar opción de crear nuevo
                    $elementos[] = ['id' => 'nuevo', 'titulo' => __('Crear como nuevo recurso', FLAVOR_PLATFORM_TEXT_DOMAIN)];
                }
                break;
        }

        return $elementos;
    }

    /**
     * Verifica si una tabla existe
     */
    private function tabla_existe($tabla) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") === $tabla;
    }

    /**
     * Guarda los meta datos
     */
    public function guardar_meta($post_id, $post) {
        if (!isset($_POST['flavor_module_integrations_nonce']) ||
            !wp_verify_nonce($_POST['flavor_module_integrations_nonce'], 'flavor_module_integrations_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $integraciones = [];
        if (isset($_POST['flavor_integraciones']) && is_array($_POST['flavor_integraciones'])) {
            foreach ($_POST['flavor_integraciones'] as $modulo => $config) {
                $modulo = sanitize_key($modulo);
                $integraciones[$modulo] = [
                    'enabled'     => !empty($config['enabled']),
                    'elemento_id' => sanitize_text_field($config['elemento_id'] ?? ''),
                ];
            }
        }

        update_post_meta($post_id, '_flavor_integraciones_modulos', $integraciones);
    }

    /**
     * Procesa integraciones cuando se comparte un post
     */
    public function procesar_integraciones($publicacion_id, $post_id, $federar) {
        $integraciones = get_post_meta($post_id, '_flavor_integraciones_modulos', true);

        if (empty($integraciones)) {
            return;
        }

        foreach ($integraciones as $modulo => $config) {
            if (empty($config['enabled'])) {
                continue;
            }

            $elemento_id = $config['elemento_id'] ?? '';
            $this->ejecutar_integracion($modulo, $post_id, $elemento_id, $publicacion_id);
        }

        // Marcar como integrado
        update_post_meta($post_id, '_flavor_integraciones_procesadas', current_time('mysql'));
    }

    /**
     * Ejecuta una integración específica
     */
    public function ejecutar_integracion($modulo, $post_id, $elemento_id, $publicacion_social_id = null) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';
        $post = get_post($post_id);
        $resultado = false;

        switch ($modulo) {
            case 'email_marketing':
                $resultado = $this->integrar_email_marketing($post, $elemento_id);
                break;

            case 'comunidades':
                $resultado = $this->integrar_comunidad($post, $elemento_id, $publicacion_social_id);
                break;

            case 'colectivos':
                $resultado = $this->integrar_colectivo($post, $elemento_id, $publicacion_social_id);
                break;

            case 'eventos':
                $resultado = $this->integrar_evento($post, $elemento_id);
                break;

            case 'foros':
                $resultado = $this->integrar_foro($post, $elemento_id);
                break;

            case 'cursos':
                $resultado = $this->integrar_curso($post, $elemento_id);
                break;

            case 'talleres':
                $resultado = $this->integrar_taller($post, $elemento_id);
                break;

            case 'campanias':
                $resultado = $this->integrar_campania($post, $elemento_id);
                break;

            case 'biblioteca':
                $resultado = $this->integrar_biblioteca($post, $elemento_id);
                break;
        }

        if ($resultado) {
            // Registrar integración exitosa
            $this->registrar_integracion($post_id, $modulo, $elemento_id, $resultado);
        }

        return $resultado;
    }

    /**
     * Integra con Email Marketing
     */
    private function integrar_email_marketing($post, $newsletter_id) {
        global $wpdb;

        // Usar tabla de relaciones nueva (prioridad)
        $tabla_posts = $wpdb->prefix . 'flavor_email_newsletter_posts';
        if ($this->tabla_existe($tabla_posts)) {
            $resultado = $wpdb->insert($tabla_posts, [
                'newsletter_id'         => intval($newsletter_id),
                'post_id'               => $post->ID,
                'titulo_personalizado'  => null,
                'extracto_personalizado' => null,
                'orden'                 => 99,
                'incluido'              => 1,
                'fecha_agregado'        => current_time('mysql'),
            ]);
            if ($resultado) {
                return $wpdb->insert_id;
            }
        }

        // Fallback a tabla de contenido de campaña
        $tabla_contenido = $wpdb->prefix . 'flavor_email_campaign_content';
        if ($this->tabla_existe($tabla_contenido)) {
            return $wpdb->insert($tabla_contenido, [
                'campaign_id'    => intval($newsletter_id),
                'tipo'           => 'post',
                'referencia_id'  => $post->ID,
                'titulo'         => $post->post_title,
                'contenido'      => wp_trim_words(strip_shortcodes($post->post_content), 100),
                'enlace'         => get_permalink($post->ID),
                'imagen'         => get_the_post_thumbnail_url($post->ID, 'medium') ?: '',
                'orden'          => 99,
                'fecha_agregado' => current_time('mysql'),
            ]);
        }

        // Fallback a cola de newsletter
        $tabla_cola = $wpdb->prefix . 'flavor_email_queue';
        if ($this->tabla_existe($tabla_cola)) {
            return $wpdb->insert($tabla_cola, [
                'tipo'           => 'post_compartido',
                'referencia_id'  => $post->ID,
                'contenido'      => wp_json_encode([
                    'post_id'   => $post->ID,
                    'titulo'    => $post->post_title,
                    'extracto'  => wp_trim_words($post->post_content, 50),
                    'enlace'    => get_permalink($post->ID),
                    'imagen'    => get_the_post_thumbnail_url($post->ID, 'medium'),
                ]),
                'fecha_creacion' => current_time('mysql'),
                'estado'         => 'pendiente',
            ]);
        }

        return false;
    }

    /**
     * Integra con Comunidad
     */
    private function integrar_comunidad($post, $comunidad_id, $publicacion_social_id) {
        global $wpdb;

        // Usar tabla de relaciones nueva
        $tabla_posts = $wpdb->prefix . 'flavor_comunidades_posts';
        if ($this->tabla_existe($tabla_posts)) {
            $resultado = $wpdb->insert($tabla_posts, [
                'comunidad_id' => intval($comunidad_id),
                'post_id'      => $post->ID,
                'usuario_id'   => $post->post_author ?: get_current_user_id(),
                'mensaje'      => wp_trim_words($post->post_content, 30),
                'fecha'        => current_time('mysql'),
            ]);
            if ($resultado) {
                return $wpdb->insert_id;
            }
        }

        // Fallback a tabla de publicaciones de comunidad
        $tabla_publicaciones = $wpdb->prefix . 'flavor_comunidad_publicaciones';
        if ($this->tabla_existe($tabla_publicaciones)) {
            return $wpdb->insert($tabla_publicaciones, [
                'comunidad_id'   => intval($comunidad_id),
                'usuario_id'     => $post->post_author ?: get_current_user_id(),
                'tipo'           => 'post_compartido',
                'contenido'      => $post->post_title . "\n\n" . wp_trim_words($post->post_content, 50),
                'enlace_externo' => get_permalink($post->ID),
                'post_id'        => $post->ID,
                'fecha_creacion' => current_time('mysql'),
                'estado'         => 'publicado',
            ]);
        }

        // Fallback a meta de publicación social
        if ($publicacion_social_id) {
            $tabla_meta = $wpdb->prefix . 'flavor_social_publicaciones_meta';
            if ($this->tabla_existe($tabla_meta)) {
                return $wpdb->insert($tabla_meta, [
                    'publicacion_id' => $publicacion_social_id,
                    'meta_key'       => '_comunidad_id',
                    'meta_value'     => $comunidad_id,
                ]);
            }
        }

        return false;
    }

    /**
     * Integra con Colectivo
     */
    private function integrar_colectivo($post, $colectivo_id, $publicacion_social_id) {
        global $wpdb;

        // Usar tabla de relaciones nueva
        $tabla_posts = $wpdb->prefix . 'flavor_colectivos_posts';
        if ($this->tabla_existe($tabla_posts)) {
            $resultado = $wpdb->insert($tabla_posts, [
                'colectivo_id' => intval($colectivo_id),
                'post_id'      => $post->ID,
                'usuario_id'   => $post->post_author ?: get_current_user_id(),
                'tipo'         => 'compartido',
                'fecha'        => current_time('mysql'),
            ]);
            if ($resultado) {
                return $wpdb->insert_id;
            }
        }

        // Fallback a tabla de actividad
        $tabla_actividad = $wpdb->prefix . 'flavor_colectivos_actividad';
        if ($this->tabla_existe($tabla_actividad)) {
            return $wpdb->insert($tabla_actividad, [
                'colectivo_id'  => intval($colectivo_id),
                'usuario_id'    => $post->post_author ?: get_current_user_id(),
                'tipo'          => 'post_compartido',
                'titulo'        => $post->post_title,
                'descripcion'   => wp_trim_words($post->post_content, 50),
                'enlace'        => get_permalink($post->ID),
                'referencia_id' => $post->ID,
                'fecha'         => current_time('mysql'),
            ]);
        }

        return false;
    }

    /**
     * Integra con Evento
     */
    private function integrar_evento($post, $evento_id) {
        global $wpdb;

        // Usar tabla de relaciones nueva (prioridad)
        $tabla_posts = $wpdb->prefix . 'flavor_eventos_posts';
        if ($this->tabla_existe($tabla_posts)) {
            $resultado = $wpdb->insert($tabla_posts, [
                'evento_id' => intval($evento_id),
                'post_id'   => $post->ID,
                'tipo'      => 'noticia',
                'orden'     => 0,
                'fecha'     => current_time('mysql'),
            ]);
            if ($resultado) {
                update_post_meta($post->ID, '_flavor_evento_asociado', $evento_id);
                return $wpdb->insert_id;
            }
        }

        // Fallback a tabla de noticias del evento
        $tabla_noticias = $wpdb->prefix . 'flavor_eventos_noticias';
        if ($this->tabla_existe($tabla_noticias)) {
            return $wpdb->insert($tabla_noticias, [
                'evento_id'      => intval($evento_id),
                'post_id'        => $post->ID,
                'titulo'         => $post->post_title,
                'extracto'       => wp_trim_words($post->post_content, 30),
                'imagen'         => get_the_post_thumbnail_url($post->ID, 'medium') ?: '',
                'enlace'         => get_permalink($post->ID),
                'fecha_agregado' => current_time('mysql'),
            ]);
        }

        // Último fallback: post meta
        update_post_meta($post->ID, '_flavor_evento_asociado', $evento_id);
        return true;
    }

    /**
     * Integra con Foro (crear debate)
     */
    private function integrar_foro($post, $foro_id) {
        global $wpdb;
        $tabla_temas = $wpdb->prefix . 'flavor_foros_temas';

        if (!$this->tabla_existe($tabla_temas)) {
            return false;
        }

        // Crear tema de debate basado en el post
        $resultado = $wpdb->insert($tabla_temas, [
            'foro_id'        => intval($foro_id),
            'autor_id'       => $post->post_author ?: get_current_user_id(),
            'titulo'         => sprintf(__('Debate: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $post->post_title),
            'contenido'      => sprintf(
                __("Este debate ha sido creado automáticamente a partir del artículo:\n\n**%s**\n\n%s\n\n[Leer artículo completo](%s)", FLAVOR_PLATFORM_TEXT_DOMAIN),
                $post->post_title,
                wp_trim_words(strip_shortcodes($post->post_content), 100),
                get_permalink($post->ID)
            ),
            'post_origen_id' => $post->ID,
            'fecha_creacion' => current_time('mysql'),
            'estado'         => 'abierto',
            'fijado'         => 0,
        ]);

        if ($resultado) {
            // Guardar referencia del tema creado
            update_post_meta($post->ID, '_flavor_foro_tema_id', $wpdb->insert_id);
        }

        return $resultado;
    }

    /**
     * Integra con Curso
     */
    private function integrar_curso($post, $curso_id) {
        global $wpdb;

        // Usar tabla de relaciones nueva (prioridad)
        $tabla_posts = $wpdb->prefix . 'flavor_cursos_posts';
        if ($this->tabla_existe($tabla_posts)) {
            $resultado = $wpdb->insert($tabla_posts, [
                'curso_id'    => intval($curso_id),
                'post_id'     => $post->ID,
                'leccion_id'  => null,
                'tipo'        => 'recurso',
                'orden'       => 99,
                'obligatorio' => 0,
                'fecha'       => current_time('mysql'),
            ]);
            if ($resultado) {
                return $wpdb->insert_id;
            }
        }

        // Fallback a tabla de materiales
        $tabla_materiales = $wpdb->prefix . 'flavor_cursos_materiales';
        if ($this->tabla_existe($tabla_materiales)) {
            return $wpdb->insert($tabla_materiales, [
                'curso_id'       => intval($curso_id),
                'tipo'           => 'articulo_externo',
                'titulo'         => $post->post_title,
                'descripcion'    => wp_trim_words(strip_shortcodes($post->post_content), 50),
                'url'            => get_permalink($post->ID),
                'imagen'         => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: '',
                'post_id'        => $post->ID,
                'orden'          => 99,
                'fecha_agregado' => current_time('mysql'),
                'acceso'         => 'publico',
            ]);
        }

        // Fallback a tabla de recursos
        $tabla_recursos = $wpdb->prefix . 'flavor_cursos_recursos';
        if ($this->tabla_existe($tabla_recursos)) {
            return $wpdb->insert($tabla_recursos, [
                'curso_id'    => intval($curso_id),
                'tipo'        => 'articulo',
                'titulo'      => $post->post_title,
                'url'         => get_permalink($post->ID),
                'descripcion' => wp_trim_words($post->post_content, 30),
                'fecha'       => current_time('mysql'),
            ]);
        }

        return false;
    }

    /**
     * Integra con Taller
     */
    private function integrar_taller($post, $taller_id) {
        global $wpdb;

        // Usar tabla de relaciones nueva (prioridad)
        $tabla_posts = $wpdb->prefix . 'flavor_talleres_posts';
        if ($this->tabla_existe($tabla_posts)) {
            $resultado = $wpdb->insert($tabla_posts, [
                'taller_id' => intval($taller_id),
                'post_id'   => $post->ID,
                'tipo'      => 'recurso',
                'orden'     => 0,
                'fecha'     => current_time('mysql'),
            ]);
            if ($resultado) {
                return $wpdb->insert_id;
            }
        }

        // Fallback a tabla de recursos
        $tabla_recursos = $wpdb->prefix . 'flavor_talleres_recursos';
        if ($this->tabla_existe($tabla_recursos)) {
            return $wpdb->insert($tabla_recursos, [
                'taller_id'   => intval($taller_id),
                'tipo'        => 'articulo',
                'titulo'      => $post->post_title,
                'descripcion' => wp_trim_words($post->post_content, 30),
                'url'         => get_permalink($post->ID),
                'post_id'     => $post->ID,
                'fecha'       => current_time('mysql'),
            ]);
        }

        // Fallback a meta del taller
        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        if ($this->tabla_existe($tabla_talleres)) {
            $posts_asociados = $wpdb->get_var($wpdb->prepare(
                "SELECT posts_asociados FROM {$tabla_talleres} WHERE id = %d",
                $taller_id
            ));
            $posts_array = $posts_asociados ? json_decode($posts_asociados, true) : [];
            $posts_array[] = $post->ID;
            return $wpdb->update(
                $tabla_talleres,
                ['posts_asociados' => wp_json_encode(array_unique($posts_array))],
                ['id' => $taller_id]
            );
        }

        return false;
    }

    /**
     * Integra con Campaña
     */
    private function integrar_campania($post, $campania_id) {
        global $wpdb;

        // Usar tabla de relaciones nueva (prioridad)
        $tabla_posts = $wpdb->prefix . 'flavor_campanias_posts';
        if ($this->tabla_existe($tabla_posts)) {
            $resultado = $wpdb->insert($tabla_posts, [
                'campania_id' => intval($campania_id),
                'post_id'     => $post->ID,
                'tipo'        => 'apoyo',
                'destacado'   => 0,
                'fecha'       => current_time('mysql'),
            ]);
            if ($resultado) {
                return $wpdb->insert_id;
            }
        }

        // Fallback a tabla de contenidos
        $tabla_contenidos = $wpdb->prefix . 'flavor_campanias_contenidos';
        if ($this->tabla_existe($tabla_contenidos)) {
            return $wpdb->insert($tabla_contenidos, [
                'campania_id'    => intval($campania_id),
                'tipo'           => 'post',
                'post_id'        => $post->ID,
                'titulo'         => $post->post_title,
                'extracto'       => wp_trim_words($post->post_content, 30),
                'enlace'         => get_permalink($post->ID),
                'imagen'         => get_the_post_thumbnail_url($post->ID, 'medium') ?: '',
                'fecha_agregado' => current_time('mysql'),
            ]);
        }

        // Fallback a meta de campaña
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';
        if ($this->tabla_existe($tabla_campanias)) {
            $posts_ids = $wpdb->get_var($wpdb->prepare(
                "SELECT posts_relacionados FROM {$tabla_campanias} WHERE id = %d",
                $campania_id
            ));
            $posts_array = $posts_ids ? json_decode($posts_ids, true) : [];
            $posts_array[] = $post->ID;
            return $wpdb->update(
                $tabla_campanias,
                ['posts_relacionados' => wp_json_encode(array_unique($posts_array))],
                ['id' => $campania_id]
            );
        }

        return false;
    }

    /**
     * Integra con Biblioteca
     */
    private function integrar_biblioteca($post, $categoria_id) {
        global $wpdb;

        // Usar tabla de relaciones nueva (prioridad)
        $tabla_posts = $wpdb->prefix . 'flavor_biblioteca_posts';
        if ($this->tabla_existe($tabla_posts)) {
            $resultado = $wpdb->insert($tabla_posts, [
                'categoria_id' => intval($categoria_id) ?: null,
                'post_id'      => $post->ID,
                'usuario_id'   => $post->post_author ?: get_current_user_id(),
                'titulo'       => $post->post_title,
                'descripcion'  => wp_trim_words(strip_shortcodes($post->post_content), 100),
                'tipo'         => 'articulo',
                'destacado'    => 0,
                'vistas'       => 0,
                'fecha'        => current_time('mysql'),
            ]);
            if ($resultado) {
                return $wpdb->insert_id;
            }
        }

        // Fallback a tabla de recursos
        $tabla_recursos = $wpdb->prefix . 'flavor_biblioteca_recursos';
        if ($this->tabla_existe($tabla_recursos)) {
            return $wpdb->insert($tabla_recursos, [
                'categoria_id'   => intval($categoria_id) ?: null,
                'usuario_id'     => $post->post_author ?: get_current_user_id(),
                'tipo'           => 'articulo',
                'titulo'         => $post->post_title,
                'descripcion'    => wp_trim_words(strip_shortcodes($post->post_content), 100),
                'url_externa'    => get_permalink($post->ID),
                'imagen'         => get_the_post_thumbnail_url($post->ID, 'medium') ?: '',
                'post_id'        => $post->ID,
                'fecha_creacion' => current_time('mysql'),
                'estado'         => 'publicado',
                'descargas'      => 0,
            ]);
        }

        // Fallback a tabla principal
        $tabla_items = $wpdb->prefix . 'flavor_biblioteca';
        if ($this->tabla_existe($tabla_items)) {
            return $wpdb->insert($tabla_items, [
                'titulo'      => $post->post_title,
                'descripcion' => wp_trim_words(strip_shortcodes($post->post_content), 100),
                'tipo'        => 'articulo',
                'url'         => get_permalink($post->ID),
                'autor'       => get_the_author_meta('display_name', $post->post_author),
                'fecha'       => $post->post_date,
                'usuario_id'  => $post->post_author ?: get_current_user_id(),
                'estado'      => 'disponible',
            ]);
        }

        return false;
    }

    /**
     * Registra una integración exitosa
     */
    private function registrar_integracion($post_id, $modulo, $elemento_id, $resultado_id) {
        global $wpdb;

        // Registrar en tabla de log
        $tabla_log = $wpdb->prefix . 'flavor_posts_integraciones_log';
        if ($this->tabla_existe($tabla_log)) {
            $wpdb->insert($tabla_log, [
                'post_id'       => $post_id,
                'modulo'        => $modulo,
                'elemento_id'   => $elemento_id ? intval($elemento_id) : null,
                'elemento_tipo' => $modulo,
                'usuario_id'    => get_current_user_id(),
                'resultado_id'  => $resultado_id ? intval($resultado_id) : null,
                'estado'        => 'exito',
                'mensaje'       => null,
                'fecha'         => current_time('mysql'),
            ]);
        }

        // También guardar en post meta como backup
        $historial = get_post_meta($post_id, '_flavor_integraciones_historial', true) ?: [];
        $historial[] = [
            'modulo'      => $modulo,
            'elemento_id' => $elemento_id,
            'resultado'   => $resultado_id,
            'fecha'       => current_time('mysql'),
            'usuario'     => get_current_user_id(),
        ];
        update_post_meta($post_id, '_flavor_integraciones_historial', $historial);

        do_action('flavor_post_integrado_modulo', $post_id, $modulo, $elemento_id, $resultado_id);
    }

    /**
     * AJAX: Obtener elementos de un módulo
     */
    public function ajax_obtener_elementos() {
        check_ajax_referer('flavor_module_integrations', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $modulo = sanitize_key($_POST['modulo'] ?? '');
        if (empty($modulo)) {
            wp_send_json_error(['message' => __('Módulo no especificado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $elementos = $this->obtener_elementos_modulo($modulo);
        wp_send_json_success(['elementos' => $elementos]);
    }

    /**
     * AJAX: Integrar post con un módulo
     */
    public function ajax_integrar_post() {
        check_ajax_referer('flavor_module_integrations', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $modulo = sanitize_key($_POST['modulo'] ?? '');
        $elemento_id = sanitize_text_field($_POST['elemento_id'] ?? '');

        if (!$post_id || !$modulo) {
            wp_send_json_error(['message' => __('Datos incompletos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $resultado = $this->ejecutar_integracion($modulo, $post_id, $elemento_id);

        if ($resultado) {
            wp_send_json_success([
                'message' => sprintf(
                    __('Post integrado con %s correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $this->modulos_disponibles[$modulo]['nombre'] ?? $modulo
                ),
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Error al integrar. Verifica que el módulo esté configurado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }
    }

    /**
     * Carga assets en admin
     */
    public function cargar_assets_admin($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        global $post;
        if (!$post || !in_array($post->post_type, $this->post_types_habilitados)) {
            return;
        }

        wp_enqueue_script(
            'flavor-module-integrations-admin',
            FLAVOR_PLATFORM_URL . 'assets/js/wp-module-integrations.js',
            ['jquery'],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-module-integrations-admin', 'flavorModuleIntegrations', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_module_integrations'),
            'postId'  => $post->ID,
            'i18n'    => [
                'integrando'        => __('Integrando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'integrado'         => __('¡Integrado!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error'             => __('Error al integrar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'seleccionar'       => __('Selecciona al menos una integración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarEliminar' => __('¿Eliminar esta integración?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'eliminando'        => __('Eliminando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'eliminado'         => __('¡Eliminado!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'errorEliminar'     => __('Error al eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando'          => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sinIntegraciones'  => __('Sin integraciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Carga assets en frontend
     */
    public function cargar_assets_frontend() {
        if (!is_singular($this->post_types_habilitados)) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        // Los estilos se añaden al portal.css
        // JavaScript extendido
        wp_enqueue_script(
            'flavor-module-integrations-frontend',
            FLAVOR_PLATFORM_URL . 'assets/js/wp-module-integrations-frontend.js',
            ['jquery', 'flavor-social-share-frontend'],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-module-integrations-frontend', 'flavorModuleIntegrationsFront', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('flavor_module_integrations'),
            'modulos'   => $this->modulos_disponibles,
            'postId'    => get_the_ID(),
        ]);
    }

    /**
     * Renderiza opciones adicionales en el modal de compartir
     */
    public function renderizar_opciones_modal($post_id) {
        if (empty($this->modulos_disponibles)) {
            return;
        }
        ?>
        <div class="flavor-integraciones-adicionales">
            <h4 class="flavor-integraciones-titulo">
                <span class="dashicons dashicons-admin-plugins"></span>
                <?php esc_html_e('Integrar también con:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h4>

            <div class="flavor-integraciones-grid">
                <?php foreach ($this->modulos_disponibles as $key => $modulo): ?>
                    <label class="flavor-integracion-option">
                        <input type="checkbox"
                               name="integraciones[<?php echo esc_attr($key); ?>]"
                               value="1"
                               class="flavor-integracion-check">
                        <span class="dashicons dashicons-<?php echo esc_attr($modulo['icono']); ?>"></span>
                        <span class="flavor-option-label"><?php echo esc_html($modulo['nombre']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene los módulos disponibles
     */
    public function get_modulos_disponibles() {
        return $this->modulos_disponibles;
    }

    // =====================================================
    // CONSULTAS Y SHORTCODES
    // =====================================================

    /**
     * Obtiene posts integrados con un elemento de un módulo
     *
     * @param string $modulo Nombre del módulo
     * @param int $elemento_id ID del elemento (evento, curso, etc.)
     * @param int $limite Límite de resultados
     * @return array Posts integrados
     */
    public static function obtener_posts_integrados($modulo, $elemento_id, $limite = 10) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tabla_map = [
            'eventos'         => 'eventos_posts',
            'cursos'          => 'cursos_posts',
            'talleres'        => 'talleres_posts',
            'campanias'       => 'campanias_posts',
            'comunidades'     => 'comunidades_posts',
            'colectivos'      => 'colectivos_posts',
            'email_marketing' => 'email_newsletter_posts',
            'biblioteca'      => 'biblioteca_posts',
        ];

        $campo_map = [
            'eventos'         => 'evento_id',
            'cursos'          => 'curso_id',
            'talleres'        => 'taller_id',
            'campanias'       => 'campania_id',
            'comunidades'     => 'comunidad_id',
            'colectivos'      => 'colectivo_id',
            'email_marketing' => 'newsletter_id',
            'biblioteca'      => 'categoria_id',
        ];

        if (!isset($tabla_map[$modulo])) {
            return [];
        }

        $tabla = $prefix . $tabla_map[$modulo];
        $campo = $campo_map[$modulo];

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return [];
        }

        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$tabla} WHERE {$campo} = %d ORDER BY fecha DESC LIMIT %d",
            $elemento_id,
            $limite
        ));

        if (empty($post_ids)) {
            return [];
        }

        return get_posts([
            'post__in'       => $post_ids,
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'posts_per_page' => $limite,
            'orderby'        => 'post__in',
        ]);
    }

    /**
     * Obtiene las integraciones de un post
     *
     * @param int $post_id ID del post
     * @param bool $usar_cache Si usar caché
     * @return array Integraciones del post
     */
    public static function obtener_integraciones_post($post_id, $usar_cache = true) {
        // Verificar caché en memoria
        $cache_key = 'integraciones_post_' . $post_id;
        if ($usar_cache && isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        // Verificar transient
        if ($usar_cache) {
            $cached = get_transient('flavor_int_' . md5($cache_key));
            if ($cached !== false) {
                self::$cache[$cache_key] = $cached;
                return $cached;
            }
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $integraciones = [];

        // Verificar en cada tabla de relaciones
        $tablas = [
            'eventos'         => ['tabla' => 'eventos_posts', 'campo' => 'evento_id', 'nombre_tabla' => 'eventos'],
            'cursos'          => ['tabla' => 'cursos_posts', 'campo' => 'curso_id', 'nombre_tabla' => 'cursos'],
            'talleres'        => ['tabla' => 'talleres_posts', 'campo' => 'taller_id', 'nombre_tabla' => 'talleres'],
            'campanias'       => ['tabla' => 'campanias_posts', 'campo' => 'campania_id', 'nombre_tabla' => 'campanias'],
            'comunidades'     => ['tabla' => 'comunidades_posts', 'campo' => 'comunidad_id', 'nombre_tabla' => 'comunidades'],
            'colectivos'      => ['tabla' => 'colectivos_posts', 'campo' => 'colectivo_id', 'nombre_tabla' => 'colectivos'],
            'email_marketing' => ['tabla' => 'email_newsletter_posts', 'campo' => 'newsletter_id', 'nombre_tabla' => 'email_campaigns'],
            'biblioteca'      => ['tabla' => 'biblioteca_posts', 'campo' => 'categoria_id', 'nombre_tabla' => 'biblioteca_categorias'],
        ];

        foreach ($tablas as $modulo => $config) {
            $tabla_relacion = $prefix . $config['tabla'];

            if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_relacion}'") !== $tabla_relacion) {
                continue;
            }

            $relaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_relacion} WHERE post_id = %d",
                $post_id
            ));

            if (!empty($relaciones)) {
                foreach ($relaciones as $rel) {
                    $elemento_id = $rel->{$config['campo']} ?? null;
                    $nombre_elemento = '';

                    // Obtener nombre del elemento
                    if ($elemento_id) {
                        $tabla_elemento = $prefix . $config['nombre_tabla'];
                        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_elemento}'") === $tabla_elemento) {
                            $campo_nombre = in_array($modulo, ['comunidades', 'colectivos', 'biblioteca']) ? 'nombre' : 'titulo';
                            $nombre_elemento = $wpdb->get_var($wpdb->prepare(
                                "SELECT {$campo_nombre} FROM {$tabla_elemento} WHERE id = %d",
                                $elemento_id
                            ));
                        }
                    }

                    $integraciones[] = [
                        'modulo'          => $modulo,
                        'elemento_id'     => $elemento_id,
                        'nombre_elemento' => $nombre_elemento,
                        'fecha'           => $rel->fecha ?? '',
                    ];
                }
            }
        }

        // Guardar en caché
        if ($usar_cache && !empty($integraciones)) {
            self::$cache[$cache_key] = $integraciones;
            set_transient('flavor_int_' . md5($cache_key), $integraciones, 300);
        }

        return $integraciones;
    }

    /**
     * Registra shortcodes
     */
    public function registrar_shortcodes() {
        add_shortcode('flavor_posts_modulo', [$this, 'shortcode_posts_modulo']);
        add_shortcode('flavor_integraciones_post', [$this, 'shortcode_integraciones_post']);
        add_shortcode('flavor_stats_integraciones', [$this, 'shortcode_stats_integraciones']);
        add_shortcode('flavor_modulos_disponibles', [$this, 'shortcode_modulos_disponibles']);
    }

    /**
     * Shortcode: Mostrar posts de un módulo
     * [flavor_posts_modulo modulo="eventos" id="123" limite="5" estilo="lista|grid|cards" columnas="3"]
     */
    public function shortcode_posts_modulo($atts) {
        $atts = shortcode_atts([
            'modulo'       => '',
            'id'           => 0,
            'limite'       => 5,
            'titulo'       => '',
            'class'        => 'flavor-posts-modulo',
            'estilo'       => 'lista',  // lista, grid, cards
            'columnas'     => 3,
            'mostrar_thumb' => 'true',
            'mostrar_fecha' => 'false',
            'mostrar_excerpt' => 'false',
            'vacio_texto'  => '',
        ], $atts);

        if (empty($atts['modulo']) || !$atts['id']) {
            return '';
        }

        $posts = self::obtener_posts_integrados($atts['modulo'], intval($atts['id']), intval($atts['limite']));

        if (empty($posts)) {
            if ($atts['vacio_texto']) {
                return '<p class="flavor-posts-vacio">' . esc_html($atts['vacio_texto']) . '</p>';
            }
            return '';
        }

        $mostrar_thumb = filter_var($atts['mostrar_thumb'], FILTER_VALIDATE_BOOLEAN);
        $mostrar_fecha = filter_var($atts['mostrar_fecha'], FILTER_VALIDATE_BOOLEAN);
        $mostrar_excerpt = filter_var($atts['mostrar_excerpt'], FILTER_VALIDATE_BOOLEAN);

        $classes = [$atts['class'], 'flavor-posts-' . $atts['estilo']];
        $html = '<div class="' . esc_attr(implode(' ', $classes)) . '"';
        if ($atts['estilo'] === 'grid' || $atts['estilo'] === 'cards') {
            $html .= ' style="--flavor-columns:' . intval($atts['columnas']) . ';"';
        }
        $html .= '>';

        if ($atts['titulo']) {
            $html .= '<h4 class="flavor-posts-modulo-titulo">' . esc_html($atts['titulo']) . '</h4>';
        }

        $tag = ($atts['estilo'] === 'lista') ? 'ul' : 'div';
        $html .= "<{$tag} class=\"flavor-posts-lista\">";

        foreach ($posts as $post) {
            $item_tag = ($atts['estilo'] === 'lista') ? 'li' : 'article';
            $html .= "<{$item_tag} class=\"flavor-post-item\">";
            $html .= '<a href="' . esc_url(get_permalink($post)) . '">';

            if ($mostrar_thumb && has_post_thumbnail($post)) {
                $thumb_size = ($atts['estilo'] === 'cards') ? 'medium' : 'thumbnail';
                $html .= '<span class="flavor-post-thumb">' . get_the_post_thumbnail($post, $thumb_size) . '</span>';
            }

            $html .= '<div class="flavor-post-content">';
            $html .= '<span class="flavor-post-title">' . esc_html($post->post_title) . '</span>';

            if ($mostrar_fecha) {
                $html .= '<span class="flavor-post-date">' . esc_html(get_the_date('', $post)) . '</span>';
            }

            if ($mostrar_excerpt && $post->post_excerpt) {
                $html .= '<span class="flavor-post-excerpt">' . esc_html(wp_trim_words($post->post_excerpt, 15)) . '</span>';
            }

            $html .= '</div>';
            $html .= '</a>';
            $html .= "</{$item_tag}>";
        }

        $html .= "</{$tag}>";
        $html .= '</div>';

        // Estilos inline si no se han cargado
        $html .= $this->get_shortcode_styles();

        return $html;
    }

    /**
     * Devuelve estilos CSS para shortcodes
     */
    private function get_shortcode_styles() {
        static $styles_added = false;
        if ($styles_added) {
            return '';
        }
        $styles_added = true;

        return '
        <style>
            .flavor-posts-grid .flavor-posts-lista,
            .flavor-posts-cards .flavor-posts-lista {
                display: grid;
                grid-template-columns: repeat(var(--flavor-columns, 3), 1fr);
                gap: 20px;
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .flavor-posts-lista { list-style: none; padding: 0; }
            .flavor-post-item { margin-bottom: 12px; }
            .flavor-post-item a {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                text-decoration: none;
                color: inherit;
            }
            .flavor-posts-cards .flavor-post-item {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                overflow: hidden;
                transition: box-shadow 0.2s;
            }
            .flavor-posts-cards .flavor-post-item:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .flavor-posts-cards .flavor-post-item a {
                flex-direction: column;
                gap: 0;
            }
            .flavor-posts-cards .flavor-post-thumb img {
                width: 100%;
                height: 160px;
                object-fit: cover;
            }
            .flavor-posts-cards .flavor-post-content {
                padding: 16px;
            }
            .flavor-post-thumb img {
                width: 60px;
                height: 60px;
                object-fit: cover;
                border-radius: 6px;
            }
            .flavor-post-title {
                font-weight: 600;
                display: block;
            }
            .flavor-post-date {
                font-size: 12px;
                color: #6b7280;
                display: block;
                margin-top: 4px;
            }
            .flavor-post-excerpt {
                font-size: 13px;
                color: #6b7280;
                display: block;
                margin-top: 6px;
            }
            @media (max-width: 768px) {
                .flavor-posts-grid .flavor-posts-lista,
                .flavor-posts-cards .flavor-posts-lista {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (max-width: 480px) {
                .flavor-posts-grid .flavor-posts-lista,
                .flavor-posts-cards .flavor-posts-lista {
                    grid-template-columns: 1fr;
                }
            }
        </style>';
    }

    /**
     * Shortcode: Mostrar integraciones de un post
     * [flavor_integraciones_post id="123"]
     */
    public function shortcode_integraciones_post($atts) {
        $atts = shortcode_atts([
            'id'    => get_the_ID(),
            'class' => 'flavor-integraciones-post',
        ], $atts);

        $integraciones = self::obtener_integraciones_post(intval($atts['id']));

        if (empty($integraciones)) {
            return '';
        }

        $iconos = [
            'eventos'         => 'calendar-alt',
            'cursos'          => 'welcome-learn-more',
            'talleres'        => 'hammer',
            'campanias'       => 'megaphone',
            'comunidades'     => 'groups',
            'colectivos'      => 'buddicons-groups',
            'email_marketing' => 'email-alt',
            'biblioteca'      => 'book',
        ];

        $html = '<div class="' . esc_attr($atts['class']) . '">';
        $html .= '<span class="flavor-integraciones-label">' . esc_html__('Integrado en:', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span>';
        $html .= '<div class="flavor-integraciones-badges">';

        foreach ($integraciones as $int) {
            $icono = $iconos[$int['modulo']] ?? 'admin-plugins';
            $html .= '<span class="flavor-integracion-badge">';
            $html .= '<span class="dashicons dashicons-' . esc_attr($icono) . '"></span>';
            $html .= '<span class="flavor-badge-text">' . esc_html($int['nombre_elemento'] ?: ucfirst($int['modulo'])) . '</span>';
            $html .= '</span>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Shortcode: Mostrar estadísticas de integraciones
     * [flavor_stats_integraciones estilo="grid|compact"]
     */
    public function shortcode_stats_integraciones($atts) {
        $atts = shortcode_atts([
            'estilo' => 'grid',
            'class'  => 'flavor-stats-integraciones',
        ], $atts);

        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tablas = [
            'eventos'         => ['tabla' => 'eventos_posts', 'icono' => 'calendar-alt'],
            'cursos'          => ['tabla' => 'cursos_posts', 'icono' => 'welcome-learn-more'],
            'talleres'        => ['tabla' => 'talleres_posts', 'icono' => 'hammer'],
            'campanias'       => ['tabla' => 'campanias_posts', 'icono' => 'megaphone'],
            'comunidades'     => ['tabla' => 'comunidades_posts', 'icono' => 'groups'],
            'colectivos'      => ['tabla' => 'colectivos_posts', 'icono' => 'buddicons-groups'],
            'email_marketing' => ['tabla' => 'email_newsletter_posts', 'icono' => 'email-alt'],
            'biblioteca'      => ['tabla' => 'biblioteca_posts', 'icono' => 'book'],
        ];

        $stats = [];
        foreach ($tablas as $modulo => $config) {
            if (!isset($this->modulos_disponibles[$modulo])) {
                continue;
            }
            $tabla = $prefix . $config['tabla'];
            if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") === $tabla) {
                $count = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$tabla}");
                if ($count > 0) {
                    $stats[$modulo] = [
                        'count'  => $count,
                        'icono'  => $config['icono'],
                        'nombre' => $this->modulos_disponibles[$modulo]['nombre'],
                    ];
                }
            }
        }

        if (empty($stats)) {
            return '';
        }

        $is_grid = $atts['estilo'] === 'grid';
        $html = '<div class="' . esc_attr($atts['class']) . ' flavor-stats-' . esc_attr($atts['estilo']) . '">';

        foreach ($stats as $modulo => $data) {
            if ($is_grid) {
                $html .= '<div class="flavor-stat-item">';
                $html .= '<span class="dashicons dashicons-' . esc_attr($data['icono']) . '"></span>';
                $html .= '<span class="flavor-stat-count">' . intval($data['count']) . '</span>';
                $html .= '<span class="flavor-stat-label">' . esc_html($data['nombre']) . '</span>';
                $html .= '</div>';
            } else {
                $html .= '<span class="flavor-stat-compact">';
                $html .= '<span class="dashicons dashicons-' . esc_attr($data['icono']) . '"></span>';
                $html .= '<strong>' . intval($data['count']) . '</strong> ' . esc_html($data['nombre']);
                $html .= '</span>';
            }
        }

        $html .= '</div>';

        // Estilos
        $html .= '
        <style>
            .flavor-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 16px;
            }
            .flavor-stat-item {
                text-align: center;
                padding: 20px;
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border-radius: 12px;
                border: 1px solid #e2e8f0;
            }
            .flavor-stat-item .dashicons {
                font-size: 28px;
                width: 28px;
                height: 28px;
                color: var(--flavor-primary, #6366f1);
                display: block;
                margin: 0 auto 8px;
            }
            .flavor-stat-count {
                font-size: 32px;
                font-weight: 700;
                display: block;
                color: #1e293b;
            }
            .flavor-stat-label {
                font-size: 12px;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .flavor-stats-compact {
                display: flex;
                flex-wrap: wrap;
                gap: 16px;
            }
            .flavor-stat-compact {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 14px;
                background: #f1f5f9;
                border-radius: 20px;
                font-size: 13px;
            }
            .flavor-stat-compact .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
        </style>';

        return $html;
    }

    /**
     * Shortcode: Mostrar módulos disponibles para integración
     * [flavor_modulos_disponibles mostrar_descripcion="true"]
     */
    public function shortcode_modulos_disponibles($atts) {
        $atts = shortcode_atts([
            'class'               => 'flavor-modulos-disponibles',
            'mostrar_descripcion' => 'true',
        ], $atts);

        $modulos = $this->get_modulos_para_usuario();

        if (empty($modulos)) {
            return '<p class="flavor-no-modulos">' . esc_html__('No hay módulos de integración disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $mostrar_desc = filter_var($atts['mostrar_descripcion'], FILTER_VALIDATE_BOOLEAN);

        $html = '<div class="' . esc_attr($atts['class']) . '">';

        foreach ($modulos as $key => $modulo) {
            $html .= '<div class="flavor-modulo-item">';
            $html .= '<span class="dashicons dashicons-' . esc_attr($modulo['icono']) . '"></span>';
            $html .= '<div class="flavor-modulo-info">';
            $html .= '<strong>' . esc_html($modulo['nombre']) . '</strong>';
            if ($mostrar_desc && !empty($modulo['descripcion'])) {
                $html .= '<span class="flavor-modulo-desc">' . esc_html($modulo['descripcion']) . '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        $html .= '
        <style>
            .flavor-modulos-disponibles {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .flavor-modulo-item {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 14px;
                background: #f8fafc;
                border-radius: 10px;
                border: 1px solid #e2e8f0;
            }
            .flavor-modulo-item .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                color: var(--flavor-primary, #6366f1);
                flex-shrink: 0;
            }
            .flavor-modulo-info {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            .flavor-modulo-desc {
                font-size: 13px;
                color: #64748b;
            }
        </style>';

        return $html;
    }

    // =====================================================
    // COLUMNAS EN ADMIN
    // =====================================================

    /**
     * Registra hooks para columnas en admin
     */
    public function registrar_columnas_admin() {
        foreach ($this->post_types_habilitados as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", [$this, 'agregar_columna_integraciones']);
            add_action("manage_{$post_type}_posts_custom_column", [$this, 'renderizar_columna_integraciones'], 10, 2);
        }
    }

    /**
     * Agrega columna de integraciones
     */
    public function agregar_columna_integraciones($columns) {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['flavor_integraciones'] = __('Integraciones', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }
        }
        return $new_columns;
    }

    /**
     * Renderiza el contenido de la columna
     */
    public function renderizar_columna_integraciones($column, $post_id) {
        if ($column !== 'flavor_integraciones') {
            return;
        }

        $integraciones = self::obtener_integraciones_post($post_id);

        if (empty($integraciones)) {
            echo '<span class="flavor-col-none">—</span>';
            return;
        }

        $iconos = [
            'eventos'         => 'calendar-alt',
            'cursos'          => 'welcome-learn-more',
            'talleres'        => 'hammer',
            'campanias'       => 'megaphone',
            'comunidades'     => 'groups',
            'colectivos'      => 'buddicons-groups',
            'email_marketing' => 'email-alt',
            'biblioteca'      => 'book',
            'foros'           => 'format-chat',
        ];

        echo '<div class="flavor-col-integraciones">';
        foreach ($integraciones as $int) {
            $icono = $iconos[$int['modulo']] ?? 'admin-plugins';
            $titulo = $int['nombre_elemento'] ?: ucfirst(str_replace('_', ' ', $int['modulo']));
            printf(
                '<span class="flavor-col-badge" title="%s"><span class="dashicons dashicons-%s"></span></span>',
                esc_attr($titulo),
                esc_attr($icono)
            );
        }
        echo '</div>';
    }

    // =====================================================
    // DESHACER INTEGRACIONES
    // =====================================================

    /**
     * Elimina una integración específica
     *
     * @param int $post_id ID del post
     * @param string $modulo Módulo de la integración
     * @param int $elemento_id ID del elemento (opcional)
     * @return bool
     */
    public static function eliminar_integracion($post_id, $modulo, $elemento_id = null) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tabla_map = [
            'eventos'         => ['tabla' => 'eventos_posts', 'campo' => 'evento_id'],
            'cursos'          => ['tabla' => 'cursos_posts', 'campo' => 'curso_id'],
            'talleres'        => ['tabla' => 'talleres_posts', 'campo' => 'taller_id'],
            'campanias'       => ['tabla' => 'campanias_posts', 'campo' => 'campania_id'],
            'comunidades'     => ['tabla' => 'comunidades_posts', 'campo' => 'comunidad_id'],
            'colectivos'      => ['tabla' => 'colectivos_posts', 'campo' => 'colectivo_id'],
            'email_marketing' => ['tabla' => 'email_newsletter_posts', 'campo' => 'newsletter_id'],
            'biblioteca'      => ['tabla' => 'biblioteca_posts', 'campo' => 'categoria_id'],
        ];

        if (!isset($tabla_map[$modulo])) {
            return false;
        }

        $config = $tabla_map[$modulo];
        $tabla = $prefix . $config['tabla'];

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return false;
        }

        $where = ['post_id' => $post_id];
        if ($elemento_id) {
            $where[$config['campo']] = $elemento_id;
        }

        $resultado = $wpdb->delete($tabla, $where);

        if ($resultado) {
            // Actualizar historial en post meta
            $historial = get_post_meta($post_id, '_flavor_integraciones_historial', true) ?: [];
            $historial = array_filter($historial, function($h) use ($modulo, $elemento_id) {
                if ($elemento_id) {
                    return !($h['modulo'] === $modulo && $h['elemento_id'] == $elemento_id);
                }
                return $h['modulo'] !== $modulo;
            });
            update_post_meta($post_id, '_flavor_integraciones_historial', array_values($historial));

            // Registrar en log
            $tabla_log = $prefix . 'posts_integraciones_log';
            if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_log}'") === $tabla_log) {
                $wpdb->insert($tabla_log, [
                    'post_id'       => $post_id,
                    'modulo'        => $modulo,
                    'elemento_id'   => $elemento_id,
                    'usuario_id'    => get_current_user_id(),
                    'estado'        => 'eliminado',
                    'mensaje'       => 'Integración eliminada manualmente',
                    'fecha'         => current_time('mysql'),
                ]);
            }

            do_action('flavor_integracion_eliminada', $post_id, $modulo, $elemento_id);
        }

        return $resultado !== false;
    }

    /**
     * AJAX: Eliminar integración
     */
    public function ajax_eliminar_integracion() {
        check_ajax_referer('flavor_module_integrations', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $modulo = sanitize_key($_POST['modulo'] ?? '');
        $elemento_id = intval($_POST['elemento_id'] ?? 0);

        if (!$post_id || !$modulo) {
            wp_send_json_error(['message' => __('Datos incompletos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $resultado = self::eliminar_integracion($post_id, $modulo, $elemento_id ?: null);

        if ($resultado) {
            wp_send_json_success(['message' => __('Integración eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(['message' => __('Error al eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Obtener historial de integraciones de un post
     */
    public function ajax_obtener_historial() {
        check_ajax_referer('flavor_module_integrations', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(['message' => __('Post ID requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $integraciones = self::obtener_integraciones_post($post_id);

        if (empty($integraciones)) {
            wp_send_json_success([
                'html' => '<p class="flavor-historial-vacio">' .
                          esc_html__('Sin integraciones activas', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                          '</p>'
            ]);
            return;
        }

        ob_start();
        ?>
        <div class="flavor-historial-lista">
            <?php foreach ($integraciones as $integracion):
                $modulo_info = $this->modulos_disponibles[$integracion['modulo']] ?? null;
                if (!$modulo_info) continue;

                $elemento_nombre = $this->obtener_nombre_elemento(
                    $integracion['modulo'],
                    $integracion['elemento_id']
                );
            ?>
            <div class="flavor-historial-item">
                <span class="dashicons dashicons-<?php echo esc_attr($modulo_info['icono']); ?>"></span>
                <div class="flavor-historial-info">
                    <strong><?php echo esc_html($modulo_info['nombre']); ?></strong>
                    <?php if ($elemento_nombre): ?>
                        <span class="flavor-historial-elemento"><?php echo esc_html($elemento_nombre); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($integracion['fecha'])): ?>
                        <span class="flavor-historial-fecha">
                            <?php echo esc_html(human_time_diff(strtotime($integracion['fecha']), current_time('timestamp'))); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <button type="button"
                        class="flavor-eliminar-integracion button-link"
                        data-post-id="<?php echo esc_attr($post_id); ?>"
                        data-modulo="<?php echo esc_attr($integracion['modulo']); ?>"
                        data-elemento-id="<?php echo esc_attr($integracion['elemento_id']); ?>"
                        title="<?php esc_attr_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <style>
            .flavor-historial-lista { margin-top: 10px; }
            .flavor-historial-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px;
                background: #f9f9f9;
                border-radius: 4px;
                margin-bottom: 6px;
            }
            .flavor-historial-item .dashicons { color: #2271b1; flex-shrink: 0; }
            .flavor-historial-info { flex: 1; font-size: 12px; }
            .flavor-historial-info strong { display: block; }
            .flavor-historial-elemento { color: #666; }
            .flavor-historial-fecha { color: #999; font-size: 11px; }
            .flavor-eliminar-integracion {
                color: #a00;
                padding: 2px;
                cursor: pointer;
            }
            .flavor-eliminar-integracion:hover { color: #dc3232; }
            .flavor-historial-vacio {
                color: #666;
                font-style: italic;
                padding: 10px 0;
            }
        </style>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Obtiene el nombre de un elemento de módulo
     */
    private function obtener_nombre_elemento($modulo, $elemento_id) {
        if (!$elemento_id) return null;

        $elementos = $this->obtener_elementos_modulo($modulo, 100);
        foreach ($elementos as $elemento) {
            if ((int)$elemento['id'] === (int)$elemento_id) {
                return $elemento['titulo'];
            }
        }
        return null;
    }

    // =====================================================
    // API REST
    // =====================================================

    /**
     * Registra endpoints de API REST
     */
    public function registrar_api_rest() {
        register_rest_route('flavor/v1', '/posts/(?P<id>\d+)/integraciones', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_integraciones'],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
            ],
        ]);

        register_rest_route('flavor/v1', '/modulos/(?P<modulo>[a-z_]+)/(?P<id>\d+)/posts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_posts_modulo'],
            'permission_callback' => '__return_true',
            'args'                => [
                'modulo' => [
                    'validate_callback' => function($param) {
                        return preg_match('/^[a-z_]+$/', $param);
                    }
                ],
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
            ],
        ]);

        register_rest_route('flavor/v1', '/posts/(?P<id>\d+)/integrar', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_integrar_post'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ]);

        register_rest_route('flavor/v1', '/integraciones/estadisticas', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_estadisticas'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ]);
    }

    /**
     * API: Obtener integraciones de un post
     */
    public function api_obtener_integraciones($request) {
        $post_id = $request->get_param('id');
        $integraciones = self::obtener_integraciones_post($post_id);

        return rest_ensure_response([
            'post_id'       => $post_id,
            'integraciones' => $integraciones,
            'total'         => count($integraciones),
        ]);
    }

    /**
     * API: Obtener posts de un módulo
     */
    public function api_obtener_posts_modulo($request) {
        $modulo = $request->get_param('modulo');
        $elemento_id = $request->get_param('id');
        $limite = $request->get_param('limite') ?: 20;

        $posts = self::obtener_posts_integrados($modulo, $elemento_id, $limite);

        $resultado = [];
        foreach ($posts as $post) {
            $resultado[] = [
                'id'        => $post->ID,
                'titulo'    => $post->post_title,
                'extracto'  => wp_trim_words($post->post_content, 30),
                'enlace'    => get_permalink($post),
                'imagen'    => get_the_post_thumbnail_url($post, 'medium') ?: '',
                'fecha'     => $post->post_date,
                'autor'     => get_the_author_meta('display_name', $post->post_author),
            ];
        }

        return rest_ensure_response([
            'modulo'      => $modulo,
            'elemento_id' => $elemento_id,
            'posts'       => $resultado,
            'total'       => count($resultado),
        ]);
    }

    /**
     * API: Integrar un post con un módulo
     */
    public function api_integrar_post($request) {
        $post_id = $request->get_param('id');
        $modulo = sanitize_key($request->get_param('modulo'));
        $elemento_id = sanitize_text_field($request->get_param('elemento_id'));

        if (!$modulo) {
            return new WP_Error('missing_modulo', __('Módulo requerido', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        $instance = self::get_instance();
        $resultado = $instance->ejecutar_integracion($modulo, $post_id, $elemento_id);

        if ($resultado) {
            return rest_ensure_response([
                'success' => true,
                'message' => __('Post integrado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'post_id' => $post_id,
                'modulo'  => $modulo,
            ]);
        }

        return new WP_Error('integration_failed', __('Error al integrar', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 500]);
    }

    /**
     * API: Estadísticas de integraciones
     */
    public function api_estadisticas($request) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $estadisticas = [];
        $tablas = [
            'eventos'         => 'eventos_posts',
            'cursos'          => 'cursos_posts',
            'talleres'        => 'talleres_posts',
            'campanias'       => 'campanias_posts',
            'comunidades'     => 'comunidades_posts',
            'colectivos'      => 'colectivos_posts',
            'email_marketing' => 'email_newsletter_posts',
            'biblioteca'      => 'biblioteca_posts',
        ];

        $total_global = 0;

        foreach ($tablas as $modulo => $tabla_nombre) {
            $tabla = $prefix . $tabla_nombre;
            if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") === $tabla) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}");
                $estadisticas[$modulo] = intval($count);
                $total_global += intval($count);
            } else {
                $estadisticas[$modulo] = 0;
            }
        }

        // Posts únicos integrados
        $posts_unicos = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$prefix}posts_integraciones_log WHERE estado = 'exito'"
        ) ?: 0;

        return rest_ensure_response([
            'por_modulo'    => $estadisticas,
            'total'         => $total_global,
            'posts_unicos'  => intval($posts_unicos),
            'fecha'         => current_time('c'),
        ]);
    }

    // =====================================================
    // WIDGET DE DASHBOARD
    // =====================================================

    /**
     * Registra widget de dashboard
     */
    public function registrar_widget_dashboard() {
        wp_add_dashboard_widget(
            'flavor_integraciones_widget',
            __('Integraciones de Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [$this, 'renderizar_widget_dashboard']
        );
    }

    /**
     * Renderiza el widget de dashboard
     */
    public function renderizar_widget_dashboard() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $iconos = [
            'eventos'         => 'calendar-alt',
            'cursos'          => 'welcome-learn-more',
            'talleres'        => 'hammer',
            'campanias'       => 'megaphone',
            'comunidades'     => 'groups',
            'colectivos'      => 'buddicons-groups',
            'email_marketing' => 'email-alt',
            'biblioteca'      => 'book',
        ];

        $tablas = [
            'eventos'         => 'eventos_posts',
            'cursos'          => 'cursos_posts',
            'talleres'        => 'talleres_posts',
            'campanias'       => 'campanias_posts',
            'comunidades'     => 'comunidades_posts',
            'colectivos'      => 'colectivos_posts',
            'email_marketing' => 'email_newsletter_posts',
            'biblioteca'      => 'biblioteca_posts',
        ];

        echo '<div class="flavor-dashboard-integraciones">';
        echo '<div class="flavor-dash-grid">';

        $hay_datos = false;
        foreach ($tablas as $modulo => $tabla_nombre) {
            if (!isset($this->modulos_disponibles[$modulo])) {
                continue;
            }

            $tabla = $prefix . $tabla_nombre;
            $count = 0;
            if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") === $tabla) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}");
            }

            if ($count > 0) {
                $hay_datos = true;
            }

            $nombre = $this->modulos_disponibles[$modulo]['nombre'] ?? ucfirst($modulo);
            $icono = $iconos[$modulo] ?? 'admin-plugins';

            printf(
                '<div class="flavor-dash-item">
                    <span class="dashicons dashicons-%s"></span>
                    <span class="flavor-dash-count">%d</span>
                    <span class="flavor-dash-label">%s</span>
                </div>',
                esc_attr($icono),
                intval($count),
                esc_html($nombre)
            );
        }

        echo '</div>';

        if (!$hay_datos) {
            echo '<p class="flavor-dash-empty">' . esc_html__('Aún no hay posts integrados con módulos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        // Últimas integraciones
        $tabla_log = $prefix . 'posts_integraciones_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_log}'") === $tabla_log) {
            $ultimas = $wpdb->get_results(
                "SELECT l.*, p.post_title
                 FROM {$tabla_log} l
                 LEFT JOIN {$wpdb->posts} p ON l.post_id = p.ID
                 WHERE l.estado = 'exito'
                 ORDER BY l.fecha DESC
                 LIMIT 5"
            );

            if (!empty($ultimas)) {
                echo '<h4 style="margin-top:16px;">' . esc_html__('Últimas integraciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h4>';
                echo '<ul class="flavor-dash-recientes">';
                foreach ($ultimas as $log) {
                    $icono = $iconos[$log->modulo] ?? 'admin-plugins';
                    printf(
                        '<li>
                            <span class="dashicons dashicons-%s"></span>
                            <a href="%s">%s</a>
                            <span class="flavor-dash-fecha">%s</span>
                        </li>',
                        esc_attr($icono),
                        esc_url(get_edit_post_link($log->post_id)),
                        esc_html($log->post_title ?: __('(Sin título)', FLAVOR_PLATFORM_TEXT_DOMAIN)),
                        esc_html(human_time_diff(strtotime($log->fecha)))
                    );
                }
                echo '</ul>';
            }
        }

        // Botón de exportación
        if (current_user_can('manage_options')) {
            printf(
                '<p class="flavor-dash-actions" style="margin-top:16px;text-align:right;">
                    <a href="%s" class="button button-small">
                        <span class="dashicons dashicons-download" style="margin-top:3px;"></span>
                        %s
                    </a>
                </p>',
                esc_url(self::get_url_exportacion()),
                esc_html__('Exportar CSV', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );
        }

        echo '</div>';

        // Estilos inline del widget
        echo '<style>
            .flavor-dash-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 12px;
                margin-bottom: 12px;
            }
            .flavor-dash-item {
                text-align: center;
                padding: 12px 8px;
                background: #f8f9fa;
                border-radius: 8px;
                border: 1px solid #e9ecef;
            }
            .flavor-dash-item .dashicons {
                display: block;
                font-size: 24px;
                width: 24px;
                height: 24px;
                margin: 0 auto 6px;
                color: #6366f1;
            }
            .flavor-dash-count {
                display: block;
                font-size: 20px;
                font-weight: 700;
                color: #1e293b;
            }
            .flavor-dash-label {
                display: block;
                font-size: 11px;
                color: #64748b;
                margin-top: 2px;
            }
            .flavor-dash-empty {
                text-align: center;
                color: #94a3b8;
                font-style: italic;
            }
            .flavor-dash-recientes {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .flavor-dash-recientes li {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 6px 0;
                border-bottom: 1px solid #f1f5f9;
            }
            .flavor-dash-recientes li:last-child {
                border-bottom: none;
            }
            .flavor-dash-recientes .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                color: #94a3b8;
            }
            .flavor-dash-recientes a {
                flex: 1;
                text-decoration: none;
            }
            .flavor-dash-fecha {
                font-size: 11px;
                color: #94a3b8;
            }
        </style>';
    }

    // =====================================================
    // BULK ACTIONS
    // =====================================================

    /**
     * Registra bulk actions
     */
    public function registrar_bulk_actions() {
        foreach ($this->post_types_habilitados as $post_type) {
            add_filter("bulk_actions-edit-{$post_type}", [$this, 'agregar_bulk_actions']);
            add_filter("handle_bulk_actions-edit-{$post_type}", [$this, 'manejar_bulk_action'], 10, 3);
        }
    }

    /**
     * Agrega opciones de bulk action
     */
    public function agregar_bulk_actions($bulk_actions) {
        foreach ($this->modulos_disponibles as $key => $modulo) {
            $bulk_actions['flavor_integrar_' . $key] = sprintf(
                __('Integrar con %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $modulo['nombre']
            );
        }
        return $bulk_actions;
    }

    /**
     * Maneja la ejecución del bulk action
     */
    public function manejar_bulk_action($redirect_to, $action, $post_ids) {
        if (strpos($action, 'flavor_integrar_') !== 0) {
            return $redirect_to;
        }

        $modulo = str_replace('flavor_integrar_', '', $action);

        if (!isset($this->modulos_disponibles[$modulo])) {
            return $redirect_to;
        }

        $integrados = 0;
        foreach ($post_ids as $post_id) {
            $resultado = $this->ejecutar_integracion($modulo, $post_id, '');
            if ($resultado) {
                $integrados++;
            }
        }

        $redirect_to = add_query_arg([
            'flavor_integrados' => $integrados,
            'flavor_modulo'     => $modulo,
        ], $redirect_to);

        return $redirect_to;
    }

    /**
     * Muestra aviso de bulk action completado
     */
    public function mostrar_aviso_bulk() {
        if (!isset($_GET['flavor_integrados'])) {
            return;
        }

        $count = intval($_GET['flavor_integrados']);
        $modulo = sanitize_key($_GET['flavor_modulo'] ?? '');
        $nombre = $this->modulos_disponibles[$modulo]['nombre'] ?? $modulo;

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            sprintf(
                _n(
                    '%d post integrado con %s.',
                    '%d posts integrados con %s.',
                    $count,
                    FLAVOR_PLATFORM_TEXT_DOMAIN
                ),
                $count,
                esc_html($nombre)
            )
        );
    }

    // =====================================================
    // FILTROS Y EXPORTACIÓN
    // =====================================================

    /**
     * Agrega dropdown de filtro por módulo en listado de posts
     */
    public function agregar_filtro_modulos($post_type) {
        if (!in_array($post_type, $this->post_types_habilitados)) {
            return;
        }

        if (empty($this->modulos_disponibles)) {
            return;
        }

        $seleccionado = $_GET['flavor_modulo_filtro'] ?? '';
        ?>
        <select name="flavor_modulo_filtro" id="flavor-modulo-filtro">
            <option value=""><?php esc_html_e('Filtrar por módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <?php foreach ($this->modulos_disponibles as $key => $modulo): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($seleccionado, $key); ?>>
                    <?php echo esc_html($modulo['nombre']); ?>
                </option>
            <?php endforeach; ?>
            <option value="_sin_integracion" <?php selected($seleccionado, '_sin_integracion'); ?>>
                <?php esc_html_e('— Sin integraciones —', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Modifica la query para filtrar por módulo
     */
    public function filtrar_por_modulo($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type') ?: 'post';
        if (!in_array($post_type, $this->post_types_habilitados)) {
            return;
        }

        $modulo_filtro = $_GET['flavor_modulo_filtro'] ?? '';
        if (empty($modulo_filtro)) {
            return;
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        if ($modulo_filtro === '_sin_integracion') {
            // Posts sin ninguna integración
            $post_ids_integrados = $this->obtener_todos_posts_integrados();
            if (!empty($post_ids_integrados)) {
                $query->set('post__not_in', $post_ids_integrados);
            }
        } else {
            // Posts integrados con módulo específico
            $post_ids = $this->obtener_posts_por_modulo($modulo_filtro);
            if (!empty($post_ids)) {
                $query->set('post__in', $post_ids);
            } else {
                // No hay posts, forzar resultado vacío
                $query->set('post__in', [0]);
            }
        }
    }

    /**
     * Obtiene todos los IDs de posts con alguna integración
     */
    private function obtener_todos_posts_integrados() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';
        $post_ids = [];

        $tablas = [
            'eventos_posts', 'cursos_posts', 'talleres_posts', 'campanias_posts',
            'comunidades_posts', 'colectivos_posts', 'email_newsletter_posts', 'biblioteca_posts'
        ];

        foreach ($tablas as $tabla_nombre) {
            $tabla = $prefix . $tabla_nombre;
            if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") === $tabla) {
                $ids = $wpdb->get_col("SELECT DISTINCT post_id FROM {$tabla}");
                $post_ids = array_merge($post_ids, $ids);
            }
        }

        return array_unique(array_map('intval', $post_ids));
    }

    /**
     * Obtiene IDs de posts integrados con un módulo específico
     */
    private function obtener_posts_por_modulo($modulo) {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tablas_map = [
            'eventos'         => 'eventos_posts',
            'cursos'          => 'cursos_posts',
            'talleres'        => 'talleres_posts',
            'campanias'       => 'campanias_posts',
            'comunidades'     => 'comunidades_posts',
            'colectivos'      => 'colectivos_posts',
            'email_marketing' => 'email_newsletter_posts',
            'biblioteca'      => 'biblioteca_posts',
        ];

        if (!isset($tablas_map[$modulo])) {
            return [];
        }

        $tabla = $prefix . $tablas_map[$modulo];
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return [];
        }

        return $wpdb->get_col("SELECT DISTINCT post_id FROM {$tabla}");
    }

    /**
     * Limpia integraciones cuando se elimina un post
     */
    public function limpiar_integraciones_post($post_id) {
        if (!in_array(get_post_type($post_id), $this->post_types_habilitados)) {
            return;
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tablas = [
            'eventos_posts', 'cursos_posts', 'talleres_posts', 'campanias_posts',
            'comunidades_posts', 'colectivos_posts', 'email_newsletter_posts', 'biblioteca_posts'
        ];

        foreach ($tablas as $tabla_nombre) {
            $tabla = $prefix . $tabla_nombre;
            if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") === $tabla) {
                $wpdb->delete($tabla, ['post_id' => $post_id], ['%d']);
            }
        }

        // Limpiar log también
        $tabla_log = $prefix . 'posts_integraciones_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_log}'") === $tabla_log) {
            $wpdb->delete($tabla_log, ['post_id' => $post_id], ['%d']);
        }

        // Limpiar meta
        delete_post_meta($post_id, '_flavor_integraciones_modulos');
    }

    /**
     * Maneja la exportación CSV de integraciones
     */
    public function manejar_exportacion_csv() {
        if (!isset($_GET['flavor_exportar_integraciones']) || $_GET['flavor_exportar_integraciones'] !== '1') {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        check_admin_referer('flavor_exportar_integraciones', 'nonce');

        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tabla_log = $prefix . 'posts_integraciones_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_log}'") !== $tabla_log) {
            wp_die(__('No hay datos para exportar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $datos = $wpdb->get_results(
            "SELECT l.*, p.post_title, u.display_name as usuario
             FROM {$tabla_log} l
             LEFT JOIN {$wpdb->posts} p ON l.post_id = p.ID
             LEFT JOIN {$wpdb->users} u ON l.usuario_id = u.ID
             ORDER BY l.fecha DESC",
            ARRAY_A
        );

        if (empty($datos)) {
            wp_die(__('No hay datos para exportar', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Generar CSV
        $filename = 'integraciones-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados
        fputcsv($output, [
            'ID',
            'Post ID',
            'Título Post',
            'Módulo',
            'Elemento ID',
            'Estado',
            'Fecha',
            'Usuario',
        ]);

        foreach ($datos as $row) {
            fputcsv($output, [
                $row['id'],
                $row['post_id'],
                $row['post_title'] ?: '(Sin título)',
                $row['modulo'],
                $row['elemento_id'],
                $row['estado'],
                $row['fecha'],
                $row['usuario'] ?: '(Sistema)',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Genera URL de exportación CSV
     */
    public static function get_url_exportacion() {
        return wp_nonce_url(
            admin_url('admin.php?flavor_exportar_integraciones=1'),
            'flavor_exportar_integraciones',
            'nonce'
        );
    }

    // =====================================================
    // PÁGINA DE ADMINISTRACIÓN
    // =====================================================

    /**
     * Registra subpáginas de administración
     */
    public function registrar_menu_admin() {
        // Página principal de integraciones
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Integraciones de Posts', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Integraciones Posts', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-integraciones-posts',
            [$this, 'renderizar_pagina_admin']
        );

        // Página de configuración
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Config. Integraciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Config. Integraciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-integraciones-config',
            [$this, 'renderizar_pagina_config']
        );
    }

    /**
     * Renderiza la página de configuración
     */
    public function renderizar_pagina_config() {
        // Guardar configuración
        if (isset($_POST['flavor_integraciones_guardar']) && check_admin_referer('flavor_integraciones_config')) {
            $config = [
                'post_types'       => array_map('sanitize_key', $_POST['post_types'] ?? []),
                'roles_permitidos' => array_map('sanitize_key', $_POST['roles_permitidos'] ?? []),
                'cache_ttl'        => absint($_POST['cache_ttl'] ?? 300),
            ];
            update_option('flavor_integraciones_config', $config);
            echo '<div class="notice notice-success"><p>' . esc_html__('Configuración guardada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';

            // Recargar configuración
            $this->cargar_configuracion();
        }

        // Obtener configuración actual
        $config = get_option('flavor_integraciones_config', []);
        $post_types_guardados = $config['post_types'] ?? $this->post_types_habilitados;
        $roles_guardados = $config['roles_permitidos'] ?? $this->roles_permitidos;
        $cache_ttl = $config['cache_ttl'] ?? 300;

        // Obtener todos los post types disponibles
        $post_types_disponibles = get_post_types(['public' => true], 'objects');

        // Obtener todos los roles
        global $wp_roles;
        $roles_disponibles = $wp_roles->roles;

        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-admin-settings" style="margin-right:8px;"></span>
                <?php esc_html_e('Configuración de Integraciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>

            <form method="post">
                <?php wp_nonce_field('flavor_integraciones_config'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e('Tipos de contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <?php foreach ($post_types_disponibles as $pt): ?>
                                    <label style="display:block;margin-bottom:6px;">
                                        <input type="checkbox"
                                               name="post_types[]"
                                               value="<?php echo esc_attr($pt->name); ?>"
                                               <?php checked(in_array($pt->name, $post_types_guardados)); ?>>
                                        <?php echo esc_html($pt->labels->singular_name); ?>
                                        <code style="font-size:11px;color:#666;">(<?php echo esc_html($pt->name); ?>)</code>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">
                                <?php esc_html_e('Selecciona qué tipos de contenido pueden integrarse con módulos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e('Roles con permisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <?php foreach ($roles_disponibles as $role_key => $role): ?>
                                    <label style="display:block;margin-bottom:6px;">
                                        <input type="checkbox"
                                               name="roles_permitidos[]"
                                               value="<?php echo esc_attr($role_key); ?>"
                                               <?php checked(in_array($role_key, $roles_guardados)); ?>
                                               <?php disabled($role_key, 'administrator'); ?>>
                                        <?php echo esc_html(translate_user_role($role['name'])); ?>
                                        <?php if ($role_key === 'administrator'): ?>
                                            <span style="color:#666;">(<?php esc_html_e('siempre habilitado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>)</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">
                                <?php esc_html_e('Selecciona qué roles de usuario pueden crear integraciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="cache_ttl"><?php esc_html_e('Tiempo de caché', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <select name="cache_ttl" id="cache_ttl">
                                <option value="60" <?php selected($cache_ttl, 60); ?>><?php esc_html_e('1 minuto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="300" <?php selected($cache_ttl, 300); ?>><?php esc_html_e('5 minutos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="900" <?php selected($cache_ttl, 900); ?>><?php esc_html_e('15 minutos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="3600" <?php selected($cache_ttl, 3600); ?>><?php esc_html_e('1 hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="0" <?php selected($cache_ttl, 0); ?>><?php esc_html_e('Desactivado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Tiempo que se mantienen en caché las consultas de integraciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e('Módulos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <div style="display:flex;flex-wrap:wrap;gap:12px;">
                                <?php foreach ($this->modulos_disponibles as $key => $modulo): ?>
                                    <div style="background:#f0f0f1;padding:10px 14px;border-radius:6px;display:flex;align-items:center;gap:8px;">
                                        <span class="dashicons dashicons-<?php echo esc_attr($modulo['icono']); ?>" style="color:#2271b1;"></span>
                                        <span><?php echo esc_html($modulo['nombre']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($this->modulos_disponibles)): ?>
                                    <p style="color:#666;font-style:italic;">
                                        <?php esc_html_e('No hay módulos activos para integración.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <p class="description" style="margin-top:10px;">
                                <?php esc_html_e('Los módulos se activan desde la configuración general del plugin.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <hr>

                <h2><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Limpiar caché', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=flavor-integraciones-config&action=limpiar_cache'), 'limpiar_cache_integraciones')); ?>"
                               class="button">
                                <span class="dashicons dashicons-trash" style="margin-top:4px;"></span>
                                <?php esc_html_e('Limpiar caché ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                            <p class="description">
                                <?php esc_html_e('Elimina todos los datos en caché de integraciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Exportar datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <td>
                            <a href="<?php echo esc_url(self::get_url_exportacion()); ?>" class="button">
                                <span class="dashicons dashicons-download" style="margin-top:4px;"></span>
                                <?php esc_html_e('Exportar CSV', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                            <p class="description">
                                <?php esc_html_e('Descarga todas las integraciones en formato CSV.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="flavor_integraciones_guardar" class="button button-primary">
                        <?php esc_html_e('Guardar cambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </p>
            </form>

            <hr>

            <h2><?php esc_html_e('Hooks disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div style="background:#f6f7f7;padding:16px;border-radius:6px;font-family:monospace;font-size:12px;">
                <p><strong>Filtros:</strong></p>
                <ul style="margin-left:20px;">
                    <li><code>flavor_integraciones_post_types</code> - <?php esc_html_e('Modificar post types habilitados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><code>flavor_integraciones_roles_permitidos</code> - <?php esc_html_e('Modificar roles permitidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><code>flavor_usuario_puede_integrar</code> - <?php esc_html_e('Filtrar permiso de integración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><code>flavor_usuario_puede_integrar_{modulo}</code> - <?php esc_html_e('Permiso por módulo específico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><code>flavor_notificar_integracion</code> - <?php esc_html_e('Activar notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                </ul>
                <p style="margin-top:12px;"><strong>Acciones:</strong></p>
                <ul style="margin-left:20px;">
                    <li><code>flavor_post_integrado_modulo</code> - <?php esc_html_e('Al integrar un post', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><code>flavor_integraciones_cache_invalidate</code> - <?php esc_html_e('Al invalidar caché', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><code>flavor_enviar_notificacion_integracion</code> - <?php esc_html_e('Enviar notificación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                </ul>
            </div>
        </div>
        <?php

        // Manejar limpieza de caché
        if (isset($_GET['action']) && $_GET['action'] === 'limpiar_cache' && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'limpiar_cache_integraciones')) {
            $this->cache_invalidate();
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_flavor_int_%' OR option_name LIKE '_transient_timeout_flavor_int_%'");
            echo '<div class="notice notice-success"><p>' . esc_html__('Caché limpiado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderiza la página de administración
     */
    public function renderizar_pagina_admin() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        // Filtros
        $filtro_modulo = sanitize_key($_GET['modulo'] ?? '');
        $filtro_estado = sanitize_key($_GET['estado'] ?? '');
        $paged = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 20;

        // Obtener datos
        $tabla_log = $prefix . 'posts_integraciones_log';
        $tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_log}'") === $tabla_log;

        $integraciones = [];
        $total = 0;

        if ($tabla_existe) {
            $where = "WHERE 1=1";
            if ($filtro_modulo) {
                $where .= $wpdb->prepare(" AND l.modulo = %s", $filtro_modulo);
            }
            if ($filtro_estado) {
                $where .= $wpdb->prepare(" AND l.estado = %s", $filtro_estado);
            }

            $total = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_log} l {$where}"
            );

            $offset = ($paged - 1) * $per_page;
            $integraciones = $wpdb->get_results(
                "SELECT l.*, p.post_title, u.display_name as usuario_nombre
                 FROM {$tabla_log} l
                 LEFT JOIN {$wpdb->posts} p ON l.post_id = p.ID
                 LEFT JOIN {$wpdb->users} u ON l.usuario_id = u.ID
                 {$where}
                 ORDER BY l.fecha DESC
                 LIMIT {$per_page} OFFSET {$offset}"
            );
        }

        $total_pages = ceil($total / $per_page);

        // Estadísticas rápidas
        $stats = [];
        if ($tabla_existe) {
            $stats = $wpdb->get_results(
                "SELECT modulo, COUNT(*) as total,
                        SUM(CASE WHEN estado = 'exito' THEN 1 ELSE 0 END) as exitosas
                 FROM {$tabla_log}
                 GROUP BY modulo"
            );
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-admin-plugins" style="margin-right:8px;"></span>
                <?php esc_html_e('Integraciones de Posts con Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>
            <a href="<?php echo esc_url(self::get_url_exportacion()); ?>" class="page-title-action">
                <span class="dashicons dashicons-download" style="vertical-align:middle;"></span>
                <?php esc_html_e('Exportar CSV', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <hr class="wp-header-end">

            <?php if (!empty($stats)): ?>
            <div class="flavor-stats-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin:20px 0;">
                <?php foreach ($stats as $stat):
                    $modulo_info = $this->modulos_disponibles[$stat->modulo] ?? null;
                    $nombre = $modulo_info['nombre'] ?? ucfirst($stat->modulo);
                    $icono = $modulo_info['icono'] ?? 'admin-plugins';
                ?>
                <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px;text-align:center;">
                    <span class="dashicons dashicons-<?php echo esc_attr($icono); ?>" style="font-size:28px;width:28px;height:28px;color:#2271b1;"></span>
                    <div style="font-size:24px;font-weight:600;margin:8px 0;"><?php echo intval($stat->total); ?></div>
                    <div style="color:#666;"><?php echo esc_html($nombre); ?></div>
                    <div style="font-size:11px;color:#999;"><?php printf(__('%d exitosas', FLAVOR_PLATFORM_TEXT_DOMAIN), $stat->exitosas); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Filtros -->
            <form method="get" style="margin:16px 0;">
                <input type="hidden" name="page" value="flavor-integraciones-posts">
                <select name="modulo">
                    <option value=""><?php esc_html_e('Todos los módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($this->modulos_disponibles as $key => $modulo): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($filtro_modulo, $key); ?>>
                            <?php echo esc_html($modulo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="estado">
                    <option value=""><?php esc_html_e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="exito" <?php selected($filtro_estado, 'exito'); ?>><?php esc_html_e('Exitoso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="error" <?php selected($filtro_estado, 'error'); ?>><?php esc_html_e('Error', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
                <button type="submit" class="button"><?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <?php if ($filtro_modulo || $filtro_estado): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-integraciones-posts')); ?>" class="button">
                        <?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Tabla -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th><?php esc_html_e('Post', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width:140px;"><?php esc_html_e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width:100px;"><?php esc_html_e('Elemento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width:80px;"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width:140px;"><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width:120px;"><?php esc_html_e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($integraciones)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:#666;">
                                <?php esc_html_e('No hay integraciones registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($integraciones as $item):
                            $modulo_info = $this->modulos_disponibles[$item->modulo] ?? null;
                            $icono = $modulo_info['icono'] ?? 'admin-plugins';
                        ?>
                        <tr>
                            <td><?php echo intval($item->id); ?></td>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($item->post_id)); ?>">
                                    <?php echo esc_html($item->post_title ?: __('(Sin título)', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </a>
                                <div class="row-actions">
                                    <a href="<?php echo esc_url(get_permalink($item->post_id)); ?>" target="_blank">
                                        <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="dashicons dashicons-<?php echo esc_attr($icono); ?>" style="color:#666;"></span>
                                <?php echo esc_html($modulo_info['nombre'] ?? $item->modulo); ?>
                            </td>
                            <td>
                                <?php if ($item->elemento_id): ?>
                                    <code>#<?php echo intval($item->elemento_id); ?></code>
                                <?php else: ?>
                                    <span style="color:#999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item->estado === 'exito'): ?>
                                    <span style="color:#00a32a;">✓ <?php esc_html_e('Éxito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php else: ?>
                                    <span style="color:#d63638;">✗ <?php esc_html_e('Error', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span title="<?php echo esc_attr($item->fecha); ?>">
                                    <?php echo esc_html(human_time_diff(strtotime($item->fecha), current_time('timestamp')) . ' ' . __('atrás', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html($item->usuario_nombre ?: __('Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(_n('%s elemento', '%s elementos', $total, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($total)); ?>
                    </span>
                    <?php
                    echo paginate_links([
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'current'   => $paged,
                        'total'     => $total_pages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ]);
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // =====================================================
    // NOTIFICACIONES
    // =====================================================

    /**
     * Envía notificación al integrar un post
     */
    public function notificar_integracion($post_id, $modulo, $elemento_id, $resultado_id) {
        // Solo notificar si hay suscriptores configurados
        $notificar = apply_filters('flavor_notificar_integracion', false, $post_id, $modulo);

        if (!$notificar) {
            return;
        }

        $post = get_post($post_id);
        $usuario = wp_get_current_user();
        $nombre_modulo = $this->modulos_disponibles[$modulo]['nombre'] ?? $modulo;

        $mensaje = sprintf(
            __('%s ha integrado "%s" con %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $usuario->display_name,
            $post->post_title,
            $nombre_modulo
        );

        // Hook para sistemas de notificación personalizados
        do_action('flavor_enviar_notificacion_integracion', [
            'tipo'        => 'integracion_post',
            'post_id'     => $post_id,
            'modulo'      => $modulo,
            'elemento_id' => $elemento_id,
            'usuario_id'  => $usuario->ID,
            'mensaje'     => $mensaje,
        ]);
    }
}

// Inicializar con todos los hooks
add_action('plugins_loaded', function() {
    $instance = Flavor_WP_Module_Integrations::get_instance();

    // Shortcodes
    add_action('init', [$instance, 'registrar_shortcodes']);

    // API REST
    add_action('rest_api_init', [$instance, 'registrar_api_rest']);

    // Columnas admin
    add_action('admin_init', [$instance, 'registrar_columnas_admin']);

    // Widget dashboard
    add_action('wp_dashboard_setup', [$instance, 'registrar_widget_dashboard']);

    // Bulk actions
    add_action('admin_init', [$instance, 'registrar_bulk_actions']);
    add_action('admin_notices', [$instance, 'mostrar_aviso_bulk']);

    // Notificaciones
    add_action('flavor_post_integrado_modulo', [$instance, 'notificar_integracion'], 10, 4);

    // Subpágina de administración
    add_action('admin_menu', [$instance, 'registrar_menu_admin'], 99);
}, 25);
